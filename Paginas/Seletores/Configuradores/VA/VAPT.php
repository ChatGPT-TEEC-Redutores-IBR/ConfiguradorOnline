<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$valn = isset($_GET['VALN']) ? $_GET['VALN'] : '';

$sql = "SELECT DISTINCT VAPT
FROM _USR_CONF_VABR
WHERE VALN = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$valn]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["VAPT"]);

    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>