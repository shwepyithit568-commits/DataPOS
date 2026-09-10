# DataPOS Theme System — Long-Term Implementation Plan

> **Document status:** Authoritative implementation plan  
> **Project:** DataPOS single-codebase multi-tenant platform  
> **Primary users:** Platform Owner, Store Owner/Manager, Customer  
> **Target market:** Myanmar SMEs using low-to-mid-range devices and unstable internet  
> **Last verified against repository:** 2026-08-30 (T1 ✅ + T2 ✅ + T3 ✅ + T4 ✅ + T5 ✅ + T6 ✅ + T7 ✅ + T8 ✅ — 94 theme tests pass; full suite 1553 tests / 6639 assertions pass; browser QA on datapos-mobile)

---

## 1. ရည်ရွယ်ချက်

DataPOS ကို Pharmacy, Mobile & Electronics, General Retail, Repair Service, Agriculture, Food & Beverage စသည့် လုပ်ငန်းမျိုးစုံက project တစ်ခုတည်းဖြင့် အသုံးပြုနိုင်စေရန် Storefront Theme System ကို တည်ဆောက်မည်။

Theme System သည် အောက်ပါအချက်များကို ဖြည့်ဆည်းရမည်။

1. Platform Owner က စမ်းသပ်ပြီးသား layout bundle များကိုသာ ထုတ်ဝေနိုင်ရမည်။
2. Store Owner/Manager က ခွင့်ပြုထားသော theme ရွေးခြင်း၊ color၊ font၊ density နှင့် brand assets ပြောင်းခြင်း ပြုလုပ်နိုင်ရမည်။
3. ပြင်ဆင်နေသော Draft သည် Customer မြင်နေသော Published Storefront ကို မထိခိုက်ရ။
4. Publish နှင့် Rollback ကို transaction, revision history, audit log ဖြင့် လုံခြုံစေရမည်။
5. Business Profile သည် recommended default ကိုသာပေးရမည်။ Theme ကို business type နှင့် hard-code မချိတ်ရ။
6. Myanmar/English၊ mobile/desktop၊ slow network နှင့် low-end device များတွင် အသုံးပြုနိုင်ရမည်။

---

## 2. မပြောင်းလဲရမည့် Architecture Decisions

### 2.1 Single Codebase

- Business type တစ်မျိုးစီအတွက် repository သီးခြားမခွဲရ။
- Theme တစ်ခုစီအတွက် application fork သို့မဟုတ် Blade page များ copy/paste မလုပ်ရ။
- Shared domain logic, checkout, catalog query, authentication နှင့် tenant isolation ကို theme ထဲတွင် duplicate မလုပ်ရ။
- Theme သည် presentation layer နှင့် supported component composition ကိုသာ ထိန်းချုပ်ရမည်။

### 2.2 Theme Bundle, Customization နှင့် Business Profile ကိုခွဲထားရန်

- **Theme Bundle:** Header, navigation, homepage section ordering, product card, footer နှင့် visual behavior တစ်စုံလုံး။
- **Customization:** Store-specific colors, typography, spacing/density, logo, favicon, banners။
- **Business Profile:** Features/capabilities နှင့် onboarding default များ။

Business Profile က recommended Theme ကိုရွေးပေးနိုင်သော်လည်း Store Owner က နောက်ပိုင်း မည်သည့် active Theme ကိုမဆို ပြောင်းနိုင်ရမည်။

### 2.3 No Arbitrary Theme Upload

Store Owner ကို ZIP, PHP, Blade, JavaScript, CSS file upload ခွင့် မပေးရ။ WordPress-style executable theme upload သည် remote-code execution, tenant data leakage, upgrade incompatibility နှင့် support burden ဖြစ်စေနိုင်သည်။

Theme အသစ် register/deploy လုပ်ခြင်းသည် Platform Owner-controlled source code release ဖြစ်ရမည်။ Store Owner အတွက် database-backed safe tokens နှင့် approved options များသာ ဖွင့်ပေးရမည်။

---

## 3. Roles နှင့် Permission Boundary

| လုပ်ဆောင်ချက် | Platform Owner | Store Owner | Store Manager | Staff/Cashier | Customer |
|---|---:|---:|---:|---:|---:|
| Theme bundle ဖန်တီး/ပြင်/ပိတ် | Yes | No | No | No | No |
| Store အတွက် theme ရွေး | Yes (support mode/audited) | Yes | Yes | No | No |
| Color/font/density ပြင် | Yes (audited) | Yes | Yes | No | No |
| Draft preview ကြည့် | Yes | Yes | Yes | No | No |
| Publish/Rollback | Yes (audited) | Yes | Yes | No | No |
| POS personal dark/high-contrast mode | Yes | Yes | Yes | Yes | No |
| Published Storefront ကြည့် | Yes | Yes | Yes | Yes | Yes |

**Authorization rule:** Route middleware တစ်ခုတည်းကို မယုံရ။ Controller/Service layer တွင် store ownership နှင့် revision ownership ကို server-side ပြန်စစ်ရမည်။

---

## 4. လက်ရှိ Repository အခြေအနေ

> **Last verified:** 2026-08-30 — T1 (Contract Hardening) + T2 (Persistent Draft) + T3 (Real Isolated Preview) + T4 (Publish/Rollback Integration) + T5 (Layout Bundle Componentization) + T6 (Business Onboarding Recommendation) + T7 (Platform Theme Governance) + T8 (Admin/POS Polish) ပြီးစီး။ Theme suites 94 tests + full suite 1553 tests pass + browser QA အတည်ပြုပြီး

### 4.1 ရှိပြီးသော (Verified)

**Theme Registry & Manifest**
- `App\Themes\ThemeRegistry` နှင့် `App\Themes\ThemeManifest` — ရှိပြီး အသုံးပြုနိုင်
- Curated presets (5): `marketplace_pro`, `retail_trust`, `emerald_fresh`, `midnight_tech`, `sunset_warm`
- Legacy ID aliases: `sky → marketplace_pro`, `midnight → midnight_tech`, `emerald → emerald_fresh`, `rose → sunset_warm`, `violet → marketplace_pro`, `custom → marketplace_pro`
- Font presets (5): `outfit`, `inter`, `pyidaungsu`, `padauk`, `system` — Myanmar system font fallback ပါ
- Grid densities (2): `compact`, `comfortable`

**Published State & Validation**
- Published theme fields 9 ခု `storefront_settings` ထဲတွင်ရှိ (`theme_preset`, `theme_primary_color`, `theme_accent_color`, `theme_header_bg`, `theme_body_bg`, `theme_glow_style`, `theme_dark_mode`, `font_preset`, `grid_density`)
- CSS custom properties (`--sf-primary`, `--sf-accent`, `--sf-font-family` etc.) Storefront ထဲ inline render လုပ်ပြီး
- Validation — **single source** `App\Themes\ThemeConfig` DTO (T1 ✅): Controller နှင့် ThemePublisher နှစ်နေရာလုံးက `ThemeConfig::fromArray()` သို့ delegate လုပ်သည်။ Duplicate validation ဖျက်ပြီးပြီ
- Revision snapshot တွင် `schema_version` + `theme_version` ပါ (T1 ✅) — future manifest/config migrations အတွက် strategy သတ်မှတ်ပြီး

