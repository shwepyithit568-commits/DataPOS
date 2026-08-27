<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBlogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Blog Test Store',
            'slug' => 'blog-test-store',
            'status' => 'active',
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);
    }

    public function test_manager_can_access_blog_index_with_stats(): void
    {
        Post::create([
            'store_id' => $this->store->id,
            'title' => 'Top 10 Phone Accessories in 2026',
            'slug' => 'top-10-phone-accessories-2026',
            'category' => 'Buying Guide',
            'content' => '<p>Here are the best accessories for your phone.</p>',
            'excerpt' => 'A guide to top phone accessories.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Post::create([
            'store_id' => $this->store->id,
            'title' => 'Upcoming Discounts Draft',
            'slug' => 'upcoming-discounts-draft',
            'category' => 'News',
            'content' => '<p>Draft details...</p>',
            'excerpt' => 'Upcoming discounts draft excerpt.',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.blog.index', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $response->assertSee('Top 10 Phone Accessories in 2026');
        $response->assertSee('Upcoming Discounts Draft');
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total'] === 2 && $stats['published'] === 1 && $stats['draft'] === 1 && $stats['categories_count'] === 2;
        });
    }

    public function test_manager_can_create_blog_post(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.blog.store', ['store_slug' => $this->store->slug]), [
                'title' => 'How to Choose the Right Tempered Glass',
                'category' => 'How-to Guide',
                'content' => '<p>Tips on tempered glass hardness and clarity.</p>',
                'excerpt' => 'A short guide on glass screen protectors.',
                'is_published' => '1',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'store_id' => $this->store->id,
            'title' => 'How to Choose the Right Tempered Glass',
            'is_published' => 1,
        ]);
    }

    public function test_manager_can_update_blog_post(): void
    {
        $post = Post::create([
            'store_id' => $this->store->id,
            'title' => 'Initial Title',
            'slug' => 'initial-title',
            'category' => 'Tips',
            'content' => '<p>Original content</p>',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->put(route('store.admin.blog.update', [
                'store_slug' => $this->store->slug,
                'post' => $post->id,
            ]), [
                'title' => 'Updated Title Version 2',
                'slug' => 'updated-title-v2',
                'category' => 'Tips & Tricks',
                'content' => '<p>Updated content body</p>',
                'is_published' => '1',
            ]);

        $response->assertRedirect();
        $this->assertEquals('Updated Title Version 2', $post->fresh()->title);
        $this->assertTrue((bool)$post->fresh()->is_published);
    }

    public function test_manager_can_delete_blog_post(): void
    {
        $post = Post::create([
            'store_id' => $this->store->id,
            'title' => 'Post to be deleted',
            'slug' => 'post-to-delete',
            'content' => '<p>Content</p>',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.blog.destroy', [
                'store_slug' => $this->store->slug,
                'post' => $post->id,
            ]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_filter_blog_by_status_and_category(): void
    {
        Post::create([
            'store_id' => $this->store->id,
            'title' => 'Published Tech News',
            'slug' => 'published-tech-news',
            'category' => 'Technology',
            'content' => '<p>Tech content</p>',
            'is_published' => true,
        ]);

        Post::create([
            'store_id' => $this->store->id,
            'title' => 'Draft Tutorial',
            'slug' => 'draft-tutorial',
            'category' => 'Tutorials',
            'content' => '<p>Tutorial content</p>',
            'is_published' => false,
        ]);

        $responsePublished = $this->actingAs($this->manager)
            ->get(route('store.admin.blog.index', [
                'store_slug' => $this->store->slug,
                'status' => 'published',
            ]));
        $responsePublished->assertOk();
        $responsePublished->assertSee('Published Tech News');
        $responsePublished->assertDontSee('Draft Tutorial');

        $responseCategory = $this->actingAs($this->manager)
            ->get(route('store.admin.blog.index', [
                'store_slug' => $this->store->slug,
                'category' => 'Tutorials',
            ]));
        $responseCategory->assertOk();
        $responseCategory->assertSee('Draft Tutorial');
        $responseCategory->assertDontSee('Published Tech News');
    }

    public function test_cross_store_blog_isolation(): void
    {
        $otherStore = Store::create([
            'name' => 'Other Blog Store',
            'slug' => 'other-blog-store',
            'status' => 'active',
        ]);

        $otherPost = Post::create([
            'store_id' => $otherStore->id,
            'title' => 'Confidential Article Other Store',
            'slug' => 'confidential-article',
            'content' => '<p>Confidential text</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.blog.edit', [
                'store_slug' => $this->store->slug,
                'post' => $otherPost->id,
            ]));

        $response->assertStatus(403);
    }
}
