# 03 Superseded Plans

> Consolidated edition. Each embedded source is preserved below with its original path and SHA-256 digest.


---

## Source 1: `archive/superseded-plans/initial_implementation_plan_final.md`

**SHA-256:** `6ab65fdadb2249dce9824ce1572d241a27c57fbab96c6d91ecac149cd3b706bd`

# DataPOS Final Implementation Plan — Granular Store Permissions, Role-Aware Navigation, Optional Modules & Sales Channels

> **Historical / Superseded Document**
> **Original Filename:** `implementation_plan_final.md`
> **Archived Date:** 2026-09-10
> **Historical Baseline:** GitHub `main` commit `c3981434b1bfb214a7a60f87c013b411ee2d56d0`
> **Status:** Delivered & Superseded by canonical architecture documents
> **Current Architecture References:** `../../README.md` (`../../README.md`; see consolidated index), `../../ADMIN_MODULES_EXECUTION_ROADMAP.md` (`../../plans/ADMIN_MODULES_EXECUTION_ROADMAP.md`; see consolidated index), `../../MULTI_STORE_DATA_ISOLATION_AUDIT_PLAN.md` (`../../architecture/MULTI_STORE_DATA_ISOLATION_AUDIT_PLAN.md`; see consolidated index)
> **Repository:** `shwepyithit568-commits/DataPOS`

## 1. Objective and non-negotiable outcomes

Complete the existing sidebar/scope work without regressing desktop accordion, collapsed flyout, mobile drawer, scrolling, active states, Ecommerce route refactoring, POS, or current store workflows. A Store Owner must be able to control each staff member's module visibility and `view/create/update/delete/export/approve/complete/refund/adjust/assign` actions. Sidebar hiding is never authorization; the same decision must protect routes, controllers, policies and UI actions.

The final access decision is:

```text
Correct platform/store scope
AND active store and active membership (platform owner excepted only by explicit platform policy)
AND plan entitlement when a real subscription system exists
AND required store capability enabled
AND required sales channel enabled
AND effective user permission granted
AND target resource belongs to the active store
```

Availability, channel state and user authorization remain separate calculations.

## 2. Ground-truth rules before implementation

1. Read every applicable `AGENTS.md` and project document.
2. Confirm HEAD equals the approval baseline. If HEAD differs, stop and provide a delta-impact note for renewed approval.
3. Run and retain baseline output for relevant tests, `php artisan route:list --json`, and `git status --short`.
4. Inventory routes by **HTTP method + URI + controller action + optional route name**. A route need not have a name; never invent one. Current Product `store/update/destroy` endpoints are unnamed and must be identified by method/URI/action unless safely named as an explicitly reported compatibility change.
5. Inventory exact `StaffRole::PERMISSION_GROUPS`, middleware, policies, pivot casts, capability registry, store modes, models/status values and translation locales from source. Claims not proven by source must be labelled proposed.
6. Preserve unrelated working-tree changes and do not modify unrelated business calculations.

## 3. Existing architecture that must be extended, not duplicated

The approved baseline contains:

- 28 capability keys across 6 registry groups: `storefront` (6), `catalog` (4), `inventory` (6), `service` (3), `commerce` (4), `operations` (5).
- No standalone `pos` registry group and no `operations.daily_closing`; `pos.tablet_touch_mode` is currently grouped under `operations`.
- `stores.business_profile`, `stores.operation_mode`, `stores.capabilities_override`.
- `store_user.role`, `status`, `staff_role_id`, `custom_permissions`.
- `StaffRole`, `Capability`, `CapabilityRegistry`, `BusinessProfileRegistry`, `Store::getCapabilities()`, `Store::hasCapability()`, `store_can()` and `store.capability` middleware.
- `AdminNavigationService`, currently including an unsafe unconditional `store_manager => true` shortcut.

Do not introduce a second capability registry. Do not claim a proposed permission, channel or role already exists.

## 4. Core modules, optional capabilities and channel boundaries

### 4.1 Core, never disabled by optional capabilities

- Store dashboard
- Basic product/catalog CRUD, categories and brands
- Basic POS counter sale, product search, cart, checkout, stock deduction and receipt
- Basic customers
- Staff authentication/security
- Essential store settings

`catalog.variants` gates variant-specific UI and multi-attribute SKU behavior—not basic Products CRUD. `operations.cashier_shifts` gates shift/open-close/daily-closing workflows—not product search, cart, checkout, receipt, returns or buyback.

### 4.2 Existing optional capability families

Use only the 28 registered keys. Examples: Ecommerce/online ordering/customer portal/blog/reviews, variants/custom fields/barcode/price wizard, advanced inventory/tracking/audit/transfers, repair/warranty/spare parts, wholesale/debt/loyalty/payables, branches/warehouses/cashier shifts/E-load/tablet touch mode.

`service.spare_parts` and `service.warranty_tracking` must not be accidentally nested under `service.repair_jobs`; route dependencies must follow each registered capability.

### 4.3 Independent sales channels

Introduce exactly these initial channel keys:

| Channel | Responsibility | Dependency |
|---|---|---|
| `pos` | In-store product search, cart, checkout, receipt, returns | Core; enabled for existing stores |
| `online_store` | Public catalog/storefront browsing | `storefront.ecommerce` |
| `online_ordering` | Cart/order submission and online-order administration | `online_store` plus `storefront.online_ordering` |

`operation_mode` is a preset, not the final state: `omnichannel` = all three; `pos_only` = POS only; `catalog_only` = POS + online store; `custom` = explicit selection. Do not implement network-independent/offline synchronization unless it already exists; “POS-only/offline-only” means no online sales channel, not guaranteed operation without a network.

## 5. Channel storage and backward-compatible migration

Add nullable JSON `stores.sales_channels` with a model array cast. Store **explicit overrides only**, so presets remain defaults without destructive overwrites:

```json
{"pos": true, "online_store": false, "online_ordering": false}
```

Precedence: protected invariant/dependency → explicit channel override → operation-mode preset default. Unknown keys are rejected. `online_ordering=true` requires `online_store=true`. POS is protected in this phase; Online-only is not implemented unless a separate approved change proves safe.

Backfill must be chunked, idempotent and transaction-safe:

- Existing stores retain `pos=true`.
- Preserve current public Ecommerce behavior: stores currently configured/used for Ecommerce remain `online_store=true`; online-order evidence keeps `online_ordering=true`.
- Do not infer from arbitrary user IDs. Record the evidence used per store.
- Do not delete capability, permission, order, POS or storefront data.
- Write migration/backfill audit records with actor `system`, store, before/after, evidence and timestamp.
- Rollback removes only values created by this migration using an auditable migration marker; it must not erase later owner changes. If safe rollback cannot be guaranteed, make the data migration an explicit deploy command with dry-run/report/apply modes instead of a reversible schema migration.

Preset changes show a diff and require confirmation. They update defaults but preserve explicit overrides unless the owner explicitly selects “reset overrides”.

## 6. Central authorization design

Create/extend one `StorePermissionService`:

```php
can(User $user, Store $store, string $permission): bool
canAny(User $user, Store $store, array $permissions): bool
canAll(User $user, Store $store, array $permissions): bool
effectivePermissions(User $user, Store $store): array
canManageStaffPermissions(User $actor, Store $store, User $target): bool
```

Effective permissions for regular staff are `(active StaffRole permissions ∪ individual grants) − individual denies`. Denies win. Inactive membership or inactive/deleted role denies access. Platform Owner and Store Owner receive policy-defined authority but cannot bypass a disabled capability/channel. Remove the unconditional Store Manager bypass. Managers receive only explicit effective permissions.

### 6.1 Permission migration

Existing `.view/.edit/.delete` keys remain readable. Canonical new assignments use `.view/.create/.update/.delete` plus verified special actions. `.edit` aliases `.update` only; it must not grant create at runtime. Before enforcement, a dry-run backfill expands legacy role-template `.edit` into explicit `.create` and `.update` only where existing route access proves that role historically created records. Report every mapping. Preserve individual data and allow rollback from a recorded snapshot.

Individual `*` submissions are rejected with 422. Legacy wildcard records are inventoried and quarantined: preserve current non-protected behavior temporarily, log use, and require owner review; never expand them to protected permissions.

Protected/non-delegable keys must be derived from real existing keys. New keys such as module/channel management must be marked proposed, localized, mapped to routes and added only once. A manager cannot grant a permission they do not hold, modify a Store Owner/Platform Owner, modify themself, grant protected permissions, or cross stores. The last active Store Owner cannot be deleted, suspended or demoted.

### 6.2 Request enforcement

Add `store.permission` middleware and a `store.channel` middleware; retain `store.capability`. Middleware ordering is store context/membership → channel → capability → permission → resource ownership. Form Requests validate known keys and privilege ceiling. Mutations use database transactions and invalidate permission/navigation caches after commit. Policies/controller authorization protect record ownership and special actions.

## 7. Central navigation design

Move menu definitions to a metadata tree containing stable key, translation key, icon, scope, route identity, active patterns, required channel, capability, permission, optional roles, badge resolver, children and order. `AdminNavigationService` filters this tree; Blade only renders filtered nodes.

Rules:

- Platform routes never resolve store badges/counts or store menus.
- Store routes require active StoreContext and membership.
- Empty groups disappear.
- Badge/KPI resolvers execute only after scope/channel/capability/permission checks.
- POS main navigation must remain usable when cashier shifts are disabled. Because current `pos.index` points to `CashierShiftController@index`, implementation must either make that action a safe core POS landing with conditional shift UI, or introduce a verified core POS landing route and update the resolver. Basic POS endpoints must remain available.
- Page buttons use the same service for create/edit/delete/export/approve/refund/adjust/staff-permission actions.
- Preserve expanded click accordion, collapsed body-teleported flyout, mobile drawer, Escape/outside click, keyboard focus, scrolling and persisted state.

## 8. Module/channel management and staff UI

Add Business Setup pages for Modules and Sales Channels. Each card shows status, description, dependencies, entitlement (only if a real plan system exists), blockers and impact. Core/protected items are read-only. Disabled module/channel permissions remain visible but disabled/read-only; grants are preserved and reactivate when availability returns.

Staff list includes identity/contact, role, status, permission summary and last login if present. Create/edit includes profile, current-store assignment, active role, grants and denies. Matrix supports module select/clear, action controls, view dependency, confirmation, feedback and existing-value hydration. Role changes never delete overrides without explicit confirmation.

Module/channel changes and staff-permission changes record actor, target, store, before/after, reason, request ID, IP, user agent and timestamp using the verified `AuditLog::write(...)` signature.

## 9. Disable blockers and data preservation

Implement a blocker service only after verifying exact model namespaces, columns and statuses in source. Required domains: pending/unfulfilled online orders, open cashier sessions, open repair jobs, in-transit transfers, unresolved stock counts/reconciliation and outstanding debt where applicable. Never paste guessed queries into production.

Disabling hides navigation/widgets/actions, blocks backend routes with the agreed response, skips badges/KPIs/jobs/listeners/search results, and marks matrix groups unavailable. It never deletes records. Re-enable restores access to existing data.

## 10. Storefront navigation security and placement limits

Create `SafeNavigationUrlRule`. Trim, reject raw controls/null bytes, repeatedly percent-decode with a bounded loop, reject controls after every decode, normalize/reject backslashes, then reject protocol-relative forms on raw and every decoded stage. Allow only safe root-relative paths or absolute `https`; permit `http` only if existing policy explicitly requires it. Reject credentials and dangerous schemes. Cover `javascript:`, mixed case/whitespace, `data:`, `vbscript:`, `//evil`, `/\\evil`, `/%2f/evil`, `/%5cevil`, repeated encoding and CRLF.

Enforce desktop 10 and mobile-bottom 5 on create, update and re-enable/toggle inside transactions with locking. A concurrent request cannot exceed the limit.

## 11. Route mapping discipline

Generate a machine-readable manifest from `php artisan route:list --json`. Map every affected endpoint by method, URI and action; route name is nullable. Do not claim a fixed total route count in the plan. Tests assert every manifest entry exists and no protected write endpoint is unmapped. Confirm actual names such as `pos.shifts.open` (plural). Do not add names merely to satisfy a test unless compatibility impact is reviewed.

## 12. Localization

All new/existing touched user strings use translation keys with parity for repository-supported Myanmar, English and Simplified Chinese locales: menu labels, actions, modules/channels, modes, descriptions, dependency/blocker/plan messages, confirmations, success/errors, accessibility text, empty states and audit labels. Add an automated recursive key-parity test.

## 13. Implementation phases and approval gates

1. **Evidence gate:** docs, baseline tests, route/permission/capability/status inventories, dirty-tree report. No mutations.
2. **Foundations:** channel schema/cast/registry, permission service, middleware, compatibility/backfill dry-run tests.
3. **Backend enforcement:** affected routes/controllers/policies, ownership, transactions, cache invalidation, blockers and audit.
4. **Navigation:** metadata tree, filtered ViewModel, lazy badges/KPIs, Blade simplification, POS landing safety.
5. **Management UI:** staff matrix and module/channel settings.
6. **Security/localization:** URL rule, placement locking and translation parity.
7. **Verification:** focused tests, full suite/build, browser QA and evidence report.

If evidence contradicts this plan or requires unrelated business-logic changes, stop and request approval with the exact conflict. Commit implementation changes separately from any pre-existing work.

## 14. Mandatory automated coverage

Tests must assert behavior, not merely create users:

- Platform Owner platform-only navigation; store-scope equivalent access without menu mixing; no platform store queries.
- Store Owner own-store staff access; cross-store denial; Platform Owner target protection; last-owner invariants.
- Manager with/without explicit staff permission and privilege ceiling.
- Cashier POS visibility without staff-tools coupling; restricted modules hidden/403.
- Inventory, finance, technician, Ecommerce and custom-role visible/hidden menus, buttons and routes.
- View-only, create-only combinations, update without create/delete, delete denial and special actions.
- Role + grants − denies; wildcard rejection/legacy containment; inactive membership/role; cache invalidation; audit records.
- Empty groups, valid route identities, no hash links, translation parity.
- POS-enabled/Ecommerce-disabled checkout remains functional; online routes/KPIs/jobs skipped.
- Each capability/channel combination, dependencies, unknown keys, protected POS, blockers, data preservation, re-enable restoration and cross-store updates.
- Preset/override precedence, idempotent backfill, dry-run, repeated execution and rollback/restore safety.
- URL allow/reject vectors and placement limits including toggle/concurrency.
- Query-listener assertions prove disabled badge/KPI queries do not run.

Run exactly:

```bash
php artisan test
npm ci
npm run build
```

## 15. Browser QA

Test light/dark at 1440×900 expanded and collapsed, 1366×600, 768×1024 and 390×844 for Platform Owner, Store Owner, Manager with/without staff management, Cashier, Inventory Staff, Technician, Finance Staff, Ecommerce Staff and custom role. Exercise list/create/edit matrices, select/clear, validation, save, unauthorized/cross-store attempts, immediate menu/action refresh, accordion/flyout/scroll/focus/Escape/outside click. Record screenshots for major roles and permission UI, plus console and failed-network results. A single screenshot is insufficient.

## 16. Completion report and acceptance criteria

