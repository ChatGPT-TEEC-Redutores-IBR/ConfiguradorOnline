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
$accm = isset($_GET['ACCM']) ? $_GET['ACCM'] : '';
$accp = isset($_GET['ACCP']) ? $_GET['ACCP'] : '';

$sql = "SELECT 
    CASE
    WHEN MOFQ IS NOT NULL THEN CONCAT(MOFQ, 'HZ')
    END AS DESCRIÇÃO,
    MOFQ
FROM (SELECT DISTINCT A.MOFQ AS MOFQ
FROM _USR_CONF_MOBR A
JOIN _USR_CONF_MOCP B ON (A.MOLN = B.MOLN AND A.MOCM = B.MOCM)
WHERE A.MOLN = ?
AND A.MOTP = ?
AND A.MOTT = ?
AND ((? = '100-112' AND A.MOCM IN ('100', '112'))
    OR A.MOCM = ?)
    AND B.MOCP LIKE REPLACE(REPLACE(?, 'B5', '5'), 'B14', '4')) AS Subquery";

$query = $pdo->prepare($sql);

$accpModified = '%' . $accp . '%';

$query->execute([$moln, $motp, $mott, $accm, $accm, $accpModified]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOFQ"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>