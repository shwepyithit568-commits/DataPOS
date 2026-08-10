<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Generate branded placeholder cards for categories that have no image yet.
 *
 * Main categories get full-size gradient cards with their icon and name.
 * Sub-categories get lighter-toned cards with the parent category context.
 *
 * Run with: php artisan categories:placeholders
 */
class GenerateCategoryPlaceholders extends Command
{
    protected $signature = 'categories:placeholders';

    protected $description = 'Generate branded placeholder images for categories without an image';

    private const SIZE = 600;

    /** Category name fragment → [topColor, bottomColor, accentColor]. */
    private const PALETTE = [
        'accessor' => ['#a78bfa', '#7c3aed', '#c4b5fd'], // violet
        'cctv'     => ['#fb7185', '#e11d48', '#fda4af'], // rose
        'electronic' => ['#38bdf8', '#0284c7', '#7dd3fc'], // sky
        'fashion'  => ['#2dd4bf', '#0d9488', '#5eead4'], // teal
        'spare'    => ['#fbbf24', '#d97706', '#fde68a'], // amber
    ];

    private const MAIN_PARENT_FALLBACK = ['#818cf8', '#4338ca', '#a5b4fc'];
    private const SUB_FALLBACK = ['#94a3b8', '#64748b', '#cbd5e1'];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $categories = Category::with('parent')->orderBy('name')->get();

        if ($categories->isEmpty()) {
            $this->error('No categories found.');

            return self::FAILURE;
        }

        $generated = 0;
        $skipped = 0;

        foreach ($categories as $cat) {
            $isParent = $cat->parent_id === null;
            $rel = 'categories/cat-' . $cat->id . '.webp';

            // Skip if image already exists on disk
            if (!empty($cat->image_path) && $disk->exists($cat->image_path)) {
                $skipped++;
                continue;
            }

            $full = $disk->path($rel);
            $subCount = $isParent ? $cat->children()->count() : 0;

            if ($this->renderCard($full, $cat->name, $isParent, $subCount, $cat->parent?->name)) {
                $cat->update(['image_path' => $rel]);
                $generated++;
                $this->line("  ✓ [{$cat->id}] {$cat->name}" . ($isParent ? " ({$subCount} sub-categories)" : ''));
            } else {
                $this->error("  ✗ Failed to render #{$cat->id} {$cat->name}");
            }
        }

        $this->info("Done. {$generated} category images generated, {$skipped} already had images.");

