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
$incm = isset($_GET['INCM']) ? $_GET['INCM'] : '';

$sql = "SELECT 
    CASE 
    WHEN INCP = INCP THEN CONCAT(INCP, ' - ', INNCP)
    ELSE INCP
    END AS DESCRICAO,
    INCP
FROM (
    SELECT DISTINCT INCP, INNCP
    FROM _USR_CONF_INBR
    WHERE INLN = ?
    AND INTP = ?
    AND INTT = ?
    AND INPT = ?
    AND INFE = ?
    AND INPC = ?
    AND INAP = ?
    AND INCM = ?
) AS Subquery
ORDER BY INCP";

$query = $pdo->prepare($sql);
$query->execute([$inln, $intp, $intt, $inpt, $infe, $inpc, $inap, $incm]);


while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["INCP"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>