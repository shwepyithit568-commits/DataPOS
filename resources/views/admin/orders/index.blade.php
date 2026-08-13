@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Order Requests</h1>
            <p class="admin-page-sub">{{ $store->name }} · {{ number_format($totalCount) }} orders</p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="admin-hairline-grid grid-cols-1 sm:grid-cols-5">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-amber-600 dark:text-amber-400">Pending</div>
            <div class="admin-stat-value">{{ number_format($stats['pending']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">Confirmed</div>
            <div class="admin-stat-value">{{ number_format($stats['confirmed']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">Delivered</div>
            <div class="admin-stat-value">{{ number_format($stats['delivered'] ?? 0) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-red-600 dark:text-red-400">Cancelled</div>
            <div class="admin-stat-value">{{ number_format($stats['cancelled']) }}</div>
        </div>
        <div class="admin-hairline-cell" title="{{ __('messages.revenue_confirmed_only') }}">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">Revenue</div>
            <div class="admin-stat-value">Ks {{ number_format($stats['revenue']) }}</div>
            <div class="admin-stat-sub flex items-center gap-1">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                <span>{{ __('messages.pending_revenue') }}: Ks {{ number_format($stats['pendingRevenue'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    {{-- Success Flash --}}
    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Reusable Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="Search order #, customer name or phone..."
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => 'Newest',
            'oldest' => 'Oldest',
            'amount_high' => 'Amount: High to Low',
            'amount_low' => 'Amount: Low to High'
        ]"
        :filters="[
            'status' => [
                'label' => 'Status',
                'options' => ['pending_contact' => 'Pending Contact', 'confirmed' => 'Confirmed', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']
            ],
            'pricing_type' => [
                'label' => 'Pricing',
                'options' => ['retail' => 'Retail', 'wholesale' => 'Wholesale']
            ],
            'contact_channel' => [
                'label' => 'Channel',
                'options' => ['viber' => 'Viber', 'telegram' => 'Telegram', 'phone' => 'Phone']
            ]
        ]"
        :showViewToggle="false"
        :showExportImport="true"
        :exportUrl="url('/store/' . $store->slug . '/admin/orders/export')"
        :totalCount="$totalCount"
        :paginator="$orders"
    />

    {{-- ===== Orders: Card view (mobile only) ===== --}}
    <div class="sm:hidden space-y-3">
        @forelse ($orders as $order)
            <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 space-y-2.5 transition-colors duration-200">
                {{-- Top row: order # + status --}}
                <div class="flex items-center justify-between gap-2">
                    <a href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id) }}" class="font-mono font-bold text-violet-600 dark:text-violet-400 text-sm truncate">
                        {{ $order->order_number }}
                    </a>
                    @if ($order->admin_note)
                        <span class="text-sm" title="Admin note: {{ $order->admin_note }}">📝</span>
                    @endif
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full uppercase whitespace-nowrap
                        {{ $order->status === 'confirmed' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : '' }}
                        {{ $order->status === 'pending_contact' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' : '' }}
                        {{ $order->status === 'delivered' ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' : '' }}
                        {{ $order->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : '' }}">
                        {{ $order->status === 'pending_contact' ? 'Pending' : ($order->status === 'delivered' ? 'Delivered' : $order->status) }}
                    </span>
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full uppercase whitespace-nowrap {{ $order->payment_status === 'paid' ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400' }}">
                        {{ $order->payment_status }}
                    </span>
                </div>

                {{-- Customer info --}}
                <div>
                    <div class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $order->customer_name }}</div>
                    <div class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone }}</div>
                    @if ($order->contact_identifier)
                        <div class="text-xs text-violet-600 dark:text-violet-400 truncate">{{ $order->contact_identifier }}</div>
                    @endif
                </div>

                {{-- Meta row --}}
                <div class="flex items-center gap-2 flex-wrap text-xs">
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 font-semibold uppercase">{{ $order->contact_channel }}</span>
                    <span class="px-2 py-0.5 rounded-full {{ $order->pricing_type === 'wholesale' ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' : 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' }} font-semibold uppercase">{{ $order->pricing_type }}</span>
                    <span class="text-gray-400 dark:text-slate-500">{{ $order->created_at->format('M d, H:i') }}</span>
                </div>

                {{-- Total + actions --}}
                <div class="flex items-center justify-between gap-2 pt-2 border-t dark:border-slate-700">
                    <div>
                        <div class="text-xs text-gray-400 dark:text-slate-500">Total</div>
                        <div class="text-base font-bold text-gray-900 dark:text-slate-100">Ks {{ number_format($order->agreed_amount ?? $order->total_amount) }}</div>
                        @if ($order->agreed_amount !== null)
                            <div class="text-xs font-semibold text-violet-500 dark:text-violet-400">(agreed amount)</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id . '/status') }}" class="inline-flex items-center gap-1">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="text-xs border rounded-lg px-2 py-1.5 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-slate-100 cursor-pointer">
                                <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>Pending</option>
                                <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirm</option>
                                <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancel</option>
                            </select>
                            <button type="submit" class="px-2 py-1.5 rounded-lg bg-emerald-600 text-white font-semibold text-xs">Update</button>
                        </form>
                        <a href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id) }}" class="px-3 py-1.5 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/60 font-semibold text-xs border border-violet-200 dark:border-violet-800 whitespace-nowrap">
                            👁 View
                        </a>
                        <a href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id . '/invoice') }}" target="_blank" title="Printable invoice"
                            class="px-3 py-1.5 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/60 font-semibold text-xs border border-sky-200 dark:border-sky-800 whitespace-nowrap">
                            🧾
                        </a>
                        @if(auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this order? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/60 font-semibold text-xs border border-red-200 dark:border-red-800" title="Delete order">
                                    🗑
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 p-8 rounded-xl text-center">
                <div class="text-4xl mb-3 opacity-40">🛒</div>
                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">No orders found</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">Try adjusting your search or filters.</div>
            </div>
        @endforelse
    </div>

    {{-- ===== Orders: Table view (tablet + desktop) ===== --}}
    <div class="hidden sm:block bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">
        <div class="admin-panel overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm text-gray-600 dark:text-slate-300">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                    <tr>
                        <th class="p-3 whitespace-nowrap">Order #</th>
                        <th class="p-3 whitespace-nowrap">Customer</th>
                        <th class="p-3 whitespace-nowrap">Channel / Type</th>
                        <th class="p-3 whitespace-nowrap text-right">Total</th>
                        <th class="p-3 whitespace-nowrap">Date</th>
                        <th class="p-3 whitespace-nowrap">Status</th>
                        <th class="p-3 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition">
                            <td class="p-3">
                                <a href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id) }}" class="font-mono font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                    {{ $order->order_number }}
                                </a>
                                @if ($order->admin_note)
                                    <span class="ml-1" title="Admin note: {{ $order->admin_note }}">📝</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-gray-900 dark:text-slate-100">{{ $order->customer_name }}</div>
                                <div class="text-xs text-gray-400 dark:text-slate-500">{{ $order->customer_phone }}</div>
                                @if ($order->contact_identifier)
                                    <div class="text-xs text-violet-600 dark:text-violet-400">{{ $order->contact_identifier }}</div>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="uppercase text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300">{{ $order->contact_channel }}</span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full uppercase {{ $order->pricing_type === 'wholesale' ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' : 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' }}">{{ $order->pricing_type }}</span>
                                </div>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <div class="font-bold text-gray-900 dark:text-slate-100">Ks {{ number_format($order->agreed_amount ?? $order->total_amount) }}</div>
                                @if ($order->agreed_amount !== null)
                                    <div class="text-xs font-semibold text-violet-500 dark:text-violet-400">agreed</div>
                                @endif
                            </td>
                            <td class="p-3 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ $order->created_at->format('M d, Y H:i') }}</td>
                            <td class="p-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase whitespace-nowrap
                                        {{ $order->status === 'confirmed' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : '' }}
                                        {{ $order->status === 'pending_contact' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' : '' }}
                                        {{ $order->status === 'delivered' ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' : '' }}
                                        {{ $order->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : '' }}">
                                        {{ $order->status === 'pending_contact' ? 'Pending' : ($order->status === 'delivered' ? 'Delivered' : $order->status) }}
                                    </span>
                                    <span class="text-xs font-bold px-2 py-1 rounded-full uppercase whitespace-nowrap {{ $order->payment_status === 'paid' ? 'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400' }}">
                                        {{ $order->payment_status }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id . '/status') }}" class="inline-flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="text-xs border rounded-lg px-2 py-1.5 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-slate-100 cursor-pointer">
                                            <option value="pending_contact" {{ $order->status === 'pending_contact' ? 'selected' : '' }}>Pending</option>
                                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirm</option>
                                            <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancel</option>
                                        </select>
                                        <button type="submit" class="px-2 py-1.5 rounded-lg bg-emerald-600 text-white font-semibold text-xs">Update</button>
                                    </form>
                                    <a href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id) }}" class="px-2.5 py-1.5 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/60 font-semibold text-xs border border-violet-200 dark:border-violet-800 whitespace-nowrap">
                                        View
                                    </a>
                                    <a href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id . '/invoice') }}" target="_blank" title="Printable invoice"
                                        class="px-2.5 py-1.5 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/60 font-semibold text-xs border border-sky-200 dark:border-sky-800 whitespace-nowrap">
                                        🧾
                                    </a>
                                    @if(auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                                        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id) }}" class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this order? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1.5 rounded-lg bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/60 font-semibold text-xs border border-red-200 dark:border-red-800" title="Delete order">
                                                🗑
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center">
                                <div class="text-4xl mb-3 opacity-40">🛒</div>
                                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">No orders found</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">Try adjusting your search or filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if (method_exists($orders, 'links'))
        <div class="text-sm">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
