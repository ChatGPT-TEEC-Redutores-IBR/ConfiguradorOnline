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

$sql = "SELECT DISTINCT
    CASE 
        WHEN MOTT = MOTT THEN MONTT
        ELSE MOTT
    END AS DESCRICAO,
    MOTT
FROM (
    SELECT MOTT, MONTT
    FROM _USR_CONF_MOBR
    WHERE MOLN = ?
    AND MOTP = ?
) AS Subquery;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$moln, $motp]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOTT"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>