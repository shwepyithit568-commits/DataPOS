# DataPOS — Release Snapshot, Git Bundle, and Backup Guide

> **Document Version:** 1.0.0  
> **Status:** Active & Canonical  
> **Applicability:** DataPOS Desktop / Standalone Windows Deployment  
> **Target Audience:** Maintainers, DevOps, Release Engineers, POS Administrators  

---

## 1. Executive Summary & Four Distinct Artifact Taxonomy

To prevent data corruption, customer credential exposure, and catastrophic deployment confusion, DataPOS maintains **four strictly distinct archive categories**. Under no circumstances may these artifacts be merged, misnamed, or distributed interchangeably.

| Artifact Category | Canonical Filename Pattern | Content Scope | Target Audience | Customer Distribution Allowed? |
| :--- | :--- | :--- | :--- | :--- |
| **1. Source Release ZIP** | `datapos-source-v{VER}-{SHA}-{DATE}.zip` | Tracked source code, `composer.lock`, `package-lock.json`, pre-compiled `public/build/`. **ZERO** `.env`, **ZERO** customer data, **ZERO** `vendor/`. | Developers, CI/CD, Package maintainers | **No** (Developer distribution only) |
| **2. Git Bundle** | `datapos-git-bundle-v{VER}-{SHA}-{DATE}.bundle` | Complete Git repository (all commits, branches, tags). Binary Git packfile. | DevOps, Core Engineering Disaster Recovery | **STRICTLY PROHIBITED** (Internal IP & commit history) |
| **3. Customer Data Backup ZIP** | `backup_{STORE}_{TIMESTAMP}.zip` | Store SQLite/MySQL database dump, uploaded receipts, inventory media, audit logs. **ZERO** PHP application code. | Store Owner, Cashier Supervisor, Support Engineer | **Store-specific Only** (Restricted to store owner) |
| **4. Windows Installer EXE** | `DataPOS-Setup-v{VER}.exe` | Self-contained executable: Embedded PHP runtime, Nginx/Caddy, compiled Laravel application, bootstrap DB seed. | End-user Cashiers, Store Technicians | **YES** (Standard production delivery format) |

---

## 2. Artifact Specification & Policies

### Artifact 1: Source Release Snapshot ZIP
- **Builder Script:** `scripts/release/build-source-snapshot.ps1`
- **Output Directory:** `dist/release-snapshots/` (Git-ignored)
- **Security Invariants:**
  - **Fail-on-Dirty:** Refuses execution if uncommitted changes exist in the Git working tree (overridable only via `-AllowDirty` for automated test suites).
  - **Secret Preflight:** Scans tracked Git files; immediately aborts if `.env`, private keys (`.pem`, `.ppk`, `.key`), or active SQLite databases are detected.
  - **Exclusion Guarantees:** Strips `storage/logs/*`, `storage/framework/*`, `vendor/`, `node_modules/`, `storage/database/*.sqlite*`, and `.env`.
  - **Asset Inclusion:** Automatically packages pre-compiled Vite production assets from `public/build/` so target machines do not require Node.js or npm.
  - **Cryptographic Manifest:** Produces companion `.sha256` checksum and `.manifest.json` recording Git commit SHA, branch, file count, and UTC timestamp.

### Artifact 2: Git Disaster Recovery Bundle
- **Builder Script:** `scripts/release/build-git-bundle.ps1`
- **Output Directory:** `dist/release-snapshots/` (Git-ignored)
- **Security Invariants:**
  - Contains complete commit history, tags, and branches created via `git bundle create --all`.
  - Automatically verified via `git bundle verify` before completion.
  - Generates companion SHA-256 hash.
  - **Strict Policy:** Must NEVER be published to public S3 buckets, FTP, or given to clients. Intended for air-gapped engineering recovery.

