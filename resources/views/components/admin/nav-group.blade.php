@props([
    'name' => '',
    'label' => '',
    'iconClass' => '',
])

<div class="relative"
     @mouseenter="setHoverGroup('{{ $name }}')"
     @mouseleave="clearHoverGroup()">
    <button type="button" @click="toggleGroup('{{ $name }}')" :aria-expanded="{{ $name }}Open.toString()" aria-controls="sidebar-sub-{{ $name }}" :aria-label="sidebarCollapsed ? '{{ addslashes($label) }}' : null" :title="sidebarCollapsed ? '{{ addslashes($label) }}' : null"
        class="relative w-full flex min-h-11 items-center justify-between px-3 py-2 rounded-xl font-semibold text-gray-700 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-slate-800/80 hover:text-violet-700 dark:hover:text-white transition focus:outline-none focus:ring-2 focus:ring-violet-500" :class="sidebarCollapsed ? 'lg:justify-center' : ''">
        <span class="flex items-center gap-3 min-w-0">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $iconClass }}" aria-hidden="true">
                {{ $icon ?? '' }}
            </span>
            <span :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $label }}</span>
        </span>
        <span class="flex items-center space-x-1.5 min-w-0" x-show="!sidebarCollapsed">
            {{ $badge ?? '' }}
            <svg class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{{ $name }}Open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </span>
        {{ $cornerBadge ?? '' }}
    </button>

    {{-- Click Dropdown inside Sidebar --}}
    <div id="sidebar-sub-{{ $name }}" x-show="{{ $name }}Open" x-transition :class="sidebarCollapsed ? 'lg:hidden' : ''" class="mt-1 pl-7 space-y-1">
        {{ $slot }}
    </div>

    {{-- Hover Flyout Panel to the Right (Desktop Only, Fast Lightweight Surface) --}}
    <div x-show="activeHoverGroup === '{{ $name }}' && viewportLg && !{{ $name }}Open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 translate-x-1"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-1"
         @mouseenter="cancelHoverTimer()"
         @mouseleave="clearHoverGroup()"
         class="hidden lg:block absolute left-full top-0 ml-2 w-64 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl p-2.5 z-50 pointer-events-auto"
         style="display: none;">
        <div class="flex items-center justify-between pb-2 mb-1.5 border-b border-slate-100 dark:border-slate-800 px-1">
            <span class="text-xs font-black text-slate-900 dark:text-slate-100 flex items-center gap-2 truncate font-myanmar">
                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md {{ $iconClass }} text-xs">
                    {{ $icon ?? '' }}
                </span>
                <span class="truncate">{{ $label }}</span>
            </span>
            {{ $badge ?? '' }}
        </div>
        <div class="grid grid-cols-1 gap-0.5 max-h-[calc(100vh-160px)] overflow-y-auto pt-0.5">
            {{ $slot }}
        </div>
    </div>
</div>
