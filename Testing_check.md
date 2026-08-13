# 🧪 DataPOS — Pre-Production Testing Check (2026-08-04)

> **⚠️ File status (2026-08-13):** ဒီဖိုင်က **2026-08-04 GUI test session ရဲ့ မှတ်တမ်း** (historical) —
> အောက်ပါ issues/fixes တွေက အဲဒီအချိန် အခြေအနေပါ။ လက်ရှိ test suite: **821 passed / 3671 assertions**
> (`php artisan test`) — POS Phase 1–2.5p1 အပြီး။ အသစ်တွေ့တဲ့ bug တွေကို ဒီမှာ ဆက်ထည့်နိုင်ပါတယ်။

> Method: pure GUI black-box testing (Playwright, desktop 1280×800 + mobile 390×844) acting as **customer / store manager / platform owner / staff**, plus a read-only code review. Sample data was entered through the real forms (2 each). Screenshot evidence: `C:\Users\kkl\.zcode\workspace\default\gui-test-screenshots\t*.png`. No code was modified during testing.

**Verdict:** ✅ **21/21 test points passed. No 500s, no blank pages, no broken storefront links.**
**Production blockers found: 1 (S-1 — Markdown shown as raw text). Recommended fixes: 4 medium (S-2 → H-2, A-1, A-3).** Everything else is minor/optional.

---

## 1. Test accounts (local only — delete before production)

| Role | Phone | Password | Note |
|---|---|---|---|
| Store Manager | `09999999999` | `Test@12345678` | existing TestAdmin, password reset for testing |
| Platform Owner (TEST) | `09100000008` | `Test@12345678` | created for this test |
| Staff (TEST) | `09100000009` | `Test@12345678` | created for this test, assigned store 1 as staff |

⚠️ These are LOCAL test accounts only — never create them on production.

## 2. Sample data entered (TEST-* — delete before go-live)

