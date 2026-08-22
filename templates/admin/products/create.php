<?php

declare(strict_types=1);

$escape = static function (
    mixed $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$fieldError = static function (
    string $field
) use (
    $errors,
    $escape
): string {
    if (
        !isset(
            $errors[$field]
        )
    ) {
        return '';
    }

    return '<p>'
        . $escape(
            $errors[$field]
        )
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

    <title>Neues Produkt</title>
</head>

<body>

<p>
    <a href="/admin/products.php">
        ← Zurück zu den Produkten
    </a>
</p>

<h1>Neues Produkt</h1>

<form method="post">

    <p>
        <label for="article_number">
            Artikelnummer
        </label>
        <br>

        <input
            type="text"
            id="article_number"
            name="article_number"
            maxlength="100"
            value="<?= $escape(
                $form['article_number']
            ) ?>"
        >

        <br>

        <small>
            Optional bei manueller
            Erfassung. Beim XLSX-Import
            ist die Artikelnummer
            erforderlich.
        </small>

        <?= $fieldError(
            'article_number'
        ) ?>
    </p>

    <p>
        <label for="name">
            Produktname
        </label>
        <br>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="200"
            value="<?= $escape(
                $form['name']
            ) ?>"
            required
        >

        <?= $fieldError('name') ?>
    </p>

    <p>
        <label for="unit">
            Einheit
        </label>
        <br>

        <input
            type="text"
            id="unit"
            name="unit"
            maxlength="50"
            value="<?= $escape(
                $form['unit']
            ) ?>"
        >

        <?= $fieldError('unit') ?>
    </p>

    <p>
        <label for="price">
            Preis
        </label>
        <br>

        <input
            type="text"
            id="price"
            name="price"
            inputmode="decimal"
            placeholder="0.00"
            value="<?= $escape(
                $form['price']
            ) ?>"
            required
        >

        <?= $fieldError('price') ?>
    </p>

    <p>
        <label for="remark">
            Bemerkung
        </label>
        <br>

        <textarea
            id="remark"
            name="remark"
            rows="4"
            cols="50"
        ><?= $escape(
            $form['remark']
        ) ?></textarea>
    </p>

    <fieldset>
        <legend>Kategorien</legend>

        <?php if (
            $categories === []
        ): ?>

            <p>
                Es wurden noch keine
                Kategorien erstellt.
            </p>

            <p>
                Das Produkt kann trotzdem
                ohne Kategorie gespeichert
                werden.
            </p>

            <p>
                <a
                    href="/admin/categories/create.php"
                >
                    Kategorie erstellen
                </a>
            </p>

        <?php else: ?>

            <?php foreach (
                $categories
                as $category
            ): ?>

                <?php
                $categoryId =
                    (int) $category['id'];

                $checked =
                    in_array(
                        $categoryId,
                        $form[
                            'category_ids'
                        ],
                        true
                    );
                ?>

                <p>
                    <label>
                        <input
                            type="checkbox"
                            name="category_ids[]"
                            value="<?= $categoryId ?>"
                            <?= $checked
                                ? 'checked'
                                : ''
                            ?>
                        >

                        <?= $escape(
                            $category['name']
                        ) ?>
                    </label>
                </p>

            <?php endforeach; ?>

        <?php endif; ?>

        <?= $fieldError(
            'categories'
        ) ?>

    </fieldset>

    <p>
        <button type="submit">
            Produkt erstellen
        </button>
    </p>

</form>

</body>
</html>