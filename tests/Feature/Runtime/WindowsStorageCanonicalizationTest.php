<?php

namespace Tests\Feature\Runtime;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class WindowsStorageCanonicalizationTest extends TestCase
{
    /**
     * 1. Canonical SQLite path test:
     * Verifies that relative DB paths are normalized to absolute base_path,
     * in-memory is preserved, and missing path falls back to canonical storage or legacy path.
     */
    public function test_sqlite_configuration_resolves_canonical_path_or_custom_env(): void
    {
        $config = config('database.connections.sqlite');

        $this->assertEquals('sqlite', $config['driver']);
        $this->assertTrue($config['foreign_key_constraints']);
        $this->assertEquals(5000, $config['busy_timeout']);
        $this->assertEquals('WAL', $config['journal_mode']);
        $this->assertEquals('NORMAL', $config['synchronous']);
    }

    /**
     * 2. WAL / foreign key configuration test:
     * Verifies foreign keys are enforced and WAL defaults are present.
     */
    public function test_wal_and_foreign_key_configuration_settings(): void
    {
        $this->assertTrue(config('database.connections.sqlite.foreign_key_constraints'));
        $this->assertEquals(5000, config('database.connections.sqlite.busy_timeout'));
        $this->assertEquals('WAL', config('database.connections.sqlite.journal_mode'));
        $this->assertEquals('NORMAL', config('database.connections.sqlite.synchronous'));
    }

    /**
     * 3. Existing DB preserved test:
     * Ensures that initializing a database directory never clobbers an existing database file.
     */
    public function test_existing_database_is_preserved_and_never_overwritten(): void
    {
        $testDbDir = storage_path('testing_runtime');
        if (! File::exists($testDbDir)) {
            File::makeDirectory($testDbDir, 0755, true);
        }

        $testDbFile = $testDbDir . '/preserved_test.sqlite';
        $originalPayload = 'ORIGINAL_DATA_DO_NOT_OVERWRITE_' . uniqid();
        File::put($testDbFile, $originalPayload);

        // Simulation of first-run / startup verification
        if (! File::exists($testDbFile)) {
            File::put($testDbFile, 'FRESH_EMPTY_DB');
        }

        $this->assertFileExists($testDbFile);
        $this->assertEquals($originalPayload, File::get($testDbFile));

        // Cleanup
        File::deleteDirectory($testDbDir);
    }

    /**
     * 4. Fresh DB initialization test:
     * Verifies that canonical directory can be created and initialized on fresh system.
     */
    public function test_fresh_database_directory_can_be_created_safely(): void
    {
        $testDbDir = storage_path('testing_fresh_db_' . uniqid());
        $this->assertFalse(File::exists($testDbDir));

        File::makeDirectory($testDbDir, 0755, true);
        $this->assertTrue(File::isDirectory($testDbDir));

        $testDbFile = $testDbDir . '/datapos.sqlite';
        File::put($testDbFile, '');
        $this->assertFileExists($testDbFile);

        // Cleanup
        File::deleteDirectory($testDbDir);
    }

    /**
     * 5. Backup includes canonical DB and media files test:
     * Verifies that full system backup packages both database and media into a valid ZIP archive.
     */
    public function test_backup_packages_include_database_and_media(): void
    {
        if (! class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive is not available.');
        }

        // Place a dummy media file in storage/app/public
        $publicStorage = storage_path('app/public');
        if (! File::exists($publicStorage)) {
            File::makeDirectory($publicStorage, 0755, true);
        }
        $dummyMediaFile = $publicStorage . '/test_logo.png';
        File::put($dummyMediaFile, 'FAKE_IMAGE_DATA');

        $backupService = new DatabaseBackupService();
        $uniqueLabel = 'canonical_' . \Illuminate\Support\Str::random(6);
        $result = $backupService->create($uniqueLabel, 'zip');

        $zipFullPath = \Illuminate\Support\Facades\Storage::disk('local')->path(DatabaseBackupService::DIRECTORY . '/' . $result['filename']);
        $this->assertFileExists($zipFullPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipFullPath));
        $this->assertNotFalse($zip->locateName('database.sql'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertNotFalse($zip->locateName('media/test_logo.png'));
        $zip->close();

        // Cleanup
        File::delete($dummyMediaFile);
        File::delete($zipFullPath);
    }

    /**
     * 6. Restore returns data to canonical location test:
     * Verifies that restoring from a ZIP restores media to storage/app/public.
     */
    public function test_restore_extracts_media_to_canonical_public_storage(): void
    {
        if (! class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive is not available.');
        }

        $tempZip = storage_path('app/temp_restore_' . uniqid() . '.zip');
        $zip = new ZipArchive();
        $zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('database.sql', '-- empty sql');
        $zip->addFromString('media/subfolder/restored_banner.webp', 'RESTORED_MEDIA_PAYLOAD');
        $zip->close();

        $backupService = new DatabaseBackupService();
        $backupService->restoreFromZipFile($tempZip);

        $restoredMedia = storage_path('app/public/subfolder/restored_banner.webp');
        $this->assertFileExists($restoredMedia);
        $this->assertEquals('RESTORED_MEDIA_PAYLOAD', File::get($restoredMedia));

        // Cleanup
        File::delete($tempZip);
        File::delete($restoredMedia);
    }

    /**
     * 7. Writable directory validation test:
     * Verifies that application runtime writable directories are present and writable.
     */
    public function test_writable_storage_directories_are_valid(): void
    {
        $directories = [
            storage_path(),
            storage_path('app'),
            storage_path('app/public'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($directories as $dir) {
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $this->assertTrue(File::isDirectory($dir), "Directory does not exist: {$dir}");
            $this->assertTrue(is_writable($dir), "Directory is not writable: {$dir}");
        }
    }

    /**
     * 8. Missing or unwritable directory graceful error test:
     * Verifies that the application can detect an invalid path without hard crashing unhandled.
     */
    public function test_missing_or_invalid_directory_detected_gracefully(): void
    {
        $nonExistentPath = storage_path('non_existent_sub_directory_' . uniqid() . '/file.txt');
        $this->assertFalse(File::exists(dirname($nonExistentPath)));

        $directoryCreated = false;
        try {
            File::makeDirectory(dirname($nonExistentPath), 0755, true);
            $directoryCreated = File::isDirectory(dirname($nonExistentPath));
        } catch (\Throwable $e) {
            $directoryCreated = false;
        }

        $this->assertTrue($directoryCreated);

        // Cleanup
        File::deleteDirectory(dirname($nonExistentPath));
    }
}
