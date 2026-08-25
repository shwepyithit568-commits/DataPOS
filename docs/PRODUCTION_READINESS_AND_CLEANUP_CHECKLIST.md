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
- [ ] **၃.၁ ကုန်သွင်းသူများ (`/store/{slug}/admin/suppliers`)**
  - [ ] Supplier ကုမ္ပဏီအမည်၊ ဖုန်း၊ လိပ်စာ၊ ဘဏ်အကောင့် မှတ်တမ်း။
- [ ] **၃.၂ အဝယ် အမှာစာ (`/store/{slug}/pos/purchases`)**
  - [ ] Purchase Order (PO) ဖွင့်ခြင်း၊ ပစ္စည်းလက်ခံခြင်း (GRN) နှင့် စတော့ တိုးခြင်း။
- [ ] **၃.၃ အဝယ် ပစ္စည်းပြန်ပို့ (`/store/{slug}/pos/purchases/returns`)**
  - [ ] ပျက်စီး/မှားယွင်းသော ပစ္စည်းများ Supplier ထံ ပြန်ပို့ပြီး ငွေ/စတော့ နှုတ်ခြင်း။
- [ ] **၃.၄ ကုန်သွင်းသူ ပေးရန်ရှိ အကြွေး (`/store/{slug}/pos/purchases/payables`)**
  - [ ] Supplier အကြွေးစာရင်းနှင့် ကျန်ငွေဆပ်ခြင်း မှတ်တမ်း။
- [ ] **၃.၅ ပစ္စည်း လွှဲပြောင်းခြင်း (`/store/{slug}/pos/transfers`)**
  - [ ] ဆိုင်ခွဲအချင်းချင်း သို့မဟုတ် ဂိုဒေါင်မှ ဆိုင်သို့ စတော့ လွှဲပြောင်းခြင်း။
- [ ] **၃.၆ ဂိုဒေါင်များ စီမံခြင်း (`/store/{slug}/admin/warehouses`)**
  - [ ] ပင်မဂိုဒေါင်၊ ဆိုင်ရှေ့စတော့၊ ဆိုင်ခွဲဂိုဒေါင်များ ခွဲခြားခြင်း။

---

### အုပ်စု ၄။ အွန်လိုင်း Storefront (`Ecommerce Storefront`)
- [ ] **၄.၁ အမှာစာများ (`/store/{slug}/admin/orders`)**
  - [ ] Online Orders စာရင်း၊ Status ပြောင်းလဲခြင်း (Pending, Confirmed, Shipped, Delivered)။
  - [ ] Invoice View, Print, Delete လုပ်ဆောင်ချက်များ။
- [ ] **၄.၂ အွန်လိုင်း ပစ္စည်းပြသမှု (`/store/{slug}/admin/web-products`)**
  - [ ] Website တွင် ပြသမည့် ပစ္စည်းများနှင့် Counter Only ပစ္စည်းများ ခွဲထုတ်ထိန်းချုပ်မှု။
- [ ] **၄.၃ ပရိုမိုးရှင်းနှင့် Coupon (`/store/{slug}/admin/promotions`)**
  - [ ] Coupon Code, Percentage Off, Fixed Discount, BOGO သတ်မှတ်ခြင်း။
- [ ] **၄.၄ ကုန်ပစ္စည်း မှတ်ချက်များ (`/store/{slug}/admin/reviews`)**
  - [ ] Customer Star Ratings & Reviews စစ်ဆေးအတည်ပြုခြင်း။
- [ ] **၄.၅ ပင်မ စာမျက်နှာ Banner များ (`/store/{slug}/admin/banners`)**
  - [ ] Promo Banners, Slider Images တင်ခြင်းနှင့် Link ချိတ်ခြင်း။
- [ ] **၄.၆ ဆောင်းပါးများ (`/store/{slug}/admin/blog`)**
  - [ ] Tech Articles, Tips & Guides ရေးသားတင်ပြခြင်း။
- [ ] **၄.၇ မှန်ကပ် ရှာဖွေရန် (`/store/{slug}/admin/glass-finder`)**
  - [ ] ဖုန်း Brand / Model အလိုက် သင့်တော်သော မှန်ကပ် စာရင်း ရှာဖွေမှု။
- [ ] **၄.၈ Web Push သတိပေးချက်များ (`/store/{slug}/admin/push`)**
  - [ ] Promotion Push Notifications ပို့ခြင်းနှင့် မှတ်တမ်းကြည့်ခြင်း။

---

### အုပ်စု ၅။ ဖောက်သည်နှင့် Member (`Customers & CRM`)
- [ ] **၅.၁ ဖောက်သည် စာရင်း (`/store/{slug}/admin/customers`)**
  - [ ] Customer CRUD, ဖုန်းနံပါတ်၊ ဝယ်ယူမှုသမိုင်း၊ အကြွေးကျန်ငွေ Profile ကြည့်ရှုခြင်း။
