<?php

namespace App\Services;

use App\Models\ImportHistory;
use App\Models\ProductMasterPreset;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MasterPresetImportService
{
    private const REQUIRED_HEADERS = ['name'];

    private const SUPPORTED_HEADERS = ['type', 'code', 'name', 'color_hex', 'content', 'sort_order', 'is_active'];

    public function __construct(private SpreadsheetImportReader $reader)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $filePath, Store $store, string $duplicateStrategy = 'skip', ?string $defaultType = null): array
    {
        $spreadsheet = $this->reader->read($filePath);
        $this->validateHeaders($spreadsheet['headers']);

        $existing = $this->existingLookup($store);
        $result = $this->emptyResult();
        $seen = [];

        foreach ($spreadsheet['rows'] as $row) {
            $this->inspectRow($row, $existing, $seen, $result, false, $duplicateStrategy, $store, $defaultType);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, Store $store, ?User $user, string $filename, string $duplicateStrategy = 'skip', ?string $defaultType = null): array
    {
        return DB::transaction(function () use ($filePath, $store, $user, $filename, $duplicateStrategy, $defaultType) {
            $spreadsheet = $this->reader->read($filePath);
            $this->validateHeaders($spreadsheet['headers']);

            $existing = $this->existingLookup($store);
            $result = $this->emptyResult();
            $seen = [];

            foreach ($spreadsheet['rows'] as $row) {
                $this->inspectRow($row, $existing, $seen, $result, true, $duplicateStrategy, $store, $defaultType);
            }

            $errorFilePath = $this->writeErrorFile($store, $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'master_presets',
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
     * @return array<string, ProductMasterPreset>
     */
    private function existingLookup(Store $store): array
    {
        $presets = ProductMasterPreset::where('store_id', $store->id)->get();
        $lookup = [];

        foreach ($presets as $preset) {
            $key = $preset->type . '|' . $this->normalizeName($preset->name);
            $lookup[$key] = $preset;
            if (!empty($preset->code)) {
                $lookup[$preset->type . '|code:' . strtoupper(trim($preset->code))] = $preset;
            }
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
        Store $store,
        ?string $defaultType = null
    ): void {
        $result['total']++;
        $rowNum = $row['_row_number'] ?? $result['total'];

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $result['failed']++;
            $result['failed_rows'][] = [
                'row' => $rowNum,
                'name' => '',
                'reason' => 'Name is required.',
            ];
            return;
        }

        $type = trim((string) ($row['type'] ?? ''));
        if ($type === '' && $defaultType !== null) {
            $type = $defaultType;
        }

        $validTypes = ['connector_spec', 'color', 'shelf_location', 'warranty', 'return_policy'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'connector_spec';
        }

        $code = !empty($row['code']) ? strtoupper(trim((string) $row['code'])) : null;
        $colorHex = !empty($row['color_hex']) ? trim((string) $row['color_hex']) : null;
        $content = !empty($row['content']) ? trim((string) $row['content']) : null;
        $sortOrder = isset($row['sort_order']) && is_numeric($row['sort_order']) ? (int) $row['sort_order'] : 0;
        
        $isActive = true;
        if (isset($row['is_active'])) {
            $rawActive = strtolower(trim((string) $row['is_active']));
            $isActive = !in_array($rawActive, ['0', 'false', 'no', 'inactive', 'off'], true);
        }

        $lookupKey = $type . '|' . $this->normalizeName($name);
        $codeKey = $code ? ($type . '|code:' . $code) : null;

        if (isset($seen[$lookupKey]) || ($codeKey && isset($seen[$codeKey]))) {
            $result['failed']++;
            $result['failed_rows'][] = [
                'row' => $rowNum,
                'name' => $name,
                'reason' => 'Duplicate entry in spreadsheet file.',
            ];
            return;
        }

        $seen[$lookupKey] = true;
        if ($codeKey) {
            $seen[$codeKey] = true;
        }

        $existingItem = $existing[$lookupKey] ?? ($codeKey ? ($existing[$codeKey] ?? null) : null);

        if ($existingItem) {
            if ($duplicateStrategy === 'update') {
                if ($commit) {
                    $existingItem->update([
                        'name' => $name,
                        'code' => $code ?? $existingItem->code,
                        'color_hex' => $colorHex ?? $existingItem->color_hex,
                        'content' => $content ?? $existingItem->content,
                        'sort_order' => $sortOrder ?: $existingItem->sort_order,
                        'is_active' => $isActive,
                    ]);
                    $result['updated']++;
                } else {
                    $result['updatable']++;
                }

                $result['preview_rows'][] = [
                    'row' => $rowNum,
                    'type' => $type,
                    'name' => $name,
                    'code' => $code ?: '—',
                    'action' => 'Update',
                ];
                return;
            }

            $result['skipped_duplicate']++;
            $result['preview_rows'][] = [
                'row' => $rowNum,
                'type' => $type,
                'name' => $name,
                'code' => $code ?: '—',
                'action' => 'Skip (Duplicate)',
            ];
            return;
        }

        if ($commit) {
            $created = ProductMasterPreset::create([
                'store_id' => $store->id,
                'type' => $type,
                'name' => $name,
                'code' => $code,
                'color_hex' => $colorHex,
                'content' => $content,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
            ]);
            $existing[$lookupKey] = $created;
            if ($codeKey) {
                $existing[$codeKey] = $created;
            }
            $result['imported']++;
        } else {
            $result['creatable']++;
        }

        $result['preview_rows'][] = [
            'row' => $rowNum,
            'type' => $type,
            'name' => $name,
            'code' => $code ?: '—',
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
        $path = $dir . '/master_presets-errors-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.csv';

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
