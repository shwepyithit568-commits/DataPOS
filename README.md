# DataPOS

> **ဒီဖိုဒါက ဘာလဲ:** DataPOS Ecommerce (datapos.com — live) ရဲ့ codebase ကို အခြေခံပြီး
> **Offline-first POS + resale စနစ်** ကို သီးခြား တည်ဆောက်နေတဲ့ project ဖြစ်ပါတယ်။
>
> **အခြေခံ:** Laravel 12.64 (PHP 8.2, source: `data_ecommerce` main project — 2026-08-10 ကူးယူ)

---

## 📌 လက်ရှိ အခြေအနေ (2026-08-20)

| အပိုင်း | အခြေအနေ |
|---|---|
| **Online POS MVP Phase 1 + 2** | ✅ **ပြီးစီး** (changelog items 257–270) |
| **Phase 2.5 — Pilot data import hub (part 1)** | ✅ ပြီးစီး (item 271) — products / customers / suppliers CSV-XLSX import |
| **POS cashier session (2026-08-17)** | ✅ 13 commits — register-lock UX · held-sale expiry (age badge / auto-expiry / per-store window / notice / stats) · shared customer model · retail/wholesale tiered pricing + discount visibility · price override + manager PIN · drag-to-scroll |
| **Admin sidebar restructure (2026-08-18)** | ✅ 11 groups (alinthit_pos layout) — inventory ops ကို Inventory group ထဲ ရွှေ့ · Reconciliation link ထည့် · Phase 4 module ၃၈ ခု → coming-soon placeholder (single route + whitelist) |
| **Products Master Data hub (2026-08-18)** | ✅ `/admin/products/master-data` — horizontal scroll tabs (Categories · Brands · Variant Settings) · same partials as the standalone pages (zero drift) · edit/create round-trip က tab ပြန်ရောက် |
| **Product form Inventory & Purchase (2026-08-18)** | ✅ alinthit_pos ပုံစံ — Initial stock (opening_balance auto-post) · Auto-SKU · Reorder level · Supplier quick-add · Purchase cost · colored section headers · Sell Online toggle |
| **Purchasing — suppliers & PO (2026-08-20)** | ✅ Supplier CRUD + import/export · purchase returns · payables (FIFO + per-PO) · aging report · dashboard alerts (commit `369dbf8` — CHANGELOG 08-20) |
| **Purchasing — warehouses, transfers, buy back (2026-08-20)** | ✅ Warehouses CRUD · stock transfers (create → ship → receive) · buy back (stock restoration) · sidebar placeholders → real links (commit `7312b54` — CHANGELOG 08-20) |
| **Test suite** | ✅ **994 passed / 4467 assertions** (`php artisan test`, run 2026-08-20) |
| **DB** | SQLite (`database/database.sqlite`) — migrations အားလုံး run ပြီး |
| **Git** | main branch · remote `github.com/shwepyithit568-commits/DataPOS.git` · **⚠️ local = origin/main (ahead 2 — 08-20 purchasing commits afternoon not yet pushed)** |
| **Deploy** | **မလုပ်ရသေးဘူး — local development သာ** (အောက်က ⚠️ ကြည့်ပါ) |

### Open issues (review 2026-08-20)

1. 🛑 **`/admin/warehouses` routes missing `EnsureStoreAccess`** — index/store/update registered outside any role group (only `auth` + `ResolveStoreContext`); reachable by any logged-in user + no cross-store warehouse/branch guard on update/destroy. **Fix pending** (TODO: add `->middleware(EnsureStoreAccess::class . ':store_manager,staff')` + store-scope check in `WarehouseController`).
2. 🧹 **`app/Http/Controllers/Admin/SupplierController.php` not strict UTF-8** — a Windows-1252 `0x97` byte where an em-dash belongs (line ~"Supplier aging report …"). Harmless to PHP but breaks strict-UTF-8 tooling. Fix pending.
3. ⚠️ **`SHOW_QUICK_LOGIN=true`** in local `.env` — dev/test only (hard-blocked in production/staging). Remember to clear it on the production `.env` at deploy.

### POS Module မှာ ပါပြီးသား အရာတွေ (`/pos` routes — web.php:567+)

- **Phase 1 (Inventory foundation):** shared ledger (`inventory_movements` + `inventory_balances`) ·
  branches & warehouses · ecommerce `orders` → ledger adapter (oversell prevention) · weighted-average costing
- **Phase 2 (MVP):** cashier shifts + opening cash · cart + barcode search + atomic sale posting ·
  receipt view + reprint (audit trail) · customer credit/debt (receivables) · sale return/refund ·
  daily closing (branch) · minimal reports (sales/cash/stock) · stock receiving (goods receipt) ·
  opening stock (manager review) · inventory adjustments (manager approval)
