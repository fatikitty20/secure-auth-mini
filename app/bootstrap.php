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

$isHttps = isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== ''
    && strtolower((string) $_SERVER['HTTPS']) !== 'off';

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

const SESSION_IDLE_TIMEOUT = 1800;
const SESSION_ABSOLUTE_TIMEOUT = 28800;
const SESSION_ROTATION_INTERVAL = 300;

function db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $connection->set_charset('utf8mb4');

    return $connection;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $submittedToken = (string) ($_POST['csrf'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf'] ?? '');

    if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(419);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    if (!is_array($user)) {
        return null;
    }

    $now = time();
    $authenticatedAt = (int) ($_SESSION['authenticated_at'] ?? 0);
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
    $isIdle = $lastActivity === 0 || ($now - $lastActivity) > SESSION_IDLE_TIMEOUT;
    $isExpired = $authenticatedAt === 0 || ($now - $authenticatedAt) > SESSION_ABSOLUTE_TIMEOUT;

    if ($isIdle || $isExpired) {
        clear_session();
        return null;
    }

    $_SESSION['last_activity'] = $now;
    $lastRotation = (int) ($_SESSION['session_rotated_at'] ?? $now);
    if (($now - $lastRotation) > SESSION_ROTATION_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['session_rotated_at'] = $now;
    }

    return $user;
}

function clear_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash(string $message): never
{
    $_SESSION['flash'] = $message;
    header('Location: /', true, 303);
    exit;
}
