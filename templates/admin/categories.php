<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$adminPageTitle = 'Kategorien';
$adminActiveSection = 'categories';

require __DIR__ . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Produktkatalog
        </p>

        <h1>Kategorien</h1>

        <p class="lead">
            Kategorien strukturieren die Produktauswahl und werden
            beim XLSX-Import bei Bedarf automatisch angelegt.
        </p>

    </div>

    <a
        class="button"
        href="/admin/categories/create.php"
    >
        Neue Kategorie
    </a>

</div>

<?php if ($created): ?>

    <div class="alert alert--success">
        Die Kategorie wurde erstellt.
    </div>

<?php endif; ?>

<section class="panel">

    <div class="panel__header">

        <div>

            <h2>Erfasste Kategorien</h2>

            <span class="small-muted">
                <?= count($categories) ?> Kategorien
            </span>

        </div>

    </div>

    <?php if ($categories === []): ?>

        <div class="empty-state">
            Es wurden noch keine Kategorien erfasst.
        </div>

    <?php else: ?>

        <div class="checkbox-grid">

            <?php foreach (
                $categories
                as $category
            ): ?>

                <div class="info-card">

                    <span class="badge badge--neutral">
                        <?= $escape($category['name']) ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php
require __DIR__ . '/partials/layout_end.php';
