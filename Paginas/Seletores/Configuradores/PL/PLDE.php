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
$plrd = isset($_GET['PLRD']) ? $_GET['PLRD'] : '';

$sql = "
SELECT PLDE
FROM (
    SELECT PLDE, 1 AS ORDEM
    FROM _USR_CONF_PLBU
    WHERE PLDE IN (
        SELECT DISTINCT PLDE
        FROM _USR_CONF_PLBR
        WHERE PLLN = ?
          AND PLBR = ?
          AND PLET = ?
          AND PLTP = ?
          AND PLRD = ?
    )

    UNION

    SELECT PLBU, 1 AS ORDEM
    FROM _USR_CONF_PLBU
    WHERE PLDE IN (
        SELECT DISTINCT PLDE
        FROM _USR_CONF_PLBR
        WHERE PLLN = ?
          AND PLBR = ?
          AND PLET = ?
          AND PLTP = ?
          AND PLRD = ?
    )

    UNION

    SELECT PLBU, 1 AS ORDEM
    FROM _USR_CONF_PLBU
    WHERE PLDE IN (
        SELECT PLBU
        FROM _USR_CONF_PLBU
        WHERE PLDE IN (
            SELECT DISTINCT PLDE
            FROM _USR_CONF_PLBR
            WHERE PLLN = ?
              AND PLBR = ?
              AND PLET = ?
              AND PLTP = ?
              AND PLRD = ?
        )
    )

    UNION

    SELECT PLBU, 1 AS ORDEM
    FROM _USR_CONF_PLBU
    WHERE PLBU IN (
        SELECT DISTINCT PLDE
        FROM _USR_CONF_PLBU
    )
    AND PLDE IN (
        SELECT DISTINCT PLDE
        FROM _USR_CONF_PLBR
        WHERE PLLN = ?
          AND PLBR = ?
          AND PLET = ?
          AND PLTP = ?
          AND PLRD = ?
    )
) AS TEMPORARIA
ORDER BY ORDEM,
    CASE 
        WHEN ISNUMERIC(PLDE) = 1 THEN CONVERT(decimal(10,2), PLDE)
        ELSE NULL
    END;
";

$params = [$plln, $plbr, $plet, $pltp, $plrd, $plln, $plbr, $plet, $pltp, $plrd, $plln, $plbr, $plet, $pltp, $plrd, $plln, $plbr, $plet, $pltp, $plrd];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLDE"]);
    echo "<option value=\"{$valor}\">{$valor}</option>";
}

$pdo = null;
?>