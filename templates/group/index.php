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

$formatDateTime = static function (
    DateTimeImmutable $dateTime
): string {
    return $dateTime->format(
        'd.m.Y H:i'
    ) . ' Uhr';
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
        Bestellungen – <?= $escape($group['name']) ?>
    </title>

    <link rel="stylesheet" href="/assets/app.css">
</head>

<body>

<header class="topbar">

    <div class="topbar__inner">

        <a
            class="brand"
            href="/group/"
        >

            <span class="brand__mark">
                EB
            </span>

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
                <strong>
                    <?= $escape($group['name']) ?>
                </strong>
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

            <p class="eyebrow">
                Deine Bestellungen
            </p>

            <h1>Liefertage</h1>

            <p class="lead">
                Wähle einen offenen Liefertag, um eine Bestellung
                zu erfassen oder einen bestehenden Entwurf
                weiterzubearbeiten.
            </p>

        </div>

    </div>

    <div class="stats">

        <div class="stat">

            <span class="stat__label">
                Gesamtbudget Lager
            </span>

            <span class="stat__value">
                <?= $escape(
                    $formatMoney(
                        (int) $calculation['total_budget_cents']
                    )
                ) ?>
            </span>

        </div>

        <div class="stat">

            <span class="stat__label">
                Bestellschluss für den Folgetag
            </span>

            <span class="stat__value">
                <?= $escape(
                    substr(
                        (string) $settings['order_cutoff_time'],
                        0,
                        5
                    )
                ) ?>
                Uhr
            </span>

        </div>

    </div>

    <section class="panel">

        <div class="panel__header">

            <div>

                <h2>Übersicht</h2>

                <span class="small-muted">
                    Bestellschluss ist jeweils am Vortag.
                </span>

            </div>

        </div>

        <?php if ($days === []): ?>

            <div class="empty-state">
                Für diese Gruppe sind keine Liefertage mit
                Teilnehmern konfiguriert.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>
                            <th>Datum</th>
                            <th>Abschnitt</th>
                            <th>Tagesbudget</th>
                            <th>Übertrag / Rundung bisher</th>
                            <th>Bestellschluss</th>
                            <th>Status</th>
                            <th>Aktion</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($days as $entry): ?>

                        <?php
                        $day = $entry['budget_day'];
                        $balance = $entry['balance'];
                        $order = $entry['order'];
                        $cutoff = $entry['cutoff'];

                        $isOpen =
                            (bool) $cutoff['is_open'];

                        $target = null;
                        $linkLabel = null;

                        $badgeClass =
                            'badge--neutral';

                        if (
                            $order !== null
                            && $order['status'] === 'submitted'
                        ) {
                            $status = 'Bestätigt';

                            $badgeClass =
                                'badge--success';

                            $target =
                                '/group/review.php?date=';

                            $linkLabel =
                                'Bestellung anzeigen';
                        } elseif (!$isOpen) {
                            if ($order !== null) {
                                $status =
                                    'Entwurf – Frist vorbei';

                                $badgeClass =
                                    'badge--warning';

                                $target =
                                    '/group/review.php?date=';

                                $linkLabel =
                                    'Entwurf anzeigen';
                            } else {
                                $status =
                                    'Nicht bestellt';

                                $badgeClass =
                                    'badge--danger';
                            }
                        } elseif ($order !== null) {
                            $status = 'Entwurf';

                            $badgeClass =
                                'badge--warning';

                            $target =
                                '/group/order.php?date=';

                            $linkLabel =
                                'Entwurf fortsetzen';
                        } else {
                            $status = 'Offen';

                            $badgeClass =
                                'badge--info';

                            $target =
                                '/group/order.php?date=';

                            $linkLabel =
                                'Bestellung erfassen';
                        }
                        ?>

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
                                <?= $escape(
                                    implode(
                                        ' + ',
                                        $day['labels']
                                    )
                                ) ?>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoney(
                                        (int) $day['budget_cents']
                                    )
                                ) ?>
                            </td>

                            <td class="numeric">
                                <?= $escape(
                                    $formatMoney(
                                        (int) $balance['carryover_cents']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $formatDateTime(
                                        $cutoff['deadline']
                                    )
                                ) ?>
                            </td>

                            <td>

                                <span
                                    class="badge <?= $badgeClass ?>"
                                >
                                    <?= $escape($status) ?>
                                </span>

                            </td>

                            <td class="actions-cell">

                                <?php if (
                                    $target !== null
                                    && $linkLabel !== null
                                ): ?>

                                    <a
                                        class="button button--small"
                                        href="<?= $escape(
                                            $target
                                            . rawurlencode(
                                                (string) $day['date']
                                            )
                                        ) ?>"
                                    >
                                        <?= $escape($linkLabel) ?>
                                    </a>

                                <?php else: ?>

                                    <span
                                        class="badge badge--neutral"
                                    >
                                        Geschlossen
                                    </span>

                                <?php endif; ?>

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