- **Phase 2.5 part 1:** pilot data-import hub (`/admin/pilot-import`) — dry-run preview → confirm → history + error reports
- **Cashier session (2026-08-17):** register-lock occupied state + shift details · held-sale age badge + auto-expiry (per-store) + one-time expiry notice + home expiry stats · shared ecommerce/POS customers (dedup, retail/wholesale) · tiered pricing + logged-in tier resolution + discount visibility · per-line price override (receipt struck original) · manager PIN for deep overrides · mouse drag-to-scroll
- **Purchasing (2026-08-20):** supplier CRUD + import/export · purchase returns · supplier payables (FIFO + per-PO) · aging report · dashboard overdue alerts · warehouses CRUD · stock transfers (create → ship → receive) · buy back (stock restoration)

အသေးစိတ်: `CHANGELOG.md` (items 257–271 + 08-20 purchasing) · စည်းမျဉ်း: `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md`

---

## 🚀 Local မှာ run နည်း

```bash
cd DataPOS
D:/xmapp/php/php.exe artisan serve --port=8501
# → http://127.0.0.1:8501
```

- Database: SQLite (`database/database.sqlite`)
- Store slug: `datapos-mobile`
- ⚠️ အရင် docs တွေက port **8500 / 8577** ကို ရည်ညွှန်းထားတာ ရှိတယ် — **ဒီ project အတွက် 8501** ပါ
  (8500 က အရင် Botble project, 8577 က test server)

---

# DataPOS — Documentation Index

ဒီဖိုင်က project ထဲက `.md` documentation တွေရဲ့ **တည်နေရာ မြေပုံ** ဖြစ်ပါတယ်။
Coding မစမီ သက်ဆိုင်ရာ documentation ကို ဒီကနေ ရှာပါ။

---

## 📌 Root — Active working docs (အရေးအကြီးဆုံး — အမြဲ update လုပ်ရမည့်ဟာများ)

| File | အကြောင်း |
|---|---|
| `README.md` | Project ခြုံငုံ မိတ်ဆက် + run နည်း + **လက်ရှိအခြေအနေ + Next steps** — entry point |
| `Source_of_Truth_MM.md` | **Business Rules + Architecture Rules** — business/architecture ပြောင်းမှသာ update |
| `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` | **POS စနစ် စည်းမျဉ်းစာချုပ် (MUST READ)** — POS module အတွက် |
| `CHANGELOG.md` | **Change Log (တစ်ခုတည်း)** — items 1–271 (history) + 08-13 fixes + 08-17 cashier session · အသစ်တိုင်း ဒီဖိုင်အဆုံးမှာ ထည့်ရမည် |
| `Testing_check.md` | Testing / known issues အခြေအနေ (UAT section ပါ ပေါင်းထည့်ထား) |

## 📂 docs/ — Reference documentation

