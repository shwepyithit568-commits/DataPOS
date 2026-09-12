<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.vouchers_print_sample') }} - {{ $template->name }} - {{ $store->name }}</title>
    <style>
        @page {
            @if($template->is58mm())
                size: 58mm auto;
                margin: 0;
            @elseif($template->is80mm())
                size: 80mm auto;
                margin: 0;
            @elseif($template->isA4())
                size: A4 portrait;
                margin: 15mm;
            @elseif($template->isA5())
                size: A5 landscape;
                margin: 10mm;
            @endif
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            margin: 0;
            padding: 20px;
            font-size: {{ $template->font_size === 'small' ? '11px' : ($template->font_size === 'large' ? '14px' : '12px') }};
            line-height: 1.4;
        }
        .voucher-container {
            background: #fff;
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            @if($template->is58mm())
                max-width: 54mm;
            @elseif($template->is80mm())
                max-width: 76mm;
            @elseif($template->isA4())
                max-width: 210mm;
            @elseif($template->isA5())
                max-width: 148mm;
            @endif
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .store-name {
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .divider {
            border-top: 1px dashed #cbd5e1;
            margin: 10px 0;
        }
        .solid-divider {
            border-top: 2px solid #0f172a;
            margin: 10px 0;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: inherit;
        }
        table.items-table th {
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            padding: 4px 0;
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
        }
        table.items-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        table.items-table td.text-right, table.items-table th.text-right {
            text-align: right;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        .totals-row.grand {
            font-size: 14px;
            font-weight: 900;
            border-top: 1px solid #0f172a;
            padding-top: 6px;
            margin-top: 4px;
        }
        .qr-box {
            text-align: center;
            margin: 14px 0 8px 0;
            padding: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .qr-image {
            width: 70px;
            height: 70px;
            border: 1px solid #0f172a;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 10px;
            background: #fff;
        }
        .barcode-section {
            text-align: center;
            margin: 12px 0;
        }
        .barcode-bars {
            height: 28px;
            background: repeating-linear-gradient(
                90deg,
                #000 0px,
                #000 2px,
                #fff 2px,
                #fff 4px,
                #000 4px,
                #000 6px
            );
            width: 75%;
            margin: 4px auto;
        }
        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 12px;
        }
        .btn-print-box {
            max-width: 380px;
            margin: 20px auto 0 auto;
            display: flex;
            gap: 8px;
            justify-content: center;
        }
        .btn-print, .btn-pdf, .btn-close {
            flex: 1;
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.1s ease;
        }
        .btn-print:active, .btn-pdf:active, .btn-close:active { transform: scale(0.97); }
        .btn-print {
            background: #0284c7;
            color: #fff;
        }
        .btn-pdf {
            background: #7c3aed;
            color: #fff;
        }
        .btn-close {
            background: #e2e8f0;
            color: #334155;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .voucher-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }
            .btn-print-box {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="voucher-container">

@if($template->document_type === 'invoice')
    {{-- Commercial Tax Invoice Sample Preview --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 14px;">
        <div>
            @if($template->show_logo)
                @if($template->logo_path)
                    <img src="{{ $template->logoUrl() }}" alt="Logo" style="max-height: 48px; margin-bottom: 6px;" />
                @else
                    <div style="width: 44px; height: 44px; background: #0f172a; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 16px; margin-bottom: 6px;">DP</div>
                @endif
            @endif
            <h1 style="font-size: 18px; font-weight: 900; text-transform: uppercase; margin: 0 0 2px 0;">{{ $template->header_title ?? $store->name }}</h1>
            @if($template->header_subtitle)
                <div style="font-size: 11px; color: #64748b;">{{ $template->header_subtitle }}</div>
            @endif
            @if($template->address)
                <div style="font-size: 11px; color: #475569;">{{ $template->address }}</div>
            @endif
            @if($template->phone)
                <div style="font-size: 11px; color: #475569;">Tel: {{ $template->phone }}</div>
            @endif
        </div>
        <div style="text-align: right;">
            <div style="font-size: 14px; font-weight: 900; color: #7c3aed; text-transform: uppercase; letter-spacing: 0.5px;">{{ __('messages.invoice_commercial_title') }}</div>
            <div style="font-size: 13px; font-weight: 800; font-family: monospace;">#ORD-2026-0001</div>
            <div style="font-size: 11px; color: #64748b;">{{ now()->format('F j, Y · h:i A') }}</div>
            <div style="margin-top: 4px;"><span style="display: inline-block; padding: 2px 6px; background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; border-radius: 4px; font-size: 10px; font-weight: bold; font-family: monospace;">{{ __('messages.invoice_tin') }}: 104889230</span></div>
            @if($template->show_barcode)
                <div style="margin-top: 6px;">
                    <div style="height: 24px; background: repeating-linear-gradient(90deg, #000 0px, #000 1.5px, #fff 1.5px, #fff 3px); width: 120px; margin-left: auto;"></div>
                </div>
            @endif
        </div>
    </div>

    {{-- Party Box --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
        <div style="padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 11px; line-height: 1.5;">
            <div style="font-weight: 800; font-size: 10px; text-transform: uppercase; color: #475569; margin-bottom: 4px;">{{ __('messages.invoice_billed_to') }}</div>
            @if($template->show_customer_info)
                <div style="font-weight: bold; color: #0f172a;">Daw Hla Hla</div>
                <div style="color: #475569;">Tel: 09-987654321</div>
                <div style="color: #64748b;">Bahan Township, Yangon</div>
            @else
                <div style="color: #64748b; font-style: italic;">{{ __('messages.walk_in_customer') }}</div>
            @endif
        </div>
        <div style="padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 11px; line-height: 1.5;">
            <div style="font-weight: 800; font-size: 10px; text-transform: uppercase; color: #475569; margin-bottom: 4px;">{{ __('messages.invoice_status') }}</div>
            <div style="display: flex; gap: 6px;">
                <span style="padding: 2px 6px; border-radius: 4px; background: #dcfce7; color: #166534; font-weight: bold; font-size: 10px;">DELIVERED</span>
                <span style="padding: 2px 6px; border-radius: 4px; background: #fef3c7; color: #92400e; font-weight: bold; font-size: 10px;">UNPAID</span>
            </div>
            @if($template->show_cashier_name)
                <div style="color: #64748b; font-size: 10px; margin-top: 6px;">Sales Staff: Ko Aung</div>
            @endif
        </div>
    </div>

    {{-- Items Table --}}
    <table class="items-table" style="margin-bottom: 16px;">
        <thead>
            <tr style="border-bottom: 1.5px solid #cbd5e1;">
                <th style="width: 30px;">#</th>
                <th>{{ __('messages.invoice_col_item') }}</th>
                <th style="text-align: center; width: 60px;">{{ __('messages.invoice_col_qty') }}</th>
                <th style="text-align: right; width: 110px;">{{ __('messages.invoice_col_unit_price') }}</th>
                <th style="text-align: right; width: 110px;">{{ __('messages.invoice_col_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td>1</td>
                <td>
                    <div style="font-weight: bold; color: #0f172a;">UAT Phone Product A</div>
                    <div style="font-size: 10px; color: #64748b;">256GB Midnight Black</div>
                </td>
                <td style="text-align: center; font-family: monospace;">2</td>
                <td style="text-align: right; font-family: monospace;">350,000</td>
                <td style="text-align: right; font-family: monospace; font-weight: bold;">700,000</td>
            </tr>
        </tbody>
    </table>

    {{-- Bottom Summary Grid --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start; margin-bottom: 20px;">
        <div>
            @if($template->show_qr)
                <div style="padding: 8px 12px; background: #fafafa; border: 1px dashed #cbd5e1; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                    @if($template->qr_image_path)
                        <img src="{{ $template->qrUrl() }}" alt="QR Code" style="width: 60px; height: 60px; object-fit: contain;" />
                    @else
                        <div style="width: 60px; height: 60px; background: #fff; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: bold;">[ QR ]</div>
                    @endif
                    <div>
                        <div style="font-size: 11px; font-weight: bold; color: #0f172a;">{{ $template->qr_label ?? 'Scan to pay with KPay / Wave / Bank' }}</div>
                        <div style="font-size: 10px; color: #64748b;">{{ __('messages.receipt_scan_to_pay') }}</div>
                    </div>
                </div>
            @endif
        </div>
        <div style="font-size: 11px;">
            <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #475569;">
                <span>{{ __('messages.subtotal') }}:</span>
                <span style="font-weight: bold; font-family: monospace;">700,000 Ks</span>
            </div>
            @if($template->show_discount_line)
                <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #dc2626;">
                    <span>{{ __('messages.discount') }}:</span>
                    <span style="font-weight: bold; font-family: monospace;">-0 Ks</span>
                </div>
            @endif
            @if($template->show_tax_breakdown)
                <div style="display: flex; justify-content: space-between; padding: 2px 0; color: #475569;">
                    <span>{{ __('messages.commercial_tax') }} (5%):</span>
                    <span style="font-weight: bold; font-family: monospace;">+ 35,000 Ks</span>
                </div>
            @endif
            <div style="display: flex; justify-content: space-between; padding: 8px 10px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; font-size: 13px; font-weight: 900; color: #7c3aed; margin-top: 6px;">
                <span>{{ __('messages.invoice_total_due') }}:</span>
                <span style="font-family: monospace;">735,000 Ks</span>
            </div>
        </div>
    </div>

    {{-- Signatures & Stamp Block --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 110px; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 20px; text-align: center; font-size: 10px;">
        <div style="display: flex; flex-direction: column; justify-content: space-between; height: 65px;">
            <div style="border-bottom: 1px dashed #94a3b8; margin-top: auto; margin-bottom: 4px;"></div>
            <div style="font-weight: bold; color: #475569;">{{ __('messages.invoice_prepared_by') }}</div>
        </div>
        <div style="display: flex; flex-direction: column; justify-content: space-between; height: 65px;">
            <div style="border-bottom: 1px dashed #94a3b8; margin-top: auto; margin-bottom: 4px;"></div>
            <div style="font-weight: bold; color: #475569;">{{ __('messages.invoice_checked_by') }}</div>
        </div>
        <div style="display: flex; flex-direction: column; justify-content: space-between; height: 65px;">
            <div style="border-bottom: 1px dashed #94a3b8; margin-top: auto; margin-bottom: 4px;"></div>
            <div style="font-weight: bold; color: #475569;">{{ __('messages.invoice_received_by') }}</div>
        </div>
        <div style="border: 1.5px dashed #cbd5e1; border-radius: 6px; height: 65px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 8.5px; font-weight: bold; text-transform: uppercase;">
            {{ __('messages.invoice_company_stamp') }}
        </div>
    </div>

    {{-- Footer Greeting & Policies --}}
    <div class="footer" style="margin-top: 18px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
        @if($template->footer_greeting)
            <div style="font-weight: bold; margin-bottom: 2px;">{{ $template->footer_greeting }}</div>
        @endif
        @if($template->footer_policy)
            <div style="color: #64748b; font-size: 10px;">{{ $template->footer_policy }}</div>
        @endif
    </div>
@else
    {{-- Standard POS Receipt Sample Preview --}}
    {{-- Header Branding --}}
    <div class="header">
        @if($template->show_logo)
            <div style="font-size: 18px; font-weight: 900; margin-bottom: 4px;">🏷️</div>
        @endif
        <h1 class="store-name">{{ $template->header_title ?? $store->name }}</h1>
        @if($template->header_subtitle)
            <div style="font-size: 11px; color: #64748b;">{{ $template->header_subtitle }}</div>
        @endif
        @if($template->address)
            <div style="font-size: 10px; color: #475569;">{{ $template->address }}</div>
        @endif
        @if($template->phone)
            <div style="font-size: 10px; color: #475569;">Tel/Viber: {{ $template->phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Metadata Row --}}
    <div style="font-size: 11px; space-y: 2px;">
        <div style="display: flex; justify-content: space-between; font-weight: bold;">
            <span>{{ __('messages.receipt_no') }}:</span>
            <span>#SMP-2026-0001</span>
        </div>
        <div style="display: flex; justify-content: space-between; color: #64748b;">
            <span>{{ __('messages.date') }}:</span>
            <span>{{ now()->format('Y-m-d H:i') }}</span>
        </div>
        @if($template->show_cashier_name)
            <div style="display: flex; justify-content: space-between; color: #64748b;">
                <span>{{ __('messages.cashier') }}:</span>
                <span>Mg Min (Counter 01)</span>
            </div>
        @endif
        @if($template->show_customer_info)
            <div style="display: flex; justify-content: space-between; color: #64748b;">
                <span>{{ __('messages.customer') }}:</span>
                <span>Daw Mya (09-789123456)</span>
            </div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('messages.description') }}</th>
                <th style="text-align: center;">{{ __('messages.qty') }}</th>
                <th class="text-right">{{ __('messages.total') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Anker 65W GaN Fast Charger</strong>
                    <div style="font-size: 10px; color: #64748b;">@ 65,000 MMK</div>
                </td>
                <td style="text-align: center;">1</td>
                <td class="text-right font-mono">65,000</td>
            </tr>
            <tr>
                <td>
                    <strong>Kingston 64GB USB 3.2 Drive</strong>
                    <div style="font-size: 10px; color: #64748b;">@ 14,000 MMK</div>
                </td>
                <td style="text-align: center;">2</td>
                <td class="text-right font-mono">28,000</td>
            </tr>
        </tbody>
    </table>

    <div class="divider"></div>

    {{-- Totals Calculation --}}
    <div>
        <div class="totals-row">
            <span style="color: #64748b;">{{ __('messages.subtotal') }}:</span>
            <span class="font-mono">93,000 MMK</span>
        </div>
        @if($template->show_discount_line)
            <div class="totals-row" style="color: #e11d48;">
                <span>{{ __('messages.store_discount_pct') }}:</span>
                <span class="font-mono">-4,650 MMK</span>
            </div>
        @endif
        @if($template->show_tax_breakdown)
            <div class="totals-row" style="color: #64748b;">
                <span>{{ __('messages.commercial_tax_pct') }}:</span>
                <span class="font-mono">4,418 MMK</span>
            </div>
        @endif
        <div class="totals-row grand">
            <span>{{ __('messages.net_total') }}:</span>
            <span class="font-mono">92,768 MMK</span>
        </div>
    </div>

    {{-- QR Code --}}
    @if($template->show_qr)
        <div class="qr-box">
            @if($template->qr_image_path)
                <img src="{{ $template->qrUrl() }}" alt="QR Code" style="max-width: 80px; max-height: 80px; margin: 0 auto; display: block; border-radius: 4px;" />
            @else
                @php
                    $sampleQrUri = null;
                    try {
                        $sampleQrUri = \App\Services\QrCodeEncoder::generatePngDataUri(url()->current(), 3);
                    } catch (\Throwable $e) {
                        $sampleQrUri = null;
                    }
                @endphp
                @if($sampleQrUri)
                    <img src="{{ $sampleQrUri }}" alt="QR Code" style="max-width: 80px; max-height: 80px; margin: 0 auto; display: block; border-radius: 4px;" />
                @else
                    <div class="qr-image">[ QR CODE ]</div>
                @endif
            @endif
            <div style="font-size: 10px; font-weight: bold; margin-top: 4px;">
                {{ $template->qr_label ?? 'Scan to pay with KPay / Wave' }}
            </div>
        </div>
    @endif

    {{-- Barcode --}}
    @if($template->show_barcode)
        <div class="barcode-section">
            <div class="barcode-bars"></div>
            <div style="font-size: 10px; font-family: monospace;">*SMP-2026-0001*</div>
        </div>
    @endif

    <div class="solid-divider"></div>

    {{-- Footer Greeting & Policies --}}
    <div class="footer">
        @if($template->footer_greeting)
            <div style="font-weight: bold; margin-bottom: 2px;">
                {{ $template->footer_greeting }}
            </div>
        @endif
        @if($template->footer_policy)
            <div style="color: #64748b; font-size: 10px;">
                {{ $template->footer_policy }}
            </div>
        @endif
    </div>
@endif

</div>

<div class="btn-print-box">
    <button type="button" class="btn-print" onclick="window.print()">
        🖨️ {{ __('messages.print') ?? 'Print Sample' }}
    </button>
    <button type="button" class="btn-pdf" onclick="saveAsPdf()">
        📥 {{ __('messages.export_pdf') ?? 'Save PDF' }}
    </button>
    <button type="button" class="btn-close" onclick="window.close()">
        ✕ {{ __('messages.close') ?? 'Close' }}
    </button>
</div>

<script>
    function saveAsPdf() {
        var oldTitle = document.title;
        document.title = 'Voucher_{{ $template->name }}_{{ $template->paper_size }}.pdf';
        window.print();
        setTimeout(function() {
            document.title = oldTitle;
        }, 1000);
    }
</script>

</body>
</html>
