<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

/**
 * MySQL migration smoke test — the guard for "passes on SQLite, fails on
 * MySQL" regressions (e.g. identifier names over MySQL's 64-char limit,
 * reserved words, engine-specific DDL). The regular suite runs on SQLite
 * only, so this test re-runs the full migration set against a real MySQL/
 * MariaDB server when one is reachable.
 *
 * - Creates a scratch database (`datapos_migrate_smoke`), points the app's
 *   `mysql` connection at it, runs `migrate:fresh`, asserts every migration
 *   applied, then drops the scratch database. Nothing else is touched.
 * - **Skipped when no local MySQL is available** (unreachable server, bad
 *   credentials, or no pdo_mysql) — so it never breaks a machine without
 *   one. Configure with MYSQL_TEST_HOST/PORT/USERNAME/PASSWORD env vars.
 */
class MysqlMigrationSmokeTest extends TestCase
{
    private const SCRATCH_DB = 'datapos_migrate_smoke';

    public function test_migrate_fresh_runs_clean_on_mysql(): void
    {
        $config = $this->mysqlConfig();

        if (! $this->canConnect($config)) {
            $this->markTestSkipped(
                'Local MySQL/MariaDB not reachable — set MYSQL_TEST_HOST/PORT/USERNAME/PASSWORD to enable.'
            );
        }

        $this->createScratchDatabase($config);

        $this->overrideMysqlConnection($config, self::SCRATCH_DB);

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);

            $output = Artisan::output();
            $this->assertStringNotContainsString('FAIL', $output, "migrate:fresh failed on MySQL:\n{$output}");

            // Every migration file applied exactly once.
            $applied = DB::table('migrations')->count();
            $files = count(glob(database_path('migrations/*.php')));
            $this->assertSame($files, $applied, "Expected {$files} migrations applied, got {$applied}.");

            // Smoke: the ledger + a representative reconciliation table exist.
            $tables = DB::select('SHOW TABLES');
            $tableNames = array_map('current', $tables);
            foreach (['inventory_movements', 'inventory_reconciliations', 'customer_ledger_entries', 'pos_sale_items'] as $required) {
                $this->assertContains($required, $tableNames, "Missing table '{$required}' after migrate:fresh.");
            }
        } finally {
            DB::purge('mysql');
            $this->dropScratchDatabase($config);
        }
    }

    /**
     * @return array{host:string, port:int, username:string, password:string}
     */
    private function mysqlConfig(): array
    {
        return [
            'host' => env('MYSQL_TEST_HOST', '127.0.0.1'),
            'port' => (int) env('MYSQL_TEST_PORT', '3306'),
            'username' => env('MYSQL_TEST_USERNAME', 'root'),
            'password' => (string) env('MYSQL_TEST_PASSWORD', ''),
        ];
    }

    /**
     * @param  array{host:string, port:int, username:string, password:string}  $config
     */
    private function canConnect(array $config): bool
    {
        try {
            new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_TIMEOUT => 3],
            );

            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * @param  array{host:string, port:int, username:string, password:string}  $config
     */
    private function createScratchDatabase(array $config): void
    {
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']}",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $pdo->exec('DROP DATABASE IF EXISTS `' . self::SCRATCH_DB . '`');
        $pdo->exec('CREATE DATABASE `' . self::SCRATCH_DB . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    /**
     * @param  array{host:string, port:int, username:string, password:string}  $config
     */
    private function dropScratchDatabase(array $config): void
    {
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            $pdo->exec('DROP DATABASE IF EXISTS `' . self::SCRATCH_DB . '`');
        } catch (\PDOException) {
            // Best-effort cleanup only.
        }
    }

    /**
     * @param  array{host:string, port:int, username:string, password:string}  $config
     */
    private function overrideMysqlConnection(array $config, string $database): void
    {
        config(['database.connections.mysql' => [
            'driver' => 'mysql',
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $database,
            'username' => $config['username'],
            'password' => $config['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]]);

        config(['database.default' => 'mysql']);
        DB::purge('mysql');
    }
}
