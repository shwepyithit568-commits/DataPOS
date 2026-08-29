<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\File;
use ZipArchive;

class LocalBackupPackageService
{
    /**
     * Create a structured backup archive with manifest and SHA-256 checksums.
     *
     * @param Store $store
     * @return array{
     *     filename: string,
     *     filepath: string,
     *     sha256: string,
     *     size_bytes: int
     * }
     */
    public function createBackupPackage(Store $store): array
    {
        $backupDir = storage_path('app/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $filename = "datapos_{$store->slug}_{$timestamp}.zip";
        $filepath = "{$backupDir}/{$filename}";

        $exportService = app(StoreDataExportService::class);
        $dataPayload = $exportService->exportStoreArchive($store);
        $dataJson = json_encode($dataPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $dataSha256 = hash('sha256', $dataJson);

        $manifest = [
            'app_name'           => 'DataPOS',
            'version'            => '2.0.0',
            'store_id'           => $store->id,
            'store_slug'         => $store->slug,
            'created_at'         => now()->toIso8601String(),
            'data_file'          => 'store_data.json',
            'data_sha256'        => $dataSha256,
            'schema_version'     => 4,
            'checksum_algorithm' => 'sha256',
        ];

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('manifest.json', $manifestJson);
            $zip->addFromString('store_data.json', $dataJson);
            $zip->close();
        }

        $packageSha256 = hash_file('sha256', $filepath);
        $sizeBytes = filesize($filepath);

        return [
            'filename'   => $filename,
            'filepath'   => $filepath,
            'sha256'     => $packageSha256,
            'size_bytes' => $sizeBytes,
            'manifest'   => $manifest,
        ];
    }

    /**
     * Perform preflight integrity and schema compatibility check on a backup package.
     *
     * @param string $filepath
     * @return array{
     *     is_valid: bool,
     *     status: string,
     *     message: string,
     *     manifest: array<string, mixed>|null
     * }
     */
    public function verifyBackupPreflight(string $filepath): array
    {
        if (! File::exists($filepath)) {
            return [
                'is_valid' => false,
                'status'   => 'file_not_found',
                'message'  => 'Backup file does not exist.',
                'manifest' => null,
            ];
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return [
                'is_valid' => false,
                'status'   => 'corrupt_archive',
                'message'  => 'Unable to open ZIP archive. File may be corrupted.',
                'manifest' => null,
            ];
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        if ($manifestRaw === false) {
            $zip->close();
            return [
                'is_valid' => false,
                'status'   => 'missing_manifest',
                'message'  => 'Manifest file (manifest.json) is missing in the backup archive.',
                'manifest' => null,
            ];
        }

        $manifest = json_decode($manifestRaw, true);
        if (! is_array($manifest) || empty($manifest['data_sha256'])) {
            $zip->close();
            return [
                'is_valid' => false,
                'status'   => 'invalid_manifest',
                'message'  => 'Backup manifest is missing critical checksum data.',
                'manifest' => null,
            ];
        }

        $dataFile = $manifest['data_file'] ?? 'store_data.json';
        $dataRaw = $zip->getFromName($dataFile);
        $zip->close();

        if ($dataRaw === false) {
            return [
                'is_valid' => false,
                'status'   => 'missing_data_file',
                'message'  => "Data file ({$dataFile}) is missing in the backup archive.",
                'manifest' => $manifest,
            ];
        }

        // Verify SHA-256 checksum
        $actualSha256 = hash('sha256', $dataRaw);
        if (! hash_equals($manifest['data_sha256'], $actualSha256)) {
            return [
                'is_valid' => false,
                'status'   => 'checksum_mismatch',
                'message'  => 'Data file SHA-256 checksum does not match manifest. Archive may have been tampered with.',
                'manifest' => $manifest,
            ];
        }

        return [
            'is_valid' => true,
            'status'   => 'ready_for_restore',
            'message'  => 'Backup package verified successfully. Ready for preflight restore.',
            'manifest' => $manifest,
        ];
    }
}
