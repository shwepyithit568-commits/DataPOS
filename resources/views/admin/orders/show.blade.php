@extends('layouts.admin.app')

@section('title', 'Order #' . $order->order_number . ' - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $amount = $order->agreed_amount !== null ? (float) $order->agreed_amount : (float) $order->total_amount;
    $isWholesale = $order->pricing_type === 'wholesale';
    $channel = strtolower($order->contact_channel);
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12">

    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.orders.index', $storeRouteParams) }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition shadow-sm">
                ←
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.orders.index', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        Orders
                    </a>
                    <span>/</span>
                    <span class="text-amber-600 dark:text-amber-400">Order Detail</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span class="font-mono">#{{ $order->order_number }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase
                        {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                        {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                        {{ $order->status === 'delivered' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : '' }}
                        {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}">
                        {{ $order->status === 'pending_contact' ? 'Pending' : $order->status }}
                    </span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $order->created_at->format('M d, Y h:i A') }} · {{ $store->name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('store.admin.orders.invoice', array_merge($storeRouteParams, ['order' => $order->id])) }}" target="_blank"
               class="px-4 py-2.5 rounded-2xl text-xs font-black bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                <span>🧾</span>
                <span>Print Invoice</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>အမှားအယွင်း ရှိနေပါသည်:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-5">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Order Summary + Quick Status Updater --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Customer & Channel Info --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <span>👤</span>
                <span>ဖောက်သည် အချက်အလက် (Customer Info)</span>
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">အမည် (Name)</span>
                    <span class="font-black text-slate-900 dark:text-slate-100">{{ $order->customer_name }}</span>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">ဖုန်းနံပါတ် (Phone)</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100">📞 {{ $order->customer_phone }}</span>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">ဆက်သွယ်ရန် Channel</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        @if ($channel === 'viber') 🟣 Viber
                        @elseif ($channel === 'telegram') 🔵 Telegram
                        @else 🟢 Phone @endif
                    </span>
                </div>

                @if ($order->contact_identifier)
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-bold">Contact ID</span>
                        <span class="font-bold text-violet-600 dark:text-violet-400">{{ $order->contact_identifier }}</span>
                    </div>
                @endif

                <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400 font-bold">Pricing Tier</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100 uppercase">{{ $order->pricing_type }}</span>
                </div>

                <div>
                    <span class="text-slate-400 font-bold block mb-1">ပို့ဆောင်ရန် လိပ်စာ (Address)</span>
                    <p class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs">
                        {{ $order->customer_address ?: 'လိပ်စာ သီးခြား မဖော်ပြထားပါ' }}
                    </p>
                </div>

                @if ($order->customer_note)
                    <div>
                        <span class="text-slate-400 font-bold block mb-1">ဖောက်သည် မှတ်ချက် (Customer Note)</span>
                        <p class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 text-xs">
                            {{ $order->customer_note }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Order Status & Price/Payment Box --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Status Bar Card --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">လက်ရှိ အခြေအနေ (Current Status)</span>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase
                            {{ $order->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                            {{ $order->status === 'pending_contact' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                            {{ $order->status === 'delivered' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : '' }}
                            {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}">
                            {{ $order->status === 'pending_contact' ? 'Pending Contact (ဆက်သွယ်ရန်)' : $order->status }}
                        </span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase {{ $order->payment_status === 'paid' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                            {{ $order->payment_status === 'paid' ? 'Paid (ငွေချေပြီး)' : 'Unpaid (ငွေမချေသေး)' }}
                        </span>
                    </div>
                </div>

                {{-- Status Change Form --}}
                <form method="POST" action="{{ route('store.admin.orders.update_status', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                      class="flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100">
                        <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>Pending Contact</option>
                        <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirm Order (အတည်ပြု)</option>
                        <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered (ပို့ဆောင်ပြီး)</option>
                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancel Order (ပယ်ဖျက်)</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-500/20 transition">
                        Update
                    </button>
                </form>
            </div>

            {{-- Agreed Price & Payment Status Editor --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <span>💵</span>
                        <span>ဈေးနှုန်းညှိနှိုင်းမှုနှင့် ငွေပေးချေမှု (Agreed Price & Payment)</span>
                    </h3>
                </div>

                <form method="POST" action="{{ route('store.admin.orders.update_finances', array_merge($storeRouteParams, ['order' => $order->id])) }}"
                      class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end pt-2">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ညှိနှိုင်းသဘောတူဈေး (Agreed Amount Ks)</label>
                        <input type="number" name="agreed_amount" min="0" step="100" value="{{ $order->agreed_amount ?? '' }}"
                               placeholder="e.g. 45000"
                               class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ငွေချေမှု အခြေအနေ (Payment)</label>
                        <select name="payment_status"
                                class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500">
                            <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>Unpaid (မချေသေး)</option>
                            <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid (ငွေချေပြီး)</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-md shadow-violet-500/20 transition">
                            Save Financials
                        </button>
                    </div>
                </form>

                <div class="text-xs text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span>Original Calculated Total: <strong class="text-slate-700 dark:text-slate-300 font-mono">Ks {{ number_format($order->total_amount) }}</strong></span>
                    @if ($order->agreed_amount !== null)
                        <span>Final Agreed Total: <strong class="text-violet-600 dark:text-violet-400 font-mono">Ks {{ number_format($order->agreed_amount) }}</strong></span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Order Items Table --}}
    <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <span>📦</span>
                <span>မှာယူထားသော ပစ္စည်းများ (Order Items)</span>
            </h3>
            <span class="text-xs font-mono text-slate-400">{{ $order->items->count() }} items</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">ပစ္စည်းအမည် (Product)</th>
                        <th class="py-3.5 px-4 text-right">တစ်ခုဈေး (Unit Price)</th>
                        <th class="py-3.5 px-4 text-center">အရေအတွက် (Qty)</th>
                        <th class="py-3.5 px-4 text-right">စုစုပေါင်း (Subtotal)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($order->items as $item)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-slate-100">
                                <div>{{ $item->product_name }}</div>
                                @if ($item->variant_name && !str_contains((string) $item->product_name, (string) $item->variant_name))
                                    <div class="text-[11px] text-slate-400 font-normal">{{ $item->variant_name }} @if ($item->variant_sku) · <span class="font-mono">{{ $item->variant_sku }}</span> @endif</div>
                                @elseif ($item->variant_sku)
                                    <div class="text-[11px] font-mono text-slate-400">{{ $item->variant_sku }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                {{ number_format($item->unit_price, 0) }} Ks
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ $item->quantity }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-black text-slate-900 dark:text-slate-100">
                                {{ number_format($item->subtotal, 0) }} Ks
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                    <tr>
                        <td colspan="3" class="py-3.5 px-4 text-right font-black text-slate-700 dark:text-slate-300 uppercase">
                            Total Amount:
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-black text-base text-violet-600 dark:text-violet-400">
                            {{ number_format($amount, 0) }} Ks
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Internal Admin Private Note --}}
    <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <span>📝</span>
                <span>ဆိုင်တွင်း မှတ်စု (Internal Admin Note - Private)</span>
            </h3>
            <span class="text-[10px] font-bold text-slate-400 uppercase bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">Internal Only</span>
        </div>

        <p class="text-xs text-slate-400">
            ဖုန်း သို့မဟုတ် Viber မှ မှာယူသူနှင့် ဈေးညှိနှိုင်းမှု၊ ပို့ဆောင်ရေး မှတ်ချက်များကို ရေးမှတ်ထားနိုင်ပါသည် (Customer ဘက်သို့ မပြပါ)။
        </p>

        <form method="POST" action="{{ route('store.admin.orders.update_note', array_merge($storeRouteParams, ['order' => $order->id])) }}" class="space-y-3">
            @csrf
            @method('PATCH')

            <textarea name="admin_note" rows="3"
                      placeholder="e.g. Customer requested morning delivery via Viber..."
                      class="w-full p-3.5 rounded-2xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500">{{ $order->admin_note }}</textarea>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-black bg-slate-800 hover:bg-slate-700 text-white dark:bg-slate-700 dark:hover:bg-slate-600 transition shadow-sm">
                    Save Note (မှတ်စုသိမ်းမည်)
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
