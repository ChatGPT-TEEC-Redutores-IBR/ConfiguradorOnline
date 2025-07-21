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

$sql = "SELECT QUCM2
FROM _USR_CONF_QUDRCM2
WHERE QULN = ?
AND QUBR = ?";

$query = $pdo->prepare($sql);
$query->execute([$quln, $qubr]);

$temProduto = false;

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["QUCM2"]);
    if ($valor !== '') {
        echo '<option value="' . $valor . '">' . $valor . '</option>';
        $temProduto = true;
    }
}


$pdo = null;
?>
