# 07 Ai Agent Prompts

> Consolidated edition. Each embedded source is preserved below with its original path and SHA-256 digest.


---

## Source 1: `prompts/AI_AGENT_QA_PLAYBOOK.md`

**SHA-256:** `50dd56ddb12e3422d12bce1f18b1cff1267ef835077fcf46712da96361ee9c6d`

# DataPOS AI Agent QA & Safe-Fix Playbook

**Status:** Active consolidated prompt

**Merged from:** legacy bug-fix, responsive, spacing, theme, localization, POS/export, security, Lighthouse and final-verification prompts

**Scope:** Reusable instructions only; feature-specific approved plans remain authoritative.

## 1. Start safely

1. Read `AGENTS.md`, `../README.md` (`../README.md`; see consolidated index), the relevant approved architecture document and current tests.
2. Record branch, exact HEAD and `git status --short` before editing.
3. Preserve unrelated user changes. Do not deploy, push, delete data or rotate configuration unless explicitly authorized.
4. Reproduce the reported issue and identify the root cause before changing code.
5. Treat current routes, migrations, models and tests as evidence—not documentation claims alone.

## 2. Engineering rules

- Fix the underlying cause; do not hide symptoms with CSS or conditional workarounds.
- Prefer existing services, policies, middleware, components and design tokens.
- Add no unnecessary dependency or duplicate architecture.
- Validate nulls, types, nested payloads, allowlists, duplicate requests and concurrent writes.
- Protect financial and quantity calculations with the project decimal/bcmath convention; do not use PHP float for persisted financial truth.
- Do not change unrelated business behaviour merely to satisfy a test.
- Remove debug leftovers, unused imports and dead code only within the approved task scope.

## 3. Security and multi-store isolation

For every affected read/write route, verify authentication, active membership, role/permission, active StoreContext and target-resource ownership.

Test direct URLs, manipulated IDs and requests across Staff, Products, Inventory, Sales, POS, Customers, Reports and Settings. UI hiding is not authorization. Unauthorized and cross-store operations must fail at backend/query level. Classify findings as Critical, High, Medium or Low and provide reproduction evidence.

## 4. UI/UX and spacing

Follow `ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md` (`../guides/ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md`; see consolidated index) and `../STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md` (`../guides/STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md`; see consolidated index).

- Use clean, full-width, restrained surfaces without unnecessary nested containers, margins, rounded corners or shadows.
- Page-level sibling sections should normally use the project’s compact approximately 4px rhythm; internal controls must retain readable spacing and usable touch targets.
- Reuse shared spacing/theme tokens instead of page-specific overrides.
- Respect `prefers-reduced-motion`, keyboard navigation and visible focus states.

Responsive verification minimum:

- Mobile: 320, 375, 390 and 430px
- Tablet: 768, 820 and 1024px
- Desktop: 1280, 1440 and 1920px

Tables wider than the viewport must scroll inside their wrapper without causing body-level horizontal overflow. Grid column counts are page-dependent; do not blindly force 5/3/2 columns where content or the canonical UI guide requires another layout.

## 5. Light and dark themes

Audit the complete rendered surface: page, sidebar, cards, tables, forms, dropdowns, modals, popovers, tooltips, empty/loading states and toasts.

- Light mode: high-contrast page/surface/text/border tokens suitable for daylight.
- Dark mode: true black outer surfaces where the current theme specifies OLED mode, with deep-slate inner surfaces and readable borders.
- Use centralized semantic tokens. Do not scatter arbitrary hardcoded colours.
- Verify default, hover, selected, disabled, error, warning and success states.
- Never rely on colour alone to communicate state.

## 6. Localization

Maintain key parity across `lang/my/messages.php`, `lang/en/messages.php` and `lang/zh_CN/messages.php`.

- Prefer concise, natural Burmese rather than literal word-for-word translation.
- Keep terminology consistent across navigation, buttons, forms, tables, settings, dialogs, validation, POS and reports.
- Preserve placeholders such as `:name`, `{count}` and `%s`.
- Find hardcoded user-facing strings, missing keys and accidental duplicates.
- Render-test Myanmar text for wrapping, truncation, table width, mobile navigation and modal/form alignment.

## 7. POS and Excel/CSV exports

For affected POS flows verify product selection, cart, quantity, discount, tax, totals, payment/change, stock posting, receipt, return/refund, void rules, idempotency and store isolation.

For `.xlsx`/CSV verify header/data mapping, Myanmar Unicode, configured currency, date/timezone, filters, empty/large datasets, quoting/newlines/commas and formula-injection protection. Provide separate POS and export results.

## 8. Browser and Lighthouse QA

Where the environment supports it, test the relevant public/admin journey in Mobile and Desktop Lighthouse and report Performance, Accessibility, Best Practices, SEO where applicable, LCP, CLS, blocking resources and violations.

Exercise the actual relevant user flow, not a screenshot alone. Capture viewport, role, theme, locale, console errors, failed network requests and reproducible defects. An untested item must be reported as Not Verified—not Passed.

## 9. Verification and Git handoff

Run the applicable build, lint/type checks, targeted tests, full regression suite and smoke tests. Do not deploy with unexplained failures.

Review the final diff for secrets, temporary/generated files, debug code and unrelated changes. Commit logically. Push or deploy only when explicitly authorized and available, then verify the target environment.

Final report must include:

- Starting/final branch and SHA
- Root cause and fix
- Complete changed-file list and reasons
- Exact commands and results
- Passed/failed/skipped counts
- Security/store-isolation evidence where relevant
- Browser/viewport/role/theme/locale evidence
- Console/network result
- Push/deployment status and URL when actually performed
- Remaining risks, limitations and items not verified

---

## Source 2: `prompts/AI_AGENT_SHOP_OWNER_MANAGER_E2E_AUDIT_PROMPT_MM.md`

**SHA-256:** `982e3320201ef62b63cd8201401b6bdac49857e5c553c9bbad6427c66b07bdf5`

# DataPOS AI Agent Shop Owner + Manager End-to-End Audit Prompt

> **အသုံးပြုပုံ:** အောက်ပါ `BEGIN PROMPT` မှ `END PROMPT` အထိကို DataPOS ကို browser ဖြင့် စမ်းသပ်မည့် AI Agent ထံ ပေးပါ။ Placeholder များကို run မစမီ ဖြည့်ပါ။ ဤစာတမ်းသည် destructive reset/seed prompt မဟုတ်ပါ။ အသစ်ဖန်တီးထားသော QA Store တစ်ဆိုင်အတွင်းသာ စမ်းသပ်ရန် ရည်ရွယ်သည်။

---

## Run မစမီ ဖြည့်ရန်

```text
BASE_URL=http://127.0.0.1:8501
PLATFORM_OWNER_PHONE=<09100000001>
PLATFORM_OWNER_PASSWORD=<password>
RUN_ID=<DDMMYY-HHMM or unique short id>
```

Credentials ကို report၊ screenshot၊ source file သို့မဟုတ် chat output ထဲ ပြန်မရေးရ။ မရှိသေးသော credential ကို မခန့်မှန်းရ။

---

# BEGIN PROMPT

## 1. သင်၏တာဝန်

သင်သည် DataPOS ကို လက်တွေ့အသုံးပြုမည့် **Shop Owner**, **Store Manager**, **Cashier** သုံးဦး၏ လုပ်ငန်းတာဝန်ကို အဆင့်လိုက် simulation လုပ်မည့် Senior UAT Agent ဖြစ်သည်။ ရည်ရွယ်ချက်မှာ page ဖွင့်ရုံ စစ်ခြင်းမဟုတ်ဘဲ ဆိုင်အသစ်တစ်ဆိုင်၏ transaction lifecycle ကို UI မှ အစအဆုံးလုပ်ပြီး အောက်ပါတို့ကို ledger နှင့် report အထိ reconcile လုပ်ရန်ဖြစ်သည်။

- Store onboarding နှင့် role permissions
- Product, category, supplier, customer master data
- Opening stock နှင့် purchase receiving
- Cash, digital, customer-credit sales
- Sale return/refund
- Stock damage adjustment
- Customer receivable collection
- Supplier payable payment
- Expense entry
- Stock balance, debt, payable, revenue, COGS, gross profit, net profit
- Audit log နှင့် cross-store isolation

**အရေးကြီးသောမူ:** UI ပြသထားသော total တစ်ခုကို မြင်ရုံဖြင့် `PASS` မလုပ်ရ။ Input transactions → ledger/movement → summary report ဟူသော အဆင့်သုံးဆင့်ကို တိုက်စစ်ရမည်။

## 2. လုံးဝလိုက်နာရမည့် Safety Rules

1. Existing store သို့မဟုတ် existing transaction ကို edit/delete/reset မလုပ်ရ။
2. `RUN_ID` ပါသော QA Store အသစ်တစ်ဆိုင်အတွင်းသာ write operation လုပ်ရ။
3. Production ဖြစ်နိုင်သော environment တွင် demo seeder, migrate fresh, database reset, truncate, bulk delete မလုပ်ရ။
4. Source code မပြင်ရ။ Bug တွေ့ပါက evidence နှင့် reproduction steps သာရေးရ။
5. Browser UI ကို primary testing channel အဖြစ်သုံးရ။ UI ဖြင့်လုပ်နိုင်သော operation ကို direct database/API ဖြင့် မကျော်ရ။
6. Database/CLI access ရှိပါက UI workflow ပြီးနောက် **read-only verification** အတွက်သာသုံးရ။ Data ပြင်ရန်မသုံးရ။
7. Password, PIN, session cookie, CSRF token နှင့် personal data ကို report/screenshot တွင် မဖော်ပြရ။
8. Money amount အားလုံး MMK ဖြစ်ပြီး decimal/tax/discount/shipping ကို ဤ scenario တွင် `0` ထားရ။
9. Error တစ်ခုဖြစ်လျှင် ထပ်ခါထပ်ခါ submit မလုပ်မီ record ဖန်တီးပြီးသားလား စစ်ရ။ Duplicate transaction မဖြစ်စေရ။
10. Feature/capability မဖွင့်ထားခြင်းကို bug ဟုချက်ချင်းမသတ်မှတ်ရ။ Store profile, edition နှင့် role permission ကို အရင်စစ်ရ။

## 3. Pass/Fail စည်းမျဉ်း

- **PASS:** UI workflow အောင်မြင်ပြီး expected value နှင့် report/ledger အတိအကျတူသည်။
- **FAIL:** Wrong calculation, wrong stock movement, unauthorized access, cross-store leak, duplicate posting, server/console error သို့မဟုတ် required workflow မပြီးနိုင်ခြင်း။
- **BLOCKED:** Credential, capability, hardware သို့မဟုတ် environment မရှိ၍ မစမ်းနိုင်ခြင်း။ အကြောင်းရင်းနှင့် ဖြေရှင်းရန်လိုအပ်ချက်ရေးရ။
- **NOT SUPPORTED:** Project က တမင်မပံ့ပိုးသည့် workflow ဖြစ်ကြောင်း code/config/docs evidence ဖြင့်အတည်ပြုနိုင်ခြင်း။

Expected result မတူပါက rounding error ဟု မယူဆရ။ MMK values ကို integer/fixed precision အတိုင်း အတိအကျတိုက်ရမည်။

## 4. QA Store နှင့် Accounts

Platform Owner ဖြင့် Login ဝင်ပြီး Store Management UI မှ အောက်ပါဆိုင်ကိုဖန်တီးပါ။ UI field အမည်ကွာပါက အနီးစပ်ဆုံး semantic field ကိုသုံးပြီး mapping ကို report ထဲရေးပါ။

```text
Store Name: Mingalar Tech Mart QA <RUN_ID>
Slug: mingalar-tech-qa-<RUN_ID-normalized>
Business Profile: Mobile / Electronics (မရှိပါက General Retail)
Currency: MMK
Timezone: Asia/Yangon
Phone: 09900000001
Address: Mandalay, Myanmar (QA Data Only)
```

ဖန်တီးရမည့် users:

| Role | Name | Phone | Requirement |
|---|---|---:|---|
| Store Owner | QA Owner `<RUN_ID>` | unique QA phone | Store settings, finance, reports အပြည့်ကြည့်နိုင်ရမည် |
| Manager | QA Manager `<RUN_ID>` | unique QA phone | Daily operations လုပ်နိုင်ပြီး platform-wide management မရရ |
| Cashier | QA Cashier `<RUN_ID>` | unique QA phone | POS သာအဓိကသုံးနိုင်ရမည်; finance/admin-sensitive settings မရရ |

Phone uniqueness constraint ရှိပါက `RUN_ID` ကို digits သာပြောင်းပြီး valid Myanmar-format QA numbers သုံးပါ။ Password/PIN ကို environment owner ပေးထားသည့် test policy အတိုင်းသတ်မှတ်ပြီး report ထဲ မရေးပါနှင့်။