**Persistent Draft (T2 ✅)**
- `store_theme_drafts` table: `store_id` (unique FK), `theme_config` (complete 9-field snapshot JSON), `base_revision_id` (nullable FK), `updated_by` (nullable FK), `lock_version` (unsigned, default 1)
- `ThemeDraftService` — getOrCreate / save (optimistic lock) / publish (base-revision conflict check) / discard / resetToPublished
- `ThemeDraftConflictException` — transaction ထဲ throw → transaction အပြင် audit → HTTP 409
- **Draft save က `storefront_settings` ကို ဘယ်တော့မှ မပြောင်းပါ** — Customer storefront က draft ကို ဖတ်စရာမလို (structural isolation ✓)
- Publish conflict / stale lock / discard ကို audit log (`store_theme_draft_conflict`, `store_theme_draft_discard`) ရေးသည် (Plan §12)
- Rollback ပြီးတိုင်း draft ကို restored state မှ reset လုပ်သည် (Plan §8.4)
- Appearance editor က draft-based fetch API (`/admin/appearance/draft` JSON endpoints) သုံးပြီး settings `<form>` နှင့် ခွဲထားသည် — Enter-key ဖြင့် legacy direct-publish bypass မဖြစ်နိုင်

**Real Isolated Preview (T3 ✅)**
- `App\Themes\ThemeContext` — request-scoped draft-config override holder (scoped container binding)
- `ResetThemePreview` global after-middleware — override ကို response အပြီး clear လုပ်ပြီး Octane/feature-test များတွင် request တစ်ခုမှ တစ်ခုသို့ leak မဖြစ်စေရ (test က ဒီ bug ကို ဖမ်းမိပြီး ပြင်ထားသည်)
- `GET /store/{slug}/admin/appearance/preview` — `EnsureStoreAccess:store_manager` auth-gated, `Cache-Control: no-store, private`, `X-Robots-Tag: noindex, nofollow`
- CSP framing (browser QA ဖြင့် ရှာတွေ့ပြီး ပြင်ထားသည်): preview response တွင်သာ `frame-ancestors 'self'` (same-origin admin page တွင် iframe ဖွင့်နိုင်) — တခြား page အားလုံး `frame-ancestors 'none'` ဆက်ထား; `SecurityHeaders` သည် `theme_preview_frame` request attribute ကို စစ်သည်
- Production Storefront pipeline ပြန်သုံးသည် — `HomeController@index` ကို delegate လုပ်ပြီး draft tokens ဖြင့် render (static mockup မဟုတ်)
- Storefront layout သည် `ThemeContext::activeConfig()` ရှိလျှင် draft tokens (colors + font) ကို CSS custom properties အဖြစ် ထုတ်သည် — published `storefront_settings` ကို ဘယ်တော့မှ မပြောင်းပါ
- Appearance UI — Desktop (1440) / Tablet (768) / Mobile (390) segmented viewport toggle + Refresh button + loading overlay (8s fallback) + draft autosave ပြီးတိုင်း preview auto-reload

**Publish/Rollback Integration (T4 ✅)**
- `App\Events\ThemeRevisionCommitted` — publish/rollback commit ပြီးမှသာ dispatch (transaction အပြင်မှာ — listener failure က publish ကို မပြန်လှန်နိုင်)
- `App\Listeners\InvalidateStorefrontCache` — target store အတွက်သာ `storefront:theme:bumped:{store_id}` marker ထား (90s window); `Cache::flush()` မသုံး; key scheme က future server-side response cache (`storefront:page:{store_id}:*`) အတွက် hook ဖြစ်
- `CachePublicPage` — marker ရှိလျှင် target store ရဲ့ public pages ကို `max-age=0, must-revalidate` (immediate revalidation) — browser QA ဖြင့် အတည်ပြု: publish ပြီးချင်း new theme ချက်ချင်းပေါ် (max-age=60 stale window မရှိတော့); တခြား store / steady-state က max-age=60 ဆက်ထား
- Publish failure atomicity — transaction ထဲ failure ဖြစ်ရင် published config + revision history + audit log + cache marker အားလုံး မပြောင်း (test ဖြင့် အတည်ပြု)
- Publish confirmation modal — "ယခု Published: Revision #N → Publish ပါက #N+1" details ပြသည်
- Stale draft conflict rejection (409) + rollback→draft reset — T2 မှာ ပြီးပြီးသား (T4 flow နှင့် ပေါင်းစပ်ထား)

**Layout Bundle Componentization (T5 ✅)**
- `App\Themes\ThemeComponents` — platform-controlled component registry: theme bundle → approved variant composition (header_variant, nav_style, product_card_variant, footer_variant)
- Approved mapping: `marketplace_pro`/`emerald_fresh` → compact card + pill nav; `retail_trust` → compact card + underline nav; `midnight_tech` → showcase card + underline nav + premium header; `sunset_warm` → showcase card + pill nav
- `x-product-card` → thin dispatcher → `components/product-card-variants/{variant}` (compact = glued grid, showcase = padded/centered); callers မပြောင်း — data contract တူ၊ presentation သာ ကွဲ
- Nav partials `storefront/components/nav-pill` / `nav-underline` (capability-aware links တူညီ) + header accent partial (premium = PRO chip)
- Legacy/unknown theme id → canonical composition; unknown component/variant → safe default (missing-content fallback) — Store Owner က arbitrary variant မရွေးနိုင်
- No theme-specific queries — registry သည် static; variant partials က already-loaded data ကိုသာ သုံး
- Preview ကလည်း draft preset ၏ composition ကို reflect (layout က `previewConfig->themePreset` ဖြင့် resolve)

**Business Onboarding Recommendation (T6 ✅)**
- `App\Themes\ThemeRecommendation` — profile → recommended theme (plan §7): mobile_electronics→marketplace_pro, general_retail→retail_trust, repair_service→retail_trust, pharmacy→emerald_fresh, agriculture→retail_trust, food_beverage→sunset_warm, unknown→marketplace_pro (safe default)
- `StoreOnboardingService::provisionStore` — edition arrays မှ duplicate theme_preset ဖယ်ပြီး recommendation ကို single source အဖြစ်သုံး (edition profile မှ resolve)
- Demo seeder — demo provisioning တိုင်း `recommendForDemoBusinessType()` ဖြင့် recommended theme ထည့် (mobile_sale_service→marketplace_pro, pharmacy→emerald_fresh, restaurant→sunset_warm, agriculture_inputs→retail_trust …)
- **Existing stores ကို ဘယ်တော့မှ silently မပြောင်း** — recommendation ကို provisioning မှာသာ ခေါ်; test ဖြင့် အတည်ပြု
- **Owner က နောက်ပိုင်း မည်သည့် active theme ကိုမဆို ပြောင်းနိုင်** — recommendation သည် default သာ၊ authorization မဟုတ် (test: pharmacy store → marketplace_pro publish အောင်မြင်)

