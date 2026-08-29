# DataPOS Single-Codebase Growth Plan (Myanmar Market)

**Document type:** Product architecture and implementation master plan  
**Status:** Proposed for owner approval  
**Created:** 2026-08-29  
**Primary constraint:** One owner/developer working with AI agents  
**Primary goal:** DataPOS ကို repository တစ်ခုတည်းအတွင်း မြန်မာနိုင်ငံရှိ လုပ်ငန်းအမျိုးအစားများအတွက် ရောင်းချနိုင်သော၊ ထိန်းသိမ်းရလွယ်သော software product အဖြစ် တိုးချဲ့ရန်

---

## 1. Executive Decision

DataPOS ကို လုပ်ငန်းအမျိုးအစားတစ်ခုစီအတွက် project copy များခွဲပြီး မတည်ဆောက်ရ။ အောက်ပါ product model ကို အသုံးပြုမည်။

```text
One Repository
└── DataPOS Core
    ├── Capability System
    ├── Business Profiles
    ├── Optional Vertical Packs
    ├── Storefront Theme Engine
    ├── Cloud Deployment
    └── Local/LAN Deployment (later)
```

ဖောက်သည်ကို ရောင်းချရာတွင် edition အမည်များကွဲနိုင်သော်လည်း source code နှင့် core database rules သည် တစ်ခုတည်းဖြစ်ရမည်။

```text
DataPOS Mobile & Electronics Edition
DataPOS General Retail Edition
DataPOS Repair & Service Edition
DataPOS Pharmacy Edition (after batch/expiry foundation)
DataPOS Agriculture Supply Edition (after batch/UOM foundation)
DataPOS Restaurant Edition (separate vertical pack, demand-gated)
```

### Non-negotiable rules

1. Authentication, tenant isolation, sales, inventory ledger, finance, audit, backup နှင့် reporting core ကို edition တစ်ခုချင်း copy မလုပ်ရ။
2. Long-lived `mobile`, `pharmacy`, `restaurant` Git branches မဖန်တီးရ။
3. Store Owner ကို PHP, Blade, JavaScript သို့မဟုတ် arbitrary CSS upload လုပ်ခွင့်မပေးရ။
4. Theme နှင့် Business Profile ကို မရောရ။
5. New vertical တစ်ခုကို label ပြောင်းရုံဖြင့် supported ဟု မရောင်းရ။ Required workflow နှင့် data integrity tests ပြည့်မှသာ ရောင်းရမည်။
6. တစ်ချိန်တည်းတွင် major vertical တစ်ခုထက်ပို၍ မဆောက်ရ (WIP limit = 1 vertical).

---

## 2. Product Layers

### 2.1 DataPOS Core

လုပ်ငန်းအမျိုးအစားအားလုံးအသုံးပြုနိုင်ရမည့် shared foundation ဖြစ်သည်။

- Store/tenant isolation
- Users, roles and permissions
- Branches and warehouses
- Products, categories, brands and variants
- Barcode/SKU foundation
- Inventory movements and balances
- POS sales, returns and reversals
- Purchasing and supplier payables
- Customers, membership and debt
- Cashier shifts and daily closing
- Payments and financial transactions
- Audit logs
- Reports and exports
- Backup, restore and migration safety
- Localization (Myanmar/English/Chinese as currently supported)
- Storefront catalog, ordering and customer account

Core rule တစ်ခုကို vertical module က bypass မလုပ်ရ။ ဥပမာ Restaurant sale, Pharmacy sale နှင့် Mobile sale အားလုံးသည် shared posting, payment, audit နှင့် inventory transaction rules ကို ဖြတ်သန်းရမည်။

### 2.2 Capability System

Boolean fields များစွာကို `stores` table ထဲထည့်ခြင်းထက် named capabilities အသုံးပြုမည်။

```text
catalog.variants
inventory.serial_tracking
inventory.batch_tracking
inventory.expiry_tracking
inventory.multi_uom
service.repair_jobs
service.warranty_tracking
commerce.wholesale_pricing
restaurant.tables
restaurant.kitchen_orders
restaurant.recipe_inventory
```

Capability တစ်ခုသည် အောက်ပါနေရာအားလုံးတွင် server-side enforce ဖြစ်ရမည်။

