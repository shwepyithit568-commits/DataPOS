@extends('layouts.auth.app')

@section('content')
<div
    x-data="{
        password: '',
        passwordConfirmation: '',
        showPassword: false,
        loading: false,
        get passwordStrength() {
            if (!this.password) return 0;
            let s = 0;
            if (this.password.length >= 6) s++;
            if (this.password.length >= 10) s++;
            if (/[A-Z]/.test(this.password)) s++;
            if (/[0-9]/.test(this.password)) s++;
            if (/[^A-Za-z0-9]/.test(this.password)) s++;
            return s;
        },
        get passwordsMatch() {
            return !this.passwordConfirmation || this.password === this.passwordConfirmation;
        }
    }"
    class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-7 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-5 animate-[fadeIn_0.4s_ease-out]"
>
    {{-- Header --}}
    <div class="text-center space-y-2">
        @if (!empty($setting?->storefrontLogo()) && \Illuminate\Support\Facades\Storage::disk('public')->exists($setting->storefrontLogo()))
            <img src="{{ asset('storage/' . $setting->storefrontLogo()) }}" alt="{{ $setting?->store_name ?? config('app.name') }}" class="w-full max-w-[200px] sm:max-w-[240px] h-auto mx-auto rounded-xl shadow-lg ring-4 ring-white/50 dark:ring-slate-800/50 object-contain" />
        @else
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 via-sky-500 to-violet-600 text-white text-2xl shadow-lg shadow-sky-500/30 mx-auto ring-4 ring-white/50 dark:ring-slate-800/50">
                ✨
            </div>
        @endif
        <div class="space-y-0.5">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-outfit tracking-tight">
                {{ __('messages.register_heading') }}
            </h1>
            <p class="text-sm font-bold text-slate-600 dark:text-slate-400 font-myanmar">
                {{ __('messages.register_intro') }}
            </p>
        </div>
    </div>

    {{-- Error Alert --}}
    @if ($errors->any())
        <div class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-sm text-rose-800 dark:text-rose-200 space-y-1 animate-[shake_0.4s_ease-in-out]">
            <div class="font-bold flex items-center space-x-1">
                <span>⚠️</span>
                <span>{{ __('messages.common_error') }}</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5 font-myanmar">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('register') }}" class="space-y-3" @submit="loading = true">
        @csrf

        {{-- Full Name --}}
        <div class="space-y-1">
            <label for="name" class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                {{ __('messages.full_name') }} <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-base">
                    👤
                </span>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    placeholder="ဦး..."
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 pl-10 pr-3.5 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm outline-none transition"
                />
            </div>
        </div>

        {{-- Phone --}}
        <div class="space-y-1">
            <label for="phone" class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                {{ __('messages.phone_number') }} <span class="text-rose-500">*</span>
            </label>
            <div class="relative flex items-stretch">
                <span class="inline-flex items-center gap-1 pl-3.5 pr-2 rounded-l-xl border border-r-0 border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-sm font-bold">
                    🇲🇲 <span class="font-mono">+95</span>
                </span>
                <input
                    type="tel"
                    inputmode="numeric"
                    name="phone"
                    id="phone"
                    value="{{ old('phone') }}"
                    required
                    maxlength="15"
                    placeholder="09xxxxxxxxx"
                    class="w-full rounded-r-xl border border-slate-300 dark:border-slate-700 pl-3.5 pr-3.5 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm font-mono outline-none transition"
                />
            </div>
        </div>

        {{-- Password --}}
        <div class="space-y-1">
            <label for="password" class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                {{ __('messages.password') }} <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-base">
                    🔑
                </span>
                <input
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    id="password"
                    x-model="password"
                    required
                    placeholder="{{ __('messages.password_min') }}"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 pl-10 pr-10 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:border-sky-500 shadow-sm outline-none transition"
                />
                <button
                    @click="showPassword = !showPassword"
                    type="button"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-base text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                >
                    <span x-text="showPassword ? '🙈' : '👁️'"></span>
                </button>
            </div>
            {{-- Password Strength --}}
            <template x-if="password">
                <div class="flex items-center gap-1.5 mt-1">
                    <div class="flex-1 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="{
                                'bg-rose-500 w-1/5': passwordStrength === 1,
                                'bg-amber-500 w-2/5': passwordStrength === 2,
                                'bg-yellow-500 w-3/5': passwordStrength === 3,
                                'bg-sky-500 w-4/5': passwordStrength === 4,
                                'bg-emerald-500 w-full': passwordStrength === 5
                            }"
                        ></div>
                    </div>
                    <span class="text-[10px] font-bold w-10 text-right"
                        :class="{
                            'text-rose-500': passwordStrength <= 1,
                            'text-amber-500': passwordStrength === 2,
                            'text-yellow-500': passwordStrength === 3,
                            'text-sky-500': passwordStrength === 4,
                            'text-emerald-500': passwordStrength === 5
                        }"
                        x-text="['', '{{ __('messages.strength_weak') }}', '{{ __('messages.strength_fair') }}', '{{ __('messages.strength_good') }}', '{{ __('messages.strength_strong') }}', '{{ __('messages.strength_strongest') }}'][passwordStrength]"></span>
                </div>
            </template>
        </div>

        {{-- Confirm Password --}}
        <div class="space-y-1">
            <label for="password_confirmation" class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                {{ __('messages.confirm_password') }} <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base"
                    :class="passwordsMatch ? 'text-slate-400' : 'text-rose-400'">
                    🔒
                </span>
                <input
                    :type="showPassword ? 'text' : 'password'"
                    name="password_confirmation"
                    id="password_confirmation"
                    x-model="passwordConfirmation"
                    required
                    placeholder="{{ __('messages.password_confirm_placeholder') }}"
                    class="w-full rounded-xl border py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 shadow-sm outline-none transition pl-10 pr-10"
                    :class="passwordsMatch
                        ? 'border-slate-300 dark:border-slate-700 focus:ring-sky-500 focus:border-sky-500'
                        : 'border-rose-400 dark:border-rose-700 focus:ring-rose-500'"
                />
                <span x-show="passwordConfirmation" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-base"
                    :class="passwordsMatch ? 'text-emerald-500' : 'text-rose-500'"
                    x-text="passwordsMatch ? '✓' : '✗'"></span>
            </div>
            <p x-show="!passwordsMatch" class="text-[11px] text-rose-500 font-bold">{{ __('messages.password_mismatch') }}</p>
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            :disabled="loading"
            class="w-full min-h-[48px] py-3.5 px-4 bg-gradient-to-r from-emerald-600 via-sky-600 to-violet-600 hover:from-emerald-500 hover:via-sky-500 hover:to-violet-500 text-white font-black text-sm rounded-2xl shadow-xl shadow-sky-500/25 transition transform active:scale-95 flex items-center justify-center space-x-2 disabled:opacity-70 disabled:cursor-not-allowed"
        >
            <template x-if="!loading">
                <span class="flex items-center space-x-2">
                    <span>{{ __('messages.register') }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </span>
            </template>
            <template x-if="loading">
                <span class="flex items-center space-x-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span>{{ __('messages.loading') }}</span>
                </span>
            </template>
        </button>

        {{-- Login Link --}}
        <div class="text-center pt-1 text-sm font-bold text-slate-600 dark:text-slate-400 font-myanmar">
            {{ __('messages.has_account') }}
            <a href="{{ route('login') }}" class="font-black text-sky-600 dark:text-sky-400 hover:underline">
                {{ __('messages.login') }} &rarr;
            </a>
        </div>
    </form>
</div>

{{-- Footer --}}
<div class="text-center mt-5 text-[11px] font-bold text-slate-400 dark:text-slate-600 font-myanmar">
    © {{ date('Y') }} DataPOS · {{ __('messages.trusted_by_us') }}
</div>
@endsection
