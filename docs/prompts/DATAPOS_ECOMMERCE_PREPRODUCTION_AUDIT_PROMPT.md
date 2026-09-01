# DataPOS — E‑Commerce Storefront Pre‑Production Audit + Safe Fix Prompt

**Purpose:** Production မတင်မီ DataPOS E‑Commerce / Storefront ကို AI Coding Agent တစ်ယောက်က current working tree အတိုင်း end-to-end စစ်ဆေး၊ safe fixes လုပ်၊ regression tests run လုပ်ပြီး **GO / NO-GO** report ထုတ်ရန်။

**Repository:** `shwepyithit568-commits/DataPOS`  
**Known stack baseline:** Laravel 12.x, PHP 8.2+, Blade, Alpine.js, Tailwind CSS 4, Vite; SQLite local/UAT, MySQL production.  
**Important:** GitHub/main or old docs ကို blindly ယုံမထားပါနှင့်။ **Current local working tree + latest owner instructions + Source of Truth + tests** ကို အရင်စစ်ပါ။

---

## ROLE

Act as a:

- Senior Laravel Engineer
- Senior E‑Commerce Engineer
- Frontend/UI/UX Specialist
- Security Reviewer
- Accessibility Reviewer
- Performance Reviewer
- QA Engineer

Your job is **not** to praise the current code. Your job is to find real defects, fix safe defects, prove the fixes with tests/manual verification, and clearly state what is still not production-ready.

---

# 0. NON-NEGOTIABLE RULES

Before changing code:

1. Read:
   - `AGENTS.md`
   - `Source_of_Truth_MM.md`
   - `README.md`
   - relevant `CHANGELOG.md`
   - relevant testing/QA docs
   - `docs/ops/DEPLOYMENT.md`
   - latest UI/UX guide present in the repository
2. Inspect:
   - routes
   - middleware
   - controllers
   - models/services
   - Blade views/components/layouts
   - migrations
   - translations
   - tests
3. Compare documentation against **actual current code**.
4. Existing approved business behavior must not be changed only to make the UI prettier.
5. Reuse existing Blade + Alpine.js + Tailwind patterns.
6. **Do not introduce Livewire or jQuery.**
7. Do not add a heavy SPA framework only to avoid reloads.
8. Do not expose, print, copy, or commit credentials/secrets.
9. Do not run destructive production commands.
10. Do not deploy to production, push, force-push, rewrite git history, or change production data unless the owner explicitly gives a separate deployment instruction.

### Forbidden production-risk commands for this task

Do NOT run against real production data:

- `php artisan migrate:fresh`
- `php artisan migrate:fresh --seed`
- UAT/demo seeders
- destructive DB resets
- mass deletes
- blind migration rollback
- history rewrite

If a schema/business-rule change is necessary and may affect stock, finance, debt, audit history, store isolation, or historical production data, **report it separately instead of guessing**.

---

# 1. FIRST — CREATE AN AUDIT MAP

Before editing, map the current Storefront implementation.

At minimum inspect:

### Routes / middleware
- `routes/web.php`
- `ResolveStoreContext`
- `SetLocale`
- store capability middleware
- auth / guest routes
- rate limiters
- public-page cache middleware

### Storefront controllers
Inspect current equivalents of:

- `Storefront/HomeController`
- `Storefront/CatalogController`
- `Storefront/BrowseController`
- `Storefront/ReviewController`
- `Storefront/BlogController`
- `Storefront/ServiceTrackingController`
- customer account controllers
- order/order-builder controllers
- wholesale flow
- auth/login/register
- locale switching

### Storefront views/components
At minimum inspect:

- `resources/views/layouts/storefront/app.blade.php`
- `resources/views/storefront/**`
- `resources/views/customer/account/**`
- `resources/views/components/product-card.blade.php`
- list-view product card component
- language switcher
- header/navigation/search
- mobile drawer / bottom navigation
- footer
- banners
- product detail
- browse/categories
- order builder / confirmation
- account/favorites
- blog/review UI
- service tracking
- wholesale UI

