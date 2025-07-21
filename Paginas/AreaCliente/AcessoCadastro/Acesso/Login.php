<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/JWT_Helper.php';
require_once $baseDir . '/Restritos/Credenciais/RateLimiter.php';
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_once $baseDir . '/Restritos/Credenciais/SenhaHelper.php';

if (!check_rate_limit('login')) {
    http_response_code(429);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Tente novamente mais tarde.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
require_valid_csrf_token();

$email = strtolower(trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? ''));
$senha = $_POST['senha'] ?? '';
$lembrar = isset($_POST['lembrar']);

if (!$email || !$senha || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Dados inválidos.']);
    exit;
}

require $baseDir . '/Restritos/Credenciais/BancoDados.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $stmt = $pdo->prepare("SELECT DS_SENHA, DS_NOME, NR_CPFCNPJ FROM _USR_CONF_SITE_CADASTROS WHERE LOWER(DS_EMAIL) = ?");
    $stmt->execute([strtolower($email)]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        log_event('Login email inexistente: ' . $email);
        http_response_code(404);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => '⚠️ E-mail não encontrado. Realize seu cadastro.'
        ]);
        exit;
    }

    if (empty($usuario['DS_SENHA']) || !password_verify($senha, $usuario['DS_SENHA'])) {
        log_event('Senha incorreta para ' . $email);
        http_response_code(403);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => '⚠️ Alguma informação está errada. Cadastre-se ou clique em Esqueceu a Senha.'
        ]);
        exit;
    }

    $senhaExpirada = password_expired($email);

    $cpfcnpj = $usuario['NR_CPFCNPJ'];
    $cpfcnpjNumeros = preg_replace('/\D/', '', $cpfcnpj);
    require_once $baseDir . '/Restritos/Credenciais/EmpresaHelper.php';
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
        log_event('Login consulta codigo/permissao: ' . $e->getMessage());
    }

    $segredo = getenv('JWT_SECRET');
    if ($segredo === false) {
        $segredo = trim(file_get_contents($baseDir . '/Restritos/Credenciais/Segredo.jwt'));
    }

    $ttlPadrao = getenv('JWT_TTL');
    $ttlPadrao = $ttlPadrao === false ? 86400 : (int)$ttlPadrao;
    $ttl = $lembrar ? 2592000 : $ttlPadrao; // 30 dias se lembrar

    $payload = [
        'email' => $email,
        'grupo' => $grupo,
        'nome' => $usuario['DS_NOME'],
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

    echo json_encode(['sucesso' => true, 'senhaExpirada' => $senhaExpirada]);
} catch (PDOException $e) {
    log_event('Login: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Erro no servidor.']);
}
?>