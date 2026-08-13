# Multi-Store အဆင့်မြှင့်တင်မှု အစီအစဉ် (Plan)

> **ရည်ရွယ်ချက်:** Admin ကနေ နှိပ်ပြီး store အသစ် တည်ဆောက်/ပြင်/ပိတ်နိုင်အောင် လုပ်ဖို့ + store တစ်ခုထက်ပိုရှိတဲ့အခါ site မပျက်အောင် ပြင်ဖို့။
>
> **ရက်စွဲ:** 2026-08-09 · **Status:** Phase 1 (Resolver Fix) ✅ Done 2026-08-11 · Phase 2 (Admin Store Management UI) ✅ Done 2026-08-11 — Phase 3 (full suite + build + UAT) / Phase 4 (commit → push → deploy) ကျန် · **ဆုံးဖြတ်သူ:** Project Owner (ဆရာကြီး)

---

## ၁။ ဘာကြောင့် ဒီအလုပ်လိုလဲ (နောက်ခံ)

2026-08-09 မှာ local dev DB ထဲ test store ("T" / `t-store`) တစ်ခု ရောက်လာပြီး **root home page (`/`) က empty state ပြပြီး ပျက်သွားခဲ့တယ်**။ အကြောင်းရင်း:

- `ResolveStoreContext` middleware ရဲ့ fallback က **active store တစ်ခုတည်းရှိမှသာ** store ကို ရွေးတယ်။
- Store ၂ ခု active ဖြစ်သွားတဲ့အခါ → **null ပြန်လို့** home page က store context မရှိတော့ဘူး။
- Platform owner က store membership မရှိလို့ user-branch ကလည်း မကယ်နိုင်ဘူး။

**ဆိုလိုတာက** — store အသစ်တွေ တကယ်ဖွင့်မယ်ဆိုရင် ဒီ resolver ကို အရင်ပြင်ရမယ်။ မပြင်ရင် store #2 ဖွင့်တာနဲ့ site ချက်ချင်း ပျက်မယ်။

---

## ၂။ လက်ရှိအခြေအနေ (Audit ရလဒ်)

### ✅ ရှိပြီးသား အရာတွေ

| အပိုင်း | နေရာ | မှတ်ချက် |
|---|---|---|
| Store model (`stores` table) | `app/Models/Store.php` | `name, slug, viber_number, telegram_username, is_active` |
| Per-store settings | `storefront_settings` (HasOne) | store တိုင်း သီးခြားရှိ |
| Path-based routing | `routes/web.php` | `/store/{store_slug}/...` — storefront + admin အကုန် |
| Store context resolver | `app/Http/Middleware/ResolveStoreContext.php` | **ပြဿနာရှိနေတဲ့ နေရာ** |
| Platform owner store selector | `admin/dashboard_select_store.blade.php` + `DashboardController@index` | login → `/admin/dashboard` မှာ store list ပြ → ရွေးပြီး ဝင်လို့ရ |
| Per-store user roles | `UserManagementController` + `EnsureStoreAccess` | `store_manager` / `staff` — platform owner က အကုန်ဝင်လို့ရ |
| CLI store creation | `routes/console.php` → `production:create-store` | **store ဖန်တီးနိုင်တဲ့ တစ်ခုတည်းသော built-in နည်းလမ်း** |
| CLI admin creation | `routes/console.php` → `production:create-admin` | `--store=` + `--role=` နဲ့ attach |
| Store-scoped data အကုန် | products, categories, brands, orders, reviews, blog, banners, payment/delivery methods, glass finder | အကုန် `store_id` နဲ့ ခွဲထားပြီးသား |
| Sitemap per-store | `routes/web.php` sitemap route | active store တိုင်းရဲ့ products/blog URLs ပါ |

### ❌ မရှိသေးတဲ့ အရာတွေ (ဒီအလုပ်မှာ လုပ်ရမယ့်ဟာ)

1. **Admin UI မှာ Store Management ဆိုတဲ့ section လုံးဝမရှိ** — `StoreController` မရှိ၊ `/admin/stores` routes မရှိ၊ sidebar မှာ link မရှိ။ Store ဖန်တီးဖို့ CLI ပဲ သုံးလို့ရတယ်။
2. **Resolver က store ၂+ active ရှိရင် null ပြန်တယ်** — "primary store" concept မရှိ။
3. Store list ကို manage လုပ်တဲ့ view မရှိ (selector က view-only)။

