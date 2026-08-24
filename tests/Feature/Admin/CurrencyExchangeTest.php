<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\CurrencyExchangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyExchangeTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'test-fx-store',
            'name' => 'Test FX Store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff']);
    }

    public function test_exchange_rates_index_renders_with_default_currencies(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.exchange_rates.index', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertSee('MMK');
        $response->assertSee('USD');
        $response->assertSee('THB');
        $response->assertSee('CNY');
        $response->assertSee(__('messages.exchange_title'));
    }

    public function test_add_new_foreign_currency(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.exchange_rates.store', ['store_slug' => $this->store->slug]), [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 4950.50,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('store.admin.exchange_rates.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('currencies', [
            'store_id' => $this->store->id,
            'code' => 'EUR',
            'symbol' => '€',
            'exchange_rate' => 4950.50,
        ]);
    }

    public function test_bulk_update_exchange_rates(): void
    {
        $service = app(CurrencyExchangeService::class);
        $service->ensureDefaultCurrencies($this->store);

        $usd = Currency::where('store_id', $this->store->id)->where('code', 'USD')->first();
        $thb = Currency::where('store_id', $this->store->id)->where('code', 'THB')->first();

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.exchange_rates.bulk_update', ['store_slug' => $this->store->slug]), [
                'rates' => [
                    $usd->id => 4650.0,
                    $thb->id => 140.0,
                ],
            ]);

        $response->assertRedirect(route('store.admin.exchange_rates.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertEquals(4650.0, $usd->fresh()->exchange_rate);
        $this->assertEquals(140.0, $thb->fresh()->exchange_rate);
    }

    public function test_update_single_currency_rate(): void
    {
        $service = app(CurrencyExchangeService::class);
        $service->ensureDefaultCurrencies($this->store);

        $usd = Currency::where('store_id', $this->store->id)->where('code', 'USD')->first();

        $response = $this->actingAs($this->manager)
            ->put(route('store.admin.exchange_rates.update', [
                'store_slug' => $this->store->slug,
                'currency' => $usd->id,
            ]), [
                'name' => 'United States Dollar (Official)',
                'symbol' => '$',
                'exchange_rate' => 4700.0,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('store.admin.exchange_rates.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertEquals(4700.0, $usd->fresh()->exchange_rate);
        $this->assertEquals('United States Dollar (Official)', $usd->fresh()->name);
    }

    public function test_cannot_delete_base_currency(): void
    {
        $service = app(CurrencyExchangeService::class);
        $service->ensureDefaultCurrencies($this->store);

        $base = Currency::where('store_id', $this->store->id)->where('is_base', true)->first();

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.exchange_rates.destroy', [
                'store_slug' => $this->store->slug,
                'currency' => $base->id,
            ]));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('currencies', ['id' => $base->id]);
    }

    public function test_delete_foreign_currency(): void
    {
        $service = app(CurrencyExchangeService::class);
        $service->ensureDefaultCurrencies($this->store);

        $sgd = Currency::where('store_id', $this->store->id)->where('code', 'SGD')->first();

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.exchange_rates.destroy', [
                'store_slug' => $this->store->slug,
                'currency' => $sgd->id,
            ]));

        $response->assertRedirect(route('store.admin.exchange_rates.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('currencies', ['id' => $sgd->id]);
    }

    public function test_convert_currency_calculation(): void
    {
        $service = app(CurrencyExchangeService::class);
        $service->ensureDefaultCurrencies($this->store);

        $usd = Currency::where('store_id', $this->store->id)->where('code', 'USD')->first();
        $usd->update(['exchange_rate' => 4500.0]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('store.admin.exchange_rates.convert', [
                'store_slug' => $this->store->slug,
                'amount' => 10,
                'from' => 'USD',
                'to' => 'MMK',
            ]));

        $response->assertStatus(200);
        $response->assertJson([
            'amount' => 10,
            'from' => 'USD',
            'to' => 'MMK',
            'result' => 45000.0,
        ]);
    }
}
