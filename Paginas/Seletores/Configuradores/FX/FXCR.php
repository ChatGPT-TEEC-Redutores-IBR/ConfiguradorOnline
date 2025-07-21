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
$fxet = isset($_GET['FXET']) ? $_GET['FXET'] : '';
$mocr = isset($_GET['MOCR']) ? $_GET['MOCR'] : '';

$sql = "SELECT 
    HYCR,
    CASE 
        WHEN FXCR = 'H' THEN 'HORÁRIO'
        WHEN FXCR = 'AH' THEN 'ANTI-HORÁRIO'
        ELSE FXCR
    END AS DESCRICAO
FROM _USR_CONF_FXCR
WHERE FXLN = ?
AND FXBR = ?
AND FXET = ?
AND MOCR = ? ";

$query = $pdo->prepare($sql);
$query->execute([$fxln, $fxbr, $fxet, $mocr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["FXCR"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);
    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>