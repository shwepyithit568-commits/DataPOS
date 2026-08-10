# Storefront Customization Roadmap (Admin Owner ထိန်းချုပ်မှု) — 3 Phase

ဤစာရွက်စာတမ်းသည် **Admin Owner** (`store_manager` role) မှ Storefront ၏ အရောင်များ (Colors)၊
စာမျက်နှာများ (Pages) နှင့် အပြင်အဆင် (Layout) တို့ကို Add / Edit / Delete ပြုလုပ်နိုင်ရန်
တည်ဆောက်မည့် အဆင့်သုံးဆင့် (3-Phase) အစီအစဉ် ဖြစ်ပါသည်။

> ⚠️ **ဤ roadmap သည် 2026-08-10 တွင် လက်ရှိ codebase ကို တိုက်ရိုက်စစ်ဆေး (verify) ပြီးမှ**
> ရေးထားခြင်းဖြစ်သည်။ အောက်ပါအချက်အားလုံးသည် လက်တွေ့ file/class/pattern များမှ ကောက်နုတ်ထားပြီး
> မှန်ကန်ကြောင်း အတည်ပြုပြီးဖြစ်သည်။

---

## 1. လက်ရှိ Codebase ၏ အခြေအနေ (Audit — 2026-08-10 တွင် တိုက်ရိုက်စစ်ဆေးပြီး)

| အပိုင်း | အတည်ပြုပြီး အခြေအနေ |
|---|---|
| Store Settings | `App\Models\StorefrontSetting` (`storefront_settings` table) — `store_id` ဖြင့် multi-tenant။ ရှိပြီးသား columns: `store_name, tagline, logo_path, storefront_logo_path, admin_logo_path, favicon_path, address, phone, opening_hours, viber_number, telegram_username, facebook_url, youtube_url, tiktok_url, map_*`, `chat_*`, `delivery_info, payment_info, footer_ad_text, default_language, how_to_intro, how_to_steps, how_to_videos` |
| Admin Settings UI | `App\Http\Controllers\Admin\StoreSettingController` — `SECTIONS = ['general', 'contact', 'delivery', 'how-to-order', 'footer']`။ Section အသစ် ထည့်ရန် (1) `SECTIONS` const (2) `edit()` abort list (3) `update()` ထဲ `match` arm (4) view file — နေရာ ၄ နေရာ ပြောင်းရမည် |
| CSS / Tailwind | **Tailwind v4** — `tailwind.config.js` **မရှိပါ**။ Config ကို `resources/css/app.css` ထဲ `@import 'tailwindcss' source(none)` + `@theme { ... }` ဖြင့် CSS-based ဖြစ်သည်။ Storefront bundle သည် `@source` list (storefront/customer/auth/errors/layouts-storefront/layouts-auth/components/welcome + `../**/*.js`) ကိုသာ scan လုပ်သည် — **admin views များကို storefront CSS ထဲ မထည့်ပါ** |
| Dark/Light | `localStorage('darkMode')` + `.dark` class — `app.css` တွင် `@variant dark (&:where(.dark, .dark *))` |
| Sanitizer | **`App\Support\SafeHtml::sanitize()`** — allow-list DOM sanitizer (DROPPED/ALLOWED/SAFE_ATTRS/SAFE_SCHEMES)။ Product description (storefront + admin preview) တွင် သုံးနေပြီးသား → Pages CMS အတွက် **ဤတစ်ခုတည်းကိုပဲ ပြန်သုံးရမည်** |
| Blog pattern | `AdminBlogController` — slug auto-generate: `Str::slug($title)` → `Post::where('store_id', $storeId)->where('slug', $slug)->exists()` loop ဖြင့် unique လုပ်သည် → **Pages အတွက် အတုယူရမည့် pattern** |
| Home page | **`resources/views/welcome.blade.php` (443 ကြောင်း, single file)** — sections များကို inline ရေးထားသည် (partial မဟုတ်): 1. Hero carousel, 2. Flash Sale, 3. Most Popular Category cards, 5. Featured, 6. New Arrivals။ `routes/web.php` (line 135, 236) မှ `view('welcome', compact('store','setting','banners','categories','categoryTree','featuredProducts','newArrivals','flashSales','upcomingSales','flashTarget','flashTargetStarts'))` |
| Permission | Admin layout: `$canManageSettings = $hasStoreContext && ($adminCanManageSettings ?? false)` — `AppServiceProvider` တွင် `hasStoreRole($store->id, 'store_manager')` ဖြင့် သတ်မှတ်ထား။ **"Owner" = `store_manager` role** (platform_owner သည် global) |
| CSP | `style-src 'self' 'unsafe-inline'` → inline `<style>` block သည် nonce မလို (nonce သည် `<script>` အတွက်သာ) → CSS variables inject လုပ်နိုင်သည် ✓ |
| Localization | `config('localization.supported')` — `lang/{en,my,zh_CN}/messages.php` ၃ ဖိုင် |

