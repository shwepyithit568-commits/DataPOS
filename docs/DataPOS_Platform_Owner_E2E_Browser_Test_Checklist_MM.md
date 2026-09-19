# DataPOS — Browser Skills ဖြင့် ဆိုင်လုပ်ငန်းစဉ် အစအဆုံး စမ်းသပ်ရန်

> Revision: 2 • ပြင်ဆင်သည့်နေ့: 2026-09-19  
> Mode: **Audit-only — Browser UAT + read-only verification**  
> ပစ်မှတ်: Local XAMPP MySQL • Mobile & Accessories Retail / Wholesale / Service  
> ဤဖိုင်တစ်ဖိုင်လုံးကို AI Agent ထံပေး၍ အသုံးပြုနိုင်သည်။ လက်ရှိ source code ကို Agent က ပထမဦးစွာ စစ်ဆေးရမည်။ ဖော်ပြထားသော route၊ role၊ table နှင့် feature များကို လက်ရှိ implementation အဖြစ် အလိုအလျောက် မယူဆရ။

## 1. Agent ထံ တိုက်ရိုက်ပေးရန် အလုပ်ညွှန်ကြားချက်

သင်သည် DataPOS အတွက် QA Engineer ဖြစ်သည်။ အောက်ပါအစီအစဉ်အတိုင်း Browser skills သုံး၍ လူတစ်ယောက် ဆိုင်ကို နေ့စဉ်အသုံးပြုသကဲ့သို့ စမ်းသပ်ပါ။ Checklist ရေးပြရုံဖြင့် မရပ်ပါနှင့်။ လုပ်ဆောင်နိုင်သော case များကို အဆုံးအထိ ဆောင်ရွက်ပြီး အထောက်အထားပါသော မြန်မာဘာသာ report ထုတ်ပါ။

- Repository: https://github.com/shwepyithit568-commits/DataPOS
- Local project: `D:\xmapp\htdocs\DataPOS` — စာလုံးပေါင်းနှင့် တကယ်ရှိသော directory ကို စစ်ပါ။ မတွေ့မှ `xampp` အမည်ကွဲကို စစ်ပါ။
- App URL: `http://127.0.0.1:8501`
- Target store: `spt-mobile` — ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ်။
- ယခင်ပြဿနာကို ပြန်စစ်ရန် reference: `/store/uat-pos-store/pos/closing`။ မူလဆိုင်ကို ရှင်းပစ်စရာမလိုဘဲ fresh test store တွင် cash-out behavior ကို ပြန်ထုတ်ပါ။
- အသုံးပြုသူက backup မလိုဟု သတ်မှတ်ထားသည်။ Backup ကို prerequisite မလုပ်ပါနှင့်။ Reset အတွက် အောက်ပါ disposable-database boundary ကို လိုက်နာပါ။
- လက်ရှိအလုပ်တွင် source code ပြင်ခြင်း၊ commit/push/deploy လုပ်ခြင်း မပါဝင်ပါ။ UAT setup နှင့် test records ဖန်တီးခြင်း ခွင့်ပြုထားသည်။ Bug တွေ့လျှင် evidence နှင့် fix handoff ရေးပါ။
- Browser ကို မရရှိနိုင်လျှင် browser cases ကို `BLOCKED` ဟု မှတ်တမ်းတင်ပါ။ Unit tests၊ SQL သို့မဟုတ် API requests ကို Browser E2E PASS ဟု မသတ်မှတ်ပါနှင့်။
- Business record များကို UI မှ ဖန်တီးပါ။ Read-only SQL/API ကို corroboration အဖြစ် သုံးနိုင်သည်။ DB ထဲ data တိုက်ရိုက်ရေးပြီး UI bug ကို ကျော်မသွားရ။
- Case တစ်ခု ပိတ်ဆို့လျှင် dependent cases ကို BLOCKED လုပ်ပြီး independent cases ကို ဆက်စမ်းပါ။ ငွေစာရင်းမျှော်မှန်းတန်ဖိုးကို actual result နဲ့ကိုက်ရန် နောက်မှ ပြန်ပြင်မထားရ။

## 2. လက်ရှိ project နှင့် ပတ်ဝန်းကျင်ကို အရင်စစ်ရန်

### ENV-01 — Source နှင့် runtime baseline

- [ ] Applicable `AGENTS.md`၊ README၊ setup docs နှင့် လက်ရှိ UAT handoff ကို ဖတ်ပါ။
- [ ] Branch၊ full HEAD SHA၊ working-tree changes ကို မှတ်ပါ။ ရှိပြီးသား user changes ကို မဖျက်ရ။
- [ ] လက်ရှိ PHP/framework၊ MySQL သို့မဟုတ် MariaDB version၊ database driver နှင့် browser version ကို မှတ်ပါ။ XAMPP label ကြောင့် database engine/version ကို မခန့်မှန်းပါနှင့်။ SQLite ဖြင့်အစားမထိုးရ။
- [ ] Routes၊ authentication၊ roles/permissions၊ shifts၊ refunds၊ expenses၊ purchases၊ repair deposits၊ closing/report services နှင့် သက်ဆိုင်ရာ models/tests ကို trace လုပ်ပါ။ Table/field အမည်ကို မခန့်မှန်းရ။
- [ ] Seeder ရှိမှုနှင့် ဖန်တီးမည့် users/stores ကို စစ်ပါ။ `UatSeeder` ဟူသောအမည်နှင့် credentials ကို အတည်မပြုဘဲ မသုံးရ။
- [ ] Browser skill ကို ဖတ်၍ browser ချိတ်ပါ။ Agent machine ၏ localhost သည် အသုံးပြုသူ PC မဟုတ်နိုင်ကြောင်း စစ်ပါ။ App မရလျှင် reachable environment ကိုဖော်ပြ၍ BLOCKED ထားပါ။
- [ ] Store timezone ကို `Asia/Yangon` ထားနိုင်မှု၊ business date၊ browser/server timezone နှင့် test စတင်ချိန်ကို မှတ်ပါ။

