<?php

namespace App\Services;

use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use App\Models\VariantPreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VariantPresetImportService
{
    private const REQUIRED_HEADERS = ['name'];

    public function __construct(private SpreadsheetImportReader $reader)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $filePath, Store $store, string $duplicateStrategy = 'skip'): array
    {
        $spreadsheet = $this->reader->read($filePath);
        $this->validateHeaders($spreadsheet['headers']);

        $existing = $this->existingLookup($store);
        $result = $this->emptyResult();
        $seen = [];

        foreach ($spreadsheet['rows'] as $row) {
            $this->inspectRow($row, $existing, $seen, $result, false, $duplicateStrategy, $store);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, Store $store, ?User $user, string $filename, string $duplicateStrategy = 'skip'): array
    {
        return DB::transaction(function () use ($filePath, $store, $user, $filename, $duplicateStrategy) {
            $spreadsheet = $this->reader->read($filePath);
            $this->validateHeaders($spreadsheet['headers']);

            $existing = $this->existingLookup($store);
            $result = $this->emptyResult();
            $seen = [];

            foreach ($spreadsheet['rows'] as $row) {
                $this->inspectRow($row, $existing, $seen, $result, true, $duplicateStrategy, $store);
            }

            $errorFilePath = $this->writeErrorFile($store, $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'variant_presets',
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
     * @return array<string, VariantPreset>
     */
    private function existingLookup(Store $store): array
    {
        $presets = VariantPreset::where('store_id', $store->id)->get();
        $lookup = [];

        foreach ($presets as $preset) {
            $key = $this->normalizeName($preset->name);
            $lookup[$key] = $preset;
        }

        return $lookup;
    }

    private function inspectRow(
        array $row,
        array &$existing,
        array &$seen,
        array &$result,
        bool $commit,
        string $duplicateStrategy,
        Store $store
    ): void {
        $result['total']++;
        $rowNum = $row['_row_number'] ?? $result['total'];

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $result['failed']++;
            $result['failed_rows'][] = [
                'row' => $rowNum,
                'name' => '',
                'reason' => 'Preset Name is required.',
            ];
            return;
        }

        $family = !empty($row['category_family']) ? trim((string) $row['category_family']) : null;
        $sortOrder = isset($row['sort_order']) && is_numeric($row['sort_order']) ? (int) $row['sort_order'] : 0;

        // Parse options
        $options = [];
        $optionName = trim((string) ($row['option_name'] ?? ($row['attribute'] ?? '')));
        $optionValuesRaw = trim((string) ($row['option_values'] ?? ($row['values'] ?? '')));

        if ($optionName !== '' || $optionValuesRaw !== '') {
            $valArray = array_values(array_filter(array_map('trim', explode(',', $optionValuesRaw)), fn ($v) => $v !== ''));
            $options[] = [
                'name' => $optionName ?: 'Option',
                'values' => $valArray,
            ];
        }

        $lookupKey = $this->normalizeName($name);

        if (isset($seen[$lookupKey])) {
            $result['failed']++;
            $result['failed_rows'][] = [
                'row' => $rowNum,
                'name' => $name,
                'reason' => 'Duplicate preset name in spreadsheet file.',
            ];
            return;
        }

        $seen[$lookupKey] = true;
        $existingItem = $existing[$lookupKey] ?? null;

        if ($existingItem) {
            if ($duplicateStrategy === 'update') {
                if ($commit) {
                    $existingItem->update([
                        'name' => $name,
                        'category_family' => $family ?? $existingItem->category_family,
                        'options' => !empty($options) ? $options : $existingItem->options,
                        'sort_order' => $sortOrder ?: $existingItem->sort_order,
                    ]);
                    $result['updated']++;
                } else {
                    $result['updatable']++;
                }

                $result['preview_rows'][] = [
                    'row' => $rowNum,
                    'name' => $name,
                    'family' => $family ?: '—',
                    'action' => 'Update',
                ];
                return;
            }

            $result['skipped_duplicate']++;
            $result['preview_rows'][] = [
                'row' => $rowNum,
                'name' => $name,
                'family' => $family ?: '—',
                'action' => 'Skip (Duplicate)',
            ];
            return;
        }

        if ($commit) {
            $created = VariantPreset::create([
                'store_id' => $store->id,
                'name' => $name,
                'category_family' => $family,
                'options' => $options,
                'sort_order' => $sortOrder,
            ]);
            $existing[$lookupKey] = $created;
            $result['imported']++;
        } else {
            $result['creatable']++;
        }

        $result['preview_rows'][] = [
            'row' => $rowNum,
            'name' => $name,
            'family' => $family ?: '—',
            'action' => 'Create',
        ];
    }

    private function validateHeaders(array $headers): void
    {
        $normalized = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        foreach (self::REQUIRED_HEADERS as $req) {
            if (!in_array($req, $normalized, true)) {
                throw new \InvalidArgumentException("Missing required column '{$req}' in import file.");
            }
        }
    }

    private function emptyResult(): array
    {
        return [
            'total' => 0,
            'creatable' => 0,
            'updatable' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped_duplicate' => 0,
            'failed' => 0,
            'preview_rows' => [],
            'failed_rows' => [],
        ];
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name), 'UTF-8');
    }

    private function writeErrorFile(Store $store, array $failedRows): ?string
    {
        if (empty($failedRows)) {
            return null;
        }

        $dir = 'imports/errors/' . $store->id;
        $path = $dir . '/variant_presets-errors-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.csv';

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Row', 'Name', 'Reason']);

        foreach ($failedRows as $failed) {
            fputcsv($output, [
                $failed['row'] ?? '',
                $failed['name'] ?? '',
                $failed['reason'] ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        Storage::disk('local')->put($path, $csv);

        return $path;
    }
}
