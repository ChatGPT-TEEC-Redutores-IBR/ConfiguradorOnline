<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$sql = "SELECT DISTINCT
    CASE 
    WHEN HYLN = '1.C' THEN 'REDUTOR IBR C - COAXIAL'
    WHEN HYLN = '1.H' THEN 'REDUTOR IBR H - HELICOIDAL'
    WHEN HYLN = '1.M' THEN 'REDUTOR IBR M - MONOESTÁGIO'
    WHEN HYLN = '1.P' THEN 'REDUTOR IBR P - PARALELO'
    WHEN HYLN = '1.R' THEN 'REDUTOR IBR R - REDONDO'
    WHEN HYLN = '1.X' THEN 'REDUTOR IBR X - ORTOGONAL'
        ELSE HYLN
    END AS DESCRIÇÃO,
    HYLN
    FROM _USR_CONF_HYBR
ORDER BY HYLN;";

$query = $pdo->prepare($sql);
$query->execute();

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo '<option value="' . htmlspecialchars($row["HYLN"]) . '">' . htmlspecialchars($row["DESCRIÇÃO"]) . '</option>';
}

$pdo = null;
?>