---

## ၃။ အလုပ်အစီအစဉ် (Roadmap — ၄ အဆင့်)

| Phase | အလုပ် | Priority | ဘာကြောင့် |
|---|---|---|---|
| **0** | Baseline — DB backup + လက်ရှိ tests ပြေး | မဖြစ်မနေ | ဘာမှမလုပ်ခင် လုံခြုံမှု |
| **1** | Resolver fix — `is_primary` concept ထည့်ပြီး fallback ပြင်ရေး | **မဖြစ်မနေ (Critical)** | ✅ **Done 2026-08-11** — migration `2026_08_11_000001` + `Store.is_primary` + resolver fallback + dashboard consistency + `StoreContextResolverTest` (6 tests) — changelog item 254 |
| **2** | Admin Store Management UI — list/create/edit/deactivate | Feature | ဆရာကြီးရဲ့ ပင်မ တောင်းဆိုချက် |
| **3** | Tests + Local UAT | မဖြစ်မနေ | regression မဖြစ်အောင် |
| **4** | Deploy + Production verify | — | Deploy #19 ခန့် |

---

## ၄။ Phase 0 — Baseline (မလုပ်ခင်)

```bash
# 1. DB backup (production ဆို ssh ထဲမှာ)
cp database/database.sqlite database/database.sqlite.bak-$(date +%Y%m%d-%H%M)

# 2. လက်ရှိ tests အကုန် ပြေး — green ဖြစ်ကြောင်း သေချာအောင် (လက်ရှိ 557 tests)
D:/xmapp/php/php.exe vendor/bin/phpunit 2>&1 | tail -5

# 3. git status သန့်ကြောင်း စစ် (မလိုအပ်တဲ့ ပြောင်းလဲမှု မရှိစေရ)
git status --short
```

---

## ၅။ Phase 1 — Resolver Fix (အရေးကြီးဆုံး)

### ၅.၁ Migration — `is_primary` column ထည့်ခြင်း

```bash
D:/xmapp/php/php.exe artisan make:migration add_is_primary_to_stores_table
```

`database/migrations/xxxx_add_is_primary_to_stores_table.php`:

```php
Schema::table('stores', function (Blueprint $table) {
    $table->boolean('is_primary')->default(false)->after('is_active');
});
```

ပြီးရင် **လက်ရှိ store (DataPOS) ကို primary အဖြစ် သတ်မှတ်**:

```bash
D:/xmapp/php/php.exe artisan tinker --execute="App\Models\Store::where('slug', 'datapos-mobile')->update(['is_primary' => true]);"
```

> **အရေးကြီး:** production မှာ deploy လုပ်တဲ့အခါ ဒီ update ကို run ရမယ် (deploy script ထဲ ထည့် သို့မဟုတ် SSH နဲ့)။ Primary store တစ်ခုတည်း ရှိရမယ် — နှစ်ခုဆို resolver က ambiguous ဖြစ်နေဦးမယ်။

### ၅.၂ Model update — `app/Models/Store.php`

```php
protected $fillable = [
    'name',
    'slug',
    'viber_number',
    'telegram_username',
    'is_active',
    'is_primary',
];

protected $casts = [
    'is_active'  => 'boolean',
    'is_primary' => 'boolean',
];
```

### ၅.၃ Resolver fix — `app/Http/Middleware/ResolveStoreContext.php`

`resolveFallbackStore()` ထဲက fallback အပိုင်းကို ဒီလို ပြောင်း:

```php
// ရှိရင် primary store ကို ဦးစားပေး
$primary = Store::where('is_active', true)->where('is_primary', true)->first();
if ($primary) {
    return $primary;
}

// မရှိရင် active store တစ်ခုတည်း ရှိမှသာ အသုံးပြု (နောက်ကြောင်း သဟဇာတ)
$activeStores = Store::where('is_active', true)->limit(2)->get();
return $activeStores->count() === 1 ? $activeStores->first() : null;
```

**Logic အကျဉ်း (priority order):**
1. Logged-in user ရဲ့ active store membership → ရှိရင် အဲဒါ
2. **Primary active store → ရှိရင် အဲဒါ** (အသစ်)
3. Active store တစ်ခုတည်း → အဲဒါ (နောက်ကြောင်း သဟဇာတ)
4. ဒါတွေ မရှိရင် → null (error page မဟုတ်ဘဲ ရှင်းရှင်းလင်းလင်း 404/maintenance)

