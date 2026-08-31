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
            <span>Receipt #:</span>
            <span>#SMP-2026-0001</span>
        </div>
        <div style="display: flex; justify-content: space-between; color: #64748b;">
            <span>Date:</span>
            <span>{{ now()->format('Y-m-d H:i') }}</span>
        </div>
        @if($template->show_cashier_name)
            <div style="display: flex; justify-content: space-between; color: #64748b;">
                <span>Cashier:</span>
                <span>Mg Min (Counter 01)</span>
            </div>
        @endif
        @if($template->show_customer_info)
            <div style="display: flex; justify-content: space-between; color: #64748b;">
                <span>Customer:</span>
                <span>Daw Mya (09-789123456)</span>
            </div>
        @endif
    </div>

    <div class="divider"></div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center;">Qty</th>
                <th class="text-right">Total</th>
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
            <span style="color: #64748b;">Subtotal:</span>
            <span class="font-mono">93,000 MMK</span>
        </div>
        @if($template->show_discount_line)
            <div class="totals-row" style="color: #e11d48;">
                <span>Store Discount (5%):</span>
                <span class="font-mono">-4,650 MMK</span>
            </div>
        @endif
        @if($template->show_tax_breakdown)
            <div class="totals-row" style="color: #64748b;">
                <span>Commercial Tax (5%):</span>
                <span class="font-mono">4,418 MMK</span>
            </div>
        @endif
        <div class="totals-row grand">
            <span>Net Total:</span>
            <span class="font-mono">92,768 MMK</span>
        </div>
    </div>

    {{-- QR Code --}}
    @if($template->show_qr)
        <div class="qr-box">
            <div class="qr-image">
                [ QR CODE ]
            </div>
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
