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
        .tool-btn-share {
            background: #059669;
            color: #fff;
        }
        .tool-btn-share:hover {
            background: #047857;
        }

        /* Paper Size Switcher Pills */
        .size-pills {
            display: inline-flex;
            background: #1e293b;
            border-radius: 7px;
            padding: 2px;
            gap: 2px;
            border: 1px solid #334155;
        }
        .size-pill {
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-decoration: none;
            cursor: pointer;
            border: none;
            background: transparent;
            transition: all 0.15s ease;
        }
        .size-pill:hover {
            color: #f1f5f9;
        }
        .size-pill.active {
            background: #7c3aed;
            color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
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
        .qr-svg-wrap svg {
            display: block;
            width: 72px;
            height: 72px;
        }
        .size-58mm .qr-svg-wrap svg { width: 56px; height: 56px; }
        .size-a5 .qr-svg-wrap svg { width: 84px; height: 84px; }
        .size-a4 .qr-svg-wrap svg { width: 96px; height: 96px; }
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

            {{-- Paper Size Switcher Pills --}}
            <div class="size-pills">
                <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id, 'paper_size' => '58mm']) }}"
                   class="size-pill {{ $paperSize === '58mm' ? 'active' : '' }}" title="58mm POS Slip">
                    58mm
                </a>
                <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id, 'paper_size' => '80mm']) }}"
                   class="size-pill {{ $paperSize === '80mm' ? 'active' : '' }}" title="80mm POS Thermal">
                    80mm
                </a>
                <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id, 'paper_size' => 'a5']) }}"
                   class="size-pill {{ $paperSize === 'a5' ? 'active' : '' }}" title="A5 Half Sheet">
                    A5
                </a>
                <a href="{{ route('store.admin.repairs.print', [...$storeRouteParams, 'repair' => $repair->id, 'paper_size' => 'a4']) }}"
                   class="size-pill {{ $paperSize === 'a4' ? 'active' : '' }}" title="A4 Full Sheet">
                    A4
                </a>
            </div>
        </div>

        <div class="toolbar-right">
            <button type="button" class="tool-btn tool-btn-print" id="btnPrint" data-repair-print>
                🖨️ <span>{{ __('messages.repair_print_ticket') }}</span>
            </button>

            <button type="button" class="tool-btn tool-btn-pdf" id="btnDownloadPdf" data-repair-download-pdf>
                📥 <span>{{ __('messages.repair_download_pdf') }}</span>
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
                        <td class="lbl">IMEI / Serial:</td>
                        <td class="val font-mono">{{ $repair->imei_serial }}</td>
                    </tr>
                @endif
                @if ($repair->pattern_lock)
                    <tr>
                        <td class="lbl">Pattern Lock:</td>
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
            @if ($trackingQrSvg)
                <div class="qr-section">
                    <div class="qr-svg-wrap">
                        {!! $trackingQrSvg !!}
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

            {{-- Native Share with File (if supported) --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="native" style="background:#7c3aed; color:#fff; border-color:#6d28d9;">
                <span>📄</span>
                <span>PDF ဖိုင် တိုက်ရိုက် Share မည် (Viber / WhatsApp / AirDrop)</span>
            </button>

            {{-- Viber Channel --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="viber">
                <span style="color:#7360f2; font-size:16px;">💬</span>
                <span>{{ __('messages.repair_share_viber') }}</span>
            </button>

            {{-- Telegram Channel --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="telegram">
                <span style="color:#229ed9; font-size:16px;">✈️</span>
                <span>{{ __('messages.repair_share_telegram') }}</span>
            </button>

            {{-- WhatsApp Channel --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="whatsapp">
                <span style="color:#25d366; font-size:16px;">🟢</span>
                <span>{{ __('messages.repair_share_whatsapp') }}</span>
            </button>

            {{-- Copy Link --}}
            <button type="button" class="share-channel-btn" data-repair-share-channel="copy">
                <span>📋</span>
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
        var deviceLabel = @js(trim(($repair->brand ?? '') . ' ' . ($repair->model ?? $repair->device_type)));
        var imeiSerial = @js($repair->imei_serial ?? '');
        var dateLabel = @js($repair->created_at->format('d/m/Y'));
        var chargeLabel = @js(format_currency($charge, $store));
        var paidLabel = @js(format_currency($paid, $store));
        var outstandingLabel = @js(format_currency($outstanding, $store));
        var hasOutstanding = {{ $outstanding > 0 ? 'true' : 'false' }};
        var storePhone = @js($storePhone ?? '');
        var customerPhone = @js($customerPhone ? preg_replace('/[^0-9]/', '', $customerPhone) : '');

        var pdfDimensions = {
            '58mm': { unit: 'mm', format: [58, 200], orientation: 'portrait' },
            '80mm': { unit: 'mm', format: [80, 240], orientation: 'portrait' },
            'a5': { unit: 'mm', format: 'a5', orientation: 'portrait' },
            'a4': { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

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

        function downloadPdf() {
            var btn = document.getElementById('btnDownloadPdf');
            var originalText = btn ? btn.innerHTML : '';
            var ticket = document.getElementById('ticketDocument');

            if (window.html2pdf && ticket) {
                if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Generating...'; }
                html2pdf().set(pdfConfig).from(ticket).save().then(function() {
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                }).catch(function(err) {
                    console.error('PDF error:', err);
                    if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
                    window.print();
                });
            } else {
                window.print();
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
                    var worker = html2pdf().set(pdfConfig).from(ticket).toPdf();
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

        function copyTrackingLink() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(trackingUrl).then(function() {
                    var el = document.getElementById('copyLinkText');
                    if (el) el.innerText = '✓ Link ကူးယူပြီးပါပြီ!';
                    setTimeout(function() { if (el) el.innerText = '{{ __('messages.repair_copy_track_link') }}'; }, 2000);
                });
            } else {
                prompt('Copy link:', trackingUrl);
            }
        }

        // Expose globally
        window.downloadPdf = downloadPdf;
        window.shareNativePdf = shareNativePdf;
        window.openShareModal = openShareModal;
        window.closeShareModal = closeShareModal;
        window.shareToViber = shareToViber;
        window.shareToTelegram = shareToTelegram;
        window.shareToWhatsApp = shareToWhatsApp;
        window.copyTrackingLink = copyTrackingLink;

        // Dual DOM Event binding
        document.addEventListener('DOMContentLoaded', function() {
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
