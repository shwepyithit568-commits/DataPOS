# DataPOS POS + Resale Plan — ခြုံငုံဖတ်ရန်

> **ရည်ရွယ်ချက်:** ဒီဖိုဒါမှာ DataPOS ရဲ့ **POS စနစ်** ကို ဘယ်လို တည်ဆောက်မယ်၊ ကိုယ့်ဆိုင်မှာ ဘယ်လို သုံးမယ်၊ ပြီးတော့ **အခြားလုပ်ငန်းရှင်တွေကို ပြန်ရောင်းချ** မယ့် ပုံစံတွေကို ရှင်းပြထားတဲ့ စာရွက်စာတမ်းတွေ ဖြစ်ပါတယ်။
>
> **ဖတ်ရမယ့်သူ:** Project Owner (ဆရာကြီး) — နားလည်ပြီး ဆွေးနွေးဖို့အတွက်
>
> **ရက်စွဲ:** 2026-08-10 (Revision 2) · **2026-08-13:** `03-sales-market-model.md` ကို ဒီဖိုင်ထဲ ပေါင်းထည့်ပြီးပါပြီ (အောက်က "Sales & Market Model" section) · **2026-08-17:** POS cashier session (13 commits) အခြေအနေ ထည့်သွင်းပြီး (§1.2)

---

## ဖိုင်တွေရဲ့ အကြောင်းအရာ

| ဖိုင် | အကြောင်းအရာ | ဘာတွေသိရမလဲ |
|---|---|---|
| `ROADMAP.md` | **ခြုံငုံ အစီအစဉ် (လုပ်ပြီးသား + ဆက်ဆောက်မယ့်ဟာ)** | လက်ရှိ အခြေအနေ (Ecommerce, multi-store, PWA, POS Phase 1–2.5p1 + **Cashier Home UI + cashier session (register-lock UX, held-sale expiry, shared customers, tiered pricing, price override, manager PIN, drag-to-scroll)** — **867 tests pass 2026-08-17**) + Implementation Phases: Phase 0 (Decisions) → 1 (Foundation) → 2 (Online POS MVP) → 2.5 (AlinnThit Pilot) → 3 (Cloud PWA offline) → 4 (Operations) → 5 (Local + Resale) → 6 (Industry packs) |
| `02-target-design.md` | **လိုချင်တဲ့ ပုံစံ** | Architecture — တစ်ခုတည်းသော codebase, deployment model ၂ မျိုး (Cloud SaaS / Local install), shared inventory ledger, money/rounding policy, POS sale state machine, cashier shift |
| ~~`03-sales-market-model.md`~~ | ~~စျေးကွက်အလိုက် ရောင်းချ/ထိန်းချုပ်ပုံ~~ | **2026-08-13: ဒီ `ROADMAP.md` ထဲ ပေါင်းပြီး** — အောက်က "Sales & Market Model" section ကြည့်ပါ |

---

## အနှစ်ချုပ် — အဓိက ဆုံးဖြတ်ချက်တွေ (Revision 2)

1. **Codebase တစ်ခုတည်း** — Ecommerce ရော POS ရော ဒီပရောဂျက်ထဲမှာပဲ ဆောက်မယ် (SoT §4.1)။ Module isolation: `App\POS\...` + `/pos` routes + သီးခြား SW/CSS/JS/tests
2. **Deployment model ၂ မျိုး — တစ်ခုနဲ့တစ်ခု မရောရ:**
   - **Cloud ဖောက်သည် = Multi-tenant SaaS** — ဗဟို application တစ်ခုတည်း၊ store/tenant အများကြီး၊ တင်းကျပ်သော `store_id` isolation၊ store အလိုက် enabled modules၊ Platform Owner စီမံ၊ Store Owner က သူ့ဆိုင်ပဲ မြင်၊ custom domain နောက်မှ ထည့်နိုင်
   - **Local ဖောက်သည် = Single-tenant install** — ဖောက်သည်တစ်ယောက် installation တစ်ခု၊ Laravel + SQLite၊ ဆိုင်ထဲ PC/LAN၊ အမြဲ internet မလို၊ resale နောက်ပိုင်းမှ signed offline license၊ versioned backup/restore/update workflow
3. **Inventory ledger က POS ရော Ecommerce ရဲ့ တစ်ခုတည်းသော stock source of truth** — `inventory_movements` (immutable) + `inventory_balances` (derived cache)။ Ecommerce orders ကို adapter/service ကတဆင့် integrate — POS/Ecommerce နှစ်ခုလုံး တူညီတဲ့ stock ကို oversell မလုပ်နိုင်။ `products.stock_status` က migration ကာလအတွင်း derived compatibility/cache field အဖြစ်သာ ကျန်ရစ်မယ်
4. **Module/capability enforcement — static routes + server-side middleware** — tenant ပေါ်မူတည်ပြီး route တွေကို conditionally register **မလုပ်ရ** (route caching နဲ့ မကိုက်ညီလို့)။ Module enabled → branch capability → user permission → approval permission — အဆင့် ၄ မျိုး သီးခြား ခွဲထားပြီး server-side က authoritative (UI hide မလုံလောက်)
5. **Money/quantity — float မသုံးရ** — MMK ကို integer (ကျပ်) သို့မဟုတ် decimal ဖြင့် သိမ်း၊ discount/tax rounding order သတ်မှတ်၊ weighted-average costing (ကနဦး Mobile/Electronics MVP)၊ negative stock default ပိတ်
6. **MVP scope ပြင်ဆင်** — Customer Debt + Cashier Shift + Daily Closing တွေကို နောက်ကျ Operations phase ထဲ မထားတော့ဘူး — **Online POS MVP (Phase 2) ထဲ ထည့်မယ်** (မြန်မာ့ဈေးကွက်ရဲ့ မဖြစ်မနေ selling feature)
7. **Offline system ၂ မျိုး သီးခြားခွဲ** — (a) Cloud PWA offline queue (IndexedDB + sync API) နဲ့ (b) Local LAN/SQLite install — phase တစ်ခုထဲ မရော။ Order: Online Cloud POS → AlinnThit Pilot → Cloud PWA offline → Local LAN edition → cloud-to-local sync (demand ရှိမှ)
8. **AlinnThit production pilot က resale မလုပ်ခင် မဖြစ်မနေ** — real data, parallel validation, reconciliation, real cashier usage, backup/restore test — pilot မတည်မငြိမ်ခင် ပြင်ပဖောက်သည်ကို မရောင်းရ
9. **ပထမဆုံး product = Mobile/Electronics POS** — SKU/barcode, variants, serial/IMEI, warranty, retail/wholesale, customer debt, receiving, branch/warehouse inventory, returns/exchanges — ကျန် industry packs (ဆေး/ကုန်စုံ/ရွှေ/စားသောက်ဆိုင်/ဓာတ်ဆီ/အဝတ်အထည်) က ဖောက်သည်အစစ် ပေါ်လာမှ
10. **Platform Owner support access ကို explicit workflow နဲ့** — Store Support Mode: reason + start/end time + write အကုန် audit + active store ကို ရှင်းရှင်း ပြ + accidental cross-store write ကာကွယ် + finance/export တွင် ပိုတင်းကျပ်

