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

$statusLabels = [
    'draft' => 'Entwurf',
    'submitted' => 'Bestätigt',
];

$deliveryDays = $deliveryDays ?? [];

$adminPageTitle = 'Bestellungen';
$adminActiveSection = 'orders';

require __DIR__ . '/partials/layout_start.php';
?>

<div class="page-header">
    <div class="page-header__copy">
        <p class="eyebrow">Administration</p>
        <h1>Bestellungen</h1>
        <p class="lead">
            Überblick über Liefertage, Gruppenstatus und alle gespeicherten
            Bestellungen.
        </p>
    </div>
</div>

<?php if ($deliveryDays !== []): ?>
    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Tagesauswertungen</h2>
                <span class="small-muted">
                    Vollständigkeit und Sammelbestellung pro Liefertag.
                </span>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Liefertag</th>
                        <th>Gruppen</th>
                        <th>Bestätigt</th>
                        <th>Entwürfe</th>
                        <th>Fehlend</th>
                        <th>Aktion</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($deliveryDays as $day): ?>
                    <tr>
                        <td>
                            <strong>
                                <?= $escape(
                                    $formatDate(
                                        (string) $day['date']
                                    )
                                ) ?>
                            </strong>
                        </td>

                        <td><?= (int) $day['expected_groups'] ?></td>

                        <td>
                            <span class="badge badge--success">
                                <?= (int) $day['submitted_orders'] ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge badge--warning">
                                <?= (int) $day['draft_orders'] ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge <?=
                                (int) $day['missing_orders'] > 0
                                    ? 'badge--danger'
                                    : 'badge--neutral'
                            ?>">
                                <?= (int) $day['missing_orders'] ?>
                            </span>
                        </td>

                        <td class="actions-cell">
                            <a
                                class="button button--small"
                                href="/admin/orders/day.php?date=<?= $escape(
                                    rawurlencode(
                                        (string) $day['date']
                                    )
                                ) ?>"
                            >
                                Tagesauswertung
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="panel__header">
        <div>
            <h2>Gespeicherte Bestellungen</h2>
            <span class="small-muted">
                Entwürfe und bestätigte Bestellungen aller Liefertage.
            </span>
        </div>

        <span class="badge badge--neutral">
            <?= count($orders) ?> Bestellungen
        </span>
    </div>

    <?php if ($orders === []): ?>
        <div class="empty-state">
            Es wurden noch keine Bestellungen gespeichert.
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Liefertag</th>
                        <th>Gruppe</th>
                        <th>Status</th>
                        <th>Positionen</th>
                        <th>Warenwert</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $statusClass =
                        $order['status'] === 'submitted'
                            ? 'badge--success'
                            : 'badge--warning';
                    ?>

                    <tr>
                        <td>
                            <?= $escape(
                                $formatDate(
                                    (string) $order['delivery_date']
                                )
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= $escape($order['group_name']) ?>
                            </strong>
                        </td>

                        <td>
                            <span class="badge <?= $statusClass ?>">
                                <?= $escape(
                                    $statusLabels[$order['status']]
                                    ?? $order['status']
                                ) ?>
                            </span>
                        </td>

                        <td><?= (int) $order['item_count'] ?></td>

                        <td class="numeric">
                            <?= $escape(
                                $formatMoney(
                                    $order['total_amount']
                                )
                            ) ?>
                        </td>

                        <td class="actions-cell">
                            <div class="toolbar">
                                <a
                                    class="button button--secondary button--small"
                                    href="/admin/orders/view.php?id=<?= (int) $order['id'] ?>"
                                >
                                    Anzeigen
                                </a>

                                <a
                                    class="button button--small"
                                    href="/admin/orders/edit.php?group_id=<?= (int) $order['group_id'] ?>&amp;date=<?= $escape(
                                        rawurlencode(
                                            (string) $order['delivery_date']
                                        )
                                    ) ?>"
                                >
                                    Bearbeiten
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
