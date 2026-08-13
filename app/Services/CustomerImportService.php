<?php

namespace App\Services;

use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AlinnThit pilot import for retail/wholesale customers.
 *
 * Customers are Users with an active store_user membership (role
 * retail_customer | wholesale_customer). Duplicate detection is by
 * normalized phone — the globally-unique users.phone key. A phone that
 * already belongs to a user *outside* this store simply attaches that user
 * to the current store (cross-store customer sharing without data leaks);
 * a phone already attached to this store is skipped or updated per the
 * chosen duplicate strategy.
 */
class CustomerImportService
{
    private const REQUIRED_HEADERS = ['name', 'phone'];

    private const SUPPORTED_HEADERS = ['name', 'phone', 'email', 'role'];

    private const VALID_ROLES = ['retail_customer', 'wholesale_customer'];

    public function __construct(private SpreadsheetImportReader $reader)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $filePath, Store $store, string $duplicateStrategy = 'skip'): array
    {
        $spreadsheet = $this->reader->read($filePath, 'Customers');
        $this->validateHeaders($spreadsheet['headers']);

        $result = $this->emptyResult();
        $seenPhones = [];

        foreach ($spreadsheet['rows'] as $row) {
            $this->inspectRow($row, $store, $seenPhones, $result, false, $duplicateStrategy);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, Store $store, ?User $user, string $filename, string $duplicateStrategy = 'skip'): array
    {
        return DB::transaction(function () use ($filePath, $store, $user, $filename, $duplicateStrategy) {
            $spreadsheet = $this->reader->read($filePath, 'Customers');
            $this->validateHeaders($spreadsheet['headers']);

            $result = $this->emptyResult();
            $seenPhones = [];

            foreach ($spreadsheet['rows'] as $row) {
                $this->inspectRow($row, $store, $seenPhones, $result, true, $duplicateStrategy);
            }

            $errorFilePath = $this->writeErrorFile($store, $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'customers',
                'filename' => $filename,
                'total_rows' => $result['total'],
                'success_rows' => $result['imported'] + $result['updated'] + $result['attached'],
                'failed_rows' => $result['failed'],
                'error_file_path' => $errorFilePath,
            ]);

            return $result;
        });
    }

    /**
     * @param array<string, true> $seenPhones
     * @param array<string, mixed> $result
     */
    private function inspectRow(array $row, Store $store, array &$seenPhones, array &$result, bool $persist, string $duplicateStrategy): void
    {
        $result['total']++;

        $name = trim((string) ($row['name'] ?? ''));
        $phone = $this->normalizePhone($row['phone'] ?? '');
        $email = trim((string) ($row['email'] ?? ''));
        $role = strtolower(trim((string) ($row['role'] ?? 'retail_customer')));

        if ($name === '') {
            $this->fail($result, $row, 'name', 'name is required.');
            return;
        }

        if (mb_strlen($name) > 255) {
            $this->fail($result, $row, 'name', 'name is too long (max 255).');
            return;
        }

        if ($phone === '') {
            $this->fail($result, $row, 'phone', 'phone is required.');
            return;
        }

        if (strlen($phone) < 7 || strlen($phone) > 15 || !preg_match('/^\d+$/', $phone)) {
            $this->fail($result, $row, 'phone', "Invalid phone '{$row['phone']}' — use 7-15 digits (leading 0 or +95 allowed).");
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail($result, $row, 'email', "Invalid email '{$email}'.");
            return;
        }

        if (!in_array($role, self::VALID_ROLES, true)) {
            $this->fail($result, $row, 'role', "Invalid role '{$role}' — use retail_customer or wholesale_customer.");
            return;
        }

        if (isset($seenPhones[$phone])) {
            $result['skipped_duplicate']++;
            $this->appendPreviewRow($result, $row, $name, $phone, 'skip_duplicate');
            return;
        }

        $existingUser = User::where('phone', $phone)->first();
        $membershipExists = $existingUser !== null
            && $existingUser->stores()->wherePivot('store_id', $store->id)->exists();

        if ($existingUser !== null && $membershipExists) {
            if ($duplicateStrategy !== 'update') {
                $result['skipped_duplicate']++;
                $this->appendPreviewRow($result, $row, $name, $phone, 'skip_duplicate');
                return;
            }

            $result['updatable']++;
            $this->appendPreviewRow($result, $row, $name, $phone, 'update');
            $seenPhones[$phone] = true;

            if ($persist) {
                $existingUser->update(['name' => $name, 'email' => $email !== '' ? $email : null]);
                $existingUser->stores()->updateExistingPivot($store->id, ['role' => $role]);
                $result['updated']++;
            }
            return;
        }

        // New customer: either a brand-new user, or an existing user whose
        // memberships are in other stores only (attach to this store).
        $result['creatable']++;
        $this->appendPreviewRow($result, $row, $name, $phone, $existingUser !== null ? 'attach' : 'create');
        $seenPhones[$phone] = true;

        if ($persist) {
            if ($existingUser === null) {
                // Debt customers do not need login credentials; the random
                // password keeps the account unusable until a store owner
                // invites them properly.
                $existingUser = User::create([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email !== '' ? $email : null,
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'customer',
                ]);
                $result['imported']++;
            } else {
                $existingUser->update(['name' => $name, 'email' => $email !== '' ? $email : null]);
                $result['attached']++;
            }

            $existingUser->stores()->attach($store->id, [
                'role' => $role,
                'status' => 'active',
            ]);
        }
    }

    /**
     * @param array<int, string> $headers
     */
    private function validateHeaders(array $headers): void
    {
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);
        if (!empty($missing)) {
            throw new \InvalidArgumentException('Missing required columns: ' . implode(', ', $missing));
        }
    }

    /**
     * Normalize a Myanmar phone cell: strip spaces/dashes/parens/plus and a
     * leading "09" → "9..." (e.g. "09 123 456 789" → "9123456789").
     */
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
            'creatable' => 0,
            'updatable' => 0,
            'imported' => 0,
            'updated' => 0,
            'attached' => 0,
            'success' => 0,
            'skipped_duplicate' => 0,
            'failed' => 0,
            'failed_rows' => [],
            'preview_rows' => [],
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function fail(array &$result, array $row, string $field, string $reason): void
    {
        $result['failed']++;
        $result['failed_rows'][] = [
            'row' => $row['_row'] ?? null,
            'name' => trim((string) ($row['name'] ?? '')),
            'phone' => trim((string) ($row['phone'] ?? '')),
            'field' => $field,
            'reason' => $reason,
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function appendPreviewRow(array &$result, array $row, string $name, string $phone, string $action): void
    {
        if (count($result['preview_rows']) >= 20) {
            return;
        }

        $result['preview_rows'][] = [
            'row' => $row['_row'] ?? null,
            'name' => $name,
            'phone' => $phone,
            'email' => trim((string) ($row['email'] ?? '')),
            'role' => strtolower(trim((string) ($row['role'] ?? 'retail_customer'))),
            'action' => $action,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $failedRows
     */
    private function writeErrorFile(Store $store, array $failedRows): ?string
    {
        if (empty($failedRows)) {
            return null;
        }

        $path = 'import-errors/' . $store->id . '/customers-' . now()->format('YmdHis') . '-' . Str::random(8) . '.csv';
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['row_number', 'name', 'phone', 'field', 'error_message']);

        foreach ($failedRows as $failedRow) {
            fputcsv($handle, [
                $failedRow['row'] ?? '',
                $failedRow['name'] ?? '',
                $failedRow['phone'] ?? '',
                $failedRow['field'] ?? '',
                $failedRow['reason'] ?? '',
            ]);
        }

        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }
}
