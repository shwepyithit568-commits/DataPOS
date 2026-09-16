<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateSyncRequest;
use App\Models\Store;
use App\Services\OfflineSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncApiController extends Controller
{
    public function __construct(
        private readonly OfflineSyncService $syncService
    ) {
    }

    /**
     * Store resolved by AuthenticateSyncRequest; the key is already verified.
     */
    private function store(Request $request): Store
    {
        return $request->attributes->get(AuthenticateSyncRequest::STORE_ATTRIBUTE);
    }

    /**
     * Push batch of offline operational records.
     */
    public function push(Request $request, string $slug): JsonResponse
    {
        $store = $this->store($request);

        $validated = $request->validate([
            'records'                         => ['required', 'array', 'min:1', 'max:500'],
            'records.*.client_transaction_id' => ['required', 'string', 'max:64'],
            'records.*.record_type'           => ['required', 'string', 'in:pos_sale,customer_debt,expense'],
            'records.*.payload'               => ['required', 'array'],
            'records.*.created_offline_at'    => ['nullable', 'date'],
        ]);

        $results = $this->syncService->processPushBatch($store, $validated['records']);

        return response()->json([
            'success' => true,
            'store'   => $store->slug,
            'results' => $results,
            'health'  => $this->syncService->getSyncHealth($store),
        ]);
    }

    /**
     * Pull catalog/price delta updates.
     */
    public function pull(Request $request, string $slug): JsonResponse
    {
        $store = $this->store($request);

        $since = $request->query('since') ? Carbon::parse($request->query('since')) : null;
        $delta = $this->syncService->getPullDelta($store, $since);

        return response()->json([
            'success' => true,
            'store'   => $store->slug,
            'delta'   => $delta,
        ]);
    }

    /**
     * Check sync connectivity status and queue health.
     */
    public function status(Request $request, string $slug): JsonResponse
    {
        $store = $this->store($request);
        $health = $this->syncService->getSyncHealth($store);

        return response()->json([
            'success' => true,
            'online'  => true,
            'store'   => $store->slug,
            'health'  => $health,
        ]);
    }

    /**
     * Trigger immediate synchronization of local pending queue.
     */
    public function trigger(Request $request, string $slug): JsonResponse
    {
        $store = $this->store($request);
        $pending = $this->syncService->getPendingQueue($store);

        if ($pending->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No pending records to sync.',
                'health'  => $this->syncService->getSyncHealth($store),
            ]);
        }

        $records = $pending->map(fn ($r) => [
            'client_transaction_id' => $r->client_transaction_id,
            'record_type'           => $r->record_type,
            'payload'               => $r->payload,
            'created_offline_at'    => $r->created_offline_at?->toIso8601String(),
        ])->all();

        $results = $this->syncService->processPushBatch($store, $records);

        return response()->json([
            'success' => true,
            'synced'  => count($results),
            'results' => $results,
            'health'  => $this->syncService->getSyncHealth($store),
        ]);
    }
}