---

## ဖတ်ရန် အစီအစဉ်

1. `ROADMAP.md` ကစဖတ်ပါ (ဘာရှိပြီးသားလဲ + ဘယ်ကစမလဲ သိအောင်)
2. `02-target-design.md` ဖတ်ပါ (ဘယ်ကို ဦးတည်နေလဲ သိအောင်)
3. ဒီဖိုင်ရဲ့ အောက်က "Sales & Market Model" + "Implementation Phases" section တွေ ဖတ်ပါ (ဘယ်လို ရောင်းမလဲ + ဘယ်ကစမလဲ သိအောင်)

---

## ဆက်စပ်ဖိုင်များ

- `Source_of_Truth_MM.md` / `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` — POS စနစ်ရဲ့ အခြေခံ စည်းမျဉ်း (အရေးအကြီးဆုံး — ဒီပြင်ဆင်ချက်တွေနဲ့ ဆန့်ကျင်နေတဲ့ အခန်းတွေ ရှိနေလို့ amendment လိုအပ်မယ်)
- `CHANGELOG.md` — implementation history / changelog (items 1–271)
- `docs/archive/deployment-runbook.md` — အရင် site ရဲ့ deploy history (archived 2026-08-13)
- `docs/ops/DEPLOYMENT.md` — deploy / backup-restore / secrets scrub မှတ်တမ်း


---

# ၃။ စျေးကွက်အလိုက် ရောင်းချ/ထိန်းချုပ်ပုံ (Sales & Market Model)

> **ဒီဖိုင်မှာ:** ဒီစနစ်ကို ဖောက်သည်တွေဆီ ဘယ်လို ရောင်းမလဲ — ဘယ်သူတွေ ဝယ်မလဲ၊ ဘယ်လို ထိန်းချုပ်မလဲ၊ စျေးနှုန်း ဘယ်လို သတ်မှတ်မလဲ။
>
> **Revision 2 (2026-08-10):** Deployment model ၂ မျိုး (Cloud SaaS / Local install) ကို ရှင်းရှင်းခွဲပြီး — "cloud ဖောက်သည်တိုင်းအတွက် သီးခြား deploy" ဆိုတဲ့ အဟောင်းပုံစံကို ဖျက်လိုက်ပြီ။

---

## ၃.၁ ဖောက်သည်အမျိုးအစား — Deployment model နဲ့ module ပေါင်းစပ်

| ဖောက်သည် | Deployment model | Module ဖွင့်ထားမယ့်အရာ |
|---|---|---|
| **Online ပဲလိုတဲ့သူ** | Cloud SaaS (tenant row) | `ecommerce` — လက်ရှိ storefront အတိုင်း |
| **POS ပဲလိုတဲ့သူ (internet ရှိ)** | Cloud SaaS (tenant row) | `pos` + sub-modules |
| **နှစ်ခုလုံးလိုတဲ့သူ** | Cloud SaaS (tenant row) | `pos` + `ecommerce` + shared inventory |
| **Offline ပဲလိုတဲ့သူ (ဆိုင်ထဲ PC)** | **Local single-tenant install** | `pos` + sub-modules — SQLite, LAN |

> ဖောက်သည်တစ်ယောက် = Cloud မှာ tenant row တစ်ခု (သို့) Local install တစ်ခု။ "Cloud ဖောက်သည်တစ်ယောက်စီအတွက် သီးခြား server/deploy" မလုပ်ရ။

---

## ၃.၂ လုပ်ငန်းအမျိုးအစား (Industry) — Mobile/Electronics က ပထမဆုံး

| လုပ်ငန်း | ကိုက်ညီမှု | Pack / Extension |
|---|---|---|
| **ဖုန်းဆိုင် / အီလက်ထရွန်းနစ်** | ✅ **ပထမဆုံး product** (ကိုယ့်ဆိုင်) | Serial/IMEI + warranty + service jobs (နောက်ပိုင်း) |
| **ဆေးဆိုင်** | 🔜 နောက်ပိုင်း | Expiry + batch/lot — demand ပေါ်မှ |
| **ရွှေဆိုင် / ဂျူးရတနာ** | 🔜 နောက်ပိုင်း | Weight pricing (ကျပ်/ပဲ/ရွေး) + daily rate + karat |
| **ကုန်စုံဆိုင်** | 🔜 နောက်ပိုင်း | Expiry + weight scale + multi-unit |
| **စားသောက်ဆိုင်** | 🔜 နောက်ပိုင်း | Table + KOT + combo |
| **ဓာတ်ဆီဆိုင်** | 🔜 နောက်ပိုင်း | Liter qty + fuel grade + pump |
| **အဝတ်အထည်** | 🔜 နောက်ပိုင်း | Size/color matrix + seasonal |
| **ပွဲရုံ / ခန်းမ** | ❌ အနည်းဆုံး | Booking/calendar module — သီးခြား လိုမယ် |

