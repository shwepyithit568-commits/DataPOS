<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'sales_channels')) {
                $table->json('sales_channels')->nullable()->after('capabilities_override');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'sales_channels')) {
                // Production Safety Policy: Snapshot current store sales channel configurations
                // before dropping the column. Fail-closed: if backup cannot be created or verified,
                // abort rollback immediately to prevent permanent data loss.
                $storesWithChannels = \Illuminate\Support\Facades\DB::table('stores')
                    ->whereNotNull('sales_channels')
                    ->select('id', 'slug', 'operation_mode', 'sales_channels')
                    ->get();

                if ($storesWithChannels->isNotEmpty()) {
                    $backupDir = storage_path('app/backups');
                    if (!is_dir($backupDir)) {
                        if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
                            throw new \RuntimeException("Failed to create backup directory for sales_channels rollback: {$backupDir}");
                        }
                    }

                    $timestamp = date('Y_m_d_His');
                    $backupPath = $backupDir . "/sales_channels_rollback_snapshot_{$timestamp}.json";
                    $jsonContent = $storesWithChannels->toJson(JSON_PRETTY_PRINT);
                    $bytesWritten = file_put_contents($backupPath, $jsonContent);

                    // Strict verification: file exists, non-zero size, valid JSON
                    if ($bytesWritten === false || !file_exists($backupPath) || filesize($backupPath) === 0) {
                        throw new \RuntimeException("Failed to write sales_channels rollback snapshot to: {$backupPath}");
                    }

                    $decoded = json_decode(file_get_contents($backupPath), true);
                    if (!is_array($decoded) || count($decoded) !== $storesWithChannels->count()) {
                        throw new \RuntimeException("Sales channels rollback snapshot verification failed (corrupt JSON) at: {$backupPath}");
                    }

                    \Illuminate\Support\Facades\Log::info("Sales channels rollback snapshot verified and saved successfully to: {$backupPath}");
                }

                $table->dropColumn('sales_channels');
            }
        });
    }
};
