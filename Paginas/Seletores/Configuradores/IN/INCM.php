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
$inap = isset($_GET['INAP']) ? $_GET['INAP'] : '';

$sql = "SELECT 
    CASE 
    WHEN INCM = INCM THEN CONCAT(INCM, ' - ', INNCM)
    ELSE INCM
    END AS DESCRICAO,
    INCM
FROM (
    SELECT DISTINCT INCM, INNCM
    FROM _USR_CONF_INBR
    WHERE INLN = ?
    AND INTP = ?
    AND INTT = ?
    AND INPT = ?
    AND INFE = ?
    AND INPC = ?
    AND INAP = ?
) AS Subquery
ORDER BY INCM";

$query = $pdo->prepare($sql);
$query->execute([$inln, $intp, $intt, $inpt, $infe, $inpc, $inap]);


while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["INCM"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>