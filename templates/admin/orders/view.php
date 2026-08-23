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

$formatDateTime = static function (?string $dateTime): string {
    if ($dateTime === null || $dateTime === '') {
        return '–';
    }

    $parsedDateTime = DateTimeImmutable::createFromFormat(
        'Y-m-d H:i:s',
        $dateTime
    );

    if ($parsedDateTime === false) {
        return $dateTime;
    }

    return $parsedDateTime->format('d.m.Y H:i');
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

    <title>
        Bestellung – <?= $escape($order['group_name']) ?>
    </title>
</head>

<body>

<p>
    <a href="/admin/orders.php">
        Zurück zu den Bestellungen
    </a>
</p>

<h1>
    Bestellung: <?= $escape($order['group_name']) ?>
</h1>

<dl>
    <dt>Liefertag</dt>
    <dd>
        <?= $escape(
            $formatDate(
                (string) $order['delivery_date']
            )
        ) ?>
    </dd>

    <dt>Status</dt>
    <dd>
        <?= $escape(
            $statusLabels[$order['status']]
            ?? $order['status']
        ) ?>
    </dd>

    <dt>Bestätigt am</dt>
    <dd>
        <?= $escape(
            $formatDateTime($order['submitted_at'])
        ) ?>
    </dd>
</dl>

<p>
    Die Produktdaten und Preise in dieser Ansicht sind die in der
    Bestellung gespeicherten Werte. Spätere Änderungen am
    Produktkatalog verändern diese Bestellung nicht.
</p>

<?php if ($items === []): ?>

    <p>
        Diese Bestellung enthält noch keine Positionen.
    </p>

<?php else: ?>

    <table>
        <thead>
            <tr>
                <th>Artikelnummer</th>
                <th>Produkt</th>
                <th>Einheit</th>
                <th>Menge</th>
                <th>Preis</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <?php if (
                        $item['article_number'] === null
                        || $item['article_number'] === ''
                    ): ?>
                        –
                    <?php else: ?>
                        <?= $escape($item['article_number']) ?>
                    <?php endif; ?>
                </td>

                <td>
                    <?= $escape($item['product_name']) ?>
                </td>

                <td>
                    <?php if (
                        $item['unit'] === null
                        || $item['unit'] === ''
                    ): ?>
                        –
                    <?php else: ?>
                        <?= $escape($item['unit']) ?>
                    <?php endif; ?>
                </td>

                <td>
                    <?= $escape(
                        rtrim(
                            rtrim(
                                (string) $item['quantity'],
                                '0'
                            ),
                            '.'
                        )
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoney($item['unit_price'])
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoney(
                            $item['line_total_amount']
                        )
                    ) ?>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>

        <tfoot>
            <tr>
                <th colspan="5">
                    Warenwert
                </th>
                <th>
                    <?= $escape(
                        $formatMoney($order['total_amount'])
                    ) ?>
                </th>
            </tr>
        </tfoot>
    </table>

<?php endif; ?>

</body>
</html>