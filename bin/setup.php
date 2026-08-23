<?php

declare(strict_types=1);

use App\Service\MigrationService;
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Dieses Skript darf nur über die Kommandozeile ausgeführt werden.\n");
}

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
$envFile = $root . '/.env';

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    fwrite(
        STDERR,
        'PHP >= 8.3 wird benötigt. Gefunden: ' . PHP_VERSION . PHP_EOL
    );
    exit(1);
}

if (!is_file($autoload)) {
    fwrite(
        STDERR,
        "Composer-Abhängigkeiten fehlen. Bitte zuerst 'composer install --no-dev --optimize-autoloader' ausführen.\n"
    );
    exit(1);
}

require $autoload;

if (is_file($envFile)) {
    fwrite(
        STDERR,
        ".env existiert bereits. setup.php überschreibt bestehende Installationen absichtlich nicht.\n"
        . "Für Updates bitte 'php bin/migrate.php' verwenden.\n"
        . "Wichtig: APP_KEY niemals bei einer bestehenden Installation neu erzeugen, sonst können Gruppenpasswörter nicht mehr entschlüsselt werden.\n"
    );
    exit(1);
}

$prompt = static function (
    string $label,
    string $default = ''
): string {
    $suffix = $default !== ''
        ? ' [' . $default . ']'
        : '';

    echo $label . $suffix . ': ';

    $value = fgets(STDIN);

    if ($value === false) {
        throw new RuntimeException(
            'Eingabe konnte nicht gelesen werden.'
        );
    }

    $value = trim($value);

    return $value === ''
        ? $default
        : $value;
};

$promptRequired = static function (
    string $label,
    string $default = ''
) use ($prompt): string {
    do {
        $value = $prompt($label, $default);

        if ($value === '') {
            echo "Bitte einen Wert eingeben.\n";
        }
    } while ($value === '');

    return $value;
};

$promptSecret = static function (string $label): string {
    echo $label . ': ';

    $hidden = false;
    $sttyState = null;

    if (DIRECTORY_SEPARATOR === '/') {
        $disabledFunctions = array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    (string) ini_get('disable_functions')
                )
            )
        );

        if (
            function_exists('shell_exec')
            && function_exists('system')
            && !in_array('shell_exec', $disabledFunctions, true)
            && !in_array('system', $disabledFunctions, true)
        ) {
            $sttyState = @shell_exec('stty -g 2>/dev/null');

            if (is_string($sttyState) && trim($sttyState) !== '') {
                @system('stty -echo');
                $hidden = true;
            }
        }
    }

    $value = fgets(STDIN);

    if ($hidden) {
        @system('stty ' . escapeshellarg(trim((string) $sttyState)));
        echo PHP_EOL;
    }

    if ($value === false) {
        throw new RuntimeException(
            'Eingabe konnte nicht gelesen werden.'
        );
    }

    return trim($value);
};

$envQuote = static function (string $value): string {
    if (
        str_contains($value, "\n")
        || str_contains($value, "\r")
    ) {
        throw new RuntimeException(
            'Mehrzeilige Werte werden in .env nicht unterstützt.'
        );
    }

    $value = str_replace(
        ['\\', '"', '$'],
        ['\\\\', '\\"', '\\$'],
        $value
    );

    return '"' . $value . '"';
};

echo "Essensbestellung - Neue Installation\n";
echo str_repeat('=', 40) . "\n\n";
echo "Vor diesem Schritt muss die leere MySQL/MariaDB-Datenbank im Hosting-Panel bereits angelegt sein.\n\n";

