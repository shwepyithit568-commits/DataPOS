# 01 Historical Audits

> Consolidated edition. Each embedded source is preserved below with its original path and SHA-256 digest.


---

## Source 1: `archive/audits/AI_AGENT_SHOP_OWNER_MANAGER_AUDIT_REPORT_MM.md`

**SHA-256:** `06276714d79b54b5a22f3a065e55c224ce135b1917dfd2b8d6c696bff3c91b51`

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

---

## Source 2: `archive/audits/commercial_readiness_phase_a_audit_report.md`

**SHA-256:** `51a5e9fa3dbd0c8e9cd24c40e02d244ae72f70ba353287dd771d1c8e52a8d740`

# DataPOS — Myanmar Business Commercial Readiness Audit & Baseline Report (Phase A)

**Document Reference:** `docs/commercial_readiness_phase_a_audit_report.md`
**Master Plan:** docs/myanmar_business_commercial_readiness_plan_v1.md (`../../plans/myanmar_business_commercial_readiness_plan_v1.md`; see consolidated index)
**Audit Execution Date:** 2026-09-09
**Auditor:** Tech Buddy (Senior Software Architect & Pair Programmer)
**Status:** Completed — Ready for Project Owner Review

---

## 1. Executive Summary

ဤ Audit Report သည် DataPOS အား Windows Offline Installer/EXE အဖြစ် ထုတ်ပိုးမရောင်းချမီ မြန်မာနိုင်ငံရှိ Retail၊ Mobile/Electronics၊ CCTV/Networking၊ Repair/Service နှင့် Wholesale လုပ်ငန်းခွင်များတွင် စိတ်ချယုံကြည်စွာ အသုံးပြုနိုင်ရန် လိုအပ်သော နည်းပညာနှင့် စာရင်းအင်းစွမ်းဆောင်ရည်များကို အသေးစိတ် စစ်ဆေးမှတ်တမ်းတင်ထားခြင်း ဖြစ်ပါသည်။

### အဓိကတွေ့ရှိချက်များ (Key Findings)
1. **စနစ်၏ ခိုင်မာသော အခြေခံကောင်းများ (Strong Foundations):**
   - DataPOS တွင် Double-entry Inventory Ledger (`inventory_movements`)၊ Multi-currency formatting (`format_currency`, MMK default)၊ Cashier Shift Closing၊ Daily Closing၊ Service/Repair lifecycle၊ Offline Sync engine နှင့် PHPSpreadsheet အခြေခံ Excel Import/Export စနစ်များ အခိုင်အမာ တည်ရှိပြီးဖြစ်ပါသည်။
   - Automated Test Suite တွင် **Test ပေါင်း ၁,၇၀၃ ခု (Assertions ပေါင်း ၇,၆၈၁ ခု)** အားလုံး **100% PASS** နေပြီး Project baseline အလွန်ခိုင်မာပါသည်။
2. **ဖြေရှင်းရန် လိုအပ်သော P0/P1 Gap များ:**
   - **PDF Generation:** လက်ရှိတွင် client-side `html2pdf.js` အပေါ် အခြေပြုထားသဖြင့် Low-end Windows PC များတွင် Canvas rendering အားနည်းနိုင်ခြင်း (Server-side reliable PDF သို့ အဆင့်မြှင့်ရန် လိုအပ်)။
   - **Document Numbering:** ဘောက်ချာ/ပြေစာ နံပါတ်များအား Store-scoped Sequential Numbering (`INV-2026-00001`) ဖြင့် collision-free သတ်မှတ်ပေးရန် လိုအပ်ခြင်း။
   - **Period Lock & Variance Approval:** Daily close ပြီးသော ရက်စွဲများသို့ Backdate အရောင်း/အဝယ် မရိုက်နိုင်စေရန် ပိတ်ပင်ခြင်းနှင့် မန်နေဂျာ Approval စနစ်များ ထပ်မံဖြည့်တင်းရန် လိုအပ်ခြင်း။

---

## 2. Environment & Repository Baseline (Section 3.1)

| Parameter | Current Value | Verification Source |
| :--- | :--- | :--- |
| **Current Git Branch** | `main` (Up to date with origin/main) | `git status` |
| **Source of Truth Commit SHA** | `6473e3dc1a47a8fd8f54e85038f1cbf4135a980f` | `git rev-parse HEAD` |
| **Remote Origin URL** | `https://github.com/shwepyithit568-commits/DataPOS.git` | `git remote -v` |
| **Working Tree Status** | Clean (Zero uncommitted code changes) | `git status -s` |
| **Operating System** | Windows 11 (x64) | System Info |
| **PHP Runtime** | PHP 8.2.12 (cli, ZTS Visual C++ 2019 x64) | `php -v` |
| **Node.js / npm** | Node v26.0.0 / npm 11.12.1 | `node -v; npm -v` |
| **Database Engine** | SQLite (Default, portable, zero-server offline-first) | `config('database.default')` |
| **Application Timezone** | `Asia/Yangon` (UTC+06:30) | `config('app.timezone')` |
| **Default Locale** | `my` (Myanmar Unicode) | `config('app.locale')` |

### Baseline Automated Test Suite Output
```text
Tests:    1 skipped, 1703 passed (7681 assertions)
Duration: 138.81s
Status:   ALL SUITES GREEN
```

### Baseline Frontend Build Output
```text
✓ 60 modules transformed.
rendering chunks...
public/build/assets/app-B1F9vMRO.css        268.54 kB │ gzip: 31.93 kB
public/build/assets/admin-Q9tNuKf4.css      328.09 kB │ gzip: 38.72 kB
public/build/assets/app-D8CId_R8.js          14.65 kB │ gzip:  4.70 kB
public/build/assets/app-admin-DaNlZXq8.js    17.50 kB │ gzip:  5.86 kB
✓ built in 799ms
```

---

## 3. Current-State Inventory Matrix (Section 3.2)

Module တစ်ခုစီအား **Complete** (ပြည့်စုံ)၊ **Partial** (တစ်စိတ်တစ်ပိုင်းပြည့်စုံပြီး အဆင့်မြှင့်ရန်လို) နှင့် **Missing** (မရှိသေး) ဟူ၍ ခွဲခြားသတ်မှတ်ထားပါသည်-