**Platform Theme Governance (T7 ✅)**
- ThemeManifest/ThemeRegistry — `status` (active|deprecated|hidden) + `replacementId` fields (all 5 themes active by default)
- `theme_governance` table + `ThemeGovernance` model + `ThemeGovernanceService` — DB-backed lifecycle overrides (static manifest is baseline)
- Lifecycle rules: **deprecated/hidden themes က existing stores အတွက် အမြဲ renderable + publishable (ဘယ်တော့မှ မချိုး)**; hidden → appearance picker မှ ပျောက် + onboarding မှာ မရွေး; deprecated → picker တွင် ⚠️ badge + replacement လမ်းညွှန်
- `Admin\ThemeGovernanceController` + `admin/theme-governance` page (Platform Owner only) — theme list (en/mm name, id, version, swatches), status + replacement dropdown per theme, sidebar link
- Every lifecycle change audited — `theme_lifecycle_change` (actor, from/to status, from/to replacement)
- Onboarding recommendation returns ACTIVE themes only — hidden/deprecated recommended → active replacement → default (fallback chain)

**Admin/POS Polish (T8 ✅)**
- Restrained Admin brand accent — admin layout `<html>` တွင် `--admin-accent` (store theme primary) + active sidebar nav link ကိုသာ accent အဖြစ်သုံး; semantic danger/success/warning colors က system-controlled ဆက်ဖြစ် (Plan §11)
- POS personal display mode — `standard_light` / `high_contrast_daylight` / `oled_dark` dropdown (header button; open state `$nextTick`-deferred so the opening click does not immediately hit `@click.outside` — same fix as the publish modal)
- Per-device localStorage persistence (`posDisplayMode`) — reload ပြီးတာနဲ့ auto-apply (browser QA ဖြင့် အတည်ပြု)
- `pos-hc` (white surfaces, near-black text, stronger borders) + `pos-oled` (pure-black backgrounds) CSS overrides — restrained
- **Storefront published theme/revision နှင့် လုံးဝသီးခြား** — POS page တွင် `--sf-*` tokens မပါ; storefront theme publish က POS preference ကို မပြောင်း (test + browser QA)
- Admin logo sync က ရှိပြီးသား (`adminLogo()`)

**ThemePublisher (transaction-based)**
- `DB::transaction` ဖြင့် `storefront_settings` lock + save + revision create
- Baseline revision auto-create (first publish)
- Publish / Rollback / Audit — atomic
- Rollback: source revision owner (store_id) check ပါ
- `AuditLog::write` ဖြင့် `store_theme_publish` / `store_theme_rollback` action မှတ်

**Revision History**
- `store_theme_revisions` table: `id`, `store_id` (FK+cascade), `revision_number`, `theme_config` (JSON), `action`, `source_revision_id` (nullable FK self), `actor_id` (nullable FK users), `created_at`
- Unique constraint: `(store_id, revision_number)` — concurrent race protection
- Index: `(store_id, created_at)`
- Row ကို delete/update မလုပ်ဘဲ rollback တိုင်း revision အသစ်ဖန်တီး → immutable ✓
- Snapshot တိုင်းတွင် `schema_version` (config shape) + `theme_version` (theme bundle) ပါ — old rows များကို `ThemeConfig::fromArray()` က normalize လုပ်နိုင်

**Admin UI**
- Appearance Settings page — theme/color/font/density ပြင်နိုင် (draft-based)
- Draft Status badge (✅ Saved / 🔵 Unsaved / Saving… / ⚠️ Conflict / Publishing…) + Save Draft + Publish Live buttons
- Publish confirmation modal + conflict warning banner
- Published Theme History list (actor name ပါ) + Rollback button
- Cross-store guard: `StoreContext` middleware + `abort_unless(source->store_id === $store->id)` service check
- **Publish/Modal click fix (2026-08-30):** modal/dropdown open state ကို `$nextTick` ဖြင့် defer လုပ်ထားသည် — opening click ကိုက modal ၏ `@click.outside` က ပိတ်ပစ်သည့် classic Alpine gotcha ကို ဖြေရှင်းထား (browser QA ဖြင့် အတည်ပြု: real click → modal ပွင့်ပြီး နေ → publish အောင်မြင်)

**Tests (Verified Green — 2026-08-29)**
- `ThemeConfigTest` (Unit) — 19 tests: canonical 9-field snapshot, unknown-key rejection, color normalization/expansion/fallback, enum/font/density validation, legacy alias resolution, schema+theme version snapshot, determinism
- `ThemeDraftTest` (Feature) — 19 tests: draft seeding, save-never-touches-published, optimistic lock (stale → 409), cross-store isolation, publish-from-draft, base-revision conflict → 409, discard + reseed, rollback reset, staff 403, cross-store API 403, conflict flag on GET, conflict + discard audited
- `ThemeEngineTest` (Feature) — 12 tests: registry, appearance page, publish flow, rollback exact-snapshot, cross-store 404/403, storefront CSS vars, unknown-key purge, lowercase normalization, schema_version, legacy alias
- `ThemePreviewTest` (Feature) — 7 tests: draft tokens + no-store/noindex headers, draft visible in preview while anonymous storefront keeps published, preview-without-draft uses published, staff 403, cross-store 403, preview allows same-origin framing, regular pages keep frame-ancestors 'none'
- `ThemeCacheInvalidationTest` (Feature) — 5 tests: publish/rollback set target-store-only revalidation marker, storefront max-age=0 after publish (other store keeps 60), marker expiry restores 60, publish failure atomicity (config + history + audit + marker unchanged)
- `ThemeComponentsTest` (Unit) — 5 tests: per-theme composition, legacy id canonicalization, unknown theme/component → safe defaults, approved variants only
- `ThemeComponentRenderTest` (Feature) — 4 tests: midnight_tech renders showcase card + underline nav + premium accent; marketplace_pro renders compact card + pill nav + no accent; legacy 'midnight' uses canonical composition; unknown preset falls back to compact
- `ThemeOnboardingRecommendationTest` (Feature) — 7 tests: plan §7 mapping, unknown profile → safe default, demo business types, pharmacy/general_retail provisioning applies recommended theme, existing stores untouched, owner can switch to any active theme after provisioning
- `ThemeGovernanceTest` (Feature) — 11 tests: default statuses, override persistence + audit, invalid status/replacement rejected, hidden excluded from selectable, hidden disappears from picker, deprecated stays renderable/publishable + badge, onboarding avoids hidden, governance page platform-owner-only (403), HTTP update audited
- `ThemeAdminPosPolishTest` (Feature) — 5 tests: admin brand accent from store theme primary, manifest fallback, no storefront tokens leak into admin, POS 3-mode dropdown present + localStorage key, POS independent of storefront theme publish
- Full regression suite — **1553 passed / 6639 assertions**

### 4.2 မပြီးသေးသောအပိုင်း

