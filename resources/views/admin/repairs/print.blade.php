@php
    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }

    $storeName = $template?->header_title ?: $store->name;
    $storeSubtitle = $template?->header_subtitle ?: ($store->tagline ?: __('messages.repair_header_subtitle'));
    $storeAddress = $template?->address ?: ($store->address ?: 'Yangon, Myanmar');
    $storePhone = $template?->phone ?: ($store->phone ?: null);
    $storeLogo = ($template?->show_logo && $template?->logoUrl()) ? $template->logoUrl() : null;

    $footerGreeting = $template?->footer_greeting ?: __('messages.repair_ticket_footer');
    $footerPolicy = $template?->footer_policy ?: ($repair->warranty_notes ?: null);

    $outstanding = (float) $repair->outstanding();
    $paid = (float) $repair->paidAmount();
    $charge = $repair->final_charge !== null ? (float) $repair->final_charge : (float) $repair->estimated_charge;

    $customerName = $repair->customer?->name ?: ($repair->contact_name ?: __('messages.repair_walk_in'));
    $customerPhone = $repair->contact_phone ?: ($repair->customer?->phone ?: null);

    $paperSize = in_array($paperSize, ['58mm', '80mm', 'a5', 'a4'], true) ? $paperSize : '80mm';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('messages.repair_ticket_heading') }} {{ $repair->job_number }} — {{ $store->name }}</title>

    <script nonce="{{ $cspNonce }}" src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>

    <style>
        @if ($myanmarFontUrl)
        @font-face {
            font-family: 'Noto Sans Myanmar';
            src: url('{{ $myanmarFontUrl }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @endif

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Noto Sans Myanmar', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background: #e2e8f0;
            font-size: 12px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Sticky Top Toolbar ── */
        .toolbar-wrap {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #0f172a;
            color: #f8fafc;
            padding: 8px 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .tool-btn-back {
            background: #1e293b;
            color: #cbd5e1;
            border-color: #334155;
        }
        .tool-btn-back:hover {
            background: #334155;
            color: #fff;
        }
        .tool-btn-print {
            background: #7c3aed;
            color: #fff;
        }
        .tool-btn-print:hover {
            background: #6d28d9;
        }
        .tool-btn-pdf {
            background: #0284c7;
            color: #fff;
        }
        .tool-btn-pdf:hover {
            background: #0369a1;
        }
        .tool-btn-share-jpg {
            background: #0d9488;
            color: #fff;
            box-shadow: 0 2px 6px rgba(13, 148, 136, .3);
        }
        .tool-btn-share-jpg:hover {
            background: #0f766e;
        }
        .tool-btn-share {
            background: #059669;
            color: #fff;
        }
        .tool-btn-share:hover {
            background: #047857;
        }

        /* Compact Paper Size Selector */
        .size-select-wrapper {
            display: inline-flex;
            align-items: center;
            background: #1e293b;
            border-radius: 8px;
            padding: 2px 6px 2px 8px;
            gap: 6px;
            border: 1px solid #334155;
        }
        .size-select-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            white-space: nowrap;
        }
        .size-select {
            background: #0f172a;
            color: #f8fafc;
            border: 1px solid #475569;
            border-radius: 6px;
            padding: 3px 8px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            outline: none;
            transition: all 0.15s ease;
        }
        .size-select:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.25);
        }

        /* Toast notification */
        .print-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 10px 20px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 9999;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .print-toast.show {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        /* ── Ticket Containers ── */
        .ticket-wrapper {
            padding: 16px 8px 40px;
            display: flex;
            justify-content: center;
        }
        .ticket-card {
            background: #ffffff;
            color: #0f172a;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
            position: relative;
            margin: 0 auto;
        }

        /* Paper Size Dimensions */
        .size-58mm {
            width: 58mm;
            padding: 10px 8px;
            font-size: 10.5px;
        }
        .size-80mm {
            width: 80mm;
            padding: 16px 14px;
            font-size: 11.5px;
        }
        .size-a5 {
            width: 148mm;
            padding: 20px 22px;
            font-size: 12px;
        }
        .size-a4 {
            width: 210mm;
            padding: 30px 32px;
            font-size: 13px;
        }

        /* Typography & Components */
        .store-logo {
            max-height: 48px;
            max-width: 140px;
            margin: 0 auto 6px;
            display: block;
            object-fit: contain;
        }
        .header-center { text-align: center; }
        .store-title { font-size: 1.25em; font-weight: 900; line-height: 1.2; }
        .store-sub { font-size: 0.85em; color: #475569; margin-top: 2px; }
        .store-contact { font-size: 0.82em; color: #64748b; margin-top: 2px; }

        .dash-divider {
            border: none;
            border-top: 1px dashed #94a3b8;
            margin: 8px 0;
        }
        .solid-divider {
            border: none;
            border-top: 1px solid #cbd5e1;
            margin: 10px 0;
        }

        .job-title-row {
            text-align: center;
            padding: 3px 0;
        }
        .job-label {
            font-size: 0.9em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
        }
        .job-number {
            font-family: Consolas, 'Courier New', monospace;
            font-size: 1.3em;
            font-weight: 900;
            color: #4338ca;
            letter-spacing: 0.5px;
        }
        .badge-status {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 9999px;
            font-size: 0.78em;
            font-weight: 800;
            background: #ede9fe;
            color: #6d28d9;
            margin-top: 2px;
        }

        /* Specs Grid */
        .specs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92em;
            margin-top: 4px;
        }
        .specs-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .specs-table .lbl {
            color: #64748b;
            font-weight: 600;
            width: 38%;
        }
        .specs-table .val {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        /* Problem & Diagnosis Boxes */
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 8px;
            margin-top: 6px;
            font-size: 0.88em;
        }
        .info-box-label {
            font-size: 0.82em;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .info-box-text {
            color: #1e293b;
            font-weight: 600;
            word-break: break-word;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
            margin-top: 6px;
        }
        .items-table th {
            text-align: left;
            padding: 4px 2px;
            border-bottom: 1.5px solid #0f172a;
            color: #334155;
            font-weight: 800;
            font-size: 0.85em;
        }
        .items-table td {
            padding: 4px 2px;
            border-bottom: 1px dotted #cbd5e1;
            vertical-align: top;
        }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .items-table tfoot td {
            border-bottom: none;
            padding-top: 6px;
            font-weight: 800;
        }

        /* Totals / Financials */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95em;
            margin-top: 6px;
        }
        .totals-table td {
            padding: 2.5px 0;
        }
        .totals-table .total-lbl {
            color: #475569;
            font-weight: 600;
        }
        .totals-table .total-val {
            text-align: right;
            font-weight: 700;
            font-family: Consolas, monospace;
        }
        .totals-table .highlight-debt .total-lbl,
        .totals-table .highlight-debt .total-val {
            color: #dc2626;
            font-weight: 900;
        }

        /* QR Code & Tracking */
        .qr-section {
            text-align: center;
            margin-top: 8px;
            padding-top: 4px;
        }
        .qr-svg-wrap {
            display: inline-block;
            background: #fff;
            padding: 4px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .qr-svg-wrap svg,
        .qr-svg-wrap img,
        .qr-svg-wrap canvas {
            display: block;
            margin: 0 auto;
            width: 72px;
            height: 72px;
        }
        .size-58mm .qr-svg-wrap svg,
        .size-58mm .qr-svg-wrap img,
        .size-58mm .qr-svg-wrap canvas { width: 56px; height: 56px; }
        .size-a5 .qr-svg-wrap svg,
        .size-a5 .qr-svg-wrap img,
        .size-a5 .qr-svg-wrap canvas { width: 84px; height: 84px; }
        .size-a4 .qr-svg-wrap svg,
        .size-a4 .qr-svg-wrap img,
        .size-a4 .qr-svg-wrap canvas { width: 96px; height: 96px; }
        .qr-hint {
            font-size: 0.78em;
            color: #64748b;
            margin-top: 3px;
        }

        /* Signature Sections (A5 & A4) */
        .signature-grid {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 8px;
        }
        .size-58mm .signature-grid,
        .size-80mm .signature-grid {
            display: none;
        }
        .sig-col {
            width: 44%;
            text-align: center;
        }
        .sig-line {
            border-top: 1px dashed #475569;
            margin-top: 32px;
            padding-top: 4px;
            font-size: 0.85em;
            font-weight: 700;
            color: #334155;
        }

        /* Modal Backdrop */
        .share-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }
        .share-modal.show {
            display: flex;
        }
        .share-card {
            background: #ffffff;
            border-radius: 12px;
            max-width: 440px;
            width: 100%;
            padding: 16px 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            text-align: left;
        }
        .share-channel-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #1e293b;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .share-channel-btn:hover {
            background: #f1f5f9;
        }

        /* ── Media Print Overrides ── */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .no-print { display: none !important; }
            .ticket-wrapper { padding: 0 !important; }
            .ticket-card {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            @if ($paperSize === '58mm')
                @page { size: 58mm auto; margin: 2mm; }
            @elseif ($paperSize === '80mm')
                @page { size: 80mm auto; margin: 3mm; }
            @elseif ($paperSize === 'a5')
                @page { size: A5 portrait; margin: 8mm; }
            @elseif ($paperSize === 'a4')
                @page { size: A4 portrait; margin: 10mm; }
            @endif
        }
    </style>
</head>
<body>

    {{-- ── SECTION 1: Fixed Responsive Action Toolbar ── --}}
    <div class="toolbar-wrap no-print">
        <div class="toolbar-left">
            <a href="{{ route('store.admin.repairs.show', [...$storeRouteParams, 'repair' => $repair->id]) }}"
               class="tool-btn tool-btn-back">
                ← <span>{{ __('messages.back') }}</span>
            </a>

            {{-- Compact Paper Size Dropdown --}}
            <div class="size-select-wrapper">
                <label for="repairPaperSizeSelect" class="size-select-label">📄 {{ __('messages.paper_size') }}:</label>
                <select id="repairPaperSizeSelect" class="size-select">
                    <option value="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id, 'paper_size' => '58mm']) }}" {{ $paperSize === '58mm' ? 'selected' : '' }}>58mm (POS)</option>
                    <option value="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id, 'paper_size' => '80mm']) }}" {{ $paperSize === '80mm' ? 'selected' : '' }}>80mm (POS)</option>
                    <option value="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id, 'paper_size' => 'a5']) }}" {{ $paperSize === 'a5' ? 'selected' : '' }}>A5 (Half)</option>
                    <option value="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id, 'paper_size' => 'a4']) }}" {{ $paperSize === 'a4' ? 'selected' : '' }}>A4 (Full)</option>
                </select>
            </div>
        </div>

        <div class="toolbar-right">
            <button type="button" class="tool-btn tool-btn-print" id="btnPrint" data-repair-print>
                🖨️ <span>{{ __('messages.repair_print_ticket') }}</span>
            </button>

            <button type="button" class="tool-btn tool-btn-pdf" id="btnDownloadPdf" data-repair-download-pdf>
                📥 <span>{{ __('messages.vouchers_save_pdf') }}</span>
            </button>

            <button type="button" class="tool-btn tool-btn-share-jpg" id="btnShareJpg" title="{{ __('messages.vouchers_copy_jpg') }}">
                🖼️ <span>{{ __('messages.vouchers_share_jpg') }}</span>
            </button>

            <button type="button" class="tool-btn tool-btn-share" id="btnShare" data-repair-share-open>
                📲 <span>{{ __('messages.repair_share_customer') }}</span>
            </button>
        </div>
    </div>

    {{-- ── SECTION 2: Ticket Document Container ── --}}
    <div class="ticket-wrapper">
        <div class="ticket-card size-{{ $paperSize }}" id="ticketDocument">

            {{-- Store Branding Header --}}
            <div class="header-center">
                @if ($storeLogo)
                    <img src="{{ $storeLogo }}" alt="{{ $storeName }}" class="store-logo" />
                @endif
                <div class="store-title">{{ $storeName }}</div>
                @if ($storeSubtitle)
                    <div class="store-sub">{{ $storeSubtitle }}</div>
                @endif
                @if ($storeAddress || $storePhone)
                    <div class="store-contact">
                        {{ $storeAddress }}
                        @if ($storePhone) · 📞 {{ $storePhone }} @endif
                    </div>
                @endif
            </div>

            <hr class="dash-divider">

            {{-- Job Header --}}
            <div class="job-title-row">
                <div class="job-label">{{ __('messages.repair_ticket_heading') }}</div>
                <div class="job-number">{{ $repair->job_number }}</div>
                <div>
                    <span class="badge-status">{{ __('messages.repair_status_' . $repair->status) }}</span>
                </div>
            </div>

            <hr class="dash-divider">

            {{-- Handover & Customer Meta Specs --}}
            <table class="specs-table">
                <tr>
                    <td class="lbl">{{ __('messages.repair_received_at') }}:</td>
                    <td class="val">{{ $repair->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @if ($repair->estimated_completion)
                    <tr>
                        <td class="lbl">{{ __('messages.repair_ticket_est_completion') }}:</td>
                        <td class="val">{{ $repair->estimated_completion->format('d/m/Y') }}</td>
                    </tr>
                @endif
                @if ($repair->voucher_no)
                    <tr>
                        <td class="lbl">{{ __('messages.repair_voucher_no') }}:</td>
                        <td class="val font-mono">{{ $repair->voucher_no }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="lbl">{{ __('messages.repair_ticket_customer') }}:</td>
                    <td class="val">{{ $customerName }}</td>
                </tr>
                @if ($customerPhone)
                    <tr>
                        <td class="lbl">{{ __('messages.repair_contact_phone') }}:</td>
                        <td class="val">{{ $customerPhone }}</td>
                    </tr>
                @endif
            </table>

            <hr class="dash-divider">

            {{-- Device Specifications --}}
            <table class="specs-table">
                @if ($repair->brand)
                    <tr>
                        <td class="lbl">{{ __('messages.repair_brand') }}:</td>
                        <td class="val">{{ $repair->brand }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="lbl">{{ __('messages.repair_device_type') }}:</td>
                    <td class="val">{{ $repair->category ?: $repair->device_type }}</td>
                </tr>
                @if ($repair->model)
                    <tr>
                        <td class="lbl">{{ __('messages.repair_model') }}:</td>
                        <td class="val">{{ $repair->model }}</td>
                    </tr>
                @endif
                @if ($repair->color || $repair->storage)
                    <tr>
                        <td class="lbl">{{ __('messages.repair_color') }} / {{ __('messages.repair_storage') }}:</td>
                        <td class="val">{{ $repair->color ?? '—' }} / {{ $repair->storage ?? '—' }}</td>
                    </tr>
                @endif
                @if ($repair->imei_serial)
                    <tr>
                        <td class="lbl">{{ __('messages.imei') }} / {{ __('messages.serial') }}:</td>
                        <td class="val font-mono">{{ $repair->imei_serial }}</td>
                    </tr>
                @endif
                @if ($repair->pattern_lock)
                    <tr>
                        <td class="lbl">{{ __('messages.pattern_lock') }}:</td>
                        <td class="val font-mono">{{ str_replace('-', ' → ', $repair->pattern_lock) }}</td>
                    </tr>
                @endif
                @if ($repair->technician)
                    <tr>
                        <td class="lbl">{{ __('messages.repair_technician') }}:</td>
                        <td class="val">{{ $repair->technician->name }}</td>
                    </tr>
                @endif
            </table>

            {{-- Problem & Condition Details --}}
            <div class="info-box">
                <span class="info-box-label">⚠️ {{ __('messages.repair_ticket_problem') }}</span>
                <div class="info-box-text">{{ $repair->reported_problem }}</div>
            </div>

            @if ($repair->intake_condition)
                <div class="info-box">
                    <span class="info-box-label">🔍 {{ __('messages.repair_intake_condition') }}</span>
                    <div class="info-box-text">{{ $repair->intake_condition }}</div>
                </div>
            @endif

            @if ($repair->accessories)
                <div class="info-box">
                    <span class="info-box-label">🎒 {{ __('messages.repair_accessories') }}</span>
                    <div class="info-box-text">{{ $repair->accessories }}</div>
                </div>
            @endif

            {{-- Parts & Services Table (if any) --}}
            @if ($repair->items->isNotEmpty())
                <hr class="dash-divider">
                <div class="job-label" style="font-size:0.82em; margin-bottom:2px;">
                    🛠️ {{ __('messages.repair_ticket_items') }}
                </div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.repair_item_name') }}</th>
                            <th class="text-center" style="width:15%">{{ __('messages.repair_item_qty') }}</th>
                            <th class="text-right" style="width:25%">{{ __('messages.repair_item_price') }}</th>
                            <th class="text-right" style="width:25%">{{ __('messages.repair_item_subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($repair->items as $item)
                            <tr>
                                <td>
                                    {{ $item->name }}
                                    @if ($item->isPart())
                                        <span style="color:#64748b; font-size:0.85em;">({{ __('messages.repair_item_part') }})</span>
                                    @endif
                                </td>
                                <td class="text-center font-mono">{{ format_quantity($item->quantity, $store) }}</td>
                                <td class="text-right font-mono">{{ format_currency($item->unit_price, $store) }}</td>
                                <td class="text-right font-mono font-bold">{{ format_currency($item->subtotal, $store) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">{{ __('messages.repair_items_total') }}</td>
                            <td class="text-right font-mono font-bold">{{ format_currency($repair->itemsTotal(), $store) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            <hr class="dash-divider">

            {{-- Financial Breakdown --}}
            <table class="totals-table">
                @if ($repair->final_charge !== null)
                    <tr>
                        <td class="total-lbl">{{ __('messages.repair_final_charge') }}:</td>
                        <td class="total-val">{{ format_currency($repair->final_charge, $store) }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="total-lbl">{{ __('messages.repair_estimated_charge') }}:</td>
                        <td class="total-val">{{ format_currency($repair->estimated_charge, $store) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="total-lbl">{{ __('messages.repair_ticket_paid') }}:</td>
                    <td class="total-val" style="color:#059669;">{{ format_currency($paid, $store) }}</td>
                </tr>
                <tr class="{{ $outstanding > 0 ? 'highlight-debt' : '' }}">
                    <td class="total-lbl">{{ __('messages.repair_ticket_balance') }}:</td>
                    <td class="total-val">{{ format_currency($outstanding, $store) }}</td>
                </tr>
            </table>

            {{-- Warranty Notes & Terms --}}
            @if ($footerPolicy)
                <div class="info-box" style="margin-top:8px;">
                    <span class="info-box-label">🛡️ {{ __('messages.repair_warranty_notes') }}</span>
                    <div class="info-box-text">{{ $footerPolicy }}</div>
                </div>
            @endif

            {{-- Customer Live Status QR Code --}}
            @if (!empty($trackingQrDataUri) || !empty($trackingQrSvg))
                <div class="qr-section">
                    <div class="qr-svg-wrap">
                        @if (!empty($trackingQrDataUri))
                            <img src="{{ $trackingQrDataUri }}" width="72" height="72" alt="QR Code" style="display:block; margin:0 auto; width:72px; height:72px;" />
                            <canvas class="qr-code-canvas" width="180" height="180" style="display:none; margin:0 auto; width:72px; height:72px;"></canvas>
                        @else
                            {!! $trackingQrSvg !!}
                        @endif
                    </div>
                    <div class="qr-hint">
                        📱 {{ __('messages.repair_scan_to_track') }}
                    </div>
                </div>
            @endif

            {{-- PDF exports are always A5, so keep signatures in the DOM and hide them on thermal previews. --}}
            <div class="signature-grid">
                <div class="sig-col">
                    <div class="sig-line">
                        {{ __('messages.repair_ticket_customer') }} (Customer Signature)
                    </div>
                </div>
                <div class="sig-col">
                    <div class="sig-line">
                        {{ __('messages.repair_technician') }} (Technician Signature)
                    </div>
                </div>
            </div>

            <hr class="dash-divider" style="margin-top:10px;">

            {{-- Footer Note & Timestamp --}}
            <div style="text-align:center; font-size:0.75em; color:#64748b; margin-top:4px;">
                {{ $footerGreeting }}
            </div>
            <div style="text-align:center; font-size:0.72em; color:#94a3b8; margin-top:2px;">
                {{ __('messages.repair_printed_date') }}: {{ now()->format('d/m/Y H:i') }} · {{ auth()->user()?->name ?? 'POS' }}
            </div>

        </div>
    </div>

    {{-- ── SECTION 3: Customer Share Modal Dialog ── --}}
    <div class="share-modal no-print" id="shareModal" data-repair-share-backdrop>
        <div class="share-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="font-size:15px; font-weight:800; color:#0f172a;">
                    📲 {{ __('messages.repair_share_modal_title') }}
                </h3>
                <button type="button" data-repair-share-close style="border:none; background:transparent; font-size:18px; cursor:pointer; color:#64748b;">✕</button>
            </div>
            <p style="font-size:12px; color:#64748b; margin-bottom:14px;">
                {{ __('messages.repair_share_modal_desc') }}
            </p>

            {{-- 1. Copy Full Voucher Text (Social Apps ready) --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="copy-text" style="background:#0284c7; color:#fff; border-color:#0369a1;">
                <span>📋</span>
                <span id="copyVoucherTextLabel">{{ __('messages.repair_copy_voucher_text') }}</span>
            </button>

            {{-- 2. Share / Copy JPG Image --}}
            <button type="button" class="share-channel-btn" id="modalBtnShareJpg" data-repair-share-channel="jpg" style="background:#0d9488; color:#fff; border-color:#0f766e;">
                <span>🖼️</span>
                <span>{{ __('messages.vouchers_share_jpg') }} ({{ __('messages.vouchers_copy_jpg') }})</span>
            </button>

            {{-- 3. Viber Channel --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="viber">
                <span style="color:#7360f2; font-size:16px;">💬</span>
                <span>{{ __('messages.repair_share_viber') }}</span>
            </button>

            {{-- 4. Telegram Channel --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="telegram">
                <span style="color:#229ed9; font-size:16px;">✈️</span>
                <span>{{ __('messages.repair_share_telegram') }}</span>
            </button>

            {{-- 5. WhatsApp Channel --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="whatsapp">
                <span style="color:#25d366; font-size:16px;">🟢</span>
                <span>{{ __('messages.repair_share_whatsapp') }}</span>
            </button>

            {{-- 6. Native Share with File (if supported) --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="native" style="background:#7c3aed; color:#fff; border-color:#6d28d9;">
                <span>📄</span>
                <span>{{ __('messages.share_pdf') }}</span>
            </button>

            {{-- 7. Copy Link Only --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="copy">
                <span>🔗</span>
                <span id="copyLinkText">{{ __('messages.repair_copy_track_link') }}</span>
            </button>
        </div>
    </div>

    {{-- ── SECTION 4: Script for Dynamic PDF & Social Sharing ── --}}
    <script nonce="{{ $cspNonce }}">
        var paperSize = @js($paperSize);
        var pdfPaperSize = 'a5';
        var jobNumber = @js($repair->job_number);
        var trackingUrl = @js($trackingUrl ?? url()->current());
        var storeName = @js($storeName);
        var deviceLabel = @js($repair->device_model ?: ($repair->brand ?: 'Device'));
        var imeiSerial = @js($repair->imei ?: ($repair->serial_number ?: null));
        var dateLabel = @js($repair->created_at->format('d/m/Y H:i'));
        var chargeLabel = @js(format_currency($charge, $store));
        var paidLabel = @js(format_currency($paid, $store));
        var outstandingLabel = @js(format_currency($outstanding, $store));
        var hasOutstanding = @js($outstanding > 0);
        var customerPhone = @js($customerPhone ? preg_replace('/[^0-9]/', '', (string)$customerPhone) : null);
        var storePhone = @js($storePhone);

        var pdfDimensions = {
            '58mm': { unit: 'mm', format: [58, 200], orientation: 'portrait' },
            '80mm': { unit: 'mm', format: [80, 240], orientation: 'portrait' },
            'a5': { unit: 'mm', format: 'a5', orientation: 'portrait' },
            'a4': { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        function initQrCanvas() {
            var wraps = document.querySelectorAll('.qr-svg-wrap');
            wraps.forEach(function(wrap) {
                var img = wrap.querySelector('img');
                var canvas = wrap.querySelector('canvas.qr-code-canvas');
                if (img && canvas) {
                    var render = function() {
                        var w = img.naturalWidth || 180;
                        var h = img.naturalHeight || 180;
                        canvas.width = w;
                        canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.imageSmoothingEnabled = false;
                        ctx.drawImage(img, 0, 0, w, h);
                        canvas.style.display = 'block';
                        img.style.display = 'none';
                    };
                    if (img.complete && img.naturalWidth > 0) {
                        render();
                    } else {
                        img.onload = render;
                    }
                }
            });
        }
        document.addEventListener('DOMContentLoaded', initQrCanvas);
        initQrCanvas();

        var pdfConfig = {
            margin: [8, 8, 8, 8],
            filename: 'Repair_Ticket_' + jobNumber + '_A5.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                logging: false,
                onclone: function(clonedDocument) {
                    var clonedTicket = clonedDocument.getElementById('ticketDocument');
                    if (!clonedTicket) return;

                    clonedTicket.classList.remove('size-58mm', 'size-80mm', 'size-a5', 'size-a4');
                    clonedTicket.classList.add('size-a5');
                }
            },
            jsPDF: pdfDimensions[pdfPaperSize]
        };

        function stampQrOnPdfCanvas(worker, ticket, rootRect, cwRect) {
            var canvas = worker.prop ? worker.prop.canvas : null;
            var qrSource = ticket.querySelector('.qr-svg-wrap img') || ticket.querySelector('.qr-svg-wrap canvas');

            if (canvas && rootRect && cwRect && qrSource) {
                var scale = canvas.width / rootRect.width;
                var boxX = (cwRect.left - rootRect.left) * scale;
                var boxY = (cwRect.top - rootRect.top) * scale;
                var boxW = cwRect.width * scale;
                var boxH = cwRect.height * scale;

                var pad = 4 * scale;
                var drawW = boxW - pad * 2;
                var drawH = boxH - pad * 2;
                var drawX = boxX + pad;
                var drawY = boxY + pad;

                var ctx = canvas.getContext('2d');
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.imageSmoothingEnabled = false;
                ctx.drawImage(qrSource, drawX, drawY, drawW, drawH);
            }
        }

        function showToast(message) {
            var toast = document.getElementById('printToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'printToast';
                toast.className = 'print-toast no-print';
                document.body.appendChild(toast);
            }
            toast.innerHTML = '📋 ' + message;
            toast.classList.add('show');
            clearTimeout(window._toastTimer);
            window._toastTimer = setTimeout(function() {
                toast.classList.remove('show');
            }, 3500);
        }

        function downloadBlob(blob, fileName) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            setTimeout(function() {
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 100);
        }

        async function downloadPdf() {
            var btn = document.getElementById('btnDownloadPdf');
            var originalText = btn ? btn.innerHTML : '';
            var ticket = document.getElementById('ticketDocument');

            if (window.html2pdf && ticket) {
                if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Generating...'; }
                try {
                    initQrCanvas();
                    var worker = html2pdf().set(pdfConfig).from(ticket);
                    await worker.toContainer();
                    var container = worker.prop.container;
                    var containerWrap = container ? container.querySelector('.qr-svg-wrap') : null;
                    var rootRect = container ? container.getBoundingClientRect() : null;
                    var cwRect = containerWrap ? containerWrap.getBoundingClientRect() : null;

                    await worker.toCanvas();
                    stampQrOnPdfCanvas(worker, ticket, rootRect, cwRect);
                    await worker.toPdf();
                    await worker.save();
                } catch (err) {
                    console.error('PDF error:', err);
                    window.print();
                } finally {
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                }
            } else {
                window.print();
            }
        }

        function dataUriToBlob(dataUri) {
            var parts = dataUri.split(',');
            var byteString = atob(parts[1]);
            var mimeString = parts[0].split(':')[1].split(';')[0];
            var ab = new ArrayBuffer(byteString.length);
            var ia = new Uint8Array(ab);
            for (var i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }
            return new Blob([ab], { type: mimeString });
        }

        async function shareJpgDirectly() {
            if (typeof closeShareModal === 'function') closeShareModal();
            var ticket = document.getElementById('ticketDocument');
            var btn = document.getElementById('btnShareJpg');
            var originalText = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ <span>Generating...</span>';
            }

            try {
                if (!window.html2pdf || !ticket) {
                    window.print();
                    return;
                }

                var worker = html2pdf().set({
                    ...pdfConfig,
                    margin: 0,
                    image: { type: 'png', quality: 1.0 },
                    html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false }
                }).from(ticket);

                await worker.toContainer();
                var exactWidthPx = ticket.offsetWidth;
                if (worker.prop && worker.prop.container) {
                    worker.prop.container.style.width = exactWidthPx + 'px';
                    if (worker.prop.container.firstChild) {
                        worker.prop.container.firstChild.style.width = exactWidthPx + 'px';
                    }
                }
                await worker.toCanvas();
                var canvas = worker.prop.canvas;

                // Stamp QR Code directly onto the rendered canvas (resets transform matrix to prevent scale/origin offset)
                var wrap = ticket.querySelector('.qr-svg-wrap');
                var origImg = wrap ? wrap.querySelector('img') : null;
                var qrCanvas = wrap ? wrap.querySelector('canvas.qr-code-canvas') : null;
                var qrSource = qrCanvas || origImg;
                if (canvas && qrSource && wrap) {
                    var ticketRect = ticket.getBoundingClientRect();
                    var wrapRect = wrap.getBoundingClientRect();
                    var scale = canvas.width / ticketRect.width;

                    var boxX = (wrapRect.left - ticketRect.left) * scale;
                    var boxY = (wrapRect.top - ticketRect.top) * scale;
                    var boxW = wrapRect.width * scale;
                    var boxH = wrapRect.height * scale;
                    var imgW = (qrSource.offsetWidth || (origImg ? origImg.width : 72)) * scale;
                    var imgH = (qrSource.offsetHeight || (origImg ? origImg.height : 72)) * scale;
                    var imgX = boxX + (boxW - imgW) / 2;
                    var imgY = boxY + (boxH - imgH) / 2;

                    var ctx = canvas.getContext('2d');
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.imageSmoothingEnabled = false;
                    ctx.drawImage(qrSource, imgX, imgY, imgW, imgH);
                }

                var dataUri = canvas.toDataURL('image/png');
                var blob = dataUriToBlob(dataUri);
                var imageFilename = pdfConfig.filename.replace(/\.pdf$/i, '.png');
                var file = new File([blob], imageFilename, { type: 'image/png' });

                // 1. Prioritize Direct Clipboard Copy (Paste directly Ctrl+V into WeChat / Viber / Telegram)
                var copied = false;
                if (navigator.clipboard && navigator.clipboard.write) {
                    try {
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);
                        copied = true;
                        showToast("{{ __('messages.vouchers_jpg_copied') }}");
                    } catch (clipErr) {
                        console.warn('Clipboard image write failed:', clipErr);
                        copied = false;
                    }
                }

                if (!copied) {
                    // 2. Mobile WebShare API (only on mobile devices when clipboard write is unsupported)
                    var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                    if (isMobile && navigator.canShare && navigator.canShare({ files: [file] })) {
                        try {
                            await navigator.share({
                                title: 'Repair Ticket #' + jobNumber,
                                text: storeName + ' - Repair Ticket #' + jobNumber + '\nTracking: ' + trackingUrl,
                                files: [file]
                            });
                            return;
                        } catch (shareErr) {
                            if (shareErr.name === 'AbortError') return;
                        }
                    }

                    // 3. Fallback direct download
                    downloadBlob(blob, imageFilename);
                    showToast("{{ __('messages.vouchers_jpg_copied') }}");
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('Share JPG error:', err);
                    showToast('Failed to generate image');
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
                var modalBtn = document.getElementById('modalBtnShareJpg');
                if (modalBtn) {
                    modalBtn.disabled = false;
                }
            }
        }

        async function shareNativePdf() {
            closeShareModal();
            var btn = document.getElementById('btnShare');
            var originalText = btn ? btn.innerHTML : '';
            var ticket = document.getElementById('ticketDocument');

            if (window.html2pdf && ticket) {
                if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Preparing PDF...'; }
                try {
                    initQrCanvas();
                    var worker = html2pdf().set(pdfConfig).from(ticket);
                    await worker.toContainer();
                    var container = worker.prop.container;
                    var containerWrap = container ? container.querySelector('.qr-svg-wrap') : null;
                    var rootRect = container ? container.getBoundingClientRect() : null;
                    var cwRect = containerWrap ? containerWrap.getBoundingClientRect() : null;

                    await worker.toCanvas();
                    stampQrOnPdfCanvas(worker, ticket, rootRect, cwRect);
                    await worker.toPdf();
                    var pdfBlob = await worker.output('blob');
                    var pdfFile = new File([pdfBlob], pdfConfig.filename, { type: 'application/pdf' });

                    if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
                        await navigator.share({
                            title: 'Repair Ticket #' + jobNumber,
                            text: storeName + ' - Repair Ticket #' + jobNumber + '\nTracking: ' + trackingUrl,
                            files: [pdfFile]
                        });
                        return;
                    }
                    downloadBlob(pdfBlob, pdfConfig.filename);
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        console.error('Share error:', e);
                    }
                } finally {
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                }
            }
            shareToViber();
        }

        function openShareModal() {
            var m = document.getElementById('shareModal');
            if (m) m.classList.add('show');
        }

        function closeShareModal() {
            var m = document.getElementById('shareModal');
            if (m) m.classList.remove('show');
        }

        function getShareText() {
            var lines = [];
            lines.push('🔧 ' + storeName + ' — ပြုပြင်ရေး လက်ခံပြေစာ');
            lines.push('🧾 အလုပ်အမှတ်: ' + jobNumber);
            lines.push('📱 စက်အမျိုးအစား: ' + deviceLabel);
            if (imeiSerial) {
                lines.push('🔢 IMEI: ' + imeiSerial);
            }
            lines.push('📅 လက်ခံရက်: ' + dateLabel);
            lines.push('💵 ကျသင့်ငွေ: ' + chargeLabel);
            lines.push('✅ ပေးချေပြီး: ' + paidLabel);
            if (hasOutstanding) {
                lines.push('⚠️ ကျန်ငွေ: ' + outstandingLabel);
            }
            lines.push('\n🔍 စက်ပြင်အခြေအနေကို Live စစ်ဆေးရန်:');
            lines.push(trackingUrl);
            if (storePhone) {
                lines.push('\n📞 ဆက်သွယ်ရန်: ' + storePhone);
            }
            return lines.join('\n');
        }

        function shareToViber() {
            var text = getShareText();
            window.location.href = 'viber://forward?text=' + encodeURIComponent(text);
        }

        function shareToTelegram() {
            var text = getShareText();
            window.open('https://t.me/share/url?url=' + encodeURIComponent(trackingUrl) + '&text=' + encodeURIComponent(text), '_blank');
        }

        function shareToWhatsApp() {
            var text = getShareText();
            var url = customerPhone ? ('https://api.whatsapp.com/send?phone=' + customerPhone + '&text=' + encodeURIComponent(text))
                                    : ('https://api.whatsapp.com/send?text=' + encodeURIComponent(text));
            window.open(url, '_blank');
        }

        function fallbackCopyText(text, successMsg) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '0';
            ta.style.left = '0';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            var success = false;
            try {
                success = document.execCommand('copy');
            } catch (err) {
                success = false;
            }
            document.body.removeChild(ta);
            if (success) {
                if (successMsg) showToast(successMsg);
            } else {
                prompt('Copy text:', text);
            }
            return success;
        }

        function copyVoucherText() {
            var text = getShareText();
            var msg = '📋 ' + '{{ __('messages.repair_voucher_text_copied') }}';
            var lbl = document.getElementById('copyVoucherTextLabel');
            if (lbl) lbl.innerText = '✓ ' + '{{ __('messages.vouchers_copied') }}';

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast(msg);
                    setTimeout(function() {
                        if (lbl) lbl.innerText = '{{ __('messages.repair_copy_voucher_text') }}';
                    }, 2500);
                }).catch(function() {
                    fallbackCopyText(text, msg);
                    setTimeout(function() {
                        if (lbl) lbl.innerText = '{{ __('messages.repair_copy_voucher_text') }}';
                    }, 2500);
                });
            } else {
                fallbackCopyText(text, msg);
                setTimeout(function() {
                    if (lbl) lbl.innerText = '{{ __('messages.repair_copy_voucher_text') }}';
                }, 2500);
            }
        }

        function copyTrackingLink() {
            var msg = '🔗 ' + '{{ __('messages.repair_copy_track_link') }}' + ' ✓';
            var lbl = document.getElementById('copyLinkText');
            if (lbl) lbl.innerText = '✓ ' + '{{ __('messages.vouchers_copied') }}';

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(trackingUrl).then(function() {
                    showToast(msg);
                    setTimeout(function() {
                        if (lbl) lbl.innerText = '{{ __('messages.repair_copy_track_link') }}';
                    }, 2000);
                }).catch(function() {
                    fallbackCopyText(trackingUrl, msg);
                    setTimeout(function() {
                        if (lbl) lbl.innerText = '{{ __('messages.repair_copy_track_link') }}';
                    }, 2000);
                });
            } else {
                fallbackCopyText(trackingUrl, msg);
                setTimeout(function() {
                    if (lbl) lbl.innerText = '{{ __('messages.repair_copy_track_link') }}';
                }, 2000);
            }
        }

        // Expose globally
        window.downloadPdf = downloadPdf;
        window.shareJpgDirectly = shareJpgDirectly;
        window.shareNativePdf = shareNativePdf;
        window.openShareModal = openShareModal;
        window.closeShareModal = closeShareModal;
        window.shareToViber = shareToViber;
        window.shareToTelegram = shareToTelegram;
        window.shareToWhatsApp = shareToWhatsApp;
        window.copyVoucherText = copyVoucherText;
        window.copyTrackingLink = copyTrackingLink;
        window.showToast = showToast;
        window.downloadBlob = downloadBlob;

        // Dual DOM Event binding
        document.addEventListener('DOMContentLoaded', function() {
            var sizeSelect = document.getElementById('repairPaperSizeSelect');
            if (sizeSelect) {
                sizeSelect.addEventListener('change', function() {
                    if (this.value) {
                        window.location.href = this.value;
                    }
                });
            }

            var printBtn = document.getElementById('btnPrint') || document.querySelector('.tool-btn-print');
            if (printBtn) {
                printBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.print();
                });
            }
            var pdfBtn = document.getElementById('btnDownloadPdf');
            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    downloadPdf();
                });
            }
            var shareJpgBtn = document.getElementById('btnShareJpg');
            if (shareJpgBtn) {
                shareJpgBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    shareJpgDirectly();
                });
            }
            var shareBtn = document.getElementById('btnShare');
            if (shareBtn) {
                shareBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openShareModal();
                });
            }

            document.querySelectorAll('[data-repair-share-close]').forEach(function(button) {
                button.addEventListener('click', closeShareModal);
            });

            var shareBackdrop = document.querySelector('[data-repair-share-backdrop]');
            if (shareBackdrop) {
                shareBackdrop.addEventListener('click', function(event) {
                    if (event.target === shareBackdrop) closeShareModal();
                });
            }

            document.querySelectorAll('[data-repair-share-channel]').forEach(function(button) {
                button.addEventListener('click', function() {
                    var handlers = {
                        'copy-text': copyVoucherText,
                        jpg: shareJpgDirectly,
                        native: shareNativePdf,
                        viber: shareToViber,
                        telegram: shareToTelegram,
                        whatsapp: shareToWhatsApp,
                        copy: copyTrackingLink
                    };
                    var handler = handlers[button.dataset.repairShareChannel];
                    if (handler) handler();
                });
            });
        });
    </script>
</body>
</html>
