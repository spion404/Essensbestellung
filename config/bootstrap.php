<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(
    dirname(__DIR__)
);

$dotenv->safeLoad();

$scriptName = str_replace(
    '\\',
    '/',
    (string) ($_SERVER['SCRIPT_NAME'] ?? '')
);

$isAdminRequest = str_contains(
    $scriptName,
    '/admin/'
);

$isAdminLoginRequest = str_ends_with(
    $scriptName,
    '/admin/login.php'
);

if ($isAdminRequest && !$isAdminLoginRequest) {
    $adminSession =
        new App\Service\AdminSessionService();

    if (!$adminSession->isAuthenticated()) {
        header('Location: /admin/login.php');
        exit;
    }
}