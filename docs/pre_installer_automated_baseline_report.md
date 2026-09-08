# DataPOS — Pre-Installer Automated Baseline Report (Prompt 1)

**Report Generated Date:** 2026-09-09  
**Target Repository:** `https://github.com/shwepyithit568-commits/DataPOS`  
**Baseline Git Commit:** `d8f7abecfbe74fade6fe2ff06084fd609b0f8252`  
**Current Branch:** `main`  
**Auditor / Agent:** Antigravity (Prompt 1 — Release Candidate Baseline, Test Evidence & GitHub CI)

---

## 1. Environment & Runtime Inventory

| Component | Version / Status | Verification Command | Notes |
| :--- | :--- | :--- | :--- |
| **PHP Runtime** | `8.2.12 (cli)` (ZTS Visual C++ 2019 x64) | `php -v` | Pinned for local Windows runtime |
| **Composer** | `2.9.4` (2026-01-22) | `composer -V` | Schema & lockfile verified valid |
| **Node.js** | `v26.0.0` | `node -v` | Modern LTS/Active runtime |
| **npm** | `11.12.1` | `npm -v` | Lockfile compliant |
| **Laravel Framework** | `12.64.0` | `php artisan about` | Latest 12.x stable |
| **Database (Test)** | `SQLite (PDO SQLite)` | `php -r "..."` | Tests execute deterministic `:memory:` SQLite |
| **Database (Local)** | `SQLite / MySQL` | `config/database.php` | Dual driver capable |
| **Vite** | `7.3.6` | `npm run build` | Assets compile in < 1s |

### Verified PHP Extensions (`composer check-platform-reqs`):
`bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_sqlite`, `phar`, `session`, `simplexml`, `sqlite3`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `zlib`.

---

## 2. Baseline Verification Commands & Exact Results

### 2.1 Composer Package Validation
```text
$ composer validate --strict
./composer.json is valid
```
**Status:** `PASS`

### 2.2 Platform Requirements Check
```text
$ composer check-platform-reqs
Checking platform requirements for packages in the vendor dir:
All 24 extension/platform requirements verified successfully.
```
**Status:** `PASS`

### 2.3 Laravel Environment About
```text
$ php artisan about --only=environment
  Application Name .......................................... DataPOS
  Laravel Version ........................................... 12.64.0
  PHP Version ............................................... 8.2.12
  Composer Version .......................................... 2.9.4
  Environment ............................................... local
  Debug Mode ................................................ ENABLED
  URL ....................................................... 127.0.0.1:8502
  Maintenance Mode .......................................... OFF
  Timezone .................................................. Asia/Yangon
  Locale .................................................... my
```
**Status:** `PASS`

### 2.4 Frontend Package Installation & Production Build
```text
$ npm ci
added 114 packages, and audited 115 packages in 4s

$ npm run build
vite v7.3.6 building client environment for production...
transforming...
✓ 60 modules transformed.
rendering chunks...
computing gzip size...
public/build/manifest.json                                  1.67 kB │ gzip:  0.39 kB
public/build/assets/Outfit-Regular-DUdsL-5p.woff2          41.82 kB
public/build/assets/Roboto-Regular-B3YRHit7.woff2         103.23 kB
public/build/assets/NotoSansMyanmar-Regular-ymaFtaIS.ttf  183.16 kB
public/build/assets/NotoSansMyanmar-Bold-B2V9onp9.ttf     183.36 kB
public/build/assets/app-B1F9vMRO.css                      268.54 kB │ gzip: 31.93 kB
public/build/assets/admin-CL0oZvTo.css                    328.47 kB │ gzip: 38.76 kB
public/build/assets/app-D8CId_R8.js                        14.65 kB │ gzip:  4.70 kB
public/build/assets/app-admin-DaNlZXq8.js                  17.50 kB │ gzip:  5.86 kB
public/build/assets/module.esm-CvtIwgpG.js                 93.85 kB │ gzip: 34.29 kB
✓ built in 747ms
```
**Status:** `PASS`

