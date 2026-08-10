<?php

namespace App\Services;

use App\Models\DataMaintenanceLog;
use App\Models\GlassFinderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DataMaintenanceRollbackService
{
    public function preview(string $executionId, ?string $storeOption = null): array
    {
        return $this->analyze($executionId, $storeOption);
    }

    public function apply(string $executionId, ?string $storeOption = null): array
    {
        $analysis = $this->analyze($executionId, $storeOption);
        $restored = [];

        DB::transaction(function () use ($analysis, &$restored): void {
            foreach ($analysis['reversible'] as $entry) {
                $updated = DB::table($entry['table'])
                    ->where('id', $entry['record_id'])
                    ->where($entry['field_name'], $entry['new_value'])
                    ->update([
                        $entry['field_name'] => $entry['old_value'],
                        'updated_at' => now(),
                    ]);

                if ($updated === 1) {
                    $restored[] = $entry['record_id'];
                }
            }
        });

        return [
            'analysis' => $analysis,
            'restored_count' => count($restored),
            'restored_ids' => $restored,
        ];
    }

    private function analyze(string $executionId, ?string $storeOption): array
    {
        if (trim($executionId) === '') {
            throw new RuntimeException('Execution ID is required.');
        }

        $store = $this->resolveStore($storeOption);

        $logs = DataMaintenanceLog::query()
            ->when($store, fn($query) => $query->where('store_id', $store->id))
            ->where('execution_id', $executionId)
            ->orderByDesc('id')
            ->get();

        if ($logs->isEmpty()) {
            throw new RuntimeException('No maintenance records found for this execution ID.');
        }

        $reversible = collect();
        $skipped = collect();

        foreach ($logs as $log) {
            $table = $this->tableForRecordType($log->record_type);
            if ($table === null) {
                $skipped->push($this->skip($log, 'Unsupported record type.'));
                continue;
            }

            $current = DB::table($table)->where('id', $log->record_id)->value($log->field_name);
            if ($current === null) {
                $skipped->push($this->skip($log, 'Record or field not found.'));
                continue;
            }

            if ((string) $current !== (string) $log->new_value) {
                $skipped->push($this->skip($log, 'Current value changed after maintenance.'));
                continue;
            }

            $reversible->push([
                'log_id' => $log->id,
                'table' => $table,
                'store_id' => $log->store_id,
                'record_type' => $log->record_type,
                'record_id' => $log->record_id,
                'field_name' => $log->field_name,
                'old_value' => $log->old_value,
                'new_value' => $log->new_value,
            ]);
        }

        return [
            'execution_id' => $executionId,
            'store' => $store,
            'total_logs' => $logs->count(),
            'reversible' => $reversible->values(),
            'skipped' => $skipped->values(),
            'summary' => [
                'reversible_count' => $reversible->count(),
                'skipped_count' => $skipped->count(),
            ],
        ];
    }

    private function tableForRecordType(string $recordType): ?string
    {
        return match ($recordType) {
            Product::class => 'products',
            GlassFinderItem::class => 'glass_finder_items',
            default => null,
        };
    }

    private function skip(DataMaintenanceLog $log, string $reason): array
    {
        return [
            'log_id' => $log->id,
            'record_type' => $log->record_type,
            'record_id' => $log->record_id,
            'field_name' => $log->field_name,
            'reason' => $reason,
        ];
    }

    private function resolveStore(?string $storeOption): ?Store
    {
        if ($storeOption === null || $storeOption === '') {
            return null;
        }

        return Store::query()
            ->where('slug', $storeOption)
            ->orWhere('id', $storeOption)
            ->firstOrFail();
    }
}
