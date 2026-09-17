# DataPOS — Closing ထွက်ငွေ၊ Expenses နှင့် Cash Drawer စစ်ဆေးပြင်ဆင်ရန်

သင်သည် ဤ DataPOS local project ကို လက်တွေ့စစ်ဆေးပြင်ဆင်မည့် AI Coding Agent ဖြစ်သည်။ Audit + Fix + Browser Verification ကို ပြီးဆုံးအောင်လုပ်ပါ။ Plan သာပြန်မပေးပါနှင့်။

## 1. Project၊ ရည်ရွယ်ချက်နှင့် ကန့်သတ်ချက်

- Project: `D:\xmapp\htdocs\DataPOS`
- Repository: https://github.com/shwepyithit568-commits/DataPOS
- Target: http://127.0.0.1:8501/store/uat-pos-store/pos/closing
- Store: `UAT-POS-STORE`, slug `uat-pos-store`။ Numeric ID ကို လက်ရှိ DB မှ အတည်ပြုပါ။
- Environment: Local XAMPP MySQL/MariaDB။ ယခင် UAT DB အမည် `datapos_uat` ဖြစ်သော်လည်း write မလုပ်မီ actual connection ကို အတည်ပြုပါ။ Credentials မဖော်ပြပါနှင့်။
- Scope: Admin/POS expense entry → payment source → drawer/shift linkage → X-Report → shift close → daily closing → သက်ဆိုင်ရာ detail/export/print။
- ရည်ရွယ်ချက်: နုတ်ထားသည့်ထွက်ငွေကို ရှင်းရှင်းလင်းလင်းမြင်ရပြီး မှန်သည့်အံဆွဲမှ တစ်ကြိမ်သာနုတ်ရန်။
- Backup မယူပါနှင့်၊ backup ခွင့်ပြုချက်လည်း မတောင်းပါနှင့်။ Existing UAT data ကို reset/reseed/delete မလုပ်ပါနှင့်။ `migrate:fresh`, `db:wipe`, table truncation မလုပ်ပါနှင့်။
- အခြားဆိုင်/production DB မပြင်ပါနှင့်။ Target store အတွင်း run ID ဖြင့် ခွဲခြားထားသော စမ်းသပ် transaction အသစ်များကိုသာ လိုသလောက်သုံးပါ။
- Existing user edits ကို ထိန်းသိမ်းပါ။ Local fix သာလုပ်ပါ။ ဤ task တွင် commit/push/deploy မလုပ်ပါနှင့်။
- လိုအပ်သော non-destructive migration ရှိလျှင် ရည်ရွယ်ချက်၊ compatibility နှင့် data impact ကိုဖော်ပြပြီး confirmed local DB တွင်သာလုပ်ပါ။

## 2. သိရှိထားသည့် evidence နှင့် မသေချာသေးသည့်အချက်များ

နောက်ဆုံး `UAT_POS_STORE_Audit_Bugs_Evidence(1).zip` မှ အောက်ပါအချက်များကို baseline အဖြစ်သုံးပါ။ ZIP မရှိလျှင် လက်ရှိ browser/source မှ reproduce လုပ်ပါ။

1. Screenshot 30: Admin expense `UAT Shop Daily Cleaning & Supplies`, Cash 5,000 Ks ရှိသည်။ Screenshot တစ်ခုတည်းဖြင့် မည်သည့်အံဆွဲမှပေးသလဲ မသိနိုင်ပါ။
2. Screenshot 32: X-Report တွင် opening 100,000၊ cash sales/in/refunds/out အားလုံး 0၊ expected cash 95,000 ပြထားသည်။ 5,000 နုတ်ထားခြင်း၏ visible breakdown မရှိပါ။
3. Screenshot 33: Closing expected 95,000၊ counted cash 0၊ variance −95,000 ဖြစ်သည်။ ဤ screenshot သည် close/save အောင်မြင်ပြီးကြောင်း မပြပါ။
4. Agent report တွင် date-range cash expenses ကို DailyClosingService ကနုတ်ပြီး shift-period cash expenses ကို closeShift ကနုတ်သည်၊ POS expense cash-out event ကိုဖယ်သည်ဟုဆိုသည်။ Code diff မပါသောကြောင့် လက်ရှိ code နှင့် ကိုက်မကိုက် စစ်ပါ။
5. ZIP ၏ report/cases/bugs status မညီပါ။ Old evidence ကို current PASS အဖြစ် မပြန်သုံးပါနှင့်။

