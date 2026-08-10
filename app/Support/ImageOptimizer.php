<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Server-side image auto-compression (PHP GD — no extra packages).
 *
 * Storefront loads stay light on slow connections (Myanmar focus):
 * - Downscales oversized uploads to $maxDim (longest side).
 * - Photos (opaque) are re-encoded to WebP when GD supports it and it is
 *   actually smaller than the original.
 * - Small transparent PNGs (icons / logos, ≤ 512px) keep PNG so alpha is
 *   preserved — they are re-encoded with max compression only.
 * - Files already under the skip threshold are left completely untouched.
 */
class ImageOptimizer
{
    /** Files at or below this size are assumed light enough — no re-encode. */
    private const MIN_SKIP_BYTES = 300 * 1024;

    /**
     * Store an uploaded image with auto resize + re-encode.
     * Returns the final storage path (may differ in extension, e.g. .webp).
     */
    public static function store(UploadedFile $file, string $dir, int $maxDim = 1600, int $quality = 82): string
    {
        $path = $file->store($dir, 'public');
        $newRel = static::optimizeFile(Storage::disk('public')->path($path), $maxDim, $quality);

        if ($newRel !== null && $newRel !== $path) {
            Storage::disk('public')->delete($path);

            return $newRel;
        }

        return $path;
    }

    /**
     * Downscale + re-encode the image at $fullPath.
     * Returns the NEW relative storage path when the file was rewritten with
     * a different extension, or null when it was left untouched (or rewritten
     * in place with the same extension).
     */
    public static function optimizeFile(string $fullPath, int $maxDim = 1600, int $quality = 82): ?string
    {
        if (! is_file($fullPath)) {
            return null;
        }

        $size = filesize($fullPath);
        $src = @imagecreatefromstring((string) file_get_contents($fullPath));
        if (! $src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // Already light and within bounds → keep exactly as-is.
        if ($w <= $maxDim && $h <= $maxDim && $size <= self::MIN_SKIP_BYTES) {
            imagedestroy($src);

            return null;
        }

        $scale = min(1.0, $maxDim / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $hasAlpha = static::hasAlpha($src);
        $dir = dirname($fullPath);
        $base = pathinfo($fullPath, PATHINFO_FILENAME);

        // Small transparent PNG icon/logo → keep PNG, max compression, only
        // replace when it actually saves bytes (temp file + rename, never
        // write over the original in place).
        if ($hasAlpha && $ext === 'png' && $nw <= 512 && $nh <= 512) {
            $tmp = $dir . '/' . $base . '.opt.png';
            if (imagepng($dst, $tmp, 9) && filesize($tmp) < $size) {
                @unlink($fullPath);
                @rename($tmp, $fullPath);
            } else {
                @unlink($tmp);
            }
            imagedestroy($src);
            imagedestroy($dst);

            return null;
        }

        // Photos (and large transparent images) → WebP when it saves bytes.
        // A source that is already .webp is re-encoded in place via a temp
        // file so the relative path never changes (DB references stay valid).
        $newFull = null;
        if (function_exists('imagewebp')) {
            if ($ext === 'webp') {
                $tmp = $dir . '/' . $base . '.opt.webp';
                if (imagewebp($dst, $tmp, $quality) && filesize($tmp) < $size) {
                    @unlink($fullPath);
                    @rename($tmp, $fullPath);
                } else {
                    @unlink($tmp);
                }
            } else {
                $tmp = $dir . '/' . $base . '.webp';
                if (imagewebp($dst, $tmp, $quality) && filesize($tmp) < $size) {
                    $newFull = $tmp;
                } else {
                    @unlink($tmp);
                }
            }
        }

        imagedestroy($src);
        imagedestroy($dst);

        if ($newFull === null) {
            return null;
        }

        unlink($fullPath);

        $publicRoot = Storage::disk('public')->path('');

        return str_replace('\\', '/', ltrim(substr($newFull, strlen($publicRoot)), '/'));
    }

    /** Sampled alpha check — true when any semi-transparent pixel is found. */
    private static function hasAlpha(\GdImage $img): bool
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $stepX = max(1, (int) ($w / 64));
        $stepY = max(1, (int) ($h / 64));

        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                if ((imagecolorat($img, $x, $y) >> 24 & 0x7F) > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
