# DataPOS — Admin Panel မပြီးသေးသော Modules များ အဆင့်လိုက် တည်ဆောက်ရေး မာစတာ လမ်းညွှန် (Master Execution Roadmap)
**Document Version:** 1.0.0  
**Target:** DataPOS Admin Sidebar Completion & Full Module Delivery  
**System Base:** Laravel 11 + Alpine.js + Tailwind CSS + Livewire + Offline-First SQLite/MySQL

---

## ၁။ ခြုံငုံသုံးသပ်ချက် (Executive Overview)

DataPOS Admin Sidebar တွင် လက်ရှိအသုံးပြုနိုင်သော Core Modules (၂၈) ခု အသင့်ရှိနေပြီး၊ လက်ရှိတွင် `coming-soon` (Placeholder) အဖြစ် သတ်မှတ်ထားသော Modules (၂၂) ခု ကျန်ရှိပါသည်။ 

လုပ်ငန်းရှင်များ လက်တွေ့အသုံးချနိုင်မှု၊ ဈေးကွက်တန်ဖိုးနှင့် စနစ်ကြံ့ခိုင်မှုအပေါ် မူတည်၍ ၎င်းကျန်ရှိသော Modules များကို **အဆင့် (၅) ဆင့်** ဖြင့် စနစ်တကျ အကောင်အထည်ဖော် တည်ဆောက်သွားပါမည်:

```
┌────────────────────────────────────────────────────────────────────────┐
│               DataPOS Admin Modules Execution Hierarchy                │
├────────────────────────────────────────────────────────────────────────┤
│  ⭐ Phase 1: High-Impact Retail Essentials (မရှိမဖြစ် နေ့စဉ်သုံး စနစ်များ)    │
│  📦 Phase 2: Advanced Inventory & Operations (စတော့နှင့် စျေးနှုန်း ထိန်းချုပ်မှု) │
│  🖨️ Phase 3: Hardware, Vouchers & Store Settings (စက်နှင့် ဘောက်ချာစနစ်)  │
│  🎁 Phase 4: Customer Loyalty, Promotions & E-load (ပရိုမိုးရှင်းစနစ်)   │
│  📊 Phase 5: Deep Analytics, Security & Governance (လုံခြုံရေးနှင့် စာရင်း) │
└────────────────────────────────────────────────────────────────────────┘
```

---

## ၂။ အဆင့်လိုက် အကောင်အထည်ဖော်မည့် အသေးစိတ် စာရင်း (Phased Implementation Plan)

---

### ⭐ Phase 1: High-Impact Retail Essentials (မရှိမဖြစ် နေ့စဉ်သုံး အဓိက Modules)
*မြန်မာ့ဈေးကွက်တွင် အရောင်းပိတ်ရန်နှင့် နေ့စဉ်လုပ်ငန်းလည်ပတ်ရန် မရှိမဖြစ် လိုအပ်သော အပိုင်းများ ဖြစ်သည်။*

| Module အမည် | Sidebar တည်နေရာ | အဓိက လုပ်ဆောင်ချက်နှင့် စနစ်ဖွဲ့စည်းပုံ | Priority |
| :--- | :--- | :--- | :--- |
| ✅**၁။ Customer Receivables & Debt Ledger**<br>(`sidebar_receivables`) | `Finance` အောက် | • ဖောက်သည် အကြွေးစာရင်းနှင့် ကျန်ငွေမှတ်တမ်း<br>• အကြွေးဆပ်ငွေသွင်းခြင်း (Partial / Full Payment)<br>• အကြွေးပြေစာ ဘောက်ချာ ထုတ်ပေးခြင်း<br>• အကြွေးများသော ဖောက်သည် Alert | 🔥 **အရေးအကြီးဆုံး (P0)** |
| ✅**၂။ Barcode & QR Label Printing**<br>(`sidebar_barcode`) | `Inventory` အောက် | • ကုန်ပစ္စည်းများ ရွေးချယ်ပြီး စတစ်ကာ Print ထုတ်ခြင်း<br>• 50x30mm, 40x30mm Thermal Sticker & A4 layout<br>• စျေးနှုန်း၊ ဘားကုဒ်၊ ဆိုင်အမည်၊ ကုန်ပစ္စည်းအမည် စိတ်ကြိုက်ထည့်သွင်းမှု | 🔥 **အရေးအကြီးဆုံး (P0)** |
| ✅**၃။ Profit & Loss Financial Statement**<br>(`sidebar_profit_loss`) | `Finance` အောက် | • နေ့စဉ်/လစဉ်/နှစ်စဉ် အရှုံးအမြတ် ရှင်းတမ်း<br>• စုစုပေါင်းအရောင်း (Revenue) - ကုန်ကျစရိတ် (COGS) = စုစုပေါင်းအမြတ် (Gross Profit)<br>• ဆိုင်လည်ပတ်စရိတ်များ (Expenses) နှုတ်ပြီး အသားတင်အမြတ် (Net Profit) ပြသမှု | 🔥 **အရေးအကြီးဆုံး (P0)** |
| ✅**၄။ Warranty & Serial / IMEI Tracker**<br>(`sidebar_warranty`) | `Inventory` အောက် | • ရောင်းချပြီး ပစ္စည်းများ၏ Serial / IMEI ဖြင့် အာမခံကာလ စစ်ဆေးခြင်း<br>• အာမခံသက်တမ်း ကုန်ဆုံးရက် တွက်ချက်မှု<br>• ပြင်ဆင်မှု မှတ်တမ်း (Service History) ချိတ်ဆက်ပြသခြင်း | ⚡ **P1** |

