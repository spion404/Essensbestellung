<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
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

$formatMoneyAmount =
    static function (mixed $amount): string {
        return 'CHF ' . number_format(
            (float) $amount,
            2,
            '.',
            "'"
        );
    };

$moneyToCents =
    static function (string $amount): int {
        [$wholePart, $decimalPart] =
            array_pad(
                explode('.', $amount, 2),
                2,
                ''
            );

        $decimalPart =
            str_pad(
                $decimalPart,
                2,
                '0'
            );

        return ((int) $wholePart * 100)
            + (int) $decimalPart;
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

$orphanTotalCents = 0;

foreach ($orphanItems as $item) {
    $orphanTotalCents +=
        $moneyToCents(
            (string) $item['unit_price']
        )
        * (int) $item['quantity'];
}

$isSubmitted =
    $existingOrder !== null
    && $existingOrder['status'] === 'submitted';

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
        Bestellung bearbeiten –
        <?= $escape($group['name']) ?>
    </title>

    <style>
        body {
            font-family: sans-serif;
            line-height: 1.45;
            margin: 2rem auto;
            max-width: 1200px;
            padding: 0 1rem;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #ccc;
            padding: 0.6rem;
            text-align: left;
            vertical-align: top;
        }

        .warning {
            border: 1px solid #999;
            padding: 0.75rem;
        }

        .product-search {
            box-sizing: border-box;
            margin: 1rem 0;
            max-width: 30rem;
            padding: 0.5rem;
            width: 100%;
        }

        .quantity-input {
            max-width: 7rem;
            padding: 0.4rem;
            width: 100%;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>

<p>
    <a
        href="/admin/orders/day.php?date=<?= $escape(
            rawurlencode($deliveryDate)
        ) ?>"
    >
        Zurück zur Tagesauswertung
    </a>
</p>

<h1>Bestellung bearbeiten</h1>

<h2>
    <?= $escape($group['name']) ?>
</h2>

<p>
    <strong>
        <?= $escape(
            $formatDate($deliveryDate)
        ) ?>
    </strong>
</p>

<p>
    Status:
    <strong>
        <?php if ($existingOrder === null): ?>
            Noch keine Bestellung
        <?php elseif ($isSubmitted): ?>
            Bestätigt
        <?php else: ?>
            Entwurf
        <?php endif; ?>
    </strong>
</p>

<p>
    Tagesbudget:
    <strong>
        <?= $escape(
            $formatMoneyCents(
                (int) $budgetDay['budget_cents']
            )
        ) ?>
    </strong>
</p>

<?php if (!$cutoffStatus['is_open']): ?>

    <p class="warning">
        <strong>Admin-Override:</strong>
        Der Bestellschluss ist bereits vorbei.
        Änderungen sind auf dieser Seite trotzdem
        möglich, weil du im geschützten
        Administrationsbereich arbeitest.
    </p>

<?php endif; ?>

<?php if ($isSubmitted): ?>

    <p class="warning">
        Diese Bestellung ist bereits bestätigt.
        Gespeicherte Änderungen wirken deshalb
        sofort auf die Sammelbestellung.
    </p>

<?php endif; ?>

<?php if ($saved): ?>

    <p>
        <strong>
            Die Änderungen wurden gespeichert.
        </strong>
    </p>

<?php endif; ?>

<?php if ($submitted): ?>

    <p>
        <strong>
            Die Bestellung wurde gespeichert
            und bestätigt.
        </strong>
    </p>

<?php endif; ?>

<?php if ($error !== null): ?>

    <p>
        <strong>
            <?= $escape($error) ?>
        </strong>
    </p>

<?php endif; ?>

<?php if ($orphanItems !== []): ?>

    <h3>Nicht mehr im Produktkatalog</h3>

    <p>
        Diese Positionen stammen aus dem
        Bestell-Snapshot und bleiben bei einer
        Korrektur unverändert erhalten.
    </p>

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

        <?php foreach ($orphanItems as $item): ?>
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

                <td>
                    <?= (int) $item['quantity'] ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoneyAmount(
                            $item['unit_price']
                        )
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoneyAmount(
                            $item['line_total_amount']
                        )
                    ) ?>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

<?php endif; ?>

<h3>Produkte</h3>

<input
    class="product-search"
    type="search"
    id="product-search"
    placeholder="Produkt, Artikelnummer oder Kategorie suchen …"
    autocomplete="off"
>

