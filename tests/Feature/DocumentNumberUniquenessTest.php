<?php

namespace Tests\Feature;

use App\Models\Store as StoreModel;
use App\POS\Models\BuyBack;
use App\POS\Models\DocumentSequence;
use App\POS\Models\Expense;
use App\POS\Models\ServiceJob;
use App\POS\Models\StockTransfer;
use App\POS\Services\DocumentSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression: document numbers were generated with a read-then-write
 * ("count today's rows, add one"), so two documents created in the same instant
 * could be handed the same number. They now come from a row-locked sequence.
 */
class DocumentNumberUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private StoreModel $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = StoreModel::create([
            'name' => 'Number Store',
            'slug' => 'number-store',
            'is_active' => true,
        ]);
    }

    private function today(string $prefix): string
    {
        return $prefix . '-' . now()->format('Ymd') . '-';
    }

    public function test_each_series_uses_its_expected_prefix_and_padding(): void
    {
        $this->assertSame($this->today('EXP') . '0001', Expense::generateExpenseNumber($this->store->id));
        $this->assertSame($this->today('BB') . '0001', BuyBack::generateNumber($this->store->id));
        $this->assertSame($this->today('TRF') . '0001', StockTransfer::generateNumber($this->store->id));
        $this->assertSame($this->today('SVC') . '0001', ServiceJob::generateNumber($this->store->id));
    }

    public function test_consecutive_calls_increment(): void
    {
        $first = Expense::generateExpenseNumber($this->store->id);
        $second = Expense::generateExpenseNumber($this->store->id);
        $third = Expense::generateExpenseNumber($this->store->id);

        $this->assertSame('0001', substr($first, -4));
        $this->assertSame('0002', substr($second, -4));
        $this->assertSame('0003', substr($third, -4));
    }

    public function test_series_are_independent_per_store(): void
    {
        $other = StoreModel::create(['name' => 'Other', 'slug' => 'other-number-store', 'is_active' => true]);

        Expense::generateExpenseNumber($this->store->id);
        Expense::generateExpenseNumber($this->store->id);

        $foreign = Expense::generateExpenseNumber($other->id);

        $this->assertSame($this->today('EXP') . '0001', $foreign);
    }

    /**
     * A sequence that starts fresh (new day, or after this change is deployed)
     * must resume after rows that already exist rather than re-issuing 0001.
     */
    public function test_a_new_sequence_resumes_after_existing_rows(): void
    {
        Expense::create([
            'store_id'       => $this->store->id,
            'expense_number' => $this->today('EXP') . '0007',
            'title'          => 'Earlier expense',
            'amount'         => '10.00',
            'expense_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        // No sequence row exists yet for today, so it must seed from the data.
        $this->assertSame(0, DocumentSequence::where('document_type', 'expense')->count());

        $next = Expense::generateExpenseNumber($this->store->id);

        $this->assertSame($this->today('EXP') . '0008', $next);
    }

    public function test_deleting_a_document_does_not_reuse_its_number(): void
    {
        $first = Expense::generateExpenseNumber($this->store->id);

        $this->assertSame('0001', substr($first, -4));

        // The sequence row is the authority. With the row count seeding the
        // generator, deleting the document would have handed out 0001 again.
        Expense::create([
            'store_id'       => $this->store->id,
            'expense_number' => $first,
            'title'          => 'Will be deleted',
            'amount'         => '10.00',
            'expense_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        Expense::where('store_id', $this->store->id)->delete();

        $next = Expense::generateExpenseNumber($this->store->id);

        $this->assertSame('0002', substr($next, -4));
    }

    public function test_the_sequence_table_is_the_source_of_truth(): void
    {
        Expense::generateExpenseNumber($this->store->id);

        $sequence = DocumentSequence::where('document_type', 'expense')
            ->where('store_id', $this->store->id)
            ->firstOrFail();

        $this->assertSame(1, (int) $sequence->last_number);
        $this->assertSame(now()->format('Ymd'), $sequence->period_key);
    }

    public function test_expenses_reject_a_duplicate_number(): void
    {
        $attributes = [
            'store_id'       => $this->store->id,
            'expense_number' => $this->today('EXP') . '0001',
            'title'          => 'First',
            'amount'         => '10.00',
            'expense_date'   => now()->toDateString(),
            'payment_method' => 'cash',
        ];

        Expense::create($attributes);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Expense::create([...$attributes, 'title' => 'Duplicate']);
    }

    public function test_an_unknown_document_type_still_produces_a_number(): void
    {
        $number = app(DocumentSequenceService::class)->nextNumber($this->store, 'custom_thing');

        $this->assertSame('CUSTOM_THING-' . now()->format('Ymd') . '-0001', $number);
    }

    public function test_a_series_with_no_existing_rows_starts_at_one(): void
    {
        $number = app(DocumentSequenceService::class)->nextNumber($this->store, 'stock_count');

        $this->assertSame($this->today('SC') . '0001', $number);
        $this->assertSame(0, DB::table('stock_counts')->count());
    }
}
