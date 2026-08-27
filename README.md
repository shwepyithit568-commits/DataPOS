# DataPOS

DataPOS သည် Myanmar SME ဆိုင်များအတွက် offline-first POS, inventory, purchasing, finance, service, customer, reports, storefront စနစ်များကို တစ်နေရာတည်းမှာ စီမံနိုင်ရန် တည်ဆောက်နေသော Laravel project ဖြစ်သည်။

## Current Project Baseline

| Item | Current State |
|---|---|
| Framework | Laravel 12.64.0 |
| PHP | PHP 8.2.12 via XAMPP (`D:\xmapp\php\php.exe`) |
| Frontend | Blade, Alpine.js, Tailwind CSS 4, Vite |
| Database | Local SQLite by default (`database/database.sqlite`) |
| Store slug | `datapos-mobile` |
| Local app port | Docs standard: `8501` |
| Environment | Local development / UAT only |
| Production deploy | Not approved yet; treat live deployment as a separate project phase |

> Note: Local `.env` currently has `APP_URL=http://127.0.0.1:8502`, but project docs and daily commands standardize on port `8501`. If browser links generate `8502`, update local `.env` or clear config cache.

## What Is Already Built

Core POS and admin modules are implemented as route-backed Laravel screens, not just placeholders:

- POS sale, cashier shift, register lock, held sale, sale return/refund, daily closing
- Inventory ledger, inventory balances, opening stock, adjustments, reconciliation, stock count
- Products, categories, brands, product import, smart product form, web catalog visibility
- Purchasing: suppliers, purchase orders, purchase returns, payables, warehouses, stock transfers, buy back
- Finance: customer receivables, expenses, expense categories, cash/bank transactions, profit and loss
- Service: repair jobs, service jobs, spare parts, warranty/serial/IMEI tracking
- Ecommerce storefront: catalog, orders, banners, blog, reviews, wholesale, promotions, web push
- Admin operations: users, roles, audit logs, backups, database tools, alert center, pilot import hub

`store.admin.coming-soon` route still exists for future roadmap items, but the 22 high-priority admin modules described in [ADMIN_MODULES_EXECUTION_ROADMAP.md](D:/xmapp/htdocs/DataPOS/docs/ADMIN_MODULES_EXECUTION_ROADMAP.md) have real routes/controllers/views/tests in the current codebase.

## Recent Verification Notes

- `php artisan about --only=environment` reports Laravel `12.64.0`, PHP `8.2.12`, environment `local`.
- `WarehouseController` is store-scoped and `/admin/warehouses` routes use `EnsureStoreAccess`.
- `SupplierController.php` is valid UTF-8.
- Tests are present for the admin and POS modules; run the current suite before release because old docs may contain stale pass counts.

## Local Run

Open a new PowerShell terminal:

```powershell
cd D:\xmapp\htdocs\DataPOS
php artisan serve --host=127.0.0.1 --port=8501
```

If `php` is not recognized in the current terminal:

```powershell
$env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [Environment]::GetEnvironmentVariable("Path", "User")
```

Fallback:

```powershell
D:\xmapp\php\php.exe artisan serve --host=127.0.0.1 --port=8501
```

Open:

```text
http://127.0.0.1:8501/store/datapos-mobile
http://127.0.0.1:8501/store/datapos-mobile/pos
http://127.0.0.1:8501/store/datapos-mobile/admin/dashboard
```

Frontend watcher:

```powershell
npm run dev
```

Production asset build:

```powershell
npm run build
```

## Common Commands

```powershell
composer install
npm install
php artisan migrate
php artisan db:seed --class=UatSeeder
php artisan optimize:clear
php artisan test
```

Full command reference:

- [PROJECT_COMMANDS_CHEATSHEET.md](D:/xmapp/htdocs/DataPOS/docs/PROJECT_COMMANDS_CHEATSHEET.md)

## Default Local Test Accounts

Default password is `password`.