---

### 📦 Phase 2: Advanced Inventory & Operations (စတော့နှင့် စျေးနှုန်း ထိန်းချုပ်မှု)
*စတော့စာရင်းတိကျမှုနှင့် စျေးနှုန်းအပြောင်းအလဲများကို အချိန်တိုအတွင်း ထိန်းချုပ်နိုင်သော အပိုင်းများ ဖြစ်သည်။*

| Module အမည် | Sidebar တည်နေရာ | အဓိက လုပ်ဆောင်ချက်နှင့် စနစ်ဖွဲ့စည်းပုံ | Priority |
| :--- | :--- | :--- | :--- |
| ✅**၅။ Stock Ledger & Bin Cards**<br>(`sidebar_stock_ledger`) | `Inventory` အောက် | • ပစ္စည်းတစ်ခုချင်းစီ၏ အဝင်၊ အထွက်၊ အရောင်း၊ အဝယ်၊ လွှဲပြောင်းမှု သမိုင်းကြောင်း (Timeline Audit Trail)<br>• Stock Movement တစ်ခုချင်းစီ၏ Reference Document (Invoice/Purchase/Adjustment ID) ပြသခြင်း | ⚡ **P1** |
| ✅**၆။ Physical Stock Count & Audit**<br>(`sidebar_stock_count`) | `Inventory` အောက် | • လစဉ် စတော့စစ်ဆေးခြင်း (Stock Take Sheet)<br>• Barcode Scanner ဖြင့် အစစ်အမှန် စတော့ကောင်ရေ ရိုက်ထည့်ခြင်း<br>• System စတော့နှင့် လက်တွေ့စတော့ ကွာဟချက် (Discrepancy) ကို အလိုအလျောက် ညှိပေးခြင်း | ⚡ **P1** |
| ✅**၇။ Bulk Price Wizard**<br>(`sidebar_price_wizard`) | `Inventory` အောက် | • ကုန်ပစ္စည်း အများအပြား၏ လက်လီ/လက်ကား စျေးနှုန်းကို တစ်ပြိုင်နက် ပြင်ဆင်ခြင်း<br>• အမြတ် % (Markup / Margin %) အလိုက် စျေးနှုန်း အလိုအလျောက် တွက်ချက်တင်ပေးခြင်း | ⚡ **P1** |
| ✅**၈။ Cash & Bank Transactions Register**<br>(`sidebar_transactions`) | `Finance` အောက် | • ကောင်တာငွေသား (Cash in Hand) နှင့် ဘဏ်စာရင်း (KPay, Wave, KBZ, CB, AYA)<br>• စာရင်းအချင်းချင်း ငွေလွှဲပြောင်းမှု (Fund Transfers) နှင့် လက်ကျန်ငွေ ရှင်းတမ်း | ⚡ **P1** |

---

### 🖨️ Phase 3: Hardware, Vouchers & Multi-Location Setup (စက်ကိရိယာနှင့် ဆိုင်ခွဲများ)
*Hardware ချိတ်ဆက်မှုနှင့် ဆိုင်အသွင်အပြင် ဘောက်ချာဒီဇိုင်းများ စိတ်ကြိုက်ပြင်ဆင်နိုင်သော အပိုင်းများ ဖြစ်သည်။*

