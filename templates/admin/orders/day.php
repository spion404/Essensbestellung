<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatMoneyAmount = static function (mixed $amount): string {
    return 'CHF ' . number_format(
        (float) $amount,
        2,
        '.',
        "'"
    );
};

$formatMoneyCents = static function (int $cents): string {
    return 'CHF ' . number_format(
        $cents / 100,
        2,
        '.',
        "'"
    );
};

$formatDate = static function (string $date): string {
    $parsedDate = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $date
    );

    if ($parsedDate === false) {
        return $date;
    }

    $weekdays = [
        1 => 'Montag',
        2 => 'Dienstag',
        3 => 'Mittwoch',
        4 => 'Donnerstag',
        5 => 'Freitag',
        6 => 'Samstag',
        7 => 'Sonntag',
    ];

    return $weekdays[(int) $parsedDate->format('N')]
        . ', '
        . $parsedDate->format('d.m.Y');
};

$formatDeadline = static function (
    DateTimeImmutable $deadline
): string {
    return $deadline->format('d.m.Y H:i') . ' Uhr';
};

$statusLabels = [
    'submitted' => 'Bestätigt',
    'draft' => 'Entwurf',
    'missing' => 'Nicht bestellt',
];

$adminPageTitle = 'Tagesauswertung – ' . $formatDate($deliveryDate);
$adminActiveSection = 'orders';

require dirname(__DIR__)
    . '/partials/layout_start.php';
?>

<div class="page-header">
    <div class="page-header__copy">
        <p class="eyebrow">Tagesauswertung</p>

        <h1><?= $escape($formatDate($deliveryDate)) ?></h1>

        <p class="lead">
            Sammelbestellung, Gruppenstatus und Exporte für diesen Liefertag.
        </p>
    </div>

    <a
        class="button button--secondary"
        href="/admin/orders.php"
    >
        Zurück zu den Bestellungen
    </a>
</div>

<div class="stats">
    <div class="stat">
        <span class="stat__label">Erwartete Gruppen</span>
        <span class="stat__value"><?= count($groupEntries) ?></span>
    </div>

    <div class="stat stat--success">
        <span class="stat__label">Bestätigt</span>
        <span class="stat__value"><?= (int) $submittedCount ?></span>
    </div>

    <div class="stat stat--warning">
        <span class="stat__label">Entwürfe</span>
        <span class="stat__value"><?= (int) $draftCount ?></span>
    </div>

    <div class="stat <?= $missingCount > 0 ? 'stat--danger' : '' ?>">
        <span class="stat__label">Nicht bestellt</span>
        <span class="stat__value"><?= (int) $missingCount ?></span>
    </div>

    <div class="stat">
        <span class="stat__label">Tagesbudget</span>
        <span class="stat__value">
            <?= $escape(
                $formatMoneyCents(
                    (int) $dayBudgetCents
                )
            ) ?>
        </span>
    </div>

    <div class="stat">
        <span class="stat__label">Bestätigter Warenwert</span>
        <span class="stat__value">
            <?= $escape(
                $formatMoneyAmount(
                    $submittedAmount
                )
            ) ?>
        </span>
    </div>
</div>

<section class="panel panel--soft">
    <div class="section-title">
        <div>
            <h3>Bestellschluss</h3>
            <span class="small-muted">
                <?= $escape(
                    $formatDeadline(
                        $cutoffStatus['deadline']
                    )
                ) ?>
            </span>
        </div>

        <span class="badge <?=
            $cutoffStatus['is_open']
                ? 'badge--info'
                : 'badge--neutral'
        ?>">
            <?= $cutoffStatus['is_open']
                ? 'Noch offen'
                : 'Vorbei' ?>
        </span>
    </div>
</section>

<?php if ($draftCount > 0 || $missingCount > 0): ?>
    <div class="alert alert--warning">
        <strong>Die Tagesbestellung ist noch nicht vollständig.</strong>
        Entwürfe und fehlende Gruppen werden weder in die Sammelbestellung
        noch in die Kommissionierung eingerechnet.
    </div>
<?php endif; ?>

