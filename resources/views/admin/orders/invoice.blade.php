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
    <title>{{ __('messages.invoice_title') }} {{ $order->order_number }} — {{ $store->name }}</title>
    <style>
        /* ---- Burmese font (subset woff2 shipped with the app build) ---- */
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

        html, body {
            font-family: 'Noto Sans Myanmar', 'Pyidaungsu', 'Myanmar Text', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
        }

        /* ---- A4 print page (portrait) ---- */
        @page { size: A4 portrait; margin: 10mm 12mm; }

        @media print {
            html, body {
                margin: 0; padding: 0; background: #fff;
            }
            * {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .sheet {
                box-shadow: none; border-radius: 0; padding: 0; max-width: none;
            }
        }

        /* ---- Screen-only chrome (never printed) ---- */
        body { background: #eef2f7; padding: 24px 12px; }
        .toolbar { max-width: 186mm; margin: 0 auto 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .toolbar .back { color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; }
        .toolbar .back:hover { color: #7c3aed; }
        .toolbar button {
            padding: 9px 18px; border: none; border-radius: 8px; background: #7c3aed;
            color: #fff; font-weight: 800; font-size: 13px; cursor: pointer;
        }
        .toolbar .hint { font-size: 11px; color: #64748b; }

        /* ---- Sheet ---- */
        .sheet {
            max-width: 186mm; margin: 0 auto; background: #fff;
            border-radius: 10px; padding: 26px 30px; box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        }

        /* ---- Header: brand + contact left, INVOICE right ---- */
        .invoice-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            gap: 10mm; padding-bottom: 4mm; border-bottom: 1.5px solid #7c3aed;
        }
        .invoice-brand { min-width: 0; max-width: 70%; }
        .invoice-brand .logo { max-width: 150px; width: 140px; height: auto; display: block; }
        .invoice-brand .store-name { font-size: 15px; font-weight: 800; color: #111827; }
        .invoice-brand .store-sub { font-size: 10px; color: #64748b; margin-top: .5mm; }
        .invoice-contact { margin-top: 2mm; font-size: 10px; color: #475569; line-height: 1.7; }
        .invoice-contact .row { display: flex; gap: 1.5mm; align-items: baseline; }
        .invoice-contact .row .k { font-weight: 700; color: #334155; flex-shrink: 0; }
        .invoice-contact .row .v { overflow-wrap: anywhere; min-width: 0; }

        .invoice-title { text-align: right; flex-shrink: 0; max-width: 45%; }
        .invoice-title h1 { font-size: 28px; font-weight: 900; color: #7c3aed; letter-spacing: 1px; line-height: 1.1; }
        .invoice-title .no { font-size: 12px; font-weight: 700; color: #334155; margin-top: 1mm; overflow-wrap: anywhere; }
        .invoice-title .date { font-size: 10px; color: #64748b; margin-top: .5mm; white-space: nowrap; }

        /* ---- Billed To / Status two-column grid ---- */
        .party-grid {
            display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 8mm; margin-top: 5mm;
        }
        .party-block h3 {
            font-size: 9px; text-transform: uppercase; letter-spacing: 1px;
            color: #94a3b8; margin-bottom: 1.5mm;
        }
        .party-block .line { font-size: 11px; line-height: 1.6; color: #334155; overflow-wrap: anywhere; }
        .party-block .line + .line { margin-top: .8mm; }
        .party-block .line .k { font-weight: 700; color: #111827; }
        .status-pills { display: flex; gap: 2mm; flex-wrap: wrap; }
        .pill { display: inline-block; padding: .8mm 2.5mm; border-radius: 999px; font-size: 8.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; }
        .pill.pending { background: #fef3c7; color: #92400e; }
        .pill.confirmed { background: #d1fae5; color: #065f46; }
        .pill.delivered { background: #dbeafe; color: #1e40af; }
        .pill.cancelled { background: #fee2e2; color: #991b1b; }
        .pill.paid { background: #ede9fe; color: #5b21b6; }
        .pill.unpaid { background: #f1f5f9; color: #475569; }
        .note-box {
            margin-top: 2mm; font-size: 10px; color: #475569; line-height: 1.7;
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 3px;
            padding: 2mm 2.5mm; overflow-wrap: anywhere; word-break: break-word;
        }
        .note-box .k { font-weight: 700; color: #334155; }

        /* ---- Items table (fixed layout; header repeats on every page) ---- */
        table.items { width: 100%; border-collapse: collapse; margin-top: 5mm; table-layout: fixed; }
        thead { display: table-header-group; }
        thead th {
            text-align: left; font-size: 8.5px; text-transform: uppercase; letter-spacing: .8px;
            color: #64748b; padding: 1.8mm 2mm; border-bottom: 1.5px solid #e2e8f0; font-weight: 700;
        }
        thead th.num { text-align: right; }
        thead th.center { text-align: center; }
        tbody td {
            padding: 2mm 2mm; font-size: 11px; color: #334155;
            border-bottom: 1px solid #f1f5f9; vertical-align: top; overflow-wrap: anywhere;
        }
        tbody td.num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        tbody td.center { text-align: center; }
        tbody tr { break-inside: avoid; page-break-inside: avoid; }
        tbody tr:last-child td { border-bottom: none; }
        .item-name { font-weight: 600; }
        .item-variant { font-size: 9.5px; color: #64748b; margin-top: .5mm; }

        /* ---- Totals (compact, right-aligned, never orphaned) ---- */
        .totals { margin: 4mm 0 0 auto; width: 58mm; break-inside: avoid; page-break-inside: avoid; }
        .totals .row { display: flex; justify-content: space-between; padding: .8mm 2mm; font-size: 11px; color: #475569; }
        .totals .row .k { font-weight: 700; }
        .totals .row .v { white-space: nowrap; font-variant-numeric: tabular-nums; }
        .totals .grand {
            display: flex; justify-content: space-between; gap: 3mm;
            font-size: 14px; font-weight: 900; color: #7c3aed;
            background: #f5f3ff; border-radius: 2mm; padding: 2mm 2.5mm; margin-top: 1mm;
        }
        .totals .grand .v { white-space: nowrap; font-variant-numeric: tabular-nums; }

        /* ---- Footer ---- */
        .invoice-foot {
            margin-top: 5mm; padding-top: 3mm; border-top: 1px dashed #e2e8f0;
            font-size: 10px; color: #64748b; line-height: 1.7; text-align: center;
            break-inside: avoid; page-break-inside: avoid; overflow-wrap: anywhere;
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a class="back" href="{{ url('/store/' . $store->slug . '/admin/orders/' . $order->id) }}">← {{ __('messages.invoice_back_to_order') }}</a>
        <button data-print>🖨️ {{ __('messages.invoice_print') }}</button>
        <span class="hint">PDF သိမ်းရာတွင် More settings → Headers and footers ကို ပိတ်ပါ။</span>
    </div>

    <div class="sheet">
        <div class="invoice-header">
            <div class="invoice-brand">
                @if (!empty($setting?->storefrontLogo()))
                    <img class="logo" src="{{ asset('storage/' . $setting->storefrontLogo()) }}" alt="{{ $store->name }}">
                @else
                    <div class="store-name">{{ $setting?->store_name ?? $store->name }}</div>
                @endif

                <div class="invoice-contact">
                    @if ($setting?->address)
                        <div class="row"><span class="k">{{ __('messages.invoice_address') }}</span><span class="v">{{ $setting->address }}</span></div>
                    @endif
                    @if ($setting?->phone)
                        <div class="row"><span class="k">{{ __('messages.invoice_phone') }}</span><span class="v">{{ $setting->phone }}</span></div>
                    @endif
                    @if ($setting?->viber_number)
                        <div class="row"><span class="k">{{ __('messages.invoice_viber') }}</span><span class="v">{{ $setting->viber_number }}</span></div>
                    @endif
                    @if ($setting?->telegram_username)
                        <div class="row"><span class="k">{{ __('messages.invoice_telegram') }}</span><span class="v">{{ $setting->telegram_username }}</span></div>
                    @endif
                </div>
            </div>

            <div class="invoice-title">
                <h1>{{ __('messages.invoice_title') }}</h1>
                <div class="no">{{ $order->order_number }}</div>
                <div class="date">{{ $order->created_at->format('F j, Y · h:i A') }}</div>
            </div>
        </div>

        <div class="party-grid">
            <div class="party-block">
                <h3>{{ __('messages.invoice_billed_to') }}</h3>
                @if ($order->customer_name)
                    <div class="line"><span class="k">{{ $order->customer_name }}</span></div>
                @endif
                @if ($order->customer_phone)
                    <div class="line"><span class="k">{{ __('messages.invoice_phone') }}</span> {{ $order->customer_phone }}</div>
                @endif
                @if ($order->customer_address)
                    <div class="line"><span class="k">{{ __('messages.invoice_address') }}</span> {{ $order->customer_address }}</div>
                @endif
                @if ($order->contact_channel && $order->contact_identifier && $order->contact_identifier !== $order->customer_phone)
                    <div class="line"><span class="k">{{ ucfirst(str_replace('_', ' ', $order->contact_channel)) }}:</span> {{ $order->contact_identifier }}</div>
                @endif
            </div>

            <div class="party-block">
                <h3>{{ __('messages.invoice_status') }}</h3>
                <div class="status-pills">
                    <span class="pill {{ $order->status }}">{{ str_replace('_', ' ', $order->status) }}</span>
                    <span class="pill {{ $order->payment_status }}">{{ __('messages.invoice_payment') }} {{ $order->payment_status }}</span>
                </div>
                @if ($order->customer_note)
                    <div class="note-box"><span class="k">{{ __('messages.invoice_customer_note') }}</span> {{ $order->customer_note }}</div>
                @endif
            </div>
        </div>

        <table class="items">
            <colgroup>
                <col style="width:5%">
                <col style="width:55%">
                <col style="width:10%">
                <col style="width:15%">
                <col style="width:15%">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('messages.invoice_col_item') }}</th>
                    <th class="center">{{ __('messages.invoice_col_qty') }}</th>
                    <th class="num">{{ __('messages.invoice_col_unit_price') }}</th>
                    <th class="num">{{ __('messages.invoice_col_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->product_name }}</div>
                            @if ($item->variant_name && !str_contains((string) $item->product_name, (string) $item->variant_name))
                                <div class="item-variant">{{ $item->variant_name }}{{ $item->variant_sku ? ' · ' . $item->variant_sku : '' }}</div>
                            @elseif ($item->variant_sku)
                                <div class="item-variant">{{ $item->variant_sku }}</div>
                            @endif
                            @if ($item->product && $item->product->product_type === 'service' && trim((string) $item->product->service_duration))
                                <div class="item-variant" style="color:#b45309;">⏱️ {{ __('messages.product_form_service_duration') }}: {{ $item->product->service_duration }}</div>
                            @endif
                            @if ($item->product && $item->product->product_type === 'digital' && trim((string) $item->product->digital_delivery_method))
                                <div class="item-variant" style="color:#0369a1;">📲 {{ __('messages.product_form_digital_delivery_method') }}: {{ $item->product->digital_delivery_method }}</div>
                            @endif
                        </td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td class="num">Ks {{ number_format((float) $item->unit_price) }}</td>
                        <td class="num">Ks {{ number_format((float) $item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            @if ($order->agreed_amount !== null)
                <div class="row"><span class="k">{{ __('messages.invoice_original_total') }}</span><span class="v">Ks {{ number_format((float) $order->total_amount) }}</span></div>
                <div class="row"><span class="k">{{ __('messages.invoice_agreed_amount') }}</span><span class="v">Ks {{ number_format((float) $order->agreed_amount) }}</span></div>
                <div class="grand"><span>{{ __('messages.invoice_total_due') }}</span><span class="v">Ks {{ number_format((float) $order->agreed_amount) }}</span></div>
            @else
                <div class="grand"><span>{{ __('messages.invoice_total_due') }}</span><span class="v">Ks {{ number_format((float) $order->total_amount) }}</span></div>
            @endif
        </div>

        <div class="invoice-foot">
            ဝယ်ယူအားပေးမှုအတွက် ကျေးဇူးတင်ပါတယ် — ပစ္စည်းအသေးစိတ် မေးရန်
            @if ($setting?->phone) {{ $setting->phone }} @endif
            @if ($setting?->telegram_username) · t.me/{{ ltrim($setting->telegram_username, '@') }} @endif
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        // The invoice is a standalone page (no admin bundle), so the delegated
        // data-print handler from csp-helpers.js is not loaded here — wire the
        // Print / Save PDF button up directly (CSP nonce-stamped inline script).
        document.addEventListener('click', (e) => {
            const el = e.target.closest('[data-print]');
            if (el) {
                e.preventDefault();
                window.print();
            }
        }, true);
    </script>
</body>
</html>