### ၅.၄ (Optional) Dashboard fallback လည်း ပြန်

`DashboardController@index` line 39: `$store = $context->getStore() ?? Store::first();` → `Store::where('is_active', true)->where('is_primary', true)->first() ?? Store::first();` — နှစ်မျိုးလုံး တသမတ်တည်း ဖြစ်အောင်။

---

## ၆။ Phase 2 — Admin Store Management UI

### ၆.၁ Controller — `app/Http/Controllers/Admin/StoreManagementController.php` (အသစ်)

Platform owner **တစ်မျိုးတည်းသာ** ဝင်ခွင့်။ CRUD ၅ ခု:

| Method | Route | အလုပ် |
|---|---|---|
| `index()` | `GET /admin/stores` | Store list (name, slug, active/primary badge, product count, actions) |
| `create()` | `GET /admin/stores/create` | Form |
| `store()` | `POST /admin/stores` | Store + StorefrontSetting တွဲဖန်တီး |
| `edit()` | `GET /admin/stores/{store}/edit` | Form (preselect မှန်အောင်) |
| `update()` | `PUT /admin/stores/{store}` | ပြင် |
| `destroy()` | `DELETE /admin/stores/{store}` | **Deactivate** (hard delete မဟုတ် — data safety) |

**Validation rules** — `routes/console.php` ထဲက `production:create-store` နဲ့ အတူတူ ပြန်သုံး (မကွဲစေရ):

```php
'name'   => ['required', 'string', 'max:255'],
'slug'   => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:stores,slug,' . $store?->id],
'phone'  => ['nullable', 'string', 'max:50', 'regex:/^(\+?95|09)[0-9]{7,11}$/'],
'viber_number' => ['nullable', 'string', 'max:50', 'regex:/^(\+?95|09)[0-9]{7,11}$/'],
'telegram_username' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]{5,32}$/'],
'address' => ['nullable', 'string'],
'opening_hours' => ['nullable', 'string', 'max:255'],
'delivery_info' => ['nullable', 'string'],
'payment_info'  => ['nullable', 'string'],
'default_language' => ['required', Rule::in(array_keys(config('localization.supported', [])))],
'is_active'  => ['boolean'],
'is_primary' => ['boolean'],
```

**store() ထဲက logic** (CLI command နဲ့ အတူတူ — transaction ထဲ):

```php
$store = DB::transaction(function () use ($data) {
    $store = Store::create([...]);
    StorefrontSetting::create([
        'store_id' => $store->id,
        'store_name' => $data['name'],
        'phone' => $data['phone'],
        'viber_number' => $data['viber_number'],
        'telegram_username' => $data['telegram_username'],
        'address' => $data['address'],
        'opening_hours' => $data['opening_hours'],
        'delivery_info' => $data['delivery_info'],
        'payment_info' => $data['payment_info'],
        'default_language' => $data['default_language'],
    ]);
    return $store;
});
```

**သတိထားစရာ:**
- `is_primary` ကို true လုပ်တဲ့အခါ **အခြား store တွေရဲ့ is_primary ကို false ပြန်လုပ်ရမယ်** (primary တစ်ခုတည်း စည်းကမ်း):
  ```php
  Store::where('is_primary', true)->where('id', '!=', $store->id)->update(['is_primary' => false]);
  ```
- **Destroy = deactivate** (`is_active = false`) — orders/reviews/history တွေ ရှိနေနိုင်လို့ hard delete မလုပ်ရ။ (ဆရာကြီး တကယ် hard delete လိုချင်မှသာ သီးခြား confirm flow ထည့်)

### ၆.၂ Routes — `routes/web.php` (global admin group ထဲ)

`/admin/dashboard` ရှိတဲ့ group (`Route::middleware(['auth', SetLocale::class])->prefix('admin')`) ထဲ ထည့်:

```php
Route::middleware('platform_owner')->group(function () {
    Route::get('/stores', [StoreManagementController::class, 'index'])->name('admin.stores.index');
    Route::get('/stores/create', [StoreManagementController::class, 'create'])->name('admin.stores.create');
    Route::post('/stores', [StoreManagementController::class, 'store'])->name('admin.stores.store');
    Route::get('/stores/{store}/edit', [StoreManagementController::class, 'edit'])->name('admin.stores.edit');
    Route::put('/stores/{store}', [StoreManagementController::class, 'update'])->name('admin.stores.update');
    Route::delete('/stores/{store}', [StoreManagementController::class, 'destroy'])->name('admin.stores.destroy');
});
```

