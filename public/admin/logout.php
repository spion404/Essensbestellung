<?php

declare(strict_types=1);

use App\Service\AdminSessionService;

require dirname(__DIR__, 2)
    . '/config/bootstrap.php';

$adminSession =
    new AdminSessionService();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/orders.php');
    exit;
}

$csrfToken = (string) (
    $_POST['csrf_token']
    ?? ''
);

if (
    !$adminSession->verifyCsrfToken(
        $csrfToken
    )
) {
    http_response_code(400);
    exit('Ungültige Anfrage.');
}

$adminSession->logout();

header('Location: /admin/login.php');
exit;