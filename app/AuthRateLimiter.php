<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

final class AuthRateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    public function __construct(private mysqli $connection)
    {
    }

    public function isLimited(string $email, string $ip): bool
    {
        $emailHash = $this->fingerprint('email:' . strtolower($email));
        $ipHash = $this->fingerprint('ip:' . $ip);

        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE attempted_at >= (UTC_TIMESTAMP() - INTERVAL 15 MINUTE)
             AND (email_hash = ? OR ip_hash = ?)'
        );
        $statement->bind_param('ss', $emailHash, $ipHash);
        $statement->execute();
        $statement->bind_result($attemptCount);
        $statement->fetch();
        $statement->close();

        return (int) $attemptCount >= self::MAX_ATTEMPTS;
    }

    public function recordFailure(string $email, string $ip): void
    {
        $emailHash = $this->fingerprint('email:' . strtolower($email));
        $ipHash = $this->fingerprint('ip:' . $ip);

        $statement = $this->connection->prepare(
            'INSERT INTO login_attempts (email_hash, ip_hash, attempted_at)
             VALUES (?, ?, UTC_TIMESTAMP())'
        );
        $statement->bind_param('ss', $emailHash, $ipHash);
        $statement->execute();
        $statement->close();
    }

    public function clearFor(string $email, string $ip): void
    {
        $emailHash = $this->fingerprint('email:' . strtolower($email));
        $ipHash = $this->fingerprint('ip:' . $ip);

        $statement = $this->connection->prepare(
            'DELETE FROM login_attempts WHERE email_hash = ? OR ip_hash = ?'
        );
        $statement->bind_param('ss', $emailHash, $ipHash);
        $statement->execute();
        $statement->close();
    }

    private function fingerprint(string $value): string
    {
        return hash_hmac('sha256', $value, (string) APP_KEY);
    }
}
