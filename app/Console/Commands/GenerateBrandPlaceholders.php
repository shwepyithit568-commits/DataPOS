<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Generate premium placeholder logos for brands that have no logo yet.
 *
 * Design: soft pastel gradient background → rounded-rectangle gradient badge
 * with bold white initials → brand name below. Looks polished at the admin
 * thumbnail size (h-10 w-10 object-contain rounded border bg-white).
 *
 * Run with: php artisan brands:placeholders
 */
class GenerateBrandPlaceholders extends Command
{
    protected $signature = 'brands:placeholders';

    protected $description = 'Generate premium branded placeholder logos for brands without a logo';

    private const SIZE = 600;

    /**
     * Each palette entry is [topGradient, bottomGradient] — deterministic per brand.
     * Colors chosen for vibrancy at small sizes on white admin backgrounds.
     */
    private const PALETTE = [
        ['#8b5cf6', '#6d28d9'], // violet
        ['#ec4899', '#be185d'], // pink
        ['#0ea5e9', '#0369a1'], // sky
        ['#14b8a6', '#0d9488'], // teal
        ['#f59e0b', '#d97706'], // amber
        ['#ef4444', '#dc2626'], // red
        ['#6366f1', '#4338ca'], // indigo
        ['#10b981', '#059669'], // emerald
        ['#d946ef', '#a21caf'], // fuchsia
        ['#3b82f6', '#2563eb'], // blue
        ['#eab308', '#ca8a04'], // yellow
        ['#f43f5e', '#e11d48'], // rose
    ];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $brands = Brand::orderBy('name')->get();

        if ($brands->isEmpty()) {
            $this->error('No brands found.');

            return self::FAILURE;
        }

        $generated = 0;
        $skipped = 0;

        foreach ($brands as $brand) {
            $rel = 'brands/brand-' . $brand->id . '.webp';

            if (!empty($brand->logo_path) && $disk->exists($brand->logo_path)) {
                $skipped++;
                continue;
            }

            $full = $disk->path($rel);

            if ($this->renderLogo($full, $brand->name)) {
                $brand->update(['logo_path' => $rel]);
                $generated++;
                $this->line("  ✓ [{$brand->id}] {$brand->name}");
            } else {
                $this->error("  ✗ Failed to render #{$brand->id} {$brand->name}");
            }
        }

        $this->info("Done. {$generated} brand logos generated, {$skipped} already had logos.");

