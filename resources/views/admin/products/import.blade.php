@extends('layouts.admin.app')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto pb-12">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg sm:rounded-xl p-4 sm:p-5 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 mb-1.5">
                <span>📥</span>
                <span>{{ __('messages.import_products') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit tracking-tight">
                {{ __('messages.import_products') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                {{ __('messages.import_upload_desc') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('store.admin.products.import.template', ['store_slug' => $store->slug]) }}" download="product-import-template.xlsx"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition active:scale-95">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                <span>{{ __('messages.download_product_template') }}</span>
            </a>
            <a href="{{ route('store.admin.products.index', ['store_slug' => $store->slug]) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700 transition">
                <span>&larr;</span>
                <span>{{ __('messages.back_to_products') }}</span>
            </a>
        </div>
    </div>

    {{-- Error Banner --}}
    @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg sm:rounded-xl p-4 text-sm text-rose-800 dark:text-rose-200 space-y-1 shadow-xs">
            <div class="font-bold flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.common_error') }}</span>
            </div>
            @foreach ($errors->all() as $error)
                <div class="text-xs pl-5 list-disc">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Preview Section (If Preview Active) --}}
    @if (session('import_preview'))
        @php $preview = session('import_preview'); @endphp
        <div class="bg-amber-50/70 dark:bg-amber-950/30 border border-amber-300/80 dark:border-amber-800 rounded-lg sm:rounded-xl p-4 sm:p-5 space-y-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-amber-200 dark:border-amber-900/60 pb-3">
                <div>
                    <h3 class="font-black text-amber-950 dark:text-amber-200 text-base sm:text-lg flex items-center gap-2">
                        <span>🔍</span>
                        <span>{{ __('messages.import_preview_ready') }}:</span>
                        <span class="font-mono text-sm bg-white dark:bg-slate-900 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800">{{ $preview['filename'] }}</span>
                    </h3>
                    <p class="text-xs text-amber-800 dark:text-amber-300 mt-0.5">{{ __('messages.import_preview_desc') }}</p>
                </div>
            </div>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 sm:gap-3 text-center">
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-3 shadow-2xs">
                    <div class="text-xl sm:text-2xl font-black text-slate-800 dark:text-slate-100 font-mono">{{ $preview['total'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-slate-400 mt-0.5">{{ __('messages.total_rows') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800/60 rounded-lg p-3 shadow-2xs">
                    <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ $preview['creatable'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-emerald-600/80 dark:text-emerald-400 mt-0.5">{{ __('messages.new_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-violet-200 dark:border-violet-800/60 rounded-lg p-3 shadow-2xs">
                    <div class="text-xl sm:text-2xl font-black text-violet-600 dark:text-violet-400 font-mono">{{ $preview['updatable'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-violet-600/80 dark:text-violet-400 mt-0.5">{{ __('messages.updated_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800/60 rounded-lg p-3 shadow-2xs">
                    <div class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ $preview['skipped_duplicate'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-amber-600/80 dark:text-amber-400 mt-0.5">{{ __('messages.skipped_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-800/60 rounded-lg p-3 shadow-2xs">
                    <div class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-mono">{{ $preview['failed'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-rose-600/80 dark:text-rose-400 mt-0.5">{{ __('messages.failed_products') }}</div>
                </div>
            </div>

            {{-- Preview Table --}}
            @if (!empty($preview['preview_rows']))
                <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-2xs">
                    <table class="w-full text-xs text-left divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-100 dark:bg-slate-800/90 font-bold text-slate-700 dark:text-slate-300">
                            <tr class="divide-x divide-slate-200 dark:divide-slate-700 text-[11px] uppercase">
                                <th class="p-2.5 text-center w-12">#</th>
                                <th class="p-2.5 min-w-[110px]">{{ __('messages.sku') }}</th>
                                <th class="p-2.5 min-w-[180px]">{{ __('messages.name') }}</th>
                                <th class="p-2.5 min-w-[120px]">{{ __('messages.brand') }}</th>
                                <th class="p-2.5 min-w-[130px]">{{ __('messages.category') }}</th>
                                <th class="p-2.5 text-right min-w-[100px]">{{ __('messages.retail_price') }}</th>
                                <th class="p-2.5 text-center w-20">{{ __('messages.stock_status') }}</th>
                                <th class="p-2.5 text-center w-24">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 text-slate-800 dark:text-slate-200 font-medium">
                            @foreach ($preview['preview_rows'] as $row)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 divide-x divide-slate-200/60 dark:divide-slate-800 transition">
                                    <td class="p-2 text-center text-slate-400 font-mono text-[11px]">{{ $row['row'] }}</td>
                                    <td class="p-2 font-mono font-bold text-violet-700 dark:text-violet-300">{{ $row['sku'] }}</td>
                                    <td class="p-2 font-bold">{{ $row['name'] }}</td>
                                    <td class="p-2 text-slate-600 dark:text-slate-400">{{ $row['brand'] ?? '—' }}</td>
                                    <td class="p-2 text-slate-600 dark:text-slate-400">{{ $row['category'] ?? '—' }}</td>
                                    <td class="p-2 text-right font-mono font-bold">Ks {{ number_format((float) ($row['retail_price'] ?? 0)) }}</td>
                                    <td class="p-2 text-center">
                                        @if (($row['stock_status'] ?? null) === 'out_of_stock')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">{{ __('messages.out_of_stock') }}</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ __('messages.in_stock') }}</span>
                                        @endif
                                    </td>
                                    <td class="p-2 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider
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

            {{-- Failed Rows --}}
            @if (!empty($preview['failed_rows']))
                <details class="bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-lg p-3">
                    <summary class="cursor-pointer text-xs font-bold text-rose-800 dark:text-rose-300 flex items-center justify-between">
                        <span>❌ {{ __('messages.failed_products') }} ({{ count($preview['failed_rows']) }})</span>
                        <span class="text-[11px] text-rose-600 font-normal">Click to expand</span>
                    </summary>
                    <div class="mt-2 space-y-1.5 text-xs bg-white dark:bg-slate-900 border border-rose-200/80 dark:border-rose-800/80 rounded p-2.5 max-h-48 overflow-y-auto font-mono">
                        @foreach ($preview['failed_rows'] as $fr)
                            <div class="text-rose-700 dark:text-rose-300 border-b border-rose-100 dark:border-rose-900/40 pb-1 last:border-0">
                                <strong>Row {{ $fr['row'] }}</strong>{{ isset($fr['sku']) ? ' [SKU: ' . $fr['sku'] . ']' : '' }}: {{ $fr['reason'] }}
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

            {{-- Confirm Import Form --}}
            <form method="POST" action="{{ route('store.admin.products.import.confirm', ['store_slug' => $store->slug]) }}"
                  class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-800/80 rounded-lg p-3.5">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ __('messages.duplicate_sku_handling') }}:</label>
                    <select name="duplicate_strategy" class="border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 text-xs bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-amber-500">
                        <option value="skip" {{ $preview['duplicate_strategy'] === 'skip' ? 'selected' : '' }}>{{ __('messages.skip_duplicate_products') }}</option>
                        <option value="update" {{ $preview['duplicate_strategy'] === 'update' ? 'selected' : '' }}>{{ __('messages.update_existing_products') }}</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg font-black text-xs shadow-xs transition active:scale-95">
                    <span>✅</span>
                    <span>{{ __('messages.confirm_import') }}</span>
                </button>
            </form>
        </div>
    @endif

    {{-- Import Result Success Banner --}}
    @if (session('import_result'))
        @php $result = session('import_result'); @endphp
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg sm:rounded-xl p-4 sm:p-5 space-y-3 shadow-sm">
            <h3 class="font-black text-emerald-900 dark:text-emerald-200 text-base sm:text-lg flex items-center gap-2">
                <span>🎉</span>
                <span>{{ __('messages.import_completed') }}</span>
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-sm text-center">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3">
                    <div class="text-xl font-bold font-mono">{{ $result['total'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-slate-400">{{ __('messages.total_rows') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3">
                    <div class="text-xl font-bold font-mono text-emerald-600">{{ $result['imported'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-emerald-600">{{ __('messages.new_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-violet-200 dark:border-violet-800 rounded-lg p-3">
                    <div class="text-xl font-bold font-mono text-violet-600">{{ $result['updated'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-violet-600">{{ __('messages.updated_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                    <div class="text-xl font-bold font-mono text-amber-600">{{ $result['skipped_duplicate'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-amber-600">{{ __('messages.skipped_products') }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-800 rounded-lg p-3">
                    <div class="text-xl font-bold font-mono text-rose-600">{{ $result['failed'] }}</div>
                    <div class="text-[11px] font-bold uppercase text-rose-600">{{ __('messages.failed_products') }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Upload Form Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg sm:rounded-xl p-5 sm:p-6 shadow-xs space-y-5">
        <form action="{{ route('store.admin.products.import', ['store_slug' => $store->slug]) }}" method="POST" enctype="multipart/form-data" class="space-y-4"
              x-data="{ fileName: '', isDragging: false }">
            @csrf
            
            {{-- Modern Drag & Drop Zone --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                    {{ __('messages.import_upload_title') }}
                </label>
                <div class="relative border-2 border-dashed rounded-xl p-6 sm:p-8 text-center transition cursor-pointer"
                     :class="isDragging ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-950/20' : 'border-slate-300 dark:border-slate-700 hover:border-violet-400 dark:hover:border-violet-600 bg-slate-50/60 dark:bg-slate-800/40'"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $refs.fileInput.files[0]?.name || ''">
                    
                    <input type="file" name="file" x-ref="fileInput" accept=".csv,.txt,.xlsx" required
                           @change="fileName = $refs.fileInput.files[0]?.name || ''"
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />

                    <div class="flex flex-col items-center justify-center gap-2">
                        <div class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-950/80 text-violet-600 dark:text-violet-400 grid place-items-center text-2xl shadow-inner">
                            📄
                        </div>
                        <div class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            <span x-show="!fileName">{{ __('messages.import_upload_title') }}</span>
                            <span x-show="fileName" x-text="fileName" class="text-violet-600 dark:text-violet-400 font-mono font-black"></span>
                        </div>
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('messages.import_upload_desc') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.duplicate_sku_handling') }}
                    </label>
                    <select name="duplicate_strategy" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-medium focus:ring-2 focus:ring-violet-500">
                        <option value="skip">{{ __('messages.skip_duplicate_products') }}</option>
                        <option value="update">{{ __('messages.update_existing_products') }}</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full min-h-[38px] inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white rounded-lg font-black text-xs sm:text-sm shadow-xs transition active:scale-98">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        <span>{{ __('messages.upload_and_preview') }}</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- Supported Columns Guide --}}
        <div class="border-t border-slate-200 dark:border-slate-800 pt-4" x-data="{ guideOpen: false }">
            <button type="button" @click="guideOpen = !guideOpen" class="w-full flex items-center justify-between text-left group">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>💡</span>
                    <span>{{ __('messages.supported_columns_guide') }}</span>
                </span>
                <span class="text-xs text-violet-600 dark:text-violet-400 group-hover:underline font-bold" x-text="guideOpen ? 'Hide' : 'Show'"></span>
            </button>

            <div x-show="guideOpen" x-transition class="mt-3 space-y-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                <div class="bg-slate-100 dark:bg-slate-800/80 rounded-lg p-3 font-mono text-[11px] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 select-all overflow-x-auto">
                    name,sku,brand,category,parent_category,retail_price,wholesale_price,old_price,sale_starts_at,sale_ends_at,stock_status,warranty,return_policy,description,meta_description,image_url,featured,variants
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200/80 dark:border-slate-700/80 space-y-2">
                    <p>
                        <strong>📌 POS / AppSheet Migration:</strong>
                        Product_ID → sku, Product_Name → name, Sale_Price → retail_price, Warranty_Period → warranty. If no stock_status is present, positive Current_Stock sets in_stock.
                    </p>
                    <p>
                        <strong>🏷️ Variants & Specifications:</strong>
                        The <code>variants</code> column accepts a JSON array where each variant holds its own retail_price, wholesale_price, sku, and stock_status:
                        <code class="block font-mono text-[10px] bg-white dark:bg-slate-900 p-1.5 rounded mt-1 select-all border border-slate-200 dark:border-slate-800">[{"name":"Type C","sku":"L2009-TC","retail_price":5000,"wholesale_price":4500,"stock_status":"in_stock"},{"name":"Lightning","sku":"L2009-LT","retail_price":7000,"wholesale_price":6300,"stock_status":"in_stock"}]</code>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Import History Card --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg sm:rounded-xl p-5 sm:p-6 shadow-xs space-y-4">
        <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
            <span>📜</span>
            <span>{{ __('messages.recent_product_imports') }}</span>
        </h2>
        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-lg shadow-2xs">
            <table class="w-full text-left text-xs divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-100 dark:bg-slate-800/90 text-slate-600 dark:text-slate-300 font-bold uppercase text-[11px]">
                    <tr class="divide-x divide-slate-200 dark:divide-slate-700">
                        <th class="p-2.5">Date</th>
                        <th class="p-2.5">File</th>
                        <th class="p-2.5">User</th>
                        <th class="p-2.5 text-center">Total</th>
                        <th class="p-2.5 text-center text-emerald-600 dark:text-emerald-400">Success</th>
                        <th class="p-2.5 text-center text-rose-600 dark:text-rose-400">Failed</th>
                        <th class="p-2.5 text-center">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 dark:divide-slate-800 text-slate-700 dark:text-slate-200 font-medium">
                    @forelse ($histories as $history)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 divide-x divide-slate-200/60 dark:divide-slate-800 transition">
                            <td class="p-2.5 font-mono text-[11px] text-slate-500">{{ $history->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-2.5 font-mono font-bold">{{ $history->filename }}</td>
                            <td class="p-2.5">{{ $history->user?->name ?? 'System' }}</td>
                            <td class="p-2.5 text-center font-mono font-bold">{{ $history->total_rows }}</td>
                            <td class="p-2.5 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $history->success_rows }}</td>
                            <td class="p-2.5 text-center font-mono font-bold {{ $history->failed_rows > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400' }}">{{ $history->failed_rows }}</td>
                            <td class="p-2.5 text-center whitespace-nowrap">
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
