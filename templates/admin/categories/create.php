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

$adminPageTitle = 'Neue Kategorie';
$adminActiveSection = 'categories';

require dirname(__DIR__) . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Kategorien
        </p>

        <h1>Neue Kategorie</h1>

        <p class="lead">
            Eine zusätzliche Filter- und Produktkategorie anlegen.
        </p>

    </div>

    <a
        class="button button--secondary"
        href="/admin/categories.php"
    >
        Zurück zu den Kategorien
    </a>

</div>

<form
    class="admin-form"
    method="post"
>

    <section class="form-section">

        <div class="field">

            <label for="name">
                Kategoriename
            </label>

            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                value="<?= $escape($form['name']) ?>"
                required
                autofocus
            >

            <?= $fieldError('name') ?>

        </div>

    </section>

    <div class="form-actions">

        <button type="submit">
            Kategorie erstellen
        </button>

        <a
            class="button button--secondary"
            href="/admin/categories.php"
        >
            Abbrechen
        </a>

    </div>

</form>

<?php
require dirname(__DIR__) . '/partials/layout_end.php';
