<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$aeln = isset($_GET['AELN']) ? $_GET['AELN'] : '';

$sql = "SELECT DISTINCT AEBR
    FROM _USR_CONF_AELN
    WHERE AELN = ?";

$query = $pdo->prepare($sql);
$query->execute([$aeln]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["AEBR"]);
    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>