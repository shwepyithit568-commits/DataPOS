@extends('layouts.admin.app')

@section('title', __('messages.sidebar_opening_stock') . ' - ' . $store->name)

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];
        
        $totalRequests = $requests->count();
        $pendingCount = $requests->where('status', 'pending')->count();
        $approvedCount = $requests->where('status', 'approved')->count();
        $rejectedCount = $requests->where('status', 'rejected')->count();
        $totalValuation = $requests->where('status', 'approved')->sum(fn($r) => (float)$r->total_cost);
    @endphp

    <div class="w-full space-y-2 sm:space-y-2.5" 
         x-data="{
             formModalOpen: false,
             searchQuery: '',
             statusFilter: 'all',
             viewMode: localStorage.getItem('admin_view_mode') || 'table',
             selectedReq: null,
             reviewTarget: null,
             reviewAction: 'approve',
             reviewNotes: '',
             submittingForm: false,
             submittingReview: false,
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
                 r.unit_cost = p.cost_price ? String(p.cost_price) : '0';
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
         @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

        {{-- 1. Top Header Banner --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 grid place-items-center text-sm font-black shrink-0">
                    📦
                </span>
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">
                        {{ __('messages.sidebar_opening_stock') }}
                    </h1>
                    <p class="text-[11px] text-slate-400 font-mono truncate">
                        {{ $store->name }} — {{ __('messages.opening_stock_subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap shrink-0">
                <button type="button" @click="formModalOpen = true"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-md shadow-emerald-900/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ {{ __('messages.opening_stock_add_new') }}</span>
                </button>
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                    <span>🛒</span>
                    <span>{{ __('messages.back_to_pos') }}</span>
                </a>
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session('error'))
            <div class="p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                <span class="text-sm font-bold shrink-0">⚠️</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif
        @if (session('success'))
            <div class="p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-700 dark:text-emerald-300 flex items-start gap-2">
                <span class="text-sm font-bold shrink-0">✓</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        {{-- 2. Compact KPI Summary Cards (4 Columns) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
            {{-- Card 1: Total Requests --}}
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ __('messages.opening_stock_requests') }}</span>
                    <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-xs">📋</span>
                </div>
                <div class="mt-1">
                    <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 font-mono">{{ number_format($totalRequests) }}</p>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Total Opening Requests</span>
                </div>
            </div>

            {{-- Card 2: Pending Review --}}
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-amber-200/80 dark:border-amber-900/50 shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">{{ __('messages.opening_stock_pending') }}</span>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                </div>
                <div class="mt-1">
                    <p class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400 font-mono">{{ number_format($pendingCount) }}</p>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Pending Review</span>
                </div>
            </div>

            {{-- Card 3: Approved --}}
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-emerald-200/80 dark:border-emerald-900/50 shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ __('messages.opening_stock_approved') }}</span>
                    <span class="w-6 h-6 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xs">✓</span>
                </div>
                <div class="mt-1">
                    <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($approvedCount) }}</p>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Approved & Ingested</span>
                </div>
            </div>

            {{-- Card 4: Total Approved Valuation --}}
            <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Valuation</span>
                    <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-xs">💰</span>
                </div>
                <div class="mt-1">
                    <p class="text-base sm:text-lg font-black font-mono text-emerald-600 dark:text-emerald-400 truncate">
                        Ks {{ number_format($totalValuation, 0) }}
                    </p>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Approved Valuation</span>
                </div>
            </div>
        </div>

        {{-- 3. Toolbar Area --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                {{-- Search Box --}}
                <div class="relative w-full sm:w-64">
                    <input type="text" x-model="searchQuery" placeholder="Search request # or submitter..."
                           class="w-full pl-8 pr-3 py-1.5 min-h-[36px] border border-slate-200 dark:border-slate-700 rounded-lg text-xs bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none focus:ring-2 focus:ring-emerald-500/40" />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                {{-- Status Filter Chips --}}
                <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-0.5 text-xs font-bold">
                    <button type="button" @click="statusFilter = 'all'"
                            :class="statusFilter === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="px-2.5 py-1 rounded-md transition">
                        အားလုံး ({{ $totalRequests }})
                    </button>
                    <button type="button" @click="statusFilter = 'pending'"
                            :class="statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-2xs font-black' : 'text-amber-600 dark:text-amber-400 hover:text-amber-700'"
                            class="px-2.5 py-1 rounded-md transition flex items-center gap-1">
                        <span>⏳ စောင့်ဆိုင်းဆဲ ({{ $pendingCount }})</span>
                    </button>
                    <button type="button" @click="statusFilter = 'approved'"
                            :class="statusFilter === 'approved' ? 'bg-emerald-600 text-white shadow-2xs font-black' : 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-700'"
                            class="px-2.5 py-1 rounded-md transition">
                        ✓ အတည်ပြုပြီး ({{ $approvedCount }})
                    </button>
                    <button type="button" @click="statusFilter = 'rejected'"
                            :class="statusFilter === 'rejected' ? 'bg-rose-600 text-white shadow-2xs font-black' : 'text-rose-600 dark:text-rose-400 hover:text-rose-700'"
                            class="px-2.5 py-1 rounded-md transition">
                        ✕ ပယ်ချခဲ့သည် ({{ $rejectedCount }})
                    </button>
                </div>
            </div>

            {{-- View Toggle (Table / Card) --}}
            <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-0.5 shrink-0" role="group">
                <button type="button"
                    @click="viewMode = 'table'; localStorage.setItem('admin_view_mode', 'table'); $dispatch('view-changed', 'table')"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-1 text-xs rounded-md transition flex items-center gap-1"
                    title="Table View">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M3 6h18v12H3z"/></svg>
                    <span class="hidden sm:inline">Table</span>
                </button>
                <button type="button"
                    @click="viewMode = 'card'; localStorage.setItem('admin_view_mode', 'card'); $dispatch('view-changed', 'card')"
                    :class="(viewMode === 'card' || viewMode === 'cards') ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-300 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-1 text-xs rounded-md transition flex items-center gap-1"
                    title="Cards View">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    <span class="hidden sm:inline">Cards</span>
                </button>
            </div>
        </div>

        {{-- 4. Google Sheets Style Spreadsheet Table View --}}
        <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
            <div class="overflow-x-auto max-h-[72vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                        <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                            <th class="py-2.5 px-3 min-w-[170px]">Request Number</th>
                            <th class="py-2.5 px-3 min-w-[150px]">{{ __('messages.opening_stock_submitted_by') }}</th>
                            <th class="py-2.5 px-3 text-center w-24">Items</th>
                            <th class="py-2.5 px-3 text-right min-w-[140px]">Total Valuation</th>
                            <th class="py-2.5 px-3 text-center w-32">{{ __('messages.status') }}</th>
                            <th class="py-2.5 px-3 text-right w-44">Actions</th>
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
                                    'rejected' => 'ပယ်ချခဲ့သည်'
                                ][$req->status] ?? $req->status;
                            @endphp
                            <tr x-show="matchesFilter('{{ $req->request_number }}', '{{ $req->submittedBy?->name ?? '' }}', '{{ $req->status }}')"
                                class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                
                                {{-- Request Number --}}
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 grid place-items-center text-xs font-black shrink-0">
                                            📦
                                        </span>
                                        <div>
                                            <span class="font-mono font-black text-slate-900 dark:text-slate-100 text-xs sm:text-sm block">
                                                {{ $req->request_number }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $req->created_at->format('d M Y, H:i') }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Submitter & Reviewer --}}
                                <td class="py-2.5 px-3">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 block truncate">
                                        {{ $req->submittedBy?->name ?? '—' }}
                                    </span>
                                    @if ($req->reviewedBy)
                                        <span class="text-[10px] text-slate-400 block">
                                            Reviewed: {{ $req->reviewedBy->name }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Items Count --}}
                                <td class="py-2.5 px-3 text-center font-mono font-bold">
                                    <button type="button" @click="selectedReq = {{ Js::from($req) }}"
                                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition text-[11px]" title="View Items">
                                        {{ $req->items->count() }} Items 🔍
                                    </button>
                                </td>

                                {{-- Total Valuation --}}
                                <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                    Ks {{ number_format((float) $req->total_cost, 2) }}
                                </td>

                                {{-- Status --}}
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase border {{ $statusBadgeClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="selectedReq = {{ Js::from($req) }}"
                                                class="px-2 py-1 rounded text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                                            🔍 {{ __('messages.view_details') ?? 'အသေးစိတ်' }}
                                        </button>
                                        @if ($req->isPending() && $isManager)
                                            <button type="button" @click="openReview({{ Js::from($req) }}, 'approve')"
                                                    class="px-2 py-1 rounded text-xs font-bold bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 transition" title="Approve">
                                                ✓
                                            </button>
                                            <button type="button" @click="openReview({{ Js::from($req) }}, 'reject')"
                                                    class="px-2 py-1 rounded text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 transition" title="Reject">
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
        <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
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
                        'rejected' => 'ပယ်ချခဲ့သည်'
                    ][$req->status] ?? $req->status;
                @endphp
                <div x-show="matchesFilter('{{ $req->request_number }}', '{{ $req->submittedBy?->name ?? '' }}', '{{ $req->status }}')"
                     class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs hover:border-emerald-300 dark:hover:border-emerald-600/50 hover:shadow-sm transition flex flex-col justify-between group">
                    
                    <div class="p-3 space-y-2.5">
                        {{-- Card Header: Icon + Request # + Status Pill --}}
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-6 h-6 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 grid place-items-center text-xs font-black shrink-0">
                                    📦
                                </span>
                                <span class="font-mono font-black text-xs text-slate-900 dark:text-slate-100 truncate">
                                    {{ $req->request_number }}
                                </span>
                            </div>

                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase border shrink-0 {{ $statusBadgeClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        {{-- Submitter & Date --}}
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200 block truncate">
                                {{ $req->submittedBy?->name ?? '—' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">
                                {{ $req->created_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                        {{-- Valuation Box --}}
                        <div class="bg-slate-50 dark:bg-slate-800/60 p-2 rounded-lg border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">Items</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $req->items->count() }} Lines</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 block uppercase font-bold">Total Cost</span>
                                <span class="font-mono font-black text-emerald-600 dark:text-emerald-400">
                                    Ks {{ number_format((float) $req->total_cost, 0) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1">
                        <button type="button" @click="selectedReq = {{ Js::from($req) }}"
                                class="px-2 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1">
                            <span>🔍</span> အသေးစိတ်
                        </button>

                        @if ($req->isPending() && $isManager)
                            <div class="inline-flex items-center gap-1">
                                <button type="button" @click="openReview({{ Js::from($req) }}, 'approve')"
                                        class="px-2 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-xs">
                                    ✓ Approve
                                </button>
                                <button type="button" @click="openReview({{ Js::from($req) }}, 'reject')"
                                        class="px-2 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-bold transition">
                                    ✕
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-slate-900 border border-dashed border-slate-200 dark:border-slate-800 p-8 rounded-xl text-center text-slate-400 shadow-2xs">
                    <div class="text-3xl mb-2 opacity-55">📦</div>
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.opening_stock_none') }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">အဖွင့်စတော့ စာရင်း တင်သွင်းထားခြင်း မရှိသေးပါ</div>
                </div>
            @endforelse
        </div>

        {{-- 6. View Details Modal Dialog --}}
        <div x-show="selectedReq" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="selectedReq = null"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                <span>📦</span> Request Details: <span class="font-mono text-emerald-600" x-text="selectedReq?.request_number"></span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Submitter: <strong class="text-slate-700 dark:text-slate-300" x-text="selectedReq?.submitted_by?.name || '—'"></strong>
                            </p>
                        </div>
                        <button type="button" @click="selectedReq = null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                    </div>

                    {{-- Items table inside modal --}}
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 max-h-72 overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/80 text-[11px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider sticky top-0">
                                <tr>
                                    <th class="text-left px-3.5 py-2">Product</th>
                                    <th class="text-right px-3.5 py-2">On-Hand</th>
                                    <th class="text-right px-3.5 py-2">Opening Qty</th>
                                    <th class="text-right px-3.5 py-2">Unit Cost</th>
                                    <th class="text-right px-3.5 py-2">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="item in (selectedReq?.items || [])" :key="item.id">
                                    <tr>
                                        <td class="px-3.5 py-2 font-bold text-slate-900 dark:text-slate-100" x-text="item.product?.name || '—'"></td>
                                        <td class="px-3.5 py-2 text-right font-mono text-slate-500" x-text="Number(item.on_hand || 0).toLocaleString()"></td>
                                        <td class="px-3.5 py-2 text-right font-mono font-black text-emerald-600" x-text="Number(item.quantity || 0).toLocaleString()"></td>
                                        <td class="px-3.5 py-2 text-right font-mono text-slate-700 dark:text-slate-300" x-text="'Ks ' + Number(item.unit_cost || 0).toLocaleString()"></td>
                                        <td class="px-3.5 py-2 text-right font-mono font-bold text-slate-900 dark:text-slate-100" x-text="'Ks ' + Number((item.quantity || 0) * (item.unit_cost || 0)).toLocaleString()"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-xs">
                            <span class="text-slate-400">Total Cost:</span>
                            <span class="font-mono font-black text-emerald-600 ml-1" x-text="'Ks ' + Number(selectedReq?.total_cost || 0).toLocaleString()"></span>
                        </div>
                        <button type="button" @click="selectedReq = null"
                                class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                            {{ __('messages.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 7. Manager Review Modal Dialog (Approve / Reject) --}}
        <div x-show="reviewTarget" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="closeReview()"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4" @click.stop>
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <span x-text="reviewAction === 'approve' ? '✓ {{ __('messages.opening_stock_approve') }}' : '✕ {{ __('messages.opening_stock_reject') }}'"></span>
                        </h3>
                        <button type="button" @click="closeReview()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Request <strong class="font-mono text-slate-900 dark:text-slate-100" x-text="reviewTarget?.request_number"></strong> အား 
                        <span x-text="reviewAction === 'approve' ? 'အတည်ပြုရန်' : 'ပယ်ချရန်'"></span> သေချာပါသလား?
                    </p>

                    <form method="POST"
                          :action="reviewAction === 'approve' ? ('/store/{{ $store->slug }}/pos/opening-stock/' + (reviewTarget ? reviewTarget.id : '') + '/approve') : ('/store/{{ $store->slug }}/pos/opening-stock/' + (reviewTarget ? reviewTarget.id : '') + '/reject')"
                          @submit="if (submittingReview) { $event.preventDefault(); } else { submittingReview = true; }"
                          class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">
                                {{ __('messages.opening_stock_review_notes') }}
                            </label>
                            <input type="text" name="review_notes" x-model="reviewNotes" maxlength="500" placeholder="စစ်ဆေးမှု မှတ်ချက် ရေးသွင်းပါ..."
                                   class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-emerald-500" />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="closeReview()"
                                    class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="submittingReview"
                                    :class="reviewAction === 'approve' ? 'bg-emerald-600 hover:bg-emerald-500 text-white' : 'bg-rose-600 hover:bg-rose-500 text-white'"
                                    class="px-5 py-2 rounded-lg text-xs font-black shadow-md transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
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
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 space-y-4 my-8 max-h-[90vh] overflow-y-auto" @click.stop>
                    
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 grid place-items-center text-sm font-black">📦</span>
                            <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100">အဖွင့်စတော့ အသစ် တင်သွင်းရန်</h3>
                        </div>
                        <button type="button" @click="formModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                    </div>

                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/opening-stock') }}" 
                          @submit="if (submittingForm) { $event.preventDefault(); } else { submittingForm = true; }"
                          class="space-y-4">
                        @csrf

                        <div class="space-y-3">
                            <template x-for="(r, i) in rows" :key="i">
                                <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-3 space-y-2.5 relative bg-slate-50/50 dark:bg-slate-850">
                                    <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                                            class="absolute top-2.5 right-2.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 p-1 rounded-lg text-xs">✕</button>

                                    <div class="relative">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 block mb-1">ကုန်ပစ္စည်း အမည် သို့မဟုတ် Barcode / SKU</label>
                                        <input type="text" x-model="r.q" @input.debounce.250ms="searchProduct(r)" @focus="r.open = r.results.length > 0"
                                               placeholder="ရှာဖွေရန် အမည် သို့မဟုတ် Barcode ရိုက်ထည့်ပါ..."
                                               class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none focus:ring-2 focus:ring-emerald-500">
                                        
                                        {{-- Autocomplete Dropdown --}}
                                        <div x-show="r.open" @click.outside="r.open = false" x-transition
                                             class="absolute z-30 mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl overflow-hidden max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                            <template x-for="p in r.results" :key="p.id">
                                                <button type="button" @click="pickProduct(r, p)"
                                                        class="w-full text-left px-3.5 py-2 hover:bg-emerald-500/10 flex items-center justify-between gap-3 transition">
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
                                        <div class="sm:col-span-4 space-y-1">
                                            <label class="text-[10px] font-bold uppercase text-slate-400 block">ရွေးချယ်ထားသော ပစ္စည်း</label>
                                            <div class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                                                <span x-text="r.name"></span>
                                            </div>
                                        </div>
                                        <div class="sm:col-span-4 space-y-1">
                                            <label class="text-[10px] font-bold uppercase text-slate-400 block">အဖွင့် ကောင်ရေ (Qty) *</label>
                                            <input type="number" min="0.001" step="any" :name="'items[' + i + '][quantity]'" x-model="r.quantity" :disabled="!r.product_id"
                                                   class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-right font-mono font-bold text-xs">
                                        </div>
                                        <div class="sm:col-span-4 space-y-1">
                                            <label class="text-[10px] font-bold uppercase text-slate-400 block">အရင်းစျေး (Unit Cost - Ks) *</label>
                                            <input type="number" min="0" step="any" :name="'items[' + i + '][unit_cost]'" x-model="r.unit_cost" :disabled="!r.product_id"
                                                   class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1.5 text-right font-mono font-bold text-xs text-emerald-600">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addRow" 
                                class="w-full rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700 py-2 text-xs font-bold text-slate-500 hover:border-emerald-500 hover:text-emerald-600 transition">
                            + နောက်ထပ် ကုန်ပစ္စည်း တစ်ကြောင်း ထပ်ထည့်မည်
                        </button>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block">မှတ်ချက် (Notes)</label>
                            <textarea name="notes" rows="2" maxlength="1000" placeholder="မှတ်ချက် ရိုက်ထည့်ပါ..." 
                                      class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 block">စုစုပေါင်း အရင်းတန်ဖိုး:</span>
                                <span class="text-base font-mono font-black text-emerald-600 dark:text-emerald-400" x-text="'Ks ' + Number(totalCost).toLocaleString()"></span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="formModalOpen = false" 
                                        class="px-4 py-2 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold">
                                    {{ __('messages.cancel') }}
                                </button>
                                <button type="submit" :disabled="!valid || submittingForm" 
                                        :class="valid ? 'bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer' : 'bg-slate-300 text-slate-500 cursor-not-allowed'" 
                                        class="px-5 py-2 rounded-lg text-xs font-black shadow-md transition active:scale-95">
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
