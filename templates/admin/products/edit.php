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

$adminPageTitle =
    'Produkt bearbeiten – '
    . (string) $form['name'];

$adminActiveSection = 'products';

require dirname(__DIR__) . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Produkte
        </p>

        <h1>Produkt bearbeiten</h1>

        <p class="lead">
            <?= $escape($form['name']) ?>
        </p>

    </div>

    <a
        class="button button--secondary"
        href="/admin/products.php"
    >
        Zurück zu den Produkten
    </a>

</div>

<form
    class="admin-form"
    method="post"
>

    <section class="form-section">

        <div class="form-section__header">

            <h2>Produktdaten</h2>

            <p class="small-muted">
                Preis und Einheit beziehen sich auf eine bestellbare
                Packung.
            </p>

        </div>

        <div class="form-grid">

            <div class="field field--full">

                <label for="name">
                    Produktname
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="200"
                    value="<?= $escape($form['name']) ?>"
                    required
                >

                <?= $fieldError('name') ?>

            </div>

            <div class="field">

                <label for="unit">
                    Einheit / Packungsgrösse
                </label>

                <input
                    type="text"
                    id="unit"
                    name="unit"
                    maxlength="50"
                    value="<?= $escape($form['unit']) ?>"
                    placeholder="z. B. 500 g, 1 l, 12 Stück"
                >

                <?= $fieldError('unit') ?>

            </div>

            <div class="field">

                <label for="price">
                    Preis pro Packung
                </label>

                <div class="input-prefix">

                    <span class="input-prefix__label">
                        CHF
                    </span>

                    <input
                        type="text"
                        id="price"
                        name="price"
                        inputmode="decimal"
                        placeholder="0.00"
                        value="<?= $escape($form['price']) ?>"
                        required
                    >

                </div>

                <?= $fieldError('price') ?>

            </div>

            <div class="field field--full">

                <label for="remark">
                    Bemerkung
                </label>

                <textarea
                    id="remark"
                    name="remark"
                    rows="4"
                    placeholder="Optionale Hinweise zum Produkt"
                ><?= $escape($form['remark']) ?></textarea>

            </div>

        </div>

    </section>

    <section class="form-section">

        <div class="form-section__header">

            <h2>Kategorien</h2>

            <p class="small-muted">
                Ein Produkt kann mehreren Kategorien zugeordnet
                werden.
            </p>

        </div>

        <fieldset class="form-fieldset">

            <legend class="visually-hidden">
                Kategorien auswählen
            </legend>

            <?php if ($categories === []): ?>

                <div class="alert alert--info">

                    Es wurden noch keine Kategorien erstellt.
                    Das Produkt kann trotzdem ohne Kategorie
                    gespeichert werden.

                </div>

                <a
                    class="button button--secondary"
                    href="/admin/categories/create.php"
                >
                    Kategorie erstellen
                </a>

            <?php else: ?>

                <div class="checkbox-grid">

                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <?php
                        $categoryId =
                            (int) $category['id'];

                        $checked = in_array(
                            $categoryId,
                            $form['category_ids'],
                            true
                        );
                        ?>

                        <label class="checkbox-card">

                            <input
                                type="checkbox"
                                name="category_ids[]"
                                value="<?= $categoryId ?>"
                                <?= $checked
                                    ? 'checked'
                                    : ''
                                ?>
                            >

                            <span class="choice-copy">
                                <strong>
                                    <?= $escape(
                                        $category['name']
                                    ) ?>
                                </strong>
                            </span>

                        </label>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <?= $fieldError('categories') ?>

        </fieldset>

    </section>

    <div class="form-actions">

        <button type="submit">
            Änderungen speichern
        </button>

        <a
            class="button button--secondary"
            href="/admin/products.php"
        >
            Abbrechen
        </a>

    </div>

</form>

<?php
require dirname(__DIR__) . '/partials/layout_end.php';
