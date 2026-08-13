# Deployment Runbook

## Deployment Checklist

1. Verify server requirements: PHP 8.2+, Composer 2, MySQL/MariaDB, required PHP extensions, writable storage, and HTTPS.
2. Upload code or check out the selected release commit.
3. Run `composer install --no-dev --prefer-dist --optimize-autoloader`.
4. Use the simplest MVP asset approach: build locally or in CI and deploy prebuilt `public/build`; if the server has Node 24+/npm, `npm ci && npm run build` is also acceptable.
5. Create the real production `.env` from `.env.example` or `docs/production-env-example.md`.
6. Run `php artisan key:generate` only for first-time setup.
7. Set writable permissions for `storage/` and `bootstrap/cache/`.
8. Create the production database and database user.
9. Back up the database before every deployment after launch.
10. Review pending migrations with `php artisan migrate:status`.
11. Run `php artisan migrate --force`. Never run `migrate:fresh` on production.
12. Run `php artisan db:seed --class=ProductionSeeder --force`.
13. Create the first admin with `php artisan production:create-admin --role=platform_owner`.
14. Create the first real store as `DataPOS` with canonical slug `datapos-mobile`.
15. Configure required store settings.
16. Create store staff only after the real store exists, for example `php artisan production:create-admin --role=store_manager --store=datapos-mobile`.
17. Run `php artisan storage:link`.
18. Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
19. Check `/store/datapos-mobile`, `/products?store_slug=datapos-mobile`, `/login`, and `/robots.txt`.
20. Verify `/login` does not show Quick Login or any default credentials.
21. Log in as the store manager and open `/store/datapos-mobile/admin/`.
22. Verify product images load from storage.
23. Smoke test product/glass import preview with a disposable file only.
24. Smoke test one order flow without altering real customer data.
25. Disable maintenance mode with `php artisan up` after verification.

Never run `php artisan migrate --seed` in production unless `DatabaseSeeder` has been re-audited for production-only behavior.

Never run UAT seeders in production. `UatSeeder` is only for `APP_ENV=local`, `APP_ENV=testing`, or `APP_ENV=uat` with `ALLOW_UAT_SEEDING=true`.

## Quick Deploy — deploy-datapos.sh (2026-08-08)

One-command code deploy to Hostinger (datapos.com) via SSH (tar + ssh pipe). Split layout:

- `laravel_app/` → full Laravel application (not a webroot)
- `public_html/` → webroot: contents of `public/` + storage symlink

```bash
# From repo root, AFTER `npm run build` (the script does NOT build)
./deploy-datapos.sh                          # code deploy (no migrations)
RUN_MIGRATIONS=true ./deploy-datapos.sh      # code deploy + pending migrations
```

Guarantees:
- Server `.env`, `vendor/`, `node_modules/`, storage uploads and caches are NEVER overwritten.
- Requires SSH key `~/.ssh/<hostinger-key>` (same key as acdcmm.com deployment).

Post-deploy cleanup (added 2026-08-08) runs automatically after upload:
1. Removes `public_html/favicon.svg` (1.4MB legacy file, replaced by favicon.ico + apple-touch-icon.png).
2. Removes `public_html/build/assets/*.ttf` (legacy fonts, replaced by WOFF2 subsets).
3. Removes any hashed file in `build/assets/` NOT referenced by the freshly-uploaded `manifest.json` (manifest-protected — never deletes assets the release uses).
   - Wrapped in `|| echo WARN` so a manifest parse failure never fails the deploy (`set -e` is active).

Verify after deploy:

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://datapos.com/
curl -s https://datapos.com/ | grep -o 'assets/app-[A-Za-z0-9_-]*\.css'   # storefront bundle
curl -s -o /dev/null -w "%{size_download}\n" https://datapos.com/favicon.ico  # expect ~11KB
# Stale assets should be gone:
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> \
  "ls /home/<HOSTINGER_USER>/domains/datapos.com/public_html/build/assets/ | grep -c ttf"  # expect 0
```

## Deploy History

### Deploy #4 (2026-08-08) — Brands CRUD refactor (safe delete + unique constraint + live search + uploader)

Deployed items 188–190 from `2026-08-02_FIXES.md`. Command:

```bash
npm run build
RUN_MIGRATIONS=true ./deploy-datapos.sh
```

Result: `DEPLOY_OK`; migration `2026_08_08_000001_add_unique_name_to_brands_table` ran (`[2] Ran`); server manifest `admin-gSQ42ZhT.css` matched local exactly; home 200; admin brands 302 (auth).

**New pre-migration safety step required for schema-changing deploys:** before running `RUN_MIGRATIONS=true`, audit production data against any new unique constraints (additive unique indexes fail the deploy if duplicates exist). For the brands `(store_id, name)` constraint the audit was done over SSH with tinker stdin:

```bash
# Duplicate normalized brand names per store (expect 0):
printf '%s\n' '$dups = DB::table("brands")->selectRaw("store_id, LOWER(TRIM(name)) as norm, COUNT(*) c")->groupBy("store_id","norm")->havingRaw("COUNT(*) > 1")->get(); echo "NAME_DUPS=" . $dups->count();' | \
  ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> \
  "cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app && php artisan tinker 2>/dev/null"
# Duplicate slugs per store (expect 0) + brand count (expect 61):
printf '%s\n' '$d = DB::table("brands")->selectRaw("store_id, slug, COUNT(*) c")->groupBy("store_id","slug")->havingRaw("COUNT(*) > 1")->get(); echo "SLUG_DUPS=" . $d->count() . " TOTAL=" . DB::table("brands")->count();' | \
  ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> \
  "cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app && php artisan tinker 2>/dev/null"
```

Audit result for Deploy #4: `NAME_DUPS=0`, `SLUG_DUPS=0`, `TOTAL=61` (matches local). Post-deploy, the constraint was verified live by inserting a duplicate inside a transaction (blocked with `1062 Duplicate entry`) then rolling back — brand count stayed 61, no test rows left.

### Deploy #5 (2026-08-08) — Products-style Brands/Categories UI + Add New Brand fix

Deployed items 192–196 from `2026-08-02_FIXES.md`: Brands/Categories aligned to the Products design system (`admin-page-header` + violet accents + shared toolbar), table/card view toggles (localStorage `admin_view_mode`), products-style tables with mobile horizontal scroll (Brands slug column always visible; Categories 6-column table + shared `_add_sub_form` partial), and the critical Add New Brand fix (a stray `</div>` in the list panel was closing the page `x-data` container early — Add form + delete modal were stuck hidden by `x-cloak`; removing the stray div restored the scope, div balance 44/44).

Command (UI-only — **no migrations**):

```bash
npm run build
./deploy-datapos.sh
```

Why no `RUN_MIGRATIONS`: `php artisan migrate:status` on production already showed both `2026_08_08_000001_add_unique_name_to_brands_table` (`[2] Ran`) and `2026_08_08_000002_add_unique_name_to_categories_table` (`[3] Ran`).

Result: `DEPLOY_OK`; home `200`; `/store/datapos-mobile/admin/brands` unauthenticated `302` (correct login redirect); deployed `brands/index.blade.php` identical to local (no stray `</div>`, div balance 44/44, `brand_index_sub` key present in my/en/zh_CN); `public_html/build/manifest.json` **MD5-matches** the local build.

Live verification URL: `https://www.datapos.com/store/datapos-mobile/admin/brands` — Add New Brand tab opens the form (name auto-focus), create → success flash + NEW highlight, duplicate name rejected (case-insensitive), delete modal (Cancel first focus, Escape/backdrop close, focus return), table/card view toggle persisted via localStorage, mobile horizontal scroll.

### Deploy #6 (2026-08-09) — Admin list filter preservation + Clear-all-filters (commits a64c81c, e032eec)

Deployed items 198-200 from `2026-08-02_FIXES.md`: the shared `AdminListReturn` helper (capture/peek/resolve) that preserves search/filter/sort/per-page across admin Edit round-trips (6 controllers, 7 edit views), the peek/resolve consume-bug fix, and the Users page migration to the shared toolbar with a Role filter + "Clear all" (toolbar `showPagination` prop). Code-only deploy — production `migrate:status` showed no pending migrations.

```bash
npm run build
./deploy-datapos.sh
```

Result: `DEPLOY_OK`; home `200`; `/store/datapos-mobile/admin/brands` unauthenticated `302` (correct login redirect); server-side grep confirmed `app/Support/AdminListReturn.php` present with `peek()`, and Product/Category controllers use `AdminListReturn::peek`. Live check: filtered Categories list → Edit → update keeps `?search=`.

### Deploy #7 (2026-08-09) — Separate brand assets (Storefront/Admin logos + favicon) (commit 7559b56)

Deployed items 202-204 from `2026-08-02_FIXES.md`: three independently configurable brand assets with backward-compatible fallback to the legacy `logo_path`. Migration `2026_08_09_000001_add_brand_asset_paths_to_storefront_settings_table` adds nullable `storefront_logo_path` / `admin_logo_path` / `favicon_path` (existing `logo_path` untouched). Fallback order: storefront `storefront_logo_path ?: logo_path`; admin `admin_logo_path ?: storefront_logo_path ?: logo_path`; favicon `favicon_path ?: admin_logo_path ?: storefront_logo_path ?: logo_path ?: favicon.ico`. Settings General tab gained three uploader cards (reusable `brand-asset.blade.php` + `Alpine.data('brandAssetUploader')`) with safe file sequencing in `StoreSettingController` (store new → save DB → delete replaced only after success; orphan cleanup on failure; never delete a path still referenced by another column). Consumers updated: storefront header, admin sidebar, auth pages, invoice, OG image, favicon links; `OptimizeImages` covers the new directories.

Schema-changing deploy — migration was audited first (additive nullable columns, no unique constraints, no data mutations):

```bash
npm run build
RUN_MIGRATIONS=true ./deploy-datapos.sh
```

Result: `DEPLOY_OK`; migration `2026_08_09_000001_add_brand_asset_paths_to_storefront_settings_table` ran (`[4] Ran` on `migrate:status`); deployed `resources/views/components/admin/settings/sections/brand-asset.blade.php` present; model helpers (`storefrontLogo`/`adminLogo`) and `brandAssetUploader` in app.js confirmed via SSH grep; home `200`, storefront `200`, admin settings unauthenticated `302` (correct auth redirect).

Live verification URL: `https://www.datapos.com/store/datapos-mobile/admin/settings` → General tab shows three uploader cards (Storefront Logo 3:1 / Admin Logo square / Favicon 512×512) with live LIGHT/DARK previews and per-field Remove; stores that only have the legacy `logo_path` keep working unchanged.

### Deploy #8 (2026-08-09) — Admin list-page consistency + Settings a11y (commits 42cefb7, fdb51f0)

Deployed items 206-207 from `2026-08-02_FIXES.md`: all admin list pages moved to the shared Products design system (`admin-page-header` / `admin-page-title` / `admin-page-sub` + `admin-hairline-grid` stats + `admin-panel` containers; gradient icon tiles removed — 0 left; ghost-cell fix on orders 5-cell hairline grid), plus Lighthouse a11y 83 → 100 mobile + 100 desktop on admin settings (label `for`/`id` pairs, contrast fixes, `nav-group` aria-label binding).

Code-only deploy (no migrations), commits `42cefb7` + `fdb51f0` pushed `b16ccb3..fdb51f0`:

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK`; production verification — home `200`, admin orders unauthenticated `302` (correct), deployed orders/reviews pages contain `admin-page-header` ✓, gradient tiles `0` ✓, `nav-group` `:aria-label="sidebarCollapsed` binding present ✓.

### Deploy #9 (2026-08-09) — Solid-surface cleanup + admin bundle split (working tree; commits 65a7098 + uncommitted admin split)

Deployed the storefront Solid-Glass design-system cleanup (items 211-213 from `2026-08-02_FIXES.md`): `glass-card` class removed site-wide (31 usages / 12 views → `bg-white dark:bg-slate-900` solid; `.glass-card` + `.dark .glass-card` deleted from `app.css` and the dead blur-22px copy in `admin.css`), Glass Finder result rows + buttons/input solidified, and translucent surfaces across catalog / blog / orders / auth / customer / welcome / browse / product-card / product-image / storefront layout converted to solid (headers `bg-white/95 backdrop-blur`, overlays `backdrop-blur-xl`, hero-image elements, and image badges kept glass per the design rules). Commit `65a7098` (22 files, +157/−152).

Note: this deploy uses the working tree, so the still-uncommitted admin bundle split also went up (items from the earlier admin performance turn): `resources/js/app.js` (brandAssetUploader moved out, −226), new `resources/js/app-admin.js`, `vite.config.js` input, and admin layout font-preloads + favicon dedup. The built manifest already contains `app-admin.js`, so the deployed admin pages resolve it correctly — but git is not yet in sync with what is live (commit the admin split separately to match).

Code-only deploy (no migrations):

```bash
npm run build
./deploy-datapos.sh
```

Result: `DEPLOY_OK`; deployed `app.css` hash `assets/app-iiwHoAe0.css` matches the local build exactly; production verification — home / products / glass-finder / blog / browse / how-to-order / login / register all `200`; `glass-card` `0` in served CSS and `0` in rendered HTML; solid favorite buttons `bg-white dark:bg-slate-800 text-rose-500` present ×40 in products HTML.

Live verification URL: `https://www.datapos.com/glass-finder?store_slug=datapos-mobile` → result cards, search toolbar and all controls render on solid `bg-white dark:bg-slate-900` / `bg-white dark:bg-slate-800` surfaces; no translucent non-overlay surfaces remain.

### Deploy #10 (2026-08-10) — Storefront Settings: structured payment/delivery methods + Google Maps (RUN_MIGRATIONS)

Deployed the Settings feature (items 215–216 from `2026-08-02_FIXES.md`): hard-coded footer payment badges replaced by admin-managed `store_payment_methods` / `store_delivery_methods` rows, Google Maps exact-location configuration (map fields on `storefront_settings`), and structured method cards in Admin Delivery & Payment + storefront footer / How-to-Order / Order Builder (legacy `payment_info`/`delivery_info` textareas kept as backward-compatible fallbacks).

**Migrations (3, additive — safe on live data):**

```bash
RUN_MIGRATIONS=true ./deploy-datapos.sh
```

Result: `DEPLOY_OK`; all 3 migrations ran on production — `add_map_fields_to_storefront_settings` (6 map columns), `create_store_payment_methods`, `create_store_delivery_methods`. Verified live: `Schema::hasTable('store_payment_methods')=yes`, `hasTable('store_delivery_methods')=yes`, `hasColumn('storefront_settings','google_maps_url')=yes`.

Production settings data set after deploy (owner-provided pin): `map_enabled=true`, `google_maps_url=https://maps.app.goo.gl/ugrW3JVwLzCmjQP89`, `map_title="DataPOS"`, then the resolved coordinates `map_latitude=17.0462098`, `map_longitude=95.6441479`, `map_embed_enabled=true` (pin resolved from the share URL redirect → DataPOS Technology Training Center). Production verification: all public pages 200, hard-coded KPay/WavePay/CB Pay/MMQR/COD badges `0`, footer + how-to-order link to the exact share URL, address-search fallback `0`, admin settings routes 302 (auth-gated, no 500), and the embedded map iframe renders at `q=17.0462098%2C95.6441479&z=17&output=embed` with `loading="lazy"`.

### Deploy #11 (2026-08-10) — Map embed zoom + Admin Settings clean full-width borderless restyle (code-only)