> **မူ:** Architecture က forward-compatible (UOM, decimal qty, custom fields) — ဒါပေမဲ့ pack တွေက **ဖောက်သည်အစစ် ပေါ်လာမှသာ** ဆောက်မယ်။ Pharmacy/Grocery/Gold/Restaurant/Fuel/Fashion ကို first-release မှာ မထည့်ရ။

---

## ၃.၃ ရောင်းချ/Install ပုံစံ — Model အလိုက်

### Cloud SaaS ဖောက်သည်အသစ် (Model A)

```
ဖောက်သည်အသစ် ရောက်လာရင်:
1. ဗဟို SaaS app ထဲမှာ php artisan store:create  (name, slug, plan)
2. enabled_modules သတ်မှတ် (pos / ecommerce / both)
3. Store owner account ဖန်တီး
4. ဆိုင်ဒေတာ ထည့်သွင်း (products import, branches → default branch/warehouse auto)
5. Deploy မလို — tenant row တစ်ခုပဲ
```

### Local install ဖောက်သည် (Model B)

```
ဖောက်သည်အသစ် ရောက်လာရင်:
1. Install package (Laravel + SQLite) ကို ဆိုင်ထဲ PC ပေါ် install
2. Setup wizard — store name, admin account, default branch/warehouse
3. License activation — resale နောက်ပိုင်းတွင် signed offline license
4. ဆိုင်ဒေတာ ထည့်သွင်း
5. Update/backup → versioned workflow (02-target-design §2.15)
```

### Install mode ရွေးစရာ

| Mode | ဘယ်သူ့အတွက် | Server ဘယ်မှာ | Internet |
|---|---|---|---|
| **Cloud (multi-tenant SaaS)** | internet ရှိတဲ့သူ, online လိုသူ | ဗဟို cloud app တစ်ခု (Hostinger...) | လို |
| **Local (Windows PC / LAN)** | offline ပဲလိုတဲ့သူ | ဆိုင်ထဲက PC — SQLite + LAN | မလို |

---

## ၃.၄ License / ထိန်းချုပ်မှု ပုံစံ

| အပိုင်း | Cloud mode | Local mode |
|---|---|---|
| License check | Online activation (server ကို မေး) | **Signed offline license** (public-key verify — private key ကို install ထဲ မထည့်ရ) — Resale Readiness (Phase 5) |
| Update | တစ်ခါ deploy → tenant အကုန် | Versioned update workflow — ဆိုင်တစ်ဆိုင်ချင်းစီ |
| Backup | Central daily + runbook | `php artisan backup` → versioned (snapshot + checksum + manifest) |
| Branch ပေါင်း | Multi-tenant app ထဲပဲ — branch capability ဖွင့် | Manual (versioned restore) သို့မဟုတ် single-branch သာ |

### License plan အဆင့် (ဥပမာ — ဆရာကြီး ဆုံးဖြတ်ရမယ်)

| Plan | ပါဝင်မှု | သင့်တော်တဲ့သူ |
|---|---|---|
| POS Basic | POS + inventory + single branch | ဆိုင်ငယ် |
| POS Pro | POS + debt + finance + daily closing | ဆိုင်လတ် |
| Ecommerce | Online storefront + orders | Online ပဲလိုသူ |
| Complete | အကုန် — multi-branch | ဆိုင်ကြီး / franchise |

> **အရေးကြီး:** Plan တွေက codebase မပြောင်း — `enabled_modules` ပဲ ပြောင်းတယ်။ Upgrade ဆိုရင် flag ဖွင့်ပေးရုံပဲ။

---

## ၃.၅ Platform Owner vs Store Owner — Support Access Workflow

- **Platform Owner (ဆရာကြီး):** SaaS app တစ်ခုလုံးကို စီမံ — plan, module flags, support
- **Store Owner (ဖောက်သည်):** သူ့ဆိုင်တစ်ခုပဲ — staff, products, POS

**Platform Owner က store ထဲ ဝင်ရမယ်ဆိုရင် — Store Support Mode ကိုသာ သုံးရမယ် (02-target-design §2.13):**

1. Enter Support Mode — **reason ရိုက်ရမယ်**
2. Start/end time record
3. **Write အကုန် audit** (actor, store, entity, before/after)
4. **Active store ကို ရှင်းရှင်း ပြ** (banner)
5. Accidental cross-store write ကာကွယ် (context lock)
6. Finance/export တွင် ပိုတင်းကျပ်
7. Store owner က သင့်တော်ရာ support activity ကို မြင်နိုင်

> Platform Owner ကို "store အကုန် invisible ဝင်လို့ရတယ်" ဆိုတဲ့ unrestricted access **မပေးရ** — SoT §6 store isolation ကို မချိုးရ။ Support session ကလွဲရင် ပုံမှန် query တွေက store-scoped ဖြစ်ရမယ်။

---

## ၃.၆ မြန်မာနိုင်ငံအတွက် အရေးကြီး feature (MVP ထဲ ပါ)

1. **ကြွေးစာရင်း (Credit/Debt)** — မြန်မာ့ဆိုင်တွေရဲ့ စာရင်းစာအုပ်ကို digitize — **MVP (Phase 2) ထဲ ပါ**
2. **Cashier shift + Daily closing** — expected vs actual cash — **MVP (Phase 2) ထဲ ပါ**
3. **Barcode/HID scanner + Split payments** (Cash/KPay/WavePay/CB Pay/MMQR) — MVP
4. **Warranty/Serial tracking** — ပြန်လဲ အများကြီးဖြစ်တဲ့အတွက် — Mobile/Electronics MVP
5. **Burmese language** — UI + receipt နှစ်မျိုးလုံး

---

## ၃.၇ ဆက်ဖတ်ရန်

- `ROADMAP.md` — ဒါတွေကို ဘယ်အချိန် ဘယ်လို ဆောက်မလဲ


