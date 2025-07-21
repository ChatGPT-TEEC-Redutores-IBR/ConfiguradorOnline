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
                WHEN QUAF = 'N' THEN 'NÃO'
                WHEN QUAF = 'BT' THEN 'BRAÇO DE TORQUE'
                WHEN QUAF IN ('F1', 'F110', 'F120', 'F140', 'F160', 'F200', 'F250', 'F300', 'F350', 'F400', 'F450', 'FC', 'FL', 'FB') THEN CONCAT('FLANGE TIPO ', QUAF)
                ELSE QUAF
            END AS DESCRICAO,
            QUAF
        FROM (
            SELECT QUAF
            FROM _USR_CONF_QUAF
            WHERE QULN = ?
              AND QUBR = ?
            UNION ALL
            SELECT 'N' AS QUAF
        ) AS Subquery
        GROUP BY QUAF
        ORDER BY DESCRICAO";

$query = $pdo->prepare($sql);
$query->execute([$quln, $qubr]);

$temProduto = false;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["QUAF"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
    $temProduto = true;
}

$pdo = null;
?>