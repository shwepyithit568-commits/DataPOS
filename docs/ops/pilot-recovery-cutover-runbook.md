# AlinnThit Pilot — Recovery & Cutover Runbook

> **ရည်ရွယ်ချက်:** Phase 2.5 (AlinnThit production pilot) အတွက် backup → restore → rollback → failover → cutover အကုန် ဘယ်လို လုပ်ရမယ်ဆိုတဲ့ လက်တွေ့ လုပ်ထုံးလုပ်နည်း။ Pilot မတည်မငြိမ်ခင် **ပြင်ပဖောက်သည်ကို မရောင်းရ** ဆိုတဲ့ စည်းကမ်း (ROADMAP.md Phase 2.5 exit criteria) ရဲ့ အခြေခံ။
>
> **အခြေအနေ:** Phase 2 (Online POS MVP, items 261–270) ပြီးပြီ · Phase 2.5 part 1 (pilot data import hub, item 271) ပြီးပြီ · ဒီ runbook က Phase 2.5 ရဲ့ ကျန် "Backup & restore test" + "Written recovery/cutover runbook" items အတွက်။
>
> **သက်ဆိုင်သူ:** Project Owner (ဆရာကြီး) — မလုပ်ခင် ဒီဖိုင်ကို သေချာ ဖတ်ပြီး အတည်ပြုပါ။

---

## ၁။ Production ပတ်ဝန်းကျင် (ဒီ runbook ရဲ့ ယူဆချက်များ)

| အချက် | တန်ဖိုး | ရင်းမြစ် |
|---|---|---|
| Hosting | Hostinger — datapos.com | `docs/archive/deployment-runbook.md` |
| SSH | `ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP>` | `docs/archive/deployment-runbook.md` |
| App path | `/home/<HOSTINGER_USER>/domains/datapos.com/laravel_app` | split layout |
| Webroot | `/home/<HOSTINGER_USER>/domains/datapos.com/public_html` | split layout |
| Deploy | `./deploy-datapos.sh` (code) · `RUN_MIGRATIONS=true ./deploy-datapos.sh` (code+migrations) | `docs/archive/deployment-runbook.md` |
| DB | MySQL (`datapos_db`) — mysqldump backup | `docs/ops/DEPLOYMENT.md` |
| Storage | `storage/app/public` (products, banners, uploads) — webroot နဲ့ symlink | `docs/ops/DEPLOYMENT.md` |
| Pilot store slug | `datapos-mobile` (canonical) | `docs/archive/deployment-runbook.md` |

### POS module ရဲ့ မဖောက်ဖျက်နိုင်တဲ့ စည်းမျဉ်း (ဒီ runbook ကို သက်ရောက်တဲ့ဟာ)

1. **Ledger movements တွေ immutable** — `inventory_movements` ကို update/delete လုပ်လို့မရ (model boot hook က ကာကွယ်ထား)။ မှားရင် **reversal movement** နဲ့သာ ပြင်ရမယ်။
2. **Posted sales / returns / shifts / closings တွေ immutable** — DB row ကို လက်နဲ့ ပြင်တာ မဟုတ်ဘဲ ပုံမှန် flow (refund, reversal, new closing) နဲ့သာ ပြင်ရမယ်။
3. **Idempotency** — `client_transaction_id` unique ဖြစ်လို့ duplicate retry က safe — ပြန်ကြိုးစားရင် duplicate မဖြစ်။
4. **`inventory:reconcile`** က ledger ကနေ balances ကို rebuild/verify လုပ်ပေးတဲ့ တရားဝင်နည်း — ကိုက်ညီမှု ပျက်ရင် ဒါကို သုံး။

> ⚠️ **DB row တွေကို တိုက်ရိုက် UPDATE/DELETE လုပ်တာ ဘယ်တော့မှ မလုပ်ရ** — ledger immutability နဲ့ audit trail ကို ချိုးဖျက်တာ။ Rollback ဆိုတာ (a) backup restore (အကုန်ပြန်) ဒါမှမဟုတ် (b) business-level reversal (ပုံမှန် flow နဲ့) သာ။

---

## ၂။ Backup (ပုံမှန် + Pilot အတွက် အထူး)

### ၂.၁ နေ့စဉ် — Database (MySQL)

```bash
# Production server ပေါ်မှာ (SSH):
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> \
  "mysqldump -u <DB_USER> -p<DB_PASS> datapos_db | gzip > <backup-root>/db/datapos_$(date +%Y%m%d_%H%M%S).sql.gz"
```

- Retention: နောက်ဆုံး **30 ရက်** (ပုံမှန် policy — `docs/ops/DEPLOYMENT.md`).
- **Pilot ကာလအတွင်း ထပ်ဆောင်း:** shift မပိတ်ခင် + daily closing approve မလုပ်ခင် backup ယူပါ (ပြန်လှည့်ရင် တစ်နေ့လုံး ပျက်မှာ မဟုတ်ဘဲ closing အကြိုအထိ ပြန်ရအောင်)။

### ၂.၂ အပတ်စဉ် — Storage (uploaded files)

```powershell
# Windows (robocopy mirror) — docs/ops/DEPLOYMENT.md အတိုင်း:
robocopy "<project-root>\storage\app\public" "<backup-root>\storage" /MIR /Z
```

POS ကိုယ်တိုင် storage မသုံးပေမယ့် product images / pilot import files တွေ ပါ — ဒါကြောင့် ပါဝင်ရမယ်။

### ၂.၃ ပြောင်းလဲမှု တိုင်း — `.env`

`.env` (DB credentials, APP_KEY, MAIL_PASSWORD) ကို encrypted နေရာမှာ သိမ်း — **git ထဲ ဘယ်တော့မှ မထည့်ရ** (scrub history သင်ခန်းစာ — `docs/ops/DEPLOYMENT.md` ကြည့်)။

### ၂.၄ Pilot-တိကျသော Backup စစ်ဆေးစာရင်း (Backup & Restore Test — Phase 2.5 item)

ပြင်ပဖောက်သည်ကို မရောင်းခင် **တစ်ကြိမ်တည်း မလုပ်ဘဲ** ဒီ drill ကို အနည်းဆုံး **၂ ကြိမ်** လုပ်ပြီး မှတ်တမ်းတင်ပါ:

1. **Backup ယူ** — ၂.၁ (DB) + ၂.၂ (storage) + ၂.၃ (.env)
2. **Test DB ပေါ်မှာ restore** — production ကို မထိဘဲ:
   ```bash
   gunzip < datapos_YYYYMMDD_HHMMSS.sql.gz | mysql -u <DB_USER> -p datapos_db_test
   ```
3. **Restore ပြီးနောက် POS integrity စစ်** (test DB ပေါ်မှာ):
   ```bash
   # Ledger = balance ကိုက်ရဲ့လား (--verify က ပြောင်းလဲမှု မလုပ်ဘဲ စစ်ပဲ):
   php artisan inventory:reconcile --verify
   # Shift/return/closing/sale statuses မပျက်စေရ:
   php artisan tinker --execute="
     echo 'sales=' . App\POS\Models\PosSale::count()
        . ' returns=' . App\POS\Models\PosReturn::count()
        . ' open_shifts=' . App\POS\Models\CashierShift::where('status','open')->count()
        . ' movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;"
   ```
