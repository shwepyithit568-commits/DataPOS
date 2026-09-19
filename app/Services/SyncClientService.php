<?php

namespace App\Services;

use App\Models\Store;
use App\Models\SyncOutboxRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The terminal side of shop ⇄ cloud replication.
 *
 * Everything here is best-effort by design: a shop PC with a dead internet link
 * keeps selling, and the queue drains later. No method throws on a network
 * failure — callers get a structured result they can show the shop owner.
 */
class SyncClientService
{
    public function __construct(
        private readonly SyncOutboxWriter $outbox
    ) {
    }

    /**
     * Push queued records to the central installation.
     *
     * @return array{ok:bool, pushed:int, synced:int, failed:int, message:string, results:array}
     */
    public function push(Store $store, ?int $limit = null, ?string $onlyClientTxId = null): array
    {
        $problems = $this->outbox->configProblems();

        if ($problems !== []) {
            return $this->result(false, 0, 0, 0, 'not_configured', [], $problems);
        }

        $limit = $limit ?? (int) config('sync.batch_size', 50);
        $maxAttempts = (int) config('sync.max_attempts', 10);

        $records = SyncOutboxRecord::query()
            ->where('store_id', $store->id)
            ->whereIn('status', ['pending', 'failed'])
            ->where('retry_count', '<', $maxAttempts)
            ->when($onlyClientTxId !== null, fn ($query) => $query->where('client_transaction_id', $onlyClientTxId))
            ->orderBy('created_offline_at')
            ->limit($limit)
            ->get();

        if ($records->isEmpty()) {
            return $this->result(true, 0, 0, 0, 'nothing_to_push', []);
        }

        $payload = $records->map(fn (SyncOutboxRecord $record) => [
            'client_transaction_id' => $record->client_transaction_id,
            'record_type'           => $record->record_type,
            'payload'               => $record->payload,
            'created_offline_at'    => optional($record->created_offline_at)->toIso8601String(),
        ])->all();

        try {
            $response = $this->client()->post($this->endpoint('push'), ['records' => $payload]);
        } catch (\Throwable $e) {
            // Still offline — leave every row pending so the next run retries.
            Log::info("Sync push to central unreachable for store [{$store->id}]: " . $e->getMessage());

            return $this->result(false, $records->count(), 0, 0, 'offline', []);
        }

        if ($response->status() === 401) {
            return $this->result(false, $records->count(), 0, 0, 'unauthorized', []);
        }

        if (! $response->successful()) {
            return $this->result(false, $records->count(), 0, 0, 'http_' . $response->status(), []);
        }

        // The central answers per record; the local row follows that answer
        // rather than being marked synced just because a request came back.
        $results = (array) $response->json('results', []);
        $byKey = collect($results)->keyBy('client_transaction_id');

        $synced = 0;
        $failed = 0;

        foreach ($records as $record) {
            $answer = $byKey->get($record->client_transaction_id);
            $status = $answer['status'] ?? '';

            if ($status === 'synced') {
                $record->forceFill([
                    'status'        => 'synced',
                    'synced_at'     => now(),
                    // A replicated sale whose total did not tie out still syncs —
                    // the shop's books are the shop's — but the divergence stays
                    // on screen instead of being buried.
                    'error_message' => ! empty($answer['warning']) ? 'warning: ' . $answer['warning'] : null,
                ])->save();
                $synced++;
                continue;
            }

            // 'skipped' / missing / explicit failure — keep the reason visible.
            $record->forceFill([
                'status'        => $status === 'skipped' ? 'conflict' : 'failed',
                'retry_count'   => $record->retry_count + 1,
                'error_message' => $answer['error']
                    ?? $answer['message']
                    ?? 'The central did not confirm this record.',
            ])->save();
            $failed++;
        }

        return $this->result(true, $records->count(), $synced, $failed, $failed > 0 ? 'partial' : 'ok', $results);
    }

    /**
     * Fetch the central's catalog delta so the shop can see what the online
     * installation knows (prices, stock, customers).
     *
     * READ-ONLY on purpose. Writing it locally would mean mapping another
     * database's row ids onto this one — a collision would silently overwrite a
     * local product's prices or stock history. Until that mapping (and who owns
     * the catalog) is a deliberate decision, pull only reports.
     *
     * @return array{ok:bool, message:string, delta:array}
     */
    public function pull(Store $store): array
    {
        $problems = $this->outbox->configProblems();

        if ($problems !== []) {
            return ['ok' => false, 'message' => 'not_configured', 'delta' => [], 'problems' => $problems];
        }

        try {
            $response = $this->client()->get($this->endpoint('pull'));
        } catch (\Throwable $e) {
            Log::info("Sync pull from central unreachable for store [{$store->id}]: " . $e->getMessage());

            return ['ok' => false, 'message' => 'offline', 'delta' => []];
        }

        if ($response->status() === 401) {
            return ['ok' => false, 'message' => 'unauthorized', 'delta' => []];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'http_' . $response->status(), 'delta' => []];
        }

        return ['ok' => true, 'message' => 'fetched', 'delta' => (array) $response->json('delta', [])];
    }

    /**
     * Cheap reachability probe for the Sync screen / `sync:status`.
     *
     * @return array{ok:bool, message:string, central_health:array}
     */
    public function ping(Store $store): array
    {
        $problems = $this->outbox->configProblems();

        if ($problems !== []) {
            return ['ok' => false, 'message' => 'not_configured', 'central_health' => [], 'problems' => $problems];
        }

        try {
            $response = $this->client()->get($this->endpoint('status'));
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'offline', 'central_health' => []];
        }

        if ($response->status() === 401) {
            return ['ok' => false, 'message' => 'unauthorized', 'central_health' => []];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'http_' . $response->status(), 'central_health' => []];
        }

        return ['ok' => true, 'message' => 'online', 'central_health' => (array) $response->json('health', [])];
    }

    /**
     * @return array{ok:bool, pushed:int, synced:int, failed:int, message:string, results:array, problems:list<string>}
     */
    private function result(
        bool $ok,
        int $pushed,
        int $synced,
        int $failed,
        string $message,
        array $results = [],
        array $problems = []
    ): array {
        return compact('ok', 'pushed', 'synced', 'failed', 'message', 'results', 'problems');
    }

    private function endpoint(string $action): string
    {
        return sprintf(
            '%s/api/v1/store/%s/sync/%s',
            rtrim((string) config('sync.central_url'), '/'),
            urlencode((string) config('sync.store_slug')),
            $action
        );
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'X-Sync-Key' => (string) config('sync.api_key'),
            'Accept'     => 'application/json',
        ])
            ->timeout((int) config('sync.timeout', 20))
            ->withOptions(['verify' => (bool) config('sync.verify_tls', true)]);
    }
}