| No | Core Business Module | Route / Entry Point | Controller & Service | Model / Storage | UI & Permissions | Automated Tests | Status |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| **1** | **POS & Sales** (Returns, Shift Close) | `store/{slug}/pos/*` | `PosSaleController`, `CashierShiftController`, `PosSaleService` | `PosSale`, `PosSaleItem`, `PosPayment`, `CashierShift` | POS Counter View, Cashier UI, `pos.access` | PosSaleTest, CashierShiftTest | **Partial** |
| **2** | **Products & Barcode** (Variants, Units) | `store/{slug}/admin/products/*` | `ProductController`, `BarcodeLabelController` | `Product`, `ProductVariant`, `BarcodeTemplate`, `ProductBatch` | Product CRUD, 3D Barcode Label Studio | ProductCrudTest, BarcodeTest | **Complete** |
| **3** | **Inventory & Stock** (Ledger, Count, Reconcile) | `store/{slug}/admin/stock-ledger/*`, `pos/reconciliation` | `StockLedgerController`, `StockCountController`, `ReconciliationService` | `InventoryMovement`, `InventoryBalance`, `StockCount` | Double-entry ledger view, Count sheets, Reconciliation UI | InventoryLedgerTest, ReconciliationTest | **Complete** |
| **4** | **Customers & Debt** (Receivables, Aging) | `store/{slug}/admin/customers/*`, `pos/customers/*` | `CustomerDirectoryController`, `CustomerDebtService`, `DebtAgingService` | `User` (customer), `CustomerLedgerEntry` | Debt Aging Table, Customer Statement View, POS Collect Modal | CustomerDebtTest, DebtAgingTest | **Partial** |
| **5** | **Suppliers & Purchasing** (PO, Payables, Return) | `store/{slug}/pos/purchases/*`, `admin/suppliers/*` | `PurchaseOrderController`, `PurchaseOrderService`, `GoodsReceiptService` | `PurchaseOrder`, `PurchaseOrderItem`, `GoodsReceipt`, `Supplier` | Purchasing Portal, GRN Inspection, Payables Aging | PurchaseOrderTest, SupplierTest | **Complete** |
| **6** | **Finance & Expenses** (Cash/Bank, P&L) | `store/{slug}/admin/expenses/*`, `admin/profit-loss` | `ExpenseController`, `ProfitLossController`, `FinancialTransactionService` | `Expense`, `FinancialAccount`, `FinancialTransaction`, `DailyClosing` | Multi-account ledger, P&L Analytics, Cash Drawer | ExpenseTest, ProfitLossTest | **Partial** |
| **7** | **Repair & Service** (IMEI, Jobs, Warranty) | `store/{slug}/admin/repairs/*`, `admin/service-jobs/*` | `RepairController`, `ServiceJobController`, `WarrantyTrackerService` | `ServiceJob`, `ServiceJobItem`, `DeviceWarranty`, `BuyBack` | Intake Wizard, Job Tracking, Spare Parts Allocator | ServiceJobTest, RepairWorkflowTest | **Complete** |
| **8** | **Ecommerce & Orders** (Storefront sync) | `store/{slug}/pos/web-orders`, `storefront/*` | `OrderController`, `OrderAdminController`, `PosSaleController` | `Order`, `OrderItem`, `StoreDeliveryMethod`, `StorePaymentMethod` | Web Order Intake, POS Counter Fulfillment | StorefrontBrowseTest, OrderTest | **Complete** |
| **9** | **Receipt & Printing** (58mm, 80mm, A4, A5) | `store/{slug}/pos/sales/{id}/receipt`, `admin/vouchers/*` | `PrinterController`, `VoucherCustomizerController`, `PrinterService` | `Printer`, `VoucherTemplate` | Live Voucher Studio, Thermal Slip, A4/A5 Print | PrinterTest, VoucherCustomizerTest | **Partial** |
| **10** | **Import / Export** (Excel, CSV) | `store/{slug}/admin/pilot-import/*`, `export` routes | `PilotImportController`, `ProductImportService`, `SpreadsheetImportReader` | `ImportHistory` | Dropzone uploader, XLSX/CSV template download | ProductImportTest, ExportTest | **Complete** |
| **11** | **PDF & Printing** (Myanmar Font Safety) | Receipt & Service Print views | Client `html2pdf.js`, `@media print` CSS | NotoSansMyanmar embedded | Print preview dialog, PDF download trigger | ServicePrintTest | **Partial** |
| **12** | **Backup & Restore** (Clean PC migration) | `store/{slug}/admin/backups/*` | `BackupController`, `DatabaseBackupService` | Local storage `storage/app/backups`, `DataMaintenanceLog` | Backup manager UI, Instant ZIP download/restore | DatabaseBackupTest | **Complete** |
| **13** | **Audit Logs** (Trail & Accountability) | `store/{slug}/admin/audit-logs/*` | `AuditLogController` | `AuditLog` | Audit Search, Filter, Old/New JSON Diff Modal | AuditLogTest | **Complete** |
| **14** | **Roles & Permissions** (Cashier, Manager, Owner) | `store/{slug}/admin/staff-roles/*` | `StaffRoleController`, `StorePermissionService` | `StaffRole`, `User` | Granular permission matrix, Multi-store switcher | StorePermissionTest, StaffRoleTest | **Complete** |

---

## 4. Known Existing Foundations to Preserve (Section 3.3)

အောက်ပါ အဆောက်အအုံများသည် စနစ်အတွင်း အလွန်သန့်ရှင်းစွာ အလုပ်လုပ်နေပြီးဖြစ်၍ ထပ်မံရေးသားခြင်းမပြုဘဲ မဖြစ်မနေ ထိန်းသိမ်းအသုံးပြုရမည်-
1. **Laravel 12 Framework Core:** Routing, Service Container, Eloquent ORM, Database Transactions.
2. **Asia/Yangon Timezone & MMK Currency Architecture:**
   - `format_currency($amount, $store)` နှင့် `window.formatCurrency(val)` (ဘယ်နေရာတွင်မှ Hardcoded "Ks" မသုံးဘဲ Dynamic format သုံးထားသည်)။
   - `format_quantity($qty, $store)` (စတော့အရေအတွက်များတွင် `.000` အပိုများ မပါစေဘဲ သန့်ရှင်းစွာ ပြသသည်)။
