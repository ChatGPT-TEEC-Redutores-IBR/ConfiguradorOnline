<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';

$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$acln = isset($_GET['ACLN']) ? $_GET['ACLN'] : '';
$acbr = isset($_GET['ACBR']) ? $_GET['ACBR'] : '';

$sql = "SELECT 
    CASE
        WHEN ACAF = 'N' THEN 'NÃO'
        WHEN ACAF = 'BT' THEN 'BRAÇO DE TORQUE'
        WHEN ACAF IN ('F1', 'F110', 'F120', 'F140', 'F160', 'F200', 'F250', 'F300', 'F350', 'F400', 'F450', 'FC', 'FL') THEN CONCAT('FLANGE TIPO ', ACAF)
        WHEN ACAF = 'H1' OR ACAF = 'PE' THEN 'PÉS'
        ELSE ACAF
    END AS DESCRIÇÃO,
    ACAF
FROM ( 
    SELECT DISTINCT ACAF
    FROM _USR_CONF_ACAF
    WHERE ACLN = ?
    AND ACBR = ?
      
    UNION
    SELECT 'N' AS ACAF
) AS Subquery
ORDER BY DESCRIÇÃO;
";

$query = $pdo->prepare($sql);
$query->execute([$acln, $acbr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["ACAF"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>