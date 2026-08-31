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

    // 5 visual templates + 'custom' fallback with body_bg, glow_style, and dark_mode
    $templates = [
        'marketplace_pro' => [
            'name'        => 'Marketplace Pro (Cloud White)',
            'desc'        => 'ရှင်းလင်းသောဖြူ၊ Sky Blue နှင့် Violet',
            'primary'     => '#0ea5e9',
            'accent'      => '#7c3aed',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#f8fafc',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'auto',
            'badge'       => 'Popular',
            'badge_color' => 'bg-sky-500',
        ],
        'retail_trust' => [
            'name'        => 'Retail Trust (Clean Blue)',
            'desc'        => 'သန့်ရှင်းရိုးရှင်းသော Royal Blue နှင့် Amber',
            'primary'     => '#2563eb',
            'accent'      => '#f59e0b',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#f8fafc',
            'glow_style'  => 'subtle',
            'dark_mode'   => 'auto',
            'badge'       => 'General Retail',
            'badge_color' => 'bg-blue-600',
        ],
        'emerald_fresh' => [
            'name'        => 'Emerald Fresh (Healthcare)',
            'desc'        => 'သဘာ၀ဆန်သော Green နှင့် Amber',
            'primary'     => '#10b981',
            'accent'      => '#f59e0b',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#f0fdf4',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'auto',
            'badge'       => 'Pharmacy',
            'badge_color' => 'bg-emerald-600',
        ],
        'midnight_tech' => [
            'name'        => 'Midnight Tech (Dark Mode)',
            'desc'        => 'Premium မဲ၊ Cyan နှင့် Orange accent',
            'primary'     => '#38bdf8',
            'accent'      => '#fb923c',
            'header_bg'   => '#0f172a',
            'body_bg'     => '#0f172a',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'dark',
            'badge'       => 'Premium Tech',
            'badge_color' => 'bg-slate-700',
        ],
        'sunset_warm' => [
            'name'        => 'Sunset Warm (Boutique)',
            'desc'        => 'နွေးထွေးသော Rose Red နှင့် Gold',
            'primary'     => '#e11d48',
            'accent'      => '#f59e0b',
            'header_bg'   => '#fff1f2',
            'body_bg'     => '#fff5f6',
            'glow_style'  => 'subtle',
            'dark_mode'   => 'auto',
            'badge'       => 'Fashion',
            'badge_color' => 'bg-rose-500',
        ],
        'custom' => [
            'name'        => 'Custom Color Scheme',
            'desc'        => 'မိမိကိုယ်ပိုင် Brand Color ရွေးချယ်မည်',
            'primary'     => $colors['primary'],
            'accent'      => $colors['accent'],
            'header_bg'   => $colors['header_bg'],
            'body_bg'     => $colors['body_bg'] ?? '#f8fafc',
            'glow_style'  => $colors['glow_style'] ?? 'vivid',
            'dark_mode'   => $colors['dark_mode'] ?? 'auto',
            'badge'       => null,
            'badge_color' => null,
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">

            @foreach ($templates as $tKey => $tpl)
            <button
                type="button"
                @click="selectTemplate('{{ $tKey }}')"
                :class="preset === '{{ $tKey }}'
                    ? 'ring-2 ring-violet-600 dark:ring-violet-400 bg-violet-50/20 dark:bg-violet-950/20 shadow-md border-violet-300 dark:border-violet-700'
                    : 'border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/80 hover:border-slate-300 dark:hover:border-slate-600 hover:shadow-xs'"
                class="relative overflow-hidden rounded-2xl border text-left transition-all duration-200 focus:outline-none cursor-pointer flex flex-col justify-between group"
            >
                {{-- Badge --}}
                @if ($tpl['badge'])
                    <span class="absolute right-2.5 top-2.5 z-10 rounded-full {{ $tpl['badge_color'] }} px-2 py-0.5 text-[9px] font-black text-white shadow-xs">
                        {{ $tpl['badge'] }}
                    </span>
                @endif
                {{-- Deprecated lifecycle badge (T7) --}}
                @if (($themeStatuses[$tKey] ?? 'active') === 'deprecated')
                    <span class="absolute left-2.5 top-2.5 z-10 rounded-full bg-amber-500 px-2 py-0.5 text-[9px] font-black text-white shadow-xs">
                        ⚠️ Deprecated
                    </span>
                @endif

                {{-- ── Mini Storefront Mockup (SVG) ── --}}
                <div class="relative w-full overflow-hidden border-b border-slate-100 dark:border-slate-700/60" style="padding-bottom: 56%;">
                    <svg class="absolute inset-0 h-full w-full" viewBox="0 0 240 144" xmlns="http://www.w3.org/2000/svg">
                        <!-- Page background -->
                        <rect width="240" height="144" fill="{{ $tpl['dark_mode'] === 'dark' ? '#0f172a' : '#f8fafc' }}"/>

                        <!-- Header bar -->
                        <rect width="240" height="32" fill="{{ $tKey === 'custom' ? $colors['header_bg'] : $tpl['header_bg'] }}"/>
                        <!-- Header logo box -->
                        <rect x="8" y="9" width="24" height="14" rx="3" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}"/>
                        <!-- Search bar -->
                        <rect x="40" y="10" width="140" height="12" rx="6" fill="{{ $tpl['dark_mode'] === 'dark' ? '#1e293b' : '#f1f5f9' }}" opacity="0.8"/>
                        <!-- Search button -->
                        <rect x="185" y="10" width="28" height="12" rx="6" fill="{{ $tKey === 'custom' ? $colors['accent'] : $tpl['accent'] }}"/>
                        <text x="199" y="18.5" font-size="5" fill="white" text-anchor="middle" font-weight="bold">Search</text>
                        <!-- Cart icon area -->
                        <circle cx="225" cy="16" r="6" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.15"/>
                        <rect x="221" y="13" width="8" height="6" rx="1" fill="none" stroke="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" stroke-width="1.2"/>

                        <!-- Category nav strip -->
                        <rect y="32" width="240" height="14" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.08"/>
                        <rect x="8" y="36" width="28" height="6" rx="3" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.4"/>
                        <rect x="44" y="36" width="28" height="6" rx="3" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.25"/>
                        <rect x="80" y="36" width="28" height="6" rx="3" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.25"/>
                        <rect x="116" y="36" width="28" height="6" rx="3" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.25"/>

                        <!-- Banner area -->
                        <rect x="8" y="52" width="224" height="34" rx="5"
                              fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.12"/>
                        <rect x="16" y="59" width="60" height="8" rx="3"
                              fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.5"/>
                        <rect x="16" y="70" width="40" height="6" rx="3"
                              fill="{{ $tKey === 'custom' ? $colors['accent'] : $tpl['accent'] }}" opacity="0.7"/>

                        <!-- Product cards row -->
                        @foreach ([8, 68, 128, 188] as $px)
                            <rect x="{{ $px }}" y="92" width="44" height="38" rx="4" fill="{{ $tpl['dark_mode'] === 'dark' ? '#1e293b' : 'white' }}"/>
                            <rect x="{{ $px }}" y="92" width="44" height="22" rx="4" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.1"/>
                            <rect x="{{ $px + 4 }}" y="117" width="20" height="4" rx="2" fill="#94a3b8"/>
                            <rect x="{{ $px + 4 }}" y="123" width="14" height="3" rx="1.5" fill="{{ $tKey === 'custom' ? $colors['accent'] : $tpl['accent'] }}" opacity="0.8"/>
                            <rect x="{{ $px + 30 }}" y="121" width="10" height="7" rx="2" fill="{{ $tKey === 'custom' ? $colors['accent'] : $tpl['accent'] }}"/>
                        @endforeach

                        <!-- Footer band -->
                        <rect y="136" width="240" height="8"
                              fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.18"/>
                        <rect x="8" y="138" width="30" height="3" rx="1.5" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.4"/>
                        <rect x="100" y="138" width="40" height="3" rx="1.5" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.25"/>
                        <rect x="200" y="138" width="32" height="3" rx="1.5" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}" opacity="0.25"/>
                    </svg>
                </div>

                {{-- Card footer --}}
                <div class="p-3">
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-xs font-black text-slate-900 dark:text-white">{{ $tpl['name'] }}</span>
                        {{-- Selected checkmark --}}
                        <span x-show="preset === '{{ $tKey }}'" x-cloak
                            class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-violet-600 text-white text-[10px] shrink-0 shadow-xs">
                            ✓
                        </span>
                    </div>
                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">{{ $tpl['desc'] }}</p>

                    {{-- Color swatches ribbon --}}
                    <div class="mt-2 flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-700/60">
                        <div class="flex -space-x-1">
                            <span class="h-4 w-4 rounded-full border border-white dark:border-slate-800 shadow-xs ring-1 ring-slate-200/60"
                                  style="background:{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}"
                                  title="Primary"></span>
                            <span class="h-4 w-4 rounded-full border border-white dark:border-slate-800 shadow-xs ring-1 ring-slate-200/60"
                                  style="background:{{ $tKey === 'custom' ? $colors['accent'] : $tpl['accent'] }}"
                                  title="Accent"></span>
                            <span class="h-4 w-4 rounded-full border border-white dark:border-slate-800 shadow-xs ring-1 ring-slate-200/60"
                                  style="background:{{ $tKey === 'custom' ? $colors['header_bg'] : $tpl['header_bg'] }}"
                                  title="Header"></span>
                            <span class="h-4 w-4 rounded-full border border-white dark:border-slate-800 shadow-xs ring-1 ring-slate-200/60"
                                  style="background:{{ $tKey === 'custom' ? ($colors['body_bg'] ?? '#f8fafc') : $tpl['body_bg'] }}"
                                  title="Body"></span>
                        </div>
                        <span class="text-[10px] font-mono text-slate-400">
                            {{ $tpl['dark_mode'] === 'dark' ? 'Dark Mode' : 'Light' }}
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
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm transition-colors duration-300"
             :style="'background:' + (preview_dark ? '#0b0f19' : colors.body_bg)">

            {{-- Mini Live Ambient Glow Orbs in preview --}}
            <div x-show="colors.glow_style !== 'none'" class="absolute -top-8 -left-8 w-36 h-36 rounded-full blur-2xl pointer-events-none transition-all duration-300"
                 :style="'background:' + colors.primary + '; opacity:' + (colors.glow_style === 'vivid' ? (preview_dark ? '0.40' : '0.35') : (preview_dark ? '0.20' : '0.15'))"></div>
            <div x-show="colors.glow_style !== 'none'" class="absolute top-1/2 -right-8 w-36 h-36 rounded-full blur-2xl pointer-events-none transition-all duration-300"
                 :style="'background:' + colors.accent + '; opacity:' + (colors.glow_style === 'vivid' ? (preview_dark ? '0.40' : '0.35') : (preview_dark ? '0.20' : '0.15'))"></div>

            {{-- Mini header --}}
            <div class="relative z-10 flex h-12 items-center gap-2 px-4 transition-colors shadow-xs" :style="'background:' + (preview_dark && colors.header_bg === '#ffffff' ? '#1e293b' : colors.header_bg)">
                <span class="h-6 w-6 rounded-lg flex-shrink-0 transition-colors shadow-xs" :style="'background:' + colors.primary"></span>
                <span class="flex-1 h-8 rounded-xl border transition-colors text-xs flex items-center px-3 font-medium"
                      :style="'border-color:' + colors.primary + '50; color:' + (preview_dark ? '#cbd5e1' : '#94a3b8') + '; background:' + (preview_dark ? 'rgba(30,41,59,0.8)' : 'rgba(241,245,249,0.6)')">
                    {{ __('messages.theme_preview_search_ph') }}
                </span>
                <span class="rounded-xl px-3.5 py-1.5 text-xs font-black transition-colors shadow-xs"
                      :style="'background:' + colors.accent + '; color:' + contrastText(colors.accent)">
                    {{ __('messages.theme_preview_search_btn') }}
                </span>
            </div>
            {{-- Category nav --}}
            <div class="relative z-10 flex gap-2 px-4 py-2 text-[10px] font-bold transition-colors"
                 :style="'background:' + colors.primary + (preview_dark ? '30' : '18')">
                <span class="rounded-lg px-2.5 py-1 text-white transition-colors shadow-xs" :style="'background:' + colors.primary">All</span>
                <span class="rounded-lg px-2.5 py-1 font-semibold" :class="preview_dark ? 'text-slate-200' : 'text-slate-600'">Phone</span>
                <span class="rounded-lg px-2.5 py-1 font-semibold" :class="preview_dark ? 'text-slate-200' : 'text-slate-600'">Accessories</span>
                <span class="rounded-lg px-2.5 py-1 font-semibold" :class="preview_dark ? 'text-slate-200' : 'text-slate-600'">Screen</span>
            </div>
            {{-- Product row mockup --}}
            <div class="relative z-10 flex gap-2.5 px-4 py-3 backdrop-blur-xs" :class="preview_dark ? 'bg-slate-900/60' : 'bg-white/40'">
                @foreach (range(1, 4) as $i)
                <div class="flex-1 overflow-hidden rounded-xl border shadow-xs transition-colors"
                     :class="preview_dark ? 'border-slate-700/80 bg-slate-800/90' : 'border-slate-200/80 bg-white/90'">
                    <div class="h-10 w-full transition-colors" :style="'background:' + colors.primary + (preview_dark ? '30' : '18')"></div>
                    <div class="p-2">
                        <div class="h-2 w-3/4 rounded-full mb-1" :class="preview_dark ? 'bg-slate-600' : 'bg-slate-200'"></div>
                        <div class="h-2.5 w-1/2 rounded-full transition-colors" :style="'background:' + colors.accent + '90'"></div>
                    </div>
                </div>
                @endforeach
            </div>
            {{-- Preview footer bar with interactive Dark/Light toggle --}}
            <div class="relative z-10 flex flex-wrap items-center gap-2 border-t px-4 py-2 backdrop-blur-xs transition-colors"
                 :class="preview_dark ? 'border-slate-800 bg-slate-900/90 text-white' : 'border-slate-200/80 bg-white/90 text-slate-800'">
                <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span class="text-[11px] font-bold" :class="preview_dark ? 'text-slate-300' : 'text-slate-500'">{{ __('messages.theme_atmosphere_test') }}</span>

                {{-- Mini Preview Dark Mode Toggle --}}
                <button type="button" @click="togglePreviewDark()"
                        class="inline-flex items-center gap-1 rounded-lg border px-2 py-0.5 text-[10px] font-bold transition shadow-xs cursor-pointer"
                        :class="preview_dark ? 'border-amber-400/40 bg-amber-950/40 text-amber-300' : 'border-slate-200 bg-slate-100 text-slate-700'">
                    <span x-text="preview_dark ? '🌙 Dark Mode' : '☀️ Light Mode'"></span>
                </button>

                <span class="flex-1"></span>
                <span class="inline-flex h-5 items-center rounded-full px-2 text-[10px] font-bold text-white shadow-xs"
                      :style="'background:' + colors.primary">Primary</span>
                <span class="inline-flex h-5 items-center rounded-full px-2 text-[10px] font-bold text-white shadow-xs"
                      :style="'background:' + colors.accent">Accent</span>
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

        {{-- 4 colour pickers in a row (Primary, Accent, Header BG, Body BG) --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

            {{-- 1. Primary Color --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-700/80 dark:bg-slate-800/60 shadow-xs">
                <div class="h-8 w-full transition-colors flex items-center justify-end px-2" :style="'background:' + colors.primary">
                    <span class="text-[9px] font-black uppercase text-white/90 bg-black/20 px-1.5 py-0.5 rounded">Primary</span>
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
                    <span class="text-[9px] font-black uppercase text-white/90 bg-black/20 px-1.5 py-0.5 rounded">Accent CTA</span>
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
                    <span class="text-[9px] font-black uppercase text-slate-700 dark:text-slate-200 bg-white/70 dark:bg-black/40 px-1.5 py-0.5 rounded">Header</span>
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
                    <span class="text-[9px] font-black uppercase text-slate-700 dark:text-slate-200 bg-white/70 dark:bg-black/40 px-1.5 py-0.5 rounded">Body BG</span>
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


