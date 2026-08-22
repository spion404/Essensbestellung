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

    <title>
        Produkt löschen –
        <?= $escape($product['name']) ?>
    </title>
</head>

<body>

<p>
    <a href="/admin/products.php">
        ← Zurück zu den Produkten
    </a>
</p>

<h1>Produkt löschen</h1>

<p>
    Soll das folgende Produkt wirklich gelöscht werden?
</p>

<p>
    <strong>
        <?= $escape($product['name']) ?>
    </strong>
</p>

<?php if (
    $product['unit'] !== null
    && $product['unit'] !== ''
): ?>

    <p>
        Einheit:
        <?= $escape($product['unit']) ?>
    </p>

<?php endif; ?>

<p>
    Preis:
    <?= number_format(
        (float) $product['price'],
        2,
        '.',
        ''
    ) ?>
</p>

<p>
    <strong>
        Dieser Vorgang kann nicht rückgängig gemacht werden.
    </strong>
</p>

<form method="post">
    <button type="submit">
        Produkt endgültig löschen
    </button>
</form>

<p>
    <a href="/admin/products.php">
        Abbrechen
    </a>
</p>

</body>
</html>