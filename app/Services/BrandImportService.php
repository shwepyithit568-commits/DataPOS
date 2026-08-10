<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandImportService
{
    private const REQUIRED_HEADERS = ['name'];

    private const SUPPORTED_HEADERS = ['name', 'slug'];

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
        $seen = ['names' => [], 'slugs' => []];

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
            $seen = ['names' => [], 'slugs' => []];

            foreach ($spreadsheet['rows'] as $row) {
                $this->inspectRow($row, $existing, $seen, $result, true, $duplicateStrategy, $store);
            }

            $errorFilePath = $this->writeErrorFile($store, $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'brands',
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
     * Load existing brands once, keyed by normalized name and slug so each
     * row resolves its target in constant time.
     *
     * @return array{names: array<string, Brand>, slugs: array<string, Brand>}
     */
    private function existingLookup(Store $store): array
    {
        $brands = Brand::where('store_id', $store->id)->get();

        $names = [];
        $slugs = [];
        foreach ($brands as $brand) {
            $names[$this->normalizeName($brand->name)] = $brand;
            $slugs[$brand->slug] = $brand;
        }

        return ['names' => $names, 'slugs' => $slugs];
    }

    /**
     * @param array<string, Brand> $existing
     * @param array{names: array<string, true>, slugs: array<string, true>} $seen
     * @param array<string, mixed> $result
     */
    private function inspectRow(array $row, array &$existing, array &$seen, array &$result, bool $persist, string $duplicateStrategy, Store $store): void
    {
        $result['total']++;

        $name = trim((string) ($row['name'] ?? ''));
        $slugInput = trim((string) ($row['slug'] ?? ''));

        if ($name === '') {
            $result['failed']++;
            $result['failed_rows'][] = $this->failure($row, 'name', 'name is required.');
            return;
        }

        if (mb_strlen($name) > 255) {
            $result['failed']++;
            $result['failed_rows'][] = $this->failure($row, 'name', 'name is too long (max 255).');
            return;
        }

        $nameKey = $this->normalizeName($name);

        // Resolve the existing brand by explicit slug first (authoritative),
        // then by normalized name — matching the admin unique-name rule.
        $existingBrand = null;
        if ($slugInput !== '' && isset($existing['slugs'][$slugInput])) {
            $existingBrand = $existing['slugs'][$slugInput];
        } elseif (isset($existing['names'][$nameKey])) {
            $existingBrand = $existing['names'][$nameKey];
        }

        // An explicit slug that collides with a *different* brand than the
        // resolved name match is ambiguous — reject instead of guessing.
        if ($existingBrand && $slugInput !== '' && isset($existing['slugs'][$slugInput])
            && $existing['slugs'][$slugInput]->id !== $existingBrand->id) {
            $result['failed']++;
            $result['failed_rows'][] = $this->failure($row, 'slug', "slug '{$slugInput}' belongs to a different brand.");
            return;
        }

        $slug = $slugInput !== '' ? $slugInput : Str::slug($name);
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $result['failed']++;
            $result['failed_rows'][] = $this->failure($row, 'slug', 'slug must be lowercase letters, numbers and dashes only.');
            return;
        }

        // A brand created/updated earlier in this same file counts as
        // existing too — preview and import must classify identically.
        $intraFileDuplicate = isset($seen['names'][$nameKey]);
        if ($intraFileDuplicate) {
            $result['skipped_duplicate']++;
            $this->appendPreviewRow($result, $row, $name, 'skip_duplicate');
            return;
        }

        if ($existingBrand) {
            if ($duplicateStrategy === 'update') {
                $result['valid']++;
                $result['updatable']++;
                $this->appendPreviewRow($result, $row, $name, 'update');

                $finalSlug = $this->uniqueSlug($store, $slug, $existingBrand->id, $seen['slugs']);
                $seen['names'][$nameKey] = true;
                $seen['slugs'][$finalSlug] = true;

                if ($persist) {
                    $existingBrand->update(['name' => $name, 'slug' => $finalSlug]);
                    $existing['names'][$this->normalizeName($name)] = $existingBrand;
                    $existing['slugs'][$finalSlug] = $existingBrand;
                    $result['updated']++;
                }
            } else {
                $result['skipped_duplicate']++;
                $this->appendPreviewRow($result, $row, $name, 'skip_duplicate');
            }
            return;
        }

        // Brand is new — make sure the slug is free within this store.
        $slug = $this->uniqueSlug($store, $slug, null, $seen['slugs']);
        $seen['names'][$nameKey] = true;
        $seen['slugs'][$slug] = true;

        $result['valid']++;
        $result['creatable']++;
        $this->appendPreviewRow($result, $row, $name, 'create');

        if ($persist) {
            $brand = Brand::create([
                'store_id' => $store->id,
                'name' => $name,
                'slug' => $slug,
            ]);
            $existing['names'][$this->normalizeName($name)] = $brand;
            $existing['slugs'][$slug] = $brand;
            $result['imported']++;
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
     * @param array<string, true> $seenSlugs
     */
    private function uniqueSlug(Store $store, string $base, ?int $ignoreId, array $seenSlugs = []): string
    {
        $slug = $base;
        $suffix = 2;

        while (isset($seenSlugs[$slug])
            || Brand::where('store_id', $store->id)
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function normalizeName(string $name): string
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
            'valid' => 0,
            'creatable' => 0,
            'updatable' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped_duplicate' => 0,
            'failed' => 0,
            'failed_rows' => [],
            'preview_rows' => [],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $result
     */
    private function appendPreviewRow(array &$result, array $row, string $name, string $action): void
    {
        $result['preview_rows'][] = [
            'row' => $row['_row'] ?? null,
            'name' => $name,
            'slug' => trim((string) ($row['slug'] ?? '')),
            'action' => $action,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function failure(array $row, string $field, string $reason): array
    {
        return [
            'row' => $row['_row'] ?? null,
            'name' => trim((string) ($row['name'] ?? '')),
            'field' => $field,
            'reason' => $reason,
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

        $path = 'imports/errors/' . $store->id . '-brands-' . now()->format('Ymd-His') . '.csv';
        $stream = fopen('php://temp', 'w');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Row', 'Name', 'Field', 'Reason']);
        foreach ($failedRows as $row) {
            fputcsv($stream, [$row['row'] ?? '', $row['name'] ?? '', $row['field'] ?? '', $row['reason'] ?? '']);
        }
        rewind($stream);
        Storage::disk('local')->put($path, stream_get_contents($stream));
        fclose($stream);

        return $path;
    }
}