Code-only deploy (no migrations) shipping two follow-ups: (1) `StorefrontSetting::mapEmbedSrc()` zoom bumped 15 → 17 (street-level, matching the owner's 21z share-link view), and (2) the Settings pages restyle (item 217) — settings shell converted to the shared clean system (`admin-page-header` / `admin-page-title` / `admin-page-sub`, full-width `w-full space-y-6`, borderless sticky section nav, quiet `admin-section-title`/`admin-section-sub` headings, borderless `admin-panel` method cards).

```bash
npm run build
./deploy-datapos.sh
```

Result: `DEPLOY_OK`. Verified live with a real authenticated production session (session created server-side via `Auth::login` + `$store->save()`, then curl with the encrypted cookie): General, Contact, Delivery & Payment and How-to-Order all `200` and each renders `admin-page-header`/`admin-page-title` + the borderless section nav; Contact page shows the exact map pin data (share URL, `17.0462098`, `95.6441479`); Delivery page renders the borderless method-card surfaces; the only remaining `rounded-2xl border border-slate-200` on Delivery is the admin calculator display in the shared layout (pre-existing, unrelated). Test session rows cleaned up afterwards.

Live verification URL: `https://www.datapos.com/store/datapos-mobile/admin/settings` (and `/settings/contact`, `/settings/delivery`, `/settings/how-to-order`).

### Deploy #12 (2026-08-10) — Settings Localization + Footer Concise Summary + Admin Consistency (3 commits, RUN_MIGRATIONS)

Code + docs deploy shipping three commits: (1) `df26e66` — structured payment/delivery methods + Google Maps settings feature (models, migrations, controller, 24 tests), (2) `df9fb59` — settings restyle + localization + footer concise summary (18 translation keys, admin page consistency, footer rewrite), (3) `0c094b9` — docs update (items 215-220, session #7/#8, deploy #10/#11/#12).

```bash
RUN_MIGRATIONS=true ./deploy-datapos.sh
```

Result: `DEPLOY_OK`. All 3 migrations already ran on Deploy #10, so `Nothing to migrate` on this deploy (safe). Live verified: Home/Products/How-to-Order all 200; footer shows structured icon chips (production has KPay/WavePay/CB Pay methods); How-to-Order shows full structured method cards with icons and instructions; admin settings pages localized in Burmese (sidebar: `အထွေထွေ · ဆက်သွယ်ရန် · ပို့ဆောင်မှုနှင့် ငွေပေးချေမှု · မှာယူနည်း`).

Commits: `df26e66` `df9fb59` `0c094b9` (origin/main).

### Deploy #13 (2026-08-09) — Footer redesign + Share/Viber fallbacks + CSP + admin Footer settings (2 commits, code-only)

Code + docs deploy shipping two commits: (1) `54f03bc` — storefront footer redesign (clean light full-width band, hairline divider, mobile horizontal-scroll pills), footer Share action (Web Share API + per-app fallback), "Get Viber" install fallbacks (footer, chat popup, product CTAs, order confirmation), CSP hardening with nonces + auth error translations + out-of-stock/stock-badge fixes, admin Settings → Footer section with combined live preview, (2) `25daafd` — **production CSP fix**: Hostinger/LiteSpeed injects a bare `upgrade-insecure-requests` CSP at the vhost level that replaces the middleware's nonce policy; fixed with the acdcmm-proven directory-level `Header always set` CSP block in `public/.htaccess`. Static header can't carry the per-request nonce, so production `script-src` uses `'unsafe-inline' 'unsafe-eval'` (same as acdcmm.com); the nonce middleware still applies locally. Keep `public/.htaccess` and `app/Http/Middleware/SecurityHeaders.php` in sync.

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK` (no migrations in changeset — code-only). Live verified: home/product/how-to-order all **200**; production CSS bundle `app-CgSC7-Z4.css` **matches local exactly**; **full CSP header now served** (`curl -sI https://datapos.com/ | grep -i content-security-policy` shows the full policy with frame-src Google Maps + YouTube); Share action + Get Viber fallbacks present in home HTML; admin dashboard → 302 `/login` (auth correct). See changelog items 221-229.

Commits: `54f03bc` `25daafd` (origin/main).

### Deploy #14 (2026-08-09) — Product detail Description|Specifications tabs + admin storefront previews (1 commit, code-only)

Code deploy shipping `e34947b` — storefront product page gains an ARIA tabbed Description | Specifications section (hash deep-links, keyboard nav, no-JS fallback); Description renders the existing `description` through the new allow-list sanitizer `App\Support\SafeHtml`; Specifications is auto-built by the shared `App\Support\ProductSpecifications` presenter (brand, product/main category, SKU, warranty, stock in Burmese, grouped variant attributes, variant names/SKUs — no invented values, no prices). Admin product add/edit forms gain a read-only live Storefront Preview (description + auto specs) under the description editor. **No database/schema/import changes.**

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK`. Live verified: product pages 200 with tabs (productTabs wired, 2 tab roles, aria-controls, Burmese fallback on empty description, specs rows for Brand/Type/Main Category/Stock); new bundle `app-DPZ7mVRj.js` matches local; admin edit 302 → /login (auth correct); CSP header intact. See changelog item 230.

Commit: `e34947b` (origin/main).

### Deploy #15 (2026-08-09) — Admin product list details modal + Specifications CSV export (1 commit, code-only)

Code deploy shipping `111daed` — admin product list gains a per-row **View** action opening a Description | Specifications modal (server-rendered partial `admin/products/_details`, loaded via fetch, plain-JS tab toggle + keyboard nav + ARIA pattern); the partial uses the same `SafeHtml` sanitizer and `ProductSpecifications` presenter as the storefront, so staff see exactly what shoppers see. Toolbar gains a **Specs CSV** export (`/admin/products/export-specs`) streaming one row per product — SKU, name, brand, product/main category, warranty, stock (Burmese), variant names/SKUs, dynamic variant-attribute columns, and the sanitized description — honoring the per-page selector. Presenter refactored to expose `structuredFor()` as the single source of truth. **No database/schema/import changes.**

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK`. Live verified: `/admin/products` 302 → /login (auth correct), `/admin/products/export-specs` 302, `/admin/products/{id}/details` 302 (new routes registered, not 404); storefront + home 200. See changelog item 231.

Commit: `111daed` (origin/main).

### Deploy #16 (2026-08-09) — Critical fix: brand preselect on product edit + absent-field preservation (1 commit, code-only)

Code deploy shipping `09f13fd` — fixes a data-loss regression where the admin product edit page's Brand dropdown was not preselected (Alpine `x-model` initialized before the `x-for` options rendered), so an untouched save submitted an empty `brand_id` and cleared the persisted brand. The brand select now carries the same `x-init="$nextTick(() => $el.value = selectedBrand)"` fix the category selects already use, and `update()` preserves nullable relationship fields (`brand_id`, `category_id`, `warranty`, `return_policy`) when the request omits them while still honoring an explicit blank as an intentional clear. **No database/schema/import changes.**

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK` (steps 2/3 and 3/3 verified after output truncation: `public_html` upload + composer install; view/config/route caches cleared on server). Live verified: product 1538 storefront page 200 with Xiaomi brand + specs tab intact; production DB brand_id=60 / category_id=50 / warranty unchanged (no damage had occurred); admin edit route 302 → /login (auth correct). See changelog item 232.

Commit: `09f13fd` (origin/main).

### Deploy #17 (2026-08-09) — Product return policy disclosure + SEO meta (2 commits, code-only)

Code deploy shipping `6f515b8` + `119a18c` — the product detail page shows the existing `return_policy` as a collapsible, keyboard-operable disclosure under the warranty box (escaped plain text, collapsed by default, whitespace-only treated as empty); the meta description is resolved centrally by `App\Support\SeoMeta` (meta_description → plain-text description → generic product summary → store default) with Unicode-safe truncation and script/style stripping, emitted as `meta description` / `og:description` / `twitter:description` by the storefront layout. Product pages now emit a clean canonical + `og:url`, `robots index,follow`, `og:type=product`, and `og:image` falling back from the product image to the store share logo. Admin form upgrades return_policy to a textarea with a live storefront preview and adds meta-description helper text + 0/160 counter; `update()` preserves description/meta_description when omitted. **No database/schema/import changes.**

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK` (caches cleared on server). Live verified: production product 1538 gained the recommended Burmese return-policy text + a meta description (per the task's manual verification step); product page 200 with meta/og/twitter description tags, canonical clean URL, robots index,follow, og:type product, og:image + twitter:image = store share logo, return-policy disclosure present; an empty-policy product (5G Epoch) shows no disclosure; home 200. See changelog item 233.

Commits: `6f515b8`, `119a18c` (origin/main).

### Deploy #18 (2026-08-09) — Viber order message lost on iOS (1 commit, code-only)

Code deploy shipping `880450d` — the product page's Direct Order Viber button swapped its href to `viber://contact` on iOS, a route that cannot carry a draft, so the product name and order details never reached the chat (Android and Telegram were fine because they use the chat+draft route). `ContactLinkBuilder::viberIosContactUrl()` now accepts a draft and returns the chat route (with the leading `+` omitted, which is what makes it work on iOS) when one is given; draft-less contact links (footer / how-to-order) keep the contact route for private-number reliability. **No database/schema/import changes.**

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK` (caches cleared on server). Live verified: product 1538's Direct Order button now renders `data-ios-href="viber://chat?number=959790444128&draft=..."` with the full order draft (greeting, product name, SKU, price, link); footer contact links still use `viber://contact`. See changelog item 234.

Commit: `880450d` (origin/main).

### Deploy #19 (2026-08-09) — Share button widened to w-22 (1 commit, code-only)

Code deploy shipping `9fd71fd` — both product-page Share buttons (desktop action row + mobile sticky bar) widened from `w-12` (48px) to `w-22` (5.5rem = 88px) so the icon + "Share/မျှဝေရန်" label fit without cramping. Tailwind v4 generates `w-22` automatically. **No database/schema/import changes.**

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK`; live page carries `w-22` and the served CSS defines `.w-22`. See changelog item 235.

Commit: `9fd71fd` (origin/main).

### Deploy #20 (2026-08-09) — Share button icon-only on mobile (1 commit, code-only)

Code deploy shipping `811ee77` — the share-button component gained a `hide-label-on-mobile` prop; the product page passes it so the label span renders `hidden sm:inline` (icon-only below 640px, icon + label above). Other placements (footer, share menu) are untouched. **No database/schema/import changes.**

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK`; live span carries `hidden sm:inline`. See changelog item 236.

Commit: `811ee77` (origin/main).

### Deploy #21 (2026-08-09) — Brands & Categories Excel import/export (2 commits, code-only)

Code deploy shipping `cf0c1ed` (+ docs commit `c3cd585`, no re-deploy needed) — admin Brands and Categories pages now have toolbar **Import** / **Export** buttons. Export streams Excel-friendly CSV (UTF-8 BOM, formula-injection-safe cells): brands `name,slug`, categories `name,slug,parent,description,icon`. Import pages (`/admin/brands/import`, `/admin/categories/import`) reuse the existing preview → confirm session flow, `SpreadsheetImportReader`, `ImportHistory` logging, error-report CSVs, and downloadable XLSX templates. Categories import resolves parents by name/slug in a two-pass run (sub-categories may reference mains anywhere in the file) with the same classification in preview and import; duplicates are matched by slug then name and skipped or updated per the chosen strategy. **No database/schema/import structure changes** (only additive `import_histories.type` values `brands` / `categories`).

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK`. Live verified: all 6 new admin routes auth-gated (302 → login) and registered; local dev preview exercised export + full upload→preview→confirm import end-to-end (brand created, history recorded). See changelog item 237.

Commits: `cf0c1ed`, `c3cd585` (origin/main).

### Deploy #22 (2026-08-09) — Products export merged into one (Specs CSV removed) (1 commit, code-only)

Code deploy shipping `b0bee27` — the products admin previously showed two export buttons (Export + Specs CSV); the Specs CSV columns (Burmese stock label, sanitized description, human-readable variant names/SKUs, dynamic variant-attribute columns) are now merged into the single products export, with the original 18 round-trip column names untouched so export → edit → re-import still works. The `export-specs` route, `exportSpecs()` method, and toolbar `specsExportUrl` prop/button were removed. Variant-attribute labels that would normalize to a fixed column name are skipped to keep the importer from seeing duplicate keys. **No database/schema/import changes.**

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK`. Live verified: `/admin/products/export` 302 (auth-gated), `/admin/products/export-specs` returns 405 (removed GET route now falls through to the `{product}` PUT route — harmless, the button is gone), home 200. See changelog item 238.

Commit: `b0bee27` (origin/main).


### Deploy #24 (2026-08-09) — Variant-aware Viber/Telegram Direct Order links (1 commit, code-only)

Code deploy shipping `838183a` — the product page's **Direct Order** Viber/Telegram buttons now rebuild their message client-side when the shopper selects a variant: the Alpine component computes the order draft from the selected variant (name, SKU, variant price) plus the product URL, so a "Back Cover - Blue" order reaches the shop with the color and variant SKU instead of just the base product. The server-rendered links stay as the no-JS fallback, and the existing `data-ios-href` iOS swap keeps carrying the draft. No database/schema changes.

```bash
./deploy-datapos.sh
```

Result: `DEPLOY_OK`. Live verified: product page 200 with `:href="viberHref"`, `:data-ios-href="viberIosHref"`, `:href="telegramHref"`, and `viberNumber: '959790444128'` / `telegramUser: 'dataposmobile'` embedded; local preview confirmed clicking the Blue variant flips both the Viber draft and Telegram text to "OPPO A3S Back Cover - Blue / SKU OP-A3S-BC-BLU" with the variant price. Full suite green (573 tests / 2775 assertions), incl. new `test_direct_order_links_rebuild_with_selected_variant`. See changelog item 242.

Commit: `838183a` (origin/main).

## Production Seeding And First Admin

Production-safe seed command:

```bash
php artisan db:seed --class=ProductionSeeder --force
```

Forbidden production seed commands:

```bash
php artisan db:seed --class=UatSeeder --force
php artisan migrate --seed
php artisan migrate:fresh --seed
```

First admin creation:

```bash
php artisan production:create-admin --role=platform_owner
```

First store manager creation after the real store exists:

```bash
php artisan production:create-admin --role=store_manager --store=datapos-mobile
```

The command asks for the phone number and password interactively. Do not use documented or shared default credentials. The command rejects weak passwords, duplicate phones, missing store assignment for store roles, and never prints the plaintext password.

First real store creation:

```bash
php artisan production:create-store --name="DataPOS" --slug=datapos-mobile
```

Optional store settings can be provided during bootstrap or updated later from `/store/datapos-mobile/admin/settings`:

```bash
php artisan production:create-store --name="DataPOS" --slug=datapos-mobile --phone=<PHONE> --viber=<PHONE> --telegram=<TELEGRAM_USERNAME> --address="<ADDRESS>" --opening-hours="09:00 AM To 05:00 PM" --delivery-info="Myanmar countrywide delivery" --payment-info="KPay, CBPay, Bank" --default-language=my
```

Use the Telegram username without the leading `@` when passing it to the command.

The current schema supports store name, slug, active flag, public phone, Viber number, Telegram username, address, opening hours, delivery information, payment information, logo, and default language (`my` or `en`). Currency, timezone, supported languages beyond the default, pickup flags, notification routing, wholesale feature flags, and theme presets are not schema-backed settings in this release.

The canonical production store slug is `datapos-mobile` — the same slug used throughout local development, so seeded UAT data maps cleanly to the real store.

## Production Data Onboarding Order

1. Confirm store settings and contact links for `DataPOS`.
2. Create brands and categories.
3. Create or import products. Product import can safely skip duplicate SKUs by default, or update only when the operator selects the update behavior.
4. Upload product images and select primary images.
5. Import Glass Finder data. Duplicate Glass Finder rows are reported/skipped according to the import preview/confirm workflow.
6. Add home banners.
7. Create wholesale accounts only for real approved customers.
8. Run a final order smoke test.

## Launch Verification Checklist

- Production owner can log in and see the platform store selector.
- Store manager can log in and is redirected to `/store/datapos-mobile/admin/dashboard`.
- Store manager is assigned only to `datapos-mobile`.
- `/store/datapos-mobile` storefront is visible.
- `/store/datapos-mobile/admin/` redirects to `/store/datapos-mobile/admin/dashboard`.
- Store settings, Viber link, and Telegram link use real business contacts.
- Retail and wholesale pricing visibility is correct.
- Order submission and admin status update work.
- Import history is visible after any import.
- Backup exists before launch.
- SSL is active.
- `APP_DEBUG=false`.
- `SHOW_QUICK_LOGIN=false`.
- `ALLOW_UAT_SEEDING=false`.
- No UAT/demo users, products, orders, or Glass Finder rows exist.

Verification checklist:

```sql
select phone, name, role from users where phone in ('REVIEW_KNOWN_UAT_PHONES');
select slug, name from stores where slug like '%uat%' or slug like '%demo%';
select count(*) from products;
select count(*) from glass_finder_items;
```

Confirm production `.env` contains:

```dotenv
APP_ENV=production
APP_DEBUG=false
ALLOW_UAT_SEEDING=false
SHOW_QUICK_LOGIN=false
```

If demo data was seeded accidentally, stop writes, enable maintenance mode, back up the database, audit rows by explicit known seed markers and IDs, then remove only reviewed rows. Do not delete by phone-number pattern alone.

## Storefront Feature Re-Apply (2026-08-02 Session)

The 2026-08-02 development session added 75 items: catalog filters, blog + blog admin, reviews, product variants + sale scheduling, header/nav/product-card polish, admin calculator, blog block editor, split settings pages, and production blog content. The full per-item log with hosting notes for every item is at `2026-08-02_FIXES.md` in the repo root (committed, deploys with the code) and is reproduced verbatim in Appendix A below.

This release is code-only: the release commit contains all 75 items, so no manual file copying is required. The runtime steps are:

1. After the normal deployment checklist, review and run `php artisan migrate --force` — new tables/columns include: `categories.icon`, `posts`, `reviews`, `products.old_price` + `product_variants` (+ `attributes`), blog fields on `posts`, `storefront_settings.chat_channels` / `chat_button_icon_path` / how-to-order fields, `variant_presets.category_family`.
2. `php artisan storage:link` — blog featured images and chat icons live in `storage/app/public`.
3. Create the real store first (see "Production Seeding And First Admin"), then seed production content:

   ```bash
   php artisan db:seed --class=ProductionSeeder --force
   php artisan db:seed --class=HowToOrderContentSeeder --force
   ```

   - `ProductionSeeder` calls `RefreshDataPOSBlogContentSeeder` (6 production blog posts + featured images copied from `database/seeders/assets/blog/`). It skips with a warning if the `datapos-mobile` store does not exist yet — run it after store creation.
   - `HowToOrderContentSeeder` fills default "How to Order" content only when empty (admin edits are never overwritten).
   - **Never run `DemoCatalogSeeder` or `BlogSeeder` on production** — demo data (UAT rule above).
4. Build assets — the session introduced new Tailwind v4 classes (`scrollbar-none`, `group-hover:pointer-events-auto`, `bottom-[100px]`, `w-4.5`, `active:scale-[0.99]`, rose/amber gradients, `decoration-rose-500`/`decoration-2`): deploy prebuilt `public/build` or run `npm ci && npm run build`.
5. `php artisan view:clear` (blade-only changes) then `php artisan optimize:clear` and rebuild config/route/view caches.

Post-deploy spot checks: `/store/datapos-mobile` home, `/products` (sort + sidebar filters), product detail (variant selector, sale badge, sticky mobile action bar, reviews, related products), `/blog` + one post (share, prev/next, JSON-LD), `/how-to-order`, admin dashboard (revenue + monthly chart), the 4 settings pages, blog editor (block editor), product form (variant presets + WYSIWYG), admin header (calculator, View Commerce, Reload), printable invoice.

Gotchas from the session (deliberate — do not "fix"):

- Product-card overlay uses a hybrid touch reveal and must be tested on a REAL phone; devtools touch emulation is misleading (a pure-CSS hover variant looked fine in emulation but did not appear on real devices — see log items 29–31). Tap the card image in the middle, not the top badge row.
- Blog JSON-LD uses `@@context` (Blade escape) — reverting it to `@context` causes a 500 (log item 46).
- Settings section routes: the controller must read `$request->route('section')`, not a method parameter — Laravel DI injects `{store_slug}` positionally and 404s (log items 40, 44).
- `CatalogController@show` must keep passing `$related` and `$hideFloatingFabs` (log items 32, 34).
- `/sitemap.xml` URLs follow `APP_URL` — set it to the production domain (log item 38).
- The `how_to_order` rewrite and blog images need `storage:link` before they render.

## Image Auto-Compression (2026-08-04)

Storefront load on slow connections is the priority: every upload is now auto-downscaled + re-encoded server-side (PHP GD, no extra packages), and existing heavy files were converted in one pass (78 MB → ~9 MB locally).

- **`app/Support/ImageOptimizer.php`** — `store()` replaces every `->store(..., 'public')` image upload (products 1600px, variants 1200, categories 1200, brands 800, banners 1600, blog 1400, chat icons 512). Opaque photos are re-encoded to **WebP** when it saves bytes; small transparent PNGs (≤512px, e.g. icons/logos) keep PNG for alpha. Files ≤300 KB and within bounds are left untouched.
- **Existing files:** one-time `php artisan images:optimize` walks `storage/app/public/{products,products/variants,categories,brands,banners,blog,chat-icons}`, converts heavy files, and rewrites every DB path whose extension changed (products, product_images, product_variants, categories, brands, home_banners, posts, storefront_settings logo/chat-button/chat-channels). **Re-running is safe and idempotent.**
- **Lazy loading:** product cards, category tiles, and non-first hero slides use `loading="lazy" decoding="async"`; the first hero slide stays eager (LCP).
- **Hosting:** require PHP **GD with WebP** (standard PHP 8.2+ build has it). `php artisan storage:link` must exist for images to render. No migration, no `npm run build` needed for this change. Verify on Hostinger: `php -m | grep -i gd`, then upload a large image in admin and confirm the stored file is `.webp` and smaller.

## Rollback Checklist

1. Enable maintenance mode with `php artisan down`.
2. Restore the previous code release.
3. If migrations changed data/schema and the deployment failed, restore the pre-deploy database backup.
4. Restore storage files if uploads or import files were changed during deployment.
5. Run `php artisan optimize:clear`.
6. Rebuild caches with `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
7. If a data-maintenance command was run, use its recorded execution ID with the matching rollback command.
8. Recheck login, catalog, admin dashboard, images, and order submission.
9. Disable maintenance mode only after smoke checks pass.

## Failure-Specific Notes

- Failed code deployment: switch back to the prior release directory or Git commit, then rebuild caches.
- Failed migration: stop, restore the database backup, and do not rerun partial migrations without review.
- Broken frontend build: restore the previous `public/build` artifact or rebuild assets from the release lockfiles.
- Storage-link failure: remove the broken link, rerun `php artisan storage:link`, and verify uploaded images.
- Incorrect environment variables: correct `.env`, run `php artisan optimize:clear`, then rebuild caches.
- Failed data maintenance: use the logged execution ID and dry-run rollback before applying rollback.

---
## Appendix A — 2026-08-02 Feature/Fix Log (verbatim from 2026-08-02_FIXES.md)

## 📝 DataPOS — 2026-08-02 ပြင်ဆင်မှတ်တမ်း

ဒီဖိုင်က **Hosting ပေါ်က Website မှာ အတိုင်းအတိ ပြန်ပြင်ဆင်ဖို့** အတွက် ရည်ရွယ်ထားပါတယ်။
ဒီနေ့ မင်းနဲ့ငါ လုပ်ခဲ့တဲ့ အပြောင်းအလဲတွေအားလုံးကို နံပါတ်စဉ်အလိုက် ချရေးထားပါတယ်။

---

### 📁 Project အချက်အလက်

| အချက် | အသေးစိတ် |
|---|---|
| Project နေရာ | `D:\xmapp\htdocs\data_ecommerce` |
| Framework | Laravel 12.64 (PHP 8.2, SQLite) |
| Store slug | `datapos-mobile` |
| Server run ရန် | `php artisan serve --host=0.0.0.0 --port=8500` |
| ဖုန်းနဲ့စမ်းရန် | `http://192.168.10.161:8500/?store_slug=datapos-mobile` |
| Product list စာမျက်နှာ | `http://192.168.10.161:8500/products?store_slug=datapos-mobile` |

> ⚠️ Port 8000 က အယင် Botble project (အဟောင်း) — ဒီ project က 8500 ပါ။

---

### 🔁 Hosting မှာ ပြန်လုပ်ဖို့ လုပ်ငန်းစဉ် (အကျဉ်းချုပ်)

1. အောက်မှာဖော်ပြထားတဲ့ ဖိုင်တွေကို hosting က project ထဲ မိတ္တူကူးထည့်ပါ (သို့) ကုဒ်တွေ ပြန်ရိုက်ပါ
2. `php artisan migrate` — အသစ်ထပ်ဆောင်းထားတဲ့ column/table တွေ (item 2, 3)
3. `php artisan db:seed --class=DemoCatalogSeeder` — demo categories + products (item 5)
4. `npm install` ပြီးရင် `npm run build` — ⚠️ Tailwind v4 class အသစ်တွေရှိလို့ build မလုပ်ရင် style တွေ ပျက်နေမယ်
5. `php artisan view:clear`
6. စစ်ဆေးရန်: home + `/products?store_slug=datapos-mobile` (ကွန်ပြူတာ + ဖုန်း နှစ်ခုလုံး)

---

### 🔧 ပြင်ဆင်ချက်များ (နံပါတ်စဉ် ၁–၃၆)

#### ၁. Product Card — Hover ပုံပြောင်း + Hover Action Bar
- **ဘာလုပ်ထားလဲ:** Product card ပေါ်မှာ mouse ထောက်ရင် ပုံ ၂ ပုံ (2nd gallery image) ပြောင်းပြတယ်။ Hover လုပ်ရင် 🛒 ထည့်မယ် + 👁️ အသေးစိတ် ခလုပ်တွေ ပေါ်လာတယ် (Shopwise ပုံစံ)။
- **ဖိုင်:** `resources/views/components/product-card.blade.php`
- ⚠️ နောက်ပိုင်း item 24 မှာ ခလုပ်တွေ အောက်ဘောင်တန်းကို ပြောင်းပြီး item 31 မှာ နောက်ဆုံးပုံစံ ဖြစ်သွားတယ် — **နောက်ဆုံးအနေအထားကို item 31 မှာ ကြည့်ပါ**။

#### ၂. Category Menu (Header Dropdown) — icon column
- **ဘာလုပ်ထားလဲ:** `categories` table ထဲကို `icon` column (emoji ဖြစ်တဲ့ 📱💻🔌…) ထပ်ဖြည့်ထားတယ်။ Categories ၈ ခု ပြန်ဖြည့်ထားတယ် (🔌🔗🎧🔘🔋⚡📦…)
- **ဖိုင်:** migration `2026_08_02_000001_add_icon_to_categories_table.php`
- **Hosting မှာ:** `php artisan migrate` လုပ်ရင် column ရောက်လာမယ်
- **Header ပုံစံ:** `resources/views/layouts/storefront/app.blade.php` — desktop မှာ dropdown (icon+name+count, `/products?category_id=X` ကို သွား), mobile menu ထဲမှာလည်း categories ရှိတယ်

#### ၃. Blog စာမျက်နှာ (အသစ်)
- **ဘာလုပ်ထားလဲ:** Blog စာမျက်နှာ ၂ မျက်နှာ ထပ်ထည့်ထားတယ် — `/blog` (စာရင်း, ၉ ခုစီ စာမျက်နှာခွဲ) + `/blog/{slug}` (အသေးစိတ် + ဆက်စပ်ဆောင်းပါး ၃ ခု)။ Myanmar ဆောင်းပါး ၃ ခု demo ထည့်ထားတယ်။
- **ဖိုင်:** migration `2026_08_02_000002_create_posts_table.php`, `app/Models/Post.php`, `app/Http/Controllers/Storefront/BlogController.php`, routes (web.php), views `storefront/blog/{index,show}.blade.php`, `database/seeders/BlogSeeder.php`
- **Hosting မှာ:** `php artisan migrate` + BlogSeeder run; ပုံတွေ `storage/app/public/blog/` ထဲ ကူးထည့်

#### ၄. `/products` Catalog (Product List) စာမျက်နှာ — Linn IT Mart ပုံစံ
- **ဘာလုပ်ထားလဲ:** `/products` စာမျက်နှာကို shop.linn.com.mm လိုမျိုး အပြည့်ပြန်ဆောက်ထားတယ်:
  - ခေါင်းစဉ် + ပစ္စည်းအရေအတွက် (ဘယ်) + **sort dropdown** (ညာ): newest / price_low_high / price_high_low
  - ဘယ်ဘက် sticky sidebar: Categories (icon+name+count, active မီးမောင်း), Brands (count ပါ), **Price min/max + Apply ခလုတ်**, Stock status, Clear filters
  - Mobile: ဘေးမှာ horizontal filter toolbar (compact)
  - Product grid: desktop 4-col / tablet 3 / mobile 2 + empty state + pagination (`withQueryString()` — filter မပျောက်)
- **ဖိုင်:** `app/Http/Controllers/Storefront/CatalogController.php` (`index()` method), `resources/views/storefront/catalog/index.blade.php`
- **Filter URL ဥပမာ:** `/products?store_slug=datapos-mobile&category_id=5&sort=price_low_high&min_price=1000000&max_price=5000000`

#### ၅. Demo Data Seeder — ဖုန်း + ကွန်ပြူတာ (အသစ်)
- **ဘာလုပ်ထားလဲ:** `DemoCatalogSeeder` (idempotent — ထပ်ခါ run ရင်လည်း ကောင်းတယ်):
  - Categories: **ဖုန်း** (slug `phone`, 📱) + **ကွန်ပြူတာ** (slug `computer`, 💻)
  - Brands: Apple, Samsung, Xiaomi, ASUS, Dell, Lenovo
  - Products: ဖုန်း ၆ မျိုး (iPhone 15 Pro Max, S24 Ultra, Xiaomi 14T, POCO X6 Pro, iPhone 13, Galaxy A55) + ကွန်ပြူတာ ၆ မျိုး (MacBook Air M3, Dell Inspiron, ASUS Vivobook, Lenovo IdeaPad, MacBook Pro M4, ASUS TUF) — မြန်မာကျပ်နဲ့ လက်တွေ့ဈေး, stock in/out ရော, အချို့ featured
- **ဖိုင်:** `database/seeders/DemoCatalogSeeder.php`
- **Run ရန်:** `php artisan db:seed --class=DemoCatalogSeeder`

#### ၆. Home — "လူကြိုက်များသော အမျိုးအစားများ" (Most Popular Category) Grid
- **ဘာလုပ်ထားလဲ:** Home ရဲ့ Section 2 (horizontal chips အဟောင်း) အစား **Category card grid** ထည့်ထားတယ် — ပစ္စည်းအများဆုံး category ၆ ခု: gradient tile ထဲ icon ကြီး (📱/💻) + အမည် + product count။ နှိပ်ရင် `/products?category_id=X` ကို သွား။ Desktop 3-col / mobile 2-col။
- **ဖိုင်:** `resources/views/welcome.blade.php` (Section 2)
- ⚠️ နောက်ပိုင်း item 10 မှာ အားလုံး (desktop အပါအဝင်) **အလျားလိုက် scroll row** ဖြစ်သွားတယ် — နောက်ဆုံးကို item 10 မှာ ကြည့်ပါ။

#### ၇. Mobile Category Button — Icon-Only
- **ဘာလုပ်ထားလဲ:** Mobile မှာ မင်းပေးထားတဲ့ ရည်ညွှန်းပုံအတိုင်း (pill ၃ တန်း icon) — Home Section 2 ရဲ့ ညာဘက်မှာ icon-only ခလုပ် (w-12 h-12 gradient tile, inline SVG pill ၃ တန်း, aria-label "Categories")။ နှိပ်ရင် dropdown ပွင့်တယ်။
- **ဖိုင်:** `resources/views/welcome.blade.php`
- ⚠️ item 10 မှာ ဒီခလုပ်ကိုပါ ဖယ်ပြီး scroll row တစ်ခုတည်းဖြစ်သွားတယ် — နောက်ဆုံးကို item 10 ကြည့်ပါ။

#### ၈. Header Categories ခလုပ် — Icon-Only
- **ဘာလုပ်ထားလဲ:** Header ရဲ့ "🗂️ Categories" text ခလုပ် အစား icon-only (h-9 w-9, ဖေါ်ဗရိတ်/cart ခလုပ်တွေနဲ့ တူတူ border/glass style) — ဒီ pill-၃တန်း SVG icon နဲ့။ Desktop hover / mobile နှိပ်ရင် dropdown ပွင့်တယ် (icon+name+count + view all)။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`
- **Test မှတ်ချက်:** Desktop မှာ dropdown က hover-open — mouseenter ပြီး click လုပ်ရင် ပိတ်သွားတတ်တယ် (double-click သို့ reload ပြီးမှ hover)။

#### ၉. Header ၂ ထပ်ပုံစံ (2-row layout)
- **ဘာလုပ်ထားလဲ:** Header ကို ၂ ထပ်ပြန်ဆောက်ထားတယ်:
  - **ထပ် ၁:** [category icon — ဘယ်ဘက်အစွန်] [logo+name — အလယ်မှာ absolute ဗဟို] [action ခလုပ်တွေ — ညာ]
  - **ထပ် ၂** (lg မှသာ): search form + nav links (Home/Products/Glass Finder/How to Order/Blog) — အလယ်မှာ
  - Mobile = ထပ် ၁ ပဲ။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`
- **စစ်ဆေးပြီး:** Desktop 1440px logo ဗဟို 712.5 = header ဗဟို; mobile 390px logo ဗဟို 187 = 187။

#### ၁၀. Home Category — Single-Row Horizontal Scroll (နောက်ဆုံး ပုံစံ)
- **ဘာလုပ်ထားလဲ:** "Most Popular Category" ကို **အားလုံးအပေါ် single-row horizontal scroll** ဖြစ်အောင်ပြောင်းထားတယ် (desktop grid + mobile icon-dropdown split ကို ဖျက်လိုက်တယ်): `flex overflow-x-auto scrollbar-none` + drag-to-scroll Alpine (isDown/startX/scrollLeft)။ Card w-40 sm:w-44 (gradient icon tile + name + count)။ Category အကုန် (count စဉ်) — take(6) ဖယ်ထား။
- **ဖိုင်:** `resources/views/welcome.blade.php`
- **စစ်ဆေးပြီး:** Mobile 390px scroll ရတယ် (343 vs 848); desktop 1440px ၅ ခုလုံး ဝင်တယ် (1216 = 1216)။
- **Hosting မှာ:** `npm run build` လိုတယ် (scrollbar-none class)

#### ၁၁. Mobile Header Fix
- **ဘာလုပ်ထားလဲ:**
  - (a) Hamburger menu ထဲက "Mobile Category Menu" section ဖယ်လိုက်တယ် (category က header icon ခလုပ် + home scroll row မှာရှိပြီးသားမို့)
  - (b) Logo နဲ့ hamburger ထပ်နေတဲ့ bug ပြင်ထားတယ် — ❤️ favorites နဲ့ 🌙 dark-mode toggle တွေကို md အောက်မှာ ဝှက်လိုက်တယ် (mobile right = hamburger + language + login)။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`

#### ၁၂. Header Search Icon + Mobile Menu Utilities
- **ဘာလုပ်ထားလဲ:**
  - (a) Search ကို compact 🔍 icon ခလုပ် ဖြစ်အောင်ပြောင်းပြီး နှိပ်ရင် header အောက်မှာ full-width search overlay bar ပွင့်တယ် (autofocus)။ Desktop ရဲ့ Row-2 full search bar ဖျက်လိုက်တယ် (Row 2 = nav links ပဲ)။
  - (b) Mobile hamburger menu ထဲမှာ language switcher + dark/light toggle ထည့်ထားတယ်။
  - (c) Search panel နဲ့ mobile menu တစ်ခုပွင့်ရင် တစ်ခုပိတ်တယ် (mutual close)။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`
- **စစ်ဆေးပြီး:** search → `/products?store_slug=...&search=iphone` အလုပ်လုပ်တယ်။

#### ၁၃. Mobile Menu — ⚙️ Settings Dropdown (တစ်ခုတည်းပေါင်း)
- **ဘာလုပ်ထားလဲ:** Language + Theme လိုင်း ၂ ခု အစား compact **"⚙️ Settings"** dropdown တစ်ခု: (1) Language row — မြန်မာ/English/简体中文 pill ၃ ခု (POST form → locale.update, active = bg-sky-600), (2) divider + Theme toggle row (🌙/☀️ + Dark/Light label)။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`

#### ၁၄. Mobile Header မှာ Shop Name ပြခြင်း
- **ဘာလုပ်ထားလဲ:** Logo link က mobile မှာ flex-col — icon (h-9 w-9) + name (text-xs, max-w-[6rem]) အောက်မှာ။ Desktop lg+ = icon + name (text-lg) + tagline အလျားလိုက်၊ ဗဟိုကျပါတယ် (713=713)။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`
- ⚠️ item 15–16 မှာ ထပ်ပြောင်းပြီး **item 19** မှာ နောက်ဆုံးပုံစံ (ပိုကြီး + ပိုလှ) ဖြစ်သွားတယ် — နောက်ဆုံးကို item 19 ကြည့်ပါ။

#### ၁၅. Mobile Brand Strip (Header အောက်မှာ) — ⛔ ဖျက်ပြီးသား
- Logo ကို header အောက်မှာ strip နဲ့ပြ — **ဒီနည်းကို မင်းမကြိုက်လို့ item 16 မှာ ဖျက်ပြီး logo ကို header ထဲ ပြန်ထည့်လိုက်တယ်။ အခု မလိုတော့ဘူး။**

#### ၁၆. Header Logo Placement — Final (Header ထဲမှာပဲ)
- **ဘာလုပ်ထားလဲ:** Brand strip ဖျက်။ Logo (icon h-9 w-9 sm:h-12 + name text-xs + tagline text-[10px]) ကို **device အကုန်မှာ header အလယ်မှာ** ပြန်ထည့် (absolute left-1/2 top-1/2)။ Mobile မှာ နေရာရအောင် header ရဲ့ **Login/Account ခလုပ်တွေကို `hidden md:flex`** လုပ်လိုက်တယ် — login ကို mobile ☰ menu ထဲကို gradient ခလုပ်နဲ့ ထည့်ထားတယ်။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`

#### ၁၇. Breadcrumb ဖျက်ခြင်း (/products မှာ)
- **ဘာလုပ်ထားလဲ:** `/products` catalog စာမျက်နှာရဲ့ Home › Products breadcrumb (`<nav aria-label="Breadcrumb">`) ကို ဖျက်လိုက်တယ် (မင်း "clear this section" ဆိုလို့)။ တခြားအရာအကုန် (title+count, sort, sidebar, grid, pagination) ရှိနေတယ်။
- **ဖိုင်:** `resources/views/storefront/catalog/index.blade.php`

#### ၁၈. Mobile Settings Dropdown — Polished
- **ဘာလုပ်ထားလဲ:** ⚙️ ခလုပ်မှာ လက်ရှိအခြေအနေ ပြမယ် (ဥပမာ "EN · Light") + gradient ⚙️ tile + rotating chevron။ Dropdown: rounded-2xl shadow-2xl backdrop-blur, section labels ("🌐 Choose language", "🎨 Toggle theme")။ Language pills grid-cols-3 + active မှာ ✓ (sky-600)။ Theme row မှာ iOS-style switch (role=switch, knob translate-x-5, aria-checked)။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`

#### ၁၉. Header Logo — ပိုကြီး + Polished (နောက်ဆုံး)
- **ဘာလုပ်ထားလဲ:** icon h-9→h-11 (sm:h-12), name text-xs→text-sm (sm:text-base), tagline text-[10px]→text-[11px], gap-2→gap-2.5, max-w-[6rem]→max-w-[7rem]။ Polish: icon မှာ sky ring (ring-sky-500/10 → hover ring-sky-500/25), logo link hover:scale-[1.02] active:scale-[0.98]။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`

#### ၂၀. Desktop Header — Top Utility Bar
- **ဘာလုပ်ထားလဲ:** Sticky header အပေါ်မှာ `hidden md:block` top bar — ဘယ်: 📞 phone + 💬 Viber + ✈️ Telegram + 🕒 opening_hours (lg မှသာ); ညာ: guest 🔑 Login + 📝 Register (သို့) auth 👤 name + 🚪 Logout (POST route('logout'))။ Login/account ကို header row-1 ကနေ ဖယ်လိုက်တယ် (top bar md+ နဲ့ mobile ☰ menu မှာပဲ)။ Row 1 right = search + lang + fav + cart + dark။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`

#### ၂၁. Desktop Categories ခလုပ် — Label + Chevron ထည့်
- **ဘာလုပ်ထားလဲ:** Category ခလုပ် `lg:h-auto lg:w-auto px-0 lg:px-3` — mobile = icon-only (w-9 = 36px); desktop = pill + "Categories" text + rotating chevron (button w=134)။ Logo ဗဟိုပဲ ရှိနေတယ် (713=713)။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`

#### ၂၂. Category Menu — Brand Flyout (Hover ရင် Brand တွေ ပေါ်)
- **ဘာလုပ်ထားလဲ:** Category dropdown row တစ်ခုချင်းစီ မှာ chevron (›) — ဒီ category မှာ brand တွေရှိရင် hover လုပ်ရင် ဘေးမှာ flyout (w-52, rounded-2xl, shadow-2xl) ပွင့်ပြီး brand list (avatar + name + count) + "👀 View all" ပြတယ်။ `$navBrandsByCategory` (category → brand → [brand, count]) ကို layout @php မှာ တွက်ထားတယ်။ Dropdown ကနေ `overflow-y-auto` ဖယ်လိုက်လို့ flyout မညှပ်တော့ဘူး။
- **Mobile bug ပါပြင်:** `@click="catOpen = !catOpen"` → `@click="catOpen = true"` (mouseenter + click နဲ့ toggle ပိတ်သွားတတ်လို့) — @click.outside / @mouseleave နဲ့ ပိတ်။
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php`
- **စစ်ဆေးပြီး:** Desktop hover ကွန်ပြူတာ → Apple(2)/ASUS(2)/Dell(1)/Lenovo(1); click Apple → `/products?category_id=12&brand_id=6`။

#### ၂၃. Header Logo — ပုံတစ်ပုံတည်း (Name ပါဝင်ပြီးသား)
- **ဘာလုပ်ထားလဲ:** Header logo ကို **ပုံတစ်ပုံတည်း** ဖြစ်အောင်လုပ်ထားတယ် — `store-logos/header-logo.png` (514x184, transparent, PHP GD + FreeType နဲ့ ထုတ်ထား: icon 160px + "DataPOS" Arial Bold 54px + "Mobile Sale Service" 24px #0284c7)။ `storefront_settings.logo_path` → `store-logos/header-logo.png`။ Header မှာ text spans ဖယ်ပြီး image-only (`w-32 sm:w-40 lg:w-48`, h-auto) — fallback icon+text သိမ်းထားတယ်။
- **ဖိုင်:** `storage/app/public/store-logos/header-logo.png` + `resources/views/layouts/storefront/app.blade.php` + DB (`storefront_settings.logo_path`)
- **Hosting မှာ:** ဒီ PNG ကို storage ထဲ ကူးထည့် + DB logo_path ပြောင်း (admin Store Settings → Logo ကနေလည်း တင်လို့ရတယ်)
- **ပြောင်းချင်ရင်:** GD script နည်းအတိုင်း regenerate လုပ်ပါ

#### ၂၄. Product Card — Bottom Row ခလုပ် ၃ ခု (စုစည်း)
- **ဘာလုပ်ထားလဲ:** ပုံပေါ်က hover action bar (🛒+👁️ duplicate) ဖယ်ပြီး အောက်ဘောင်မှာ round button ၃ ခု (w-10 h-10 rounded-full white/95 border shadow-md): 🛒 Add to Order (qty badge), 👁️ Details, Share (dropdown)။
- **ဖိုင်:** `resources/views/components/product-card.blade.php`
- ⚠️ **item 25 မှာ ပုံအလယ်ကို ပြောင်း** — နောက်ဆုံးကို item 31 ကြည့်ပါ။

#### ၂၅. Product Card — Overlay ပုံအလယ်မှာ (အရောင်နဲ့)
- **ဘာလုပ်ထားလဲ:** Bottom row ဖျက်။ ခလုပ် ၃ ခုကို **ပုံအလယ်မှာ** overlay (`absolute inset-0 z-10 flex items-center justify-center gap-2`) — mobile = အမြဲမြင်ရ; desktop = hover မှသာ။ Gradient circle တွေ: 🛒 from-sky-500 to-blue-600 (badge), 👁️ from-violet-600 to-fuchsia-500, 🔗 Share from-amber-500 to-orange-600 (dropdown အပေါ်ဘက်ပွင့်)။ Empty overlay က ပုံ link ကို မထိအောင် pointer-events-auto လုပ်ထား။
- **ဖိုင်:** `resources/views/components/product-card.blade.php`
- ⚠️ item 26 → 31 အထိ ထပ်ပြောင်းသွားတယ် — **နောက်ဆုံးကို item 31 ကြည့်ပါ**။

#### ၂၆. Product Card — Overlay Tap-to-Show (မူလ)
- **ဘာလုပ်ထားလဲ:** Overlay ကို `translate-y-6` (ခလုပ်တွေ နည်းနည်းအောက်ဆင်း) + default `opacity-0` — mobile မှာ ပုံကို နှိပ်မှပေါ်; desktop က group-hover။ Alpine `actionsOpen` state + broadcast event (`card-actions-opened`)။
- **ဖိုင်:** `resources/views/components/product-card.blade.php`
- ⚠️ item 28/29/30/31 မှာ နောက်ဆုံးပုံစံ ဖြစ်သွားတယ် — item 31 ကြည့်ပါ။

#### ၂၇. ဖုန်းနဲ့ စမ်းသပ်ဖို့ Link (မိမိဖုန်း)
- **အချက်အလက်:** PC LAN IP = 192.168.10.161; server 8500 က 0.0.0.0 (LAN ရနိုင်)။ **ဖုန်းက Wi-Fi တူတူရှိရမယ်** (mobile data မဟုတ်ဘူး)။ Home: `http://192.168.10.161:8500/?store_slug=datapos-mobile` | Products: `http://192.168.10.161:8500/products?store_slug=datapos-mobile`။
- **မရရင်:** Windows Firewall က PHP ကို block လုပ်နေတာ (allow PHP)။ "database is locked" ပြရင် 8577 test server ကို ပိတ် (SQLite DB တူမို့)။

#### ၂၈. Product Card — တစ်ခုတည်းပဲ ပွင့်စေခြင်း (Auto-Close)
- **ဘာလုပ်ထားလဲ:** Card တစ်ခုဖွင့်ရင် နောက်တစ်ခု အော်တို ပိတ်အောင် — card တစ်ခုချင်းစီမှာ unique `cardKey` + root မှာ `@card-actions-opened.window` listener (key မတူရင် actionsOpen=false)။ Image tap မှာ broadcast လုပ်ထား။
- **ဖိုင်:** `resources/views/components/product-card.blade.php`
- **Test မှတ်ချက်:** ပုံထိပ်စွန်း (badge ❤️ row) က pointer-events-auto — **ပုံအလယ်ကို ထိရမယ်**။
- ⚠️ item 30 မှာ CSS hover နဲ့ အစားထိုးပြီး item 31 မှာ hybrid ဖြစ်သွားတယ် — item 31 ကြည့်ပါ။

#### ၂၉. Product Card — Touch (ထိရုံ) နဲ့ပေါ်စေခြင်း (မူလ)
- **ဘာလုပ်ထားလဲ:** `@touchstart` (120ms timer → open) + `@touchmove` (cancel+close) + `@click` fallback + overlay close မှာ `Date.now() - openedAt > 400` guard။ `touch-manipulation` class ထည့် (double-tap zoom မဖြစ်အောင်)။
- **ဖိုင်:** `resources/views/components/product-card.blade.php`
- ⚠️ **ဒီနည်း တစ်ကယ့်ဖုန်းမှာ အလုပ်မဖြစ်ခဲ့ဘူး** (120ms timer က နှေးသလို ခံစားရ + touchmove က finger jitter နဲ့ ပိတ်သွား) — item 30 → 31 မှာ ပြင်ထားပြီး နောက်ဆုံးပုံစံက item 31။

#### ၃၀. Product Card — Pure CSS Hover (အဆင့် ၂) — ⚠️ ဒီနည်းလည်း မအောင်မြင်ခဲ့
- **ဘာလုပ်ထားလဲ:** JS toggle အကုန်ဖျက်ပြီး static CSS `group-hover:opacity-100` (lg: prefix မပါ — အားလုံးအကျယ်မှာ) + `pointer-events-none group-hover:pointer-events-auto`။
- **ဖိုင်:** `resources/views/components/product-card.blade.php`
- ⚠️ **တစ်ကယ့် ဖုန်းမှာ မပေါ်ခဲ့ဘူး** — mobile browser တွေက touch မှာ `:hover` ကို အားကိုးလို့မရ (mouse hover ≠ touch hover — emulation နဲ့ စမ်းရင် ကောင်းပုံရပြီး တစ်ကယ့်ဖုန်းမှာ မပေါ်)။ **နောက်ဆုံးအဖြေက item 31 (hybrid)** — item 31 ကြည့်ပါ။

#### ၃၁. ✅ Product Card — Hybrid Touch Reveal (နောက်ဆုံး အလုပ်ဖြစ်တဲ့နည်း)
- **ဘာလုပ်ထားလဲ:** Linn IT Mart လိုမျိုး **ထိရုံနဲ့ ချက်ချင်း (0ms) ပေါ်အောင်**:
  - `@touchstart` — timer မပါ, ချက်ချင်း `reveal = true; revealAt = Date.now();` + `window.dispatchEvent(new CustomEvent('card-revealed', { detail: { key: cardKey } }))`
  - `@touchmove` — **finger က 10px ထက်ပိုရွေ့မှသာ** ပိတ် (`Math.abs(touches[0].clientY - touchStartY) > 10`) — jitter နဲ့ မပိတ်တော့, scroll လုပ်ရင် ပိတ်တယ်
  - `@click` (mobile width, mouse fallback) — toggle + broadcast
  - Card root: `@card-revealed.window` — နောက်တစ်ခုဖွင့်ရင် အယင်တစ်ခု အော်တိုပိတ်
  - Overlay `:class="reveal ? 'opacity-100 pointer-events-auto' : 'opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto'"` (CSS group-hover က desktop hover အတွက်)
  - ခလုပ်တွေရဲ့ click ကို `if (Date.now() - revealAt > 400)` guard — ပထမအထိက ပြရုံပဲ (ခလုပ်မနှိပ်ဖြစ်အောင်), ဒုတိယအထိမှ ခလုပ် အလုပ်လုပ်
  - Overlay container `@click` မှာလည်း guard တူတူ — empty နေရာကို ရည်ရွယ်ချက်ရှိရှိနှိပ်ရင် ပိတ်
- **ဖိုင်:** `resources/views/components/product-card.blade.php`
- **Hosting မှာ:** **`npm run build` မဖြစ်မနေ လိုအပ်** (group-hover:pointer-events-auto class)
- **စစ်ဆေးပြီး:** c0 open [1,0] → c2 [0,1] auto-close → default hidden; eye click navigate; desktop hover အလုပ်လုပ်တယ်။

#### ၃၂. ✅ Product Detail စာမျက်နှာ — Action ခလုတ်များ (Sticky Bottom Bar + Desktop Row)
- **ဘာလုပ်ထားလဲ:** Detail စာမျက်နှာ (catalog/show) မှာ:
  - **(a) Mobile sticky bar** `md:hidden fixed bottom-[100px] left-3 right-3 z-50` (အောက်က 78px bottom nav အပေါ် 10px) — [❤️ Favorite][Share][🛒 Add to Order — gradient, flex-1, qty badge]။ out-of-stock ဆို muted label ပြ။
  - **(b) Desktop inline row** `hidden md:flex` (price box အောက်) — [❤️][Share][🛒 Add to Order] — အယင် standalone "+ 📋 Add to Order" ခလုတ်ကို ဖျက် (direct order form က ရှိနေတယ်)
  - ပုံစံထဲ ထပ်ဖြည့်: `resources/views/components/share-button.blade.php` (props: url/title/text/buttonClass — FB/Telegram/Viber + native Web Share dropdown)
  - Detail wrapper: `pb-[70px] md:pb-0` (bar က content မဖုံးအောင်)
  - Floating FAB တွေ (order-builder cart widget + mobile contact) ကို detail မှာ `$hideFloatingFabs = true` (CatalogController@show ကနေ) — layout မှာ `@if (!($hideFloatingFabs ?? false))`
- **ဖိုင်:** `resources/views/storefront/catalog/show.blade.php`, `resources/views/components/share-button.blade.php`, `app/Http/Controllers/Storefront/CatalogController.php`, `resources/views/layouts/storefront/app.blade.php`
- **Hosting မှာ:** **`npm run build` လို** (bottom-[100px], w-4.5)

#### ၃၃. Detail — Back ခလုပ် ညာဘက်ကို ပြောင်း
- **ဘာလုပ်ထားလဲ:** Detail စာမျက်နှာအပေါ်က Back link ကို `<div class="flex justify-end">` နဲ့ ညာဘက်မှာ ထားတယ် (အယင်ဘယ်ဘက်)။ Blade-only — build မလို။
- **ဖိုင်:** `resources/views/storefront/catalog/show.blade.php`

#### ၃၄. Detail — Related Products (ဆက်စပ်ပစ္စည်းများ)
- **ဘာလုပ်ထားလဲ:** CatalogController@show မှာ `$related` (category သို့ brand တူ, current မဟုတ်, latest, limit 4) query လုပ်ပြီး show.blade.php မှာ "🔗 Related Products" section — header + `grid grid-cols-2 lg:grid-cols-4` + `x-product-card` ပြန်သုံး။ Lang key `related_products` (my: ဆက်စပ်ပစ္စည်းများ, en: Related Products)။
- **ဖိုင်:** `app/Http/Controllers/Storefront/CatalogController.php`, `resources/views/storefront/catalog/show.blade.php`, `lang/my/messages.php`, `lang/en/messages.php`

#### ၃၅. Detail — Direct Order Form Collapsed (ခလုပ်နှိပ်မှ ပွင့်)
- **ဘာလုပ်ထားလဲ:** ကြီးမားတဲ့ order form ကို အယင်အော်တိုမပွင့်တော့ဘူး — `x-data="{ orderFormOpen: {{ ($errors->any() || old('customer_name')) ? 'true' : 'false' }}, contactChannel: ... }"`။ Full-width toggle header ("🛒 Direct Order" + Open/Close label + rotating chevron) → နှိပ်မှ `<form x-show="orderFormOpen" x-cloak>` ပွင့်တယ် (fade/slide, collapse plugin မလို)။ **Validation error / old input ရှိရင် အော်တိုပွင့်** (error မြင်ရအောင်)။ Lang key `open`/`close` (my: ဖွင့်ရန်/ပိတ်ရန်)။
- **ဖိုင်:** `resources/views/storefront/catalog/show.blade.php`, `lang/my/messages.php`, `lang/en/messages.php`
- **Hosting မှာ:** `npm run build` လို (active:scale-[0.99])

#### ၃၆. ✅ Detail — Favorite + Share ခလုပ် Gradient အရောင်နဲ့ (အတည်ပြုပြီး — "ကြိုက်တယ်ကွာ")
- **ဘာလုပ်ထားလဲ:** Product card ရဲ့ gradient စနစ်နဲ့ လိုက်ဖက်အောင်:
  - ❤️ Favorite (mobile bar + desktop row နှစ်ခုလုံး): `bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-rose-500/40`, active: `scale-110 ring-2 ring-rose-300`
  - 🔗 Share (နှစ်ခုလုံး, x-share-button button-class): `bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-amber-500/40`
- **ဖိုင်:** `resources/views/storefront/catalog/show.blade.php`
- **Hosting မှာ:** `npm run build` လို (from-rose-500, ring-rose-300)

#### ၃၇. ✅ Blog Link URL Bug ပြင်ဆင် (404 ဖြစ်နေတာ)
- **ဘာလုပ်ထားလဲ:** Blog စာရင်း/အသေးစိတ်မျက်နှာက link တွေက `/store/{slug}/blog/...` လို့ ထုတ်ပေးထားလို့ route `/blog/{slug}` နဲ့ မကိုက်လို့ **404 ဖြစ်နေတယ်** (browser နဲ့စမ်းတုန်း တွေ့တယ်)။ ပြင်ဆင်ချက် — `store_slug` query param နဲ့ `/blog/{slug}?store_slug=...` အဖြစ်ပြောင်း:
  - `resources/views/storefront/blog/index.blade.php` (post card link)
  - `resources/views/storefront/blog/show.blade.php` (back link + related post links)
- **Hosting မှာ:** ဒီ blade ဖိုင် ၂ ခုပဲ ပြန်ကူးထည့် — build မလို။

#### ၃၈. ✅ Sitemap — Blog + Product URL တွေ ထည့်
- **ဘာလုပ်ထားလဲ:** `/sitemap.xml` က static page ၃ ခုပဲ ပါခဲ့တယ် — blog / blog post တွေ / product တွေ မပါ။ အခု: `/how-to-order` (0.6), `/blog` (0.7) + active store တိုင်းရဲ့ in-stock product URL (0.8) + published blog post URL (0.7) တွေ ထည့်ထားတယ်။
- **ဖိုင်:** `routes/web.php` (sitemap route)
- **Hosting မှာ:** routes/web.php ပြန်ကူးထည့်; `.env` ရဲ့ `APP_URL` ကို production domain နဲ့ ထားရင် sitemap URL တွေ အလိုက်သင့်ပြောင်းမယ်။

#### ၃၉. ✅ How-to-Order စာမျက်နှာ — Admin ကနေ ပြင်လို့ရအောင် + YouTube/TikTok ဗီဒီယို
- **ဘာလုပ်ထားလဲ:** "မှာယူနည်း" စာမျက်နှာကို store တစ်ခုချင်းစီရဲ့ Admin (Store Settings → "📖 How to Order" tab) ကနေ ပြင်လို့ရအောင် လုပ်ထားတယ်:
  - **Intro Text** (ခေါင်းစဉ်အောက်က မိတ်ဆက်စာကြောင်း)
  - **Ordering Steps** repeater (icon emoji + title + desc, အဆင့် ၆ ခုအထိ, +Add/Remove)
  - **ဗီဒီယိုလင့်များ** repeater (title + URL) — YouTube link ထည့်ရင် iframe embed အဖြစ်ပြ; TikTok link ဆိုရင် "Watch on TikTok" card ပြ
  - **Channel ခလုတ်များ** — Contact tab က youtube_url / tiktok_url / facebook_url ထည့်ထားရင် "▶️ YouTube မှာ ကြည့်ရန်" စတဲ့ ခလုပ်တွေ စာမျက်နှာပေါ်မှာ ပေါ်မယ် (footer "Follow Us" မှာလည်း ပေါ်တယ်)
  - **Default မြန်မာစာ content** — `HowToOrderContentSeeder` က နည်းပညာအားနည်းတဲ့ ဖောက်သည်တွေ နားလည်လွယ်အောင် ရေးထားတဲ့ intro + အဆင့် ၄ ခု (ဘာ software မှ မလိုဘူး ဆိုတဲ့ပုံစံ) ဖြည့်ပေးတယ် — **အလွတ်ရှိမှသာ ဖြည့်တာမို့ Admin ပြင်ထားတာ မပျက်ဘူး**
  - Video section က ဗီဒီယို/လင့်တွေ ရှိမှပဲ ပေါ်တယ် (မထည့်ရသေးရင် ပေါ်မည်မဟုတ်)
- **ဖိုင်:** migration `2026_08_02_000003_add_how_to_order_fields_to_storefront_settings.php` (how_to_intro / how_to_steps / how_to_videos), `app/Models/StorefrontSetting.php` (fillable + array casts), `app/Http/Controllers/Admin/StoreSettingController.php` (validation + blank-row filter), `resources/views/admin/settings/edit.blade.php` (How to Order tab), `resources/views/storefront/how_to_order/index.blade.php` (render editable content + video section), `lang/{my,en,zh_CN}/messages.php` (video_tutorials, watch_on_* keys), `database/seeders/HowToOrderContentSeeder.php`
- **Hosting မှာ:**
  1. Migration + seeder files ပြန်ကူးထည့်
  2. `php artisan migrate`
  3. `php artisan db:seed --class=HowToOrderContentSeeder`
  4. `npm run build` မလိုဘူး (အသုံးပြုထားတဲ့ Tailwind class တွေ အကုန် existing) — ဒါပေမယ့် iframe/video ပြဖို့ blade ပြန်ကူးထည့်ဖို့ မမေ့
  5. Admin → Store Settings → Contact tab မှာ ကိုယ့်ရဲ့ YouTube channel / TikTok profile URL အစစ်တွေ ထည့်; How to Order tab မှာ ဗီဒီယိုလင့်တွေ ထည့်

#### ၄၀. ✅ Admin Settings — Tab ၆ ခု → Sidebar စာမျက်နှာ ၄ ခု (tab တွေ များလွန်းလို့)
- **ဘာလုပ်ထားလဲ:** Store Settings စာမျက်နှာက tab ၆ ခု (Identity/Contact/Footer Delivery/Footer Payment/How to Order/Media) ကို **tab bar ဖျက်ပြီး admin sidebar ထဲက သီးခြားစာမျက်နှာ ၄ ခု** ဖြစ်အောင် ခွဲလိုက်တယ်:
  - 🎛️ **General** (`/admin/settings`) — ဆိုင်အမည်, tagline, language, opening hours, logo upload
  - ☎️ **Contact** (`/admin/settings/contact`) — phone, Viber, Telegram, address, chat button, Facebook/YouTube/TikTok social links
  - 🚚 **Delivery & Payment** (`/admin/settings/delivery`) — delivery_info + payment_info + footer_ad_text (ပေါင်းလိုက်)
  - 📖 **How to Order** (`/admin/settings/how-to-order`) — intro + steps + videos (item 39 က repeater)
  - စာမျက်နှာတစ်ခုချင်းစီမှာ သူ့ form + Save ခလုတ်ပဲ — **section တစ်ခု save ရင် တခြား section ကို မထိဘူး** (ပိုလုံခြုံ)
  - Settings စာမျက်နှာအတွင်းမှာလည်း ဘယ်ဘက် vertical section nav (sidebar ပုံစံ) ပါတယ်
- **ဖိုင်:** `app/Http/Controllers/Admin/StoreSettingController.php` (edit/update — `$request->route('section')` သုံး, match-based validation), `routes/web.php` (`/admin/settings/{section}` route, `whereIn`), `resources/views/admin/settings/edit.blade.php` (shell + section sidebar nav), `resources/views/admin/settings/sections/{general,contact,delivery,how_to_order}.blade.php` (အသစ် ၄ ဖိုင်), `resources/views/layouts/admin/app.blade.php` (sidebar မှာ Settings group ထဲ link ၄ ခု), `lang/{my,en,zh_CN}/messages.php` (settings_general/contact/delivery/how_to_order)
- **⚠️ သတိထားစရာ (bug ပြင်ပြီးသား):** Controller method မှာ `?string $section` param သုံးရင် Laravel DI က `{store_slug}` ကို `$section` ထဲ positional ထည့်လို့ **404** ဖြစ်တယ် — `$request->route('section')` နဲ့ ယူရမယ်။
- **Hosting မှာ:** ဖိုင်တွေ ပြန်ကူးထည့် (controller, routes, views, lang, admin layout) — migration/seeder မလို, build မလို။

#### ၄၁. ✅ Blog Admin CRUD (Omnimart ပုံစံ — ဆောင်းပါး admin ကနေ ရေး/ပြင်/ဖျက်)
- **ဘာလုပ်ထားလဲ:** Blog က အရင်က storefront ပဲ ရှိပြီး seeder နဲ့ပဲ ထည့်လို့ရတယ် — အခု **Admin → 📝 Content → Blog Posts** ကနေ ရေး/ပြင်/ဖျက်/published-draft ပြောင်းလို့ရတယ်:
  - List (search + status filter + sort + pagination), Create/Edit form (title, slug auto, excerpt, content, cover image upload → `storage/app/public/blog/`, published checkbox, published_at datetime)
  - Auto slug (title ကနေ) + duplicate slug ရှိရင် -2, -3 ထပ်ဆောင်း
- **ဖိုင်:** `app/Http/Controllers/Admin/AdminBlogController.php`, views `admin/blog/{index,form}.blade.php`, routes, admin sidebar (📝 Content group), lang keys
- **Hosting မှာ:** controller/routes/views/lang ကူးထည့်; blog images → `php artisan storage:link` ရှိဖို့ သေချာ

#### ၄၂. ✅ Dashboard — ဝင်ငွေ stats + Monthly Chart + Top Products (Omnimart ပုံစံ)
- **ဘာလုပ်ထားလဲ:** Dashboard မှာ:
  - **This Month Revenue** (Ks) + month order count, **This Year Revenue** (Ks), **Delivered Orders** card
  - **📈 Monthly Revenue Report** — နောက်ဆုံး ၁၂ လ ဝင်ငွေ bar chart (CSS pure — chart library မလို)
  - **🏆 Top Products (by qty)** — order_items ကနေ top 5
  - Revenue တွက်နည်း: `agreed_amount ?? total_amount`၊ **cancelled orders ကို ဖယ်** (revenue မဟုတ်လို့)
- **ဖိုင်:** `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php`
- **Hosting မှာ:** controller + view ကူးထည့် — build မလို (Tailwind class အကုန် existing)

#### ၄၃. ✅ Order Flow — Delivered status + Printable Invoice
- **ဘာလုပ်ထားလဲ:**
  - Order status တွေမှာ **Delivered** ထပ်ထည့် (pending_contact → confirmed → **delivered** → cancelled) — orders list (card + table) နဲ့ show page မှာ select + blue badge
  - **🧾 Printable Invoice** — `/admin/orders/{order}/invoice` — standalone print-friendly page: store logo/address/phone, order #, customer info, items table, total (agreed amount ရှိရင် override), payment/status badges, "🖨️ Print / Save PDF" button
  - Orders index မှာ Delivered summary card ထည့်
- **ဖိုင်:** `app/Http/Controllers/Admin/OrderAdminController.php` (+invoice method), views `admin/orders/{index,show}.blade.php` + `admin/orders/invoice.blade.php` (အသစ်), routes
- **Hosting မှာ:** controller/routes/views ကူးထည့် — build မလို

#### ၄၄. ✅ Product Reviews (⭐ — ဖောက်သည်တွေ rating + comment ပေးနိုင်)
- **ဘာလုပ်ထားလဲ:**
  - **Storefront:** product detail မျက်နှာမှာ "⭐ ပြန်လည်သုံးသပ်ချက်များ" section — ကြယ်ပွင့် ၁–၅ ရွေး (Alpine star picker) + နာမည် + ဖုန်း (optional) + comment form → POST `/store/{store}/product/{slug}/reviews` (guest friendly, throttle 5/min)
  - Review တွေ **approve ပြီးမှသာ** ပေါ်တယ် + ပျမ်းမျှ rating (avg ★) ပြ
  - **Admin:** **📝 Content → ⭐ Product Reviews** — list (pending/approved filter, rating filter), ✓ Approve / Hide toggle, 🗑️ delete
- **ဖိုင်:** migration `2026_08_02_000004_create_reviews_table.php`, `app/Models/Review.php` + Product `reviews()/approvedReviews()`, `app/Http/Controllers/Storefront/ReviewController.php`, `app/Http/Controllers/Admin/AdminReviewController.php`, views `admin/reviews/index.blade.php`, show.blade.php reviews section, routes, RateLimiter `reviews`, lang keys (my/en/zh_CN)
- **⚠️ bug ပြင်ပြီးသား:** ReviewController method မှာ `string $slug` param သုံးရင် Laravel DI က `{store_slug}` ကို `$slug` ထဲ positional ထည့်လို့ **404** ဖြစ်တယ် — `$request->route('slug')` နဲ့ ယူရမယ် (item 40 နဲ့ အတူတူ)
- **Hosting မှာ:** `php artisan migrate` + controller/routes/views/lang ကူးထည့် — build မလို

#### ၄၅. ✅ Blog Form — Omnimart ပုံစံ (Category + WYSIWYG Editor + Tags + Meta)
- **ဘာလုပ်ထားလဲ:** Blog create/edit form ကို Omnimart လိုမျိုး ပြန်ဆောက်:
  - **Select Category** dropdown — Tips & Tricks / How-to Guide / Product News / Announcements + store မှာ သုံးပြီးသား category များ (storefront မှာ 🏷️ badge အဖြစ် ပြ)
  - **Details (WYSIWYG Editor)** — lightweight toolbar (Bold / Italic / Underline / H2 / H3 / Bullet / Numbered / Link / Clear) — `contenteditable` + `document.execCommand` + hidden textarea sync (library/CDN မလို, build မလို)
  - **Image size hint** — "Image Size Should Be 1200 × 750 (16:10)"
  - **Tags** (comma ခြား) — storefront မှာ #chip တွေ ပြ
  - **Meta Keywords + Meta Description** — SEO (storefront `<head>` မှာ ထည့်ပြီးသား: `<title>` = post title, description/keywords = post က)
- **ဖိုင်:** migration `2026_08_02_000005_add_blog_fields_to_posts_table.php` (category/tags/meta_keywords/meta_description), `app/Models/Post.php`, `app/Http/Controllers/Admin/AdminBlogController.php`, `resources/views/admin/blog/form.blade.php`, `app/Http/Controllers/Storefront/BlogController.php` (SEO meta pass), `resources/views/storefront/blog/{index,show}.blade.php` (category badge + tags + HTML content render), `resources/views/layouts/storefront/app.blade.php` (keywords meta — `$metaKeywords` သုံးအောင် ပြင်)
- **Content render:** content မှာ HTML tag ရှိရင် HTML အဖြစ်ပြ; မရှိရင် nl2br plain text (အဟောင်းတွေ မပျက်)
- **Hosting မှာ:** `php artisan migrate` + files ကူးထည့် — build မလို (execCommand-based editor)

#### ၄၆. ✅ Blog Experience — Solution-Provider Package (Share / Reading time / Prev-Next / Category filter / SEO)
- **ဘာလုပ်ထားလဲ:** Blog စာမျက်နှာတွေကို ဖောက်သည်တွေအတွက် ပိုကောင်းအောင်:
  - **Detail:** 🔗 Share buttons (FB/Viber/Telegram — product card က share component ပြန်သုံး) + ⏱️ **reading time** ("2 min read") + **category badge** + tags chips
  - **Prev/Next ဆောင်းပါး** nav — ဖတ်နေတာ ဆက်ဖတ်အောင် (published_at နဲ့ စဉ်)
  - **Google Article structured data (JSON-LD)** — headline/date/image/description → Google rich results
  - **Blog index:** 🏷️ **category filter chips** (All + ရှိတဲ့ category အကုန်) — `/blog?category=...` နဲ့ filter
  - SEO: `<title>` = post title, meta description/keywords = post က (အရင်က default ပဲ)
  - Seeded posts ၂ ခုမှာ category သတ်မှတ်ပြီးသား (Tips & Tricks / How-to Guide)
- **ဖိုင်:** `app/Http/Controllers/Storefront/BlogController.php` (category filter + prev/next + SEO meta), `resources/views/storefront/blog/{index,show}.blade.php`, `lang/{my,en,zh_CN}` (blog_previous/blog_next), `resources/views/layouts/storefront/app.blade.php` (keywords meta)
- **⚠️ bug သတိပြု:** JSON-LD ထဲက `"@context"` ကို Blade က `@context` directive အဖြစ် မှားကောက်လို့ 500 ဖြစ်တယ် → `@@context` နဲ့ escape လုပ်ရမယ်
- **Hosting မှာ:** controller/routes/views/lang ကူးထည့် — build မလို

#### ၄၇. ✅ Product Form Upgrade — WYSIWYG + Sale Price/Discount + Image Preview + Variants (Solution Provider)
- **ဘာလုပ်ထားလဲ:** Create/Edit Product စာမျက်နှာကို Omnimart ပုံစံအဆင့်မြှင့်:
  - **🧩 Product Variants** — variant repeater (နာမည်/SKU/လက်ကားဈေး/Retail/Stock/Default checkbox) — storefront မှာ ဖောက်သည်က variant ရွေးရင် ဈေးနဲ့ SKU အော်တိုပြောင်း (Alpine reactive)
  - **Sale Price + Discount** — `old_price` ထည့်ရင် storefront detail + product card မှာ ~~ဈေးဟောင်း~~ ကျော် + အနီရောင် `-17%` badge အော်တိုပေါ် (discount % အော်တိုတွက်)
  - **WYSIWYG Description** — blog editor နဲ့ အတူတူ shared component `<x-richtext-editor>` (contenteditable + execCommand, library/build မလို) — storefront မှာ HTML အတိုင်း render
  - **Image Preview** — main image + gallery ရွေးတာနဲ့ ချက်ချင်း preview
  - **Live Price Helper** — Retail vs Wholesale ထည့်တာနဲ့ **margin %** ကို ချက်ချင်း တွက်ပြ (Alpine getter)
  - **SEO Meta Description** — detail စာမျက်နှာမှာ og:description + `<meta name=description>` ကို product က meta_description ပဲသုံး
  - Modern styling — rounded-xl glass cards, quick category/brand modal add, min-h-[44px] touch targets
- **ဖိုင်:** migration `2026_08_02_000006_add_product_variants_and_sale.php` (products.old_price + meta_description, product_variants table), `app/Models/{Product,ProductVariant}.php` (variants()/defaultVariant()/isOnSale()/discountPercent()), `app/Http/Controllers/Admin/ProductController.php` (validation + syncVariants()), `resources/views/admin/products/{_form,create,edit}.blade.php`, `resources/views/components/richtext-editor.blade.php`, `resources/views/storefront/catalog/show.blade.php` (variant selector + reactive price/SKU/discount), `resources/views/components/product-card.blade.php` (sale badge), `lang/{my,en,zh_CN}` (variants key)
- **⚠️ bug သတိပြု:** storefront variant/discount က Alpine x-data နဲ့ client-side render လို့ curl နဲ့စစ်ရင် `x-text` တန်ဖိုးတွေ raw HTML မှာ မပေါ်ဘူး (ဘရောဇာနဲ့ပဲ စစ်ရမယ်); update() က old_price မပါရင် null ပြန်ဖြစ်လို့ edit form မှာ sale price အမြဲ ပါထည့်ပြီးသား
- **Hosting မှာ:** `php artisan migrate` + files ကူးထည့် — build မလို (execCommand editor + Alpine ရှိပြီးသား)

---

#### ၄၈. ✅ Floating Chat Button → Social Channels Popup (Mobile)
- **ဘာလုပ်ထားလဲ:** Mobile အောက်ခြေက ပေါ်နေတဲ့ Chat ခလုတ်ကို တိုက်ရိုက် link (Telegram) မဟုတ်တော့ဘဲ — နှိပ်ရင် **Admin က settings (Contact) မှာ ထည့်ထားတဲ့ social/chat channel တွေကို popup စာရင်း** အနေနဲ့ ပြ၊ သက်ဆိုင်ရာ app/page ကို သွား:
  - **Viber** (viber_number ရှိရင်) → `viber://chat?number=...`
  - **Telegram** (telegram_username ရှိရင်) → `https://t.me/...`
  - **Facebook / YouTube / TikTok** (url ရှိရင်) → သူ့ url အတိုင်း
  - **Chat Button URL** (chat_button_url ရှိရင်) → သူ့ label/icon နဲ့
  - Popup ခေါင်းစဉ် "Chat with us" (language အတိုင်း); အပြင်နှိပ်ရင် ပိတ်; button ပုံစံ/icon/ gradient က အရင်အတိုင်း အတိအကျ
  - Configure မလုပ်ထားတဲ့ channel တွေက popup မှာ မပေါ် (channel လုံးဝမရှိရင် button လည်း မပြ)
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php` (floating contact button block — `<a>` → Alpine `x-data` popup + `<button>`) — migration/DB မလို
- **Hosting မှာ:** app.blade.php ကူးထည့်ရုံပဲ — build မလို (Tailwind standard classes ပဲသုံး)

---

#### ၄၉. ✅ Chat Channels Manager (Admin) — Popup ကို စိတ်ကြိုက်စီမံနိုင် (icon/ပုံ + label + link)
- **ဘာလုပ်ထားလဲ:** Item 48 ရဲ့ popup channel တွေကို Admin က ကိုယ်တိုင် စိတ်ကြိုက် စီမံနိုင်အောင် — Settings → Contact မှာ **"📱 Chat Channels (floating chat popup)"** repeater အသစ်ထည့်:
  - **+ Add Channel** — row တစ်ခုချင်းစီမှာ **Icon (emoji/စာသား)** + **Icon Image (PNG/JPG/WebP upload, image ရှိရင် image ကအနိုင်ရ)** + **Label (စာသား)** + **Link** (`https:// , viber:// , tel: , tg://`)
  - Row ထည့်/ဖျက် လွတ်လပ်စွာလုပ်နိုင်; blank rows တွေကို save မှာ အော်တိုဖျက်
  - **Admin က channels ထည့်ထားရင် popup မှာ အဲဒီ custom channels ပဲပြ** (auto Viber/Telegram/Facebook တွေ မပြ) — မထည့်ထားရင် အရင်အတိုင်း auto ပြ
  - ဖျက်လိုက်/အစားထိုးလိုက်တဲ့ icon ပုံတွေကို storage ကနေ အော်တိုရှင်း (old icon_path diff cleanup)
  - Storefront မှာ icon image ရှိရင် `<img>` နဲ့ပြ, မရှိရင် emoji ပြ; popup header/label တွေက admin ရိုက်ထားတဲ့အတိုင်း
- **ဖိုင်:** migration `2026_08_02_000007_add_chat_channels_to_storefront_settings_table.php` (storefront_settings.chat_channels JSON), `app/Models/StorefrontSetting.php` (fillable + array cast), `app/Http/Controllers/Admin/StoreSettingController.php` (contact validation + per-row image upload → `storage/app/public/chat-icons/` + cleanup), `resources/views/admin/settings/sections/contact.blade.php` (Alpine repeater), `resources/views/layouts/storefront/app.blade.php` (admin channels ဦးစားပေး + image icon render)
- **⚠️ သတိပြု:** Browser automation မှာ IAB file chooser မပံ့ပိုးလို့ admin form ကနေ image upload ကို automation နဲ့မစမ်းရပေမယ့် controller က logo pattern အတိုင်းပဲ (proven); storefront image render ကို DB ထဲ icon_path ထည့်ပြီး verify ပြီးသား
- **Hosting မှာ:** `php artisan migrate` + files ကူးထည့် — build မလို (Tailwind standard classes) — storage:link ရှိရမယ် (`php artisan storage:link`)

---

#### ၅၀. ✅ ဖယ်လိုက်တာ: "Floating Button Link" field (chat_button_url) — popup နဲ့ redundant
- **ဘာကြောင့်:** Floating Chat Button က link မဟုတ်တော့ဘဲ popup ဖွင့်တဲ့ toggle ဖြစ်သွားလို့ — "Floating Button Link" က ဘာမှမထိန်းတော့ဘူး။ Custom link ထည့်ချင်ရင် **Chat Channels** repeater မှာပဲ ထည့်လို့ရပြီ (icon + label + link ပါတစ်ခါတည်း)
- **ပြောင်းထားတာ:** `resources/views/admin/settings/sections/contact.blade.php` (Floating Button Link input ဖယ် + helper text အသစ်: "Button နှိပ်ရင် popup ဖွင့်ပြီး channel များကို Chat Channels မှာ ထည့်ပါ" + icon auto helper ပြင်ပြီး), `resources/views/layouts/storefront/app.blade.php` (auto fallback ထဲက chat_button_url item ဖယ်), `app/Http/Controllers/Admin/StoreSettingController.php` (validation rule ဖယ်)
- **မှတ်စု:** DB column (`chat_button_url`) ကို migration မလုပ်ဘဲ legacy အနေနဲ့ထားတယ် (harmless) — model fillable မှာလည်း ကျန်ထားတယ်
- **Hosting မှာ:** ဒီ ၃ ဖိုင် ကူးထည့်ရုံပဲ — build/migrate မလို

---

#### ၅၁. ✅ Floating Chat Button Icon — ကိုယ့်ပုံ icon upload လုပ်လို့ရအောင် (Image Picker)
- **ဘာလုပ်ထားလဲ:** Floating Button Icon က emoji dropdown (✈️ 💬 📞 📱) ပဲမဟုတ်တော့ဘဲ — **ကိုယ့်စိတ်ကြိုက် icon ပုံ (PNG/JPG/WebP) upload** လုပ်လို့ရအောင် image picker ထည့်:
  - Admin form မှာ Choose File + live preview + ✕ remove (Alpine)
  - Uploaded ပုံကို `storage/app/public/chat-icons/` မှာသိမ်း; အသစ်တင်ရင် အဟောင်းဖျက်; ✕ remove လုပ်ရင် DB မှာ null + file ဖျက်
  - Storefront မှာ icon_path ရှိရင် `<img>` နဲ့ပြ, မရှိရင် emoji ပြ; button gradient/label က မပြောင်း
- **ဖိုင်:** migration `2026_08_03_000001_add_chat_button_icon_path_to_storefront_settings_table.php` (storefront_settings.chat_button_icon_path), `app/Models/StorefrontSetting.php` (fillable), `app/Http/Controllers/Admin/StoreSettingController.php` (validation `chat_button_icon_image` + upload/replace/remove), `resources/views/admin/settings/sections/contact.blade.php` (image picker + Alpine preview/remove + `chat_button_icon_remove` hidden), `resources/views/layouts/storefront/app.blade.php` (button icon img render)
- **Hosting မှာ:** `php artisan migrate` + files ကူးထည့် — build မလို

---

#### ၅၂. ✅ Floating Chat Button → Icon-only (စာမထည့်တော့ဘူး, ပုံပဲခလုတ်) — Device အားလုံးမှာပြ
- **ဘာလုပ်ထားလဲ:** Floating Chat Button က **icon ပုံလေးကိုပဲ ခလုတ်အဖြစ်ထား** — အဝိုင်း gradient နောက်ခံလေး မပါတော့ဘူး, "Chat" စာသားလည်း မပါ; ထိလိုက်တာနဲ့ popup ပေါ်တယ်:
  - **နောက်ခံဖယ်:** `rounded-full bg-gradient-to-br` circular gradient ဖယ် → icon ပုံပဲ (drop-shadow-lg နဲ့) — ပုံမတင်ရသေးရင် emoji ပဲ (text-3xl + drop-shadow)
  - **စာသားဖယ်:** button ထဲက label `<span>` ဖယ် → ပုံ (သို့) emoji ပဲကျန်
  - **Device အားလုံးမှာပြ:** `md:hidden` ဖယ် → Desktop မှာလည်း button ပေါ်ပြီ (mobile တင် မဟုတ်တော့)
  - **Touch target:** button container က `h-14 w-14` (56px) — ပုံက 56px အပြည့်; ထိရလွယ်
  - **Hover နဲ့ဖွင့် (product card hover လိုမျိုး):** Desktop/မောက်မြှားရှိတဲ့ device မှာ icon ပုံလေးကို **ထိရုံနဲ့** channel popup ပွင့်, ဖယ်လိုက်ရင် ပိတ် — `@mouseenter` / `@mouseleave` + `hoverable: window.matchMedia('(hover: hover)').matches` guard (Mobile/touch မှာ hover က မဖွင့်ဘူး → နှိပ်ရင် ဖွင့်တဲ့ toggle အတိုင်းပဲ)
  - `aria-label` ကတော့ ထားသေးတယ် (screen reader အတွက် — မျက်စိနဲ့မြင်ရတဲ့ စာသားတော့ မရှိ)
  - Popup ဖွင့်တာ / channel စာရင်း / z-order — မပြောင်းဘူး (item 48/49 အတိုင်း)
- **ဖိုင်:** `resources/views/layouts/storefront/app.blade.php` (wrapper `fixed bottom-24 right-3 z-40` — md:hidden ဖယ် + `x-data` မှာ `hoverable` ထည့် + `@mouseenter/@mouseleave`; button class → `inline-flex h-14 w-14 items-center justify-center transition active:scale-95` — gradient/bg ဖယ်; img → `h-14 w-14 object-contain drop-shadow-lg`; label span + `$floatGradient` ဖယ်) — migration/DB မလို
- **Browser စစ်ပြီး (390px + 1280px):** Mobile/Desktop နှစ်ဖက်စလုံး — `text: ''` (စာမပါ), `hasBg: false` (အဝိုင်းနောက်ခံမရှိ), img = Admin ရဲ့ uploaded icon ပုံ 56px; Desktop hover → popup ပွင့် (mouseleave → ပိတ်); Mobile mouseenter → မပွင့် (hoverable guard); Desktop မှာ floating cart widget နဲ့ မထိဘူး
- **Hosting မှာ:** app.blade.php ကူးထည့်ရုံပဲ — build/migrate မလို

---

#### ၅၃. ✅ Variant စနစ် အဆင့်မြှင့်တင်ခြင်း — Grouped Selector + Variant Image + CSV + Admin Search (A–E)
- **ဘာလုပ်ထားလဲ:** Variant တွေကို အကောင်းဆုံးပုံစံဖြစ်အောင် ၅ ပိုင်း ပြင်ဆင်ခဲ့တယ်:
  - **A. Grouped selector (attribute structure):** `product_variants` မှာ `attributes` JSON column အသစ် (`[{"label":"Mobile Storage","value":"256GB"},{"label":"Phone Color","value":"Black"}]` လို)။ Preset Apply/Generate Combinations လုပ်တုန်း preset name ကို dimension label အဖြစ် auto-fill လုပ်တယ်။ Storefront မှာ Storage row + Color row ခွဲပြတယ် — flat pill list မဟုတ်တော့ဘူး။ Click လုပ်ရင် မရှိတဲ့ combo ဆိုရင် အရင်းနီးဆုံး combo ကို auto-adjust (sparse combo တွေ lock မဖြစ်အောင်)။ attributes မရှိတဲ့ အဟောင်း product တွေက flat pill fallback အတိုင်းပဲ — backward compatible
  - **B. Variant image:** Product form မှာ variant တစ်ခုချင်းစီအတွက် image upload (preview + ✕ remove ပါ)။ Storefront မှာ variant ရွေးရင် hero image ပြောင်း + order list မှာ variant image သုံး။ Product card က default variant ရဲ့ image ကို သုံး
  - **C. Validation:** Variant name တွေ product အတွင်း duplicate မဖြစ်ရ (SKU စစ်တာက အရင်ကရှိပြီးသား) + out-of-stock variant chips တွေ disabled
  - **D. CSV export/import:** Export CSV မှာ `Variants` JSON column ပါ; import က variants column ကို parse ပြီး variant တွေ create/update (invalid JSON → failed row)
  - **E. Admin:** Product search က variant name/SKU ပါရှာ; Order show/invoice မှာ variant_name/SKU ကို product name အောက်မှာ separate line ပြ
- **ဖိုင်တွေ:** migration `2026_08_04_000004_add_attributes_to_product_variants_table.php`, `app/Models/ProductVariant.php` (attributes fillable/cast), `app/Http/Controllers/Admin/ProductController.php` (validation + syncVariants attributes/image/remove + variantNameError + search + export + import template), `app/Services/ProductImportService.php` (variants JSON support), `resources/views/admin/products/_form.blade.php` (attribute chips + per-variant image upload), `create.blade.php`/`edit.blade.php` (Alpine state — buildVariantRow attributes + previewVariantImage), `resources/views/storefront/catalog/show.blade.php` (grouped selector + hero image swap + `@js` data), `resources/views/components/product-card.blade.php` (default variant image), `resources/views/admin/orders/show.blade.php` + `invoice.blade.php` (variant line)
- **Test:** ProductCatalogTest + ProductImportTest မှာ test အသစ် ၆ ခု (attributes saved + grouped selector, duplicate name rejected, variant image upload, import variants, invalid variants JSON) — အကုန် pass
- **Browser စစ်ပြီး (desktop + 390px mobile):** iPhone 15 Pro Max — "Mobile Storage:" + "Phone Color:" row ခွဲပြ; 512GB click → SKU `APL-IP15PM-512-BL` + Ks 6,150,000 ပြောင်း; Add to Order → cart မှာ "iPhone 15 Pro Max - 512GB Blue Titanium" + ဈေးမှန်; console errors မရှိ။ Flat product (Samsung S24 Ultra) — အဟောင်း pill list အတိုင်း အလုပ်လုပ်
- **Hosting မှာ:** `php artisan migrate` + files ကူးထည့် — build မလို

---

#### ၅၄. ✅ Test 23 ခု အဟောင်း UI နဲ့ လိုက်အောင် ပြင်ဆင် (full suite green — 346 passed)
- **ဘာကြောင့်:** Settings page က sidebar sections ခွဲသွား (item 40)၊ chat button က icon-only popup ဖြစ်သွား (items 50-52)၊ catalog filter keys တွေ ထပ်တိုးလာတဲ့အခါ tests အဟောင်းတွေက အဟောင်းပုံစံပဲ ရှာနေလို့ fail ဖြစ်နေတာ
- **ပြင်ထားတာတွေ:**
  - **Settings tests** (StorefrontSocialMediaSettingTest, StoreSettingsAndBrandingTest, StorefrontChatButtonSettingTest): GET/POST URL တွေကို `/settings/contact` / `/settings/delivery` ပြောင်း + POST တွေမှာ `section` ထည့်; `chat_button_url` field ဖယ်လိုက်လို့ label/icon အတိုင်းပဲ စစ်; floating button test တွေကို icon-only popup + auto channels စစ်တဲ့ပုံစံ ပြန်ရေး
  - **StorefrontBrandingRenderingTest:** floating button class `md:hidden fixed bottom-24 right-3` → `fixed bottom-24 right-3` (device အားလုံးမှာ ပြတာ)
  - **AdminDashboardTest:** Revenue က cancelled order မပါ (revenueSumSince) → "Ks 3,000" → "Ks 1,000"
  - **AdminSidebarNavigationUXTest:** "Store Settings" စာသား → settings link ရှိမရှိကို `data-route-name="store.admin.settings.edit"` နဲ့ စစ်
  - **ProductDiscoveryTest:** card details button က SVG eye icon (emoji/text မဟုတ်တော့) → SVG path + aria-label စစ်
  - **StorefrontNavigationContextTest:** `lg:min-w-[15rem]` → `h-16 sm:h-[4.5rem]` (header bar class)
  - **Localization:** zh_CN မှာ keys ၁၄ ခု (apply, clear_filters, related_products, sort_by … ) လိုနေတာ ထည့်; LocalizationKeysParityTest မှာ key order မကိုက်တာ sort ပြီးမှ စစ်
  - **StorefrontAssetTest:** chat icon ပုံ ကို `public/assets/images/chat-icons/` → `public/assets/chat-icons/` ပြောင်း (regex/test နဲ့ ကိုက်အောင်)
- **ဖိုင်:** `lang/zh_CN/messages.php`, `tests/Feature/` (အထက်ပါ ၈ ဖိုင်), `tests/Unit/StorefrontAssetTest.php`, `public/assets/chat-icons/` (file move)
- **အတည်ပြု:** `php artisan test` → **346 passed, 0 failed**

---

#### 55. Product Add/Edit Admin Form — UI/UX Polish + Localization + Category-Based Variant Preset Suggestions
- **ဘာလုပ်ထားလဲ:** Product Add/Edit စာမျက်နှာကို ecommerce admin မှာသုံးသင့်တဲ့ပုံစံအတိုင်း section ခွဲပြီး polish လုပ်ထားတယ် — **Core Product Info**, **Pricing & Sale Schedule**, **Images & Media**, **Warranty, Return & SEO**, **Variants**။
- **Pricing logic:** Retail price, wholesale price, old price, sale start/end fields တွေကို grouping ပြန်လုပ်ထားပြီး wholesale customer တွေမှာ wholesale price ပဲပြမည့် rule ကို admin hint ထဲမှာရှင်းထားတယ်။
- **Variant UX:** Variant preset dropdown တွေကို category ရွေးတာနဲ့ auto-filter ဖြစ်အောင်လုပ်ထားတယ်။ Category မရွေးထားရင် preset အကုန်ပြတယ်။ Mobile/Phone ဆို `Mobile Storage`, `Phone Color`; Accessories ဆို `Accessories Color`; CCTV ဆို `CCTV Kit Size`; Computer/Laptop ဆို `Computer RAM / Storage`; Fashion ဆို `Fashion Size`, `Fashion Color` တို့ကိုဦးစားပေးပြတယ်။
- **Variant Settings family tag:** `variant_presets.category_family` column ထပ်ထည့်ထားတယ်။ Variant Settings form မှာ `Category Family` dropdown ပါပြီး `mobile`, `accessories`, `cctv`, `computer`, `fashion` တို့ကို preset တစ်ခုချင်းစီမှာသတ်မှတ်နိုင်တယ်။ Product Add/Edit filter က family tag ကိုဦးစားပေးသုံးပြီး tag မရှိတဲ့ old data အတွက် preset name fallback ဆက်ထားတယ်။
- **Backfill:** Migration ထဲမှာ preset name အလိုက် old data family auto-fill rule ပါတယ်။ Local DB မှာလည်း existing presets တွေကို family tag ဖြည့်ပြီးပြီ။
- **Selection safety:** Category ပြောင်းလိုက်လို့ လက်ရှိရွေးထားတဲ့ preset က filter ထဲမပါတော့ရင် selection ကို auto-clear လုပ်တယ်။ မမှန်တဲ့ preset နဲ့ combination မထုတ်မိအောင်ပါ။
- **Localization:** Product Add/Edit form label/hint/button/modal/gallery text တွေကို `messages.product_form_*` translation keys နဲ့ချိတ်ထားတယ်။ `en`, `my`, `zh_CN` locale ဖိုင်တွေထဲ key parity ထည့်ထားပြီး `my` ကို Burmese text နဲ့ဖြည့်ထားတယ်။
- **Create/Edit header:** Header ကို store context ပါအောင်ပြန်တင်ထားပြီး Edit page မှာ SKU context ပါပြတယ်။ Form width ကို admin workflow အတွက် `max-w-7xl` သို့ချဲ့ထားတယ်။
- **Gallery panel:** Edit page မှာ Product Image Gallery upload/primary/delete UI ကို localized labels + delete confirm နဲ့ polish လုပ်ထားတယ်။
- **ဖိုင်တွေ:** `database/migrations/2026_08_04_000005_add_category_family_to_variant_presets_table.php`, `app/Models/VariantPreset.php`, `app/Http/Controllers/Admin/VariantPresetController.php`, `app/Http/Controllers/Admin/ProductController.php`, `database/seeders/VariantPresetSeeder.php`, `resources/views/admin/variant_presets/index.blade.php`, `resources/views/admin/products/_form.blade.php`, `resources/views/admin/products/create.blade.php`, `resources/views/admin/products/edit.blade.php`, `lang/en/messages.php`, `lang/my/messages.php`, `lang/zh_CN/messages.php`
- **စစ်ပြီး:** `php artisan test --filter=ProductCatalogTest` pass, `npm run build` pass, Blade/PHP syntax pass, browser desktop/mobile မှာ Product Create/Edit render + horizontal overflow မရှိ။
- **Hosting မှာ:** files copy ပြီး `php artisan migrate`, `npm run build`, `php artisan view:clear` လုပ်ရန်။

---

### ✅ နောက်ဆုံးအခြေအနေ အကျဉ်းချုပ် (Final State Checklist)

Hosting မှာ ပြန်လုပ်ပြီးရင် အောက်ပါအတိုင်း ဖြစ်နေရမယ်:

- [ ] `/products` မှာ sort dropdown + sidebar filter + pagination အလုပ်လုပ်တယ်
- [ ] Home မှာ "လူကြိုက်များသော အမျိုးအစားများ" single-row scroll — ဖုန်း 📱 + ကွန်ပြူတာ 💻 card တွေ
- [ ] Header ၂ ထပ် (category icon | logo | actions + nav/search)
- [ ] Mobile: Settings dropdown (language + theme), search icon, login က menu ထဲမှာ
- [ ] Desktop: top utility bar (phone/viber/telegram/login), category button text+chevron, hover brand flyout
- [ ] Product card: **ဖုန်းမှာ ထိရုံနဲ့** 🛒👁️Share ခလုတ် ၃ ခု ပေါ် (0ms), နောက်တစ်ခု ဖွင့်ရင် အယင်တစ်ခု အော်တိုပိတ်
- [ ] Detail စာမျက်နှာ: mobile sticky bottom bar (❤️ rose / Share amber / 🛒 sky) + desktop row + Back ညာဘက် + Related Products + collapsed Direct Order form + FABs ဝှက်
- [ ] Blog စာမျက်နှာ ၂ မျက်နှာ အလုပ်လုပ်တယ်
- [ ] Product Create/Edit: WYSIWYG description + variant repeater + sale price + image preview + margin % helper
- [ ] Storefront detail: variant ရွေးရင် ဈေး/SKU ပြောင်း + ~~ဈေးဟောင်း~~ + `-17%` badge + HTML description
- [ ] Product card: sale ရှိရင် ~~ဈေးဟောင်း~~ + `-17%` badge
- [ ] Logo = header-logo.png တစ်ပုံတည်း (name ပါပြီးသား)

---

### 🔑 မှတ်စုများ

- **`npm run build` မဖြစ်မနေလိုတဲ့နေရာ:** item 10 (scrollbar-none), 31 (group-hover:pointer-events-auto), 32 (bottom-[100px], w-4.5), 35 (active:scale-[0.99]), 36 (rose/amber gradients) — blade class အသစ်ထည့်တိုင်း build လုပ်ဖို့ သတိရပါ။
- **Catalog Controller ရဲ့ show() မှာ** `$related` + `$hideFloatingFabs` ပါအောင် သေချာစစ် (items 32, 34)။
- **တစ်ကယ့် ဖုန်းနဲ့ စမ်းဖို့:** Wi-Fi တူမှရတယ်; emulation (mouse) နဲ့ ရတာနဲ့ တစ်ကယ့် touch မတူဘူး (item 30 က ဒါကြောင့် မအောင်မြင်) — နောက်ဆုံးအဖြေက item 31 hybrid။
- **Test gotcha:** Product card ပုံရဲ့ ထိပ်စွန်း (badges/❤️) က နှိပ်လို့မရတဲ့ နေရာ — **ပုံအလယ်ကို ထိပါ**။

---

#### 56. Variant Settings Admin Page — Localization Final Pass
- **ဘာလုပ်ထားလဲ:** Variant Settings page ရဲ့ title/subtitle, stat labels, form labels, hints, option rows, search/filter controls, preset card actions, empty/no-match states တွေကို `messages.variant_preset_*` translation keys နဲ့ချိတ်ထားတယ်။
- **Locale files:** `lang/en/messages.php`, `lang/my/messages.php`, `lang/zh_CN/messages.php` ထဲမှာ key parity ထည့်ထားတယ်။ `my` ကို Burmese UI text နဲ့ဖြည့်ပြီး `zh_CN` ကို English fallback ထားထားတယ်။
- **Compatibility:** UI က locale အလိုက်ပြနေချိန် feature tests မကျစေအောင် action buttons တွေမှာ stable `data-test-label` markers ထည့်ထားတယ်။
- **Safety:** Alpine `x-text` strings နဲ့ delete confirm string ကို `@js()` escaping သုံးပြီး quote/translation text ကြောင့် JavaScript မပျက်အောင်ပြောင်းထားတယ်။
- **ဖိုင်တွေ:** `resources/views/admin/variant_presets/index.blade.php`, `lang/en/messages.php`, `lang/my/messages.php`, `lang/zh_CN/messages.php`

---

#### 57. Product Variant Preset Flow — Browser E2E Attribute Value Fix
- **ဘာလုပ်ထားလဲ:** Product Add/Edit မှာ preset combination generate လုပ်တဲ့အခါ variant attribute `label` hidden fields ပဲ render ဖြစ်ပြီး `value` hidden fields မပါလာတဲ့ Alpine `x-for` template issue ကိုပြင်ထားတယ်။
- **Root cause:** `x-for` template ထဲမှာ root sibling hidden inputs နှစ်ခုထားထားလို့ Alpine က first root ကိုပဲ render လုပ်နေတယ်။ အဲ့ကြောင့် `variants[*][attributes][*][value]` fields form submit ထဲမပါနိုင်တဲ့ risk ရှိတယ်။
- **Fix:** Attribute hidden inputs ကို single `<span class="hidden">` wrapper ထဲထည့်ပြီး `label` / `value` နှစ်ခုလုံး render-submit ဖြစ်အောင်ပြောင်းထားတယ်။
- **Browser စစ်ချက်:** Admin Product Create မှာ `Mobile Storage` + `Phone Color` preset combination generate လုပ်ပြီး variants 16 rows ထွက်တယ်။ Hidden attribute labels 32 ခု + values 32 ခု ပါလာတယ်။ Product save အောင်မြင်ပြီး Edit page မှာ values ပြန်ပါလာတယ်။ Storefront detail မှာ `Mobile Storage`, `Phone Color`, `128GB`, `Black`, selected variant SKU ပြတယ်။
- **ဖိုင်တွေ:** `resources/views/admin/products/_form.blade.php`

---

#### 58. Home Banner Photos — Business Category Campaign Assets
- **ဘာလုပ်ထားလဲ:** DataPOS အတွက် Mobile & Accessories, CCTV & Security, Computer & Laptop, Fashion, All Categories campaign banner photos ၅ ပုံ generate လုပ်ပြီး home banner records အဖြစ် upload ထည့်ထားတယ်။
- **Image style:** Website overlay text မရှုပ်အောင် generated images ထဲမှာ text/logo/watermark မထည့်ထားဘူး။ ပုံတိုင်းမှာ left/top negative space ထားပြီး storefront hero overlay နဲ့ကိုက်အောင် 16:9 wide commercial product photography style သုံးထားတယ်။
- **Uploaded files:** `storage/app/public/banners/datapos-mobile-accessories-2026.png`, `storage/app/public/banners/datapos-cctv-security-2026.png`, `storage/app/public/banners/datapos-computer-laptop-2026.png`, `storage/app/public/banners/datapos-fashion-2026.png`, `storage/app/public/banners/datapos-all-categories-2026.png`
- **DB records:** `home_banners` table ထဲမှာ `page=home`, `sort_order=10..14`, `is_active=true` အဖြစ်ထည့်ထားတယ်။

---

#### 59. Home Banners — Replaced Old Uploads With Generated Campaign Set
- **ဘာလုပ်ထားလဲ:** User အရင်တင်ထားတဲ့ old home banners ၄ ခုကို `home_banners` table နဲ့ `storage/app/public/banners` ထဲက image files ပါဖျက်ထားတယ်။
- **ကျန်ထားတာ:** Generated campaign banners ၅ ခုကိုပဲ `page=home`, `is_active=true` နဲ့ထားထားတယ်။
- **Sort order:** New generated banners တွေကို `sort_order=1..5` ပြန်စီထားတယ် — Mobile & Accessories, CCTV, Computer/Laptop, Fashion, All Categories။

---

#### 60. Glass Finder Banners — Replaced Old Uploads With Generated Screen Protector Set
- **ဘာလုပ်ထားလဲ:** Glass Finder page အတွက် tempered glass finder, precision installation, wholesale glass stock campaign banner photos ၃ ပုံ generate/upload လုပ်ထားတယ်။
- **Old cleanup:** အရင်ရှိတဲ့ old `page=glass_finder` banners ၃ ခုကို `home_banners` table နဲ့ storage image files ထဲကနေဖျက်ထားတယ်။
- **Image style:** Generated images ထဲမှာ text/logo/watermark မထည့်ဘဲ Glass Finder page overlay text နဲ့ကိုက်အောင် left/top negative space ထားထားတယ်။
- **Uploaded files:** `storage/app/public/banners/datapos-glass-finder-premium-2026.png`, `storage/app/public/banners/datapos-glass-installation-2026.png`, `storage/app/public/banners/datapos-glass-wholesale-2026.png`
- **DB records:** `page=glass_finder`, `sort_order=1..3`, `is_active=true` အဖြစ်ထားထားတယ်။

---

#### 61. How to Order Page — Non-Technical Customer Rewrite
- **ဘာလုပ်ထားလဲ:** `/how-to-order?store_slug=datapos-mobile` page ကို website သုံးမကျွမ်းသူတွေပါ လိုက်လုပ်နိုင်အောင် ၅ ဆင့် guide အဖြစ်ပြန်ရေးထားတယ်။
- **UX changes:** Header copy အသစ်, quick action cards, Order Builder/Viber CTA, simple 5-step cards, direct contact/payment/delivery summary sections ထည့်ထားတယ်။ Mobile screen မှာ card တွေရှုပ်မနေအောင် dense but readable layout ပြောင်းထားတယ်။
- **Content update:** `storefront_settings.how_to_intro` နဲ့ `how_to_steps` ကို plain Burmese copy အသစ်နဲ့ update လုပ်ထားတယ်။ Admin settings ကနေ နောက်ပိုင်းပြန်ပြင်လို့ရတဲ့ structure ဆက်ထားတယ်။
- **ဖိုင်တွေ:** `resources/views/storefront/how_to_order/index.blade.php`

---

#### 62. Admin Sidebar — Header Open Control + Icon Polish
- **ဘာလုပ်ထားလဲ:** Admin sidebar ထဲက desktop collapse/open control ကိုဖယ်ပြီး header ပေါ်မှာ desktop collapse/expand button ထားထားတယ်။ Mobile မှာ header menu button က sidebar ဖွင့်ရန်သီးသန့်ဖြစ်ပြီး sidebar ထဲမှာ close button ပဲကျန်အောင်ပြောင်းထားတယ်။
- **UI polish:** Main sidebar groups ဖြစ်တဲ့ Catalog, Sales, Wholesale, Content, Tools, Settings icons တွေကို emoji အစား consistent inline SVG icons နဲ့ပြောင်းထားတယ်။ Emoji variation ကြောင့် icon နှစ်ခုထပ်ပြနိုင်တဲ့ issue ကိုလျှော့ထားတယ်။
- **Behavior:** Variant Settings page ဝင်တဲ့အခါ Catalog group auto-open ဖြစ်အောင် `variant-presets` path ကို active group detection ထဲထည့်ထားတယ်။ Desktop collapsed state ကို header ကနေထိန်းပြီး collapsed ဖြစ်ရင် sidebar groups တွေ auto-close ဖြစ်တယ်။
- **ဖိုင်တွေ:** `resources/views/layouts/admin/app.blade.php`

---

#### 63. Admin Sidebar — Child Menu SVG Icon Final Pass
- **ဘာလုပ်ထားလဲ:** Admin sidebar child links အားလုံးကို emoji icons အစား consistent inline SVG icons နဲ့ပြောင်းထားတယ် — Products, Categories, Brands, Variant Settings, Import, Orders, Wholesale Applications, Blog, Reviews, Glass Finder, Import History, Settings subsections, Users, Home Banners။
- **UX result:** Browser/device အလိုက် emoji variation selector ကြောင့် icon နှစ်ခုထပ်မြင်နိုင်တဲ့ issue ကိုရှင်းထားတယ်။ Sidebar visual rhythm က main groups နဲ့ child links နှစ်ခုလုံးတူညီသွားတယ်။
- **Accessibility:** Dashboard icon wrapper မှာ `aria-hidden="true"` ထည့်ပြီး decorative icon semantics ကိုညီအောင်ပြင်ထားတယ်။
- **ဖိုင်တွေ:** `resources/views/layouts/admin/app.blade.php`

---

#### 64. Store Settings — Delivery & Payment Content Updated
- **ဘာလုပ်ထားလဲ:** DataPOS store settings ထဲက `delivery_info`, `payment_info`, `footer_ad_text` ကို customer ဖတ်ရလွယ်တဲ့ Burmese wording နဲ့ဖြည့်ထားတယ်။
- **Delivery:** ဆိုင်လာယူနိုင်ခြင်း, No 478, KhaingShweWar St, NyaungDon address, Myanmar nationwide delivery, Royal Express, MGL, ကားဂိတ်ရှိသောမြို့များ, delivery fee/time ကို Viber/Telegram မှ ပြန်အတည်ပြုမည်ဆိုတဲ့စာသားထည့်ထားတယ်။
- **Payment:** KBZ Pay / Wave Pay / KPay `09784343151`, MMQR, Bank Transfer, Cash on Delivery, payment screenshot ပို့ရန် instruction ထည့်ထားတယ်။ QR image/data မရှိသေးလို့ Viber/Telegram မှ QR ပုံတောင်းယူရန် wording ထည့်ထားတယ်။
- **Footer ad:** Mobile, Accessories, CCTV, Computer, Fashion, Glass ပစ္စည်းများကို လက်လီ/လက်ကား မှာယူနိုင်ကြောင်း footer text ထည့်ထားတယ်။

---

#### 65. Store Settings — Contact & Social Content Updated
- **ဘာလုပ်ထားလဲ:** DataPOS Contact & Social settings ထဲမှာ Main Phone, Viber, Telegram, Address, Facebook, YouTube, floating chat button label/link, chat channels တွေကိုဖြည့်ထားတယ်။
- **Contact fields:** `phone=<PHONE>`, `viber_number=<VIBER>`, `telegram_username=@<TELEGRAM_USERNAME>`, `address=<ADDRESS>` အဖြစ် update ထားတယ်။
- **Social fields:** `facebook_url=https://facebook.com/datapos`, `youtube_url=https://youtube.com/@datapos` ထည့်ထားတယ်။ TikTok link မသိသေးလို့ blank ထားထားတယ်။
- **Floating chat:** Label ကို `ဆိုင်ကိုမေးမယ်` ထားပြီး Viber, Telegram, Facebook, YouTube, Call Now channels တွေကို popup ထဲမှာပြနိုင်အောင်စီထားတယ်။ Existing uploaded channel icon images တွေကို မဖျက်ဘဲဆက်သုံးထားတယ်။

---

#### 66. Store Settings — TikTok Link Added
- **ဘာလုပ်ထားလဲ:** User request အရ TikTok account link ကို ခန့်မှန်း URL `https://www.tiktok.com/@datapos` အဖြစ် Contact & Social settings ထဲထည့်ထားတယ်။
- **Floating chat:** Chat channels ထဲမှာ TikTok channel ကိုလည်း ထပ်ထည့်ထားတယ်။
- **မှတ်ချက်:** Link အမှန်မသိသေးလို့ ခန့်မှန်းထားတာဖြစ်ပြီး Admin Contact settings မှာ user ကနောက်ပိုင်းပြန်ပြင်နိုင်တယ်။

---

#### 67. Admin Store Settings — Responsive Section Tabs
- **ဘာလုပ်ထားလဲ:** Storefront Settings page ရဲ့ Settings section navigation ကို mobile မှာ horizontal scroll tab bar အဖြစ်ပြောင်းထားတယ်။ Desktop မှာတော့ sticky vertical column nav ပုံစံဆက်ထားတယ်။
- **UI polish:** `Settings` header ကို compact label ပြောင်းပြီး mobile တွင် `Swipe tabs` hint ထည့်ထားတယ်။ General, Contact, Delivery & Payment, How to Order tabs တွေကို `min-w-max` နဲ့ horizontal scroll ထဲမှာမညှပ်အောင်ပြင်ထားတယ်။
- **Icon polish:** Settings tabs icons တွေကို emoji အစား inline SVG icons နဲ့ပြောင်းထားတယ်။ Browser/device အလိုက် double icon ဖြစ်နိုင်တာကိုရှောင်ထားတယ်။
- **ဖိုင်တွေ:** `resources/views/admin/settings/edit.blade.php`

---

#### 68. Admin Sidebar - Duplicate Child Icon Cleanup
- **What changed:** Sidebar child menu labels ထဲမှာကျန်နေတဲ့ emoji prefixes ကို language keys ကနေဖယ်ထားတယ်။ SVG icon တစ်ခုကိုပဲ canonical icon အနေနဲ့ထားပြီး `Blog Posts`, `Product Reviews`, `General`, `Contact`, `Delivery & Payment`, `How to Order` labels တွေကို plain text ဖြစ်အောင်ပြင်ထားတယ်။
- **Why:** Admin sidebar child links တွေမှာ inline SVG icon ထည့်ပြီးသားဖြစ်လို့ translation label ထဲက emoji နဲ့ပေါင်းပြီး icon နှစ်ခုလိုမြင်နေရတာကိုရှင်းရန်။
- **Verification:** `php artisan optimize:clear` run ပြီး browser DOM မှာ sidebar labels အားလုံး emoji မပါတော့တာစစ်ထားတယ်။
- **Files:** `lang/en/messages.php`, `lang/my/messages.php`, `lang/zh_CN/messages.php`

---

#### 69. Admin Header - Calculator Quick Tool
- **What changed:** Admin header ပေါ်မှာ Calculator icon button အသစ်ထည့်ပြီး admin pages အားလုံးကနေ modal calculator ခေါ်သုံးနိုင်အောင်လုပ်ထားတယ်။
- **Calculator features:** Number input, decimal, clear, backspace, `+`, `-`, `×`, `÷`, `=`, keyboard input, `Esc` close, `5%/10%/15%/20%/30%` quick percent buttons တွေပါဝင်တယ်။
- **UI/UX:** Mobile မှာ bottom sheet style, desktop မှာ centered dialog style နဲ့ပေါ်အောင်လုပ်ထားပြီး dark mode, focus ring, overlay click close, accessible labels တွေထည့်ထားတယ်။
- **Verification:** Browser မှာ Calculator button ဖွင့်ပြီး `7 + 8 = 15` result မှန်တာစစ်ထားတယ်။
- **Files:** `resources/views/layouts/admin/app.blade.php`, `lang/en/messages.php`, `lang/my/messages.php`, `lang/zh_CN/messages.php`

---

#### 70. Admin Calculator - Mobile Size Polish
- **What changed:** Phone viewport မှာ Calculator modal ကို screen width ပြည့်နီးပါးဖြစ်အောင်ချဲ့ပြီး keypad button height ကိုတိုးထားတယ်။
- **UI/UX:** Mobile မှာ bottom sheet width ကို `calc(100vw - 0.5rem)` အထိချဲ့ထားပြီး result display ကိုပိုမြင့်အောင်၊ number/operator keys တွေကိုပိုနှိပ်လွယ်အောင်လုပ်ထားတယ်။ Desktop မှာတော့ compact dialog size ဆက်ထားတယ်။
- **Files:** `resources/views/layouts/admin/app.blade.php`

---

#### 71. Admin Calculator - Shortcut Double-Tap Zoom Fix
- **What changed:** Phone shortcut/PWA mode မှာ Calculator keys တွေကို ခပ်မြန်မြန်နှစ်ချက်ဆင့်နှိပ်တဲ့အခါ browser auto zoom မဖြစ်အောင် calculator-only touch behavior ထည့်ထားတယ်။
- **UI/UX:** `.admin-calculator` scope ထဲမှာ `touch-action: manipulation`, `user-select: none`, `-webkit-tap-highlight-color: transparent` ထည့်ပြီး calculator keypad ကို native app-like tap feel ဖြစ်အောင်ပြင်ထားတယ်။
- **Files:** `resources/css/app.css`, `resources/views/layouts/admin/app.blade.php`

---

#### 72. Admin Header - View Commerce and Reload Shortcuts
- **What changed:** Admin header action area ထဲမှာ storefront ကြည့်ရန် `View Commerce` icon link နဲ့ current admin page refresh လုပ်ရန် `Reload` icon button ထည့်ထားတယ်။
- **UI/UX:** View Commerce ကို emerald shop icon, Reload ကို slate refresh icon အဖြစ် icon-only compact controls နဲ့ထားပြီး mobile header မှာလည်းနေရာမကုန်အောင်ပြင်ထားတယ်။
- **Localization:** `view_commerce`, `reload_page` translation keys ကို `en`, `my`, `zh_CN` languages တွေမှာထည့်ထားတယ်။
- **Files:** `resources/views/layouts/admin/app.blade.php`, `lang/en/messages.php`, `lang/my/messages.php`, `lang/zh_CN/messages.php`

---

#### 73. Admin Blog - Production Block Editor
- **What changed:** Blog add/edit form ကို WordPress + Notion style block editor layout အဖြစ်ပြန်တင်ထားတယ်။ Title, Category, Published, Featured Image, Content blocks, SEO, Publish controls, Preview panel တွေကို ecommerce blog workflow နဲ့ကိုက်အောင်ခွဲထားတယ်။
- **Blocks:** Paragraph, Image, Heading, Quote, Button, Video blocks ထည့်/ဖျက်/အပေါ်အောက်ရွှေ့နိုင်တယ်။ Existing old content ကိုမပျက်စေဖို့ `Existing Content` HTML block အနေနဲ့ load လုပ်ထားတယ်။
- **Compatibility:** DB migration မလိုဘဲ existing `posts.content` HTML field ထဲကို generated HTML အဖြစ် sync/save လုပ်တယ်။ Storefront blog renderer နဲ့လည်း backward-compatible ဖြစ်တယ်။
- **Verification:** Browser မှာ `/store/datapos-mobile/admin/blog/12/edit` render စစ်ပြီး Add Block dropdown က Paragraph block ထည့်နိုင်တာစစ်ထားတယ်။
- **Files:** `resources/views/admin/blog/form.blade.php`

---

#### 74. Blog Content - Customer-Focused Rewrite
- **What changed:** DataPOS store ထဲက existing blog posts ၆ ခုကို customer ဖတ်ချင်စရာဖြစ်အောင် title, category, excerpt, tags, meta keywords, meta description, HTML content ပြန်ရေးထားတယ်။
- **Topics:** Mobile buying guide, Accessories charging safety, CCTV resolution guide, Laptop spec guide, WiFi/router guide, Online fashion size guide တွေကို Mobile, Accessories, CCTV, Computer, Network, Fashion business lines အလိုက်ပြန်ချိန်ထားတယ်။
- **Production support:** `RefreshDataPOSBlogContentSeeder` ထည့်ထားလို့ production/server မှာ data refresh ပြန် run လုပ်ချင်ရင် `php artisan db:seed --class=RefreshDataPOSBlogContentSeeder` နဲ့ပြန်သွင်းနိုင်တယ်။
- **Verification:** Public blog list/detail နဲ့ admin blog edit page တွေမှာ Burmese text မပျက်ဘဲ render ဖြစ်တာ၊ meta description ပါသွားတာ browser နဲ့စစ်ထားတယ်။
- **Files:** `database/seeders/RefreshDataPOSBlogContentSeeder.php`, `database/database.sqlite`

---

#### 75. Blog Featured Images - Production Seeder Assets
- **What changed:** Blog posts ၆ ခုအတွက် AI-generated featured images ၆ ပုံကို project seed assets အဖြစ်ထည့်ပြီး current local storage ထဲလည်းသွင်းထားတယ်။
- **Images:** `phone-buying-checklist.png`, `power-bank-and-cable-mistakes.png`, `cctv-buying-guide.png`, `laptop-buying-guide.png`, `how-to-choose-a-wifi-router.png`, `online-clothing-size-guide.png`
- **Production support:** `RefreshDataPOSBlogContentSeeder` က `database/seeders/assets/blog/` ထဲက image files တွေကို `storage/app/public/blog/` ထဲ copy လုပ်ပြီး `posts.image_path` ကို set လုပ်တယ်။ `ProductionSeeder` ကနေ seeder ကိုခေါ်ထားလို့ hosting မှာ `php artisan db:seed --class=ProductionSeeder` run လုပ်ရင် content + images ပါသွားမယ်။
- **Verification:** DB `image_path` values ၆ ခုစစ်ထားပြီး public blog list/detail မှာ `/storage/blog/...png` URLs render/load ဖြစ်တာ browser နဲ့စစ်ထားတယ်။
- **Files:** `database/seeders/assets/blog/*.png`, `storage/app/public/blog/*.png`, `database/seeders/RefreshDataPOSBlogContentSeeder.php`, `database/seeders/ProductionSeeder.php`, `database/database.sqlite`

### Deploy #25 (2026-08-10) — 1:1 category photo cards + category photos restored (working-tree deploy, 1 incident)

- **What changed:** Home "Most Popular Category" cards rebuilt as 1:1 photo tiles (photo top, name below, no product count — changelog 246). Cover priority: category `image_path` → newest featured product image → emoji.
- **Deploy fix:** `deploy-datapos.sh` now excludes `./.freebuff` (~47 MB desktop DB) from the app tar — was making uploads stall/time out.
- **Incident:** SSH connection to Hostinger reset repeatedly. Step [2/3] ships stock `public/index.php`; the [3/3] rewrite to the split-layout version didn't complete → site 500 (stock `../vendor/autoload.php` path missing in split layout). One retry truncated index.php to 0 bytes. **Fix:** wrote split-layout `index.php` (535 B) via `echo <base64> | base64 -d > index.php` and ran `composer install --no-scripts` + `package:discover` + `optimize:clear` + `config:cache` + `view:cache` manually. **Lesson:** if a deploy dies during [3/3], verify `public_html/index.php` still contains `$laravelRoot = dirname(__DIR__) . '/laravel_app'` before anything else.
- **Category photos:** production DB had zero images; restored 4 real orphaned photos (Aug 8 uploads) to Electronic(90)/Phone Spare Parts(108)/Phone Accessories(88)/Power & Storage(85); reverted an accidental write on Microphone(119).
- **Verification:** live home 200 · 12 cards · 4 photo cards render `storage/categories/…` in 1:1 squares; remaining 8 show emoji fallback (no photos exist for them yet).
- **Note:** production category IDs (78–119) differ from local dev (112–142) — never assume IDs match across environments.

### Deploy #26 (2026-08-10) — Web Push backend + order-notification system (deployed)

- **What changed:** full web-push backend now live — `push_subscriptions` + `push_notification_logs` tables (migrated), VAPID keys generated in server `.env` (`php artisan vapid:generate` → 3 `VAPID_*` entries), `api/push/*` routes, admin `/admin/push` + `/admin/push/history` pages, and Burmese order notifications (new order / status change / payment received) dispatched via `AdminPushNotifier` (deduped by `Cache::add`, logged, queued via `ShouldQueue`).
- **Queue note:** production `.env` has `QUEUE_CONNECTION=sync` + `CACHE_STORE=file` → notifications deliver **inline** during the request; the scheduler entry in `routes/console.php` (`queue:work --once` every minute) auto-skips on sync. `crontab` is **not available over SSH** on this Hostinger plan (hPanel only) — if the queue driver is ever switched to `database`/`redis`, add `* * * * * cd ~/domains/datapos.com/laravel_app && php artisan schedule:run >> /dev/null 2>&1` via hPanel Cron Jobs.
- **Deploy:** `RUN_MIGRATIONS=true ./deploy-datapos.sh` completed cleanly (`DEPLOY_OK`) on the first try; both push migrations ran.
- **Post-deploy:** `php artisan vapid:generate` (keys written to `.env`), then `php artisan config:cache` so the VAPID key reaches `config('webpush.vapid.public_key')` (the live homepage `<meta name="vapid-public-key">` confirms it).
- **Live verification:** home 200 · `/manifest.webmanifest`, `/sw.js`, `/js/push-notification.js`, `/icons/badge-72.png` all 200 · admin `/admin/push` + `/admin/push/history` → 302 (login redirect, no 404/500) · routes listed. **Real order test:** storefront POST created order `ORD-6A798FAF814F3` (Ks 4000.00, "Push Test Buyer") → `push_notification_logs` row `[order] 🆕 အော်ဒါ #ORD-6A798FAF814F3 … recipients=5` written. Actual browser delivery awaits a real push subscription (currently 0 on production) — open the site on a phone/browser and allow notifications (bell after 5 page views), or use Admin → Web Push → Send test.
- **Cleanup:** temp verification scripts removed from the server (`/tmp/prod-*.php`); local `.tmp-*` removed.

### Deploy #27 (2026-08-10) — PWA theme-color red → light blue (deployed)

- **What changed:** commit `65102ea` — PWA `theme_color` + layout `<meta name="theme-color">` switched from `#C8102E` (red) to `#38bdf8` (Tailwind sky-400); `public/sw.js` `CACHE_VERSION` v2 → v3 so installed apps re-precache the manifest on the next SW update.
- **Deploy:** `./deploy-datapos.sh` → `DEPLOY_OK` (code + webroot, no migrations).
- **Live verification:** `manifest.webmanifest` → `theme_color #38bdf8` ✓ · homepage meta → `#38bdf8` ✓ · server `sw.js` → `datapos-v3` ✓.
- **Caveat:** the plain `https://datapos.com/sw.js` URL can show a stale copy for up to 7 days (Hostinger edge cache `Cache-Control: public, max-age=604800`); browsers bypass HTTP cache for SW update checks, and a cache-busted fetch returns the fresh file. Manifest/meta are served from Laravel (config/view cache rebuilt by the deploy).
- **Files:** `public/manifest.webmanifest`, `resources/views/layouts/storefront/app.blade.php`, `public/sw.js`, `2026-08-02_FIXES.md`.