ဥပမာ read-only discovery commands — project နှင့် PHP executable တကယ်ရှိကြောင်း စစ်ပြီးမှ run ပါ။

```powershell
Set-Location 'D:\xmapp\htdocs\DataPOS'
git status --short
git rev-parse HEAD
php --version
php artisan --version
php artisan route:list
```

### ENV-02 — Test database နှင့် reset boundary

**Default:** သီးသန့် disposable DB `datapos_browser_uat_<runid>` တည်ဆောက်၍ လက်ရှိ app မှ အဲဒီ DB သို့ ချိတ်ထားကြောင်း အတည်ပြုပြီး migrations/verified seeders သုံးပါ။ Existing shared DB တစ်ခုလုံးကို reset မလုပ်ရ။

1. Run ID သတ်မှတ်ပါ၊ ဥပမာ `UAT-20260919-01`။ DB အမည်တွင် သင့်လျော်သော underscore ပုံစံသုံးပါ။
2. လိုအပ်သော local configuration ပြောင်းပြီး cached config ကို ရှင်းပါ။ Long-running server/worker ရှိလျှင် configuration အသစ်ကိုယူအောင် restart လုပ်ပါ။
3. CLI နှင့် web runtime နှစ်ခုလုံး၏ **effective connection/database** ကို existing safe diagnostic နည်းဖြင့် စစ်ပါ။ DB password ကို evidence ထဲ မထည့်ရ။
4. `migrate:fresh` သည် selected connection ပေါ်ရှိ tables အားလုံးကို ဖျက်ကြောင်း နားလည်ထားပြီး dedicated disposable DB ဖြစ်ကြောင်း သက်သေရှိမှ သုံးပါ။
5. Existing `datapos_uat` သို့မဟုတ် production-like DB ကို အမည်ကြည့်ရုံဖြင့် disposable ဟုမယူဆရ။ Isolated DB အသစ် မသုံးနိုင်လျှင် store အသစ်နှင့်စမ်းပြီး baseline ကို မှတ်ပါ။
6. Fresh setup ပြီး application login နှင့် DB binding ကို ပြန်စစ်ပါ။ ဤအဆင့်ပြီးမှ Browser cases စပါ။

`UatSeeder` မရှိလျှင် လက်ရှိ project ၏ supported bootstrap နည်းကို သုံးပါ။ Seeder account ကို public report တွင် password/PIN နှင့် မထုတ်ပြပါနှင့်။

### ENV-03 — Capability နှင့် route map

အောက်ပါ column များဖြင့် `capabilities.csv` ရေးပါ။

`feature, actual_route, supported, enabled_for_store, allowed_roles, evidence, requirement_gap`

- Core: products, stock, cash/noncash sale, wholesale credit, collections, refunds, expenses, shift opening/closing, reports, store isolation.
- Conditional: manager closing approval, multi-warehouse, repair lifecycle, variants, tax, forex payments, offline queue, physical print, ecommerce.
- Required feature မရှိပါက `GAP` ကို report တွင် ခွဲပြပါ။ Test status က NOT TESTED/BLOCKED ဖြစ်နိုင်သော်လည်း requirement ပြည့်သည်ဟု မယူဆရ။
- Optional feature တကယ်မသက်ဆိုင်ပါက `N/A` နှင့် အကြောင်းပြချက် ထည့်ပါ။ Core feature မရှိမှုကို N/A ဖြင့်ဖျောက်မထားရ။

## 3. Role၊ ဆိုင်နှင့် master data setup

### SET-01 — Platform မှ store ဖွင့်ခြင်း

Platform Owner ဖြင့် login → stores → create store → save → reload → store admin သို့ switch လုပ်ပါ။

| Field | Test value |
|---|---|
| Store name | ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ် |
| Slug | `spt-mobile` — ရှိပြီးသားဆို `<slug>-<runid>` အသစ်သုံးပြီး actual slug ကို case အားလုံးတွင် သုံးပါ |
| Edition | Mobile/Electronics နှင့် အနီးစပ်ဆုံး supported edition; actual value ကို မှတ်ပါ |
| Address | အမှတ် (၁၂၃)၊ လမ်း ၃၀၊ ၇၇ x ၇၈ ကြား၊ ချမ်းအေးသာစံမြို့နယ်၊ မန္တလေးမြို့ |
| Phone | `09450001122` — dummy test value |
| Language / currency | Myanmar / MMK |
| Warehouse | MAIN နှင့် AUX — **ဆိုင်တစ်ဆိုင်တည်းအတွင်း** ဂိုဒေါင်နှစ်ခု |
| Payment methods | Cash, KPay — simulated payment; တကယ့်ငွေလွှဲမလုပ်ရ |
| Expense category | ဆိုင်နေ့စဉ်အသုံးစရိတ် |

Expected: store တစ်ခုသာဖန်တီး၊ reload ပြီး data မပျောက်၊ scope switch မှန်၊ unrelated store data မပေါ်။

### SET-02 — Staff နှင့် session isolation

| Role | Test identity | Baseline responsibility |
|---|---|---|
| Platform Owner | Verified bootstrap account | Store provisioning/platform settings |
| Store Owner | UAT Store Owner | ဆိုင် settings၊ reports၊ ခွင့်ပြုထားသော approval |
| Manager | ကိုသန့်၊ `09200000011` | Stock/purchases၊ refund approval၊ expense approval၊ closing review |
| Cashier | မလှလှ၊ `09200000022` | POS၊ ခွင့်ပြုထားသော collection/deposit၊ ကိုယ့် shift closing |
| Other-store staff | ဆိုင် B အတွက် သီးခြား account | Store-isolation negative tests |

