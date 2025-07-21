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
$hyrd = isset($_GET['HYRD']) ? $_GET['HYRD'] : '';
$hycm = isset($_GET['HYCM']) ? $_GET['HYCM'] : '';

$sql = "SELECT 
    CASE
        WHEN HYBU = 'N' THEN 'NÃO'
        WHEN HYBU = 'B1' THEN 'BUCHA SIMPLES'
        WHEN HYBU = 'B2' THEN 'BUCHA DUPLA'
        WHEN ? LIKE '%EE%' THEN NULL
        WHEN ? = '1.R' THEN NULL
        ELSE HYBU
    END AS DESCRIÇÃO,
    HYBU
FROM ( 
    SELECT DISTINCT HYBU
    FROM _USR_CONF_HYKE
    WHERE HYLN = ?
      AND HYBR = ?
      AND HYCM = ?
      AND ? NOT LIKE '%EE%'
) AS Subquery;";

$query = $pdo->prepare($sql);
$query->execute([$hycm, $hyln, $hyln, $hybr, $hycm, $hycm]);

$temProduto = false;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["HYBU"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    if ($valor !== '' && $descricao !== '') {
        echo '<option value="' . $valor . '">' . $descricao . '</option>';
        $temProduto = true;
    }
}

$pdo = null;
?>