## 5. Role နှင့် Tenant Isolation Gate

Transaction မစမီ အောက်ပါတို့ကိုစစ်ပါ။

1. Store Owner က မိမိဆိုင် Dashboard, Products, Customers, Suppliers, Purchases, POS, Receivables, Payables, Expenses, Stock Ledger, Inventory Valuation, Profit & Loss, Audit Logs ကို role/capability ခွင့်အတိုင်းဝင်နိုင်ရမည်။
2. Manager က daily operation pages ဝင်နိုင်ရမည်။ Platform `/admin/stores` သို့ direct URL ဝင်၍ ဆိုင်အသစ်ဖန်တီး/ဖျက်ခွင့် မရရ။
3. Cashier က POS ဝင်နိုင်ရမည်။ Store settings, user/role management, P&L စသည့် sensitive pages ကို direct URL ဖြင့်ဝင်ရာတွင် `403` သို့မဟုတ် safe redirect ဖြစ်ရမည်။
4. QA Store URL ထဲ record ID ပြောင်းခြင်းဖြင့် အခြားဆိုင် Product, Customer, Sale, Debt, Purchase ကို မမြင်/မပြင်နိုင်ရ။
5. Access-denied စမ်းသပ်မှုတွင် destructive request မပို့ရ။ Read page/direct URL check သာလုပ်ရ။

Cross-store data မြင်ရပါက **Critical FAIL** အဖြစ်ချက်ချင်းမှတ်တမ်းတင်ပါ။

## 6. Master Data Setup

### 6.1 Categories

- Home Appliances
- Mobile Accessories
- Power & Charging

### 6.2 Products and Opening Stock

Tax, discount, variant, batch/expiry ကို `0`/disabled ထားပါ။ SKU နှင့် barcode ကို unique `RUN_ID` suffix ထည့်ပါ။

| Code | Product | Category | Cost Price | Sale Price | Opening Qty |
|---|---|---|---:|---:|---:|
| P-A | Myanmar Rice Cooker 1.8L | Home Appliances | 60,000 | 80,000 | 10 |
| P-B | Type-C Fast Cable 1m | Mobile Accessories | 3,000 | 5,000 | 20 |
| P-C | Power Bank 10000mAh | Power & Charging | 18,000 | 25,000 | 15 |

Opening Stock ကို Product form မှ တိုက်ရိုက်မရပါက official Opening Stock UI ကိုသုံးပါ။ Product create နှင့် Opening Stock နှစ်နေရာလုံးတွင် quantity ထပ်မထည့်ရ။

### 6.3 Supplier and Customer

```text
Supplier: Golden Mandalay Distribution QA <RUN_ID>
Supplier opening payable: 0 MMK

Customer: Ko Aung Credit Customer QA <RUN_ID>
Customer opening debt: 0 MMK
Credit limit: at least 200,000 MMK (field ရှိလျှင်)
```

Master data ဖန်တီးပြီး Product list, Supplier list, Customer list တွင် search ဖြင့်ပြန်ရှာ၍ duplicate မရှိကြောင်းစစ်ပါ။

## 7. Baseline Snapshot

Transaction မလုပ်မီ အောက်ပါ baseline ကို screenshot/evidence ယူပါ။

| Product | Expected Baseline Stock |
|---|---:|
| P-A | 10 |
| P-B | 20 |
| P-C | 15 |

Expected baseline customer debt = `0 MMK`; supplier payable = `0 MMK`; scenario revenue/expense = `0 MMK`။ Existing date-range transactions မရောစေရန် QA Store နှင့် current test date range ကိုသာ filter လုပ်ပါ။

## 8. Purchase Workflow (Manager)

Supplier ထံမှ Purchase Order/GRN တစ်စောင်လုပ်ပါ။

| Product | Qty | Unit Cost | Line Total |
|---|---:|---:|---:|
| P-A | 5 | 60,000 | 300,000 |
| P-B | 10 | 3,000 | 30,000 |
| **Total** | | | **330,000** |

လုပ်ဆောင်ချက်:

1. Purchase draft ဖန်တီးပါ။
2. Discount/tax/shipping = `0` ဖြစ်ကြောင်းစစ်ပါ။
3. Initial payment `130,000 MMK` ထည့်ပြီး ကျန်ငွေကို supplier credit/payable အဖြစ်ထားပါ။
4. System workflow လိုအပ်သလို Order/Confirm ပြီး Receive လုပ်ပါ။
5. Refresh ပြီး transaction status နှင့် payment status ကိုပြန်စစ်ပါ။

Expected after receive:

- P-A stock = `15`
- P-B stock = `30`
- P-C stock = `15`
- Supplier outstanding payable = `200,000 MMK`
- Inventory movement တွင် P-A `+5`, P-B `+10` purchase/receive references ရှိရမည်။

PO creation အချိန်မှာ payable တက်ပြီး receive အချိန်မှာ stock တက်သည့် implementation ဖြစ်ပါက timing ကို report ထဲဖော်ပြပါ။ Final expected values မပြောင်းရ။

## 9. Sales Workflows (Cashier)

Shift feature enabled ဖြစ်ပါက opening cash float `50,000 MMK` ဖြင့် shift ဖွင့်ပါ။ Payment method မရှိသေးပါက Owner/Manager ဖြင့် Cash နှင့် KPay test methods ကို official settings UI မှဖွင့်ပါ။

### Sale S-1: Cash

- P-A × 2 @ 80,000 = `160,000 MMK`
- Customer: Walk-in
- Payment: Cash `160,000`
- Discount/tax = `0`

### Sale S-2: Digital

- P-B × 3 @ 5,000 = `15,000 MMK`
- Customer: Walk-in
- Payment: KPay/Digital `15,000`
- Discount/tax = `0`

### Sale S-3: Customer Credit

- P-A × 1 @ 80,000 = `80,000 MMK`
- P-C × 2 @ 25,000 = `50,000 MMK`
- Total = `130,000 MMK`
- Customer: Ko Aung Credit Customer QA `<RUN_ID>`
- Payment: Debt/Credit `130,000`
- Discount/tax = `0`

Sale တစ်စောင်စီအတွက်:

1. Checkout button ကို တစ်ကြိမ်သာနှိပ်ပါ။
2. Success/receipt reference ကိုမှတ်ပါ။
3. Sales history တွင် record တစ်စောင်တည်းရှိကြောင်းစစ်ပါ။
4. Receipt total, payment method, customer နှင့် line quantities မှန်ကြောင်းစစ်ပါ။

Expected after all three sales, before return:

- P-A = `12` (`15 - 2 - 1`)
- P-B = `27` (`30 - 3`)
- P-C = `13` (`15 - 2`)
- Gross revenue = `305,000 MMK`
- COGS = `225,000 MMK` (`2×60,000 + 3×3,000 + 1×60,000 + 2×18,000`)
- Gross profit = `80,000 MMK`
- Customer receivable = `130,000 MMK`
- Cash sale = `160,000 MMK`; digital sale = `15,000 MMK`; credit sale = `130,000 MMK`

## 10. Customer Return

Sale S-3 ထဲမှ P-C `1 unit` ကို original sale reference ဖြင့် return/refund လုပ်ပါ။ Refund ကို customer debt adjustment/credit အဖြစ် သတ်မှတ်နိုင်ပါက ယင်းကိုသုံးပါ; cash refund မလုပ်ရ။

Expected after return:

- P-C stock = `14`
- Customer debt = `105,000 MMK` (`130,000 - 25,000`)
- Net sales revenue = `280,000 MMK`
- Net COGS = `207,000 MMK`
- Gross profit = `73,000 MMK`
- Sale return movement P-C `+1` ရှိရမည်။
- Original sale ကို delete/overwrite မလုပ်ဘဲ return reference သီးခြားရှိရမည်။

System က debt-credit return ကိုမပံ့ပိုးဘဲ refund method သတ်မှတ်ခိုင်းပါက မခန့်မှန်းပါနှင့်။ Workflow ကို `BLOCKED` လုပ်ပြီး available options screenshot နှင့် report တင်ပါ။

## 11. Stock Damage Adjustment

P-B `2 units` ကို reason `Damaged during handling - QA <RUN_ID>` ဖြင့် stock adjustment လုပ်ပါ။ Approval workflow ရှိပါက Manager submit, Owner approve ခွဲလုပ်ပါ။

Expected:

- P-B stock = `25` (`27 - 2`)
- Stock ledger တွင် adjustment `-2`၊ reason၊ actor၊ timestamp ပါရမည်။
- Damage adjustment သည် sales revenue မပြောင်းရ။

## 12. Debt Collection and Supplier Payment

### 12.1 Customer Debt Collection

Customer ထံမှ `40,000 MMK` ကို Cash collection အဖြစ်လက်ခံပါ။

Expected customer debt:

```text
130,000 credit sale - 25,000 return credit - 40,000 collection = 65,000 MMK
```

Debt ledger တွင် sale, return/credit, collection သုံးမျိုးလုံး immutable entries/references ဖြင့်မြင်ရမည်။

### 12.2 Supplier Payment

Supplier outstanding ထဲမှ `50,000 MMK` ထပ်ပေးပါ။

Expected supplier payable:

```text
330,000 purchase - 130,000 initial payment - 50,000 later payment = 150,000 MMK
```

Payment history, voucher/reference နှင့် actor ကိုပြန်စစ်ပါ။ Payment လုပ်ခြင်းကြောင့် stock မပြောင်းရ။

## 13. Expense and Profit

Owner/Manager ဖြင့် expense ဖန်တီးပါ။

```text
Category: Shop Operations (မရှိပါက valid operating expense category အသစ်)
Description: QA Internet and Delivery Expense <RUN_ID>
Amount: 20,000 MMK
Date: same scenario date
```

Expected final profit for scenario date range:

```text
Net Revenue       = 280,000 MMK
Net COGS          = 207,000 MMK
Gross Profit      =  73,000 MMK
Operating Expense =  20,000 MMK
Net Profit        =  53,000 MMK
```

Customer debt collection နှင့် supplier payment ကို revenue/expense အဖြစ် ထပ်တွက်ထားပါက **Critical accounting FAIL** ဖြစ်သည်။ ယင်းတို့သည် receivable/payable settlement ဖြစ်ပြီး scenario profit ကို မပြောင်းရ။

## 14. Final Reconciliation Matrix

Same date range နှင့် QA Store filter ကိုအသုံးပြုပြီး UI reports မှ အောက်ပါတန်ဖိုးများကို ရယူပါ။ `Actual` ကိုကိုယ်တိုင်ဖြည့်ပြီး Expected နှင့်ကွာခြားချက်တွက်ပါ။

### 14.1 Inventory

| Product | Expected | Actual | Difference | Evidence |
|---|---:|---:|---:|---|
| P-A | 12 | | | |
| P-B | 25 | | | |
| P-C | 14 | | | |

### 14.2 Debt and Payable

| Metric | Expected | Actual | Difference | Evidence |
|---|---:|---:|---:|---|
| Customer receivable | 65,000 | | | |
| Supplier payable | 150,000 | | | |

### 14.3 Profit and Loss

| Metric | Expected | Actual | Difference | Evidence |
|---|---:|---:|---:|---|
| Net Revenue | 280,000 | | | |
| COGS | 207,000 | | | |
| Gross Profit | 73,000 | | | |
| Expenses | 20,000 | | | |
| Net Profit | 53,000 | | | |

### 14.4 Movement Trace

P-A, P-B, P-C တစ်ခုချင်းစီအတွက် Stock Ledger/Bin Card ကိုဖွင့်ပြီး အောက်ပါ movement chain ရှိမရှိစစ်ပါ။

```text
Opening Stock → Purchase Receive → POS Sale → Sale Return (P-C) → Damage Adjustment (P-B)
```

Final balance မှန်သော်လည်း movement reference ပျောက်နေပါက `FAIL` ဖြစ်သည်။

## 15. Additional Integrity Checks

1. Refresh/logout/login ပြီးနောက် final values မပြောင်းရ။
2. Browser back/refresh ကြောင့် sale, purchase, payment duplicate မဖြစ်ရ။
3. Inventory valuation ကို system costing method ဖြင့်စစ်ပြီး costing method (weighted average/FIFO/other) ကို report ထဲရေးပါ။
4. Report date boundaries သည် `Asia/Yangon` date နှင့်ကိုက်ရမည်။
5. Audit Logs တွင် store/user creation, product/opening stock, purchase/receive/payment, sale/return, adjustment, debt collection, expense စသည့် sensitive actions ကို actor + store + timestamp ဖြင့် trace လုပ်နိုင်ရမည်။
6. Manager/Cashier သည် မိမိမရသင့်သော action ကို UI ဖုံးထားရုံမဟုတ်ဘဲ direct request တွင် server-side deny ဖြစ်ရမည်။
7. Console error, HTTP 500/419, broken button, stale totals, Myanmar text corruption, mobile overflow တွေ့ပါက သီးခြား defect ရေးပါ။

