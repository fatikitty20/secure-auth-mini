<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/AuthRateLimiter.php';

final class AuthService
{
    public function __construct(
        private mysqli $connection,
        private AuthRateLimiter $rateLimiter,
    ) {
    }

    public function register(string $name, string $email, string $password): string
    {
        $this->validateRegistration($name, $email, $password);

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Password hashing failed.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
        );
        $statement->bind_param('sss', $name, $email, $passwordHash);
        $statement->execute();
        $statement->close();

        return 'Cuenta creada. Ya puedes iniciar sesión.';
    }

    public function login(string $email, string $password): string
    {
        $this->validateLogin($email, $password);
        $email = strtolower(trim($email));

        if ($this->rateLimiter->isLimited($email, $this->clientIp())) {
            throw new LoginRateLimitException('Demasiados intentos. Espera 15 minutos.');
        }

        $account = $this->findAccountByEmail($email);
        if ($account === null || !password_verify($password, $account['password_hash'])) {
            $this->rateLimiter->recordFailure($email, $this->clientIp());
            throw new InvalidCredentialsException('Correo o contraseña incorrectos.');
        }

        $this->rehashPasswordIfNeeded($account, $password);
        $this->rateLimiter->clearFor($email, $this->clientIp());
        $this->startAuthenticatedSession($account);

        return 'Sesión iniciada correctamente.';
    }

    public function logout(): void
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

    private function validateRegistration(string $name, string $email, string $password): void
    {
        $validName = $name !== '' && strlen($name) <= 120;
        $validEmail = filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && strlen($email) <= 254;
        $validPassword = strlen($password) >= 12 && strlen($password) <= 72;

        if (!$validName || !$validEmail || !$validPassword) {
            throw new InvalidArgumentException(
                'Revisa tus datos. La contraseña debe tener entre 12 y 72 caracteres.'
            );
        }
    }

    private function validateLogin(string $email, string $password): void
    {
        $validEmail = filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false
            && strlen($email) <= 254;
        $validPassword = $password !== '' && strlen($password) <= 72;

        if (!$validEmail || !$validPassword) {
            throw new InvalidCredentialsException('Correo o contraseña incorrectos.');
        }
    }

    private function findAccountByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1'
        );
        $statement->bind_param('s', $email);
        $statement->execute();
        $statement->bind_result($id, $name, $storedEmail, $passwordHash);

        $found = $statement->fetch();
        $statement->close();

        if (!$found) {
            return null;
        }

        return [
            'id' => (int) $id,
            'name' => (string) $name,
            'email' => (string) $storedEmail,
            'password_hash' => (string) $passwordHash,
        ];
    }

    private function rehashPasswordIfNeeded(array $account, string $password): void
    {
        if (!password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
            return;
        }

        $newHash = password_hash($password, PASSWORD_DEFAULT);
        if ($newHash === false) {
            throw new RuntimeException('Password rehash failed.');
        }

        $statement = $this->connection->prepare(
            'UPDATE users SET password_hash = ? WHERE id = ?'
        );
        $statement->bind_param('si', $newHash, $account['id']);
        $statement->execute();
        $statement->close();
    }

    private function startAuthenticatedSession(array $account): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $account['id'],
            'name' => $account['name'],
            'email' => $account['email'],
        ];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $_SESSION['authenticated_at'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['session_rotated_at'] = time();
    }

    private function clientIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}

final class InvalidCredentialsException extends RuntimeException
{
}

final class LoginRateLimitException extends RuntimeException
{
}
