@extends('layouts.admin.app')

@section('title', __('messages.membership_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

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

<div class="w-full space-y-2 sm:space-y-2.5" x-data="membershipHubData()">

    {{-- ============================================================
         1. TOP PAGE HEADER — Eyebrow, Title, Context & Add Tier Action
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>{{ __('messages.membership_title') }}</span>
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $store->name }} · {{ __('messages.membership_subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                    @click="showAddTierModal = true"
                    class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <span class="text-sm leading-none">+</span>
                <span>{{ __('messages.membership_add_tier') }}</span>
            </button>
        </div>
    </header>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') }}:</span>
            </div>
            @foreach ($errors->all() as $err)
                <p class="ml-4">• {{ $err }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. 4 KEY KPI STAT CARDS
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">
        {{-- Total Registered Members --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-violet-600 dark:text-violet-400 truncate">{{ __('messages.membership_total_members') }}</span>
                <span class="text-xs">👥</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-violet-700 dark:text-violet-300 mt-1 font-mono tracking-tight">{{ number_format($stats['total_members']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Registered Customers</div>
        </div>

        {{-- Active VIP Tiers --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400 truncate">{{ __('messages.membership_active_tiers') }}</span>
                <span class="text-xs">🏆</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-blue-600 dark:text-blue-400 mt-1 font-mono tracking-tight">{{ $stats['active_tiers'] }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Progression Levels</div>
        </div>

        {{-- Points in Circulation --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 truncate">{{ __('messages.membership_points_circulation') }}</span>
                <span class="text-xs">🪙</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono tracking-tight">{{ number_format($stats['points_in_circulation']) }} <span class="text-xs font-normal">Pts</span></div>
            <div class="text-[10px] text-slate-400 mt-0.5">Customer Balance</div>
        </div>

        {{-- Total Points Redeemed --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 truncate">{{ __('messages.membership_points_redeemed') }}</span>
                <span class="text-xs">🎁</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono tracking-tight">{{ number_format($stats['total_points_redeemed']) }} <span class="text-xs font-normal">Pts</span></div>
            <div class="text-[10px] text-slate-400 mt-0.5">Claimed Rewards</div>
        </div>
    </div>

    {{-- ============================================================
         3. MEMBERSHIP TIERS GALLERY
         ============================================================ --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between px-1">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <span>⭐</span>
                <span>VIP Membership Tiers & Privileges ({{ count($tiers) }})</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
            @foreach($tiers as $tier)
                @php
                    $colors = match($tier->badge_color) {
                        'blue' => 'border-blue-200 dark:border-blue-800/60 bg-blue-50/20 dark:bg-blue-950/20',
                        'amber' => 'border-amber-200 dark:border-amber-800/60 bg-amber-50/20 dark:bg-amber-950/20',
                        'purple' => 'border-purple-200 dark:border-purple-800/60 bg-purple-50/20 dark:bg-purple-950/20',
                        'emerald' => 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/20 dark:bg-emerald-950/20',
                        'rose' => 'border-rose-200 dark:border-rose-800/60 bg-rose-50/20 dark:bg-rose-950/20',
                        default => 'border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900',
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

                <div class="rounded-lg border {{ $colors }} p-3 shadow-2xs flex flex-col justify-between space-y-3 transition hover:border-slate-300 dark:hover:border-slate-700">
                    <div>
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black font-mono {{ $badgePill }}">
                                {{ $tier->code }}
                            </span>
                            @if($tier->is_default)
                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 flex items-center gap-0.5">
                                    ★ Default
                                </span>
                            @endif
                        </div>

                        <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 mt-1.5 truncate">
                            {{ $tier->name }}
                        </h3>

                        {{-- Tier Benefits List --}}
                        <div class="mt-2 space-y-1.5 text-xs">
                            <div class="flex justify-between items-center text-slate-500 dark:text-slate-400">
                                <span>Min Spend:</span>
                                <strong class="font-mono text-slate-900 dark:text-slate-100 text-[11px]">{{ number_format($tier->min_spending) }} Ks</strong>
                            </div>
                            <div class="flex justify-between items-center text-slate-500 dark:text-slate-400">
                                <span>VIP Discount:</span>
                                <strong class="font-mono text-emerald-600 dark:text-emerald-400 text-[11px]">{{ $tier->discount_percent }}% Off</strong>
                            </div>
                            <div class="flex justify-between items-center text-slate-500 dark:text-slate-400">
                                <span>Point Multiplier:</span>
                                <strong class="font-mono text-violet-600 dark:text-violet-400 text-[11px]">{{ $tier->point_multiplier }}x Pts</strong>
                            </div>
                            <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center text-slate-400 text-[11px]">
                                <span>Members:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">{{ number_format($tier->members_count ?? 0) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-1">
                        <button type="button"
                                @click="openEditTier({{ $tier->id }}, '{{ addslashes($tier->name) }}', '{{ $tier->code }}', {{ $tier->min_spending }}, {{ $tier->discount_percent }}, {{ $tier->point_multiplier }}, '{{ $tier->badge_color }}', {{ $tier->is_active ? 'true' : 'false' }})"
                                class="px-2 py-1 text-xs font-bold text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                                title="Edit Tier">
                            Edit
                        </button>

                        @if(!$tier->is_default)
                            <form method="POST" action="{{ route('store.admin.membership.tiers.destroy', ['store_slug' => $store->slug, 'tier' => $tier->id]) }}" onsubmit="return confirm('Delete this membership tier?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-md transition cursor-pointer"
                                        title="Delete Tier">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         4. CUSTOMER MEMBERS & LOYALTY DIRECTORY TABLE
         ============================================================ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition space-y-0">
        <div class="p-2.5 sm:p-3 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
            <div>
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                    <span>👥</span>
                    <span>{{ __('messages.membership_members_directory') }}</span>
                </h2>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Customer loyalty balances, VIP tier progression, and manual reward adjustments
                </p>
            </div>

            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('store.admin.membership.index', ['store_slug' => $store->slug]) }}" class="flex flex-wrap items-center gap-1.5">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search name, phone..."
                       class="px-2.5 py-1 text-xs rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">

                <select name="tier_id" class="px-2.5 py-1 text-xs rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold">
                    <option value="">All VIP Tiers</option>
                    @foreach($tiers as $t)
                        <option value="{{ $t->id }}" {{ request('tier_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="px-3 py-1 bg-violet-600 text-white rounded-md text-xs font-bold hover:bg-violet-700 transition active:scale-95 cursor-pointer shadow-2xs">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[650px]">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="p-2.5">Customer</th>
                        <th class="p-2.5">Contact</th>
                        <th class="p-2.5">VIP Membership Tier</th>
                        <th class="p-2.5 text-right">{{ __('messages.membership_current_points') }}</th>
                        <th class="p-2.5 text-right">{{ __('messages.membership_total_spend') }}</th>
                        <th class="p-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
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
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            {{-- Customer Name --}}
                            <td class="p-2.5">
                                <div class="font-bold text-slate-900 dark:text-slate-100">{{ $m->name }}</div>
                                <span class="text-[10px] text-slate-400">{{ ucfirst($m->customer_role ?? 'Customer') }}</span>
                            </td>

                            {{-- Contact --}}
                            <td class="p-2.5 font-mono text-slate-600 dark:text-slate-300">
                                {{ $m->phone ?? $m->email ?? '-' }}
                            </td>

                            {{-- Current Tier Badge --}}
                            <td class="p-2.5">
                                @if($m->tier_name)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black font-mono {{ $tierColor }}">
                                        {{ $m->tier_name }}
                                        @if($m->tier_discount > 0)
                                            <span class="text-[9px] opacity-80">({{ $m->tier_discount }}% off)</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px]">Standard Member</span>
                                @endif
                            </td>

                            {{-- Points Balance --}}
                            <td class="p-2.5 text-right">
                                <span class="font-mono text-xs font-black text-amber-600 dark:text-amber-400">
                                    {{ number_format($m->loyalty_points) }}
                                </span>
                                <span class="text-[10px] text-slate-400 ml-0.5">Pts</span>
                            </td>

                            {{-- Lifetime Spend --}}
                            <td class="p-2.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format($m->total_spent) }} Ks
                            </td>

                            {{-- Quick Actions --}}
                            <td class="p-2.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Adjust Points --}}
                                    <button type="button"
                                            @click="openAdjustPoints({{ $m->id }}, '{{ addslashes($m->name) }}', {{ $m->loyalty_points }})"
                                            class="px-2 py-1 rounded-md text-[11px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 hover:bg-amber-100 transition cursor-pointer">
                                        {{ __('messages.membership_adjust_points') }}
                                    </button>

                                    {{-- Change Tier --}}
                                    <button type="button"
                                            @click="openAssignTier({{ $m->id }}, '{{ addslashes($m->name) }}', {{ $m->membership_tier_id ?? 'null' }})"
                                            class="px-2 py-1 rounded-md text-[11px] font-bold bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60 hover:bg-violet-100 transition cursor-pointer">
                                        {{ __('messages.membership_assign_tier') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                                No registered customer members found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
            <div class="p-2.5 border-t border-slate-100 dark:border-slate-800">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    {{-- ============================================================
         MODAL 1: ADD TIER
         ============================================================ --}}
    <div x-show="showAddTierModal"
         x-cloak
         @click.self="showAddTierModal = false"
         @keydown.escape.window="showAddTierModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl space-y-3.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>👑</span>
                    <span>{{ __('messages.membership_add_tier') }}</span>
                </h3>
                <button type="button" @click="showAddTierModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.membership.tiers.store', ['store_slug' => $store->slug]) }}" class="space-y-3 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_tier_name') }} *</label>
                        <input type="text" name="name" required placeholder="e.g. Diamond VIP" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_tier_code') }} *</label>
                        <input type="text" name="code" required placeholder="e.g. DIAMOND" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_min_spending') }} *</label>
                    <input type="number" step="1000" min="0" name="min_spending" required placeholder="e.g. 5000000" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_discount_percent') }} *</label>
                        <input type="number" step="0.5" min="0" max="100" name="discount_percent" required placeholder="e.g. 15" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_point_multiplier') }} *</label>
                        <input type="number" step="0.1" min="0.1" max="10" name="point_multiplier" required value="2.0" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_badge_color') }}</label>
                    <select name="badge_color" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="slate">Slate (Default)</option>
                        <option value="blue">Blue (Silver Theme)</option>
                        <option value="amber">Amber (Gold Theme)</option>
                        <option value="purple">Purple (Platinum Theme)</option>
                        <option value="emerald">Emerald Green</option>
                        <option value="rose">Rose Red</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAddTierModal = false" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold shadow-2xs active:scale-95 cursor-pointer">Create Tier</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 2: EDIT TIER
         ============================================================ --}}
    <div x-show="showEditTierModal"
         x-cloak
         @click.self="showEditTierModal = false"
         @keydown.escape.window="showEditTierModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl space-y-3.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>✏️</span>
                    <span>Edit Tier: <span x-text="editingTier.name" class="text-violet-600 dark:text-violet-400"></span></span>
                </h3>
                <button type="button" @click="showEditTierModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form method="POST"
                  :action="'{{ url('/store/' . $store->slug . '/admin/membership/tiers') }}/' + editingTier.id"
                  class="space-y-3 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_tier_name') }} *</label>
                        <input type="text" name="name" x-model="editingTier.name" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_tier_code') }} *</label>
                        <input type="text" name="code" x-model="editingTier.code" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_min_spending') }} *</label>
                    <input type="number" step="1000" min="0" name="min_spending" x-model="editingTier.min_spending" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_discount_percent') }} *</label>
                        <input type="number" step="0.5" min="0" max="100" name="discount_percent" x-model="editingTier.discount_percent" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_point_multiplier') }} *</label>
                        <input type="number" step="0.1" min="0.1" max="10" name="point_multiplier" x-model="editingTier.point_multiplier" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.membership_badge_color') }}</label>
                    <select name="badge_color" x-model="editingTier.badge_color" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="slate">Slate (Default)</option>
                        <option value="blue">Blue (Silver Theme)</option>
                        <option value="amber">Amber (Gold Theme)</option>
                        <option value="purple">Purple (Platinum Theme)</option>
                        <option value="emerald">Emerald Green</option>
                        <option value="rose">Rose Red</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" value="1" :checked="editingTier.is_active == 1" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Active VIP Tier</span>
                </div>

                <div class="flex justify-end gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditTierModal = false" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold shadow-2xs active:scale-95 cursor-pointer">Update Tier</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 3: ADJUST LOYALTY POINTS
         ============================================================ --}}
    <div x-show="showAdjustPointsModal"
         x-cloak
         @click.self="showAdjustPointsModal = false"
         @keydown.escape.window="showAdjustPointsModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl space-y-3.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>🪙</span>
                    <span>Adjust Points: <span x-text="adjustTarget.customer_name" class="text-amber-600 dark:text-amber-400"></span></span>
                </h3>
                <button type="button" @click="showAdjustPointsModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.membership.adjust_points', ['store_slug' => $store->slug]) }}" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="customer_id" :value="adjustTarget.customer_id">

                <div class="p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 rounded-lg flex justify-between items-center">
                    <span class="text-slate-600 dark:text-slate-400">Current Points Balance:</span>
                    <strong class="font-mono text-sm font-black text-amber-600 dark:text-amber-400" x-text="Number(adjustTarget.current_points).toLocaleString() + ' Pts'"></strong>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Adjustment Type *</label>
                        <select name="type" x-model="adjustTarget.type" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <option value="bonus">Grant Bonus (+)</option>
                            <option value="adjusted">Correction Adjustment (+/-)</option>
                            <option value="redeemed">Manual Redeem (-)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Points Amount *</label>
                        <input type="number" name="points" x-model="adjustTarget.points" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Reason / Notes</label>
                    <input type="text" name="notes" x-model="adjustTarget.notes" placeholder="e.g. Birthday reward, store promotion" class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>

                <div class="flex justify-end gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAdjustPointsModal = false" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold shadow-2xs active:scale-95 cursor-pointer">Apply Points</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================
         MODAL 4: ASSIGN MEMBERSHIP TIER
         ============================================================ --}}
    <div x-show="showAssignTierModal"
         x-cloak
         @click.self="showAssignTierModal = false"
         @keydown.escape.window="showAssignTierModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-2xl space-y-3.5 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-slate-100 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                    <span>🏆</span>
                    <span>Change VIP Tier: <span x-text="assignTarget.customer_name" class="text-violet-600 dark:text-violet-400"></span></span>
                </h3>
                <button type="button" @click="showAssignTierModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.membership.assign_tier', ['store_slug' => $store->slug]) }}" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="customer_id" :value="assignTarget.customer_id">

                <div>
                    <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Select Membership Tier *</label>
                    <select name="tier_id" x-model="assignTarget.new_tier_id" required class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach($tiers as $t)
                            <option value="{{ $t->id }}">
                                {{ $t->name }} ({{ $t->code }}) — {{ $t->discount_percent }}% Off
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showAssignTierModal = false" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold shadow-2xs active:scale-95 cursor-pointer">Assign Tier</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