အထက်ပါအချက်များသည် historical evidence ဖြစ်သည်။ လက်ရှိ code regression သို့မဟုတ် data reset ဖြစ်သည်ဟု မယူဆပါနှင့်။

## 3. ပြင်မီ စစ်ဆေးရန်

- Applicable `AGENTS.md`, README နှင့် current handoff ကိုဖတ်ပါ။ Branch၊ HEAD၊ working-tree status ကို မှတ်တမ်းတင်ပါ။ Old docs ထက် current source ကို အခြေခံပါ။
- Framework၊ money helpers၊ DB engine၊ timezone နှင့် testing configuration ကို အတည်ပြုပါ။ UI test သည် XAMPP MySQL/MariaDB ကို အသုံးပြုကြောင်းမှတ်တမ်းတင်ပါ။
- Routes → controllers → services/models → queries → Blade/components → export/print → tests ကို trace လုပ်ပါ။
- ယခင် report ရည်ညွှန်းထားသော `app/POS/Services/DailyClosingService.php`, `app/POS/Services/CashierShiftService.php`, `app/POS/Http/Controllers/CashierShiftController.php` ကို စတင်စစ်နိုင်သည်။ Actual path နှင့် responsibility ကို ထပ်အတည်ပြုပါ။
- Expense status၊ payment account/source၊ store၊ cashier/drawer/shift၊ paid timestamp၊ cash event linkage၊ cancellation/void၊ permissions ကို စစ်ပါ။ `created_at` သည် paid time ဖြစ်သည်ဟု မယူဆပါနှင့်။
- Existing expense 5,000 ၏ actual record၊ source account၊ shift association နှင့် existing cash event ရှိမရှိကို read-only စစ်ပါ။ မသေချာသော legacy record ကို default active drawer ထဲ အလိုအလျောက်မချိတ်ပါနှင့်။
- X-Report သည် တစ်နေ့ဆိုင်စုစုပေါင်းလား၊ single shift လား အတည်ပြုပါ။ Daily closing နှင့် shift closing ၏ scope/cutoff ကွာခြားချက်ကို ရှင်းပါ။
- Baseline screenshot၊ relevant read-only query output နှင့် expected-versus-actual ကိုယူပြီး root cause ရေးပါ။ ထို့နောက် အနည်းဆုံးလိုအပ်သော fix ကိုလုပ်ပါ။

## 4. မှန်ကန်ရမည့် Cash စည်းမျဉ်းများ

### 4.1 Payment method နှင့် ငွေထွက်နေရာ

`Cash` ဟူသော method တစ်ခုတည်းဖြင့် POS drawer ကို သတ်မှတ်၍မရပါ။ Existing account/drawer architecture ကို reuse လုပ်ပါ။

- ဒီ drawer/shift မှ အမှန်တကယ်ပေးသည့် posted/paid expense ကိုသာ ဒီ drawer မှနုတ်ပါ။
- Safe/petty cash/အခြား drawer မှ Cash ပေးခြင်းသည် ဒီ POS drawer ကို မလျော့စေရ။
- KPay/WavePay/Bank ဖြင့်ပေးခြင်းသည် သက်ဆိုင်ရာအကောင့်မှသာထွက်ပြီး cash drawer မလျော့စေရ။
- Pending/unpaid expense သည် cash movement မဖြစ်သေးပါ။ Split payments ရှိပြီးသားဆိုလျှင် သက်ဆိုင်ရာ cash portion သာနုတ်ပါ။
- Date range၊ cashier ID သို့မဟုတ် payment method သာဖြင့် drawer ownership မဆုံးဖြတ်ပါနှင့်။ Concurrent shifts နှင့် shift မဖွင့်မီ expense များကို စစ်ပါ။
- Legacy source မသိသည့် record ကို unresolved အဖြစ်တိတိကျကျဖော်ပြပါ။ ပြင်ဆင်နိုင်သော existing authorized workflow ရှိလျှင်သာ အကြောင်းရင်းနှင့် audit trail ဖြင့်ချိတ်ပါ။

