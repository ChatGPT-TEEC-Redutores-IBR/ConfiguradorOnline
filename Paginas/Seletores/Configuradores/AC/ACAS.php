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
        WHEN ACAS = 'N' THEN 'NÃO'
        WHEN ACAS IN ('S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7') THEN CONCAT('BASE DE FIXAÇÃO TIPO ', ACAS)
        WHEN ACAS = 'ED' THEN 'EIXO DE SAÍDA DUPLO'
        WHEN ACAS = 'ES' THEN 'EIXO DE SAÍDA SIMPLES'
        ELSE ACAS      
    END AS DESCRIÇÃO,
    ACAS
FROM ( 
    SELECT DISTINCT ACAS
    FROM _USR_CONF_ACAS
    WHERE ACLN = ?
    AND ACBR = ?
    
    UNION
    SELECT 'N' AS ACAS
) Subquery";

$query = $pdo->prepare($sql);
$query->execute([$acln, $acbr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["ACAS"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>