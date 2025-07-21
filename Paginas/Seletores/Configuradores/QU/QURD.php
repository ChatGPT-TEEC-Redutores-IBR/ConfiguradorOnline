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
$quvz = isset($_GET['QUVZ']) ? $_GET['QUVZ'] : '';

$sql = "SELECT QURD
FROM (
    SELECT QURD
    FROM _USR_CONF_QUBR
    WHERE QULN = ?
      AND QUBR = ?
      AND ((QUVZ IS NULL OR QUVZ = '') OR QUVZ = ?)
) AS subquery
GROUP BY QURD
ORDER BY TRY_CAST(QURD AS DECIMAL(10, 2))";

$query = $pdo->prepare($sql);
$query->execute([$quln, $qubr, $quvz]);

$temProduto = false;

echo '<option value="" disabled hidden selected></option>';

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["QURD"]);
    if ($valor !== '') {
        echo '<option value="' . $valor . '">' . $valor . '</option>';
        $temProduto = true;
    }
}

$pdo = null;
?>