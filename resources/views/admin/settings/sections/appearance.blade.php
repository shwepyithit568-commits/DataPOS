{{--
  Admin › Store Settings › Appearance Section
  Template Cards (5 designs) + Custom colour overrides.
  Saved via POST /store/{slug}/admin/settings (section = appearance).

  Alpine.js: registered via Alpine.data() inside alpine:init to avoid
  "function not defined" errors from script/x-data evaluation order.
--}}

@php
    $currentPreset = $setting->theme_preset ?? 'sky';
    $colors = $setting->themeColors();

    // ── Load or create the active draft (T2 Draft System) ────────────────────
    /** @var \App\Models\StoreThemeDraft $draft */
    $draft = app(\App\Services\ThemeDraftService::class)->getOrCreate(
        $setting->store ?? $store,
        auth()->user()
    );
    $draftConfig   = $draft->theme_config;
    $lockVersion   = $draft->lock_version;
    $baseRevId     = $draft->base_revision_id;

    // Conflict: another actor published since this draft was last re-based
    $latestRevId = \App\Models\StoreThemeRevision::where('store_id', $store->id)
        ->where('action', '!=', 'baseline')
        ->latest('revision_number')
        ->value('id');
    $latestRevNumber = \App\Models\StoreThemeRevision::where('store_id', $store->id)
        ->latest('revision_number')
        ->value('revision_number');
    $isConflict = $draft->isConflicting($latestRevId);

    // Use the DRAFT config to seed the editor (not the published setting)
    $currentPreset = $draftConfig['theme_preset'] ?? $currentPreset;
    $colors = [
        'primary'    => $draftConfig['theme_primary_color'] ?? $colors['primary'],
        'accent'     => $draftConfig['theme_accent_color']  ?? $colors['accent'],
        'header_bg'  => $draftConfig['theme_header_bg']     ?? $colors['header_bg'],
        'body_bg'    => $draftConfig['theme_body_bg']        ?? ($colors['body_bg'] ?? '#f8fafc'),
        'glow_style' => $draftConfig['theme_glow_style']    ?? ($colors['glow_style'] ?? 'vivid'),
        'dark_mode'  => $draftConfig['theme_dark_mode']     ?? ($colors['dark_mode'] ?? 'auto'),
    ];
    // ─────────────────────────────────────────────────────────────────────────

    // 7 curated visual templates + 'custom' fallback with body_bg, glow_style, and dark_mode
    $templates = [
        'marketplace_pro' => [
            'name'        => __('messages.theme_tpl_marketplace_pro'),
            'desc'        => __('messages.theme_tpl_marketplace_pro_desc'),
            'primary'     => '#0ea5e9',
            'accent'      => '#7c3aed',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#f8fafc',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'auto',
            'badge'       => __('messages.theme_badge_tech_mobile'),
            'badge_color' => 'bg-sky-500',
        ],
        'retail_trust' => [
            'name'        => __('messages.theme_tpl_retail_trust'),
            'desc'        => __('messages.theme_tpl_retail_trust_desc'),
            'primary'     => '#2563eb',
            'accent'      => '#f59e0b',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#f8fafc',
            'glow_style'  => 'subtle',
            'dark_mode'   => 'auto',
            'badge'       => __('messages.theme_badge_general_retail'),
            'badge_color' => 'bg-blue-600',
        ],
        'emerald_fresh' => [
            'name'        => __('messages.theme_tpl_emerald_fresh'),
            'desc'        => __('messages.theme_tpl_emerald_fresh_desc'),
            'primary'     => '#10b981',
            'accent'      => '#f59e0b',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#f0fdf4',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'auto',
            'badge'       => __('messages.theme_badge_healthcare'),
            'badge_color' => 'bg-emerald-600',
        ],
        'royal_tech' => [
            'name'        => __('messages.theme_tpl_royal_tech'),
            'desc'        => __('messages.theme_tpl_royal_tech_desc'),
            'primary'     => '#7c3aed',
            'accent'      => '#38bdf8',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#faf5ff',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'auto',
            'badge'       => __('messages.theme_badge_gaming_pc'),
            'badge_color' => 'bg-violet-600',
        ],
        'cyber_amber' => [
            'name'        => __('messages.theme_tpl_cyber_amber'),
            'desc'        => __('messages.theme_tpl_cyber_amber_desc'),
            'primary'     => '#f59e0b',
            'accent'      => '#0ea5e9',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#fffbeb',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'auto',
            'badge'       => __('messages.theme_badge_accessories_cctv'),
            'badge_color' => 'bg-amber-600',
        ],
        'sunset_warm' => [
            'name'        => __('messages.theme_tpl_sunset_warm'),
            'desc'        => __('messages.theme_tpl_sunset_warm_desc'),
            'primary'     => '#e11d48',
            'accent'      => '#f59e0b',
            'header_bg'   => '#fff1f2',
            'body_bg'     => '#fff5f6',
            'glow_style'  => 'subtle',
            'dark_mode'   => 'auto',
            'badge'       => __('messages.theme_badge_boutique_fashion'),
            'badge_color' => 'bg-rose-500',
        ],
        'midnight_tech' => [
            'name'        => __('messages.theme_tpl_midnight_tech'),
            'desc'        => __('messages.theme_tpl_midnight_tech_desc'),
            'primary'     => '#38bdf8',
            'accent'      => '#fb923c',
            'header_bg'   => '#0f172a',
            'body_bg'     => '#0f172a',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'dark',
            'badge'       => __('messages.theme_badge_dark_mode'),
            'badge_color' => 'bg-slate-700',
        ],
        'custom' => [
            'name'        => __('messages.theme_tpl_custom'),
            'desc'        => __('messages.theme_tpl_custom_desc'),
            'primary'     => $colors['primary'],
            'accent'      => $colors['accent'],
            'header_bg'   => $colors['header_bg'],
            'body_bg'     => $colors['body_bg'] ?? '#f8fafc',
            'glow_style'  => $colors['glow_style'] ?? 'vivid',
            'dark_mode'   => $colors['dark_mode'] ?? 'auto',
            'badge'       => __('messages.theme_badge_custom'),
            'badge_color' => 'bg-slate-600',
        ],
    ];

    // Normalize legacy keys — single source of truth from ThemeRegistry
    $legacyMap = \App\Themes\ThemeRegistry::legacyMap();
    if (isset($legacyMap[$currentPreset])) {
        $currentPreset = $legacyMap[$currentPreset];
    }

    // If preset not in our template list, treat as custom
    if (!array_key_exists($currentPreset, $templates)) {
        $currentPreset = 'custom';
    }

    $templatesJson = json_encode(array_map(
        fn($t) => [
            'primary'    => $t['primary'],
            'accent'     => $t['accent'],
            'header_bg'  => $t['header_bg'],
            'body_bg'    => $t['body_bg'],
            'glow_style' => $t['glow_style'],
            'dark_mode'  => $t['dark_mode'],
        ],
        $templates
    ));

    // ── Theme lifecycle (T7): hidden themes are not offered in the picker;
    //    deprecated ones stay selectable but carry a migration badge. ──
    $governanceService = app(\App\Services\ThemeGovernanceService::class);
    $themeStatuses = [];
    foreach (array_keys($templates) as $tKey) {
        $themeStatuses[$tKey] = $tKey === 'custom'
            ? 'active'
            : $governanceService->effectiveStatus($tKey);
    }
    $templates = array_filter(
        $templates,
        fn($tKey) => $themeStatuses[$tKey] !== 'hidden',
        ARRAY_FILTER_USE_KEY,
    );
    // ─────────────────────────────────────────────────────────────────────────
@endphp

{{--
  ALPINE FIX: Factory function pattern with CSP nonce.
  ─────────────────────────────────────────────────────────────
  1. Nonce-based CSP: script tag MUST carry nonce="{{ $cspNonce }}"
     or SecurityHeaders middleware will block inline execution.
  2. Factory pattern: window.sfThemeFactory() returns fresh state
     with methods, called by x-data="sfThemeFactory()".
  ─────────────────────────────────────────────────────────────
--}}
<script nonce="{{ $cspNonce }}">
/* ① Store PHP-injected data in a plain window variable. */
window._sfThemeData = {
    preset:    {!! json_encode($currentPreset) !!},
    dark_mode: {!! json_encode($colors['dark_mode'] ?? 'auto') !!},
    colors:    {
        primary:    {!! json_encode($colors['primary'] ?? '#0ea5e9') !!},
        accent:     {!! json_encode($colors['accent'] ?? '#7c3aed') !!},
        header_bg:  {!! json_encode($colors['header_bg'] ?? '#ffffff') !!},
        body_bg:    {!! json_encode($colors['body_bg'] ?? '#f8fafc') !!},
        glow_style: {!! json_encode($colors['glow_style'] ?? 'vivid') !!}
    },
    templates: {!! $templatesJson !!},

    /* Draft system (T2) */
    lockVersion:    {!! json_encode($lockVersion) !!},
    baseRevisionId: {!! json_encode($baseRevId) !!},
    draftSaveUrl:   {!! json_encode(route('store.admin.appearance.draft.save',    ['store_slug' => $store->slug])) !!},
    publishUrl:     {!! json_encode(route('store.admin.appearance.publish',       ['store_slug' => $store->slug])) !!},
    discardUrl:     {!! json_encode(route('store.admin.appearance.draft.discard', ['store_slug' => $store->slug])) !!},
    csrfToken:      {!! json_encode(csrf_token()) !!},
    isConflict:     {!! json_encode($isConflict) !!},

    /* Isolated preview (T3) */
    previewUrl:     {!! json_encode(route('store.admin.appearance.preview', ['store_slug' => $store->slug])) !!},
};

