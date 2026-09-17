# DataPOS — Closing Cash-Out ကျန်ရှိသော Defect များ ပြင်ဆင်ခြင်း (အဆင့်ဆုံး အစီရင်ခံစာ)

**Task:** Closing Cash-Out ပြဿနာများကို အပြီးသတ်ပြင်ရန် — Audit → Implement → Test → Browser Verify → Branch/PR
**Reviewed baseline commit:** `4de2cb3` — *ဤ commit သည် `origin/main` ပေါ် ရောက်နေပြီးဖြစ်သည်* (`git rev-list --left-right --count main...origin/main` → `0 0`)။ Task တွင် "GitHub ပေါ်မရောက်သေးပါ" ဟု ဆိုထားသည်မှာ ခေတ်မမီတော့ပါ။
**Fix branch:** `fix/closing-cashout-remaining-defects`
**Store:** `UAT-POS-STORE` (slug `uat-pos-store`, **id = 6**) — DB မှ အတည်ပြု
**DB:** MySQL/MariaDB 10.4.32 (`datapos_uat`), app timezone `Asia/Yangon`, session tz `Asia/Rangoon`
**ပြီးဆုံးချိန်:** 2026-09-18

---

## 1. အနှစ်ချုပ် (TL;DR)

အရေးကြီးဆုံး တွေ့ရှိချက် ၂ ခုမှာ **အရင် pass က လွတ်သွားသော** defect များဖြစ်ပြီး ၂ ခုစလုံးသည် ငွေအပေါ် တိုက်ရိုက် သက်ရောက်သည်-

1. **Browser retry protection လုံးဝ မရှိခဲ့ပါ။** POS cash-out modal သည် `client_transaction_id` ကို လုံးဝ မပို့ခဲ့သဖြင့် response ပျောက်ခြင်း / double-click တွင် expense နှစ်ခု ဖြစ်နိုင်သည်။ ထို့အပြင် ကုဒ်ရှိပြီးသား idempotency သည် "key တူ + ပမာဏ မတူ" ကို တိတ်ဆိတ်စွာ "သိမ်းပြီးပါပြီ" ဟု ပြန်ဖြေခဲ့သည်။
2. **MariaDB သည် business timestamp များကို တိတ်ဆိတ်စွာ ပြန်ရေးနေခဲ့သည်။** `cashier_shifts.opened_at` နှင့် `inventory_movements.occurred_at` သည် implicit `ON UPDATE CURRENT_TIMESTAMP` ရရှိထားသဖြင့် မည်သည့် UPDATE တွင်မဆို "ယခုအချိန်" သို့ ပြောင်းသွားသည်။ ဥပမာ တိုင်းတာချက် — shift #2 ၏ `opened_at` သည် `2026-09-17 12:00:00` မှ `2026-09-18 01:41:21` သို့ `cash_out` update တစ်ခုတည်းဖြင့် ပြောင်းသွားသည် (**သက်သေပြပြီး**)။ ရလဒ် — 17 ရက်နေ့ shift သည် 18 ရက်နေ့ shift ဖြစ်သွားပြီး 17 ရက်နေ့၏ daily closing သည် shift များကို **လုံးဝ မတွေ့တော့ပါ** → opening float၊ cash sales၊ cash-out အားလုံး ပျောက်သွားသည်။ ၎င်းသည် "မှန်သည့်အံဆွဲမှ တစ်ကြိမ်သာနုတ်ခြင်း" ကို တိုက်ရိုက်ချိုးဖျက်သည်။

နောက်ထပ် ၃ ခု (အလုပ်လုပ်စဉ် တွေ့ရှိသည်)-

