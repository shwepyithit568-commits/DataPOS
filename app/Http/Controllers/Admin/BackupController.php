<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(StoreContext $context, DatabaseBackupService $backups): View
    {
        $store = $context->getStore();

        $files = $backups->list();
        $totalSize = array_sum(array_column($files, 'size'));
        $lastBackup = $files[0]['created_at'] ?? null;

        return view('admin.backups.index', compact('store', 'files', 'totalSize', 'lastBackup'));
    }

    public function store(StoreContext $context, DatabaseBackupService $backups): RedirectResponse
    {
        $store = $context->getStore();

        try {
            $result = $backups->create('manual');
            $sizeKb = round($result['size'] / 1024, 1);

            return redirect()
                ->route('store.admin.backups.index', ['store_slug' => $store->slug])
                ->with('success', __('messages.backup_created', ['file' => $result['filename'], 'size' => $sizeKb]));
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('store.admin.backups.index', ['store_slug' => $store->slug])
                ->with('error', __('messages.backup_failed'));
        }
    }

    public function download(string $store_slug, string $file, StoreContext $context, DatabaseBackupService $backups): StreamedResponse
    {
        $context->getStore();

        if (!$backups->exists($file)) {
            abort(404, 'Backup file not found.');
        }

        return Storage::disk('local')->download($backups->path($file), basename($file), [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function destroy(string $store_slug, string $file, StoreContext $context, DatabaseBackupService $backups): RedirectResponse
    {
        $store = $context->getStore();

        if (!$backups->exists($file)) {
            abort(404, 'Backup file not found.');
        }

        $backups->delete($file);

        return redirect()
            ->route('store.admin.backups.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.backup_deleted'));
    }
}
