<?php

declare(strict_types=1);

use App\Database;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductImportService;
use Throwable;

require dirname(__DIR__, 3)
    . '/config/bootstrap.php';

session_start();

$pdo = Database::connect();

$productRepository =
    new ProductRepository($pdo);

$categoryRepository =
    new CategoryRepository($pdo);

$importService = new ProductImportService(
    $pdo,
    $productRepository,
    $categoryRepository
);

$errors = [];

$debug = filter_var(
    $_ENV['APP_DEBUG'] ?? false,
    FILTER_VALIDATE_BOOL
);

$preview =
    $_SESSION['product_import_preview']
    ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) (
        $_POST['action'] ?? ''
    );

    /*
     * XLSX prüfen und Vorschau erzeugen.
     */
    if ($action === 'preview') {
        unset(
            $_SESSION[
                'product_import_preview'
            ]
        );

        $preview = null;

        $mode = (string) (
            $_POST['mode']
            ?? ProductImportService::MODE_MERGE
        );

        if (
            !in_array(
                $mode,
                [
                    ProductImportService::MODE_MERGE,
                    ProductImportService::MODE_REPLACE,
                ],
                true
            )
        ) {
            $errors[] =
                'Bitte einen gültigen '
                . 'Importmodus auswählen.';
        }

        if (
            !isset($_FILES['xlsx'])
            || !is_array($_FILES['xlsx'])
        ) {
            $errors[] =
                'Bitte eine XLSX-Datei auswählen.';
        } elseif (
            $_FILES['xlsx']['error']
            !== UPLOAD_ERR_OK
        ) {
            $errors[] =
                'Die Datei konnte nicht '
                . 'hochgeladen werden.';
        } elseif (
            (int) $_FILES['xlsx']['size']
            > 5 * 1024 * 1024
        ) {
            $errors[] =
                'Die XLSX-Datei darf maximal '
                . '5 MB gross sein.';
        }

        if ($errors === []) {
            $originalName = basename(
                (string) $_FILES['xlsx']['name']
            );

            $extension = strtolower(
                pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                )
            );

            if ($extension !== 'xlsx') {
                $errors[] =
                    'Bitte eine Datei im '
                    . 'XLSX-Format auswählen.';
            } else {
                try {
                    $result =
                        $importService->read(
                            $_FILES[
                                'xlsx'
                            ]['tmp_name']
                        );

                    if (
                        $result['errors']
                        !== []
                    ) {
                        $errors = array_merge(
                            $errors,
                            $result['errors']
                        );
                    } elseif (
                        $result['rows'] === []
                    ) {
                        $errors[] =
                            'Die XLSX-Datei '
                            . 'enthält keine '
                            . 'Produktzeilen.';
                    } else {
                        $preview = [
                            'file_name' =>
                                $originalName,
                            'mode' =>
                                $mode,
                            'rows' =>
                                $result['rows'],
                        ];

                        $_SESSION[
                            'product_import_preview'
                        ] = $preview;
                    }
                } catch (Throwable $exception) {
                    error_log(
                        sprintf(
                            'XLSX preview failed: '
                            . '%s: %s in %s:%d',
                            $exception::class,
                            $exception->getMessage(),
                            $exception->getFile(),
                            $exception->getLine()
                        )
                    );

                    $errors[] =
                        'Die XLSX-Datei konnte '
                        . 'nicht gelesen werden.';

                    if ($debug) {
                        $errors[] = sprintf(
                            'Technischer Fehler: '
                            . '%s: %s',
                            $exception::class,
                            $exception->getMessage()
                        );
                    }
                }
            }
        }
    }

    /*
     * Vorschau endgültig importieren.
     */
    if ($action === 'import') {
        $preview =
            $_SESSION[
                'product_import_preview'
            ]
            ?? null;

        if ($preview === null) {
            $errors[] =
                'Es ist keine Importvorschau '
                . 'vorhanden.';
        } else {
            $hasRowErrors = false;

            foreach (
                $preview['rows']
                as $row
            ) {
                if ($row['errors'] !== []) {
                    $hasRowErrors = true;
                    break;
                }
            }

            if ($hasRowErrors) {
                $errors[] =
                    'Die Datei enthält '
                    . 'fehlerhafte Zeilen und '
                    . 'kann noch nicht '
                    . 'importiert werden.';
            } else {
                try {
                    $result =
                        $importService->import(
                            $preview['rows'],
                            $preview['mode']
                        );

                    unset(
                        $_SESSION[
                            'product_import_preview'
                        ]
                    );

                    $query = http_build_query([
                        'import_created' =>
                            $result['created'],
                        'import_updated' =>
                            $result['updated'],
                        'import_deleted' =>
                            $result['deleted'],
                    ]);

                    header(
                        'Location: '
                        . '/admin/products.php?'
                        . $query
                    );

                    exit;
                } catch (Throwable $exception) {
                    error_log(
                        sprintf(
                            'XLSX import failed: '
                            . '%s: %s in %s:%d',
                            $exception::class,
                            $exception->getMessage(),
                            $exception->getFile(),
                            $exception->getLine()
                        )
                    );

                    $errors[] =
                        'Der Import konnte nicht '
                        . 'abgeschlossen werden.';

                    if ($debug) {
                        $errors[] = sprintf(
                            'Technischer Fehler: '
                            . '%s: %s',
                            $exception::class,
                            $exception->getMessage()
                        );
                    }
                }
            }
        }
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/products/import.php';