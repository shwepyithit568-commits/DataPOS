@extends('layouts.admin.app')

@section('title', __('messages.sidebar_opening_stock') . ' - ' . $store->name)

@section('content')
    @php
        $isManager = auth()->user()?->hasStoreRole($store->id, 'store_manager') || auth()->user()?->hasStoreRole($store->id, 'store_owner');
        $storeRouteParams = ['store_slug' => $store->slug];
        
        $totalRequests = $requests->count();
        $pendingCount = $requests->where('status', 'pending')->count();
        $approvedCount = $requests->where('status', 'approved')->count();
        $totalValuation = $requests->where('status', 'approved')->sum(fn($r) => (float)$r->total_cost);
    @endphp

    <div class="space-y-6 pb-12" 
         x-data="{
             formModalOpen: false,
             searchQuery: '',
             statusFilter: 'all',
             rows: [{ q: '', results: [], open: false, product_id: '', product_variant_id: '', name: '', sku: '', balance: 0, quantity: '1', unit_cost: '0' }],
             
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
                 r.balance = p.balance || 0;
                 r.q = p.name;
                 r.quantity = '1';
                 r.unit_cost = p.cost_price ? String(p.cost_price) : '0';
                 r.results = []; r.open = false;
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
                 const matchesSearch = this.searchQuery === '' || 
                     requestNumber.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                     submitter.toLowerCase().includes(this.searchQuery.toLowerCase());
                 const matchesStatus = this.statusFilter === 'all' || status === this.statusFilter;
                 return matchesSearch && matchesStatus;
             }
         }">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold text-xl">
                        📦
                    </span>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ __('messages.sidebar_opening_stock') }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ __('messages.opening_stock_subtitle') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" @click="formModalOpen = true"
                        class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs sm:text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/20 transition cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>+ အဖွင့်စတော့ အသစ်သွင်းမည်</span>
                </button>
                <a href="{{ url('/store/' . $store->slug . '/pos') }}"
                   class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition border border-slate-200 dark:border-slate-700">
                    <span>🛒</span> {{ __('messages.back_to_pos') }}
                </a>
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
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">စုစုပေါင်း မှတ်တမ်း</span>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">{{ number_format($totalRequests) }}</p>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Total Opening Requests</span>
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-900/50 p-4 sm:p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">စောင့်ဆိုင်းဆဲ</span>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                </div>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono">{{ number_format($pendingCount) }}</p>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Pending Review</span>
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-900/50 p-4 sm:p-5 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block">အတည်ပြုပြီး</span>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">{{ number_format($approvedCount) }}</p>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Approved & Ingested</span>
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-sm">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">စုစုပေါင်း အဖွင့်စတော့ တန်ဖိုး</span>
                <p class="text-2xl font-black font-mono mt-1 text-emerald-600 dark:text-emerald-400">
                    Ks {{ number_format($totalValuation, 0) }}
                </p>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Total Approved Valuation</span>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 rounded-2xl shadow-sm">
            <div class="relative flex-1 max-w-md">
                <input type="text" x-model="searchQuery"
                       placeholder="တောင်းဆိုမှုနံပါတ် သို့မဟုတ် တင်သွင်းသူ ရှာရန်..."
                       class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3.5 py-2 pl-9 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold text-slate-400 uppercase hidden sm:inline">Status:</span>
                <button type="button" @click="statusFilter = 'all'"
                        :class="statusFilter === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                        class="px-3 py-1.5 rounded-xl text-xs transition font-semibold">
                    အားလုံး ({{ $totalRequests }})
                </button>
                <button type="button" @click="statusFilter = 'pending'"
                        :class="statusFilter === 'pending' ? 'bg-amber-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                        class="px-3 py-1.5 rounded-xl text-xs transition font-semibold">
                    စောင့်ဆိုင်းဆဲ ({{ $pendingCount }})
                </button>
                <button type="button" @click="statusFilter = 'approved'"
                        :class="statusFilter === 'approved' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                        class="px-3 py-1.5 rounded-xl text-xs transition font-semibold">
                    အတည်ပြုပြီး ({{ $approvedCount }})
                </button>
            </div>
        </div>

        {{-- Requests & History Table --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>📜</span> အဖွင့်စတော့ တောင်းဆိုမှု မှတ်တမ်းများ
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        ဆိုင်အဖွင့်စတော့ တင်သွင်းချက်များနှင့် အတည်ပြုပြီး စာရင်းများ
                    </p>
                </div>
            </div>

            @if ($requests->isEmpty())
                <div class="p-12 text-center text-slate-500">
                    <p class="text-3xl mb-2">📦</p>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.opening_stock_none') }}</p>
                    <p class="text-xs text-slate-400 mt-1">အဖွင့်စတော့ စာရင်း တင်သွင်းထားခြင်း မရှိသေးပါ</p>
                    <button type="button" @click="formModalOpen = true"
                            class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-sm">
                        + ပထမဆုံး အဖွင့်စတော့ စာရင်းသွင်းမည်
                    </button>
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($requests as $req)
                        @php
                            $statusColors = [
                                'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300',
                                'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300',
                                'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300',
                            ][$req->status] ?? 'bg-slate-100 text-slate-800';
                            
                            $statusLabel = [
                                'pending' => 'စောင့်ဆိုင်းဆဲ (Pending Review)', 
                                'approved' => 'အတည်ပြုပြီး (Approved)', 
                                'rejected' => 'ပယ်ချခဲ့သည် (Rejected)'
                            ][$req->status] ?? $req->status;
                        @endphp

                        <div x-show="matchesFilter('{{ $req->request_number }}', '{{ $req->submittedBy?->name ?? '' }}', '{{ $req->status }}')"
                             class="p-5 space-y-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2.5 flex-wrap">
                                        <span class="font-mono font-black text-base text-slate-900 dark:text-white">{{ $req->request_number }}</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusColors }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                        တင်သွင်းသူ: <strong class="text-slate-700 dark:text-slate-300">{{ $req->submittedBy?->name ?? '—' }}</strong> · 
                                        ရက်စွဲ: <span class="font-mono">{{ $req->created_at->format('d M Y, H:i') }}</span>
                                        @if ($req->reviewedBy) · စစ်ဆေးသူ: <strong class="text-slate-700 dark:text-slate-300">{{ $req->reviewedBy->name }}</strong> @endif
                                    </p>
                                    @if ($req->review_notes)
                                        <p class="text-xs italic text-slate-600 dark:text-slate-400 mt-1 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700">
                                            စစ်ဆေးချက် မှတ်ချက်: "{{ $req->review_notes }}"
                                        </p>
                                    @endif
                                </div>
                                <div class="sm:text-right">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 block">စုစုပေါင်း အဖွင့်စတော့ တန်ဖိုး</span>
                                    <span class="text-lg font-mono font-black text-emerald-600 dark:text-emerald-400">
                                        Ks {{ number_format((float) $req->total_cost, 2) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Line items table --}}
                            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60">
                                <table class="w-full text-xs">
                                    <thead class="bg-slate-100 dark:bg-slate-800/80 text-[11px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="text-left px-3.5 py-2">ကုန်ပစ္စည်း</th>
                                            <th class="text-right px-3.5 py-2">လက်ရှိ On-Hand</th>
                                            <th class="text-right px-3.5 py-2">အဖွင့် ကောင်ရေ</th>
                                            <th class="text-right px-3.5 py-2">အရင်းစျေး (Unit Cost)</th>
                                            <th class="text-right px-3.5 py-2">စုစုပေါင်း အရင်း</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800">
                                        @foreach ($req->items as $item)
                                            <tr>
                                                <td class="px-3.5 py-2.5 font-semibold text-slate-900 dark:text-white">
                                                    {{ $item->product?->name ?? '—' }}
                                                </td>
                                                <td class="px-3.5 py-2.5 text-right font-mono text-slate-500 dark:text-slate-400">
                                                    {{ number_format((float) ($item->on_hand ?? 0), 3) }}
                                                </td>
                                                <td class="px-3.5 py-2.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                                    {{ number_format((float) $item->quantity, 3) }}
                                                </td>
                                                <td class="px-3.5 py-2.5 text-right font-mono text-slate-600 dark:text-slate-300">
                                                    Ks {{ number_format((float) $item->unit_cost, 2) }}
                                                </td>
                                                <td class="px-3.5 py-2.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                                    Ks {{ number_format((float) ($item->quantity * $item->unit_cost), 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Manager Review Actions --}}
                            @if ($req->isPending() && $isManager)
                                <div class="pt-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/opening-stock/' . $req->id . '/approve') }}" class="flex-1 flex gap-2">
                                        @csrf
                                        <input type="text" name="review_notes" maxlength="500" placeholder="{{ __('messages.opening_stock_review_notes') }}"
                                               class="flex-1 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                        <button type="submit" class="rounded-xl px-4 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-sm flex items-center gap-1 whitespace-nowrap">
                                            <span>✅</span> {{ __('messages.opening_stock_approve') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/opening-stock/' . $req->id . '/reject') }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('ဤအဖွင့်စတော့ တောင်းဆိုမှုကို ပယ်ချရန် သေချာပါသလား?')"
                                                class="w-full sm:w-auto rounded-xl px-3.5 py-1.5 text-xs font-bold bg-rose-100 hover:bg-rose-200 dark:bg-rose-950/80 dark:hover:bg-rose-900 text-rose-700 dark:text-rose-300 transition flex items-center justify-center gap-1">
                                            <span>✕</span> {{ __('messages.opening_stock_reject') }}
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Modal for New Opening Stock Entry --}}
        <div x-show="formModalOpen" style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4"
             x-transition>
            <div class="relative w-full max-w-3xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-2xl space-y-5 my-8 max-h-[90vh] overflow-y-auto"
                 @click.outside="formModalOpen = false">
                
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 font-bold">📦</span>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">အဖွင့်စတော့ အသစ် တင်သွင်းရန်</h3>
                    </div>
                    <button type="button" @click="formModalOpen = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/opening-stock') }}" class="space-y-4">
                    @csrf

                    <div class="space-y-3">
                        <template x-for="(r, i) in rows" :key="i">
                            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3 relative bg-slate-50/50 dark:bg-slate-950/40">
                                <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                                        class="absolute top-3 right-3 p-1 rounded-lg text-slate-400 hover:text-rose-600">✕</button>

                                <div class="relative">
                                    <label class="text-[11px] font-bold uppercase text-slate-500 block mb-1">ကုန်ပစ္စည်း အမည် သို့မဟုတ် Barcode / SKU</label>
                                    <input type="text" x-model="r.q" @input.debounce.250ms="searchProduct(r)" @focus="r.open = r.results.length > 0"
                                           placeholder="ရှာဖွေရန် အမည် သို့မဟုတ် Barcode ရိုက်ထည့်ပါ..."
                                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20">
                                    
                                    <div x-show="r.open" @click.outside="r.open = false" x-transition
                                         class="absolute z-30 mt-1 w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl overflow-hidden max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                        <template x-for="p in r.results" :key="p.id">
                                            <button type="button" @click="pickProduct(r, p)"
                                                    class="w-full text-left px-4 py-2.5 hover:bg-emerald-500/10 flex items-center justify-between gap-3 transition">
                                                <div>
                                                    <span class="block font-bold text-sm text-slate-900 dark:text-white" x-text="p.name"></span>
                                                    <span class="block text-xs font-mono text-slate-400" x-text="p.sku + ' · လက်ကျန်: ' + (p.balance || 0)"></span>
                                                </div>
                                                <span class="text-xs font-bold text-emerald-600 font-mono" x-text="'Ks ' + Number(p.price || 0).toLocaleString()"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <input type="hidden" :name="'items[' + i + '][product_id]'" :value="r.product_id" :disabled="!r.product_id">
                                <input type="hidden" :name="'items[' + i + '][product_variant_id]'" :value="r.product_variant_id || ''" :disabled="!r.product_id">

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3" x-show="r.name">
                                    <div class="sm:col-span-4 space-y-1">
                                        <label class="text-[11px] font-bold uppercase text-slate-500 block">ရွေးချယ်ထားသော ပစ္စည်း</label>
                                        <div class="px-3 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                                            <span x-text="r.name"></span>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-4 space-y-1">
                                        <label class="text-[11px] font-bold uppercase text-slate-500 block">အဖွင့် ကောင်ရေ (Qty)</label>
                                        <input type="number" min="0.001" step="any" :name="'items[' + i + '][quantity]'" x-model="r.quantity" :disabled="!r.product_id" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-right font-mono font-bold text-sm">
                                    </div>
                                    <div class="sm:col-span-4 space-y-1">
                                        <label class="text-[11px] font-bold uppercase text-slate-500 block">အရင်းစျေး (Unit Cost - Ks)</label>
                                        <input type="number" min="0" step="any" :name="'items[' + i + '][unit_cost]'" x-model="r.unit_cost" :disabled="!r.product_id" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-right font-mono font-bold text-sm text-emerald-600">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addRow" class="w-full rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 py-2.5 text-xs font-bold text-slate-500 hover:border-emerald-500 hover:text-emerald-600 transition">
                        + နောက်ထပ် ကုန်ပစ္စည်း တစ်ကြောင်း ထပ်ထည့်မည်
                    </button>

                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase text-slate-500 block">မှတ်ချက် (Notes)</label>
                        <textarea name="notes" rows="2" maxlength="1000" placeholder="မှတ်ချက် ရိုက်ထည့်ပါ..." class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm"></textarea>
                    </div>

                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-800">
                        <div>
                            <span class="text-xs text-slate-500 block">စုစုပေါင်း အရင်းတန်ဖိုး:</span>
                            <span class="text-base font-mono font-black text-emerald-600" x-text="'Ks ' + totalCost.toLocaleString()"></span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="formModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">ပယ်ဖျက်မည်</button>
                            <button type="submit" :disabled="!valid" :class="valid ? 'bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer' : 'bg-slate-300 text-slate-500 cursor-not-allowed'" class="px-5 py-2 rounded-xl text-xs font-bold shadow-md transition">
                                တင်သွင်းမည်
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
