# DataPOS — Security, Secrets and Public Repository Audit Report (Prompt 2)

**Audit Date:** 2026-09-09  
**Repository:** `https://github.com/shwepyithit568-commits/DataPOS`  
**Audited Git Commit:** `f3534be80605d2060e4586dac2dabe4a951e3d49` (Prompt 1 baseline HEAD)  
**Branch:** `main`  
**Auditor / Agent:** Antigravity (Prompt 2 — Security, Secrets & Public Repository Audit)

---

## 1. Executive Summary

A comprehensive read-heavy security, credential, secret exposure, and dependency audit was performed across the current repository and full Git history prior to building release archives and the Windows Offline Installer.

### Key Audit Highlights:
- **No Active Production Secrets in `.env`:** `.env` is not tracked in Git. Only `.env.example` with safe placeholder strings (`change_me`, `yourdomain.com`) exists in the repository.
- **No Private Keys / SSH Credentials:** Full Git history scan confirms zero tracked `.pem`, `.ppk`, `id_rsa`, or private SSH keys.
- **CRITICAL FINDING — Tracked SQL Dump Containing Real User Data:**
  The file `manual_2026-08-30_015910.mysql.sql` (3.6 MB) was added in commit `01618dc0d70e4004807f0c9e7fba7ef1620809f3` and is currently tracked in the repository. It contains real owner/staff/customer phone numbers, names, bcrypt password hashes, and active session tokens from August 2026.
- **Seeder Safety Guards Verified:** `DatabaseSeeder` contains zero automatic seed calls; `ProductionSeeder` seeds only standard blog content and expense categories; `UatSeeder` strictly halts if `APP_ENV=production|staging` or `ALLOW_UAT_SEEDING` is not `true`.
- **Dependency Audit:** `npm audit --omit=dev` reported **0 vulnerabilities**. `composer audit` identified 11 advisories across 2 packages (`league/commonmark` and `livewire/livewire`).
- **License Compliance:** All runtime dependencies use permissive open-source licenses (MIT, BSD-3-Clause, Apache 2.0, SIL OFL 1.1). Zero viral copyleft (GPL/AGPL) licenses exist in the application stack.

---

## 2. Sanitized Secret & Sensitive Exposure Findings Table

All raw credentials and secret tokens are strictly redacted below in compliance with Common Rules.

| ID | Category | File Path / Location | Commit | Description / Exposure | Severity | Status / Remediation |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **SEC-01** | **Customer / Staff Data** | `manual_2026-08-30_015910.mysql.sql` | `01618dc` | Contains `users` table with 9 real user records: Myanmar names, real phone numbers (`09784343151`, `09254343151`, etc.), email (`shwepyithit568@gmail.com`), real bcrypt hashes (`$2y$12$...`), and remember tokens. | **CRITICAL** | **Tracked in Git.** Needs Project Owner credential rotation & approval to untrack/scrub. |
| **SEC-02** | **Session Hijacking** | `manual_2026-08-30_015910.mysql.sql` | `01618dc` | Contains `sessions` table with 10 session tokens and base64 serialized session payloads with visitor IP addresses and user agents. | **HIGH** | Invalidate all sessions. Exclude dump from all installer/release packages. |
| **SEC-03** | **Database Schema / Dumps** | `AlinnThit Mobile Shop Notes data/*.sql` | Various | Contains `brands.sql`, `categories.sql`, `posts.sql` with legacy database name `u595335768_alinnthit_db`. No customer data or credentials. | **LOW** | Informational. Should be excluded from release distribution. |
| **SEC-04** | **Application Key** | `.env.example` / `docs/*` | All | `APP_KEY=base64:GENERATE_ON_SERVER_ONCE` used as dummy placeholder in docs. | **INFO** | Safe placeholder. No real key exposed. |
| **SEC-05** | **Database Credentials** | `.env.example` | Initial | Standard placeholder `DB_USERNAME=change_me`, `DB_PASSWORD=change_me`. | **INFO** | Safe placeholder. |

---

## 3. Git Tracking & `.gitignore` Audit

### 3.1 Already-Tracked Artifact Risk Analysis
Running `git ls-files` revealed that:
1. `manual_2026-08-30_015910.mysql.sql` is tracked by Git. Adding `*.sql` to `.gitignore` prevents future untracked files from being staged, but **Git will continue to track and version files that were already committed prior to the ignore rule**.
2. To remove it from Git tracking without deleting it from local disk, `git rm --cached manual_2026-08-30_015910.mysql.sql` is required.
3. Because the file exists in past commit history (`01618dc0d70e4004807f0c9e7fba7ef1620809f3`), complete removal from public Git history requires `git-filter-repo` or BFG Repo-Cleaner followed by a force-push, which requires explicit Project Owner approval.

