<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$sql = "SELECT CASE 
        WHEN MOLN = '2.I' THEN 'MOTOR IBR MS/ML'
        WHEN MOLN = '3.I' THEN 'MOTOR IBR T3A/T3C'
        WHEN MOLN = '3.W' THEN 'MOTOR WEG ALTO RENDIMENTO'
        WHEN MOLN = '3.APM' THEN 'MOTOR IBR ANTICORROSIVO APM'
        WHEN MOLN = '3.SPM' THEN 'MOTOR IBR ANTICORROSIVO SPM'
        WHEN MOLN = '2.WS' THEN 'MOTOR WEG ESPECIAL'
        ELSE MOLN
        END AS DESCRIÇÃO,
    MOLN
FROM ( 
    SELECT DISTINCT MOLN
    FROM _USR_CONF_MOBR) AS Subquery;";

$query = $pdo->prepare($sql);
$query->execute();

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo '<option value="' . htmlspecialchars($row["MOLN"]) . '">' . htmlspecialchars($row["DESCRIÇÃO"]) . '</option>';
}

$pdo = null;
?>