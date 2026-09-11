@php
    $tmpl = $voucherTemplate ?? null;
    $storeName = data_get($tmpl, 'header_title') ?: $store->name;
    $storeSubtitle = data_get($tmpl, 'header_subtitle');
    $storePhone = data_get($tmpl, 'phone') ?: ($store->setting?->phone ?? $store->phone);
    $storeAddress = data_get($tmpl, 'address') ?: ($store->setting?->address ?? $store->address);
    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
    $logoUrl = $showLogo ? ($templateLogo ?? ($store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null)) : null;
    $footerGreeting = data_get($tmpl, 'footer_greeting');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - {{ $transaction->transaction_number }} - {{ $storeName }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .voucher-box {
            max-width: 550px;
            margin: 0 auto;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .store-name {
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .voucher-title {
            font-size: 14px;
            font-weight: 700;
            color: #475569;
            margin: 0;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .meta-label {
            color: #64748b;
            font-weight: 600;
        }
        .meta-value {
            font-weight: 700;
            font-family: monospace;
        }
        .amount-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 4px;
            font-weight: 700;
        }
        .amount-num {
            font-size: 24px;
            font-weight: 800;
            font-family: monospace;
            color: #0f172a;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 10px;
        }
        .sig-block {
            text-align: center;
            width: 40%;
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .btn-print {
            display: block;
            margin: 20px auto 0;
            background: #7c3aed;
            color: #fff;
            border: none;
            padding: 10px 24px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
        }
        @media print {
            .btn-print {
                display: none;
            }
            body {
                padding: 0;
            }
            .voucher-box {
                border: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="voucher-box">
    <div class="header">
        @if ($logoUrl)
            <div style="margin-bottom: 8px;">
                <img src="{{ $logoUrl }}" alt="{{ $storeName }}" style="max-height: 48px; max-width: 160px; object-fit: contain; margin: 0 auto; display: block;">
            </div>
        @endif
        <h1 class="store-name">{{ $storeName }}</h1>
        @if ($storeSubtitle)
            <div style="font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px;">{{ $storeSubtitle }}</div>
        @endif
        @if ($storePhone || $storeAddress)
            <div style="font-size: 10.5px; color: #64748b; margin-bottom: 6px;">
                @if ($storePhone) 📞 {{ $storePhone }} @endif
                @if ($storeAddress) | 📍 {{ $storeAddress }} @endif
            </div>
        @endif
        <p class="voucher-title">
            @if($transaction->type === 'deposit')
                RECEIPT VOUCHER (ငွေရပြေစာ)
            @elseif($transaction->type === 'withdrawal')
                PAYMENT VOUCHER (ငွေပေးပြေစာ)
            @else
                FUND TRANSFER VOUCHER (ငွေလွှဲပြေစာ)
            @endif
        </p>
    </div>

    <div class="meta-row">
        <span class="meta-label">{{ __('messages.voucher_no') }}:</span>
        <span class="meta-value">{{ $transaction->transaction_number }}</span>
    </div>
    <div class="meta-row">
        <span class="meta-label">{{ __('messages.date_time') }}:</span>
        <span class="meta-value">{{ $transaction->transaction_date->format('Y-m-d h:i A') }}</span>
    </div>
    <div class="meta-row">
        <span class="meta-label">{{ __('messages.transaction_type') }}:</span>
        <span class="meta-value">{{ strtoupper($transaction->type) }}</span>
    </div>

    @if($transaction->fromAccount)
        <div class="meta-row">
            <span class="meta-label">{{ __('messages.from_account') }}:</span>
            <span class="meta-value">{{ $transaction->fromAccount->name }}</span>
        </div>
    @endif

    @if($transaction->toAccount)
        <div class="meta-row">
            <span class="meta-label">{{ __('messages.to_account') }}:</span>
            <span class="meta-value">{{ $transaction->toAccount->name }}</span>
        </div>
    @endif

    @if($transaction->category)
        <div class="meta-row">
            <span class="meta-label">{{ __('messages.category') }} / {{ __('messages.purpose') }}:</span>
            <span class="meta-value">{{ ucwords(str_replace('_', ' ', $transaction->category)) }}</span>
        </div>
    @endif

    @if($transaction->payer_or_payee)
        <div class="meta-row">
            <span class="meta-label">{{ __('messages.payer_payee') }}:</span>
            <span class="meta-value">{{ $transaction->payer_or_payee }}</span>
        </div>
    @endif

    @if($transaction->reference_no)
        <div class="meta-row">
            <span class="meta-label">{{ __('messages.reference_slip') }}:</span>
            <span class="meta-value">{{ $transaction->reference_no }}</span>
        </div>
    @endif

    <div class="amount-box">
        <div class="amount-title">{{ __('messages.transactions_amount') }}</div>
        <div class="amount-num">{{ format_currency($transaction->amount, $store) }}</div>
        @if((float) $transaction->fee > 0)
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                + {{ __('messages.transactions_fee') }}: {{ format_currency($transaction->fee, $store) }}
            </div>
        @endif
    </div>

    @if($transaction->notes)
        <div style="margin-bottom: 20px;">
            <span class="meta-label">{{ __('messages.notes') }}:</span>
            <p style="margin: 4px 0 0 0; color: #334155; font-size: 12px;">{{ $transaction->notes }}</p>
        </div>
    @endif

    <div class="signatures">
        <div class="sig-block">
            Prepared By ({{ $transaction->recorder?->name ?? 'Cashier' }})
        </div>
        <div class="sig-block">
            Authorized / Received By
        </div>
    </div>

    @if($footerGreeting)
        <div style="text-align: center; font-size: 11px; color: #64748b; margin-top: 24px;">
            {{ $footerGreeting }}
        </div>
    @endif
</div>

<button type="button" class="btn-print" onclick="window.print()">
    🖨️ Print Voucher
</button>

</body>
</html>
