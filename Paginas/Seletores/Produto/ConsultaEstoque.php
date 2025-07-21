<?php
header('Content-Type: application/json; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require $baseDir . '/Restritos/Credenciais/BancoDados.php';


try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $codigoProduto = isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '';
    if (!$codigoProduto) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT 
                ESTOQUECOMPONENTE.CD_EMPRESA,
                ESTOQUECOMPONENTE.CD_ALMOXARIFADO,
                ESTOQUECOMPONENTE.CD_PRODUTO,
                MIN(ESTOQUECOMPONENTE.NR_QTTOTAL - ISNULL(ESTOQUECOMPONENTE.NR_QTBLOQUEADA, 0)) AS ESTOQUEDISPONIVEL
            FROM (
                SELECT DISTINCT CD_EMPRESA FROM MMES_ESTOQUE
            ) AS CODIGOEMPRESA
            CROSS APPLY fn_explodeestoque(CODIGOEMPRESA.CD_EMPRESA, ?) AS ESTRUTURA
            INNER JOIN MMES_ESTOQUE AS ESTOQUECOMPONENTE 
                ON ESTOQUECOMPONENTE.CD_EMPRESA = ESTRUTURA.CD_EMPRESA 
                AND ESTOQUECOMPONENTE.CD_PRODUTO = ESTRUTURA.CD_COMPONENTE
            INNER JOIN MMPR_PRODUTO AS PRODUTO 
                ON PRODUTO.CD_EMPRESA = ESTOQUECOMPONENTE.CD_EMPRESA 
                AND PRODUTO.CD_PRODUTO = ESTOQUECOMPONENTE.CD_PRODUTO
            WHERE 
                PRODUTO.ID_CONTRESTOQUE = 0
                AND ESTOQUECOMPONENTE.CD_ALMOXARIFADO IN ('1', '16')
                AND (ESTOQUECOMPONENTE.NR_QTTOTAL - ISNULL(ESTOQUECOMPONENTE.NR_QTBLOQUEADA, 0)) IS NOT NULL
            GROUP BY 
                ESTOQUECOMPONENTE.CD_EMPRESA,
                ESTOQUECOMPONENTE.CD_ALMOXARIFADO,
                ESTOQUECOMPONENTE.CD_PRODUTO";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$codigoProduto]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows ?: []);

} catch (PDOException $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}

$pdo = null;
?>
