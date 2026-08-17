# Hostinger Deploy Checklist — DataPOS (datapos.com)

> **ရည်ရွယ်ချက်:** Local validation (full suite green + MySQL smoke test + Drill #3 rehearsal) ပြီးသွားတဲ့ code ကို Hostinger ပေါ်တင် → migrate → production-safe seed → verify အဆုံး အတိအကျ လုပ်ရမယ့် အဆင့်တွေ။
>
> **မူ:** ဒီ checklist က `deploy-datapos.sh` (split layout: `laravel_app/` + `public_html/`) ကို အခြေခံတယ် — server `.env`, `vendor/`, `node_modules/`, `storage/` caches တွေကို **ဘယ်တော့မှ မဖျက်/မရေးလွန်း**။
>
> **နောက်ခံ:** Local rehearsal မှာ ဖမ်းမိပြီး ပြင်ပြီးသား — (1) migration FK-name 64-char limit (MySQL/MariaDB) — `MigrationConstraintNameTest` + `MysqlMigrationSmokeTest` နဲ့ ကာကွယ်ထား · (2) `--set-gtid-purged=OFF` က MySQL 8 မှာသာ (MariaDB က reject) — mysqldump မှာ မထည့်ရ။

---

## 0. Preconditions — local validation အကုန် PASS ဖြစ်ရမယ်

| # | Check | ဘယ်မှာ အတည်ပြု |
|---|---|---|
| P1 | Full suite **886 tests / 4056 assertions green** (SQLite) | `php artisan test` |
| P2 | `MysqlMigrationSmokeTest` — local MySQL/MariaDB ပေါ် `migrate:fresh` 76 applied clean (MySQL မရှိရင် skipped) | suite run |
| P3 | Drill #3 local rehearsal **PASS** — backup → restore → verify → flow → cleanup (live recheck = baseline) | `docs/ops/backup-restore-production-drill.md` §10 |
| P4 | `git status` clean on main · `HEAD == origin/main` | `git status --short` |
| P5 | Assets built locally (`npm run build` → `public/build/manifest.json` fresh) — deploy script က ဒါကို upload လုပ်တယ် | `ls -la public/build/` |

---

## 1. Config — တစ်နေရာတည်းမှာ ဖြည့်ရမယ်

`deploy-datapos.sh` ထဲက placeholder ၅ ခု + hPanel က DB credentials:

| Variable | Value (fill in) |
|---|---|
| `HOST` | `<HOSTINGER_IP>` (hPanel → SSH access) |
| `PORT` | `<SSH_PORT>` (hPanel မှာ ပြတဲ့ port) |
| `USER` | `<HOSTINGER_USER>` |
| `KEY` | `~/.ssh/<hostinger-key>` (acdcmm.com deploy နဲ့ တူညီတဲ့ key) |
| `TARGET` | `/home/${USER}/domains/datapos.com` |
| `DB_DATABASE` | `datapos_commerce_prod` (hPanel မှာ ဆောက်မယ့် DB) |
| `DB_USERNAME` / `DB_PASSWORD` | hPanel က dedicated user (least privilege — ဒီ DB တစ်ခုတည်းပဲ) |
| `APP_URL` | `https://datapos.com` |

---

## 2. Step 1 — hPanel: database ဆောက် (web UI)

1. hPanel → **Databases** → **Create Database**:
   - Name: `datapos_commerce_prod`
   - Charset: `utf8mb4` / `utf8mb4_unicode_ci`
2. **Create Database User** (သီးခြား user): `datapos_user` + strong password (`.env` ထဲ ပဲ ထည့်၊ ဘယ်နေရာမှ မရေး).
3. **Assign user to database** — grant `ALL PRIVILEGES` on `datapos_commerce_prod` **ဒီ DB တစ်ခုတည်းမှာ** (global grants မလုပ်ရ).

---

## 3. Step 2 — Code deploy (migrations မပါ — code ပဲ)

```bash
# ပထမဆုံး: deploy guard ကို ဖွင့် + migrations OFF
ALLOW_HOSTINGER_DEPLOY=true ./deploy-datapos.sh
```

မျှော်လင့်ရတဲ့ output: `==> [1/3] Uploading application...` → `[2/3] Uploading webroot...` → `[3/3] Post-deploy...` → **`DEPLOY_OK`**.

Script က ဘာတွေ လုပ်လဲ (မှတ်ထားစရာ):
- `composer install --no-scripts --no-dev --optimize-autoloader` — Hostinger CLI က `proc_open` ကို disable ထားလို့ `--no-scripts` + `php artisan package:discover` သီးခြား run.
- `public_html/storage` → `../laravel_app/storage/app/public` `ln -s` (PHP `symlink()` disabled → shell နဲ့).
- Split-layout `public_html/index.php` ကို ပြန်ရေး (`LARAVEL_START` → `laravel_app/bootstrap/app.php`).
- `optimize:clear` → `config:cache` → `view:cache`.
- Asset cleanup: stale hashed assets ကို manifest နဲ့ ယှဉ်ပြီး ဖျက် (current release သုံးတဲ့ files တွေကို မဖျက်).

> ⚠️ `migrate:status` / `migrate` ကို run ချင်ရင် **အဆင့် ၅ မှာ** (`.env` ပြီးမှ) လုပ်ရမယ် — မဟုတ်ရင် app က hPanel DB ကို မမှတ်မိသေးဘူး.

---

## 4. Step 3 — Server `.env` (production values)

SSH ဝင် → `laravel_app/.env` ကို ရေးပြီး **မရှိရင်** `.env.example` ကနေ copy:

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <USER>@<HOSTINGER_IP>
cd /home/<USER>/domains/datapos.com/laravel_app
cp .env.example .env   # first time only — .env ကို deploy script က ဘယ်တော့မှ မထိ
nano .env
```

`.env` ထဲ မဖြစ်မနေ ထည့်ရမယ့်ဟာ (DEPLOYMENT.md §2 အတိုင်း):

```ini
APP_NAME="DataPOS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://datapos.com
APP_TIMEZONE=Asia/Yangon

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=datapos_commerce_prod
DB_USERNAME=datapos_user
DB_PASSWORD=<hPanel မှာ ဖန်တီးထားတဲ့ strong password>

CACHE_STORE=file
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
LOG_LEVEL=info
```

ပြီးရင် (`.env` ပြောင်းတိုင်း ဒါ ၂ ခု ပြန် run — deploy script က ပို့တဲ့ config cache က အဟောင်း ဖြစ်နေလို့):

```bash
# APP_KEY မရှိသေးရင် (production boot မှာ missing key က critical warning ထုတ်တယ်)
php artisan key:generate --force

# .env ပြောင်းပြီး → cache ပြန်ဆောက်
php artisan config:clear && php artisan config:cache
```

> 🚫 **`ALLOW_UAT_SEEDING` ကို မထည့်ရ** — UatSeeder က environment guard (`local/testing/uat` ပဲ) + flag ၂ ထပ် ကာကွယ်ထားပေမယ့် ဒီ flag မရှိမှ ပိုလုံခြုံတယ်.

---

## 5. Step 4 — Migrate (production DB)

```bash
# code deploy + pending migrations
ALLOW_HOSTINGER_DEPLOY=true RUN_MIGRATIONS=true ./deploy-datapos.sh
```

Script ထဲက `php artisan migrate --force` က **76 ခုလုံး** run မယ် (non-interactive).

Verify — migrations အကုန် applied:

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <USER>@<HOSTINGER_IP> \
  "cd /home/<USER>/domains/datapos.com/laravel_app && php artisan migrate:status | tail -5"
```

မျှော်လင့်ရတဲ့ result: `Ran?` column အကုန် **Yes** (76 rows). MySQL/MariaDB compatibility က local smoke test + guard test နဲ့ ပြီးသား သက်သေရှိ — ဒါပေမယ့် `migrate:status` က နောက်ဆုံး confirmation.

> 💡 `migrate` fail ရင် (production-blocking) — local MariaDB မှာ အရင် reproduce လုပ်ပြီး `MigrationConstraintNameTest` နဲ့ စစ်။ ဒီနေ့ ဖမ်းမိတဲ့ FK-name bug မျိုး ပြန်ပေါ်ရင် ဒီ test က fail လုပ်ပေးမယ်.

---

## 6. Step 5 — Seed (production-safe သာ)

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <USER>@<HOSTINGER_IP> \
  "cd /home/<USER>/domains/datapos.com/laravel_app && php artisan db:seed --class=ProductionSeeder"
```

မျှော်လင့်ရတဲ့ output: `ProductionSeeder completed: production blog content seeded.`

**စည်းကမ်း (အရေးကြီး):**
- ✅ **`--class=ProductionSeeder` ပဲ** run ရမယ် — ဒါက production-safe (blog content ပဲ).
- ❌ `php artisan db:seed` (bare) **မလုပ်ရ** — `DatabaseSeeder` က no-op ဖြစ်လို့ ဘာမှ မဖြစ်ပေမယ့် အလေ့အကျင့် မကောင်းဘူး.
- ❌ **`UatSeeder` ကို ဘယ်တော့မှ မလုပ်ရ** — demo catalog + demo users + demo sales data တွေ production DB ထဲ ဝင်မယ် (environment guard ရှိပေမယ့် `APP_ENV=production` ဆို abort လုပ်တယ် — ဒါကို အားမကိုးဘဲ run ကို မလုပ်ပဲ နေရတာ အကောင်းဆုံး).

---

## 7. Step 6 — Pilot store + users bootstrap (tinker)

UAT demo data မလိုဘဲ **production အတွက် အနည်းဆုံး** — Store ၁ ခု + Owner/Manager/Staff users:

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <USER>@<HOSTINGER_IP> \
  "cd /home/<USER>/domains/datapos.com/laravel_app && php artisan tinker"
```

```php
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// 1) Pilot store (canonical slug)
$store = Store::firstOrCreate(
    ['slug' => 'datapos-mobile'],
    ['name' => 'DataPOS', 'viber_number' => '09123456789', 'telegram_username' => 'datapos_mobile', 'is_active' => true]
);

// 2) Owner (platform_owner — platform-wide, store-agnostic)
$owner = User::firstOrCreate(
    ['phone' => '09100000001'],
    ['name' => 'Owner (Platform Admin)', 'password' => Hash::make('<STRONG_PASSWORD>'), 'role' => 'platform_owner']
);

// 3) Manager + staff (store-scoped; PIN = POS override approval)
$manager = User::firstOrCreate(
    ['phone' => '09100000002'],
    ['name' => 'Store Manager', 'password' => Hash::make('<STRONG_PASSWORD>'), 'role' => 'customer', 'pos_pin' => Hash::make('1234')]
);
$staff = User::firstOrCreate(
    ['phone' => '09100000003'],
    ['name' => 'Cashier', 'password' => Hash::make('<STRONG_PASSWORD>'), 'role' => 'customer']
);

// 4) Store memberships
DB::table('store_user')->updateOrInsert(
    ['store_id' => $store->id, 'user_id' => $manager->id],
    ['role' => 'store_manager', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]
);
DB::table('store_user')->updateOrInsert(
    ['store_id' => $store->id, 'user_id' => $staff->id],
    ['role' => 'staff', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]
);

echo 'Store=' . $store->slug . ' Owner=' . $owner->phone . ' Manager=' . $manager->phone . ' Staff=' . $staff->phone . PHP_EOL;
```

ပြီးရင် exit → branches/warehouses က **auto-create** ဖြစ်တယ် (first POS use မှာ `StoreLocationService::ensureDefaults` — ဒါမှမဟုတ် ကြိုတင်ဖို့):

```bash
php artisan inventory:ensure-locations
```

> 📝 Storefront settings (store_name/address/phones/payment info...) ကို Owner login ပြီး **admin UI → Store Settings** ကနေ ဖြည့်ရမယ် — production tinker နဲ့ မလိုအပ်ဘဲ မထည့်ပါနဲ့.

---

## 8. Step 7 — Post-deploy verification (အကုန် PASS ဖြစ်ရမယ်)

Local (ဘယ်နေရာကမဆို) ကနေ:

```bash
# 1) Health endpoint
curl -s -o /dev/null -w "%{http_code}\n" https://datapos.com/up        # expect 200

# 2) Security headers (SecurityHeaders middleware)
curl -sI https://datapos.com/ | grep -iE "x-frame-options|x-content-type-options|referrer-policy|content-security-policy|strict-transport-security"

# 3) ⚠️ Hostinger CSP quirk — LiteSpeed က bare `upgrade-insecure-requests` CSP ထည့်တတ်တယ်
#    → public/.htaccess ရဲ့ `Header always set Content-Security-Policy "..."` ကို
#      SecurityHeaders middleware နဲ့ in sync ဖြစ်အောင် ထားရမယ် (deploy မှာ .htaccess ပါ upload ဖြစ်တယ်)
curl -sI https://datapos.com/ | grep -i "content-security-policy"

# 4) Built assets load (manifest + hashed CSS/JS)
curl -s -o /dev/null -w "%{http_code}\n" https://datapos.com/build/manifest.json   # expect 200
```

Server ပေါ်မှာ:

```bash
cd /home/<USER>/domains/datapos.com/laravel_app

# 5) Config cache active + routes registered
ls bootstrap/cache/config.php                      # ရှိရမယ်
php artisan route:list --path=store                # store routes မြင်ရမယ်

# 6) Storage symlink
ls -la /home/<USER>/domains/datapos.com/public_html/storage   # → ../laravel_app/storage/app/public

# 7) App က production DB ကို တကယ် မှတ်မိကြောင်း (config cache ရှိနေလို့ env override မလုပ်ရ)
php artisan tinker --execute="echo 'env=' . config('app.env') . ' db=' . config('database.connections.' . config('database.default') . '.database') . PHP_EOL;"
# expect: env=production db=datapos_commerce_prod

# 8) APP_KEY ရှိ
php artisan key:generate --show   # 32-char base64 string ပြန်လာရမယ် (generate မလုပ်ဘူး)
```

Browser/UI မှာ:
- [ ] `https://datapos.com` → storefront render (Owner မဝင်ရသေးရင်လည်း 200).
- [ ] `https://datapos.com/login` → Owner/Manager phone + password နဲ့ ဝင်လို့ရ.
- [ ] Login ပြီး session cookie မှာ `Secure; HttpOnly; SameSite=Lax` ပါ.
- [ ] `/store/datapos-mobile/pos` → cashier page render + shift open/close စမ်း.
- [ ] Owner → admin → Store Settings → ဆိုင် info ဖြည့်လို့ရ.

---

## 9. Step 8 — Post-deploy operational sequence (runbook §3.1)

Deploy + verify ပြီးတာနဲ့ pilot cutover စာရင်းကို ဒီအတိုင်း ဆက်ဖြတ်:

| # | Action | Success criteria | Doc |
|---|---|---|---|
| 1 | **Drill #3 production backup/restore run** | §2.4 checklist ၅ ခုလုံး PASS · Drill Log #3 row | `docs/ops/backup-restore-production-drill.md` |
| 2 | Pilot data imports (products/customers/suppliers) | duplicate 0 · count ကိုက် | Import hub (Owner) |
| 3 | Opening-stock reconciliation | **diff = 0** (real data) | `/store/{slug}/pos/reconciliation` |
| 4 | Debt opening balances import | receivables total ကိုက် | Import hub → Debt tab |
| 5 | Real cashier workflow ၁ ပတ် (returns, debt, daily closing) | **issue 0** → pilot stable | runbook §3.1 #7 |

> ⚠️ Production mysqldump: MariaDB-safe flags — `mysqldump --single-transaction --quick --no-tablespaces | gzip` (**`--set-gtid-purged=OFF` မထည့်ရ** — MySQL 8 မှာသာ).

---

## 10. Rollback (ဘယ်လို ပြန်လှည့်မလဲ)

- **Code:** အရင် commit ကို `ALLOW_HOSTINGER_DEPLOY=true ./deploy-datapos.sh` နဲ့ ပြန် deploy (`.env`/vendor/storage က မထိဘူး — safe).
- **DB:** `backup-restore-production-drill.md` အတိုင်း dump → restore (drill က production မှာ run ပြီးသား ဆိုရင် ဒီ path က စမ်းပြီးသား).
- **Restore ပြီးရင်** ပျောက်သွားတဲ့ sales/returns တွေကို **ပုံမှန် POS flow နဲ့ပဲ ပြန်တင်** — tinker နဲ့ လက်နဲ့ ပြန်ထည့်တာ မလုပ်ရ (ledger integrity).

---

## Checklist — deploy နေ့အတွက် အမြန် scan

```
☐ P1–P5 local validation (886 tests · smoke · drill rehearsal · clean git · fresh build)
☐ hPanel: DB `datapos_commerce_prod` + dedicated user + grant (ဒီ DB တစ်ခုတည်း)
☐ Step 2: ALLOW_HOSTINGER_DEPLOY=true ./deploy-datapos.sh → DEPLOY_OK
☐ Step 3: server .env (APP_ENV/DB_*/SESSION_*/CACHE_STORE) + key:generate + config:cache
☐ Step 4: RUN_MIGRATIONS=true deploy → migrate:status 76/76 Ran
☐ Step 5: db:seed --class=ProductionSeeder (UatSeeder မလုပ်ရ)
☐ Step 6: tinker — Store datapos-mobile + owner/manager/staff + memberships + inventory:ensure-locations
☐ Step 7: /up 200 · headers · CSP quirk · build assets · storage link · login · session flags
☐ Step 8: Drill #3 production run → pilot imports → reconciliation diff 0 → debt import → ၁ ပတ် usage
```
