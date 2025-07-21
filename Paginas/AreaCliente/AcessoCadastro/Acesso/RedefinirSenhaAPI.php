<?php
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    }
});

header('Content-Type: application/json; charset=utf-8');

$token = filter_input(INPUT_POST, 'token', FILTER_UNSAFE_RAW) ?: filter_input(INPUT_GET, 'token', FILTER_UNSAFE_RAW) ?: '';
if (!preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token inválido.']);
    exit;
}

$arquivo = $baseDir . '/Restritos/Credenciais/TokensRecuperacao/' . $token . '.json';
if (!file_exists($arquivo)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token inválido ou expirado.']);
    exit;
}

$dados = json_decode(file_get_contents($arquivo), true);
$criado = $dados['criadoEm'] ?? 0;
if (time() - $criado > 24*60*60) {
    unlink($arquivo);
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token expirado.']);
    exit;
}

$email = strtolower(trim($dados['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token();
    $senha = $_POST['senha'] ?? '';
    $senhaRep = $_POST['senhaRepetida'] ?? '';
    if ($senha !== $senhaRep) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'mensagem' => 'As senhas não conferem.']);
        exit;
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $senha)) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Senha fora do padrão de segurança.']);
        exit;
    }
    require $baseDir . '/Restritos/Credenciais/BancoDados.php';
    try {
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
            $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
        }
        $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, $options);
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE _USR_CONF_SITE_CADASTROS SET DS_SENHA=? WHERE LOWER(DS_EMAIL)=?");
        $upd->execute([$hash, strtolower($email)]);
        require_once $baseDir . '/Restritos/Credenciais/SenhaHelper.php';
        set_password_timestamp($email);

        $stmt = $pdo->prepare("SELECT DS_NOME, NR_CPFCNPJ FROM _USR_CONF_SITE_CADASTROS WHERE LOWER(DS_EMAIL)=?");
        $stmt->execute([strtolower($email)]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        require_once $baseDir . '/Restritos/Credenciais/EmpresaHelper.php';
        require_once $baseDir . '/Restritos/Credenciais/JWT_Helper.php';

        $cpfcnpj = $usuario['NR_CPFCNPJ'] ?? '';
        $cpfcnpjNumeros = preg_replace('/\D/', '', $cpfcnpj);
        $empresa = obter_nome_empresa($cpfcnpjNumeros, $baseDir);

        $codigo = 0;
        $grupo = 'BRONZE';
        try {
            $codStmt = $pdo->prepare("SELECT TOP 1 CD_PESSOA FROM MBAD_PESSOA WHERE NR_CPFCNPJ = ?");
            $codStmt->execute([$cpfcnpjNumeros]);
            $codigo = $codStmt->fetchColumn() ?: 0;
            if ($codigo) {
                $permStmt = $pdo->prepare("SELECT MAX(TIPO.DS_TIPO) AS PERMISSAO\nFROM MBAD_PESSOACONTATO AS CONTATO\nINNER JOIN MBAD_PESSOACONTATOTIPO AS TIPO ON CONTATO.CD_TIPO = TIPO.CD_TIPO\nWHERE CONTATO.CD_PESSOA = ? AND CONTATO.CD_FUNCAO = 'SITE' AND LOWER(CONTATO.DS_EMAIL) = ?");
                $permStmt->execute([$codigo, strtolower(trim($email))]);
                $permissao = $permStmt->fetchColumn();
                $grupo = $permissao ? $permissao : 'PRATA';
            }
        } catch (PDOException $e) {
        }

        $segredo = getenv('JWT_SECRET');
        if ($segredo === false) {
            $segredo = trim(file_get_contents($baseDir . '/Restritos/Credenciais/Segredo.jwt'));
        }

        $ttlPadrao = getenv('JWT_TTL');
        $ttlPadrao = $ttlPadrao === false ? 86400 : (int)$ttlPadrao;
        $ttl = $ttlPadrao;

        $payload = [
            'email' => $email,
            'grupo' => $grupo,
            'nome' => $usuario['DS_NOME'] ?? '',
            'cpfcnpj' => $cpfcnpj,
            'empresa' => $empresa,
            'codigo' => $codigo,
            'exp' => time() + $ttl
        ];

        $tokenJWT = JWTHelper::encode($payload, $segredo);

        setcookie('auth_token', $tokenJWT, [
            'expires' => time() + $ttl,
            'path' => '/',
            'domain' => 'configurador.redutoresibr.com.br',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        unlink($arquivo);
        http_response_code(200);
        echo json_encode(['sucesso' => true]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar senha.']);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro inesperado.']);
    }
} else {
    http_response_code(200);
    echo json_encode(['sucesso' => true]);
    exit;
}
?>