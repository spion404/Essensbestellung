<?php

declare(strict_types=1);

$escape = static function (
    mixed $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$adminPageTitle = 'Produkte';
$adminActiveSection = 'products';

require __DIR__ . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Produktkatalog
        </p>

        <h1>Produkte</h1>

        <p class="lead">
            Artikel, Preise, Packungseinheiten, Kategorien und
            Bemerkungen verwalten.
        </p>

    </div>

    <div class="toolbar">

        <a
            class="button button--secondary"
            href="/admin/products/import.php"
        >
            XLSX importieren
        </a>

        <a
            class="button"
            href="/admin/products/create.php"
        >
            Neues Produkt
        </a>

    </div>

</div>

<?php if ($created): ?>

    <div class="alert alert--success">
        Das Produkt wurde erstellt.
    </div>

<?php endif; ?>

<?php if ($updated): ?>

    <div class="alert alert--success">
        Das Produkt wurde aktualisiert.
    </div>

<?php endif; ?>

<?php if ($deleted): ?>

    <div class="alert alert--success">
        Das Produkt wurde gelöscht.
    </div>

<?php endif; ?>

<?php if ($importCompleted): ?>

    <div class="alert alert--success">

        <strong>Produktimport abgeschlossen.</strong>

        <ul class="compact-list">
            <li>
                Neu erstellt:
                <?= (int) ($importCreated ?? 0) ?>
            </li>
            <li>
                Aktualisiert:
                <?= (int) ($importUpdated ?? 0) ?>
            </li>
            <li>
                Vor dem Import entfernt:
                <?= (int) ($importDeleted ?? 0) ?>
            </li>
        </ul>

    </div>

<?php endif; ?>

<section class="panel">

    <div class="panel__header">

        <div>

            <h2>Produktkatalog</h2>

            <span class="small-muted">
                <?= count($products) ?> Produkte erfasst
            </span>

        </div>

    </div>

    <?php if ($products === []): ?>

        <div class="empty-state">
            Es wurden noch keine Produkte erfasst.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Artikelnummer</th>
                        <th>Produkt</th>
                        <th>Einheit</th>
                        <th>Preis</th>
                        <th>Kategorien</th>
                        <th>Bemerkung</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($products as $product): ?>

                    <tr>

                        <td>
                            <?= $product['article_number'] === null
                                || $product['article_number'] === ''
                                    ? '–'
                                    : $escape(
                                        $product['article_number']
                                    ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= $escape($product['name']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= $product['unit'] === null
                                || $product['unit'] === ''
                                    ? '–'
                                    : $escape($product['unit']) ?>
                        </td>

                        <td class="numeric">
                            CHF
                            <?= number_format(
                                (float) $product['price'],
                                2,
                                '.',
                                "'"
                            ) ?>
                        </td>

                        <td>
                            <?= $product['categories'] === null
                                || $product['categories'] === ''
                                    ? '–'
                                    : $escape(
                                        $product['categories']
                                    ) ?>
                        </td>

                        <td>
                            <?= $product['remark'] === null
                                || $product['remark'] === ''
                                    ? '–'
                                    : $escape($product['remark']) ?>
                        </td>

                        <td class="actions-cell">

                            <div class="toolbar">

                                <a
                                    class="button button--secondary button--small"
                                    href="/admin/products/edit.php?id=<?= (int) $product['id'] ?>"
                                >
                                    Bearbeiten
                                </a>

                                <a
                                    class="button button--danger button--small"
                                    href="/admin/products/delete.php?id=<?= (int) $product['id'] ?>"
                                >
                                    Löschen
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php
require __DIR__ . '/partials/layout_end.php';