Do not assume file names if the current tree changed. Locate the actual implementation first.

---

# 2. E‑COMMERCE FUNCTIONAL FLOW AUDIT

Act like a real shopper and verify the complete customer journey.

Check:

1. Storefront home
2. Header / navigation
3. Search
4. Search suggestions
5. Category browsing
6. Brand/category filtering
7. Product catalog
8. Grid/list view switching
9. Product detail
10. Product images/fallbacks
11. Sale/old-price/discount rendering
12. Stock availability presentation
13. Favorites
14. Reviews
15. Login
16. Registration
17. Customer account
18. Customer order history
19. Order builder / online order request
20. Order submission
21. Confirmation page
22. How-to-order/contact
23. Delivery/payment information
24. Blog
25. Wholesale flow
26. Service tracking
27. Language switching
28. Light/Dark theme
29. Footer/social/contact links
30. Browser back/forward behavior

Find and fix:

- dead links
- wrong store slug/context
- broken query-string filters
- state lost unexpectedly
- duplicate requests
- duplicate order submissions
- broken validation
- wrong success/error messages
- empty state bugs
- stale state
- cache-related wrong content
- rendering glitches
- body-level horizontal overflow
- broken mobile drawer
- inaccessible modal/menu/search
- unexpected full reloads for simple local UI state

Do not claim a flow works unless you actually traced or tested it.

---

# 3. MULTI-STORE / TENANT ISOLATION — CRITICAL

Store isolation is a production blocker.

Verify that Store A can never receive Store B data through:

- home page
- catalog
- product detail
- browse/categories
- search suggestions
- account
- favorites
- orders
- order confirmation
- reviews
- blog
- banners
- payment/delivery settings
- footer/contact details
- service tracking
- wholesale data
- cached public pages
- API/AJAX endpoints

Check especially:

- route `store_slug`
- `StoreContext`
- controller queries
- route model binding
- cached results/cache keys
- direct IDs/slugs
- order confirmation URLs
- account order URLs
- favorites
- suggestions endpoint
- review submission
- public cache separation per store

### Required attack-style tests

Attempt equivalent cases:

- Store A URL + Store B product/order ID
- Store A session + Store B order/account record
- manipulated `store_slug`
- direct URL without expected store context
- cached response after switching store
- query-string/header based context switching
- cross-store product slug collision

Server-side protection is required. Hiding records in Blade is not security.

Severity:
- Critical
- High
- Medium
- Low

Add or strengthen automated tests for any isolation issue fixed.

---

# 4. INVENTORY / E‑COMMERCE DATA CORRECTNESS

Do not create a competing stock source.

Verify that storefront stock/availability and online-order effects follow the project's approved inventory/ledger architecture.

Check for:

- stale `stock_status`
- direct/manual stock mutation from storefront code
- oversell possibility
- race conditions
- duplicate reservation/confirmation/cancel effects
- invalid negative availability
- online order lifecycle inconsistency
- ecommerce/POS shared-stock mismatch
- incorrect sale price / old price / promotion window logic

If fixing this touches inventory ledger architecture, money rules, accounting, or historical data, stop broad refactoring and report:
- root cause
- affected files
- proposed safe fix
- migration/data impact
- rollback considerations

Do not invent a new stock architecture.

---

# 5. SECURITY AUDIT

Check Storefront for:

### Authentication / authorization
- session security
- account ownership
- order ownership
- route access
- quick-login/dev-only exposure

### CSRF
All state-changing web forms must have valid CSRF handling.

### Rate limiting / abuse
Verify appropriate protection for:
- login
- registration
- order submission
- reviews
- search suggestions if needed
- favorites/actions
- public lookup endpoints

### XSS / output safety
Audit:
- product names/descriptions
- blog content
- review content
- banners
- settings/contact/footer
- dynamic HTML
- user-entered names/messages

