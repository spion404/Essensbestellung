<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require dirname(__DIR__, 3)
    . '/config/bootstrap.php';

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Produkte');

$sheet->fromArray(
    [
        [
            'Artikelnummer',
            'Produkt',
            'Einheit',
            'Preis',
            'Kategorien',
            'Bemerkung',
        ],
    ],
    null,
    'A1'
);

/*
 * Kopfzeile hervorheben.
 */
$sheet
    ->getStyle('A1:F1')
    ->getFont()
    ->setBold(true);

/*
 * Artikelnummern als Text behandeln.
 * So bleiben führende Nullen erhalten.
 */
$sheet
    ->getStyle('A:A')
    ->getNumberFormat()
    ->setFormatCode(
        NumberFormat::FORMAT_TEXT
    );

/*
 * Preisformat.
 */
$sheet
    ->getStyle('D:D')
    ->getNumberFormat()
    ->setFormatCode('0.00');

/*
 * Praktische Spaltenbreiten.
 */
$sheet
    ->getColumnDimension('A')
    ->setWidth(20);

$sheet
    ->getColumnDimension('B')
    ->setWidth(40);

$sheet
    ->getColumnDimension('C')
    ->setWidth(20);

$sheet
    ->getColumnDimension('D')
    ->setWidth(12);

$sheet
    ->getColumnDimension('E')
    ->setWidth(45);

$sheet
    ->getColumnDimension('F')
    ->setWidth(45);

$sheet->freezePane('A2');

$sheet->setAutoFilter('A1:F1');

/*
 * Separates Hinweisblatt.
 */
$notes =
    $spreadsheet->createSheet();

$notes->setTitle('Hinweise');

$notes->setCellValue(
    'A1',
    'Hinweise zum Produktimport'
);

$notes
    ->getStyle('A1')
    ->getFont()
    ->setBold(true);

$notes->setCellValue(
    'A3',
    'Artikelnummer'
);

$notes->setCellValue(
    'B3',
    'Pflichtfeld. Eindeutige Kennung des Produkts. Als Text speichern.'
);

$notes->setCellValue(
    'A4',
    'Produkt'
);

$notes->setCellValue(
    'B4',
    'Pflichtfeld. Name des Produkts.'
);

$notes->setCellValue(
    'A5',
    'Einheit'
);

$notes->setCellValue(
    'B5',
    'Optional, z. B. kg, Liter oder Packung.'
);

$notes->setCellValue(
    'A6',
    'Preis'
);

$notes->setCellValue(
    'B6',
    'Pflichtfeld. Beispiel: 2.50'
);

$notes->setCellValue(
    'A7',
    'Kategorien'
);

$notes->setCellValue(
    'B7',
    'Mehrere Kategorien mit Semikolon trennen.'
);

$notes->setCellValue(
    'A8',
    'Bemerkung'
);

$notes->setCellValue(
    'B8',
    'Optionaler Hinweis zum Produkt.'
);

$notes->setCellValue(
    'A10',
    'Beispiel'
);

$notes
    ->getStyle('A10')
    ->getFont()
    ->setBold(true);

$notes->fromArray(
    [
        [
            'Artikelnummer',
            '100001',
        ],
        [
            'Produkt',
            'Tomatensauce',
        ],
        [
            'Einheit',
            'Glas 500 g',
        ],
        [
            'Preis',
            '2.90',
        ],
        [
            'Kategorien',
            'Saucen & Gewürze; Vegetarisch',
        ],
        [
            'Bemerkung',
            'Optionale Bemerkung',
        ],
    ],
    null,
    'A11'
);

$notes
    ->getColumnDimension('A')
    ->setWidth(22);

$notes
    ->getColumnDimension('B')
    ->setWidth(70);

/*
 * Beim Öffnen soll das eigentliche
 * Produktblatt aktiv sein.
 */
$spreadsheet->setActiveSheetIndex(0);

$fileName =
    'produktimport-vorlage.xlsx';

header(
    'Content-Type: '
    . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: '
    . 'attachment; filename="'
    . $fileName
    . '"'
);

header(
    'Cache-Control: max-age=0'
);

$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

$spreadsheet->disconnectWorksheets();

exit;