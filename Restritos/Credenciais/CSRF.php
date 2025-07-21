<?php
function csrf_token() {
    $token = $_COOKIE['csrf_token'] ?? '';
    if (!preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + 3600;
        $secure =
            (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
                strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false);
                        if (PHP_VERSION_ID >= 70300) {
            setcookie('csrf_token', $token, [
                'expires' => $expires,
                'path' => '/',
                'secure' => $secure,
                'httponly' => false,
                'samesite' => 'Strict'
            ]);
        } else {
            setcookie('csrf_token', $token, $expires, '/; samesite=Strict', '', $secure, false);
        }
    }
    return $token;
}

function require_valid_csrf_token() {
    $cookie = $_COOKIE['csrf_token'] ?? '';
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $post = $_POST['csrf_token'] ?? '';
    $token = $post ?: $header;
    if (!preg_match('/^[A-Fa-f0-9]{64}$/', $cookie) || !hash_equals($cookie, $token)) {
        if (function_exists('log_event')) {
            log_event('CSRF token invalido. Cookie: ' . $cookie . ' Cabecalho: ' . $token);
        }
        http_response_code(403);
        echo json_encode(['sucesso' => false, 'mensagem' => '⚠️ Token CSRF Inválido.']);
        exit;
    }
}
?>