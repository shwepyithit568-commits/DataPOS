# DataPOS — လက်တွေ့ UAT (2026-09-18) တွင် တွေ့သော ချို့ယွင်းချက်များ ပြင်ဆင်ခြင်း

ဤစာတမ်းသည် `datapos_uat` (Store: **DataPOS Mobile & Accessories**) ကို အသစ်ပြန်ထည့်ပြီး
MySQL အစစ်ပေါ်တွင် တစ်နေ့တာ လုပ်ငန်းလည်ပတ်မှုအတိုင်း run ကြည့်ရာမှ **တွေ့ရှိခဲ့သော bug ၆ ခု** ကို
ဖြေရှင်းထားသည့် မှတ်တမ်းဖြစ်သည်။ Test ၂၃ ခု အသစ်ထည့်ပြီး၊ ချို့ယွင်းချက်တစ်ခုချင်းစီအတွက်
**fix မတိုင်မီ FAIL ဖြစ်ကြောင်း** အထောက်အထား မှတ်တမ်းတင်ထားသည်။

---

## 1. အရေးကြီးဆုံး — POS မဟုတ်သော ကောင်တာငွေများ အံဆွဲစာရင်းထဲ ရောက်မလာခြင်း

### လက္ခဏာ (တိုင်းတာပြီး)
ကြွေးကောက်ခံ 10,000 + စက်ပြင်အပ်ငွေ 10,000 + စက်ပြင်ကျန်ငွေ 35,000 = **၅၅,၀၀၀** သည်
လက်တွေ့ အံဆွဲထဲ ရောက်ခဲ့သော်လည်း `cashier_shifts.cash_in = 0`၊ `cash_events = 0 rows`။
ရလဒ်: စာရင်းပိတ်စာမျက်နှာက expected **345,000** ပြ၊ အံဆွဲတွင် တကယ်ရှိ **400,000** →
**၅၅,၀၀၀ လိုနေသည်ဟု မှားယွင်းစွာ ပြ**။

### အမြစ်အကြောင်းရင်း
`CashEvent` ကို ရေးသော နေရာ **တစ်ခုတည်း** ရှိသည် — manual "＋ ငွေသွင်း" (`CashierShiftService::recordCashMovement` / `addCashEvent`)။
ကြွေးကောက်ခံခြင်း (`CustomerReceivableController`) နှင့် စက်ပြင်ငွေလက်ခံခြင်း
(`RepairController`, `ServiceJobController`) တို့သည် `cash_in`/`CashEvent`/`shift` ကို
**တစ်လုံးမှ မချိတ်ဆက်ထား**။

### ဖြေရှင်းချက်
| ဖိုင် | အလုပ် |
|---|---|
| `app/POS/Services/CashierShiftService.php` | `resolveCounterDrawer()` (ambiguity မရှိမှ ရွေး) + `postCounterCashIn()` (bcmath + period-lock guard) |
| `app/POS/Services/CounterCashPosting.php` (အသစ်) | `post()` / `postSafely()` / `flash()` — ငွေပေးချေမှု စာရင်းတွင်သာ မှားယွင်းမှု မဖြစ်စေရန် |
| `CustomerReceivableController::collect()` | cash ဖြစ်ပါက အံဆွဲသို့ ရေးပြီး၊ မရေးနိုင်ပါက **warning** ပြ |
| `RepairController::store()` / `addPayment()` | အပ်ငွေ/ကျန်ငွေ cash ဖြစ်ပါက အလားတူ |
| `ServiceJobController::store()` / `addPayment()` | အလားတူ (controller နှစ်ခုလုံး ရှိသည်) |

**ဆုံးဖြတ်ချက် (မှတ်တမ်း):** drawer ကို **မခန့်မှန်း** — (၁) ကိုယ်တိုင် ဖွင့်ထားသော shift ရှိလျှင် ၎င်း၊
(၂) မရှိလျှင် ဆိုင်တွင် ဖွင့်ထားသော shift **တစ်ခုတည်း** ရှိမှ ၎င်း၊ (၃) နှစ်ခုရှိလျှင် သို့မဟုတ်
business date ကို အတည်ပြုပြီးသား (period locked) ဖြစ်လျှင် **မရေးဘဲ warning ပြ**။

