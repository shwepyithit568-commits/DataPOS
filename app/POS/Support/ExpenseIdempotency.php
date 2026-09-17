<?php

namespace App\POS\Support;

use App\POS\Models\Expense;
use Illuminate\Database\QueryException;

/**
 * Duplicate-submission protection for expense creation.
 *
 * A browser cannot know whether a POST that timed out actually reached the
 * server, so it resends the SAME logical expense with the SAME
 * `client_transaction_id`. The server answers that resend from the row it
 * already wrote — unless the payload differs, which means the operator changed
 * something after a submission whose outcome they could not see. That case is
 * NOT a retry and must never be answered as if the new values had been saved:
 * it is reported as a conflict so the stored row is opened for edit instead.
 *
 * Fingerprints are computed from the same canonical field list on both the write
 * path and the comparison path, so a row created before the fingerprint column
 * existed (NULL) is still compared correctly instead of being treated as a
 * blanket replay.
 */
class ExpenseIdempotency
{
    public const RESULT_REPLAY = 'replay';
    public const RESULT_CONFLICT = 'conflict';

    /**
     * Canonical fingerprint of an expense submission.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fingerprint(array $payload): string
    {
        $canonical = [
            'store_id' => (string) ($payload['store_id'] ?? ''),
            'expense_date' => (string) ($payload['expense_date'] ?? ''),
            'title' => trim((string) ($payload['title'] ?? '')),
            'amount' => bcadd((string) ($payload['amount'] ?? '0'), '0', 2),
            'payment_method' => (string) ($payload['payment_method'] ?? ''),
            'payment_source' => (string) ($payload['payment_source'] ?? ''),
            'cashier_shift_id' => (string) ($payload['cashier_shift_id'] ?? ''),
            'recorded_by' => (string) ($payload['recorded_by'] ?? ''),
        ];

        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Fingerprint of an already-stored expense, used for rows written before the
     * fingerprint column existed.
     */
    public static function fingerprintOf(Expense $expense): string
    {
        return self::fingerprint([
            'store_id' => $expense->store_id,
            'expense_date' => $expense->expense_date?->format('Y-m-d') ?? (string) $expense->expense_date,
            'title' => $expense->title,
            'amount' => $expense->amount,
            'payment_method' => $expense->payment_method,
            'payment_source' => $expense->payment_source,
            'cashier_shift_id' => $expense->cashier_shift_id,
            'recorded_by' => $expense->recorded_by,
        ]);
    }

    /**
     * Does an existing row (found by client_transaction_id) represent the same
     * submission as the incoming payload?
     */
    public static function matches(Expense $existing, array $payload): bool
    {
        $incoming = self::fingerprint($payload);

        $stored = (string) ($existing->request_fingerprint ?? '');
        if ($stored !== '') {
            return hash_equals($stored, $incoming);
        }

        return hash_equals(self::fingerprintOf($existing), $incoming);
    }

    public static function resultFor(Expense $existing, array $payload): string
    {
        return self::matches($existing, $payload) ? self::RESULT_REPLAY : self::RESULT_CONFLICT;
    }

    /**
     * Is this exception the (store_id, client_transaction_id) unique index doing
     * its job under a concurrent double-submit?
     *
     * MySQL reports 1062; SQLite reports 19 (SQLITE_CONSTRAINT_UNIQUE); Postgres
     * reports 23505. All three mean "somebody won the race" — the correct answer
     * is to read the winner back and replay it, never to surface the raw driver
     * error to the operator.
     */
    public static function isDuplicateKey(QueryException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        if (in_array($driverCode, [1062, 19, 23505], true)) {
            return true;
        }

        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());

        return in_array($sqlState, ['23000', '23505'], true)
            && stripos($e->getMessage(), 'client_transaction') !== false;
    }

    /**
     * Normalise an incoming key: trimmed, or null when the caller sent nothing.
     */
    public static function normalizeKey(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $key = trim($raw);

        return $key === '' ? null : mb_substr($key, 0, 100);
    }
}
