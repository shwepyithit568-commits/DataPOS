<div class="p-4 sm:p-6 space-y-6">
    <div class="admin-section-head">
        <div>
            <h2 class="admin-section-title">🎨 {{ __('messages.settings_footer_page') }}</h2>
            <p class="admin-section-sub">{{ __('messages.settings_footer_preview_hint') }}</p>
        </div>
        <a href="{{ url('/store/' . $store->slug) }}" target="_blank" rel="noopener noreferrer"
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
            👁 {{ __('messages.settings_view_storefront') }}
        </a>
    </div>

    {{-- ============ COMBINED LIVE PREVIEW ============ --}}
    {{-- Renders the REAL storefront footer component with this store's settings,
         so the preview is pixel-for-pixel what customers see (light + dark mode). --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/60">
            <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">
                🖥 {{ __('messages.settings_footer_live_preview') }}
            </p>
            <span class="inline-flex items-center gap-1.5 text-[10px] font-black text-emerald-600 dark:text-emerald-400">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live
            </span>
        </div>
        <div class="bg-slate-100 dark:bg-slate-950">
            <x-storefront-footer :store="$store" :setting="$setting" />
        </div>
    </section>

    {{-- ============ EDIT SOURCES ============ --}}
    <section class="space-y-3">
        <h3 class="admin-section-title">🔗 {{ __('messages.settings_footer_sources') }}</h3>
        <p class="{{ $helpClass }}">{{ __('messages.settings_footer_sources_hint') }}</p>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <a href="{{ $settingsBase }}"
               class="group rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-violet-300 hover:shadow-sm dark:border-slate-700 dark:bg-slate-800/60 dark:hover:border-violet-600">
                <p class="flex items-center gap-2 text-sm font-black text-gray-900 dark:text-slate-100">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base dark:bg-slate-700" aria-hidden="true">🪧</span>
                    {{ __('messages.settings_general') }}
                </p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('messages.settings_footer_source_general') }}</p>
                <span class="mt-3 inline-flex items-center gap-1 text-xs font-black text-violet-600 transition group-hover:gap-2 dark:text-violet-400">
                    {{ __('messages.settings_footer_edit') }} →
                </span>
            </a>
            <a href="{{ $settingsBase }}/contact"
               class="group rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-violet-300 hover:shadow-sm dark:border-slate-700 dark:bg-slate-800/60 dark:hover:border-violet-600">
                <p class="flex items-center gap-2 text-sm font-black text-gray-900 dark:text-slate-100">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base dark:bg-slate-700" aria-hidden="true">📞</span>
                    {{ __('messages.settings_contact') }}
                </p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('messages.settings_footer_source_contact') }}</p>
                <span class="mt-3 inline-flex items-center gap-1 text-xs font-black text-violet-600 transition group-hover:gap-2 dark:text-violet-400">
                    {{ __('messages.settings_footer_edit') }} →
                </span>
            </a>
            <a href="{{ $settingsBase }}/delivery"
               class="group rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-violet-300 hover:shadow-sm dark:border-slate-700 dark:bg-slate-800/60 dark:hover:border-violet-600">
                <p class="flex items-center gap-2 text-sm font-black text-gray-900 dark:text-slate-100">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base dark:bg-slate-700" aria-hidden="true">💳</span>
                    {{ __('messages.settings_delivery') }}
                </p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('messages.settings_footer_source_delivery') }}</p>
                <span class="mt-3 inline-flex items-center gap-1 text-xs font-black text-violet-600 transition group-hover:gap-2 dark:text-violet-400">
                    {{ __('messages.settings_footer_edit') }} →
                </span>
            </a>
        </div>
    </section>
</div>
