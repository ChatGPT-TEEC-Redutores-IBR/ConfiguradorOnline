<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
header('Content-Type: application/json; charset=UTF-8');
$cnpj = preg_replace('/\D/', '', $_GET['cnpj'] ?? '');
if (strlen($cnpj) !== 14) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'CNPJ inválido.']);
    exit;
}
$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'ConfiguradorIBR'
    ]
]);
$resp = @file_get_contents('https://www.receitaws.com.br/v1/cnpj/' . $cnpj, false, $ctx);
if ($resp === false) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro na consulta.']);
    exit;
}
$dados = json_decode($resp, true);
$nome = $dados['nome'] ?? ($dados['nome_fantasia'] ?? ($dados['fantasia'] ?? ''));
if ($nome) {
    echo json_encode(['sucesso' => true, 'nome' => strtoupper($nome)]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não encontrado.']);
}
?>