@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('messages.master_data') }}</h1>
            <p class="admin-page-sub">{{ __('messages.master_data_sub') }}</p>
        </div>
    </div>

    {{-- Horizontal scroll tab bar (alinthit_pos master-data style) — tab is
         kept in the URL so refresh/back preserve it and each tab is linkable. --}}
    @php
        $tabs = [
            'categories' => [
                'label' => __('messages.categories'),
                'activeClass' => 'border-orange-300 bg-orange-100 text-orange-700 dark:border-orange-500/40 dark:bg-orange-500/15 dark:text-orange-300 shadow-sm',
                'idleClass' => 'border-slate-200 text-gray-500 dark:border-slate-700 dark:text-slate-400 hover:bg-orange-50 hover:text-orange-700 dark:hover:bg-slate-800 dark:hover:text-orange-300',
                'icon' => 'M3 6h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z',
            ],
            'brands' => [
                'label' => __('messages.brands'),
                'activeClass' => 'border-blue-300 bg-blue-100 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/15 dark:text-blue-300 shadow-sm',
                'idleClass' => 'border-slate-200 text-gray-500 dark:border-slate-700 dark:text-slate-400 hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-slate-800 dark:hover:text-blue-300',
                'icon' => 'M20 13 11 4H4v7l9 9 7-7ZM7.5 7.5h.01',
            ],
            'variant-presets' => [
                'label' => __('messages.variant_settings'),
                'activeClass' => 'border-teal-300 bg-teal-100 text-teal-700 dark:border-teal-500/40 dark:bg-teal-500/15 dark:text-teal-300 shadow-sm',
                'idleClass' => 'border-slate-200 text-gray-500 dark:border-slate-700 dark:text-slate-400 hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-slate-800 dark:hover:text-teal-300',
                'icon' => 'M4 7h10M4 17h10M18 5v4M18 15v4M14 7h8M14 17h8',
            ],
        ];
    @endphp
    <div class="flex items-center gap-2 overflow-x-auto pb-1.5 -mb-1.5" role="tablist" aria-label="{{ __('messages.master_data') }}">
        @foreach ($tabs as $key => $tab)
            <a href="{{ route('store.admin.products.master-data', ['store_slug' => $store->slug, 'tab' => $key]) }}"
                role="tab"
                @if ($activeTab === $key) aria-selected="true" @endif
                class="inline-flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-violet-500 {{ $activeTab === $key ? $tab['activeClass'] : $tab['idleClass'] }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true">
                    <path d="{{ $tab['icon'] }}"/>
                </svg>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Active tab content — the same partial the standalone index page renders. --}}
    <div>
        @if ($activeTab === 'brands')
            @include('admin.brands._content')
        @elseif ($activeTab === 'variant-presets')
            @include('admin.variant_presets._content')
        @else
            @include('admin.categories._content')
        @endif
    </div>
</div>
@endsection