3. **Double-Entry Stock Ledger Architecture:**
   - `inventory_movements` table တွင် `sale`, `sale_return`, `purchase_receipt`, `purchase_return`, `adjustment_in`, `adjustment_out`, `transfer_in`, `transfer_out` ဟူ၍ balance track လုပ်ထားသည်။
4. **Offline Local Font Assets:**
   - `resources/assets/fonts/NotoSansMyanmar/` တွင် SIL Open Font License (OFL 1.1) ရရှိထားသော Noto Sans Myanmar font များ အသင့်ပါရှိပြီး အင်တာနက်မလိုဘဲ render လုပ်နိုင်သည်။
5. **Excel Integration:**
   - `phpoffice/phpspreadsheet` (^5.9) ဖြင့် Local PHP ပေါ်တွင် တိုက်ရိုက် XLSX ရေး/ဖတ် ပြုလုပ်နိုင်သဖြင့် ပြင်ပ cloud library များ လုံးဝမလိုအပ်ပါ။
6. **Triple-Level Closing Foundation:**
   - `CashierShift` (ကောင်တာအဆင့်) → `DailyClosing` (ဆိုင်ခွဲနေ့စဉ်အဆင့်) စနစ်များ ရှိပြီးဖြစ်ပါသည်။

---

## 5. Gap Analysis & Priority Action Items (Phase B, C, D Planning)

### P0 Priorities (လုပ်ငန်းသုံး မလွှဲမရှောင်သာ လိုအပ်ချက်များ)
1. **P0-1: Strict Period Lock & Backdate Prevention (Section 5.3)**
   - *Current State:* `DailyClosing` စနစ်ရှိသော်လည်း မန်နေဂျာ Approval ပြီးသွားသည့် နေ့စွဲများသို့ ကောင်တာမှ Backdate အရောင်း/အဝယ် ဝင်ရိုက်နိုင်သေးသည့် အပေါက်အပြဲရှိနိုင်သည်။
   - *Action:* Daily Closed ရက်စွဲများအတွက် `canModifyPeriod` validation rule ထည့်သွင်း၍ အရောင်း၊ အဝယ်၊ Stock Adjustment များကို အလိုအလျောက်ပိတ်ပင်ရန်။
2. **P0-2: Store-Scoped Sequential Document Numbering (Section 6.3)**
   - *Current State:* ဘောက်ချာနံပါတ်များတွင် database ID သို့မဟုတ် random prefix သုံးထားသည့် နေရာအချို့ရှိသည်။
   - *Action:* `INV-{YYYY}-{00001}`, `RET-{YYYY}-{00001}`, `PO-{YYYY}-{00001}` ပုံစံဖြင့် Store-scoped sequence generator ပြုလုပ်၍ နံပါတ်ခုန်ခြင်း၊ ထပ်ခြင်း လုံးဝမရှိစေရန် တည်ဆောက်ရန်။
3. **P0-3: Reliable Offline PDF Standardization (Section 7.1)**
   - *Current State:* `html2pdf.js` ကို client browser ပေါ်တွင် run ထားသဖြင့် စက်နိမ့်များတွင် တစ်ခါတစ်ရံ စာလုံးလွဲခြင်း၊ wrap မညီခြင်း ဖြစ်နိုင်သည်။
   - *Action:* Server-side local PHP-based PDF engine (ဥပမာ DomPDF with embedded Noto Sans Myanmar) သို့မဟုတ် browser print fallbacks အား တိကျသော standard သတ်မှတ်ရန်။
4. **P0-4: Closing Cash Drawer Variance Sign-Off (Section 5.2)**
   - *Current State:* Cashier Shift ပိတ်ချိန်တွင် Counted Cash နှင့် Expected Cash ကွာခြားချက်ရှိပါက အကြောင်းပြချက်နှင့် မန်နေဂျာ Sign-off မပါဘဲ ပိတ်ခွင့်ရနေနိုင်သည်။
   - *Action:* Variance ရှိပါက မဖြစ်မနေ အကြောင်းပြချက်ရိုက်ထည့်စေပြီး Manager Approval Flow ချိတ်ဆက်ရန်။

### P1 Priorities (နေ့စဉ်လုပ်ငန်း အဆင်ပြေချောမွေ့ရေး)
1. **P1-1: Customer & Supplier Statement Printing (Section 6.2)**
   - ကာစတန်မာနှင့် ကုန်သည်များအတွက် လချုပ်/ရက်ချုပ် စာရင်းရှင်းတမ်း (Statements) များကို 80mm နှင့် A4 ဖြင့် ရိုက်ထုတ်နိုင်သည့် view ထည့်သွင်းရန်။
2. **P1-2: Centralized Business Document Settings UI (Section 6.1)**
   - ဆိုင်လိပ်စာ၊ ဖုန်း၊ Viber/Telegram၊ Tax ID၊ ပြေစာအောက်ခြေစည်းကမ်းချက်များကို တစ်နေရာတည်းမှ စီမံနိုင်သော UI ပေါင်းစည်းရန်။

---

## 6. Audit Conclusion & Phase B Readiness

- **Current Repository Health:** **EXCELLENT (1,703 Tests Passed, Clean Git Tree)**
- **Audit Sign-off:** Phase A ၏ စစ်ဆေးချက်များအရ DataPOS ၏ နည်းပညာအခြေခံသည် ၈၀% ကျော် အဆင့်မြင့်မားစွာ ပြီးစီးနေပြီးဖြစ်ပါသည်။
- **Recommendation:** `docs/myanmar_business_commercial_readiness_plan_v1.md` ပါ မူဝါဒအတိုင်း **Phase B — P0 Integrity and Controls** (Period Lock, Sequential Document Numbering, Reconciliation Equations, Cash Drawer Variance) သို့ ဆက်လက်တက်လှမ်းနိုင်ရန် Boss (Project Owner) ၏ အတည်ပြုချက်ကို တောင်းခံအပ်ပါသည်။

---

