<?php

declare(strict_types=1);

use App\Service\ProductImportService;

$escape = static function (
    mixed $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$validRows = 0;
$invalidRows = 0;

if ($preview !== null) {
    foreach (
        $preview['rows']
        as $row
    ) {
        if ($row['errors'] === []) {
            $validRows++;
        } else {
            $invalidRows++;
        }
    }
}

$adminPageTitle = 'Produkte importieren';
$adminActiveSection = 'products';

require dirname(__DIR__) . '/partials/layout_start.php';

?>

<div class="page-header">

    <div class="page-header__copy">

        <p class="eyebrow">
            Produkte
        </p>

        <h1>Produkte aus XLSX importieren</h1>

        <p class="lead">
            Produktkatalog prüfen, Vorschau kontrollieren und
            anschliessend importieren.
        </p>

    </div>

    <div class="toolbar">

        <a
            class="button button--secondary"
            href="/admin/products/import-template.php"
        >
            XLSX-Vorlage herunterladen
        </a>

        <a
            class="button button--secondary"
            href="/admin/products.php"
        >
            Zurück zu den Produkten
        </a>

    </div>

</div>

<section class="panel">

    <div class="panel__header">

        <div>

            <h2>Dateiformat</h2>

            <span class="small-muted">
                Die erste Zeile muss diese sechs Spalten enthalten.
            </span>

        </div>

    </div>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th>E</th>
                    <th>F</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Artikelnummer</td>
                    <td>Produkt</td>
                    <td>Einheit</td>
                    <td>Preis</td>
                    <td>Kategorien</td>
                    <td>Bemerkung</td>
                </tr>
            </tbody>
        </table>

    </div>

    <p>
        Mehrere Kategorien werden mit einem Semikolon getrennt,
        zum Beispiel
        <code class="code-inline">
            Saucen &amp; Gewürze; Vegetarisch
        </code>.
    </p>

    <p class="small-muted">
        Die Artikelnummer ist erforderlich und sollte in Excel
        als Text gespeichert sein, damit führende Nullen erhalten
        bleiben.
    </p>

</section>

<?php if ($errors !== []): ?>

    <div class="alert alert--danger">

        <strong>Fehler beim Import:</strong>

        <ul class="compact-list">

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= $escape($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>

<form
    class="admin-form"
    method="post"
    enctype="multipart/form-data"
>

    <input
        type="hidden"
        name="action"
        value="preview"
    >

    <section class="form-section">

        <div class="form-section__header">

            <h2>Importmodus</h2>

        </div>

        <fieldset class="form-fieldset">

            <legend>
                Was soll mit bestehenden Produkten passieren?
            </legend>

            <div class="import-mode-grid">

                <label class="radio-card">

                    <input
                        type="radio"
                        name="mode"
                        value="<?= ProductImportService::MODE_MERGE ?>"
                        <?= $selectedMode
                            === ProductImportService::MODE_MERGE
                                ? 'checked'
                                : ''
                        ?>
                    >

                    <span class="choice-copy">

                        <strong>
                            Ergänzen / aktualisieren
                        </strong>

                        <span>
                            Neue Artikelnummern werden angelegt,
                            bestehende anhand der Artikelnummer
                            aktualisiert.
                        </span>

                    </span>

                </label>

                <label class="radio-card">

                    <input
                        type="radio"
                        name="mode"
                        value="<?= ProductImportService::MODE_REPLACE ?>"
                        <?= $selectedMode
                            === ProductImportService::MODE_REPLACE
                                ? 'checked'
                                : ''
                        ?>
                    >

                    <span class="choice-copy">

                        <strong>
                            Bestehenden Katalog ersetzen
                        </strong>

                        <span>
                            Alle bestehenden Produkte werden entfernt
                            und durch die XLSX-Datei ersetzt.
                        </span>

                    </span>

                </label>

            </div>

        </fieldset>

    </section>

    <section class="form-section">

        <div class="form-section__header">

            <h2>XLSX-Datei</h2>

        </div>

        <div class="file-box">

            <div class="field">

                <label for="xlsx">
                    Datei auswählen
                </label>

                <input
                    type="file"
                    id="xlsx"
                    name="xlsx"
                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    required
                >

            </div>

        </div>

        <div class="form-actions">

            <button type="submit">
                Datei prüfen
            </button>

        </div>

    </section>

</form>

<?php if ($preview !== null): ?>

    <section class="panel">

        <div class="panel__header">

            <div>

                <h2>Importvorschau</h2>

                <span class="small-muted">
                    <?= $escape($preview['file_name']) ?>
                </span>

            </div>

        </div>

        <div class="stats">

            <div class="stat stat--success">

                <span class="stat__label">
                    Gültige Zeilen
                </span>

                <span class="stat__value">
                    <?= $validRows ?>
                </span>

            </div>

            <div
                class="stat <?= $invalidRows > 0
                    ? 'stat--danger'
                    : ''
                ?>"
            >

                <span class="stat__label">
                    Fehlerhafte Zeilen
                </span>

                <span class="stat__value">
                    <?= $invalidRows ?>
                </span>

            </div>

        </div>

        <p>
            Importmodus:
            <strong>
                <?php if (
                    $preview['mode']
                    === ProductImportService::MODE_REPLACE
                ): ?>
                    Bestehenden Katalog ersetzen
                <?php else: ?>
                    Ergänzen / aktualisieren
                <?php endif; ?>
            </strong>
        </p>

        <?php if (
            $preview['mode']
            === ProductImportService::MODE_REPLACE
        ): ?>

            <div class="alert alert--warning">
                <strong>Achtung:</strong>
                Beim Bestätigen werden alle vorhandenen Produkte
                gelöscht und durch diesen Katalog ersetzt.
            </div>

        <?php endif; ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Zeile</th>
                        <th>Artikelnummer</th>
                        <th>Produkt</th>
                        <th>Einheit</th>
                        <th>Preis</th>
                        <th>Kategorien</th>
                        <th>Bemerkung</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach (
                    $preview['rows']
                    as $row
                ): ?>

                    <tr>

                        <td>
                            <?= (int) $row['row'] ?>
                        </td>

                        <td>
                            <?= $escape(
                                $row['article_number']
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= $escape(
                                    $row['name']
                                ) ?>
                            </strong>
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
                            <?= $row['categories'] === []
                                ? '–'
                                : $escape(
                                    implode(
                                        ', ',
                                        $row['categories']
                                    )
                                )
                            ?>
                        </td>

                        <td>
                            <?= $row['remark'] !== ''
                                ? $escape($row['remark'])
                                : '–'
                            ?>
                        </td>

                        <td class="preview-status">

                            <?php if (
                                $row['errors'] === []
                            ): ?>

                                <span
                                    class="badge badge--success"
                                >
                                    OK
                                </span>

                            <?php else: ?>

                                <div class="alert alert--danger">

                                    <?php foreach (
                                        $row['errors']
                                        as $rowError
                                    ): ?>

                                        <div>
                                            <?= $escape(
                                                $rowError
                                            ) ?>
                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

    <?php if ($invalidRows === 0): ?>

        <section class="form-section admin-form">

            <div class="form-section__header">

                <h2>Import bestätigen</h2>

            </div>

            <?php if (
                $preview['mode']
                === ProductImportService::MODE_REPLACE
            ): ?>

                <div class="alert alert--warning">
                    Der bestehende Produktkatalog wird vollständig
                    ersetzt.
                </div>

            <?php else: ?>

                <div class="alert alert--info">
                    Bestehende Produkte mit gleicher Artikelnummer
                    werden aktualisiert; neue Artikelnummern ergänzt.
                </div>

            <?php endif; ?>

            <p>
                Noch nicht vorhandene Kategorien werden automatisch
                angelegt.
            </p>

            <form method="post">

                <input
                    type="hidden"
                    name="action"
                    value="import"
                >

                <button type="submit">
                    Import durchführen
                </button>

            </form>

        </section>

    <?php else: ?>

        <div class="alert alert--warning">

            <strong>Import noch nicht möglich.</strong>

            Korrigiere die markierten Zeilen in der XLSX-Datei
            und lade sie anschliessend erneut hoch.

        </div>

    <?php endif; ?>

<?php endif; ?>

<?php
require dirname(__DIR__) . '/partials/layout_end.php';
