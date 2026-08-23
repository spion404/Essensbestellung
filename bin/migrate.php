<?php

declare(strict_types=1);

use App\Database;
use App\Service\MigrationService;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Dieses Skript darf nur über die Kommandozeile ausgeführt werden.\n");
}

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
$envFile = $root . '/.env';

if (!is_file($autoload)) {
    fwrite(
        STDERR,
        "Composer-Abhängigkeiten fehlen. Bitte zuerst 'composer install' ausführen.\n"
    );
    exit(1);
}

if (!is_file($envFile)) {
    fwrite(
        STDERR,
        ".env fehlt. Bei einer Neuinstallation zuerst 'php bin/setup.php' ausführen.\n"
    );
    exit(1);
}

require $autoload;

Dotenv\Dotenv::createImmutable($root)->safeLoad();

try {
    $pdo = Database::connect();

    $service = new MigrationService(
        $pdo,
        $root . '/database/migrations'
    );

    echo "Essensbestellung - Datenbankmigrationen\n";
    echo str_repeat('=', 40) . "\n\n";

    $result = $service->migrate(
        static function (string $message): void {
            echo $message . PHP_EOL;
        }
    );

    echo PHP_EOL;

    if ($result['applied'] === []) {
        echo "Keine neuen Migrationen auszuführen.\n";
    } else {
        echo sprintf(
            "%d Migration(en) erfolgreich ausgeführt.\n",
            count($result['applied'])
        );
    }

    if ($result['baselined'] !== []) {
        echo sprintf(
            "%d bereits vorhandene Migration(en) wurden beim ersten Lauf erkannt und registriert.\n",
            count($result['baselined'])
        );
    }

    exit(0);
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "\nFEHLER: " . $exception->getMessage() . PHP_EOL
    );
    exit(1);
}
