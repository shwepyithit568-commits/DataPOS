@extends('layouts.storefront.app')

@section('content')
@php
    $accountUrl = $store ? url('/account?store_slug=' . $store->slug) : url('/account');
    $accountOrderUrl = fn ($order) => $store
        ? url('/account/orders/' . $order->id . '?store_slug=' . $store->slug)
        : url('/account/orders/' . $order->id);
@endphp
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white font-outfit">
                My Orders (အော်ဒါမှတ်တမ်းများ)
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">သင်၏ အော်ဒါမှတ်တမ်းများ</p>
        </div>
        <a href="{{ $accountUrl }}" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">&larr; Account Dashboard</a>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-white/50 dark:border-slate-800/80 shadow-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-100/70 dark:bg-slate-800/70 border-b border-slate-200/60 dark:border-slate-800/60 font-bold text-slate-800 dark:text-slate-200">
                    <tr>
                        <th class="p-3.5">Order Number</th>
                        <th class="p-3.5">Date</th>
                        <th class="p-3.5">Total Amount</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-violet-50/30 dark:hover:bg-slate-800/30 transition">
                            <td class="p-3.5 font-mono font-extrabold text-violet-600 dark:text-violet-400">{{ $order->order_number }}</td>
                            <td class="p-3.5 text-xs text-slate-500 dark:text-slate-400">{{ $order->created_at->format('M d, Y H:i') }}</td>
                            <td class="p-3.5 font-extrabold text-slate-900 dark:text-white">Ks {{ number_format($order->total_amount) }}</td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full uppercase
                                    {{ $order->status === 'confirmed' ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300' : '' }}
                                    {{ $order->status === 'pending_contact' ? 'bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300' : '' }}
                                    {{ $order->status === 'cancelled' ? 'bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300' : '' }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="p-3.5 text-right">
                                <a href="{{ $accountOrderUrl($order) }}" class="px-3 py-1 bg-violet-600 text-white rounded-lg font-bold text-xs hover:bg-violet-500 transition">
                                    View Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                မည်သည့် အော်ဒါ မှတ်တမ်းမျှ မရှိသေးပါ။ (No order history)
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $orders->links() }}
    </div>
</div>
@endsection
