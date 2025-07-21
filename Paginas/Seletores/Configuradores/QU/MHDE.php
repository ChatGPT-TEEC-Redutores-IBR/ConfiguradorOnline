<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$quln = isset($_GET['QULN']) ? $_GET['QULN'] : '';
$qubr = isset($_GET['QUBR']) ? $_GET['QUBR'] : '';
$qucm = isset($_GET['QUCM']) ? $_GET['QUCM'] : '';

$sql = "SELECT MHDE
FROM (SELECT MHDE, 1 as ORDEM
FROM _USR_CONF_MHAM
WHERE MHLN = ?
AND MHBR = ?
AND MHCM = ?) AS TEMPORARIA
ORDER BY ORDEM, TRY_CAST(MHDE AS DECIMAL(10, 2));";

$query = $pdo->prepare($sql);

$query->execute([$quln, $qubr, $qucm]);

echo '<option value="" selected hidden></option>';

$temProduto = false;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MHDE"]);
    $descricao = htmlspecialchars($row["MHDE"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
    $temProduto = true;
}

$pdo = null;
?>
