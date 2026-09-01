# DataPOS — Admin Pre‑Production Audit + Safe Fix Prompt

**Purpose:** Production မတင်မီ DataPOS Admin / Central Management ကို AI Coding Agent က code correctness, authorization, store isolation, exports, localization, UI/UX, responsive behavior, performance, accessibility, and production safety အားလုံး audit လုပ်ပြီး safe fixes လုပ်ရန်။

**Repository:** `shwepyithit568-commits/DataPOS`  
**Known stack baseline:** Laravel 12.x, PHP 8.2+, Blade, Alpine.js, Tailwind CSS 4, Vite; SQLite local/UAT, MySQL production.

This is a **production-readiness audit + repair task**, not a redesign exercise.

---

# ROLE

Act as:

- Senior Laravel Architect
- Senior Backend Engineer
- Senior Frontend Engineer
- UI/UX Specialist
- Multi-Tenant Security Reviewer
- Database / Data Integrity Reviewer
- Accessibility Reviewer
- Performance Reviewer
- QA Engineer

Be skeptical. Verify actual behavior. Fix safe defects. Do not mark incomplete foundations as complete features.

---

# 0. OWNER PRIORITY + PROJECT RULES

Before editing:

Read and obey the current versions of:

- `AGENTS.md`
- `README.md`
- `Source_of_Truth_MM.md`
- relevant POS source-of-truth if admin changes touch POS
- `CHANGELOG.md`
- test/QA notes
- `docs/ops/DEPLOYMENT.md`
- latest Admin UI/UX guide in the repository

### Decision priority

When docs conflict:
1. latest explicit owner instruction
2. Source of Truth
3. approved architecture docs
4. current agent instructions
5. changelog/testing docs
6. current code + tests
7. old assumptions

Do not silently choose an old document over newer owner requirements.

---

# 1. PRODUCTION SAFETY — DO NOT DEPLOY FROM THIS TASK

This repository is being audited **before** production approval.

Do not:
- deploy production
- push automatically
- force-push
- rewrite git history
- mutate live production data
- run destructive DB reset commands
- use UAT/demo seeders on production
- rotate keys/secrets casually

Forbidden against production:

- `php artisan migrate:fresh`
- `php artisan migrate:fresh --seed`
- UAT/demo seeding
- blind destructive rollback
- mass data cleanup without migration/recovery plan

Safe deliverable for this task:
- inspect
- audit
- implement safe local fixes
- add/update tests
- build
- re-test
- report `GO / GO WITH CONDITIONS / NO-GO`

---

# 2. CURRENT ADMIN ARCHITECTURE MAP

Before fixing, map the current implementation.

Inspect:

### Routes
- platform-owner `/admin/**`
- store-scoped `/store/{store_slug}/admin/**`
- `EnsureStoreAccess`
- `ResolveStoreContext`
- `SetLocale`
- capability middleware
- manager/staff role boundaries

### Controllers
Audit all current admin controllers, especially critical areas:

- Dashboard
- Store Management
- User / Staff / Roles
- Products
- Product Master Data
- Brands
- Categories
- Warehouses
- Suppliers
- Inventory / Stock Ledger / Stock Count / Valuation
- Orders
- Promotions
- Receivables / Debt
- Expenses
- Cash / Bank transactions
- Profit & Loss
- Sales analytics
- Repair / Service Jobs / Spare Parts / Warranty
- Backups
- Database tools
- Imports / Import history
- Settings
- Theme / Appearance / Theme Governance
- Banners
- Blog
- Reviews
- Wholesale
- Printers / Barcode / Voucher
- Alerts / Audit logs
- Sync tools

Locate actual current classes; do not rely only on this list.

### Views
Inspect:
- `resources/views/layouts/admin/app.blade.php`
- `resources/views/admin/**`
- `resources/views/components/admin/**`
- shared forms/buttons/modals/tables
- toolbar
- nav groups/sidebar
- toast/alerts
- settings sections
- master data
- products/brands/categories
- export/import UI

### Data layer
Inspect:
- models
- policies/middleware
- services
- `StoreContext`
- migrations/indexes
- export/import services
- money handling
- validation
- transactions
- audit logging

---

# 3. CRITICAL MULTI-STORE DATA ISOLATION

This is a release blocker.

The Store Manager of Store A must not view or mutate Store B data.

Audit at minimum:

