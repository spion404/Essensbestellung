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

    return '<p class="field-error">'
        . $escape($errors[$field])
        . '</p>';
};

$adminPageTitle = 'Einstellungen';
$adminActiveSection = 'settings';

require __DIR__ . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Konfiguration
        </p>

        <h1>Einstellungen</h1>

        <p class="lead">
            Lagername, Bestellschluss, Budgetansätze und
            Lagerzeiträume konfigurieren.
        </p>

    </div>

</div>

<?php if ($saved): ?>

    <div class="alert alert--success">
        Einstellungen wurden gespeichert.
    </div>

<?php endif; ?>

<form
    class="admin-form"
    method="post"
>

    <section class="form-section">

        <div class="form-section__header">

            <h2>Allgemein</h2>

        </div>

        <div class="form-grid">

            <div class="field">

                <label for="camp_name">
                    Lagername
                </label>

                <input
                    type="text"
                    id="camp_name"
                    name="camp_name"
                    value="<?= $escape(
                        $settings['camp_name']
                    ) ?>"
                    required
                >

                <?= $fieldError(
                    'camp_name',
                    $errors
                ) ?>

            </div>

            <div class="field">

                <label for="order_cutoff_time">
                    Bestellschluss für den Folgetag
                </label>

                <input
                    type="time"
                    id="order_cutoff_time"
                    name="order_cutoff_time"
                    value="<?= $escape(
                        substr(
                            (string) $settings[
                                'order_cutoff_time'
                            ],
                            0,
                            5
                        )
                    ) ?>"
                    required
                >

                <p class="field-help">
                    Eine Lieferung für Dienstag schliesst
                    beispielsweise am Montag zu dieser Uhrzeit.
                </p>

                <?= $fieldError(
                    'order_cutoff_time',
                    $errors
                ) ?>

            </div>

        </div>

    </section>

    <section class="form-section">

        <div class="form-section__header">

            <h2>Budgetansätze</h2>

            <p class="small-muted">
                Beträge pro Person und Liefertag.
            </p>

        </div>

        <div class="form-grid form-grid--three">

            <?php
            $budgetFields = [
                'budget_full_day' => [
                    'Ganzer Tag',
                    'Betrag pro Person für einen ganzen Tag',
                ],
                'budget_half_day' => [
                    'Halber Tag',
                    'Betrag pro Person für einen halben Tag',
                ],
                'budget_visitor_day' => [
                    'Besuchstag',
                    'Betrag pro zusätzlicher Person am Besuchstag',
                ],
            ];
            ?>

            <?php foreach (
                $budgetFields
                as $field => [$label, $help]
            ): ?>

                <div class="field">

                    <label for="<?= $escape($field) ?>">
                        <?= $escape($label) ?>
                    </label>

                    <div class="input-prefix">

                        <span class="input-prefix__label">
                            CHF
                        </span>

                        <input
                            type="number"
                            id="<?= $escape($field) ?>"
                            name="<?= $escape($field) ?>"
                            min="0"
                            step="0.01"
                            value="<?= $escape(
                                $settings[$field]
                            ) ?>"
                            required
                        >

                    </div>

                    <p class="field-help">
                        <?= $escape($help) ?>
                    </p>

                    <?= $fieldError(
                        $field,
                        $errors
                    ) ?>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

    <section class="form-section">

        <div class="form-section__header">

            <h2>Lagerzeiträume</h2>

            <p class="small-muted">
                Leere Felder sind möglich, solange der betreffende
                Lagerabschnitt nicht verwendet wird.
            </p>

        </div>

        <?php
        $dateFields = [
            'arrival_date' => 'Anreisetag',
            'week1_start_date' => 'Beginn erste Woche',
            'week1_end_date' => 'Ende erste Woche',
            'week1_departure_date' => 'Abreisetag erste Woche',
            'visitor_date' => 'Besuchstag',
            'week2_start_date' => 'Beginn zweite Woche',
            'week2_end_date' => 'Ende zweite Woche',
            'week2_departure_date' => 'Abreisetag zweite Woche',
        ];
        ?>

        <div class="form-grid">

            <?php foreach (
                $dateFields
                as $field => $label
            ): ?>

                <div class="field">

                    <label for="<?= $escape($field) ?>">
                        <?= $escape($label) ?>
                    </label>

                    <input
                        type="date"
                        id="<?= $escape($field) ?>"
                        name="<?= $escape($field) ?>"
                        value="<?= $escape(
                            $settings[$field]
                        ) ?>"
                    >

                    <?= $fieldError(
                        $field,
                        $errors
                    ) ?>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

    <div class="form-actions">

        <button type="submit">
            Einstellungen speichern
        </button>

    </div>

</form>

<?php
require __DIR__ . '/partials/layout_end.php';
