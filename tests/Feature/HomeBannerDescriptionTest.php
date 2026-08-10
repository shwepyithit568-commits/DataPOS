<?php

namespace Tests\Feature;

use App\Models\HomeBanner;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeBannerDescriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->store = Store::create([
            'name' => 'Banner Store',
            'slug' => 'banner-store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'phone' => '09990000001',
            'role' => 'customer',
        ]);
        $this->manager->stores()->attach($this->store->id, [
            'role' => 'store_manager',
            'status' => 'active',
        ]);
    }

    /** Task 10A: description accepts null. */
    public function test_description_accepts_null_on_create(): void
    {
        $response = $this->actingAs($this->manager)->post('/store/banner-store/admin/banners', [
            'title' => 'No Description Banner',
            'page' => 'home',
            'image' => UploadedFile::fake()->create('banner.jpg', 20, 'image/jpeg'),
            'sort_order' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('home_banners', [
            'store_id' => $this->store->id,
            'title' => 'No Description Banner',
            'description' => null,
        ]);
    }

    /** Task 10A: description accepts a valid value. */
    public function test_description_accepts_valid_value_on_create(): void
    {
        $response = $this->actingAs($this->manager)->post('/store/banner-store/admin/banners', [
            'title' => 'Promo Banner',
            'page' => 'home',
            'description' => 'Limited time offer on all accessories.',
            'image' => UploadedFile::fake()->create('banner.jpg', 20, 'image/jpeg'),
            'sort_order' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('home_banners', [
            'store_id' => $this->store->id,
            'title' => 'Promo Banner',
            'description' => 'Limited time offer on all accessories.',
        ]);
    }

    /** Task 10A: description rejects values over 500 characters. */
    public function test_description_rejects_values_over_500_characters(): void
    {
        $response = $this->actingAs($this->manager)->post('/store/banner-store/admin/banners', [
            'title' => 'Too Long',
            'page' => 'home',
            'description' => str_repeat('x', 501),
            'image' => UploadedFile::fake()->create('banner.jpg', 20, 'image/jpeg'),
            'sort_order' => 1,
        ]);

        $response->assertSessionHasErrors(['description']);
        $this->assertDatabaseMissing('home_banners', [
            'store_id' => $this->store->id,
            'title' => 'Too Long',
        ]);
    }

    /** Task 10A: description persists on update. */
    public function test_description_persists_and_updates(): void
    {
        $banner = $this->store->homeBanners()->create([
            'title' => 'Original',
            'page' => 'home',
            'description' => 'First description.',
            'image_path' => 'banners/original.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $updateResponse = $this->actingAs($this->manager)
            ->put("/store/banner-store/admin/banners/{$banner->id}", [
                'title' => 'Updated Title',
                'description' => 'Second description.',
            ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHasNoErrors();

        $this->assertSame('Second description.', $banner->fresh()->description);
        // Updating with null clears the description.
        $this->actingAs($this->manager)
            ->put("/store/banner-store/admin/banners/{$banner->id}", [
                'title' => 'Updated Title',
                'description' => null,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($banner->fresh()->description);
    }

    /** Task 10A: banner remains store-scoped (another store cannot edit it). */
    public function test_banner_update_remains_store_scoped(): void
    {
        $ownerStore = Store::create(['name' => 'Owner Store', 'slug' => 'owner-store', 'is_active' => true]);
        $intruderStore = Store::create(['name' => 'Intruder Store', 'slug' => 'intruder-store', 'is_active' => true]);

        $ownerManager = User::factory()->create(['phone' => '09990000002', 'role' => 'customer']);
        $ownerManager->stores()->attach($ownerStore->id, ['role' => 'store_manager', 'status' => 'active']);

        $intruderManager = User::factory()->create(['phone' => '09990000003', 'role' => 'customer']);
        $intruderManager->stores()->attach($intruderStore->id, ['role' => 'store_manager', 'status' => 'active']);

        $banner = $ownerStore->homeBanners()->create([
            'title' => 'Owner Banner',
            'page' => 'home',
            'image_path' => 'banners/owner.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Intruder (attached only to their own store) must be blocked from the owner's banner.
        $this->actingAs($intruderManager)
            ->put("/store/intruder-store/admin/banners/{$banner->id}", [
                'title' => 'Hijacked',
                'description' => 'Stolen',
            ])
            ->assertStatus(403);

        $this->assertSame('Owner Banner', $banner->fresh()->title);
    }
}
