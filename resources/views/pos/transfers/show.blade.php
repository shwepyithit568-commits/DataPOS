@extends('layouts.admin.app')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('pos.transfers.index', $storeRouteParams) }}" class="text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300">← {{ __('messages.back') }}</a>
        <h1 class="text-xl font-semibold text-neutral-900 dark:text-white">🔄 {{ $transfer->transfer_number }}</h1>
        @php
            $statusColors = [
                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                'in_transit' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
                'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                'cancelled' => 'bg-neutral-100 text-neutral-500 dark:bg-white/10 dark:text-neutral-400',
            ];
        @endphp
        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full {{ $statusColors[$transfer->status] ?? '' }}">
            {{ __('messages.' . $transfer->status) }}
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

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5">
            <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">{{ __('messages.from_warehouse') }}</h3>
            <p class="font-semibold text-neutral-900 dark:text-white">{{ $transfer->fromWarehouse->name ?? '—' }}</p>
        </div>
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5">
            <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">{{ __('messages.to_warehouse') }}</h3>
            <p class="font-semibold text-neutral-900 dark:text-white">{{ $transfer->toWarehouse->name ?? '—' }}</p>
        </div>
    </div>

    @if($transfer->notes)
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5 mb-6">
            <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">{{ __('messages.notes') }}</h3>
            <p class="text-sm text-neutral-700 dark:text-neutral-300">{{ $transfer->notes }}</p>
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
                    <th class="text-right px-5 py-3 font-medium text-neutral-500 dark:text-neutral-400">{{ __('messages.unit_cost') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfer->items as $item)
                    <tr class="border-b border-neutral-100 dark:border-white/5">
                        <td class="px-5 py-3 text-neutral-500 dark:text-neutral-400">{{ $loop->iteration }}</td>
                        <td class="px-5 py-3 font-medium text-neutral-900 dark:text-white">{{ $item->product->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-neutral-700 dark:text-neutral-300">{{ number_format($item->quantity, 3) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-neutral-700 dark:text-neutral-300">{{ number_format($item->unit_cost, 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Timeline --}}
    <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl p-5 mb-6">
        <h3 class="font-semibold text-neutral-900 dark:text-white mb-3">{{ __('messages.timeline') }}</h3>
        <div class="space-y-3 text-sm">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                <span class="text-neutral-500 dark:text-neutral-400">{{ __('messages.created') }}: {{ $transfer->created_at->format('d M Y H:i') }}</span>
            </div>
            @if($transfer->shipped_at)
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    <span class="text-neutral-500 dark:text-neutral-400">{{ __('messages.shipped') }}: {{ $transfer->shipped_at->format('d M Y H:i') }}</span>
                </div>
            @endif
            @if($transfer->received_at)
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                    <span class="text-neutral-500 dark:text-neutral-400">{{ __('messages.received') }}: {{ $transfer->received_at->format('d M Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3">
        @if($transfer->status === 'pending')
            <form method="POST" action="{{ route('pos.transfers.ship', [...$storeRouteParams, 'transfer' => $transfer->id]) }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors cursor-pointer">
                    🚚 {{ __('messages.ship') }}
                </button>
            </form>
            <form method="POST" action="{{ route('pos.transfers.cancel', [...$storeRouteParams, 'transfer' => $transfer->id]) }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-white/5 rounded-lg hover:bg-neutral-200 dark:hover:bg-white/10 transition-colors cursor-pointer">
                    ✕ {{ __('messages.cancel') }}
                </button>
            </form>
        @endif

        @if($transfer->status === 'in_transit')
            <form method="POST" action="{{ route('pos.transfers.receive', [...$storeRouteParams, 'transfer' => $transfer->id]) }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors cursor-pointer">
                    ✅ {{ __('messages.receive') }}
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