Return commit SHA/branch link, complete changed-file list with reason, migration/backfill/dry-run/rollback results, final capability/channel/role/permission/route/menu/action matrices, staff workflow, effective-permission examples, isolation/audit/cache/query evidence, exact test/build output with passed/failed/skipped counts, browser checklist/screenshots, console/network results, known limitations and confirmation that unrelated files were untouched.

The work is accepted only when all approved tests pass, build succeeds, required browser QA is evidenced, no existing POS/Ecommerce workflow is accidentally disabled, direct unauthorized URLs are blocked, and the working tree/commit contains only scoped changes. Environmental limitations must be reported honestly and never described as success.

## Approval statement

Approval authorizes implementation of this plan only. It does not authorize invention of unverified features, unrelated refactors, production deployment, destructive data changes or rewriting user-owned unrelated changes. After approval, begin with Phase 1 evidence and stop at any material contradiction.

---

## Source 2: `archive/superseded-plans/pos-resale-plan/02-target-design.md`

**SHA-256:** `e878c6187635aa8bcca4a4aa6281466e0aa8a04e12fcc88719885788b088e847`

# ၂။ လိုချင်တဲ့ ပုံစံ (Target Design)

> [!NOTE]
> **Archived Historical Document:** ဤဖိုင်သည် ၂၀၂၆ ခုနှစ် သြဂုတ်လ Phase 2/2.5 ကာလမှ မူလ Target Design စာရွက်စာတမ်းဟောင်း ဖြစ်သည်။
> လက်ရှိ Canonical Architecture အတွက် `../../../architecture/Source_of_Truth_Master_MM.md` (`../../../architecture/Source_of_Truth_Master_MM.md`; see consolidated index) ကိုသာ ကြည့်ရှုအသုံးပြုပါ။

> **ဒီဖိုင်မှာ:** POS စနစ်ကို ဘယ်လို ပုံစံနဲ့ တည်ဆောက်မလဲ — architecture, module ခွဲထားမှု, deployment model ၂ မျိုး, inventory ledger, money policy, sale lifecycle။
>
> **အခြေခံ:** `../Source_of_Truth_Master_MM.md` (`../../../architecture/Source_of_Truth_Master_MM.md`; see consolidated index) (Master SoT) — Single Codebase Multi-Store architecture နှင့် inventory ledger စည်းမျဉ်းများ။ မူလ v1/v2 draft များကို `docs/archive/source-of-truth-history/` တွင် archive ပြုလုပ်ထားသည်။
>
> **သတိပြုရန်:** ဒီဖိုင်က SoT နဲ့ ဆန့်ကျင်နေသော အချက်များရှိပါက — SoT amendment ကို Owner ထံ တင်ပြပြီး အတည်ပြုမှ ရေးသားရမည် (SoT §33)။ လက်ရှိ ဆန့်ကျင်ချက်စာရင်း → ဒီဖိုင် အောက်ဆုံး "SoT Conflicts" အပိုင်း။

---

## ၂.၁ အဓိက ဆုံးဖြတ်ချက်: **တစ်ခုတည်းသော codebase**

Ecommerce ရော POS ရော **ဒီပရောဂျက်ထဲမှာပဲ** ဆောက်မယ် — ပရောဂျက်အသစ် သီးခြား မဆောက်ဘူး (SoT §4.1)။

**ဘာကြောင့်လဲ:**
- POS က catalog (products/SKU/prices) ကို ဒီထဲကပဲ ယူသုံးရမယ်
- Staff/auth/store isolation က ရှိပြီးသား
- Deploy pipeline တစ်ခုတည်း
- Cloud SaaS တွင် ဖောက်သည်တစ်ယောက် = `stores` table ထဲ tenant row တစ်ခု

---

## ၂.၂ Module ခွဲထားမှု (အရေးအကြီးဆုံး)

တစ်ခုတည်းထဲ ဆောက်ပေမဲ့ **POS ကို တင်းကျပ်တဲ့ module** အနေနဲ့ ခွဲထားရမယ်:

| အပိုင်း | စည်းမျဉ်း |
|---|---|
| Namespace | `App\POS\...` (Controller/Model/Service အကုန်) — ecommerce code နဲ့ မရောနှောရ |
| Tables | POS tables သီးခြား (`branches`, `warehouses`, `inventory_movements`, `sales`, ...) — ecommerce `orders` ကို ပြန်မသုံးရ (SoT §5) |
| Routes | `/pos` + `/pos/admin` — route group သီးခြား + middleware သီးခြား။ **Static registration** (၂.၄ ကြည့်ပါ) |
| Service Worker | `/pos/sw.js` (scope: `/pos/`) — storefront `/sw.js` နဲ့ မရောနှောရ (SoT §4.3) |
| CSS/JS | `pos.css` / `pos.js` — Vite entry သီးခြား |
| Tests | `tests/Feature/POS/...` — သီးခြား directory |
| Migration | POS migrations က add-only — ecommerce tables ကို မပြောင်း (ခြွင်းချက်: inventory adapter အတွက် approved migration) |
| Catalog share | POS က products ကို **read-only** share — inventory ကို shared ledger နဲ့ ထိန်း (၂.၅) |

---

## ၂.၃ Deployment Model ၂ မျိုး — **တစ်ခုနဲ့တစ်ခု မရောရ**

တစ်ခုတည်းသော codebase ကို model ၂ မျိုးနဲ့ run လုပ်လို့ရမယ်။ ဒီနှစ်မျိုးက **မတူညီတဲ့ system** ဖြစ်ပြီး "one deployment per cloud customer" နဲ့ "one multi-tenant SaaS application" ကို တစ်မျိုးတည်းလို မရေးရ။

### Model A — Cloud (Multi-tenant SaaS) — ပုံမှန်

| အချက် | အသေးစိတ် |
|---|---|
| Application | **ဗဟို application တစ်ခုတည်း** — cloud ပေါ်မှာ တစ်ခါတည်း run |
| Tenants | Store/tenant အများကြီး — တစ်ယောက်ချင်းစီအတွက် သီးခြား install/deploy **မလုပ်** |
| Isolation | တင်းကျပ်သော `store_id` scope — query/resource အဆင့်တိုင်း (SoT §6) |
| Modules | Store အလိုက် enabled modules (POS / Ecommerce / Both) |
| Admin | Platform Owner က tenant အကုန် စီမံ · Store Owner က သူ့ဆိုင်ပဲ |
| Domain | စတင်တွင် path/subdomain (`/store/{slug}`) — custom domain က နောက်ပိုင်း ထည့်နိုင် |
| Database | Cloud MySQL (central source of truth) |
| Offline | Cloud PWA offline queue (IndexedDB + idempotent sync) — Phase 3 |

### Model B — Local (Single-tenant installation) — offline ဖောက်သည်

| အချက် | အသေးစိတ် |
|---|---|
| Installation | ဖောက်သည်တစ်ယောက်အတွက် **dedicated install တစ်ခု** — ဆိုင်ထဲ PC ပေါ်မှာ |
| Database | **SQLite** — local |
| Network | Local PC / LAN (ဆိုင်ထဲ Wi-Fi) — browser devices က LAN ကတဆင့် ဝင်သုံး |
| Internet | **အမြဲ internet မလို** — cloud sync မလို |
| License | Signed offline license (public-key verify) — **resale နောက်ပိုင်း** (Phase 5)၊ MVP မစမ်းရသေးခင် မလုပ် |
| Backup | Versioned backup/restore/update workflow — live file copy မဟုတ်ဘူး (၂.၁၅) |

### Operational consequences (နှစ်မျိုးလုံး)

| လုပ်ငန်းဆောင်တာ | Cloud SaaS | Local install |
|---|---|---|
| Tenant အသစ် | `store:create` command → tenant row (deploy မလို) | Install package တစ်ခုလုံး ပေးရ |
| Update | တစ်ခါ deploy → အကုန် ရောက် | ဆိုင်တစ်ဆိုင်ချင်းစီ — versioned update workflow |
| Data center | ဗဟို MySQL တစ်ခု | SQLite — ဆိုင်တစ်ဆိုင်စီ သီးခြား |
| Scale | ဆိုင်ခွဲ/ဖောက်သည် များလာရင် infra ပြင်ရ (၂.၁၆) | Scale မလို — local ပဲ |
| Backup | Central daily + runbook | Versioned local backup (checksum + manifest) |
| Support | Platform Owner က remote (Support Mode — ၂.၁၃) | ဆိုင်မှာ လက်နဲ့ လုပ်ရ / support access ပိုခက် |

---

## ၂.၄ Module / Capability Enforcement — **Static Routes + Server-side Middleware**

**အရေးကြီးဆုံး correction:** tenant/feature flag ပေါ်မူတည်ပြီး route တွေကို conditionally register **မလုပ်ရ**။ Laravel route caching (`php artisan route:cache`) နဲ့ မကိုက်ညီလို့ပါ။

**နည်းလမ်း:**
1. POS routes အားလုံးကို **statically register** လုပ်မယ်
2. Route group ပေါ်မှာ **server-side module/capability middleware** တပ်မယ်:
   - Active store ကို resolve လုပ်
   - Module enabled လား စစ် (store-level)
   - Branch capability ရှိလား စစ် (branch-level)
   - Branch access + user role permission ရှိလား စစ် (SoT §7: `branch access AND branch capability AND role permission`)
   - မရရင် ရည်ရွယ်ချက်ရှိရှိ **403 သို့မဟုတ် 404** ပြန်
3. UI မှာ မရတဲ့ navigation items တွေကို **hide** — ဒါက authorization မဟုတ်ဘူး၊ UX သက်သက်ပဲ
4. Backend authorization က **authoritative** (UI hide တစ်ခုတည်း မလုံလောက် — SoT §7)

**Permission အဆင့် ၄ မျိုး — သီးခြား ခွဲထားရမယ်:**

| အဆင့် | ဘာကို ထိန်း | ဥပမာ |
|---|---|---|
| Store-level modules | ဘယ် module ဖွင့်လဲ | `enabled_modules`: pos / ecommerce / service / inventory / finance |
| Branch-level capabilities | ဘယ် branch မှာ ဘာလုပ်လို့ရလဲ | `branch_capabilities`: pos_sales, inventory, service, customer_debt, ... (SoT §7) |
| User permissions | ဘယ် user က ဘယ် branch မှာ ဘယ် action | `user_branch_roles` + policies (Owner/Admin/Manager/Cashier/Read-only) |
| Approval permissions | ဘယ် action က ဘယ်သူ့ approval လို | Manager approval: adjustment, void/reverse, discount override, handoff, backdated (SoT §8) |

---

## ၂.၅ Inventory Ledger — **POS ရော Ecommerce ရဲ့ တစ်ခုတည်းသော Source of Truth**

> **Correction:** Ledger ကို "POS-only stock system" အဖြစ် ဒီဇိုင်းမလုပ်ရ။ **POS နဲ့ Ecommerce နှစ်ခုလုံးရဲ့** authoritative inventory source ဖြစ်ရမယ်။

`products.quantity` / `products.stock_status` တစ်ခုတည်းကို inventory truth အဖြစ် မသုံးရ (SoT §14.1) — Revision 2 မှာ **stock_status က migration ကာလအတွင်း derived compatibility/cache field အဖြစ်သာ** ကျန်ရစ်မယ်။ Ledger ကနေ recalculate/rebuild လုပ်နိုင်ရမယ်။

### Ledger က ထောက်ပံ့ရမယ့် movement types (အနည်းဆုံး)

| Movement | Effect | မှတ်ချက် |
|---|---|---:|---|
| `opening_balance` | + | Migration batch |
| `purchase_received` | + | Goods receipt ဖြစ်မှ (PO တင်ရုံနဲ့ မတိုး) |
| `purchase_returned` | − | Supplier settlement ပါ update |
| `pos_sale` | − | Walk-in sale |
| `pos_return` | + | Refund/exchange return |
| `online_order_reservation` | − | Online order confirm ချိန်မှာ reserved |
| `online_order_confirmation` | − | Reserved → committed |
| `online_order_cancellation` | + | Cancel → availability ပြန် |
| `inventory_adjustment` | ± | Cashier က submit → manager approve မှ |
| `stock_count` | ± | Count difference |
| `transfer_out` | − | Dispatch |
| `transfer_in` | + | Receipt |
| `service_consumption` | − | Service parts (နောက်ပိုင်း) |
| `service_part_return` | + | Service parts (နောက်ပိုင်း) |
| `reversal` | ± | Correction — `reversal_of_id` နဲ့ link |

### Online order reservation policy (Ecommerce adapter)

- **Reserve လုပ်ချိန်:** Online order confirm လုပ်တဲ့အခါ (Viber/Telegram confirm → `online_order_reservation` movement)
- **Commit လုပ်ချိန်:** Order က fulfillment စတင် / payment ပြည့် / dispatch — `online_order_confirmation` (reserved → committed)
- **Reservation expire/release:** သတ်မှတ်ထားတဲ့ ကာလအတွင်း confirm/fulfill မဖြစ်ရင် auto-release (`online_order_cancellation`) — ကာလကို Owner သတ်မှတ် (Open Decision)
- **Cancel:** Cancel ဖြစ်ရင် reserved quantity ကို availability ပြန်ထည့်
- **Reconciliation:** `inventory_movements` ကနေ balance rebuild/verify — ledger နဲ့ balance ကွာရင် alert + review workflow

### Ecommerce integration (adapter/service)

- Existing `orders` + `order_items` ကို **ပြောင်းမလုပ်** — `InventoryAdapter` / service က order lifecycle events တွေကို ledger movements အဖြစ် ပြောင်းပေးမယ်
- POS sale ရော online order ရော တူညီတဲ့ stock pool ကနေ ယူလို့ **oversell မဖြစ်ရ**
- `sale_source` / order reference ကို movement `source_type`/`source_id` မှာ မှတ်

### Ledger table spec (အနည်းဆုံး fields)

`inventory_movements`: `store_id`, `branch_id`, `warehouse_id`, `product_id`, `product_variant_id`, `movement_type`, `quantity_delta`, `unit_cost`, `source_type`, `source_id`, `client_transaction_id`, `occurred_at`, `posted_by`, `reversal_of_id`, `metadata` (JSON), timestamps

### Ledger rules (မဖြစ်မနေ)

1. **Posted movement ကို edit/delete မလုပ်ရ** — correction က reversal movement နဲ့သာ (SoT §15.1)
2. **Duplicate posting မဖြစ်ရ** — `(store_id, source_type, source_id)` သို့မဟုတ် `client_transaction_id` unique constraint (SoT §19.2)
3. **Offline retry idempotent** — same `client_transaction_id` → existing result ပြန် return
4. **Balance update က transactional** — `DB::transaction` (SoT §19.3)
5. **Concurrent sales မှားမဖြစ်ရ** — row lock / atomic update ဖြင့် race ကာကွယ်
6. **`inventory_balances` က derived performance cache** — direct write မလုပ်ရ
7. **Reconciliation command** — `php artisan inventory:reconcile` — movements ကနေ balances rebuild/verify + mismatch report

### Indexes (အနည်းဆုံး)

