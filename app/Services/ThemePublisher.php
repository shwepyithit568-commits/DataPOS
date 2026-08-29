<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\StoreThemeRevision;
use App\Models\User;
use App\Themes\ThemeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ThemePublisher
{
    public const THEME_FIELDS = [
        'theme_preset',
        'theme_primary_color',
        'theme_accent_color',
        'theme_header_bg',
        'theme_body_bg',
        'theme_glow_style',
        'theme_dark_mode',
        'font_preset',
        'grid_density',
    ];

    /** @param array<string, mixed> $config */
    public function publish(Store $store, array $config, ?User $actor = null, ?string $ipAddress = null): StoreThemeRevision
    {
        $validated = $this->validate($config);

        return DB::transaction(function () use ($store, $validated, $actor, $ipAddress) {
            $setting = StorefrontSetting::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->first();

            if (! $setting) {
                $setting = StorefrontSetting::create([
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                ]);
            }

            $current = $this->snapshot($setting);
            $latest = StoreThemeRevision::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->latest('revision_number')
                ->first();

            $nextNumber = ($latest?->revision_number ?? 0) + 1;

            if (! $latest) {
                StoreThemeRevision::create([
                    'store_id' => $store->id,
                    'revision_number' => $nextNumber++,
                    'theme_config' => $current,
                    'action' => 'baseline',
                    'actor_id' => $actor?->id,
                    'created_at' => now(),
                ]);
            }

            $published = array_replace($current, $validated);
            $setting->fill($published)->save();

            $revision = StoreThemeRevision::create([
                'store_id' => $store->id,
                'revision_number' => $nextNumber,
                'theme_config' => $this->snapshot($setting),
                'action' => 'publish',
                'actor_id' => $actor?->id,
                'created_at' => now(),
            ]);

            $this->audit($store, $revision, $actor, $ipAddress);

            return $revision;
        });
    }

    public function rollback(Store $store, StoreThemeRevision $source, ?User $actor = null, ?string $ipAddress = null): StoreThemeRevision
    {
        abort_unless($source->store_id === $store->id, 404);
        $validated = $this->validate($source->theme_config);

        return DB::transaction(function () use ($store, $source, $validated, $actor, $ipAddress) {
            $setting = StorefrontSetting::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->firstOrFail();

            $latest = StoreThemeRevision::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->latest('revision_number')
                ->firstOrFail();

            $setting->fill($validated)->save();

            $revision = StoreThemeRevision::create([
                'store_id' => $store->id,
                'revision_number' => $latest->revision_number + 1,
                'theme_config' => $this->snapshot($setting),
                'action' => 'rollback',
                'source_revision_id' => $source->id,
                'actor_id' => $actor?->id,
                'created_at' => now(),
            ]);

            $this->audit($store, $revision, $actor, $ipAddress);

            return $revision;
        });
    }

    /** @return array<string, mixed> */
    public function snapshot(StorefrontSetting $setting): array
    {
        return collect(self::THEME_FIELDS)
            ->mapWithKeys(fn (string $field) => [$field => $setting->{$field}])
            ->all();
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private function validate(array $config): array
    {
        return Validator::make($config, [
            'theme_preset' => ['nullable', 'string', Rule::in(array_keys(StorefrontSetting::THEME_PRESETS))],
            'theme_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_header_bg' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_body_bg' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_glow_style' => ['nullable', Rule::in(['vivid', 'subtle', 'none'])],
            'theme_dark_mode' => ['nullable', Rule::in(['auto', 'light', 'dark'])],
            'font_preset' => ['nullable', Rule::in(array_keys(ThemeRegistry::FONT_PRESETS))],
            'grid_density' => ['nullable', Rule::in(array_keys(ThemeRegistry::GRID_DENSITIES))],
        ])->validate();
    }

    private function audit(Store $store, StoreThemeRevision $revision, ?User $actor, ?string $ipAddress): void
    {
        AuditLog::write(
            $store->id,
            'store_theme_'.$revision->action,
            'store_theme_revisions',
            $revision->id,
            [
                'revision_number' => $revision->revision_number,
                'source_revision_id' => $revision->source_revision_id,
                'theme_preset' => $revision->theme_config['theme_preset'] ?? null,
            ],
            $actor?->id,
            $ipAddress,
        );
    }
}
