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
    WHEN ACLN = '1.I' THEN 'REDUTOR ACOR IBR I - INOX'
    WHEN ACLN = '1.Z' THEN 'REDUTOR ACOR IBR Z - ALUMÍNIO'
    WHEN ACLN = '1.VFN' THEN 'REDUTOR ACOR IBR VFN - REDONDO'
    ELSE ACLN
    END AS DESCRIÇÃO,
    ACLN
    FROM _USR_CONF_ACBR
ORDER BY ACLN;";

$query = $pdo->prepare($sql);
$query->execute();

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo '<option value="' . htmlspecialchars($row["ACLN"]) . '">' . htmlspecialchars($row["DESCRIÇÃO"]) . '</option>';
}

$pdo = null;
?>