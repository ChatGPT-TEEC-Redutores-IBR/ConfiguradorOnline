<?php
function get_password_timestamp($email) {
    $baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
    $dir = $baseDir . '/Restritos/Credenciais/PasswordDates';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
        file_put_contents($dir . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
    }
    $file = $dir . '/' . md5(strtolower(trim($email))) . '.json';
    if (!file_exists($file)) return 0;
    $data = json_decode(file_get_contents($file), true);
    return (int)($data['dtSenha'] ?? 0);
}

function set_password_timestamp($email, $timestamp = null) {
    $timestamp = $timestamp ?? time();
    $baseDir = $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__, 2);
    $dir = $baseDir . '/Restritos/Credenciais/PasswordDates';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
        file_put_contents($dir . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
    }
    $file = $dir . '/' . md5(strtolower(trim($email))) . '.json';
    file_put_contents($file, json_encode(['dtSenha' => $timestamp]));
    chmod($file, 0600);
}

function password_expired($email, $dias = 180) {
    $ts = get_password_timestamp($email);
    return $ts && time() - $ts > $dias * 86400;
}
?>