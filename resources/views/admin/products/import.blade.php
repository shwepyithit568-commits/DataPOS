@extends('layouts.admin.app')

@section('title', __('messages.import_products') . ' - ' . $store->name)
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6" x-data="{ guideOpen: false, activeGuideTab: 'columns', copied: false }">
    {{-- 1. Top Header Banner (Ultra-Dense 36px) --}}
    <div class="px-2 py-1.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 select-none">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-7 h-7 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 grid place-items-center text-sm font-black shrink-0">
                📥
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                        {{ __('messages.import_products') }}
                    </h1>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        {{ $store->name }}
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 font-mono truncate hidden sm:block">
                    {{ __('messages.import_upload_desc') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
            <a href="{{ route('store.admin.products.import.template', ['store_slug' => $store->slug]) }}" download="product-import-template.xlsx"
               class="h-7 px-2.5 rounded text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs hover:shadow-emerald-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                <span>{{ __('messages.download_product_template') }}</span>
            </a>
            <a href="{{ route('store.admin.products.index', ['store_slug' => $store->slug]) }}"
               class="h-7 px-2.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition inline-flex items-center gap-1.5 shadow-2xs">
                <span>📦</span>
                <span>{{ __('messages.back_to_products') }}</span>
            </a>
        </div>
    </div>

    {{-- Error Flash Alert --}}
    @if ($errors->any())
        <div class="p-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded text-xs text-rose-700 dark:text-rose-300 space-y-0.5 shadow-2xs">
            <div class="font-bold flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.common_error') }}</span>
            </div>
            @foreach ($errors->all() as $error)
                <div class="pl-5 text-[11px]">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- 2. Compact Centered Stat Cards (4 Columns) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Card 1: Total Processed Rows --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                📋
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    {{ __('messages.total_rows') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5">
                    {{ number_format($histories->sum('total_rows')) }}
                </div>
            </div>
        </div>

        {{-- Card 2: Success Products --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-emerald-200/90 dark:border-emerald-900/50 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-black shrink-0">
                ✓
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 leading-none">
                    {{ __('messages.new_products') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5">
                    {{ number_format($histories->sum('success_rows')) }}
                </div>
            </div>
        </div>

        {{-- Card 3: Failed Rows --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border {{ $histories->sum('failed_rows') > 0 ? 'border-rose-200/90 dark:border-rose-900/50' : 'border-slate-200/90 dark:border-slate-800' }} shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md {{ $histories->sum('failed_rows') > 0 ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }} flex items-center justify-center text-sm font-black shrink-0">
                ⚠️
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider {{ $histories->sum('failed_rows') > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }} leading-none">
                    {{ __('messages.failed_products') }}
                </p>
                <div class="text-xs sm:text-sm font-black font-mono {{ $histories->sum('failed_rows') > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }} tabular-nums mt-0.5">
                    {{ number_format($histories->sum('failed_rows')) }}
                </div>
            </div>
        </div>

        {{-- Card 4: Total Import Batches --}}
        <div class="p-2 sm:p-2.5 bg-white dark:bg-slate-900 rounded border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3 transition">
            <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm font-black shrink-0">
                📜
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 leading-none">
                    Import Batches
                </p>
                <div class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-slate-100 tabular-nums mt-0.5">
                    {{ number_format($histories->count()) }}
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Live Preview Section (If Preview Active) --}}
    @if (session('import_preview'))
        @php $preview = session('import_preview'); @endphp
        <div class="bg-amber-50/70 dark:bg-amber-950/30 border border-amber-300/80 dark:border-amber-800 rounded p-2.5 sm:p-3 space-y-2 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 border-b border-amber-200 dark:border-amber-900/60 pb-1.5">
                <div>
                    <h3 class="font-black text-amber-950 dark:text-amber-200 text-xs sm:text-sm flex items-center gap-1.5">
                        <span>🔍</span>
                        <span>{{ __('messages.import_preview_ready') }}:</span>
                        <span class="font-mono text-[11px] bg-white dark:bg-slate-900 px-1.5 py-0.2 rounded border border-amber-200 dark:border-amber-800">{{ $preview['filename'] }}</span>
                    </h3>
                    <p class="text-[10px] text-amber-800 dark:text-amber-300 mt-0.5">{{ __('messages.import_preview_desc') }}</p>
                </div>
            </div>

            {{-- 5 Preview KPI Mini Stat Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-1 text-center">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded p-1.5 shadow-2xs">
                    <div class="text-xs sm:text-sm font-black text-slate-800 dark:text-slate-100 font-mono">{{ $preview['total'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-slate-400 mt-0.5">{{ __('messages.total_rows') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800/60 rounded p-1.5 shadow-2xs">
                    <div class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ $preview['creatable'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-emerald-600/80 dark:text-emerald-400 mt-0.5">{{ __('messages.new_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-violet-200 dark:border-violet-800/60 rounded p-1.5 shadow-2xs">
                    <div class="text-xs sm:text-sm font-black text-violet-600 dark:text-violet-400 font-mono">{{ $preview['updatable'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-violet-600/80 dark:text-violet-400 mt-0.5">{{ __('messages.updated_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800/60 rounded p-1.5 shadow-2xs">
                    <div class="text-xs sm:text-sm font-black text-amber-600 dark:text-amber-400 font-mono">{{ $preview['skipped_duplicate'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-amber-600/80 dark:text-amber-400 mt-0.5">{{ __('messages.skipped_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-800/60 rounded p-1.5 shadow-2xs">
                    <div class="text-xs sm:text-sm font-black text-rose-600 dark:text-rose-400 font-mono">{{ $preview['failed'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-rose-600/80 dark:text-rose-400 mt-0.5">{{ __('messages.failed_products') }}</div>
                </div>
            </div>

            {{-- Spreadsheet Preview Table --}}
            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded shadow-2xs max-h-64 overflow-y-auto">
                    <table class="w-full text-xs text-left divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-100 dark:bg-slate-800/90 font-bold text-slate-700 dark:text-slate-300 sticky top-0 z-10 select-none">
                            <tr class="divide-x divide-slate-200 dark:divide-slate-700 text-[11px] uppercase">
                                <th class="py-1.5 px-2 text-center w-10">#</th>
                                <th class="py-1.5 px-2 min-w-[110px]">{{ __('messages.sku') }}</th>
                                <th class="py-1.5 px-2 min-w-[180px]">{{ __('messages.name') }}</th>
                                <th class="py-1.5 px-2 min-w-[120px]">{{ __('messages.brand') }}</th>
                                <th class="py-1.5 px-2 min-w-[130px]">{{ __('messages.category') }}</th>
                                <th class="py-1.5 px-2 text-right min-w-[100px]">{{ __('messages.retail_price') }}</th>
                                <th class="py-1.5 px-2 text-center w-20">{{ __('messages.stock_status') }}</th>
                                <th class="py-1.5 px-2 text-center w-24">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 text-slate-800 dark:text-slate-200 font-medium">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 divide-x divide-slate-200/60 dark:divide-slate-800 transition">
                                    <td class="py-1 px-2 text-center text-slate-400 font-mono text-[11px]">{{ $row['row'] }}</td>
                                    <td class="py-1 px-2 font-mono font-bold text-emerald-700 dark:text-emerald-400">{{ $row['sku'] }}</td>
                                    <td class="py-1 px-2 font-bold">{{ $row['name'] }}</td>
                                    <td class="py-1 px-2 text-slate-600 dark:text-slate-400">{{ $row['brand'] ?? '—' }}</td>
                                    <td class="py-1 px-2 text-slate-600 dark:text-slate-400">{{ $row['category'] ?? '—' }}</td>
                                    <td class="py-1 px-2 text-right font-mono font-bold tabular-nums">Ks {{ number_format((float) ($row['retail_price'] ?? 0)) }}</td>
                                    <td class="py-1 px-2 text-center">
                                        @if (($row['stock_status'] ?? null) === 'out_of_stock')
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">{{ __('messages.out_of_stock') }}</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ __('messages.in_stock') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-1 px-2 text-center">
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-black uppercase tracking-wider
                                            {{ $row['action'] === 'create' ? 'bg-emerald-500 text-white' : ($row['action'] === 'update' ? 'bg-violet-500 text-white' : 'bg-amber-500 text-white') }}">
                                            {{ $row['action'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Failed Rows Details Accordion --}}
            @if (!empty($preview['failed_rows']))
                <details class="bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded p-2">
                    <summary class="cursor-pointer text-xs font-bold text-rose-800 dark:text-rose-300 flex items-center justify-between select-none">
                        <span>❌ {{ __('messages.failed_products') }} ({{ count($preview['failed_rows']) }})</span>
                        <span class="text-[10px] text-rose-600 font-normal">Click to expand</span>
                    </summary>
                    <div class="mt-1.5 space-y-1 text-xs bg-white dark:bg-slate-900 border border-rose-200/80 dark:border-rose-800/80 rounded p-2 max-h-40 overflow-y-auto font-mono">
                        @foreach ($preview['failed_rows'] as $fr)
                            <div class="text-rose-700 dark:text-rose-300 border-b border-rose-100 dark:border-rose-900/40 pb-0.5 last:border-0 text-[11px]">
                                <strong>Row {{ $fr['row'] }}</strong>{{ isset($fr['sku']) ? ' [SKU: ' . $fr['sku'] . ']' : '' }}: {{ $fr['reason'] }}
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

            {{-- Confirm Import Form --}}
            <form method="POST" action="{{ route('store.admin.products.import.confirm', ['store_slug' => $store->slug]) }}"
                  class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-800/80 rounded p-2">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ __('messages.duplicate_sku_handling') }}:</label>
                    <select name="duplicate_strategy" class="border border-slate-300 dark:border-slate-600 rounded px-2 py-1 text-xs bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-medium focus:ring-1 focus:ring-amber-500">
                        <option value="skip" {{ $preview['duplicate_strategy'] === 'skip' ? 'selected' : '' }}>{{ __('messages.skip_duplicate_products') }}</option>
                        <option value="update" {{ $preview['duplicate_strategy'] === 'update' ? 'selected' : '' }}>{{ __('messages.update_existing_products') }}</option>
                    </select>
                </div>
                <button type="submit" class="h-7 inline-flex items-center justify-center gap-1.5 px-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded font-black text-xs shadow-2xs transition active:scale-95 cursor-pointer">
                    <span>✅</span>
                    <span>{{ __('messages.confirm_import') }}</span>
                </button>
            </form>
        </div>
    @endif

    {{-- Import Result Success Banner --}}
    @if (session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded p-2.5 sm:p-3 space-y-2 shadow-2xs">
            <h3 class="font-black text-emerald-900 dark:text-emerald-200 text-xs sm:text-sm flex items-center gap-1.5">
                <span>🎉</span>
                <span>{{ __('messages.import_completed') }}</span>
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-1 text-center text-xs">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded p-1.5">
                    <div class="text-xs sm:text-sm font-bold font-mono">{{ $result['total'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-slate-400 mt-0.5">{{ __('messages.total_rows') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800 rounded p-1.5">
                    <div class="text-xs sm:text-sm font-bold font-mono text-emerald-600">{{ $result['imported'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-emerald-600 mt-0.5">{{ __('messages.new_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-violet-200 dark:border-violet-800 rounded p-1.5">
                    <div class="text-xs sm:text-sm font-bold font-mono text-violet-600">{{ $result['updated'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-violet-600 mt-0.5">{{ __('messages.updated_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800 rounded p-1.5">
                    <div class="text-xs sm:text-sm font-bold font-mono text-amber-600">{{ $result['skipped_duplicate'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-amber-600 mt-0.5">{{ __('messages.skipped_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-800 rounded p-1.5">
                    <div class="text-xs sm:text-sm font-bold font-mono text-rose-600">{{ $result['failed'] }}</div>
                    <div class="text-[9px] font-bold uppercase text-rose-600 mt-0.5">{{ __('messages.failed_products') }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- 4. Main Upload Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded p-3 sm:p-4 shadow-2xs space-y-3">
        <form action="{{ route('store.admin.products.import', ['store_slug' => $store->slug]) }}" method="POST" enctype="multipart/form-data" class="space-y-2.5"
              x-data="{ fileName: '', isDragging: false, uploading: false }"
              @submit="if (uploading) { $event.preventDefault(); } else { uploading = true; }">
            @csrf
            
            {{-- Modern Drag & Drop Zone --}}
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    {{ __('messages.import_upload_title') }}
                </label>
                <div class="relative border-2 border-dashed rounded-lg p-4 sm:p-5 text-center transition cursor-pointer"
                     :class="isDragging ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20' : 'border-slate-300 dark:border-slate-700 hover:border-emerald-400 dark:hover:border-emerald-600 bg-slate-50/60 dark:bg-slate-800/40'"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $refs.fileInput.files[0]?.name || ''">
                    
                    <input type="file" name="file" x-ref="fileInput" accept=".csv,.txt,.xlsx" required
                           @change="fileName = $refs.fileInput.files[0]?.name || ''"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />

                    <div class="flex flex-col items-center justify-center gap-1">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 grid place-items-center text-lg shadow-inner">
                            📄
                        </div>
                        <div class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">
                            <span x-show="!fileName">{{ __('messages.import_upload_title') }}</span>
                            <span x-show="fileName" x-text="fileName" class="text-emerald-600 dark:text-emerald-400 font-mono font-black"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ __('messages.import_upload_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">
                        {{ __('messages.duplicate_sku_handling') }}
                    </label>
                    <select name="duplicate_strategy" class="w-full border border-slate-300 dark:border-slate-700 rounded px-2.5 py-1 text-xs bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-medium focus:ring-1 focus:ring-emerald-500 outline-none">
                        <option value="skip">{{ __('messages.skip_duplicate_products') }}</option>
                        <option value="update">{{ __('messages.update_existing_products') }}</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" :disabled="uploading"
                            class="w-full h-7 inline-flex items-center justify-center gap-1.5 px-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded font-black text-xs shadow-2xs transition active:scale-95 disabled:opacity-60 cursor-pointer">
                        <span x-show="!uploading" class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                            <span>{{ __('messages.upload_and_preview') }}</span>
                        </span>
                        <span x-show="uploading" class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            <span>Processing...</span>
                        </span>
                    </button>
                </div>
            </div>
        </form>

        {{-- 5. Supported Columns Guide Drawer --}}
        <div class="border-t border-slate-100 dark:border-slate-800 pt-2">
            <button type="button" @click="guideOpen = !guideOpen" class="w-full flex items-center justify-between text-left group cursor-pointer">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>💡</span>
                    <span>{{ __('messages.supported_columns_guide') }}</span>
                </span>
                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 group-hover:underline font-bold" x-text="guideOpen ? 'Hide ▲' : 'Show ▼'"></span>
            </button>

            <div x-show="guideOpen" x-transition class="mt-2 space-y-1.5 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                <div class="relative bg-slate-100 dark:bg-slate-800/80 rounded p-2 font-mono text-[10px] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 overflow-x-auto">
                    <span>name,sku,brand,category,parent_category,retail_price,wholesale_price,old_price,sale_starts_at,sale_ends_at,stock_status,warranty,return_policy,description,meta_description,image_url,featured,variants</span>
                </div>
                <div class="p-2 bg-slate-50 dark:bg-slate-800/50 rounded border border-slate-200/80 dark:border-slate-700/80 space-y-1 text-[11px]">
                    <p>
                        <strong>📌 POS / AppSheet Migration:</strong>
                        Product_ID &rarr; sku, Product_Name &rarr; name, Sale_Price &rarr; retail_price, Warranty_Period &rarr; warranty. If no stock_status is present, positive Current_Stock sets in_stock.
                    </p>
                    <p>
                        <strong>🏷️ Variants & Specifications:</strong>
                        The <code class="bg-slate-200 dark:bg-slate-700 px-1 py-0.2 rounded font-mono text-[10px]">variants</code> column accepts a JSON array where each variant holds its own retail_price, wholesale_price, sku, and stock_status:
                        <code class="block font-mono text-[10px] bg-white dark:bg-slate-900 p-1 rounded mt-1 select-all border border-slate-200 dark:border-slate-800">[{"name":"Type C","sku":"L2009-TC","retail_price":5000,"wholesale_price":4500,"stock_status":"in_stock"},{"name":"Lightning","sku":"L2009-LT","retail_price":7000,"wholesale_price":6300,"stock_status":"in_stock"}]</code>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- 6. Recent Import History Table --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded p-3 sm:p-4 shadow-2xs space-y-2">
        <div class="flex items-center justify-between">
            <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                <span>📜</span>
                <span>{{ __('messages.recent_product_imports') }}</span>
            </h2>
            <span class="text-[11px] text-slate-400 font-mono">{{ $histories->count() }} Records</span>
        </div>

        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded shadow-2xs">
            <table class="w-full text-left text-xs divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-100 dark:bg-slate-800/90 text-slate-600 dark:text-slate-300 font-bold uppercase text-[11px] select-none">
                    <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                        <th class="py-1.5 px-2.5">Date & Time</th>
                        <th class="py-1.5 px-2.5">Filename</th>
                        <th class="py-1.5 px-2.5">User</th>
                        <th class="py-1.5 px-2.5 text-center">Total</th>
                        <th class="py-1.5 px-2.5 text-center text-emerald-600 dark:text-emerald-400">Success</th>
                        <th class="py-1.5 px-2.5 text-center text-rose-600 dark:text-rose-400">Failed</th>
                        <th class="py-1.5 px-2.5 text-center">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 text-slate-700 dark:text-slate-200 font-medium">
                    @forelse ($histories as $history)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 divide-x divide-slate-200/60 dark:divide-slate-800 transition">
                            <td class="py-1.5 px-2.5 font-mono text-[11px] text-slate-500">{{ $history->created_at->format('Y-m-d H:i') }}</td>
                            <td class="py-1.5 px-2.5 font-mono font-bold">{{ $history->filename }}</td>
                            <td class="py-1.5 px-2.5">{{ $history->user?->name ?? 'System' }}</td>
                            <td class="py-1.5 px-2.5 text-center font-mono font-bold tabular-nums">{{ $history->total_rows }}</td>
                            <td class="py-1.5 px-2.5 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $history->success_rows }}</td>
                            <td class="py-1.5 px-2.5 text-center font-mono font-bold tabular-nums {{ $history->failed_rows > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">{{ $history->failed_rows }}</td>
                            <td class="py-1.5 px-2.5 text-center whitespace-nowrap">
                                @if ($history->error_file_path && $history->failed_rows > 0)
                                    <a href="{{ route('store.admin.import-history.errors', ['store_slug' => $store->slug, 'history' => $history]) }}"
                                       class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
                                        <span>⬇️</span>
                                        <span>{{ __('messages.error_report') }}</span>
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">{{ __('messages.no_errors') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">{{ __('messages.no_import_history') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
