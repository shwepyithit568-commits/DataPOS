<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StorefrontSetting;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-time pass over the existing public storage images: downscales oversized
 * files and re-encodes photos to WebP, then rewrites every DB reference whose
 * extension changed (e.g. products/abc.jpg → products/abc.webp).
 *
 * Run with: php artisan images:optimize
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize';

    protected $description = 'Compress existing storefront images (WebP) and update DB paths';

    /** Directory → longest-side max dimension, mirroring each upload form. */
    private const DIR_MAX_DIM = [
        'products' => 1600,
        'categories' => 1200,
        'brands' => 800,
        'banners' => 1600,
        'blog' => 1400,
        'chat-icons' => 512,
    ];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $total = 0;
        $optimized = 0;

        foreach (self::DIR_MAX_DIM as $dir => $maxDim) {
            foreach ($disk->files($dir) as $rel) {
                $result = $this->processFile($disk->path($rel), $rel, $maxDim);
                $total++;
                $optimized += $result;
            }
        }

        // Variant images live one level deeper.
        foreach ($disk->files('products/variants') as $rel) {
            $result = $this->processFile($disk->path($rel), $rel, 1200);
            $total++;
            $optimized += $result;
        }

        $this->info("Scanned {$total} files, rewrote {$optimized} path(s).");

        return self::SUCCESS;
    }

    private function processFile(string $fullPath, string $rel, int $maxDim): int
    {
        $newRel = ImageOptimizer::optimizeFile($fullPath, $maxDim, 82);

        if ($newRel !== null && $newRel !== $rel) {
            $this->updateReferences($rel, $newRel);
            $this->line("  {$rel} -> {$newRel}");

            return 1;
        }

        return 0;
    }

    /** Rewrite every column / JSON entry that pointed at the old path. */
    private function updateReferences(string $oldRel, string $newRel): void
    {
        Product::where('image_path', $oldRel)->update(['image_path' => $newRel]);
        ProductImage::where('image_path', $oldRel)->update(['image_path' => $newRel]);
        ProductVariant::where('image_path', $oldRel)->update(['image_path' => $newRel]);
        Category::where('image_path', $oldRel)->update(['image_path' => $newRel]);
        Brand::where('logo_path', $oldRel)->update(['logo_path' => $newRel]);
        HomeBanner::where('image_path', $oldRel)->update(['image_path' => $newRel]);
        Post::where('image_path', $oldRel)->update(['image_path' => $newRel]);
        StorefrontSetting::where('logo_path', $oldRel)->update(['logo_path' => $newRel]);
        StorefrontSetting::where('storefront_logo_path', $oldRel)->update(['storefront_logo_path' => $newRel]);
        StorefrontSetting::where('admin_logo_path', $oldRel)->update(['admin_logo_path' => $newRel]);
        StorefrontSetting::where('favicon_path', $oldRel)->update(['favicon_path' => $newRel]);
        StorefrontSetting::where('chat_button_icon_path', $oldRel)->update(['chat_button_icon_path' => $newRel]);

        foreach (StorefrontSetting::all() as $setting) {
            $channels = $setting->chat_channels;
            if (! is_array($channels)) {
                continue;
            }

            $changed = false;
            foreach ($channels as &$channel) {
                if (($channel['icon_path'] ?? null) === $oldRel) {
                    $channel['icon_path'] = $newRel;
                    $changed = true;
                }
            }
            unset($channel);

            if ($changed) {
                $setting->chat_channels = array_values($channels);
                $setting->save();
            }
        }
    }
}