Actual role slugs/permissions ကို code နှင့် UI မှစစ်ပြီး map လုပ်ပါ။ စာရွက်ပါအမည်နှင့်မတူရုံဖြင့် bug မသတ်မှတ်ရ။ Cashier သည် platform/admin-sensitive reports ကို မဝင်နိုင်ရ။ Manager လိုသောအလုပ်ကို cashier အဖြစ် permission ချဲ့ပြီးမကျော်ရ။

Role တစ်မျိုးစီကို separate browser context/profile ဖြင့်သုံးပါ။ Tab အသစ်တစ်ခုသည် session အသစ်မဟုတ်ပါ။ Approval ပြီး cashier session/shift မှန်သေးကြောင်း ပြန်စစ်ပါ။

### SET-03 — Master Data tabs

| Tab | Records |
|---|---|
| Categories | Parent `Phone Accessories` / `ACC`; children `Chargers & Adapters` / `CHG`, `Tempered Glass` / `GLS`, `Power Banks` / `PB` |
| Brands | Remax / RMX, Apple / AAPL, Anker / ANK |
| Shelves | စင် A-01၊ စင် B-02 — Shelf သည် Warehouse နှင့် မတူ |
| Warranties | 6 months, 1 year — actual date calculation စစ်ရန် |
| Return policies | UAT Cash Refund 7 Days; No Returns; Defect Exchange Only |
| Variant presets | Color: Black/White/Titanium; Storage: 128GB/256GB/512GB |

Tab တိုင်းတွင် create → reload → edit → reload စမ်းပါ။ မသုံးရသေးသော temporary record တစ်ခုကို delete လုပ်ပြီး referenced record delete ကို application rule အတိုင်းတား/handle လုပ်ကြောင်းစစ်ပါ။ Duplicate/blank validation နှင့် category parent cycle prevention ကို စစ်ပါ။

Baseline Glass တွင် **UAT Cash Refund 7 Days** သုံးပါ။ No Returns နှင့် Exchange Only ကို သီးခြား negative cases တွင် သုံးပါ။ Policy text ရှိရုံနှင့် enforcement ရှိသည်ဟု မယူဆရ။

### SET-04 — Products

SKU/name generator ၏ actual documented rules ကိုစစ်ပါ။ Exact generated name ကို မခန့်မှန်းရ။ Test alias ကို actual product ID/SKU နှင့် map လုပ်ပါ။

| Alias | Product | Tracking | Cost | Retail | Wholesale | Barcode |
|---|---|---|---:|---:|---:|---|
| CHARGER | Remax RP-U25, Universal Type-C | Quantity | 15,000 | 25,000 | 18,000 | `UAT-CHG-001` |
| GLASS | Apple-compatible IP15P Tempered Glass | Quantity | 1,500 | 5,000 | 2,500 | `UAT-GLS-001` |
| POWERBANK | Remax RPP-292 | Serial | 35,000 | 55,000 | 42,000 | `UAT-PB-001` |

Alphanumeric internal barcode ကို supported symbology ဖြင့်သုံးပါ။ Numeric-only validation ရှိလျှင် valid supported barcode ဖြင့် mapping ကို record လုပ်ပါ။ Baseline တွင် price ကို တိတိကျကျသတ်မှတ်ပါ။ `15,000 + 66.6% = 24,990` ဖြစ်သဖြင့် markup 66.6% ဖြင့် 25,000 ထွက်ရမည်ဟု မတောင်းဆိုရ။ Rounding သီးခြားစမ်းပါ။

- CHARGER: category CHG, Remax, shelf A-01.
- GLASS: category GLS, shelf A-01, UAT Cash Refund 7 Days.
- POWERBANK: category PB, Remax, shelf B-02, 6-month warranty.
- Products သုံးခုကို save/reload/edit ဖြင့် price၊ policy၊ tracking နှင့် dropdown references မှန်ကြောင်းစစ်ပါ။
- Baseline products တွင် variants မဖွင့်ပါနှင့်။ Variant tests အတွက် သီးခြား product သုံးပါ။

## 4. Baseline စာရင်းကို တစ်မျိုးတည်းသတ်မှတ်ရန်

Baseline တွင် tax/fees/automatic promotions မပါရ။ Store-level test configuration မှ tax disabled သို့မဟုတ် applicable test exemption ကို အတည်ပြုပါ။ မလုပ်နိုင်လျှင် transaction မစမီ independent expected sheet အသစ်တွက်ပြီး assumption ကို မှတ်ပါ။ Published totals ကို အဲဒီ run အတွက် မသုံးရ။ Legal tax rate ကို မခန့်မှန်းရ။

- Whole MMK, discount 1,000 fixed, credit collection 50,000 fixed.
- Supplier payment သည် POS drawer မှမဟုတ်ဘဲ သီးခြား SAFE cash account မှဖြစ်ရမည်။ SAFE opening balance 300,000 သတ်မှတ်ပြီး payment 200,000 နုတ်လျှင် SAFE 100,000 ကျန်ရမည်။
- Drawer opening 100,000 သည် သီးခြား opening float ဖြစ်သည်။ SAFE မှလွှဲခြင်းအဖြစ် ထပ်မမှတ်ရ။ System က explicit transfer လိုအပ်လျှင် SAFE 400,000 → drawer 100,000 → supplier 200,000 ဟု transaction မစမီ alternative ကိုရေးမှတ်ပါ။ SAFE final 100,000 တူရမည်။
- Application က SAFE/out-of-shift purchasing ကို မပံ့ပိုးလျှင် procurement scenario ကို သီးခြား run ခွဲပြီး baseline ကို BLOCKED/adjusted scenario အဖြစ် သေချာဖော်ပြပါ။ Supplier cash ကို ပျောက်သွားသကဲ့သို့ မဖယ်ထုတ်ရ။
- Repair deposit သည် cash receipt ဖြစ်ပြီး repair ဝင်ငွေရရှိပြီးသားဟု အလိုအလျောက်မယူဆရ။ Report ၏ cash/accrual basis ကို record လုပ်ပါ။
- Reversal၊ void၊ extra test sale နှင့် variance tests ကို baseline shift ထဲ မရောရ။

