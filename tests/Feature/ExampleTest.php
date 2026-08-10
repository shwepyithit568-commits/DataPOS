<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
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