        return self::SUCCESS;
    }

    private function renderLogo(string $fullPath, string $name): bool
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
            return false;
        }

        $im = imagecreatetruecolor(self::SIZE, self::SIZE);
        if ($im === false) {
            return false;
        }

        // ── 1. Soft gradient background (slate-50 → white) ──
        for ($y = 0; $y < self::SIZE; $y++) {
            $t = $y / (self::SIZE - 1);
            $c = $this->lerpColor('#f1f5f9', '#ffffff', $t);
            $lineColor = imagecolorallocate($im, $c[0], $c[1], $c[2]);
            imageline($im, 0, $y, self::SIZE, $y, $lineColor);
        }

        // ── 2. Gradient-filled rounded-rectangle badge ──
        $initials = $this->initials($name);
        [$topHex, $bottomHex] = $this->colorPair($name);
        [$tR, $tG, $tB] = $this->hexToRgb($topHex);
        [$bR, $bG, $bB] = $this->hexToRgb($bottomHex);

        $badgeW = (int) (self::SIZE * 0.48);
        $badgeH = (int) (self::SIZE * 0.48);
        $badgeR = (int) ($badgeW * 0.26);
        $cx = (int) (self::SIZE / 2);
        $cy = (int) (self::SIZE * 0.40);
        $x1 = $cx - (int) ($badgeW / 2);
        $y1 = $cy - (int) ($badgeH / 2);
        $x2 = $x1 + $badgeW;
        $y2 = $y1 + $badgeH;

        // Draw gradient rows first (full rectangle area)
        for ($py = $y1; $py < $y2; $py++) {
            $rowT = ($py - $y1) / max($badgeH - 1, 1);
            $cr = (int) round($tR + ($bR - $tR) * $rowT);
            $cg = (int) round($tG + ($bG - $tG) * $rowT);
            $cb = (int) round($tB + ($bB - $tB) * $rowT);
            $rowColor = imagecolorallocate($im, $cr, $cg, $cb);
            imageline($im, $x1, $py, $x2, $py, $rowColor);
        }

        // Clip corners: draw bg-colored quarter-circles at each corner
        $bgSample = imagecolorallocate($im, 241, 245, 249); // match gradient top
        // Top-left corner — circle centered at (x1+r, y1+r)
        imagefilledellipse($im, $x1 + $badgeR, $y1 + $badgeR, $badgeR * 2, $badgeR * 2, $bgSample);
        // Top-right corner — circle centered at (x2-r, y1+r)
        imagefilledellipse($im, $x2 - $badgeR, $y1 + $badgeR, $badgeR * 2, $badgeR * 2, $bgSample);
        // Bottom-left corner — circle centered at (x1+r, y2-r)
        imagefilledellipse($im, $x1 + $badgeR, $y2 - $badgeR, $badgeR * 2, $badgeR * 2, $bgSample);
        // Bottom-right corner — circle centered at (x2-r, y2-r)
        imagefilledellipse($im, $x2 - $badgeR, $y2 - $badgeR, $badgeR * 2, $badgeR * 2, $bgSample);

        // ── 3. Subtle inner highlight (top-left light reflection) ──
        $highlight = imagecolorallocatealpha($im, 255, 255, 255, 80);
        imagefilledellipse($im, $cx - (int) ($badgeW * 0.15), $cy - (int) ($badgeH * 0.18), (int) ($badgeW * 0.55), (int) ($badgeH * 0.35), $highlight);

        // ── 4. Bold white initials centered in badge ──
        $font = resource_path('assets/fonts/Outfit/Outfit-SemiBold.ttf');
        $initialsSize = (int) ($badgeH * 0.38);
        $white = imagecolorallocate($im, 255, 255, 255);
        $box = imagettfbbox($initialsSize, 0, $font, $initials);
        if ($box !== false) {
            $tw = abs($box[2] - $box[0]);
            $th = abs($box[7] - $box[1]);
            $tx = $cx - (int) ($tw / 2);
            $ty = $cy - (int) ($th / 2) + abs($box[1]);
            imagettftext($im, $initialsSize, 0, $tx, $ty, $white, $font, $initials);
        }

        // ── 5. Brand name below the badge ──
        $nameSize = 42;
        $nameColor = imagecolorallocate($im, 30, 41, 59); // slate-800
        $this->drawCenteredText($im, $name, $font, $nameSize, $nameColor, (int) (self::SIZE * 0.74));

        // ── 6. Subtle bottom accent dot (brand color) ──
        $accentW = (int) (self::SIZE * 0.12);
        $accentY = (int) (self::SIZE * 0.88);
        $accentColor = imagecolorallocatealpha($im, $tR, $tG, $tB, 70);
        imagefilledellipse($im, $cx, $accentY, $accentW, (int) ($accentW * 0.3), $accentColor);

        $result = imagewebp($im, $fullPath, 85);
        imagedestroy($im);

        return $result;
    }

    private function initials(string $name): string
    {
        // Take up to two leading word initials (numeric names like "168" → "1")
        $words = preg_split('/[\s\-_]+/', trim($name)) ?: [];
        $letters = '';
        foreach ($words as $word) {
            $letters .= mb_substr($word, 0, 1);
            if (mb_strlen($letters) >= 2) {
                break;
            }
        }

        return mb_strtoupper($letters ?: mb_substr($name, 0, 1));
    }

    private function colorPair(string $name): array
    {
        $hash = 0;
        foreach (str_split(mb_strtoupper($name)) as $ch) {
            $hash = ($hash * 31 + ord($ch)) % 100000;
        }

        return self::PALETTE[$hash % count(self::PALETTE)];
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

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

    private function drawCenteredText(\GdImage $im, string $text, string $font, int $fontSize, int $color, int $y): void
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);
        if ($box === false) {
            return;
        }

        $width = abs($box[2] - $box[0]);
        $x = (int) ((self::SIZE - $width) / 2);
        $baseline = $y + abs($box[7] - $box[1]);

        imagettftext($im, $fontSize, 0, $x, $baseline, $color, $font, $text);
    }
}