<section class="panel">
    <div class="panel__header">
        <div>
            <h2>Ausgabe und Export</h2>
            <span class="small-muted">
                Druckliste für die Verteilung oder Export zur
                Weiterverarbeitung.
            </span>
        </div>
    </div>

    <div class="toolbar">
        <a
            class="button"
            href="/admin/orders/picking.php?date=<?= $escape(
                rawurlencode($deliveryDate)
            ) ?>"
        >
            Kommissionierliste drucken
        </a>

        <a
            class="button button--secondary"
            href="/admin/orders/export.php?date=<?= $escape(
                rawurlencode($deliveryDate)
            ) ?>&amp;format=xlsx"
        >
            XLSX herunterladen
        </a>

        <a
            class="button button--secondary"
            href="/admin/orders/export.php?date=<?= $escape(
                rawurlencode($deliveryDate)
            ) ?>&amp;format=csv"
        >
            CSV herunterladen
        </a>
    </div>
</section>

<section class="panel">
    <div class="panel__header">
        <div>
            <h2>Sammelbestellung</h2>
            <span class="small-muted">
                Summierte Packungen aus definitiv bestätigten Bestellungen.
            </span>
        </div>

        <span class="badge badge--neutral">
            <?= count($aggregateItems) ?> Produkte
        </span>
    </div>

    <?php if ($aggregateItems === []): ?>
        <div class="empty-state">
            Für diesen Liefertag gibt es noch keine bestätigten
            Bestellpositionen.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Artikelnummer</th>
                        <th>Produkt</th>
                        <th>Einheit</th>
                        <th>Packungen gesamt</th>
                        <th>Warenwert</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($aggregateItems as $item): ?>
                    <tr>
                        <td>
                            <?= $item['article_number'] === null
                                || $item['article_number'] === ''
                                    ? '–'
                                    : $escape($item['article_number']) ?>
                        </td>

                        <td>
                            <strong>
                                <?= $escape($item['product_name']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= $item['unit'] === null
                                || $item['unit'] === ''
                                    ? '–'
                                    : $escape($item['unit']) ?>
                        </td>

                        <td class="numeric">
                            <strong>
                                <?= (int) $item['total_quantity'] ?>
                            </strong>
                        </td>

                        <td class="numeric">
                            <?= $escape(
                                $formatMoneyAmount(
                                    $item['total_amount']
                                )
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel__header">
        <div>
            <h2>Gruppen</h2>
            <span class="small-muted">
                Status, Warenwert und direkte Admin-Korrektur.
            </span>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Gruppe</th>
                    <th>Tagesbudget</th>
                    <th>Status</th>
                    <th>Warenwert</th>
                    <th>Aktion</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($groupEntries as $entry): ?>
                <?php
                $statusClass = match ($entry['status']) {
                    'submitted' => 'badge--success',
                    'draft' => 'badge--warning',
                    'missing' => 'badge--danger',
                    default => 'badge--neutral',
                };
                ?>

                <tr>
                    <td>
                        <strong>
                            <?= $escape($entry['group']['name']) ?>
                        </strong>
                    </td>

                    <td class="numeric">
                        <?= $escape(
                            $formatMoneyCents(
                                (int) $entry['budget_day']['budget_cents']
                            )
                        ) ?>
                    </td>

                    <td>
                        <span class="badge <?= $statusClass ?>">
                            <?= $escape(
                                $statusLabels[$entry['status']]
                                ?? $entry['status']
                            ) ?>
                        </span>
                    </td>

                    <td class="numeric">
                        <?php if ($entry['order'] === null): ?>
                            –
                        <?php else: ?>
                            <?= $escape(
                                $formatMoneyAmount(
                                    $entry['order']['total_amount']
                                )
                            ) ?>
                        <?php endif; ?>
                    </td>

                    <td class="actions-cell">
                        <a
                            class="button button--small"
                            href="/admin/orders/edit.php?group_id=<?= (int) $entry['group']['id'] ?>&amp;date=<?= $escape(
                                rawurlencode($deliveryDate)
                            ) ?>"
                        >
                            <?= $entry['order'] === null
                                ? 'Bestellung erfassen'
                                : 'Bestellung bearbeiten' ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require dirname(__DIR__)
    . '/partials/layout_end.php';