- Staff/users
- Roles where store-scoped
- Products
- Categories
- Brands
- Warehouses
- Suppliers
- Purchases where relevant
- Inventory
- Stock counts
- Stock ledger
- Orders
- Customers
- Receivables
- Expenses
- Cash/bank
- Repairs/service
- Warranty/serial/IMEI
- Promotions
- Settings
- Banners
- Reviews
- Blog
- Wholesale
- Exports
- Imports
- Print endpoints
- AJAX/JSON endpoints
- dashboard metrics
- alert polling
- backup/database tooling access

Verify all of:
- index
- show
- create
- store
- edit
- update
- destroy
- bulk actions
- export
- import
- print
- JSON/AJAX
- direct route model binding

### Required cross-store attacks

Try:
- Store A manager + Store B ID in URL
- Store A manager + Store B record in POST/PUT/DELETE
- cross-store bulk IDs
- cross-store export filter
- cross-store print endpoint
- cross-store AJAX endpoint
- cross-store dashboard metric/cache leak
- manipulated `store_slug`
- role escalation
- staff management scope bypass

Protection must exist at server/query level.

Do not rely on:
- hidden menu
- disabled button
- frontend filtering

Add regression tests for each serious issue fixed.

Severity:
- Critical
- High
- Medium
- Low

---

# 4. PLATFORM OWNER VS STORE ADMIN

Verify platform-level `/admin/**` and store-level `/store/{store_slug}/admin/**` do not accidentally share authorization assumptions.

Check:

- Platform owner-only screens
- Store manager-only actions
- Staff read/write limitations
- support mode
- store management
- theme governance
- sensitive backup/database functions
- impersonation/support behavior if any
- route naming / redirects
- cached menu state

A store manager must not gain platform-owner privileges through direct URLs.

---

# 5. BUSINESS LOGIC + DATA INTEGRITY

Check real admin operations, not only page rendering.

## Products / Inventory

Verify:
- product CRUD
- brand/category relationship
- SKU uniqueness rules
- variants
- store scope
- ecommerce visibility
- stock status derivation
- imports
- bulk price tools
- ledger integration
- no direct unsafe stock mutation
- no negative/race-condition corruption

## Orders

Verify:
- status transitions
- store scope
- finance implications
- export
- notes
- alerts
- duplicate operations
- customer linkage

## Money

Follow existing approved project money rules.

Check:
- MMK precision
- float misuse
- totals
- discount/tax order
- immutable posted financial facts where required
- decimal consistency
- reporting totals

Do not invent accounting behavior.

## Receivables / Expenses / Cash Bank / P&L

Look for:
- broken sums
- missing transaction wrapping
- wrong date scope
- cross-store aggregation
- destructive edits without audit
- stale caches
- N+1 queries

If a fix changes finance/accounting architecture, flag separately rather than guessing.

---

# 6. POS-RELATED ADMIN HANDOFF

The Admin area supports POS operations, so verify admin-side configuration/data does not break POS.

At minimum check:
- product availability
- pricing
- staff/roles
- register/store settings
- inventory
- printers/vouchers
- tax/discount settings if present
- daily closing/report links
- stock count/ledger
- barcode

Do not rewrite the POS domain unless the defect is clearly inside this Admin task.

If POS sale correctness itself is questionable, report a separate production blocker and identify the relevant POS test/module.

---

# 7. EXCEL / CSV IMPORT-EXPORT AUDIT

Audit current exports/imports for:

- correct column headers
- correct data mapping
- UTF-8
- Myanmar Unicode
- English/Chinese where relevant
- comma/quote/newline escaping
- empty data
- large data
- date/time formatting
- Asia/Yangon timezone correctness
- currency/quantity formatting
- current UI filters reflected in exported data
- correct store scope
- no cross-store leakage
- stable filename
- safe error handling

### CSV/Excel security
Check:
- formula injection (`=`, `+`, `-`, `@` payloads)
- malformed CSV
- oversized imports
- duplicate rows
- transaction safety
- partial import rollback
- unsafe file MIME/extension handling

If Excel is not actually supported and only CSV exists, say so clearly. Do not claim Excel support from a CSV feature.

Run relevant existing import/export tests and add edge-case regression tests when needed.

---

# 8. LOCALIZATION AUDIT

Admin views should not contain avoidable hardcoded user-facing labels.

Audit:
- `lang/en/messages.php`
- `lang/my/messages.php`
- `lang/zh_CN/messages.php`
- Blade templates
- JS-generated labels
- validation/errors
- tables
- buttons
- sidebar
- settings
- modals
- toasts
- empty states

### Myanmar wording

Make Burmese:
- concise
- natural
- practical
- retail/business friendly
- action-oriented

Avoid literal machine translation.

Examples of direction:
- shorter button labels when context is obvious
- remove unnecessary formal filler
- keep terminology consistent