## Source 3: `archive/audits/pre_installer_automated_baseline_report.md`

**SHA-256:** `1eebe7273e1446a6e4682d90cc52394722f9e9b43c6e01a17c85361b8b274d16`

# DataPOS — Pre-Installer Automated Baseline Report (Prompt 1)

**Report Generated Date:** 2026-09-09
**Target Repository:** `https://github.com/shwepyithit568-commits/DataPOS`
**Baseline Git Commit:** `d8f7abecfbe74fade6fe2ff06084fd609b0f8252`
**Current Branch:** `main`
**Auditor / Agent:** Antigravity (Prompt 1 — Release Candidate Baseline, Test Evidence & GitHub CI)

---

## 1. Environment & Runtime Inventory

| Component | Version / Status | Verification Command | Notes |
| :--- | :--- | :--- | :--- |
| **PHP Runtime** | `8.2.12 (cli)` (ZTS Visual C++ 2019 x64) | `php -v` | Pinned for local Windows runtime |
| **Composer** | `2.9.4` (2026-01-22) | `composer -V` | Schema & lockfile verified valid |
| **Node.js** | `v26.0.0` | `node -v` | Modern LTS/Active runtime |
| **npm** | `11.12.1` | `npm -v` | Lockfile compliant |
| **Laravel Framework** | `12.64.0` | `php artisan about` | Latest 12.x stable |
| **Database (Test)** | `SQLite (PDO SQLite)` | `php -r "..."` | Tests execute deterministic `:memory:` SQLite |
| **Database (Local)** | `SQLite / MySQL` | `config/database.php` | Dual driver capable |
| **Vite** | `7.3.6` | `npm run build` | Assets compile in < 1s |

### Verified PHP Extensions (`composer check-platform-reqs`):
`bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_sqlite`, `phar`, `session`, `simplexml`, `sqlite3`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `zlib`.

---

## 2. Baseline Verification Commands & Exact Results

### 2.1 Composer Package Validation
```text
$ composer validate --strict
./composer.json is valid
```
**Status:** `PASS`

### 2.2 Platform Requirements Check
```text
$ composer check-platform-reqs
Checking platform requirements for packages in the vendor dir:
All 24 extension/platform requirements verified successfully.
```
**Status:** `PASS`

### 2.3 Laravel Environment About
```text
$ php artisan about --only=environment
  Application Name .......................................... DataPOS
  Laravel Version ........................................... 12.64.0
  PHP Version ............................................... 8.2.12
  Composer Version .......................................... 2.9.4
  Environment ............................................... local
  Debug Mode ................................................ ENABLED
  URL ....................................................... 127.0.0.1:8502
  Maintenance Mode .......................................... OFF
  Timezone .................................................. Asia/Yangon
  Locale .................................................... my
```
**Status:** `PASS`

### 2.4 Frontend Package Installation & Production Build
```text
$ npm ci
added 114 packages, and audited 115 packages in 4s

$ npm run build
vite v7.3.6 building client environment for production...
transforming...
✓ 60 modules transformed.
rendering chunks...
computing gzip size...
public/build/manifest.json                                  1.67 kB │ gzip:  0.39 kB
public/build/assets/Outfit-Regular-DUdsL-5p.woff2          41.82 kB
public/build/assets/Roboto-Regular-B3YRHit7.woff2         103.23 kB
public/build/assets/NotoSansMyanmar-Regular-ymaFtaIS.ttf  183.16 kB
public/build/assets/NotoSansMyanmar-Bold-B2V9onp9.ttf     183.36 kB
public/build/assets/app-B1F9vMRO.css                      268.54 kB │ gzip: 31.93 kB
public/build/assets/admin-CL0oZvTo.css                    328.47 kB │ gzip: 38.76 kB
public/build/assets/app-D8CId_R8.js                        14.65 kB │ gzip:  4.70 kB
public/build/assets/app-admin-DaNlZXq8.js                  17.50 kB │ gzip:  5.86 kB
public/build/assets/module.esm-CvtIwgpG.js                 93.85 kB │ gzip: 34.29 kB
✓ built in 747ms
```
**Status:** `PASS`

### 2.5 Translation Parity Verification
```text
$ php artisan test --filter=LocalizationTest
Tests: 8 passed (24 assertions)
```
- Multi-lingual key parity across `my` (Myanmar), `en` (English), and `zh_CN` (Chinese) is strictly verified.
**Status:** `PASS`

### 2.6 Full Automated Test Suite Execution
```text
$ php artisan test
Tests:    1 skipped, 1743 passed (7966 assertions)
Duration: 146.37s
```
- **Total Tests:** 1,744
- **Passed:** 1,743
- **Failed:** 0
- **Skipped:** 1 (`Tests\Feature\MysqlMigrationSmokeTest` — intentional skip when local MySQL daemon is absent; runs on SQLite test memory)
- **Total Assertions:** 7,966
**Status:** `PASS`

---

## 3. Root Cause Investigation & Safe Regression Fix

### Flaky Test Identified:
- **File:** `tests/Feature/CatalogPerPageTest.php`
- **Symptom:** `test_default_per_page_is_40` / `test_invalid_per_page_falls_back_to_40` intermittently failed with `assertDontSee('PerPage Product 41')`.
- **Root Cause:**
  In `CatalogPerPageTest::makeStoreWithProducts()`, test products were populated using `Product::create([... 'created_at' => now()->subMinutes($count - $i)])`. However, `created_at` is not in Eloquent `$fillable`. Eloquent silently discarded the custom timestamps and stamped all 45 products with the identical current second. When `CatalogController` ordered products by `$query->latest()` (`created_at DESC`), SQLite's indeterminate row order caused `PerPage Product 41` to intermittently land in the first page slice.
- **Remediation Applied:**
  Updated `makeStoreWithProducts()` to use `$product->timestamps = false;` and `$product->forceFill([...])->save();` with explicit chronological offsets. Verified across multiple consecutive runs; passes 100% deterministically.

---

## 4. GitHub Actions CI Infrastructure

