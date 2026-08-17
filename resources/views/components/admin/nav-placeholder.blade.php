@props([
    'href' => '#',
    'label' => '',
])

{{-- Roadmap placeholder link — points at the single coming-soon page. --}}
<x-admin.nav-link variant="placeholder" :href="$href" :label="$label">
    <x-slot:icon>
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 7v5l3 2"/>
        </svg>
    </x-slot:icon>
</x-admin.nav-link>
