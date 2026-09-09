# DataPOS — Offline-First Commercial Retail & POS Platform

DataPOS သည် မြန်မာနိုင်ငံရှိ မိုဘိုင်းဖုန်း၊ အီလက်ထရောနစ်၊ ကွန်ပျူတာ၊ စက်ပစ္စည်းနှင့် အထွေထွေ လက်လီ/လက်ကား (SME) အရောင်းဆိုင်များအတွက် အင်တာနက်မရှိဘဲ (၀% Network Dependency) လုံးဝ သီးသန့် အသုံးပြုနိုင်သော **Offline-First POS Counter**၊ **ကုန်ပစ္စည်းလက်ကျန် (Stock Ledger & Valuation)**၊ **အရစ်ကျ/အကြွေးစာရင်း (Receivables)**၊ **ဝန်ဆောင်မှု/ပြင်ဆင်ရေး (Repair & Service)**၊ **အသုံးစရိတ်နှင့် ငွေစာရင်း (Cash/Bank & P&L)** နှင့် **Online Storefront (E-Commerce)** တို့ကို စနစ်တစ်ခုတည်းတွင် စုစည်းစီမံနိုင်ရန် တည်ဆောက်ထားသော Production-Grade Laravel 12 Enterprise POS Platform ဖြစ်ပါသည်။

---

## 1. Current Release & Readiness Status

- **Status:** `Release Candidate (RC) — Pre-Installer Automated Verification Baseline`
- **Current Git HEAD:** `d9c4aeac275e7682e4f4b6b72f3b909967a3a29e` (`main` branch)
- **Automated Verification:** **1,743 Passed, 1 Skipped, 0 Failed (7,966 Assertions)**
- **Continuous Integration:** Automated GitHub Actions CI workflow active via [`.github/workflows/ci.yml`](.github/workflows/ci.yml)
- **Physical Hardware Verification:** `PENDING — Human Verification` (Thermal printers, barcode scanners, cash drawers, clean PC restores)

---

## 2. Technology Stack & Supported Versions

| Layer | Technology | Pinned / Supported Version | Purpose |
| :--- | :--- | :--- | :--- |
| **Backend Runtime** | **PHP** | `^8.2.12` (x64) | Core server execution |
| **Framework** | **Laravel** | `12.64.0` | MVC architecture, Eloquent ORM, Routing |
| **Database** | **SQLite (Primary)** | `PDO SQLite / SQLite3` (WAL Mode) | Offline zero-config local database |
| **Database (Alt)** | **MySQL / MariaDB** | `^8.0 / ^10.4` | Optional client-server / cloud deployment |
| **Reactive UI** | **Livewire** | `^4.3` | Dynamic reactive components |
| **Frontend Scripting** | **Alpine.js** | `^3.15.12` | Lightweight client-side reactivity |
| **Styling** | **Tailwind CSS** | `^4.3.3` | Ultra-dense utility design system |
| **Build Tool** | **Vite** | `^7.0.7` | Asset bundling & immutable hashing |
| **Spreadsheets** | **PhpSpreadsheet** | `^5.9` | High-fidelity `.xlsx` and `.csv` import/export |
| **PDF Rendering** | **html2pdf.js** | `^0.14.0` | Client-side vector voucher PDF printing |
| **Web Push** | **Laravel WebPush** | `^11.0` | Offline PWA push notification engine |
| **Typography** | **Google Fonts** | `Noto Sans Myanmar`, `Outfit`, `Roboto` | Tri-lingual offline bundled fonts |

---

## 3. Supported Business Modes

1. **POS-Only Counter Mode (Standalone Retail/Cashier):**
   - Direct counter landing page; bypasses storefront.
   - 0% external internet dependency.
   - Cashier shift management, cash drawer reconciliation, barcode scanning, thermal receipt printing.
2. **Hybrid Online Storefront + POS Counter:**
   - Public-facing e-commerce storefront for customers (browsing, glass finder, online ordering).
   - Backoffice POS counter for shop staff.
   - Unified real-time stock balances across counter and web catalog.
