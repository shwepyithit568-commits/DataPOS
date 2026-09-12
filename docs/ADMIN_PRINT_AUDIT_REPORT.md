# DataPOS — Admin & Counter Printing Systems Audit Report
**ဘောက်ချာ၊ ပြေစာနှင့် ပုံနှိပ်ထုတ်ဝေမှု စနစ်များ စစ်ဆေးချက် အစီရင်ခံစာ**

---

## ၁။ အကျဉ်းချုပ် (Executive Summary)

DataPOS စနစ်တစ်ခုလုံး (Admin Panel နှင့် POS Counter) တွင် ပါဝင်သော **Print (ပုံနှိပ်ထုတ်ဝေမှု)** အပိုင်းအားလုံးကို စစ်ဆေးရှာဖွေခဲ့ရာ စုစုပေါင်း ပုံနှိပ်ထုတ်ဝေသည့်နေရာ **၁၅ ခု** ရှိကြောင်း စိစစ်တွေ့ရှိရပါသည်။

အဆိုပါ ပုံနှိပ်ထုတ်ဝေမှု အပိုင်းများကို `/admin/vouchers` (Voucher Customizer & Template Studio) နှင့် ချိတ်ဆက်ထားမှု အခြေအနေအရ အဓိကအားဖြင့် **(၂) မျိုး** ခွဲခြားနိုင်ပါသည်-
1. **`/admin/vouchers` နှင့် တိုက်ရိုက်ချိတ်ဆက်ထားသော အပိုင်းများ (Template-Driven Prints — ၈ ခု):** အရောင်းပြေစာ၊ ငွေစာရင်းဘောက်ချာ၊ အော်ဒါအင်ဗွိုက်စ်၊ စက်ပြင်လက်မှတ်၊ အာမခံလက်မှတ်၊ လက်ကားလျှောက်လွှာ စသည်တို့ ဖြစ်ပြီး စတိုးဆိုင်၏ Logo, Address, Phone, QR Code, Footer Greeting နှင့် Paper Size သတ်မှတ်ချက်များကို `/admin/vouchers` မှ အလိုအလျောက် ရယူအသုံးပြုပါသည်။
2. **သီးခြားလုပ်ဆောင်သော ပုံနှိပ်အပိုင်းများ (Standalone / Operational Prints — ၇ ခု):** ကုန်ပစ္စည်းကပ် ဘားကုဒ်စတစ်ကာ (Barcode Labels)၊ ပရင်တာစမ်းသပ်စာရွက် (Printer Test Print)၊ သိုလှောင်ရုံ စာရင်းစစ်စာရွက် (Stock Count Sheet) နှင့် ဘဏ္ဍာရေးအစီရင်ခံစာများ (P&L / Inventory Valuation) ဖြစ်ပြီး ၎င်းတို့၏ သီးသန့် Layout/Dimension များဖြင့် လည်ပတ်ပါသည်။

---

## ၂။ ပုံနှိပ်ထုတ်ဝေမှု နေရာများအားလုံးနှင့် `/admin/vouchers` ချိတ်ဆက်မှု အခြေအနေ

