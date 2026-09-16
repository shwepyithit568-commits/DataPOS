# DataPOS — UAT-POS-STORE ဆိုင်၏ XAMPP MySQL Browser UAT

ဤဖိုင်ကို local project သို့ဝင်ရောက်နိုင်သော AI Agent အား အစအဆုံးဖတ်ပြီး လုပ်ဆောင်ရန် ပေးပါ။ Checklist ရေးပေးရုံဖြင့် မရပ်ပါနှင့်။ အမှန်တကယ် Browser ဖြင့်ဆောင်ရွက်ပြီး evidence နှင့် အစီရင်ခံပါ။

**Revision 2 — GitHub code reviewed; backup မယူရန် user က တိတိကျကျညွှန်ကြားထားသည်။**

## 1. တာဝန်၊ ရည်ရွယ်ချက်နှင့် အခွင့်အာဏာ

သင်သည် DataPOS အတွက် QA / UAT Agent ဖြစ်သည်။ မြန်မာပြည်တွင်း Mobile & Accessories လက်လီ/လက်ကား အရောင်းနှင့် ဖုန်းပြုပြင်ရေးဆိုင်တစ်ဆိုင်၏ နေ့စဉ်လုပ်ငန်းကို လက်တွေ့အသုံးပြုပုံအတိုင်း စမ်းသပ်ပါ။

- Repository reference: https://github.com/shwepyithit568-commits/DataPOS
- User ပေးထားသည့် local project: `D:\xmapp\htdocs\DataPOS`
- Target store name: `UAT-POS-STORE`
- Database: local XAMPP MySQL-compatible server အစစ်။ SQLite ဖြင့်အစားထိုးပြီး PASS မပေးရ။ XAMPP တွင် MariaDB ဖြစ်ပါက actual engine/version ကိုတိတိကျကျဖော်ပြပါ။
- Mode: **Audit + authorized local UAT data reset + browser-driven UAT**။ Application source bug များကို report လုပ်ပါ။ ဤတာဝန်အရ business code ကို အလိုအလျောက်ပြင်ရန် မဟုတ်ပါ။ Reversible local runtime/config ပြင်ဆင်မှုနှင့် scoped reset/test helper များကို လိုအပ်သလောက်သာ လုပ်နိုင်သည်။
- Authorized: local target store ၏စမ်းသပ် operational/master data ကို backup မယူဘဲ ရှင်းရန်၊ synthetic UAT data အသစ်ဖြည့်ရန်၊ Browser မှ create/edit/sale/purchase/refund/service/close လုပ်ရန်။
- Not authorized: production/remote DB ပြောင်းခြင်း၊ အခြားဆိုင်ဒေတာရှင်းခြင်း၊ repository commit/push/deploy၊ broad source refactor၊ real SMS/Viber/email/payment ပို့ခြင်း။ Integrations ကို local sandbox/stub mode ဖြင့်သာစမ်းပါ။
- GitHub main မှ ဖတ်ရှုခဲ့သော files နှင့် pinned code reference များကို Section 9 တွင်ပေးထားသည်။ Code review သည် local Browser UAT ပြီးမြောက်မှု မဟုတ်ပါ။ Local HEAD ကွာလျှင် diff အရ အောက်ပါ mapping ကိုပြန်စစ်ပါ။
- User အတည်ပြုချက်: ဆိုင်ထဲတွင် test data များသာရှိသည်။ Backup/export dump/restore rehearsal မလုပ်ရန်၊ backup မရှိခြင်းကြောင့် မရပ်ရန်၊ backup permission ထပ်မတောင်းရန်။ AGENTS.md ၏ destructive-action confirmation အတွက် ဤတိကျသော store-scoped reset ကို user က ခွင့်ပြုထားပြီးဖြစ်သည်။

ရှင်းလင်းပြီးသား scope အတွက် ထပ်ခါတလဲလဲ permission မတောင်းပါနှင့်။ Target identity၊ local DB boundary သို့မဟုတ် shared-data ownership မသေချာမှသာ သက်ဆိုင်ရာ destructive step ကိုရပ်ပြီး တိကျသော blocker ကိုတင်ပြပါ။ ကျန် read-only စစ်ဆေးမှုကို ဆက်လုပ်ပါ။

## 2. စမလုပ်မီ local project နှင့် runtime စစ်ဆေးရန်

1. ပေးထားသော `D:\xmapp\htdocs\DataPOS` ရှိမရှိ စစ်ပါ။ မရှိလျှင် `D:\xampp\htdocs\DataPOS` စသည့် candidate ကိုစစ်နိုင်သော်လည်း typo ဟုယူဆပြီး တိတ်တဆိတ် project မပြောင်းပါနှင့်။ Repository remote နှင့် project identity ကိုတိုက်စစ်ပြီး actual path မှတ်တမ်းတင်ပါ။
2. `AGENTS.md`, README, လက်ရှိ setup/UAT docs နှင့် module rules ကိုဖတ်ပါ။ `git status --short`, `git branch --show-current`, `git rev-parse HEAD`, `git remote -v` ဖြင့် baseline ရယူပါ။ User ၏ uncommitted work ကို မပယ်ဖျက်ပါနှင့်။ Auto pull/reset/checkout မလုပ်ပါနှင့်။
3. Dependency manifests/lockfiles၊ routes၊ controllers/services၊ models၊ policies၊ migrations၊ seeders နှင့် tests ကိုစစ်ပြီး actual stack/runtime versions ကိုဖော်ပြပါ။ Blade/Alpine/Tailwind ရှိပါက လက်ရှိပုံစံကို လိုက်နာပါ။
4. XAMPP Apache/PHP/MySQL status၊ actual PHP binary/extensions၊ DB port၊ DB name၊ app URL၊ queue/cache/session drivers၊ upload disk တို့ကိုစစ်ပါ။ Secrets/password များကို output/screenshot/report ထဲမထည့်ပါနှင့်။
5. `.env` တစ်ခုတည်းကြည့်ပြီး မဆုံးဖြတ်ပါနှင့်။ Running app ၏ effective DB connection ကိုစစ်ပါ။ Host သည် local ဖြစ်ပြီး remote forwarding/production connection မဟုတ်ကြောင်း၊ app/browser requests နှင့် CLI သည် DB တူကြောင်း အတည်ပြုပါ။
6. Actual database engine/version, SQL mode, storage engines, charset/collation, timezone ကိုစစ်ပါ။ မြန်မာစာအတွက် Unicode round-trip စမ်းပါ။ App ဆိုင်အချိန် `Asia/Yangon` နှင့် DB stored time ကို ဘယ်လိုပြောင်းသုံးသည်ကိုမှတ်ပါ။
7. Existing migrations status နှင့် required setup steps စစ်ပါ။ Shared DB schema ကိုအပြောင်းအလဲဖြစ်စေမည့် migration များကို မဆင်မခြင်မ run ပါနှင့်။ Pending migration က UAT ကိုပိတ်ဆို့လျှင် exact blocker နှင့် isolated alternative ကိုတင်ပြပါ။
8. Local base URL ကို config/server response မှရှာပါ။ `127.0.0.1:8501` သို့မဟုတ် URL အဟောင်းကို မယူဆပါနှင့်။ Login နှင့် test roles ရရှိမှုကိုစစ်ပါ။ Authentication လိုအပ်လျှင် advertised browser authentication flow ကိုသုံးပြီး credentials မဖော်ပြပါနှင့်။
9. Applicable Browser skill ကိုဖတ်ပြီး browser capability ကိုသုံးပါ။ မရှိလျှင် available Playwright workflow ကိုသုံးပြီး အကြောင်းပြပါ။ Browser လုံးဝမရလျှင် Browser UAT ကို BLOCKED ဟုသတ်မှတ်ပါ။ API/SQL စမ်းခြင်းဖြင့် browser PASS မအစားထိုးပါနှင့်။

