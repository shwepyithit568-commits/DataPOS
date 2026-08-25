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
            <h3 class="font-mono">Ks {{ number_format($metrics['total_cost_value']) }}</h3>
        </div>
        <div class="kpi-card">
            <span>{{ __('messages.inv_val_total_retail') }}</span>
            <h3 class="font-mono">Ks {{ number_format($metrics['total_retail_value']) }}</h3>
        </div>
        <div class="kpi-card">
            <span>{{ __('messages.inv_val_potential_profit') }}</span>
            <h3 class="font-mono" style="color: #16a34a;">Ks {{ number_format($metrics['potential_profit']) }}</h3>
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
                <th>SKU</th>
                <th>Product Name</th>
                <th>Category</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Cost</th>
                <th class="text-right">Total Cost Value</th>
                <th class="text-right">Retail Price</th>
                <th class="text-right">Total Retail Value</th>
                <th class="text-right">Potential Profit</th>
                <th class="text-center">Margin</th>
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
                    <td class="text-center font-mono">{{ number_format($p->computed_qty) }}</td>
                    <td class="text-right font-mono">{{ number_format($p->computed_cost) }}</td>
                    <td class="text-right font-mono" style="font-weight: bold; color: #dc2626;">{{ number_format($p->computed_cost_value) }}</td>
                    <td class="text-right font-mono">{{ number_format((float) $p->retail_price) }}</td>
                    <td class="text-right font-mono">{{ number_format($p->computed_retail_value) }}</td>
                    <td class="text-right font-mono" style="color: #16a34a;">{{ number_format($p->computed_profit) }}</td>
                    <td class="text-center">{{ $p->computed_margin }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="4" class="text-right">Grand Total:</td>
                <td class="text-center font-mono">{{ number_format($metrics['total_units']) }}</td>
                <td></td>
                <td class="text-right font-mono">{{ number_format($metrics['total_cost_value']) }}</td>
                <td></td>
                <td class="text-right font-mono">{{ number_format($metrics['total_retail_value']) }}</td>
                <td class="text-right font-mono">{{ number_format($metrics['potential_profit']) }}</td>
                <td class="text-center">{{ $metrics['potential_margin'] }}%</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <span>DataPOS Inventory Valuation System</span>
        <span>Page 1 of 1</span>
    </div>

</body>
</html>
