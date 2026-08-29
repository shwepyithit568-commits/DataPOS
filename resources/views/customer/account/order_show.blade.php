@extends('layouts.storefront.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-white/50 dark:border-slate-800/80 shadow-2xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-800/60 pb-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white font-outfit">
                    Order Details (#{{ $order->order_number }})
                </h1>
                <p class="text-xs font-mono text-slate-500 dark:text-slate-400">
                    Date: {{ $order->created_at->format('M d, Y H:i') }}
                </p>
            </div>
            <span class="px-3 py-1 text-xs font-bold rounded-full uppercase
                {{ $order->status === 'confirmed' ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300' : '' }}
                {{ $order->status === 'pending_contact' ? 'bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300' : '' }}
                {{ $order->status === 'cancelled' ? 'bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300' : '' }}">
                {{ $order->status }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 space-y-1">
                <span class="font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px] block">Customer Info</span>
                <p class="font-extrabold text-slate-900 dark:text-white">{{ $order->customer_name }}</p>
                <p class="text-xs font-mono text-slate-600 dark:text-slate-300">Phone: {{ $order->customer_phone }}</p>
                <p class="text-xs text-violet-600 dark:text-violet-400 font-semibold mt-1">Channel: {{ strtoupper($order->contact_channel) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 space-y-1">
                <span class="font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px] block">Address</span>
                <p class="font-semibold text-slate-800 dark:text-slate-200 leading-relaxed">{{ $order->customer_address ?? 'Not specified' }}</p>
            </div>
        </div>

        <div class="pt-2 space-y-3">
            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-outfit">
                Order Items
            </h3>
            <div class="space-y-2">
                @foreach ($order->items as $item)
                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between text-xs sm:text-sm">
                        <div>
                            <div class="font-extrabold text-slate-900 dark:text-white">{{ $item->product_name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">Ks {{ number_format($item->unit_price) }} x {{ $item->quantity }}</div>
                        </div>
                        <div class="font-black text-violet-600 dark:text-violet-400">
                            Ks {{ number_format($item->subtotal) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200/60 dark:border-slate-800/60">
            <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ __('messages.total_amount') ?? 'စုစုပေါင်း ကျသင့်ငွေ' }}:</span>
            <span class="text-2xl font-black text-violet-600 dark:text-violet-400 font-outfit">Ks {{ number_format($order->total_amount) }}</span>
        </div>

        <div class="pt-2">
            <a href="{{ url('/account/orders') }}" class="text-xs text-violet-600 dark:text-violet-400 font-bold hover:underline">
                &larr; {{ __('messages.back_to_orders') }}
            </a>
        </div>
    </div>
</div>
@endsection
