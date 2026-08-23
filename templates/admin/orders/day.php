<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatMoneyAmount =
    static function (mixed $amount): string {
        return 'CHF ' . number_format(
            (float) $amount,
            2,
            '.',
            "'"
        );
    };

$formatMoneyCents =
    static function (int $cents): string {
        return 'CHF ' . number_format(
            $cents / 100,
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

$formatDeadline =
    static function (
        DateTimeImmutable $deadline
    ): string {
        return $deadline->format(
            'd.m.Y H:i'
        ) . ' Uhr';
    };

$statusLabels = [
    'submitted' => 'Bestätigt',
    'draft' => 'Entwurf',
    'missing' => 'Nicht bestellt',
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
        Tagesauswertung –
        <?= $escape($deliveryDate) ?>
    </title>
</head>
<body>

<p>
    <a href="/admin/orders.php">
        Zurück zu den Bestellungen
    </a>
</p>

<h1>Tagesauswertung</h1>

<h2>
    <?= $escape(
        $formatDate($deliveryDate)
    ) ?>
</h2>

<p>
    Bestellschluss:
    <strong>
        <?= $escape(
            $formatDeadline(
                $cutoffStatus['deadline']
            )
        ) ?>
    </strong>
    –
    <?= $cutoffStatus['is_open']
        ? 'noch offen'
        : 'vorbei' ?>
</p>

<h3>Status der Gruppen</h3>

<ul>
    <li>
        Erwartete Gruppen:
        <strong>
            <?= count($groupEntries) ?>
        </strong>
    </li>

    <li>
        Bestätigt:
        <strong>
            <?= $submittedCount ?>
        </strong>
    </li>

    <li>
        Entwürfe:
        <strong>
            <?= $draftCount ?>
        </strong>
    </li>

    <li>
        Nicht bestellt:
        <strong>
            <?= $missingCount ?>
        </strong>
    </li>

    <li>
        Tagesbudget aller Gruppen:
        <strong>
            <?= $escape(
                $formatMoneyCents(
                    $dayBudgetCents
                )
            ) ?>
        </strong>
    </li>

    <li>
        Warenwert bestätigter Bestellungen:
        <strong>
            <?= $escape(
                $formatMoneyAmount(
                    $submittedAmount
                )
            ) ?>
        </strong>
    </li>
</ul>

<?php if (
    $draftCount > 0
    || $missingCount > 0
): ?>

    <p>
        <strong>
            Achtung: Die Sammelbestellung unten
            enthält nur definitiv bestätigte
            Bestellungen. Entwürfe und fehlende
            Gruppen werden nicht eingerechnet.
        </strong>
    </p>

<?php endif; ?>

<h3>Sammelbestellung</h3>

<?php if ($aggregateItems === []): ?>

    <p>
        Für diesen Liefertag gibt es noch keine
        bestätigten Bestellpositionen.
    </p>

<?php else: ?>

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
                    <?php if (
                        $item['article_number'] === null
                        || $item['article_number'] === ''
                    ): ?>
                        –
                    <?php else: ?>
                        <?= $escape(
                            $item['article_number']
                        ) ?>
                    <?php endif; ?>
                </td>

                <td>
                    <?= $escape(
                        $item['product_name']
                    ) ?>
                </td>

                <td>
                    <?php if (
                        $item['unit'] === null
                        || $item['unit'] === ''
                    ): ?>
                        –
                    <?php else: ?>
                        <?= $escape(
                            $item['unit']
                        ) ?>
                    <?php endif; ?>
                </td>

                <td>
                    <?= (int) $item['total_quantity'] ?>
                </td>

                <td>
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

<?php endif; ?>

<h3>Gruppen</h3>

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
        <tr>
            <td>
                <?= $escape(
                    $entry['group']['name']
                ) ?>
            </td>

            <td>
                <?= $escape(
                    $formatMoneyCents(
                        (int) $entry[
                            'budget_day'
                        ][
                            'budget_cents'
                        ]
                    )
                ) ?>
            </td>

            <td>
                <?= $escape(
                    $statusLabels[
                        $entry['status']
                    ]
                    ?? $entry['status']
                ) ?>
            </td>

            <td>
                <?php if (
                    $entry['order'] === null
                ): ?>
                    –
                <?php else: ?>
                    <?= $escape(
                        $formatMoneyAmount(
                            $entry[
                                'order'
                            ][
                                'total_amount'
                            ]
                        )
                    ) ?>
                <?php endif; ?>
            </td>

            <td>
                <a
                    href="/admin/orders/edit.php?group_id=<?= (int) $entry['group']['id'] ?>&amp;date=<?= $escape(
                        rawurlencode(
                            $deliveryDate
                        )
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

</body>
</html>