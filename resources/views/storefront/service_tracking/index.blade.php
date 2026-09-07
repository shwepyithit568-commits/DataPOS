@extends('layouts.storefront.app', ['title' => __('messages.track_service_title')])

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $queryParam = $storeSlug ? '?store_slug=' . $storeSlug : '';
    $formAction = $store ? url('/store/' . $store->slug . '/track/service') : url('/service-tracking');

    $statusColors = [
        'received'          => 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        'diagnosing'        => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
        'awaiting_approval' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'awaiting_parts'    => 'bg-purple-50 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300 border-purple-200 dark:border-purple-800',
        'in_repair'         => 'bg-orange-50 text-orange-700 dark:bg-orange-950/70 dark:text-orange-300 border-orange-200 dark:border-orange-800',
        'ready'             => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'delivered'         => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        'cancelled'         => 'bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        'unrepairable'      => 'bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border-rose-200 dark:border-rose-800',
    ];
@endphp

<div class="space-y-2 sm:space-y-3 max-w-5xl mx-auto pb-16 select-none font-sans">

    {{-- Page Header (Storefront Standard) --}}
    <div class="text-center max-w-2xl mx-auto space-y-1.5 pt-1 sm:pt-2">
        <div class="sf-btn-3d active inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-black pointer-events-none">
            <span>🔧 {{ $store->name ?? config('app.name') }}</span>
            <span>·</span>
            <span>{{ __('messages.nav_service_track') }}</span>
        </div>
        <h1 class="text-xl sm:text-3xl font-black text-slate-900 dark:text-white font-outfit tracking-tight">
            {{ __('messages.track_service_title') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
            {{ __('messages.track_service_subtitle') }}
        </p>
    </div>

    {{-- Search & Lookup Toolbar Card --}}
    <div
        x-data="{
            searchQuery: '{{ addslashes($query) }}',
            fillAndSubmit(val) {
                this.searchQuery = val;
                this.$nextTick(() => { this.$refs.trackForm.submit(); });
            }
        }"
        class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2.5"
    >
        <form method="GET" action="{{ $formAction }}" class="w-full" x-ref="trackForm">
            @if(request('store_slug'))
                <input type="hidden" name="store_slug" value="{{ request('store_slug') }}" />
            @endif

            <div class="space-y-2.5">
                {{-- Input Row --}}
                <div class="flex flex-col sm:flex-row items-stretch gap-1.5 sm:gap-2">
                    <div class="relative flex-1 min-w-0">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="q"
                            x-model="searchQuery"
                            required
                            placeholder="{{ __('messages.track_service_search_placeholder') }}"
                            class="w-full h-10 sm:h-11 rounded-md border border-slate-300 dark:border-slate-700 pl-9 pr-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs sm:text-sm font-bold focus:border-teal-500 focus:ring-1 focus:ring-teal-500 shadow-2xs transition"
                        />
                    </div>

                    <button
                        type="submit"
                        class="sf-btn-3d-success h-10 sm:h-11 px-5 flex items-center justify-center gap-1.5 cursor-pointer shrink-0 text-xs sm:text-sm font-black rounded-md"
                    >
                        <span>🔍</span>
                        <span>{{ __('messages.track_service_btn') }}</span>
                    </button>
                </div>

                {{-- Quick Helper Tags --}}
                <div class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                    <span class="font-bold text-slate-600 dark:text-slate-300">💡 {{ __('messages.track_service_search_help') }}:</span>
                    <button type="button" @click="fillAndSubmit('V-9801')"
                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 font-mono text-slate-700 dark:text-slate-300 text-[11px] font-bold transition border border-slate-200 dark:border-slate-700">
                        Voucher No (V-...)
                    </button>
                    <button type="button" @click="fillAndSubmit('SVC-')"
                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 font-mono text-slate-700 dark:text-slate-300 text-[11px] font-bold transition border border-slate-200 dark:border-slate-700">
                        Job # (SVC-...)
                    </button>
                    <button type="button" @click="fillAndSubmit('09')"
                            class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 font-mono text-slate-700 dark:text-slate-300 text-[11px] font-bold transition border border-slate-200 dark:border-slate-700">
                        {{ __('messages.phone_number') }} (09...)
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Search Results Section (If searched) --}}
    @if ($searched)
        @if ($results->isNotEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3.5 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-2.5">
                    <div>
                        <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-myanmar">
                            {{ __('messages.track_service_multiple_found') }}
                        </h2>
                    </div>
                    <span class="self-start sm:self-auto text-xs font-black text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-950/60 px-2.5 py-0.5 rounded-md border border-teal-200 dark:border-teal-800 font-mono">
                        {{ $results->count() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-2.5 pt-1">
                    @foreach ($results as $job)
                        @php
                            $targetSlug = $job->store?->slug ?? ($store?->slug ?? 'default');
                            $trackUrl = route('storefront.service.track.token', ['store_slug' => $targetSlug, 'token' => $job->tracking_token]);
                            $devTitle = trim(($job->category ?? $job->device_type ?? 'Device') . ' ' . ($job->brand ? '· ' . $job->brand : '') . ' ' . ($job->model ? '· ' . $job->model : ''));
                        @endphp
                        <a href="{{ $trackUrl }}"
                           class="p-3 sm:p-4 rounded-md sm:rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 hover:border-teal-400 dark:hover:border-teal-500 hover:bg-white dark:hover:bg-slate-800 shadow-2xs hover:shadow-xs transition group flex flex-col justify-between space-y-2.5">
                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-mono font-black px-2 py-0.5 rounded bg-white dark:bg-slate-900 text-teal-700 dark:text-teal-300 border border-slate-200 dark:border-slate-700 shadow-2xs">
                                            {{ $job->voucher_no ?? $job->job_number }}
                                        </span>
                                        @if ($job->voucher_no)
                                            <span class="text-[11px] font-mono text-slate-400 font-bold">({{ $job->job_number }})</span>
                                        @endif
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] sm:text-[11px] font-bold rounded border {{ $statusColors[$job->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ __('messages.repair_status_' . $job->status) }}
                                    </span>
                                </div>

                                <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition font-outfit">
                                    {{ $devTitle }}
                                </h3>

                                <div class="space-y-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex justify-between">
                                        <span>{{ __('messages.track_service_device_owner') }}:</span>
                                        <span class="font-bold text-slate-700 dark:text-slate-200">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</span>
                                    </div>
                                    @if ($job->reported_problem)
                                        <div class="flex justify-between gap-2">
                                            <span class="shrink-0">{{ __('messages.track_service_problem_label') }}:</span>
                                            <span class="font-semibold text-rose-600 dark:text-rose-400 truncate max-w-[200px] text-right">{{ $job->reported_problem }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-xs font-bold text-teal-600 dark:text-teal-400">
                                <span class="text-[11px] text-slate-400 font-mono">{{ $job->created_at->format('d M Y, h:i A') }}</span>
                                <span class="flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                                    {{ __('messages.track_service_view_details') }} →
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Not Found State --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-6 sm:p-8 text-center border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-2">
                <div class="w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-950/60 text-amber-500 flex items-center justify-center text-xl mx-auto shadow-inner">
                    🔍
                </div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-myanmar">
                    {{ __('messages.track_service_job_not_found') }}
                </h3>
            </div>
        @endif
    @endif

    {{-- How It Works / Feature Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-3">
        <div class="rounded-lg sm:rounded-xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-2">
            <div class="w-8 h-8 rounded-md bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-base font-bold shadow-2xs">
                ⚡
            </div>
            <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">{{ __('messages.track_service_title') }}</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
                {{ __('messages.track_service_subtitle') }}
            </p>
        </div>

        <div class="rounded-lg sm:rounded-xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-2">
            <div class="w-8 h-8 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-base font-bold shadow-2xs">
                📱
            </div>
            <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">{{ __('messages.track_service_cost_breakdown') }}</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
                {{ __('messages.track_service_technician_notes') }}
            </p>
        </div>

        <div class="rounded-lg sm:rounded-xl border border-slate-200/80 bg-white p-3.5 sm:p-4 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-2">
            <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base font-bold shadow-2xs">
                💬
            </div>
            <h3 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar">{{ __('messages.track_service_contact_shop') }}</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
                {{ __('messages.contact_shop_hint') }}
            </p>
        </div>
    </div>

</div>
@endsection
