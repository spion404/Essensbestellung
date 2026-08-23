<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatMoney = static function (mixed $amount): string {
    return 'CHF ' . number_format(
        (float) $amount,
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

    return $parsedDate->format('d.m.Y');
};

$formatDateTime = static function (
    ?string $dateTime
): string {
    if ($dateTime === null || $dateTime === '') {
        return '–';
    }

    $parsedDateTime =
        DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $dateTime
        );

    if ($parsedDateTime === false) {
        return $dateTime;
    }

    return $parsedDateTime->format(
        'd.m.Y H:i'
    );
};

$statusLabels = [
    'draft' => 'Entwurf',
    'submitted' => 'Bestätigt',
];

$isSubmitted =
    $order['status'] === 'submitted';

$adminPageTitle =
    'Bestellung – '
    . (string) $order['group_name'];

$adminActiveSection = 'orders';

require dirname(__DIR__) . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Bestellungen
        </p>

        <h1>
            <?= $escape($order['group_name']) ?>
        </h1>

        <p class="lead">
            Gespeicherter Bestell-Snapshot für diesen Liefertag.
        </p>

    </div>

    <div class="toolbar">

        <a
            class="button"
            href="/admin/orders/edit.php?group_id=<?= (int) $order['group_id'] ?>&amp;date=<?= $escape(
                rawurlencode(
                    (string) $order['delivery_date']
                )
            ) ?>"
        >
            Bestellung bearbeiten
        </a>

        <a
            class="button button--secondary"
            href="/admin/orders.php"
        >
            Zurück
        </a>

    </div>

</div>

<div class="stats">

    <div class="stat">

        <span class="stat__label">
            Liefertag
        </span>

        <span class="stat__value">
            <?= $escape(
                $formatDate(
                    (string) $order['delivery_date']
                )
            ) ?>
        </span>

    </div>

    <div
        class="stat <?= $isSubmitted
            ? 'stat--success'
            : 'stat--warning'
        ?>"
    >

        <span class="stat__label">
            Status
        </span>

        <span class="stat__value">
            <?= $escape(
                $statusLabels[
                    $order['status']
                ]
                ?? $order['status']
            ) ?>
        </span>

    </div>

    <div class="stat">

        <span class="stat__label">
            Bestätigt am
        </span>

        <span class="stat__value">
            <?= $escape(
                $formatDateTime(
                    $order['submitted_at']
                )
            ) ?>
        </span>

    </div>

    <div class="stat">

        <span class="stat__label">
            Warenwert
        </span>

        <span class="stat__value">
            <?= $escape(
                $formatMoney(
                    $order['total_amount']
                )
            ) ?>
        </span>

    </div>

</div>

<div class="alert alert--info">
    Produktdaten und Preise sind Snapshots der Bestellung.
    Spätere Änderungen am Produktkatalog verändern diese
    Bestellung nicht.
</div>

<section class="panel">

    <div class="panel__header">

        <div>
            <h2>Bestellpositionen</h2>
            <span class="small-muted">
                <?= count($items) ?> Positionen
            </span>
        </div>

    </div>

    <?php if ($items === []): ?>

        <div class="empty-state">
            Diese Bestellung enthält noch keine Positionen.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Artikelnummer</th>
                        <th>Produkt</th>
                        <th>Einheit</th>
                        <th>Packungen</th>
                        <th>Preis</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($items as $item): ?>

                    <tr>

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
                            <?= (int) $item['quantity'] ?>
                        </td>

                        <td class="numeric">
                            <?= $escape(
                                $formatMoney(
                                    $item['unit_price']
                                )
                            ) ?>
                        </td>

                        <td class="numeric">
                            <strong>
                                <?= $escape(
                                    $formatMoney(
                                        $item[
                                            'line_total_amount'
                                        ]
                                    )
                                ) ?>
                            </strong>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="5">
                            Warenwert
                        </th>
                        <th class="numeric">
                            <?= $escape(
                                $formatMoney(
                                    $order['total_amount']
                                )
                            ) ?>
                        </th>
                    </tr>
                </tfoot>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php
require dirname(__DIR__) . '/partials/layout_end.php';