### လက်တွေ့စမ်းသပ်မှု (browser, MySQL)
- Store B မှာ shift ဖွင့် (opening 100,000) → ကြွေး 3,000 ငွေသားဖြင့် ကောက်ခံ
- အောင်မြင်စာ: **"အကြွေး ကောက်ခံပြီးပါပြီ — 3,000 Ks · Store B Counter အံဆွဲထဲ ထည့်ပြီးပါပြီ"**
- DB: `cash_in = 3000.00`, `cash_events` row = `cash_in 3000.00 | ဖောက်သည်ထံမှ ကြွေးကောက်ခံရငွေ — Daw Mya (Store B)`
- နေ့ချုပ်စာမျက်နှာ: opening 100,000 + in 3,000 − ထွက်ငွေ 1,000 = **102,000** = ရေတွက်ရငွေ → ကွာဟချက် **0** ✓

---

## 2. ငွေသွင်း/ငွေထုတ် ပမာဏ အဝိုင်းဂဏန်း ရိုက်မရခြင်း (blocking)

`resources/views/pos/partials/modal-reporting.blade.php:270` — `min="1" step="100"`။
Browser သည် step base ကို `min` (၁) မှ တွက်သည် → **1, 101, 901, 1001 …** သာ လက်ခံ။
1,000 / 5,000 / 55,000 ရိုက်လျှင် browser က ငြင်းပြီး **Save ခလုတ် နှိပ်လို့ မရ** (တိတ်ဆိတ်စွာ ဘာမှမဖြစ်)။
browser စာ: *"The two nearest valid values are 901 and 1001."*

**ပြင်:** `min="0"` (step base 0 ဖြစ်လို့ အဝိုင်းဂဏန်း valid)။
မိသားစု ၂၄ ခုကို စစ်ပြီး ဤတစ်ခုတည်း ချိုးနေသည် (ကျန်ဟာများက step=1 / any)။

---

## 3. စာရင်းပိတ်စာမျက်နှာက ရေတွက်မထည့်မီ "ငွေလိုနေသည်" အနီပြခြင်း

`resources/views/pos/closing.blade.php` — `counted` ကို '0' မှ စတင်သောကြောင့်
စာမျက်နှာဖွင့်သည်နှင့် "Cash Shortage −395,000" အနီပြ (ရေတွက်ငွေ ၀ နှင့် expected လုံးလုံး နှိုင်းယှဉ်လို့)။

**ပြင်:** `countedTouched` flag အသစ် + badge/ကတ်/သတိပေးချက် အားလုံးကို `countedTouched` ဖြင့် guard။
- ရေတွက်မထည့်မီ → **"—"** (အရောင်ဖျော့)
- ရေတွက်ပြီး → တကယ့်ကွာဟချက် (အနီ/ဝါ/အလုံးစုံ)

