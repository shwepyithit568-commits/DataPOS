@php
    $supportedLocales = config('localization.supported', []);
    $activeLocale = app()->getLocale();
    $activeLocaleLabel = $supportedLocales[$activeLocale]['native'] ?? $activeLocale;
    $menuAlignmentClass = $attributes->get('align') === 'left' ? 'left-0' : 'right-0';
@endphp

<form
    method="POST"
    action="{{ route('locale.update') }}"
    class="relative inline-flex shrink-0"
    aria-label="{{ __('messages.language_switcher_label') }}"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
>
    @csrf
    <span id="locale-switcher-{{ $attributes->get('id', 'default') }}-label" class="sr-only">
        {{ __('messages.language_switcher_label') }}
    </span>

    <button
        type="button"
            class="h-10 w-10 inline-flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-800 text-base shadow-sm transition hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500"
        aria-labelledby="locale-switcher-{{ $attributes->get('id', 'default') }}-label"
        aria-haspopup="menu"
        :aria-expanded="open.toString()"
        title="{{ $activeLocaleLabel }}"
        @click="open = !open"
    >
        <x-flag :code="$activeLocale" />
    </button>

    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute {{ $menuAlignmentClass }} top-full z-50 mt-2 flex min-w-[8.5rem] items-center gap-1 rounded-xl border border-slate-200 bg-white/95 p-1.5 shadow-xl shadow-slate-900/10 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95"
        role="menu"
        aria-labelledby="locale-switcher-{{ $attributes->get('id', 'default') }}-label"
    >
        @foreach ($supportedLocales as $code => $locale)
            @php
                $isActive = $activeLocale === $code;
            @endphp
            <button
                type="submit"
                name="locale"
                value="{{ $code }}"
                class="h-9 w-9 inline-flex items-center justify-center rounded-lg text-lg transition focus:outline-none focus:ring-2 focus:ring-sky-500 {{ $isActive ? 'bg-sky-100 text-sky-700 ring-1 ring-sky-300 dark:bg-sky-950 dark:text-sky-200 dark:ring-sky-700' : 'hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                role="menuitem"
                title="{{ $locale['native'] }}"
                aria-current="{{ $isActive ? 'true' : 'false' }}"
                @click="open = false"
            >
                <x-flag :code="$code" />
                <span class="sr-only">{{ $locale['native'] }}</span>
            </button>
        @endforeach
    </div>
</form>
