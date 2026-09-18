<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\InventoryAdjustment;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A stock correction must name the people behind it.
 *
 * When the shelf and the ledger disagree, someone types the real count into
 * /pos/adjustments — and months later the shop has to answer "who wrote this
 * off, and who signed it off?" The record already carried submitted_by /
 * reviewed_by / reviewed_at; this pins it AND the screen that shows it, because
 * an audit trail nobody can read is not a control.
 */
class InventoryAdjustmentAuditTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $owner;

    private User $keeper;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Audit Shop', 'slug' => 'audit-shop', 'is_active' => true]);

        $this->owner = $this->staff('Owner', '09730000101', 'store_owner');
        $this->keeper = $this->staff('Stock Keeper', '09730000102', 'staff');

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Audit Bracket',
            'slug' => 'audit-bracket-' . Str::random(4),
            'sku' => 'AUD-' . Str::random(4),
            'retail_price' => 4000,
            'wholesale_price' => 3000,
        ]);

        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '5',
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    private function staff(string $name, string $phone, string $role): User
    {
        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($this->store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function submit(User $actor, string $quantity, string $reason = 'စတော့စစ်ဆေးတွေ့ရှိမှု ကွာဟချက်')
    {
        return $this->actingAs($actor)->post("/store/{$this->store->slug}/pos/adjustments", [
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => $quantity,
                'reason' => $reason,
            ]],
            'notes' => 'Counted on the shelf',
        ]);
    }

    public function test_submitting_records_who_asked_and_supersets_nothing(): void
    {
        $this->submit($this->keeper, '-2');

        $adjustment = InventoryAdjustment::where('store_id', $this->store->id)->sole();

        $this->assertSame($this->keeper->id, $adjustment->submitted_by);
        $this->assertNull($adjustment->reviewed_by, 'nothing is signed off until a reviewer acts');
        $this->assertSame('pending', $adjustment->status);

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $this->store->id,
            'action' => 'inventory_adjustment_submitted',
            'entity_id' => $adjustment->id,
            'actor_id' => $this->keeper->id,
        ]);

        // The ledger only moves on approval — a submitted correction is a request.
        $this->assertSame('5.000', app(InventoryService::class)->totalOnHand($this->store->id, $this->product->id));
    }

    public function test_approving_records_the_reviewer_the_time_and_the_reason(): void
    {
        $this->submit($this->keeper, '-2');
        $adjustment = InventoryAdjustment::where('store_id', $this->store->id)->sole();

        $this->actingAs($this->owner)
            ->post("/store/{$this->store->slug}/pos/adjustments/{$adjustment->id}/approve", ['review_notes' => 'နှစ်လုံး ကွဲသွားသည်'])
            ->assertRedirect();

        $fresh = $adjustment->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($this->owner->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertSame('နှစ်လုံး ကွဲသွားသည်', $fresh->review_notes);

        // Now the stock moved, and the movement names the person who did it.
        $this->assertSame('3.000', app(InventoryService::class)->totalOnHand($this->store->id, $this->product->id));
        $this->assertDatabaseHas('inventory_movements', [
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'movement_type' => 'adjustment_out',
            'quantity_delta' => '-2.000',
            'posted_by' => $this->owner->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $this->store->id,
            'action' => 'inventory_adjustment_approved',
            'entity_id' => $adjustment->id,
            'actor_id' => $this->owner->id,
        ]);
    }

    public function test_the_screen_shows_who_submitted_who_approved_and_every_line(): void
    {
        $this->submit($this->keeper, '-2', 'လက်ကျန် ကွာဟချက် — စတော့ပြန်ရေတွက်');
        $adjustment = InventoryAdjustment::where('store_id', $this->store->id)->sole();
        $this->actingAs($this->owner)->post("/store/{$this->store->slug}/pos/adjustments/{$adjustment->id}/approve");
        $adjustment->refresh();

        $response = $this->actingAs($this->owner)->get("/store/{$this->store->slug}/pos/adjustments");

        $response->assertOk();
        $response->assertSee($adjustment->adjustment_number, false);
        // Submitted by + approved by, with their timestamps.
        $response->assertSee($this->keeper->name, false);
        $response->assertSee($this->owner->name, false);
        $response->assertSee($adjustment->reviewed_at->format('d/m/Y H:i'), false);
        // The line itself: product, quantity and the reason that was typed.
        $response->assertSee($this->product->name, false);
        $response->assertSee('လက်ကျန် ကွာဟချက် — စတော့ပြန်ရေတွက်', false);
        $response->assertSee('Counted on the shelf', false);
    }

    public function test_the_export_carries_both_names(): void
    {
        $this->submit($this->keeper, '-1');
        $adjustment = InventoryAdjustment::where('store_id', $this->store->id)->sole();
        $this->actingAs($this->owner)->post("/store/{$this->store->slug}/pos/adjustments/{$adjustment->id}/approve");

        $response = $this->actingAs($this->owner)->get("/store/{$this->store->slug}/pos/adjustments/export");
        $response->assertOk();

        // It is a real xlsx: read it back and look for both names, so the
        // spreadsheet a shop emails to its accountant carries the same audit.
        $path = tempnam(sys_get_temp_dir(), 'adjexport') . '.xlsx';
        file_put_contents($path, $response->streamedContent() ?: (string) $response->getContent());

        $cells = [];
        try {
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
            foreach ($sheet->getRowIterator(1) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = (string) $cell->getValue();
                }
            }
        } finally {
            @unlink($path);
        }

        $this->assertContains($this->keeper->name, $cells, 'submitter in the export');
        $this->assertContains($this->owner->name, $cells, 'reviewer in the export');
    }
}