try {
    $timezone = $promptRequired(
        'Zeitzone',
        'Europe/Zurich'
    );

    try {
        new DateTimeZone($timezone);
    } catch (Throwable) {
        throw new RuntimeException(
            'Ungültige Zeitzone: ' . $timezone
        );
    }

    $dbHost = $promptRequired(
        'Datenbank-Host',
        'localhost'
    );

    $dbPort = $promptRequired(
        'Datenbank-Port',
        '3306'
    );

    if (!ctype_digit($dbPort)) {
        throw new RuntimeException(
            'Der Datenbank-Port muss numerisch sein.'
        );
    }

    $dbName = $promptRequired(
        'Datenbank-Name'
    );

    $dbUser = $promptRequired(
        'Datenbank-Benutzer'
    );

    $dbPassword = $promptSecret(
        'Datenbank-Passwort'
    );

    if ($dbPassword === '') {
        echo "Hinweis: Es wurde ein leeres Datenbank-Passwort eingegeben.\n";
    }

    echo "\nDatenbankverbindung wird geprüft ...\n";

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $dbHost,
        $dbPort,
        $dbName
    );

    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    echo "Datenbankverbindung erfolgreich.\n\n";

    do {
        $adminPassword = $promptSecret(
            'Neues Admin-Passwort (mindestens 10 Zeichen)'
        );

        if (strlen($adminPassword) < 10) {
            echo "Das Admin-Passwort muss mindestens 10 Zeichen lang sein.\n";
            continue;
        }

        $adminPasswordRepeat = $promptSecret(
            'Admin-Passwort wiederholen'
        );

        if ($adminPassword !== $adminPasswordRepeat) {
            echo "Die Passwörter stimmen nicht überein.\n";
            $adminPassword = '';
        }
    } while ($adminPassword === '');

    if (!extension_loaded('sodium')) {
        throw new RuntimeException(
            'Die PHP-Erweiterung sodium fehlt.'
        );
    }

    $appKey = base64_encode(
        random_bytes(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES
        )
    );

    $adminPasswordHash = password_hash(
        $adminPassword,
        PASSWORD_DEFAULT
    );

    if ($adminPasswordHash === false) {
        throw new RuntimeException(
            'Das Admin-Passwort konnte nicht gehasht werden.'
        );
    }

    $env = implode(
        PHP_EOL,
        [
            'APP_ENV=production',
            'APP_DEBUG=false',
            'APP_KEY=' . $envQuote($appKey),
            'APP_TIMEZONE=' . $envQuote($timezone),
            '',
            'ADMIN_PASSWORD_HASH=' . $envQuote($adminPasswordHash),
            '',
            'DB_HOST=' . $envQuote($dbHost),
            'DB_PORT=' . $envQuote($dbPort),
            'DB_NAME=' . $envQuote($dbName),
            'DB_USER=' . $envQuote($dbUser),
            'DB_PASSWORD=' . $envQuote($dbPassword),
            '',
        ]
    );

    echo "\nDatenbankmigrationen werden ausgeführt ...\n";

    $migrationService = new MigrationService(
        $pdo,
        $root . '/database/migrations'
    );

    $migrationService->migrate(
        static function (string $message): void {
            echo $message . PHP_EOL;
        }
    );

    if (file_put_contents($envFile, $env) === false) {
        throw new RuntimeException(
            '.env konnte nicht geschrieben werden.'
        );
    }

    @chmod($envFile, 0600);

    echo "\n.env wurde erstellt.\n";
    echo "APP_KEY wurde sicher erzeugt.\n";
    echo "Admin-Passwort wurde nur als Hash gespeichert.\n";

    echo "\nInstallation abgeschlossen.\n\n";
    echo "Nächste Schritte:\n";
    echo "1. Document Root der Domain/Subdomain auf den Ordner public/ setzen.\n";
    echo "2. HTTPS aktivieren.\n";
    echo "3. 'php bin/check.php' ausführen.\n";
    echo "4. /admin/login.php öffnen und Lagerdaten konfigurieren.\n";
    echo "5. APP_KEY und .env sicher sichern; APP_KEY bei bestehender Installation nie ersetzen.\n";

    exit(0);
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        "\nFEHLER: " . $exception->getMessage() . PHP_EOL
    );

    if (is_file($envFile)) {
        fwrite(
            STDERR,
            "Hinweis: .env wurde möglicherweise bereits erstellt. Vor einem erneuten setup.php-Lauf prüfen, ob die Installation wirklich frisch ist.\n"
        );
    }

    exit(1);
}