- `platform_owner` middleware (အသစ် သို့မဟုတ် inline closure) — `$user->isPlatformOwner()` မဟုတ်ရင် 403။
- **အရေးကြီး:** ဒီ routes တွေက **store-scoped group ထဲ မထည့်ရ** — platform-level ဖြစ်ရမယ် (store context မလို)။

### ၆.၃ Views (အသစ် ၃ ဖိုင်)

- `resources/views/admin/stores/index.blade.php` — table/card list:
  - Store name + slug + **Active/Inactive badge** + **Primary badge** (star)
  - Product count + settings existence
  - Actions: Edit · Deactivate/Activate · "Open Storefront" link (`/store/{slug}`) · "Open Admin" link
- `resources/views/admin/stores/create.blade.php` — form (store + settings fields တစ်ခါတည်း)
- `resources/views/admin/stores/edit.blade.php` — form (existing values preselect — **Phase 1 ရဲ့ brand race lesson ကို သတိရ**: Alpine x-model + x-for ဆို `$nextTick` fix ထည့်)

Design: `layouts/admin/app.blade.php` ရဲ့ design system (`admin-*` classes, hairline, dark mode) သုံး — borderless, clean, full-width ပုံစံ။

### ၆.၄ Sidebar — `resources/views/layouts/admin/app.blade.php`

Platform owner အတွက်သာ **"Store Management"** link ထည့် (selector ဘေးမှာ):

```blade
@if(auth()->user()->isPlatformOwner())
    <a href="{{ route('admin.stores.index') }}" ...>Store Management</a>
@endif
```

### ၆.၅ CLI command တွေကို မဖျက်ရ

`production:create-store` / `production:create-admin` က deploy automation အတွက် အသုံးဝင်နေဆဲ — **ထားရမယ်**။ Admin UI က parallel entry point ပဲ။ (ရွေးစရာ: UI ထဲက store() logic ကို shared service ထုတ်ပြီး CLI + UI နှစ်ခုလုံး သုံးအောင် — Phase 2 ရဲ့ nice-to-have)

---

## ၇။ Phase 3 — Tests (အသစ်ရေးရမယ့်)

### ၇.၁ Resolver tests — `tests/Feature/StoreContextResolverTest.php`

| # | Case | Expected |
|---|---|---|
| 1 | Active store ၂ ခု + primary ၁ ခု | root `/` → primary store ကို ရွေး (site မပျက်) |
| 2 | Primary မရှိ + active ၁ ခု | အဲဒီ store (နောက်ကြောင်း သဟဇာတ) |
| 3 | Primary မရှိ + active ၂ ခု | null → 404/empty state (ရှင်းရှင်းလင်းလင်း) |
| 4 | Logged-in user store membership | user ရဲ့ store ကို ဦးစား |
| 5 | Platform owner login + no membership | primary store ကို ရ |
| 6 | `is_primary` တစ်ခုထဲ သေချာ (two-primary migration case) | first-by-id သို့မဟုတ် deterministic |

### ၇.၂ Store Management tests — `tests/Feature/AdminStoreManagementTest.php`

| # | Case | Expected |
|---|---|---|
| 1 | Non-platform-owner → 403 | staff/store_manager ဝင်လို့မရ |
| 2 | Platform owner → index list ရ | active/inactive/primary badge |
| 3 | Create store → store + settings နှစ်ခုလုံး ဖန်တီး | slug unique, validation |
| 4 | Create store → storefront URL + admin URL အလုပ်လုပ် | `/store/{slug}` 200 |
| 5 | Duplicate slug → validation error | |
| 6 | Edit store → value မပြောင်းဘဲ save → မပျက် | (Phase 1 brand lesson) |
| 7 | is_primary ပြောင်း → အခြား store တွေ false ဖြစ် | primary တစ်ခုတည်း |
| 8 | Deactivate → storefront 404 + admin block | `is_active=false` |
| 9 | Reactivate → ပြန်အလုပ်လုပ် | |
| 10 | Store ဖျက်တာ hard delete မဟုတ် | deactivate ပဲ |

### ၇.၃ Run

```bash
D:/xmapp/php/php.exe vendor/bin/phpunit --filter="StoreContextResolverTest|AdminStoreManagementTest"
D:/xmapp/php/php.exe vendor/bin/phpunit 2>&1 | tail -5   # full suite green
npm run build                                             # CSS/JS build မှန်
```

