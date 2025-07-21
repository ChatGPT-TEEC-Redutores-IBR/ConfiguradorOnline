<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$fxln = isset($_GET['FXLN']) ? $_GET['FXLN'] : '';
$fxbr = isset($_GET['FXBR']) ? $_GET['FXBR'] : '';

$sql = "SELECT FXRD
    FROM _USR_CONF_FXBR
    WHERE FXLN = ?
    AND FXBR = ?
    GROUP BY FXRD
    ORDER BY TRY_CAST(FXRD AS DECIMAL(10, 2));";

$query = $pdo->prepare($sql);
$query->execute([$fxln, $fxbr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["FXRD"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>