## 16. Hardware-dependent Checks

Printer/barcode scanner မချိတ်ထားလျှင် အောက်ပါတို့ကို `BLOCKED - hardware unavailable` ဟုရေးပြီး software preview အထိသာစစ်ပါ။ Hardware မစမ်းရသေးဘဲ PASS မလုပ်ရ။

- 58mm/80mm sale receipt preview and actual print
- Return receipt
- Debt collection receipt
- Barcode scan input
- Cash drawer/shift closing slip

## 17. Bug Evidence Standard

Bug တစ်ခုစီအတွက် အောက်ပါ format မဖြစ်မနေသုံးပါ။

```text
Bug ID: QA-<RUN_ID>-NN
Severity: Critical / High / Medium / Low
Role: Platform Owner / Store Owner / Manager / Cashier
Page/URL:
Precondition:
Steps to Reproduce:
Expected:
Actual:
Transaction/Reference ID (secret မပါ):
Screenshot/Evidence:
Console/HTTP Error:
Reproducibility: Always / Intermittent / Once
Data Integrity Impact:
Suggested Area to Inspect (မခန့်မှန်းနိုင်လျှင် blank):
```

Severity guidance:

- **Critical:** Cross-store leak, wrong stock/debt/profit ledger, duplicate financial posting, unauthorized financial action.
- **High:** Core sale/purchase/return/payment workflow မပြီးနိုင်ခြင်း၊ HTTP 500၊ report အဓိကမှားခြင်း။
- **Medium:** Filter/export/receipt/UX issue ဖြင့် workaround ရှိခြင်း။
- **Low:** Copy, spacing, minor visual inconsistency။

## 18. Final Report Format

Final response ကို အောက်ပါအစီအစဉ်ဖြင့် Burmese language ဖြင့်တင်ပါ။ Technical identifiers ကို English ဖြင့်ထားပါ။

1. **Executive Verdict:** `PASS`, `PASS WITH ISSUES`, `FAIL`, သို့မဟုတ် `BLOCKED`
2. **Environment:** Base URL, test date/time, browser/viewport, commit hash (ရနိုင်ပါက), QA Store name/slug
3. **Roles Tested:** Platform Owner, Store Owner, Manager, Cashier
4. **Workflow Results:** Step တစ်ခုချင်း PASS/FAIL/BLOCKED table
5. **Final Reconciliation:** Inventory, receivable, payable, P&L Expected vs Actual tables
6. **Ledger Trace:** Product movement နှင့် debt/payable references
7. **Permission & Isolation Results**
8. **Bugs:** Severity အလိုက်အမြင့်ဆုံးကိုအရင်စီပါ
9. **Hardware/Unverified Areas**
10. **Go-Live Recommendation:** ရောင်းချအသုံးပြုရန် ready မready နှင့် blockers

Report တွင် လုပ်ခဲ့သည်ဟု မခန့်မှန်းရ။ Screenshot/reference/actual value မရှိသော step ကို `NOT VERIFIED` ဟု ရိုးသားစွာရေးရ။ Calculation အားလုံးတူပြီး Critical/High defect မရှိမှသာ overall `PASS` လုပ်ခွင့်ရှိသည်။

# END PROMPT

---

## Owner မှ Run ပြီးနောက် ဆုံးဖြတ်ရန် Exit Gate

- [ ] Inventory final quantities သုံးမျိုးလုံး exact match
- [ ] Customer debt `65,000 MMK` exact match
- [ ] Supplier payable `150,000 MMK` exact match
- [ ] Net revenue `280,000 MMK` exact match
- [ ] COGS `207,000 MMK` exact match
- [ ] Gross profit `73,000 MMK` exact match
- [ ] Expense `20,000 MMK` exact match
- [ ] Net profit `53,000 MMK` exact match
- [ ] Duplicate transaction မရှိ
- [ ] Cross-store leak မရှိ
- [ ] Manager/Cashier unauthorized access မရှိ
- [ ] Critical/High defect မရှိ
- [ ] Hardware မစမ်းရသေးပါက production-ready ဟု မသတ်မှတ်ထား

---

## Source 3: `prompts/DATAPOS_ADMIN_PREPRODUCTION_AUDIT_PROMPT.md`

**SHA-256:** `deb3518b3340430f14b898ecaadae7b2a5c73e78595210fd6216152c7b52f560`

# DataPOS — Admin Pre‑Production Audit + Safe Fix Prompt

**Purpose:** Production မတင်မီ DataPOS Admin / Central Management ကို AI Coding Agent က code correctness, authorization, store isolation, exports, localization, UI/UX, responsive behavior, performance, accessibility, and production safety အားလုံး audit လုပ်ပြီး safe fixes လုပ်ရန်။

**Repository:** `shwepyithit568-commits/DataPOS`
**Known stack baseline:** Laravel 12.x, PHP 8.2+, Blade, Alpine.js, Tailwind CSS 4, Vite; SQLite local/UAT, MySQL production.

This is a **production-readiness audit + repair task**, not a redesign exercise.

---

# ROLE

Act as:

- Senior Laravel Architect
- Senior Backend Engineer
- Senior Frontend Engineer
- UI/UX Specialist
- Multi-Tenant Security Reviewer
- Database / Data Integrity Reviewer
- Accessibility Reviewer
- Performance Reviewer
- QA Engineer

Be skeptical. Verify actual behavior. Fix safe defects. Do not mark incomplete foundations as complete features.

---

# 0. OWNER PRIORITY + PROJECT RULES

Before editing:

Read and obey the current versions of:

- `AGENTS.md`
- `README.md`
- `../Source_of_Truth_Master_MM.md` (`../architecture/Source_of_Truth_Master_MM.md`; see consolidated index)
- relevant POS source-of-truth if admin changes touch POS
- `CHANGELOG.md`
- test/QA notes
- `docs/ops/DEPLOYMENT.md`
- latest Admin UI/UX guide in the repository

### Decision priority

When docs conflict:
1. latest explicit owner instruction
2. Source of Truth
3. approved architecture docs
4. current agent instructions
5. changelog/testing docs
6. current code + tests
7. old assumptions

Do not silently choose an old document over newer owner requirements.

---

# 1. PRODUCTION SAFETY — DO NOT DEPLOY FROM THIS TASK

This repository is being audited **before** production approval.

Do not:
- deploy production
- push automatically
- force-push
- rewrite git history
- mutate live production data
- run destructive DB reset commands
- use UAT/demo seeders on production
- rotate keys/secrets casually

Forbidden against production:

- `php artisan migrate:fresh`
- `php artisan migrate:fresh --seed`
- UAT/demo seeding
- blind destructive rollback
- mass data cleanup without migration/recovery plan

Safe deliverable for this task:
- inspect
- audit
- implement safe local fixes
- add/update tests
- build
- re-test
- report `GO / GO WITH CONDITIONS / NO-GO`

---

# 2. CURRENT ADMIN ARCHITECTURE MAP

Before fixing, map the current implementation.

Inspect:

### Routes
- platform-owner `/admin/**`
- store-scoped `/store/{store_slug}/admin/**`
- `EnsureStoreAccess`
- `ResolveStoreContext`
- `SetLocale`
- capability middleware
- manager/staff role boundaries

### Controllers
Audit all current admin controllers, especially critical areas:

- Dashboard
- Store Management
- User / Staff / Roles
- Products
- Product Master Data
- Brands
- Categories
- Warehouses
- Suppliers
- Inventory / Stock Ledger / Stock Count / Valuation
- Orders
- Promotions
- Receivables / Debt
- Expenses
- Cash / Bank transactions
- Profit & Loss
- Sales analytics
- Repair / Service Jobs / Spare Parts / Warranty
- Backups
- Database tools
- Imports / Import history
- Settings
- Theme / Appearance / Theme Governance
- Banners
- Blog
- Reviews
- Wholesale
- Printers / Barcode / Voucher
- Alerts / Audit logs
- Sync tools

Locate actual current classes; do not rely only on this list.

### Views
Inspect:
- `resources/views/layouts/admin/app.blade.php`
- `resources/views/admin/**`
- `resources/views/components/admin/**`
- shared forms/buttons/modals/tables
- toolbar
- nav groups/sidebar
- toast/alerts
- settings sections
- master data
- products/brands/categories
- export/import UI

### Data layer
Inspect:
- models
- policies/middleware
- services
- `StoreContext`
- migrations/indexes
- export/import services
- money handling
- validation
- transactions
- audit logging

---

# 3. CRITICAL MULTI-STORE DATA ISOLATION

This is a release blocker.

The Store Manager of Store A must not view or mutate Store B data.

Audit at minimum:

- Staff/users
- Roles where store-scoped
- Products
- Categories
- Brands
- Warehouses
- Suppliers
- Purchases where relevant
- Inventory
- Stock counts
- Stock ledger
- Orders
- Customers
- Receivables
- Expenses
- Cash/bank
- Repairs/service
- Warranty/serial/IMEI
- Promotions
- Settings
- Banners
- Reviews
- Blog
- Wholesale
- Exports
- Imports
- Print endpoints
- AJAX/JSON endpoints
- dashboard metrics
- alert polling
- backup/database tooling access

Verify all of:
- index
- show
- create
- store
- edit
- update
- destroy
- bulk actions
- export
- import
- print
- JSON/AJAX
- direct route model binding

### Required cross-store attacks

Try:
- Store A manager + Store B ID in URL
- Store A manager + Store B record in POST/PUT/DELETE
- cross-store bulk IDs
- cross-store export filter
- cross-store print endpoint
- cross-store AJAX endpoint
- cross-store dashboard metric/cache leak
- manipulated `store_slug`
- role escalation
- staff management scope bypass

Protection must exist at server/query level.

Do not rely on:
- hidden menu
- disabled button
- frontend filtering

Add regression tests for each serious issue fixed.

Severity:
- Critical
- High
- Medium
- Low

---

# 4. PLATFORM OWNER VS STORE ADMIN

Verify platform-level `/admin/**` and store-level `/store/{store_slug}/admin/**` do not accidentally share authorization assumptions.

Check:

- Platform owner-only screens
- Store manager-only actions
- Staff read/write limitations
- support mode
- store management
- theme governance
- sensitive backup/database functions
- impersonation/support behavior if any
- route naming / redirects
- cached menu state

A store manager must not gain platform-owner privileges through direct URLs.

---

# 5. BUSINESS LOGIC + DATA INTEGRITY

Check real admin operations, not only page rendering.

## Products / Inventory

Verify:
- product CRUD
- brand/category relationship
- SKU uniqueness rules
- variants
- store scope
- ecommerce visibility
- stock status derivation
- imports
- bulk price tools
- ledger integration
- no direct unsafe stock mutation
- no negative/race-condition corruption

## Orders

Verify:
- status transitions
- store scope
- finance implications
- export
- notes
- alerts
- duplicate operations
- customer linkage

## Money

Follow existing approved project money rules.

Check:
- MMK precision
- float misuse
- totals
- discount/tax order
- immutable posted financial facts where required
- decimal consistency
- reporting totals

Do not invent accounting behavior.

## Receivables / Expenses / Cash Bank / P&L

Look for:
- broken sums
- missing transaction wrapping
- wrong date scope
- cross-store aggregation
- destructive edits without audit
- stale caches
- N+1 queries

If a fix changes finance/accounting architecture, flag separately rather than guessing.

---

# 6. POS-RELATED ADMIN HANDOFF

The Admin area supports POS operations, so verify admin-side configuration/data does not break POS.

At minimum check:
- product availability
- pricing
- staff/roles
- register/store settings
- inventory
- printers/vouchers
- tax/discount settings if present
- daily closing/report links
- stock count/ledger
- barcode

Do not rewrite the POS domain unless the defect is clearly inside this Admin task.

If POS sale correctness itself is questionable, report a separate production blocker and identify the relevant POS test/module.

---

# 7. EXCEL / CSV IMPORT-EXPORT AUDIT

Audit current exports/imports for:

- correct column headers
- correct data mapping
- UTF-8
- Myanmar Unicode
- English/Chinese where relevant
- comma/quote/newline escaping
- empty data
- large data
- date/time formatting
- Asia/Yangon timezone correctness
- currency/quantity formatting
- current UI filters reflected in exported data
- correct store scope
- no cross-store leakage
- stable filename
- safe error handling

### CSV/Excel security
Check:
- formula injection (`=`, `+`, `-`, `@` payloads)
- malformed CSV
- oversized imports
- duplicate rows
- transaction safety
- partial import rollback
- unsafe file MIME/extension handling

If Excel is not actually supported and only CSV exists, say so clearly. Do not claim Excel support from a CSV feature.

