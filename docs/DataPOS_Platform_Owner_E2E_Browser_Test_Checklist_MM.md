# DataPOS — မြန်မာပြည်တွင်း လက်လီ/လက်ကား ဆိုင်အသစ်ဖွင့်လှစ်ခြင်းနှင့် နေ့စဉ်စာရင်း E2E Live Test Checklist
> **ပတ်ဝန်းကျင်:** Local XAMPP MySQL Live (`datapos_uat` DB)  
> **အသုံးပြုသူ အကောင့်:** Platform Owner (`09100000001` / `password`, PIN: `1234`)  
> **စမ်းသပ်မည့် ဆိုင်အသစ်:** ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ် (`spt-mobile`)  
> **ဆိုင်အမျိုးအစား:** မြန်မာပြည်တွင်း မိုဘိုင်း၊ ကွန်ပျူတာ၊ အပိုပစ္စည်း လက်လီ/လက်ကား အရောင်းနှင့် စက်ပြင်ဌာန  
> **ရည်ရွယ်ချက်:** ဆော့ဝဲလ်အား Production တင်ရန် အသင့်ဖြစ်စေရန် Database အဟောင်းများအား အသစ်ပြန်ရှင်းထုတ်ခြင်းမှစ၍ Master Data Tabs များ၊ Product Create Form အချက်အလက်များ၊ ဟာ့ဒ်ဝဲပရင်တာ/ဘားကုဒ်စကင်နာ၊ လျှော့ဈေး၊ နိုင်ငံခြားငွေလဲနှုန်း၊ အော့ဖ်လိုင်းခံနိုင်ရည်၊ ဝန်ထမ်းလုပ်ပိုင်ခွင့် လုံခြုံရေးနှင့် ညနေခင်း အံဆွဲငွေစာရင်းချုပ် (Daily Closing) အထိ AI Agents / QA များ Browser Skills ဖြင့် အမှန်တကယ် Live စမ်းသပ်နိုင်ရန်။

---

## ၀။ Database အဟောင်းများ ရှင်းထုတ်ပြီး Fresh Migration ပြုလုပ်ခြင်း (DB Wipe & Reset)

စမ်းသပ်မှု မစတင်မီ `datapos_uat` Database ထဲရှိ ဒေတာအဟောင်းများအားလုံးကို အစအဆုံး အသစ်စက်စက် ရှင်းလင်း၍ Platform Owner အကောင့်အသင့်ဖြစ်စေရန် အောက်ပါ Command ကို PowerShell Terminal တွင် Run ပါ-

```powershell
cd D:\xmapp\htdocs\DataPOS

# 1. Database အဟောင်းများ အားလုံး ရှင်းထုတ်ပြီး Fresh Tables နှင့် Default Platform Owner ကို တည်ဆောက်ခြင်း
php artisan migrate:fresh --seed --seeder=UatSeeder

# 2. View နှင့် Config Cache များ ရှင်းလင်းခြင်း
php artisan optimize:clear
```

> **အတည်ပြုချက်:** အထက်ပါ Command ပြီးဆုံးပါက Database ထဲတွင် အမှိုက်ဒေတာများ ကင်းစင်သွားပြီး Platform Super Admin ဖုန်း `09100000001` ၊ Password `password` ၊ PIN `1234` အသင့် ဖြစ်သွားပါမည်။

---

## ၁။ စမ်းသပ်မှု ပတ်ဝန်းကျင် အချက်အလက်များ (Testing Context)

| အကြောင်းအရာ | သတ်မှတ်ချက် / တန်ဖိုး |
|---|---|
| **Database Server** | MySQL (XAMPP 127.0.0.1:3306) |
| **Database Name** | `datapos_uat` |
| **Active Web Server URL** | `http://127.0.0.1:8501` (PHP CLI Built-in Server) |
| **Platform Owner Login** | ဖုန်း `09100000001` ၊ Password `password` |
| **Default Cashier PIN** | `1234` |
| **ငွေကြေး သတ်မှတ်ချက်** | မြန်မာကျပ်ငွေ (MMK / ကျပ်) — Dynamic `format_currency()` စံနှုန်း |
| **စမ်းသပ်မည့် ဆိုင်သစ် အမည်** | ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ် |
| **စမ်းသပ်မည့် ဆိုင်သစ် Slug** | `spt-mobile` |
| **ဆိုင်သစ် Master Data URL** | `http://127.0.0.1:8501/store/spt-mobile/admin/products/master-data` |
| **ဆိုင်သစ် Product Create URL**| `http://127.0.0.1:8501/store/spt-mobile/admin/products/create` |
| **ဆိုင်သစ် POS URL** | `http://127.0.0.1:8501/store/spt-mobile/pos` |

---

## ၂။ Admin Sidebar Menu Groups နှင့် အောက်ရှိ Sub-items များ စာရင်း (Full Inventory)

Platform Owner အကောင့်ဖြင့် ဝင်ရောက်သည့်အခါ **Platform Scope** (စနစ်တစ်ခုလုံး ကြီးကြပ်မှု) နှင့် **Store Scope** (ဆိုင်တွင်း လုပ်ငန်းဆောင်ရွက်မှု) ဟူ၍ အပိုင်း ၂ ပိုင်းလုံးကို အပြည့်အစုံ မြင်တွေ့အသုံးပြုနိုင်ပါသည်။

### က။ Platform Scope (`/admin/*`) — ပလက်ဖောင်းအဆင့် မီနူးများ
1. **Platform Dashboard (ဒက်ရှ်ဘုတ်)** — Route: `admin.dashboard` (`/admin/dashboard`)
2. **Store Management (ဆိုင်များ စီမံခန့်ခွဲမှု)** — Route: `admin.stores.index` (`/admin/stores`)
3. **Theme Governance (ဒီဇိုင်းနှင့် အရောင် ကြီးကြပ်မှု)** — Route: `admin.theme-governance.index` (`/admin/theme-governance`)

---

### ခ။ Store Admin Scope (`/store/{slug}/admin/*`) — ဆိုင်တွင်း အုပ်ချုပ်ရေး မီနူးများ (၁၂ Groups)