## 3. UAT-POS-STORE ဒေတာကို လုံခြုံစွာ အစမှပြန်စရန်

### 3.1 Exact target နှင့် reset plan

- Store name အပြင် immutable store ID၊ slug၊ tenant/owner နှင့် local DB ကို cross-check လုပ်ပါ။ Name duplicate ရှိလျှင် မခန့်မှန်းပါနှင့်။ Store မရှိလျှင် unrelated store ကို rename/reset မလုပ်ပါနှင့်။
- Schema/relations မှ ownership dependency map တည်ဆောက်ပါ။ Direct `store_id` မရှိသော child tables များကို parent relation ဖြင့် scope သတ်မှတ်ပါ။ Soft-deleted rows၊ pivots၊ uploads၊ ledgers၊ jobs/cache/search entries တို့ပါစစ်ပါ။
- Sales/items/payments/refunds/returns၊ purchases/receipts/supplier payments/returns၊ stock movements/counts/reservations/serials၊ service jobs/parts/deposits၊ cash shifts/expenses/receivables/payables၊ customers/suppliers၊ store-owned products/variants/brands/categories/presets စသည်တို့အတွက် **ရှိသမျှ actual tables** ကိုစစ်ပါ။ စာရင်းထဲရှိတိုင်း feature ရှိသည်ဟုမယူဆပါနှင့်။
- Target store shell/identity၊ login access၊ permissions၊ required settings ကို ထိန်းထားပါ။ Platform/global/shared entities နှင့် အခြားဆိုင်တွင်အသုံးပြုနေသော users/catalog/media များကို မဖျက်ပါနှင့်။ Store-only links/data ကိုသာရှင်းပါ။ Audit retention rule ရှိပါက audit trails ကိုထိန်းပြီး reset event အသစ်မှတ်ပါ။ Retained records/reasons ကိုအတိအကျဖော်ပြပါ။
- Table တစ်ခုစီအတွက် ownership predicate၊ planned delete count၊ retained count၊ dependency order ပါသော dry-run manifest ကိုရေးပါ။

### 3.2 Backup မယူဘဲ scoped deletion execution

1. **Backup မယူရ။** SQL dump၊ ZIP snapshot၊ restore rehearsal ကို ဤ UAT အတွက်မခိုင်းရ။ User က test data ဖြစ်ကြောင်း အတည်ပြုထားသည်။ Dry-run row counts သည် data backup မဟုတ်ဘဲ scope verification သာဖြစ်သည်။
2. Target store အတွက် concurrent browser/queue writes မရှိစေပါ။ အခြားဆိုင်ကိုမပြင်ပါနှင့်။ Exact store ID နှင့် effective local MySQL DB ကိုစစ်ပြီး ဆက်လုပ်ပါ။
3. Existing reviewed store-scoped reset service ရှိပါက ownership behavior ကိုဖတ်ပြီးသုံးပါ။ မရှိလျှင် schema-derived parameterized helper ဖြင့် child-first deletion လုပ်ပါ။ Target assertion၊ expected counts၊ InnoDB transaction/rollback ပါစေ။ Nontransactional table ရှိလျှင် rollback ကတိမပေးဘဲ actual limitation မှတ်ပါ။
4. Active/shared application DB တွင် `migrate:fresh`, `db:wipe`, DROP DATABASE, whole-table TRUNCATE, unscoped DELETE, global foreign-key disabling မသုံးရ။ Shared sequence/counter မရှင်းရ။ Section 9 ၏ dedicated disposable migration-smoke database သည်သီးခြားကိစ္စဖြစ်သည်။
5. Target-only uploads များကို shared references မရှိကြောင်းစစ်ပြီး DB commit နောက်မှရှင်းပါ။ Global/session/cache tables အကုန်မရှင်းရ။ Target-bound stale cart/hold/cache များသာရှင်းပါ။
6. Before/after counts၊ orphan checks နှင့် unrelated store critical data fingerprints ကိုတိုက်စစ်ပါ။ Target identity/login access/permissions နှင့် required store config ကိုထိန်းပါ။ Store-owned service master data ကိုရှင်းလျှင် Browser setup မှပြန်တည်ဆောက်ပါ။
7. Browser မှ empty products/transactions/stock/balances ကိုစစ်ပါ။ Retained config/audit exceptions မှတ်တမ်းတင်ပါ။ UAT data ပြန်ဖြည့်ပြီးနောက် reset ကို အလိုအလျောက်ထပ်မလုပ်ပါနှင့်။

## 4. စမ်းသပ်ဒေတာနှင့် expected ledger

Run ID ကို `UAT-YYYYMMDD-HHMM` ပုံစံဖြင့်သတ်မှတ်ပါ။ Synthetic names/reference များတွင် Run ID ထည့်ပါ။ Real customer ဖုန်း၊ IMEI၊ bank account မသုံးပါနှင့်။ Identity validation ရှိလျှင် clearly documented test fixtures ကိုသုံးပါ။