### ⚠️ အရေးကြီးဆုံး Constraint (Tailwind v4)

- Tailwind သည် build ချိန်တွင် class များကို generate လုပ်သည် — **Admin မှ ထည့်သွင်းသော arbitrary color ကို**
  runtime တွင် class အသစ်အဖြစ် မဖန်တီးနိုင်။
- ထို့ကြောင့် နည်းလမ်းမှာ — **CSS Custom Properties (variables) ကို `:root` (နှင့် `.dark`) တွင် သတ်မှတ်**ပြီး
  Blade များမှ `bg-[var(--brand-primary)]` / `text-[var(--brand-accent)]` ကဲ့သို့ သုံးရန် ဖြစ်သည်။
- **`npm run build` ပြန်လုပ်ရမည်** — blade/JS အပြောင်းအလဲတိုင်း (Vite + Tailwind v4)။
  Storefront CSS ထဲ class ဝင်ရန် file သည် `@source` list ထဲ ရှိရမည် (အသစ်ဖန်တီးသော partial များကို
  `storefront/**` အောက်တွင် ထားပါ — `@source '../views/storefront/**/*.blade.php'` ပါပြီးသား)။

---

## Phase 1 — Theme Presets (အရောင်စနစ်) — အမြန်ဆုံး၊ အလုံခြုံဆုံး

### ရည်ရွယ်ချက်
Admin Owner မှ Storefront ၏ brand accent အရောင်များကို preset ရွေးချယ်မှုဖြင့် ပြောင်းနိုင်ရန်။

### အကောင်အထည်ဖော်မှု (မှန်ကန်သော နည်းလမ်း)

1. **Migration (၁ ခု):** `storefront_settings` တွင် —
   - `theme_preset` (string, nullable, default `'sky'`)
   - (Optional) `theme_colors` (JSON, nullable — future custom override)

2. **Preset Palette (၅–၈ ခု):** sky (default), emerald, violet, rose, amber, slate —
   preset တစ်ခုချင်းစီက CSS variables ၃–၄ ခုကို သတ်မှတ်ပေးမည်:
   - `--brand-primary` (CTA/active accent)
   - `--brand-primary-hover`
   - `--brand-accent` (secondary gradient end)
   - Dark variant (`.dark` အောက်တွင် override)

3. **Model Helpers:** `StorefrontSetting` တွင် —
   ```php
   public function themeCssVariables(): array  // ['--brand-primary' => '#...', ...]
   ```

4. **Layout Inject:** `resources/views/layouts/storefront/app.blade.php` head တွင် (CSP ခွင့်ပြုထားပြီးသား) —
   ```blade
   <style>
       :root {
           @foreach ($setting->themeCssVariables() as $name => $value)
               {{ $name }}: {{ $value }};
           @endforeach
       }
       .dark {
           @foreach ($setting->themeCssVariables(true) as $name => $value)
               {{ $name }}: {{ $value }};
           @endforeach
       }
   </style>
   ```

5. **Class ပြောင်းလဲမှု — သေးငယ်၍ ဘေးကင်းစွာ (bounded sweep):**
   - **အားလုံးကို တစ်ခါတည်း မပြောင်းပါနှင့်** — storefront တွင် sky/violet/fuchsia class များ အများအပြား ရှိပြီး
     full sweep သည် regression risk မြင့်သည်။
   - **ပထမအသုတ် (Phase 1):** brand accent ပေါ်နေသော မျက်နှာပြင်အနည်းငယ်ကိုသာ ပြောင်းပါ —
     header CTA buttons, search submit, category card active state, cart badge, featured section headers,
     footer accent links (≈ 10–15 နေရာ)။ Neutral slate များ (text/border/background) ကို **မထိပါနှင့်**။
   - နည်းလမ်း ၂ မျိုး: (a) `bg-[var(--brand-primary)]` arbitrary value, သို့မဟုတ်
     (b) `@theme` တွင် semantic token (`--color-brand-primary`) ထည့်၍ `bg-brand-primary` class သုံး —
     (b) က ပိုသန့်သော်လည်း `@theme` ထဲ runtime variable ကို theme-preset `<style>` က လွှမ်းမိုးနိုင်ရန်
     စနစ်တကျ စစ်ရမည်။ **Phase 1 အတွက် (a) ကို အကြံပြုသည်** (localized, low-risk)။

