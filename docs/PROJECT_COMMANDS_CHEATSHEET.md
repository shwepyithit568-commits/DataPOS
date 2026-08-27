# DataPOS - Project Commands Cheatsheet

DataPOS ကို local development မှာ run/test/debug လုပ်ရန် အမြန်ကြည့် command စာရင်းပါ။

Project root: `D:\xmapp\htdocs\DataPOS`

> Current local setup: PHP PATH ထဲမှာ `D:\xmapp\php` ထည့်ပြီးသားဖြစ်လို့ `php artisan ...` ကို တိုက်ရိုက်သုံးနိုင်သည်။ Terminal အဟောင်းတွင်မရသေးပါက terminal အသစ်ပြန်ဖွင့်ပါ။

---

## 1. Daily Start

Project folder ထဲဝင်ရန်:

```powershell
cd D:\xmapp\htdocs\DataPOS
```

Laravel server ဖွင့်ရန်:

```powershell
php artisan serve --host=127.0.0.1 --port=8501
```

> Note: Local `.env` ထဲက `APP_URL` က `8502` ဖြစ်နေနိုင်သည်။ Docs standard က `8501` ဖြစ်သည်။ Browser link generation မှားနေလျှင် `.env` ကို `APP_URL=http://127.0.0.1:8501` ပြောင်းပြီး `php artisan config:clear` run ပါ။

Browser URL:

```text
http://127.0.0.1:8501
http://127.0.0.1:8501/store/datapos-mobile
http://127.0.0.1:8501/store/datapos-mobile/pos
http://127.0.0.1:8501/store/datapos-mobile/admin/dashboard
Phone URL ကိုလည်း IP အသစ်ဖြင့်ဖွင့်ပါ:

```text
http://192.168.10.165:8500/store/datapos-mobile
```

Vite CSS/JS watcher ဖွင့်ရန်:

```powershell
npm run dev
```

Production assets build လုပ်ရန်:

```powershell
npm run build
```

Composer script နဲ့ server, queue, logs, vite ကို တစ်ပြိုင်နက် run လုပ်ချင်လျှင်:

```powershell
composer run-script dev
```

---

## 2. PHP PATH Check

PHP command အလုပ်လုပ်မလုပ် စစ်ရန်:

```powershell
php -v
where.exe php
```

Expected path:

```text
D:\xmapp\php\php.exe
```

PATH ပျက်သွားလျှင် User PATH ထဲ ပြန်ထည့်ရန်:

```powershell
$phpDir = "D:\xmapp\php"
$userPath = [Environment]::GetEnvironmentVariable("Path", "User")
$parts = $userPath -split ";" | Where-Object { $_ }
if ($parts -notcontains $phpDir) {
  [Environment]::SetEnvironmentVariable("Path", (($parts + $phpDir) -join ";"), "User")
}
```

အခုဖွင့်ထားတဲ့ PowerShell terminal ထဲမှာ ချက်ချင်း `php` သုံးချင်လျှင်:

```powershell
$env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" + [Environment]::GetEnvironmentVariable("Path", "User")
```

Fallback အနေနဲ့ full path သုံးနိုင်သည်:

```powershell
D:\xmapp\php\php.exe artisan serve --host=127.0.0.1 --port=8501
```

---

## 3. Install / Refresh Dependencies

PHP packages install/update:

```powershell
composer install
```

Composer autoload refresh:

```powershell
composer dump-autoload
```

Node packages install:

```powershell
npm install
```

Clean install လိုအပ်မှသာ:

```powershell
npm ci
```

---

## 4. Database Commands

Local migration အသစ်များ run ရန်:

```powershell
php artisan migrate
```

Migration status စစ်ရန်:

```powershell
php artisan migrate:status
```

Local/UAT demo data ပြန်ထည့်ရန်:

```powershell
php artisan db:seed --class=UatSeeder
```

Local database ကို အကုန်ဖျက်ပြီး fresh ပြန်ဆောက်ရန်:

```powershell
php artisan migrate:fresh --seed --seeder=UatSeeder
```

> သတိ: `migrate:fresh` က database table/data အားလုံးဖျက်ပါတယ်။ Local/UAT test DB မှာသာ သုံးပါ။ Production/live server မှာ မသုံးပါ။

---

## 5. Cache Clear / Rebuild

အပြောင်းအလဲမပေါ်၊ route/view/config မှားနေသလိုဖြစ်လျှင်:

```powershell
php artisan optimize:clear
```

သီးခြား clear လုပ်ရန်:

```powershell
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

Production-ready cache ပြန်ဆောက်ရန်:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 6. Tests

Full test suite:

```powershell
php artisan test
```

Composer test script:

```powershell
composer test
```