### 4.2 တစ်ကြိမ်သာနုတ်ခြင်း

Expense table နှင့် linked cash event နှစ်ခုရှိလျှင် တစ်ခုတည်းသောငွေထွက်ကို နှစ်ကြိမ်မနုတ်ရ။ Existing accounting design နှင့်ကိုက်သော authoritative calculation ကိုရွေးပါ။

- Manual cash-out functionality ကို မဖျက်ပါနှင့်။ Expense-linked event နှင့် unrelated manual cash-out ကို ခွဲစစ်ပါ။
- POST retry/double-click၊ concurrent request တို့မှ expense/movement ပွားခြင်းမဖြစ်ရန် existing transaction/idempotency pattern ကိုအသုံးပြုပါ။
- ယခင် linked events ပါသော data နှင့် အသစ်ရေးမည့် data နှစ်မျိုးစလုံး မှန်ရမည်။ Blind deletion/backfill မလုပ်ပါနှင့်။
- Drawer → Safe transfer သည် drawer out ဖြစ်သော်လည်း operating expense မဟုတ်ပါ။ Expense က goods net sales ကို မလျော့စေရ။

### 4.3 တွက်ချက်မှုနှင့် Closed period

တူညီသော drawer/shift/scope/cutoff အတွက်:

`Expected cash = Opening + Retained cash sales + Other drawer cash inflows − Cash refunds − Drawer-paid expenses − Other drawer cash outflows`

- Retained cash sales တွင် customer change ကို ထပ်မပါစေရ။ Existing sales/refund/debt collection/repair receipt logic မပျက်စေရ။ Inflow နှင့် outflow categories မထပ်ရ။
- Existing money precision helpers ကိုသုံးပါ။ Floating-point rounding သို့မဟုတ် `max(0, ...)` ဖြင့် မကိုက်ညီမှုကို ဖုံးမထားပါနှင့်။
- Opening သည် shift စဖွင့်ချိန် actual float ဖြစ်သည်။ အရင်ထွက်ပြီးသား expense ကို ထပ်မနုတ်ရ။
- App timezone၊ business date၊ shift boundaries နှင့် cutoff ကို တစ်ပြေးညီသုံးပါ။ ဆက်တိုက် shifts အကြား တစ်ခုတည်းသော expense နှစ်ဖက်မပါစေရ။
- Closed/approved period ကို နောက်ပိုင်း expense edit/void/backdate ဖြင့် တိတ်တဆိတ်မပြောင်းစေရ။ Existing lock/reversal/adjustment policy ကိုလိုက်နာပြီး permission/audit trail ကို ထိန်းပါ။

## 5. UI တွင် ပြင်ဆင်ရန်

- X-Report နှင့် Closing တွင် opening၊ retained cash sales၊ other cash-in၊ refunds၊ drawer-paid expenses၊ other cash-out၊ expected cash ကို ရှင်းလင်းစွာပြပါ။
- Example: opening 100,000 + inflow 0 − refund 0 − expense 5,000 − other out 0 = expected 95,000။ ပြထားသော rows များပေါင်းလျှင် total နှင့် အတိအကျကိုက်ရမည်။
- `အသုံးစရိတ်` နှင့် `အခြားငွေထုတ်` ကို သီးသန့်ပြပါ။ Total cash-out ပြလျှင် breakdown ကိုရှင်းပြီး expense ကို ထပ်မနုတ်ပါနှင့်။
- Expense subtotal ကိုနှိပ်လျှင် permission ရှိသူက reference၊ အကြောင်းအရာ၊ amount၊ payment source၊ date/time၊ shift/drawer နှင့် status ကို ကြည့်နိုင်ရမည်။ Internal implementation labels မထည့်ပါနှင့်။
- Daily closing က store-wide ဖြစ်လျှင် drawer expense နှင့် other-account expense ကို ခွဲပြပြီး report scope ကိုရှင်းပါ။ Day aggregate ကို single-shift amount နှင့် တိုက်ရိုက်မနှိုင်းပါနှင့်။
- Counted cash၊ expected cash၊ variance = counted − expected ကို တစ်ပြေးညီပြပါ။ Zero input နှင့် မဖြည့်ရသေးခြင်းကို existing UX နှင့်ကိုက်စွာကိုင်တွယ်ပါ။ Actual shortage ရှိလျှင် မဖုံးကွယ်ပါနှင့်။
- Existing export/print ရှိလျှင် visible breakdown နှင့် totals ကို တူညီစေပါ။ Framework/design system အသစ်မထည့်ပါနှင့်။