Run relevant existing import/export tests and add edge-case regression tests when needed.

---

# 8. LOCALIZATION AUDIT

Admin views should not contain avoidable hardcoded user-facing labels.

Audit:
- `lang/en/messages.php`
- `lang/my/messages.php`
- `lang/zh_CN/messages.php`
- Blade templates
- JS-generated labels
- validation/errors
- tables
- buttons
- sidebar
- settings
- modals
- toasts
- empty states

### Myanmar wording

Make Burmese:
- concise
- natural
- practical
- retail/business friendly
- action-oriented

Avoid literal machine translation.

Examples of direction:
- shorter button labels when context is obvious
- remove unnecessary formal filler
- keep terminology consistent

Keep useful acronyms:
- SKU
- IMEI
- SN
- PIN
- KPay
- COD

Preserve placeholders/interpolation.

No raw translation key should render to the user.

---

# 9. ADMIN UI/UX — CORE DIRECTION

Admin is an operational work tool, not a marketing landing page.

Use:
- compact layout
- clear tables
- predictable filters
- readable totals
- visible actions
- safe destructive actions
- low-end-device-friendly rendering

Avoid:
- oversized hero blocks
- decorative gradients everywhere
- cards inside cards
- huge blank space
- tiny gray text
- random button styles
- accidental action movement
- one-off CSS hacks

Use the current admin layout and shared components wherever possible.

---

# 10. PAGE-LEVEL SPACING — ~4PX

Owner standard:

Direct page-level sibling sections should generally be around **4px** apart.

Typical flow:

Header
↓ ~4px
Banner / Summary
↓ ~4px
Search / Toolbar
↓ ~4px
Filters
↓ ~4px
Table / Grid
↓ ~4px
Pagination / Actions

Rules:

- Prefer shared `gap-1` / `space-y-1` style where appropriate.
- Remove duplicate margins/paddings causing accidental 12–24px gaps.
- This does NOT mean every input/button/card internal padding becomes 4px.
- Preserve touch target and readability.
- Larger spacing is allowed only for a real semantic boundary.

Mobile page outer padding should remain compact, around `8px` where appropriate.

---

# 11. LIGHT MODE — HIGH-CONTRAST DAYLIGHT

Use/normalize semantic theme tokens.

Target:

- page background: `#f4f6f8`
- cards/tables/panels: `#ffffff`
- default borders: `#cbd5e1`
- stronger border: `#94a3b8`
- primary text/icon: `#0f172a`
- secondary: `#1e293b`
- muted/supporting: `#334155`

Audit:
- body
- sidebar
- top bar
- table
- toolbar
- inputs/selects
- cards
- forms
- modals
- dropdowns
- toast
- badges
- empty states
- loading states
- settings
- product/admin panels

Borders must be visible in bright environments.

---

# 12. DARK MODE — TRUE OLED

Target:

- major background/sidebar: `#000000`
- inner cards/panels: `#0a0f1d` or `#111827`
- borders: `#1e293b` / `#334155`
- primary text: `#f8fafc`
- secondary: `#e2e8f0`
- muted: `#cbd5e1`

Requirements:

- major canvas should not remain gray if OLED black is intended
- nested cards/forms/dropdowns/modals must not fall back to white
- no low-contrast text
- status colors remain understandable
- border/divider remains visible but subdued
- focus state remains obvious

Prefer centralized tokens/theme layer over scattered hardcoded values.

---

# 13. BUTTONS — LIGHTWEIGHT 3D / ELEVATED

Buttons must feel tactile but not heavy.

Semantic colors:
- Primary: violet/indigo
- Success: emerald/green
- Warning: amber
- Danger: rose/red
- Neutral: slate

Interaction:

- subtle lower edge/shadow
- hover: slight rise (`translateY(-1px)`)
- active: slight press (`translateY(1px)`)
- active shadow reduces
- clear focus
- clear disabled
- clear loading
- prevent duplicate submit where relevant
- icon-only button has aria-label

Transition target: roughly `120–200ms`.

Prefer:
- transform
- opacity
- bg/border/color
- small box-shadow

Avoid:
- huge glow
- large blur
- continuous animation
- heavy animation libraries
- overused gradients

---

# 14. SMOOTH INTERACTIONS / NO UNNECESSARY PAGE RELOAD

Use existing Blade + Alpine.js + approved shared JS.

Improve local interactions where safe:

- sidebar collapse
- nav groups
- tabs
- filter drawer
- view toggle
- dropdown
- modal
- theme switching
- settings preview
- card/table mode
- expandable sections
- search/filter UI
- small AJAX updates already supported

Rules:

- Do not add a heavy SPA framework.
- Keep server validation and authorization authoritative.
- Keep URL/back-forward behavior correct.
- Prevent duplicate submissions.
- Show loading/success/error feedback.
- Avoid flicker/layout jumps.
- Preserve scroll position when useful.
- respect `prefers-reduced-motion`.

A full reload is acceptable when security/architecture/file download/print/auth requires it.

---

# 15. HEADER / SIDEBAR / NAVIGATION

Audit:

- compact height
- store/module context
- active state
- role-based visibility
- collapsed sidebar behavior
- mobile sidebar
- drawer overlay
- keyboard navigation
- focus states
- long Myanmar labels
- icon consistency
- touch targets
- content not hidden behind fixed UI
- sidebar store switch/context correctness

Do not make major actions unexpectedly move between pages.

---

# 16. TABLE STANDARD

Operational records should favor table view.

Check:
- sticky header where useful
- dark hairline dividers
- row hover/focus
- right-aligned actions
- visible totals
- `tabular-nums` for money/qty/date/reference
- pagination
- empty state
- sorting/filtering
- horizontal scroll on mobile
- no whole-page horizontal overflow

Products/Brands/Categories should use consistent table visual language where applicable.

On small screens, table wrapper may scroll horizontally; the `body` should not.

---

# 17. CARD / MASTER DATA GRID

Where card view is useful:

Target responsive direction:
- Desktop: 5 columns where content supports it
- Tablet: 3 columns
- Mobile: 2 columns

Do not force dense business tables into cards.

Master Data:
- modern multi-column grid
- modern icons
- clear labels
- consistent sizes
- touch-friendly actions
- horizontal-scroll quick-action row only where genuinely useful
- Floating Action Button only for a clear primary action, not as decoration

Product item/card backgrounds should use the available parent width. Remove unnecessary outer margin/padding while preserving internal text/price padding.

---

# 18. FORMS

Audit:
- server-side validation
- old input preservation
- field-level errors
- required/optional labels
- logical grouping
- desktop multi-column layout where useful
- mobile single-column fallback
- sticky save/cancel only when useful
- input type/min/max/step
- autocomplete
- keyboard flow
- disabled/loading state
- destructive confirmation
- CSRF

Do not hide validation only in JavaScript.

---

# 19. CSP / FRONTEND SECURITY

Do not introduce:
- inline `onclick`
- inline `onchange`
- inline `onsubmit`

Prefer:
- Alpine bindings
- existing CSP helpers
- shared JS

Do not weaken CSP to accommodate a shortcut.

Audit scripts for:
- unsafe DOM HTML
- unsanitized dynamic markup
- duplicate listeners
- memory leak / polling leak
- accidental repeated AJAX
- missing teardown

---

# 20. RESPONSIVE AUDIT

Test at minimum:

### Mobile
- 320
- 375
- 390
- 430

### Tablet
- 768
- 820
- 1024

### Desktop
- 1280
- 1366
- 1440
- 1920

Check:
- sidebar
- header
- toolbar
- tables
- forms
- cards
- modals
- settings
- master data
- product admin
- filters
- action buttons
- Burmese labels

No unintended body horizontal scroll.

---

# 21. ACCESSIBILITY

Verify:

- semantic buttons/links
- visible keyboard focus
- input labels
- error associations
- aria labels for icon-only actions
- dialog focus handling
- escape close where appropriate
- readable contrast in both themes
- touch target size
- reduced motion
- table semantics
- status not conveyed by color alone

---

# 22. PERFORMANCE

Check:

- N+1 queries
- dashboard aggregation
- repeated counts
- pagination
- unbounded lists
- slow filters
- export memory usage
- large import memory usage
- unnecessary JS
- huge Blade shared layout cost
- excessive DOM
- polling frequency
- image size
- font CLS
- expensive shadows/animations

Keep low-end POS laptops and phones usable.

Do not trade correctness for a Lighthouse score.

---

# 23. SETTINGS / THEME / FOOTER

Audit Admin settings end-to-end:

- General
- Currency
- Appearance
- Theme
- Contact
- Delivery
- How-to-order
- Footer
- POS settings

Check:
- store scope
- permission
- validation
- save/update
- preview
- rollback/revisions if supported
- success/error feedback
- localization
- storefront rendering impact

Theme settings must not allow unsafe/invalid styling to break admin/storefront.

Footer translations should be concise.

---

# 24. BACKUP / DATABASE TOOLS

These are high-risk admin modules.

Verify:
- platform/store permission
- destructive confirmation
- no arbitrary file path access
- safe filename handling
- storage destination
- backup integrity
- restore protections
- environment restrictions
- no accidental production reset
- no credentials in downloads/logs

Do not perform a real destructive restore as part of this audit.

If a safe isolated restore test environment exists, document exactly what was tested.

---

# 25. TEST SUITE

Inspect current tests. Relevant existing test names may include equivalents of:

- `AdminProductionReadinessTest`
- `ProductionBlockerRemediationTest`
- `BusinessWorkflowAuditTest`
- `AdminDashboardTest`
- `AdminSidebarNavigationUXTest`
- `AdminProductivityEnhancementTest`
- `AdminMasterDataPageTest`
- `AdminStoreManagementTest`
- `AdminUserManagementTest`
- `AdminWarehouseAuthorizationTest`
- `StoreAuthorizationTest`
- `StoreContextResolverTest`
- `StoreScopedRouteSignatureTest`
- `AdminBrandTest`
- `AdminCategoryTest`
- `AdminProductDuplicateAndBulkPriceTest`
- `ProductImportTest`
- `MasterDataExportImportTest`
- `BrandCategoryImportExportTest`
- `AdminOrderFinanceAndExportTest`
- `AdminBannerAndSettingsFormTest`
- `StoreSettingsAndBrandingTest`
- `LocalizationTest`
- `LocalizationKeysParityTest`
- `FrontendAssetIntegrityTest`
- `MigrationSafetyTest`
- `MysqlMigrationSmokeTest`
- `HttpsConfigurationTest`
- `AdminBackupTest`

Locate the actual current equivalents.

Testing workflow:
1. targeted tests for the area being fixed
2. related module suite
3. store-isolation/security tests
4. localization tests
5. production-readiness tests
6. broader/full suite when shared layout/service changes
7. `npm run build` after Blade/Tailwind/JS changes

Never hide pre-existing failures.

---

# 26. LIGHTHOUSE / VISUAL QA

If browser tooling is available:

Run Mobile + Desktop checks for at least:
- Admin dashboard
- Settings
- Products/Master Data
- one dense table page

Report:
- Performance
- Accessibility
- Best Practices
- major layout/render issues

Test:
- Light Daylight
- OLED Dark
- Myanmar
- English
- Chinese where supported

Do not fabricate Lighthouse scores.

---

# 27. DEBUG LOGGING

Add debug logging only where it materially helps diagnose a real problem.

Rules:
- meaningful context
- function/action
- safe IDs/state
- exception context
- no password/token/secret
- no sensitive customer data
- no verbose debug in production
- respect environment log level

Remove temporary `dd`, `dump`, debug banners, console spam.

---

# 28. CODE CLEANUP

During touched areas only, remove:
- dead code
- duplicate imports
- unreachable branches
- obvious duplicate logic
- unsafe inline handler leftovers
- obsolete temporary UI code

Do not turn a focused production audit into a full rewrite.

---

# 29. SAFE FIX PRIORITY

Fix in this order:

1. Critical security / cross-store access
2. Data integrity / finance / inventory correctness
3. Authorization
4. Broken Admin workflow
5. Export/import correctness
6. Production deployment blockers
7. Responsive/mobile rendering
8. Accessibility
9. Localization
10. Light/OLED theme
11. Button consistency
12. Smooth interactions
13. Performance
14. Cosmetic polish

If a cosmetic fix conflicts with business usability, business usability wins.

---

# 30. FINAL REPORT

Return:

## A. Production Decision
- `GO`
- `GO WITH CONDITIONS`
- `NO-GO`

## B. Findings
Table:
- Severity
- Module
- Problem
- Root cause
- Impact
- Fix status
- Verification

## C. Security
Explicitly report:
- cross-store tests
- role escalation tests
- IDOR/BOLA
- CSP/CSRF
- sensitive admin tools

## D. Business/Data Integrity
- products/inventory
- orders
- money
- receivables/finance
- exports/imports