- [ ] **၅.၂ လက်ကား လျှောက်လွှာများ (`/store/{slug}/admin/wholesale/applications`)**
  - [ ] B2B Wholesale Customer လျှောက်လွှာများ Review/Approve/Reject ပြုလုပ်ခြင်း။
- [ ] **၅.၃ Member အဆင့်နှင့် Points (`/store/{slug}/admin/membership`)**
  - [ ] Silver, Gold, VIP Member Tiers သတ်မှတ်ခြင်း၊ Points အတိုး/အလျော့ ပြင်ဆင်ခြင်း။

---

### အုပ်စု ၆။ စက်ပြင်နှင့် ဝန်ဆောင်မှု (`Repairs & Service`)
- [ ] **၆.၁ စက်ပြင် လုပ်ငန်းများ (`/store/{slug}/admin/repairs`)**
  - [ ] Repair Ticket ဖွင့်ခြင်း၊ ပစ္စည်းလက်ခံစလစ် ထုတ်ပေးခြင်း၊ Status အဆင့်ဆင့် ပြောင်းလဲခြင်း။
- [ ] **၆.၂ ဝန်ဆောင်မှု စာရင်း (`/store/{slug}/admin/service-jobs`)**
  - [ ] ဆားဗစ်အမျိုးအစားနှင့် ဝန်ဆောင်ခ ဈေးနှုန်း သတ်မှတ်ခြင်း။
- [ ] **၆.၃ စက်ပြင် အပိုပစ္စည်းများ (`/store/{slug}/admin/spare-parts`)**
  - [ ] Repair စက်ပြင်ရာတွင် သုံးစွဲသည့် အပိုပစ္စည်း စတော့ ထိန်းချုပ်မှု။
- [ ] **၆.၄ စက်ပြင် ဆက်တင် (`/store/{slug}/admin/service-settings`)**
  - [ ] စက်ပြင် စည်းမျဉ်းများ၊ အာမခံကာလ သတ်မှတ်ချက်များ။

---

### အုပ်စု ၇။ ငွေစာရင်းနှင့် ဘဏ္ဍာရေး (`Finance & Accounts`)
- [ ] **၇.၁ ဖောက်သည် ရရန်ရှိ အကြွေး (`/store/{slug}/admin/receivables`)**
  - [ ] Customer Debts စာရင်း၊ ငွေကောက်ခံမှတ်တမ်း၊ အကြွေးပြေစာ ထုတ်ပေးခြင်း။
- [ ] **၇.၂ ကုန်သွင်းသူ ပေးရန်ရှိ အကြွေး (`/store/{slug}/pos/purchases/payables`)**
  - [ ] Supplier Payables ရှင်းတမ်း။
- [ ] **၇.၃ အရှုံး/အမြတ် ရှင်းတမ်း (`/store/{slug}/admin/profit-loss`)**
  - [ ] Revenue - COGS - Expenses = Net Profit တွက်ချက်မှု တိကျခြင်း။
- [ ] **၇.၄ ဆိုင်သုံး အသုံးစရိတ်များ (`/store/{slug}/admin/expenses`)**
  - [ ] နေ့စဉ် ဆိုင်သုံးစရိတ် (မီးခ၊ လစာ၊ စားစရိတ်၊ သယ်ယူပို့ဆောင်ခ) မှတ်တမ်း။
- [ ] **၇.၅ စရိတ် အမျိုးအစားများ (`/store/{slug}/admin/expense-categories`)**
  - [ ] Expense Category CRUD။
- [ ] **၇.၆ ဘဏ်/ငွေသား သွင်းထုတ်လွှဲ (`/store/{slug}/admin/transactions`)**
  - [ ] Cash Drawer, KPay, Wave, Bank Accounts လက်ကျန်ငွေနှင့် Transfer မှတ်တမ်း။

---

### အုပ်စု ၈။ အစီရင်ခံစာနှင့် စာရင်းအင်း (`Reports & Analytics`)
- [ ] **၈.၁ အရောင်း အစီရင်ခံစာ (`/store/{slug}/pos/reports/sales`)**
  - [ ] နေ့အလိုက်/လအလိုက် အရောင်းစာရင်းနှင့် ကုန်ပစ္စည်းအလိုက် ရောင်းအား။
- [ ] **၈.၂ အရောင်း ခွဲခြမ်းစိတ်ဖြာချက် (`/store/{slug}/admin/sales-analytics`)**
  - [ ] Top Selling Products, Peak Hours, Cashier Performance Graphs။
- [ ] **၈.၃ ငွေစာရင်း အစီရင်ခံစာ (`/store/{slug}/pos/reports/cash`)**
  - [ ] Cash Drawer Shift Report & Payment Breakdown။
- [ ] **၈.၄ စတော့ လက်ကျန် အစီရင်ခံစာ (`/store/{slug}/pos/reports/stock`)**
  - [ ] Available Stock, Low Stock, Out of Stock ပစ္စည်းများ။
- [ ] **၈.၅ စတော့ တန်ဖိုးတွက်ချက်မှု (`/store/{slug}/admin/inventory-valuation`)**
  - [ ] Total Inventory Value at Cost vs Retail Value။
