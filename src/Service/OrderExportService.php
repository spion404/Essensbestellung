<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use DateTimeZone;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class OrderExportService
{
    private readonly DateTimeZone $timezone;

    public function __construct(
        ?string $timezoneName = null
    ) {
        $timezoneName ??=
            $_ENV['APP_TIMEZONE']
            ?? 'Europe/Zurich';

        try {
            $this->timezone =
                new DateTimeZone($timezoneName);
        } catch (Throwable) {
            $this->timezone =
                new DateTimeZone('Europe/Zurich');
        }
    }

    public function createWorkbook(
        array $report
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet();

        $campName = (string) (
            $report['settings']['camp_name']
            ?? 'Essensbestellung'
        );

        $deliveryDate =
            (string) $report['delivery_date'];

        $spreadsheet
            ->getProperties()
            ->setCreator('Essensbestellung')
            ->setTitle(
                'Bestellungen ' . $deliveryDate
            )
            ->setSubject(
                'Sammelbestellung und Kommissionierung'
            );

        $summarySheet =
            $spreadsheet->getActiveSheet();

        $summarySheet->setTitle(
            'Sammelbestellung'
        );

        $this->fillSummarySheet(
            $summarySheet,
            $campName,
            $report
        );

        $commissionSheet =
            $spreadsheet->createSheet();

        $commissionSheet->setTitle(
            'Kommissionierung'
        );

        $this->fillCommissionSheet(
            $commissionSheet,
            $campName,
            $report
        );

        $statusSheet =
            $spreadsheet->createSheet();

        $statusSheet->setTitle(
            'Gruppenstatus'
        );

        $this->fillStatusSheet(
            $statusSheet,
            $campName,
            $report
        );

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function fillSummarySheet(
        Worksheet $sheet,
        string $campName,
        array $report
    ): void {
        $headerRow = 7;

        $this->writeMetadata(
            $sheet,
            'Sammelbestellung',
            $campName,
            $report
        );

        $headers = [
            'Artikelnummer',
            'Produkt',
            'Einheit',
            'Packungen gesamt',
            'Warenwert',
        ];

        $this->writeHeaderRow(
            $sheet,
            $headerRow,
            $headers
        );

        $row = $headerRow + 1;

        foreach (
            $report['aggregate_items']
            as $item
        ) {
            $articleNumber =
                (string) (
                    $item['article_number']
                    ?? ''
                );

            $sheet->setCellValueExplicit(
                'A' . $row,
                $articleNumber,
                DataType::TYPE_STRING
            );

            $sheet->setCellValue(
                'B' . $row,
                (string) $item['product_name']
            );

            $sheet->setCellValue(
                'C' . $row,
                (string) ($item['unit'] ?? '')
            );

            $sheet->setCellValue(
                'D' . $row,
                (int) $item['total_quantity']
            );

            $sheet->setCellValue(
                'E' . $row,
                (float) $item['total_amount']
            );

            $row++;
        }

        if ($row === $headerRow + 1) {
            $sheet->setCellValue(
                'A' . $row,
                'Keine bestätigten Bestellpositionen.'
            );
        } else {
            $lastDataRow = $row - 1;

            $sheet->setAutoFilter(
                'A' . $headerRow
                . ':E' . $lastDataRow
            );

            $sheet
                ->getStyle(
                    'D' . ($headerRow + 1)
                    . ':D' . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode('0');

            $sheet
                ->getStyle(
                    'E' . ($headerRow + 1)
                    . ':E' . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '"CHF" #,##0.00'
                );
        }

        $sheet->freezePane(
            'A' . ($headerRow + 1)
        );

        $this->setWidths(
            $sheet,
            [
                'A' => 18,
                'B' => 42,
                'C' => 20,
                'D' => 20,
                'E' => 18,
            ]
        );
    }

    private function fillCommissionSheet(
        Worksheet $sheet,
        string $campName,
        array $report
    ): void {
        $headerRow = 7;

        $this->writeMetadata(
            $sheet,
            'Kommissionierung pro Gruppe',
            $campName,
            $report
        );

        $headers = [
            'Gruppe',
            'Artikelnummer',
            'Produkt',
            'Einheit',
            'Packungen',
            'Einzelpreis',
            'Positionswert',
        ];

        $this->writeHeaderRow(
            $sheet,
            $headerRow,
            $headers
        );

        $row = $headerRow + 1;

        foreach (
            $report['submitted_orders']
            as $submittedOrder
        ) {
            foreach (
                $submittedOrder['items']
                as $item
            ) {
                $sheet->setCellValue(
                    'A' . $row,
                    (string) $submittedOrder[
                        'group'
                    ]['name']
                );

                $sheet->setCellValueExplicit(
                    'B' . $row,
                    (string) (
                        $item['article_number']
                        ?? ''
                    ),
                    DataType::TYPE_STRING
                );

                $sheet->setCellValue(
                    'C' . $row,
                    (string) $item['product_name']
                );

                $sheet->setCellValue(
                    'D' . $row,
                    (string) ($item['unit'] ?? '')
                );

                $sheet->setCellValue(
                    'E' . $row,
                    (int) $item['quantity']
                );

                $sheet->setCellValue(
                    'F' . $row,
                    (float) $item['unit_price']
                );

                $sheet->setCellValue(
                    'G' . $row,
                    (float) $item['line_total_amount']
                );

                $row++;
            }
        }

        if ($row === $headerRow + 1) {
            $sheet->setCellValue(
                'A' . $row,
                'Keine bestätigten Bestellpositionen.'
            );
        } else {
            $lastDataRow = $row - 1;

            $sheet->setAutoFilter(
                'A' . $headerRow
                . ':G' . $lastDataRow
            );

            $sheet
                ->getStyle(
                    'E' . ($headerRow + 1)
                    . ':E' . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode('0');

            $sheet
                ->getStyle(
                    'F' . ($headerRow + 1)
                    . ':G' . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '"CHF" #,##0.00'
                );
        }

        $sheet->freezePane(
            'A' . ($headerRow + 1)
        );

        $this->setWidths(
            $sheet,
            [
                'A' => 24,
                'B' => 18,
                'C' => 42,
                'D' => 20,
                'E' => 14,
                'F' => 16,
                'G' => 18,
            ]
        );
    }

    private function fillStatusSheet(
        Worksheet $sheet,
        string $campName,
        array $report
    ): void {
        $headerRow = 7;

        $this->writeMetadata(
            $sheet,
            'Gruppenstatus',
            $campName,
            $report
        );

        $headers = [
            'Gruppe',
            'Status',
            'Ganze Personen',
            'Halbe Personen',
            'Besucher',
            'Tagesbudget',
            'Warenwert',
        ];

        $this->writeHeaderRow(
            $sheet,
            $headerRow,
            $headers
        );

        $statusLabels = [
            'submitted' => 'Bestätigt',
            'draft' => 'Entwurf',
            'missing' => 'Nicht bestellt',
        ];

        $row = $headerRow + 1;

        foreach (
            $report['group_entries']
            as $entry
        ) {
            $sheet->setCellValue(
                'A' . $row,
                (string) $entry['group']['name']
            );

            $sheet->setCellValue(
                'B' . $row,
                $statusLabels[
                    $entry['status']
                ]
                ?? (string) $entry['status']
            );

            $sheet->setCellValue(
                'C' . $row,
                (int) $entry[
                    'budget_day'
                ]['full_participants']
            );

            $sheet->setCellValue(
                'D' . $row,
                (int) $entry[
                    'budget_day'
                ]['half_participants']
            );

            $sheet->setCellValue(
                'E' . $row,
                (int) $entry[
                    'budget_day'
                ]['visitor_participants']
            );

            $sheet->setCellValue(
                'F' . $row,
                (int) $entry[
                    'budget_day'
                ]['budget_cents'] / 100
            );

            if ($entry['order'] !== null) {
                $sheet->setCellValue(
                    'G' . $row,
                    (float) $entry[
                        'order'
                    ]['total_amount']
                );
            }

            $row++;
        }

        $lastDataRow = $row - 1;

        $sheet->setAutoFilter(
            'A' . $headerRow
            . ':G' . $lastDataRow
        );

        $sheet
            ->getStyle(
                'C' . ($headerRow + 1)
                . ':E' . $lastDataRow
            )
            ->getNumberFormat()
            ->setFormatCode('0');

        $sheet
            ->getStyle(
                'F' . ($headerRow + 1)
                . ':G' . $lastDataRow
            )
            ->getNumberFormat()
            ->setFormatCode(
                '"CHF" #,##0.00'
            );

        $sheet->freezePane(
            'A' . ($headerRow + 1)
        );

        $this->setWidths(
            $sheet,
            [
                'A' => 24,
                'B' => 20,
                'C' => 18,
                'D' => 18,
                'E' => 14,
                'F' => 18,
                'G' => 18,
            ]
        );
    }

    private function writeMetadata(
        Worksheet $sheet,
        string $title,
        string $campName,
        array $report
    ): void {
        $sheet->setCellValue(
            'A1',
            $title
        );

        $sheet
            ->getStyle('A1')
            ->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->setCellValue(
            'A2',
            'Lager'
        );

        $sheet->setCellValue(
            'B2',
            $campName
        );

        $sheet->setCellValue(
            'A3',
            'Liefertag'
        );

        $sheet->setCellValue(
            'B3',
            $this->formatDate(
                (string) $report['delivery_date']
            )
        );

        $sheet->setCellValue(
            'A4',
            'Exportiert'
        );

        $sheet->setCellValue(
            'B4',
            (new DateTimeImmutable(
                'now',
                $this->timezone
            ))->format('d.m.Y H:i')
        );

        $sheet->setCellValue(
            'A5',
            'Gruppenstatus'
        );

        $sheet->setCellValue(
            'B5',
            sprintf(
                '%d bestätigt, %d Entwurf, %d nicht bestellt',
                (int) $report['submitted_count'],
                (int) $report['draft_count'],
                (int) $report['missing_count']
            )
        );

        $sheet
            ->getStyle('A2:A5')
            ->getFont()
            ->setBold(true);
    }

    private function writeHeaderRow(
        Worksheet $sheet,
        int $row,
        array $headers
    ): void {
        foreach ($headers as $index => $header) {
            $column = chr(
                ord('A') + $index
            );

            $sheet->setCellValue(
                $column . $row,
                $header
            );
        }

        $lastColumn = chr(
            ord('A') + count($headers) - 1
        );

        $sheet
            ->getStyle(
                'A' . $row
                . ':' . $lastColumn . $row
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                'A' . $row
                . ':' . $lastColumn . $row
            )
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(
                Border::BORDER_THIN
            );
    }

    private function setWidths(
        Worksheet $sheet,
        array $widths
    ): void {
        foreach (
            $widths
            as $column => $width
        ) {
            $sheet
                ->getColumnDimension($column)
                ->setWidth($width);
        }
    }

    private function formatDate(
        string $date
    ): string {
        $parsedDate =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $date,
                $this->timezone
            );

        if ($parsedDate === false) {
            return $date;
        }

        return $parsedDate->format(
            'd.m.Y'
        );
    }
}