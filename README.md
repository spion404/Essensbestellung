# Essensbestellung

Webanwendung zur Verwaltung von Essensbestellungen für Pfadilager.

Das Tool ist dafür gedacht, auf einem normalen Webhosting installiert und für ein einzelnes Lager betrieben zu werden. Gruppen erfassen ihre Tagesbestellungen selbst; die Administration verwaltet Gruppen, Produkte, Budgets, Bestellungen, Tagesauswertungen und Exporte.

## Funktionen

### Gruppenbereich

- passwortgeschützter Zugang pro Gruppe
- Liefertage gemäss Lagerkonfiguration
- Tages- und Gesamtbudget
- Suche und Filter im Produktkatalog
- Bestellung in ganzen Packungen
- Entwurf speichern und definitiv bestätigen
- Bestellschluss am Vortag zur konfigurierten Uhrzeit
- Budgetwarnung bei Überschreitung des berechneten Tagesbudgets
- Anzeige von Budgetüberträgen und Rundungen
- PDF-Export definitiv bestätigter Bestellungen

### Administration

- passwortgeschützter Adminbereich
- Gruppen- und Teilnehmerverwaltung
- Lagerdaten und Budgetansätze
- Produkt- und Kategorienverwaltung
- XLSX-Produktimport
- Tagesbudgets
- Tagesauswertung aller Gruppen
- Admin-Korrekturen auch nach Bestellschluss
- Rundung / Kostenkorrektur pro Bestellung
- XLSX- und CSV-Export der Sammelbestellung
- Kommissionierung pro Gruppe
- druckbare Kommissionierliste
- PDF-Export der Kommissionierliste

## Systemanforderungen

Aktuell unterstützte Umgebung:

- Apache oder Nginx
- PHP >= 8.3
- MySQL >= 8.4 oder MariaDB >= 10.11
- Composer 2
- UTF-8 / utf8mb4
- Möglichkeit, den Document Root einer Domain oder Subdomain auf `public/` zu setzen
- für die empfohlene Installation: SSH-Zugang mit PHP-CLI und Composer

Benötigte PHP-Erweiterungen:

- ctype
- dom
- fileinfo
- filter
- gd
- iconv
- json
- libxml
- mbstring
- openssl
- PDO
- PDO_MySQL
- session
- SimpleXML
- sodium
- xml
- XMLReader
- XMLWriter
- zip
- zlib

PhpSpreadsheet 5.9 benötigt einen grossen Teil dieser Erweiterungen ebenfalls direkt. Dompdf verwendet insbesondere `dom` und `mbstring` für die PDF-Erzeugung. Vor einer Installation kann mit `php bin/check.php` geprüft werden, ob der Hoster alle Voraussetzungen erfüllt.

## Empfohlene Verzeichnisstruktur

Die Domain darf **nicht** auf das Projektverzeichnis selbst zeigen, sondern auf `public/`:

```text
/home/USER/apps/Essensbestellung/
├── bin/
├── config/
├── database/
├── src/
├── templates/
├── vendor/
├── .env
└── public/              <- Document Root der Domain/Subdomain
    ├── index.php
    ├── admin/
    ├── group/
    └── assets/
```

Dadurch sind `.env`, Datenbankmigrationen, PHP-Klassen und Composer-Dateien nicht direkt über HTTP erreichbar.

## Neue Installation auf einem Webhoster

### 1. Datenbank anlegen

Im Hosting-Panel zuerst eine leere MySQL- oder MariaDB-Datenbank samt Benutzer anlegen.

Benötigt werden:

- Datenbank-Host
- Port, normalerweise `3306`
- Datenbankname
- Benutzername
- Passwort

Die Datenbank sollte `utf8mb4` verwenden.

### 2. Repository installieren

Per SSH:

```bash
git clone https://github.com/spion404/Essensbestellung.git
cd Essensbestellung
```

Abhängigkeiten installieren:

```bash
composer install --no-dev --optimize-autoloader
```

Dompdf wird über Composer installiert und dient sowohl dem Gruppen-PDF als auch dem PDF der Kommissionierliste.

Falls Composer wegen fehlender PHP-Erweiterungen abbricht, müssen diese zuerst im Hosting-Panel aktiviert oder durch den Hoster bereitgestellt werden.

### 3. Setup-Assistent starten

```bash
php bin/setup.php
```

Der Assistent fragt nach:

- Zeitzone
- Datenbankzugang
- Admin-Passwort

Er erzeugt automatisch:

