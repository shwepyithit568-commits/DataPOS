@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $query = $storeSlug ? '?store_slug=' . $storeSlug : '';
    $user = auth()->user();
    $setting = $setting ?? ($store?->setting ?? null);

    $viberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number);
    $viberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number);
    $telegramUrl = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);
    $callNumber = $setting?->phone ? \App\Support\ContactLinkBuilder::normalizeMyanmarPhone($setting->phone) : null;
@endphp

<div class="mx-auto max-w-4xl space-y-6 sm:space-y-8 pb-16">
    {{-- ===================== Hero Header ===================== --}}
    <header class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
        <div class="space-y-2">
            <div class="sf-btn-3d active inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase pointer-events-none">
                <span>💼</span>
                <span>Wholesale Partner Program · {{ __('messages.wholesale_apply_title') }}</span>
            </div>

            <h1 class="font-sans text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 dark:text-white leading-tight">
                {{ __('messages.wholesale_apply_title') }}
            </h1>

            <p class="max-w-2xl text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-300 font-myanmar">
                {{ __('messages.wholesale_apply_subtitle') }}
            </p>
        </div>

        {{-- Benefits Highlights Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <div class="p-3.5 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-1 shadow-2xs">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 flex items-center justify-center text-sm font-bold">🏷️</span>
                    <span class="text-xs font-black text-slate-900 dark:text-white">{{ __('messages.wholesale_benefit_1_title') }}</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                    {{ __('messages.wholesale_benefit_1_desc') }}
                </p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-1 shadow-2xs">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-sky-100 dark:bg-sky-950/80 text-sky-600 dark:text-sky-400 flex items-center justify-center text-sm font-bold">🚌</span>
                    <span class="text-xs font-black text-slate-900 dark:text-white">{{ __('messages.wholesale_benefit_2_title') }}</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                    {{ __('messages.wholesale_benefit_2_desc') }}
                </p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 space-y-1 shadow-2xs">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-bold">💬</span>
                    <span class="text-xs font-black text-slate-900 dark:text-white">{{ __('messages.wholesale_benefit_3_title') }}</span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                    {{ __('messages.wholesale_benefit_3_desc') }}
                </p>
            </div>
        </div>
    </header>

    {{-- ===================== Flash Success & Error Messages ===================== --}}
    @if (session('success'))
        <div class="p-4 sm:p-4.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 font-myanmar flex items-center gap-2.5 shadow-2xs">
            <span class="text-lg">✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 sm:p-4.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs sm:text-sm text-rose-800 dark:text-rose-300 font-myanmar flex items-center gap-2.5 shadow-2xs">
            <span class="text-lg">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ===================== Application Status Box (If already applied) ===================== --}}
    @if ($application)
        @php
            $status = $application->status;
        @endphp
        <div class="rounded-3xl p-5 sm:p-6 border shadow-sm space-y-3
            {{ $status === 'approved' ? 'bg-emerald-50/70 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/80' : '' }}
            {{ $status === 'pending'  ? 'bg-amber-50/70 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/80' : '' }}
            {{ $status === 'rejected' ? 'bg-rose-50/70 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800/80' : '' }}
            {{ $status === 'suspended'? 'bg-slate-50/70 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700' : '' }}">
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                <div class="flex items-center gap-2.5">
                    @if ($status === 'approved')
                        <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm font-bold shadow-2xs">✓</span>
                    @elseif ($status === 'pending')
                        <span class="relative flex h-3.5 w-3.5 ml-1">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-500"></span>
                        </span>
                    @elseif ($status === 'rejected')
                        <span class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center text-sm font-bold shadow-2xs">✕</span>
                    @else
                        <span class="w-8 h-8 rounded-xl bg-slate-600 text-white flex items-center justify-center text-sm font-bold shadow-2xs">🔒</span>
                    @endif

                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-bold block uppercase tracking-wider">{{ __('messages.wholesale_status_label') }}</span>
                        <span class="text-sm font-black
                            {{ $status === 'approved' ? 'text-emerald-900 dark:text-emerald-200' : '' }}
                            {{ $status === 'pending'  ? 'text-amber-900 dark:text-amber-200' : '' }}
                            {{ $status === 'rejected' ? 'text-rose-900 dark:text-rose-200' : '' }}
                            {{ $status === 'suspended'? 'text-slate-800 dark:text-slate-300' : '' }}">
                            @if ($status === 'approved')
                                🌟 {{ __('messages.wholesale_status_approved') }}
                            @elseif ($status === 'pending')
                                ⏳ {{ __('messages.wholesale_status_pending') }}
                            @elseif ($status === 'rejected')
                                ❌ {{ __('messages.wholesale_status_rejected') }}
                            @else
                                🔒 {{ __('messages.wholesale_status_suspended') }}
                            @endif
                        </span>
                    </div>
                </div>

                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                    {{ __('messages.wholesale_applied_date') }}: {{ $application->created_at->format('M d, Y') }}
                </div>
            </div>

            <p class="text-xs leading-relaxed font-myanmar
                {{ $status === 'approved' ? 'text-emerald-800 dark:text-emerald-300/90' : '' }}
                {{ $status === 'pending'  ? 'text-amber-800 dark:text-amber-300/90' : '' }}
                {{ $status === 'rejected' ? 'text-rose-800 dark:text-rose-300/90' : '' }}
                {{ $status === 'suspended'? 'text-slate-700 dark:text-slate-300' : '' }}">
                @if ($status === 'approved')
                    {{ __('messages.wholesale_approved_hint') }}
                @elseif ($status === 'pending')
                    {{ __('messages.wholesale_pending_hint') }}
                @elseif ($status === 'rejected')
                    {{ __('messages.wholesale_rejected_hint') }}
                @endif
            </p>

            @if ($application->admin_note)
                <div class="p-3 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 font-myanmar space-y-1">
                    <span class="font-bold text-slate-900 dark:text-white">Admin Note:</span>
                    <p class="leading-relaxed">{{ $application->admin_note }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ===================== Guest Notice (If not logged in) ===================== --}}
    @if (!$user)
        <div class="rounded-3xl bg-white dark:bg-slate-900 p-6 sm:p-8 border border-slate-200/90 dark:border-slate-800 shadow-sm text-center space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-sky-100 dark:bg-sky-950/80 text-sky-600 dark:text-sky-400 flex items-center justify-center text-2xl mx-auto shadow-2xs">
                🔐
            </div>
            <div class="space-y-1 max-w-md mx-auto">
                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                    အကောင့်ဝင်ရောက်ပြီးမှ လျှောက်ထားနိုင်ပါသည်
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">
                    {{ __('messages.wholesale_login_required') }}
                </p>
            </div>
            <div class="pt-2">
                <a href="{{ route('customer.login', ['store_slug' => $store->slug]) }}"
                   class="sf-btn-3d-primary inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-xs font-black cursor-pointer select-none">
                    <span>🔑</span>
                    <span>အကောင့်ဝင်မည် (Customer Login)</span>
                </a>
            </div>
        </div>
    @else
        {{-- ===================== Wholesale Application Form ===================== --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 p-6 sm:p-8 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-6">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 flex items-center justify-center text-sm font-bold shadow-2xs">📝</span>
                    <div>
                        <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-white font-sans">
                            {{ $application ? __('messages.wholesale_update_btn') : __('messages.wholesale_submit_btn') }}
                        </h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar">
                            လုပ်ငန်းနှင့် ဆိုင်အချက်အလက်များကို မှန်ကန်စွာ ဖြည့်သွင်းပေးပါရန်
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ url('/store/' . ($store?->slug ?? 'default') . '/wholesale/apply') }}" class="space-y-4 sm:space-y-5">
                @csrf

                {{-- Business Name --}}
                <div class="space-y-1.5 font-myanmar">
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300">
                        {{ __('messages.wholesale_business_name') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="business_name"
                           value="{{ old('business_name', $application->business_name ?? '') }}"
                           required
                           placeholder="ဥပမာ- ရွှေပြည်သစ် မိုဘိုင်းနှင့် ဆက်စပ်ပစ္စည်းအရောင်းဆိုင်"
                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white focus:border-sky-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none transition shadow-2xs" />
                    @error('business_name')
                        <p class="text-[11px] text-rose-500 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Phone Number --}}
                <div class="space-y-1.5 font-myanmar">
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300">
                        {{ __('messages.wholesale_contact_phone') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $application->phone ?? $user->phone ?? '') }}"
                           required
                           placeholder="09..."
                           class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60 px-3.5 py-2.5 text-xs sm:text-sm font-mono text-slate-900 dark:text-white focus:border-sky-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none transition shadow-2xs" />
                    @error('phone')
                        <p class="text-[11px] text-rose-500 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Address --}}
                <div class="space-y-1.5 font-myanmar">
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300">
                        {{ __('messages.wholesale_business_address') }}
                    </label>
                    <textarea name="address" rows="3"
                              placeholder="ဆိုင်လိပ်စာ၊ လမ်းအမည်၊ မြို့နယ်နှင့် တိုင်းဒေသကြီး"
                              class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white focus:border-sky-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none transition shadow-2xs">{{ old('address', $application->address ?? '') }}</textarea>
                    @error('address')
                        <p class="text-[11px] text-rose-500 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Business Type / Products Note --}}
                <div class="space-y-1.5 font-myanmar">
                    <label class="block text-xs font-black text-slate-700 dark:text-slate-300">
                        ရောင်းချသော ပစ္စည်းအမျိုးအစား သို့မဟုတ် လိုအပ်သော ပစ္စည်းများ (မှတ်စု)
                    </label>
                    <textarea name="notes" rows="2"
                              placeholder="ဥပမာ- ဖုန်းအပိုပစ္စည်း လက်လီဆိုင်၊ Touch LCD နှင့် မှန်မကွဲ အဓိက မှာယူလိုပါသည်"
                              class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/60 px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white focus:border-sky-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none transition shadow-2xs">{{ old('notes', $application->notes ?? '') }}</textarea>
                    @error('notes')
                        <p class="text-[11px] text-rose-500 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit 3D CTA Button --}}
                <div class="pt-2">
                    <button type="submit"
                            class="sf-btn-3d-primary w-full min-h-[46px] sm:min-h-[50px] inline-flex items-center justify-center gap-2 rounded-2xl text-xs sm:text-sm font-black cursor-pointer select-none">
                        <span>📝</span>
                        <span>{{ $application ? __('messages.wholesale_update_btn') : __('messages.wholesale_submit_btn') }}</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ===================== Direct Partner Contact Card ===================== --}}
    <section class="rounded-3xl bg-slate-50/90 dark:bg-slate-900/60 p-6 sm:p-7 border border-slate-200/90 dark:border-slate-800 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="space-y-1">
                <h3 class="text-sm font-black text-slate-900 dark:text-white font-myanmar">
                    လက်ကား အသေးစိတ် မေးမြန်းလိုပါက
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                    ပစ္စည်းလက်ကျန်၊ အထူးဈေးနှုန်းနှင့် အော်ဒါအသေးစိတ်များကို အောက်ပါ လိုင်းများမှတစ်ဆင့် ဆက်သွယ်နိုင်ပါသည်
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-1 sm:pt-0">
                @if ($viberUrl)
                    <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl }}"
                       class="sf-btn-3d-viber inline-flex min-h-[38px] items-center gap-2 rounded-xl px-4 py-2 text-xs font-black cursor-pointer select-none">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-white/20">
                            <x-brand-icon brand="viber" class="h-3 w-3 fill-white text-white"/>
                        </span>
                        <span>Viber Chat မေးမြန်းမည်</span>
                    </a>
                @endif
                @if ($callNumber)
                    <a href="tel:{{ $callNumber }}"
                       class="sf-btn-3d-success inline-flex min-h-[38px] items-center gap-2 rounded-xl px-4 py-2 text-xs font-black cursor-pointer select-none">
                        <span>📞</span>
                        <span>ဖုန်းတိုက်ရိုက်ခေါ်မည်</span>
                    </a>
                @endif
                @if ($telegramUrl)
                    <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer"
                       class="sf-btn-3d-telegram inline-flex min-h-[38px] items-center gap-2 rounded-xl px-4 py-2 text-xs font-black cursor-pointer select-none">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-white/20">
                            <x-brand-icon brand="telegram" class="h-3 w-3 fill-white text-white"/>
                        </span>
                        <span>Telegram Chat မေးမြန်းမည်</span>
                    </a>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection

