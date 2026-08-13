@php
    $tabs = [
        'sales' => ['label' => __('messages.reports_sales'), 'url' => url('/store/' . $store->slug . '/pos/reports/sales')],
        'cash' => ['label' => __('messages.reports_cash'), 'url' => url('/store/' . $store->slug . '/pos/reports/cash')],
        'stock' => ['label' => __('messages.reports_stock'), 'url' => url('/store/' . $store->slug . '/pos/reports/stock')],
    ];
@endphp

<div class="flex items-center justify-between gap-3 mb-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.reports_title') }}</p>
        <h1 class="text-xl font-black mt-0.5">{{ $tabs[$active]['label'] }}</h1>
    </div>
    <div class="flex items-center gap-2">
        <div class="flex rounded-xl bg-slate-200 dark:bg-slate-800 p-1">
            @foreach ($tabs as $key => $tab)
                <a href="{{ $tab['url'] }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $key === $active ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
        <a href="{{ url('/store/' . $store->slug . '/pos') }}"
           class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            ← {{ __('messages.back_to_pos') }}
        </a>
    </div>
</div>
