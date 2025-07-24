<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once __DIR__ . '/../../../../LogsErros/Logs.php';
require_once __DIR__ . '/../../../../Restritos/Credenciais/JWT_Helper.php';
require_once __DIR__ . '/../../../../Restritos/Credenciais/TokenBlacklist.php';

$token = filter_input(INPUT_GET, 'token', FILTER_UNSAFE_RAW) ?? '';
if (!preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
    log_event('Token email invalido');
    header('Location: /AreaCliente/Sessao?erro=token_invalido');
    exit;
}

$arquivo = __DIR__ . '/../../../../Restritos/Credenciais/TokensEmail/' . $token . '.json';
if (!file_exists($arquivo)) {
    log_event('Arquivo token email inexistente');
    header('Location: /AreaCliente/Sessao?erro=token_invalido');
    exit;
}

$dados = json_decode(file_get_contents($arquivo), true);
$criado = $dados['criadoEm'] ?? 0;
if (time() - $criado > 24*60*60) {
    unlink($arquivo);
    log_event('Token email expirado');
    header('Location: /AreaCliente/Sessao?erro=token_expirado');
    exit;
}

$nome = mb_strtoupper($dados['nome'] ?? '', 'UTF-8');
$cpfcnpj = preg_replace('/\D/', '', $dados['cpfcnpj'] ?? '');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);
require_once $baseDir . '/Restritos/Credenciais/EmpresaHelper.php';
$empresa = obter_nome_empresa($cpfcnpj, $baseDir);
$novoEmail = $dados['novoEmail'] ?? '';
$emailAtual = $dados['emailAtual'] ?? '';
$grupo = $dados['grupo'] ?? '';
$codigo = $dados['codigo'] ?? 0;
$hashSenha = $dados['senha'] ?? '';

require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/BancoDados.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $stmt = $pdo->prepare("SELECT 1 FROM _USR_CONF_SITE_CADASTROS WHERE LOWER(DS_EMAIL)=? AND LOWER(DS_EMAIL)<>?");
    $stmt->execute([$novoEmail, $emailAtual]);
    if ($stmt->fetch()) {
        unlink($arquivo);
        log_event('Novo email ja utilizado: ' . $novoEmail);
        header('Location: /AreaCliente/Sessao?erro=email_em_uso');
        exit;
    }

    $sqlUpd = "UPDATE _USR_CONF_SITE_CADASTROS SET DS_EMAIL=?, DS_NOME=?, NR_CPFCNPJ=?";
    $paramsUpd = [$novoEmail, $nome, $cpfcnpj];
    if ($hashSenha) {
        $sqlUpd .= ", DS_SENHA=?";
        $paramsUpd[] = $hashSenha;
    }
    $sqlUpd .= " WHERE LOWER(DS_EMAIL)=?";
    $paramsUpd[] = $emailAtual;
    $upd = $pdo->prepare($sqlUpd);
    $upd->execute($paramsUpd);
    if ($hashSenha) {
        require_once $baseDir . '/Restritos/Credenciais/SenhaHelper.php';
        set_password_timestamp($novoEmail, time());
    }

    
    $codigo = 0;
    $grupo = 'BRONZE';
    try {
        $codStmt = $pdo->prepare(
            "SELECT TOP 1 CD_PESSOA FROM MBAD_PESSOA WHERE NR_CPFCNPJ = ?"
        );
        $codStmt->execute([preg_replace('/\\D/', '', $cpfcnpj)]);
        $codigo = $codStmt->fetchColumn() ?: 0;

        if ($codigo) {
            $permStmt = $pdo->prepare(
                "SELECT MAX(TIPO.DS_TIPO) AS PERMISSAO\n" .
                "FROM MBAD_PESSOACONTATO AS CONTATO\n" .
                "INNER JOIN MBAD_PESSOACONTATOTIPO AS TIPO ON CONTATO.CD_TIPO = TIPO.CD_TIPO\n" .
                "WHERE CONTATO.CD_PESSOA = ? AND CONTATO.CD_FUNCAO = 'SITE' AND LOWER(CONTATO.DS_EMAIL) = ?"
            );
            $permStmt->execute([$codigo, strtolower(trim($novoEmail))]);
            $permissao = $permStmt->fetchColumn();
            $grupo = $permissao ? $permissao : 'PRATA';
        }
    } catch (PDOException $e) {
        log_event('EditarPerfil consulta codigo/permissao: ' . $e->getMessage());
    }
    
 unlink($arquivo);

    $tokenCookie = $_COOKIE['auth_token'] ?? '';
    if ($tokenCookie) {
        blacklist_token($tokenCookie);
    }
    setcookie('auth_token', '', time() - 3600, '/', 'configurador.redutoresibr.com.br', true, true);

    log_event('Email atualizado para ' . $novoEmail);
    header('Location: /AreaCliente?atualizacao=sucesso');
    exit;
} catch (PDOException $e) {
    log_event('ConfirmarEmail: ' . $e->getMessage());
    header('Location: /AreaCliente/Sessao?erro=erro');
    exit;
}