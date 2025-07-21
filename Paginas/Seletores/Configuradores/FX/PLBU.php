<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$fxln = isset($_GET['FXLN']) ? $_GET['FXLN'] : '';
$fxbr = isset($_GET['FXBR']) ? $_GET['FXBR'] : '';
$fxcm = isset($_GET['FXCM']) ? $_GET['FXCM'] : '';
$plde = isset($_GET['PLDE']) ? $_GET['PLDE'] : '';

$sql = "SELECT
    CASE
        WHEN PLBU = 'N' THEN 'NÃO'
        ELSE PLBU
    END AS DESCRICAO,
    PLBU
FROM
    (
        SELECT 
            CASE 
                WHEN EXISTS (
                    SELECT PLDE
                    FROM _USR_CONF_PLAS
                    WHERE PLLN = ?
                    AND PLBR = ?
                    AND PLCM = ?
                    AND PLDE = ?
                ) THEN 'N'
                ELSE B.PLDE
            END AS PLBU
        FROM _USR_CONF_PLAS A
        LEFT JOIN _USR_CONF_PLBU B ON A.PLDE = B.PLDE AND B.PLBU = '{PLDE}'
        WHERE A.PLLN = ?
        AND A.PLBR = ?
        AND A.PLCM = ?
    ) AS subquery
WHERE PLBU IS NOT NULL;";

$query = $pdo->prepare($sql);
$query->execute([$fxln, $fxbr, $fxcm, $plde, $fxln, $fxbr, $fxcm]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLBU"]);
    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>