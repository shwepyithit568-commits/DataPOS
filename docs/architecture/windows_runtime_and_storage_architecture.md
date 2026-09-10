# DataPOS — Windows Runtime and Storage Architecture (Prompt 3)

**Architecture Version:** 1.0 (Canonical)  
**Date:** 2026-09-09  
**Audited Git Commit:** `c476db95274d807a07285c14e27fd9077c339673` (Prompt 2 HEAD)  
**Status:** Implementation-Ready Specification  
**Auditor / Agent:** Antigravity (Prompt 3 — Runtime, SQLite Database & Writable Storage Canonicalization)

---

## 1. Executive Summary & Problem Statement

Prior to this audit, DataPOS documentation and configuration contained path and runtime discrepancies:
1. **Historical Database Path Mismatch:** Root `README.md` referenced `database/database.sqlite` (Laravel framework default), while archived `archive/superseded-plans/windows_offline_installer_plan_v1.md` referenced `storage/database/datapos.sqlite`.
2. **Read-Only vs. Writable Data Collision:** In standard Windows deployments, putting writable files in `C:\Program Files\DataPOS` causes `Access Denied` (`EACCES`) errors for standard non-administrator users, blocking SQLite writes, session storage, and log appending.
3. **SQLite Concurrency & Locking:** Default SQLite configuration without Write-Ahead Logging (WAL) and busy timeout leads to intermittent `Database is locked` crashes when a cashier executes a POS checkout while background tasks or reports are running.
4. **Launcher Lifecycle Ambiguity:** The built-in server launcher lacked an evidence-based specification for loopback binding, duplicate process prevention, and health checks.

This document establishes the **Canonical Windows Runtime & Storage Architecture** to resolve all discrepancies before any installer script or executable is built.

---

## 2. Canonical Path Matrix

To guarantee clean upgrades, simple backups, and zero permission issues, program binaries and writable application state are strictly separated:

| Component | Canonical Location (Relative) | Recommended Windows Path (`C:\DataPOS`) | Access Type | Backup Policy |
| :--- | :--- | :--- | :--- | :--- |
| **Application Binaries** | `app/`, `bootstrap/`, `config/`, `routes/`, `resources/` | `C:\DataPOS\app`, etc. | Read-Only | Included in Source / Installer |
| **PHP Runtime** | `php/` | `C:\DataPOS\php` | Read-Only | Included in Installer |
| **Vendor Packages** | `vendor/` | `C:\DataPOS\vendor` | Read-Only | Pre-bundled in Installer |
| **Compiled Web Assets** | `public/build/` | `C:\DataPOS\public\build` | Read-Only | Included in Source / Installer |
| **Front Controller Router** | `server.php` / `public/index.php` | `C:\DataPOS\server.php` | Read-Only | Included in Source / Installer |
| **Environment Configuration** | `.env` | `C:\DataPOS\.env` | Writable | Dynamic per machine (Excluded from Git) |
| **Primary SQLite Database** | `storage/database/datapos.sqlite` | `C:\DataPOS\storage\database\datapos.sqlite` | **Writable (Active)** | **Backed up in Customer Data ZIP** |
| **Media & Uploads** | `storage/app/public/` | `C:\DataPOS\storage\app\public` | **Writable** | **Backed up in Customer Data ZIP** |
| **System Backups** | `storage/app/private/backups/` | `C:\DataPOS\storage\app\private\backups` | **Writable** | Retains last 14 snapshots |
| **Portable Store Backups** | `storage/app/backups/` | `C:\DataPOS\storage\app\backups` | **Writable** | Verified portable store packages |
| **Framework Cache/Sessions**| `storage/framework/{cache,sessions,views}` | `C:\DataPOS\storage\framework` | **Writable (Temp)** | Excluded from Backups |
| **Application Logs** | `storage/logs/` | `C:\DataPOS\storage\logs` | **Writable (Append)**| Excluded from Backups |
| **Process PID File** | `storage/framework/server.pid` | `C:\DataPOS\storage\framework\server.pid`| Writable | Ephemeral runtime state |

---

