@extends('layouts.admin.app')

@section('title', __('messages.warranty_details') . ' - ' . $warranty->serial_number)

@section('content')
@php
    $compStatus = $warranty->computed_status;
@endphp

<div x-data="{ claimModalOpen: false }" class="max-w-5xl mx-auto space-y-6">

    {{-- Breadcrumbs & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('store.admin.warranty.index', ['store_slug' => $store->slug]) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ __('messages.sidebar_warranty') }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold font-mono">{{ $warranty->serial_number }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 font-outfit mt-1">
                {{ $warranty->product_name }}
            </h1>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.warranty.certificate', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ __('messages.print_certificate') }}</span>
            </a>

            <button type="button"
                    @click="claimModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ __('messages.record_warranty_claim') }}</span>
            </button>

            <a href="{{ route('store.admin.warranty.edit', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}"
               class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition">
                {{ __('messages.edit') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-sm flex items-center gap-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Hero Status Banner --}}
    <div class="p-6 rounded-2xl border shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6 {{ $compStatus === 'active' ? 'bg-emerald-50/50 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-900' : ($compStatus === 'expiring_soon' ? 'bg-amber-50/50 border-amber-200 dark:bg-amber-950/20 dark:border-amber-900' : 'bg-rose-50/50 border-rose-200 dark:bg-rose-950/20 dark:border-rose-900') }}">
        <div class="space-y-1">
            <div class="flex items-center gap-2.5">
                @if($compStatus === 'active')
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-600 text-white">
                        {{ __('messages.status_active') }}
                    </span>
                @elseif($compStatus === 'expiring_soon')
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-amber-600 text-white">
                        {{ __('messages.status_expiring_soon') }}
                    </span>
                @elseif($compStatus === 'expired')
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-rose-600 text-white">
                        {{ __('messages.status_expired') }}
                    </span>
                @elseif($compStatus === 'claimed')
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-indigo-600 text-white">
                        {{ __('messages.status_claimed') }}
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-slate-600 text-white">
                        {{ ucfirst($compStatus) }}
                    </span>
                @endif
                <span class="text-xs text-slate-500 font-bold uppercase">{{ __('messages.warranty_type_' . $warranty->warranty_type) }}</span>
            </div>
            <div class="text-3xl font-black font-outfit mt-2 {{ $warranty->days_remaining > 0 ? 'text-slate-900 dark:text-slate-100' : 'text-rose-600 dark:text-rose-400' }}">
                @if($warranty->days_remaining > 0)
                    {{ $warranty->days_remaining }} <span class="text-sm font-bold text-slate-500">Days Remaining (ရက်ကျန်)</span>
                @else
                    Expired {{ abs($warranty->days_remaining) }} days ago
                @endif
            </div>
        </div>

        {{-- Timeline Dates --}}
        <div class="flex items-center gap-6 text-xs bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div>
                <div class="text-slate-400 font-semibold uppercase text-[10px]">{{ __('messages.purchase_date') }}</div>
                <div class="font-bold text-slate-800 dark:text-slate-200 text-sm font-mono mt-0.5">{{ $warranty->purchase_date->format('d M Y') }}</div>
            </div>
            <div class="text-slate-300 dark:text-slate-600 text-lg">&rarr;</div>
            <div>
                <div class="text-slate-400 font-semibold uppercase text-[10px]">{{ __('messages.warranty_expiry') }}</div>
                <div class="font-bold text-slate-800 dark:text-slate-200 text-sm font-mono mt-0.5">{{ $warranty->warranty_expiry_date->format('d M Y') }}</div>
            </div>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Device & Identifiers Card --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit border-b border-slate-100 dark:border-slate-800 pb-2">
                {{ __('messages.device_and_serial_info') }}
            </h3>

            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs space-y-2">
                <div class="pt-2 flex justify-between">
                    <span class="text-slate-500">{{ __('messages.product_name') }}:</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $warranty->product_name }}</span>
                </div>
                <div class="pt-2 flex justify-between">
                    <span class="text-slate-500">Serial Number (SN):</span>
                    <span class="font-mono font-bold text-violet-600 dark:text-violet-400">{{ $warranty->serial_number }}</span>
                </div>
                @if($warranty->imei_primary)
                    <div class="pt-2 flex justify-between">
                        <span class="text-slate-500">Primary IMEI:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $warranty->imei_primary }}</span>
                    </div>
                @endif
                @if($warranty->imei_secondary)
                    <div class="pt-2 flex justify-between">
                        <span class="text-slate-500">Secondary IMEI:</span>
                        <span class="font-mono text-slate-700 dark:text-slate-300">{{ $warranty->imei_secondary }}</span>
                    </div>
                @endif
                @if($warranty->invoice_number)
                    <div class="pt-2 flex justify-between">
                        <span class="text-slate-500">Invoice / Receipt:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">#{{ $warranty->invoice_number }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Customer & Policy Card --}}
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit border-b border-slate-100 dark:border-slate-800 pb-2">
                {{ __('messages.customer_and_terms') }}
            </h3>

            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs space-y-2">
                <div class="pt-2 flex justify-between">
                    <span class="text-slate-500">{{ __('messages.customer_name') }}:</span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ $warranty->customer_name ?: 'Walk-in Customer' }}</span>
                </div>
                <div class="pt-2 flex justify-between">
                    <span class="text-slate-500">{{ __('messages.phone') }}:</span>
                    <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $warranty->customer_phone ?: '-' }}</span>
                </div>
                <div class="pt-2 flex justify-between">
                    <span class="text-slate-500">{{ __('messages.warranty_duration') }}:</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $warranty->warranty_duration_months }} Months</span>
                </div>
                <div class="pt-2 flex justify-between">
                    <span class="text-slate-500">Claim History Count:</span>
                    <span class="font-bold {{ $warranty->claim_count > 0 ? 'text-indigo-600' : 'text-slate-400' }}">{{ $warranty->claim_count }} claims</span>
                </div>
            </div>

            @if($warranty->terms_conditions)
                <div class="pt-2">
                    <div class="text-[11px] font-bold text-slate-400 uppercase mb-1">{{ __('messages.warranty_terms_conditions') }}:</div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-lg leading-relaxed">
                        {{ $warranty->terms_conditions }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Service & Repair History (Linked with ServiceJob system) --}}
    <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit">
                    {{ __('messages.linked_service_history') }}
                </h3>
                <p class="text-xs text-slate-400">Past repair tickets and service jobs logged for this Serial / IMEI</p>
            </div>
            <a href="{{ route('store.admin.repairs.create', ['store_slug' => $store->slug, 'imei_serial' => $warranty->serial_number]) }}"
               class="text-xs font-bold text-violet-600 hover:text-violet-500">
                + Create Repair Ticket
            </a>
        </div>

        @if($serviceJobs->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 dark:bg-slate-800/60 uppercase font-semibold">
                        <tr>
                            <th class="px-4 py-2.5">Ticket #</th>
                            <th class="px-4 py-2.5">Date</th>
                            <th class="px-4 py-2.5">Device Problem / Issue</th>
                            <th class="px-4 py-2.5">Technician</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($serviceJobs as $job)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-2.5 font-mono font-bold text-violet-600">#{{ $job->job_number }}</td>
                                <td class="px-4 py-2.5 font-mono text-slate-500">{{ $job->created_at ? $job->created_at->format('d/m/Y') : '-' }}</td>
                                <td class="px-4 py-2.5 text-slate-700 dark:text-slate-300 font-semibold">{{ $job->reported_problem ?: ($job->model ?: 'Service Repair') }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $job->technician?->name ?: 'Unassigned' }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $job->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <a href="{{ route('store.admin.repairs.show', ['store_slug' => $store->slug, 'repair' => $job->id]) }}" class="text-violet-600 hover:underline">
                                        View Job &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-xs text-slate-400 bg-slate-50/50 dark:bg-slate-800/30 rounded-xl">
                No past repair tickets logged for this device's Serial / IMEI.
            </div>
        @endif
    </div>

    {{-- Claim / Notes Timeline Card --}}
    @if($warranty->notes)
        <div class="p-5 rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider font-outfit border-b border-slate-100 dark:border-slate-800 pb-2">
                {{ __('messages.internal_notes_and_claims') }}
            </h3>
            <pre class="text-xs text-slate-700 dark:text-slate-300 font-mono whitespace-pre-wrap leading-relaxed">{{ $warranty->notes }}</pre>
        </div>
    @endif

    {{-- Claim Modal --}}
    <div x-show="claimModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="claimModalOpen = false"
             class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-outfit">{{ __('messages.record_warranty_claim') }}</h3>
                <button type="button" @click="claimModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" action="{{ route('store.admin.warranty.claim', ['store_slug' => $store->slug, 'warranty' => $warranty->id]) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Claim Reason / Problem Description *</label>
                    <textarea name="claim_reason" rows="2" required placeholder="e.g. Speaker malfunctioning, charging port loose" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Action / Resolution Taken</label>
                    <input type="text" name="resolution" placeholder="e.g. Replaced speaker under warranty / Sent to service center" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Update Status</label>
                    <select name="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 font-semibold">
                        <option value="active">Active (Keep Active)</option>
                        <option value="claimed" selected>Claimed (အာမခံ လဲလှယ်/ပြင်ဆင်ပြီး)</option>
                        <option value="void">Void (အာမခံ ပျက်ပြယ်အဖြစ် သတ်မှတ်မည်)</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="claimModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 text-slate-700 dark:border-slate-700 dark:text-slate-300">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm">
                        {{ __('messages.save_claim') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
