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

$sql = "SELECT DISTINCT CASE 
        WHEN MOTP = 'M' THEN 'MONOFÁSICO'
        WHEN MOTP = 'T' THEN 'TRIFÁSICO'
        WHEN MOTP = 'F' THEN 'TRIFÁSICO COM FREIO'
        ELSE MOTP
        END AS DESCRIÇÃO,
    MOTP
FROM (SELECT MOTP AS MOTP
FROM _USR_CONF_MOBR
WHERE MOLN = ?
) AS Subquery;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$moln]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOTP"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>