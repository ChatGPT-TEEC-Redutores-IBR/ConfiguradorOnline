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
$mocr = isset($_GET['MOCR']) ? $_GET['MOCR'] : '';

$sql = "SELECT 
    HYCR,
    CASE 
        WHEN HYCR = 'H' THEN 'HORÁRIO'
        WHEN HYCR = 'AH' THEN 'ANTI-HORÁRIO'
        ELSE HYCR
    END AS DESCRICAO
FROM _USR_CONF_HYCR
WHERE HYLN = ?
AND HYBR = ?
AND MOCR = ?";

$query = $pdo->prepare($sql);
$query->execute([$hyln, $hybr, $mocr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["HYCR"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);
    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>