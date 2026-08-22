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
    string $field,
    array $errors
) use ($escape): string {
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

    <title>
        Einstellungen –
        <?= $escape($settings['camp_name']) ?>
    </title>
</head>

<body>

<h1>Einstellungen</h1>

<?php if ($saved): ?>
    <p>
        Einstellungen wurden gespeichert.
    </p>
<?php endif; ?>

<form method="post">

    <h2>Allgemein</h2>

    <p>
        <label for="camp_name">
            Lagername
        </label>
        <br>

        <input
            type="text"
            id="camp_name"
            name="camp_name"
            value="<?= $escape($settings['camp_name']) ?>"
            required
        >

        <?= $fieldError('camp_name', $errors) ?>
    </p>


    <p>
        <label for="order_cutoff_time">
            Bestellschluss für den Folgetag
        </label>
        <br>

        <input
            type="time"
            id="order_cutoff_time"
            name="order_cutoff_time"
            value="<?= $escape(
                substr(
                    (string) $settings['order_cutoff_time'],
                    0,
                    5
                )
            ) ?>"
            required
        >

        <?= $fieldError('order_cutoff_time', $errors) ?>
    </p>


    <h2>Budget</h2>

    <p>
        <label for="budget_full_day">
            Betrag pro Person – ganzer Tag
        </label>
        <br>

        CHF

        <input
            type="number"
            id="budget_full_day"
            name="budget_full_day"
            min="0"
            step="0.01"
            value="<?= $escape($settings['budget_full_day']) ?>"
            required
        >

        <?= $fieldError('budget_full_day', $errors) ?>
    </p>


    <p>
        <label for="budget_half_day">
            Betrag pro Person – halber Tag
        </label>
        <br>

        CHF

        <input
            type="number"
            id="budget_half_day"
            name="budget_half_day"
            min="0"
            step="0.01"
            value="<?= $escape($settings['budget_half_day']) ?>"
            required
        >

        <?= $fieldError('budget_half_day', $errors) ?>
    </p>


    <p>
        <label for="budget_visitor_day">
            Betrag pro zusätzliche Person – Besuchstag
        </label>
        <br>

        CHF

        <input
            type="number"
            id="budget_visitor_day"
            name="budget_visitor_day"
            min="0"
            step="0.01"
            value="<?= $escape($settings['budget_visitor_day']) ?>"
            required
        >

        <?= $fieldError('budget_visitor_day', $errors) ?>
    </p>


    <h2>Zeiträume</h2>


    <p>
        <label for="arrival_date">
            Anreisetag
        </label>
        <br>

        <input
            type="date"
            id="arrival_date"
            name="arrival_date"
            value="<?= $escape($settings['arrival_date']) ?>"
        >

        <?= $fieldError('arrival_date', $errors) ?>
    </p>


    <p>
        <label for="week1_start_date">
            Beginn erste Woche
        </label>
        <br>

        <input
            type="date"
            id="week1_start_date"
            name="week1_start_date"
            value="<?= $escape($settings['week1_start_date']) ?>"
        >

        <?= $fieldError('week1_start_date', $errors) ?>
    </p>


    <p>
        <label for="week1_end_date">
            Ende erste Woche
        </label>
        <br>

        <input
            type="date"
            id="week1_end_date"
            name="week1_end_date"
            value="<?= $escape($settings['week1_end_date']) ?>"
        >

        <?= $fieldError('week1_end_date', $errors) ?>
    </p>


    <p>
        <label for="week1_departure_date">
            Abreisetag erste Woche
        </label>
        <br>

        <input
            type="date"
            id="week1_departure_date"
            name="week1_departure_date"
            value="<?= $escape(
                $settings['week1_departure_date']
            ) ?>"
        >

        <?= $fieldError('week1_departure_date', $errors) ?>
    </p>


    <p>
        <label for="visitor_date">
            Besuchstag
        </label>
        <br>

        <input
            type="date"
            id="visitor_date"
            name="visitor_date"
            value="<?= $escape($settings['visitor_date']) ?>"
        >

        <?= $fieldError('visitor_date', $errors) ?>
    </p>


    <p>
        <label for="week2_start_date">
            Beginn zweite Woche
        </label>
        <br>

        <input
            type="date"
            id="week2_start_date"
            name="week2_start_date"
            value="<?= $escape($settings['week2_start_date']) ?>"
        >

        <?= $fieldError('week2_start_date', $errors) ?>
    </p>


    <p>
        <label for="week2_end_date">
            Ende zweite Woche
        </label>
        <br>

        <input
            type="date"
            id="week2_end_date"
            name="week2_end_date"
            value="<?= $escape($settings['week2_end_date']) ?>"
        >

        <?= $fieldError('week2_end_date', $errors) ?>
    </p>


    <p>
        <label for="week2_departure_date">
            Abreisetag zweite Woche
        </label>
        <br>

        <input
            type="date"
            id="week2_departure_date"
            name="week2_departure_date"
            value="<?= $escape(
                $settings['week2_departure_date']
            ) ?>"
        >

        <?= $fieldError('week2_departure_date', $errors) ?>
    </p>


    <p>
        <button type="submit">
            Einstellungen speichern
        </button>
    </p>

</form>

</body>
</html>