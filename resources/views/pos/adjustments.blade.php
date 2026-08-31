@extends('layouts.admin.app')

@section('title', __('messages.sidebar_stock_adjustments') . ' - ' . $store->name)
@section('main_padding', 'p-2')

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];
        
        $totalRequests = $stats['total'] ?? $requests->total();
        $pendingCount = $stats['pending'] ?? 0;
        $approvedCount = $stats['approved'] ?? 0;
        $rejectedCount = $stats['rejected'] ?? 0;
        $netQuantity = $stats['net_quantity'] ?? 0;
        $activeStatus = $filters['status'] ?? '';
    @endphp

    <div class="w-full space-y-2 sm:space-y-2.5" 
         x-data="{
             formModalOpen: false,
             viewMode: localStorage.getItem('admin_view_mode') || 'table',
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
             }
         }"
         @view-changed.window="viewMode = $event.detail">

        {{-- ============================================================
             1. COMPACT HERO PAGE HEADER (Admin UI Standard)
             ============================================================ --}}
        <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
            <div class="min-w-0">
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
            <a href="{{ route('pos.adjustments.index', array_merge(['store_slug' => $store->slug], request()->except('status', 'page'))) }}"
               class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ empty($activeStatus) ? 'border-amber-600 bg-amber-50/60 dark:border-amber-500 dark:bg-amber-950/40 ring-2 ring-amber-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
                <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner">
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
            </a>

            {{-- Pending Review --}}
            <a href="{{ route('pos.adjustments.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'pending'])) }}"
               class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ $activeStatus === 'pending' ? 'border-amber-600 bg-amber-50/60 dark:border-amber-500 dark:bg-amber-950/40 ring-2 ring-amber-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
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
            </a>

            {{-- Approved & Posted --}}
            <a href="{{ route('pos.adjustments.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'approved'])) }}"
               class="group p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ $activeStatus === 'approved' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700' }}">
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
            </a>

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
             3. MASTER TOOLBAR COMPONENT (<x-admin.toolbar>)
             ============================================================ --}}
        <x-admin.toolbar
            :showSearch="true"
            :searchPlaceholder="__('messages.search') . ' Ref No., submitter, reason, product...'"
            :searchValue="$filters['search'] ?? ''"
            :filterCount="$activeFiltersCount ?? 0"
            :showViewToggle="true"
            :activeView="'table'"
            :showSort="true"
            :sort="$filters['sort'] ?? 'newest'"
            :sortOptions="[
                'newest'   => __('messages.sort_newest') ?? 'Newest',
                'oldest'   => __('messages.sort_oldest') ?? 'Oldest',
                'qty_desc' => 'Highest Qty (+)',
                'qty_asc'  => 'Lowest Qty (−)',
            ]"
            :showPagination="true"
            :paginator="$requests"
            :showPerPageSelector="true"
            :perPageOptions="[
                15    => '15',
                25    => '25',
                50    => '50',
                100   => '100',
                'all' => __('messages.all'),
            ]"
        >
            {{-- Quick Status Filter Tabs inside toolbar --}}
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs shrink-0 overflow-x-auto">
                @foreach([
                    '' => ['label' => 'အားလုံး', 'count' => $totalRequests],
                    'pending' => ['label' => 'စောင့်ဆိုင်းဆဲ', 'count' => $pendingCount],
                    'approved' => ['label' => 'အတည်ပြုပြီး', 'count' => $approvedCount],
                    'rejected' => ['label' => 'ပယ်ချပြီး', 'count' => $rejectedCount],
                ] as $stKey => $stCfg)
                    <a href="{{ route('pos.adjustments.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => $stKey])) }}"
                       class="px-2.5 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ ($activeStatus === $stKey || (empty($activeStatus) && $stKey === '')) ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        <span>{{ $stCfg['label'] }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold {{ ($activeStatus === $stKey || (empty($activeStatus) && $stKey === '')) ? 'bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                            {{ $stCfg['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </x-admin.toolbar>

        {{-- ============================================================
             4. RESPONSIVE CARDS VIEW GRID (CARD VIEW MODE)
             ============================================================ --}}
        <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
            @forelse($requests as $req)
                @php
                    $statusColors = [
                        'pending' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                        'approved' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
                        'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
                    ][$req->status] ?? 'bg-slate-100 text-slate-800 border border-slate-200';

                    $statusLabel = [
                        'pending' => 'စောင့်ဆိုင်းဆဲ',
                        'approved' => 'အတည်ပြုပြီး',
                        'rejected' => 'ပယ်ချခဲ့သည်',
                    ][$req->status] ?? $req->status;

                    $totalQtyVal = (float) $req->total_quantity;
                    $itemCount = $req->items->count();
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-amber-300 dark:hover:border-amber-600/50 hover:shadow-sm transition flex flex-col justify-between group overflow-hidden">
                    {{-- Top Bar & Header --}}
                    <div class="p-3 sm:p-3.5 space-y-2.5">
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
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
                        <div class="p-2.5 rounded-lg border {{ $totalQtyVal < 0 ? 'bg-rose-50/60 dark:bg-rose-950/30 border-rose-100 dark:border-rose-900/50' : ($totalQtyVal > 0 ? 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-100 dark:border-emerald-900/50' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-100 dark:border-slate-800') }} flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                                    {{ __('messages.adjustment_net_change') }}
                                </span>
                                <span class="text-base font-black font-mono leading-tight {{ $totalQtyVal < 0 ? 'text-rose-600 dark:text-rose-400' : ($totalQtyVal > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300') }}">
                                    {{ $totalQtyVal > 0 ? '+' : '' }}{{ number_format($totalQtyVal, 3) }} Units
                                </span>
                            </div>
                            <span class="text-[10px] font-bold font-mono px-2 py-0.5 rounded-md bg-white/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700 shadow-2xs">
                                📦 {{ $itemCount }} {{ $itemCount > 1 ? 'Items' : 'Item' }}
                            </span>
                        </div>

                        {{-- Line items preview --}}
                        <div class="space-y-1.5">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                <span>ကုန်ပစ္စည်းများ</span>
                                <span class="font-mono text-[9px]">{{ $itemCount }} Lines</span>
                            </div>
                            <div class="space-y-1 max-h-32 overflow-y-auto pr-0.5 divide-y divide-slate-100 dark:divide-slate-800/80">
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
                                            {{ (float)$item->quantity > 0 ? '+' : '' }}{{ number_format((float)$item->quantity, 2) }}
                                        </span>
                                    </div>
                                @endforeach
                                @if($itemCount > 3)
                                    <div class="pt-1 text-[10px] text-slate-400 font-bold text-center">
                                        + နောက်ထပ် {{ $itemCount - 3 }} မျိုးကျန်ရှိသည်
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Submitter & Reviewer Meta --}}
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 text-[11px] text-slate-500 dark:text-slate-400 space-y-0.5">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">တင်သွင်းသူ:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300 truncate">{{ $req->submittedBy?->name ?? '—' }}</span>
                            </div>
                            @if($req->reviewedBy)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">စစ်ဆေးသူ:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300 truncate">{{ $req->reviewedBy->name }}</span>
                                </div>
                            @endif
                        </div>

                        @if($req->notes)
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-1.5 rounded border border-slate-200/60 dark:border-slate-800 line-clamp-2">
                                <strong>မှတ်ချက်:</strong> {{ $req->notes }}
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer Action Area --}}
                    <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800">
                        @if ($req->isPending() && $isManager)
                            <div class="flex items-center gap-1.5">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/approve') }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full py-1.5 px-2 rounded-lg text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs flex items-center justify-center gap-1 transition cursor-pointer active:scale-95">
                                        <span>✅</span>
                                        <span>အတည်ပြုမည်</span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/adjustments/' . $req->id . '/reject') }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('ဤစတော့ အတိုး/အလျော့ တောင်းဆိုမှုကို ပယ်ချရန် သေချာပါသလား?')"
                                            class="py-1.5 px-2.5 rounded-lg text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition cursor-pointer active:scale-95" title="Reject">
                                        <span>✕ ပယ်ချမည်</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span class="text-[11px] font-mono">
                                    @if($req->isApproved())
                                        အတည်ပြုပြီး: {{ $req->reviewed_at?->format('d/m/Y') ?? '—' }}
                                    @elseif($req->isRejected())
                                        ပယ်ချပြီး: {{ $req->reviewed_at?->format('d/m/Y') ?? '—' }}
                                    @else
                                        စောင့်ဆိုင်းဆဲ
                                    @endif
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">#{{ $req->id }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full p-12 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
                    <span class="text-4xl mb-2 block">🔍</span>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">ရှာဖွေမှု သို့မဟုတ် Filter နှင့် ကိုက်ညီသည့် မှတ်တမ်း မရှိပါ</p>
                    <p class="text-xs text-slate-400 mt-1">Status Filter သို့မဟုတ် Search စာသားကို ပြန်လည်စစ်ဆေးပါ</p>
                    <a href="{{ route('pos.adjustments.index', ['store_slug' => $store->slug]) }}"
                       class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition">
                        Filter အားလုံး ပြန်လည်ရှင်းလင်းမည် (Reset)
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
                        @forelse ($requests as $req)
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
                                ] [$req->status] ?? $req->status;

                                $allReasons = $req->items->pluck('reason')->filter()->unique()->join(', ');
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 divide-x divide-slate-200/80 dark:divide-slate-800 transition">
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
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    <span class="text-2xl mb-1 block">🔍</span>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">ရှာဖွေမှု သို့မဟုတ် Filter နှင့် ကိုက်ညီသည့် မှတ်တမ်း မရှိပါ</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Status Filter သို့မဟုတ် Search စာသားကို ပြန်လည်စစ်ဆေးပါ</p>
                                    <a href="{{ route('pos.adjustments.index', ['store_slug' => $store->slug]) }}"
                                       class="mt-2 inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition">
                                        Filter အားလုံး ပြန်လည်ရှင်းလင်းမည် (Reset)
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============================================================
             6. MODAL FOR CREATING A NEW STOCK ADJUSTMENT
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