## E. Admin UI/UX
Explicitly report:
- page spacing ~4px
- mobile outer padding ~8px
- full-width layouts
- responsive table behavior
- card/grid 5/3/2 where relevant
- Light Daylight standard
- True OLED Dark standard
- lightweight 3D buttons
- smooth transition/no unnecessary reload
- Myanmar concise localization
- accessibility

## F. Tests
List exact commands and:
- pass
- fail
- skipped
- pre-existing failures

## G. Build
Report `npm run build` result if frontend changed.

## H. Files Changed
Explain every changed file.

## I. Database/Migration Impact
State explicitly:
- none
or
- migration required + why + rollback/data risk

## J. Remaining Blockers
Do not bury them.

## K. Deployment Recommendation
State what must happen before production deploy.

---

# DEFINITION OF DONE

Never say `Done`, `Fixed`, or `Production Ready` based only on code inspection.

A production-ready claim requires evidence that:

- targeted code was inspected
- authorization was tested
- cross-store access was tested
- important business flow was tested
- UI was rendered at phone/tablet/desktop sizes
- Light and OLED Dark were checked
- Myanmar layout was checked
- relevant tests passed
- frontend build passed when changed
- no destructive production operation was performed
- remaining risks are explicitly listed

**Smallest correct + secure + maintainable + verified change wins.**

---

## Source 4: `prompts/datapos_ai_agents_pre_installer_prompts_v1.md`

**SHA-256:** `adfa443d857bbd37fdfa2c244dc846cbfa90c8d08a3bdbd2ed314aa9eb67c178`

# DataPOS — AI Agents Pre-Installer Prompts v1

## အသုံးပြုရန် ရည်ရွယ်ချက်

ဤ prompts များသည် DataPOS ကို Windows EXE/Offline Installer မတည်ဆောက်မီ AI Agents ဖြင့်လုပ်နိုင်သည့် technical verification၊ documentation refresh၊ CI၊ security audit၊ release snapshot automation နှင့် installer plan correction များအတွက်ဖြစ်သည်။

Repository:

```text
https://github.com/shwepyithit568-commits/DataPOS
```

Audit စစ်ဆေးခဲ့ချိန် GitHub `main` latest commit:

```text
d8f7abecfbe74fade6fe2ff06084fd609b0f8252
```

Agent တစ်ယောက်ပြီးမှ နောက် Agent ကိုစပါ။ တစ်ပြိုင်နက်တည်းမလုပ်ပါနှင့်။ Agent တစ်ယောက်စီသည် မစမီ latest `main` ကို pull/fetch လုပ်ပြီး actual HEAD ကို report လုပ်ရမည်။ အထက်ပါ SHA ကို hardcode source of truth အဖြစ် မယူဘဲ လက်ရှိ GitHub `main` HEAD ကိုစစ်ပြီး အသုံးပြုရမည်။

---

## အားလုံးလိုက်နာရမည့် Common Rules

Prompt တစ်ခုချင်းစီအောက်တွင် အောက်ပါစည်းကမ်းများ အကျုံးဝင်သည်—

1. Repository ရှိ `AGENTS.md`၊ `README.md`၊ `docs/README.md`၊ Source of Truth documents၊ `docs/myanmar_business_commercial_readiness_plan_v1.md`၊ historical `docs/archive/completed-phases/phase_f_completion_report.md` နှင့် superseded `docs/archive/superseded-plans/windows_offline_installer_plan_v1.md` ရှိပါက အပြည့်အစုံဖတ်ပါ။
2. Existing unrelated changes ကို မဖျက်၊ reset၊ overwrite သို့မဟုတ် commit မလုပ်ပါနှင့်။
3. `git reset --hard`၊ `git clean -fd`၊ `migrate:fresh`၊ production database reset စသည့် destructive command မသုံးပါနှင့်။
4. Real `.env`၊ credentials၊ APP_KEY၊ customer database၊ backup contents သို့မဟုတ် secret values ကို output/report/GitHub မှာ မဖော်ပြပါနှင့်။
5. Test မ run နိုင်ခြင်းကို PASS မရေးပါနှင့်။ Automated test ကို physical hardware/manual UAT အဖြစ် မရေးပါနှင့်။
6. လူကိုယ်တိုင်စစ်ဆေးရန်လိုသော အရာတိုင်းကို `PENDING — Human Verification` ဟုရေးပါ။
7. Changes မစမီ baseline test/build run ပါ။ Changes ပြီးနောက် targeted tests၊ full tests နှင့် production build ကို သင့်တော်သလို run ပါ။
8. Test counts နှင့် assertion counts ကို မရောပါနှင့်။ Exact command နှင့် exact final summary ကို report ထဲထည့်ပါ။
9. Store scope၊ permission၊ existing POS/Ecommerce/Repair workflows နှင့် existing data compatibility မပျက်ရ။
10. User-facing strings ပြင်ပါက Myanmar၊ English၊ Simplified Chinese translation key parity စစ်ပါ။
11. Agent တစ်ယောက်စီသည် မိမိ scope အတွင်းသာ commit တစ်ခု သို့မဟုတ် logically separated commits ပြုလုပ်ပါ။ Push authorization/configuration ရှိမှသာ push လုပ်ပါ။
12. Final response တွင် full commit SHA၊ changed files၊ reason၊ tests/build exact result၊ limitations နှင့် `git status --short` output ပေးပါ။

---

# Prompt 1 — Release Candidate Baseline၊ Test Evidence နှင့် GitHub CI

```text
DataPOS repository ၏ လက်ရှိ GitHub main branch ကို Windows Offline Installer မစမီ release-candidate baseline အဖြစ် ပြန်စစ်ပြီး automated verification infrastructure ကို production-ready အဆင့်သို့ ပြင်ဆင်ပါ။

Repository:
https://github.com/shwepyithit568-commits/DataPOS

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။

## Objectives

1. Current `main` full HEAD SHA၊ branch၊ remotes၊ `git status --short` ကို မှတ်တမ်းတင်ပါ။ Documentation ထဲရှိ SHA အဟောင်းကို source of truth မယူပါနှင့်။
2. Current environment နှင့် project requirements ကို inventory လုပ်ပါ—PHP၊ Composer၊ Node/npm၊ Laravel၊ SQLite၊ required PHP extensions။
3. Baseline အဖြစ် အောက်ပါတို့ကို run ပါ—
   - `composer validate --strict`
   - `php artisan about --only=environment`
   - `php artisan test`
   - `npm ci`
   - `npm run build`
4. Failing tests/build ရှိပါက root cause ရှာပြီး task scope အတွင်းရှိ regression ကိုသာ ပြင်ပါ။ Tests ကို ဖြုတ်ခြင်း၊ skip လုပ်ခြင်း၊ assertion ပျော့အောင်လုပ်ခြင်းဖြင့် green မလုပ်ပါနှင့်။
5. `.github/workflows/ci.yml` မရှိပါက ဖန်တီးပါ။ ရှိပါက လက်ရှိ architecture နှင့်ကိုက်ညီအောင် ပြင်ပါ။ CI တွင် အနည်းဆုံး—
   - Supported PHP version
   - Required PHP extensions
   - Composer install from lock file
   - Node install from lock file
   - SQLite test setup
   - Laravel key/test environment setup without real secrets
   - `php artisan test`
   - `npm run build`
   - Translation key parity test/command
   - Generated/build artifact check
   ပါရမည်။
6. CI က `database/database.sqlite` သို့မဟုတ် project ၏ canonical testing database ကို deterministic အဖြစ်စီစဉ်ရမည်။ Real/local DB မတင်ရ။
7. CI dependency cache သုံးနိုင်သော်လည်း stale build/vendor ကို source of truth မလုပ်ရ။
8. `main` latest commit အတွက် CI run ဖြစ်နိုင်စေရန် commit ပြုလုပ်ပါ။ Push ခွင့်ရှိပြီး user scope အတွင်းဖြစ်မှ push ပါ။

## Human-only Boundaries

- Physical printer/scanner test မလုပ်နိုင်ပါက PENDING ဟုရေးပါ။
- Seven-day real pilot အဖြစ် automated test ကို မရေတွက်ပါနှင့်။
- Clean Windows install စမ်းသပ်မှု မလုပ်ရသေးပါက PENDING ဟုရေးပါ။

## Deliverables

- CI workflow file
- Baseline verification report: `docs/archive/audits/pre_installer_automated_baseline_report.md`
- Exact test/build summaries
- Full commit SHA and changed files
- Remaining automated blockers
- Human verification pending list

ဤ task တွင် README အကြီးစား rewrite၊ installer build၊ EXE creation သို့မဟုတ် production deployment မလုပ်ပါနှင့်။
```

---

# Prompt 2 — Security၊ Secrets နှင့် Public Repository Audit

```text
DataPOS public GitHub repository ကို Windows installer/release archive မထုတ်မီ read-heavy security and secret exposure audit ပြုလုပ်ပါ။ Confirmed issues ကို scope အတွင်း လုံခြုံစွာပြင်နိုင်ပါက ပြင်ပါ။ Git history rewrite၊ credential rotation သို့မဟုတ် repository visibility ပြောင်းခြင်းကို explicit Project Owner approval မရှိဘဲ မလုပ်ပါနှင့်။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1 ပြီးဆုံးသော latest main ကိုအသုံးပြုပါ။

## Audit Scope

1. Current files နှင့် full Git history ကို secret scanner ဖြင့်စစ်ပါ။ Tool မရှိပါက documented alternatives သုံးပါ။ Secret raw values ကို terminal report/final response တွင် မဖော်ပြရ။ File path၊ commit၊ secret type နှင့် remediation status ကို redact လုပ်ပြီးသာ report လုပ်ပါ။
2. အနည်းဆုံး အောက်ပါတို့ကိုစစ်ပါ—
   - `.env*`
   - APP_KEY
   - DB credentials
   - SMTP/API/payment keys
   - VAPID/web-push keys
   - SSH/private keys
   - Access tokens
   - Real phone/email/customer data
   - SQLite databases
   - Backup ZIPs
   - Uploaded vouchers/images containing private information
3. `.gitignore` သည် `.env`၊ databases၊ backups၊ logs၊ keys၊ `vendor`၊ `node_modules`၊ local Excel files နှင့် build/temp artifacts ကို သင့်တော်စွာ exclude လုပ်ကြောင်းစစ်ပါ။
4. Already tracked sensitive artifact ရှိမရှိ `git ls-files` ဖြင့်စစ်ပါ။ Ignore rule ရှိရုံဖြင့် already tracked file မလုံခြုံကြောင်း report လုပ်ပါ။
5. Production/UAT seeders ထဲတွင် default passwords၊ known PINs နှင့် test accounts မတော်တဆ commercial build ထဲမဝင်စေရန် စစ်ပါ။
6. Installer/release archive exclusion policy ရေးပါ။
7. Dependencies အတွက်—
   - `composer audit`
   - `npm audit --omit=dev` သို့မဟုတ် project-compatible command
   run ပြီး findings ကို severity အလိုက် report လုပ်ပါ။ Safe compatible updates မဟုတ်ပါက version bump မလုပ်ဘဲ remediation plan ရေးပါ။
8. License inventory ပြုလုပ်ပါ—PHP runtime၊ Laravel dependencies၊ PHPSpreadsheet၊ html2pdf.js၊ Noto Sans Myanmar၊ installer tooling။ Redistribution restriction မသေချာပါက Unknown/Pending Legal Review ဟုရေးပါ။

## Deliverables

- `docs/archive/audits/security_and_release_archive_audit.md`
- Sanitized secret findings table
- Required credential rotation checklist for Project Owner
- Installer/source ZIP inclusion-exclusion matrix
- Dependency audit results
- Third-party license manifest draft
- Changed files and commit SHA

Real credentials ကို ကိုယ်တိုင် rotate မလုပ်ပါနှင့်။ Git history ကို approval မရှိဘဲ rewrite/force-push မလုပ်ပါနှင့်။
```

---

# Prompt 3 — Runtime၊ SQLite Database နှင့် Writable Storage Canonicalization

