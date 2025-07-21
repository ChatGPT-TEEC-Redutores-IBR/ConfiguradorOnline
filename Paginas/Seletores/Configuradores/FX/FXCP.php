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
$fxrd = isset($_GET['FXRD']) ? $_GET['FXRD'] : '';
$fxet = isset($_GET['FXET']) ? $_GET['FXET'] : '';
$fxcm = isset($_GET['FXCM']) ? $_GET['FXCM'] : '';
$fxbu = isset($_GET['FXBU']) ? $_GET['FXBU'] : '';

$sql = "SELECT DISTINCT FXCP
FROM _USR_CONF_FXBR
WHERE FXLN = ?
AND FXBR = ?
AND FXRD = ?
AND FXET = ?
AND FXCM = ?
AND FXBU = ?
ORDER BY FXCP";

$query = $pdo->prepare($sql);
$query->execute([$fxln, $fxbr, $fxrd, $fxet, $fxcm, $fxbu]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["FXCP"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>