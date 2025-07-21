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
$hycm = isset($_GET['HYCM']) ? $_GET['HYCM'] : '';
$vapt = isset($_GET['VAPT']) ? $_GET['VAPT'] : '';
$hymo = isset($_GET['HYMO']) ? $_GET['HYMO'] : '';

$sql = "SELECT DISTINCT VASCP
FROM _USR_CONF_VABR
WHERE VALN = ?
  AND VACM = ?
  AND VAPT = ?
  AND (? <> 'S' OR VASCP <> 'B3')";

$query = $pdo->prepare($sql);
$query->execute([$valn, $hycm, $vapt, $hymo]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["VASCP"]);
    
    echo '<option value="' . $valor . '">' . $valor . '</option>';
}

$pdo = null;
?>
