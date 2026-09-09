# DataPOS — Windows Offline Installer Plan v2

> **Document Reference:** `docs/windows_offline_installer_plan_v2.md`  
> **Predecessor Reference:** [windows_offline_installer_plan_v1.md](file:///d:/xmapp/htdocs/DataPOS/docs/windows_offline_installer_plan_v1.md) (Preserved as architectural history)  
> **Parent Document:** [myanmar_business_commercial_readiness_plan_v1.md](file:///d:/xmapp/htdocs/DataPOS/docs/myanmar_business_commercial_readiness_plan_v1.md)  
> **Completion Report:** [phase_f_completion_report.md](file:///d:/xmapp/htdocs/DataPOS/docs/phase_f_completion_report.md)  
> **Created Date:** 2026-09-09  
> **Status:** 🟡 Awaiting Project Owner Approval Before Implementation  
> **Engineering Invariant:** This document is an architectural and procedural specification. **NO installer scripts, launcher executables, or binary packages may be built before explicit Project Owner sign-off.**

---

## 1. Decision Log (v1-to-v2 Correction Table)

| # | Topic | v1 Limitation / Inconsistency | v2 Resolution & Evidence Base |
|:---|:---|:---|:---|
| **D-1** | **Install Path Conflict** | Section 4.1 cited `C:\DataPOS`, but Section 14.3 specified `{autopf}\DataPOS` (`C:\Program Files\DataPOS`). | **Resolved:** `{autopf}` requires Administrator privileges for every file write, breaking SQLite WAL and storage. Standardized on **Model A (`C:\DataPOS`)** for single-user offline pilot, with **Model B (Split Program Files / AppData)** roadmap for GA. |
| **D-2** | **Elevation & Privilege Model** | Vaguely stated "no admin required" while attempting Program Files & machine scheduler writes. | **Clarified:** Admin elevation required **once** during Inno Setup (directory creation, ACLs, Firewall). Daily POS operation runs **100% non-elevated (standard user)**. |
| **D-3** | **PHP Runtime Specification** | Vague "PHP 8.2.x NTS x64". | **Pinned:** PHP 8.2.12 (or 8.2.27 NTS Win32-vs16-x64), official SHA-256 bitstream validation, PHP License v3.01 audit and bundled notice. |
| **D-4** | **PHP Extensions Inventory** | General list without validation. | **Inventory Verified:** Sourced from `composer check-platform-reqs` (`bcmath`, `curl`, `fileinfo`, `gd`, `iconv`, `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`, `zip`, `zlib`). `xdebug` and unused DB drivers stripped. |
| **D-5** | **Server & Launcher Architecture** | Treated built-in server as permanent production solution. | **Scoped:** PHP built-in server designated for **Single-User Offline Pilot only**. Process supervision, port collision fallback (`8501` → `8502`), single-instance mutex, and log rotation specified. |
| **D-6** | **Router Script (`server.php`)** | Ambiguous deliverable. | **Verified:** Root `server.php` confirmed existing in repository with immutable static asset caching for `public/build/`. |
| **D-7** | **Database Seeding** | Used generic `php artisan migrate --force --seed` (risking demo leaks). | **Strict Policy:** Replaced with `ProductionSeeder` (categories, order guides, blog only). Zero demo stores, zero fake transactions, zero default credentials. |
| **D-8** | **First-Run Store & Owner Creation** | Unspecified account provisioning. | **Defined:** Clean First-Run Wizard prompts Store Owner to configure store metadata, currency, and create custom PIN/password directly in UI. |
| **D-9** | **Auto-Backup & Scheduler** | Hardcoded to 02:00 AM regardless of PC power state. | **Enhanced:** Configurable backup time (default 08:00 PM at shop close). Added `<StartWhenAvailable>true</StartWhenAvailable>` so missed backups run on next boot. |
| **D-10** | **Artifact Taxonomy** | Conflated source archives, customer backups, and installer. | **Isolated:** Reconciled with `docs/release_snapshot_and_backup_guide.md` across all 4 distinct artifact formats. |
| **D-11** | **Binary Compression (UPX)** | Promoted UPX compression without antivirus risk analysis. | **Policy Set:** UPX prohibited due to high false-positive heuristic flags in Windows Defender. Native Inno Setup LZMA2/Ultra solid compression selected. |
| **D-12** | **Code Signing Reality** | Claimed self-signed would suffice for pilot without explaining friction. | **Honest Disclosure:** Self-signed certificates trigger Windows SmartScreen "Unknown Publisher". Documented technician bypass for pilot; Commercial GA requires Sectigo/DigiCert OV. |
| **D-13** | **Compatibility Claims** | Claimed Windows 10/11 "Fully tested". | **Reclassified:** Host testing is automated; Clean VM and Physical Retail Hardware categorized honestly as `PLANNED` and `PENDING`. |

---

## 2. Runtime and Storage Architecture

### 2.1 Canonical Path Layout (Prompt 3 Alignment)

Following the canonicalization established in Prompt 3 and tested in `tests/Feature/Runtime/WindowsStorageCanonicalizationTest.php`, DataPOS runtime storage is structured as follows:

```
C:\DataPOS\                               ← Application Root Directory
├── bin\                                  ← Runtime Executables (Read/Execute)
│   ├── php\                              ← Pinned PHP 8.2.x NTS x64 Runtime
│   │   ├── php.exe
│   │   ├── php.ini                       ← Production hardened config
│   │   └── ext\                          ← Verified extensions only
│   └── DataPOS.exe                       ← Native Process Supervisor & Tray App
├── app\                                  ← Production Laravel Codebase
│   ├── app\
│   ├── config\
│   ├── database\
│   ├── public\
│   │   ├── build\                        ← Pre-compiled Vite production assets
│   │   ├── index.php
│   │   └── storage -> C:\DataPOS\storage\app\public (Junction)
│   ├── resources\
│   ├── routes\
│   ├── server.php                        ← Caching dev router
│   ├── vendor\                           ← Composer production-only packages
│   ├── .env                              ← Production environment (auto-generated)
│   └── artisan
├── storage\                              ← Canonical Writable Runtime Storage
│   ├── app\
│   │   ├── backups\                      ← Customer DB & Media Backups
│   │   └── public\                       ← Uploaded Store Logos, QR Slips
│   ├── database\
│   │   └── datapos.sqlite                ← Primary SQLite Database (WAL Mode)
│   ├── framework\
│   │   ├── cache\
│   │   ├── sessions\
│   │   └── views\
│   └── logs\
│       ├── laravel.log                   ← Rotated daily application logs
│       └── launcher.log                  ← Process supervisor logs
└── unins000.exe                          ← Inno Setup Uninstaller
```

### 2.2 Permissions and ACL Specification

- **Installation Phase:** Requires UAC Administrator elevation to execute `C:\DataPOS` directory creation.
- **Access Control List (ACL) Command:**
  ```cmd
  icacls "C:\DataPOS" /grant:r "Users":(OI)(CI)M /T
  icacls "C:\DataPOS\storage" /grant:r "Users":(OI)(CI)F /T
  icacls "C:\DataPOS\app\.env" /grant:r "SYSTEM":(F) /grant:r "Administrators":(F) /grant:r "Users":(M)
  ```
- **Runtime Phase:** Any local Windows standard user account can start, operate, and back up DataPOS without UAC elevation or administrative privileges.

---

## 3. Pinned PHP Runtime & Platform Inventory

### 3.1 Pinned Runtime Specifications

- **Version:** PHP 8.2.12 NTS (Non-Thread Safe) x64  
  *(Alternative tested: PHP 8.2.27 NTS Win32-vs16-x64)*
- **Architecture:** x64 (AMD64 / Intel 64-bit)
- **Compiler:** Visual C++ 2019 / VS16
- **Download Source:** `https://windows.php.net/downloads/releases/archives/`
- **Integrity Check:** SHA-256 checksum verified against official PHP release hashes before unpacking into staging.
- **License Compliance:** PHP License v3.01. The installer includes `bin/php/license.txt`.

### 3.2 Required Extensions Inventory

Verified via `composer check-platform-reqs` and active codebase requirements:

| Extension | Requirement Origin | Purpose in DataPOS |
|:---|:---|:---|
| `ext-pdo_sqlite` | Laravel Core | SQLite database driver |
| `ext-sqlite3` | SQLite Native | Direct SQLite engine & WAL checkpointing |
| `ext-bcmath` | Domain Service | Precision MMK currency & ledger computations |
| `ext-mbstring` | Laravel / UI | Tri-lingual UTF-8 string handling (Myanmar & Chinese) |
| `ext-openssl` | Laravel Security | App encryption, hash message authentication |
| `ext-fileinfo` | Upload Handlers | MIME type validation for receipts, logos |
| `ext-gd` | Image Processing | Product thumbnail generation & barcode rendering |
| `ext-zip` | Backup Service | Customer backup archive generation & restore |
| `ext-curl` | HTTP Client | Hardware receipt printer network probes / optional sync |
| `ext-iconv` | Character Conversion | ESC/POS thermal receipt raw byte streams |

### 3.3 Production `php.ini` Hardening Directives

```ini
[PHP]
engine = On
short_open_tag = Off
precision = 14
output_buffering = 4096
zlib.output_compression = Off
implicit_flush = Off
max_execution_time = 60
max_input_time = 60
memory_limit = 256M
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
display_startup_errors = Off
log_errors = On
log_errors_max_len = 1024
ignore_repeated_errors = On
ignore_repeated_source = On
report_memleaks = On
html_errors = Off
error_log = "C:\DataPOS\storage\logs\php_errors.log"
variables_order = "GPCS"
request_order = "GP"
register_argc_argv = Off
auto_globals_jit = On
post_max_size = 20M
default_mimetype = "text/html"
default_charset = "UTF-8"
upload_max_filesize = 20M
max_file_uploads = 10
allow_url_fopen = On
allow_url_include = Off
default_socket_timeout = 60

extension_dir = "ext"
extension = bcmath
extension = curl
extension = fileinfo
extension = gd
extension = mbstring
extension = openssl
extension = pdo_sqlite
extension = sqlite3
extension = zip

[Date]
date.timezone = "Asia/Yangon"

[opcache]
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 64
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.validate_timestamps = 0
```

---

## 4. First-Run Provisioning & Production-Safe Seeding

### 4.1 Automated First-Run Sequence (Installer Finalization)

During installation (executed silently via Inno Setup `[Run]`):

```cmd
:: 1. Initialize environment
copy "C:\DataPOS\app\.env.example" "C:\DataPOS\app\.env"
"C:\DataPOS\bin\php\php.exe" "C:\DataPOS\app\artisan" key:generate --force

:: 2. Initialize canonical storage directories
mkdir "C:\DataPOS\storage\database" 2>nul
mkdir "C:\DataPOS\storage\app\public" 2>nul
mkdir "C:\DataPOS\storage\app\backups" 2>nul
mkdir "C:\DataPOS\storage\framework\cache" 2>nul
mkdir "C:\DataPOS\storage\framework\sessions" 2>nul
mkdir "C:\DataPOS\storage\framework\views" 2>nul
mkdir "C:\DataPOS\storage\logs" 2>nul

:: 3. Create empty SQLite database file
type nul > "C:\DataPOS\storage\database\datapos.sqlite"

:: 4. Run database migrations (Schema only)
"C:\DataPOS\bin\php\php.exe" "C:\DataPOS\app\artisan" migrate --force

:: 5. Run production-safe seeder (NO DEMO DATA)
"C:\DataPOS\bin\php\php.exe" "C:\DataPOS\app\artisan" db:seed --class=Database\Seeders\ProductionSeeder --force

:: 6. Create storage symlink junction
"C:\DataPOS\bin\php\php.exe" "C:\DataPOS\app\artisan" storage:link

:: 7. Optimize production caches
"C:\DataPOS\bin\php\php.exe" "C:\DataPOS\app\artisan" config:cache
"C:\DataPOS\bin\php\php.exe" "C:\DataPOS\app\artisan" route:cache
"C:\DataPOS\bin\php\php.exe" "C:\DataPOS\app\artisan" view:cache
```

### 4.2 In-App Setup Wizard (UI Handshake)

When the cashier or store manager first launches DataPOS, the system detects zero registered stores and redirects to `/setup`:

1. **Step 1: Store Profile:** Store Name (Myanmar/English), Contact Phone, Business Address.
2. **Step 2: Currency & Formatting:** Default MMK, symbol display format, receipt footer text.
3. **Step 3: Document Numbering:** Starting invoice sequence, receipt prefix.
4. **Step 4: Hardware Setup:** Thermal receipt printer (58mm/80mm), test print pulse.
5. **Step 5: Store Owner Account:** Name, login phone number, 6-digit cashier quick PIN, and secure admin password.

---

## 5. Process Supervisor & Launcher Lifecycle

### 5.1 Launcher Responsibilities (`DataPOS.exe`)

The launcher is a lightweight native Windows executable (compiled in Go or C#/.NET 8 AOT) serving as the process supervisor:

```
[Launcher Start]
       │
       ▼
[Check Named Mutex] ──(Already Running)──► [Focus Existing Browser Window] ──► [Exit]
       │
       ▼ (First Instance)
[Check Available Port: 8501]
       │
       ├─► (Port Busy) ──► [Probe 8502, 8503...] ──► [Select Available Port]
       │
       ▼
[Spawn PHP Process (Hidden)]
  Cmd: php.exe -S 127.0.0.1:{PORT} server.php
  Env: PHP_CLI_SERVER_WORKERS=4
       │
       ▼
[Poll Health Endpoint: http://127.0.0.1:{PORT}/up] (Timeout 5s)
       │
       ├─► (Healthy 200 OK)
       │        │
       │        ├─► [Launch Default Browser to http://127.0.0.1:{PORT}]
       │        └─► [Show System Tray Icon]
       │
       ▼
[Supervise Process Loop]
       │
       ├─► (Crash Detected) ──► [Log Event] ──► [Restart PHP Process (Max 3 attempts)]
       │
       └─► (User Selects "Exit DataPOS" from Tray)
                │
                ▼
           [Graceful Shutdown: Send SIGTERM]
           [Wait 2s for SQLite WAL Flush]
           [Terminate PHP Process]
           [Remove Tray Icon & Exit]
```

### 5.2 Fault Tolerance & Port Collision Handling

- **Primary Port:** `8501` (avoids common web server ports 80, 443, 8080, 8000).
- **Collision Strategy:** If `8501` is bound by another service, the launcher attempts `8502`, `8503`, up to `8505`. The active port is saved to `C:\DataPOS\storage\framework\active_port.txt` so background tasks and desktop shortcuts can resolve the current URL.
- **Multiple Instance Prevention:** Managed via Windows Mutex `Global\DataPOS_SingleInstance_Mutex`.

---

## 6. Backup, Scheduled Tasks, and Upgrade Transactions

### 6.1 Automated Daily Backup Task

- **Command Executed:**
  ```cmd
  "C:\DataPOS\bin\php\php.exe" "C:\DataPOS\app\artisan" backup:database --label=daily_auto
  ```
- **Windows Task Scheduler Specification:**
  - **Task Name:** `DataPOS_DailyBackup`
  - **Trigger:** Configurable daily time (Default: 08:00 PM / shop closing time).
  - **Resilience Setting:** `<StartWhenAvailable>true</StartWhenAvailable>`. If the POS machine was powered off at 08:00 PM, Windows will automatically execute the backup immediately upon the next user logon.
  - **Retention Policy:** Retains last 30 daily backups in `C:\DataPOS\storage\app\backups\`; oldest archives are automatically pruned by `DatabaseBackupService`.

### 6.2 Atomic Upgrade Transaction & Rollback Architecture

When updating DataPOS via `DataPOS-Update-vX.Y.Z.exe`:

```
[Start Updater]
       │
       ▼
[1. Stop Running Service] ──► Gracefully stop DataPOS.exe & PHP processes
       │
       ▼
[2. Pre-Upgrade Backup]   ──► Create full snapshot:
                              `storage/app/backups/pre_update_{OLD_VER}_{TIMESTAMP}.zip`
       │
       ▼
[3. Stage New Binaries]   ──► Backup existing `app/` to `app_backup/`
                              Unpack new `app/` files (preserves `.env` & `storage/`)
       │
       ▼
[4. Run DB Migrations]    ──► `php.exe artisan migrate --force`
       │
       ├─► [SUCCESS]
       │        │
       │        ├─► `php.exe artisan optimize:clear`
       │        ├─► `php.exe artisan config:cache && route:cache && view:cache`
       │        ├─► Delete `app_backup/`
       │        └─► Restart `DataPOS.exe` ──► Upgrade Complete
       │
       └─► [FAILURE DETECTED]
                │
                ▼
           [5. Automatic Rollback Engine]
           - Restore original `app_backup/` to `app/`
           - Restore pre-update SQLite database file
           - Restore previous configuration
           - Restart DataPOS.exe on previous version
           - Display Error Dialog: "Update failed; system restored to previous version."
```

---

## 7. Security Hardening & Secret Management

| Vector | Security Guarantee & Implementation |
|:---|:---|
| **Network Exposure** | PHP built-in server strictly bound to `127.0.0.1` (loopback only). No external LAN IP binding. |
| **Credential Storage** | `.env` file generated fresh on client PC. `APP_KEY` created via cryptographically secure random bytes. Zero default passwords. |
| **Database Encryption & ACL** | `datapos.sqlite` file ACL restricted to local machine users. Password hashes stored using standard Bcrypt (work factor 12). |
| **Binary Integrity** | SHA-256 manifest published on official release channel. Inno Setup installer checks staging bitstream. |
| **Data Leak Prevention** | Inno Setup `[Files]` excludes `.git/`, `.env`, `tests/`, `node_modules/`, `storage/logs/*`, `storage/app/backups/*`. |
| **Uninstaller Safety** | Uninstaller prompts: *"Do you want to retain your sales data and database backups?"* Default is **YES**, copying `storage/` to `C:\DataPOS_Retained_Data\`. |

---

## 8. Code Signing and Binary Compression Policy

### 8.1 Code Signing Phased Strategy

1. **Phase 1: Pilot Release (3–5 Partner Stores in Myanmar):**
   - **Strategy:** Unsigned or Local Self-Signed Certificate.
   - **User Impact:** Windows SmartScreen will display an *"Unknown Publisher"* warning banner.
   - **Mitigation:** In the pilot phase, DataPOS is deployed directly by trained technicians who follow the documented bypass protocol (*"More info" → "Run anyway"*). No customer self-download during pilot.
2. **Phase 2: Commercial General Availability (GA):**
   - **Strategy:** Standard Organization Validation (OV) Code Signing Certificate (Sectigo, DigiCert, or Certum).
   - **Annual Budget:** ~$200–$350 USD/year.
   - **Outcome:** Validates publisher identity ("DataPOS Myanmar"), establishes Microsoft SmartScreen cloud reputation, and eliminates security warnings.

### 8.2 Compression Policy: UPX Prohibition

- **Finding:** UPX (Ultimate Packer for eXecutables) frequently triggers heuristic false-positive detections in Windows Defender, Malwarebytes, and Avast.
- **Policy:** **UPX binary compression is strictly prohibited.**
- **Alternative:** Solid LZMA2/Ultra64 compression built natively into Inno Setup 6.x.
  - Estimated Staging Uncompressed: ~95–130 MB
  - Estimated Compressed Installer EXE: ~55–75 MB (Easily shared via Telegram/USB in Myanmar).

---

## 9. Testing & Verification Matrix

| Test Layer | Test Scope | Verification Method | Status |
|:---|:---|:---|:---:|
| **1. Runtime Canonicalization** | Path resolution, SQLite WAL, storage permissions | `tests/Feature/Runtime/WindowsStorageCanonicalizationTest.php` | ✅ **Automated (PASSED)** |
| **2. Release Automation** | Source ZIP & Git Bundle exclusion/checksums | `tests/Feature/Release/ReleaseSnapshotAutomationTest.php` | ✅ **Automated (PASSED)** |
| **3. Clean VM Inno Setup** | Clean Windows 10/11 x64 silent install | Inno Setup silent install in clean Hyper-V / VirtualBox | 🔄 **PLANNED (Phase G)** |
| **4. Process Lifecycle** | Mutex single-instance, port collision, supervisor | PowerShell headless launcher test script | 🔄 **PLANNED (Phase G)** |
| **5. Hardware Integration** | 58mm/80mm ESC/POS printer, USB barcode scanner | Physical retail counter testing in Myanmar | 🟡 **PENDING (Human UAT)** |
| **6. Disaster Recovery** | Full backup restore on second clean PC | Backup ZIP upload & extraction test | 🟡 **PENDING (Human UAT)** |
| **7. Power Failure Resilience** | Sudden PC shutdown during POS checkout | Physical power disconnect test (SQLite WAL integrity) | 🟡 **PENDING (Human UAT)** |
| **8. 7-Day Live Pilot** | 50+ real customer transactions daily | 3 retail pilot stores in Myanmar | 🟡 **PENDING (Human UAT)** |

---

## 10. Pilot vs Commercial GA Release Gates

```
[Phase F Engineering Baseline: 1,751 Tests Passed] ✅
                      │
                      ▼
[Prompt 1-6 Governance & Release Automation Complete] ✅
                      │
                      ▼
[Project Owner Approves Plan v2] ⏳
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│              PHASE G — PILOT RELEASE GATE               │
│                                                         │
│  [ ] Clean VM Inno Setup Installation Validation       │
│  [ ] ProductionSeeder Schema Integrity Verified        │
│  [ ] First-Run Setup Wizard Verified in UI             │
│  [ ] Technician-Guided Pilot Deployment (3-5 Stores)   │
│  [ ] 7-Day Offline Pilot Run (Zero Corruption)         │
└─────────────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────┐
│          COMMERCIAL GENERAL AVAILABILITY GATE           │
│                                                         │
│  [ ] OV Code Signing Certificate Installed & Signed     │
│  [ ] Microsoft SmartScreen Reputation Established      │
│  [ ] Multi-Station Reverse Proxy Evaluated (Caddy/LAN) │
│  [ ] Commercial Software License Key Architecture      │
│  [ ] Public Self-Service Download Portal Enabled       │
└─────────────────────────────────────────────────────────┘
```

---

## 11. Open Decisions for Project Owner

Before Phase G code implementation commences, the Project Owner's explicit guidance is requested on the following 6 core architectural parameters:

| # | Question | Recommended Option | Alternative Option | Owner Decision |
|:---:|:---|:---|:---|:---:|
| **Q-1** | **Installation Directory Layout** | **Model A (`C:\DataPOS`)** — Simpler for technician support, non-elevated daily runtime, easy USB backup copy. | **Model B (`{autopf}\DataPOS`)** — Standard Windows layout, but requires split AppData directory mapping. | `[   ]` |
| **Q-2** | **Daily Auto-Backup Scheduled Time** | **08:00 PM** (Typical retail shop closing time in Myanmar). | **02:00 AM** (Night-time, but assumes PC stays turned on overnight). | `[   ]` |
| **Q-3** | **Default Application Port** | **8501** (Matches current active development port, with auto-fallback to 8502). | **8765** (Legacy proposal in v1). | `[   ]` |
| **Q-4** | **Pilot Code Signing Approach** | **Unsigned / Self-Signed for Pilot** (Technician guides installation past SmartScreen; saves immediate cost). | **Purchase OV Certificate Now** (~$250 USD upfront before pilot). | `[   ]` |
| **Q-5** | **Installer Language Experience** | **Bilingual (Myanmar + English)** — Default Myanmar with English subtext. | **Myanmar Only**. | `[   ]` |
| **Q-6** | **Pilot Deployment Scope** | **3 Stores** (Boss's shop + 2 trusted friendly pilot stores). | **5 Stores**. | `[   ]` |

---

## 12. Approval Checkpoint

> **CRITICAL REMINDER:** Per the Strict Engineering Craftsmanship Policy, neither Inno Setup scripts, Go launcher code, nor packaged binaries may be compiled until this checkpoint is signed by the Project Owner.

```
Approval Decision:   [  ] APPROVED TO PROCEED WITH PHASE G
                     [  ] REVISIONS REQUESTED (See notes below)
                     [  ] PAUSED / REJECTED

Project Owner:       _____________________________________________

Date:                _____________________________________________

Signature:           _____________________________________________

Specific Instructions / Decisions for Q-1 through Q-6:

  Q-1 Directory:      _____________________________________________
  Q-2 Backup Time:    _____________________________________________
  Q-3 Web Port:       _____________________________________________
  Q-4 Code Signing:   _____________________________________________
  Q-5 Language:       _____________________________________________
  Q-6 Pilot Stores:   _____________________________________________
```

---

*Prepared by Tech Buddy per `docs/datapos_ai_agents_pre_installer_prompts_v1.md` (Prompt 7).*  
*Predecessor Artifact: `docs/windows_offline_installer_plan_v1.md` (Maintained for version comparison).*