<form
    method="post"
    action="/admin/orders/edit.php?group_id=<?= $groupId ?>&amp;date=<?= $escape(
        rawurlencode($deliveryDate)
    ) ?>"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= $escape($adminCsrfToken) ?>"
    >

    <input
        type="hidden"
        name="group_id"
        value="<?= $groupId ?>"
    >

    <input
        type="hidden"
        name="delivery_date"
        value="<?= $escape($deliveryDate) ?>"
    >

    <table>
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Einheit</th>
                <th>Bestellpreis</th>
                <th>Packungen</th>
                <th>Positionswert</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($products as $product): ?>
            <?php
            $productId =
                (int) $product['id'];

            $existingItem =
                $existingItemsByProductId[
                    $productId
                ]
                ?? null;

            $effectivePrice =
                $existingItem !== null
                    ? (string) $existingItem[
                        'unit_price'
                    ]
                    : (string) $product['price'];

            $effectivePriceCents =
                $moneyToCents(
                    $effectivePrice
                );

            $quantity =
                $quantities[
                    (string) $productId
                ]
                ?? '';

            $searchText =
                implode(
                    ' ',
                    [
                        (string) $product['name'],

                        (string) (
                            $product['article_number']
                            ?? ''
                        ),

                        (string) (
                            $product['categories']
                            ?? ''
                        ),

                        (string) (
                            $product['remark']
                            ?? ''
                        ),
                    ]
                );
            ?>

            <tr
                data-product-row
                data-search="<?= $escape($searchText) ?>"
            >
                <td>
                    <strong>
                        <?= $escape(
                            $product['name']
                        ) ?>
                    </strong>

                    <?php if (
                        $product['article_number'] !== null
                        && $product['article_number'] !== ''
                    ): ?>
                        <br>

                        <small>
                            Art.-Nr.
                            <?= $escape(
                                $product['article_number']
                            ) ?>
                        </small>
                    <?php endif; ?>

                    <?php if (
                        $product['categories'] !== null
                        && $product['categories'] !== ''
                    ): ?>
                        <br>

                        <small>
                            <?= $escape(
                                $product['categories']
                            ) ?>
                        </small>
                    <?php endif; ?>
                </td>

                <td>
                    <?= $product['unit'] === null
                        || $product['unit'] === ''
                            ? '–'
                            : $escape(
                                $product['unit']
                            ) ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoneyAmount(
                            $effectivePrice
                        )
                    ) ?>

                    <?php if (
                        $existingItem !== null
                        && (string) $product['price']
                            !== $effectivePrice
                    ): ?>
                        <br>

                        <small>
                            Aktueller Katalogpreis:
                            <?= $escape(
                                $formatMoneyAmount(
                                    $product['price']
                                )
                            ) ?>
                        </small>
                    <?php endif; ?>
                </td>

                <td>
                    <input
                        class="quantity-input"
                        type="number"
                        name="quantities[<?= $productId ?>]"
                        value="<?= $escape($quantity) ?>"
                        min="0"
                        step="1"
                        inputmode="numeric"
                        data-price-cents="<?= $effectivePriceCents ?>"
                    >
                </td>

                <td data-line-total>
                    CHF 0.00
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

    <h3>Budgetkontrolle</h3>

    <p>
        Warenwert:
        <strong id="order-total">
            CHF 0.00
        </strong>
    </p>

    <p>
        Verbleibendes Tagesbudget:
        <strong id="remaining-budget">
            <?= $escape(
                $formatMoneyCents(
                    (int) $budgetDay['budget_cents']
                )
            ) ?>
        </strong>
    </p>

    <p id="budget-warning" hidden>
        <strong>
            Achtung: Das Tagesbudget wird überschritten.
        </strong>
    </p>

    <div class="actions">
        <button
            type="submit"
            name="action"
            value="save"
        >
            Änderungen speichern
        </button>

        <?php if (!$isSubmitted): ?>
            <button
                type="submit"
                name="action"
                value="submit"
            >
                Speichern und definitiv bestätigen
            </button>
        <?php endif; ?>
    </div>
</form>

<script>
    (() => {
        const budgetCents =
            <?= (int) $budgetDay['budget_cents'] ?>;

        const orphanTotalCents =
            <?= $orphanTotalCents ?>;

        const searchInput =
            document.getElementById(
                'product-search'
            );

        const rows = Array.from(
            document.querySelectorAll(
                '[data-product-row]'
            )
        );

        const inputs = rows.map(
            (row) => row.querySelector(
                'input[data-price-cents]'
            )
        );

        const totalElement =
            document.getElementById(
                'order-total'
            );

        const remainingElement =
            document.getElementById(
                'remaining-budget'
            );

        const warningElement =
            document.getElementById(
                'budget-warning'
            );

        const formatter =
            new Intl.NumberFormat(
                'de-CH',
                {
                    style: 'currency',
                    currency: 'CHF',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );

        const getQuantity = (input) => {
            const quantity =
                Number(input.value);

            if (
                !Number.isInteger(quantity)
                || quantity <= 0
            ) {
                return 0;
            }

            return quantity;
        };

        const lineTotal = (input) => {
            return Number(
                input.dataset.priceCents
            ) * getQuantity(input);
        };

        const update = () => {
            let totalCents =
                orphanTotalCents;

            inputs.forEach((input) => {
                const value =
                    lineTotal(input);

                totalCents += value;

                const row =
                    input.closest(
                        '[data-product-row]'
                    );

                const lineElement =
                    row.querySelector(
                        '[data-line-total]'
                    );

                lineElement.textContent =
                    formatter.format(
                        value / 100
                    );
            });

            const remainingCents =
                budgetCents - totalCents;

            totalElement.textContent =
                formatter.format(
                    totalCents / 100
                );

            remainingElement.textContent =
                formatter.format(
                    remainingCents / 100
                );

            warningElement.hidden =
                remainingCents >= 0;
        };

        inputs.forEach((input) => {
            input.addEventListener(
                'input',
                update
            );
        });

        searchInput.addEventListener(
            'input',
            () => {
                const term =
                    searchInput.value
                        .trim()
                        .toLocaleLowerCase(
                            'de-CH'
                        );

                rows.forEach((row) => {
                    const searchText =
                        String(
                            row.dataset.search
                            || ''
                        ).toLocaleLowerCase(
                            'de-CH'
                        );

                    row.hidden =
                        term !== ''
                        && !searchText.includes(
                            term
                        );
                });
            }
        );

        update();
    })();
</script>

</body>
</html>