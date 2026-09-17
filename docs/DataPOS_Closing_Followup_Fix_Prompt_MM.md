# DataPOS Closing — ကျန်ရှိသော Financial Correctness ပြဿနာများကို ပြင်ရန်

သင်သည် DataPOS local project အတွက် AI Coding Agent ဖြစ်သည်။ ဤ task သည် **Audit + Fix + Targeted Tests + Browser Verification** ဖြစ်သည်။ Plan သာမပေးဘဲ authorized local work ကို အဆုံးထိလုပ်ပါ။

## 1. Context နှင့် Scope

- Project: `D:\xmapp\htdocs\DataPOS`
- Repository: https://github.com/shwepyithit568-commits/DataPOS
- Target: http://127.0.0.1:8501/store/uat-pos-store/pos/closing
- Store slug: `uat-pos-store` / Name: `UAT-POS-STORE`။ Numeric ID ကို DB မှအတည်ပြုပါ။ နောက်ဆုံး report က ID 6 ဟုဆိုသော်လည်း hard-code မလုပ်ပါနှင့်။
- Local XAMPP MySQL/MariaDB connection ကို အတည်ပြုပါ။ ယခင် DB အမည် `datapos_uat` ဖြစ်သည်။ Credentials မထုတ်ပြပါနှင့်။
- Input evidence: `UAT_CLOSING_CASH_OUT_AUDIT_EVIDENCE.zip`။ ရှိလျှင် report၊ changes.diff၊ cases၊ test output နှင့် screenshots ကိုဖတ်ပါ။ မရှိလျှင် အောက်ပါ review findings နှင့် current source မှစပါ။
- Visible expense row၊ 100,000 − 5,000 = 95,000 equation၊ approved closing နှင့် print preview ကို ယခင် screenshots တွင်တွေ့ထားပြီးဖြစ်သည်။ ဤ working UI ကို ထိန်းထားပါ။ UI redesign မလုပ်ပါနှင့်။
- Backup မယူပါနှင့်။ Existing UAT data ကို reset/reseed/wipe/truncate/delete မလုပ်ပါနှင့်။ အခြားဆိုင်၊ production DB မပြင်ပါနှင့်။
- Local code fix နှင့် target store အတွင်း ခွဲခြားထားသော test records သာလုပ်ပါ။ Commit/push/deploy မလုပ်ပါနှင့်။ Existing user edits ကိုမဖျက်ပါနှင့်။
- ဤ task ၏ အဓိကရည်ရွယ်ချက်မှာ မှန်သည့် drawer မှ တစ်ကြိမ်သာနုတ်ခြင်း၊ legacy cash movement မပျောက်ခြင်း၊ daily/shift closing တို့၏ scope ကိုက်ညီခြင်းနှင့် evidence ပြည့်စုံခြင်းဖြစ်သည်။

## 2. Inspect First

Applicable AGENTS.md၊ README နှင့် လက်ရှိ handoff ကိုဖတ်ပါ။ Branch/HEAD၊ working-tree status၊ DB engine၊ app timezone နှင့် test DB configuration ကိုမှတ်တမ်းတင်ပါ။ Review ပြီးနောက်ပြင်ပြီးသား code ရှိလျှင် current state ကိုစစ်ပြီး duplicate fix မလုပ်ပါနှင့်။

အဓိက trace လုပ်ရန်:

- Admin `ExpenseController` create/update/delete routes နှင့် request validation/authorization။
- POS `CashierShiftController::recordExpense()`၊ active shift resolution နှင့် retry behavior။
- `CashierShiftService::closeShift()`၊ `DailyClosingService::expectedTotals()`။
- Expense/CashEvent models၊ migrations၊ period locks/observers/services၊ closing snapshots။
- Expense form၊ X-Report၊ Closing၊ drilldown၊ exports/print နှင့် tests။

Source တွင် အောက်ပါ findings ကို reproduce လုပ်ပြီး root cause နှင့် အနည်းဆုံးလိုအပ်သော fix ကိုဖော်ပြပါ။ Existing architecture၊ money helpers၊ localization၊ permissions နှင့် store isolation ကို reuse လုပ်ပါ။ Dependency/framework အသစ်မထည့်ပါနှင့်။

