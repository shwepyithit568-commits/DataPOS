<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Create and restore Full System (Database + Media Files ZIP) or SQL / SQLite database backups.
 *
 * Backups are stored on the local disk under `backups/` — never on the public disk.
 */
class DatabaseBackupService
{
    /** Directory (relative to the local disk root) where backups live. */
    public const DIRECTORY = 'backups';

    /** Keep at most this many backups; older ones are pruned. */
    public const KEEP = 14;

    /**
     * Create a backup snapshot.
     *
     * @param string $label
     * @param string $format 'zip' (Full: Database + Media), 'sql' (Database only), 'sqlite' (SQLite file)
     * @return array{filename: string, size: int, driver: string, format: string, created_at: \Illuminate\Support\Carbon}
     */
    public function create(string $label = 'backup', string $format = 'zip'): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        // 1. Full System ZIP (Database SQL + Media Files + Manifest)
        if ($format === 'zip' && class_exists('ZipArchive')) {
            $stamp = now()->format('Y-m-d_His');
            $safeLabel = preg_replace('/[^A-Za-z0-9_-]/', '_', $label) ?: 'full_backup';
            $filename = "{$safeLabel}_{$stamp}.full.zip";
            $zipRelative = self::DIRECTORY . '/' . $filename;
            $zipFullPath = Storage::disk('local')->path($zipRelative);

            $dir = dirname($zipFullPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                // Add Database SQL Dump
                $sql = $this->dump($pdo, $driver);
                $zip->addFromString('database.sql', $sql);

                // If SQLite, also include raw sqlite file
                if ($driver === 'sqlite') {
                    $dbPath = $connection->getDatabaseName();
                    if (file_exists($dbPath)) {
                        $zip->addFile($dbPath, 'database.sqlite');
                    }
                }

                // Add all uploaded media files (logos, product images, banners, receipts)
                $publicPath = storage_path('app/public');
                if (is_dir($publicPath)) {
                    $this->addDirectoryToZip($zip, $publicPath, 'media');
                }

                // Add Manifest
                $manifest = [
                    'app'           => 'DataPOS',
                    'version'       => '2026.1',
                    'created_at'    => now()->toIso8601String(),
                    'driver'        => $driver,
                    'database_name' => basename($connection->getDatabaseName()),
                    'type'          => 'full_system_backup',
                ];
                $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

                $zip->close();
                $this->prune();

                return [
                    'filename'   => $filename,
                    'size'       => filesize($zipFullPath),
                    'driver'     => $driver,
                    'format'     => 'zip',
                    'created_at' => now(),
                ];
            }
        }

        // 2. Direct SQLite Snapshot
        if ($format === 'sqlite' && $driver === 'sqlite') {
            $dbPath = $connection->getDatabaseName();
            if (file_exists($dbPath)) {
                $stamp = now()->format('Y-m-d_His');
                $filename = "{$label}_{$stamp}.sqlite";
                $relative = self::DIRECTORY . '/' . $filename;
                Storage::disk('local')->put($relative, file_get_contents($dbPath));
                $this->prune();

                return [
                    'filename'   => $filename,
                    'size'       => filesize($dbPath),
                    'driver'     => $driver,
                    'format'     => 'sqlite',
                    'created_at' => now(),
                ];
            }
        }

        // 3. Standard Universal SQL Dump
        $filename = $this->filename($label, $driver);
        $relative = self::DIRECTORY . '/' . $filename;

        $sql = $this->dump($pdo, $driver);
        Storage::disk('local')->put($relative, $sql);

        $this->prune();