### 4.1 Browser မှတည်ဆောက်မည့် setup

- MMK currency၊ supported money precision/rounding၊ business date/timezone၊ receipt identity နှင့် payment methods ကိုစစ်ပါ။
- Cash, KPay, WavePay, CB Pay, MMQR တို့ကို app ကထောက်ပံ့သလောက် configure လုပ်ပြီး manual-recorded payment နှင့် provider-confirmed payment ကွာခြားမှုကိုဖော်ပြပါ။ Real transfer မလုပ်ရ။
- Owner/Manager/Cashier/Technician role များကို actual permission model နှင့် mapping လုပ်ပါ။ Missing roles/features ကို GAP ဟုရေးပါ။ မိမိဘာသာ feature အသစ်မထည့်ပါနှင့်။
- Retail walk-in/customer၊ wholesale customer၊ credit customer၊ service customer နှင့် suppliers ၂ ဦး အနည်းဆုံးထားပါ။
- Stock/serial/variant အမျိုးအစားစုံရန် အောက်ပါ fictional fixtures ကိုသုံးနိုင်သည်။ ဈေးနှုန်းများသည် UAT arithmetic အတွက်သာဖြစ်ပြီး ဈေးကွက်ပေါက်ဈေး မဟုတ်ပါ။

| Fixture | Model/type | Cost MMK | Retail MMK | Wholesale MMK | Purchase qty |
|---|---|---:|---:|---:|---:|
| PHONE-A | Serial/IMEI phone, 8/128 Black | 400,000 | 450,000 | 430,000 | 3 |
| PHONE-B | 8/256 Blue variant | 500,000 | 560,000 | 535,000 | 2 |
| CABLE-A | Type-C Cable Black | 3,000 | 5,000 | 4,000 | 20 |
| CABLE-B | Type-C Cable White | 3,200 | 5,500 | 4,500 | 10 |
| CHARGER-A | 20W Charger | 10,000 | 15,000 | 12,000 | 10 |
| CASE-A | Model-specific Phone Case | 2,000 | 4,000 | 3,000 | 10 |
| GLASS-A | Screen Protector | 1,000 | 3,000 | 2,000 | 10 |
| PART-A | Repair Battery Part | 15,000 | 25,000 | 20,000 | 5 |

Create/save/reload/edit/search ကို Browser မှလုပ်ပါ။ Stock ကို opening balance နှင့် purchase receipt နှစ်မျိုးလုံးထပ်ပေါင်းပြီး double-count မလုပ်ပါနှင့်။ Baseline fixtures အတွက် stock စတင် 0၊ purchase receipt မှအထက် quantity များဝင်စေပါ။ Fixtures အားလုံး၏ purchase cost စုစုပေါင်းသည် **2,497,000 MMK** ဖြစ်သည်။ Purchase tax/fees မပါသည့် baseline ဖြစ်သည်။

### 4.2 Application နှင့်သီးခြား expected calculations

UI result ကိုပဲကူးပြီး expected value မသတ်မှတ်ပါနှင့်။ Money calculation အတွက် decimal arithmetic သုံးပြီး independent ledger ရေးပါ။

- Opening till cash = **100,000 MMK**။
- Retail baseline: CABLE-A 2 × 5,000 + CHARGER-A 1 × 15,000 = **25,000**။ Tax off၊ discount off၊ cash received 30,000၊ change **5,000**၊ net till increase **25,000**။
- Wholesale baseline: CABLE-A 5 × 4,000 = **20,000**၊ KPay manual record၊ till cash increase **0**။
- Credit baseline: PHONE-A 1 × 450,000၊ cash deposit **100,000**၊ receivable **350,000**။ နောက် cash collection **150,000** ပြီး receivable **200,000**။ Collection သည် sales revenue အသစ် မဖြစ်ရ။
- Retail baseline မှ CABLE-A 1 ကို cash refund **5,000**၊ sellable stock သို့ 1 ပြန်ဝင်။ Original payment/return limits ကိုလေးစားပါ။
- Service baseline (ရှိလျှင်): PART-A 1 × 25,000 + labor 10,000 = **35,000**၊ cash deposit 10,000 + completion payment 25,000။ Part consumption 1 ကြိမ်သာ၊ deposit/revenue double-count မဖြစ်ရ။
- Cash expense = **5,000**။
- အထက်လှုပ်ရှားမှုများသာရှိပြီး purchase ကို supplier credit ဖြင့်ထားလျှင် ဆိုင်စုစုပေါင်း physical cash = **400,000 MMK**။ Service cash ကိုသီးခြားနေရာတွင်သိမ်းလျှင် POS drawer နှင့် service cash book ကိုခွဲပါ။ **400,000 ကို cashier shift UI အတွက် အလိုအလျောက် expected မသတ်မှတ်ရ**။ Debt collection/service payment မှ shift ထဲသို့ posting ရှိမရှိ Section 9 အတိုင်း trace လုပ်၍ drawer expectation သီးခြားတွက်ပါ။ Additional scenarios ကိုသီးခြား shift/run section တွင်ထား၍ baseline နှင့် မရောပါနှင့်။
- Baseline ending stock: PHONE-A 2, PHONE-B 2, CABLE-A 14, CABLE-B 10, CHARGER-A 9, CASE-A 10, GLASS-A 10, PART-A 4 (service supported) / 5 (service unavailable)။
- Baseline outstanding supplier payable = **2,497,000 MMK** (supplier payment/return မလုပ်ရသေးသည့်အချိန်)။
- Baseline net goods sales = **490,000 MMK**၊ service completed ပါလျှင် total **525,000 MMK**။ Actual report ၏ sales/service recognition rule ကိုရှင်းပြ၍သီးခြားတိုက်စစ်ပါ။

Tax cases ကို baseline ပြီးမှသီးခြားလုပ်ပါ။ Tax rate/order သည် approved app configuration မှသာယူပါ။ “Myanmar standard” ဆိုပြီး 5% သို့မဟုတ် ဥပဒေလိုက်နာမှုကို မခန့်မှန်းပါနှင့်။ Tax-off, configured tax-on, inclusive/exclusive (ရှိသမျှ), line/cart discount interaction, rounding နှင့် refund tax reversal ကိုစစ်ပါ။ Business rule မသတ်မှတ်ထားလျှင် ambiguity အဖြစ်တင်ပြပါ။

