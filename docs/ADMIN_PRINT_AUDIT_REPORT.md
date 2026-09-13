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

### အရေးကြီး တွေ့ရှိချက် (Critical Finding) နှင့် ဖြေရှင်းပြီးစီးမှု:
- `/admin/repairs/{id}/print` (RepairController) သည် `/admin/vouchers` နှင့် အပြည့်အဝ ချိတ်ဆက်ထားပြီး 58mm/80mm/A5/A4 အစုံအလင်ဖြင့် အလွန်ကောင်းမွန်စွာ အလုပ်လုပ်ပါသည်။
- Sidebar ရှိ `/admin/service-jobs/{job}/print` (ServiceJobController) အတွက် `ServiceJobController::printTicket` method ကို `RepairController::printTicket` သို့ delegate ချိတ်ဆက်ပြီးဖြစ်သဖြင့် Group A Interactive Studio Toolbar ဖြင့် အပြည့်အဝ အောင်မြင်စွာ အလုပ်လုပ်ပါသည်။ (ယခင်ကျန်ရှိနေခဲ့သော `resources/views/admin/service_jobs/print.blade.php` ဖိုင်ဟောင်းမှာ အသုံးမပြုတော့သော Legacy ဖိုင်ဖြစ်ပါသည်)။

---

## ၅။ နိဂုံးချုပ်သုံးသပ်ချက်နှင့် စံသတ်မှတ်ချက် လမ်းညွှန် (Gold Standard Blueprint)

DataPOS ၏ ပုံနှိပ်ထုတ်ဝေမှု စနစ်သည် `/admin/vouchers` ဖြင့် ချိတ်ဆက်ထားသော Core Documents (အရောင်းပြေစာ၊ ဘောက်ချာ၊ အင်ဗွိုက်စ်၊ စက်ပြင်လက်မှတ်၊ အာမခံကတ်) များတွင် **Template-Driven Architecture** စနစ်ကျစွာ တည်ဆောက်ထားပြီး၊ စတိုးဆိုင် Logo, လိပ်စာ, ဖုန်းနံပါတ်, QR နှင့် စာချုပ်စည်းကမ်းချက်များကို ဗဟိုမှ ထိန်းချုပ်နိုင်ပါသည်။

