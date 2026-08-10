# DataPOS — Production Backup Strategy

## 1. Database Backup (MySQL)

Run this command daily via Windows Task Scheduler or a cron-equivalent:

```bash
# Full backup with compression
mysqldump -u DB_USER -p DB_NAME | gzip > "<backup-root>/db/datapos_$(date +%Y%m%d_%H%M%S).sql.gz"
```

**Windows PowerShell equivalent:**
```powershell
$date = Get-Date -Format "yyyyMMdd_HHmmss"
$dest = "<backup-root>\db\datapos_$date.sql.gz"
mysqldump -u root -p datapos_db | gzip | Out-File $dest -Encoding Byte
```

Retention policy: Keep the last **30 daily backups**.

Test restore monthly:
```bash
gunzip < datapos_20260101_000000.sql.gz | mysql -u root -p datapos_db_test
```

---

## 2. Storage Backup (Uploaded Files)

User-uploaded files live in `storage/app/public/` (product images, banners, logos).
This directory is symlinked to `public/storage` via `php artisan storage:link`.

Backup the actual storage folder, **not** the symlink:

```powershell
# Robocopy mirror (Windows)
robocopy "<project-root>\storage\app\public" `
         "<backup-root>\storage" `
         /MIR /Z /LOG:"<backup-root>\storage_backup.log"
```

Frequency: **Weekly** (images change less often than DB records).

---

## 3. Environment Configuration Backup

The `.env` file contains database credentials, app key, and secret configuration. It must **never** be committed to git.

Backup procedure:
1. Manually copy `.env` to a **password-protected** USB drive or encrypted network share.
2. Store at minimum: `APP_KEY`, `DB_PASSWORD`, `APP_URL`.
3. After any `.env` change, re-run the backup.

**Never store `.env` in cloud storage without encryption.**

---

## 4. Recommended Backup Schedule

| Item | Frequency | Retention |
|------|-----------|-----------|
| MySQL dump | Daily (2 AM) | 30 days |
| Storage files | Weekly | 4 weeks |
| `.env` backup | On change | Last 3 versions |

---

## 5. Disaster Recovery Checklist

```bash
# 1. Restore database
gunzip < backup.sql.gz | mysql -u root -p datapos_db

# 2. Restore storage files
robocopy <backup-root>\storage <project-root>\storage\app\public /MIR

# 3. Restore .env
copy .env.backup .env

# 4. Re-run Laravel setup
php artisan key:generate        # Only if APP_KEY was lost
php artisan migrate --force
php artisan storage:link
php artisan config:clear
php artisan cache:clear
npm run build
```
