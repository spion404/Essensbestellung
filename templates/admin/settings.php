<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatMoney = static function (mixed $value): string {
    return number_format(
        (float) $value,
        2,
        '.',
        "'"
    );
};

$formatDate = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '–';
    }

    $date = DateTimeImmutable::createFromFormat(
        'Y-m-d',
        (string) $value
    );

    if ($date === false) {
        return '–';
    }

    return $date->format('d.m.Y');
};

$formatTime = static function (mixed $value): string {
    if ($value === null || $value === '') {
        return '–';
    }

    return substr((string) $value, 0, 5);
};

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Einstellungen – <?= $escape($settings['camp_name']) ?>
    </title>
</head>

<body>

<h1>Einstellungen</h1>

<h2>Allgemein</h2>

<dl>
    <dt>Lagername</dt>
    <dd><?= $escape($settings['camp_name']) ?></dd>

    <dt>Bestellschluss für den Folgetag</dt>
    <dd><?= $escape($formatTime($settings['order_cutoff_time'])) ?> Uhr</dd>
</dl>


<h2>Budget</h2>

<dl>
    <dt>Ganzer Tag</dt>
    <dd>
        CHF <?= $escape($formatMoney($settings['budget_full_day'])) ?>
        pro Person
    </dd>

    <dt>Halber Tag</dt>
    <dd>
        CHF <?= $escape($formatMoney($settings['budget_half_day'])) ?>
        pro Person
    </dd>

    <dt>Besuchstag</dt>
    <dd>
        CHF <?= $escape($formatMoney($settings['budget_visitor_day'])) ?>
        pro zusätzlicher Person
    </dd>
</dl>


<h2>Zeiträume</h2>

<dl>
    <dt>Anreisetag</dt>
    <dd><?= $escape($formatDate($settings['arrival_date'])) ?></dd>

    <dt>Erste Woche</dt>
    <dd>
        <?= $escape($formatDate($settings['week1_start_date'])) ?>
        –
        <?= $escape($formatDate($settings['week1_end_date'])) ?>
    </dd>

    <dt>Abreisetag erste Woche</dt>
    <dd>
        <?= $escape($formatDate($settings['week1_departure_date'])) ?>
    </dd>

    <dt>Besuchstag</dt>
    <dd>
        <?= $escape($formatDate($settings['visitor_date'])) ?>
    </dd>

    <dt>Zweite Woche</dt>
    <dd>
        <?= $escape($formatDate($settings['week2_start_date'])) ?>
        –
        <?= $escape($formatDate($settings['week2_end_date'])) ?>
    </dd>

    <dt>Abreisetag zweite Woche</dt>
    <dd>
        <?= $escape($formatDate($settings['week2_departure_date'])) ?>
    </dd>
</dl>

</body>
</html>