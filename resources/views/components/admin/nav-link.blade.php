@props([
    'href' => '#',
    'routeName' => null,
    'active' => false,
    'label' => '',
    'variant' => 'sub', // 'main' (Dashboard) | 'direct' (standalone collapsed-aware link) | 'sub' (inside a group) | 'placeholder' (roadmap link to the coming-soon page)
    'group' => null,
])

@php
    $isMain = $variant === 'main';
    $isDirect = $variant === 'direct';
    $isPlaceholder = $variant === 'placeholder';
    $standalone = $isMain || $isDirect;

    $linkThemeMap = [
        'pos' => [
            'active_bg' => 'sf-btn-3d-primary',
            'hover_border' => 'hover:border-blue-300/80 dark:hover:border-blue-800',
        ],
        'inventory' => [
            'active_bg' => 'sf-btn-3d-gold',
            'hover_border' => 'hover:border-amber-300/80 dark:hover:border-amber-800',
        ],
        'purchasing' => [
            'active_bg' => 'sf-btn-3d-orange',
            'hover_border' => 'hover:border-orange-300/80 dark:hover:border-orange-800',
        ],
        'ecommerce' => [
            'active_bg' => 'sf-btn-3d-success',
            'hover_border' => 'hover:border-emerald-300/80 dark:hover:border-emerald-800',
        ],
        'customers' => [
            'active_bg' => 'sf-btn-3d-teal',
            'hover_border' => 'hover:border-teal-300/80 dark:hover:border-teal-800',
        ],
        'service' => [
            'active_bg' => 'sf-btn-3d-indigo',
            'hover_border' => 'hover:border-indigo-300/80 dark:hover:border-indigo-800',
        ],
        'finance' => [
            'active_bg' => 'sf-btn-3d-lime',
            'hover_border' => 'hover:border-lime-300/80 dark:hover:border-lime-800',
        ],
        'reports' => [
            'active_bg' => 'sf-btn-3d-cyan',
            'hover_border' => 'hover:border-cyan-300/80 dark:hover:border-cyan-800',
        ],
        'security' => [
            'active_bg' => 'sf-btn-3d-danger',
            'hover_border' => 'hover:border-rose-300/80 dark:hover:border-rose-800',
        ],
        'maintenance' => [
            'active_bg' => 'sf-btn-3d-slate',
            'hover_border' => 'hover:border-slate-300/80 dark:hover:border-slate-700',
        ],
        'setup' => [
            'active_bg' => 'sf-btn-3d-fuchsia',
            'hover_border' => 'hover:border-fuchsia-300/80 dark:hover:border-fuchsia-800',
        ],
        'dashboard' => [
            'active_bg' => 'sf-btn-3d-telegram',
            'hover_border' => 'hover:border-sky-300/80 dark:hover:border-sky-800',
        ],
        'platform_dashboard' => [
            'active_bg' => 'sf-btn-3d-telegram',
            'hover_border' => 'hover:border-sky-300/80 dark:hover:border-sky-800',
        ],
        'platform_stores' => [
            'active_bg' => 'sf-btn-3d-success',
            'hover_border' => 'hover:border-emerald-300/80 dark:hover:border-emerald-800',
        ],
        'platform_theme_governance' => [
            'active_bg' => 'sf-btn-3d-accent',
            'hover_border' => 'hover:border-purple-300/80 dark:hover:border-purple-800',
        ],
    ];

    $groupKey = (string) $group;
    $theme = $linkThemeMap[$groupKey] ?? [
        'active_bg' => 'sf-btn-3d-telegram',
        'hover_border' => 'hover:border-sky-300/80 dark:hover:border-sky-800',
    ];

    $linkClasses = $isMain
        ? 'group flex w-full min-h-11 items-center justify-between gap-3 rounded-xl px-3 py-2 font-semibold transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-sky-500 '
          . ($active
                ? $theme['active_bg'] . ' active:translate-y-0.5 active:border-b'
                : 'nav-idle-trigger shadow-2xs hover:shadow-xs hover:-translate-y-0.5 active:translate-y-0.5 active:border-b')
        : 'group flex w-full items-center justify-between gap-2 px-3 py-2 min-h-10 rounded-lg text-xs font-semibold transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-sky-500 '
          . ($active
                ? $theme['active_bg'] . ' active:translate-y-0.5 active:border-b'
                : ($isPlaceholder
                    ? 'text-slate-400 dark:text-slate-500 hover:text-sky-600 dark:hover:text-sky-300'
                    : 'nav-idle-sublink shadow-2xs hover:shadow-xs hover:-translate-y-0.5 active:translate-y-0.5 active:border-b'));

    $mainBadgeMap = [
        'dashboard' => 'sf-btn-3d-telegram',
        'platform_dashboard' => 'sf-btn-3d-telegram',
        'platform_stores' => 'sf-btn-3d-success',
        'platform_theme_governance' => 'sf-btn-3d-accent',
    ];
    $mainBadgeClass = $mainBadgeMap[$groupKey] ?? 'sf-btn-3d-telegram';

    $iconWrapClasses = $isMain
        ? ($active
            ? 'bg-white/20 border border-white/30 text-white shadow-inner'
            : $mainBadgeClass)
        : ($active
            ? 'bg-white/20 border border-white/30 text-white shadow-inner'
            : 'bg-slate-100 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/80 text-slate-600 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white shadow-2xs');

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
    @if ($isPlaceholder)
        <span class="shrink-0 rounded-full border border-violet-300/70 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-500 dark:border-violet-500/30 dark:text-violet-300">{{ __('messages.coming_soon_short') }}</span>
    @endif
</a>
