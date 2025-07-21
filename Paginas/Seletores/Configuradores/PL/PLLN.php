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
        WHEN PLLN = '3.PB' THEN 'REDUTOR PLANETÁRIO IBR PB'
        WHEN PLLN = '3.PBL' THEN 'REDUTOR PLANETÁRIO IBR PBL'
        WHEN PLLN = '3.SA' THEN 'REDUTOR PLANETÁRIO IBR SA'
        WHEN PLLN = '3.SB' THEN 'REDUTOR PLANETÁRIO IBR SB'
        WHEN PLLN = '3.SBL' THEN 'REDUTOR PLANETÁRIO IBR SBL'
        WHEN PLLN = '3.SD' THEN 'REDUTOR PLANETÁRIO IBR SD'
        ELSE PLLN
    END AS DESCRICAO,
    PLLN
FROM 
    (SELECT DISTINCT PLLN
    FROM _USR_CONF_PLBR) AS subquery
ORDER BY PLLN;";

$stmt = $pdo->prepare($sql);
$stmt->execute([$sql]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLLN"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>