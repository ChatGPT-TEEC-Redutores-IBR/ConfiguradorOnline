<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


$pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
]);

$aeln = isset($_GET['AELN']) ? $_GET['AELN'] : '';
$aebr = isset($_GET['AEBR']) ? $_GET['AEBR'] : '';
$aetp = isset($_GET['AETP']) ? $_GET['AETP'] : '';

$sql = "SELECT DISTINCT COALESCE(
        (SELECT CONCAT(AEOC, ' - ', AEOD)
        FROM _USR_CONF_AEOLN
         WHERE AELN = ?
           AND AEBR = ?
           AND AETP = ?
           AND AEEL = OBSERVACOES.AEEL),
        OBSERVACOES.AEEL) AS DESCRICAO,
    OBSERVACOES.AEEL
FROM 
    (SELECT DISTINCT AEEL
    FROM _USR_CONF_AELN
     WHERE AELN = ?
       AND AEBR = ?
       AND AETP = ?
    ) AS OBSERVACOES
ORDER BY OBSERVACOES.AEEL;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$aeln, $aebr, $aetp, $aeln, $aebr, $aetp]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $valor = htmlspecialchars($row["AEEL"]);
    $descricao = htmlspecialchars($row["DESCRICAO"]);

    echo '<option value="' . $valor . '">' . $descricao . '</option>';
}

$pdo = null;
?>