# DataPOS — MySQL Portability Fixes (Production-Readiness pass 2)

**Date:** 2026-09-18
**Branch:** `fix/closing-cashout-remaining-defects` (follow-up commits)
**Scope:** the P1 + MySQL-triage work requested after the closing cash-out pass.

---

## 1. ဘာကြောင့် ဒီ pass လိုအပ်ခဲ့သလဲ

`phpunit.xml` သည် SQLite (`:memory:`) ကို default ထားသည်။ SQLite သည် **မရှိသော column ကို double-quote နှင့်ရေးလျှင် error မပေးဘဲ string literal အဖြစ် ဖတ်သည်**။ ဥပမာ `where "payment_method" = 'cash'` သည် SQLite တွင် `'payment_method' = 'cash'` ဖြစ်သွားပြီး **အမြဲ false** ဖြစ်သည်။ ထို့ကြောင့်—

- SQLite (dev/test): query သည် ရိုးရိုး **0 row** ပြန်သည် → test အောင်၊ ဒါပေမယ့် ရလဒ်က မှား
- MySQL/MariaDB (အမှန်တကယ် hosting အင်ဂျင်): **Unknown column → 500 error**

"SQLite အောင်တယ်" သည် MySQL တွင် အလုပ်လုပ်မည်ဟု **ဘယ်တော့မှ မဆိုနိုင်ပါ**။ ဒီ pass သည် အရင် pass တွင် baseline အဖြစ်သာ မှတ်ထားခဲ့သော MySQL failure ၂၆ ခုကို တစ်ခုချင်း စစ်ထုတ်ပြီး **တကယ့် app bug ၆ မျိုး** ကို ရှာဖွေပြင်ဆင်ခဲ့သည်။

---

## 2. တွေ့ရှိပြီး ပြင်ဆင်ခဲ့သော တကယ့် bug ၆ မျိုး

| # | နေရာ | ပြဿနာ | MySQL တွင် ရလဒ် | SQLite တွင် ရလဒ် |
|---|---|---|---|---|
| 1 | `BusinessReconciliationService:184` | `po_payment_logs.payment_method` — **column လုံးဝ မရှိ** | Cash reconciliation စာမျက်နှာ **500** | Supplier cash payments **0** (တိတ်ဆိတ် မှား) |
| 2 | `BulkPriceWizardService:44` | `CAST(x AS REAL)` — MariaDB လက်မခံ | Price Wizard စာမျက်နှာ **500** | အောင် (REAL အလုပ်လုပ်) |
| 3 | `DemoBusinessScenarioService:649/659` | `pos_sale_items.sale_id` နှင့် `pos_return_items.return_id` (အမှန်က `pos_sale_id`/`pos_return_id`) | ဆိုင်ဒေတာ ရှင်းခြင်း/Pilot seed **500**၊ ရှင်းလင်းမှု တစ်ဝက်ရပ် | အောင် (no-op) |
| 4 | `OfflineSyncService:271` | `products.cost_price` နှင့် `products.is_active` — **column မရှိ** | Terminal sync **500** | အောင် (cost မပါလာ) |
| 5 | `StockCountService:145` | `$product->cost_price ?? $product->buy_price` — နှစ်ခုလုံး မရှိ → **အမြဲ 0** | အောင် | အောင် — ဒါပေမယ့် **variance တန်ဖိုး 0** (ငွေအပေါ် သက်ရောက်) |
| 6 | `WarrantyTrackerService:170` | `ServiceJob::latest('received_at')` — `service_jobs` တွင် မရှိ | Warranty detail **500** | အောင် — ဒါပေမယ့် ORDER BY သည် constant string → **အစီအစဉ် လုံးဝ မှား** |

### ပြင်ဆင်ချက်များ

1. **`po_payment_logs.payment_method`** — migration အသစ် (`2026_09_18_000003`၊ nullable) + `PoPaymentLog::PAYMENT_METHODS` + payment form တွင် ရွေးချယ်စရာ + `applyPayment()` တွင် သိမ်း။ Reconciliation သည် **explicit cash ကိုသာ** နုတ်ပြီး မှတ်တမ်းမတင်ထားသော ငွေချေမှုကို `supplier_payments_unrecorded` အဖြစ် သီးခြားပြသည် (ခန့်မှန်း၍ နုတ်ခြင်း မရှိ)။
2. **`CAST AS REAL` ဖယ်** — `(retail_price - purchase_cost) * 1.0 / NULLIF(retail_price, 0)` (အင်ဂျင် နှစ်မျိုးလုံးတွင် အလုပ်လုပ်၊ `NULLIF` က သုညဖြင့် စားခြင်းကိုပါ ကာကွယ်)။
3. **FK column အမည်များ မှန်** — `sale_id`→`pos_sale_id`၊ `return_id`→`pos_return_id`။
4. **Offline pull** — `purchase_cost` (CAST … AS DECIMAL(12,2) ဖြင့်) + `is_active` = 1 (product တစ်ခုသည် ဤစာရင်းတွင် ရှိသရွေ့ ရောင်းနိုင်သေးသည်)။
5. **Stock count** — `purchase_cost` (တကယ့် column)။
6. **Warranty history** — `latest('created_at')`။

---

## 3. P1 (အရင်က report လုပ်ထားသော အသေးအဖွဲ)

