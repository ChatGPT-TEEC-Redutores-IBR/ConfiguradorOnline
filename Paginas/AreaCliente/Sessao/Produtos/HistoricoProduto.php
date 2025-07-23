<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once __DIR__ . '/../../../../LogsErros/Logs.php';
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/BancoDados.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $produto = strtoupper(trim(filter_input(INPUT_POST, 'produto', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
    $link = trim(filter_input(INPUT_POST, 'link', FILTER_SANITIZE_URL) ?? '');
    if ($link && (!filter_var($link, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $link))) {
        echo '⚠️ Link invalido.';
        exit;
    }

    if (!$email || !$produto) {
        echo '⚠️ Dados Incompletos.';
        exit;
    }

    $sql = "INSERT INTO _USR_CONF_SITE_HISTORICO_PRODUTO (
                DS_EMAIL, DS_REFERENCIA, DS_LINK, DT_DATA
            ) SELECT ?, ?, ?, GETDATE()
              WHERE NOT EXISTS (
                  SELECT 1 FROM _USR_CONF_SITE_HISTORICO_PRODUTO
                   WHERE DS_EMAIL = ?
                     AND DS_REFERENCIA = ?
              )";

    $stm = $pdo->prepare($sql);
    $stm->execute([$email, $produto, $link, $email, $produto]);

    echo '✅ Histórico Salvo.';
    $pdo = null;
} catch (PDOException $e) {
    log_event('HistoricoProduto: ' . $e->getMessage());
    http_response_code(500);
    echo '⚠️ Erro ao Salvar Dados.';
}
?>