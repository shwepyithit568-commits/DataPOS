<?php

namespace App\Themes;

/**
 * ThemeContext — request-scoped theme override for the storefront preview.
 *
 * The appearance preview route sets a draft ThemeConfig here and then renders
 * the production storefront view. The storefront layout reads activeConfig()
 * and renders those tokens instead of the published ones.
 *
 * Guarantees:
 *  - Never mutates storefront_settings, models, caches, or global config.
 *  - Registered as a scoped container binding (AppServiceProvider), so the
 *    override resets at the start of every request (Octane-safe).
 *  - Only the authenticated preview route may set it — anonymous storefront
 *    requests always resolve the published config.
 */
class ThemeContext
{
    protected ?ThemeConfig $activeConfig = null;

    public function setConfig(?ThemeConfig $config): void
    {
        $this->activeConfig = $config;
    }

    public function activeConfig(): ?ThemeConfig
    {
        return $this->activeConfig;
    }

    public function isPreview(): bool
    {
        return $this->activeConfig !== null;
    }
}