## 3. Fix A — Drawer / Shift ကို မှန်ကန်စွာရွေးချယ်ခြင်း

ယခင် diff တွင် Admin မှ drawer expense တင်ရာ shift မပါလျှင် `where('status', 'open')->first()` ဖြင့် ဆိုင်၏ ပထမဆုံး open shift ကိုယူထားသည်။ Validation သည် shift ရှိခြင်းနှင့် store တူခြင်းကိုသာ စစ်ထားသည်။

လိုအပ်သောပြင်ဆင်ချက်:

1. Drawer expense အတွက် explicit valid shift selection လိုအပ်စေပါ။ POS သည် authenticated cashier ၏ unequivocal active shift ကို server မှ resolve လုပ်နိုင်လျှင်သာ သုံးပါ။ Ambiguous shift ကို မခန့်မှန်းပါနှင့်။
2. Source value ကို allowed enum ဖြင့်စစ်ပါ။ Cash/non-cash method နှင့် source ကိုက်ညီရမည်။ Drawer source ဖြစ်ပြီး shift null မဖြစ်စေရ။
3. Selected shift သည် target store ပိုင်၊ open ဖြစ်၊ actor သုံးခွင့်ရှိပြီး paid time/business period နှင့်ကိုက်ရမည်။ Admin create နှင့် update နှစ်ခုစလုံးစစ်ပါ။
4. No open shift၊ closed shift၊ unauthorized drawer၊ other-store shift၊ invalid source ကို server validation/authorization ဖြင့်ပိတ်ပါ။ UI dropdown တစ်ခုတည်းကို မယုံပါနှင့်။
5. Concurrent expense creation နှင့် shift close အကြား race ကို existing transaction/locking pattern ဖြင့်ကာကွယ်ပါ။

Acceptance: Drawer A မှ expense 5,000 ဆို A သာလျော့၊ B မလျော့။ Shift မရွေးထားပါက အခြား cashier ဆီ auto-assign မဖြစ်ရ။

## 4. Fix B — Legacy Cash Movement မပျောက်စေရန်

ယခင် diff သည် `expense_id` ရှိသော event သို့မဟုတ် reason `Expense:%` ဖြစ်သော event ကို cash-out မှဖယ်သည်။ သို့သော် legacy expense တွင် source/shift မသိလျှင် expense ဘက်မှလည်း မနုတ်နိုင်သဖြင့် actual outflow နှစ်ဖက်လုံးမှ ပျောက်နိုင်သည်။

လိုအပ်သောပြင်ဆင်ချက်:

1. Free-text reason prefix တစ်ခုတည်းဖြင့် event ကို မဖယ်ပါနှင့်။ Explicit validated linkage နှင့် transaction identity ကိုအခြေခံပါ။
2. တူညီသည့် paid drawer expense ကို authoritative calculation ထဲ တကယ်ထည့်ပြီးမှ ၎င်း၏ linked event ကို duplicate အဖြစ်ဖယ်နိုင်သည်။ Cancelled/void cases ကို existing reversal policy အတိုင်းကိုင်တွယ်ပါ။
3. Event သည် actual drawer outflow ဖြစ်ကြောင်းရှိပြီး matching expense မအတည်ပြုနိုင်လျှင် outflow ကို တိတ်တဆိတ်ဖျောက်မထားပါနှင့်။ Reconciliation issue အဖြစ်ခွဲပြပြီး double deduction မရှိအောင်စစ်ပါ။
4. `cash_out - drawer_expenses` fallback နှင့် negative-to-zero clamp ကိုစစ်ပါ။ Aggregate cash_out ထဲ expense ပါပြီးသားဟု သက်သေမရှိဘဲ အလိုအလျောက်နုတ်မထားပါနှင့်။
5. Shifts အများအပြားထဲ တစ်ခုမှာ cash events ရှိရုံဖြင့် အခြား shift ၏ legacy aggregate outflow မပျောက်စေရ။ Mixed legacy/current representation ကို shift အလိုက် reconcile လုပ်ပါ။
6. Blind backfill/deletion မလုပ်ပါနှင့်။ Proven mapping ရှိလျှင်သာ audit trail၊ before/after amounts နှင့် minimal correction လုပ်ပါ။

