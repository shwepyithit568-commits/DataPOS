@props([
    'href' => '#',
    'routeName' => null,
    'active' => false,
    'label' => '',
    'variant' => 'sub', // 'main' (Dashboard) | 'direct' (standalone collapsed-aware link) | 'sub' (inside a group)
])

@php
    $isMain = $variant === 'main';
    $isDirect = $variant === 'direct';
    $standalone = $isMain || $isDirect;

    $linkClasses = $isMain
        ? 'group flex min-h-11 items-center gap-3 rounded-xl px-3 py-2 font-semibold transition focus:outline-none focus:ring-2 focus:ring-violet-500 '
          . ($active
                ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20'
                : 'text-gray-600 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-slate-800/80 hover:text-violet-700 dark:hover:text-white')
        : 'flex items-center justify-between gap-2 px-3 py-2.5 min-h-11 rounded-md text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-violet-500 '
          . ($active
                ? 'bg-violet-600 text-white shadow-md'
                : 'text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800/80');

    $iconWrapClasses = $isMain
        ? ($active ? 'bg-white/15' : 'bg-gray-100 dark:bg-slate-800 group-hover:bg-white dark:group-hover:bg-slate-700')
        : ($active ? 'bg-white/20' : 'bg-gray-100 dark:bg-slate-800');

    $iconWrap = $isMain ? 'h-8 w-8 shrink-0 rounded-lg' : 'h-5 w-5 shrink-0 rounded-md';
    $rowGap = $isMain ? 'gap-3' : 'gap-2';
@endphp

<a href="{{ $href }}"
    @if ($routeName) data-route-name="{{ $routeName }}" @endif
    @click="sidebarOpen = false{{ $isMain ? '; expandSidebar()' : '' }}"
    @if ($active) aria-current="page" @endif
    @if ($standalone) aria-label="{{ $label }}" @endif
    @if ($standalone) :title="sidebarCollapsed ? '{{ addslashes($label) }}' : null" @endif
    class="{{ $linkClasses }}"
    @if ($standalone) :class="sidebarCollapsed ? 'lg:justify-center' : ''" @endif
>
    <span class="flex items-center {{ $rowGap }} min-w-0">
        <span class="inline-flex {{ $iconWrap }} items-center justify-center {{ $iconWrapClasses }}" aria-hidden="true">
            {{ $icon ?? '' }}
        </span>
        <span class="{{ $isMain ? '' : 'truncate' }}" @if ($standalone) :class="sidebarCollapsed ? 'lg:hidden' : ''" @endif>{{ $label }}</span>
    </span>
    {{ $badge ?? '' }}
</a>
