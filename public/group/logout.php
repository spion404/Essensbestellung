<?php

declare(strict_types=1);

use App\Service\GroupSessionService;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$groupSession = new GroupSessionService();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /group/');
    exit;
}

$csrfToken = (string) (
    $_POST['csrf_token']
    ?? ''
);

if (
    !$groupSession->verifyCsrfToken(
        $csrfToken
    )
) {
    http_response_code(400);
    exit('Ungültige Anfrage.');
}

$groupSession->logout();

header(
    'Location: /group/login.php?logged_out=1'
);
exit;