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
    WHEN VALN = '1.V' THEN 'VARIADOR MECÂNICO DE VELOCIDADE IBR V'
        ELSE VALN
    END AS DESCRIÇÃO,
    VALN
    FROM _USR_CONF_VABR
ORDER BY VALN;";

$query = $pdo->prepare($sql);
$query->execute();

$temProduto = false;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    echo '<option value="' . htmlspecialchars($row["VALN"]) . '">' . htmlspecialchars($row["DESCRIÇÃO"]) . '</option>';
    $temProduto = true;
}

$pdo = null;
?>