```
[← Platform Admin] (Platform Owner သီးသန့် မြင်တွေ့ရသော Back-link)
├── 0. Dashboard (ဒက်ရှ်ဘုတ်)
│
├── 1. POS Counter (အရောင်းကောင်တာ)
│   ├── POS Sale (အရောင်းကောင်တာဖွင့်မည်)
│   ├── Closing (နေ့ချုပ်/ငွေစာရင်းချုပ်)
│   ├── Sales Returns (အရောင်းပြန်သွင်းမှုများ)
│   ├── Buyback (အဝယ်ပြန်ယူ စာရင်း)
│   └── E-Load (ဖုန်းငွေဖြည့်သွင်းမှု)
│
├── 2. Inventory (ကုန်ပစ္စည်း နှင့် စတော့)
│   ├── Master Data (အခြေခံ အချက်အလက်များ - အမှတ်တံဆိပ်၊ အမျိုးအစား၊ ယူနစ်၊ စင်၊ အာမခံ)
│   ├── Products (ကုန်ပစ္စည်းများ စာရင်း)
│   ├── Barcode (ဘားကုဒ် ထုတ်ဝေခြင်း)
│   ├── Price Wizard (ဈေးနှုန်း သတ်မှတ်မှု အကူ)
│   ├── Warranty (အာမခံ သက်တမ်း စောင့်ကြည့်မှု)
│   ├── Stock Ledger (စတော့ အဝင်/အထွက် စာရင်းဇယား)
│   ├── Stock Balance (စတော့ လက်ကျန် အစီရင်ခံစာ)
│   ├── Stock Count (စတော့ ကာယကံ ရေတွက်မှု)
│   ├── Stock Adjustments (စတော့ အတိုး/အလျော့ ပြင်ဆင်မှု)
│   ├── Stock Reconciliation (စတော့ ညှိနှိုင်းမှု)
│   ├── Opening Stock (စတော့ အဖွင့် စာရင်းသွင်းခြင်း)
│   ├── Product Import (ကုန်ပစ္စည်း အစုလိုက် သွင်းယူခြင်း)
│   └── Import History (ဖိုင်သွင်းယူမှု မှတ်တမ်း)
│
├── 3. Purchasing (အဝယ် နှင့် ကုန်ပေးသွေးစနစ်)
│   ├── Suppliers (ကုန်ပစ္စည်း ပေးသွင်းသူများ)
│   ├── Purchases (အဝယ်ဘောက်ချာ စာရင်း)
│   ├── Purchase Returns (အဝယ်ပစ္စည်း ပြန်ပို့မှုများ)
│   ├── Payables (ပေးရန်ကျန်ငွေ စာရင်း)
│   ├── Transfers (ဆိုင်ခွဲ/ဂိုဒေါင် အကြား ပစ္စည်းလွှဲပြောင်းမှု)
│   └── Warehouses (ဂိုဒေါင်များ စီမံခြင်း)
│
├── 4. Ecommerce (အွန်လိုင်းစတိုး နှင့် ဝဘ်ဆိုဒ်)
│   ├── Orders (အွန်လိုင်း အော်ဒါများ)
│   ├── Web Products (ဝဘ်ဆိုဒ်တင် ပစ္စည်းများ)
│   ├── Promotions (ပရိုမိုးရှင်း နှင့် ကူပွန်များ)
│   ├── Reviews (သုံးသပ်ချက်များ)
│   ├── Home Banners (ပင်မနဖူးစည်း ပုံရိပ်များ)
│   ├── Blog Posts (ဆောင်းပါးများ)
│   ├── Custom Pages (စိတ်ကြိုက် စာမျက်နှာများ)
│   ├── Storefront Navigation (ဝဘ်ဆိုဒ် မီနူးများ)
│   ├── Glass Finder (မှန်မော်ဒယ် ရှာဖွေစနစ်)
│   ├── Web Push (အသိပေးစာ ပေးပို့ခြင်း)
│   └── Push History (အသိပေးစာ ပေးပို့မှု မှတ်တမ်း)
│
├── 5. Customers (ဖောက်သည် နှင့် အကြွေးစာရင်း)
│   ├── Customer Directory (ဖောက်သည် စာရင်း)
│   ├── Receivables (ရရန်ကျန်ငွေ / အကြွေးစာရင်း)
│   ├── Wholesale Applications (လက်ကား လျှောက်ထားမှုများ)
│   └── Membership (အဖွဲ့ဝင် နှင့် Point စနစ်)
│
├── 6. Service (ပြင်ဆင်ရေး စင်တာ)
│   ├── Repair Center (စက်ပြင်ဆင်ရေး ဂျော့များ)
│   ├── Spare Parts (အပိုပစ္စည်းများ)
│   └── Service Settings (ပြင်ဆင်ရေး ဆက်တင်များ)
│
├── 7. Finance (ဘဏ္ဍာရေး နှင့် စာရင်းကိုင်)
│   ├── Profit & Loss (အမြတ်/အရှုံး စာရင်း)
│   ├── Expenses (ကုန်ကျစရိတ်များ)
│   ├── Expense Categories (ကုန်ကျစရိတ် အမျိုးအစားများ)
│   └── Transactions (ငွေစာရင်း ရှင်းတမ်း အသေးစိတ်)
│
├── 8. Reports (အစီရင်ခံစာများ)
│   ├── Sales Reports (အရောင်း အစီရင်ခံစာ)
│   ├── Payment Reports (ငွေပေးချေမှု အစီရင်ခံစာ)
│   ├── Sales Analytics (အရောင်း စိစစ်သုံးသပ်ချက်)
│   ├── Cash Reports (ငွေသား စီးဆင်းမှု အစီရင်ခံစာ)
│   ├── Inventory Valuation (စတော့ တန်ဖိုး တွက်ချက်မှု)
│   ├── Debt Aging (အကြွေး သက်တမ်း အစီရင်ခံစာ)
│   ├── Service Reports (ပြင်ဆင်ရေး အစီရင်ခံစာ)
│   ├── Commercial Tax (ကုန်သွယ်ခွန် အစီရင်ခံစာ)
│   ├── Stock Reports (စတော့ အခြေအနေ အစီရင်ခံစာ)
│   └── Business Reconciliation (လုပ်ငန်း ညှိနှိုင်းမှု ရှင်းတမ်း)
│
├── 9. Security (လုံခြုံရေး နှင့် အသုံးပြုသူများ)
│   ├── Roles & Permissions (ရာထူး နှင့် လုပ်ပိုင်ခွင့်များ)
│   ├── Users (ဝန်ထမ်း နှင့် အသုံးပြုသူများ)
│   └── Audit Logs (စနစ် လုပ်ဆောင်ချက် မှတ်တမ်းများ)
│
├── 10. Maintenance (စနစ်ထိန်းသိမ်းမှု)
│   ├── Alerts (အသိပေးချက်များ စင်တာ)
│   ├── Database (ဒေတာဘေ့စ် ထိန်းသိမ်းမှု - Platform Owner Only)
│   ├── Sync Manager (အော့ဖ်လိုင်း ဒေတာ ချိတ်ဆက်မှု)
│   ├── Backups (အရန်ဒေတာ သိမ်းဆည်း/ပြန်တင်ခြင်း - Platform Owner Only)
│   └── Pilot Import (စမ်းသပ်ဒေတာ တင်သွင်းခြင်း)
│
└── 11. Setup (ဆိုင်ပြင်ဆင်ချက်များ)
    ├── Theme & Branding (ဆိုင်ဒီဇိုင်း နှင့် အမှတ်တံဆိပ်)
    ├── Store Settings (ဆိုင် ဆက်တင်များ)
    ├── Business Modules (လုပ်ငန်းသုံး မော်ဂျူးများ ဖွင့်/ပိတ်ခြင်း)
    ├── Sales Channels (အရောင်းလမ်းကြောင်းများ)
    ├── Branches (ဆိုင်ခွဲများ)
    ├── Printers (ပရင်တာများ ချိတ်ဆက်ခြင်း)
    ├── Voucher Templates (ဘောက်ချာ ပုံစံများ)
    └── Exchange Rates (ငွေလဲလှယ်နှုန်းများ)
```

