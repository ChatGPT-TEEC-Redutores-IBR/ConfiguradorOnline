<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$inln = isset($_GET['INLN']) ? $_GET['INLN'] : '';
$intp = isset($_GET['INTP']) ? $_GET['INTP'] : '';
$intt = isset($_GET['INTT']) ? $_GET['INTT'] : '';
$infe = isset($_GET['INFE']) ? $_GET['INFE'] : '';
$inco = isset($_GET['INCO']) ? $_GET['INCO'] : '';
$inopcs = isset($_GET['INOPCS']) ? $_GET['INOPCS'] : '';

$sql = "SELECT DISTINCT CASE 
        WHEN MOLN = '2.I' THEN 'MOTOR IBR STANDARD'
        WHEN MOLN = '3.I' THEN 'MOTOR IBR ALTO RENDIMENTO'
        WHEN MOLN = '3.W' THEN 'MOTOR WEG ALTO RENDIMENTO'
        WHEN MOLN = '3.APM' THEN 'MOTOR IBR ANTICORROSIVO APM'
        WHEN MOLN = '3.SPM' THEN 'MOTOR IBR ANTICORROSIVO SPM'
        ELSE MOLN
        END AS DESCRIÇÃO,
    MOLN
    FROM _USR_CONF_INOPCS
    WHERE INLN = ?
AND INTP = ?
AND INTT = ?
AND INFE = ?
AND INCO = ?
AND INOPCS = ?";

$query = $pdo->prepare($sql);
$query->execute([$inln, $intp, $intt, $infe, $inco, $inopcs]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOLN"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>