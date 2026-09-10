# DataPOS — Shop Owner / Manager / Cashier End-to-End Audit Report

> ဤအစီရင်ခံစာသည် `AI_AGENT_SHOP_OWNER_MANAGER_E2E_AUDIT_PROMPT_MM.md` ပါ scenario နှင့်အညီ
> DataPOS **Main Project** (`D:\xmapp\htdocs\DataPOS`) ကို ပြန်လည်စစ်ဆေးခြင်း ဖြစ်သည်။
> စစ်ဆေးမှုသည် **code + test + route/middleware-level audit** ဖြစ်ပြီး browser UI UAT မဟုတ်သေးပါ။
> Browser-based UI workflow များကို `NOT VERIFIED` ဟု ရိုးသားစွာ ဖော်ပြထားသည်။

---

## 1. Executive Verdict

> **Note (2026-09-01):** အောက်ပါ Fix Log အရ Bug 1, 2, 4 တို့ကို ပြုပြင်ပြီးဖြစ်သည်။
> Main Project မှ **test suite 1559 / 6731 assertions အားလုံး green (0 failure)** ဖြစ်သွားပြီ။
> ကျန်နေသေးသည့် blockers = Browser UI UAT (exact MMK reconciliation) + hardware testing သာ။
>
> **PASS WITH ISSUES (Code/Test Level)**
- Core accounting ledger (POS sale → inventory movement → customer debt → supplier payable → P&L) သည်
  feature tests များဖြင့် coverage ကောင်းမွန်ပြီး passing ဖြစ်သည်။
- Test suite: **1555 tests မှ 4 failure** (mrow မှာ detail တွေ့နိုင်သည်)။
- **Critical bug မတွေ့ရသေးပါ**။ သို့သော် role/permission gap (Cashier role မရှိခြင်း) နှင့်
  frontend defect တစ်ခုတို့ကြောင့် Overall `PASS` မပေးဘဲ `PASS WITH ISSUES` ဖြစ်သည်။

---

## 1.5 Fix Log (2026-09-01) — ပြုပြင်ပြီးသား

| Bug | Fix | Verification |
|---|---|---|
| 🔴 Bug 4 (Cashier server-side deny) | New middleware `app/Http/Middleware/EnsureFinanceAccess.php` (`finance_access` alias) — P&L, admin receivables, expenses, expense-categories, cash/bank transactions တို့ကို Owner/Manager သာလာအောင် route-level `403` deny။ Sidebar မှာလည်း `canManageFinance` ဖြင့် ဆော့ link များ staff မမြင်ရ။ POS နှင့် supplier payables (POS back-office) ကို staff အတွက် ထားဆဲ။ | New test `tests/Feature/Admin/FinanceAccessControlTest.php` (4 tests) + suite green ✓ |
| 🟠 Bug 1 (StockLedger month-boundary flakiness) | `StockLedgerTest` index/filter/search များတွင် `preset => 'all'` သုံးသည် — calendar-month ပေါ် မမူတည်တော့။ | Suite green ✓ |
| 🟠 Bug 2 (`x-collapse` Alpine plugin) | `delivery.blade.php` မှ unregistered `x-collapse` ကို Alpine core `x-transition` (plugin မလို) ဖြင့် အစားထိုး | `FrontendAssetIntegrityTest` green ✓ |
| 🟡 Bug 3 (opening-stock double-count risk) | Not changed (design note သာ — ပြင်ရန် ဆုံးဖြတ်ရန် ကျန်) | — |

Related tests update: `AdminExpenseTest`/`AdminExpenseCategoryTest` တို့မှ staff-can-view ကို staff-is-denied (403) သို့ ပြောင်းသည် (audit §13 နှင့်ကိုက်ညီ)။

---

## 2. Environment

