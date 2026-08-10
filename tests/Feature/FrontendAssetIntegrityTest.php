<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class FrontendAssetIntegrityTest extends TestCase
{
    private function frontendFiles(): array
    {
        $paths = [
            resource_path('views'),
            resource_path('js'),
        ];

        $files = [];

        foreach ($paths as $path) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

            foreach ($iterator as $file) {
                /** @var SplFileInfo $file */
                $filename = $file->getFilename();

                if ($file->isFile() && (
                    str_ends_with($filename, '.blade.php')
                    || str_ends_with($filename, '.js')
                    || str_ends_with($filename, '.vue')
                )) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    public function test_no_duplicate_alpine_cdn_references_exist(): void
    {
        $violations = [];
        $cdnPatterns = [
            'unpkg.com/alpinejs',
            'cdn.jsdelivr.net/npm/alpinejs',
            'alpinejs@',
            'alpine.min.js',
        ];

        foreach ($this->frontendFiles() as $file) {
            $contents = file_get_contents($file);

            foreach ($cdnPatterns as $pattern) {
                if (stripos($contents, $pattern) !== false) {
                    $violations[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file) . " contains {$pattern}";
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_alpine_is_started_once_from_vite_entrypoint(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        $this->assertSame(1, substr_count($appJs, "import Alpine from 'alpinejs'"));
        $this->assertSame(1, substr_count($appJs, 'Alpine.start()'));
    }

    public function test_x_collapse_is_not_used_without_plugin(): void
    {
        $violations = [];

        foreach ($this->frontendFiles() as $file) {
            $contents = file_get_contents($file);

            if (str_contains($contents, 'x-collapse')) {
                $violations[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_livewire_assets_are_not_loaded_by_frontend_layouts(): void
    {
        $violations = [];
        $patterns = [
            '@livewireStyles',
            '@livewireScripts',
            '<livewire:',
            'wire:model',
            'wire:click',
            'wire:submit',
            'wire:navigate',
        ];

        foreach ($this->frontendFiles() as $file) {
            $contents = file_get_contents($file);

            foreach ($patterns as $pattern) {
                if (str_contains($contents, $pattern)) {
                    $violations[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file) . " contains {$pattern}";
                }
            }
        }

        $this->assertSame([], $violations);
    }
}
