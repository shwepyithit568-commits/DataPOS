# ၁။ လုပ်ပြီးသား အခြေအနေ (လက်ရှိ Codebase Audit)

> **ဒီဖိုင်မှာ:** လက်ရှိ ပရောဂျက်မှာ ဘာတွေ ရှိပြီးသားလဲ၊ ဘာတွေ မရှိသေးဘူးလဲ — POS တည်ဆောက်ရေး စတင်ဖို့ အခြေခံအနေနဲ့ သိထားရမယ့်အရာ။
>
> **စစ်ဆေးသည့်ရက်စွဲ:** 2026-08-10 (တိုက်ရိုက် codebase ကြည့်ပြီး ရေးထားသည်)

---

## ၁.၁ နည်းပညာအခြေခံ

| အပိုင်း | အခြေအနေ |
|---|---|
| Framework | **Laravel 12** (PHP ^8.2) |
| Database | Local: **SQLite** · Production: **MySQL** (CloudBase/Hostinger) — နှစ်မျိုးလုံး config ပါပြီးသား |
| Frontend | Blade + **Alpine.js** + **Tailwind CSS v4** (CSS-based `@theme`, config file မရှိ) |
| Assets | Vite — `app.css`/`app.js` (storefront) + `admin.css`/`app-admin.js` (admin) သီးခြားခွဲထားပြီးသား |
| Deploy | `deploy-datapos.sh` — tar + SSH pipe → Hostinger (datapos.com) |
| Testing | ~588 feature test methods + unit tests — PHPUnit |

---

## ၁.၂ ရှိပြီးသား (Reuse လို့ရတဲ့အရာတွေ) ✅

### Multi-store အခြေခံ
- `stores` table (`name`, `slug` unique, `is_active`) + `Store` model
- `store_user` pivot — staff access (`role`: `store_manager`, `staff`, `wholesale_customer`, `retail_customer` + `status`)
- `StoreContext` service + `ResolveStoreContext` middleware — path-based store isolation (`/store/{store_slug}/...`)
- Platform owner role (`isPlatformOwner()`)

### Catalog
- `products` — SKU (store-scoped unique), name, slug, category, brand, retail/wholesale price, old_price, sale schedule, stock_status (in/out), warranty, return_policy, image
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
| Branches / Warehouses | **မရှိသေး** | `branches`, `warehouses`, `capabilities` tables တွေ ဆောက်ရမယ် |
| POS sales | **မရှိသေး** | ecommerce `orders` ကို POS sales အဖြစ် ပြန်သုံးလို့ **မရ** (SoT §4) |
| Inventory ledger | **မရှိသေး** | `inventory_movements` + `inventory_balances` — products.quantity ပဲ အားကိုးလို့ မရ |
| UOM / fractional qty | **မရှိသေး** | piece/kg/liter — လုပ်ငန်းစုံအတွက် လို |
| Service jobs | **မရှိသေး** | ဖုန်းပြုပြင်ရေး စနစ် — SoT §12 |
| Debt / Finance / Daily closing | **မရှိသေး** | customer receivables, supplier payables, ledger, closing |
| Devices / offline sync | **မရှိသေး** | device registration, IndexedDB, idempotent sync API |
| POS routes/UI | **မရှိသေး** | `/pos` + `/pos/admin` |

---

## ၁.၄ အရေးကြီး မှတ်ချက်များ

1. **Service worker scope သတိထား** — လက်ရှိ `public/sw.js` က storefront တစ်ခုလုံး (web push ပါ)။ POS အတွက် `/pos/sw.js` သီးခြား scope လုပ်ရမယ် — storefront SW ကို မထိခိုက်စေရ (SoT §3.3)
2. **CSS/JS ခွဲထားပြီးသား** — admin.css/app.css သီးခြားဆိုတဲ့ pattern ရှိပြီးသား → POS လည်း ဒီပုံစံ လိုက်ရမယ်
3. **Multi-store resolver မှာ ပြဿနာရှိနေ** — `docs/multi-store-ready-plan.md` မှာ မှတ်ထားသလို — store ၂ ခု active ဖြစ်ရင် home ပျက်နိုင်တယ် → ဖောက်သည်အများကြီး မလုပ်ခင် ဒါ အရင် ပြင်ရမယ်
4. **SQLite က local မှာ သုံးနေပြီးသား** — "offline-only" ဖောက်သည်အတွက် Local mode က ဒီအတိုင်း အလုပ်ဖြစ်နိုင်တယ် (နည်းပညာအရ စမ်းပြီးသား)

---

## ၁.၅ အကျဉ်းချုပ်

- **Ecommerce က အပြည့်အဝ လုပ်ပြီးသား + live** — POS အတွက် catalog, store isolation, payment methods, admin ပုံစံတွေ reuse လို့ရတယ်
- **POS က ၀ ကနေ စတင်ရမယ်** — ဒါပေမဲ့ "foundation" တွေ (multi-store, auth, product, payment) အများကြီး ရှိပြီးသားမို့ အခုစရင် မြန်မယ်
- နောက်အဆင့် → `02-target-design.md` ဖတ်ပါ
