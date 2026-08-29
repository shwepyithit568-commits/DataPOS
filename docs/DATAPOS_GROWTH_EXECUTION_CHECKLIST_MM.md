# DataPOS Single-Codebase Growth — အဆင့်လိုက် အကောင်အထည်ဖော်မှု မာစတာ Checklist
**Document Type:** Execution Master Checklist & End-to-End Production Standards  
**Status:** Active Execution Baseline  
**Base Architecture:** [DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md](file:///d:/xmapp/htdocs/DataPOS/docs/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md)  
**Primary Constraints:** One Owner/Developer + AI Agents | Single Codebase | Myanmar SME Realities  
**Version:** 2.0.0 (End-to-End Production Craftsmanship Edition)

---

## 🎯 စစ်မှန်သော လုပ်ငန်းခွင်သုံး အဓိပ္ပာယ်သတ်မှတ်ချက် (Definition of Done)

Feature သို့မဟုတ် အပိုင်းတစ်ခုကို "ပြီးစီးပါပြီ (Done)" ဟု သတ်မှတ်ရန် အောက်ပါ **အဆင့် (၆) ဆင့်လုံး အပြည့်အစုံ** ပြီးမြောက်ရပါမည် (Backend ရေးရုံ၊ Test Pass ရုံဖြင့် မပြီးပါ)-

1. **Database & Schema:** Migration, Foreign Keys, Indexes နှင့် Data Integrity
2. **Domain Service & Engine:** Bcmath MMK precision, Double-entry ledger, Server-Side Authorization
3. **Admin Management UI:** စာရင်းသွင်း/ပြင်/ဖျက် (CRUD), Filter, Search, Validation Messages, Mobile/Desktop Responsive Layout
4. **POS Counter Experience:** Cashier စတင်ရောင်းချနိုင်မည့် Cart Interaction, Multi-UOM Picker, Batch/Expiry Modal, Scanner Auto-Detect
5. **Hardware & Printing:** 58mm/80mm ESC/POS Thermal Receipt, KOT မီးဖိုချောင် Print, Barcode Label Print
6. **Real-world Verification:** Browser မှတ်တမ်းနှင့် Cashier လက်တွေ့ရောင်းချမှု Flow စစ်ဆေးအောင်မြင်ခြင်း

---

## 📋 မာတိကာ (Table of Contents)

1. [Phase 0 — Baseline Lock & Safety Tagging (အတည်ပြုပြီး)](#phase-0--baseline-lock--safety-tagging)
2. [Phase 1 — Capability & Business Profile Foundation (အတည်ပြုပြီး)](#phase-1--capability--business-profile-foundation)
3. [Phase 2 — Storefront Decoupling & View Models (အတည်ပြုပြီး)](#phase-2--storefront-decoupling--view-models)
4. [Phase 3 — Storefront Theme Engine MVP (အတည်ပြုပြီး)](#phase-3--storefront-theme-engine-mvp)
5. [Phase 4 — Tier A Productization (Mobile, Retail, Repair Editions)](#phase-4--tier-a-productization)
6. [Phase 5 — Cloud Resale & Multi-Tenant Hardening](#phase-5--cloud-resale--multi-tenant-hardening)
7. [Phase 6 — Tier B: Batch, Expiry & Multi-UOM (Pharmacy & Agriculture)](#phase-6--tier-b-batch-expiry--multi-uom)
8. [Phase 7 — Local / LAN Deployment Edition](#phase-7--local--lan-deployment-edition)
9. [Phase 8 — Tier C: Restaurant Vertical Pack Prototype](#phase-8--tier-c-restaurant-vertical-pack-prototype)

---

## Phase 0 — Baseline Lock & Safety Tagging
- [x] Multi-Store Data Isolation Tests (Cross-store leaks block စစ်ဆေးခြင်း)
- [x] Demo Data Seeders & Safety Gate (`UAT_ALLOW_SEED_DEMO_DATA`)
- [x] Baseline Git Tag: `v1.0.0-baseline-locked`

---

## Phase 1 — Capability & Business Profile Foundation
- [x] `BusinessProfileRegistry` (Mobile, Retail, Agriculture, Pharmacy)
- [x] `Capability` Enum & Matrix
- [x] Blade Helper `store_can($capability)`
- [x] Git Tag: `v1.1.0-capability-foundation`

---

## Phase 2 — Storefront Decoupling & View Models
- [x] `StoreHeaderViewModel`, `ProductCardViewModel`, `CategoryFilterViewModel`
- [x] POS-Only Counter Mode Landing (`/store/{slug}`)
- [x] Decoupled Storefront Views (Non-electronics stores hide IMEI/Glass Finder)
- [x] Git Tag: `v1.2.0-storefront-decoupling`

---

## Phase 3 — Storefront Theme Engine MVP
- [x] `ThemeRegistry` & `ThemeManifest` (5 Curated Themes)
- [x] Font Stacks (Outfit, Inter, Pyidaungsu, Padauk) & Grid Density Mappings
- [x] Storefront Appearance Settings UI
- [x] Git Tag: `v1.3.0-theme-engine-mvp`

---

## Phase 4 — Tier A Productization (Mobile, Retail, Repair Editions)

### ၄.၁ Backend Engine & Services
- [x] `StoreOnboardingService` (Auto-provisions Categories, Brands, Default Settings)
- [x] Dedicated Store Owner User Account Provisioning (`provisionOwnerAccount`)
- [x] `HardwareMatrixService` (58mm/80mm ESC/POS commands & scanner specs)

### ၄.၂ Admin & POS UI Workflows (ကျန်ရှိသည့် အပိုင်းများ)
- [x] Platform Store Creation Form with Edition Selector (`admin/stores/create.blade.php`)
- [ ] **Live Hardware Testing Dashboard:** Browser မှနေ၍ USB/LAN/Bluetooth Thermal Printer သို့ ESC/POS Test Receipt တိုက်ရိုက် Print ထုတ်စမ်းသပ်သည့် UI Screen
- [ ] **Barcode Scanner Diagnostics View:** ကောင်တာ စကင်နာ ဖတ်နှုန်းနှင့် Keycode Emulation စစ်ဆေးသည့် Interactive Tool

---

## Phase 5 — Cloud Resale & Multi-Tenant Hardening

### ၅.၁ Backend Engine & Services
- [x] `SubscriptionPlanService` (Starter, Standard, Enterprise Limits)
- [x] `SupportAccessService` & `SupportModeController` (Mandatory Reason & Audit Log)
- [x] `StoreDataExportService` (1-Click Data Portability Export)

### ၅.၂ Admin UI & Workflows (ကျန်ရှိသည့် အပိုင်းများ)
- [x] Persistent Top Sticky Support Mode Warning Banner with Exit Button
- [x] Admin Store Settings Data Export Download Button
- [ ] **Platform Super Admin Store Management Console:** ဆိုင်အလိုက် Subscription Plan ပြောင်းလဲခြင်းနှင့် Support Mode ခလုတ်ပါဝင်သော Dashboard UI
- [ ] **Plan Limit Reached In-App Notice:** ကုန်ပစ္စည်း/ဆိုင်ခွဲ သတ်မှတ်အရေအတွက် ပြည့်ပါက ပြသပေးမည့် Upgrade Notice Modal

---

## Phase 6 — Tier B: Batch, Expiry & Multi-UOM (Pharmacy & Agriculture)

### ၆.၁ Backend Engine & Models
- [x] `product_units` & `product_batches` database tables
- [x] `ProductUnit` & `ProductBatch` models
- [x] `UnitConversionService` (Base quantity & Unit price conversions)
- [x] `BatchTrackingService` (FEFO allocation, Expired sale blocking, Recall reports)

### ၆.၂ Admin Management UI
- [ ] **Admin Product Units Management:** ပစ္စည်းတစ်ခုချင်းစီအောက်တွင် ယူနစ်ခွဲများ (ဥပမာ - ၁ ဖာ = ၁၀ ကတ် = ၁၀၀ လုံး) စာရင်းသွင်း/ပြင်/ဖျက်သည့် UI
- [ ] **Admin Product Batches Management:** Batch Number, MFG Date, EXP Date, Stock ထည့်သွင်း/ပြင်ဆင်သည့် UI
- [ ] **Expiry Alert Dashboard Widget:** ရက် ၃၀/၆၀ အတွင်း သက်တမ်းကုန်မည့် ဆေးဝါးများ စာရင်းဇယားနှင့် အရောင်အလိုက် သတိပေးချက် UI

### ၆.၃ POS Counter Cashier Experience
- [ ] **POS Cart Multi-UOM Selector:** ကောင်တာတွင် ဆေးဝါးရွေးပြီးသည်နှင့် **[လုံး / ကတ် / ဖာ]** ခလုတ်ဖြင့် ယူနစ်ပြောင်းရောင်းနိုင်သော Widget
- [ ] **POS Cart Batch Selection Modal:** ဆေးဝါး/မြေဩဇာ ရောင်းချချိန်တွင် FEFO အလိုက် အလိုအလျောက် သက်တမ်းအနီးဆုံး Batch ကို ရွေးပေးခြင်း သို့မဟုတ် Cashier က Batch နံပါတ် ရွေးနိုင်ခြင်း
- [ ] **POS Expired Batch Blocking Notice:** သက်တမ်းကုန်ဆေးဝါးအား စကင်ဖတ်မိပါက ကောင်တာမျက်နှာပြင်တွင် အနီရောင် Alert တက်ပြီး ရောင်းချခွင့် ပိတ်ပင်ခြင်း

---

## Phase 7 — Local / LAN Deployment Edition

### ၇.၁ Backend Engine & Services
- [x] `OfflineLicenseService` (HMAC-SHA256 Signed Offline License Verification)
- [x] `LanNetworkService` (Local LAN IP discovery & Terminal URL generator)
- [x] `LocalBackupPackageService` (SHA-256 Checksum Manifest Zip Package)

### ၇.၂ Local / LAN UI & Workflows
- [ ] **LAN Connection QR Code & Terminal Guide View:** Settings တွင် ဆာဗာ IP နှင့် ကောင်တာ Tablet များ ချိတ်ဆက်ရန် QR Code ပြသပေးသည့် မျက်နှာပြင်
- [ ] **Offline License Activation Screen:** အင်တာနက်မရှိဘဲ License Key ထည့်သွင်း အသက်သွင်းနိုင်သည့် Admin License UI
- [ ] **1-Click Backup & Safe Restore UI:** Backup Zip ဒေါင်းလုဒ်ရယူခြင်းနှင့် Preflight Checksum စစ်ဆေးပြီးမှ ပြန် Restore လုပ်ပေးသည့် Screen

---

## Phase 8 — Tier C: Restaurant Vertical Pack Prototype

### ၈.၁ Backend Engine & Models
- [x] `restaurant_tables` & `kitchen_order_tickets` database tables
- [x] `RestaurantTable` & `KitchenOrderTicket` models
- [x] `RestaurantService` (Table lifecycle, KOT creation, KOT ESC/POS print string, Bill splitting calculation)

### ၈.၂ POS & Kitchen UI Experience
- [ ] **Restaurant Table Floor Plan UI (စားပွဲပုံစံ မြေပုံ Grid):** Indoor, VIP, Outdoor ဇုန်အလိုက် စားပွဲများ၏ အခြေအနေ (Available - အစိမ်း, Occupied - အနီ, Reserved - အဝါ) ကို မျက်မြင် ကြည့်ရှုနိုင်သည့် POS Table Grid
- [ ] **Table-to-Cart POS Ordering:** စားပွဲကို နှိပ်လိုက်သည်နှင့် အော်ဒါစာရင်း Cart ထဲသို့ တိုက်ရိုက်ရောက်ရှိခြင်း
- [ ] **Kitchen Order Modifiers UI:** ဟင်းပွဲတစ်ခုချင်းစီအောက်တွင် "အစပ်လျှော့"၊ "ရေခဲမထည့်"၊ "အရည်သီးသန့်" စသည့် Modifiers ထည့်သွင်းနိုင်သည့် Modal
- [ ] **Kitchen Display / KOT Print Button:** အော်ဒါတင်သည်နှင့် မီးဖိုချောင်သို့ KOT Print ထုတ်ပေးသည့် Action
- [ ] **Bill Splitting Dialog:** စားသုံးသူများ ဘေလ်ခွဲရှင်းလိုပါက ငွေပမာဏ ခွဲဝေတွက်ချက်ပေးသည့် Modal Dialog
