<?php

// Script to safely sync Railway runtime environment variables into .env
$envPath = is_dir('/var/www/html') ? '/var/www/html/.env' : dirname(__DIR__).'/.env';
$examplePath = is_dir('/var/www/html') ? '/var/www/html/.env.example' : dirname(__DIR__).'/.env.example';

if (! file_exists($envPath)) {
    if (file_exists($examplePath)) {
        copy($examplePath, $envPath);
    } else {
        touch($envPath);
    }
}

$isRailway = is_dir('/var/www/html') || getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_STATIC_URL');

$vars = [
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: ($isRailway ? 'mysql.railway.internal' : '127.0.0.1'),
    'DB_PORT' => getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306',
    'DB_DATABASE' => getenv('DB_DATABASE') ?: getenv('MYSQLDATABASE') ?: ($isRailway ? 'railway' : 'krayin'),
    'DB_USERNAME' => getenv('DB_USERNAME') ?: getenv('MYSQLUSER') ?: 'root',
    'DB_PASSWORD' => getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '',
    'DATABASE_URL' => getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: '',
    'APP_KEY' => getenv('APP_KEY') ?: '',
    'APP_URL' => getenv('APP_URL') ?: ($isRailway ? 'https://laravel-crm-production-baa6.up.railway.app' : 'http://localhost:8000'),
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'WHATSAPP_PHONE_NUMBER_ID' => getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '',
    'WHATSAPP_ACCESS_TOKEN' => getenv('WHATSAPP_ACCESS_TOKEN') ?: '',
    'WHATSAPP_VERIFY_TOKEN' => getenv('WHATSAPP_VERIFY_TOKEN') ?: '',
    'WHATSAPP_AUTO_CREATE_LEAD' => 'true',
    'MAIL_RECEIVER_DRIVER' => 'webklex-imap',
    'MAIL_MAILER' => getenv('MAIL_MAILER') ?: 'brevo',
    'BREVO_API_KEY' => getenv('BREVO_API_KEY') ?: (getenv('MAIL_PASSWORD') ?: ''),
    'MAIL_HOST' => getenv('MAIL_HOST') ?: 'smtp-relay.brevo.com',
    'MAIL_PORT' => getenv('MAIL_PORT') ?: '587',
    'MAIL_USERNAME' => getenv('MAIL_USERNAME') ?: '',
    'MAIL_PASSWORD' => getenv('MAIL_PASSWORD') ?: '',
    'MAIL_ENCRYPTION' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'MAIL_FROM_ADDRESS' => getenv('MAIL_FROM_ADDRESS') ?: '',
    'MAIL_FROM_NAME' => getenv('MAIL_FROM_NAME') ?: 'AUURA CRM',
];

$lines = file_exists($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$existing = [];

foreach ($lines as $line) {
    $trimmed = trim($line);
    if (str_starts_with($trimmed, '#') || empty($trimmed)) {
        continue;
    }
    if (str_contains($line, '=')) {
        [$k, $v] = explode('=', $line, 2);
        $existing[trim($k)] = trim($v);
    }
}

foreach ($vars as $k => $v) {
    if ($v !== '' && $v !== false && $v !== null) {
        $existing[$k] = $v;
    }
}

$output = [];
foreach ($existing as $k => $v) {
    // If value contains spaces, wrap in quotes
    if (str_contains($v, ' ') && ! str_starts_with($v, '"') && ! str_starts_with($v, "'")) {
        $v = '"'.$v.'"';
    }
    $output[] = "{$k}={$v}";
}

file_put_contents($envPath, implode("\n", $output)."\n");
echo "Environment synchronized to .env successfully.\n";