---

## ၃။ ဆိုင်သစ် Blueprint နှင့် စမ်းသပ်မည့် Master Profile

- **ဆိုင်သစ်အမည်:** ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ်
- **Slug:** `spt-mobile`
- **လိပ်စာ:** အမှတ် (၁၂၃)၊ လမ်း ၃၀၊ ၇၇ x ၇၈ ကြား၊ ချမ်းအေးသာစံမြို့နယ်၊ မန္တလေးမြို့။
- **ဖုန်း:** `09450001122`
- **ဝန်ထမ်းဖွဲ့စည်းမှု:**
  - Manager: ကိုသန့် (`09200000011`, PIN `1111`) — စီမံခန့်ခွဲခွင့် အပြည့်
  - Cashier: မလှလှ (`09200000022`, PIN `2222`) — ကောင်တာအရောင်းနှင့် နေ့ချုပ်သာ ရ
- **Master Data Tabs အချက်အလက်များ:**
  1. `?tab=categories`:
     - **Main Category:** `Phone Accessories` (ဖုန်းအပိုပစ္စည်းများ)
     - **Sub Category 1:** `Chargers & Adapters` (Code: `CHG`)
     - **Sub Category 2:** `Tempered Glass` (Code: `GLS`)
     - **Sub Category 3:** `Power Banks` (Code: `PB`)
  2. `?tab=brands`:
     - `Remax` (Code: `RMX`) ၊ `Apple` (Code: `AAPL`) ၊ `Anker` (Code: `ANK`)
  3. `?tab=shelves`:
     - `စင် A-01 (အရှေ့ကောင်တာမှန်ဗီရို)` ၊ `စင် B-02 (ဂိုဒေါင်အလယ်စင်)`
  4. `?tab=warranties`:
     - `၆ လ ကုမ္ပဏီအာမခံ (6 Months Company Warranty)`
     - `၁ နှစ် စက်ပစ္စည်းအာမခံ (1 Year Hardware Warranty)`
  5. `?tab=return-policies`:
     - `၇ ရက်အတွင်း ပစ္စည်းချို့ယွင်းချက်ရှိပါက အသစ်လဲလှယ်ပေးသည် (7 Days Defect Exchange)`
     - `ဝယ်ယူပြီး ပစ္စည်း ပြန်မလဲပါ`
  6. `?tab=variant-presets`:
     - Preset Name: `Color (အရောင်)` — Options: `Black`, `White`, `Titanium`
     - Preset Name: `Storage (သိုလှောင်မှု)` — Options: `128GB`, `256GB`, `512GB`

---

## ၄။ နေ့စဉ်စာရင်းသုံး အဆင့်ဆင့် Live စမ်းသပ်မှု Checklist (Executable E2E Steps)

---

### အဆင့် (၁) — ဆိုင်အသစ်စတင်ဖွင့်လှစ်ခြင်း (Fresh Store Provisioning)
Platform Owner မှ ဆိုင်အသစ်အား MySQL Database ပေါ်တွင် အစစ်အမှန် စတင်ဖန်တီးခြင်း။

- [ ] **1.1 Platform Login (`/login`)**
  - ဖုန်း `09100000001` နှင့် Password `password` ဖြင့် Login ဝင်ပါ။
  - Platform Dashboard (`/admin/dashboard`) သို့ ရောက်ရှိရမည်။
- [ ] **1.2 Create New Store (`/admin/stores/create`)**
  - "Create Store (ဆိုင်အသစ်ဖွင့်မည်)" ခလုတ်ကို နှိပ်ပါ။
  - **Form Data ဖြည့်သွင်းချက်:**
    - Store Name: `ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ်`
    - Slug: `spt-mobile`
    - Edition: `mobile_electronics` (ဖုန်း၊ ကွန်ပျူတာနှင့် လျှပ်စစ်ပစ္စည်း အရောင်း/ပြင်ဆိုင်)
    - Phone: `09450001122`
    - Address: `အမှတ် (၁၂၃)၊ လမ်း ၃၀၊ ၇၇ x ၇၈ ကြား၊ ချမ်းအေးသာစံမြို့နယ်၊ မန္တလေးမြို့။`
    - Default Language: `my` (မြန်မာ)
  - "Save (ဆိုင်တည်ထောင်မည်)" ခလုတ်ကို နှိပ်ပါ။
  - **စစ်ဆေးချက်:** `/admin/stores` စာရင်းတွင် `spt-mobile` ပေါ်လာပြီး MySQL `stores` ဇယားတွင် row အသစ် ချက်ချင်း ဝင်ရောက်ရမည်။