---

## လက်ရှိ အခြေအနေ (Current State)



## ၁.၁ နည်းပညာအခြေခံ (Verified)

| အပိုင်း | အခြေအနေ |
|---|---|
| Framework | **Laravel 12.64** (PHP ^8.2) |
| Database | Local: **SQLite** (`database/database.sqlite`) · Production: MySQL (Hostinger) config ပါပြီးသား |
| Frontend | Blade + **Alpine.js** + **Tailwind CSS v4** (CSS-based `@theme`) |
| Assets | Vite — `app.css`/`app.js` (storefront) + `admin.css`/`app-admin.js` (admin) သီးခြား |
| Deploy | `deploy-datapos.sh` — **⚠️ အရင် project ရဲ့ live ကို သွားတဲ့ script — DataPOS အတွက် မသုံးရသေး** (README ကြည့်ပါ) |
| Testing | **867 tests pass / 3939 assertions** — PHPUnit, SQLite local (2026-08-17 run ပြီး) |
| Dev server | `php artisan serve --port=8501` (README/.env အတိုင်း) |
| Git | main · remote `github.com/shwepyithit568-commits/DataPOS.git` · **local = origin/main (in sync, 2026-08-17 — cashier session 13 commits push ပြီး, HEAD `ae70fde`)** |

---

## ၁.၂ ရှိပြီးသား (Built — Verified) ✅

### Ecommerce foundation (data_ecommerce ကနေ ကူးလာတဲ့ အခြေခံ)
- Multi-store (`stores` + `store_user` pivot + `StoreContext`/`ResolveStoreContext` path isolation)
- Catalog — products (SKU store-unique, variant, sale schedule, warranty...), categories (parent_id nested), brands, variant presets
- Storefront — home, products, detail, glass-finder, order-builder, blog, favorites, reviews, search
- Orders (`orders` + `order_items` — online Viber/Telegram flow) · PWA + Web Push · payments/delivery settings
- Admin panel အပြည့် — products, orders, brands, categories, blog, reviews, settings, push, backups, import history
- Product/Brand/Category CSV-XLSX import/export (`ProductImportService`)

### POS Phase 1 — Inventory foundation (changelog items 257–260)
- **Shared inventory ledger** — `inventory_movements` + `inventory_balances` (immutable; `inventory:reconcile` rebuild)
- **Branches & warehouses** — default location auto-creation, ledger FK constraints
- **Ecommerce InventoryAdapter** — `orders` → ledger movements, oversell prevention
- **Weighted-average CostingService** — receiving/returns/adjustments/serial

### POS Phase 2 — Online POS MVP (changelog items 261–270)
- Cashier shifts + opening cash (`/pos`) · cart + barcode search + atomic sale posting
- Receipt view + reprint (audit trail) · customer credit/debt (receivables, SoT §17)
- Sale return/refund (state machine) · daily closing (branch) · minimal reports (sales/cash/stock)
- Stock receiving (goods receipt → ledger, GRV numbers) · opening stock (manager review, OSR numbers)
- Inventory adjustments (manager approval, ADJ numbers) · idempotent `client_transaction_id` အကုန်မှာ

### POS Phase 2.5 part 1 — Pilot data-import hub (changelog item 271)
- `/admin/pilot-import` — products / customers / suppliers CSV-XLSX → **dry-run preview** → confirm → ImportHistory + error reports
- `suppliers` master table · `CustomerImportService` (phone-normalized duplicates, cross-store attach) · `SupplierImportService`

### ဒီနေ့ (2026-08-13) ပြင်ထားတာ — `CHANGELOG.md`
- Product import 500 bug (variants JSON မှာ `stock_status` မပါရင် crash → fallback fix, `ProductImportService.php`)
- "Ks 0" ဈေးပြသမှု (variant price 0 → product price fallback — product-card ×2 + catalog/show)

### ဒီနေ့ (2026-08-17) ပြင်ထားတာ — `CHANGELOG.md`
- **Cashier Home UI အပြည့်:** desktop 2-pane (grid + cart) / mobile 1-tab + floating cart → bottom-sheet drawer (swipe/backdrop/Escape close) · single-row scroll rows (module links / category / brand chips + toolbar) · `x-pos.chip-scroll` component
- **POS 500 fixes:** `resume`/`void`/`post` — `$store_slug` positional binding (Laravel 11/12 position-based resolve) + HTTP regression tests + `StoreScopedRouteSignatureTest` reflection guard (route အကုန် audit clean)
- **HTML caching:** private → `no-store` · public storefront → ETag + `max-age=60` + 304 · build assets → immutable (project `server.php` + `.htaccess`) · SW v5 network-first navigations
- Admin dashboard POS quick-action bar (shift status + jump to sale) · Store Settings quick-action render fix · `AdminDashboardTest` time-of-day fix

### POS cashier session (2026-08-17) — 13 commits (selling workflow အပြည့်)

- **Register-lock UX** — register ကို တစ်ခြားသူရဲ့ shift က ယူထားရင် "shift မဖွင့်ရသေးဘူး" အစား **occupied state** + shift-open rejection error ပြ · occupied register banner မှာ shift အသေးစိတ် (opening cash, cash sales) ပြ
- **Held sales (hold/recall)** — live recall fix · **age badge** (held since HH:mm) + **auto-expiry** (default 24h → **per-store setting** `pos_hold_expiry_hours`) · auto-expired ရင် cashier ကို **one-time notice** ပြ · POS home မှာ **expiry stats** (count / oldest / soon-to-expire)
- **Shared customer model** — ecommerce + POS အတူတူ (multi-store) — register enrollment + POS quick-add + phone/name dedup · POS quick-add မှာ **retail/wholesale customer type** ရွေးလို့ရ
- **Tiered pricing** — ဖောက်သည်တွဲရင် retail/wholesale tier ဈေး သက်ရောက် · **logged-in storefront shopper → POS cart** မှာ tier ဆက်ထိန်း · grid / cart lines / payment screen မှာ retail-vs-wholesale **discount visibility** (`retail_unit_price` / `line_retail_total` / `retail_subtotal`)
- **Per-line price override** — ✏️ editor · override က tier ဈေးကို အနိုင်ရ · hold/resume မှာ မပျက် · receipt မှာ original ဈေး အစင်း (`original_unit_price`)
- **Manager PIN approval** — per-store threshold (%) ထက် ပိုလျှော့တဲ့ override ကို manager/owner PIN နဲ့သာ (`users.pos_pin`, hashed) — approver က `pos_sale_items.approved_by` အထိ audit
- **Mouse drag-to-scroll** — horizontal chip rows (module/category/brand) ကို desktop မှာ မောက်နဲ့ ဆွဲလို့ရ (chip click တွေ မကျိုး)