- [ ] **၈.၆ အကြွေး သက်တမ်းစစ်တမ်း (`/store/{slug}/admin/debt-aging`)**
  - [ ] 1-30, 31-60, 61-90, 90+ Days Overdue Debts Analysis။
- [ ] **၈.၇ စက်ပြင် ဝန်ဆောင်မှု အစီရင်ခံစာ (`/store/{slug}/pos/reports/services`)**
  - [ ] Repair Income, Completed Jobs, Pending Repairs စာရင်း။

---

### အုပ်စု ၉။ လုံခြုံရေးနှင့် ခွင့်ပြုချက် (`Security & Access`)
- [ ] **၉.၁ ရာထူးနှင့် လုပ်ပိုင်ခွင့်များ (`/store/{slug}/admin/security/roles`)**
  - [ ] Manager, Cashier, Stock Keeper Roles & Granular Permission Matrix။
- [ ] **၉.၂ စနစ် လုပ်ဆောင်ချက် မှတ်တမ်း (`/store/{slug}/admin/security/audit-logs`)**
  - [ ] Price Change, Stock Void, Role Update, Cash Withdrawal Audit Trail။

---

### အုပ်စု ၁၀။ စနစ် ထိန်းသိမ်းရေး (`System Maintenance`)
- [ ] **၁၀.၁ စနစ် သတိပေးချက် ဗဟို (`/store/{slug}/admin/alerts`)**
  - [ ] Low Stock, Overdue Debt, Security Alerts & Telegram Bot Briefing။
- [ ] **၁၀.၂ ဒေတာဘေ့စ် ထိန်းသိမ်းရေး (`/store/{slug}/admin/database`)**
  - [ ] Database Vacuum, Table Optimization, PRAGMA Integrity Check, Cache Clear။
- [ ] **၁၀.၃ ဒေတာ အရန်သိမ်းခြင်း (`/store/{slug}/admin/backups`)**
  - [ ] One-Click Database & File Backup Download (.sqlite / .sql / .zip)။
- [ ] **၁၀.၄ ဒေတာ အစမ်းသွင်းခြင်း (`/store/{slug}/admin/pilot-import`)**
  - [ ] Pilot Batch Excel / CSV Data Ingestion Wizard။
- [ ] **၁၀.၅ တင်သွင်းမှု မှတ်တမ်း (`/store/{slug}/admin/import-history`)**
  - [ ] Bulk Import Jobs & Error Log Review။

---

### အုပ်စု ၁၁။ ဆိုင် ဆက်တင်များ (`Business Setup`)
- [ ] **၁၁.၁ အထွေထွေ ဆက်တင် (`/store/{slug}/admin/settings`)**
  - [ ] ဆိုင်အမည်၊ ဖုန်း၊ လိပ်စာ၊ Logo၊ Currency ဆက်တင်များ။
- [ ] **၁၁.၂ လိပ်စာနှင့် ဆက်သွယ်ရန် (`/store/{slug}/admin/settings/contact`)**
  - [ ] Viber, Facebook, Telegram, Map Embed ဆက်တင်များ။
- [ ] **၁၁.၃ ပို့ဆောင်ခနှင့် ငွေပေးချေမှု (`/store/{slug}/admin/settings/delivery`)**
  - [ ] မြို့နယ်အလိုက် ပို့ဆောင်ခနှင့် Payment Accounts (KPay QR)။
- [ ] **၁၁.၄ မှာယူနည်း လမ်းညွှန် (`/store/{slug}/admin/settings/how-to-order`)**
  - [ ] Online မှာယူမှု အဆင့်ဆင့် လမ်းညွှန်ချက် စာမျက်နှာ။
- [ ] **၁၁.၅ ဆိုင်ခွဲများ စီမံခြင်း (`/store/{slug}/admin/branches`)**
  - [ ] Outlets, Default Branch, Branch Assigned Warehouses။
- [ ] **၁၁.၆ ဘောက်ချာ ပရင်တာများ (`/store/{slug}/admin/printers`)**
  - [ ] 58mm/80mm Thermal, LAN IP, Bluetooth, Auto Cutter, Drawer Kick။
- [ ] **၁၁.၇ ဘောက်ချာ ဒီဇိုင်း စိတ်ကြိုက်ပြင် (`/store/{slug}/admin/vouchers`)**
  - [ ] 80mm, 58mm, A4, A5 Layouts, Payment QR, Policy Footer Customizer။
- [ ] **၁၁.၈ နိုင်ငံခြား ငွေလဲနှုန်းများ (`/store/{slug}/admin/exchange-rates`)**
  - [ ] USD, THB, CNY နေ့စဉ်ငွေလဲနှုန်းနှင့် Landed Cost Calculator။
- [ ] **၁၁.၉ အကောင့်နှင့် ဝန်ထမ်းများ (`/store/{slug}/admin/users`)**
  - [ ] Staff Accounts CRUD, PIN Code, Assigned Roles & Branches။

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
