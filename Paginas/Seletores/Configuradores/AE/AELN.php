<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$sql = "SELECT 
    CASE 
    WHEN AELN = '3.GR' THEN 'ACOPLAMENTO ELÁSTICO IBR GR'
        WHEN AELN = '3.GS' THEN 'ACOPLAMENTO ELÁSTICO IBR GS'
        WHEN AELN = '3.RIC' THEN 'ACOPLAMENTO ELÁSTICO IBR RIC'
        ELSE AELN
    END AS DESCRICAO,
    AELN
FROM 
    (SELECT DISTINCT AELN
    FROM _USR_CONF_AELN) AS subquery
ORDER BY AELN";

$query = $pdo->prepare($sql);
$query->execute();

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo '<option value="' . htmlspecialchars($row["AELN"]) . '">' . htmlspecialchars($row["DESCRICAO"]) . '</option>';
}

$pdo = null;
?>