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
$accm = isset($_GET['ACCM']) ? $_GET['ACCM'] : '';

$sql = "SELECT DISTINCT ACCP
FROM _USR_CONF_ACCP
WHERE ACLN = ?
AND ACBR = ?
AND ACCM = ?
ORDER BY ACCP";

$query = $pdo->prepare($sql);
$query->execute([$acln, $acbr, $accm]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["ACCP"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>