- Routes/middleware
- Policies/authorization
- Admin sidebar and dashboard
- Form fields and validation
- Service-layer business rules
- Reports
- Storefront navigation and sections
- Imports/exports

UI ကိုဖျောက်ထားခြင်းတစ်ခုတည်းကို permission enforcement ဟု မယူဆရ။

### 2.3 Business Profiles

Business Profile သည် capabilities, labels, default settings, navigation နှင့် demo configuration ကို စုပေးသော preset ဖြစ်သည်။ Code package မဟုတ်ပါ။

ဥပမာ `mobile_electronics` profile:

```text
Enable:
- catalog.variants
- inventory.serial_tracking
- service.warranty_tracking
- service.repair_jobs
- commerce.wholesale_pricing

Default storefront content:
- Brand/model discovery
- Warranty messaging
- Compatibility filters
- Repair tracking
```

Profile apply လုပ်ရာတွင် existing business data ကို မဖျက်ရ။ Capability disable လုပ်ခြင်းသည် historical records ကို မဖျက်ရ၊ hidden/readonly access policy ကို သတ်မှတ်ရမည်။

### 2.4 Vertical Packs

Vertical Pack သည် data model နှင့် workflow အမှန်တကယ်ကွဲသော domain feature ဖြစ်သည်။

- Mobile/Electronics Pack
- Repair/Service Pack
- Pharmacy/Agriculture Batch Pack
- Restaurant Operations Pack

Pack တစ်ခုစီတွင် အနည်းဆုံး အောက်ပါအရာများပါရမည်။

- Models and migrations
- Domain services
- Validation and authorization
- Admin/POS views
- Reports
- Import/export rules
- Audit coverage
- Automated tests
- Demo seed data
- Upgrade/rollback notes

### 2.5 Storefront Theme Engine

Theme သည် “ဘယ်လိုမြင်ရမလဲ” ကိုသာ ဆုံးဖြတ်သည်။ Business Profile သည် “ဘာတွေပါရမလဲ၊ ဘယ်လိုအလုပ်လုပ်ရမလဲ” ကို ဆုံးဖြတ်သည်။

Theme တစ်ခုရွေးလိုက်လျှင် customer-facing storefront အားလုံး အတွဲလိုက်ပြောင်းရမည်။

- Header and navigation
- Homepage
- Category/browse page
- Product listing
- Product detail
- Search and filters
- Cart/order builder
- Customer account
- Blog and guidance pages
- Contact/service tracking presentation
- Footer
- Mobile navigation
- Empty, loading and error states

Store Owner သည် Platform Owner enable လုပ်ထားသော themes များထဲမှရွေးနိုင်ပြီး safe branding values ကိုသာပြောင်းနိုင်မည်။

---

## 3. Myanmar Market Priority

### 3.1 Tier A - Build and sell first

#### A1. Mobile and Electronics

DataPOS ၏ လက်ရှိ feature set နှင့် အကိုက်ဆုံးဖြစ်သည်။ ပထမဆုံး production reference customer နှင့် case study ကို ဒီ vertical မှရယူရမည်။

Required capabilities:

- Retail/wholesale pricing
- Product variants
- SKU and barcode scan
- IMEI/serial tracking
- Warranty tracking
- Customer debt
- Repair/service jobs
- Spare parts consumption
- Supplier purchasing and payables
- Branch/warehouse transfer
- Returns/exchanges

#### A2. General Retail and Wholesale

ကုန်စုံအသေးစား၊ အထွေထွေပစ္စည်းဆိုင်၊ အလှကုန်၊ အိမ်သုံးပစ္စည်းနှင့် ဖြန့်ချိရေးလုပ်ငန်းများကို ရည်ရွယ်သည်။

Required capabilities:

- Fast barcode POS
- Retail/wholesale price tiers
- Customer/supplier debt
- Stock receiving and transfer
- Cashier shift and daily closing
- Basic promotions
- Purchase and sales reports
- Low-stock alerts

#### A3. Repair and Service

Mobile/computer/CCTV repair, installation and maintenance businesses ကို ရည်ရွယ်သည်။ လက်ရှိ service/repair foundation ကို shared pack အဖြစ် တည်ငြိမ်စေရမည်။

Required capabilities:

