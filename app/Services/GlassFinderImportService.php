<?php

namespace App\Services;

use App\Models\GlassFinderItem;
use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GlassFinderImportService
{
    private const REQUIRED_HEADERS = ['brand', 'phone_model', 'glass_code'];
    private const VALID_STOCK_STATUSES = ['in_stock', 'out_of_stock'];

    public function __construct(private SpreadsheetImportReader $reader)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $filePath, Store $store): array
    {
        $spreadsheet = $this->readSpreadsheet($filePath);
        $this->validateHeaders($spreadsheet['headers']);

        $existingKeySet = $this->existingKeySet($store);
        $seenKeySet = [];
        $result = $this->emptyResult();

        foreach ($spreadsheet['rows'] as $row) {
            $this->inspectRow($row, $existingKeySet, $seenKeySet, $result, false);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, Store $store, ?User $user, string $filename): array
    {
        return DB::transaction(function () use ($filePath, $store, $user, $filename) {
            $spreadsheet = $this->readSpreadsheet($filePath);
            $this->validateHeaders($spreadsheet['headers']);

            $existingKeySet = $this->existingKeySet($store);
            $seenKeySet = [];
            $result = $this->emptyResult();

            foreach ($spreadsheet['rows'] as $row) {
                $this->inspectRow($row, $existingKeySet, $seenKeySet, $result, true, $store);
            }

            $errorFilePath = $this->writeErrorFile($store, $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'glass_finder',
                'filename' => $filename,
                'total_rows' => $result['total'],
                'success_rows' => $result['imported'],
                'failed_rows' => $result['failed'],
                'error_file_path' => $errorFilePath,
            ]);

            return $result;
        });
    }

    /**
     * Read the workbook, preferring the app's "Glass Finder" sheet, then the
     * ACDC AppSheet export's "App_Data" sheet (Glass_Code / Brand /
     * Phone_Model / Stock_Status / Active / Verified), then the active sheet.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, ?string>>}
     */
    private function readSpreadsheet(string $filePath): array
    {
        foreach (['Glass Finder', 'App_Data'] as $sheetName) {
            $spreadsheet = $this->reader->read($filePath, $sheetName);
            if (empty(array_diff(self::REQUIRED_HEADERS, $spreadsheet['headers']))) {
                return $spreadsheet;
            }
        }

        return $this->reader->read($filePath);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(): array
    {
        return [
            'total' => 0,
            'valid' => 0,
            'valid_rows' => 0,
            'imported' => 0,
            'duplicates' => 0,
            'duplicate_rows' => 0,
            'skipped_duplicate' => 0,
            'failed' => 0,
            'failed_rows' => [],
            'preview_rows' => [],
        ];
    }

    /**
     * @param array<string, true> $existingKeySet
     * @param array<string, true> $seenKeySet
     * @param array<string, mixed> $result
     */
    private function inspectRow(array $row, array &$existingKeySet, array &$seenKeySet, array &$result, bool $persist, ?Store $store = null): void
    {
        $result['total']++;

        $validationError = $this->validateRow($row);
        if ($validationError !== null) {
            $result['failed']++;
            $result['failed_rows'][] = $validationError;
            return;
        }

        $normalizedCode = GlassCodeNormalizer::normalize($row['glass_code']);
        $key = $this->key($row['phone_model'], $normalizedCode);

        if (isset($existingKeySet[$key]) || isset($seenKeySet[$key])) {
            $result['duplicates']++;
            $result['duplicate_rows']++;
            $result['skipped_duplicate']++;
            $this->appendPreviewRow($result, $row, $normalizedCode, 'skip_duplicate');
            return;
        }

        $result['valid']++;
        $result['valid_rows']++;
        $this->appendPreviewRow($result, $row, $normalizedCode, 'import');

        if (!$persist) {
            $seenKeySet[$key] = true;
            return;
        }

        GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => $row['brand'],
            'phone_model' => $row['phone_model'],
            'glass_code' => $row['glass_code'],
            'normalized_glass_code' => $normalizedCode,
            'stock_status' => $this->normalizeStockStatus($row['stock_status'] ?? null),
        ]);

        $seenKeySet[$key] = true;
        $existingKeySet[$key] = true;
        $result['imported']++;
    }

    /**
     * @param array<int, string> $headers
     */
    private function validateHeaders(array $headers): void
    {
        $missingHeaders = array_diff(self::REQUIRED_HEADERS, $headers);

        if (!empty($missingHeaders)) {
            throw new \InvalidArgumentException('Missing required columns: ' . implode(', ', $missingHeaders));
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function validateRow(array $row): ?array
    {
        foreach (self::REQUIRED_HEADERS as $header) {
            if (trim((string) ($row[$header] ?? '')) === '') {
                return $this->failure($row, $header, "{$header} is required.");
            }
        }

        if (strlen((string) $row['brand']) > 255) {
            return $this->failure($row, 'brand', 'Brand is too long.');
        }

        if (strlen((string) $row['phone_model']) > 255) {
            return $this->failure($row, 'phone_model', 'Phone model is too long.');
        }

        if (strlen((string) $row['glass_code']) > 100) {
            return $this->failure($row, 'glass_code', 'Glass code is too long.');
        }

        $stockStatus = $this->normalizeStockStatus($row['stock_status'] ?? null);
        if (!in_array($stockStatus, self::VALID_STOCK_STATUSES, true)) {
            return $this->failure($row, 'stock_status', 'Invalid stock_status: ' . ($row['stock_status'] ?? ''));
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function failure(array $row, string $field, string $message): array
    {
        $originalData = $row;
        unset($originalData['_row']);

        return [
            'row' => $row['_row'],
            'row_number' => $row['_row'],
            'field' => $field,
            'reason' => $message,
            'error_message' => $message,
            'original_data' => json_encode($originalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * Accept both snake_case ("out_of_stock") and human-readable
     * ("Out Of Stock") stock values from AppSheet exports; blank means
     * the default in-stock state.
     */
    private function normalizeStockStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        $status = str_replace(' ', '_', $status);

        return $status === '' ? 'in_stock' : $status;
    }

    private function key(string $phoneModel, string $normalizedCode): string
    {
        return mb_strtolower(trim($phoneModel)) . '||' . mb_strtolower(trim($normalizedCode));
    }

    /**
     * @return array<string, true>
     */
    private function existingKeySet(Store $store): array
    {
        return GlassFinderItem::where('store_id', $store->id)
            ->get(['phone_model', 'normalized_glass_code'])
            ->map(fn(GlassFinderItem $item) => $this->key($item->phone_model, $item->normalized_glass_code))
            ->flip()
            ->map(fn() => true)
            ->toArray();
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $row
     */
    private function appendPreviewRow(array &$result, array $row, string $normalizedCode, string $action): void
    {
        if (count($result['preview_rows']) >= 20) {
            return;
        }

        $result['preview_rows'][] = [
            'row' => $row['_row'],
            'brand' => $row['brand'],
            'phone_model' => $row['phone_model'],
            'glass_code' => $row['glass_code'],
            'normalized_glass_code' => $normalizedCode,
            'stock_status' => $this->normalizeStockStatus($row['stock_status'] ?? null),
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

        $path = 'import-errors/' . $store->id . '/glass-finder-' . now()->format('YmdHis') . '-' . Str::random(8) . '.csv';
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['row_number', 'field', 'error_message', 'original_data']);

        foreach ($failedRows as $failedRow) {
            fputcsv($handle, [
                $failedRow['row_number'] ?? $failedRow['row'] ?? '',
                $failedRow['field'] ?? '',
                $failedRow['error_message'] ?? $failedRow['reason'] ?? '',
                $failedRow['original_data'] ?? '',
            ]);
        }

        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }
}