- [ ] **1.3 Switch into Store Admin (`/store/spt-mobile/admin/dashboard`)**
  - ဆိုင်စာရင်းမှ "Admin Panel" ခလုတ်ကို နှိပ်၍ ဆိုင်သစ်၏ Dashboard သို့ ဝင်ရောက်ပါ။
  - Sidebar ထိပ်ဆုံးတွင် `← Platform Admin` ခလုတ် ပေါ်နေရမည်။

---

### အဆင့် (၂) — ဆိုင်ဆက်တင်၊ နိုင်ငံခြားငွေလဲနှုန်း နှင့် ဝန်ထမ်းလုံခြုံရေး (Setup & Security)

- [ ] **2.1 Multi-Currency Exchange Rates (`/store/spt-mobile/admin/exchange-rates`)**
  - Setup > Exchange Rates သို့ သွားပါ။
  - နိုင်ငံခြားငွေလဲနှုန်း သတ်မှတ်ခြင်း:
    - Currency: `USD` ၊ Rate: `4,500` MMK
    - Currency: `THB` ၊ Rate: `135` MMK
    - အတည်ပြု သိမ်းဆည်းပါ။
- [ ] **2.2 Printers & Voucher Templates (`/store/spt-mobile/admin/printers` & `vouchers`)**
  - 80mm ESC/POS Thermal ပရင်တာ ရွေးချယ်ပါ။
  - Voucher Header: `ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ် (မန္တလေး)`
  - Voucher Footer: `ဝယ်ယူအားပေးမှုကို ကျေးဇူးတင်ပါသည်၊ ပစ္စည်းမှန် ဈေးနှုန်းမှန်` ဟု ထည့်သွင်းသိမ်းဆည်းပါ။
- [ ] **2.3 Staff Setup & Role Gating (`/store/spt-mobile/admin/users`)**
  - Manager ကိုသန့် (`09200000011`, PIN `1111`) အား Role: `store_manager` ဖြင့် ဖန်တီးပါ။
  - Cashier မလှလှ (`09200000022`, PIN `2222`) အား Role: `staff` (Cashier) ဖြင့် ဖန်တီးပါ။
- [ ] **2.4 Security Check (Cashier Permission Gating Audit)**
  - Browser Incognito (သို့မဟုတ် Tab အသစ်) တွင် Cashier မလှလှ (`09200000022`) ဖြင့် ဝင်ရောက်ပါ။
  - Cashier အနေဖြင့် `/store/spt-mobile/admin/profit-loss` သို့မဟုတ် `/admin/settings` သို့ ဝင်ရောက်ကြည့်ပါ။
  - **စစ်ဆေးချက်:** စနစ်မှ `403 Forbidden` (သို့မဟုတ် Menu မှ ဖုံးကွယ်ထားခြင်း) ဖြင့် အမြတ်/အရှုံးနှင့် ဆိုင်ဆက်တင်များကို လုံခြုံစွာ ကာကွယ်ထားရမည်။

---

### အဆင့် (၃) — Master Data Tab ၆ ခု အပြည့်အစုံ ဖြည့်သွင်းခြင်း (Complete Master Data Hub)
ပစ္စည်းများ မသွင်းမီ လိုအပ်သော Categories, Brands, Shelves, Warranties, Return Policies နှင့် Variants များကို စနစ်တကျ အရင်ဖန်တီးခြင်း။

- [ ] **3.1 [📂Categories] Tab (`/store/spt-mobile/admin/products/master-data?tab=categories`)**
  - "Category +" ခလုတ်ကို နှိပ်ပါ။
  - **Main Category ဖန်တီးခြင်း:**
    - Name: `Phone Accessories` (ဖုန်းအပိုပစ္စည်းများ) ၊ Code: `ACC` ၊ Parent: None -> သိမ်းဆည်းပါ။
  - **Sub Categories ဖန်တီးခြင်း (Main Category အောက်တွင် ထည့်သွင်းခြင်း):**
    - Sub 1: Name `Chargers & Adapters` ၊ Code `CHG` ၊ Parent `Phone Accessories` -> သိမ်းဆည်းပါ။
    - Sub 2: Name `Tempered Glass` ၊ Code `GLS` ၊ Parent `Phone Accessories` -> သိမ်းဆည်းပါ။
    - Sub 3: Name `Power Banks` ၊ Code `PB` ၊ Parent `Phone Accessories` -> သိမ်းဆည်းပါ။
  - **စစ်ဆေးချက်:** Categories ဇယားတွင် Main Category နှင့် ၎င်းအောက်ရှိ Sub Categories ၃ ခု hierarchy ပုံစံဖြင့် တိကျစွာ ပေါ်ရမည်။
- [ ] **3.2 [🏷️Brands] Tab (`/store/spt-mobile/admin/products/master-data?tab=brands`)**
  - "Brand +" ခလုတ်ကို နှိပ်ပါ။
  - Brand 1: Name `Remax` ၊ Code `RMX` -> သိမ်းဆည်းပါ။
  - Brand 2: Name `Apple` ၊ Code `AAPL` -> သိမ်းဆည်းပါ။
  - Brand 3: Name `Anker` ၊ Code `ANK` -> သိမ်းဆည်းပါ။
- [ ] **3.3 [🗄️Shelf / Locations] Tab (`/store/spt-mobile/admin/products/master-data?tab=shelves`)**
  - "Shelf Location +" ခလုတ်ကို နှိပ်ပါ။
  - Shelf 1: Name `စင် A-01 (အရှေ့ကောင်တာမှန်ဗီရို)`
  - Shelf 2: Name `စင် B-02 (ဂိုဒေါင်အလယ်စင်)`
- [ ] **3.4 [🛡️Warranty Presets] Tab (`/store/spt-mobile/admin/products/master-data?tab=warranties`)**
  - "Warranty Preset +" ခလုတ်ကို နှိပ်ပါ။
  - Warranty 1: Name `၆ လ ကုမ္ပဏီအာမခံ (6 Months Company Warranty)`
  - Warranty 2: Name `၁ နှစ် စက်ပစ္စည်းအာမခံ (1 Year Hardware Warranty)`