3. **Expense detail modal ကို လုံးဝ ဖွင့်၍ မရခဲ့ပါ။** မော်ဒယ်၏ `@click.away` သည် document တွင် နားထောင်သဖြင့် ဖွင့်လိုက်သော click ကိုပင် ပြန်ပိတ်သည်။ Event လမ်းကြောင်း တိုင်းတာချက်: `BTN-CAPTURE false → BTN-BUBBLE true → DOC-BUBBLE false`။
4. **NULL payment_source ကို "Safe/Other" အဖြစ် ဖော်ပြခဲ့သည်** — Task ၏ Fix D တောင်းဆိုချက်နှင့် ဆန့်ကျင်သည်။
5. **X-Report သည် Closing ၏ စည်းမျဉ်းအသစ်များကို မသုံးခဲ့ပါ** — NULL source ကို "Other" ဟု ပြဆဲဖြစ်သည်။

**ရလဒ်:** SQLite **2057/2057 PASS**။ MySQL တွင် baseline (HEAD) ၏ failure **53 → 26** သို့ ကျဆင်း (ကျွန်တော့်ပြင်ဆင်ချက်ကြောင့် **failure အသစ် တစ်ခုမှ မရှိ**)။

---

## 2. Finding တစ်ခုချင်း — Root cause နှင့် ပြင်ဆင်ချက်

### A. Browser retry protection (ရှိခဲ့သည့် အဟောင်း)

**Root cause**
- `resources/js/app-admin.js` ၏ `submitExpense()` သည် body တွင် `client_transaction_id` ကို **မထည့်ခဲ့ပါ**။ Server-side idempotency ကုဒ်သည် လုံးဝ အလုပ်မလုပ်ခဲ့ပါ။
- Admin expense create form တွင် retry key လုံးဝ မပါခဲ့ပါ။
- Unique index (`expenses_store_client_tx_unique`) ကို MySQL error 1062 ဖြင့်သာ ဖမ်းခဲ့သည် (SQLite error 19 မဖမ်း)၊ Admin controller တွင် try/catch လုံးဝမရှိဘဲ race တွင် **raw DB error ကို user ထံ ပို့နိုင်ခဲ့သည်**။
- "key တူ + payload မတူ" ကို မခွဲခြားခဲ့ပါ (တိတ်ဆိတ်စွာ replay ဟု ပြန်ဖြေ)။

**ပြင်ဆင်ချက်**
- `app/POS/Support/ExpenseIdempotency.php` (အသစ်): canonical fingerprint (sha256)၊ `matches()`၊ `resultFor()` → replay/conflict၊ `isDuplicateKey()` (MySQL 1062 + SQLite 19 + Postgres 23505)၊ `normalizeKey()`။ Fingerprint column မရှိသော အဟောင်း row များကို field-by-field နှိုင်းယှဉ်သည် (blanket replay မလုပ်ပါ)။
- Migration `2026_09_18_000001_add_request_fingerprint_to_expenses_table.php`: `expenses.request_fingerprint` (char 64, nullable) — additive၊ data မပြောင်း။
- Admin `ExpenseController::store()`: idempotency ကို **period lock မတိုင်မီ** စစ်သည် (period ပိတ်ပြီးနောက် ရောက်လာသော retry သည် replay ဖြစ်ရမည်၊ lock error မဖြစ်ရ)；`DB::transaction` + shift row lock အတွင်း create；unique race တွင် winner ကို replay；driver error ကို **ဘယ်တော့မှ မထုတ်ပြန်**ဘဲ `messages.expense_save_failed_retry` သာ ပြသည်။
- POS `CashierShiftController::recordExpense()`: အလားတူ fingerprint + conflict + SQLite/MySQL duplicate detection။ `catch (\Exception)` ဖြင့် `$e->getMessage()` ကို client ထံ ပို့ခဲ့သည့် leak ကို ဖယ်ရှား။
- `resources/js/app-admin.js`: `ensureExpenseKey()` / `clearExpenseKey()` / `startNewExpenseAfterConflict()` — key ကို `baseUrl + csrf` (store+session) scope ဖြင့် `sessionStorage` တွင် memoize၊ **server မှ အတည်ပြုပြီးမှသာ** ရှင်းသည်၊ နောက် expense အတွက် key အသစ် ထုတ်သည်။
- POS modal (`modal-quick-tools.blade.php`): 409 conflict တွင် ရှင်းလင်းသော မက်ဆေ့ဂျ် + "အသစ်အဖြစ် ထပ်မံမှတ်ရန်" recovery ခလုတ်။
- Admin form: hidden `client_transaction_id` + `x-init` ဖြင့် **ရှင်းပြီးမှ ထုတ်**သည် (`expense_saved` flash ပေါ်မူတည်၍)။