## 5. Procurement နှင့် opening stock

### INV-01 — Opening stock

Manager ဖြင့် MAIN warehouse တွင် CHARGER 20 × 15,000 = 300,000၊ GLASS 50 × 1,500 = 75,000 သွင်းပါ။ Stock ledger နှင့် balance မှာ quantities၊ unit cost၊ warehouse/store မှန်ရမည်။ Opening stock value 375,000 ကို POS cash sale အဖြစ် မတွက်ရ။

### PUR-01 — Supplier purchase နှင့် partial payment

Supplier `မန္တလေး အီလက်ထရွန်းနစ် ကုန်တိုက်ကြီး` ဖန်တီးပါ။ POWERBANK 10 × 35,000 = 350,000 အဝယ်၊ SAFE မှ 200,000 ပေး၊ payable 150,000 ချန်ပါ။

1. Draft/order/invoice/goods receipt လက်ရှိ workflow ကိုလိုက်နာပါ။ Draft ဖန်တီးရုံဖြင့် stock တိုးမည်ဟု မယူဆရ။
2. MAIN တွင် serial `RMX-2026-001` မှ `RMX-2026-010` အထိ 10 ခုလက်ခံပါ။ Duplicate serial ကို သီးခြား reject စမ်းပါ။
3. Posted receipt ပြီး stock 10၊ available serial 10၊ payable 150,000၊ SAFE cash decrease 200,000 ကိုစစ်ပါ။
4. Refresh/retry ကြောင့် stock/payment နှစ်ကြိမ်မဝင်ရ။ Receipt ID၊ invoice ID၊ payment ID ကို record လုပ်ပါ။

### INV-02 — Same-store warehouse transfer

CHARGER 5 ခု MAIN → AUX လွှဲပါ။ Send/receive statuses နှင့် in-transit handling ကိုစစ်ပါ။ လက်ခံပြီး MAIN 15၊ AUX 5၊ store total 20၊ store stock value unchanged ဖြစ်ရမည်။ Shelf ပြောင်းခြင်းကို warehouse transfer PASS ဟု မယူဆရ။

## 6. Baseline business day — တစ်ဆင့်ပြီးတိုင်း checkpoint ယူရန်

Case တိုင်းတွင် actual invoice/payment/repair/refund/expense ID၊ actual cashier/shift ID နှင့် browser evidence ကိုမှတ်ပါ။ Total မကိုက်လျှင် အဲဒီနေရာမှာ defect ရေးပြီး dependent reconciliation ကို အောင်မြင်ဟုမသတ်မှတ်ရ။

| Case | Browser လုပ်ဆောင်ချက် | မျှော်မှန်းရလဒ် | Drawer expected |
|---|---|---|---:|
| DAY-01 | Cashier login; MAIN POS shift ကို float 100,000 နဲ့ဖွင့် | Open shift တစ်ခု၊ owner/store/register မှန် | 100,000 |
| DAY-02 | Barcode ဖြင့် CHARGER နှစ်ကြိမ်ထည့်၊ qty 2 စစ်၊ qty 1 ပြန်ထား | Scan input/cart qty မှန်၊ invoice မဖန်တီးသေး | 100,000 |
| DAY-03 | CHARGER 1 + GLASS 1 ရောင်း; Cash tender 50,000 | Sale 30,000၊ change 20,000၊ retained cash 30,000 | 130,000 |
| DAY-04 | POWERBANK 1 ကို serial RMX-2026-001 ဖြင့် KPay 55,000 ရောင်း | Cash မတိုး၊ KPay receipts 55,000၊ serial sold၊ warranty link | 130,000 |
| DAY-05 | Wholesale customer ဦးသန်းလွင် ဖန်တီး; CHARGER 5 + GLASS 20 ကို credit ရောင်း | 90,000 + 50,000 − fixed discount 1,000 = 139,000 receivable | 130,000 |
| DAY-06 | ဒေါ်ခင်စန်း၏ repair job intake; cash deposit 30,000 လက်ခံ | Job/deposit တစ်ကြိမ်သာ၊ cashier shift နှင့် link၊ earned revenue မဟုတ်သေး | 160,000 |
| DAY-07 | DAY-05 invoice အတွက် Cash collection 50,000 | Receivable 89,000၊ payment တစ်ကြိမ်သာ၊ sale revenue ထပ်မတိုး | 210,000 |
| DAY-08 | DAY-03 မှ GLASS 1 ကို return; cash refund 5,000; resellable MAIN stock သို့ပြန်ထည့် | Original line link၊ returned qty 1၊ refund 5,000၊ stock +1 | 205,000 |
| DAY-09 | Cash expense 2,000 ကို လက်ရှိ drawer မှ တစ်ကြိမ်ထုတ် | Paid expense +2,000၊ cash −2,000၊ source shift မှန် | 203,000 |

DAY-06/07/08/09 အတွက် approval လိုပါက Manager သုံး၍ approved workflow အတိုင်းလုပ်ပါ။ Approval user နှင့် ငွေကိုင် drawer owner မတူနိုင်သဖြင့် ledger association ကိုစစ်ပါ။ Expense draft ကို saved ဖြစ်ရုံနှင့် ငွေထွက်ပြီးဟုမတွက်ရ။