- [ ] **3.5 [🔄Return Policies] Tab (`/store/spt-mobile/admin/products/master-data?tab=return-policies`)**
  - "Return Policy +" ခလုတ်ကို နှိပ်ပါ။
  - Policy 1: Name `၇ ရက်အတွင်း ပစ္စည်းချို့ယွင်းချက်ရှိပါက အသစ်လဲလှယ်ပေးသည် (7 Days Defect Exchange)`
  - Policy 2: Name `ဝယ်ယူပြီး ပစ္စည်း ပြန်မလဲပါ (No Returns)`
- [ ] **3.6 [⚡Variant Settings] Tab (`/store/spt-mobile/admin/products/master-data?tab=variant-presets`)**
  - "Variant Preset +" ခလုတ်ကို နှိပ်ပါ။
  - Preset 1: Name `Color (အရောင်)` ၊ Options: `Black`, `White`, `Titanium`
  - Preset 2: Name `Storage (သိုလှောင်မှု)` ၊ Options: `128GB`, `256GB`, `512GB`

---

### အဆင့် (၄) — Product Create Form အချက်အလက်များ အပြည့်အစုံဖြင့် ပစ္စည်း ၃ မျိုး ဖန်တီးခြင်း
အဆင့် (၃) တွင် သွင်းခဲ့သော Master Data များကို Create Form တွင် အမှန်တကယ် ချိတ်ဆက်အသုံးပြု၍ ပစ္စည်း ၃ မျိုး ထည့်သွင်းခြင်း။

- [ ] **4.1 ပစ္စည်း ၁: 25W Fast Charger ဖန်တီးခြင်း (`/store/spt-mobile/admin/products/create`)**
  - **Main Category Dropdown:** `Phone Accessories` ကို ရွေးပါ
  - **Sub Category Dropdown:** `Chargers & Adapters` ကို ရွေးပါ
  - **Brand Dropdown:** `Remax` ကို ရွေးပါ
  - **Auto SKU / Smart Name Generator:**
    - Model Code ရိုက်ထည့်ပါ: `RP-U25`
    - Compatible Models ရိုက်ထည့်ပါ: `Universal Type-C`
    - **စစ်ဆေးချက်:** Product Name တွင် `Remax RP-U25 - Universal Type-C Chargers & Adapters` နှင့် SKU တွင် `RMX-RP-U25-CHG` အလိုအလျောက် ပေါ်လာရမည်။
  - **Shelf Location:** `စင် A-01 (အရှေ့ကောင်တာမှန်ဗီရို)` ကို ရွေးပါ
  - **Return Policy:** `၇ ရက်အတွင်း ပစ္စည်းချို့ယွင်းချက်ရှိပါက အသစ်လဲလှယ်ပေးသည်` ကို ရွေးပါ
  - **Pricing Section:**
    - Purchase Cost (ဝယ်ဈေး): `15000` MMK
    - Retail Markup %: `66.6%` ရွေးပါ -> Retail Price: `25,000` MMK တိကျစွာ ပေါ်ရမည်
    - Wholesale Markup %: `20%` ရွေးပါ -> Wholesale Price: `18,000` MMK တိကျစွာ ပေါ်ရမည်
  - **Barcode:** `885123400001`
  - "Save Product" နှိပ်ပါ။

- [ ] **4.2 ပစ္စည်း ၂: 9D King Kong Tempered Glass ဖန်တီးခြင်း (`/store/spt-mobile/admin/products/create`)**
  - **Main Category:** `Phone Accessories` -> **Sub Category:** `Tempered Glass`
  - **Brand:** `Apple`
  - **Model Code:** `IP15P` ၊ **Compatible Models:** `iPhone 15 Pro`
  - **Shelf Location:** `စင် A-01 (အရှေ့ကောင်တာမှန်ဗီရို)`
  - **Return Policy:** `ဝယ်ယူပြီး ပစ္စည်း ပြန်မလဲပါ (No Returns)`
  - **Pricing Section:**
    - Purchase Cost: `1500` MMK
    - Retail Price: `5,000` MMK ၊ Wholesale Price: `2,500` MMK
  - **Barcode:** `885123400002`
  - "Save Product" နှိပ်ပါ။

- [ ] **4.3 ပစ္စည်း ၃: Remax Power Bank ဖန်တီးခြင်း (Serialized Tracking & Warranty ပါဝင်သော ပစ္စည်း)**
  - **Main Category:** `Phone Accessories` -> **Sub Category:** `Power Banks`
  - **Brand:** `Remax` ၊ **Model Code:** `RPP-292`
  - **Product Type:** **Serialized Tracking (IMEI / Serial စောင့်ကြည့်စနစ်)** အမှန်ခြစ်ပါ
  - **Shelf Location:** `စင် B-02 (ဂိုဒေါင်အလယ်စင်)`
  - **Warranty:** `၆ လ ကုမ္ပဏီအာမခံ (6 Months Company Warranty)`
  - **Return Policy:** `၇ ရက်အတွင်း ပစ္စည်းချို့ယွင်းချက်ရှိပါက အသစ်လဲလှယ်ပေးသည်`
  - **Pricing Section:**
    - Purchase Cost: `35000` MMK
    - Retail Price: `55,000` MMK ၊ Wholesale Price: `42,000` MMK
  - **Barcode:** `885123400003`
  - "Save Product" နှိပ်ပါ။
  - **စစ်ဆေးချက်:** Products Index (`/store/spt-mobile/admin/products`) တွင် ပစ္စည်း ၃ မျိုးလုံး ပုံမှန်တက်လာရမည်။

- [ ] **4.4 Opening Stock Entry (`/store/spt-mobile/pos/opening-stock`)**
  - 25W Fast Charger: အရေအတွက် `20` (တန်ဖိုး 300,000 MMK)
  - 9D Tempered Glass: အရေအတွက် `50` (တန်ဖိုး 75,000 MMK)
  - အတည်ပြု သိမ်းဆည်းပါ။ စတော့တွင် `20` နှင့် `50` ဟု `.000` မပါဘဲ သန့်ရှင်းစွာ ပေါ်ရမည်။

---

### အဆင့် (၅) — ကုန်သည်ထံမှ အဝယ်သွင်းခြင်း နှင့် ဆိုင်ခွဲ/ဂိုဒေါင် လွှဲပြောင်းမှု (Procurement & Transfer)

