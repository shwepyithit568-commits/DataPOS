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
- COGS = `189,000 MMK` (`2×60,000 + 3×3,000 + 1×60,000 + 2×18,000`)
- Gross profit = `116,000 MMK`
- Customer receivable = `130,000 MMK`
- Cash sale = `160,000 MMK`; digital sale = `15,000 MMK`; credit sale = `130,000 MMK`

## 10. Customer Return

Sale S-3 ထဲမှ P-C `1 unit` ကို original sale reference ဖြင့် return/refund လုပ်ပါ။ Refund ကို customer debt adjustment/credit အဖြစ် သတ်မှတ်နိုင်ပါက ယင်းကိုသုံးပါ; cash refund မလုပ်ရ။

Expected after return:

- P-C stock = `14`
- Customer debt = `105,000 MMK` (`130,000 - 25,000`)
- Net sales revenue = `280,000 MMK`
- Net COGS = `171,000 MMK`
- Gross profit = `109,000 MMK`
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
Net COGS          = 171,000 MMK
Gross Profit      = 109,000 MMK
Operating Expense =  20,000 MMK
Net Profit        =  89,000 MMK
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
| COGS | 171,000 | | | |
| Gross Profit | 109,000 | | | |
| Expenses | 20,000 | | | |
| Net Profit | 89,000 | | | |

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
- [ ] COGS `171,000 MMK` exact match
- [ ] Gross profit `109,000 MMK` exact match
- [ ] Expense `20,000 MMK` exact match
- [ ] Net profit `89,000 MMK` exact match
- [ ] Duplicate transaction မရှိ
- [ ] Cross-store leak မရှိ
- [ ] Manager/Cashier unauthorized access မရှိ
- [ ] Critical/High defect မရှိ
- [ ] Hardware မစမ်းရသေးပါက production-ready ဟု မသတ်မှတ်ထား

