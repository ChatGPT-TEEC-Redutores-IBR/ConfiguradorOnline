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
    WHEN FXLN = '1.FFA' THEN 'REDUTOR IBR P FFA - PARALELO'
    WHEN FXLN = '1.FKA' THEN 'REDUTOR IBR X FKA - ORTOGONAL'
    WHEN FXLN = '1.FR' THEN 'REDUTOR IBR C FR - COAXIAL'
    ELSE FXLN
    END AS DESCRIÇÃO,
    FXLN
    FROM _USR_CONF_FXBR
    ORDER BY FXLN;";

$query = $pdo->prepare($sql);
$query->execute([]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["FXLN"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>