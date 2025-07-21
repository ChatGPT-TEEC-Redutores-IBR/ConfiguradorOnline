<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/JWT_Helper.php';
require_once $baseDir . '/Restritos/Credenciais/RateLimiter.php';
require_once $baseDir . '/Restritos/Credenciais/TokenBlacklist.php';
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function formatar_cpf(string $valor): string {
    $valor = preg_replace('/\D/', '', $valor);
    $valor = substr($valor, 0, 11);
    $valor = preg_replace('/(\d{3})(\d)/', '$1.$2', $valor, 1);
    $valor = preg_replace('/(\d{3})(\d)/', '$1.$2', $valor, 1);
    $valor = preg_replace('/(\d{3})(\d{1,2})$/', '$1-$2', $valor);
    return $valor;
}

function formatar_cnpj(string $valor): string {
    $valor = preg_replace('/\D/', '', $valor);
    $valor = substr($valor, 0, 14);
    $valor = preg_replace('/^(\d{2})(\d)/', '$1.$2', $valor);
    $valor = preg_replace('/^(\d{2})\.(\d{3})(\d)/', '$1.$2.$3', $valor);
    $valor = preg_replace('/\.(\d{3})(\d)/', '.$1/$2', $valor);
    $valor = preg_replace('/(\d{4})(\d)/', '$1-$2', $valor);
    return $valor;
}

function formatar_documento(string $valor): string {
    $valor = preg_replace('/\D/', '', $valor);
    return strlen($valor) > 11 ? formatar_cnpj($valor) : formatar_cpf($valor);
}

