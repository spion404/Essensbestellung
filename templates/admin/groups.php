<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$adminPageTitle = 'Gruppen';
$adminActiveSection = 'groups';

require __DIR__ . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Administration
        </p>

        <h1>Gruppen</h1>

        <p class="lead">
            Gruppenpasswörter und Teilnehmerzahlen für die
            Lagerabschnitte verwalten.
        </p>

    </div>

    <div class="toolbar">

        <a
            class="button button--secondary"
            href="/admin/budgets.php"
        >
            Tagesbudgets
        </a>

        <a
            class="button"
            href="/admin/groups/create.php"
        >
            Neue Gruppe
        </a>

    </div>

</div>

<?php if ($created): ?>

    <div class="alert alert--success">
        Die Gruppe wurde erstellt.
    </div>

<?php endif; ?>

<?php if ($updated): ?>

    <div class="alert alert--success">
        Die Gruppe wurde aktualisiert.
    </div>

<?php endif; ?>

<section class="panel">

    <div class="panel__header">

        <div>

            <h2>Erfasste Gruppen</h2>

            <span class="small-muted">
                Die Zahlen bilden die Grundlage für die
                Tagesbudgets.
            </span>

        </div>

    </div>

    <?php if ($groups === []): ?>

        <div class="empty-state">
            Es wurden noch keine Gruppen erfasst.
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Gruppe</th>
                        <th>Anreise ½</th>
                        <th>Woche 1</th>
                        <th>Abreise W1 ½</th>
                        <th>Abreise W1 ganz</th>
                        <th>Besucher</th>
                        <th>Woche 2</th>
                        <th>Abreise W2 ½</th>
                        <th>Aktion</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($groups as $group): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= $escape($group['name']) ?>
                            </strong>
                        </td>

                        <td class="numeric">
                            <?= (int) $group[
                                'participants_arrival_half'
                            ] ?>
                        </td>

                        <td class="numeric">
                            <?= (int) $group[
                                'participants_week1_full'
                            ] ?>
                        </td>

                        <td class="numeric">
                            <?= (int) $group[
                                'participants_week1_departure_half'
                            ] ?>
                        </td>

                        <td class="numeric">
                            <?= (int) $group[
                                'participants_week1_departure_full'
                            ] ?>
                        </td>

                        <td class="numeric">
                            <?= (int) $group[
                                'participants_visitors'
                            ] ?>
                        </td>

                        <td class="numeric">
                            <?= (int) $group[
                                'participants_week2_full'
                            ] ?>
                        </td>

                        <td class="numeric">
                            <?= (int) $group[
                                'participants_week2_departure_half'
                            ] ?>
                        </td>

                        <td class="actions-cell">

                            <a
                                class="button button--secondary button--small"
                                href="/admin/groups/edit.php?id=<?= (int) $group['id'] ?>"
                            >
                                Bearbeiten
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php
require __DIR__ . '/partials/layout_end.php';