| Folder | အကြောင်း | Files |
|---|---|---|
| `docs/prompts/` | Agent conversation templates (new chat မှာ paste လုပ်ရန်) — **အကုန် ၁ ဖိုင်ထဲ** | `TEMPLATES_MM.md` (AI Agent Instructions · project-start · bug-fix · new-feature · UI/Layout prompt · Storefront roadmap — 2026-08-13 ပေါင်းစည်းပြီး) |
| `docs/pos-resale-plan/` | POS + Resale စနစ် တည်ဆောက်ရေး အစီအစဉ် (2026-08-13 မှာ 4 ဖိုင် → 2 ဖိုင် စုစည်း) | `ROADMAP.md` (overview + current-state + implementation phases) · `02-target-design.md` |
| `docs/ops/` | Deployment / operations / security (2026-08-13 မှာ 5 ဖိုင် → 2 ဖိုင်) | `DEPLOYMENT.md` (deploy guide + backup + env example + secrets scrub) · `pilot-recovery-cutover-runbook.md` |
| `docs/archive/` | Dated / done one-off logs (ပြီးသွားသော အလုပ် မှတ်တမ်း) | `deployment-runbook.md` (အရင် site ရဲ့ deploy history #1–#27) · audit reports တွေ ဖျက်ပြီး (record: CHANGELOG.md) |

## 🔁 Workflow အတိုချုပ်

1. အလုပ်မလုပ်မီ → `Source_of_Truth_MM.md` (rules) + `CHANGELOG.md` (history) + `Testing_check.md` (known issues) စစ်ပါ။
2. POS ဆိုင်ရာ → `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` + `docs/pos-resale-plan/` ကို ဦးစားပေးဖတ်ပါ။
3. အလုပ်ပြီးပါက → `CHANGELOG.md` (item အသစ်) + `Testing_check.md` (bug ဆိုရင်) update လုပ်ပါ။
4. Business/Architecture Rule ပြောင်းမှသာ `Source_of_Truth_MM.md` ကို update လုပ်ပါ။
5. 5+ files ထိမည့် / schema / inventory-payment ထိမည့် change → Affected Files / Approach / Risks ကို အရင် ပြပြီး confirmation ယူပါ။

## ⚠️ Security note

`docs/ops/production-env-datapos.md` မှာ အရင်က **တကယ့် production credentials** (APP_KEY, DB_PASSWORD, MAIL_PASSWORD) ပါခဲ့ပြီး
git history ထဲ ရောက်နေပါသည် — ဖိုင်ကို **2026-08-13 တွင် repo ကနေ ဖျက်လိုက်ပြီ** (safe template = `docs/ops/DEPLOYMENT.md` §Production .env Example)
သို့သော် **git history ထဲမှာ ကျန်နေဆဲ** — repo ကို public မလုပ်မခင် `docs/ops/DEPLOYMENT.md` §Scrubbing Secrets အတိုင်း history scrub လုပ်ရမည်။
မလိုအပ်တော့ပါက ဒီ secrets တွေ သုံးနေတဲ့ Hostinger server ရဲ့ APP_KEY / DB_PASSWORD / MAIL_PASSWORD တွေကို
ပြောင်းလဲ (rotate) လုပ်သင့်သည်။

## ⚠️ အရေးကြီး သတိပေးချက်

- **ဒီဖိုဒါက local development အတွက်ပါ** — live site (datapos.com / alinnthit.com) နဲ့ သီးခြား။
- `deploy-datapos.sh` က အရင် project ရဲ့ live ကို deploy လုပ်တဲ့ script ဖြစ်လို့
  **ဒီဖိုဒါကနေ run လုပ်ရင် live site ပေါ် ရောက်သွားနိုင်တယ် — မလုပ်ပါနဲ့!**
  DataPOS အတွက် deploy script အသစ် သီးခြား ရေးရမယ် (resale/pilot အဆင့် ရောက်မှ)။
- `.env` က local အတွက် အသစ် ဖန်တီးထားတာ (SQLite, fresh APP_KEY) — production secrets မပါပါဘူး။

---

## 🗂️ နောက်ဆက်လုပ်ရမှာများ (Next Steps)

- ✅ **2026-08-17 — POS cashier session ပြီးပြီ** (13 commits): register-lock UX → held-sale expiry system → shared customer model → tiered pricing → price override + manager PIN → drag-to-scroll (အသေးစိတ်: အပေါ်က "လက်ရှိ အခြေအနေ" + `CHANGELOG.md`)
- ✅ **2026-08-18 — Admin sidebar ကို အဟောင်း project (alinthit_pos) အုပ်စုဖွဲ့မှုနဲ့ ပြန်တည်ဆောက်ပြီး** (11 groups): inventory ops တွေ Inventory & Products group ထဲ ရွှေ့ · Reconciliation link ထည့် · Phase 4 module ၃၈ ခုကို coming-soon placeholder (single route + whitelist) နဲ့ ပြထား — နောက်မှ တစ်ခုချင်းစီ ဆက်ဆောက်ရမယ် (အသေးစိတ်: `CHANGELOG.md`)
- ✅ **2026-08-20 — Purchasing batch ပြီးပြီ**: supplier CRUD/import/export · purchase returns · payables (FIFO + per-PO) · aging report · dashboard alerts · warehouses CRUD · stock transfers · buy back (အသေးစိတ်: အပေါ်က "လက်ရှိ အခြေအနေ" + `CHANGELOG.md` 08-20)

0. 🛑 **Review follow-ups (2026-08-20)** — (a) `/admin/warehouses` routes missing `EnsureStoreAccess`; (b) `SupplierController.php` not strict UTF-8; (c) remember to clear `SHOW_QUICK_LOGIN` from the production `.env`. (အပေါ်က Open issues ကြည့်ပါ)

1. **Phase 2.5 ကျန်တဲ့အပိုင်း** — opening-stock reconciliation · debt opening balances ·
   AppSheet/Google Sheets parallel validation · real cashier workflow · backup & restore test ·
   performance + store-isolation test · stabilization period (အသေးစိတ်: `docs/pos-resale-plan/ROADMAP.md` §2.5)
2. **Phase 3 — Cloud PWA Offline Queue** (offline sale → sync → idempotent, `/pos/sw.js` သီးခြား)
3. **Phase 4 — Operations Modules** (service jobs, expenses, advanced reports, stock counts)
4. **Phase 5 — Local LAN/SQLite Edition + Resale Readiness** (license, provisioning, docs)
5. **Owner Open Decisions** — `Source_of_Truth_MM.md` §38 (negative stock policy, return limits, printer model, tax, ...)

**လက်ရှိ ဆိုင်းငံ့ထားတာ:** Final layout UI polish — resale/pilot မတိုင်ခင် မလုပ်ရသေး (Owner decision 2026-08-11)။

---

## 📦 Release Notes

> ⚠️ **ဒီ Release Notes တွေက မူရင်း project (`data_ecommerce`) ရဲ့ v0.1.0-rc1 မှတ်တမ်းပါ** — DataPOS ကို ကူးယူချိန်က သိမ်းထားတာ။ အဲဒီ project ရဲ့ ပထမဆုံး production store က ACDC Mobile (`acdc-mobile`) ဖြစ်ခဲ့တယ် — ဒီအောက်က "acdc-mobile" / "datapos-mobile remains local/UAT only" စာကြောင်းတွေက အဲဒီ project အတွက်ပါ။ **DataPOS အတွက် canonical production slug က `datapos-mobile`** (deploy မလုပ်ရသေးလို့ လက်ရှိ ဆုံးဖြတ်ချက် — `docs/ops/DEPLOYMENT.md` ကြည့်ပါ)။

# DataPOS Ecommerce v0.1.0-rc1

Release candidate for MVP hosting selection and deployment preparation.

## Included MVP Features

- Public storefront home, catalog, search, filters, product detail, gallery, and favorites.
- Store-scoped admin dashboard with product, category, brand, image, Glass Finder, import history, order, wholesale, and settings workflows.
- Product CSV/XLSX import preview/confirm, duplicate handling, import history, and failed-row downloads.
- Glass Finder search, compatibility groups, CSV/XLSX import, and admin CRUD.
- Customer order builder with Viber/Telegram contact links and admin order status workflow.
- Wholesale application, approval/rejection, and wholesale price visibility.
- Store isolation, CSRF protection, HTTPS configuration controls, and UAT seeding safety checks.
- Production-safe seeding uses an explicit `ProductionSeeder`; demo/UAT seeding remains opt-in and blocked outside local/testing/UAT environments.
- First production admin creation uses `php artisan production:create-admin` with operator-provided credentials and no default passwords.
- First real production store bootstrap uses `ACDC Mobile` with canonical slug `acdc-mobile`; `datapos-mobile` remains local/UAT data only.

## Known Non-Blocking Limitations

- Livewire remains installed in Composer but has no active app usage in `app/`, `routes/`, `resources/views/`, or `resources/js/`.
- `public/build` is ignored locally; deploy prebuilt assets separately or build on the target server.
- Store contact/profile values are database/admin managed in the MVP. Environment placeholders are documentation aids.

## Required Server Capabilities

- PHP 8.2 or newer.
- PHP extensions required by Laravel and imports: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`, `gd`.
- Composer 2.
- MySQL or MariaDB with `utf8mb4`.
- Writable `storage/` and `bootstrap/cache/`.
- HTTPS certificate support.
- Optional Node.js 24+ and npm if assets are built on the server.

## Deployment Prerequisites

- Create a real production `.env`; never commit it.
- Generate `APP_KEY` once during initial setup only.
- Set `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync`, and `ALLOW_UAT_SEEDING=false`.
- Set `SHOW_QUICK_LOGIN=false`; Quick Login must remain disabled in production.
- Back up the database before every migration or deployment after launch.
- Run `php artisan migrate --force`; never run `php artisan migrate:fresh` on production.
- Run `php artisan db:seed --class=ProductionSeeder --force`; never run UAT/demo seeders in production.
- Create the first platform admin with `php artisan production:create-admin --role=platform_owner`.
- Create the first store with `php artisan production:create-store --name="ACDC Mobile" --slug=acdc-mobile`.

## Migration-Edit History Note

This project is still pre-hosting. Several migrations were created during the hardening/UAT phases. Treat the current migration set as the release-candidate baseline and do not edit migrations after production data exists.

## Queue And Scheduler

The MVP release is configured for `QUEUE_CONNECTION=sync`. No always-on queue worker is required for the current MVP. If background jobs are added later, configure a process supervisor. No production scheduler requirement is currently confirmed.

## Rollback Overview

Use maintenance mode, restore the previous code release, restore the database backup when migrations or data maintenance changed data, restore storage if needed, clear/rebuild caches, and verify login/catalog/admin/order flows before reopening the site.
