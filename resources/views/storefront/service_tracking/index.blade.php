@extends('layouts.storefront.app', ['title' => __('messages.track_service_title')])

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $queryParam = $storeSlug ? '?store_slug=' . $storeSlug : '';
    $formAction = $store ? url('/store/' . $store->slug . '/track/service') : url('/service-tracking');

    $statusColors = [
        'received'          => 'bg-blue-100 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        'diagnosing'        => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
        'awaiting_approval' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        'awaiting_parts'    => 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-200 dark:border-purple-800',
        'in_repair'         => 'bg-orange-100 text-orange-800 dark:bg-orange-950/80 dark:text-orange-300 border-orange-200 dark:border-orange-800',
        'ready'             => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        'delivered'         => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700',
        'cancelled'         => 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border-rose-200 dark:border-rose-800',
        'unrepairable'      => 'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300 border-rose-200 dark:border-rose-800',
    ];
@endphp

<div class="space-y-6 sm:space-y-8 max-w-5xl mx-auto">

    {{-- Page Header (Glass Finder & Storefront matching style) --}}
    <div class="text-center max-w-2xl mx-auto space-y-2.5 pt-2 sm:pt-4">
        <div class="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-teal-500/10 text-teal-700 dark:text-teal-300 text-xs font-extrabold border border-teal-400/30">
            <span>🔧 {{ $store->name ?? config('app.name') }}</span>
            <span>·</span>
            <span>{{ __('messages.nav_service_track') }}</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white font-outfit tracking-tight">
            {{ __('messages.track_service_title') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
            {{ __('messages.track_service_subtitle') }}
        </p>
    </div>

    {{-- Search & Lookup Toolbar Card (Matches Glass Finder toolbar container) --}}
    <div
        x-data="{
            searchQuery: '{{ addslashes($query) }}',
            setQuery(val) {
                this.searchQuery = val;
                this.$nextTick(() => { this.$refs.trackForm.submit(); });
            }
        }"
        class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-6 lg:p-7 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4"
    >
        <form method="GET" action="{{ $formAction }}" class="w-full" x-ref="trackForm">
            @if(request('store_slug'))
                <input type="hidden" name="store_slug" value="{{ request('store_slug') }}" />
            @endif

            <div class="space-y-3">
                {{-- Input Row --}}
                <div class="flex flex-col sm:flex-row items-stretch gap-2 sm:gap-2.5">
                    <div class="relative flex-1 min-w-0">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="q"
                            x-model="searchQuery"
                            required
                            placeholder="{{ __('messages.track_service_search_placeholder') }}"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 pl-10 pr-4 py-3 sm:py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-teal-500 shadow-sm transition"
                        />
                    </div>

                    <button
                        type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-teal-600 via-emerald-500 to-teal-500 hover:from-teal-500 hover:to-emerald-400 text-white rounded-xl font-extrabold text-sm shadow-md shadow-teal-500/20 flex items-center justify-center space-x-2 transition active:scale-95 cursor-pointer shrink-0"
                    >
                        <span>🔍</span>
                        <span>{{ __('messages.track_service_btn') }}</span>
                    </button>
                </div>

                {{-- Quick Helper Tags --}}
                <div class="flex flex-wrap items-center gap-1.5 pt-1 text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                    <span class="font-bold text-slate-600 dark:text-slate-300">💡 ရှာဖွေနိုင်သည့် ပုံစံများ:</span>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-mono text-slate-700 dark:text-slate-300 text-[11px] font-bold">Voucher No (ဥပမာ- V-1024)</span>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-mono text-slate-700 dark:text-slate-300 text-[11px] font-bold">Job # (SVC-...)</span>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-mono text-slate-700 dark:text-slate-300 text-[11px] font-bold">ဖုန်းနံပါတ် (09...)</span>
                </div>
            </div>
        </form>
    </div>

    {{-- Search Results Section (If searched) --}}
    @if ($searched)
        @if ($results->isNotEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-6 lg:p-7 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 dark:border-slate-800/60 pb-3">
                    <div>
                        <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white font-outfit">
                            {{ __('messages.track_service_multiple_found') }}
                        </h2>
                        <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                            သင်ကြည့်ရှုလိုသော စက်ပစ္စည်းအချက်အလက်ကို နှိပ်၍ အသေးစိတ်ကြည့်ရှုနိုင်ပါသည်
                        </p>
                    </div>
                    <span class="self-start sm:self-auto text-xs font-extrabold text-teal-700 dark:text-teal-300 bg-teal-100 dark:bg-teal-950/80 px-3 py-1 rounded-full border border-teal-300 dark:border-teal-800">
                        {{ $results->count() }} ခု တွေ့ရှိပါသည်
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-1">
                    @foreach ($results as $job)
                        @php
                            $targetSlug = $job->store?->slug ?? ($store?->slug ?? 'default');
                            $trackUrl = route('storefront.service.track.token', ['store_slug' => $targetSlug, 'token' => $job->tracking_token]);
                            $devTitle = trim(($job->category ?? $job->device_type ?? 'Device') . ' ' . ($job->brand ? '· ' . $job->brand : '') . ' ' . ($job->model ? '· ' . $job->model : ''));
                        @endphp
                        <a href="{{ $trackUrl }}"
                           class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/80 hover:border-teal-400 dark:hover:border-teal-600 hover:bg-white dark:hover:bg-slate-800 shadow-sm hover:shadow-md transition-all group flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs font-mono font-black px-2.5 py-0.5 rounded-lg bg-white dark:bg-slate-900 text-teal-700 dark:text-teal-300 border border-slate-200 dark:border-slate-700 shadow-xs">
                                            {{ $job->voucher_no ?? $job->job_number }}
                                        </span>
                                        @if ($job->voucher_no)
                                            <span class="text-[10px] font-mono text-slate-400">({{ $job->job_number }})</span>
                                        @endif
                                    </div>
                                    <span class="px-2.5 py-0.5 text-[11px] font-bold rounded-full border {{ $statusColors[$job->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ __('messages.repair_status_' . $job->status) }}
                                    </span>
                                </div>

                                <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition">
                                    {{ $devTitle }}
                                </h3>

                                <div class="mt-2 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex justify-between">
                                        <span>ပိုင်ရှင်:</span>
                                        <span class="font-bold text-slate-700 dark:text-slate-200">{{ $job->contact_name ?: ($job->customer?->name ?? '—') }}</span>
                                    </div>
                                    @if ($job->reported_problem)
                                        <div class="flex justify-between">
                                            <span>ချွတ်ယွင်းချက်:</span>
                                            <span class="font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[180px]">{{ $job->reported_problem }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-2.5 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-xs font-bold text-teal-600 dark:text-teal-400">
                                <span class="text-[11px] text-slate-400 font-normal">{{ $job->created_at->format('d M Y, h:i A') }}</span>
                                <span class="flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                                    အခြေအနေကြည့်ရန် →
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Not Found State (Storefront styled) --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 sm:p-12 text-center border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-500 flex items-center justify-center text-2xl mx-auto shadow-inner">
                    🔍
                </div>
                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white font-myanmar">
                    ရှာဖွေမှု ရလဒ် မတွေ့ရှိပါ
                </h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-myanmar max-w-md mx-auto leading-relaxed">
                    {{ __('messages.track_service_job_not_found') }}
                </p>
            </div>
        @endif
    @endif

    {{-- How It Works / Feature Cards (Matches How to Order 3-card grid) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-2.5">
            <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-lg font-bold shadow-xs">
                ⚡
            </div>
            <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white font-myanmar">တိုက်ရိုက်စစ်ဆေးခြင်း</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
                ဆိုင်သို့ စက်အပ်နှံစဉ် ရရှိထားသော Voucher နံပါတ် သို့မဟုတ် ဖုန်းနံပါတ်ဖြင့် အချိန်မရွေး ပြုပြင်မှုအဆင့်ဆင့်ကို တိုက်ရိုက်စစ်ဆေးနိုင်ပါသည်။
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-2.5">
            <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-lg font-bold shadow-xs">
                📱
            </div>
            <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white font-myanmar">ကျသင့်ငွေနှင့် အပိုပစ္စည်းများ</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
                ကျသင့်ငွေ ခန့်မှန်းခြေ၊ ပေးချေပြီးငွေနှင့် လဲလှယ်တပ်ဆင်ထားသော အပိုပစ္စည်းစာရင်းများကို ပွင့်လင်းမြင်သာစွာ ကြည့်ရှုနိုင်ပါသည်။
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-2.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg font-bold shadow-xs">
                💬
            </div>
            <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white font-myanmar">တိုက်ရိုက်မေးမြန်းနိုင်ခြင်း</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
                ပြုပြင်မှုနှင့်ပတ်သက်၍ မရှင်းလင်းသည်များရှိပါက Viber သို့မဟုတ် Telegram မှတစ်ဆင့် ဆိုင်နှင့် ချက်ချင်း စကားပြောဆိုနိုင်ပါသည်။
            </p>
        </div>
    </div>

</div>
@endsection
