@php
    $paperSize = $paperSize ?? '80mm';
    $isThermal = in_array($paperSize, ['58mm', '80mm'], true);
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.buyback_slip_title') }} - {{ $buyback->buyback_number }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Outfit:wght@100..900&family=JetBrains+Mono:wght@400;500;600;700&family=Padauk:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        :root {
            --slip-width: {{ $paperSize === '58mm' ? '54mm' : ($paperSize === '80mm' ? '76mm' : ($paperSize === 'a5' ? '140mm' : '190mm')) }};
            --slip-font-size: {{ $paperSize === '58mm' ? '11px' : ($paperSize === '80mm' ? '12px' : '13px') }};
        }

        @page {
            size: {{ $paperSize === 'a4' ? 'A4 portrait' : ($paperSize === 'a5' ? 'A5 landscape' : ($paperSize === '58mm' ? '58mm auto' : '80mm auto')) }};
            margin: {{ $isThermal ? '2mm' : '8mm' }};
        }

        body {
            font-family: 'DM Sans', 'Padauk', sans-serif;
            background: #0f172a;
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Top Bar styling */
        .no-print-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
        }

        .stage {
            padding-top: 64px;
            padding-bottom: 40px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /* Paper slip layout */
        .paper-slip {
            width: var(--slip-width);
            background: #fff;
            color: #0f172a;
            font-size: var(--slip-font-size);
            line-height: 1.35;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
            border-radius: {{ $isThermal ? '2px' : '6px' }};
            padding: {{ $paperSize === '58mm' ? '12px 8px' : ($paperSize === '80mm' ? '18px 12px' : '24px 20px') }};
            margin: 0 auto;
            box-sizing: border-box;
        }

        .paper-pill {
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 11px;
            transition: all 0.15s ease;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.05);
        }
        .paper-pill:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
        .paper-pill.active {
            color: #fff;
            background: #0284c7;
            border-color: #38bdf8;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.35);
        }

        .dashed-sep {
            border-top: 1px dashed #cbd5e1;
            margin: 8px 0;
        }

        .double-sep {
            border-top: 2px solid #0f172a;
            margin: 8px 0;
        }

        /* Print media override */
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
            }
            .no-print-toolbar, .no-print-toast {
                display: none !important;
            }
            .stage {
                padding: 0 !important;
                min-height: auto !important;
            }
            .paper-slip {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    {{-- 1. Dark Glassmorphic Toolbar --}}
    <div class="no-print-toolbar">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('pos.buybacks.show', [...$storeRouteParams, 'buyback' => $buyback->id]) }}"
               class="h-7 px-2.5 rounded-md bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition inline-flex items-center gap-1">
                <span>←</span>
                <span>{{ __('messages.back') }}</span>
            </a>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30">
                {{ __('messages.buyback_slip_title') }}
            </span>
            <span class="font-mono text-xs font-bold text-slate-300 hidden sm:inline">{{ $buyback->buyback_number }}</span>
        </div>

        {{-- Center: Paper Sizing --}}
        <div class="flex items-center gap-1">
            <span class="text-[11px] text-slate-400 font-bold hidden md:inline">{{ __('messages.paper_size') ?? 'စာရွက်အရွယ်အစား' }}:</span>
            <div class="flex items-center gap-1 bg-white/5 p-0.5 rounded-lg border border-white/10">
                <button type="button" class="paper-pill {{ $paperSize === '80mm' ? 'active' : '' }}" data-paper="80mm">80mm</button>
                <button type="button" class="paper-pill {{ $paperSize === '58mm' ? 'active' : '' }}" data-paper="58mm">58mm</button>
                <button type="button" class="paper-pill {{ $paperSize === 'a5' ? 'active' : '' }}" data-paper="a5">A5</button>
                <button type="button" class="paper-pill {{ $paperSize === 'a4' ? 'active' : '' }}" data-paper="a4">A4</button>
            </div>
        </div>

        {{-- Right: Actions (Auto Print, Print, PDF, JPG, Close) --}}
        <div class="flex items-center gap-1.5">
            <label class="hidden lg:flex items-center gap-1.5 text-xs text-slate-300 font-semibold cursor-pointer select-none mr-1">
                <input type="checkbox" id="autoPrintCheck" class="rounded border-slate-700 text-sky-500 focus:ring-0">
                <span>{{ __('messages.auto_print') ?? 'အလိုအလျောက် ပရင့်ထုတ်မည်' }}</span>
            </label>

            <button type="button" id="btnPrint"
                    class="h-7 px-3 rounded-md bg-sky-500 hover:bg-sky-400 text-white text-xs font-black transition inline-flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95">
                <span>🖨️</span>
                <span>{{ __('messages.print') }}</span>
            </button>

            <button type="button" id="btnDownloadPdf"
                    class="h-7 px-2.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition inline-flex items-center gap-1 cursor-pointer active:scale-95">
                <span>📄</span>
                <span class="hidden sm:inline">PDF</span>
            </button>

            <button type="button" id="btnShareJpg"
                    class="h-7 px-2.5 rounded-md bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition inline-flex items-center gap-1 cursor-pointer active:scale-95"
                    title="Copy JPG Image to Clipboard">
                <span>🖼️</span>
                <span class="hidden sm:inline">JPG</span>
            </button>

            <button type="button" id="btnClose"
                    class="w-7 h-7 rounded-md grid place-items-center text-slate-400 hover:text-white hover:bg-white/10 transition text-xs font-black cursor-pointer">
                ✕
            </button>
        </div>
    </div>

    {{-- 2. Printable Paper Slip Stage --}}
    <div class="stage">
        <div class="paper-slip" id="printableVoucher">
            {{-- Header: Store Branding --}}
            <div class="text-center pb-2">
                @if(!empty($store->logo_url))
                    <img src="{{ $store->logo_url }}" alt="{{ $store->name }}" class="h-10 mx-auto mb-1.5 object-contain">
                @endif
                <h1 class="font-black text-sm uppercase tracking-wide text-slate-900">{{ $store->name }}</h1>
                @if(!empty($store->address))
                    <p class="text-[11px] text-slate-600 mt-0.5">{{ $store->address }}</p>
                @endif
                @if(!empty($store->phone))
                    <p class="text-[11px] text-slate-600 font-mono">{{ $store->phone }}</p>
                @endif

                <div class="mt-2 inline-block px-2.5 py-0.5 rounded border border-slate-900 text-[10px] font-black uppercase tracking-wider">
                    {{ __('messages.buyback_slip_title') }}
                </div>
            </div>

            <div class="dashed-sep"></div>

            {{-- Metadata Info --}}
            <div class="space-y-0.5 text-[11px]">
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('messages.buyback_number') }}:</span>
                    <span class="font-mono font-black text-slate-900">{{ $buyback->buyback_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('messages.date') }}:</span>
                    <span class="font-mono text-slate-800">{{ $buyback->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('messages.customer') }}:</span>
                    <span class="font-bold text-slate-900">{{ $buyback->customer?->name ?? __('messages.walk_in_customer') }}</span>
                </div>
                @if(!empty($buyback->customer?->phone))
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ __('messages.phone') ?? 'ဖုန်း' }}:</span>
                        <span class="font-mono text-slate-800">{{ $buyback->customer->phone }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-slate-500">{{ __('messages.cashier') }}:</span>
                    <span class="text-slate-800">{{ $buyback->creator?->name ?? '—' }}</span>
                </div>
                @if($buyback->reason)
                    <div class="flex justify-between text-[10px] pt-0.5">
                        <span class="text-slate-500">{{ __('messages.reason') }}:</span>
                        <span class="text-slate-800 italic font-semibold text-right max-w-[60%]">{{ $buyback->reason }}</span>
                    </div>
                @endif
            </div>

            <div class="dashed-sep"></div>

            {{-- Items Table --}}
            <table class="w-full text-left text-[11px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-300 font-bold text-slate-600">
                        <th class="py-1">{{ __('messages.products') }}</th>
                        <th class="py-1 text-center w-12">{{ __('messages.qty') }}</th>
                        <th class="py-1 text-right w-16">{{ __('messages.price') }}</th>
                        <th class="py-1 text-right w-20">{{ __('messages.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @foreach($buyback->items as $item)
                        @php
                            $lineTotal = (float) $item->unit_price * (float) $item->quantity;
                        @endphp
                        <tr>
                            <td class="py-1.5 align-top">
                                <div class="font-bold text-slate-900 leading-tight">{{ $item->product?->name ?? '—' }}</div>
                                @if(!empty($item->product?->sku))
                                    <div class="font-mono text-[9px] text-slate-400">{{ $item->product->sku }}</div>
                                @endif
                            </td>
                            <td class="py-1.5 text-center align-top font-mono font-bold text-slate-800">
                                {{ format_quantity($item->quantity, $store) }}
                            </td>
                            <td class="py-1.5 text-right align-top font-mono text-slate-600">
                                {{ format_currency($item->unit_price, $store) }}
                            </td>
                            <td class="py-1.5 text-right align-top font-mono font-black text-slate-900">
                                {{ format_currency($lineTotal, $store) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="double-sep"></div>

            {{-- Totals Section --}}
            <div class="space-y-1 text-right text-[11px]">
                <div class="flex justify-between items-center text-xs sm:text-sm font-black text-slate-900">
                    <span class="uppercase tracking-wide">{{ __('messages.total_value') }}:</span>
                    <span class="font-mono font-black text-rose-600">
                        {{ format_currency($buyback->total_value, $store) }}
                    </span>
                </div>
                <div class="flex justify-between items-center text-[11px] text-slate-600">
                    <span>{{ __('messages.refund_amount') ?? 'ပြန်လည်ပေးအပ်ငွေ' }}:</span>
                    <span class="font-mono font-bold">
                        {{ format_currency($buyback->refund_amount, $store) }}
                    </span>
                </div>
            </div>

            @if($buyback->notes)
                <div class="mt-2 p-1.5 rounded bg-slate-50 border border-slate-200 text-[10px] text-slate-700">
                    <span class="font-bold">{{ __('messages.notes') }}:</span>
                    <span class="italic ml-0.5">{{ $buyback->notes }}</span>
                </div>
            @endif

            {{-- Signatures Section --}}
            <div class="mt-6 pt-3 grid grid-cols-2 gap-4 text-center text-[10px] text-slate-600">
                <div class="border-t border-slate-300 pt-1">
                    <p class="font-bold text-slate-800">{{ __('messages.buyback_customer_sign') }}</p>
                    <p class="text-[9px] text-slate-400 mt-0.5">(Sign & Date)</p>
                </div>
                <div class="border-t border-slate-300 pt-1">
                    <p class="font-bold text-slate-800">{{ __('messages.buyback_staff_sign') }}</p>
                    <p class="text-[9px] text-slate-400 mt-0.5">(Authorized Rep)</p>
                </div>
            </div>

            {{-- Footer info --}}
            <div class="mt-4 text-center text-[9px] text-slate-400 space-y-0.5">
                <p>Printed on {{ now()->format('d M Y, H:i') }} · {{ $store->name }}</p>
                <p>Powered by DataPOS</p>
            </div>
        </div>
    </div>

    {{-- Toast Notification for Copy/Download --}}
    <div id="toastMessage" class="no-print-toast fixed bottom-4 right-4 z-50 transform translate-y-12 opacity-0 transition-all duration-300 bg-slate-900/95 text-white px-3.5 py-2 rounded-lg border border-slate-700 shadow-2xl text-xs font-semibold flex items-center gap-2">
        <span class="text-emerald-400 font-bold">✓</span>
        <span id="toastText"></span>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script nonce="{{ $cspNonce }}">
        const currentPaper = '{{ $paperSize }}';
        const buybackNumber = '{{ $buyback->buyback_number }}';

        function showToast(msg) {
            const toast = document.getElementById('toastMessage');
            const text = document.getElementById('toastText');
            if (!toast || !text) return;
            text.innerText = msg;
            toast.classList.remove('translate-y-12', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-12', 'opacity-0');
            }, 3000);
        }

        // Paper switching handler
        document.querySelectorAll('.paper-pill').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetPaper = this.getAttribute('data-paper');
                if (targetPaper && targetPaper !== currentPaper) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('paper_size', targetPaper);
                    window.location.href = url.toString();
                }
            });
        });

        // Print handler
        document.getElementById('btnPrint')?.addEventListener('click', () => {
            window.print();
        });

        // Close handler
        document.getElementById('btnClose')?.addEventListener('click', () => {
            window.close();
        });

        // Auto print handler
        const autoPrintCheck = document.getElementById('autoPrintCheck');
        if (autoPrintCheck) {
            const savedAuto = localStorage.getItem('buyback_auto_print');
            if (savedAuto === '1') {
                autoPrintCheck.checked = true;
                window.onload = () => { setTimeout(() => window.print(), 350); };
            }
            autoPrintCheck.addEventListener('change', function() {
                localStorage.setItem('buyback_auto_print', this.checked ? '1' : '0');
            });
        }

        // PDF download handler
        document.getElementById('btnDownloadPdf')?.addEventListener('click', () => {
            const element = document.getElementById('printableVoucher');
            const opt = {
                margin: [4, 4, 4, 4],
                filename: `BuyBack_${buybackNumber}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: currentPaper === 'a4' ? 'a4' : (currentPaper === 'a5' ? 'a5' : [80, 200]), orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save().then(() => {
                showToast('PDF downloaded successfully!');
            });
        });

        // JPG Share / Clipboard copy handler
        document.getElementById('btnShareJpg')?.addEventListener('click', async () => {
            const element = document.getElementById('printableVoucher');
            const opt = {
                margin: 2,
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: { scale: 2, useCORS: true },
            };
            try {
                const canvas = await html2pdf().set(opt).from(element).outputCanvas();
                canvas.toBlob(async (blob) => {
                    if (navigator.clipboard && navigator.clipboard.write) {
                        try {
                            await navigator.clipboard.write([
                                new ClipboardItem({ 'image/png': blob })
                            ]);
                            showToast('ပြေစာပုံရိပ်ကို Clipboard သို့ ကူးယူပြီးပါပြီ။ Viber/Telegram တွင် Paste (Ctrl+V) လုပ်နိုင်ပါသည်။');
                            return;
                        } catch (e) {
                            console.warn('Clipboard write failed, downloading image instead', e);
                        }
                    }
                    // Fallback download
                    const link = document.createElement('a');
                    link.download = `BuyBack_${buybackNumber}.jpg`;
                    link.href = canvas.toDataURL('image/jpeg', 0.95);
                    link.click();
                    showToast('JPG Image saved successfully!');
                }, 'image/png');
            } catch (err) {
                console.error(err);
                showToast('Failed to generate JPG image');
            }
        });
    </script>
</body>
</html>
