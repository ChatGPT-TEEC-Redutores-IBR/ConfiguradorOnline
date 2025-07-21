<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$vacm = isset($_GET['VACM']) ? $_GET['VACM'] : '';
$vaecp = isset($_GET['VAECP'])? $_GET['VAECP'] : '';
$vapt = isset($_GET['VAPT'])? $_GET['VAPT'] : '';
$moln = isset($_GET['MOLN'])? $_GET['MOLN'] : '';

$sql = "SELECT CASE 
        WHEN MOTP = 'M' THEN 'MONOFÁSICO'
        WHEN MOTP = 'T' THEN 'TRIFÁSICO'
        WHEN MOTP = 'F' THEN 'TRIFÁSICO COM FREIO'
        ELSE MOTP
        END AS DESCRIÇÃO,
    MOTP
FROM (SELECT DISTINCT A.MOTP AS MOTP
FROM _USR_CONF_MOBR A
JOIN _USR_CONF_MOCP B ON (A.MOLN = B.MOLN AND A.MOCM = B.MOCM)
WHERE A.MOLN = ?
AND ((? = '100-112' AND A.MOCM IN ('100', '112'))
    OR A.MOCM = ?)
AND B.MOCP LIKE REPLACE(REPLACE(?, 'B5', '5'), 'B14', '4')
AND A.MOPT = ?) AS Subquery;";

$vaecpModified = '%' . $vaecp . '%';

$stmt = $pdo->prepare($sql);
$stmt->execute([$moln, $vacm, $vacm, $vaecpModified, $vapt]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOTP"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>