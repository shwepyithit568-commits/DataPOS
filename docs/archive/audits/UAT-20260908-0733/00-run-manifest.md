# UAT Run Manifest — UAT-20260908-0733

## Environment

| Item | Value |
|---|---|
| Test run ID | UAT-20260908-0733 (defined by `UatCoordinatorSetupSeeder::RUN_ID`) |
| Test date/time | 2026-09-08, execution started 17:45 (Asia/Yangon, UTC+6:30) |
| Git commit SHA | `3566f06c6958ad03028a1a17ed17a8bf0058eae5` |
| Environment URL | http://127.0.0.1:8502 (local staging-equivalent, `APP_ENV=local`) |
| Database | SQLite `database/database.sqlite` (local, single-tenant dev DB — no production data present) |
| PHP version | 8.2.12 (XAMPP) |
| Node version | v26.0.0 |
| Browser | Chromium (Freebuff preview harness) |
| Seeder | `php artisan migrate:fresh --seed --seeder=UatCoordinatorSetupSeeder --force` |
| DB backup (rollback point) | `database/backup-pre-uat-20260908.sqlite` (1,789,952 bytes, taken 17:43 before reset) |

## Safety verification

- [x] Environment is local staging-equivalent; no real customer data (all accounts are UAT-prefixed seed data).
- [x] Recoverable DB snapshot taken before testing (backup file above).
- [x] `ALLOW_UAT_SEEDING=true` + `APP_ENV=local` — seeder safety guard satisfied.
- [x] No pre-existing records deleted; database reset performed with explicit user approval (backup + fresh re-seed option).
- [x] All created records use run-ID/UAT prefixes.

## Baseline automated test run (`php artisan test`)

| Metric | Value |
|---|---|
| Passed | 1,703 |
| Failed | 0 |
| Skipped | 1 |
| Assertions | 7,679 |
| Duration | 141.57s |

Baseline build: `npm run build` — SUCCESS (Vite build completed, assets emitted under `public/build/`).

## Application smoke test

- [x] Login page renders (HTTP 200) at `/login`.
- [x] Real password login as Platform Owner (`09100000001` / `password`) → redirected to `/admin/dashboard`.
- [x] Browser console: 0 errors on login + dashboard load.
- [x] Network: all requests 200 (fonts, CSS, JS).

## Test stores

| Store | Slug | Purpose |
|---|---|---|
| Store A | `uat-pos-store` | Main workflow testing (POS, purchases, ecommerce, repair, finance) |
| Store B | `uat-isolation-store` | Cross-store isolation testing (0 products, empty) |

## Test users (all passwords `password`, POS PIN `1234`)

| Role | Phone | Notes |
|---|---|---|
| Platform Owner | 09100000001 | Platform scope |
| Store Owner (A) | 09800000001 | Full store admin |
| Store Manager (A) | 09800000002 | Stock/sales/daily ops |
| Cashier (A) | 09800000003 | POS counter |
| Inventory Staff (A) | 09800000004 | Stock keeper |
| Accountant (A) | 09800000005 | Finance/reports |
| Technician (A) | 09800000006 | Repair/service |
| Ecommerce Staff (A) | 09800000007 | Online orders |
| Restricted Custom (A) | 09800000008 | Catalog view only |
| UAT Customer | 09822222222 | Retail customer (storefront) |

## Shared test dataset (opening state — verified in DB after fresh seed)

| Record | Value | Verified |
|---|---|---|
| Product A | SKU `UAT-PHONE-001`, cost MMK 300,000, price MMK 350,000, ledger qty 10 | ledgerQty=10 |
| Product B | SKU `UAT-CASE-001`, cost MMK 10,000, price MMK 15,000, ledger qty 20 | ledgerQty=20 |
| Supplier | `UAT Supplier` | created |
| Customer | `UAT Customer` | created |
| Opening cashier cash | MMK 100,000 (Register 1, open shift) | 1 open shift |
| POS sales / online orders | 0 / 0 | confirmed |

## Pre-test findings (Phase 0)

| # | Severity | Finding |
|---|---|---|
| F-0.1 | Low | Raw translation key `messages.all_stores` leaks on Platform Owner dashboard store selector (visible as literal text + `MESSAGES.ALL_STORES` sub-label). Localization defect, cosmetic. |
