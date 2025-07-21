<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/JWT_Helper.php';
require_once $baseDir . '/Restritos/Credenciais/TokenBlacklist.php';

$token = $_COOKIE['auth_token'] ?? '';
if (!$token) {
    http_response_code(401);
    
    echo json_encode(["erro" => "⚠️ Usuário não autenticado."]);
    exit;
}


$segredo = getenv('JWT_SECRET');
if ($segredo === false) {
    $segredo = trim(file_get_contents($baseDir . '/Restritos/Credenciais/Segredo.jwt'));
}

$dados = JWTHelper::decode($token, $segredo);
if (!$dados || is_token_blacklisted($token)) {
    http_response_code(403);
    echo json_encode(["erro" => "⚠️ Credenciais inválidas."]);
    exit;
}


$cpfcnpj = preg_replace('/\D/', '', $dados['cpfcnpj'] ?? '');
$empresa = $dados['empresa'] ?? '';
if (!$empresa) {
    require_once $baseDir . '/Restritos/Credenciais/EmpresaHelper.php';
    $empresa = obter_nome_empresa($cpfcnpj, $baseDir);
}

$ttl = getenv('JWT_TTL');
if ($ttl === false) {
    $ttl = 86400; // 24 horas por inatividade
} else {
    $ttl = (int)$ttl;
}

$dados['exp'] = time() + $ttl;
$novoToken = JWTHelper::encode($dados, $segredo);
setcookie('auth_token', $novoToken, [
    'expires' => time() + $ttl,
    'path' => '/',
    'domain' => 'configurador.redutoresibr.com.br',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

echo json_encode([
    "sucesso" => true,
    "email" => $dados['email'],
    "grupo" => $dados['grupo'],
    "nome" => strtoupper($dados['nome']),
    "cpfcnpj" => $dados['cpfcnpj'] ?? '',
    "empresa" => $empresa,
    "codigo" => $dados['codigo'],
    "exp" => $dados['exp']
]);
?>