if (!check_rate_limit('editar_perfil')) {
    http_response_code(429);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Tente novamente mais tarde.']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
require_valid_csrf_token();

$token = $_COOKIE['auth_token'] ?? '';
if (!$token) {
    http_response_code(401);
    echo json_encode(['erro' => '⚠️ Usuário não autenticado.']);
    exit;
}

$segredo = getenv('JWT_SECRET');
if ($segredo === false) {
    $segredo = trim(file_get_contents(__DIR__ . '/../../../../Restritos/Credenciais/Segredo.jwt'));
}

$dadosToken = JWTHelper::decode($token, $segredo);
if (!$dadosToken || is_token_blacklisted($token)) {
    http_response_code(403);
    echo json_encode(['erro' => '⚠️ Credenciais inválidas.']);
    exit;
}

$nome = filter_input(INPUT_POST, 'nome', FILTER_UNSAFE_RAW) ?? '';
$nome = mb_strtoupper(trim(strip_tags($nome)), 'UTF-8');
$emailNovo = strtolower(trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? ''));
$cpfcnpj = preg_replace('/\D/', '', filter_input(INPUT_POST, 'cpfcnpj', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
$senha = $_POST['senha'] ?? '';
$senhaRep = $_POST['senhaRepetida'] ?? '';
$hashSenha = '';
if ($senha || $senhaRep) {
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
}
$nomeAtual = mb_strtoupper(trim($dadosToken['nome'] ?? ''), 'UTF-8');
$emailAtual = strtolower(trim($dadosToken['email'] ?? ''));
$cpfAtual = preg_replace('/\D/', '', $dadosToken['cpfcnpj'] ?? '');
require_once $baseDir . '/Restritos/Credenciais/EmpresaHelper.php';
$empresa = obter_nome_empresa($cpfcnpj, $baseDir);


if (!$nome || !$emailNovo || !$cpfcnpj || !filter_var($emailNovo, FILTER_VALIDATE_EMAIL) || strlen($emailNovo) > 255 || !in_array(strlen($cpfcnpj), [11,14])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Dados inválidos.']);
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/BancoDados.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    if ($emailNovo !== $emailAtual) {
        $stmt = $pdo->prepare("SELECT 1 FROM _USR_CONF_SITE_CADASTROS WHERE LOWER(DS_EMAIL)=?");
        $stmt->execute([$emailNovo]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ E-mail já cadastrado.']);
            exit;
        }
    }

    $tokenNovo = bin2hex(random_bytes(32));
    $dirTokens = __DIR__ . '/../../../../Restritos/Credenciais/TokensEmail';
    if (!is_dir($dirTokens)) {
        mkdir($dirTokens, 0700, true);
        file_put_contents($dirTokens . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
    }
    $tokenFile = $dirTokens . '/' . $tokenNovo . '.json';
    $dadosTokenFile = [
        'nome' => $nome,
        'cpfcnpj' => $cpfcnpj,
        'novoEmail' => $emailNovo,
        'emailAtual' => $emailAtual,
        'grupo' => $dadosToken['grupo'],
        'codigo' => $dadosToken['codigo'],
        'criadoEm' => time()
    ];
    if ($hashSenha) {
        $dadosTokenFile['senha'] = $hashSenha;
        $dadosTokenFile['dtSenha'] = time();
    }
    file_put_contents($tokenFile, json_encode($dadosTokenFile));
    chmod($tokenFile, 0600);
    
    $alteracoes = [];
    if ($nome !== $nomeAtual) {
        $alteracoes[] = 'Nome: ' . $nomeAtual . ' -> ' . $nome;
    }
    if ($emailNovo !== $emailAtual) {
        $alteracoes[] = 'E-mail: ' . $emailAtual . ' -> ' . $emailNovo;
    }
    if ($cpfcnpj !== $cpfAtual) {
        $alteracoes[] = 'CPF/CNPJ: ' . formatar_documento($cpfAtual) . ' -> ' . formatar_documento($cpfcnpj);
    }
    if ($hashSenha) {
        $alteracoes[] = 'Senha: ***';
    }
    $detalhesHtml = implode('<br>', array_map('htmlspecialchars', $alteracoes));
    $detalhesTxt = implode("\n", $alteracoes);

        require __DIR__ . '/../../../../Restritos/Credenciais/PHPMailer/src/PHPMailer.php';
        require __DIR__ . '/../../../../Restritos/Credenciais/PHPMailer/src/SMTP.php';
        require __DIR__ . '/../../../../Restritos/Credenciais/PHPMailer/src/Exception.php';
        require __DIR__ . '/../../../../Restritos/Credenciais/AcessoEmail.php';
        
        $linkConfirmacao = 'https://configurador.redutoresibr.com.br/AreaCliente/Sessao/EditarPerfil/EditarPerfil.php?token=' . $tokenNovo;
        $template = file_get_contents(__DIR__ . '/EmailConfirmacao.html');
        $html = str_replace(
            ['NOME_USUARIO', 'LINK_CONFIRMACAO', 'DETALHES_MUDANCA'],
            [$nome, $linkConfirmacao, $detalhesHtml],
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
            $emailDestino = $emailAtual;
            $mail->addAddress($emailDestino);
            $mail->addEmbeddedImage(
                $baseDir . '/Layout/Imagens/Logotipos/Logotipo.png',
                'logo_ibr'
            );
            $mail->Subject = 'Confirmação de Atualização de Dados - Configurador de Produtos da Redutores IBR';
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = "Olá $nome,\n" . ($detalhesTxt ? "\nSolicitações de Mudança:\n$detalhesTxt\n" : "\n") . "\nConfirme sua alteração acessando: $linkConfirmacao";            $mail->send();
            log_event('Email de confirmacao de alteração enviado para ' . $emailDestino);
        } catch (Exception $e) {
            log_event('Erro ao enviar email de confirmacao para ' . $emailDestino . ': ' . $e->getMessage());
        }
        echo json_encode(['sucesso' => true, 'mensagem' => '✅ Verifique o e-mail ' . $emailDestino . ' para confirmar a alteração.']);
        $pdo = null;
} catch (Throwable $e) {
    log_event('SolicitarAtualizacao: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Erro ao atualizar dados.']);
}
?>