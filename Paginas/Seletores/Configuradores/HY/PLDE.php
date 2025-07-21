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
$hycm = isset($_GET['HYCM']) ? $_GET['HYCM'] : '';

$sql = "SELECT DISTINCT PLBU
FROM (
    SELECT B.PLBU
    FROM _USR_CONF_PLAS A
    JOIN _USR_CONF_PLBU B ON A.PLDE = B.PLDE
    WHERE A.PLLN = ? AND A.PLBR = ? AND A.PLCM = ?

    UNION

    SELECT PLDE AS PLBU
    FROM _USR_CONF_PLAS
    WHERE PLLN = ? AND PLBR = ? AND PLCM = ?
) AS TEMPORARIA";

$query = $pdo->prepare($sql);
$query->execute([$hyln, $hybr, $hycm, $hyln, $hybr, $hycm]);

echo '<option value="" selected hidden></option>';

$temProduto = false;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["PLBU"]);
    $descricao = htmlspecialchars($row["PLBU"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
    $temProduto = true;
}

$pdo = null;
?>