<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.stock_count_print_title') }} - {{ $session->session_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        * {
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #111827;
        }
        body {
            margin: 0;
            padding: 20px;
            background: #fff;
            font-size: 12px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #334155;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .store-title {
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: 700;
            color: #4338ca;
            margin-top: 4px;
        }
        .meta-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 11px;
        }
        .meta-item {
            line-height: 1.5;
        }
        .meta-label {
            font-weight: 600;
            color: #64748b;
        }
        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        table.items-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
        }
        table.items-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            font-size: 11px;
            vertical-align: middle;
        }
        table.items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .count-box {
            display: inline-block;
            width: 70px;
            height: 20px;
            border-bottom: 1px dashed #94a3b8;
        }
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sig-box {
            width: 28%;
            text-align: center;
            border-top: 1px solid #64748b;
            padding-top: 6px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    {{-- Print Controls --}}
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 18px; font-size: 13px; font-weight: bold; background: #4f46e5; color: white; border: none; border-radius: 8px; cursor: pointer;">
            🖨️ Print Stock Count Sheet
        </button>
    </div>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td>
                <div class="store-title">{{ $store->name ?? 'DataPOS Store' }}</div>
                <div class="doc-title">{{ __('messages.stock_count_print_title') }}</div>
                @if($session->notes)
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ $session->notes }}</div>
                @endif
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div style="font-family: monospace; font-size: 15px; font-weight: 800; color: #4338ca;">
                    {{ $session->session_number }}
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                    Date: {{ $session->created_at->format('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Meta Summary --}}
    <div class="meta-grid">
        <div class="meta-item">
            <span class="meta-label">{{ __('messages.stock_count_scope') }}:</span>
            <span class="meta-val">{{ $session->scope === 'category' ? __('messages.stock_count_scope_category') : __('messages.stock_count_scope_all') }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">{{ __('messages.stock_count_total_products') }}:</span>
            <span class="meta-val">{{ $session->total_items }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">{{ __('messages.stock_count_status') }}:</span>
            <span class="meta-val" style="text-transform: uppercase;">{{ $session->status }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">{{ __('messages.stock_count_created_by') }}:</span>
            <span class="meta-val">{{ $session->createdBy?->name ?? 'Admin' }}</span>
        </div>
    </div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">#</th>
                <th>{{ __('messages.product') }}</th>
                <th>SKU / Barcode</th>
                <th>{{ __('messages.category') }}</th>
                <th class="text-right" style="width: 80px;">{{ __('messages.stock_count_system_qty') }}</th>
                <th class="text-center" style="width: 90px;">{{ __('messages.stock_count_counted_qty') }}</th>
                <th class="text-right" style="width: 80px;">{{ __('messages.stock_count_variance') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $fmtQty = function($v) {
                    $val = (float) $v;
                    return $val == (int) $val ? number_format($val, 0) : rtrim(rtrim(number_format($val, 3), '0'), '.');
                };
            @endphp
            @foreach($session->lines as $idx => $line)
                <tr>
                    <td class="text-center" style="font-size: 10px; color: #94a3b8;">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $line->product?->name ?? 'Unknown Product' }}</strong>
                    </td>
                    <td style="font-family: monospace; font-size: 10px;">
                        {{ $line->product?->sku ?: '-' }} / {{ $line->product?->barcode ?: '-' }}
                    </td>
                    <td>{{ $line->category?->name ?? '-' }}</td>
                    <td class="text-right" style="font-family: monospace; font-weight: bold;">
                        {{ $fmtQty($line->system_quantity) }}
                    </td>
                    <td class="text-center">
                        @if($line->counted_quantity !== null)
                            <span style="font-family: monospace; font-weight: bold;">{{ $fmtQty($line->counted_quantity) }}</span>
                        @else
                            <div class="count-box"></div>
                        @endif
                    </td>
                    <td class="text-right" style="font-family: monospace;">
                        @if($line->is_counted)
                            {{ $line->variance_quantity > 0 ? '+' : '' }}{{ $fmtQty($line->variance_quantity) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sig-box">
            {{ __('messages.stock_count_counted_by') }} (Signature / Date)
        </div>
        <div class="sig-box">
            {{ __('messages.stock_count_verified_by') }} (Signature / Date)
        </div>
        <div class="sig-box">
            {{ __('messages.stock_count_approved_by') }} (Manager Signature / Date)
        </div>
    </div>

</body>
</html>