4. **Shift ဖွင့် → sale post → return → close** — restore ပြီးသား test DB မှာ flow ၄ ခု အလုပ်လုပ်ကြောင်း စမ်း (restore က POS state ကို မချိုးဘူးဆိုတဲ့ သက်သေ)။
5. **ပြီးရင် test DB ကို ဖျက်** — `DROP DATABASE datapos_db_test;`

**အောင်မြင်မှု စံ:** `reconcile --verify` က diff 0 · flow ၄ ခု pass · open shift count မှန်။

### ၂.၅ Drill #2 — Production (MySQL) — step-by-step commands

> **ဘာကြောင့်:** Drill #1 က local SQLite ပေါ်မှာ run ခဲ့တယ် — SQLite ≠ MySQL မို့ production server ပေါ်မှာ **တကယ့် MySQL** နဲ့ `backup → restore to datapos_db_test → reconcile → flow test` ကို ထပ်စစ်ရမယ် (၂.၄ checklist ရဲ့ ၂ ကြိမ်မြောက် drill)။ အောက်က command တွေကို **local machine ကနေ** SSH ဖြင့် run ပြီး ရလဒ်ကို §2.6 Drill Log မှာ မှတ်တမ်းတင်ပါ။
>
> ⚠️ **မလုပ်ခင် အရင်ဆုံး §2.5A local dry-run ကို run ပါ** — ဒီ flow-test script ကို **local MySQL** (`datapos_db_test`) မှာ pass ဖြစ်မှသာ ဒီ production drill ကို ဆက်လုပ်ပါ။ (Drill #1 = SQLite · §2.5A = local MySQL · ဒီအခန်း = production MySQL — တစ်ဆင့်ချင်း တက်ရတယ်။)
>
> ⚠️ **ဒီ project က Hostinger မှာ မတင်ရသေးပါ (2026-08-13)** — production server မရှိသေးလို့ ဒီ drill ရဲ့ step ၇ ခုလုံးကို **localhost** ပေါ်မှာ local MySQL `datapos_db` (live stand-in) → `datapos_db_test` round-trip အနေနဲ့ run ပြီး §2.6 Drill Log မှာ မှတ်တမ်းတင်ထားတယ် (mysqldump → restore → reconcile → flow → cleanup)။ **Hostinger မှာ deploy ပြီးမှ** production MySQL နဲ့ ဒီအတိုင်း ထပ်စစ်ရန် ကျန်။

**Pre-flight (မစခင် ပြီးထားရမယ့်ဟာ):**

0. **Local dry-run pass** — §2.5A ကို run ပြီး flow-test script က local MySQL မှာ pass ဖြစ်ပြီးပြီ (Drill Log §2.6)။
1. **Test DB ဖန်တီး (Hostinger hPanel → Databases):** `datapos_db_test` အသစ်တည်ပြီး production `.env` ရဲ့ MySQL user (`<DB_USER>`) ကို ဒီ DB ပေါ်မှာ **full privileges** ပေးပါ။ (Hostinger shared hosting မှာ user က database တစ်ခုချင်းစီအတွက် သတ်မှတ်ထားတာ — grant မပေးရင် restore က `Access denied` ဖြစ်မယ်။)
2. **Cashier user ရှိရမယ်** — `datapos-mobile` store မှာ `staff` / `store_manager` role နဲ့ user ရှိရမယ် (မရှိရင်: `php artisan production:create-admin --role=store_manager --store=datapos-mobile` — ဒါက live DB ကို ထိလို့ မလုပ်ခင် သေချာစဉ်းစားပါ; မရှိရင် flow test က မဆွဲနိုင်)။
3. **Production `.env` ထဲက real values** ယူထားပါ — `<DB_USER>`, `<DB_PASS>`, `<DB_NAME>` (= `DB_DATABASE` တန်ဖိုး — ဒီ runbook ထဲမှာ `datapos_db` လို့ အတိုသုံးထား):

   ```bash
   ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> \
     "grep -E '^(DB_DATABASE|DB_USERNAME)=' /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app/.env"
   # DB_PASSWORD ကိုတော့ .env ထဲက တိုက်ရိုက် ကြည့်ပြီး <DB_PASS> နေရာမှာ ထည့်သုံးပါ
   ```

**Step 1 — Baseline (live DB — drill မစခင် အခြေအနေ):**

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app

echo '== Step 1: baseline (live DB) =='
php artisan inventory:reconcile --verify
php artisan tinker <<'PHP'
echo 'sales=' . App\POS\Models\PosSale::count()
   . ' returns=' . App\POS\Models\PosReturn::count()
   . ' open_shifts=' . App\POS\Models\CashierShift::where('status','open')->count()
   . ' movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;
PHP
REMOTE
```

> မျှော်လင့်ချက်: `✅ Ledger and balances are consistent.` (diff 0) — ဒီ counts တွေကို Step 4 / Step 7 နဲ့ နှိုင်းယှဉ်ဖို့ ချရေးထားပါ။

**Step 2 — Backup (production DB — ၂.၁ အတိုင်း):**

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
mkdir -p ~/backups/drill2
mysqldump -u <DB_USER> -p<DB_PASS> <DB_NAME> | gzip > ~/backups/drill2/datapos_$(date +%Y%m%d_%H%M%S).sql.gz
ls -lh ~/backups/drill2/
REMOTE
```

> Output ထဲက `.sql.gz` filename ကို မှတ်ထားပါ (Step 3 က နောက်ဆုံး dump ကို auto-ရွေးပါတယ်)။ Storage (၂.၂) နဲ့ `.env` (၂.၃) backup က ၂.၄ checklist အတိုင်း လုပ်ပြီးသား ဖြစ်ရမယ် — ဒီ drill က DB restore ကို အဓိက စစ်တာ။

**Step 3 — Restore → `datapos_db_test` (production ကို မထိ):**

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
DUMP=$(ls -t ~/backups/drill2/datapos_*.sql.gz | head -1)
echo "Restoring: $DUMP"
gunzip < "$DUMP" | mysql -u <DB_USER> -p<DB_PASS> datapos_db_test

echo '-- table count (live vs test) — နှစ်ခု တူရမယ်:'
mysql -u <DB_USER> -p<DB_PASS> -N -e "SELECT 'live', COUNT(*) FROM information_schema.tables WHERE table_schema='<DB_NAME>' UNION ALL SELECT 'test', COUNT(*) FROM information_schema.tables WHERE table_schema='datapos_db_test';"
REMOTE
```

**Step 4 — Verify restored DB (reconcile + counts — test DB ပေါ်မှာ):**

> ⚠️ Production မှာ `config:cache` ရှိနေလို့ `DB_DATABASE=datapos_db_test php artisan ...` ဆိုတဲ့ env override က **အလုပ်မလုပ်ဘူး** — ဒါကြောင့် runtime မှာ connection ကို ပြန်ညွှန်တဲ့ အောက်က tinker pattern ကို သုံးရတယ် (config cache ကို မဖျက်ဘူး):

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app

php artisan tinker <<'PHP'
// Point this tinker session at the TEST database (config:cache-safe):
config(['database.connections.mysql.database' => 'datapos_db_test']);
DB::purge('mysql');

echo 'sales=' . App\POS\Models\PosSale::count()
   . ' returns=' . App\POS\Models\PosReturn::count()
   . ' open_shifts=' . App\POS\Models\CashierShift::where('status','open')->count()
   . ' movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;

Artisan::call('inventory:reconcile --verify');
echo Artisan::output();
PHP
REMOTE
```

> မျှော်လင့်ချက်: **counts = Step 1 နဲ့ အတူတူ** · `✅ Ledger and balances are consistent.` (diff 0) — restore က data မပျက်စေဘူးဆိုတဲ့ သက်သေ။

**Step 5 — Flow test (test DB ပေါ်မှာ — shift open → sale post → return → shift close):**

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app

php artisan tinker <<'PHP'
config(['database.connections.mysql.database' => 'datapos_db_test']);
DB::purge('mysql');

$store = App\Models\Store::where('slug', 'datapos-mobile')->firstOrFail();
$cashier = App\Models\User::whereHas('stores', function ($q) use ($store) {
    $q->where('stores.id', $store->id)->whereIn('store_user.role', ['staff', 'store_manager']);
})->firstOrFail();

// Default warehouse မှာ stock ရှိတဲ့ base product (variant မဟုတ်) ကို ရွေး —
// sale ရဲ့ movement path (variant=null → sentinel 0) နဲ့ ကိုက်ညီအောင်:
$inv = app(App\POS\Services\InventoryService::class);
$wh = $inv->defaultWarehouseId($store->id);
$balance = App\POS\Models\InventoryBalance::where('store_id', $store->id)
    ->where('warehouse_id', $wh)
    ->where('product_variant_id', 0)
    ->where('quantity_on_hand', '>', 0)->firstOrFail();
$product = $balance->product;
if (! $product || (float) $product->retail_price <= 0) {
    throw new RuntimeException('No product with stock + price found for the flow test.');
}

$shifts  = app(App\POS\Services\CashierShiftService::class);
$sales   = app(App\POS\Services\PosSaleService::class);
$returns = app(App\POS\Services\PosReturnService::class);

// 1) Shift open
$register = 'Drill2-' . now()->format('Ymd-His');
$shift = $shifts->openShift($store, ['register_name' => $register, 'opening_cash' => '100000'], $cashier);

// 2) Sale post (cash, 1 unit)
$sale = $sales->post(
    $store,
    [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => '1']],
    [['method' => 'cash', 'amount' => (string) $product->retail_price]],
    $cashier,
    $shift
);

