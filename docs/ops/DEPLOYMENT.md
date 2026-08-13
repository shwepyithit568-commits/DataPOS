# Production Deployment & Security Hardening Guide

This document provides step-by-step instructions for deploying **DataPOS** to a live production server safely and securely.

---

## 1. Server Environment Requirements

- **PHP**: 8.2 or higher
  - Required Extensions: `OpenSSL`, `PDO`, `Mbstring`, `Tokenizer`, `XML`, `Ctype`, `JSON`, `BCMath`, `Fileinfo`, `GD` (for image uploads)
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Web Server**: Nginx or Apache
- **Node.js & npm**: 18+ (for asset compilation)
- **Process Manager**: Supervisor (for queue workers, if background queues are used)

### Performance Recommendations

| Component | Recommended | Notes |
|-----------|------------|-------|
| **Cache Driver** | `file` (config: `CACHE_STORE=file`) or Redis if available | The default `database` driver stores cache in the DB, adding load instead of reducing it. File cache is significantly faster. |
| **Queue Driver** | `database` (default) — adequate for low-volume stores | For high-volume stores, switch to Redis for faster job processing. |
| **Image Optimization** | Manual optimization before upload, or use a service like TinyPNG | The app stores original uploads without compression. For best page load performance, compress images to <200 KB before uploading. |
| **PHP Memory** | `memory_limit = 256M` | CSV imports process rows in memory; sufficient headroom prevents failures on large files. |
| **MySQL / MariaDB** | `innodb_buffer_pool_size = 1G` (or 70% of available RAM) | Critical for index performance on tables with thousands of rows. |

---

## 2. Environment Configuration (`.env`)

Create or update the `.env` file on the production server:

```ini
APP_NAME="DataPOS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Asia/Yangon

# Session Security (encrypted, HTTPS-only cookies)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Database Configuration (use a dedicated database user with least privilege)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=datapos_commerce_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_db_password

# Cache (use 'file' for single-server — much faster than 'database')
CACHE_STORE=file

# Logging (info level in production — avoid debug)
LOG_CHANNEL=stack
LOG_STACK=daily        # rotate logs daily
LOG_DAILY_DAYS=14      # keep 14 days of history
LOG_LEVEL=info
```

> **Important:** Generate a secure `APP_KEY` using `php artisan key:generate`. The application will log a critical warning if `APP_KEY` is missing or empty in production.

---

## 3. Production Deployment Checklist Commands

Run the following commands in sequence during deployment:

```bash
# 1. Install PHP dependencies (no dev dependencies)
composer install --no-dev --optimize-autoloader

# 2. Run Database Migrations (includes performance indexes)
php artisan migrate --force

# 3. Create Storage Link (for public images/banners/uploads)
php artisan storage:link

# 4. Compile Frontend Assets for Production
npm ci
npm run build

# 5. Clear & Cache Application Configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Optimize Application Performance
php artisan optimize
```

---

## 4. Directory & File Permissions

Set appropriate ownership and write permissions for Laravel storage and cache directories:

```bash
# Set owner to web server user (e.g. www-data)
chown -R www-data:www-data /var/www/html/datapos_commerce

# Set permissions for storage & bootstrap/cache
chmod -R 775 /var/www/html/datapos_commerce/storage
chmod -R 775 /var/www/html/datapos_commerce/bootstrap/cache
```

---

## 5. Security & Nginx Configuration Checklist

### Nginx Block Security Snippet

