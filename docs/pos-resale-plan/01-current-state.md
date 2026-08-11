# ၁။ လုပ်ပြီးသား အခြေအနေ (လက်ရှိ Codebase Audit)

> **ဒီဖိုင်မှာ:** လက်ရှိ ပရောဂျက်မှာ ဘာတွေ ရှိပြီးသားလဲ၊ ဘာတွေ မရှိသေးဘူးလဲ — POS တည်ဆောက်ရေး စတင်ဖို့ အခြေခံအနေနဲ့ သိထားရမယ့်အရာ။
>
> **စစ်ဆေးသည့်ရက်စွဲ:** 2026-08-10 (တိုက်ရိုက် codebase ကြည့်ပြီး ရေးထားသည်) — Revision 2

---

## ၁.၁ နည်းပညာအခြေခံ

| အပိုင်း | အခြေအနေ |
|---|---|
| Framework | **Laravel 12** (PHP ^8.2) |
| Database | Local: **SQLite** · Production: **MySQL** (Hostinger) — နှစ်မျိုးလုံး config ပါပြီးသား |
| Frontend | Blade + **Alpine.js** + **Tailwind CSS v4** (CSS-based `@theme`, config file မရှိ) |
| Assets | Vite — `app.css`/`app.js` (storefront) + `admin.css`/`app-admin.js` (admin) သီးခြားခွဲထားပြီးသား |
| Deploy | `deploy-datapos.sh` — tar + SSH pipe → Hostinger (datapos.com) — **DataPOS ၏ live စမ်းသပ်ဆိုင်** |
| Testing | **608 tests pass (2930 assertions)** — PHPUnit, SQLite local (2026-08-10 စစ်ပြီး) |
| Dev server | `php artisan serve --port=8501` (README/.env အတိုင်း) |

---

## ၁.၂ ရှိပြီးသား (Reuse လို့ရတဲ့အရာတွေ) ✅

### Multi-store အခြေခံ
- `stores` table (`name`, `slug` unique, `is_active`) + `Store` model
- `store_user` pivot — staff access (`role`: `store_manager`, `staff`, `wholesale_customer`, `retail_customer` + `status`)
- `StoreContext` service + `ResolveStoreContext` middleware — path-based store isolation (`/store/{store_slug}/...`)
- Platform owner role (`isPlatformOwner()`)

### Catalog
- `products` — SKU (store-scoped unique), name, slug, category, brand, retail/wholesale price, old_price, sale schedule, **`stock_status` enum (`in_stock`/`out_of_stock`) — လောလောဆယ် manual field**, warranty, return_policy, image
- `product_variants` — variant name, SKU, price (e.g. 256GB / Black)
- `categories` (parent_id nested) + `brands` + `variant_presets`
- Import/Export — CSV/XLSX (products, brands, categories) via `ProductImportService`

### Ecommerce (လက်ရှိ live)
- Storefront: home, products, product detail, glass-finder, order-builder (cart), blog, how-to-order, favorites, reviews, search
- Orders: `orders` + `order_items` — online order flow (Viber/Telegram confirm)
- **PWA** — `public/manifest.webmanifest`, `public/sw.js` (app-shell + web push), install banner
- **Web Push** — push_subscriptions + push_notification_logs, admin push page, Burmese order notifications
- Payments/delivery: `store_payment_methods`, `store_delivery_methods` (Cash/KBZ/Wave/CB/MMQR)
- SEO, CSP nonce, dark/light theme, Burmese/English/Chinese localization

### Admin
- Full admin panel: products, orders, brands, categories, blog, reviews, settings (general/contact/delivery/how-to-order/footer), push, backups, import history
- `can_manage_settings` gate = `store_manager` role (Owner-level)

---

## ၁.၃ မရှိသေးတဲ့အရာများ (POS အတွက် ဆောက်ရမယ့်ဟာ) ❌

