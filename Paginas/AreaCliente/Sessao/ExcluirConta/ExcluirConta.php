<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/LogsErros/Logs.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/JWT_Helper.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/TokenBlacklist.php';
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/BancoDados.php';

$tokenEmail = filter_input(INPUT_GET, 'token', FILTER_UNSAFE_RAW) ?? '';
if (!preg_match('/^[A-Fa-f0-9]{64}$/', $tokenEmail)) {
    log_event('Token exclusao invalido');
    header('Location: /AreaCliente?exclusao=token_invalido');
    exit;
}

$arquivo = $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/TokensExclusao/' . $tokenEmail . '.json';
if (!file_exists($arquivo)) {
    log_event('Arquivo token exclusao inexistente');
    header('Location: /AreaCliente?exclusao=token_invalido');
    exit;
}

$dados = json_decode(file_get_contents($arquivo), true);
$criado = $dados['criadoEm'] ?? 0;
if (time() - $criado > 24*60*60) {
    unlink($arquivo);
    log_event('Token exclusao expirado');
    header('Location: /AreaCliente?exclusao=token_expirado');
    exit;
}

$email = strtolower(trim($dados['email'] ?? ''));

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $tabelas = [
        '_USR_CONF_SITE_HISTORICO_PRODUTO',
        '_USR_CONF_SITE_HISTORICO_DESENHO',
        '_USR_CONF_SITE_HISTORICO_COTACAO',
        '_USR_CONF_SITE_CADASTROS'
    ];

    foreach ($tabelas as $tbl) {
        $stmt = $pdo->prepare("DELETE FROM $tbl WHERE DS_EMAIL = ?");
        $stmt->execute([$email]);
    }

    $tokenCookie = $_COOKIE['auth_token'] ?? '';
    if ($tokenCookie) {
        blacklist_token($tokenCookie);
    }
    setcookie('auth_token', '', time() - 3600, '/', 'configurador.redutoresibr.com.br', true, true);

    unlink($arquivo);
    log_event('Conta excluida: ' . $email);
    header('Location: /AreaCliente?exclusao=sucesso');
    exit;
} catch (PDOException $e) {
    log_event('ExcluirConta: ' . $e->getMessage());
    header('Location: /AreaCliente?exclusao=erro');
    exit;
}
?>