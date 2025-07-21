<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$hyln = isset($_GET['HYLN']) ? $_GET['HYLN'] : '';
$hybr = isset($_GET['HYBR']) ? $_GET['HYBR'] : '';
$hyrd = isset($_GET['HYRD']) ? $_GET['HYRD'] : '';

$sql = "SELECT 
    CASE
        WHEN HYCM LIKE '%EE%' THEN CONCAT(REPLACE(HYCM, 'EE', 'EIXO DE ENTRADA DE Ø'), 'MM')
        ELSE HYCM
    END AS DESCRIÇÃO,
    HYCM
FROM (
  SELECT A.HYCM,
  CASE 
           WHEN CHARINDEX('-', HYCM) > 0 THEN 
             CASE 
               WHEN ISNUMERIC(SUBSTRING(HYCM, 1, CHARINDEX('-', HYCM) - 1)) = 1 THEN CAST(SUBSTRING(HYCM, 1, CHARINDEX('-', HYCM) - 1) AS INTEGER)
               ELSE 999999
             END
           ELSE 
             CASE 
               WHEN ISNUMERIC(HYCM) = 1 THEN CAST(HYCM AS INTEGER)
               ELSE 999999
             END
         END AS SortValue
FROM _USR_CONF_HYCP A
WHERE A.HYLN = ?
AND A.HYBR = ?
AND (

    EXISTS (
        SELECT 1
        FROM _USR_CONF_HYKE B
        WHERE B.HYLN = ?
        AND B.HYBR = ?
        AND B.HYRD = ?)
    OR (
        A.HYCM NOT IN (
            SELECT C.HYCM
            FROM _USR_CONF_HYKE C
            WHERE C.HYLN = ?
            AND C.HYBR = ?
            AND C.HYRD <> '')))) AS Subquery
GROUP BY HYCM
ORDER BY MAX(SortValue);";

$query = $pdo->prepare($sql);
$query->execute([$hyln, $hybr, $hyln, $hybr, $hyrd, $hyln, $hybr]);

$temProduto = false;

echo '<option value="" disabled hidden selected></option>';

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo '<option value="' . htmlspecialchars($row["HYCM"]) . '">' . htmlspecialchars($row["DESCRIÇÃO"]) . '</option>';
}

$pdo = null;
?>