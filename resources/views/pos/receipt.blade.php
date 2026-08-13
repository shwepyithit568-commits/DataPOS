@php
    // Resolve the Noto Sans Myanmar woff2 from the Vite manifest when the build
    // exists; fall back to no @font-face (system Myanmar fonts) if not.
    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar-Regular.woff2');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <title>Receipt {{ $sale->receipt_number }} — {{ $store->name }}</title>
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
            font-family: 'Noto Sans Myanmar', -apple-system, 'Segoe UI', sans-serif;
            color: #0f172a;
            background: #f1f5f9;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ---- Receipt (80mm-ish) ---- */
        .receipt {
            width: 320px;
            margin: 24px auto;
            background: #fff;
            border-radius: 8px;
            padding: 20px 18px;
            box-shadow: 0 4px 16px rgba(2, 6, 23, .08);
        }

        @media print {
            body { background: #fff; }
            .receipt { margin: 0; box-shadow: none; border-radius: 0; width: 100%; }
            .no-print { display: none !important; }
        }

        .store-name { font-size: 17px; font-weight: 800; text-align: center; }
        .store-meta { text-align: center; font-size: 12px; color: #64748b; margin-top: 2px; }

        .rule { border: none; border-top: 1px dashed #cbd5e1; margin: 12px 0; }

        .receipt-no { text-align: center; font-size: 15px; font-weight: 800; letter-spacing: .3px; }
        .receipt-no span { color: #0284c7; }

        .meta-row { display: flex; justify-content: space-between; font-size: 12px; color: #334155; padding: 1px 0; }
        .meta-row b { color: #0f172a; font-weight: 700; }

        table.items { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.items th {
            text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .4px;
            color: #64748b; border-bottom: 1px solid #e2e8f0; padding: 4px 0;
        }
        table.items td { padding: 5px 0; vertical-align: top; }
        table.items td.amt, table.items th.amt { text-align: right; white-space: nowrap; }
        .qty-price { font-size: 12px; color: #64748b; }

        .totals { margin-top: 6px; }
        .total-row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 13px; }
        .total-row.grand { font-size: 15px; font-weight: 800; border-top: 1px solid #e2e8f0; margin-top: 6px; padding-top: 6px; }
        .total-row .change { color: #059669; font-weight: 700; }

        .footer { text-align: center; font-size: 11px; color: #94a3b8; margin-top: 14px; }
        .reprint-note { text-align: center; font-size: 11px; color: #f59e0b; font-weight: 700; margin-top: 6px; }

        /* ---- Screen-only chrome ---- */
        .toolbar {
            max-width: 320px; margin: 16px auto 0; display: flex; gap: 8px; padding: 0 12px;
        }
        .toolbar a, .toolbar button {
            flex: 1; text-align: center; padding: 10px; border-radius: 10px;
            font-size: 13px; font-weight: 700; text-decoration: none; border: none; cursor: pointer;
        }
        .toolbar .btn-print { background: #0284c7; color: #fff; }
        .toolbar .btn-back { background: #e2e8f0; color: #334155; }
    </style>
</head>
<body>
    <div class="receipt">
        <p class="store-name">{{ $store->name }}</p>
        @if ($store->viber_number || $store->telegram_username)
            <p class="store-meta">
                {{ $store->viber_number ? 'Viber/Phone: ' . $store->viber_number : '' }}
                {{ $store->telegram_username ? ' · Telegram: ' . $store->telegram_username : '' }}
            </p>
        @endif

        <hr class="rule">

        <p class="receipt-no">Receipt <span>#{{ $sale->receipt_number }}</span></p>
        <hr class="rule">

        <div class="meta-row"><span>Date</span><b>{{ $sale->posted_at?->format('d M Y, H:i') }}</b></div>
        <div class="meta-row"><span>Cashier</span><b>{{ $sale->cashier?->name ?? '—' }}</b></div>
        @if ($sale->customer)
            <div class="meta-row"><span>Customer</span><b>{{ $sale->customer->name }}</b></div>
        @endif
        @if ($sale->cashierShift?->register_name)
            <div class="meta-row"><span>Register</span><b>{{ $sale->cashierShift->register_name }}</b></div>
        @endif

        <hr class="rule">

        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="amt">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            <div class="qty-price">{{ rtrim(rtrim($item->quantity, '0'), '.') }} × Ks {{ number_format((float) $item->unit_price) }}</div>
                        </td>
                        <td class="amt">Ks {{ number_format((float) $item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="rule">

        <div class="totals">
            @php $balanceDue = $sale->payments->firstWhere('method', 'credit')?->amount ?? '0'; @endphp
            @foreach ($sale->payments as $payment)
                <div class="total-row">
                    <span>{{ $payment->method === 'credit' ? __('messages.payment_credit') : ucfirst($payment->method) }}</span>
                    <span>Ks {{ number_format((float) $payment->amount) }}</span>
                </div>
                @if ((float) $payment->change_given > 0)
                    <div class="total-row">
                        <span>Change</span>
                        <span class="change">− Ks {{ number_format((float) $payment->change_given) }}</span>
                    </div>
                @endif
            @endforeach
            <div class="total-row grand">
                <span>Total</span>
                <span>Ks {{ number_format((float) $sale->total) }}</span>
            </div>
            @if ((float) $balanceDue > 0)
                <div class="total-row" style="color:#d97706;font-weight:700;">
                    <span>Balance due</span>
                    <span>Ks {{ number_format((float) $balanceDue) }}</span>
                </div>
            @endif
        </div>

        @if ($isReprint)
            <p class="reprint-note">COPY — REPRINT #{{ $printCount }}</p>
        @endif

        <p class="footer">Thank you for your purchase!<br>{{ $store->name }} · {{ now()->format('Y') }}</p>
    </div>

    <div class="toolbar no-print">
        <button type="button" class="btn-print" onclick="window.print()">🖨️ Print / Save PDF</button>
        <a href="{{ url('/store/' . $store->slug . '/pos') }}" class="btn-back">← {{ __('messages.back_to_pos') }}</a>
    </div>
</body>
</html>
