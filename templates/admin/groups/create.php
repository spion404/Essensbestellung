<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$fieldError = static function (
    string $field
) use ($errors, $escape): string {
    if (!isset($errors[$field])) {
        return '';
    }

    return '<p>'
        . $escape($errors[$field])
        . '</p>';
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

    <title>Neue Gruppe</title>
</head>

<body>

<p>
    <a href="/admin/groups.php">
        ← Zurück zu den Gruppen
    </a>
</p>

<h1>Neue Gruppe</h1>

<form method="post">

    <h2>Allgemein</h2>

    <p>
        <label for="name">
            Gruppenname
        </label>
        <br>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="150"
            value="<?= $escape($form['name']) ?>"
            required
        >

        <?= $fieldError('name') ?>
    </p>


    <p>
        <label for="password">
            Gruppenpasswort
        </label>
        <br>

        <input
            type="text"
            id="password"
            name="password"
            value="<?= $escape($form['password']) ?>"
            autocomplete="off"
            required
        >

        <?= $fieldError('password') ?>
    </p>


    <h2>Teilnehmerzahlen</h2>


    <p>
        <label for="participants_arrival_half">
            Anreisetag – halber Tag
        </label>
        <br>

        <input
            type="number"
            id="participants_arrival_half"
            name="participants_arrival_half"
            min="0"
            step="1"
            value="<?= $escape(
                $form['participants_arrival_half']
            ) ?>"
            required
        >

        <?= $fieldError('participants_arrival_half') ?>
    </p>


    <p>
        <label for="participants_week1_full">
            Erste Woche – ganzer Tag
        </label>
        <br>

        <input
            type="number"
            id="participants_week1_full"
            name="participants_week1_full"
            min="0"
            step="1"
            value="<?= $escape(
                $form['participants_week1_full']
            ) ?>"
            required
        >

        <?= $fieldError('participants_week1_full') ?>
    </p>


    <p>
        <label for="participants_week1_departure_half">
            Abreisetag erste Woche – halber Tag
        </label>
        <br>

        <input
            type="number"
            id="participants_week1_departure_half"
            name="participants_week1_departure_half"
            min="0"
            step="1"
            value="<?= $escape(
                $form['participants_week1_departure_half']
            ) ?>"
            required
        >

        <?= $fieldError(
            'participants_week1_departure_half'
        ) ?>
    </p>


    <p>
        <label for="participants_week1_departure_full">
            Abreisetag erste Woche – ganzer Tag
        </label>
        <br>

        <input
            type="number"
            id="participants_week1_departure_full"
            name="participants_week1_departure_full"
            min="0"
            step="1"
            value="<?= $escape(
                $form['participants_week1_departure_full']
            ) ?>"
            required
        >

        <?= $fieldError(
            'participants_week1_departure_full'
        ) ?>
    </p>


    <p>
        <label for="participants_visitors">
            Zusätzliche Personen am Besuchstag
        </label>
        <br>

        <input
            type="number"
            id="participants_visitors"
            name="participants_visitors"
            min="0"
            step="1"
            value="<?= $escape(
                $form['participants_visitors']
            ) ?>"
            required
        >

        <?= $fieldError('participants_visitors') ?>
    </p>


    <p>
        <label for="participants_week2_full">
            Zweite Woche – ganzer Tag
        </label>
        <br>

        <input
            type="number"
            id="participants_week2_full"
            name="participants_week2_full"
            min="0"
            step="1"
            value="<?= $escape(
                $form['participants_week2_full']
            ) ?>"
            required
        >

        <?= $fieldError('participants_week2_full') ?>
    </p>


    <p>
        <label for="participants_week2_departure_half">
            Abreisetag zweite Woche – halber Tag
        </label>
        <br>

        <input
            type="number"
            id="participants_week2_departure_half"
            name="participants_week2_departure_half"
            min="0"
            step="1"
            value="<?= $escape(
                $form['participants_week2_departure_half']
            ) ?>"
            required
        >

        <?= $fieldError(
            'participants_week2_departure_half'
        ) ?>
    </p>


    <p>
        <button type="submit">
            Gruppe erstellen
        </button>
    </p>

</form>

</body>
</html>