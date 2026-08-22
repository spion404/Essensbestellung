<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use PDO;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class ProductImportService
{
    public const MODE_MERGE = 'merge';
    public const MODE_REPLACE = 'replace';

    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function read(
        string $filePath
    ): array {
        $reader =
            IOFactory::createReader('Xlsx');

        $reader->setReadDataOnly(true);

        $spreadsheet =
            $reader->load($filePath);

        try {
            $sheet =
                $spreadsheet
                    ->getActiveSheet();

            $fileErrors =
                $this->validateHeaders(
                    $sheet
                );

            if ($fileErrors !== []) {
                return [
                    'rows' => [],
                    'errors' => $fileErrors,
                ];
            }

            $rows = [];
            $seenArticleNumbers = [];

            $highestRow =
                $sheet->getHighestDataRow();

            for (
                $rowNumber = 2;
                $rowNumber <= $highestRow;
                $rowNumber++
            ) {
                $articleNumber = trim(
                    (string) $sheet
                        ->getCell(
                            'A' . $rowNumber
                        )
                        ->getFormattedValue()
                );

                $name = trim(
                    (string) $sheet
                        ->getCell(
                            'B' . $rowNumber
                        )
                        ->getValue()
                );

                $unit = trim(
                    (string) $sheet
                        ->getCell(
                            'C' . $rowNumber
                        )
                        ->getValue()
                );

                $priceInput = trim(
                    (string) $sheet
                        ->getCell(
                            'D' . $rowNumber
                        )
                        ->getValue()
                );

                $categoriesInput = trim(
                    (string) $sheet
                        ->getCell(
                            'E' . $rowNumber
                        )
                        ->getValue()
                );

                $remark = trim(
                    (string) $sheet
                        ->getCell(
                            'F' . $rowNumber
                        )
                        ->getValue()
                );

                /*
                 * Komplett leere Zeilen ignorieren.
                 */
                if (
                    $articleNumber === ''
                    && $name === ''
                    && $unit === ''
                    && $priceInput === ''
                    && $categoriesInput === ''
                    && $remark === ''
                ) {
                    continue;
                }

                $errors = [];

                /*
                 * Artikelnummer
                 */
                if ($articleNumber === '') {
                    $errors[] =
                        'Artikelnummer fehlt.';
                } elseif (
                    mb_strlen(
                        $articleNumber
                    ) > 100
                ) {
                    $errors[] =
                        'Artikelnummer ist länger '
                        . 'als 100 Zeichen.';
                } else {
                    $articleNumberKey =
                        mb_strtolower(
                            $articleNumber,
                            'UTF-8'
                        );

                    if (
                        isset(
                            $seenArticleNumbers[
                                $articleNumberKey
                            ]
                        )
                    ) {
                        $errors[] =
                            'Artikelnummer kommt '
                            . 'mehrfach in der '
                            . 'Datei vor.';
                    } else {
                        $seenArticleNumbers[
                            $articleNumberKey
                        ] = true;
                    }
                }

                /*
                 * Produktname
                 */
                if ($name === '') {
                    $errors[] =
                        'Produktname fehlt.';
                } elseif (
                    mb_strlen($name) > 200
                ) {
                    $errors[] =
                        'Produktname ist länger '
                        . 'als 200 Zeichen.';
                }

                /*
                 * Einheit
                 */
                if (
                    mb_strlen($unit) > 50
                ) {
                    $errors[] =
                        'Einheit ist länger '
                        . 'als 50 Zeichen.';
                }

                /*
                 * Preis
                 */
                $price =
                    $this->normalizePrice(
                        $priceInput
                    );

                if ($price === null) {
                    $errors[] =
                        'Preis ist ungültig.';
                }

                /*
                 * Kategorien
                 */
                $categories =
                    $this->parseCategories(
                        $categoriesInput
                    );

                foreach (
                    $categories
                    as $category
                ) {
                    if (
                        mb_strlen(
                            $category
                        ) > 100
                    ) {
                        $errors[] =
                            'Kategorie "'
                            . $category
                            . '" ist länger '
                            . 'als 100 Zeichen.';
                    }
                }

                $rows[] = [
                    'row' => $rowNumber,
                    'article_number' =>
                        $articleNumber,
                    'name' => $name,
                    'unit' => $unit,
                    'price' =>
                        $price
                        ?? $priceInput,
                    'remark' => $remark,
                    'categories' =>
                        $categories,
                    'errors' => $errors,
                ];
            }

            return [
                'rows' => $rows,
                'errors' => [],
            ];
        } finally {
            $spreadsheet
                ->disconnectWorksheets();
        }
    }

    public function import(
        array $rows,
        string $mode
    ): array {
        if (
            !in_array(
                $mode,
                [
                    self::MODE_MERGE,
                    self::MODE_REPLACE,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Ungültiger Importmodus.'
            );
        }

        if ($this->pdo->inTransaction()) {
            throw new \LogicException(
                'Der Import kann nicht '
                . 'innerhalb einer bereits '
                . 'laufenden Transaktion '
                . 'gestartet werden.'
            );
        }

        foreach ($rows as $row) {
            if ($row['errors'] !== []) {
                throw new \LogicException(
                    'Ein Import mit '
                    . 'fehlerhaften Zeilen '
                    . 'ist nicht erlaubt.'
                );
            }
        }

        $this->pdo->beginTransaction();

        try {
            $created = 0;
            $updated = 0;
            $deleted = 0;

            if (
                $mode
                === self::MODE_REPLACE
            ) {
                $deleted =
                    $this->productRepository
                        ->deleteAll();
            }

            foreach ($rows as $row) {
                $categoryIds = [];

                foreach (
                    $row['categories']
                    as $categoryName
                ) {
                    $categoryIds[] =
                        $this
                            ->categoryRepository
                            ->findOrCreateId(
                                $categoryName
                            );
                }

                if (
                    $mode
                    === self::MODE_MERGE
                    && $this
                        ->productRepository
                        ->findByArticleNumber(
                            $row[
                                'article_number'
                            ]
                        )
                        !== null
                ) {
                    $this
                        ->productRepository
                        ->updateByArticleNumber(
                            $row[
                                'article_number'
                            ],
                            $row['name'],
                            $row['unit'] !== ''
                                ? $row['unit']
                                : null,
                            $row['price'],
                            $row['remark'] !== ''
                                ? $row['remark']
                                : null,
                            $categoryIds
                        );

                    $updated++;

                    continue;
                }

                $this
                    ->productRepository
                    ->create(
                        $row['name'],
                        $row['unit'] !== ''
                            ? $row['unit']
                            : null,
                        $row['price'],
                        $row['remark'] !== ''
                            ? $row['remark']
                            : null,
                        $categoryIds,
                        $row[
                            'article_number'
                        ]
                    );

                $created++;
            }

            $this->pdo->commit();

            return [
                'created' => $created,
                'updated' => $updated,
                'deleted' => $deleted,
            ];
        } catch (Throwable $exception) {
            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function validateHeaders(
        Worksheet $sheet
    ): array {
        $expectedHeaders = [
            'A' => 'artikelnummer',
            'B' => 'produkt',
            'C' => 'einheit',
            'D' => 'preis',
            'E' => 'kategorien',
            'F' => 'bemerkung',
        ];

        $errors = [];

        foreach (
            $expectedHeaders
            as $column => $expectedHeader
        ) {
            $actualHeader =
                mb_strtolower(
                    trim(
                        (string) $sheet
                            ->getCell(
                                $column . '1'
                            )
                            ->getValue()
                    ),
                    'UTF-8'
                );

            if (
                $actualHeader
                !== $expectedHeader
            ) {
                $errors[] =
                    'Spalte '
                    . $column
                    . ' muss "'
                    . ucfirst(
                        $expectedHeader
                    )
                    . '" heissen.';
            }
        }

        return $errors;
    }

    private function normalizePrice(
        string $price
    ): ?string {
        if ($price === '') {
            return null;
        }

        $price = str_replace(
            ',',
            '.',
            $price
        );

        if (
            preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $price
            ) !== 1
        ) {
            return null;
        }

        [
            $wholePart,
            $decimalPart,
        ] = array_pad(
            explode(
                '.',
                $price,
                2
            ),
            2,
            ''
        );

        $wholePart =
            ltrim(
                $wholePart,
                '0'
            );

        if ($wholePart === '') {
            $wholePart = '0';
        }

        if (
            strlen($wholePart) > 8
        ) {
            return null;
        }

        $decimalPart =
            str_pad(
                $decimalPart,
                2,
                '0'
            );

        return $wholePart
            . '.'
            . $decimalPart;
    }

    private function parseCategories(
        string $categories
    ): array {
        if ($categories === '') {
            return [];
        }

        $parts =
            explode(
                ';',
                $categories
            );

        $result = [];
        $seen = [];

        foreach ($parts as $part) {
            $category =
                trim($part);

            if ($category === '') {
                continue;
            }

            $key =
                mb_strtolower(
                    $category,
                    'UTF-8'
                );

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $category;
        }

        return $result;
    }
}