| Item | Value |
|---|---|
| Project | DataPOS (Laravel) — Main Project `D:\xmapp\htdocs\DataPOS` |
| Branch / Commit | `main` @ `db1ce08` |
| App URL (dev .env) | `http://127.0.0.1:8502` |
| DB | `DB_CONNECTION=sqlite` → `database/database.sqlite` (live data, migration 100% Ran) |
| PHP | 8.2.12 (CLI) |
| Test DB | in-memory sqlite (`phpunit.xml`) — live DB ကို မထိဘဲ testing |
| Test date | 2026-09-01 |

> ⚠️ Browser UI UAT (BASE_URL 8501) ကို ဤစစ်ဆေးမှုတွင် မလုပ်ရသေးပါ။
> Section 9 "Hardware/Unverified" နှင့် အောက်ပါ workflow table တွင် `NOT VERIFIED` ဟု ခွဲခြားထားသည်။

---

## 3. Roles & Tenant Isolation (Audit §5, §15)

### Routes (web.php) — code evidence
- **Platform Owner** routes: `/admin/*` — `platform_owner` middleware (`EnsurePlatformOwner`) ✅
- **Store admin** routes: `/store/{store_slug}/admin/*` — `auth` + `EnsureStoreAccess:store_manager,staff`
  + `ResolveStoreContext` + per-section `store_manager` only gate ✅
- Tenant context: `ResolveStoreContext` + `StoreContext` resolves store by slug; controllers
  cross-check `$product->store_id !== $store->id → 403` (ProductController), route bindings scoped by store ✅
- `hasStoreRole()` hierarchical: store_owner > store_manager > staff ✅

### ⚠️ GAP : Finance-sensitive pages အတွက် server-side deny မရှိခြင်း
- User pivot roles = `store_owner / store_manager / staff / customer / platform_owner`။
  dedicated **`cashier`** user-role type မရှိပါ — cashier ကို granular **`StaffRole` permission slug `cashier`**
  (`app/Models/StaffRole.php:722`) အနေဖြင့် ကိုယ်စားပြုထားသည်။
- သို့သော် finance/admin-sensitive web routes (`/admin/profit-loss`, receivables, payables, expenses,
  settings, transactions, audit-logs …) အားလုံးတွင် route middleware သည် `EnsureStoreAccess:store_manager,staff`
  သာဖြစ်ပြီး **`staff` role တစ်ယောက်တည်းက ဤ pages အားလုံးကို server-side 403 မရဘဲ direct URL ဖြင့် ဝင်နိုင်သည်**။
  `cashier` StaffRole permission သည် POS-level behaviors (ဥပမာ price override PIN) နှင့် UI sidebar ကိုသာ သက်ရောက်ပြီး
  finance pages ၏ **route-level hard deny ကို မဆောင်ရွက်ပါ**။
  → Audit §5.3 "Cashier သည် sensitive pages ကို server-side deny ရမည်" လိုအပ်ချက်ကို **မပြည့်မီ**။ Severity: **High**.
- Fix: finance-sensitive routes တွင် `staff`/cashier ကို server-side block လုပ်ရန် (compulsory) middleware အသစ်
  (သို့) `EnsureStoreAccess` တွင် `:store_manager` တင်းကျပ်ရန် — UI မှာဖုံးရုံဖြင့် မလုံလောက်။

### Cross-store isolation
- Handled ကောင်းမွန် (context-based route + per-controller store_id check + `BusinessWorkflowAuditTest`
  test_scenario_6_multi_store_isolation ✅)။ Live UI UAT တွင် record-id ပြောင်း၍ ထပ်စစ်ရန် လိုပါသည်။

---

## 4. Worksflow Results (Code/Test Level)

