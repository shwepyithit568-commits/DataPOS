{{--
  Admin › Store Settings › Appearance Section
  Template Cards (5 designs) + Custom colour overrides.
  Saved via POST /store/{slug}/admin/settings (section = appearance).

  Alpine.js: registered via Alpine.data() inside alpine:init to avoid
  "function not defined" errors from script/x-data evaluation order.
--}}

@php
    $presets = \App\Models\StorefrontSetting::THEME_PRESETS;
    $currentPreset = $setting->theme_preset ?? 'sky';
    $colors = $setting->themeColors();

    // 5 visual templates + 'custom' fallback with body_bg, glow_style, and dark_mode
    $templates = [
        'sky' => [
            'name'        => 'Cloud White',
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
        'midnight' => [
            'name'        => 'Midnight Dark',
            'desc'        => 'Premium မဲ၊ Cyan နှင့် Orange accent',
            'primary'     => '#38bdf8',
            'accent'      => '#fb923c',
            'header_bg'   => '#0f172a',
            'body_bg'     => '#0f172a',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'dark',
            'badge'       => 'Premium',
            'badge_color' => 'bg-slate-700',
        ],
        'emerald' => [
            'name'        => 'Emerald Fresh',
            'desc'        => 'သဘာ၀ဆန်သော Green နှင့် Amber',
            'primary'     => '#10b981',
            'accent'      => '#f59e0b',
            'header_bg'   => '#ffffff',
            'body_bg'     => '#f0fdf4',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'auto',
            'badge'       => null,
            'badge_color' => null,
        ],
        'rose' => [
            'name'        => 'Sunset Rose',
            'desc'        => 'နွေးထွေးသော Rose Red နှင့် Gold',
            'primary'     => '#e11d48',
            'accent'      => '#f59e0b',
            'header_bg'   => '#fff1f2',
            'body_bg'     => '#fff5f6',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'auto',
            'badge'       => null,
            'badge_color' => null,
        ],
        'violet' => [
            'name'        => 'Royal Violet',
            'desc'        => 'ဘုန်းကြီးသောမဲ Violet နှင့် Emerald',
            'primary'     => '#7c3aed',
            'accent'      => '#10b981',
            'header_bg'   => '#1e1b4b',
            'body_bg'     => '#faf5ff',
            'glow_style'  => 'vivid',
            'dark_mode'   => 'dark',
            'badge'       => 'Luxury',
            'badge_color' => 'bg-violet-600',
        ],
        'custom' => [
            'name'        => 'Custom',
            'desc'        => 'မိမိကိုယ်ပိုင် color ရွေးချယ်',
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
    dark_mode: {!! json_encode($setting->theme_dark_mode ?? 'auto') !!},
    colors:    {
        primary:    {!! json_encode($colors['primary'] ?? '#0ea5e9') !!},
        accent:     {!! json_encode($colors['accent'] ?? '#7c3aed') !!},
        header_bg:  {!! json_encode($colors['header_bg'] ?? '#ffffff') !!},
        body_bg:    {!! json_encode($colors['body_bg'] ?? '#f8fafc') !!},
        glow_style: {!! json_encode($colors['glow_style'] ?? 'vivid') !!}
    },
    templates: {!! $templatesJson !!}
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
        },

        resetToTemplate: function () {
            var t = this.templates[this.preset] || this.templates['sky'];
            this.colors.primary    = t.primary;
            this.colors.accent     = t.accent;
            this.colors.header_bg  = t.header_bg;
            this.colors.body_bg    = t.body_bg;
            this.colors.glow_style = t.glow_style;
            this.dark_mode         = t.dark_mode || 'auto';
            this.preview_dark      = (this.dark_mode === 'dark');
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
        }
    };
};
</script>

