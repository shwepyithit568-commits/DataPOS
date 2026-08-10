<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryImportService
{
    private const REQUIRED_HEADERS = ['name'];

    private const SUPPORTED_HEADERS = ['name', 'slug', 'parent', 'description', 'icon'];

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

        $registry = $this->existingRegistry($store);
        $result = $this->emptyResult();
        $seen = ['names' => [], 'slugs' => []];

        // Two passes so a Sub-category can reference a Main category defined
        // later in the same file. Preview and import run the exact same code.
        $this->processRows($spreadsheet['rows'], $registry, $seen, $result, false, $duplicateStrategy, $store, false);
        $this->processRows($spreadsheet['rows'], $registry, $seen, $result, false, $duplicateStrategy, $store, true);

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

            $registry = $this->existingRegistry($store);
            $result = $this->emptyResult();
            $seen = ['names' => [], 'slugs' => []];

            $this->processRows($spreadsheet['rows'], $registry, $seen, $result, true, $duplicateStrategy, $store, false);
            $this->processRows($spreadsheet['rows'], $registry, $seen, $result, true, $duplicateStrategy, $store, true);

            $errorFilePath = $this->writeErrorFile($store, $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'categories',
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
     * Every existing store category as a descriptor array, keyed by normalized
     * name and slug. Descriptors keep preview and import uniform — no partial
     * Eloquent models needed for rows that only exist in the file.
     *
     * @return array{names: array<string, array{id: ?int, parent_id: ?int}>, slugs: array<string, array{id: ?int, parent_id: ?int}>}
     */
    private function existingRegistry(Store $store): array
    {
        $names = [];
        $slugs = [];
        foreach (Category::where('store_id', $store->id)->get() as $category) {
            $names[$this->normalizeName($category->name)] = ['id' => $category->id, 'parent_id' => $category->parent_id];
            $slugs[$category->slug] = ['id' => $category->id, 'parent_id' => $category->parent_id];
        }

        return ['names' => $names, 'slugs' => $slugs];
    }

    /**
     * @param array<int, array<string, ?string>> $rows
     * @param array{names: array<string, array{id: ?int, parent_id: ?int}>, slugs: array<string, array{id: ?int, parent_id: ?int}>} $registry
     * @param array{names: array<string, true>, slugs: array<string, true>} $seen
     * @param array<string, mixed> $result
     */
    private function processRows(array $rows, array &$registry, array &$seen, array &$result, bool $persist, string $duplicateStrategy, Store $store, bool $childrenOnly): void
    {
        foreach ($rows as $row) {
            $parentInput = trim((string) ($row['parent'] ?? ''));

            if ($childrenOnly && $parentInput === '') {
                continue;
            }
            if (!$childrenOnly && $parentInput !== '') {
                continue;
            }

            $this->inspectRow($row, $registry, $seen, $result, $persist, $duplicateStrategy, $store);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param array{names: array<string, array{id: ?int, parent_id: ?int}>, slugs: array<string, array{id: ?int, parent_id: ?int}>} $registry
     * @param array{names: array<string, true>, slugs: array<string, true>} $seen
     * @param array<string, mixed> $result
     */
    private function inspectRow(array $row, array &$registry, array &$seen, array &$result, bool $persist, string $duplicateStrategy, Store $store): void
    {
        $result['total']++;

        $name = trim((string) ($row['name'] ?? ''));
        $slugInput = trim((string) ($row['slug'] ?? ''));
        $parentInput = trim((string) ($row['parent'] ?? ''));
        $icon = trim((string) ($row['icon'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));

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

        if ($icon !== '' && mb_strlen($icon) > 8) {
            $result['failed']++;
            $result['failed_rows'][] = $this->failure($row, 'icon', 'icon must be 8 characters or fewer.');
            return;
        }

        $nameKey = $this->normalizeName($name);

        // Intra-file duplicates are skipped in both preview and import so the
        // two views can never disagree about a row's fate.
        if (isset($seen['names'][$nameKey])) {
            $result['skipped_duplicate']++;
            $this->appendPreviewRow($result, $row, $name, 'skip_duplicate');
            return;
        }

        $existing = $this->resolveExisting($registry, $nameKey, $slugInput);

        if ($existing && $slugInput !== '' && isset($registry['slugs'][$slugInput])
            && $registry['slugs'][$slugInput]['id'] !== $existing['id']) {
            $result['failed']++;
            $result['failed_rows'][] = $this->failure($row, 'slug', "slug '{$slugInput}' belongs to a different category.");
            return;
        }

        $slug = $slugInput !== '' ? $slugInput : Str::slug($name);
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $result['failed']++;
            $result['failed_rows'][] = $this->failure($row, 'slug', 'slug must be lowercase letters, numbers and dashes only.');
            return;
        }

        // ── Resolve the parent (Main category) ─────────────────────────────
        $parent = null;
        if ($parentInput !== '') {
            $parent = $this->resolveParent($registry, $parentInput, $nameKey);
            if ($parent === null) {
                $result['failed']++;
                $result['failed_rows'][] = $this->failure($row, 'parent', "parent '{$parentInput}' not found in this store.");
                return;
            }
            if ($parent['parent_id'] !== null) {
                $result['failed']++;
                $result['failed_rows'][] = $this->failure($row, 'parent', "parent '{$parentInput}' must be a Main category.");
                return;
            }
        }

        if ($existing) {
            // An existing Main category that already has Sub-categories must
            // not be demoted to a Sub-category (three-level tree guard).
            if ($parent && $existing['parent_id'] === null && $this->hasChildren($registry, $existing['id'])) {
                $result['failed']++;
                $result['failed_rows'][] = $this->failure($row, 'parent', "category '{$name}' already has Sub-categories and cannot be demoted.");
                return;
            }

            if ($duplicateStrategy === 'update') {
                $result['valid']++;
                $result['updatable']++;
                $this->appendPreviewRow($result, $row, $name, 'update');

                $finalSlug = $this->uniqueSlug($store, $slug, $existing['id'], $seen['slugs']);
                $seen['names'][$nameKey] = true;
                $seen['slugs'][$finalSlug] = true;
                $plannedParent = $parent ? $parent['id'] : $existing['parent_id'];

                // Register the planned state in BOTH modes so a later row in
                // the same file can resolve this category as a parent (or as a
                // duplicate) identically in preview and import.
                $this->register($registry, $nameKey, $finalSlug, $persist ? $existing['id'] : $existing['id'], $plannedParent);

                if ($persist) {
                    $category = Category::find($existing['id']);
                    $category->update([
                        'name' => $name,
                        'slug' => $finalSlug,
                        'parent_id' => $plannedParent,
                        'description' => $description !== '' ? $description : $category->description,
                        'icon' => $icon !== '' ? $icon : $category->icon,
                    ]);
                    $this->refreshRegistry($registry, $category);
                    $result['updated']++;
                }
            } else {
                $result['skipped_duplicate']++;
                $this->appendPreviewRow($result, $row, $name, 'skip_duplicate');
            }
            return;
        }

        // New category — make sure the slug is free within this store.
        $slug = $this->uniqueSlug($store, $slug, null, $seen['slugs']);
        $seen['names'][$nameKey] = true;
        $seen['slugs'][$slug] = true;

        // Planned descriptor (id null until persisted) lets later rows in the
        // same file treat this category as an existing Main parent in both
        // preview and import.
        $this->register($registry, $nameKey, $slug, null, $parent ? $parent['id'] : null);

        $result['valid']++;
        $result['creatable']++;
        $this->appendPreviewRow($result, $row, $name, 'create');

        if ($persist) {
            $category = Category::create([
                'store_id' => $store->id,
                'parent_id' => $parent ? $parent['id'] : null,
                'name' => $name,
                'slug' => $slug,
                'description' => $description !== '' ? $description : null,
                'icon' => $icon !== '' ? $icon : null,
            ]);
            $this->refreshRegistry($registry, $category);
            $result['imported']++;
        }
    }

    /**
     * Insert (or overwrite) a descriptor in the registry. In persist mode the
     * real id is passed in once the model exists; before that the id stays
     * null so children only ever use it as a linkage check, never as a FK.
     *
     * @param array{names: array<string, array{id: ?int, parent_id: ?int}>, slugs: array<string, array{id: ?int, parent_id: ?int}>} $registry
     */
    private function register(array &$registry, string $nameKey, string $slug, ?int $id, ?int $parentId): void
    {
        $descriptor = ['id' => $id, 'parent_id' => $parentId];
        $registry['names'][$nameKey] = $descriptor;
        $registry['slugs'][$slug] = $descriptor;
    }

    /**
     * Resolve an existing category by slug first (authoritative), then by
     * normalized name — matching the admin unique-name rule.
     *
     * @param array{names: array<string, array{id: ?int, parent_id: ?int}>, slugs: array<string, array{id: ?int, parent_id: ?int}>} $registry
     * @return array{id: ?int, parent_id: ?int}|null
     */
    private function resolveExisting(array $registry, string $nameKey, string $slugInput): ?array
    {
        if ($slugInput !== '' && isset($registry['slugs'][$slugInput])) {
            return $registry['slugs'][$slugInput];
        }

        return $registry['names'][$nameKey] ?? null;
    }

    /**
     * Resolve the parent reference by name or slug — existing categories
     * first, then categories created earlier in this same file run.
     *
     * @param array{names: array<string, array{id: ?int, parent_id: ?int}>, slugs: array<string, array{id: ?int, parent_id: ?int}>} $registry
     * @return array{id: ?int, parent_id: ?int}|null
     */
    private function resolveParent(array $registry, string $parentInput, string $childNameKey): ?array
    {
        if ($this->normalizeName($parentInput) === $childNameKey) {
            return null; // self-reference is never a valid parent
        }

        $byName = $registry['names'][$this->normalizeName($parentInput)] ?? null;
        if ($byName) {
            return $byName;
        }

        return $registry['slugs'][$parentInput] ?? null;
    }

    /**
     * @param array{names: array<string, array{id: ?int, parent_id: ?int}>, slugs: array<string, array{id: ?int, parent_id: ?int}>} $registry
     */
    private function hasChildren(array $registry, int $categoryId): bool
    {
        foreach ($registry['names'] as $descriptor) {
            if ($descriptor['parent_id'] === $categoryId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{names: array<string, array{id: ?int, parent_id: ?int}>, slugs: array<string, array{id: ?int, parent_id: ?int}>} $registry
     */
    private function refreshRegistry(array &$registry, Category $category): void
    {
        $descriptor = ['id' => $category->id, 'parent_id' => $category->parent_id];
        $registry['names'][$this->normalizeName($category->name)] = $descriptor;
        $registry['slugs'][$category->slug] = $descriptor;
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
            || Category::where('store_id', $store->id)
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
            'parent' => trim((string) ($row['parent'] ?? '')),
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

        $path = 'imports/errors/' . $store->id . '-categories-' . now()->format('Ymd-His') . '.csv';
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