### 3.2 `.gitignore` Enhancements Made
Updated `.gitignore` with the following defensive rules:
- Broad `.env.*` exclusion (with `!.env.example` whitelist) to prevent accidental staging of `.env.local`, `.env.staging`, or `.env.production`.
- `*.sql` wildcard to block accidental commits of database dumps.
- `/storage/database/*.sqlite*` to protect canonical SQLite databases created by the upcoming runtime architecture.

---

## 4. Required Credential Rotation Checklist for Project Owner

Due to the presence of `manual_2026-08-30_015910.mysql.sql` in the repository commit history:

- [ ] **Rotate Platform Owner Account Passwords:**
  - Change password for user associated with phone `09784343151` (`KoKoLInn`).
  - Change password for user associated with phone `09254343151` and email `shwepyithit568@gmail.com` (`Shwe pyi Thit`).
- [ ] **Invalidate Live Sessions:**
  - Flush all session cookies / session records on the live server (`php artisan session:clear` or truncate `sessions` table).
- [ ] **Rotate Hostinger Database Credentials (if applicable):**
  - Verify database `u595335768_alinnthit_db` on Hostinger hPanel and rotate the DB user password if it matched any password previously used.
- [ ] **Decision on Public Git History Scrubbing:**
  - Option A (Recommended if repository is public): Run `git-filter-repo --invert-paths --path manual_2026-08-30_015910.mysql.sql` and force-push to GitHub.
  - Option B: Make the repository Private if not already private.

---

## 5. Production & UAT Seeder Isolation Verification

| Seeder | Purpose | Contains Test Passwords / Demo Users | Production Safety Mechanism | Verification Status |
| :--- | :--- | :--- | :--- | :--- |
| `DatabaseSeeder.php` | Default artisan entry | **None** | Empty `run()` method with instructions; zero automatic calls. | **PASS (Safe)** |
| `ProductionSeeder.php` | Commercial first-run setup | **None** | Calls only `RefreshDataPOSBlogContentSeeder`, `ExpenseCategorySeeder`, `HowToOrderContentSeeder`. Zero user accounts or mock sales. | **PASS (Safe)** |
| `UatSeeder.php` | Full UAT local test harness | Contains test passwords (`'password'`), demo store accounts, mock vouchers. | `guardEnvironment()` aborts immediately if `APP_ENV` is `production` or `staging`, or if `ALLOW_UAT_SEEDING` is not `true`. | **PASS (Guarded)** |
| `MobileCatalogSqlSeeder.php` | Imports catalog products from dump | Reads `manual_2026-08-30_015910.mysql.sql` | **Extracts ONLY brands, categories, products, images, and variants.** Does NOT import users or sessions. | **PASS (Limited Scope)** |

Automated regression coverage in `tests/Feature/UatSeederSafetyTest.php` confirms that executing `UatSeeder` in production or staging throws an exception and halts immediately.

---

## 6. Dependency Security Audit Results

### 6.1 NPM Audit (`npm audit --omit=dev`)
```text
found 0 vulnerabilities
```
**Status:** `PASS` (Clean production frontend dependencies).

### 6.2 Composer Audit (`composer audit`)
Found 11 advisories across 2 packages:

1. **`league/commonmark` (10 advisories):**
   - **Advisories:** GHSA-8rr7-cvq3-gmfh, GHSA-jjv6-8j6v-6j52, GHSA-f8fg-pg57-v4j8, GHSA-j8pm-gj4c-rq4x, GHSA-mj63-m3rc-8ppr, GHSA-mh25-x5hq-wrqp, GHSA-jfm3-95jq-q3rf, GHSA-g2gp-3wwq-f4ph, GHSA-2q4p-g7hv-5rgv (CVE-2026-71488), GHSA-29pj-957v-52mc (CVE-2026-71478).
   - **Severity:** High / Medium (Denial of Service & XSS via AttributesExtension in crafted Markdown parsing).
   - **Context in DataPOS:** `league/commonmark` is pulled in as an indirect dependency of `laravel/framework`. DataPOS does not expose untrusted raw public user input to Markdown rendering without HTML sanitization (`CustomPageRenderingTest` verifies strip-tags sanitization).
   - **Remediation Plan:** Update via `composer update league/commonmark` once a compatible bump is tested across the suite; do not bump without compatibility verification.

2. **`livewire/livewire` (1 advisory):**
   - **Advisory:** GHSA-g3hc-697w-wm82 (CVE-2026-81887).
   - **Severity:** Medium (DOM-based XSS during client-side state handling in Livewire 4.x <= 4.3.3).
   - **Context in DataPOS:** Used for internal admin/POS UI interactions.
   - **Remediation Plan:** Monitor Livewire 4.x upstream releases for patch >= 4.3.4 and apply in routine maintenance.

