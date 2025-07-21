<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$acln = isset($_GET['ACLN']) ? $_GET['ACLN'] : '';
$acbr = isset($_GET['ACBR']) ? $_GET['ACBR'] : '';

$sql = "SELECT ACRD
FROM (
 SELECT ACRD
    FROM _USR_CONF_ACBR
    WHERE ACLN = ?
    AND ACBR = ?) AS subquery
GROUP BY ACRD
ORDER BY TRY_CAST(ACRD AS DECIMAL(10, 2));";

$query = $pdo->prepare($sql);
$query->execute([$acln, $acbr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["ACRD"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>