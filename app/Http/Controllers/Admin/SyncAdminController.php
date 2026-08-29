<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\SyncOutboxRecord;
use App\Services\OfflineSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SyncAdminController extends Controller
{
    public function __construct(
        private readonly OfflineSyncService $syncService
    ) {
    }

    /**
     * Display Sync Management dashboard & Outbox queue.
     */
    public function index(Request $request, string $store_slug): View
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();
        $status = $request->query('status', 'all');

        $query = SyncOutboxRecord::query()->where('store_id', $store->id);
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();
        $health = $this->syncService->getSyncHealth($store);

        return view('admin.sync.index', compact('store', 'records', 'health', 'status'));
    }

    /**
     * Retry an individual failed sync record.
     */
    public function retry(Request $request, string $store_slug, int $id): RedirectResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();
        $record = SyncOutboxRecord::where('store_id', $store->id)->findOrFail($id);

        $results = $this->syncService->processPushBatch($store, [[
            'client_transaction_id' => $record->client_transaction_id,
            'record_type'           => $record->record_type,
            'payload'               => $record->payload,
            'created_offline_at'    => $record->created_offline_at?->toIso8601String(),
        ]]);

        $status = $results[0]['status'] ?? 'failed';
        if ($status === 'synced') {
            return redirect()->back()->with('success', __('messages.sync_retry_success') ?? 'Sync successful!');
        }

        return redirect()->back()->with('error', $results[0]['error'] ?? 'Sync failed.');
    }

    /**
     * Retry all pending and failed records in the outbox.
     */
    public function retryAll(Request $request, string $store_slug): RedirectResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();
        $pending = $this->syncService->getPendingQueue($store, 100);

        if ($pending->isEmpty()) {
            return redirect()->back()->with('info', __('messages.sync_no_pending') ?? 'No pending records to sync.');
        }

        $records = $pending->map(fn ($r) => [
            'client_transaction_id' => $r->client_transaction_id,
            'record_type'           => $r->record_type,
            'payload'               => $r->payload,
            'created_offline_at'    => $r->created_offline_at?->toIso8601String(),
        ])->all();

        $results = $this->syncService->processPushBatch($store, $records);
        $syncedCount = count(array_filter($results, fn ($r) => ($r['status'] ?? '') === 'synced'));

        return redirect()->back()->with('success', "Processed {$syncedCount} of " . count($results) . " records successfully.");
    }
}