- Device/customer intake
- Job number and status workflow
- Technician assignment
- Diagnosis and estimate
- Deposit and balance payment
- Spare-parts consumption
- Service warranty
- Customer-facing tracking
- Job profitability report

### 3.2 Tier B - Build only after foundation and customer validation

#### B1. Pharmacy and Agriculture Supply

Pharmacy နှင့် စိုက်ပျိုးရေးဆေး/မြေသြဇာဆိုင်တို့သည် shared batch foundation အသုံးပြုနိုင်သော်လည်း labels နှင့် regulatory workflow ကွဲနိုင်သည်။

Required foundation before sale:

- Batch/lot tracking
- Manufacture and expiry dates
- FEFO stock issuing option
- Multi-unit/UOM conversion
- Expiring/expired stock blocking and alerts
- Batch-aware purchase receiving and returns
- Batch recall/history report
- Supplier traceability
- Safe product warnings/notes

Pharmacy-ready ဟု မကြေညာမီ actual pharmacist/store workflow ဖြင့် pilot ပြုလုပ်ရမည်။

#### B2. Fashion and Boutique

General Retail core ပေါ်တွင် relatively low-cost extension ဖြစ်နိုင်သည်။

- Size/color matrix
- Variant image management
- Exchange workflow
- Visual storefront theme
- Optional season/collection fields

### 3.3 Tier C - Separate demand-gated vertical pack

#### C1. Restaurant and Food Service

Restaurant ကို retail labels ပြောင်းထားသော edition အဖြစ် မဆောက်ရ။ အောက်ပါ workflow များကြောင့် သီးခြား vertical pack လိုသည်။

- Tables and zones
- Dine-in/takeaway/delivery order types
- Kitchen Order Ticket (KOT)
- Menu modifiers/add-ons
- Course/kitchen status
- Split/merge bill
- Recipe and ingredient consumption
- Kitchen printer routing
- Waste and void control

အနည်းဆုံး paying pilot customers 2 ယောက် သို့မဟုတ် signed requirements ရှိမှ development စရန် အကြံပြုသည်။ Restaurant pack က shared core နှင့် 50% အောက်သာ share နိုင်ကြောင်း prototype က သက်သေပြမှသာ separate repository/product ကို ပြန်စဉ်းစားရမည်။

### 3.4 Do not prioritize yet

- Gold/jewelry weight and daily price systems
- Fuel station pump integration
- Hotel/property management
- Full manufacturing/MRP
- Hospital/clinic records
- Payroll/HR suite

ဤလုပ်ငန်းများသည် domain risk မြင့်ပြီး လက်ရှိ DataPOS core မှ အလွန်ကွဲသည်။ Customer demand နှင့် domain expert မရှိဘဲ မဆောက်ရ။

---

## 4. Storefront Theme Product Plan

### 4.1 Initial theme catalog

ပြင်ပ brand များ၏ design ကို တိုက်ရိုက် clone သို့မဟုတ် trademark နာမည်ဖြင့် မရောင်းရ။ Familiar ecommerce interaction patterns ကို DataPOS ၏ကိုယ်ပိုင် theme names နှင့်တည်ဆောက်မည်။

| Theme | Design direction | Recommended use |
|---|---|---|
| `Marketplace Pro` | Dense marketplace discovery | Mobile, electronics, large catalogs |
| `Retail Trust` | Clear search and trust-focused retail | General retail, pharmacy |
| `Visual Boutique` | Image-led catalog | Fashion, beauty, lifestyle |
| `Quick Shop` | Lightweight mobile-first ordering | Small Myanmar shops, unstable internet |
| `Wholesale Catalog` | Dense B2B pricing and MOQ | Wholesale/distribution |

Delivery order:

1. Build engine plus `Marketplace Pro`.
2. Build `Retail Trust` and prove cross-theme compatibility.
3. Stop and run pilot/QA.
4. Add remaining themes only after the first two pass all storefront journeys.

### 4.2 Theme permissions

| Action | Platform Owner | Store Owner |
|---|---:|---:|
| Create/register theme | Yes | No |
| Install/update/deprecate theme | Yes | No |
| Enable theme for stores/plans | Yes | No |
| Select an enabled theme | Yes | Yes |
| Change colors/logo/font/banner | Yes | Yes |
| Change approved section visibility/order | Yes | Limited |
| Edit executable templates/scripts | No web upload | No |
| Preview/publish own store | Yes | Yes |
| Roll back own store theme | Yes | Yes |

