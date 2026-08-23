<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
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

    <title>Gruppen</title>
</head>

<body>

<h1>Gruppen</h1>

<?php if ($created): ?>
    <p>
        Die Gruppe wurde erstellt.
    </p>
<?php endif; ?>
<?php if ($updated): ?>
    <p>
        Die Gruppe wurde aktualisiert.
    </p>
<?php endif; ?>

<p>
    <a href="/admin/groups/create.php">
        Neue Gruppe erstellen
    </a>
    |
    <a href="/admin/budgets.php">
        Tagesbudgets anzeigen
    </a>
</p>

<?php if ($groups === []): ?>

    <p>
        Es wurden noch keine Gruppen erfasst.
    </p>

<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Gruppe</th>
                <th>Anreise ½</th>
                <th>1. Woche ganz</th>
                <th>Abreise Woche 1 ½</th>
                <th>Abreise Woche 1 ganz</th>
                <th>Besucher</th>
                <th>2. Woche ganz</th>
                <th>Abreise Woche 2 ½</th>
                <th>Aktionen</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($groups as $group): ?>
            <tr>
                <td>
                    <?= $escape($group['name']) ?>
                </td>

                <td>
                    <?= $escape(
                        $group['participants_arrival_half']
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $group['participants_week1_full']
                    ) ?>
                </td>
                <td>
                    <?= $escape(
                        $group['participants_week1_departure_half']
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $group['participants_week1_departure_full']
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $group['participants_visitors']
                    ) ?>
                </td>
                <td>
                    <?= $escape(
                        $group['participants_week2_full']
                    ) ?>
                </td>

                <td>
                    <?= $escape(
                        $group['participants_week2_departure_half']
                    ) ?>
                </td>
                <td>
                    <a
                        href="/admin/groups/edit.php?id=<?= (int) $group['id'] ?>"
                    >
                        Bearbeiten
                    </a>
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

<?php endif; ?>

</body>
</html>