Created `.github/workflows/ci.yml` with the following automated pipeline:
1. **Trigger:** `push` and `pull_request` on `main`.
2. **Environment:** `ubuntu-latest`, PHP `8.2`, Node `20`.
3. **Extensions:** `mbstring`, `xml`, `ctype`, `iconv`, `intl`, `pdo`, `pdo_sqlite`, `sqlite3`, `bcmath`, `curl`, `gd`, `zip`, `fileinfo`.
4. **Composer Cache & Strict Validation:** `composer validate --strict` and lockfile caching.
5. **Node & Vite Build Verification:** `npm ci`, `npm run build`, and assertion of `public/build/manifest.json`.
6. **Isolated Test Harness:** In-memory SQLite (`:memory:`), `APP_ENV=testing`, dummy app key without real secrets.
7. **Translation Parity & Test Execution:** `LocalizationTest` and full suite `php artisan test`.

---

## 5. Automated Blockers Status

- **Automated Blockers:** `0` (Zero). All automated unit, feature, and translation tests are green.

---

## 6. Human Verification Pending List (Boundaries Check)

As mandated by project policy, the following items cannot be marked as PASS by automated tooling and remain explicitly pending human verification:

1. `PENDING — Human Verification`: Physical 58mm & 80mm thermal receipt printer output (alignment, Myanmar font rendering).
2. `PENDING — Human Verification`: Physical USB and Bluetooth barcode scanner hardware testing.
3. `PENDING — Human Verification`: Cash drawer RJ11/RJ12 electronic kick pulse on checkout.
4. `PENDING — Human Verification`: Clean Windows 10/11 standalone PC fresh installation.
5. `PENDING — Human Verification`: Database backup restoration on a clean secondary PC.
6. `PENDING — Human Verification`: Physical sudden power cut / crash recovery behavior on active cashier shifts.
7. `PENDING — Human Verification`: Seven-day real shop pilot operating with 0% external internet connectivity.
8. `PENDING — Human Verification`: Store Owner final commercial acceptance sign-off.

---

## Source 4: `archive/audits/README_AUDIT_NOTES.md`

**SHA-256:** `ed835366279b0aef328bb6cb963ac76bac359eff9a74a88c680e9bb5ba66ab45`

# DataPOS — README Audit Notes (Prompt 4)

**Audit Date:** 2026-09-09
**Target File:** `README.md`
**Purpose:** Record removed stale claims, corrected paths, updated commands, and architectural alignments during the full README rewrite.

---

## 1. Stale Claims Removed & Reasons

| Stale Claim in Old README | Correction / New Statement | Reason |
| :--- | :--- | :--- |
| **Absolute Local Paths:** `[...](D:/xmapp/htdocs/DataPOS/...)` used throughout links. | Replaced with repository-relative paths (`docs/...`, `Source_of_Truth_MM.md`, etc.). | Absolute Windows drive paths break navigation on GitHub, CI, and other developers' machines. |
| **Database Path:** Stated exclusively as `database/database.sqlite`. | Updated to canonical path `storage/database/datapos.sqlite` with automatic backward-compatible fallback to `database/database.sqlite`. | Prompt 3 canonicalization separated read-only binaries from writable data to prevent Windows permissions failures. |
| **Outdated "Recommended Next Phase":** Recommended building demo presets, one-click backup/restore, and POS workflows. | Removed and replaced with current Phase F / Pre-Installer status. | Demo preset switcher, one-click backup/restore, POS sale/return/reconciliation, and debt management were already built and verified in Phases A through E. |
| **Missing CI & Automated Baseline:** No mention of GitHub Actions CI or test suite assertion counts. | Added GitHub Actions CI workflow, exact test metrics (**1,743 passed, 1 skipped, 0 failed, 7,966 assertions**). | Baseline established in Prompt 1 must be transparently communicated to contributors and maintainers. |
| **Port Inconsistency:** Mentioned `8501` and `8502` without clear precedence. | Standardized on canonical port `8501` as primary, with `8502` as automatic fallback. | Clean configuration alignment across `.env.example`, documentation, and launcher design. |
| **Lack of Module Transparency:** Only summarized broad module categories. | Expanded into a comprehensive 22+ implemented module inventory alongside known deferred modules. | Prevents confusion regarding what is currently production-tested vs. future roadmap. |
| **Human vs. Automated Test Blur:** Did not explicitly define which hardware items require physical testing. | Added dedicated **Human Verification Pending List** (printers, scanners, cash drawers, clean PC restore, 7-day pilot). | Adherence to Strict Engineering Craftsmanship Policy in `AGENTS.md`. |
| **Missing Tri-lingual Standards:** Did not mention translation key parity for Myanmar, English, and Simplified Chinese. | Added Tri-Lingual Language Invariance policy from `AGENTS.md` v4.1. | Ensures future contributors maintain 3-language translation parity. |

---

## 2. Evidence Verification Summary

- **Composer Requirements:** PHP `^8.2`, Laravel `^12.0`, Livewire `^4.3`, PhpSpreadsheet `^5.9`, Webpush `^11.0`.
- **Node Packages:** Vite `^7.0.7`, TailwindCSS `^4.3.3`, Alpine.js `^3.15.12`, html2pdf.js `^0.14.0`.
- **Test Metrics:** Total 1,744 tests; 1,743 passed, 1 skipped (`MysqlMigrationSmokeTest` when MySQL absent), 7,966 assertions, duration ~146s.
- **Routes & Controllers:** All module names cross-referenced against active routes in `routes/web.php` and `bootstrap/app.php`.

---

## Source 5: `archive/audits/security_and_release_archive_audit.md`

**SHA-256:** `35a2c59bf840b8cc0c9a614a92640246aa6865651dfe174c5e3962eacea19da8`

# DataPOS — Security, Secrets and Public Repository Audit Report (Prompt 2)

**Audit Date:** 2026-09-09
**Repository:** `https://github.com/shwepyithit568-commits/DataPOS`
**Audited Git Commit:** `f3534be80605d2060e4586dac2dabe4a951e3d49` (Prompt 1 baseline HEAD)
**Branch:** `main`
**Auditor / Agent:** Antigravity (Prompt 2 — Security, Secrets & Public Repository Audit)

---

## 1. Executive Summary

A comprehensive read-heavy security, credential, secret exposure, and dependency audit was performed across the current repository and full Git history prior to building release archives and the Windows Offline Installer.

