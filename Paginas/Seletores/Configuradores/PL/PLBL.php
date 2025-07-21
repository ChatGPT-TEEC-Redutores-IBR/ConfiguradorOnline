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
$plde = isset($_GET['PLDE']) ? $_GET['PLDE'] : '';
$plbu = isset($_GET['PLBU']) ? $_GET['PLBU'] : '';
$plbud = isset($_GET['PLBUD']) ? $_GET['PLBUD'] : '';

$sql = "SELECT PLBL
FROM _USR_CONF_PLBR
WHERE PLLN = ?
  AND PLBR = ?
  AND PLET = ?
  AND PLTP = ?
  AND PLRD = ?
  AND (
      (? = 'N' AND PLDE = ?)
      OR
      (? <> 'N' AND ? = 'N' AND PLDE = ?)
      OR
      (? <> 'N' AND ? <> 'N' AND PLDE = ?)
  )
ORDER BY
    CASE
        WHEN ISNUMERIC(PLBL) = 1 THEN CONVERT(decimal(10, 2), PLBL)
        ELSE NULL
    END;";

$params = [
    $plln, $plbr, $plet, $pltp, $plrd,
    $plbu, $plde,
    $plbu, $plbud, $plbu,
    $plbu, $plbud, $plbud
];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLBL"]);
    echo "<option value=\"{$valor}\">{$valor}</option>";
}

$pdo = null;
?>