- `.env` mit `APP_ENV=production`
- einen zufälligen `APP_KEY`
- einen sicheren Hash des Admin-Passworts
- die Datenbanktabellen über alle vorhandenen Migrationen
- die Tabelle `schema_migrations` zur Versionsverwaltung der Datenbank

**Wichtig:** Der `APP_KEY` darf bei einer bestehenden Installation nie neu erzeugt werden. Mit ihm werden die Gruppenpasswörter verschlüsselt. Ohne den ursprünglichen Schlüssel können bereits gespeicherte Gruppenpasswörter nicht mehr entschlüsselt werden.

### 4. Document Root konfigurieren

Im Hosting-Panel die Domain oder Subdomain auf den Ordner

```text
/PFAD/ZUR/Essensbestellung/public
```

setzen.

Beispiel:

```text
essen.example.ch -> /home/USER/apps/Essensbestellung/public
```

HTTPS sollte aktiviert sein.

### 5. Systemcheck ausführen

```bash
php bin/check.php
```

Der Check kontrolliert unter anderem:

- PHP-Version
- benötigte PHP-Erweiterungen
- Composer-Abhängigkeiten
- `.env`
- `APP_KEY`
- Admin-Passwort-Hash
- Zeitzone
- Datenbankverbindung
- MySQL-/MariaDB-Version
- Datenbank-Zeichensatz
- ausstehende Migrationen

Ein erfolgreicher Check endet mit:

```text
Ergebnis: 0 Fehler, 0 Warnung(en).
```

Warnungen sind nicht immer fatal, sollten vor dem produktiven Betrieb aber geprüft werden.

### 6. Anwendung konfigurieren

Im Browser öffnen:

```text
https://DEINE-DOMAIN/admin/login.php
```

Danach zuerst unter **Einstellungen** Lagername, Budgets, Bestellschluss und Lagerdaten konfigurieren. Anschliessend Gruppen und Produkte anlegen bzw. importieren.

## Datenbankmigrationen

Alle Datenbankänderungen liegen in:

```text
database/migrations/
```

Die Anwendung führt sie in Dateinamensreihenfolge aus, beispielsweise:

```text
001_initial_schema.sql
002_add_product_article_number.sql
003_create_orders.sql
004_make_order_quantities_integer.sql
005_add_order_rounding.sql
```

Ausführen:

```bash
php bin/migrate.php
```

Bereits installierte Migrationen werden in `schema_migrations` gespeichert und beim nächsten Lauf übersprungen.

### Bestehende Installation vor Einführung von `schema_migrations`

Wenn die Tabellen bereits manuell angelegt wurden, erkennt der erste Lauf von

```bash
php bin/migrate.php
```

den bekannten Stand 001 bis 005 anhand der vorhandenen Tabellen und Spalten und registriert die bereits installierten Migrationen automatisch.

Dadurch muss eine bestehende Entwicklungsdatenbank nicht neu aufgebaut werden.

Vor Datenbankmigrationen auf einem produktiven Lager sollte trotzdem immer ein Datenbank-Backup erstellt werden.

## Updates

Empfohlener Ablauf:

```bash
git pull
composer install --no-dev --optimize-autoloader
php bin/migrate.php
php bin/check.php
```

Vor einem Update mit Datenbankmigrationen wird ein Backup von Datenbank und `.env` empfohlen.

## Manuelle Installation ohne SSH

Der primär unterstützte Installationsweg verwendet PHP-CLI und Composer über SSH.

Wenn ein Hoster keinen SSH-Zugang anbietet, kann das Projekt grundsätzlich lokal mit

```bash
composer install --no-dev --optimize-autoloader
```

vorbereitet und inklusive `vendor/` hochgeladen werden. `.env` und Datenbank müssen dann manuell eingerichtet werden; Migrationen müssten beispielsweise über phpMyAdmin ausgeführt werden.

Dieser Weg ist fehleranfälliger und sollte erst verwendet werden, wenn der SSH-Weg beim gewünschten Hoster nicht möglich ist.

## Sicherheitshinweise

- `.env` niemals committen oder öffentlich zugänglich machen.
- Document Root immer auf `public/` setzen.
- `APP_DEBUG=false` im produktiven Betrieb verwenden.
- HTTPS aktivieren.
- `APP_KEY` und `.env` sicher sichern.
- Datenbank vor Migrationen sichern.
- Admin-Passwort nicht mit Gruppenpasswörtern teilen.

## Entwicklung / Herkunft

Das Essensbestellungstool wurde ursprünglich für das KALA 2027 der Pfadi Unterwalden entwickelt.

Wenn du das Tool auch für dein Lager verwenden möchtest und Unterstützung brauchst, kannst du dich gerne bei Spion melden.
