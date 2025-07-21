<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$sql = "SELECT DISTINCT
    CASE 
    WHEN QULN = '1.QDR' THEN 'REDUTOR IBR QDR - QUADRADO DUPLA REDUÇÃO'
    WHEN QULN = '1.QP' THEN 'REDUTOR IBR QP - QUADRADO DUPLA REDUÇÃO MONOESTÁGIO'
    ELSE QULN
    END AS DESCRIÇÃO,
    QULN
    FROM _USR_CONF_QUDRBR
    
ORDER BY QULN";

$query = $pdo->prepare($sql);
$query->execute();

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo '<option value="' . htmlspecialchars($row["QULN"]) . '">' . htmlspecialchars($row["DESCRIÇÃO"]) . '</option>';
}

$pdo = null;
?>