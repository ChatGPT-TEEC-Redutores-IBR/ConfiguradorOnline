<?php
header('Content-Type: application/json; charset=UTF-8');
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once __DIR__ . '/../../../../Restritos/Credenciais/JWT_Helper.php';
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/BancoDados.php';
require_once __DIR__ . '/../../../../Restritos/Credenciais/Configurador_Helper.php';

$token = $_COOKIE['auth_token'] ?? '';
if (!$token) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

    $segredo = getenv('JWT_SECRET');
    if ($segredo === false) {
        $segredo = trim(file_get_contents(__DIR__ . '/../../../../Restritos/Credenciais/Segredo.jwt'));
    }

$dados = JWTHelper::decode($token, $segredo);
if (!$dados) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

function parseDataTimestamp(string $str): int {
    if (strpos($str, '/') !== false) {
        [$dataParte, $horaParte] = array_pad(explode(' ', $str), 2, '');
        [$dia, $mes, $ano] = array_map('intval', explode('/', $dataParte));
        [$hora, $min, $seg] = array_pad(explode(':', $horaParte), 3, 0);
        return mktime((int)$hora, (int)$min, (int)$seg, (int)$mes, (int)$dia, (int)$ano);
    }
    $ts = strtotime($str);
    return $ts ?: 0;
}