## 5. Browser ဖြင့် နေ့စဉ်ဆိုင်လုပ်ငန်း စမ်းသပ်ရန်

အောက်ပါ test IDs ကိုသုံးပြီး action → expected → actual → persistent DB/ledger check → evidence ကိုရေးပါ။ Core workflow ကို direct SQL/API inserts ဖြင့်အစားမထိုးပါနှင့်။ Browser ရလဒ်ကို read-only DB query ဖြင့်အတည်ပြုနိုင်သည်။

| ID | လက်တွေ့လုပ်ငန်းနှင့် အဓိကစစ်ဆေးရန် |
|---|---|
| ENV-01 | Login/logout၊ correct store selection၊ effective XAMPP DB၊ UTF-8 မြန်မာစာ save/reload/search |
| SET-01 | Settings save/reload၊ receipt details၊ payment configuration၊ roles၊ missing/disabled Service navigation |
| CAT-01 | Brand/category/product/variant create/edit၊ SKU/barcode uniqueness၊ Burmese names၊ image upload၊ invalid/duplicate input |
| PUR-01 | Supplier → purchase → receive → stock/cost/payable update၊ partial receive နှင့် duplicate receive prevention |
| POS-01 | Cashier shift open၊ search/barcode input၊ cart quantity၊ remove/add၊ exact cash/overpay/insufficient cash၊ receipt |
| POS-02 | Retail/wholesale switch နှင့် customer pricing၊ quantity threshold ရှိလျှင် boundary၊ unauthorized price override |
| POS-03 | Line/cart fixed/percentage discounts၊ 0/100%/negative/over-total limits၊ tax order၊ fractional rounding၊ server recalculation |
| POS-04 | Hold/resume/cancel sale ရှိလျှင် cart/customer/price persistence၊ browser refresh/back နှင့် duplicate receipt prevention |
| PAY-01 | Cash + supported digital methods၊ split/partial payment ရှိလျှင် balances၊ pending/failed payment handling |
| CRD-01 | Credit sale၊ customer statement၊ later collections၊ overpayment handling၊ receivable aging/date filters |
| RET-01 | Full/partial return၊ repeated return rejection၊ refund limit၊ tax/discount prorating၊ resellable/damaged stock disposition |
| SER-01 | Service intake → diagnosis → estimate → approval → parts use → repair → ready → handover → payment၊ actual supported state sequence |
| SER-02 | Customer/device/serial/complaint/accessories/warranty/technician data၊ service cancellation၊ unused parts return၊ deposit refund rules |
| INV-01 | Variant-specific stock၊ serial unique identity၊ stock count/adjustment reason၊ movements၊ reorder/low stock |
| SUP-01 | Supplier payment/statement၊ purchase return၊ payable adjustment၊ already sold/used serial/stock safeguards |
| RPT-01 | Sales/payment/tax/discount/return/stock/profit/service/receivable/payable reports၊ dates/filter/export totals agreement |
| NAV-01 | Reports menu restructure ရှိလျှင် role visibility၊ active state၊ links၊ direct URL access၊ disabled modules |
| CLS-01 | Expense၊ expected versus counted cash၊ shift close၊ reprint၊ closed-shift restrictions၊ next-day reopen |
| TEN-01 | Other-store records ကို list/search/direct URL/export မှမရနိုင်ခြင်း၊ server-level authorization |
| RES-01 | Double-click submit၊ two-tab stock contention၊ request interruption၊ refresh/retry၊ stale cart/price handling |
| UI-01 | Phone/tablet/desktop layouts၊ dialogs၊ touch controls၊ Burmese labels၊ keyboard focus၊ print preview |

### Scenario အသေးစိတ်

**ဆိုင်ဖွင့်ချိန်** — Owner ဖြင့် setup ပြီး Cashier session အသစ်ဖြင့် till ဖွင့်ပါ။ Owner login cache ကြောင့် cashier အား admin rights မကျန်ရ။ Technician/customer data access ကို actual role policy နှင့်စစ်ပါ။

**ပစ္စည်းအဝင်** — Purchase နှင့် receipt သီးခြားရှိလျှင် အမှာတင်ရုံဖြင့် stock မတိုးရသည့် rule ကိုစစ်ပါ။ Partial/duplicate receipts၊ supplier credit/payment၊ quantity/cost validation၊ invoice price update feature ရှိလျှင် permission/variant matching/transaction atomicity ကိုစစ်ပါ။

**အရောင်း** — အပေါ်က baseline ကိုအရင်ပြီးစီးပါ။ ထို့နောက် discount/tax/mixed tender/void/hold/stock-out စမ်းပါ။ Serial item တစ်ခုကိုအရောင်းနှစ်ခုသို့ ခွင့်မပြုရ။ Non-serial item last unit ကို browser contexts နှစ်ခုမှပြိုင်ဝယ်ပြီး app policy အရ oversell ကာကွယ်မှုနှင့် transaction outcome ကိုစစ်ပါ။ Negative-stock policy ရှိလျှင် အတည်ပြုထားသော rule ဖြင့်သာဆုံးဖြတ်ပါ။

**ပြန်အမ်း** — မူလ invoice ကိုရှာ၍ partial return လုပ်ပါ။ Net paid ထက်ကျော် refund မဖြစ်ရ၊ returned quantity ထက်ထပ်မအမ်းရ၊ credit sale return သည် receivable/cash ကိုမှန်စွာပြင်ရ၊ defective item ကို sellable stock သို့ မရည်ရွယ်ဘဲပြန်မထည့်ရ။

**ပြုပြင်ရေး** — GitHub တွင် Repairs routes/controller ရှိပြီးဖြစ်သည်။ Target store capability `service.repair_jobs` ဖွင့်ထားခြင်းနှင့် permissions ကိုစစ်ပြီး actual UI ဖြင့်စမ်းပါ။ Local code တွင်မရှိလျှင် **GAP: required Mobile Sale & Service workflow unavailable** ဟုတင်ပြပြီး sales UAT ကိုဆက်လုပ်ပါ။ Service မစမ်းရခြင်းကို PASS မသတ်မှတ်ရ။ Intake deposit၊ parts reservation/consumption timing၊ labor revenue၊ cancelled job refund နှင့် warranty revisit ကို app rule အရစစ်ပါ။ Device PIN/password ကို report မထည့်ရ။