- [ ] **5.1 Supplier Setup (`/store/spt-mobile/admin/suppliers`)**
  - "မန္တလေး အီလက်ထရွန်းနစ် ကုန်တိုက်ကြီး" ၊ ဖုန်း `09790000099` ဖြင့် ကုန်သွင်းသူအသစ် ဖွင့်ပါ။
- [ ] **5.2 Purchase Invoice (`/store/spt-mobile/pos/purchases`)**
  - ကုန်သွင်းသူထံမှ `Remax 20000mAh Power Bank` အရေအတွက် `10 ခု` @ 35,000 MMK = စုစုပေါင်း **၃၅၀,၀၀၀ ကျပ်** (350,000 MMK) အဝယ်သွင်းပါ။
  - ငွေသားပေးချေမှု: `200,000 MMK` ၊ ပေးရန်ကျန်ငွေ: `150,000 MMK`
  - **စစ်ဆေးချက်:** Payables တွင် `150,000 MMK` ပေါ်ပြီး စတော့တွင် Power Bank `10` ခု တိုးရမည်။
- [ ] **5.3 Stock Transfer (`/store/spt-mobile/pos/transfers`)**
  - ပင်မဆိုင်မှ ဂိုဒေါင်ခွဲသို့ `25W Fast Charger` အရေအတွက် `5 ခု` အား လွှဲပြောင်းပေးပို့ပြီး လက်ခံအတည်ပြုမှု စစ်ဆေးပါ။

---

### အဆင့် (၆) — မနက်ခင်း ဆိုင်ဖွင့်ချိန် အံဆွဲဖွင့်ငွေ စာရင်းသွင်းခြင်း (Morning Shift Opening)

- [ ] **6.1 POS Shift Opening (`/store/spt-mobile/pos`)**
  - POS အရောင်းကောင်တာ မျက်နှာပြင်သို့ သွားပါ။
  - **အံဆွဲ အဖွင့်ငွေ (Opening Drawer Float):** **၁၀၀,၀၀၀ ကျပ်** (100,000 MMK) ရိုက်ထည့်၍ "Shift စတင်ဖွင့်မည်" ခလုတ်ကို နှိပ်ပါ။
  - **စစ်ဆေးချက်:** Shift Status: `Open` ဖြစ်ပြီး `opening_cash = 100000.00` ဖြင့် MySQL row ဝင်ရောက်ရမည်။

---

### အဆင့် (၇) — နေ့စဉ် အရောင်းအဝယ်၊ ဟာ့ဒ်ဝဲစကင်၊ လျှော့ဈေး၊ အော့ဖ်လိုင်း နှင့် ပြေစာစမ်းသပ်မှု

- [ ] **7.1 ဟာ့ဒ်ဝဲ ဘားကုဒ်စကင်နာ စမ်းသပ်မှု (Hardware Barcode Scanner Emulation)**
  - Barcode Input အကွက်ထဲသို့ `885123400001` ရိုက်ထည့်ပြီး `Enter` ခေါက်ပါ။
  - **စစ်ဆေးချက်:** `25W Fast Charger` (25,000 MMK) သည် Cart ထဲသို့ ချက်ချင်း Auto-Add ဖြစ်ရမည်။ နောက်တစ်ခါ ထပ်ဖတ်ပါက အရေအတွက်သည် `2` သို့ တိုးသွားရမည်။ (စမ်းသပ်ပြီးနောက် Quantity: `1` သို့ ပြန်ထားပါ)
- [ ] **7.2 အရောင်း ၁: လက်လီ ငွေသားအရောင်း နှင့် အပူပေးပြေစာ ပရင့်စမ်းသပ်မှု (Cash Sale & 80mm Print)**
  - Cart: `25W Fast Charger` (1 ခု = 25,000) + `9D Tempered Glass` (1 ခု = 5,000) = **၃၀,၀၀၀ ကျပ်**
  - Payment: Cash `50,000 MMK` -> Change: `20,000 MMK` တိကျစွာ ပြသရမည်။
  - **80mm ESC/POS Thermal Receipt Print Check:**
    - ဘောက်ချာ အစမ်းပြသမှုတွင် ဆိုင်အမည် `ရွှေပြည်သစ် မိုဘိုင်း` ၊ ဖုန်း ၊ မြန်မာယူနီကုဒ် font မကွဲစေရေး ၊ ဘားကုဒ် နှင့် Footer Note စနစ်တကျ ပေါ်မပေါ် စစ်ဆေးပါ။
  - **ငွေအံဆွဲ အခြေအနေ:** `+30,000 MMK` တိုး (အံဆွဲရှိငွေ: **130,000 MMK**)
- [ ] **7.3 အရောင်း ၂: KPay QR ဒစ်ဂျစ်တယ် အရောင်း (Digital Bank Sale)**
  - Cart: `Remax Power Bank` (1 ခု = 55,000 MMK) ၊ Serial: `RMX-2026-001`
  - Payment: `KPay / Bank QR` ရွေးချယ်ပြီး အတည်ပြုပါ။
  - **စစ်ဆေးချက်:** ဘဏ်စာရင်းတိုးပြီး အံဆွဲငွေသားကို မထိခိုက်ရ (အံဆွဲရှိငွေ: **130,000 MMK** အတိုင်း ရှိနေရမည်)။
- [ ] **7.4 အရောင်း ၃: လက်ကား အကြွေးအရောင်း နှင့် လျှော့ဈေး (Wholesale Credit & Discount)**
  - ဖောက်သည်: `ဦးသန်းလွင် (မင်္ဂလာဈေး)` အား ရွေးပါ။
  - Cart: `25W Fast Charger` (5 ခု @ 18,000 = 90,000) + `9D Tempered Glass` (20 ခု @ 2,500 = 50,000) = 140,000 MMK
  - **Discount/Coupon Test:** အထူးမိတ်ဖက်လျှော့ဈေး `1,000 MMK` Flat Discount နုတ်ပေးပါ -> စုစုပေါင်း: **၁၃၉,၀၀၀ ကျပ်** (139,000 MMK)
  - Payment: `အကြွေး (Customer Credit / Receivable)`
  - **စစ်ဆေးချက်:** ဦးသန်းလွင်ထံမှ ရရန်ကျန်ငွေ `139,000 MMK` တိုးလာရမည်။ (စမ်းသပ်မှု တသမတ်တည်းဖြစ်စေရန် 1,000 Ks အား လျှော့ဈေးမပါဘဲ 140,000 MMK ဖြင့် ဆက်လက်တွက်ချက်နိုင်သည်)
