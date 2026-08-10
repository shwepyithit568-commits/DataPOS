<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Proves the AdminListReturn round-trip: visiting a filtered admin list stores
 * the filtered URI in the session; opening the edit page must NOT consume it
 * (peek), and submitting update() must redirect back to the filtered list.
 */
class AdminFilterReturnRoundTripTest extends TestCase
{
    use RefreshDatabase;

    private function adminStore(): array
    {
        $store = Store::create(['slug' => 'roundtrip-store', 'name' => 'Roundtrip Store']);
        $owner = User::factory()->create(['role' => 'platform_owner']);
        $store->users()->attach($owner, ['role' => 'store_manager', 'status' => 'active']);

        return [$store, $owner];
    }

    private function createCategory(Store $store, string $name): Category
    {
        return Category::create([
            'store_id' => $store->id,
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(3),
        ]);
    }

    public function test_edit_page_does_not_consume_the_filtered_list_session_key(): void
    {
        [$store, $owner] = $this->adminStore();
        $category = $this->createCategory($store, 'Filtered Cat');

        // 1) Visit the filtered list — captures the URI in the session.
        $this->actingAs($owner)->get("/store/{$store->slug}/admin/categories?search=filtered");

        // 2) Open the edit page — must peek (keep) the session key.
        $this->actingAs($owner)->get("/store/{$store->slug}/admin/categories/{$category->id}/edit")
            ->assertOk();

        $this->assertSame(
            "/store/{$store->slug}/admin/categories?search=filtered",
            session()->get('admin_categories_return'),
            'The edit request must not consume the stored filtered-list URI.'
        );

        // 3) Submit the update — the redirect must return to the filtered list.
        $response = $this->actingAs($owner)->put("/store/{$store->slug}/admin/categories/{$category->id}", [
            'name' => 'Filtered Cat Renamed',
            'type' => 'parent',
        ]);

        $response->assertRedirect("/store/{$store->slug}/admin/categories?search=filtered");
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Filtered Cat Renamed',
        ]);
    }

    public function test_update_without_prior_filtered_list_falls_back_to_plain_index(): void
    {
        [$store, $owner] = $this->adminStore();
        $category = $this->createCategory($store, 'Plain Cat');

        $response = $this->actingAs($owner)->put("/store/{$store->slug}/admin/categories/{$category->id}", [
            'name' => 'Plain Cat Renamed',
            'type' => 'parent',
        ]);

        $response->assertRedirect("/store/{$store->slug}/admin/categories");
    }
}