**ဆိုင်ပိတ်ချိန်နှင့် နောက်နေ့** — till count၊ cash variance၊ digital method totals၊ outstanding debts၊ sales net of returns ကိုလွတ်လပ်သော ledger နှင့်တိုက်ပါ။ Browser/app server restart ပြီး transaction/customer/stock persistence ကိုစစ်ပါ။ Date boundary ကို system clock ပြောင်းမလုပ်ဘဲ supported business-date/filter/test-clock mechanism ရှိသလောက်စမ်းပါ။ 23:59/00:00 Yangon date filtering ကိုမစမ်းနိုင်လျှင် NOT RUN ဟုရေးပါ။

**Hardware** — Barcode text entry စမ်းခြင်းကို physical scanner PASS မခေါ်ရ။ Print preview/ PDF ကို actual 58/80mm printer PASS မခေါ်ရ။ Physical scanner/printer/cash drawer/customer display မရလျှင် MANUAL REQUIRED ဟုသတ်မှတ်ပါ။

## 6. Permission၊ resilience နှင့် UI evidence

- Unrelated store ကိုပြင်စရာမလိုဘဲ existing read-only canary record တစ်ခုဖြင့် tenant isolation စစ်ပါ။ Other-store update/delete ကို စမ်းမည့်အစား denied access/read checks ကိုအရင်သုံးပါ။ Actual writes လိုအပ်သည့် isolation test ကို isolated test DB တွင်သာလုပ်ပါ။
- Cashier မှ restricted report/cost/settings/refund/price edit များကို menu ဖျောက်ထားရုံမဟုတ်ဘဲ direct URL/server response ဖြင့်စစ်ပါ။ Expected permissions ကို code/policy မှအရင်သတ်မှတ်ပါ။
- Double submission/retry သည် sale၊ payment၊ stock movement နှစ်ခါမဖြစ်ရ။ Network interruption နောက် state ကိုစစ်ပြီးမှ retry လုပ်ပါ။
- Browser console၊ failed requests (status/route)၊ relevant app/MySQL logs ကိုစစ်ပါ။ Tokens/cookies/customer secrets မသိမ်းပါနှင့်။
- Exact viewports: 375×812, 768×1024, 1366×768။ 320px သို့မဟုတ် 1440px+ ကို issue/requirement ရှိမှထပ်စမ်းပါ။ Actual tested sizes ကို report တွင်ရေးပါ။
- POS/cart/checkout၊ product form၊ service form၊ reports၊ sidebar၊ modals တွင် horizontal page overflow၊ clipped buttons၊ Burmese text၊ amount alignment၊ table scroll၊ loading/empty/error feedback စစ်ပါ။ Theme supported ဖြစ်လျှင် dark/light စစ်ပါ။
- Screenshot ကို test ID/run ID နှင့်ချိတ်ပါ။ Important before/after၊ invoice/refund/service/close/report screenshots နှင့် sanitized failure traces သိမ်းပါ။

## 7. Findings နှင့် bug priorities

Bug တွေ့လျှင် root cause ကို actual routes → handlers/services → models/policies → DB transaction အထိ trace လုပ်ပါ။ Confirmed finding နှင့် suspected risk ကိုခွဲပါ။ ဤ audit scope တွင် app source ကို မပြင်ပါနှင့်။ Smallest safe fix proposal နှင့် targeted regression test ကိုရေးပါ။

| Priority | သတ်မှတ်ချက် |
|---|---|
| P0 | Other-store data leakage/change၊ data loss၊ duplicate charging၊ critical security/tenant failure |
| P1 | Checkout/return/service completion ပိတ်ဆို့၊ stock/cash/tax/receivable မှား၊ financial report reconciliation မညီ |
| P2 | Workaround ရှိသော workflow/permission usability/report-filter/export ပြဿနာ |
| P3 | Cosmetic/spacing/text ပြဿနာ၊ လုပ်ငန်းမပိတ်ဆို့ |

Finding တစ်ခုစီတွင် ID၊ severity၊ module/URL၊ branch/HEAD၊ role၊ preconditions/test data IDs၊ exact reproduction steps၊ expected/actual၊ evidence path၊ log/query reference၊ root cause confidence၊ impacted records နှင့် proposed verification ပါရမည်။ P0 တွေ့လျှင် ဆက်စမ်းခြင်းက ဒေတာပျက်စီးစေမည့် branch ကိုရပ်၍ evidence ထိန်းပါ။ Independent safe tests ကိုဆက်လုပ်နိုင်သည်။

Existing tests/build/lint commands ကို project မှရှာပြီး relevant gates ကို run ပါ။ Test DB သီးသန့် local ဖြစ်ကြောင်း အရင်စစ်ပါ။ RefreshDatabase/destructive test hooks သည် active UAT/shared DB ကိုမထိရ။ Baseline failures ကို သီးခြားတင်ပြပါ။ Green automated tests တစ်ခုတည်းဖြင့် browser UAT အောင်မြင်သည်ဟု မပြောရ။

## 8. Checkpoint နှင့် အဆုံးသတ် deliverables

Project instruction နှင့်မဆန့်ကျင်သည့် `uat-artifacts/<run-id>/` သို့မဟုတ် သီးခြား local output directory တစ်ခုတွင် အောက်ပါတို့သိမ်းပါ။ DB backup ဖိုင်မဖန်တီးပါနှင့်။ Artifacts ကိုမ commit ပါနှင့်။

1. `UAT_REPORT_MM.md` — မြန်မာလို outcome၊ tested runtime/base URL၊ store ID၊ branch/HEAD၊ scope၊ financial/stock reconciliation၊ priorities၊ limitations။
2. `UAT_CASES.csv` — ID, scenario, role, preconditions, expected, actual, status, evidence, bug_id။
3. `UAT_BUGS.md` — reproducible findings နှင့် smallest fix proposals။
4. `UAT_LEDGER.csv` — document/reference, datetime, type, SKU, qty_delta, sales_net, cash_delta, digital_delta, receivable_delta, payable_delta, expected, actual, difference။ လိုအပ်လျှင် ledger tables ကို သီးခြားခွဲနိုင်သည်။
5. `RESET_MANIFEST.md` — exact target၊ no-backup user authorization၊ per-table before/deleted/retained/after၊ other-store verification၊ retained exceptions။ Secrets မပါရ။
6. `evidence/` — actual browser screenshots၊ sanitized logs၊ invoice/report exports။
7. `UAT_PROGRESS.md` — completed/pending/blocked test IDs၊ last transaction IDs၊ next action။ Session ပြတ်လျှင် ဒီဖိုင်မှဆက်ပါ။ Store ကိုအစမှ ထပ် reset မလုပ်ပါနှင့်။