> **သင်ခန်းစာ:** `x-data="..."` attribute အတွင်း **raw double-quote (") မထည့်ရ** — ပထမ edit တွင်
> comment ထဲ `"shortage"` ထည့်မိရာ attribute ပြတ်ပြီး **Alpine တစ်ခုလုံး ပျက်**သွားသည်
> (browser စစ်ဆေးမှုက ဖမ်းမိ)။

---

## 4. Menu နှင့် Route မသင့်တင့်ခြင်း (403 လင့် ၄ ခု + export)

Store Manager ၏ menu တွင် ပြသော လင့် ၄ ခုက 403 ပြန်သည်:

| လင့် | route လိုအပ် | manager role တွင် | အမှန်တကယ် |
|---|---|---|---|
| `/admin/membership` | `membership.view` | မပါ | 403 |
| `/pos/reports/reconciliation` | `stock_reconciliation.view` | မပါ | 403 |
| `/admin/settings/modules` | `store_owner` role | manager ပါ | 403 |
| `/admin/settings/channels` | `store_owner` role | manager ပါ | 403 |
| P&L XLSX/CSV export | `profit_loss.export` | **ဘယ် role မှာမှ မပါ** | 403 |

**အမြစ်အကြောင်းရင်း ၂ မျိုး:**
1. Nav gate သည် `StorePermissionService::canAny()` — **OR** semantics။ ထို့ကြောင့်
   `['stock_reconciliation.view','stock_reconciliation.edit','reports_sales.view']` ကဲ့သို့ စာရင်းတွင်
   တစ်ခုပါက လင့်ပေါ်လာ၊ route ကမူ တိကျသော key တစ်ခုကိုသာ တောင်း → 403။
2. Role template တွင် route က တောင်းသည့် key လုံးဝ မပါ (`profit_loss.export` ကို မည်သည့် role မှ မရ)။

**ပြင်:**
- Nav node များ၏ key များကို **route က တောင်းသည့် key အတိအကျ** ဖြစ်စေရန် ချိန် (membership → `membership.view`၊ reconciliation → `stock_reconciliation.view`)။
- `modules`/`channels` ကို `required_roles => ['store_owner']` (route အတိုင်း)။
- `StaffRole.php` template တွင် `store_manager` → `membership.view`, `profit_loss.export`, `stock_reconciliation.view`; `accountant` → `profit_loss.export` ထည့်။
- **ရှိပြီးသား store များအတွက်:** `php artisan staff:sync-role-permissions [--dry-run] [--store=ID]` (အသစ်၊ add-only + audit log)။

**လက်တွေ့စမ်းသပ်မှု (browser):** manager sidebar တွင် ပြသော လင့်အားလုံး **200**၊
modules/channels မပြသတော့၊ P&L export → **200 + XLSX magic `50 4b 03 04`**။

---

## 5. Manager ကိုယ်တိုင် အံဆွဲငွေထုတ် မှတ်ရာတွင် စာသား ရှုပ်ထွေးခြင်း

Guard က မှန်သည် (အံဆွဲက shift ပိုင်ရှင်၏)၊ သို့သော် စာသားက "Shift ဖွင့်ပါ" ဆိုသဖြင့်
cashier ၏ shift ဖွင့်ထားသည်ကို မြင်နေရသော manager နားလည်မှု မလွယ်။
**ပြင်:** စာသားကို "သင်ကိုယ်တိုင် ဖွဘ်ထားသော Shift လိုအပ်သည်၊ အခြားဝန်ထမ်း Shift မှ နုတ်၍မရ" ဟု
၃ ဘာသာလုံး ရှင်းလင်းစွာ ပြန်ရေး။

---

## 6. Dashboard က အတည်မပြုရသေးသော အွန်လိုင်းအော်ဒါကို ဝင်ငွေထဲ ထည့်ခြင်း

`DashboardController::revenueSumBetween()` က `pending_contact` အော်ဒါများကို ဝင်ငွေအဖြစ် ရေတွက်နေသည်။
2026-09-18 တွင် "ယနေ့ ရောင်းရငွေ **242,000**" ပြရာ တကယ်ရသော POS 217,000 + အတည်မပြုရသေး
အွန်လိုင်းအော်ဒါ 25,000 ပါနေသည်။

**ပြင်:** `REVENUE_ORDER_STATUSES = ['confirmed', 'delivered']`။ လက်တွေ့စစ်ပြီး: **217,000** ✓
(အတည်မပြုရသေးအော်ဒါအရေအတွက် ၂ ခုသည် သီးခြား stat အဖြစ် ဆက်ပြသနေသည်)။

---

## 7. Test များ

| ဖိုင် | Test | ဖော်ပြချက် |
|---|---|---|
| `tests/Feature/POS/CounterCashToDrawerTest.php` | 14 | ကောင်တာငွေ → အံဆွဲ (cash/non-cash၊ own shift/other register/ambiguous၊ period lock၊ repair advance+balance၊ zero amount) |
| `tests/Feature/Admin/MenuRoutePermissionAgreementTest.php` | 9 | role keys၊ nav visibility၊ menu==route key၊ sync command (idempotent/dry-run)၊ dashboard revenue |
| `tests/Feature/AdminDashboardTest.php` | (ရှိပြီး) | ORD-STAT-3 → confirmed၊ ORD-STAT-4 (fresh pending 500) ကို မရေတွက်ကြောင်း အတည်ပြု |

**Fail-before အထောက်အထား**
- `CounterCashToDrawerTest`: fix မတိုင်မီ **12/14 FAIL** (4 errors + 8 failures)
- `MenuRoutePermissionAgreementTest`: fix မတိုင်မီ **6/9 FAIL**

**Suite ရလဒ် (အင်ဂျင် ၂ မျိုးလုံး)**
| အင်ဂျင် | ရလဒ် |
|---|---|
| SQLite (`:memory:`) | **2089 tests PASS** |
| MySQL (`datapos_closing_fix_test`) | **2089 tests, 9619 assertions PASS** (6m19s) |

> မှတ်ချက်: SQLite ကို ပထမအကြိမ် run စဉ် `AdminDashboardTest` မှာ ၁ ခု ကျခဲ့သည် —
> ထို test က `pending_contact` အော်ဒါကို ဝင်ငွေအဖြစ် ရေတွက်သည်ဟု သတ်မှတ်ထားခဲ့သောကြောင့်
> (fix 6 နှင့် ဆန့်ကျင်)။ fixture ကို `confirmed` ပြောင်းပြီး `pending_contact` ၅၀၀ ကို
> မရေတွက်ကြောင်း တိတိကျကျ assert လုပ်သည့် အော်ဒါတစ်ခု ထပ်ထည့်ကာ ရည်ရွယ်ချက် မပျောက်စေဘဲ ပြင်ခဲ့သည်။

---

## 8. ပြင်ဆင်ထားသော ဖိုင်စာရင်း

```
app/POS/Services/CashierShiftService.php          resolveCounterDrawer(), postCounterCashIn()
app/POS/Services/CounterCashPosting.php           (အသစ်)
app/Http/Controllers/Admin/CustomerReceivableController.php
app/Http/Controllers/Admin/RepairController.php
app/Http/Controllers/Admin/ServiceJobController.php
app/Http/Controllers/Admin/DashboardController.php
app/Models/StaffRole.php                          role templates
app/Services/AdminNavigationService.php           nav gates
app/Console/Commands/SyncRoleRoutePermissions.php (အသစ်)
resources/views/pos/partials/modal-reporting.blade.php  min="0"
resources/views/pos/closing.blade.php             countedTouched
lang/{my,en,zh_CN}/messages.php                   key ၅ ခု + စာသားရှင်း (parity 5903×3)
```

## 9. မစမ်းရသေးသည် (ရိုးသားစွာ)

- **ပရင်တာ/ESC-POS လက်တွေ့ထုတ်ခြင်း** — မရှိသေးပါ (print preview သာ)။
- ဤ pass သည် **DB schema/migration အသစ် မလိုအပ်**ပါ (ကော်လံများ ရှိပြီးသား)။
- `staff:sync-role-permissions` ကို `datapos_uat` ပေါ်တွင် run ခဲ့သည် (role ၄ ခု update၊ audit log ရှိ)။
  production သို့ မတင်ရသေးပါ။
- Browser စစ်ဆေးမှုကို Store B (uat-store-b) တွင် ပြုလုပ်ခဲ့သည် — အကြောင်းရင်းမှာ
  Store A ၏ 2026-09-18 business date ကို အတည်ပြုပြီး (approved) ဖြစ်နေသောကြောင့်
  shift အသစ် ဖွင့်၍ မရတော့ခြင်းဖြစ်သည် (period lock က မှန်ကန်စွာ တားသည်)။
  Store B ရှိ ဖောက်သည်နှင့် အကြွေးကို browser စမ်းသပ်မှုအတွက် fixture အဖြစ် ဖန်တီးခဲ့သည်။
