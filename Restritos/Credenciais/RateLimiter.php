<?php
function check_rate_limit(string $context, int $maxAttempts = 5, int $window = 60): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $dir = dirname(__DIR__) . '/LimitesSolicitacoes';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
        if (!file_exists($dir . '/web.config')) {
            file_put_contents($dir . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
        }
    }
    $hash = md5($context . '-' . $ip);
    $file = $dir . '/' . $hash . '.json';
    $now = time();
    if (file_exists($file)) {
          $data = json_decode(file_get_contents($file), true);
        if (!is_array($data) || !isset($data['start'])) {
            $data = ['count' => 1, 'start' => $now];
        } elseif ($now - $data['start'] > $window) {
            $data = ['count' => 1, 'start' => $now];
        } else {
            $data['count'] = ($data['count'] ?? 0) + 1;
        }
    } else {
        $data = ['count' => 1, 'start' => $now];
    }
    file_put_contents($file, json_encode($data));
    chmod($file, 0600);
    if ($data['count'] > $maxAttempts) {
        if (function_exists('log_event')) {
            log_event("Rate limit excedido para $ip no contexto $context");
        }
        return false;
    }
    return true;
}
?>