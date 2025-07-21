<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$fxln = isset($_GET['FXLN']) ? $_GET['FXLN'] : '';
$fxbr = isset($_GET['FXBR']) ? $_GET['FXBR'] : '';
$fxrd = isset($_GET['FXRD']) ? $_GET['FXRD'] : '';
$fxet = isset($_GET['FXET']) ? $_GET['FXET'] : '';
$fxcm = isset($_GET['FXCM']) ? $_GET['FXCM'] : '';

$sql = "SELECT 
    CASE
        WHEN FXBU = 'N' THEN 'NÃO'
        WHEN FXBU = 'B1' THEN 'BUCHA SIMPLES'
        WHEN FXBU = 'B2' THEN 'BUCHA DUPLA'
        WHEN ? LIKE '%EE%' THEN NULL
        ELSE FXBU
    END AS DESCRIÇÃO,
    FXBU
FROM ( 
    SELECT DISTINCT FXBU
    FROM _USR_CONF_FXBR
    WHERE FXLN = ?
      AND FXBR = ?
      AND FXRD = ?
      AND FXET = ?
      AND FXCM = ?
      AND ? NOT LIKE '%EE%'
) AS Subquery;";

$query = $pdo->prepare($sql);
$query->execute([$fxcm, $fxln, $fxbr, $fxrd, $fxet, $fxcm, $fxcm]);

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["FXBU"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>