@extends('layouts.admin.app')

@section('title', __('messages.promotion_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="w-full space-y-5 sm:space-y-6"
     x-data="{
        showCreateModal: false,
        showEditModal: false,
        showValidateModal: false,
        editPromo: {
            id: null, name: '', code: '', type: 'percent_off', value: 0,
            min_order_amount: 0, total_uses_limit: '', per_customer_limit: '',
            starts_at: '', expires_at: '', is_active: 1, is_public: 0
        },
        validateCode: '', validateTotal: 0, validateResult: null,

        openEdit(id, name, code, type, value, minOrder, usesLimit, perLimit, startsAt, expiresAt, isActive, isPublic) {
            this.editPromo = { id, name, code, type, value, min_order_amount: minOrder,
                total_uses_limit: usesLimit || '', per_customer_limit: perLimit || '',
                starts_at: startsAt || '', expires_at: expiresAt || '', is_active: isActive ? 1 : 0, is_public: isPublic ? 1 : 0 };
            this.showEditModal = true;
        },

        async checkCoupon() {
            if (!this.validateCode) return;
            const url = '{{ route('store.admin.promotions.validate_coupon', ['store_slug' => $store->slug]) }}'
                + '?code=' + encodeURIComponent(this.validateCode)
                + '&order_total=' + encodeURIComponent(this.validateTotal);
            const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            this.validateResult = await r.json();
        }
     }">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                Ecommerce & POS Sales Rules
            </p>
            <h1 class="admin-page-title mt-0.5">{{ __('messages.promotion_title') }}</h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.promotion_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <button type="button" @click="showValidateModal = true"
                    class="admin-secondary-btn">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Test Coupon</span>
            </button>
            <button type="button" @click="showCreateModal = true"
                    class="admin-primary-btn bg-violet-600 hover:bg-violet-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.promotion_add') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash / Errors --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-sm text-rose-800 dark:text-rose-200">
            @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
        </div>
    @endif

    {{-- ============================================================
         KPI HAIRLINE GRID
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-5">
        <div class="admin-hairline-cell bg-violet-50/30 dark:bg-violet-950/20">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">{{ __('messages.promotion_total') }}</div>
            <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono">{{ number_format($stats['total']) }}</div>
            <div class="admin-stat-sub">All promotions</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.promotion_active') }}</div>
            <div class="admin-stat-value text-emerald-600 dark:text-emerald-400 font-mono">{{ number_format($stats['active']) }}</div>
            <div class="admin-stat-sub">Currently live</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-slate-500 dark:text-slate-400">{{ __('messages.promotion_expired') }}</div>
            <div class="admin-stat-value text-slate-600 dark:text-slate-300 font-mono">{{ number_format($stats['expired']) }}</div>
            <div class="admin-stat-sub">Inactive / ended</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-amber-600 dark:text-amber-400">{{ __('messages.promotion_total_uses') }}</div>
            <div class="admin-stat-value text-amber-600 dark:text-amber-400 font-mono">{{ number_format($stats['total_uses']) }}</div>
            <div class="admin-stat-sub">Times redeemed</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-rose-600 dark:text-rose-400">{{ __('messages.promotion_total_discount') }}</div>
            <div class="admin-stat-value text-rose-600 dark:text-rose-400 font-mono">{{ number_format($stats['total_discount']) }} Ks</div>
            <div class="admin-stat-sub">Cumulative savings</div>
        </div>
    </div>

    {{-- ============================================================
         FILTER BAR & TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
        {{-- Filters --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 font-outfit">
                All Promotions & Coupon Codes
            </h2>
            <form method="GET" action="{{ route('store.admin.promotions.index', ['store_slug' => $store->slug]) }}" class="flex flex-wrap gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search name or code..."
                       class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">

                <select name="type" class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold">
                    <option value="">All Types</option>
                    <option value="percent_off" {{ request('type') === 'percent_off' ? 'selected' : '' }}>% Off</option>
                    <option value="flat_off" {{ request('type') === 'flat_off' ? 'selected' : '' }}>Flat Off</option>
                    <option value="bogo" {{ request('type') === 'bogo' ? 'selected' : '' }}>BOGO</option>
                </select>

                <select name="status" class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                </select>

                <button type="submit" class="px-3.5 py-1.5 bg-violet-600 text-white rounded-xl text-xs font-bold hover:bg-violet-500 transition">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-mono uppercase">
                        <th class="pb-3">Promotion</th>
                        <th class="pb-3">Type / Value</th>
                        <th class="pb-3">Coupon Code</th>
                        <th class="pb-3 text-center">Uses</th>
                        <th class="pb-3">Validity</th>
                        <th class="pb-3 text-center">Status</th>
                        <th class="pb-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($promotions as $promo)
                        @php
                            $status = $promo->statusLabel();
                            $statusCss = match($status) {
                                'active'    => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                                'scheduled' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300',
                                'expired'   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                                'exhausted' => 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300',
                                default     => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                            };
                            $typeCss = match($promo->type) {
                                'percent_off' => 'bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-300',
                                'flat_off'    => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300',
                                'bogo'        => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                                default       => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                            };
                        @endphp
                        <tr>
                            {{-- Name / Scope --}}
                            <td class="py-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100">{{ $promo->name }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    @if($promo->is_public)
                                        <span class="text-violet-500">★ Auto-apply</span>
                                    @endif
                                    @if($promo->category)
                                        Category: {{ $promo->category->name }}
                                    @elseif($promo->product)
                                        Product: {{ $promo->product->name }}
                                    @else
                                        All products
                                    @endif
                                </div>
                            </td>

                            {{-- Type / Value --}}
                            <td class="py-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black font-mono {{ $typeCss }}">
                                    @if($promo->type === 'percent_off') {{ $promo->value }}% Off
                                    @elseif($promo->type === 'flat_off') {{ number_format($promo->value) }} Ks Off
                                    @else BOGO
                                    @endif
                                </span>
                                @if($promo->min_order_amount > 0)
                                    <div class="text-[10px] text-slate-400 mt-0.5">Min: {{ number_format($promo->min_order_amount) }} Ks</div>
                                @endif
                            </td>

                            {{-- Code --}}
                            <td class="py-3">
                                @if($promo->code)
                                    <code class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-black font-mono text-slate-900 dark:text-slate-100 select-all">{{ $promo->code }}</code>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Uses --}}
                            <td class="py-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                {{ number_format($promo->used_count) }}
                                @if($promo->total_uses_limit)
                                    <span class="text-slate-400 font-normal">/{{ number_format($promo->total_uses_limit) }}</span>
                                @endif
                            </td>

                            {{-- Validity --}}
                            <td class="py-3 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                @if($promo->starts_at)
                                    <div>From: {{ $promo->starts_at->format('d M Y') }}</div>
                                @endif
                                @if($promo->expires_at)
                                    <div class="{{ $promo->isExpired() ? 'text-rose-500' : '' }}">Until: {{ $promo->expires_at->format('d M Y') }}</div>
                                @else
                                    <div class="text-slate-400">No expiry</div>
                                @endif
                            </td>

                            {{-- Status badge --}}
                            <td class="py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusCss }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Edit --}}
                                    <button type="button"
                                            @click="openEdit(
                                                {{ $promo->id }},
                                                '{{ addslashes($promo->name) }}',
                                                '{{ $promo->code ?? '' }}',
                                                '{{ $promo->type }}',
                                                {{ $promo->value }},
                                                {{ $promo->min_order_amount }},
                                                {{ $promo->total_uses_limit ?? 'null' }},
                                                {{ $promo->per_customer_limit ?? 'null' }},
                                                '{{ $promo->starts_at?->format('Y-m-d') ?? '' }}',
                                                '{{ $promo->expires_at?->format('Y-m-d') ?? '' }}',
                                                {{ $promo->is_active ? 'true' : 'false' }},
                                                {{ $promo->is_public ? 'true' : 'false' }}
                                            )"
                                            class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    {{-- Toggle Active --}}
                                    <form method="POST" action="{{ route('store.admin.promotions.toggle', ['store_slug' => $store->slug, 'promotion' => $promo->id]) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-1.5 {{ $promo->is_active ? 'text-amber-500 hover:text-amber-700' : 'text-slate-400 hover:text-emerald-600' }} rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                                title="{{ $promo->is_active ? 'Deactivate' : 'Activate' }}">
                                            @if($promo->is_active)
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('store.admin.promotions.destroy', ['store_slug' => $store->slug, 'promotion' => $promo->id]) }}" onsubmit="return confirm('Delete this promotion?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 text-rose-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                    <span>No promotions yet. Click <strong>Create Promotion</strong> to add your first one.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($promotions->hasPages())
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                {{ $promotions->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         MODAL 1: CREATE PROMOTION
         ============================================================ --}}
    <div x-show="showCreateModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 overflow-y-auto" style="display:none;">
        <div @click.away="showCreateModal = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full my-4 shadow-2xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.promotion_add') }}</h3>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.promotions.store', ['store_slug' => $store->slug]) }}" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_name') }} *</label>
                    <input type="text" name="name" required placeholder="e.g. Thingyan Sale 2026" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_type') }} *</label>
                        <select name="type" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                            <option value="percent_off">{{ __('messages.promotion_type_percent_off') }}</option>
                            <option value="flat_off">{{ __('messages.promotion_type_flat_off') }}</option>
                            <option value="bogo">{{ __('messages.promotion_type_bogo') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_value') }} *</label>
                        <input type="number" name="value" step="0.01" min="0" required placeholder="e.g. 10" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_code') }}</label>
                        <input type="text" name="code" placeholder="e.g. THADINGYUT10" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_min_order') }}</label>
                        <input type="number" name="min_order_amount" step="1000" min="0" value="0" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Starts At</label>
                        <input type="date" name="starts_at" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expires At</label>
                        <input type="date" name="expires_at" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_uses_limit') }}</label>
                        <input type="number" name="total_uses_limit" min="1" placeholder="Unlimited" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_per_customer_limit') }}</label>
                        <input type="number" name="per_customer_limit" min="1" placeholder="Unlimited" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-violet-600">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" class="w-4 h-4 rounded text-violet-600">
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.promotion_is_public') }}</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 text-white font-bold shadow-md">Create Promotion</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 2: EDIT PROMOTION
         ============================================================ --}}
    <div x-show="showEditModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 overflow-y-auto" style="display:none;">
        <div @click.away="showEditModal = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full my-4 shadow-2xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">
                    Edit: <span x-text="editPromo.name" class="text-violet-600"></span>
                </h3>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST"
                  :action="'{{ url('/store/' . $store->slug . '/admin/promotions') }}/' + editPromo.id"
                  class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_name') }} *</label>
                    <input type="text" name="name" x-model="editPromo.name" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_type') }} *</label>
                        <select name="type" x-model="editPromo.type" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                            <option value="percent_off">% Off</option>
                            <option value="flat_off">Flat Off</option>
                            <option value="bogo">BOGO</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_value') }} *</label>
                        <input type="number" name="value" step="0.01" min="0" x-model="editPromo.value" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_code') }}</label>
                        <input type="text" name="code" x-model="editPromo.code" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.promotion_min_order') }}</label>
                        <input type="number" name="min_order_amount" step="1000" min="0" x-model="editPromo.min_order_amount" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Starts At</label>
                        <input type="date" name="starts_at" x-model="editPromo.starts_at" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expires At</label>
                        <input type="date" name="expires_at" x-model="editPromo.expires_at" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Total Uses Limit</label>
                        <input type="number" name="total_uses_limit" min="1" x-model="editPromo.total_uses_limit" placeholder="Unlimited" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Per Customer Limit</label>
                        <input type="number" name="per_customer_limit" min="1" x-model="editPromo.per_customer_limit" placeholder="Unlimited" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editPromo.is_active == 1" class="w-4 h-4 rounded text-violet-600">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" :checked="editPromo.is_public == 1" class="w-4 h-4 rounded text-violet-600">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Auto-Apply</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 text-white font-bold shadow-md">Update Promotion</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 3: TEST COUPON VALIDATOR
         ============================================================ --}}
    <div x-show="showValidateModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" style="display:none;">
        <div @click.away="showValidateModal = false" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">
                    🎟 Test Coupon Validator
                </h3>
                <button type="button" @click="showValidateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Coupon Code</label>
                    <input type="text" x-model="validateCode" @keydown.enter="checkCoupon()" placeholder="e.g. THADINGYUT10"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 uppercase">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Order Total (MMK)</label>
                    <input type="number" x-model="validateTotal" step="1000" min="0"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>
                <button type="button" @click="checkCoupon()" class="w-full px-4 py-2.5 rounded-xl bg-violet-600 text-white font-bold shadow-md hover:bg-violet-500 transition">
                    Validate Coupon
                </button>

                <template x-if="validateResult">
                    <div :class="validateResult.valid ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200'"
                         class="p-4 rounded-2xl border space-y-1">
                        <p class="font-bold" x-text="validateResult.message"></p>
                        <template x-if="validateResult.valid">
                            <p class="text-sm font-mono font-black" x-text="'Discount: ' + Number(validateResult.discount).toLocaleString() + ' Ks'"></p>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