**T1 (✅ Complete — 2026-08-29)**
- ✅ Theme config typed DTO / value object — `App\Themes\ThemeConfig` (canonical 9-field snapshot, unknown keys discard + debug log)
- ✅ Single canonical validation source — Controller `$request->only(ThemeConfig::SAFE_KEYS)` + `ThemePublisher::publish()` + `ThemeDraftService::save()` အားလုံး `ThemeConfig::fromArray()` မှတစ်ဆင့်
- ✅ `schema_version` + `theme_version` strategy — `ThemeConfig::SCHEMA_VERSION = 1`၊ manifest `version` field ပါ၊ revision snapshot ထဲ နှစ်ခုလုံး မှတ်တမ်းတင်
- ✅ `unknown key` → reject/log behavior — `ThemeConfig::fromArray()` မှ discard + `Log::debug`
- ✅ Legacy ID migration/fallback — `$legacyMap` + `allValidIds()`
- ✅ Token validation (color/font/density) unit tests — `ThemeConfigTest` (19 tests)

**T2 (✅ Complete — 2026-08-29)**
- ✅ Persistent Draft (`store_theme_drafts` table) — migration + `StoreThemeDraft` model
- ✅ `ThemeDraftService` create/update/reset/discard + `AppearanceDraftController` JSON API
- ✅ Optimistic locking (`lock_version`) — stale → HTTP 409
- ✅ Base revision conflict detection — 409 + conflict flag on GET /draft
- ✅ Store-scoped draft routes + authorization — `EnsureStoreAccess:store_manager` + cross-store 403 tests
- ✅ Draft saved/failed/conflict/publishing UI states — badge, banner, modal, autosave

**T3 (✅ Complete — 2026-08-29)**
- ✅ Request-scoped `ThemeContext` + `ResetThemePreview` after-middleware (cross-request leak protected)
- ✅ Authenticated no-store/private/noindex preview route — `GET /admin/appearance/preview`
- ✅ Production Storefront components reuse — `HomeController@index` delegate + draft tokens via CSS vars
- ✅ Desktop/Tablet/Mobile viewport controls — segmented toggle + Refresh + loading overlay
- ✅ Draft token changes visible without publish — autosave → preview auto-reload
- ✅ CSP framing — preview response တွင်သာ `frame-ancestors 'self'` (browser QA ဖြင့် ရှာတွေ့ပြီး ပြင်ထား)

**T4 (✅ Complete — 2026-08-29)**
- ✅ Publish directly from selected draft version — draft → publish flow (T2/T3 နှင့် ပေါင်းစပ်)
- ✅ Stale draft conflict rejection — base-revision conflict → 409 (T2)
- ✅ Commit-after cache/event handling — `ThemeRevisionCommitted` event + target-store `max-age=0` window; `Cache::flush()` မသုံး
- ✅ Rollback/draft reconciliation — rollback ပြီးတိုင်း draft reset (T2)
- ✅ Confirmation UI + actor/revision details — modal တွင် "ယခု Published: #N → #N+1" ပြသည်
- ✅ Publish failure atomicity — transaction failure က config/history/audit/marker အားလုံး မပြောင်း (test)

**T5 (✅ Complete — 2026-08-29)**
- ✅ Common Storefront data/view-model contract — `x-product-card` API မပြောင်း; variant partials က loaded data သာ သုံး
- ✅ Header/navigation/product-card component variants — registry + approved partials
- ✅ Five bundles mapped to approved component variants — `ThemeComponents::composition()`
- ✅ Capability-aware optional sections — nav/footer links တွင် `store_can()` ဆက်သုံး (variant နှစ်ခုလုံး)
- ✅ No theme-specific catalog/price/stock queries — static registry + shared data
- ✅ Missing content/asset fallback — unknown variant → safe default; product-image fallback ဆက်ရှိ

**T6 (✅ Complete — 2026-08-29)**
- ✅ BusinessProfile → recommended theme mapping — `ThemeRecommendation` (plan §7)
- ✅ Apply only during new store/demo provisioning — `StoreOnboardingService` + demo seeder
- ✅ Existing stores are not silently changed — test ဖြင့် အတည်ပြု
- ✅ Owner may choose any active theme afterward — recommendation သည် default သာ
- ✅ Unknown/new profile gets safe default — `marketplace_pro`

**T7 (✅ Complete — 2026-08-29)**
- ✅ Platform Owner theme list/status/version UI — `admin/theme-governance` + sidebar link
- ✅ Active/deprecated/hidden lifecycle — DB-backed overrides (`theme_governance`), audited
- ✅ Deprecation does not break stores already using theme — deprecated/hidden themes renderable + publishable (test)
- ✅ Migration/fallback path before removal — replacement dropdown + deprecated badge + onboarding fallback chain
- ✅ Preview image နှင့် Myanmar/English metadata — name_en/name_mm/description ရှိပြီးသား (preview image က manifest ထဲ မထည့်ရသေးသော future item)

**T8 (✅ Complete — 2026-08-30)**
- ✅ Restrained Admin brand accent option — `--admin-accent` (store theme primary) → active sidebar link
- ✅ POS personal high-contrast/daylight/OLED preference — 3-mode dropdown + per-device localStorage
- ✅ Semantic colors remain system-controlled — danger/success/warning ကို Store Owner customization မလုပ်
- ✅ Storefront published theme/revision နှင့် POS preference သီးခြား — `--sf-*` tokens POS တွင် မပါ; publish က preference မပြောင်း
- ✅ Device preference persistence — localStorage `posDisplayMode` (reload auto-apply)

**T9 (မစလျှင် — Production Verification)**
- ❌ All 5 themes × mobile/tablet/desktop browser regression matrix — manual QA ကျန်
- ❌ Myanmar/English × home/catalog/product/search/cart/order/how-to-order/contact routes
- ❌ Empty/small/40+/promotion/missing-image datasets + slow network/low-end device smoke test
- ❌ Cross-store isolation + publish/rollback/concurrency/failure suite (feature tests ရှိပြီးသား — browser matrix ကျန်)
- ❌ Deployment restart persistence

**အရေးကြီး:** Local color mockup ရှိခြင်းကို isolated Storefront Preview ပြီးပြီဟု မသတ်မှတ်ရ။
T2–T4 ပြီးသွားပြီ — Draft isolation + Real Preview + commit-after cache invalidation + publish/rollback atomicity အားလုံး browser QA နှင့် tests ဖြင့် အတည်ပြုပြီး။

---

## 5. Theme Data Model Target

### 5.1 Published State

လက်ရှိ `storefront_settings` ကို Customer-facing published state အဖြစ် ဆက်သုံးမည်။ Existing code compatibility အတွက် publish မပြီးမချင်း ဤ columns များကို draft save က မပြောင်းရ။

Published theme fields:

```text
theme_preset
theme_primary_color
theme_accent_color
theme_header_bg
theme_body_bg
theme_glow_style
theme_dark_mode
font_preset
grid_density
```

### 5.2 Draft State

`store_theme_drafts` table အသစ်ကိုအောက်ပါအတိုင်းတည်ဆောက်ရန်:

```text
id
store_id                  unique FK -> stores.id, cascade delete
theme_config              JSON, complete normalized snapshot
base_revision_id          nullable FK -> store_theme_revisions.id
updated_by                nullable FK -> users.id
lock_version              unsigned integer default 1
created_at
updated_at
```

Rules:

