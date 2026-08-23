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

$adminPageTitle = 'Tagesbudgets';
$adminActiveSection = 'budgets';

require __DIR__ . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Budgetkontrolle
        </p>

        <h1>Tagesbudgets</h1>

        <p class="lead">
            Berechnete Budgets für
            <strong><?= $escape($settings['camp_name']) ?></strong>
            auf Basis der aktuellen Teilnehmerzahlen.
        </p>

    </div>

    <div class="toolbar">

        <a
            class="button button--secondary"
            href="/admin/groups.php"
        >
            Gruppen
        </a>

        <a
            class="button button--secondary"
            href="/admin/settings.php"
        >
            Budgetansätze
        </a>

    </div>

</div>

<div class="stats">

    <div class="stat">

        <span class="stat__label">
            Ganzer Tag / Person
        </span>

        <span class="stat__value">
            <?= $escape(
                $formatMoney($budgetFullDayCents)
            ) ?>
        </span>

    </div>

    <div class="stat">

        <span class="stat__label">
            Halber Tag / Person
        </span>

        <span class="stat__value">
            <?= $escape(
                $formatMoney($budgetHalfDayCents)
            ) ?>
        </span>

    </div>

    <div class="stat">

        <span class="stat__label">
            Besuchstag / zusätzliche Person
        </span>

        <span class="stat__value">
            <?= $escape(
                $formatMoney($budgetVisitorDayCents)
            ) ?>
        </span>

    </div>

    <?php if ($calculationError === null): ?>

        <div class="stat stat--success">

            <span class="stat__label">
                Gesamtbudget Lager
            </span>

            <span class="stat__value">
                <?= $escape(
                    $formatMoney($campTotalBudgetCents)
                ) ?>
            </span>

        </div>

    <?php endif; ?>

</div>

<div class="alert alert--info">
    Der Besuchstag wird additiv berechnet: Die normalen Teilnehmer
    des Tages bleiben bestehen, Besucher kommen zusätzlich dazu.
</div>

<?php if ($calculationError !== null): ?>

    <div class="alert alert--danger">

        <strong>
            Berechnung nicht möglich:
        </strong>

        <?= $escape($calculationError) ?>

        Bitte prüfe die Datumsangaben in den Einstellungen.

    </div>

<?php elseif ($groupBudgets === []): ?>

    <section class="panel">

        <div class="empty-state">
            Es wurden noch keine Gruppen erfasst.
        </div>

    </section>

<?php else: ?>

    <section class="panel">

        <div class="panel__header">

            <div>

                <h2>Übersicht aller Gruppen</h2>

                <span class="small-muted">
                    Lagerbudget pro Gruppe
                </span>

            </div>

        </div>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Gruppe</th>
                        <th>Lagerbudget</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach (
                    $groupBudgets
                    as $groupBudget
                ): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= $escape(
                                    $groupBudget[
                                        'group'
                                    ]['name']
                                ) ?>
                            </strong>
                        </td>

                        <td class="numeric">
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

                        <td class="actions-cell">

                            <a
                                class="button button--secondary button--small"
                                href="/admin/budgets.php?group_id=<?= (int) $groupBudget['group']['id'] ?>"
                            >
                                Tagesdetails
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

                <tfoot>
                    <tr>
                        <th>Gesamt</th>
                        <th class="numeric">
                            <?= $escape(
                                $formatMoney(
                                    $campTotalBudgetCents
                                )
                            ) ?>
                        </th>
                        <th></th>
                    </tr>
                </tfoot>

            </table>

        </div>

    </section>

    <?php if ($selectedGroupBudget !== null): ?>

        <section class="panel">

            <div class="panel__header">

                <div>

                    <h2>
                        Tagesdetails:
                        <?= $escape(
                            $selectedGroupBudget[
                                'group'
                            ]['name']
                        ) ?>
                    </h2>

                </div>

            </div>

            <?php if (
                $selectedGroupBudget[
                    'calculation'
                ]['days'] === []
            ): ?>

                <div class="empty-state">
                    Für diese Gruppe konnten noch keine Tagesbudgets
                    berechnet werden.
                </div>

            <?php else: ?>

                <div class="table-wrap">

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
                            $selectedGroupBudget[
                                'calculation'
                            ]['days']
                            as $day
                        ): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= $escape(
                                            $formatDate(
                                                $day['date']
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
                                    <?= (int) $day[
                                        'full_participants'
                                    ] ?>
                                </td>

                                <td class="numeric">
                                    <?= (int) $day[
                                        'half_participants'
                                    ] ?>
                                </td>

                                <td class="numeric">
                                    <?= (int) $day[
                                        'visitor_participants'
                                    ] ?>
                                </td>

                                <td class="numeric">
                                    <?= $escape(
                                        $formatMoney(
                                            (int) $day[
                                                'budget_cents'
                                            ]
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

                                <th class="numeric">
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

                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>

<?php endif; ?>

<?php
require __DIR__ . '/partials/layout_end.php';
