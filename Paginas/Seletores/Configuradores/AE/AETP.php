<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$aeln = isset($_GET['AELN']) ? $_GET['AELN'] : '';
$aebr = isset($_GET['AEBR']) ? $_GET['AEBR'] : '';

$sql = "SELECT 
    CASE 
        WHEN AETP = 'S' THEN 'TIPO SIMPLES'
        WHEN AETP = 'D' THEN 'TIPO DUPLO'
        WHEN AETP NOT IN ('S', 'D') THEN CONCAT('TIPO ', AETP)
        ELSE AETP
        END AS DESCRIÇÃO,
    AETP
FROM (
SELECT DISTINCT AETP
    FROM _USR_CONF_AELN
    WHERE AELN = ?
    AND AEBR = ?) AS Subquery;";

$stmt = $pdo->prepare($sql);
$stmt->execute([$aeln, $aebr]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["AETP"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>