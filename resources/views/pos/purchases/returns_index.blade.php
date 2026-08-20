@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 space-y-6">

        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_purchases') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ __('messages.sidebar_returns') }}</h1>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                ← {{ __('messages.back') }}
            </a>
        </div>

        {{-- Search & Sort --}}
        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}" class="flex gap-2 items-end">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.po_return_search') }}"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
            </div>
            <div>
                <select name="sort" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ __('messages.po_sort_newest') }}</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ __('messages.po_sort_oldest') }}</option>
                    <option value="highest" {{ request('sort') === 'highest' ? 'selected' : '' }}>{{ __('messages.po_sort_highest') }}</option>
                    <option value="lowest" {{ request('sort') === 'lowest' ? 'selected' : '' }}>{{ __('messages.po_sort_lowest') }}</option>
                </select>
            </div>
            <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-bold bg-sky-600 hover:bg-sky-500 text-white transition">
                {{ __('messages.search') }}
            </button>
        </form>

        {{-- Returns table --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-4 py-3">{{ __('messages.po_return_col_number') }}</th>
                            <th class="text-left px-4 py-3">{{ __('messages.po_col_po_number') }}</th>
                            <th class="text-left px-4 py-3">{{ __('messages.supplier_col_name') }}</th>
                            <th class="text-right px-4 py-3">{{ __('messages.reports_qty') }}</th>
                            <th class="text-right px-4 py-3">{{ __('messages.reports_value') }}</th>
                            <th class="text-left px-4 py-3">{{ __('messages.po_return_col_reason') }}</th>
                            <th class="text-left px-4 py-3">{{ __('messages.po_return_col_date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($returns as $return)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-orange-600 dark:text-orange-400">{{ $return->return_number }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($return->purchaseOrder)
                                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                                           class="font-bold text-sky-600 dark:text-sky-400 hover:underline">
                                            {{ $return->purchaseOrder->po_number }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-bold">{{ $return->supplier?->name ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">
                                    {{ number_format((float) $return->total_quantity, 3) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold">
                                    Ks {{ number_format((float) $return->total_cost) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 truncate block max-w-[160px]">
                                        {{ $return->reason ?: '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400">
                                    {{ $return->returned_at?->format('d M Y, H:i') ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-400 dark:text-slate-500">
                                    <div class="text-3xl mb-2">🔄</div>
                                    <div class="font-semibold">{{ __('messages.po_return_none') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($returns->hasPages())
                <div class="p-3 border-t dark:border-slate-800">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