Do not use unsafe rendering unless sanitized intentionally.

### CSP
- Avoid new inline event handler attributes.
- Reuse Alpine.js or project CSP helpers.
- Do not weaken CSP just to make new UI code work.

### Other checks
- IDOR/BOLA
- open redirect
- file upload validation
- MIME/extension checks
- path traversal
- unsafe external embeds
- unsafe URL rendering
- debug data leakage
- stack trace leakage
- secret leakage
- sensitive fields in logs

---

# 6. STOREFRONT UI/UX STANDARD

Audit actual rendered pages, not only class names.

## 6.1 Responsive targets

Verify at minimum:

### Mobile
- 320
- 375
- 390
- 430 px

### Tablet
- 768
- 820
- 1024 px

### Desktop
- 1280
- 1366
- 1440
- 1920 px

No body-level horizontal scrollbar on normal pages.

### Product grid target

Where the catalog/card grid supports it:

- Desktop: 5 columns
- Tablet: 3 columns
- Mobile: 2 columns

Use responsive behavior, not fragile fixed widths.

---

## 6.2 Mobile full-width / full-bleed direction

On mobile:

- use available screen width efficiently
- target outer horizontal page padding around `8px` where appropriate
- remove unnecessary nested margins
- do not make text touch the screen edge
- product cards should use the available parent width
- keep internal text/price/action padding readable

Do not force full-bleed on components where it harms usability.

---

## 6.3 Section spacing

Owner UI direction:

- Direct page-level sibling sections should generally be about `4px` apart.
- Avoid stacked parent gap + child margin + wrapper padding creating accidental 12–24px empty zones.
- Do **not** force every internal control spacing to 4px.
- Preserve readable product-card content, touch targets, form fields, and line-height.

Examples to inspect:
- header → banner
- banner → search
- search → filter
- filter → product grid
- section heading → content
- product sections → following sections
- footer transition

---

# 7. LIGHT + TRUE OLED DARK MODE

Apply consistently to nested components, not only the `<body>`.

## Light Mode — High-Contrast Daylight

Target semantic colors:

- page background: `#f4f6f8`
- cards/panels/tables: `#ffffff`
- default border: `#cbd5e1`
- stronger border: `#94a3b8`
- primary text/icons: `#0f172a`
- secondary: `#1e293b`
- muted/supporting: `#334155`

Check:
- header
- search
- drawers
- cards
- filters
- forms
- product detail
- dropdowns
- account UI
- blog
- order UI
- footer transitions
- empty/loading/error states

## Dark Mode — True OLED

Target:

- main page / major outer surfaces: `#000000`
- inner cards/panels: `#0a0f1d` or `#111827`
- border/divider: `#1e293b` / `#334155`
- primary text: `#f8fafc`
- secondary: `#e2e8f0`
- muted: `#cbd5e1`

Requirements:

- no gray-looking major canvas where OLED black is expected
- no unintended white nested component
- no low-contrast gray-on-black text
- dropdown/input/modal/search/menu states must also support dark mode
- hover/focus/selected/disabled/error/success states must remain readable

Prefer shared semantic tokens / theme utilities over random raw colors scattered across files.

---

# 8. BUTTONS — LIGHTWEIGHT 3D / ELEVATED STYLE

Buttons should be colorful, modern, tactile, and lightweight.

Use semantic colors:
- Primary: existing violet/indigo direction
- Success: emerald/green
- Warning: amber
- Danger: rose/red
- Neutral: slate

Style direction:

- subtle vertical shadow/darker lower edge
- hover: slight lift, e.g. `translateY(-1px)`
- active: slight press, e.g. `translateY(1px)`
- reduce shadow on active
- clear focus ring
- clear disabled/loading state
- icon-only buttons need accessible labels

Prefer approximately `120–200ms` transitions.