### DAY-10 — Receipt နှင့် persisted state

- [ ] Cash receipt တွင် gross/tax/discount/net၊ tender/change၊ invoice no၊ cashier၊ date၊ shop name မှန်။
- [ ] Shop name ကို setup အမည်အပြည့်ဖြင့် နှိုင်းပါ။ မြန်မာစာ၊ footer၊ barcode နှင့် 80mm preview ဖြတ်တောက်မှု စစ်ပါ။
- [ ] Credit invoice တွင် paid 0 / due 139,000; collection receipt ပြီး due 89,000 ဖြစ်ကြောင်းစစ်ပါ။
- [ ] Refund receipt နှင့် expense voucher ကို original transaction/shift နှင့် trace လုပ်နိုင်ရမည်။
- [ ] Save ပြီး browser reload၊ logout/login နှင့် read-only persisted records ဖြင့်စစ်ပါ။

## 7. Closing — ထွက်ငွေ၊ count၊ approval၊ snapshot

Actual closing route ကို discovery map မှသုံးပါ။ Candidate: `/store/{slug}/pos/closing`။

### CLOSE-01 — Breakdown

| Component | MMK |
|---|---:|
| Opening float | 100,000 |
| Net retained cash from sales | +30,000 |
| Repair cash deposit | +30,000 |
| Credit collection | +50,000 |
| Customer cash refund | −5,000 |
| Paid cash expense | −2,000 |
| **Expected drawer cash** | **203,000** |
| KPay — drawer ထဲမပါ | 55,000 |

- [ ] Cash inflows total 110,000၊ outflows total 7,000 ကို source IDs နှင့် အထောက်အထားပြနိုင်ရမည်။ Opening float ကို receipt revenue အဖြစ်မတွက်ရ။
- [ ] Closing UI တွင် refund 5,000 နှင့် other expense 2,000 ကို ခွဲမြင်နိုင်ရမည် သို့မဟုတ် total 7,000 မှ drill-down ဖြင့် trace လုပ်နိုင်ရမည်။ Cash-out မပေါ်ခြင်းကို UI issue နှင့် calculation issue ခွဲရေးပါ။
- [ ] Refund record က cash movement အဖြစ်ပါဝင်ပြီးသားဆို `cash_refunds` နဲ့ generic `cash_out` တွင် **နှစ်ကြိမ်မနုတ်ရ**။ Category အမည်တူမတူထက် unique economic event ကိုစစ်ပါ။
- [ ] Supplier payment 200,000 သည် SAFE ledger တွင်ရှိပြီး drawer query ထဲ မပါရ။ Other store/warehouse/register/shift ၏ ငွေကိုရောမတွက်ရ။
- [ ] Read-only ledger sum နှင့် UI တူရမည်။ UI number ကိုပြန်ကူးထားရုံနှင့် independent verification မဖြစ်ရ။

### CLOSE-02 — Count နှင့် close

Simulated physical count: 10,000 × 20 + 1,000 × 3 = **203,000**။ ဤသည် test count ဖြစ်ပြီး လူကငွေသားတကယ်ရေတွက်ထားကြောင်း မဆိုရ။

- [ ] Count 203,000၊ variance 0၊ calculated expected cash မှန်။
- [ ] Cashier shift close → reload → persisted closed status နှင့် close timestamp စစ်ပါ။
- [ ] Daily closing submission ရှိလျှင် status transitions ကို သီးခြားမှတ်ပါ။ Shift close နှင့် daily report submit ကို တစ်ခုတည်းဟုမယူဆရ။
- [ ] Manager approval ရှိလျှင် Manager context မှ approve → reload → reviewer/status/audit trail စစ်ပါ။ မရှိလျှင် capability gap/N/A ကိုသက်ဆိုင်သလိုဖော်ပြပါ။
- [ ] Print/export ထွက်သည့် snapshot သည် approved/closed figures နှင့်တူပြီး နောက် shift ကြောင့် တိတ်တဆိတ်ပြောင်းမသွားရ။ Correction/reopen supported ဖြစ်လျှင် authorized audited workflow သုံးရမည်။
- [ ] Double submit/reload ကြောင့် duplicate closing မဖန်တီးရ။ Closed shift ထဲ transaction အသစ်မဝင်ရ; supported correction rules ကိုသီးခြားစစ်ပါ။

## 8. Independent reconciliation — Baseline နောက်ဆုံးအဖြေ

Tax/fee မရှိ၊ တစ်ခုတည်းသော cost layer၊ sale/refund completed၊ transfer same-store၊ repair intake only ဟူသော Section 4 assumptions အောက်တွင်သာ အောက်ပါအဖြေများကို သုံးပါ။

| Check | Expected | Independent calculation |
|---|---:|---|
| Drawer cash | 203,000 | 100,000 + 110,000 − 7,000 |
| KPay receipts | 55,000 | DAY-04 only |
| Wholesale invoice | 139,000 | 5×18,000 + 20×2,500 − 1,000 |
| Customer receivable | 89,000 | 139,000 − 50,000 |
| Supplier payable | 150,000 | 350,000 − 200,000 |
| Repair deposit pending earning | 30,000 | DAY-06 only |
| CHARGER total / MAIN / AUX | 14 / 9 / 5 | 20 − 1 − 5; transfer 5 |
| GLASS total / MAIN | 30 / 30 | 50 − 1 − 20 + 1 |
| POWERBANK total / MAIN | 9 / 9 | 10 − 1 |
| Inventory value | 570,000 | 14×15,000 + 30×1,500 + 9×35,000 |
| MAIN / AUX inventory value | 495,000 / 75,000 | MAIN includes remaining glass and power banks |
| Net goods sales | 219,000 | 30,000 + 55,000 + 139,000 − 5,000 |
| Net goods COGS | 155,000 | 6×15,000 + 20×1,500 + 1×35,000 |
| Goods gross profit | 64,000 | 219,000 − 155,000 |
| Scenario profit after expense | 62,000 | 64,000 − 2,000; excludes unearned repair deposit |