// 3) Full refund (controller-style ctid — idempotent retry အတွက် လိုအပ်)
$ctid = 'pos_return:drill2:' . now()->format('YmdHis');
$return = $returns->post(
    $store, $sale,
    [['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '1']],
    [['method' => 'cash', 'amount' => (string) $sale->total]],
    $cashier, $shift, $ctid
);

// 3b) Idempotent retry — same ctid ပြန်ခေါ် → same return id ပြန်ရမယ် (Drill #1 5b အတိုင်း)
$retry = $returns->post(
    $store, $sale,
    [['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '1']],
    [['method' => 'cash', 'amount' => (string) $sale->total]],
    $cashier, $shift, $ctid
);

// 4) Shift close — expected = opening (sale cash in ↔ refund cash out → net 0)
$shift->refresh();
$closed = $shifts->closeShift($shift, ['actual_closing_amount' => (string) $shift->opening_cash], $cashier);

echo 'REGISTER=' . $register . PHP_EOL;
echo 'SALE=' . $sale->receipt_number . ' (' . $sale->total . ' Ks)' . PHP_EOL;
echo 'RETURN=' . $return->refund_number . ' (' . $return->total . ' Ks)' . PHP_EOL;
echo 'RETRY_SAME=' . ($retry->id === $return->id ? 'yes' : 'no') . PHP_EOL;
echo 'CLOSED expected=' . $closed->expected_closing_amount
   . ' actual=' . $closed->actual_closing_amount
   . ' diff=' . $closed->difference . PHP_EOL;
PHP
REMOTE
```

> မျှော်လင့်ချက်: `SALE=RCP-…` · `RETURN=RET-…` · `RETRY_SAME=yes` · `CLOSED … diff=0` — restore ပြီးသား DB မှာ flow ၄ ခု (open → sale → return → close) အလုပ်လုပ်ကြောင်း သက်သေ။ Store ထဲမှာ stock ရှိတဲ့ base product မရှိရင် error တက်မယ် — ဒါ drill fail မဟုတ်၊ data condition; စစ်ပါ။

**Step 6 — Post-flow reconcile (test DB — movements +2 ဖြစ်ပြီး diff 0):**

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app
php artisan tinker <<'PHP'
config(['database.connections.mysql.database' => 'datapos_db_test']);
DB::purge('mysql');
echo 'movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;
Artisan::call('inventory:reconcile --verify');
echo Artisan::output();
PHP
REMOTE
```

> မျှော်လင့်ချက်: `movements` = baseline + 2 (sale + return) · `✅ Ledger and balances are consistent.` (diff 0)။

**Step 7 — Cleanup (test DB ဖျက် + live ပြန်စစ်):**

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app

mysql -u <DB_USER> -p<DB_PASS> -e "DROP DATABASE datapos_db_test;"
echo 'DROP ok — test DB ဖျက်ပြီး'

echo '== live DB ပြန်စစ် (Step 1 counts အတိုင်း ရှိရမယ်) =='
php artisan inventory:reconcile --verify
php artisan tinker <<'PHP'
echo 'sales=' . App\POS\Models\PosSale::count()
   . ' returns=' . App\POS\Models\PosReturn::count()
   . ' open_shifts=' . App\POS\Models\CashierShift::where('status','open')->count()
   . ' movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;
PHP
REMOTE
```

> (hPanel ကနေလည်း `datapos_db_test` ကို ဖျက်လို့ရတယ် — CLI grant အပြည့်မရှိရင် hPanel သုံးပါ။) Drill backup (`~/backups/drill2/`) ကို log မှတ်ပြီးမှ ဖျက်နိုင်တယ်။

**အောင်မြင်မှု စံ (Drill #2):** Step 4/6 မှာ `reconcile --verify` diff 0 · Step 5 flow ၄ ခု pass + `RETRY_SAME=yes` + close diff 0 · Step 7 ပြီးနောက် live DB counts = Step 1 baseline အတိုင်း · test DB ဖျက်ပြီး။ ရလဒ်ကို §2.6 Drill Log မှာ မှတ်တမ်းတင်ပါ။

### ၂.၅A Local dry-run — Drill #2 flow-test script ကို local MySQL မှာ စမ်းနည်း (production မထိခင်)

> **ဘာကြောင့်:** Drill #1 က local SQLite ပေါ်မှာ run ခဲ့တယ် — §2.5 Drill #2 က production MySQL ပေါ်မှာ run မယ်။ ဒါပေမဲ့ **production ကို မထိခင်** ဒီ flow-test script (shift open → sale → return → idempotent retry → close) ကို **local MySQL** (`datapos_db_test`) ပေါ်မှာ အရင် စမ်းနိုင်တယ် — script ရဲ့ syntax/service API/MySQL semantics မှားရင် production ရောက်ခင် local မှာ ဖမ်းမိတယ်။
>
> ⚠️ ဒါက **rehearsal** ပဲ — **Drill #2 (production) ကို မလဲစားဘူး**။ Local က MariaDB (XAMPP) ဖြစ်ပြီး production က MySQL (Hostinger server version) မို့ နှစ်ခုလုံး လုပ်ရမယ်။
>
> ဒီ command တွေက **local machine ပေါ်မှာပဲ** run ရမယ် — `DROP DATABASE datapos_db_test` ပါလို့ production မှာ ဘယ်တော့မှ မလုပ်ရ။

**Pre-flight (မစခင် ပြီးထားရမယ့်ဟာ):**

1. **XAMPP MySQL (MariaDB) running** — port 3306 မှာ listener ရှိရမယ် (`netstat -ano | findstr :3306` နဲ့ စစ်)။
2. **MySQL client + PHP** — `D:/xmapp/mysql/bin/mysql.exe` နဲ့ `D:/xmapp/php/php.exe` (ဒီ project README အတိုင်း) — ကိုယ့် XAMPP path နဲ့ ကိုက်အောင် ပြင်ပါ။ `php -m` မှာ `pdo_mysql` ပါရမယ် (XAMPP မှာ default ပါ)။
3. **Local root စကားဝှက်** — XAMPP default က root password မရှိ (`mysql -u root` အလုပ်လုပ်)။ ကိုယ့် machine မှာ password ရှိရင် mysql command တွေမှာ `-p<pass>` ထည့်ပြီး artisan command တွေမှာ `DB_PASSWORD=<pass>` ထည့်ပါ။
4. **Git Bash** — ဒီ runbook ရဲ့ command တွေ bash syntax (heredoc/env prefix) သုံးလို့ Git Bash (သို့) WSL မှာ run ပါ — CMD/PowerShell မဟုတ်ဘူး။

> ⚠️ **env prefix မဖြစ်မနေ လိုအပ်တဲ့ အကြောင်း:** local `.env` က SQLite ကို default ထားလို့ — အောက်က artisan command တိုင်းကို `DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= DB_DATABASE=datapos_db_test` prefix နဲ့ run ရတယ်။ ဒါက session တစ်ခုလုံးကို local MySQL `datapos_db_test` ပေါ် ညွှန်တယ် (local မှာ config cache မရှိလို့ env override က အလုပ်လုပ်)။ Production မှာတော့ config:cache ရှိလို့ ဒီ prefix အစား Step 5 ထဲက `config([...]); DB::purge('mysql')` pattern ကို သုံးရတယ် — အဲဒီ line တွေက local မှာလည်း ထည့်ထားလို့ ရတယ် (နှစ်နေရာလုံး တူညီတဲ့ script ဖြစ်ဖို့)။

**Step 1 — Local `datapos_db_test` ဖန်တီး (အသစ်/ပြန်စ):**

```bash
D:/xmapp/mysql/bin/mysql.exe -u root -e "DROP DATABASE IF EXISTS datapos_db_test; CREATE DATABASE datapos_db_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Step 2 — Schema migrate (test DB ပေါ်မှာ):**

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= DB_DATABASE=datapos_db_test D:/xmapp/php/php.exe artisan migrate --force
```

> မျှော်လင့်ချက်: migrations အကုန် `DONE` — test DB မှာ table တွေ ဖြစ်လာမယ်။

**Step 3 — Drill data seed (store → cashier → product → opening stock):**

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= DB_DATABASE=datapos_db_test D:/xmapp/php/php.exe artisan tinker <<'PHP'
$store = App\Models\Store::create(['name' => 'DataPOS', 'slug' => 'datapos-mobile', 'is_active' => true]);
$cashier = App\Models\User::create(['name' => 'Dry-run Cashier', 'phone' => '09100000099', 'password' => bcrypt('password'), 'role' => 'customer']);
$cashier->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);
$product = App\Models\Product::create([
    'store_id' => $store->id,
    'sku' => 'DRY-RUN-001',
    'name' => 'Dry Run Product',
    'slug' => 'dry-run-product',
    'retail_price' => 15000,
    'wholesale_price' => 9000,
    'stock_status' => 'in_stock',
]);
app(App\POS\Services\InventoryService::class)->postMovement([
    'store_id' => $store->id,
    'product_id' => $product->id,
    'movement_type' => 'opening_balance',
    'quantity_delta' => '10',
    'unit_cost' => '8000',
    'source_type' => 'opening_balance',
    'client_transaction_id' => 'drill2-dryrun:' . now()->format('YmdHis'),
    'occurred_at' => now(),
]);
echo 'SEED store=' . $store->slug . ' cashier=' . $cashier->phone . ' product=' . $product->sku . ' retail=' . $product->retail_price . ' qty=10' . PHP_EOL;
PHP
```

> မျှော်လင့်ချက်: `SEED store=datapos-mobile cashier=09100000099 product=DRY-RUN-001 retail=15000.00 qty=10` — ဒါတွေက §2.5 Step 5 ရဲ့ flow-test script က လိုအပ်တဲ့ အခြေအနေ (store + cashier + stock ရှိတဲ့ base product) ကို ဖြည့်ပေးတယ်။

**Step 4 — Baseline verify (test DB):**

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= DB_DATABASE=datapos_db_test D:/xmapp/php/php.exe artisan tinker <<'PHP'
echo 'sales=' . App\POS\Models\PosSale::count()
   . ' returns=' . App\POS\Models\PosReturn::count()
   . ' open_shifts=' . App\POS\Models\CashierShift::where('status','open')->count()
   . ' movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;
Artisan::call('inventory:reconcile --verify');
echo Artisan::output();
PHP
```

> မျှော်လင့်ချက်: `sales=0 returns=0 open_shifts=0 movements=1` · `✅ Ledger and balances are consistent.` (diff 0 — opening balance ၁ ခုပဲ ရှိ)။

**Step 5 — Flow test (test DB — §2.5 Step 5 နဲ့ တူညီတဲ့ script):**

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= DB_DATABASE=datapos_db_test D:/xmapp/php/php.exe artisan tinker <<'PHP'
config(['database.connections.mysql.database' => 'datapos_db_test']);
DB::purge('mysql');

$store = App\Models\Store::where('slug', 'datapos-mobile')->firstOrFail();
$cashier = App\Models\User::whereHas('stores', function ($q) use ($store) {
    $q->where('stores.id', $store->id)->whereIn('store_user.role', ['staff', 'store_manager']);
})->firstOrFail();

// Default warehouse မှာ stock ရှိတဲ့ base product (variant မဟုတ်) ကို ရွေး —
// sale ရဲ့ movement path (variant=null → sentinel 0) နဲ့ ကိုက်ညီအောင်:
$inv = app(App\POS\Services\InventoryService::class);
$wh = $inv->defaultWarehouseId($store->id);
$balance = App\POS\Models\InventoryBalance::where('store_id', $store->id)
    ->where('warehouse_id', $wh)
    ->where('product_variant_id', 0)
    ->where('quantity_on_hand', '>', 0)->firstOrFail();
$product = $balance->product;
if (! $product || (float) $product->retail_price <= 0) {
    throw new RuntimeException('No product with stock + price found for the flow test.');
}

$shifts  = app(App\POS\Services\CashierShiftService::class);
$sales   = app(App\POS\Services\PosSaleService::class);
$returns = app(App\POS\Services\PosReturnService::class);

// 1) Shift open
$register = 'Drill2-' . now()->format('Ymd-His');
$shift = $shifts->openShift($store, ['register_name' => $register, 'opening_cash' => '100000'], $cashier);

// 2) Sale post (cash, 1 unit)
$sale = $sales->post(
    $store,
    [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => '1']],
    [['method' => 'cash', 'amount' => (string) $product->retail_price]],
    $cashier,
    $shift
);

// 3) Full refund (controller-style ctid — idempotent retry အတွက် လိုအပ်)
$ctid = 'pos_return:drill2:' . now()->format('YmdHis');
$return = $returns->post(
    $store, $sale,
    [['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '1']],
    [['method' => 'cash', 'amount' => (string) $sale->total]],
    $cashier, $shift, $ctid
);

// 3b) Idempotent retry — same ctid ပြန်ခေါ် → same return id ပြန်ရမယ် (Drill #1 5b အတိုင်း)
$retry = $returns->post(
    $store, $sale,
    [['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '1']],
    [['method' => 'cash', 'amount' => (string) $sale->total]],
    $cashier, $shift, $ctid
);

// 4) Shift close — expected = opening (sale cash in ↔ refund cash out → net 0)
$shift->refresh();
$closed = $shifts->closeShift($shift, ['actual_closing_amount' => (string) $shift->opening_cash], $cashier);

echo 'REGISTER=' . $register . PHP_EOL;
echo 'SALE=' . $sale->receipt_number . ' (' . $sale->total . ' Ks)' . PHP_EOL;
echo 'RETURN=' . $return->refund_number . ' (' . $return->total . ' Ks)' . PHP_EOL;
echo 'RETRY_SAME=' . ($retry->id === $return->id ? 'yes' : 'no') . PHP_EOL;
echo 'CLOSED expected=' . $closed->expected_closing_amount
   . ' actual=' . $closed->actual_closing_amount
   . ' diff=' . $closed->difference . PHP_EOL;
PHP
```

> မျှော်လင့်ချက်: `SALE=RCP-…` · `RETURN=RET-…` · `RETRY_SAME=yes` · `CLOSED … diff=0` — **§2.5 Step 5 ရဲ့ script ကို ဘာမှ မပြင်ဘဲ** ဒီကနေ copy ကူးပြီး production မှာ run လို့ရတယ်ဆိုတဲ့ သက်သေ။

**Step 6 — Post-flow reconcile (test DB):**

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= DB_DATABASE=datapos_db_test D:/xmapp/php/php.exe artisan tinker <<'PHP'
echo 'movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;
Artisan::call('inventory:reconcile --verify');
echo Artisan::output();
PHP
```

> မျှော်လင့်ချက်: `movements=3` (opening + sale + return) · `✅ Ledger and balances are consistent.` (diff 0)။

**Step 7 — Cleanup (test DB ဖျက် + local dev DB မပျက်ကြောင်း ပြန်စစ်):**

```bash
D:/xmapp/mysql/bin/mysql.exe -u root -e "DROP DATABASE datapos_db_test;"
D:/xmapp/php/php.exe artisan tinker --execute="echo 'default=' . config('database.default') . ' sales=' . App\POS\Models\PosSale::count() . ' movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;"
```

> မျှော်လင့်ချက်: `default=sqlite` · counts = Drill #1 baseline (sales=3 · movements=33) — local dev SQLite ကို မထိခဲ့ဘူးဆိုတဲ့ သက်သေ။

**အောင်မြင်မှု စံ (dry-run):** Step 4/6 `reconcile --verify` diff 0 · Step 5 flow ၄ ခု pass + `RETRY_SAME=yes` + close diff 0 · Step 7 ပြီးနောက် local SQLite = unchanged · test DB ဖျက်ပြီး။ ရလဒ်ကို §2.6 Drill Log မှာ မှတ်တမ်းတင်ပါ — ပြီးမှသာ §2.5 Drill #2 (production) ကို ဆက်လုပ်ပါ။

### ၂.၆ Drill မှတ်တမ်း (Drill Log)

#### Drill #1 — 2026-08-13 13:42 (local SQLite, adapted from the MySQL steps)

**ပတ်ဝန်းကျင်:** Local dev DB (`database/database.sqlite`, SQLite, journal_mode=`delete` — plain file copy = consistent snapshot)။ Production က MySQL ဖြစ်လို့ mysqldump step တွေကို SQLite copy semantics နဲ့ အစားထိုးပြီး run ခဲ့တယ်။

| # | အဆင့် | ရလဒ် |
|---|---|---|
| 1 | Baseline (live DB) — counts + `reconcile --verify` | sales=3 · returns=2 · shifts=2 · open=1 · movements=33 · balances=25 · **diff 0** ✅ |
| 2 | Backup — `cp database.sqlite /tmp/pilot-drill/backup/database_20260813_134207.sqlite` + `.env` copy | **SHA256 match** (`b0293225…`) — byte-identical ✅ |
| 3 | Restore — backup → `database/database-test-drill.sqlite` | SHA match ✅ |
| 4 | Verify restored DB — `reconcile --verify` + counts (via `DB_DATABASE=` override) | **diff 0** · counts = live အတိုင်း (sales=3 · open=1 · movements=33) ✅ |
| 5 | Flow test — shift open → sale post → full refund → shift close (service layer, auth-as-cashier) | `#3 Drill Register` (opening 100,000) → **RCP-20260813-0002** (15,000) → **RET-20260813-0002** (15,000, ctid `pos_return:4:4:…`) → closed expected=100,000 **diff 0** ✅ |
| 5b | Idempotent retry — same ctid နဲ့ return ပြန်ခေါ် | **Same return id ပြန်ရတယ် (id=3 === id=3, no duplicate)** ✅ |
| 6 | Post-flow reconcile (test DB) | movements 33→35 (sale+return) · **diff 0** ✅ |
| 7 | Cleanup + live DB ပြန်စစ် | test DB ဖျက် · live DB counts က baseline အတိုင်း (sales=3 · open=1 · movements=33) · **diff 0** ✅ |

**သင်ခန်းစာ (harness):** `PosReturnService::post()` က client_transaction_id ကို **caller ကနေ လက်ခံ**တယ် — HTTP controller က generate လုပ်ပေးတယ် (line 86: `pos_return:{store}:{sale}:{YmdHis}:{rand}`)။ Service ကို ctid မပါဘဲ တိုက်ရိုက်ခေါ်ရင် idempotent-retry check က skip ဖြစ်ပြီး retry က "already fully refunded" exception တက်တယ် — **ဒါ bug မဟုတ်**၊ offline sync retry တွေက ဒီ ctid ကို controller ကနေရလို့ မှန်မှန် အလုပ်လုပ်တယ်။ Service-level test ရေးရင် controller ပုံစံအတိုင်း ctid ထည့်ပေးရမယ်။

**Drill အောင်မြင်မှု စံ:** ✅ ပြည့်မီ — restore ပြီးသား DB မှာ reconcile diff 0 + flow ၄ ခု pass + open-shift state မှန်။ **Production (MySQL) မှာ drill ၂ ကြိမ်မြောက် ပြန်လုပ်ရန် ကျန်** — SQLite ≠ MySQL မို့ production server ပေါ်မှာ `mysqldump → restore to datapos_db_test → reconcile → flow` ကို ထပ်စစ်ပါ။

#### Drill #2 Dry-run — 2026-08-13 19:42 (local MySQL · MariaDB 10.4.32, XAMPP)

**ပတ်ဝန်းကျင်:** Local XAMPP MySQL (MariaDB 10.4.32, `127.0.0.1:3306`, root/empty password) — `datapos_db_test` အသစ်တည်ပြီး migrate → seed → flow test (§2.5A အတိုင်း)။ Production ကို မထိဘူး။

| # | အဆင့် | ရလဒ် |
|---|---|---|
| 1 | `datapos_db_test` create (local MySQL) | ✅ |
| 2 | Migrate schema → test DB | ✅ migrations အကုန် DONE |
| 3 | Seed — store `datapos-mobile` + store_manager cashier + product (DRY-RUN-001, 15,000 Ks) + opening stock 10 | ✅ |
| 4 | Baseline (test DB) — counts + `reconcile --verify` | sales=0 · returns=0 · open=0 · movements=1 · **diff 0** ✅ |
| 5 | Flow test (test DB) — shift open → sale → full refund → close | RCP-20260813-0001 (15,000) → RET-20260813-0001 (15,000) → close expected=actual=100,000 · **diff 0** ✅ |
| 5b | Idempotent retry — same ctid ပြန်ခေါ် | **RETRY_SAME=yes** ✅ |
| 6 | Post-flow reconcile (test DB) | movements 1→3 (opening+sale+return) · **diff 0** ✅ |
| 7 | Cleanup — `DROP DATABASE datapos_db_test` + local SQLite ပြန်စစ် | SQLite counts = Drill #1 baseline (sales=3 · movements=33) · **unchanged** ✅ |

**သင်ခန်းစာ (harness):** Local `.env` က SQLite default မို့ artisan command တိုင်းကို `DB_CONNECTION=mysql … DB_DATABASE=datapos_db_test` env prefix နဲ့ run ရတယ် (local မှာ config cache မရှိလို့ env override အလုပ်လုပ်)။ Flow-test script ထဲက `config([...]); DB::purge('mysql')` line တွေက production ရဲ့ config:cache အတွက် — local မှာလည်း ထည့်ထားလို့ နှစ်နေရာလုံး တူညီတဲ့ script ကို verbatim run နိုင်တယ်။ **Local MariaDB ≠ Hostinger MySQL** မို့ ဒီ dry-run က §2.5 Drill #2 (production) ကို မလဲစားဘူး — ဒါပေမဲ့ script-level bug က local မှာ ဖမ်းပြီးသား။

#### Drill #2 — Localhost rehearsal (2026-08-13 20:09 — MySQL round-trip · production မတင်ရသေး)

**ပတ်ဝန်းကျင်:** Project က Hostinger ပေါ်မှာ မတင်ရသေးလို့ (2026-08-13) — production ကို မထိဘဲ **localhost** ပေါ်မှာ Drill #2 ရဲ့ step ၇ ခုလုံး (mysqldump → restore → reconcile → flow test → cleanup) ကို **full MySQL round-trip** အနေနဲ့ run ခဲ့။ **Live DB stand-in:** local MySQL `datapos_db` (MariaDB 10.4.32, XAMPP) — migrate + seed (store `datapos-mobile`, store_manager cashier, products LIVE-001/LIVE-002, opening stock 10 each)။ Backup → `~/backups/drill2/datapos_20260813_200903.sql.gz` (9.5K)။ Local dev SQLite ကို မထိဘူး။

| # | အဆင့် | ရလဒ် |
|---|---|---|
| 1 | Baseline (live `datapos_db`) — counts + `reconcile --verify` | sales=0 · returns=0 · open=0 · movements=2 · balances=2 · **diff 0** ✅ |
| 2 | Backup — `mysqldump datapos_db \| gzip > ~/backups/drill2/datapos_20260813_200903.sql.gz` | ✅ 9.5K |
| 3 | Restore → `datapos_db_test` | ✅ table count **live 54 = test 54** |
| 4 | Verify restored DB — counts + `reconcile --verify` | counts = baseline အတိုင်း (sales=0 · movements=2 · balances=2) · **diff 0** ✅ |
| 5 | Flow test (restored DB) — shift open → sale → return → close | RCP-20260813-0001 (15,000) → RET-20260813-0001 (15,000) → close expected=actual=100,000 · **diff 0** ✅ |
| 5b | Idempotent retry — same ctid ပြန်ခေါ် | **RETRY_SAME=yes** ✅ |
| 6 | Post-flow reconcile (test DB) | movements 2→4 (opening×2 + sale + return) · sales=1 · returns=1 · **diff 0** ✅ |
| 7 | Cleanup — `DROP DATABASE datapos_db_test;` + live ပြန်စစ် | test DB ဖျက် · live `datapos_db` counts = Step 1 baseline (sales=0 · movements=2) · **diff 0** ✅ · local SQLite unchanged (sales=3 · movements=33) |

**သင်ခန်းစာ (harness):** mysqldump → mysql restore round-trip က local MySQL မှာ အလုပ်လုပ်တယ် — table ၅၄ လုံးလုံး တူ · restore ပြီးသား DB မှာ reconcile diff 0 + flow ၄ ခု pass (Drill #1 SQLite result နဲ့ ညီ)။ ဒါက **Drill #2 (production) ကို မလဲစားဘူး** — Hostinger MySQL (server version) + real data နဲ့ ထပ်စစ်ရဦးမယ်။ Project က Hostinger မှာ မတင်ရသေးလို့ production drill က **deploy ပြီးမှ** လုပ်ရန်။

#### Drill #2 — (2026-08-?? — Production MySQL · deploy ပြီးမှ လုပ်ရန် / pending)

**ပတ်ဝန်းကျင်:** Production server (Hostinger) — MySQL `datapos_db` → restore to `datapos_db_test` → reconcile → flow test။ Command list: §2.5။

| # | အဆင့် | ရလဒ် |
|---|---|---|
| 0 | Pre-flight — `datapos_db_test` ဖန်တီး + user grant (hPanel) · cashier user ရှိ | ☐ |
| 1 | Baseline (live DB) — counts + `reconcile --verify` | ☐ diff 0 |
| 2 | Backup — `mysqldump <DB_NAME> \| gzip > ~/backups/drill2/datapos_<ts>.sql.gz` | ☐ |
| 3 | Restore → `datapos_db_test` | ☐ table count = live |
| 4 | Verify restored DB (test DB) — counts + `reconcile --verify` | ☐ diff 0 · counts = live |
| 5 | Flow test (test DB) — shift open → sale → return → close | ☐ RCP-… / RET-… / diff 0 |
| 5b | Idempotent retry — same ctid ပြန်ခေါ် | ☐ `RETRY_SAME=yes` |
| 6 | Post-flow reconcile (test DB) | ☐ movements +2 · diff 0 |
| 7 | Cleanup — `DROP DATABASE datapos_db_test;` + live ပြန်စစ် | ☐ counts = baseline · diff 0 |

**Drill #2 အောင်မြင်မှု စံ:** (ပြီးမှ ဖြည့်) — restore ပြီးသား test DB မှာ reconcile diff 0 + flow pass + cleanup ပြီးနောက် live DB = baseline။

---

## ၃။ Cutover (Pilot Go-Live)

Cutover ဆိုတာ နှစ်စနစ် (အဟောင်း AppSheet/Sheets ↔ DataPOS POS) ကနေ **DataPOS POS တစ်ခုတည်း** ကို ပြောင်းတဲ့ နေ့။

### ၃.၁ Cutover မလုပ်ခင် (Pre-cutover — ရက်သတ္တပတ် ၂ ပတ်ခန့်)

| # | အလုပ် | ဘယ်သူ | Done |
|---|---|---|---|
| 1 | Product / customer / supplier data import (item 271 hub) ပြီး · duplicate 0 · count ကိုက် | Owner | ☐ |
| 2 | Opening-stock reconciliation — ledger balance vs လက်ရှိ stock (AppSheet) diff = 0 | Owner | ☐ |
| 3 | Debt opening balances import ပြီး · receivables total ကိုက် | Owner | ☐ |
| 4 | Backup & restore drill ၂ ကြိမ် (၂.၄) အောင်မြင် | Owner | Drill #1 (SQLite) ✅ · Drill #2 localhost rehearsal (MySQL round-trip) ✅ 2026-08-13 (§2.6) · **production run ကျန် — deploy ပြီးမှ** |
| 5 | Performance + store-isolation test pass (cross-store leak 0) | Owner | ✅ code-side verified 2026-08-13 — full suite **821 pass (3671 assertions)** · store-isolation batch (StoreAuthorization/StoreContextResolver/AdminStoreManagement/CustomerDebt/PosSale/PosReturn/DailyClosing/AdminUserManagement/PilotImport) **116 pass (443 assertions) — cross-store leak 0** · load testing N/A until production is live |
| 6 | ဒီ runbook ကို Owner ဖတ်ပြီး အတည်ပြု | Owner | ☐ |
| 7 | Real cashier workflow (returns, debt, daily closing) ၁ ပတ် အသုံးပြု — issue 0 | Owner/Staff | ☐ |

### ၃.၂ Cutover နေ့ (Go-Live Sequence)

```
1. နောက်ဆုံး backup ယူ        → ၂.၁ + ၂.၂ (DB + storage) — "PRE-CUTOVER" tag နဲ့
2. AppSheet/Sheets ကို read-only  → နှစ်စနစ် ပြိုင်ရေးမဖြစ်အောင်
3. Data freeze check             → product/customer/supplier/opening/debt counts ပြန် verify
4. Inventory reconcile (verify)   → php artisan inventory:reconcile --verify  (diff 0)
5. Shifts/close state check       → open shift မရှိ၊ ပြီးခဲ့တဲ့ closing တွေ approve ဖြစ်ပြီး
6. Production switch              → ပုံမှန် business စတင် (sales/returns/debt/closing)
7. ၂ နာရီကြာ watch             → ပထမ sale → receipt → return → closing ၁ ခုစီ စမ်း
8. နေ့ကုန် daily closing        → shift close → closing approve → diff 0
```

### ၃.၃ Cutover နောက် (Stabilization)

- **Parallel validation (Phase 2.5 item):** ပထမ ၁–၂ ပတ် AppSheet နဲ့ နှိုင်းယှဉ် — နေ့စဉ် sales total / closing diff 0 ဖြစ်ကြောင်း စစ်။ မကိုက်ရင် ဒီ runbook §5 (Incident) ဖွင့်။
- **မှတ်တမ်း:** နေ့စဉ် backup + closing မှတ်တမ်း ထားပါ — ၂ ပတ် issue-free ပြီးမှသာ "pilot stable" လို့ သတ်မှတ်။

---

## ၄။ Rollback (ပြန်လှည့်နည်း)

### ၄.၁ Code Rollback (release မှားရင်)

```bash
# 1. Maintenance mode
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> \
  "cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app && php artisan down"

# 2. Previous release commit ကို deploy ပြန်လုပ် (git checkout → deploy-datapos.sh)
#    deploy-datapos.sh က server .env/vendor/storage ကို မထိဘူး — code ပဲ ပြန်။

# 3. Caches rebuild
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache

# 4. Smoke test ပြီးမှ
php artisan up
```

**Migration ပါ ပြောင်းခဲ့ရင်:** `RUN_MIGRATIONS=true` deploy ကို rollback လုပ်ဖို့ **`migrate:rollback` ကို မလုပ်နဲ့** — production မှာ data-mutating rollback မလုပ်ရ။ ဒီအစား:
- Migration additive (column/table အသစ်) ဆိုရင် **code ပြန်** လုံလောက်တယ် — schema က ရှေ့ဆက်သွားတာ ဘေးကင်းတယ် (forward-only).
- Migration က data ပြောင်းခဲ့ရင် → **§4.2 DB restore** လုပ်ပြီး code ပြန်။

### ၄.၂ Database Rollback (data ပျက်စီးမှု / မှားယွင်းတဲ့ operation)

```bash
# 1. အကုန် maintenance
php artisan down

# 2. Latest good backup ကို restore (PRE-CUTOVER သို့မဟုတ် နေ့စဉ်)
gunzip < datapos_YYYYMMDD_HHMMSS.sql.gz | mysql -u <DB_USER> -p datapos_db

# 3. Storage ပါ ပြန်လိုရင် (မလိုအပ်ရင် ကျော်)
robocopy "<backup-root>\storage" "<project-root>\storage\app\public" /MIR

# 4. Ledger verify + caches
php artisan inventory:reconcile --verify
php artisan optimize:clear

# 5. Smoke test (login, POS sale, shift, closing) ပြီးမှ
php artisan up
```

> ⚠️ **Restore လုပ်ပြီးရင် ပျက်သွားတဲ့ ကာလအတွင်း ရိုက်ထားတဲ့ sales/returns တွေကို ပြန်ထည့်ဖို့** — POS က immutable ဖြစ်လို့ ပုံမှန် flow (re-post) နဲ့သာ ပြန်လုပ်ရမယ်။ Restore နဲ့ ပျောက်သွားတဲ့ transactions တွေကို tinker နဲ့ လက်နဲ့ ပြန်ထည့်တာ **မလုပ်ရ** — ဒါက ledger integrity ကို ချိုး။

### ၄.၃ Business-Level Reversal (single မှားယွင်းမှု — restore မလိုဘဲ)

| အမှား | ပြင်နည်း (ပုံမှန် flow) |
|---|---|
| Sale မှား (item/price) | Return/refund လုပ်ပြီး အသစ် ပြန်ရိုက် — `pos_return` + `sales_return` reversal movement |
| Return မှား | Partial return flow ကို ပြန်သုံး / မရရင် restore (§4.2) |
| Cash event မှား | Shift ထဲမှာ contra entry (cash_in ↔ cash_out) ပြန် — notes ထဲမှာ ရှင်းပြ |
| Shift close မှားပြီး diff ရှိ | နောက်နေ့ closing မှာ explanation နဲ့ မှတ် — close ကို ပြန်ဖွင့်လို့မရ |
| Inventory movement မှား | Reversal movement (immutable rule) — လက်နဲ့ DELETE/UPDATE မလုပ်ရ |

---

## ၅။ Failover & Incident Response

### ၅.၁ အခြေအနေ အဆင့်တွေ

| Level | လက္ခဏာ | ဘာလုပ် |
|---|---|---|
| **L3 — Watch** | POS page နှေးတာ / search နှေးတာ / ခဏတာ error | Log ကြည့် → restart worker/cache → ၁ နာရီ watch |
| **L2 — Degraded** | Sale post ခဏတာ မရ / shift open မရ | Backup ယူ → PHP-FPM/MySQL restart → reconcile --verify → ပြီးမှ ပြန်ဖွင့် |
| **L1 — Outage** | POS လုံးဝ မရ / DB down / hosting down | §5.2 အတိုင်း — ဦးစား: data safety |

### ၅.၂ Hosting/DB Outage ကိုင်တွယ်နည်း (L1)

1. **အရင်ဆုံး: ဆိုင်ကို ပြောပါ** — စာရင်းသွင်းတာ ရပ်ပြီး စာရွက်နဲ့ မှတ်ထားခိုင်းပါ (sale လက်ခံရင် paper ပေါ်မှာ — နောက်မှ re-post)။
2. **အတည်ပြု:** SSH ဝင်လို့ရလား → MySQL အသက်ရှင်လား → disk full လား → hosting status page စစ်။
3. **Backup အနေအထား စစ်:** နောက်ဆုံး good dump ရှိကြောင်း (ပျက်စီးမှုထက် အရင်) သေချာပါစေ။
4. **Restore/repair:** ပြဿနာ အရ ကိုင်တွယ် — DB ပျက်ရင် §4.2 · code ပျက်ရင် §4.1 · server reboot ပဲ ဆိုရင် restart + verify။
5. **ပြန်ဖွင့်ပြီးနောက်:**
   ```bash
   php artisan inventory:reconcile --verify        # ledger balance diff 0
   # Outage အတွင်း paper နဲ့ ရိုက်ထားတဲ့ sales တွေကို shift အသစ်ဖွင့်ပြီး re-post
   ```
6. **Incident log ရေး** (§6) — ဘာဖြစ်လဲ၊ ဘယ်လောက်ကြာ၊ ဘာတွေ ပျက်သွားလဲ၊ ဘာတွေ ပြန်လုပ်ခဲ့လဲ။

### ၅.၃ Data-loss ဖြစ်ရင် ဦးစားပေး

1. **ချက်ချင်း write ရပ်** — `php artisan down` (ပိုပျက်စီးမှု မဖြစ်အောင်)
2. **နောက်ဆုံး good backup ကို copy** — အသစ်တစ်ခု မထပ်ရေးပါနဲ့ (disk image copy လုပ်ထားပါ)
3. ဒီ runbook §4.2 အတိုင်း restore
4. ဘာကြောင့် ပျက်လဲ root cause ရှာ — ပြန်မဖြစ်အောင် fix

---

## ၆။ Incident Log Template

Incident တိုင်း ဒီပုံစံနဲ့ `docs/ops/` ထဲမှာ `incident-YYYYMMDD.md` ရေးပါ:

```markdown
# Incident — YYYY-MM-DD

## ဖြစ်စဉ်
- ဘာဖြစ်လဲ: ...
- ဘယ်အချိန်: ...
- ဘယ်သူတွေ သက်ရောက်: ...
- Severity (L1/L2/L3): ...

## Root cause
- ...

## လုပ်ခဲ့တဲ့ အဆင့်တွေ
1. ...
2. ...

## ပျက်စီးမှု / ပြန်လည်ပြုပြင်မှု
- Sales lost / re-posted: ...
- DB restored from: `datapos_<timestamp>.sql.gz`
- Reconcile result: diff=0 / diff=X (ဖြေရှင်းနည်း: ...)

## ကာကွယ်ရေး (Prevention)
- ...

## သင်ခန်းစာ
- ...
```

---

## ၇။ ဆက်စပ်ဖိုင်များ

- `docs/ops/DEPLOYMENT.md` — ပုံမှန် DB/storage/.env backup policy
- `docs/archive/deployment-runbook.md` — deploy + rollback checklist (အရင် site ရဲ့ history — archived 2026-08-13)
- `docs/ops/DEPLOYMENT.md` — ပထမဆုံး production setup
- launch readiness audit — 2026-08-13 မှာ ဖျက်ပြီး (record: `CHANGELOG.md` ထဲမှာ)
- `docs/ops/DEPLOYMENT.md` — secrets မပေါက်ကြားရေး (ဒီ runbook က `.env` backup ကို encrypted နေရာမှာသာ သိမ်းရမယ်လို့ ပြောတာ ဒီကနေ)

## ၈။ Acceptance (ဒီ runbook ပြည့်စုံမှု စစ်ဆေးစာရင်း)

- [ ] Backup & restore drill ၂ ကြိမ် အောင်မြင် (reconcile diff 0 + flow ၄ ခု pass)
- [ ] Cutover checklist (၃.၁) အကုန် Done
- [ ] Code rollback လမ်းကြောင်း စမ်းပြီး (deploy script + caches)
- [ ] DB rollback လမ်းကြောင်း စမ်းပြီး (test DB restore)
- [ ] Business reversal ၄ မျိုး (၄.၃) staff ကို ရှင်းပြပြီး
- [ ] Failover plan (၅) ကို Owner + staff နဲ့ ဆွေးနွေးပြီး
- [ ] Incident log template ကို staff သိအောင် ပြထား
