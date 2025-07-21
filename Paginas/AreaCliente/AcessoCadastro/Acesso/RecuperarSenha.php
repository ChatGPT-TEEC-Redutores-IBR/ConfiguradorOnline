<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/RateLimiter.php';
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';

if (!check_rate_limit('recuperar_senha', 5, 3600)) {
    http_response_code(429);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => '⚠️ Limite de solicitações de restauração de senhas atingido. Tente novamente em 1 hora.'
    ]);
      exit;
}

header('Content-Type: application/json; charset=UTF-8');
require_valid_csrf_token();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = strtolower(trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? ''));
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ E-mail inválido.']);
    exit;
}

require $baseDir . '/Restritos/Credenciais/BancoDados.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $stmt = $pdo->prepare("SELECT DS_NOME FROM _USR_CONF_SITE_CADASTROS WHERE LOWER(DS_EMAIL) = ?");
    $stmt->execute([strtolower($email)]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ E-mail não cadastrado.']);
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $dirTokens = $baseDir . '/Restritos/Credenciais/TokensRecuperacao';
    if (!is_dir($dirTokens)) {
        mkdir($dirTokens, 0700, true);
        file_put_contents($dirTokens . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
    }
    $tokenFile = $dirTokens . '/' . $token . '.json';
    file_put_contents($tokenFile, json_encode([
        'email' => $email,
        'nome' => $usuario['DS_NOME'],
        'criadoEm' => time()
    ]));
    chmod($tokenFile, 0600);

    require $baseDir . '/Restritos/Credenciais/PHPMailer/src/PHPMailer.php';
    require $baseDir . '/Restritos/Credenciais/PHPMailer/src/SMTP.php';
    require $baseDir . '/Restritos/Credenciais/PHPMailer/src/Exception.php';
    require $baseDir . '/Restritos/Credenciais/AcessoEmail.php';

    $link = 'https://configurador.redutoresibr.com.br/AreaCliente?token=' . $token;
    $template = file_get_contents(__DIR__ . '/EmailRecuperacao.html');
    $html = str_replace(
        ['NOME_USUARIO', 'LINK_REDEFINICAO'],
        [$usuario['DS_NOME'], $link],
        $template
    );

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
    $mail->addEmbeddedImage($baseDir . '/Layout/Imagens/Logotipos/Logotipo.png', 'logo_ibr');
    $mail->Subject = 'Recuperação de Senha - Configurador Redutores IBR';
    $mail->isHTML(true);
    $mail->Body = $html;
    $mail->AltBody = "Olá {$usuario['DS_NOME']},\n\nPara redefinir sua senha acesse: $link";
    $mail->send();
    log_event('Email de recuperacao enviado para ' . $email);

    echo json_encode(['sucesso' => true, 'mensagem' => '✅ Verifique o e-mail ' . $email . ' para redefinir sua senha.']);
} catch (Exception $e) {
    log_event('RecuperarSenha: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Erro ao enviar instruções.']);
}
?>