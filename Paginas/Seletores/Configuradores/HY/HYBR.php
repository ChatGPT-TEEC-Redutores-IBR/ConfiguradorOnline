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

$sql = "SELECT HYBR
    FROM _USR_CONF_HYBR
    WHERE HYLN = ?
    GROUP BY HYBR
    ORDER BY TRY_CAST(HYBR AS DECIMAL(10, 2))";

$query = $pdo->prepare($sql);
$query->execute([$hyln]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["HYBR"]);
    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>
