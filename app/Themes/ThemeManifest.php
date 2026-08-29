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
     * @param string $version Bundle version — recorded in every published revision
     *                        snapshot so future manifest changes can be detected
     *                        and migrated additively (see ThemePlan §5.3).
     * @param string $status Lifecycle: active | deprecated | hidden (T7).
     *                        Existing stores keep rendering deprecated/hidden
     *                        themes; only NEW selection is affected.
     * @param string|null $replacementId Recommended replacement when deprecated.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $nameEn,
        public readonly string $nameMm,
        public readonly string $description,
        public readonly array $colors,
        public readonly string $defaultFont = 'outfit',
        public readonly string $defaultDensity = 'compact',
        public readonly string $version = '1',
        public readonly string $status = 'active',
        public readonly ?string $replacementId = null,
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
