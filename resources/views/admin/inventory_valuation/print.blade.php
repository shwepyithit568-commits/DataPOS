<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.inv_val_title') }} - {{ $store->name }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0 0 4px 0;
            font-size: 20px;
            color: #0f172a;
            font-weight: 800;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 11px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            background: #f8fafc;
        }
        .kpi-card span {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            display: block;
        }
        .kpi-card h3 {
            margin: 4px 0 0 0;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #94a3b8;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 16px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #0284c7; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ Print Statement
        </button>
    </div>

    <div class="header">
        <h1>{{ $store->name }}</h1>
        <p>{{ __('messages.inv_val_title') }}</p>
        <p style="font-size: 10px; margin-top: 2px;">Generated Date: {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    <div class="meta-grid">
        <div class="kpi-card">
            <span>{{ __('messages.inv_val_total_cost') }}</span>
            <h3 class="font-mono">{{ format_currency($metrics['total_cost_value'], $store) }}</h3>
        </div>
        <div class="kpi-card">
            <span>{{ __('messages.inv_val_total_retail') }}</span>
            <h3 class="font-mono">{{ format_currency($metrics['total_retail_value'], $store) }}</h3>
        </div>
        <div class="kpi-card">
            <span>{{ __('messages.inv_val_potential_profit') }}</span>
            <h3 class="font-mono" style="color: #16a34a;">{{ format_currency($metrics['potential_profit'], $store) }}</h3>
        </div>
        <div class="kpi-card">
            <span>{{ __('messages.inv_val_units_on_hand') }}</span>
            <h3 class="font-mono">{{ number_format($metrics['total_units']) }} units</h3>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>{{ __('messages.sku') }}</th>
                <th>{{ __('messages.product_name') }}</th>
                <th>{{ __('messages.category') }}</th>
                <th class="text-center">{{ __('messages.qty') }}</th>
                <th class="text-right">{{ __('messages.unit_cost') }}</th>
                <th class="text-right">{{ __('messages.total_cost_value') }}</th>
                <th class="text-right">{{ __('messages.retail_price') }}</th>
                <th class="text-right">{{ __('messages.total_retail_value') }}</th>
                <th class="text-right">{{ __('messages.potential_profit') }}</th>
                <th class="text-center">{{ __('messages.margin') }}</th>
            </tr>
        </thead>
        <tbody>
            @php $idx = 1; @endphp
            @foreach ($products as $p)
                <tr>
                    <td class="text-center">{{ $idx++ }}</td>
                    <td class="font-mono">{{ $p->sku ?? '-' }}</td>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td>{{ $p->category?->name ?? '-' }}</td>
                    <td class="text-center font-mono">{{ format_quantity($p->computed_qty, $store) }}</td>
                    <td class="text-right font-mono">{{ format_currency($p->computed_cost, $store) }}</td>
                    <td class="text-right font-mono" style="font-weight: bold; color: #dc2626;">{{ format_currency($p->computed_cost_value, $store) }}</td>
                    <td class="text-right font-mono">{{ format_currency((float) $p->retail_price, $store) }}</td>
                    <td class="text-right font-mono">{{ format_currency($p->computed_retail_value, $store) }}</td>
                    <td class="text-right font-mono" style="color: #16a34a;">{{ format_currency($p->computed_profit, $store) }}</td>
                    <td class="text-center">{{ $p->computed_margin }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="4" class="text-right">{{ __('messages.grand_total') }}:</td>
                <td class="text-center font-mono">{{ format_quantity($metrics['total_units'], $store) }}</td>
                <td></td>
                <td class="text-right font-mono">{{ format_currency($metrics['total_cost_value'], $store) }}</td>
                <td></td>
                <td class="text-right font-mono">{{ format_currency($metrics['total_retail_value'], $store) }}</td>
                <td class="text-right font-mono">{{ format_currency($metrics['potential_profit'], $store) }}</td>
                <td class="text-center">{{ $metrics['potential_margin'] }}%</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <span>DataPOS {{ __('messages.inventory_valuation_system') }}</span>
        <span>{{ __('messages.page_label') }} 1 / 1</span>
    </div>

</body>
</html>
