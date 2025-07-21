<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$acln = isset($_GET['ACLN']) ? $_GET['ACLN'] : '';
$acbr = isset($_GET['ACBR']) ? $_GET['ACBR'] : '';

$sql = "SELECT 
    CASE
        WHEN ACCM LIKE '%EE%' THEN CONCAT(REPLACE(ACCM, 'EE', 'EIXO DE ENTRADA DE Ø'), 'MM')
        ELSE ACCM
    END AS DESCRIÇÃO,
    ACCM
FROM (
  SELECT ACCM,
         CASE 
           WHEN CHARINDEX('-', ACCM) > 0 THEN 
             CASE 
               WHEN ISNUMERIC(SUBSTRING(ACCM, 1, CHARINDEX('-', ACCM) - 1)) = 1 THEN CAST(SUBSTRING(ACCM, 1, CHARINDEX('-', ACCM) - 1) AS INTEGER)
               ELSE 999999
             END
           ELSE 
             CASE 
               WHEN ISNUMERIC(ACCM) = 1 THEN CAST(ACCM AS INTEGER)
               ELSE 999999
             END
         END AS SortValue
  FROM _USR_CONF_ACCP
  WHERE ACLN = ? AND ACBR = ?
  ) AS Subquery
GROUP BY ACCM
ORDER BY MAX(SortValue);";

$query = $pdo->prepare($sql);
$query->execute([$acln, $acbr]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["ACCM"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>