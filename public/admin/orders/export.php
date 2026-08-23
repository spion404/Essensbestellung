<?php

declare(strict_types=1);

use App\Database;
use App\Repository\GroupRepository;
use App\Repository\OrderRepository;
use App\Repository\SettingsRepository;
use App\Service\DailyBudgetService;
use App\Service\OrderCutoffService;
use App\Service\OrderDayReportService;
use App\Service\OrderExportService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require dirname(__DIR__, 3)
    . '/config/bootstrap.php';

$deliveryDate = trim(
    (string) ($_GET['date'] ?? '')
);

$format = strtolower(
    trim(
        (string) ($_GET['format'] ?? 'xlsx')
    )
);

if (!in_array($format, ['xlsx', 'csv'], true)) {
    http_response_code(400);
    exit('Ungültiges Exportformat.');
}

$pdo = Database::connect();

$groupRepository =
    new GroupRepository($pdo);

$orderRepository =
    new OrderRepository($pdo);

$settingsRepository =
    new SettingsRepository($pdo);

$dailyBudgetService =
    new DailyBudgetService();

$orderCutoffService =
    new OrderCutoffService();

$reportService =
    new OrderDayReportService(
        $groupRepository,
        $orderRepository,
        $settingsRepository,
        $dailyBudgetService,
        $orderCutoffService
    );

try {
    if ($format === 'xlsx') {
        $report = $reportService->buildExport(
            $deliveryDate
        );
    } else {
        $report = $reportService->build(
            $deliveryDate
        );
    }
} catch (InvalidArgumentException) {
    http_response_code(404);
    exit('Liefertag nicht gefunden.');
} catch (RuntimeException $exception) {
    http_response_code(404);
    exit($exception->getMessage());
}

$filenameBase =
    'bestellungen_'
    . $report['delivery_date'];

if ($format === 'csv') {
    header(
        'Content-Type: text/csv; charset=UTF-8'
    );

    header(
        'Content-Disposition: attachment; filename="'
        . $filenameBase
        . '_sammelbestellung.csv"'
    );

    header('Cache-Control: no-store');

    $output = fopen(
        'php://output',
        'wb'
    );

    if ($output === false) {
        throw new RuntimeException(
            'Der CSV-Export konnte nicht geöffnet werden.'
        );
    }

    fwrite(
        $output,
        "\xEF\xBB\xBF"
    );

    fputcsv(
        $output,
        [
            'Artikelnummer',
            'Produkt',
            'Einheit',
            'Packungen gesamt',
            'Warenwert CHF',
        ],
        ';',
        '"',
        ''
    );

    foreach (
        $report['aggregate_items']
        as $item
    ) {
        fputcsv(
            $output,
            [
                (string) (
                    $item['article_number']
                    ?? ''
                ),
                (string) $item['product_name'],
                (string) ($item['unit'] ?? ''),
                (int) $item['total_quantity'],
                number_format(
                    (float) $item['total_amount'],
                    2,
                    '.',
                    ''
                ),
            ],
            ';',
            '"',
            ''
        );
    }

    fclose($output);
    exit;
}

$exportService =
    new OrderExportService();

$spreadsheet =
    $exportService->createWorkbook(
        $report
    );

header(
    'Content-Type: '
    . 'application/vnd.openxmlformats-officedocument.'
    . 'spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="'
    . $filenameBase
    . '.xlsx"'
);

header('Cache-Control: no-store');

$writer = new Xlsx(
    $spreadsheet
);

try {
    $writer->save(
        'php://output'
    );
} finally {
    $spreadsheet->disconnectWorksheets();
}

exit;