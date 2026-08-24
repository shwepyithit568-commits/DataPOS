<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.receivables_statement_title') }} - {{ $customer->name }} - {{ $store->name }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Pyidaungsu", "Myanmar3", sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 20px;
        }

        .print-container {
            background: #ffffff;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* ── A4 Format ── */
        .format-a4 {
            max-width: 800px;
            padding: 40px;
            border-radius: 8px;
        }

        /* ── Thermal Format (80mm) ── */
        .format-thermal {
            max-width: 320px;
            padding: 16px 12px;
            font-size: 12px;
        }

        .header-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
        }

        .header-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .statement-badge {
            display: inline-block;
            padding: 4px 12px;
            background-color: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            text-align: center;
        }

        .summary-item .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
        }

        .summary-item .val {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 4px;
        }

        .summary-item .val.debt {
            color: #e11d48;
        }

        .summary-item .val.paid {
            color: #059669;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }

        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-align: left;
            padding: 10px 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            padding-top: 20px;
        }

        .sign-box {
            text-align: center;
            width: 200px;
        }

        .sign-line {
            border-top: 1px dashed #94a3b8;
            margin-bottom: 6px;
        }

        .print-actions {
            max-width: 800px;
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
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .print-actions {
                display: none !important;
            }
            .print-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    {{-- Action Bar (Hidden during Print) --}}
    <div class="print-actions">
        <button type="button" class="btn" onclick="window.close()">{{ __('messages.close') }}</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ {{ __('messages.print') }}</button>
    </div>

    {{-- Printable Container --}}
    <div class="print-container {{ $format === 'thermal' ? 'format-thermal' : 'format-a4' }}">

        {{-- Store Header --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
            <div>
                <div class="header-title">{{ $store->name }}</div>
                @if($store->setting?->phone)
                    <div class="header-sub">📞 {{ $store->setting->phone }}</div>
                @endif
                @if($store->setting?->address)
                    <div class="header-sub">📍 {{ $store->setting->address }}</div>
                @endif
            </div>
            <div style="text-align: right;">
                <div class="statement-badge">{{ __('messages.receivables_statement_badge') }}</div>
                <div class="header-sub" style="margin-top: 4px;">{{ __('messages.date') }}: {{ now()->translatedFormat('d M Y') }}</div>
            </div>
        </div>

        {{-- Customer Info --}}
        <div style="margin-top: 16px; padding: 12px; background-color: #f8fafc; border-radius: 6px; font-size: 13px;">
            <div style="font-weight: bold; color: #0f172a; font-size: 15px;">{{ $customer->name }}</div>
            @if($customer->phone)
                <div style="color: #64748b; margin-top: 2px;">📞 {{ $customer->phone }}</div>
            @endif
            @if($customer->address)
                <div style="color: #64748b; margin-top: 2px;">📍 {{ $customer->address }}</div>
            @endif
        </div>

        {{-- Summary Cards --}}
        @php
            $totalDebtIncurred = $history->where('amount', '>', 0)->sum('amount');
            $totalCollected = $history->where('type', 'collection')->sum(fn($h) => abs((float) $h->amount));
            $bal = (float) $balance;
        @endphp
        <div class="summary-box">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="label">{{ __('messages.receivables_total_incurred') }}</div>
                    <div class="val">{{ number_format($totalDebtIncurred, 0) }} Ks</div>
                </div>
                <div class="summary-item">
                    <div class="label">{{ __('messages.receivables_total_paid') }}</div>
                    <div class="val paid">{{ number_format($totalCollected, 0) }} Ks</div>
                </div>
                <div class="summary-item">
                    <div class="label">{{ __('messages.receivables_current_debt') }}</div>
                    <div class="val debt">{{ number_format($bal, 0) }} Ks</div>
                </div>
            </div>
        </div>

        {{-- Transactions Ledger Table --}}
        <table>
            <thead>
                <tr>
                    <th>{{ __('messages.date') }}</th>
                    <th>{{ __('messages.type') }}</th>
                    <th>{{ __('messages.reference') }}</th>
                    <th class="text-right">{{ __('messages.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $item)
                    @php
                        $isPlus = (float) $item->amount > 0;
                        $amt = abs((float) $item->amount);
                    @endphp
                    <tr>
                        <td style="white-space: nowrap;">{{ $item->occurred_at ? $item->occurred_at->format('d/m/Y') : $item->created_at->format('d/m/Y') }}</td>
                        <td>
                            @if($item->type === 'sale_debt')
                                {{ __('messages.receivables_type_sale_debt') }}
                            @elseif($item->type === 'collection')
                                {{ __('messages.receivables_type_collection') }}
                            @elseif($item->type === 'opening_balance')
                                {{ __('messages.receivables_type_opening_balance') }}
                            @else
                                {{ $item->type }}
                            @endif
                        </td>
                        <td class="font-mono">
                            @if($item->source_type === 'pos_sale')
                                Sale #{{ $item->source_id }}
                            @else
                                {{ $item->notes ?: '-' }}
                            @endif
                        </td>
                        <td class="text-right font-mono" style="font-weight: bold; color: {{ $isPlus ? '#e11d48' : '#059669' }};">
                            {{ $isPlus ? '+' : '-' }} {{ number_format($amt, 0) }} Ks
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px; color: #94a3b8;">
                            {{ __('messages.no_history_records') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Final Outstanding Box --}}
        <div style="padding: 12px 16px; background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
            <div style="font-weight: 700; color: #9f1239; font-size: 14px;">{{ __('messages.receivables_final_balance_due') }}:</div>
            <div style="font-weight: 900; font-size: 18px; color: #e11d48;" class="font-mono">{{ number_format($bal, 0) }} Ks</div>
        </div>

        {{-- Signature Section (A4 only) --}}
        @if($format === 'a4')
            <div class="signature-section">
                <div class="sign-box">
                    <div class="sign-line"></div>
                    <div style="font-size: 11px; color: #64748b;">{{ __('messages.receivables_customer_signature') }}</div>
                </div>
                <div class="sign-box">
                    <div class="sign-line"></div>
                    <div style="font-size: 11px; color: #64748b;">{{ __('messages.receivables_authorized_signature') }}</div>
                </div>
            </div>
        @endif

        {{-- Footer note --}}
        <div style="text-align: center; margin-top: 24px; font-size: 11px; color: #94a3b8;">
            {{ __('messages.thank_you_for_business') }} — {{ $store->name }}
        </div>
    </div>

</body>
</html>
