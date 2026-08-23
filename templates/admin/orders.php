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
    $parsedDate =
        DateTimeImmutable::createFromFormat(
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

    return $weekdays[
        (int) $parsedDate->format('N')
    ]
        . ', '
        . $parsedDate->format('d.m.Y');
};

$statusLabels = [
    'draft' => 'Entwurf',
    'submitted' => 'Bestätigt',
];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Bestellungen</title>
</head>
<body>

<h1>Bestellungen</h1>

<p>
    <a href="/admin/groups.php">
        Gruppen
    </a>
    |
    <a href="/admin/products.php">
        Produkte
    </a>
    |
    <a href="/admin/settings.php">
        Einstellungen
    </a>
</p>

<form
    method="post"
    action="/admin/logout.php"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= $escape($adminCsrfToken) ?>"
    >

    <button type="submit">
        Administration abmelden
    </button>
</form>

<h2>Tagesauswertungen</h2>

<?php if ($deliveryDays === []): ?>

    <p>
        Es sind noch keine Liefertage mit
        Teilnehmern konfiguriert.
    </p>

<?php else: ?>

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
                    <?= $escape(
                        $formatDate(
                            (string) $day['date']
                        )
                    ) ?>
                </td>

                <td>
                    <?= (int) $day['expected_groups'] ?>
                </td>

                <td>
                    <?= (int) $day['submitted_orders'] ?>
                </td>

                <td>
                    <?= (int) $day['draft_orders'] ?>
                </td>

                <td>
                    <?= (int) $day['missing_orders'] ?>
                </td>

                <td>
                    <a
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

<?php endif; ?>

<h2>Alle gespeicherten Bestellungen</h2>

<?php if ($orders === []): ?>

    <p>
        Es wurden noch keine Bestellungen gespeichert.
    </p>

<?php else: ?>

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
            <tr>
                <td>
                    <?= $escape(
                        $formatDate(
                            (string) $order['delivery_date']
                        )
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $order['group_name']
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $statusLabels[
                            $order['status']
                        ]
                        ?? $order['status']
                    ) ?>
                </td>

                <td>
                    <?= (int) $order['item_count'] ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoney(
                            $order['total_amount']
                        )
                    ) ?>
                </td>

                <td>
                    <a
                        href="/admin/orders/view.php?id=<?= (int) $order['id'] ?>"
                    >
                        Anzeigen
                    </a>
                    |
                    <a
                        href="/admin/orders/edit.php?group_id=<?= (int) $order['group_id'] ?>&amp;date=<?= $escape(
                            rawurlencode(
                                (string) $order['delivery_date']
                            )
                        ) ?>"
                    >
                        Bearbeiten
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

<?php endif; ?>

</body>
</html>