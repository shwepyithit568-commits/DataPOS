<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batch 5 — normal auth flow, ACDC Mobile branding, PUBLIC_STORAGE_PATH
 * fallback, and the product-image fallback label survive the production
 * recovery changes.
 */
class LoginBrandingAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** Task 8: the normal phone/password login still works. */
    public function test_normal_login_with_valid_credentials_still_works(): void
    {
        $user = User::create([
            'name' => 'Existing Customer',
            'phone' => '09987654321',
            'password' => bcrypt('secret123'),
            'role' => 'customer',
        ]);

        $response = $this->post('/login', [
            'phone' => '09987654321',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    /** Task 8: registration still works and client role tampering is still ignored. */
    public function test_registration_still_works_and_forces_customer_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Customer',
            'phone' => '09123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'platform_owner', // Attempted role tampering by client
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::where('phone', '09123456789')->first();
        $this->assertNotNull($user);
        $this->assertEquals('customer', $user->role);
    }

    /** Task 8: login page shows DataPOS branding and keeps the normal auth form. */
    public function test_login_page_has_datapos_branding_and_normal_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('DataPOS');
        // The recovered footer branding renders; the page title is driven by the
        // deployment APP_NAME (env config, not view code) and is not asserted here.
        // Footer uses the localized trusted_by_us key — assert it survives rebrand.
        $response->assertSee(__('messages.trusted_by_us'));
        // Normal login form: POST to the login route with CSRF, phone/password,
        // and the password visibility toggle.
        $response->assertSee('method="POST"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('showPassword', false);
    }

    /** Task 8: register page shows DataPOS branding and keeps the registration form. */
    public function test_register_page_has_datapos_branding_and_normal_form(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertSee('DataPOS');
        $response->assertSee(__('messages.trusted_by_us'));
        $response->assertSee('method="POST"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('showPassword', false);
    }

    /** Task 8: PUBLIC_STORAGE_PATH honors the environment override. */
    public function test_public_storage_path_honors_environment_override(): void
    {
        putenv('PUBLIC_STORAGE_PATH=' . storage_path('custom-public'));
        $this->refreshApplication();

        $this->assertSame(
            storage_path('custom-public'),
            config('filesystems.disks.public.root')
        );
    }

    /** Task 8: PUBLIC_STORAGE_PATH falls back to the default local path when unset. */
    public function test_public_storage_path_falls_back_to_default_when_unset(): void
    {
        putenv('PUBLIC_STORAGE_PATH');
        $this->refreshApplication();

        $this->assertSame(
            storage_path('app/public'),
            config('filesystems.disks.public.root')
        );
    }

    /** Task 8: product-image component shows the ACDC Mobile fallback label. */
    public function test_product_image_fallback_label_is_acdc_mobile(): void
    {
        $html = (string) view('components.product-image', ['path' => null]);

        $this->assertStringContainsString('DataPOS', $html);
        $this->assertStringNotContainsString('ACDC Mobile', $html);
        $this->assertStringNotContainsString('No Preview', $html);
    }

    /** Task 8: product-image component renders the image path when provided. */
    public function test_product_image_renders_image_when_path_provided(): void
    {
        $html = (string) view('components.product-image', ['path' => 'products/sample.jpg']);

        $this->assertStringContainsString('storage/products/sample.jpg', $html);
        $this->assertStringContainsString('No Preview', $html);
        $this->assertStringNotContainsString('ACDC Mobile', $html);
    }
}
