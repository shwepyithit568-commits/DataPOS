@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-6 space-y-5"
         x-data="{ reverseConfirm: false, reverseId: null, reverseNumber: '' }">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_purchases') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ __('messages.sidebar_returns') }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases/returns/export?' . http_build_query(request()->query())) }}"
                   class="rounded-xl px-4 py-2.5 text-sm font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition inline-flex items-center gap-1.5">
                    📥 {{ __('messages.po_return_export') }}
                </a>
                <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
                   class="rounded-xl px-4 py-2.5 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                    ← {{ __('messages.back') }}
                </a>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('messages.po_return_total_returns') }}</p>
                <p class="text-2xl font-black mt-1 font-mono">{{ number_format($summary->count) }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('messages.po_return_total_value') }}</p>
                <p class="text-2xl font-black mt-1 font-mono text-orange-600 dark:text-orange-400">Ks {{ number_format((float) $summary->total_cost) }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('messages.po_return_total_qty') }}</p>
                <p class="text-2xl font-black mt-1 font-mono">{{ number_format((float) $summary->total_qty, 3) }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}"
              class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 shadow-sm space-y-3">
            {{-- Row 1: Search + Sort --}}
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">{{ __('messages.search') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.po_return_search') }}"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">{{ __('messages.sort') ?? 'Sort' }}</label>
                    <select name="sort" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ __('messages.po_sort_newest') }}</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ __('messages.po_sort_oldest') }}</option>
                        <option value="highest" {{ request('sort') === 'highest' ? 'selected' : '' }}>{{ __('messages.po_sort_highest') }}</option>
                        <option value="lowest" {{ request('sort') === 'lowest' ? 'selected' : '' }}>{{ __('messages.po_sort_lowest') }}</option>
                    </select>
                </div>
            </div>

            {{-- Row 2: Date range + Supplier --}}
            <div class="flex gap-2 items-end flex-wrap">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">{{ __('messages.po_return_filter_date_from') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">{{ __('messages.po_return_filter_date_to') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">{{ __('messages.po_supplier') }}</label>
                    <select name="supplier_id" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        <option value="">{{ __('messages.po_return_filter_supplier') }}</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-xl px-5 py-2.5 text-sm font-bold bg-sky-600 hover:bg-sky-500 text-white transition">
                        {{ __('messages.search') }}
                    </button>
                    @if (request()->hasAny(['search', 'date_from', 'date_to', 'supplier_id', 'sort']))
                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}"
                           class="rounded-xl px-4 py-2.5 text-sm font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                            ✕ {{ __('messages.clear') ?? 'Clear' }}
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Success / Error --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- Desktop Table --}}
        <div class="hidden md:block rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
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
                            <th class="text-center px-4 py-3">Status</th>
                            <th class="text-center px-4 py-3">{{ __('messages.actions') ?? 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($returns as $return)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 {{ $return->isReversed() ? 'opacity-50' : '' }}">
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
                                <td class="px-4 py-3 text-center">
                                    @if ($return->isReversed())
                                        <span class="inline-block rounded-lg px-2.5 py-1 text-[11px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                            {{ __('messages.po_return_status_reversed') }}
                                        </span>
                                    @else
                                        <span class="inline-block rounded-lg px-2.5 py-1 text-[11px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            {{ __('messages.po_return_status_active') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if (! $return->isReversed())
                                        <button type="button"
                                                @click="reverseConfirm = true; reverseId = {{ $return->id }}; reverseNumber = '{{ $return->return_number }}'"
                                                class="rounded-lg px-3 py-1.5 text-[11px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 hover:bg-rose-100 dark:hover:bg-rose-900/40 transition">
                                            🔄 {{ __('messages.po_return_reverse_btn') }}
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-slate-400 dark:text-slate-500">
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

        {{-- Mobile Cards --}}
        <div class="md:hidden space-y-3">
            @forelse ($returns as $return)
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden {{ $return->isReversed() ? 'opacity-50' : '' }}">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-orange-600 dark:text-orange-400">{{ $return->return_number }}</span>
                                    @if ($return->isReversed())
                                        <span class="inline-block rounded-lg px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                            {{ __('messages.po_return_status_reversed') }}
                                        </span>
                                    @else
                                        <span class="inline-block rounded-lg px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            {{ __('messages.po_return_status_active') }}
                                        </span>
                                    @endif
                                </div>
                                @if ($return->purchaseOrder)
                                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/' . $return->purchaseOrder->id) }}"
                                       class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline mt-0.5 block">
                                        {{ $return->purchaseOrder->po_number }}
                                    </a>
                                @endif
                            </div>
                            <p class="font-mono font-black text-sm whitespace-nowrap text-orange-600 dark:text-orange-400">
                                Ks {{ number_format((float) $return->total_cost) }}
                            </p>
                        </div>

                        <div class="mt-2 flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                            @if ($return->supplier)
                                <span class="font-bold">{{ $return->supplier->name }}</span>
                            @endif
                            <span class="font-mono">{{ number_format((float) $return->total_quantity, 3) }} {{ __('messages.reports_units') }}</span>
                        </div>

                        @if ($return->reason)
                            <p class="mt-1.5 text-xs text-slate-400 truncate">{{ $return->reason }}</p>
                        @endif

                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">{{ $return->returned_at?->format('d M Y, H:i') ?? '—' }}</span>
                            @if (! $return->isReversed())
                                <button type="button"
                                        @click="reverseConfirm = true; reverseId = {{ $return->id }}; reverseNumber = '{{ $return->return_number }}'"
                                        class="rounded-lg px-3 py-1.5 text-[11px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 hover:bg-rose-100 dark:hover:bg-rose-900/40 transition">
                                    🔄 {{ __('messages.po_return_reverse_btn') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm p-10 text-center text-slate-400 dark:text-slate-500">
                    <div class="text-3xl mb-2">🔄</div>
                    <div class="font-semibold">{{ __('messages.po_return_none') }}</div>
                </div>
            @endforelse

            @if ($returns->hasPages())
                <div class="py-2">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>

        {{-- Reverse Confirmation Modal --}}
        <div x-show="reverseConfirm" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/50" @click="reverseConfirm = false" aria-hidden="true"></div>
            <div class="relative min-h-full flex items-center justify-center p-4 py-6">
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-lg">
                            ⚠️
                        </div>
                        <div>
                            <h3 class="text-lg font-black">{{ __('messages.po_return_reverse_btn') }} — <span class="font-mono" x-text="reverseNumber"></span></h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('messages.po_return_confirm_reverse') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" @click="reverseConfirm = false"
                                class="flex-1 rounded-xl px-4 h-12 text-sm font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                            {{ __('messages.cancel') }}
                        </button>
                        <form method="POST" :action="'{{ url('/store/' . $store->slug . '/pos/purchases/returns') }}/' + reverseId + '/reverse'" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full rounded-xl px-6 h-12 text-sm font-black bg-rose-600 hover:bg-rose-500 text-white transition">
                                🔄 {{ __('messages.po_return_reverse_btn') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
