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
$quvz = isset($_GET['QUVZ']) ? $_GET['QUVZ'] : '';
$qurd = isset($_GET['QURD']) ? $_GET['QURD'] : '';

$sql = "SELECT 
    CASE
        WHEN Subquery.QUCP LIKE '%EE%' THEN 
            CASE
                WHEN Subquery.QUCP LIKE '%TS%' THEN 
                    CONCAT(REPLACE(REPLACE(Subquery.QUCP, 'EE', 'EIXO DE ENTRADA DE Ø'), 'TS', ''), 'MM - COM SOLDA')
                ELSE 
                    CONCAT(REPLACE(Subquery.QUCP, 'EE', 'EIXO DE ENTRADA DE Ø'), 'MM')
            END
        ELSE Subquery.QUCP
    END AS DESCRICAO,
    Subquery.QUCP
FROM (
    SELECT DISTINCT A.QUCP
    FROM _USR_CONF_QUCP AS A
    LEFT JOIN _USR_CONF_QUBR AS B 
        ON A.QULN = B.QULN 
        AND A.QUBR = B.QUBR 
        AND A.QUCM = B.QUCM
    WHERE A.QULN = ?
      AND A.QUBR = ?
      AND A.QUCM = ?
      AND ((B.QUVZ IS NULL OR B.QUVZ = '') OR B.QUVZ = ?)
      AND B.QURD = ?
      AND (B.QUBU IS NULL OR B.QUBU = 'N' OR A.QUCP NOT LIKE '%EE%')
) AS Subquery
ORDER BY DESCRICAO";

$query = $pdo->prepare($sql);
$query->execute([$quln, $qubr, $qucm, $quvz, $qurd]);

$temProduto = false;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["QUCP"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    if ($valor !== '' && $descricao !== '') {
        echo '<option value="' . $valor . '">' . $descricao . '</option>';
        $temProduto = true;
    }
}

if (!$temProduto) {
    echo '<option value="">Nenhuma Forma Construtiva Encontrada</option>';
}

$pdo = null;
?>