<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

/**
 * Create a database backup file.
 *
 * Run with: php artisan backup:database
 * Schedule with: php artisan schedule:run (see routes/console.php)
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--label=backup : Backup file label}';

    protected $description = 'Create a SQL dump of the application database';

    public function handle(DatabaseBackupService $backups): int
    {
        $result = $backups->create((string) $this->option('label'));

        $sizeKb = round($result['size'] / 1024, 1);
        $this->info("Backup created: {$result['filename']} ({$sizeKb} KB, {$result['driver']})");

        return self::SUCCESS;
    }
}