### Artifact 3: Customer Data Backup ZIP
- **Generator Service:** `App\Services\DatabaseBackupService` / `php artisan datapos:backup`
- **Output Directory:** `storage/app/backups/` or external USB drive (`D:\DataPOS-Backups\`)
- **Content:**
  - Database SQL dump or SQLite file snapshot.
  - Storage media (`storage/app/public/products`, `invoices`).
  - System metadata and store identifier.
- **Strict Policy:** Contains real business transactions, customer PII, customer debts, and ledger data. Never package into source code repositories or installer bundles.

### Artifact 4: Windows Offline Installer Executable (EXE)
- **Builder:** Inno Setup / Inno Script (Planned in Prompt 7).
- **Target File:** `dist/installers/DataPOS-Setup-v{VER}.exe`
- **Content:** The full offline execution stack (packaged PHP, database engine, sanitized migrations, frontend UI, desktop shortcuts).

---

## 3. Step-by-Step Restoration Procedures

### A. Restoring from a Source Release Snapshot ZIP

When provisioning a new developer workstation or non-installer deployment from a source snapshot:

```powershell
# 1. Verify cryptographic SHA-256 hash
$computed = (Get-FileHash -Path "datapos-source-v2026.1.0-xxxxxxx-20260909.zip" -Algorithm SHA256).Hash.ToLower()
$expected = (Get-Content "datapos-source-v2026.1.0-xxxxxxx-20260909.sha256").Split(" ")[0].Trim().ToLower()
if ($computed -ne $expected) {
    throw "Checksum mismatch! Archive may be corrupted or tampered with."
}
Write-Host "Integrity confirmed: $computed" -ForegroundColor Green

# 2. Extract the source archive
Expand-Archive -Path "datapos-source-v2026.1.0-xxxxxxx-20260909.zip" -DestinationPath "C:\DataPOS"
cd "C:\DataPOS\datapos-source-v2026.1.0-xxxxxxx-20260909"

# 3. Install production PHP dependencies (deterministic from composer.lock)
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Initialize environment configuration
Copy-Item ".env.example" ".env"
php artisan key:generate --force

# 5. Configure SQLite database
# Ensure storage/database directory exists
New-Item -ItemType Directory -Force -Path "storage/database" | Out-Null
New-Item -ItemType File -Force -Path "storage/database/datapos.sqlite" | Out-Null

# 6. Run database migrations & seed initial roles
php artisan migrate --force
php artisan db:seed --force

# 7. Optimize caches for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### B. Restoring from a Git Bundle

When restoring complete repository history onto a fresh machine without internet access to GitHub:

```bash
# 1. Verify bundle integrity
git bundle verify datapos-git-bundle-v2026.1.0-xxxxxxx-20260909.bundle

# 2. Clone directly from the bundle file
git clone datapos-git-bundle-v2026.1.0-xxxxxxx-20260909.bundle DataPOS-restored

# 3. Enter directory and checkout main branch
cd DataPOS-restored
git checkout main

# 4. Verify Git commit log matches source commit
git log -n 5 --oneline
```

---

### C. Restoring Customer Data from a Customer Data Backup ZIP

When a POS machine experiences hardware failure and customer data needs restoring onto a fresh DataPOS instance:

```powershell
# 1. Ensure DataPOS service is temporarily stopped
# (To prevent database locking during SQLite replacement)

# 2. Invoke DataPOS backup restoration utility
php artisan datapos:restore-backup "D:\DataPOS-Backups\backup_Store01_2026-09-08_120000.zip" --force

# 3. Run database migrations to reconcile any schema differences
php artisan migrate --force

# 4. Clear application cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 5. Restart DataPOS desktop service
```

---

## 4. Verification and Security Checklist

Before approving any source snapshot or release bundle:

- [ ] Working tree status is completely clean (`git status --porcelain` is empty).
- [ ] No active `.env` file exists in the archive.
- [ ] No SQLite database files (`*.sqlite`, `*.sqlite-wal`, `*.sqlite-shm`) exist in the archive.
- [ ] No raw SQL dumps (`manual_*.sql`, `*.dump`) exist in the archive.
- [ ] `composer.lock` and `package-lock.json` are present and unmodified.
- [ ] `public/build/manifest.json` and compiled assets are verified inside the ZIP.
- [ ] SHA-256 checksum matches the exact archive bitstream.
- [ ] Manifest file (`*.manifest.json`) accurately reflects the Git HEAD SHA.