### Key Audit Highlights:
- **No Active Production Secrets in `.env`:** `.env` is not tracked in Git. Only `.env.example` with safe placeholder strings (`change_me`, `yourdomain.com`) exists in the repository.
- **No Private Keys / SSH Credentials:** Full Git history scan confirms zero tracked `.pem`, `.ppk`, `id_rsa`, or private SSH keys.
- **CRITICAL FINDING — Tracked SQL Dump Containing Real User Data:**
  The file `manual_2026-08-30_015910.mysql.sql` (3.6 MB) was added in commit `01618dc0d70e4004807f0c9e7fba7ef1620809f3` and is currently tracked in the repository. It contains real owner/staff/customer phone numbers, names, bcrypt password hashes, and active session tokens from August 2026.
- **Seeder Safety Guards Verified:** `DatabaseSeeder` contains zero automatic seed calls; `ProductionSeeder` seeds only standard blog content and expense categories; `UatSeeder` strictly halts if `APP_ENV=production|staging` or `ALLOW_UAT_SEEDING` is not `true`.
- **Dependency Audit:** `npm audit --omit=dev` reported **0 vulnerabilities**. `composer audit` identified 11 advisories across 2 packages (`league/commonmark` and `livewire/livewire`).
- **License Compliance:** All runtime dependencies use permissive open-source licenses (MIT, BSD-3-Clause, Apache 2.0, SIL OFL 1.1). Zero viral copyleft (GPL/AGPL) licenses exist in the application stack.

---

## 2. Sanitized Secret & Sensitive Exposure Findings Table

All raw credentials and secret tokens are strictly redacted below in compliance with Common Rules.

| ID | Category | File Path / Location | Commit | Description / Exposure | Severity | Status / Remediation |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **SEC-01** | **Customer / Staff Data** | `manual_2026-08-30_015910.mysql.sql` | `01618dc` | Contains `users` table with 9 real user records: Myanmar names, real phone numbers (`09784343151`, `09254343151`, etc.), email (`shwepyithit568@gmail.com`), real bcrypt hashes (`$2y$12$...`), and remember tokens. | **CRITICAL** | **Tracked in Git.** Needs Project Owner credential rotation & approval to untrack/scrub. |
| **SEC-02** | **Session Hijacking** | `manual_2026-08-30_015910.mysql.sql` | `01618dc` | Contains `sessions` table with 10 session tokens and base64 serialized session payloads with visitor IP addresses and user agents. | **HIGH** | Invalidate all sessions. Exclude dump from all installer/release packages. |
| **SEC-03** | **Database Schema / Dumps** | `AlinnThit Mobile Shop Notes data/*.sql` | Various | Contains `brands.sql`, `categories.sql`, `posts.sql` with legacy database name `u595335768_alinnthit_db`. No customer data or credentials. | **LOW** | Informational. Should be excluded from release distribution. |
| **SEC-04** | **Application Key** | `.env.example` / `docs/*` | All | `APP_KEY=base64:GENERATE_ON_SERVER_ONCE` used as dummy placeholder in docs. | **INFO** | Safe placeholder. No real key exposed. |
| **SEC-05** | **Database Credentials** | `.env.example` | Initial | Standard placeholder `DB_USERNAME=change_me`, `DB_PASSWORD=change_me`. | **INFO** | Safe placeholder. |

---

## 3. Git Tracking & `.gitignore` Audit

### 3.1 Already-Tracked Artifact Risk Analysis
Running `git ls-files` revealed that:
1. `manual_2026-08-30_015910.mysql.sql` is tracked by Git. Adding `*.sql` to `.gitignore` prevents future untracked files from being staged, but **Git will continue to track and version files that were already committed prior to the ignore rule**.
2. To remove it from Git tracking without deleting it from local disk, `git rm --cached manual_2026-08-30_015910.mysql.sql` is required.
3. Because the file exists in past commit history (`01618dc0d70e4004807f0c9e7fba7ef1620809f3`), complete removal from public Git history requires `git-filter-repo` or BFG Repo-Cleaner followed by a force-push, which requires explicit Project Owner approval.

### 3.2 `.gitignore` Enhancements Made
Updated `.gitignore` with the following defensive rules:
- Broad `.env.*` exclusion (with `!.env.example` whitelist) to prevent accidental staging of `.env.local`, `.env.staging`, or `.env.production`.
- `*.sql` wildcard to block accidental commits of database dumps.
- `/storage/database/*.sqlite*` to protect canonical SQLite databases created by the upcoming runtime architecture.

---

## 4. Required Credential Rotation Checklist for Project Owner

Due to the presence of `manual_2026-08-30_015910.mysql.sql` in the repository commit history:

- [ ] **Rotate Platform Owner Account Passwords:**
  - Change password for user associated with phone `09784343151` (`KoKoLInn`).
  - Change password for user associated with phone `09254343151` and email `shwepyithit568@gmail.com` (`Shwe pyi Thit`).
- [ ] **Invalidate Live Sessions:**
  - Flush all session cookies / session records on the live server (`php artisan session:clear` or truncate `sessions` table).
- [ ] **Rotate Hostinger Database Credentials (if applicable):**
  - Verify database `u595335768_alinnthit_db` on Hostinger hPanel and rotate the DB user password if it matched any password previously used.
- [ ] **Decision on Public Git History Scrubbing:**
  - Option A (Recommended if repository is public): Run `git-filter-repo --invert-paths --path manual_2026-08-30_015910.mysql.sql` and force-push to GitHub.
  - Option B: Make the repository Private if not already private.

---

## 5. Production & UAT Seeder Isolation Verification

