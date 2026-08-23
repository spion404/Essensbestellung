<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\SettingsRepository;
use App\Service\EncryptionService;
use App\Service\GroupAuthService;
use App\Service\GroupSessionService;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$groupSession = new GroupSessionService();

$pdo = Database::connect();

$groupRepository = new GroupRepository($pdo);
$settingsRepository = new SettingsRepository($pdo);

$encryptionService = new EncryptionService(
    $_ENV['APP_KEY'] ?? ''
);

$groupAuthService = new GroupAuthService(
    $groupRepository,
    $encryptionService
);

$currentGroupId = $groupSession->groupId();

if ($currentGroupId !== null) {
    if (
        $groupRepository->findById(
            $currentGroupId
        ) !== null
    ) {
        header('Location: /group/');
        exit;
    }

    $groupSession->logout();

    header('Location: /group/login.php');
    exit;
}

$settings = $settingsRepository->get();
$groups = $groupRepository->findAll();

$error = null;
$selectedGroupId = '';
$loggedOut =
    (string) ($_GET['logged_out'] ?? '') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedGroupId = trim(
        (string) ($_POST['group_id'] ?? '')
    );

    $password = (string) (
        $_POST['password']
        ?? ''
    );

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

        $error =
            'Die Sitzung ist abgelaufen. '
            . 'Bitte versuche es erneut.';
    } elseif (
        $selectedGroupId === ''
        || !ctype_digit($selectedGroupId)
        || (int) $selectedGroupId < 1
    ) {
        $error =
            'Bitte eine Gruppe auswählen.';
    } else {
        $group = $groupAuthService->authenticate(
            (int) $selectedGroupId,
            $password
        );

        if ($group === null) {
            $error =
                'Gruppe oder Passwort ist ungültig.';
        } else {
            $groupSession->login(
                (int) $group['id']
            );

            header('Location: /group/');
            exit;
        }
    }
}

$csrfToken = $groupSession->csrfToken();

require dirname(__DIR__, 2)
    . '/templates/group/login.php';