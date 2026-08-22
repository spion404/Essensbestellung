<?php

declare(strict_types=1);

use App\Database;
use App\Repository\SettingsRepository;

require dirname(__DIR__, 2) . '/config/bootstrap.php';

$pdo = Database::connect();

$settingsRepository = new SettingsRepository($pdo);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campName = trim($_POST['camp_name'] ?? '');

    $budgetFullDay = trim($_POST['budget_full_day'] ?? '');
    $budgetHalfDay = trim($_POST['budget_half_day'] ?? '');
    $budgetVisitorDay = trim($_POST['budget_visitor_day'] ?? '');

    $orderCutoffTime = trim($_POST['order_cutoff_time'] ?? '');

    $arrivalDate = trim($_POST['arrival_date'] ?? '');
    $week1StartDate = trim($_POST['week1_start_date'] ?? '');
    $week1EndDate = trim($_POST['week1_end_date'] ?? '');
    $week1DepartureDate = trim($_POST['week1_departure_date'] ?? '');
    $visitorDate = trim($_POST['visitor_date'] ?? '');
    $week2StartDate = trim($_POST['week2_start_date'] ?? '');
    $week2EndDate = trim($_POST['week2_end_date'] ?? '');
    $week2DepartureDate = trim($_POST['week2_departure_date'] ?? '');

    if ($campName === '') {
        $errors['camp_name'] = 'Bitte einen Lagernamen eingeben.';
    }

    if (!isValidPositiveMoney($budgetFullDay)) {
        $errors['budget_full_day'] =
            'Bitte einen gültigen Betrag eingeben.';
    }

    if (!isValidPositiveMoney($budgetHalfDay)) {
        $errors['budget_half_day'] =
            'Bitte einen gültigen Betrag eingeben.';
    }

    if (!isValidPositiveMoney($budgetVisitorDay)) {
        $errors['budget_visitor_day'] =
            'Bitte einen gültigen Betrag eingeben.';
    }

    if (!isValidTime($orderCutoffTime)) {
        $errors['order_cutoff_time'] =
            'Bitte eine gültige Uhrzeit eingeben.';
    }

    $dates = [
        'arrival_date' => $arrivalDate,
        'week1_start_date' => $week1StartDate,
        'week1_end_date' => $week1EndDate,
        'week1_departure_date' => $week1DepartureDate,
        'visitor_date' => $visitorDate,
        'week2_start_date' => $week2StartDate,
        'week2_end_date' => $week2EndDate,
        'week2_departure_date' => $week2DepartureDate,
    ];

    foreach ($dates as $field => $date) {
        if ($date !== '' && !isValidDate($date)) {
            $errors[$field] = 'Bitte ein gültiges Datum eingeben.';
        }
    }

    if ($errors === []) {
        $settingsRepository->update([
            'camp_name' => $campName,

            'budget_full_day' => $budgetFullDay,
            'budget_half_day' => $budgetHalfDay,
            'budget_visitor_day' => $budgetVisitorDay,

            'order_cutoff_time' => $orderCutoffTime,

            'arrival_date' => emptyToNull($arrivalDate),
            'week1_start_date' => emptyToNull($week1StartDate),
            'week1_end_date' => emptyToNull($week1EndDate),
            'week1_departure_date' => emptyToNull($week1DepartureDate),
            'visitor_date' => emptyToNull($visitorDate),
            'week2_start_date' => emptyToNull($week2StartDate),
            'week2_end_date' => emptyToNull($week2EndDate),
            'week2_departure_date' => emptyToNull($week2DepartureDate),
        ]);

        header('Location: /admin/settings.php?saved=1');
        exit;
    }

    $settings = [
        'camp_name' => $campName,

        'budget_full_day' => $budgetFullDay,
        'budget_half_day' => $budgetHalfDay,
        'budget_visitor_day' => $budgetVisitorDay,

        'order_cutoff_time' => $orderCutoffTime,

        'arrival_date' => $arrivalDate,
        'week1_start_date' => $week1StartDate,
        'week1_end_date' => $week1EndDate,
        'week1_departure_date' => $week1DepartureDate,
        'visitor_date' => $visitorDate,
        'week2_start_date' => $week2StartDate,
        'week2_end_date' => $week2EndDate,
        'week2_departure_date' => $week2DepartureDate,
    ];
} else {
    $settings = $settingsRepository->get();
}

$saved = isset($_GET['saved']);

require dirname(__DIR__, 2)
    . '/templates/admin/settings.php';


function isValidPositiveMoney(string $value): bool
{
    if ($value === '' || !is_numeric($value)) {
        return false;
    }

    return (float) $value >= 0;
}


function isValidTime(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!H:i', $value);

    return $date !== false
        && $date->format('H:i') === $value;
}


function isValidDate(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date !== false
        && $date->format('Y-m-d') === $value;
}


function emptyToNull(string $value): ?string
{
    return $value === '' ? null : $value;
}