### 4.3 Safe customization fields

- Primary/accent/background colors
- Contrast-safe text color derived by system
- Approved font presets with Myanmar glyph support
- Logo, favicon and banners
- Button/card style presets
- Homepage section visibility and approved ordering
- Store-specific headings and descriptions
- Light/dark preference where theme supports it

Do not accept:

- Raw PHP/Blade/JavaScript
- Arbitrary remote scripts
- Unvalidated HTML
- Unrestricted CSS
- Theme assets outside controlled storage paths

### 4.4 Theme lifecycle

```text
Installed -> Enabled -> Draft customization -> Preview -> Published
                                         \-> Discard
Published -> New revision -> Rollback
Theme version -> Upgrade check -> Migrate config -> Publish
```

Required records:

- Theme registry/manifest
- Store draft configuration
- Store published configuration
- Revision history
- Theme asset references
- Theme engine/app compatibility version

Theme in-use ဖြစ်နေချိန် hard delete မလုပ်ရ။ `deprecated` status ထားပြီး replacement path ပေးရမည်။

---

## 5. Admin Product Strategy

Admin Panel ကို vertical တစ်ခုစီအတွက် design အသစ်မခွဲရ။ Stable admin shell တစ်ခုတည်းထားပြီး capabilities အလိုက် menu, dashboard widgets, fields နှင့် workflow များပြောင်းမည်။

### Platform Owner responsibilities

- Stores, subscriptions/plans and capabilities
- Business profile catalog
- Theme catalog and rollout
- Vertical pack availability
- Support-mode access with reason and audit
- Global compatibility and migration status
- Usage/health monitoring

### Store Owner responsibilities

- Own-store users and permissions
- Branches, warehouses and operational settings
- Enabled optional modules within allowed plan
- Store branding and enabled theme selection
- Store-level workflow settings
- Publish/rollback own storefront

### Staff responsibilities

- Role-authorized operational actions only
- No theme/package/profile administration
- No cross-store access

Admin color branding may be allowed later, but navigation structure and workflow layout must remain platform-controlled to reduce training and support cost.

---

## 6. Target Technical Boundaries

ဒီ section သည် implementation စတင်ချိန်တွင် repository conventions နှင့် ထပ်မံစစ်ပြီးမှ final class/table names သတ်မှတ်ရန် architecture target ဖြစ်သည်။

### 6.1 Suggested services

```text
App\Capabilities\CapabilityRegistry
App\Capabilities\StoreCapabilityResolver
App\BusinessProfiles\BusinessProfileRegistry
App\Themes\ThemeRegistry
App\Themes\ThemeConfigValidator
App\Themes\ThemeRenderer
App\Themes\ThemePublisher
App\Themes\ThemeAssetManager
```

### 6.2 Suggested persistence concepts

```text
business_profiles
capabilities
business_profile_capabilities
store_capabilities
storefront_themes
store_theme_configs
store_theme_revisions
store_theme_assets
```

Exact tables မဖန်တီးမီ current schema နှင့် JSON-vs-normalized tradeoff ကို ADR ဖြင့်ဆုံးဖြတ်ရမည်။ Store-specific operational data အားလုံးတွင် `store_id` isolation နှင့် required indexes ရှိရမည်။

### 6.3 Rendering strategy

- Controllers မှ business data ကို theme-independent view models ဖြင့်ပေးရန်
- Theme files မှ database queries မပြုလုပ်ရန်
- Shared UI contracts သတ်မှတ်ရန် (`ProductCardData`, `StoreHeaderData`, etc.)
- Theme fallback ရှိရန်
- Missing/incompatible theme ကြောင့် storefront 500 error မဖြစ်စေရန်
- Published config ကို cache လုပ်ပြီး publish/update တွင် targeted invalidation ပြုလုပ်ရန်
- Preview ကို signed, expiring, store-scoped token ဖြင့်သာကြည့်ရန်

### 6.4 Theme package policy

First release တွင် themes ကို application source အတွင်း reviewed code အဖြစ်သာ ဖြန့်ဝေရန်။ Theme upload installer ကို business demand မရှိမချင်း မဆောက်ရ။

