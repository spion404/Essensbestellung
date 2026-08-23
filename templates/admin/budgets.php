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

    return $weekdays[(int) $parsedDate->format('N')]
        . ', '
        . $parsedDate->format('d.m.Y');
};

$budgetFullDayCents = (int) round(
    ((float) $settings['budget_full_day']) * 100
);

$budgetHalfDayCents = (int) round(
    ((float) $settings['budget_half_day']) * 100
);

$budgetVisitorDayCents = (int) round(
    ((float) $settings['budget_visitor_day']) * 100
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
        Tagesbudgets – <?= $escape($settings['camp_name']) ?>
    </title>
</head>

<body>

<h1>Tagesbudgets</h1>

<p>
    <strong><?= $escape($settings['camp_name']) ?></strong>
</p>

<p>
    <a href="/admin/groups.php">Gruppen</a>
    |
    <a href="/admin/settings.php">Einstellungen</a>
</p>

<h2>Budgetansätze</h2>

<table>
    <thead>
        <tr>
            <th>Ansatz</th>
            <th>Betrag pro Person</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Ganzer Tag</td>
            <td><?= $escape($formatMoney($budgetFullDayCents)) ?></td>
        </tr>
        <tr>
            <td>Halber Tag</td>
            <td><?= $escape($formatMoney($budgetHalfDayCents)) ?></td>
        </tr>
        <tr>
            <td>Zusätzliche Person am Besuchstag</td>
            <td><?= $escape($formatMoney($budgetVisitorDayCents)) ?></td>
        </tr>
    </tbody>
</table>

<p>
    Der Besuchstag wird additiv berechnet:
    Die normalen Teilnehmer des betreffenden Tages bleiben bestehen,
    die Besucher kommen zusätzlich dazu.
</p>

<?php if ($calculationError !== null): ?>

    <h2>Berechnung nicht möglich</h2>

    <p>
        <?= $escape($calculationError) ?>
    </p>

    <p>
        Bitte prüfe die Datumsangaben in den
        <a href="/admin/settings.php">Einstellungen</a>.
    </p>

<?php elseif ($groupBudgets === []): ?>

    <p>
        Es wurden noch keine Gruppen erfasst.
    </p>

<?php else: ?>

    <h2>Übersicht aller Gruppen</h2>

    <table>
        <thead>
            <tr>
                <th>Gruppe</th>
                <th>Lagerbudget</th>
                <th>Details</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($groupBudgets as $groupBudget): ?>
            <tr>
                <td>
                    <?= $escape($groupBudget['group']['name']) ?>
                </td>

                <td>
                    <?= $escape(
                        $formatMoney(
                            (int) $groupBudget[
                                'calculation'
                            ][
                                'total_budget_cents'
                            ]
                        )
                    ) ?>
                </td>

                <td>
                    <a
                        href="/admin/budgets.php?group_id=<?= (int) $groupBudget['group']['id'] ?>"
                    >
                        Tagesbudget anzeigen
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>

        </tbody>

        <tfoot>
            <tr>
                <th>Gesamt</th>
                <th>
                    <?= $escape(
                        $formatMoney($campTotalBudgetCents)
                    ) ?>
                </th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <?php if ($selectedGroupBudget !== null): ?>

        <h2>
            Tagesdetails:
            <?= $escape($selectedGroupBudget['group']['name']) ?>
        </h2>

        <?php if (
            $selectedGroupBudget['calculation']['days'] === []
        ): ?>

            <p>
                Für diese Gruppe konnten noch keine Tagesbudgets
                berechnet werden. Prüfe, ob in den Einstellungen
                die Lagerdaten erfasst sind.
            </p>

        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Abschnitt</th>
                        <th>Ganze Personen</th>
                        <th>Halbe Personen</th>
                        <th>Besucher</th>
                        <th>Tagesbudget</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach (
                    $selectedGroupBudget['calculation']['days']
                    as $day
                ): ?>
                    <tr>
                        <td>
                            <?= $escape(
                                $formatDate($day['date'])
                            ) ?>
                        </td>

                        <td>
                            <?= $escape(
                                implode(' + ', $day['labels'])
                            ) ?>
                        </td>

                        <td>
                            <?= (int) $day['full_participants'] ?>
                        </td>

                        <td>
                            <?= (int) $day['half_participants'] ?>
                        </td>

                        <td>
                            <?= (int) $day['visitor_participants'] ?>
                        </td>

                        <td>
                            <?= $escape(
                                $formatMoney(
                                    (int) $day['budget_cents']
                                )
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="5">
                            Lagerbudget
                        </th>
                        <th>
                            <?= $escape(
                                $formatMoney(
                                    (int) $selectedGroupBudget[
                                        'calculation'
                                    ][
                                        'total_budget_cents'
                                    ]
                                )
                            ) ?>
                        </th>
                    </tr>
                </tfoot>
            </table>

        <?php endif; ?>

    <?php else: ?>

        <p>
            Die gewählte Gruppe wurde nicht gefunden.
        </p>

    <?php endif; ?>

<?php endif; ?>

</body>
</html>