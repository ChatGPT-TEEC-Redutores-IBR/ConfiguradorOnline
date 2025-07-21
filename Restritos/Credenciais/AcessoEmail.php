<?php
$smtpHost = getenv('SMTP_HOST') ?: 'smtp.office365.com';
$smtpUser = getenv('SMTP_USER') ?: 'no-reply@redutoresibr.com.br';
$smtpPass = getenv('SMTP_PASS') ?: 'Ibr@2025';
$smtpSecure = getenv('SMTP_SECURE') ?: 'tls';
$smtpPort = getenv('SMTP_PORT') ?: 587;
?>