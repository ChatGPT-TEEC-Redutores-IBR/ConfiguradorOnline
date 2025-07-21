<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$quln = isset($_GET['QULN']) ? $_GET['QULN'] : '';

$sql = "SELECT QUBR
FROM _USR_CONF_QUDRBR
    WHERE QULN = ?
    GROUP BY QUBR
ORDER BY TRY_CAST(QUBR AS DECIMAL(10, 2));";

$query = $pdo->prepare($sql);
$query->execute([$quln]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["QUBR"]);
    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>