<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\BlogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontBlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_blog_index_renders_articles(): void
    {
        $store = Store::create([
            'name'          => 'DataPOS Mobile',
            'slug'          => 'datapos-mobile',
            'business_type' => 'mobile_sale_service',
            'is_active'     => true,
        ]);

        BlogSeeder::seedForStore($store);

        $this->assertEquals(9, Post::where('store_id', $store->id)->count());

        $response = $this->get('/blog?store_slug=' . $store->slug);
        $response->assertStatus(200);
        $response->assertSee('ဖုန်းဝယ်မယ်ဆို မဆုံးဖြတ်ခင် စစ်သင့်တဲ့ အချက် ၇ ချက်');
        $response->assertSee('Mobile Guide');
        $response->assertSee('cctv-buying-guide.webp');
    }

    public function test_storefront_blog_show_renders_article_content(): void
    {
        $store = Store::create([
            'name'          => 'DataPOS Mobile',
            'slug'          => 'datapos-mobile',
            'business_type' => 'mobile_sale_service',
            'is_active'     => true,
        ]);

        BlogSeeder::seedForStore($store);

        $response = $this->get('/blog/phone-buying-checklist?store_slug=' . $store->slug);
        $response->assertStatus(200);
        $response->assertSee('ဖုန်းဝယ်မယ်ဆို မဆုံးဖြတ်ခင် စစ်သင့်တဲ့ အချက် ၇ ချက်');
        $response->assertSee('ဖုန်းတစ်လုံးက နေ့တိုင်းသုံးရတဲ့ပစ္စည်းပါ။');
        $response->assertSee('blog/phone-buying-checklist.webp');
    }

    public function test_admin_blog_index_renders_for_manager(): void
    {
        $store = Store::create([
            'name'          => 'DataPOS Mobile',
            'slug'          => 'datapos-mobile',
            'business_type' => 'mobile_sale_service',
            'is_active'     => true,
        ]);

        $manager = User::factory()->create();
        $store->users()->attach($manager->id, ['role' => 'store_manager']);

        BlogSeeder::seedForStore($store);

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/blog");
        $response->assertStatus(200);
        $response->assertSee('ဖုန်းဝယ်မယ်ဆို မဆုံးဖြတ်ခင် စစ်သင့်တဲ့ အချက် ၇ ချက်');
        $response->assertSee('Mobile Guide');
    }
}
