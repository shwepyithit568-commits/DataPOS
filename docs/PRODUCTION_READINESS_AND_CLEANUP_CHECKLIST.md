# DataPOS — Production အဆင့်မီ စစ်ဆေးပြုပြင်ရေးနှင့် ပရောဂျက် သန့်ရှင်းရေး မာစတာ Checklist
**Document Version:** 1.0.0  
**Target:** Production Launch & Commercial Deployment Readiness  
**System Base:** Laravel 12 + Alpine.js + Tailwind CSS + Offline-First Architecture (No Livewire / No jQuery)  
**Author:** Tech Buddy & Boss  
**Date:** August 2026

---

## 📋 မာတိကာ (Table of Contents)
1. [ခြုံငုံသုံးသပ်ချက်နှင့် ရည်ရွယ်ချက်](#၁-ခြုံငုံသုံးသပ်ချက်နှင့်-ရည်ရွယ်ချက်)
2. [အပိုင်း ၁။ မလိုအပ်သော အပိုဖိုင်များ ရှင်းလင်းရေး (File Cleanup Audit)](#အပိုင်း-၁-မလိုအပ်သော-အပိုဖိုင်များ-ရှင်းလင်းရေး-file-cleanup-audit)
3. [အပိုင်း ၂။ စာမျက်နှာတစ်ခုချင်းစီ Production Audit Checklist](#အပိုင်း-၂-စာမျက်နှာတစ်ခုချင်းစီ-production-audit-checklist)
4. [အပိုင်း ၃။ စာမျက်နှာတိုင်းတွင် မဖြစ်မနေ စစ်ဆေးရမည့် အချက်များ (Standard Verification Criteria)](#အပိုင်း-၃-စာမျက်နှာတိုင်းတွင်-မဖြစ်မနေ-စစ်ဆေးရမည့်-အချက်များ)
5. [အပိုင်း ၄။ အဆင့်လိုက် စမ်းသပ်ပြုပြင်မည့် အစီအစဉ် (Phased Execution Plan)](#အပိုင်း-၄-အဆင့်လိုက်-စမ်းသပ်ပြုပြင်မည့်-အစီအစဉ်)

---

## ၁။ ခြုံငုံသုံးသပ်ချက်နှင့် ရည်ရွယ်ချက်

DataPOS စနစ်ကို အမှန်တကယ် လက်တွေ့ဆိုင်များတွင် Install ပြုလုပ်ရောင်းချခြင်း (Commercial Production Deployment) မပြုလုပ်မီ:
1. ပရောဂျက်အတွင်း ကျန်ရှိနေသော မလိုအပ်သည့် Temporary logs, scratch scripts, dead files များကို သန့်ရှင်းဖယ်ရှားရန်၊
2. စနစ်အတွင်းရှိ Admin စာမျက်နှာ (၅၀) ကျော်နှင့် POS စနစ်တစ်ခုချင်းစီ၏ UI, UX, Button လုပ်ဆောင်ချက်များ၊ မြန်မာဖောင့်နှင့် Error Handling များကို စနစ်တကျ စစ်ဆေးပြင်ဆင်ရန်၊
3. အမှားအယွင်းကင်းစင်ပြီး လုပ်ငန်းရှင်များ ယုံကြည်စိတ်ချစွာ သုံးစွဲနိုင်သော Production Build ထုတ်နိုင်ရန် ရည်ရွယ်ပါသည်။

---

## အပိုင်း ၁။ မလိုအပ်သော အပိုဖိုင်များ ရှင်းလင်းရေး (File Cleanup Audit)

*မှတ်ချက် - ဖိုင်များကို အပြီးဖျက်ခြင်းမပြုမီ လိုအပ်ပါက Backup သို့မဟုတ် `trash` ဖြင့်သာ ရှင်းလင်းရပါမည်။*

### (က) ဖယ်ရှား/သန့်ရှင်းသင့်သော ယာယီဖိုင်များ (Candidates for Removal)
- [x] `headers.txt` (Root directory ရှိ ယာယီ HTTP header dump ဖိုင် — ဖယ်ရှားပြီး)
- [x] `.freebuff-preview.log` (Root directory ရှိ Freebuff preview log ဖိုင်အဟောင်း — ဖယ်ရှားပြီး)
- [x] `.phpunit.result.cache` (Test run cache ဖိုင် — `.gitignore` တွင် ထည့်သွင်းပြီး)
- [x] `scripts/lh-cookie.php` (Lighthouse cookie scratch script — ဖယ်ရှားပြီး)
- [x] `storage/logs/laravel.log` (Development ကာလ Error Log အဟောင်းများအား ရှင်းလင်းပြီး Clean Log စတင်ရန်)

### (ခ) သိမ်းဆည်းထားရမည့် မရှိမဖြစ် ပင်မဖိုင်များ (Must KEEP — DO NOT DELETE)
- [x] `.env` / `.env.example`
- [x] `Source_of_Truth_MM.md` (Project Architecture Baseline)
- [x] `docs/ADMIN_MODULES_EXECUTION_ROADMAP.md` (Module Roadmap Baseline)
- [x] `docs/MYANMAR_SME_COMMERCIALIZATION_GUIDE.md` (Commercialization Guide)
- [x] `Ecommerce_Product.xlsx` / `Ecommerce_Product_final.xlsx` (Pilot Import အတွက် Demo Data Sheets)
- [x] `deploy-datapos.sh` (Production Deployment Script)

---

## အပိုင်း ၂။ စာမျက်နှာတစ်ခုချင်းစီ Production Audit Checklist

---

### အုပ်စု ၁။ POS အရောင်းကောင်တာ (`POS & In-store Sales`)
- [x] **၁.၁ POS Sale Counter (`/store/{slug}/pos`)**
  - [x] Barcode Scanner / SKU ဖြင့် ပစ္စည်း ရှာဖွေ/ထည့်သွင်းမှု အလုပ်လုပ်ခြင်း။
  - [x] Walk-in Customer နှင့် Existing Customer ရွေးချယ်မှု မှန်ကန်ခြင်း။
  - [x] Discount (ရာခိုင်နှုန်း / ပမာဏ) နှင့် Tax တွက်ချက်မှု တိကျခြင်း။
  - [x] Multi-payment (Cash, KPay, Wave, Debt Credit) ရှင်းတမ်း မှန်ကန်ခြင်း။
  - [x] Order Hold (ခေတ္တဆိုင်းငံ့) နှင့် Recall ပြန်ခေါ်ခြင်း စနစ် အလုပ်လုပ်ခြင်း။
  - [x] Thermal Printer ဘောက်ချာ ချက်ချင်း print ထွက်ခြင်း။
- [x] **၁.၂ နေ့စဉ် အရောင်းပိတ် (`/store/{slug}/pos/closing`)**
  - [x] Opening Float (အဖွင့်ငွေ)၊ Cash In/Out၊ Total Cash in Drawer တွက်ချက်မှု။
  - [x] Expected vs Actual Cash ကွာဟချက် (Discrepancy) စစ်ဆေးခြင်း။
  - [x] Daily Closing Summary Slip ထုတ်ယူခြင်း။
- [x] **၁.၃ အရောင်း ပြန်အမ်းငွေ (`/store/{slug}/pos/returns`)**
  - [x] Invoice No ဖြင့် ပြန်အမ်းပစ္စည်း ရှာဖွေခြင်း။
  - [x] ပစ္စည်းစတော့ ပြန်တိုးခြင်းနှင့် ငွေသား/Credit ပြန်အမ်းမှု မှန်ကန်ခြင်း။
- [x] **၁.၄ အဟောင်း ပြန်ဝယ် (`/store/{slug}/pos/buy-back`)**
  - [x] Customer ID, Phone, Device Model, Serial/IMEI, ပေးချေငွေ မှတ်တမ်းတင်ခြင်း။
  - [x] Buy-back စာရင်းအား Inventory သို့ ပြန်သွင်းနိုင်ခြင်း။
- [x] **၁.၅ ဖုန်းဘေလ်ဖြည့် (`/store/{slug}/admin/eload`)**
  - [x] Operator ၄ ခု (MPT, Atom, Ooredoo, Mytel) Balance ထိန်းချုပ်မှု။
  - [x] Quick Top-up နှင့် ကော်မရှင်/အမြတ်ငွေ အလိုအလျောက် တွက်ချက်မှု။

---

### အုပ်စု ၂။ ကုန်ပစ္စည်းနှင့် စတော့ (`Inventory & Products`)
- [x] **၂.၁ အခြေခံ အမျိုးအစား (`/store/{slug}/admin/products/master-data`)**
  - [x] Categories, Brands, Variant Presets CRUD လုပ်ဆောင်ချက်များ။
- [x] **၂.၂ ကုန်ပစ္စည်းများ (`/store/{slug}/admin/products`)**
  - [x] Product Create / Edit / Delete လုပ်ဆောင်ချက်များနှင့် Image Upload။
  - [x] Barcode / SKU ထပ်နေမှု တားဆီးပေးခြင်း။
  - [x] Cost Price (ဝယ်စျေး), Normal Price (ရောင်းစျေး), Wholesale Price (လက်ကားစျေး)။
- [x] **၂.၃ Barcode / QR ထုတ်ခြင်း (`/store/{slug}/admin/barcode`)**
  - [x] 50x30mm, 40x30mm, A4 Label စတစ်ကာ Print ထုတ်ခြင်း။
- [x] **၂.၄ ဈေးနှုန်း အစုလိုက်ပြင်ခြင်း (`/store/{slug}/admin/price-wizard`)**
  - [x] Category/Brand အလိုက် Margin % ဖြင့် ဈေးနှုန်း အစုလိုက် update ပြုလုပ်ခြင်း။
- [x] **၂.၅ အာမခံနှင့် Serial စစ်ဆေးခြင်း (`/store/{slug}/admin/warranty`)**
  - [x] Serial/IMEI ဖြင့် အာမခံသက်တမ်းနှင့် ဝယ်ယူခဲ့သည့် ဘောက်ချာ ရှာဖွေခြင်း။
- [x] **၂.၆ စတော့ စာရင်းမှတ်တမ်း (`/store/{slug}/admin/stock-ledger`)**
  - [x] ပစ္စည်းတစ်ခုချင်းစီ၏ အဝင်/အထွက်/အရောင်း/အဝယ် Timeline Bin Card ပြသခြင်း။
- [x] **၂.၇ စတော့ ရေတွက်စစ်ဆေးခြင်း (`/store/{slug}/admin/stock-count`)**
  - [x] Physical Stock Count စစ်တမ်းစာရွက်ထုတ်ခြင်းနှင့် Discrepancy Auto-Adjust။
- [x] **၂.၈ စတော့ အတိုး/အလျော့ ညှိခြင်း (`/store/{slug}/pos/adjustments`)**
  - [x] Damage, Lost, Expired, Audit အကြောင်းပြချက်များဖြင့် စတော့ အတိုး/အလျော့ လုပ်ခြင်း။
  - [x] Modern Form, Multi-row Auto-search နှင့် Manager Approval Workflow။
- [x] **၂.၉ စတော့ ကွာဟချက် ညှိနှိုင်းခြင်း (`/store/{slug}/pos/reconciliation`)**
  - [x] Opening Stock vs Ledger Variance စစ်တမ်းနှင့် Net Diff Calculation။
  - [x] Only Diffs filter နှင့် Manager Auto-reconciliation Posting။
- [x] **၂.၁၀ စတော့ အဖွင့်စာရင်း သွင်းခြင်း (`/store/{slug}/pos/opening-stock`)**
  - [x] Opening Stock Balance Setup, Unit Cost Calculation နှင့် Review Approval။
- [x] **၂.၁၁ ကုန်ပစ္စည်း တင်သွင်းရန် (`/store/{slug}/admin/products/import`)**
  - [x] Excel / CSV ဖြင့် ကုန်ပစ္စည်းများ Batch Import ပြုလုပ်ခြင်း။

---

### အုပ်စု ၃။ အဝယ်နှင့် ဂိုဒေါင်လွှဲပြောင်း (`Purchasing & Transfers`)
- [x] **၃.၁ ကုန်သွင်းသူများ (`/store/{slug}/admin/suppliers`)**
  - [x] Supplier ကုမ္ပဏီအမည်၊ ဖုန်း၊ လိပ်စာ၊ ဘဏ်အကောင့် မှတ်တမ်း။
  - [x] Excel / CSV Supplier Batch Import, Template Download, Aging Report။
- [x] **၃.၂ အဝယ် အမှာစာ (`/store/{slug}/pos/purchases`)**
  - [x] Purchase Order (PO) ဖွင့်ခြင်း (Pending -> Ordered -> Received)။
  - [x] ပစ္စည်းလက်ခံခြင်း (GRN) နှင့် စတော့ အလိုအလျောက် တိုးခြင်း (Inventory Ledger Movement)။
  - [x] ဝယ်ယူမှု ဘောက်ချာ/ပြေစာ ဖိုင် (Image/PDF) တွဲတင်ခြင်း။
- [x] **၃.၃ အဝယ် ပစ္စည်းပြန်ပို့ (`/store/{slug}/pos/purchases/returns`)**
  - [x] ပျက်စီး/မှားယွင်းသော ပစ္စည်းများ Supplier ထံ ပြန်ပို့ပြီး ငွေ/စတော့ နှုတ်ခြင်း။
- [x] **၃.၄ ကုန်သွင်းသူ ပေးရန်ရှိ အကြွေး (`/store/{slug}/pos/purchases/payables`)**
  - [x] Supplier Payables အကြွေးစာရင်းနှင့် FIFO စနစ်ဖြင့် ကျန်ငွေဆပ်ခြင်း မှတ်တမ်း။
  - [x] Supplier Debt Summary နှင့် PDF/CSV Export။
- [x] **၃.၅ ပစ္စည်း လွှဲပြောင်းခြင်း (`/store/{slug}/pos/transfers`)**
  - [x] ဆိုင်ခွဲအချင်းချင်း သို့မဟုတ် ဂိုဒေါင်မှ ဆိုင်သို့ စတော့ လွှဲပြောင်းခြင်း (Pending -> In Transit -> Completed)။
  - [x] Multi-item Transfer နှင့် Transfer Notes။
- [x] **၃.၆ ဂိုဒေါင်များ စီမံခြင်း (`/store/{slug}/admin/warehouses`)**
  - [x] ပင်မဂိုဒေါင်၊ ဆိုင်ရှေ့စတော့၊ ဆိုင်ခွဲဂိုဒေါင်များ ခွဲခြားထိန်းချုပ်ခြင်း။
  - [x] Active Products Count နှင့် Total Stock Quantity metrics များ ပြသခြင်း။

---

### အုပ်စု ၄။ အွန်လိုင်း Storefront (`Ecommerce Storefront`)
- [x] **၄.၁ အမှာစာများ (`/store/{slug}/admin/orders`)**
  - [x] Online Orders စာရင်း၊ Status ပြောင်းလဲခြင်း (Pending Contact, Confirmed, Delivered, Cancelled)။
  - [x] Invoice View, Print, Delete, Admin Internal Note, Agreed Amount/Finance Management နှင့် Excel/CSV Export။
- [x] **၄.၂ အွန်လိုင်း ပစ္စည်းပြသမှု (`/store/{slug}/admin/web-products`)**
  - [x] Website တွင် ပြသမည့် ပစ္စည်းများနှင့် Counter Only ပစ္စည်းများ ခွဲထုတ်ထိန်းချုပ်မှု (Single & Bulk Toggle)။
- [x] **၄.၃ ပရိုမိုးရှင်းနှင့် Coupon (`/store/{slug}/admin/promotions`)**
  - [x] Coupon Code, Percentage Off, Fixed Discount, Minimum Spend & Expiry Date သတ်မှတ်ခြင်း။
- [x] **၄.၄ ကုန်ပစ္စည်း မှတ်ချက်များ (`/store/{slug}/admin/reviews`)**
  - [x] Customer Star Ratings & Reviews စစ်ဆေးအတည်ပြုခြင်း (Toggle Approve / Delete)။
- [x] **၄.၅ ပင်မ စာမျက်နှာ Banner များ (`/store/{slug}/admin/banners`)**
  - [x] Promo Banners, Slider Images တင်ခြင်းနှင့် Target Link ချိတ်ဆက်မှု။
- [x] **၄.၆ ဆောင်းပါးများ (`/store/{slug}/admin/blog`)**
  - [x] Tech Articles, Tips & Guides ရေးသားတင်ပြခြင်း (Rich Content, Categories, Draft/Published)။
- [x] **၄.၇ မှန်ကပ် ရှာဖွေရန် (`/store/{slug}/admin/glass-finder`)**
  - [x] ဖုန်း Brand / Model အလိုက် သင့်တော်သော မှန်ကပ် စာရင်း ရှာဖွေမှု၊ CSV Import & Code Normalization။
- [x] **၄.၈ Web Push သတိပေးချက်များ (`/store/{slug}/admin/push`)**
  - [x] Order Alert, Payment Alert & Promotion Push Notifications ပို့ခြင်းနှင့် History မှတ်တမ်း။

---

### အုပ်စု ၅။ ဖောက်သည်နှင့် Member (`Customers & CRM`)
- [x] **၅.၁ ဖောက်သည် စာရင်း (`/store/{slug}/admin/customers`)**
  - [x] Customer CRUD (အမည်၊ ဖုန်း၊ လိပ်စာ၊ Note)၊ ဝယ်ယူမှုသမိုင်း၊ အကြွေးကျန်ငွေ Profile နှင့် CSV Export။
  - [x] POS Quick Add Customer နှင့် Store Scoped Isolation။
- [x] **၅.၂ လက်ကား လျှောက်လွှာများ (`/store/{slug}/admin/wholesale/applications`)**
  - [x] B2B Wholesale Customer လျှောက်လွှာများ Review/Approve/Reject၊ Print Slip နှင့် CSV Export။
  - [x] Approve ဖြစ်ပြီးပါက Storefront တွင် Wholesale Price အလိုအလျောက် မြင်တွေ့ခွင့်ရရှိခြင်း။
- [x] **၅.၃ Member အဆင့်နှင့် Points (`/store/{slug}/admin/membership`)**
  - [x] Silver, Gold, VIP Member Tiers သတ်မှတ်ခြင်း (Spend threshold, Discount %, Point multiplier)။
  - [x] Member Points အတိုး/အလျော့ ပြင်ဆင်ခြင်းနှင့် Manual Tier Assignment။

---

### အုပ်စု ၆။ စက်ပြင်နှင့် ဝန်ဆောင်မှု (`Repairs & Service`)
- [x] **၆.၁ စက်ပြင် လုပ်ငန်းများ (`/store/{slug}/admin/repairs`)**
  - [x] Mobile/Device Repair Ticket ဖွင့်ခြင်း၊ ပစ္စည်းလက်ခံစလစ် ထုတ်ပေးခြင်း၊ Status အဆင့်ဆင့် ပြောင်းလဲခြင်း (Pending -> In Progress -> Ready -> Delivered/Cancelled)။
  - [x] Advance Payment (စရံငွေ) နှင့် Payment Ledger မှတ်တမ်းတင်ခြင်း။
  - [x] Public Tracking Token ဖြင့် Customer မှ Online မှ အခြေအနေ အချိန်နှင့်တစ်ပြေးညီ စစ်ဆေးနိုင်ခြင်း။
- [x] **၆.၂ ဝန်ဆောင်မှု စာရင်း (`/store/{slug}/admin/service-jobs`)**
  - [x] Computer / CCTV / Network ပြင်ဆင်မှု Service Jobs (SVC-YYYYMMDD-####) စီမံခန့်ခွဲမှု။
  - [x] Technician တာဝန်ပေးအပ်ခြင်း၊ ပြင်ဆင်ခ ဝန်ဆောင်ခ ဈေးနှုန်း သတ်မှတ်ခြင်း။
- [x] **၆.၃ စက်ပြင် အပိုပစ္စည်းများ (`/store/{slug}/admin/spare-parts`)**
  - [x] Repair စက်ပြင်ရာတွင် သုံးစွဲသည့် အပိုပစ္စည်း (Spare Parts) များကို Inventory Ledger မှ Auto-deduct စတော့ နှုတ်ယူမှု။
- [x] **၆.၄ စက်ပြင် ဆက်တင် (`/store/{slug}/admin/service-settings`)**
  - [x] Statuses, Brands, Categories, Models, Colors, Storage, Common Defects, Accessories Master Data Settings နှင့် CSV Import/Export။

---

### အုပ်စု ၇။ ငွေစာရင်းနှင့် ဘဏ္ဍာရေး (`Finance & Accounts`)
- [x] **၇.၁ ဖောက်သည် ရရန်ရှိ အကြွေး (`/store/{slug}/admin/receivables`)**
  - [x] Customer Debts စာရင်း၊ ငွေကောက်ခံမှတ်တမ်း၊ အကြွေးပြေစာ (A4 & Thermal 80mm) ထုတ်ပေးခြင်းနှင့် CSV Export။
- [x] **၇.၂ ကုန်သွင်းသူ ပေးရန်ရှိ အကြွေး (`/store/{slug}/pos/purchases/payables`)**
  - [x] Supplier Payables ရှင်းတမ်း၊ FIFO အကြွေးဆပ်ခြင်းနှင့် PDF/CSV Export။
- [x] **၇.၃ အရှုံး/အမြတ် ရှင်းတမ်း (`/store/{slug}/admin/profit-loss`)**
  - [x] Revenue - COGS - Expenses = Net Profit တွက်ချက်မှု တိကျခြင်း၊ Date Range Filter (ယနေ့၊ ယခုလ၊ စိတ်ကြိုက်)၊ Breakdown Waterfall Chart၊ Print Slip & Excel/CSV Export။
- [x] **၇.၄ ဆိုင်သုံး အသုံးစရိတ်များ (`/store/{slug}/admin/expenses`)**
  - [x] နေ့စဉ် ဆိုင်သုံးစရိတ် (မီးခ၊ လစာ၊ စားစရိတ်၊ သယ်ယူပို့ဆောင်ခ) CRUD၊ ဘောက်ချာ/ပြေစာ ဖိုင်တွဲတင်ခြင်းနှင့် CSV Export။
- [x] **၇.၅ စရိတ် အမျိုးအစားများ (`/store/{slug}/admin/expense-categories`)**
  - [x] Expense Category CRUD နှင့် Active Toggle ထိန်းချုပ်မှု။
- [x] **၇.၆ ဘဏ်/ငွေသား သွင်းထုတ်လွှဲ (`/store/{slug}/admin/transactions`)**
  - [x] Cash Drawer, KPay, Wave, Bank Accounts လက်ကျန်ငွေ၊ Deposit / Withdrawal / Account Transfer (with fee) နှင့် Printable Voucher။

---

### အုပ်စု ၈။ အစီရင်ခံစာနှင့် စာရင်းအင်း (`Reports & Analytics`)
- [x] **၈.၁ အရောင်း အစီရင်ခံစာ (`/store/{slug}/pos/reports/sales`)**
  - [x] နေ့အလိုက်/လအလိုက် အရောင်းစာရင်း၊ ကုန်ပစ္စည်းအလိုက် ရောင်းအား၊ Payment Breakdown နှင့် CSV/XLSX Export။
- [x] **၈.၂ အရောင်း ခွဲခြမ်းစိတ်ဖြာချက် (`/store/{slug}/admin/sales-analytics`)**
  - [x] Top Selling Products, Hourly Peak Distribution, Cashier Performance Leaderboard, Channel Analytics (POS vs Web Orders) နှင့် CSV Export။
- [x] **၈.၃ ငွေစာရင်း အစီရင်ခံစာ (`/store/{slug}/pos/reports/cash`)**
  - [x] Cash Drawer Shift Balance, Discrepancy & In/Out Movement Breakdown နှင့် CSV/XLSX Export။
- [x] **၈.၄ စတော့ လက်ကျန် အစီရင်ခံစာ (`/store/{slug}/pos/reports/stock`)**
  - [x] Available Stock, Low Stock, Out of Stock, Total Quantity at Cost & Retail Value နှင့် CSV/XLSX Export။
- [x] **၈.၅ စတော့ တန်ဖိုးတွက်ချက်မှု (`/store/{slug}/admin/inventory-valuation`)**
  - [x] Total Inventory Value at Cost vs Retail Value, Potential Profit, Category/Search Filter, Print Statement နှင့် CSV Export။
- [x] **၈.၆ အကြွေး သက်တမ်းစစ်တမ်း (`/store/{slug}/admin/debt-aging`)**
  - [x] FIFO Debt Aging Buckets (Current, 1-30, 31-60, 61-90, 90+ Days Overdue), Total Exposure Analysis, Print Statement နှင့် CSV Export။
- [x] **၈.၇ စက်ပြင် ဝန်ဆောင်မှု အစီရင်ခံစာ (`/store/{slug}/pos/reports/services`)**
  - [x] Repair Income, Completed Jobs, Pending Repairs, Spare Parts Cost Breakdown နှင့် CSV/XLSX Export။

---

### အုပ်စု ၉။ လုံခြုံရေးနှင့် ခွင့်ပြုချက် (`Security & Access`)
- [x] **၉.၁ ရာထူးနှင့် လုပ်ပိုင်ခွင့်များ (`/store/{slug}/admin/security/roles`)**
  - [x] Store Manager, Cashier, Accountant, Technician, Stock Keeper Built-in Roles & Auto-bootstrap။
  - [x] Custom Roles CRUD, Granular Permission Matrix (POS, Inventory, Finance, Service, Reports, Settings) နှင့် CSV Export။
- [x] **၉.၂ အကောင့်နှင့် ဝန်ထမ်းများ (`/store/{slug}/admin/users`)**
  - [x] Staff Accounts CRUD, PIN Code, Assigned Roles & Branch Access Control။
- [x] **၉.၃ စနစ် လုပ်ဆောင်ချက် မှတ်တမ်း (`/store/{slug}/admin/security/audit-logs`)**
  - [x] Pricing, Sales, Stock Adjustments, Cash Withdrawals, Daily Closings, Role Changes & Security Events Audit Trail။
  - [x] Action Category Filters, User/Date Filters, IP Address Tracking နှင့် CSV Export။

---

### အုပ်စု ၁၀။ စနစ် ထိန်းသိမ်းရေး (`System Maintenance`)
- [x] **၁၀.၁ စနစ် သတိပေးချက် ဗဟို (`/store/{slug}/admin/alerts`)**
  - [x] Low Stock, Overdue Debt, Security Alerts & Telegram Bot Briefing။
- [x] **၁၀.၂ ဒေတာဘေ့စ် ထိန်းသိမ်းရေး (`/store/{slug}/admin/database`)**
  - [x] Database Vacuum, Table Optimization, PRAGMA Integrity Check, Cache Clear။
- [x] **၁၀.၃ ဒေတာ အရန်သိမ်း/ပြန်တင်ခြင်း (`/store/{slug}/admin/backups`)**
  - [x] One-Click Database & File Backup (.sql Universal / .sqlite Snapshot), Restore Wizard & Audit Log။
- [x] **၁၀.၄ ဒေတာ အစမ်းသွင်းခြင်း (`/store/{slug}/admin/pilot-import`)**
  - [x] Pilot Batch Excel / CSV Data Ingestion Wizard, Dry-Run Preview, Myanmar SME Demo Presets Seeder & Safe Data Reset။
- [x] **၁၀.၅ တင်သွင်းမှု မှတ်တမ်း (`/store/{slug}/admin/import-history`)**
  - [x] Bulk Import Jobs & Error Log Review, Type/Search Filtering, Error CSV Download & Audit Logging။

---

### အုပ်စု ၁၁။ ဆိုင် ဆက်တင်များ (`Business Setup`)
- [x] **၁၁.၁ အထွေထွေ ဆက်တင် (`/store/{slug}/admin/settings`)**
  - [x] ဆိုင်အမည်၊ ဖုန်း၊ လိပ်စာ၊ Logo၊ Currency & Accounting format ဆက်တင်များ။
- [x] **၁၁.၂ လိပ်စာနှင့် ဆက်သွယ်ရန် (`/store/{slug}/admin/settings/contact`)**
  - [x] Viber, Facebook, Telegram, Map Embed နှင့် Floating Chat Button ဆက်တင်များ။
- [x] **၁၁.၃ ပို့ဆောင်ခနှင့် ငွေပေးချေမှု (`/store/{slug}/admin/settings/delivery`)**
  - [x] မြို့နယ်အလိုက် ပို့ဆောင်ခ (Delivery Methods) နှင့် Payment Accounts (KPay QR)။
- [x] **၁၁.၄ မှာယူနည်း လမ်းညွှန် (`/store/{slug}/admin/settings/how-to-order`)**
  - [x] Online မှာယူမှု အဆင့်ဆင့် လမ်းညွှန်ချက် စာမျက်နှာနှင့် Video Tutorial Embed။
- [x] **၁၁.၅ ဆိုင်ခွဲများ စီမံခြင်း (`/store/{slug}/admin/branches`)**
  - [x] Outlets, Default Branch, Branch Assigned Warehouses။
- [x] **၁၁.၆ ဘောက်ချာ ပရင်တာများ (`/store/{slug}/admin/printers`)**
  - [x] 58mm/80mm Thermal, LAN IP, Bluetooth, Auto Cutter, Drawer Kick။
- [x] **၁၁.၇ ဘောက်ချာ ဒီဇိုင်း စိတ်ကြိုက်ပြင် (`/store/{slug}/admin/vouchers`)**
  - [x] 80mm, 58mm, A4, A5 Layouts, Payment QR, Policy Footer Customizer။
- [x] **၁၁.၈ နိုင်ငံခြား ငွေလဲနှုန်းများ (`/store/{slug}/admin/exchange-rates`)**
  - [x] USD, THB, CNY နေ့စဉ်ငွေလဲနှုန်းနှင့် Landed Cost Calculator။

---

## အပိုင်း ၃။ စာမျက်နှာတိုင်းတွင် မဖြစ်မနေ စစ်ဆေးရမည့် အချက်များ

စာမျက်နှာတစ်ခုချင်းစီကို Audit စစ်ဆေးရာတွင် အောက်ပါ စံသတ်မှတ်ချက် (၆) ချက်ဖြင့် တညီတညွတ်တည်း စစ်ဆေးပါမည်:

1. **🎨 UI/UX & Layout Integrity**:
   - အချိုးအစား မညီခြင်း၊ စာလုံးထပ်ခြင်း မရှိစေရ။
   - Dark Mode / Light Mode နှစ်မျိုးစလုံးတွင် စာသားများ ရှင်းလင်းစွာ ဖတ်ရှုနိုင်ရမည်။
   - Mobile / Tablet / Desktop မျက်နှာပြင် အားလုံးတွင် အဆင်ပြေရမည်။
2. **🇲🇲 Myanmar Unicode Typography**:
   - မြန်မာစာ စာလုံးကျိုးခြင်း၊ အောက်ကမြစ်နှင့် သဝေထိုး လွဲမှားခြင်း မရှိရ။
   - အင်္ဂလိပ်/မြန်မာ ဝေါဟာရ ရောထွေးမှု သဘာဝကျရမည်။
3. **⚡ Alpine.js & Interactivity**:
   - Modal များ ဖွင့်/ပိတ် လျင်မြန်စွာ အလုပ်လုပ်ရမည်။
   - Form Submit နှိပ်ချိန်တွင် Button Disable ဖြစ်ပြီး Double Submit မဖြစ်စေရ။
4. **🛡️ Security & Validation**:
   - CSRF Token ပါဝင်ရမည်။
   - Form များတွင် မမှန်ကန်သော Input ထည့်ပါက မြန်မာလို Error Message ရှင်းလင်းစွာ ပြရမည်။
   - ဖျက်သိမ်းခြင်း (Delete) လုပ်ဆောင်ချက်တိုင်းတွင် Confirmation Alert တောင်းရမည်။
5. **🗄️ Database & Query Performance**:
   - N+1 Query ပြဿနာ မရှိစေရ။
   - Empty State (ဒေတာ မရှိသေးသည့် အခြေအနေ) တွင် ကောင်းမွန်သော ရှင်းလင်းချက်နှင့် ခလုတ် ပြသရမည်။
6. **🖨️ Hardware & Thermal Slip Compliance**:
   - Print ထုတ်ရသော စာမျက်နှာများတွင် Browser Print Preview သေသပ်ရမည်။

---

## အပိုင်း ၄။ အဆင့်လိုက် စမ်းသပ်ပြုပြင်မည့် အစီအစဉ် (Phased Execution Plan)

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                        DataPOS Step-by-Step Audit Plan                           │
├──────────────────────────────────────────────────────────────────────────────────┤
│  🧹 Step 1: File Cleanup & Code Sanitation (မလိုအပ်သော အပိုဖိုင်များ ရှင်းထုတ်ခြင်း)  │
│  🔍 Step 2: High-Priority Core Modules Audit (POS, Inventory, Finance, Setup)    │
│  📱 Step 3: Ecommerce & CRM Modules Audit (Orders, Wholesale, Membership)        │
│  📊 Step 4: Analytics, Security & Maintenance Audit (Alerts, Database, Roles)    │
│  🚀 Step 5: Final Production Build, Cache Optimization & Release Sign-off        │
└──────────────────────────────────────────────────────────────────────────────────┘
```

---
*Created with Tech Buddy for DataPOS Production Launch Quality Assurance.*
