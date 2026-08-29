<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ThemeGovernance;
use App\Models\User;
use App\Themes\ThemeManifest;
use App\Themes\ThemeRegistry;

/**
 * ThemeGovernanceService — platform-controlled theme lifecycle (T7).
 *
 * Status semantics (ThemePlan §T7 / §12):
 *  - active:     selectable for new stores + shown in the appearance picker.
 *  - deprecated: still fully renderable and selectable, but flagged with a
 *                recommended replacement so store owners can migrate.
 *  - hidden:     NOT shown in the picker / not recommended to new stores, but
 *                stores already using it keep rendering (never breaks).
 *
 * The static manifest status is the baseline; theme_governance rows override
 * it. Every lifecycle change is audited (platform-level, store_id = null).
 */
class ThemeGovernanceService
{
    /** Build the effective (override-aware) manifest for a theme id. */
    public function effectiveManifest(string $themeId): ThemeManifest
    {
        $manifest = ThemeRegistry::get($themeId);
        $override = ThemeGovernance::where('theme_id', $manifest->id)->first();

        if (! $override) {
            return $manifest;
        }

        return new ThemeManifest(
            id: $manifest->id,
            nameEn: $manifest->nameEn,
            nameMm: $manifest->nameMm,
            description: $manifest->description,
            colors: $manifest->colors,
            defaultFont: $manifest->defaultFont,
            defaultDensity: $manifest->defaultDensity,
            version: $manifest->version,
            status: $override->status,
            replacementId: $override->replacement_id ?: $manifest->replacementId,
        );
    }

    public function effectiveStatus(string $themeId): string
    {
        return $this->effectiveManifest($themeId)->status;
    }

    /** Recommended replacement when deprecated (override ?? manifest ?? null). */
    public function replacementFor(string $themeId): ?string
    {
        return $this->effectiveManifest($themeId)->replacementId;
    }

    /**
     * Theme ids a NEW store may be provisioned/recommended with: active only.
     * (Deprecated/hidden remain renderable for existing stores.)
     *
     * @return list<string>
     */
    public function activeIds(): array
    {
        return array_values(array_filter(
            ThemeRegistry::ids(),
            fn (string $id) => $this->effectiveStatus($id) === ThemeGovernance::STATUS_ACTIVE,
        ));
    }

    /**
     * Theme ids offered in the appearance picker: active + deprecated.
     *
     * @return list<string>
     */
    public function selectableIds(): array
    {
        return array_values(array_filter(
            ThemeRegistry::ids(),
            fn (string $id) => in_array($this->effectiveStatus($id), [
                ThemeGovernance::STATUS_ACTIVE,
                ThemeGovernance::STATUS_DEPRECATED,
            ], true),
        ));
    }

    /** All registered themes, each with its effective status/replacement. */
    public function list(): array
    {
        return array_map(
            fn (string $id) => $this->effectiveManifest($id),
            ThemeRegistry::ids(),
        );
    }

    /**
     * Upsert a lifecycle override + audit it. Platform Owner only (enforced at
     * the route); the actor is recorded for audit.
     *
     * @throws \InvalidArgumentException when status is not allow-listed.
     */
    public function setStatus(string $themeId, string $status, ?string $replacementId = null, ?User $actor = null): void
    {
        $canonical = ThemeRegistry::get($themeId)->id;

        if (! in_array($status, ThemeGovernance::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid theme status: {$status}");
        }

        if ($replacementId !== null && ! ThemeRegistry::has($replacementId)) {
            throw new \InvalidArgumentException("Replacement theme does not exist: {$replacementId}");
        }

        $previous = $this->effectiveStatus($canonical);
        $previousReplacement = $this->replacementFor($canonical);

        ThemeGovernance::updateOrCreate(
            ['theme_id' => $canonical],
            [
                'status'         => $status,
                'replacement_id' => $replacementId,
                'updated_by'     => $actor?->id,
            ],
        );

        AuditLog::write(
            null,
            'theme_lifecycle_change',
            'theme_governance',
            ThemeGovernance::where('theme_id', $canonical)->value('id'),
            [
                'theme_id'            => $canonical,
                'from_status'         => $previous,
                'to_status'           => $status,
                'from_replacement_id' => $previousReplacement,
                'to_replacement_id'   => $replacementId,
            ],
            $actor?->id,
        );
    }
}
