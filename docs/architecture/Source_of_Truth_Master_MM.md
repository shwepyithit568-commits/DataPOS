# DataPOS Single-Codebase Platform — Master Source of Truth (MM)

> **Document Type:** Master Domain Architecture & Business Rules Reference
> **Status:** Canonical Master Source of Truth (Active Multi-Store Single-Codebase Baseline)
> **Language:** Burmese (မြန်မာဘာသာ) with English technical terms
> **Verified Branch / Target:** `feature/reports-daily-closing-xz` @ `31d0ca92b7e35ce08881b2a944869d59f68d5f5f`
> **Project Path:** `D:\xmapp\htdocs\DataPOS`
> **Predecessor References (Archived):**
> - [`archive/source-of-truth-history/Source_of_Truth_MM_v2.md`](../archive/source-of-truth-history/Source_of_Truth_MM_v2.md)
> - [`archive/source-of-truth-history/DataPOS_Mobile_Offline_POS_Project_Source_of_Truth_v1_EN.md`](../archive/source-of-truth-history/DataPOS_Mobile_Offline_POS_Project_Source_of_Truth_v1_EN.md)
> - [`archive/source-of-truth-history/AlinnThit_Mobile_Offline_POS_Project_Source_of_Truth_20260731.md`](../archive/source-of-truth-history/AlinnThit_Mobile_Offline_POS_Project_Source_of_Truth_20260731.md)

---

## ၁။ ရည်ရွယ်ချက်နှင့် စည်းမျဉ်းများ (Objective & Non-Negotiables)

လူ Developer များ၊ AI Coding Agent များနှင့် Project Owner တို့အကြား Scope Drift၊ Incorrect Assumption၊ Duplicate Architecture နှင့် Conflicting Implementations မဖြစ်ပေါ်စေရန် ဤဖိုင်ကို DataPOS ပလက်ဖောင်းတစ်ခုလုံး၏ **Master Source of Truth** အဖြစ် သတ်မှတ်သည်။

### အဓိက မဏ္ဍိုင် (Core Invariants)
1. **Single Codebase, Multi-Store:** မတူညီသော စတိုးဆိုင်များနှင့် လုပ်ငန်းခွဲများ (Retail, Mobile/Electronics, Repair/Service, Wholesale, Ecommerce) အားလုံးသည် သီးခြား Codebase ခွဲထုတ်စရာမလိုဘဲ Single Laravel Application (`D:\xmapp\htdocs\DataPOS`) ပေါ်တွင် စနစ်တကျ run မည်။
2. **Tenant Isolation:** ဆိုင်တစ်ဆိုင်၏ Data (Staff, Products, Inventory, Sales, Customers, Reports, Settings) သည် အခြားဆိုင်သို့ လုံးဝ မပေါက်ကြားစေရ (`StoreContext` မှတစ်ဆင့် Query-level စစ်ဆေးသည်)။
3. **Double-Entry Inventory Ledger:** စတော့လက်ကျန်သည် ကိန်းဂဏန်းတစ်ခုကို တိုက်ရိုက် update လုပ်ခြင်းမဟုတ်ဘဲ `inventory_movements` ledger record ဖြင့်သာ အတိုး/အလျော့ မှတ်တမ်းတင်ရမည်။
4. **Offline-Capable Desktop Runtime:** Windows စက်များတွင် Internet မရှိဘဲ Offline POS အဖြစ် ချောမွေ့စွာ အသုံးပြုနိုင်ရန် SQLite WAL Mode ဖြင့် runtime တည်ဆောက်ထားသည်။

---

## ၂။ Source-of-Truth ဦးစားပေးအဆင့် (Hierarchy)

သတင်းအချက်အလက် ကွဲလွဲမှုများရှိလာပါက အောက်ပါ အဆင့်အတိုင်း ဆုံးဖြတ်ရမည်-

