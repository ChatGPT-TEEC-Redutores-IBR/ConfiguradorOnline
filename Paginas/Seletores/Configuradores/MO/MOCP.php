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
$mopl = isset($_GET['MOPL']) ? $_GET['MOPL'] : '';
$mocm = isset($_GET['MOCM']) ? $_GET['MOCM'] : '';

$sql = "SELECT DISTINCT B.MOCP
FROM _USR_CONF_MOBR A
JOIN _USR_CONF_MOCP B ON (A.MOLN = B.MOLN AND A.MOCM = B.MOCM)
WHERE A.MOLN = ?
AND A.MOTP = ?
AND A.MOTT = ?
AND A.MOFQ = ?
AND A.MOPT = ?
AND A.MOPL = ?
AND A.MOCM = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$moln, $motp, $mott, $mofq, $mopt, $mopl, $mocm]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOCP"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>