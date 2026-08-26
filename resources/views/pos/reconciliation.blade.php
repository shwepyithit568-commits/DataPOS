@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_reconciliation') . ' - ' . $store->name)

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];
        
        $totalProducts = $report['products'] ?? 0;
        $diffProducts = $report['diff_products'] ?? 0;
        $totalDiff = (float)($report['total_diff'] ?? 0);
        $isClean = $report['clean'] ?? false;
    @endphp

    <div class="space-y-6 pb-12" 
         x-data="{ 
             onlyDiffs: false, 
             searchQuery: '',
             reconcileModalOpen: false,
             matchesFilter(name, sku, hasDiff) {
                 const matchesSearch = this.searchQuery === '' || 
                     name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                     sku.toLowerCase().includes(this.searchQuery.toLowerCase());
                 const matchesDiff = !this.onlyDiffs || hasDiff;
                 return matchesSearch && matchesDiff;
             }
         }">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center p-2.5 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 font-bold text-xl">
                        ⚖️
                    </span>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ __('messages.sidebar_stock_reconciliation') }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ __('messages.reconciliation_subtitle') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                @if ($isManager && !$isClean)
                    <button type="button" @click="reconcileModalOpen = true"
                            class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs sm:text-sm font-bold bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-600/20 transition cursor-pointer">
                        <span>⚖️</span> အလိုအလျောက် ညှိနှိုင်းပြင်ဆင်မည်
                    </button>
                @endif
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition border border-slate-200 dark:border-slate-700">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>Print ရှင်းတမ်း</span>
                </button>
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session('error'))
            <div class="rounded-2xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 px-5 py-4 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <span class="text-xl">⚠️</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 px-5 py-4 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <span class="text-xl">✅</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        {{-- KPI Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">စစ်ဆေးပြီး ပစ္စည်းစုစုပေါင်း</span>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">{{ number_format($totalProducts) }}</p>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Total Audited Items</span>
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 border p-4 sm:p-5 shadow-sm {{ $isClean ? 'border-emerald-200 dark:border-emerald-900/50' : 'border-amber-200 dark:border-amber-900/50' }}">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider {{ $isClean ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">ကွာဟချက်ရှိသော ပစ္စည်း</span>
                    <span class="p-1 rounded-lg {{ $isClean ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">⚖️</span>
                </div>
                <p class="text-2xl font-black font-mono mt-1 {{ $isClean ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ number_format($diffProducts) }}
                </p>
                <span class="text-[11px] text-slate-500 mt-0.5 block">{{ $isClean ? 'Clean (ကိုက်ညီပါသည်)' : 'Requires Adjustment' }}</span>
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">စုစုပေါင်း ကွာဟချက် အရေအတွက်</span>
                <p class="text-2xl font-black font-mono mt-1 {{ $totalDiff != 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                    {{ $totalDiff > 0 ? '+' : '' }}{{ number_format($totalDiff, 3) }}
                </p>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Net Stock Variance</span>
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">စာရင်း အခြေအနေ</span>
                <p class="text-base font-black mt-2 {{ $isClean ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ $isClean ? '✨ အားလုံး ကိုက်ညီပါသည်' : '⚠️ ကွာဟချက် စစ်ဆေးရန်' }}
                </p>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Audit Status</span>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 rounded-2xl shadow-sm">
            <div class="relative flex-1 max-w-md">
                <input type="text" x-model="searchQuery"
                       placeholder="ကုန်ပစ္စည်းအမည် သို့မဟုတ် SKU ဖြင့် စစ်ဆေးရန်..."
                       class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3.5 py-2 pl-9 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="onlyDiffs = false"
                        :class="!onlyDiffs ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                        class="px-3.5 py-1.5 rounded-xl text-xs transition font-semibold">
                    အားလုံးပြရန် ({{ $totalProducts }})
                </button>
                <button type="button" @click="onlyDiffs = true"
                        :class="onlyDiffs ? 'bg-sky-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                        class="px-3.5 py-1.5 rounded-xl text-xs transition font-semibold flex items-center gap-1">
                    <span>⚠️ ကွာဟချက်ရှိသည်များ ({{ $diffProducts }})</span>
                </button>
            </div>
        </div>

        {{-- Discrepancy Table --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📋</span> စတော့ ကွာဟချက် နှိုင်းယှဉ်ချက် ရှင်းတမ်း
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Import သွင်းခဲ့သော အဖွင့်စာရင်းနှင့် Ledger ရှိ မှတ်တမ်းများအား ကုန်ပစ္စည်းတစ်ခုချင်းစီအလိုက် စစ်ဆေးမှု
                    </p>
                </div>
            </div>

            @if (empty($report['rows']) || $totalProducts === 0)
                <div class="p-12 text-center text-slate-500">
                    <p class="text-3xl mb-2">⚖️</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.reconciliation_none') }}</p>
                    <p class="text-xs text-slate-400 mt-1">ကွာဟချက် ညှိနှိုင်းရန် ကုန်ပစ္စည်း စာရင်းများ မရှိသေးပါ</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="text-left px-5 py-3.5">{{ __('messages.product') }}</th>
                                <th class="text-right px-4 py-3.5">Import အဖွင့်စာရင်း</th>
                                <th class="text-right px-4 py-3.5">Ledger မှတ်တမ်း</th>
                                <th class="text-right px-4 py-3.5">ကွာဟချက် (Variance)</th>
                                <th class="text-right px-5 py-3.5">လက်ရှိ On-Hand</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($report['rows'] as $row)
                                @php 
                                    $hasDiff = abs((float) ($row['diff'] ?? 0)) > 0.0001; 
                                    $productName = $row['product_name'] ?? '—';
                                    $productSku = $row['sku'] ?? '';
                                @endphp
                                <tr x-show="matchesFilter('{{ addslashes($productName) }}', '{{ addslashes($productSku) }}', {{ $hasDiff ? 'true' : 'false' }})" 
                                    class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition {{ $hasDiff ? 'bg-amber-50/30 dark:bg-amber-950/20' : '' }}">
                                    <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            @if ($hasDiff)
                                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            @else
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            @endif
                                            <span>{{ $productName }}</span>
                                        </div>
                                        @if (!empty($productSku))
                                            <span class="text-xs font-mono text-slate-400 block mt-0.5 ml-4">SKU: {{ $productSku }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono text-slate-600 dark:text-slate-300">
                                        {{ number_format((float) ($row['imported_qty'] ?? 0), 3) }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono text-slate-600 dark:text-slate-300">
                                        {{ number_format((float) ($row['recorded_qty'] ?? 0), 3) }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-black {{ $hasDiff ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                        {{ (float) ($row['diff'] ?? 0) > 0 ? '+' : '' }}{{ number_format((float) ($row['diff'] ?? 0), 3) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                        {{ number_format((float) ($row['on_hand'] ?? 0), 3) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Past Reconciliation Records --}}
        @if (!empty($history) && $history->isNotEmpty())
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📜</span> ယခင် ညှိနှိုင်းပြင်ဆင်ခဲ့သော မှတ်တမ်းများ
                </h3>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($history as $h)
                        <div class="py-3 flex items-center justify-between gap-3 text-sm">
                            <div>
                                <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $h->reconciliation_number }}</span>
                                <span class="text-xs text-slate-500 block mt-0.5">
                                    အတည်ပြုသူ: {{ $h->approver?->name ?? '—' }} · ရက်စွဲ: {{ $h->created_at->format('d M Y, H:i') }}
                                </span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                ပြီးစီး
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Auto-Reconcile Modal --}}
        <div x-show="reconcileModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4"
             x-transition>
            <div class="relative w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-2xl space-y-4"
                 @click.outside="reconcileModalOpen = false">
                
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-sky-500/10 text-sky-600 font-bold">⚖️</span>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">ကွာဟချက်များအား အလိုအလျောက် ညှိနှိုင်းမည်</h3>
                    </div>
                    <button type="button" @click="reconcileModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    မန်နေဂျာမှ အတည်ပြုပါက လက်ရှိ စစ်ဆေးတွေ့ရှိသော ကွာဟချက် (<strong>{{ $diffProducts }}</strong>) ခုအား Stock Movements ဖြင့် အလိုအလျောက် ညှိနှိုင်းတင်သွင်းပေးသွားမည် ဖြစ်ပါသည်။
                </p>

                <form method="POST" action="{{ route('pos.reconciliation.approve', $storeRouteParams) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1">စစ်ဆေးချက် မှတ်ချက်</label>
                        <input type="text" name="review_notes" placeholder="ဥပမာ - လစဉ် စာရင်းစစ် ကွာဟချက် အလိုအလျောက် ညှိနှိုင်းခြင်း..." maxlength="255"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-sky-500/20">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="reconcileModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">ပယ်ဖျက်မည်</button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-sky-600 hover:bg-sky-500 text-white shadow-md transition">
                            အတည်ပြု ညှိနှိုင်းမည်
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
