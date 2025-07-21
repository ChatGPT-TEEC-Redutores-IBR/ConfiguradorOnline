<?php
header('Content-Type: application/json; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';
require_once $baseDir . '/Restritos/Credenciais/Configurador_Helper.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $entrada = trim(filter_input(INPUT_POST, 'valor', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $entrada = strtoupper($entrada);

    $isCodigo = preg_match('/^[A-Z]{2,4}\.[0-9]{8}$/', $entrada);
    $isReferencia = preg_match('/^[0-9A-Z]{1,5}(\.[0-9A-Z]{1,5}){2,}$/', $entrada);

    if (!$isCodigo && !$isReferencia) {
        echo json_encode(['erro' => 'Entrada inválida.']);
        exit;
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

    $query = $pdo->prepare($sql);
    $query->execute([$entrada]);

    $rows = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo json_encode(['erro' => 'Produto não encontrado.']);
        exit;
    }

    $variaveis = [];
    $cdProdConfig = $rows[0]['CD_PRODCONFIG'] ?? '';

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
    $amigavel = codigo_amigavel($cdProdConfig);

    $url = "https://configurador.redutoresibr.com.br/Configurador{$amigavel}?$queryString";
    echo json_encode(['url' => $url]);

} catch (PDOException $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}

$pdo = null;
?>