| စဉ် | ကဏ္ဍ / စာရွက်အမျိုးအစား | URL / Route Name | Blade View ဖိုင်တည်နေရာ | စက္ကူအရွယ်အစား (Paper Size) | `/admin/vouchers` နှင့် ချိတ်ဆက်မှု | Print Preview ဒီဇိုင်းတူညီမှု အုပ်စု |
|:---|:---|:---|:---|:---|:---:|:---:|
| **၁** | **POS အရောင်းပြေစာ (Sales Receipt)** | `/store/{slug}/pos/sales/{id}/receipt`<br>`pos.receipt` | `resources/views/pos/receipt.blade.php` | 58mm, 80mm, A5, A4 | **ချိတ်ဆက်ထားသည် (Dynamic)** | **Group A** (Interactive Studio Toolbar) |
| **၂** | **နေ့စဉ်စာရင်းချုပ် / Shift ပိတ်ပြေစာ (Daily Closing / Z-Report)** | `/store/{slug}/pos/closing/print`<br>`pos.closing.print` | `resources/views/pos/closing_print.blade.php` | 58mm, 80mm, A5, A4 | **ချိတ်ဆက်ထားသည် (Dynamic)** | **Group A** (Interactive Studio Toolbar) |
| **၃** | **စက်ပြင်လက်ခံ/အပ်နှံလွှာ (Repair Ticket / Handover)** | `/store/{slug}/admin/repairs/{id}/print`<br>`store.admin.repairs.print` | `resources/views/admin/repairs/print.blade.php` | 58mm, 80mm, A5, A4 | **ချိတ်ဆက်ထားသည် (Dynamic)** | **Group A** (Interactive Studio Toolbar) |
| **၄** | **ငွေဝင်/ငွေထွက် ဘောက်ချာ (Cash/Bank Transaction Voucher)** | `/store/{slug}/admin/transactions/{id}/voucher`<br>`store.admin.transactions.voucher` | `resources/views/admin/transactions/voucher.blade.php` | A5 Portrait | **ချိတ်ဆက်ထားသည် (A5 Default)** | **Group B** (Corporate Sheet Document) |
| **၅** | **ကုမ္ပဏီသုံး အခွန်ပြေစာ / အော်ဒါ အင်ဗွိုက်စ် (Commercial Tax Invoice)** | `/store/{slug}/admin/orders/{id}/invoice`<br>`store.admin.orders.invoice` | `resources/views/admin/orders/invoice.blade.php` | 58mm, 80mm, A5, A4 | **ချိတ်ဆက်ထားသည် (Dynamic)** | **Group A** (Interactive Studio Toolbar) |
| **၆** | **အာမခံလက်မှတ် (Warranty Certificate)** | `/store/{slug}/admin/warranty/certificate`<br>`(Direct View)` | `resources/views/admin/warranty/certificate.blade.php` | A4 Portrait | **ချိတ်ဆက်ထားသည် (A4 Default)** | **Group B** (Corporate Sheet Document) |
| **၇** | **လက်ကားဖောက်သည် လျှောက်လွှာ (Wholesale Application)** | `/store/{slug}/admin/wholesale/applications/{id}/print`<br>`store.admin.wholesale.applications.print` | `resources/views/admin/wholesale/print.blade.php` | A4 Portrait | **ချိတ်ဆက်ထားသည် (A4 Default)** | **Group B** (Corporate Sheet Document) |
| **၈** | **ဖုန်းဘေလ်ဖြည့် စလစ်ပြေစာ (E-load Slip)** | `/store/{slug}/admin/eload/transactions/{id}/slip`<br>`store.admin.eload.slip` | `resources/views/admin/eload/_slip.blade.php` | 80mm Thermal | **ချိတ်ဆက်ထားသည် (80mm Default)** | **Group C** (Thermal Auto-Slip) |
| **၉** | **ဘားကုဒ်နှင့် QR စတစ်ကာ (Barcode Labels)** | `/store/{slug}/admin/barcode/print`<br>`store.admin.barcode.print` | `resources/views/admin/barcode/print.blade.php` | Custom Sticker (40x30, 50x30, etc.) | **ချိတ်ဆက်မထားပါ (သီးသန့် Label Studio)** | **Group D** (Barcode Sticker Grid) |
| **၁၀** | **ပရင်တာ စမ်းသပ်စာရွက် (Printer Test Print)** | `/store/{slug}/admin/printers/{id}/test-print`<br>`store.admin.printers.test_print` | `resources/views/admin/printers/test_print.blade.php` | 58mm / 80mm | **ချိတ်ဆက်မထားပါ (Hardware Specs)** | **Group C** (Thermal Hardware Test) |
| **၁၁** | **သိုလှောင်ရုံ စတော့စစ်စာရွက် (Stock Count Audit Sheet)** | `/store/{slug}/admin/stock-count/{id}/print`<br>`store.admin.stock_count.print` | `resources/views/admin/stock_count/print.blade.php` | A4 Portrait | **ချိတ်ဆက်မထားပါ (Internal Form)** | **Group E** (Tabular Worksheet) |
| **၁၂** | **ပစ္စည်းလက်ကျန်ကတ်ပြား (Stock Ledger Bin Card)** | `/store/{slug}/admin/stock-ledger/print-bin-card/{id}`<br>`store.admin.stock_ledger.print_bin_card` | `resources/views/admin/stock_ledger/print_bin_card.blade.php` | A4 Portrait | **ချိတ်ဆက်မထားပါ (Warehouse Card)** | **Group E** (Tabular Worksheet) |
| **၁၃** | **ဖောက်သည် အကြွေးစာရင်းရှင်းတမ်း (Receivables Statement)** | `/store/{slug}/admin/receivables/statement`<br>`(Direct View)` | `resources/views/admin/receivables/statement.blade.php` | A4 / 80mm | **ချိတ်ဆက်မထားပါ (Store direct)** | **Group E** (Tabular Worksheet) |
| **၁၄** | **အရှုံးအမြတ် စာရင်းရှင်းတမ်း (Profit & Loss Statement)** | `/store/{slug}/admin/profit-loss/statement`<br>`(Direct View)` | `resources/views/admin/profit_loss/statement.blade.php` | A4 Portrait | **ချိတ်ဆက်မထားပါ (Financial Report)** | **Group E** (Tabular Worksheet) |
| **၁၅** | **စတော့တန်ဖိုး / အကြွေးသက်တမ်း စာရင်း (Inventory / Aging)** | `/store/{slug}/admin/reports/*/print` | `resources/views/admin/inventory_valuation/print.blade.php`<br>`resources/views/admin/debt_aging/print.blade.php` | A4 Portrait | **ချိတ်ဆက်မထားပါ (Report Export)** | **Group E** (Tabular Worksheet) |