Acceptance: Manual cash-out၊ proven expense-linked cash-out နှင့် unresolved legacy outflow တို့ အမှန်တကယ်ထွက်ငွေ မပျောက်ဘဲ တစ်ကြိမ်သာပါရမည်။

## 5. Fix C — Shift Closing / Daily Closing / Detail Scope ကိုက်ညီခြင်း

ယခင် diff တွင် daily summary သည် unlinked drawer expense ကို date ဖြင့်နုတ်သော်လည်း shift close သည် shift ID ပါမှနုတ်သည်။ Linked expense query နှင့် detail list ၏ date filters လည်း မတူပါ။

လိုအပ်သောပြင်ဆင်ချက်:

1. New paid drawer expense ကို valid shift မပါဘဲ မသိမ်းစေရ။ Existing unlinked record ကို arbitrary shift ထဲ မထည့်ပါနှင့်။
2. Same scope/cutoff အတွက် reusable calculation rules ကိုသုံးပါ။ Daily report သည် store aggregate ဖြစ်လျှင် single shift နှင့် တိုက်ရိုက်ညီရမည်ဟု မဆိုပါနှင့်။ ပါဝင်သော shifts/movements ကိုရှင်းပြပါ။
3. Before-opening၊ backdated expense၊ sequential shifts၊ overnight shift နှင့် business-date boundary တွင် တစ်ခုတည်းသော outflow ဘယ် period ထဲပါသလဲ အတိအကျသတ်မှတ်ပြီး စမ်းပါ။
4. Detail modal သည် subtotal ကိုရှင်းပြနိုင်ရမည်။ Paid/unpaid/void status၊ source၊ linked shift နှင့် included/excluded status ကိုပြပါ။ All-day expenses ပြလျှင် drawer subtotal နှင့် ရောမထင်စေရ။
5. Approved closing summary/print/detail သည် persisted snapshot သို့မဟုတ် existing historical policy နှင့်ကိုက်ရမည်။ နောက်ပိုင်း source edit ကြောင့် ပိတ်ပြီးစာရင်း တိတ်တဆိတ်မပြောင်းစေရ။
6. Expense create/update/delete/void/backdate ကို approved period အတွက် actual HTTP endpoint မှစမ်းပါ။ Date ပြောင်းရွှေ့ခြင်းတွင် original နှင့် destination period နှစ်ဖက်စစ်ပါ။ Unauthorized changes ကိုပိတ်ပြီး authorized correction ရှိလျှင် audited reversal/adjustment ဖြင့်သာလုပ်ပါ။

## 6. Fix D — Unknown Source ကို Safe ဟု မယူဆရန်

ယခင် reconciliation က မူလ expense ကို `safe (null legacy)` ဟုဖော်ပြထားသည်။ Null သည် Safe မှပေးကြောင်း သက်သေမဟုတ်ပါ။

- Null/unknown source ကို `ငွေထွက်နေရာ မအတည်ပြုရသေး` အဖြစ် သီးခြားဖော်ပြပါ။ Safe confirmed expenses နှင့် မပေါင်းပါနှင့်။
- Safe/KPay/Bank ဟု label တပ်ထားရုံဖြင့် account balance က မှန်ကန်စွာလျော့ပြီးဟု မဆိုပါနှင့်။ Existing account ledger ရှိလျှင် actual posting ကိုစစ်ပါ။ မရှိလျှင် classification only ဟု report လုပ်ပြီး task scope အပြင် banking system အသစ်မဆောက်ပါနှင့်။
- Original legacy record ကို အထောက်အထားမရှိဘဲ source ပြောင်းမထားပါနှင့်။ Unknown amount၊ drawer impact certainty နှင့်လိုအပ်သော resolution ကို report လုပ်ပါ။

## 7. Fix E — Test Claims နှင့် Evidence ကို အပြည့်အစုံပြန်တင်ရန်

