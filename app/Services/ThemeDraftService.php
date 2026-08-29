<?php

namespace App\Services;

use App\Exceptions\ThemeDraftConflictException;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\StoreThemeDraft;
use App\Models\StoreThemeRevision;
use App\Models\User;
use App\Themes\ThemeConfig;
use Illuminate\Support\Facades\DB;

class ThemeDraftService
{
    public function __construct(
        private readonly ThemePublisher $themePublisher,
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Load the store's active draft, or create one from the current published
     * state if none exists yet.
     *
     * IMPORTANT: this method NEVER touches storefront_settings.
     */
    public function getOrCreate(Store $store, ?User $actor = null): StoreThemeDraft
    {
        $draft = StoreThemeDraft::where('store_id', $store->id)->first();

        if ($draft) {
            return $draft;
        }

        return $this->createFromPublished($store, $actor);
    }

    /**
     * Save a draft with optimistic concurrency protection.
     *
     * Rules:
     *  - lock_version MUST match the client's known version, otherwise HTTP 409.
     *  - storefront_settings is NEVER updated here.
     *  - Returns the updated draft (with incremented lock_version).
     *
     * @param array<string, mixed> $config Raw user input — ThemeConfig normalizes it.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException (409) on stale lock.
     */
    public function save(
        Store   $store,
        array   $config,
        int     $expectedLockVersion,
        ?User   $actor = null,
    ): StoreThemeDraft {
        $normalized = ThemeConfig::fromArray($config);

        try {
            return DB::transaction(function () use ($store, $normalized, $expectedLockVersion, $actor) {
                $draft = StoreThemeDraft::where('store_id', $store->id)
                    ->lockForUpdate()
                    ->first();

                if (! $draft) {
                    // Draft vanished (edge case: race with discard). Re-create and save.
                    $draft = $this->createFromPublished($store, $actor);
                }

                // Optimistic lock check
                if ($draft->lock_version !== $expectedLockVersion) {
                    throw new ThemeDraftConflictException($draft, 'stale_lock', 'Draft was modified by another session. Refresh and try again.');
                }

                $draft->update([
                    'theme_config' => $normalized->toArray(),
                    'updated_by'   => $actor?->id,
                    'lock_version' => $draft->lock_version + 1,
                ]);

                return $draft->fresh();
            });
        } catch (ThemeDraftConflictException $e) {
            $this->auditConflict($store, $e->draft, $e->reason, $actor);
            abort(409, $e->getMessage());
        }
    }

    /**
     * Publish the draft to the live storefront.
     *
     * Conflict check: if the published theme was changed by another actor after
     * this draft's base_revision_id was recorded, we refuse with an HTTP 409
     * (audited as store_theme_draft_conflict).
     *
     * On success, re-bases the draft to the new revision so future saves start
     * from the correct baseline.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException (409) on stale lock or conflict.
     */
    public function publish(
        Store   $store,
        int     $expectedLockVersion,
        ?User   $actor = null,
        ?string $ipAddress = null,
    ): StoreThemeRevision {
        try {
            return DB::transaction(function () use ($store, $expectedLockVersion, $actor, $ipAddress) {
                $draft = StoreThemeDraft::where('store_id', $store->id)
                    ->lockForUpdate()
                    ->first();

                if (! $draft) {
                    abort(422, 'No draft found to publish. Open the appearance page first.');
                }

                // Optimistic lock check
                if ($draft->lock_version !== $expectedLockVersion) {
                    throw new ThemeDraftConflictException($draft, 'stale_lock', 'Draft was modified by another session. Refresh and try again.');
                }

                // Conflict check: someone published between draft creation and now
                $latestRevision = StoreThemeRevision::where('store_id', $store->id)
                    ->where('action', '!=', 'baseline')
                    ->latest('revision_number')
                    ->value('id');

                if ($draft->isConflicting($latestRevision)) {
                    throw new ThemeDraftConflictException($draft, 'base_revision', 'The theme was published by another user since you opened this page. Review the current theme and re-apply your changes.');
                }

                // Delegate the actual publish (storefront_settings update + revision row)
                $revision = $this->themePublisher->publish(
                    $store,
                    $draft->theme_config,
                    $actor,
                    $ipAddress,
                );

                // Re-base draft to the new revision so future saves start clean
                $draft->update([
                    'base_revision_id' => $revision->id,
                    'lock_version'     => $draft->lock_version + 1,
                ]);

                return $revision;
            });
        } catch (ThemeDraftConflictException $e) {
            // Audit must happen OUTSIDE the rolled-back transaction,
            // otherwise the insert would be rolled back with the abort.
            $this->auditConflict($store, $e->draft, $e->reason, $actor, $ipAddress);
            abort(409, $e->getMessage());
        }
    }

    /**
     * Discard the active draft entirely.
     * The next getOrCreate() call will re-seed from the published state.
     * Recorded in the audit log — discarding throws away draft work.
     */
    public function discard(Store $store, ?User $actor = null, ?string $ipAddress = null): void
    {
        $draft = StoreThemeDraft::where('store_id', $store->id)->first();

        if (! $draft) {
            return;
        }

        AuditLog::write(
            $store->id,
            'store_theme_draft_discard',
            'store_theme_drafts',
            $draft->id,
            [
                'theme_preset' => $draft->theme_config['theme_preset'] ?? null,
                'lock_version' => $draft->lock_version,
            ],
            $actor?->id,
            $ipAddress,
        );

        $draft->delete();
    }

    /**
     * Reset the draft to the current published state (called after a rollback).
     * Increments lock_version so any in-flight tab-saves are rejected.
     */
    public function resetToPublished(Store $store, ?User $actor = null): StoreThemeDraft
    {
        return DB::transaction(function () use ($store, $actor) {
            $draft = StoreThemeDraft::where('store_id', $store->id)
                ->lockForUpdate()
                ->first();

            $setting = StorefrontSetting::where('store_id', $store->id)->first();
            $config  = $setting
                ? ThemeConfig::fromSetting($setting)
                : ThemeConfig::fromArray([]);

            $latestRevisionId = StoreThemeRevision::where('store_id', $store->id)
                ->latest('revision_number')
                ->value('id');

            if ($draft) {
                $draft->update([
                    'theme_config'     => $config->toArray(),
                    'base_revision_id' => $latestRevisionId,
                    'updated_by'       => $actor?->id,
                    'lock_version'     => $draft->lock_version + 1,
                ]);
                return $draft->fresh();
            }

            return StoreThemeDraft::create([
                'store_id'         => $store->id,
                'theme_config'     => $config->toArray(),
                'base_revision_id' => $latestRevisionId,
                'updated_by'       => $actor?->id,
                'lock_version'     => 1,
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Record a refused publish (stale lock or base-revision conflict) in the
     * audit log. Plan §12 requires draft conflicts to be audited; regular
     * autosaves are intentionally NOT audited.
     */
    private function auditConflict(Store $store, StoreThemeDraft $draft, string $reason, ?User $actor, ?string $ipAddress = null): void
    {
        AuditLog::write(
            $store->id,
            'store_theme_draft_conflict',
            'store_theme_drafts',
            $draft->id,
            [
                'reason'          => $reason,
                'lock_version'    => $draft->lock_version,
                'base_revision_id'=> $draft->base_revision_id,
                'theme_preset'    => $draft->theme_config['theme_preset'] ?? null,
            ],
            $actor?->id,
            $ipAddress,
        );
    }

    /**
     * Create a fresh draft seeded from the current published StorefrontSetting.
     * base_revision_id is set to the latest published revision, or null if none.
     */
    private function createFromPublished(Store $store, ?User $actor): StoreThemeDraft
    {
        $setting = StorefrontSetting::where('store_id', $store->id)->first();
        $config  = $setting
            ? ThemeConfig::fromSetting($setting)
            : ThemeConfig::fromArray([]);

        $latestRevisionId = StoreThemeRevision::where('store_id', $store->id)
            ->latest('revision_number')
            ->value('id');

        return StoreThemeDraft::create([
            'store_id'         => $store->id,
            'theme_config'     => $config->toArray(),
            'base_revision_id' => $latestRevisionId,
            'updated_by'       => $actor?->id,
            'lock_version'     => 1,
        ]);
    }
}
