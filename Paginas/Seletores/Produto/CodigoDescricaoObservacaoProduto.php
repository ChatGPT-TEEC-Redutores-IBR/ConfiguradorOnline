<?php
header('Content-Type: application/json; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $codigo = trim(filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $codigo = strtoupper($codigo);

    if (!$codigo) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT TOP 1 
            CD_PRODUTO, 
            DS_PRODUTO, 
            DS_OBSNOTA,
            CD_EMPRESA
        FROM MMPR_PRODUTO 
        WHERE DS_REFERENCIA = ?
          AND ID_STATUS = 0
          AND CD_PRODCONFIG IS NOT NULL";

    $query = $pdo->prepare($sql);
    $query->execute([$codigo]);

    $row = $query->fetch(PDO::FETCH_ASSOC);

    echo json_encode($row ?: []);

} catch (PDOException $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}

$pdo = null;
?>