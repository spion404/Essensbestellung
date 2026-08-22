<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Service\EncryptionService;

require dirname(__DIR__, 3) . '/config/bootstrap.php';

$pdo = Database::connect();

$groupRepository = new GroupRepository($pdo);

$encryptionService = new EncryptionService(
    $_ENV['APP_KEY'] ?? ''
);

$idValue = (string) ($_GET['id'] ?? '');

if (
    $idValue === ''
    || !ctype_digit($idValue)
    || (int) $idValue < 1
) {
    http_response_code(404);
    exit('Gruppe nicht gefunden.');
}

$groupId = (int) $idValue;

$group = $groupRepository->findById($groupId);

if ($group === null) {
    http_response_code(404);
    exit('Gruppe nicht gefunden.');
}

$errors = [];
$warnings = [];

try {
    $password = $encryptionService->decrypt(
        $group['password_encrypted']
    );
} catch (Throwable $exception) {
    $password = '';

    $warnings[] =
        'Das gespeicherte Gruppenpasswort konnte nicht '
        . 'entschlüsselt werden. Du kannst ein neues Passwort '
        . 'eintragen und speichern.';
}

$form = [
    'name' => (string) $group['name'],
    'password' => $password,

    'participants_arrival_half'
        => (string) $group['participants_arrival_half'],

    'participants_week1_full'
        => (string) $group['participants_week1_full'],

    'participants_week1_departure_half'
        => (string) $group[
            'participants_week1_departure_half'
        ],

    'participants_week1_departure_full'
        => (string) $group[
            'participants_week1_departure_full'
        ],

    'participants_visitors'
        => (string) $group['participants_visitors'],

    'participants_week2_full'
        => (string) $group['participants_week2_full'],

    'participants_week2_departure_half'
        => (string) $group[
            'participants_week2_departure_half'
        ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $field) {
        $form[$field] = trim(
            (string) ($_POST[$field] ?? '')
        );
    }

    if ($form['name'] === '') {
        $errors['name'] =
            'Bitte einen Gruppennamen eingeben.';
    } elseif (mb_strlen($form['name']) > 150) {
        $errors['name'] =
            'Der Gruppenname ist zu lang.';
    } elseif (
        $groupRepository->nameExistsForOtherGroup(
            $form['name'],
            $groupId
        )
    ) {
        $errors['name'] =
            'Eine andere Gruppe mit diesem Namen existiert bereits.';
    }

    if ($form['password'] === '') {
        $errors['password'] =
            'Bitte ein Gruppenpasswort eingeben.';
    }

    $participantFields = [
        'participants_arrival_half',
        'participants_week1_full',
        'participants_week1_departure_half',
        'participants_week1_departure_full',
        'participants_visitors',
        'participants_week2_full',
        'participants_week2_departure_half',
    ];

    foreach ($participantFields as $field) {
        if (!isValidParticipantCount($form[$field])) {
            $errors[$field] =
                'Bitte eine gültige Teilnehmerzahl eingeben.';
        }
    }

    if ($errors === []) {
        $encryptedPassword = $encryptionService->encrypt(
            $form['password']
        );

        $groupRepository->update(
            $groupId,
            [
                'name' => $form['name'],

                'password_encrypted' => $encryptedPassword,

                'participants_arrival_half'
                    => (int) $form[
                        'participants_arrival_half'
                    ],

                'participants_week1_full'
                    => (int) $form[
                        'participants_week1_full'
                    ],

                'participants_week1_departure_half'
                    => (int) $form[
                        'participants_week1_departure_half'
                    ],

                'participants_week1_departure_full'
                    => (int) $form[
                        'participants_week1_departure_full'
                    ],

                'participants_visitors'
                    => (int) $form[
                        'participants_visitors'
                    ],

                'participants_week2_full'
                    => (int) $form[
                        'participants_week2_full'
                    ],

                'participants_week2_departure_half'
                    => (int) $form[
                        'participants_week2_departure_half'
                    ],
            ]
        );

        header(
            'Location: /admin/groups.php?updated=1'
        );
        exit;
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/groups/edit.php';


function isValidParticipantCount(string $value): bool
{
    if ($value === '' || !ctype_digit($value)) {
        return false;
    }

    $count = (int) $value;

    return $count >= 0 && $count <= 10000;
}