## 6. မဖြစ်မနေ စမ်းသပ်ရမည့် Cases

အောက်ပါ cases သည် independent fixtures သို့မဟုတ် သီးခြား baseline အပေါ်တွက်ရန်ဖြစ်သည်။ Existing live UAT opening ကို 100,000 ဖြစ်အောင် ပြန်ရေးခိုင်းခြင်းမဟုတ်ပါ။ Test တစ်ခုချင်း run ID၊ record IDs၊ baseline၊ expected၊ actual ကို မှတ်တမ်းတင်ပါ။

| Case | စမ်းသပ်ချက် | Expected |
|---|---|---|
| C01 | Opening 100,000၊ movement မရှိ | Expected 100,000၊ expense 0 |
| C02 | ဒီ drawer မှ Cash expense 5,000 | Expense row 5,000၊ expected 95,000 |
| C03 | Safe မှ Cash expense 5,000 | ဒီ drawer 100,000 မပြောင်း |
| C04 | KPay/Bank မှ expense 5,000 | Drawer မပြောင်း၊ သက်ဆိုင်ရာ account မှသာထွက် |
| C05 | POS expense တစ်ခု submit/retry | Expense နှင့် cash deduction တစ်ကြိမ်သာ |
| C06 | Legacy expense + linked cash-out event | တစ်ကြိမ်သာနုတ်၊ unrelated manual out ဆက်မှန် |
| C07 | Drawer A/B သို့မဟုတ် concurrent shifts | A expense ကို B ထဲမနုတ် |
| C08 | Expense before opening / shift boundary | မှန်သည့် period တွင်သာပါ၊ နောက် shift တွင်ထပ်မနုတ် |
| C09 | Unpaid expense; open-period edit/void | Unpaid မနုတ်၊ paid change/void သည် policy အတိုင်း တစ်ကြိမ်သာပြင် |
| C10 | Drawer မှ safe သို့ transfer 5,000 | Drawer 95,000၊ operating expense 0 |
| C11 | Sale 25,000၊ tender 30,000၊ change 5,000၊ drawer expense 5,000၊ refund 10,000၊ opening 100,000 | Expected 110,000၊ goods net sales 15,000 |
| C12 | Expected 95,000၊ counted 95,000 ဖြင့် close/save၊ reload | Variance 0၊ stored closing နှင့် reopened detail ကိုက် |
| C13 | Expected 95,000၊ counted 94,000 | Shortage −1,000 နှင့် existing reason/authorization workflow မှန် |
| C14 | Closed-period edit/void/backdate attempt | Lock သို့မဟုတ် authorized audited adjustment၊ silent history rewrite မရှိ |
| C15 | Cashier/manager permission၊ other-store reference tampering | UI နှင့် server နှစ်ဖက် scope/authorization မှန် |

- ရှိပြီးသား cash debt collection နှင့် repair cash receipt ကို affected summary ကတွက်လျှင် retained inflow တစ်ကြိမ်သာပါကြောင်း targeted regression လုပ်ပါ။ သီးခြား module rewrite မလုပ်ပါနှင့်။
- Read-only reconciliation တွင် same cutoff ကို query အားလုံး၌ အမှန်တကယ်အသုံးပြုပြီး scope/timezone/status/source ကို မှတ်တမ်းတင်ပါ။ Independent expected sum နှင့် service/UI/stored close amount တို့၏ delta ကိုထုတ်ပါ။
- Financial correctness cases အတွက် meaningful automated regression tests ရေး/ပြင်ပါ။ ဖြစ်နိုင်လျှင် fix မတိုင်မီ FAIL၊ ပြင်ပြီး PASS အထောက်အထားယူပါ။
- Configured tests မစမီ database reset/drop လုပ်သလားစစ်ပါ။ Destructive test setup ကို UAT DB ပေါ်မ run ပါနှင့်။ Dedicated disposable test DB ကိုသာသုံးပြီး SQLite result နှင့် MySQL result ကို ခွဲရေးပါ။ Existing valuable DB ကို drop/create မလုပ်ပါနှင့်။
- Relevant existing tests/gates သာ run ပါ။ Unrelated baseline failures ကို သီးသန့်ဖော်ပြပါ။