- Store တစ်ခုလျှင် active draft တစ်ခုသာရှိရမည်။
- Draft JSON သည် partial patch မဟုတ်ဘဲ complete normalized theme snapshot ဖြစ်ရမည်။
- `base_revision_id` သည် draft စတင်ချိန် Published revision ကိုညွှန်ရမည်။
- `lock_version` ဖြင့် tab နှစ်ခု/agent နှစ်ခု တပြိုင်နက် save လုပ်သည့် lost update ကိုကာကွယ်ရမည်။
- Draft ကို Customer storefront query က မဖတ်ရ။

### 5.3 Revision State

လက်ရှိ `store_theme_revisions` ကို immutable history အဖြစ် ဆက်သုံးမည်။

- Existing row ကို update/delete မလုပ်ရ။
- Publish/Rollback တိုင်း revision အသစ်ရေးရမည်။
- `theme_config` သည် exact complete snapshot ဖြစ်ရမည်။
- Future manifest changes အတွက် `schema_version`, `theme_version` ကို additive migration ဖြင့်ထည့်ရန်။
- Revision number allocation ကို transaction + row lock + unique constraint ဖြင့်ကာကွယ်ရမည်။

### 5.4 Future Manifest Metadata

Theme registry တိုးချဲ့သည့်အခါ manifest contract တွင် အနည်းဆုံးအောက်ပါတို့ ပါရမည်။

```text
id
version
name_en
name_mm
description
status                    active | deprecated | hidden
supported_schema_version
preview_image
recommended_profiles[]
default_tokens{}
supported_tokens[]
layout_components{}
feature_requirements[]
```

Manifest ကို executable JSON upload အဖြစ်မယူရ။ Repository ထဲရှိ validated PHP configuration/value objects ဖြင့် register လုပ်ရမည်။

---

## 6. Theme Token Contract

Theme token ကို component တစ်ခုချင်းစီမှာ hard-coded color ထပ်ရေးခြင်းမလုပ်ဘဲ central resolver မှ CSS custom properties အဖြစ် ထုတ်ပေးရမည်။

### 6.1 Store Owner ပြောင်းနိုင်သော Safe Tokens

```text
color.primary
color.accent
color.header_background
color.body_background
mode.light_dark
effect.glow
typography.preset
catalog.grid_density
```

Logo, favicon နှင့် banners ကို asset subsystem တွင်ဆက်ထားပြီး theme snapshot တွင် file binary/path duplicate မလုပ်ရ။

### 6.2 Platform-controlled Tokens

```text
layout.header_variant
layout.home_composition
layout.footer_variant
component.product_card_variant
component.navigation_variant
component.promotion_variant
component.search_variant
```

Store Owner ကို layout component ID arbitrary ရိုက်ထည့်ခွင့်မပေးရ။ Selected bundle က ခွင့်ပြုထားသော component mapping ကိုသာ အသုံးပြုရမည်။

### 6.3 Validation

- Colors: canonical lowercase `#rrggbb` only
- Font: registry allow-list only
- Density/mode/effect: enum allow-list only
- Unknown keys: reject or explicitly discard and log; silently persist မလုပ်ရ
- Missing keys: manifest default ဖြင့် normalize လုပ်ရ
- Contrast: WCAG AA ကိုရည်မှန်းပြီး normal text အနည်းဆုံး 4.5:1, large text 3:1
- Unsafe CSS value, URL, `var()`, `calc()`, HTML နှင့် JavaScript မသိမ်းရ

---

## 7. Curated Theme Bundles

Theme နာမည်များကို Amazon/AliExpress ကဲ့သို့ trademark-copy မလုပ်ရ။ Ecommerce pattern များမှသာလေ့လာပြီး DataPOS ကိုယ်ပိုင်နာမည်၊ layout နှင့် assets သုံးရမည်။

| Theme ID | အဓိကပုံစံ | Recommended Profiles | Restriction |
|---|---|---|---|
| `marketplace_pro` | Category-rich marketplace, compact catalog | Mobile, Electronics, Multi-category | Any store may choose |
| `retail_trust` | Clean/high-contrast retail | General Retail, Mart, Agriculture | Any store may choose |
| `emerald_fresh` | Trust/health-oriented storefront | Pharmacy, Healthcare, Agriculture | Any store may choose |
| `midnight_tech` | Premium dark technology catalog | Mobile, Computer, CCTV, Gaming | Any store may choose |
| `sunset_warm` | Warm visual merchandising | Fashion, Cosmetics, Lifestyle, Food gifts | Any store may choose |

Recommended mapping သည် onboarding convenience သာဖြစ်ပြီး authorization rule မဟုတ်ပါ။

Suggested onboarding defaults:

```text
mobile_electronics -> marketplace_pro
general_retail     -> retail_trust
repair_service     -> retail_trust
pharmacy           -> emerald_fresh
agriculture        -> retail_trust
food_beverage      -> sunset_warm
unknown/new        -> marketplace_pro
```

---

## 8. Required User Workflows

### 8.1 Start/Edit Draft

1. Appearance page ဖွင့်သည်။
2. Draft ရှိလျှင် draft ကို load လုပ်သည်။ မရှိလျှင် current published snapshot မှ draft ဖန်တီးသည်။
3. Store Owner က theme/color/font/density ပြင်သည်။
4. Debounced autosave သို့မဟုတ် explicit `Save Draft` ဖြင့် draft table သာ update လုပ်သည်။
5. Customer-facing `storefront_settings` မပြောင်းရ။

### 8.2 Isolated Preview

1. Authenticated Store Manager/Owner သာ preview route ကိုကြည့်နိုင်ရမည်။
2. Preview request တွင် StoreContext နှင့် draft ownership စစ်ရမည်။
3. Preview layout သည် production Storefront components/query services ကိုပြန်သုံးရမည်။ Fake SVG/mockup တစ်ခုတည်း မဖြစ်ရ။
4. Draft config ကို request-scoped ThemeContext ဖြင့် inject လုပ်ရမည်။ Global model/cache/config ကို mutate မလုပ်ရ။
5. Preview response ကို `no-store, private` ထားရမည်။ Search engine indexing ပိတ်ရမည်။
6. Preview URL/token ကို Customer session မှအသုံးမပြုနိုင်ရ။

### 8.3 Publish

1. Draft version နှင့် `base_revision_id` ကို server-side ပြန်စစ်သည်။
2. Published revision ပြောင်းပြီးသားဆိုလျှင် conflict ပြပြီး reload/merge ကို တောင်းရမည်။ Silent overwrite မလုပ်ရ။
3. Full config ကို service layer တွင် ပြန် validate/normalize လုပ်သည်။
4. Transaction ထဲတွင် setting row lock, revision number allocation, published update, revision create, audit log create လုပ်သည်။
5. Commit အောင်မြင်ပြီးမှ related cache invalidation/event dispatch လုပ်သည်။
6. Draft ကို latest published state နှင့် sync/reset လုပ်သည်။

### 8.4 Rollback

