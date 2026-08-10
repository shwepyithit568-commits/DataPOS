@props([
    'name' => '',
    'label' => '',
    'iconClass' => '',
])

<div>
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
    <div id="sidebar-sub-{{ $name }}" x-show="{{ $name }}Open" x-transition :class="sidebarCollapsed ? 'lg:hidden' : ''" class="mt-1 pl-7 space-y-1">
        {{ $slot }}
    </div>
</div>