Installer တည်ဆောက်ရပါက Platform Owner only ဖြစ်ပြီး အနည်းဆုံးအောက်ပါတို့လိုသည်။

- Signed/approved package policy
- Strict `manifest.json` schema
- File count and size limits
- MIME and extension allowlist
- Zip path traversal protection
- No executable server-side files
- Asset scan and safe extraction path
- Engine/app compatibility check
- Atomic install and rollback
- Audit log

---

## 7. Implementation Roadmap

Calendar estimates မဟုတ်ဘဲ exit criteria ဖြင့် phase ပြီး/မပြီးဆုံးဖြတ်မည်။ AI agent speed သည် production readiness ကို မအစားထိုးနိုင်။

### Phase 0 - Baseline, Decisions and Safety Freeze

**Goal:** လက်ရှိ project ကို feature ထပ်မတိုးမီ ယုံကြည်ရသော baseline ပြုလုပ်ရန်။

Tasks:

- Current automated test baseline ကို run/record
- Dirty worktree changes ကို owner-approved commits အဖြစ်ခွဲ
- Current production/pilot workflows inventory ပြုလုပ်
- Existing store isolation audit completion status စစ်
- Theme/Profile/Capability terminology ကို ADR ဖြင့် lock
- Supported editions and non-goals approve
- Current schema backup and restore drill status confirm
- Critical user journeys list ပြုလုပ်

Exit criteria:

- Test baseline documented
- No unknown migration failures
- Store isolation blockers resolved or explicitly tracked
- Architecture decisions owner-approved
- Rollback point/tag available

### Phase 1 - Capability Foundation

**Goal:** Modules ကို UI hide/show ထက်ပိုသော server-enforced capability system ဖြင့်ထိန်းရန်။

Tasks:

- Capability registry/resolver
- Store capability persistence
- Business profile definitions in code first
- Middleware/policy enforcement pattern
- Sidebar/dashboard visibility integration
- Platform Owner management UI (minimal)
- Audit capability changes
- Migration defaults preserving current behavior

Initial profiles:

- `mobile_electronics`
- `general_retail`
- `repair_service`

Exit criteria:

- Existing stores retain current access after migration
- Disabled capability cannot be reached by direct URL/API
- Cross-store capability writes fail
- Profile changes do not delete historical data
- Feature tests cover manager/staff/platform roles

### Phase 2 - Storefront Decoupling

**Goal:** လက်ရှိ mobile-specific storefront ကို industry-neutral data and component contracts အဖြစ်ခွဲရန်။

Tasks:

- Mobile-specific hardcoded sections inventory
- Industry content and module navigation separation
- Shared storefront view models
- Header/footer/product card/search component boundaries
- Profile-driven section availability
- Neutral fallback copy
- Pharmacy store တွင် Glass Finder/mobile copy မပေါ်စေရန်

Exit criteria:

- General Retail profile shows no mobile-only features
- Storefront pages render without theme-specific queries
- Current Mobile storefront behavior remains supported
- Myanmar/English localization parity passes

### Phase 3 - Theme Engine MVP

**Goal:** Complete storefront theme bundle selection with safe per-store branding.

Tasks:

- Theme registry and manifests
- Draft/published theme configuration
- `Marketplace Pro` implementation
- `Retail Trust` implementation
- Real storefront preview at desktop/tablet/mobile sizes
- Color/logo/font/banner customization
- Publish transaction and cache invalidation
- Revision history and rollback
- Theme fallback and compatibility errors

Exit criteria:

- One store can preview without affecting public storefront
- Publishing one store never changes another store
- Rollback restores exact previous published revision
- Both themes pass all customer-facing routes
- Mobile viewport has no horizontal overflow/overlap
- Theme selection survives app restart/deploy

### Phase 4 - Mobile/General Retail/Repair Productization

**Goal:** ပထမဆုံးရောင်းချမည့် editions သုံးခုကို reliable product packages အဖြစ်ပြင်ရန်။

Tasks:

- Edition onboarding wizard/preset
- Demo stores and role-based demo accounts
- Import templates and validation
- Printer/barcode hardware test matrix
- Customer debt and opening-balance workflow validation
- Repair workflow completion and profitability reporting
- Sales demo script and owner training guide
- Backup/restore runbook
- Release checklist and support checklist

