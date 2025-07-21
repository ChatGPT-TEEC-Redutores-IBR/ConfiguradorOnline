<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/RateLimiter.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';

if (!check_rate_limit('solicitar_desenho', 10, 3600)) {
    http_response_code(429);
    echo '⚠️ Limite Máximo de Solicitações Excedido. Tente novamente em 1 hora.';
    exit;
}

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
       PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

$formato    = strtoupper(filter_input(INPUT_POST, 'formato', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
$cdProduto  = strtoupper(filter_input(INPUT_POST, 'cd_produto', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
$empresa    = strtoupper(filter_input(INPUT_POST, 'cd_empresa', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '001');

$nrCompl       = 'RDS';
$cdResponsavel = $empresa;
$cdTipo        = 1;
$idStatus      = 0;
$usuaCriacao   = 'REDUTORES IBR';
$complorigem   = 'PRODUTO';
$tipoorigem    = 'Avant.BO.Materiais.Produto';
$atributo1     = '001';
$atributo2     = '001';
$atributo3     = $empresa . ',' . $cdProduto;
$atributo4     = $empresa;
$drvw_project = explode('.', $cdProduto)[0];

$dataFormatada = date('m-d-Y');
$dataFormatadaInicio = date('d/m/Y H:i:s');

$nome    = mb_strtoupper(trim(filter_input(INPUT_POST, 'nome', FILTER_UNSAFE_RAW) ?? ''), 'UTF-8');
$empresacliente = strtoupper(filter_input(INPUT_POST, 'empresa', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
$email   = strtoupper(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$cpfcnpj = preg_replace('/\D/', '', filter_input(INPUT_POST, 'cpfcnpj', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

$sqlDoc = "SELECT ISNULL(MAX(CAST(CD_DOCUMENTO AS INT)), 0) + 1 AS PROXIMO
           FROM PMPR_PROJETO
           WHERE NR_COMPL = ?";
$stm = $pdo->prepare($sqlDoc);
$stm->execute([$nrCompl]);
$cdDocumento = $stm->fetch(PDO::FETCH_ASSOC)['PROXIMO'] ?? 1;

$drvw_idfield = $empresa . '-' . $cdDocumento . '-' . $nrCompl;

$sql = "INSERT INTO PMPR_PROJETO (
    CD_EMPRESA, CD_DOCUMENTO, NR_COMPL,
    DT_EMISSAO, DATA_CRIACAO, DATA_MODIFIC, DT_ALTERSTATUS,
    CD_RESPONSAVEL, DS_NOME, CD_TIPO, ID_STATUS,
    PC_DESCTO1, PC_DESCTO2, PC_DESCTO3, PC_DESCTO4, PC_DESCTO5,
    PC_DESCTO6, PC_DESCTO7, PC_DESCTO8, PC_DESCTO9, PC_DESCTO10,
    PC_ACRESCIMO, PC_TOTALDESCONTO, PC_TOTALACRESCIMO,
    VL_DESCTOADIC, VL_ACRESADIC, VL_DURACAO, VL_TRABALHO, VL_CUSTO,
    VL_TOTALATENDER, VL_TOTALFINANCEIRO,
    CD_DOCDESTINO, CD_DOCORIGEM,
    NR_COMPLORIGEM, DS_TIPOORIGEM, DS_ATRIBUTO5,
    DS_ATRIBUTO1, DS_ATRIBUTO2, DS_ATRIBUTO3,
    USUA_CRIACAO, USUA_MODIFIC
) VALUES (
    ?, ?, ?, 
    ?, ?, ?, ?,
    ?, ?, ?, ?,
    0, 0, 0, 0, 0,
    0, 0, 0, 0, 0,
    0, 0, 0,
    0, 0, 0, 0, 0,
    0, 0,
    0, 0,
    ?, ?, ?,
    ?, ?, ?,
    ?, ?
)";

$insert = $pdo->prepare($sql);
$insert->execute([
    $empresa, $cdDocumento, $nrCompl,
    $dataFormatada, $dataFormatada, $dataFormatada, $dataFormatada,
    $cdResponsavel, $formato, $cdTipo, $idStatus,
    $complorigem, $tipoorigem, $cdProduto,
    $atributo1, $atributo2, $atributo3,
    $usuaCriacao, $usuaCriacao
]);

$sql2 = "INSERT INTO _USR_PMPR_PROJETO (
    CD_EMPRESA, CD_DOCUMENTO, NR_COMPL,
    DT_SOLICITACAO, DS_PRIORITARIO,
    CD_NOMECLIENTE, NR_CPFCNPJ, CD_EMAILCLIENTE,
    DS_SITE
) VALUES (
    ?, ?, ?, 
    ?, 'False',
    ?, ?, ?,
    'True'
)";

$insert2 = $pdo->prepare($sql2);
$insert2->execute([
    $empresa, $cdDocumento, $nrCompl,
    $dataFormatadaInicio,
    $nome, $cpfcnpj, $email
]);

$sql3 = "INSERT INTO _USR_PMPR_PROJETO_DRIVEWORKS (
    CD_EMPRESA, CD_DOCUMENTO, NR_COMPL,
    DRVW_IDFIELD, DRVW_TRANSITION, DRVW_PROJECT, DRVW_STATE
) VALUES (
    ?, ?, ?,
    ?, 'Release', ?, 'Novo' 
)";

$insert3 = $pdo->prepare($sql3);
$insert3->execute([
    $empresa, $cdDocumento, $nrCompl,
    $drvw_idfield, $drvw_project
]);

    log_event("Desenho do produto $cdProduto gerada por $email");
    echo $drvw_idfield;
    
$pdo = null;
} catch (PDOException $e) {
    log_event('Erro em SolicitacaoDesenho: ' . $e->getMessage());
    http_response_code(500);
    echo '⚠️ Erro ao Salvar Dados.';
}
?>
