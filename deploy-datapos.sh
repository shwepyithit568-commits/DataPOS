#!/usr/bin/env bash
# Deploy DataPOS (data_ecommerce) to Hostinger for datapos.com via SSH (tar + ssh pipe).
#
# NOTE: This follows the same split layout as the acdcmm.com deployment:
#   - laravel_app/  -> full Laravel application (not a webroot)
#   - public_html/  -> webroot: contents of public/ + storage symlink
#
# Usage:
#   ./deploy-datapos.sh              # code deploy (no migrations)
#   RUN_MIGRATIONS=true ./deploy-datapos.sh   # code deploy + run pending migrations
#
# Notes:
#   - Server .env, vendor/, node_modules/, storage uploads and caches are NEVER overwritten.
#   - Run from the repository root (D:\xmapp\htdocs\data_ecommerce).
#   - Requires the SSH key that is already set up for the acdcmm.com deployment.
set -euo pipefail

# --- Config (edit if Hostinger details change) ---
HOST="***REMOVED***"
PORT="***REMOVED***"
USER="***REMOVED***"
KEY="${HOME}/.ssh/***REMOVED***"
TARGET="/home/${USER}/domains/datapos.com"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-false}"

SSH="ssh -p ${PORT} -i ${KEY} -o BatchMode=yes -o StrictHostKeyChecking=accept-new"
REMOTE="${USER}@${HOST}"

APP_EXCLUDES=(
  --exclude='./vendor'
  --exclude='./node_modules'
  --exclude='./.git'
  --exclude='./.freebuff'
  --exclude='./.env'
  --exclude='./.env.*'
  --exclude='./database/database.sqlite'
  --exclude='./database/database.sqlite-*'
  --exclude='./storage/app/private'
  --exclude='./storage/app/public'
  --exclude='./storage/framework/cache'
  --exclude='./storage/framework/sessions'
  --exclude='./storage/framework/testing'
  --exclude='./storage/framework/views'
  --exclude='./storage/logs'
  --exclude='./bootstrap/cache/*.php'
  --exclude='./public/storage'
)

echo "==> [1/3] Uploading application to ${REMOTE}:${TARGET}/laravel_app"
tar czf - "${APP_EXCLUDES[@]}" -C . . \
  | ${SSH} "${REMOTE}" "mkdir -p '${TARGET}/laravel_app' && tar xzf - -C '${TARGET}/laravel_app'"

echo "==> [2/3] Uploading webroot (public/) to ${REMOTE}:${TARGET}/public_html"
# Excludes the local `public/storage` symlink; the real storage symlink is created below.
tar czf - --exclude='./storage' -C public . \
  | ${SSH} "${REMOTE}" "mkdir -p '${TARGET}/public_html' && tar xzf - -C '${TARGET}/public_html'"

echo "==> [3/3] Post-deploy: composer install + storage link + caches"
# Hostinger CLI disables proc_open, so composer's `@php` subprocess scripts
# (post-autoload-dump) would fail. Use --no-scripts and run package:discover directly.
# symlink() is also disabled, so create the public storage symlink with shell `ln -s`.
${SSH} "${REMOTE}" bash -s <<EOF
set -e
cd '${TARGET}/laravel_app'
composer install --no-scripts --no-dev --optimize-autoloader --no-interaction --no-progress
php artisan package:discover
if [ ! -e '${TARGET}/public_html/storage' ]; then
  ln -s '../laravel_app/storage/app/public' '${TARGET}/public_html/storage'
fi
cat > '${TARGET}/public_html/index.php' <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Split layout: public_html is the webroot, Laravel lives one level up.
\$laravelRoot = dirname(__DIR__) . '/laravel_app';

if (file_exists(\$maintenance = \$laravelRoot . '/storage/framework/maintenance.php')) {
    require \$maintenance;
}

require \$laravelRoot . '/vendor/autoload.php';

/** @var Application \$app */
\$app = require_once \$laravelRoot . '/bootstrap/app.php';

\$app->handleRequest(Request::capture());
PHP
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

# --- Post-deploy asset cleanup (best-effort; never fails the deploy) ---
# 1) Legacy favicon.svg (1.4MB) — replaced by favicon.ico + apple-touch-icon.png.
rm -f '${TARGET}/public_html/favicon.svg'
# 2) Legacy TTF fonts — replaced by WOFF2 subsets (~80% smaller).
rm -f '${TARGET}/public_html/build/assets/'*.ttf
# 3) Stale hashed assets (old CSS/JS/fonts) not referenced by the freshly
#    uploaded build manifest. Parsed against the manifest so we never delete
#    a file the current release actually uses.
php -r '
    \$raw = file_get_contents(\$argv[1]);
    \$m = json_decode(\$raw, true);
    if (!is_array(\$m) || count(\$m) === 0) { fwrite(STDERR, "bad manifest\n"); exit(1); }
    \$keep = [];
    foreach (\$m as \$entry) {
        if (isset(\$entry["file"])) { \$keep[] = basename(\$entry["file"]); }
        foreach ((\$entry["css"] ?? []) as \$c) { \$keep[] = basename(\$c); }
        foreach ((\$entry["imports"] ?? []) as \$c) { \$keep[] = basename(\$c); }
    }
    if (count(\$keep) === 0) { fwrite(STDERR, "nothing to keep\n"); exit(1); }
    foreach (glob(\$argv[2] . "/assets/*") as \$f) {
        if (!in_array(basename(\$f), \$keep, true)) { @unlink(\$f); }
    }
' '${TARGET}/public_html/build/manifest.json' '${TARGET}/public_html/build' \
  || echo "WARN: stale asset cleanup skipped (manifest parse failed)"

if [ '${RUN_MIGRATIONS}' = 'true' ]; then
  php artisan migrate --force
fi
echo "DEPLOY_OK"
EOF

echo "==> Deploy complete."