### 2.5 Translation Parity Verification
```text
$ php artisan test --filter=LocalizationTest
Tests: 8 passed (24 assertions)
```
- Multi-lingual key parity across `my` (Myanmar), `en` (English), and `zh_CN` (Chinese) is strictly verified.
**Status:** `PASS`

### 2.6 Full Automated Test Suite Execution
```text
$ php artisan test
Tests:    1 skipped, 1743 passed (7966 assertions)
Duration: 146.37s
```
- **Total Tests:** 1,744
- **Passed:** 1,743
- **Failed:** 0
- **Skipped:** 1 (`Tests\Feature\MysqlMigrationSmokeTest` — intentional skip when local MySQL daemon is absent; runs on SQLite test memory)
- **Total Assertions:** 7,966
**Status:** `PASS`

---

## 3. Root Cause Investigation & Safe Regression Fix

### Flaky Test Identified:
- **File:** `tests/Feature/CatalogPerPageTest.php`
- **Symptom:** `test_default_per_page_is_40` / `test_invalid_per_page_falls_back_to_40` intermittently failed with `assertDontSee('PerPage Product 41')`.
- **Root Cause:**
  In `CatalogPerPageTest::makeStoreWithProducts()`, test products were populated using `Product::create([... 'created_at' => now()->subMinutes($count - $i)])`. However, `created_at` is not in Eloquent `$fillable`. Eloquent silently discarded the custom timestamps and stamped all 45 products with the identical current second. When `CatalogController` ordered products by `$query->latest()` (`created_at DESC`), SQLite's indeterminate row order caused `PerPage Product 41` to intermittently land in the first page slice.
- **Remediation Applied:**
  Updated `makeStoreWithProducts()` to use `$product->timestamps = false;` and `$product->forceFill([...])->save();` with explicit chronological offsets. Verified across multiple consecutive runs; passes 100% deterministically.

---

## 4. GitHub Actions CI Infrastructure

Created `.github/workflows/ci.yml` with the following automated pipeline:
1. **Trigger:** `push` and `pull_request` on `main`.
2. **Environment:** `ubuntu-latest`, PHP `8.2`, Node `20`.
3. **Extensions:** `mbstring`, `xml`, `ctype`, `iconv`, `intl`, `pdo`, `pdo_sqlite`, `sqlite3`, `bcmath`, `curl`, `gd`, `zip`, `fileinfo`.
4. **Composer Cache & Strict Validation:** `composer validate --strict` and lockfile caching.
5. **Node & Vite Build Verification:** `npm ci`, `npm run build`, and assertion of `public/build/manifest.json`.
6. **Isolated Test Harness:** In-memory SQLite (`:memory:`), `APP_ENV=testing`, dummy app key without real secrets.
7. **Translation Parity & Test Execution:** `LocalizationTest` and full suite `php artisan test`.

---

## 5. Automated Blockers Status

- **Automated Blockers:** `0` (Zero). All automated unit, feature, and translation tests are green.

---

## 6. Human Verification Pending List (Boundaries Check)

As mandated by project policy, the following items cannot be marked as PASS by automated tooling and remain explicitly pending human verification:

1. `PENDING — Human Verification`: Physical 58mm & 80mm thermal receipt printer output (alignment, Myanmar font rendering).
2. `PENDING — Human Verification`: Physical USB and Bluetooth barcode scanner hardware testing.
3. `PENDING — Human Verification`: Cash drawer RJ11/RJ12 electronic kick pulse on checkout.
4. `PENDING — Human Verification`: Clean Windows 10/11 standalone PC fresh installation.
5. `PENDING — Human Verification`: Database backup restoration on a clean secondary PC.
6. `PENDING — Human Verification`: Physical sudden power cut / crash recovery behavior on active cashier shifts.
7. `PENDING — Human Verification`: Seven-day real shop pilot operating with 0% external internet connectivity.
8. `PENDING — Human Verification`: Store Owner final commercial acceptance sign-off.
