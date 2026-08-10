<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportHistory;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportHistoryController extends Controller
{
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();

        $summaryQuery = ImportHistory::where('store_id', $store->id);

        $summary = [
            'total_imports' => (clone $summaryQuery)->count(),
            'successful_rows' => (clone $summaryQuery)->sum('success_rows'),
            'failed_rows' => (clone $summaryQuery)->sum('failed_rows'),
            'last_import_date' => (clone $summaryQuery)->latest()->value('created_at'),
        ];

        $histories = ImportHistory::where('store_id', $store->id)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.import_history.index', compact('store', 'summary', 'histories'));
    }

    public function show(string $store_slug, ImportHistory $history, StoreContext $context): View
    {
        $store = $context->getStore();
        $this->authorizeHistory($history, $store->id);

        $history->load('user');

        return view('admin.import_history.show', compact('store', 'history'));
    }

    public function downloadErrors(string $store_slug, ImportHistory $history, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        $this->authorizeHistory($history, $store->id);

        if (!$history->error_file_path || !Storage::disk('local')->exists($history->error_file_path)) {
            abort(404, 'Error report is not available.');
        }

        $downloadName = pathinfo($history->filename, PATHINFO_FILENAME) . '-failed-rows.csv';

        return Storage::disk('local')->download($history->error_file_path, $downloadName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(string $store_slug, ImportHistory $history, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $this->authorizeHistory($history, $store->id);

        if ($history->error_file_path) {
            Storage::disk('local')->delete($history->error_file_path);
        }

        $history->delete();

        return redirect()
            ->route('store.admin.import-history.index', ['store_slug' => $store->slug])
            ->with('success', 'Import history deleted successfully.');
    }

    private function authorizeHistory(ImportHistory $history, int $storeId): void
    {
        if ($history->store_id !== $storeId) {
            abort(403, 'Unauthorized import history access.');
        }
    }
}
