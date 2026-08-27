<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Review Test Store',
            'slug' => 'review-test-store',
            'status' => 'active',
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Review Target Phone',
            'slug' => 'review-target-phone',
            'sku' => 'SKU-REV-001',
            'retail_price' => 250000,
            'wholesale_price' => 200000,
            'status' => 'active',
        ]);
    }

    public function test_manager_can_access_reviews_index_with_stats(): void
    {
        Review::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'reviewer_name' => 'Ko Kyaw',
            'reviewer_phone' => '0912345678',
            'rating' => 5,
            'comment' => 'Very good quality product!',
            'is_approved' => false,
        ]);

        Review::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'reviewer_name' => 'Ma Hla',
            'reviewer_phone' => '0987654321',
            'rating' => 4,
            'comment' => 'Fast delivery.',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.reviews.index', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $response->assertSee('Ko Kyaw');
        $response->assertSee('Ma Hla');
        $response->assertSee('Very good quality product!');
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 2 && $stats['pending'] === 1 && $stats['approved'] === 1 && $stats['five_star'] === 1;
        });
    }

    public function test_manager_can_toggle_approve_review(): void
    {
        $review = Review::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'reviewer_name' => 'Ko Aung',
            'rating' => 5,
            'comment' => 'Awesome phone',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->patch(route('store.admin.reviews.approve', [
                'store_slug' => $this->store->slug,
                'review' => $review->id,
            ]));

        $response->assertRedirect();
        $this->assertTrue($review->fresh()->is_approved);

        // Toggle back to hide
        $response2 = $this->actingAs($this->manager)
            ->patch(route('store.admin.reviews.approve', [
                'store_slug' => $this->store->slug,
                'review' => $review->id,
            ]));

        $response2->assertRedirect();
        $this->assertFalse($review->fresh()->is_approved);
    }

    public function test_manager_can_delete_review(): void
    {
        $review = Review::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'reviewer_name' => 'Spam Bot',
            'rating' => 1,
            'comment' => 'Spam comment text',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.reviews.destroy', [
                'store_slug' => $this->store->slug,
                'review' => $review->id,
            ]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_filter_reviews_by_status_and_rating(): void
    {
        Review::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'reviewer_name' => 'Pending FiveStar User',
            'rating' => 5,
            'comment' => 'Pending comment',
            'is_approved' => false,
        ]);

        Review::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'reviewer_name' => 'Approved ThreeStar User',
            'rating' => 3,
            'comment' => 'Approved comment',
            'is_approved' => true,
        ]);

        $responsePending = $this->actingAs($this->manager)
            ->get(route('store.admin.reviews.index', [
                'store_slug' => $this->store->slug,
                'status' => 'pending',
            ]));
        $responsePending->assertOk();
        $responsePending->assertSee('Pending FiveStar User');
        $responsePending->assertDontSee('Approved ThreeStar User');

        $responseRating = $this->actingAs($this->manager)
            ->get(route('store.admin.reviews.index', [
                'store_slug' => $this->store->slug,
                'rating' => 3,
            ]));
        $responseRating->assertOk();
        $responseRating->assertSee('Approved ThreeStar User');
        $responseRating->assertDontSee('Pending FiveStar User');
    }

    public function test_cross_store_review_isolation(): void
    {
        $otherStore = Store::create([
            'name' => 'Other Store',
            'slug' => 'other-store',
            'status' => 'active',
        ]);

        $otherProduct = Product::create([
            'store_id' => $otherStore->id,
            'name' => 'Other Product',
            'slug' => 'other-product',
            'sku' => 'SKU-OTHER-001',
            'retail_price' => 100000,
            'wholesale_price' => 80000,
            'status' => 'active',
        ]);

        $otherReview = Review::create([
            'store_id' => $otherStore->id,
            'product_id' => $otherProduct->id,
            'reviewer_name' => 'Other Store Customer',
            'rating' => 5,
            'comment' => 'Secret customer comment',
            'is_approved' => true,
        ]);

        // Attempt to toggle other store's review
        $response = $this->actingAs($this->manager)
            ->patch(route('store.admin.reviews.approve', [
                'store_slug' => $this->store->slug,
                'review' => $otherReview->id,
            ]));

        $response->assertStatus(403);
    }
}