Exit criteria:

- At least one real pilot shop completes daily workflow
- Daily closing and stock reconciliation match manual records
- Backup restore drill passes
- Owner can onboard a store without editing source code
- Critical defects have documented resolution/rollback

### Phase 5 - Cloud Resale Hardening

**Goal:** Multi-tenant cloud sales ကို လုံခြုံတည်ငြိမ်အောင်လုပ်ရန်။

Tasks:

- Provisioning and plan/capability assignment
- Platform support-mode workflow with audit
- Scheduler/queue/backup monitoring
- Rate limits and upload quotas
- Store data export and deletion workflow
- Operational metrics and incident runbook
- Update/deployment rollback process
- Customer-facing release notes

Exit criteria:

- Tenant isolation test suite passes
- Provisioning and offboarding are repeatable
- Restore point objectives documented and tested
- Support access is reason-bound and audited
- Deployment rollback tested

### Phase 6 - Pharmacy/Agriculture Foundation

**Entry gate:** Tier A editions stable and at least one validated customer requirement set.

Tasks:

- UOM and unit conversion foundation
- Batch/lot inventory model
- Manufacture/expiry dates
- FEFO option and expiry blocking policy
- Batch-aware receiving/sales/returns/transfers
- Expiry and recall reports
- Pharmacy/Agriculture profiles and demo data
- `Retail Trust` industry adaptation

Exit criteria:

- Batch quantity reconciles through full lifecycle
- Expired stock policy is enforced server-side
- Returns and transfers preserve batch identity
- Real pilot owner verifies daily workflow
- No unsupported medical claims are shown by default

### Phase 7 - Local/LAN Edition

**Entry gate:** Cloud edition operationally stable and paying demand for offline/local installation confirmed.

Tasks:

- Supported local database decision and compatibility tests
- Installer/provisioning
- LAN access and firewall guide
- Versioned backup package with checksums
- Restore preflight and automatic pre-restore backup
- Signed offline license (public-key verification)
- Upgrade and rollback tool
- Remote support procedure with owner consent

Exit criteria:

- Clean-machine install test passes
- Power/network interruption recovery tested
- Backup/restore and upgrade/rollback pass
- Private signing key is absent from customer installations
- Support runbook usable by non-developer operator

### Phase 8 - Restaurant Discovery and Prototype

**Entry gate:** At least two serious prospects/pilots or funded implementation request.

Tasks:

- Field interviews and workflow mapping
- Table/KOT/modifier/recipe prototype
- Shared-core reuse measurement
- Printer and kitchen latency test
- Data model ADR
- Go/no-go decision

Exit criteria:

- Requirements signed off by pilot users
- Prototype proves operational flow
- Repository strategy is decided using measured shared-code ratio
- Full build is separately approved

---

## 8. Solo Developer + AI Agent Operating Model

### 8.1 Work-in-progress limits

- One active vertical pack maximum
- One active architecture migration maximum
- Theme count maximum two until engine is stable
- No new major feature while a production blocker is open
- Every AI task must have explicit scope and acceptance criteria

### 8.2 Required workflow for each feature

```text
1. Read repository and related docs
2. Define problem and non-goals
3. Write/update ADR or implementation plan
4. Add/adjust tests for expected behavior
5. Implement smallest safe slice
6. Run focused tests
7. Run broader regression tests
8. Review tenant isolation/security/data integrity
9. Browser-test critical UI on desktop/mobile
10. Update changelog/runbook
11. Commit one coherent change
12. Pilot before expanding scope
```

### 8.3 AI agent rules

- Agent တစ်ခုစီကို overlapping files မပေးရ။
- Architecture decision ကို agents အလိုအလျောက်မဆုံးဖြတ်စေရ။ Owner-approved ADR ကို source of truth သုံးရမည်။
- Generated migrations and destructive operations ကို manual review မရှိဘဲ မ run ရ။
- Agent claim “tests passed” ကို command output နှင့် changed files review မရှိဘဲ မယုံရ။
- Security, money, inventory and tenant-scope changes ကို second review pass ပြုလုပ်ရမည်။
- Large refactor ကို feature development နှင့် commit တစ်ခုထဲမရောရ။