1. **`AGENTS.md` နှင့် Project Owner ၏ နောက်ဆုံးအတည်ပြုချက်များ:** အမြင့်ဆုံး စည်းကမ်းသတ်မှတ်ချက် ဖြစ်သည်။
2. **လက်ရှိ Target Branch ရှိ Executable Code၊ Migrations၊ Routes နှင့် Configs:** လက်ရှိ run နေသော ကုဒ်သည် အမှန်တရားဖြစ်သည်။
3. **Automated Test Suites (196 test files) နှင့် CI Results:** အောင်မြင်ပြီးသော tests များသည် verified facts များဖြစ်သည်။
4. **Active Architecture & Standards Documents (`docs/` အောက်ရှိ ဖိုင်များ):**
   - [`docs/README.md`](../README.md) — Documentation index
   - [`docs/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md`](../plans/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md) — Growth strategy
   - [`docs/SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md`](SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md) — Domain design
   - [`docs/reports_implementation_plan_v2.md`](../plans/reports_implementation_plan_v2.md) — Reports suite master plan
   - [`docs/windows_offline_installer_plan_v2.md`](../plans/windows_offline_installer_plan_v2.md) — Installer roadmap
   - [`docs/windows_runtime_and_storage_architecture.md`](windows_runtime_and_storage_architecture.md) — Storage spec
   - [`docs/prompts/ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md`](../guides/ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md) — Admin UI standard
   - [`docs/STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md`](../guides/STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md) — Storefront UI standard
   - [`docs/prompts/AI_AGENT_QA_PLAYBOOK.md`](../prompts/AI_AGENT_QA_PLAYBOOK.md) — QA playbook
5. **ဤ Master Source of Truth ဖိုင် (`docs/Source_of_Truth_Master_MM.md`)**
6. **`docs/archive/` ရှိ သမိုင်းဝင် အထောက်အထားများ:** Snapshot သာဖြစ်ပြီး implementation ညွှန်ကြားချက်အဖြစ် မသုံးရ။

---

## ၃။ Single Codebase & Store Architecture

DataPOS သည် URL Structure အားဖြင့် Scope နှစ်ခု ခွဲခြားထားသည်-

### က။ Platform Scope (`/admin/...` သို့မဟုတ် `/platform/...`)
- Super Admin / Platform Owner များအတွက်ဖြစ်ပြီး Store အသစ်များ တည်ဆောက်ခြင်း၊ Store Status စီမံခြင်းနှင့် System-wide Settings များကို ကိုင်တွယ်သည်။

### ခ။ Store Scope (`/store/{store_slug}/...`)
- သက်ဆိုင်ရာ Store တစ်ခုချင်းစီ၏ သီးသန့်နယ်ပယ်ဖြစ်သည်-
  - **Storefront:** `/store/{store_slug}` (Catalog, Products, Cart, Service Tracking)
  - **Store Admin:** `/store/{store_slug}/admin/...` (Dashboard, Inventory, Purchases, Staff, Settings)
  - **POS Counter:** `/store/{store_slug}/pos/...` (Counter checkout, Cashier Shifts, Daily Closing X/Z, Web Orders)

### ဂ။ Modular Store Capabilities (ဆိုင်အလိုက် ဖွင့်/ပိတ်နိုင်သော စွမ်းဆောင်ရည်များ)
Store တိုင်းတွင် feature အားလုံး ဖွင့်ပေးရန်မလိုဘဲ `stores.capabilities_override` အရ လိုအပ်သလို ဖွင့်/ပိတ်နိုင်သည်-
- `storefront` (Online Catalog & Order Builder)
- `catalog` (Brands, Categories, Variants, SKU management)
- `inventory` (Stock Ledger, Stock In, Adjustment, Supplier Purchases)
- `service` (Repair/Service intake, Ticket status, Technician tracking)
- `commerce` (POS, Wholesale pricing, Customer ledgers)
- `operations` (Cashier shifts, Drawer events, Daily Closing X/Z reports)

---

## ၄။ Store Membership နှင့် Authorization Boundary

UI တွင် Menu သို့မဟုတ် ခလုတ် ဖျောက်ထားရုံဖြင့် လုံခြုံရေး မမြောက်ပါ။ Route၊ Controller နှင့် Policy အဆင့်တိုင်းတွင် အောက်ပါ စစ်ဆေးမှု ၇ ဆင့်လုံး ပြည့်စုံမှသာ ခွင့်ပြုသည်-

