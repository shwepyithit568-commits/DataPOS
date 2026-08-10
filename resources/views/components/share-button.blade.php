@props([
    'url' => url('/'),
    'title' => config('app.name'),
    'label' => __('messages.share'),
    'buttonClass' => '',
    // Icon-only on mobile (label appears from sm up) for tight action rows.
    'hideLabelOnMobile' => false,
    // Only render an app row when the store actually uses that channel
    // (matches the app-wide rule: no icon for a channel the store lacks).
    'showViber' => false,
    'showTelegram' => false,
    'showFacebook' => false,
])

@php
    $shareText = $title . ' — ' . $url;
    $telegramShareUrl = 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($title);
    $facebookShareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url);
    $viberForwardUrl = 'viber://forward?text=' . rawurlencode($shareText);
@endphp

<div
    class="relative"
    x-data="shareAction"
    data-share-url="{{ $url }}"
    data-share-title="{{ $title }}"
    data-copy-label="{{ __('messages.copy_link') }}"
    data-copied-label="{{ __('messages.copied') }}"
    @click.outside="shareOpen = false"
>
    <button
        type="button"
        @click="share()"
        :aria-expanded="shareOpen ? 'true' : 'false'"
        aria-haspopup="true"
        title="{{ $label }}"
        class="{{ $buttonClass }} inline-flex items-center gap-1.5"
    >
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/>
        </svg>
        <span @if ($hideLabelOnMobile) class="hidden sm:inline" @endif>{{ $label }}</span>
    </button>

    {{-- Fallback menu: browsers without navigator.share (or when the user cancels) --}}
    <div
        x-show="shareOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute bottom-full left-0 z-50 mb-2 w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl dark:border-slate-700 dark:bg-slate-800"
        role="menu"
    >
        <p class="px-2.5 pb-1 pt-1.5 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('messages.share_via') }}</p>
        @if ($showViber)
            <a href="{{ $viberForwardUrl }}" class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700" role="menuitem" title="{{ __('messages.share_via_viber') }}">
                <x-brand-icon brand="viber" class="h-4 w-4 text-violet-600 dark:text-violet-400"/> {{ __('messages.share_via_viber') }}
            </a>
        @endif
        @if ($showTelegram)
            <a href="{{ $telegramShareUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700" role="menuitem">
                <x-brand-icon brand="telegram" class="h-4 w-4 text-sky-600 dark:text-sky-400"/> Telegram
            </a>
        @endif
        @if ($showFacebook)
            <a href="{{ $facebookShareUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700" role="menuitem">
                <x-brand-icon brand="facebook" class="h-4 w-4 text-blue-600 dark:text-blue-400"/> Facebook
            </a>
        @endif
        <button type="button" @click="copy()" class="flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700" role="menuitem">
            <svg class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v8.25A2.25 2.25 0 006 16.5h2.25m8.25-8.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-7.5A2.25 2.25 0 018.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 00-2.25 2.25v6"/></svg>
            <span x-text="copied ? copiedLabel : copyLabel"></span>
        </button>
    </div>
</div>
