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

    <title>Kategorien</title>
</head>

<body>

<h1>Kategorien</h1>

<?php if ($created): ?>
    <p>
        Die Kategorie wurde erstellt.
    </p>
<?php endif; ?>

<p>
    <a href="/admin/categories/create.php">
        Neue Kategorie erstellen
    </a>
</p>


<?php if ($categories === []): ?>

    <p>
        Es wurden noch keine Kategorien erfasst.
    </p>

<?php else: ?>

    <table>
        <thead>
            <tr>
                <th>Kategorie</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($categories as $category): ?>

            <tr>
                <td>
                    <?= $escape($category['name']) ?>
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

<?php endif; ?>

</body>
</html>