/* ② Factory function — Alpine calls this when it processes x-data.
      Returns a FRESH object each time (no shared-reference issues).  */
window.sfThemeFactory = function () {
    var d = window._sfThemeData;
    var initialDark = (d.dark_mode === 'dark');
    return {
        preset:       d.preset,
        dark_mode:    d.dark_mode || 'auto',
        preview_dark: initialDark,
        colors:       JSON.parse(JSON.stringify(d.colors)),     // deep copy
        templates:    JSON.parse(JSON.stringify(d.templates)),  // deep copy

        /* ── Draft system state (T2) ── */
        draftStatus:        d.isConflict ? 'conflict' : 'saved', // 'saved'|'unsaved'|'saving'|'conflict'|'publishing'
        lockVersion:        d.lockVersion,
        baseRevisionId:     d.baseRevisionId,
        conflictMsg:        '',
        showPublishConfirm: false,
        publishError:       '',
        _autosaveTimer:     null,

        /* ── Isolated preview state (T3) ── */
        showPreview:      false,    // toggled on demand by user click
        previewViewport:  'desktop', // 'desktop' (1440) | 'tablet' (768) | 'mobile' (390)
        previewKey:       0,        // bump to force iframe reload with the latest draft
        previewLoaded:    false,
        previewSrc:       d.previewUrl + '?v=' + d.lockVersion,

        togglePreview: function () {
            this.showPreview = !this.showPreview;
            if (this.showPreview && !this.previewLoaded) {
                this.refreshPreview();
            }
        },

        setViewport: function (vp) {
            this.previewViewport = vp;
        },

        refreshPreview: function () {
            this.previewKey++;
            this.previewLoaded = false;
            this.previewSrc = d.previewUrl + '?v=' + this.previewKey;
            // Fallback: if the iframe never fires load event (blocked or errored),
            // drop the loading overlay so the frame is never stuck spinning.
            var self = this;
            clearTimeout(this._previewLoadTimer);
            this._previewLoadTimer = setTimeout(function () { self.previewLoaded = true; }, 8000);
        },

        onPreviewLoaded: function () {
            this.previewLoaded = true;
            clearTimeout(this._previewLoadTimer);
        },

        selectTemplate: function (key) {
            this.preset = key;
            if (this.templates[key]) {
                this.colors.primary    = this.templates[key].primary;
                this.colors.accent     = this.templates[key].accent;
                this.colors.header_bg  = this.templates[key].header_bg;
                this.colors.body_bg    = this.templates[key].body_bg;
                this.colors.glow_style = this.templates[key].glow_style;
                if (this.templates[key].dark_mode) {
                    this.dark_mode    = this.templates[key].dark_mode;
                    this.preview_dark = (this.dark_mode === 'dark');
                }
            }
            this.markUnsaved();
        },

        setDarkMode: function (mode) {
            this.dark_mode = mode;
            if (mode === 'dark') {
                this.preview_dark = true;
            } else if (mode === 'light') {
                this.preview_dark = false;
            }
            this.onCustomColorChange();
        },

        togglePreviewDark: function () {
            this.preview_dark = !this.preview_dark;
        },

        onCustomColorChange: function () {
            this.preset = 'custom';
            if (this.templates['custom']) {
                this.templates['custom'].primary    = this.colors.primary;
                this.templates['custom'].accent     = this.colors.accent;
                this.templates['custom'].header_bg  = this.colors.header_bg;
                this.templates['custom'].body_bg    = this.colors.body_bg;
                this.templates['custom'].glow_style = this.colors.glow_style;
                this.templates['custom'].dark_mode  = this.dark_mode;
            }
            this.markUnsaved();
        },

        resetToTemplate: function () {
            var t = this.templates[this.preset] || this.templates['marketplace_pro'];
            this.colors.primary    = t.primary;
            this.colors.accent     = t.accent;
            this.colors.header_bg  = t.header_bg;
            this.colors.body_bg    = t.body_bg;
            this.colors.glow_style = t.glow_style;
            this.dark_mode         = t.dark_mode || 'auto';
            this.preview_dark      = (this.dark_mode === 'dark');
            this.markUnsaved();
        },

        sanitizeHex: function (field, value) {
            var v = String(value).trim();
            if (v.charAt(0) !== '#') v = '#' + v;
            v = '#' + v.slice(1).replace(/[^0-9a-fA-F]/g, '').slice(0, 6);
            this.colors[field] = v;
        },

        contrastText: function (hex) {
            if (!hex || hex.length < 7) return '#1e293b';
            var r = parseInt(hex.slice(1, 3), 16);
            var g = parseInt(hex.slice(3, 5), 16);
            var b = parseInt(hex.slice(5, 7), 16);
            return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.55 ? '#1e293b' : '#ffffff';
        },

        /* ── Draft system methods (T2) ── */

        markUnsaved: function () {
            if (this.draftStatus === 'conflict') return; // keep conflict visible
            this.draftStatus = 'unsaved';
            this.scheduleAutosave();
        },

        scheduleAutosave: function () {
            clearTimeout(this._autosaveTimer);
            var self = this;
            this._autosaveTimer = setTimeout(function () { self.saveDraft(); }, 900);
        },

        buildThemeConfig: function () {
            return {
                theme_preset:        this.preset,
                theme_primary_color: this.colors.primary,
                theme_accent_color:  this.colors.accent,
                theme_header_bg:     this.colors.header_bg,
                theme_body_bg:       this.colors.body_bg,
                theme_glow_style:    this.colors.glow_style,
                theme_dark_mode:     this.dark_mode,
                font_preset:         document.querySelector('[name="font_preset"]:checked')?.value || 'outfit',
                grid_density:        document.querySelector('[name="grid_density"]:checked')?.value || 'compact',
            };
        },

        saveDraft: async function () {
            if (this.draftStatus === 'saving' || this.draftStatus === 'publishing') return;
            this.draftStatus = 'saving';
            try {
                var res = await fetch(d.draftSaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  d.csrfToken,
                    },
                    body: JSON.stringify({
                        theme_config: this.buildThemeConfig(),
                        lock_version: this.lockVersion,
                    }),
                });
                if (res.status === 409) {
                    var body = await res.json();
                    this.draftStatus = 'conflict';
                    this.conflictMsg = body.message || 'Draft conflict. Refresh to reload.';
                    return;
                }
                if (!res.ok) { this.draftStatus = 'unsaved'; return; }
                var data = await res.json();
                this.lockVersion = data.draft.lock_version;
                this.draftStatus = 'saved';
                this.refreshPreview(); // draft changed → reload the isolated preview
            } catch (e) {
                this.draftStatus = 'unsaved';
            }
        },

        openPublishConfirm: function () {
            this.publishError = '';
            // Defer to the next tick: the opening click must NOT immediately
            // trigger the modal's @click.outside (classic Alpine gotcha — the
            // same event that opens the modal would otherwise close it in the
            // same pass, so the modal flashes and instantly disappears).
            this.$nextTick(() => { this.showPublishConfirm = true; });
        },

        closePublishConfirm: function () {
            if (this.draftStatus === 'publishing') return;
            this.showPublishConfirm = false;
            this.publishError = '';
        },

        confirmPublish: async function () {
            this.draftStatus = 'publishing';
            this.publishError = '';
            try {
                /* Auto-save first so the server has the latest config */
                var saveRes = await fetch(d.draftSaveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': d.csrfToken },
                    body: JSON.stringify({ theme_config: this.buildThemeConfig(), lock_version: this.lockVersion }),
                });
                if (saveRes.ok) {
                    var saveData = await saveRes.json();
                    this.lockVersion = saveData.draft.lock_version;
                }

                var res = await fetch(d.publishUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': d.csrfToken },
                    body: JSON.stringify({ lock_version: this.lockVersion }),
                });

                if (res.status === 409) {
                    var body = await res.json();
                    this.draftStatus = 'conflict';
                    this.conflictMsg = body.message || 'Publish conflict. Another user published first. Refresh and review.';
                    this.showPublishConfirm = false;
                    return;
                }

                if (!res.ok) {
                    var errBody = {};
                    try { errBody = await res.json(); } catch(err) {}
                    this.draftStatus = 'saved';
                    this.publishError = errBody.message || 'Publish failed. Please try again.';
                    return;
                }

                var data = await res.json();
                this.draftStatus = 'saved';
                this.lockVersion = this.lockVersion + 1;
                this.showPublishConfirm = false;
                /* Success flash: reload to update storefront & revision history */
                window.location.reload();
            } catch (e) {
                this.draftStatus = 'saved';
                this.publishError = 'Network connection error. Please try again.';
            }
        },
    };
};

