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
                // before dropping the column to prevent permanent loss of post-migration owner customizations.
                try {
                    $storesWithChannels = \Illuminate\Support\Facades\DB::table('stores')
                        ->whereNotNull('sales_channels')
                        ->select('id', 'slug', 'operation_mode', 'sales_channels')
                        ->get();

                    if ($storesWithChannels->isNotEmpty()) {
                        $backupDir = storage_path('app/backups');
                        if (!is_dir($backupDir)) {
                            @mkdir($backupDir, 0755, true);
                        }
                        $timestamp = date('Y_m_d_His');
                        $backupPath = $backupDir . "/sales_channels_rollback_snapshot_{$timestamp}.json";
                        file_put_contents($backupPath, $storesWithChannels->toJson(JSON_PRETTY_PRINT));
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to snapshot sales_channels prior to rollback: " . $e->getMessage());
                }

                $table->dropColumn('sales_channels');
            }
        });
    }
};
