@props([
    'name' => '',
    'label' => '',
    'iconClass' => '',
])

@php
    // Group-specific visual color themes for admin navigation (Guaranteed 100% display in Light & Dark Mode)
    $groupThemes = [
        'pos' => [
            'badge' => 'sf-btn-3d-primary',
            'active_trigger' => 'nav-active-pos',
            'hover_trigger' => 'hover:border-blue-300 dark:hover:border-blue-800',
            'indicator' => 'bg-blue-500',
            'ring' => 'ring-2 ring-blue-500 ring-offset-1 dark:ring-blue-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-pos',
            'arrow' => 'nav-arrow-pos',
        ],
        'inventory' => [
            'badge' => 'sf-btn-3d-gold',
            'active_trigger' => 'nav-active-inventory',
            'hover_trigger' => 'hover:border-amber-300 dark:hover:border-amber-800',
            'indicator' => 'bg-amber-500',
            'ring' => 'ring-2 ring-amber-500 ring-offset-1 dark:ring-amber-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-inventory',
            'arrow' => 'nav-arrow-inventory',
        ],
        'purchasing' => [
            'badge' => 'sf-btn-3d-orange',
            'active_trigger' => 'nav-active-purchasing',
            'hover_trigger' => 'hover:border-orange-300 dark:hover:border-orange-800',
            'indicator' => 'bg-orange-500',
            'ring' => 'ring-2 ring-orange-500 ring-offset-1 dark:ring-orange-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-purchasing',
            'arrow' => 'nav-arrow-purchasing',
        ],
        'ecommerce' => [
            'badge' => 'sf-btn-3d-success',
            'active_trigger' => 'nav-active-ecommerce',
            'hover_trigger' => 'hover:border-emerald-300 dark:hover:border-emerald-800',
            'indicator' => 'bg-emerald-500',
            'ring' => 'ring-2 ring-emerald-500 ring-offset-1 dark:ring-emerald-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-ecommerce',
            'arrow' => 'nav-arrow-ecommerce',
        ],
        'customers' => [
            'badge' => 'sf-btn-3d-teal',
            'active_trigger' => 'nav-active-customers',
            'hover_trigger' => 'hover:border-teal-300 dark:hover:border-teal-800',
            'indicator' => 'nav-indicator-customers',
            'ring' => 'ring-2 ring-teal-500 ring-offset-1 dark:ring-teal-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-customers',
            'arrow' => 'nav-arrow-customers',
        ],
        'service' => [
            'badge' => 'sf-btn-3d-indigo',
            'active_trigger' => 'nav-active-service',
            'hover_trigger' => 'hover:border-indigo-300 dark:hover:border-indigo-800',
            'indicator' => 'bg-indigo-500',
            'ring' => 'ring-2 ring-indigo-500 ring-offset-1 dark:ring-indigo-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-service',
            'arrow' => 'nav-arrow-service',
        ],
        'finance' => [
            'badge' => 'sf-btn-3d-lime',
            'active_trigger' => 'nav-active-finance',
            'hover_trigger' => 'hover:border-lime-300 dark:hover:border-lime-800',
            'indicator' => 'nav-indicator-finance',
            'ring' => 'ring-2 ring-lime-500 ring-offset-1 dark:ring-lime-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-finance',
            'arrow' => 'nav-arrow-finance',
        ],
        'reports' => [
            'badge' => 'sf-btn-3d-cyan',
            'active_trigger' => 'nav-active-reports',
            'hover_trigger' => 'hover:border-cyan-300 dark:hover:border-cyan-800',
            'indicator' => 'bg-cyan-500',
            'ring' => 'ring-2 ring-cyan-500 ring-offset-1 dark:ring-cyan-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-reports',
            'arrow' => 'nav-arrow-reports',
        ],
        'security' => [
            'badge' => 'sf-btn-3d-danger',
            'active_trigger' => 'nav-active-security',
            'hover_trigger' => 'hover:border-rose-300 dark:hover:border-rose-800',
            'indicator' => 'bg-rose-500',
            'ring' => 'ring-2 ring-rose-500 ring-offset-1 dark:ring-rose-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-security',
            'arrow' => 'nav-arrow-security',
        ],
        'maintenance' => [
            'badge' => 'sf-btn-3d-slate',
            'active_trigger' => 'nav-active-maintenance',
            'hover_trigger' => 'hover:border-slate-300 dark:hover:border-slate-700',
            'indicator' => 'bg-slate-500',
            'ring' => 'ring-2 ring-slate-500 ring-offset-1 dark:ring-slate-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-maintenance',
            'arrow' => 'nav-arrow-maintenance',
        ],
        'setup' => [
            'badge' => 'sf-btn-3d-fuchsia',
            'active_trigger' => 'nav-active-setup',
            'hover_trigger' => 'hover:border-fuchsia-300 dark:hover:border-fuchsia-800',
            'indicator' => 'nav-indicator-setup',
            'ring' => 'ring-2 ring-fuchsia-500 ring-offset-1 dark:ring-fuchsia-400 dark:ring-offset-slate-950',
            'accordion' => 'nav-accordion-setup',
            'arrow' => 'nav-arrow-setup',
        ],
    ];

    $groupKey = (string) $name;
    $theme = $groupThemes[$groupKey] ?? [
        'badge' => 'sf-btn-3d-telegram',
        'active_trigger' => 'nav-active-dashboard',
        'hover_trigger' => 'hover:border-sky-300 dark:hover:border-sky-800',
        'indicator' => 'bg-sky-500',
        'ring' => 'ring-2 ring-sky-500 ring-offset-1 dark:ring-sky-400 dark:ring-offset-slate-950',
        'accordion' => 'border-slate-200/80 dark:border-slate-800',
        'arrow' => 'text-sky-600 dark:text-sky-400',
    ];

    // Child links already own route matching. Reuse their aria-current marker
    // so the group highlight cannot drift from the active destination.
    $groupActive = str_contains((string) $slot, 'aria-current="page"');
    $triggerClasses = 'relative w-full flex min-h-11 items-center justify-between px-3 py-2 rounded-xl font-semibold transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-sky-500 '
        . ($groupActive
            ? $theme['active_trigger'] . ' active:translate-y-0.5 active:border-b shadow-xs'
            : 'nav-idle-trigger shadow-2xs hover:shadow-xs hover:-translate-y-0.5 active:translate-y-0.5 active:border-b');

    $finalBadgeClass = $theme['badge'];
    $activeIconClasses = $groupActive ? $theme['ring'] : '';