function obterInfo(PDO $pdo, string $entrada): array {
    static $cache = [];
    if (array_key_exists($entrada, $cache)) return $cache[$entrada];

    $entrada = strtoupper(trim($entrada));
    $isCodigo = preg_match('/^[A-Z]{2,4}\.[0-9]{8}$/', $entrada);

    $sql = $isCodigo
        ? "SELECT TOP 1 CD_PRODUTO, DS_REFERENCIA, DS_PRODUTO FROM MMPR_PRODUTO WHERE CD_PRODUTO = ? AND ID_STATUS = 0 AND CD_PRODCONFIG IS NOT NULL"
        : "SELECT TOP 1 CD_PRODUTO, DS_REFERENCIA, DS_PRODUTO FROM MMPR_PRODUTO WHERE DS_REFERENCIA = ? AND ID_STATUS = 0 AND CD_PRODCONFIG IS NOT NULL";

    try {
        $stm = $pdo->prepare($sql);
        $stm->execute([$entrada]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        $cache[$entrada] = [
            'codigo' => $row['CD_PRODUTO'] ?? null,
            'referencia' => $row['DS_REFERENCIA'] ?? $entrada,
            'descricao' => $row['DS_PRODUTO'] ?? null
        ];
    } catch (PDOException $e) {
        $cache[$entrada] = [
            'codigo' => null,
            'referencia' => $entrada,
            'descricao' => null
        ];
    }
    return $cache[$entrada];
}

function gerarLink(PDO $pdo, string $entrada): ?string {
    $entrada = strtoupper(trim($entrada));
    $isCodigo = preg_match('/^[A-Z]{2,4}\.[0-9]{8}$/', $entrada);
    $isReferencia = preg_match('/^[0-9A-Z]{1,5}(\.[0-9A-Z]{1,5}){2,}$/', $entrada);
    if (!$isCodigo && !$isReferencia) {
        return null;
    }

    $sql = "SELECT
                PRODUTO.CD_PRODCONFIG,
                ESTRUTURA.NM_VARIAVEL,
                ESTRUTURA.CD_ITEM,
                MIN(ESTRUTURA.DS_VARIAVEL.value('(/VariavelOpcaoSimples/Valor)[1]', 'varchar(4000)')) AS RESPOSTA_SELETOR,
                MIN(ESTRUTURA.DS_VARIAVEL.value('(/VariavelOpcaoMultiplas/Valor)[1]', 'varchar(4000)')) AS RESPOSTA_SELETORMULTIPLO
            FROM MMPR_PRODUTOESTRUTURA AS ESTRUTURA
            INNER JOIN MMPR_PRODUTO AS PRODUTO
                ON ESTRUTURA.CD_EMPRESA = PRODUTO.CD_EMPRESA
                AND ESTRUTURA.CD_PRODUTO = PRODUTO.CD_PRODUTO
            WHERE " . ($isCodigo ? "PRODUTO.CD_PRODUTO = ?" : "PRODUTO.DS_REFERENCIA = ?") . "
              AND (
                LTRIM(RTRIM(ESTRUTURA.DS_VARIAVEL.value('(/VariavelOpcaoSimples/Valor)[1]', 'varchar(4000)'))) IS NOT NULL AND
                LTRIM(RTRIM(ESTRUTURA.DS_VARIAVEL.value('(/VariavelOpcaoSimples/Valor)[1]', 'varchar(4000)'))) <> ''
             OR
                LTRIM(RTRIM(ESTRUTURA.DS_VARIAVEL.value('(/VariavelOpcaoMultiplas/Valor)[1]', 'varchar(4000)'))) IS NOT NULL AND
                LTRIM(RTRIM(ESTRUTURA.DS_VARIAVEL.value('(/VariavelOpcaoMultiplas/Valor)[1]', 'varchar(4000)'))) <> ''
             )
                AND PRODUTO.ID_STATUS = 0
            GROUP BY
                PRODUTO.CD_PRODCONFIG,
                ESTRUTURA.NM_VARIAVEL,
                ESTRUTURA.CD_ITEM
            ORDER BY
                ESTRUTURA.CD_ITEM";

    try {
        $query = $pdo->prepare($sql);
        $query->execute([$entrada]);
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
         if (!$rows) {
            $sqlCfg = "SELECT TOP 1 CD_PRODCONFIG FROM MMPR_PRODUTO WHERE " . ($isCodigo ? "CD_PRODUTO = ?" : "DS_REFERENCIA = ?") . " AND ID_STATUS = 0 AND CD_PRODCONFIG IS NOT NULL";
            $q = $pdo->prepare($sqlCfg);
            $q->execute([$entrada]);
            $cd = $q->fetchColumn();
            if (!$cd) return null;
            $codAmigavel = codigo_amigavel($cd);

            return "https://configurador.redutoresibr.com.br/Configurador{$codAmigavel}";
        }

        $variaveis = [];
        $cdProdConfig = $rows[0]['CD_PRODCONFIG'] ?? '';
        if (!$cdProdConfig) {
            $sqlCfg = "SELECT TOP 1 CD_PRODCONFIG FROM MMPR_PRODUTO WHERE " . ($isCodigo ? "CD_PRODUTO = ?" : "DS_REFERENCIA = ?") . " AND ID_STATUS = 0 AND CD_PRODCONFIG IS NOT NULL";
            $q = $pdo->prepare($sqlCfg);
            $q->execute([$entrada]);
            $cdProdConfig = $q->fetchColumn() ?: '';
        }

        foreach ($rows as $row) {
            $chave = trim($row['NM_VARIAVEL'] ?? '');
            $valorSimples = trim($row['RESPOSTA_SELETOR'] ?? '');
            $valorMultiplo = trim($row['RESPOSTA_SELETORMULTIPLO'] ?? '');
            $valorFinal = $valorMultiplo ?: $valorSimples;
            if ($chave && $valorFinal) {
                $variaveis[$chave] = $valorFinal;
            }
        }
        $queryString = http_build_query($variaveis, '', '&', PHP_QUERY_RFC3986);
        $codAmigavel = codigo_amigavel($cdProdConfig);

        $linkBase = "https://configurador.redutoresibr.com.br/Configurador{$codAmigavel}";
        return $queryString ? "$linkBase?$queryString" : $linkBase;
        } catch (PDOException $e) {
        return null;
    }
}

$termo = strtoupper(trim(filter_input(INPUT_GET, 'pesquisa', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
$dataFiltro = trim(filter_input(INPUT_GET, 'data', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
$pagina = max(0, (int)filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT));
$limite = (int)filter_input(INPUT_GET, 'limite', FILTER_VALIDATE_INT);
if ($limite <= 0) $limite = 10;
if ($limite > 100) $limite = 100;

$email = strtolower($dados['email']);
try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $stmtTot = $pdo->prepare(
        "SELECT COUNT(*) FROM _USR_CONF_SITE_HISTORICO_PRODUTO WHERE DS_EMAIL = ?"
    );
    $stmtTot->execute([$email]);
    $total = (int)$stmtTot->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT DS_REFERENCIA AS produto, DT_DATA AS data
           FROM _USR_CONF_SITE_HISTORICO_PRODUTO
          WHERE DS_EMAIL = ?
          ORDER BY DT_DATA DESC
          OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"
    );
    $stmt->bindValue(1, $email);
    $stmt->bindValue(2, $pagina * $limite, PDO::PARAM_INT);
    $stmt->bindValue(3, $limite, PDO::PARAM_INT);
    $stmt->execute();
    $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($lista as &$item) {
        $ts = parseDataTimestamp($item['data']);
        $item['data'] = date('d/m/Y H:i:s', $ts);
        $info = obterInfo($pdo, $item['produto']);
        $item['codigo'] = $info['codigo'] ?? '';
        $item['descricao'] = $info['descricao'] ?? '';
        $item['referencia'] = $info['referencia'] ?? $item['produto'];
        $item['link'] = gerarLink($pdo, $item['referencia']) ?: '';
        $item['produto'] = $item['referencia'];
    }
    unset($item);
    
    echo json_encode(['total' => $total, 'lista' => $lista]);
    $pdo = null;
} catch (PDOException $e) {
    log_event('ListarProdutos: ' . $e->getMessage());
    echo json_encode([]);
}

?>