**အတွင်းလမ်းအတွင်း တွေ့ရှိပြီး ပြင်ခဲ့သေး:**

- **POS modal သည် key ကို လုံးဝ မပို့ခဲ့ခြင်း** → ယခု ပို့သည်။
- **`x-init` နှင့် သီးခြား DOMContentLoaded handler အကြား race** → save ပြီးသော key ကို input တွင် ပြန်ကိုင်ထားမိပြီး **နောက် expense သည် false duplicate conflict** ဖြစ်သည် (Browser တွင် တိုင်းတာ: `keyRotated: false`)။ ယခု တစ်ခုတည်းသော `x-init` အတွင်း ရှင်းပြီးမှ ထုတ်သည် → `keyRotated: true`။

---

### B. Drawer / shift ချိတ်ဆက်မှုနှင့် တွက်ချက်မှု

**Root cause**
- `payment_source` ကို enum ဖြင့် မစစ်ခဲ့ပါ (`'nullable','string','max:50'`) → unknown source များ တိတ်ဆိတ်စွာ သိမ်းခံရပြီး "non-drawer" အဖြစ် သတ်မှတ်ခံရသည်။
- POS တွင် cashier-shifts capability ပိတ်ထားလျှင် cash expense ကို `payment_source='drawer'` + `cashier_shift_id=NULL` အဖြစ် သိမ်းခဲ့သည် → **unattributable drawer claim**။
- Cashier တွင် open shift ၂ ခုရှိလျှင် `openShiftFor()` သည် `latest('opened_at')` ဖြင့် **ခန့်မှန်း**ခဲ့သည်။
- Daily (`DailyClosingService::expectedTotals`) နှင့် shift (`CashierShiftService::closeShift`) သည် deduction rule **မတူညီသော မိတ္တူ ၂ စောင်** ကို သီးခြား ထမ်းထားသည်။
- NULL source ကို `other_cash_expenses` (Safe/Petty/Bank) ထဲ ရောနှောထည့်ခဲ့သည်။

**ပြင်ဆင်ချက်**
- `app/POS/Services/ExpenseCashAttribution.php` (အသစ်) — **တစ်ခုတည်းသော အမှန်တရား**။ `drawerDeductionQuery()` ကို daily နှင့် shift close နှစ်ခုစလုံး သုံးသည်. ထို့ကြောင့် "shift များ၏ နုတ်ငွေပေါင်း == daily နုတ်ငွေ" ဟူသော ဂုဏ်သတ္တိ အာမခံထားသည်။ `stateFor()` သည် row တစ်ခုချင်း၏ state ကို ဆုံးဖြတ်သည် (deducted / unpaid / void / non_drawer / unresolved / non_cash)။
- `summary` တွင် **`unresolved_cash_expenses`** (amount + count + expense refs) နှင့် **`is_reconciled`** အသစ် ထည့်သည်။ NULL source နှင့် shift မပါသော 'drawer' claim များသည် **မည်သည့်အံဆွဲမှ မနုတ်**ဘဲ သီးခြား ဖော်ပြခံရပြီး period ကို "ညီပြီး/ပြီးဆုံး" ဟု မခေါ်နိုင်တော့ပါ။
- `payment_source` → `Rule::in(array_keys(Expense::PAYMENT_SOURCES))` (create + update)။
- POS: capability ပိတ်ထားလျှင် cash expense ကို `SOURCE_SAFE` အဖြစ် သိမ်းသည် (drawer claim လုံးဝ မဖြစ်)။
- POS: open shift ၂ ခုရှိလျှင် **ငြင်းပယ်**သည် (`messages.pos_expense_shift_ambiguous`)။
- Shift သည် target store ပိုင်၊ open ဖြစ်၊ များသော အဆိုင်း၏ business date သည် period-locked မဖြစ်ရ၊ expense_date သည် shift မဖွင့်မီ ရက်စွဲ မဖြစ်ရ (`ExpenseController::resolveDrawerShift()` + POS တွင် တူညီသော guard)။

