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

    return '<p class="field-error">'
        . $escape($errors[$field])
        . '</p>';
};

$adminPageTitle = 'Neue Gruppe';
$adminActiveSection = 'groups';

require dirname(__DIR__) . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Gruppen
        </p>

        <h1>Neue Gruppe</h1>

        <p class="lead">
            Zugangsdaten und Teilnehmerzahlen für eine neue
            Lagergruppe erfassen.
        </p>

    </div>

    <a
        class="button button--secondary"
        href="/admin/groups.php"
    >
        Zurück zu den Gruppen
    </a>

</div>

<form
    class="admin-form"
    method="post"
>

    <section class="form-section">

        <div class="form-section__header">
            <h2>Allgemein</h2>
            <p class="small-muted">
                Name und Passwort für den Zugang der Gruppe.
            </p>
        </div>

        <div class="form-grid">

            <div class="field">

                <label for="name">
                    Gruppenname
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    value="<?= $escape($form['name']) ?>"
                    required
                >

                <?= $fieldError('name') ?>

            </div>

            <div class="field">

                <label for="password">
                    Gruppenpasswort
                </label>

                <input
                    type="text"
                    id="password"
                    name="password"
                    value="<?= $escape($form['password']) ?>"
                    autocomplete="off"
                    required
                >

                <p class="field-help">
                    Dieses Passwort verwendet die Gruppe zur
                    Anmeldung im Bestellbereich.
                </p>

                <?= $fieldError('password') ?>

            </div>

        </div>

    </section>

    <section class="form-section">

        <div class="form-section__header">
            <h2>Teilnehmerzahlen</h2>
            <p class="small-muted">
                Diese Werte werden später pro Liefertag in
                Budget und Personenanzahl eingerechnet.
            </p>
        </div>

        <div class="form-grid form-grid--three">

            <div class="field">

                <label for="participants_arrival_half">
                    Anreisetag – halber Tag
                </label>

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

                <?= $fieldError(
                    'participants_arrival_half'
                ) ?>

            </div>

            <div class="field">

                <label for="participants_week1_full">
                    Erste Woche – ganzer Tag
                </label>

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

                <?= $fieldError(
                    'participants_week1_full'
                ) ?>

            </div>

            <div class="field">

                <label for="participants_week1_departure_half">
                    Abreise Woche 1 – halber Tag
                </label>

                <input
                    type="number"
                    id="participants_week1_departure_half"
                    name="participants_week1_departure_half"
                    min="0"
                    step="1"
                    value="<?= $escape(
                        $form[
                            'participants_week1_departure_half'
                        ]
                    ) ?>"
                    required
                >

                <?= $fieldError(
                    'participants_week1_departure_half'
                ) ?>

            </div>

            <div class="field">

                <label for="participants_week1_departure_full">
                    Abreise Woche 1 – ganzer Tag
                </label>

                <input
                    type="number"
                    id="participants_week1_departure_full"
                    name="participants_week1_departure_full"
                    min="0"
                    step="1"
                    value="<?= $escape(
                        $form[
                            'participants_week1_departure_full'
                        ]
                    ) ?>"
                    required
                >

                <?= $fieldError(
                    'participants_week1_departure_full'
                ) ?>

            </div>

            <div class="field">

                <label for="participants_visitors">
                    Zusätzliche Personen am Besuchstag
                </label>

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

                <p class="field-help">
                    Diese Personen kommen am definierten Besuchstag
                    zusätzlich zu den normalen Teilnehmern dazu.
                </p>

                <?= $fieldError(
                    'participants_visitors'
                ) ?>

            </div>

            <div class="field">

                <label for="participants_week2_full">
                    Zweite Woche – ganzer Tag
                </label>

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

                <?= $fieldError(
                    'participants_week2_full'
                ) ?>

            </div>

            <div class="field">

                <label for="participants_week2_departure_half">
                    Abreise Woche 2 – halber Tag
                </label>

                <input
                    type="number"
                    id="participants_week2_departure_half"
                    name="participants_week2_departure_half"
                    min="0"
                    step="1"
                    value="<?= $escape(
                        $form[
                            'participants_week2_departure_half'
                        ]
                    ) ?>"
                    required
                >

                <?= $fieldError(
                    'participants_week2_departure_half'
                ) ?>

            </div>

        </div>

    </section>

    <div class="form-actions">

        <button type="submit">
            Gruppe erstellen
        </button>

        <a
            class="button button--secondary"
            href="/admin/groups.php"
        >
            Abbrechen
        </a>

    </div>

</form>

<?php
require dirname(__DIR__) . '/partials/layout_end.php';
