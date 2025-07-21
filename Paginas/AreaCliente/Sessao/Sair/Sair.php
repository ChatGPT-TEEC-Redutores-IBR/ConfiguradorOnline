<?php
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once __DIR__ . '/../../../../Restritos/Credenciais/TokenBlacklist.php';
$token = $_COOKIE['auth_token'] ?? '';
if ($token) {
    blacklist_token($token);
}

setcookie('auth_token', '', time() - 3600, '/', 'configurador.redutoresibr.com.br', true, true);

header("Location: https://configurador.redutoresibr.com.br/");
exit;
?>