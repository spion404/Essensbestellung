<?php

declare(strict_types=1);

$escape = static function (mixed $value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$validRows = 0;
$invalidRows = 0;

if ($preview !== null) {
    foreach ($preview['rows'] as $row) {
        if ($row['errors'] === []) {
            $validRows++;
        } else {
            $invalidRows++;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Produkte importieren</title>
</head>

<body>

<p>
    <a href="/admin/products.php">
        ← Zurück zu den Produkten
    </a>
</p>

<h1>Produkte aus XLSX importieren</h1>

<p>
    Die erste Zeile der Datei muss folgende
    Spalten enthalten:
</p>

<table>
    <thead>
        <tr>
            <th>A</th>
            <th>B</th>
            <th>C</th>
            <th>D</th>
            <th>E</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>Produkt</td>
            <td>Einheit</td>
            <td>Preis</td>
            <td>Kategorien</td>
            <td>Bemerkung</td>
        </tr>
    </tbody>
</table>

<p>
    Mehrere Kategorien werden mit einem
    Semikolon getrennt.
</p>

<p>
    Beispiel:
    <code>
        Saucen &amp; Gewürze; Vegetarisch
    </code>
</p>

<?php if ($errors !== []): ?>

    <h2>Fehler</h2>

    <?php foreach ($errors as $error): ?>
        <p>
            <?= $escape($error) ?>
        </p>
    <?php endforeach; ?>

<?php endif; ?>

<h2>XLSX-Datei auswählen</h2>

<form
    method="post"
    enctype="multipart/form-data"
>
    <input
        type="hidden"
        name="action"
        value="preview"
    >

    <p>
        <label for="xlsx">
            XLSX-Datei
        </label>
        <br>

        <input
            type="file"
            id="xlsx"
            name="xlsx"
            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            required
        >
    </p>

    <p>
        <button type="submit">
            Datei prüfen
        </button>
    </p>
</form>

<?php if ($preview !== null): ?>

    <hr>

    <h2>Importvorschau</h2>

    <p>
        Datei:
        <strong>
            <?= $escape($preview['file_name']) ?>
        </strong>
    </p>

    <p>
        Gültige Zeilen:
        <?= $validRows ?>
        <br>

        Fehlerhafte Zeilen:
        <?= $invalidRows ?>
    </p>

    <table>
        <thead>
            <tr>
                <th>Zeile</th>
                <th>Produkt</th>
                <th>Einheit</th>
                <th>Preis</th>
                <th>Kategorien</th>
                <th>Bemerkung</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($preview['rows'] as $row): ?>

            <tr>
                <td>
                    <?= (int) $row['row'] ?>
                </td>

                <td>
                    <?= $escape($row['name']) ?>
                </td>

                <td>
                    <?= $row['unit'] !== ''
                        ? $escape($row['unit'])
                        : '–'
                    ?>
                </td>

                <td>
                    <?= $escape($row['price']) ?>
                </td>

                <td>
                    <?php if ($row['categories'] === []): ?>
                        –
                    <?php else: ?>
                        <?= $escape(
                            implode(
                                ', ',
                                $row['categories']
                            )
                        ) ?>
                    <?php endif; ?>
                </td>

                <td>
                    <?= $row['remark'] !== ''
                        ? $escape($row['remark'])
                        : '–'
                    ?>
                </td>

                <td>
                    <?php if ($row['errors'] === []): ?>

                        OK

                    <?php else: ?>

                        <?php foreach (
                            $row['errors']
                            as $rowError
                        ): ?>

                            <div>
                                <?= $escape($rowError) ?>
                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>
                </td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

    <?php if ($invalidRows === 0): ?>

        <h2>Import bestätigen</h2>

        <p>
            Beim Import werden
            <?= $validRows ?>
            Produkte erstellt.
        </p>

        <p>
            Noch nicht vorhandene Kategorien
            werden automatisch angelegt.
        </p>

        <form method="post">
            <input
                type="hidden"
                name="action"
                value="import"
            >

            <button type="submit">
                Produkte importieren
            </button>
        </form>

    <?php else: ?>

        <h2>Import noch nicht möglich</h2>

        <p>
            Korrigiere die markierten Zeilen
            in der XLSX-Datei und lade sie
            anschliessend erneut hoch.
        </p>

    <?php endif; ?>

<?php endif; ?>

</body>
</html>