Module အလိုက် targeted tests:

```powershell
php artisan test --filter=AuthenticationTest
php artisan test --filter=AdminSidebarNavigationUXTest
php artisan test --filter=AdminWarehouseAuthorizationTest
php artisan test --filter=ProductFormPurchaseFieldsTest
php artisan test --filter=PilotImportTest
php artisan test --filter=PosReportsRevampTest
php artisan test --filter=SalesAnalyticsTest
php artisan test --filter=EloadRegisterTest
php artisan test --filter=ProfitLossTest
```

POS core tests:

```powershell
php artisan test --filter=POS
php artisan test --filter=PosSaleTest
php artisan test --filter=CashierShiftTest
php artisan test --filter=PurchaseOrderTest
php artisan test --filter=InventoryLedgerTest
php artisan test --filter=InventoryReconciliationTest
```

UI/CSS/JS ပြင်ပြီးတိုင်း:

```powershell
npm run build
```

---

## 7. Laravel Useful Commands

Route စာရင်းကြည့်ရန်:

```powershell
php artisan route:list
php artisan route:list --path=store
php artisan route:list --path=admin
```

Interactive shell:

```powershell
php artisan tinker
```

App key generate လုပ်ရန်:

```powershell
php artisan key:generate
```

Storage link ပြန်ချိတ်ရန်:

```powershell
php artisan storage:link
```

Queue worker local test:

```powershell
php artisan queue:listen --tries=1 --timeout=0
```

Laravel logs live ကြည့်ရန်:

```powershell
php artisan pail --timeout=0
```

---

## 8. Default Local Test Accounts

Default password:

```text
password
```

| Role | Name | Login Phone | URL / Note |
|---|---|---|---|
| Platform Owner | Owner | `09100000001` | `/admin/dashboard` |
| Store Manager | Mg Hla | `09100000002` | `/store/datapos-mobile/admin/dashboard`, POS PIN `1234` |
| Staff / Cashier | Ko Kyaw | `09100000003` | `/store/datapos-mobile/pos` |
| Wholesale Customer | Daw Aye | `09100000004` | `/store/datapos-mobile/wholesale` |
| Retail Customer | Ma Su | `09100000006` | `/store/datapos-mobile` |

Login data မရှိတော့လျှင်:

```powershell
php artisan db:seed --class=UatSeeder
```

---

## 9. Common Problems

| Problem | Cause | Fix |
|---|---|---|
| `php` command မသိဘူး | PATH မဝင်သေးခြင်း၊ terminal အဟောင်းဖြစ်ခြင်း | terminal အသစ်ဖွင့်ပါ၊ `php -v` စစ်ပါ |
| `Port 8501 is already in use` | Server အဟောင်း run နေဆဲ | terminal အဟောင်းပိတ်ပါ၊ သို့မဟုတ် `--port=8502` ဖြင့် run ပါ |
| UI မပြောင်းသေးဘူး | Vite build/watch မလုပ်ထားခြင်း၊ view cache ကျန်ခြင်း | `npm run dev` သို့ `npm run build`, ပြီးလျှင် `php artisan optimize:clear` |
| Route 404/old route ဖြစ်နေတယ် | route cache ကျန်ခြင်း | `php artisan route:clear` သို့ `php artisan optimize:clear` |
| `.env` ပြင်ပြီး effect မရှိဘူး | config cache ကျန်ခြင်း | `php artisan config:clear` |
| Login user မရှိဘူး | UAT seeder မ run ထားခြင်း | `php artisan db:seed --class=UatSeeder` |
| Uploaded image မပေါ်ဘူး | storage symlink မရှိခြင်း | `php artisan storage:link` |

---

## 10. Production Safety Notes

Local development command နဲ့ production command မရောပါနှင့်။

Production/live server မှာ မလုပ်ရ:

```powershell
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan db:seed --class=UatSeeder
php artisan db:seed --class=DemoCatalogSeeder
```

Production deployment တွင်သာ သုံးရမည့် safe direction:

```powershell
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan production:create-admin --role=platform_owner
```

> DataPOS current state က local development အတွက်ဖြစ်သည်။ `deploy-datapos.sh` ကို မစစ်ဘဲ မ run ပါနှင့်။

---

## 11. Quick Workflow

Daily coding:

```powershell
cd D:\xmapp\htdocs\DataPOS
php artisan serve --host=127.0.0.1 --port=8501
npm run dev
```

Code change ပြီးစစ်ရန်:

```powershell
php artisan test
npm run build
```

Database change ပါလျှင်:

```powershell
php artisan migrate
php artisan test
```

Cache issue ဖြစ်လျှင်:

```powershell
php artisan optimize:clear
```
