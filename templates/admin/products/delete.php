<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$adminPageTitle =
    'Produkt löschen – '
    . (string) $product['name'];

$adminActiveSection = 'products';

require dirname(__DIR__) . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Produkte
        </p>

        <h1>Produkt löschen</h1>

        <p class="lead">
            Prüfe die Angaben, bevor du den Artikel endgültig
            aus dem aktuellen Produktkatalog entfernst.
        </p>

    </div>

    <a
        class="button button--secondary"
        href="/admin/products.php"
    >
        Abbrechen
    </a>

</div>

<section class="danger-zone">

    <h2>
        <?= $escape($product['name']) ?>
    </h2>

    <div class="content-grid">

        <?php if (
            $product['unit'] !== null
            && $product['unit'] !== ''
        ): ?>

            <div class="info-card">

                <span class="info-card__label">
                    Einheit
                </span>

                <span class="info-card__value">
                    <?= $escape($product['unit']) ?>
                </span>

            </div>

        <?php endif; ?>

        <div class="info-card">

            <span class="info-card__label">
                Preis
            </span>

            <span class="info-card__value">
                CHF
                <?= number_format(
                    (float) $product['price'],
                    2,
                    '.',
                    "'"
                ) ?>
            </span>

        </div>

    </div>

    <p>
        <strong>
            Dieser Vorgang kann nicht rückgängig gemacht werden.
        </strong>
    </p>

    <form method="post">

        <div class="form-actions">

            <button
                class="button--danger"
                type="submit"
            >
                Produkt endgültig löschen
            </button>

            <a
                class="button button--secondary"
                href="/admin/products.php"
            >
                Abbrechen
            </a>

        </div>

    </form>

</section>

<?php
require dirname(__DIR__) . '/partials/layout_end.php';