```text
DataPOS ၏ local Windows deployment architecture တွင် documentation/configuration မကိုက်ညီမှုများကို audit ပြီး installer မစမီ canonical runtime layout ကို implementation-ready အဖြစ်သတ်မှတ်ပါ။ Safe and backward-compatible code/config changes လိုအပ်ပါက tests ဖြင့်ပြင်ပါ။ Installer/EXE ကို မတည်ဆောက်သေးပါနှင့်။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1 နှင့် Prompt 2 ပြီးသော latest main ကိုသုံးပါ။

## Known Questions to Resolve from Repository Evidence

1. README တွင် local SQLite path `database/database.sqlite` ဖြစ်သော်လည်း installer plan တွင် `storage/database/datapos.sqlite` ဟုရှိသည်။ Application အမှန်တကယ်သုံးနေသော config၊ `.env.example`၊ migrations၊ tests၊ backup service နှင့် restore service ကိုစစ်ပြီး canonical path တစ်ခုသတ်မှတ်ပါ။
2. Backward compatibility မပျက်စေရန် existing database migration/move/fallback behavior လိုမလို ဆုံးဖြတ်ပါ။ Existing DB ကို silent overwrite မလုပ်ရ။
3. Program binaries နှင့် writable data ကိုခွဲပါ—
   - Application/runtime location
   - Database
   - Uploads/media
   - Logs/cache/sessions
   - Backups
   - Config/secrets
4. `C:\Program Files\DataPOS` နှင့် `%PROGRAMDATA%`/`%LOCALAPPDATA%` တို့၏ Windows permissions implications ကို စစ်ပြီး single-user v1 အတွက် ရွေးချယ်မှုတစ်ခုကို evidence နှင့်ဆုံးဖြတ်ပါ။
5. Backup/restore သည် canonical DB/media paths ကိုသာသုံးကြောင်း automated tests ဖြင့်အတည်ပြုပါ။
6. SQLite WAL၊ busy timeout၊ foreign keys၊ transactions နှင့် abrupt shutdown recovery config ကိုစစ်ပါ။ Unsupported claim မရေးပါနှင့်။
7. PHP built-in server launcher plan အတွက်—
   - loopback-only binding
   - port collision/fallback
   - PID/process ownership
   - stale process cleanup
   - health check
   - graceful shutdown
   - multiple launcher clicks
   - log rotation
   တို့ကို technical design အဖြစ်ရေးပါ။
8. Current `server.php`/front-controller compatibility ရှိမရှိ code ဖြင့်စစ်ပါ။ မရှိသော file ကို plan ထဲမညွှန်းရ။

## Required Tests

- Canonical SQLite path test
- Existing DB preserved test
- Fresh DB initialization test
- Backup includes canonical DB/media test
- Restore returns data to canonical location test
- Writable directory validation test
- Missing/unwritable directory graceful error test
- WAL/foreign key configuration test

## Deliverables

- `docs/windows_runtime_and_storage_architecture.md`
- Updated configuration/tests where required
- Database path migration/backward-compatibility design
- Final path matrix
- Exact test/build results
- Commit SHA and changed files

Do not create installer script၊ launcher EXE၊ Windows scheduled tasks သို့မဟုတ် production package in this task.
```

---

# Prompt 4 — README.md Full Rewrite from Current Repository

```text
DataPOS repository ၏ root `README.md` သည် project စတင်ခါစက အချက်အလက်များနှင့် stale environment details ပါနေသောကြောင့် လက်ရှိ codebase ကို source of truth အဖြစ်အသုံးပြုပြီး README ကို အပြည့်အစုံပြန်ရေးပါ။ README ကို marketing claim မဟုတ်ဘဲ developer၊ tester၊ future maintainer နှင့် installer builder တို့အတွက် တိကျသော entry point ဖြစ်အောင်ရေးပါ။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1–3 ပြီးဆုံးသော latest main ကိုသုံးပါ။

## Mandatory Audit Before Writing

- `composer.json` / `composer.lock`
- `package.json` / lock file
- `.env.example`
- `config/database.php` and runtime configuration
- Routes, modules and middleware
- Migrations/seeders
- Tests
- Backup/restore
- Offline assets/PWA
- Reports/import/export/PDF/printing
- Roles/permissions
- Current docs map
- Current CI workflow

README အဟောင်းကို စကားလုံးပြောင်းရုံ မလုပ်ပါနှင့်။ Current implementation ကို evidence ဖြင့် inventory ပြီး လုံးဝပြန်တည်ဆောက်ပါ။

## Required README Sections

1. DataPOS အကြောင်းအကျဉ်း
2. Current release/readiness status
3. Exact technology stack and supported versions
4. Supported business modes—POS-only၊ Online + Offline၊ optional modules; code က support လုပ်သလောက်သာရေးရန်
5. Implemented module inventory
6. Known partial/deferred modules
7. Local development requirements
8. Windows local/UAT run instructions
9. Canonical database/storage paths
10. Environment setup from `.env.example`
11. Install dependencies/build/migrate commands
12. Safe UAT seed/demo setup
13. Default test accounts—ရှိပါက UAT-only ဟုရှင်းပြပြီး commercial build မှ exclude လုပ်ရန်
14. Automated test and CI commands
15. Backup/restore workflow
16. XLSX/CSV/PDF/printing summary
17. Offline vs sales-channel terminology
18. Security rules and secret handling
19. Destructive commands that must not run on production
20. Documentation index with repository-relative links
21. Current human-verification pending list
22. Installer status and prerequisites
23. Contribution/development rules
24. License/status—အတည်မပြုရသေးပါက proprietary/undecided ကို မခန့်မှန်းဘဲ project owner confirmation required ဟုရေးရန်

## README Accuracy Rules

- Absolute Windows `file:///D:/...` links မသုံးပါနှင့်။ GitHub-compatible relative links သုံးပါ။
- Port `8501`/`8502` စသည့်မကိုက်ညီမှုများကို repository config နဲ့ဖြေရှင်းပြီး canonical value တစ်ခုရေးပါ။
- Tests PASS count ကို current command run မရှိဘဲ hardcode မလုပ်ပါနှင့်။
- “Fully tested”, “production-ready”, “seven-day pilot passed”, “hardware supported” စသည့် claims ကို evidence မရှိဘဲ မရေးပါနှင့်။
- Automated/Manual/Physical status ကို သီးခြားရေးပါ။
- README တွင် real secret၊ real customer data သို့မဟုတ် production credentials မပါရ။

## Verification

- All Markdown links resolve
- Commands match actual project files
- Module names match routes/controllers
- Documentation paths exist
- README statements do not conflict with current completion report

## Deliverables

- Rewritten root `README.md`
- Optional historical `docs/archive/audits/README_AUDIT_NOTES.md` with removed stale claims and reasons
- Exact verification results
- Full commit SHA and changed files

ဤ task တွင် application business logic၊ migrations၊ installer သို့မဟုတ် production deployment မပြင်ပါနှင့်။ README accuracy ကိုထိခိုက်သော critical code mismatch တွေ့ပါက blocker အဖြစ် report လုပ်ပါ။
```

---

# Prompt 5 — Phase F Completion Report Refresh and Evidence Correction

```text
Historical `docs/archive/completed-phases/phase_f_completion_report.md` ကို မပြန်ရေးဘဲ current GitHub HEAD နှင့် actual test evidence အတွက် completion report အသစ်ရေးပါ။ Report အဟောင်းထဲက stale SHA၊ uncommitted changes၊ exaggerated automated/manual claims နှင့် inconsistent test counts ကို မကူးပါနှင့်။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1–4 ပြီးဆုံးသော latest main ကိုသုံးပါ။

## Mandatory Corrections

1. Current full HEAD SHA နှင့် clean/dirty status ကို actual commands ဖြင့် update ပါ။
2. `32 modified + 19 untracked` ကဲ့သို့ stale data ဖယ်ရှားပါ။ Current values သာရေးပါ။
3. Full test suite ကို fresh run လုပ်ပြီး tests၊ assertions၊ failures၊ skipped နှင့် duration ကို မရောဘဲရေးပါ။
4. `npm ci` နှင့် `npm run build` exact result ထည့်ပါ။
5. GitHub CI run/status ရှိပါက URL/status ထည့်ပါ။ မရှိ/မrunနိုင်ပါက Pending ဟုရေးပါ။
6. Automated test က route rendering/CDN regex စစ်ထားခြင်းသာဖြစ်ပါက `Automated Offline Readiness Test` ဟုသာရေးပါ။ `Seven-Day Physical Offline Pilot` ကို PENDING ထားပါ။
7. Simulated exception/transaction rollback test ကို `Simulated Crash/Atomicity Test` ဟုရေးပါ။ Physical power cut test ကို PENDING ထားပါ။
8. ESC/POS byte-generation test ကို automated ဟုရေးပြီး physical 58mm/80mm output ကို PENDING ထားပါ။
9. Automated backup/restore test နှင့် clean second-PC restore ကို သီးခြားခွဲပါ။ Physical clean-PC restore ကို PENDING ထားပါ။
10. Browser UAT၊ printer၊ scanner၊ A4 print နှင့် human usability ကို PENDING ထားပါ။
11. Known limitations ထဲမှ P0/P1 blockers ကို “does not block” ဟုခန့်မှန်းမရေးဘဲ Installer Pilot / Commercial GA gate အလိုက်ခွဲပါ။
12. Database/runtime path decision ကို Prompt 3 ရလဒ်အတိုင်း update ပါ။
13. Documentation links ကို repository-relative links ပြောင်းပါ။

## Final Gate Categories

- PASS — Automated Evidence
- PASS — Human/Physical Evidence
- PENDING — Human Verification
- BLOCKED
- DEFERRED — Explicitly Approved Scope

## Deliverables

- New current completion report; historical `docs/archive/completed-phases/phase_f_completion_report.md` ကိုမပြင်ရ
- Evidence-to-claim matrix
- Remaining human checklist (မလုပ်သေးပါ)
- Installer pilot blockers
- Commercial GA blockers
- Exact test/build output
- Full commit SHA and changed files

Project Owner signature/approval ကို Agent ကိုယ်တိုင် မဖြည့်ရ။ Human UAT ကို PASS မလုပ်ရ။
```

---

# Prompt 6 — Safe Source ZIP and Git Bundle Automation

```text
DataPOS ကို installer မတည်ဆောက်မီ recoverable release snapshot အဖြစ် သိမ်းနိုင်ရန် source ZIP၊ Git bundle နှင့် checksum generation automation ကိုတည်ဆောက်ပါ။ Customer data backup ZIP နှင့် source release ZIP ကို လုံးဝခွဲထားရမည်။ Installer မတည်ဆောက်သေးပါနှင့်။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1–5 ပြီးဆုံးသော latest main ကိုသုံးပါ။

## Required Artifacts and Scripts

1. `scripts/release/build-source-snapshot.ps1`
   - Clean Git worktree မဟုတ်ပါက fail
   - Current tag/version/full SHA capture
   - Tracked source files မှသာ ZIP တည်ဆောက်
   - `.env`၊ database၊ backups၊ uploads၊ logs၊ keys၊ caches၊ `vendor`၊ `node_modules`၊ local spreadsheets ကို exclude
   - `composer.lock` နှင့် npm lock file ကို include
   - SHA-256 checksum ထုတ်
   - Manifest JSON/Markdown ထုတ်
2. `scripts/release/build-git-bundle.ps1`
   - Branches/tags/history ပါဝင်သည့် Git bundle ထုတ်
   - `git bundle verify` run
   - SHA-256 checksum ထုတ်
3. `docs/release_snapshot_and_backup_guide.md`
   - Source ZIP
   - Git bundle
   - Customer data backup ZIP
   - Installer artifact
   တို့၏ ရည်ရွယ်ချက်နှင့် restore procedure ကို သီးခြားရှင်းပြပါ။

## Security Rules

- Build output ကို repository အတွင်း commit မလုပ်ရ။ Output directory ကို gitignored ထားပါ။
- Secret scan မအောင်မြင်ပါက source snapshot build ကို block လုပ်နိုင်သည့် preflight design ထားပါ။
- Real database/customer backup ကို source ZIP ထဲဘယ်တော့မှမထည့်ရ။
- Source ZIP ကို installer bundle ဟုမခေါ်ရ။
- Git bundle ကို public customer distribution မလုပ်ရ။
- Archive names တွင် semantic version၊ short SHA နှင့် build date ပါရမည်။

## Tests

- Generated ZIP inclusion/exclusion assertions
- `.env`/SQLite/key/log absence assertions
- Required lock files/source presence
- Manifest SHA equals Git HEAD
- Checksum verification
- Git bundle verification
- Dirty worktree refuses release snapshot

## Deliverables

- Release snapshot scripts
- Documentation
- Automated tests or safe verification script
- Example filenames only; generated large archives ကို Git commit မလုပ်ရ
- Exact verification output
- Full commit SHA and changed files
```

---

# Prompt 7 — Windows Installer Plan v2 Correction Only

```text
Archived `docs/archive/superseded-plans/windows_offline_installer_plan_v1.md` ကို historical reference အဖြစ်သာဖတ်ပြီး repository evidence နှင့် current verification outputs အပေါ်အခြေခံ၍ `docs/windows_offline_installer_plan_v2.md` ကိုသာ active plan အဖြစ် update လုပ်ပါ။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။

## Required Corrections