> **Note:** Most security headers (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`,
> `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`, `Strict-Transport-Security`)
> are already set by the application's `SecurityHeaders` middleware. Duplicating them in Nginx is
> optional but harmless — the application headers take precedence for dynamic responses, while
> Nginx headers protect static assets served directly.

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/html/datapos_commerce/public;

    index index.php;

    # SSL Certificate Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Optional: additional Nginx-level headers (application also sets these)
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block access to hidden files (.env, .git)
    location ~ /\. {
        deny all;
    }

    # Deny access to sensitive storage paths
    location ~ ^/storage/(framework|logs|app/private) {
        deny all;
    }
}
```

---

## 6. Verification Steps

1. **Verify Security Headers**:
   ```bash
   curl -I https://yourdomain.com
   ```
   Check that the following headers are returned:
   - `X-Frame-Options: SAMEORIGIN`
   - `X-Content-Type-Options: nosniff`
   - `X-XSS-Protection: 1; mode=block`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
   - `Content-Security-Policy: ...`
   - `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` (HTTPS only)

2. **Verify Route Caching**:
   ```bash
   php artisan route:list
   ```

3. **Verify Debug Mode is Disabled**:
   Ensure `APP_DEBUG=false` so error stack traces are never exposed to clients.

4. **Verify Health Check Endpoint**:
   ```bash
   curl -I https://yourdomain.com/up
   ```
   Should return HTTP `200 OK`.

5. **Verify Session Security**:
   Check that the session cookie has the `Secure`, `HttpOnly`, and `SameSite=Lax` flags:
   ```bash
   curl -I -X POST https://yourdomain.com/login -d "phone=test&password=test"
   ```
   Look for `Set-Cookie` header containing `Secure; HttpOnly; SameSite=lax`.

6. **Verify APP_KEY is Set**:
   ```bash
   php artisan key:generate --show
   ```
   Should return a 32-character base64-encoded string.

## 7. Application Security Architecture

The following security layers are active in the application:

| Layer | Mechanism | Location |
|---|---|---|
| **Security Headers** | `SecurityHeaders` middleware (global) | `app/Http/Middleware/SecurityHeaders.php` |
| **CSRF Protection** | Laravel's `VerifyCsrfToken` (web middleware group) | Automatic for all `POST/PUT/DELETE` routes |
| **Rate Limiting** | Named limiters: `login`, `register`, `orders`, `imports`, `glass_finder_favorite` | `AppServiceProvider::boot()` |
| **Authentication** | Session-based auth with session regeneration on login/logout | `LoginController`, `RegisterController` |
| **Authorization** | Store-scoped roles via `store_user` pivot table & `EnsureStoreAccess` middleware | `EnsureStoreAccess.php`, `User::hasStoreRole()` |
| **HTTPS Enforcement** | `URL::forceScheme('https')` in production | `AppServiceProvider::boot()` |
| **APP_KEY Validation** | Production boot-time check logs critical warning if key is missing | `AppServiceProvider::boot()` |
| **Input Validation** | Form request validation on all store/update operations | Each controller method |
| **Session Security** | `HttpOnly`, `Secure` (production), `SameSite=Lax`, encryption | `.env` + `session.php` config |

### Content-Security-Policy (CSP)

The application sets the following CSP headers via `SecurityHeaders` middleware:

```
default-src 'self';
script-src 'self' 'nonce-<per-request>' 'unsafe-eval';
style-src 'self' 'unsafe-inline';
img-src 'self' data: blob:;
font-src 'self';
connect-src 'self';
frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube.com;
frame-ancestors 'none';
form-action 'self';
base-uri 'self';
object-src 'none'
```

> `script-src` is **nonce-based**: the `SecurityHeaders` middleware generates a fresh nonce
> per request and shares it with views as `$cspNonce`; every inline `<script>` block in the
> Blade layouts carries `nonce="{{ $cspNonce }}"`. `'unsafe-inline'` is no longer allowed for
> scripts — inline event-handler attributes were replaced by the delegated listeners in
> `resources/js/csp-helpers.js` (`data-ios-href`, `data-catalog-view`, `data-auto-submit`,
> `data-confirm`, `data-print`, `data-img-fallback`). `'unsafe-eval'` remains because Alpine's
> standard build compiles directive expressions with the `Function` constructor; drop it only
> after migrating to the `alpinejs/csp` build. `frame-src` whitelists the Google Maps embed
> (`mapEmbedSrc()` → `www.google.com/maps?...&output=embed`) and the YouTube video embeds
> used on the How-to-Order page.

	> **Note:** `'unsafe-inline'` and `'unsafe-eval'` are required by Livewire's Alpine.js integration.
	> When migrating to a nonce-based CSP, update both the middleware and the Blade layouts.

> **⚠️ Hostinger production quirk (verified 2026-08-09):** LiteSpeed injects a bare
> `Content-Security-Policy: upgrade-insecure-requests` at the vhost level, which **replaces** the
> nonce-based policy the middleware sets (the vhost `Header always set` runs after PHP). The fix is
> the directory-level `Header always set Content-Security-Policy "..."` block at the top of
> `public/.htaccess` (applied after vhost-level headers — same proven pattern as acdcmm.com). A
> static header cannot carry the per-request nonce, so in production `script-src` uses
> `'unsafe-inline' 'unsafe-eval'`; the nonce middleware still applies locally and takes over
> automatically if the vhost override is removed. **Keep `public/.htaccess` and
> `app/Http/Middleware/SecurityHeaders.php` in sync.** Verify after deploy:
> `curl -sI https://datapos.com/ | grep -i content-security-policy` should show the full policy,
> not just `upgrade-insecure-requests`.

---

## 8. Database & Performance Optimization

### Indexes

All performance indexes are created by the migration `2026_07_28_020000_add_performance_indexes.php`.
Run `php artisan migrate` to apply them:

| Table | Index | Purpose |
|-------|-------|---------|
| `products` | `stock_status` | Dashboard in-stock / out-of-stock counts |
| `products` | `is_featured` | Future featured-product listings |
| `orders` | `created_at` | Admin & customer "latest orders" sorting |

### Cache Strategy

Dashboard aggregation stats (product counts, order counts, etc.) are cached for 60 seconds
via `Cache::remember()`. This reduces 7 COUNT queries per page load to 1 cache read.

**Recommended cache driver:** `file` (single-server) or `redis` (multi-server).
The `database` cache driver stores cache in MySQL — it adds DB load rather than reducing it.

```bash
# Set in .env
CACHE_STORE=file
```

### CSV Import Performance

Both Product and Glass Finder CSV imports have been optimised:

1. **Pre-loaded duplicate sets** — existing SKUs and glass codes are loaded into memory
   before the import loop, eliminating per-row `SELECT ... EXISTS()` checks.
2. **DB transaction wrapping** — the entire import runs inside a single `DB::transaction()`,
   reducing disk-commit overhead and ensuring atomic rollback on failure.

For a 1,000-row import, these optimisations reduce database queries from ~3,000 to ~1,000
(one INSERT per row plus one pre-load query).

### Image Optimization

The app stores originally-uploaded images without compression. For production:

- **Before uploading:** Compress images to ~100-200 KB using TinyPNG, Squoosh, or similar.
- **Resolution:** Product/category images at ~800×800 px is sufficient for HiDPI displays.
- **Format:** WebP provides 25-35% smaller files than JPEG at equivalent quality.
- **CDN (future):** For multi-server deployments, move the `public` disk to S3-compatible
  object storage and serve via a CDN.


---

## Backup Strategy

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


---

## Production .env Example (safe template)

# Production Environment Example

Use this as a checklist when creating the real server `.env`. Do not commit the real `.env`.

```dotenv
APP_NAME="DataPOS"
APP_ENV=production
APP_KEY=base64:GENERATE_ON_SERVER_ONCE
APP_DEBUG=false
APP_URL=https://example.com
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
ALLOW_UAT_SEEDING=false
SHOW_QUICK_LOGIN=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=database_name
DB_USERNAME=database_user
DB_PASSWORD=strong_database_password

SESSION_DRIVER=database
CACHE_STORE=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=smtp_user
MAIL_PASSWORD=smtp_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

VIBER_PUBLIC_NUMBER="+959000000000"
TELEGRAM_PUBLIC_USERNAME="your_store_username"
STORE_PUBLIC_NAME="DataPOS"
STORE_PUBLIC_SLUG="datapos-mobile"
STORE_PUBLIC_PHONE="+959000000000"
```

`APP_KEY` must be generated only during the initial environment setup. Do not rotate it casually after encrypted cookies, sessions, or other encrypted data exist.

Production deployments must use `php artisan db:seed --class=ProductionSeeder --force` only. Do not run UAT/demo seeders, and do not use `php artisan migrate --seed` unless `DatabaseSeeder` has been re-audited for production-only data. Create the first admin with `php artisan production:create-admin --role=platform_owner`; the command prompts for credentials and does not provide default passwords.

The first real production store is `DataPOS` with canonical slug `datapos-mobile` — the same slug used throughout local development.


---

## Scrubbing Secrets From Git History

# Scrub Production Credentials from Git History

> **ဘာကြောင့် ဒီဖိုင် လိုလဲ:** `docs/production-env-datapos.md` (အခု `docs/ops/production-env-datapos.md` — **2026-08-13 တွင် working tree ကနေ ဖျက်လိုက်ပြီ**၊ ဒါပေမဲ့ git history ထဲမှာ ကျန်နေဆဲ — ဒီအောက်က scrub ညွှန်ကြားချက်တွေက history အတွက် ဆက်အသုံးဝင်တယ်)
> ထဲမှာ **တကယ့် production credentials** (`APP_KEY`, `DB_PASSWORD`, `MAIL_PASSWORD`) တွေ ပါခဲ့ပြီး
> **initial commit (59976ee) ကတည်းက** git history ထဲ ရောက်နေပါသည်။
> Working tree မှာ values တွေကို REDACTED လုပ်ပြီးသားဖြစ်သော်လည်း **အဟောင်း commits တွေထဲမှာ
> မူရင်းတန်ဖိုးတွေ ကျန်နေဆဲဖြစ်သည်။**

---

## 1. လက်ရှိ အခြေအနေ (ဒီ repo အတွက် အတည်ပြုပြီး)

| အချက် | အခြေအနေ |
|---|---|
| Total commits | **4** (59976ee → 38e1fbb → a84d860 → 8c0a265) — history က သေးငယ်လို့ rewrite ပေါ့ပါးသည် |
| Secrets ပါသော ဖိုင် | `docs/production-env-datapos.md` တစ်ခုတည်းသာ (initial commit မှ စ၍ commit ၄ ခုလုံးတွင်) |
| `.env` ကိုယ်တိုင် | **ဘယ်တော့မှ commit မဖြစ်ဖူး** ✓ (`.env.example` သာ — အန္တရာယ်ကင်း) |
| Remote | `https://github.com/shwepyithit568-commits/DataPOS.git` — **private ဖြစ်ဖွယ်ရှိ** (unauthenticated API က 404) |
| Branch | `main` — local = origin/main (up to date) |
| filter-repo | **မရှိသေးပါ** — `pip install git-filter-repo` ဖြင့် ထည့်ရမည် |

---

## 2. ရွေးစရာ ၂ ခု — ဘယ်ဟာ ယူမလဲ

### Option A — History rewrite (git filter-repo) — **အကြံပြုသည်**
Commit ၄ ခုပဲ ရှိတာမို့ rewrite က မြန်ပြီး အန္တရာယ်နည်းသည်။ ပြီးရင် **credentials တွေကို ဘယ်လိုပဲဖြစ်ဖြစ် rotate လုပ်ပါ** (အောက် §6) — history ဖျက်တာက အနာဂတ် ယိုစိမ့်မှုကို ရပ်တန့်ပေးတာပဲ၊ ဖော်ထုတ်ပြီးသား အချက်အလက်ကို မပြန်ပျက်စေနိုင်ပါ။

### Option B — History မပြောင်းဘဲ ထားပါ (rotate သာလုပ်ပါ)
Repo က private ဖြစ်ပြီး ရရှိသူ အကန့်အသတ်ရှိပါက "အရေးမကြီးသေး" လို့ ဆုံးဖြတ်နိုင်သည်။
ဒါပေမယ့် (a) repo ကို public ပြောင်း/မျှဝေမယ်ဆိုရင် ပေါက်ကြားမယ်၊ (b) GitHub Secret Scanning က
ဖော်ထုတ်ပြီးသားလည်း ဖြစ်နိုင်သည်။ **ဒီအခြေအနေမှာ Option A ကို ဦးစားပေးပါ။**

---

## 3. Option A — Step-by-step (git filter-repo)

### Step 0 — Prerequisite
- Python 3.5+ ရှိရမည် (`python --version` စစ်ပါ)။
- filter-repo ထည့်ပါ:
  ```bash
  pip install git-filter-repo
  ```
  (သို့) single-script နည်း: `https://github.com/newren/git-filter-repo` မှ `git-filter-repo` ဖိုင်ကို
  ဒေါင်းလုဒ်လုပ်ပြီး PATH ထဲ ထားပါ။

### Step 1 — အရင်ဆုံး backup လုပ်ပါ (မဖြစ်မနေ)
```bash
# 1a. Git history backup (branch/commit အားလုံး)
git bundle create "D:\backup-datapos-$(date +%Y%m%d).bundle" --all

# 1b. Working tree (uncommitted) backup — ဒီ repo မှာ uncommitted အလုပ်တွေ အများကြီး ရှိနေလို့
#     ဒီ step က အရေးကြီးဆုံးပဲ။ Folder တစ်ခုလုံး ကော်ပီကူးထားပါ:
#     D:\xmapp\htdocs\DataPOS  →  D:\backup\DataPOS-20260813
```

### Step 2 — filter-repo ကို **fresh clone** ပေါ်မှာ ပြေးပါ (main checkout ကို မထိ)
> ⚠️ filter-repo သည် **dirty working tree ရှိရင် ငြင်းပယ်သည်**။ ဒီ repo မှာ uncommitted အလုပ်တွေ
> အများကြီးရှိနေလို့ **မူလ folder ထဲ မပြေးပါနဲ့** — အောက်ပါအတိုင်း clone အသစ်မှာ ပြေးပါ။

```bash
cd D:\xmapp\htdocs
git clone https://github.com/shwepyithit568-commits/DataPOS.git DataPOS-scrub
cd DataPOS-scrub

# secrets ပါသော ဖိုင်ကို history အားလုံးကနေ ဖျက်
git filter-repo --path docs/production-env-datapos.md --invert-paths

# filter-repo က origin remote ကို ဖျက်လိုက်လို့ ပြန်ထည့်ပါ
git remote add origin https://github.com/shwepyithit568-commits/DataPOS.git
```

> `--invert-paths` က **ဖိုင်တစ်ခုလုံးကို** commit အားလုံးကနေ ဖျက်သည် (values ချည်း မဟုတ်) —
> အန္တရာယ်ကင်းဆုံး နည်းဖြစ်သည်။ ဖိုင်ရဲ့ structure ကို ထိန်းထားလိုပါက အစား
> `--replace-text` (values စာရင်း ပါသော ဖိုင်) သုံးနိုင်သည် — သို့သော် key/value ပုံစံကို
> ထားထားလိုပါက `--invert-paths` က ပိုသန့်သည်။

### Step 3 — Verify (မတင်မီ)
```bash
# ဖိုင် မရှိတော့ကြောင်း စစ်
git log --all --oneline -- docs/production-env-datapos.md     # (output ဘာမှ မရှိရမည်)

# commit ၄ ခု ကျန်နေဆဲ ဖြစ်ကြောင်း စစ်
git rev-list --count HEAD                                      # → 4

# value တွေ history ထဲ မကျန်တော့ကြောင်း စစ် (ကိုယ်ပိုင် secret string နဲ့ အစားထိုး)
git rev-list --all | xargs git grep -l "APP_KEY=base64" 2>/dev/null || echo "CLEAN"
```

### Step 4 — Force-push
```bash
git push --force --all origin
# (branch က main တစ်ခုတည်းဆိုရင်:  git push --force origin main)
```

### Step 5 — Main checkout (မူရင်း folder) ကို ပြန်ညှိပါ
> ⚠️ ဒီ step က **သင့်ရဲ့ uncommitted အလုပ်တွေကို မဖျက်ပါစေနဲ့** — မလုပ်မီ stash လုပ်ထားပါ။

```bash
cd D:\xmapp\htdocs\DataPOS
git stash push -u -m "wip-before-history-rewrite"   # uncommitted + untracked အကုန် သိမ်း
git fetch origin
git reset --hard origin/main                        # rewrite ပြီးသား history ကို ယူ
git stash pop                                        # အလုပ်တွေ ပြန်ထုတ်
git status                                           # အလုပ်တွေ ကျန်နေသေးကြောင်း စစ်
```

> ရွေးစရာ (ပိုလုံခြုံ): main checkout ကို မထိဘဲ **DataPOS-scrub ကနေ ဆက်အလုပ်လုပ်ပါ** — folder နာမည်ပြောင်းပြီး
> သုံးနိုင်သည်။ သို့သော် uncommitted အလုပ်တွေက scrub clone ထဲ မရောက်လို့ stash/ကော်ပီ နည်းက ပိုသင့်သည်။

---

## 4. Option A — ပြီးပြီးနောက် မဖြစ်မနေ လုပ်ရမည့်ဟာများ

1. **Collaborators အားလုံး re-clone / hard reset လုပ်ရမည်** — အဟောင်း clone တွေက SHA mismatch ဖြစ်လိမ့်မည်။
2. **GitHub cache purge:** force-push ပြီးနောက် GitHub က အဟောင်း commits တွေကို SHA တိုက်ရိုက်ဖွင့်ချင်း
   ကြည့်လို့ ရနေနိုင်သည်။ GitHub Support ကို
   (github.com/contact → "Sensitive data" request) ဖြင့် cached views ဖျက်ခိုင်းပါ။
3. **Protected branch ဆိုပါက** force-push မရနိုင် — admin ခွင့်ပြုချက် လိုမည်။
4. **Secrets များကို rotate လုပ်ပါ** (အောက် §6) — history ဖျက်တာ လုံလောက်မှု မဟုတ်ပါ။

---

## 5. BFG (အခြားရွေးချယ်စရာ)

Java ရှိပါက BFG Repo-Cleaner သုံးနိုင်သည် (filter-repo ထက် အသုံးပြုရ လွယ်သည်၊
ရလဒ် အတူတူ — history ကို rewrite လုပ်သည်):

```bash
# https://rtyley.github.io/bfg-repo-cleaner/ မှ bfg.jar ဒေါင်းလုဒ်
java -jar bfg.jar --delete-files production-env-datapos.md
git reflog expire --expire=now --all && git gc --prune=now --aggressive
git push --force --all origin
```

---

## 6. Credential Rotation (မဖြစ်မနေ — ဘယ် option ပဲ ယူယူ)

History ထဲက ထွက်သွားပြီးသား values တွေကို ပြန်ပျက်စေလို့ မရပါ။ ဒါကြောင့် Hostinger server မှာ:

1. **Laravel APP_KEY** ပြောင်းပါ — server ပေါ်ရှိ `.env` မှာ
   `php artisan key:generate` ပြေးပြီး ရလာတဲ့ key ကို ထည့်ပါ (sessions/encrypted data invalid ဖြစ်မှာ — ပုံမှန်ပဲ)။
2. **DB_PASSWORD ပြောင်းပါ** — Hostinger MySQL panel မှာ password အသစ်တည်ပြီး `.env` update + `php artisan config:cache`။
3. **MAIL_PASSWORD ပြောင်းပါ** — email provider (SMTP) password ပြောင်းပါ။
4. GitHub က public repo ရှိရင် Secret Scanning က auto-detect လုပ်ပြီး alert ပို့ထားနိုင်သည် — သတိပြုပါ။

---

## 7. အကျိုးဆက် (Consequences) အကျဉ်းချုပ်

| ဆိုးကျိုး | ရှင်းလင်းချက် |
|---|---|
| **အားလုံး commit hashes ပြောင်းသွားမည်** | 4 commits လုံး — SHA အသစ်တွေ ရသည်; ဘယ်သူမဆို အဟောင်း clone ရှိရင် ပြန်စရမည် |
| **Force-push လိုအပ်သည်** | `--force` မသုံးရင် push ငြင်းမည်; protected branch ဆိုရင် admin လို |
| **GitHub မှာ အဟောင်း objects ကျန်နေနိုင်သည်** | SHA တိုက်ရိုက်နဲ့ ကြည့်လို့ရနိုင်သေးသည် — Support နဲ့ purge လုပ်ရမည် |
| **Working tree ကို မထိရ** | filter-repo က dirty repo ကို ငြင်းသည် → fresh clone / stash နည်း သုံးရမည် |
| **origin remote ဖျက်ခံရသည်** | filter-repo ပြီးတိုင်း `git remote add origin …` ပြန်လုပ်ရမည် |
| **Credentials တွေ ပေါက်ကြားပြီးသားပါ** | history ဖျက်တာက အနာဂတ်အတွက်; **rotate လုပ်ဖို့ မမေ့ပါနဲ့** |

---

## 8. လုံးဝ မလုပ်သင့်သော အရာ (သတိပေးချက်)

- ⛔ `git filter-branch` ကို **မသုံးပါနဲ့** (deprecated, နှေးပြီး မှားလွယ်) — filter-repo / BFG သုံးပါ။
- ⛔ Dirty tree ပေါ်မှာ filter-repo ပြေးဖို့ မကြိုးစားပါနဲ့ — အလုပ်တွေ ဆုံးရှုံးနိုင်သည်။ အမြဲ backup + fresh clone မှ လုပ်ပါ။
- ⛔ Push မလုပ်မီ verify (Step 3) ကို မကျော်ပါနဲ့။