---

### C. Detail UI နှင့် posted status ကိုက်ညီမှု

**Root cause** — `closing.blade.php` တွင်
```php
$isDrawer = ($exp->payment_method === 'cash' && ($exp->payment_source === 'drawer' || $exp->cashier_shift_id));
```
ဤစည်းမျဉ်းသည် **shift id ရှိရုံဖြင့် "နုတ်ပြီး"** ဟု ပြသည် — `status` (paid/unpaid/void) ကို လုံးဝ မစစ်သဖြင့် **မပေးရသေးသော / ပယ်ဖျက်ထားသော expense များသည် "နုတ်ပြီး" ဟု ပြခံရပြီး ledger မှ နုတ်မထားပါ**။ ထို့အပြင် `payment_source='safe'` + shift id ရှိသော row သည်လည်း "နုတ်ပြီး" ဟု မှားယွင်းပြသည်။

**ပြင်ဆင်ချက်**
- Badge ကို **service က ဆုံးဖြတ်သည်** (`cash_out_state`) — View တွင် စည်းမျဉ်း ပြန်မရေးပါ။
- "ပြထားသော နုတ်ငွေစုစုပေါင်း" (shown rows deducted total) ကို **rows များမှ တွက်သည်** — ထို့ကြောင့် နုတ်ပြီးဟု ပြထားသော row များ၏ ပေါင်းလဒ် == drawer subtotal ဖြစ်ရမည် (test C03 ဖြင့် သော့ခတ်ထား)။
- NULL source → "ငွေထွက်နေရာ မအတည်ပြုရသေး" (Safe နှင့် **မပေါင်း**)။
- `closing_x_report.blade.php` ကိုလည်း တူညီသော state-driven rendering သို့ ပြောင်းပြီး X-Report စာမျက်နှာတွင် unresolved banner ထည့်သည်။
- i18n key အသစ် ၂၂ ခုကို **my / en / zh_CN** သုံးဘာသာစလုံးတွင် ထည့်သည် (5890 keys × 3, parity diff = 0)။

---

### D. Closed-period / snapshot integrity

**Root cause**
- Expense ပြင်ခြင်းသည် **ပိတ်ပြီး ရေတွက်ပြီးသော shift** ၏ drawer တွက်ချက်မှုကို ပြန်လည်ရေးနိုင်ခဲ့သည် (delete/amount edit/source ပြောင်းခြင်း)။
- Expense create/update နှင့် shift close အကြား **lock ordering မရှိ**ခဲ့ပါ။
- Approved closing သည် `summary_snapshot` တွင် `version` + `metrics` သာ သိမ်းခဲ့သည် — **detail လုံးဝ မသိမ်း**။ ထို့ကြောင့် approved (သမိုင်းဝင်) စာရွက်အတွက် detail ကို **live row များမှ** ဆွဲပြခဲ့သည် — ၎င်းသည် သမိုင်းအထောက်အထား မဟုတ်ပါ။