P&L က cash-basis report ဖြစ်လျှင် accrual goods-sales table နဲ့ မတူမှုကို accounting definition အရရှင်းပြပါ။ Customer collection၊ supplier principal payment၊ opening float၊ repair deposit တို့ကို sales/expense အဖြစ် အလိုအလျောက်ထပ်မတွက်ရ။

Report filters (store, warehouse, date, timezone, status) တူအောင်ထားပြီး UI၊ export၊ underlying records ကို နှိုင်းပါ။ Warehouse qty မှန်ပေမယ့် store total မှားခြင်းကိုလည်း defect အဖြစ်မှတ်ပါ။

## 9. Baseline နှင့်ခွဲ၍ စမ်းသပ်မည့် regression cases

Case တစ်ခုချင်းစီအတွက် isolated shift/product/customer သုံးပါ။ Shared balance ကိုပေါင်းပြောင်းစေမည့် tests ကို တစ်ပြိုင်တည်းမလုပ်ပါနှင့်။

| Case | Scenario | Acceptance |
|---|---|---|
| REG-01 | Save/pay/collect/expense ကို double-click သို့မဟုတ် retry | Business transaction တစ်ကြိမ်သာ; duplicate stock/cash/debt မဖြစ် |
| REG-02 | No Returns ပစ္စည်း cash refund; Exchange Only ကို cash refund | Documented policy/authorized override အတိုင်း; rejection ကြောင့် stock/cash မပြောင်း |
| REG-03 | Original sold qty ထက်ပို return၊ တူညီသော refund ထပ်လုပ် | Reject; cumulative returned qty မကျော် |
| REG-04 | Sold/duplicate serial ထပ်ရောင်း၊ မှားသော store serial သုံး | Reject; serial/stock/payment တစ်ခုချင်း မပျက် |
| REG-05 | Stock 1 ကို browser contexts နှစ်ခုမှ တပြိုင်နက်ရောင်း | Configured stock rule ကို server ကထိန်း; unintended oversell/duplicate serial မရှိ |
| REG-06 | Receipt post/payment submit အတွင်း request failure | Partial stock/payment မကျန်; retry safe; console/network evidence ရှိ |
| REG-07 | Shift float 10,000၊ actual count 9,000 / 11,000 ကို သီးခြား shift နှစ်ခုတွင်စမ်း | Variance actual−expected = −1,000 / +1,000; reason/approval rule မှန် |
| REG-08 | Shift ပိတ်ပြီး sale/expense တင်; closing ထပ်တင် | Closed record ကာကွယ်; supported correction တွင် audit trail ရှိ |
| REG-09 | ညသန်းခေါင်အနီး transaction/business date filter | Yangon business date မှန်; other day/shift ထဲမရော; global machine clock မပြောင်း |
| REG-10 | Invalid/negative qty၊ discount > allowed total၊ blank input | Validation နှင့် permissions မှန်; valid zero/decimal rules ကို project policy အတိုင်းစစ် |
| REG-11 | Product price edit ပြီး invoice အဟောင်းကြည့် | Historical unit price/discount/tax/snapshot မပြောင်း |
| REG-12 | Partial supplier payment နှင့် purchase return | Payable/stock/cash refund ခွဲမှန်; debit note ကို cash refund အဖြစ်မယူဆ |

### SEC-01 — Permissions နှင့် store isolation

Cashier/Store Owner/Manager/Platform Owner တို့၏ actual permissions matrix ကိုရေးပြီး UI visibility **နှင့် server enforcement နှစ်ခုလုံး** စစ်ပါ။

- Cashier ဖြင့် profit/cost report၊ settings၊ price adjustment၊ platform routes တိုက်ရိုက်ဝင်ပါ။ Menu ပျောက်ခြင်းတစ်ခုတည်းကို PASS မပေးရ။
- ဆိုင် A staff ဖြင့် ဆိုင် B slug၊ invoice/product/customer/expense/closing IDs ကို request တွင်ပြောင်း၍ read/write/export စမ်းပါ။ Controlled UAT records ကိုသာသုံးပါ။
- Denied request သည် 403/404 သို့မဟုတ် safe equivalent ဖြစ်ပြီး data leak/side effect မရှိရ။ Same-store valid request ကို control အဖြစ်ထားပါ။
- Permission revoke လုပ်ပြီး existing session က stale permission ဖြင့် ဆက်လုပ်နိုင်မနိုင်စစ်ပါ။ CSRF protection ကို bypass/disable မလုပ်ရ။
- Service module disabled ဆိုင်တွင် menu/search/quick-link/direct endpoint ကို capability rule အတိုင်းစစ်ပါ။ Platform Owner ၏ authorized access ကို cross-store vulnerability ဟုမယူဆရ။

### SRV-01 — Service lifecycle (သီးခြား scenario)

Service enabled store တွင် repair intake → estimate 50,000 → customer approval → spare part 1 ခု (test cost 10,000) သုံး → repair completed → delivery → final collection စမ်းပါ။

