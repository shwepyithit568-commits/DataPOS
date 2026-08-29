<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\DatabaseBackupService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(Request $request, StoreContext $context, DatabaseBackupService $backups): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $tab = $request->query('tab', 'backups');
        $files = $backups->list();
        $totalSize = array_sum(array_column($files, 'size'));
        $lastBackup = $files[0]['created_at'] ?? null;
        $driver = DB::connection()->getDriverName();
        $databaseName = DB::connection()->getDatabaseName();

        $stats = [
            'total_backups' => count($files),
            'total_size'    => $totalSize,
            'last_backup'   => $lastBackup,
            'driver'        => strtoupper($driver),
            'database_name' => basename($databaseName),
        ];

        return view('admin.backups.index', compact('store', 'files', 'totalSize', 'lastBackup', 'tab', 'stats', 'driver'));
    }

    /**
     * Create a new database backup snapshot (SQL or SQLite).
     */
    public function store(Request $request, StoreContext $context, DatabaseBackupService $backups): RedirectResponse
    {
        $store = $context->getStore();
        $format = $request->input('format', 'sql');

        try {
            $result = $backups->create('manual', $format);
            $sizeKb = round($result['size'] / 1024, 1);

            AuditLog::write(
                $store->id,
                'database_backup_created',
                'backup',
                null,
                ['filename' => $result['filename'], 'format' => $format, 'size_kb' => $sizeKb],
                auth()->id(),
                request()->ip()
            );

            return redirect()
                ->route('store.admin.backups.index', ['store_slug' => $store->slug])
                ->with('success', "Database backup created successfully: {$result['filename']} ({$sizeKb} KB)");
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('store.admin.backups.index', ['store_slug' => $store->slug])
                ->with('error', 'Database backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download an existing backup file.
     */
    public function download(string $store_slug, string $file, StoreContext $context, DatabaseBackupService $backups): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $context->getStore();

        if (! $backups->exists($file)) {
            abort(404, 'Backup file not found.');
        }

        if (str_ends_with($file, '.zip')) {
            $contentType = 'application/zip';
        } elseif (str_ends_with($file, '.sqlite')) {
            $contentType = 'application/x-sqlite3';
        } else {
            $contentType = 'application/sql';
        }

        $fullPath = Storage::disk('local')->path($backups->path($file));

        return response()->download($fullPath, basename($file), [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Delete an existing backup file.
     */
    public function destroy(string $store_slug, string $file, StoreContext $context, DatabaseBackupService $backups): RedirectResponse
    {
        $store = $context->getStore();

        if (! $backups->exists($file)) {
            abort(404, 'Backup file not found.');
        }

        $backups->delete($file);

        AuditLog::write(
            $store->id,
            'database_backup_deleted',
            'backup',
            null,
            ['filename' => $file],
            auth()->id(),
            request()->ip()
        );

        return redirect()
            ->route('store.admin.backups.index', ['store_slug' => $store->slug])
            ->with('success', 'Backup file deleted successfully. (အရန်ဖိုင်အား ပယ်ဖျက်ပြီးပါပြီ)');
    }

    /**
     * Restore database from an existing backup file in the storage list.
     */
    public function restore(Request $request, string $store_slug, StoreContext $context, DatabaseBackupService $backups): RedirectResponse
    {
        $store = $context->getStore();
        $validated = $request->validate([
            'filename' => ['required', 'string'],
        ]);

        $filename = $validated['filename'];

        if (! $backups->exists($filename)) {
            return back()->with('error', 'ရွေးချယ်ထားသော Backup ဖိုင် မရှိတော့ပါ (Backup file not found).');
        }

        try {
            $backups->restore($filename);

            AuditLog::write(
                $store->id,
                'database_restored_from_file',
                'database',
                null,
                ['filename' => $filename],
                auth()->id(),
                request()->ip()
            );

            return redirect()
                ->route('store.admin.backups.index', ['store_slug' => $store->slug, 'tab' => 'restore'])
                ->with('success', "Database and files restored successfully from {$filename}! (ဒေတာများနှင့် ပုံများအားလုံး မူလအတိုင်း အောင်မြင်စွာ ပြန်လည်ရယူပြီးပါပြီ)");
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore database from an uploaded backup file (.zip, .sql or .sqlite).
     */
    public function uploadRestore(Request $request, string $store_slug, StoreContext $context, DatabaseBackupService $backups): RedirectResponse
    {
        $store = $context->getStore();

        $request->validate([
            'backup_file' => ['required', 'file', 'max:204800'], // Max 200MB
        ]);

        $file = $request->file('backup_file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, ['zip', 'sql', 'sqlite', 'db'])) {
            return back()->with('error', 'ခွင့်ပြုမထားသော ဖိုင်အမျိုးအစားဖြစ်ပါသည်။ .zip, .sql သို့မဟုတ် .sqlite ဖိုင်သာ တင်သွင်းနိုင်ပါသည် (.zip, .sql or .sqlite only)');
        }

        try {
            $backups->restoreFromUploadedFile($file);

            AuditLog::write(
                $store->id,
                'database_restored_from_upload',
                'database',
                null,
                ['original_filename' => $file->getClientOriginalName(), 'size' => $file->getSize()],
                auth()->id(),
                request()->ip()
            );

            return redirect()
                ->route('store.admin.backups.index', ['store_slug' => $store->slug, 'tab' => 'restore'])
                ->with('success', "Database restored successfully from uploaded file ({$file->getClientOriginalName()})! (တင်သွင်းထားသော ဖိုင်မှ ဒေတာများ ပြန်လည်ရယူပြီးပါပြီ)");
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Restore from file failed: ' . $e->getMessage());
        }
    }
}
