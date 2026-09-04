<?php

namespace Tests\Feature\Storefront;

use App\Models\Store;
use App\Models\StorefrontPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storeA = Store::create([
            'name'      => 'Store A',
            'slug'      => 'store-a',
            'is_active' => true,
        ]);
        $this->storeB = Store::create([
            'name'      => 'Store B',
            'slug'      => 'store-b',
            'is_active' => true,
        ]);
    }

    public function test_published_custom_page_renders_successfully(): void
    {
        $page = StorefrontPage::create([
            'store_id'     => $this->storeA->id,
            'title_my'     => 'အာမခံမူဝါဒ',
            'title_en'     => 'Warranty Policy',
            'slug'         => 'warranty-policy',
            'summary_en'   => 'Complete warranty terms and coverage.',
            'content_en'   => "## 1. Coverage\n\nWe provide **1 year** full warranty on all phones.",
            'status'       => 'published',
            'is_enabled'   => true,
        ]);

        $response = $this->get('/store/' . $this->storeA->slug . '/page/' . $page->slug);
        $response->assertOk();
        $response->assertSee('အာမခံမူဝါဒ');
        $response->assertSee('1. Coverage');
        $response->assertSee('<strong>1 year</strong>', false);
    }

    public function test_draft_page_returns_404(): void
    {
        $page = StorefrontPage::create([
            'store_id'     => $this->storeA->id,
            'title_my'     => 'မူကြမ်း',
            'title_en'     => 'Draft Page',
            'slug'         => 'draft-page',
            'status'       => 'draft',
            'is_enabled'   => true,
        ]);

        $response = $this->get('/store/' . $this->storeA->slug . '/page/' . $page->slug);
        $response->assertNotFound();
    }

    public function test_markdown_sanitization_strips_malicious_scripts(): void
    {
        $page = StorefrontPage::create([
            'store_id'     => $this->storeA->id,
            'title_my'     => 'လုံခြုံရေး',
            'title_en'     => 'Security Notice',
            'slug'         => 'security-notice',
            'content_en'   => "Hello World <script>alert('XSS')</script> [Evil Link](javascript:alert(1))",
            'status'       => 'published',
            'is_enabled'   => true,
        ]);

        $response = $this->get('/store/' . $this->storeA->slug . '/page/' . $page->slug);
        $response->assertOk();
        $response->assertDontSee('<script>', false);
        $response->assertDontSee('javascript:alert', false);
    }

    public function test_multi_store_isolation(): void
    {
        $pageA = StorefrontPage::create([
            'store_id'     => $this->storeA->id,
            'title_my'     => 'ဆိုင် A အကြောင်း',
            'title_en'     => 'About Store A',
            'slug'         => 'about-us',
            'status'       => 'published',
            'is_enabled'   => true,
        ]);

        // Attempting to access Store A's page under Store B's slug should return 404
        $response = $this->get('/store/' . $this->storeB->slug . '/page/' . $pageA->slug);
        $response->assertNotFound();
    }
}