| Type | Entries | Where |
|---|---|---|
| Categories | `TEST-Cat-Screen` (test-cat-screen), `TEST-Cat-Cable` (test-cat-cable) | Admin → Catalog → Categories |
| Brands | `TEST-Brand-Alpha`, `TEST-Brand-Beta` | Admin → Catalog → Brands |
| Products | `TEST-Prod-Flash` (SKU TEST-FLASH-001, retail 12,000 / old 15,000, sale ends 2026-08-05), `TEST-Prod-Plain` (SKU TEST-PLAIN-001) | Admin → Catalog → Products |
| Blog posts | `TEST-Blog-Hello` (id 13, Mobile Guide), `TEST-Blog-Tips` (id 14, Tips & Tricks) — both published | Admin → Content → Blog Posts |
| Reviews | "TEST Reviewer 1" (5★) + "TEST Reviewer 2" (4★) on iPhone 15 Pro Max — both APPROVED | Storefront review form + admin approval |
| Order | `ORD-6A71EA52D8D50` (order #10) — TEST Customer / 09100000002, 1× iPhone 256GB, Ks 5,120,000 — status set to **confirmed** | Storefront Order Builder → admin |

Cleanup: delete the 2 TEST products (cascade removes their reviews… reviews are on iPhone — delete the 2 TEST reviews), 2 TEST categories, 2 TEST brands, 2 TEST blog posts, order #10, then the 3 test users.

## 3. Test results (21 points)

| # | Point | Result | Evidence |
|---|---|---|---|
| t1 | Blog index (category chips, cards, links) | ✅ PASS — filter works (Mobile Guide → 1 post) | t1_blog_index.png |
| t2 | Blog detail (content, tags, share, prev/next, reading time) | ✅ PASS | t2_blog_show.png |
| t3 | Home (hero, flash countdown ticking, categories row, nav) | ✅ PASS — countdown ticked 02:08:13→02:08:04 | t3_home.png |
| t4 | Products (sort, category filter, price min/max, clear, pagination) | ✅ PASS | t4_products.png |
| t5 | Product detail (variant select → price/SKU change, collapsed order form, sticky mobile bar) | ✅ PASS — 512GB → SKU APL-IP15PM-512-BL, Ks 5,900,000 | t5_product_detail.png, t5_product_mobile_bottombar.png |
| t6 | Review submission ×2 (star picker + success message) | ✅ PASS | t6_review_submit.png |
| t7 | Guest order flow (add → builder → submit → success + ref) | ✅ PASS — ORD-6A71EA52D8D50 | t7_order_success.png |
| t8 | Glass Finder + How to Order pages | ✅ PASS (raw Markdown on How to Order — see S-1) | t8_howto.png |
| t9 | Manager login + dashboard (stats, chart, top products) | ✅ PASS | t9_dashboard.png |
| t10 | Create 2 categories | ✅ PASS — form has name/image/desc only (no icon field — see A-3) | t10_categories.png |
| t11 | Create 2 brands | ✅ PASS | t11_brands.png |
| t12 | Create 2 products (one WITH sale window) | ✅ PASS — storefront shows -20% red-strike badge on TEST-Prod-Flash (retail view) | t12_products.png |
| t13 | Create 2 blog posts (block editor) + publish | ✅ PASS — appear in admin + storefront | t13_blog_admin.png |
| t14 | Approve 2 reviews → visible on product page | ✅ PASS — avg "4.5 ★ (2)" correct | t14_reviews_approve.png |
| t15 | Order detail + status update + printable invoice | ✅ PASS — pending_contact → confirmed; invoice renders | t15_order_admin.png |
| t16 | Settings pages ×4 + save | ✅ PASS — no 404/500 | t16_settings_general.png |
| t17 | Header calculator (7+8=15) | ✅ PASS | t17_calculator.png |
| t18 | Product search (name + variant SKU/name) | ✅ PASS — "512" found 10 via variants | t18_search.png |
| t19 | Admin mobile 390px (no horizontal overflow) | ✅ PASS — scrollWidth == 390 | t19_admin_mobile.png |
| t20 | Owner login → platform store selector | ✅ PASS | t20_owner.png |
| t21 | Staff login → menu/permissions | ⚠️ PASS with issue — Settings menu visible but 403 (see H-2) | t21_staff.png |

## 4. Console errors collected

| Point | URL | Error |
|---|---|---|
| t21 | `/store/datapos-mobile/admin/settings` (as staff) | `403 Forbidden` (expected access control — but menu shouldn't be shown, see H-2) |

All other pages: **0 console errors / 0 warnings.**

---

## 5. Issues — verified by re-check (❌ = false positive, investigated)

### 🛑 Should fix — production blocker
- **S-1 (HIGH) Storefront shows raw Markdown as text.** Footer contact/payment blocks, How to Order page intro, and the invoice header print `**No. 478, Khaing Shwe War Street…**`, `## 💳 ငွေပေးချေနိုင်သော နည်းလမ်းများ`, `**Wave Pay** – **09 784 343 151**`, `## 👋 အသုံးပြုနည်း` literally — the stored `storefront_settings` content (delivery_info / payment_info / footer_ad_text / how_to_intro) contains Markdown syntax that views render as plain text. **Fix:** strip Markdown from the stored values (or render a minimal Markdown pass) — affects every page's footer + how-to-order + invoice. (Verified in DB.)

### 🛑 Should fix — medium
- **H-2 Staff sees a "Settings" menu it cannot use** — sidebar shows ဆက်တင်များ for staff; clicking → 403 Forbidden page. Either hide the item for staff or show a friendly "no permission" state. (t21)
- **A-1 iPhone variants show a flat pill list instead of grouped rows.** The grouped "Mobile Storage / Phone Color" selector (built earlier) falls back to flat because the current iPhone variants have `attributes = null` in DB. Fix = re-save variants via admin preset (or backfill attributes JSON). Not a code bug — a data gap. (Verified: `attributes: null`.)

### 🔧 Should fix — low
- **S-3 Invoice "Total Due" and amount run together** — "Total DueKs 5,120,000" (missing space). (t15)
- **S-4 Order detail shows "Contact Address: 09100000002"** — the customer's phone number rendered under a Contact Address label; either fix the label/value mapping or drop the row. (t15)
- **S-5 Flash-sale header "မကြာမီ စတင်မည်" (starts soon) with a running countdown while an active -14% deal is visible** — the countdown target (soonest upcoming start) is correct but visually ambiguous; consider per-card labels or header wording ("အချိန်ပိုင်း လျှော့စျေး" + subtitle). Design decision — user's call. (t3)
- **S-6 "All" category chip on the blog page is untranslated** on the Burmese UI. (t1)

### ➕ Should add
- **A-3 (low) Category create form has no icon/emoji field**, though `categories.icon` exists and the header dropdown shows icons (categories fall back to a generic tile). Add an icon picker to the admin form.
- **A-2 (low) Blog pagination** — currently none; appears only when >9 posts exist. Add `paginate()` control to the index if the blog grows.

### 🗑️ Should delete
- **D-1 (low) Duplicate "📍 📍"** in the footer contact block (double location pin before the address — same root cause as S-1; the stored content has two pins).
- **D-2 (low) "Contact Address" duplication on order detail** — same as S-4; if the phone was never meant to be an address, drop that row.

### ❌ Checked and NOT bugs (do not re-report)
- **H-1 "Bottom nav invisible on product detail"** — FALSE POSITIVE. Verified: at 390px the scroll-aware nav hides on scroll-down (by design, `translate-y-[180%]`) and reappears on scroll-up / at top (rect 766–832 in view). The tester measured it in its intentional hidden state.
- **H-3 "Login page pre-fills admin credentials"** — browser autofill of a previously-saved account in the test browser, NOT a site bug (auth views have no autocomplete prefill). Optional hardening: `autocomplete="new-password"` on the password field if a shared/public machine is a concern.
- **S-2 "Per-review stars show 5★ for both reviews"** — FALSE POSITIVE. Code dims stars above the rating (`opacity-25` on `$i > $review->rating`); the 4★ review's 5th star is dimmed visually but still present in DOM text, so an accessibility snapshot shows 5 ★ characters. Average "4.5 ★ (2)" confirms stored values are correct.

## 6. Code-level review (read-only)

- 125 routes; all key storefront/admin routes → 200; unknown paths → correct 404; no 500s.
- No debug leftovers (`dd/dump/var_dump`), no TODO/FIXME, no broken asset references.
- `robots.txt` disallows /login, /register, /admin, /store/*/admin, glass-finder favorite ✓
- `sitemap.xml` includes products + blog + static pages, follows `APP_URL` (set to the real domain on production) ✓
- Reviews rate-limited (5/min per IP) ✓; admin routes behind auth + `EnsureStoreAccess` ✓
- Flash-sale countdown: on expiry does one soft reload so the server re-renders deals ✓
- Local `.env` is local-only (APP_ENV=local, APP_DEBUG=true, ALLOW_UAT_SEEDING=true) — production runbook flips these.

## 7. Suggested fix order before go-live

1. **S-1** strip Markdown from `storefront_settings` content (footer / how-to-order / invoice) — 1 hour
2. **H-2** hide Settings menu for staff role (or friendly 403 page)
3. **A-1** backfill `attributes` on product variants (re-save iPhone variants via preset)
4. **S-3 / S-4 / S-6 / D-1 / D-2** quick template fixes
5. **A-3** category icon picker (nice-to-have)
6. Re-run: `php artisan test` (349 tests) + this checklist's t1–t9 spot checks

---

## 8. Reusable English prompts (copy-paste for this kind of full-site test)

### Prompt 1 — Full pre-production audit (owner/manager/employee roles)
> Act as the owner, store manager, and a staff member of my e-commerce shop and do a thorough pre-production QA pass on my site at {URL}. Log in with the admin account I give you and enter sample data through the real forms: create 2 categories, 2 brands, 2 products (one with a sale price + sale window), 2 blog posts, and submit 2 product reviews and 1 test order from the storefront as a guest. Then test the main customer journeys (home, product list + filters, product detail + variant selection + add-to-cart, order builder, blog, how-to-order, glass finder) and every admin section (dashboard, orders + status updates + invoice, products, blog editor, reviews approval, settings pages, calculator). For each page check: it loads without console errors, no broken links, no 404s, layout is not broken on a 390px phone viewport, and forms give clear success/error feedback. Collect console errors as you go. Do NOT modify code and do NOT delete anything. Then write me a file called Testing_check.md containing: (1) what you tested and passed/failed per page with screenshot evidence, (2) a list of issues grouped into exactly four categories — Hard to use / Should fix / Should add / Should delete — each with severity and reproduction steps, (3) any console errors with the page they happened on, (4) the exact list of sample data you created so I can delete it later, (5) a final verdict on whether the site is ready for production and what MUST be fixed first.

### Prompt 2 — Storefront customer-journey check (no data entry)
> Act as a first-time customer in Myanmar with a budget phone and slow internet. Open my site at {URL} on a 390x844 phone viewport and walk through: home page (flash sale countdown, category row, featured), product list with filters and sorting, product detail (variant selector, sale badge, review form, add to order), order builder/checkout, blog reading, how-to-order, glass finder, and the floating chat button. Report: anything confusing or hard to use, any button that does nothing, any layout overlap/overflow, text that looks broken or empty, and load-time killers (large images, missing lazy-loading, external requests). No code changes — just a findings list with priorities.

### Prompt 3 — Admin data-entry & workflow check (manager view)
> Act as my store manager. Log in to the admin panel at {URL} with the account I provide. Test every data-entry workflow end to end: create a category, a brand, a product (with variants via preset, sale price, WYSIWYG description), a blog post (block editor), approve a product review, update an order status and open the printable invoice, edit store settings (General/Contact/Delivery/How to Order) and save, use the calculator, and search products. Note anything that is slow, confusing, errors, loses input, or breaks validation messages. Give me a list of what to fix before production with severity.

### Prompt 4 — Quick daily regression (2 minutes)
> Run a quick regression on {URL}: load home, products, one product detail, blog, and the admin dashboard. Check console errors, page status, and that the bottom nav / chat button / countdown timer work. Report only what changed or broke since yesterday.

---

## 2026-08-07 Daily Test Session

> Automated test suite run + Playwright browser verification during feature development.

**Verdict:** ✅ **380 tests passed / 1910 assertions / 0 failures** (full suite)

### Changes tested (items 114–126)

| Item | Feature | Test Status | Browser Verified |
|------|---------|-------------|-----------------|
| 114 | Header: Menu & Favorites swap | ✅ All pass | ✅ Mobile + Desktop |
| 115 | Header: Lang + Dark icons all viewports | ✅ All pass | ✅ Mobile 390px |
| 116 | Header: Language/Theme removed from drawer | ✅ All pass | ✅ Drawer only shows Account |
| 117 | Product Card: Heart red→green toggle | ✅ All pass | ✅ Playwright JS verify classes |
| 118 | Sale Discounts: 15 products | ✅ All pass | ✅ Discount badges visible |
| 119 | Footer: Shopee full-bleed design | ✅ All pass | ✅ Dark bg, 3-col grid |
| 120 | Footer: Payment icons + Google Map | ✅ All pass | ✅ KPay/WavePay/CB/MMQR/COD + Map link |
| 121 | Glass Finder: Banner-search gap 5px | ✅ All pass | ✅ gap = 5px |
| 122 | Glass Finder: Banner header-flush | ✅ All pass | ✅ gap = 0px from header |
| 123 | Glass Finder: Banner height increased | ✅ All pass | ✅ Mobile 200px / Desktop 280px |
| 124 | Glass Finder: Rounded corners removed | ✅ All pass | ✅ Sharp corners |
| 125 | Glass Finder: Card rounded-2xl + gap | ✅ All pass | ✅ Reduced gaps |
| 126 | Admin Login: KoKoLInn password reset | ✅ All pass | ✅ Login success |

### Artisan Test Commands
```bash
php artisan test                          # Full suite — 380 pass, 1910 assertions
php artisan test --filter="GlassFinderTest"  # Glass Finder — 16 pass, 88 assertions
php artisan test --filter="StoreSettingsAndBrandingTest"  # Settings — 7 pass, 33 assertions
php artisan test --filter="StorefrontNavigationContextTest|CustomerAccountTest|ProductDiscoveryTest"  # Nav + Products — 17 pass, 105 assertions
```

### npm build
```bash
npm run build   # CSS JIT rebuild (Tailwind arbitrary classes: min-h-[200px] etc.)
```

### Home Page Banner Update (welcome.blade.php) — 2026-08-07
- Mobile 390px: header-gap 0px, hero 202px, flash-sale gap 5px, no horizontal overflow ✅
- Desktop 1280px: hero responsive (min-h lg 280px), sections 32px spacing ✅
- Full suite: 380 passed / 1910 assertions / 0 failures ✅
- Files: `resources/views/welcome.blade.php` (item 128)

---

## 2026-08-07 Daily Test Session — Storefront AliExpress Redesign (Items 129–153)

> Automated suite + live Preview verification (Playwright-style) during the AliExpress-style storefront redesign.

**Verdict:** ✅ **380 tests passed / 1910 assertions / 0 failures** (full suite, 11.35s) — includes the new `StorefrontBrowseTest` (4 tests / 18 assertions).

### Changes tested (items 129–153)

| Item | Feature | Test Status | Browser Verified |
|------|---------|-------------|-----------------|
| 129 | /browse two-pane browser (BrowseController + view) | ✅ StorefrontBrowseTest 4/4 | ✅ Mobile + Desktop |
| 130 | Product card full-bleed + tap-to-reveal | ✅ All pass | ✅ Mobile 390px touch |
| 131 | Catalog hairline grid + Grid/List toggle + localStorage | ✅ All pass | ✅ 4/3/2 columns, reload persistence |
| 132 | Home product cards = products page style | ✅ All pass | ✅ Match confirmed |
| 133 | Favorite icon top-right (all cards) | ✅ All pass | ✅ Larger tap target |
| 134 | List card 4-button row kept | ✅ All pass | ✅ Verified layout |
| 135 | Mobile full-bleed (px-1, 4px) | ✅ All pass | ✅ No overflow 390px |
| 136 | Bottom nav AliExpress edge-to-edge + bold tabs | ✅ All pass | ✅ Edge-flush nav |
| 137 | Font sizes 9–11px → 12–14px + contrast | ✅ All pass | ✅ Lighthouse-fixed |
| 138 | Product images lazy-load | ✅ All pass | ✅ Hover-only 2nd image |
| 139 | Mobile header + favorite count icon + /browse shortcut | ✅ All pass | ✅ Count badge |
| 140 | Mobile menu ☰ → left-drawer + swipe | ✅ All pass | ✅ Slide-in gesture |
| 141 | Live search suggestions (name + price) | ✅ All pass | ✅ Mobile + desktop overlay |
| 142 | Suggestions: category/brand sections + trending chips | ✅ All pass | ✅ Sections render |
| 143 | /browse fully responsive rewrite | ✅ All pass | ✅ 390/768/1280 all good |
| 144 | Desktop Categories flyout → mega menu | ✅ All pass | ✅ Rail + panel sync |
| 145 | Responsive columns 4/3/2 · browse 2-col · glass 2/3/4 | ✅ All pass | ✅ Per-viewport verified |
| 146 | Glass Finder List + Table view toggle | ✅ 20/20 (batch) | ✅ 214 rows table, localStorage |
| 147 | Glass Finder actions once per glass code | ✅ 31/31 (batch) | ✅ 4 actions per code, 0 per row |
| 148 | Favorites code-level glass display fix | ✅ 32/32 (batch) | ✅ Rich name + Glass pill + cart id |
| 149 | Favorites full-name cards grid (2/3/4) | ✅ 32/32 (batch) | ✅ 1/2/3 → 2/3/4 verified |
| 150 | Favorites image/placeholder tiles (brandHue) | ✅ 50/50 (batch) | ✅ Photo + gradient + broken-img fallback |
| 151 | StorefrontBrowseTest (new) | ✅ 4 pass / 18 assertions | — |
| 152 | Full suite | ✅ 380 / 1910 / 0 failures | — |
| 153 | Preview live checks | ✅ | ✅ 390/768/1280 no overflow, console 0 |

### Targeted runs (today)
```bash
php artisan test --filter="StorefrontBrowseTest|GlassFinderTest|CustomerAccountTest|StorefrontNavigationContextTest|ProductCatalogTest|LocalizationKeysParityTest"
# → 52 passed (404 assertions)
php artisan test --filter="StorefrontBrowseTest"     # → 4 passed (18 assertions)
php artisan test                                     # → 380 passed (1910 assertions)
```

### Browser verification highlights
- **Glass Finder List view:** 20 full-width cards (551px), actions 4 per card header, 0 buttons in model rows ✅
- **Glass Finder Table view:** 20 sections, 4 actions per section, 214 model rows with zero buttons ✅
- **Favorites:** full names no clipping, brandHue(OPPO)=351 matches PHP mirror, broken-image → placeholder ✅
- **localStorage persistence:** `catalog_view` + `glass_view` survive reload ✅
- Console errors: 0 on fresh load (pre-rebuild stale exceptions cleared) ✅

---

## 2026-08-07 Daily Test Session — Banner Carousel Smooth Transition (Item 154)

> Banner slide ကူးတဲ့အခါ page jump/lurch ဖြစ်နေတဲ့ bug ကို CSS Grid stacking (fixed container height) + pure fade transition နဲ့ ပြင်ဆင်ပြီး regression စစ်ခဲ့။

**Verdict:** ✅ **380 tests passed / 1910 assertions / 0 failures** (full suite, 12.19s) — LocalizationKeysParityTest 4/4, storefront suites 40/40 (298 assertions). `npm run build` ✅

### Changes verified (item 154)

| Item | Feature | Test Status | Note |
|------|---------|-------------|------|
| 154 | Home banner: slide text → CSS grid stack + fade | ✅ Home renders (HTTP 200) | No height jump on transition |
| 154 | Glass Finder banner: same grid-stack pattern | ✅ GlassFinderTest 16/16 | No height jump on transition |
| 154 | `messages.special_offer` in en/my/zh_CN | ✅ Parity 4/4 (80 assertions) | No missing key |

### Targeted runs
```bash
php artisan test --filter="LocalizationKeysParityTest"     # → 4 passed (80 assertions)
php artisan test --filter="StorefrontBrowseTest|GlassFinderTest|ProductCatalogTest|StorefrontNavigationContextTest"
# → 40 passed (298 assertions)
```

### Runs
```bash
php artisan test                                    # → 380 passed (1910 assertions)
npm run build                                       # → Vite production build ok
```

### Next
- Playwright/visual check 390/768/1280 banner transitions.

---

## 2026-08-07 Daily Test Session — Flash Sale Section Upgrade (Item 155)

> Customer attention ရအောင် flash sale section ကို UI upgrade လုပ်ပြီး regression စစ်ခဲ့။

**Verdict:** ✅ **380 tests passed / 1910 assertions / 0 failures** (full suite, 11.96s) — FlashSaleHomeSectionTest 3/3, `npm run build` ✅

### Changes verified (item 155)

| Item | Feature | Test Status | Note |
|------|---------|-------------|------|
| 155 | Flash sale section: glow bg + max discount badge + countdown box | ✅ FlashSaleHomeSectionTest 3/3 | Existing section still renders |
| 155 | Deal cards: rose border, hover lift, bigger discount badge, hover title | ✅ StorefrontBranding/StorefrontNavigation pass | No layout regressions |
| 155 | `npm run build` after new Tailwind classes | ✅ Vite build ok | `app-BLE4B8TW.css` |

### Runs
```bash
php artisan test --filter="FlashSaleHomeSectionTest|StorefrontBrandingRenderingTest|StorefrontNavigationContextTest"
# → 16 passed (95 assertions)
php artisan test                          # → 380 passed (1910 assertions)
npm run build                             # → ok
```

### Next
- Playwright visual check 390/768/1280 of flash-sale cards + hover states.

---

## 2026-08-07 Daily Test Session — Flash Sale Product Cards Upgrade (Item 156)

> Flash sale deal cards တွေကို You-save chip + arrow CTA + price alignment + gradient accent တွေနဲ့ ထပ်မြှင့်ပြီး regression စစ်ခဲ့။

**Verdict:** ✅ **380 tests passed / 1910 assertions / 0 failures** (full suite, 11.27s) — FlashSaleHomeSectionTest 3/3, LocalizationKeysParityTest 4/4, `npm run build` ✅

### Changes verified (item 156)

| Item | Feature | Test Status | Note |
|------|---------|-------------|------|
| 156 | You save (Ks) green chip | ✅ FlashSale + Parity pass | `you_save` in en/my/zh_CN |
| 156 | Gradient arrow CTA + price bottom-aligned | ✅ FlashSaleHomeSectionTest 3/3 | Card layout flex-col + mt-auto |
| 156 | Active deal gradient bottom bar + hover border | ✅ Full storefront suites pass | No overflow risk (truncate) |
| 156 | `npm run build` after new classes | ✅ Vite build ok | `app-B9SCtCuB.css` |

### Runs
```bash
php artisan test --filter="FlashSaleHomeSectionTest|LocalizationKeysParityTest|StorefrontBrandingRenderingTest|StorefrontNavigationContextTest"
# → 20 passed (175 assertions)
php artisan test                          # → 380 passed (1910 assertions)
npm run build                             # → ok
```

### Next
- Playwright visual check 390/768/1280 of deal card hover states + overflow.

---

## 2026-08-07 Daily Test Session — Upcoming Sale Label Wording (Item 157)

> “မကြာမီ စတင်မည်” ကို “လာမည့် လျှော့စျေး / Upcoming Sale / 即将开抢” ပြောင်းပြီး locale parity + flash-sale regression စစ်ခဲ့။

**Verdict:** ✅ **380 tests passed / 1910 assertions / 0 failures** (full suite, 11.78s) — LocalizationKeysParityTest 4/4, FlashSaleHomeSectionTest 3/3.

### Changes verified (item 157)

| Item | Feature | Test Status | Note |
|------|---------|-------------|------|
| 157 | my: `starting_soon` = လာမည့် လျှော့စျေး | ✅ Parity pass | Better distinction from section title |
| 157 | en: `starting_soon` = Upcoming Sale | ✅ Parity pass | Same upstream/downstream keys |
| 157 | zh_CN: `starting_soon` = 即将开抢 | ✅ Parity pass | Same status wording |

### Runs
```bash
php artisan test --filter="LocalizationKeysParityTest|FlashSaleHomeSectionTest"
# → 7 passed (100 assertions)
php artisan test                          # → 380 passed (1910 assertions)
npm run build                             # → ok
```

### Next
- Browser refresh + UI wording check on home.

---

## 2026-08-07 Daily Test Session — Live Deploy + Split-Layout Front Controller Fix (Item 158)

> `deploy-datapos.sh` နဲ့ datapos.com deploy runခဲ့ — live site 500 ဖြစ်တာကို public_html/index.php split-layout front controller fix နဲ့ ပြန်ရှာခဲ့။

**Verdict:** ✅ Live `https://datapos.com` → **HTTP 200 OK**, title `DataPOS`. Deploy script bug fix (item 158) added.

### Deployment checks

| Check | Result |
|-------|--------|
| SSH to Hostinger | ✅ SSH_OK, PHP 8.3.30 |
| `deploy-datapos.sh` code deploy | ✅ DEPLOY_OK |
| Live site after first deploy | ❌ HTTP 500 — `public_html/index.php` used local `../vendor` path |
| Root cause | Split layout webroot must point into `../laravel_app` for vendor/bootstrap/storage |
| Fix | Deploy script + live server now write split-layout `public_html/index.php` |
| Re-check live site | ✅ HTTP 200 OK |

### Commands
```bash
bash deploy-datapos.sh
curl https://datapos.com/?store_slug=datapos-mobile
```

---

## 2026-08-07 Daily Test Session — Desktop Product Card Warranty Badge (Item 159)

> Desktop grid card မှာ `🛡️ 1 Month Warranty` text badge က title ကို ညှစ်နေလို့ compact shield icon + tooltip ပုံစံပြောင်းပြီး regression စစ်ခဲ့။

**Verdict:** ✅ Targeted suites pass — 40 tests / 296 assertions. `npm run build` ✅

### Changes verified (item 159)

| Item | Feature | Test Status | Note |
|------|---------|-------------|------|
| 159 | Warranty text badge → compact shield icon | ✅ ProductDiscovery + ProductCatalog pass | Full text on `title`/`aria-label` |
| 159 | Product name gets more width | ✅ StorefrontBrowse/Nav/CustomerOrderBuilder pass | `truncate` keeps name visible |
| 159 | Build after new widths | ✅ Vite ok | `w-6 h-6 sm:w-7 sm:h-7` |

### Runs
```bash
php artisan test --filter="ProductDiscoveryTest|ProductCatalogTest|StorefrontBrowseTest|StorefrontNavigationContextTest|CustomerOrderBuilderTest"
# → 40 passed (296 assertions)
npm run build
```

### Next
- Live deploy after commit if user confirms.

---

## 2026-08-07 Daily Test Session — Warranty Icon Blue Color (Item 160)

> Desktop card warranty shield icon ကို amber → blue/sky color ပြောင်းပြီး quick regression စစ်ခဲ့။

**Verdict:** ✅ ProductCatalogTest / ProductDiscoveryTest / StorefrontBrowseTest — 29 tests / 219 assertions pass. `npm run build` ✅

### Change verified

| Item | Feature | Test Status |
|------|---------|-------------|
| 160 | Warranty icon blue/sky gradient + border | ✅ Catalog/Discovery/Browse pass |

---

## 2026-08-08 Daily Test Session — Admin Panel Clean Redesign + Performance (Items 176-182)

> Admin CSS bundle split, admin clean/full-width/borderless redesign (33 pages), admin font-size 12px+, fonts→WOFF2, favicon optimize, deploy cleanup, hosting deploy.

**Verdict:** ✅ Admin tests 103/103 pass · FrontendAssetIntegrityTest ✅ · LocalizationKeysParityTest ✅ · Build ✅ · Blade compile (33 views) ✅ · Live deploy `DEPLOY_OK`

### Tests run

| Batch | Result |
|-------|--------|
| Admin (dashboard/sidebar/products/users/orders/settings/banners/backup/finance/note/prod-readiness/workflow) | ✅ 99/99 pass |
| Admin + Asset + Localization + Business workflow | ✅ 103/103 pass (536 assertions) |
| Full admin sweep (Admin\|StoreSettings\|Wholesale\|Import\|Category\|Brand\|Catalog\|GlassFinder\|Banner\|Backup\|Migration) | 243 passed, 3 failed (**pre-existing**: LoginBranding tagline ×2 + MigrationSafety "Order Summary" locale — proven pre-existing via `git stash` test) |

### Changes verified

| Item | Feature | Test Status |
|------|---------|-------------|
| 176 | Fonts → WOFF2 subsets (793KB→180KB) | ✅ build + live fetch 103KB |
| 177 | Favicon 207KB→11KB + apple-touch-icon PNG | ✅ live verify |
| 178 | Post-deploy cleanup (stale TTF/favicon.svg/hashed assets) | ✅ live: 0 TTF, svg removed |
| 179 | Admin CSS bundle split (226KB→112KB) | ✅ admin.css live 111KB |
| 180 | Admin clean/full-width/borderless redesign (33 views) | ✅ admin tests pass + preview verify |
| 181 | Admin font-size 9/10/11px→12px | ✅ getComputedStyle min 12px (0 under) |
| 182 | Hosting deploy datapos.com | ✅ HTTP 200 + new assets live |
| 183 | Admin UI refactor — clean shell (tablet drawer, mobile More menu, 44px targets, safe-area, :inert) + KPI hierarchy + design system | ✅ preview 397/459/674px + drawer/Escape/More-menu live + 268 tests pass |
| 184 | Hosting deploy #2 — admin refactor live | ✅ DEPLOY_OK + manifest hash match + classes served |
| 185 | Admin sidebar fixes — collapsed label-hiding (Home Banners root cause), single-open accordion, corner badge, drawer focus round-trip | ✅ 23/23 sidebar + live DOM verify |
| 186 | Sidebar DOM-assert tests — collapsed labels/centering/badge/single-open + aria-current improvement | ✅ 23 tests, 200+ assertions |
| 187 | Sidebar component extraction (x-admin.nav-link/nav-group) + Home Banners flag icon + deploy #3 | ✅ 23/23 + 46 admin + manifest hash match |
| 188 | Brands CRUD safety — safe delete (blocked when in use, backend + UI) + unique (store_id, name) + per_page whitelist | ✅ 35/35 brand tests + migration Ran |
| 189 | Brands UI/UX — debounced live search, reusable logo uploader, tabbed add/edit, accessible delete modal, responsive list | ✅ live browser end-to-end + 0 console errors |
| 190 | AdminBrandTest (35) + FlashSale regression fix + x-data/scope runtime bug fixes | ✅ brand 35/35 · batch 77/77 · full 406 pass (8 pre-existing) |
| 191 | Deploy #4 — Brands CRUD live (safe delete + unique (store_id,name) + live search + uploader) | ✅ DEPLOY_OK + migration [2] Ran + constraint insert-block verified |

### Session 2026-08-08 (afternoon) — Admin UI Refactor

**Admin/Storefront batch: 268 passed, 3 failed (1292 assertions)** — run with `--filter='Admin|StoreSettings|Wholesale|Category|Brand|GlassFinder|Banner|Backup|Migration|Localization|Catalog|Storefront|FrontendAsset|LoginBranding'`.

- `AdminSidebarNavigationUXTest` updated: sidebar breakpoint `md:` → `lg:` (tablet now uses the drawer) — 29/29 pass
- `AdminDashboardTest` + `AdminOrderAlertEndpointTest` — data-* stat attributes + test strings preserved, 35/35 pass
- `LocalizationKeysParityTest` — `more_actions` key added to my/en/zh_CN, parity pass
- **Build:** `npm run build` ✓ · `view:cache` (33 views) ✓
- **Browser (preview live):** dashboard 397/459/674px — no page-level horizontal overflow; drawer open/backdrop/Escape/close; More actions menu (View store/Reload/Calculator/language); dark mode clean; chart label overflow 0 (measured); min font 12px+; touch targets 44px (header/toolbar/buttons; table row actions compact by design)

### Notes
- `LoginBrandingAndSecurityTest` 2 failures + `MigrationSafetyTest` 1 failure are **pre-existing** (tagline assertion vs store-settings text; "Order Summary" literal vs Myanmar locale translation) — verified by stashing all 2026-08-08 changes and re-running (still fails on pristine code). Not related to admin redesign.
- Local dev sqlite DB owner password temporarily set to `PreviewTest123!` for preview verification (gitignored, not on production).
- Deploy #2 (2026-08-08): `./deploy-datapos.sh` → `DEPLOY_OK`; server manifest `admin-37dZfeUL.css` matches local build exactly; new design classes served; storefront/login 200; favicon.svg cleaned (404); 0 stale TTF files.

### Session 2026-08-08 (evening) — Sidebar Fixes + Component Extraction (Items 185–187)

**Sidebar/Admin batch: 376 passed, 8 failed (1915 assertions)** — the 8 failures are all **pre-existing** (LoginBranding ×2, MigrationSafety ×1, OrderRequest ×1, QuickLoginVisibility ×4 — verified via `git stash` on the pristine test file: identical 8 failures without my changes; environment-dependent, unrelated to sidebar).

- **Sidebar fix (185):** Home Banners label was the ONLY nav label missing the collapsed-hiding binding (`:class="sidebarCollapsed ? 'lg:hidden' : ''"`) — root cause of the clipped Burmese fragment in the 80px rail; added binding + icon centering (`lg:justify-center`) + `aria-label`/`:title` (collapsed-only tooltips); single-open accordion via `toggleGroup()`; collapsed desktop corner badge (`sidebarCollapsed && viewportLg` guard); drawer focus management (open → Close menu, close → hamburger, `:inert` when closed).
- **DOM-assert tests (186):** 4 new tests — every-nav-label-hiding (≥14 bindings + per-group loop + Home Banners aria/title), icon centering (≥8), corner badge (+ no `data-pending-order-count` when empty), single-open accordion internals; `extractUntil()` helper; `aria-current="page"` now on every active link (was Dashboard/Blog/Settings only) — test updated to assert the improved behavior.
- **Component extraction (187):** `<x-admin.nav-link>` (variant: main/direct/sub, collapsed bindings, icon + badge slots, `addslashes()`-hardened `:title`) + `<x-admin.nav-group>` (accordion button + subnav container + badge/corner-badge slots) — dot-syntax matching `<x-admin.toolbar>`; `variant` prop name (Blade reserves `type`); Home Banners icon photo → **flag/banner SVG**; deploy #3 `DEPLOY_OK` — server `app-BeE15R8Q.css` = local, home 200, dashboard 302 (auth).
- **Commands:** `php artisan test --filter=AdminSidebarNavigationUXTest` → 23/23 (201 assertions) · admin batch 46/46 · `npm run build` ✓ · `view:cache` ✓ · `./deploy-datapos.sh` → `DEPLOY_OK`
- **Browser (preview live):** 6 accordion groups + 19 links render from components; collapsed-click expands + opens group; corner badge hidden on mobile (viewportLg guard); Home Banners flag icon renders 14×14; dark mode drawer clean; no console errors.

### Session 2026-08-08 (night) — Brands CRUD Safety + UX + Tests (Items 188–190)

**Brand/Admin batch: 77 passed, 0 failed (454 assertions)** (`AdminBrandTest`, `CategoryBrandDestroyRouteTest`, `AdminDashboardTest`, `AdminSidebarNavigationUXTest`, `AdminUserManagementTest`, `LocalizationKeysParityTest`, `StorefrontAssetTest`). Full suite: **406 passed, 8 failed (2021 assertions)** — all 8 pre-existing (LoginBranding ×2, MigrationSafety ×1, OrderRequest ×1, QuickLoginVisibility ×4 — stash-verified earlier; environment-dependent).

- **Safe delete (188):** brands with `products_count > 0` can no longer be deleted — `destroy()` returns validation-style errors instead of silently nulling product `brand_id` (migration is `nullable -> nullOnDelete`); UI shows "Used by N products" + products-filter link; only 0-product brands get the confirmation modal.
- **Unique constraint (188):** migration `2026_08_08_000001_add_unique_name_to_brands_table` — unique `(store_id, name)`; duplicate audit before adding: 0 duplicate normalized names, 0 duplicate slugs (61 brands); controller enforces `LOWER(TRIM(name))` case-insensitive store-scoped uniqueness with ignore-self on update; whitespace-only names rejected.
- **per_page safety (188):** removed `per_page=all`→100000; `ALLOWED_PER_PAGE` whitelist (25/50/100), everything else falls back to 25.
- **Live search (189):** toolbar opt-in `liveSearch` prop — 450ms Alpine `@input.debounce` on desktop + mobile overlay, spinner, clear control, all query params preserved.
- **Logo uploader (189):** new reusable `x-admin.logo-uploader` — instant preview (object-URL revoked), filename/size, remove-selected, client type/size warnings (server authoritative), keep/replace/remove states that never conflict; used on both Add and Edit.
- **Delete modal (189):** accessible — role=dialog/aria-modal, Cancel initial focus, Escape/backdrop close, focus trap, focus returns to trigger, "Deleting…" state, trusted data-id/name (no stale-row submits).
- **Tests (190):** new `AdminBrandTest` — 35 tests covering authz, cross-store isolation, create (with/without logo, invalid type, oversized), duplicate-name rules (same store rejected, different store allowed), rename, logo replace/remove, safe delete (unused deletable / used blocked with associations + logo preserved), search name/slug, logo filter, sorts, pagination + safe per-page, query persistence, products-filter link.
- **Regression fixed (190):** `FlashSaleHomeSectionTest` created same-store "Deal Brand" ×4 which the new unique constraint rejected — brand names now get a random suffix (slugs already unique) — 3/3 pass.
- **Runtime bug fixes (190, found via visual verification):** (a) `x-data` attribute broken by `[tabindex="-1"]` — HTML has no backslash escaping, attribute terminated at first `"`, Alpine source leaked as visible text + scope dead → fixed with a JS `getAttribute('tabindex') !== '-1'` filter; (b) delete modal lived outside the `x-data` scope so `confirmTarget` never resolved → moved inside; (c) duplicate `id="brand-row-{id}"` on desktop `<tr>` and mobile `<div>` made mobile highlight-scroll target the hidden desktop row → mobile id `brand-row-m-{id}` + scroll whichever is visible; (d) `$refs.lastDelete` assignment → plain `lastDeleteEl` data property.
- **Browser (preview live, 397px mobile):** create → success flash + NEW highlight; live search → `?search=DAHUA` filtered to 1 row + active pill; duplicate "DAHUA" → error flash + auto-return to Add tab + field-level error; delete modal (Cancel focused → Escape closes → focus returns) → real delete → "Brand deleted successfully" + count 62→61; blocked state ("Used by 52 products" link, no Delete); uploader Alpine scope alive (file select → preview blob + filename); no leaked text; 0 console errors.
- **Commands:** brand suite 35/35 (118 assertions) · batch 77/77 · full suite 406 pass / 8 pre-existing · `npm run build` ✓ · `view:cache` ✓ · migration `[25] Ran` on local dev DB.
- **Not deployed** — new unique-name migration must run on production (audit production duplicates first) before the next deploy.

### Session 2026-08-08 (late night) — Deploy #4 (Item 191)

**Brands CRUD refactor (items 188–190) deployed to production — `RUN_MIGRATIONS=true ./deploy-datapos.sh` → `DEPLOY_OK`.**

- **Pre-migration production audit (SSH tinker on Hostinger):** `GROUP BY store_id, LOWER(TRIM(name)) HAVING COUNT(*) > 1` → **0 duplicates**; `GROUP BY store_id, slug HAVING COUNT(*) > 1` → **0 duplicates**; brand count **61** (matches local). Migration was safe to run.
- **Migration:** `php artisan migrate --force` ran `2026_08_08_000001_add_unique_name_to_brands_table` → `DONE`; `migrate:status` → `[2] Ran`.
- **Constraint live-verified:** transaction-wrapped duplicate insert (store 1, "Xiaomi") → `INSERT_BLOCKED: SQLSTATE[23000] Duplicate entry '1-Xiaomi'` (1062), then rollback — the unique (store_id, name) index is enforced on production. BRAND_COUNT=61 after rollback, no leftover test rows.
- **Site checks:** home 200 · admin brands 302 (auth) · server manifest `admin-gSQ42ZhT.css` = local build exactly.
- **Commands:** `npm run build` ✓ → `RUN_MIGRATIONS=true ./deploy-datapos.sh` → `DEPLOY_OK` · SSH: duplicate audit, `migrate:status`, insert-block test.
- **Live now:** used brands cannot be deleted ("Used by N products" + products link) · brand names case-insensitive store-scoped unique at controller + DB · 450ms debounced live search · logo uploader · accessible delete modal — https://www.datapos.com/store/datapos-mobile/admin/brands

### Session 2026-08-08 (late night #2) — Products-Style Brands/Categories UI + View Toggles + Add New Brand Fix (Items 192–197)

**Brand/Category/Admin batch: 78 passed, 0 failed (328 assertions)** (AdminBrandTest, AdminCategoryTest, LocalizationKeysParityTest). Full build clean; `view:cache` compile OK.

- **Products-style alignment (192):** Brands/Categories index headers replaced gradient icon-tile + count-pill with the shared `admin-page-header`/`admin-page-title`/`admin-page-sub` pattern (`{{ $store->name }} — {{ __('messages.brand_index_sub') }}`); accent colors unified pink (brands)/amber (categories) → violet system (tab active, icon tiles, primary buttons, focus rings, highlight rings, NEW badges, logo placeholders, Add Sub panel); container spacing `space-y-6` to match Products; new `brand_index_sub`/`category_index_sub` keys in all 3 locales, dead `brand_count`/`category_count` removed.
- **View toggles (193–194):** both pages now read `localStorage.getItem('admin_view_mode') || 'table'` + `@view-changed.window` (shared key with Products). Brands added a real card grid (`grid-cols-2 sm:3 lg:4 xl:5`); Categories reuses ONE hierarchy DOM with `:class` switching (divide-y ↔ card grid + section card borders) and an empty-state `col-span-full` fix.
- **Products-style tables (195):** Brands table now visible on all sizes with `overflow-x-auto` + `min-w-[640px]` (mobile stacked view removed), slug column always visible (was `hidden lg:table-cell`); Categories rewritten as a 6-column table (`min-w-[760px]`, horizontal scroll on mobile), clickable parent `<tr>` rows (`aria-expanded`), children `x-show`, Add Sub form extracted to shared `_add_sub_form.blade.php` partial (table colspan=6 + card view), highlight ids `cat-row-t-{id}` vs `cat-row-{id}`; new `category_col_icon`/`category_col_subs`/`category_col_actions` keys.
- **Add New Brand critical fix (196):** a stray `</div>` left in the list panel during the item-195 table rewrite closed the page `x-data` container early — the Add form and delete modal lived outside Alpine scope (`x-cloak` kept them permanently hidden) and card grid + pagination leaked into the Add tab. Root cause confirmed in-browser (`addInsidePageXdata: false`); fixed by removing the single stray `</div>` (div balance 44/44).
- **Browser E2E (preview, after fix):** Add tab → form shows + name auto-focus · table/toolbar/pagination hidden on Add tab · empty submit → "The name field is required." + stays on Add tab · create `QA TEST BRAND 20260808` → success flash + NEW badge + highlight · duplicate (lowercase variant) → localized error · delete modal (target name correct + Cancel auto-focus) → "Brand deleted successfully" + count restored to 61 · mobile horizontal scroll (table 640px / wrapper 301px) · no console errors.
- **Deploy #5 (197):** migration check first — production `migrate:status` shows both `add_unique_name` migrations `[2]/[3] Ran`, so **UI-only deploy** (`./deploy-datapos.sh`, no RUN_MIGRATIONS) → `DEPLOY_OK`. Production verified: home 200 · admin brands 302 (auth) · deployed blade identical to local (no stray div, 44/44) · `brand_index_sub` in 3 locales · **build manifest MD5 matches local**.
- **Commands:** brand/category/localization batch 78/78 (328) · `npm run build` ✓ · `view:cache` ✓ · `./deploy-datapos.sh` → `DEPLOY_OK` · SSH `migrate:status` + `md5sum manifest.json` + blade div-balance checks.

### Session 2026-08-09 — Pre-Existing Test Failures Resolved — First Ever 100% Full Suite (459/459)

The long-standing 8 "pre-existing, environment-dependent" failures are now fixed — full suite is **459 passed, 0 failed (2226 assertions)**, the first 100% green run for this repo.

- **QuickLoginVisibilityTest (×4, 500 errors):** root cause was NOT the quick-login feature — `LoginController::create()` calls `StorefrontSetting::first()`, but the test class never used `RefreshDatabase`, so the in-memory DB had no tables and every `/login` GET returned 500. Added `use RefreshDatabase;` — all 4 env-variant tests pass.
- **LoginBrandingAndSecurityTest (×2):** asserted a stale hardcoded Burmese footer string (`မှလှိုက်လှဲစွာ ကြိုဆိုပါသည်။`) from the pre-rebrand era; the footer now renders the localized `messages.trusted_by_us` key (`ကျွန်ုပ်တို့ ယုံကြည်ထောက်ပံ့ပါသည်`). Switched the asserts to `__('messages.trusted_by_us')` (locale-agnostic) and renamed the two tests to `test_login_page_has_datapos_branding_and_normal_form` / `test_register_page_has_datapos_branding_and_normal_form`.
- **MigrationSafetyTest (×1) + OrderRequestTest (×1):** asserted the English literal `Order Summary` but the app locale is `my` (`.env APP_LOCALE=my`), so the confirmation page renders `အော်ဒါ အချုပ်အခြာ`. Replaced with `__('messages.order_summary')`. OrderRequestTest additionally needed `$store->setting()->create([...viber_number, telegram_username...])` because the confirmation page only renders the Viber/Telegram buttons when the store setting exists (`@if ($viberUrl)` / `@if ($telegramUrl)`).
- **Code review:** pass — remaining English asserts in these files are raw HTML/JS/view-data (not translations), so they are safe under the `my` locale; `RefreshDatabase` per-method app refresh prevents the `config()` mutations from leaking into the key-absence test.
- **Commands:** 4-class batch 27/27 (157 assertions) · full suite **459/459 (2226)** — first 100% pass.
- **No app code changed** — test files only: `tests/Feature/QuickLoginVisibilityTest.php`, `tests/Feature/LoginBrandingAndSecurityTest.php`, `tests/Feature/MigrationSafetyTest.php`, `tests/Feature/OrderRequestTest.php`.

### Session 2026-08-09 (#2) — Admin List Filter Preservation + Clear-All-Filters + Deploy #6 (Items 198–201)

- **Filter-drop bug (198):** filtered admin list → Edit → update လုပ်ရင် query string (search/filter/sort/per_page) ပျောက်ပြီး plain list ပြန်ကျ — `update()`/`store()` redirects ၆ ခုက bare `/store/.../admin/...` ကို ပြန်လွှဲနေလို့။ Shared helper `app/Support/AdminListReturn.php` ထည့် — `capture()` (index မှာ relative URI ကို session သိမ်း) + `resolve()` (update/store redirect; `/store/` prefix + scheme-less ပဲ လက်ခံ) + `peek()` (edit/create back links အတွက် မဖျက်ဘဲ ဖတ်)။ Controllers 6 ခု (Brand, Category, Product, GlassFinder, Banner, Users) + edit views 7 ခု wired။ **Round-trip test** (`AdminFilterReturnRoundTripTest`, 2 tests): list capture → edit page **မဖျက်** → update redirect `?search=filtered` ပြန်ထိန်း ✓ · fallback လမ်း ✓ · 105 tests pass · build ✓
- **Peek/resolve consume bug (199, user report):** ပထမဗားရှင်းမှာ `resolve()` က session key ကို forget — edit (GET) မှာ back link အတွက် resolve ခေါ်တာနဲ့ key ပျက်ပြီး နောက် update မှာ filter ပျောက်။ `peek()` ခွဲထည့်ပြီး edit/create → peek (မဖျက်), update/store → resolve (ဖျက်) · browser verify: categories `?search=accessor` → edit → back link preserved ✓ · 108 tests pass
- **Clear-all-filters (200):** shared toolbar မှာ Clear all ရှိပြီးသား — gap က Users page (custom form သုံးထားလို့)။ Users index → shared toolbar migration (Role filter + pills + clear-all + result count) · toolbar `showPagination` prop (default true) + `$showPerPageSelector` rename — ကိုယ်ပိုင် pagination ရှိတဲ့ pages က per-page selector ဖျောက်။ **En-route 500 bug:** `$showPagination` ကို `@props` မှာ မထည့်ဘဲ @php block မှာ refer → `@props` ထဲထည့်ပြီး fix · 99 tests pass
- **Deploy #6 (201):** commits `a64c81c` + `e032eec` (pushed `677dcdf..e032eec`) · production `migrate:status` အကုန် `Ran` → **code-only** `./deploy-datapos.sh` → `DEPLOY_OK` · production verify: home 200 · admin brands 302 (auth) · server မှာ `AdminListReturn.php` + `peek` usage SSH grep နဲ့ အတည်ပြု ✓
- **Commands:** round-trip + admin batch 108/108 (449 assertions) · `npm run build` ✓ · `view:cache` ✓ · SSH `migrate:status` · `./deploy-datapos.sh` → `DEPLOY_OK`
- **Files:** `app/Support/AdminListReturn.php` (new), 6 admin controllers, 8 views (7 edit + users index + toolbar), `AdminFilterReturnRoundTripTest.php` (new), `UserFactory.php` (+phone)

---

### Session 2026-08-09 (#3) — Separate Brand Assets (Storefront/Admin/Favicon) + Deploy #7 (Items 202–205)

- **Feature (202):** one `logo_path` ကို ၈ နေရာမှာ သုံးနေတာကို **3 independent assets** ခွဲ — migration `2026_08_09_000001` က `storefront_logo_path`/`admin_logo_path`/`favicon_path` (nullable, `logo_path` မဖျက်) · model fallback: storefront `?: logo_path` · admin `?: storefront ?: logo_path` · favicon `?: admin ?: storefront ?: logo_path ?: favicon.ico` · controller safe sequencing (save DB ပြီးမှ old ဖျက် · DB fail → orphan cleanup · တခြား column ကသုံးနေတဲ့ path မဖျက်) · settings UI uploader card ၃ ခု (LIGHT/DARK previews, 48×48/32×32/16×16 chips) + reusable `Alpine.data('brandAssetUploader')` · consumers ၈ နေရာ update (header, sidebar, auth ×2, invoice, OG, favicon links) · **25 tests pass** · full suite **486 passed (2338 assertions)** · build ✓
- **Alpine bug (203):** `Alpine.data('brandAssetUploader')` ကို `Alpine.start()` **ပြီးမှ** register လုပ်ထားလို့ browser မှာ `ReferenceError: brandAssetUploader is not defined` + state props အကုန် undefined — block ကို `Alpine.start()` ရှေ့ရွှေ့ပြီး fix · rebuild နောက် console သန့် ✓ (unit/feature tests တွေက JS ကို မစစ်လို့ browser QA မှာပဲ ဖမ်းမိ)
- **Real-UI round-trip (204):** GD test images ၃ ပုံ (3:1 purple / square teal / 512 orange) → settings UI ကနေ တကယ်တင် → DB paths ၃ ခု save (store-logos/admin-logos/favicons သီးခြား) + storefront header 160×44 ✓ + admin sidebar square 38×38 ✓ + favicon/OG links ✓ · remove → file ပါ ဖျက် + fallback ပြန်ကျ ✓ · store ကို မူလအခြေအနေ ပြန်ထား + test files ဖျက်
- **Deploy #7 (205):** commit `7559b56` (14 files, +1051/−43) pushed `c07e83a..7559b56` · `RUN_MIGRATIONS=true ./deploy-datapos.sh` → migration `[4] Ran` → `DEPLOY_OK` · production verify: deployed component/model/app.js ရှိ ✓ · home 200 · storefront 200 · admin settings (unauth) 302 ✓
- **Commands:** brand assets + settings + auth batch 58/58 (239 assertions) · full suite 486/486 (2338 assertions) · `npm run build` ✓ · `RUN_MIGRATIONS=true ./deploy-datapos.sh` → `DEPLOY_OK` · SSH `migrate:status` → `[4] Ran`
- **Files:** migration (new), `StorefrontSetting.php`, `StoreSettingController.php`, `app.js`, `brand-asset.blade.php` (new), settings general/edit views, storefront/admin layouts, auth ×2, invoice, `OptimizeImages.php`, `AdminBrandAssetsTest.php` (new, 25 tests)

---

### Session 2026-08-09 (#4) — Admin Settings A11y 100/100 + List Pages Consistency + Deploy #8 (Items 206–208)

- **A11y (206):** Lighthouse a11y 83 → **100 mobile + 100 desktop** — `general.blade.php` label `for`/`id` ၄ ခု (store_name, tagline, default_language, opening_hours) · contrast fixes (gray-400→gray-500 dark:slate-400, brand-asset Light/Dark preview labels swap, size chips slate-600/dark:slate-300) · `nav-group` static aria-label → `:aria-label="sidebarCollapsed ? … : null"` (expanded မှာ visible text က accessible name — label-content-name-mismatch fix; collapsed icon-only မှာ aria-label ဆက်ပေး) + `addslashes()` · 85 tests pass · code review pass (utilities layer wins over components layer for color override)
- **Consistency (207, user report):** pages ပုံစံမတူ — Products/Brands/Categories က shared system သုံးနေပေမယ့် ကျန် ၇ မျက်နှာ gradient icon tiles + boxed stat cards သုံးနေ → **အကုန် Products style ပြောင်း**: `admin-page-header`/`admin-page-title`/`admin-page-sub` + `admin-hairline-grid` stats + `admin-panel` containers · gradient tiles 0 ကျန် · blog Add Post button ထိန်း · **🐛 ghost cell bug:** orders 5-cell hairline grid မှာ empty 6th track က gray block ပြ → `grid-cols-1 sm:grid-cols-5` fix (screenshot နဲ့ ဖမ်းမိ) · browser verify ၇ ခုလုံး ✓
- **Deploy #8 (208):** commits `42cefb7` (refactor, 7 files) + `fdb51f0` (a11y, 5 files) pushed `b16ccb3..fdb51f0` · code-only `./deploy-datapos.sh` → `DEPLOY_OK` · production verify: home 200 · admin orders 302 (auth) · deployed orders/reviews `admin-page-header` ✓ + gradient 0 ✓ + nav-group aria binding ✓
- **Commands:** full suite 486/486 (2338 assertions) · Lighthouse mobile+desktop a11y 100/100 (authenticated session cookie via Laravel encrypted-cookie tinker) · `npm run build` ✓ · `./deploy-datapos.sh` → `DEPLOY_OK` · SSH deployed-code grep
- **Files:** 7 index pages (orders, wholesale, users, glass_finder, banners, blog, reviews) · settings general/edit · brand-asset partial · nav-group · admin layout

---

### Session 2026-08-09 (#5) — Cloud Hue CSS Variable Pattern + Both-Source Verification

- **Refactor (209):** `favorites.blade.php` ရဲ့ brand cloud tiles inline gradient (server L55 PHP `hsl(...)`, Alpine L131 string-concat `:style`) တွေကို **CSS custom property pattern** နဲ့ ပြန်ရေး — `app.css` မှာ `.cloud-hue-bg` class (135deg gradient, `hsl(var(--cloud-hue, 210)…)`, hue angle ဖြစ်လို့ 360+ ကို CSS က သူ့အလိုလို wrap) · markup က `class="cloud-hue-bg"` + `style="--cloud-hue: {{ $cloudHue }}"` (server) / `:style="'--cloud-hue: ' + h"` (Alpine) · gradient logic CSS ထဲ တစ်နေရထဲ ပေါင်း — server/client shade ကွာနေတာ (1%) ပါ ပြန်ညှိ · Alpine `x-show`/`x-data` မပျက် (`:style` ကိုပဲ ကျဉ်းအောင်) · static inline `style="` count 7→4 (ကျန် ၄ ခုလုံး justified: richtext dynamic, favorites `--cloud-hue` var, catalog ×2 max+env) · scan: non-admin inline gradients **0 ကျန်** (browse pills/glass-finder chips က Tailwind gradient utilities — hue-based မဟုတ်၊ var pattern မလို)
- **Verification (210) — cloud hue both-source (auth browser):** test glass favorites ၃ ခု (Apple/​Honor/​Huawei) → `lh-cookie.php` encrypted auth cookie + `favoritesStore` localStorage → preview browser inject → `/account/favorites` render → **6 tiles (3 server-rendered + 3 Alpine/localStorage) အကုန် တူညီ** — computed `--cloud-hue` (38/154/190) က PHP `phpHue` နဲ့ အတိအကျတူ + computed `background-image` က rgb တန်ဖိုးအထိ **byte-identical** (same brand = identical gradient) · screenshot visual confirm (Apple warm yellow-green, Honor cyan-teal — brand အလိုက် ကွဲ) · **cleanup:** GlassFavorite ×3 ဖျက် (user 4 → 0) + preview localStorage ရှင်း — production data မထိ
- **Commands:** `npm run build` ✓ (`.cloud-hue-bg` built CSS ထဲ ဝင်) · `view:clear`/`view:cache` ✓ · favorites page 302 (auth-gated — expected) · preview browser computed-style check + screenshot
- **Files:** `resources/css/app.css` (`.cloud-hue-bg`), `resources/views/customer/account/favorites.blade.php` (L55 + L131)

---

### Session 2026-08-09 (#6) — Solid-Surface Cleanup — Glass-Card Removal + Glass Finder + Storefront Pages (Items 211–213)

- **Glass-card removal (211):** `glass-card` class **၃၁ နေရာ / ၁၂ ဖိုင်** → `bg-white dark:bg-slate-900` solid (auth login/register · customer favorites×3/index/orders/order_show · blog index×3/show×4 · catalog index×6 · glass_finder×2 · orders builder×3/confirmation×3) · special ၃ နေရာ glass-card CSS ရဲ့ border/shadow ကို အားကိုးထားလို့ explicit ထည့် (blog/index header border+shadow-2xl · blog/index empty border+shadow-xl · blog/show post card shadow-2xl) · **CSS ၂ ဖိုင်လုံး `.glass-card` + `.dark .glass-card` ဖျက်** — app.css (solid version) + admin.css (dead blur-22px version — admin views မသုံး) · welcome.blade.php:381 comment ပဲ ကျန် (markup မဟုတ်) · **Verified:** views glass-card 0 · built CSS 0 · `npm run build` ✓ · view:cache ✓ · home/products/glass-finder/blog/how-to-order/login/register/blog-detail အကုန် 200 ✓ · StorefrontNavigationContextTest 2 passed ✓
- **Glass Finder solid (212):** L80 toolbar + L156 results container (item 211 မှာ solid ဖြစ်ပြီးသား) · **result rows** L211 list card + L294 table container (`bg-white/80 dark:bg-slate-900/60` → solid) · **buttons/input ၅ ခု** L99 search input + L187/L196 view toggles + L251/L338 favorite buttons (`bg-white/90` → `bg-white dark:bg-slate-800`) · glass_finder မှာ translucent `bg-white/80-90` **0 ကျန်** ✓ · view:cache ✓ · 200 ✓ · Alpine `:class` active/inactive states မပျက်
- **Storefront pages solid (213):** translucent surfaces/buttons/inputs/chips အကုန် → solid — **catalog/index ×14** (inputs/selects/toggle/price/icon chips) · **catalog/show ×3** (variant pills + info panel) · **blog/index ×2** (category chips) · **orders/builder ×7** (qty stepper/inputs/contact buttons) · **auth login/register ×8** (inputs + phone prefix) · **customer index/order_show ×6** (cards `bg-white/60` → solid) + favorites placeholder · **welcome ×2** (flash-sale countdown chips) · **browse ×5** (mobile strip, desktop rail + hover/active → slate-50, panel, brands strip, chip dark state) · **product-card** favorite (`bg-white/95` → solid) · **product-image** placeholder · **layouts/storefront/app ×16** (header buttons ×6, sub-header /70→/95, nav rows, footer payment chips ×9) · **Kept (rule-allowed):** headers (`bg-white/95 backdrop-blur`) · overlays (dropdowns/drawers/mega-menu/floating CTA — `backdrop-blur-xl`) · hero-image elements (slider dots, hero CTA) · image badges (blog, cloud circles, product-image — `backdrop-blur-md`) · **Verified:** final scan non-overlay translucent **0** · view:cache ✓ · home/browse/how-to-order/products/glass-finder 200 ✓
- **Commands:** `npm run build` ✓ (item 211 မှာ — arbitrary class အသစ်မရှိလို့ 212/213 မှာ build မလို) · `view:clear`/`view:cache` ✓ (၃ ကြိမ်လုံး) · curl အကုန် 200 ✓ · grep scans: glass-card 0 · translucent non-overlay 0
- **Files:** 12 blade views + `resources/css/app.css` + `resources/css/admin.css` · welcome · browse/index · components (product-card, product-card-list, product-image, share-button) · layouts/storefront/app

---

### Session 2026-08-10 (#7) — Storefront Settings Feature + Exact Map Pin/Embed + Admin Restyle (Items 215–218)

- **Settings feature (215):** hard-coded footer payment badges (KPay/WavePay/CB Pay/MMQR/COD SVG) → **admin-managed structured methods** · migrations ၃ ခု (additive — map fields ×6 + `store_payment_methods` + `store_delivery_methods`) · `StorePaymentMethod` (masked account numbers) / `StoreDeliveryMethod` / `Store` relations / `StorefrontSetting` map helpers (mapUrl, mapDirectionsUrl, mapEmbedSrc) · `StoreSettingController` — map validation (https-only + Google-Maps-shaped, `javascript:` reject) + store-scoped payment/delivery CRUD (cross-store 404, `route('method')` binding fix, icon replace/remove/delete lifecycle, `hasMapInput` guard) · Admin contact → Exact Store Location card + live map preview · delivery section → structured method cards + **nested `<form>` bug fix** (delivery ကို main settings form ထဲ မထည့်တော့ — HTML nested forms မထောက်) · storefront footer/how-to-order/order-builder → structured methods with `show_account_details` gating + legacy textarea fallback · new `StorePaymentDeliveryAndMapTest` (24 tests/93 assertions) · **full suite 511 passed / 2438 assertions**
- **Map pin + embed (216):** owner share URL `maps.app.goo.gl/ugrW3JVwLzCmjQP89` resolve → **DataPOS Technology Training Center 17.0462098, 95.6441479** · production DB: map_enabled + URL + title + coords + embed · `mapEmbedSrc()` zoom 15→17 street-level · lazy iframe no-API-key · live iframe verified on production how-to-order ✓
- **Settings restyle (217):** settings shell → shared `admin-page-header`/title/sub full-width · sidebar white card → borderless sticky nav · section headings → `admin-section-title`/sub · delivery cards borderless `admin-panel` · browser verified General/Contact/Delivery
- **Admin list consistency (218):** backups/import_history/variant_presets → shared header + `admin-hairline-grid` stats + `admin-panel` tables + `min-h-11` buttons · orders/users/wholesale/glass_finder/banners/blog/reviews **already converted** (Deploy #8) · **all 13 admin list pages now `admin-page-header`** ✓ · `ImportHistoryManagementTest` updated to shared classes · build ✓ view:cache ✓ full suite 511 ✓ · ✅ item 218 deployed in Deploy #12 (2026-08-10)
- **Commands:** full suite 511/511 (2438 assertions) · `npm run build` ✓ · `view:clear`/`view:cache` ✓ · browser CRUD (QA KPay Test add → footer chip → cleanup) · production deploy #10 (RUN_MIGRATIONS, 3 migrations) + code-only deploys (z17, settings restyle) — live verified with authenticated production session
- **Files:** 3 migrations `2026_08_10_*` · `StorePaymentMethod`, `StoreDeliveryMethod`, `Store`, `StorefrontSetting` · `StoreSettingController` · `routes/web.php` · admin settings edit + 4 section partials · storefront footer/how-to-order/order-builder · `payment-method-icon` component · lang en/my/zh_CN (4 keys) · `StorePaymentDeliveryAndMapTest` · backups/import_history/variant_presets index · `ImportHistoryManagementTest`

---

### Session 2026-08-10 (#8) — Settings Localization + Footer Concise Summary + Deploy #12 (Items 219–220)

- **Settings localization (219):** settings sidebar nav + section headings + buttons were **hardcoded English** → translated via `__()` keys · `lang/my/messages.php` — 18 settings_* keys translated to Burmese · section partials (general/contact/delivery/how_to_order) headings + buttons localized · `en`/`zh_CN` keys synced · `settings_ordering_steps` (:count) placeholder removed (Alpine x-text live count) · `LocalizationKeysParityTest` (all 3 locales identical) ✓ · browser-verified Burmese locale: sidebar = `အထွေထွေ · ဆက်သွယ်ရန် · ပို့ဆောင်မှုနှင့် ငွေပေးချေမှု · မှာယူနည်း` ✓
- **Footer concise summary (220):** `layouts/storefront/app.blade.php` footer — stores without structured methods now show **concise note + "See details" link** instead of full `payment_info`/`delivery_info` text dump · stores WITH structured methods still show icon chips (unchanged) · How-to-Order page still shows **full details** (legacy text OR structured cards) · `footer_payment_note`/`footer_delivery_note`/`see_details` keys added to en/my/zh_CN · `StoreSettingsAndBrandingTest` updated (assertDontSee legacy dump, assertSee concise note) ✓
- **Deploy #12 (2026-08-10):** 3 commits pushed to origin/main (`df26e66` feat: structured payment/delivery + Google Maps, `df9fb59` refactor: settings restyle + localization + footer, `0c094b9` docs) · `RUN_MIGRATIONS=true ./deploy-datapos.sh` → `DEPLOY_OK` · live verified: Home/Products/How-to-Order all 200 · footer shows structured icon chips (production has methods) · How-to-Order shows full structured cards ✓
- **Commands:** full suite 511/511 (2441 assertions) · `npm run build` ✓ · `view:cache` ✓ · `LocalizationKeysParityTest` 4/4 ✓ · browser rendered Burmese settings nav + concise footer ✓ · production deploy #12 live ✓
- **Files:** `lang/en/messages.php`, `lang/my/messages.php`, `lang/zh_CN/messages.php` (18+ keys) · `resources/views/admin/settings/edit.blade.php` · `resources/views/admin/settings/sections/{general,contact,delivery,how_to_order}.blade.php` · `resources/views/layouts/storefront/app.blade.php` · `tests/Feature/StoreSettingsAndBrandingTest.php`


---

## UAT (User Acceptance Testing)

## Local User Acceptance Test (UAT) Checklist

**Project:** DataPOS
**Store A:** DataPOS — slug: `datapos-mobile` (25 products, 5 categories, 5 brands, 15 Glass Finder items, 4 orders)
**Store B:** UAT Test Store B — slug: `uat-store-b` (2 products, 1 category, 1 brand, 1 Glass Finder item, 1 order - multi-store isolation testing)
**Date:** _________

## Storefront URLs

The storefront uses **hybrid store-context routing**:
- Homepage, Products, and Glass Finder resolve store context using the `store_slug` query parameter.
- Product details, guest order submissions, order confirmation, wholesale application, and store admin dashboards use path-based routing (`/store/{store_slug}/...`).
- `X-Store-Slug` HTTP header is supported as an internal/test fallback.

| Page | Real Browser URL |
|---|---|
| Homepage | `http://localhost:8500/?store_slug=datapos-mobile` |
| Products | `http://localhost:8500/products?store_slug=datapos-mobile` |
| Product detail | `http://localhost:8500/store/datapos-mobile/product/{slug}` |
| Glass Finder | `http://localhost:8500/glass-finder?store_slug=datapos-mobile` |
| Order submit | `POST http://localhost:8500/store/datapos-mobile/orders` |
| Order confirmation | `http://localhost:8500/store/datapos-mobile/orders/{id}/confirmation?token={token}` |
| Admin dashboard | `http://localhost:8500/store/datapos-mobile/admin/dashboard` |
| Store B admin | `http://localhost:8500/store/uat-store-b/admin/dashboard` |

**Instructions:**
- ✅ = Pass
- ❌ = Fail (log defect in **Appendix A** below)
- 🖐 = Manual/browser check required (cannot be automated)
- 🔧 = Can be tested via automated test

---

## A. Guest Customer (No Login)

### Homepage & Navigation

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.1 | Open homepage `/?store_slug=datapos-mobile` | Homepage loads, store name and banners displayed | 🔧 | ☐ | | | | |
| A.2 | Mobile hamburger menu opens/closes | Navigation drawer appears on click | 🖐 | ☐ | | | | |
| A.3 | Bottom navigation bar visible on mobile | Home, Products, Glass Finder, Account icons visible | 🖐 | ☐ | | | | |
| A.4 | Myanmar text renders without overflow | Burmese characters display correctly | 🖐 | ☐ | | | | |
| A.5 | Dark/light theme toggle works | Theme switches on click, preference persists | 🖐 | ☐ | | | | |

### Product Catalog

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.6 | Click "Products" in navigation | Product listing page loads with 25 products for Store A | 🔧 | ☐ | | | | |
| A.7 | Product grid displays correctly | Desktop 5 cols, Tablet 3 cols, Mobile 2 cols | 🖐 | ☐ | | | | |
| A.8 | Featured products badge visible | Featured items show "Featured" label | 🖐 | ☐ | | | | |
| A.9 | Search by product name | Type "Samsung" → filtered results appear | 🔧 | ☐ | | | | |
| A.10 | Filter by category | Select "Tempered Glass" → only TG products shown | 🖐 | ☐ | | | | |
| A.11 | Filter by brand | Select "iPhone" → only iPhone products shown | 🖐 | ☐ | | | | |
| A.12 | Click product → detail page | Product detail loads with name, price, description, warranty | 🔧 | ☐ | | | | |
| A.13 | Retail price visible on detail | Price displays as "Ks 15,000" | 🔧 | ☐ | | | | |
| A.14 | Wholesale price hidden from guest | Wholesale price not shown | 🔧 | ☐ | | | | |
| A.15 | Out-of-stock badge | "Out of Stock" badge shown; order button disabled/hidden | 🔧 | ☐ | | | | |
| A.16 | Product with warranty text | Warranty information displayed in detail | 🖐 | ☐ | | | | |
| A.17 | Product with return policy | Return policy displayed in detail | 🖐 | ☐ | | | | |

### Glass Finder

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.18 | Open Glass Finder page | Search form loads with brand/model/code fields | 🔧 | ☐ | | | | |
| A.19 | Search by phone model | Type "Samsung Galaxy S24 Ultra" → compatible glasses shown | 🔧 | ☐ | | | | |
| A.20 | Search by glass code | Type "G-S24U-F" → matching records found | 🔧 | ☐ | | | | |
| A.21 | Search by brand | Select "iPhone" → all iPhone glasses shown | 🖐 | ☐ | | | | |
| A.22 | Glass compatibility row shows stock status | "In Stock" / "Out of Stock" label visible | 🔧 | ☐ | | | | |
| A.23 | Guest cannot favorite glass (redirects to login) | Clicking heart icon → redirect to login | 🔧 | ☐ | | | | |

### Order Request (Guest)

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.24 | Click "Order" on in-stock product | Order form appears with name, phone, address, contact channel | 🖐 | ☐ | | | | |
| A.25 | Name field validation | Submit empty → validation error shown | 🔧 | ☐ | | | | |
| A.26 | Phone field validation | Submit invalid phone → validation error shown | 🔧 | ☐ | | | | |
| A.27 | Address field validation (Required) | Submit without address → validation error shown, no order created | 🔧 | ☐ | | | | |
| A.28 | Select Viber channel | Order request submitted; redirects to confirmation page | 🔧 | ☐ | | | | |
| A.29 | Select Telegram channel | Order request submitted; redirects to confirmation page | 🔧 | ☐ | | | | |

### Order Confirmation (Guest)

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.30 | Confirmation page shows success banner | Green "Order Successful" banner displayed | 🔧 | ☐ | | | | |
| A.31 | Order summary shows correct data | Order number, name, phone, total, status all correct | 🔧 | ☐ | | | | |
| A.32 | Order items listed | Product name, quantity, subtotal displayed | 🔧 | ☐ | | | | |
| A.33 | Viber button visible with pre-filled message | Viber link opens with order details | 🖐 | ☐ | | | | |
| A.34 | Telegram button visible with pre-filled message | Telegram link opens with order details | 🖐 | ☐ | | | | |
| A.35 | Invalid confirmation token returns 404 | Modify token in URL → 404 error | 🔧 | ☐ | | | | |
| A.36 | No token returns 404 | Remove token parameter → 404 error | 🔧 | ☐ | | | | |
| A.37 | Order data saved before external link | Database contains order record | 🔧 | ☐ | | | | |
| A.38 | Cross-store token access blocked | Token from Store A used on Store B URL → 404 | 🔧 | ☐ | | | | |

### SEO

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.39 | GET `/robots.txt` returns 200 | Dynamic robots.txt with sitemap URL | 🔧 | ☐ | | | | |
| A.40 | GET `/sitemap.xml` returns 200 | XML sitemap with /, /products, /glass-finder | 🔧 | ☐ | | | | |

### Security

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.41 | Homepage returns security headers | X-Frame-Options, CSP, X-Content-Type-Options present | 🔧 | ☐ | | | | |

---

## B. Retail Customer (Logged In)

**Test credentials:** Phone `09100000006`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| B.1 | Register new account | Registration form accepts phone & password | 🔧 | ☐ | | | | |
| B.2 | Login with phone & password | Successful login → redirect to home | 🔧 | ☐ | | | | |
| B.3 | Login with wrong password | Error message displayed | 🔧 | ☐ | | | | |
| B.4 | Logout | Session cleared, redirect to home | 🔧 | ☐ | | | | |
| B.5 | Account page accessible | Account dashboard loads with name and phone | 🔧 | ☐ | | | | |
| B.6 | Favorites page accessible | Shows favorited products (if any) | 🔧 | ☐ | | | | |
| B.7 | Order history shows own orders | Order list shows ORD-UAT-004 and any new orders | 🔧 | ☐ | | | | |
| B.8 | Click order → order detail opens | Order detail page shows items and status | 🔧 | ☐ | | | | |
| B.9 | Own order confirmation accessible | Logged in → confirmation page loads without token | 🔧 | ☐ | | | | |
| B.10 | Cannot access another customer's order | Try another user's order URL → 403 | 🔧 | ☐ | | | | |
| B.11 | Retail price visible (same as guest) | Price shows retail price | 🔧 | ☐ | | | | |
| B.12 | Glass Finder — can favorite result | Heart icon toggles favorite (requires login) | 🔧 | ☐ | | | | |

---

## C. Wholesale Customer

### Pending Wholesale Customer

**Test credentials:** Phone `09100000005`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| C.1 | Login as pending wholesale user | Login succeeds | 🔧 | ☐ | | | | |
| C.2 | Browse products — retail price only | Wholesale price NOT visible | 🔧 | ☐ | | | | |
| C.3 | Order request uses retail pricing | Total calculated at retail price | 🔧 | ☐ | | | | |
| C.4 | Approval status displayed on account | "Wholesale application pending" message shown | 🖐 | ☐ | | | | |

### Approved Wholesale Customer

**Test credentials:** Phone `09100000004`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| C.5 | Login as approved wholesale user | Login succeeds | 🔧 | ☐ | | | | |
| C.6 | Browse products — wholesale price visible | Both prices shown; retail may be crossed out | 🔧 | ☐ | | | | |
| C.7 | Order request uses wholesale pricing | Total calculated at wholesale price | 🔧 | ☐ | | | | |
| C.8 | Retail-only promotions not accessible | N/A (no retail-only promos exist) | 🖐 | ☐ | | | | |

---

## D. Store Manager

**Test credentials:** Phone `09100000002`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| D.1 | Access `/store/datapos-mobile/admin/dashboard` | Dashboard loads with store statistics | 🔧 | ☐ | | | | |
| D.2 | Dashboard shows order count, product count | Statistics panels render with data | 🖐 | ☐ | | | | |
| D.3 | Product list page loads | All 25 products listed with pagination | 🔧 | ☐ | | | | |
| D.4 | Create new product | Product created; visible in listing | 🔧 | ☐ | | | | |
| D.5 | Edit existing product | Changes saved and displayed | 🔧 | ☐ | | | | |
| D.6 | Delete product | Product removed from listing | 🔧 | ☐ | | | | |
| D.7 | Toggle featured status | Product featured badge toggles | 🔧 | ☐ | | | | |
| D.8 | Upload product image | Image upload works, preview shows | 🖐 | ☐ | | | | |
| D.9 | Change stock status | Switch between in_stock / out_of_stock | 🔧 | ☐ | | | | |
| D.10 | Category list page | All 5 categories listed | 🔧 | ☐ | | | | |
| D.11 | Create category | Category created; assignable to products | 🔧 | ☐ | | | | |
| D.12 | Edit category | Category name/description updated | 🔧 | ☐ | | | | |
| D.13 | Delete category | Category removed | 🔧 | ☐ | | | | |
| D.14 | Brand list page | All 5 brands listed | 🔧 | ☐ | | | | |
| D.15 | Create brand | Brand created; assignable to products | 🔧 | ☐ | | | | |
| D.16 | Edit brand | Brand name updated | 🔧 | ☐ | | | | |
| D.17 | Delete brand | Brand removed | 🔧 | ☐ | | | | |
| D.18 | Product CSV import page loads | Import form with file upload visible | 🖐 | ☐ | | | | |
| D.19 | Valid CSV import creates products | Products from CSV appear in listing | 🔧 | ☐ | | | | |
| D.20 | Duplicate SKU in import is skipped | Second row with existing SKU not duplicated | 🔧 | ☐ | | | | |
| D.21 | Glass Finder admin page loads | Glass items list with CRUD | 🔧 | ☐ | | | | |
| D.22 | Create Glass Finder item | New glass record saves | 🔧 | ☐ | | | | |
| D.23 | Glass Finder CSV import | Records from CSV import correctly | 🔧 | ☐ | | | | |
| D.24 | Wholesale applications page loads | Lists pending + approved applications | 🔧 | ☐ | | | | |
| D.25 | Approve wholesale application | Status changes; user sees wholesale price | 🔧 | ☐ | | | | |
| D.26 | Reject wholesale application | Status changes; notes field available | 🔧 | ☐ | | | | |
| D.27 | Order list page loads | All 4 UAT orders visible | 🔧 | ☐ | | | | |
| D.28 | View order detail | Order items, customer info, status displayed | 🔧 | ☐ | | | | |
| D.29 | Update order status | Status changes correctly (pending_contact → confirmed → cancelled) | 🔧 | ☐ | | | | |
| D.30 | Store settings page loads | Settings form with store info | 🔧 | ☐ | | | | |
| D.31 | Update store settings | Changes saved and visible on storefront | 🖐 | ☐ | | | | |
| D.32 | Banner management page loads | Banner list with CRUD | 🔧 | ☐ | | | | |
| D.33 | Upload new banner | Banner appears on storefront home page | 🖐 | ☐ | | | | |
| D.34 | Product search in admin | Search by name or SKU → filtered results | 🔧 | ☐ | | | | |
| D.35 | Cross-store admin access blocked | `/store/uat-store-b/admin/dashboard` → 403 Forbidden | 🔧 | ☐ | | | | |

---

## E. Staff

**Test credentials:** Phone `09100000003`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| E.1 | Access admin dashboard | Dashboard loads | 🔧 | ☐ | | | | |
| E.2 | Manage products (same as manager) | Product CRUD available | 🔧 | ☐ | | | | |
| E.3 | Manage categories (same as manager) | Category CRUD available | 🔧 | ☐ | | | | |
| E.4 | Manage brands (same as manager) | Brand CRUD available | 🔧 | ☐ | | | | |
| E.5 | Manage orders (same as manager) | Order list + status update available | 🔧 | ☐ | | | | |
| E.6 | Manage Glass Finder (same as manager) | Glass Finder CRUD + import available | 🔧 | ☐ | | | | |
| E.7 | **Blocked:** Store settings page | `/admin/settings` → 403 | 🔧 | ☐ | | | | |
| E.8 | Platform owner dashboard blocked | `/admin/dashboard` → 403 (unless owner) | 🔧 | ☐ | | | | |
| E.9 | Store A staff cannot access Store B | `/store/uat-store-b/admin/dashboard` → 403 | 🔧 | ☐ | | | | |

---

## F. Platform Owner & Multi-Store Isolation

**Test credentials:** Phone `09100000001`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| F.1 | Access `/admin/dashboard` | Platform owner dashboard loads with store selection | 🔧 | ☐ | | | | |
| F.2 | Access any store dashboard | Platform owner can view both Store A and Store B admin | 🔧 | ☐ | | | | |
| F.3 | No store-level restrictions | Platform owner can switch stores seamlessly | 🔧 | ☐ | | | | |
| F.4 | Cross-store data isolation | Store A catalog (25 products) and Store B catalog (2 products) never leak into each other | 🔧 | ☐ | | | | |

---

## G. Device & Screen Testing

| # | Test | Device/Width | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| G.1 | Homepage layout | Desktop (1920px) | Full layout, no overflow | 🖐 | ☐ | | | | |
| G.2 | Homepage layout | Tablet (768px) | 3-col product grid, nav adapts | 🖐 | ☐ | | | | |
| G.3 | Homepage layout | Mobile 430px | 2-col product grid, bottom nav visible | 🖐 | ☐ | | | | |
| G.4 | Homepage layout | Mobile 390px | No horizontal scroll, form usable | 🖐 | ☐ | | | | |
| G.5 | Homepage layout | Mobile 360px | Smallest width, all content accessible | 🖐 | ☐ | | | | |
| G.6 | Product grid | Desktop | 5 columns | 🖐 | ☐ | | | | |
| G.7 | Product grid | Tablet | 3 columns | 🖐 | ☐ | | | | |
| G.8 | Product grid | Mobile | 2 columns | 🖐 | ☐ | | | | |
| G.9 | Order form at mobile width | 360-430px | Fields stack, Viber/Telegram buttons visible | 🖐 | ☐ | | | | |
| G.10 | Confirmation page at mobile width | 360-430px | Buttons visible, no overflow | 🖐 | ☐ | | | | |
| G.11 | Dark theme | All widths | Readable contrast, images visible | 🖐 | ☐ | | | | |
| G.12 | Myanmar text overflow | All widths | Burmese characters don't break layout | 🖐 | ☐ | | | | |

---

## H. Business Workflow Tests

### Order Lifecycle (Guest)

```
Guest browses → submits order → confirmation page → admin confirms → status updated
```

| # | Step | Expected | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| H.1 | Guest browses products, adds to cart | Product detail visible with Order button | 🔧 | ☐ | | | | |
| H.2 | Submits order request | Order saved in DB, redirects to confirmation | 🔧 | ☐ | | | | |
| H.3 | Confirmation page displays | Shows order summary, Viber/Telegram buttons | 🔧 | ☐ | | | | |
| H.4 | Admin sees pending order | Admin order list shows new order | 🔧 | ☐ | | | | |
| H.5 | Admin confirms order | Status changes from pending_contact to confirmed | 🔧 | ☐ | | | | |
| H.6 | Admin can cancel order | Status changes to cancelled | 🔧 | ☐ | | | | |

### Wholesale Lifecycle

```
Customer applies → pending → admin approves → wholesale price visible
```

| # | Step | Expected | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| H.7 | Customer submits wholesale application | Application appears in admin list | 🔧 | ☐ | | | | |
| H.8 | Application shows as pending | Status = pending | 🔧 | ☐ | | | | |
| H.9 | Admin approves application | Pivot role changes to wholesale_customer active | 🔧 | ☐ | | | | |
| H.10 | Customer logs in after approval | Wholesale price visible on products | 🔧 | ☐ | | | | |
| H.11 | Wholesale order uses correct price | Total = wholesale price × quantity | 🔧 | ☐ | | | | |

### Glass Finder Lifecycle

```
Customer searches → compatible results → favorites → order via chat
```

| # | Step | Expected | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| H.12 | Customer searches by model | Compatible glasses listed | 🔧 | ☐ | | | | |
| H.13 | Customer favorites a result | Favorite saved (requires login) | 🔧 | ☐ | | | | |
| H.14 | Order via chat button | Viber/Telegram link opens | 🖐 | ☐ | | | | |
| H.15 | Admin imports glass CSV records | Records added without duplicates | 🔧 | ☐ | | | | |

---

## I. Data Integrity & Multi-Store Isolation Checks

| # | Check | Expected | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| I.1 | Cross-store product isolation | Store A catalog shows 25 items; Store B catalog shows 2 items; zero leakage | 🔧 | ☐ | | | | |
| I.2 | Cross-store order isolation | Store A admin sees 4 orders; Store B admin sees 1 order; zero leakage | 🔧 | ☐ | | | | |
| I.3 | Cross-store category isolation | Categories are strictly store-scoped (Store A: 5, Store B: 1) | 🔧 | ☐ | | | | |
| I.4 | Cross-store brand isolation | Brands are strictly store-scoped (Store A: 5, Store B: 1) | 🔧 | ☐ | | | | |
| I.5 | Cross-customer order isolation | Customer A cannot view Customer B order confirmation (403) | 🔧 | ☐ | | | | |
| I.6 | Guest token cannot be enumerated | Invalid token → 404, not 200 | 🔧 | ☐ | | | | |
| I.7 | Prices calculated server-side | Price manipulation via request payload invalid | 🔧 | ☐ | | | | |
| I.8 | Wholesale price for approved only | Non-wholesale users see retail price only | 🔧 | ☐ | | | | |
| I.9 | Out-of-stock order blocked | Ordering out-of-stock item → validation error, no order created | 🔧 | ☐ | | | | |
| I.10 | Duplicate SKU protection | Same SKU in same store → rejected with error | 🔧 | ☐ | | | | |
| I.11 | Glass Finder duplicate handling | Duplicate (store, model, glass_code) row in CSV → skipped | 🔧 | ☐ | | | | |
| I.12 | Store manager isolation | Store A manager (`09100000002`) blocked from Store B admin (`uat-store-b`) with 403 | 🔧 | ☐ | | | | |
| I.13 | Store B manager isolation | Store B manager (`09100000007`) blocked from Store A admin (`datapos-mobile`) with 403 | 🔧 | ☐ | | | | |
| I.14 | Glass Finder record isolation | Store A Glass Finder items (15) and Store B items (1) never mix | 🔧 | ☐ | | | | |
| I.15 | Dashboard cache non-leakage | Statistics cached for Store A do not overwrite or appear on Store B dashboard | 🔧 | ☐ | | | | |

---

## J. Local Environment Checks

| # | Check | Expected | Actual Evidence | Result |
|---|---|---|---|---|
| J.1 | PHP version | 8.2.x | `PHP 8.2.12 (cli)` | PASS (☐) |
| J.2 | Required extensions | `mbstring`, `fileinfo`, `pdo_sqlite`, `pdo_mysql` loaded | All present in `php -m` | PASS (☐) |
| J.3 | GD extension | Optional warning / prerequisite | Not loaded in CLI `php -m`. Verified raw image uploads (JPEG, WebP, PNG) succeed via storage disk. Warning recorded for future image resizing. | WARNING (☐) |
| J.4 | SQLite connection | Database file writable, migrations active | `database.sqlite` loaded via SQLite connection | PASS (☐) |
| J.5 | Storage link exists | `public/storage` → `storage/app/public` | `<project-root>\public\storage` LINKED | PASS (☐) |
| J.6 | Storage directories writable | Cache, logs, sessions, views | All framework storage subdirectories writable | PASS (☐) |
| J.7 | APP_KEY set | 32-byte key generated | `APP_KEY` set (base64 string verified present) | PASS (☐) |
| J.8 | APP_DEBUG | `true` (local environment) | `Debug Mode: ENABLED` | PASS (☐) |
| J.9 | Cache driver | `file` or `database` | `database` cache driver active | PASS (☐) |
| J.10 | Queue driver | `sync` for local UAT | `database` driver active (0 pending jobs). Recommended: `QUEUE_CONNECTION=sync` for synchronous UAT execution. | PASS (☐) |
| J.11 | Mail dependency | Log driver | `MAIL_MAILER=log` (Viber/Telegram customer contact) | PASS (☐) |

---

## K. Manual UAT Execution Sessions

### Session 1: Guest and Mobile Storefront

**Focus:** Unauthenticated browsing, product discovery, Glass Finder, mobile responsiveness.
**Requires:** No login. Open browser dev tools for mobile widths.

| Step | What to do | Key tests to check |
|---|---|---|
| 1.1 | Open `/?store_slug=datapos-mobile` | A.1–A.5, G.1–G.5 |
| 1.2 | Browse to `/products?store_slug=datapos-mobile` | A.6–A.11, G.6–G.8 |
| 1.3 | Click a product → detail page | A.12–A.17 |
| 1.4 | Open `/glass-finder?store_slug=datapos-mobile` | A.18–A.23 |
| 1.5 | Submit a guest order (with address) | A.24–A.32, H.1–H.3 |
| 1.6 | Verify confirmation page renders | A.33–A.34, H.3 |
| 1.7 | Try invalid token → 404 | A.35–A.36 |
| 1.8 | Verify robots.txt and sitemap.xml | A.39–A.40 |
| 1.9 | Verify security headers in browser dev tools | A.41 |

**Session 1 estimated time:** 30 minutes

---

### Session 2: Retail and Wholesale Accounts

**Focus:** Registration, login, account management, price visibility, ordering.
**Requires:** Retail customer and wholesale user accounts.

| Step | What to do | Key tests to check |
|---|---|---|
| 2.1 | Register a new account | B.1 |
| 2.2 | Login as Ma Su (`09100000006` / `password`) | B.2–B.4 |
| 2.3 | Browse account, favorites, orders | B.5–B.10 |
| 2.4 | Verify retail price only | B.11 |
| 2.5 | Login as U Mya (`09100000005` / `password`) — pending wholesale | C.1–C.4 |
| 2.6 | Verify wholesale price hidden | C.2–C.3 |
| 2.7 | Login as Daw Aye (`09100000004` / `password`) — approved wholesale | C.5–C.8 |
| 2.8 | Verify wholesale price visible | C.6–C.7 |

**Session 2 estimated time:** 25 minutes

---

### Session 3: Manager and Staff Operations

**Focus:** Admin panels, CRUD operations, order management, CSV import.
**Requires:** Store Manager and Staff accounts.

| Step | What to do | Key tests to check |
|---|---|---|
| 3.1 | Login as Mg Hla (`09100000002` / `password`) | D.1–D.2 |
| 3.2 | Browse admin dashboard | D.1–D.2 |
| 3.3 | Create/edit/delete a product | D.3–D.9 |
| 3.4 | Create/edit/delete categories and brands | D.10–D.17 |
| 3.5 | View and update order status | D.27–D.29 |
| 3.6 | Browse wholesale applications, approve pending | D.24–D.26 |
| 3.7 | Browse Glass Finder admin | D.21–D.23 |
| 3.8 | Access store settings | D.30–D.31 |
| 3.9 | Login as Ko Kyaw (`09100000003` / `password`) | E.1–E.9 |
| 3.10 | Verify staff cannot access settings | E.7 |

**Session 3 estimated time:** 40 minutes

---

### Session 4: Platform Owner and Cross-Store Isolation

**Focus:** Store switching, cross-store data isolation.
**Requires:** Platform Owner and Store B Manager accounts.

| Step | What to do | Key tests to check |
|---|---|---|
| 4.1 | Login as Owner (`09100000001` / `password`) | F.1 |
| 4.2 | Access admin dashboard `/admin/dashboard` | F.1 |
| 4.3 | Access Store A admin | F.2 |
| 4.4 | Access Store B admin | F.2 |
| 4.5 | Login as U Ko Ko (`09100000007` / `password`) | E.1–E.9 |
| 4.6 | Verify Store B manager sees only Store B data | I.1–I.4, I.13 |
| 4.7 | Try to access Store A admin from Store B account | I.1, I.5, I.13 |
| 4.8 | Verify Store A order confirmation cannot be viewed by Store B | I.2, I.5–I.6 |

**Session 4 estimated time:** 20 minutes

---

### Session 5: Complete Business Workflows

**Focus:** End-to-end workflow verification for all 3 core business processes.
**Requires:** Guest browser, Manager login.

| Step | What to do | Key tests to check |
|---|---|---|
| 5.1 | **Order lifecycle:** Guest browses → submits order → confirmation page → manager confirms order | H.1–H.6 |
| 5.2 | **Wholesale lifecycle:** Customer applies → manager approves → wholesale price visible | H.7–H.11 |
| 5.3 | **Glass Finder lifecycle:** Search → favorite → order via chat | H.12–H.15 |
| 5.4 | **Data integrity:** Verify all I.1–I.15 pass | I.1–I.15 |

**Session 5 estimated time:** 20 minutes

---

### Results Summary

| Session | Tests Passed | Tests Failed | Not Tested | Tester Signature | Date |
|---|---|---|---|---|---|
| 1. Guest & Mobile | 0 | 0 | 25 | | |
| 2. Accounts | 0 | 0 | 12 | | |
| 3. Admin | 0 | 0 | 35 | | |
| 4. Isolation | 0 | 0 | 8 | | |
| 5. Workflows | 0 | 0 | 15 | | |

*Note: Manual UAT execution has NOT occurred yet. All manual checklist items remain NOT TESTED.*

---

## Appendix A — UAT Results & Defect Log

**Project:** DataPOS
**Store A:** DataPOS — slug: `datapos-mobile` (25 products, 5 categories, 5 brands, 15 Glass Finder items, 4 orders)
**Store B:** UAT Test Store B — slug: `uat-store-b` (2 products, 1 category, 1 brand, 1 Glass Finder item, 1 order)
**UAT Status:** MANUAL UAT READY (Manual UAT has NOT been executed yet)
**UAT Date:** _________
**Tester:** _________

### Severity Levels

| Severity | Definition | Action |
|---|---|---|
| **Blocker** | Cannot operate the business or exposes customer data | Fix immediately |
| **Critical** | Major workflow broken, no workaround | Fix during UAT |
| **Major** | Important feature works incorrectly | Log for prioritization |
| **Minor** | Cosmetic or low-impact issue | Log for backlog |
| **Warning** | Environment or deployment prerequisite | Document for production setup |

### Defect Log

#### Blocker

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

#### Critical

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

#### Major

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

#### Minor

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

#### Environment Warnings & Deployment Prerequisite Log

| ID | Category | Component | Description | Impact & Empirical Finding | Action Required |
|---|---|---|---|---|---|
| ENV-WARN-001 | PHP Extension | Image Processing (`gd`) | PHP `gd` extension is not loaded in current local CLI PHP (`php -m`). | **Verified Empirical Result:** Real image uploads (JPEG, WebP, PNG) for Product, Category, Brand Logo, and Home Banner succeed using standard file storage. `gd` is NOT required for raw file uploads, but IS required for future image resizing, thumbnail cropping, or `dimensions` validation rules. | Enable `extension=gd` in production `php.ini`. |

### Summary

| Category | Count | Notes |
|---|---|---|
| Blocker | 0 | None identified |
| Critical | 0 | None identified |
| Major | 0 | None (GD reclassified to Environment Warning after verified raw upload success) |
| Minor | 0 | None identified |
| **Total Software Defects** | **0** | Application software logic ready for manual UAT |
| Environment Warnings | 1 | `gd` extension recommended for production deployment |

### Sign-off

- [ ] All Blocker and Critical defects are resolved
- [ ] Core business workflows verified (order, wholesale, glass finder)
- [ ] Data integrity confirmed
- [ ] Device/screen testing completed on at least one mobile width
- [ ] UAT READY for production hosting purchase

**Tester Signature:** _________________ **Date:** _________
**Project Owner Approval:** _________________ **Date:** _________

---

## Appendix B — Local Device / LAN Test Note

### Project Path

```bat
D:\xmapp\htdocs\DataPOS
```

### Tonight — Stop the Laravel Server

Laravel server running terminal တွင်:

```bat
Ctrl + C
```

ပြီးလျှင် Command Prompt window ကိုပိတ်ပြီး PC ကို shutdown လုပ်နိုင်သည်။

### Tomorrow Morning — Start Again

1. **Open Command Prompt**

2. **Go to the project folder**

```bat
cd /d D:\xmapp\htdocs\DataPOS
```

3. **Check the current PC IPv4 address**

```bat
ipconfig
```

Expected IPv4:

```text
192.168.10.161
```

4. **Clear Laravel caches**

```bat
D:\xmapp\php\php.exe artisan optimize:clear
```

5. **Start the Laravel LAN server**

```bat
D:\xmapp\php\php.exe artisan serve --host=0.0.0.0 --port=8500
```

Command Prompt window ကို မပိတ်ရပါ။ Window ပိတ်လျှင် Laravel server ရပ်သွားမည်။

### Phone URLs

#### Storefront

```text
http://192.168.10.161:8500/store/datapos-mobile
```

#### Admin

```text
http://192.168.10.161:8500/store/datapos-mobile/admin/
```

#### Admin Login

- Local DB (SQLite) ထဲမှာ seeded/existing admin account ရှိပြီးသားဆိုရင် အဲဒါကို သုံးပါ။
- မရှိသေးဘူးဆိုရင် interactive prompt နဲ့ ဖန်တီးပါ (platform_owner role):

```bat
D:\xmapp\php\php.exe artisan production:create-admin
```

  (phone format: `09xxxxxxxxx`, password min 12 characters + uppercase + number + symbol)

### Phone Test Requirements

- Phone နှင့် PC ကို router တစ်လုံးတည်း၏ private network တွင်ချိတ်ထားပါ။
- Phone ကို Guest Wi-Fi မချိတ်ပါနှင့်။
- Mobile Data ကို ယာယီပိတ်ထားပါ။
- VPN / Proxy ကို ယာယီပိတ်ထားပါ။
- Router port forwarding မဖွင့်ပါနှင့်။

### If the PC IPv4 Address Changes

ဥပမာ IPv4 အသစ်က:

```text
192.168.10.165
```

`.env` ထဲက:

```env
APP_URL=http://192.168.10.161:8500
```

ကို:

```env
APP_URL=http://192.168.10.165:8500
```

အဖြစ်ပြောင်းပါ။

ပြီးလျှင်:

```bat
D:\xmapp\php\php.exe artisan optimize:clear
D:\xmapp\php\php.exe artisan serve --host=0.0.0.0 --port=8500
```

Phone URL ကိုလည်း IP အသစ်ဖြင့်ဖွင့်ပါ:

```text
http://192.168.10.165:8500/store/datapos-mobile
```

### Current Temporary LAN Configuration

```env
APP_URL=http://192.168.10.161:8500
FORCE_HTTPS=false
SESSION_SECURE_COOKIE=false
```

### Important Notes

- Current local database is SQLite, so XAMPP MySQL ကို start လုပ်ရန်မလိုပါ။
- Windows network profile ကို Private အဖြစ်သတ်မှတ်ထားသည်။
- Firewall rule name:

```text
DataPOS LAN Test TCP 8500
```

- Firewall rule သည် Private profile, TCP port 8500, subnet `192.168.10.0/24` အတွက်သာဖြစ်သည်။
- Tailwind/Vite class အသစ်တွေ ထည့်ပြီးရင် CSS ပြောင်းလဲမှု ဖုန်းမှာ မမြင်ရရင်:

```bat
cd /d D:\xmapp\htdocs\DataPOS
npm run build
```

ပြီးမှ `optimize:clear` + server restart လုပ်ပါ။