| Role | Login Phone | Main Area |
|---|---|---|
| Platform Owner | `09100000001` | `/admin/dashboard` |
| Store Manager | `09100000002` | `/store/datapos-mobile/admin/dashboard`, POS PIN `1234` |
| Staff / Cashier | `09100000003` | `/store/datapos-mobile/pos` |
| Wholesale Customer | `09100000004` | `/store/datapos-mobile/wholesale` |
| Retail Customer | `09100000006` | `/store/datapos-mobile` |

## Documentation Map

| File | Purpose |
|---|---|
| [Source_of_Truth_MM.md](D:/xmapp/htdocs/DataPOS/Source_of_Truth_MM.md) | Business and architecture rules. Update only when rules change. |
| [DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md](D:/xmapp/htdocs/DataPOS/DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md) | POS module rules and offline-first direction. |
| [CHANGELOG.md](D:/xmapp/htdocs/DataPOS/CHANGELOG.md) | Historical implementation log. |
| [Testing_check.md](D:/xmapp/htdocs/DataPOS/Testing_check.md) | Test notes, known issues, and manual QA records. |
| [PROJECT_COMMANDS_CHEATSHEET.md](D:/xmapp/htdocs/DataPOS/docs/PROJECT_COMMANDS_CHEATSHEET.md) | Daily commands for run/test/debug. |
| [ADMIN_MODULES_EXECUTION_ROADMAP.md](D:/xmapp/htdocs/DataPOS/docs/ADMIN_MODULES_EXECUTION_ROADMAP.md) | Current admin module inventory and completion/readiness matrix. |
| [ADMIN_UI_UX_STANDARD_GUIDE.md](D:/xmapp/htdocs/DataPOS/docs/ADMIN_UI_UX_STANDARD_GUIDE.md) | UI/UX implementation standards for admin pages. |
| [MYANMAR_SME_COMMERCIALIZATION_GUIDE.md](D:/xmapp/htdocs/DataPOS/docs/MYANMAR_SME_COMMERCIALIZATION_GUIDE.md) | Myanmar SME sales, demo, installer, backup, and rollout strategy. |
| [docs/ops/DEPLOYMENT.md](D:/xmapp/htdocs/DataPOS/docs/ops/DEPLOYMENT.md) | Deployment and production environment guide. |
| [docs/ops/pilot-recovery-cutover-runbook.md](D:/xmapp/htdocs/DataPOS/docs/ops/pilot-recovery-cutover-runbook.md) | Pilot recovery and cutover workflow. |

## Development Rules

Before code changes:

1. Read the related source-of-truth document.
2. Inspect existing routes, controllers, models, views, migrations, tests, and translations.
3. Keep changes small and preserve working behavior.
4. Scope all store data by `store_id` / current `StoreContext`.
5. Avoid hardcoded UI text in admin views; update `lang/en/messages.php`, `lang/my/messages.php`, and `lang/zh_CN/messages.php`.
6. Run targeted tests first, then broader tests when the change touches shared behavior.

## Production Safety

This repository is still treated as local/UAT for DataPOS resale preparation.

Do not run these on production:

```powershell
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan db:seed --class=UatSeeder
php artisan db:seed --class=DemoCatalogSeeder
```

Production deployment must be a separate controlled phase:

```powershell
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan production:create-admin --role=platform_owner
```

Security note: historical docs mention that real production credentials once existed in git history. Before making this repository public or deploying from it, rotate any affected credentials and scrub history as described in [DEPLOYMENT.md](D:/xmapp/htdocs/DataPOS/docs/ops/DEPLOYMENT.md).

## Recommended Next Phase

Focus on commercialization readiness before adding more large modules:

1. Build a mobile-shop demo preset first.
2. Add a safe admin demo preset switcher.
3. Add one-click local backup and restore workflow.
4. Verify one real pilot workflow: product import, POS sale, return, stock count, debt collection, daily closing, P&L.
5. Package a simple local installer only after pilot workflow is stable.
6. Defer licensing and Android APK until the local pilot is proven.
