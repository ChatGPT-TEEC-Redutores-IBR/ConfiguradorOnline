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
$qurd = isset($_GET['QURD']) ? $_GET['QURD'] : '';

$sql = "
SELECT QUCM
FROM (
    SELECT QUCM,
           CASE 
             WHEN CHARINDEX('-', QUCM) > 0 THEN 
               CASE 
                 WHEN ISNUMERIC(SUBSTRING(QUCM, 1, CHARINDEX('-', QUCM) - 1)) = 1 
                   THEN CAST(SUBSTRING(QUCM, 1, CHARINDEX('-', QUCM) - 1) AS INTEGER)
                 ELSE 999999
               END
             ELSE 
               CASE 
                 WHEN ISNUMERIC(QUCM) = 1 
                   THEN CAST(QUCM AS INTEGER)
                 ELSE 999999
               END
           END AS SortValue
    FROM _USR_CONF_QUBR
    WHERE QULN = ?
      AND QUBR = ?
      AND ((QUVZ IS NULL OR QUVZ = '') OR QUVZ = ?)
      AND QURD = ?
) AS Subquery
GROUP BY QUCM
ORDER BY MAX(SortValue)";

$query = $pdo->prepare($sql);
$query->execute([$quln, $qubr, $quvz, $qurd]);

$temProduto = false;

echo '<option value="" disabled hidden selected></option>';

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["QUCM"]);

    if ($valor !== '') {
        echo '<option value="' . $valor . '">' . $valor . '</option>';
        $temProduto = true;
    }
}

if (!$temProduto) {
    echo '<option value="">Nenhuma Carcaça Encontrada</option>';
}

$pdo = null;
?>