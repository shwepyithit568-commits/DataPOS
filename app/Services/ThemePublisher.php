<?php

namespace App\Services;

use App\Events\ThemeRevisionCommitted;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\StoreThemeRevision;
use App\Models\User;
use App\Themes\ThemeConfig;
use Illuminate\Support\Facades\DB;

class ThemePublisher
{
    /**
     * The 9 canonical theme field names persisted in storefront_settings.
     */
    public const THEME_FIELDS = ThemeConfig::SAFE_KEYS;

    /**
     * Validate, normalize, and publish a theme config for the given store.
     *
     * @param array<string, mixed> $config Raw user input — unknown keys are discarded.
     */
    public function publish(Store $store, array $config, ?User $actor = null, ?string $ipAddress = null): StoreThemeRevision
    {
        $themeConfig = ThemeConfig::fromArray($config);

        $revision = DB::transaction(function () use ($store, $themeConfig, $actor, $ipAddress) {
            $setting = StorefrontSetting::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->first();

            if (! $setting) {
                $setting = StorefrontSetting::create([
                    'store_id'   => $store->id,
                    'store_name' => $store->name,
                ]);
            }

            // Capture the baseline snapshot (before applying new config)
            $baseline = ThemeConfig::fromSetting($setting);

            $latest = StoreThemeRevision::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->latest('revision_number')
                ->first();

            $nextNumber = ($latest?->revision_number ?? 0) + 1;

            // First-ever publish: record a baseline revision first
            if (! $latest) {
                StoreThemeRevision::create([
                    'store_id'        => $store->id,
                    'revision_number' => $nextNumber++,
                    'theme_config'    => $baseline->toRevisionSnapshot(),
                    'action'          => 'baseline',
                    'actor_id'        => $actor?->id,
                    'created_at'      => now(),
                ]);
            }

            // Apply the new canonical config to the setting
            $setting->fill($themeConfig->toArray())->save();

            $revision = StoreThemeRevision::create([
                'store_id'        => $store->id,
                'revision_number' => $nextNumber,
                'theme_config'    => ThemeConfig::fromSetting($setting)->toRevisionSnapshot(),
                'action'          => 'publish',
                'actor_id'        => $actor?->id,
                'created_at'      => now(),
            ]);

            $this->audit($store, $revision, $actor, $ipAddress);

            return $revision;
        });

        // After commit: notify listeners (cache invalidation) — failures here
        // must never roll back or undo the already-persisted publish.
        ThemeRevisionCommitted::dispatch($store, $revision, 'publish', $actor);

        return $revision;
    }

    /**
     * Roll back the store's published theme to an exact previous revision.
     * Creates a new revision row; never mutates existing history.
     */
    public function rollback(Store $store, StoreThemeRevision $source, ?User $actor = null, ?string $ipAddress = null): StoreThemeRevision
    {
        abort_unless($source->store_id === $store->id, 404);

        // Normalize the snapshot being restored (handles legacy/unknown keys in old revisions)
        $themeConfig = ThemeConfig::fromArray($source->theme_config);

        $revision = DB::transaction(function () use ($store, $source, $themeConfig, $actor, $ipAddress) {
            $setting = StorefrontSetting::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->firstOrFail();

            $latest = StoreThemeRevision::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->latest('revision_number')
                ->firstOrFail();

            $setting->fill($themeConfig->toArray())->save();

            $revision = StoreThemeRevision::create([
                'store_id'          => $store->id,
                'revision_number'   => $latest->revision_number + 1,
                'theme_config'      => ThemeConfig::fromSetting($setting)->toRevisionSnapshot(),
                'action'            => 'rollback',
                'source_revision_id'=> $source->id,
                'actor_id'          => $actor?->id,
                'created_at'        => now(),
            ]);

            $this->audit($store, $revision, $actor, $ipAddress);

            return $revision;
        });

        // After commit: notify listeners (cache invalidation) — failures here
        // must never roll back or undo the already-persisted rollback.
        ThemeRevisionCommitted::dispatch($store, $revision, 'rollback', $actor);

        return $revision;
    }

    /**
     * Create a canonical snapshot array from a StorefrontSetting model.
     * Kept for backward compatibility with any callers; internally delegates to ThemeConfig.
     *
     * @return array<string, mixed>
     */
    public function snapshot(StorefrontSetting $setting): array
    {
        return ThemeConfig::fromSetting($setting)->toRevisionSnapshot();
    }

    private function audit(Store $store, StoreThemeRevision $revision, ?User $actor, ?string $ipAddress): void
    {
        AuditLog::write(
            $store->id,
            'store_theme_' . $revision->action,
            'store_theme_revisions',
            $revision->id,
            [
                'revision_number'    => $revision->revision_number,
                'source_revision_id' => $revision->source_revision_id,
                'theme_preset'       => $revision->theme_config['theme_preset'] ?? null,
                'schema_version'     => $revision->theme_config['schema_version'] ?? null,
            ],
            $actor?->id,
            $ipAddress,
        );
    }
}