Prefer:
- transform
- opacity
- background-color
- border-color
- color
- small box-shadow

Avoid:
- huge blur
- animated glow
- continuously running decoration
- heavy gradients everywhere
- new animation dependency

Keep low-end mobile devices responsive.

---

# 9. SMOOTH INTERACTIONS — NO UNNECESSARY RELOAD

Where existing Blade + Alpine architecture supports it safely, make local UI state changes immediate and smooth.

Candidates:
- mobile menu
- dropdowns
- product grid/list mode
- filters UI
- sort selector
- tabs
- theme switch
- language UI if architecture safely supports reactive update
- favorites state
- accordion
- modal
- search suggestions

Rules:

- Do not add React/Vue/Livewire only for this.
- Do not duplicate server business logic in JS.
- Keep canonical URL/query state correct.
- Back/forward must remain sane.
- Persistent mutations still require server validation.
- prevent double-submit
- show loading/success/error feedback
- preserve scroll position when useful
- respect `prefers-reduced-motion`

Full reload remains acceptable for authentication, file download, print, or flows where server navigation is the correct architecture.

---

# 10. MYANMAR LOCALIZATION

Audit:
- `lang/en/messages.php`
- `lang/my/messages.php`
- `lang/zh_CN/messages.php`
- hardcoded user-facing text

Burmese requirements:

- short
- natural
- retail-friendly
- action-oriented
- not literal machine translation
- consistent terminology

Prefer concise labels where context is already obvious.

Keep standard acronyms when clearer:
- SKU
- IMEI
- SN
- PIN
- KPay
- COD

Preserve placeholders:
- `{name}`
- `{count}`
- `%s`
- Blade/PHP variables

Test Myanmar rendering for:
- button wrap
- clipped nav labels
- search placeholder
- card title
- dialog width
- mobile drawer
- table/header if any
- footer
- order forms

No raw translation key should appear to customers.

---

# 11. ACCESSIBILITY

Verify:

- semantic HTML
- correct button/link usage
- keyboard navigation
- visible focus
- skip/navigation usability
- accessible names
- icon-only aria-label
- input labels
- error association
- dialog/drawer focus handling
- escape-to-close when appropriate
- color contrast
- touch target size
- reduced motion
- image alt text
- screen-reader-safe decorative icons

Do not treat Lighthouse score alone as proof.

---

# 12. PERFORMANCE / CORE WEB VITALS

Check:

- LCP
- CLS
- INP/interactivity
- image dimensions
- lazy loading
- oversized images
- duplicate assets
- blocking scripts/styles
- excessive layout complexity
- unnecessary Alpine watchers
- huge layout file impact
- query count / N+1
- public-page cache correctness
- cache key store separation
- repeated AJAX calls
- search suggestion debounce/cancellation
- font loading / CLS
- Vite production build

Do not add expensive visual effects that hurt low-end phones.

---

# 13. SEO / PUBLIC STORE QUALITY

Check where relevant:

- page title
- meta description
- canonical
- robots behavior
- product structured metadata if already supported
- duplicate URLs/query variants
- product/category discoverability
- broken social/meta image
- image alt
- 404 handling
- unavailable product behavior
- no accidental indexing of auth/admin/private pages

Do not invent a new SEO subsystem if none is required; fix concrete production issues.

---

# 14. TESTS TO INSPECT / RUN

Use the current test tree. Relevant existing tests may include equivalents of:

- `ProductCatalogTest`
- `ProductDetailTabsAndSpecsTest`
- `ProductDiscoveryTest`
- `ProductEcommerceVisibilityTest`
- `WebCatalogProductVisibilityTest`
- `StorefrontBannerRenderingTest`
- `StorefrontBrandingRenderingTest`
- `StorefrontBrowseTest`
- `StorefrontEmptyCategoryFilterTest`
- `StorefrontNavigationContextTest`
- `StorefrontHowToOrderTest`
- `StorefrontChatButtonSettingTest`
- `StorefrontSocialMediaSettingTest`
- `StorefrontTaglineTest`
- `FlashSaleHomeSectionTest`
- `HomeBannerDescriptionTest`
- `CustomerAccountTest`
- `CustomerOrderBuilderTest`
- `OrderRequestTest`
- `StorePaymentDeliveryAndMapTest`
- `StoreSettingsAndBrandingTest`
- `StoreAuthorizationTest`
- `StoreContextResolverTest`
- `StoreScopedRouteSignatureTest`
- `LocalizationTest`
- `LocalizationKeysParityTest`
- `FrontendAssetIntegrityTest`
- `HttpsConfigurationTest`

Do not assume filenames still exist. Locate current equivalents.

Workflow:
1. run targeted tests around changed code
2. fix failures caused by the changes
3. run broader relevant Storefront tests
4. run full suite if practical / required by shared changes
5. run `npm run build` for Blade/Tailwind/JS asset changes

Do not hide unrelated pre-existing failures. Report them separately.

---

# 15. MANUAL VISUAL QA

Use browser/dev preview if available.

Test both:
- Light Mode
- OLED Dark Mode

Test languages:
- Myanmar
- English
- Chinese if currently supported

Test user states:
- guest
- logged-in retail customer
- wholesale customer where applicable

Capture/report:
- page
- viewport
- problem
- severity
- reproduction steps
- root cause
- fix
- re-test result

---

# 16. LIGHTHOUSE

Run relevant public pages on Mobile + Desktop if tooling is available.

Report:
- Performance
- Accessibility
- Best Practices
- SEO
- major LCP/CLS/INP causes

Do not fabricate scores if Lighthouse cannot run.

---

# 17. SAFE FIX POLICY

You are authorized by this prompt to make **small, clearly correct, production-safe fixes** within the E‑Commerce/Storefront scope.

Prioritize:
1. Critical security/data leak
2. Order correctness
3. Store isolation
4. Broken shopper flow
5. Mobile/rendering regression
6. Accessibility
7. Theme consistency
8. Localization
9. Performance
10. Cosmetic polish

Avoid unrelated rewrites.

If a fix requires:
- broad schema redesign
- inventory architecture change
- finance/accounting rule change
- destructive data migration
- major route contract change
- large cross-module redesign

document it as a blocker/proposal instead of guessing.

---

# 18. FINAL PRODUCTION READINESS REPORT

Return a structured final report:

## A. Executive Result
- `GO`
- `GO WITH CONDITIONS`
- `NO-GO`

## B. Critical / High Findings
For each:
- Severity
- Area
- Root cause
- Security/business impact
- Files affected
- Fix status
- Verification

## C. Functional Shopper Flow
- Passed
- Failed
- Not tested

## D. Store Isolation
Explicitly state whether cross-store leakage was tested and what happened.

## E. UI/UX
Report:
- responsive
- mobile overflow
- 5/3/2 grid
- 8px mobile outer padding
- ~4px section rhythm
- Light Daylight theme
- OLED Dark theme
- 3D/elevated buttons
- smooth transitions
- localization
- accessibility

## F. Performance / Lighthouse
Actual results only.

## G. Tests
Include commands and:
- passed
- failed
- skipped
- pre-existing failures

## H. Files Changed
Explain why each file changed.

## I. Remaining Risks
Anything not verified must stay visible.

## J. Production Blockers
List exact blockers before deployment.

---

# DEFINITION OF DONE

Do not say **Done / Fixed / Production Ready** unless:

- code path was inspected
- relevant bug was reproduced or clearly proven
- fix was implemented
- targeted tests passed
- production asset build passed when frontend assets changed
- critical Storefront flows were re-tested
- Store isolation was checked
- Light + OLED Dark rendering was checked
- Mobile/Tablet/Desktop rendering was checked
- remaining risks are documented

**No fake confidence. Evidence first.**