{{-- ③ x-data calls the factory — simple expression, no quote conflicts. --}}
<div class="divide-y divide-gray-100 dark:divide-slate-700/60"
     x-data="sfThemeFactory()">

    <input type="hidden" name="theme_preset" :value="preset">

    {{-- ── Section header ── --}}
    <div class="px-4 py-5 sm:px-6">
        <h2 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.settings_appearance') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('messages.settings_appearance_desc') }}</p>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 1 — Choose a Template
    ══════════════════════════════════════════════════════ --}}
    <div class="px-4 py-6 sm:px-6">
        <p class="mb-4 text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-black text-white">1</span>
            Template ရွေးချယ်ပါ
        </p>

        {{-- Template Card Grid --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3">

            @foreach ($templates as $tKey => $tpl)
            <button
                type="button"
                @click="selectTemplate('{{ $tKey }}')"
                :class="preset === '{{ $tKey }}'
                    ? 'ring-2 ring-violet-500 ring-offset-2 shadow-lg scale-[1.02]'
                    : 'hover:shadow-md hover:scale-[1.01] opacity-90 hover:opacity-100'"
                class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white text-left transition-all duration-200 dark:border-slate-700 dark:bg-slate-800/60 focus:outline-none"
            >
                {{-- Badge --}}
                @if ($tpl['badge'])
                    <span class="absolute right-2 top-2 z-10 rounded-full {{ $tpl['badge_color'] }} px-2 py-0.5 text-[10px] font-black text-white shadow">
                        {{ $tpl['badge'] }}
                    </span>
                @endif

                {{-- ── Mini Storefront Mockup (SVG) ── --}}
                <div class="relative w-full overflow-hidden" style="padding-bottom: 60%;">
                    <svg class="absolute inset-0 h-full w-full" viewBox="0 0 240 144" xmlns="http://www.w3.org/2000/svg">
                        <!-- Page background -->
                        <rect width="240" height="144" fill="#f8fafc"/>

                        <!-- Header bar -->
                        <rect width="240" height="32" fill="{{ $tKey === 'custom' ? $colors['header_bg'] : $tpl['header_bg'] }}"/>
                        <!-- Header logo box -->
                        <rect x="8" y="9" width="24" height="14" rx="3" fill="{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}"/>
                        <!-- Search bar -->
                        <rect x="40" y="10" width="140" height="12" rx="6" fill="{{ $tKey === 'midnight' || $tKey === 'violet' ? '#1e293b' : '#f1f5f9' }}" opacity="0.8"/>
                        <!-- Search button -->
                        <rect x="185" y="10" width="28" height="12" rx="6" fill="{{ $tKey === 'custom' ? $colors['accent'] : $tpl['accent'] }}"/>
                        <text x="199" y="19.5" font-size="5" fill="white" text-anchor="middle" font-weight="bold">Search</text>
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
                            <rect x="{{ $px }}" y="92" width="44" height="38" rx="4" fill="white"/>
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

                        <!-- Active indicator -->
                        {{-- We can't use x-show inside SVG easily; rely on ring from parent button --}}
                    </svg>
                </div>

                {{-- Card footer --}}
                <div class="px-3 py-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-slate-800 dark:text-slate-100">{{ $tpl['name'] }}</span>
                        {{-- Selected checkmark --}}
                        <span x-show="preset === '{{ $tKey }}'" x-cloak
                            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-violet-600 text-white">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </div>
                    <p class="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">{{ $tpl['desc'] }}</p>

                    {{-- Color swatches --}}
                    <div class="mt-1.5 flex gap-1">
                        <span class="h-3.5 w-3.5 rounded-full border border-white/60 shadow-sm ring-1 ring-slate-200/50"
                              style="background:{{ $tKey === 'custom' ? $colors['primary'] : $tpl['primary'] }}"></span>
                        <span class="h-3.5 w-3.5 rounded-full border border-white/60 shadow-sm ring-1 ring-slate-200/50"
                              style="background:{{ $tKey === 'custom' ? $colors['accent'] : $tpl['accent'] }}"></span>
                        <span class="h-3.5 w-3.5 rounded-full border border-slate-200 shadow-sm ring-1 ring-slate-200/50"
                              style="background:{{ $tKey === 'custom' ? $colors['header_bg'] : $tpl['header_bg'] }}"></span>
                        <span class="h-3.5 w-3.5 rounded-full border border-slate-200 shadow-sm ring-1 ring-slate-200/50"
                              style="background:{{ $tKey === 'custom' ? ($colors['body_bg'] ?? '#f8fafc') : $tpl['body_bg'] }}"></span>
                    </div>
                </div>
            </button>
            @endforeach

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 2 — Live Preview + Fine-Tune Colors
    ══════════════════════════════════════════════════════ --}}
    <div class="px-4 py-6 sm:px-6">
        <p class="mb-4 text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-black text-white">2</span>
            Color & Atmosphere Fine-Tune (စိတ်ကြိုက်ချိန်ညှိပါ)
        </p>

        {{-- Live preview strip with interactive body background, ambient glow, and dark mode preview --}}
        <div class="mb-5 relative overflow-hidden rounded-2xl border border-slate-200 shadow-md dark:border-slate-700 transition-colors duration-300"
             :style="'background:' + (preview_dark ? '#0b0f19' : colors.body_bg)">

            {{-- Mini Live Ambient Glow Orbs in preview --}}
            <div x-show="colors.glow_style !== 'none'" class="absolute -top-8 -left-8 w-36 h-36 rounded-full blur-2xl pointer-events-none transition-all duration-300"
                 :style="'background:' + colors.primary + '; opacity:' + (colors.glow_style === 'vivid' ? (preview_dark ? '0.40' : '0.35') : (preview_dark ? '0.20' : '0.15'))"></div>
            <div x-show="colors.glow_style !== 'none'" class="absolute top-1/2 -right-8 w-36 h-36 rounded-full blur-2xl pointer-events-none transition-all duration-300"
                 :style="'background:' + colors.accent + '; opacity:' + (colors.glow_style === 'vivid' ? (preview_dark ? '0.40' : '0.35') : (preview_dark ? '0.20' : '0.15'))"></div>

            {{-- Mini header --}}
            <div class="relative z-10 flex h-12 items-center gap-2 px-4 transition-colors shadow-xs" :style="'background:' + (preview_dark && colors.header_bg === '#ffffff' ? '#1e293b' : colors.header_bg)">
                <span class="h-6 w-6 rounded-lg flex-shrink-0 transition-colors" :style="'background:' + colors.primary"></span>
                <span class="flex-1 h-8 rounded-xl border-2 transition-colors text-xs flex items-center px-3"
                      :style="'border-color:' + colors.primary + '60; color:' + (preview_dark ? '#cbd5e1' : '#94a3b8') + '; background:' + (preview_dark ? 'rgba(30,41,59,0.8)' : 'rgba(241,245,249,0.5)')">
                    ကုန်ပစ္စည်း ရှာဖွေပါ...
                </span>
                <span class="rounded-xl px-3 py-2 text-xs font-black transition-colors"
                      :style="'background:' + colors.accent + '; color:' + contrastText(colors.accent)">
                    ရှာရန်
                </span>
            </div>
            {{-- Category nav --}}
            <div class="relative z-10 flex gap-2 px-4 py-2 text-[10px] font-bold transition-colors"
                 :style="'background:' + colors.primary + (preview_dark ? '30' : '18')">
                <span class="rounded-lg px-2.5 py-1 text-white transition-colors" :style="'background:' + colors.primary">All</span>
                <span class="rounded-lg px-2.5 py-1" :class="preview_dark ? 'text-slate-200' : 'text-slate-600'">Phone</span>
                <span class="rounded-lg px-2.5 py-1" :class="preview_dark ? 'text-slate-200' : 'text-slate-600'">Accessories</span>
                <span class="rounded-lg px-2.5 py-1" :class="preview_dark ? 'text-slate-200' : 'text-slate-600'">Screen</span>
            </div>
            {{-- Product row mockup --}}
            <div class="relative z-10 flex gap-2 px-4 py-3 backdrop-blur-xs" :class="preview_dark ? 'bg-slate-900/60' : 'bg-white/40'">
                @foreach (range(1, 4) as $i)
                <div class="flex-1 overflow-hidden rounded-xl border shadow-2xs transition-colors"
                     :class="preview_dark ? 'border-slate-700/80 bg-slate-800/90' : 'border-slate-200/80 bg-white/90'">
                    <div class="h-10 w-full transition-colors" :style="'background:' + colors.primary + (preview_dark ? '30' : '18')"></div>
                    <div class="p-1.5">
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
                <span class="text-[11px] font-bold" :class="preview_dark ? 'text-slate-300' : 'text-slate-500'">Live Preview</span>

                {{-- Mini Preview Dark Mode Toggle --}}
                <button type="button" @click="togglePreviewDark()"
                        class="ml-1 inline-flex items-center gap-1 rounded-lg border px-2 py-0.5 text-[10px] font-bold transition shadow-2xs"
                        :class="preview_dark ? 'border-amber-400/40 bg-amber-950/40 text-amber-300' : 'border-slate-200 bg-slate-100 text-slate-700'">
                    <span x-text="preview_dark ? '🌙 Dark Preview' : '☀️ Light Preview'"></span>
                </button>

                <span class="flex-1"></span>
                <span class="inline-flex h-5 items-center rounded-full px-2 text-[10px] font-bold text-white shadow-2xs"
                      :style="'background:' + colors.primary">Primary</span>
                <span class="inline-flex h-5 items-center rounded-full px-2 text-[10px] font-bold text-white shadow-2xs"
                      :style="'background:' + colors.accent">Accent</span>
                <span class="inline-flex h-5 items-center rounded-full border px-2 text-[10px] font-bold shadow-2xs"
                      :class="preview_dark ? 'text-slate-200 border-slate-600' : 'text-slate-700 border-slate-300'"
                      :style="'background:' + (preview_dark && colors.header_bg === '#ffffff' ? '#1e293b' : colors.header_bg)">
                    Header
                </span>
                <span class="inline-flex h-5 items-center rounded-full border px-2 text-[10px] font-bold shadow-2xs"
                      :class="preview_dark ? 'text-slate-200 border-slate-600' : 'text-slate-700 border-slate-300'"
                      :style="'background:' + (preview_dark ? '#0b0f19' : colors.body_bg)">
                    Body BG
                </span>
            </div>
        </div>

        {{-- 4 colour pickers in a row (Primary, Accent, Header BG, Body BG) --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

            {{-- 1. Primary Color --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40 shadow-2xs">
                <div class="h-8 w-full transition-colors" :style="'background:' + colors.primary"></div>
                <div class="px-3 py-3 space-y-2">
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-200">
                        {{ __('messages.theme_primary_color') }}
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_primary_color_help') }}</p>
                    <div class="flex items-center gap-1.5">
                        <input
                            type="color"
                            name="theme_primary_color"
                            x-model="colors.primary"
                            @input="onCustomColorChange()"
                            class="h-9 w-10 cursor-pointer rounded-lg border border-slate-300 p-0.5 dark:border-slate-600 flex-shrink-0"
                        >
                        <input
                            type="text"
                            x-model="colors.primary"
                            @input="sanitizeHex('primary', $event.target.value); onCustomColorChange()"
                            maxlength="7"
                            placeholder="#0ea5e9"
                            class="block w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 font-mono text-xs text-slate-800 shadow-xs focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                    </div>
                </div>
            </div>

            {{-- 2. Accent Color --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40 shadow-2xs">
                <div class="h-8 w-full transition-colors" :style="'background:' + colors.accent"></div>
                <div class="px-3 py-3 space-y-2">
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-200">
                        {{ __('messages.theme_accent_color') }}
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_accent_color_help') }}</p>
                    <div class="flex items-center gap-1.5">
                        <input
                            type="color"
                            name="theme_accent_color"
                            x-model="colors.accent"
                            @input="onCustomColorChange()"
                            class="h-9 w-10 cursor-pointer rounded-lg border border-slate-300 p-0.5 dark:border-slate-600 flex-shrink-0"
                        >
                        <input
                            type="text"
                            x-model="colors.accent"
                            @input="sanitizeHex('accent', $event.target.value); onCustomColorChange()"
                            maxlength="7"
                            placeholder="#7c3aed"
                            class="block w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 font-mono text-xs text-slate-800 shadow-xs focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                    </div>
                </div>
            </div>

            {{-- 3. Header BG --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40 shadow-2xs">
                <div class="h-8 w-full border-b border-slate-200 transition-colors dark:border-slate-700"
                     :style="'background:' + colors.header_bg"></div>
                <div class="px-3 py-3 space-y-2">
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-200">
                        {{ __('messages.theme_header_bg') }}
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_header_bg_help') }}</p>
                    <div class="flex items-center gap-1.5">
                        <input
                            type="color"
                            name="theme_header_bg"
                            x-model="colors.header_bg"
                            @input="onCustomColorChange()"
                            class="h-9 w-10 cursor-pointer rounded-lg border border-slate-300 p-0.5 dark:border-slate-600 flex-shrink-0"
                        >
                        <input
                            type="text"
                            x-model="colors.header_bg"
                            @input="sanitizeHex('header_bg', $event.target.value); onCustomColorChange()"
                            maxlength="7"
                            placeholder="#ffffff"
                            class="block w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 font-mono text-xs text-slate-800 shadow-xs focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                    </div>
                </div>
            </div>

            {{-- 4. Body BG (New) --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40 shadow-2xs">
                <div class="h-8 w-full border-b border-slate-200 transition-colors dark:border-slate-700"
                     :style="'background:' + colors.body_bg"></div>
                <div class="px-3 py-3 space-y-2">
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-200">
                        {{ __('messages.theme_body_bg') }}
                    </label>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_body_bg_help') }}</p>
                    <div class="flex items-center gap-1.5">
                        <input
                            type="color"
                            name="theme_body_bg"
                            x-model="colors.body_bg"
                            @input="onCustomColorChange()"
                            class="h-9 w-10 cursor-pointer rounded-lg border border-slate-300 p-0.5 dark:border-slate-600 flex-shrink-0"
                        >
                        <input
                            type="text"
                            x-model="colors.body_bg"
                            @input="sanitizeHex('body_bg', $event.target.value); onCustomColorChange()"
                            maxlength="7"
                            placeholder="#f8fafc"
                            class="block w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 font-mono text-xs text-slate-800 shadow-xs focus:border-violet-500 focus:outline-none focus:ring-1 focus:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- Reset to template defaults --}}
        <div class="mt-3">
            <button type="button" @click="resetToTemplate()"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 hover:text-violet-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-violet-400">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                {{ __('messages.theme_reset_preset') }}
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 3 — Ambient Glow Style
    ══════════════════════════════════════════════════════ --}}
    <div class="px-4 py-6 sm:px-6">
        <p class="mb-3 text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-black text-white">3</span>
            {{ __('messages.theme_glow_style_label') }}
        </p>
        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">{{ __('messages.theme_glow_style_help') }}</p>

        <input type="hidden" name="theme_glow_style" :value="colors.glow_style">

        <div class="flex flex-wrap gap-2.5">
            @foreach ([
                'vivid'  => ['emoji' => '✨', 'title' => __('messages.theme_glow_vivid'),  'desc' => 'တောက်ပသော အရောင်စုံ အလင်းစက်ဝိုင်း'],
                'subtle' => ['emoji' => '🌫️', 'title' => __('messages.theme_glow_subtle'), 'desc' => 'နူးညံ့သော ဖျော့တော့အလင်း'],
                'none'   => ['emoji' => '🚫', 'title' => __('messages.theme_glow_none'),   'desc' => 'အလင်းပိတ်ပြီး ရိုးရိုးနောက်ခံထားမည်'],
            ] as $gVal => $gItem)
                <button
                    type="button"
                    @click="colors.glow_style = '{{ $gVal }}'; onCustomColorChange()"
                    :class="colors.glow_style === '{{ $gVal }}'
                        ? 'border-violet-500 bg-violet-50 text-violet-800 ring-2 ring-violet-500/30 dark:border-violet-400 dark:bg-violet-950/50 dark:text-violet-200'
                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700/60'"
                    class="flex flex-col items-start gap-1 rounded-2xl border px-4 py-3 text-left transition shadow-2xs"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ $gItem['emoji'] }}</span>
                        <span class="text-xs font-black">{{ $gItem['title'] }}</span>
                    </div>
                    <span class="text-[10px] opacity-70">{{ $gItem['desc'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         STEP 4 — Dark Mode Preference
    ══════════════════════════════════════════════════════ --}}
    <div class="px-4 py-6 sm:px-6">
        <p class="mb-3 text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-violet-600 text-xs font-black text-white">4</span>
            {{ __('messages.theme_dark_mode_label') }}
        </p>
        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">{{ __('messages.theme_dark_mode_help') }}</p>

        {{-- Controlled via Alpine state --}}
        <input type="hidden" name="theme_dark_mode" :value="dark_mode">

        <div class="flex flex-wrap gap-3">
            @foreach ([
                'auto'  => ['emoji' => '🌓', 'label' => 'Auto (စနစ်အလိုက်)',  'desc' => 'ဝယ်ယူသူ၏ စနစ် (System Theme) အတိုင်း လိုက်ပြောင်းမည်'],
                'light' => ['emoji' => '☀️', 'label' => 'Light (အမြဲတမ်းလင်း)', 'desc' => 'ဝယ်ယူသူတိုင်းအတွက် ဖြူ/လင်း မုဒ် ဖော်ပြမည်'],
                'dark'  => ['emoji' => '🌙', 'label' => 'Dark (အမြဲတမ်းမှောင်)',  'desc' => 'ဝယ်ယူသူတိုင်းအတွက် နက်မှောင်/အမှောင် မုဒ် ဖော်ပြမည်'],
            ] as $val => $item)
                <button
                    type="button"
                    @click="setDarkMode('{{ $val }}')"
                    :class="dark_mode === '{{ $val }}'
                        ? 'border-violet-500 bg-violet-50 text-violet-900 ring-2 ring-violet-500/40 dark:border-violet-400 dark:bg-violet-950/60 dark:text-violet-200'
                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700/60'"
                    class="flex flex-col items-start gap-1 rounded-2xl border px-5 py-3.5 text-left transition shadow-2xs cursor-pointer focus:outline-none"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ $item['emoji'] }}</span>
                        <span class="text-xs font-black">{{ $item['label'] }}</span>
                        <span x-show="dark_mode === '{{ $val }}'" x-cloak
                              class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-violet-600 text-[10px] text-white">✓</span>
                    </div>
                    <span class="text-[10px] opacity-70">{{ $item['desc'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

</div>