---

## ၁.၃ မရှိသေးတဲ့အရာများ (ဆက်ဆောက်ရမယ့်ဟာ) ❌

| အပိုင်း | ဘယ် Phase | မှတ်ချက် |
|---|---|---|
| Pilot data import — opening-stock reconciliation, debt opening balances | Phase 2.5 | import hub (part 1) ပြီးပြီ — ကျန် |
| AppSheet/Google Sheets parallel validation + real cashier usage + stabilization | Phase 2.5 | pilot ကာလ အလုပ် |
| Backup & restore test (versioned workflow) | Phase 2.5 | runbook (`docs/ops/pilot-recovery-cutover-runbook.md`) — Drill #1 (SQLite) ✅ · §2.5A local MySQL ✅ · Drill #2 localhost rehearsal ✅ (2026-08-13, runbook §2.6) · **production drill ကျန် — deploy ပြီးမှ** |
| `/pos` PWA offline queue — IndexedDB, idempotent sync API, device registration | Phase 3 | storefront SW နဲ့ မရော |
| Full purchasing + purchase returns + supplier payables | Phase 4 | MVP က receive-without-PO |
| Stock transfers + stock counts · Service jobs (phone repair) · Expenses + finance ledger · Advanced reports · Accounting period closing | Phase 4 | |
| Local LAN/SQLite edition + license + provisioning + resale docs | Phase 5 | |
| Industry packs (pharmacy/gold/grocery/restaurant...) | Phase 6 | demand ရှိမှသာ |
| UOM / barcode (piece + decimal qty + HID scan) | Phase 2 (မပါတော့) / ops | barcode search ရှိပြီး (item 262) — UOM foundation မရှိသေး |
| Serial/IMEI + warranty tracking | Mobile MVP core | မရှိသေး |
| Final layout UI polish | resale/pilot မတိုင်ခင် | **Owner decision 08-11: defer** |

---

## ၁.၄ Open Decisions — Owner Input Required (SoT §38)

Implementation မှာ မခန့်မှန်းရတဲ့အရာတွေ — **Owner (ဆရာကြီး) ဆုံးဖြတ်ပေးရမယ်:**
Negative stock exception policy · Return/exchange time limits · Item condition rules · Service warranty rules ·
Customer credit limits · Debt approval rules · Supplier payable workflow · Historical migration depth ·
Official cutover date · Daily closing / discrepancy thresholds · Offline retention · Receipt printer model /
paper width / connection · Cash drawer · Barcode/label scanner models · Tax usage · Final receipt layout ·
Final Burmese/English/Chinese terminology

(Resolved ပြီးသား: Money/rounding policy 08-10 — MMK integer, receipt round final step တစ်ခါတည်း · Hosting = Hostinger 48mo)

---

## ၁.၅ နောက်ဆက်လုပ်ရမှာများ (Next Steps — အစီအစဉ်)

1. ~~**Git sync**~~ — ✅ ပြီးပြီ (2026-08-13): docs consolidation `19222c1` + fix ၂ ခု `8fe8228` / `adb155a` (CashierShiftController အပါအဝင်) — origin/main နဲ့ in sync
2. ~~**POS cashier session (2026-08-17)**~~ — ✅ ပြီးပြီ: 13 commits (register-lock UX → held-sale expiry → shared customer model → tiered pricing → price override + manager PIN → drag-to-scroll) — §1.2 "POS cashier session" + CHANGELOG.md
3. **Phase 2.5 ကျန်** — opening-stock reconciliation → debt opening balances → backup/restore drill →
   real cashier usage (ဆိုင်မှာ တကယ်သုံး) → parallel validation → stabilization (ဒီဖိုင် Phase 2.5 exit criteria)
4. **Phase 3 — Cloud PWA Offline Queue** — `/pos/sw.js` + IndexedDB + idempotent sync API + device registration
5. **Phase 4 — Operations Modules** (purchasing, transfers, service, expenses, reports, period closing)
6. **Phase 5 — Local LAN/SQLite Edition + Resale Readiness**
7. Owner open decisions တွေ အချိန်တန်ရင် ဖြေရှင်း

---

## ၁.၆ အရေးကြီး မှတ်ချက်များ (Revision 3)

1. **`products.stock_status` က derived cache** — ledger က source of truth (changelog item 257, `config/inventory.php` `sync_stock_status_cache` default true) — manual field အနေနဲ့ မမှတ်ရ
2. **Ledger/sales/shifts/closings immutable** — မှားရင် reversal/correction document နဲ့သာ ပြင်ရမယ် (SoT §15.1)
3. **Service worker scope** — POS အတွက် `/pos/sw.js` သီးခြား — storefront SW (web push) မထိရ (SoT §4.3)
4. **ဖိုင်နာမည်တွေ** — `CHANGELOG.md` က history (items 1–271) · အသစ်တွေ → `CHANGELOG.md`
5. **GitHub က local နဲ့ in sync (2026-08-17)** — POS cashier session 13 commits အကုန် origin/main ကို push ပြီး · working tree clean · HEAD = `ae70fde`
6. **Port** — ဒီ project = **8501** (အရင် docs ရဲ့ 8500/8577 နဲ့ မရော)
7. **Deploy-datapos.sh က DataPOS live ကို မသွားရသေးဘူး** — pilot/resale အဆင့် ရောက်မှ deploy script အသစ် ရေးရမယ်