ယခင် ZIP တွင် report ရည်ညွှန်းသော migration နှင့် `ClosingCashOutAuditTest.php` မပါခဲ့ပါ။ `76 passed` output သည် test coverage အပြည့်အစုံကို စစ်နိုင်ရန် မလုံလောက်ပါ။

- Actual test source ကိုဖတ်ပြီး test names နှင့် assertion/HTTP behavior တူမတူစစ်ပါ။ C05 idempotency ကို single create ဖြင့် PASS မပေးပါနှင့်။
- Same idempotency key/request ကို endpoint သို့ နှစ်ကြိမ်နှင့် concurrent ပို့စမ်းပြီး expense/movement တစ်ခုသာရှိကြောင်းစစ်ပါ။ Different key နှင့် legitimate same-value expense နှစ်ခုကို မပယ်ရ။ Same key/different payload ကို existing policy အတိုင်း reject လုပ်ပါ။
- DB transaction ရှိရုံဖြင့် idempotency ပြည့်စုံသည်ဟု မယူဆပါနှင့်။ Existing mechanism မလုံလောက်လျှင် scoped durable protection ကိုအနည်းဆုံးလိုအပ်သလိုပြင်ပါ။
- New/modified tests နှင့် migrations ဖိုင်အားလုံးကို full source ဖြင့်ပေးပါ။ Tracked-only `git diff` ထဲ untracked files မပါနိုင်ကြောင်းသတိထားပါ။ Evidence ထဲထည့်ရန်အတွက် commit လုပ်စရာမလိုပါ။
- Test မစမီ DB reset/drop behavior ကိုစစ်ပါ။ Automated destructive fixtures ကို existing UAT DB ပေါ်မ run ပါနှင့်။ Disposable test DB ကိုသီးခြားသုံးပါ။ MySQL/MariaDB နှင့် SQLite results ကိုခွဲရေးပါ။

## 8. Targeted Regression Matrix

Independent fixtures/run IDs ဖြင့် အောက်ပါတို့စမ်းပါ။ Existing approved UAT record ကို reopen/delete လုပ်ပြီး စမ်းစရာမလိုပါ။ Locked behavior စစ်ရာ mutation reject ဖြစ်ပြီး data မပြောင်းကြောင်း အတည်ပြုပါ။

| ID | Case | Expected |
|---|---|---|
| F01 | Drawer A/B ဖွင့်၊ Admin drawer expense shift မရွေး | Validation error၊ expense မဖန်တီး |
| F02 | A shift ကိုရွေး၊ opening 100,000 မှ expense 5,000 | A 95,000၊ B မပြောင်း |
| F03 | No open/closed/foreign-store/unauthorized shift၊ invalid source | Reject၊ DB unchanged |
| F04 | POS cash expense without valid active shift | Reject၊ orphan drawer expense မရှိ |
| F05 | Paid drawer expense + valid linked cash-out 5,000 | Total outflow 5,000 သာ |
| F06 | Legacy real cash-out reason Expense: + unresolved expense | Proven outflow မပျောက်၊ unknown source ကိုမခန့်မှန်း |
| F07 | Unrelated manual cash-out reason Expense: စာသားပါ | Prefix ကြောင့်မဖယ်၊ actual outflow မှန် |
| F08 | Mixed shifts: legacy aggregate out + new event-backed out | Shift တစ်ခု၏ငွေထွက် မပျောက်၊ double count မရှိ |
| F09 | Unlinked drawer expense / before opening / overnight boundary | Explicit resolution၊ period policy ကိုက်၊ daily/shift reconciliation delta 0 |
| F10 | Safe/KPay expense 5,000 | POS drawer မလျော့၊ unknown ကို Safe မပြ |
| F11 | Same request retry/concurrent၊ separate legitimate request | Retry တစ်ကြိမ်သာ၊ distinct request သီးခြားမှန် |
| F12 | Paid expense edit/void in open period | Net impact တစ်ကြိမ်သာ၊ manual cash-out မပျက် |
| F13 | Approved period expense create/update/delete/void/backdate via HTTP | Lock/authorized audited adjustment policy မှန်၊ snapshot မပြောင်း |
| F14 | Opening 100k၊ retained sale 25k (tender 30k/change 5k)၊ expense 5k၊ refund 10k | Expected 110k၊ goods net sales 15k |
| F15 | Expected/count 95k → close → reload → approve → print | Variance 0၊ stored summary/detail/print ကိုက် |