6. **Admin Section အသစ် — `theme`:** `SECTIONS` + `edit()` + `update()` `match` arm + view:
   - `resources/views/admin/settings/sections/theme.blade.php`
   - Preset palette cards (အရောင်နမူနာ swatches ဖြင့် radio)
   - Live preview — `footer.blade.php` section ၏ read-only preview pattern ကို အတုယူပါ
   - `lang/{en,my,zh_CN}/messages.php` ထဲ keys ထည့်ပါ

7. **Validation:** `'theme_preset' => ['required', Rule::in(array_keys(config('storefront.themes', [])))]`
   — theme palette map ကို `config/storefront.php` (အသစ်) တွင် ထားပါ (controller/Model တွင် hardcode မထားပါနှင့်)။
   Custom color ထည့်လျှင် `/^#[0-9a-fA-F]{6}$/` regex validate။

### Phase 1 Risk & ကာကွယ်မှု
- **Risk:** class sweep ကြောင့် button/badge အရောင် မတော်တဆ ပြောင်းခြင်း။
  → ပြောင်းလဲမှု တစ်ခုချင်းကို browser screenshot (light + dark, mobile + desktop) ဖြင့် စစ်ပါ။
- **Risk:** Tailwind v4 arbitrary value ကို build မှာ မကောက်မိခြင်း → `npm run build` ပြီးတိုင်း
  ထွက်လာသော CSS ထဲ variable class ရှိမရှိ grep စစ်ပါ။
- **Rules:** `store_id` isolation · `store_manager` authorization · translation ၃ ဘာသာ · SQLite+MySQL
  compatible migration (add column — safe) · migration မလိုအပ်သော update path များ မပြောင်းပါ။

### Phase 1 Affected Files (ခန့်မှန်း)
- `database/migrations/*_add_theme_to_storefront_settings_table.php`
- `config/storefront.php` (အသစ် — palette map)
- `app/Models/StorefrontSetting.php` (helpers)
- `app/Http/Controllers/Admin/StoreSettingController.php`
- `resources/views/admin/settings/sections/theme.blade.php`
- `resources/views/layouts/storefront/app.blade.php` (variable inject + ≤15 class swaps)
- `resources/css/app.css` (optional — semantic tokens)
- `lang/{en,my,zh_CN}/messages.php`

---

## Phase 2 — Pages CMS (Add / Edit / Delete)

### ရည်ရွယ်ချက်
Admin Owner မှ custom pages (About, FAQ, Policy ...) ဖန်တီး/တည်းဖြတ်/ဖျက်နိုင်ရန် — blog pattern အတိုင်း။

### အကောင်အထည်ဖော်မှု

1. **Migration (၁ ခု):** `pages` table —
   `id, store_id (FK→stores), slug, title, content (longText), meta_description (nullable),
   is_published (bool, default true), sort_order (int, default 0), timestamps`
   Index: unique `(store_id, slug)`, `(store_id, is_published)` — MySQL + SQLite compatible။

2. **Model:** `app/Models/Page.php` — `Store::hasMany(Page)`, `Page::belongsTo(Store)`,
   `$fillable`, `$casts = ['is_published' => 'boolean']`။

3. **Admin Controller:** `app/Http/Controllers/Admin/PageController.php` — CRUD:
   - `index/create/store/edit/update/destroy` — store-scoped (`StoreContext` service ဖြင့်)
   - Slug: **blog အတိုင်း** — `Str::slug($title)` → `Page::where('store_id',$storeId)->where('slug',$slug)->exists()` loop
   - **Authorization:** `store_manager` role သာ (layout ၏ `$canManageSettings` gate နှင့် တူညီ) —
     controller/middleware အဆင့်တွင် `abort_unless` ထည့်ပါ (UI hide တစ်ခုတည်း မလုံလောက်)

4. **Storefront:** `GET /pages/{slug}` (store-scoped route) → `Storefront\PageController@show`
   - `is_published = true` သာ · `store_slug` isolation (StoreContext) · not found → 404

5. **Content Security:** `App\Support\SafeHtml::sanitize($content)` **သေချာပေါက်** သုံးပါ —
   storefront တွင် `{!! SafeHtml::sanitize($page->content) !!}` ဖြင့် render (raw render မလုပ်ပါနှင့်)။
   `meta_description` → existing `SeoMeta` helper pattern (product page ကဲ့သို့ strip + truncate)။

