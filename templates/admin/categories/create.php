<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$fieldError = static function (
    string $field
) use ($errors, $escape): string {
    if (!isset($errors[$field])) {
        return '';
    }

    return '<p>'
        . $escape($errors[$field])
        . '</p>';
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

    <title>Neue Kategorie</title>
</head>

<body>

<p>
    <a href="/admin/categories.php">
        ← Zurück zu den Kategorien
    </a>
</p>

<h1>Neue Kategorie</h1>

<form method="post">

    <p>
        <label for="name">
            Kategoriename
        </label>
        <br>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            value="<?= $escape($form['name']) ?>"
            required
        >

        <?= $fieldError('name') ?>
    </p>

    <p>
        <button type="submit">
            Kategorie erstellen
        </button>
    </p>

</form>

</body>
</html>