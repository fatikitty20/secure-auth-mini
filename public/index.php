<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/AuthRateLimiter.php';
require_once dirname(__DIR__) . '/app/AuthService.php';
require_once dirname(__DIR__) . '/app/AuthController.php';

$authService = new AuthService(db(), new AuthRateLimiter(db()));
$authController = new AuthController($authService);
$authController->handleRequest();

$message = (string) ($_SESSION['flash'] ?? '');
unset($_SESSION['flash']);
$user = current_user();

require dirname(__DIR__) . '/app/views/auth.php';
