<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'register') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || strlen($password) < 12 || strlen($password) > 72) {
                flash('Revisa los datos. Usa una contraseña de 12 a 72 caracteres.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $hash);
            $stmt->execute();
            flash('Cuenta creada. Ya puedes iniciar sesión.');
        }

        if ($action === 'login') {
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || $password === '' || strlen($password) > 72) {
                flash('Correo o contraseña incorrectos.');
            }

            $emailHash = fingerprint('email:' . $email);
            $ipHash = fingerprint('ip:' . (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $connection = db();
            $rate = $connection->prepare('SELECT COUNT(*) FROM login_attempts WHERE attempted_at >= (UTC_TIMESTAMP() - INTERVAL 15 MINUTE) AND (email_hash = ? OR ip_hash = ?)');
            $rate->bind_param('ss', $emailHash, $ipHash);
            $rate->execute();
            $rate->bind_result($attempts);
            $rate->fetch();
            $rate->close();
            if ((int) $attempts >= 5) {
                header('Retry-After: 900');
                http_response_code(429);
                flash('Demasiados intentos. Espera 15 minutos antes de volver a probar.');
            }

            $stmt = $connection->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($id, $name, $storedEmail, $storedHash);
            $found = $stmt->fetch();
            $stmt->close();

            if (!$found || !password_verify($password, (string) $storedHash)) {
                $record = $connection->prepare('INSERT INTO login_attempts (email_hash, ip_hash, attempted_at) VALUES (?, ?, UTC_TIMESTAMP())');
                $record->bind_param('ss', $emailHash, $ipHash);
                $record->execute();
                $record->close();
                flash('Correo o contraseña incorrectos.');
            }

            if (password_needs_rehash((string) $storedHash, PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $connection->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $userId = (int) $id;
                $update->bind_param('si', $newHash, $userId);
                $update->execute();
                $update->close();
            }
            $clear = $connection->prepare('DELETE FROM login_attempts WHERE email_hash = ? OR ip_hash = ?');
            $clear->bind_param('ss', $emailHash, $ipHash);
            $clear->execute();
            $clear->close();

            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) $id, 'name' => (string) $name, 'email' => (string) $storedEmail];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            flash('Sesión iniciada correctamente.');
        }

        if ($action === 'logout') {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]);
            }
            session_destroy();
            header('Location: /');
            exit;
        }
    } catch (mysqli_sql_exception $exception) {
        error_log('Secure Auth Mini database operation failed: ' . $exception->getCode());
        flash($action === 'register' ? 'No se pudo crear la cuenta con esos datos.' : 'Servicio temporalmente no disponible.');
    }

    flash('Solicitud no válida.');
}

$message = (string) ($_SESSION['flash'] ?? '');
unset($_SESSION['flash']);
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Auth Mini</title>
    <meta name="description" content="Demo educativa de autenticación segura en PHP.">
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<main class="shell">
    <header class="brand"><span class="mark">🔐</span><div><p class="eyebrow">PHP · MYSQL · SECURITY</p><h1>Secure Auth Mini</h1></div></header>
    <?php if ($message !== ''): ?><p class="notice" role="status"><?= h($message) ?></p><?php endif; ?>
    <?php if (is_array($user)): ?>
        <section class="card">
            <p class="eyebrow">SESIÓN SEGURA</p>
            <h2>Hola, <?= h((string) $user['name']) ?></h2>
            <p class="muted">Has iniciado sesión como <?= h((string) $user['email']) ?>.</p>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="logout">
                <button class="button secondary" type="submit">Cerrar sesión</button>
            </form>
        </section>
    <?php else: ?>
        <section class="grid">
            <form class="card" method="post" autocomplete="on">
                <p class="eyebrow">VUELVE A TU CUENTA</p><h2>Iniciar sesión</h2>
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="login">
                <label>Correo<input type="email" name="email" autocomplete="username" maxlength="254" required></label>
                <label>Contraseña<input type="password" name="password" autocomplete="current-password" maxlength="72" required></label>
                <button class="button" type="submit">Entrar <span>→</span></button>
            </form>
            <form class="card" method="post" autocomplete="on">
                <p class="eyebrow">EMPIEZA AQUÍ</p><h2>Crear cuenta</h2>
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="register">
                <label>Nombre<input type="text" name="name" autocomplete="name" maxlength="120" required></label>
                <label>Correo<input type="email" name="email" autocomplete="email" maxlength="254" required></label>
                <label>Contraseña<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required></label>
                <p class="hint">Usa al menos 12 caracteres.</p>
                <button class="button secondary" type="submit">Crear cuenta <span>↗</span></button>
            </form>
        </section>
    <?php endif; ?>
    <footer><span>Hecho para aprender seguridad web.</span><span>Passwords hashed · CSRF protected · Rate limited</span></footer>
</main>
</body>
</html>
