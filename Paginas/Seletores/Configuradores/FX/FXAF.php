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

$sql = "SELECT 
    CASE
        WHEN FXAF = 'N' THEN 'NÃO'
        WHEN FXAF = 'BT' THEN 'BRAÇO DE TORQUE'
        WHEN FXAF IN ('F1', 'F110', 'F120', 'F140', 'F160', 'F200', 'F250', 'F300', 'F350', 'F400', 'F450', 'FC', 'FL') THEN CONCAT('FLANGE TIPO ', FXAF)
        WHEN FXAF = 'H1' OR FXAF = 'PE' THEN 'PÉS'
        ELSE FXAF
    END AS DESCRIÇÃO,
    FXAF
FROM ( 
    SELECT DISTINCT FXAF
    FROM _USR_CONF_FXAF
    WHERE FXLN = ?
    AND FXBR = ?
    UNION
    SELECT 'N' AS FXAF
) AS Subquery
ORDER BY DESCRIÇÃO;";

$query = $pdo->prepare($sql);
$query->execute([$fxln, $fxbr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["FXAF"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>