---

## 7. Third-Party License Inventory Manifest

| Component | Upstream Author / Project | Version / Source | License Type | Redistribution & Commercial Rights |
| :--- | :--- | :--- | :--- | :--- |
| **PHP Runtime** | The PHP Group | 8.2.x Windows x64 binary | PHP License v3.01 | Permissive; commercial redistribution allowed with attribution. |
| **Laravel Framework** | Taylor Otwell | 12.64.0 | MIT License | Permissive; commercial redistribution & modification allowed. |
| **PhpSpreadsheet** | PHPOffice | 5.9.x | MIT License | Permissive; commercial use allowed. |
| **Livewire** | Caleb Porzio | 4.3.x | MIT License | Permissive; commercial use allowed. |
| **html2pdf.js** | Erik Koopmans | 0.14.0 | MIT License | Permissive; commercial use allowed. |
| **Alpine.js** | Caleb Porzio | 3.15.12 | MIT License | Permissive; commercial use allowed. |
| **TailwindCSS** | Tailwind Labs | 4.3.3 | MIT License | Permissive; commercial use allowed. |
| **Noto Sans Myanmar Font** | Google Fonts | OpenType TTF | SIL Open Font License 1.1 | Permissive; can be freely bundled with application software. |
| **Outfit Font** | Onsen Studio | WOFF2 | SIL Open Font License 1.1 | Permissive; can be bundled. |
| **Roboto Font** | Google | WOFF2 | Apache License 2.0 | Permissive; can be bundled with copyright notice. |
| **Inno Setup (Installer)** | Jordan Russell | Inno Setup 6.x | Inno Setup License (Modified BSD) | Free of charge for commercial use; no royalty fees. |

**License Assessment:** `100% COMPLIANT`. All components are compatible with commercial closed-source distribution. No viral copyleft obligations.

---

## 8. Installer & Source Release Archive Inclusion-Exclusion Matrix

To ensure zero leakage of credentials, test data, or bloated development dependencies, release builds must adhere to the following strict matrix:

| Artifact / Path | Release Source ZIP | Windows Offline Installer | Reason / Policy |
| :--- | :---: | :---: | :--- |
| **Source Code (`app/`, `bootstrap/`, `config/`, `routes/`, `resources/`)** | **INCLUDE** | **INCLUDE** | Core application functionality |
| **Compiled Frontend (`public/build/`)** | **INCLUDE** | **INCLUDE** | Production CSS/JS assets and web fonts |
| **Production Vendor (`vendor/` optimized, no-dev)** | **EXCLUDE** (lockfile only) | **INCLUDE** (pre-installed) | Offline runtime requires pre-bundled vendor |
| **PHP Windows x64 Runtime (`php/`)** | **EXCLUDE** | **INCLUDE** (pinned zip/dir) | Offline installer runtime dependency |
| **Real `.env`** | **EXCLUDE (BLOCK)** | **EXCLUDE (BLOCK)** | Must be generated dynamically on first installation |
| **`.env.example`** | **INCLUDE** | **INCLUDE** | Template for dynamic environment initialization |
| **Database Dumps (`*.sql`, `manual_*.sql`)** | **EXCLUDE (BLOCK)** | **EXCLUDE (BLOCK)** | Sensitive customer data / obsolete dumps |
| **Live SQLite DBs (`*.sqlite`, `database.sqlite`)** | **EXCLUDE (BLOCK)** | **EXCLUDE (BLOCK)** | Fresh DB must be migrated on first install |
| **Node Modules (`node_modules/`)** | **EXCLUDE** | **EXCLUDE** | Not needed at runtime (Vite assets pre-compiled) |
| **Git Metadata (`.git/`, `.github/`)** | **EXCLUDE** | **EXCLUDE** | Repository internals not needed for end-user runtime |
| **Development Cache / IDE (`.vscode`, `.idea`, etc.)** | **EXCLUDE** | **EXCLUDE** | Tooling noise |
| **Backups (`storage/app/uat-backups/`, `*.zip`)** | **EXCLUDE (BLOCK)** | **EXCLUDE (BLOCK)** | Sensitive backup data |
| **Local Excel Spreadsheets (`*.xlsx`)** | **EXCLUDE** | **EXCLUDE** | Local developer working sheets |
| **Storage Logs (`storage/logs/*.log`)** | **EXCLUDE** | **EXCLUDE** | Machine-specific log traces |
| **UAT Seeders (`UatSeeder.php`, etc.)** | **INCLUDE** (code only) | **INCLUDE** (disabled by env) | Sealed by `guardEnvironment()` |
| **Inno Setup Script (`installer.iss`)** | **INCLUDE** | **BUILD RUNNER** | Used to compile the setup executable |