Test statuses: **PASS / FAIL / BLOCKED / NOT RUN / GAP / N/A**။ N/A တွင်အကြောင်းပြချက်လိုသည်။ Required Service feature မရှိခြင်းကို N/A နောက်ကွယ်မဖျောက်ရ။ Test count၊ browser actions၊ screenshots မတီထွင်ရ။

### Acceptance criteria

- Authorized target store သာ user ညွှန်ကြားသည့်အတိုင်း backup မယူဘဲ reset ဖြစ်ခြင်း၊ other-store/global data မပြောင်းကြောင်း evidence ရှိခြင်း။
- XAMPP MySQL-compatible engine အစစ်ပေါ် core browser flows save/reload/restart အထိလုပ်ခြင်း။
- Baseline stock/cash/digital/receivable/payable/report values သည် independent ledger နှင့်ညီခြင်း။ Differences အားလုံးကိုရှင်းပြနိုင်ခြင်း။
- Retail၊ wholesale၊ purchase၊ return၊ credit၊ service (required)၊ close-day workflow coverage နှင့် permissions/tenant checks ပြည့်ခြင်း။ Unsupported required feature ရှိလျှင် scope-wide PASS မပေးရ။
- Required cases မစမ်းရသေးလျှင် သို့မဟုတ် unresolved P0/P1 ရှိလျှင် **NOT READY** သို့မဟုတ် **BLOCKED** ဟုတင်ပြခြင်း။ PASS သည် tested local scope အတွက်သာဖြစ်ပြီး production readiness အပြည့်အဝဆိုလိုခြင်း မဟုတ်။
- အဆုံးတွင် UAT-created data ကိုဆိုင်ထဲထားခဲ့ရန်။ User ပြန်ကြည့်နိုင်ရန် final store state၊ key receipt/job IDs နှင့် report paths ပေးရန်။ နောက်တစ်ခါ reset/cleanup အလိုအလျောက်မလုပ်ရန်။
- Actual changed local configs/helpers/artifacts၊ DB impact၊ commands/results၊ unverified hardware နှင့် **commit: not performed / push: not performed / deploy: not performed** ကိုဖော်ပြရန်။

အခု inspect → target verification/dry-run → scoped reset → browser setup → baseline day → edge cases → reconciliation → report အစဉ်အတိုင်း လုပ်ဆောင်ပါ။ Plan ထုတ်ပေးပြီးသာ မရပ်ပါနှင့်။

## 9. GitHub code ကိုအခြေခံထားသည့် မဖြစ်မနေစစ်ရမည့်အချက်များ

### 9.1 Review reference နှင့် local version

