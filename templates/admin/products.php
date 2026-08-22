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

    <title>Produkte</title>
</head>

<body>

<h1>Produkte</h1>

<?php if ($created): ?>
    <p>
        Das Produkt wurde erstellt.
    </p>
<?php endif; ?>

<p>
    <a href="/admin/products/create.php">
        Neues Produkt erstellen
    </a>
</p>

<?php if ($products === []): ?>

    <p>
        Es wurden noch keine Produkte erfasst.
    </p>

<?php else: ?>

    <table>
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Einheit</th>
                <th>Preis</th>
                <th>Kategorien</th>
                <th>Bemerkung</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($products as $product): ?>

            <tr>
                <td>
                    <?= $escape($product['name']) ?>
                </td>

                <td>
                    <?= $escape($product['unit']) ?>
                </td>

                <td>
                    <?= number_format(
                        (float) $product['price'],
                        2,
                        '.',
                        ''
                    ) ?>
                </td>

                <td>
                    <?php if ($product['categories'] === null): ?>
                        –
                    <?php else: ?>
                        <?= $escape($product['categories']) ?>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if (
                        $product['remark'] === null
                        || $product['remark'] === ''
                    ): ?>
                        –
                    <?php else: ?>
                        <?= $escape($product['remark']) ?>
                    <?php endif; ?>
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

<?php endif; ?>

</body>
</html>