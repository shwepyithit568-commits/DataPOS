<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.profit_loss_statement_title') }} - {{ $store->name }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Pyidaungsu", "Myanmar3", sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: #0f172a;
            padding: 24px;
        }

        .statement-container {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .header-section {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .store-title {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .store-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .doc-badge {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4338ca;
            text-align: right;
        }

        .period-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        table.income-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
        }

        table.income-table th {
            text-align: left;
            padding: 8px;
            border-bottom: 2px solid #cbd5e1;
            color: #475569;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
        }

        table.income-table td {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .section-header {
            font-weight: 800;
            background-color: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            text-transform: uppercase;
            padding: 8px !important;
        }

        .indent {
            padding-left: 24px !important;
            color: #475569;
        }

        .subtotal-row {
            font-weight: 700;
            border-top: 1px solid #cbd5e1 !important;
            border-bottom: 1px solid #cbd5e1 !important;
            background-color: #fafafa;
        }

        .major-row {
            font-weight: 900;
            font-size: 14px;
            background-color: #e0e7ff;
            color: #312e81;
        }

        .net-profit-row {
            font-weight: 900;
            font-size: 16px;
            background-color: #dcfce7;
            color: #14532d;
            border-top: 2px solid #15803d !important;
            border-bottom: 3px double #15803d !important;
        }

        .net-loss-row {
            font-weight: 900;
            font-size: 16px;
            background-color: #ffe4e6;
            color: #881337;
            border-top: 2px solid #be123c !important;
            border-bottom: 3px double #be123c !important;
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            text-align: right;
        }

        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            padding-top: 30px;
        }

        .sign-box {
            width: 200px;
            text-align: center;
        }

        .sign-line {
            border-top: 1px dashed #94a3b8;
            margin-bottom: 6px;
        }

        .no-print-bar {
            max-width: 820px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
        }

        .btn-primary {
            background: #4338ca;
            color: #ffffff;
            border-color: #4338ca;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .statement-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Toolbar --}}
    <div class="no-print-bar">
        <button type="button" class="btn" onclick="window.close()">{{ __('messages.close') }}</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ {{ __('messages.print') }}</button>
    </div>

    {{-- Statement Sheet --}}
    <div class="statement-container">

        {{-- Store Header --}}
        <div class="header-section">
            <div>
                <div class="store-title">{{ $store->name }}</div>
                @if($store->setting?->phone)
                    <div class="store-sub">📞 {{ $store->setting->phone }}</div>
                @endif
                @if($store->setting?->address)
                    <div class="store-sub">📍 {{ $store->setting->address }}</div>
                @endif
            </div>
            <div>
                <div class="doc-badge">{{ __('messages.profit_loss_statement_title') }}</div>
                <div class="store-sub" style="text-align: right; margin-top: 4px;">{{ __('messages.date') }}: {{ now()->translatedFormat('d M Y') }}</div>
            </div>
        </div>

        {{-- Period Box --}}
        <div class="period-box">
            <div>
                <span style="color: #64748b;">{{ __('messages.period') }}:</span>
                <strong>{{ $statement['period']['label'] }}</strong>
            </div>
            <div>
                <span style="color: #64748b;">Currency:</span>
                <strong>Myanmar Kyat (MMK)</strong>
            </div>
        </div>

        {{-- Formal Income Statement Table --}}
        <table class="income-table">
            <thead>
                <tr>
                    <th>Account / Particulars</th>
                    <th class="font-mono" style="width: 160px;">Amount (MMK)</th>
                    <th class="font-mono" style="width: 160px;">Total (MMK)</th>
                </tr>
            </thead>
            <tbody>
                {{-- 1. REVENUE --}}
                <tr class="section-header">
                    <td colspan="3">1. REVENUE (အရောင်းရငွေများ)</td>
                </tr>
                <tr>
                    <td class="indent">Gross Sales (စုစုပေါင်း အရောင်းရငွေ)</td>
                    <td class="font-mono">{{ number_format($statement['revenue']['gross_sales'], 0) }}</td>
                    <td></td>
                </tr>
                @if($statement['revenue']['discounts'] > 0)
                    <tr>
                        <td class="indent">Less: Sales Discounts Allowed (လျော့စျေးများ)</td>
                        <td class="font-mono" style="color: #e11d48;">- {{ number_format($statement['revenue']['discounts'], 0) }}</td>
                        <td></td>
                    </tr>
                @endif
                @if($statement['revenue']['returns'] > 0)
                    <tr>
                        <td class="indent">Less: Sales Returns & Refunds (ပြန်အမ်းငွေများ)</td>
                        <td class="font-mono" style="color: #e11d48;">- {{ number_format($statement['revenue']['returns'], 0) }}</td>
                        <td></td>
                    </tr>
                @endif
                <tr class="subtotal-row">
                    <td><strong>NET SALES REVENUE (အသားတင် အရောင်းရငွေ)</strong></td>
                    <td></td>
                    <td class="font-mono" style="font-weight: 800; color: #0284c7;">{{ number_format($statement['revenue']['net_sales'], 0) }}</td>
                </tr>

                {{-- 2. COST OF GOODS SOLD --}}
                <tr class="section-header">
                    <td colspan="3">2. COST OF GOODS SOLD (ရောင်းရသော ပစ္စည်းများ၏ အရင်းစရိတ်)</td>
                </tr>
                <tr>
                    <td class="indent">Cost of Sales (ကုန်ပစ္စည်းအရင်းစရိတ်)</td>
                    <td class="font-mono">{{ number_format($statement['cogs']['gross_cogs'], 0) }}</td>
                    <td></td>
                </tr>
                @if($statement['cogs']['returns_cogs'] > 0)
                    <tr>
                        <td class="indent">Less: Cost of Returned Goods (ပြန်သွင်းအရင်း)</td>
                        <td class="font-mono" style="color: #059669;">- {{ number_format($statement['cogs']['returns_cogs'], 0) }}</td>
                        <td></td>
                    </tr>
                @endif
                <tr class="subtotal-row">
                    <td><strong>NET COST OF GOODS SOLD (အသားတင် ပစ္စည်းအရင်း)</strong></td>
                    <td></td>
                    <td class="font-mono" style="font-weight: 800; color: #d97706;">{{ number_format($statement['cogs']['net_cogs'], 0) }}</td>
                </tr>

                {{-- 3. GROSS PROFIT --}}
                <tr class="major-row">
                    <td>GROSS PROFIT (စုစုပေါင်း အကြမ်းအမြတ်) [Margin: {{ $statement['gross_margin'] }}%]</td>
                    <td></td>
                    <td class="font-mono">{{ number_format($statement['gross_profit'], 0) }}</td>
                </tr>

                {{-- 4. OPERATING EXPENSES --}}
                <tr class="section-header">
                    <td colspan="3">4. OPERATING EXPENSES (ဆိုင်လည်ပတ်စရိတ်များ)</td>
                </tr>
                @forelse ($statement['expenses']['by_category'] as $cat)
                    <tr>
                        <td class="indent">{{ $cat['name'] }}</td>
                        <td class="font-mono">{{ number_format($cat['amount'], 0) }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td class="indent" style="color: #94a3b8; font-style: italic;">No recorded operating expenses in this period</td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
                <tr class="subtotal-row">
                    <td><strong>TOTAL OPERATING EXPENSES (စုစုပေါင်း လည်ပတ်စရိတ်)</strong></td>
                    <td></td>
                    <td class="font-mono" style="font-weight: 800; color: #e11d48;">{{ number_format($statement['expenses']['total'], 0) }}</td>
                </tr>

                {{-- 5. NET PROFIT / LOSS --}}
                @php $isProf = $statement['net_profit'] >= 0; @endphp
                <tr class="{{ $isProf ? 'net-profit-row' : 'net-loss-row' }}">
                    <td>
                        {{ $isProf ? 'NET OPERATING PROFIT (အသားတင် အမြတ်)' : 'NET OPERATING LOSS (အသားတင် အရှုံး)' }}
                        <span style="font-size: 11px; font-weight: normal; margin-left: 8px;">[Net Margin: {{ $statement['net_margin'] }}%]</span>
                    </td>
                    <td></td>
                    <td class="font-mono">{{ number_format($statement['net_profit'], 0) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Signatures --}}
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div style="font-size: 11px; color: #64748b;">Prepared By (စာရင်းကိုင်)</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div style="font-size: 11px; color: #64748b;">Audited / Store Manager</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div style="font-size: 11px; color: #64748b;">Managing Director / Owner</div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #94a3b8;">
            Generated by {{ config('app.name', 'DataPOS') }} Enterprise POS System • {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>

</body>
</html>