---

## ၃။ Print Preview ဒီဇိုင်းတူညီမှုနှင့် ကွဲပြားမှုများ (Preview Consistency Analysis)

Print Preview မျက်နှာပြင်များကို အသုံးပြုသူအတွေ့အကြုံ (UI/UX) နှင့် Styling အရ **(၅) မျိုး** ခွဲခြားလေ့လာနိုင်ပါသည်-

### အုပ်စု (A) — ခေတ်မီ အပြန်အလှန်တုံ့ပြန်နိုင်သော စတူဒီယိုဒီဇိုင်း (Interactive Studio Toolbar)
- **ပါဝင်သော စာမျက်နှာများ:** POS Receipt, POS Daily Closing, Repair Handover Ticket, Commercial Tax Invoice.
- **သွင်ပြင်လက္ခဏာများ:**
  - အပေါ်ဘက်တွင် မရွေ့လျားသော Sticky Toolbar ပါဝင်သည်။
  - ပုံနှိပ်စက္ကူအရွယ်အစား (`58mm`, `80mm`, `A5`, `A4`) ခလုတ်များပါရှိပြီး နှိပ်လိုက်သည်နှင့် မျက်နှာပြင်ပေါ်တွင် စက္ကူအရွယ်အစားအလိုက် Live အချိုးအစား ပြောင်းလဲပြသပေးသည်။
  - `Print` ခလုတ်အပြင် `Save as PDF` (html2pdf ဖြင့် client-side PDF ထုတ်ယူခြင်း)၊ Direct Clipboard JPG Copy နှင့် Social Share (Viber/Telegram/WhatsApp) ပါဝင်သည်။
  - ကုမ္ပဏီအခွန်ပြေစာအတွက် TIN (အခွန်ထမ်းအမှတ်)၊ Code 128 ဘားကုဒ်၊ ငွေပေးချေမှု QR နှင့် တရားဝင် ၃ မျိုး လက်မှတ် + တံဆိပ်တုံး နေရာများ ပြည့်စုံစွာ ပါရှိသည်။
  - မြန်မာစာ Font (Noto Sans Myanmar) ကို Vite asset မှ တိုက်ရိုက် ချိတ်ဆက်ထားသဖြင့် Font မကွဲဘဲ အလွန်လှပသပ်ရပ်သည်။
- **တူညီမှုအဆင့်:** **၁၀၀% တူညီပြီး စံသတ်မှတ်ချက် အမြင့်မားဆုံးဖြစ်သည်**။

### အုပ်စု (B) — တရားဝင် ရုံးသုံးစာရွက်စာတမ်း ဒီဇိုင်း (Corporate Sheet Documents)
- **ပါဝင်သော စာမျက်နှာများ:** Cash/Bank Voucher (A5), Warranty Certificate (A4), Wholesale Application (A4).
- **သွင်ပြင်လက္ခဏာများ:**
  - A4 သို့မဟုတ် A5 စာရွက်အပြည့် Sheet Container (`.invoice-sheet`, `.cert-card`, `.voucher-box`) ဖြင့် ဖွဲ့စည်းထားသည်။
  - စတိုးဆိုင်၏ တရားဝင် Logo, ခေါင်းစဉ်ခွဲ, လက်မှတ်ထိုးရန်နေရာများ (Prepared By, Approved By, Customer Signature) ပါဝင်သည်။
  - အပေါ်ဘက်တွင် Print နှင့် Close ခလုတ်ပါဝင်ပြီး Print ထုတ်သည့်အခါ ခလုတ်များ အလိုအလျောက် ဖျောက်ပေးသည်။
- **တူညီမှုအဆင့်:** **ဖွဲ့စည်းပုံ သပ်ရပ်ညီညွတ်မှု မြင့်မားသည်**။

### အုပ်စု (C) — အပူပေးစက္ကူလိပ် သီးသန့်ပြေစာ (Thermal Slip Layout)
- **ပါဝင်သော စာမျက်နှာများ:** E-load Slip (80mm), Printer Test Print (58mm/80mm).
- **သွင်ပြင်လက္ခဏာများ:**
  - အကျဉ်းချုံး စာသား (Narrow layout, Monospace fonts, Dashed dividers) ဖြင့် ပြုလုပ်ထားသည်။
  - စာမျက်နှာဖွင့်သည်နှင့် Print Dialog တန်းပွင့်စေရန် `<body onload="window.print()">` ပါဝင်သည်။