---

## တည်ဆောက်ရမယ့် အဆင့်ဆင့် (Implementation Phases)

## ၄။ တည်ဆောက်ရမယ့် အဆင့်ဆင့် (Implementation Phases)

> **ဒီဖိုင်မှာ:** POS စနစ်ကို ဘယ်ကစပြီး ဘယ်လို အဆင့်ဆင့် ဆောက်မလဲ။
>
> **Revision 2 (2026-08-10):** Owner မှ အတည်ပြုထားသော phase structure အသစ် — MVP scope ပြင်ပြီး (debt + closing က Phase 2)၊ AlinnThit pilot phase အသစ် ထည့်ပြီး၊ offline system ၂ မျိုး ခွဲပြီး။
>
> **မူအရ:** အဆင့်တိုင်း ပြီးရင် စမ်းသပ်ပြီးမှ နောက်တစ်ဆင့်။ မလိုအပ်တဲ့အရာ ကြိုမဆောက်နဲ့။

---

## Implementation Order (အဓိက ဦးစားပေး)

1. **Online Cloud POS** (Phase 0 → 2)
2. **AlinnThit production pilot** (Phase 2.5) — resale မလုပ်ခင် မဖြစ်မနေ
3. **Cloud PWA offline queue** (Phase 3)
4. **Local LAN/SQLite edition** (Phase 5)
5. **Cloud-to-local sync** — proven customer demand ရှိမှသာ

> Cloud PWA offline sync နဲ့ Local-server mode ကို phase တစ်ခုထဲ **မရော**။

---

## Phase 0 — Architecture Decisions & Risk Removal

**ရည်ရွယ်ချက်:** ကုဒ်မရေးခင် ဆုံးဖြတ်ချက်တွေ အတည်ပြု + risk ရှင်းပြီး foundation ပြင်ဆင်

| အလုပ် | အသေးစိတ် | Status |
|---|---|---|
| Tenancy/deployment decision | Cloud SaaS vs Local install — 02-target-design §2.3 | ✅ Approved 2026-08-10 — SoT နှစ်ဖိုင်လုံးတွင် မှတ်ပြီး |
| Store/domain resolver ပြင် | `CHANGELOG.md` အတိုင်း — store ၂ ခု active ရင် home မပျက်အောင် | ✅ 2026-08-13 (`7ae71ef`) — primary-store resolver fix + admin store management UI |
| Shared Ecommerce/POS inventory source of truth | Ledger ဒီဇိုင်း + adapter + stock_status derived ပြောင်း | ✅ SoT ပြင်ပြီး + implementation ပြီး (item 257–260 — ledger + adapter) |
| Money & rounding policy | Integer MMK, precision, rounding order (02 §2.6) — acceptance test နဲ့ သေချာ | ✅ Approved 2026-08-10 — SoT Open Decision #15/#6 Resolved |
| Weighted-average valuation | CostingService design (02 §2.7) | ✅ Rule က SoT §14.4/§10.4 မှာ မှတ်ပြီး + CostingService ပြီး (item 257–260) |
| Negative-stock policy | Default block + future override rules (02 §2.8) | ⏳ Owner approve လို |
| Offline mode separation | Cloud queue vs Local — 02 §2.12 | ✅ ဒီစာရွက်စာတမ်းမှာ သတ်မှတ်ပြီး |
| Permission matrix | Store modules / branch capabilities / user roles / approvals — 4 levels | ⏳ စရန် |
| Data-quality audit | AppSheet/Google Sheets ဒေတာ စစ် | ⏳ စရန် |
| Architecture Decision Records (ADR) | ဆုံးဖြတ်ချက်တိုင်း ADR ရေး | ⏳ စရန် |
| Detailed acceptance tests | Phase 1–2 အတွက် acceptance criteria | ⏳ စရန် |

**ထွက်ကုန်:** Approved decisions + ADRs + Phase 1 task breakdown + acceptance tests

---

## Phase 1 — Minimum Shared Foundation

**ရည်ရွယ်ချက်:** POS ရော Ecommerce ရော မှီခိုရမယ့် အုတ်မြစ် — ဒါမရှိရင် ဘာမှ မဆောက်နဲ့

| အလုပ် | အသေးစိတ် |
|---|---|
| Default branch/warehouse | Store တိုင်းကို default branch + warehouse auto-create (02 §2.11) — ✅ **2026-08-11 done** (branches/warehouses tables + StoreLocationService::ensureDefaults + controller/CLI hooks + `inventory:ensure-locations` backfill + ledger FK + 10 tests — changelog item 258) |
| Store module middleware | Static routes + module/capability enforcement (02 §2.4) — route:cache compatible |
| Branch roles & policies | `user_branch_roles` + policies — Owner/Admin/Manager/Cashier/Read-only |
| Barcode/UOM foundation | Product UOM + barcode + decimal qty |
| Weighted-average costing | CostingService (02 §2.7) — ✅ **2026-08-11 done** (`App\POS\Services\CostingService` — receiving recalc, COGS carry, returns, serial specific cost, reversal replay — 12 tests — changelog item 260) |
| Customers & suppliers | Master data — debt အတွက် foundation |
| **Inventory movements & balances** | Ledger (02 §2.5) — immutable, idempotent, transactional — ✅ **2026-08-11 part 1 done** (migration + enum + models + InventoryService + `inventory:reconcile` + 19 tests — changelog item 257) |
| Opening stock | `opening_balance` movements — migration batch နဲ့ — movement type + service အသင့် (ledger part 1 ထဲ) |
| **Ecommerce inventory adapter** | `orders` → ledger integration — reserve/confirm/cancel — oversell မဖြစ်ရ — ✅ **2026-08-11 done** (`OrderInventoryAdapter` + `OrderAdminController@updateStatus` hook — reserve on confirm, commit on delivered, release on cancel, oversell block — 13 tests — changelog item 259) |
| Audit & approvals | `audit_logs`, `approvals` (SoT §15, §20) |
| Concurrency & idempotency tests | Parallel sale, duplicate posting, retry — အကုန် test |

