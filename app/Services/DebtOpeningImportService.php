<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Services\CustomerDebtService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AlinnThit pilot cutover: opening (pre-cutover) customer debt balances.
 *
 * Rows are `phone, amount[, notes]`. Each row resolves to a customer by
 * normalized phone (the shared customer model — same rule as the customer
 * import, so POS, ecommerce and this import agree on who a phone belongs
 * to). Only customers already attached to the current store can receive an
 * opening balance — a phone with no in-store customer is a failed row, so
 * the receivables total can be reconciled against the old system exactly.
 *
 * Confirming posts one immutable `opening_balance` ledger entry per row
 * (SoT §17 — the balance is SUM(amount), never a direct edit) through
 * CustomerDebtService, then records ImportHistory + an audit log. The whole
 * import is one transaction; a duplicate phone within the file is skipped
 * (never double-posted).
 */
class DebtOpeningImportService
{
    private const REQUIRED_HEADERS = ['phone', 'amount'];

    private const MAX_AMOUNT = '999999999999'; // 12 digits — MMK ceiling guard

    public function __construct(
        private SpreadsheetImportReader $reader,
        private CustomerDebtService $debt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $filePath, Store $store, ?string $duplicateStrategy = null): array
    {
        $spreadsheet = $this->reader->read($filePath, 'Debt');
        $this->validateHeaders($spreadsheet['headers']);

        $result = $this->emptyResult();

        foreach ($spreadsheet['rows'] as $row) {
            $this->inspectRow($row, $store, $result, false);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, Store $store, ?User $user, string $filename, ?string $duplicateStrategy = null): array
    {
        return DB::transaction(function () use ($filePath, $store, $user, $filename) {
            $spreadsheet = $this->reader->read($filePath, 'Debt');
            $this->validateHeaders($spreadsheet['headers']);

            $result = $this->emptyResult();

            foreach ($spreadsheet['rows'] as $row) {
                $this->inspectRow($row, $store, $result, true, $user);
            }

            $errorFilePath = $this->writeErrorFile($store, $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'debt',
                'filename' => $filename,
                'total_rows' => $result['total'],
                'success_rows' => $result['posted'],
                'failed_rows' => $result['failed'],
                'error_file_path' => $errorFilePath,
            ]);

            AuditLog::write(
                storeId: $store->id,
                action: 'debt_opening_imported',
                entityType: 'store',
                entityId: $store->id,
                metadata: [
                    'total_rows' => $result['total'],
                    'posted' => $result['posted'],
                    'failed' => $result['failed'],
                    'total_amount' => $result['total_amount'],
                ],
                actorId: $user?->id,
            );

            return $result;
        });
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function inspectRow(array $row, Store $store, array &$result, bool $persist, ?User $actor = null): void
    {
        $result['total']++;

        $phone = $this->normalizePhone($row['phone'] ?? '');
        $amount = trim((string) ($row['amount'] ?? ''));
        $notes = trim((string) ($row['notes'] ?? ''));

        if ($phone === '') {
            $this->fail($result, $row, 'phone is required.');
            return;
        }

        if (strlen($phone) < 7 || strlen($phone) > 15 || ! preg_match('/^\d+$/', $phone)) {
            $this->fail($result, $row, "Invalid phone '{$row['phone']}' — use 7-15 digits (leading 0 or +95 allowed).");
            return;
        }

        if ($amount === '' || ! preg_match('/^\d+(\.\d{1,2})?$/', $amount) || bccomp($amount, '0', 2) <= 0) {
            $this->fail($result, $row, "Invalid amount '{$row['amount']}' — must be a positive number in MMK (max 2 decimals).");
            return;
        }

        if (bccomp($amount, self::MAX_AMOUNT, 2) > 0) {
            $this->fail($result, $row, "Amount '{$amount}' is unreasonably large.");
            return;
        }

        $customer = User::findByNormalizedPhone($phone);
        $inStore = $customer !== null && $customer->stores()->wherePivot('store_id', $store->id)->exists();

        if (! $inStore) {
            $result['not_found']++;
            $result['failed']++;
            $result['failed_rows'][] = [
                'row' => $row['_row'] ?? null,
                'name' => $customer?->name ?? '',
                'reason' => "No customer with phone {$phone} in this store — import the customer first.",
            ];
            $this->appendPreviewRow($result, $row, $customer?->name ?? '', $phone, $amount, $customer !== null ? $this->debt->balanceFor($store->id, $customer->id) : '0.00', 'not_found');
            return;
        }

        // Same phone twice in one file is a mistake — skip, never double-post.
        if (isset($result['seen'][$phone])) {
            $result['failed']++;
            $result['failed_rows'][] = [
                'row' => $row['_row'] ?? null,
                'name' => $customer->name,
                'reason' => "Duplicate phone {$phone} in the same file — skipped.",
            ];
            $this->appendPreviewRow($result, $row, $customer->name, $phone, $amount, $this->debt->balanceFor($store->id, $customer->id), 'skip_duplicate');
            return;
        }
        $result['seen'][$phone] = true;

        $result['found']++;
        $result['total_amount'] = bcadd($result['total_amount'], $amount, 2);

        $balance = $this->debt->balanceFor($store->id, $customer->id);
        $this->appendPreviewRow($result, $row, $customer->name, $phone, $amount, $balance, 'post');

        if ($persist) {
            try {
                $this->debt->recordOpeningBalance(
                    $store,
                    $customer->id,
                    $amount,
                    $actor ?? User::find(auth()->id()),
                    $notes !== '' ? $notes : null,
                    'ob:' . $store->id . ':' . Str::uuid(),
                );
                $result['posted']++;
            } catch (InventoryException $e) {
                $result['failed']++;
                $result['failed_rows'][] = [
                    'row' => $row['_row'] ?? null,
                    'name' => $customer->name,
                    'reason' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function fail(array &$result, array $row, string $reason): void
    {
        $result['failed']++;
        $result['failed_rows'][] = [
            'row' => $row['_row'] ?? null,
            'name' => trim((string) ($row['phone'] ?? '')),
            'reason' => $reason,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function appendPreviewRow(array &$result, array $row, string $name, string $phone, string $amount, string $balance, string $action): void
    {
        $result['preview_rows'][] = [
            'row' => $row['_row'] ?? null,
            'name' => $name,
            'phone' => $phone,
            'amount' => $amount,
            'balance' => $balance,
            'action' => $action,
        ];
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function validateHeaders(array $headers): void
    {
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);
        if (! empty($missing)) {
            throw new \InvalidArgumentException('Missing required columns: ' . implode(', ', $missing));
        }
    }

    private function normalizePhone(mixed $value): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return $digits === null ? '' : ltrim($digits, '0');
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(): array
    {
        return [
            'total' => 0,
            'found' => 0,
            'posted' => 0,
            'not_found' => 0,
            'failed' => 0,
            'total_amount' => '0.00',
            'seen' => [],
            'failed_rows' => [],
            'preview_rows' => [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $failedRows
     */
    private function writeErrorFile(Store $store, array $failedRows): ?string
    {
        if (empty($failedRows)) {
            return null;
        }

        $path = "imports/errors/debt-{$store->id}-" . now()->format('Ymd-His') . '-' . Str::random(6) . '.csv';
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['row', 'phone', 'reason']);
        foreach ($failedRows as $failed) {
            fputcsv($handle, [$failed['row'] ?? '', $failed['name'] ?? '', $failed['reason'] ?? '']);
        }
        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }
}
