@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">Order Detail ({{ $store->name }})</h1>
        <a href="{{ url('/store/' . $store->slug . '/admin/orders') }}" class="text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline">&larr; Back to Orders</a>
    </div>

    @if (session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-md text-sm text-green-700 dark:text-green-300">{{ session('success') }}</div>
    @endif

    {{-- Order Header --}}
    <div class="bg-white dark:bg-slate-800 p-6 rounded-lg space-y-4 transition-colors duration-200">
        <div class="flex items-center justify-between border-b dark:border-slate-700 pb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 font-outfit">#{{ $order->order_number }}</h2>
                <p class="text-xs text-gray-400 dark:text-slate-500 font-mono">{{ $order->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 text-xs font-bold rounded uppercase
                    {{ $order->status === 'confirmed' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : '' }}
                    {{ $order->status === 'pending_contact' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' : '' }}
                    {{ $order->status === 'delivered' ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' : '' }}
                    {{ $order->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : '' }}">
                    {{ $order->status === 'pending_contact' ? 'Pending Contact' : ($order->status === 'delivered' ? 'Delivered' : $order->status) }}
                </span>
                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id . '/status') }}" class="inline-flex items-center space-x-2">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="text-xs border rounded px-2 py-1 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-slate-100">
                        <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>Pending Contact</option>
                        <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirm Order</option>
                        <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancel Order</option>
                    </select>
                    <button type="submit" class="px-3 py-1 rounded bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">Update</button>
                </form>
                <a href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id . '/invoice') }}" target="_blank"
                    class="px-3 py-1 rounded bg-sky-600 text-white text-xs font-semibold hover:bg-sky-700 whitespace-nowrap">
                    🧾 Invoice
                </a>
            </div>
        </div>

        {{-- Customer Info --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div class="space-y-2">
                <div>
                    <span class="font-semibold text-gray-700 dark:text-slate-300">Customer:</span>
                    <span class="text-gray-900 dark:text-slate-100 ml-1">{{ $order->customer_name }}</span>
                </div>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-slate-300">Phone:</span>
                    <span class="text-gray-900 dark:text-slate-100 ml-1">{{ $order->customer_phone }}</span>
                </div>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-slate-300">Contact Channel:</span>
                    <span class="uppercase text-xs font-semibold px-2 py-0.5 rounded bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 ml-1">{{ $order->contact_channel }}</span>
                </div>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-slate-300">Contact:</span>
                    <span class="text-gray-900 dark:text-slate-100 ml-1">{{ $order->contact_identifier ?: $order->customer_phone }}</span>
                </div>
            </div>
            <div class="space-y-2">
                <div>
                    <span class="font-semibold text-gray-700 dark:text-slate-300">Address:</span>
                    <p class="text-gray-600 dark:text-slate-400 mt-0.5">{{ $order->customer_address ?? 'Not specified' }}</p>
                </div>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-slate-300">Pricing Type:</span>
                    <span class="text-gray-900 dark:text-slate-100 ml-1 capitalize">{{ $order->pricing_type }}</span>
                </div>
            </div>
        </div>

        @if ($order->customer_note)
            <div class="border-t dark:border-slate-700 pt-3 text-sm">
                <span class="font-semibold text-gray-700 dark:text-slate-300">Customer Note:</span>
                <p class="text-gray-600 dark:text-slate-400 mt-1">{{ $order->customer_note }}</p>
            </div>
        @endif

        @if ($order->user)
            <div class="border-t dark:border-slate-700 pt-3 text-sm">
                <span class="font-semibold text-gray-700 dark:text-slate-300">Account User:</span>
                <span class="text-gray-900 dark:text-slate-100 ml-1">{{ $order->user->name }} ({{ $order->user->phone }})</span>
            </div>
        @endif
    </div>

    {{-- Order Items --}}
    <div class="bg-white dark:bg-slate-800 rounded-lg overflow-hidden transition-colors duration-200">
        <table class="w-full text-left text-sm text-gray-600 dark:text-slate-300">
            <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                <tr>
                    <th class="p-3">Product</th>
                    <th class="p-3 text-right">Unit Price</th>
                    <th class="p-3 text-right">Qty</th>
                    <th class="p-3 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-slate-700">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="p-3">
                            <div class="font-medium text-gray-900 dark:text-slate-100">{{ $item->product_name }}</div>
                            @if ($item->variant_name && !str_contains((string) $item->product_name, (string) $item->variant_name))
                                <div class="text-xs text-gray-500 dark:text-slate-400">{{ $item->variant_name }}@if ($item->variant_sku) · {{ $item->variant_sku }}@endif</div>
                            @elseif ($item->variant_sku)
                                <div class="text-xs font-mono text-gray-400 dark:text-slate-500">{{ $item->variant_sku }}</div>
                            @endif
                        </td>
                        <td class="p-3 text-right">Ks {{ number_format($item->unit_price) }}</td>
                        <td class="p-3 text-right">{{ $item->quantity }}</td>
                        <td class="p-3 text-right font-semibold text-gray-900 dark:text-slate-100">Ks {{ number_format($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-slate-900/50 border-t dark:border-slate-700 font-bold text-gray-900 dark:text-slate-100">
                <tr>
                    <td colspan="3" class="p-3 text-right">Total:</td>
                    <td class="p-3 text-right font-outfit text-lg text-violet-600 dark:text-violet-400">Ks {{ number_format($order->total_amount) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Payment & Price (final agreed amount set by admin after phone/Viber/Telegram) --}}
    <div class="bg-white dark:bg-slate-800 p-6 rounded-lg space-y-4 transition-colors duration-200">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-slate-100 font-outfit">💵 Payment &amp; Price</h3>
            <span class="px-3 py-1 text-xs font-bold rounded uppercase {{ $order->payment_status === 'paid' ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400' }}">
                {{ $order->payment_status }}
            </span>
        </div>

        <div class="text-sm text-gray-600 dark:text-slate-400">
            Glass Finder ပစ္စည်းတွေက ဈေးမပါတဲ့အတွက် ဖုန်း/Viber/Telegram နဲ့ ဈေးညှိပြီးမှ နောက်ဆုံးသဘောတူဈေး ဒီမှာ ထည့်ပါ — Revenue စာရင်းက အဲဒီဈေးအတိုင်း တွက်ပေးပါမယ်။
        </div>

        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id . '/finances') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Agreed Amount (Ks)</label>
                <input type="number" name="agreed_amount" min="0" step="100" value="{{ $order->agreed_amount ?? '' }}"
                    placeholder="e.g. 45000"
                    class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-300 mb-1">Payment Status</label>
                <select name="payment_status" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-slate-100 cursor-pointer">
                    <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition shadow">
                    Save Payment Details
                </button>
            </div>
        </form>

        <div class="text-xs text-gray-500 dark:text-slate-500 pt-2 border-t dark:border-slate-700 flex items-center gap-4">
            <span>Original total: <strong>Ks {{ number_format($order->total_amount) }}</strong></span>
            @if ($order->agreed_amount !== null)
                <span>Agreed (final): <strong class="text-violet-600 dark:text-violet-400">Ks {{ number_format($order->agreed_amount) }}</strong></span>
            @endif
        </div>
    </div>

    {{-- Internal admin note (never shown to the customer) --}}
    <div class="bg-white dark:bg-slate-800 p-6 rounded-lg space-y-4 transition-colors duration-200">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-slate-100 font-outfit">📝 Admin Note</h3>
            <span class="px-3 py-1 text-xs font-bold rounded bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400">Internal only</span>
        </div>

        <div class="text-sm text-gray-600 dark:text-slate-400">
            ဖုန်း / Viber မှာ ဆက်သွယ်ထားတဲ့ အချက်အလက်တွေ (ဥပမာ — ပို့ဆောင်မှု မှတ်ချက်၊ လိုချင်တဲ့အရောင်၊ ဈေးညှိပြီးတဲ့အကြောင်း) ကို မှတ်ထားပါ။ Customer ဖက်ကို ဘယ်တော့မှ မပြပါဘူး — Customer ရဲ့ ကိုယ်ပိုင် note က အထက်မှာရှိတဲ့ Customer Note ပါ။
        </div>

        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id . '/note') }}">
            @csrf
            @method('PATCH')
            <textarea name="admin_note" rows="4" placeholder="e.g. Customer wants the blue frame — confirmed over Viber..."
                class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">{{ $order->admin_note }}</textarea>
            <div class="mt-3 flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-lg bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition shadow">
                    Save Note
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
