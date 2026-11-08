<?php

declare(strict_types=1);

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Config is missing. Copy config.example.php to config.php and configure the database.');
}
require_once $configFile;

if (!defined('APP_KEY') || strlen((string) APP_KEY) < 32 || str_contains((string) APP_KEY, 'replace-with-')) {
    error_log('Secure Auth Mini: APP_KEY is missing or insecure.');
    http_response_code(503);
    exit('Application configuration is incomplete.');
}

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('SECURE_AUTH_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function db(): mysqli
{
    static $connection = null;
    if ($connection instanceof mysqli) return $connection;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $connection->set_charset('utf8mb4');
    return $connection;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return (string) $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $submitted = (string) ($_POST['csrf'] ?? '');
    $expected = (string) ($_SESSION['csrf'] ?? '');
    if ($expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(419);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash(string $message): void
{
    $_SESSION['flash'] = $message;
    header('Location: /');
    exit;
}

function fingerprint(string $value): string
{
    return hash_hmac('sha256', $value, (string) APP_KEY);
}