- **တူညီမှုအဆင့်:** **Thermal စံနှုန်းချင်း တူညီသည်**။

### အုပ်စု (D) — ဘားကုဒ်စတစ်ကာ အထူးဒီဇိုင်း (Barcode Sticker Grid)
- **ပါဝင်သော စာမျက်နှာများ:** Barcode Label Print (`/admin/barcode/print`).
- **သွင်ပြင်လက္ခဏာများ:**
  - အခြားဘောက်ချာများနှင့် လုံးဝမတူဘဲ စတစ်ကာကော်ပြား အကွက်များ (Grid of adhesive labels) ပုံစံဖြစ်သည်။
  - စတစ်ကာအရွယ်အစား (40x30mm, 50x30mm, 35x25mm စသည်) နှင့် Zoom In/Out ထိန်းချုပ်ခလုတ်များ ပါဝင်သည်။

### အုပ်စု (E) — သိုလှောင်ရုံနှင့် စီမံခန့်ခွဲမှု အစီရင်ခံစာဇယားများ (Tabular Worksheets)
- **ပါဝင်သော စာမျက်နှာများ:** Stock Count Sheet, Stock Ledger Bin Card, Receivables Statement, P&L Statement, Inventory Valuation.
- **သွင်ပြင်လက္ခဏာများ:**
  - A4 Portrait/Landscape စာရွက်ပေါ်တွင် Data Table ကော်လံများဖြင့် စာရင်းဇယားပြသထားသော စာရွက်များ ဖြစ်သည်။

---

## ၄။ တွေ့ရှိချက်များနှင့် အကြံပြုချက်များ (Findings & Recommendations)

### အရေးကြီး တွေ့ရှိချက် (Critical Finding):
- `/admin/repairs/{id}/print` (RepairController) သည် `/admin/vouchers` နှင့် အပြည့်အဝ ချိတ်ဆက်ထားပြီး 58mm/80mm/A5/A4 အစုံအလင်ဖြင့် အလွန်ကောင်းမွန်စွာ အလုပ်လုပ်ပါသည်။
- သို့သော် Sidebar ရှိ `/admin/service-jobs/{job}/print` (ServiceJobController) တွင်မူ Controller ထဲ၌ `printTicket()` method မပါရှိသေးဘဲ `resources/views/admin/service_jobs/print.blade.php` ဖိုင်ဟောင်းတစ်ခု ကျန်ရှိနေသည်ကို စစ်ဆေးတွေ့ရှိရပါသည်။ Service Jobs စာမျက်နှာမှ Print ခလုတ်နှိပ်ပါက Error ဖြစ်နိုင်ခြေရှိသဖြင့် `RepairController::printTicket` နည်းတူ အဆင့်မြှင့်တင်ချိတ်ဆက်ပေးရန် လိုအပ်ပါသည်။

---

## ၅။ နိဂုံးချုပ်သုံးသပ်ချက်နှင့် စံသတ်မှတ်ချက် လမ်းညွှန် (Gold Standard Blueprint)

DataPOS ၏ ပုံနှိပ်ထုတ်ဝေမှု စနစ်သည် `/admin/vouchers` ဖြင့် ချိတ်ဆက်ထားသော Core Documents (အရောင်းပြေစာ၊ ဘောက်ချာ၊ အင်ဗွိုက်စ်၊ စက်ပြင်လက်မှတ်၊ အာမခံကတ်) များတွင် **Template-Driven Architecture** စနစ်ကျစွာ တည်ဆောက်ထားပြီး၊ စတိုးဆိုင် Logo, လိပ်စာ, ဖုန်းနံပါတ်, QR နှင့် စာချုပ်စည်းကမ်းချက်များကို ဗဟိုမှ ထိန်းချုပ်နိုင်ပါသည်။

စက်ပြင်လက်ခံလွှာ (Repair Ticket & Service Tracking Print) တွင် အောင်မြင်စွာ တည်ဆောက်ပြီးစီးခဲ့သော **Interactive Studio Toolbar**, **Direct Clipboard JPG Copy**, **Pixel-Perfect QR Code Canvas Stamping** နှင့် **Social Share Modal** စံသတ်မှတ်ချက် အပြည့်အစုံကို နောင်တွင် အခြား Print နေရာများ၌ ပုံတူကူးယူ အကောင်အထည်ဖော်ရန်အတွက် [docs/SERVICE_VOUCHER_PRINT_STANDARD.md](file:///d:/xmapp/htdocs/DataPOS/docs/SERVICE_VOUCHER_PRINT_STANDARD.md) တွင် အသေးစိတ် မှတ်တမ်းတင်ထားပြီး ဖြစ်ပါသည်။

