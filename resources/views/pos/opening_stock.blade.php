@extends('layouts.admin.app')

@section('title', __('messages.sidebar_opening_stock') . ' - ' . $store->name)
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];
        
        $totalRequests = $requests->count();
        $pendingCount = $requests->where('status', 'pending')->count();
        $approvedCount = $requests->where('status', 'approved')->count();
        $rejectedCount = $requests->where('status', 'rejected')->count();
        $totalValuation = $requests->where('status', 'approved')->sum(fn($r) => (float)$r->total_cost);

        $fmtQty = static fn($qty): string => (string) (
            is_numeric($qty)
                ? (floor((float) $qty) == (float) $qty ? (int) $qty : rtrim(rtrim(number_format((float) $qty, 3, '.', ''), '0'), '.'))
                : $qty
        );
    @endphp

    <div class="w-full space-y-0.5 pb-6" 
         x-data="{
             formModalOpen: false,
             searchQuery: '',
             statusFilter: 'all',
             viewMode: localStorage.getItem('pos_opening_stock_view') || 'table',
             selectedReq: null,
             reviewTarget: null,
             reviewAction: 'approve',
             reviewNotes: '',
             submittingForm: false,
             submittingReview: false,
             exportOpen: false,
             rows: [{ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', balance: 0, quantity: '1', unit_cost: '0' }],
             
             async searchProduct(r) {
                 if (!r.q || r.q.trim() === '') { r.results = []; r.open = false; return; }
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
                 r.balance = p.balance || 0;
                 r.q = p.name;
                 r.quantity = '1';
                 r.unit_cost = p.cost_price ? String(p.cost_price) : (p.purchase_price ? String(p.purchase_price) : '0');
                 r.results = []; 
                 r.open = false;
             },
             addRow() { 
                 this.rows.push({ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', balance: 0, quantity: '1', unit_cost: '0' }); 
             },
             removeRow(i) { 
                 if (this.rows.length > 1) this.rows.splice(i, 1); 
             },
             get totalQty() { 
                 return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0), 0); 
             },
             get totalCost() { 
                 return this.rows.reduce((s, r) => s + (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_cost) || 0), 0); 
             },
             get valid() { 
                 return this.rows.some(r => r.product_id && (parseFloat(r.quantity) || 0) > 0); 
             },
             matchesFilter(requestNumber, submitter, status) {
                 const q = this.searchQuery.trim().toLowerCase();
                 const matchesSearch = !q || 
                     (requestNumber && requestNumber.toLowerCase().includes(q)) ||
                     (submitter && submitter.toLowerCase().includes(q));
                 const matchesStatus = this.statusFilter === 'all' || status === this.statusFilter;
                 return matchesSearch && matchesStatus;
             },
             openReview(req, action) {
                 this.reviewTarget = req;
                 this.reviewAction = action;
                 this.reviewNotes = '';
                 this.submittingReview = false;
             },
             closeReview() {
                 this.reviewTarget = null;
             }
         }"
         @keydown.escape.window="if (formModalOpen) formModalOpen = false; else if (selectedReq) selectedReq = null; else if (reviewTarget) closeReview();"
         @view-changed.window="viewMode = $event.detail; localStorage.setItem('pos_opening_stock_view', $event.detail)">

        {{-- 1. Top Header Banner (Ultra-Dense 36px) --}}
        <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-7 h-7 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 grid place-items-center text-sm font-black shrink-0">
                    📦
                </span>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                            {{ __('messages.sidebar_opening_stock') }}
                        </h1>
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            {{ $store->name }}
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 font-mono truncate hidden sm:block">
                        {{ __('messages.opening_stock_subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                <button type="button" @click="formModalOpen = true"
                        class="h-7 px-2.5 rounded text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs hover:shadow-emerald-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ {{ __('messages.opening_stock_add_new') }}</span>
                </button>
                <button type="button" onclick="window.print()"
                        class="h-7 px-2 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1"
                        title="{{ __('messages.print') ?? 'Print' }}">
                    <span>🖨️</span>
                    <span class="hidden sm:inline">{{ __('messages.print') ?? 'Print' }}</span>
                </button>
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="h-7 px-2.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs">
                    <span>🛒</span>
                    <span>{{ __('messages.back_to_pos') }}</span>
                </a>
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session('error'))
            <div class="p-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded text-xs text-rose-700 dark:text-rose-300 flex items-start gap-1.5">
                <span class="text-sm font-bold shrink-0">⚠️</span>
                <div class="font-medium">{{ session('error') }}</div>
            </div>
        @endif
        @if (session('success'))
            <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded text-xs text-emerald-700 dark:text-emerald-300 flex items-start gap-1.5">
                <span class="text-sm font-bold shrink-0">✓</span>
                <div class="font-medium">{{ session('success') }}</div>
            </div>
        @endif

        {{-- 2. Compact Centered Stat Cards (4 Columns) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
            {{-- Card 1: Total Requests --}}
            <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
                <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                    📋
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                        {{ __('messages.opening_stock_requests') }}
                    </p>
                    <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5">
                        {{ number_format($totalRequests) }}
                    </div>
                </div>
            </div>

            {{-- Card 2: Pending Review --}}
            <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-amber-200/90 dark:border-amber-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
                <div class="w-8 h-8 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-black shrink-0 relative">
                    ⏳
                    @if ($pendingCount > 0)
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 leading-none">
                        {{ __('messages.opening_stock_pending') }}
                    </p>
                    <div class="text-xs sm:text-sm font-black font-mono text-amber-600 dark:text-amber-400 tabular-nums mt-0.5">
                        {{ number_format($pendingCount) }}
                    </div>
                </div>
            </div>

            {{-- Card 3: Approved --}}
            <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-emerald-200/90 dark:border-emerald-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
                <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-black shrink-0">
                    ✓
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 leading-none">
                        {{ __('messages.opening_stock_approved') }}
                    </p>
                    <div class="text-xs sm:text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5">
                        {{ number_format($approvedCount) }}
                    </div>
                </div>
            </div>

            {{-- Card 4: Total Approved Valuation --}}
            <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
                <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-black shrink-0">
                    💰
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                        {{ __('messages.valuation_total') ?? 'စုစုပေါင်း တန်ဖိုး' }}
                    </p>
                    <div class="text-xs sm:text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5 truncate">
                        Ks {{ number_format($totalValuation, 0) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Interactive Toolbar --}}
        <div class="p-1 sm:p-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 select-none">
            <div class="flex flex-wrap items-center gap-1 flex-1">
                {{-- Search Box (h-7) --}}
                <div class="relative w-full sm:w-56">
                    <input type="text" x-model="searchQuery" placeholder="Search request # or submitter..."
                           class="w-full pl-7 pr-2.5 h-7 border border-slate-200 dark:border-slate-700 rounded text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none focus:ring-1 focus:ring-emerald-500/50" />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-1.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                {{-- Status Filter Chips --}}
                <div class="inline-flex rounded border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-0.5 text-xs font-bold">
                    <button type="button" @click="statusFilter = 'all'"
                            :class="statusFilter === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-2 py-0.5 rounded transition text-[11px]">
                        အားလုံး ({{ $totalRequests }})
                    </button>
                    <button type="button" @click="statusFilter = 'pending'"
                            :class="statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-2xs font-black' : 'text-amber-600 dark:text-amber-400 hover:text-amber-700'"
                            class="px-2 py-0.5 rounded transition text-[11px] flex items-center gap-1">
                        <span>⏳ စောင့်ဆိုင်းဆဲ ({{ $pendingCount }})</span>
                    </button>
                    <button type="button" @click="statusFilter = 'approved'"
                            :class="statusFilter === 'approved' ? 'bg-emerald-600 text-white shadow-2xs font-black' : 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-700'"
                            class="px-2 py-0.5 rounded transition text-[11px]">
                        ✓ အတည်ပြုပြီး ({{ $approvedCount }})
                    </button>
                    <button type="button" @click="statusFilter = 'rejected'"
                            :class="statusFilter === 'rejected' ? 'bg-rose-600 text-white shadow-2xs font-black' : 'text-rose-600 dark:text-rose-400 hover:text-rose-700'"
                            class="px-2 py-0.5 rounded transition text-[11px]">
                        ✕ ပယ်ချခဲ့သည် ({{ $rejectedCount }})
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                {{-- Excel / CSV Export Dropdown --}}
                <div class="relative" x-data="{ exportOpen: false }">
                    <button type="button" @click="exportOpen = !exportOpen"
                            class="h-7 px-2 rounded text-xs font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition inline-flex items-center gap-1 cursor-pointer">
                        <span>📥</span>
                        <span>Export</span>
                        <svg class="w-3 h-3 text-emerald-600 transition-transform" :class="exportOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>

                    <div x-show="exportOpen" @click.outside="exportOpen = false" x-transition
                         class="absolute right-0 mt-1 w-44 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl z-30 py-1 divide-y divide-slate-100 dark:divide-slate-800">
                        <a href="{{ $exportUrl }}?format=xlsx" @click="exportOpen = false"
                           class="w-full px-3 py-1.5 text-left text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 flex items-center gap-2">
                            <span>📊</span>
                            <span>Excel (.xlsx)</span>
                        </a>
                        <a href="{{ $exportUrl }}?format=csv" @click="exportOpen = false"
                           class="w-full px-3 py-1.5 text-left text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 flex items-center gap-2">
                            <span>📄</span>
                            <span>CSV (.csv)</span>
                        </a>
                    </div>
                </div>

                {{-- View Toggle (Table / Card) --}}
                <div class="inline-flex rounded border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-0.5 shrink-0" role="group">
                    <button type="button"
                        @click="viewMode = 'table'; localStorage.setItem('pos_opening_stock_view', 'table'); $dispatch('view-changed', 'table')"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="h-6 px-1.5 text-xs rounded transition flex items-center gap-1 cursor-pointer"
                        title="Table View">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M3 6h18v12H3z"/></svg>
                        <span class="hidden sm:inline text-[11px]">Table</span>
                    </button>
                    <button type="button"
                        @click="viewMode = 'card'; localStorage.setItem('pos_opening_stock_view', 'card'); $dispatch('view-changed', 'card')"
                        :class="(viewMode === 'card' || viewMode === 'cards') ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        class="h-6 px-1.5 text-xs rounded transition flex items-center gap-1 cursor-pointer"
                        title="Cards View">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                        <span class="hidden sm:inline text-[11px]">Cards</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- 4. Google Sheets Style Spreadsheet Table View --}}
        <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded shadow-2xs overflow-hidden transition">
            <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-1.5 px-2.5 min-w-[170px]">Request Number</th>
                            <th class="py-1.5 px-2.5 min-w-[150px]">{{ __('messages.opening_stock_submitted_by') }}</th>
                            <th class="py-1.5 px-2.5 text-center w-24">Items</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[140px]">Total Valuation</th>
                            <th class="py-1.5 px-2.5 text-center w-32">{{ __('messages.status') }}</th>
                            <th class="py-1.5 px-2.5 text-right w-44">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($requests as $req)
                            @php
                                $statusBadgeClass = [
                                    'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                                    'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                    'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                                ][$req->status] ?? 'bg-slate-100 text-slate-700';

                                $statusLabel = [
                                    'pending' => 'စောင့်ဆိုင်းဆဲ',
                                    'approved' => 'အတည်ပြုပြီး',
                                    'rejected' => 'ပယ်ချခဲ့သည်',
                                ][$req->status] ?? $req->status;
                            @endphp
                            <tr x-show="matchesFilter('{{ $req->request_number }}', '{{ $req->submitter?->name ?? '' }}', '{{ $req->status }}')"
                                class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                
                                {{-- Request Number --}}
                                <td class="py-1.5 px-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 grid place-items-center text-xs font-black shrink-0">
                                            📦
                                        </span>
                                        <div>
                                            <span class="font-mono font-black text-slate-900 dark:text-slate-100 text-xs block">
                                                {{ $req->request_number }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $req->created_at->format('d M Y, H:i') }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Submitter & Reviewer --}}
                                <td class="py-1.5 px-2.5">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 block truncate">
                                        {{ $req->submitter?->name ?? '—' }}
                                    </span>
                                    @if ($req->approver)
                                        <span class="text-[10px] text-slate-400 block">
                                            Reviewed: {{ $req->approver->name }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Items Count --}}
                                <td class="py-1.5 px-2.5 text-center font-mono font-bold">
                                    <button type="button" @click="selectedReq = {{ Js::from($req) }}"
                                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition text-[11px] cursor-pointer" title="View Items">
                                        {{ $req->items->count() }} Items 🔍
                                    </button>
                                </td>

                                {{-- Total Valuation --}}
                                <td class="py-1.5 px-2.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                                    Ks {{ number_format((float) $req->total_cost, 0) }}
                                </td>

                                {{-- Status --}}
                                <td class="py-1.5 px-2.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-black uppercase border {{ $statusBadgeClass }}">
                                        {{ $req->status === 'pending' ? 'စောင့်ဆိုင်းဆဲ' : ($req->status === 'approved' ? 'အတည်ပြုပြီး' : 'ပယ်ချခဲ့သည်') }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="py-1.5 px-2.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="selectedReq = {{ Js::from($req) }}"
                                                class="px-2 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition cursor-pointer">
                                            🔍 {{ __('messages.view_details') ?? 'အသေးစိတ်' }}
                                        </button>
                                        @if ($req->status === 'pending' && $isManager)
                                            <button type="button" @click="openReview({{ Js::from($req) }}, 'approve')"
                                                    class="px-2 py-1 rounded text-xs font-bold bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 transition cursor-pointer" title="Approve">
                                                ✓
                                            </button>
                                            <button type="button" @click="openReview({{ Js::from($req) }}, 'reject')"
                                                    class="px-2 py-1 rounded text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 transition cursor-pointer" title="Reject">
                                                ✕
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <div class="text-3xl mb-2 opacity-55">📦</div>
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.opening_stock_none') }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">အဖွင့်စတော့ စာရင်း တင်သွင်းထားခြင်း မရှိသေးပါ</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 5. Responsive Multi-Column Card Grid View --}}
        <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-1.5 sm:gap-2">
            @forelse ($requests as $req)
                @php
                    $statusBadgeClass = [
                        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                        'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                    ][$req->status] ?? 'bg-slate-100 text-slate-700';
                @endphp
                <div x-show="matchesFilter('{{ $req->request_number }}', '{{ $req->submitter?->name ?? '' }}', '{{ $req->status }}')"
                     class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg overflow-hidden shadow-2xs hover:border-emerald-300 dark:hover:border-emerald-600/50 hover:shadow-sm transition flex flex-col justify-between group">
                    
                    <div class="p-2.5 space-y-2">
                        {{-- Card Header: Icon + Request # + Status Pill --}}
                        <div class="flex items-center justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-5 h-5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 grid place-items-center text-xs font-black shrink-0">
                                    📦
                                </span>
                                <span class="font-mono font-black text-xs text-slate-900 dark:text-slate-100 truncate">
                                    {{ $req->request_number }}
                                </span>
                            </div>

                            <span class="px-2 py-0.2 rounded-full text-[10px] font-black uppercase border shrink-0 {{ $statusBadgeClass }}">
                                {{ $req->status === 'pending' ? 'စောင့်ဆိုင်းဆဲ' : ($req->status === 'approved' ? 'အတည်ပြုပြီး' : 'ပယ်ချခဲ့သည်') }}
                            </span>
                        </div>

                        {{-- Submitter & Date --}}
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200 block truncate">
                                {{ $req->submitter?->name ?? '—' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">
                                {{ $req->created_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                        {{-- Valuation Box --}}
                        <div class="bg-slate-50 dark:bg-slate-800/60 p-1.5 rounded border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">Items</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $req->items->count() }} Lines</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">Total Cost</span>
                                <span class="font-mono font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                                    Ks {{ number_format((float) $req->total_cost, 0) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1">
                        <button type="button" @click="selectedReq = {{ Js::from($req) }}"
                                class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                            <span>🔍</span> အသေးစိတ်
                        </button>

                        @if ($req->status === 'pending' && $isManager)
                            <div class="inline-flex items-center gap-1">
                                <button type="button" @click="openReview({{ Js::from($req) }}, 'approve')"
                                        class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-2xs cursor-pointer">
                                    ✓ Approve
                                </button>
                                <button type="button" @click="openReview({{ Js::from($req) }}, 'reject')"
                                        class="px-2 py-1 rounded bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-bold transition cursor-pointer">
                                    ✕
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded text-center text-slate-400 shadow-2xs">
                    <div class="text-3xl mb-2 opacity-55">📦</div>
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.opening_stock_none') }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">အဖွင့်စတော့ စာရင်း တင်သွင်းထားခြင်း မရှိသေးပါ</div>
                </div>
            @endforelse
        </div>

        {{-- 6. View Details Modal Dialog --}}
        <div x-show="selectedReq" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="selectedReq = null"></div>
            <div class="min-h-full flex items-center justify-center p-3">
                <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xl p-4 space-y-3" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <div>
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                <span>📦</span> Request Details: <span class="font-mono text-emerald-600" x-text="selectedReq?.request_number"></span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Submitter: <strong class="text-slate-700 dark:text-slate-300" x-text="selectedReq?.submitter?.name || '—'"></strong>
                            </p>
                        </div>
                        <button type="button" @click="selectedReq = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold cursor-pointer">&times;</button>
                    </div>

                    {{-- Items table inside modal --}}
                    <div class="overflow-x-auto rounded border border-slate-200 dark:border-slate-800 max-h-72 overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/80 text-[11px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider sticky top-0">
                                <tr>
                                    <th class="text-left px-3 py-1.5">Product</th>
                                    <th class="text-right px-3 py-1.5">On-Hand</th>
                                    <th class="text-right px-3 py-1.5">Opening Qty</th>
                                    <th class="text-right px-3 py-1.5">Unit Cost</th>
                                    <th class="text-right px-3 py-1.5">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="item in (selectedReq?.items || [])" :key="item.id">
                                    <tr>
                                        <td class="px-3 py-1.5 font-bold text-slate-900 dark:text-slate-100" x-text="item.product?.name || '—'"></td>
                                        <td class="px-3 py-1.5 text-right font-mono text-slate-500" x-text="Number(item.on_hand || 0).toLocaleString()"></td>
                                        <td class="px-3 py-1.5 text-right font-mono font-black text-emerald-600" x-text="Number(item.quantity || 0).toLocaleString()"></td>
                                        <td class="px-3 py-1.5 text-right font-mono text-slate-700 dark:text-slate-300" x-text="'Ks ' + Number(item.unit_cost || 0).toLocaleString()"></td>
                                        <td class="px-3 py-1.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100" x-text="'Ks ' + Number((item.quantity || 0) * (item.unit_cost || 0)).toLocaleString()"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-xs">
                            <span class="text-slate-400">Total Cost:</span>
                            <span class="font-mono font-black text-emerald-600 ml-1" x-text="'Ks ' + Number(selectedReq?.total_cost || 0).toLocaleString()"></span>
                        </div>
                        <button type="button" @click="selectedReq = null"
                                class="h-7 px-3 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition cursor-pointer">
                            {{ __('messages.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 7. Manager Review Modal Dialog (Approve / Reject) --}}
        <div x-show="reviewTarget" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeReview()"></div>
            <div class="min-h-full flex items-center justify-center p-3">
                <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xl p-4 space-y-3" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span x-text="reviewAction === 'approve' ? '✓ {{ __('messages.opening_stock_approve') }}' : '✕ {{ __('messages.opening_stock_reject') }}'"></span>
                        </h3>
                        <button type="button" @click="closeReview()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold cursor-pointer">&times;</button>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Request <strong class="font-mono text-slate-900 dark:text-slate-100" x-text="reviewTarget?.request_number"></strong> အား 
                        <span x-text="reviewAction === 'approve' ? 'အတည်ပြုရန်' : 'ပယ်ချရန်'"></span> သေချာပါသလား?
                    </p>

                    <form method="POST"
                          :action="reviewAction === 'approve' ? ('/store/{{ $store->slug }}/pos/opening-stock/' + (reviewTarget ? reviewTarget.id : '') + '/approve') : ('/store/{{ $store->slug }}/pos/opening-stock/' + (reviewTarget ? reviewTarget.id : '') + '/reject')"
                          @submit="if (submittingReview) { $event.preventDefault(); } else { submittingReview = true; }"
                          class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                {{ __('messages.opening_stock_review_notes') }}
                            </label>
                            <input type="text" name="review_notes" x-model="reviewNotes" maxlength="500" placeholder="စစ်ဆေးမှု မှတ်ချက် ရေးသွင်းပါ..."
                                   class="w-full rounded border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-emerald-500" />
                        </div>

                        <div class="flex items-center justify-end gap-1.5 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="closeReview()"
                                    class="h-7 px-3 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition cursor-pointer">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="submittingReview"
                                    :class="reviewAction === 'approve' ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-rose-600 hover:bg-rose-500 text-white'"
                                    class="h-7 px-4 rounded text-xs font-black shadow-2xs transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
                                <span x-show="!submittingReview" x-text="reviewAction === 'approve' ? '✓ အတည်ပြုမည်' : '✕ ပယ်ချမည်'"></span>
                                <span x-show="submittingReview">Processing...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 8. Modal for New Opening Stock Entry --}}
        <div x-show="formModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="formModalOpen = false"></div>
            <div class="min-h-full flex items-center justify-center p-3">
                <div class="relative w-full max-w-3xl bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xl p-4 space-y-3 my-6 max-h-[90vh] overflow-y-auto" @click.stop>
                    
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 grid place-items-center text-sm font-black">📦</span>
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100">အဖွင့်စတော့ အသစ် တင်သွင်းရန်</h3>
                        </div>
                        <button type="button" @click="formModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold cursor-pointer">&times;</button>
                    </div>

                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/opening-stock') }}" 
                          @submit="if (submittingForm) { $event.preventDefault(); } else { submittingForm = true; }"
                          class="space-y-3">
                        @csrf

                        <div class="space-y-2">
                            <template x-for="(r, i) in rows" :key="i">
                                <div class="rounded border border-slate-200 dark:border-slate-800 p-2.5 space-y-2 relative bg-slate-50/50 dark:bg-slate-900/70">
                                    <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                                            class="absolute top-2 right-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 p-1 rounded text-xs cursor-pointer">✕</button>

                                    <div class="relative">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 block mb-0.5">ကုန်ပစ္စည်း အမည် သို့မဟုတ် Barcode / SKU</label>
                                        <input type="text" x-model="r.q" @input.debounce.250ms="searchProduct(r)" @focus="r.open = r.results.length > 0"
                                               placeholder="ရှာဖွေရန် အမည် သို့မဟုတ် Barcode ရိုက်ထည့်ပါ..."
                                               class="w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none focus:ring-1 focus:ring-emerald-500">
                                        
                                        {{-- Autocomplete Dropdown --}}
                                        <div x-show="r.open" @click.outside="r.open = false" x-transition
                                             class="absolute z-30 mt-1 w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl overflow-hidden max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                            <template x-for="p in r.results" :key="p.id">
                                                <button type="button" @click="pickProduct(r, p)"
                                                        class="w-full text-left px-3 py-1.5 hover:bg-emerald-500/10 flex items-center justify-between gap-3 transition cursor-pointer">
                                                    <div>
                                                        <span class="block font-bold text-xs text-slate-900 dark:text-white" x-text="p.name"></span>
                                                        <span class="block text-[10px] font-mono text-slate-400" x-text="p.sku + ' · လက်ကျန်: ' + (p.balance || 0)"></span>
                                                    </div>
                                                    <span class="text-xs font-bold text-emerald-600 font-mono" x-text="'Ks ' + Number(p.price || 0).toLocaleString()"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <input type="hidden" :name="'items[' + i + '][product_id]'" :value="r.product_id" :disabled="!r.product_id">
                                    <input type="hidden" :name="'items[' + i + '][product_variant_id]'" :value="r.product_variant_id || ''" :disabled="!r.product_id">

                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2" x-show="r.name">
                                        <div class="sm:col-span-4 space-y-0.5">
                                            <label class="text-[10px] font-bold uppercase text-slate-400 block">ရွေးချယ်ထားသော ပစ္စည်း</label>
                                            <div class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                                                <span x-text="r.name"></span>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-4 space-y-0.5">
                                            <label class="text-[10px] font-bold uppercase text-slate-400 block">အဖွင့် ကောင်ရေ (Qty) *</label>
                                            <input type="number" min="0.001" step="any" :name="'items[' + i + '][quantity]'" x-model="r.quantity" :disabled="!r.product_id"
                                                   class="w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 text-right font-mono font-bold text-xs">
                                        </div>
                                        <div class="sm:col-span-4 space-y-0.5">
                                            <label class="text-[10px] font-bold uppercase text-slate-400 block">အရင်းစျေး (Unit Cost - Ks) *</label>
                                            <input type="number" min="0" step="any" :name="'items[' + i + '][unit_cost]'" x-model="r.unit_cost" :disabled="!r.product_id"
                                                   class="w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 text-right font-mono font-bold text-xs text-emerald-600">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addRow" 
                                class="w-full rounded border-2 border-dashed border-slate-200 dark:border-slate-700 py-1.5 text-xs font-bold text-slate-500 hover:border-emerald-500 hover:text-emerald-600 transition cursor-pointer">
                            + နောက်ထပ် ကုန်ပစ္စည်း တစ်ကြောင်း ထပ်ထည့်မည်
                        </button>

                        <div class="space-y-0.5">
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block">မှတ်ချက် (Notes)</label>
                            <textarea name="notes" rows="2" maxlength="1000" placeholder="မှတ်ချက် ရိုက်ထည့်ပါ..." 
                                      class="w-full rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                        </div>

                        <div class="flex items-center justify-between p-2.5 rounded bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 block">စုစုပေါင်း အရင်းတန်ဖိုး:</span>
                                <span class="text-sm sm:text-base font-mono font-black text-emerald-600 dark:text-emerald-400" x-text="'Ks ' + Number(totalCost).toLocaleString()"></span>
                            </div>
                            <div class="flex gap-1.5">
                                <button type="button" @click="formModalOpen = false" 
                                        class="h-7 px-3 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold cursor-pointer">
                                    {{ __('messages.cancel') }}
                                </button>
                                <button type="submit" :disabled="!valid || submittingForm" 
                                        :class="valid ? 'bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer' : 'bg-slate-300 text-slate-500 cursor-not-allowed'" 
                                        class="h-7 px-4 rounded text-xs font-black shadow-2xs transition active:scale-95">
                                    <span x-show="!submittingForm">တင်သွင်းမည်</span>
                                    <span x-show="submittingForm">Submitting...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