Keep useful acronyms:
- SKU
- IMEI
- SN
- PIN
- KPay
- COD

Preserve placeholders/interpolation.

No raw translation key should render to the user.

---

# 9. ADMIN UI/UX — CORE DIRECTION

Admin is an operational work tool, not a marketing landing page.

Use:
- compact layout
- clear tables
- predictable filters
- readable totals
- visible actions
- safe destructive actions
- low-end-device-friendly rendering

Avoid:
- oversized hero blocks
- decorative gradients everywhere
- cards inside cards
- huge blank space
- tiny gray text
- random button styles
- accidental action movement
- one-off CSS hacks

Use the current admin layout and shared components wherever possible.

---

# 10. PAGE-LEVEL SPACING — ~4PX

Owner standard:

Direct page-level sibling sections should generally be around **4px** apart.

Typical flow:

Header  
↓ ~4px  
Banner / Summary  
↓ ~4px  
Search / Toolbar  
↓ ~4px  
Filters  
↓ ~4px  
Table / Grid  
↓ ~4px  
Pagination / Actions

Rules:

- Prefer shared `gap-1` / `space-y-1` style where appropriate.
- Remove duplicate margins/paddings causing accidental 12–24px gaps.
- This does NOT mean every input/button/card internal padding becomes 4px.
- Preserve touch target and readability.
- Larger spacing is allowed only for a real semantic boundary.

Mobile page outer padding should remain compact, around `8px` where appropriate.

---

# 11. LIGHT MODE — HIGH-CONTRAST DAYLIGHT

Use/normalize semantic theme tokens.

Target:

- page background: `#f4f6f8`
- cards/tables/panels: `#ffffff`
- default borders: `#cbd5e1`
- stronger border: `#94a3b8`
- primary text/icon: `#0f172a`
- secondary: `#1e293b`
- muted/supporting: `#334155`

Audit:
- body
- sidebar
- top bar
- table
- toolbar
- inputs/selects
- cards
- forms
- modals
- dropdowns
- toast
- badges
- empty states
- loading states
- settings
- product/admin panels

Borders must be visible in bright environments.

---

# 12. DARK MODE — TRUE OLED

Target:

- major background/sidebar: `#000000`
- inner cards/panels: `#0a0f1d` or `#111827`
- borders: `#1e293b` / `#334155`
- primary text: `#f8fafc`
- secondary: `#e2e8f0`
- muted: `#cbd5e1`

Requirements:

- major canvas should not remain gray if OLED black is intended
- nested cards/forms/dropdowns/modals must not fall back to white
- no low-contrast text
- status colors remain understandable
- border/divider remains visible but subdued
- focus state remains obvious

Prefer centralized tokens/theme layer over scattered hardcoded values.

---

# 13. BUTTONS — LIGHTWEIGHT 3D / ELEVATED

Buttons must feel tactile but not heavy.

Semantic colors:
- Primary: violet/indigo
- Success: emerald/green
- Warning: amber
- Danger: rose/red
- Neutral: slate

Interaction:

- subtle lower edge/shadow
- hover: slight rise (`translateY(-1px)`)
- active: slight press (`translateY(1px)`)
- active shadow reduces
- clear focus
- clear disabled
- clear loading
- prevent duplicate submit where relevant
- icon-only button has aria-label

Transition target: roughly `120–200ms`.

Prefer:
- transform
- opacity
- bg/border/color
- small box-shadow

Avoid:
- huge glow
- large blur
- continuous animation
- heavy animation libraries
- overused gradients

---

# 14. SMOOTH INTERACTIONS / NO UNNECESSARY PAGE RELOAD

Use existing Blade + Alpine.js + approved shared JS.

Improve local interactions where safe:

- sidebar collapse
- nav groups
- tabs
- filter drawer
- view toggle
- dropdown
- modal
- theme switching
- settings preview
- card/table mode
- expandable sections
- search/filter UI
- small AJAX updates already supported

Rules:

- Do not add a heavy SPA framework.
- Keep server validation and authorization authoritative.
- Keep URL/back-forward behavior correct.
- Prevent duplicate submissions.
- Show loading/success/error feedback.
- Avoid flicker/layout jumps.
- Preserve scroll position when useful.
- respect `prefers-reduced-motion`.

A full reload is acceptable when security/architecture/file download/print/auth requires it.

---

# 15. HEADER / SIDEBAR / NAVIGATION

Audit:

- compact height
- store/module context
- active state
- role-based visibility
- collapsed sidebar behavior
- mobile sidebar
- drawer overlay
- keyboard navigation
- focus states
- long Myanmar labels
- icon consistency
- touch targets
- content not hidden behind fixed UI
- sidebar store switch/context correctness

