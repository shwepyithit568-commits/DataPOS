@props([
    'color' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
    'title' => '',
    'subtitle' => '',
])

{{-- Colored icon section header (alinthit_pos product-form style). --}}
<div class="mb-4 flex items-center gap-2.5">
    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $color }}" aria-hidden="true">
        {{ $icon ?? '' }}
    </span>
    <div class="min-w-0">
        <h2 class="text-base font-black text-gray-900 dark:text-slate-100">{{ $title }} {{ $badge ?? '' }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>
</div>