        return [
            'filename'   => $filename,
            'size'       => strlen($sql),
            'driver'     => $driver,
            'format'     => 'sql',
            'created_at' => now(),
        ];
    }

    /** @return array<int, array{filename: string, size: int, driver: string, format: string, created_at: \Illuminate\Support\Carbon}> */
    public function list(): array
    {
        $files = collect(Storage::disk('local')->files(self::DIRECTORY))
            ->filter(fn (string $path) => str_ends_with($path, '.zip') || str_ends_with($path, '.sql') || str_ends_with($path, '.sqlite'))
            ->map(function (string $path) {
                $basename = basename($path);
                $created = $this->timestampFromFilename($basename);

                if (str_ends_with($basename, '.zip')) {
                    $format = 'zip';
                } elseif (str_ends_with($basename, '.sqlite')) {
                    $format = 'sqlite';
                } else {
                    $format = 'sql';
                }

                $driver = str_contains($basename, 'sqlite') ? 'sqlite' : (str_contains($basename, 'mysql') ? 'mysql' : 'universal');

                return [
                    'filename'   => $basename,
                    'size'       => Storage::disk('local')->size($path),
                    'driver'     => $driver,
                    'format'     => $format,
                    'created_at' => $created,
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        return $files->all();
    }

    public function path(string $filename): string
    {
        return self::DIRECTORY . '/' . basename($filename);
    }

    public function exists(string $filename): bool
    {
        return Storage::disk('local')->exists($this->path($filename));
    }

    public function delete(string $filename): bool
    {
        return Storage::disk('local')->delete($this->path($filename));
    }

    /**
     * Restore database and media from an existing backup file.
     */
    public function restore(string $filename): void
    {
        $relPath = $this->path($filename);
        if (! Storage::disk('local')->exists($relPath)) {
            throw new \RuntimeException("Backup file {$filename} not found.");
        }

        $fullPath = Storage::disk('local')->path($relPath);

        // 1. Full ZIP Archive Restore
        if (str_ends_with($filename, '.zip') && class_exists('ZipArchive')) {
            $this->restoreFromZipFile($fullPath);
            return;
        }

        // 2. Direct SQLite Binary File Restore
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (str_ends_with($filename, '.sqlite') && $driver === 'sqlite') {
            $targetDb = $connection->getDatabaseName();
            $content = Storage::disk('local')->get($relPath);
            file_put_contents($targetDb, $content);
            return;
        }

        // 3. SQL Dump Restore
        $sql = Storage::disk('local')->get($relPath);
        $this->restoreFromSql($sql);
    }

    /**
     * Restore database and media from an uploaded backup file (.zip, .sql, .sqlite).
     */
    public function restoreFromUploadedFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'zip' && class_exists('ZipArchive')) {
            $this->restoreFromZipFile($file->getRealPath());
            return;
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($extension === 'sqlite' && $driver === 'sqlite') {
            $targetDb = $connection->getDatabaseName();
            copy($file->getRealPath(), $targetDb);
            return;
        }

        $sql = file_get_contents($file->getRealPath());
        $this->restoreFromSql($sql);
    }

    /**
     * Restore from a ZIP archive file (Database SQL + Media Files).
     */
    public function restoreFromZipFile(string $zipFilePath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new \RuntimeException('Failed to open backup ZIP archive.');
        }

        // 1. Restore Database SQL
        $sql = $zip->getFromName('database.sql');
        if ($sql !== false && ! empty($sql)) {
            $this->restoreFromSql($sql);
        }

        // 2. Extract media files back into storage/app/public/
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if (str_starts_with($entryName, 'media/') && ! str_ends_with($entryName, '/')) {
                $relPath = substr($entryName, 6); // remove 'media/'
                $destPath = storage_path('app/public/' . $relPath);
                $destDir = dirname($destPath);
                if (! is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                file_put_contents($destPath, $zip->getFromIndex($i));
            }
        }

        $zip->close();
    }

    /**
     * Restore database from raw SQL queries.
     */
    public function restoreFromSql(string $sql): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        if ($driver === 'sqlite') {
            $cleanSql = preg_replace('/SET\s+FOREIGN_KEY_CHECKS\s*=\s*[01];?/i', '', $sql);
            $pdo->exec('PRAGMA foreign_keys = OFF;');
            $pdo->exec($cleanSql);
            $pdo->exec('PRAGMA foreign_keys = ON;');
        } else {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
            $pdo->exec($sql);
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    /** Add a directory recursively to a ZIP archive. */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipDir): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(rtrim($dir, '\\/')) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            // Skip hidden gitignore or empty files if needed
            if ($file->isDir()) {
                $zip->addEmptyDir($zipDir . '/' . $relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $zipDir . '/' . $relativePath);
            }
        }
    }

    /** Remove the oldest backups once the count exceeds KEEP. */
    private function prune(): void
    {
        $backups = $this->list();

        if (count($backups) <= self::KEEP) {
            return;
        }

        foreach (array_slice($backups, self::KEEP) as $old) {
            $this->delete($old['filename']);
        }
    }

    private function filename(string $label, string $driver): string
    {
        $stamp = now()->format('Y-m-d_His');
        $safeLabel = preg_replace('/[^A-Za-z0-9_-]/', '_', $label) ?: 'backup';

        return "{$safeLabel}_{$stamp}.{$driver}.sql";
    }

    private function timestampFromFilename(string $filename): \Illuminate\Support\Carbon
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2}_\d{6})/', $filename, $m)) {
            try {
                return \Illuminate\Support\Carbon::createFromFormat('Y-m-d_His', $m[1]);
            } catch (\Throwable) {
                // fall through to file modification time
            }
        }

        $full = $this->path($filename);
        $mtime = Storage::disk('local')->lastModified($full);

        return \Illuminate\Support\Carbon::createFromTimestamp($mtime);
    }

    private function dump(PDO $pdo, string $driver): string
    {
        $lines = [];
        $lines[] = '-- DataPOS database backup';
        $lines[] = '-- Driver: ' . $driver;
        $lines[] = '-- Created at: ' . now()->toDateTimeString();
        $lines[] = '-- Generated by the application (no shell tools required)';
        $lines[] = '';

        if ($driver === 'sqlite') {
            $lines[] = 'PRAGMA foreign_keys = OFF;';
        } else {
            $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        }
        $lines[] = '';

        $tables = $this->tables($pdo, $driver);

        foreach ($tables as $table) {
            $lines = array_merge($lines, $this->dumpTable($pdo, $driver, $table));
            $lines[] = '';
        }

        if ($driver === 'sqlite') {
            $lines[] = 'PRAGMA foreign_keys = ON;';
        } else {
            $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        }

        return implode(PHP_EOL, $lines);
    }

    /** @return array<int, string> */
    private function tables(PDO $pdo, string $driver): array
    {
        if ($driver === 'sqlite') {
            $rows = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

            return $rows;
        }

        // MySQL / MariaDB
        return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return array<int, string> */
    private function dumpTable(PDO $pdo, string $driver, string $table): array
    {
        $lines = [];

        // Schema
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?");
            $stmt->execute([$table]);
            $create = $stmt->fetchColumn();
            if ($create) {
                $lines[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
                $lines[] = $create . ';';
            }
        } else {
            $stmt = $pdo->prepare('SHOW CREATE TABLE `' . $table . '`');
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $create = $row[1] ?? '';
            $lines[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
            $lines[] = $create . ';';
        }

        $lines[] = '';

        // Data
        $stmt = $pdo->query('SELECT * FROM `' . $table . '`');
        $columns = $this->columns($stmt, $driver);
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);

        if ($rows === []) {
            return $lines;
        }

        $colList = implode(', ', array_map(fn ($c) => '`' . $c . '`', $columns));

        foreach ($rows as $row) {
            $values = array_map(fn ($value) => $this->sqlValue($pdo, $value, $driver), $row);
            $lines[] = 'INSERT INTO `' . $table . '` (' . $colList . ') VALUES (' . implode(', ', $values) . ');';
        }

        return $lines;
    }

    /** @return array<int, string> */
    private function columns(\PDOStatement $stmt, string $driver): array
    {
        $count = $stmt->columnCount();
        $columns = [];

        for ($i = 0; $i < $count; $i++) {
            $meta = $stmt->getColumnMeta($i);
            $columns[] = (string) ($meta['name'] ?? $i);
        }

        return $columns;
    }

    private function sqlValue(PDO $pdo, mixed $value, string $driver): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $quoted = $pdo->quote((string) $value);

        return $quoted === false ? "''" : $quoted;
    }
}
