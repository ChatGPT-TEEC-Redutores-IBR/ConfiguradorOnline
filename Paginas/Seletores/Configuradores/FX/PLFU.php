<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$plbu = isset($_GET['PLBU']) ? $_GET['PLBU'] : '';
$qucm = isset($_GET['QUCM']) ? $_GET['QUCM'] : '';
$plde = isset($_GET['PLDE']) ? $_GET['PLDE'] : '';
$plgu = isset($_GET['PLGU']) ? $_GET['PLGU'] : '';

$sql = "SELECT PLFU
FROM (
SELECT PLFU, 1 as ORDEM
FROM _USR_CONF_PLCM
WHERE PLTM = (
        CASE
        WHEN (? = '63')
        OR (? = '71' AND ? = '19')
        OR (? = '71' AND ? = '19' AND ? = 'N')
        THEN '7MP62A'
        
        WHEN (? = '71' AND ? = '24')
        OR (? = '71' AND ? = '24' AND ? = 'N')
        OR (? = '80')
        OR (? = '90' AND ? = '24')
        OR (? = '90' AND ? = '24' AND ? = 'N')
        THEN '7MP90A'
        
        WHEN (? = '90' AND ? = '32')
        OR (? = '90' AND ? = '32' AND ? = 'N')
        OR (? = '100-112' AND ? = '32')
        OR (? = '100-112' AND ? = '32' AND ? = 'N')
        THEN '7MP120A'
        
        WHEN (? = '100-112' AND ? = '42')
        OR (? = '100-112' AND ? = '42' AND ? = 'N')
        THEN '7MP142A'
    END
)

        AND PLGU = ?
) AS TEMPORARIA
ORDER BY ORDEM, TRY_CAST(PLFU AS DECIMAL(10, 2));
";

$query = $pdo->prepare($sql);
$query->execute([$qucm, $qucm, $plbu, $qucm, $plde, $plbu, $qucm, $plbu, $qucm, $plde, $plbu, $qucm, $qucm, $plbu, $qucm, $plde, $plbu,
               $qucm, $plbu, $qucm, $plde, $plbu, $qucm, $plbu, $qucm, $plde, $plbu, $qucm, $plbu, $qucm, $plde, $plbu, $plgu]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLFU"]);
    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>