**ပြင်ဆင်ချက်**
- `Expense::isLockedByClosedShift()` / `deductionShift()`: closed shift မှ နုတ်ထားသော expense ကို **delete ပိတ်**၊ **drawer-relevant edit (amount/method/source/shift/date) ပိတ်**၊ cosmetic edit (title/notes/category/attachment/paid_to/reference) ကို ဖွင့်ထားသည်။ အခြားလမ်းလိုအပ်လျှင် reversal/audited adjustment ဖြင့်သာ။
- `DB::transaction` + **shift row lock** ကို create/update/delete သုံးခုစလုံးတွင် (shift id များကို sort လုပ်ပြီး lock → deadlock မဖြစ်)။ `closeShift()` သည်လည်း ထို row ကို lock ယူပြီးမှ အတွင်းပိုင်း figure များကို ဖတ်သည် → expense သည် ပိတ်လိုက်သော အံဆွဲပေါ် တင်ကျန်မနေနိုင်။
- `DailyClosing::frozenExpenseRows()` / `hasFrozenExpenseDetail()` / `frozenExpenseMeta()` အသစ်။ `create()` သည် detail ကို **bounded snapshot** (rows ≤ 200 + total + truncated + dropped_amount) အဖြစ် သိမ်းသည် (`version: 2`)။
- Closing detail modal: approved closing အတွက် **frozen row များ** ကိုသာ ပြသည်။ legacy (v1) snapshot ဖြစ်လျှင် — live row များကို သမိုင်းအထောက်အထားအဖြစ် **မပြ**ဘဲ **"ဤပိတ်ချိန်မှတ်တမ်းတွင် အသုံးစရိတ် အသေးစိတ် မသိမ်းထားပါ"** ဟု အတိတိအလင်း ဖော်ပြသည်။ legacy snapshot ၏ `other_cash_expenses` သည် ယခုငြင်းပယ်ထားသော စည်းမျဉ်းဖြင့် တွက်ထားသဖြင့် **မပြ**တော့ပါ၊ unresolved figure ကို "လက်ရှိ စာရင်းမှ ခွဲခြားချက်" ဟု တံဆိပ်တပ်ပြသည်။

---

### E. (နောက်ထပ်) Modal opener သည် မိမိ modal ကို ပြန်ပိတ်ခြင်း

**Root cause** — `@click.away="showExpenseModal = false"` သည် document တွင် listener မှတ်ထားသည်။ Opener ကို click လုပ်လျှင်: button handler က `true` လုပ် → document သို့ bubble → away handler က `false` ပြန်လုပ်။ **မော်ဒယ် ဖွင့်ပြီး တစ်ချက်တည်း ပြန်ပိတ်သည်** → drawer expense detail ကို လုံးဝ မမြင်နိုင်။

**ပြင်ဆင်ချက်** — `closing.blade.php` နှင့် `closing_x_report.blade.php` ရှိ opener နှစ်ခုကို `@click.stop` သို့ ပြောင်း (print modal တွင် `@click.stop` ရှိပြီးသား — expense opener နှစ်ခုကို လွတ်ခဲ့သည်)။

> **Scope အပြင် တွေ့ရှိချက် (မပြင်ခဲ့ပါ):** `resources/views/pos/reports/services.blade.php:63` တွင် တူညီသော pattern (`exportModalOpen` opener တွင် `.stop` မပါ) ရှိသည်။ ၁ token ပြင်ရုံဖြစ်သော်လည်း Services report သည် ဤ task scope မဟုတ်သဖြင့် သီးခြား report လုပ်သည်။

---

## 3. Migration များ (non-destructive၊ additive)

| Migration | ရည်ရွယ်ချက် | Data impact |
|---|---|---|
| `2026_09_18_000001_add_request_fingerprint_to_expenses_table.php` | `expenses.request_fingerprint` (char 64, nullable) ထည့် | မရှိ — nullable column အသစ်သာ |
| `2026_09_18_000002_stop_implicit_timestamp_rewrite_on_business_columns.php` | MariaDB ၏ implicit `ON UPDATE CURRENT_TIMESTAMP` ကို `cashier_shifts.opened_at` နှင့် `inventory_movements.occurred_at` မှ ဖယ် (DEFAULT ကို ဆက်ထားသဖြင့် omit-insert ဆက်အလုပ်လုပ်) | မရှိ — တန်ဖိုးများ တူညီစွာ ပြောင်း (connection session tz သည် app တူ), row count တူ |