6. **Admin Views:** `resources/views/admin/pages/{index,form}.blade.php`
   - Form: title, slug (auto-fill), rich-text editor (**product description editor ကို reuse** — content field
     submission name ကို product form ၏ ပုံစံအတိုင်း ထားပါ), meta_description, published toggle, sort_order
   - Sidebar: Settings group အောက်တွင် "Pages" link — `@if ($canManageSettings)`

7. **Footer Links (Optional):** Settings → Footer section တွင် "ပြရန် Pages" multi-select —
   ရွေးထားသော published pages များကို footer မှာ ထည့်ပေးပါ။

### Phase 2 Risk & ကာကွယ်မှု
- **Risk:** XSS — `SafeHtml` ကို ကျော်လွန်၍ raw render လုပ်မိခြင်း → sanitizer ကို save + render
  နှစ်နေရာလုံးတွင် သေချာသုံးပါ (သို့) store ချိန်တွင်သာ sanitize ပြီး render ချိန်တွင် ထပ်မသုံးပါ။
- **Risk:** slug ထပ်ခြင်း / store လွဲခြင်း → unique `(store_id, slug)` + blog loop pattern + StoreContext။
- **Risk:** multi-store data leak → ရှိပြီးသား blog/order controllers ၏ store-scope ပုံစံအတိုင်း အတိအကျ လိုက်နာပါ။

### Phase 2 Affected Files (ခန့်မှန်း)
- `database/migrations/*_create_pages_table.php`
- `app/Models/Page.php`
- `app/Http/Controllers/Admin/PageController.php` + `app/Http/Controllers/Storefront/PageController.php`
- `routes/web.php`
- `resources/views/admin/pages/*.blade.php`
- `resources/views/storefront/pages/show.blade.php`
- `resources/views/layouts/admin/app.blade.php` (sidebar link)
- `resources/views/layouts/storefront/app.blade.php` / footer (optional links)
- `lang/{en,my,zh_CN}/messages.php`

---

## Phase 3 — Home Layout Control (Section Show/Hide + Order)

### ရည်ရွယ်ချက်
Admin Owner မှ Home page section များကို ပြ/မပြ + အစီအစဉ် ထိန်းချုပ်နိုင်ရန်။

### ⚠️ လက်ရှိ အတားအဆီး (Verified)
`welcome.blade.php` သည် **443 ကြောင်း single file** ဖြစ်ပြီး section များကို inline ရေးထားသည် —
partials မဟုတ်ပါ။ ထို့ကြောင့် section control မလုပ်မီ ရွေးစရာ ၂ မျိုး ရှိသည်:

- **Option A (အကြံပြု): Section extraction** — section တစ်ခုချင်းစီကို
  `resources/views/storefront/sections/{hero,flash-sale,category-cards,featured,new-arrivals}.blade.php`
  အဖြစ် ခွဲထုတ်ပါ (`@source '../views/storefront/**/*.blade.php'` ထဲ ပါပြီးသား — build OK)။
  **ပထမဆုံး "extract only — logic မပြောင်း" commit** လုပ်ပြီး storefront render မပျက်ကြောင်း
  screenshot/test ဖြင့် အတည်ပြုမှ နောက်တစ်ဆင့် ဆက်လုပ်ပါ။
- **Option B (ပိုလွယ်): Inline `@if`** — section တစ်ခုချင်းစီကို
  `@if ($homeSectionVisible('flash_sale'))` စသည်ဖြင့် ရစ်ပတ်ပါ (extraction မလို၊ သို့သော်
  file ကြီးနေဆဲ)။

### အကောင်အထည်ဖော်မှု (Option A အတိုင်း)

1. **Section keys စာရင်း (config/storefront.php):**
   `['hero_banners', 'flash_sale', 'category_cards', 'featured_products', 'new_arrivals', 'blog_posts']`

2. **Storage:** `storefront_settings` တွင် `home_sections` JSON column —
   ```json
   [{"key":"category_cards","visible":true,"sort":2}, ...]
   ```
   (ရှေ့နောက် table `home_sections` ထက် JSON က ဒီ scale အတွက် လုံလောက်ပြီး migration ပိုပေါ့သည်)

3. **Model Helper:** `StorefrontSetting::homeSections(): array` — default (အကုန် visible, လက်ရှိ order)
   နှင့် merge လုပ်ပြီး return။ Valid keys မဟုတ်ပါက filter။

