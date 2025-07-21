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
$vacm = isset($_GET['VACM']) ? $_GET['VACM'] : '';
$vaecp = isset($_GET['VAECP']) ? $_GET['VAECP'] : '';
$vapt = isset($_GET['VAPT']) ? $_GET['VAPT'] : '';

$sql = "SELECT CASE 
        WHEN MOTT = MOTT THEN MONTT
        ELSE MOTT
    END AS DESCRICAO,
    MOTT
FROM ( 
    SELECT DISTINCT A.MOTT, A.MONTT
FROM _USR_CONF_MOBR A
JOIN _USR_CONF_MOCP B ON A.MOCM = B.MOCM
WHERE A.MOLN = ?
AND A.MOTP = ?
AND ((? = '100-112' AND A.MOCM IN ('100', '112'))
    OR A.MOCM = ?)
AND B.MOCP LIKE REPLACE(REPLACE(?, 'B5', '5'), 'B14', '4')
AND A.MOPT = ?) AS Subquery;
";

$vaecpModified = '%' . $vaecp . '%';

$stmt = $pdo->prepare($sql);
$stmt->execute([$moln, $motp, $vacm, $vacm, $vaecpModified, $vapt]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOTT"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>