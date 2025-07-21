<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$hyln = isset($_GET['HYLN']) ? $_GET['HYLN'] : '';
$hybr = isset($_GET['HYBR']) ? $_GET['HYBR'] : '';

$sql = "
SELECT 
    CASE
        WHEN HYAF = 'N' THEN 'NÃO'
        WHEN HYAF = 'BT' THEN 'BRAÇO DE TORQUE'
        WHEN HYAF IN ('F1', 'F110', 'F120', 'F140', 'F160', 'F200', 'F250', 'F300', 'F350', 'F400', 'F450', 'FC', 'FL') THEN CONCAT('FLANGE TIPO ', HYAF)
        WHEN HYAF = 'H1' OR HYAF = 'PE' THEN 'PÉS'
        ELSE HYAF
    END AS DESCRICAO,
    HYAF
FROM ( 
    SELECT DISTINCT HYAF
    FROM _USR_CONF_HYAF
    WHERE HYLN = ?
    AND HYBR = ?
    
    UNION
    SELECT 'N' AS HYAF
    WHERE ? <> '211A'
) AS Subquery
ORDER BY DESCRICAO";

$query = $pdo->prepare($sql);
$query->execute([$hyln, $hybr, $hybr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["HYAF"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);
    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>