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

$sql = "SELECT 
    CASE 
        WHEN QUUN = QUUN THEN QUNUN
        ELSE QUUN
    END AS DESCRICAO,
    QUUN
FROM (
    SELECT DISTINCT QUUN, QUNUN
    FROM _USR_CONF_QUDRUN
    WHERE QULN = ?
    AND QUBR = ?
) AS Subquery;";

$query = $pdo->prepare($sql);
$query->execute([$quln, $qubr]);

$temProduto = false;

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["QUUN"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);
    if ($valor !== '' && $descricao !== '') {
        echo '<option value="' . $valor . '">' . $descricao . '</option>';
        $temProduto = true;
    }
}


$pdo = null;
?>
