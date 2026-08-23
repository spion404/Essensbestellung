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
        Bestellungen – Administration
    </title>

    <link rel="stylesheet" href="/assets/app.css">
</head>

<body>

<header class="topbar">

    <div class="topbar__inner">

        <a
            class="brand"
            href="/admin/orders.php"
        >

            <span class="brand__mark">
                EB
            </span>

            <span class="brand__text">

                <span class="brand__title">
                    <?= $escape($settings['camp_name']) ?>
                </span>

                <span class="brand__subtitle">
                    Administration
                </span>

            </span>

        </a>

        <div class="topbar__actions">

            <nav
                class="nav"
                aria-label="Administration"
            >

                <a
                    href="/admin/orders.php"
                    aria-current="page"
                >
                    Bestellungen
                </a>

                <a href="/admin/groups.php">
                    Gruppen
                </a>

                <a href="/admin/products.php">
                    Produkte
                </a>

                <a href="/admin/categories.php">
                    Kategorien
                </a>

                <a href="/admin/settings.php">
                    Einstellungen
                </a>

            </nav>

            <form
                class="inline-form"
                method="post"
                action="/admin/logout.php"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= $escape($adminCsrfToken) ?>"
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

            <p class="eyebrow">
                Administration
            </p>

            <h1>Bestellungen</h1>

            <p class="lead">
                Überblick über Liefertage, Gruppenstatus und alle
                gespeicherten Bestellungen.
            </p>

        </div>

    </div>

    <section class="panel">

        <div class="panel__header">

            <div>

                <h2>Tagesauswertungen</h2>

                <span class="small-muted">
                    Status aller erwarteten Gruppen pro Liefertag.
                </span>

            </div>

        </div>

        <?php if ($deliveryDays === []): ?>

            <div class="empty-state">
                Es sind noch keine Liefertage mit Teilnehmern
                konfiguriert.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>
                            <th>Liefertag</th>
                            <th>Gruppen</th>
                            <th>Bestätigt</th>
                            <th>Entwürfe</th>
                            <th>Fehlend</th>
                            <th>Aktion</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($deliveryDays as $day): ?>

                        <tr>

                            <td>

                                <strong>
                                    <?= $escape(
                                        $formatDate(
                                            (string) $day['date']
                                        )
                                    ) ?>
                                </strong>

                            </td>

                            <td>
                                <?= (int) $day['expected_groups'] ?>
                            </td>

                            <td>

                                <span
                                    class="badge badge--success"
                                >
                                    <?= (int) $day['submitted_orders'] ?>
                                </span>

                            </td>

                            <td>

                                <span
                                    class="badge badge--warning"
                                >
                                    <?= (int) $day['draft_orders'] ?>
                                </span>

                            </td>

                            <td>

                                <span
                                    class="badge <?=
                                        (int) $day['missing_orders'] > 0
                                            ? 'badge--danger'
                                            : 'badge--neutral'
                                    ?>"
                                >
                                    <?= (int) $day['missing_orders'] ?>
                                </span>

                            </td>

                            <td class="actions-cell">

                                <a
                                    class="button button--small"
                                    href="/admin/orders/day.php?date=<?= $escape(
                                        rawurlencode(
                                            (string) $day['date']
                                        )
                                    ) ?>"
                                >
                                    Tagesauswertung
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

    <section class="panel">

        <div class="panel__header">

            <div>

                <h2>
                    Alle gespeicherten Bestellungen
                </h2>

                <span class="small-muted">
                    Entwürfe und bestätigte Bestellungen aller Tage.
                </span>

            </div>

        </div>

        <?php if ($orders === []): ?>

            <div class="empty-state">
                Es wurden noch keine Bestellungen gespeichert.
            </div>

        <?php else: ?>

            <div class="table-wrap">

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

                        <?php
                        $statusClass =
                            $order['status'] === 'submitted'
                                ? 'badge--success'
                                : 'badge--warning';
                        ?>

                        <tr>

                            <td>
                                <?= $escape(
                                    $formatDate(
                                        (string) $order[
                                            'delivery_date'
                                        ]
                                    )
                                ) ?>
                            </td>

                            <td>

                                <strong>
                                    <?= $escape(
                                        $order['group_name']
                                    ) ?>
                                </strong>

                            </td>

                            <td>

                                <span
                                    class="badge <?= $statusClass ?>"
                                >
                                    <?= $escape(
                                        $statusLabels[
                                            $order['status']
                                        ]
                                        ?? $order['status']
                                    ) ?>
                                </span>

                            </td>

                            <td>
                                <?= (int) $order['item_count'] ?>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoney(
                                        $order['total_amount']
                                    )
                                ) ?>
                            </td>

                            <td class="actions-cell">

                                <div class="toolbar">

                                    <a
                                        class="button button--secondary button--small"
                                        href="/admin/orders/view.php?id=<?= (int) $order['id'] ?>"
                                    >
                                        Anzeigen
                                    </a>

                                    <a
                                        class="button button--small"
                                        href="/admin/orders/edit.php?group_id=<?= (int) $order['group_id'] ?>&amp;date=<?= $escape(
                                            rawurlencode(
                                                (string) $order[
                                                    'delivery_date'
                                                ]
                                            )
                                        ) ?>"
                                    >
                                        Bearbeiten
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>