1. Canonical application/runtime/database/uploads/logs/backups/config paths ကို Prompt 3 အတိုင်းသုံးပါ။
2. `C:\DataPOS` နှင့် `{autopf}\DataPOS` conflict ကိုဖြေရှင်းပါ။ Program files နှင့် writable data ကိုခွဲပါ။
3. “No admin required”၊ Windows Task Scheduler၊ Program Files ACL၊ startup behavior တို့၏ privileges ကို တိတိကျကျသတ်မှတ်ပါ။
4. PHP runtime version ကို vague/hardcoded မထားဘဲ supported pinned patch version၊ architecture၊ checksum၊ download source နှင့် license verification plan ထည့်ပါ။
5. Required PHP extensions ကို `composer check-platform-reqs` နှင့် current code requirements ကနေ inventory ပြုလုပ်ပါ။
6. PHP built-in server/launcher ကို v1 pilot single-user local app အဖြစ်သာသတ်မှတ်ပြီး process supervision၊ health check၊ port collision၊ duplicate launch၊ graceful shutdown နှင့် log rotation ထည့်ပါ။
7. `server.php` သို့မဟုတ် router file ကို plan မှာသုံးပါက repository ထဲအမှန်တကယ်ရှိ/ဖန်တီးမည့် deliverable ဖြစ်ကြောင်းရှင်းပါ။
8. Database initialization တွင် generic `--seed` မသုံးဘဲ production-safe seeder သီးခြားသတ်မှတ်ပါ။ UAT users/default password/PIN မပါရ။
9. Fresh APP_KEY generation၊ environment creation၊ file permissions နှင့် uninstall data preservation ကို အသေးစိတ်ရေးပါ။
10. Upgrade transaction တွင် pre-update backup၊ version compatibility၊ migration failure rollback နှင့် old binary restore plan ပါရမည်။
11. Auto-backup time ကို hardcode 02:00 AM မထားဘဲ first-run/admin setting မှရွေးနိုင်ရန် စဉ်းစားပါ။ PC ပိတ်နေချိန် missed task behavior ထည့်ပါ။
12. Source ZIP၊ Git bundle၊ customer backup နှင့် installer artifact ကိုမရောရ။
13. Installer file size estimate ကို actual staging build မရှိသေးသရွေ့ estimate ဟုရေးပါ။ UPX ကို blind compression မသုံးဘဲ compatibility/security/antivirus risk review ထည့်ပါ။
14. Code signing ကို pilot နှင့် commercial GA အလိုက်ခွဲပါ။ Self-signed certificate က customer SmartScreen trust မပေးနိုင်ကြောင်းရှင်းပါ။
15. Windows 10/11 “fully tested” ဟု physical/VM evidence မရှိဘဲ မရေးပါနှင့်။ Status ကို Planned/Pending/Automated/Physical အဖြစ်ခွဲပါ။
16. Installer build မစမီ Human UAT pending items ကို gate အဖြစ် ဆက်ထားပါ။

## Required v2 Sections

- Decision log
- Runtime and storage architecture
- First-run and production-safe seed design
- Launcher lifecycle/state machine
- Backup/update/rollback
- Security and secrets
- Code signing and checksums
- Source/release artifact taxonomy
- VM automated installer tests
- Physical PC/hardware tests
- Pilot vs Commercial GA release gates
- Owner open decisions
- Approval checkpoint

## Deliverables

- `docs/windows_offline_installer_plan_v2.md`
- v1-to-v2 correction table
- Unresolved owner decisions
- No installer/EXE implementation
- Full commit SHA and changed files

Project Owner approval မရမီ Inno Setup script၊ launcher executable၊ PHP runtime bundle သို့မဟုတ် final installer မဖန်တီးပါနှင့်။
```

---

# Prompt 8 — Final Cross-Check Agent

```text
Prompt 1–7 agents အားလုံးပြီးဆုံးပြီးနောက် DataPOS pre-installer work ကို read-only final cross-check ပြုလုပ်ပါ။ အဓိက code/business logic မပြင်ပါနှင့်။ Documentation-only factual corrections လိုပါက သီးခြား commit ပြုလုပ်နိုင်သည်။

## Verify

1. GitHub main latest full SHA and clean status
2. CI workflow exists and latest run status
3. Full tests and production build evidence
4. README accuracy and working relative links
5. Canonical database/storage paths are consistent across code, README, backup/restore and installer v2 plan
6. Completion report uses current SHA and honest evidence categories
7. Automated tests are not mislabeled as physical/manual tests
8. Security audit and credential-rotation checklist exist
9. Source snapshot/Git bundle scripts exclude secrets and customer data
10. Installer v2 remains unimplemented and awaits Project Owner approval
11. Human UAT, physical printer/scanner, clean-PC restore, real power-cut and seven-day offline pilot remain PENDING

## Output

Create `docs/pre_installer_ai_work_final_review.md` containing:

- PASS/FAIL/PENDING matrix
- Critical blockers
- Non-blocking limitations
- Human tasks for next day
- Exact Git/CI/test/build references
- Recommendation: Ready or Not Ready for Installer Implementation

