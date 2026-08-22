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

$errors = [];

$form = [
    'name' => '',
    'password' => '',
    'participants_arrival_half' => '0',
    'participants_week1_full' => '0',
    'participants_week1_departure_half' => '0',
    'participants_week1_departure_full' => '0',
    'participants_visitors' => '0',
    'participants_week2_full' => '0',
    'participants_week2_departure_half' => '0',
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
    } elseif ($groupRepository->nameExists($form['name'])) {
        $errors['name'] =
            'Eine Gruppe mit diesem Namen existiert bereits.';
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

        $groupRepository->create([
            'name' => $form['name'],

            'password_encrypted' => $encryptedPassword,

            'participants_arrival_half'
                => (int) $form['participants_arrival_half'],

            'participants_week1_full'
                => (int) $form['participants_week1_full'],

            'participants_week1_departure_half'
                => (int) $form[
                    'participants_week1_departure_half'
                ],

            'participants_week1_departure_full'
                => (int) $form[
                    'participants_week1_departure_full'
                ],

            'participants_visitors'
                => (int) $form['participants_visitors'],

            'participants_week2_full'
                => (int) $form['participants_week2_full'],

            'participants_week2_departure_half'
                => (int) $form[
                    'participants_week2_departure_half'
                ],
        ]);

        header('Location: /admin/groups.php?created=1');
        exit;
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/groups/create.php';


function isValidParticipantCount(string $value): bool
{
    if ($value === '' || !ctype_digit($value)) {
        return false;
    }

    $count = (int) $value;

    return $count >= 0 && $count <= 10000;
}