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
$inpt = isset($_GET['INPT']) ? $_GET['INPT'] : '';
$infe = isset($_GET['INFE']) ? $_GET['INFE'] : '';
$inpc = isset($_GET['INPC']) ? $_GET['INPC'] : '';

$sql = "SELECT 
    CASE 
    WHEN INAP = INAP THEN CONCAT(INAP, ' - ', INNAP)
    ELSE INAP
    END AS DESCRICAO,
    INAP
FROM (
    SELECT DISTINCT INAP, INNAP
    FROM _USR_CONF_INBR
    WHERE INLN = ?
    AND INTP = ?
    AND INTT = ?
    AND INPT = ?
    AND INFE = ?
    AND INPC = ?
) AS Subquery
ORDER BY INAP";

$query = $pdo->prepare($sql);
$query->execute([$inln, $intp, $intt, $inpt, $infe, $inpc]);


while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["INAP"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>