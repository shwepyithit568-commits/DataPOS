@php
    $tmpl = $voucherTemplate ?? null;
    $allowedLayouts = ['58mm', '80mm', 'a5_portrait', 'a5_landscape', 'a4_portrait', 'a4_landscape'];
    $layout = in_array($layout ?? '80mm', $allowedLayouts, true) ? $layout : '80mm';
    $isThermal = in_array($layout, ['58mm', '80mm'], true);
    $is58 = $layout === '58mm';
    $is80 = $layout === '80mm';
    $isA5 = in_array($layout, ['a5_portrait', 'a5_landscape'], true);
    $isA4 = in_array($layout, ['a4_portrait', 'a4_landscape'], true);
    $isLandscape = in_array($layout, ['a5_landscape', 'a4_landscape'], true);

    $isX = ($type ?? 'z') === 'x';
    $title = $isX ? __('messages.x_report_reading') : __('messages.z_report_closing');
    if ($isX) {
        $marker = '*** X-REPORT — READING ONLY / စာရင်းကြည့်ရှုရန်သာ ***';
    } elseif ($closing && $closing->isApproved()) {
        $marker = '*** Z-REPORT — FINAL/APPROVED DAILY CLOSING / အတည်ပြုပြီး နေ့စဉ်စာရင်းချုပ် ***';
    } else {
        $marker = '*** PENDING Z-REPORT — SUBMITTED (UNAPPROVED) / ဆိုင်းငံ့ နေ့စဉ်စာရင်းချုပ် ***';
    }

    $showLogo = (bool) data_get($tmpl, 'show_logo', true);
    $templateLogo = ($tmpl instanceof \App\Models\VoucherTemplate) ? $tmpl->logoUrl() : null;
    $logoUrl = $showLogo ? ($templateLogo ?? ($store->setting?->adminLogo() ? asset('storage/' . $store->setting->adminLogo()) : null)) : null;
    $address = data_get($tmpl, 'address') ?: ($store->address ?? null);
    $phone = data_get($tmpl, 'phone') ?: ($store->viber_number ? 'Viber: ' . $store->viber_number : ($store->phone ?? null));
    $headerTitle = data_get($tmpl, 'header_title') ?: $store->name;
    $headerSubtitle = data_get($tmpl, 'header_subtitle');

    $methods = \App\POS\Models\DailyClosing::expectedMethods();
    $countedMethods = \App\POS\Models\DailyClosing::countedMethods();

    $summary = $totals['summary'] ?? [];
    $expectedMap = $totals['expected'] ?? [];
    $countedMap = $totals['counted'] ?? [];
    $diffMap = $totals['differences'] ?? [];
    $totalDiff = $totals['total_difference'] ?? '0.00';

    $myanmarFontUrl = null;
    try {
        $myanmarFontUrl = \Illuminate\Support\Facades\Vite::asset('resources/assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Regular.ttf');
    } catch (\Throwable $e) {
        $myanmarFontUrl = null;
    }

    $storeRouteParams = ['store_slug' => $store->slug];
    $printUrlParams = array_merge($storeRouteParams, [
        'type' => $type,
        'date' => $date->toDateString(),
    ]);
    if ($closing) {
        $printUrlParams['closing'] = $closing->id;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $isX ? 'X-Report' : 'Z-Report' }}_{{ $date->toDateString() }} — {{ $store->name }}</title>

    <script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>

    <style>
        @if ($myanmarFontUrl)
        @font-face {
            font-family: 'Noto Sans Myanmar';
            src: url('{{ $myanmarFontUrl }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @endif

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            letter-spacing: normal !important;
        }

        body {
            font-family: 'Noto Sans Myanmar', 'Pyidaungsu', 'Myanmar Text', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            line-height: 1.4;
            padding: 24px 16px;
            letter-spacing: normal !important;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Action Toolbar (Screen Only) ── */
        .toolbar-wrap {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }
        .tool-btn-back {
            background: #334155;
            color: #f1f5f9;
        }
        .tool-btn-back:hover { background: #475569; }
        .tool-btn-print {
            background: #0284c7;
            color: #fff;
        }
        .tool-btn-print:hover { background: #0369a1; }
        .tool-btn-pdf {
            background: #059669;
            color: #fff;
        }
        .tool-btn-pdf:hover { background: #047857; }
        .tool-btn-pdf:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        .layout-pills {
            display: inline-flex;
            background: #1e293b;
            padding: 3px;
            border-radius: 8px;
            gap: 2px;
        }
        .layout-pill {
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.15s ease;
        }
        .layout-pill:hover { color: #f8fafc; }
        .layout-pill.active {
            background: #0284c7;
            color: #ffffff;
            font-weight: 700;
        }

        .type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: normal;
            text-transform: uppercase;
        }
        .type-badge-x { background: #fef3c7; color: #92400e; }
        .type-badge-z { background: #dcfce7; color: #166534; }

        /* ── Report Document Wrapper ── */
        .report-page-container {
            margin: 54px auto 0 auto;
        }

        /* ── Thermal Containers (58mm / 80mm) ── */
        .report-card-thermal {
            background: #fff;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .report-card-58mm {
            width: 260px;
            padding: 12px 8px;
            font-size: 11px;
        }
        .report-card-80mm {
            width: 340px;
            padding: 18px 14px;
            font-size: 12px;
        }

        /* ── Full Sheet Containers (A5 / A4) ── */
        .report-card-fullsheet {
            background: #fff;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            border: 1px solid #e2e8f0;
        }
        .report-card-a5_portrait {
            width: 560px;
            padding: 24px;
            font-size: 12px;
        }
        .report-card-a5_landscape {
            width: 780px;
            padding: 24px 30px;
            font-size: 12px;
        }
        .report-card-a4_portrait {
            width: 794px;
            padding: 36px 40px;
            font-size: 12.5px;
        }
        .report-card-a4_landscape {
            width: 1080px;
            padding: 36px 44px;
            font-size: 12.5px;
        }

        /* Header Elements */
        .store-header { text-align: center; margin-bottom: 12px; }
        .store-logo { max-height: 48px; max-width: 130px; object-fit: contain; margin: 0 auto 6px auto; display: block; }
        .store-name { font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .store-sub { font-size: 11px; color: #64748b; margin-top: 2px; }
        .marker-badge {
            margin: 10px 0;
            padding: 5px 8px;
            text-align: center;
            font-weight: 800;
            font-size: 11px;
            border: 1px dashed #64748b;
            border-radius: 4px;
            background: #f8fafc;
            letter-spacing: normal;
        }

        /* Tables & Typography */
        .rule { border: none; border-top: 1px dashed #cbd5e1; margin: 10px 0; }
        .solid-rule { border: none; border-top: 1.5px solid #0f172a; margin: 10px 0; }

        .meta-table, .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: inherit;
        }
        .meta-table td { padding: 3px 0; vertical-align: top; }
        .meta-label { color: #64748b; font-weight: 600; width: 44%; letter-spacing: normal; }
        .meta-val { color: #0f172a; font-weight: 700; text-align: right; width: 56%; letter-spacing: normal; }

        .data-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 800;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: normal;
            padding: 6px 8px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }

        .diff-over { color: #d97706; font-weight: 700; }
        .diff-short { color: #dc2626; font-weight: 700; }
        .diff-exact { color: #64748b; font-weight: 600; }

        /* Signature block for A5/A4 */
        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-box {
            text-align: center;
            border-top: 1px dashed #94a3b8;
            padding-top: 8px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
        }

        /* ── PRINT MEDIA RULES ── */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                color: #000000 !important;
            }
            .no-print {
                display: none !important;
            }
            .report-page-container {
                margin: 0 !important;
                padding: 0 !important;
            }
            .report-card-thermal, .report-card-fullsheet {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
            }

            table, tr, td, th, .signature-grid {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            @if ($layout === '58mm')
                @page { size: 58mm auto; margin: 2mm; }
            @elseif ($layout === '80mm')
                @page { size: 80mm auto; margin: 3mm; }
            @elseif ($layout === 'a5_portrait')
                @page { size: A5 portrait; margin: 8mm; }
            @elseif ($layout === 'a5_landscape')
                @page { size: A5 landscape; margin: 8mm; }
            @elseif ($layout === 'a4_portrait')
                @page { size: A4 portrait; margin: 12mm; }
            @elseif ($layout === 'a4_landscape')
                @page { size: A4 landscape; margin: 12mm; }
            @endif
        }
    </style>
</head>
<body class="layout-{{ $layout }}">

    {{-- ── SECTION 1: Interactive Navigation & Print Layout Toolbar ── --}}
    <div class="toolbar-wrap no-print">
        <div class="toolbar-left">
            <a href="{{ route('pos.closing.index', array_merge($storeRouteParams, ['date' => $date->toDateString()])) }}" class="tool-btn tool-btn-back">
                ← <span>{{ __('messages.back') }}</span>
            </a>

            <span class="type-badge {{ $isX ? 'type-badge-x' : 'type-badge-z' }}">
                {{ $isX ? 'X-REPORT' : 'Z-REPORT' }}
            </span>

            {{-- Layout Selector Pills --}}
            <div class="layout-pills">
                <a href="{{ route('pos.closing.print', array_merge($printUrlParams, ['layout' => '58mm'])) }}"
                   class="layout-pill {{ $layout === '58mm' ? 'active' : '' }}" title="58mm Thermal">
                    58mm
                </a>
                <a href="{{ route('pos.closing.print', array_merge($printUrlParams, ['layout' => '80mm'])) }}"
                   class="layout-pill {{ $layout === '80mm' ? 'active' : '' }}" title="80mm Thermal">
                    80mm
                </a>
                <a href="{{ route('pos.closing.print', array_merge($printUrlParams, ['layout' => 'a5_portrait'])) }}"
                   class="layout-pill {{ $layout === 'a5_portrait' ? 'active' : '' }}" title="A5 Portrait">
                    A5 P
                </a>
                <a href="{{ route('pos.closing.print', array_merge($printUrlParams, ['layout' => 'a5_landscape'])) }}"
                   class="layout-pill {{ $layout === 'a5_landscape' ? 'active' : '' }}" title="A5 Landscape">
                    A5 L
                </a>
                <a href="{{ route('pos.closing.print', array_merge($printUrlParams, ['layout' => 'a4_portrait'])) }}"
                   class="layout-pill {{ $layout === 'a4_portrait' ? 'active' : '' }}" title="A4 Portrait">
                    A4 P
                </a>
                <a href="{{ route('pos.closing.print', array_merge($printUrlParams, ['layout' => 'a4_landscape'])) }}"
                   class="layout-pill {{ $layout === 'a4_landscape' ? 'active' : '' }}" title="A4 Landscape">
                    A4 L
                </a>
            </div>
        </div>

        <div class="toolbar-right">
            <button type="button" id="btnSavePdf" class="tool-btn tool-btn-pdf" data-save-pdf title="{{ __('messages.export_pdf') ?? 'Save PDF' }}">
                📥 <span id="btnSavePdfText">{{ __('messages.export_pdf') ?? 'Save PDF' }}</span>
            </button>
            <button type="button" id="btnPrint" class="tool-btn tool-btn-print" data-print title="{{ __('messages.print') ?? 'Print' }}">
                🖨️ <span>{{ __('messages.print') ?? 'Print' }}</span>
            </button>
        </div>
    </div>

    {{-- ── SECTION 2: Report Document Container ── --}}
    <div class="report-page-container">
        <div id="reportDocument" class="{{ $isThermal ? 'report-card-thermal report-card-' . $layout : 'report-card-fullsheet report-card-' . $layout }}">

            {{-- Header Branding --}}
            <div class="store-header">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $store->name }}" class="store-logo">
                @endif
                <div class="store-name">{{ $store->name }}</div>
                @if ($address)
                    <div class="store-sub">{{ $address }}</div>
                @endif
                @if ($phone)
                    <div class="store-sub">{{ $phone }}</div>
                @endif

                <div class="marker-badge">
                    {{ $marker }}
                </div>
            </div>

            <hr class="solid-rule">

            {{-- Document Metadata --}}
            <table class="meta-table">
                <tr>
                    <td class="meta-label">{{ __('messages.date') }}:</td>
                    <td class="meta-val font-mono">{{ $date->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">{{ __('messages.printed') ?? 'Printed' }}:</td>
                    <td class="meta-val font-mono">{{ now()->format('d/m/Y H:i:s') }}</td>
                </tr>
                @if ($closing)
                    <tr>
                        <td class="meta-label">{{ __('messages.status') }}:</td>
                        <td class="meta-val font-bold {{ $closing->isApproved() ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $closing->isApproved() ? __('messages.approved') : __('messages.pending') }}
                        </td>
                    </tr>
                    @if ($closing->closingUser)
                        <tr>
                            <td class="meta-label">{{ __('messages.closed_by') }}:</td>
                            <td class="meta-val">{{ $closing->closingUser->name }}</td>
                        </tr>
                    @endif
                    @if ($closing->approver)
                        <tr>
                            <td class="meta-label">{{ __('messages.closing_approver') }}:</td>
                            <td class="meta-val">{{ $closing->approver->name }} ({{ $closing->approved_at?->format('d/m H:i') }})</td>
                        </tr>
                    @endif
                @else
                    <tr>
                        <td class="meta-label">{{ __('messages.status') }}:</td>
                        <td class="meta-val font-bold text-sky-600">{{ $isX ? __('messages.reading_only') : __('messages.pending') }}</td>
                    </tr>
                @endif
            </table>

            <hr class="rule">

            {{-- Sales & Financial Summary --}}
            <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; margin-bottom: 6px; color: #475569;">
                {{ __('messages.sales_summary') ?? 'Sales & Drawer Summary' }}
            </div>

            <table class="meta-table">
                @if (!empty($summary['gross_sales']))
                    <tr>
                        <td class="meta-label">{{ __('messages.gross_sales') }}:</td>
                        <td class="meta-val font-mono">{{ format_currency((float) $summary['gross_sales'], $store) }}</td>
                    </tr>
                @endif
                @if (!empty($summary['discounts']) && (float)$summary['discounts'] > 0)
                    <tr>
                        <td class="meta-label">{{ __('messages.discounts') }}:</td>
                        <td class="meta-val font-mono">-{{ format_currency((float) $summary['discounts'], $store) }}</td>
                    </tr>
                @endif
                @if (!empty($summary['tax']) && (float)$summary['tax'] > 0)
                    <tr>
                        <td class="meta-label">{{ __('messages.tax_collected') }}:</td>
                        <td class="meta-val font-mono">{{ format_currency((float) $summary['tax'], $store) }}</td>
                    </tr>
                @endif
                @if (!empty($summary['returns']) && (float)$summary['returns'] > 0)
                    <tr>
                        <td class="meta-label">{{ __('messages.returns_refunds') }}:</td>
                        <td class="meta-val font-mono">-{{ format_currency((float) $summary['returns'], $store) }}</td>
                    </tr>
                @endif
                @if (!empty($summary['net_sales']))
                    <tr style="font-weight: 800; border-top: 1px dotted #e2e8f0;">
                        <td class="meta-label" style="font-weight: 800; color: #0f172a;">{{ __('messages.net_sales') }}:</td>
                        <td class="meta-val font-mono">{{ format_currency((float) $summary['net_sales'], $store) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="meta-label">{{ __('messages.opening_float') }}:</td>
                    <td class="meta-val font-mono">{{ format_currency((float) ($totals['opening_amount'] ?? 0), $store) }}</td>
                </tr>
                @if (!empty($summary['cash_in']) && (float)$summary['cash_in'] > 0)
                    <tr>
                        <td class="meta-label">{{ __('messages.cash_in') }}:</td>
                        <td class="meta-val font-mono">+{{ format_currency((float) $summary['cash_in'], $store) }}</td>
                    </tr>
                @endif
                @if (!empty($summary['cash_out']) && (float)$summary['cash_out'] > 0)
                    <tr>
                        <td class="meta-label">{{ __('messages.cash_out') }}:</td>
                        <td class="meta-val font-mono">-{{ format_currency((float) $summary['cash_out'], $store) }}</td>
                    </tr>
                @endif
                <tr style="border-top: 1px dashed #cbd5e1;">
                    <td class="meta-label font-bold" style="color: #0f172a;">{{ __('messages.expected_cash') }}:</td>
                    <td class="meta-val font-mono font-black">{{ format_currency((float) ($expectedMap['cash'] ?? 0), $store) }}</td>
                </tr>
                @if (!$isX && isset($countedMap['cash']))
                    <tr>
                        <td class="meta-label font-bold" style="color: #0f172a;">{{ __('messages.counted_cash') }}:</td>
                        <td class="meta-val font-mono font-bold">{{ format_currency((float) ($countedMap['cash'] ?? 0), $store) }}</td>
                    </tr>
                    @php
                        $cashDiff = $diffMap['cash'] ?? '0.00';
                        $cashDiffFloat = (float) $cashDiff;
                    @endphp
                    <tr>
                        <td class="meta-label font-bold" style="color: #0f172a;">{{ __('messages.cash_short_over') ?? 'Cash Variance' }}:</td>
                        <td class="meta-val font-mono font-black {{ $cashDiffFloat < 0 ? 'diff-short' : ($cashDiffFloat > 0 ? 'diff-over' : 'diff-exact') }}">
                            {{ $cashDiffFloat > 0 ? '+' : '' }}{{ format_currency($cashDiffFloat, $store) }}
                        </td>
                    </tr>
                @endif
            </table>
            @if (!empty($summary['is_legacy']))
                <div style="margin-top: 6px; padding: 4px 8px; background: #f1f5f9; border-left: 3px solid #94a3b8; font-size: 10px; color: #475569;">
                    * {{ __('messages.legacy_closing_notice') }}
                </div>
            @endif

            <hr class="solid-rule">

            {{-- Payment Methods Breakdown Table --}}
            <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; margin-bottom: 6px; color: #475569;">
                {{ __('messages.payment_methods') ?? 'Payment Method Reconciliation' }}
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">{{ __('messages.payment_method') }}</th>
                        <th class="text-right">{{ __('messages.expected') }}</th>
                        @if (!$isX)
                            <th class="text-right">{{ __('messages.counted') }}</th>
                            <th class="text-right">{{ __('messages.difference') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($methods as $m)
                        @php
                            $isCredit = $m === 'credit';
                            $exp = (float) ($expectedMap[$m] ?? 0);
                            $cnt = (float) ($countedMap[$m] ?? 0);
                            $df = (float) ($diffMap[$m] ?? 0);
                        @endphp
                        <tr>
                            <td class="font-bold">
                                {{ __('messages.payment_' . $m) }}
                                @if ($isCredit)
                                    <div style="font-size: 9.5px; font-weight: 500; color: #64748b;">{{ __('messages.closing_credit_info') }}</div>
                                @endif
                            </td>
                            <td class="text-right font-mono">{{ format_currency($exp, $store) }}</td>
                            @if (!$isX)
                                <td class="text-right font-mono">{{ $isCredit ? '—' : format_currency($cnt, $store) }}</td>
                                <td class="text-right font-mono font-bold {{ $df < 0 ? 'diff-short' : ($df > 0 ? 'diff-over' : 'diff-exact') }}">
                                    {{ $df > 0 ? '+' : '' }}{{ format_currency($df, $store) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    @if (!$isX)
                        @php $totDiffFloat = (float) $totalDiff; @endphp
                        <tr style="background: #f8fafc; font-weight: 900; border-top: 1.5px solid #0f172a;">
                            <td class="font-black">{{ __('messages.closing_total_difference') }}</td>
                            <td></td>
                            <td></td>
                            <td class="text-right font-mono font-black {{ $totDiffFloat < 0 ? 'diff-short' : ($totDiffFloat > 0 ? 'diff-over' : 'diff-exact') }}">
                                {{ $totDiffFloat > 0 ? '+' : '' }}{{ format_currency($totDiffFloat, $store) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- Variance Explanation --}}
            @if (!$isX && $closing && $closing->explanation)
                <div style="margin-top: 12px; padding: 8px 10px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; font-size: 11px;">
                    <span style="font-weight: 700; color: #92400e;">{{ __('messages.explanation') ?? 'Explanation' }}:</span>
                    <span style="font-style: italic; color: #78350f;">"{{ $closing->explanation }}"</span>
                </div>
            @endif

            {{-- Signature Areas for Full Sheet A5/A4 --}}
            @if (!$isThermal)
                <div class="signature-grid">
                    <div class="signature-box">
                        <div>{{ __('messages.cashier_signature') }}</div>
                        <div style="margin-top: 24px; font-size: 10px; color: #64748b;">
                            {{ $closing?->closingUser?->name ?? auth()->user()?->name ?? '—' }}
                        </div>
                    </div>
                    <div class="signature-box">
                        <div>{{ __('messages.manager_signature') }}</div>
                        <div style="margin-top: 24px; font-size: 10px; color: #64748b;">
                            {{ $closing?->approver?->name ?? '—' }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Footer Note --}}
            <div style="text-align: center; margin-top: 20px; font-size: 10px; color: #94a3b8;">
                {{ $store->name }} · {{ config('app.name', 'DataPOS') }} · {{ $isX ? 'X-Report Reading' : 'Z-Report Daily Closing' }}
            </div>

        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            var currentLayout = @js($layout);
            var isThermal = @js($isThermal);
            var filename = @js(($isX ? 'X-Report' : 'Z-Report') . '_' . $date->toDateString() . '_' . $store->slug . '_' . $layout . '.pdf');

            var pdfDimensions = {
                '58mm': { unit: 'mm', format: [58, 260], orientation: 'portrait' },
                '80mm': { unit: 'mm', format: [80, 300], orientation: 'portrait' },
                'a5_portrait': { unit: 'mm', format: 'a5', orientation: 'portrait' },
                'a5_landscape': { unit: 'mm', format: 'a5', orientation: 'landscape' },
                'a4_portrait': { unit: 'mm', format: 'a4', orientation: 'portrait' },
                'a4_landscape': { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };

            function doPrint() {
                window.print();
            }

            // Render DOM element via SVG foreignObject to preserve native browser HarfBuzz Myanmar font shaping
            function renderViaSvgForeignObject(element, scale) {
                return new Promise(function (resolve, reject) {
                    var width = element.offsetWidth || element.clientWidth || 340;
                    var height = element.offsetHeight || element.scrollHeight || 600;

                    var clone = element.cloneNode(true);
                    clone.style.boxShadow = 'none';
                    clone.style.margin = '0';

                    // Convert any images inside to Base64 to prevent canvas tainting
                    var images = clone.querySelectorAll('img');
                    var origImages = element.querySelectorAll('img');
                    for (var i = 0; i < images.length; i++) {
                        var orig = origImages[i];
                        if (orig && orig.complete && orig.naturalWidth > 0) {
                            try {
                                var ic = document.createElement('canvas');
                                ic.width = orig.naturalWidth;
                                ic.height = orig.naturalHeight;
                                var ictx = ic.getContext('2d');
                                ictx.drawImage(orig, 0, 0);
                                images[i].src = ic.toDataURL('image/png');
                            } catch (e) {
                                // Ignore if tainted
                            }
                        }
                    }

                    // Extract all page styles for typographic parity
                    var styles = '';
                    var styleElements = document.querySelectorAll('style');
                    for (var s = 0; s < styleElements.length; s++) {
                        styles += styleElements[s].textContent + '\n';
                    }

                    var serializedHtml = new XMLSerializer().serializeToString(clone);

                    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + (width * scale) + '" height="' + (height * scale) + '" viewBox="0 0 ' + width + ' ' + height + '">'
                        + '<foreignObject width="100%" height="100%">'
                        + '<div xmlns="http://www.w3.org/1999/xhtml">'
                        + '<style>'
                        + styles
                        + '* { letter-spacing: normal !important; }'
                        + 'body, html { margin: 0; padding: 0; background: #ffffff !important; font-family: "Noto Sans Myanmar", "Pyidaungsu", "Myanmar Text", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }'
                        + '</style>'
                        + serializedHtml
                        + '</div>'
                        + '</foreignObject>'
                        + '</svg>';

                    var blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
                    var url = URL.createObjectURL(blob);
                    var img = new Image();

                    img.onload = function () {
                        try {
                            var canvas = document.createElement('canvas');
                            canvas.width = width * scale;
                            canvas.height = height * scale;
                            var ctx = canvas.getContext('2d');
                            ctx.fillStyle = '#ffffff';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            ctx.drawImage(img, 0, 0);
                            URL.revokeObjectURL(url);
                            resolve(canvas);
                        } catch (err) {
                            URL.revokeObjectURL(url);
                            reject(err);
                        }
                    };

                    img.onerror = function (err) {
                        URL.revokeObjectURL(url);
                        reject(err);
                    };

                    img.src = url;
                });
            }

            async function downloadPdf() {
                var reportEl = document.getElementById('reportDocument');
                var btn = document.getElementById('btnSavePdf');
                var btnText = document.getElementById('btnSavePdfText');
                var originalText = btnText ? btnText.textContent : 'Save PDF';

                if (!reportEl) {
                    doPrint();
                    return;
                }

                if (!window.html2pdf) {
                    doPrint();
                    return;
                }

                if (btn) btn.disabled = true;
                if (btnText) btnText.textContent = '⏳ {{ __("messages.track_service_generating_pdf") ?? "Generating PDF..." }}';

                try {
                    if (document.fonts && document.fonts.ready) {
                        await document.fonts.ready;
                    }

                    var jsPdfSetting = pdfDimensions[currentLayout] || { unit: 'mm', format: 'a4', orientation: 'portrait' };
                    if (isThermal) {
                        var elHeightPx = reportEl.scrollHeight || reportEl.offsetHeight;
                        var heightMm = Math.max(140, Math.ceil((elHeightPx * 0.264583) + 12));
                        var widthMm = currentLayout === '58mm' ? 58 : 80;
                        jsPdfSetting = { unit: 'mm', format: [widthMm, heightMm], orientation: 'portrait' };
                    }

                    var opt = {
                        margin: isThermal ? [4, 2, 4, 2] : [8, 8, 8, 8],
                        filename: filename,
                        image: { type: 'jpeg', quality: 0.98 },
                        jsPDF: jsPdfSetting,
                        pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
                    };

                    // Render through SVG foreignObject to preserve Myanmar font shaping
                    var customCanvas = null;
                    try {
                        customCanvas = await renderViaSvgForeignObject(reportEl, 2);
                    } catch (svgErr) {
                        console.warn('SVG foreignObject render fallback:', svgErr);
                    }

                    var worker = html2pdf().set(opt);
                    if (customCanvas) {
                        worker.prop.canvas = customCanvas;
                        await worker.toPdf().save();
                    } else {
                        // Fallback to standard html2pdf
                        await worker.from(reportEl).save();
                    }
                } catch (err) {
                    console.error('PDF export failed:', err);
                    doPrint();
                } finally {
                    if (btn) btn.disabled = false;
                    if (btnText) btnText.textContent = originalText;
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                var printBtn = document.getElementById('btnPrint');
                if (printBtn) {
                    printBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        doPrint();
                    });
                }

                var pdfBtn = document.getElementById('btnSavePdf');
                if (pdfBtn) {
                    pdfBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        downloadPdf();
                    });
                }

                document.addEventListener('click', function (e) {
                    var printTarget = e.target.closest('[data-print]');
                    if (printTarget) {
                        e.preventDefault();
                        doPrint();
                        return;
                    }
                    var savePdfTarget = e.target.closest('[data-save-pdf]');
                    if (savePdfTarget) {
                        e.preventDefault();
                        downloadPdf();
                    }
                }, true);
            });
        })();
    </script>
</body>
</html>
