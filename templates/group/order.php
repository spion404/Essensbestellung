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

$formatPrice = static function (string $amount): string {
    return 'CHF ' . number_format(
        (float) $amount,
        2,
        '.',
        "'"
    );
};

$moneyToCents = static function (string $amount): int {
    [$wholePart, $decimalPart] = array_pad(
        explode('.', $amount, 2),
        2,
        ''
    );

    $decimalPart = str_pad(
        $decimalPart,
        2,
        '0'
    );

    return ((int) $wholePart * 100)
        + (int) $decimalPart;
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

    return $weekdays[
        (int) $parsedDate->format('N')
    ]
        . ', '
        . $parsedDate->format('d.m.Y');
};

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
        Bestellung – <?= $escape($group['name']) ?>
    </title>
</head>

<body>

<p>
    <a href="/group/">
        Zurück zu den Liefertagen
    </a>
</p>

<h1><?= $escape($settings['camp_name']) ?></h1>

<h2>
    Bestellung:
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
    Abschnitt:
    <?= $escape(
        implode(
            ' + ',
            $budgetDay['labels']
        )
    ) ?>
</p>

<ul>
    <li>
        Ganze Personen:
        <?= (int) $budgetDay['full_participants'] ?>
    </li>
    <li>
        Halbe Personen:
        <?= (int) $budgetDay['half_participants'] ?>
    </li>
    <li>
        Besucher:
        <?= (int) $budgetDay['visitor_participants'] ?>
    </li>
    <li>
        Tagesbudget:
        <strong>
            <?= $escape(
                $formatMoney(
                    (int) $budgetDay['budget_cents']
                )
            ) ?>
        </strong>
    </li>
</ul>

<?php if ($saved): ?>
    <p>
        <strong>
            Der Entwurf wurde gespeichert.
        </strong>
    </p>
<?php endif; ?>

<?php if ($error !== null): ?>
    <p>
        <strong><?= $escape($error) ?></strong>
    </p>
<?php endif; ?>

<?php if ($products === []): ?>

    <p>
        Es sind noch keine Produkte verfügbar.
    </p>

<?php else: ?>

    <form
        method="post"
        action="/group/order.php?date=<?= $escape(
            rawurlencode($deliveryDate)
        ) ?>"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= $escape($csrfToken) ?>"
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
                    <th>Kategorien</th>
                    <th>Bemerkung</th>
                    <th>Einheit</th>
                    <th>Preis</th>
                    <th>Menge</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($products as $product): ?>
                <?php
                $productId =
                    (string) (int) $product['id'];

                $quantity =
                    $quantities[$productId]
                    ?? '';

                $priceCents = $moneyToCents(
                    (string) $product['price']
                );
                ?>

                <tr>
                    <td>
                        <?= $escape($product['name']) ?>

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
                    </td>

                    <td>
                        <?php if (
                            $product['categories'] === null
                            || $product['categories'] === ''
                        ): ?>
                            –
                        <?php else: ?>
                            <?= $escape(
                                $product['categories']
                            ) ?>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (
                            $product['remark'] === null
                            || $product['remark'] === ''
                        ): ?>
                            –
                        <?php else: ?>
                            <?= $escape(
                                $product['remark']
                            ) ?>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (
                            $product['unit'] === null
                            || $product['unit'] === ''
                        ): ?>
                            –
                        <?php else: ?>
                            <?= $escape(
                                $product['unit']
                            ) ?>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?= $escape(
                            $formatPrice(
                                (string) $product['price']
                            )
                        ) ?>
                    </td>

                    <td>
                        <input
                            type="number"
                            name="quantities[<?= (int) $product['id'] ?>]"
                            value="<?= $escape($quantity) ?>"
                            min="0"
                            step="0.001"
                            inputmode="decimal"
                            data-price-cents="<?= $priceCents ?>"
                        >
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
                    $formatMoney(
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

        <p>
            <button
                type="submit"
                name="action"
                value="save"
            >
                Entwurf speichern
            </button>

            <button
                type="submit"
                name="action"
                value="review"
            >
                Bestellung prüfen
            </button>
        </p>
    </form>

    <script>
        (() => {
            const budgetCents =
                <?= (int) $budgetDay['budget_cents'] ?>;

            const inputs = document.querySelectorAll(
                'input[data-price-cents]'
            );

            const totalElement =
                document.getElementById('order-total');

            const remainingElement =
                document.getElementById(
                    'remaining-budget'
                );

            const warningElement =
                document.getElementById(
                    'budget-warning'
                );

            const formatter = new Intl.NumberFormat(
                'de-CH',
                {
                    style: 'currency',
                    currency: 'CHF',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );

            const updateBudget = () => {
                let totalCents = 0;

                inputs.forEach((input) => {
                    const priceCents = Number(
                        input.dataset.priceCents
                    );

                    const quantity = Number(
                        input.value || 0
                    );

                    if (
                        !Number.isFinite(quantity)
                        || quantity <= 0
                    ) {
                        return;
                    }

                    totalCents += Math.round(
                        priceCents * quantity
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
                    updateBudget
                );
            });

            updateBudget();
        })();
    </script>

<?php endif; ?>

</body>
</html>