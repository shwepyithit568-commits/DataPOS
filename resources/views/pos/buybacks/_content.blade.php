{{-- Buy Back Listing --}}
@php
    $currentPage = $buybacks->currentPage();
    $lastPage = $buybacks->lastPage();
    $total = $buybacks->total();
@endphp

<div class="mb-4">
    <x-admin.toolbar
        :search="$search"
        :search-placeholder="__('messages.search_buyback_placeholder')"
        :sort="request('sort', 'created_at')"
        :sort-options="[
            'created_at' => __('messages.sort_by_date'),
            'buyback_number' => __('messages.sort_by_number'),
        ]"
        :filters="[]"
        :total-count="$total"
        :per-page="(int) request('per_page', 25)"
        :show-view-toggle="true"
        :show-export-import="false"
        view-mode="table"
        :show-pagination="false"
    />
</div>

<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-2 border-b border-neutral-200 dark:border-white/10">
        <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}"
           class="px-4 py-2.5 text-sm font-medium border-b-2 border-blue-600 dark:border-emerald-400 text-blue-600 dark:text-emerald-400">
            ↩️ {{ __('messages.sidebar_buy_back') }}
            <span class="ml-1.5 inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-xs font-medium rounded-full bg-neutral-100 text-neutral-600 dark:bg-white/10 dark:text-neutral-300">{{ $total }}</span>
        </a>
    </div>
    <a href="{{ route('pos.buybacks.create', $storeRouteParams) }}"
       class="inline-flex items-center gap-1.5 px-3 h-8 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-emerald-600 dark:hover:bg-emerald-700 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        {{ __('messages.new_buyback') }}
    </a>
</div>

@if($buybacks->isEmpty())
    <div class="text-center py-12 bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-white/10">
        <div class="text-4xl mb-3">↩️</div>
        <p class="text-neutral-500 dark:text-neutral-400">{{ __('messages.no_buybacks_found') }}</p>
        <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">{{ __('messages.buyback_empty_hint') }}</p>
    </div>
@else
    <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 dark:border-white/10">
                        <th class="text-left px-4 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.buyback_number') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.items') }}</th>
                        <th class="text-right px-4 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.total_value') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.status') }}</th>
                        <th class="text-left px-4 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.date') }}</th>
                        <th class="text-right px-4 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($buybacks as $buyback)
                        <tr class="border-b border-neutral-100 dark:border-white/5 hover:bg-neutral-50 dark:hover:bg-white/[2%] transition-colors">
                            <td class="px-4 py-3 font-mono text-xs">
                                <a href="{{ route('pos.buybacks.show', [...$storeRouteParams, 'buyback' => $buyback->id]) }}"
                                   class="text-blue-600 dark:text-emerald-400 hover:underline">{{ $buyback->buyback_number }}</a>
                            </td>
                            <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400">{{ $buyback->items->count() }}</td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-neutral-700 dark:text-neutral-300">{{ number_format($buyback->total_value, 2) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                                        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                                        'cancelled' => 'bg-neutral-100 text-neutral-500 dark:bg-white/10 dark:text-neutral-400',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full {{ $statusColors[$buyback->status] ?? '' }}">
                                    {{ __('messages.' . $buyback->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-neutral-500 dark:text-neutral-400">{{ $buyback->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('pos.buybacks.show', [...$storeRouteParams, 'buyback' => $buyback->id]) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 text-neutral-500 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-lg transition-colors" title="{{ __('messages.view') }}">
                                    👁️
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $buybacks->withQueryString()->links('pagination::tailwind') }}
    </div>
@endif
