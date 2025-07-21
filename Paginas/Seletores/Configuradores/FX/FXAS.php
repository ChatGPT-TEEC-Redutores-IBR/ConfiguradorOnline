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
        WHEN FXAS = 'N' THEN 'NÃO'
        WHEN FXAS LIKE 'S%' THEN CONCAT('BASE DE FIXAÇÃO TIPO ', FXAS)
        WHEN FXAS = 'ED' THEN 'EIXO DE SAÍDA DUPLO'
        WHEN FXAS = 'ES' THEN 'EIXO DE SAÍDA SIMPLES'
        ELSE FXAS      
    END AS DESCRIÇÃO,
    FXAS
FROM ( 
    SELECT DISTINCT FXAS
    FROM _USR_CONF_FXAS
    WHERE FXLN = ?
    AND FXBR = ?
    UNION
    SELECT 'SX' AS FXAS
    WHERE ? = '1.FR'
    UNION
    SELECT 'N' AS FXAS
    WHERE ? <> '1.FR'
) Subquery";

$query = $pdo->prepare($sql);
$query->execute([$fxln, $fxbr, $fxln, $fxln]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["FXAS"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>