| Module အမည် | Sidebar တည်နေရာ | အဓိက လုပ်ဆောင်ချက်နှင့် စနစ်ဖွဲ့စည်းပုံ | Priority |
| :--- | :--- | :--- | :--- |
| ✅**၉။ Printer Setup & Direct Printing**<br>(`sidebar_printers`) | `Setup` အောက် | • 58mm / 80mm Thermal Receipt Printers Setting<br>• USB, Bluetooth, LAN / Network IP Printer ချိတ်ဆက်မှု<br>• Print Copies, Auto Cutter, Cash Drawer Kick အဖွင့်/အပိတ် | ⚡ **P1** |
| ✅**၁၀။ Voucher Customizer & Templates**<br>(`sidebar_vouchers`) | `Setup` အောက် | • ဘောက်ချာခေါင်းစဉ်၊ ဆိုင် Logo၊ ဖုန်းနံပါတ်၊ လိပ်စာ၊ ကျေးဇူးတင်စကား စိတ်ကြိုက်ပြင်ဆင်ခြင်း<br>• KPay / Wave QR Code ဘောက်ချာတွင် ထည့်သွင်းပြသခြင်း<br>• A4, A5, 80mm, 58mm Voucher Templates | ⚡ **P1** |
| ✅**၁၁။ Multi-Branch Management**<br>(`sidebar_branches`) | `Setup` အောက် | • ဆိုင်ခွဲများ (Branch 1, Branch 2) သတ်မှတ်ခြင်း<br>• ဆိုင်ခွဲအလိုက် သီးခြား POS စာရင်းနှင့် Stock ခွဲဝေမှု | 🔹 **P2** |
| ✅**၁၂။ Currency Exchange Rates**<br>(`sidebar_exchange_rates`) | `Setup` အောက် | • MMK, USD, THB (ဘတ်), CNY (ယွမ်) နေ့စဉ် ငွေလဲနှုန်းများ<br>• နိုင်ငံခြားငွေဖြင့် ဝင်လာသော ပစ္စည်းများအား မြန်မာကျပ်ငွေသို့ အလိုအလျောက် ဈေးပြောင်းလဲပေးခြင်း | 🔹 **P2** |

---

### 🎁 Phase 4: Customer Loyalty, Promotions & E-load (အရောင်းမြှင့်တင်ရေး စနစ်များ)
*ဖောက်သည်များ ပြန်လည်လာရောက်စေရန်နှင့် အရောင်းအားကောင်းစေရန် အထောက်အကူပြုသော အပိုင်းများ ဖြစ်သည်။*

| Module အမည် | Sidebar တည်နေရာ | အဓိက လုပ်ဆောင်ချက်နှင့် စနစ်ဖွဲ့စည်းပုံ | Priority |
| :--- | :--- | :--- | :--- |
| ✅**၁၃။ Membership Tier & Loyalty Points**<br>(`sidebar_membership`) | `Customers` အောက် | • ဖောက်သည် အဆင့်များ (Silver, Gold, VIP Member)<br>• ဝယ်ယူမှုပမာဏအလိုက် Points ပေးခြင်းနှင့် Points ဖြင့် ငွေလျှော့ဝယ်ယူနိုင်ခြင်း | 🔹 **P2** |
| ✅**၁၄။ Promotions & Coupon Engine**<br>(`sidebar_promotions`) | `Ecommerce` အောက် | • Coupon Code လျှော့စျေးများ (ဥပမာ: `THADINGYUT10` - 10% Off)<br>• Buy 1 Get 1 (တစ်ခုဝယ် တစ်ခုလက်ဆောင်) နှင့် အထူးလျှော့စျေး သတ်မှတ်ခြင်း | 🔹 **P2** |
| ✅**၁၅။ Web Catalog Product Visibility**<br>(`sidebar_web_products`) | `Ecommerce` အောက် | • Online Storefront တွင် ပြသမည့် ပစ္စည်းများနှင့် ဆိုင်တွင်းသာ ရောင်းမည့် ပစ္စည်းများ သီးသန့် ခွဲထုတ်ထိန်းချုပ်ခြင်း | 🔹 **P2** |
| ✅**၁၆။ Mobile E-Load & Bill Register**<br>(`sidebar_eload`) | `POS` အောက် | • MPT, Atom, Ooredoo, Mytel ဖုန်းဘေလ်ဖြည့်ခြင်း မှတ်တမ်း<br>• E-Load ဝယ်စျေး/ရောင်းစျေးနှင့် အမြတ်ငွေ အလိုအလျောက် တွက်ချက်မှု | 🔹 **P2** |

---

