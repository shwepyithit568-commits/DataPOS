<?php

namespace App\Services;

class GlassCodeNormalizer
{
    /**
     * Normalize raw glass codes from Excel or user input into lowercased alphanumeric strings.
     * e.g., " GLASS-001 " -> "glass001", "glass 001" -> "glass001", "Glass_001" -> "glass001"
     */
    public static function normalize(?string $code): string
    {
        if (empty($code)) {
            return '';
        }

        // Strip spaces, dashes, underscores, and convert to lowercase
        $normalized = str_replace([' ', '-', '_'], '', strtolower(trim($code)));

        return preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
    }
}