| Audit Section | Workflow | Status (Code/Test) | Evidence / Note |
|---|---|---|---|
| §4, §5 | Store creation (Platform Owner), user roles/assign | ✅ Implemented | `StoreManagementController`, `UserManagementController`, `StoreOnboardingService` |
| §6.1 | Categories CRUD | ✅ | `CategoryController` (routes present) |
| §6.2 | Products + Opening Stock | ⚠️ Double-count risk | Product create `initial_stock>0` posts `opening_balance` ledger (`ProductController:704`) နှင့် Opening Stock UI (`OpeningStockService`) နှစ်ခုလုံးရှိသည်။ ၎င်းတို့ကို တစ်ပြိုင်နက်သုံးပါက opening stock နှစ်ခါတက်နိုင်သည် (audit §6.2 warning)။ |
| §6.3 | Supplier / Customer master | ✅ | `SupplierController`, `CustomerDirectoryController`, opening payable/debt `0` |
| §7 | Baseline snapshot | ⚠️ Needs UAT | Stock/debt baseline ပြသမှုသည် UI report မှ ဆွဲရန် လို — `NOT VERIFIED` |
| §8 | Purchase (PO → ordered → received) + partial payment + payable | ✅ Implemented | `PurchaseOrderService` — payable accrues at creation, `paid_amount` decrements, `remaining_balance` tracked with bcmath ✅ |
| §9 | POS Sales Cash / Digital / Credit | ✅ Implemented (+tests) | `PosSaleService`, Payment method (Cash/KPay) via `StorePaymentMethod`; credit → customer receivable. POS feature tests passing ✅ |
| §10 | Customer Return (credit-based) | ✅ Implemented | `PosReturnService` — credit refund reduces receivable, stock back at original COGS, over-refund guarded, idempotent ✅ |
| §11 | Stock Damage Adjustment | ✅ Implemented | `InventoryAdjustmentService` (staff submit → manager approve), ledger `adjustment` |
| §12.1 | Customer debt collection | ✅ Implemented | `CustomerDebtService` (`collect`), ledger entries immutable |
| §12.2 | Supplier payment | ✅ Implemented | `PurchaseOrderService::applySupplierPayment` — non-stock-affecting ✅ |
| §13 | Expense + Net Profit | ✅ Implemented | `ExpenseController`, `ProfitLossService` — debt collection & supplier payment ကို revenue/expense မထည့် (correct per audit) ✅ |
| §14.4 | Movement trace (Opening→Purchase→Sale→Return→Adjustment) | ⚠️ **3 test FAIL** | `StockLedgerTest` index/filter/search — date-boundary flakiness (အောက်တွင် Bug 1) |
| §15.5 | Audit Logs | ✅ Implemented (AuditLogController, AuditLog::write) |
| §16 | Hardware (58/80mm receipt, barcode, drawer) | ⚠️ BLOCKED | Printer module present (`PrinterService`, receipts preview) — hardware မချိတ်ထား → **BLOCKED - hardware unavailable** |
| §15.6 | Server-side deny for Cashier | ❌ **GAP** | Cashier role မရှိခြင်း (Section 3) |

---

## 5. Bugs Found (Severity အလိုက်)

### ✅~FIXED~ 🔴 Bug 1 — Stock Ledger test failures (3 tests) — Medium (test/robustness)
- **Bug ID:** AUDIT-01
- **Page/URL:** `/store/{slug}/admin/stock-ledger`
- **Finding:** `tests/Feature/Admin/StockLedgerTest.php`
  → `test_admin_can_access_stock_ledger_index`,
  → `test_admin_can_filter_movements_by_flow_and_type`,
  → `test_admin_can_search_movements_by_product_or_sku` တို့ fail ဖြစ်နေသည်။
- **Root cause (code evidence):** controller `resolveDateRange()` default preset = `this_month`
  (`now()->startOfMonth()` မှ `endOfMonth()`); test က movements ကို `now()->subDays(1..5)` (late **August**) ဖြင့် ဖန်တီးသည်။
  Test run ရက်သည် **Sep 1** (month boundary) ဖြစ်နေ၍ August movements များ `this_month` filter (September) တွင် မပါဝင်ဘဲ
  page တွင် product/movement မပေါ်တော့ဘဲ `assertSee` fail ဖြစ်ခြင်း။