- Deposit 20,000 cash၊ remaining payment 30,000 cash; total collected 50,000.
- Quote 50,000 သည် parts အပါအဝင် fixed total ဖြစ်ကြောင်း သတ်မှတ်; tax/fee မပါ။ Spare part opening qty 2 → consumed 1 → remaining 1.
- Status မပြောင်းခင်/ပြောင်းပြီး stock consumption ဘယ်အချိန်ဖြစ်ကြောင်း documented rule နှင့်စစ်ပါ။ Double complete ကြောင့် parts ထပ်မနုတ်ရ။
- Deposit offset မှန်၊ due 0၊ receipt total 50,000၊ recognized revenue timing မှန်၊ deposit ကို revenue ထပ်မတိုးရ။
- Cancellation/deposit refund ကို အခြား job ဖြင့်သီးခြားစမ်းပြီး reason/approval/cash movement စစ်ပါ။

### EXT-01 — Tax၊ discount၊ forex၊ variants

- Tax: approved project rule/rate၊ inclusive/exclusive mode၊ line/cart discount order နှင့် rounding ကို transaction မစမီ လက်ဖြင့်တွက်ပါ။ Partial return ပြီး tax/discount reversal နှင့် tax report ကိုနှိုင်းပါ။ Test rate ကို ဥပဒေနှုန်းဟုမဖော်ပြရ။
- Forex: test-only USD 4,500 MMK နှင့် THB 135 MMK rates သိမ်း/reload စစ်ပါ။ Foreign-payment supported ဆို 45,000 MMK sale ကို USD 10 ဖြင့်ပေးသည့် သီးခြား case စမ်းပါ; original currency၊ rate snapshot၊ MMK equivalent၊ change rule ကိုစစ်ပါ။ Rate ပြောင်းပြီး invoice အဟောင်းမပြောင်းရ။ Rate setting ရှိရုံနှင့် forex payment PASS မပေးရ။
- Variants: သီးခြား product တွင် Color/Storage combinations ဖန်တီး၊ duplicate combination validation၊ per-variant SKU/price/stock၊ selection ပြောင်းလျှင် stale fields မကျန်မှုကိုစစ်ပါ။ Parent stock နှင့် variant stock double count မဖြစ်ရ။
- Markup: 15,000 × 1.666 = 24,990 ကို base result အဖြစ်ထားပြီး configured round-to-10/100/etc rule ရှိမှ rounded expectation တွက်ပါ။

### EXT-02 — Offline နှင့် hardware

Offline capability ကို အရင်ရှာပါ။ Local XAMPP ကိုဖွင့်ထားပြီး internet ဖြုတ်ခြင်းသည် app server မရတော့ခြင်းနှင့် မတူပါ။ Test failure mode ကိုမှတ်ပါ။

- Supported offline: cached lookup/cart၊ queue ID၊ reconnect sync၊ repeated sync၊ logout/reload recovery၊ stock conflict၊ serial conflict၊ payment deduplication စစ်ပါ။ Final invoice/stock/payment တစ်ကြိမ်သာရှိရ။ သီးခြား records သုံးပါ။
- Unsupported offline: server unavailable တွင် clear error၊ unsaved state indication၊ silent fake-success မရှိမှု စစ်ပါ။ Offline sale ကို PASS မပေးရ။
- Scanner: keyboard barcode + Enter သည် input emulation ဖြစ်သည်။ Physical scanner မသုံးလျှင် hardware case ကို NOT TESTED လုပ်ပါ။
- Printer: browser 80mm preview/PDF တွင် Myanmar font၊ totals၊ clipping စစ်ပါ။ Physical paper output၊ ESC/POS encoding၊ cutter/cash drawer trigger တို့ကို hardware မရှိဘဲ PASS မပေးရ။
- အသိပေးစာ၊ real payment၊ customer message မပို့ရ။ Test-only integrations/mocks ရှိမှသုံးပြီး mocked result ဟုဖော်ပြပါ။

## 10. Sidebar coverage နှင့် responsive UI

Menu စာရင်းသည် coverage inventory သာဖြစ်သည်။ Page ဖွင့်လို့ရခြင်းကို CRUD/business workflow PASS အဖြစ် မသတ်မှတ်ရ။ Actual build အရရှိသည့် menus ကိုအောက်ပါ groups နှင့် map လုပ်ပါ။

| Scope / group | စစ်ဆေးရန် inventory |
|---|---|
| Platform | Dashboard, Stores, Theme Governance |
| Store dashboard / POS | Dashboard, POS, Closing, Sales Returns, Buyback, E-Load |
| Inventory | Master Data, Products, Barcode, Price Wizard, Warranty, Stock Ledger/Balance/Count/Adjustments/Reconciliation, Opening Stock, Import/History |
| Purchasing | Suppliers, Purchases, Purchase Returns, Payables, Transfers, Warehouses |
| Ecommerce | Orders, Web Products, Promotions, Reviews, Banners, Blog, Pages, Navigation, Glass Finder, Web Push/History |
| Customers | Directory, Receivables, Wholesale Applications, Membership |
| Service | Repair Center, Spare Parts, Service Settings |
| Finance | Profit & Loss, Expenses, Expense Categories, Transactions |
| Reports | Sales, Payments, Analytics, Cash, Valuation, Debt Aging, Service, Tax, Stock, Business Reconciliation |
| Security | Roles & Permissions, Users, Audit Logs |
| Maintenance | Alerts, Database, Sync, Backups, Pilot Import — destructive tools ကို smoke-test အတွက်မနှိပ်ရ |
| Setup | Theme, Store Settings, Modules, Channels, Branches, Printers, Vouchers, Exchange Rates |

တစ်မျက်နှာစီ actual route၊ role၊ enabled state၊ page-load result၊ tested interaction၊ evidence၊ untested scope ကို မှတ်ပါ။ Unsupported/not enabled menus ကို reason ဖြင့်ခွဲပါ။

Responsive checks: POS၊ products form/list၊ refund၊ expense၊ closing၊ sidebar ကို **375×812, 768×1024, 1366×768** တွင် စမ်းပါ။

