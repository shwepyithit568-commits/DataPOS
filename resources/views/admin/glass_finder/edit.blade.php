@extends('layouts.admin.app')

@section('title', __('messages.glass_finder_edit_item') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">
    
    {{-- Header --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-fuchsia-50 text-fuchsia-700 dark:bg-fuchsia-950/60 dark:text-fuchsia-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-fuchsia-100 dark:border-fuchsia-900/60 mb-0.5">
                <span>🔍</span>
                <span>{{ __('messages.sidebar_glass_finder') }}</span>
                <span class="text-slate-400 dark:text-slate-500">·</span>
                <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Edit Model</span>
            </div>
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ __('messages.glass_finder_edit_item') }}
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $store->name }} · {{ $item->brand }} - {{ $item->phone_model }} ({{ $item->glass_code }})
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/glass-finder') }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                <span>{{ __('messages.back') }}</span>
            </a>
        </div>
    </header>

    @if ($errors->any())
        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            <div class="flex items-center gap-1.5 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-4 font-semibold">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Form Panel --}}
    <div class="max-w-2xl bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-2xs">
        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder/' . $item->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Brand <span class="text-rose-500">*</span></label>
                    <input type="text" name="brand" value="{{ old('brand', $item->brand) }}" required
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none transition" />
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Phone Model <span class="text-rose-500">*</span></label>
                    <input type="text" name="phone_model" value="{{ old('phone_model', $item->phone_model) }}" required
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none transition" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Glass Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="glass_code" value="{{ old('glass_code', $item->glass_code) }}" required
                           class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-mono font-bold text-violet-600 dark:text-violet-400 focus:ring-2 focus:ring-violet-500 outline-none transition" />
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Stock Status</label>
                    <select name="stock_status"
                            class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none transition cursor-pointer">
                        <option value="in_stock" {{ old('stock_status', $item->stock_status) === 'in_stock' ? 'selected' : '' }}>In Stock (လက်ကျန်ရှိ)</option>
                        <option value="out_of_stock" {{ old('stock_status', $item->stock_status) === 'out_of_stock' ? 'selected' : '' }}>Out of Stock (ပြတ်လပ်)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/glass-finder') }}"
                   class="px-3.5 py-2 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition">
                    {{ __('messages.cancel') }}
                </a>
                <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg font-bold text-xs shadow-2xs transition active:scale-95">
                    {{ __('messages.update') }}
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
