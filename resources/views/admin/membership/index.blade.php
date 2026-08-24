@extends('layouts.admin.app')

@section('title', __('messages.membership_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<script nonce="{{ $cspNonce }}">
window.membershipHubData = function () {
    return {
        // Modals
        showAddTierModal: false,
        showEditTierModal: false,
        showAdjustPointsModal: false,
        showAssignTierModal: false,

        // Active editing tier
        editingTier: {
            id: null,
            name: '',
            code: '',
            min_spending: 0,
            discount_percent: 0,
            point_multiplier: 1.0,
            badge_color: 'slate',
            is_active: 1
        },

        // Active customer for point/tier adjustments
        adjustTarget: {
            customer_id: null,
            customer_name: '',
            current_points: 0,
            points: 100,
            type: 'bonus',
            notes: ''
        },

        assignTarget: {
            customer_id: null,
            customer_name: '',
            current_tier_id: null,
            new_tier_id: ''
        },

        openEditTier(id, name, code, minSpending, discount, multiplier, color, active) {
            this.editingTier = {
                id,
                name,
                code,
                min_spending: minSpending,
                discount_percent: discount,
                point_multiplier: multiplier,
                badge_color: color,
                is_active: active ? 1 : 0
            };
            this.showEditTierModal = true;
        },

        openAdjustPoints(id, name, points) {
            this.adjustTarget = {
                customer_id: id,
                customer_name: name,
                current_points: points,
                points: 100,
                type: 'bonus',
                notes: 'Loyalty bonus / VIP reward'
            };
            this.showAdjustPointsModal = true;
        },

        openAssignTier(id, name, tierId) {
            this.assignTarget = {
                customer_id: id,
                customer_name: name,
                current_tier_id: tierId,
                new_tier_id: tierId || ''
            };
            this.showAssignTierModal = true;
        }
    };
};
</script>

<div class="w-full space-y-5 sm:space-y-6" x-data="membershipHubData()">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.sidebar_customers') ?? 'Customer Loyalty & Retention' }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.membership_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.membership_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Add New Tier Trigger --}}
            <button type="button"
                    @click="showAddTierModal = true"
                    class="admin-primary-btn bg-violet-600 hover:bg-violet-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.membership_add_tier') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-sm text-rose-800 dark:text-rose-200">
            @foreach ($errors->all() as $err)
                <p>{{ $err }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         SUMMARY STATS HAIRLINE GRID
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        {{-- 1. Total Registered Members --}}
        <div class="admin-hairline-cell bg-violet-50/30 dark:bg-violet-950/20">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">{{ __('messages.membership_total_members') }}</div>
            <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono">
                {{ number_format($stats['total_members']) }}
            </div>
            <div class="admin-stat-sub text-slate-500">Registered Customers</div>
        </div>

        {{-- 2. Active VIP Tiers --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">{{ __('messages.membership_active_tiers') }}</div>
            <div class="admin-stat-value text-blue-600 dark:text-blue-400 font-mono">
                {{ $stats['active_tiers'] }}
            </div>
            <div class="admin-stat-sub text-slate-400">Progression Levels</div>
        </div>

        {{-- 3. Points in Circulation --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-amber-600 dark:text-amber-400">{{ __('messages.membership_points_circulation') }}</div>
            <div class="admin-stat-value text-amber-600 dark:text-amber-400 font-mono">
                {{ number_format($stats['points_in_circulation']) }} Pts
            </div>
            <div class="admin-stat-sub text-slate-400">Customer Balance</div>
        </div>

        {{-- 4. Total Points Redeemed --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.membership_points_redeemed') }}</div>
            <div class="admin-stat-value text-emerald-600 dark:text-emerald-400 font-mono">
                {{ number_format($stats['total_points_redeemed']) }} Pts
            </div>
            <div class="admin-stat-sub text-slate-400">Claimed Rewards</div>
        </div>
    </div>

    {{-- ============================================================
         MEMBERSHIP TIERS GALLERY
         ============================================================ --}}
    <div class="space-y-3">
        <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono">
            VIP Membership Tiers & Benefits ({{ count($tiers) }})
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($tiers as $tier)
                @php
                    $colors = match($tier->badge_color) {
                        'blue' => 'border-blue-300 dark:border-blue-800/80 bg-blue-50/20 dark:bg-blue-950/20 text-blue-700 dark:text-blue-300',
                        'amber' => 'border-amber-300 dark:border-amber-800/80 bg-amber-50/20 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300',
                        'purple' => 'border-purple-300 dark:border-purple-800/80 bg-purple-50/20 dark:bg-purple-950/20 text-purple-700 dark:text-purple-300',
                        'emerald' => 'border-emerald-300 dark:border-emerald-800/80 bg-emerald-50/20 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300',
                        'rose' => 'border-rose-300 dark:border-rose-800/80 bg-rose-50/20 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300',
                        default => 'border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300',
                    };
                    $badgePill = match($tier->badge_color) {
                        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200',
                        'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200',
                        'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-200',
                        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200',
                        'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200',
                        default => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
                    };
                @endphp

                <div class="rounded-3xl border {{ $colors }} p-5 shadow-sm flex flex-col justify-between space-y-4 transition hover:shadow-md">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black font-mono {{ $badgePill }}">
                                {{ $tier->code }}
                            </span>
                            @if($tier->is_default)
                                <span class="text-[11px] font-black text-amber-500 flex items-center gap-0.5">
                                    ★ Default
                                </span>
                            @endif
                        </div>

                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 mt-2 font-outfit">
                            {{ $tier->name }}
                        </h3>

                        {{-- Tier Benefits List --}}
                        <div class="mt-3 space-y-2 text-xs">
                            <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                                <span>Min Spend:</span>
                                <strong class="font-mono text-slate-900 dark:text-slate-100">{{ number_format($tier->min_spending) }} Ks</strong>
                            </div>
                            <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                                <span>VIP Discount:</span>
                                <strong class="font-mono text-emerald-600 dark:text-emerald-400">{{ $tier->discount_percent }}% Off</strong>
                            </div>
                            <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                                <span>Point Multiplier:</span>
                                <strong class="font-mono text-violet-600 dark:text-violet-400">{{ $tier->point_multiplier }}x Pts</strong>
                            </div>
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-slate-500">
                                <span>Members:</span>
                                <span class="font-bold">{{ number_format($tier->members_count ?? 0) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-1">
                        <button type="button"
                                @click="openEditTier({{ $tier->id }}, '{{ addslashes($tier->name) }}', '{{ $tier->code }}', {{ $tier->min_spending }}, {{ $tier->discount_percent }}, {{ $tier->point_multiplier }}, '{{ $tier->badge_color }}', {{ $tier->is_active ? 'true' : 'false' }})"
                                class="p-1.5 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Edit Tier">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>

                        @if(!$tier->is_default)
                            <form method="POST" action="{{ route('store.admin.membership.tiers.destroy', ['store_slug' => $store->slug, 'tier' => $tier->id]) }}" onsubmit="return confirm('Delete this membership tier?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 text-rose-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                        title="Delete Tier">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         CUSTOMER MEMBERS & LOYALTY DIRECTORY TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 font-outfit">
                    {{ __('messages.membership_members_directory') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Customer loyalty balances, VIP tier progression, and manual reward adjustments
                </p>
            </div>

            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('store.admin.membership.index', ['store_slug' => $store->slug]) }}" class="flex flex-wrap items-center gap-2">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search name, phone..."
                       class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">

                <select name="tier_id" class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold">
                    <option value="">All VIP Tiers</option>
                    @foreach($tiers as $t)
                        <option value="{{ $t->id }}" {{ request('tier_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="px-3.5 py-1.5 bg-violet-600 text-white rounded-xl text-xs font-bold hover:bg-violet-500 transition">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-mono uppercase">
                        <th class="pb-3">Customer</th>
                        <th class="pb-3">Contact</th>
                        <th class="pb-3">VIP Membership Tier</th>
                        <th class="pb-3 text-right">Points Balance</th>
                        <th class="pb-3 text-right">Lifetime Spend</th>
                        <th class="pb-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($members as $m)
                        @php
                            $tierColor = match($m->tier_color) {
                                'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200',
                                'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200',
                                'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-200',
                                'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200',
                                'rose' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200',
                                default => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
                            };
                        @endphp
                        <tr>
                            {{-- Customer Name --}}
                            <td class="py-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100">{{ $m->name }}</div>
                                <span class="text-[10px] text-slate-400">{{ ucfirst($m->customer_role ?? 'Customer') }}</span>
                            </td>

                            {{-- Contact --}}
                            <td class="py-3 font-mono text-slate-600 dark:text-slate-300">
                                {{ $m->phone ?? $m->email ?? '-' }}
                            </td>

                            {{-- Current Tier Badge --}}
                            <td class="py-3">
                                @if($m->tier_name)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black font-mono {{ $tierColor }}">
                                        {{ $m->tier_name }}
                                        @if($m->tier_discount > 0)
                                            <span class="text-[10px] opacity-80">({{ $m->tier_discount }}% off)</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-slate-400">Standard Member</span>
                                @endif
                            </td>

                            {{-- Points Balance --}}
                            <td class="py-3 text-right">
                                <span class="font-mono text-sm font-black text-amber-600 dark:text-amber-400">
                                    {{ number_format($m->loyalty_points) }}
                                </span>
                                <span class="text-[10px] text-slate-400 ml-0.5">Pts</span>
                            </td>

                            {{-- Lifetime Spend --}}
                            <td class="py-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format($m->total_spent) }} Ks
                            </td>

                            {{-- Quick Actions --}}
                            <td class="py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Adjust Points --}}
                                    <button type="button"
                                            @click="openAdjustPoints({{ $m->id }}, '{{ addslashes($m->name) }}', {{ $m->loyalty_points }})"
                                            class="px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 hover:bg-amber-100 transition">
                                        {{ __('messages.membership_adjust_points') }}
                                    </button>

                                    {{-- Change Tier --}}
                                    <button type="button"
                                            @click="openAssignTier({{ $m->id }}, '{{ addslashes($m->name) }}', {{ $m->membership_tier_id ?? 'null' }})"
                                            class="px-2.5 py-1 rounded-xl text-xs font-bold bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60 hover:bg-violet-100 transition">
                                        {{ __('messages.membership_assign_tier') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No registered customer members found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         MODAL 1: ADD TIER
         ============================================================ --}}
    <div x-show="showAddTierModal"
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="showAddTierModal = false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">
                    {{ __('messages.membership_add_tier') }}
                </h3>
                <button type="button" @click="showAddTierModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.membership.tiers.store', ['store_slug' => $store->slug]) }}" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_tier_name') }} *</label>
                        <input type="text" name="name" required placeholder="e.g. Diamond VIP" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_tier_code') }} *</label>
                        <input type="text" name="code" required placeholder="e.g. DIAMOND" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_min_spending') }} *</label>
                    <input type="number" step="1000" min="0" name="min_spending" required placeholder="e.g. 5000000" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_discount_percent') }} *</label>
                        <input type="number" step="0.5" min="0" max="100" name="discount_percent" required placeholder="e.g. 15" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_point_multiplier') }} *</label>
                        <input type="number" step="0.1" min="0.1" max="10" name="point_multiplier" required value="2.0" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_badge_color') }}</label>
                    <select name="badge_color" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                        <option value="slate">Slate (Default)</option>
                        <option value="blue">Blue (Silver Theme)</option>
                        <option value="amber">Amber (Gold Theme)</option>
                        <option value="purple">Purple (Platinum Theme)</option>
                        <option value="emerald">Emerald Green</option>
                        <option value="rose">Rose Red</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAddTierModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 text-white font-bold shadow-md">Create Tier</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 2: EDIT TIER
         ============================================================ --}}
    <div x-show="showEditTierModal"
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="showEditTierModal = false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">
                    Edit Tier: <span x-text="editingTier.name" class="text-violet-600"></span>
                </h3>
                <button type="button" @click="showEditTierModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST"
                  :action="'{{ url('/store/' . $store->slug . '/admin/membership/tiers') }}/' + editingTier.id"
                  class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_tier_name') }} *</label>
                        <input type="text" name="name" x-model="editingTier.name" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_tier_code') }} *</label>
                        <input type="text" name="code" x-model="editingTier.code" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_min_spending') }} *</label>
                    <input type="number" step="1000" min="0" name="min_spending" x-model="editingTier.min_spending" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_discount_percent') }} *</label>
                        <input type="number" step="0.5" min="0" max="100" name="discount_percent" x-model="editingTier.discount_percent" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_point_multiplier') }} *</label>
                        <input type="number" step="0.1" min="0.1" max="10" name="point_multiplier" x-model="editingTier.point_multiplier" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.membership_badge_color') }}</label>
                    <select name="badge_color" x-model="editingTier.badge_color" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                        <option value="slate">Slate (Default)</option>
                        <option value="blue">Blue (Silver Theme)</option>
                        <option value="amber">Amber (Gold Theme)</option>
                        <option value="purple">Purple (Platinum Theme)</option>
                        <option value="emerald">Emerald Green</option>
                        <option value="rose">Rose Red</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_active" value="1" :checked="editingTier.is_active == 1" class="w-4 h-4 rounded text-violet-600">
                    <span class="font-bold text-slate-800 dark:text-slate-200">Active VIP Tier</span>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditTierModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 text-white font-bold shadow-md">Update Tier</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 3: ADJUST LOYALTY POINTS
         ============================================================ --}}
    <div x-show="showAdjustPointsModal"
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="showAdjustPointsModal = false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">
                    Adjust Points: <span x-text="adjustTarget.customer_name" class="text-amber-600"></span>
                </h3>
                <button type="button" @click="showAdjustPointsModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.membership.adjust_points', ['store_slug' => $store->slug]) }}" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="customer_id" :value="adjustTarget.customer_id">

                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 rounded-2xl flex justify-between items-center">
                    <span class="text-slate-600 dark:text-slate-400">Current Points Balance:</span>
                    <strong class="font-mono text-base font-black text-amber-600 dark:text-amber-400" x-text="Number(adjustTarget.current_points).toLocaleString() + ' Pts'"></strong>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Adjustment Type *</label>
                        <select name="type" x-model="adjustTarget.type" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                            <option value="bonus">Grant Bonus (+)</option>
                            <option value="adjusted">Correction Adjustment (+/-)</option>
                            <option value="redeemed">Manual Redeem (-)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Points Amount *</label>
                        <input type="number" name="points" x-model="adjustTarget.points" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Reason / Notes</label>
                    <input type="text" name="notes" x-model="adjustTarget.notes" placeholder="e.g. Birthday reward, store promotion" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAdjustPointsModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 text-white font-bold shadow-md">Apply Points</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 4: ASSIGN MEMBERSHIP TIER
         ============================================================ --}}
    <div x-show="showAssignTierModal"
         x-transition.opacity
         class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="showAssignTierModal = false"
             class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
            <div class="flex justify-between items-center">
                <h3 class="text-base font-black text-slate-900 dark:text-slate-100 font-outfit">
                    Change VIP Tier: <span x-text="assignTarget.customer_name" class="text-violet-600"></span>
                </h3>
                <button type="button" @click="showAssignTierModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.membership.assign_tier', ['store_slug' => $store->slug]) }}" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="customer_id" :value="assignTarget.customer_id">

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Membership Tier *</label>
                    <select name="tier_id" x-model="assignTarget.new_tier_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100">
                        @foreach($tiers as $t)
                            <option value="{{ $t->id }}">
                                {{ $t->name }} ({{ $t->code }}) — {{ $t->discount_percent }}% Off
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAssignTierModal = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-violet-600 text-white font-bold shadow-md">Assign Tier</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
