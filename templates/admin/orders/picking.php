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

$campName = (string) (
    $settings['camp_name']
    ?? 'Essensbestellung'
);

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
        Kommissionierung – <?= $escape($deliveryDate) ?>
    </title>

    <style>
        :root {
            color-scheme: light;
        }

        body {
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.35;
            margin: 1.5rem auto;
            max-width: 1200px;
            padding: 0 1rem 3rem;
        }

        h1,
        h2,
        h3 {
            line-height: 1.2;
        }

        table {
            border-collapse: collapse;
            margin: 0 0 1.5rem;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #777;
            padding: 0.45rem 0.55rem;
            text-align: left;
            vertical-align: top;
        }

        th {
            font-weight: 700;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .controls {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .controls button,
        .controls a {
            font: inherit;
        }

        .summary {
            border: 1px solid #777;
            margin-bottom: 1.5rem;
            padding: 0.8rem 1rem;
        }

        .summary p {
            margin: 0.25rem 0;
        }

        .warning {
            border: 2px solid #111;
            margin: 1rem 0 1.5rem;
            padding: 0.8rem 1rem;
        }

        .warning ul {
            margin-bottom: 0;
        }

        .check-column {
            text-align: center;
            width: 3.5rem;
        }

        .check-box {
            border: 1.5px solid #111;
            display: inline-block;
            height: 1rem;
            width: 1rem;
        }

        .group-card {
            break-inside: avoid;
            margin-top: 2rem;
            page-break-inside: avoid;
        }

        .group-header {
            align-items: baseline;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            justify-content: space-between;
        }

        .group-header h3 {
            margin-bottom: 0.5rem;
        }

        .notes {
            border-bottom: 1px solid #777;
            height: 2.5rem;
            margin: 0.6rem 0 1.2rem;
        }

        .muted {
            color: #555;
        }

        @media print {
            @page {
                margin: 12mm;
                size: A4 portrait;
            }

            body {
                font-size: 10pt;
                margin: 0;
                max-width: none;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .summary,
            .warning {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .group-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="controls no-print">
    <a
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

<h1>Kommissionierung</h1>

<h2><?= $escape($campName) ?></h2>

<div class="summary">
    <p>
        <strong>Liefertag:</strong>
        <?= $escape($formatDate($deliveryDate)) ?>
    </p>

    <p>
        <strong>Bestellschluss:</strong>
        <?= $escape(
            $formatDeadline(
                $cutoffStatus['deadline']
            )
        ) ?>
    </p>

    <p>
        <strong>Bestätigte Gruppen:</strong>
        <?= (int) $submittedCount ?>
    </p>

    <p>
        <strong>Entwürfe:</strong>
        <?= (int) $draftCount ?>
        &nbsp;·&nbsp;
        <strong>Nicht bestellt:</strong>
        <?= (int) $missingCount ?>
    </p>
</div>

<?php if ($draftGroups !== [] || $missingGroups !== []): ?>
    <div class="warning">
        <strong>
            Achtung: Diese Druckliste ist noch nicht vollständig.
        </strong>

        <?php if ($draftGroups !== []): ?>
            <p>
                Folgende Gruppen haben nur einen Entwurf:
            </p>

            <ul>
                <?php foreach ($draftGroups as $groupName): ?>
                    <li><?= $escape($groupName) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($missingGroups !== []): ?>
            <p>
                Folgende Gruppen haben noch nicht bestellt:
            </p>

            <ul>
                <?php foreach ($missingGroups as $groupName): ?>
                    <li><?= $escape($groupName) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p>
            Die Mengen unten enthalten ausschliesslich definitiv
            bestätigte Bestellungen.
        </p>
    </div>
<?php endif; ?>

<h2>Sammelkontrolle</h2>

<p class="muted">
    Diese Liste eignet sich zur Kontrolle der gesamten angelieferten
    beziehungsweise bereitgestellten Packungen vor der Verteilung auf
    die einzelnen Gruppen.
</p>

<?php if ($aggregateItems === []): ?>
    <p>
        Für diesen Liefertag gibt es noch keine bestätigten
        Bestellpositionen.
    </p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th class="check-column">OK</th>
                <th>Artikelnummer</th>
                <th>Produkt</th>
                <th>Einheit</th>
                <th class="number">Packungen gesamt</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($aggregateItems as $item): ?>
            <tr>
                <td class="check-column">
                    <span class="check-box"></span>
                </td>

                <td>
                    <?= $item['article_number'] === null
                        || $item['article_number'] === ''
                            ? '–'
                            : $escape($item['article_number']) ?>
                </td>

                <td>
                    <?= $escape($item['product_name']) ?>
                </td>

                <td>
                    <?= $item['unit'] === null
                        || $item['unit'] === ''
                            ? '–'
                            : $escape($item['unit']) ?>
                </td>

                <td class="number">
                    <strong>
                        <?= (int) $item['total_quantity'] ?>
                    </strong>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Kommissionierung nach Gruppe</h2>

<?php if ($submittedOrders === []): ?>
    <p>
        Es gibt noch keine definitiv bestätigten Bestellungen.
    </p>
<?php else: ?>
    <?php foreach ($submittedOrders as $submittedOrder): ?>
        <section class="group-card">
            <div class="group-header">
                <h3>
                    <?= $escape(
                        $submittedOrder['group']['name']
                    ) ?>
                </h3>

                <span>
                    Positionen:
                    <strong>
                        <?= count($submittedOrder['items']) ?>
                    </strong>
                </span>
            </div>

            <?php if ($submittedOrder['items'] === []): ?>
                <p>
                    Diese bestätigte Bestellung enthält keine
                    Positionen.
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th class="check-column">OK</th>
                            <th>Artikelnummer</th>
                            <th>Produkt</th>
                            <th>Einheit</th>
                            <th class="number">Packungen</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach (
                        $submittedOrder['items']
                        as $item
                    ): ?>
                        <tr>
                            <td class="check-column">
                                <span class="check-box"></span>
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
                                <?= $escape(
                                    $item['product_name']
                                ) ?>
                            </td>

                            <td>
                                <?= $item['unit'] === null
                                    || $item['unit'] === ''
                                        ? '–'
                                        : $escape(
                                            $item['unit']
                                        ) ?>
                            </td>

                            <td class="number">
                                <strong>
                                    <?= (int) $item['quantity'] ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p><strong>Bemerkungen:</strong></p>
            <div class="notes"></div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>