**အသုံးပြုနည်း (confirmed local DB ပေါ်တွင်သာ):**
```bash
D:/xmapp/php/php.exe artisan migrate --force
```

**အတည်ပြုချက် (`datapos_uat`):** migration မတိုင်မီနှင့် အပြီး row count များ တင်းတိမ်တူ — stores 2, expenses 2, cashier_shifts 1, cash_events 0, daily_closings 1, inventory_movements 8, pos_sales 0. `shift#1.opened_at = 2026-09-17 07:44:24` မပြောင်း၊ `inventory_movements.occurred_at` ၈ ခုလုံး `2026-09-17 07:43:20` မပြောင်း၊ `closing#1` သည် `approved` ဆက်ဖြစ်။ **reset/reseed/wipe/truncate/migrate:fresh မလုပ်ခဲ့ပါ။** Existing approved closing ကို reopen/delete မလုပ်ခဲ့ပါ။

---

## 4. Test ရလဒ်များ (တိတိကျကျ)

### SQLite (in-memory — `phpunit.xml` default)
```bash
D:/xmapp/php/php.exe vendor/bin/phpunit --no-coverage
```
**PASS — 2057 tests, 9518 assertions, 0 failures.**
### MySQL / MariaDB 10.4.32 (disposable DB — `datapos_closing_fix_test`၊ `datapos_uat` **မဟုတ်**)
```bash
D:/xmapp/php/php.exe -r '$p=new PDO("mysql:host=127.0.0.1;port=3306","root",""); $p->exec("CREATE DATABASE IF NOT EXISTS datapos_closing_fix_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");'
DB_CONNECTION=mysql DB_DATABASE=datapos_closing_fix_test DB_HOST=127.0.0.1 DB_PORT=3306 DB_USERNAME=root DB_PASSWORD= \
  D:/xmapp/php/php.exe vendor/bin/phpunit --no-coverage
```
| Run | Result |
|---|---|
| Baseline (HEAD `4de2cb3`, migrations မပါ) | **Errors 20, Failures 33 (distinct 53)** |
| With this fix | **Errors 11, Failures 15 (distinct 26)** |
| New failures introduced by this fix | **0** (set difference အတည်ပြု) |
| Tests repaired by this fix | **27** |
| Closing + exactness suite (targeted) | **PASS — 69/69** |

### Fail-before / Pass-after (ဤ task ၏ test အသစ်များ)
`tests/Feature/POS/ClosingCashOutRemainingDefectsTest.php` (32 tests) ကို fix များကို `git stash` ဖြင့် ဖယ်ပြီး ပြန်စမ်းသည်:
- **Before:** `Tests: 29, Errors: 3, Failures: 14` (15 ခုသည် အရင် pass ၏ အပြင်ဖြစ်သဖြင့် အောင်နေသည်)
- **After:** **PASS — 32/32**

### Engine-specific ပြင်ခဲ့သည့် test တစ်ခု
`ReportTotalsExactnessTest::test_exact_sum_does_not_drift_where_a_float_sum_does` သည် "plain SQL SUM သည် drift ဖြစ်ရမည်" ကို **universal** အဖြစ် ရေးထားခဲ့သည်။ MySQL/MariaDB သည် DECIMAL ကို တိကျစွာ sum လုပ်သဖြင့် ၎င်း assertion သည် MySQL တွင် မှားသည်။ Engine-aware ဖြစ်အောင် ပြင်ပြီး (`exact_sum` သည် မှန်ရမည် — drift assertion ကို SQLite တွင်သာ)။ → MySQL ပေါ်တွင် ယခု PASS။

