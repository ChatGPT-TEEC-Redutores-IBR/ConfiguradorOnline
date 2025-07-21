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

$sql = "SELECT 
    CASE 
    WHEN INPT = INPT THEN CONCAT(INPT, ' - ', INNPT)
    ELSE INPT
    END AS DESCRICAO,
    INPT
FROM (
    SELECT DISTINCT INPT, INNPT
    FROM _USR_CONF_INBR
    WHERE INLN = ?
    AND INTP = ?
    AND INTT = ?
) AS Subquery
ORDER BY INPT";

$query = $pdo->prepare($sql);
$query->execute([$inln, $intp, $intt]);


while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["INPT"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>