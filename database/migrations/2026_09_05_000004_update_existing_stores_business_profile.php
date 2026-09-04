<?php

use App\BusinessProfiles\BusinessProfile;
use App\BusinessProfiles\BusinessProfileRegistry;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $stores = Store::whereNull('business_profile')->orWhere('business_profile', '')->get();

        foreach ($stores as $store) {
            $resolved = BusinessProfileRegistry::resolveProfile(null, $store->business_type);
            $store->update([
                'business_profile' => $resolved ?: BusinessProfile::MOBILE_ELECTRONICS,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse action needed
    }
};