- Unique: `(store_id, source_type, source_id)` · `(store_id, client_transaction_id)`
- Index: `(warehouse_id, product_id, product_variant_id)` · `(occurred_at)` · `(reversal_of_id)` · `(movement_type)` — MySQL + SQLite compatible

---

## ၂.၆ Money, Quantity နဲ့ Rounding Policy

**Float ကို money/quantity အတွက် မသုံးရ။**

| အကြောင်း | ဆုံးဖြတ်ချက် |
|---|---|
| MMK storage | **Integer (ကျပ်)** — DB column: `BIGINT`/`INTEGER` (cents/ပြား မလို) — သို့မဟုတ် decimal(16,2) — နှစ်မျိုးလုံး float မဟုတ် |
| Quantity precision | `DECIMAL(12,3)` (MySQL) / `NUMERIC(12,3)` (SQLite) — fractional qty foundation (0.5kg, 2.5L) |
| Unit cost precision | `DECIMAL(14,4)` — weighted average အတွက် precision ပိုမြင့် |
| Discount/tax order | Line discount → line tax → line total → subtotal → order discount → tax → **grand total** (အစဉ်ကို Phase 0 မှာ acceptance test နဲ့ သေချာ သတ်မှတ် — Open Decision #14) |
| Receipt total rounding | Round ကို **final step တစ်ခါတည်းသာ** — intermediate rounding မလုပ် |
| Exchange rate (future) | `DECIMAL(14,6)` — နောက်ပိုင်း multi-currency အတွက် column ပဲ ထားမယ် — **full multi-currency feature ကို MVP မစောင့်ရ** |
| Posted sale totals | Posting ချိန်မှာ **immutable calculated totals** သိမ်း — နောက် price ပြောင်းရင် မပြောင်း |

---

## ၂.၇ Inventory Valuation — **Weighted-average Costing** (ကနဦး Mobile/Electronics MVP)

- **Receiving:** New stock ဝင်ရင် — `new_avg_cost = (existing_qty × existing_avg_cost + received_qty × received_unit_cost) / total_qty`
- **Returns (sales return):** Return က cost ကို ပြန်မတွက်ဘူး — original movement ရဲ့ unit cost ကို သုံး (sale line cost ပြန် restore)
- **Purchase return:** Supplier return က weighted average ကို ပြန်တွက် (returned qty ရဲ့ cost ကို နုတ်)
- **Adjustments:** Adjustment က လက်ရှိ avg cost ကို သုံး (valuation change မဟုတ်ဘူး) — special case ကို Phase 0 မှာ သတ်မှတ်
- **Serial/IMEI items:** Specific cost ထိန်းနိုင်ရမယ် (serialized item တိုင်းရဲ့ cost သီးခြား) — လိုအပ်ရင် movement `metadata` မှာ မှတ်
- **Negative inventory:** Default **ပိတ်ထား** (၂.၈) — negative ဖြစ်ချိန် cost calculation ကို မလုပ်ရအောင် block
- အားလုံး costing logic က `App\POS\Services\CostingService` ထဲ စုပြီး test ရေးရမယ်

---

## ၂.၈ Negative Stock Policy

- **Default:** Negative available stock ဖြစ်စေမည့် sale/adjustment ကို **block** (SoT §14.3)
- Exception (နောက်ပိုင်း ဒီဇိုင်း): Authorized manager override — ဒါပေမဲ့ override တိုင်း **audit + visibly reported** ဖြစ်ရမယ်
- Override design က Phase 0 မှာ သတ်မှတ်မယ် (Open Decision #16)

---

## ၂.၉ POS Sale State Machine

```
Draft → Held → Posted → Partially Refunded → Refunded
                     ↘ Reversed
Voided (posting မတိုင်ခင် — draft/held ကိုသာ)
```

| State | Rules |
|---|---|
| Draft / Held | Edit လို့ရ — hold/resume sale (MVP ပါ) — held sale က သိမ်းထားပြီး နောက်မှ resume |
| Posted | **Edit/delete မလုပ်ရ** — receipt number က **posting ချိန်မှာ** assign |
| Partially Refunded / Refunded | Refund document ဖြင့်သာ — original sale ကို မပြောင်း |
| Reversed | Posting အမှားကို reversal နဲ့ ပြင်ရမယ် (SoT §15.1) |
| Voided | Posting မတိုင်ခင် draft/held ကိုသာ void — posted ဖြစ်ပြီးသား sale ကို void မလုပ်ရ |

**Atomicity:** Posted sale တစ်ခုမှာ — sale header + lines + payments + inventory movements + finance entries + audit record အကုန် `DB::transaction` ထဲ (SoT §15.2, §19.3)

**Printing:** Print မအောင်လို့ posted sale ကို **ပြန်မဖျက်ရ** — sale က ပြီးပြီး၊ reprint ပဲ လုပ်ရမယ်။ Reprints တွေကို audit (reprint log) လုပ်ရမယ်

---

## ၂.၁၀ Cashier Shift နဲ့ Daily Closing — အဆင့် ၃ မျိုး

**အရေးကြီး:** ဒီသုံးမျိုးက မတူဘူး — ရောထွေးမရေးရ။

| အဆင့် | ဘာလဲ | ဘယ်အချိန် |
|---|---|---|
| **Cashier shift closing** | Cashier တစ်ယောက် + register/device တစ်ခု — shift စ/ဆုံး | နေ့စဉ် — shift တိုင်း |
| **Branch daily closing** | Branch တစ်ခုလုံးရဲ့ နေ့စဉ် summary — shifts အကုန် ပေါင်း | နေ့ကုန် |
| **Finance/accounting period closing** | စာရင်းကိုင်ကာလ (monthly...) — ledger ကို ပိတ် | ကာလအလိုက် (နောက်ပိုင်း) |

**Cashier shift fields (အနည်းဆုံး):** branch, device/register, cashier, opening_time, opening_cash, cash_sales, cash_refunds, cash_in/out, expected_closing_amount, actual_closing_amount, difference, notes, closed_by, manager_approval (required ဖြစ်ရင်)

**MVP (Phase 2):** Cashier shift closing + simple branch daily summary — finance period closing က Operations phase

**Daily closing rule (SoT §18):** Unresolved pending offline sale ရှိနေချိန် final closing ကို approve မလုပ်ရ (owner-approved exceptional procedure မရှိရင်)

---

## ၂.၁၁ Branches နဲ့ Warehouses — ဆက်နွယ်မှု

- **Branch** = business/sales location · **Warehouse** = inventory location
- Branch တစ်ခုမှာ warehouse တစ်ခု သို့မဟုတ် အများကြီး ရှိနိုင် (SoT §14.2)
- **Store တိုင်းကို default branch + default warehouse တစ်ခုစီ auto-create** လုပ်ပေးမယ်
- **Single-branch store** မှာ branch-switching UI ကို မပြရ — unnecessary complexity
- **Multi-branch UI** က branch > 1 ဖြစ်ပြီး multi-branch capability enabled မှသာ ပေါ်

---

## ၂.၁၂ Offline System ၂ မျိုး — သီးခြားခွဲ

### (a) Cloud PWA offline queue (Cloud SaaS အတွက် — Phase 3)
- Central MySQL + POS PWA + IndexedDB + offline transaction queue
- Internet ပြန်ရတဲ့အခါ sync · device registration/revocation (SoT §9) · conflict handling (SoT §19.5) · idempotency (SoT §19.2) · failed-queue recovery/export

### (b) Local LAN installation (Model B — Phase 5)
- Laravel က ဆိုင်ထဲ PC ပေါ်မှာ run · SQLite · browser devices တွေ LAN/Wi-Fi ကတဆင့် ဝင်
- **ပထမ Local release မှာ central cloud sync မပါ** — dedicated backup/restore/update workflow သာ
- Cloud-to-local sync က **proven customer demand ရှိမှသာ** (နောက်ပိုင်း)

**ဒီနှစ်ခုကို phase တစ်ခုထဲ မရော။** Recommended order → ROADMAP.md

---

## ၂.၁၃ Platform Owner Support Access — Explicit Workflow

> **Correction:** Platform Owner ကို "store အကုန် invisible ဝင်လို့ရတယ်" ဆိုတဲ့ လွတ်လပ်တဲ့ access **မပေးရ** — SoT §6 ရဲ့ store isolation ကို မချိုးရ။

**Store Support Mode workflow:**
1. Enter Store Support Mode — **reason မဖြစ်မနေ ရိုက်ရမယ်**
2. Start/end time ကို မှတ်တမ်းတင်မယ်
3. **Write တိုင်း audit** — actor, store, entity, before/after, reason (SoT §20)
4. **Active store ကို ရှင်းရှင်းလင်းလင်း ပြ** — banner/indicator ဖြင့်
5. **Accidental cross-store write ကို ကာကွယ်** — support session ထဲမှာ store context lock
6. **Finance / data-export actions တွင် ပိုတင်းကျပ်** — extra confirmation + approval
7. **Store Owner visibility** — သင့်တော်ရာ support activity ကို store owner က မြင်နိုင် (support log)

Platform Owner access ကို "unscoped tenant query ရေးခွင့်" အဖြစ် မသုံးရ — support session ကလွဲပြီး ပုံမှန် query တွေက store-scoped ဖြစ်ရမယ်

---

## ၂.၁၄ Industry Scope — **Mobile/Electronics POS က ပထမဆုံး**

> **Correction:** "Industry-agnostic core" ကို abstract လုပ်နေရုံနဲ့ မရ — **ပထမဆုံး စစ်မှန်တဲ့ product က Mobile/Electronics POS** ဖြစ်ရမယ်။

**Mobile/Electronics MVP core (Priority):**
- SKU · Barcode/HID scan · Product variants · Piece-based UOM · Decimal quantity foundation
- Serial/IMEI tracking · Warranty · Retail/Wholesale pricing · Customer debt
- Receiving · Branch/Warehouse inventory · Returns/Exchanges

**နောက်ပိုင်း Operations phase:** Mobile repair/service jobs · full purchasing · supplier payables

**Future extension points — ဖောက်သည်အစစ် မပေါ်မချင်း မဆောက်ရ:**
Pharmacy (expiry/batch) · Grocery/scale integration · Gold pricing (ကျပ်/ပဲ/ရွေး) · Restaurant KOT/tables · Fuel pumps · Advanced fashion matrix

Architecture က forward-compatible ဖြစ်ရမယ် (UOM, decimal qty, custom fields) — ဒါပေမဲ့ pack တွေက **customer demand ပေါ်မှသာ** (Phase 6)

---

## ၂.၁၅ Backup / Restore — Versioned Workflow (Local Edition)

> **Correction:** SQLite backup ကို "live file copy" အဖြစ် မရေးရ။

**Backup package (တစ်ခုချင်းစီမှာ):**
- Consistent DB snapshot (WAL checkpoint ပြီးမှ)
- Uploaded files/assets
- Manifest (created_at, app version, schema version, checksums)
- Integrity verification

**Restore workflow:**
- Restore dry-run/check · Automatic pre-restore backup · Version compatibility validation · Clear failure/recovery behavior

**Offline licensing:** Resale Readiness (Phase 5) မှာ — MVP မစမ်းရသေးခင် မလုပ်။ License = **signed payload** — public key နဲ့ verify။ **Private signing key ကို customer installation ထဲ ဘယ်တော့မှ မထည့်ရ**

---

## ၂.၁၆ Hosting / Operations — Upgrade Triggers

**AlinnThit pilot အတွက်:** Existing shared hosting (Hostinger) က လက်ခံနိုင် (SoT Hosting Decision)

**Resale SaaS အတွက်:** အောက်ပါတွေ လိုအပ်နိုင်တဲ့ infra — persistent queue workers, scheduler reliability, concurrent DB transactions, backups, monitoring, sync API traffic, large imports, error reporting

**Measurable upgrade triggers (ဒီထက်ကျော်ရင် VPS/infra ပြောင်းရမယ် — Owner နဲ့ ဆုံးဖြတ်):**
- Active tenants > N (ဥပမာ 25–50)
- Sync queue backlog / response time threshold ကျော်
- Peak concurrent POS/online requests ကျော်
- Import job runtime ကျော် / scheduler miss
- Storage/backup size ကျော်

(Exact thresholds က Phase 0 မှာ metric နဲ့တကွ သတ်မှတ်မယ်)

---

## ၂.၁၇ POS ရဲ့ အဓိက Modules (Revised)

1. POS Sales (Draft/Held/Posted/Return/Reversal) · 2. Cart + Barcode + Hold/Resume
3. Split Payments (Cash/KPay/WavePay/CB Pay/MMQR) · 4. Receipt + Reprint
5. Customer Credit/Debt · 6. Simple Receiving · 7. Opening Stock
8. Inventory Adjustments (manager approval) · 9. Cashier Shift + Daily Closing
10. Minimal Sales/Cash/Stock Reports · 11. Audit Trail
→ **Operations phase:** Full purchasing, purchase returns, supplier payables, stock transfers, stock counts, service jobs, expenses, finance ledger, advanced reports

Inventory ကို **shared ledger (immutable movements)** နဲ့ ထိန်းမယ် — `products.quantity` တစ်ခုတည်း မဟုတ်ဘူး (SoT §10)

---

## SoT Conflicts (✅ Approved 2026-08-10 + Applied — နှစ်ဖိုင်လုံးတွင် ပြင်ပြီး)

| # | SoT အခန်း | ဆန့်ကျင်ချက် | Applied amendment |
|---|---|---|---|
| 1 | SoT §5 (နှစ်ဖိုင်လုံး) — "Automatic Ecommerce Inventory Sync Out of Scope / Manual stock" | Ledger က POS+Ecommerce နှစ်ခုလုံးရဲ့ source of truth — adapter နဲ့ auto integrate | §5/§4 ပြင်ပြီး — manual maintain → ledger-derived · `online_reserve/confirm/cancel` movement types ထည့်ပြီး |
| 2 | SoT §28/§19 — Phase 2 Online POS တွင် debt/closing မပါ | Customer Debt + Cashier Shift + Daily Closing က Online POS MVP (Phase 2) ထဲ | Phase 2 scope ပြင်ပြီး — debt/closing ထည့်ပြီး၊ Phase 4 မှ ဖယ်ပြီး |
| 3 | SoT §28/§19 — AlinnThit pilot phase မရှိ၊ Local LAN edition phase မရှိ | Phase 2.5 Pilot + Phase 5 Local/Resale + Phase 6 packs ထည့်မယ် | Phase list ပြန်ရေးပြီး — Phase 0–6 အသစ် |
| 4 | SoT Open Decision #14/#15 (Price/Discount rules) | Money/rounding policy သတ်မှတ်ပြီး (၂.၆) | SoT မှာ Resolved အဖြစ် မှတ်ပြီး |
| 5 | SoT မှာ inventory valuation rule မရှိ | Weighted-average costing (၂.၇) | SoT §14.4 / §10.4 — Weighted-Average Costing rule ထည့်ပြီး |

---

## ၂.၁၈ ဆက်ဖတ်ရန်

- `ROADMAP.md` (Sales & Market Model section) — ဖောက်သည်တွေကို ဘယ်လို ရောင်းမလဲ
- `ROADMAP.md` — ဘယ်ကစ ဆောက်မလဲ

---

## Source 3: `archive/superseded-plans/pos-resale-plan/ROADMAP.md`

**SHA-256:** `358d847d82fed48338dab4ef2ed7a003b11c4e5c47612ae8d9a81c1696ca86dc`

# DataPOS POS + Resale Plan — ခြုံငုံဖတ်ရန်

> [!NOTE]
> **Archived Historical Document:** ဤဖိုင်သည် ၂၀၂၆ ခုနှစ် သြဂုတ်လ Phase 2/2.5 ကာလမှ မူလ Resale Plan စာရွက်စာတမ်းဟောင်း ဖြစ်သည်။
> လက်ရှိ Canonical Architecture နှင့် Growth Strategy အတွက် `../../../architecture/Source_of_Truth_Master_MM.md` (`../../../architecture/Source_of_Truth_Master_MM.md`; see consolidated index) နှင့် `../../../plans/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md` (`../../../plans/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md`; see consolidated index) ကိုသာ ကြည့်ရှုအသုံးပြုပါ။

> **ရည်ရွယ်ချက်:** ဒီဖိုဒါမှာ DataPOS ရဲ့ **POS စနစ်** ကို ဘယ်လို တည်ဆောက်မယ်၊ ကိုယ့်ဆိုင်မှာ ဘယ်လို သုံးမယ်၊ ပြီးတော့ **အခြားလုပ်ငန်းရှင်တွေကို ပြန်ရောင်းချ** မယ့် ပုံစံတွေကို ရှင်းပြထားတဲ့ စာရွက်စာတမ်းတွေ ဖြစ်ပါတယ်။
>
> **ဖတ်ရမယ့်သူ:** Project Owner (ဆရာကြီး) — နားလည်ပြီး ဆွေးနွေးဖို့အတွက်
>
> **ရက်စွဲ:** 2026-08-10 (Revision 2) · **2026-08-13:** `03-sales-market-model.md` ကို ဒီဖိုင်ထဲ ပေါင်းထည့်ပြီးပါပြီ (အောက်က "Sales & Market Model" section) · **2026-08-17:** POS cashier session (13 commits) အခြေအနေ ထည့်သွင်းပြီး (§1.2) · **2026-08-17 (pm):** Phase 2.5 cutover features (opening-stock reconciliation + debt opening balances import) — ဒီနေ့ 17 commits (§1.2) · **2026-08-20:** purchasing batch (suppliers/payables/aging + warehouses/transfers/buy-back) (§1.2)

---

## ဖိုင်တွေရဲ့ အကြောင်းအရာ

| ဖိုင် | အကြောင်းအရာ | ဘာတွေသိရမလဲ |
|---|---|---|
| `ROADMAP.md` | **ခြုံငုံ အစီအစဉ် (လုပ်ပြီးသား + ဆက်ဆောက်မယ့်ဟာ)** | လက်ရှိ အခြေအနေ (Ecommerce, multi-store, PWA, POS Phase 1–2.5 + **Cashier Home UI + cashier session (register-lock UX, held-sale expiry, shared customers, tiered pricing, price override, manager PIN, drag-to-scroll)** + **Phase 2.5 cutover (opening-stock reconciliation, debt opening balances)** — **884 tests pass 2026-08-17**) + Implementation Phases: Phase 0 (Decisions) → 1 (Foundation) → 2 (Online POS MVP) → 2.5 (AlinnThit Pilot) → 3 (Cloud PWA offline) → 4 (Operations) → 5 (Local + Resale) → 6 (Industry packs) |
| `02-target-design.md` | **လိုချင်တဲ့ ပုံစံ** | Architecture — တစ်ခုတည်းသော codebase, deployment model ၂ မျိုး (Cloud SaaS / Local install), shared inventory ledger, money/rounding policy, POS sale state machine, cashier shift |
| ~~`03-sales-market-model.md`~~ | ~~စျေးကွက်အလိုက် ရောင်းချ/ထိန်းချုပ်ပုံ~~ | **2026-08-13: ဒီ `ROADMAP.md` ထဲ ပေါင်းပြီး** — အောက်က "Sales & Market Model" section ကြည့်ပါ |

---

## အနှစ်ချုပ် — အဓိက ဆုံးဖြတ်ချက်တွေ (Revision 2)

1. **Codebase တစ်ခုတည်း** — Ecommerce ရော POS ရော ဒီပရောဂျက်ထဲမှာပဲ ဆောက်မယ် (SoT §4.1)။ Module isolation: `App\POS\...` + `/pos` routes + သီးခြား SW/CSS/JS/tests
2. **Deployment model ၂ မျိုး — တစ်ခုနဲ့တစ်ခု မရောရ:**
   - **Cloud ဖောက်သည် = Multi-tenant SaaS** — ဗဟို application တစ်ခုတည်း၊ store/tenant အများကြီး၊ တင်းကျပ်သော `store_id` isolation၊ store အလိုက် enabled modules၊ Platform Owner စီမံ၊ Store Owner က သူ့ဆိုင်ပဲ မြင်၊ custom domain နောက်မှ ထည့်နိုင်
   - **Local ဖောက်သည် = Single-tenant install** — ဖောက်သည်တစ်ယောက် installation တစ်ခု၊ Laravel + SQLite၊ ဆိုင်ထဲ PC/LAN၊ အမြဲ internet မလို၊ resale နောက်ပိုင်းမှ signed offline license၊ versioned backup/restore/update workflow
3. **Inventory ledger က POS ရော Ecommerce ရဲ့ တစ်ခုတည်းသော stock source of truth** — `inventory_movements` (immutable) + `inventory_balances` (derived cache)။ Ecommerce orders ကို adapter/service ကတဆင့် integrate — POS/Ecommerce နှစ်ခုလုံး တူညီတဲ့ stock ကို oversell မလုပ်နိုင်။ `products.stock_status` က migration ကာလအတွင်း derived compatibility/cache field အဖြစ်သာ ကျန်ရစ်မယ်
4. **Module/capability enforcement — static routes + server-side middleware** — tenant ပေါ်မူတည်ပြီး route တွေကို conditionally register **မလုပ်ရ** (route caching နဲ့ မကိုက်ညီလို့)။ Module enabled → branch capability → user permission → approval permission — အဆင့် ၄ မျိုး သီးခြား ခွဲထားပြီး server-side က authoritative (UI hide မလုံလောက်)
5. **Money/quantity — float မသုံးရ** — MMK ကို integer (ကျပ်) သို့မဟုတ် decimal ဖြင့် သိမ်း၊ discount/tax rounding order သတ်မှတ်၊ weighted-average costing (ကနဦး Mobile/Electronics MVP)၊ negative stock default ပိတ်
6. **MVP scope ပြင်ဆင်** — Customer Debt + Cashier Shift + Daily Closing တွေကို နောက်ကျ Operations phase ထဲ မထားတော့ဘူး — **Online POS MVP (Phase 2) ထဲ ထည့်မယ်** (မြန်မာ့ဈေးကွက်ရဲ့ မဖြစ်မနေ selling feature)
7. **Offline system ၂ မျိုး သီးခြားခွဲ** — (a) Cloud PWA offline queue (IndexedDB + sync API) နဲ့ (b) Local LAN/SQLite install — phase တစ်ခုထဲ မရော။ Order: Online Cloud POS → AlinnThit Pilot → Cloud PWA offline → Local LAN edition → cloud-to-local sync (demand ရှိမှ)
8. **AlinnThit production pilot က resale မလုပ်ခင် မဖြစ်မနေ** — real data, parallel validation, reconciliation, real cashier usage, backup/restore test — pilot မတည်မငြိမ်ခင် ပြင်ပဖောက်သည်ကို မရောင်းရ
9. **ပထမဆုံး product = Mobile/Electronics POS** — SKU/barcode, variants, serial/IMEI, warranty, retail/wholesale, customer debt, receiving, branch/warehouse inventory, returns/exchanges — ကျန် industry packs (ဆေး/ကုန်စုံ/ရွှေ/စားသောက်ဆိုင်/ဓာတ်ဆီ/အဝတ်အထည်) က ဖောက်သည်အစစ် ပေါ်လာမှ
10. **Platform Owner support access ကို explicit workflow နဲ့** — Store Support Mode: reason + start/end time + write အကုန် audit + active store ကို ရှင်းရှင်း ပြ + accidental cross-store write ကာကွယ် + finance/export တွင် ပိုတင်းကျပ်

---

## ဖတ်ရန် အစီအစဉ်

1. `ROADMAP.md` ကစဖတ်ပါ (ဘာရှိပြီးသားလဲ + ဘယ်ကစမလဲ သိအောင်)
2. `02-target-design.md` ဖတ်ပါ (ဘယ်ကို ဦးတည်နေလဲ သိအောင်)
3. ဒီဖိုင်ရဲ့ အောက်က "Sales & Market Model" + "Implementation Phases" section တွေ ဖတ်ပါ (ဘယ်လို ရောင်းမလဲ + ဘယ်ကစမလဲ သိအောင်)

---

## ဆက်စပ်ဖိုင်များ

- `../Source_of_Truth_Master_MM.md` (`../../../architecture/Source_of_Truth_Master_MM.md`; see consolidated index) — Single Codebase Multi-Store Master Source of Truth (မူလ v1/v2 draft များကို `docs/archive/source-of-truth-history/` တွင် archive ပြုလုပ်ထားသည်)
- `CHANGELOG.md` — implementation history / changelog
- `docs/archive/deployment-runbook.md` — အရင် site ရဲ့ deploy history (archived 2026-08-13)
- `docs/ops/DEPLOYMENT.md` — deploy / backup-restore / secrets scrub မှတ်တမ်း


---

# ၃။ စျေးကွက်အလိုက် ရောင်းချ/ထိန်းချုပ်ပုံ (Sales & Market Model)

> **ဒီဖိုင်မှာ:** ဒီစနစ်ကို ဖောက်သည်တွေဆီ ဘယ်လို ရောင်းမလဲ — ဘယ်သူတွေ ဝယ်မလဲ၊ ဘယ်လို ထိန်းချုပ်မလဲ၊ စျေးနှုန်း ဘယ်လို သတ်မှတ်မလဲ။
>
> **Revision 2 (2026-08-10):** Deployment model ၂ မျိုး (Cloud SaaS / Local install) ကို ရှင်းရှင်းခွဲပြီး — "cloud ဖောက်သည်တိုင်းအတွက် သီးခြား deploy" ဆိုတဲ့ အဟောင်းပုံစံကို ဖျက်လိုက်ပြီ။

---

## ၃.၁ ဖောက်သည်အမျိုးအစား — Deployment model နဲ့ module ပေါင်းစပ်

| ဖောက်သည် | Deployment model | Module ဖွင့်ထားမယ့်အရာ |
|---|---|---|
| **Online ပဲလိုတဲ့သူ** | Cloud SaaS (tenant row) | `ecommerce` — လက်ရှိ storefront အတိုင်း |
| **POS ပဲလိုတဲ့သူ (internet ရှိ)** | Cloud SaaS (tenant row) | `pos` + sub-modules |
| **နှစ်ခုလုံးလိုတဲ့သူ** | Cloud SaaS (tenant row) | `pos` + `ecommerce` + shared inventory |
| **Offline ပဲလိုတဲ့သူ (ဆိုင်ထဲ PC)** | **Local single-tenant install** | `pos` + sub-modules — SQLite, LAN |

> ဖောက်သည်တစ်ယောက် = Cloud မှာ tenant row တစ်ခု (သို့) Local install တစ်ခု။ "Cloud ဖောက်သည်တစ်ယောက်စီအတွက် သီးခြား server/deploy" မလုပ်ရ။

---

## ၃.၂ လုပ်ငန်းအမျိုးအစား (Industry) — Mobile/Electronics က ပထမဆုံး

| လုပ်ငန်း | ကိုက်ညီမှု | Pack / Extension |
|---|---|---|
| **ဖုန်းဆိုင် / အီလက်ထရွန်းနစ်** | ✅ **ပထမဆုံး product** (ကိုယ့်ဆိုင်) | Serial/IMEI + warranty + service jobs (နောက်ပိုင်း) |
| **ဆေးဆိုင်** | 🔜 နောက်ပိုင်း | Expiry + batch/lot — demand ပေါ်မှ |
| **ရွှေဆိုင် / ဂျူးရတနာ** | 🔜 နောက်ပိုင်း | Weight pricing (ကျပ်/ပဲ/ရွေး) + daily rate + karat |
| **ကုန်စုံဆိုင်** | 🔜 နောက်ပိုင်း | Expiry + weight scale + multi-unit |
| **စားသောက်ဆိုင်** | 🔜 နောက်ပိုင်း | Table + KOT + combo |
| **ဓာတ်ဆီဆိုင်** | 🔜 နောက်ပိုင်း | Liter qty + fuel grade + pump |
| **အဝတ်အထည်** | 🔜 နောက်ပိုင်း | Size/color matrix + seasonal |
| **ပွဲရုံ / ခန်းမ** | ❌ အနည်းဆုံး | Booking/calendar module — သီးခြား လိုမယ် |

> **မူ:** Architecture က forward-compatible (UOM, decimal qty, custom fields) — ဒါပေမဲ့ pack တွေက **ဖောက်သည်အစစ် ပေါ်လာမှသာ** ဆောက်မယ်။ Pharmacy/Grocery/Gold/Restaurant/Fuel/Fashion ကို first-release မှာ မထည့်ရ။

---

## ၃.၃ ရောင်းချ/Install ပုံစံ — Model အလိုက်

### Cloud SaaS ဖောက်သည်အသစ် (Model A)

```
ဖောက်သည်အသစ် ရောက်လာရင်:
1. ဗဟို SaaS app ထဲမှာ php artisan store:create  (name, slug, plan)
2. enabled_modules သတ်မှတ် (pos / ecommerce / both)
3. Store owner account ဖန်တီး
4. ဆိုင်ဒေတာ ထည့်သွင်း (products import, branches → default branch/warehouse auto)
5. Deploy မလို — tenant row တစ်ခုပဲ
```

### Local install ဖောက်သည် (Model B)

```
ဖောက်သည်အသစ် ရောက်လာရင်:
1. Install package (Laravel + SQLite) ကို ဆိုင်ထဲ PC ပေါ် install
2. Setup wizard — store name, admin account, default branch/warehouse
3. License activation — resale နောက်ပိုင်းတွင် signed offline license
4. ဆိုင်ဒေတာ ထည့်သွင်း
5. Update/backup → versioned workflow (02-target-design §2.15)
```

### Install mode ရွေးစရာ

| Mode | ဘယ်သူ့အတွက် | Server ဘယ်မှာ | Internet |
|---|---|---|---|
| **Cloud (multi-tenant SaaS)** | internet ရှိတဲ့သူ, online လိုသူ | ဗဟို cloud app တစ်ခု (Hostinger...) | လို |
| **Local (Windows PC / LAN)** | offline ပဲလိုတဲ့သူ | ဆိုင်ထဲက PC — SQLite + LAN | မလို |

---

## ၃.၄ License / ထိန်းချုပ်မှု ပုံစံ

| အပိုင်း | Cloud mode | Local mode |
|---|---|---|
| License check | Online activation (server ကို မေး) | **Signed offline license** (public-key verify — private key ကို install ထဲ မထည့်ရ) — Resale Readiness (Phase 5) |
| Update | တစ်ခါ deploy → tenant အကုန် | Versioned update workflow — ဆိုင်တစ်ဆိုင်ချင်းစီ |
| Backup | Central daily + runbook | `php artisan backup` → versioned (snapshot + checksum + manifest) |
| Branch ပေါင်း | Multi-tenant app ထဲပဲ — branch capability ဖွင့် | Manual (versioned restore) သို့မဟုတ် single-branch သာ |

### License plan အဆင့် (ဥပမာ — ဆရာကြီး ဆုံးဖြတ်ရမယ်)

| Plan | ပါဝင်မှု | သင့်တော်တဲ့သူ |
|---|---|---|
| POS Basic | POS + inventory + single branch | ဆိုင်ငယ် |
| POS Pro | POS + debt + finance + daily closing | ဆိုင်လတ် |
| Ecommerce | Online storefront + orders | Online ပဲလိုသူ |
| Complete | အကုန် — multi-branch | ဆိုင်ကြီး / franchise |

> **အရေးကြီး:** Plan တွေက codebase မပြောင်း — `enabled_modules` ပဲ ပြောင်းတယ်။ Upgrade ဆိုရင် flag ဖွင့်ပေးရုံပဲ။

---

## ၃.၅ Platform Owner vs Store Owner — Support Access Workflow

- **Platform Owner (ဆရာကြီး):** SaaS app တစ်ခုလုံးကို စီမံ — plan, module flags, support
- **Store Owner (ဖောက်သည်):** သူ့ဆိုင်တစ်ခုပဲ — staff, products, POS

**Platform Owner က store ထဲ ဝင်ရမယ်ဆိုရင် — Store Support Mode ကိုသာ သုံးရမယ် (02-target-design §2.13):**

1. Enter Support Mode — **reason ရိုက်ရမယ်**
2. Start/end time record
3. **Write အကုန် audit** (actor, store, entity, before/after)
4. **Active store ကို ရှင်းရှင်း ပြ** (banner)
5. Accidental cross-store write ကာကွယ် (context lock)
6. Finance/export တွင် ပိုတင်းကျပ်
7. Store owner က သင့်တော်ရာ support activity ကို မြင်နိုင်

> Platform Owner ကို "store အကုန် invisible ဝင်လို့ရတယ်" ဆိုတဲ့ unrestricted access **မပေးရ** — SoT §6 store isolation ကို မချိုးရ။ Support session ကလွဲရင် ပုံမှန် query တွေက store-scoped ဖြစ်ရမယ်။

---

## ၃.၆ မြန်မာနိုင်ငံအတွက် အရေးကြီး feature (MVP ထဲ ပါ)

1. **ကြွေးစာရင်း (Credit/Debt)** — မြန်မာ့ဆိုင်တွေရဲ့ စာရင်းစာအုပ်ကို digitize — **MVP (Phase 2) ထဲ ပါ**
2. **Cashier shift + Daily closing** — expected vs actual cash — **MVP (Phase 2) ထဲ ပါ**
3. **Barcode/HID scanner + Split payments** (Cash/KPay/WavePay/CB Pay/MMQR) — MVP
4. **Warranty/Serial tracking** — ပြန်လဲ အများကြီးဖြစ်တဲ့အတွက် — Mobile/Electronics MVP
5. **Burmese language** — UI + receipt နှစ်မျိုးလုံး

---

## ၃.၇ ဆက်ဖတ်ရန်

- `ROADMAP.md` — ဒါတွေကို ဘယ်အချိန် ဘယ်လို ဆောက်မလဲ


---

## လက်ရှိ အခြေအနေ (Current State)



## ၁.၁ နည်းပညာအခြေခံ (Verified)

| အပိုင်း | အခြေအနေ |
|---|---|
| Framework | **Laravel 12.64** (PHP ^8.2) |
| Database | Local: **SQLite** (`database/database.sqlite`) · Production: MySQL (Hostinger) config ပါပြီးသား |
| Frontend | Blade + **Alpine.js** + **Tailwind CSS v4** (CSS-based `@theme`) |
| Assets | Vite — `app.css`/`app.js` (storefront) + `admin.css`/`app-admin.js` (admin) သီးခြား |
| Deploy | `deploy-datapos.sh` — **⚠️ အရင် project ရဲ့ live ကို သွားတဲ့ script — DataPOS အတွက် မသုံးရသေး** (README ကြည့်ပါ) |
| Testing | **994 tests pass / 4467 assertions** — PHPUnit, SQLite local (2026-08-20 run) |
| Dev server | `php artisan serve --port=8501` (README/.env အတိုင်း) |
| Git | main · remote `github.com/shwepyithit568-commits/DataPOS.git` · **⚠️ local = origin/main (ahead 2 — 08-20 purchasing commits not yet pushed)** |

---

## ၁.၂ ရှိပြီးသား (Built — Verified) ✅

### Ecommerce foundation (data_ecommerce ကနေ ကူးလာတဲ့ အခြေခံ)
- Multi-store (`stores` + `store_user` pivot + `StoreContext`/`ResolveStoreContext` path isolation)
- Catalog — products (SKU store-unique, variant, sale schedule, warranty...), categories (parent_id nested), brands, variant presets
- Storefront — home, products, detail, glass-finder, order-builder, blog, favorites, reviews, search
- Orders (`orders` + `order_items` — online Viber/Telegram flow) · PWA + Web Push · payments/delivery settings
- Admin panel အပြည့် — products, orders, brands, categories, blog, reviews, settings, push, backups, import history
- Product/Brand/Category CSV-XLSX import/export (`ProductImportService`)

### POS Phase 1 — Inventory foundation (changelog items 257–260)
- **Shared inventory ledger** — `inventory_movements` + `inventory_balances` (immutable; `inventory:reconcile` rebuild)
- **Branches & warehouses** — default location auto-creation, ledger FK constraints
- **Ecommerce InventoryAdapter** — `orders` → ledger movements, oversell prevention
- **Weighted-average CostingService** — receiving/returns/adjustments/serial

### POS Phase 2 — Online POS MVP (changelog items 261–270)
- Cashier shifts + opening cash (`/pos`) · cart + barcode search + atomic sale posting
- Receipt view + reprint (audit trail) · customer credit/debt (receivables, SoT §17)
- Sale return/refund (state machine) · daily closing (branch) · minimal reports (sales/cash/stock)
- Stock receiving (goods receipt → ledger, GRV numbers) · opening stock (manager review, OSR numbers)
- Inventory adjustments (manager approval, ADJ numbers) · idempotent `client_transaction_id` အကုန်မှာ

### POS Phase 2.5 part 1 — Pilot data-import hub (changelog item 271)
- `/admin/pilot-import` — products / customers / suppliers CSV-XLSX → **dry-run preview** → confirm → ImportHistory + error reports
- `suppliers` master table · `CustomerImportService` (phone-normalized duplicates, cross-store attach) · `SupplierImportService`

### ဒီနေ့ (2026-08-13) ပြင်ထားတာ — `CHANGELOG.md`
- Product import 500 bug (variants JSON မှာ `stock_status` မပါရင် crash → fallback fix, `ProductImportService.php`)
- "Ks 0" ဈေးပြသမှု (variant price 0 → product price fallback — product-card ×2 + catalog/show)

### ဒီနေ့ (2026-08-17) ပြင်ထားတာ — `CHANGELOG.md`
- **Cashier Home UI အပြည့်:** desktop 2-pane (grid + cart) / mobile 1-tab + floating cart → bottom-sheet drawer (swipe/backdrop/Escape close) · single-row scroll rows (module links / category / brand chips + toolbar) · `x-pos.chip-scroll` component
- **POS 500 fixes:** `resume`/`void`/`post` — `$store_slug` positional binding (Laravel 11/12 position-based resolve) + HTTP regression tests + `StoreScopedRouteSignatureTest` reflection guard (route အကုန် audit clean)
- **HTML caching:** private → `no-store` · public storefront → ETag + `max-age=60` + 304 · build assets → immutable (project `server.php` + `.htaccess`) · SW v5 network-first navigations
- Admin dashboard POS quick-action bar (shift status + jump to sale) · Store Settings quick-action render fix · `AdminDashboardTest` time-of-day fix

### POS cashier session (2026-08-17) — 13 commits (selling workflow အပြည့်)

- **Register-lock UX** — register ကို တစ်ခြားသူရဲ့ shift က ယူထားရင် "shift မဖွင့်ရသေးဘူး" အစား **occupied state** + shift-open rejection error ပြ · occupied register banner မှာ shift အသေးစိတ် (opening cash, cash sales) ပြ
- **Held sales (hold/recall)** — live recall fix · **age badge** (held since HH:mm) + **auto-expiry** (default 24h → **per-store setting** `pos_hold_expiry_hours`) · auto-expired ရင် cashier ကို **one-time notice** ပြ · POS home မှာ **expiry stats** (count / oldest / soon-to-expire)
- **Shared customer model** — ecommerce + POS အတူတူ (multi-store) — register enrollment + POS quick-add + phone/name dedup · POS quick-add မှာ **retail/wholesale customer type** ရွေးလို့ရ
- **Tiered pricing** — ဖောက်သည်တွဲရင် retail/wholesale tier ဈေး သက်ရောက် · **logged-in storefront shopper → POS cart** မှာ tier ဆက်ထိန်း · grid / cart lines / payment screen မှာ retail-vs-wholesale **discount visibility** (`retail_unit_price` / `line_retail_total` / `retail_subtotal`)
- **Per-line price override** — ✏️ editor · override က tier ဈေးကို အနိုင်ရ · hold/resume မှာ မပျက် · receipt မှာ original ဈေး အစင်း (`original_unit_price`)
- **Manager PIN approval** — per-store threshold (%) ထက် ပိုလျှော့တဲ့ override ကို manager/owner PIN နဲ့သာ (`users.pos_pin`, hashed) — approver က `pos_sale_items.approved_by` အထိ audit
- **Mouse drag-to-scroll** — horizontal chip rows (module/category/brand) ကို desktop မှာ မောက်နဲ့ ဆွဲလို့ရ (chip click တွေ မကျိုး)

### Purchasing batch (2026-08-20) — commits `369dbf8` + `7312b54`

- **Suppliers full CRUD** (`/admin/suppliers`) + CSV/Excel import/export (dry-run preview, duplicates, template).
- **Supplier payables** (`/pos/purchases/payables`) — FIFO general payments + per-PO payments; **aging report** (30/60/90+ day buckets); dashboard overdue alerts + quick-pay.
- **Purchase returns** — reverse a received PO, adjust stock + supplier credit.
- **Warehouses CRUD** (`/admin/warehouses`) — branch assignment, default-warehouse delete guard.
- **Stock transfers** (`/pos/transfers`) — multi-item create → ship → receive via `InventoryService` ledger movements.
- **Buy back** (`/pos/buy-back`) — customer returns posting stock restoration through the ledger.
- Replaced all remaining "Coming Soon" placeholders in the Purchasing & Transfers sidebar group with real links.
- ⚠️ Review follow-ups on this batch: warehouse routes missing `EnsureStoreAccess`; `SupplierController.php` not strict UTF-8 — see README Open issues.

### Phase 2.5 cutover — reconciliation + debt import (2026-08-17 pm, commits `8c504ba` + `7b354c2`)

- **Opening-stock reconciliation** — `/store/{slug}/pos/reconciliation`: imported (approved OSR) vs ledger opening position per product → diff report (summary cards + "ကွာခြားချက်များသာ" filter, staff view / manager approve) → manager approves → `adjustment_in/out` corrections converge diff → 0 · `REC-YYYYMMDD-####` + per-line snapshot (`inventory_reconciliation_items`) + audit · insufficient-stock rollback
- **Debt opening balances** — pilot import hub **Debt tab** (`/admin/pilot-import/debt`): CSV/XLSX `phone, amount[, notes]` → dry-run preview (customer + current balance per row) → confirm posts **immutable `opening_balance` ledger entries** (SoT §17, source `manual`) · idempotent (client_transaction_id) + cross-store guard + audit `debt_opening_imported` + ImportHistory + error reports · no migration needed
- **Live verified (preview)** — reconciliation converged diff = 0 (`REC-20260817-0001`, Mg Hla approve) · debt import Ks 350,000 posted (Ma Su 150,000 + Daw Phyu 200,000) → collection 50,000 → receivables **350,000 → 300,000** ✓

---

## ၁.၃ မရှိသေးတဲ့အရာများ (ဆက်ဆောက်ရမယ့်ဟာ) ❌

| အပိုင်း | ဘယ် Phase | မှတ်ချက် |
|---|---|---|
| ~~Pilot data import — opening-stock reconciliation, debt opening balances~~ | Phase 2.5 | ✅ **2026-08-17 အကုန်ပြီး** — import hub part 1 (item 271) + reconciliation (`8c504ba`) + Debt tab (`7b354c2`) — §1.2 |
| AppSheet/Google Sheets parallel validation + real cashier usage + stabilization | Phase 2.5 | pilot ကာလ အလုပ် |
| Backup & restore test (versioned workflow) | Phase 2.5 | runbook (`docs/ops/pilot-recovery-cutover-runbook.md`) — Drill #1 (SQLite) ✅ · §2.5A local MySQL ✅ · Drill #2 localhost rehearsal ✅ (2026-08-13, runbook §2.6) · **production drill ကျန် — deploy ပြီးမှ** |
| `/pos` PWA offline queue — IndexedDB, idempotent sync API, device registration | Phase 3 | storefront SW နဲ့ မရော |
| ~~Supplier management + purchase returns + supplier payables + aging~~ | Phase 4 | ✅ **2026-08-20 ပြီး** (`369dbf8`) — suppliers CRUD/import-export · purchase returns · payables (FIFO + per-PO) · aging — §1.2 |
| ~~Warehouses + stock transfers + buy back~~ | Phase 4 | ✅ **2026-08-20 ပြီး** (`7312b54`) — warehouses CRUD · transfers (create→ship→receive) · buy back — §1.2 |
| Stock counts · Service jobs (phone repair) · Expenses + finance ledger · Advanced reports · Accounting period closing | Phase 4 | |
| Local LAN/SQLite edition + license + provisioning + resale docs | Phase 5 | |
| Industry packs (pharmacy/gold/grocery/restaurant...) | Phase 6 | demand ရှိမှသာ |
| UOM / barcode (piece + decimal qty + HID scan) | Phase 2 (မပါတော့) / ops | barcode search ရှိပြီး (item 262) — UOM foundation မရှိသေး |
| Serial/IMEI + warranty tracking | Mobile MVP core | မရှိသေး |
| Final layout UI polish | resale/pilot မတိုင်ခင် | **Owner decision 08-11: defer** |

---

## ၁.၄ Open Decisions — Owner Input Required (SoT §38)

Implementation မှာ မခန့်မှန်းရတဲ့အရာတွေ — **Owner (ဆရာကြီး) ဆုံးဖြတ်ပေးရမယ်:**
Negative stock exception policy · Return/exchange time limits · Item condition rules · Service warranty rules ·
Customer credit limits · Debt approval rules · Supplier payable workflow · Historical migration depth ·
Official cutover date · Daily closing / discrepancy thresholds · Offline retention · Receipt printer model /
paper width / connection · Cash drawer · Barcode/label scanner models · Tax usage · Final receipt layout ·
Final Burmese/English/Chinese terminology

(Resolved ပြီးသား: Money/rounding policy 08-10 — MMK integer, receipt round final step တစ်ခါတည်း · Hosting = Hostinger 48mo)

---

## ၁.၅ နောက်ဆက်လုပ်ရမှာများ (Next Steps — အစီအစဉ်)

1. ~~**Git sync**~~ — ✅ ပြီးပြီ (2026-08-13): docs consolidation `19222c1` + fix ၂ ခု `8fe8228` / `adb155a` (CashierShiftController အပါအဝင်) — origin/main နဲ့ in sync
2. ~~**POS cashier session (2026-08-17)**~~ — ✅ ပြီးပြီ: 13 commits (register-lock UX → held-sale expiry → shared customer model → tiered pricing → price override + manager PIN → drag-to-scroll) — §1.2 "POS cashier session" + CHANGELOG.md
3. **Phase 2.5 ကျန်** — ~~opening-stock reconciliation~~ ✅ (`8c504ba`) → ~~debt opening balances~~ ✅ (`7b354c2`) →
   backup/restore **production drill** (deploy ပြီးမှ) → real cashier usage (ဆိုင်မှာ တကယ်သုံး) →
   parallel validation → stabilization (ဒီဖိုင် Phase 2.5 exit criteria)
4. **Phase 3 — Cloud PWA Offline Queue** — `/pos/sw.js` + IndexedDB + idempotent sync API + device registration
5. **Phase 4 — Operations Modules** (purchasing, transfers, service, expenses, reports, period closing)
6. **Phase 5 — Local LAN/SQLite Edition + Resale Readiness**
7. Owner open decisions တွေ အချိန်တန်ရင် ဖြေရှင်း

---

## ၁.၆ အရေးကြီး မှတ်ချက်များ (Revision 3)

1. **`products.stock_status` က derived cache** — ledger က source of truth (changelog item 257, `config/inventory.php` `sync_stock_status_cache` default true) — manual field အနေနဲ့ မမှတ်ရ
2. **Ledger/sales/shifts/closings immutable** — မှားရင် reversal/correction document နဲ့သာ ပြင်ရမယ် (SoT §15.1)
3. **Service worker scope** — POS အတွက် `/pos/sw.js` သီးခြား — storefront SW (web push) မထိရ (SoT §4.3)
4. **ဖိုင်နာမည်တွေ** — `CHANGELOG.md` က history (items 1–271) · အသစ်တွေ → `CHANGELOG.md`
5. **GitHub က local နဲ့ in sync (2026-08-17)** — 08-17 commit 17 ခု အကုန် origin/main ကို push ပြီး (cashier session + Phase 2.5 cutover features) · **⚠️ 08-20 purchasing commits (`369dbf8`, `7312b54`) က main ပေါ်မှာရှိပြီး origin/main ထက် ahead 2 — push မလုပ်ရသေးပါ** · working tree clean · local HEAD = `7312b54`
6. **Port** — ဒီ project = **8501** (အရင် docs ရဲ့ 8500/8577 နဲ့ မရော)
7. **Deploy-datapos.sh က DataPOS live ကို မသွားရသေးဘူး** — pilot/resale အဆင့် ရောက်မှ deploy script အသစ် ရေးရမယ်


---

## တည်ဆောက်ရမယ့် အဆင့်ဆင့် (Implementation Phases)

## ၄။ တည်ဆောက်ရမယ့် အဆင့်ဆင့် (Implementation Phases)

> **ဒီဖိုင်မှာ:** POS စနစ်ကို ဘယ်ကစပြီး ဘယ်လို အဆင့်ဆင့် ဆောက်မလဲ။
>
> **Revision 2 (2026-08-10):** Owner မှ အတည်ပြုထားသော phase structure အသစ် — MVP scope ပြင်ပြီး (debt + closing က Phase 2)၊ AlinnThit pilot phase အသစ် ထည့်ပြီး၊ offline system ၂ မျိုး ခွဲပြီး။
>
> **မူအရ:** အဆင့်တိုင်း ပြီးရင် စမ်းသပ်ပြီးမှ နောက်တစ်ဆင့်။ မလိုအပ်တဲ့အရာ ကြိုမဆောက်နဲ့။

---

## Implementation Order (အဓိက ဦးစားပေး)

1. **Online Cloud POS** (Phase 0 → 2)
2. **AlinnThit production pilot** (Phase 2.5) — resale မလုပ်ခင် မဖြစ်မနေ
3. **Cloud PWA offline queue** (Phase 3)
4. **Local LAN/SQLite edition** (Phase 5)
5. **Cloud-to-local sync** — proven customer demand ရှိမှသာ

> Cloud PWA offline sync နဲ့ Local-server mode ကို phase တစ်ခုထဲ **မရော**။

---

## Phase 0 — Architecture Decisions & Risk Removal

**ရည်ရွယ်ချက်:** ကုဒ်မရေးခင် ဆုံးဖြတ်ချက်တွေ အတည်ပြု + risk ရှင်းပြီး foundation ပြင်ဆင်

| အလုပ် | အသေးစိတ် | Status |
|---|---|---|
| Tenancy/deployment decision | Cloud SaaS vs Local install — 02-target-design §2.3 | ✅ Approved 2026-08-10 — SoT နှစ်ဖိုင်လုံးတွင် မှတ်ပြီး |
| Store/domain resolver ပြင် | `CHANGELOG.md` အတိုင်း — store ၂ ခု active ရင် home မပျက်အောင် | ✅ 2026-08-13 (`7ae71ef`) — primary-store resolver fix + admin store management UI |
| Shared Ecommerce/POS inventory source of truth | Ledger ဒီဇိုင်း + adapter + stock_status derived ပြောင်း | ✅ SoT ပြင်ပြီး + implementation ပြီး (item 257–260 — ledger + adapter) |
| Money & rounding policy | Integer MMK, precision, rounding order (02 §2.6) — acceptance test နဲ့ သေချာ | ✅ Approved 2026-08-10 — SoT Open Decision #15/#6 Resolved |
| Weighted-average valuation | CostingService design (02 §2.7) | ✅ Rule က SoT §14.4/§10.4 မှာ မှတ်ပြီး + CostingService ပြီး (item 257–260) |
| Negative-stock policy | Default block + future override rules (02 §2.8) | ⏳ Owner approve လို |
| Offline mode separation | Cloud queue vs Local — 02 §2.12 | ✅ ဒီစာရွက်စာတမ်းမှာ သတ်မှတ်ပြီး |
| Permission matrix | Store modules / branch capabilities / user roles / approvals — 4 levels | ⏳ စရန် |
| Data-quality audit | AppSheet/Google Sheets ဒေတာ စစ် | ⏳ စရန် |
| Architecture Decision Records (ADR) | ဆုံးဖြတ်ချက်တိုင်း ADR ရေး | ⏳ စရန် |
| Detailed acceptance tests | Phase 1–2 အတွက် acceptance criteria | ⏳ စရန် |

**ထွက်ကုန်:** Approved decisions + ADRs + Phase 1 task breakdown + acceptance tests

---

## Phase 1 — Minimum Shared Foundation

**ရည်ရွယ်ချက်:** POS ရော Ecommerce ရော မှီခိုရမယ့် အုတ်မြစ် — ဒါမရှိရင် ဘာမှ မဆောက်နဲ့

| အလုပ် | အသေးစိတ် |
|---|---|
| Default branch/warehouse | Store တိုင်းကို default branch + warehouse auto-create (02 §2.11) — ✅ **2026-08-11 done** (branches/warehouses tables + StoreLocationService::ensureDefaults + controller/CLI hooks + `inventory:ensure-locations` backfill + ledger FK + 10 tests — changelog item 258) |
| Store module middleware | Static routes + module/capability enforcement (02 §2.4) — route:cache compatible |
| Branch roles & policies | `user_branch_roles` + policies — Owner/Admin/Manager/Cashier/Read-only |
| Barcode/UOM foundation | Product UOM + barcode + decimal qty |
| Weighted-average costing | CostingService (02 §2.7) — ✅ **2026-08-11 done** (`App\POS\Services\CostingService` — receiving recalc, COGS carry, returns, serial specific cost, reversal replay — 12 tests — changelog item 260) |
| Customers & suppliers | Master data — debt အတွက် foundation |
| **Inventory movements & balances** | Ledger (02 §2.5) — immutable, idempotent, transactional — ✅ **2026-08-11 part 1 done** (migration + enum + models + InventoryService + `inventory:reconcile` + 19 tests — changelog item 257) |
| Opening stock | `opening_balance` movements — migration batch နဲ့ — movement type + service အသင့် (ledger part 1 ထဲ) |
| **Ecommerce inventory adapter** | `orders` → ledger integration — reserve/confirm/cancel — oversell မဖြစ်ရ — ✅ **2026-08-11 done** (`OrderInventoryAdapter` + `OrderAdminController@updateStatus` hook — reserve on confirm, commit on delivered, release on cancel, oversell block — 13 tests — changelog item 259) |
| Audit & approvals | `audit_logs`, `approvals` (SoT §15, §20) |
| Concurrency & idempotency tests | Parallel sale, duplicate posting, retry — အကုန် test |

**Exit criteria:** Ledger က POS + Ecommerce နှစ်ဖက်စလုံး ထိန်းနိုင် · cross-store leak 0 · route:cache + module middleware အလုပ်ဖြစ် · SQLite+MySQL green

---

## Phase 2 — Usable Online POS MVP

**ရည်ရွယ်ချက်:** ဆိုင်မှာ တကယ်သုံးလို့ရတဲ့ online POS — offline complexity မထည့်ခင် online integrity အရင် validate (SoT §28)

| အလုပ် | အသေးစိတ် |
|---|---|
| Cashier shifts + opening cash | ✅ item 261 — shift open/close, opening cash, cash in/out, daily summary (02 §2.10) |
| Barcode/HID scanner input | ✅ item 262 — /pos barcode/SKU/name search (HID scanner types into the search box) |
| Product & variant search | ✅ item 262 — live search w/ ledger balance |
| Cart | ✅ item 262 — add/merge/update/remove (retail pricing; wholesale နောက်) |
| Hold/resume sale | ✅ item 262 — session draft → held row → resume/void |
| Sale posting | ✅ item 262 — atomic: receipt @ posting, ledger movements, payments, drawer |
| Split payments | ✅ item 262 — payment modal: Cash / KPay / WavePay / CB Pay / MMQR, change calc (02 §2.8) |
| **Customer credit/debt** | ✅ item 264 — customer attach + `credit` payment → `customer_ledger_entries` (sale_debt/collection/reversal), balance = SUM, collect form (SoT §17) |
| Receipt & reprint | ✅ item 263 — printable receipt + reprint audit trail (SoT §8) |
| Audit trail (foundation) | ✅ item 263 — `audit_logs` table + AuditLog model (Phase 1 item, first consumer: reprints) |
| Sale return/refund/reversal | ✅ item 265 — `pos_returns` doc + `sales_return` ledger @ original cost + cash→drawer / credit→debt + partially_refunded/refunded (SoT §15.1, 02 §2.9) |
| Simple stock receiving | ✅ item 268 — `goods_receipts` doc (GRV-Ymd-####, idempotent) → `purchase_received` ledger @ unit cost → weighted-avg recalc (SoT §6) |
| Opening stock | ✅ item 269 — `opening_stock_requests` (OSR-Ymd-####): staff submits → manager approves → `opening_balance` ledger @ unit cost + avg set (SoT §6) |
| Inventory adjustment (manager approval) | ✅ item 270 — `inventory_adjustments` (ADJ-Ymd-####): cashier submits signed +/− with reason → manager approves → `adjustment_in/out` @ avg cost, avg unchanged (SoT §6) |
| ~~Audit trail~~ | ✅ အကုန် မှတ်တမ်း — items 261–270 |
| **Daily closing** | ✅ item 266 — `daily_closings` per store+date: expected (shift drawer math + e-method sales + credit info) vs counted, diff, explanation, **manager approval**, offline gate (SoT §18, 02 §2.10) |
| Minimal reports | ✅ item 267 — `/pos/reports/{sales,cash,stock}` read-only: sales + method totals (cashier/date filters), cash drawer per-shift + aggregates, stock qty × avg cost value (ledger cache) |


**Exit criteria:** အထက်ပါ MVP items အကုန် online မှာ အလုပ်ဖြစ် · atomic posting · oversell 0 · daily closing reconcile ရတယ် · POS tests green

> ✅ **2026-08-11 — Phase 2 (Online POS MVP) အကုန်ပြီးပြီ (items 261–270).** နောက်တစ်ဆင့်: Phase 2.5 AlinnThit pilot.

> **မှတ်ချက်:** Full purchasing / purchase returns / supplier payables / advanced accounting တွေက Operations (Phase 4) — MVP မဟုတ်ဘူး။

---

## Phase 2.5 — AlinnThit Production Pilot

**ရည်ရွယ်ချက်:** ပြင်ပဖောက်သည်ကို မရောင်းခင် ကိုယ့်ဆိုင်မှာ စမ်းပြီး workflow မှန်ကြောင်း အတည်ပြု

| အလုပ် | အသေးစိတ် |
|---|---|
| Clean product/customer/supplier data | ✅ item 271 — `/admin/pilot-import` hub (Products/Customers/Suppliers tabs): CSV/XLSX upload → **dry-run preview** (validation + duplicate detection: SKU / phone / phone-then-name) → confirm → ImportHistory + error reports · `suppliers` master table · templates |
| Opening-stock reconciliation | ✅ **2026-08-17** — `/pos/reconciliation`: imported (approved OSR) vs ledger opening position → diff report → manager approve → adjustment corrections converge diff = 0 · REC numbers + per-line snapshot + audit (§1.2, commit `8c504ba`) |
| Debt opening balances | ✅ **2026-08-17** — import hub **Debt tab**: CSV `phone, amount` → dry-run preview → confirm posts `opening_balance` ledger entries (SoT §17) · idempotent + audit + error reports (§1.2, commit `7b354c2`) |
| AppSheet/Google Sheets parallel validation | နှစ်စနစ် တွဲပြေး → ကိုက်ကြောင်း စစ် |
| Real cashier workflow | တကယ့် ဆိုင်မှာ သုံး |
| Returns/refunds + customer debt + daily closing | MVP features တွေ real usage |
| Backup & restore test | Versioned workflow စမ်း |
| Performance test + store-isolation test | Load + tenant leak မရှိကြောင်း |
| Several weeks of observed real usage | Stabilization period |
| Written recovery/cutover runbook | Rollback/failover လုပ်နည်း စာရွက် |

**Exit criteria:** Pilot workflow မတည်မငြိမ်ခင် **ပြင်ပဖောက်သည်ကို မရောင်းရ** · runbook အပြည့် · reconciliation diff = 0

---

## Phase 3 — Cloud PWA Offline Queue

**ရည်ရွယ်ချက်:** Internet မရှိတဲ့အခါလည်း cloud POS ဆက်အလုပ်လုပ်နိုင် (Model A အတွက်)

| အလုပ် | အသေးစိတ် |
|---|---|
| `/pos` PWA installable | သီးခြား service worker (`/pos/sw.js`) — storefront SW မထိ (SoT §4.3) |
| IndexedDB branch dataset | လိုတဲ့ဒေတာ သိမ်း (offline queue) |
| Offline transaction queue | Draft → ready → syncing → synced + error states (SoT §19.4) |
| Idempotent sync API | `client_transaction_id` unique — duplicate မဖြစ်ရ (SoT §19.2) |
| Device registration/revocation | Device handoff workflow (SoT §9.2) |
| Queue status & recovery | syncing/pending/failed/error — မမြင်ရအောင် မဝှက် + failed-queue recovery/export |
| Conflict strategy | Posted immutable → correction document (SoT §19.5) |

**Exit criteria:** Offline sale → reconnect → sync → balance မှန် · duplicate 0 · revoked device reject · Windows + Android field test pass

---

## Phase 4 — Operations Modules

**ရည်ရွယ်ချက်:** MVP အပြင် ကျန် module တွေ (SoT §13)

| Module | အချိန် |
|---|---|
| Full purchasing + purchase returns + supplier payables | Phase 4 |
| Stock transfers + stock counts | Phase 4 |
| Service jobs (ဖုန်းပြုပြင်ရေး) + service parts | Phase 4 |
| Expenses + finance ledger | Phase 4 |
| Advanced reports | Phase 4 |
| Finance/accounting period closing | Phase 4 |

---

## Phase 5 — Local LAN/SQLite Edition & Resale Readiness

**ရည်ရွယ်ချက်:** Offline-only ဖောက်သည်အတွက် Local install + ပြင်ပရောင်းချဖို့ ပြင်ဆင် — ကျယ်ရင် ၂ ပိုင်း ခွဲ:

### 5a. Local installation, backup, restore, update workflow
- SQLite single-tenant install (Model B — 02 §2.3)
- Browser devices → LAN/Wi-Fi
- **Versioned backup/restore** (02 §2.15): WAL checkpoint, snapshot, assets, manifest, checksums, integrity verify, restore dry-run, pre-restore backup, version compat validation
- Versioned update workflow (upgrade path)
- **ပထမ Local release တွင် central cloud sync မပါ**

### 5b. Provisioning, plans, licensing, support mode, monitoring, documentation
- **Offline license** — signed payload, public-key verify — **private signing key ကို install ထဲ မထည့်ရ**
- Tenant provisioning tooling (Cloud) + plan gating
- Store Support Mode (02 §2.13)
- Monitoring + error reporting + measurable upgrade triggers (02 §2.16)
- Resale documentation + training materials

**Exit criteria:** Local install ကို ဖောက်သည်အသစ်တစ်ယောက်ဆီ ပေးပြီး backup→restore→update အကုန် အလုပ်ဖြစ် · license verify/reject test pass

---

## Phase 6 — Customer-driven Industry Packs

**ရည်ရွယ်ချက်:** ဖောက်သည်အစစ် ပေါ်လာမှသာ ဆောက်မယ် — validated demand မရှိရင် မဆောက်

| Pack | ဘယ်အခါ |
|---|---|
| Pharmacy (expiry/batch) | ဆေးဆိုင် ဖောက်သည် ရလာရင် |
| Gold Shop (ကျပ်/ပဲ/ရွေး + daily rate + karat) | ရွှေဆိုင် ဖောက်သည် ရလာရင် |
| Grocery (scale + multi-unit) / Restaurant (KOT/tables) / Fuel / Fashion matrix | Demand ပေါ်မှ တစ်ခုချင်းစီ |

---

## Resale တိုးချဲ့မှု (ဘယ်အချိန် ဘာလုပ်မလဲ)

| ဘယ်အခါ | ဘာလုပ် |
|---|---|
| Phase 2.5 pilot မတည်မငြိမ်ခင် | **ပြင်ပဖောက်သည်ကို မရောင်းရ** |
| Pilot ပြီးမှ Cloud ဖောက်သည်သစ် | `store:create` + enabled_modules — Phase 1 မှာ ရှိပြီးသား |
| Offline-only ဖောက်သည်သစ် | Local LAN edition — Phase 5 ပြီးမှ |
| License စတင်ရောင်းချင်ရင် | Signed offline license (5b) — MVP မစမ်းရသေးခင် မလုပ် |
| Industry pack လိုလာရင် | Phase 6 — demand ရှိမှ |

---

## တည်ဆောက်စဉ် လိုက်နာရမယ့် အခြေခံစည်းမျဉ်း

1. **Store/tenant isolation** — store A ဒေတာ store B မှာ မပေါ်ရ (SoT §6)
2. **Server-side authorization** — static routes + middleware — UI hide တစ်ခုတည်း မလုံလောက်
3. **Inventory/finance ကို atomic + auditable** — partial မဖြစ်ရ (SoT §15.2, §19.3)
4. **Idempotency** — sync retry လုပ်ရင် duplicate မဖြစ်ရ (SoT §19.2)
5. **Money/quantity float မသုံး** — integer MMK + decimal precision (02 §2.6)
6. **Negative stock default block** (02 §2.8)
7. **SQLite + MySQL နှစ်မျိုးလုံး** — migration/test run ရမယ်
8. **Burmese/English label** — နှစ်ဘာသာ (lang ၃ ဖိုင်)
9. **Test ရေးရမယ်** — feature တိုင်းနဲ့
10. **SoT နဲ့ ဆန့်ကျင်ရင် ရပ်ပြီး မေး** — မခန့်မှန်းနဲ့ (02-target-design ရဲ့ SoT Conflicts ဇယား)

---

## ဆက်ဆွေးနွေးရန်

- ✅ SoT amendment ၅ ခု (02-target-design §SoT Conflicts) — **Approved 2026-08-10 + နှစ်ဖိုင်လုံးတွင် apply ပြီး**
- Phase 0 ရဲ့ ကျန် Owner decisions (negative stock override, hosting trigger thresholds) အတည်ပြုဖို့
- AlinnThit pilot ရဲ့ စတင်ရက် / data source

---

## Source 4: `archive/superseded-plans/windows_offline_installer_plan_v1.md`

**SHA-256:** `f11761a7c9517478cacc8fc2beece99f7e576be627ea44155a9fd04a6b272528`

# DataPOS — Windows Offline Installer Plan v1

**Document Reference:** `docs/windows_offline_installer_plan_v1.md`
**Parent Document:** myanmar_business_commercial_readiness_plan_v1.md (`../../plans/myanmar_business_commercial_readiness_plan_v1.md`; see consolidated index)
**Completion Report:** phase_f_completion_report.md (`../completed-phases/phase_f_completion_report.md`; see consolidated index)
**Created:** 2026-09-09
**Prepared By:** Tech Buddy (Senior Software Architect & Pair Programmer)
**Status:** 🟡 Awaiting Project Owner Approval Before Implementation

> **Rule (from master plan, Section 23):** ဤ plan ကို implement မစမီ Project Owner ၏ explicit approval ရရှိရမည်။
> Phase F completion report ပါ UAT sign-off မပြည့်မနေ installer build မစရ။

---

## 1. Goal

DataPOS application ကို Myanmar ဆိုင်ရှင်တစ်ဦး (programmer မဟုတ်သူ) ကိုယ်တိုင် — XAMPP၊ PHP၊ Composer၊ Node.js မည်သည့် developer tool မှ မတပ်ဆင်ဘဲ — Windows 10/11 (64-bit) PC တစ်လုံးပေါ်တွင် double-click တစ်ချက်ဖြင့် install ပြုလုပ်နိုင်ပြီး offline ဖြင့် အပြည့်အဝ အသုံးပြုနိုင်သော self-contained installer package တည်ဆောက်ရမည်။

### Success Criteria

- Installer file size ≤ 150 MB (download-friendly for Myanmar mobile data)
- Install time on low-spec PC ≤ 5 minutes
- Zero internet required after installation
- Zero command-line interaction required from user
- First-run store setup ≤ 10 minutes for non-technical user
- Uninstall via Windows "Add or Remove Programs" leaves no orphan files

---

## 2. Technology Decision

### 2.1 Installer Framework

**Selected: Inno Setup 6.x (free, open-source, ISL license)**

| Option | Cost | Myanmar PC Compatible | File Size | Complexity |
|:---|:---:|:---:|:---:|:---:|
| **Inno Setup 6** (selected) | Free | ✅ Win 10/11 | Small overhead | Low |
| NSIS | Free | ✅ | Small overhead | Medium |
| WiX Toolset | Free | ✅ | Medium overhead | High |
| Electron packager | Free | ⚠️ Chromium = +150MB | Very large | High |
| InstallShield | Paid | ✅ | Medium | Medium |

**Rationale:** Inno Setup ကို Myanmar IT community တွင် ကျယ်ကျယ်ပြန့်ပြန့် အသုံးပြုနေပြီး documentation ကောင်းကောင်းရှိသည်။ Pascal script ဖြင့် complex logic ရေးနိုင်ပြီး UPX compression support ရှိသည်။ File size overhead နည်းဆုံး။

### 2.2 Runtime Bundle Strategy

**Selected: PHP 8.2 NTS + PHP Built-in Server (zero Apache/Nginx)**

```
DataPOS-Installer/
├── php/              ← PHP 8.2.x NTS x64 Windows binary (embedded)
│   ├── php.exe
│   ├── php.ini       ← Pre-configured (SQLite, mbstring, fileinfo, openssl, zip, bcmath, gd)
│   └── ext/          ← Only required extensions
├── app/              ← Laravel application (pre-built assets)
│   ├── public/build/ ← Vite-built assets (CSS/JS/fonts — already compiled)
│   ├── storage/      ← Writable; gitignored contents seeded fresh
│   └── ...
├── DataPOS.exe       ← Launcher (wraps php.exe artisan serve)
├── DataPOS-Setup.bat ← First-run migration runner (hidden, called by installer)
└── Uninstall.exe     ← Inno Setup uninstaller
```

**Why PHP built-in server (not Apache/Nginx):**
- Zero port conflict complexity
- Zero Windows Service permission requirements (no admin needed to run)
- PHP 8.2 built-in server is production-stable for single-user local apps
- 20–30 concurrent requests support — sufficient for one POS terminal

### 2.3 Database

**SQLite 3 (already the project database — no change required)**

- SQLite WAL mode enabled for crash safety (already configured)
- Database file: `storage/database/datapos.sqlite`
- Backup ZIP: `storage/app/backups/`
- No server process; no firewall rules; no port required

### 2.4 Launcher Executable

**Selected: Simple compiled wrapper (AutoIt or Go single-binary)**

The launcher (`DataPOS.exe`) will:
1. Check if PHP process already running on port 8765
2. If not — start `php.exe -S 127.0.0.1:8765 -t public/ server.php` as hidden process
3. Wait up to 5 seconds for server readiness (HTTP probe)
4. Open default browser to `http://127.0.0.1:8765`
5. Show system tray icon with "Open DataPOS" and "Stop Server" menu

**Port:** `8765` (chosen to avoid conflict with XAMPP port 80/443 and common tools)

---

## 3. Bundled Component Manifest

| Component | Version | License | Included In Bundle |
|:---|:---|:---|:---:|
| PHP | 8.2.12 NTS x64 (Windows VC16) | PHP License 3.01 | ✅ |
| SQLite | 3.x (bundled with PHP) | Public Domain | ✅ |
| Laravel Application | Current main branch | Proprietary | ✅ |
| Vite Build Assets | Pre-compiled CSS/JS | MIT | ✅ |
| Noto Sans Myanmar Font | 2.x | SIL OFL 1.1 | ✅ |
| html2pdf.js | 0.10.x | MIT | ✅ (in public/js/) |
| PHPSpreadsheet | ^5.9 (via Composer — vendor/) | MIT | ✅ |
| Laravel Vendor | All composer deps | Mixed MIT/BSD | ✅ |
| Inno Setup Runtime | 6.x | ISL | ✅ (installer only) |

**Total estimated bundle size:** ~90–120 MB (before UPX compression)
**After UPX compression:** ~55–75 MB

---

## 4. Installer UX Flow

### 4.1 Wizard Pages

```
Page 1: Welcome Screen
  ├── DataPOS logo + version
  ├── "ကြိုဆိုပါသည် — DataPOS ကို Windows တွင် တပ်ဆင်ရန်"
  └── [Next] [Cancel]

Page 2: License Agreement
  ├── Proprietary license text (Myanmar + English)
  └── [I Accept] radio → [Next]

Page 3: Install Location
  ├── Default: C:\DataPOS\
  ├── [Browse...] button
  ├── Disk space check: required ~200MB, available: {X}MB
  └── [Next]

Page 4: Start Menu & Shortcuts
  ├── ☑ Create Desktop shortcut
  ├── ☑ Add to Start Menu
  ├── ☑ Start DataPOS automatically when Windows starts
  └── [Next]

Page 5: Installing...
  ├── Progress bar (file extraction)
  ├── "PHP runtime extracting..."
  ├── "Application files extracting..."
  ├── "Setting up database..."  ← runs php artisan migrate --seed silently
  ├── "Creating shortcuts..."
  └── [Finish]

Page 6: Installation Complete
  ├── ✅ "DataPOS Successfully installed!"
  ├── ☑ Launch DataPOS now
  └── [Finish]
```

### 4.2 Silent Install Support

```bash
DataPOS-Setup.exe /SILENT /DIR="C:\DataPOS"
DataPOS-Setup.exe /VERYSILENT /SUPPRESSMSGBOXES /DIR="D:\DataPOS"
```

> ဆိုင်ပေါင်းများ တပ်ဆင်ဖို့ IT reseller များ အတွက် batch deploy support။

---

## 5. First-Run Setup Wizard (In-App)

ပထမ login ပြီးနောက် — store data မရှိသေး (fresh install) ဆိုပါက application အတွင်းမှ first-run wizard ပေါ်လာရမည်:

```
Step 1: Store Information
  ├── Store Name (Myanmar)
  ├── Store Name (English)
  ├── Phone / Viber / Telegram
  ├── Address
  └── [Next]

Step 2: Currency & Localization
  ├── Currency: MMK (default) — configurable decimal places
  ├── Date Format: DD/MM/YYYY (default)
  ├── Language: မြန်မာ / English / 中文
  └── [Next]

Step 3: Document Numbering
  ├── Invoice prefix (default: INV)
  ├── Receipt prefix (default: RCT)
  ├── Starting number (default: 00001)
  ├── Reset annually? ☑ Yes
  └── [Next]

Step 4: Printer Setup
  ├── Default paper size: 58mm / 80mm / A4
  ├── [Test Print] button → downloads ESC/POS test byte stream
  └── [Next]

Step 5: Create Owner Account
  ├── Owner name
  ├── PIN (4-6 digits for POS)
  ├── Password (admin login)
  └── [Finish Setup]
```

---

## 6. Auto-Start Mechanism

**Selected: Windows Task Scheduler (no Windows Service required)**

```xml
<!-- Task: DataPOS_Autostart -->
<Triggers>
  <LogonTrigger>
    <Enabled>true</Enabled>
    <UserId>CURRENT_USER</UserId>
  </LogonTrigger>
</Triggers>
<Actions>
  <Exec>
    <Command>C:\DataPOS\DataPOS.exe</Command>
    <Arguments>--autostart</Arguments>
  </Exec>
</Actions>
<Settings>
  <MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy>
  <RunOnlyIfNetworkAvailable>false</RunOnlyIfNetworkAvailable>
</Settings>
```

**Why Task Scheduler (not registry Run key):**
- No admin elevation required
- Works with UAC-enabled systems
- Supports `--autostart` flag to suppress "already running" dialog
- Easily disabled by user via Task Scheduler UI

**Startup behavior with `--autostart` flag:**
- Start PHP server silently (no browser window)
- Show tray icon only
- User clicks tray icon to open browser

---

## 7. Automatic Daily Backup (L-4 Resolution)

> Resolves Known Limitation L-4 from Phase F Completion Report.

**Selected: Windows Task Scheduler + PHP CLI backup command**

```xml
<!-- Task: DataPOS_DailyBackup -->
<Triggers>
  <CalendarTrigger>
    <StartBoundary>2026-01-01T02:00:00</StartBoundary>
    <Repetition>
      <Interval>P1D</Interval>
    </Repetition>
  </CalendarTrigger>
</Triggers>
<Actions>
  <Exec>
    <Command>C:\DataPOS\php\php.exe</Command>
    <Arguments>C:\DataPOS\app\artisan datapos:backup --silent</Arguments>
  </Exec>
</Actions>
```

**Behavior:**
- Runs at 02:00 AM daily (configurable in-app settings)
- Creates `datapos_backup_YYYY-MM-DD_HH-mm.zip` with SHA-256 checksum
- Retains last 30 backups; deletes oldest (configurable retention)
- Writes success/failure to `storage/logs/backup.log`
- Optional: copy backup to USB drive if `D:\DataPOS_Backups\` exists (USB-aware)

---

## 8. Update Mechanism

**Strategy: Manual offline update package (appropriate for Myanmar offline market)**

### 8.1 Update Package Format

```
DataPOS-Update-v1.1.exe   ← Inno Setup updater
  ├── Stops running DataPOS.exe (graceful shutdown via HTTP /api/shutdown)
  ├── Backs up current installation to .zip with timestamp
  ├── Extracts new app/ files (preserves storage/ and .env)
  ├── Runs php artisan migrate --force
  ├── Restarts DataPOS.exe
  └── Shows changelog (Burmese + English)
```

### 8.2 Update Distribution

1. Update package uploaded to project GitHub Releases page
2. Reseller/IT partner downloads and brings on USB drive to site
3. Double-click updater on customer PC
4. ≤ 3 minutes with no internet

### 8.3 In-App Update Notice (Optional — Phase G)

```
GET http://127.0.0.1:8765/api/version
→ {"version": "1.0.0", "update_available": false}
```

If local file `C:\DataPOS\update\DataPOS-Update-*.exe` exists:
- Show yellow banner: "အပ်ဒိတ် ရရှိနိုင်ပါသည်"
- [Install Update] button runs the updater

> Internet-based auto-update ကို Phase G တွင် optional feature အဖြစ် ထည့်သွင်းနိုင်သည်။

---

## 9. Uninstaller

**Inno Setup built-in uninstaller (`Uninstall.exe`)**

```pascal
// Inno Setup [UninstallRun] section
[UninstallRun]
Filename: "{app}\php\php.exe"; Parameters: "{app}\app\artisan down"; Flags: runhidden
; Gracefully stops PHP server
```

**Uninstall behavior:**
1. Offer to keep or delete `storage/` (data, backups, uploads)
2. Remove Task Scheduler tasks (autostart + daily backup)
3. Remove shortcuts (Desktop + Start Menu)
4. Remove `C:\DataPOS\` (except user-chosen preserved `storage/`)
5. Remove from "Add or Remove Programs"
6. Leave no orphan registry keys

**Uninstall dialog (Myanmar + English):**
```
"DataPOS ဖြုတ်ချသောအခါ သင့်ဆိုင်ဒေတာများကို ဘာလုပ်မည်နည်း?"
  ⦿ ဒေတာများ ထိန်းသိမ်းထားမည် (Recommended)
  ○ ဒေတာများ အားလုံးဖျက်မည် (ဆိုင်မဖွင့်တော့ပါ)
```

---

## 10. Code Signing Requirements

> ⚠️ Windows SmartScreen filter တားဆီးမည်ကို ကာကွယ်ရန် code signing certificate လိုအပ်သည်။

| Option | Annual Cost | Suitable For |
|:---|:---:|:---|
| Standard OV Code Signing (Sectigo/DigiCert) | ~$200–$400 USD | Commercial distribution |
| EV Code Signing (Hardware Token) | ~$400–$700 USD | Instant SmartScreen trust |
| Self-signed (test only) | Free | Internal pilot only |
| No signing | Free | SmartScreen warning will appear |

**Recommendation for pilot phase:** Self-signed certificate ဖြင့် pilot ကို ဆိုင်ပေါင်း 3–5 ခု တွင် test လုပ်ပြီး commercial distribution မတိုင်မီ OV certificate ဝယ်ယူပါ။

**Workaround for pilot (documented for users):**
```
"More info" → "Run anyway" ကိုနှိပ်ပါ
ဒါကို Tech Buddy install လုပ်ပေးမည်ဖြစ်သဖြင့် ကိုယ်တိုင်လုပ်ရန် မလိုပါ
```

---

## 11. Windows Compatibility Matrix

| OS | Architecture | Status | Notes |
|:---|:---:|:---:|:---|
| Windows 11 (22H2+) | x64 | ✅ Primary target | Fully tested |
| Windows 10 (21H2+) | x64 | ✅ Primary target | Fully tested |
| Windows 10 (1909) | x64 | ⚠️ Best-effort | PHP 8.2 minimum OS: Win 10 1607 |
| Windows 10 | x86 (32-bit) | ❌ Not supported | PHP 8.2 x64 only |
| Windows 8.1 | x64 | ❌ Not supported | EOL; PHP 8.2 does not support |
| Windows 7 | x64 | ❌ Not supported | EOL |

**Minimum Hardware Requirements:**
```
CPU:     Intel/AMD 64-bit (dual-core recommended)
RAM:     2 GB minimum, 4 GB recommended
Storage: 500 MB free (installation) + 1 GB (data growth)
Display: 1280×720 minimum, 1366×768 recommended
Printer: USB thermal 58mm/80mm (optional)
```

---

## 12. Security Hardening for Installer

| Concern | Mitigation |
|:---|:---|
| PHP server accessible from LAN | Bind to `127.0.0.1` only (loopback) |
| Port 8765 conflict | Check + fallback to 8766, 8767 |
| Database file readable by all users | Set file ACL to current user only |
| `.env` contains APP_KEY | Generated fresh on install (not hardcoded) |
| Installer integrity | SHA-256 of installer published on download page |
| Backup files unencrypted | Document limitation; optional AES-256 encryption in v1.1 |
| LAN multi-user sharing | Not supported in v1 — documented limitation |

---

## 13. Known Installer Limitations (v1.0)

| # | Limitation | Plan |
|:---:|:---|:---|
| I-1 | Single-PC only (SQLite) — no LAN multi-station | Architecture review for v2 |
| I-2 | No hardware-locked license (L-7) | License module in Phase G |
| I-3 | No internet-based auto-update | Manual update package |
| I-4 | No code signing certificate on pilot | OV cert before commercial release |
| I-5 | 32-bit Windows not supported | Document clearly on download page |
| I-6 | Backup not encrypted at rest | AES-256 option in v1.1 |

---

## 14. Build Pipeline (Phase G Implementation Tasks)

### 14.1 Pre-Build Checklist

```
[ ] Composer install --no-dev --optimize-autoloader
[ ] npm run build  (Vite production assets)
[ ] php artisan config:cache
[ ] php artisan route:cache
[ ] php artisan view:cache
[ ] php artisan event:cache
[ ] Remove .git/, node_modules/, tests/, docs/ from bundle
[ ] Set APP_ENV=production, APP_DEBUG=false in bundled .env.example
[ ] Generate fresh APP_KEY during install (not pre-seeded)
[ ] Confirm SQLite WAL mode in database.php
[ ] Confirm all fonts in public/fonts/ (NotoSansMyanmar)
[ ] Confirm html2pdf.js in public/js/ (offline)
```

### 14.2 Installer Build Script (`build-installer.ps1`)

```powershell
# Step 1: Build assets
npm run build

# Step 2: Composer production install
composer install --no-dev --optimize-autoloader

# Step 3: Artisan caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 4: Copy to staging area
Copy-Item -Recurse . .\staging\app -Exclude @('.git','node_modules','tests','.env')

# Step 5: Download PHP 8.2 NTS x64 (if not cached)
# Source: https://windows.php.net/download/

# Step 6: Compile Inno Setup script
& "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" DataPOS.iss

# Step 7: Compute SHA-256 of installer
Get-FileHash .\Output\DataPOS-Setup-v1.0.exe -Algorithm SHA256
```

### 14.3 Inno Setup Script Outline (`DataPOS.iss`)

```pascal
[Setup]
AppName=DataPOS
AppVersion=1.0.0
AppPublisher=DataPOS Myanmar
DefaultDirName={autopf}\DataPOS
DefaultGroupName=DataPOS
OutputDir=Output
OutputBaseFilename=DataPOS-Setup-v1.0
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
MinVersion=10.0.17763  ; Windows 10 1809+

[Files]
Source: "staging\php\*"; DestDir: "{app}\php"; Flags: recursesubdirs
Source: "staging\app\*"; DestDir: "{app}\app"; Flags: recursesubdirs
Source: "DataPOS.exe"; DestDir: "{app}"

[Run]
; First-run database migration
Filename: "{app}\php\php.exe"; Parameters: """{app}\app\artisan"" migrate --force --seed"; \
  WorkingDir: "{app}\app"; Flags: runhidden waituntilterminated; \
  StatusMsg: "ဒေတာဘေ့စ် ပြင်ဆင်နေသည်..."

; Schedule autostart task
Filename: "schtasks.exe"; Parameters: "/Create /TN DataPOS_Autostart ..."; Flags: runhidden

; Schedule daily backup task
Filename: "schtasks.exe"; Parameters: "/Create /TN DataPOS_DailyBackup ..."; Flags: runhidden

[UninstallRun]
Filename: "schtasks.exe"; Parameters: "/Delete /TN DataPOS_Autostart /F"; Flags: runhidden
Filename: "schtasks.exe"; Parameters: "/Delete /TN DataPOS_DailyBackup /F"; Flags: runhidden
```

---

## 15. Testing Plan for Installer

### 15.1 Automated (CI)

```
[ ] Inno Setup compilation succeeds (exit code 0)
[ ] Installer SHA-256 matches published checksum
[ ] Silent install on clean Windows 10 VM (VirtualBox)
[ ] Silent install on clean Windows 11 VM
[ ] php artisan migrate runs without error after install
[ ] App accessible at http://127.0.0.1:8765 after launcher
[ ] Login, create sale, print receipt — smoke test
[ ] Uninstall leaves no orphan files
```

### 15.2 Manual (Real Hardware)

```
[ ] Low-spec PC (4GB RAM, HDD) — install time < 5 min
[ ] SmartScreen warning → "Run anyway" works
[ ] First-run wizard completes in < 10 min
[ ] 58mm thermal receipt prints from installed app
[ ] USB barcode scanner scans product
[ ] Backup ZIP downloaded and restored on second PC
[ ] Auto-start works after Windows restart
[ ] Daily backup creates file at 02:00 AM
[ ] Uninstall via "Add or Remove Programs" — clean removal
```

---

## 16. Phase G Deliverables

When Project Owner approves this plan, Phase G implementation will produce:

| # | Deliverable | Priority |
|:---:|:---|:---:|
| G-1 | `DataPOS.iss` — Inno Setup script | P0 |
| G-2 | `DataPOS.exe` — Launcher binary (AutoIt or Go) | P0 |
| G-3 | `build-installer.ps1` — Build automation script | P0 |
| G-4 | PHP 8.2 NTS x64 bundle + pre-configured `php.ini` | P0 |
| G-5 | `DataPOS-Setup-v1.0.exe` — Final installer | P0 |
| G-6 | `SHA256SUMS.txt` — Installer integrity file | P0 |
| G-7 | First-run setup wizard (in-app PHP/Blade) | P0 |
| G-8 | Windows Task Scheduler XML tasks (autostart + backup) | P0 |
| G-9 | `datapos:backup` Artisan command (auto-backup) | P1 |
| G-10 | In-app update notice (local file detection) | P1 |
| G-11 | Code signing (OV certificate) | P1 (before commercial) |
| G-12 | `DataPOS-Update-v1.1.exe` — Update package template | P2 |
| G-13 | License/activation module | P2 |
| G-14 | Installer testing report | P0 |

---

## 17. Open Questions for Project Owner

Before implementation begins, Project Owner ၏ ဆုံးဖြတ်ချက် လိုအပ်သည့် အချက်များ:

| # | Question | Default (if no answer) |
|:---:|:---|:---|
| Q-1 | Install directory ကို `C:\DataPOS` လိုချင်သလား၊ `C:\Program Files\DataPOS` လိုချင်သလား? | `C:\DataPOS` (admin-free, simpler) |
| Q-2 | Port `8765` မဟုတ်ဘဲ အခြား port သုံးမည်လား? | `8765` |
| Q-3 | Daily auto-backup time — 02:00 AM OK လား? | 02:00 AM |
| Q-4 | Backup retention — 30 days keep မည်လား? | 30 days |
| Q-5 | Installer language — Myanmar only / bilingual (MY+EN)? | Bilingual |
| Q-6 | Code signing certificate — ဝယ်ရန် budget ရှိပါသလား? | Self-signed for pilot |
| Q-7 | Pilot before wider distribution — ဆိုင်အရေအတွက်? | 3–5 ဆိုင် |
| Q-8 | Single-PC only v1.0 OK လား၊ LAN multi-station ချက်ချင်း လိုချင်ပါသလား? | Single-PC v1.0 |

---

## 18. Approval Checkpoint

> **ဤ `windows_offline_installer_plan_v1.md` ကို review ပြုလုပ်ပြီး Project Owner က approval မပေးမီ installer code မရေးရ၊ PHP bundle မစုစည်းရ၊ Inno Setup script မဖန်တီးရ။**

```
Approval Status:   [ ] Approved  [ ] Rejected  [ ] Revisions Required

Project Owner:     _______________________________

Date:              _______________________________

Signature:         _______________________________

Q-1 to Q-8 Answers / Notes:

  Q-1 Install Dir:    _______________________________
  Q-2 Port:           _______________________________
  Q-3 Backup Time:    _______________________________
  Q-4 Retention:      _______________________________
  Q-5 Language:       _______________________________
  Q-6 Code Signing:   _______________________________
  Q-7 Pilot Stores:   _______________________________
  Q-8 LAN Multi-PC:   _______________________________
```

---

*Prepared by Tech Buddy per `myanmar_business_commercial_readiness_plan_v1.md` Section 20 (Phase F → Phase G handoff).*
*Next document: `windows_offline_installer_phase_g_implementation.md` (after approval).*
