@extends('layouts.admin.app')

@section('title', __('messages.vouchers_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<script nonce="{{ $cspNonce }}">
window.voucherStudioData = function () {
    return {
        // Active template properties for live preview
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
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.sidebar_setup') ?? 'Store & Hardware Setup' }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.vouchers_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.vouchers_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Print Sample Preview Button --}}
            @if($selectedTemplate)
                <a href="{{ route('store.admin.vouchers.preview', ['store_slug' => $store->slug, 'voucher' => $selectedTemplate->id]) }}"
                   target="_blank"
                   class="admin-secondary-btn flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>{{ __('messages.vouchers_print_sample') }}</span>
                </a>
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
         TEMPLATE GALLERY TABS
         ============================================================ --}}
    <div class="space-y-2">
        <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono">
            {{ __('messages.vouchers_template_gallery') }} ({{ count($templates) }})
        </h2>
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            @foreach($templates as $tmpl)
                <a href="{{ route('store.admin.vouchers.index', ['store_slug' => $store->slug, 'template_id' => $tmpl->id]) }}"
                   class="px-3.5 py-2 rounded-2xl border text-xs font-bold transition flex items-center gap-2 shrink-0 {{ ($selectedTemplate && $selectedTemplate->id === $tmpl->id) ? 'bg-violet-600 text-white border-violet-600 shadow-md' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black {{ ($selectedTemplate && $selectedTemplate->id === $tmpl->id) ? 'bg-violet-800 text-violet-100' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                        {{ strtoupper($tmpl->paper_size) }}
                    </span>
                    <span>{{ $tmpl->name }}</span>
                    @if($tmpl->is_default)
                        <span class="text-[10px] text-amber-300">★</span>
                    @endif
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
                    <div class="font-bold mb-1">Please fix the following errors:</div>
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
                        1. Format & Visual Preset
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Template Name --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Template Profile Name *
                            </label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $selectedTemplate->name ?? '') }}"
                                   required
                                   class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
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
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Customer Info</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_cashier_name" value="1" x-model="showCashier" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Cashier Name</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_tax_breakdown" value="1" x-model="showTax" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Tax Breakdown</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_discount_line" value="1" x-model="showDiscount" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Discount Line</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <input type="checkbox" name="show_barcode" value="1" x-model="showBarcode" class="w-3.5 h-3.5 rounded text-violet-600">
                            <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Barcode</span>
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
                         'w-full max-w-[480px] text-xs': isA4() || isA5(),
                         'border-2 border-slate-900': stylePreset === 'classic_border',
                         'ring-1 ring-slate-200': stylePreset === 'clean_minimal'
                     }">

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
                                <th class="pb-1">Item</th>
                                <th class="pb-1 text-center">Qty</th>
                                <th class="pb-1 text-right">Amount</th>
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
                            <span>Subtotal:</span>
                            <span class="font-mono">41,000 MMK</span>
                        </div>
                        <div x-show="showDiscount" class="flex justify-between text-rose-600">
                            <span>Promotion Discount (5%):</span>
                            <span class="font-mono">-2,000 MMK</span>
                        </div>
                        <div x-show="showTax" class="flex justify-between text-slate-600">
                            <span>Commercial Tax (5%):</span>
                            <span class="font-mono">1,950 MMK</span>
                        </div>
                        <div class="flex justify-between font-black text-sm border-t border-slate-900 pt-1.5 mt-1">
                            <span>Net Total:</span>
                            <span class="font-mono">40,950 MMK</span>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500 pt-0.5">
                            <span>Paid with KBZPay:</span>
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
@endsection
