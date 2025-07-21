<?php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=()');
header("Content-Security-Policy: default-src 'self'; "
    . "img-src 'self' data:; "
    . "script-src 'self'; "
    . "style-src 'self'; "
    . "font-src 'self' data:; "
    . "media-src 'self' https://cdn.jsdelivr.net; "
    . "connect-src 'self'; "
    . "form-action 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "frame-ancestors 'none';");

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ini_set('log_errors', 1);

$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
$logPath = $baseDir . '/Restritos/logs';
if (!is_dir($logPath)) {
    mkdir($logPath, 0700, true);
}
$phpErrorLog = $logPath . '/php_errors.log';
ini_set('error_log', $phpErrorLog);
if (!file_exists($phpErrorLog)) {
    touch($phpErrorLog);
    chmod($phpErrorLog, 0600);
}
ini_set('error_log', $phpErrorLog);

require_once $baseDir . '/LogsErros/Logs.php';
require_once __DIR__ . '/Sanitizacao.php';
require_once __DIR__ . '/CSRF.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token();
}

?>