Behavioral bugs အတွက် meaningful regression test ကို fix မတိုင်မီ FAIL၊ ပြင်ပြီး PASS ဖြစ်ကြောင်း ဖြစ်နိုင်သမျှပြပါ။ Related existing tests/gates ကို run ပါ။ Baseline unrelated failures ကိုခွဲရေးပါ။

## 9. Browser Verification

Available Browser skill ကိုဖတ်ပြီး real local browser ဖြင့် Admin source/shift selection၊ invalid input၊ A/B isolation၊ expense detail၊ closing/approval/reload ကိုစမ်းပါ။ Cashier နှင့် authorized manager/server permissions ကို သီးခြားစစ်ပါ။ Owner screenshot ကို cashier authorization proof အဖြစ် မသုံးပါနှင့်။

Changed forms/detail ကို 375px၊ 768px၊ 1366px တွင်စစ်ပြီး Burmese labels၊ dropdown၊ table-contained scrolling၊ modal၊ keyboard/touch၊ supported themes ကိုစစ်ပါ။ Console/network/log errors စစ်ပါ။ Browser သို့မဟုတ် permission scenario မစမ်းနိုင်လျှင် BLOCKED/NOT RUN ဟုရေးပါ။ Print preview ကို physical printer/ESC-POS hardware test ဟု မခေါ်ပါနှင့်။

## 10. Final Deliverables နှင့် Acceptance

အသစ်တစ်ခုတည်းသော evidence ZIP ပေးပါ။ အောက်ပါတို့အပြည့်အစုံပါရမည်:

1. `REPORT_MM.md`: A–E findings တစ်ခုချင်း root cause၊ changed files/reasons၊ resolved/remaining၊ DB impact၊ no reset/reseed status။
2. `CASES.csv`: F01–F15 expected/actual/status၊ record IDs၊ test name၊ evidence path။
3. `changes.diff` နှင့် `source/`: ဒီ task ၏ new/modified migration/test ဖိုင်အပြည့်အစုံ၊ baseline နှင့်ဆက်စပ်ကြောင်းရှင်းလင်းချက်။ Secrets မပါစေရ။
4. `test-results.txt`: exact commands၊ actual output၊ DB engine၊ fail-before/pass-after ရနိုင်သမျှ၊ baseline failures။
5. `reconciliation.md`: store/drawer/shift IDs၊ timezone/cutoff၊ included/excluded movement references၊ independent expected sum၊ service/UI/stored amounts နှင့် delta။ Unknowns ကိုခွဲဖော်ပြပါ။
6. `evidence/`: actual validation errors၊ A/B isolation၊ legacy reconciliation၊ retry result၊ successful closing၊ forbidden closed-period edit နှင့် responsive proof။
7. `manifest.txt`: ZIP ထဲဖိုင်စာရင်း၊ branch/HEAD၊ final git status၊ tracked/untracked changed files၊ migration status၊ commit/push/deploy မလုပ်ထားကြောင်း။

ZIP ကို တကယ်ပြန်ဖွင့်ပြီး referenced files အားလုံးပါကြောင်း၊ reports/cases/output တူညီသည့် run ကိုရည်ညွှန်းကြောင်း စစ်ပါ။ Evidence ပြည့်စုံမှ သက်ဆိုင်ရာ case ကို PASS ပေးပါ။

ပြီးစီးမှုစံ: Wrong-drawer auto assignment မရှိ၊ real outflow မပျောက်၊ double deduction မရှိ၊ unresolved source မခန့်မှန်း၊ same-scope daily/shift reconciliation မှန်၊ retry နှင့် closed-period protection မှန်၊ browser proof နှင့် source/test/migration evidence ပြည့်စုံရမည်။ ဤ scope PASS ကို project တစ်ခုလုံး Production Ready ဟု မရေးပါနှင့်။
