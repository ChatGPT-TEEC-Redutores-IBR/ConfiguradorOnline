<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$aeel = isset($_GET['AEEL']) ? $_GET['AEEL'] : '';
$aebr = isset($_GET['AEBR']) ? $_GET['AEBR'] : '';
$aetp = isset($_GET['AETP']) ? $_GET['AETP'] : '';
$aeln = isset($_GET['AELN']) ? $_GET['AELN'] : '';

$sql = "SELECT 
    CASE 
        WHEN AEEEP = 'SIM' THEN CONCAT(AEEE, ' (PADRÃO)')
        ELSE AEEE
    END AS DESCRIÇÃO,
    AEEE
FROM (
    SELECT DISTINCT
        AEEE,
        AEEEP
        FROM _USR_CONF_AELN
    WHERE AELN = ?
        AND AEBR = ?
        AND AETP = ?
        AND AEEL = ?
) AS Subquery;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$aeln, $aebr, $aetp, $aeel]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["AEEE"]);
    $descricao = htmlspecialchars($row["DESCRIÇÃO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>