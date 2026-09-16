<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\SyncOutboxRecord;
use App\Services\OfflineSyncService;
use Illuminate\Http\JsonResponse;
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

    /**
     * Issue a new sync API key, invalidating any previously installed one.
     *
     * The plaintext key is flashed so it can be shown exactly once on the Sync
     * screen; only its hash is persisted.
     */
    public function rotateKey(Request $request, string $store_slug): RedirectResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();

        $key = $store->generateSyncApiKey();

        AuditLog::write(
            $store->id,
            'sync_api_key_rotated',
            'store',
            $store->id,
            ['last4' => $store->sync_api_key_last4],
            auth()->id(),
            $request->ip()
        );

        return redirect()
            ->route('store.admin.sync.index', ['store_slug' => $store->slug])
            ->with('sync_api_key_plaintext', $key)
            ->with('success', __('messages.sync_key_generated'));
    }

    /**
     * Revoke the sync API key, disabling terminal sync until a new key is issued.
     */
    public function revokeKey(Request $request, string $store_slug): RedirectResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();

        $store->revokeSyncApiKey();

        AuditLog::write(
            $store->id,
            'sync_api_key_revoked',
            'store',
            $store->id,
            [],
            auth()->id(),
            $request->ip()
        );

        return redirect()
            ->route('store.admin.sync.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.sync_key_revoked'));
    }

    /**
     * JSON sync health for the admin status widget (session-authenticated).
     *
     * Replaces the widget's old unauthenticated hit on /api/v1/.../sync/status.
     */
    public function status(Request $request, string $store_slug): JsonResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'store'   => $store->slug,
            'health'  => $this->syncService->getSyncHealth($store),
        ]);
    }

    /**
     * Process the pending outbox for the status widget (session-authenticated).
     */
    public function trigger(Request $request, string $store_slug): JsonResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();
        $pending = $this->syncService->getPendingQueue($store);

        if ($pending->isNotEmpty()) {
            $records = $pending->map(fn ($r) => [
                'client_transaction_id' => $r->client_transaction_id,
                'record_type'           => $r->record_type,
                'payload'               => $r->payload,
                'created_offline_at'    => $r->created_offline_at?->toIso8601String(),
            ])->all();

            $this->syncService->processPushBatch($store, $records);
        }

        return response()->json([
            'success' => true,
            'health'  => $this->syncService->getSyncHealth($store),
        ]);
    }
}
