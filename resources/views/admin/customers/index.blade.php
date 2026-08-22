@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">👥 {{ __('messages.sidebar_customer_directory') }}</h1>
            <p class="admin-page-sub">{{ $store->name }} · {{ number_format($totalCount) }} {{ __('messages.customers') ?? 'customers' }}</p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">Total</div>
            <div class="admin-stat-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">Retail</div>
            <div class="admin-stat-value">{{ number_format($stats['retail']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">Wholesale</div>
            <div class="admin-stat-value">{{ number_format($stats['wholesale']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-amber-600 dark:text-amber-400">With Debt</div>
            <div class="admin-stat-value">{{ number_format($stats['debt']) }}</div>
        </div>
    </div>

    {{-- Success Flash --}}
    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="Search name, phone, email..."
        :sort="request('sort', 'name')"
        :sortOptions="[
            'name'   => 'Name: A to Z',
            'newest' => 'Newest',
            'phone'  => 'Phone',
        ]"
        :filters="[
            'role' => [
                'label' => 'Customer Type',
                'options' => ['retail_customer' => 'Retail', 'wholesale_customer' => 'Wholesale']
            ]
        ]"
        :showViewToggle="false"
        :showExportImport="false"
        :totalCount="$totalCount"
        :paginator="$customers"
    />

    {{-- ===== Card view (mobile only) ===== --}}
    <div class="sm:hidden space-y-3">
        @forelse ($customers as $customer)
            <a href="{{ route('store.admin.customers.show', [...$storeRouteParams, 'customer' => $customer->id]) }}"
               class="block bg-white dark:bg-slate-800 rounded-xl p-3.5 space-y-2.5 transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $customer->name }}</div>
                        <div class="text-xs text-gray-400 dark:text-slate-500">{{ $customer->phone ?? 'No phone' }}</div>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full uppercase whitespace-nowrap
                        {{ ($customer->stores->first()?->pivot->role ?? 'retail_customer') === 'wholesale_customer'
                            ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300'
                            : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' }}">
                        {{ ($customer->stores->first()?->pivot->role ?? 'retail_customer') === 'wholesale_customer' ? 'Wholesale' : 'Retail' }}
                    </span>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-slate-400">
                        Joined {{ $customer->created_at->format('M d, Y') }}
                    </span>
                    @if ((float) ($customer->debt_balance ?? '0.00') > 0)
                        <span class="text-amber-600 dark:text-amber-400 font-semibold">
                            💰 {{ number_format((float) $customer->debt_balance, 0) }} MMK debt
                        </span>
                    @endif
                </div>
            </a>
        @empty
            <div class="bg-white dark:bg-slate-800 p-8 rounded-xl text-center">
                <div class="text-4xl mb-3 opacity-40">👥</div>
                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">No customers found</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">Customers will appear here after POS sales or pilot import.</div>
            </div>
        @endforelse
    </div>

    {{-- ===== Table view (tablet + desktop) ===== --}}
    <div class="hidden sm:block bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">
        <div class="admin-panel overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm text-gray-600 dark:text-slate-300">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                    <tr>
                        <th class="p-3 whitespace-nowrap">Customer</th>
                        <th class="p-3 whitespace-nowrap">Phone</th>
                        <th class="p-3 whitespace-nowrap">Type</th>
                        <th class="p-3 whitespace-nowrap">Joined</th>
                        <th class="p-3 whitespace-nowrap text-right">Debt Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition cursor-pointer"
                            onclick="window.location='{{ route('store.admin.customers.show', [...$storeRouteParams, 'customer' => $customer->id]) }}'">
                            <td class="p-3">
                                <div class="font-bold text-gray-900 dark:text-slate-100">{{ $customer->name }}</div>
                                @if ($customer->email)
                                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ $customer->email }}</div>
                                @endif
                            </td>
                            <td class="p-3 whitespace-nowrap">{{ $customer->phone ?? '—' }}</td>
                            <td class="p-3">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase whitespace-nowrap
                                    {{ ($customer->stores->first()?->pivot->role ?? 'retail_customer') === 'wholesale_customer'
                                        ? 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300'
                                        : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' }}">
                                    {{ ($customer->stores->first()?->pivot->role ?? 'retail_customer') === 'wholesale_customer' ? 'Wholesale' : 'Retail' }}
                                </span>
                            </td>
                            <td class="p-3 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ $customer->created_at->format('M d, Y') }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                @if ((float) ($customer->debt_balance ?? '0.00') > 0)
                                    <span class="text-amber-600 dark:text-amber-400 font-semibold">
                                        {{ number_format((float) $customer->debt_balance, 0) }} MMK
                                    </span>
                                @else
                                    <span class="text-gray-300 dark:text-slate-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center">
                                <div class="text-4xl mb-3 opacity-40">👥</div>
                                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">No customers found</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">Customers will appear here after POS sales or pilot import.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if (method_exists($customers, 'links'))
        <div class="text-sm">{{ $customers->links() }}</div>
    @endif
</div>
@endsection