**Exit criteria:** Ledger က POS + Ecommerce နှစ်ဖက်စလုံး ထိန်းနိုင် · cross-store leak 0 · route:cache + module middleware အလုပ်ဖြစ် · SQLite+MySQL green

---

## Phase 2 — Usable Online POS MVP

**ရည်ရွယ်ချက်:** ဆိုင်မှာ တကယ်သုံးလို့ရတဲ့ online POS — offline complexity မထည့်ခင် online integrity အရင် validate (SoT §28)

| အလုပ် | အသေးစိတ် |
|---|---|
| Cashier shifts + opening cash | ✅ item 261 — shift open/close, opening cash, cash in/out, daily summary (02 §2.10) |
| Barcode/HID scanner input | ✅ item 262 — /pos barcode/SKU/name search (HID scanner types into the search box) |
| Product & variant search | ✅ item 262 — live search w/ ledger balance |
| Cart | ✅ item 262 — add/merge/update/remove (retail pricing; wholesale နောက်) |
| Hold/resume sale | ✅ item 262 — session draft → held row → resume/void |
| Sale posting | ✅ item 262 — atomic: receipt @ posting, ledger movements, payments, drawer |
| Split payments | ✅ item 262 — payment modal: Cash / KPay / WavePay / CB Pay / MMQR, change calc (02 §2.8) |
| **Customer credit/debt** | ✅ item 264 — customer attach + `credit` payment → `customer_ledger_entries` (sale_debt/collection/reversal), balance = SUM, collect form (SoT §17) |
| Receipt & reprint | ✅ item 263 — printable receipt + reprint audit trail (SoT §8) |
| Audit trail (foundation) | ✅ item 263 — `audit_logs` table + AuditLog model (Phase 1 item, first consumer: reprints) |
| Sale return/refund/reversal | ✅ item 265 — `pos_returns` doc + `sales_return` ledger @ original cost + cash→drawer / credit→debt + partially_refunded/refunded (SoT §15.1, 02 §2.9) |
| Simple stock receiving | ✅ item 268 — `goods_receipts` doc (GRV-Ymd-####, idempotent) → `purchase_received` ledger @ unit cost → weighted-avg recalc (SoT §6) |
| Opening stock | ✅ item 269 — `opening_stock_requests` (OSR-Ymd-####): staff submits → manager approves → `opening_balance` ledger @ unit cost + avg set (SoT §6) |
| Inventory adjustment (manager approval) | ✅ item 270 — `inventory_adjustments` (ADJ-Ymd-####): cashier submits signed +/− with reason → manager approves → `adjustment_in/out` @ avg cost, avg unchanged (SoT §6) |
| ~~Audit trail~~ | ✅ အကုန် မှတ်တမ်း — items 261–270 |
| **Daily closing** | ✅ item 266 — `daily_closings` per store+date: expected (shift drawer math + e-method sales + credit info) vs counted, diff, explanation, **manager approval**, offline gate (SoT §18, 02 §2.10) |
| Minimal reports | ✅ item 267 — `/pos/reports/{sales,cash,stock}` read-only: sales + method totals (cashier/date filters), cash drawer per-shift + aggregates, stock qty × avg cost value (ledger cache) |


**Exit criteria:** အထက်ပါ MVP items အကုန် online မှာ အလုပ်ဖြစ် · atomic posting · oversell 0 · daily closing reconcile ရတယ် · POS tests green

> ✅ **2026-08-11 — Phase 2 (Online POS MVP) အကုန်ပြီးပြီ (items 261–270).** နောက်တစ်ဆင့်: Phase 2.5 AlinnThit pilot.

> **မှတ်ချက်:** Full purchasing / purchase returns / supplier payables / advanced accounting တွေက Operations (Phase 4) — MVP မဟုတ်ဘူး။

---

## Phase 2.5 — AlinnThit Production Pilot

**ရည်ရွယ်ချက်:** ပြင်ပဖောက်သည်ကို မရောင်းခင် ကိုယ့်ဆိုင်မှာ စမ်းပြီး workflow မှန်ကြောင်း အတည်ပြု

| အလုပ် | အသေးစိတ် |
|---|---|
| Clean product/customer/supplier data | ✅ item 271 — `/admin/pilot-import` hub (Products/Customers/Suppliers tabs): CSV/XLSX upload → **dry-run preview** (validation + duplicate detection: SKU / phone / phone-then-name) → confirm → ImportHistory + error reports · `suppliers` master table · templates |
| Opening-stock reconciliation | Ledger vs လက်ရှိ |
| Debt opening balances | Receivables import |
| AppSheet/Google Sheets parallel validation | နှစ်စနစ် တွဲပြေး → ကိုက်ကြောင်း စစ် |
| Real cashier workflow | တကယ့် ဆိုင်မှာ သုံး |
| Returns/refunds + customer debt + daily closing | MVP features တွေ real usage |
| Backup & restore test | Versioned workflow စမ်း |
| Performance test + store-isolation test | Load + tenant leak မရှိကြောင်း |
| Several weeks of observed real usage | Stabilization period |
| Written recovery/cutover runbook | Rollback/failover လုပ်နည်း စာရွက် |

**Exit criteria:** Pilot workflow မတည်မငြိမ်ခင် **ပြင်ပဖောက်သည်ကို မရောင်းရ** · runbook အပြည့် · reconciliation diff = 0

---

## Phase 3 — Cloud PWA Offline Queue

**ရည်ရွယ်ချက်:** Internet မရှိတဲ့အခါလည်း cloud POS ဆက်အလုပ်လုပ်နိုင် (Model A အတွက်)

| အလုပ် | အသေးစိတ် |
|---|---|
| `/pos` PWA installable | သီးခြား service worker (`/pos/sw.js`) — storefront SW မထိ (SoT §4.3) |
| IndexedDB branch dataset | လိုတဲ့ဒေတာ သိမ်း (offline queue) |
| Offline transaction queue | Draft → ready → syncing → synced + error states (SoT §19.4) |
| Idempotent sync API | `client_transaction_id` unique — duplicate မဖြစ်ရ (SoT §19.2) |
| Device registration/revocation | Device handoff workflow (SoT §9.2) |
| Queue status & recovery | syncing/pending/failed/error — မမြင်ရအောင် မဝှက် + failed-queue recovery/export |
| Conflict strategy | Posted immutable → correction document (SoT §19.5) |

**Exit criteria:** Offline sale → reconnect → sync → balance မှန် · duplicate 0 · revoked device reject · Windows + Android field test pass

---

## Phase 4 — Operations Modules

**ရည်ရွယ်ချက်:** MVP အပြင် ကျန် module တွေ (SoT §13)

| Module | အချိန် |
|---|---|
| Full purchasing + purchase returns + supplier payables | Phase 4 |
| Stock transfers + stock counts | Phase 4 |
| Service jobs (ဖုန်းပြုပြင်ရေး) + service parts | Phase 4 |
| Expenses + finance ledger | Phase 4 |
| Advanced reports | Phase 4 |
| Finance/accounting period closing | Phase 4 |

---

## Phase 5 — Local LAN/SQLite Edition & Resale Readiness

**ရည်ရွယ်ချက်:** Offline-only ဖောက်သည်အတွက် Local install + ပြင်ပရောင်းချဖို့ ပြင်ဆင် — ကျယ်ရင် ၂ ပိုင်း ခွဲ:

### 5a. Local installation, backup, restore, update workflow
- SQLite single-tenant install (Model B — 02 §2.3)
- Browser devices → LAN/Wi-Fi
- **Versioned backup/restore** (02 §2.15): WAL checkpoint, snapshot, assets, manifest, checksums, integrity verify, restore dry-run, pre-restore backup, version compat validation
- Versioned update workflow (upgrade path)
- **ပထမ Local release တွင် central cloud sync မပါ**

### 5b. Provisioning, plans, licensing, support mode, monitoring, documentation
- **Offline license** — signed payload, public-key verify — **private signing key ကို install ထဲ မထည့်ရ**
- Tenant provisioning tooling (Cloud) + plan gating
- Store Support Mode (02 §2.13)
- Monitoring + error reporting + measurable upgrade triggers (02 §2.16)
- Resale documentation + training materials

**Exit criteria:** Local install ကို ဖောက်သည်အသစ်တစ်ယောက်ဆီ ပေးပြီး backup→restore→update အကုန် အလုပ်ဖြစ် · license verify/reject test pass

---

## Phase 6 — Customer-driven Industry Packs

**ရည်ရွယ်ချက်:** ဖောက်သည်အစစ် ပေါ်လာမှသာ ဆောက်မယ် — validated demand မရှိရင် မဆောက်

| Pack | ဘယ်အခါ |
|---|---|
| Pharmacy (expiry/batch) | ဆေးဆိုင် ဖောက်သည် ရလာရင် |
| Gold Shop (ကျပ်/ပဲ/ရွေး + daily rate + karat) | ရွှေဆိုင် ဖောက်သည် ရလာရင် |
| Grocery (scale + multi-unit) / Restaurant (KOT/tables) / Fuel / Fashion matrix | Demand ပေါ်မှ တစ်ခုချင်းစီ |

---

## Resale တိုးချဲ့မှု (ဘယ်အချိန် ဘာလုပ်မလဲ)

| ဘယ်အခါ | ဘာလုပ် |
|---|---|
| Phase 2.5 pilot မတည်မငြိမ်ခင် | **ပြင်ပဖောက်သည်ကို မရောင်းရ** |
| Pilot ပြီးမှ Cloud ဖောက်သည်သစ် | `store:create` + enabled_modules — Phase 1 မှာ ရှိပြီးသား |
| Offline-only ဖောက်သည်သစ် | Local LAN edition — Phase 5 ပြီးမှ |
| License စတင်ရောင်းချင်ရင် | Signed offline license (5b) — MVP မစမ်းရသေးခင် မလုပ် |
| Industry pack လိုလာရင် | Phase 6 — demand ရှိမှ |

---

## တည်ဆောက်စဉ် လိုက်နာရမယ့် အခြေခံစည်းမျဉ်း

1. **Store/tenant isolation** — store A ဒေတာ store B မှာ မပေါ်ရ (SoT §6)
2. **Server-side authorization** — static routes + middleware — UI hide တစ်ခုတည်း မလုံလောက်
3. **Inventory/finance ကို atomic + auditable** — partial မဖြစ်ရ (SoT §15.2, §19.3)
4. **Idempotency** — sync retry လုပ်ရင် duplicate မဖြစ်ရ (SoT §19.2)
5. **Money/quantity float မသုံး** — integer MMK + decimal precision (02 §2.6)
6. **Negative stock default block** (02 §2.8)
7. **SQLite + MySQL နှစ်မျိုးလုံး** — migration/test run ရမယ်
8. **Burmese/English label** — နှစ်ဘာသာ (lang ၃ ဖိုင်)
9. **Test ရေးရမယ်** — feature တိုင်းနဲ့
10. **SoT နဲ့ ဆန့်ကျင်ရင် ရပ်ပြီး မေး** — မခန့်မှန်းနဲ့ (02-target-design ရဲ့ SoT Conflicts ဇယား)

---

## ဆက်ဆွေးနွေးရန်

- ✅ SoT amendment ၅ ခု (02-target-design §SoT Conflicts) — **Approved 2026-08-10 + နှစ်ဖိုင်လုံးတွင် apply ပြီး**
- Phase 0 ရဲ့ ကျန် Owner decisions (negative stock override, hosting trigger thresholds) အတည်ပြုဖို့
- AlinnThit pilot ရဲ့ စတင်ရက် / data source
