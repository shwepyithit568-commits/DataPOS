@extends('layouts.admin.app')

@section('title', __('messages.vouchers_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<script nonce="{{ $cspNonce }}">
window.voucherStudioData = function () {
    return {
        activeTab: {!! json_encode(old('tab', $activeTab ?? 'studio')) !!},
        // Active template properties for live preview
        documentType: {!! json_encode(old('document_type', $selectedTemplate->document_type ?? 'pos_sale')) !!},
        paperSize: {!! json_encode(old('paper_size', $selectedTemplate->paper_size ?? '80mm')) !!},
        stylePreset: {!! json_encode(old('style_preset', $selectedTemplate->style_preset ?? 'clean_minimal')) !!},
        headerTitle: {!! json_encode(old('header_title', $selectedTemplate->header_title ?? $store->name)) !!},
        headerSubtitle: {!! json_encode(old('header_subtitle', $selectedTemplate->header_subtitle ?? '')) !!},
        address: {!! json_encode(old('address', $selectedTemplate->address ?? ($store->address ?? 'Yangon, Myanmar'))) !!},
        phone: {!! json_encode(old('phone', $selectedTemplate->phone ?? ($store->phone ?? '09-123456789'))) !!},
        showLogo: {{ old('show_logo', $selectedTemplate->show_logo ?? true) ? 'true' : 'false' }},
        showQr: {{ old('show_qr', $selectedTemplate->show_qr ?? true) ? 'true' : 'false' }},
        qrType: {!! json_encode(old('qr_type', $selectedTemplate->qr_type ?? 'kpay')) !!},
        qrLabel: {!! json_encode(old('qr_label', $selectedTemplate->qr_label ?? 'Scan to pay with KPay / Wave')) !!},
        showCustomer: {{ old('show_customer_info', $selectedTemplate->show_customer_info ?? true) ? 'true' : 'false' }},
        showCashier: {{ old('show_cashier_name', $selectedTemplate->show_cashier_name ?? true) ? 'true' : 'false' }},
        showTax: {{ old('show_tax_breakdown', $selectedTemplate->show_tax_breakdown ?? true) ? 'true' : 'false' }},
        showDiscount: {{ old('show_discount_line', $selectedTemplate->show_discount_line ?? true) ? 'true' : 'false' }},
        showBarcode: {{ old('show_barcode', $selectedTemplate->show_barcode ?? true) ? 'true' : 'false' }},
        footerGreeting: {!! json_encode(old('footer_greeting', $selectedTemplate->footer_greeting ?? 'Thank you for shopping with us! ကျေးဇူးတင်ပါသည်')) !!},
        footerPolicy: {!! json_encode(old('footer_policy', $selectedTemplate->footer_policy ?? 'Goods once sold are not returnable without receipt.')) !!},
        fontSize: {!! json_encode(old('font_size', $selectedTemplate->font_size ?? 'medium')) !!},

        is80mm: function () { return this.paperSize === '80mm'; },
        is58mm: function () { return this.paperSize === '58mm'; },
        isA4: function () { return this.paperSize === 'a4'; },
        isA5: function () { return this.paperSize === 'a5'; },
        isThermal: function () { return this.paperSize === '80mm' || this.paperSize === '58mm'; }
    };
};
</script>

<div x-data="window.voucherStudioData()" class="w-full space-y-5 sm:space-y-6">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <h1 class="admin-page-title">
                {{ __('messages.vouchers_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.vouchers_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Print Sample Preview Button (visible on Studio tab) --}}
            @if($selectedTemplate)
                <div x-show="activeTab === 'studio'">
                    <a href="{{ route('store.admin.vouchers.preview', ['store_slug' => $store->slug, 'voucher' => $selectedTemplate->id]) }}"
                       target="_blank"
                       class="admin-secondary-btn flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>{{ __('messages.vouchers_print_sample') }}</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         PRIMARY SUB-NAVIGATION TABS
         ============================================================ --}}
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button type="button"
                @click="activeTab = 'studio'"
                :class="activeTab === 'studio' ? 'bg-violet-600 text-white shadow-sm ring-1 ring-violet-500' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            <span>{{ __('messages.vouchers_tab_studio') }}</span>
        </button>

        <button type="button"
                @click="activeTab = 'defaults'"
                :class="activeTab === 'defaults' ? 'bg-violet-600 text-white shadow-sm ring-1 ring-violet-500' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-150 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>{{ __('messages.vouchers_tab_defaults') }}</span>
            <span class="px-1.5 py-0.5 rounded-md text-[10px] font-black" :class="activeTab === 'defaults' ? 'bg-violet-800 text-violet-100' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'">8</span>
        </button>
    </div>

    {{-- ============================================================
         STUDIO TAB (EDITOR & LIVE PREVIEW)
         ============================================================ --}}
    <div x-show="activeTab === 'studio'" class="space-y-5 sm:space-y-6">

    {{-- ============================================================
         TEMPLATE GALLERY TABS
         ============================================================ --}}
    <div class="space-y-2">
        <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono">
            {{ __('messages.vouchers_template_gallery') }} ({{ count($templates) }})
        </h2>
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            @foreach($templates as $tmpl)
                <a href="{{ route('store.admin.vouchers.index', ['store_slug' => $store->slug, 'template_id' => $tmpl->id]) }}"
                   class="px-3.5 py-2.5 rounded-2xl border text-xs font-bold transition flex items-center gap-2.5 shrink-0 {{ ($selectedTemplate && $selectedTemplate->id === $tmpl->id) ? 'bg-violet-600 text-white border-violet-600 shadow-md ring-2 ring-violet-400/40' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    <span class="text-base leading-none">{{ $tmpl->documentTypeIcon() }}</span>
                    <div class="flex flex-col text-left">
                        <div class="flex items-center gap-1.5">
                            <span class="font-extrabold">{{ $tmpl->name }}</span>
                            @if($tmpl->is_default)
                                <span class="text-[10px] text-amber-300" title="Default Template">★</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] {{ ($selectedTemplate && $selectedTemplate->id === $tmpl->id) ? 'text-violet-200' : 'text-slate-400 dark:text-slate-500' }}">
                            <span class="font-mono uppercase font-black px-1 py-0.2 rounded {{ ($selectedTemplate && $selectedTemplate->id === $tmpl->id) ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $tmpl->paper_size }}</span>
                            <span>·</span>
                            <span>{{ $tmpl->documentTypeLabel() }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ============================================================
         STUDIO TWO-COLUMN LAYOUT: EDITOR & LIVE PREVIEW
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- LEFT COLUMN: CUSTOMIZATION CONTROLS (7 Cols) --}}
        <div class="lg:col-span-7 space-y-6">

            @if ($errors->any())
                <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-sm text-rose-800 dark:text-rose-200">
                    <div class="font-bold mb-1">{{ __('messages.fix_errors_prompt') ?? 'Please fix the following errors:' }}</div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ $selectedTemplate ? route('store.admin.vouchers.update', ['store_slug' => $store->slug, 'voucher' => $selectedTemplate->id]) : route('store.admin.vouchers.store', ['store_slug' => $store->slug]) }}"
                  enctype="multipart/form-data"
                  class="space-y-6">
                @csrf
                @if($selectedTemplate)
                    @method('PUT')
                @endif

                {{-- Card 1: Format & Preset --}}
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 font-mono">
                        {{ __('messages.vouchers_section_format') }}
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Template Name --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_template_name') }} *
                            </label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $selectedTemplate->name ?? '') }}"
                                   required
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                        </div>

                        {{-- Target Document Type --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_target_document') }} *
                            </label>
                            <select name="document_type"
                                    x-model="documentType"
                                    required
                                    class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                                <option value="pos_sale">🛒 {{ __('messages.vouchers_doc_pos_sale') }}</option>
                                <option value="repair">🔧 {{ __('messages.vouchers_doc_repair') }}</option>
                                <option value="invoice">🏢 {{ __('messages.vouchers_doc_invoice') }}</option>
                                <option value="mini_slip">📱 {{ __('messages.vouchers_doc_mini_slip') }}</option>
                                <option value="closing">📊 {{ __('messages.vouchers_doc_closing') }}</option>
                                <option value="transaction">💳 {{ __('messages.vouchers_doc_transaction') }}</option>
                                <option value="warranty">🛡️ {{ __('messages.vouchers_doc_warranty') }}</option>
                                <option value="wholesale">📦 {{ __('messages.vouchers_doc_wholesale') }}</option>
                                <option value="general">📄 {{ __('messages.vouchers_doc_general') }}</option>
                            </select>
                        </div>

                        {{-- Paper Size --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_paper_size') }} *
                            </label>
                            <select name="paper_size"
                                    x-model="paperSize"
                                    required
                                    class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                                <option value="80mm">{{ __('messages.vouchers_size_80mm') }}</option>
                                <option value="58mm">{{ __('messages.vouchers_size_58mm') }}</option>
                                <option value="a4">{{ __('messages.vouchers_size_a4') }}</option>
                                <option value="a5">{{ __('messages.vouchers_size_a5') }}</option>
                            </select>
                        </div>

                        {{-- Style Preset --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_style_preset') }} *
                            </label>
                            <select name="style_preset"
                                    x-model="stylePreset"
                                    required
                                    class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                                <option value="clean_minimal">{{ __('messages.vouchers_preset_clean') }}</option>
                                <option value="modern_tech">{{ __('messages.vouchers_preset_tech') }}</option>
                                <option value="classic_border">{{ __('messages.vouchers_preset_classic') }}</option>
                            </select>
                        </div>

                        {{-- Font Size --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_font_size') }} *
                            </label>
                            <select name="font_size"
                                    x-model="fontSize"
                                    required
                                    class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                                <option value="small">{{ __('messages.vouchers_font_small') }}</option>
                                <option value="medium">{{ __('messages.vouchers_font_medium') }}</option>
                                <option value="large">{{ __('messages.vouchers_font_large') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Branding & Contact Info --}}
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 font-mono">
                        2. {{ __('messages.vouchers_header_branding') }}
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Store Title --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_header_title') }} *
                            </label>
                            <input type="text"
                                   name="header_title"
                                   x-model="headerTitle"
                                   required
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                        </div>

                        {{-- Subtitle --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_header_subtitle') }}
                            </label>
                            <input type="text"
                                   name="header_subtitle"
                                   x-model="headerSubtitle"
                                   placeholder="e.g. Sales, Service & Accessories"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                        </div>

                        {{-- Phone / Viber --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_phone') }}
                            </label>
                            <input type="text"
                                   name="phone"
                                   x-model="phone"
                                   placeholder="09-123456789"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                        </div>

                        {{-- Address --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_address') }}
                            </label>
                            <input type="text"
                                   name="address"
                                   x-model="address"
                                   placeholder="Yangon, Myanmar"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                        </div>

                        {{-- Show Logo Toggle & Upload --}}
                        <div class="sm:col-span-2 p-3.5 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-2">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox"
                                       name="show_logo"
                                       value="1"
                                       x-model="showLogo"
                                       class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.vouchers_show_logo') }}</span>
                            </label>
                            <div x-show="showLogo" class="pt-1.5">
                                <input type="file"
                                       name="logo_file"
                                       accept="image/*"
                                       class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-violet-100 file:text-violet-700 hover:file:bg-violet-200">
                                @if($selectedTemplate && $selectedTemplate->logo_path)
                                    <p class="text-[11px] text-emerald-600 mt-1 font-mono">Current logo uploaded: {{ basename($selectedTemplate->logo_path) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Payment QR Code Integration --}}
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 font-mono">
                        3. {{ __('messages.vouchers_qr_section') }}
                    </h3>

                    <div class="space-y-3">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox"
                                   name="show_qr"
                                   value="1"
                                   x-model="showQr"
                                   class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.vouchers_show_qr') }}</span>
                        </label>

                        <div x-show="showQr" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    {{ __('messages.vouchers_qr_type') }}
                                </label>
                                <select name="qr_type"
                                        x-model="qrType"
                                        class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500">
                                    <option value="kpay">{{ __('messages.vouchers_qr_kpay') }}</option>
                                    <option value="wave">{{ __('messages.vouchers_qr_wave') }}</option>
                                    <option value="bank">{{ __('messages.vouchers_qr_bank') }}</option>
                                    <option value="custom">{{ __('messages.vouchers_qr_custom') }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    {{ __('messages.vouchers_qr_label') }}
                                </label>
                                <input type="text"
                                       name="qr_label"
                                       x-model="qrLabel"
                                       placeholder="Scan to pay with KPay / Wave"
                                       class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                    {{ __('messages.vouchers_qr_upload') }}
                                </label>
                                <input type="file"
                                       name="qr_file"
                                       accept="image/*"
                                       class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-violet-100 file:text-violet-700 hover:file:bg-violet-200">
                                @if($selectedTemplate && $selectedTemplate->qr_image_path)
                                    <p class="text-[11px] text-emerald-600 mt-1 font-mono">Current QR uploaded: {{ basename($selectedTemplate->qr_image_path) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Visibility Options & Footer Notes --}}
                <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 font-mono">
                        4. {{ __('messages.vouchers_visibility_options') }}
                    </h3>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_customer_info" value="1" x-model="showCustomer" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ __('messages.vouchers_show_customer') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_cashier_name" value="1" x-model="showCashier" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ __('messages.vouchers_show_cashier') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_tax_breakdown" value="1" x-model="showTax" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ __('messages.vouchers_show_tax') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_discount_line" value="1" x-model="showDiscount" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ __('messages.vouchers_show_discount') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_barcode" value="1" x-model="showBarcode" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ __('messages.vouchers_show_barcode') }}</span>
                        </label>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_footer_greeting') }}
                            </label>
                            <input type="text"
                                   name="footer_greeting"
                                   x-model="footerGreeting"
                                   placeholder="Thank you for shopping with us! ကျေးဇူးတင်ပါသည်"
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                {{ __('messages.vouchers_footer_policy') }}
                            </label>
                            <textarea name="footer_policy"
                                      x-model="footerPolicy"
                                      rows="2"
                                      placeholder="Goods once sold are not returnable without receipt."
                                      class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm"></textarea>
                        </div>
                    </div>

                    {{-- Default toggle --}}
                    <div class="pt-2">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox"
                                   name="is_default"
                                   value="1"
                                   {{ old('is_default', $selectedTemplate->is_default ?? false) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                            <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.vouchers_set_default') }}</span>
                        </label>
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold shadow-md transition">
                        {{ __('messages.vouchers_save_template') }}
                    </button>
                </div>

            </form>
        </div>

        {{-- RIGHT COLUMN: REAL-TIME LIVE PREVIEW PANE (5 Cols) --}}
        <div class="lg:col-span-5 sticky top-20 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ __('messages.vouchers_live_preview') }}
                </span>
                <span class="text-xs font-mono font-bold text-violet-600 dark:text-violet-400" x-text="paperSize.toUpperCase() + ' · ' + stylePreset"></span>
            </div>

            {{-- Preview Wrapper --}}
            <div class="bg-slate-100 dark:bg-slate-950 p-4 sm:p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-inner overflow-x-auto flex justify-center">

                {{-- THE RECEIPT / INVOICE MOCKUP --}}
                <div class="bg-white text-slate-900 p-5 rounded-lg shadow-xl font-sans transition-all duration-200"
                     :class="{
                         'w-[300px] text-xs': is80mm(),
                         'w-[230px] text-[11px]': is58mm(),
                         'w-full max-w-[500px] text-xs': isA4() || isA5(),
                         'border-2 border-slate-900': stylePreset === 'classic_border',
                         'ring-1 ring-slate-200': stylePreset === 'clean_minimal'
                     }">

                    {{-- ── INVOICE MODE: Commercial Tax Invoice Layout (When documentType === 'invoice') ── --}}
                    <div x-show="documentType === 'invoice'" class="space-y-3.5">
                        {{-- Top Dual Header --}}
                        <div class="flex items-start justify-between gap-3 border-b pb-3 border-slate-200">
                            <div class="space-y-0.5">
                                <div x-show="showLogo" class="w-9 h-9 bg-slate-900 text-white rounded-lg flex items-center justify-center font-black text-xs mb-1">
                                    DP
                                </div>
                                <h4 class="font-black text-xs uppercase tracking-wide text-slate-900" x-text="headerTitle || 'DataPOS Store'"></h4>
                                <p x-show="headerSubtitle" class="text-[9.5px] text-slate-500" x-text="headerSubtitle"></p>
                                <p x-show="address" class="text-[9.5px] text-slate-600" x-text="address"></p>
                                <p x-show="phone" class="text-[9.5px] text-slate-600" x-text="'Tel: ' + phone"></p>
                            </div>
                            <div class="text-right space-y-0.5">
                                <div class="font-black text-[11px] uppercase tracking-wider text-violet-700">{{ __('messages.invoice_commercial_title') }}</div>
                                <div class="font-mono font-black text-xs text-slate-900">#ORD-2026-0001</div>
                                <div class="text-[9px] text-slate-500">{{ now()->format('M j, Y · h:i A') }}</div>
                                <div><span class="inline-block px-1.5 py-0.5 rounded bg-violet-100 text-violet-800 font-mono font-bold text-[8.5px]">TIN: 104889230</span></div>
                                <div x-show="showBarcode" class="mt-1">
                                    <div class="h-3.5 bg-repeating-linear-gradient w-20 ml-auto" style="background: repeating-linear-gradient(90deg, #000 0px, #000 1.5px, #fff 1.5px, #fff 3px);"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Billed To & Status Grid --}}
                        <div class="grid grid-cols-2 gap-2 text-[9.5px]">
                            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 space-y-0.5">
                                <div class="font-black text-slate-800 uppercase tracking-wider text-[8.5px]">{{ __('messages.invoice_billed_to') }}</div>
                                <div class="font-bold text-slate-900" x-show="showCustomer">UAT Customer</div>
                                <div class="text-slate-600" x-show="showCustomer">09-987654321</div>
                                <div class="text-slate-500" x-show="showCustomer">Bahan Township, Yangon</div>
                                <div class="text-slate-500 italic" x-show="!showCustomer">{{ __('messages.walk_in_customer') }}</div>
                            </div>
                            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 space-y-0.5">
                                <div class="font-black text-slate-800 uppercase tracking-wider text-[8.5px]">{{ __('messages.invoice_status') }}</div>
                                <div class="flex flex-wrap gap-1 mt-0.5">
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[8px]">DELIVERED</span>
                                    <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[8px]">UNPAID</span>
                                </div>
                                <div x-show="showCashier" class="text-slate-500 pt-0.5">Sales: Staff</div>
                            </div>
                        </div>

                        {{-- Itemized Table --}}
                        <table class="w-full text-left text-[10px] border-b border-slate-200">
                            <thead>
                                <tr class="border-b border-slate-200 text-[8.5px] text-slate-500 uppercase tracking-wider">
                                    <th class="pb-1 w-5">#</th>
                                    <th class="pb-1">{{ __('messages.invoice_col_item') }}</th>
                                    <th class="pb-1 text-center">{{ __('messages.invoice_col_qty') }}</th>
                                    <th class="pb-1 text-right">{{ __('messages.invoice_col_unit_price') }}</th>
                                    <th class="pb-1 text-right">{{ __('messages.invoice_col_amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="py-1.5 text-slate-400">1</td>
                                    <td class="py-1.5 font-bold text-slate-900">
                                        <div>UAT Phone Product A</div>
                                        <div class="text-[8.5px] text-slate-500 font-normal">256GB Midnight Black</div>
                                    </td>
                                    <td class="py-1.5 text-center font-mono">2</td>
                                    <td class="py-1.5 text-right font-mono">350,000</td>
                                    <td class="py-1.5 text-right font-mono font-bold">700,000</td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- Totals & QR Grid --}}
                        <div class="grid grid-cols-2 gap-2.5 items-start">
                            {{-- QR Code Left --}}
                            <div>
                                <div x-show="showQr" class="p-1.5 rounded-lg bg-slate-50 border border-dashed border-slate-200 flex items-center gap-2">
                                    <div class="w-10 h-10 bg-white border border-slate-400 flex items-center justify-center font-mono text-[8px] font-bold shrink-0">
                                        [ QR ]
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-[9px] text-slate-800 truncate" x-text="qrLabel"></div>
                                        <div class="text-[8px] text-slate-500">{{ __('messages.receipt_scan_to_pay') }}</div>
                                    </div>
                                </div>
                            </div>
                            {{-- Totals Right --}}
                            <div class="space-y-0.5 text-[10px]">
                                <div class="flex justify-between text-slate-600">
                                    <span>{{ __('messages.subtotal') }}:</span>
                                    <span class="font-mono">700,000 Ks</span>
                                </div>
                                <div x-show="showDiscount" class="flex justify-between text-rose-600">
                                    <span>{{ __('messages.discount') }}:</span>
                                    <span class="font-mono">-0 Ks</span>
                                </div>
                                <div x-show="showTax" class="flex justify-between text-slate-600">
                                    <span>{{ __('messages.commercial_tax') }} (5%):</span>
                                    <span class="font-mono">+ 35,000 Ks</span>
                                </div>
                                <div class="flex justify-between font-black text-[11px] text-violet-700 bg-violet-50 p-1.5 rounded border border-violet-200 mt-1">
                                    <span>{{ __('messages.invoice_total_due') }}:</span>
                                    <span class="font-mono">735,000 Ks</span>
                                </div>
                            </div>
                        </div>

                        {{-- Signatures & Stamp Block (visible on A4/A5) --}}
                        <div x-show="isA4() || isA5()" class="grid grid-cols-3 gap-2 pt-2.5 border-t border-slate-200 text-center text-[8.5px] text-slate-500">
                            <div>
                                <div class="border-b border-dashed border-slate-400 pb-4"></div>
                                <div class="font-bold text-slate-700 mt-0.5">{{ __('messages.invoice_prepared_by') }}</div>
                            </div>
                            <div>
                                <div class="border-b border-dashed border-slate-400 pb-4"></div>
                                <div class="font-bold text-slate-700 mt-0.5">{{ __('messages.invoice_received_by') }}</div>
                            </div>
                            <div class="border border-dashed border-slate-300 rounded p-1 flex items-center justify-center text-[7.5px] font-bold uppercase text-slate-400">
                                {{ __('messages.invoice_company_stamp') }}
                            </div>
                        </div>

                        {{-- Footer Notes --}}
                        <div class="text-center pt-2 border-t border-dashed border-slate-200 space-y-0.5">
                            <p x-show="footerGreeting" class="font-bold text-[9.5px] text-slate-800" x-text="footerGreeting"></p>
                            <p x-show="footerPolicy" class="text-[8.5px] text-slate-500 italic" x-text="footerPolicy"></p>
                        </div>
                    </div>

                    {{-- ── RETAIL SLIP MODE: Standard POS Receipt Layout (When documentType !== 'invoice') ── --}}
                    <div x-show="documentType !== 'invoice'">
                        {{-- Store Header --}}
                        <div class="text-center space-y-1">
                            <div x-show="showLogo" class="w-10 h-10 mx-auto bg-slate-900 text-white rounded-lg flex items-center justify-center font-black text-sm mb-1">
                                DP
                            </div>
                            <h4 class="font-black text-sm uppercase tracking-wide" x-text="headerTitle || 'DataPOS Store'"></h4>
                            <p x-show="headerSubtitle" class="text-[10px] text-slate-500" x-text="headerSubtitle"></p>
                            <p x-show="address" class="text-[10px] text-slate-600" x-text="address"></p>
                            <p x-show="phone" class="text-[10px] text-slate-600" x-text="'Tel/Viber: ' + phone"></p>
                        </div>

                        <div class="my-3 border-t border-dashed border-slate-300"></div>

                        {{-- Invoice Metadata --}}
                        <div class="space-y-0.5 text-[11px]">
                            <div class="flex justify-between font-bold">
                                <span>Receipt #:</span>
                                <span class="font-mono">#INV-2026-8899</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Date & Time:</span>
                                <span>{{ now()->format('Y-m-d H:i') }}</span>
                            </div>
                            <div x-show="showCashier" class="flex justify-between text-slate-600">
                                <span>Cashier:</span>
                                <span>Mg Min (POS-01)</span>
                            </div>
                            <div x-show="showCustomer" class="flex justify-between text-slate-600">
                                <span>Customer:</span>
                                <span>Daw Hla Hla (0998765432)</span>
                            </div>
                        </div>

                        <div class="my-3 border-t border-slate-300"></div>

                        {{-- Sample Line Items Table --}}
                        <table class="w-full text-left text-[11px]">
                            <thead>
                                <tr class="border-b border-slate-200 text-[10px] text-slate-500 uppercase">
                                    <th class="pb-1">{{ __('messages.item') }}</th>
                                    <th class="pb-1 text-center">{{ __('messages.qty') }}</th>
                                    <th class="pb-1 text-right">{{ __('messages.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="py-1.5">
                                        <div class="font-bold">Remax 20W Fast Charger</div>
                                        <div class="text-[10px] text-slate-400">@ 25,000 MMK</div>
                                    </td>
                                    <td class="py-1.5 text-center font-mono">1</td>
                                    <td class="py-1.5 text-right font-mono font-bold">25,000</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5">
                                        <div class="font-bold">Type-C Braided Cable 1m</div>
                                        <div class="text-[10px] text-slate-400">@ 8,000 MMK</div>
                                    </td>
                                    <td class="py-1.5 text-center font-mono">2</td>
                                    <td class="py-1.5 text-right font-mono font-bold">16,000</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="my-3 border-t border-slate-300"></div>

                        {{-- Totals Calculation --}}
                        <div class="space-y-1 text-[11px]">
                            <div class="flex justify-between text-slate-600">
                                <span>{{ __('messages.subtotal') }}:</span>
                                <span class="font-mono">41,000 MMK</span>
                            </div>
                            <div x-show="showDiscount" class="flex justify-between text-rose-600">
                                <span>{{ __('messages.promotion_discount_pct') }}:</span>
                                <span class="font-mono">-2,000 MMK</span>
                            </div>
                            <div x-show="showTax" class="flex justify-between text-slate-600">
                                <span>{{ __('messages.commercial_tax_pct') }}:</span>
                                <span class="font-mono">1,950 MMK</span>
                            </div>
                            <div class="flex justify-between font-black text-sm border-t border-slate-900 pt-1.5 mt-1">
                                <span>{{ __('messages.net_total') }}:</span>
                                <span class="font-mono">40,950 MMK</span>
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-500 pt-0.5">
                                <span>{{ __('messages.paid_with') }} KBZPay:</span>
                                <span class="font-mono">40,950 MMK</span>
                            </div>
                        </div>

                        {{-- QR Code Section --}}
                        <div x-show="showQr" class="my-4 text-center p-2 bg-slate-50 rounded-lg border border-slate-200">
                            <div class="w-16 h-16 mx-auto bg-white border border-slate-900 flex items-center justify-center font-mono font-black text-[10px]">
                                [ QR ]
                            </div>
                            <p class="text-[10px] font-bold text-slate-700 mt-1" x-text="qrLabel"></p>
                        </div>

                        {{-- Barcode Section --}}
                        <div x-show="showBarcode" class="my-3 text-center">
                            <div class="h-6 bg-repeating-linear-gradient w-3/4 mx-auto border-l-2 border-r-2 border-slate-900">
                                <div class="h-6 w-full" style="background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 6px);"></div>
                            </div>
                            <div class="text-[10px] font-mono text-slate-500 mt-0.5">*INV-2026-8899*</div>
                        </div>

                        <div class="my-3 border-t border-dashed border-slate-300"></div>

                        {{-- Footer Notes & Policy --}}
                        <div class="text-center space-y-1">
                            <p x-show="footerGreeting" class="font-bold text-[11px] text-slate-800" x-text="footerGreeting"></p>
                            <p x-show="footerPolicy" class="text-[10px] text-slate-500 italic" x-text="footerPolicy"></p>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>
    </div>
    {{-- /STUDIO TAB --}}

    {{-- ============================================================
         DOCUMENT PAPER DEFAULTS TAB
         ============================================================ --}}
    <div x-show="activeTab === 'defaults'" class="space-y-6" x-cloak>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>{{ __('messages.vouchers_tab_defaults') }}</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ __('messages.vouchers_doc_defaults_desc') }}
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('store.admin.vouchers.document_defaults', ['store_slug' => $store->slug]) }}" class="mt-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($documentTypes as $doc)
                        @php
                            $currentSize = $documentDefaults[$doc['key']] ?? '80mm';
                        @endphp
                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 hover:border-violet-300 dark:hover:border-violet-700/50 transition-colors flex flex-col justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                                    @if($doc['icon'] === 'pos_sale')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    @elseif($doc['icon'] === 'closing')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    @elseif($doc['icon'] === 'repair')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    @elseif($doc['icon'] === 'invoice')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @elseif($doc['icon'] === 'transaction')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    @elseif($doc['icon'] === 'eload')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    @elseif($doc['icon'] === 'warranty')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        {{ $doc['title'] }}
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                        {{ $doc['desc'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-2.5 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between gap-3">
                                <label for="default_{{ $doc['key'] }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                    {{ __('messages.vouchers_paper_size') }}:
                                </label>
                                <div class="relative w-48">
                                    <select id="default_{{ $doc['key'] }}"
                                            name="defaults[{{ $doc['key'] }}]"
                                            class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 shadow-sm">
                                        <option value="80mm" {{ $currentSize === '80mm' ? 'selected' : '' }}>
                                            80mm Thermal (POS)
                                        </option>
                                        <option value="58mm" {{ $currentSize === '58mm' ? 'selected' : '' }}>
                                            58mm Thermal (Mini)
                                        </option>
                                        <option value="a5" {{ $currentSize === 'a5' ? 'selected' : '' }}>
                                            A5 Half-Sheet
                                        </option>
                                        <option value="a4" {{ $currentSize === 'a4' ? 'selected' : '' }}>
                                            A4 Full-Sheet
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold shadow-md transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ __('messages.vouchers_save_document_defaults') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
