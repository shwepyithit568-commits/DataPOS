# DataPOS Final Implementation Plan — Granular Store Permissions, Role-Aware Navigation, Optional Modules & Sales Channels

> **Approval baseline:** GitHub `main` commit `c3981434b1bfb214a7a60f87c013b411ee2d56d0`  
> **Status:** Approval requested — no application code, migration, route, or production data may be changed before approval.  
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
