<?php

declare(strict_types=1);

namespace App\Service;

use PDO;
use RuntimeException;
use Throwable;

final class MigrationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationDirectory
    ) {
    }

    public function migrate(?callable $output = null): array
    {
        $this->ensureMigrationTable();

        $baselined = $this->baselineExistingSchema();
        $applied = [];
        $skipped = [];

        foreach ($baselined as $migration) {
            if ($output !== null) {
                $output('[BASELINE] ' . $migration);
            }
        }

        $appliedMigrations = array_fill_keys(
            $this->findAppliedMigrations(),
            true
        );

        foreach ($this->findMigrationFiles() as $migration => $path) {
            if (isset($appliedMigrations[$migration])) {
                $skipped[] = $migration;
                if ($output !== null) {
                    $output('[OK] Bereits installiert: ' . $migration);
                }
                continue;
            }

            if ($output !== null) {
                $output('[RUN] ' . $migration);
            }

            $this->executeMigrationFile(
                $migration,
                $path
            );

            $this->recordMigration($migration);
            $applied[] = $migration;
            $appliedMigrations[$migration] = true;

            if ($output !== null) {
                $output('[OK] Installiert: ' . $migration);
            }
        }

        return [
            'baselined' => $baselined,
            'applied' => $applied,
            'skipped' => $skipped,
        ];
    }

    public function status(): array
    {
        $files = $this->findMigrationFiles();

        if (!$this->tableExists('schema_migrations')) {
            return array_map(
                static fn (string $migration): array => [
                    'migration' => $migration,
                    'applied' => false,
                ],
                array_keys($files)
            );
        }

        $applied = array_fill_keys(
            $this->findAppliedMigrations(),
            true
        );

        $status = [];

        foreach (array_keys($files) as $migration) {
            $status[] = [
                'migration' => $migration,
                'applied' => isset($applied[$migration]),
            ];
        }

        return $status;
    }

    private function ensureMigrationTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                migration VARCHAR(255) NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function baselineExistingSchema(): array
    {
        $existingRecords = $this->findAppliedMigrations();

        if ($existingRecords !== []) {
            return [];
        }

        $files = $this->findMigrationFiles();
        $baselined = [];

        $hasInitialSchema =
            $this->tableExists('settings')
            && $this->tableExists('groups')
            && $this->tableExists('categories')
            && $this->tableExists('products')
            && $this->tableExists('product_categories');

        $hasArticleNumber =
            $hasInitialSchema
            && $this->columnExists(
                'products',
                'article_number'
            );

        $hasOrders =
            $hasInitialSchema
            && $this->tableExists('orders')
            && $this->tableExists('order_items');

        $hasIntegerQuantities =
            $hasOrders
            && $this->columnIsInteger(
                'order_items',
                'quantity'
            );

        $hasRounding =
            $hasOrders
            && $this->columnExists(
                'orders',
                'rounding_amount'
            );

        $knownStates = [
            '001_initial_schema.sql' => $hasInitialSchema,
            '002_add_product_article_number.sql' => $hasArticleNumber,
            '003_create_orders.sql' => $hasOrders,
            '004_make_order_quantities_integer.sql' => $hasIntegerQuantities,
            '005_add_order_rounding.sql' => $hasRounding,
        ];

        foreach ($knownStates as $migration => $isApplied) {
            if (
                !$isApplied
                || !isset($files[$migration])
            ) {
                continue;
            }

            $this->recordMigration($migration);
            $baselined[] = $migration;
        }

        return $baselined;
    }

    private function findMigrationFiles(): array
    {
        if (!is_dir($this->migrationDirectory)) {
            throw new RuntimeException(
                'Migrationsordner nicht gefunden: '
                . $this->migrationDirectory
            );
        }

        $paths = glob(
            rtrim($this->migrationDirectory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . '*.sql'
        );

        if ($paths === false) {
            throw new RuntimeException(
                'Die Migrationsdateien konnten nicht gelesen werden.'
            );
        }

        $files = [];

        foreach ($paths as $path) {
            $filename = basename($path);

            if (
                preg_match(
                    '/^\d{3}_[A-Za-z0-9_-]+\.sql$/',
                    $filename
                ) !== 1
            ) {
                continue;
            }

            $files[$filename] = $path;
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    private function findAppliedMigrations(): array
    {
        if (!$this->tableExists('schema_migrations')) {
            return [];
        }

        $statement = $this->pdo->query(
            'SELECT migration
            FROM schema_migrations
            ORDER BY migration ASC'
        );

        return array_map(
            static fn (array $row): string =>
                (string) $row['migration'],
            $statement->fetchAll()
        );
    }

    private function recordMigration(string $migration): void
    {
        $statement = $this->pdo->prepare(
            'INSERT IGNORE INTO schema_migrations (
                migration
            ) VALUES (
                :migration
            )'
        );

        $statement->execute([
            'migration' => $migration,
        ]);
    }

    private function executeMigrationFile(
        string $migration,
        string $path
    ): void {
        $sql = file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException(
                'Migration konnte nicht gelesen werden: '
                . $migration
            );
        }

        $sql = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $sql
        ) ?? $sql;

        $trimmed = trim($sql);

        if ($trimmed === '') {
            throw new RuntimeException(
                'Migration ist leer: ' . $migration
            );
        }

        if ($trimmed === $migration) {
            throw new RuntimeException(
                'Migration enthält nur ihren Dateinamen '
                . 'und kein SQL: '
                . $migration
            );
        }

        $statements = $this->splitSqlStatements($sql);

        if ($statements === []) {
            throw new RuntimeException(
                'Migration enthält keine ausführbaren SQL-Anweisungen: '
                . $migration
            );
        }

        try {
            foreach ($statements as $statement) {
                $this->pdo->exec($statement);
            }
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Migration fehlgeschlagen: '
                . $migration
                . PHP_EOL
                . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);

        $singleQuoted = false;
        $doubleQuoted = false;
        $backtickQuoted = false;
        $lineComment = false;
        $blockComment = false;

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $next = $index + 1 < $length
                ? $sql[$index + 1]
                : '';

            if ($lineComment) {
                if ($char === "\n") {
                    $lineComment = false;
                    $buffer .= $char;
                }

                continue;
            }

            if ($blockComment) {
                if ($char === '*' && $next === '/') {
                    $blockComment = false;
                    $index++;
                }

                continue;
            }

            if (
                !$singleQuoted
                && !$doubleQuoted
                && !$backtickQuoted
            ) {
                if (
                    $char === '-'
                    && $next === '-'
                    && (
                        $index + 2 >= $length
                        || ctype_space($sql[$index + 2])
                    )
                ) {
                    $lineComment = true;
                    $index++;
                    continue;
                }

                if ($char === '#') {
                    $lineComment = true;
                    continue;
                }

                if ($char === '/' && $next === '*') {
                    $blockComment = true;
                    $index++;
                    continue;
                }
            }

            if (
                $char === "'"
                && !$doubleQuoted
                && !$backtickQuoted
            ) {
                if ($singleQuoted) {
                    if ($next === "'") {
                        $buffer .= "''";
                        $index++;
                        continue;
                    }

                    if (!$this->isEscaped($sql, $index)) {
                        $singleQuoted = false;
                    }
                } else {
                    $singleQuoted = true;
                }

                $buffer .= $char;
                continue;
            }

            if (
                $char === '"'
                && !$singleQuoted
                && !$backtickQuoted
            ) {
                if (!$this->isEscaped($sql, $index)) {
                    $doubleQuoted = !$doubleQuoted;
                }

                $buffer .= $char;
                continue;
            }

            if (
                $char === '`'
                && !$singleQuoted
                && !$doubleQuoted
            ) {
                $backtickQuoted = !$backtickQuoted;
                $buffer .= $char;
                continue;
            }

            if (
                $char === ';'
                && !$singleQuoted
                && !$doubleQuoted
                && !$backtickQuoted
            ) {
                $statement = trim($buffer);

                if ($statement !== '') {
                    $statements[] = $statement;
                }

                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $statement = trim($buffer);

        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }

    private function isEscaped(
        string $value,
        int $position
    ): bool {
        $slashes = 0;

        for (
            $index = $position - 1;
            $index >= 0 && $value[$index] === '\\';
            $index--
        ) {
            $slashes++;
        }

        return $slashes % 2 === 1;
    }

    private function tableExists(string $table): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = :table_name'
        );

        $statement->execute([
            'table_name' => $table,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function columnExists(
        string $table,
        string $column
    ): bool {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = :table_name
            AND column_name = :column_name'
        );

        $statement->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function columnIsInteger(
        string $table,
        string $column
    ): bool {
        $statement = $this->pdo->prepare(
            'SELECT data_type
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = :table_name
            AND column_name = :column_name
            LIMIT 1'
        );

        $statement->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        $dataType = $statement->fetchColumn();

        if ($dataType === false) {
            return false;
        }

        return in_array(
            strtolower((string) $dataType),
            [
                'tinyint',
                'smallint',
                'mediumint',
                'int',
                'integer',
                'bigint',
            ],
            true
        );
    }
}