```text
၁။ မှန်ကန်သော Platform/Store Scope ဖြစ်ခြင်း
AND ၂။ အဆိုပါ Store တွင် အကောင့်ရှိပြီး Active Membership ဖြစ်ခြင်း
AND ၃။ လုပ်ငန်းစဉ်အတွက် လိုအပ်သော Store Capability ဖွင့်ထားခြင်း
AND ၄။ သက်ဆိုင်ရာ Sales Channel ဖွင့်ထားခြင်း
AND ၅။ User ရာထူး၏ Role Permission (ဥပမာ- pos.checkout, inventory.adjust, pos_closing.approve) ရရှိထားခြင်း
AND ၆။ Target Resource သည် လက်ရှိ Active Store ၏ ပိုင်ဆိုင်မှုအစစ်အမှန်ဖြစ်ခြင်း (store_id isolation)
AND ၇။ Record သည် အခြား Store မှ Manipulated ID မဟုတ်ခြင်း
```

---

## ၅။ POS နှင့် Ecommerce တာဝန်ခွဲဝေမှုနှင့် ဆက်စပ်မှု

### က။ POS တာဝန်များ (Counter & In-Store Operations)
- **Offline-First Resilience:** Local loopback တွင် အင်တာနက်မလိုဘဲ ကောင်တာရောင်းချနိုင်ခြင်း။
- **Hardware Integration:** 58mm/80mm ESC/POS Thermal Receipt Printing နှင့် Barcode Scanning။
- **Cashier Shifts & Closing:** Shift Open/Close၊ Cash-in/Cash-out drawer events၊ X-Report (စစ်ဆေးရန်) နှင့် Z-Report (နေ့စဉ်စာရင်းပိတ် အတည်ပြုရန်)။
- **Sales & Returns:** Cart checkout၊ Split payment၊ Discount၊ Tax၊ Immutable receipts၊ Void နှင့် Returns/Refunds။

### ခ။ Storefront / Ecommerce တာဝန်များ (Online Customer Journey)
- **Public Browsing:** Mobile-responsive catalog၊ Category browser၊ Brand filtering၊ Search suggestions (XSS-safe)။
- **Ordering Suite:** Shopping cart၊ Direct Viber Order modal (`.sf-btn-3d-viber`)၊ Order Builder။
- **Service Tracking:** Customer အနေဖြင့် Repair Token ဖြင့် မိမိ service status အား online တွင် စစ်ဆေးနိုင်ခြင်း။

### ဂ။ Shared Inventory Ledger (လက်ကျန်စတော့ စည်းမျဉ်း)
- **Single Source of Truth:** POS ရောင်းချမှုနှင့် Online Orders များသည် သီးခြားစတော့မခွဲဘဲ တူညီသော `inventory_movements` ledger ပေါ်တွင် မူတည်သည်။
- **Online Order Lifecycle:**
  - Online Order တင်ချိန်တွင် `online_reserve` ဖြင့် စတော့ကြိုပွိုင့်မှတ်သည်။
  - Cashier မှ `/store/{store_slug}/pos/web-orders` တွင် စစ်ဆေးအတည်ပြုပြီး ရောင်းချပြီးစီးပါက `online_confirm` ဖြင့် ledger တွင် အပြီးသတ် စတော့နုတ်သည်။
  - ပယ်ဖျက်ပါက `online_cancel` ဖြင့် reserved စတော့ကို ပြန်လွှတ်ပေးသည်။
- `products.stock_status` သည် manual စာရင်းမဟုတ်ဘဲ Inventory Ledger Balance မှ အလိုအလျောက် တွက်ချက်ပြသသော dynamic status ဖြစ်သည်။

---

## ၆။ Financial & Quantity Calculations စံသတ်မှတ်ချက်

1. **Bcmath Precision for MMK:** ပလက်ဖောင်းအတွင်းရှိ မည်သည့် ကျပ်ငွေ၊ စျေးနှုန်း၊ Tax၊ Discount နှင့် Total ပမာဏကိုမျှ PHP Float ဖြင့် သိမ်းဆည်းတွက်ချက်ခြင်း လုံးဝ မပြုလုပ်ရ။ စနစ်ကျသော `bcmath` string operations သို့မဟုတ် Integer Cents/MMK စံနှုန်းဖြင့်သာ တွက်ချက်ရမည်။
2. **Clean Quantity Formatting:** စတော့လက်ကျန်နှင့် အရေအတွက်များတွင် `.000` မပါစေဘဲ `format_quantity($qty, $store)` (သို့မဟုတ် `$fmtQty`) ဖြင့်သာ သန့်ရှင်းစွာ ပြသရမည် (ဥပမာ- `10` အစား `10.000` မပြရ)။
3. **Dynamic Currency:** မည်သည့် Blade view သို့မဟုတ် JS တွင်မျှ Hardcoded `Ks` လုံးဝ (လုံးဝ) မရေးရ။ `/admin/settings/currency` Setting အတိုင်း `format_currency($amount, $store)` သို့မဟုတ် `window.formatCurrency(val)` ဖြင့်သာ Dynamic ပြသရမည်။