Do not mark Ready unless every automated gate is evidenced and all explicitly required human gates are either completed or clearly retained as blockers awaiting Project Owner action.
```

---

## အလုပ်ခိုင်းရန် အကြံပြုအစီအစဉ်

| Order | Agent | Scope | Code Change |
| ---: | --- | --- | --- |
| 1 | Baseline & CI | Tests/build/GitHub verification | CI/fixes only |
| 2 | Security | Secrets/dependencies/licenses | Minimal security fixes |
| 3 | Runtime & Storage | SQLite/writable paths/backup consistency | Config/tests if required |
| 4 | README | Current project documentation | README/docs only |
| 5 | Phase F Report | Evidence correction | Report only |
| 6 | Release Archives | Source ZIP/Git bundle automation | Scripts/docs/tests |
| 7 | Installer Plan v2 | Correct plan only | Docs only |
| 8 | Final Reviewer | Cross-check | Read-only/docs correction |

## နောက်နေ့ လူကိုယ်တိုင်လုပ်ရန် ချန်ထားရမည့်အရာများ

- Browser UAT workflow
- 58mm/80mm thermal printer အစစ်
- A4 printer
- USB/Bluetooth scanner
- Cash drawer
- Clean Windows 10/11 installation
- Backup ကို ဒုတိယကွန်ပျူတာတွင် restore
- Real Windows restart/process recovery
- Physical power-loss test
- Seven-day no-internet pilot
- Store Owner sign-off

AI Agent မည်သူမျှ အထက်ပါ human tasks မလုပ်ရသေးလျှင် PASS ဟုမရေးရ။

---

## Source 5: `prompts/DATAPOS_ECOMMERCE_PREPRODUCTION_AUDIT_PROMPT.md`

**SHA-256:** `e393694578200e7653801ba5b9614a38261beb697dd06a9b26d41062be31cd51`

# DataPOS — E‑Commerce Storefront Pre‑Production Audit + Safe Fix Prompt

**Purpose:** Production မတင်မီ DataPOS E‑Commerce / Storefront ကို AI Coding Agent တစ်ယောက်က current working tree အတိုင်း end-to-end စစ်ဆေး၊ safe fixes လုပ်၊ regression tests run လုပ်ပြီး **GO / NO-GO** report ထုတ်ရန်။

**Repository:** `shwepyithit568-commits/DataPOS`
**Known stack baseline:** Laravel 12.x, PHP 8.2+, Blade, Alpine.js, Tailwind CSS 4, Vite; SQLite local/UAT, MySQL production.
**Important:** GitHub/main or old docs ကို blindly ယုံမထားပါနှင့်။ **Current local working tree + latest owner instructions + Source of Truth + tests** ကို အရင်စစ်ပါ။

---

## ROLE

Act as a:

- Senior Laravel Engineer
- Senior E‑Commerce Engineer
- Frontend/UI/UX Specialist
- Security Reviewer
- Accessibility Reviewer
- Performance Reviewer
- QA Engineer

Your job is **not** to praise the current code. Your job is to find real defects, fix safe defects, prove the fixes with tests/manual verification, and clearly state what is still not production-ready.

---

# 0. NON-NEGOTIABLE RULES

Before changing code:

1. Read:
   - `AGENTS.md`
   - `../Source_of_Truth_Master_MM.md` (`../architecture/Source_of_Truth_Master_MM.md`; see consolidated index)
   - `README.md`
   - relevant `CHANGELOG.md`
   - relevant testing/QA docs
   - `docs/ops/DEPLOYMENT.md`
   - latest UI/UX guide present in the repository
2. Inspect:
   - routes
   - middleware
   - controllers
   - models/services
   - Blade views/components/layouts
   - migrations
   - translations
   - tests
3. Compare documentation against **actual current code**.
4. Existing approved business behavior must not be changed only to make the UI prettier.
5. Reuse existing Blade + Alpine.js + Tailwind patterns.
6. **Do not introduce Livewire or jQuery.**
7. Do not add a heavy SPA framework only to avoid reloads.
8. Do not expose, print, copy, or commit credentials/secrets.
9. Do not run destructive production commands.
10. Do not deploy to production, push, force-push, rewrite git history, or change production data unless the owner explicitly gives a separate deployment instruction.

### Forbidden production-risk commands for this task

Do NOT run against real production data:

- `php artisan migrate:fresh`
- `php artisan migrate:fresh --seed`
- UAT/demo seeders
- destructive DB resets
- mass deletes
- blind migration rollback
- history rewrite

If a schema/business-rule change is necessary and may affect stock, finance, debt, audit history, store isolation, or historical production data, **report it separately instead of guessing**.

---

# 1. FIRST — CREATE AN AUDIT MAP

Before editing, map the current Storefront implementation.

At minimum inspect:

### Routes / middleware
- `routes/web.php`
- `ResolveStoreContext`
- `SetLocale`
- store capability middleware
- auth / guest routes
- rate limiters
- public-page cache middleware

### Storefront controllers
Inspect current equivalents of:

- `Storefront/HomeController`
- `Storefront/CatalogController`
- `Storefront/BrowseController`
- `Storefront/ReviewController`
- `Storefront/BlogController`
- `Storefront/ServiceTrackingController`
- customer account controllers
- order/order-builder controllers
- wholesale flow
- auth/login/register
- locale switching

### Storefront views/components
At minimum inspect:

- `resources/views/layouts/storefront/app.blade.php`
- `resources/views/storefront/**`
- `resources/views/customer/account/**`
- `resources/views/components/product-card.blade.php`
- list-view product card component
- language switcher
- header/navigation/search
- mobile drawer / bottom navigation
- footer
- banners
- product detail
- browse/categories
- order builder / confirmation
- account/favorites
- blog/review UI
- service tracking
- wholesale UI

Do not assume file names if the current tree changed. Locate the actual implementation first.

---

# 2. E‑COMMERCE FUNCTIONAL FLOW AUDIT

Act like a real shopper and verify the complete customer journey.

Check:

1. Storefront home
2. Header / navigation
3. Search
4. Search suggestions
5. Category browsing
6. Brand/category filtering
7. Product catalog
8. Grid/list view switching
9. Product detail
10. Product images/fallbacks
11. Sale/old-price/discount rendering
12. Stock availability presentation
13. Favorites
14. Reviews
15. Login
16. Registration
17. Customer account
18. Customer order history
19. Order builder / online order request
20. Order submission
21. Confirmation page
22. How-to-order/contact
23. Delivery/payment information
24. Blog
25. Wholesale flow
26. Service tracking
27. Language switching
28. Light/Dark theme
29. Footer/social/contact links
30. Browser back/forward behavior

Find and fix:

- dead links
- wrong store slug/context
- broken query-string filters
- state lost unexpectedly
- duplicate requests
- duplicate order submissions
- broken validation
- wrong success/error messages
- empty state bugs
- stale state
- cache-related wrong content
- rendering glitches
- body-level horizontal overflow
- broken mobile drawer
- inaccessible modal/menu/search
- unexpected full reloads for simple local UI state

Do not claim a flow works unless you actually traced or tested it.

---

# 3. MULTI-STORE / TENANT ISOLATION — CRITICAL

Store isolation is a production blocker.

Verify that Store A can never receive Store B data through:

- home page
- catalog
- product detail
- browse/categories
- search suggestions
- account
- favorites
- orders
- order confirmation
- reviews
- blog
- banners
- payment/delivery settings
- footer/contact details
- service tracking
- wholesale data
- cached public pages
- API/AJAX endpoints

Check especially:

- route `store_slug`
- `StoreContext`
- controller queries
- route model binding
- cached results/cache keys
- direct IDs/slugs
- order confirmation URLs
- account order URLs
- favorites
- suggestions endpoint
- review submission
- public cache separation per store

### Required attack-style tests

Attempt equivalent cases:

- Store A URL + Store B product/order ID
- Store A session + Store B order/account record
- manipulated `store_slug`
- direct URL without expected store context
- cached response after switching store
- query-string/header based context switching
- cross-store product slug collision

Server-side protection is required. Hiding records in Blade is not security.

Severity:
- Critical
- High
- Medium
- Low

Add or strengthen automated tests for any isolation issue fixed.

---

# 4. INVENTORY / E‑COMMERCE DATA CORRECTNESS

Do not create a competing stock source.

Verify that storefront stock/availability and online-order effects follow the project's approved inventory/ledger architecture.

Check for:

- stale `stock_status`
- direct/manual stock mutation from storefront code
- oversell possibility
- race conditions
- duplicate reservation/confirmation/cancel effects
- invalid negative availability
- online order lifecycle inconsistency
- ecommerce/POS shared-stock mismatch
- incorrect sale price / old price / promotion window logic

If fixing this touches inventory ledger architecture, money rules, accounting, or historical data, stop broad refactoring and report:
- root cause
- affected files
- proposed safe fix
- migration/data impact
- rollback considerations

Do not invent a new stock architecture.

---

# 5. SECURITY AUDIT

Check Storefront for:

### Authentication / authorization
- session security
- account ownership
- order ownership
- route access
- quick-login/dev-only exposure

### CSRF
All state-changing web forms must have valid CSRF handling.

### Rate limiting / abuse
Verify appropriate protection for:
- login
- registration
- order submission
- reviews
- search suggestions if needed
- favorites/actions
- public lookup endpoints

### XSS / output safety
Audit:
- product names/descriptions
- blog content
- review content
- banners
- settings/contact/footer
- dynamic HTML
- user-entered names/messages

Do not use unsafe rendering unless sanitized intentionally.

### CSP
- Avoid new inline event handler attributes.
- Reuse Alpine.js or project CSP helpers.
- Do not weaken CSP just to make new UI code work.

### Other checks
- IDOR/BOLA
- open redirect
- file upload validation
- MIME/extension checks
- path traversal
- unsafe external embeds
- unsafe URL rendering
- debug data leakage
- stack trace leakage
- secret leakage
- sensitive fields in logs

---

# 6. STOREFRONT UI/UX STANDARD

Audit actual rendered pages, not only class names.

## 6.1 Responsive targets

Verify at minimum:

### Mobile
- 320
- 375
- 390
- 430 px

### Tablet
- 768
- 820
- 1024 px

### Desktop
- 1280
- 1366
- 1440
- 1920 px

No body-level horizontal scrollbar on normal pages.

### Product grid target

Where the catalog/card grid supports it:

- Desktop: 5 columns
- Tablet: 3 columns
- Mobile: 2 columns

Use responsive behavior, not fragile fixed widths.

---

## 6.2 Mobile full-width / full-bleed direction

On mobile:

- use available screen width efficiently
- target outer horizontal page padding around `8px` where appropriate
- remove unnecessary nested margins
- do not make text touch the screen edge
- product cards should use the available parent width
- keep internal text/price/action padding readable

Do not force full-bleed on components where it harms usability.

---

## 6.3 Section spacing

Owner UI direction:

- Direct page-level sibling sections should generally be about `4px` apart.
- Avoid stacked parent gap + child margin + wrapper padding creating accidental 12–24px empty zones.
- Do **not** force every internal control spacing to 4px.
- Preserve readable product-card content, touch targets, form fields, and line-height.

Examples to inspect:
- header → banner
- banner → search
- search → filter
- filter → product grid
- section heading → content
- product sections → following sections
- footer transition

---

# 7. LIGHT + TRUE OLED DARK MODE

Apply consistently to nested components, not only the `<body>`.

## Light Mode — High-Contrast Daylight

Target semantic colors:

- page background: `#f4f6f8`
- cards/panels/tables: `#ffffff`
- default border: `#cbd5e1`
- stronger border: `#94a3b8`
- primary text/icons: `#0f172a`
- secondary: `#1e293b`
- muted/supporting: `#334155`

Check:
- header
- search
- drawers
- cards
- filters
- forms
- product detail
- dropdowns
- account UI
- blog
- order UI
- footer transitions
- empty/loading/error states

## Dark Mode — True OLED

Target:

- main page / major outer surfaces: `#000000`
- inner cards/panels: `#0a0f1d` or `#111827`
- border/divider: `#1e293b` / `#334155`
- primary text: `#f8fafc`
- secondary: `#e2e8f0`
- muted: `#cbd5e1`

Requirements:

- no gray-looking major canvas where OLED black is expected
- no unintended white nested component
- no low-contrast gray-on-black text
- dropdown/input/modal/search/menu states must also support dark mode
- hover/focus/selected/disabled/error/success states must remain readable

Prefer shared semantic tokens / theme utilities over random raw colors scattered across files.

---

# 8. BUTTONS — LIGHTWEIGHT 3D / ELEVATED STYLE

Buttons should be colorful, modern, tactile, and lightweight.

Use semantic colors:
- Primary: existing violet/indigo direction
- Success: emerald/green
- Warning: amber
- Danger: rose/red
- Neutral: slate

Style direction:

- subtle vertical shadow/darker lower edge
- hover: slight lift, e.g. `translateY(-1px)`
- active: slight press, e.g. `translateY(1px)`
- reduce shadow on active
- clear focus ring
- clear disabled/loading state
- icon-only buttons need accessible labels

Prefer approximately `120–200ms` transitions.

Prefer:
- transform
- opacity
- background-color
- border-color
- color
- small box-shadow

Avoid:
- huge blur
- animated glow
- continuously running decoration
- heavy gradients everywhere
- new animation dependency

Keep low-end mobile devices responsive.

---

# 9. SMOOTH INTERACTIONS — NO UNNECESSARY RELOAD

Where existing Blade + Alpine architecture supports it safely, make local UI state changes immediate and smooth.

Candidates:
- mobile menu
- dropdowns
- product grid/list mode
- filters UI
- sort selector
- tabs
- theme switch
- language UI if architecture safely supports reactive update
- favorites state
- accordion
- modal
- search suggestions

Rules:

- Do not add React/Vue/Livewire only for this.
- Do not duplicate server business logic in JS.
- Keep canonical URL/query state correct.
- Back/forward must remain sane.
- Persistent mutations still require server validation.
- prevent double-submit
- show loading/success/error feedback
- preserve scroll position when useful
- respect `prefers-reduced-motion`

Full reload remains acceptable for authentication, file download, print, or flows where server navigation is the correct architecture.

---

# 10. MYANMAR LOCALIZATION

Audit:
- `lang/en/messages.php`
- `lang/my/messages.php`
- `lang/zh_CN/messages.php`
- hardcoded user-facing text

Burmese requirements:

- short
- natural
- retail-friendly
- action-oriented
- not literal machine translation
- consistent terminology

Prefer concise labels where context is already obvious.

Keep standard acronyms when clearer:
- SKU
- IMEI
- SN
- PIN
- KPay
- COD

Preserve placeholders:
- `{name}`
- `{count}`
- `%s`
- Blade/PHP variables

Test Myanmar rendering for:
- button wrap
- clipped nav labels
- search placeholder
- card title
- dialog width
- mobile drawer
- table/header if any
- footer
- order forms

No raw translation key should appear to customers.

---

# 11. ACCESSIBILITY

Verify:

- semantic HTML
- correct button/link usage
- keyboard navigation
- visible focus
- skip/navigation usability
- accessible names
- icon-only aria-label
- input labels
- error association
- dialog/drawer focus handling
- escape-to-close when appropriate
- color contrast
- touch target size
- reduced motion
- image alt text
- screen-reader-safe decorative icons

Do not treat Lighthouse score alone as proof.

---

# 12. PERFORMANCE / CORE WEB VITALS

Check:

- LCP
- CLS
- INP/interactivity
- image dimensions
- lazy loading
- oversized images
- duplicate assets
- blocking scripts/styles
- excessive layout complexity
- unnecessary Alpine watchers
- huge layout file impact
- query count / N+1
- public-page cache correctness
- cache key store separation
- repeated AJAX calls
- search suggestion debounce/cancellation
- font loading / CLS
- Vite production build

Do not add expensive visual effects that hurt low-end phones.

---

# 13. SEO / PUBLIC STORE QUALITY

Check where relevant:

- page title
- meta description
- canonical
- robots behavior
- product structured metadata if already supported
- duplicate URLs/query variants
- product/category discoverability
- broken social/meta image
- image alt
- 404 handling
- unavailable product behavior
- no accidental indexing of auth/admin/private pages

Do not invent a new SEO subsystem if none is required; fix concrete production issues.

---

# 14. TESTS TO INSPECT / RUN

Use the current test tree. Relevant existing tests may include equivalents of:

- `ProductCatalogTest`
- `ProductDetailTabsAndSpecsTest`
- `ProductDiscoveryTest`
- `ProductEcommerceVisibilityTest`
- `WebCatalogProductVisibilityTest`
- `StorefrontBannerRenderingTest`
- `StorefrontBrandingRenderingTest`
- `StorefrontBrowseTest`
- `StorefrontEmptyCategoryFilterTest`
- `StorefrontNavigationContextTest`
- `StorefrontHowToOrderTest`
- `StorefrontChatButtonSettingTest`
- `StorefrontSocialMediaSettingTest`
- `StorefrontTaglineTest`
- `FlashSaleHomeSectionTest`
- `HomeBannerDescriptionTest`
- `CustomerAccountTest`
- `CustomerOrderBuilderTest`
- `OrderRequestTest`
- `StorePaymentDeliveryAndMapTest`
- `StoreSettingsAndBrandingTest`
- `StoreAuthorizationTest`
- `StoreContextResolverTest`
- `StoreScopedRouteSignatureTest`
- `LocalizationTest`
- `LocalizationKeysParityTest`
- `FrontendAssetIntegrityTest`
- `HttpsConfigurationTest`

Do not assume filenames still exist. Locate current equivalents.

Workflow:
1. run targeted tests around changed code
2. fix failures caused by the changes
3. run broader relevant Storefront tests
4. run full suite if practical / required by shared changes
5. run `npm run build` for Blade/Tailwind/JS asset changes

Do not hide unrelated pre-existing failures. Report them separately.

---

# 15. MANUAL VISUAL QA

Use browser/dev preview if available.

Test both:
- Light Mode
- OLED Dark Mode

Test languages:
- Myanmar
- English
- Chinese if currently supported

Test user states:
- guest
- logged-in retail customer
- wholesale customer where applicable

Capture/report:
- page
- viewport
- problem
- severity
- reproduction steps
- root cause
- fix
- re-test result

---

# 16. LIGHTHOUSE

Run relevant public pages on Mobile + Desktop if tooling is available.

Report:
- Performance
- Accessibility
- Best Practices
- SEO
- major LCP/CLS/INP causes

Do not fabricate scores if Lighthouse cannot run.

---

# 17. SAFE FIX POLICY

You are authorized by this prompt to make **small, clearly correct, production-safe fixes** within the E‑Commerce/Storefront scope.

Prioritize:
1. Critical security/data leak
2. Order correctness
3. Store isolation
4. Broken shopper flow
5. Mobile/rendering regression
6. Accessibility
7. Theme consistency
8. Localization
9. Performance
10. Cosmetic polish

Avoid unrelated rewrites.

If a fix requires:
- broad schema redesign
- inventory architecture change
- finance/accounting rule change
- destructive data migration
- major route contract change
- large cross-module redesign

document it as a blocker/proposal instead of guessing.

---

# 18. FINAL PRODUCTION READINESS REPORT

Return a structured final report:

## A. Executive Result
- `GO`
- `GO WITH CONDITIONS`
- `NO-GO`

## B. Critical / High Findings
For each:
- Severity
- Area
- Root cause
- Security/business impact
- Files affected
- Fix status
- Verification

## C. Functional Shopper Flow
- Passed
- Failed
- Not tested

## D. Store Isolation
Explicitly state whether cross-store leakage was tested and what happened.

## E. UI/UX
Report:
- responsive
- mobile overflow
- 5/3/2 grid
- 8px mobile outer padding
- ~4px section rhythm
- Light Daylight theme
- OLED Dark theme
- 3D/elevated buttons
- smooth transitions
- localization
- accessibility

## F. Performance / Lighthouse
Actual results only.

## G. Tests
Include commands and:
- passed
- failed
- skipped
- pre-existing failures

## H. Files Changed
Explain why each file changed.

## I. Remaining Risks
Anything not verified must stay visible.

## J. Production Blockers
List exact blockers before deployment.

---

# DEFINITION OF DONE

Do not say **Done / Fixed / Production Ready** unless:

- code path was inspected
- relevant bug was reproduced or clearly proven
- fix was implemented
- targeted tests passed
- production asset build passed when frontend assets changed
- critical Storefront flows were re-tested
- Store isolation was checked
- Light + OLED Dark rendering was checked
- Mobile/Tablet/Desktop rendering was checked
- remaining risks are documented

**No fake confidence. Evidence first.**
