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

    return $weekdays[(int) $parsedDate->format('N')]
        . ', '
        . $parsedDate->format('d.m.Y');
};

$formatDateTime = static function (
    DateTimeImmutable $dateTime
): string {
    return $dateTime->format('d.m.Y H:i') . ' Uhr';
};

$isSubmitted =
    $summary['order']['status'] === 'submitted';

$isOrderingOpen =
    (bool) $cutoffStatus['is_open'];

$remainingBudgetCents =
    (int) $summary['remaining_budget_cents'];

$statusLabel = $isSubmitted
    ? 'Bestätigt'
    : 'Entwurf';

$statusClass = $isSubmitted
    ? 'badge--success'
    : 'badge--warning';
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

    <link rel="stylesheet" href="/assets/app.css">
    <link rel="stylesheet" href="/assets/public.css">
</head>

<body>

<header class="topbar">
    <div class="topbar__inner">
        <a class="brand" href="/group/">
            <span class="brand__mark">EB</span>

            <span class="brand__text">
                <span class="brand__title">
                    <?= $escape($settings['camp_name']) ?>
                </span>

                <span class="brand__subtitle">
                    Gruppenbestellung
                </span>
            </span>
        </a>

        <div class="topbar__actions">
            <span class="topbar__identity">
                Angemeldet als
                <strong><?= $escape($group['name']) ?></strong>
            </span>

            <form
                class="inline-form"
                method="post"
                action="/group/logout.php"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $escape($csrfToken) ?>"
                >

                <button
                    class="button--secondary button--small"
                    type="submit"
                >
                    Abmelden
                </button>
            </form>
        </div>
    </div>
</header>