3. **Multi-Store Isolation Architecture:**
   - Single installation can host multiple store tenants (`store_id` isolation).
   - Cross-store data leakage strictly blocked by `EnsureStoreAccess` and global query scopes.

---

## 4. Implemented Module Inventory

All 22+ core operational modules are fully implemented with verified routes, controllers, views, and automated tests:

### POS & Sales Operations
- **POS Terminal:** Fast cart interaction, barcode scanning, discount controls, multiple payment methods (Cash, KPay, Wave, Bank Transfer, Split Payments).
- **Cashier Shifts & Register Lock:** Opening float, live cash in drawer tracking, mid-shift cash drops, expected vs actual cash reconciliation, shift closing report.
- **Document Sequencing:** Store-scoped, period-locked invoice number generation (`RCP-YYYYMMDD-XXXX`).
- **POS Returns & Refunds:** Return item processing against original receipts, cash/credit refunds, restock controls.
- **Held Sales (Hold/Resume):** Multiple customer carts held in memory and resumed instantly.

### Inventory & Warehouse Management
- **Product Master Data:** Categories, brands, compatible models, specifications, warranty, wholesale tiers.
- **Inventory Valuation:** Real-time stock valuation by FIFO/Weighted Average cost.
- **Stock Ledger (Double-Entry):** Immutable stock movements (`opening`, `sale`, `return`, `adjustment`, `transfer_in`, `transfer_out`).
- **Stock Count & Auditing:** Blind physical stock counts, variance analysis, auto-generated reconciliation adjustments.
- **Stock Transfers & Warehouses:** Multi-branch warehouse transfers with dispatch and receive confirmation workflows.
- **Buy-Back & Trade-In:** Second-hand device and part buy-back workflow with customer ID verification.

### Purchasing & Payables
- **Suppliers & Purchase Orders:** Vendor management, PO creation, goods received notes (GRN), purchase returns.
- **Accounts Payable:** Supplier payment recording and outstanding debt tracking.

### Finance, Cash & Accounting
- **Customer Receivables & Debt Aging:** Customer credit balances, credit limits, 30/60/90-day debt aging brackets, debt collection settlements.
- **Expenses & SME Categories:** Standard expense tracking (rent, utilities, salaries, maintenance) with receipt attachments.
- **Cash & Bank Transactions:** Internal cash transfers between cash drawers, safe, and bank accounts.
- **Profit & Loss (P&L):** Accurate gross and net profit computation taking into account sales, COGS, discounts, and operating expenses.

### Service & Device Repairs
- **Repair Jobs & Service Tickets:** Device intake, intake condition checklist, technician assignment, status pipeline (received, diagnosing, repaired, delivered).
- **Spare Parts Usage:** Consumed spare parts deduction directly from inventory ledger upon repair completion.
- **Warranty & IMEI Tracker:** Serial number and IMEI warranty claim management.

### Storefront, Admin & Security
- **Storefront & Web Catalog:** Product cards, category flyouts, search suggestions, order placement.
- **Audit Logs:** System-wide immutable security audit trail recording all critical actions, actors, IP addresses, and previous states.
- **Database Tools & Backup/Restore:** One-click full system backup packages (Database SQL + Media Files + Manifest ZIP), preflight integrity verification, and secondary PC disaster recovery.
- **Voucher & Theme Customizer:** Dynamic 58mm/80mm receipt templates, brand logos, headers, footers.

---

## 5. Known Partial / Deferred Modules

To maintain architectural honesty, the following capabilities are explicitly deferred to future milestones:
- **Cloud Central Synchronization:** Multi-branch central cloud aggregation (deferred pending dedicated sync server architecture).
- **Native Android APK:** Counter currently runs on modern browsers and PWA; native Java/Kotlin Android shell is planned for v2.
- **Automated Payment Gateway Webhooks:** CBPay/KBZPay direct API webhooks (currently operates via manual transaction reference verification).

---

