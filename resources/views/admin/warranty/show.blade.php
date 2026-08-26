@extends('layouts.admin.app')

@section('title', __('messages.warranty_details') . ' - ' . $warranty->serial_number)
@section('main_padding', 'p-2')

@section('content')
@php
    $compStatus = $warranty->computed_status;
@endphp

<div x-data="{ claimModalOpen: false }" class="w-full max-w-5xl mx-auto space-y-2 sm:space-y-2.5">

    {{-- ============================================================
         1. COMPACT HERO PAGE HEADER
         ============================================================ --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-slate-500 mb-0.5">
                <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.admin_dashboard') }}</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.sidebar_warranty') }}</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-slate-700 dark:text-slate-200 font-bold font-mono">{{ $warranty->serial_number }}</span>
            </div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>{{ $warranty->product_name }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    SN: {{ $warranty->serial_number }}
                </span>
            </h1>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            {{-- Print Certificate --}}
            <a href="{{ route('store.admin.warranty.certificate', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}"
               target="_blank"
               class="px-2.5 py-1.5 text-xs font-bold rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 shadow-2xs transition flex items-center gap-1">
                <span>🖨️</span>
                <span>{{ __('messages.print_certificate') }}</span>
            </a>

            {{-- Record Claim Modal Button --}}
            <button type="button"
                    @click="claimModalOpen = true"
                    class="px-2.5 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-2xs transition flex items-center gap-1">
                <span>🛠️</span>
                <span>{{ __('messages.record_warranty_claim') }}</span>
            </button>

            {{-- Edit --}}
            <a href="{{ route('store.admin.warranty.edit', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}"
               class="px-2.5 py-1.5 text-xs font-bold rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition shadow-2xs">
                <span>✏️ {{ __('messages.edit') }}</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         2. HERO STATUS BANNER CARD
         ============================================================ --}}
    <div class="p-3 sm:p-4 rounded-lg border shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-4 {{ $compStatus === 'active' ? 'bg-emerald-50/60 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-900/60' : ($compStatus === 'expiring_soon' ? 'bg-amber-50/60 border-amber-200 dark:bg-amber-950/20 dark:border-amber-900/60' : 'bg-rose-50/60 border-rose-200 dark:bg-rose-950/20 dark:border-rose-900/60') }}">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                @if($compStatus === 'active')
                    <span class="px-2 py-0.5 rounded-md text-xs font-black uppercase bg-emerald-600 text-white shadow-2xs">
                        {{ __('messages.status_active') }}
                    </span>
                @elseif($compStatus === 'expiring_soon')
                    <span class="px-2 py-0.5 rounded-md text-xs font-black uppercase bg-amber-600 text-white shadow-2xs">
                        {{ __('messages.status_expiring_soon') }}
                    </span>
                @elseif($compStatus === 'expired')
                    <span class="px-2 py-0.5 rounded-md text-xs font-black uppercase bg-rose-600 text-white shadow-2xs">
                        {{ __('messages.status_expired') }}
                    </span>
                @elseif($compStatus === 'claimed')
                    <span class="px-2 py-0.5 rounded-md text-xs font-black uppercase bg-indigo-600 text-white shadow-2xs">
                        {{ __('messages.status_claimed') }}
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-md text-xs font-black uppercase bg-slate-600 text-white shadow-2xs">
                        {{ ucfirst($compStatus) }}
                    </span>
                @endif
                <span class="text-xs text-slate-500 font-bold uppercase">{{ __('messages.warranty_type_' . $warranty->warranty_type) }}</span>
            </div>
            <div class="text-xl sm:text-2xl font-black font-mono mt-1 {{ $warranty->days_remaining > 0 ? 'text-slate-900 dark:text-slate-100' : 'text-rose-600 dark:text-rose-400' }}">
                @if($warranty->days_remaining > 0)
                    {{ $warranty->days_remaining }} <span class="text-xs font-bold text-slate-500">Days Remaining (ရက်ကျန်)</span>
                @else
                    Expired {{ abs($warranty->days_remaining) }} days ago
                @endif
            </div>
        </div>

        {{-- Timeline Dates --}}
        <div class="flex items-center gap-4 text-xs bg-white dark:bg-slate-900 p-2.5 sm:p-3 rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div>
                <div class="text-slate-400 font-bold uppercase text-[10px]">{{ __('messages.purchase_date') }}</div>
                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs font-mono mt-0.5">{{ $warranty->purchase_date->format('d M Y') }}</div>
            </div>
            <div class="text-slate-300 dark:text-slate-600 font-bold">&rarr;</div>
            <div>
                <div class="text-slate-400 font-bold uppercase text-[10px]">{{ __('messages.warranty_expiry') }}</div>
                <div class="font-bold text-slate-800 dark:text-slate-200 text-xs font-mono mt-0.5">{{ $warranty->warranty_expiry_date->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. DETAILS GRID
         ============================================================ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-2.5">

        {{-- Device & Identifiers Card --}}
        <div class="p-3 sm:p-4 rounded-lg border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2.5">
            <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                {{ __('messages.device_and_serial_info') }}
            </h3>

            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs space-y-2">
                <div class="pt-1.5 flex justify-between">
                    <span class="text-slate-500">{{ __('messages.product_name') }}:</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100 text-right">{{ $warranty->product_name }}</span>
                </div>
                <div class="pt-1.5 flex justify-between">
                    <span class="text-slate-500">Serial Number (SN):</span>
                    <span class="font-mono font-bold text-violet-600 dark:text-violet-400">{{ $warranty->serial_number }}</span>
                </div>
                @if($warranty->imei_primary)
                    <div class="pt-1.5 flex justify-between">
                        <span class="text-slate-500">Primary IMEI:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $warranty->imei_primary }}</span>
                    </div>
                @endif
                @if($warranty->imei_secondary)
                    <div class="pt-1.5 flex justify-between">
                        <span class="text-slate-500">Secondary IMEI:</span>
                        <span class="font-mono text-slate-700 dark:text-slate-300">{{ $warranty->imei_secondary }}</span>
                    </div>
                @endif
                @if($warranty->invoice_number)
                    <div class="pt-1.5 flex justify-between">
                        <span class="text-slate-500">Invoice / Receipt:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">#{{ $warranty->invoice_number }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Customer & Policy Card --}}
        <div class="p-3 sm:p-4 rounded-lg border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2.5">
            <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                {{ __('messages.customer_and_terms') }}
            </h3>

            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs space-y-2">
                <div class="pt-1.5 flex justify-between">
                    <span class="text-slate-500">{{ __('messages.customer_name') }}:</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $warranty->customer_name ?: 'Walk-in Customer' }}</span>
                </div>
                <div class="pt-1.5 flex justify-between">
                    <span class="text-slate-500">{{ __('messages.phone') }}:</span>
                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $warranty->customer_phone ?: '-' }}</span>
                </div>
                <div class="pt-1.5 flex justify-between">
                    <span class="text-slate-500">{{ __('messages.warranty_duration') }}:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $warranty->warranty_duration_months }} Months</span>
                </div>
                <div class="pt-1.5 flex justify-between">
                    <span class="text-slate-500">Claim History Count:</span>
                    <span class="font-bold {{ $warranty->claim_count > 0 ? 'text-indigo-600' : 'text-slate-400' }}">{{ $warranty->claim_count }} claims</span>
                </div>
            </div>

            @if($warranty->terms_conditions)
                <div class="pt-1.5">
                    <div class="text-[11px] font-bold text-slate-400 uppercase mb-1">{{ __('messages.warranty_terms_conditions') }}:</div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/60 p-2 rounded-lg leading-relaxed">
                        {{ $warranty->terms_conditions }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
         4. SERVICE & REPAIR HISTORY
         ============================================================ --}}
    <div class="p-3 sm:p-4 rounded-lg border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                    {{ __('messages.linked_service_history') }}
                </h3>
                <p class="text-[11px] text-slate-400">Past repair tickets and service jobs logged for this Serial / IMEI</p>
            </div>
            <a href="{{ route('store.admin.repairs.create', ['store_slug' => $store->slug, 'imei_serial' => $warranty->serial_number]) }}"
               class="text-xs font-bold text-violet-600 hover:text-violet-500">
                + Create Repair Ticket
            </a>
        </div>

        @if($serviceJobs->isNotEmpty())
            <div class="overflow-x-auto rounded-lg border border-slate-200/90 dark:border-slate-800">
                <table class="w-full text-left text-xs border-collapse divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-100 dark:bg-slate-800/95 uppercase font-black text-slate-700 dark:text-slate-300 divide-x divide-slate-300 dark:divide-slate-700">
                        <tr>
                            <th class="px-3 py-2">Ticket #</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Device Problem / Issue</th>
                            <th class="px-3 py-2">Technician</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                        @foreach ($serviceJobs as $job)
                            <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-2 font-mono font-bold text-violet-600">#{{ $job->job_number }}</td>
                                <td class="px-3 py-2 font-mono text-slate-500">{{ $job->created_at ? $job->created_at->format('d/m/Y') : '-' }}</td>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-300 font-semibold">{{ $job->reported_problem ?: ($job->model ?: 'Service Repair') }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $job->technician?->name ?: 'Unassigned' }}</td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $job->status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('store.admin.repairs.show', ['store_slug' => $store->slug, 'repair' => $job->id]) }}" class="text-violet-600 hover:underline font-bold">
                                        View Job &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-xs text-slate-400 bg-slate-50/50 dark:bg-slate-800/30 rounded-lg border border-slate-200/60 dark:border-slate-800">
                No past repair tickets logged for this device's Serial / IMEI.
            </div>
        @endif
    </div>

    {{-- Claim / Notes Timeline Card --}}
    @if($warranty->notes)
        <div class="p-3 sm:p-4 rounded-lg border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs space-y-2">
            <h3 class="text-xs font-black text-slate-900 dark:text-slate-100 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                {{ __('messages.internal_notes_and_claims') }}
            </h3>
            <pre class="text-xs text-slate-700 dark:text-slate-300 font-mono whitespace-pre-wrap leading-relaxed">{{ $warranty->notes }}</pre>
        </div>
    @endif

    {{-- ============================================================
         5. CLAIM MODAL
         ============================================================ --}}
    <div x-show="claimModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div @click.away="claimModalOpen = false"
             class="bg-white dark:bg-slate-900 rounded-lg max-w-lg w-full p-4 sm:p-5 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
                <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.record_warranty_claim') }}</h3>
                <button type="button" @click="claimModalOpen = false" class="text-slate-400 hover:text-slate-600 text-base font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('store.admin.warranty.claim', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Claim Reason / Problem Description *</label>
                    <textarea name="claim_reason" rows="2" required placeholder="e.g. Speaker malfunctioning, charging port loose" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-violet-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Action / Resolution Taken</label>
                    <input type="text" name="resolution" placeholder="e.g. Replaced speaker under warranty / Sent to service center" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Update Status</label>
                    <select name="status" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-violet-500">
                        <option value="active">Active (Keep Active)</option>
                        <option value="claimed" selected>Claimed (အာမခံ လဲလှယ်/ပြင်ဆင်ပြီး)</option>
                        <option value="void">Void (အာမခံ ပျက်ပြယ်အဖြစ် သတ်မှတ်မည်)</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="claimModalOpen = false" class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-2xs">
                        {{ __('messages.save_claim') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
