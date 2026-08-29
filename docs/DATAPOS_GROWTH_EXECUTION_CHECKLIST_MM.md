# DataPOS Single-Codebase Growth — အဆင့်လိုက် အကောင်အထည်ဖော်မှု မာစတာ Checklist
**Document Type:** Execution Master Checklist & Quality Gates  
**Status:** Active Execution Baseline  
**Master Architecture Reference:** [DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md](file:///d:/xmapp/htdocs/DataPOS/docs/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md)  
**Primary Constraints:** One Owner/Developer + AI Agents | Single Codebase | Myanmar SME Realities  
**Version:** 3.0.0 (Full Alignment with Growth Plan — Phase Exit Criteria Edition)

---

## 1. မပြောင်းလဲရမည့် အခြေခံမူများ (Non-Negotiable Rules)

1. **One Repository / Single Core:** Authentication, Tenant Isolation, Sales, Inventory Ledger, Finance, Audit, Backup Core ကို edition တစ်ခုချင်း copy မပြုလုပ်ရ၊ branch ကြာရှည်ခွဲမထားရ။
2. **No Skin-Deep Done:** Database Table/Service/Test သက်သက် ရေးရုံဖြင့် "Done" မကြေညာရ။ Exit Criteria ၆ ချက်လုံး ပြည့်မှသာ Phase/Feature တစ်ခုကို ပြီးစီးသည်ဟု မှတ်တမ်းတင်ရမည်။
3. **WIP Limit = 1 Major Vertical:** တစ်ချိန်တည်းတွင် major vertical တစ်ခုထက်ပို၍ မဆောက်ရ။
4. **No Fake Readiness:** Required workflow/data integrity tests မပြည့်မချင်း vertical တစ်ခုကို supported ဟု ဖောက်သည်ထံ မရောင်းရ။
5. **No Web Code Uploads:** Store Owner ကို PHP, Blade, JS, Arbitrary CSS upload ခွင့်မပြုရ။

---

## 2. စစ်မှန်သော "Done" အဓိပ္ပာယ် (Definition of Done — §8.4 Growth Plan)

Feature/Phase တစ်ခုကို "ပြီးစီးပါပြီ (Done)" ဟု သတ်မှတ်ရန် အောက်ပါ **ခြောက်ချက်လုံး** ပြည့်မှ Done-

| # | စစ်ဆေးချက် | မပြည့်ပါက |
|---|---|---|
| ၁ | **Database & Schema:** Migration, FK, Indexes, Integrity | ✗ Not Done |
| ၂ | **Domain Service & Engine:** Bcmath MMK, Ledger, Server-Side Auth | ✗ Not Done |
| ၃ | **Admin Management UI:** CRUD, Search, Filter, Validation, Responsive | ✗ Not Done |
| ၄ | **POS Counter Experience:** Live Cart, UOM Picker, Batch/Expiry Modal, Scanner | ✗ Not Done |
| ၅ | **Hardware & Printing:** 58mm/80mm ESC/POS, KOT, Barcode Label | ✗ Not Done |
| ၆ | **Real-world Verification:** Browser-tested, Cashier flow passed, Pilot confirmed | ✗ Not Done |

---

## 3. Product Architecture Layers (§2 Growth Plan)

```text
One Repository
└── DataPOS Core (Ledger, Auth, Multi-Store, POS Sales, Inventory, Finance, Audit)
    ├── Capability System (named capabilities, server-enforced at routes/policies/service/UI)
    ├── Business Profiles (Presets: mobile_electronics, general_retail, repair_service, pharmacy, agriculture, restaurant)
    ├── Vertical Packs
    │   ├── Tier A: Mobile/Electronics Pack, General Retail, Repair/Service
    │   ├── Tier B: Pharmacy/Agriculture Batch Pack
    │   └── Tier C: Restaurant Operations Pack (demand-gated)
    ├── Storefront Theme Engine (Curated Presets: Marketplace Pro, Retail Trust, Emerald Fresh, Midnight Tech, Sunset Warm)
    ├── Cloud Multi-Tenant Hardening (Subscription Tiers, Support Mode, Quotas, Data Portability)
    └── Local / Standalone LAN Edition (Offline HMAC Licensing, Quick-Connect QR, Checksum Preflight Backup)
```

---

## 📋 မာတိကာ (Table of Contents)

1. [Phase 0 — Baseline Lock & Safety Freeze](#phase-0--baseline-lock--safety-freeze)
2. [Phase 1 — Capability & Business Profile Foundation](#phase-1--capability--business-profile-foundation)
3. [Phase 2 — Storefront Decoupling & View Models](#phase-2--storefront-decoupling--view-models)
4. [Phase 3 — Storefront Theme Engine MVP](#phase-3--storefront-theme-engine-mvp)
5. [Phase 4 — Tier A Productization (Mobile, Retail, Repair)](#phase-4--tier-a-productization-mobile-retail-repair)
6. [Phase 5 — Cloud Resale & Multi-Tenant Hardening](#phase-5--cloud-resale--multi-tenant-hardening)
7. [Phase 6 — Tier B: Pharmacy/Agriculture Batch, Expiry & Multi-UOM](#phase-6--tier-b-pharmacyagriculture-batch-expiry--multi-uom)
8. [Phase 7 — Local / LAN Deployment Edition](#phase-7--local--lan-deployment-edition)
9. [Phase 8 — Tier C: Restaurant Operations Pack (Demand-Gated)](#phase-8--tier-c-restaurant-operations-pack-demand-gated)
10. [Quality Gates Across All Phases](#quality-gates-across-all-phases)

---

## Phase 0 — Baseline Lock & Safety Freeze

> **Goal (§Phase 0 Growth Plan):** လက်ရှိ project ကို feature ထပ်မတိုးမီ ယုံကြည်ရသော Multi-Store Baseline ပြုလုပ်ရန်။

### ၀.၁ Automated Test Baseline
- [x] Full test suite run & recorded (baseline test count documented)
- [x] No unknown migration failures
- [x] Store isolation blockers resolved or explicitly tracked

### ၀.၂ Multi-Store Data Isolation Audit
- [x] Products, Customers, Orders, Debt, Audit Logs cross-store leak စစ်ဆေးခြင်း
- [x] `tests/Feature/MultiStoreIsolationAuditTest.php` — passes all scenarios

### ၀.၃ Demo Data Safety Gates
- [x] `UAT_ALLOW_SEED_DEMO_DATA` safety gate ဖြင့် Production database မတော်တဆ wipe ဖြစ်ခြင်းမှ ကာကွယ်ခြင်း
- [x] Default Demo Store, Store Owner, Cashier, Customer accounts (`UatDemoStoreSeeder`)

### ၀.၄ Architecture Decisions Approved
- [x] Capability/Profile/Theme terminology locked in ADR
- [x] Supported editions and non-goals owner-approved
- [x] Rollback point/tag available: `v1.0.0-baseline-locked`

### Phase 0 Exit Criteria ✅
- [x] Test baseline documented
- [x] No unknown migration failures
- [x] Store isolation audit complete
- [x] Architecture decisions owner-approved
- [x] Git tag `v1.0.0-baseline-locked` exists

---

## Phase 1 — Capability & Business Profile Foundation

> **Goal (§Phase 1 Growth Plan):** Modules ကို UI hide/show ထက်ပိုသော server-enforced capability system ဖြင့် ထိန်းရန်။

### ၁.၁ Capability Registry & Resolver Engine
- [x] `App\Capabilities\Capability` Enum & named capability matrix (`catalog.variants`, `inventory.serial_tracking`, `inventory.batch_tracking`, `inventory.expiry_tracking`, `inventory.multi_uom`, `service.repair_jobs`, `restaurant.tables`, `restaurant.kitchen_orders`)
- [x] `App\Capabilities\StoreCapabilityResolver` — server-side evaluation
- [x] Middleware/Policy enforcement at routes, controllers, service layer

### ၁.၂ Business Profile Registry
- [x] `App\BusinessProfiles\BusinessProfileRegistry` (`mobile_electronics`, `general_retail`, `repair_service`)
- [ ] `pharmacy`, `agriculture` profiles — Phase 6 entry gate
- [ ] `restaurant` profile — Phase 8 entry gate

### ၁.၃ Server-Side Authorization & UI Helpers
- [x] Blade helper `store_can($capability)` — UI-level gate
- [x] Admin Sidebar capability-driven visibility
- [x] Route/Controller Policy checks (server-enforced, not just hidden)

### ၁.၄ Backward Compatibility
- [x] Existing stores retain current access after migration (no regressions)
- [x] Disabled capability hides data (read-only) but does not delete historical records

### ၁.၅ Verification
- [x] `tests/Feature/Admin/CapabilityAndProfileFoundationTest.php` (7 passed tests)
- [x] Cross-store capability writes fail
- [x] Disabled capability cannot be reached via direct URL/API
- [x] Git tag: `v1.1.0-capability-foundation`

### Phase 1 Exit Criteria ✅
- [x] Existing stores retain current access after migration
- [x] Disabled capability: inaccessible via direct URL/API
- [x] Cross-store capability write: fails
- [x] Profile change: no historical data deleted
- [x] Feature tests cover manager/staff/platform roles

---

## Phase 2 — Storefront Decoupling & View Models

> **Goal (§Phase 2 Growth Plan):** Mobile-specific storefront ကို industry-neutral data/component contracts အဖြစ် ခွဲထုတ်ရန်။

### ၂.၁ Mobile-Specific Hardcoded Sections Removed
- [x] Electronics-specific: IMEI/Glass Finder/Device Repair shortcuts Pharmacy/Retail stores တွင် မပေါ်တော့ခြင်း
- [x] Neutral fallback copy for all profile types

### ၂.၂ Industry-Neutral Shared View Models
- [x] `StoreHeaderViewModel` (Store metadata, branding, navigation links)
- [x] `ProductCardViewModel` (Pricing tiers, discount ribbons, badging)
- [x] `CategoryFilterViewModel` (Empty category pruning, dynamic facets)
- [x] Storefront pages render without theme-specific database queries

### ၂.၃ POS-Only Counter Mode
- [x] Storefront မဖွင့်ထားသော ဆိုင်အတွက် `/store/{slug}` ဝင်ပါက Counter Landing ပြသပြီး staff ကို POS တိုက်ရိုက် redirect

### ၂.၄ Verification
- [x] `tests/Feature/Storefront/StorefrontDecouplingTest.php` (7 passed tests)
- [x] General Retail profile shows no mobile-only features
- [x] Myanmar/English localization parity tested
- [x] Git tag: `v1.2.0-storefront-decoupling`

### Phase 2 Exit Criteria ✅
- [x] General Retail profile: no mobile-only features visible
- [x] Storefront pages render without theme-specific queries
- [x] Current Mobile storefront behavior remains supported
- [x] Myanmar/English localization parity passes

---

## Phase 3 — Storefront Theme Engine MVP

> **Goal (§Phase 3 Growth Plan):** Complete storefront theme bundle selection with safe per-store branding.

### ၃.၁ Theme Registry & Manifest System
- [x] `App\Themes\ThemeRegistry` & `App\Themes\ThemeManifest`
- [x] 5 Curated Presets: `marketplace_pro`, `retail_trust`, `emerald_fresh`, `midnight_tech`, `sunset_warm`
- [x] Safe Typography Font Stacks (Outfit, Inter, Pyidaungsu, Padauk, System UI)
- [x] Grid Density Layout Mappings (compact, comfortable, spacious)

### ၃.၂ Theme Configuration & Publishing
- [x] Published theme configuration per store
- [x] **Draft configuration isolated from the public storefront** — `store_theme_drafts` table + `ThemeDraftService` (2026-08-29, T2 complete): draft save က published `storefront_settings` ကို ဘယ်တော့မှ မပြောင်းပါ
- [x] Color/logo/font/banner customization (CSS Custom Properties)
- [x] Publish transaction with audit logging
- [x] **Published storefront response cache invalidation** — publish/rollback commit ပြီးတိုင်း target store ရဲ့ public pages ကို `max-age=0` revalidation window (90s) ဖြင့် ချက်ချင်းအသစ်ပြသည် (`ThemeRevisionCommitted` event + `InvalidateStorefrontCache` listener; `Cache::flush()` မသုံး — 2026-08-29, T4)
- [x] **Revision History & Rollback:** Theme ကိုပြောင်းပြီးပါက ယခင် published revision သို့ ပြန် rollback ပြုလုပ်နိုင်ခြင်း — rollback ပြီးတိုင်း draft ကိုလည်း restored state မှ reset လုပ်သည်

### ၃.၃ Storefront Appearance Settings UI
- [x] Admin Appearance Settings (`admin/settings/sections/appearance.blade.php`) — Preset, Font, Density, Colors ရွေးချယ်နိုင်ခြင်း
- [x] **Real-time Live Preview:** Settings တွင် အရောင်/ဖောင့်ပြောင်းလျှင် Draft autosave → Preview auto-reload ဖြင့် Save/Publish မလုပ်မီ Storefront မည်သို့ပြောင်းသွားမည်ကို ချက်ချင်းမြင်နိုင်ခြင်း (T3, 2026-08-29)
- [x] **Mobile Preview Viewport:** Desktop (1440) / Tablet (768) / Mobile (390) ခလုတ်ဖြင့် preview size ပြောင်းကြည့်နိုင်ခြင်း (T3, 2026-08-29)

### ၃.၄ Theme Polish & Integration
- [x] **Admin Dashboard Brand Accent Sync:** ဆိုင်၏ Industry Theme အလိုက် Admin Sidebar active link ၏ Brand Accent ပြောင်းပေးခြင်း — `--admin-accent` (store theme primary), semantic colors မပြောင်း (2026-08-30, T8)
- [x] **POS Counter High-Contrast & Dark Mode Toggle:** Cashier ၁-ချက်နှိပ်ရုံဖြင့် Standard Light ↔ High-Contrast Daylight ↔ OLED Dark Mode ပြောင်းနိုင်ခြင်း — per-device localStorage persistence (2026-08-30, T8)

### ၃.၅ Verification
- [x] `tests/Feature/Admin/StorefrontThemeEngineTest.php` (12 passed tests) + `tests/Feature/Admin/ThemeDraftTest.php` (19 tests) + `tests/Feature/Admin/ThemePreviewTest.php` (7 tests) + `tests/Feature/Admin/ThemeCacheInvalidationTest.php` (5 tests) + `tests/Feature/Admin/ThemeOnboardingRecommendationTest.php` (7 tests) + `tests/Feature/Admin/ThemeGovernanceTest.php` (11 tests) + `tests/Feature/Admin/ThemeAdminPosPolishTest.php` (5 tests) + `tests/Unit/Themes/ThemeConfigTest.php` (19 tests) + `tests/Unit/Themes/ThemeComponentsTest.php` (5 tests) + `tests/Feature/Storefront/ThemeComponentRenderTest.php` (4 tests) — total 94 theme-related tests, full suite 1553 tests pass (2026-08-30)
- [x] One store can preview without affecting public storefront — Real Isolated Preview (T3 complete, 2026-08-29): draft config ကို request-scoped `ThemeContext` ဖြင့် inject; anonymous Customer က published revision ကို ဆက်မြင်သည်
- [x] Publishing one store never changes another store
- [ ] **Mobile viewport has no horizontal overflow/overlap — ကျန်ရှိ Themes (Emerald Fresh, Midnight Tech, Sunset Warm) အတွက် Browser viewport test ပြုလုပ်ရန်**
- [x] Git tag: `v1.3.0-theme-engine-mvp`

### Phase 3 Exit Criteria (ကျန်ရှိသောအပိုင်းများ)
- [x] Rollback restores exact previous published revision
- [ ] Both themes pass all customer-facing storefront routes
- [ ] Mobile viewport: no horizontal overflow on all 5 themes
- [ ] Theme selection survives app restart/deploy

---

## Phase 4 — Tier A Productization (Mobile, Retail, Repair)

> **Goal (§Phase 4 Growth Plan):** ပထမဆုံးရောင်းချမည့် editions ၃ ခုကို reliable product packages အဖြစ်ပြင်ရန်။

### ၄.၁ Store Onboarding & Edition Preset Provisioning
- [x] `App\Services\StoreOnboardingService` — Auto-provisions Categories, Brands, Default Settings per edition
- [x] Dedicated Store Owner Account Provisioning (`provisionOwnerAccount`)
- [x] Platform Store Creation Form with Edition Selector (`admin/stores/create.blade.php`)

### ၄.၂ Hardware Matrix & Printer Testing
- [x] `App\Services\HardwareMatrixService` — 58mm/80mm ESC/POS Thermal Receipt formatting, Printer/Scanner specs
- [ ] **Live Hardware Testing Dashboard:** Browser မှ USB/LAN/Bluetooth Thermal Printer သို့ ESC/POS Test Receipt တိုက်ရိုက် print ထုတ်စမ်းသပ်နိုင်သည့် Admin UI Screen
- [ ] **Barcode Scanner Diagnostics View:** ကောင်တာ Scanner ဖတ်နှုန်းနှင့် Keycode Emulation စစ်ဆေးသည့် Interactive Tool (Keypress capture, scan speed test)

### ၄.၃ Edition Onboarding Wizard & Demo Content
- [x] Demo Stores with role accounts (Store Owner, Manager, Cashier, Customer)
- [ ] **Edition Onboarding Step-by-Step Wizard:** ဆိုင်ရှင်သစ်တစ်ဦး source code မတိုက်ဘဲ store create → edition select → demo data → ready ထိ guided UI wizard

### ၄.၄ Backup/Restore Runbook & Pilot Validation
- [ ] **Backup/Restore Runbook:** ဆိုင်ရှင်ကိုယ်တိုင် data backup/restore ပြုလုပ်နိုင်သည့် Step-by-step guide
- [ ] **Real Pilot Store:** At least one real pilot shop completes full daily workflow (daily closing, stock reconciliation matches manual records)

### ၄.၅ Verification
- [x] `tests/Feature/Admin/StoreOnboardingAndEditionTest.php` (5 passed tests)
- [x] Git tag: `v1.4.0-tier-a-productization`

### Phase 4 Exit Criteria (ကျန်ရှိသောအပိုင်းများ)
- [ ] At least one real pilot shop completes daily workflow
- [ ] Daily closing and stock reconciliation match manual records
- [ ] Backup/restore drill passes
- [ ] Owner can onboard a store without editing source code
- [ ] Critical defects have documented resolution/rollback

---

## Phase 5 — Cloud Resale & Multi-Tenant Hardening

> **Goal (§Phase 5 Growth Plan):** Multi-tenant cloud sales ကို လုံခြုံတည်ငြိမ်အောင်လုပ်ရန်။

### ၅.၁ Subscription Plan Quotas & Limits
- [x] `App\Services\SubscriptionPlanService` (Starter, Standard, Enterprise — Max Products, Max Branches)
- [x] Store schema migration: `subscription_tier`, `max_products`, `max_branches` columns
- [ ] **Plan Limit Enforcement at Service Layer:** ကုန်ပစ္စည်း/ဆိုင်ခွဲ create ပြုလုပ်ရာတွင် Plan limit ကျော်ပါက server-side block ဖြင့် ပိတ်ပင်ခြင်း
- [ ] **In-App Upgrade Notice Modal:** Plan limit ပြည့်ပါက ပြသပေးမည့် Upgrade Notice Modal Dialog

### ၅.၂ Support Mode Access & Audit
- [x] `App\Services\SupportAccessService` & `SupportModeController` — Mandatory Reason, Auto-expiry, AuditLog
- [x] Persistent Top Sticky Support Mode Warning Banner with Exit Button (Admin Layout)
- [ ] **Platform Super Admin Store Management Console:** ဆိုင်အလိုက် Subscription Plan ပြောင်းလဲခြင်း/Support Mode activate ပြုလုပ်နိုင်သော Platform Admin Dashboard UI (ဆိုင်စာရင်း, Plan badge, Support Mode button)

### ၅.၃ Store Data Export & Portability
- [x] `App\Services\StoreDataExportService` — JSON export of Catalog, Inventory, Customers, Debt, Orders, Settings
- [x] Admin Store Settings Data Export Download Button
- [ ] **Data Export Progress & Download Notification:** ဆိုင်ရှင် export ပြုလုပ်သည်နှင့် Background job မှ export file ပြင်ဆင်ပြီး Download button/notification ပေါ်လာခြင်း

### ၅.၄ Operational Safety
- [ ] **Rate Limits & Upload Quotas:** File upload size limits enforced per plan tier
- [ ] **Store Offboarding Workflow:** ဆိုင်တစ်ဆိုင်ကို ပိတ်သိမ်းသည်နှင့် data retain/delete policy အသေးစိတ် UI wizard

### ၅.၅ Verification
- [x] `tests/Feature/Admin/MultiTenantCloudHardeningTest.php` (5 passed tests)
- [x] Tenant isolation test suite passes
- [ ] **Provisioning and offboarding repeatable (manual drill documented)**
- [x] Support access is reason-bound and audited
- [x] Git tag: `v1.5.0-cloud-resale-hardening`

### Phase 5 Exit Criteria (ကျန်ရှိသောအပိုင်းများ)
- [ ] Tenant isolation test suite: full scenario coverage
- [ ] Provisioning and offboarding: repeatable and documented
- [ ] Restore point objectives: documented and tested
- [ ] Deployment rollback: tested

---

## Phase 6 — Tier B: Pharmacy/Agriculture Batch, Expiry & Multi-UOM

> **Entry Gate (§Phase 6 Growth Plan):** Tier A editions stable, at least one validated customer requirement set from Pharmacy/Agriculture pilot.

### ၆.၁ Database Schema & Models
- [x] `product_units` & `product_batches` migration (`2026_08_29_000004`)
- [x] `App\Models\ProductUnit` (packing hierarchy, conversion factors, price override)
- [x] `App\Models\ProductBatch` (lot/batch number, MFG date, EXP date, `isExpired()`, `isExpiringSoon()`)
- [x] Relationships on `Product`: `units()`, `batches()`

### ၆.၂ Domain Services
- [x] `App\Services\UnitConversionService` (Packing factor, Base quantity, Unit price conversions)
- [x] `App\Services\BatchTrackingService` (FEFO allocation, Server-enforced expiry blocking, 30-day expiry alert, Batch recall report)

### ၆.၃ Admin Management UI — ❗ **ကျန်ရှိသည် (Backend Foundation ပြီးသာ)**
- [ ] **Admin Product Units Manager:** ပစ္စည်းတစ်ခုချင်းစီ product edit page တွင် ယူနစ်ခွဲများ (ဥပမာ - ၁ ဖာ = ၁၀ ကတ် = ၁၀၀ လုံး) CRUD table, `packing_factor`, `price_override` ပါဝင်ခြင်း
- [ ] **Admin Product Batches Manager:** Products > Batches tab/page တွင် Batch Number, MFG Date, EXP Date, Initial Stock, Current Stock ပြသပေးသော CRUD table
- [ ] **Expiry Alert Dashboard Widget:** Admin Dashboard တွင် ရက် ၃၀/၆၀ အတွင်း သက်တမ်းကုန်မည့် ဆေးဝါး/မြေဩဇာ စာရင်းဇယားနှင့် အရောင်အဆင့်သတ်မှတ်ချက် (ပြာ = 60d, လိမ္မော် = 30d, အနီ = expired) ပါဝင်သော Widget
- [ ] **Batch Recall Report Page:** Batch ID တစ်ခုကို ရှာဖွေလိုက်ပါက ထို Batch မှ မည်သည့် Sale/Transfer/Return တွင် ထွက်ခဲ့သည်ကို တင်ပြပေးသော Report

### ၆.၄ POS Counter Cashier Experience — ❗ **ကျန်ရှိသည် (Backend Foundation ပြီးသာ)**
- [ ] **POS Cart Multi-UOM Selector Widget:** ဆေးဝါးရွေးပြီးသည်နှင့် `[လုံး × 500 ကျပ် | ကတ် × 50 ကျပ် | ဖာ × 10 ကျပ်]` ပုံစံ ခလုတ်တန်းပေါ်လာပြီး Cashier ရွေးချယ်သည်နှင့် Cart price/qty အလိုအလျောက် update ဖြစ်ခြင်း
- [ ] **POS Cart Batch Selection Modal:** Product ကို cart ထဲထည့်သောအခါ FEFO အလိုက် Available Batches dropdown ပေါ်လာပြီး EXP date/qty ပြသပေးကာ Cashier ရွေးချယ်နိုင်ခြင်း (ညှိနှိုင်းမရောင်းချသင့်သော Batch ကို disabled ဖြင့် ပြသခြင်း)
- [ ] **POS Expired Batch Blocking Notice:** Barcode scan ဖြင့် သက်တမ်းကုန်ဆေးဝါး ဝင်လာပါက Cart ထဲမထည့်ဘဲ `❌ သက်တမ်းကုန်ဆီး — ရောင်းချခွင့်မရှိပါ` alert ပြပေးခြင်း (keyboard shortcut ပါ bypass မရနိုင်ခြင်း)

### ၆.၅ Batch-Aware Receiving/Returns/Transfers
- [ ] **Purchase Receiving ↔ Batch Link:** Supplier မှ ပစ္စည်းဝင်ချိန်တွင် Batch Number/MFG/EXP Date ထည့်သွင်းနိုင်ပြီး `product_batches` stock ကို သီးခြား update ဖြစ်ခြင်း
- [ ] **Returns Batch Preservation:** Customer return တွင် မည်သည့် Batch မှ ပြန်ရောက်သည်ကို record ထိန်းသိမ်းခြင်း

### ၆.၆ Pharmacy/Agriculture Demo & Pilot
- [ ] **Demo Store Seeder for Pharmacy:** ဆေးဝါး Category, Products with Batches, EXP dates, Units (လုံး/ကတ်/ဖာ) ပါဝင်သော Sample Data
- [ ] **Real Pharmacist/Agriculture Pilot:** Actual pilot store မှ daily workflow ကို complete ပြုလုပ်ပြီး verify ပြုလုပ်ခြင်း

### ၆.၇ Verification
- [x] `tests/Feature/POS/BatchExpiryAndMultiUomTest.php` (5 passed tests)
- [ ] Batch quantity reconciles through full lifecycle (receive → sell → return → transfer)
- [ ] Expired stock policy enforced server-side (not just UI hidden)
- [ ] Returns and transfers preserve batch identity
- [x] Git tag: `v1.6.0-batch-expiry-multi-uom`

### Phase 6 Exit Criteria (ကျန်ရှိသောအပိုင်းများ)
- [ ] Batch quantity reconciles through full lifecycle
- [ ] Expired stock policy: server-side enforced
- [ ] Returns/transfers preserve batch identity
- [ ] Real pilot owner verifies daily workflow

---

## Phase 7 — Local / LAN Deployment Edition

> **Entry Gate (§Phase 7 Growth Plan):** Cloud edition operationally stable, paying demand for offline/local installation confirmed.

### ၇.၁ Core Services
- [x] `App\Services\OfflineLicenseService` — HMAC-SHA256 Signed Envelope, Expiry, Store Slug, Machine Fingerprint validation
- [x] `App\Services\LanNetworkService` — Local LAN IP discovery, Terminal & Tablet POS URL generator
- [x] `App\Services\LocalBackupPackageService` — Zip package with `manifest.json` SHA-256 Checksums, Preflight archive verification

### ၇.၂ Local/LAN UI & Workflows — ❗ **ကျန်ရှိသည် (Backend Foundation ပြီးသာ)**
- [ ] **LAN Connection QR Code & Terminal Guide:** Store Settings တွင် ဆာဗာ LAN IP နှင့် QR Code ပြသပေးသည့် "ကောင်တာ Tablet ချိတ်ဆက်နည်း" Guide Page
- [ ] **Offline License Activation Screen:** Platform Admin မှ license key ထုတ်ပေးပြီး Store Admin က offline activation ပြုလုပ်နိုင်သည့် UI (signature validation result feedback ပြသပေးခြင်း)
- [ ] **1-Click Backup Download & Safe Restore UI:** Admin Settings တွင် Backup Package ဒေါင်းလုဒ်ခလုတ်နှင့် Restore ပြုလုပ်ရာတွင် Checksum Preflight စစ်ဆေးပြီး ‌ "✅ စစ်ဆေးမှုအောင်မြင်ပါသည် — Restore ဆက်လုပ်မည်" confirmation modal ပြသပေးခြင်း

### ၇.၃ Installer & System Safety
- [ ] **Clean-Machine Install Test:** XAMPP/Laragon ပေါ်တွင် fresh install test (no pre-existing data) pass
- [ ] **Power/Network Interruption Recovery:** Unexpected shutdown ပြန်ဖွင့်သည့်အခါ POS session ပြန်ဆက်နိုင်မှု/shifted data integrity test

### ၇.၄ Remote Support Procedure
- [ ] **Remote Support Runbook:** Owner consent ဖြင့် remote support ပြုလုပ်ရာတွင် ဘာကို access ရပြီး ဘာ မရသည်ကို document တွင် မှတ်တမ်းတင်ထားခြင်း

### ၇.၅ Verification
- [x] `tests/Feature/Admin/LocalLanDeploymentEditionTest.php` (4 passed tests)
- [ ] Private signing key is absent from customer installations (verify in deployment package)
- [x] Git tag: `v1.7.0-local-lan-deployment`

### Phase 7 Exit Criteria (ကျန်ရှိသောအပိုင်းများ)
- [ ] Clean-machine install test passes
- [ ] Power/network interruption recovery tested
- [ ] Backup/restore and upgrade/rollback pass
- [ ] Private signing key absent from customer installations
- [ ] Support runbook usable by non-developer operator

---

## Phase 8 — Tier C: Restaurant Operations Pack (Demand-Gated)

> **Entry Gate (§Phase 8 Growth Plan):** At least two serious paying pilot customers **or** funded implementation request. Restaurant pack must prove ≥50% shared-core reuse before separate repository decision.

### ၈.၁ Pre-Build: Field Interviews & Requirements Sign-off
- [ ] **Actual Restaurant Pilot Workflow Documented:** စားသောက်ဆိုင် Pilot တစ်ဆိုင်၏ တစ်ရက် full workflow (order taking, cooking, billing, closing) ကို ကွင်းဆင်းမှတ်တမ်းတင်ခြင်း
- [ ] **Data Model ADR Approved:** restaurant_tables, kitchen_order_tickets, recipe_ingredients, modifiers Schema decision owner-approved

### ၈.၂ Backend Engine & Models
- [x] `restaurant_tables` & `kitchen_order_tickets` database tables
- [x] `App\Models\RestaurantTable` (zone, capacity, status lifecycle)
- [x] `App\Models\KitchenOrderTicket` (order type, items JSON with modifiers, status workflow)
- [x] `App\Services\RestaurantService` (Table lifecycle, KOT creation, KOT ESC/POS string, Bill splitting)
- [ ] **Recipe & Ingredient Consumption:** ဟင်းပွဲတစ်မျိုးချက်သောအခါ ingredient stock (ဆန်/ဆီ/ဟင်းသီးဟင်းရွက်) ကို Inventory Ledger မှ server-side deduct ပြုလုပ်ခြင်း
- [ ] **Waste & Void Control:** KOT void/cancel ပြုလုပ်ရာတွင် reason mandatory ဖြစ်ပြီး AuditLog write ဖြစ်ခြင်း

### ၈.၃ POS & Kitchen UI Experience — ❗ **ကျန်ရှိသည် (Backend Foundation ပြီးသာ)**
- [ ] **Restaurant Table Floor Plan UI:** Indoor/VIP/Outdoor Zone ဇုန်အလိုက် table grid ပြသပြီး Available (🟢) / Occupied (🔴) / Reserved (🟡) / Dirty (⚫) status ကို real-time ပြသပေးသည့် POS Home Screen
- [ ] **Table-to-Cart POS Ordering Flow:** Table ကို tap/click လုပ်သည်နှင့် POS Cart ဖွင့်ပြီး Table name/zone ကို Cart header တွင် ပြသပေးကာ order item တင်နိုင်ခြင်း
- [ ] **Item Modifiers & Add-on Modal:** Cart item တစ်ခုကို long-press/tap လုပ်သည်နှင့် modifiers list ပေါ်လာပြီး "Less sugar / No ice / Extra soup" ကဲ့သို့ options ရွေးချယ်ပေးသည့် Modal — ရွေးချယ်ချက် KOT items JSON ထဲ save ဖြစ်ခြင်း
- [ ] **Send to Kitchen / KOT Print Button:** Cart confirm လုပ်သည်နှင့် "Send to Kitchen" button ဖြင့် Kitchen Thermal Printer သို့ ESC/POS KOT Print ထုတ်ပေးပြီး Table status ကို Occupied သို့ ပြောင်းပေးခြင်း
- [ ] **Bill Splitting Dialog:** Checkout ချိန်တွင် "Divide by persons" ဖြင့် ငွေပမာဏ ခွဲဝေတွက်ချက်ပေးသည့် confirmation modal

### ၈.၄ Kitchen Display & Hardware
- [ ] **Kitchen Printer Routing Test:** မီးဖိုချောင် thermal printer (58mm/80mm) တွင် KOT ပုံနှိပ်ချိန် ≤3 seconds ဖြစ်ကြောင်း latency test
- [ ] **Kitchen Display System (KDS) Optional:** Browser-based KDS page (`/store/{slug}/kitchen`) တွင် pending/preparing/ready tickets real-time ပြသပေးခြင်း (WebSocket/polling)

### ၈.၅ Shared-Core Reuse Assessment
- [ ] **Reuse Ratio Measurement:** Restaurant pack ပြီးဆုံးပြီးနောက် shared core (Sale posting, Payment, Inventory, Audit) ကို 50% အထက် reuse ဖြစ်မဖြစ် တိုင်းတာပြီး separate repository decision ပြုလုပ်ခြင်း

### ၈.၆ Verification
- [x] `tests/Feature/POS/RestaurantVerticalPackTest.php` (5 passed tests)
- [ ] Table lifecycle: full flow tested in browser (available → occupied → bill → released)
- [ ] KOT print: verified on real thermal printer
- [x] Git tag: `v1.8.0-restaurant-vertical-pack`

### Phase 8 Exit Criteria (ကျန်ရှိသောအပိုင်းများ)
- [ ] Requirements signed off by real pilot users
- [ ] Prototype proves operational flow (table → order → kitchen → bill → close)
- [ ] Repository strategy decided using measured shared-code ratio
- [ ] Full build is separately approved by owner

---

## Quality Gates Across All Phases

> Reference: §10 Growth Plan Quality Gates

### Security
- [x] Tenant isolation on every store-owned record (cross-store writes fail)
- [x] Server-side capability and role enforcement (not just UI hidden)
- [x] CSRF/authentication protection
- [x] Support access audit-logged with mandatory reason
- [x] Secrets never stored in client code or repository

### Data Integrity
- [x] Money calculations: bcmath (no floating-point errors)
- [x] Inventory changes: transactional and auditable
- [x] Posted documents: corrected through reversal, not destructive edit
- [ ] Batch quantity: reconciles through full lifecycle (Phase 6 exit gate)
- [ ] Backup/restore: tested before production migration

### Performance & Myanmar SME Realities
- [ ] POS and storefront: no full product list in initial response (paginated/searched server-side)
- [ ] Published theme/profile resolution: cached and invalidated on publish
- [ ] Images: optimized and size-limited
- [ ] Tested on mid/low-range Android and weak network (2G/3G Myanmar conditions)

### UX & Localization
- [x] Myanmar labels understandable to non-technical owners
- [x] Destructive actions have explicit confirmation
- [x] Forms preserve input after validation failure
- [x] Empty states tell the user what action is possible
- [x] Theme preview never modifies live storefront — Draft save သည် published `storefront_settings` ကို မပြောင်းပါ (T2 complete, 2026-08-29)
- [x] Admin navigation shows only relevant capabilities

---

## Current Implementation Status Summary

| Phase | Backend Engine | Admin UI | POS Counter UI | Hardware/Print | Pilot/Real-World |
|---|:---:|:---:|:---:|:---:|:---:|
| Phase 0 — Baseline | ✅ | ✅ | ✅ | ✅ | ✅ |
| Phase 1 — Capability | ✅ | ✅ | ✅ | — | ✅ |
| Phase 2 — Decoupling | ✅ | ✅ | ✅ | — | ✅ |
| Phase 3 — Theme Engine | ✅ | ✅ | ⚠️ (display mode done) | — | ⚠️ |
| Phase 4 — Tier A | ✅ | ⚠️ Partial | ⚠️ Partial | ⚠️ Partial | ❌ |
| Phase 5 — Cloud | ✅ | ⚠️ Partial | — | — | ⚠️ |
| Phase 6 — Batch/UOM | ✅ | ❌ | ❌ | ❌ | ❌ |
| Phase 7 — Local/LAN | ✅ | ❌ | — | — | ❌ |
| Phase 8 — Restaurant | ✅ | ❌ | ❌ | ❌ | ❌ |

**Legend:** ✅ Done | ⚠️ Partial | ❌ Not Started | — Not Applicable

> **Honest Summary:** Phases 0-3 Backend + Foundation = Solid. Phase 3-8 Admin UI, POS Counter Experience, Hardware Integration, Real-world Pilot Verification = **ကျန်ရှိသည်** (တကယ့် လုပ်ငန်းခွင်သုံး Software ဖြစ်ရန် ထပ်မံ တည်ဆောက်ရန် လိုအပ်သည်)
