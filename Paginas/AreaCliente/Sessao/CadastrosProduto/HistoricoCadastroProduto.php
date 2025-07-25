<?php
header('Content-Type: application/json; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once __DIR__ . '/../../../../LogsErros/Logs.php';
require_once __DIR__ . '/../../../../Restritos/Credenciais/JWT_Helper.php';
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/BancoDados.php';

$token = $_COOKIE['auth_token'] ?? '';
if (!$token) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário não autenticado.']);
    exit;
}

$segredo = getenv('JWT_SECRET');
if ($segredo === false) {
    $segredo = trim(file_get_contents(__DIR__ . '/../../../../Restritos/Credenciais/Segredo.jwt'));
}

$dados = JWTHelper::decode($token, $segredo);
if (!$dados) {
    http_response_code(403);
    echo json_encode(['erro' => 'Token inválido ou expirado.']);
    exit;
}

$produto = strtoupper(trim(filter_input(INPUT_POST, 'produto', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
$oportunidade = trim(filter_input(INPUT_POST, 'oportunidade', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

if (!$produto) {
    echo json_encode(['erro' => 'Codigo invalido']);
    exit;
}

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

$sql = "INSERT INTO _USR_CONF_SITE_HISTORICO_CADASTROS (DS_EMAIL, DS_REFERENCIA, CD_OPORTUNIDADE, DT_DATA)
            SELECT ?, ?, ?, CONVERT(VARCHAR(19), GETDATE(), 120)
             WHERE NOT EXISTS (
                 SELECT 1 FROM _USR_CONF_SITE_HISTORICO_CADASTROS
                  WHERE DS_EMAIL = ? AND DS_REFERENCIA = ? AND CD_OPORTUNIDADE = ?
             )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        strtolower($dados['email']),
        $produto,
        $oportunidade,
        strtolower($dados['email']),
        $produto,
        $oportunidade
    ]);

    echo json_encode(['sucesso' => true]);
    $pdo = null;
} catch (PDOException $e) {
    log_event('RegistrarCadastro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao registrar cadastro']);
}
?>