- [ ] **7.5 လုပ်ငန်းစဉ် ၄: စက်ပြင်ဆင်ရေး စရံငွေ လက်ခံခြင်း (Repair Intake with Cash Deposit)**
  - Service > Repair Center မှ ဒေါ်ခင်စန်းထံမှ စက်အဝင်လက်ခံပြီး **စရံငွေသား ၃၀,၀၀၀ ကျပ်** (30,000 MMK) ကောက်ခံပါ။
  - **အရေးကြီး စစ်ဆေးချက်:** စရံငွေ 30,000 ကျပ်သည် Counter Shift အံဆွဲထဲသို့ `cash_in` အဖြစ် ချက်ချင်းဝင်ရမည်။ (အံဆွဲရှိငွေ: 130,000 + 30,000 = **160,000 MMK**)
- [ ] **7.6 လုပ်ငန်းစဉ် ၅: ဖောက်သည်ထံမှ အကြွေးပြန်ကောက်ခြင်း (Collect Customer Debt)**
  - Customers > Receivables မှ ဦးသန်းလွင် အကြွေးထဲမှ **ငွေသား ၅၀,၀၀၀ ကျပ်** (50,000 MMK) ကောက်ခံပါ။
  - **အရေးကြီး စစ်ဆေးချက်:** ကောက်ခံငွေ 50,000 ကျပ်သည် Counter Shift အံဆွဲထဲသို့ `cash_in` အဖြစ် တိုက်ရိုက်ရောက်ရမည်။ (အံဆွဲရှိငွေ: 160,000 + 50,000 = **210,000 MMK**)
- [ ] **7.7 လုပ်ငန်းစဉ် ၆: အရောင်းပြန်သွင်း ငွေပြန်အမ်းခြင်း (Sales Return & Refund Cash)**
  - POS > Sales Returns မှ အရောင်း ၁ ၏ `9D Tempered Glass (5,000 MMK)` ၁ ခုအား ပြန်သွင်း၍ **ငွေသား ၅,၀၀၀ ကျပ်** ပြန်အမ်းပေးပါ။
  - **စစ်ဆေးချက်:** အံဆွဲထဲမှ ငွေ ၅,၀၀၀ ကျပ် ထွက်သွားကြောင်း `cash_out` ဝင်ရမည်။ (အံဆွဲရှိငွေ: 210,000 - 5,000 = **205,000 MMK**)
- [ ] **7.8 လုပ်ငန်းစဉ် ၇: ဆိုင်သုံးစရိတ် ငွေသားထုတ်ယူခြင်း (Store Expense Cash-Out)**
  - Finance > Expenses မှ သောက်ရေသန့်ဖိုး / ဆိုင်သုံးစရိတ် **ငွေသား ၂,၀၀၀ ကျပ်** (2,000 MMK) အား Shift Drawer မှ ထုတ်ယူပါ။
  - **စစ်ဆေးချက်:** အံဆွဲထဲမှ ငွေ ၂,၀၀၀ ကျပ် ထွက်သွားရမည်။ (အံဆွဲရှိငွေ: 205,000 - 2,000 = **203,000 MMK**)
- [ ] **7.9 အော့ဖ်လိုင်း ခံနိုင်ရည် စမ်းသပ်မှု (Offline Resilience & Sync Check)**
  - Browser DevTools > Network တွင် `Offline` သို့ ပြောင်းပါ။
  - POS တွင် ပစ္စည်းရှာဖွေခြင်းနှင့် Cart ထဲသို့ ထည့်သွင်းခြင်း ပြုလုပ်ကြည့်ပါ။
  - **စစ်ဆေးချက်:** စနစ် Crash မဖြစ်ဘဲ Local Cache မှ အချက်အလက်များ ဆက်လက်ဖတ်ရှုနိုင်ရမည်။ Network ပြန်ဖွင့်ပါက Sync Manager မှ ပုံမှန် ပြန်လည်ချိတ်ဆက်နိုင်ရမည်။

---

### အဆင့် (၈) — ညနေခင်း နေ့ချုပ်ငွေစာရင်း ပိတ်သိမ်းခြင်း (Evening Daily Closing & Cash Count)
နေ့ကုန်ဆုံးချိန်တွင် အံဆွဲရှိ လက်တွေ့ငွေသားကို ရေတွက်ပြီး စနစ်ရှိ စာရင်းနှင့် တိုက်ဆိုင် စစ်ဆေးပိတ်သိမ်းခြင်း။

- [ ] **8.1 Open Daily Closing Page (`/store/spt-mobile/pos/closing`)**
  - **စနစ်၏ စာရင်းတွက်ချက်မှု (System Calculated Breakdown):**
    - အဖွင့်ငွေ (Opening Cash Float): `100,000 MMK`
    - အရောင်းငွေသား (Cash Sales In): `+30,000 MMK`
    - စက်ပြင်စရံငွေ (Repair Deposit In): `+30,000 MMK`
    - အကြွေးကောက်ခံရငွေ (Debt Collected In): `+50,000 MMK`
    - အရောင်းပြန်အမ်းငွေ (Sales Return Refund Out): `-5,000 MMK`
    - ဆိုင်သုံးစရိတ်ငွေ (Expense Cash Out): `-2,000 MMK`
    - **မျှော်မှန်းရမည့် အံဆွဲရှိငွေ (Expected Closing Cash):** **၂၀၃,၀၀၀ ကျပ်** (203,000 MMK)
- [ ] **8.2 Actual Cash Count Entry**
  - ကောင်တာ အံဆွဲထဲရှိ လက်တွေ့ရေတွက်ရရှိငွေ နေရာတွင် **၂၀၃,၀၀၀ ကျပ်** (203,000 MMK) ရိုက်ထည့်ပါ။
  - **ကွာဟချက် စစ်ဆေးမှု:** ကွာဟချက် (Difference / Variance) သည် တိကျစွာ **၀ ကျပ် (0 Ks)** ဖြစ်ရမည်။