## 3. Canonical SQLite Database Path Resolution & Backward Compatibility

### 3.1 Three-Tier Path Resolution
`config/database.php` was updated to implement automatic fallback and path normalization:

```php
'sqlite' => [
    'driver' => 'sqlite',
    'url' => env('DB_URL'),
    'database' => env('DB_DATABASE')
        ? (in_array(env('DB_DATABASE'), [':memory:', 'sqlite::memory:']) || preg_match('/^([A-Za-z]:[\\\\\/]|\/)/', env('DB_DATABASE'))
            ? env('DB_DATABASE')
            : base_path(env('DB_DATABASE')))
        : (file_exists(storage_path('database/datapos.sqlite'))
            ? storage_path('database/datapos.sqlite')
            : (file_exists(database_path('database.sqlite'))
                ? database_path('database.sqlite')
                : storage_path('database/datapos.sqlite'))),
    'prefix' => '',
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
    'busy_timeout' => env('DB_BUSY_TIMEOUT', 5000),
    'journal_mode' => env('DB_JOURNAL_MODE', 'WAL'),
    'synchronous' => env('DB_SYNCHRONOUS', 'NORMAL'),
    'transaction_mode' => 'DEFERRED',
],
```

### 3.2 Non-Destructive Migration & Fallback Rules
1. **Clean Fresh Install:** Creates `storage/database/datapos.sqlite` and runs migrations.
2. **Existing Legacy Installation (`database/database.sqlite`):**
   - If `storage/database/datapos.sqlite` does NOT exist but legacy `database/database.sqlite` DOES exist, the application seamlessly connects to the legacy database. **Zero data loss.**
   - When the Windows Installer upgrades an existing installation, it copies `database/database.sqlite` to `storage/database/datapos.sqlite`, preserving the original file as `database/database.sqlite.bak`.
3. **Explicit `.env` Configuration:** If an administrator or developer specifies `DB_DATABASE=storage/database/datapos.sqlite` or any custom path, it is automatically resolved relative to `base_path()`, guaranteeing consistency across CLI, web server, and background workers.
4. **Under no circumstances is an existing SQLite file overwritten during setup or startup.**

---

## 4. Windows Permissions & Installation Directory Selection

| Directory Option | Privileges Required | Windows ACL / Sandboxing | Multi-user Support | Evaluation for DataPOS v1 |
| :--- | :--- | :--- | :--- | :--- |
| **`C:\Program Files\DataPOS`** | Administrator (UAC elevation) | Read-only for standard users. Writing to SQLite or logs throws `EACCES` unless manual ACL permissions are modified via `icacls`. | System-wide | **Not Recommended for v1:** Causes permission failures on non-admin cashier accounts. |
| **`%PROGRAMDATA%\DataPOS`** (`C:\ProgramData`) | Admin to install, User to write | Shared across all machine users. Separates program files (`Program Files`) from data files (`ProgramData`). | System-wide | **Overkill for v1:** Adds path complexity and multi-directory management. |
| **`%LOCALAPPDATA%\DataPOS`** | None (User-level) | Full write access for the current logged-in user. No UAC prompts. | Single user only | **Good for Personal Apps:** However, if two cashiers log into separate Windows accounts, they cannot share the same database. |
| **`C:\DataPOS` (Standalone Root)** | Admin once during install, User thereafter | **Full Control granted to `Users` group.** Self-contained, portable, immune to UAC elevation blocks. | Machine-wide shared | **RECOMMENDED FOR V1 (Commercial SME):** Follows proven standards used by Myanmar tech shops, accounting software, and XAMPP. Allows shop owners to easily copy the entire `C:\DataPOS` folder to a USB drive for disaster recovery. |

---

## 5. SQLite Concurrency, Durability and Shutdown Recovery

To support high-speed POS checkouts without database lock crashes:

1. **Write-Ahead Logging (`journal_mode=WAL`):**
   - In WAL mode, SQLite writes changes to `datapos.sqlite-wal`.
   - **Readers do not block writers, and writers do not block readers.** Cashiers can record sales while managerial dashboards or inventory valuation queries are executing.