- **Impact:** App မှားတွက်နေခြင်းမဟုတ် — test သည် month-boundary မှာ flaky ဖြစ်ခြင်းသာ။
- **Fix:** Test တွင် `preset => 'all'` (သို့) explicit date range သုံးရန်၊ သို့မဟုတ် movements များကို
  "this month" အတွင်း ကျစေရန် `now()->subDays(...)` အစား current month date များသုံးပါ။

### ✅~FIXED~ 🟠 Bug 2 — `x-collapse` without Alpine plugin — Low/Medium (frontend)
- **Bug ID:** AUDIT-02
- **File:** `resources/views/admin/settings/sections/delivery.blade.php:149`
- **Finding:** `x-collapse` attribute သုံးထားသော်လည်း `@alpinejs/collapse` package မတပ်ဆင်ထား၊
  `Alpine.plugin()` register မလုပ်ထား။ `FrontendAssetIntegrityTest::test_x_collapse_is_not_used_without_plugin` fail။
  Delivery/Payment settings တွင် edit section ၏ collapse/expand animation မအလုပ်လုပ်ခြင်း။
- **Fix:** `@alpinejs/collapse` ထည့်ပြီး `app.js` တွင် `Alpine.plugin(collapse)` register လုပ်ရန်
  (သို့) `x-collapse` အစား plain `x-show` + CSS transition သုံးရန်။

### 🟠 Bug 3 — Opening stock double-count risk (design) — Medium (data integrity)
- **Bug ID:** AUDIT-03
- **Page/URL:** Product create form (`initial_stock`) + `/store/{slug}/pos/opening-stock`
- **Finding:** Product create တွင် `initial_stock>0` ထည့်ပါက `opening_balance` ledger movement
  ချက်ချင်းတင်သည် (`ProductController:704`); Opening Stock module (`OpeningStockService`) လည်း
  opening stock တင်နိုင်သည်။ နှစ်နေရာလုံးတွင် quantity ထည့်လျှင် opening stock နှစ်ဆတက်
  (audit prompt ၏ §6.2 warning နှင့် ကိုက်ညီ)။
- **Fix:** ပိုမိုရှင်းလင်းရန် — product form မှ `initial_stock` field ကို disabled/deprecated လုပ်ပြီး
  opening stock သည် Official Opening Stock UI မှသာ တင်နိုင်စေရန် (သို့) create-time initial stock ကို
  `source_type=opening_stock` same-channel ဖြင့် သွား၍ idempotency စစ်ယူပါ။

### ✅~FIXED~ High — Cashier အတွက် finance pages server-side deny မရှိ (permissions gap)
- **Bug ID:** AUDIT-04 · Severity: **High** (server-side authorization gap)
- **Detail:** Section 3 တွင် ဖော်ပြထားသည်။ `cashier` StaffRole permission ရှိသော်လည်း finance/admin-sensitive
  routes တွင် hard deny middleware မရှိ — `staff` pivot-role သည် P&L, receivables, payables, expenses, settings,
  audit-logs များကို server-side ဝင်နိုင်သည်။ Audit ၏ "Cashier does not reach sensitive pages" ကို မပြည့်မီ။
- **Fix:** finance-sensitive routes တွင် cashier/staff အတွက် route-level deny middleware (server-side) ထည့်ပါ
  (granular `custom_permissions` ကိုလည်း route-level enforcement လုပ်နိုင်သည်)။

---

## 6. Final Reconciliation (Audit §14) — NOT VERIFIED (UI)

Expected values (prompt မှ) ကို ရေးထားသော်လည်း **browser UI UAT** မလုပ်ရသေးသဖြင့်
Actual ကို မဖြည့်နိုင်ပါ။ ဤနားတွင် run ရန် ကျန်သည် —