| အပိုင်း | အခြေအနေ | မှတ်ချက် |
|---|---|---|
| Branches / Warehouses | **မရှိသေး** | `branches`, `warehouses`, `capabilities`, `branch_capabilities`, `user_branch_roles` tables တွေ ဆောက်ရမယ် (SoT §7) |
| POS sales | **မရှိသေး** | ecommerce `orders` ကို POS sales အဖြစ် ပြန်သုံးလို့ **မရ** (SoT §5) |
| Inventory ledger | **မရှိသေး** | `inventory_movements` + `inventory_balances` — **POS ရော Ecommerce ရဲ့ မျှဝေထားတဲ့ source of truth** ဖြစ်ရမယ် |
| Ecommerce inventory adapter | **မရှိသေး** | `orders`/`order_items` ကို ledger နဲ့ ချိတ်တဲ့ adapter/service — oversell မဖြစ်အောင် |
| UOM / barcode | **မရှိသေး** | piece-based UOM + decimal qty foundation + barcode/HID scan |
| Cashier shift / Opening cash | **မရှိသေး** | MVP (Phase 2) ထဲ ပါမယ် |
| Customer debt / Daily closing | **မရှိသေး** | **MVP (Phase 2) ထဲ ပါမယ်** — Operations phase ထဲ မထားတော့ဘူး |
| Serial/IMEI + warranty | **မရှိသေး** | Mobile/Electronics MVP core |
| Service jobs | **မရှိသေး** | Operations phase (နောက်ပိုင်း) — SoT §16 |
| Devices / offline sync | **မရှိသေး** | Cloud PWA offline queue (Phase 3) — device registration, IndexedDB, idempotent sync API |
| Local LAN edition | **မရှိသေး** | SQLite single-tenant install (Phase 5) — Cloud sync နဲ့ မရော |
| POS routes/UI | **မရှိသေး** | `/pos` + `/pos/admin` — static routes + module middleware |
| Money/rounding policy, costing | **မရှိသေး** | integer MMK, weighted-average cost, negative stock disabled — Phase 0 မှာ သတ်မှတ်မယ် |

---

## ၁.၄ အရေးကြီး မှတ်ချက်များ (Revision 2)

1. **`products.stock_status` က manual field ဖြစ်နေတယ်** — Revision 2 မှာ ledger က source of truth ဖြစ်လာပြီး `stock_status` က **derived compatibility/cache field** အဖြစ် ပြောင်းရမယ်။ Migration ကာလအတွင်း ယာယီသာ — independent competing source of truth အဖြစ် မထားရ။ (SoT §5 နဲ့ ဆန့်ကျင်နေလို့ amendment လိုမယ် — 00-overview ရဲ့ ဆက်စပ်ဖိုင်များ ကြည့်ပါ)
2. **Ecommerce stock က လောလောဆယ် manual** — Viber/Telegram confirm + POS ထဲ manual entry။ New decision မှာ adapter ကတဆင့် ledger နဲ့ ချိတ်မယ် (online reservation/confirmation/cancellation movements)။ `orders` table ကို POS sales အဖြစ် ပြန်မသုံးရဆိုတဲ့ rule က မပြောင်းဘူး
3. **Service worker scope သတိထား** — လက်ရှိ `public/sw.js` က storefront တစ်ခုလုံး (web push ပါ)။ POS အတွက် `/pos/sw.js` သီးခြား scope လုပ်ရမယ် — storefront SW ကို မထိခိုက်စေရ (SoT §4.3)
4. **CSS/JS ခွဲထားပြီးသား** — admin.css/app.css သီးခြားဆိုတဲ့ pattern ရှိပြီးသား → POS လည်း ဒီပုံစံ လိုက်ရမယ်
5. **Multi-store resolver မှာ ပြဿနာရှိနေ** — `docs/multi-store-ready-plan.md` မှာ မှတ်ထားသလို — store ၂ ခု active ဖြစ်ရင် home ပျက်နိုင်တယ် → **SaaS tenant အများကြီး မလုပ်ခင် ဒါ အရင် ပြင်ရမယ်** (Phase 0)
6. **SQLite က local မှာ သုံးနေပြီးသား** — Local LAN edition အတွက် နည်းပညာအရ အလုပ်ဖြစ်နိုင်တယ် (စမ်းပြီးသား) — ဒါပေမဲ့ backup/restore က versioned workflow လိုမယ် (live file copy မဟုတ်ဘူး)
7. **`store_slug` context က path-based ဖြစ်နေတယ်** — Cloud SaaS မှာ tenant အများကြီး ဖြစ်လာရင် resolver ကို ပြန်စစ်ရမယ် (custom domain နောက်ပိုင်းအတွက်)

---

## ၁.၅ အကျဉ်းချုပ်

- **Ecommerce က အပြည့်အဝ လုပ်ပြီးသား + live (608 tests)** — POS အတွက် catalog, store isolation, payment methods, admin ပုံစံတွေ reuse လို့ရတယ်
- **POS က ၀ ကနေ စတင်ရမယ်** — ဒါပေမဲ့ "foundation" တွေ (multi-store, auth, product, payment) အများကြီး ရှိပြီးသားမို့ အခုစရင် မြန်မယ်
- နောက်အဆင့် → `02-target-design.md` ဖတ်ပါ
