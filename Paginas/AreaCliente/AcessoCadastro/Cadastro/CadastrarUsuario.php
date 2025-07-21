<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/RateLimiter.php';
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';

if (!check_rate_limit('cadastro')) {
    http_response_code(429);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Tente novamente mais tarde.']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
require_valid_csrf_token();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$nome = filter_input(INPUT_POST, 'nome', FILTER_UNSAFE_RAW) ?? '';
$nome = mb_strtoupper(trim(strip_tags($nome)), 'UTF-8');
$email = strtolower(trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? ''));
$cpfcnpj = preg_replace('/\D/', '', filter_input(INPUT_POST, 'cpfcnpj', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
$senha = $_POST['senha'] ?? '';
$senhaRep = $_POST['senhaRepetida'] ?? '';

if ($senha !== $senhaRep) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ As senhas não conferem.']);
    exit;
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $senha)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Senha fora do padrão de segurança.']);
    exit;
}
$hashSenha = password_hash($senha, PASSWORD_DEFAULT);

if (strlen($cpfcnpj) === 14) {
    $ctx = stream_context_create([
        'http' => ['timeout' => 10, 'user_agent' => 'ConfiguradorIBR']
    ]);
    $resp = @file_get_contents('https://www.receitaws.com.br/v1/cnpj/' . $cpfcnpj, false, $ctx);
    $dadosEmpresa = $resp ? json_decode($resp, true) : null;
    $nomeReceita = $dadosEmpresa['nome'] ?? ($dadosEmpresa['nome_fantasia'] ?? ($dadosEmpresa['fantasia'] ?? ''));
    if ($nomeReceita) {
        $empresa = strtoupper($nomeReceita);
    }
}

if (!$nome || !$email || !$cpfcnpj || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || !in_array(strlen($cpfcnpj), [11,14])) {
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

    $stmt = $pdo->prepare("SELECT 1 FROM _USR_CONF_SITE_CADASTROS WHERE LOWER(DS_EMAIL) = ?");
    $stmt->execute([strtolower($email)]);
    if ($stmt->fetch()) {
              echo json_encode([
            'sucesso' => false,
            'mensagem' => '⚠️ Você já possui cadastro. Retorne à tela anterior e realize sua autenticação.'
        ]);
         exit;
    }

    $token = bin2hex(random_bytes(32));
    $dirTokens = $baseDir . '/Restritos/Credenciais/TokensCadastro';
    if (!is_dir($dirTokens)) {
        mkdir($dirTokens, 0700, true);
        file_put_contents($dirTokens . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
    }
    $tokenFile = $dirTokens . '/' . $token . '.json';
    file_put_contents($tokenFile, json_encode([
        'nome' => $nome,
        'email' => $email,
        'cpfcnpj' => $cpfcnpj,
        'senha' => $hashSenha,
        'dtSenha' => time(),
        'criadoEm' => time()
    ]));
    chmod($tokenFile, 0600);

    require $baseDir . '/Restritos/Credenciais/PHPMailer/src/PHPMailer.php';
    require $baseDir . '/Restritos/Credenciais/PHPMailer/src/SMTP.php';
    require $baseDir . '/Restritos/Credenciais/PHPMailer/src/Exception.php';
    require $baseDir . '/Restritos/Credenciais/AcessoEmail.php';

 $linkConfirmacao = 'https://configurador.redutoresibr.com.br/Paginas/AreaCliente/AcessoCadastro/Cadastro/ConfirmacaoCadastro.php?token=' . $token;
        $template  = file_get_contents(__DIR__ . '/EmailCadastro.html');
        $html = str_replace(
        ['NOME_USUARIO', 'LINK_CONFIRMACAO'],
        [$nome, $linkConfirmacao],
        $template
    );

    try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = $smtpSecure;
        $mail->Port = $smtpPort;
         $mail->setFrom($smtpUser, 'Não Responder - Comunicação Redutores IBR');
        $mail->addAddress($email);
        $mail->addEmbeddedImage(
            $baseDir . '/Layout/Imagens/Logotipos/Logotipo.png',
            'logo_ibr'
        );
        $mail->Subject = 'Confirmação de Cadastro - Configurador de Produtos da Redutores IBR';
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = "Olá $nome,\n\nConfirme seu cadastro acessando: $linkConfirmacao";

        $mail->send();
        log_event('Email de confirmacao enviado para ' . $email);
    } catch (Exception $e) {
        log_event('Erro ao enviar email de confirmacao para ' . $email . ': ' . $e->getMessage());
    }

    echo json_encode(['sucesso' => true, 'mensagem' => '✅ Verifique o e-mail ' . $email . ' para confirmar o cadastro.']);
    log_event('Cadastro pendente: ' . $email);
    $pdo = null;
} catch (PDOException $e) {
    log_event('CadastrarUsuario: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Erro ao cadastrar usuário. Entre em contato com nossos Consultores Comerciais.']);
}
?>