- `resources/views/pos/reports/services.blade.php` — export modal opener တွင် **`@click.stop`** ထည့် (မထည့်လျှင် မော်ဒယ်သည် ဖွင့်သည့် click ကိုပင် ပြန်ပိတ်သဖြင့် **လုံးဝ ဖွင့်၍မရ**)။
- `resources/views/layouts/pos/app.blade.php` — header control အုပ်စုတွင် `min-w-0 flex-wrap justify-end` ထည့်။ ~360px ဖုန်းတွင် 32px horizontal overflow ရှိခဲ့သည်။
- `tests/Feature/ReportTotalsExactnessTest.php` — SQLite ၏ REAL-sum drift ကို universal အဖြစ် ရေးထားခဲ့သည်။ ယခု engine-aware။
- `tests/Feature/Admin/PilotImportPresetsTest.php` — `Product::…->firstOrFail()` တွင် `orderBy('id')` ထည့်။ ORDER BY မရှိလျှင် "ပထမ product" သည် အင်ဂျင်အလိုက် ပြောင်းသဖြင့် assertion သည် row order ပေါ် မှီခိုနေခဲ့သည်။

---

## 4. Test ရလဒ်များ

```bash
# SQLite (in-memory)
D:/xmapp/php/php.exe vendor/bin/phpunit --no-coverage
# → OK (2063 tests, 9535 assertions)

# MySQL / MariaDB 10.4.32 — disposable DB (datapos_uat မထိ)
D:/xmapp/php/php.exe -r '$p=new PDO("mysql:host=127.0.0.1;port=3306","root",""); $p->exec("CREATE DATABASE IF NOT EXISTS datapos_closing_fix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");'
DB_CONNECTION=mysql DB_DATABASE=datapos_closing_fix_test DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= \
  D:/xmapp/php/php.exe vendor/bin/phpunit --no-coverage
```

| Run | Distinct failing tests (MySQL) |
|---|---|
| Baseline `4de2cb3` (full suite) | **53** |
| ဤ pass အပြီး | **12** (failure အသစ် **၀**၊ ပြန်ကောင်းသွားသည် **၄၁**) |

**Fail-before အထောက်အထား:** test အသစ် ၆ ခုကို HEAD ပေါ်တွင် run စမ်းသည် → **၆ ခုလုံး FAIL** (ဇယား ၂ ၏ bug များကို သက်သေပြ)၊ ပြင်ပြီးနောက် **၆/၆ PASS** — SQLite နှင့် MySQL နှစ်ခုလုံးတွင်။

**ကျန်ရှိသော ၁၂ ခု** (အားလုံး HEAD တွင်တင် ရှိပြီးသား၊ ဤ scope နှင့် မသက်ဆိုင်):

| အုပ်စု | အကြောင်းရင်း | အမျိုးအစား |
|---|---|---|
| `Phase4PosDecouplingTest` (4)၊ `PosMoneyAndAtomicityTest` (4)၊ `InventoryLedgerTest` (1) | `inventory_movements.posted_by` / `stock_counts.created_by` FK ချိုးခြင်း — fixture က မရှိသော user id ကို ရေးသည်။ SQLite သည် FK ကို မ enforce သဖြင့် မပေါ်ခဲ့ခြင်း | **Test fixture** (app bug မဟုတ်) |
| `MoneyWritePathSafetyTest` (1) | တန်ဖိုး နှိုင်းယှဉ်မှု ကွာခြင်း | စစ်ဆေးရန် ကျန် |
| `AdminServiceJobsTest` (1) | `is_active` column တောင်းနေသော query | စစ်ဆေးရန် ကျန် |
| `ProductionBootstrapTest` (1) | default value assertion | စစ်ဆေးရန် ကျန် |

---

## 5. တွေ့ရှိပြီး ဖြေရှင်းခဲ့သော Test-suite ပြဿနာ (တစ်ခု)

ဤ pass အတွင်း test အသစ်က `cleanStoreData()` ကို ခေါ်ရာ ၎င်းသည် **တကယ့် public disk** ပေါ်ရှိ `demo-stores/<id>` ကို ဖျက်သည်။ ထို directory ကို process တစ်ခုတည်းအတွင်း အခြား test များ (AdminBackup, AdminBrand, AdminExpense, PilotImport) က မှီခိုသဖြင့် **suite တစ်ခုလုံးတွင် Storage assertion များ ကျဆင်း**ခဲ့သည် (isolated run တွင် အားလုံး pass)။
ဖြေရှင်းချက် — ထို test class ၏ `setUp()` တွင် `Storage::fake('public')`။ ယခု SQLite full suite **2063/2063 PASS**။

---

## 6. ကျန်ရှိသေးသော အလုပ်များ (Production အတွက်)

1. **Printer / ESC-POS လက်တွေ့စမ်းသပ်ချက်** — မလုပ်ရသေးပါ (NOT RUN)။ အကြီးဆုံး မသေချာမှု။
2. **ကျန်သော MySQL failure ၁၂ ခု** — အထက်တွင် ဖော်ပြထားသည့်အတိုင်း အများစုသည် fixture ပြဿနာဖြစ်သော်လည်း ၃ ခုကို စစ်ထုတ်ရန် ကျန်သည်။ ငွေနှင့် ဆက်စပ်သော `MoneyWritePathSafetyTest` ကို ဦးစားပေးစစ်ပါ။
3. **Deploy checklist** — `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, `.env` မ commit (အတည်ပြုပြီး)၊ server ပေါ်တွင် `npm run build`, migrate မတိုင်မီ DB backup။
4. **UAT drill** — ဝန်ထမ်းအစစ်နှင့် တစ်ရက်တာ အပြည့်အစုံ လည်ပတ်စမ်းသပ်ခြင်း။

> ဤ scope PASS ဖြစ်ခြင်းကို **project တစ်ခုလုံး Production Ready** ဟု မသတ်မှတ်ပါ။ အထက်ပါ ၄ ချက် ကျန်ပါသည်။
