@extends('layouts.admin.app')

@section('title', __('messages.adjustment_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];

        $totalRequests = $stats['total'] ?? $requests->total();
        $pendingCount = $stats['pending'] ?? 0;
        $approvedCount = $stats['approved'] ?? 0;
        $rejectedCount = $stats['rejected'] ?? 0;
        $netQuantity = (float) ($stats['net_quantity'] ?? 0);
        $activeStatus = $filters['status'] ?? '';
        $search = $filters['search'] ?? '';

        $fmtQty = function($v) {
            $val = (float) $v;
            return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
        };
    @endphp

    <div class="w-full space-y-0.5 pb-6"
         x-data="{
             formModalOpen: false,
             viewMode: localStorage.getItem('pos_adjustments_view') || 'table',
             setView(mode) {
                 this.viewMode = mode;
                 localStorage.setItem('pos_adjustments_view', mode);
             },
             rows: [{ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', balance: 0, dir: 'in', quantity: '1', reason: 'စတော့စစ်ဆေးတွေ့ရှိမှု ကွာဟချက်' }],
             
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
                 r.balance = p.balance ?? 0;
                 r.q = p.name;
                 r.quantity = '1';
                 r.results = []; 
                 r.open = false;
             },
             setQuickReason(r, reasonText) {
                 r.reason = reasonText;
             },
             addRow() { 
                 this.rows.push({ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', balance: 0, dir: 'in', quantity: '1', reason: 'စတော့စစ်ဆေးတွေ့ရှိမှု ကွာဟချက်' }); 
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
                 return this.rows.some(r => r.product_id && (parseFloat(r.quantity) || 0) > 0 && r.reason && r.reason.trim() !== ''); 
             }
         }">

        {{-- ============================================================
             1. COMPACT PAGE HEADER (34px - 38px Standard Height)
             ============================================================ --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                    ⚡
                </span>
                <div class="min-w-0">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                        <span>{{ __('messages.adjustment_title') }}</span>
                        <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
                    </h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                        {{ __('messages.adjustment_subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="h-7 px-2.5 rounded-md bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                    <span>🛒</span>
                    <span>{{ __('messages.back_to_pos') }}</span>
                </a>

                <button type="button"
                        @click="formModalOpen = true"
                        class="h-7 px-3 rounded-md bg-amber-600 hover:bg-amber-500 text-white text-xs font-black shadow-2xs hover:shadow-amber-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
                        <path d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>{{ __('messages.adjustment_new') }}</span>
                </button>
            </div>
        </div>

        {{-- Flash Alerts --}}
        @if (session('error'))
            <div class="px-3 py-1.5 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span>⚠️</span>
                <div class="min-w-0 truncate">{{ session('error') }}</div>
            </div>
        @endif
        @if (session('success'))
            <div class="px-3 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2 shadow-2xs">
                <span>✅</span>
                <div class="min-w-0 truncate">{{ session('success') }}</div>
            </div>
        @endif

        {{-- ============================================================
             2. SUMMARY STAT CARDS (Row-Based Center Alignment)
             ============================================================ --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1" role="list" aria-label="{{ __('messages.adjustment_title') }} Statistics">
            {{-- Card 1: Total Adjustments --}}
            <a href="{{ route('pos.adjustments.index', array_merge(['store_slug' => $store->slug], request()->except('status', 'page'))) }}"
               class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border transition shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 {{ empty($activeStatus) ? 'border-amber-500 dark:border-amber-500 bg-amber-50/30 dark:bg-amber-950/20 ring-1 ring-amber-500/30' : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                    📜
                </div>
                <div class="min-w-0 text-left">
                    <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                        {{ number_format($totalRequests) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.total_adjustments') }}
                    </p>
                </div>
            </a>

            {{-- Card 2: Pending Approval --}}
            <a href="{{ route('pos.adjustments.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'pending'])) }}"
               class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border transition shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 {{ $activeStatus === 'pending' ? 'border-amber-500 dark:border-amber-500 bg-amber-50/40 dark:bg-amber-950/30 ring-1 ring-amber-500/30' : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold relative">
                    ⏳
                    @if($pendingCount > 0)
                        <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    @endif
                </div>
                <div class="min-w-0 text-left">
                    <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-mono">
                        {{ number_format($pendingCount) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-amber-700 dark:text-amber-300/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.adjustment_pending') }}
                    </p>
                </div>
            </a>

            {{-- Card 3: Approved --}}
            <a href="{{ route('pos.adjustments.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'approved'])) }}"
               class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border transition shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 {{ $activeStatus === 'approved' ? 'border-emerald-500 dark:border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/30 ring-1 ring-emerald-500/30' : 'border-slate-200/80 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                    ✅
                </div>
                <div class="min-w-0 text-left">
                    <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-mono">
                        {{ number_format($approvedCount) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-emerald-700 dark:text-emerald-300/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.adjustment_approved') }}
                    </p>
                </div>
            </a>

            {{-- Card 4: Net Stock Movement --}}
            <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                    ⚡
                </div>
                <div class="min-w-0 text-left">
                    <div class="text-sm sm:text-base font-black font-mono leading-none tabular-nums {{ $netQuantity < 0 ? 'text-rose-600 dark:text-rose-400' : ($netQuantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300') }}">
                        {{ $netQuantity > 0 ? '+' : '' }}{{ $fmtQty($netQuantity) }}
                    </div>
                    <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                        {{ __('messages.net_stock_movement') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================================
             3. INTERACTIVE INLINE TOOLBAR (Search, Filters, Export, View Toggle)
             ============================================================ --}}
        <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
            {{-- Left: Search Bar & Filter Pills --}}
            <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0">
                <form method="GET" action="{{ route('pos.adjustments.index', $storeRouteParams) }}" class="relative min-w-[180px] sm:min-w-[260px] flex-1 max-w-sm">
                    @if($activeStatus !== '')
                        <input type="hidden" name="status" value="{{ $activeStatus }}">
                    @endif
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="{{ __('messages.adjustment_search_placeholder') }}"
                           class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </form>

                {{-- Status Filter Tabs --}}
                <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700 overflow-x-auto max-w-full">
                    @foreach([
                        '' => ['label' => __('messages.all'), 'count' => $totalRequests],
                        'pending' => ['label' => __('messages.adjustment_pending'), 'count' => $pendingCount],
                        'approved' => ['label' => __('messages.adjustment_approved'), 'count' => $approvedCount],
                        'rejected' => ['label' => __('messages.adjustment_rejected'), 'count' => $rejectedCount],
                    ] as $stKey => $stCfg)
                        <a href="{{ route('pos.adjustments.index', array_merge($storeRouteParams, request()->except('page'), ['status' => $stKey])) }}"
                           class="px-2 py-0.5 rounded text-[11px] font-bold transition flex items-center gap-1 whitespace-nowrap cursor-pointer {{ ($activeStatus === $stKey || (empty($activeStatus) && $stKey === '')) ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                            <span>{{ $stCfg['label'] }}</span>
                            <span class="text-[10px] font-mono opacity-80">({{ $stCfg['count'] }})</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Right: Export Button & View Switcher --}}
            <div class="flex items-center gap-1 self-end sm:self-auto shrink-0">
                @if(!empty($exportUrl))
                    <a href="{{ $exportUrl }}"
                       title="Export Excel (.xlsx)"
                       class="h-6 px-2 rounded text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                        <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Excel</span>
                    </a>
                @endif

                <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                    <button type="button"
                            @click="setView('table')"
                            class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                        <span>{{ __('messages.view_table') ?? 'Table' }}</span>
                    </button>
                    <button type="button"
                            @click="setView('card')"
                            class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                            :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <span>{{ __('messages.view_cards') ?? 'Cards' }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
             4. RESPONSIVE CARDS VIEW GRID (CARD VIEW MODE / MOBILE OPTIMIZED)
             ============================================================ --}}
        <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
            @forelse($requests as $req)
                @php
                    $statusColors = [
                        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
                        'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
                    ][$req->status] ?? 'bg-slate-100 text-slate-800 border border-slate-200';

                    $statusLabel = [
                        'pending' => __('messages.adjustment_pending'),
                        'approved' => __('messages.adjustment_approved'),
                        'rejected' => __('messages.adjustment_rejected'),
                    ][$req->status] ?? $req->status;

                    $totalQtyVal = (float) $req->total_quantity;
                    $itemCount = $req->items->count();
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-amber-300 dark:hover:border-amber-600/50 transition flex flex-col justify-between overflow-hidden">
                    {{-- Top Bar & Header --}}
                    <div class="p-2.5 sm:p-3 space-y-2">
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                            <div>
                                <span class="font-mono font-black text-xs sm:text-sm text-slate-900 dark:text-white tracking-tight">
                                    {{ $req->adjustment_number }}
                                </span>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    {{ $req->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColors }}">
                                @if($req->isPending())
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                @endif
                                <span>{{ $statusLabel }}</span>
                            </span>
                        </div>

                        {{-- Net Movement Hero Metric Box --}}
                        <div class="p-2 rounded-lg border {{ $totalQtyVal < 0 ? 'bg-rose-50/60 dark:bg-rose-950/30 border-rose-100 dark:border-rose-900/50' : ($totalQtyVal > 0 ? 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-100 dark:border-emerald-900/50' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-100 dark:border-slate-800') }} flex items-center justify-between">
                            <div>
                                <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                                    {{ __('messages.adjustment_net_change') }}
                                </span>
                                <span class="text-sm sm:text-base font-black font-mono leading-tight {{ $totalQtyVal < 0 ? 'text-rose-600 dark:text-rose-400' : ($totalQtyVal > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300') }}">
                                    {{ $totalQtyVal > 0 ? '+' : '' }}{{ $fmtQty($totalQtyVal) }}
                                </span>
                            </div>
                            <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded-md bg-white/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700 shadow-2xs">
                                📦 {{ $itemCount }} {{ __('messages.adjustment_lines') }}
                            </span>
                        </div>

                        {{-- Line items preview --}}
                        <div class="space-y-1">
                            <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                <span>{{ __('messages.products') ?? 'Products' }}</span>
                                <span class="font-mono text-[9px]">{{ $itemCount }} Lines</span>
                            </div>
                            <div class="space-y-1 max-h-28 overflow-y-auto pr-0.5 divide-y divide-slate-100 dark:divide-slate-800/80">
                                @foreach($req->items->take(3) as $item)
                                    <div class="pt-1 first:pt-0 flex items-start justify-between gap-2 text-xs">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-slate-800 dark:text-slate-200 truncate text-[11px]">
                                                {{ $item->product?->name ?? '—' }}
                                            </div>
                                            @if($item->productVariant)
                                                <div class="text-[10px] text-slate-400 font-sans truncate">
                                                    {{ $item->productVariant->name }}
                                                </div>
                                            @endif
                                            @if($item->reason)
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1 italic mt-0.5">
                                                    ↳ {{ $item->reason }}
                                                </div>
                                            @endif
                                        </div>
                                        <span class="font-mono font-bold text-[11px] shrink-0 {{ (float)$item->quantity < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ (float)$item->quantity > 0 ? '+' : '' }}{{ $fmtQty($item->quantity) }}
                                        </span>
                                    </div>
                                @endforeach
                                @if($itemCount > 3)
                                    <div class="pt-1 text-[10px] text-slate-400 font-bold text-center">
                                        + {{ $itemCount - 3 }} more
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Submitter & Reviewer Meta --}}
                        <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800/80 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 space-y-0.5">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">{{ __('messages.adjustment_submitted_by') }}:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300 truncate">{{ $req->submittedBy?->name ?? '—' }}</span>
                            </div>
                            @if($req->reviewedBy)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">{{ __('messages.adjustment_reviewed_by') }}:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300 truncate">{{ $req->reviewedBy->name }}</span>
                                </div>
                            @endif
                        </div>

                        @if($req->notes)
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-1.5 rounded border border-slate-200/60 dark:border-slate-800 line-clamp-2">
                                <strong>{{ __('messages.notes') ?? 'Notes' }}:</strong> {{ $req->notes }}
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer Action Area --}}
                    <div class="p-2 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800">
                        @if ($req->isPending() && $isManager)
                            <div class="flex items-center gap-1.5">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/approve') }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full h-7 px-2 rounded-md text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs flex items-center justify-center gap-1 transition cursor-pointer active:scale-95">
                                        <span>✅</span>
                                        <span>{{ __('messages.adjustment_approve') }}</span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/reject') }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('{{ __('messages.adjustment_reject_confirm') }}')"
                                            class="h-7 px-2.5 rounded-md text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition cursor-pointer active:scale-95" title="Reject">
                                        <span>✕ {{ __('messages.adjustment_reject') }}</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span class="text-[10px] sm:text-[11px] font-mono">
                                    @if($req->isApproved())
                                        {{ __('messages.adjustment_approved') }}: {{ $req->reviewed_at?->format('d/m/Y') ?? '—' }}
                                    @elseif($req->isRejected())
                                        {{ __('messages.adjustment_rejected') }}: {{ $req->reviewed_at?->format('d/m/Y') ?? '—' }}
                                    @else
                                        {{ __('messages.adjustment_pending') }}
                                    @endif
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">#{{ $req->id }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full p-8 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-lg border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                    <span class="text-3xl mb-1.5 block">🔍</span>
                    <p class="text-xs sm:text-sm font-black text-slate-700 dark:text-slate-300">{{ __('messages.adjustment_empty') }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ __('messages.adjustment_empty_hint') }}</p>
                    <a href="{{ route('pos.adjustments.index', $storeRouteParams) }}"
                       class="mt-2.5 inline-flex items-center gap-1.5 h-7 px-3 rounded-md text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition">
                        {{ __('messages.all') }}
                    </a>
                </div>
            @endforelse
        </div>

        {{-- ============================================================
             5. SPREADSHEET DATA GRID TABLE (TABLE VIEW MODE)
             ============================================================ --}}
        <div x-show="viewMode === 'table'" class="rounded-lg border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto max-h-[68vh] overflow-y-auto">
                <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                    <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800 border-b-2 border-slate-300 dark:border-slate-700 shadow-xs select-none backdrop-blur-xs">
                        <tr class="text-[11px] font-black uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100">
                            <th class="py-1.5 px-2.5 min-w-[130px]">Ref No.</th>
                            <th class="py-1.5 px-2.5 min-w-[180px]">{{ __('messages.products') ?? 'Products' }}</th>
                            <th class="py-1.5 px-2.5 text-right min-w-[120px] bg-slate-200/50 dark:bg-slate-700/50 font-black text-slate-900 dark:text-white">
                                {{ __('messages.adjustment_net_change') }}
                            </th>
                            <th class="py-1.5 px-2.5 min-w-[150px]">{{ __('messages.adjustment_reason') }}</th>
                            <th class="py-1.5 px-2.5 text-center min-w-[100px]">{{ __('messages.status') }}</th>
                            <th class="py-1.5 px-2.5 min-w-[130px]">{{ __('messages.adjustment_submitted_by') }} & {{ __('messages.date') ?? 'Date' }}</th>
                            <th class="py-1.5 px-2 text-right min-w-[130px]">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @forelse ($requests as $req)
                            @php
                                $statusColors = [
                                    'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                                    'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
                                    'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
                                ][$req->status] ?? 'bg-slate-100 text-slate-800';
                                
                                $statusLabel = [
                                    'pending' => __('messages.adjustment_pending'), 
                                    'approved' => __('messages.adjustment_approved'), 
                                    'rejected' => __('messages.adjustment_rejected')
                                ][$req->status] ?? $req->status;

                                $totalQtyVal = (float) $req->total_quantity;
                                $allReasons = $req->items->pluck('reason')->filter()->unique()->join(', ');
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 divide-x divide-slate-200/80 dark:divide-slate-800 transition">
                                <td class="py-1.5 px-2.5 font-mono font-black text-slate-900 dark:text-slate-100">
                                    {{ $req->adjustment_number }}
                                </td>
                                <td class="py-1.5 px-2.5">
                                    <div class="font-bold text-slate-900 dark:text-slate-100">
                                        {{ $req->items->first()?->product?->name ?? '—' }}
                                        @if($req->items->count() > 1)
                                            <span class="text-[10px] font-mono text-slate-400">+{{ $req->items->count() - 1 }} more</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-1.5 px-2.5 text-right font-mono font-black text-xs sm:text-sm tabular-nums bg-slate-50/50 dark:bg-slate-800/30 {{ $totalQtyVal < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $totalQtyVal > 0 ? '+' : '' }}{{ $fmtQty($totalQtyVal) }}
                                </td>
                                <td class="py-1.5 px-2.5 text-[11px] text-slate-500 truncate max-w-xs">
                                    {{ $allReasons ?: '—' }}
                                </td>
                                <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColors }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="py-1.5 px-2.5 font-mono text-xs text-slate-500">
                                    <div>{{ $req->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="text-[10px] text-slate-400 font-sans truncate">{{ $req->submittedBy?->name ?? '—' }}</div>
                                </td>
                                <td class="py-1.5 px-2 text-right">
                                    @if ($req->isPending() && $isManager)
                                        <div class="flex items-center justify-end gap-1">
                                            <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/approve') }}">
                                                @csrf
                                                <button type="submit" class="h-6 px-2 rounded text-[11px] font-black bg-emerald-600 text-white hover:bg-emerald-500 transition cursor-pointer shadow-2xs">
                                                    {{ __('messages.adjustment_approve') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/reject') }}">
                                                @csrf
                                                <button type="submit" onclick="return confirm('{{ __('messages.adjustment_reject_confirm') }}')"
                                                        class="h-6 px-2 rounded text-[11px] font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition cursor-pointer">
                                                    {{ __('messages.adjustment_reject') }}
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-slate-400 font-mono">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    <span class="text-2xl mb-1 block">🔍</span>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.adjustment_empty') }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ __('messages.adjustment_empty_hint') }}</p>
                                    <a href="{{ route('pos.adjustments.index', $storeRouteParams) }}"
                                       class="mt-2 inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition">
                                        {{ __('messages.all') }}
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($requests->hasPages())
            <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                {{ $requests->links() }}
            </div>
        @endif

        {{-- ============================================================
             6. RESPONSIVE MODAL FOR CREATING A NEW STOCK ADJUSTMENT
             ============================================================ --}}
        <div x-show="formModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4"
             x-transition>
            <div class="relative w-full max-w-3xl rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3.5 sm:p-4 shadow-2xl space-y-3 my-4 sm:my-8 max-h-[94vh] overflow-y-auto"
                 @click.outside="formModalOpen = false">
                
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 grid place-items-center font-bold text-sm">⚡</span>
                        <div>
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.adjustment_modal_title') }}</h3>
                            <p class="text-[10px] sm:text-[11px] text-slate-400">{{ __('messages.adjustment_modal_subtitle') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="formModalOpen = false" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center text-sm font-bold cursor-pointer">✕</button>
                </div>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments') }}" class="space-y-2.5">
                    @csrf

                    <div class="space-y-2">
                        <template x-for="(r, i) in rows" :key="i">
                            <div class="rounded-lg border border-slate-200 dark:border-slate-800 p-2.5 sm:p-3 space-y-2 relative bg-slate-50/50 dark:bg-slate-950/40">
                                <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                                        class="absolute top-2 right-2 w-6 h-6 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 grid place-items-center cursor-pointer text-xs font-bold">✕</button>

                                {{-- Product Search Bar --}}
                                <div class="relative">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-0.5 block">
                                        {{ __('messages.products') ?? 'Product' }} (Barcode / SKU / Name)
                                    </label>
                                    <input type="text"
                                           x-model="r.q"
                                           @input.debounce.250ms="searchProduct(r)"
                                           @focus="r.open = (r.results && r.results.length > 0)"
                                           placeholder="{{ __('messages.adjustment_product_placeholder') }}"
                                           class="w-full h-7 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 text-xs font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                                    
                                    {{-- Autocomplete Dropdown --}}
                                    <div x-show="r.open" @click.outside="r.open = false" x-transition
                                         class="absolute z-30 mt-1 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl overflow-hidden max-h-52 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                        <template x-for="p in r.results" :key="p.id">
                                            <button type="button" @click="pickProduct(r, p)"
                                                    class="w-full text-left px-3 py-1.5 hover:bg-amber-500/10 flex items-center justify-between gap-3 transition cursor-pointer">
                                                <div class="min-w-0">
                                                    <span class="block font-bold text-xs text-slate-900 dark:text-white truncate" x-text="p.name"></span>
                                                    <span class="block text-[10px] font-mono text-slate-400" x-text="(p.sku ? p.sku + ' · ' : '') + '{{ __('messages.adjustment_on_hand') }}: ' + (p.balance || 0)"></span>
                                                </div>
                                                <span class="text-xs font-bold text-amber-600 font-mono shrink-0" x-text="'Ks ' + Number(p.price || 0).toLocaleString()"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <input type="hidden" :name="'items[' + i + '][product_id]'" :value="r.product_id" :disabled="!r.product_id">
                                <input type="hidden" :name="'items[' + i + '][product_variant_id]'" :value="r.product_variant_id || ''" :disabled="!r.product_id">

                                {{-- Direction, Quantity, Reason --}}
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2" x-show="r.name">
                                    {{-- Direction Toggle --}}
                                    <div class="sm:col-span-3 space-y-0.5">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 block">{{ __('messages.adjustment_direction') }}</label>
                                        <div class="flex rounded-md bg-slate-200 dark:bg-slate-800 p-0.5 h-7 items-center">
                                            <button type="button" @click="r.dir = 'in'" class="flex-1 h-6 rounded text-[11px] font-bold transition cursor-pointer flex items-center justify-center" :class="r.dir === 'in' ? 'bg-emerald-600 text-white shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400'">
                                                + {{ __('messages.adjustment_in') }}
                                            </button>
                                            <button type="button" @click="r.dir = 'out'" class="flex-1 h-6 rounded text-[11px] font-bold transition cursor-pointer flex items-center justify-center" :class="r.dir === 'out' ? 'bg-rose-600 text-white shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400'">
                                                − {{ __('messages.adjustment_out') }}
                                            </button>
                                        </div>
                                        <input type="hidden" :name="'items[' + i + '][quantity]'" :value="signed(r)" :disabled="!r.product_id">
                                    </div>

                                    {{-- Quantity Input --}}
                                    <div class="sm:col-span-3 space-y-0.5">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 block">{{ __('messages.quantity') ?? 'Quantity' }}</label>
                                        <input type="number" min="0.001" step="any" x-model="r.quantity" :disabled="!r.product_id" class="w-full h-7 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 text-right font-mono font-black text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
                                    </div>

                                    {{-- Reason Input & Quick Chips --}}
                                    <div class="sm:col-span-6 space-y-0.5">
                                        <label class="text-[10px] font-bold uppercase text-slate-500 block">{{ __('messages.adjustment_reason') }}</label>
                                        <input type="text" :name="'items[' + i + '][reason]'" x-model="r.reason" :disabled="!r.product_id" maxlength="255" placeholder="{{ __('messages.adjustment_reason_placeholder') }}" class="w-full h-7 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 text-xs font-semibold focus:ring-1 focus:ring-amber-500 focus:outline-none">
                                        
                                        {{-- 1-Tap Quick Reason Chips for Mobile & Fast POS Operation --}}
                                        <div class="flex flex-wrap items-center gap-1 pt-0.5">
                                            @foreach([
                                                __('messages.adjustment_quick_discrepancy'),
                                                __('messages.adjustment_quick_damaged'),
                                                __('messages.adjustment_quick_expired'),
                                                __('messages.adjustment_quick_sample'),
                                                __('messages.adjustment_quick_restock'),
                                            ] as $qReason)
                                                <button type="button"
                                                        @click="setQuickReason(r, '{{ $qReason }}')"
                                                        class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 hover:bg-amber-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-amber-950/60 border border-slate-200 dark:border-slate-700 transition cursor-pointer">
                                                    {{ $qReason }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Add Row Button --}}
                    <button type="button" @click="addRow" class="w-full h-8 rounded-md border-2 border-dashed border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-400 hover:border-amber-500 hover:text-amber-600 transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>{{ __('messages.adjustment_add_row') }}</span>
                    </button>

                    {{-- Notes --}}
                    <div class="space-y-0.5">
                        <label class="text-[10px] font-bold uppercase text-slate-500 block">{{ __('messages.notes') ?? 'Notes' }}</label>
                        <textarea name="notes" rows="2" maxlength="1000" placeholder="{{ __('messages.adjustment_notes_placeholder') }}" class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1 text-xs text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-amber-500 focus:outline-none"></textarea>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-100 dark:bg-slate-800">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-500 block">{{ __('messages.adjustment_net_change') }}:</span>
                            <span class="text-sm font-mono font-black tabular-nums" :class="totalQty < 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="(totalQty > 0 ? '+' : '') + totalQty + ' Units'"></span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="formModalOpen = false" class="h-7 px-3 rounded-md text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300 transition cursor-pointer">
                                {{ __('messages.cancel') }}
                            </button>
                            <button type="submit" :disabled="!valid" :class="valid ? 'bg-amber-600 hover:bg-amber-500 text-white cursor-pointer shadow-2xs' : 'bg-slate-300 dark:bg-slate-700 text-slate-500 cursor-not-allowed'" class="h-7 px-4 rounded-md text-xs font-black transition">
                                {{ __('messages.adjustment_submit') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
