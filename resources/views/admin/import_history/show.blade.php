@extends('layouts.admin.app')

@section('title', __('messages.import_history_batch_details') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3 md:p-4')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-2.5 sm:space-y-3 pb-8" x-data>

    {{-- 1. Compact Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <span class="w-10 h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 grid place-items-center text-lg font-bold shrink-0 shadow-sm">
                📋
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    <a href="{{ route('store.admin.import-history.index', $storeRouteParams) }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition">
                        {{ __('messages.import_history') }}
                    </a>
                    <span>/</span>
                    <span class="text-violet-600 dark:text-violet-400 font-mono">Batch #{{ $history->id }}</span>
                </div>
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 truncate font-mono">
                    {{ $history->filename }}
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.import_history_batch_details') }} ({{ $history->displayType() }})
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('store.admin.import-history.index', $storeRouteParams) }}"
               class="h-9 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5">
                <span>←</span>
                <span>နောက်သို့ ပြန်သွားမည်</span>
            </a>
            <form method="POST" action="{{ route('store.admin.import-history.destroy', array_merge($storeRouteParams, ['history' => $history->id])) }}"
                  onsubmit="return confirm('{{ __('messages.import_history_delete_confirm') }}')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="h-9 px-3 rounded-xl text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition inline-flex items-center gap-1.5 cursor-pointer">
                    <span>🗑️</span>
                    <span>ဖျက်မည်</span>
                </button>
            </form>
        </div>
    </div>

    {{-- 2. 4 Stat KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">
        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Rows</div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-slate-900 dark:text-slate-100 tabular-nums">
                {{ number_format($history->total_rows) }}
            </div>
            <p class="text-[11px] text-slate-400">တင်သွင်းသော စုစုပေါင်း</p>
        </div>

        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Successful Rows</div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 tabular-nums">
                {{ number_format($history->success_rows) }}
            </div>
            <p class="text-[11px] text-slate-400">အောင်မြင်သော စာကြောင်းရေ</p>
        </div>

        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="text-xs font-semibold text-rose-600 dark:text-rose-400">Failed Rows</div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-rose-600 dark:text-rose-400 tabular-nums">
                {{ number_format($history->failed_rows) }}
            </div>
            <p class="text-[11px] text-slate-400">အမှားရှိသော စာကြောင်းရေ</p>
        </div>

        <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 shadow-sm">
            <div class="text-xs font-semibold text-sky-600 dark:text-sky-400">Batch Success Rate</div>
            <div class="mt-1 text-xl sm:text-2xl font-black font-mono tracking-tight text-sky-600 dark:text-sky-400 tabular-nums">
                {{ $history->total_rows > 0 ? round(($history->success_rows / $history->total_rows) * 100, 1) . '%' : '100%' }}
            </div>
            <p class="text-[11px] text-slate-400">အောင်မြင်မှု ရာခိုင်နှုန်း</p>
        </div>
    </div>

    {{-- 3. Batch Metadata & Error Report Card --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Metadata Card --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                <span>ℹ️</span>
                <span>တင်သွင်းမှု ဆိုင်ရာ အချက်အလက်များ</span>
            </h3>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1.5 border-b border-slate-50 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">ဖိုင်အမည် (Filename):</span>
                    <span class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ $history->filename }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">အမျိုးအစား (Import Type):</span>
                    <span class="font-bold text-violet-600 dark:text-violet-400">{{ $history->displayType() }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">တင်သွင်းချိန် (Timestamp):</span>
                    <span class="font-mono text-slate-900 dark:text-slate-100">{{ $history->created_at->format('d M Y, h:i:s A') }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-50 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">ဆောင်ရွက်သူ (Admin User):</span>
                    <span class="text-slate-900 dark:text-slate-100 font-medium">{{ $history->user?->name ?? 'System' }}</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-500 dark:text-slate-400">အခြေအနေ (Status):</span>
                    <span>
                        @if ($history->failed_rows === 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                ✓ အောင်မြင်စွာ ပြီးစီးပါသည် (Complete)
                            </span>
                        @elseif ($history->success_rows > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300">
                                ⚠ တစ်စိတ်တစ်ပိုင်းသာ သွင်းနိုင်ခဲ့သည် (Partial)
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">
                                ⨯ မအောင်မြင်ပါ (Failed)
                            </span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Error Report Card --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span>⚠️</span>
                    <span>အမှား မှတ်တမ်းနှင့် အစီရင်ခံစာ (Error Report)</span>
                </h3>

                @if ($history->failed_rows > 0 && $history->error_file_path)
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-2 leading-relaxed">
                        ဤတင်သွင်းမှုတွင် မအောင်မြင်ခဲ့သော စာကြောင်းရေ <strong class="text-rose-600">{{ $history->failed_rows }}</strong> ခု ရှိခဲ့ပါသည်။ အမှားဖြစ်ရသည့် အကြောင်းအရင်း၊ စာကြောင်းနံပါတ်နှင့် မူလဒေတာများကို Excel CSV ဖြင့် ဒေါင်းလုဒ်ရယူ၍ ပြင်ဆင်နိုင်ပါသည်။
                    </p>
                @else
                    <div class="p-4 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-800/60 text-xs text-emerald-800 dark:text-emerald-200 mt-2">
                        ✓ အမှားမရှိပါ — စာကြောင်းများ အားလုံးကို အောင်မြင်စွာ တင်သွင်းပြီးဖြစ်ပါသည်။
                    </div>
                @endif
            </div>

            @if ($history->failed_rows > 0 && $history->error_file_path)
                <a href="{{ route('store.admin.import-history.errors', array_merge($storeRouteParams, ['history' => $history->id])) }}"
                   class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition flex items-center justify-center gap-2 cursor-pointer active:scale-95">
                    <span>⬇</span>
                    <span>အမှား CSV ဖိုင် ဒေါင်းလုဒ်ရယူမည် (Download Error Report)</span>
                </a>
            @endif
        </div>
    </div>

</div>
@endsection
