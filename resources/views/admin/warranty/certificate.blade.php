<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.warranty_certificate_title') }} - {{ $warranty->serial_number }}</title>
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
            padding: 30px;
        }

        .cert-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 8px double #4338ca;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .cert-header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }

        .store-name {
            font-size: 26px;
            font-weight: 900;
            color: #1e1b4b;
            letter-spacing: -0.5px;
        }

        .store-info {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        .cert-title {
            margin-top: 16px;
            font-size: 20px;
            font-weight: 800;
            color: #4338ca;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cert-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .grid-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 24px 0;
        }

        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }

        .info-box-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #4338ca;
            margin-bottom: 12px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .row:last-child {
            margin-bottom: 0;
        }

        .label {
            color: #64748b;
        }

        .value {
            font-weight: 700;
            color: #0f172a;
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .validity-banner {
            background-color: #e0e7ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .validity-title {
            font-size: 13px;
            font-weight: 800;
            color: #312e81;
        }

        .validity-dates {
            font-size: 13px;
            font-weight: 900;
            color: #1e1b4b;
            font-family: ui-monospace, monospace;
        }

        .terms-box {
            font-size: 11px;
            color: #475569;
            background-color: #fafafa;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 12px;
            margin-top: 20px;
            line-height: 1.6;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
        }

        .sign-box {
            width: 220px;
            text-align: center;
        }

        .sign-line {
            border-top: 1px dashed #94a3b8;
            margin-bottom: 6px;
        }

        .no-print-bar {
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
            .cert-card {
                box-shadow: none;
                max-width: 100%;
                border-width: 4px;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <button type="button" class="btn" onclick="window.close()">{{ __('messages.close') }}</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ {{ __('messages.print') }}</button>
    </div>

    <div class="cert-card">
        {{-- Header --}}
        <div class="cert-header">
            <div class="store-name">{{ $store->name }}</div>
            @if($store->setting?->phone || $store->setting?->address)
                <div class="store-info">
                    @if($store->setting?->phone) 📞 {{ $store->setting->phone }} @endif
                    @if($store->setting?->address) | 📍 {{ $store->setting->address }} @endif
                </div>
            @endif
            <div class="cert-title">{{ __('messages.warranty_certificate_title') }}</div>
            <div class="cert-sub">Official Warranty & Guarantee Card (တရားဝင် အာမခံလက်မှတ်)</div>
        </div>

        {{-- Validity Period Highlight --}}
        <div class="validity-banner">
            <div>
                <div class="validity-title">{{ __('messages.warranty_coverage') }}: {{ $warranty->warranty_duration_months }} Months ({{ __('messages.warranty_type_' . $warranty->warranty_type) }})</div>
            </div>
            <div class="validity-dates">
                {{ $warranty->purchase_date->format('d/m/Y') }} &rarr; {{ $warranty->warranty_expiry_date->format('d/m/Y') }}
            </div>
        </div>

        {{-- Info Grid --}}
        <div class="grid-section">
            {{-- Device Details --}}
            <div class="info-box">
                <div class="info-box-title">{{ __('messages.device_and_serial_info') }}</div>
                <div class="row">
                    <span class="label">{{ __('messages.product_name') }}:</span>
                    <span class="value">{{ $warranty->product_name }}</span>
                </div>
                <div class="row">
                    <span class="label">Serial Number:</span>
                    <span class="value font-mono">{{ $warranty->serial_number }}</span>
                </div>
                @if($warranty->imei_primary)
                    <div class="row">
                        <span class="label">Primary IMEI:</span>
                        <span class="value font-mono">{{ $warranty->imei_primary }}</span>
                    </div>
                @endif
                @if($warranty->imei_secondary)
                    <div class="row">
                        <span class="label">Secondary IMEI:</span>
                        <span class="value font-mono">{{ $warranty->imei_secondary }}</span>
                    </div>
                @endif
                @if($warranty->invoice_number)
                    <div class="row">
                        <span class="label">Invoice No:</span>
                        <span class="value font-mono">#{{ $warranty->invoice_number }}</span>
                    </div>
                @endif
            </div>

            {{-- Customer & Policy --}}
            <div class="info-box">
                <div class="info-box-title">{{ __('messages.customer_information') }}</div>
                <div class="row">
                    <span class="label">{{ __('messages.customer_name') }}:</span>
                    <span class="value">{{ $warranty->customer_name ?: 'Walk-in Customer' }}</span>
                </div>
                <div class="row">
                    <span class="label">{{ __('messages.phone') }}:</span>
                    <span class="value font-mono">{{ $warranty->customer_phone ?: '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">{{ __('messages.purchase_date') }}:</span>
                    <span class="value font-mono">{{ $warranty->purchase_date->format('d M Y') }}</span>
                </div>
                <div class="row">
                    <span class="label">{{ __('messages.warranty_expiry') }}:</span>
                    <span class="value font-mono" style="color: #4338ca;">{{ $warranty->warranty_expiry_date->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Terms & Conditions --}}
        <div class="terms-box">
            <strong>{{ __('messages.warranty_terms_conditions') }}:</strong>
            <p style="margin-top: 4px;">
                {{ $warranty->terms_conditions ?: '၁။ ရေဝင်ခြင်း၊ ပြုတ်ကျခြင်း၊ မျက်နှာပြင်ကွဲအက်ခြင်းနှင့် တရားမဝင် ဆော့ဝဲလ်သွင်းထားခြင်းများအတွက် အာမခံ အကျုံးမဝင်ပါ။ ၂။ အာမခံရယူရန် ဤလက်မှတ်နှင့် စက်၏ Serial/IMEI တူညီရမည် ဖြစ်ပါသည်။' }}
            </p>
        </div>

        {{-- Signatures --}}
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div style="font-size: 11px; color: #64748b;">Customer Signature (ဝယ်ယူသူလက်မှတ်)</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div style="font-size: 11px; color: #64748b;">Authorized Store Stamp / Signature</div>
            </div>
        </div>
    </div>

</body>
</html>
