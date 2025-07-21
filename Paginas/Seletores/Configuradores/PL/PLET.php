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
$plbr = isset($_GET['PLBR']) ? $_GET['PLBR'] : '';

$sql = "SELECT DISTINCT PLET
FROM _USR_CONF_PLBR
WHERE PLLN = ?
AND PLBR = ?
ORDER BY PLET ASC;";

$stmt = $pdo->prepare($sql);
$stmt->execute([$plln, $plbr]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLET"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';

}

$pdo = null;
?>