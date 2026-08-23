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
</head>

<body>

<h1><?= $escape($settings['camp_name']) ?></h1>

<p>
    Angemeldet als
    <strong><?= $escape($group['name']) ?></strong>
</p>

<form
    method="post"
    action="/group/logout.php"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= $escape($csrfToken) ?>"
    >

    <button type="submit">
        Abmelden
    </button>
</form>

<h2>Liefertage</h2>

<p>
    Lagerbudget dieser Gruppe:
    <strong>
        <?= $escape(
            $formatMoney(
                (int) $calculation['total_budget_cents']
            )
        ) ?>
    </strong>
</p>

<p>
    Bestellschluss ist jeweils am Vortag um
    <strong>
        <?= $escape(
            substr(
                (string) $settings['order_cutoff_time'],
                0,
                5
            )
        ) ?>
        Uhr
    </strong>.
</p>

<?php if ($days === []): ?>

    <p>
        Für diese Gruppe sind keine Liefertage mit
        Teilnehmern konfiguriert.
    </p>

<?php else: ?>

    <table>
        <thead>
            <tr>
                <th>Datum</th>
                <th>Abschnitt</th>
                <th>Tagesbudget</th>
                <th>Bestellschluss</th>
                <th>Status</th>
                <th>Aktion</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($days as $entry): ?>
            <?php
            $day = $entry['budget_day'];
            $order = $entry['order'];
            $cutoff = $entry['cutoff'];

            $isOpen =
                (bool) $cutoff['is_open'];

            $target = null;
            $linkLabel = null;

            if (
                $order !== null
                && $order['status'] === 'submitted'
            ) {
                $status = 'Bestätigt';

                $target =
                    '/group/review.php?date=';

                $linkLabel =
                    'Bestellung anzeigen';
            } elseif (!$isOpen) {
                if ($order !== null) {
                    $status =
                        'Entwurf – Bestellschluss vorbei';

                    $target =
                        '/group/review.php?date=';

                    $linkLabel =
                        'Entwurf anzeigen';
                } else {
                    $status =
                        'Nicht bestellt – Bestellschluss vorbei';
                }
            } elseif ($order !== null) {
                $status = 'Entwurf';

                $target =
                    '/group/order.php?date=';

                $linkLabel =
                    'Entwurf fortsetzen';
            } else {
                $status = 'Offen';

                $target =
                    '/group/order.php?date=';

                $linkLabel =
                    'Bestellung erfassen';
            }
            ?>

            <tr>
                <td>
                    <?= $escape(
                        $formatDate(
                            (string) $day['date']
                        )
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        implode(
                            ' + ',
                            $day['labels']
                        )
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoney(
                            (int) $day['budget_cents']
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
                    <?= $escape($status) ?>
                </td>

                <td>
                    <?php if (
                        $target !== null
                        && $linkLabel !== null
                    ): ?>
                        <a
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
                        Geschlossen
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

<?php endif; ?>

</body>
</html>