# DataPOS — Production Readiness & Quality Assurance Master Checklist
**Document Version:** 2.1.0 — Expanded with Toolbar, Export/PDF, Finance, L10n, Dark Mode Sections  
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
12. [**Section K — Admin Toolbar Consistency Audit**](#section-k--admin-toolbar-consistency-audit)
13. [**Section L — Import / Export / PDF Matrix**](#section-l--import--export--pdf-matrix)
14. [**Section M — Inventory, Debt & Finance Deep Audit**](#section-m--inventory-debt--finance-deep-audit)
15. [**Section N — Localization Key Coverage Audit**](#section-n--localization-key-coverage-audit)
16. [**Section O — Dark Mode & Light Mode Full Audit**](#section-o--dark-mode--light-mode-full-audit)
17. [Final Sign-Off Checklist](#final-sign-off-checklist)

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
- [x] **No `dd()`, `dump()`, `var_dump()`, `ray()` debug calls in production code**
  - **Audited (`2026-08-29`):** Word-boundary grep across `app/` + `resources/` clean. Only `dump(` hits are the legitimate `$this->dump(PDO $pdo, string $driver)` database-dump method in `app/Services/DatabaseBackupService.php` (not a debug call).
  - Verification: `grep -rnE "\bdd\(|\bdump\(|\bvar_dump\(|\bray\(" app/ resources/ --include="*.php" --include="*.blade.php" | grep -vE "is_array|in_array|toArray|FromArray"`
- [x] **No `console.log()` in production JavaScript**
  - **Audited (`2026-08-29`):** `grep -rn "console.log" resources/js/ resources/views/` returned zero matches.
- [x] `.phpunit.result.cache` in `.gitignore`
- [x] **No `TODO:` / `FIXME:` / `HACK:` / `XXX:` blocking production code**
  - **Audited (`2026-08-29`):** zero matches across `app/`. Remaining blank — flag any blockins found in full review.
  - Verification: `grep -rnE "TODO:|FIXME:|HACK:|XXX:" app/ --include="*.php"` — must be 0 blocking items

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

---

## Section K — Admin Toolbar Consistency Audit

> **Background:** DataPOS တွင် shared `<x-admin.toolbar>` component တစ်ခု ရှိပြီး Search, Filter, Sort, ViewToggle (Table/Card), Export (Excel+CSV), Import, Pagination ပါဝင်သည်။ ဒီ Section တွင် module တစ်ခုချင်းစီ ဒီ Toolbar ကို မှန်မှန်ကန်ကန် သုံးနေမနေ စစ်ဆေးသည်။

### K.1 Toolbar Component Architecture
- [x] `resources/views/components/admin/toolbar.blade.php` — Shared toolbar component ရှိပြီး
- [x] Props: `search`, `filters`, `sort`, `viewMode`, `exportUrl`, `importUrl`, `paginator`, `bulkActions` — configurable ဖြစ်ပြီး
- [x] Toolbar container — `rounded-xl bg-white/95 dark:bg-slate-900/95` dark mode support ပါဝင်ပြီး
- [x] Export dropdown modal — Excel (.xlsx) + CSV (.csv) format choices
- [ ] **Toolbar `exportUrl` filter carryover:** Export ခလုတ်နှိပ်သောအခါ ယခုစိစစ်ထားသော search/filter params ကို export URL ထဲ carryover ဖြစ်ကြောင်း verify (ဥပမာ — Status=Completed filter ထားပြီး Export နှိပ်ပါက Completed orders သာ export ဖြစ်ကြောင်း)

### K.2 Per-Module Toolbar Presence Audit

> **Verification Method:** Browser တွင် module list page ကိုဖွင့်ပြီး Toolbar (Search + Filter + Export) ပြသနေမနေ စစ်ဆေးရမည်။

| Module | Route | Search | Filter | Sort | Export | Import | Pagination | Status |
|---|---|:---:|:---:|:---:|:---:|:---:|:---:|---|
| Products | `/admin/products` | [ ] | [ ] | [ ] | [x] | [x] | [ ] | ⚠️ Verify |
| Customers | `/admin/customers` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Suppliers | `/admin/suppliers` | [ ] | [ ] | [ ] | [x] | [x] | [ ] | ⚠️ Verify |
| Orders | `/admin/orders` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Repairs | `/admin/repairs` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Service Jobs | `/admin/service-jobs` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Expenses | `/admin/expenses` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Transactions | `/admin/transactions` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Receivables | `/admin/receivables` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Inventory Valuation | `/admin/inventory-valuation` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Debt Aging | `/admin/debt-aging` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Purchases | `/pos/purchases` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| POS Sales Report | `/pos/reports/sales` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |
| Audit Logs | `/admin/security/audit-logs` | [ ] | [ ] | [ ] | [x] | [ ] | [ ] | ⚠️ Verify |

### K.3 Toolbar Behavior Consistency
- [ ] **Search debounce:** ← အားလုံး ≥ 300ms debounce ပါဝင်ကြောင်း (not instant request per keystroke)
- [ ] **Clear search (X button):** Search input ဘေးတွင် clear ခလုတ် ပေါ်ပြနေကြောင်း တိုင်းထည့်ပြောင်းလဲသည်ပါ
- [ ] **Active filter pill display:** Filter ရွေးလျှင် pill badge အဖြစ် toolbar အောက်တွင် ပြသကြောင်း
- [ ] **Filter clear:** Filter pill ပေါ်က X ကို နှိပ်ပါက filter ရှင်းပြီး list refresh ဖြစ်ကြောင်း
- [ ] **Per-page selector:** Paginator ရှိသော pages တွင် 25/50/100/All selector ပေါ်နေကြောင်း
- [ ] **Pagination URL preservation:** Page change သောအခါ current search/filter params ကျန်ရှိနေကြောင်း
- [ ] **View mode (Table/Card) — localStorage persistence:** Page reload ပြန်ဝင်ပါကလည်း ရွေးထားသော view mode ကျန်ရှိနေကြောင်း

---

## Section L — Import / Export / PDF Matrix

> **Background:** Module တစ်ခုချင်းစီ မည်သည့် format ဖြင့် Export/Import/Print ပြုလုပ်နိုင်သည်ကို စစ်ဆေးရမည်။
> **Verification:** Actual download ပြုလုပ်ပြီး file ကို Excel/LibreOffice/Notepad တွင် ဖွင့်ကြည့်ပြီး Myanmar content မပျက်ကြောင်း (UTF-8 BOM) verify ဖြစ်ရမည်။

### L.1 Export / Import Matrix Per Module

| Module | XLSX Export | CSV Export | PDF/Print | Import | Thermal Print |
|---|:---:|:---:|:---:|:---:|:---:|
| Products | [ ] | [ ] | — | [ ] | — |
| Customers | [ ] | [ ] | — | — | — |
| Suppliers | [ ] | [ ] | — | [ ] | — |
| Orders | [ ] | [ ] | [ ] PDF Invoice | — | — |
| Purchases | [ ] | [ ] | [ ] Purchase Slip | — | — |
| Receivables | [ ] | [ ] | [ ] Debt Statement | — | — |
| Supplier Payables | [ ] | [ ] | [ ] Payable Statement | — | — |
| Profit & Loss | [ ] | [ ] | [ ] P&L Statement | — | — |
| Inventory Valuation | [ ] | [ ] | [ ] Inventory Statement | — | — |
| Debt Aging Report | [ ] | [ ] | [ ] Aging Statement | — | — |
| Sales Report | [ ] | [ ] | — | — | — |
| Stock Report | [ ] | [ ] | — | — | — |
| Cash Report | [ ] | [ ] | — | — | [ ] Closing Slip |
| Service Report | [ ] | [ ] | — | — | — |
| Audit Logs | [ ] | [ ] | — | — | — |
| Roles/Users | [ ] | [ ] | — | — | — |
| Wholesale Applications | — | — | [ ] Approval Slip | — | — |
| POS Receipt (Sale) | — | — | — | — | [ ] 58mm / 80mm |
| POS Receipt (Return) | — | — | — | — | [ ] 58mm / 80mm |
| Repair Intake Slip | — | — | — | — | [ ] 58mm / 80mm |
| Stock Count Sheet | — | [ ] | [ ] Physical Count Sheet | — | — |
| Customer Debt Receipt | — | — | — | — | [ ] 80mm |

### L.2 Excel Export Quality Gates

**Per exported XLSX file, verify:**
- [ ] Myanmar text renders correctly in Excel (not garbled boxes)
- [ ] Column widths auto-fit to content (`columnAutoSize`)
- [ ] Header row has distinct styling (bold / colored background)
- [ ] Numeric columns (MMK amounts) are right-aligned and formatted with commas
- [ ] Date columns display `dd/mm/yyyy` format (Myanmar preference)
- [ ] Empty cells are blank (not "null" string)
- [ ] File opens without "Repair file" warning in Excel

### L.3 CSV Export Quality Gates
- [ ] UTF-8 BOM (`EF BB BF`) present — prevents garbled Myanmar in Excel on Windows
- [ ] Myanmar text visible when opened in Notepad/LibreOffice
- [ ] Comma-separated correctly (no unescaped commas inside quoted fields)
- [ ] Newline handling correct (CRLF for Windows compatibility)

### L.4 PDF / Browser Print Quality Gates

**Print pages needing `@media print` CSS verification:**
- [ ] `admin/profit_loss/statement.blade.php` — A4 print, clean layout, no navbar
- [ ] `admin/orders/invoice.blade.php` — A4 invoice, header/footer/items/total correct
- [ ] `admin/receivables` debt statement — A4 or 80mm print
- [ ] `admin/debt_aging` aging statement — A4 print
- [ ] `admin/inventory_valuation` — A4 print
- [ ] `admin/wholesale/print.blade.php` — Wholesale approval slip
- [ ] `pos/purchases/show.blade.php` — Purchase receipt

**Print criteria (each page):**
- [ ] `@media print` — sidebar/navbar/buttons hidden
- [ ] Myanmar font renders on print preview (Padauk/Pyidaungsu)
- [ ] Page breaks at sensible points (`page-break-inside: avoid` for table rows)
- [ ] No orphan single-row on last page
- [ ] Company name / Store name / Date / Invoice No clearly visible

### L.5 Thermal Print Template Quality Gates

**For each Thermal Print layout (58mm/80mm):**
- [ ] Myanmar store name prints without boxes
- [ ] Item names with Myanmar characters wrap correctly at 32 chars (58mm) / 48 chars (80mm)
- [ ] Total/Change displayed prominently (bold/larger size)
- [ ] QR code (if enabled) prints with correct size and scans with phone camera
- [ ] ESC/POS cut command at end of receipt
- [ ] No extra blank lines before cut

---

## Section M — Inventory, Debt & Finance Deep Audit

> **Goal:** ငွေကြေး, အကြွေး, စတော့ calculation တိကျမှုကို End-to-End trace လုပ်ပြီး verify ပြုလုပ်ရမည်။

### M.1 Inventory Ledger Integrity

**Trail Test — Product တစ်ခု lifecycle trace:**
```
Opening Stock → Purchase Receive → Sale → Return → Adjustment → Transfer → Stock Count
```
- [ ] **Opening stock = base:** Product `opening_qty = 50` ဖြင့် စတင်ပြီး ledger first entry verify
- [ ] **Purchase +10:** GRN receive 10 units → ledger `+10` entry, balance `= 60`
- [ ] **Sale -3:** POS sell 3 units → ledger `-3`, balance `= 57`
- [ ] **Return +2:** Customer return 2 → ledger `+2`, balance `= 59`
- [ ] **Damage adjustment -1:** Stock adjust (Damage) → ledger `-1`, balance `= 58`
- [ ] **Transfer -5:** Branch A → Branch B transfer 5 → Branch A `-5`, Branch B `+5`
- [ ] **Stock count reconcile:** Physical count = 53, system = 53 → ကွာဟချက် 0 confirm
- [ ] **All movements in stock_ledger view:** Bin card `/admin/stock-ledger` တွင် ဒီ movements အားလုံး timeline ဖြင့် ပြသပေးကြောင်း verify

### M.2 Customer Debt (Receivables) Integrity

**FIFO Debt Trail Test:**
- [ ] **Debt creation:** POS sale with Debt Credit payment `30,000 ကျပ်` → customer debt `30,000` ဖြစ်ကြောင်း
- [ ] **Partial collection:** Collect `10,000` → remaining debt `20,000` ဖြင့် ledger update ဖြစ်ကြောင်း
- [ ] **Multiple debts FIFO:** Customer debt Invoice A=`20,000`, Invoice B=`15,000`; collect `25,000` → FIFO: Invoice A fully paid, Invoice B `10,000` remaining
- [ ] **Debt aging buckets:** 31-day old debt → `31-60 days` bucket တွင် ပါဝင်ကြောင်း
- [ ] **Debt receipt print:** Collect payment ပြီးသောအခါ Thermal receipt/PDF statement ထုတ်နိုင်ကြောင်း
- [ ] **Cross-store debt isolation:** Store A customer ၏ debt ကို Store B admin မမြင်ရကြောင်း

### M.3 Supplier Payables Integrity

**FIFO Payable Trail Test:**
- [ ] **Payable creation:** Purchase Order receive → supplier payable `amount` ဖြစ်ကြောင်း
- [ ] **Payment settlement:** Pay supplier `X ကျပ်` → payable reduces by `X`, history recorded
- [ ] **FIFO order:** Oldest payable ကို ဦးစွာ settle ဖြစ်ကြောင်း
- [ ] **Purchase return credit:** Supplier return → payable reduces or credit note created
- [ ] **Aging report:** Overdue payables 90+ days → flagged in report

### M.4 Profit & Loss Calculation Integrity

**Formula Verification (spot check with manual calculator):**
```
Gross Revenue = Sum of all sale amounts in period
COGS = Sum of (qty × cost_price) for sold items
Gross Profit = Gross Revenue - COGS
Expenses = Sum of all expense entries in period
Net Profit = Gross Profit - Expenses
```
- [ ] **Gross Revenue accuracy:** P&L report total vs manual sum of sales report for same date range — must match
- [ ] **COGS accuracy:** 5 random products — `qty_sold × cost_price` manual check vs report COGS
- [ ] **Expense inclusion:** Create `10,000` expense in period, verify P&L expenses increase by `10,000`
- [ ] **Returns deduction:** Process return — verify P&L revenue decreases correctly
- [ ] **Date range filter:** P&L for `01/08/2026 - 31/08/2026` includes only August transactions
- [ ] **Bcmath precision:** No floating-point rounding error in totals (e.g., `7,499.9999` must not appear)

### M.5 Cash Drawer & Shift Integrity

- [ ] **Opening float recorded:** Cashier enters `50,000 ကျပ်` float → audit_log entry exists
- [ ] **Cash in = opening + cash sales - cash withdrawals:** Formula verify at shift end
- [ ] **Discrepancy calculation:** Expected `85,000`, Counted `84,500` → Discrepancy `-500 ကျပ်`
- [ ] **KPay/Wave separate from cash drawer:** Digital payments do NOT add to cash drawer balance
- [ ] **Closing slip accuracy:** Slip totals match screen totals
- [ ] **Shift history:** Previous shifts viewable with their opening/closing amounts

### M.6 Exchange Rate & Landed Cost

- [ ] **Rate update:** Admin update USD rate from `2,100` to `2,150` → new purchases use `2,150`
- [ ] **Landed cost calculator:** Import product at USD `50` + `2,150 rate` + `5% duty` → MMK cost `113,625 ကျပ်` (verify formula)
- [ ] **Old transactions:** Rate change does NOT retroactively change old purchase costs

---

## Section N — Localization Key Coverage Audit

> **Background:** EN/MY/ZH all have **3,173 keys** (0 missing). However **31 keys** have identical EN=MY values — likely untranslated Myanmar labels.

### N.1 Current Language Coverage Status

| Language | Total Keys | Missing Keys | Likely Untranslated | Status |
|---|---|---|---|---|
| English (en) | 3,173 | — (baseline) | — | ✅ Complete |
| Myanmar (my) | 3,173 | 0 | **13 keys** (all accepted brand names — see N.2) | ✅ 31→13 fixed |
| Chinese (zh_CN) | 3,173 | 0 | Accepted brand names | ⚠️ Review needed |

**Verification command:**
```bash
php -r "
  error_reporting(0);
  \$en=include 'lang/en/messages.php';
  \$my=include 'lang/my/messages.php';
  \$miss=array_diff_key(\$en,\$my);
  echo 'Missing in MY: '.count(\$miss).PHP_EOL;
  \$unt=array_filter(\$en, fn(\$v,\$k)=>isset(\$my[\$k])&&\$my[\$k]===\$v&&is_string(\$v)&&preg_match('/[a-zA-Z]/',\$v),ARRAY_FILTER_USE_BOTH);
  echo 'Same as EN: '.count(\$unt).PHP_EOL;
" 2>/dev/null
```

### N.2 Untranslated Keys — RESOLVED (`2026-08-29`)

**Translated to Myanmar in `lang/my/messages.php` (18 keys fixed):**

- [x] `viber_telegram_chat` → `"Viber / Telegram ချက်တက် ဆက်သွယ်မည်"`
- [x] `backup_zip_btn` → `"အပြည့်အစုံ Backup (.zip)"`
- [x] `backup_sql_btn` → `"SQL အရန်သိမ်း (.sql)"`
- [x] `backup_sqlite_btn` → `"SQLite မိတ္တူ (.sqlite)"`
- [x] `backup_format_zip` → `"စနစ်အပြည့် ZIP (.zip)"`
- [x] `backup_format_sql` → `"SQL ဖိုင်ဖော်မက် (.sql)"`
- [x] `backup_format_sqlite` → `"SQLite မိတ္တူ (.sqlite)"`
- [x] `theme_preview_label` → `"ကြိုကြည့်ရန်"`
- [x] `promotion_code` → `"ကူပွန် ကုဒ်"`
- [x] `web_catalog_live_sync` → `"Storefront တိုက်ရိုက် Sync"`
- [x] `printers_conn_usb` → `"USB တိုက်ရိုက် (ESC/POS)"`
- [x] `printers_device_path` → `"USB လိပ်စာ / COM Port / Bluetooth ID"`
- [x] `receivables_statement_badge` → `"ဖောက်သည် စာရင်းရှင်း"`
- [x] `pl_gross_margin` → `"စုစုပေါင်း အမြတ်နှုန်း"`
- [x] `pl_net_margin` → `"အသားတင် အမြတ်နှုန်း"`
- [x] `export_excel_format` → `"Excel ဖိုင် (.xlsx)"`
- [x] `export_csv_format` → `"CSV ဖိုင် (.csv)"`
- [x] `warranty_certificate_title` → `"အာမခံ သက်သေခံလွှာ"`

**Remaining 13 — approved as brand / proper names (no translation needed):**

- [x] `payment_wavepay`, `payment_cb_pay`, `payment_mmqr` — payment brand names
- [x] `facebook`, `youtube`, `tiktok` — social platform brand names
- [x] `contact_channel_viber`, `contact_channel_telegram` — contact-app brand names
- [x] `vouchers_qr_kpay` (KBZPay), `vouchers_qr_wave` (Wave Money) — QR payment brand names
- [x] `vouchers_preset_clean` / `tech` / `classic` — already include a Myanmar parenthetical (`ရိုးရှင်း သန့်ပြန့်`, `ခေတ်မီဆန်းသစ်`, `ဘောင်ခတ် စာရင်းပုံစံ`)

**Verification result after fix:** `Same as EN: 13` (0 genuinely untranslated Myanmar labels remain; all 13 are accepted brand/proper names). `php -l` passes.

### N.3 Localization Key Usage Audit

- [ ] **No raw English strings in Blade views:** ❌ **~50 hardcoded English strings found** (`2026-08-29`). Examples: `admin/alerts/index.blade.php` ("Database Tools", "Telegram Bot API"), `admin/brands/import.blade.php` ("Skip duplicate brands", "Update existing brands"), `admin/orders/invoice.blade.php` ("Billed To"), many "Export CSV" / "Back to X" spans. Must be wrapped in `__('messages.…')`.
- [x] **Auth messages:** `lang/my/auth.php` present and translated.
- [x] **Validation messages:** `lang/my/validation.php` **created** (`2026-08-29`) — full Myanmar translation of all EN keys (0 missing), `php -l` clean. Replaces Laravel English fallback (Section I.1 fix).
- [x] **Pagination:** `lang/my/pagination.php` **created** (`2026-08-29`) with `"&laquo; ယခင်"` / `"နောက် &raquo;"`. Also created `lang/my/passwords.php`; both cover 100% of EN keys.
- [ ] **ZH locale completeness:** If Chinese language is offered to customers, verify zh_CN translations are not placeholders

### N.4 Runtime Localization Test

- [ ] **Language switcher works:** Switch EN → MY → ZH and back — all labels change correctly
- [ ] **Session persistence:** Language setting persists across page loads and browser restart
- [ ] **Flash messages:** Success/Error toasts display in selected language
- [ ] **Validation errors:** Form submission failure shows Myanmar validation messages (not Laravel default English)
- [ ] **Storefront language:** Customer-facing storefront pages respect selected locale

---

## Section O — Dark Mode & Light Mode Full Audit

> **Background:** DataPOS သည် Tailwind `dark:` class system ကို အသုံးပြုသည်။ Theme ၅ မျိုး (Marketplace Pro, Retail Trust, Emerald Fresh, Midnight Tech, Sunset Warm) ရှိပြီး Dark/Light toggle feature ပါဝင်သည်။

### O.1 Dark Mode Toggle Mechanism
- [ ] **Toggle control location:** Admin settings / POS counter ဆက်တင်တွင် Dark↔Light mode toggle ရွှေ့ပြောင်းနိုင်သော button ရှိပြီး (**ရှာ၍မတွေ့ပါက ဦးစွာ implement လုပ်ရမည်**)
- [ ] **localStorage persistence:** Dark mode ရွေးထားပါက browser reload ပြန်ဝင်လည်း dark mode ဆက်ရှိကြောင်း
- [ ] **Transition smooth:** Light ↔ Dark ပြောင်းသောအခါ CSS transition ≤ 200ms (not instant flicker)
- [ ] **No flash of wrong theme (FOUT):** Page load ချိန်တွင် Light mode ဖြင့် flash ဖြစ်ပြီးမှ Dark mode ကူးသွားခြင်း မရှိကြောင်း (`<html class="dark">` ကို server/localStorage မှ JS ဖြင့် early-apply ဖြစ်ကြောင်း)

### O.2 Admin Panel Dark Mode Coverage

**စစ်ဆေးရမည့် CSS class pattern:** `dark:bg-*`, `dark:text-*`, `dark:border-*`

| Admin Page | Light Mode | Dark Mode | Contrast OK | Status |
|---|:---:|:---:|:---:|---|
| Dashboard / Home | [ ] | [ ] | [ ] | ⚠️ Verify |
| Products List | [ ] | [ ] | [ ] | ⚠️ Verify |
| POS Counter | [ ] | [ ] | [ ] | ⚠️ Verify |
| Modals (any) | [ ] | [ ] | [ ] | ⚠️ Verify |
| Forms (create/edit) | [ ] | [ ] | [ ] | ⚠️ Verify |
| Tables (all) | [ ] | [ ] | [ ] | ⚠️ Verify |
| Charts/Analytics | [ ] | [ ] | [ ] | ⚠️ Verify |
| Repair/Service Jobs | [ ] | [ ] | [ ] | ⚠️ Verify |
| Settings Pages | [ ] | [ ] | [ ] | ⚠️ Verify |
| Toolbar Component | [x] | [x] | [ ] | ✅ Built-in |
| Sidebar Navigation | [ ] | [ ] | [ ] | ⚠️ Verify |
| Admin Alerts/Toasts | [ ] | [ ] | [ ] | ⚠️ Verify |

### O.3 Dark Mode Specific Issues to Check

- [ ] **White background leaks:** Dark mode တွင် `bg-white` class ကို `dark:bg-slate-900` မပါဘဲ သုံးနေသော elements ကြောင့် bright white box ပေါ်မလာကြောင်း
- [ ] **Text contrast (WCAG AA):** Dark background ပေါ်တွင် text contrast ratio ≥ 4.5:1 ဖြစ်ကြောင်း — Chrome DevTools Accessibility panel ဖြင့် spot check
- [ ] **Icon visibility:** SVG icon stroke colors dark mode တွင် `dark:text-*` class မပါဘဲ invisible မဖြစ်ကြောင်း
- [ ] **Input field backgrounds:** Form inputs dark mode တွင် `dark:bg-slate-800 dark:text-slate-100` ဖြင့် readable ဖြစ်ကြောင်း
- [ ] **Placeholder text:** Input placeholder dark mode တွင် `dark:placeholder-slate-400` ဖြင့် too dark မဖြစ်ကြောင်း
- [ ] **Border visibility:** Borders dark mode တွင် `dark:border-slate-700` ဖြင့် visible ဖြစ်ကြောင်း (not invisible white on black)
- [ ] **Badge/Pill colors:** Status badges (green/red/yellow) dark mode တွင် background + text ကြည်လင်ကြောင်း
- [ ] **Chart colors:** Analytics charts dark mode တွင် axis labels + grid lines readable ဖြစ်ကြောင်း

### O.4 Storefront Dark Mode (if applicable)
- [ ] Customer-facing storefront dark mode — theme preset ၅ ခုအတွက် dark variant ရှိမရှိ confirm
- [ ] `Midnight Tech` theme — OLED-friendly dark mode ဖြင့် correct contrast
- [ ] `Marketplace Pro` dark mode — product cards readable
- [ ] Storefront dark preference follows OS preference (`prefers-color-scheme: dark`) — စစ်ဆေးကြောင်း

### O.5 POS Counter Dark Mode (High Priority for Cashier UX)
- [ ] **Daylight High-Contrast (Light) mode:** POS counter outdoor ဆိုင်တွင် bright sunlight ဝင်နေချိန် numbers readable ဖြစ်ကြောင်း
- [ ] **OLED Dark mode:** POS counter night shift / dimly-lit ဆိုင်တွင် eye-strain နည်းကြောင်း
- [ ] **1-tap toggle:** Cashier ကို settings သွားစရာမလိုဘဲ POS header ဖြင့်သာ mode ပြောင်းနိုင်ကြောင်း (**မရှိပါက implement လုပ်ရမည်**)
- [ ] **Cart item readability:** Dark mode POS cart တွင် product name + price + qty ကြည်လင်ကြောင်း
- [ ] **Keyboard shortcuts:** Dark mode toggle keyboard shortcut (`Ctrl+D` သို့မဟုတ် `F11`) ပါဝင်ကြောင်း (optional enhancement)

---

## Final Sign-Off Checklist

> **Platform Owner မှ Production Launch ကို approve မပြုမီ ဒီ checklist ၁၅ ချက်လုံး ပြည့်ရမည်။**

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
| 11 | Toolbar: All list pages have consistent Search/Filter/Export/Pagination (Section K) | [ ] |
| 12 | Export/PDF: XLSX+CSV Myanmar text correct, PDF print clean on all report pages (Section L) | [ ] |
| 13 | Finance Integrity: Inventory ledger + Debt FIFO + P&L formula spot-checked (Section M) | [ ] |
| 14 | Localization: 31 untranslated keys resolved, no raw English strings in views (Section N) | [ ] |
| 15 | Dark/Light Mode: All admin pages + POS counter pass contrast audit (Section O) | [ ] |

**Launch Decision:**
- 15/15 ✅ → **Production Launch ကို ခွင့်ပြုသည်**
- 13-14/15 → **Minor issues — fix within 2 days, recheck**
- < 13/15 → **ကျန်ရှိသော items ကို Fix ပြုလုပ်ပြီးမှ recheck ပြုလုပ်ရမည်**

---

*Version 2.1.0 — Expanded by Tech Buddy: Toolbar Consistency + Export/PDF Matrix + Finance Deep Audit + Localization + Dark Mode.*  
*ဒီ Checklist ကို AI Agent တစ်ခုက Verification ပြုလုပ်ရာတွင် ကိုးကား (Reference) ဖြစ်နိုင်ရန် ဒီဇိုင်းဆွဲထားသည်။*  
*Section J (Pilot Validation) ကို AI မစစ်ဆေးနိုင်ပါ — Human စစ်ဆေးမှသာ မှန်ကန်မည်ဖြစ်သည်။*
