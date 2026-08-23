<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatMoney = static function (int $cents): string {
    return 'CHF ' . number_format(
        $cents / 100,
        2,
        '.',
        "'"
    );
};

$formatAmount = static function (mixed $amount): string {
    return 'CHF ' . number_format(
        (float) $amount,
        2,
        '.',
        "'"
    );
};

$formatDate = static function (string $date): string {
    $parsed = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $date
    );

    if ($parsed === false) {
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

    return $weekdays[(int) $parsed->format('N')]
        . ', '
        . $parsed->format('d.m.Y');
};

$roundingCents = (int) (
    $summary['rounding_cents']
    ?? 0
);

$effectiveTotalCents = (int) (
    $summary['effective_total_cents']
    ?? $summary['total_cents']
);

$remainingBudgetCents =
    (int) $summary['remaining_budget_cents'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 18mm 16mm 18mm 16mm;
        }

        body {
            color: #1f2a22;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            margin: 0;
        }

        h1 {
            color: #245c38;
            font-size: 20pt;
            margin: 0 0 3mm;
        }

        h2 {
            font-size: 12pt;
            margin: 7mm 0 2.5mm;
        }

        .subtitle {
            color: #68736b;
            margin-bottom: 7mm;
        }

        .meta,
        .financials,
        .items {
            border-collapse: collapse;
            width: 100%;
        }

        .meta td {
            border-bottom: 1px solid #d9ded8;
            padding: 2mm 1.5mm;
            vertical-align: top;
        }

        .meta .label {
            color: #68736b;
            font-weight: bold;
            width: 35mm;
        }

        .items th,
        .items td {
            border-bottom: 1px solid #d9ded8;
            padding: 2.2mm 1.5mm;
            vertical-align: top;
        }

        .items th {
            background: #e6f0e9;
            color: #174328;
            font-size: 8pt;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .article {
            color: #68736b;
            font-size: 8pt;
        }

        .financials {
            margin-top: 3mm;
        }

        .financials td {
            border-bottom: 1px solid #d9ded8;
            padding: 2mm 1.5mm;
        }

        .financials .label {
            width: 70%;
        }

        .financials .value {
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
        }

        .financials .total td {
            border-top: 2px solid #245c38;
            color: #174328;
            font-size: 10.5pt;
            font-weight: bold;
        }

        .warning {
            background: #fbeceb;
            border: 1px solid #a13d37;
            margin-top: 5mm;
            padding: 3mm;
        }

        .note {
            color: #68736b;
            font-size: 8pt;
            margin-top: 7mm;
        }

        .footer {
            color: #68736b;
            font-size: 7.5pt;
            margin-top: 10mm;
            text-align: right;
        }
    </style>
</head>
<body>

<h1><?= $escape($settings['camp_name']) ?></h1>

<div class="subtitle">
    Definitiv bestätigte Essensbestellung
</div>

<table class="meta">
    <tr>
        <td class="label">Gruppe</td>
        <td><strong><?= $escape($group['name']) ?></strong></td>
    </tr>
    <tr>
        <td class="label">Liefertag</td>
        <td><?= $escape($formatDate($deliveryDate)) ?></td>
    </tr>
    <tr>
        <td class="label">Status</td>
        <td>Bestätigt</td>
    </tr>
    <tr>
        <td class="label">Bestätigt am</td>
        <td>
            <?= $summary['order']['submitted_at'] === null
                ? '–'
                : $escape(
                    (new DateTimeImmutable(
                        (string) $summary['order']['submitted_at'],
                        $timezone
                    ))->format('d.m.Y H:i')
                ) ?>
        </td>
    </tr>
</table>

<h2>Bestellpositionen</h2>

<table class="items">
    <thead>
        <tr>
            <th>Produkt</th>
            <th>Einheit</th>
            <th class="right">Packungen</th>
            <th class="right">Preis</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($summary['items'] as $item): ?>
        <tr>
            <td>
                <strong><?= $escape($item['product_name']) ?></strong>
                <?php if (
                    $item['article_number'] !== null
                    && $item['article_number'] !== ''
                ): ?>
                    <div class="article">
                        Art.-Nr. <?= $escape($item['article_number']) ?>
                    </div>
                <?php endif; ?>
            </td>
            <td>
                <?= $item['unit'] === null || $item['unit'] === ''
                    ? '–'
                    : $escape($item['unit']) ?>
            </td>
            <td class="right nowrap">
                <?= (int) $item['quantity'] ?>
            </td>
            <td class="right nowrap">
                <?= $escape($formatAmount($item['unit_price'])) ?>
            </td>
            <td class="right nowrap">
                <strong>
                    <?= $escape($formatAmount($item['line_total_amount'])) ?>
                </strong>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Budget und Abrechnung</h2>

<table class="financials">
    <tr>
        <td class="label">Tagesbudget</td>
        <td class="value">
            <?= $escape(
                $formatMoney(
                    (int) $summary['budget_cents']
                )
            ) ?>
        </td>
    </tr>
    <tr>
        <td class="label">Übertrag / Rundung bisher</td>
        <td class="value">
            <?= $escape(
                $formatMoney(
                    (int) $budgetBalance['carryover_cents']
                )
            ) ?>
        </td>
    </tr>
    <tr>
        <td class="label">Gesamtbudget Lager</td>
        <td class="value">
            <?= $escape(
                $formatMoney(
                    (int) $budgetBalance['total_budget_cents']
                )
            ) ?>
        </td>
    </tr>
    <tr>
        <td class="label">Warenwert</td>
        <td class="value">
            <?= $escape(
                $formatMoney(
                    (int) $summary['total_cents']
                )
            ) ?>
        </td>
    </tr>
    <tr>
        <td class="label">Rundung / Kostenkorrektur</td>
        <td class="value">
            <?= $escape($formatMoney($roundingCents)) ?>
        </td>
    </tr>
    <tr class="total">
        <td class="label">Effektive Kosten</td>
        <td class="value">
            <?= $escape($formatMoney($effectiveTotalCents)) ?>
        </td>
    </tr>
</table>

<?php if ($remainingBudgetCents < 0): ?>
    <div class="warning">
        <strong>Tagesbudget überschritten:</strong>
        Der Warenwert liegt um
        <?= $escape($formatMoney(abs($remainingBudgetCents))) ?>
        über dem berechneten Tagesbudget.
        Der Übertrag wird dabei bewusst nicht in die Warnschwelle eingerechnet.
    </div>
<?php endif; ?>

<p class="note">
    Der Wert „Übertrag / Rundung bisher“ ist ein automatisch berechneter
    Informationswert aus früheren Lagertagen. Die Budgetwarnung dieser
    Bestellung bezieht sich ausschliesslich auf das berechnete Tagesbudget.
</p>

<div class="footer">
    PDF erstellt am <?= $escape($generatedAt->format('d.m.Y H:i')) ?> Uhr
</div>

</body>
</html>
