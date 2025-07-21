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

$sql = "SELECT DISTINCT
    CASE 
    WHEN INTP <> '' THEN CONCAT('TIPO ', INTP)
    ELSE INTP
        END AS DESCRIÇÃO,
    INTP
    FROM _USR_CONF_INBR
    WHERE INLN = ?
    ORDER BY INTP";

$query = $pdo->prepare($sql);
$query->execute([$inln]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["INTP"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>