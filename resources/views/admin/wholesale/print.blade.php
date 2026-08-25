@php
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
    <title>Wholesale Application #{{ $application->id }} — {{ $store->name }}</title>
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

        html, body {
            font-family: 'Noto Sans Myanmar', 'Pyidaungsu', 'Myanmar Text', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
        }

        @page { size: A4 portrait; margin: 12mm 15mm; }

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

        body { background: #eef2f7; padding: 24px 12px; }
        .toolbar { max-width: 186mm; margin: 0 auto 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .toolbar .back { color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; }
        .toolbar .back:hover { color: #4f46e5; }
        .toolbar button {
            padding: 9px 20px; border: none; border-radius: 10px; background: #4f46e5;
            color: #fff; font-weight: 800; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;
        }
        .toolbar button:hover { background: #4338ca; }

        .sheet {
            max-width: 186mm; margin: 0 auto; background: #fff;
            border-radius: 12px; padding: 30px 36px; box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        }

        .header {
            display: flex; justify-content: space-between; align-items: flex-start;
            padding-bottom: 16px; border-bottom: 2px solid #4f46e5;
        }
        .brand .store-name { font-size: 18px; font-weight: 900; color: #111827; }
        .brand .store-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 20px; font-weight: 900; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-title .app-no { font-size: 13px; font-weight: 800; color: #334155; margin-top: 2px; }
        .doc-title .date { font-size: 11px; color: #64748b; margin-top: 2px; }

        .status-banner {
            margin: 20px 0; padding: 12px 18px; border-radius: 8px; font-weight: 800; font-size: 13px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .status-approved { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .status-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .status-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .status-suspended { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .section-title {
            font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
            color: #64748b; margin: 18px 0 8px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 4px;
        }

        .grid-2 {
            display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
        }
        .field-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;
        }
        .field-label { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 3px; }
        .field-val { font-size: 13px; font-weight: 700; color: #1e293b; word-break: break-word; }

        .notes-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; font-size: 12px; line-height: 1.6; color: #334155; margin-top: 10px;
        }

        .signatures {
            margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-end; padding-top: 20px;
        }
        .sign-box { text-align: center; width: 200px; }
        .sign-line { border-top: 1px solid #94a3b8; margin-top: 40px; padding-top: 6px; font-size: 11px; font-weight: 700; color: #475569; }

        .footer {
            margin-top: 30px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="toolbar no-print">
        <a href="{{ route('store.admin.wholesale.applications.show', $storeRouteParams + ['application' => $application->id]) }}" class="back">
            ← ပြန်သွားရန် (Back to Application)
        </a>
        <button onclick="window.print()">
            <span>🖨</span>
            <span>Print Application Slip (စာရွက်ထုတ်မည်)</span>
        </button>
    </div>

    <div class="sheet">
        {{-- Header --}}
        <div class="header">
            <div class="brand">
                <div class="store-name">{{ $store->name }}</div>
                <div class="store-sub">Wholesale Membership Application & Verification Slip</div>
                @if ($setting && $setting->phone)
                    <div style="font-size: 10px; color: #64748b; margin-top: 4px;">Phone: {{ $setting->phone }}</div>
                @endif
                @if ($setting && $setting->address)
                    <div style="font-size: 10px; color: #64748b;">Address: {{ $setting->address }}</div>
                @endif
            </div>
            <div class="doc-title">
                <h1>Wholesale Application</h1>
                <div class="app-no">APP-{{ str_pad($application->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="date">Date: {{ $application->created_at->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        {{-- Status Banner --}}
        <div class="status-banner status-{{ $application->status }}">
            <div>
                Application Status: <strong>{{ strtoupper($application->status) }}</strong>
            </div>
            <div>
                @if ($application->status === 'approved')
                    ✓ Authorized Wholesale Tier Access
                @elseif ($application->status === 'pending')
                    ⏳ Awaiting Verification
                @elseif ($application->status === 'rejected')
                    ✕ Application Declined
                @else
                    ⊘ Account Suspended
                @endif
            </div>
        </div>

        {{-- Business & Applicant Information --}}
        <div class="section-title">လုပ်ငန်းနှင့် လျှောက်ထားသူ အချက်အလက် (Business & Applicant Information)</div>
        <div class="grid-2">
            <div class="field-box">
                <div class="field-label">Business / Shop Name (လုပ်ငန်းအမည်)</div>
                <div class="field-val">{{ $application->business_name }}</div>
            </div>
            <div class="field-box">
                <div class="field-label">Applicant Name (လျှောက်ထားသူ)</div>
                <div class="field-val">{{ $application->user?->name ?? 'Guest Applicant' }}</div>
            </div>
            <div class="field-box">
                <div class="field-label">Contact Phone (ဆက်သွယ်ရန်ဖုန်း)</div>
                <div class="field-val">{{ $application->phone }}</div>
            </div>
            <div class="field-box">
                <div class="field-label">Member User ID</div>
                <div class="field-val">#{{ $application->user_id ?? 'N/A' }}</div>
            </div>
        </div>

        @if ($application->address)
            <div class="field-box" style="margin-top: 12px;">
                <div class="field-label">Business Address (လုပ်ငန်းလိပ်စာ)</div>
                <div class="field-val">{{ $application->address }}</div>
            </div>
        @endif

        {{-- Applicant Note --}}
        @if ($application->notes)
            <div class="section-title">Applicant Note (ဖောက်သည်၏ မှတ်ချက်)</div>
            <div class="notes-box">
                {{ $application->notes }}
            </div>
        @endif

        {{-- Admin Internal Note / Verification --}}
        @if ($application->admin_note)
            <div class="section-title">Store Verification & Decision Note (ဆိုင်တွင်းစစ်ဆေးချက်)</div>
            <div class="notes-box" style="background: #fdf4ff; border-color: #f0abfc; color: #86198f;">
                {{ $application->admin_note }}
            </div>
        @endif

        {{-- Signatures --}}
        <div class="signatures">
            <div class="sign-box">
                <div class="sign-line">Applicant Signature<br>(လျှောက်ထားသူ လက်မှတ်)</div>
            </div>
            <div class="sign-box">
                <div class="sign-line">Authorized Manager<br>(စိစစ်ခွင့်ပြုသူ လက်မှတ်)</div>
            </div>
        </div>

        <div class="footer">
            Generated on {{ now()->format('d M Y, h:i A') }} · {{ $store->name }} DataPOS Wholesale Management System
        </div>
    </div>

</body>
</html>
