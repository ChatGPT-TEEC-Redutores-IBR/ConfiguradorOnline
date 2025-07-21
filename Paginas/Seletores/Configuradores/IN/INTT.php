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

$sql = "SELECT 
    CASE 
    WHEN INTT = INTT THEN CONCAT(INTT, ' - ', INNTT)
    ELSE INTT
    END AS DESCRICAO,
    INTT
FROM (
    SELECT DISTINCT INTT, INNTT
    FROM _USR_CONF_INBR
    WHERE INLN = ?
    AND INTP = ?
) AS Subquery
ORDER BY INTT";

$query = $pdo->prepare($sql);
$query->execute([$inln, $intp]);


while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["INTT"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>