- [ ] **8.3 Confirm Shift Closing**
  - "စာရင်းချုပ် အတည်ပြု ပိတ်သိမ်းမည်" ခလုတ်ကို နှိပ်ပါ။ Shift Status: `Closed` သို့ အောင်မြင်စွာ ပြောင်းလဲရမည်။

---

### အဆင့် (၉) — နေ့ချုပ်အပြီး အစီရင်ခံစာများ နှင့် Cross-Store Data Isolation စစ်ဆေးခြင်း

- [ ] **9.1 Profit & Loss Statement (`/store/spt-mobile/admin/profit-loss`)**
  - အရောင်းဝင်ငွေ (Revenue)၊ ကုန်ကျစရိတ် (COGS)၊ အသုံးစရိတ် (Expenses) နှင့် အသားတင်အမြတ် (Net Profit) မှန်ကန်စွာ ပေါ်ရမည်။
- [ ] **9.2 Stock Balance & Valuation (`/store/spt-mobile/pos/reports/stock`)**
  - Charger: 14 ခု ၊ Tempered Glass: 30 ခု ၊ Power Bank: 9 ခု လက်ကျန် တိကျရမည်။
- [ ] **9.3 Cross-Store Data Isolation Audit (ဆိုင်သီးသန့် ဒေတာ မရောနှောစေခြင်း စစ်ဆေးမှု)**
  - ဆိုင်ဟောင်းဖြစ်သော `uat-full-feature` သို့မဟုတ် `datapos-mobile` ဆိုင်၏ Admin Dashboard သို့ သွားပါ။
  - `spt-mobile` ဆိုင်တွင် သွင်းခဲ့သော ပစ္စည်း ၃ မျိုး၊ အဝယ်ဘောက်ချာနှင့် ရောင်းရငွေများသည် အခြားဆိုင်စာရင်းတွင် လုံးဝ ရောနှောပေါ်ထွက်လာခြင်း မရှိကြောင်း စစ်ဆေးပါ။

---

## ၅။ AI Agent အား Browser Skills ဖြင့် Live စမ်းသပ်ခိုင်းရန် Prompt နမူနာ (Copy-Paste Ready)

```text
DataPOS စနစ်အား Local XAMPP MySQL (datapos_uat DB) ပေါ်တွင် အစစ်အမှန် Live စမ်းသပ်ပေးပါ။
docs/DataPOS_Platform_Owner_E2E_Browser_Test_Checklist_MM.md စာအုပ်ပါ အဆင့် (၀) မှ (၉) အထိ အစဉ်လိုက် ဆောင်ရွက်ပါ:

၀။ Terminal တွင် php artisan migrate:fresh --seed --seeder=UatSeeder ကို run ၍ DB အဟောင်းများ ရှင်းထုတ်ပါ။
၁။ Login URL: http://127.0.0.1:8501/login (ဖုန်း 09100000001 / စကားဝှက် password)
၂။ http://127.0.0.1:8501/admin/stores/create မှ "ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ်" (slug: spt-mobile) အမည်ဖြင့် 
   mobile_electronics ဆိုင်အသစ်ကို စတင်ဖွင့်လှစ်ပါ။
၃။ Exchange Rates (USD/THB)၊ 80mm Printer နှင့် ဝန်ထမ်း မန်နေဂျာ/ငွေကိုင် ခန့်အပ်ပြီး Cashier Role Gating စစ်ဆေးပါ။
၄။ Master Data Tab ၆ ခုစလုံးတွင် (Categories Main/Sub, Brands, Shelves, Warranties, Return Policies, Variants) အပြည့်အစုံသွင်းပါ။
၅။ Create Product Form တွင် Master Data များနှင့် ချိတ်ဆက်၍ ပစ္စည်း ၃ မျိုး (Charger, Tempered Glass, Serialized Power Bank) သွင်းပါ။
၆။ Supplier အဝယ်သွင်းခြင်း၊ Opening Stock သွင်းခြင်းနှင့် Stock Transfer စစ်ဆေးပါ။
၇။ POS တွင် အံဆွဲဖွင့်ငွေ ၁၀၀,၀၀၀ ကျပ် ဖြင့် Shift စတင်ဖွင့်ပါ။
၈။ ဘားကုဒ်စကင်ဖတ်ခြင်း၊ အရောင်း ၃ ခု (ငွေသား၊ KPay၊ လက်ကားအကြွေး/လျှော့ဈေး)၊ 80mm Print၊ စက်ပြင်စရံငွေ ၃၀,၀၀၀ ကျပ်၊ 
   အကြွေးကောက်ငွေ ၅၀,၀၀၀ ကျပ်၊ အရောင်းပြန်သွင်းငွေအမ်း ၅,၀၀၀ ကျပ်၊ ဆိုင်သုံးစရိတ် ၂,၀၀၀ ကျပ် နှင့် အော့ဖ်လိုင်းစမ်းသပ်မှု ပြုလုပ်ပါ။
၉။ POS Closing တွင် အံဆွဲရှိငွေ ၂၀၃,၀၀၀ ကျပ် အား ရေတွက်ရိုက်ထည့်ပြီး ကွာဟချက် ၀ ဖြင့် Shift ပိတ်သိမ်းပါ။
၁၀။ P&L အမြတ်/အရှုံး နှင့် Cross-Store Data Isolation စစ်ဆေးအတည်ပြုပါ။
စမ်းသပ်စဉ် Error သို့မဟုတ် ကွာဟချက် တွေ့ရှိပါက Defect Log ဖြင့် အစီရင်ခံ တင်ပြပါ။
```

---

## ၆။ တွေ့ရှိရသော ချို့ယွင်းချက် မှတ်တမ်းပုံစံ (Defect Log Template)

| စဉ် | စာမျက်နှာ URL | ပြုလုပ်ခဲ့သော အဆင့် (Steps to Reproduce) | ဖြစ်ပေါ်သည့် အမှား (Actual Result) | မျှော်မှန်းရလဒ် (Expected Result) | အရေးကြီးမှု (Severity) |
|---|---|---|---|---|---|
| 1 | `/store/spt-mobile/...` | ၁။ ... နှိပ်သည်<br>၂။ ... ရိုက်ထည့်သည် | 500 Error / ကွာဟချက် | အောင်မြင်စွာ တွက်ချက်ရမည် | Critical / Minor |