- Target revision သည် current Store နှင့်သက်ဆိုင်ကြောင်း server-side စစ်ရမည်။
- Rollback သည် history row အဟောင်းကို update မလုပ်ဘဲ revision အသစ်တစ်ခုဖန်တီးရမည်။
- Exact snapshot restore လုပ်ရမည်။
- Rollback ပြီးလျှင် existing draft ကို restored state မှပြန်စတင်ရန် သို့မဟုတ် stale အဖြစ်ပြရမည်။

---

## 9. Appearance UI Requirements

### 9.1 Non-technical Store Owner Experience

- `Choose Design` → `Customize Brand` → `Preview` → `Publish` အစီအစဉ်ဖြင့် ပြရမည်။
- Technical terms များထက် Myanmar-friendly labels သုံးရမည်။
- Current Published, Unsaved Changes, Draft Saved, Publishing, Conflict, Publish Failed states များကို ထင်ရှားစွာပြရမည်။
- Publish နှင့် Save Draft ကို မရောထွေးစေရ။
- Destructive-looking rollback အတွက် target revision/theme/date/actor ကို confirmation dialog တွင်ပြရမည်။
- Network ပျက်ပါက draft မပျောက်စေရန် retryable error state ပေးရမည်။

### 9.2 Preview Viewports

- Desktop: 1440px-class viewport
- Tablet: 768px-class viewport
- Mobile: 390px-class viewport
- Segmented control ဖြင့်ပြောင်းရမည်။
- Preview frame ကြောင့် outer admin page horizontal overflow မဖြစ်ရ။
- Preview loading/error/empty states များထားရမည်။

### 9.3 Accessibility

- Controls အားလုံး keyboard အသုံးပြုနိုင်ရမည်။
- Color swatch တစ်ခုတည်းဖြင့် state မဖော်ပြရ။ Text/check icon ပါရမည်။
- Focus indicator, accessible label, error association ထည့်ရမည်။
- Myanmar text clipping/line-height ကို 320px အထိစစ်ရမည်။
- Reduced motion preference ကိုလေးစားရမည်။

---

## 10. Storefront Component Architecture

Theme bundle များသည် common view models/data contracts ကို အသုံးပြုရမည်။

Recommended boundaries:

```text
ThemeResolver
ThemeContext
ThemeConfigValidator
ThemeDraftService
ThemePublisher
ThemePreviewController
ThemeRegistry / ThemeManifest

Storefront components:
  header
  navigation
  promotion_strip
  hero_or_banner
  category_navigation
  featured_products
  timed_promotions
  product_grid
  product_card
  trust/payment/delivery
  footer
```

Rules:

- Component သည် `Store` ကို arbitrary query မလုပ်ရ။ Prepared view model/DTO ကိုယူရမည်။
- N+1 query မဖြစ်ရ။ Theme တစ်ခုရွေးခြင်းကြောင့် query count မတိုးသင့်။
- Theme-specific business logic မရေးရ။ Price, stock, promotion, ordering rule များကို existing services မှသာယူရမည်။
- Capability မရှိသော section ကို graceful hide/fallback လုပ်ရမည်။
- Missing image/data အတွက် stable fallback ရှိရမည်။

---

## 11. POS နှင့် Admin Theme Boundary

Storefront branding ကို POS/Admin တစ်ခုလုံးသို့ တန်းကူးမချရ။ Operational UI တွင် readability နှင့် consistency က branding ထက်ဦးစားပေးရမည်။

### Storefront

- Store Owner brand colors/theme bundle အပြည့်အဝ သက်ရောက်နိုင်သည်။

### Admin

- Logo နှင့် restrained accent color သာ optional sync လုပ်မည်။
- Navigation hierarchy, danger/success/warning semantic colors မပြောင်းရ။

### POS

- Storefront Theme နှင့် independent ဖြစ်သော user/device preference သုံးမည်။
- Modes: `standard_light`, `high_contrast_daylight`, `oled_dark`
- Cashier တစ်ဦး၏ preference သည် Storefront published theme/revision မဖြစ်ရ။

---

## 12. Security, Integrity နှင့် Multi-tenant Requirements

- Every read/write query must be store-scoped.
- Route model binding တစ်ခုတည်းမယုံဘဲ service layer ownership check လုပ်ရမည်။
- Preview response ကို public cache/CDN မသိမ်းရ။
- Theme config မှ raw HTML/CSS/JS render မလုပ်ရ။
- CSP ကို မလျှော့ရ။ `unsafe-inline` ထည့်၍ preview ပြဿနာမဖြေရှင်းရ။
- Publish/Rollback/Draft conflict ကို audit log ရေးရမည်။ Draft autosave တိုင်း audit row မရေးဘဲ significant event များသာရေးရန်။
- Revision history immutable ဖြစ်ရမည်။
- File upload validation ကို existing Storefront asset subsystem မှသာအသုံးပြုရမည်။
- Platform Owner support-mode change ကို actor နှင့် support context ပါ audit လုပ်ရမည်။

---

## 13. Performance နှင့် Offline-conscious Requirements

- Storefront Theme resolution က request တစ်ခုလျှင် database query ထပ်မတိုးစေရန် loaded setting/relation ကိုအသုံးပြုရမည်။
- CSS token payload ကို သေးငယ်စွာ inline render လုပ်နိုင်သော်လည်း arbitrary generated stylesheet မတည်ဆောက်ရ။
- Theme JS ကြီးများ၊ animation libraries နှင့် webfont downloads မလိုအပ်ဘဲ မထည့်ရ။
- Myanmar system font fallback မဖြစ်မနေထားရမည်။
- Preview iframe/frame ကို lazy-load လုပ်ရမည်။
- Product images သည် existing optimization/lazy-loading conventions ကိုလိုက်ရမည်။
- Cache စတင်အသုံးပြုပါက key တွင် store ID/slug + published revision ထည့်ပြီး publish commit အပြီး target store cache သာ invalidate လုပ်ရမည်။ `Cache::flush()` မသုံးရ။

---

## 14. Implementation Phases

### Phase T1 — Contract Hardening

> **Status (2026-08-29):** ✅ Complete — ThemeConfig DTO single source, tests green

- [x] Theme config typed DTO/value object တည်ဆောက်ရန် — `App\Themes\ThemeConfig` (canonical 9-field snapshot)
- [x] Single canonical validation rule source — Controller `$request->only(ThemeConfig::SAFE_KEYS)` + `ThemePublisher::publish()` အားလုံး `ThemeConfig::fromArray()` မှတစ်ဆင့်
- [x] Unknown keys: reject + log strategy implement — discard + `Log::debug` (silently persist မလုပ်တော့)
- [x] `schema_version` + `theme_version` fields strategy — `SCHEMA_VERSION=1` + manifest `version`, revision snapshot တွင် နှစ်ခုလုံး ပါ
- [x] ~~Legacy IDs migration/fallback~~ — `$legacyMap` + `allValidIds()` + `ThemeConfigTest` verified
- [x] ~~Token defaults/unknown key behavior test~~ — `ThemeConfigTest` (21 unit tests) standalone ဖြည့်ပြီး

**Exit:** Same input always resolves to one complete canonical snapshot; invalid/unknown values cannot reach rendering.

