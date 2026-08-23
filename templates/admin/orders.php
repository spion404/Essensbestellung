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
    <a href="/admin/groups.php">Gruppen</a>
    |
    <a href="/admin/products.php">Produkte</a>
</p>

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
                    <?= $escape($order['group_name']) ?>
                </td>

                <td>
                    <?= $escape(
                        $statusLabels[$order['status']]
                        ?? $order['status']
                    ) ?>
                </td>

                <td>
                    <?= (int) $order['item_count'] ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoney($order['total_amount'])
                    ) ?>
                </td>

                <td>
                    <a
                        href="/admin/orders/view.php?id=<?= (int) $order['id'] ?>"
                    >
                        Anzeigen
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

<?php endif; ?>

</body>
</html>