2. **Busy Timeout (`busy_timeout=5000`):**
   - If a write transaction is in progress, any concurrent write attempt waits up to 5,000 milliseconds (5 seconds) instead of immediately failing with `SQLSTATE[HY000]: General error: 5 database is locked`.
3. **Durability & Sudden Power-Loss Recovery (`synchronous=NORMAL`):**
   - Under WAL mode, `NORMAL` synchronous ensures ACID durability against application and operating system crashes.
   - On Windows, if power is abruptly lost, SQLite automatically detects the `-wal` file on the next connection and rolls forward/recovers uncommitted transactions with zero database corruption.
4. **Foreign Key Integrity (`foreign_key_constraints=true`):**
   - Enforced by default on every SQLite connection.

---

## 6. PHP Built-in Server Launcher Technical Specification

The Windows desktop launcher (`DataPOS.exe` or launcher script) must adhere to the following lifecycle state machine:

```
[User Launches DataPOS]
       │
       ▼
[Check Stale PID in storage/framework/server.pid]
       │
       ├──► (PID Alive & /up returns 200) ──────────► [Bring Browser to Front / Open URL] ──► [EXIT LAUNCHER]
       │
       └──► (PID Dead or /up unreachable)
                 │
                 ▼
          [Clean Stale PID File]
                 │
                 ▼
          [Check Port 8501 Availability]
                 │
                 ├──► Port 8501 Free ────────► Use 8501
                 │
                 └──► Port 8501 Busy ────────► Fallback to 8502 (or next available)
                             │
                             ▼
             [Spawn Background PHP Built-in Server]
             Command: php.exe -S 127.0.0.1:<PORT> server.php
             Binding: Loopback ONLY (127.0.0.1 — Never 0.0.0.0)
             Cwd:     C:\DataPOS
             Output:  Redirect to storage/logs/php-server.log
                             │
                             ▼
             [Write New PID to storage/framework/server.pid]
                             │
                             ▼
             [Poll Health Check: http://127.0.0.1:<PORT>/up]
             Max Retries: 30 (3 seconds total, 100ms interval)
                             │
                             ├──► (HTTP 200 OK) ────► [Launch Default Browser to http://127.0.0.1:<PORT>]
                             │
                             └──► (Timeout) ────────► [Display Diagnostic Error Dialog & Log Link]
```

### Key Launcher Requirements:
- **Loopback-Only Binding:** Must bind exclusively to `127.0.0.1`. Never bind to `0.0.0.0` in single-user offline mode to prevent unauthenticated network exposure on local Wi-Fi.
- **Multiple Launcher Clicks:** If a user double-clicks the desktop shortcut while DataPOS is already running, the launcher detects the healthy server via `/up` and simply opens/focuses the browser, avoiding duplicate server instances.
- **Front-Controller Router (`server.php`):** The repository root already contains a specialized `server.php` router script that serves versioned build assets (`public/build/*`) with far-future immutable cache headers, ensuring instant offline page reloads.

---

## 7. Automated Test Verification

All requirements specified in Prompt 3 have been implemented and verified via automated tests:

- **Test Suite:** [`tests/Feature/Runtime/WindowsStorageCanonicalizationTest.php`](../../tests/Feature/Runtime/WindowsStorageCanonicalizationTest.php)
- **Verified Tests:**
  1. `test_sqlite_configuration_resolves_canonical_path_or_custom_env` — **PASS**
  2. `test_wal_and_foreign_key_configuration_settings` — **PASS**
  3. `test_existing_database_is_preserved_and_never_overwritten` — **PASS**
  4. `test_fresh_database_directory_can_be_created_safely` — **PASS**
  5. `test_backup_packages_include_database_and_media` — **PASS**
  6. `test_restore_extracts_media_to_canonical_public_storage` — **PASS**
  7. `test_writable_storage_directories_are_valid` — **PASS**
  8. `test_missing_or_invalid_directory_detected_gracefully` — **PASS**
- **Test Summary:** **8 passed (39 assertions), Duration: 1.00s.**