### Phase T2 — Persistent Draft & Conflict Safety

> **Status (2026-08-29):** ✅ Complete — draft isolation, optimistic lock, conflict detection, UI states, tests green

- [x] `store_theme_drafts` migration/model/relationship
- [x] `ThemeDraftService` create/update/reset/discard + `AppearanceDraftController` JSON API
- [x] Optimistic locking via `lock_version` (stale → 409)
- [x] Base revision conflict detection (409 + GET /draft conflict flag)
- [x] Store-scoped draft routes and authorization (`EnsureStoreAccess:store_manager` + cross-store 403)
- [x] Draft saved/failed/conflict/publishing UI states (badge + banner + publish modal + autosave)
- [x] Rollback → draft reset reconciliation (Plan §8.4) + draft conflict/discard audit (Plan §12)

**Exit:** Store A draft never changes Store A published page or any Store B data. — ✅ verified by `ThemeDraftTest` (19 tests)

### Phase T3 — Real Isolated Preview

> **Status (2026-08-29):** ✅ Complete — request-scoped override, auth-gated no-store route, production components reuse, viewport controls, tests green

- [x] Request-scoped `ThemeContext` + `ResetThemePreview` after-middleware (cross-request leak protected)
- [x] Authenticated/no-store preview route (`/admin/appearance/preview` — `no-store, private` + `X-Robots-Tag: noindex, nofollow`)
- [x] Production Storefront components and real demo/current data reuse — `HomeController@index` delegate, draft tokens via CSS custom properties
- [x] Desktop/Tablet/Mobile segmented viewport controls (1440 / 768 / 390)
- [x] Preview loading state (overlay + 8s fallback); empty states come from the real storefront; error → iframe timeout fallback
- [x] Draft token changes visible without publish — autosave → preview auto-reload

**Exit:** Owner previews all customer-facing routes while anonymous Customer continues seeing exact published revision. — ✅ verified by `ThemePreviewTest` (7 tests) + layout override + browser QA

### Phase T4 — Publish/Rollback Integration Completion

> **Status (2026-08-29):** ✅ Complete — commit-after cache invalidation, publish failure atomicity, confirmation UI, tests green

- [x] Publish directly from selected draft version — draft → publish flow (T2/T3)
- [x] Stale draft conflict rejection — base-revision conflict → 409 + audit (T2)
- [x] Commit-after cache/event handling — `ThemeRevisionCommitted` event → target-store `max-age=0` revalidation window (90s); key `storefront:theme:bumped:{store_id}`; `Cache::flush()` မသုံး
- [x] Rollback/draft reconciliation — rollback ပြီးတိုင်း draft reset (T2)
- [x] Confirmation UI and actor/revision details — modal တွင် current revision → next revision ပြသည်
- [x] Publish failure leaves published config and revision history unchanged — `ThemeCacheInvalidationTest` (failure injection: config + revisions + audit + marker အားလုံး မပြောင်း)

**Exit:** Atomic publish/rollback verified under concurrent and failure tests. — ✅ verified by `ThemeCacheInvalidationTest` (5 tests) + browser QA (publish → max-age=0 + new theme live; rollback → max-age=0 + restored)

### Phase T5 — Layout Bundle Componentization

> **Status (2026-08-29):** ✅ Complete — approved component registry, product-card/nav/header variants, tests + browser QA green

- [x] Common Storefront data/view-model contract — `x-product-card` API unchanged; variant partials consume already-loaded data only
- [x] Header/navigation/home/footer/product-card component registry — `App\Themes\ThemeComponents`
- [x] Five bundles mapped to approved component variants — composition table
- [x] Capability-aware optional sections — `store_can()` gating identical across variants
- [x] No theme-specific catalog/price/stock queries — static registry + shared view data
- [x] Missing content/asset fallback — unknown variant/component → safe default; image fallback

**Exit:** Five themes produce meaningfully different whole-store layouts without duplicating business logic. — ✅ verified by `ThemeComponentsTest` (5) + `ThemeComponentRenderTest` (4) + browser QA (marketplace_pro compact/pill vs midnight_tech showcase/underline/premium)

### Phase T6 — Business Onboarding Recommendation

> **Status (2026-08-29):** ✅ Complete — profile mapping, provisioning-only application, safe defaults, tests green

- [x] BusinessProfile → recommended theme mapping — `ThemeRecommendation` (plan §7)
- [x] Apply only during new store/demo provisioning — `StoreOnboardingService` + demo seeder
- [x] Existing stores are not silently changed — verified by test
- [x] Owner may choose any active theme afterward — recommendation is a default, not an authorization rule
- [x] Unknown/new profile gets safe default — `marketplace_pro`

**Exit:** Every supported profile starts presentably while remaining theme-independent. — ✅ verified by `ThemeOnboardingRecommendationTest` (7 tests)

### Phase T7 — Platform Theme Governance

> **Status (2026-08-29):** ✅ Complete — lifecycle UI, DB-backed overrides, audited, existing stores never break

- [x] Platform Owner theme list/status/version UI — `admin/theme-governance` + sidebar link (en/mm name, id, version, swatches)
- [x] Active/deprecated/hidden lifecycle — `theme_governance` DB overrides + `ThemeGovernanceService`
- [x] Deprecation does not break stores already using theme — deprecated/hidden renderable + publishable (test)
- [x] Migration/fallback path before removal — replacement dropdown + deprecated badge + onboarding fallback chain
- [ ] Preview image (manifest `preview_image` — name_en/name_mm/description ရှိပြီးသား; preview image က future item)

**Exit:** Theme lifecycle can be managed without Store Owner code/file access and without breaking existing stores. — ✅ verified by `ThemeGovernanceTest` (11 tests) + browser QA (hidden theme disappears from picker; platform-only 403)

### Phase T8 — Admin/POS Polish

> **Status (2026-08-30):** ✅ Complete — restrained admin accent, POS 3-mode preference, semantic colors system-controlled, tests + browser QA green

- [x] Restrained Admin brand accent option — `--admin-accent` (store theme primary) → active sidebar link only
- [x] POS personal high-contrast/daylight/OLED preference — `standard_light` / `high_contrast_daylight` / `oled_dark` dropdown
- [x] Semantic colors remain system-controlled — danger/success/warning untouched; `--sf-*` never leaks into admin/POS
- [x] Device preference persistence — localStorage `posDisplayMode`, reload auto-apply (browser QA verified)