Do not make major actions unexpectedly move between pages.

---

# 16. TABLE STANDARD

Operational records should favor table view.

Check:
- sticky header where useful
- dark hairline dividers
- row hover/focus
- right-aligned actions
- visible totals
- `tabular-nums` for money/qty/date/reference
- pagination
- empty state
- sorting/filtering
- horizontal scroll on mobile
- no whole-page horizontal overflow

Products/Brands/Categories should use consistent table visual language where applicable.

On small screens, table wrapper may scroll horizontally; the `body` should not.

---

# 17. CARD / MASTER DATA GRID

Where card view is useful:

Target responsive direction:
- Desktop: 5 columns where content supports it
- Tablet: 3 columns
- Mobile: 2 columns

Do not force dense business tables into cards.

Master Data:
- modern multi-column grid
- modern icons
- clear labels
- consistent sizes
- touch-friendly actions
- horizontal-scroll quick-action row only where genuinely useful
- Floating Action Button only for a clear primary action, not as decoration

Product item/card backgrounds should use the available parent width. Remove unnecessary outer margin/padding while preserving internal text/price padding.

---

# 18. FORMS

Audit:
- server-side validation
- old input preservation
- field-level errors
- required/optional labels
- logical grouping
- desktop multi-column layout where useful
- mobile single-column fallback
- sticky save/cancel only when useful
- input type/min/max/step
- autocomplete
- keyboard flow
- disabled/loading state
- destructive confirmation
- CSRF

Do not hide validation only in JavaScript.

---

# 19. CSP / FRONTEND SECURITY

Do not introduce:
- inline `onclick`
- inline `onchange`
- inline `onsubmit`

Prefer:
- Alpine bindings
- existing CSP helpers
- shared JS

Do not weaken CSP to accommodate a shortcut.

Audit scripts for:
- unsafe DOM HTML
- unsanitized dynamic markup
- duplicate listeners
- memory leak / polling leak
- accidental repeated AJAX
- missing teardown

---

# 20. RESPONSIVE AUDIT

Test at minimum:

### Mobile
- 320
- 375
- 390
- 430

### Tablet
- 768
- 820
- 1024

### Desktop
- 1280
- 1366
- 1440
- 1920

Check:
- sidebar
- header
- toolbar
- tables
- forms
- cards
- modals
- settings
- master data
- product admin
- filters
- action buttons
- Burmese labels

No unintended body horizontal scroll.

---

# 21. ACCESSIBILITY

Verify:

- semantic buttons/links
- visible keyboard focus
- input labels
- error associations
- aria labels for icon-only actions
- dialog focus handling
- escape close where appropriate
- readable contrast in both themes
- touch target size
- reduced motion
- table semantics
- status not conveyed by color alone

---

# 22. PERFORMANCE

Check:

- N+1 queries
- dashboard aggregation
- repeated counts
- pagination
- unbounded lists
- slow filters
- export memory usage
- large import memory usage
- unnecessary JS
- huge Blade shared layout cost
- excessive DOM
- polling frequency
- image size
- font CLS
- expensive shadows/animations

Keep low-end POS laptops and phones usable.

Do not trade correctness for a Lighthouse score.

---

# 23. SETTINGS / THEME / FOOTER

Audit Admin settings end-to-end:

- General
- Currency
- Appearance
- Theme
- Contact
- Delivery
- How-to-order
- Footer
- POS settings

Check:
- store scope
- permission
- validation
- save/update
- preview
- rollback/revisions if supported
- success/error feedback
- localization
- storefront rendering impact

Theme settings must not allow unsafe/invalid styling to break admin/storefront.

Footer translations should be concise.

---

# 24. BACKUP / DATABASE TOOLS

These are high-risk admin modules.

Verify:
- platform/store permission
- destructive confirmation
- no arbitrary file path access
- safe filename handling
- storage destination
- backup integrity
- restore protections
- environment restrictions
- no accidental production reset
- no credentials in downloads/logs

Do not perform a real destructive restore as part of this audit.

If a safe isolated restore test environment exists, document exactly what was tested.

---

# 25. TEST SUITE

Inspect current tests. Relevant existing test names may include equivalents of:

