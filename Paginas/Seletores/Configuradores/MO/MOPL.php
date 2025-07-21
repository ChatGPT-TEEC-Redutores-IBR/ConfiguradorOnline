<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$moln = isset($_GET['MOLN']) ? $_GET['MOLN'] : '';
$motp = isset($_GET['MOTP']) ? $_GET['MOTP'] : '';
$mott = isset($_GET['MOTT']) ? $_GET['MOTT'] : '';
$mofq = isset($_GET['MOFQ']) ? $_GET['MOFQ'] : '';
$mopt = isset($_GET['MOPT']) ? $_GET['MOPT'] : '';

$sql = "SELECT DISTINCT MOPL
FROM _USR_CONF_MOBR
WHERE MOLN = ?
AND MOTP = ?
AND MOTT = ?
AND MOFQ = ?
AND MOPT = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$moln, $motp, $mott, $mofq, $mopt]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOPL"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>