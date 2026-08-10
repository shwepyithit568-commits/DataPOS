<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Separate brand assets — Storefront (horizontal), Admin (square icon) and
 * Favicon — with full backward compatibility for stores that only have the
 * legacy `logo_path`.
 */
class AdminBrandAssetsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->store = Store::create([
            'name' => 'Brand Asset Store',
            'slug' => 'brand-asset-store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'customer']);
        $this->manager->stores()->attach($this->store->id, [
            'role' => 'store_manager',
            'status' => 'active',
        ]);
    }

    private function settingWithLegacyLogo(): StorefrontSetting
    {
        Storage::disk('public')->put('store-logos/legacy.png', 'legacy-bytes');

        return $this->store->setting()->create([
            'store_name' => 'Brand Asset Store',
            'logo_path' => 'store-logos/legacy.png',
            'default_language' => 'my',
        ]);
    }

    /** Generate a real GD PNG so Laravel's `image` rule passes. */
    private function makePng(int $w, int $h, bool $transparent = false, string $name = 'asset.png'): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        if ($transparent) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $trans);
            imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, imagecolorallocatealpha($img, 210, 30, 30, 0));
        } else {
            $col = imagecolorallocate($img, 210, 30, 30);
            imagefill($img, 0, 0, $col);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'ba_') . '.png';
        imagepng($img, $tmp);
        imagedestroy($img);

        return new UploadedFile($tmp, $name, 'image/png', null, true);
    }

    private function settingsUrl(?string $section = null): string
    {
        return '/store/' . $this->store->slug . '/admin/settings' . ($section ? '/' . $section : '');
    }

    /** 1. Migration preserves existing logo_path (columns exist + value kept). */
    public function test_migration_adds_columns_and_preserves_existing_logo_path(): void
    {
        $setting = $this->settingWithLegacyLogo();

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('storefront_settings', [
            'storefront_logo_path', 'admin_logo_path', 'favicon_path',
        ]));
        $this->assertSame('store-logos/legacy.png', $setting->fresh()->logo_path);
    }

    /** 2. Storefront falls back to logo_path. */
    public function test_storefront_falls_back_to_legacy_logo_path(): void
    {
        $setting = $this->settingWithLegacyLogo();

        $this->assertSame('store-logos/legacy.png', $setting->storefrontLogo());
    }

    /** 3. Admin falls back through admin → storefront → legacy. */
    public function test_admin_logo_fallback_order(): void
    {
        $setting = $this->settingWithLegacyLogo();
        $this->assertSame('store-logos/legacy.png', $setting->adminLogo());

        $setting->update(['storefront_logo_path' => 'store-logos/sf.png']);
        $this->assertSame('store-logos/sf.png', $setting->adminLogo());

        $setting->update(['admin_logo_path' => 'admin-logos/adm.png']);
        $this->assertSame('admin-logos/adm.png', $setting->adminLogo());
    }

    /** 4. Favicon falls back through favicon → admin → storefront → legacy. */
    public function test_favicon_fallback_order(): void
    {
        $setting = $this->settingWithLegacyLogo();
        $this->assertSame('store-logos/legacy.png', $setting->favicon());

        $setting->update(['storefront_logo_path' => 'store-logos/sf.png']);
        $this->assertSame('store-logos/sf.png', $setting->favicon());

        $setting->update(['admin_logo_path' => 'admin-logos/adm.png']);
        $this->assertSame('admin-logos/adm.png', $setting->favicon());

        $setting->update(['favicon_path' => 'favicons/fav.png']);
        $this->assertSame('favicons/fav.png', $setting->favicon());
    }

    /** 5/6/7. Each dedicated upload succeeds and stores an optimized file. */
    public function test_storefront_logo_upload_succeeds(): void
    {
        $setting = $this->settingWithLegacyLogo();

        $this->actingAs($this->manager)
            ->from($this->settingsUrl())
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'storefront_logo' => $this->makePng(320, 106, false, 'sf.png'),
            ])
            ->assertRedirect($this->settingsUrl());

        $path = $setting->fresh()->storefront_logo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        // Legacy logo untouched by a specialized upload.
        Storage::disk('public')->assertExists('store-logos/legacy.png');
        $this->assertSame('store-logos/legacy.png', $setting->fresh()->logo_path);
    }

    public function test_admin_logo_upload_succeeds(): void
    {
        $setting = $this->settingWithLegacyLogo();

        $this->actingAs($this->manager)
            ->from($this->settingsUrl())
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'admin_logo' => $this->makePng(256, 256, false, 'adm.png'),
            ])
            ->assertRedirect($this->settingsUrl());

        $path = $setting->fresh()->admin_logo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_favicon_upload_succeeds(): void
    {
        $setting = $this->settingWithLegacyLogo();

        $this->actingAs($this->manager)
            ->from($this->settingsUrl())
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'favicon' => $this->makePng(128, 128, false, 'fav.png'),
            ])
            ->assertRedirect($this->settingsUrl());

        $path = $setting->fresh()->favicon_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    /** 8. Invalid file types are rejected. */
    public function test_invalid_file_type_is_rejected(): void
    {
        $this->settingWithLegacyLogo();

        $this->actingAs($this->manager)
            ->from($this->settingsUrl())
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'storefront_logo' => UploadedFile::fake()->create('logo.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('storefront_logo');

        $this->assertDatabaseHas('storefront_settings', ['store_id' => $this->store->id, 'storefront_logo_path' => null]);
    }

    /** 9. Oversized files are rejected. */
    public function test_oversized_file_is_rejected(): void
    {
        $this->settingWithLegacyLogo();

        // 1600×1600 with random 2×2 color blocks → noisy, poorly-compressible PNG (> 2 MB).
        $big = imagecreatetruecolor(1600, 1600);
        for ($x = 0; $x < 1600; $x += 2) {
            for ($y = 0; $y < 1600; $y += 2) {
                $col = imagecolorallocate($big, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                imagefilledrectangle($big, $x, $y, min($x + 1, 1599), min($y + 1, 1599), $col);
            }
        }
        $tmp = tempnam(sys_get_temp_dir(), 'big_') . '.png';
        imagepng($big, $tmp);
        imagedestroy($big);
        $this->assertGreaterThan(2 * 1024 * 1024, filesize($tmp), 'Test image must exceed 2 MB.');

        $this->actingAs($this->manager)
            ->from($this->settingsUrl())
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'storefront_logo' => new UploadedFile($tmp, 'big.png', 'image/png', null, true),
            ])
            ->assertSessionHasErrors('storefront_logo');
    }

    /** 10. Transparent PNG keeps its alpha channel after optimization. */
    public function test_transparent_png_remains_transparent(): void
    {
        $setting = $this->settingWithLegacyLogo();

        // A noisy large transparent PNG forces the optimizer's re-encode path.
        $img = imagecreatetruecolor(700, 300);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $trans);
        for ($y = 0; $y < 300; $y += 8) {
            imagefilledrectangle($img, 0, $y, 699, $y + 3, imagecolorallocatealpha($img, ($y * 3) % 255, 20, 60, 0));
        }
        $tmp = tempnam(sys_get_temp_dir(), 'alpha_') . '.png';
        imagepng($img, $tmp);
        imagedestroy($img);

        $this->actingAs($this->manager)
            ->from($this->settingsUrl())
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'storefront_logo' => new UploadedFile($tmp, 'alpha.png', 'image/png', null, true),
            ]);

        $path = $setting->fresh()->storefront_logo_path;
        $this->assertNotNull($path);
        $full = Storage::disk('public')->path($path);
        $decoded = @imagecreatefromstring((string) file_get_contents($full));
        $this->assertNotFalse($decoded, 'Stored file must remain a decodable image.');

        // Sample a pixel that is transparent in the source (between the opaque bars).
        $transparentPx = imagecolorat($decoded, 5, 5) >> 24 & 0x7F;
        $this->assertGreaterThan(0, $transparentPx, 'Transparent region must stay transparent (alpha preserved).');
    }

    /** 11/12/13. Replacing a specialized asset deletes only its replaced file. */
    public function test_storefront_replacement_deletes_only_replaced_file(): void
    {
        $setting = $this->settingWithLegacyLogo();
        Storage::disk('public')->put('store-logos/old-sf.png', 'old');
        $setting->update(['storefront_logo_path' => 'store-logos/old-sf.png']);

        $this->actingAs($this->manager)
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'storefront_logo' => $this->makePng(320, 106, false, 'new-sf.png'),
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('store-logos/old-sf.png');
        Storage::disk('public')->assertExists('store-logos/legacy.png');
        $this->assertNotNull($setting->fresh()->storefront_logo_path);
    }

    public function test_admin_replacement_deletes_only_replaced_file(): void
    {
        $setting = $this->settingWithLegacyLogo();
        Storage::disk('public')->put('admin-logos/old-adm.png', 'old');
        $setting->update(['admin_logo_path' => 'admin-logos/old-adm.png']);

        $this->actingAs($this->manager)
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'admin_logo' => $this->makePng(256, 256, false, 'new-adm.png'),
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('admin-logos/old-adm.png');
        Storage::disk('public')->assertExists('store-logos/legacy.png');
    }

    public function test_favicon_replacement_deletes_only_replaced_file(): void
    {
        $setting = $this->settingWithLegacyLogo();
        Storage::disk('public')->put('favicons/old-fav.png', 'old');
        $setting->update(['favicon_path' => 'favicons/old-fav.png']);

        $this->actingAs($this->manager)
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'favicon' => $this->makePng(128, 128, false, 'new-fav.png'),
            ])
            ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing('favicons/old-fav.png');
        Storage::disk('public')->assertExists('store-logos/legacy.png');
    }

    /** 14/15. Failed validation preserves previous files and creates no orphans. */
    public function test_failed_validation_preserves_existing_files(): void
    {
        $setting = $this->settingWithLegacyLogo();
        Storage::disk('public')->put('store-logos/keep.png', 'keep');
        $setting->update(['storefront_logo_path' => 'store-logos/keep.png']);

        $this->actingAs($this->manager)
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'storefront_logo' => UploadedFile::fake()->create('bad.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('storefront_logo');

        Storage::disk('public')->assertExists('store-logos/keep.png');
        Storage::disk('public')->assertExists('store-logos/legacy.png');
        $this->assertSame('store-logos/keep.png', $setting->fresh()->storefront_logo_path);
    }

    /** 14b. Failed database update removes orphan files and preserves previous DB paths. */
    public function test_failed_db_update_cleans_orphans_and_preserves_previous_files(): void
    {
        $setting = $this->settingWithLegacyLogo();
        $oldPath = $setting->fresh()->storefront_logo_path; // null — legacy-only

        // Force the DB save to fail after the new file was already stored.
        // The `saving` model event fires before the UPDATE executes.
        StorefrontSetting::saving(function () {
            throw new \RuntimeException('Forced DB failure');
        });

        $this->actingAs($this->manager)
            ->from($this->settingsUrl())
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'storefront_logo' => $this->makePng(320, 106, false, 'orphan.png'),
            ])
            ->assertStatus(500);

        // No orphan file was left behind.
        $this->assertCount(0, collect(Storage::disk('public')->allFiles('store-logos'))
            ->filter(fn ($p) => str_ends_with($p, 'orphan.png')));

        // DB + previous files unchanged.
        $fresh = $setting->fresh();
        $this->assertNull($fresh->storefront_logo_path);
        $this->assertSame('store-logos/legacy.png', $fresh->logo_path);
        Storage::disk('public')->assertExists('store-logos/legacy.png');
        $this->assertSame($oldPath, $fresh->storefront_logo_path);
    }

    /** 16/17/18. Removing a specialized logo activates its fallback without touching other assets. */
    public function test_removing_storefront_logo_activates_legacy_fallback(): void
    {
        $setting = $this->settingWithLegacyLogo();
        Storage::disk('public')->put('store-logos/sf.png', 'sf');
        $setting->update(['storefront_logo_path' => 'store-logos/sf.png']);

        $this->actingAs($this->manager)
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'storefront_logo_remove' => '1',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $setting->fresh();
        Storage::disk('public')->assertMissing('store-logos/sf.png');
        Storage::disk('public')->assertExists('store-logos/legacy.png');
        $this->assertNull($fresh->storefront_logo_path);
        $this->assertSame('store-logos/legacy.png', $fresh->storefrontLogo());
    }

    public function test_removing_admin_logo_does_not_delete_storefront_logo(): void
    {
        $setting = $this->settingWithLegacyLogo();
        Storage::disk('public')->put('admin-logos/adm.png', 'adm');
        Storage::disk('public')->put('store-logos/sf.png', 'sf');
        $setting->update(['admin_logo_path' => 'admin-logos/adm.png', 'storefront_logo_path' => 'store-logos/sf.png']);

        $this->actingAs($this->manager)
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'admin_logo_remove' => '1',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $setting->fresh();
        Storage::disk('public')->assertMissing('admin-logos/adm.png');
        Storage::disk('public')->assertExists('store-logos/sf.png');
        Storage::disk('public')->assertExists('store-logos/legacy.png');
        $this->assertNull($fresh->admin_logo_path);
        // Admin now falls back to the Storefront logo.
        $this->assertSame('store-logos/sf.png', $fresh->adminLogo());
    }

    public function test_removing_favicon_does_not_delete_other_assets(): void
    {
        $setting = $this->settingWithLegacyLogo();
        Storage::disk('public')->put('favicons/fav.png', 'fav');
        Storage::disk('public')->put('admin-logos/adm.png', 'adm');
        $setting->update(['favicon_path' => 'favicons/fav.png', 'admin_logo_path' => 'admin-logos/adm.png']);

        $this->actingAs($this->manager)
            ->post($this->settingsUrl(), [
                'store_name' => 'Brand Asset Store',
                'default_language' => 'my',
                'favicon_remove' => '1',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $setting->fresh();
        Storage::disk('public')->assertMissing('favicons/fav.png');
        Storage::disk('public')->assertExists('admin-logos/adm.png');
        Storage::disk('public')->assertExists('store-logos/legacy.png');
        $this->assertNull($fresh->favicon_path);
        $this->assertSame('admin-logos/adm.png', $fresh->favicon());
    }

    /** 19. Admin layout uses admin_logo_path. */
    public function test_admin_layout_uses_admin_logo(): void
    {
        $setting = $this->settingWithLegacyLogo();
        $setting->update(['admin_logo_path' => 'admin-logos/adm.png']);
        Storage::disk('public')->put('admin-logos/adm.png', 'adm');

        $this->actingAs($this->manager)
            ->get($this->settingsUrl())
            ->assertOk()
            ->assertSee(asset('storage/admin-logos/adm.png'), false);
    }

    /** 20. Storefront layout uses storefront_logo_path. */
    public function test_storefront_layout_uses_storefront_logo(): void
    {
        $setting = $this->settingWithLegacyLogo();
        $setting->update(['storefront_logo_path' => 'store-logos/sf.png']);
        Storage::disk('public')->put('store-logos/sf.png', 'sf');

        $this->actingAs($this->manager)
            ->get('/store/' . $this->store->slug)
            ->assertOk()
            ->assertSee(asset('storage/store-logos/sf.png'), false);
    }

    /** 21. Login page uses the Storefront fallback. */
    public function test_login_page_uses_storefront_logo_fallback(): void
    {
        $this->settingWithLegacyLogo();

        $this->get('/login')
            ->assertOk()
            ->assertSee(asset('storage/store-logos/legacy.png'), false);
    }

    /** 21b. Invoice uses the Storefront fallback. */
    public function test_invoice_uses_storefront_logo_fallback(): void
    {
        $setting = $this->settingWithLegacyLogo();
        Storage::disk('public')->put('store-logos/sf.png', 'sf');
        $setting->update(['storefront_logo_path' => 'store-logos/sf.png']);

        $order = Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-BRAND-001',
            'customer_name' => 'Brand Buyer',
            'customer_phone' => '09222222222',
            'contact_channel' => 'viber',
            'contact_identifier' => '@brand_buyer',
            'pricing_type' => 'retail',
            'total_amount' => 10000.00,
        ]);

        $this->actingAs($this->manager)
            ->get('/store/' . $this->store->slug . '/admin/orders/' . $order->id . '/invoice')
            ->assertOk()
            ->assertSee(asset('storage/store-logos/sf.png'), false);
    }

    /** 22. Storefront favicon link uses the documented fallback chain. */
    public function test_favicon_link_uses_fallback(): void
    {
        $this->settingWithLegacyLogo();

        // Store with NO setting at all → static favicon.ico fallback.
        Store::create(['name' => 'No Setting Store', 'slug' => 'no-setting-store', 'is_active' => true]);

        $this->actingAs($this->manager)
            ->get('/store/no-setting-store')
            ->assertOk()
            ->assertSee(asset('favicon.ico'), false);

        // Store with only the legacy logo → legacy PNG is the favicon source.
        $this->actingAs($this->manager)
            ->get('/store/' . $this->store->slug)
            ->assertOk()
            ->assertSee(asset('storage/store-logos/legacy.png'), false);

        // Dedicated favicon uploaded → it wins over every fallback.
        $setting = $this->store->setting;
        $setting->update(['favicon_path' => 'favicons/fav.png']);
        Storage::disk('public')->put('favicons/fav.png', 'fav');

        $this->actingAs($this->manager)
            ->get('/store/' . $this->store->slug)
            ->assertOk()
            ->assertSee(asset('storage/favicons/fav.png'), false);
    }

    /** 23. Every uploader has an accessible name (label + labelled input). */
    public function test_uploaders_have_accessible_labels(): void
    {
        $this->settingWithLegacyLogo();

        $html = $this->actingAs($this->manager)
            ->get($this->settingsUrl())
            ->assertOk()
            ->getContent();

        foreach (['Storefront Logo', 'Admin Logo', 'Favicon / App Icon'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        foreach (['brand-asset-storefront-logo', 'brand-asset-admin-logo', 'brand-asset-favicon'] as $id) {
            $this->assertStringContainsString('for="' . $id . '"', $html);
            $this->assertStringContainsString('id="' . $id . '"', $html);
        }
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('role="alert"', $html);
    }

    /** 25. Existing General Settings fields continue saving correctly. */
    public function test_existing_general_settings_fields_still_save(): void
    {
        $setting = $this->settingWithLegacyLogo();

        $this->actingAs($this->manager)
            ->from($this->settingsUrl())
            ->post($this->settingsUrl(), [
                'store_name' => 'Renamed Store',
                'tagline' => 'New tagline here',
                'opening_hours' => '10:00 - 18:00',
                'default_language' => 'en',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->settingsUrl());

        $fresh = $setting->fresh();
        $this->assertSame('Renamed Store', $fresh->store_name);
        $this->assertSame('New tagline here', $fresh->tagline);
        $this->assertSame('10:00 - 18:00', $fresh->opening_hours);
        $this->assertSame('en', $fresh->default_language);
        $this->assertSame('store-logos/legacy.png', $fresh->logo_path);
    }
}
