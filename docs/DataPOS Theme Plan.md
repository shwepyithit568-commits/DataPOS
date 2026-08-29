# DataPOS Theme System — Long-Term Implementation Plan

> **Document status:** Authoritative implementation plan  
> **Project:** DataPOS single-codebase multi-tenant platform  
> **Primary users:** Platform Owner, Store Owner/Manager, Customer  
> **Target market:** Myanmar SMEs using low-to-mid-range devices and unstable internet  
> **Last verified against repository:** 2026-08-29

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

### 4.1 ရှိပြီးသား

- `App\Themes\ThemeRegistry` နှင့် `ThemeManifest`
- Curated presets: `marketplace_pro`, `retail_trust`, `emerald_fresh`, `midnight_tech`, `sunset_warm`
- Font presets: `outfit`, `inter`, `pyidaungsu`, `padauk`, `system`
- Grid densities: `compact`, `comfortable`
- Store-specific published fields in `storefront_settings`
- CSS custom properties မှတစ်ဆင့် color/font rendering
- `ThemePublisher` transaction-based publish
- Immutable `store_theme_revisions`
- Exact rollback, cross-store protection နှင့် audit logging
- Appearance Settings page နှင့် local component preview

### 4.2 မပြီးသေးသောအပိုင်း

- Published state နှင့်သီးခြားဖြစ်သော persistent Draft
- Real Storefront rendering ကိုသုံးသော isolated preview
- Desktop/Tablet/Mobile preview controls
- Draft conflict/version detection
- Theme manifest version compatibility
- All-theme/all-route browser regression matrix
- POS personal contrast/dark-mode preference
- Platform Owner theme lifecycle UI
- Response cache စတင်အသုံးပြုပါက targeted invalidation

**အရေးကြီး:** Local color mockup ရှိခြင်းကို isolated Storefront Preview ပြီးပြီဟု မသတ်မှတ်ရ။

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

- [ ] Theme config DTO/value object သို့ typed normalizer တည်ဆောက်ရန်
- [ ] Single canonical validation rule source သို့ controller/service duplication ဖယ်ရန်
- [ ] `schema_version` နှင့် `theme_version` strategy သတ်မှတ်ရန်
- [ ] Existing legacy IDs migration/fallback tests ဖြည့်ရန်
- [ ] All token defaults/unknown key behavior test လုပ်ရန်

**Exit:** Same input always resolves to one complete canonical snapshot; invalid/unknown values cannot reach rendering.

### Phase T2 — Persistent Draft & Conflict Safety

- [ ] `store_theme_drafts` migration/model/relationship
- [ ] `ThemeDraftService` create/update/reset
- [ ] Optimistic locking via `lock_version`
- [ ] Base revision conflict detection
- [ ] Store-scoped draft routes and authorization
- [ ] Draft saved/failed/conflict UI states

**Exit:** Store A draft never changes Store A published page or any Store B data.

### Phase T3 — Real Isolated Preview

- [ ] Request-scoped `ThemeContext`
- [ ] Authenticated/no-store preview route
- [ ] Production Storefront components and real demo/current data reuse
- [ ] Desktop/Tablet/Mobile segmented viewport controls
- [ ] Preview loading, empty and error states
- [ ] Draft token changes visible without publish

**Exit:** Owner previews all customer-facing routes while anonymous Customer continues seeing exact published revision.

### Phase T4 — Publish/Rollback Integration Completion

- [ ] Publish directly from selected draft version
- [ ] Stale draft conflict rejection
- [ ] Commit-after cache/event handling
- [ ] Rollback/draft reconciliation
- [ ] Confirmation UI and actor/revision details
- [ ] Publish failure leaves published config and revision history unchanged

**Exit:** Atomic publish/rollback verified under concurrent and failure tests.

### Phase T5 — Layout Bundle Componentization

- [ ] Common Storefront data/view-model contract
- [ ] Header/navigation/home/footer/product-card component registry
- [ ] Five bundles mapped to approved component variants
- [ ] Capability-aware optional sections
- [ ] No theme-specific catalog/price/stock queries
- [ ] Missing content/asset fallback

**Exit:** Five themes produce meaningfully different whole-store layouts without duplicating business logic.

### Phase T6 — Business Onboarding Recommendation

- [ ] BusinessProfile → recommended theme mapping
- [ ] Apply only during new store/demo provisioning
- [ ] Existing stores are not silently changed
- [ ] Owner may choose any active theme afterward
- [ ] Unknown/new profile gets safe default

**Exit:** Every supported profile starts presentably while remaining theme-independent.

### Phase T7 — Platform Theme Governance

- [ ] Platform Owner theme list/status/version UI
- [ ] Active/deprecated/hidden lifecycle
- [ ] Deprecation does not break stores already using theme
- [ ] Migration/fallback path before removal
- [ ] Preview image and Myanmar/English metadata

**Exit:** Theme lifecycle can be managed without Store Owner code/file access and without breaking existing stores.

### Phase T8 — Admin/POS Polish

- [ ] Restrained Admin brand accent option
- [ ] POS personal high-contrast/daylight/OLED preference
- [ ] Semantic colors remain system-controlled
- [ ] Device/user preference persistence

**Exit:** Branding is visible but operational readability and safety remain consistent.

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

နောက် Agent သည် **Phase T1 — Contract Hardening** ကိုအရင်လုပ်ရမည်။ T1 ပြီးမှ **Phase T2 — Persistent Draft & Conflict Safety** သို့ဆက်သွားရမည်။

Priority order:

1. Canonical Theme Config validation/normalization source တစ်ခုတည်းတည်ဆောက်ရန်။
2. Existing controller နှင့် `ThemePublisher` ကို ထို contract အသုံးပြုစေရန်။
3. Complete snapshot/unknown key/legacy ID tests ဖြည့်ရန်။
4. ပြီးမှ `store_theme_drafts` နှင့် optimistic locking တည်ဆောက်ရန်။

ဤအစီအစဉ်သည် invalid/partial config များကို draft/revision ထဲသို့ မရောက်စေဘဲ နောက်အဆင့် Preview နှင့် Layout Bundle များအတွက် ခိုင်မာသော foundation ဖြစ်စေမည်။