| Metric | Expected | Actual | Status |
|---|---:|---:|---|
| Inventory P-A / P-B / P-C | 12 / 25 / 14 | — | NOT VERIFIED |
| Customer receivable | 65,000 | — | NOT VERIFIED |
| Supplier payable | 150,000 | — | NOT VERIFIED |
| Net Revenue | 280,000 | — | NOT VERIFIED |
| Net COGS | 171,000 | — | NOT VERIFIED |
| Gross Profit | 109,000 | — | NOT VERIFIED |
| Expenses | 20,000 | — | NOT VERIFIED |
| Net Profit | 89,000 | — | NOT VERIFIED |

ဤတန်ဖိုးများအတွက် စဉ်းစားမှု: Core services (PosSaleService / PosReturnService /
PurchaseOrderService / ProfitLossService / CustomerDebtService) များသည် bcmath MMK precision ဖြင့်
audit expectation အတိုင်း cash/digital/credit + return + collection + payment ကို ခွဲထားပြီး
debt collection နှင့် supplier payment ကို P&L ထဲ revenue/expense အနေဖြင့် မထည့်ဘဲ —
**ကုဒ်အဆင့်တွင် matching** ဖြစ်နိုင်ခြေမြင့် သော်လည်း live UI ဖြင့် confirm မလုပ်ရသေးပါ။

---

## 7. Ledger Trace (Audit §14.4)

- `inventory_movements` (opening_balance → purchase_received → pos_sale → sales_return → adjustment)
  + `InventoryMovementType` enum တွင် full chain ရှိသည်။ `InventoryService::postMovement` သည် atomic ledger။
- Customer ledger (`customer_ledger`): sale / return-credit / collection — immutable entries (CustomerDebtService) ✅
- Supplier payable (`suppliers.total_credit` + PO `remaining_balance` / `paid_amount`) ✅
- **⚠️** Stock Ledger **index/bin-card UI** သည် bug/filter နှင့် server-render အပေါ် မူတည်၍
  September-1 ကဲ့သို့ month boundary ရက်တွင် default view မှ မကြာခဏ data မပေါ်နိုင် (Bug 1 root cause)။
  UAT ၌ `preset=all` သို့ custom range သုံးပါရန် အကြံပြုသည်။

---

## 8. Permission & Isolation Results (Code)

- Platform Owner `/admin/stores` — platform_owner only ✅
- Store Owner `/admin/users` — `store_owner` only ✅
- Cross-store: per-controller `store_id` check + route model binding scoped ✅ (more UAT needed)
- ❌ Cashier POS-only deny — NOT met (Bug 4)

---

## 9. Hardware / Unverified Areas (Audit §16, §18.9)

- 58mm/80mm sale/return/collection receipt **hardware print** — module (`PrinterService`) ရှိသော်လည်း
  printer မချိတ်ထားသဖြင့် **BLOCKED - hardware unavailable**။ Software preview သာရှိနိုင်သည်။
- Barcode scanning input, cash drawer, shift closing slip — **NOT VERIFIED** (hardware)။
- Browser UI ဖြင့် exact MMK reconciliation (§14) — **NOT VERIFIED** (တင်ရန် ကျန်)။

---

## 10. Go-Live Recommendation

- **Ready state:** Core ledger/accounting ကောင်း၊ security middleware (`finance_access`) ရှိလာပြီး
  test suite **1559 tests / 0 failure** green။ ကျန်နေသေးသည့် blockers:
  1. Browser UI E2E UAT (exact MMK reconciliation — §14) — **NOT VERIFIED**။
  2. Hardware (58/80mm printer, barcode scanner, cash drawer) — **BLOCKED - hardware unavailable**။
  3. Optional: opening-stock double-count risk (Bug 3) အပြီးသတ် ဆုံးဖြတ်ခြင်း။
  ယင်းတို့ မပြီးမချင် **production-ready ဟု မသတ်မှတ်ရ** (audit §18.10)။

---

## 11. Live UAT Attempt (2026-09-01) — BLOCKED at QA-Store Creation (§4)

❌ **Verdict:** `BLOCKED` — scenario စတင်နိုင်ခြင်း မရှိပါ (QA store မဖန်တီးနိုင်)။

