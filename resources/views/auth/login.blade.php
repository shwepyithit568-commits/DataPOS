@extends('layouts.auth.app')

@section('content')
<div
    x-data="{
        phone: '{{ old('phone') }}',
        password: '',
        showPassword: false,
        loading: false
    }"
    x-init="$watch('phone', v => phone = v.replace(/[^0-9]/g, ''))"
    class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-7 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-5 animate-[fadeIn_0.4s_ease-out]"
>
    {{-- Header --}}
    <div class="text-center space-y-2">
        @if (!empty($setting?->storefrontLogo()) && \Illuminate\Support\Facades\Storage::disk('public')->exists($setting->storefrontLogo()))
            <img src="{{ asset('storage/' . $setting->storefrontLogo()) }}" alt="{{ $setting?->store_name ?? config('app.name') }}" class="w-full max-w-[200px] sm:max-w-[240px] h-auto mx-auto rounded-xl shadow-lg ring-4 ring-white/50 dark:ring-slate-800/50 object-contain" />
        @else
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-violet-600 via-fuchsia-500 to-rose-500 text-white text-2xl shadow-lg shadow-sky-500/30 mx-auto ring-4 ring-white/50 dark:ring-slate-800/50">
                ⚡
            </div>
        @endif
        <div class="space-y-0.5">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-outfit tracking-tight">
                {{ __('messages.login_heading') }}
            </h1>
            <p class="text-sm font-bold text-slate-600 dark:text-slate-400 font-myanmar">
                {{ __('messages.login_intro') }}
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
    <form method="POST" action="{{ route('login') }}" class="space-y-3" @submit="loading = true">
        @csrf

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
                    x-model="phone"
                    required
                    autofocus
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
                    placeholder="••••••••"
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
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center pt-0.5">
            <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-sky-600 shadow-sm focus:ring-sky-500 w-4.5 h-4.5" />
                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.remember_me') }}</span>
            </label>
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            :disabled="loading"
            class="w-full min-h-[48px] py-3.5 px-4 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:from-violet-600 hover:via-violet-500 hover:to-rose-500 text-white font-black text-sm rounded-2xl shadow-xl shadow-sky-500/25 transition transform active:scale-95 flex items-center justify-center space-x-2 disabled:opacity-70 disabled:cursor-not-allowed"
        >
            <template x-if="!loading">
                <span class="flex items-center space-x-2">
                    <span>{{ __('messages.login') }}</span>
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

        {{-- Quick Login (DEV/TEST ONLY) --}}
        @if (!empty($quickLoginUsers))
            <div class="pt-2 space-y-2">
                <div class="flex items-center space-x-2">
                    <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">Quick Login</span>
                    <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
                </div>
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach ($quickLoginUsers as $ql)
                        <form method="POST" action="{{ route('quick-login') }}">
                            @csrf
                            <input type="hidden" name="phone" value="{{ $ql['phone'] }}" />
                            <button type="submit"
                                class="w-full text-left px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-sky-50 dark:hover:bg-sky-950/40 hover:border-sky-300 dark:hover:border-sky-700 transition text-xs group"
                            >
                                <span class="font-bold text-slate-700 dark:text-slate-300 group-hover:text-sky-700 dark:group-hover:text-sky-300 block truncate">
                                    {{ $ql['name'] }}
                                </span>
                                <span class="font-mono text-[10px] text-slate-400 dark:text-slate-500">
                                    {{ $ql['phone'] }}
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Register Link --}}
        <div class="text-center pt-1 space-y-2">
            <div class="text-sm font-bold text-slate-600 dark:text-slate-400 font-myanmar">
                {{ __('messages.no_account') }}
                <a href="{{ route('register') }}" class="font-black text-sky-600 dark:text-sky-400 hover:underline">
                    {{ __('messages.register_new') }} &rarr;
                </a>
            </div>
            <a href="{{ url('/?store_slug=' . ($setting?->store_slug ?? request('store_slug', 'datapos-mobile'))) }}" class="inline-flex items-center text-sm font-bold text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition">
                {{ __('messages.continue_without_login') }} &rarr;
            </a>
        </div>
    </form>
</div>

{{-- Footer --}}
<div class="text-center mt-5 text-[11px] font-bold text-slate-400 dark:text-slate-600 font-myanmar">
    © {{ date('Y') }} DataPOS · {{ __('messages.trusted_by_us') }}
</div>
@endsection
