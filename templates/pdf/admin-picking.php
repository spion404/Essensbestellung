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

$formatDeadline = static function (
    DateTimeImmutable $deadline
): string {
    return $deadline->format('d.m.Y H:i') . ' Uhr';
};
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 13mm 11mm 14mm 11mm;
        }

        body {
            color: #111;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            margin: 0;
        }

        h1 {
            font-size: 18pt;
            margin: 0 0 2mm;
        }

        h2 {
            font-size: 12pt;
            margin: 7mm 0 2.5mm;
        }

        h3 {
            font-size: 11pt;
            margin: 0 0 2.5mm;
        }

        .subtitle {
            color: #555;
            margin-bottom: 5mm;
        }

        .meta,
        .list {
            border-collapse: collapse;
            width: 100%;
        }

        .meta td {
            border-bottom: 1px solid #aaa;
            padding: 1.7mm 1mm;
        }

        .meta .label {
            color: #555;
            font-weight: bold;
            width: 36mm;
        }

        .warning {
            border: 1.5px solid #333;
            margin: 5mm 0;
            padding: 3mm;
        }

        .list th,
        .list td {
            border: 1px solid #777;
            padding: 1.8mm 1.5mm;
            vertical-align: top;
        }

        .list th {
            background: #eeeeee;
            font-size: 7.8pt;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .check {
            border: 1.2px solid #111;
            display: inline-block;
            height: 3.5mm;
            width: 3.5mm;
        }

        .check-cell {
            text-align: center;
            width: 8mm;
        }

        .article {
            color: #555;
        }

        .group {
            page-break-inside: avoid;
            margin-top: 7mm;
        }

        .notes {
            border-bottom: 1px solid #777;
            height: 9mm;
            margin-top: 2mm;
        }

        .page-break {
            page-break-before: always;
        }

        .footer {
            color: #666;
            font-size: 7pt;
            margin-top: 7mm;
            text-align: right;
        }
    </style>
</head>
<body>

<h1>Kommissionierung</h1>
<div class="subtitle">
    <?= $escape($settings['camp_name']) ?> –
    <?= $escape($formatDate($deliveryDate)) ?>
</div>

<table class="meta">
    <tr>
        <td class="label">Liefertag</td>
        <td><?= $escape($formatDate($deliveryDate)) ?></td>
    </tr>
    <tr>
        <td class="label">Bestellschluss</td>
        <td><?= $escape($formatDeadline($cutoffStatus['deadline'])) ?></td>
    </tr>
    <tr>
        <td class="label">Bestätigte Gruppen</td>
        <td><?= (int) $submittedCount ?></td>
    </tr>
    <tr>
        <td class="label">Entwürfe / fehlend</td>
        <td><?= (int) $draftCount ?> / <?= (int) $missingCount ?></td>
    </tr>
</table>

<?php if ($draftGroups !== [] || $missingGroups !== []): ?>
    <div class="warning">
        <strong>Achtung: Die Kommissionierliste ist noch nicht vollständig.</strong>

        <?php if ($draftGroups !== []): ?>
            <div>
                Entwürfe: <?= $escape(implode(', ', $draftGroups)) ?>
            </div>
        <?php endif; ?>

        <?php if ($missingGroups !== []): ?>
            <div>
                Noch nicht bestellt: <?= $escape(implode(', ', $missingGroups)) ?>
            </div>
        <?php endif; ?>

        <div>
            Die Mengen enthalten nur definitiv bestätigte Bestellungen.
        </div>
    </div>
<?php endif; ?>

<h2>Sammelkontrolle</h2>

<table class="list">
    <thead>
        <tr>
            <th class="check-cell">OK</th>
            <th>Artikelnummer</th>
            <th>Produkt</th>
            <th>Einheit</th>
            <th class="right">Packungen gesamt</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($aggregateItems === []): ?>
        <tr>
            <td colspan="5">
                Keine bestätigten Bestellpositionen vorhanden.
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($aggregateItems as $item): ?>
            <tr>
                <td class="check-cell"><span class="check"></span></td>
                <td>
                    <?= $item['article_number'] === null
                        || $item['article_number'] === ''
                            ? '–'
                            : $escape($item['article_number']) ?>
                </td>
                <td><strong><?= $escape($item['product_name']) ?></strong></td>
                <td>
                    <?= $item['unit'] === null || $item['unit'] === ''
                        ? '–'
                        : $escape($item['unit']) ?>
                </td>
                <td class="right">
                    <strong><?= (int) $item['total_quantity'] ?></strong>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<div class="page-break"></div>

<h2>Kommissionierung nach Gruppe</h2>

<?php if ($submittedOrders === []): ?>
    <p>Es gibt noch keine definitiv bestätigten Bestellungen.</p>
<?php else: ?>
    <?php foreach ($submittedOrders as $submittedOrder): ?>
        <div class="group">
            <h3>
                <?= $escape($submittedOrder['group']['name']) ?>
                – <?= count($submittedOrder['items']) ?> Positionen
            </h3>

            <table class="list">
                <thead>
                    <tr>
                        <th class="check-cell">OK</th>
                        <th>Artikelnummer</th>
                        <th>Produkt</th>
                        <th>Einheit</th>
                        <th class="right">Packungen</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($submittedOrder['items'] as $item): ?>
                    <tr>
                        <td class="check-cell"><span class="check"></span></td>
                        <td>
                            <?= $item['article_number'] === null
                                || $item['article_number'] === ''
                                    ? '–'
                                    : $escape($item['article_number']) ?>
                        </td>
                        <td><strong><?= $escape($item['product_name']) ?></strong></td>
                        <td>
                            <?= $item['unit'] === null || $item['unit'] === ''
                                ? '–'
                                : $escape($item['unit']) ?>
                        </td>
                        <td class="right">
                            <strong><?= (int) $item['quantity'] ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <strong>Bemerkungen:</strong>
            <div class="notes"></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="footer">
    PDF erstellt am <?= $escape($generatedAt->format('d.m.Y H:i')) ?> Uhr
</div>

</body>
</html>