        return self::SUCCESS;
    }

    private function renderCard(
        string $fullPath,
        string $name,
        bool $isParent,
        int $subCount,
        ?string $parentName,
    ): bool {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
            return false;
        }

        $im = imagecreatetruecolor(self::SIZE, self::SIZE);
        if ($im === false) {
            return false;
        }

        [$top, $bottom, $accent] = $this->resolveColors($name, $isParent);

        // Vertical gradient background
        for ($y = 0; $y < self::SIZE; $y++) {
            $t = $y / (self::SIZE - 1);
            $color = $this->imageColor($im, $this->lerpColor($top, $bottom, $t));
            imageline($im, 0, $y, self::SIZE, $y, $color);
        }

        // Decorative circles
        $highlight = $this->imageColorAlpha($im, 255, 255, 255, 50);
        imagefilledellipse($im, (int) (self::SIZE * 0.78), (int) (self::SIZE * 0.18), 420, 420, $highlight);

        $subtle = $this->imageColorAlpha($im, 255, 255, 255, 25);
        imagefilledellipse($im, (int) (self::SIZE * 0.22), (int) (self::SIZE * 0.82), 300, 300, $subtle);

        $white = imagecolorallocate($im, 255, 255, 255);

        // --- Main category: show sub-count pill ---
        if ($isParent && $subCount > 0) {
            $pillText = "{$subCount} sub-categories";
            $this->drawPill($im, $pillText, (int) (self::SIZE * 0.25), $accent);
        }

        // --- Category name, centered ---
        $nameFont = resource_path('assets/fonts/Outfit/Outfit-SemiBold.ttf');
        $nameSize = $isParent ? 48 : 40;
        $lines = $this->wrapText($name, $nameFont, $nameSize, (int) (self::SIZE * 0.80));
        $lineHeight = (int) ($nameSize * 1.3);
        $blockHeight = count($lines) * $lineHeight;
        $startY = (int) (self::SIZE * 0.46) - (int) ($blockHeight / 2);

        foreach ($lines as $i => $line) {
            $this->drawCenteredText($im, $line, $nameFont, $nameSize, $white, $startY + $i * $lineHeight);
        }

        // --- Sub-category: show parent name hint ---
        if (!$isParent && !empty($parentName)) {
            $hintFont = resource_path('assets/fonts/Outfit/Outfit-Regular.ttf');
            $hintColor = $this->imageColorAlpha($im, 255, 255, 255, 90);
            $this->drawCenteredText($im, mb_strtoupper($parentName), $hintFont, 22, $hintColor, (int) (self::SIZE * 0.82));
        }

        // --- Bottom line: store name ---
        $bottomFont = resource_path('assets/fonts/Outfit/Outfit-Regular.ttf');
        $bottomColor = $this->imageColorAlpha($im, 255, 255, 255, 110);
        $this->drawCenteredText($im, 'ALINNTHIT MOBILE', $bottomFont, 16, $bottomColor, (int) (self::SIZE * 0.92));

        $result = imagewebp($im, $fullPath, 82);
        imagedestroy($im);

        return $result;
    }

    private function resolveColors(string $name, bool $isParent): array
    {
        $lower = mb_strtolower($name);
        foreach (self::PALETTE as $key => $colors) {
            if (str_contains($lower, $key)) {
                // Sub-categories use the lighter accent as top
                return $isParent ? $colors : [$colors[2], $colors[0], $colors[2]];
            }
        }

        return $isParent ? self::MAIN_PARENT_FALLBACK : self::SUB_FALLBACK;
    }

    // ─── Drawing helpers (shared with product placeholder) ───

    private function drawPill(\GdImage $im, string $text, int $y, string $accentColor): void
    {
        $font = resource_path('assets/fonts/Outfit/Outfit-SemiBold.ttf');
        $fontSize = 20;
        $padding = 24;

        $box = imagettfbbox($fontSize, 0, $font, mb_strtoupper($text));
        $width = abs($box[2] - $box[0]) + $padding * 2;
        $height = 48;
        $x = (int) ((self::SIZE - $width) / 2);

        // Pill background
        $rgb = $this->hexToRgb($accentColor);
        $fill = imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], 60);
        $this->fillRoundedRect($im, $x, $y, $x + $width, $y + $height, 24, $fill);

        $white = imagecolorallocate($im, 255, 255, 255);
        $this->drawCenteredText($im, mb_strtoupper($text), $font, $fontSize, $white, $y + (int) (($height - $fontSize) / 2), true);
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

    private function imageColor(\GdImage $im, array $rgb): int
    {
        return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    }

    private function imageColorAlpha(\GdImage $im, int $r, int $g, int $b, int $alpha): int
    {
        // GD alpha: 0 = opaque, 127 = transparent — we invert for clarity
        return imagecolorallocatealpha($im, $r, $g, $b, 127 - $alpha);
    }

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

        return $box ? abs($box[2] - $box[0]) : 0;
    }

    private function drawCenteredText(\GdImage $im, string $text, string $font, int $fontSize, int $color, int $y, bool $absoluteY = false): void
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return;
        }

        $width = abs($box[2] - $box[0]);
        $x = (int) ((self::SIZE - $width) / 2);
        $y = $absoluteY ? $y + $fontSize : $y + abs($box[7] - $box[1]);

        imagettftext($im, $fontSize, 0, $x, $y, $color, $font, $text);
    }
}
