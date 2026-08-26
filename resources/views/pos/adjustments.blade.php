@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_adjustments') . ' - ' . $store->name)
@section('main_padding', 'p-2')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];
        
        $totalRequests = $requests->count();
        $pendingCount = $requests->where('status', 'pending')->count();
        $approvedCount = $requests->where('status', 'approved')->count();
        $rejectedCount = $requests->where('status', 'rejected')->count();
        $netQuantity = $requests->where('status', 'approved')->sum(fn($r) => (float)$r->total_quantity);
    @endphp

    <div class="w-full space-y-2 sm:space-y-2.5" 
         x-data="{
             formModalOpen: false,
             searchQuery: '',
             statusFilter: 'all',
             viewMode: 'cards',
             rows: [{ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', balance: 0, dir: 'in', quantity: '1', reason: 'စတော့ စစ်ဆေးတွေ့ရှိမှု ကွာဟချက်' }],
             
             async searchProduct(r) {
                 if (r.q.trim() === '') { r.results = []; r.open = false; return; }
                 try {
                     const res = await fetch('{{ url('/store/' . $store->slug . '/pos/products') }}?q=' + encodeURIComponent(r.q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                     const json = await res.json();
                     r.results = (json.results || []).slice(0, 8);
                     r.open = true;
                 } catch(e) { console.error(e); }
             },
             pickProduct(r, p) {
                 r.product_id = p.product_id;
                 r.product_variant_id = p.type === 'variant' ? p.id : null;
                 r.name = p.name;
                 r.sku = p.sku;
                 r.balance = p.balance;
                 r.q = p.name;
                 r.quantity = '1';
                 r.results = []; r.open = false;
             },
             setQuickReason(r, reasonText) {
                 r.reason = reasonText;
             },
             addRow() { 
                 this.rows.push({ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', balance: 0, dir: 'in', quantity: '1', reason: 'စတော့ စစ်ဆေးတွေ့ရှိမှု ကွာဟချက်' }); 
             },
             removeRow(i) { 
                 if (this.rows.length > 1) this.rows.splice(i, 1); 
             },
             signed(r) { 
                 return (r.dir === 'out' ? -1 : 1) * (parseFloat(r.quantity) || 0); 
             },
             get totalQty() { 
                 return this.rows.reduce((s, r) => s + this.signed(r), 0); 
             },
             get valid() { 
                 return this.rows.some(r => r.product_id && (parseFloat(r.quantity) || 0) > 0 && r.reason.trim() !== ''); 
             },
             matchesFilter(itemNumber, submitter, reason, status) {
                 const q = this.searchQuery.toLowerCase().trim();
                 const matchesSearch = q === '' || 
                     itemNumber.toLowerCase().includes(q) ||
                     submitter.toLowerCase().includes(q) ||
                     reason.toLowerCase().includes(q);
                 const matchesStatus = this.statusFilter === 'all' || status === this.statusFilter;
                 return matchesSearch && matchesStatus;
             }
         }">

        {{-- ============================================================
             1. COMPACT HERO PAGE HEADER (Admin UI Standard)
             ============================================================ --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 mb-0.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        <span>⚡</span>
                        <span>{{ __('messages.sidebar_stock_adjustments') }}</span>
                    </span>
                    <span class="text-slate-300 dark:text-slate-700">/</span>
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
                </div>
                <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>{{ __('messages.adjustment_title') }}</span>
                    <span class="text-xs font-mono font-bold text-slate-400">({{ number_format($totalRequests) }})</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ __('messages.adjustment_subtitle') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                {{-- Quick Link to POS --}}
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                    <span>🛒</span>
                    <span>{{ __('messages.back_to_pos') }}</span>
                </a>

                {{-- Primary Action: Create New Adjustment --}}
                <button type="button" @click="formModalOpen = true"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white shadow-md shadow-amber-900/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>+ အတိုး/အလျော့ အသစ်ပြုလုပ်မည်</span>
                </button>
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session('error'))
            <div class="p-3 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span>⚠️</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif
        @if (session('success'))
            <div class="p-3 rounded-lg border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span>✅</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        {{-- ============================================================
             2. KPI SUMMARY METRIC CARDS (4-UP CLICK-TO-FILTER)
             ============================================================ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5" role="list" aria-label="Stock Adjustment Metrics">
            {{-- Total Requests --}}
            <button type="button" @click="statusFilter = 'all'"
                    class="text-left group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 cursor-pointer"
                    :class="statusFilter === 'all' ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700'">
                <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner">
                    <span class="text-base sm:text-lg">📜</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                        {{ number_format($totalRequests) }}
                    </p>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                        စုစုပေါင်း မှတ်တမ်း
                    </p>
                </div>
            </button>

            {{-- Pending Review --}}
            <button type="button" @click="statusFilter = 'pending'"
                    class="text-left group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 cursor-pointer"
                    :class="statusFilter === 'pending' ? 'border-amber-600 bg-amber-50/60 dark:border-amber-500 dark:bg-amber-950/40 ring-2 ring-amber-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700'">
                <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner relative">
                    <span class="text-base sm:text-lg">⏳</span>
                    @if($pendingCount > 0)
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-mono">
                        {{ number_format($pendingCount) }}
                    </p>
                    <p class="text-[10px] sm:text-[11px] text-amber-700 dark:text-amber-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.adjustment_pending') }}
                    </p>
                </div>
            </button>

            {{-- Approved & Posted --}}
            <button type="button" @click="statusFilter = 'approved'"
                    class="text-left group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 cursor-pointer"
                    :class="statusFilter === 'approved' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700'">
                <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner">
                    <span class="text-base sm:text-lg">✅</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-mono">
                        {{ number_format($approvedCount) }}
                    </p>
                    <p class="text-[10px] sm:text-[11px] text-emerald-700 dark:text-emerald-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.adjustment_approved') }}
                    </p>
                </div>
            </button>

            {{-- Net Stock Quantity Movement --}}
            <div class="p-2.5 sm:p-3 rounded-lg border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs flex items-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 shadow-inner">
                    <span class="text-base sm:text-lg">⚡</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-base sm:text-lg font-black font-mono leading-none tabular-nums {{ $netQuantity < 0 ? 'text-rose-600 dark:text-rose-400' : ($netQuantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300') }}">
                        {{ $netQuantity > 0 ? '+' : '' }}{{ number_format($netQuantity, 2) }}
                    </p>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                        Net Stock Movement
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================================
             3. MASTER INTERACTIVE TOOLBAR
             ============================================================ --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            {{-- Search Box --}}
            <div class="relative w-full sm:w-80">
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Search ref #, submitter, reason..."
                       class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-amber-500 shadow-2xs font-semibold">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="11" cy="11" r="8" stroke-width="2"/>
                    <path d="m21 21-4.3-4.3" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                {{-- Status Filter Tabs --}}
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs shrink-0">
                    <button type="button" @click="statusFilter = 'all'"
                            :class="statusFilter === 'all' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                            class="px-2.5 py-1 rounded-md text-xs transition cursor-pointer">
                        အားလုံး ({{ $totalRequests }})
                    </button>
                    <button type="button" @click="statusFilter = 'pending'"
                            :class="statusFilter === 'pending' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                            class="px-2.5 py-1 rounded-md text-xs transition cursor-pointer">
                        စောင့်ဆိုင်းဆဲ ({{ $pendingCount }})
                    </button>
                    <button type="button" @click="statusFilter = 'approved'"
                            :class="statusFilter === 'approved' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                            class="px-2.5 py-1 rounded-md text-xs transition cursor-pointer">
                        အတည်ပြုပြီး ({{ $approvedCount }})
                    </button>
                    <button type="button" @click="statusFilter = 'rejected'"
                            :class="statusFilter === 'rejected' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                            class="px-2.5 py-1 rounded-md text-xs transition cursor-pointer">
                        ပယ်ချပြီး ({{ $rejectedCount }})
                    </button>
                </div>

                {{-- View Toggle (Cards vs Table) --}}
                <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button type="button" @click="viewMode = 'cards'"
                            :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-700 text-amber-600 shadow-2xs' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                            class="p-1 rounded-md transition cursor-pointer" title="Cards View">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </button>
                    <button type="button" @click="viewMode = 'table'"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-amber-600 shadow-2xs' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                            class="p-1 rounded-md transition cursor-pointer" title="Spreadsheet View">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
             4. REQUESTS LIST (CARDS VIEW & TABLE VIEW)
             ============================================================ --}}
        @if ($requests->isEmpty())
            <div class="p-12 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                <span class="text-4xl mb-2 block">⚡</span>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.adjustment_none') }}</p>
                <p class="text-xs text-slate-400 mt-1">စတော့ အတိုး/အလျော့ စာရင်း တင်သွင်းထားခြင်း မရှိသေးပါ</p>
                <button type="button" @click="formModalOpen = true"
                        class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold shadow-md transition cursor-pointer">
                    + ပထမဆုံး စတော့ အတိုး/အလျော့ တင်သွင်းမည်
                </button>
            </div>
        @else
            {{-- Cards View --}}
            <div x-show="viewMode === 'cards'" class="space-y-2.5">
                @foreach ($requests as $req)
                    @php
                        $statusColors = [
                            'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                            'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
                            'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
                        ][$req->status] ?? 'bg-slate-100 text-slate-800';
                        
                        $statusLabel = [
                            'pending' => 'စောင့်ဆိုင်းဆဲ (Pending)', 
                            'approved' => 'အတည်ပြုပြီး (Approved)', 
                            'rejected' => 'ပယ်ချခဲ့သည် (Rejected)'
                        ][$req->status] ?? $req->status;

                        $allReasons = $req->items->pluck('reason')->filter()->unique()->join(', ');
                    @endphp

                    <div x-show="matchesFilter('{{ $req->adjustment_number }}', '{{ $req->submittedBy?->name ?? '' }}', '{{ addslashes($allReasons) }}', '{{ $req->status }}')"
                         class="p-3 sm:p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3 hover:border-slate-300 dark:hover:border-slate-700 transition">
                        
                        {{-- Header Row --}}
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2.5">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono font-black text-sm text-slate-900 dark:text-white">{{ $req->adjustment_number }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColors }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    တင်သွင်းသူ: <strong class="text-slate-700 dark:text-slate-300">{{ $req->submittedBy?->name ?? '—' }}</strong> · 
                                    ရက်စွဲ: <span class="font-mono">{{ $req->created_at->format('d/m/Y H:i') }}</span>
                                    @if ($req->reviewedBy) · စစ်ဆေးသူ: <strong class="text-slate-700 dark:text-slate-300">{{ $req->reviewedBy->name }}</strong> @endif
                                </p>
                            </div>
                            <div class="sm:text-right">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('messages.adjustment_net_change') }}</span>
                                <span class="text-sm sm:text-base font-mono font-black {{ (float) $req->total_quantity < 0 ? 'text-rose-600 dark:text-rose-400' : ((float) $req->total_quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400') }}">
                                    {{ (float) $req->total_quantity > 0 ? '+' : '' }}{{ number_format((float) $req->total_quantity, 3) }} Units
                                </span>
                            </div>
                        </div>

                        {{-- Line items sub-table --}}
                        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-850">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-100 dark:bg-slate-800 text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                                    <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                                        <th class="text-left px-3 py-1.5">ကုန်ပစ္စည်း</th>
                                        <th class="text-right px-3 py-1.5">လက်ရှိ On-Hand</th>
                                        <th class="text-right px-3 py-1.5">ပြင်ဆင်သည့် အရေအတွက်</th>
                                        <th class="text-left px-3 py-1.5">အကြောင်းပြချက်</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                                    @foreach ($req->items as $item)
                                        <tr class="divide-x divide-slate-200/80 dark:divide-slate-800">
                                            <td class="px-3 py-2 font-bold text-slate-900 dark:text-white">
                                                {{ $item->product?->name ?? '—' }}
                                                @if($item->productVariant)
                                                    <span class="text-[10px] text-slate-400 font-normal">({{ $item->productVariant->name }})</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono text-slate-500 dark:text-slate-400">
                                                {{ number_format((float) ($item->on_hand ?? 0), 3) }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono font-black {{ (float) $item->quantity < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                {{ (float) $item->quantity > 0 ? '+' : '' }}{{ number_format((float) $item->quantity, 3) }}
                                            </td>
                                            <td class="px-3 py-2 text-slate-600 dark:text-slate-400">
                                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                                    {{ $item->reason }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($req->notes)
                            <p class="text-[11px] text-slate-500 italic bg-slate-50 dark:bg-slate-800/50 p-2 rounded-md border border-slate-200/80 dark:border-slate-800">
                                <strong>မှတ်ချက်:</strong> {{ $req->notes }}
                            </p>
                        @endif

                        @if ($req->review_notes)
                            <p class="text-[11px] italic text-emerald-800 dark:text-emerald-300 bg-emerald-50/50 dark:bg-emerald-950/20 p-2 rounded-md border border-emerald-200/80 dark:border-emerald-900/40">
                                <strong>စစ်ဆေးချက် မှတ်ချက်:</strong> "{{ $req->review_notes }}"
                            </p>
                        @endif

                        {{-- Manager Review Action Bar --}}
                        @if ($req->isPending() && $isManager)
                            <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/approve') }}" class="flex-1 flex gap-2">
                                    @csrf
                                    <input type="text" name="review_notes" maxlength="500" placeholder="{{ __('messages.adjustment_review_notes') }}"
                                           class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                                    <button type="submit" class="rounded-lg px-3.5 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-2xs flex items-center gap-1 whitespace-nowrap cursor-pointer">
                                        <span>✅</span> {{ __('messages.adjustment_approve') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/reject') }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('ဤစတော့ အတိုး/အလျော့ တောင်းဆိုမှုကို ပယ်ချရန် သေချာပါသလား?')"
                                            class="w-full sm:w-auto rounded-lg px-3.5 py-1.5 text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition flex items-center justify-center gap-1 cursor-pointer">
                                        <span>✕</span> {{ __('messages.adjustment_reject') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Table View --}}
            <div x-show="viewMode === 'table'" class="rounded-lg border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto max-h-[68vh] overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                        <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800 border-b-2 border-slate-300 dark:border-slate-700 shadow-xs select-none backdrop-blur-xs">
                            <tr class="text-[11px] font-black uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">
                                <th class="py-2.5 px-3 min-w-[140px]">Ref No.</th>
                                <th class="py-2.5 px-3 min-w-[180px]">Products & Qty</th>
                                <th class="py-2.5 px-3 text-right min-w-[120px]">Net Change</th>
                                <th class="py-2.5 px-3 min-w-[140px]">Reason Summary</th>
                                <th class="py-2.5 px-3 text-center min-w-[110px]">Status</th>
                                <th class="py-2.5 px-3 min-w-[130px]">Submitted By & Date</th>
                                <th class="py-2.5 px-2 text-right min-w-[150px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            @foreach ($requests as $req)
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                                        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
                                        'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
                                    ][$req->status] ?? 'bg-slate-100 text-slate-800';
                                    
                                    $statusLabel = [
                                        'pending' => 'Pending', 
                                        'approved' => 'Approved', 
                                        'rejected' => 'Rejected'
                                    ][$req->status] ?? $req->status;

                                    $allReasons = $req->items->pluck('reason')->filter()->unique()->join(', ');
                                @endphp
                                <tr x-show="matchesFilter('{{ $req->adjustment_number }}', '{{ $req->submittedBy?->name ?? '' }}', '{{ addslashes($allReasons) }}', '{{ $req->status }}')"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 divide-x divide-slate-200/80 dark:divide-slate-800 transition">
                                    <td class="py-2 px-3 font-mono font-bold text-slate-900 dark:text-slate-100">
                                        {{ $req->adjustment_number }}
                                    </td>
                                    <td class="py-2 px-3">
                                        <div class="font-bold text-slate-900 dark:text-slate-100">
                                            {{ $req->items->first()?->product?->name ?? '—' }}
                                            @if($req->items->count() > 1)
                                                <span class="text-[10px] font-mono text-slate-400">+{{ $req->items->count() - 1 }} more</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 text-right font-mono font-black {{ (float) $req->total_quantity < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ (float) $req->total_quantity > 0 ? '+' : '' }}{{ number_format((float) $req->total_quantity, 3) }}
                                    </td>
                                    <td class="py-2 px-3 text-[11px] text-slate-500 truncate max-w-xs">
                                        {{ $allReasons }}
                                    </td>
                                    <td class="py-2 px-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColors }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 font-mono text-xs text-slate-500">
                                        <div>{{ $req->created_at->format('d/m/Y H:i') }}</div>
                                        <div class="text-[10px] text-slate-400 font-sans">{{ $req->submittedBy?->name ?? '—' }}</div>
                                    </td>
                                    <td class="py-2 px-2 text-right">
                                        @if ($req->isPending() && $isManager)
                                            <div class="flex items-center justify-end gap-1">
                                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/approve') }}">
                                                    @csrf
                                                    <button type="submit" class="px-2 py-1 rounded text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-500 cursor-pointer">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/reject') }}">
                                                    @csrf
                                                    <button type="submit" onclick="return confirm('Reject this adjustment?')" class="px-2 py-1 rounded text-xs font-bold bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-950/60 dark:text-rose-300 cursor-pointer">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-[11px] text-slate-400 font-mono">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ============================================================
             5. MODAL FOR CREATING A NEW STOCK ADJUSTMENT
             ============================================================ --}}
        <div x-show="formModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
             x-transition>
            <div class="relative w-full max-w-3xl rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-5 shadow-2xl space-y-4 my-8 max-h-[92vh] overflow-y-auto"
                 @click.outside="formModalOpen = false">
                
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 font-bold text-sm">⚡</span>
                        <div>
                            <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">စတော့ အတိုး/အလျော့ အသစ် တင်သွင်းရန်</h3>
                            <p class="text-[11px] text-slate-400">မန်နေဂျာ စစ်ဆေးအတည်ပြုရန် တင်သွင်းမည့် စာရင်း</p>
                        </div>
                    </div>
                    <button type="button" @click="formModalOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">✕</button>
                </div>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments') }}" class="space-y-3">
                    @csrf

                    <div class="space-y-2.5">
                        <template x-for="(r, i) in rows" :key="i">
                            <div class="rounded-lg border border-slate-200 dark:border-slate-800 p-3 space-y-2.5 relative bg-slate-50/50 dark:bg-slate-950/40">
                                <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                                        class="absolute top-2.5 right-2.5 p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 cursor-pointer text-xs">✕</button>

                                <div class="relative">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 block">ကုန်ပစ္စည်း အမည် သို့မဟုတ် Barcode / SKU</label>
                                    <input type="text" x-model="r.q" @input.debounce.250ms="searchProduct(r)" @focus="r.open = r.results.length > 0"
                                           placeholder="ရှာဖွေရန် အမည် သို့မဟုတ် Barcode ရိုက်ထည့်ပါ..."
                                           class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-amber-500">
                                    
                                    {{-- Autocomplete Dropdown --}}
                                    <div x-show="r.open" @click.outside="r.open = false" x-transition
                                         class="absolute z-30 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl overflow-hidden max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                        <template x-for="p in r.results" :key="p.id">
                                            <button type="button" @click="pickProduct(r, p)"
                                                    class="w-full text-left px-3 py-2 hover:bg-amber-500/10 flex items-center justify-between gap-3 transition cursor-pointer">
                                                <div>
                                                    <span class="block font-bold text-xs text-slate-900 dark:text-white" x-text="p.name"></span>
                                                    <span class="block text-[10px] font-mono text-slate-400" x-text="p.sku + ' · လက်ကျန်: ' + (p.balance || 0)"></span>
                                                </div>
                                                <span class="text-xs font-bold text-amber-600 font-mono" x-text="'Ks ' + Number(p.price || 0).toLocaleString()"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <input type="hidden" :name="'items[' + i + '][product_id]'" :value="r.product_id" :disabled="!r.product_id">
                                <input type="hidden" :name="'items[' + i + '][product_variant_id]'" :value="r.product_variant_id || ''" :disabled="!r.product_id">

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5" x-show="r.name">
                                    <div class="sm:col-span-4 space-y-1">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 block">အတိုး / အလျော့</label>
                                        <div class="flex rounded-lg bg-slate-200 dark:bg-slate-800 p-0.5">
                                            <button type="button" @click="r.dir = 'in'" class="flex-1 py-1 rounded-md text-xs font-bold transition cursor-pointer" :class="r.dir === 'in' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400'">+ အတိုး</button>
                                            <button type="button" @click="r.dir = 'out'" class="flex-1 py-1 rounded-md text-xs font-bold transition cursor-pointer" :class="r.dir === 'out' ? 'bg-rose-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400'">− အလျော့</button>
                                        </div>
                                        <input type="hidden" :name="'items[' + i + '][quantity]'" :value="signed(r)" :disabled="!r.product_id">
                                    </div>
                                    <div class="sm:col-span-3 space-y-1">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 block">အရေအတွက်</label>
                                        <input type="number" min="0.001" step="any" x-model="r.quantity" :disabled="!r.product_id" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-right font-mono font-bold text-xs">
                                    </div>
                                    <div class="sm:col-span-5 space-y-1">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 block">အကြောင်းပြချက်</label>
                                        <input type="text" :name="'items[' + i + '][reason]'" x-model="r.reason" :disabled="!r.product_id" maxlength="255" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addRow" class="w-full rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-700 py-2 text-xs font-bold text-slate-500 hover:border-amber-500 hover:text-amber-600 transition cursor-pointer">
                        + နောက်ထပ် ကုန်ပစ္စည်း တစ်ကြောင်း ထပ်ထည့်မည်
                    </button>

                    <div class="space-y-1">
                        <label class="text-[11px] font-bold uppercase text-slate-500 block">မှတ်ချက် (Notes)</label>
                        <textarea name="notes" rows="2" maxlength="1000" placeholder="မှတ်ချက် ရိုက်ထည့်ပါ..." class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs text-slate-900 dark:text-slate-100"></textarea>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-between p-3 rounded-lg bg-slate-100 dark:bg-slate-800">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">စုစုပေါင်း ပြင်ဆင်မှု:</span>
                            <span class="text-sm font-mono font-black" :class="totalQty < 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="(totalQty > 0 ? '+' : '') + totalQty + ' Units'"></span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="formModalOpen = false" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 cursor-pointer">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="!valid" :class="valid ? 'bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white cursor-pointer shadow-md' : 'bg-slate-300 dark:bg-slate-700 text-slate-500 cursor-not-allowed'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition">
                                တင်သွင်းမည်
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
