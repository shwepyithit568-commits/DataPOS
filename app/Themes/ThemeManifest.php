<?php

namespace App\Themes;

class ThemeManifest
{
    /**
     * @param string $id
     * @param string $nameEn
     * @param string $nameMm
     * @param string $description
     * @param array<string, string> $colors [primary, accent, header_bg, body_bg, glow_style, dark_mode]
     * @param string $defaultFont
     * @param string $defaultDensity
     */
    public function __construct(
        public readonly string $id,
        public readonly string $nameEn,
        public readonly string $nameMm,
        public readonly string $description,
        public readonly array $colors,
        public readonly string $defaultFont = 'outfit',
        public readonly string $defaultDensity = 'compact'
    ) {}

    public function primaryColor(): string
    {
        return $this->colors['primary'] ?? '#0ea5e9';
    }

    public function accentColor(): string
    {
        return $this->colors['accent'] ?? '#7c3aed';
    }

    public function headerBg(): string
    {
        return $this->colors['header_bg'] ?? '#ffffff';
    }

    public function bodyBg(): string
    {
        return $this->colors['body_bg'] ?? '#f8fafc';
    }

    public function glowStyle(): string
    {
        return $this->colors['glow_style'] ?? 'vivid';
    }

    public function darkMode(): string
    {
        return $this->colors['dark_mode'] ?? 'auto';
    }
}