| Seeder | Purpose | Contains Test Passwords / Demo Users | Production Safety Mechanism | Verification Status |
| :--- | :--- | :--- | :--- | :--- |
| `DatabaseSeeder.php` | Default artisan entry | **None** | Empty `run()` method with instructions; zero automatic calls. | **PASS (Safe)** |
| `ProductionSeeder.php` | Commercial first-run setup | **None** | Calls only `RefreshDataPOSBlogContentSeeder`, `ExpenseCategorySeeder`, `HowToOrderContentSeeder`. Zero user accounts or mock sales. | **PASS (Safe)** |
| `UatSeeder.php` | Full UAT local test harness | Contains test passwords (`'password'`), demo store accounts, mock vouchers. | `guardEnvironment()` aborts immediately if `APP_ENV` is `production` or `staging`, or if `ALLOW_UAT_SEEDING` is not `true`. | **PASS (Guarded)** |
| `MobileCatalogSqlSeeder.php` | Imports catalog products from dump | Reads `manual_2026-08-30_015910.mysql.sql` | **Extracts ONLY brands, categories, products, images, and variants.** Does NOT import users or sessions. | **PASS (Limited Scope)** |

Automated regression coverage in `tests/Feature/UatSeederSafetyTest.php` confirms that executing `UatSeeder` in production or staging throws an exception and halts immediately.

---

## 6. Dependency Security Audit Results

### 6.1 NPM Audit (`npm audit --omit=dev`)
```text
found 0 vulnerabilities
```
**Status:** `PASS` (Clean production frontend dependencies).

### 6.2 Composer Audit (`composer audit`)
Found 11 advisories across 2 packages:

1. **`league/commonmark` (10 advisories):**
   - **Advisories:** GHSA-8rr7-cvq3-gmfh, GHSA-jjv6-8j6v-6j52, GHSA-f8fg-pg57-v4j8, GHSA-j8pm-gj4c-rq4x, GHSA-mj63-m3rc-8ppr, GHSA-mh25-x5hq-wrqp, GHSA-jfm3-95jq-q3rf, GHSA-g2gp-3wwq-f4ph, GHSA-2q4p-g7hv-5rgv (CVE-2026-71488), GHSA-29pj-957v-52mc (CVE-2026-71478).
   - **Severity:** High / Medium (Denial of Service & XSS via AttributesExtension in crafted Markdown parsing).
   - **Context in DataPOS:** `league/commonmark` is pulled in as an indirect dependency of `laravel/framework`. DataPOS does not expose untrusted raw public user input to Markdown rendering without HTML sanitization (`CustomPageRenderingTest` verifies strip-tags sanitization).
   - **Remediation Plan:** Update via `composer update league/commonmark` once a compatible bump is tested across the suite; do not bump without compatibility verification.

2. **`livewire/livewire` (1 advisory):**
   - **Advisory:** GHSA-g3hc-697w-wm82 (CVE-2026-81887).
   - **Severity:** Medium (DOM-based XSS during client-side state handling in Livewire 4.x <= 4.3.3).
   - **Context in DataPOS:** Used for internal admin/POS UI interactions.
   - **Remediation Plan:** Monitor Livewire 4.x upstream releases for patch >= 4.3.4 and apply in routine maintenance.

---

## 7. Third-Party License Inventory Manifest

| Component | Upstream Author / Project | Version / Source | License Type | Redistribution & Commercial Rights |
| :--- | :--- | :--- | :--- | :--- |
| **PHP Runtime** | The PHP Group | 8.2.x Windows x64 binary | PHP License v3.01 | Permissive; commercial redistribution allowed with attribution. |
| **Laravel Framework** | Taylor Otwell | 12.64.0 | MIT License | Permissive; commercial redistribution & modification allowed. |
| **PhpSpreadsheet** | PHPOffice | 5.9.x | MIT License | Permissive; commercial use allowed. |
| **Livewire** | Caleb Porzio | 4.3.x | MIT License | Permissive; commercial use allowed. |
| **html2pdf.js** | Erik Koopmans | 0.14.0 | MIT License | Permissive; commercial use allowed. |
| **Alpine.js** | Caleb Porzio | 3.15.12 | MIT License | Permissive; commercial use allowed. |
| **TailwindCSS** | Tailwind Labs | 4.3.3 | MIT License | Permissive; commercial use allowed. |
| **Noto Sans Myanmar Font** | Google Fonts | OpenType TTF | SIL Open Font License 1.1 | Permissive; can be freely bundled with application software. |
| **Outfit Font** | Onsen Studio | WOFF2 | SIL Open Font License 1.1 | Permissive; can be bundled. |
| **Roboto Font** | Google | WOFF2 | Apache License 2.0 | Permissive; can be bundled with copyright notice. |
| **Inno Setup (Installer)** | Jordan Russell | Inno Setup 6.x | Inno Setup License (Modified BSD) | Free of charge for commercial use; no royalty fees. |

**License Assessment:** `100% COMPLIANT`. All components are compatible with commercial closed-source distribution. No viral copyleft obligations.

---

## 8. Installer & Source Release Archive Inclusion-Exclusion Matrix

To ensure zero leakage of credentials, test data, or bloated development dependencies, release builds must adhere to the following strict matrix:

| Artifact / Path | Release Source ZIP | Windows Offline Installer | Reason / Policy |
| :--- | :---: | :---: | :--- |
| **Source Code (`app/`, `bootstrap/`, `config/`, `routes/`, `resources/`)** | **INCLUDE** | **INCLUDE** | Core application functionality |
| **Compiled Frontend (`public/build/`)** | **INCLUDE** | **INCLUDE** | Production CSS/JS assets and web fonts |
| **Production Vendor (`vendor/` optimized, no-dev)** | **EXCLUDE** (lockfile only) | **INCLUDE** (pre-installed) | Offline runtime requires pre-bundled vendor |
| **PHP Windows x64 Runtime (`php/`)** | **EXCLUDE** | **INCLUDE** (pinned zip/dir) | Offline installer runtime dependency |
| **Real `.env`** | **EXCLUDE (BLOCK)** | **EXCLUDE (BLOCK)** | Must be generated dynamically on first installation |
| **`.env.example`** | **INCLUDE** | **INCLUDE** | Template for dynamic environment initialization |
| **Database Dumps (`*.sql`, `manual_*.sql`)** | **EXCLUDE (BLOCK)** | **EXCLUDE (BLOCK)** | Sensitive customer data / obsolete dumps |
| **Live SQLite DBs (`*.sqlite`, `database.sqlite`)** | **EXCLUDE (BLOCK)** | **EXCLUDE (BLOCK)** | Fresh DB must be migrated on first install |
| **Node Modules (`node_modules/`)** | **EXCLUDE** | **EXCLUDE** | Not needed at runtime (Vite assets pre-compiled) |
| **Git Metadata (`.git/`, `.github/`)** | **EXCLUDE** | **EXCLUDE** | Repository internals not needed for end-user runtime |
| **Development Cache / IDE (`.vscode`, `.idea`, etc.)** | **EXCLUDE** | **EXCLUDE** | Tooling noise |
| **Backups (`storage/app/uat-backups/`, `*.zip`)** | **EXCLUDE (BLOCK)** | **EXCLUDE (BLOCK)** | Sensitive backup data |
| **Local Excel Spreadsheets (`*.xlsx`)** | **EXCLUDE** | **EXCLUDE** | Local developer working sheets |
| **Storage Logs (`storage/logs/*.log`)** | **EXCLUDE** | **EXCLUDE** | Machine-specific log traces |
| **UAT Seeders (`UatSeeder.php`, etc.)** | **INCLUDE** (code only) | **INCLUDE** (disabled by env) | Sealed by `guardEnvironment()` |
| **Inno Setup Script (`installer.iss`)** | **INCLUDE** | **BUILD RUNNER** | Used to compile the setup executable |

