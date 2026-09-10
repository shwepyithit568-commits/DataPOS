<?php

namespace App\POS\Services;

use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StoreBusinessDateService
{
    /**
     * Resolve the active application business timezone.
     */
    public function timezone(): string
    {
        return config('app.timezone', 'Asia/Yangon');
    }

    /**
     * Get the current calendar business date for the store.
     */
    public function currentDate(Store $store): Carbon
    {
        return Carbon::now($this->timezone())->startOfDay();
    }

    /**
     * Strictly parse an ISO Y-m-d business date string.
     *
     * @throws ValidationException
     */
    public function parseDate(Store $store, ?string $input): Carbon
    {
        if ($input === null || trim($input) === '') {
            return $this->currentDate($store);
        }

        $trimmed = trim($input);

        // Enforce strict YYYY-MM-DD pattern
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
            throw ValidationException::withMessages([
                'business_date' => [__('messages.invalid_date_format')],
            ]);
        }

        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $trimmed, $this->timezone());
            if (!$parsed || $parsed->format('Y-m-d') !== $trimmed) {
                throw new \InvalidArgumentException();
            }
            return $parsed->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'business_date' => [__('messages.invalid_date_format')],
            ]);
        }
    }

    /**
     * Assert that the business date is not in the future relative to the store calendar.
     *
     * @throws ValidationException
     */
    public function assertNotFuture(Store $store, \Carbon\CarbonInterface $date): void
    {
        $today = $this->currentDate($store);

        if ($date->gt($today)) {
            throw ValidationException::withMessages([
                'business_date' => [__('messages.future_date_not_allowed')],
            ]);
        }
    }

    /**
     * Return the half-open query range [start, endExclusive) for the business date.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function queryRange(Store $store, \Carbon\CarbonInterface $date): array
    {
        $start = $date->copy()->startOfDay();
        $endExclusive = $date->copy()->addDay()->startOfDay();

        return [$start, $endExclusive];
    }
}
