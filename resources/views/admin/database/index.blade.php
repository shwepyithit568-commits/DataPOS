@extends('layouts.admin.app')

@section('title', 'Database Tools & Optimizer - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                🗄️
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        Dashboard
                    </a>
                    <span>/</span>
                    <span class="text-cyan-600 dark:text-cyan-400">Maintenance</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>ဒေတာဘေ့စ် ထိန်းသိမ်းရေး (Database Tools & Optimizer)</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · ဒေတာဘေ့စ် ကျစ်လစ်စေခြင်း၊ Query အမြန်နှုန်း မြှင့်တင်ခြင်းနှင့် ကျန်းမာရေး စစ်ဆေးခြင်း</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('store.admin.backups.index', $storeRouteParams) }}"
               class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                <span>💾</span>
                <span>Database Backups</span>
            </a>
            <a href="{{ route('store.admin.alerts.index', $storeRouteParams) }}"
               class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-cyan-600 hover:bg-cyan-500 text-white shadow-lg shadow-cyan-500/20 transition flex items-center gap-2 active:scale-95">
                <span>🔔</span>
                <span>စနစ် သတိပေးချက်များ</span>
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-rose-100 dark:bg-rose-900 grid place-items-center text-rose-600 dark:text-rose-300 font-black">⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. 4 Key Health KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-3.5">
        {{-- File Size --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-cyan-600 dark:text-cyan-400 truncate">Database Size</span>
                <span class="text-base">💾</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono tracking-tight">{{ $stats['file_size'] }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5 font-mono">{{ $stats['driver'] }} Storage</p>
        </div>

        {{-- Total Tables --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 truncate">Schema Tables</span>
                <span class="text-base">🗄️</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">{{ number_format($stats['total_tables']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ဇယားစုစုပေါင်း အရေအတွက်</p>
        </div>

        {{-- Total Records --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 truncate">Total Records</span>
                <span class="text-base">📊</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ number_format($stats['total_rows']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">စုစုပေါင်း ဒေတာစာကြောင်းများ</p>
        </div>

        {{-- Health / Integrity --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 truncate">Integrity Health</span>
                <span class="text-base">🛡️</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">Healthy ✓</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">{{ $stats['integrity_status'] }} Status</p>
        </div>
    </div>

    {{-- 3. Optimization Action Tools --}}
    <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h2 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>⚡</span>
                    <span>ဒေတာဘေ့စ် စွမ်းဆောင်ရည်မြှင့်တင်ရေး ကိရိယာများ (Optimization Tools)</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">တစ်ချက်နှိပ်ရုံဖြင့် ဒေတာဘေ့စ်အား ကျစ်လစ်ရှင်းလင်းစေပြီး Query အမြန်နှုန်း တိုးတက်စေပါသည်</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
            {{-- VACUUM --}}
            <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                <div>
                    <div class="flex items-center gap-2 text-cyan-600 dark:text-cyan-400 font-black text-sm">
                        <span>🧹</span>
                        <span>Vacuum & Defrag</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        ဖျက်ထားသော ဒေတာနေရာလွတ်များကို ပြန်လည်သိမ်းဆည်းပြီး ဖိုင်အရွယ်အစားကို အကျစ်လစ်ဆုံးဖြစ်အောင် ရှင်းလင်းပေးပါသည်
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.vacuum', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('ဒေတာဘေ့စ် VACUUM ပြုလုပ်မည်မှာ သေချာပါသလား?')"
                            class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-cyan-600 hover:bg-cyan-500 text-white shadow-sm transition active:scale-95 text-center">
                        Vacuum Reclaim Space
                    </button>
                </form>
            </div>

            {{-- Re-Index & Analyze --}}
            <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                <div>
                    <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-black text-sm">
                        <span>⚡</span>
                        <span>Re-Index & Analyze</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Query စာရင်းဇယားများကို ပြန်လည်တွက်ချက်ပြီး အရောင်း၊ စတော့နှင့် ရှာဖွေမှု အမြန်နှုန်းများကို အမြင့်ဆုံး ရရှိစေပါသည်
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.optimize', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition active:scale-95 text-center">
                        Optimize Query Planner
                    </button>
                </form>
            </div>

            {{-- Integrity Check --}}
            <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                <div>
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-black text-sm">
                        <span>🔍</span>
                        <span>Integrity Health Check</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        ဒေတာဘေ့စ် စာမျက်နှာများနှင့် ဇယားများ ပျက်စီးချွတ်ယွင်းမှု ရှိမရှိ အပြည့်အစုံ စစ်ဆေးပေးပါသည်
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.integrity', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition active:scale-95 text-center">
                        Check Database Health
                    </button>
                </form>
            </div>

            {{-- Clear Cache --}}
            <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 bg-slate-50/60 dark:bg-slate-800/40 flex flex-col justify-between space-y-3">
                <div>
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400 font-black text-sm">
                        <span>🗑</span>
                        <span>Purge App Cache</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        ယာယီ View၊ Config၊ Route နှင့် Session Cache ဖိုင်အဟောင်းများကို အပြီးတိုင် ရှင်းလင်းပေးပါသည်
                    </p>
                </div>
                <form method="POST" action="{{ route('store.admin.database.clear_cache', $storeRouteParams) }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white shadow-sm transition active:scale-95 text-center">
                        Clear Cache Files
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- 4. Database Schema Tables Breakdown --}}
    <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden space-y-0">
        <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <span>📋</span>
                    <span>ဒေတာဘေ့စ် ဇယားများ အခြေအနေ (Schema Tables Breakdown)</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">လက်ရှိ စနစ်အတွင်းရှိ ဒေတာဇယားများနှင့် စာကြောင်းရေများ</p>
            </div>
            <span class="text-xs font-mono font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-xl">
                {{ count($tables) }} Tables Total
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">စဉ်</th>
                        <th class="py-3 px-4">ဇယားအမည် (Table Name)</th>
                        <th class="py-3 px-4">ကဏ္ဍ (Category)</th>
                        <th class="py-3 px-4 text-right">စာကြောင်းရေ (Row Count)</th>
                        <th class="py-3 px-4 text-center">အခြေအနေ (Status)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($tables as $index => $t)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-4 font-mono text-slate-400">{{ $index + 1 }}</td>
                            <td class="py-2.5 px-4 font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ $t['name'] }}
                            </td>
                            <td class="py-2.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $t['category'] }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                {{ number_format($t['rows']) }}
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <span class="text-emerald-600 font-bold text-[11px]">✓ Active</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
