<?php
function log_event($message) {
    $logDir = dirname(__DIR__) . '/Restritos/logs';
    if (!is_dir($logDir)) {
        if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
            error_log('Failed to create log directory: ' . $logDir);
            return;
        }
    }
    $sanitized = preg_replace('/[A-Fa-f0-9]{64}/', '[TOKEN]', $message);
    $entry = '[' . date('d/m/Y H:i:s') . '] ' . $sanitized . PHP_EOL;
    $logFile = $logDir . '/site.log';
    if (file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX) === false) {
        error_log('Failed to write to log file: ' . $logFile);
        return;
    }
    chmod($logFile, 0600);
    error_log(trim($entry));
}
?>