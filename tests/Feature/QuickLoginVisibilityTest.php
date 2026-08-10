<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Batch 5 — Quick Login was removed entirely from the login screen.
 * No environment (local, staging, production) may render it, even if a
 * legacy show_quick_login flag were present, and the configuration key
 * itself no longer exists.
 */
class QuickLoginVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_login_panel_is_absent_in_local_even_when_flag_is_enabled(): void
    {
        config([
            'app.env' => 'local',
            'app.show_quick_login' => true,
        ]);

        $this->assertQuickLoginIsHidden($this->get('/login'));
    }

    public function test_quick_login_panel_is_absent_in_staging_even_when_flag_is_enabled(): void
    {
        config([
            'app.env' => 'staging',
            'app.show_quick_login' => true,
        ]);

        $this->assertQuickLoginIsHidden($this->get('/login'));
    }

    public function test_quick_login_panel_is_absent_in_production_even_when_flag_is_enabled(): void
    {
        config([
            'app.env' => 'production',
            'app.show_quick_login' => true,
        ]);

        $this->assertQuickLoginIsHidden($this->get('/login'));
    }

    public function test_quick_login_panel_is_absent_when_flag_is_disabled(): void
    {
        config([
            'app.env' => 'local',
            'app.show_quick_login' => false,
        ]);

        $this->assertQuickLoginIsHidden($this->get('/login'));
    }

    public function test_quick_login_configuration_key_no_longer_exists(): void
    {
        $this->assertNull(config('app.show_quick_login'));
    }

    private function assertQuickLoginIsHidden(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertDontSee('Quick Login');
        $response->assertDontSee('PWD: password', false);
        $response->assertDontSee('fillQuick', false);
        $response->assertDontSee('show_quick_login', false);

        foreach (['09100000001', '09100000002', '09100000004', '09100000006'] as $phone) {
            $response->assertDontSee($phone, false);
        }
    }
}
