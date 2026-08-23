<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Dieses Skript darf nur über die Kommandozeile ausgeführt werden.\n");
}

$root = dirname(__DIR__);
$errors = 0;
$warnings = 0;

$ok = static function (string $message): void {
    echo '[OK]   ' . $message . PHP_EOL;
};

$fail = static function (string $message) use (&$errors): void {
    $errors++;
    echo '[FEHLER] ' . $message . PHP_EOL;
};

$warn = static function (string $message) use (&$warnings): void {
    $warnings++;
    echo '[WARN] ' . $message . PHP_EOL;
};

$info = static function (string $message): void {
    echo '[INFO] ' . $message . PHP_EOL;
};

echo "Essensbestellung - Systemcheck\n";
echo str_repeat('=', 40) . "\n\n";

if (version_compare(PHP_VERSION, '8.4.0', '>=')) {
    $ok('PHP ' . PHP_VERSION);
} else {
    $fail(
        'PHP ' . PHP_VERSION
        . ' gefunden; aktuell wird PHP >= 8.4 benötigt.'
    );
}

$requiredExtensions = [
    'ctype',
    'dom',
    'fileinfo',
    'filter',
    'gd',
    'iconv',
    'json',
    'libxml',
    'mbstring',
    'openssl',
    'pdo',
    'pdo_mysql',
    'session',
    'simplexml',
    'sodium',
    'xml',
    'xmlreader',
    'xmlwriter',
    'zip',
    'zlib',
];

foreach ($requiredExtensions as $extension) {
    if (extension_loaded($extension)) {
        $ok('PHP-Erweiterung: ' . $extension);
    } else {
        $fail('PHP-Erweiterung fehlt: ' . $extension);
    }
}

$autoload = $root . '/vendor/autoload.php';

if (is_file($autoload)) {
    $ok('Composer-Abhängigkeiten installiert');
    require $autoload;
} else {
    $fail(
        "vendor/autoload.php fehlt; bitte 'composer install --no-dev --optimize-autoloader' ausführen."
    );
}

$envFile = $root . '/.env';
$envLoaded = false;

if (is_file($envFile)) {
    $ok('.env vorhanden');

    if (is_file($autoload)) {
        try {
            Dotenv\Dotenv::createImmutable($root)->safeLoad();
            $envLoaded = true;
            $ok('.env konnte geladen werden');
        } catch (Throwable $exception) {
            $fail('.env konnte nicht geladen werden: ' . $exception->getMessage());
        }
    }
} else {
    $fail(
        ".env fehlt; bei einer Neuinstallation 'php bin/setup.php' ausführen."
    );
}

if ($envLoaded) {
    $requiredEnvironment = [
        'APP_ENV',
        'APP_KEY',
        'APP_TIMEZONE',
        'ADMIN_PASSWORD_HASH',
        'DB_HOST',
        'DB_PORT',
        'DB_NAME',
        'DB_USER',
    ];

    foreach ($requiredEnvironment as $name) {
        $value = trim((string) ($_ENV[$name] ?? ''));

        if ($value === '') {
            $fail($name . ' ist nicht gesetzt.');
        } else {
            $ok($name . ' ist gesetzt.');
        }
    }

    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        $warn('APP_DEBUG=true; auf einem öffentlichen Server sollte APP_DEBUG=false sein.');
    } else {
        $ok('APP_DEBUG ist für Produktion deaktiviert.');
    }

    $timezoneName = (string) ($_ENV['APP_TIMEZONE'] ?? '');

    if ($timezoneName !== '') {
        try {
            new DateTimeZone($timezoneName);
            $ok('Zeitzone gültig: ' . $timezoneName);
        } catch (Throwable) {
            $fail('Ungültige APP_TIMEZONE: ' . $timezoneName);
        }
    }

    $encodedKey = (string) ($_ENV['APP_KEY'] ?? '');

    if ($encodedKey !== '' && extension_loaded('sodium')) {
        $decodedKey = base64_decode($encodedKey, true);

        if (
            $decodedKey !== false
            && strlen($decodedKey) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES
        ) {
            $ok('APP_KEY ist gültig.');
        } else {
            $fail('APP_KEY ist ungültig.');
        }
    }

    $adminHash = (string) ($_ENV['ADMIN_PASSWORD_HASH'] ?? '');

    if ($adminHash !== '') {
        $passwordInfo = password_get_info($adminHash);

        if (($passwordInfo['algoName'] ?? 'unknown') !== 'unknown') {
            $ok('ADMIN_PASSWORD_HASH ist gültig.');
        } else {
            $fail('ADMIN_PASSWORD_HASH ist kein gültiger password_hash()-Wert.');
        }
    }
}

if ($envLoaded && is_file($autoload)) {
    try {
        $pdo = App\Database::connect();
        $ok('Datenbankverbindung erfolgreich.');

        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $info('Datenbankserver: ' . $version);

        if (stripos($version, 'mariadb') !== false) {
            if (
                preg_match('/\d+\.\d+(?:\.\d+)?/', $version, $matches) === 1
                && version_compare($matches[0], '10.11.0', '>=')
            ) {
                $ok('Unterstützte MariaDB-Version.');
            } else {
                $fail('Aktuell wird MariaDB >= 10.11 vorausgesetzt.');
            }
        } elseif (
            preg_match('/\d+\.\d+(?:\.\d+)?/', $version, $matches) === 1
        ) {
            if (version_compare($matches[0], '8.4.0', '>=')) {
                $ok('Unterstützte MySQL-Version.');
            } else {
                $fail('Aktuell wird MySQL >= 8.4 vorausgesetzt.');
            }
        } else {
            $warn('Datenbankversion konnte nicht automatisch eingeordnet werden.');
        }

        $charset = (string) $pdo
            ->query('SELECT @@character_set_database')
            ->fetchColumn();

        if (strtolower($charset) === 'utf8mb4') {
            $ok('Datenbank-Zeichensatz ist utf8mb4.');
        } else {
            $warn(
                'Datenbank-Zeichensatz ist '
                . $charset
                . '; empfohlen ist utf8mb4.'
            );
        }

        $migrationService = new App\Service\MigrationService(
            $pdo,
            $root . '/database/migrations'
        );

        $migrationStatus = $migrationService->status();
        $pending = array_values(
            array_filter(
                $migrationStatus,
                static fn (array $item): bool => !$item['applied']
            )
        );

        if ($pending === []) {
            $ok('Alle bekannten Migrationen sind registriert.');
        } else {
            $warn(
                count($pending)
                . " Migration(en) sind noch nicht registriert. 'php bin/migrate.php' ausführen."
            );

            foreach ($pending as $item) {
                $info('Ausstehend: ' . $item['migration']);
            }
        }
    } catch (Throwable $exception) {
        $fail('Datenbankprüfung fehlgeschlagen: ' . $exception->getMessage());
    }
}

echo PHP_EOL;
echo str_repeat('-', 40) . PHP_EOL;
echo sprintf(
    "Ergebnis: %d Fehler, %d Warnung(en).\n",
    $errors,
    $warnings
);

if ($errors > 0) {
    exit(1);
}

exit(0);
