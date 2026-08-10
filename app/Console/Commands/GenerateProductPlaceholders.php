<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generate branded placeholder cards for products that have no image yet.
 * The owner plans to photograph the real inventory later and upload it, so
 * these are clean, on-brand placeholders rather than stock photos.
 *
 * Cards are 900×900 WebP with a category-colored gradient, the product name
 * and a subtle "Photo coming soon" hint.
 *
 * Run with: php artisan products:placeholders
 */
class GenerateProductPlaceholders extends Command
{
    protected $signature = 'products:placeholders {--store= : Restrict to a specific store ID (defaults to the first store)}';

    protected $description = 'Generate branded placeholder images for products without an image';

    private const SIZE = 900;

    /** Category name (lowercased fragment) → [topColor, bottomColor]. */
    private const CATEGORY_COLORS = [
        'mobile' => ['#8b5cf6', '#4f46e5'],
        'accessor' => ['#f472b6', '#db2777'],
        'cctv' => ['#f87171', '#b91c1c'],
        'computer' => ['#60a5fa', '#2563eb'],
        'fashion' => ['#2dd4bf', '#0f766e'],
        'network' => ['#fbbf24', '#d97706'],
    ];

    private const FALLBACK_COLORS = ['#818cf8', '#4338ca'];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $products = Product::where('store_id', $this->optionStoreId())
            ->with('category')
            ->orderBy('sku')
            ->get();

        if ($products->isEmpty()) {
            $this->error('No products found.');

            return self::FAILURE;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($products as $product) {
            // Regenerate when the file is missing too, so running this on a
            // fresh server (where storage files were not committed) still works.
            $rel = 'products/placeholder-' . $product->id . '.webp';
            if (!empty($product->image_path) && $disk->exists($product->image_path)) {
                $skipped++;
                continue;
            }

            $full = $disk->path($rel);

            if (!$this->renderCard($full, $product->name, $product->category?->name)) {
                $this->error("Failed to render placeholder for #{$product->id} {$product->name}");
                continue;
            }

            $product->update(['image_path' => $rel]);
            $updated++;
            $this->line("  ✓ #{$product->id} {$product->name}");
        }

        $this->info("Done. {$updated} placeholders generated, {$skipped} already had images.");

        return self::SUCCESS;
    }

    private function optionStoreId(): int
    {
        // Product images are attached per store; default to the first store
        // (single-store deployments) or the store given via --store.
        return (int) $this->option('store') ?: Product::orderBy('store_id')->value('store_id') ?: 1;
    }

    /**
     * Draw the placeholder card. Returns false when GD rendering fails.
     */
    private function renderCard(string $fullPath, string $name, ?string $categoryName): bool
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
            return false;
        }

        $im = imagecreatetruecolor(self::SIZE, self::SIZE);
        if ($im === false) {
            return false;
        }

        [$top, $bottom] = $this->categoryColors($categoryName);

        // Vertical gradient background
        for ($y = 0; $y < self::SIZE; $y++) {
            $t = $y / (self::SIZE - 1);
            $color = $this->imageColor($im, $this->lerpColor($top, $bottom, $t));
            imageline($im, 0, $y, self::SIZE, $y, $color);
        }

        // Soft decorative highlight circle (top-right)
        $highlight = imagecolorallocatealpha($im, 255, 255, 255, 46);
        imagefilledellipse($im, (int) (self::SIZE * 0.82), (int) (self::SIZE * 0.14), 480, 480, $highlight);

        $nameFont = resource_path('assets/fonts/Outfit/Outfit-SemiBold.ttf');
        $hintFont = resource_path('assets/fonts/Outfit/Outfit-Regular.ttf');
        $white = imagecolorallocate($im, 255, 255, 255);

        // Category pill
        if ($categoryName !== null && $categoryName !== '') {
            $this->drawPill($im, $categoryName, (int) (self::SIZE * 0.12));
        }

        // Product name, wrapped and centered
        $lines = $this->wrapText($name, $nameFont, 56, (int) (self::SIZE * 0.82));
        $lineHeight = 72;
        $blockHeight = count($lines) * $lineHeight;
        $startY = (int) (self::SIZE * 0.42) - (int) ($blockHeight / 2);

        foreach ($lines as $i => $line) {
            $this->drawCenteredText($im, $line, $nameFont, 56, $white, $startY + $i * $lineHeight);
        }

        // "Photo coming soon" hint
        $hintColor = imagecolorallocatealpha($im, 255, 255, 255, 80);
        $this->drawCenteredText($im, 'Photo coming soon', $hintFont, 24, $hintColor, (int) (self::SIZE * 0.84));

        $result = imagewebp($im, $fullPath, 82);
        imagedestroy($im);

        return $result;
    }

    /**
     * @return array{string, string}
     */
    private function categoryColors(?string $categoryName): array
    {
        if ($categoryName !== null && $categoryName !== '') {
            $lower = mb_strtolower($categoryName);
            foreach (self::CATEGORY_COLORS as $key => $colors) {
                if (Str::contains($lower, $key)) {
                    return $colors;
                }
            }
        }

        return self::FALLBACK_COLORS;
    }

    /**
     * @return array{int, int, int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @return array{int, int, int}
     */
    private function lerpColor(string $from, string $to, float $t): array
    {
        $a = $this->hexToRgb($from);
        $b = $this->hexToRgb($to);

        return [
            (int) round($a[0] + ($b[0] - $a[0]) * $t),
            (int) round($a[1] + ($b[1] - $a[1]) * $t),
            (int) round($a[2] + ($b[2] - $a[2]) * $t),
        ];
    }

    /**
     * @param array{int, int, int} $rgb
     */
    private function imageColor(\GdImage $im, array $rgb): int
    {
        return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    }

    private function drawPill(\GdImage $im, string $text, int $y): void
    {
        $font = resource_path('assets/fonts/Outfit/Outfit-SemiBold.ttf');
        $fontSize = 22;
        $padding = 26;

        $box = imagettfbbox($fontSize, 0, $font, mb_strtoupper($text));
        $width = abs($box[2] - $box[0]) + $padding * 2;
        $height = 56;
        $x = (int) ((self::SIZE - $width) / 2);
        $cy = $y + (int) ($height / 2);

        $fill = imagecolorallocatealpha($im, 255, 255, 255, 56);
        $this->fillRoundedRect($im, $x, $y, $x + $width, $y + $height, 28, $fill);

        $white = imagecolorallocate($im, 255, 255, 255);
        $this->drawCenteredText($im, mb_strtoupper($text), $font, $fontSize, $white, $cy - (int) ($fontSize / 2), true);
    }

    private function fillRoundedRect(\GdImage $im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, string $font, int $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($this->textWidth($candidate, $font, $fontSize) <= $maxWidth || $current === '') {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }

    private function textWidth(string $text, string $font, int $fontSize): int
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return 0;
        }

        return abs($box[2] - $box[0]);
    }

    private function drawCenteredText(\GdImage $im, string $text, string $font, int $fontSize, int $color, int $y, bool $absoluteY = false): void
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return;
        }

        $width = abs($box[2] - $box[0]);
        $height = abs($box[7] - $box[1]);
        $x = (int) ((self::SIZE - $width) / 2);
        $y = $absoluteY ? $y : $y + $height;

        imagettftext($im, $fontSize, 0, $x, $y, $color, $font, $text);
    }
}
