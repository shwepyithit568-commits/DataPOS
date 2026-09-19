<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\SyncOutboxRecord;
use App\Services\OfflineSyncService;
use App\Services\SyncClientService;
use App\Services\SyncOutboxWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SyncAdminController extends Controller
{
    public function __construct(
        private readonly OfflineSyncService $syncService,
        private readonly SyncClientService $client,
        private readonly SyncOutboxWriter $writer,
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

        $oldestPending = SyncOutboxRecord::query()
            ->where('store_id', $store->id)
            ->whereIn('status', ['pending', 'failed'])
            ->orderBy('created_offline_at')
            ->first();

        // How — and whether — this installation replicates. A shop owner looking
        // at a growing queue needs to see "why is nothing leaving" without
        // reading .env.
        $replication = [
            'enabled'      => (bool) config('sync.enabled'),
            'role'         => (string) config('sync.role'),
            'is_terminal'  => $this->writer->isTerminal(),
            'central_url'  => (string) config('sync.central_url'),
            'store_slug'   => (string) config('sync.store_slug'),
            'key_last4'    => (string) config('sync.api_key') !== ''
                ? substr((string) config('sync.api_key'), -4)
                : null,
            'device'       => $this->writer->deviceId(),
            'problems'     => $this->writer->configProblems(),
        ];

        return view('admin.sync.index', compact('store', 'records', 'health', 'status', 'replication', 'oldestPending'));
    }

    /**
     * Retry an individual failed sync record — over the network, to the central.
     */
    public function retry(Request $request, string $store_slug, int $id): RedirectResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();
        $record = SyncOutboxRecord::where('store_id', $store->id)->findOrFail($id);

        $result = $this->client->push($store, 1, $record->client_transaction_id);

        return $this->redirectWithPushResult($result);
    }

    /**
     * Retry all pending and failed records in the outbox.
     */
    public function retryAll(Request $request, string $store_slug): RedirectResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();

        return $this->redirectWithPushResult($this->client->push($store));
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
     * Drain the queue towards the central installation (status widget button).
     */
    public function trigger(Request $request, string $store_slug): JsonResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();

        $result = $this->client->push($store);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'pushed'  => $result['pushed'],
            'synced'  => $result['synced'],
            'failed'  => $result['failed'],
            'health'  => $this->syncService->getSyncHealth($store),
        ]);
    }

    /**
     * Probe the central installation so the owner can tell a dead link from a
     * rejected key. Kept out of index() so a page load never waits on a timeout.
     */
    public function testConnection(Request $request, string $store_slug): JsonResponse
    {
        $store = Store::where('slug', $store_slug)->firstOrFail();

        return response()->json($this->client->ping($store) + [
            'success' => true,
            'store'   => $store->slug,
        ]);
    }

    /**
     * @param  array{ok:bool, pushed:int, synced:int, failed:int, message:string}  $result
     */
    private function redirectWithPushResult(array $result): RedirectResponse
    {
        if (! $result['ok']) {
            $message = match ($result['message']) {
                'offline'        => __('messages.sync_push_offline'),
                'unauthorized'   => __('messages.sync_connection_unauthorized'),
                'not_configured' => __('messages.sync_config_missing', ['keys' => implode(', ', $result['problems'] ?? [])]),
                'nothing_to_push' => __('messages.sync_no_pending'),
                default          => __('messages.sync_push_failed', ['reason' => $result['message']]),
            };

            return redirect()->back()->with('error', $message);
        }

        if ($result['pushed'] === 0) {
            return redirect()->back()->with('info', __('messages.sync_no_pending'));
        }

        return redirect()->back()->with(
            $result['failed'] > 0 ? 'warning' : 'success',
            __('messages.sync_pushed_counts', [
                'synced' => $result['synced'],
                'failed' => $result['failed'],
            ])
        );
    }
}
