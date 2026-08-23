<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
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

$adminPageTitle = 'Kommissionierung – ' . $formatDate($deliveryDate);
$adminActiveSection = 'orders';
$adminExtraStylesheets = ['/assets/picking.css'];

require dirname(__DIR__)
    . '/partials/layout_start.php';
?>

<div class="page-header">
    <div class="page-header__copy">
        <p class="eyebrow">Kommissionierung</p>
        <h1><?= $escape($formatDate($deliveryDate)) ?></h1>
        <p class="lead">
            Sammelkontrolle und gruppenweise Packliste für die Ausgabe.
        </p>
    </div>

    <div class="toolbar no-print">
        <a
            class="button button--secondary"
            href="/admin/orders/day.php?date=<?= $escape(
                rawurlencode($deliveryDate)
            ) ?>"
        >
            Zurück zur Tagesauswertung
        </a>

        <button
            type="button"
            onclick="window.print()"
        >
            Drucken
        </button>
    </div>
</div>

<div class="picking-summary">
    <div class="picking-summary__item">
        <span class="picking-summary__label">Liefertag</span>
        <span class="picking-summary__value">
            <?= $escape($formatDate($deliveryDate)) ?>
        </span>
    </div>

    <div class="picking-summary__item">
        <span class="picking-summary__label">Bestellschluss</span>
        <span class="picking-summary__value">
            <?= $escape(
                $formatDeadline(
                    $cutoffStatus['deadline']
                )
            ) ?>
        </span>
    </div>

    <div class="picking-summary__item">
        <span class="picking-summary__label">Bestätigt</span>
        <span class="picking-summary__value">
            <?= (int) $submittedCount ?> Gruppen
        </span>
    </div>

    <div class="picking-summary__item">
        <span class="picking-summary__label">Noch offen</span>
        <span class="picking-summary__value">
            <?= (int) $draftCount ?> Entwürfe ·
            <?= (int) $missingCount ?> fehlend
        </span>
    </div>
</div>

<?php if ($draftGroups !== [] || $missingGroups !== []): ?>
    <div class="alert alert--warning">
        <strong>Diese Kommissionierliste ist noch nicht vollständig.</strong>

        <?php if ($draftGroups !== []): ?>
            <p>
                Nur als Entwurf gespeichert:
                <?= $escape(implode(', ', $draftGroups)) ?>
            </p>
        <?php endif; ?>

        <?php if ($missingGroups !== []): ?>
            <p>
                Noch nicht bestellt:
                <?= $escape(implode(', ', $missingGroups)) ?>
            </p>
        <?php endif; ?>

        <p>
            Die untenstehenden Mengen enthalten ausschliesslich definitiv
            bestätigte Bestellungen.
        </p>
    </div>
<?php endif; ?>

<section class="panel">
    <div class="panel__header">
        <div>
            <h2>Sammelkontrolle</h2>
            <span class="small-muted">
                Gesamte Packungsmenge vor der Verteilung kontrollieren.
            </span>
        </div>
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
                        <th class="picking-check-column">OK</th>
                        <th>Artikelnummer</th>
                        <th>Produkt</th>
                        <th>Einheit</th>
                        <th>Packungen gesamt</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($aggregateItems as $item): ?>
                    <tr>
                        <td class="picking-check-column">
                            <span class="picking-check-box"></span>
                        </td>

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
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="page-header">
    <div class="page-header__copy">
        <h2>Kommissionierung nach Gruppe</h2>
        <p class="lead">
            Jede bestätigte Gruppe erhält eine eigene Abhakliste.
        </p>
    </div>
</div>

<?php if ($submittedOrders === []): ?>
    <section class="panel">
        <div class="empty-state">
            Es gibt noch keine definitiv bestätigten Bestellungen.
        </div>
    </section>
<?php else: ?>
    <?php foreach ($submittedOrders as $submittedOrder): ?>
        <section class="panel picking-group">
            <div class="panel__header">
                <div>
                    <h2>
                        <?= $escape(
                            $submittedOrder['group']['name']
                        ) ?>
                    </h2>

                    <span class="small-muted">
                        <?= count($submittedOrder['items']) ?> Positionen
                    </span>
                </div>

                <span class="badge badge--success">
                    Bestätigt
                </span>
            </div>

            <?php if ($submittedOrder['items'] === []): ?>
                <div class="empty-state">
                    Diese bestätigte Bestellung enthält keine Positionen.
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="picking-check-column">OK</th>
                                <th>Artikelnummer</th>
                                <th>Produkt</th>
                                <th>Einheit</th>
                                <th>Packungen</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach (
                            $submittedOrder['items']
                            as $item
                        ): ?>
                            <tr>
                                <td class="picking-check-column">
                                    <span class="picking-check-box"></span>
                                </td>

                                <td>
                                    <?= $item['article_number'] === null
                                        || $item['article_number'] === ''
                                            ? '–'
                                            : $escape(
                                                $item['article_number']
                                            ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= $escape(
                                            $item['product_name']
                                        ) ?>
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
                                        <?= (int) $item['quantity'] ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <p><strong>Bemerkungen:</strong></p>
            <div class="picking-notes"></div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require dirname(__DIR__)
    . '/partials/layout_end.php';
