<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify the application returns a successful response with all
     * required security headers set by the SecurityHeaders middleware.
     *
     * Note: Strict-Transport-Security is only set on HTTPS requests,
     * so it is not asserted in the test environment.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('Content-Security-Policy');

        // The storefront home is a public page: browser-cacheable with a short
        // max-age + ETag revalidation (CachePublicPage middleware). The CSP
        // nonce travels WITH the cached response, so it stays self-consistent.
        $response->assertHeader('ETag');
        $this->assertStringContainsString('max-age=60', (string) $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        // Conditional GET: revalidating with the ETag must yield a 304 (no
        // body) rather than a full re-render, while a mismatched tag still
        // returns the full 200.
        $this->get('/', ['If-None-Match' => $response->headers->get('ETag')])
            ->assertStatus(304)
            ->assertHeader('ETag', $response->headers->get('ETag'));
        $this->get('/', ['If-None-Match' => '"stale-etag"'])
            ->assertStatus(200);

        // script-src must be nonce-based: it carries a per-request nonce and
        // no longer allows unsafe-inline (inline event handlers were replaced
        // by the delegated listeners in resources/js/csp-helpers.js).
        $csp = $response->headers->get('Content-Security-Policy');
        preg_match('/script-src ([^;]+)/', $csp, $matches);
        $this->assertNotEmpty($matches[1], 'CSP must define script-src');
        $this->assertStringContainsString("'nonce-", $matches[1]);
        $this->assertStringNotContainsString("'unsafe-inline'", $matches[1]);
    }

    /**
     * Private / dynamic pages (auth, admin, POS, …) must never be cached:
     * they carry session state + a CSRF token where a stale copy breaks forms.
     */
    public function test_private_pages_are_no_store(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    /**
     * Verify the application health-check endpoint returns a successful response.
     */
    public function test_the_health_check_endpoint_is_accessible(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    /**
     * Verify the robots.txt endpoint returns valid directives with the correct sitemap URL.
     */
    public function test_robots_txt_is_dynamic_and_contains_correct_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type');
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        // Cache-Control: no-store is scoped to text/html — non-HTML responses
        // (robots.txt, static assets) must keep Laravel's default no-cache
        // instead of our stricter no-store.
        $this->assertStringNotContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $response->assertSee('User-agent: *');
        $response->assertSee('Disallow: /admin');
        $response->assertSee('Disallow: /login');
        $response->assertSee('Disallow: /register');
        $response->assertSee('Disallow: /account');
        $response->assertSee('Sitemap: ' . url('/sitemap.xml'));
        $response->assertDontSee('yourdomain.com');
        $response->assertDontSee('__SITEMAP_URL__');
    }
}
