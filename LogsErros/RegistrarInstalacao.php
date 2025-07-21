<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
require_once __DIR__ . '/Logs.php';

$page = trim(filter_input(INPUT_POST, 'page', FILTER_SANITIZE_URL)) ?: 'N/A';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';

log_event('PWA Instalado em ' . $page . ' UA:' . $userAgent);

echo json_encode(['sucesso' => true]);