if (window.Alpine) {
    window.Alpine.data('sfThemeFactory', window.sfThemeFactory);
} else {
    document.addEventListener('alpine:init', function () {
        if (window.Alpine) window.Alpine.data('sfThemeFactory', window.sfThemeFactory);
    });
}
</script>

{{-- ③ x-data calls the factory — simple expression, no quote conflicts. --}}
<div class="divide-y divide-gray-100 dark:divide-slate-700/60"
     x-data="sfThemeFactory()">

    {{-- ══════════════════════════════════════════════════════
         Modal: In-App Publish Live Confirmation
    ══════════════════════════════════════════════════════ --}}
    <div x-show="showPublishConfirm"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @keydown.escape.window="closePublishConfirm()">
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-left space-y-4"
             @click.outside="closePublishConfirm()">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 text-xl font-bold">
                    🚀
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.theme_modal_publish_title') }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.theme_modal_publish_desc') }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-slate-50 p-3.5 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 space-y-1.5">
                <p>• {{ __('messages.theme_modal_publish_point1') }}</p>
                <p>• {{ __('messages.theme_modal_publish_point2', ['current' => $latestRevNumber ?? '—', 'next' => ($latestRevNumber ?? 0) + 1]) }}</p>
            </div>

            <div x-show="publishError" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-bold text-rose-700 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-300">
                ⚠️ <span x-text="publishError"></span>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button"
                        @click="closePublishConfirm()"
                        :disabled="draftStatus === 'publishing'"
                        class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 transition cursor-pointer">
                    {{ __('messages.theme_modal_publish_btn_cancel') }}
                </button>
                <button type="button"
                        @click="confirmPublish()"
                        :disabled="draftStatus === 'publishing'"
                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 hover:bg-violet-500 px-5 py-2.5 text-xs font-black text-white disabled:opacity-50 transition shadow-sm cursor-pointer">
                    <svg x-show="draftStatus === 'publishing'" x-cloak class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    <span x-show="draftStatus === 'publishing'" x-cloak>{{ __('messages.theme_status_publishing') }}</span>
                    <span x-show="draftStatus !== 'publishing'">{{ __('messages.theme_modal_publish_btn_confirm') }}</span>
                </button>
            </div>
        </div>
    </div>
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 text-violet-600 dark:text-violet-400 grid place-items-center text-lg font-bold shadow-xs shrink-0">
                    🎨
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>{{ __('messages.settings_appearance') }}</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                            Live Theme Engine
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.settings_appearance_desc') }}</p>
                </div>
            </div>

            {{-- ── Draft status badge + action buttons (T2) ── --}}
            <div class="flex flex-wrap items-center gap-2 flex-shrink-0 self-start sm:self-auto">

                {{-- Status badge --}}
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold transition-all duration-200 shadow-xs"
                    :class="{
                        'bg-emerald-50 border-emerald-300 text-emerald-700 dark:bg-emerald-950/50 dark:border-emerald-700 dark:text-emerald-300': draftStatus === 'saved',
                        'bg-blue-50 border-blue-300 text-blue-700 dark:bg-blue-950/50 dark:border-blue-700 dark:text-blue-300 animate-pulse':       draftStatus === 'unsaved',
                        'bg-slate-50 border-slate-300 text-slate-600 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400':                  draftStatus === 'saving',
                        'bg-amber-50 border-amber-400 text-amber-800 dark:bg-amber-950/50 dark:border-amber-600 dark:text-amber-300':              draftStatus === 'conflict',
                        'bg-violet-50 border-violet-400 text-violet-700 dark:bg-violet-950/50 dark:border-violet-600 dark:text-violet-300':        draftStatus === 'publishing',
                    }"
                >
                    <span x-show="draftStatus === 'saved'"      x-cloak class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>{{ __('messages.theme_status_saved') }}</span>
                    </span>
                    <span x-show="draftStatus === 'unsaved'"    x-cloak class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <span>{{ __('messages.theme_status_unsaved') }}</span>
                    </span>
                    <span x-show="draftStatus === 'saving'"     x-cloak class="flex items-center gap-1">
                        <svg class="animate-spin h-3 w-3 text-slate-500" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        <span>{{ __('messages.theme_status_saving') }}</span>
                    </span>
                    <span x-show="draftStatus === 'conflict'"   x-cloak class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                        <span>⚠️ {{ __('messages.theme_status_conflict') }}</span>
                    </span>
                    <span x-show="draftStatus === 'publishing'" x-cloak class="flex items-center gap-1">
                        <svg class="animate-spin h-3 w-3 text-violet-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        <span>{{ __('messages.theme_status_publishing') }}</span>
                    </span>
                </span>

                {{-- Toggle Live Preview button --}}
                <button
                    type="button"
                    @click="togglePreview()"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition active:scale-95 disabled:opacity-50 cursor-pointer shadow-xs"
                    :class="showPreview
                        ? 'border-violet-400 bg-violet-50 text-violet-700 dark:border-violet-600 dark:bg-violet-950/60 dark:text-violet-300'
                        : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-violet-400 hover:text-violet-700'"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span x-text="showPreview ? @js(__('messages.theme_btn_hide_preview')) : @js(__('messages.theme_btn_toggle_preview'))"></span>
                </button>

                {{-- Save Draft button --}}
                <button
                    type="button"
                    id="btn-save-draft"
                    @click="saveDraft()"
                    :disabled="draftStatus === 'saving' || draftStatus === 'publishing'"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-xs transition hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-violet-400 hover:text-violet-700 active:scale-95 disabled:opacity-50 cursor-pointer"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-3-5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 21V9h6v12"/></svg>
                    <span>{{ __('messages.theme_btn_save_draft') }}</span>
                </button>

                {{-- Publish button --}}
                <button
                    type="button"
                    id="btn-publish-theme"
                    @click="openPublishConfirm()"
                    :disabled="draftStatus === 'saving' || draftStatus === 'publishing'"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 hover:bg-violet-500 px-4 py-2 text-xs font-black text-white shadow-xs hover:shadow-violet-500/20 transition active:scale-95 disabled:opacity-50 cursor-pointer"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l7-7 7 7M12 5v14"/></svg>
                    <span>{{ __('messages.theme_btn_publish_live') }}</span>
                </button>
            </div>
        </div>

        {{-- Conflict warning banner --}}
        <div x-show="draftStatus === 'conflict'" x-cloak
             class="mt-3 flex items-start gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span x-text="conflictMsg || @js(__('messages.theme_conflict_warning'))"></span>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         LIVE PREVIEW — Collapsible isolated draft storefront (T3)
    ══════════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 transition-all duration-200">
        {{-- Collapsible Bar Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3 cursor-pointer select-none rounded-xl p-2.5 -m-2.5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition"
             @click="togglePreview()">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 text-sm font-bold shadow-xs">
                    🖼
                </span>
                <div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100 leading-tight flex items-center gap-2">
                        <span>{{ __('messages.theme_preview_title') }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200/60 dark:border-slate-700">
                            {{ __('messages.theme_preview_isolated') }}
                        </span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-normal mt-0.5">
                        {{ __('messages.theme_preview_desc') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-black transition cursor-pointer shadow-2xs"
                        :class="showPreview
                            ? 'border-violet-300 bg-violet-100 text-violet-800 dark:border-violet-700 dark:bg-violet-900/60 dark:text-violet-200'
                            : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50'">
                    <span x-text="showPreview ? @js('✖️ ' . __('messages.theme_btn_hide_preview')) : @js('👁️ ' . __('messages.theme_btn_open_preview'))"></span>
                    <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="{ 'rotate-180': showPreview }" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
        </div>

        {{-- Expanded Preview Area --}}
        <div x-show="showPreview"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="mt-4 space-y-3">

            {{-- Viewport toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('messages.theme_preview_viewport') }}</span>
                    {{-- Viewport segmented control --}}
                    <div class="inline-flex rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-0.5 shadow-xs" role="group" aria-label="Preview viewport size">
                        <button type="button" @click="setViewport('desktop')"
                                :class="previewViewport === 'desktop' ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                class="rounded-lg px-3 py-1 text-[11px] font-black transition cursor-pointer" aria-pressed="false">
                            🖥 {{ __('messages.theme_preview_desktop') }}
                        </button>
                        <button type="button" @click="setViewport('tablet')"
                                :class="previewViewport === 'tablet' ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                class="rounded-lg px-3 py-1 text-[11px] font-black transition cursor-pointer" aria-pressed="false">
                            📱 {{ __('messages.theme_preview_tablet') }}
                        </button>
                        <button type="button" @click="setViewport('mobile')"
                                :class="previewViewport === 'mobile' ? 'bg-violet-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                                class="rounded-lg px-3 py-1 text-[11px] font-black transition cursor-pointer" aria-pressed="false">
                            📲 {{ __('messages.theme_preview_mobile') }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Refresh Button --}}
                    <button type="button" @click="refreshPreview()"
                            title="Reload preview frame"
                            class="inline-flex items-center gap-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1 text-[11px] font-black text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs cursor-pointer active:scale-95">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>{{ __('messages.theme_preview_refresh') }}</span>
                    </button>
                </div>
            </div>

            {{-- Browser Window Mockup Frame --}}
            <div class="rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-900 shadow-lg overflow-hidden">
                {{-- Window Title Bar --}}
                <div class="h-8 px-3.5 bg-slate-800/90 border-b border-slate-700/80 flex items-center justify-between text-xs select-none">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="px-4 py-0.5 rounded-md bg-slate-900/60 border border-slate-700/60 text-[10px] font-mono text-slate-400 flex items-center gap-1.5">
                        <span class="text-emerald-400 text-xs">🔒</span>
                        <span class="truncate max-w-[240px]">storefront/preview?draft=active</span>
                    </div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                        <span x-text="previewViewport.toUpperCase()"></span> VIEW
                    </div>
                </div>

                {{-- Preview frame — overflow-x-auto so wide viewports never stretch the admin page --}}
                <div class="relative bg-slate-100 dark:bg-slate-950 overflow-x-auto p-2 sm:p-4">
                    <div class="mx-auto transition-all duration-300 rounded-xl overflow-hidden shadow-md" :style="'width:' + (previewViewport === 'mobile' ? '390px' : previewViewport === 'tablet' ? '768px' : '1440px') + '; min-height: 640px;'">
                        <iframe :src="previewSrc"
                                @load="onPreviewLoaded()"
                                class="block w-full border-0 bg-white"
                                style="height: 640px;"
                                title="Storefront draft preview"
                                loading="lazy"></iframe>
                    </div>

                    {{-- Loading overlay --}}
                    <div x-show="!previewLoaded" x-cloak
                         class="pointer-events-none absolute inset-0 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs">
                        <span class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-4 py-2 text-xs font-black text-slate-700 dark:text-slate-200 shadow-xl">
                            <svg class="animate-spin h-4 w-4 text-violet-600" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            {{ __('messages.theme_preview_loading') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 1 — Choose a Template
    ══════════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 space-y-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-600 text-xs font-black text-white shadow-xs">
                1
            </span>
            <div>
                <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100">
                    {{ __('messages.theme_step1_title') }}
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_step1_desc') }}</p>
            </div>
        </div>

        {{-- Template Card Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-3.5">

            @foreach ($templates as $tKey => $tpl)
            @php
                $tplPrimary = $tKey === 'custom' ? $colors['primary'] : $tpl['primary'];
                $tplAccent  = $tKey === 'custom' ? $colors['accent'] : $tpl['accent'];
                $tplHeader  = $tKey === 'custom' ? $colors['header_bg'] : $tpl['header_bg'];
                $tplBody    = $tKey === 'custom' ? ($colors['body_bg'] ?? '#f8fafc') : $tpl['body_bg'];
                $isDark     = $tpl['dark_mode'] === 'dark';
                $cardBg     = $isDark ? '#1e293b' : '#ffffff';
                $cardBorder = $isDark ? '#334155' : '#e2e8f0';
                $surfaceBg  = $isDark ? '#0f172a' : $tplBody;
                $searchBg   = $isDark ? '#1e293b' : '#f1f5f9';
            @endphp
            <button
                type="button"
                @click="selectTemplate('{{ $tKey }}')"
                :class="preset === '{{ $tKey }}'
                    ? 'border-violet-500 border-b-[4px] border-b-violet-700 ring-2 ring-violet-500/40 bg-violet-50/25 dark:bg-violet-950/30 shadow-lg -translate-y-0.5'
                    : 'border-slate-200 border-b-[3px] border-b-slate-300/90 dark:border-slate-700/80 dark:border-b-slate-800 bg-white dark:bg-slate-800/90 hover:border-slate-300 hover:border-b-slate-400 dark:hover:border-b-slate-700 hover:-translate-y-0.5 active:translate-y-0.5 active:border-b-[1.5px]'"
                class="sf-btn-3d relative overflow-hidden rounded-2xl border text-left transition-all duration-150 focus:outline-none cursor-pointer flex flex-col justify-between group select-none shadow-xs"
            >
                {{-- Badge --}}
                @if ($tpl['badge'])
                    <span class="absolute right-2.5 top-2.5 z-10 rounded-full {{ $tpl['badge_color'] }} px-2.5 py-0.5 text-[9px] font-black text-white shadow-xs tracking-wide">
                        {{ $tpl['badge'] }}
                    </span>
                @endif
                {{-- Deprecated lifecycle badge (T7) --}}
                @if (($themeStatuses[$tKey] ?? 'active') === 'deprecated')
                    <span class="absolute left-2.5 top-2.5 z-10 rounded-full bg-amber-500 px-2 py-0.5 text-[9px] font-black text-white shadow-xs">
                        ⚠️ Deprecated
                    </span>
                @endif

                {{-- ── Realistic Storefront Mini-Mockup (SVG) ── --}}
                <div class="relative w-full overflow-hidden border-b border-slate-100 dark:border-slate-700/60" style="padding-bottom: 58%;">
                    <svg class="absolute inset-0 h-full w-full" viewBox="0 0 240 144" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="goldHeartGrad_{{ $tKey }}" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#fcd34d"/>
                                <stop offset="50%" stop-color="#fbbf24"/>
                                <stop offset="100%" stop-color="#d97706"/>
                            </linearGradient>
                            <linearGradient id="primaryGrad_{{ $tKey }}" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="{{ $tplPrimary }}"/>
                                <stop offset="100%" stop-color="{{ $tplPrimary }}" stop-opacity="0.85"/>
                            </linearGradient>
                        </defs>

                        <!-- Page Surface Background -->
                        <rect width="240" height="144" fill="{{ $surfaceBg }}"/>

                        <!-- Ambient Glow Orbs -->
                        <circle cx="20" cy="50" r="38" fill="{{ $tplPrimary }}" fill-opacity="0.12"/>
                        <circle cx="220" cy="110" r="42" fill="{{ $tplAccent }}" fill-opacity="0.10"/>

                        <!-- ── 1. Sticky Storefront Header (y: 0–26) ── -->
                        <rect width="240" height="26" fill="{{ $tplHeader }}"/>
                        <line x1="0" y1="26" x2="240" y2="26" stroke="{{ $isDark ? '#334155' : '#e2e8f0' }}" stroke-width="1"/>

                        <!-- Logo Monogram Box -->
                        <rect x="7" y="6" width="20" height="14" rx="3.5" fill="url(#primaryGrad_{{ $tKey }})"/>
                        <rect x="11" y="10" width="12" height="6" rx="1.5" fill="#ffffff" fill-opacity="0.9"/>

                        <!-- Search Bar -->
                        <rect x="31" y="6" width="118" height="14" rx="4.5" fill="{{ $searchBg }}" stroke="{{ $tplPrimary }}" stroke-opacity="0.35" stroke-width="0.75"/>
                        <rect x="36" y="11" width="36" height="4" rx="1.5" fill="{{ $isDark ? '#64748b' : '#94a3b8' }}"/>

                        <!-- Search CTA Button -->
                        <rect x="127" y="7" width="20" height="12" rx="3" fill="{{ $tplAccent }}"/>
                        <rect x="131" y="11" width="12" height="4" rx="1" fill="#ffffff" fill-opacity="0.9"/>

                        <!-- 3D Header Action Icons (Wishlist, Language, Cart) -->
                        <circle cx="162" cy="13" r="5" fill="#f43f5e" fill-opacity="0.18"/>
                        <path d="M162 14.8l-2.2-2.2c-.6-.6-.6-1.5 0-2.1.6-.6 1.5-.6 2.1 0 .6-.6 1.5-.6 2.1 0 .6.6.6 1.5 0 2.1L162 14.8z" fill="#f43f5e"/>

                        <circle cx="178" cy="13" r="5" fill="{{ $tplPrimary }}" fill-opacity="0.18"/>
                        <rect x="175" y="11" width="6" height="4" rx="1" fill="{{ $tplPrimary }}"/>

                        <circle cx="194" cy="13" r="5.5" fill="{{ $tplPrimary }}" fill-opacity="0.2"/>
                        <rect x="191" y="10" width="6" height="5" rx="1" fill="none" stroke="{{ $tplPrimary }}" stroke-width="1.2"/>
                        <!-- Cart notification badge -->
                        <circle cx="198" cy="9.5" r="2.2" fill="#ef4444"/>

                        <circle cx="210" cy="13" r="5" fill="#64748b" fill-opacity="0.15"/>
                        <circle cx="210" cy="13" r="2.5" fill="{{ $isDark ? '#fbbf24' : '#64748b' }}"/>

                        <!-- ── 2. Category Nav Strip (y: 26–39) ── -->
                        <rect y="26" width="240" height="13" fill="{{ $tplPrimary }}" fill-opacity="0.07"/>
                        <!-- Active category pill -->
                        <rect x="7" y="28.5" width="28" height="8" rx="2.5" fill="url(#primaryGrad_{{ $tKey }})"/>
                        <rect x="11" y="31" width="20" height="3" rx="1" fill="#ffffff"/>
                        <!-- Other category pills -->
                        <rect x="39" y="28.5" width="24" height="8" rx="2.5" fill="{{ $isDark ? '#334155' : '#e2e8f0' }}"/>
                        <rect x="67" y="28.5" width="26" height="8" rx="2.5" fill="{{ $isDark ? '#334155' : '#e2e8f0' }}"/>
                        <rect x="97" y="28.5" width="24" height="8" rx="2.5" fill="{{ $isDark ? '#334155' : '#e2e8f0' }}"/>
                        <rect x="125" y="28.5" width="26" height="8" rx="2.5" fill="{{ $isDark ? '#334155' : '#e2e8f0' }}"/>

                        <!-- ── 3. Hero Section & 4 Value Trust Badges (y: 41–64) ── -->
                        <rect x="7" y="41" width="226" height="23" rx="4" fill="{{ $tplPrimary }}" fill-opacity="0.12"/>
                        <rect x="14" y="46" width="56" height="5" rx="1.5" fill="{{ $tplPrimary }}"/>
                        <rect x="14" y="53" width="34" height="4" rx="1.2" fill="{{ $tplAccent }}"/>

                        <!-- 4 Value Trust Strip Badges -->
                        @foreach ([7, 65, 123, 181] as $tIdx => $tx)
                            <rect x="{{ $tx }}" y="66" width="52" height="7" rx="2" fill="{{ $cardBg }}" stroke="{{ $cardBorder }}" stroke-width="0.6"/>
                            <circle cx="{{ $tx + 5 }}" cy="69.5" r="2" fill="{{ $tIdx === 0 ? '#38bdf8' : ($tIdx === 1 ? '#10b981' : ($tIdx === 2 ? '#f59e0b' : '#7c3aed')) }}"/>
                            <rect x="{{ $tx + 9 }}" y="68.5" width="36" height="2" rx="0.75" fill="{{ $isDark ? '#94a3b8' : '#64748b' }}"/>
                        @endforeach

                        <!-- ── 4. Product Showcase Cards Grid (y: 75–134) ── -->
                        @foreach ([7, 65, 123, 181] as $pIdx => $px)
                            <!-- 3D Card Box -->
                            <rect x="{{ $px }}" y="76" width="52" height="57" rx="4" fill="{{ $cardBg }}" stroke="{{ $cardBorder }}" stroke-width="0.75"/>
                            <line x1="{{ $px }}" y1="133" x2="{{ $px + 52 }}" y2="133" stroke="{{ $isDark ? '#0f172a' : '#cbd5e1' }}" stroke-width="1.5"/>

                            <!-- Product Thumbnail Image -->
                            <rect x="{{ $px + 2.5 }}" y="78.5" width="47" height="26" rx="3" fill="{{ $tplPrimary }}" fill-opacity="0.10"/>

                            <!-- 3D Gold Wishlist Heart Button -->
                            <rect x="{{ $px + 36 }}" y="80.5" width="11" height="9" rx="2" fill="url(#goldHeartGrad_{{ $tKey }})" stroke="#b45309" stroke-width="0.4"/>
                            <path d="M{{ $px + 41.5 }} 86l-2-2c-.5-.5-.5-1.2 0-1.7.5-.5 1.2-.5 1.7 0 .5-.5 1.2-.5 1.7 0 .5.5.5 1.2 0 1.7L{{ $px + 41.5 }} 86z" fill="#ffffff"/>

                            <!-- Title skeleton -->
                            <rect x="{{ $px + 3.5 }}" y="107.5" width="32" height="3.5" rx="1.2" fill="{{ $isDark ? '#94a3b8' : '#64748b' }}"/>

                            <!-- Price Tag -->
                            <rect x="{{ $px + 3.5 }}" y="113.5" width="22" height="4.5" rx="1.2" fill="{{ $tplPrimary }}"/>

                            <!-- 3D Add to Cart Button -->
                            <rect x="{{ $px + 33 }}" y="119" width="16" height="11" rx="2.5" fill="url(#primaryGrad_{{ $tKey }})"/>
                            <line x1="{{ $px + 33 }}" y1="130" x2="{{ $px + 49 }}" y2="130" stroke="{{ $isDark ? '#020617' : '#0369a1' }}" stroke-width="1"/>
                            <rect x="{{ $px + 37 }}" y="123" width="8" height="3" rx="0.75" fill="#ffffff"/>
                        @endforeach

                        <!-- ── 5. Clean Footer Strip (y: 136–144) ── -->
                        <rect y="136" width="240" height="8" fill="{{ $tplPrimary }}" fill-opacity="0.15"/>
                        <rect x="7" y="139" width="28" height="2.5" rx="1" fill="{{ $tplPrimary }}" fill-opacity="0.5"/>
                        <rect x="180" y="139" width="53" height="2.5" rx="1" fill="{{ $tplPrimary }}" fill-opacity="0.3"/>
                    </svg>
                </div>

                {{-- Card footer --}}
                <div class="p-3 bg-white/50 dark:bg-slate-800/50 backdrop-blur-xs">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-xs font-black text-slate-900 dark:text-white truncate">{{ $tpl['name'] }}</span>
                        {{-- Selected checkmark --}}
                        <span x-show="preset === '{{ $tKey }}'" x-cloak
                            class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-violet-600 text-white text-[10px] font-bold shrink-0 shadow-xs ring-2 ring-violet-200 dark:ring-violet-800">
                            ✓
                        </span>
                    </div>
                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 leading-relaxed">{{ $tpl['desc'] }}</p>

                    {{-- Color swatches ribbon --}}
                    <div class="mt-2.5 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-700/60">
                        <div class="flex -space-x-1">
                            <span class="h-4 w-4 rounded-full border border-white dark:border-slate-800 shadow-xs ring-1 ring-slate-200/80 dark:ring-slate-700"
                                  style="background:{{ $tplPrimary }}"
                                  title="Primary"></span>
                            <span class="h-4 w-4 rounded-full border border-white dark:border-slate-800 shadow-xs ring-1 ring-slate-200/80 dark:ring-slate-700"
                                  style="background:{{ $tplAccent }}"
                                  title="Accent"></span>
                            <span class="h-4 w-4 rounded-full border border-white dark:border-slate-800 shadow-xs ring-1 ring-slate-200/80 dark:ring-slate-700"
                                  style="background:{{ $tplHeader }}"
                                  title="Header"></span>
                            <span class="h-4 w-4 rounded-full border border-white dark:border-slate-800 shadow-xs ring-1 ring-slate-200/80 dark:ring-slate-700"
                                  style="background:{{ $tplBody }}"
                                  title="Body"></span>
                        </div>
                        <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300">
                            {{ $isDark ? '🌙 Dark' : '☀️ Light' }}
                        </span>
                    </div>
                </div>
            </button>
            @endforeach

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 2 — Live Preview + Fine-Tune Colors
    ══════════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-600 text-xs font-black text-white shadow-xs">
                    2
                </span>
                <div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100">
                        {{ __('messages.theme_step2_title') }}
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_step2_desc') }}</p>
                </div>
            </div>

            <button type="button" @click="resetToTemplate()"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 transition hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-violet-600 dark:hover:text-violet-400 shadow-xs active:scale-95 cursor-pointer">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>{{ __('messages.theme_reset_preset') }}</span>
            </button>
        </div>

        {{-- Live preview strip with interactive body background, ambient glow, and dark mode preview --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 shadow-md transition-all duration-300"
             :style="'background:' + (preview_dark ? '#0b0f19' : colors.body_bg)">

            {{-- Live Ambient Glow Orbs in preview --}}
            <div x-show="colors.glow_style !== 'none'" class="absolute -top-12 -left-12 w-48 h-48 rounded-full blur-3xl pointer-events-none transition-all duration-300"
                 :style="'background:' + colors.primary + '; opacity:' + (colors.glow_style === 'vivid' ? (preview_dark ? '0.45' : '0.35') : (preview_dark ? '0.22' : '0.15'))"></div>
            <div x-show="colors.glow_style !== 'none'" class="absolute top-1/2 -right-12 w-48 h-48 rounded-full blur-3xl pointer-events-none transition-all duration-300"
                 :style="'background:' + colors.accent + '; opacity:' + (colors.glow_style === 'vivid' ? (preview_dark ? '0.45' : '0.35') : (preview_dark ? '0.22' : '0.15'))"></div>

            {{-- Storefront Interactive 3D Sticky Header --}}
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-2 px-3.5 py-2.5 transition-colors border-b shadow-xs"
                 :style="'background:' + (preview_dark && colors.header_bg === '#ffffff' ? '#1e293b' : colors.header_bg) + '; border-color:' + (preview_dark ? '#334155' : '#e2e8f0')">
                <div class="flex items-center gap-2">
                    <span class="h-7 w-7 rounded-xl grid place-items-center text-white text-xs font-black shadow-xs transition-colors"
                          :style="'background:' + colors.primary">
                        🏪
                    </span>
                    <div>
                        <span class="text-xs font-black tracking-tight flex items-center gap-1.5"
                              :class="preview_dark ? 'text-white' : 'text-slate-900'">
                            <span>{{ $store->name ?? 'DataPOS Store' }}</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full text-white shadow-2xs"
                                  :style="'background:' + colors.primary">VERIFIED</span>
                        </span>
                    </div>
                </div>

                {{-- Interactive Search Bar Mockup --}}
                <div class="flex-1 min-w-[200px] max-w-md mx-auto flex items-center">
                    <div class="relative w-full flex items-center">
                        <input type="text"
                               disabled
                               :placeholder="@js(__('messages.theme_preview_search_ph'))"
                               class="w-full h-8 rounded-xl border text-xs px-3 font-medium transition-colors shadow-2xs select-none"
                               :style="'border-color:' + colors.primary + '60; color:' + (preview_dark ? '#cbd5e1' : '#64748b') + '; background:' + (preview_dark ? 'rgba(30,41,59,0.85)' : '#f8fafc')">
                        <button type="button"
                                class="absolute right-1 px-2.5 py-1 rounded-lg text-[11px] font-black transition-colors shadow-xs flex items-center gap-1"
                                :style="'background:' + colors.accent + '; color:' + contrastText(colors.accent)">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span>{{ __('messages.theme_preview_search_btn') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Header Action 3D Icons --}}
                <div class="flex items-center gap-1.5">
                    {{-- Favorites icon button --}}
                    <span class="h-7 w-7 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-gradient-to-b from-rose-50 to-rose-100 dark:from-rose-950/60 dark:to-rose-900/40 grid place-items-center text-rose-500 text-xs shadow-xs border-b-[2px] border-b-rose-300 dark:border-b-rose-800">
                        ❤️
                    </span>
                    {{-- Language switcher button --}}
                    <span class="h-7 px-2 rounded-xl border border-sky-200 dark:border-sky-900/60 bg-gradient-to-b from-sky-50 to-blue-100 dark:from-sky-950/60 dark:to-blue-900/40 flex items-center gap-1 text-[10px] font-bold text-sky-700 dark:text-sky-300 shadow-xs border-b-[2px] border-b-sky-300 dark:border-b-sky-800">
                        🇲🇲 MM
                    </span>
                    {{-- Cart button with count --}}
                    <span class="relative h-7 px-2.5 rounded-xl border border-emerald-200 dark:border-emerald-900/60 bg-gradient-to-b from-emerald-50 to-teal-100 dark:from-emerald-950/60 dark:to-teal-900/40 flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 shadow-xs border-b-[2px] border-b-emerald-300 dark:border-b-emerald-800">
                        🛒
                        <span class="absolute -top-1 -right-1 h-4 min-w-4 px-1 rounded-full bg-rose-500 text-white text-[9px] font-black grid place-items-center shadow-xs ring-1 ring-white">2</span>
                    </span>
                </div>
            </div>

            {{-- Category Nav Strip --}}
            <div class="relative z-10 flex items-center gap-1.5 px-3.5 py-1.5 text-[11px] font-bold transition-colors overflow-x-auto"
                 :style="'background:' + colors.primary + (preview_dark ? '25' : '12')">
                <span class="rounded-lg px-2.5 py-1 text-white transition-all shadow-xs border-b-[2px] border-b-black/20"
                      :style="'background:' + colors.primary">
                    ⭐ {{ __('messages.all') ?? 'All' }}
                </span>
                <span class="rounded-lg px-2 py-1 font-semibold transition"
                      :class="preview_dark ? 'text-slate-200 hover:bg-slate-800/50' : 'text-slate-700 hover:bg-white/50'">
                    📱 {{ __('messages.nav_phone') ?? 'Smartphones' }}
                </span>
                <span class="rounded-lg px-2 py-1 font-semibold transition"
                      :class="preview_dark ? 'text-slate-200 hover:bg-slate-800/50' : 'text-slate-700 hover:bg-white/50'">
                    🎧 {{ __('messages.nav_accessories') ?? 'Accessories' }}
                </span>
                <span class="rounded-lg px-2 py-1 font-semibold transition"
                      :class="preview_dark ? 'text-slate-200 hover:bg-slate-800/50' : 'text-slate-700 hover:bg-white/50'">
                    📹 CCTV & Networking
                </span>
                <span class="rounded-lg px-2 py-1 font-semibold transition"
                      :class="preview_dark ? 'text-slate-200 hover:bg-slate-800/50' : 'text-slate-700 hover:bg-white/50'">
                    💻 Computer & PC
                </span>
            </div>

            {{-- 4-Item Value Trust Strip (Section 6.1 standard) --}}
            <div class="relative z-10 grid grid-cols-2 sm:grid-cols-4 gap-2 px-3.5 py-2">
                @foreach ([
                    ['icon' => '⚡', 'title' => __('messages.fast_delivery') ?? 'အမြန်ဆုံး ပို့ဆောင်မှု', 'sub' => 'Township Delivery'],
                    ['icon' => '🛡️', 'title' => __('messages.genuine_warranty') ?? 'စစ်မှန်သော အာမခံ', 'sub' => '100% Original'],
                    ['icon' => '🔧', 'title' => __('messages.service_tracking') ?? 'ကျွမ်းကျင် ဆာဗစ်', 'sub' => 'Expert Tech Care'],
                    ['icon' => '💬', 'title' => __('messages.direct_support') ?? 'တိုက်ရိုက် အကူအညီ', 'sub' => 'Viber / Telegram'],
                ] as $tIdx => $tCard)
                <div class="flex items-center gap-2 p-1.5 rounded-xl border border-slate-200/70 dark:border-slate-700/60 shadow-2xs transition-colors"
                     :class="preview_dark ? 'bg-slate-900/60' : 'bg-white/80'">
                    <span class="text-sm shrink-0">{{ $tCard['icon'] }}</span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-black truncate" :class="preview_dark ? 'text-white' : 'text-slate-900'">{{ $tCard['title'] }}</p>
                        <p class="text-[8.5px] text-slate-400 font-mono truncate">{{ $tCard['sub'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Product Showcase Row Mockup --}}
            <div class="relative z-10 grid grid-cols-2 sm:grid-cols-4 gap-2.5 px-3.5 py-2.5">
                @php
                    $demoProducts = [
                        ['title' => 'Redmi Note 13 4G (8/256GB)', 'price' => '685,000', 'tag' => 'Official Warranty'],
                        ['title' => 'Anker 20W Fast Charger Kit',  'price' => '42,000',  'tag' => 'Fast Delivery'],
                        ['title' => 'Hikvision 2MP CCTV Camera',   'price' => '89,000',  'tag' => 'Security Pro'],
                        ['title' => 'Logitech G102 Gaming Mouse',  'price' => '58,000',  'tag' => 'Gaming Gear'],
                    ];
                @endphp
                @foreach ($demoProducts as $pIdx => $dp)
                <div class="relative overflow-hidden rounded-2xl border shadow-xs transition-colors flex flex-col justify-between"
                     :class="preview_dark ? 'border-slate-700/80 bg-slate-800/90' : 'border-slate-200/80 bg-white/95'">
                    
                    {{-- Product Image Box --}}
                    <div class="relative w-full aspect-square transition-colors flex items-center justify-center p-3"
                         :style="'background:' + colors.primary + (preview_dark ? '20' : '10')">
                        <span class="text-2xl filter drop-shadow-sm">{{ $pIdx === 0 ? '📱' : ($pIdx === 1 ? '🔌' : ($pIdx === 2 ? '📹' : '🖱️')) }}</span>
                        
                        {{-- 3D Gold Wishlist Heart Button (Standard v1.0 §10.3) --}}
                        <span class="absolute top-1.5 right-1.5 h-6 w-6 rounded-lg bg-gradient-to-b from-amber-300 via-amber-400 to-amber-600 border border-amber-200 border-b-[2px] border-b-amber-700 shadow-xs grid place-items-center text-white text-[10px]">
                            ❤️
                        </span>
                    </div>

                    {{-- Product Info --}}
                    <div class="p-2.5 space-y-1.5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded text-white"
                                  :style="'background:' + colors.accent">
                                {{ $dp['tag'] }}
                            </span>
                            <h4 class="text-[11px] font-bold font-myanmar mt-1 line-clamp-1"
                                :class="preview_dark ? 'text-slate-100' : 'text-slate-800'">
                                {{ $dp['title'] }}
                            </h4>
                            <p class="text-xs font-black mt-0.5"
                               :style="'color:' + colors.primary">
                                {{ $dp['price'] }} <span class="text-[9px] font-normal text-slate-400">MMK</span>
                            </p>
                        </div>

                        {{-- 3D Primary Add to Cart Button (Standard v1.0 §4.2) --}}
                        <button type="button"
                                class="w-full py-1.5 px-2 rounded-xl text-[10px] font-black text-white transition-transform active:translate-y-0.5 border-b-[2.5px] border-b-black/30 shadow-xs flex items-center justify-center gap-1"
                                :style="'background:' + colors.primary">
                            <span>🛒</span>
                            <span>{{ __('messages.add_to_cart') ?? 'Add to Cart' }}</span>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Preview footer bar with interactive Dark/Light toggle --}}
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-2 border-t px-4 py-2.5 backdrop-blur-xs transition-colors"
                 :class="preview_dark ? 'border-slate-800 bg-slate-900/90 text-white' : 'border-slate-200/80 bg-white/90 text-slate-800'">
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span class="text-[11px] font-bold" :class="preview_dark ? 'text-slate-300' : 'text-slate-600'">{{ __('messages.theme_atmosphere_test') }}</span>

                    {{-- Mini Preview Dark Mode Toggle --}}
                    <button type="button" @click="togglePreviewDark()"
                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[10px] font-black transition shadow-xs cursor-pointer active:scale-95"
                            :class="preview_dark ? 'border-amber-400/40 bg-amber-950/40 text-amber-300' : 'border-slate-200 bg-slate-100 text-slate-700'">
                        <span x-text="preview_dark ? '🌙 Dark Mode' : '☀️ Light Mode'"></span>
                    </button>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex h-5 items-center rounded-full px-2 text-[10px] font-bold text-white shadow-xs"
                          :style="'background:' + colors.primary">{{ __('messages.theme_color_primary') }}</span>
                    <span class="inline-flex h-5 items-center rounded-full px-2 text-[10px] font-bold text-white shadow-xs"
                          :style="'background:' + colors.accent">{{ __('messages.theme_color_accent') }}</span>
                    <span class="inline-flex h-5 items-center rounded-full border px-2 text-[10px] font-bold shadow-xs"
                          :class="preview_dark ? 'text-slate-200 border-slate-600' : 'text-slate-700 border-slate-300'"
                          :style="'background:' + (preview_dark && colors.header_bg === '#ffffff' ? '#1e293b' : colors.header_bg)">
                        Header
                    </span>
                    <span class="inline-flex h-5 items-center rounded-full border px-2 text-[10px] font-bold shadow-xs"
                          :class="preview_dark ? 'text-slate-200 border-slate-600' : 'text-slate-700 border-slate-300'"
                          :style="'background:' + (preview_dark ? '#0b0f19' : colors.body_bg)">
                        Body BG
                    </span>
                </div>
            </div>
        </div>

        {{-- 4 colour pickers in a row (Primary, Accent, Header BG, Body BG) --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

            {{-- 1. Primary Color --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-700/80 dark:bg-slate-800/60 shadow-xs">
                <div class="h-8 w-full transition-colors flex items-center justify-end px-2" :style="'background:' + colors.primary">
                    <span class="text-[9px] font-black uppercase text-white/90 bg-black/20 px-1.5 py-0.5 rounded">{{ __('messages.theme_color_primary') }}</span>
                </div>
                <div class="p-3.5 space-y-2">
                    <label class="block text-xs font-black text-slate-800 dark:text-slate-100">
                        {{ __('messages.theme_primary_color') }}
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1">{{ __('messages.theme_primary_color_help') }}</p>
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="color"
                            name="theme_primary_color"
                            x-model="colors.primary"
                            @input="onCustomColorChange()"
                            class="h-9 w-10 cursor-pointer rounded-xl border border-slate-300 p-0.5 dark:border-slate-600 flex-shrink-0 shadow-xs"
                        >
                        <input
                            type="text"
                            x-model="colors.primary"
                            @input="sanitizeHex('primary', $event.target.value); onCustomColorChange()"
                            maxlength="7"
                            placeholder="#0ea5e9"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-900 px-3 py-2 font-mono text-xs text-slate-800 dark:text-slate-100 shadow-xs focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500/30 dark:border-slate-700"
                        >
                    </div>
                </div>
            </div>

            {{-- 2. Accent Color --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-700/80 dark:bg-slate-800/60 shadow-xs">
                <div class="h-8 w-full transition-colors flex items-center justify-end px-2" :style="'background:' + colors.accent">
                    <span class="text-[9px] font-black uppercase text-white/90 bg-black/20 px-1.5 py-0.5 rounded">{{ __('messages.theme_color_accent') }}</span>
                </div>
                <div class="p-3.5 space-y-2">
                    <label class="block text-xs font-black text-slate-800 dark:text-slate-100">
                        {{ __('messages.theme_accent_color') }}
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1">{{ __('messages.theme_accent_color_help') }}</p>
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="color"
                            name="theme_accent_color"
                            x-model="colors.accent"
                            @input="onCustomColorChange()"
                            class="h-9 w-10 cursor-pointer rounded-xl border border-slate-300 p-0.5 dark:border-slate-600 flex-shrink-0 shadow-xs"
                        >
                        <input
                            type="text"
                            x-model="colors.accent"
                            @input="sanitizeHex('accent', $event.target.value); onCustomColorChange()"
                            maxlength="7"
                            placeholder="#7c3aed"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-900 px-3 py-2 font-mono text-xs text-slate-800 dark:text-slate-100 shadow-xs focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500/30 dark:border-slate-700"
                        >
                    </div>
                </div>
            </div>

            {{-- 3. Header BG --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-700/80 dark:bg-slate-800/60 shadow-xs">
                <div class="h-8 w-full border-b border-slate-200 dark:border-slate-700 transition-colors flex items-center justify-end px-2"
                     :style="'background:' + colors.header_bg">
                    <span class="text-[9px] font-black uppercase text-slate-700 dark:text-slate-200 bg-white/70 dark:bg-black/40 px-1.5 py-0.5 rounded">{{ __('messages.theme_color_header') }}</span>
                </div>
                <div class="p-3.5 space-y-2">
                    <label class="block text-xs font-black text-slate-800 dark:text-slate-100">
                        {{ __('messages.theme_header_bg') }}
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1">{{ __('messages.theme_header_bg_help') }}</p>
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="color"
                            name="theme_header_bg"
                            x-model="colors.header_bg"
                            @input="onCustomColorChange()"
                            class="h-9 w-10 cursor-pointer rounded-xl border border-slate-300 p-0.5 dark:border-slate-600 flex-shrink-0 shadow-xs"
                        >
                        <input
                            type="text"
                            x-model="colors.header_bg"
                            @input="sanitizeHex('header_bg', $event.target.value); onCustomColorChange()"
                            maxlength="7"
                            placeholder="#ffffff"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-900 px-3 py-2 font-mono text-xs text-slate-800 dark:text-slate-100 shadow-xs focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500/30 dark:border-slate-700"
                        >
                    </div>
                </div>
            </div>

            {{-- 4. Body BG --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-700/80 dark:bg-slate-800/60 shadow-xs">
                <div class="h-8 w-full border-b border-slate-200 dark:border-slate-700 transition-colors flex items-center justify-end px-2"
                     :style="'background:' + colors.body_bg">
                    <span class="text-[9px] font-black uppercase text-slate-700 dark:text-slate-200 bg-white/70 dark:bg-black/40 px-1.5 py-0.5 rounded">{{ __('messages.theme_color_body_bg') }}</span>
                </div>
                <div class="p-3.5 space-y-2">
                    <label class="block text-xs font-black text-slate-800 dark:text-slate-100">
                        {{ __('messages.theme_body_bg') }}
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1">{{ __('messages.theme_body_bg_help') }}</p>
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="color"
                            name="theme_body_bg"
                            x-model="colors.body_bg"
                            @input="onCustomColorChange()"
                            class="h-9 w-10 cursor-pointer rounded-xl border border-slate-300 p-0.5 dark:border-slate-600 flex-shrink-0 shadow-xs"
                        >
                        <input
                            type="text"
                            x-model="colors.body_bg"
                            @input="sanitizeHex('body_bg', $event.target.value); onCustomColorChange()"
                            maxlength="7"
                            placeholder="#f8fafc"
                            class="block w-full rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-900 px-3 py-2 font-mono text-xs text-slate-800 dark:text-slate-100 shadow-xs focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500/30 dark:border-slate-700"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 3 — Ambient Glow Style
    ══════════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 space-y-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-600 text-xs font-black text-white shadow-xs">
                3
            </span>
            <div>
                <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100">
                    {{ __('messages.theme_glow_style_label') }}
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_glow_style_help') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach ([
                'vivid'  => ['emoji' => '✨', 'title' => __('messages.theme_glow_vivid'),  'desc' => __('messages.theme_glow_vivid_desc')],
                'subtle' => ['emoji' => '🌫️', 'title' => __('messages.theme_glow_subtle'), 'desc' => __('messages.theme_glow_subtle_desc')],
                'none'   => ['emoji' => '🚫', 'title' => __('messages.theme_glow_none'),   'desc' => __('messages.theme_glow_none_desc')],
            ] as $gVal => $gItem)
                <button
                    type="button"
                    @click="colors.glow_style = '{{ $gVal }}'; onCustomColorChange()"
                    :class="colors.glow_style === '{{ $gVal }}'
                        ? 'border-violet-500 bg-violet-50/50 text-violet-900 ring-2 ring-violet-500/30 dark:border-violet-400 dark:bg-violet-950/40 dark:text-violet-200 shadow-xs'
                        : 'border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/60'"
                    class="flex flex-col items-start gap-1 rounded-2xl border p-4 text-left transition shadow-2xs cursor-pointer focus:outline-none"
                >
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ $gItem['emoji'] }}</span>
                            <span class="text-xs font-black">{{ $gItem['title'] }}</span>
                        </div>
                        <span x-show="colors.glow_style === '{{ $gVal }}'" x-cloak
                              class="w-4 h-4 rounded-full bg-violet-600 text-white text-[10px] font-bold grid place-items-center">✓</span>
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ $gItem['desc'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 4 — Dark Mode Preference
    ══════════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 space-y-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-600 text-xs font-black text-white shadow-xs">
                4
            </span>
            <div>
                <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100">
                    {{ __('messages.theme_dark_mode_label') }}
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_dark_mode_help') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach ([
                'auto'  => ['emoji' => '🌓', 'label' => __('messages.theme_dark_auto'),  'desc' => __('messages.theme_dark_auto_desc')],
                'light' => ['emoji' => '☀️', 'label' => __('messages.theme_dark_light'), 'desc' => __('messages.theme_dark_light_desc')],
                'dark'  => ['emoji' => '🌙', 'label' => __('messages.theme_dark_dark'),  'desc' => __('messages.theme_dark_dark_desc')],
            ] as $val => $item)
                <button
                    type="button"
                    @click="setDarkMode('{{ $val }}')"
                    :class="dark_mode === '{{ $val }}'
                        ? 'border-violet-500 bg-violet-50/50 text-violet-900 ring-2 ring-violet-500/40 dark:border-violet-400 dark:bg-violet-950/40 dark:text-violet-200 shadow-xs'
                        : 'border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/60'"
                    class="flex flex-col items-start gap-1 rounded-2xl border p-4 text-left transition shadow-2xs cursor-pointer focus:outline-none"
                >
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ $item['emoji'] }}</span>
                            <span class="text-xs font-black">{{ $item['label'] }}</span>
                        </div>
                        <span x-show="dark_mode === '{{ $val }}'" x-cloak
                              class="w-4 h-4 rounded-full bg-violet-600 text-white text-[10px] font-bold grid place-items-center">✓</span>
                    </div>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ $item['desc'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 5 — Font Family & Typography Preset
    ══════════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 space-y-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-600 text-xs font-black text-white shadow-xs">
                5
            </span>
            <div>
                <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100">
                    {{ __('messages.theme_step5_title') }}
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_step5_desc') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach (\App\Themes\ThemeRegistry::FONT_PRESETS as $fKey => $fItem)
                <label class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition shadow-2xs {{ ($setting->font_preset ?? 'outfit') === $fKey ? 'border-violet-500 bg-violet-50/60 dark:bg-violet-950/40 ring-2 ring-violet-500/30 shadow-xs' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 hover:bg-slate-50' }}">
                    <input type="radio" name="font_preset" value="{{ $fKey }}" {{ ($setting->font_preset ?? 'outfit') === $fKey ? 'checked' : '' }} class="mt-1 text-violet-600 focus:ring-violet-500">
                    <div class="min-w-0">
                        <span class="block text-xs font-black text-slate-900 dark:text-white">{{ $fItem['name'] }}</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5 truncate">{{ $fItem['css'] }}</span>
                        <div class="mt-2 p-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/70 border border-slate-200/60 dark:border-slate-700 text-[11px] text-slate-700 dark:text-slate-300">
                            {{ __('messages.theme_font_sample') }}
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 6 — Product Grid Density
    ══════════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-5 space-y-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-600 text-xs font-black text-white shadow-xs">
                6
            </span>
            <div>
                <h3 class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100">
                    {{ __('messages.theme_step6_title') }}
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_step6_desc') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach (\App\Themes\ThemeRegistry::GRID_DENSITIES as $dKey => $dItem)
                <label class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition shadow-2xs {{ ($setting->grid_density ?? 'compact') === $dKey ? 'border-violet-500 bg-violet-50/60 dark:bg-violet-950/40 ring-2 ring-violet-500/30 shadow-xs' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 hover:bg-slate-50' }}">
                    <input type="radio" name="grid_density" value="{{ $dKey }}" {{ ($setting->grid_density ?? 'compact') === $dKey ? 'checked' : '' }} class="mt-1 text-violet-600 focus:ring-violet-500">
                    <div class="min-w-0">
                        <span class="block text-xs font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>{{ $dItem['name'] }}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $dKey === 'compact' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300' }}">
                                {{ $dKey === 'compact' ? __('messages.theme_density_compact_badge') : __('messages.theme_density_comfortable_badge') }}
                            </span>
                        </span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            {{ $dKey === 'compact' ? __('messages.theme_density_compact_desc') : __('messages.theme_density_comfortable_desc') }}
                        </span>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         Sticky Bottom Action Bar (Save Draft + Publish Live)
    ══════════════════════════════════════════════════════ --}}
    <div class="border-t border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/80 px-4 py-3.5 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sticky bottom-0 z-20 backdrop-blur-md">
        <div>
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <span>{{ __('messages.theme_actions_title') }}</span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold shadow-xs"
                    :class="{
                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300/60': draftStatus === 'saved',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300 border border-blue-300/60 animate-pulse':             draftStatus === 'unsaved',
                        'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300':            draftStatus === 'saving',
                        'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300':         draftStatus === 'conflict',
                        'bg-violet-100 text-violet-800 dark:bg-violet-900/60 dark:text-violet-300':     draftStatus === 'publishing',
                    }"
                >
                    <span x-show="draftStatus === 'saved'"      x-cloak>✓ {{ __('messages.theme_status_saved') }}</span>
                    <span x-show="draftStatus === 'unsaved'"    x-cloak>• {{ __('messages.theme_status_unsaved') }}</span>
                    <span x-show="draftStatus === 'saving'"     x-cloak>{{ __('messages.theme_status_saving') }}</span>
                    <span x-show="draftStatus === 'conflict'"   x-cloak>⚠️ {{ __('messages.theme_status_conflict') }}</span>
                    <span x-show="draftStatus === 'publishing'" x-cloak>{{ __('messages.theme_status_publishing') }}</span>
                </span>
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                {{ __('messages.theme_actions_help') }}
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap shrink-0">
            <button
                type="button"
                @click="togglePreview()"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border px-3.5 py-2.5 text-xs font-black transition active:scale-95 cursor-pointer shadow-xs"
                :class="showPreview
                    ? 'border-violet-400 bg-violet-50 text-violet-700 dark:border-violet-600 dark:bg-violet-950/60 dark:text-violet-300'
                    : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-violet-400 hover:text-violet-700'"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span x-text="showPreview ? @js('✖️ ' . __('messages.theme_btn_hide_preview')) : @js('👁️ ' . __('messages.theme_btn_toggle_preview'))"></span>
            </button>

            <button
                type="button"
                @click="saveDraft()"
                :disabled="draftStatus === 'saving' || draftStatus === 'publishing'"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 px-4 py-2.5 text-xs font-black text-slate-700 dark:text-slate-200 shadow-xs transition active:scale-95 disabled:opacity-50 cursor-pointer"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-3-5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 21V9h6v12"/></svg>
                <span>💾 {{ __('messages.theme_btn_save_draft') }}</span>
            </button>

            <button
                type="button"
                id="btn-bottom-publish-theme"
                @click="openPublishConfirm()"
                :disabled="draftStatus === 'saving' || draftStatus === 'publishing'"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-violet-600 hover:bg-violet-500 px-5 py-2.5 text-xs font-black text-white shadow-sm shadow-violet-500/20 transition active:scale-95 disabled:opacity-50 cursor-pointer"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l7-7 7 7M12 5v14"/></svg>
                <span>🚀 {{ __('messages.theme_btn_publish_live') }}</span>
            </button>
        </div>
    </div>

</div>


