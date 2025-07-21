<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$moln = isset($_GET['MOLN']) ? $_GET['MOLN'] : '';
$motp = isset($_GET['MOTP']) ? $_GET['MOTP'] : '';
$qucm = isset($_GET['QUCM']) ? $_GET['QUCM'] : '';
$qucp = isset($_GET['QUCP']) ? $_GET['QUCP'] : '';

$sql = "SELECT DISTINCT CASE 
            WHEN MOTT = MOTT THEN MONTT
            ELSE MOTT
        END AS DESCRICAO,
        MOTT
    FROM ( 
        SELECT A.MOTT, A.MONTT
        FROM _USR_CONF_MOBR A
        JOIN _USR_CONF_MOCP B ON (A.MOLN = B.MOLN AND A.MOCM = B.MOCM)
        WHERE A.MOLN = ?
        AND A.MOTP = ?
        AND ((? = '100-112' AND A.MOCM IN ('100', '112'))
            OR A.MOCM = ?)
        AND B.MOCP LIKE REPLACE(REPLACE(?, 'B5', '5'), 'B14', '4')
    ) AS Subquery;";

$qucpModified = '%' . $qucp . '%';

$query = $pdo->prepare($sql);
$query->execute([$moln, $motp, $qucm, $qucm, $qucpModified]);

echo '<option value="" selected hidden></option>';

$temProduto = false;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["MOTT"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
    $temProduto = true;
}

$pdo = null;
?>