---

## Source 6: `archive/audits/UAT-20260908-0733/00-run-manifest.md`

**SHA-256:** `8f816cb1c15b5e7d516809fb97e2869136b254704600fe38671782c4ebb963e7`

# UAT Run Manifest — UAT-20260908-0733

## Environment

| Item | Value |
|---|---|
| Test run ID | UAT-20260908-0733 (defined by `UatCoordinatorSetupSeeder::RUN_ID`) |
| Test date/time | 2026-09-08, execution started 17:45 (Asia/Yangon, UTC+6:30) |
| Git commit SHA | `3566f06c6958ad03028a1a17ed17a8bf0058eae5` |
| Environment URL | http://127.0.0.1:8502 (local staging-equivalent, `APP_ENV=local`) |
| Database | SQLite `database/database.sqlite` (local, single-tenant dev DB — no production data present) |
| PHP version | 8.2.12 (XAMPP) |
| Node version | v26.0.0 |
| Browser | Chromium (Freebuff preview harness) |
| Seeder | `php artisan migrate:fresh --seed --seeder=UatCoordinatorSetupSeeder --force` |
| DB backup (rollback point) | `database/backup-pre-uat-20260908.sqlite` (1,789,952 bytes, taken 17:43 before reset) |

## Safety verification

- [x] Environment is local staging-equivalent; no real customer data (all accounts are UAT-prefixed seed data).
- [x] Recoverable DB snapshot taken before testing (backup file above).
- [x] `ALLOW_UAT_SEEDING=true` + `APP_ENV=local` — seeder safety guard satisfied.
- [x] No pre-existing records deleted; database reset performed with explicit user approval (backup + fresh re-seed option).
- [x] All created records use run-ID/UAT prefixes.

## Baseline automated test run (`php artisan test`)

| Metric | Value |
|---|---|
| Passed | 1,703 |
| Failed | 0 |
| Skipped | 1 |
| Assertions | 7,679 |
| Duration | 141.57s |

Baseline build: `npm run build` — SUCCESS (Vite build completed, assets emitted under `public/build/`).

## Application smoke test

- [x] Login page renders (HTTP 200) at `/login`.
- [x] Real password login as Platform Owner (`09100000001` / `password`) → redirected to `/admin/dashboard`.
- [x] Browser console: 0 errors on login + dashboard load.
- [x] Network: all requests 200 (fonts, CSS, JS).

## Test stores

| Store | Slug | Purpose |
|---|---|---|
| Store A | `uat-pos-store` | Main workflow testing (POS, purchases, ecommerce, repair, finance) |
| Store B | `uat-isolation-store` | Cross-store isolation testing (0 products, empty) |

## Test users (all passwords `password`, POS PIN `1234`)

| Role | Phone | Notes |
|---|---|---|
| Platform Owner | 09100000001 | Platform scope |
| Store Owner (A) | 09800000001 | Full store admin |
| Store Manager (A) | 09800000002 | Stock/sales/daily ops |
| Cashier (A) | 09800000003 | POS counter |
| Inventory Staff (A) | 09800000004 | Stock keeper |
| Accountant (A) | 09800000005 | Finance/reports |
| Technician (A) | 09800000006 | Repair/service |
| Ecommerce Staff (A) | 09800000007 | Online orders |
| Restricted Custom (A) | 09800000008 | Catalog view only |
| UAT Customer | 09822222222 | Retail customer (storefront) |

## Shared test dataset (opening state — verified in DB after fresh seed)

| Record | Value | Verified |
|---|---|---|
| Product A | SKU `UAT-PHONE-001`, cost MMK 300,000, price MMK 350,000, ledger qty 10 | ledgerQty=10 |
| Product B | SKU `UAT-CASE-001`, cost MMK 10,000, price MMK 15,000, ledger qty 20 | ledgerQty=20 |
| Supplier | `UAT Supplier` | created |
| Customer | `UAT Customer` | created |
| Opening cashier cash | MMK 100,000 (Register 1, open shift) | 1 open shift |
| POS sales / online orders | 0 / 0 | confirmed |

## Pre-test findings (Phase 0)

| # | Severity | Finding |
|---|---|---|
| F-0.1 | Low | Raw translation key `messages.all_stores` leaks on Platform Owner dashboard store selector (visible as literal text + `MESSAGES.ALL_STORES` sub-label). Localization defect, cosmetic. |

---

## Source 7: `archive/audits/UAT-20260908-0733/handoff-ledger.md`

**SHA-256:** `c78fd4176505087ce94132c8464c367d2f3d9700a6726940e30793e219ea94f1`

# Shared Handoff Ledger — UAT-20260908-0733

Baseline (after fresh seed, before any agent actions):

| Event | Product A Qty | Product B Qty | Cash | Digital Payment | Receivable | Payable |
|---|---:|---:|---:|---:|---:|---:|
| SEED (opening) | 10 | 20 | 100,000 (drawer) | 0 | 0 | 0 |

<!-- Each agent appends: before state, action, expected, actual, IDs, refs, timestamps -->
