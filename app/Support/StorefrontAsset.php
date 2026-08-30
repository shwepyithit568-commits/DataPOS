<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StorefrontAsset
{
    public static function imageUrl(?string $path): ?string
    {
        $path = self::normalizeImagePath($path);

        if ($path === null) {
            return null;
        }

        if (str_starts_with($path, 'assets/')) {
            if (! is_file(public_path($path))) {
                return null;
            }

            return asset($path);
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return asset('storage/' . $path);
    }

    public static function normalizeImagePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
            return null;
        }

        if (! preg_match('#^(assets/chat-icons|chat-icons|payment-icons|payment-qr)/[A-Za-z0-9][A-Za-z0-9._/-]*\.(png|jpe?g|webp|svg)$#i', $path)) {
            return null;
        }

        return $path;
    }

    public static function isManagedChatUpload(?string $path): bool
    {
        $path = self::normalizeImagePath($path);

        return $path !== null && str_starts_with($path, 'chat-icons/');
    }
}
