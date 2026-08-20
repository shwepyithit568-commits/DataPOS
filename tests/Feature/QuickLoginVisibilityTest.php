<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Quick Login visibility tests.
 *
 * The feature shows passwordless login buttons on the login page when:
 *   1. config('app.show_quick_login') is true
 *   2. APP_ENV is local, testing, or uat
 *   3. There are users in the database to list
 *
 * It is BLOCKED when:
 *   - show_quick_login is false (any env)
 *   - APP_ENV is production or staging (even if flag is true)
 *   - The POST /quick-login route rejects production/staging environments
 */
class QuickLoginVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------------ */
    /*  Hidden when flag is off                                             */
    /* ------------------------------------------------------------------ */

    public function test_quick_login_panel_is_absent_when_flag_is_disabled(): void
    {
        $this->createTestUser();
        config(['app.show_quick_login' => false]);

        $this->assertQuickLoginIsHidden($this->get('/login'));
    }

    /* ------------------------------------------------------------------ */
    /*  Hidden in production/staging even when flag is true                 */
    /* ------------------------------------------------------------------ */

    public function test_quick_login_panel_is_absent_in_production_even_when_flag_is_enabled(): void
    {
        $this->createTestUser();
        config(['app.show_quick_login' => true, 'app.env' => 'production']);

        $this->assertQuickLoginIsHidden($this->get('/login'));
    }

    public function test_quick_login_panel_is_absent_in_staging_even_when_flag_is_enabled(): void
    {
        $this->createTestUser();
        config(['app.show_quick_login' => true, 'app.env' => 'staging']);

        $this->assertQuickLoginIsHidden($this->get('/login'));
    }

    /* ------------------------------------------------------------------ */
    /*  Visible in local/testing/uat when flag is true                     */
    /* ------------------------------------------------------------------ */

    public function test_quick_login_panel_is_visible_in_local_when_flag_is_enabled(): void
    {
        $this->createTestUser();
        config([
            'app.env' => 'local',
            'app.show_quick_login' => true,
        ]);

        $this->assertQuickLoginIsVisible($this->get('/login'));
    }

    public function test_quick_login_panel_is_visible_in_testing_when_flag_is_enabled(): void
    {
        $this->createTestUser();
        config([
            'app.env' => 'testing',
            'app.show_quick_login' => true,
        ]);

        $this->assertQuickLoginIsVisible($this->get('/login'));
    }

    public function test_quick_login_panel_is_visible_in_uat_when_flag_is_enabled(): void
    {
        $this->createTestUser();
        config([
            'app.env' => 'uat',
            'app.show_quick_login' => true,
        ]);

        $this->assertQuickLoginIsVisible($this->get('/login'));
    }

    /* ------------------------------------------------------------------ */
    /*  Configuration key exists                                           */
    /* ------------------------------------------------------------------ */

    public function test_quick_login_configuration_key_exists(): void
    {
        $this->assertNotNull(config('app.show_quick_login'));
    }

    /* ------------------------------------------------------------------ */
    /*  POST /quick-login blocks in production                             */
    /* ------------------------------------------------------------------ */

    public function test_quick_login_route_is_blocked_in_production(): void
    {
        $user = $this->createTestUser();
        config(['app.show_quick_login' => true, 'app.env' => 'production']);

        $response = $this->call('POST', '/quick-login', ['phone' => $user->phone]);
        $this->assertEquals(403, $response->getStatusCode(), 'Quick login must return 403 in production');
    }

    public function test_quick_login_route_is_blocked_when_flag_is_disabled(): void
    {
        $user = $this->createTestUser();
        config([
            'app.env' => 'local',
            'app.show_quick_login' => false,
        ]);

        $this->post('/quick-login', ['phone' => $user->phone])
            ->assertStatus(403);
    }

    public function test_quick_login_route_logs_in_user_in_local(): void
    {
        $user = $this->createTestUser();
        config([
            'app.env' => 'local',
            'app.show_quick_login' => true,
        ]);

        $this->post('/quick-login', ['phone' => $user->phone])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function createTestUser(): User
    {
        return User::factory()->create([
            'phone' => '09100000001',
        ]);
    }

    private function assertQuickLoginIsHidden(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertDontSee('quick-login', false);
        $response->assertDontSee('Quick Login', false);
    }

    private function assertQuickLoginIsVisible(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertSee('quick-login', false);
        $response->assertSee('Quick Login', false);
    }
}