---

## ၇။ Windows Runtime & Storage Architecture

1. **Canonical Database Path:** `storage/database/datapos.sqlite` ဖြစ်သည် (Laravel မူလ `database/database.sqlite` ကို မသုံးရ)။
2. **SQLite WAL Mode & Locking:**
   - `journal_mode = WAL`
   - `busy_timeout = 5000` (5 စက္ကန့်)
   - `foreign_keys = ON`
   - `synchronous = NORMAL`
3. **App & Storage Separation:** Read-only application files နှင့် Writable user data (`storage/`, `database/`) တို့ကို ခွဲခြားထားပြီး Windows Non-Admin standard users များအတွက် `Access Denied` အမှားအယွင်းများ မဖြစ်စေရန် စီမံထားသည်။

---

## ၈။ သမိုင်းဝင်ယူဆချက်ဟောင်းများနှင့် လက်ရှိအခြေအနေ နှိုင်းယှဉ်ချက် (Historical vs Current)

| အကြောင်းအရာ | မူလယူဆချက်ဟောင်း (Historical v1) | လက်ရှိခိုင်မာသောအခြေအနေ (Current Verified Reality) |
|---|---|---|
| **Architecture** | POS တစ်ခုစီတွင် SQLite ခွဲထားပြီး Cloud DB သို့ API ဖြင့် sync လုပ်ရန် | Single Codebase Multi-Store Ledger ဖြစ်ပြီး Local/Cloud တစ်ပြေးညီ run သည် |
| **Online Orders** | Viber မှလာသော order ကို POS ထဲသို့ cashier က လက်ဖြင့် manual ပြန်ရိုက်ထည့်ရန် | Online Order Builder၊ Direct Viber Modal နှင့် POS `/web-orders` ချိတ်ဆက်မှု အပြည့်အစုံ ပါဝင်သည် |
| **Project Path** | `D:\xmapp\htdocs\data_ecommerce` (အစောပိုင်း standalone ecommerce) | `D:\xmapp\htdocs\DataPOS` (ပူးပေါင်းပြီးစီးသော single repository) |
| **Database** | `database/database.sqlite` | Canonical path: `storage/database/datapos.sqlite` (WAL enabled) |
| **Currency Display** | Static `Ks` hardcoded စာသားများ | Centralized dynamic currency formatting (`format_currency`) |
| **Reporting Suite** | Phase 2 Review အဆင့်သာရှိခဲ့သည် | Phase 2 Daily Closing, X-Report, Z-Report အားလုံး ပြီးစီးပြီး immutable snapshot စနစ် တပ်ဆင်ပြီးဖြစ်သည် |

---

## ၉။ ဆက်စပ်အသုံးပြုရမည့် Active Documents

- **Documentation Master Index:** [`docs/README.md`](../README.md)
- **Growth & Product Strategy:** [`DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md`](../plans/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md)
- **Product Domain & Architecture:** [`SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md`](SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md)
- **AlinnThit Mobile SKU Logic Guide:** [`guides/ALINN_THIT_MOBILE_SKU_LOGIC_MM.md`](../guides/ALINN_THIT_MOBILE_SKU_LOGIC_MM.md)
- **Reporting Suite Architecture:** [`reports_implementation_plan_v2.md`](../plans/reports_implementation_plan_v2.md)
- **Windows Offline Installer Plan v2:** [`windows_offline_installer_plan_v2.md`](../plans/windows_offline_installer_plan_v2.md)
- **Runtime & Storage Architecture:** [`windows_runtime_and_storage_architecture.md`](windows_runtime_and_storage_architecture.md)
- **Quality Assurance & Fix Playbook:** [`prompts/AI_AGENT_QA_PLAYBOOK.md`](../prompts/AI_AGENT_QA_PLAYBOOK.md)