### 8.4 Definition of Done

Feature တစ်ခုသည် အောက်ပါတို့ပြည့်မှ Done ဖြစ်သည်။

- Acceptance criteria pass
- Validation and authorization present
- Tenant scope verified
- Failure/edge cases handled
- Audit/logging included where material
- Focused and regression tests pass
- Mobile/desktop UI checked where relevant
- Migration rollback/recovery considered
- Documentation updated
- No unrelated file churn

---

## 9. Commercial Packaging for Myanmar

### 9.1 Recommended offers

အစပိုင်းတွင် pricing ကို feature အလွန်များစွာခွဲမည့်အစား support/deployment scope ဖြင့် ရိုးရှင်းစွာစမ်းသပ်ရန်။ Exact prices ကို customer interviews နှင့် operating cost မတိုင်းမီ ဒီ plan တွင် lock မလုပ်ပါ။

- **Starter:** Single store, core POS, basic reports
- **Business:** Purchasing, debt, advanced inventory, storefront
- **Operations:** Multi-branch, repair/vertical capabilities, advanced reports
- **Local/LAN:** One-time setup plus maintenance agreement (after Phase 7)

### 9.2 Myanmar-specific selling requirements

- Myanmar language first, English technical fallback
- MMK formatting and configurable currency display
- Viber/Telegram/phone ordering links
- Customer and supplier credit/debt
- Cashier shift and daily closing
- Barcode scanner support
- 58mm/80mm receipt support with tested printers
- Excel import/export with clear error report
- Unstable internet considerations
- Low-end device performance
- Backup ownership and restore explanation
- Simple owner training materials

### 9.3 Do not promise before verified

- “Works fully offline” without tested offline queue/local edition
- Bluetooth printing across all Android devices
- Pharmacy compliance without pilot validation
- Restaurant readiness without KOT/table workflow
- Automatic cloud/local synchronization
- Any integration not tested on the customer's actual hardware

---

## 10. Quality Gates

### Security

- Tenant isolation on every store-owned record
- Server-side capability and role enforcement
- CSRF/authentication protection
- Safe uploads and storage paths
- No executable theme uploads
- Support access audit
- Secrets never stored in client code or repository

### Data integrity

- Money avoids floating-point storage/calculation errors
- Inventory changes are transactional and auditable
- Posted documents are corrected through reversal, not destructive edits
- Import retries are idempotent where relevant
- Historical records survive profile/capability changes
- Backup/restore tested before production migration

### Performance and connectivity

- Avoid loading all products into initial POS/storefront response
- Paginate/search server-side
- Optimize and size-limit images
- Cache published theme/profile resolution
- Keep storefront JavaScript lightweight
- Test on mid/low-range Android and weak network profiles

### UX

- Myanmar labels understandable to non-technical owners
- Destructive actions have explicit confirmation
- Forms preserve input after validation failure
- Empty states tell the user what action is possible
- Theme preview never modifies live storefront
- Admin navigation shows only relevant capabilities

---

## 11. Decision Gates and Stop Rules

A new vertical must satisfy all of the following before full implementation:

1. At least one real customer workflow has been observed and documented.
2. Required data model differences are known.
3. Existing core reuse is assessed.
4. Pilot customer or commercial demand exists.
5. Current production blockers are under control.
6. Backup/rollback path is available.
7. The feature can be maintained by one owner after launch.

Stop or postpone when:

- A vertical requires duplicating core sales/inventory/finance code.
- Customer demand is only hypothetical.
- Existing pilot data does not reconcile.
- Automated tests are becoming less reliable.
- Support workload is already above solo capacity.
- A feature requires paid infrastructure that revenue cannot support.

---

## 12. First 90-Day Execution Sequence (Order, Not Fixed Dates)

ဒီ sequence သည် calendar promise မဟုတ်ပါ။ Step တစ်ခု၏ exit criteria ပြည့်မှ နောက်တစ်ခုသို့သွားရန်ဖြစ်သည်။