စက်ပြင်လက်ခံလွှာ (Repair Ticket & Service Tracking Print) တွင် အောင်မြင်စွာ တည်ဆောက်ပြီးစီးခဲ့သော **Interactive Studio Toolbar**, **Direct Clipboard JPG Copy**, **Pixel-Perfect QR Code Canvas Stamping** နှင့် **Social Share Modal** စံသတ်မှတ်ချက် အပြည့်အစုံကို နောင်တွင် အခြား Print နေရာများ၌ ပုံတူကူးယူ အကောင်အထည်ဖော်ရန်အတွက် [docs/SERVICE_VOUCHER_PRINT_STANDARD.md](file:///d:/xmapp/htdocs/DataPOS/docs/SERVICE_VOUCHER_PRINT_STANDARD.md) တွင် အသေးစိတ် မှတ်တမ်းတင်ထားပြီး ဖြစ်ပါသည်။

---

## ၆။ ပြီးစီးခဲ့သော အဆင့်မြှင့်တင်မှုများနှင့် ပြင်ဆင်ချက်များ မှတ်တမ်း (Completed Upgrades Log)

လက်ရှိအချိန်အထိ အောင်မြင်စွာ စစ်ဆေးပြင်ဆင်ပြီးစီးခဲ့သော အပိုင်းများမှာ အောက်ပါအတိုင်း ဖြစ်ပါသည်-

### (၁) Commercial Tax Invoice (ကုမ္ပဏီသုံး အခွန်ပြေစာ) အဆင့်မြှင့်တင်ခြင်း
- **ဖိုင်:** `resources/views/admin/orders/invoice.blade.php`
- **ဆောင်ရွက်ချက်များ:**
  - **Interactive Top Nav Bar ရလဒ်ကောင်းမွန်စေရန် ပြင်ဆင်ခြင်း:** ခလုတ်များ မလှုပ်ရှားနိုင်ဖြစ်နေစေသည့် JavaScript Syntax Error ကို အပြီးသတ် ရှင်းလင်းပေးခဲ့ပြီး `Back to Order`, `Paper Size Switcher (58mm, 80mm, A5, A4)`, `Print/PDF (`printInvoice()`)`, `Download PDF (`downloadPdfDirectly`)`, `Share as JPG (`shareJpgDirectly`)`, `Copy to Clipboard (`copyJpgToClipboard`)` စသည့် ခလုတ်အားလုံး ၁၀၀% ကောင်းမွန်စွာ အလုပ်လုပ်စေခဲ့ပါသည်။
  - **စက္ကူဆိုဒ် တိကျသော အချိုးအစား သတ်မှတ်ချက် (Exact mm Dimensions):** A4 (210 × 297 mm, 8.27 × 11.69 in) နှင့် A5 (148 × 210 mm, 5.8 × 8.3 in) တို့ကို အတိအကျ သတ်မှတ်ပြီး မျက်နှာပြင်ငယ်များတွင် အချိုးအစားမပျက်စေဘဲ Responsive Proportional Scaling (`updatePreviewScale()`) ဖြင့် ညှိယူပြသပေးခဲ့ပါသည်။ Print, PDF Export နှင့် JPG Snapshot တို့တွင် စာသား/ဘောင်များ ပြတ်တောက်မှု မရှိစေရန် စီမံထားပါသည်။
  - **ကုန်သွယ်လုပ်ငန်းခွန် ပြည့်စုံစွာ ပြသခြင်း:** စည်းကြပ်ငွေ/ကျသင့်ငွေ (Subtotal / Taxable Amount)၊ ကုန်သွယ်လုပ်ငန်းခွန် (Commercial Tax 5%) နှင့် စုစုပေါင်းငွေ (Total Amount) တို့ကို တိကျစွာ ခွဲခြမ်းပြသပေးပါသည်။

### (၂) အော်ဒါအသေးစိတ် စာမျက်နှာများတွင် ကုန်သွယ်လုပ်ငန်းခွန် (Commercial Tax) ပေါင်းစပ်ဖော်ပြခြင်း
- **Admin Order Details (`/store/{slug}/admin/orders/{id}`):**
  - ဖိုင်: `resources/views/admin/orders/show.blade.php`
  - အော်ဒါအချက်အလက် စုစုပေါင်းငွေ အကျဉ်းချုပ်ဘား၊ ပစ္စည်းစာရင်း Table Rows (Taxable Badge) နှင့် Table Footer (`tfoot`) တို့တွင် `ကျသင့်ငွေ: 700,000 Ks`၊ `🏛️ ကုန်သွယ်လုပ်ငန်းခွန် (5%): +35,000 Ks` နှင့် `စုစုပေါင်း: 735,000 Ks` ကို ပြည့်စုံစွာ ထည့်သွင်းပေးခဲ့ပါသည်။ စတော့နှင့် အရေအတွက်ကိုလည်း `format_quantity()` ဖြင့် `.000` အပိုများ ကင်းစင်စေခဲ့ပါသည်။
- **Customer Storefront Order Details (`/account/orders/{id}?store_slug=...`):**
  - ဖိုင်: `resources/views/customer/account/order_show.blade.php`
  - ဖောက်သည် ဝက်ဘ်ဆိုဒ် ငွေကြေးတွက်ချက်မှုဘောက်စ်တွင် ကုန်သွယ်လုပ်ငန်းခွန် ခွဲခြမ်းပြသပေးခဲ့သည်။
  - ဖောက်သည် ဖွင့်ကြည့်သည့် **Thermal Receipt Slip Modal** နှင့် စက္ကူဖြတ်စလစ် **Printable Thermal Slip (`@media print`)** နှစ်ခုစလုံးတွင် Subtotal, ကုန်သွယ်လုပ်ငန်းခွန် (5%) နှင့် Total Amount အပြည့်အစုံ ပါဝင်စေခဲ့ပါသည်။
- **Customer Orders History List (`/account/orders?store_slug=...`):**
  - ဖိုင်: `resources/views/customer/account/orders.blade.php`
  - အော်ဒါစာရင်း Card View နှင့် Table View ဈေးနှုန်းအောက်တွင် အခွန်ပါဝင်သောအော်ဒါဖြစ်ပါက `+ကုန်သွယ်လုပ်ငန်းခွန်` badge ဖြင့် ရှင်းလင်းစွာ အသိပေးထားပါသည်။

### (၃) Service Jobs Print Route ချိတ်ဆက်မှု
- `ServiceJobController::printTicket` အား `RepairController::printTicket` နှင့် ချိတ်ဆက်ပြီးဖြစ်သဖြင့် Service Jobs အပိုင်းမှ Print ထုတ်ပါက စံချိန်မီ Group A Interactive Studio Toolbar ပါရှိသော Repair Handover Ticket (`admin/repairs/print.blade.php`) ကို အပြည့်အဝ ရရှိအသုံးပြုနိုင်ပါသည်။

### (၄) Group B တရားဝင် ရုံးသုံးစာရွက်စာတမ်းများ (Corporate Sheet Documents) အဆင့်မြှင့်တင်ခြင်း [ပြီးစီး]
- **ပါဝင်သော ဖိုင်များ:**
  1. `resources/views/admin/warranty/certificate.blade.php` (အာမခံသက်သေခံလွှာ - A5 Portrait Default / A4 Switchable)
  2. `resources/views/admin/transactions/voucher.blade.php` (ငွေစာရင်းဘောက်ချာ - A5 Portrait)
  3. `resources/views/admin/wholesale/print.blade.php` (လက်ကားဖောက်သည် လျှောက်လွှာ - A4 Portrait)
- **ဆောင်ရွက်ချက်များ:**
  - **တိကျသော စက္ကူအရွယ်အစား သတ်မှတ်ချက် (Exact Dimensions):**
    - A5 အတွက် `148mm × 210mm` (`@page { size: 148mm 210mm; margin: 0; }`) — Warranty Certificate & Transaction Voucher။
    - A4 အတွက် `210mm × 297mm` (`@page { size: 210mm 297mm; margin: 0; }`) — Wholesale Application & Warranty Certificate (A4 mode)။
    - အာမခံလက်မှတ်တွင် မိုဘိုင်း/ကွန်ပျူတာဆိုင်များ စာရွက်ကုန်ကျစရိတ် သက်သာစေရန်နှင့် ဖုန်းဗူး/ဖိုင်တွဲများအတွင်း လွယ်ကူစွာ ထည့်သွင်းနိုင်ရန်အတွက် **A5 (Half Sheet - 148 × 210 mm)** ကို မူလ Standard အဖြစ် သတ်မှတ်ပေးခဲ့ပြီး Toolbar မှတစ်ဆင့် A4 သို့ အချိန်မရွေး လွတ်လပ်စွာ ပြောင်းလဲထုတ်ယူနိုင်ပါသည်။
  - **Responsive Proportional Scaling (`updatePreviewScale()`):** မိုဘိုင်းဖုန်း သို့မဟုတ် မျက်နှာပြင်ငယ်များတွင် ဘေးဘောင်နှင့် စာသားများ ပြတ်တောက်မှု မရှိစေဘဲ စာရွက်အချိုးအစားအတိုင်း အချိုးညီ လျှော့ချပြသပေးခြင်း။
  - **Standard Top Sticky Action Bar (`.top-nav-bar`):**
    - `Back to List/Detail` လမ်းညွှန်ခလုတ်။
    - Document Badge (ဘောက်ချာ/လက်မှတ် နံပါတ် အပြည့်အစုံ)။
    - `Share / Copy JPG` ခလုတ် (html2canvas ဖြင့် Clipboard သို့ ရုပ်ပုံတိုက်ရိုက်ကူးယူခြင်း / Download ပြုလုပ်ခြင်း)။
    - `Save as PDF` ခလုတ် (html2pdf client-side စာရွက်ဆိုဒ်အလိုက် တိုက်ရိုက်သိမ်းဆည်းခြင်း)။
    - `Print` ခလုတ် (`window.print()`)။
  - **Myanmar Font & Tri-lingual Localization:**
    - `Noto Sans Myanmar` ဖောင့်အား Vite asset မှ တိုက်ရိုက် ချိတ်ဆက်အသုံးပြုထားသဖြင့် မြန်မာစာလုံးပေါင်းများ မကွဲဘဲ အလွန်လှပသပ်ရပ်ခြင်း။
    - ဘာသာစကား ၃ မျိုး (`my`, `en`, `zh_CN`) အပြည့်အစုံ ပံ့ပိုးပေးထားပြီး Hardcoded စာသားများနှင့် ငွေကြေးသတ်မှတ်ချက်များ လုံးဝမပါဝင်စေဘဲ စံနှုန်းမီ ရေးဆွဲထားခြင်း။
  - **Automated Feature Tests:** `CashBankTransactionTest` (9 passed, 37 assertions) နှင့် `WholesaleWorkflowTest` (17 passed, 90 assertions) တို့ဖြင့် အောင်မြင်စွာ စစ်ဆေးပြီးစီး။
  - **Strict CSP Compliance & Toolbar Event Handlers Fix:**
    - `certificate.blade.php`, `voucher.blade.php` နှင့် `wholesale/print.blade.php` တို့တွင် Content Security Policy (CSP) ကြောင့် inline `onclick="..."` / `onchange="..."` များနှင့် un-nonced script များ ပိတ်ပင်ခံရနိုင်ခြေအား ဖြေရှင်းပေးခဲ့သည်။
    - `<script nonce="{{ $cspNonce ?? '' }}">` ဖြင့် dynamic nonce အပြည့်အဝ ထည့်သွင်းပေးခဲ့ပြီး ခလုတ်များနှင့် Paper Size Dropdown များအတွက် `addEventListener` ဖြင့် standard compliant ဖြစ်စေခဲ့သည်။
    - Vendor script URL များတွင် `asset(...)` ကြောင့် မတူညီသော port နံပါတ် ချိတ်ဆက်မိနိုင်ခြေအား Relative path (`/vendor/html2pdf/html2pdf.bundle.min.js`) သို့ ပြောင်းလဲ၍ Host/Port သီးခြားစီ မဖြစ်စေဘဲ အမြဲ အဆင်ပြေစေရန် ပြင်ဆင်ပြီးစီး။


---

### (၅) Group E သိုလှောင်ရုံနှင့် စီမံခန့်ခွဲမှု အစီရင်ခံစာဇယားများ (Worksheets & Statements) အဆင့်မြှင့်တင်ခြင်း [ပြီးစီး]
- **ပါဝင်သော ဖိုင်များ:**
  1. `resources/views/admin/stock_count/print.blade.php` (သိုလှောင်ရုံ စတော့စစ်စာရွက် - A4 Portrait)
  2. `resources/views/admin/stock_ledger/print_bin_card.blade.php` (ပစ္စည်းလက်ကျန်ကတ်ပြား - A4 Portrait)
  3. `resources/views/admin/inventory_valuation/print.blade.php` (ကုန်ပစ္စည်းတန်ဖိုးရှင်းတမ်း - A4 Landscape)
  4. `resources/views/admin/debt_aging/print.blade.php` (အကြွေးသက်တမ်းခွဲခြမ်းစိတ်ဖြာမှု ရှင်းတမ်း - A4 Landscape)
- **ဆောင်ရွက်ချက်များ:**
  - **Standard Top Sticky Action Bar (`.top-nav-bar`):** Back button, Document Badge, `🖼️ Copy JPG`, `📥 Save as PDF`, `🖨️ Print` ခလုတ်များ အပြည့်အစုံ ပါဝင်စေခဲ့သည်။
  - **Strict CSP Compliance:** `<script nonce="{{ $cspNonce ?? '' }}">` နှင့် DOM Event Listeners (`addEventListener`) များဖြင့် လုံခြုံရေးစံနှုန်း အပြည့်အဝ ညီညွတ်စေခဲ့သည်။
  - **Clean Quantity Formatting:** စတော့အရေအတွက်များတွင် `.000` အပိုများ ကင်းစင်စေပြီး သန့်ရှင်းစွာ ပြသပေးခြင်း (`format_quantity($qty, $store)` သို့မဟုတ် `$fmtQty`)။
  - **Myanmar Font & Logo Branding:** `Noto Sans Myanmar` ဖောင့် ချိတ်ဆက်ပြီး စတိုးဆိုင် Logo, Phone, Address များကို Header တွင် စနစ်တကျ ပြသပေးခြင်း။
  - **Signatures Area:** ရေတွက်သူ (Counted by)၊ စစ်ဆေးသူ (Verified by) နှင့် စတိုးမန်နေဂျာ (Approved by) လက်မှတ်ထိုးရန် ဇယားကွက်များ စနစ်ကျစွာ ထည့်သွင်းပေးခဲ့သည်။
  - **Automated Tests:** `StockCountTest` (12 passed), `StockLedgerTest` (7 passed), `InventoryValuationTest` (6 passed), `DebtAgingTest` (5 passed) စုစုပေါင်း 30 Tests အားလုံး အောင်မြင်စွာ စစ်ဆေးပြီးစီး။

### (၆) Group C အပူပေးစက္ကူလိပ် သီးသန့်ပြေစာများနှင့် စမ်းသပ်စာရွက်များ [ပြီးစီး]
- **ပါဝင်သော ဖိုင်များ:**
  1. `resources/views/admin/eload/_slip.blade.php` (ဖုန်းဘေလ်ဖြည့် စလစ်ပြေစာ - 80mm & 58mm Multi-width)
  2. `resources/views/admin/printers/test_print.blade.php` (ပရင်တာ ဟာ့ဒ်ဝဲလ် စမ်းသပ်စာရွက်)
- **ဆောင်ရွက်ချက်များ:**
  - **58mm / 80mm Dynamic Multi-width:** မြန်မာနိုင်ငံရှိ ဖုန်းအရောင်းဆိုင်များတွင် အသုံးများသော 58mm အပူပေးစက္ကူလိပ်များတွင် စာသားများ ဘေးဘောင်ကျော်မသွားစေရန် Dynamic `@page` width နှင့် Font size များကို စံနှုန်းမီ ချိန်ညှိပေးခဲ့သည်။
  - **Hardware Diagnostic Test Pattern:** 58mm (32 characters ruler) နှင့် 80mm (42-48 characters ruler) အတွက် သီးခြား Alignment Test Pattern များ ထည့်သွင်းပေးခဲ့သည်။
  - **Inline Event Handlers Removal:** Inline `onload` / `onclick` များကို ဖယ်ရှားပြီး CSP compliant listeners များဖြင့် ပြင်ဆင်ပြီးစီး။

### (၇) အသုံးမပြုတော့သော ဖိုင်ဟောင်းများ ရှင်းလင်းသိမ်းဆည်းခြင်း (Legacy Cleanup) [ပြီးစီး]
- `resources/views/admin/service_jobs/print.blade.php` အား `admin.repairs.print` သို့ စနစ်တကျ Forward လုပ်ပေးပြီး Duplicate Code များကို ရှင်းလင်းသိမ်းဆည်းခဲ့သည်။

---

## ၇။ ပုံနှိပ်စနစ် အလုံးစုံ စစ်ဆေးပြီးစီးမှု အခြေအနေ (Full Audit & Upgrade Completed)

DataPOS စနစ်အတွင်းရှိ ပုံနှိပ်ထုတ်ဝေမှုစနစ် အားလုံး (**Group A, Group B, Group C, Group D, Group E**) အား စနစ်တကျ စစ်ဆေးပြင်ဆင်ပြီး ဖြစ်ပါသည်-
- ✅ **Group A (Interactive Studio Templates):** Orders Invoice (`admin/orders/invoice.blade.php`) နှင့် Repair Handover Ticket (`admin/repairs/print.blade.php`) တို့တွင် Multi-template, Thermal/A5/A4, Barcode/QR, Image/PDF Export စနစ်များ အပြည့်အစုံ ပြီးစီး။
- ✅ **Group B (Corporate Sheet Documents):** Warranty Certificate (`admin/warranty/certificate.blade.php`), Transaction Voucher (`admin/transactions/voucher.blade.php`), Wholesale Application (`admin/wholesale/print.blade.php`) တို့တွင် A5/A4 စံနှုန်းနှင့် CSP Nonce ပြီးစီး။
- ✅ **Group C (Thermal Slips):** POS Receipt (`pos/receipt.blade.php`), E-load Slip (`admin/eload/_slip.blade.php`), Printer Test Print (`admin/printers/test_print.blade.php`) တို့တွင် 80mm/58mm Dual-size နှင့် Hardware diagnostic ပြီးစီး။
- ✅ **Group D (Hardware Utility):** Barcode Generator (`admin/barcode/print.blade.php`) စံနှုန်းပြည့်မီ။
- ✅ **Group E (Worksheets & Statements):** Stock Count Audit (`stock_count/print.blade.php`), Stock Bin Card (`stock_ledger/print_bin_card.blade.php`), Inventory Valuation (`inventory_valuation/print.blade.php`), Debt Aging (`debt_aging/print.blade.php`) တို့တွင် Toolbar, Scaling, Clean Qty, Noto Myanmar Font ဖြင့် အပြည့်အဝ အဆင့်မြှင့်တင်ပြီးစီး။


