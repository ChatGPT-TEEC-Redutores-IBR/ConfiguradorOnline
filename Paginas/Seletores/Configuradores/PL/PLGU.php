<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$plln = isset($_GET['PLLN']) ? $_GET['PLLN'] : '';
$plbr = isset($_GET['PLBR']) ? $_GET['PLBR'] : '';
$plet = isset($_GET['PLET']) ? $_GET['PLET'] : '';
$pltp = isset($_GET['PLTP']) ? $_GET['PLTP'] : '';

if ($pltp != 'A') {
    $pltp = 'PADRÃO';
}

$sql = "
SELECT PLGU
FROM (
    SELECT DISTINCT
        PLGU, 
        1 AS ORDEM,
        CASE 
            WHEN ISNUMERIC(LEFT(PLGU, PATINDEX('%[^0-9.]%', PLGU + 'a') - 1)) = 1
            THEN CAST(LEFT(PLGU, PATINDEX('%[^0-9.]%', PLGU + 'a') - 1) AS DECIMAL(10, 2))
            ELSE NULL 
        END AS NUMERIC_PART
    FROM _USR_CONF_PLCM
    WHERE PLTM = (
        SELECT PLTM
        FROM _USR_CONF_PLTM
        WHERE PLLN = ?
          AND PLBR = ?
          AND PLET = ?
          AND PLTP = ?
    )
) AS TEMPORARIA
ORDER BY ORDEM, NUMERIC_PART;
";

$params = [$plln, $plbr, $plet, $pltp];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLGU"]);
    echo "<option value=\"{$valor}\">{$valor}</option>";
}

$pdo = null;
?>
