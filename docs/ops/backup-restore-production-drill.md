# Phase 2.5 — Production Backup & Restore Drill (Execution Runbook)

> **ရည်ရွယ်ချက်:** Hostinger ပေါ် deploy ပြီးမှ **production MySQL** နဲ့ `backup → restore to test DB → verify → flow test → cleanup` round-trip ကို လက်တွေ့ run ဖို့ — ဒီဖိုင်က တစ်ခါတည်း run လို့ရတဲ့ drill ဖြစ်တယ်။ ဒီ command တွေက **local machine ကနေ SSH** ဖြင့် run ရတယ်။
>
> **ရင်းမြစ်:** `docs/ops/pilot-recovery-cutover-runbook.md` §2.4 (checklist) + §2.5 (Drill #2 step-by-step) — ဒီဖိုင်က အဲဒါတွေကို အတည်ပြုပြီး **execution order** နဲ့ ပြန်စုထားတာ။ Drill #1 (SQLite) ✅ + Drill #2 localhost rehearsal (MySQL round-trip) ✅ 2026-08-13 — **production run က ဒီဖိုင်အတိုင်း ကျန်။**
>
> **ဘယ်တော့ run ရမလဲ:** (1) Hostinger deploy ပြီးချင်း — **Phase 2.5 cutover မလုပ်ခင် အနည်းဆုံး ၂ ကြိမ်** (runbook §2.4)၊ (2) cutover နေ့မှာ final run (ဒီဖိုင် §7 Drill Log မှာ မှတ်တမ်း)။
>
> ⚠️ **ဒီ drill က live DB ကို ဘယ်တော့မှ မပြင်ဘူး** — restore က `datapos_db_test` ပေါ်ပဲ။ Live DB ကို ထိတဲ့အရာက **baseline check (read-only) နဲ့ backup ယူတာ** ပဲ။

---

## ၁။ Configuration — ဒီနေရာတစ်ခုတည်းမှာ ဖြည့်ပါ

အောက်က တန်ဖိုးတွေကို **ဒီ drill မစခင်** ဖြည့်ပါ (production `.env` ထဲက real values):

| Variable | Value (fill) | ဘယ်ကရမလဲ |
|---|---|---|
| `SSH_PORT` | `<SSH_PORT>` | SSH config / အရင် deploy log — `docs/archive/deployment-runbook.md` |
| `SSH_KEY` | `<hostinger-key>` (path) | `~/.ssh/<hostinger-key>` — acdcmm နဲ့ တူတဲ့ key |
| `HOSTINGER_USER` | `<HOSTINGER_USER>` | hPanel account |
| `HOSTINGER_IP` | `<HOSTINGER_IP>` | hPanel / SSH |
| `APP_PATH` | `/home/<HOSTINGER_USER>/domains/datapos.com/laravel_app` | split layout |
| `DB_USER` / `DB_PASS` / `DB_NAME` | production `.env` တန်ဖိုး | အောက်က fetch command |
| `TEST_DB` | `datapos_db_test` | hPanel မှာ ဖန်တီးပြီးသား (Pre-flight #1) |
| `BACKUP_ROOT` | `~/backups/drill3` | drill run တစ်ခုစီအတွက် အသစ် |

Real `.env` values ယူနည်း:

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> \
  "grep -E '^(DB_DATABASE|DB_USERNAME)=' /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app/.env"
# DB_PASSWORD က .env ထဲက တိုက်ရိုက် ကြည့်ပြီး <DB_PASS> နေရာမှာ ထည့်သုံးပါ
```

> ဒီဖိုင်ထဲက အောက်က command တိုင်းမှာ `<SSH_PORT>`, `<hostinger-key>`, `<HOSTINGER_USER>`, `<HOSTINGER_IP>`, `<DB_USER>`, `<DB_PASS>`, `<DB_NAME>` ကို အပေါ်က တန်ဖိုးတွေနဲ့ အစားထိုးပါ။ (Sed replace လုပ်ချင်ရင်: `sed -i 's/<DB_NAME>/datapos_db/g' ...` — ဒါပေမဲ့ သေချာပြန်ဖတ်ပါ။)

---

## ၂။ Pre-flight — drill မစခင် အကုန် ပြီးထားရမယ်

- [ ] **§2.5A local dry-run pass** — `docs/ops/pilot-recovery-cutover-runbook.md` §2.5A ကို local MySQL မှာ run ပြီး flow-test script pass ဖြစ်ပြီးသား (production မထိခင် syntax/service API စစ်ပြီးသား)။
- [ ] **Test DB ဖန်တီး (hPanel → Databases):** `datapos_db_test` အသစ် + production `.env` ရဲ့ MySQL user (`<DB_USER>`) ကို ဒီ DB ပေါ် **full privileges** ပေးထား — Hostinger shared hosting မှာ user က DB တစ်ခုချင်းစီအတွက် သတ်မှတ်ထားလို့ grant မပေးရင် restore က `Access denied` ဖြစ်မယ်။
- [ ] **Cashier user ရှိ** — `datapos-mobile` store မှာ `staff`/`store_manager` role user ရှိရမယ် (flow test က ဒီသူနဲ့ ဆွဲတာ)။
- [ ] **Low-activity window** — shift ဖွင့်မရှိတဲ့အချိန် (သို့) ဆိုင်ပိတ်ချိန် ရွေးပါ (baseline = live snapshot ဖြစ်လို့)။
- [ ] **Backup root ပြင်ဆင်:** `mkdir -p ~/backups/drill3` (server ပေါ်မှာ — Step B ထဲ ပါပြီးသား)။
- [ ] **ဒီဖိုင် §7 Drill Log ကို copy** — ရလဒ်တွေ မှတ်ဖို့ အသင့်။

---

## ၃။ Step A — Baseline (live DB — read-only)

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app

echo '== Step A: baseline (live DB) =='
php artisan inventory:reconcile --verify
php artisan tinker <<'PHP'
echo 'sales=' . App\POS\Models\PosSale::count()
   . ' returns=' . App\POS\Models\PosReturn::count()
   . ' open_shifts=' . App\POS\Models\CashierShift::where('status','open')->count()
   . ' movements=' . App\POS\Models\InventoryMovement::count()
   . ' balances=' . App\POS\Models\InventoryBalance::count()
   . ' customers=' . App\Models\User::count()
   . ' receipts=' . App\POS\Models\PosSale::whereDate('created_at', today())->count() . PHP_EOL;
PHP
REMOTE
```

> မျှော်လင့်ချက်: `✅ Ledger and balances are consistent.` (diff 0) — ဒီ counts တွေကို **Step D / Step F** နဲ့ နှိုင်းယှဉ်ဖို့ §7 Drill Log မှာ ချရေးထားပါ။

---

## ၄။ Step B — Backup (production DB — runbook §2.1/§2.4 checklist #1)

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
mkdir -p ~/backups/drill3

# Hostinger-safe mysqldump:
#   --single-transaction  → InnoDB consistent snapshot (table lock မလို)
#   --no-tablespaces      → Hostinger shared hosting မှာ EVENT/TRIGGER privilege မရှိတတ်လို့
#                           tablespace dump ကို ကျော် (မပါရင် "Access denied ... EVENT" fail)
#   --set-gtid-purged=OFF → **MySQL 8 မှာသာ** — MariaDB က ဒီ flag ကို မသိ ("unknown variable" နဲ့
#                           reject — local rehearsal 2026-08-17 မှာ တွေ့)။ Hostinger က MySQL 8 ဆိုရင်
#                           ထည့်ပါ၊ MariaDB ဆိုရင် ဖျက်ပြီး အောက်က command ကို သုံးပါ။
mysqldump --single-transaction --quick --no-tablespaces \
  -u <DB_USER> -p<DB_PASS> <DB_NAME> | gzip > ~/backups/drill3/datapos_$(date +%Y%m%d_%H%M%S).sql.gz

ls -lh ~/backups/drill3/
REMOTE
```

> Output ထဲက `.sql.gz` filename ကို မှတ်ထားပါ (Step C က နောက်ဆုံး dump ကို auto-ရွေးပါတယ်)။
>
> **§2.4 checklist #1 ပြီးပြည့်စုံဖို့** DB dump အပြင် ဒါတွေလည်း လိုသေးတယ် (ဒီ drill ရဲ့ အဓိကက DB ဖြစ်လို့ အောက်က ၂ ခုက လိုအပ်ရင် သီးခြား):
> - **Storage (runbook §2.2):** `robocopy "<project-root>\storage\app\public" "<backup-root>\storage" /MIR /Z` (Windows, local machine ကနေ)
> - **`.env` (runbook §2.3):** encrypted နေရာမှာ သိမ်း (git ထဲ မထည့်ရ)

---

## ၅။ Step C — Restore → `datapos_db_test` (live DB ကို မထိ — §2.4 checklist #2)

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
DUMP=$(ls -t ~/backups/drill3/datapos_*.sql.gz | head -1)
echo "Restoring: $DUMP"

# Test DB ကို အသစ်/ပြန်စ (ဖျက်ပြီး ပြန်ဆောက် — drill data သန့်အောင်):
mysql -u <DB_USER> -p<DB_PASS> -e "DROP DATABASE IF EXISTS datapos_db_test; CREATE DATABASE datapos_db_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

gunzip < "$DUMP" | mysql -u <DB_USER> -p<DB_PASS> datapos_db_test

echo '-- table count (live vs test) — နှစ်ခု တူရမယ်:'
mysql -u <DB_USER> -p<DB_PASS> -N -e "SELECT 'live', COUNT(*) FROM information_schema.tables WHERE table_schema='<DB_NAME>' UNION ALL SELECT 'test', COUNT(*) FROM information_schema.tables WHERE table_schema='datapos_db_test';"
REMOTE
```

> မျှော်လင့်ချက်: `live` count = `test` count (ဇယားအရေအတွက် အတူတူ)။

---

## ၆။ Step D — Verify restored DB (test DB ပေါ်မှာ — §2.4 checklist #3)

> ⚠️ Production မှာ `config:cache` ရှိနေလို့ `DB_DATABASE=datapos_db_test php artisan ...` env override က **အလုပ်မလုပ်ဘူး** — ဒါကြောင့် runtime မှာ connection ကို ပြန်ညွှန်တဲ့ အောက်က tinker pattern ကို သုံးရတယ် (config cache ကို မဖျက်ဘူး)။

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
   . ' movements=' . App\POS\Models\InventoryMovement::count()
   . ' balances=' . App\POS\Models\InventoryBalance::count()
   . ' customers=' . App\Models\User::count() . PHP_EOL;

Artisan::call('inventory:reconcile --verify');
echo Artisan::output();
PHP
REMOTE
```

> မျှော်လင့်ချက်: **counts = Step A baseline နဲ့ အတူတူ** · `✅ Ledger and balances are consistent.` (diff 0) — restore က data မပျက်စေဘူးဆိုတဲ့ သက်သေ။

---

## ၇။ Step E — Flow test (test DB ပေါ်မှာ — §2.4 checklist #4)

Shift ဖွင့် → sale post → return → **idempotent retry** → shift ပိတ် — restore ပြီးသား DB မှာ POS flow အလုပ်လုပ်ကြောင်း သက်သေ။

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
$register = 'Drill3-' . now()->format('Ymd-His');
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
$ctid = 'pos_return:drill3:' . now()->format('YmdHis');
$return = $returns->post(
    $store, $sale,
    [['pos_sale_item_id' => $sale->items->first()->id, 'quantity' => '1']],
    [['method' => 'cash', 'amount' => (string) $sale->total]],
    $cashier, $shift, $ctid
);

// 3b) Idempotent retry — same ctid ပြန်ခေါ် → same return id ပြန်ရမယ်
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

> မျှော်လင့်ချက်: `SALE=RCP-…` · `RETURN=RET-…` · `RETRY_SAME=yes` · `CLOSED … diff=0` — flow ၄ ခု (open → sale → return → close) + idempotency အလုပ်လုပ်ကြောင်း သက်သေ။ Store ထဲမှာ stock ရှိတဲ့ base product မရှိရင် error တက်မယ် — ဒါ drill fail မဟုတ်၊ data condition; စစ်ပါ။

---

## ၈။ Step F — Post-flow reconcile + cleanup (test DB — §2.4 checklist #5)

```bash
ssh -p <SSH_PORT> -i ~/.ssh/<hostinger-key> <HOSTINGER_USER>@<HOSTINGER_IP> bash -s <<'REMOTE'
set -euo pipefail
cd /home/<HOSTINGER_USER>/domains/datapos.com/laravel_app

echo '== Post-flow reconcile (test DB) =='
php artisan tinker <<'PHP'
config(['database.connections.mysql.database' => 'datapos_db_test']);
DB::purge('mysql');
echo 'movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;
Artisan::call('inventory:reconcile --verify');
echo Artisan::output();
PHP

echo '== Cleanup: drop test DB =='
mysql -u <DB_USER> -p<DB_PASS> -e "DROP DATABASE datapos_db_test;"
echo 'DROP ok — test DB ဖျက်ပြီး'

echo '== Live DB ပြန်စစ် (Step A baseline counts အတိုင်း ရှိရမယ်) =='
php artisan inventory:reconcile --verify
php artisan tinker <<'PHP'
echo 'sales=' . App\POS\Models\PosSale::count()
   . ' returns=' . App\POS\Models\PosReturn::count()
   . ' open_shifts=' . App\POS\Models\CashierShift::where('status','open')->count()
   . ' movements=' . App\POS\Models\InventoryMovement::count() . PHP_EOL;
PHP
REMOTE
```

> မျှော်လင့်ချက်: Post-flow `movements` = Step A + 2 (sale + return) · `✅ Ledger and balances are consistent.` (diff 0) · live DB counts = Step A baseline အတိုင်း (drill က live ကို မထိခဲ့ဘူး)။
>
> hPanel ကနေလည်း `datapos_db_test` ကို ဖျက်လို့ရတယ် — CLI grant အပြည့်မရှိရင် hPanel သုံးပါ။ Drill backup (`~/backups/drill3/`) ကို §7 Drill Log မှတ်ပြီးမှ ဖျက်နိုင်တယ်။

---

## ၉။ §2.4 Checklist → ဒီဖိုင် Step မြေပုံ

| §2.4 Checklist | ဒီဖိုင်မှာ | အောင်မြင်မှု စံ |
|---|---|---|
| 1. Backup ယူ (DB + storage + .env) | Step B | `.sql.gz` ထွက်ပြီး `ls -lh` မှာ မြင်ရ · storage/.env ပါ (သီးခြား) |
| 2. Test DB ပေါ်မှာ restore | Step C | table count live = test |
| 3. Restore ပြီးနောက် POS integrity စစ် | Step D | counts = Step A · `reconcile --verify` diff 0 |
| 4. Shift → sale → return → close စမ်း | Step E | `SALE=…` `RETURN=…` `RETRY_SAME=yes` `CLOSED diff=0` |
| 5. Test DB ဖျက် | Step F | `DROP ok` · live counts = Step A |

**အောင်မြင်မှု စံ (drill အောင်တယ်လို့ မှတ်ဖို့):** အပေါ်က ၅ ချက်လုံး pass — ရလဒ်ကို အောက်က Drill Log မှာ မှတ်တမ်းတင်ပါ။

---

## ၁၀။ Drill Log

| Run # | Date | Runner | Baseline (A) | Restore (C) | Verify (D) | Flow (E) | Cleanup (F) | PASS/FAIL | Issues |
|---|---|---|---|---|---|---|---|---|---|
| #1 | 2026-08-13 | (Drill #2 localhost rehearsal) | | | | | | ✅ | runbook §2.6 |
| #2 (local MySQL rehearsal, current 17-commit code) | 2026-08-17 | codebuff | sales=0 returns=0 open_shifts=0 movements=3 balances=3 customers=7 | 56=56 tables | counts=tie · diff 0 | RCP-…-0001 · RET-…-0001 · RETRY_SAME=yes · close diff=0 | movements 3→5 · DROP ok · live = baseline | ✅ | migration `000006` FK name >64 chars — **fixed** (explicit FK names) + guard test `MigrationConstraintNameTest` · `--set-gtid-purged` MariaDB မသိ → flag note |
| #3 (production MySQL — Hostinger deploy ပြီးမှ) | | | | | | | ☐ | |

Baseline (Step A) တန်ဖိုးတွေ: sales=__ · returns=__ · open_shifts=__ · movements=__ · balances=__ · customers=__

> **Drill #1 (SQLite):** ✅ 2026-08-13 (runbook §2.4) · **Drill #2 localhost rehearsal (MySQL round-trip):** ✅ 2026-08-13 (runbook §2.6) · **Local MySQL rehearsal (ဒီဖိုင် flow ကို current 17-commit code နဲ့):** ✅ 2026-08-17 — Step A–F အကုန် pass + **bug ၁ ခု ဖမ်း** (migration FK name) — အပေါ်က #2 row · **Drill #3 (production MySQL) — Hostinger deploy ပြီးမှ run ရန်။**
