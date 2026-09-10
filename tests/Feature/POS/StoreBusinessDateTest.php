<?php

namespace Tests\Feature\POS;

use App\Models\Store;
use App\POS\Services\StoreBusinessDateService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreBusinessDateTest extends TestCase
{
    use RefreshDatabase;

    protected StoreBusinessDateService $dateService;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dateService = app(StoreBusinessDateService::class);
        $this->store = Store::create([
            'name' => 'Date Test Store',
            'slug' => 'date-test-store',
        ]);
    }

    public function test_date_service_resolves_application_timezone(): void
    {
        config(['app.timezone' => 'Asia/Yangon']);
        $this->assertSame('Asia/Yangon', $this->dateService->timezone());

        $current = $this->dateService->currentDate($this->store);
        $this->assertSame('Asia/Yangon', $current->timezoneName);
        $this->assertSame('00:00:00', $current->format('H:i:s'));
    }

    public function test_strict_iso_date_parsing(): void
    {
        $parsed = $this->dateService->parseDate($this->store, '2026-09-10');
        $this->assertSame('2026-09-10', $parsed->format('Y-m-d'));
        $this->assertSame('00:00:00', $parsed->format('H:i:s'));

        // Null or empty falls back to currentDate
        $defaultParsed = $this->dateService->parseDate($this->store, null);
        $this->assertSame($this->dateService->currentDate($this->store)->format('Y-m-d'), $defaultParsed->format('Y-m-d'));
    }

    public function test_invalid_date_format_throws_validation_exception_without_500(): void
    {
        $this->expectException(ValidationException::class);
        $this->dateService->parseDate($this->store, '10/09/2026');
    }

    public function test_malformed_date_string_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);
        $this->dateService->parseDate($this->store, 'not-a-date');
    }

    public function test_future_date_assertion_throws_validation_exception(): void
    {
        $today = $this->dateService->currentDate($this->store);
        $future = $today->copy()->addDays(2);

        $this->expectException(ValidationException::class);
        $this->dateService->assertNotFuture($this->store, $future);
    }

    public function test_today_and_past_dates_pass_future_assertion(): void
    {
        $today = $this->dateService->currentDate($this->store);
        $past = $today->copy()->subDays(5);

        // Neither should throw
        $this->dateService->assertNotFuture($this->store, $today);
        $this->dateService->assertNotFuture($this->store, $past);
        $this->assertTrue(true);
    }

    public function test_half_open_range_captures_midnight_and_excludes_next_day_midnight(): void
    {
        config(['app.timezone' => 'Asia/Yangon']);
        $date = Carbon::parse('2026-09-10', 'Asia/Yangon')->startOfDay();

        [$start, $endExclusive] = $this->dateService->queryRange($this->store, $date);

        $this->assertSame('2026-09-10 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-11 00:00:00', $endExclusive->format('Y-m-d H:i:s'));

        // Exact start boundary (00:00:00.000000)
        $exactStart = Carbon::parse('2026-09-10 00:00:00', 'Asia/Yangon');
        $this->assertTrue($exactStart->gte($start));
        $this->assertTrue($exactStart->lt($endExclusive));

        // Sub-second end of day (23:59:59.999999)
        $subSecondEnd = Carbon::parse('2026-09-10 23:59:59.999999', 'Asia/Yangon');
        $this->assertTrue($subSecondEnd->gte($start));
        $this->assertTrue($subSecondEnd->lt($endExclusive));

        // Previous microsecond (2026-09-09 23:59:59.999999) -> must be excluded
        $prevDayMicro = Carbon::parse('2026-09-09 23:59:59.999999', 'Asia/Yangon');
        $this->assertFalse($prevDayMicro->gte($start));

        // Next midnight (2026-09-11 00:00:00.000000) -> must be excluded
        $nextMidnight = Carbon::parse('2026-09-11 00:00:00', 'Asia/Yangon');
        $this->assertFalse($nextMidnight->lt($endExclusive));
    }

    public function test_date_service_respects_configured_application_timezone(): void
    {
        config(['app.timezone' => 'UTC']);
        $date = Carbon::parse('2026-09-10', 'UTC')->startOfDay();

        [$start, $endExclusive] = $this->dateService->queryRange($this->store, $date);

        $this->assertSame('UTC', $start->timezoneName);
        $this->assertSame('2026-09-10 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-11 00:00:00', $endExclusive->format('Y-m-d H:i:s'));
    }
}