### လုပ်ခဲ့သည့်အဆင့်များ (real browser UI)
1. Server `http://127.0.0.1:8501` တွင် Platform Owner `09100000001` အကောင့် log-in (page ၏ QUICK LOGIN ဖြင့် — form credentials မသိ၍ မခန့်မှန်း) ✅
2. `/admin/stores` → `+ ဆိုင်ခွဲ/စတိုး အသစ်ဖွင့်ရန်` → Electronics edition pre-selected, owner/phone/password/PIN, store name `Mingalar Tech Mart QA 0901a`, slug `mingalar-tech-qa-0901a`, Myanmar, Mandalay address — အားလုံး ဖြည့်ပြီး submit ⛔

### Blocker (evidence)
| Evidence | Detail |
|---|---|
| UI | Submit button ကို နှိပ်ပြီးနောက် button သည် `သိမ်းဆည်းနေပါသည်…` (Saving) အဖြစ် disabled ဖြစ်ပြီး နောက်မလှုပ်တော့။ Confirmation modal မရှိ။ |
| Network | Browser network log တွင် `/admin/stores` သို့ **POST မပို့ပါ** (create စာမျက်နှာ load ပြီးနောက် POST မရှိ) → form submit ကို app ၏ global click/double-submit handler က ပိတ်ထားပုံရသည်။ |
| DB (read-only) | `stores` table မှာ QA store မထွက် (store id 1 = datapos-mobile သာရှိ), users=7, categories=36 — **မည်သည့်အရာမှ မထည့်သွင်းပါ**။ |
| Server log | Store-create POST အတွက် server exception/log မရှိ။ |
| Reproduced | Stale server (:8501) နှင့် fresh current-code server (:8502) နှစ်ခုလုံးတွင် တူညီစွာ ဖြစ်သည်။ |

### Related defect — running server stale vs on-disk code
- `storage/logs/laravel.log` တွင် `local.ERROR: Route [store.admin.expense_categories.index] not defined` (01:06) — သို့သော် route သည် လက်ရှိ code တွင် **defined** ဖြစ်နေသည် (`route:list` က အတည်ပြု) → :8501 server သည် file များနှင့် မကိုက်ညီသော (stale) code ကို run နေခဲ့ခြင်း။

### Impact & Next
- Scenario အဆင့်အားလုံး (master data, purchase, sales, return, adjustment, collection, payment, expense, reconciliation) အတွက် **NOT VERIFIED** (QA store မရသေးသောကြောင့်)။
- **Recommended fix (High):** Store-create UI ၏ submit handler ကို စစ်ဆေးပါ — button disabled ဖြစ်ပြီး form POST မထွက်ခြင်း (double-submit/confirm JS interaction)။ နောက် live UAT run ရန်။

---

## Appendix — Key Files (for fixes)

| Area | Files |
|---|---|
| Roles/permissions | `app/Http/Middleware/EnsureStoreAccess.php`, `app/Models/User.php`, `routes/web.php` |
| Cashier role (new) | `database/migrations`, `UserManagementController`, routes POS gate |
| Stock Ledger filters | `app/Http/Controllers/Admin/StockLedgerController.php` (`resolveDateRange`) |
| Alpine collapse | `resources/views/admin/settings/sections/delivery.blade.php`, `resources/js/app.js`, `package.json` |
| Opening stock | `app/Http/Controllers/Admin/ProductController.php:704`, `app/POS/Services/OpeningStockService.php` |
| POS sale / return / debt / payable / P&L | `app/POS/Services/{PosSaleService,PosReturnService,CustomerDebtService,PurchaseOrderService,ProfitLossService,InventoryService}.php` |
| Tests | `tests/Feature/Admin/StockLedgerTest.php`, `tests/Feature/POS/*`, `tests/Feature/FrontendAssetIntegrityTest.php` |

---

*Report generated 2026-09-01 · Level: code + test (+route/middleware) · Browser UI UAT pending*