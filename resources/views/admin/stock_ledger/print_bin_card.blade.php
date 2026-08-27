<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.stock_ledger_bin_card_title') }} - {{ $product->name }}</title>
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
            font-size: 11px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #334155;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .store-title {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 15px;
            font-weight: 700;
            color: #4338ca;
            margin-top: 3px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 14px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
        }
        .meta-item {
            line-height: 1.4;
        }
        .meta-label {
            font-weight: 600;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
        }
        .meta-val {
            font-weight: 700;
            color: #0f172a;
            font-size: 12px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 6px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
        }
        table.items-table td {
            border: 1px solid #e2e8f0;
            padding: 5px 6px;
            font-size: 10.5px;
            vertical-align: middle;
        }
        table.items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: 700; }
        .signatures {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sig-box {
            width: 28%;
            text-align: center;
            border-top: 1px solid #64748b;
            padding-top: 5px;
            font-size: 10.5px;
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
    <div class="no-print" style="margin-bottom: 16px; text-align: right;">
        <button onclick="window.print()" style="padding: 7px 16px; font-size: 12px; font-weight: bold; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer;">
            🖨️ Print Product Bin Card
        </button>
    </div>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td>
                <div class="store-title">{{ $store->name ?? 'DataPOS Store' }}</div>
                <div class="doc-title">{{ __('messages.stock_ledger_bin_card_title') }}</div>
                <div style="font-size: 12px; font-weight: bold; margin-top: 4px;">{{ $product->name }}</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="font-mono" style="font-size: 13px; font-weight: 800; color: #4338ca;">
                    SKU: {{ $product->sku }}
                </div>
                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">
                    Category: {{ $product->category?->name ?? '-' }}
                </div>
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                    Date Printed: {{ now()->format('d/m/Y H:i') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Meta Grid --}}
    <div class="meta-grid">
        <div class="meta-item">
            <div class="meta-label">{{ __('messages.stock_ledger_opening_balance') }}</div>
            <div class="meta-val font-mono">{{ number_format($binCardData['opening_balance'], 3) }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">{{ __('messages.stock_ledger_in_qty') }}</div>
            <div class="meta-val font-mono" style="color: #059669;">+{{ number_format($binCardData['total_in'], 3) }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">{{ __('messages.stock_ledger_out_qty') }}</div>
            <div class="meta-val font-mono" style="color: #e11d48;">-{{ number_format($binCardData['total_out'], 3) }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">{{ __('messages.stock_ledger_current_stock') }}</div>
            <div class="meta-val font-mono" style="color: #4f46e5;">{{ number_format($binCardData['current_on_hand'], 3) }}</div>
        </div>
    </div>

    {{-- Movements Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">#</th>
                <th style="width: 80px;">{{ __('messages.stock_ledger_date') }}</th>
                <th>{{ __('messages.stock_ledger_movement_type') }}</th>
                <th>{{ __('messages.stock_ledger_reference') }}</th>
                <th class="text-right" style="width: 55px; color: #059669;">In (+)</th>
                <th class="text-right" style="width: 55px; color: #e11d48;">Out (-)</th>
                <th class="text-right" style="width: 65px;">Balance</th>
                <th style="width: 60px;">{{ __('messages.stock_ledger_posted_by') }}</th>
            </tr>
        </thead>
        <tbody class="font-mono">
            @forelse($binCardData['timeline_chronological'] as $idx => $row)
                <tr>
                    <td class="text-center" style="font-size: 9px; color: #94a3b8;">{{ $idx + 1 }}</td>
                    <td style="font-size: 9.5px;">{{ $row['occurred_at'] ? $row['occurred_at']->format('d/m/y H:i') : '-' }}</td>
                    <td style="font-family: sans-serif; font-size: 10px; font-weight: 600;">{{ __('messages.movement_type_' . $row['movement_type']) }}</td>
                    <td style="font-family: sans-serif; font-size: 9.5px; color: #475569;">
                        {{ $row['source_type'] ? ucfirst($row['source_type']) . ($row['source_id'] ? " #{$row['source_id']}" : '') : '-' }}
                    </td>
                    <td class="text-right font-bold" style="color: #059669;">
                        {{ $row['in_qty'] > 0 ? '+' . number_format($row['in_qty'], 3) : '-' }}
                    </td>
                    <td class="text-right font-bold" style="color: #e11d48;">
                        {{ $row['out_qty'] > 0 ? '-' . number_format($row['out_qty'], 3) : '-' }}
                    </td>
                    <td class="text-right font-bold" style="background-color: #f8fafc;">
                        {{ number_format($row['running_balance'], 3) }}
                    </td>
                    <td style="font-family: sans-serif; font-size: 9.5px; color: #64748b;">
                        {{ $row['posted_by_name'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 16px; color: #94a3b8;">
                        {{ __('messages.stock_ledger_no_movements') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sig-box">
            Stock Keeper / Store Staff
        </div>
        <div class="sig-box">
            Verified / Audited By
        </div>
        <div class="sig-box">
            Store Manager Approval
        </div>
    </div>

</body>
</html>
