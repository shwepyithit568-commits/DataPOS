@props([
    'name' => '',
    'label' => '',
    'iconClass' => '',
])

@php
    // Child links already own route matching. Reuse their aria-current marker
    // so the group highlight cannot drift from the active destination.
    $groupActive = str_contains((string) $slot, 'aria-current="page"');
    $triggerClasses = 'relative w-full flex min-h-11 items-center justify-between px-3 py-2 rounded-xl font-semibold transition focus:outline-none focus:ring-2 focus:ring-violet-500 '
        . ($groupActive
            ? 'bg-violet-100 text-violet-800 ring-1 ring-inset ring-violet-200 shadow-sm dark:bg-violet-500/20 dark:text-violet-100 dark:ring-violet-400/30'
            : 'text-gray-700 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-slate-800/80 hover:text-violet-700 dark:hover:text-white');
    $activeIconClasses = $groupActive
        ? 'ring-2 ring-violet-500 ring-offset-1 dark:ring-violet-400 dark:ring-offset-slate-950'
        : '';
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
                <span class="absolute inset-y-2 left-0 w-1 rounded-r-full bg-violet-600 dark:bg-violet-400" aria-hidden="true"></span>
            @endif
            <span class="flex items-center gap-3 min-w-0">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $iconClass }} {{ $activeIconClasses }}" aria-hidden="true">
                    {{ $icon ?? '' }}
                </span>
                <span :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $label }}</span>
            </span>
            <span class="flex items-center space-x-1.5 min-w-0" x-show="!sidebarCollapsed">
                {{ $badge ?? '' }}
                <svg class="w-4 h-4 transition-all duration-150 flex-shrink-0"
                     :class="[
                         (!viewportLg && {{ $name }}Open) ? 'rotate-90 text-violet-600 dark:text-violet-400' : '',
                         (viewportLg && activeHoverGroup === '{{ $name }}') ? 'translate-x-0.5 text-violet-600 dark:text-violet-400' : 'text-slate-400 dark:text-slate-500'
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
         class="mt-1 pl-7 space-y-1">
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
             class="hidden lg:block fixed z-[9999] w-72 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl ring-1 ring-slate-900/5 dark:ring-white/5 p-3">
            <div class="flex items-center gap-2.5 pb-2 mb-2 border-b border-slate-100 dark:border-slate-800 px-0.5">
                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $iconClass }} text-sm shadow-xs">
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
