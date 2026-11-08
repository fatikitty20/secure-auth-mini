<?php
/** @var string $message */
/** @var array|null $user */
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
    <header class="brand">
        <span class="mark" aria-hidden="true">🔐</span>
        <div>
            <p class="eyebrow">PHP · MYSQL · SECURITY</p>
            <h1>Secure Auth Mini</h1>
        </div>
    </header>

    <?php if ($message !== ''): ?>
        <p class="notice" role="status"><?= h($message) ?></p>
    <?php endif; ?>

    <?php if ($user !== null): ?>
        <?php require __DIR__ . '/authenticated.php'; ?>
    <?php else: ?>
        <?php require __DIR__ . '/forms.php'; ?>
    <?php endif; ?>

    <footer>
        <span>Hecho para aprender seguridad web.</span>
        <span>Passwords hashed · CSRF protected · Rate limited</span>
    </footer>
</main>
</body>
</html>