- Page-level horizontal overflow၊ ဖြတ်နေသော action buttons၊ sidebar overlay၊ modal close/scroll၊ keyboard focus၊ Burmese long labels စစ်ပါ။
- Table အတွင်း horizontal scroll သည် ရည်ရွယ်ထားလျှင် လက်ခံနိုင်သည်။ Whole page overflow နှင့် ခွဲပါ။
- Empty/loading/validation/error states၊ dark/light supported themes နှင့် duplicate-submit prevention ကိုစစ်ပါ။
- Failed network requests၊ console errors၊ relevant server log excerpts ကို case နဲ့ချိတ်ပါ။ Secret/token/password မပါစေရ။

## 11. Evidence၊ checkpoints နှင့် report format

Test directory: `docs/uat/<runid>/` သို့မဟုတ် project ကသတ်မှတ်ထားသော evidence directory။

လိုအပ်သော output များ:

1. `README_MM.md` — outcome၊ environment/HEAD၊ scope၊ gate decision၊ မစမ်းရသေးသောအပိုင်း။
2. `case-results.csv` — `case_id,role,store,shift,viewport,status,expected,actual,record_ids,evidence,notes`.
3. `capabilities.csv` — actual routes၊ supported/enabled features၊ role mapping.
4. `reconciliation.csv` — each expected/actual/difference နှင့် source transaction IDs.
5. `defects.md` — reproducible bugs နှင့် prioritized fix handoff.
6. `screenshots/`၊ လိုအပ်သော sanitized network/log/SQL excerpts.
7. `checkpoint.md` — last completed case၊ active sessions/shift IDs၊ remaining cases၊ restart instructions.

### Test status

| Status | သတ်မှတ်ချက် |
|---|---|
| PASS | Expected result နှင့် actual result တူပြီး evidence ရှိ |
| FAIL | Test ကို run ပြီး actual behavior က expected rule မကိုက် |
| BLOCKED | Prerequisite/tool/access/earlier failure ကြောင့် မစမ်းနိုင် |
| NOT TESTED | Scope ထဲပါသော်လည်း မစမ်းရသေး |
| N/A | Capability/profile အရ အမှန်တကယ်မသက်ဆိုင်; reason လို |

`GAP` သည် requirement classification ဖြစ်ပြီး PASS အစားမဟုတ်ပါ။ Screenshots သည် UI evidence ဖြစ်ပြီး persistence/ledger correctness အတွက် record IDs/read-only checks နှင့်တွဲသုံးပါ။

### Defect template

```markdown
### BUG-001 — [တိုတောင်းသော ခေါင်းစဉ်]
- Severity: P0 / P1 / P2 / P3
- Case ID / HEAD / environment:
- Role / store / shift / URL / viewport:
- Preconditions + exact record IDs:
- Reproduction steps:
- Expected:
- Actual:
- Business impact:
- Evidence links:
- Suspected code path / root cause: confirmed or hypothesis
- Smallest proposed fix:
- Regression checks after fix:
- Status: open / fixed by another change / retest pending
```

P0: store isolation breach၊ severe data corruption။ P1: cash/stock/debt wrong၊ duplicate payment၊ checkout/closing blocked။ P2: usable workaround ရှိသော workflow/report/UI issue။ P3: cosmetic issue။ အကျိုးသက်ရောက်မှုအရချိန်ညှိပြီး severity ကို အထောက်အထားနှင့်ရေးပါ။

Agent context/time limit နီးလာလျှင် checkpoint ရေးပါ။ နောက် agent သည် အဲဒီ state မှဆက်ရမည်။ Database reset သို့မဟုတ် baseline transactions ပြန်လုပ်ခြင်းကြောင့် duplicate မဖြစ်စေရ။

## 12. Completion gate နှင့် fix handoff

အောက်ပါတို့ ပြည့်မှ **“စမ်းသပ်ထားသော scope အတွင်း UAT PASS”** ဟုရေးပါ။

- Core business day နှင့် closing အဆင့်များ actual browser evidence ဖြင့် PASS.
- Cash၊ noncash၊ receivable၊ payable၊ stock/serial၊ refund/expense reconciliation မှန်.
- Permissions/store isolation အတွက် UI + server checks PASS.
- Required P0/P1 cases တွင် unresolved FAIL/BLOCKED မရှိ.
- Required capability gaps၊ hardware/offline မစမ်းရသေးမှုနှင့် report scope ကို ရှင်းလင်းဖော်ပြထား.

**Overall result:** PASS / FAIL / INCOMPLETE. Required case BLOCKED/NOT TESTED ဖြစ်လျှင် INCOMPLETE; confirmed core failure ရှိလျှင် FAIL. Optional exclusions ကိုဖော်ပြပြီး scope-limited verdict သာပေးပါ။

Code changes မလုပ်ထားလျှင် “Source changes: none; commit/push/deploy: not performed” ဟုရေးပါ။ User က နောက်မှ fix ခွင့်ပြုလျှင် defect IDs ကိုအခြေခံ၍ reproduce → root cause → smallest fix → regression test → affected browser retest အတိုင်းဆက်ပါ။ Existing architecture၊ authorization၊ store isolation၊ money/quantity formatting ကိုထိန်းပါ။ Shared services/routes/migrations ပြင်ခြင်းကို အစီအစဉ်တကျလုပ်ပြီး unrelated refactor မလုပ်ရ။

Final Burmese report တွင် PASS/FAIL/BLOCKED/NOT TESTED/N/A counts၊ အဓိကတွေ့ရှိချက်၊ closing expected/actual၊ unresolved bug priorities၊ evidence directory နှင့် နောက်ဆက်လုပ်ရန် case IDs ကို ပေးပါ။ “Production ready” ကို ဤစမ်းသပ်မှုတစ်ခုတည်းကြောင့် မကြေညာရ။