<main class="app-container">

    <div class="page-header">
        <div class="page-header__copy">
            <p class="eyebrow">Bestellung prüfen</p>

            <h1><?= $escape($formatDate($deliveryDate)) ?></h1>

            <p class="lead">
                Kontrolliere die Bestellung vor der definitiven Bestätigung.
            </p>
        </div>

        <a
            class="button button--secondary"
            href="/group/"
        >
            Zurück zu den Liefertagen
        </a>
    </div>

    <div class="review-header-grid">
        <div class="stat">
            <span class="stat__label">Status</span>
            <span class="stat__value">
                <span class="badge <?= $statusClass ?>">
                    <?= $escape($statusLabel) ?>
                </span>
            </span>
        </div>

        <div class="stat">
            <span class="stat__label">Bestellschluss</span>
            <span class="stat__value">
                <?= $escape(
                    $formatDateTime(
                        $cutoffStatus['deadline']
                    )
                ) ?>
            </span>
        </div>

        <div class="stat stat--success">
            <span class="stat__label">Tagesbudget</span>
            <span class="stat__value">
                <?= $escape(
                    $formatMoneyCents(
                        (int) $summary['budget_cents']
                    )
                ) ?>
            </span>
        </div>

        <div class="stat">

            <span class="stat__label">
                Übertrag / Rundung bisher
            </span>

            <span class="stat__value">
                <?= $escape(
                    $formatMoneyCents(
                        (int) $budgetBalance[
                            'carryover_cents'
                        ]
                    )
                ) ?>
            </span>

        </div>

        <div class="stat">

            <span class="stat__label">
                Gesamtbudget Lager
            </span>

            <span class="stat__value">
                <?= $escape(
                    $formatMoneyCents(
                        (int) $budgetBalance[
                            'total_budget_cents'
                        ]
                    )
                ) ?>
            </span>

        </div>

        <div class="stat">
            <span class="stat__label">Warenwert</span>
            <span class="stat__value">
                <?= $escape(
                    $formatMoneyCents(
                        (int) $summary['total_cents']
                    )
                ) ?>
            </span>
        </div>

        <div class="stat">

            <span class="stat__label">
                Rundung / Kostenkorrektur
            </span>

            <span class="stat__value">
                <?= $escape(
                    $formatMoneyCents(
                        (int) $summary[
                            'rounding_cents'
                        ]
                    )
                ) ?>
            </span>

        </div>

        <div class="stat">

            <span class="stat__label">
                Effektive Kosten
            </span>

            <span class="stat__value">
                <?= $escape(
                    $formatMoneyCents(
                        (int) $summary[
                            'effective_total_cents'
                        ]
                    )
                ) ?>
            </span>

        </div>

        <div class="stat <?= $remainingBudgetCents < 0 ? 'stat--danger' : '' ?>">
            <span class="stat__label">Verbleibend</span>
            <span class="stat__value">
                <?= $escape(
                    $formatMoneyCents(
                        $remainingBudgetCents
                    )
                ) ?>
            </span>
        </div>
    </div>

    <?php if ($submitted): ?>
        <div class="alert alert--success">
            <strong>Bestellung bestätigt.</strong>
            Die Bestellung wurde definitiv übermittelt.
        </div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <div class="alert alert--danger">
            <strong><?= $escape($error) ?></strong>
        </div>
    <?php endif; ?>

    <?php if (
        !$isSubmitted
        && !$isOrderingOpen
    ): ?>
        <div class="alert alert--warning">
            <strong>Bestellschluss vorbei.</strong>
            Dieser Entwurf bleibt sichtbar, kann aber nicht mehr bearbeitet
            oder definitiv bestätigt werden.
        </div>
    <?php endif; ?>

    <?php if ($remainingBudgetCents < 0): ?>
        <div class="alert alert--danger">
            <strong>Tagesbudget überschritten.</strong>
            Die Bestellung liegt um
            <?= $escape(
                $formatMoneyCents(
                    abs($remainingBudgetCents)
                )
            ) ?>
            über dem berechneten Tagesbudget.
        </div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel__header">
            <div>
                <h2>Bestellpositionen</h2>
                <span class="small-muted">
                    <?= count($summary['items']) ?> ausgewählte Produkte
                </span>
            </div>
        </div>

        <?php if ($summary['items'] === []): ?>
            <div class="empty-state">
                Die Bestellung enthält noch keine Produkte.
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Produkt</th>
                            <th>Einheit</th>
                            <th>Packungen</th>
                            <th>Preis / Packung</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($summary['items'] as $item): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= $escape($item['product_name']) ?>
                                </strong>

                                <?php if (
                                    $item['article_number'] !== null
                                    && $item['article_number'] !== ''
                                ): ?>
                                    <br>
                                    <span class="small-muted">
                                        Art.-Nr.
                                        <?= $escape($item['article_number']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= $item['unit'] === null
                                    || $item['unit'] === ''
                                        ? '–'
                                        : $escape($item['unit']) ?>
                            </td>

                            <td class="numeric">
                                <strong>
                                    <?= (int) $item['quantity'] ?>
                                </strong>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoneyAmount(
                                        $item['unit_price']
                                    )
                                ) ?>
                            </td>

                            <td class="numeric">
                                <strong>
                                    <?= $escape(
                                        $formatMoneyAmount(
                                            $item['line_total_amount']
                                        )
                                    ) ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel panel--soft">
        <div class="review-actions">

            <?php if (
                !$isSubmitted
                && $isOrderingOpen
            ): ?>

                <a
                    class="button button--secondary"
                    href="/group/order.php?date=<?= $escape(
                        rawurlencode($deliveryDate)
                    ) ?>"
                >
                    Bestellung bearbeiten
                </a>

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
                <?php endif; ?>

            <?php elseif ($isSubmitted): ?>

                <span class="badge badge--success">
                    Bestellung abgeschlossen
                </span>

            <?php else: ?>

                <span class="badge badge--neutral">
                    Bestellung geschlossen
                </span>

            <?php endif; ?>

        </div>

        <?php if (
            !$isSubmitted
            && $isOrderingOpen
            && $summary['items'] !== []
        ): ?>
            <p class="review-note">
                Nach der definitiven Bestätigung kann die Gruppe diese
                Bestellung nicht mehr verändern. Die Administration kann
                bei Bedarf weiterhin Korrekturen vornehmen.
            </p>
        <?php elseif ($isSubmitted): ?>
            <p class="review-note">
                Diese Bestellung ist abgeschlossen und für die Gruppe
                schreibgeschützt.
            </p>
        <?php endif; ?>
    </section>

</main>

</body>
</html>
