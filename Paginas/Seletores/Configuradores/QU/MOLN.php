<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$qucm = isset($_GET['QUCM']) ? $_GET['QUCM'] : '';
$qucp = isset($_GET['QUCP']) ? $_GET['QUCP'] : '';

$sql = "SELECT 
    CASE 
        WHEN MOLN = '2.I' THEN 'MOTOR IBR MS/ML'
        WHEN MOLN = '3.I' THEN 'MOTOR IBR T3A/T3C'
        WHEN MOLN = '3.W' THEN 'MOTOR WEG ALTO RENDIMENTO'
        WHEN MOLN = '3.APM' THEN 'MOTOR IBR ANTICORROSIVO APM'
        WHEN MOLN = '3.SPM' THEN 'MOTOR IBR ANTICORROSIVO SPM'
        ELSE MOLN
    END AS DESCRIÇÃO,
    MOLN
FROM ( 
    SELECT DISTINCT A.MOLN
    FROM _USR_CONF_MOBR A
    JOIN _USR_CONF_MOCP B ON (A.MOLN = B.MOLN AND A.MOCM = B.MOCM)
    AND ((? = '100-112' AND A.MOCM IN ('100', '112'))
        OR A.MOCM = ?)
    AND B.MOCP LIKE REPLACE(REPLACE(?, 'B5', '5'), 'B14', '4')
) AS Subquery";

$query = $pdo->prepare($sql);

$qucpModified = '%' . $qucp . '%';

$query->execute([$qucm, $qucm, $qucpModified]);

echo '<option value="" selected hidden></option>';

$temProduto = false;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOLN"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
    $temProduto = true;
}

$pdo = null;
?>