- `AdminProductionReadinessTest`
- `ProductionBlockerRemediationTest`
- `BusinessWorkflowAuditTest`
- `AdminDashboardTest`
- `AdminSidebarNavigationUXTest`
- `AdminProductivityEnhancementTest`
- `AdminMasterDataPageTest`
- `AdminStoreManagementTest`
- `AdminUserManagementTest`
- `AdminWarehouseAuthorizationTest`
- `StoreAuthorizationTest`
- `StoreContextResolverTest`
- `StoreScopedRouteSignatureTest`
- `AdminBrandTest`
- `AdminCategoryTest`
- `AdminProductDuplicateAndBulkPriceTest`
- `ProductImportTest`
- `MasterDataExportImportTest`
- `BrandCategoryImportExportTest`
- `AdminOrderFinanceAndExportTest`
- `AdminBannerAndSettingsFormTest`
- `StoreSettingsAndBrandingTest`
- `LocalizationTest`
- `LocalizationKeysParityTest`
- `FrontendAssetIntegrityTest`
- `MigrationSafetyTest`
- `MysqlMigrationSmokeTest`
- `HttpsConfigurationTest`
- `AdminBackupTest`

Locate the actual current equivalents.

Testing workflow:
1. targeted tests for the area being fixed
2. related module suite
3. store-isolation/security tests
4. localization tests
5. production-readiness tests
6. broader/full suite when shared layout/service changes
7. `npm run build` after Blade/Tailwind/JS changes

Never hide pre-existing failures.

---

# 26. LIGHTHOUSE / VISUAL QA

If browser tooling is available:

Run Mobile + Desktop checks for at least:
- Admin dashboard
- Settings
- Products/Master Data
- one dense table page

Report:
- Performance
- Accessibility
- Best Practices
- major layout/render issues

Test:
- Light Daylight
- OLED Dark
- Myanmar
- English
- Chinese where supported

Do not fabricate Lighthouse scores.

---

# 27. DEBUG LOGGING

Add debug logging only where it materially helps diagnose a real problem.

Rules:
- meaningful context
- function/action
- safe IDs/state
- exception context
- no password/token/secret
- no sensitive customer data
- no verbose debug in production
- respect environment log level

Remove temporary `dd`, `dump`, debug banners, console spam.

---

# 28. CODE CLEANUP

During touched areas only, remove:
- dead code
- duplicate imports
- unreachable branches
- obvious duplicate logic
- unsafe inline handler leftovers
- obsolete temporary UI code

Do not turn a focused production audit into a full rewrite.

---

# 29. SAFE FIX PRIORITY

Fix in this order:

1. Critical security / cross-store access
2. Data integrity / finance / inventory correctness
3. Authorization
4. Broken Admin workflow
5. Export/import correctness
6. Production deployment blockers
7. Responsive/mobile rendering
8. Accessibility
9. Localization
10. Light/OLED theme
11. Button consistency
12. Smooth interactions
13. Performance
14. Cosmetic polish

If a cosmetic fix conflicts with business usability, business usability wins.

---

# 30. FINAL REPORT

Return:

## A. Production Decision
- `GO`
- `GO WITH CONDITIONS`
- `NO-GO`

## B. Findings
Table:
- Severity
- Module
- Problem
- Root cause
- Impact
- Fix status
- Verification

## C. Security
Explicitly report:
- cross-store tests
- role escalation tests
- IDOR/BOLA
- CSP/CSRF
- sensitive admin tools

## D. Business/Data Integrity
- products/inventory
- orders
- money
- receivables/finance
- exports/imports

## E. Admin UI/UX
Explicitly report:
- page spacing ~4px
- mobile outer padding ~8px
- full-width layouts
- responsive table behavior
- card/grid 5/3/2 where relevant
- Light Daylight standard
- True OLED Dark standard
- lightweight 3D buttons
- smooth transition/no unnecessary reload
- Myanmar concise localization
- accessibility

## F. Tests
List exact commands and:
- pass
- fail
- skipped
- pre-existing failures

## G. Build
Report `npm run build` result if frontend changed.

## H. Files Changed
Explain every changed file.

## I. Database/Migration Impact
State explicitly:
- none
or
- migration required + why + rollback/data risk

## J. Remaining Blockers
Do not bury them.

## K. Deployment Recommendation
State what must happen before production deploy.

---

# DEFINITION OF DONE

Never say `Done`, `Fixed`, or `Production Ready` based only on code inspection.

A production-ready claim requires evidence that:

- targeted code was inspected
- authorization was tested
- cross-store access was tested
- important business flow was tested
- UI was rendered at phone/tablet/desktop sizes
- Light and OLED Dark were checked
- Myanmar layout was checked
- relevant tests passed
- frontend build passed when changed
- no destructive production operation was performed
- remaining risks are explicitly listed

**Smallest correct + secure + maintainable + verified change wins.**
