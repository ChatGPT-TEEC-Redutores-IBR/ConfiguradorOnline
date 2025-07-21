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
$vacm = isset($_GET['VACM']) ? $_GET['VACM'] : '';
$vacp = isset($_GET['VACP']) ? $_GET['VACP'] : '';

$sql = "SELECT MOPT
FROM (
    SELECT DISTINCT A.MOPT
    FROM _USR_CONF_MOBR A
    JOIN _USR_CONF_MOCP B ON (A.MOLN = B.MOLN AND A.MOCM = B.MOCM)
    WHERE A.MOLN = ?
    AND A.MOTP = ?
    AND A.MOTT = ?
    AND A.MOFQ = ?
    AND ((? = '100-112' AND A.MOCM IN ('100', '112'))
    OR A.MOCM = ?)
    AND B.MOCP LIKE REPLACE(REPLACE(?, 'B5', '5'), 'B14', '4')) AS TEMPORARIA
ORDER BY TRY_CAST(MOPT AS DECIMAL(10, 2));
";

$vacpModified = '%' . $vacp . '%';

$stmt = $pdo->prepare($sql);
$stmt->execute([$moln, $motp, $mott, $mofq, $vacm, $vacm, $vacpModified]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOPT"]);

    echo '<option value="' . $valor . '">' .  $valor . '</option>';
}

$pdo = null;
?>