Reviewed reference: [`e77f4b0e8204041476f40064bed19d39a18ef8df`](https://github.com/shwepyithit568-commits/DataPOS/commit/e77f4b0e8204041476f40064bed19d39a18ef8df), commit title: `docs: write the shop owner/manager E2E audit prompt and complete the docs index`။ GitHub default branch သည် `main` ဖြစ်သည်။ ဤ reference ကို latest forever ဟုမယူဆပါနှင့်။ Local SHA နှင့်ကွာခြားချက်ကို မှတ်ပြီး local code ကိုအမှန်တကယ်စမ်းပါ။

README ဖော်ပြချက်တစ်ခုတည်းကို PASS evidence မယူထားပါ။ အောက်ပါ actual files ကိုဖတ်ရှု၍ ဤ UAT instructions ကိုညှိထားသည်။ GitHub code ကိုမပြင်ထားပါ၊ local XAMPP/Browser UAT ကိုဤနေရာမှ မ run ထားပါ။

| Source | တွေ့ရှိချက်နှင့် Agent လုပ်ရန် |
|---|---|
| [AGENTS.md](https://github.com/shwepyithit568-commits/DataPOS/blob/main/AGENTS.md) | XAMPP path သည် `D:\xmapp` ဖြစ်ကြောင်းပါသည်။ ပေးထားသော path ကို typo ဟုမယူဆရ။ မြန်မာလို report၊ actual end-to-end evidence၊ currency/quantity helpers နှင့် tri-lingual checks ကိုလိုက်နာရန်။ |
| [composer.json](https://github.com/shwepyithit568-commits/DataPOS/blob/main/composer.json) | PHP `^8.2`, Laravel `^12.0`, Livewire `^4.3`။ Exact installed versions ကို lock/runtime မှစစ်ရန်။ `composer setup` က key generation/migration ပါသောကြောင့် existing project ပေါ် မျက်စိမှိတ်မ run ရ။ |
| [config/database.php](https://github.com/shwepyithit568-commits/DataPOS/blob/main/config/database.php) | Default SQLite; explicit mysql/mariadb connections၊ DB_URL၊ strict mode၊ utf8mb4 ရှိသည်။ `pdo_mysql` နှင့် effective connection ကိုစစ်ရန်။ |
| [phpunit.xml](https://github.com/shwepyithit568-commits/DataPOS/blob/main/phpunit.xml) | Tests default သည် SQLite `:memory:` ဖြစ်သည်။ Green suite ကို MySQL UAT PASS ဟုမယူရ။ |
| [UatSeeder.php](https://github.com/shwepyithit568-commits/DataPOS/blob/main/database/seeders/UatSeeder.php) | `datapos-mobile` နှင့် `uat-store-b` ကိုကိုင်တွယ်ပြီး roles/products/opening inventory/orders ဖြည့်သည်။ `UAT-POS-STORE` အတွက် scoped reset/reseed tool မဟုတ်။ ဤ task တွင် whole seeder မ run ရ။ |
| [routes/web.php](https://github.com/shwepyithit568-commits/DataPOS/blob/main/routes/web.php) | Actual POS/repair/service/receivable/report routes နှင့် permission/capability middleware ရှိသည်။ Browser paths ကို target slug ဖြင့်ဆောက်ရန်။ |

### 9.2 XAMPP MySQL migration test ကို အမှန်တကယ် run ရန်

[tests/Feature/MysqlMigrationSmokeTest.php](https://github.com/shwepyithit568-commits/DataPOS/blob/e77f4b0e8204041476f40064bed19d39a18ef8df/tests/Feature/MysqlMigrationSmokeTest.php) ကို local တွင်ပြန်ဖတ်ပါ။

- Test သည် `MYSQL_TEST_HOST`, `MYSQL_TEST_PORT`, `MYSQL_TEST_USERNAME`, `MYSQL_TEST_PASSWORD` ကိုသုံးသည်။ XAMPP actual settings ဖြင့် local process environment မှပေးပါ။ Password ကို command history/report မထည့်ပါနှင့်။
- `pdo_mysql` မရှိလျှင် သို့မဟုတ် connect မရလျှင် test က **SKIP** လုပ်သည်။ SKIP ကို PASS ဟုမပြောရ။
- Test သည် `datapos_migrate_smoke` ကို **DROP DATABASE IF EXISTS** လုပ်ပြီးဖန်တီး၊ `migrate:fresh` run၊ ပြီးလျှင်ပြန် drop လုပ်သည်။ ထို DB သည်မရှိသေးကြောင်းနှင့် active app DB မဟုတ်ကြောင်း အရင်စစ်ပါ။ Existing unknown DB ဖြစ်နေလျှင် မ run ဘဲ သီးခြား disposable MySQL instance သုံးပါ သို့မဟုတ် blocker တင်ပြပါ။
- ဤ test တစ်ခုတည်းအတွက် dedicated disposable scratch DB create/drop ကိုခွင့်ပြုသည်။ Active UAT DB ကို wipe လုပ်ရန်ခွင့်ပြုခြင်းမဟုတ်ပါ။ Backup မယူရ။
- Preflight ပြီးလျှင် `php artisan test tests/Feature/MysqlMigrationSmokeTest.php` ကို local XAMPP PHP ဖြင့် run ပြီး exact output သိမ်းပါ။
- `composer validate --strict`, `composer check-platform-reqs`, `npm run build` နှင့် relevant existing tests ကို run ပါ။ Full suite ထဲမှာလည်း အဆိုပါ destructive scratch test ပါနိုင်သဖြင့် preflight အရင်ပြီးစေရန်။

### 9.3 Actual Browser entry points

`BASE=http://127.0.0.1:<actual-port>/store/<actual-UAT-POS-STORE-slug>` ဟုသတ်မှတ်ပါ။ Name ကို slug အဖြစ်မခန့်မှန်းရ။ README canonical port 8501/fallback 8502 ရှိသော်လည်း running server ကိုစစ်ရမည်။

| Workflow | Actual route suffix |
|---|---|
| POS / shift | `/pos`, POST `/pos/shifts`, POST `/pos/shifts/{shift}/close` |
| Cart discount | POST `/pos/cart/discount` |
| Sale refund | `/pos/sales/{sale}/refund`, POST `/pos/sales/{sale}/refunds` |
| Purchase lifecycle | `/pos/purchases`, `/pos/purchases/create`, POST `/pos/purchases/{purchaseOrder}/order`, `/receive`, `/cancel`, `/return`, `/pay` |
| Supplier payable | `/pos/purchases/payables` |
| Receivables | `/admin/receivables`, `/admin/receivables/{customer}`, POST `/admin/receivables/{customer}/collect` |
| Mobile repairs | `/admin/repairs`, `/admin/repairs/create`, POST `/admin/repairs/{repair}/payments`, `/items/{item}/deduct` |
| Repair master data | `/admin/service-settings` |
| General service jobs | `/admin/service-jobs` — mobile repair နှင့်သီးခြား route ရှိသည် |
| Customer repair tracking | `/track/service`, `/track/service/{token}` |
| Reports | `/pos/reports/sales`, `/cash`, `/stock`, `/services`, `/payments`, `/tax`, `/reconciliation` |
| Tax aliases | `/admin/reports/tax`, `/admin/reports/commercial-tax`, `/reports/tax` |
| Closing | `/pos/closing`, `/pos/closing/x-report`, `/pos/reports/daily-closing` |

POST paths များသည် UI action tracing အတွက်ပေးထားခြင်းဖြစ်ပြီး Browser clicks/forms ကို request replay ဖြင့် အစားထိုးရန်မဟုတ်ပါ။

### 9.4 Tax/discount/refund regression checks

[PosSaleService.php](https://github.com/shwepyithit568-commits/DataPOS/blob/e77f4b0e8204041476f40064bed19d39a18ef8df/app/POS/Services/PosSaleService.php) ၏ `cartTotals`, `post`, `holdCart`, `resumeHeld`, `clearCart` ကိုစစ်ပါ။ Cart/discount session keys တွင် store ID ပါသည်။

- `enable_tax` default false၊ `default_tax_rate` fallback 5.0၊ `tax_type` fallback exclusive ရှိသည်။ ဤ 5.0 သည် code default သာဖြစ်ပြီး ဥပဒေအရဆိုင်တိုင်းသုံးရမည့် rate ဟု မဆိုလိုပါ။
- လက်ရှိ `cartTotals` က line tax ကိုအရင်တွက်ပြီး cart discount ကိုနောက်မှနုတ်သည်။ Exclusive tax တွင် `subtotal + tax - discount` ဖြစ်သည်။ Discount ကြောင့် tax base ကိုလျှော့မထားသည့် behavior ကို requirement/business-rule validation နှင့်ခွဲစစ်ပါ။
- Repro fixture: taxable subtotal 10,000၊ configured rate 5၊ fixed cart discount 1,000 → current code expected exclusive tax 500 / total 9,500; inclusive tax 476.19 / total 9,000။ ဤကိန်းများသည် observed formula အတွက် regression oracle ဖြစ်ပြီး tax-law approval မဟုတ်ပါ။ Independent business expectation ကိုသီးခြားမှတ်ပါ။
- Default rate၊ product override၊ exempt line၊ rate 0၊ mixed cart၊ 2-decimal truncation/rounding၊ hold/resume၊ customer tier switch၊ checkout/reprint/export တို့တွင် same amounts ဖြစ်မဖြစ်စစ်ပါ။
- Fixed discount > total၊ negative၊ scientific notation `1e3`၊ comma-formatted input၊ fractional qty ကို UI/server validation မှ expected 4xx/validation error ဖြင့် reject/normalize လုပ်သင့်သည့်နေရာတွင် 500 မဖြစ်ရ။
- [PosReturnService.php](https://github.com/shwepyithit568-commits/DataPOS/blob/e77f4b0e8204041476f40064bed19d39a18ef8df/app/POS/Services/PosReturnService.php) သည် original item tax နှင့် prorated order discount ကိုအသုံးပြုသည်။ Partial returns အကြိမ်ကြိမ်၊ mixed tax rates၊ fractional allocation remainder၊ full refund ceiling နှင့် credit reversal ကိုစစ်ပါ။

### 9.5 Repairs / debt collections / drawer အကြား integration

[RepairController.php](https://github.com/shwepyithit568-commits/DataPOS/blob/e77f4b0e8204041476f40064bed19d39a18ef8df/app/Http/Controllers/Admin/RepairController.php) တွင် `addPayment` က `ServiceJobPayment` ရေးပြီး allowed methods သည် `cash,kpay,wavepay,cb_pay,mmqr` ဖြစ်သည်။ `deductItem` က `service_consumption` movement နှင့် `is_deducted` flag ကိုသုံးပြီး terminal job deduction ကိုပိတ်ထားသည်။

- Repair completed မှသာ auto-deduct ဖြစ်မည်ဟု မယူဆရ။ Actual **Deduct Part** UI ဖြင့် terminal မဖြစ်မီ part deduct လုပ်ပါ။ Double click/retry/concurrency တွင် stock တစ်ကြိမ်သာလျော့ရ။ Deducted line ကို edit/delete လုပ်၍ history မပျောက်ရ။
- [CustomerReceivableController.php](https://github.com/shwepyithit568-commits/DataPOS/blob/e77f4b0e8204041476f40064bed19d39a18ef8df/app/Http/Controllers/Admin/CustomerReceivableController.php) ၏ collection methods သည် `cash,kpay,wave,bank,other` ဖြစ်သည်။ Repair နှင့် debt methods ၏ `wavepay`/`wave` ကွာခြားမှုကို report grouping တွင်တိုက်စစ်ပါ။
- [CustomerDebtService.php](https://github.com/shwepyithit568-commits/DataPOS/blob/e77f4b0e8204041476f40064bed19d39a18ef8df/app/POS/Services/CustomerDebtService.php) သည် customer ledger collection ရေးသည်။ [CashierShiftService.php](https://github.com/shwepyithit568-commits/DataPOS/blob/e77f4b0e8204041476f40064bed19d39a18ef8df/app/POS/Services/CashierShiftService.php) တွင် cash sale/refund နှင့် cash-in/out သီးခြား APIs ရှိသည်။
- ဖတ်ထားသော payment entry methods ထဲတွင် shift update တိုက်ရိုက်မတွေ့ရသဖြင့် **potential integration gap** အဖြစ် observer/listener/model hooks နှင့် Browser actual result ကိုထပ်စစ်ပါ။ Confirmed bug ဟုကြိုတင်မရေးရ။
- Baseline cash ကို POS sale/refund၊ debt collection၊ repair collection၊ expense အလိုက် bucket ခွဲပါ။ တစ် drawer တည်းအမှန်တကယ်သိမ်းသော requirement ရှိလျှင် 400,000 နှင့် reconcile လုပ်နိုင်ရမည်။ UI mismatch ကိုဖုံးရန် arbitrary cash-in adjustment ထည့်မလုပ်ရ။ Documented manual transfer workflow ရှိမှ reference ဖြင့်သုံးပါ။
- Repair payment overpay/concurrent submissions၊ outstanding balance၊ status transitions၊ tracking token privacy၊ receipt print နှင့် services report ကိုတစ်ဆက်တည်းစမ်းပါ။

### 9.6 Purchase၊ report၊ reopening နှင့် runtime security

- [PurchaseOrderService.php](https://github.com/shwepyithit568-commits/DataPOS/blob/e77f4b0e8204041476f40064bed19d39a18ef8df/app/POS/Services/PurchaseOrderService.php) တွင် `create`, `markOrdered`, `receive`, `update`, `delete`, `returnItems`, `applyPayment`, `applyPaymentFifo` ရှိသည်။ PO discount/delivery fee၊ supplier payable၊ received PO edit/delete reversals၊ invoice price-update permissions ကိုစမ်းပါ။ Partial receiving သည်actual UI မထောက်ပံ့လျှင် invented workflow မလုပ်ဘဲ coverage gap မှတ်ပါ။
- Close shift → new shift open → close again ကိုမဖြစ်မနေစမ်းပါ။ First close တစ်ခါအောင်ရုံဖြင့်မပြီးရ။ Register lock/closed-shift state နှင့် daily closing approval ကိုစစ်ပါ။
- Reports navigation ရှိ legacy/tax aliases၊ permissions၊ date filters၊ XLSX/CSV နှင့် return-adjusted P&L ကို XAMPP DB ပေါ်တိုက်စစ်ပါ။ Missing columns/invalid SQL/strict-mode errors များကို actual log ဖြင့်ဖမ်းပါ။
- Apache DocumentRoot သည် app `public/` ကိုညွှန်ကြောင်းစစ်ပါ။ Local `.env` exposure စမ်းရာ status နှင့် redacted finding သာမှတ်၍ secret body မသိမ်းရ။ GET/read-only unauthenticated checks ဖြင့် sensitive endpoints ကိုစစ်ပါ။ 200/422 တစ်ခုတည်းဖြင့် auth bypass ဟုမဆုံးဖြတ်ဘဲ actual response/business access ကိုစစ်ပါ။
- External internet ပိတ်ထားသော်လည်း localhost/XAMPP ကိုမပိတ်ဘဲ POS asset loading/checkout/receipt ကိုစမ်းပါ။ External fonts/CDN requests ကြောင့် UI ပျက်မပျက်စစ်ပါ။ Offline sync endpoints ကိုတွေ့လျှင် auth/store scoping စစ်ပြီး real external sync မပို့ရ။
- Latest commit ၏ audit document ထဲက historical bugs/test counts သည် current runtime findings မဟုတ်ပါ။ ပြန် reproduce မလုပ်ရသေးသည့်အရာကို FAIL ဟုမရေးရ။
