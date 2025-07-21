<?php
ini_set('display_errors', 0);
error_reporting(0);
require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/AcessoSeguranca.php';
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 4);
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/JWT_Helper.php';

$token = filter_input(INPUT_GET, 'token', FILTER_UNSAFE_RAW) ?? '';
if (!preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
    log_event('Token cadastro invalido');
     header('Location: /AreaCliente?cadastro=token_invalido');
    exit;
}

$arquivo = $baseDir . '/Restritos/Credenciais/TokensCadastro/' . $token . '.json';
if (!file_exists($arquivo)) {
    log_event('Arquivo token cadastro inexistente');
    header('Location: /AreaCliente?cadastro=token_invalido');
    exit;
}

$dados = json_decode(file_get_contents($arquivo), true);
$criado = $dados['criadoEm'] ?? 0;
if (time() - $criado > 24*60*60) {
    unlink($arquivo);
    log_event('Token cadastro expirado');
    header('Location: /AreaCliente?cadastro=token_expirado');
    exit;
}

$nome = mb_strtoupper($dados['nome'] ?? '', 'UTF-8');
$email = $dados['email'] ?? '';
$cpfcnpj = preg_replace('/\D/', '', $dados['cpfcnpj'] ?? '');
$hashSenha = $dados['senha'] ?? '';
$dtSenha = $dados['dtSenha'] ?? time();
require_once $baseDir . '/Restritos/Credenciais/EmpresaHelper.php';
$empresa = obter_nome_empresa($cpfcnpj, $baseDir);

require $baseDir . '/Restritos/Credenciais/BancoDados.php';

try {
    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $stmt = $pdo->prepare("SELECT 1 FROM _USR_CONF_SITE_CADASTROS WHERE LOWER(DS_EMAIL) = ?");
    $stmt->execute([strtolower($email)]);
    if ($stmt->fetch()) {
        unlink($arquivo);
        log_event('Cadastro ja confirmado: ' . $email);
    header('Location: /AreaCliente?cadastro=erro');
        exit;
    }

$sql = "INSERT INTO _USR_CONF_SITE_CADASTROS (DS_NOME, DS_EMAIL, NR_CPFCNPJ, DS_SENHA) VALUES (?, ?, ?, ?)";
$ins = $pdo->prepare($sql);
$ins->execute([$nome, strtolower($email), $cpfcnpj, $hashSenha]);


    $cpfcnpjNumeros = preg_replace('/\D/', '', $cpfcnpj);
    $codigo = 0;
    $grupo = 'BRONZE';
    try {
        $codStmt = $pdo->prepare(
            "SELECT TOP 1 CD_PESSOA FROM MBAD_PESSOA WHERE NR_CPFCNPJ = ?"
        );
        $codStmt->execute([$cpfcnpjNumeros]);
        $codigo = $codStmt->fetchColumn() ?: 0;

        if ($codigo) {
            $permStmt = $pdo->prepare(
                "SELECT MAX(TIPO.DS_TIPO) AS PERMISSAO\n" .
                "FROM MBAD_PESSOACONTATO AS CONTATO\n" .
                "INNER JOIN MBAD_PESSOACONTATOTIPO AS TIPO ON CONTATO.CD_TIPO = TIPO.CD_TIPO\n" .
                "WHERE CONTATO.CD_PESSOA = ? AND CONTATO.CD_FUNCAO = 'SITE' AND LOWER(CONTATO.DS_EMAIL) = ?"
            );
            $permStmt->execute([$codigo, strtolower(trim($email))]);
            $permissao = $permStmt->fetchColumn();
            $grupo = $permissao ? $permissao : 'PRATA';
        }
    } catch (PDOException $e) {
        log_event('ConfirmarCadastro consulta codigo/permissao: ' . $e->getMessage());
    }

    unlink($arquivo);
    require_once $baseDir . '/Restritos/Credenciais/SenhaHelper.php';
    set_password_timestamp($email, $dtSenha);
    log_event('Cadastro confirmado: ' . $email);
    
     if ($codigo === 0) {
        $tokenPipe = '6741b82d59d8d230f2aacd0b88f1ea99';
        try {
            $dealData = [
                'pipeline_id' => 78334,
                'stage_id'    => 483644,
                'title'       => 'Lead site configurador - ' . $nome
            ];

            $contextDeal = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nToken: $tokenPipe\r\n",
                    'content' => json_encode($dealData)
                ]
            ]);

            $respDeal = @file_get_contents('https://api.pipe.run/v1/deals', false, $contextDeal);
            $dealJson = $respDeal !== false ? json_decode($respDeal, true) : null;
            $dealId = $dealJson['deal_id'] ?? $dealJson['id'] ?? $dealJson['data']['deal_id'] ?? $dealJson['data']['id'] ?? '';

            $linhasNota = [
                'Nome: ' . $nome,
                (strlen($cpfcnpj) === 11 ? 'CPF' : 'CNPJ') . ': ' . $cpfcnpj
            ];
            if (strlen($cpfcnpj) === 14 && $empresa) {
                $linhasNota[] = 'Empresa: ' . $empresa;
            }
            $linhasNota[] = 'Email: ' . $email;
            $textoNota = implode('<br>', $linhasNota);

            if ($dealId) {
                $contextNote = stream_context_create([
                    'http' => [
                        'method'  => 'POST',
                        'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nToken: $tokenPipe\r\n",
                        'content' => json_encode(['deal_id' => $dealId, 'text' => $textoNota])
                    ]
                ]);

                @file_get_contents('https://api.pipe.run/v1/notes', false, $contextNote);
            }
        } catch (Exception $e) {
            log_event('Erro ao criar lead: ' . $e->getMessage());
        }
    }

$segredo = getenv('JWT_SECRET');
    if ($segredo === false) {
        $segredo = trim(file_get_contents($baseDir . '/Restritos/Credenciais/Segredo.jwt'));
    }

     $ttl = getenv('JWT_TTL');
    if ($ttl === false) {
        $ttl = 86400; // 24 horas por inatividade
    } else {
        $ttl = (int)$ttl;
    }

    $payload = [
        'email' => strtolower($email),
        'grupo' => $grupo,
        'nome' => $nome,
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

    header('Location: /AreaCliente/Sessao');
    exit;
} catch (PDOException $e) {
    log_event('ConfirmarCadastro: ' . $e->getMessage());
    header('Location: /AreaCliente?cadastro=erro');
    exit;
}
?>
