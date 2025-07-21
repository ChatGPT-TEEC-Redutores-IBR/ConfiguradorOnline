<?php
header('Content-Type: text/html; charset=UTF-8');
$baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
require_once $baseDir . '/Restritos/Credenciais/CSRF.php';
require_valid_csrf_token();
require $baseDir . '/Restritos/Credenciais/AcessoSeguranca.php';
require_once $baseDir . '/LogsErros/Logs.php';
require_once $baseDir . '/Restritos/Credenciais/RateLimiter.php';
require_once $baseDir . '/Restritos/Credenciais/JWT_Helper.php';

if (!check_rate_limit('solicitar_cotacao', 10, 3600)) {
    http_response_code(429);
    echo '⚠️ Limite Máximo de Solicitações Excedido. Tente novamente em 1 hora.';
    exit;
}

$nome      = strtoupper(trim(filter_input(INPUT_POST, 'nome', FILTER_UNSAFE_RAW) ?? ''));
$empresa   = strtoupper(trim(filter_input(INPUT_POST, 'empresa', FILTER_UNSAFE_RAW) ?? ''));
$email     = strtoupper(trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? ''));
$telefone  = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
$quantidade= trim(filter_input(INPUT_POST, 'quantidade', FILTER_SANITIZE_NUMBER_INT) ?? '');
$observacao= strtoupper(trim(filter_input(INPUT_POST, 'observacao', FILTER_UNSAFE_RAW) ?? ''));
$cnpj      = preg_replace('/\D/', '', filter_input(INPUT_POST, 'cnpj', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
$cdProduto = strtoupper(trim(filter_input(INPUT_POST, 'cd_produto', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));
$referencia= strtoupper(trim(filter_input(INPUT_POST, 'referencia_produto', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));

if (!$nome || !$email || !$cnpj || !$quantidade) {
    echo '⚠️ Dados Incompletos.';
    exit;
}

$agora = new DateTime('now');
$created = $agora->format('Y-m-d H:i:s');
$dataTitulo = $agora->format('d/m/Y H:i');

$nomeEmpresaOuPessoa = strlen($cnpj) === 11 ? $nome : $empresa;
$titulo = "Site - $cnpj - $nomeEmpresaOuPessoa - $dataTitulo";

$stageId = 574348;
$tokenJwt = $_COOKIE['auth_token'] ?? '';
if ($tokenJwt) {
    $segredo = getenv('JWT_SECRET');
    if ($segredo === false) {
        $segredo = trim(file_get_contents(__DIR__ . '/../../Restritos/Credenciais/Segredo.jwt'));
    }
    $dados = JWTHelper::decode($tokenJwt, $segredo);
    if ($dados) {
        $grupo = strtoupper($dados['grupo'] ?? '');
        if ($grupo === 'PRATA') $stageId = 611115;
        elseif ($grupo === 'OURO' || $grupo === 'DIAMANTE') $stageId = 611116;
    }
}

$tokenPipe = '6741b82d59d8d230f2aacd0b88f1ea99';

try {
    $companyId = null;
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\nToken: $tokenPipe\r\n"
        ]
    ]);
    $respCompany = @file_get_contents("https://api.pipe.run/v1/companies?show=1&sort=ASC&cnpj={$cnpj}", false, $context);
    if ($respCompany !== false) {
        $json = json_decode($respCompany, true);
        $companyId = $json['data'][0]['id'] ?? $json[0]['id'] ?? $json['id'] ?? null;
    }

    $dealData = [
        'pipeline_id' => 90783,
        'stage_id'    => $stageId,
        'title'       => $titulo,
        'created_at'  => $created,
        'tags'        => [['id' => 359705], ['id' => 362614]]
    ];
    if ($companyId) $dealData['company_id'] = $companyId;

    $contextDeal = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nToken: $tokenPipe\r\n",
            'content' => json_encode($dealData)
        ]
    ]);
    $respDeal = file_get_contents('https://api.pipe.run/v1/deals', false, $contextDeal);
    $dealJson = json_decode($respDeal, true);
    $dealId = $dealJson['deal_id'] ?? $dealJson['id'] ?? $dealJson['data']['deal_id'] ?? $dealJson['data']['id'] ?? '';

    echo "✅ Oportunidade $dealId enviada! Em breve nossos Consultores Comerciais entrarão em contato com a proposta.";
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }
    ignore_user_abort(true);

    $linhasNota = [
        "Referência: $referencia",
        "Código: " . ($cdProduto ?: ''),
        "Quantidade: $quantidade",
        "Nome: $nome",
        (strlen($cnpj) === 11 ? 'CPF' : 'CNPJ') . ": $cnpj"
    ];
    if (strlen($cnpj) === 14) $linhasNota[] = "Empresa: $empresa";
    $linhasNota[] = "Email: $email";
    if ($telefone) $linhasNota[] = "Telefone: $telefone";
    if ($observacao) $linhasNota[] = "Observação: $observacao";
    $textoNota = implode('<br>', $linhasNota);

    $contextNote = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Accept: application/json\r\nContent-Type: application/json\r\nToken: $tokenPipe\r\n",
            'content' => json_encode(['deal_id' => $dealId, 'text' => $textoNota])
        ]
    ]);
    @file_get_contents('https://api.pipe.run/v1/notes', false, $contextNote);
    log_event("Cotacao do produto $referencia gerada por $email");

    $params = http_build_query(['produto' => $referencia, 'oportunidade' => $dealId]);
    @file_get_contents("https://" . $_SERVER['HTTP_HOST'] . "/Paginas/AreaCliente/Sessao/Cotacoes/RegistrarCotacao.php", false, stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/x-www-form-urlencoded\r\n','content'=>$params]]));
    
} catch (Exception $e) {
    log_event('Erro em SolicitacaoCotacao: ' . $e->getMessage());
    http_response_code(500);
    echo '⚠️ Erro ao enviar solicitação.';
}
?>