4. **Home View:** `welcome.blade.php` —
   ```blade
   @foreach ($homeSections as $section)
       @if ($section['visible'] && view()->exists("storefront.sections.{$section['key']}"))
           @include("storefront.sections.{$section['key']}")
       @endif
   @endforeach
   ```
   Controller က `$homeSections = $setting->homeSections()` ကို compact ထဲ ထည့်ပေးရမည်
   (routes/web.php line 135, 236 — နှစ်နေရာ)။

5. **Admin UI:** Settings → `layout` section —
   - Section list (locale-aware title + description) · show/hide toggle · Up/Down order buttons
   - **Drag-and-drop မလိုပါ** — mobile-friendly reorder buttons က လုံလောက်သည်
   - Section "Delete" ဆိုသည်မှာ **hide** သာ — section data (banners/products) ကို တကယ်မဖျက်ပါ

6. **Validation:** `home_sections` → `array` + key `Rule::in(config('storefront.home_sections'))` +
   duplicate/unknown key filter ပြီး save။

### Phase 3 Risk & ကာကွယ်မှု
- **Risk (အကြီးဆုံး):** section extraction ကြောင့် home page layout ပျက်ခြင်း
  → "extract only" commit ကို logic change နှင့် ခွဲပြီး screenshot diff (light/dark, mobile/desktop)
  ဖြင့် စစ်ပါ။
- **Risk:** section keys နှင့် blade files ကွဲခြင်း → `view()->exists()` fallback + config ကို
  source of truth အဖြစ် ထားပါ (view မရှိသော key ကို skip)။
- **Risk:** controller compact list မေ့ခြင်း → routes/web.php ၏ home route ၂ နေရာလုံး (line 135, 236)
  update လုပ်ရန် checklist ထားပါ။

### Phase 3 Affected Files (ခန့်မှန်း)
- `config/storefront.php` (section keys)
- `database/migrations/*_add_home_sections_to_storefront_settings_table.php`
- `app/Models/StorefrontSetting.php`
- `routes/web.php` (home route compact ၂ နေရာ)
- `resources/views/welcome.blade.php` (extraction) + `resources/views/storefront/sections/*.blade.php` (အသစ် ၅–၆ ခု)
- `app/Http/Controllers/Admin/StoreSettingController.php` (layout section)
- `resources/views/admin/settings/sections/layout.blade.php`
- `lang/{en,my,zh_CN}/messages.php`

---

## လိုက်နာရမည့် အခြေခံစည်းမျဉ်းများ (Phase အားလုံး)

- `store_id` / `store_slug` isolation မပျက်စေရ — store တစ်ခု၏ အပြောင်းအလဲ အခြားကို မထိရပါ
- Livewire / jQuery မသုံးပါနှင့် — Alpine.js + Blade သာ
- Blade user-facing text ကို hardcode မရေးပါ — `lang/{en,my,zh_CN}` ၃ ဖိုင်လုံး update
- SQLite (local) + MySQL (production/CloudBase) compatible migration
- Authorization: `store_manager` (`$canManageSettings` gate) — UI hide သာမက controller/middleware အဆင့်ပါ
- Security: `SafeHtml::sanitize()` (rich text), `SeoMeta` (meta), CSP nonce pattern မဖျက်
- Performance: N+1 / missing index / unpaginated query များ ရှောင်ပါ
- Migration/schema change + 5 files ကျော် ထိလျှင် — Affected Files / Approach / Risks ကို
  **အရင်တင်ပြပြီး confirmation ယူမှ** ဆက်လုပ်ပါ
- Broad rewrite ထက် minimal safe implementation ကို ဦးစားပေးပါ

## Implementation ပြီးပါက (Checklist)

1. Phase သက်ဆိုင်ရာ test ရေး/run: storefront render, admin UI, store isolation, authorization,
   sanitizer, slug uniqueness
2. Browser verification: light + dark theme, mobile (360px) + tablet + desktop screenshot diff
3. Regression risk စစ် (full test suite + preview)
4. `2026-08-02_FIXES.md` Item အသစ်ဖြင့် update
5. `Testing_check.md` update (သက်ဆိုင်ပါက)
6. Business/Architecture Rule ပြောင်းမှသာ `Source_of_Truth.md` update

## လုပ်ငန်းစဉ် (Priority Order)

1. **Phase 1 — Theme Presets:** အမြန်ဆုံး အကျိုးရှိဆုံး — bounded class sweep + CSS variables
2. **Phase 2 — Pages CMS:** blog pattern အတိုင်း — စာမျက်နှာ CRUD
3. **Phase 3 — Home Layout:** section extraction (အရင်) → show/hide + order (နောက်)
