<?php
function blacklist_token(string $token): void {
    $dir = __DIR__ . '/TokensInvalidos';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
        if (!file_exists($dir . '/web.config')) {
            file_put_contents($dir . '/web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
        }
    }
    $file = $dir . '/' . hash('sha256', $token) . '.blk';
    file_put_contents($file, 'revogado');
    chmod($file, 0600);
}

function is_token_blacklisted(string $token): bool {
    $dir = __DIR__ . '/TokensInvalidos';
    $file = $dir . '/' . hash('sha256', $token) . '.blk';
    return file_exists($file);
}
?>