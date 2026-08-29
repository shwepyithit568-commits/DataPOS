# DataPOS — Production Readiness & Quality Assurance Master Checklist
**Document Version:** 2.0.0 — Agent-Verifiable Edition  
**Target:** Production Launch & Commercial Deployment Readiness for Myanmar SME Market  
**System Stack:** Laravel 12 + Alpine.js + Vanilla CSS + ESC/POS Thermal Printing  
**Architecture Rule:** Single Codebase | Multi-Store Tenant Isolated | No Livewire | No jQuery  
**Author:** Boss + Tech Buddy  
**Reference:** [DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md](file:///d:/xmapp/htdocs/DataPOS/docs/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md)  
**Date:** August 2026

> **AI Agent အတွက် မှတ်ချက်:** ဒီ Checklist ကို စစ်ဆေးသောအခါ `[x]` သည် "UI ရှိသည်" သက်သက်ဖြင့် mark မလုပ်ရ။ "End-to-end browser test ပြုလုပ်ပြီး စစ်မှန်သော Cashier workflow တွင် အလုပ်လုပ်ကြောင်း verify ဖြစ်ပြီး" မှသာ `[x]` ဟု မှတ်တမ်းတင်ရမည်။

---

## 📋 မာတိကာ (Table of Contents)

1. [စစ်မှန်သော "Done" ဆိုသည်မှာ](#definition-of-done)
2. [Section A — Codebase Cleanup Audit](#section-a--codebase-cleanup-audit)
3. [Section B — Standard Verification Criteria (Per-Page Gates)](#section-b--standard-verification-criteria-per-page-gates)
4. [Section C — Myanmar SME Real-World Edge Cases](#section-c--myanmar-sme-real-world-edge-cases)
5. [Section D — POS Counter Experience (Cashier UX)](#section-d--pos-counter-experience-cashier-ux)
6. [Section E — Hardware Integration (Printer, Scanner, Drawer)](#section-e--hardware-integration-printer-scanner-drawer)
7. [Section F — Module-by-Module Production Audit](#section-f--module-by-module-production-audit)
8. [Section G — Security & Data Integrity Gates](#section-g--security--data-integrity-gates)
9. [Section H — Performance & Low-End Device Testing](#section-h--performance--low-end-device-testing)
10. [Section I — Localization & Typography](#section-i--localization--typography)
11. [Section J — Pilot Store Validation (Must Pass Before Sales)](#section-j--pilot-store-validation-must-pass-before-sales)
12. [Final Sign-Off Checklist](#final-sign-off-checklist)

---

## Definition of Done

Feature/Module တစ်ခုကို **Done** ဟု မှတ်တမ်းတင်ရန် အောက်ပါ **ခြောက်ချက်** လုံးပြည့်ရမည် —

| # | Gate | Verification Method |
|---|---|---|
| 1 | **Database & Schema** — Migration, FK, Index ပါဝင်ပြီး | `php artisan migrate:status` — no pending migrations |
| 2 | **Service Logic** — bcmath MMK, Ledger integrity, Server-side auth | Feature tests pass with `php artisan test --filter` |
| 3 | **Admin UI** — CRUD, Validation errors in Myanmar, Responsive | Browser test: create/edit/delete + check mobile 375px |
| 4 | **POS Counter Experience** — Cashier flow live test | Manual: Cashier role → full sale → receipt printed |
| 5 | **Hardware** — Printer/Scanner tested on real device | Physical test: 58mm/80mm receipt cut clean, scan = product found |
| 6 | **Real-World Flow** — Daily opening → sales → closing drill | Manual: open float → make 5 sales → close shift → balance matches |

---

## Section A — Codebase Cleanup Audit

> **Verification:** Run the commands listed. If output is clean/empty, mark `[x]`. If output shows files, fix first.

### A.1 Temporary & Dead Files
- [x] `headers.txt` — removed from root directory
- [x] `.freebuff-preview.log` — removed from root directory
- [x] `scripts/lh-cookie.php` — removed (scratch Lighthouse cookie script)
- [x] `storage/logs/laravel.log` — cleared for clean baseline; `.gitignore` covers it

**Verification command:**
```bash
ls -la headers.txt .freebuff-preview.log 2>&1 | grep "No such file"
```

### A.2 Git Repository Cleanliness
- [ ] **No secrets/API keys in committed files**
  - Verification: `git log --all -p | grep -E "(secret|password|api_key|APP_KEY)" | grep -v ".env.example"`
- [ ] **No `dd()`, `dump()`, `var_dump()`, `ray()` debug calls in production code**
  - Verification: `grep -rn "dd(\|dump(\|var_dump(\|ray(" app/ resources/ --include="*.php" --include="*.blade.php"`
- [ ] **No `console.log()` in production JavaScript**
  - Verification: `grep -rn "console.log" resources/js/ resources/views/ --include="*.js" --include="*.blade.php"`
- [x] `.phpunit.result.cache` in `.gitignore`
- [ ] **No `TODO:` or `FIXME:` blocking production code**
  - Verification: `grep -rn "TODO:\|FIXME:\|HACK:\|XXX:" app/ --include="*.php"` — must be 0 blocking items

### A.3 Configuration Safety
- [ ] `.env.production` template created and `.env` variables documented
- [ ] `APP_DEBUG=false` for production deploy
- [ ] `APP_ENV=production` for production deploy
- [x] Sensitive files in `.gitignore` (`.env`, `storage/`, `vendor/`)

### A.4 Migration Safety
- [ ] All migrations run cleanly on fresh database: `php artisan migrate:fresh --seed`
- [ ] No migration rollback errors: `php artisan migrate:rollback --step=5`

---

## Section B — Standard Verification Criteria (Per-Page Gates)

> **Every module in Section F must pass ALL of these criteria.** AI Agent: စစ်ဆေးသောအခါ module တစ်ခုချင်းစီတွင် ဒီ criteria ၇ ချက်ကို apply ပြုလုပ်ရမည်။

### B.1 🎨 UI/UX Layout Integrity
- [ ] ကုန်ပစ္စည်းစာရင်း 0 ပစ္စည်း (Empty State) — Help text နှင့် action button ပါသော Empty State ပြသပေးခြင်း (not blank page)
- [ ] Column overflow / horizontal scroll — ≤ 1440px အကျဉ်းကျပ်ပါက table ကို overflow-x:auto ဖြင့် handle
- [ ] Mobile 375px (Galaxy A series) — Sidebar collapse, table horizontal scroll, form stack vertically ဖြစ်ခြင်း
- [ ] Tablet 768px (POS Tablet mode) — Main content area ≥ 600px ဖြစ်ခြင်း
- [ ] Dark Mode / Light Mode toggle — text, icon, badge များ contrast ≥ 4.5:1 (WCAG AA)
- [ ] Page title `<h1>` — Page တစ်ခုစီတွင် တစ်ခုသာ ရှိရမည်
- [ ] Loading state — List/table ဖွင့်နေချိန် skeleton/spinner ပြသပေးခြင်း

### B.2 🇲🇲 Myanmar Unicode Typography
- [ ] `Padauk` / `Pyidaungsu` font ပါဝင်ခြင်း — `font-family` CSS စစ်ဆေးခြင်း
- [ ] `ဝ၇` vs `07` digit — Myanmar numeral ရောထွေးမှု မရှိခြင်း (MMK ကို `07,500` မဟုတ်ဘဲ `7,500` ဖြင့် ပြသ)
- [ ] `_ာ_ား_ိ_ီ_ု_ူ_ေ_ဲ_ံ_် ်` vowel spacing — browser render ကျိုးကျဲမှု မရှိခြင်း
- [ ] Error messages Myanmar language — `"ပစ္စည်းမတွေ့ပါ"` ကဲ့သို့ user-friendly ဖြင့် ပြသခြင်း (not raw PHP errors)
- [ ] MMK currency format — `7,500 ကျပ်` (comma separator, "ကျပ်" suffix, no decimal for MMK)

### B.3 ⚡ Alpine.js & Interactive Behavior
- [ ] Modal open/close — ESC key ဖြင့် ပိတ်နိုင်ခြင်း၊ backdrop click ဖြင့် ပိတ်နိုင်ခြင်း
- [ ] Form submit double-click prevention — Submit button ကို ပထမ click နှင့် disable ဖြစ်ပြီး spinner ပြသပေးခြင်း
- [ ] Search/filter — Debounce ≥ 300ms ဖြင့် input တိုင်း server request မပို့ဘဲ pause ပြီးမှ ပို့ခြင်း
- [ ] Toast/Flash message — Save/Delete အောင်မြင်ပါက `"✅ သိမ်းဆည်းပြီးပါပြီ"` toast 3s ပြသပြီး ကွယ်သွားခြင်း
- [ ] Delete confirmation — "✅ ဤပစ္စည်းကို ဖျက်မည်မှာ သေချာပါသလား?" modal ပါဝင်ခြင်း (not browser `confirm()`)

### B.4 🛡️ Security & Validation
- [ ] CSRF Token — POST/PUT/DELETE form အားလုံးတွင် `@csrf` ပါဝင်ခြင်း
- [ ] Server-side validation — Client validation bypass လုပ်ပြီး invalid data ပေးပို့ပါကလည်း `422 Unprocessable` ဖြင့် reject ဖြစ်ခြင်း
- [ ] Authorization Policy — Manager-only routes ကို Cashier role ဖြင့် URL တိုက်ရိုက် ဝင်ပါက `403 Forbidden` ဖြစ်ခြင်း
- [ ] Store Isolation — Store A ၏ record ကို Store B ၏ user ဖြင့် access ပြုလုပ်ပါက `404/403` ဖြစ်ခြင်း
- [ ] XSS Protection — User input ကို blade `{{ $var }}` ဖြင့် escaped ပြသခြင်း (`{!! $var !!}` မသုံးရ data ပြသရာတွင်)

### B.5 🗄️ Database & Query Performance
- [ ] N+1 Query — List page တွင် `with()` eager loading ပါဝင်ပြီး Debugbar/Telescope ဖြင့် query count စစ်ဆေးခြင်း
- [ ] Pagination — Item 100+ ရှိသော list pages တွင် server-side pagination (`paginate(25)`) ပါဝင်ခြင်း
- [ ] Index Coverage — FK columns, `store_id`, `created_at` filter columns တွင် index ပါဝင်ခြင်း
- [ ] Money Precision — `decimal(15,2)` column type, bcmath ဖြင့် calculation (float ကို မသုံးရ)

### B.6 🖨️ Print Compliance
- [ ] Voucher/Receipt print preview — `@media print` CSS ဖြင့် header/footer ရှင်းပြီး content only ပြသခြင်း
- [ ] Thermal 58mm layout — Content width ≤ 384px, Myanmar font ပါဝင်ခြင်း
- [ ] Thermal 80mm layout — Content width ≤ 576px, Myanmar font ပါဝင်ခြင်း
- [ ] Auto-cut command — ESC/POS `GS V B n` cut command ပါဝင်ခြင်း

### B.7 ♿ Accessibility & UX
- [ ] Form label ↔ input association — `<label for="id">` တိုင်း matching `id` ပါဝင်ခြင်း
- [ ] Focus trap in Modal — Modal ဖွင့်ထားချိန် Tab key ဖြင့် modal ထဲတွင်သာ focus ရောက်ခြင်း
- [ ] Error state persistence — Form validation fail ဖြစ်ပါက user ရိုက်ထည့်ထားသော value ကို preserve ထားပေးခြင်း
- [ ] Action feedback latency — Button click မှ visual response ≤ 200ms ဖြစ်ခြင်း

---

## Section C — Myanmar SME Real-World Edge Cases

> **AI Agent:** ဒီ Edge Cases တွေကို specifically စစ်ဆေးရမည်။ Myanmar ဈေးကွက်တွင် အဖြစ်အများဆုံး ပြဿနာများဖြစ်သည်။

### C.1 Currency & Money Edge Cases
- [ ] **MMK Comma Formatting:** `1000000` → display `1,000,000 ကျပ်` (not `1000000.00`)
- [ ] **Zero Decimal MMK:** Receipt တွင် `5,000 ကျပ်` (not `5,000.00 ကျပ်`)
- [ ] **Change Calculation:** Customer `10,000 ကျပ်` ပေး, Total `7,500 ကျပ်` → Change `2,500 ကျပ်` ဖြင့် ထပ်ဆင့် confirm
- [ ] **Debt with Partial Payment:** Customer `50,000 ကျပ်` ကြွေးပေး `20,000` → Remaining debt `30,000` ဖြင့် ledger update
- [ ] **Bcmath Precision Test:** `0.1 + 0.2` PHP float bug → bcmath ဖြင့် `0.3` ဖြစ်ကြောင်း verify: `php -r "echo bcadd('0.1','0.2',2);"`

### C.2 Network & Connectivity Edge Cases
- [ ] **Slow Network (2G simulation):** Chrome DevTools → Network → Slow 3G ပြောင်းပြီး POS page load ≤ 8s ဖြစ်ကြောင်း test
- [ ] **Form Submit on Slow Network:** Submit ချိန် network ကျသွားပါက double-submit မဖြစ်ဘဲ error message ပြသပေးခြင်း
- [ ] **Session Timeout:** 8 နာရီကြာ active မဟုတ်သော session → POS counter မှ graceful redirect to login (not 500 error)

### C.3 Myanmar Calendar & Date
- [ ] **Date Format:** `dd/mm/yyyy` (Myanmar preference) — `08/29/2026` မဟုတ်ဘဲ `29/08/2026` ပြသ
- [ ] **Receipt Date/Time:** မြန်မာ Standard Time (UTC+6:30) ဖြင့် ပြသပေးခြင်း — Timezone config စစ်ဆေးရမည်: `config/app.php` → `'timezone' => 'Asia/Rangoon'`

### C.4 Product Data Edge Cases
- [ ] **Long Product Name:** 200+ character product name → UI overflow မဖြစ်ဘဲ `text-ellipsis` ဖြင့် truncate
- [ ] **Zero Stock Sale Attempt:** Out of stock ပစ္စည်းကို POS cart ထဲ ထည့်ပါက server-side block ဖြစ်ပြီး `"❌ စတော့ မလုံလောက်ပါ"` error ပြသပေးခြင်း
- [ ] **Duplicate Barcode Scan:** တစ်ဆက်တည်း barcode နှစ်ကြိမ် scan ပြုလုပ်ပါက qty=1 → qty=2 ဖြင့် increment (duplicate item မဟုတ်ဘဲ)
- [ ] **Negative Price Guard:** Cost Price / Sale Price ကို 0 သို့မဟုတ် negative ထည့်ပါက server validation ဖြင့် reject

### C.5 Multi-User Concurrent Scenarios
- [ ] **Two Cashiers Same Product:** Cashier A နှင့် B သည် တစ်ပြိုင်နက် qty=1 last item ကို ရောင်းပါက stock integrity ကျပ်မတ်ပြီး pessimistic lock သို့မဟုတ် transaction ဖြင့် handle ဖြစ်ခြင်း
- [ ] **Manager Edit + Cashier Sale:** Manager က product price ပြောင်းနေချိန် Cashier ၏ active POS session ပြောင်းလဲသွားသော price ဖြင့် `[x]` ဒေတာ inconsistency မဖြစ်ကြောင်း test

---

## Section D — POS Counter Experience (Cashier UX)

> **Goal:** Cashier (non-technical Myanmar user) တစ်ဦး training မရှိဘဲ ၅ မိနစ်အတွင်း first sale ပြုလုပ်နိုင်ရမည်။

### D.1 Product Search & Cart
- [ ] **Barcode Scan Speed:** Scanner ဖြင့် scan → product found & added to cart ≤ 500ms
- [ ] **Keyboard SKU Lookup:** SKU/barcode ကို keyboard ဖြင့် ရိုက်ထည့်ပြီး Enter → cart ထဲ ဝင်ခြင်း
- [ ] **Product Name Search:** ဆိုင်ရှိ ပစ္စည်းအမည် ၃ လုံးရိုက်ထည့်ပါက relevant results ≤ 1s ပေါ်လာခြင်း
- [ ] **Qty +/- Controls:** Cart item qty ကို `+` `-` ခလုတ် (မြင်ရသာ ကြီးသော touch-friendly) ဖြင့် ပြောင်းနိုင်ခြင်း
- [ ] **Quick Remove:** Cart item ကို swipe/X button ဖြင့် ဖယ်ရှားနိုင်ခြင်း
- [ ] **Discount Per Item:** Item တစ်ခုစီတွင် `%` သို့မဟုတ် `ကျပ်` discount ထည့်နိုင်ခြင်း
- [ ] **Hold & Recall:** ၃ active order ကို hold ထားပြီး မည်သည့် order မဆို recall ပြန်ခေါ်နိုင်ခြင်း

### D.2 Payment Processing
- [ ] **Multi-Payment Split:** Cash `5,000` + KPay `2,500` = Total `7,500` → Payment breakdown မှန်ကန်ခြင်း
- [ ] **Cash Change Display:** Cash payment ထည့်သည်နှင့် Change ကြီးကြီးမားမားဖြင့် ချက်ချင်း ပြသပေးခြင်း
- [ ] **Customer Debt Credit:** Existing customer ၏ credit balance မှ sale deduct ပြုလုပ်နိုင်ခြင်း
- [ ] **Zero-Total Sale Guard:** Total `0 ကျပ်` ဖြင့် sale complete ပြုလုပ်ပါက manager confirmation ဓမ္မတာ တောင်းခံခြင်း

### D.3 Receipt & Post-Sale
- [ ] **Auto Print on Completion:** Sale ပြီးသောအခါ receipt print dialog ချက်ချင်းပွင့်ခြင်း (manual click မလိုဘဲ)
- [ ] **Reprint Receipt:** ရောင်းပြီးသား invoice ကို Reprint ပြုလုပ်နိုင်ခြင်း
- [ ] **Receipt Content Accuracy:** Receipt တွင် — ဆိုင်အမည်, တယ်လီဖုန်း, Invoice No, Date/Time (Myanmar TZ), Item List, Subtotal, Discount, Tax, Total, Payment Method, Change, Cashier Name ပါဝင်ခြင်း
- [ ] **New Sale Speed:** Sale complete → new empty cart ready ≤ 2s (POS responsiveness)

### D.4 Daily Opening & Closing
- [ ] **Opening Float Entry:** Shift စတင်ချိန် `Opening Cash (အဖွင့်ငွေ)` ထည့်သွင်းကာ Cashier PIN ဖြင့် confirm ပြုလုပ်ခြင်း
- [ ] **Closing Reconciliation:** Expected Cash (Counted) vs Actual Drawer → Discrepancy ကြီးပါက manager approval ဓမ္မတာ တောင်းခံခြင်း
- [ ] **Closing Slip Print:** Daily Closing Summary (Total Sales, Cash/KPay/Wave breakdown, Drawer Balance) Thermal Print ထုတ်ပေးခြင်း

---

## Section E — Hardware Integration (Printer, Scanner, Drawer)

> **AI Agent:** ဒီ section ကို software test ဖြင့် မစစ်ဆေးနိုင်ပါ။ Physical hardware ဖြင့် စစ်ဆေးမှသာ `[x]` mark ပြုလုပ်ခွင့်ရှိသည်။

### E.1 Thermal Receipt Printer Testing

**Test Matrix — Myanmar Market Common Printers:**

| Printer Model | Connection | 58mm Test | 80mm Test | Auto-Cut | Drawer Kick |
|---|---|---|---|---|---|
| XPrinter XP-58IIH | USB | [ ] | N/A | [ ] | [ ] |
| XPrinter XP-80C | USB | N/A | [ ] | [ ] | [ ] |
| Rongta RP58-BPLUS | Bluetooth | [ ] | N/A | [ ] | [ ] |
| TVS-E RP45 Shoppe | USB | [ ] | [ ] | [ ] | [ ] |
| Generic ESC/POS | LAN | [ ] | [ ] | [ ] | [ ] |

**Print Quality Checklist (per model tested):**
- [ ] Myanmar Unicode characters render correctly (no boxes/question marks)
- [ ] Myanmar digits `၀-၉` render correctly when used
- [ ] QR Code (Payment QR) scans correctly from printed receipt
- [ ] Paper cut is clean (no paper jam on auto-cut)
- [ ] Cash drawer opens on sale completion (if connected)
- [ ] Print latency ≤ 3 seconds from cart checkout to print complete

### E.2 Barcode Scanner Testing

**Scanner Compatibility Matrix:**

| Scanner Type | Scan → Product Found | Continuous Scan | Speed |
|---|---|---|---|
| USB HID Barcode Scanner | [ ] | [ ] | [ ] ≤500ms |
| Bluetooth Barcode Scanner | [ ] | [ ] | [ ] ≤1s |
| Phone Camera (manual) | [ ] | N/A | [ ] ≤2s |

**Barcode Format Support:**
- [ ] EAN-13 (standard retail) — scan test
- [ ] EAN-8 (small products) — scan test
- [ ] Code 128 (self-generated SKU) — scan test
- [ ] QR Code (internal tracking) — scan test

### E.3 Barcode Label Printer Testing
- [ ] 50×30mm label — product name, price, barcode print ရှင်းလင်းစွာ ပြသပေးခြင်း
- [ ] 40×30mm label — barcode scan ပြုလုပ်နိုင်ခြင်း
- [ ] A4 label sheet (30-up) — alignment မပျက်ဘဲ print ထုတ်ပေးခြင်း
- [ ] Myanmar product name on label — font render ကောင်းမွန်ခြင်း

---

## Section F — Module-by-Module Production Audit

> **Verification Protocol:** Each module, apply Section B criteria (B.1-B.7). Then check module-specific items below.  
> **Legend:** ✅ Verified end-to-end | ⚠️ Partial — UI exists but untested | ❌ Not implemented | 🔧 Needs fix

---

### F.1 POS Counter (`/store/{slug}/pos`)

| Feature | Status | Verification Method |
|---|---|---|
| Barcode scan → cart add ≤ 500ms | ⚠️ | Chrome DevTools Network tab, physical scanner test |
| Multi-payment split (Cash+KPay+Wave) | ⚠️ | Manual: enter split amounts, verify total, check receipt |
| Order Hold (≥ 3 simultaneous holds) | ⚠️ | Hold 3 orders, recall each, verify cart content |
| Wholesale price auto-switch for B2B customer | ⚠️ | Login as wholesale customer, add product, verify price tier |
| Zero stock sale block (server-side) | ⚠️ | POST direct API call with qty > stock, expect 422 |
| Duplicate barcode scan = qty increment | ⚠️ | Scan same barcode twice, cart qty should be 2 |

**Section B Compliance:**
- [ ] B.1 (UI) — Mobile 375px cashier screen usable with 1 hand
- [ ] B.2 (Myanmar) — All labels in Myanmar, MMK formatted
- [ ] B.3 (Alpine) — No double-submit on payment confirm
- [ ] B.4 (Security) — Cashier cannot access Manager-only URLs
- [ ] B.5 (Database) — Stock ledger deducted atomically
- [ ] B.6 (Print) — Receipt auto-prints on sale complete
- [ ] B.7 (UX) — Toast "✅ ရောင်းချမှု ပြီးစီးပါပြီ" ≤ 200ms

---

### F.2 Daily Closing (`/store/{slug}/pos/closing`)

| Feature | Status | Verification Method |
|---|---|---|
| Opening float recorded per shift | ⚠️ | Open shift, enter 50,000 float, check audit log |
| Cash in drawer calculation accuracy | ⚠️ | Manual calculate vs system — must match |
| Discrepancy > threshold → manager approval | ⚠️ | Enter wrong count, verify manager approval prompt |
| Closing slip print (58mm + 80mm) | ⚠️ | Print on physical printer, verify Myanmar content |

- [ ] B.1-B.7 compliance verified

---

### F.3 Products & Inventory (`/store/{slug}/admin/products`)

| Feature | Status | Verification Method |
|---|---|---|
| Product create with image upload | ⚠️ | Create product, upload image, verify on storefront |
| Barcode uniqueness validation | ⚠️ | Enter duplicate barcode, expect `"ဘားကုဒ် ထပ်နေပါသည်"` error |
| Cost/Normal/Wholesale price tiers | ⚠️ | Set all 3 prices, verify each appears correctly in POS |
| Variant create (Color/Size) | ⚠️ | Create product with 3 color variants, verify stock separate |
| Excel import 100 products | ⚠️ | Import template, verify all imported, check error handling |
| Low stock alert trigger | ⚠️ | Set min stock = 5, sell to qty = 3, verify alert appears |

- [ ] B.1-B.7 compliance verified
- [ ] Empty state: "ကုန်ပစ္စည်း မရှိသေးပါ — ပထမဆုံး ပစ္စည်းထည့်ရန်" + Add button

---

### F.4 Stock Management

| Module | Route | Status | Key Verification |
|---|---|---|---|
| Stock Ledger | `/pos/reports/stock-ledger` | ⚠️ | Buy 10 → Sell 3 → Ledger shows +10, -3, balance 7 |
| Stock Count | `/admin/stock-count` | ⚠️ | Physical count sheet → discrepancy → auto-adjust with audit log |
| Stock Adjustment | `/pos/adjustments` | ⚠️ | Adjust -2 (damaged), verify ledger entry, reason required |
| Reconciliation | `/pos/reconciliation` | ⚠️ | Opening vs ledger discrepancy → manager post only |
| Opening Stock | `/pos/opening-stock` | ⚠️ | Set initial stock, verify cost calculation correct |

- [ ] B.1-B.7 compliance verified for each
- [ ] Inventory movement is atomic (no partial updates on error)

---

### F.5 Purchasing & Transfers

| Module | Route | Status | Key Verification |
|---|---|---|---|
| Purchase Order | `/pos/purchases` | ⚠️ | Create PO → Receive GRN → stock increments automatically |
| Purchase Return | `/pos/purchases/returns` | ⚠️ | Return to supplier → stock deducted, payable reduced |
| Supplier Payables | `/pos/purchases/payables` | ⚠️ | FIFO payment applied, history recorded |
| Branch Transfer | `/pos/transfers` | ⚠️ | Transfer 10 items, verify source -10, destination +10 |
| Suppliers | `/admin/suppliers` | ⚠️ | CRUD, Excel import, Aging report |

- [ ] B.1-B.7 compliance verified for each

---

### F.6 Ecommerce Storefront

| Module | Route | Status | Key Verification |
|---|---|---|---|
| Online Orders | `/admin/orders` | ⚠️ | Place storefront order, verify in admin, change status |
| Promotions/Coupon | `/admin/promotions` | ⚠️ | Create 10% coupon, apply in cart, verify discount math |
| Web Products Toggle | `/admin/web-products` | ⚠️ | Toggle product off, verify hidden from storefront |
| Product Reviews | `/admin/reviews` | ⚠️ | Approve review, verify shows on storefront |
| Push Notifications | `/admin/push` | ⚠️ | Send test push, verify browser notification received |
| Glass Finder | `/admin/glass-finder` | ⚠️ | Search by phone model, verify correct tempered glass listed |

- [ ] B.1-B.7 compliance for each
- [ ] Storefront mobile viewport (375px) — no horizontal overflow

---

### F.7 Customers & CRM

| Module | Route | Status | Key Verification |
|---|---|---|---|
| Customer Management | `/admin/customers` | ⚠️ | CRUD, debt profile, purchase history visible |
| Debt Collection | `/admin/receivables` | ⚠️ | Record payment, verify debt reduces, receipt printable |
| Wholesale Applications | `/admin/wholesale/applications` | ⚠️ | Approve application, verify wholesale price visible in storefront |
| Membership Tiers | `/admin/membership` | ⚠️ | Set Silver threshold, make qualifying purchase, verify tier auto-upgrade |

- [ ] B.1-B.7 compliance verified

---

### F.8 Repair & Service Jobs

| Module | Route | Status | Key Verification |
|---|---|---|---|
| Repair Intake | `/admin/repairs` | ⚠️ | Create ticket, print intake slip, status workflow Pending→Ready→Delivered |
| Advance Payment | in repairs | ⚠️ | Collect deposit, balance on pickup, ledger records both |
| Customer Tracking | public token | ⚠️ | Share token URL, verify customer can see status |
| Spare Parts Deduction | `/admin/spare-parts` | ⚠️ | Add parts to repair, verify inventory auto-deducted |
| Service Jobs (CCTV/Network) | `/admin/service-jobs` | ⚠️ | Create SVC job, assign technician, complete and invoice |

- [ ] B.1-B.7 compliance verified

---

### F.9 Finance & Accounts

| Module | Route | Status | Key Verification |
|---|---|---|---|
| Receivables | `/admin/receivables` | ⚠️ | Debt aging buckets correct, CSV export works |
| Profit & Loss | `/admin/profit-loss` | ⚠️ | Revenue - COGS - Expenses = Net, Waterfall chart |
| Expenses | `/admin/expenses` | ⚠️ | Create expense, verify reflected in P&L |
| Bank/Cash Transactions | `/admin/transactions` | ⚠️ | Transfer between accounts, fee recorded |
| Debt Aging Report | `/admin/debt-aging` | ⚠️ | FIFO aging buckets, overdue flag correct |

- [ ] Money calculations use bcmath throughout (no PHP float)
- [ ] B.1-B.7 compliance verified

---

### F.10 Reports & Analytics

| Report | Route | Status | Key Verification |
|---|---|---|---|
| Sales Report | `/pos/reports/sales` | ⚠️ | Date filter, totals match manual sum, CSV correct |
| Sales Analytics | `/admin/sales-analytics` | ⚠️ | Top 10 products, cashier leaderboard accurate |
| Cash Report | `/pos/reports/cash` | ⚠️ | Shift balance matches closing discrepancy |
| Stock Report | `/pos/reports/stock` | ⚠️ | Low/Out of stock counts accurate |
| Inventory Valuation | `/admin/inventory-valuation` | ⚠️ | Cost value = qty × cost price per product (spot check 5 items) |
| Service Report | `/pos/reports/services` | ⚠️ | Repair income = sum of completed job invoices |

- [ ] All CSV exports — open in Excel, verify Myanmar text not garbled (UTF-8 BOM)
- [ ] B.1-B.7 compliance verified

---

### F.11 Security & Access Control

| Feature | Status | Key Verification |
|---|---|---|
| Role matrix enforcement | ⚠️ | Cashier role: access `/admin/products` → 403 expected |
| Store owner cannot access other stores | ⚠️ | Change `store_id` in URL → 404/403 expected |
| Audit log — all money events | ⚠️ | Make sale, check audit_logs for sale entry with user, IP, timestamp |
| Support mode — reason required | ⚠️ | Platform admin enter store without reason → blocked |
| Session timeout | ⚠️ | Wait >8h (or set session lifetime short), access POS → redirect to login |

- [ ] B.4 Security compliance for all admin routes
- [ ] AuditLog entries exist for: Price change, Stock adjustment, Cash withdrawal, Role change, Login/Logout

---

### F.12 System Settings & Maintenance

| Module | Route | Status | Key Verification |
|---|---|---|---|
| Store Settings | `/admin/settings` | ⚠️ | Change store name, verify in receipt and storefront |
| Printer Settings | `/admin/printers` | ⚠️ | Add printer, test print button → receipt outputs correctly |
| Receipt Designer | `/admin/vouchers` | ⚠️ | Enable QR, preview updates, save and print |
| Exchange Rates | `/admin/exchange-rates` | ⚠️ | Update USD rate, verify Landed Cost Calculator uses new rate |
| Database Maintenance | `/admin/database` | ⚠️ | Vacuum + integrity check complete without errors |
| Backup & Restore | `/admin/backups` | ⚠️ | Download backup, delete test record, restore, verify record returns |
| Import History | `/admin/import-history` | ⚠️ | View past imports, download error CSV if any |

- [ ] B.1-B.7 compliance verified

---

### F.13 eLoad / Phone Top-up (`/store/{slug}/admin/eload`)

| Feature | Status | Key Verification |
|---|---|---|
| Operator balance display | ⚠️ | All 4 operators (MPT/Atom/Ooredoo/Mytel) show balance |
| Commission calculation | ⚠️ | Top-up 10,000, commission 2% → profit 200 recorded correctly |
| Transaction history | ⚠️ | Verify top-up recorded in transaction log |

---

## Section G — Security & Data Integrity Gates

> **AI Agent:** ဒီ section ကို automated + manual test နှစ်မျိုးလုံးဖြင့် စစ်ဆေးရမည်။

### G.1 Tenant Isolation (Critical — Must Pass 100%)
- [ ] **URL Tampering Test:** `GET /store/store-a/admin/products` ကို Store B user ဖြင့် access → `404` ပြရမည်
- [ ] **API Parameter Tampering:** POST request body တွင် `store_id` ကို ပြင်ပြောင်းပေးပို့ပါက application logic က ignore/reject ဖြစ်ရမည်
- [ ] **Report Cross-Store:** Store A ၏ sales report တွင် Store B ၏ data မပါဝင်ကြောင်း verify
- [ ] **Audit Log Isolation:** Store A admin မှ Store A ၏ audit logs သာမြင်ရပြီး Store B ၏ logs မမြင်ရကြောင်း verify

### G.2 Financial Integrity
- [ ] **Sale Atomicity:** Sale process (stock deduct + payment record + invoice create) ကို transaction ဖြင့် wrap ထားပြီး midway error ဖြစ်ပါက partial state မကျန်ကြောင်း verify
- [ ] **Double-Payment Guard:** Same invoice ကို twice submit ပြုလုပ်ပါက second payment reject ဖြစ်ကြောင်း verify
- [ ] **Reversal/Void Only:** Completed invoice ကို destructive edit မလုပ်နိုင်ဘဲ reversal/void ဖြင့်သာ ပြင်ဆင်နိုင်ကြောင်း verify
- [ ] **Ledger Reconciliation:** All inventory IN = purchases + opening stock; all OUT = sales + adjustments + returns. Spot check 3 products.

### G.3 Input Sanitization
- [ ] **XSS in Product Name:** `<script>alert('xss')</script>` ကို product name အဖြစ် save ပြုလုပ်ပြီး storefront/admin တွင် script execute မဖြစ်ကြောင်း verify
- [ ] **SQL Injection:** URL parameter / form field တွင် `' OR '1'='1` ထည့်ပါက error 422/404 ဖြစ်ပြီး data leak မဖြစ်ကြောင်း verify
- [ ] **File Upload Safety:** Non-image file (`.php`, `.exe`) ကို product image upload location တွင် upload ပြုလုပ်ပါက `422` ဖြင့် reject ါြစ်ကြောင်း verify

---

## Section H — Performance & Low-End Device Testing

> **AI Agent:** ဒီ tests တွေ fail ဖြစ်ပါက Performance budget ကျော်ကြောင်း flag ထောင်ပြရမည်။

### H.1 Page Load Budgets

| Page | Target Load Time | Test Method |
|---|---|---|
| POS Counter | ≤ 2s first load, ≤ 500ms subsequent | Chrome Lighthouse (throttled 3G) |
| Admin Product List (100 items) | ≤ 3s | Chrome Lighthouse |
| Admin Dashboard | ≤ 2s | Chrome Lighthouse |
| Storefront Homepage | ≤ 4s (3G) | Chrome Lighthouse |

- [ ] POS Counter — Lighthouse Performance Score ≥ 70 on throttled 3G
- [ ] Admin Dashboard — No page-level N+1 queries (Telescope: query count ≤ 20)
- [ ] Storefront — Images optimized (≤ 200KB each), lazy loaded

### H.2 Low-End Android Device Test

**Target Devices (Myanmar Market):**
- [ ] Samsung Galaxy A14 (or equivalent ≤ 4GB RAM) — Admin + POS browsing
- [ ] Realme C series (or equivalent ≤ 3GB RAM) — Cashier POS screen
- [ ] Any mid-range tablet — POS full-screen mode

**Criteria:**
- [ ] No horizontal scroll on 375px width
- [ ] Buttons/inputs minimum 44px tap target height
- [ ] Keyboard does not cover critical POS input fields on mobile
- [ ] Page remains responsive after 30 minutes continuous POS use (no memory leak)

### H.3 Database Performance
- [ ] Sales report for 1 year of data (12,000+ records) — query ≤ 5s
- [ ] Product search with 5,000+ products — results ≤ 1s
- [ ] Stock ledger for high-volume product (500+ movements) — loads ≤ 2s

---

## Section I — Localization & Typography

### I.1 Myanmar Language Coverage
- [ ] All nav menu items — Myanmar labels only (no untranslated English placeholder)
- [ ] All form labels — Myanmar
- [ ] All error messages — Myanmar user-friendly (not raw Laravel validation messages)
- [ ] All empty states — Myanmar with helpful action text
- [ ] All confirmation dialogs — Myanmar
- [ ] All button text — Myanmar

### I.2 Font Rendering Quality
- [ ] Chrome Windows — Myanmar font renders without box characters
- [ ] Chrome Android — Myanmar font renders on mobile
- [ ] Firefox — Myanmar font renders (Padauk/Pyidaungsu fallback works)

### I.3 Mixed Language Content
- [ ] Technical terms (IMEI, SKU, ESC/POS) — acceptable in English
- [ ] Product names — mixed Myanmar/English acceptable
- [ ] Admin labels — Myanmar primary, English abbreviation in parentheses acceptable

---

## Section J — Pilot Store Validation (Must Pass Before Sales)

> **အရေးကြီးသော မူ:** ဒီ section ကို AI Agent တစ်ယောက်တည်း စစ်ဆေး၍ မရပါ။ **Real cashier တစ်ဦး, real products, real daily workflow** ဖြင့် စစ်ဆေးမှသာ `[x]` mark ပြုလုပ်ခွင့်ရှိသည်။

### J.1 Pilot Store Setup (Pre-Pilot Checklist)
- [ ] Pilot store account created, Store Owner role assigned
- [ ] Initial 50+ products imported via Excel template
- [ ] Opening stock entered for all products
- [ ] Cashier account created with Cashier role (PIN enabled)
- [ ] Thermal printer connected and test receipt printed successfully
- [ ] Barcode scanner tested — all barcodes scan correctly
- [ ] Store settings — name, phone, address, receipt footer configured

### J.2 Day 1 — Full Workflow Test
- [ ] Cashier opens shift with float amount
- [ ] 10 POS sales completed (mix of cash, KPay, debt)
- [ ] 1 order hold and recall performed
- [ ] 1 return/refund processed
- [ ] 1 stock adjustment (damaged item) performed
- [ ] Shift closed — discrepancy < 500 ကျပ်
- [ ] Daily closing slip printed successfully

### J.3 Week 1 — Extended Validation
- [ ] Purchase order received — stock incremented correctly
- [ ] Supplier payment recorded — payable reduced
- [ ] Customer debt collected — receivable reduced
- [ ] Stock count performed — discrepancy resolved
- [ ] Backup created and verified (download + checksum pass)
- [ ] At least 1 repair job: intake → in progress → ready → delivered
- [ ] Profit & Loss report for the week — revenue/expense figures verified by owner

### J.4 Pilot Store Feedback Collection
- [ ] Cashier: "ဘာတွေ အဆင်မပြေ?" — issues documented
- [ ] Owner: "မည်သည့် report မျှော်လင့်သလောက် မတွေ့ဘူး?" — issues documented
- [ ] All reported bugs: fix, retest, confirm resolved
- [ ] Owner signs: "ဒီ Software ကို နေ့တိုင်း လုပ်ငန်းအတွက် အသုံးပြုရန် သင့်တော်ပြီ" ✍️

---

## Final Sign-Off Checklist

> **Platform Owner မှ Production Launch ကို approve မပြုမီ ဒီ checklist ၁၀ ချက်လုံး ပြည့်ရမည်။**

| # | Gate | Status |
|---|---|---|
| 1 | Codebase: No `dd()`, no secrets, no debug code | [ ] |
| 2 | Migrations: Fresh install + rollback both pass | [ ] |
| 3 | Tests: `php artisan test` — all pass, 0 failures | [ ] |
| 4 | Section B (Per-page criteria): Applied to ALL modules in F | [ ] |
| 5 | Hardware: Printer + Scanner tested on physical device (Section E matrix) | [ ] |
| 6 | Security: Tenant isolation tests pass (Section G.1 all items) | [ ] |
| 7 | Performance: Lighthouse ≥ 70 on POS + Admin (Section H.1) | [ ] |
| 8 | Myanmar UX: All labels Myanmar, MMK formatted, font renders (Section I) | [ ] |
| 9 | Pilot Store: Day 1 full workflow complete without critical errors (Section J.2) | [ ] |
| 10 | Owner Sign-off: Pilot store owner approves for daily use (Section J.4) | [ ] |

**Launch Decision:**
- 10/10 ✅ → **Production Launch ကို ခွင့်ပြုသည်**
- < 10/10 → **ကျန်ရှိသော items ကို Fix ပြုလုပ်ပြီးမှ recheck ပြုလုပ်ရမည်**

---

*Version 2.0.0 — Upgraded by Tech Buddy for DataPOS Production Launch Quality Assurance.*  
*ဒီ Checklist ကို AI Agent တစ်ခုက Verification ပြုလုပ်ရာတွင် ကိုးကား (Reference) ဖြစ်နိုင်ရန် ဒီဇိုင်းဆွဲထားသည်။*  
*Section J (Pilot Validation) ကို AI မစစ်ဆေးနိုင်ပါ — Human စစ်ဆေးမှသာ မှန်ကန်မည်ဖြစ်သည်။*