### 📊 Phase 5: Deep Analytics, Security & Maintenance (လုံခြုံရေး၊ စာရင်းအင်းနှင့် ထိန်းသိမ်းမှု)
*လုပ်ငန်းကြီးများအတွက် မရှိမဖြစ်လိုအပ်သော အသေးစိတ် အစီရင်ခံစာများနှင့် ဝန်ထမ်းအခွင့်အရေး ကန့်သတ်ချက်များ ဖြစ်သည်။*

| Module အမည် | Sidebar တည်နေရာ | အဓိက လုပ်ဆောင်ချက်နှင့် စနစ်ဖွဲ့စည်းပုံ | Priority |
| :--- | :--- | :--- | :--- |
| ✅**၁၇။ Sales Analytics & Deep Charts**<br>(`sidebar_sales_analytics`) | `Reports` အောက် | • ရောင်းအားအကောင်းဆုံး ပစ္စည်းများ (Top Selling Items)<br>• နာရီအလိုက်/နေ့အလိုက် ရောင်းအားဂရပ်များ (Peak Sales Hours)<br>• Cashier တစ်ဦးချင်းစီ၏ စွမ်းဆောင်ရည် | 🔹 **P2** |
|**၁၈။ Inventory Valuation Report**<br>(`sidebar_inventory_valuation`) | `Reports` အောက် | • စတော့လက်ကျန် စုစုပေါင်း၏ ကုန်ကျစရိတ်တန်ဖိုး (Total Inventory Value at Cost)<br>• ရောင်းရငွေတန်ဖိုး (Total Value at Retail Price) နှင့် ခန့်မှန်းအမြတ်ငွေ | 🔹 **P2** |
| **၁၉။ Debt Aging Analysis Report**<br>(`sidebar_aging_report`) | `Reports` အောက် | • အကြွေးသက်တမ်း ခွဲခြားမှု (၁-၃၀ ရက်၊ ၃၁-၆၀ ရက်၊ ၆၁-၉၀ ရက်၊ ရက် ၉၀ အထက်)<br>• မဆပ်ဘဲ ကြာမြင့်နေသော အန္တရာယ်ရှိ အကြွေးစာရင်းများ သတိပေးခြင်း | 🔹 **P2** |
| **၂၀။ Staff Roles & Granular Permissions**<br>(`sidebar_roles`) | `Security` အောက် | • မန်နေဂျာ၊ စာရင်းကိုင်၊ အရောင်းစာရေး (Cashier) ရာထူးခွဲခြားခြင်း<br>• လျှော့စျေးပေးခွင့်၊ စျေးနှုန်းပြင်ခွင့်၊ အကြွေးပေးခွင့်၊ အစီရင်ခံစာကြည့်ခွင့် အသေးစိတ် သတ်မှတ်ခြင်း | 🔹 **P2** |
| **၂၁။ System Audit Trail Logs**<br>(`sidebar_audit_logs`) | `Security` အောက် | • စျေးနှုန်းပြင်ဆင်ခြင်း၊ စတော့ဖြတ်ခြင်း၊ ဘောက်ချာဖျက်ခြင်း၊ ငွေထုတ်ခြင်း စသည့် အရေးကြီးလုပ်ဆောင်ချက်များကို မည်သူ/မည်သည့်အချိန်တွင် လုပ်ခဲ့သည်ကို အသေးစိတ် မှတ်တမ်းတင်ခြင်း | 🔹 **P2** |
| **၂၂။ Database Optimizer & System Alerts**<br>(`sidebar_database` / `alerts`) | `Maintenance` အောက် | • Database Vacuum / Index Optimize ပြုလုပ်ပေးခြင်း<br>• စတော့နည်းပါးမှု၊ အကြွေးကျော်လွန်မှု၊ နေ့စဉ်အရောင်း အကျဉ်းချုပ်ကို Telegram Bot / Email သို့ သတိပေးချက် ပို့ပေးခြင်း | 🔹 **P2** |

---

## ၃။ စတင်တည်ဆောက်မည့် လမ်းစဉ် (Immediate Next Action)

ဆရာကြီး အတည်ပြုပေးသည်နှင့် **Phase 1 (High-Impact Retail Essentials)** ၏ ပထမဆုံး အရေးကြီးဆုံး Module ဖြစ်သော:

👉 **Module ၁: Customer Receivables & Debt Ledger (ဖောက်သည် အကြွေးစာရင်းနှင့် ငွေကောက်ခံမှုစနစ်)** ကို စတင် တည်ဆောက်သွားပါမည်။

---
*Created with Tech Buddy for DataPOS Master Roadmap Delivery.*