## 6. Local Development Requirements

- **Operating System:** Windows 10/11 (or Linux / macOS for web-only development)
- **PHP:** `8.2.12` or higher (Required extensions: `bcmath`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`, `zip`)
- **Composer:** `2.2+` (Lockfile compliant)
- **Node.js & npm:** Node `v20+` or `v26+`, npm `11+`
- **Local Web Server:** Built-in PHP server or XAMPP at `D:\xmapp` / `C:\xampp`

---

## 7. Canonical Database & Storage Paths

Following the [Windows Runtime and Storage Architecture](docs/windows_runtime_and_storage_architecture.md), program binaries and writable application state are strictly decoupled:

- **Canonical Database Path:** `storage/database/datapos.sqlite`
- **Legacy Fallback Path:** `database/database.sqlite` (Automatically recognized if present; zero data loss)
- **Media & Uploads:** `storage/app/public/`
- **System Backups:** `storage/app/private/backups/`
- **Portable Backups:** `storage/app/backups/`
- **Framework Temporary Files:** `storage/framework/{cache,sessions,views}`
- **Application Logs:** `storage/logs/`

---

## 8. Windows Local & UAT Run Instructions

### Step 1: Clone Repository
```powershell
git clone https://github.com/shwepyithit568-commits/DataPOS.git
cd DataPOS
```

### Step 2: Configure Environment
```powershell
Copy-Item .env.example .env
php artisan key:generate
```

### Step 3: Install Dependencies
```powershell
composer install --prefer-dist
npm ci
```

### Step 4: Build Frontend Assets
```powershell
npm run build
```
*(For active frontend development with hot-reload, run `npm run dev` in a separate terminal).*

### Step 5: Database Migration & Safe UAT Seeding
```powershell
# Create canonical storage database directory if missing
New-Item -ItemType Directory -Force -Path storage\database

# Run database migrations
php artisan migrate

# Optional: Seed realistic UAT testing data (Requires ALLOW_UAT_SEEDING=true in .env)
php artisan db:seed --class=UatSeeder
```

### Step 6: Start Local Application Server
Standardized on canonical port **`8501`** (fallback `8502`):

```powershell
php artisan serve --host=127.0.0.1 --port=8501
```
Or via the offline-cached production router:
```powershell
php -S 127.0.0.1:8501 server.php
```

### Step 7: Access DataPOS in Browser
- **Storefront Home:** `http://127.0.0.1:8501/store/datapos-mobile`
- **POS Counter Terminal:** `http://127.0.0.1:8501/store/datapos-mobile/pos`
- **Admin Management Panel:** `http://127.0.0.1:8501/store/datapos-mobile/admin/dashboard`
- **System Health Endpoint:** `http://127.0.0.1:8501/up` (Returns HTTP 200 OK)

---

## 9. Default Local Test Accounts (UAT Only)

> [!WARNING]
> The accounts below are seeded **exclusively** during local testing via `UatSeeder`. `UatSeeder` strictly halts if `APP_ENV` is set to `production` or `staging`. These accounts do not exist in commercial production builds.

| Role | Login Identifier (Phone) | Default Password | Access Area |
| :--- | :--- | :--- | :--- |
| **Platform Owner** | `09100000001` | `password` | Global System Administration |
| **Store Manager** | `09100000002` | `password` (POS PIN: `1234`) | Store Admin & POS Overrides |
| **Cashier / Staff** | `09100000003` | `password` (POS PIN: `1234`) | POS Counter Terminal |
| **Technician** | `09100000005` | `password` | Service & Repair Jobs |
| **Stock Keeper** | `09100000006` | `password` | Warehouses & Stock Transfers |
| **Accountant** | `09100000007` | `password` | Finance, P&L, Receivables |
| **Wholesale Customer**| `09100000008` | `password` | Wholesale Catalog & Tier Pricing |
| **Retail Customer** | `09100000010` | `password` | Public Storefront Shopping |

---

## 10. Automated Tests & CI Commands

DataPOS enforces strict automated testing across unit logic, feature controllers, store isolation, financial ledgers, and tri-lingual translation key parity.

### Run Full Test Suite:
```powershell
php artisan test
```
*Current Suite Baseline: 1,743 passed, 1 skipped, 0 failed (7,966 assertions).*

### Run Targeted Subsystems:
```powershell
# POS counter & cashier shifts
php artisan test --filter=POS

# Multi-lingual translation parity (my, en, zh_CN)
php artisan test --filter=LocalizationTest

# Backup & disaster recovery integrity
php artisan test --filter=Backup

# Windows canonical runtime paths
php artisan test --filter=WindowsStorageCanonicalizationTest
```

### Validate Composer Packages & Platform Requirements:
```powershell
composer validate --strict
composer check-platform-reqs
```

---

## 11. Backup & Disaster Recovery Workflow

DataPOS features a zero-configuration, self-contained disaster recovery engine ([`DatabaseBackupService`](app/Services/DatabaseBackupService.php)):

1. **Full-System Backup Package (ZIP):**
   - Automatically exports complete database SQL dump + raw SQLite binary.
   - Archives all uploaded media (`storage/app/public` logos, product images, banners).
   - Generates cryptographic `manifest.json` containing app version, driver, timestamp, and SHA-256 verification hashes.
2. **One-Click Fresh-PC Restore:**
   - When a backup ZIP is uploaded to a clean secondary PC, DataPOS validates the manifest, restores database tables with foreign keys preserved, and re-inflates all media files into `storage/app/public/`.
3. **Automated Pruning:** Retains the 14 most recent backup snapshots to prevent disk exhaustion.

---

## 12. Reporting, Export & Printing Summary

- **Excel & CSV Export:** Integrated via PhpSpreadsheet across products, stock balances, inventory valuation, receivables, expenses, sales, and audit logs.
- **Vector Voucher PDF Generation:** Client-side vector generation via `html2pdf.js` with instant browser print preview.
- **ESC/POS Thermal Receipt Engine:** Native direct byte-level generator for 58mm and 80mm thermal receipt printers, supporting cash drawer kick pulses (`ESC p 0 25 250`) and cut commands (`GS V 66 0`).

---

## 13. Offline vs. Sales-Channel Terminology

- **Offline POS Counter:** Refers to the physical cashier counter application operating locally on SQLite with 100% operational autonomy without external internet access.
- **Sales Channels:** Refers to business transaction sources:
  - `pos`: Physical counter register checkout.
  - `ecommerce`: Online orders placed through the public storefront.
  - `wholesale`: Bulk order purchases approved under negotiated pricing.

---

## 14. Security Rules & Secret Handling

- **Zero Secrets in Repository:** Real `.env` files, APP_KEYs, database passwords, and API credentials must **never** be committed to Git.
- **Database Dumps Protected:** All database dumps (`*.sql`, `*.sqlite`) are gitignored.
- **Public Git Exposure Notice:** If sensitive legacy dump files are present in historical commits, refer to [Security and Release Archive Audit](docs/security_and_release_archive_audit.md) for credential rotation procedures.

---

## 15. Destructive Commands (NEVER Run on Production)

> [!CAUTION]
> The following commands will cause permanent data loss. **NEVER** run these commands on a live store or production database:

```powershell
# PROHIBITED ON PRODUCTION:
php artisan migrate:fresh          # Drops all tables and destroys real data
php artisan migrate:fresh --seed   # Destroys real data and seeds test fixtures
php artisan db:wipe                # Drops all database objects
git reset --hard                   # Overwrites local modifications
```

---

## 16. Documentation Index

All project documentation uses repository-relative markdown links:

- [Project Commands Cheatsheet](docs/PROJECT_COMMANDS_CHEATSHEET.md) — Daily development and operational commands.
- [Myanmar Business Commercial Readiness Plan](docs/myanmar_business_commercial_readiness_plan_v1.md) — 20-point enterprise readiness specification.
- [Pre-Installer Automated Baseline Report](docs/pre_installer_automated_baseline_report.md) — Prompt 1 baseline verification report.
- [Security and Release Archive Audit](docs/security_and_release_archive_audit.md) — Prompt 2 secret audit, dependency scan, and license manifest.
- [Windows Runtime and Storage Architecture](docs/windows_runtime_and_storage_architecture.md) — Prompt 3 canonical storage and runtime specification.
- [Phase F Completion Report](docs/phase_f_completion_report.md) — Automated audit report and evidence matrix.
- [AI Agents Pre-Installer Prompts v1](docs/datapos_ai_agents_pre_installer_prompts_v1.md) — 8-step pre-installer governance roadmap.
- [Production Deployment Guide](docs/ops/DEPLOYMENT.md) — Infrastructure setup and deployment instructions.
- [Storefront UI/UX Standard Guide v1.0](docs/STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md) — Comprehensive storefront component guide.

---

## 17. Current Human Verification Pending List

In compliance with the project's Strict Engineering Craftsmanship Policy, automated tests do not substitute for physical hardware testing. The following gates remain pending human verification:

1. `PENDING — Human Verification`: Physical 58mm & 80mm thermal receipt printer test (Myanmar font legibility & alignment).
2. `PENDING — Human Verification`: Physical USB & Bluetooth barcode scanner testing with Myanmar retail barcodes.
3. `PENDING — Human Verification`: Cash drawer electronic kick pulse triggering on cash checkout.
4. `PENDING — Human Verification`: Clean Windows 10/11 fresh PC installation and first-run verification.
5. `PENDING — Human Verification`: Database backup restoration test on a clean second PC.
6. `PENDING — Human Verification`: Physical sudden power cut / abrupt shutdown recovery on an active cashier register.
7. `PENDING — Human Verification`: Seven-day physical offline shop pilot operating with zero external network connectivity.
8. `PENDING — Human Verification`: Store Owner final commercial acceptance sign-off.

---

## 18. Windows Offline Installer Status & Prerequisites

- **Status:** Architectural specification complete ([`windows_runtime_and_storage_architecture.md`](docs/windows_runtime_and_storage_architecture.md)); Inno Setup script creation and binary build are deferred until Prompt 7 approval.
- **Prerequisites:** Project Owner resolution of open design questions (code signing certificate, auto-backup schedule, clean PC verification).

---

## 19. Contribution & Development Guidelines

All contributors and AI pairing assistants must strictly follow the standards defined in [`AGENTS.md`](AGENTS.md):

1. **Ultra-Dense 2px Rhythm:** Maintain `@section('main_padding', 'p-0.5 sm:p-1')`, `<div class="w-full space-y-0.5 pb-6">`, `gap-0.5 sm:gap-1`.
2. **Zero Hardcoded Currency:** Never write hardcoded `Ks` in Blade templates or Javascript. Use `format_currency($amount, $store)` or `window.formatCurrency(val)` dynamically based on store settings.
3. **Clean Quantity Formatting:** Never display `.000` on integer quantities. Use `format_quantity($qty, $store)` (e.g. `10` instead of `10.000`).
4. **Tri-Lingual Language Invariance:** Every user-facing string must simultaneously provide keys in:
   - `lang/my/messages.php` (Natural Myanmar language)
   - `lang/en/messages.php` (English)
   - `lang/zh_CN/messages.php` (Simplified Chinese)

---

## 20. License & Commercial Status

- **Application Software:** Proprietary Commercial Software. All rights reserved by the Project Owner.
- **Third-Party Libraries:** Built upon permissive open-source foundations (Laravel [MIT], Livewire [MIT], PhpSpreadsheet [MIT], Alpine.js [MIT], TailwindCSS [MIT], html2pdf.js [MIT], Noto Sans Myanmar [SIL OFL 1.1], PHP Runtime [PHP License v3.01]).
- **Distribution:** Redistribution, resale, or packaging without explicit authorization from the Project Owner is prohibited.
