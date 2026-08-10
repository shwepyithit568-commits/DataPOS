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
