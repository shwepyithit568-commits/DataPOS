<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.debt_aging_title') }} - {{ $store->name }}</title>
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
            border-bottom: 2px solid #d97706;
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
        <button onclick="window.print()" style="padding: 8px 16px; background: #d97706; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ Print Statement
        </button>
    </div>

    <div class="header">
        <h1>{{ $store->name }}</h1>
        <p>{{ __('messages.debt_aging_title') }}</p>
        <p style="font-size: 10px; margin-top: 2px;">Generated Date: {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    <div class="meta-grid">
        <div class="kpi-card">
            <span>{{ __('messages.debt_aging_total_receivables') }}</span>
            <h3 class="font-mono" style="color: #dc2626;">Ks {{ number_format($metrics['total_outstanding']) }}</h3>
        </div>
        <div class="kpi-card">
            <span>0 - 30 Days (Current)</span>
            <h3 class="font-mono" style="color: #16a34a;">Ks {{ number_format($metrics['bucket_0_30']) }}</h3>
        </div>
        <div class="kpi-card">
            <span>31 - 60 Days (Follow-up)</span>
            <h3 class="font-mono" style="color: #d97706;">Ks {{ number_format($metrics['bucket_31_60']) }}</h3>
        </div>
        <div class="kpi-card">
            <span>90+ Days (Overdue)</span>
            <h3 class="font-mono" style="color: #991b1b;">Ks {{ number_format($metrics['bucket_90_plus']) }}</h3>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Customer Name</th>
                <th>Phone</th>
                <th class="text-right">Total Due (Ks)</th>
                <th class="text-right">0 - 30d</th>
                <th class="text-right">31 - 60d</th>
                <th class="text-right">61 - 90d</th>
                <th class="text-right">90d+</th>
                <th class="text-center">Overdue Days</th>
                <th class="text-center">Risk</th>
            </tr>
        </thead>
        <tbody>
            @php $idx = 1; @endphp
            @foreach ($customers as $c)
                <tr>
                    <td class="text-center">{{ $idx++ }}</td>
                    <td><strong>{{ $c['customer_name'] }}</strong></td>
                    <td class="font-mono">{{ $c['customer_phone'] }}</td>
                    <td class="text-right font-mono" style="font-weight: bold; color: #dc2626;">{{ number_format($c['total_due']) }}</td>
                    <td class="text-right font-mono">{{ $c['bucket_0_30'] > 0 ? number_format($c['bucket_0_30']) : '-' }}</td>
                    <td class="text-right font-mono">{{ $c['bucket_31_60'] > 0 ? number_format($c['bucket_31_60']) : '-' }}</td>
                    <td class="text-right font-mono">{{ $c['bucket_61_90'] > 0 ? number_format($c['bucket_61_90']) : '-' }}</td>
                    <td class="text-right font-mono" style="font-weight: bold; color: #991b1b;">{{ $c['bucket_90_plus'] > 0 ? number_format($c['bucket_90_plus']) : '-' }}</td>
                    <td class="text-center font-mono">{{ $c['max_overdue_days'] }} d</td>
                    <td class="text-center uppercase" style="font-size: 9px; font-weight: bold;">{{ $c['risk_level'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="3" class="text-right">Grand Total:</td>
                <td class="text-right font-mono" style="color: #dc2626;">{{ number_format($metrics['total_outstanding']) }}</td>
                <td class="text-right font-mono">{{ number_format($metrics['bucket_0_30']) }}</td>
                <td class="text-right font-mono">{{ number_format($metrics['bucket_31_60']) }}</td>
                <td class="text-right font-mono">{{ number_format($metrics['bucket_61_90']) }}</td>
                <td class="text-right font-mono">{{ number_format($metrics['bucket_90_plus']) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <span>DataPOS Debt Aging Analysis System</span>
        <span>Page 1 of 1</span>
    </div>

</body>
</html>
