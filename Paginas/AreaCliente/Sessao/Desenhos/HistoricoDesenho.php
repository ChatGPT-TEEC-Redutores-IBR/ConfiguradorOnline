<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 3);
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once __DIR__ . '/../../../../LogsErros/Logs.php';
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/BancoDados.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $email = strtolower(trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? ''));
    $produto = strtoupper(trim(filter_input(INPUT_POST, 'produto', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
    $formato = strtoupper(trim(filter_input(INPUT_POST, 'formato', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
    $drvwIdField = trim(filter_input(INPUT_POST, 'drvw_idfield', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');

        $isCodigo = preg_match('/^[A-Z]{2,4}\.[0-9]{8}$/', $produto);
    if ($isCodigo) {
        $sqlRef = "SELECT TOP 1 DS_REFERENCIA FROM MMPR_PRODUTO WHERE CD_PRODUTO = ? AND ID_STATUS = 0 AND CD_PRODCONFIG IS NOT NULL";
        try {
            $stmRef = $pdo->prepare($sqlRef);
            $stmRef->execute([$produto]);
            $rowRef = $stmRef->fetch(PDO::FETCH_ASSOC);
            $produto = $rowRef['DS_REFERENCIA'] ?? $produto;
        } catch (PDOException $e) {
        }
    }

    if (!$email || !$produto || !$formato) {
        echo '⚠️ Dados Incompletos.';
        exit;
    }

    $sql = "INSERT INTO _USR_CONF_SITE_HISTORICO_DESENHO (DS_EMAIL, DS_REFERENCIA, DS_FORMATO, DRVW_IDFIELD, DT_DATA)
            SELECT ?, ?, ?, ?, CONVERT(VARCHAR(19), GETDATE(), 120)
             WHERE NOT EXISTS (
                 SELECT 1 FROM _USR_CONF_SITE_HISTORICO_DESENHO
                  WHERE DS_EMAIL = ? AND DS_REFERENCIA = ? AND DS_FORMATO = ? AND DRVW_IDFIELD = ?
             )";
    $stm = $pdo->prepare($sql);
    $stm->execute([
        $email,
        $produto,
        $formato,
        $drvwIdField,
        $email,
        $produto,
        $formato,
        $drvwIdField
    ]);

    echo '✅ Histórico Salvo.';
    $pdo = null;
} catch (PDOException $e) {
    log_event('HistoricoDesenho: ' . $e->getMessage());
    http_response_code(500);
    echo '⚠️ Erro ao Salvar Dados.';
}
?>