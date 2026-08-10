# DataPOS Ecommerce v0.1.0-rc1

Release candidate for MVP hosting selection and deployment preparation.

## Included MVP Features

- Public storefront home, catalog, search, filters, product detail, gallery, and favorites.
- Store-scoped admin dashboard with product, category, brand, image, Glass Finder, import history, order, wholesale, and settings workflows.
- Product CSV/XLSX import preview/confirm, duplicate handling, import history, and failed-row downloads.
- Glass Finder search, compatibility groups, CSV/XLSX import, and admin CRUD.
- Customer order builder with Viber/Telegram contact links and admin order status workflow.
- Wholesale application, approval/rejection, and wholesale price visibility.
- Store isolation, CSRF protection, HTTPS configuration controls, and UAT seeding safety checks.
- Production-safe seeding uses an explicit `ProductionSeeder`; demo/UAT seeding remains opt-in and blocked outside local/testing/UAT environments.
- First production admin creation uses `php artisan production:create-admin` with operator-provided credentials and no default passwords.
- First real production store bootstrap uses `ACDC Mobile` with canonical slug `acdc-mobile`; `datapos-mobile` remains local/UAT data only.

## Known Non-Blocking Limitations

- Livewire remains installed in Composer but has no active app usage in `app/`, `routes/`, `resources/views/`, or `resources/js/`.
- `public/build` is ignored locally; deploy prebuilt assets separately or build on the target server.
- Store contact/profile values are database/admin managed in the MVP. Environment placeholders are documentation aids.

## Required Server Capabilities

- PHP 8.2 or newer.
- PHP extensions required by Laravel and imports: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`, `gd`.
- Composer 2.
- MySQL or MariaDB with `utf8mb4`.
- Writable `storage/` and `bootstrap/cache/`.
- HTTPS certificate support.
- Optional Node.js 24+ and npm if assets are built on the server.

## Deployment Prerequisites

- Create a real production `.env`; never commit it.
- Generate `APP_KEY` once during initial setup only.
- Set `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync`, and `ALLOW_UAT_SEEDING=false`.
- Set `SHOW_QUICK_LOGIN=false`; Quick Login must remain disabled in production.
- Back up the database before every migration or deployment after launch.
- Run `php artisan migrate --force`; never run `php artisan migrate:fresh` on production.
- Run `php artisan db:seed --class=ProductionSeeder --force`; never run UAT/demo seeders in production.
- Create the first platform admin with `php artisan production:create-admin --role=platform_owner`.
- Create the first store with `php artisan production:create-store --name="ACDC Mobile" --slug=acdc-mobile`.

## Migration-Edit History Note

This project is still pre-hosting. Several migrations were created during the hardening/UAT phases. Treat the current migration set as the release-candidate baseline and do not edit migrations after production data exists.

## Queue And Scheduler

The MVP release is configured for `QUEUE_CONNECTION=sync`. No always-on queue worker is required for the current MVP. If background jobs are added later, configure a process supervisor. No production scheduler requirement is currently confirmed.

## Rollback Overview

Use maintenance mode, restore the previous code release, restore the database backup when migrations or data maintenance changed data, restore storage if needed, clear/rebuild caches, and verify login/catalog/admin/order flows before reopening the site.
