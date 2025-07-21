<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/LogsErros/Logs.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/JWT_Helper.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/RateLimiter.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/TokenBlacklist.php';
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!check_rate_limit('excluir_conta')) {
    http_response_code(429);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Tente novamente mais tarde.']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

$token = $_COOKIE['auth_token'] ?? '';
if (!$token) {
    http_response_code(401);
    echo json_encode(['erro' => '⚠️ Usuário não autenticado.']);
    exit;
}

$segredo = getenv('JWT_SECRET');
if ($segredo === false) {
    $segredo = trim(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/Segredo.jwt'));
}

$dados = JWTHelper::decode($token, $segredo);
if (!$dados || is_token_blacklisted($token)) {
    http_response_code(403);
    echo json_encode(['erro' => '⚠️ Credenciais inválidas.']);
    exit;
}

$nome = mb_strtoupper(trim($dados['nome'] ?? ''), 'UTF-8');
$email = strtolower(trim($dados['email'] ?? ''));
$cpfcnpj = preg_replace('/\D/', '', $dados['cpfcnpj'] ?? '');
$grupo = $dados['grupo'] ?? '';
$codigo = $dados['codigo'] ?? 0;

$tokenNovo = bin2hex(random_bytes(32));
$dirTokens = $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/TokensExclusao';
if (!is_dir($dirTokens)) {
    mkdir($dirTokens, 0700, true);
    file_put_contents($dirTokens . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
}
$tokenFile = $dirTokens . '/' . $tokenNovo . '.json';
file_put_contents($tokenFile, json_encode([
    'email' => $email,
    'nome' => $nome,
    'cpfcnpj' => $cpfcnpj,
    'grupo' => $grupo,
    'codigo' => $codigo,
    'criadoEm' => time()
]));
chmod($tokenFile, 0600);

require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/PHPMailer/src/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/PHPMailer/src/SMTP.php';
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/PHPMailer/src/Exception.php';
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoEmail.php';

$linkConfirmacao = 'https://configurador.redutoresibr.com.br/Paginas/AreaCliente/Sessao/ExcluirConta/ExcluirConta.php?token=' . $tokenNovo;
$template = file_get_contents(__DIR__ . '/EmailExclusao.html');
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
    $mail->Subject = 'Confirmação de Exclusão de Conta - Configurador de Produtos da Redutores IBR';
    $mail->isHTML(true);
    $mail->Body = $html;
    $mail->AltBody = "Olá $nome,\n\nConfirme a exclusão acessando: $linkConfirmacao";
    $mail->send();
    log_event('Email de confirmacao de exclusao enviado para ' . $email);
} catch (Exception $e) {
    log_event('Erro ao enviar email de exclusao para ' . $email . ': ' . $e->getMessage());
}

echo json_encode(['sucesso' => true, 'mensagem' => '✅ Verifique o e-mail ' . $email . ' para confirmar a exclusão.']);
?>