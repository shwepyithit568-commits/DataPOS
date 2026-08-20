@extends('layouts.admin.app')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('pos.buybacks.index', $storeRouteParams) }}" class="text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300">← {{ __('messages.back') }}</a>
        <h1 class="text-xl font-semibold text-neutral-900 dark:text-white">↩️ {{ $buyback->buyback_number }}</h1>
        @php
            $statusColors = [
                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                'cancelled' => 'bg-neutral-100 text-neutral-500 dark:bg-white/10 dark:text-neutral-400',
            ];
        @endphp
        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full {{ $statusColors[$buyback->status] ?? '' }}">
            {{ __('messages.' . $buyback->status) }}
        </span>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-lg text-sm text-emerald-600 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg text-sm text-red-600 dark:text-red-400">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5">
            <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">{{ __('messages.buyback_number') }}</h3>
            <p class="font-mono font-semibold text-neutral-900 dark:text-white">{{ $buyback->buyback_number }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5">
            <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">{{ __('messages.total_value') }}</h3>
            <p class="font-semibold text-lg text-neutral-900 dark:text-white">{{ number_format($buyback->total_value, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5">
            <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">{{ __('messages.created_by') }}</h3>
            <p class="font-semibold text-neutral-900 dark:text-white">{{ $buyback->creator->name ?? '—' }}</p>
        </div>
    </div>

    @if($buyback->reason)
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5 mb-6">
            <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">{{ __('messages.reason') }}</h3>
            <p class="text-sm text-neutral-700 dark:text-neutral-300">{{ $buyback->reason }}</p>
        </div>
    @endif

    @if($buyback->notes)
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5 mb-6">
            <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">{{ __('messages.notes') }}</h3>
            <p class="text-sm text-neutral-700 dark:text-neutral-300">{{ $buyback->notes }}</p>
        </div>
    @endif

    {{-- Items --}}
    <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-neutral-200 dark:border-white/10">
            <h3 class="font-semibold text-neutral-900 dark:text-white">{{ __('messages.items') }}</h3>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-neutral-200 dark:border-white/10">
                    <th class="text-left px-5 py-3 font-medium text-neutral-500 dark:text-neutral-400">#</th>
                    <th class="text-left px-5 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.product') }}</th>
                    <th class="text-right px-5 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.quantity') }}</th>
                    <th class="text-right px-5 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.unit_price') }}</th>
                    <th class="text-right px-5 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.subtotal') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($buyback->items as $item)
                    <tr class="border-b border-neutral-100 dark:border-white/5">
                        <td class="px-5 py-3 text-neutral-500 dark:text-neutral-400">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3 font-medium text-neutral-900 dark:text-white">{{ $item->product->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-neutral-700 dark:text-neutral-300">{{ number_format($item->quantity, 3) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-neutral-700 dark:text-neutral-300">{{ number_format($item->unit_price, 4) }}</td>
                        <td class="px-5 py-3 text-right font-mono font-semibold text-neutral-900 dark:text-white">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-neutral-50 dark:bg-white/[2%]">
                    <td colspan="4" class="px-5 py-3 text-right font-semibold text-neutral-900 dark:text-white">{{ __('messages.total') }}</td>
                    <td class="px-5 py-3 text-right font-mono font-bold text-neutral-900 dark:text-white">{{ number_format($buyback->total_value, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3">
        @if($buyback->status === 'pending')
            <form method="POST" action="{{ route('pos.buybacks.complete', [...$storeRouteParams, 'buyback' => $buyback->id]) }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors cursor-pointer">
                    ✅ {{ __('messages.complete') }}
                </button>
            </form>
            <form method="POST" action="{{ route('pos.buybacks.cancel', [...$storeRouteParams, 'buyback' => $buyback->id]) }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-white/5 rounded-lg hover:bg-neutral-200 dark:hover:bg-white/10 transition-colors cursor-pointer">
                    ✕ {{ __('messages.cancel') }}
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
