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

$sql = "SELECT 
            CASE 
                WHEN QUAS = 'N' THEN 'NÃO'
                WHEN QUAS = 'ES' THEN 'EIXO DE SAÍDA SIMPLES'
                WHEN QUAS = 'ED' THEN 'EIXO DE SAÍDA DUPLO'
                WHEN QUAS = 'ES30' THEN 'EIXO DE SAÍDA SIMPLES - Ø30MM'
                ELSE QUAS
            END AS DESCRICAO,
            QUAS
        FROM ( 
            SELECT DISTINCT QUAS
            FROM _USR_CONF_QUAS
            WHERE QULN = ?
              AND QUBR = ?
              AND ((QUVZ IS NULL OR QUVZ = '') OR QUVZ = ?)
            UNION ALL
            SELECT 'N' AS QUAS
        ) AS Subquery
        GROUP BY QUAS
        ORDER BY DESCRICAO";

$stmt = $pdo->prepare($sql);
$stmt->execute([$quln, $qubr, $quvz]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["QUAS"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>