## 7. Browser Skills ဖြင့် လက်တွေ့အတည်ပြုရန်

- Available Browser skill ကိုဖတ်ပြီး actual local app ကို အသုံးပြုပါ။ Code inspection သာဖြင့် browser PASS မပေးပါနှင့်။
- အနည်းဆုံး expense create → X-Report breakdown/detail → Closing counted input → save/close → reload → stored detail ကို အဆုံးထိစမ်းပါ။ Manager approval workflow ရှိလျှင် target UAT record ကို သက်ဆိုင်ရာ role ဖြင့် အဆုံးထိစမ်းပါ။
- 375px mobile၊ 768px tablet၊ 1366px desktop တွင် changed pages စစ်ပါ။ Burmese labels၊ input/buttons၊ table-contained scrolling၊ modal၊ keyboard/touch နှင့် supported dark/light theme ကိုစစ်ပါ။ Tested widths အမှန်ကိုရေးပါ။
- Console errors၊ failed requests၊ relevant Laravel logs ကိုစစ်ပါ။ Browser access မရလျှင် BLOCKED လို့ရေးပြီး automated checks ဆက်လုပ်ပါ။ Fake screenshots/result မဖန်တီးပါနှင့်။

## 8. Completion နှင့် Evidence Handoff

တစ်ခုတည်းသော current run folder/ZIP အဖြစ် အောက်ပါတို့ပေးပါ:

1. `REPORT_MM.md`: root cause၊ changed files/reasons၊ scope၊ resolved/remaining risks၊ database impact၊ overall result။ Financial misattribution/double deduction ကို cosmetic P3 ဟု မသတ်မှတ်ပါနှင့်။ Impact အလိုက် priority ရေးပါ။
2. `CASES.csv`: C01–C15 နှင့် related checks အတွက် PASS/FAIL/BLOCKED/NOT RUN၊ expected/actual၊ record IDs၊ evidence path။
3. `changes.diff`: လက်ရှိ task နှင့်သက်ဆိုင်သော patch။ Before-existing edits ကို ခွဲဖော်ပြပါ။ Credentials မပါစေရ။
4. `test-results.txt`: exact commands၊ actual output၊ DB engine၊ baseline failures၊ remaining failures။
5. `reconciliation.md`: same scope/cutoff ရှိ expense sources၊ opening၊ inflows/outflows၊ expected၊ counted၊ variance၊ DB/UI delta။ Sensitive unrelated data မပါစေရ။
6. `evidence/`: before/after breakdown၊ expense source detail၊ safe/KPay drawer unaffected proof၊ successful close/reload၊ role/viewport proof။ ရည်ညွှန်းသမျှဖိုင် တကယ်ပါရမည်။
7. Branch/HEAD၊ final working-tree status၊ migration status၊ local-only/commit/push/deploy status ကိုရေးပါ။

Acceptance: Hidden 5,000 deduction မရှိ၊ correct drawer/source only၊ one deduction only၊ visible sum = expected၊ same-scope DB/X-Report/Closing/export ကိုက်၊ close/reload/approval policy မှန်၊ relevant regressions PASS ဖြစ်ရမည်။

Scope အပြင်က bug များကို သီးသန့် report လုပ်ပါ။ ဤ fix PASS ဖြစ်ခြင်းကို project တစ်ခုလုံး Production Ready ဟု မရေးပါနှင့်။ မစမ်းရသေးသည့်အချက်ကို ရှင်းလင်းစွာဖော်ပြပြီး ပြီးစီးသမျှအထောက်အထားနှင့် တင်ပြပါ။
