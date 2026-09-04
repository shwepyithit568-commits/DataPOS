<?php

use App\Models\Store;
use App\Services\StorefrontNavigationDefaultsService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultsService = app(StorefrontNavigationDefaultsService::class);

        Store::query()->chunk(50, function ($stores) use ($defaultsService) {
            foreach ($stores as $store) {
                $defaultsService->seedDefaultsForStore($store, false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or cleanup items if needed
    }
};
