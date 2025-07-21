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

$sql = "SELECT DISTINCT ACBR
    FROM _USR_CONF_ACBR
    WHERE ACLN = ?";

$query = $pdo->prepare($sql);
$query->execute([$acln]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["ACBR"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>