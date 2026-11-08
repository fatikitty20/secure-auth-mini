<?php

declare(strict_types=1);

require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/AuthRateLimiter.php';

final class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function handleRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        verify_csrf();

        try {
            $this->dispatch((string) ($_POST['action'] ?? ''));
        } catch (LoginRateLimitException $exception) {
            header('Retry-After: 900');
            http_response_code(429);
            flash($exception->getMessage());
        } catch (InvalidCredentialsException | InvalidArgumentException $exception) {
            flash($exception->getMessage());
        } catch (mysqli_sql_exception $exception) {
            error_log('Secure Auth Mini database operation failed: ' . $exception->getCode());
            flash($this->databaseErrorMessage((string) ($_POST['action'] ?? '')));
        } catch (Throwable $exception) {
            error_log('Secure Auth Mini request failed: ' . $exception->getMessage());
            flash('Servicio temporalmente no disponible.');
        }

        flash('Solicitud no válida.');
    }

    private function dispatch(string $action): void
    {
        if ($action === 'register') {
            $message = $this->authService->register(
                trim((string) ($_POST['name'] ?? '')),
                strtolower(trim((string) ($_POST['email'] ?? ''))),
                (string) ($_POST['password'] ?? ''),
            );
            flash($message);
        }

        if ($action === 'login') {
            $message = $this->authService->login(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? ''),
            );
            flash($message);
        }

        if ($action === 'logout') {
            $this->authService->logout();
            header('Location: /', true, 303);
            exit;
        }
    }

    private function databaseErrorMessage(string $action): string
    {
        return $action === 'register'
            ? 'No se pudo crear la cuenta con esos datos.'
            : 'Servicio temporalmente no disponible.';
    }
}
