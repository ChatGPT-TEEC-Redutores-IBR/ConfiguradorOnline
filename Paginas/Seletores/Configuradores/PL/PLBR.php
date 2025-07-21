<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$plln = isset($_GET['PLLN']) ? $_GET['PLLN'] : '';

$sql = "SELECT PLBR
FROM (
    SELECT DISTINCT PLBR
    FROM _USR_CONF_PLBR
    WHERE PLLN = ?
) AS SubQuery
ORDER BY CONVERT(decimal(10,2), PLBR);";

$stmt = $pdo->prepare($sql);
$stmt->execute([$plln]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLBR"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';

}

$pdo = null;
?>