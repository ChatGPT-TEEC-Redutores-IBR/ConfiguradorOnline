<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/RateLimiter.php';
require_once $baseDir . '/Restritos/Credenciais/BancoDados.php';
require_once $baseDir . '/Restritos/Credenciais/Configurador_Helper.php';

if (!check_rate_limit('solicitar_cadastro', 10, 3600)) {
    http_response_code(429);
    echo '⚠️ Limite Máximo de Solicitações Excedido. Tente novamente em 1 hora.';
    exit;
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

$nome       = strtoupper(trim(filter_input(INPUT_POST, 'nome', FILTER_UNSAFE_RAW) ?? ''));
$email      = strtoupper(trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? ''));
$referencia = strtoupper(trim(filter_input(INPUT_POST, 'referencia_produto', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
$link       = trim(filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL) ?? '');

if (!$nome || !$email || !$referencia) {
    echo '⚠️ Dados Incompletos.';
    exit;
}

$agora      = new DateTime('now');
$created    = $agora->format('Y-m-d H:i:s'); 
$dataTitulo = $agora->format('d/m/Y H:i');

$tokenPipe = '6741b82d59d8d230f2aacd0b88f1ea99';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    if (!$link) {
        $link = gerarLink($pdo, $referencia) ?: '';
    }

    $dealData = [
        'pipeline_id'  => 96591,
        'stage_id'     => 617200,
        'title'        => "$referencia - $email - $nome - $dataTitulo",
        'tags'         => [['id' => 359705], ['id' => 344438], ['id' => 362615]],
        'created_at'   => $created,
        'custom_fields'=> [
            ['id' => 259977, 'value' => $link],
            ['id' => 259975, 'value' => $nome],
            ['id' => 259976, 'value' => $referencia]
        ]
    ];

    $contextDeal = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nToken: $tokenPipe\r\n",
            'content' => json_encode($dealData)
        ]
    ]);
    $respDeal = file_get_contents('https://api.pipe.run/v1/deals', false, $contextDeal);
    $dealJson = json_decode($respDeal, true);
    $dealId = $dealJson['deal_id'] ?? $dealJson['id'] ?? $dealJson['data']['deal_id'] ?? $dealJson['data']['id'] ?? '';

    echo "✅ Oportunidade $dealId enviada! Em breve nossos Consultores Comerciais entrarão em contato.";
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }
    ignore_user_abort(true);

    $personId = null;
    $personData = [
        'name'          => $nome,
        'contactEmails' => [strtolower($email)]
    ];
    $contextPerson = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nToken: $tokenPipe\r\n",
            'content' => json_encode($personData)
        ]
    ]);
    $respPerson = @file_get_contents('https://api.pipe.run/v1/persons', false, $contextPerson);
    if ($respPerson !== false) {
        $personJson = json_decode($respPerson, true);
        $personId = $personJson['person_id'] ?? $personJson['id'] ?? $personJson['data']['person_id'] ?? $personJson['data']['id'] ?? null;
    }
    if ($personId) {
        $contextAddPerson = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => "Accept: application/json\r\nToken: $tokenPipe\r\n"
            ]
        ]);
        @file_get_contents("https://api.pipe.run/v1/deals/$dealId/persons/$personId", false, $contextAddPerson);
    }

    $textoNota = "Nome: $nome<br>Email: $email<br>Referência: $referencia";
    $contextNote = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nToken: $tokenPipe\r\n",
            'content' => json_encode(['deal_id' => $dealId, 'text' => $textoNota])
        ]
    ]);
    @file_get_contents('https://api.pipe.run/v1/notes', false, $contextNote);

    $sql = "INSERT INTO _USR_CONF_SITE_HISTORICO_CADASTROS (DS_EMAIL, DS_REFERENCIA, CD_OPORTUNIDADE, DT_DATA)
            SELECT ?, ?, ?, CONVERT(VARCHAR(19), GETDATE(), 120)
             WHERE NOT EXISTS (
                 SELECT 1 FROM _USR_CONF_SITE_HISTORICO_CADASTROS
                  WHERE DS_EMAIL = ? AND DS_REFERENCIA = ? AND CD_OPORTUNIDADE = ?
             )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        strtolower($email),
        $referencia,
        $dealId,
        strtolower($email),
        $referencia,
        $dealId
    ]);

        $stmtLink = $pdo->prepare(
        "SELECT DS_LINK FROM _USR_CONF_SITE_HISTORICO_CADASTROS
          WHERE DS_EMAIL = ? AND DS_REFERENCIA = ? AND CD_OPORTUNIDADE = ?"
    );
    $stmtLink->execute([
        strtolower($email),
        $referencia,
        $dealId
    ]);
    $linkBanco = trim($stmtLink->fetchColumn() ?: '');

    if ($linkBanco) {
        $dealUpdate = [
            'custom_fields' => [
                ['id' => 259977, 'value' => $linkBanco]
            ]
        ];
        $contextUpdate = stream_context_create([
            'http' => [
                'method'  => 'PUT',
                'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nToken: $tokenPipe\r\n",
                'content' => json_encode($dealUpdate)
            ]
        ]);
        @file_get_contents("https://api.pipe.run/v1/deals/$dealId", false, $contextUpdate);
    }
    
    $pdo = null;
    log_event("Cadastro do produto $referencia solicitado por $email");
} catch (Exception $e) {
    log_event('Erro em SolicitacaoCadastro: ' . $e->getMessage());
    http_response_code(500);
    echo '⚠️ Erro ao enviar solicitação.';
}
?>