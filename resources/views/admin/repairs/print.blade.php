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
    <title>{{ __('messages.repair_ticket_heading') }} {{ $repair->job_number }} — {{ $store->name }}</title>
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
            font-size: 13px;
            line-height: 1.5;
        }

        .ticket {
            width: 420px;
            margin: 24px auto;
            background: #fff;
            border-radius: 8px;
            padding: 22px 20px;
            box-shadow: 0 4px 16px rgba(2, 6, 23, .08);
        }

        @media print {
            body { background: #fff; }
            .ticket { margin: 0; box-shadow: none; border-radius: 0; width: 100%; }
            .no-print { display: none !important; }
            @page { size: A5 portrait; margin: 10mm; }
        }

        .store-name { font-size: 16px; font-weight: 800; text-align: center; }
        .store-meta { text-align: center; font-size: 11px; color: #64748b; margin-top: 2px; }

        .rule { border: none; border-top: 1px dashed #cbd5e1; margin: 11px 0; }

        .ticket-head { text-align: center; }
        .ticket-title { font-size: 15px; font-weight: 800; letter-spacing: .3px; }
        .job-no { font-size: 14px; font-weight: 800; color: #0284c7; font-family: Consolas, monospace; margin-top: 2px; }

        .meta-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px; }
        .meta-row .label { color: #64748b; }

        .section-title { font-size: 11px; font-weight: 800; color: #0284c7; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
        .kv { display: flex; gap: 8px; font-size: 12px; margin-bottom: 3px; }
        .kv .label { color: #64748b; width: 110px; flex-shrink: 0; }
        .kv .value { font-weight: 600; }

        table.items { width: 100%; border-collapse: collapse; font-size: 12px; }
        table.items th { text-align: left; color: #64748b; font-size: 11px; border-bottom: 1px solid #e2e8f0; padding: 4px 0; }
        table.items td { padding: 4px 0; border-bottom: 1px dotted #e2e8f0; vertical-align: top; }
        table.items td.num, table.items th.num { text-align: right; }
        table.items tfoot td { border-bottom: none; padding-top: 6px; font-weight: 800; }

        .money-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; font-size: 12px; }
        .money-grid .label { color: #64748b; }
        .money-grid .value { font-weight: 700; text-align: right; }
        .money-grid .balance .value { color: #d97706; font-weight: 800; }

        .footer-note { font-size: 10.5px; color: #64748b; margin-top: 4px; }
        .print-btn { display: block; width: 200px; margin: 18px auto 0; padding: 10px 0; text-align: center;
            background: #7c3aed; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-head">
            <div class="store-name">{{ $store->name }}</div>
            <div class="store-meta">{{ $store->slug }} · {{ __('messages.sidebar_repair_center') }}</div>
        </div>

        <hr class="rule">
        <div class="ticket-head">
            <div class="ticket-title">{{ __('messages.repair_ticket_heading') }}</div>
            <div class="job-no">{{ $repair->job_number }}</div>
        </div>

        <div class="meta-row" style="margin-top:8px;">
            <span class="label">{{ __('messages.repair_received_at') }}</span>
            <span>{{ $repair->created_at->format('M d, Y H:i') }}</span>
        </div>
        <div class="meta-row">
            <span class="label">{{ __('messages.status') }}</span>
            <span>{{ __('messages.repair_status_' . $repair->status) }}</span>
        </div>
        @if ($repair->estimated_completion)
            <div class="meta-row">
                <span class="label">{{ __('messages.repair_ticket_est_completion') }}</span>
                <span>{{ $repair->estimated_completion->format('M d, Y') }}</span>
            </div>
        @endif
        @if ($repair->voucher_no)
            <div class="meta-row">
                <span class="label">{{ __('messages.repair_voucher_no') }}</span>
                <span>{{ $repair->voucher_no }}</span>
            </div>
        @endif

        <hr class="rule">

        <div class="section-title">{{ __('messages.repair_ticket_customer') }}</div>
        <div class="kv"><span class="label">{{ __('messages.repair_customer_label') }}</span>
            <span class="value">{{ $repair->customer?->name ?? $repair->contact_name ?? '—' }}</span></div>
        @if ($repair->contact_phone || $repair->customer?->phone)
            <div class="kv"><span class="label">{{ __('messages.repair_contact_phone') }}</span>
                <span class="value">{{ $repair->contact_phone ?? $repair->customer->phone }}</span></div>
        @endif

        <hr class="rule">

        <div class="section-title">{{ __('messages.repair_ticket_device') }}</div>
        <div class="kv"><span class="label">{{ __('messages.repair_device_type') }}</span>
            <span class="value">{{ $repair->device_type }}</span></div>
        @if ($repair->model)
            <div class="kv"><span class="label">{{ __('messages.repair_model') }}</span>
                <span class="value">{{ $repair->model }}</span></div>
        @endif
        @if ($repair->imei_serial)
            <div class="kv"><span class="label">IMEI / Serial</span>
                <span class="value">{{ $repair->imei_serial }}</span></div>
        @endif
        <div class="kv" style="margin-top:5px;"><span class="label">{{ __('messages.repair_ticket_problem') }}</span>
            <span class="value">{{ $repair->reported_problem }}</span></div>

        @if ($repair->items->isNotEmpty())
            <hr class="rule">
            <div class="section-title">{{ __('messages.repair_ticket_items') }}</div>
            <table class="items">
                <thead>
                    <tr>
                        <th>{{ __('messages.repair_item_name') }}</th>
                        <th class="num">{{ __('messages.repair_item_qty') }}</th>
                        <th class="num">{{ __('messages.repair_item_price') }}</th>
                        <th class="num">{{ __('messages.repair_item_subtotal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($repair->items as $item)
                        <tr>
                            <td>{{ $item->name }} @if ($item->isPart())<br><span style="color:#94a3b8;font-size:10px;">{{ $item->is_deducted ? __('messages.repair_deducted') : __('messages.repair_item_part') }}</span>@endif</td>
                            <td class="num">{{ $item->quantity }}</td>
                            <td class="num">{{ number_format((float) $item->unit_price, 0) }}</td>
                            <td class="num">{{ number_format((float) $item->subtotal, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">{{ __('messages.repair_items_total') }}</td>
                        <td class="num">{{ number_format($repair->itemsTotal(), 0) }} MMK</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <hr class="rule">

        <div class="money-grid">
            <div><span class="label">{{ __('messages.repair_estimated_charge') }}</span>
                <div class="value">{{ number_format((float) $repair->estimated_charge, 0) }} MMK</div></div>
            <div><span class="label">{{ __('messages.repair_final_charge') }}</span>
                <div class="value">{{ $repair->final_charge !== null ? number_format((float) $repair->final_charge, 0) . ' MMK' : '—' }}</div></div>
            <div><span class="label">{{ __('messages.repair_ticket_paid') }}</span>
                <div class="value">{{ number_format($repair->paidAmount(), 0) }} MMK</div></div>
            <div class="balance"><span class="label">{{ __('messages.repair_ticket_balance') }}</span>
                <div class="value">{{ number_format($repair->outstanding(), 0) }} MMK</div></div>
        </div>

        @if ($repair->warranty_notes)
            <hr class="rule">
            <div class="section-title">{{ __('messages.repair_warranty_notes') }}</div>
            <div style="font-size:12px;">{{ $repair->warranty_notes }}</div>
        @endif

        <hr class="rule">
        <div class="footer-note">{{ __('messages.repair_ticket_footer') }}</div>
        <div class="footer-note" style="margin-top:6px;">
            {{ __('messages.repair_printed_date') }}: {{ now()->format('M d, Y H:i') }} · {{ auth()->user()?->name ?? '' }}
        </div>
    </div>

    <button class="print-btn no-print" onclick="window.print()">{{ __('messages.repair_print_ticket') }}</button>
</body>
</html>
