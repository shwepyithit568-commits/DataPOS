# DataPOS Single-Codebase Growth — အဆင့်လိုက် အကောင်အထည်ဖော်မှု မာစတာ Checklist
**Document Type:** Execution Master Checklist & Quality Gates  
**Status:** Active Execution Baseline  
**Base Architecture:** [DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md](file:///d:/xmapp/htdocs/DataPOS/docs/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md)  
**Primary Constraints:** One Owner/Developer + AI Agents | Single Codebase | Myanmar SME Realities  
**Version:** 1.0.0 (August 2026)

---

## 📋 မာတိကာ (Table of Contents)
1. [မဟာဗျူဟာ ထပ်ဆောင်းအကြံပြုချက်များနှင့် သတိပြုရန် စည်းမျဉ်းများ](#၁-မဟာဗျူဟာ-ထပ်ဆောင်းအကြံပြုချက်များနှင့်-သတိပြုရန်-စည်းမျဉ်းများ)
2. [Phase 0 — Baseline Lock & Safety Tagging (လက်ရှိအခြေအနေ အတည်ပြုခြင်း)](#phase-0--baseline-lock--safety-tagging)
3. [Phase 1 — Capability & Business Profile Foundation (အခြေခံ အင်ဂျင်)](#phase-1--capability--business-profile-foundation)
4. [Phase 2 — Storefront Decoupling & View Models (စတိုးမျက်နှာစာ သီးခြားခွဲထုတ်ခြင်း)](#phase-2--storefront-decoupling--view-models)
5. [Phase 3 — Storefront Theme Engine MVP (ဒီဇိုင်းနှင့် အပြင်အဆင် အင်ဂျင်)](#phase-3--storefront-theme-engine-mvp)
6. [Phase 4 — Tier A Productization (Mobile, General Retail, Repair Editions)](#phase-4--tier-a-productization)
7. [Phase 5 — Cloud Resale & Multi-Tenant Hardening (Cloud စီးပွားဖြစ် ရောင်းချမှု)](#phase-5--cloud-resale--multi-tenant-hardening)
8. [Phase 6 — Tier B: Batch, Expiry & Multi-UOM (Pharmacy & Agriculture)](#phase-6--tier-b-batch-expiry--multi-uom)
9. [Phase 7 — Local / LAN Deployment Edition (Offline/LAN ဆိုင်များအတွက်)](#phase-7--local--lan-deployment-edition)
10. [Phase 8 — Tier C: Restaurant Vertical Pack Prototype (စားသောက်ဆိုင်)](#phase-8--tier-c-restaurant-vertical-pack-prototype)

---

## ၁။ မဟာဗျူဟာ ထပ်ဆောင်းအကြံပြုချက်များနှင့် သတိပြုရန် စည်းမျဉ်းများ

### (က) ထပ်မံတိုးမြှင့်ရမည့် နည်းပညာဆိုင်ရာ အကြံပြုချက်များ
1. **Code-First Capability Registry (Schema မရှုပ်ထွေးစေရေး):**
   - Boolean column များကို `stores` table တွင် ရမ်းမထည့်ဘဲ Enum/Class-based Registry (`App\Capabilities\CapabilityRegistry`) ဖြင့် စတင်မည်။
   - Database တွင် custom override လိုအပ်မှသာ normalized `store_capabilities` table ကို သုံးမည်။
2. **Design Tokens & Pre-approved Layouts (Theme လုံခြုံရေး):**
   - Theme တစ်ခုစီကို Blade code upload မလုပ်ခွင့်မပေးဘဲ Pre-approved Blade layout bundle + CSS Design Tokens (Colors, Radius, Elevation, Fonts) ဖြင့်သာ ထိန်းချုပ်မည်။
3. **Zero Historical Data Deletion (အချက်အလက် မပျက်စီးရေး):**
   - Capability သို့မဟုတ် Business Profile ပြောင်းလဲခြင်းကြောင့် ယခင် စာရင်းဟောင်းများ (Transactions, Audits, Invoices) လုံးဝ မပျက်စေရ။ Menu/Action ဖျောက်ခြင်း (Read-only access) သာ ပြုလုပ်မည်။
4. **Synchronous Fallback for Future Offline/LAN:**
   - Background Queue/Cron မရှိသော Local/LAN ကွန်ပျူတာများအတွက် အရေးကြီးသော Process များ (Backup, Receipts, Audits) တွင် Synchronous Fallback code အမြဲပါဝင်စေမည်။

### (ခ) ရှောင်ရှားရမည့် အချက်များ (Strict Red Lines)
- ❌ လုပ်ငန်းအမျိုးအစားတစ်ခုစီအတွက် Git branch သို့မဟုတ် repo သီးခြား မခွဲရ။
- ❌ Store owner ကို raw Blade/PHP/JS upload လုပ်ခွင့် လုံးဝ မပေးရ။
- ❌ Batch/Expiry မပြီးသေးဘဲ Pharmacy POS ဟု ဈေးကွက်သို့ မရောင်းချရ။
- ❌ KOT/Table workflow မပါဘဲ Retail POS ကို စားသောက်ဆိုင်သို့ မရောင်းရ။
- ❌ Automated tests မစစ်ဘဲ feature အသစ် မတိုးရ (WIP Limit = 1 Vertical at a time)။

---

## Phase 0 — Baseline Lock & Safety Tagging

> **ရည်ရွယ်ချက်:** လက်ရှိ Multi-Store Data Isolation ရလဒ်များနှင့် Demo Store အားလုံးကို အတည်ပြု၍ Safe Git Baseline သတ်မှတ်ရန်။

- [x] **၀.၁ လက်ရှိ Automated Test Suite ပြည့်စုံစွာ Run စစ်ဆေးခြင်း**
  - [x] `php artisan test` ဖြင့် စုစုပေါင်း tests 1,402 အားလုံး Pass ဖြစ်ကြောင်း အတည်ပြုပြီး (6,048 assertions)။
- [x] **၀.၂ Working Tree Clean Commit & Tagging ပြုလုပ်ခြင်း**
  - [x] လက်ရှိ ပြင်ဆင်ထားသော Isolation test suite များနှင့် Demo seeder များကို commit လုပ်ပြီး Safe baseline သတ်မှတ်ခြင်း။
  - [x] Git Tag (`v1.0.0-isolation-hardened`) သတ်မှတ်ရန် အဆင်သင့်ဖြစ်ခြင်း။
- [x] **၀.၃ လက်ရှိ Demo Stores (၆) ခု အလုပ်လုပ်ပုံ အတည်ပြုခြင်း**
  - [x] Agriculture (`diamond-stone-agri`)
  - [x] Mobile Accessories (`datapos-mobile`)
  - [x] CCTV & PC (`cctv-network-computer`)
  - [x] Mobile Sale & Service (`mobile-sale-service`)
  - [x] Pharmacy (`pharmacy`)
  - [x] Restaurant (`si-taw-gyi-food-bar`)

---

## Phase 1 — Capability & Business Profile Foundation

> **ရည်ရွယ်ချက်:** Feature များကို UI hide/show သာမက Server-side Middleware, Policies နှင့် အမှန်တကယ် စစ်ဆေးသော Capability စနစ် တည်ဆောက်ရန်။

- [x] **၁.၁ Capability Registry & Definition (`App\Capabilities\CapabilityRegistry`)**
  - [x] Storefront & Ecommerce: `storefront.ecommerce`, `storefront.online_ordering`, `storefront.customer_portal`
  - [x] Catalog: `catalog.variants`, `catalog.custom_fields`
  - [x] Inventory: `inventory.serial_tracking`, `inventory.batch_tracking`, `inventory.expiry_tracking`, `inventory.multi_uom`
  - [x] Service: `service.repair_jobs`, `service.warranty_tracking`
  - [x] Commerce: `commerce.wholesale_pricing`, `commerce.customer_debt`
  - [x] Operations: `operations.branches`, `operations.warehouses`, `operations.cashier_shifts`, `pos.tablet_mobile_mode`
- [x] **၁.၂ Business Profile & Operation Mode Registry (`App\BusinessProfiles\BusinessProfileRegistry`)**
  - [x] Profile: `mobile_electronics` (Variants, Serials, Warranty, Repairs, Wholesale)
  - [x] Profile: `general_retail` (Barcode POS, Variants, Wholesale, Cashier Shifts)
  - [x] Profile: `repair_service` (Repair Jobs, Warranty, Spare Parts, Customer Debt)
  - [x] Operation Modes:
    - 📱 **POS-Only Mode (In-Store Counter / Tablet / Phone):** Online eCommerce မသုံးဘဲ ဆိုင်တွင်း POS ရောင်းချမှု သီးသန့်သုံးမည့် ဆိုင်များအတွက် (Public Web Catalog ပိတ်ထားပြီး Phone/Tablet ဖြင့် တိုက်ရိုက် POS ဝင်သုံးနိုင်သည်)။
    - 🌐 **Omnichannel Mode (POS + Web Storefront):** ဆိုင်တွင်း POS အရောင်းရော အွန်လိုင်း ဝဘ်ဆိုက်ပါ တွဲဖက်သုံးမည့် ဆိုင်များအတွက်။
- [x] **၁.၃ Store Capability Resolver & Context Integration (`App\Services\StoreContext`)**
  - [x] Store Model သို့ `business_profile` attribute သတ်မှတ်ခြင်း (Default: `mobile_electronics` သို့မဟုတ် `general_retail`)။
  - [x] `hasCapability(string $capability): bool` helper method ထည့်သွင်းခြင်း။
- [x] **၁.၄ Server-Side Enforcement Middleware (`CheckStoreCapability`)**
  - [x] Route များတွင် `middleware('store.capability:service.repair_jobs')` စသည်ဖြင့် ကာကွယ်ခြင်း။
  - [x] Capability ပိတ်ထားသော Route ကို URL တိုက်ရိုက်ခေါ်ပါက `403 Forbidden` ပြသခြင်း။
- [x] **၁.၅ Admin Navigation & Sidebar Dynamic Filtering**
  - [x] Sidebar Menu များတွင် `@if(store_can('service.repair_jobs'))` စသည်ဖြင့် သက်ဆိုင်ရာ Menu သာ ဖော်ပြခြင်း။
- [x] **၁.၆ Automated Feature Tests**
  - [x] `test_disabled_capability_route_aborts_403()`
  - [x] `test_store_profile_resolves_correct_capabilities()`
  - [x] `test_cross_store_cannot_access_unauthorized_capability_modules()`

---

## Phase 2 — Storefront Decoupling & View Models

> **ရည်ရွယ်ချက်:** လက်ရှိ Storefront မျက်နှာစာမှ Mobile-specific အသုံးအနှုန်းများနှင့် အစိတ်အပိုင်းများကို industry-neutral view models အဖြစ် ခွဲထုတ်ရန်။

- [x] **၂.၁ Storefront View Models သတ်မှတ်ခြင်း (`App\ViewModels\Storefront\...`)**
  - [x] `StoreHeaderViewModel` (Logo, Contact, Social, Navigation Links)
  - [x] `ProductCardViewModel` (Title, SKU, Price, Badges, Stock Status)
  - [x] `CategoryFilterViewModel` (Categories, Brands, Price Range)
- [x] **၂.၂ Industry-Specific Sections ကို Profile အလိုက် Dynamic ပြုလုပ်ခြင်း**
  - [x] Glass Finder / IMEI Search ကို `mobile_electronics` profile တွင်သာ ဖော်ပြခြင်း။
  - [x] General Retail နှင့် Pharmacy ဆိုင်များတွင် Mobile-specific widgets များ အလိုအလျောက် ပုန်းနေစေခြင်း။
  - [x] `storefront.ecommerce` capability ပိတ်ထားသော **POS-Only ဆိုင်များ** တွင် Public Catalog လုံးဝ ပိတ်ထားပြီး Cashier Login သို့မဟုတ် "Counter Sales Only" စာမျက်နှာသို့ အလိုအလျောက် ညွှန်ပြခြင်း (Safe Redirect / Fallback)။
- [x] **၂.၃ Mobile & Tablet POS Touch Experience (POS-Only Ready)**
  - [x] Tablet (iPad/Android Tablet) နှင့် Phone မျက်နှာပြင်များတွင် POS အရောင်းကောင်တာ Touch UI ကောင်းမွန်စွာ အလုပ်လုပ်ခြင်း။
  - [x] PWA (Progressive Web App) အနေဖြင့် ဖုန်း/တက်ပလက် Screen ပေါ်တွင် App ကဲ့သို့ Icon တင်၍ သုံးနိုင်ခြင်း။
  - [x] ကင်မရာဖြင့် Barcode Scan ဖတ်ခြင်းနှင့် 58mm/80mm Bluetooth/Network Thermal Print ထုတ်နိုင်ခြင်း။
- [x] **၂.၄ Storefront Decoupling Tests**
  - [x] `test_general_retail_storefront_omits_mobile_finder()`
  - [x] `test_mobile_storefront_preserves_device_repair_tracking()`
  - [x] `test_pos_only_store_shows_in_store_counter_landing_to_guests()`
  - [x] `test_pos_only_store_redirects_staff_to_pos_directly()`

---

## Phase 3 — Storefront Theme Engine MVP

> **ရည်ရွယ်ချက်:** Store Owner သည် Platform မှ သတ်မှတ်ပေးထားသော Theme များထဲမှ ရွေးချယ်နိုင်ပြီး Brand အရောင်နှင့် Logo များကို လုံခြုံစွာ စိတ်ကြိုက်ပြင်ဆင် Preview ကြည့်နိုင်စေရန်။

- [x] **၃.၁ Theme Registry & Manifests (`App\Themes\...`)**
  - [x] Theme: `marketplace_pro` (Mobile, Electronics, စုံလင်သော ကတ်တလောက်ပုံစံ)
  - [x] Theme: `retail_trust` (General Retail, Grocery, သန့်ရှင်းရိုးရှင်းသော လက်လီပုံစံ)
  - [x] Theme: `emerald_fresh` (Pharmacy, Healthcare, သဘာဝဆန်သော အစိမ်းရောင်ပုံစံ)
  - [x] Theme: `midnight_tech` (Premium Tech, Dark Mode ပုံစံ)
  - [x] Theme: `sunset_warm` (Boutique, Fashion, နွေးထွေးသော ပန်းရောင်စုံပုံစံ)
- [x] **၃.၂ CSS Design Tokens & Branding Settings (`storefront_settings`)**
  - [x] Primary Color, Accent Color, Surface Color, Header Text Color
  - [x] Font Family Presets (Myanmar Unicode Safe - Pyidaungsu, Padauk, Inter, Outfit, System)
  - [x] Product Grid Density (Compact / Comfortable)
- [x] **၃.၃ Theme Customizer & Real-time Preview Engine**
  - [x] Admin Customizer UI (`/store/{slug}/admin/theme` & `/store/{slug}/admin/settings/appearance`)
  - [x] Live Mini SVG & Interactive Storefront Mockup Preview with Light/Dark Mode toggles
- [x] **၃.၄ Automated Feature Tests**
  - [x] `test_theme_registry_loads_all_manifests_and_presets()`
  - [x] `test_store_manager_can_access_theme_customizer_page()`
  - [x] `test_store_manager_can_update_theme_preset_and_tokens()`
  - [x] `test_storefront_renders_updated_css_variables_and_font()`
  - [x] `test_cross_store_manager_cannot_update_other_store_theme()`

---

## Phase 4 — Tier A Productization

> **ရည်ရွယ်ချက်:** ပထမဆုံး စီးပွားဖြစ်ရောင်းချမည့် Editions (၃) ခုအား အဆင်သင့်သုံး Ready-to-sell Package ဖြစ်အောင် ပြင်ဆင်ပြီး စမ်းသပ်ဆိုင်ဖြင့် စစ်ဆေးရန်။

- [x] **၄.၁ Edition Onboarding Wizard & Preset Selector**
  - [x] ဆိုင်အသစ်စဖွင့်ချိန်တွင် (Mobile / General Retail / Pharmacy) ရွေးချယ်သည်နှင့် Default Category/Brand/Settings အလိုအလျောက် သတ်မှတ်ပေးခြင်း။
  - [x] Dedicated Store Owner Account ကို ဆိုင်နှင့် တပြိုင်နက် ဖန်တီးပေးခြင်း (`StoreOnboardingService`)။
- [x] **၄.၂ Demo Data Seeders & Role Accounts စစ်ဆေးခြင်း**
  - [x] Store Owner, Manager, Cashier, Technician, Wholesale Customer Accounts စနစ်တကျ အလုပ်လုပ်ခြင်း။
- [x] **၄.၃ Hardware Compatibility Matrix စမ်းသပ်ခြင်း**
  - [x] USB / LAN / Bluetooth 80mm & 58mm POS Thermal Printers (ESC/POS) Test Receipt Generator (`HardwareMatrixService`)
  - [x] 1D / 2D Barcode Scanners (USB & Wireless HID) & Web Camera Scanners
- [x] **၄.၄ Pilot Store Validation (လက်တွေ့ ဆိုင်စမ်းသပ်မှု စစ်ဆေးချက်)**
  - [x] အနည်းဆုံး Real Pilot ဆိုင် ၁ ဆိုင်တွင် နေ့စဉ်အရောင်း၊ အဝယ်၊ အကြွေးနှင့် Cashier Daily Closing ပြုလုပ်ပြီး စာရင်းကိုက်ညီမှု စစ်ဆေးခြင်း။
  - [x] Database Backup & Restore လက်တွေ့ စမ်းသပ်အောင်မြင်ခြင်း။

---

## Phase 5 — Cloud Resale & Multi-Tenant Hardening

> **ရည်ရွယ်ချက်:** Multi-tenant Cloud SaaS အဖြစ် ဆိုင်များစွာသို့ တပြိုင်နက် လုံခြုံစိတ်ချစွာ ဝန်ဆောင်မှုပေးနိုင်ရန်။

- [ ] **၅.၁ Automated Store Provisioning & Subscription Plan Limits**
  - [ ] Plan Limits: Max Products, Max Branches, Allowed Capabilities
- [ ] **၅.၂ Support Mode with Strict Reason & Audit Logging**
  - [ ] Platform Super Admin က ဆိုင်အကောင့်ထဲသို့ အကူအညီပေးရန် ဝင်ရောက်ပါက အကြောင်းပြချက် မဖြစ်မနေ ထည့်သွင်းရခြင်းနှင့် Audit Log မှတ်တမ်းတင်ခြင်း။
- [ ] **၅.၃ Rate Limiting, File Upload Quotas & Tenant Resource Protection**
- [ ] **၅.၄ Store Data Export (GDPR/Data Ownership) & Clean Deletion Workflow**

---

## Phase 6 — Tier B: Batch, Expiry & Multi-UOM (Pharmacy & Agriculture)

> **Entry Gate:** Tier A Editions များ တည်ငြိမ်ပြီး ဆေးဆိုင်/စိုက်ပျိုးရေးဆိုင် Customer လိုအပ်ချက် အတည်ပြုပြီးမှ စတင်မည်။

- [ ] **၆.၁ Multi-UOM (Unit of Measurement) Conversion Foundation**
  - [ ] ဥပမာ - ၁ ဖာ = ၁၀ ကတ် = ၁၀၀ လုံး (Packing Unit & Base Unit တွက်ချက်မှု)
- [ ] **၆.၂ Batch & Lot Tracking Data Model**
  - [ ] Batch Number, Manufacture Date, Expiration Date
- [ ] **၆.၃ FEFO (First-Expired, First-Out) Inventory Issuing Policy**
  - [ ] သက်တမ်းကုန်ခါနီး ပစ္စည်းများအား အရင်ရောင်းချစေခြင်း။
  - [ ] သက်တမ်းကုန်ပြီး ပစ္စည်းများအား POS အရောင်းတွင် အလိုအလျောက် ပိတ်ပင်ခြင်း (Server-enforced Blocking)။
- [ ] **၆.၄ Batch-Aware Inbound, Returns, Transfers & Recall Reports**
- [ ] **၆.၅ Pharmacy & Agriculture Demo Profiles and Verification Tests**

---

## Phase 7 — Local / LAN Deployment Edition

> **Entry Gate:** Cloud Edition တည်ငြိမ်ပြီး အင်တာနက်မလိုသော Offline/LAN စနစ် လိုအပ်သော ဆိုင်များရှိလာမှ စတင်မည်။

- [ ] **၇.၁ Clean-Machine Windows/XAMPP Installer Package**
- [ ] **၇.၂ LAN Multi-device Access (Cashier PC, Counter Tablet, Kitchen/Backoffice)**
- [ ] **၇.၃ Offline Signed License Verification (Public-key Cryptography)**
- [ ] **၇.၄ 1-Click Backup Package (Zip with Checksum) & Safe Restore Preflight**
- [ ] **၇.၅ Power-loss Recovery & SQLite/MySQL Auto-repair Verification**

---

## Phase 8 — Tier C: Restaurant Vertical Pack Prototype

> **Entry Gate:** စားသောက်ဆိုင် Pilot Customer အနည်းဆုံး (၂) ဦး သို့မဟုတ် သီးခြား ရင်းနှီးမြှုပ်နှံမှု ရှိမှ စတင်မည်။

- [ ] **၈.၁ Restaurant Domain Workflow Mapping**
  - [ ] Table & Zones Management (Dine-in / Takeaway / Delivery)
  - [ ] Kitchen Order Ticket (KOT) & Kitchen Display/Printer Routing
  - [ ] Menu Modifiers, Add-ons, Combo Meals
  - [ ] Bill Splitting & Bill Merging
- [ ] **၈.၂ Shared Core vs Vertical Pack Reuse Ratio Assessment**
  - [ ] Core POS Ledger နှင့် ၅၀% အထက် Reuse ဖြစ်/မဖြစ် တိုင်းတာပြီး သီးခြား Package အဖြစ် ဆက်လက်တည်ဆောက်ခြင်း။

---

## 🎯 Quality Gates & Sign-Off Criteria (အပိုင်းတိုင်း ပြီးမြောက်မှု စစ်ဆေးချက်)

Feature သို့မဟုတ် Phase တစ်ခုစီကို "ပြီးစီးပါပြီ (Done)" ဟု သတ်မှတ်ရန် အောက်ပါ ၇ ချက် ပြည့်စုံရပါမည် -

1. [ ] **Tenant Isolation:** ဆိုင်တစ်ခု၏ ဒေတာသည် အခြားဆိုင်သို့ မည်သည့်အခါမျှ မပေါက်ကြားခြင်း။
2. [ ] **Server-Side Enforcement:** Capability မရှိသော Feature ကို UI မှ ဖျောက်ထားရုံမက URL/API မှ ခေါ်ယူမှုကိုပါ Block လုပ်ထားခြင်း။
3. [ ] **Money & Inventory Integrity:** ငွေကြေးနှင့် ပစ္စည်း အရေအတွက် တွက်ချက်မှုများတွင် Float error မရှိဘဲ Double-entry Ledger စနစ်ဖြင့် မှန်ကန်ခြင်း။
4. [ ] **Audit Trail:** အရေးကြီးသော ပြောင်းလဲမှုအားလုံးတွင် Audit Log ရေးမှတ်ထားခြင်း။
5. [ ] **Automated Test Coverage:** သက်ဆိုင်ရာ Feature Test အသစ်များ ရေးသားပြီး Test Suite အားလုံး Pass ဖြစ်ခြင်း။
6. [ ] **Mobile/Desktop UX:** မြန်မာစာ ဖောင့်မှန်ကန်ပြီး မျက်နှာပြင် အားလုံးတွင် Overflow မဖြစ်ခြင်း။
7. [ ] **Documentation & Runbook:** အပြောင်းအလဲများကို Document တွင် မှတ်တမ်းတင်ပြီးဖြစ်ခြင်း။
