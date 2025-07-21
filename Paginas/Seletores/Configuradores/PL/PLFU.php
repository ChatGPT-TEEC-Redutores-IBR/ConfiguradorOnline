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
$plgu = isset($_GET['PLGU']) ? $_GET['PLGU'] : '';
$pltp = isset($_GET['PLTP']) ? $_GET['PLTP'] : '';

if ($pltp != 'A') {
    $pltp = 'PADRÃO';
}

$sql = "
SELECT PLFU
FROM (
    SELECT DISTINCT PLFU, 1 AS ORDEM
    FROM _USR_CONF_PLCM
    WHERE PLTM = (
        SELECT PLTM
        FROM _USR_CONF_PLTM
        WHERE PLLN = ?
          AND PLBR = ?
          AND PLET = ?
          AND PLTP = ?
    )
    AND PLGU = ?
) AS TEMPORARIA
ORDER BY ORDEM,
    CASE 
        WHEN ISNUMERIC(PLFU) = 1 THEN CONVERT(decimal(10, 2), PLFU)
        ELSE NULL
    END;
";

$params = [$plln, $plbr, $plet, $pltp, $plgu];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLFU"]);
    echo "<option value=\"{$valor}\">{$valor}</option>";
}

$pdo = null;
?>
