<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
require_once __DIR__ . '/Logs.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$msg = isset($data['msg']) ? substr(trim($data['msg']), 0, 300) : 'N/A';
$url = isset($data['url']) ? substr(trim($data['url']), 0, 300) : ''; 
$linha = isset($data['linha']) ? intval($data['linha']) : 0;
$coluna = isset($data['coluna']) ? intval($data['coluna']) : 0;
$stack = isset($data['stack']) ? substr(trim($data['stack']), 0, 1000) : '';

log_event("JS Error: $msg File:$url Line:$linha:$coluna Stack:$stack");

echo json_encode(['sucesso' => true]);