---

## ၈။ Phase 4 — Deploy + Verification

```bash
# 1. Commit + push
git add -A
git commit -m "feat: multi-store readiness — primary store resolver + admin store management"
git push origin main

# 2. Deploy
./deploy-datapos.sh

# 3. Production DB — migration + primary သတ်မှတ်
ssh -p ***REMOVED*** -i ~/.ssh/***REMOVED*** <host> "cd <app_path> && php artisan migrate --force && php artisan tinker --execute=\"App\Models\Store::where('slug','datapos-mobile')->update(['is_primary'=>true]);\""

# 4. Caches clear
ssh ... "php artisan view:clear && php artisan config:clear && php artisan cache:clear"
```

**Production verify list:**
- [ ] `/` home 200 + products ပေါ် (primary store)
- [ ] `/store/datapos-mobile` 200
- [ ] Admin login → `/admin/stores` 200 (platform owner)
- [ ] Store အသစ် create → storefront URL 200 → admin URL 200
- [ ] Deactivate → storefront 404
- [ ] Store #2 active ဖြစ်နေတုန်း `/` home မပျက် (အရေးကြီးဆုံး — Phase 1 ရဲ့ ရည်ရွယ်ချက်)
- [ ] Sitemap မှာ store #2 ရဲ့ products ပါ
- [ ] Robots.txt မှာ `/store/*/admin` disallow ဆက်ရှိ

---

## ၉။ အန္တရာယ်နဲ့ သတိထားစရာ (Risks)

| Risk | သက်ရောက်မှု | ကာကွယ်နည်း |
|---|---|---|
| **is_primary ၂ ခုဖြစ်နေ** | resolver ambiguous | update လုပ်တိုင်း တစ်ခုတည်းသေချာ (test #6, #7) |
| Store ၂ ခုလုံး active + primary မရှိ | old bug ပြန်ဖြစ် | Phase 1 မှာ primary အမြဲရှိနေအောင် + test #3 |
| Hard delete store | orders/reviews history ပျက် | destroy = deactivate (test #10) |
| Alpine select race (brand lesson) | edit form blank → data ပျက် | `$nextTick` fix pattern + test #6 |
| Store settings မရှိတဲ့ store | storefront ပြဿနာ | create မှာ StorefrontSetting တွဲဖန်တီး (transaction) |
| Cross-store data leakage | privacy | store-scoped queries အကုန် `store_id` filter (EnsureStoreAccess ရှိပြီးသား) |

---

## ၁၀။ Acceptance Checklist (အကုန်ပြီးရင် စစ်ရမယ့်အရာ)

- [ ] Root `/` home — store ၂ ခု active ရှိတုန်းမှာတောင် ပုံမှန် render
- [ ] Admin မှာ "Store Management" section — create/edit/deactivate UI
- [ ] Store အသစ် create လို့ရ — storefront + admin URL တစ်ခါတည်း အလုပ်လုပ်
- [ ] Non-platform-owner က store management ဝင်လို့မရ (403)
- [ ] Deactivate store က storefront မှာ 404 + admin မှာ block
- [ ] Edit လုပ်တဲ့အခါ value မပြောင်းဘဲ save → data မပျက်
- [ ] Full test suite green (557 + အသစ်)
- [ ] `npm run build` မှန် · production deploy ပြီး verify
- [ ] Changelog (`2026-08-02_FIXES.md`) + runbook (Deploy #) မှတ်တမ်းတင်
- [ ] Database migration လုပ်ထားတာ — `is_primary` column ပဲ (schema ပြောင်းစရာ အခြားဘာမှ မရှိ)

---

## ၁၁။ လုပ်ရမယ့်အစဉ် (အကျဉ်း)

```
Phase 0 (backup + baseline tests)
  → Phase 1 (migration → model → resolver → dashboard → tests)
  → Phase 2 (controller → routes → views → sidebar → tests)
  → Phase 3 (full suite + build + local UAT preview)
  → Phase 4 (commit → push → deploy → migrate → primary set → verify)
  → Docs (changelog + runbook)
```

**ခန့်မှန်း စုစုပေါင်း:** Phase 1 (resolver) — ၁ session · Phase 2 (UI) — ၁ session · Phase 3–4 (tests/deploy) — ၁ session။