### Lint gates
- `php -l` — ပြင်ခဲ့သော PHP ဖိုင် ၁၂ ခုလုံး **No syntax errors**။
- `npm run build` (Vite 7) — **✓ built** (Blade/JS asset အသစ်များ အမှန်တကယ် ပါဝင်၊ server :8501 မှ ဝန်ဆောင်နေသည်ကို bundle hash ဖြင့် အတည်ပြု)။
- `vendor/bin/pint --test` — **ဤ project သည် baseline တွင်တင် pint-clean မဟုတ်ပါ** (မထိသော ဖိုင် ၄ ခုအနက် ၃ ခု fail)။ ထို့ကြောင့် format အလိုအလျောက် မပြင်ခဲ့ပါ (unrelated diff မဖန်တီးရန်)။
- Tri-lingual parity — my/en/zh_CN **5890 keys × 3, diff = 0**။

---

## 5. Browser Verification (အမှန်တကယ် browser၊ `http://127.0.0.1:8501`)

Store `uat-pos-store`၊ login `UAT Store Owner`။ Browser server သည် **main folder** မှ ဆောင်ရွက်ကြောင်း bundle hash ဖြင့် အတည်ပြု (`dataposExpenseKey` ပါဝင်)။

| စမ်းသပ်ချက် | ရလဒ် |
|---|---|
| Drawer expense ကို **ပိတ်ထားသော ရက်စွဲ (2026-09-17) အဆိုင်းသို့** ချိတ်၍ တင်ခြင်း | **ငြင်းပယ်** — "ဤအဆိုင်း၏ ရက်စွဲကို ပိတ်ပြီးဖြစ်သဖြင့် ..."၊ expense မဖန်တီး၊ store သည် 2 expenses အတိအကျ ဆက်ရှိ |
| Admin form hidden retry key | **ရှိ** (`5749475c-…`, UUID 36 char) + sessionStorage `datapos.expense.txid.6` တွင် တူညီစွာ memoize |
| မှန်ကန်သော expense တင်ခြင်း → key rotation | **key လဲသွားသည်** (`6404…` → `261e…`)၊ row ဖန်တီးခံရ |
| POS endpoint retry (same key + same payload) | 200၊ **id=5 (row တူ)**၊ `idempotent_replay: true` |
| POS endpoint same key + **amount မတူ** | **409** + "ဤသုံးစရိတ်သည် EXP-20260918-0003 အဖြစ် စာရင်းသွင်းပြီးဖြစ်ပါသည်…"， row အသစ် **မဖန်တီး** |
| POS endpoint key မတူ + value တူ | 200၊ **id=6** (တရားဝင် expense နှစ်ခု ခွင့်ပြု) |
| Closing (2026-09-17, approved, legacy v1 snapshot) detail | "ပိတ်ချိန် မှတ်တမ်း" + **"အသေးစိတ် မသိမ်းထားပါ"** + unresolved 5,000 (လက်ရှိ စာရင်း) — live row များကို သမိုင်းအဖြစ် မပြ |
| X-Report (2026-09-17) detail (live path) | EXP-0002 = ✅ "အံဆွဲမှ နုတ်ပြီး" + REG-1 (#1)၊ EXP-0001 = ⚠️ "ငွေထွက်နေရာ မအတည်ပြုရသေး" + "မနှုတ်ပါ" |
| Same-scope reconciliation (X-Report) | "အသုံးစရိတ်: 5,000 Ks" **==** "ပြထားသော နုတ်ငွေစုစုပေါင်း: 5,000 Ks" |
| Viewport | **375×812**, **768×1000**, **1366×900** — Burmese labels မှန်၊ modal မှန်၊ **internal overflow မရှိ** (measured: `scrollWidth == clientWidth`) |
| Console / JS error (၃ စာမျက်နှာ) | **(no errors)** — `error`, `unhandledrejection`, `console.error` ဖမ်းစစ်ပြီး |
| Network | POST/409/DELETE အားလုံး မျှော်မှန်းသည့်အတိုင်း |

**Print** — physical printer/ESC-POS မစမ်းခဲ့ပါ။ Print preview မစမ်းခဲ့ပါ (**NOT RUN**)။

**ပြင်ဆင်ချက်များ၏ နောက်ဆက်တွဲ:** browser တွင် ဖန်တီးခဲ့သော စမ်းသပ် expense ၄ ခု (`UAT-CLOSINGFIX run1/run2/run3`၊ အားလုံး 2026-09-18၊ drawer/shift နှင့် မချိတ်) ကို စမ်းပြီးနောက် **ဖျက်ပြီးပါပြီ**။ ဆိုင်သည် မူလအခြေအနေသို့ ပြန်ရောက်သည် (expenses 2)။ Expense number sequence သည် `EXP-20260918-0003` အထိ တက်သွားသည် (ပြန်လျှော့၍ မရပါ — ဤသည်သာ ကျန်သော သက်ရောက်မှု)။

---

## 6. ကျန်ရှိသော ကန့်သတ်ချက်များ / Out of scope

- **Physical printing မစမ်းရသေးပါ** — print preview/ESC-POS hardware test မလုပ်ခဲ့ပါ။
- **MySQL တွင် ကျန်သော failure ၂၆ ခု** — အားလုံး **HEAD တွင်တင် ရှိပြီးသား**၊ ဤ task နှင့် မသက်ဆိုင်သော module များ (BulkPriceWizard, DemoStoresSeeder, PilotImportPresets, StoreDeletion, WarrantyTracker, OfflineSync, InventoryLedger, Phase4PosDecoupling, `po_payment_logs.payment_method` မရှိခြင်း, SQLite FK-enforcement မတူခြင်း စသည်)။ SQLite တွင် အားလုံး PASS ဖြစ်သော်လည်း **SQLite PASS ကို MySQL PASS ဟု ဤအစီရင်ခံစာတွင် မရေးထားပါ**။
- **Concurrent retry ကို အမှန်တကယ် parallel process ဖြင့် မစမ်းခဲ့ပါ** — unique-index race ကို deterministic (winner ကို ကြိုတင်ရေးထည့်) နည်းဖြင့် စမ်းသည်။ Transaction + row lock + unique index ရှိပြီး SQLite/MySQL နှစ်ခုလုံးတွင် duplicate detection ကို unit-level စမ်းထားသည်။
- **`services.blade.php` export modal** — တူညီသော `.stop` လိုအပ်ချက် (အထက်တွင် report လုပ်ထား)။
- **Phone width တွင် POS header/top-bar** တွင် 32px horizontal overflow ရှိသည် (ကျွန်တော့်ပြင်ဆင်ချက်နှင့် မသက်ဆိုင်ပါ — measured offenders များသည် header အတွင်းရှိ `flex items-center gap-2` အုပ်စု)။ 768/1366 တွင် မရှိပါ။
- **Bank/KPay account ledger posting** — expense source ကို *ခွဲခြားသတ်မှတ်* ခြင်းသာ လုပ်သည်၊ ဘဏ်အကောင့် ledger သစ် မဆောက်ခဲ့ပါ (task scope အပြင်)။
- ဤ scope PASS ကို **project တစ်ခုလုံး Production Ready** ဟု မသတ်မှတ်ပါ။

---

## 7. Branch / Commit / Status

- Branch: `fix/closing-cashout-remaining-defects` (base: `main` @ `4de2cb3`)
- Commit: ဤ commit (see PR)
- `main` ကို force-push မလုပ်ပါ၊ auto-merge မလုပ်ပါ၊ production deploy မလုပ်ပါ။
- `.env`, password, customer data, `vendor/`, `node_modules/` — commit မပါ။
- Migration status (local): `2026_09_18_000001`, `2026_09_18_000002` နှစ်ခုလုံး `datapos_uat` ပေါ်တွင် **applied**။
