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
