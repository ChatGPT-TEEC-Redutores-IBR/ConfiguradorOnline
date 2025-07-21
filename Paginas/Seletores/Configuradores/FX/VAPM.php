<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$fxpm = isset($_GET['FXPM']) ? $_GET['FXPM'] : '';

$sql = "SELECT
    CASE 
        WHEN ? IN ('B3', 'H3') THEN 'B3'
        WHEN ? IN ('V5', 'H5') THEN 'V5'
        WHEN ? IN ('V6', 'H6') THEN 'V6'
    END AS POSICAO
";

$query = $pdo->prepare($sql);
$query->execute([$fxpm, $fxpm, $fxpm]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["POSICAO"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>