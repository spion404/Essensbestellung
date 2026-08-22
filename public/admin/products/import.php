<?php

declare(strict_types=1);

use App\Database;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductImportService;
use Throwable;

require dirname(__DIR__, 3) . '/config/bootstrap.php';

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

$preview =
    $_SESSION['product_import_preview']
    ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) (
        $_POST['action'] ?? ''
    );

    /*
     * Neue XLSX-Datei einlesen.
     */
    if ($action === 'preview') {
        unset(
            $_SESSION['product_import_preview']
        );

        $preview = null;

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
                'Die Datei konnte nicht hochgeladen werden.';
        } elseif (
            (int) $_FILES['xlsx']['size']
            > 5 * 1024 * 1024
        ) {
            $errors[] =
                'Die XLSX-Datei darf maximal 5 MB gross sein.';
        } else {
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
                    'Bitte eine Datei im XLSX-Format auswählen.';
            } else {
                try {
                    $result =
                        $importService->read(
                            $_FILES['xlsx']['tmp_name']
                        );

                    if ($result['errors'] !== []) {
                        $errors = array_merge(
                            $errors,
                            $result['errors']
                        );
                    } elseif ($result['rows'] === []) {
                        $errors[] =
                            'Die XLSX-Datei enthält '
                            . 'keine Produktzeilen.';
                    } else {
                        $preview = [
                            'file_name' =>
                                $originalName,
                            'rows' =>
                                $result['rows'],
                        ];

                        $_SESSION[
                            'product_import_preview'
                        ] = $preview;
                    }
                } catch (Throwable $exception) {
                    error_log(
                        'XLSX import preview failed: '
                        . $exception->getMessage()
                    );

                    $errors[] =
                        'Die XLSX-Datei konnte '
                        . 'nicht gelesen werden.';
                }
            }
        }
    }

    /*
     * Angezeigte Vorschau importieren.
     */
    if ($action === 'import') {
        $preview =
            $_SESSION['product_import_preview']
            ?? null;

        if ($preview === null) {
            $errors[] =
                'Es ist keine Importvorschau vorhanden.';
        } else {
            $hasRowErrors = false;

            foreach ($preview['rows'] as $row) {
                if ($row['errors'] !== []) {
                    $hasRowErrors = true;
                    break;
                }
            }

            if ($hasRowErrors) {
                $errors[] =
                    'Die Datei enthält fehlerhafte Zeilen '
                    . 'und kann noch nicht importiert werden.';
            } else {
                try {
                    $imported =
                        $importService->import(
                            $preview['rows']
                        );

                    unset(
                        $_SESSION[
                            'product_import_preview'
                        ]
                    );

                    header(
                        'Location: /admin/products.php'
                        . '?imported='
                        . $imported
                    );

                    exit;
                } catch (Throwable $exception) {
                    error_log(
                        'XLSX product import failed: '
                        . $exception->getMessage()
                    );

                    $errors[] =
                        'Der Import konnte nicht '
                        . 'abgeschlossen werden.';
                }
            }
        }
    }
}

require dirname(__DIR__, 3)
    . '/templates/admin/products/import.php';