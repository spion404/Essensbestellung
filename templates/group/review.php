<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatMoneyCents = static function (int $cents): string {
    return 'CHF ' . number_format(
        $cents / 100,
        2,
        '.',
        "'"
    );
};

$formatMoneyAmount = static function (mixed $amount): string {
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

    return $weekdays[
        (int) $parsedDate->format('N')
    ]
        . ', '
        . $parsedDate->format('d.m.Y');
};

$formatQuantity = static function (string $quantity): string {
    return rtrim(
        rtrim(
            $quantity,
            '0'
        ),
        '.'
    );
};

$isSubmitted =
    $summary['order']['status'] === 'submitted';

$remainingBudgetCents =
    (int) $summary['remaining_budget_cents'];

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
        Bestellung prüfen – <?= $escape($group['name']) ?>
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

<?php if ($submitted): ?>
    <p>
        <strong>
            Die Bestellung wurde definitiv bestätigt.
        </strong>
    </p>
<?php endif; ?>

<?php if ($error !== null): ?>
    <p>
        <strong><?= $escape($error) ?></strong>
    </p>
<?php endif; ?>

<p>
    Status:
    <strong>
        <?= $isSubmitted
            ? 'Bestätigt'
            : 'Entwurf' ?>
    </strong>
</p>

<?php if ($summary['items'] === []): ?>

    <p>
        Die Bestellung enthält noch keine Produkte.
    </p>

<?php else: ?>

    <table>
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Einheit</th>
                <th>Menge</th>
                <th>Preis</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($summary['items'] as $item): ?>
            <tr>
                <td>
                    <?= $escape($item['product_name']) ?>

                    <?php if (
                        $item['article_number'] !== null
                        && $item['article_number'] !== ''
                    ): ?>
                        <br>
                        <small>
                            Art.-Nr.
                            <?= $escape(
                                $item['article_number']
                            ) ?>
                        </small>
                    <?php endif; ?>
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
                        $formatQuantity(
                            (string) $item['quantity']
                        )
                    ) ?>
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

<h3>Budget</h3>

<dl>
    <dt>Tagesbudget</dt>
    <dd>
        <?= $escape(
            $formatMoneyCents(
                (int) $summary['budget_cents']
            )
        ) ?>
    </dd>

    <dt>Warenwert</dt>
    <dd>
        <?= $escape(
            $formatMoneyCents(
                (int) $summary['total_cents']
            )
        ) ?>
    </dd>

    <dt>Verbleibend</dt>
    <dd>
        <?= $escape(
            $formatMoneyCents(
                $remainingBudgetCents
            )
        ) ?>
    </dd>
</dl>

<?php if ($remainingBudgetCents < 0): ?>
    <p>
        <strong>
            Achtung: Das Tagesbudget wird um
            <?= $escape(
                $formatMoneyCents(
                    abs($remainingBudgetCents)
                )
            ) ?>
            überschritten.
        </strong>
    </p>
<?php endif; ?>

<?php if (!$isSubmitted): ?>

    <p>
        <a
            href="/group/order.php?date=<?= $escape(
                rawurlencode($deliveryDate)
            ) ?>"
        >
            Bestellung bearbeiten
        </a>
    </p>

    <?php if ($summary['items'] !== []): ?>
        <form
            method="post"
            action="/group/review.php?date=<?= $escape(
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

            <button
                type="submit"
                name="action"
                value="submit"
            >
                Bestellung definitiv bestätigen
            </button>
        </form>

        <p>
            Nach der Bestätigung kann die Gruppe diese Bestellung
            nicht mehr verändern.
        </p>
    <?php endif; ?>

<?php else: ?>

    <p>
        Diese Bestellung ist abgeschlossen und kann von der Gruppe
        nicht mehr bearbeitet werden.
    </p>

<?php endif; ?>

</body>
</html>