1. Stabilize current worktree and record full test baseline.
2. Approve capability/profile/theme ADRs.
3. Implement capability foundation preserving all current stores.
4. Create `mobile_electronics`, `general_retail`, `repair_service` profiles.
5. Remove mobile-only assumptions from neutral storefront paths.
6. Implement Theme Engine draft/publish/revision foundation.
7. Deliver `Marketplace Pro` end-to-end.
8. Deliver `Retail Trust` to prove multiple themes.
9. Run desktop/mobile/browser regression on every storefront route.
10. Pilot Mobile/General Retail/Repair editions with real workflows.
11. Fix pilot blockers and complete backup/restore drill.
12. Decide next investment using evidence: Pharmacy/Agriculture foundation or Cloud/Local resale hardening.

The next vertical must not start merely because theme work looks complete. Operational correctness and pilot evidence come first.

---

## 13. Immediate Deliverables Before Coding the New Architecture

Create and approve these small documents before implementation:

1. `ADR: Capability and Business Profile Model`
2. `ADR: Storefront Theme Rendering and Security Boundary`
3. `Storefront Route and Component Inventory`
4. `Mobile-specific Assumption Audit`
5. `Edition Capability Matrix`
6. `Theme Acceptance Test Matrix`
7. `Pilot Store Exit Checklist`

Recommended first implementation slice:

```text
Capability registry
-> three code-defined profiles
-> server-side route enforcement
-> sidebar visibility
-> backward-compatible defaults
-> focused tenant/role tests
```

Theme UI redesign should start only after the profile/capability boundary is stable enough that a theme does not accidentally expose an unsupported industry module.

---

## 14. Relationship to Existing Project Documents

ဒီ plan သည် ရှိပြီးသား documents များကို အစားထိုးခြင်းမဟုတ်ဘဲ product expansion layer အဖြစ် ဖြည့်စွက်သည်။ Conflict ဖြစ်ပါက owner-approved Source of Truth နှင့် newer ADR ကို ဦးစားပေးပြီး conflict ကို document နှစ်ဖိုင်လုံးတွင် ပြင်ရမည်။

- `docs/pos-resale-plan/ROADMAP.md` - POS/resale phases and current-state history
- `docs/pos-resale-plan/02-target-design.md` - single-codebase, inventory and deployment target design
- `docs/MYANMAR_SME_COMMERCIALIZATION_GUIDE.md` - commercialization and pilot guidance
- `docs/SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md` - product/store architecture direction
- `Source_of_Truth_MM.md` - approved project-level rules and decisions

---

## 15. Owner Approval Checklist

Implementation မစမီ အောက်ပါတို့ကို owner မှ approve/revise လုပ်ရန်။

- [ ] One repository + shared core strategy
- [ ] Initial sellable editions: Mobile, General Retail, Repair
- [ ] Pharmacy/Agriculture is Tier B, foundation-gated
- [ ] Restaurant is demand-gated separate vertical pack
- [ ] Initial themes: Marketplace Pro and Retail Trust
- [ ] Store Owner may select enabled themes and safe branding only
- [ ] Platform Owner controls themes, profiles and capabilities
- [ ] No executable theme upload
- [ ] WIP limit: one major vertical at a time
- [ ] Pilot and backup/restore gates before wider sales

---

## Final Recommendation

DataPOS ၏ ရေရှည်အားသာချက်သည် feature အများဆုံး software ဖြစ်ခြင်းမဟုတ်ဘဲ shared core တည်ငြိမ်ပြီး မြန်မာ SME တစ်မျိုးချင်းလိုအပ်ချက်ကို controlled profiles, vertical packs နှင့် storefront themes ဖြင့် လုံခြုံစွာပေါင်းစပ်ပေးနိုင်ခြင်းဖြစ်ရမည်။

တစ်ယောက်တည်း AI agents နှင့်တည်ဆောက်နေသောအခြေအနေတွင် project copy များ၊ long-lived edition branches များနှင့် vertical များကိုတစ်ပြိုင်နက်ဆောက်ခြင်းသည် အကြီးဆုံး maintenance risk ဖြစ်သည်။ Mobile/General Retail/Repair ကို ပထမဆုံးအရည်အသွေးမြင့်အောင်လုပ်ပြီး pilot evidence အပေါ်မူတည်၍ Pharmacy/Agriculture သို့တိုးခြင်းက အချိန်၊ ငွေကြေးနှင့် product reputation အတွက် အကောင်းဆုံးလမ်းကြောင်းဖြစ်သည်။