@endphp

{{--
    Desktop Hover Flyout — uses x-teleport to escape nav overflow clipping.
    The flyout is teleported to <body> and positioned with fixed coordinates
    so it always appears to the RIGHT of the sidebar, never clipped.

    Hover bridge: mouseenter/leave on both the trigger button wrapper AND the
    flyout panel share the same 250ms debounced timer so moving the mouse
    diagonally from sidebar → flyout never closes the panel.
--}}
<div class="relative">
    {{-- Button trigger wrapper --}}
    <div @mouseenter="openHoverGroup('{{ $name }}', $el)"
         @focusin="openHoverGroup('{{ $name }}', $el)"
         @mouseleave="
            if (!viewportLg) return;
            if (hoverTimer) clearTimeout(hoverTimer);
            hoverTimer = setTimeout(() => { activeHoverGroup = null }, 250);
         "
         @focusout="clearHoverGroup()">
        <button type="button"
                data-nav-trigger
                data-nav-group-active="{{ $groupActive ? 'true' : 'false' }}"
                @click="toggleGroup('{{ $name }}')"
                :aria-expanded="{{ $name }}Open.toString()"
                aria-controls="sidebar-sub-{{ $name }}"
                :aria-label="sidebarCollapsed ? '{{ addslashes($label) }}' : null"
                :title="sidebarCollapsed ? '{{ addslashes($label) }}' : null"
                class="{{ $triggerClasses }}" :class="sidebarCollapsed ? 'lg:justify-center' : ''">
            @if ($groupActive)
                <span class="absolute inset-y-2 left-0 w-1 rounded-r-full {{ $theme['indicator'] }} shadow-xs" aria-hidden="true"></span>
            @endif
            <span class="flex items-center gap-3 min-w-0">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $finalBadgeClass }} {{ $activeIconClasses }}" aria-hidden="true">
                    {{ $icon ?? '' }}
                </span>
                <span :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $label }}</span>
            </span>
            <span class="flex items-center space-x-1.5 min-w-0" x-show="!sidebarCollapsed">
                {{ $badge ?? '' }}
                <svg class="w-4 h-4 transition-all duration-150 flex-shrink-0"
                     :class="[
                         (!viewportLg && {{ $name }}Open) ? 'rotate-90 {{ $theme['arrow'] }}' : '',
                         (viewportLg && activeHoverGroup === '{{ $name }}') ? 'translate-x-0.5 {{ $theme['arrow'] }}' : 'text-slate-400 dark:text-slate-500'
                     ]"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
            {{ $cornerBadge ?? '' }}
        </button>
    </div>

    {{-- In-sidebar accordion (mobile and expanded desktop) --}}
    <div id="sidebar-sub-{{ $name }}"
         x-show="(!sidebarCollapsed || !viewportLg) && {{ $name }}Open"
         x-transition
         :class="sidebarCollapsed ? 'lg:hidden' : ''"
         class="mt-1 pl-3.5 space-y-1 border-l-2 {{ $theme['accordion'] }} ml-4 py-1">
        {{ $slot }}
    </div>

    {{--
        Desktop Flyout: Teleported to <body> so it is never clipped by
        nav overflow-y-auto. Positioned with `fixed` top/left coordinates
        captured from the trigger button's getBoundingClientRect().
    --}}
    <template x-teleport="body">
        <div x-cloak
             x-show="viewportLg && activeHoverGroup === '{{ $name }}'"
             data-nav-flyout="{{ $name }}"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 -translate-x-1"
             x-transition:enter-end="opacity-100 scale-100 translate-x-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-x-1"
             @mouseenter="cancelHoverTimer(); activeHoverGroup = '{{ $name }}'"
             @focusin="cancelHoverTimer(); activeHoverGroup = '{{ $name }}'"
             @mouseleave="clearHoverGroup()"
             @focusout="clearHoverGroup()"
             :style="{ top: `${hoverFlyoutTop}px`, left: `${hoverFlyoutSidebarRight + 8}px` }"
             class="hidden lg:block fixed z-[9999] w-72 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 border-b-2 border-b-slate-300 dark:border-slate-700 dark:border-b-slate-950 shadow-2xl p-3">
            <div class="flex items-center gap-2.5 pb-2 mb-2 border-b border-slate-100 dark:border-slate-800 px-0.5">
                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $finalBadgeClass }} text-sm shadow-xs">
                    {{ $icon ?? '' }}
                </span>
                <span class="text-sm font-black text-slate-900 dark:text-slate-100 truncate font-myanmar flex-1">{{ $label }}</span>
                {{ $badge ?? '' }}
            </div>
            <div class="flex flex-col gap-0.5 max-h-[calc(100vh-180px)] overflow-y-auto">
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
