<?php

namespace App\Services;

use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AlinnThit pilot import for suppliers (store-scoped master data).
 *
 * Duplicate detection matches by normalized phone first, then by
 * case-insensitive name — both within the current store only.
 */
class SupplierImportService
{
    private const REQUIRED_HEADERS = ['name'];

    private const SUPPORTED_HEADERS = ['name', 'phone', 'email', 'contact_person', 'address', 'notes'];

    public function __construct(private SpreadsheetImportReader $reader)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $filePath, Store $store, string $duplicateStrategy = 'skip'): array
    {
        $spreadsheet = $this->reader->read($filePath, 'Suppliers');
        $this->validateHeaders($spreadsheet['headers']);

        $result = $this->emptyResult();
        $seen = ['phones' => [], 'names' => []];

        foreach ($spreadsheet['rows'] as $row) {
            $this->inspectRow($row, $store, $seen, $result, false, $duplicateStrategy);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, Store $store, ?User $user, string $filename, string $duplicateStrategy = 'skip'): array
    {
        return DB::transaction(function () use ($filePath, $store, $user, $filename, $duplicateStrategy) {
            $spreadsheet = $this->reader->read($filePath, 'Suppliers');
            $this->validateHeaders($spreadsheet['headers']);

            $existing = $this->existingLookup($store);
            $result = $this->emptyResult();
            $seen = ['phones' => [], 'names' => []];

            foreach ($spreadsheet['rows'] as $row) {
                $this->inspectRow($row, $store, $seen, $result, true, $duplicateStrategy, $existing);
            }

            $errorFilePath = $this->writeErrorFile($store, $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'suppliers',
                'filename' => $filename,
                'total_rows' => $result['total'],
                'success_rows' => $result['imported'] + $result['updated'],
                'failed_rows' => $result['failed'],
                'error_file_path' => $errorFilePath,
            ]);

            return $result;
        });
    }

    /**
     * @param array{phones: array<string, Supplier>, names: array<string, Supplier>} $existing
     * @param array{phones: array<string, true>, names: array<string, true>} $seen
     * @param array<string, mixed> $result
     */
    private function inspectRow(array $row, Store $store, array &$seen, array &$result, bool $persist, string $duplicateStrategy, ?array &$existing = null): void
    {
        $result['total']++;

        $name = trim((string) ($row['name'] ?? ''));
        $phone = $this->normalizePhone($row['phone'] ?? '');
        $email = trim((string) ($row['email'] ?? ''));

        if ($name === '') {
            $this->fail($result, $row, 'name', 'name is required.');
            return;
        }

        if (mb_strlen($name) > 255) {
            $this->fail($result, $row, 'name', 'name is too long (max 255).');
            return;
        }

        if ($phone !== '' && (strlen($phone) < 7 || strlen($phone) > 15 || !preg_match('/^\d+$/', $phone))) {
            $this->fail($result, $row, 'phone', "Invalid phone '{$row['phone']}' — use 7-15 digits (leading 0 or +95 allowed).");
            return;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail($result, $row, 'email', "Invalid email '{$email}'.");
            return;
        }

        $nameKey = $this->nameKey($name);

        // Intra-file duplicates are skipped regardless of strategy — the
        // same phone/name appearing twice in one file is a data error.
        if (($phone !== '' && isset($seen['phones'][$phone])) || isset($seen['names'][$nameKey])) {
            $result['skipped_duplicate']++;
            $this->appendPreviewRow($result, $row, $name, $phone, 'skip_duplicate');
            return;
        }

        $existingSupplier = $this->matchExisting($store, $name, $phone, $existing);

        if ($existingSupplier !== null) {
            if ($duplicateStrategy !== 'update') {
                $result['skipped_duplicate']++;
                $this->appendPreviewRow($result, $row, $name, $phone, 'skip_duplicate');
                return;
            }

            $result['updatable']++;
            $this->appendPreviewRow($result, $row, $name, $phone, 'update');
            $seen['phones'][$phone !== '' ? $phone : 'p' . $existingSupplier->id] = true;
            $seen['names'][$nameKey] = true;

            if ($persist) {
                $existingSupplier->update($this->payload($row, $phone));
                $result['updated']++;
            }
            return;
        }

        $result['creatable']++;
        $this->appendPreviewRow($result, $row, $name, $phone, 'create');
        $seen['phones'][$phone !== '' ? $phone : 'p' . Str::random(6)] = true;
        $seen['names'][$nameKey] = true;

        if ($persist) {
            Supplier::create(['store_id' => $store->id] + $this->payload($row, $phone));
            $result['imported']++;
        }
    }

    /**
     * @param array{phones: array<string, Supplier>, names: array<string, Supplier>}|null $existing
     */
    private function matchExisting(Store $store, string $name, string $phone, ?array &$existing): ?Supplier
    {
        // Lazily load the store's suppliers once per import run.
        if ($existing === null) {
            $existing = $this->existingLookup($store);
        }

        if ($phone !== '' && isset($existing['phones'][$phone])) {
            return $existing['phones'][$phone];
        }

        return $existing['names'][$this->nameKey($name)] ?? null;
    }

    /**
     * @return array{phones: array<string, Supplier>, names: array<string, Supplier>}
     */
    private function existingLookup(Store $store): array
    {
        $suppliers = Supplier::where('store_id', $store->id)->get();

        $phones = [];
        $names = [];
        foreach ($suppliers as $supplier) {
            if ($supplier->phone !== null && $supplier->phone !== '') {
                $phones[$supplier->phone] = $supplier;
            }
            $names[$this->nameKey($supplier->name)] = $supplier;
        }

        return ['phones' => $phones, 'names' => $names];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function payload(array $row, string $phone): array
    {
        return [
            'name' => trim((string) $row['name']),
            'phone' => $phone !== '' ? $phone : null,
            'email' => trim((string) ($row['email'] ?? '')) !== '' ? trim((string) $row['email']) : null,
            'contact_person' => trim((string) ($row['contact_person'] ?? '')) !== '' ? trim((string) $row['contact_person']) : null,
            'address' => trim((string) ($row['address'] ?? '')) !== '' ? trim((string) $row['address']) : null,
            'notes' => trim((string) ($row['notes'] ?? '')) !== '' ? trim((string) $row['notes']) : null,
        ];
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

    private function normalizePhone(mixed $value): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return $digits === null ? '' : ltrim($digits, '0');
    }

    private function nameKey(string $name): string
    {
        return mb_strtolower(trim($name));
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
            'contact_person' => trim((string) ($row['contact_person'] ?? '')),
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

        $path = 'import-errors/' . $store->id . '/suppliers-' . now()->format('YmdHis') . '-' . Str::random(8) . '.csv';
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
