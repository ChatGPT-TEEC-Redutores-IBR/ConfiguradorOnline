<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$plln = isset($_GET['PLLN']) ? $_GET['PLLN'] : '';
$plbr = isset($_GET['PLBR']) ? $_GET['PLBR'] : '';
$plet = isset($_GET['PLET']) ? $_GET['PLET'] : '';

$sql = "SELECT CASE 
        WHEN PLTP <> 'PADRÃO' THEN CONCAT('TIPO ', PLTP)
        ELSE PLTP
        END AS DESCRIÇÃO,
    PLTP
FROM ( 
    SELECT DISTINCT PLTP
    FROM _USR_CONF_PLBR
    WHERE PLLN = ?
    AND PLBR = ?
    AND PLET = ?) AS Subquery;";

$stmt = $pdo->prepare($sql);
$stmt->execute([$plln, $plbr, $plet]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLTP"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
    
}

$pdo = null;
?>