**Exit:** Branding is visible but operational readability and safety remain consistent. — ✅ verified by `ThemeAdminPosPolishTest` (5 tests) + browser QA (accent #0ea5e9 on active link; OLED/high-contrast modes apply + persist; POS independent of storefront publish)

### Phase T9 — Production Verification

- [ ] All 5 themes × mobile/tablet/desktop
- [ ] Myanmar/English
- [ ] Home/catalog/product/search/cart/order/how-to-order/contact routes
- [ ] Empty, small, 40+ product, promotion and missing-image datasets
- [ ] Slow network and low-end device smoke test
- [ ] Cross-store isolation and authorization suite
- [ ] Publish/rollback/concurrency/failure suite
- [ ] Deployment restart persistence

**Exit:** Verification matrix and evidence complete; no horizontal overflow, overlap, inaccessible controls, tenant leak or relevant console error.

---

## 15. Required Test Matrix

### Unit Tests

- Registry resolves active, legacy, deprecated and unknown IDs safely.
- Config normalizer returns complete deterministic snapshot.
- Color/font/density/mode validation rejects unsafe values.
- Business profile recommendation is default-only behavior.

### Feature Tests

- Store manager can create/update own draft.
- Staff/customer cannot edit or preview draft.
- Store A cannot read/write/publish/rollback Store B theme data.
- Draft save does not modify `storefront_settings`.
- Publish writes exact setting + revision + audit atomically.
- Publish failure rolls back every database write.
- Stale `lock_version` or `base_revision_id` returns conflict.
- Rollback restores exact snapshot and creates a new revision.
- Deprecated theme remains renderable for existing store.

### Browser Tests

For each active theme:

```text
390x844 mobile
768x1024 tablet
1440x900 desktop
Myanmar locale
English locale
```

Check:

- Correct page/title/content
- No blank screen/framework overlay
- No relevant console errors/warnings
- No page-wide horizontal overflow
- No clipped Myanmar text or overlapping controls
- Theme selection updates preview
- Public Storefront remains unchanged before publish
- Publish updates Storefront
- Rollback restores previous visuals
- Keyboard focus and form errors work

---

## 16. Definition of Done

Feature တစ်ခုကို Done ဟုသတ်မှတ်ရန် အောက်ပါအားလုံး ပြည့်စုံရမည်။

1. Migration/schema/index/foreign keys ရှိသည်။
2. Server-side validation, authorization, store isolation ရှိသည်။
3. Domain service transaction/error handling/audit ရှိသည်။
4. Non-technical Admin UI တွင် loading/error/empty/conflict states ရှိသည်။
5. Public Storefront နှင့် Preview behavior အစအဆုံး အသုံးပြုနိုင်သည်။
6. Unit/Feature tests နှင့် browser desktop/mobile QA အောင်သည်။
7. Existing settings/storefront regression tests မပျက်ပါ။
8. Checklist/document status ကို အမှန်အတိုင်း update လုပ်ထားသည်။

Backend table/model သာရှိခြင်း၊ static mockup သာရှိခြင်း သို့မဟုတ် test မရှိခြင်းကို Done ဟုမသတ်မှတ်ရ။

---

## 17. AI Agent Execution Protocol

Agent တစ်ခုစီသည် အောက်ပါအစီအစဉ်ကိုလိုက်နာရမည်။

1. `AGENTS.md`, ဤ plan, growth checklist နှင့် ဆက်စပ် code/tests ကိုအရင်ဖတ်ရန်။
2. `git status` စစ်ပြီး မိမိမပြုလုပ်သော changes များကို မဖျက်/မပြန်လှန်ရန်။
3. Plan phase တစ်ခုသာရွေးပြီး scope ကိုကြေညာရန်။ Phase များစွာကို တစ်ခါတည်းမရောရန်။
4. Existing class/table/route/package ရှိကြောင်း repository တွင်ရှာပြီးမှအသုံးပြုရန်။ မခန့်မှန်းရ။
5. Database → service → authorization/controller → UI → tests → browser QA အစဉ်လိုက် end-to-end လုပ်ရန်။
6. Existing architecture/helper/components ကိုပြန်သုံးပြီး unnecessary dependency/abstraction မထည့်ရန်။
7. PHP/Blade/JS/CSS arbitrary upload သို့မဟုတ် raw theme code execution မဖွင့်ရန်။
8. Cross-store negative test မပါဘဲ tenant feature ကိုပြီးစီးသည်ဟုမကြေညာရ။
9. UI ပြောင်းလဲမှုတိုင်း desktop/mobile browser QA နှင့် console check လုပ်ရန်။
10. Relevant focused tests အရင်မောင်းပြီး နောက် regression tests မောင်းရန်။
11. Formatting tool ကြောင့် unrelated files/churn မထည့်ရန်။
12. Checklist တွင် fully verified item သာ `[x]` ပြောင်းရန်။ Partial ကို Partial ဟုတင်ပြရန်။

### Agent Handoff Report Format

Agent အလုပ်ပြီးတိုင်း အောက်ပါတို့ကို ပေးရမည်။

```text
Phase / Scope:
Completed:
Not Completed:
Files Changed:
Database Changes:
Authorization & Isolation:
Tests Run + Results:
Browser QA + Viewports:
Known Risks / Assumptions:
Recommended Next Step:
```

---

## 18. Prohibited Implementations

- Business type တစ်မျိုးစီ repository/project copy ထုတ်ခြင်း
- Store Owner executable theme ZIP/PHP/Blade/JS upload
- Theme ID ပေါ်မူတည်ပြီး checkout/price/stock business logic ပြောင်းခြင်း
- Draft save လုပ်ရာတွင် published `storefront_settings` ကို update ခြင်း
- Store scope မပါသော revision/draft query
- Revision row update/delete လုပ်ခြင်း
- Theme တစ်ခုစီအတွက် duplicated product/catalog queries
- Raw user CSS/HTML render ခြင်း
- CSP/authorization လျှော့၍ preview အလုပ်လုပ်စေခြင်း
- Global cache flush
- Platform semantic danger/success/warning colors ကို Store Owner customization လုပ်ခွင့်ပေးခြင်း
- Static mockup ကို production preview အဖြစ်သတ်မှတ်ခြင်း
- Automated tests သာအောင်ပြီး UI ကိုမစစ်ဘဲ Done ဟုကြေညာခြင်း

---

## 19. Immediate Next Work

> **2026-08-30 update:** T1 ✅ + T2 ✅ + T3 ✅ + T4 ✅ + T5 ✅ + T6 ✅ + T7 ✅ + T8 ✅ ပြီးစီးပြီ။ 94 theme tests + full suite 1553 tests pass + browser QA (datapos-mobile) အတည်ပြုပြီး။ Theme Engine implementation phases အားလုံး (T1–T8) ပြီးပြီ — ကျန်တာ **T9 — Production Verification** (browser regression matrix) ဖြစ်သည်။

### T9 — Production Verification (နောက်တစ်ဆင့်)

1. All 5 themes × mobile (390) / tablet (768) / desktop (1440) browser regression — no horizontal overflow, no overlap, no console errors
2. Myanmar/English × home/catalog/product/search/cart/order/how-to-order/contact routes
3. Dataset scenarios: empty, small, 40+ products, promotions, missing images
4. Slow network + low-end device smoke test (Myanmar 2G/3G conditions)
5. Cross-store isolation + publish/rollback/concurrency/failure suite (feature tests ရှိပြီးသား — browser ဖြင့် ပြန်အတည်ပြု)
6. Deployment restart persistence — theme selection survives restart

**မှတ်ချက်:** T1–T8 ပြီးပြီမို့ draft JSON schema ကို finalize လုပ်ထားပြီးပြီ — draft သည် `ThemeConfig::toArray()` 9-field canonical snapshot ဖြစ်သည်။

