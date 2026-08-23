<?php

declare(strict_types=1);

use App\Service\AdminAuthService;
use App\Service\AdminSessionService;

require dirname(__DIR__, 2)
    . '/config/bootstrap.php';

$adminSession = new AdminSessionService();

if ($adminSession->isAuthenticated()) {
    header('Location: /admin/orders.php');
    exit;
}

$adminAuthService = new AdminAuthService(
    (string) (
        $_ENV['ADMIN_PASSWORD_HASH']
        ?? ''
    )
);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) (
        $_POST['csrf_token']
        ?? ''
    );

    $password = (string) (
        $_POST['password']
        ?? ''
    );

    if (
        !$adminSession->verifyCsrfToken(
            $csrfToken
        )
    ) {
        http_response_code(400);

        $error =
            'Die Sitzung ist abgelaufen. '
            . 'Bitte versuche es erneut.';
    } else {
        try {
            if (
                !$adminAuthService->authenticate(
                    $password
                )
            ) {
                $error =
                    'Das Admin-Passwort ist ungültig.';
            } else {
                $adminSession->login();

                header(
                    'Location: /admin/orders.php'
                );
                exit;
            }
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}

$csrfToken =
    $adminSession->csrfToken();

require dirname(__DIR__, 2)
    . '/templates/admin/login.php';