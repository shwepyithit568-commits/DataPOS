# ၄။ တည်ဆောက်ရမယ့် အဆင့်ဆင့် (Implementation Phases)

> **ဒီဖိုင်မှာ:** POS စနစ်ကို ဘယ်ကစပြီး ဘယ်လို အဆင့်ဆင့် ဆောက်မလဲ။
>
> **Revision 2 (2026-08-10):** Owner မှ အတည်ပြုထားသော phase structure အသစ် — MVP scope ပြင်ပြီး (debt + closing က Phase 2)၊ AlinnThit pilot phase အသစ် ထည့်ပြီး၊ offline system ၂ မျိုး ခွဲပြီး။
>
> **မူအရ:** အဆင့်တိုင်း ပြီးရင် စမ်းသပ်ပြီးမှ နောက်တစ်ဆင့်။ မလိုအပ်တဲ့အရာ ကြိုမဆောက်နဲ့။

---

## Implementation Order (အဓိက ဦးစားပေး)

1. **Online Cloud POS** (Phase 0 → 2)
2. **AlinnThit production pilot** (Phase 2.5) — resale မလုပ်ခင် မဖြစ်မနေ
3. **Cloud PWA offline queue** (Phase 3)
4. **Local LAN/SQLite edition** (Phase 5)
5. **Cloud-to-local sync** — proven customer demand ရှိမှသာ

> Cloud PWA offline sync နဲ့ Local-server mode ကို phase တစ်ခုထဲ **မရော**။

---

## Phase 0 — Architecture Decisions & Risk Removal

**ရည်ရွယ်ချက်:** ကုဒ်မရေးခင် ဆုံးဖြတ်ချက်တွေ အတည်ပြု + risk ရှင်းပြီး foundation ပြင်ဆင်

| အလုပ် | အသေးစိတ် | Status |
|---|---|---|
| Tenancy/deployment decision | Cloud SaaS vs Local install — 02-target-design §2.3 | ✅ Approved 2026-08-10 — SoT နှစ်ဖိုင်လုံးတွင် မှတ်ပြီး |
| Store/domain resolver ပြင် | `docs/multi-store-ready-plan.md` အတိုင်း — store ၂ ခု active ရင် home မပျက်အောင် | ⏳ ရှိပြီးသား plan — စရန် (ပထမဆုံး implementation task) |
| Shared Ecommerce/POS inventory source of truth | Ledger ဒီဇိုင်း + adapter + stock_status derived ပြောင်း | ✅ SoT §5/§14.1 (MM) + §4/§10.1 (EN) ပြင်ပြီး — implementation စရန် |
| Money & rounding policy | Integer MMK, precision, rounding order (02 §2.6) — acceptance test နဲ့ သေချာ | ✅ Approved 2026-08-10 — SoT Open Decision #15/#6 Resolved |
| Weighted-average valuation | CostingService design (02 §2.7) | ✅ Rule က SoT §14.4/§10.4 မှာ မှတ်ပြီး — CostingService implementation စရန် |
| Negative-stock policy | Default block + future override rules (02 §2.8) | ⏳ Owner approve လို |
| Offline mode separation | Cloud queue vs Local — 02 §2.12 | ✅ ဒီစာရွက်စာတမ်းမှာ သတ်မှတ်ပြီး |
| Permission matrix | Store modules / branch capabilities / user roles / approvals — 4 levels | ⏳ စရန် |
| Data-quality audit | AppSheet/Google Sheets ဒေတာ စစ် | ⏳ စရန် |
| Architecture Decision Records (ADR) | ဆုံးဖြတ်ချက်တိုင်း ADR ရေး | ⏳ စရန် |
| Detailed acceptance tests | Phase 1–2 အတွက် acceptance criteria | ⏳ စရန် |

**ထွက်ကုန်:** Approved decisions + ADRs + Phase 1 task breakdown + acceptance tests

---

## Phase 1 — Minimum Shared Foundation

**ရည်ရွယ်ချက်:** POS ရော Ecommerce ရော မှီခိုရမယ့် အုတ်မြစ် — ဒါမရှိရင် ဘာမှ မဆောက်နဲ့

| အလုပ် | အသေးစိတ် |
|---|---|
| Default branch/warehouse | Store တိုင်းကို default branch + warehouse auto-create (02 §2.11) — ✅ **2026-08-11 done** (branches/warehouses tables + StoreLocationService::ensureDefaults + controller/CLI hooks + `inventory:ensure-locations` backfill + ledger FK + 10 tests — changelog item 258) |
| Store module middleware | Static routes + module/capability enforcement (02 §2.4) — route:cache compatible |
| Branch roles & policies | `user_branch_roles` + policies — Owner/Admin/Manager/Cashier/Read-only |
| Barcode/UOM foundation | Product UOM + barcode + decimal qty |
| Weighted-average costing | CostingService (02 §2.7) — ✅ **2026-08-11 done** (`App\POS\Services\CostingService` — receiving recalc, COGS carry, returns, serial specific cost, reversal replay — 12 tests — changelog item 260) |
| Customers & suppliers | Master data — debt အတွက် foundation |
| **Inventory movements & balances** | Ledger (02 §2.5) — immutable, idempotent, transactional — ✅ **2026-08-11 part 1 done** (migration + enum + models + InventoryService + `inventory:reconcile` + 19 tests — changelog item 257) |
| Opening stock | `opening_balance` movements — migration batch နဲ့ — movement type + service အသင့် (ledger part 1 ထဲ) |
| **Ecommerce inventory adapter** | `orders` → ledger integration — reserve/confirm/cancel — oversell မဖြစ်ရ — ✅ **2026-08-11 done** (`OrderInventoryAdapter` + `OrderAdminController@updateStatus` hook — reserve on confirm, commit on delivered, release on cancel, oversell block — 13 tests — changelog item 259) |
| Audit & approvals | `audit_logs`, `approvals` (SoT §15, §20) |
| Concurrency & idempotency tests | Parallel sale, duplicate posting, retry — အကုန် test |

**Exit criteria:** Ledger က POS + Ecommerce နှစ်ဖက်စလုံး ထိန်းနိုင် · cross-store leak 0 · route:cache + module middleware အလုပ်ဖြစ် · SQLite+MySQL green

---

## Phase 2 — Usable Online POS MVP

**ရည်ရွယ်ချက်:** ဆိုင်မှာ တကယ်သုံးလို့ရတဲ့ online POS — offline complexity မထည့်ခင် online integrity အရင် validate (SoT §28)

| အလုပ် | အသေးစိတ် |
|---|---|
| Cashier shifts + opening cash | ✅ item 261 — shift open/close, opening cash, cash in/out, daily summary (02 §2.10) |
| Barcode/HID scanner input | ✅ item 262 — /pos barcode/SKU/name search (HID scanner types into the search box) |
| Product & variant search | ✅ item 262 — live search w/ ledger balance |
| Cart | ✅ item 262 — add/merge/update/remove (retail pricing; wholesale နောက်) |
| Hold/resume sale | ✅ item 262 — session draft → held row → resume/void |
| Sale posting | ✅ item 262 — atomic: receipt @ posting, ledger movements, payments, drawer |
| Split payments | ✅ item 262 — payment modal: Cash / KPay / WavePay / CB Pay / MMQR, change calc (02 §2.8) |
| **Customer credit/debt** | ✅ item 264 — customer attach + `credit` payment → `customer_ledger_entries` (sale_debt/collection/reversal), balance = SUM, collect form (SoT §17) |
| Receipt & reprint | ✅ item 263 — printable receipt + reprint audit trail (SoT §8) |
| Audit trail (foundation) | ✅ item 263 — `audit_logs` table + AuditLog model (Phase 1 item, first consumer: reprints) |
| Sale return/refund/reversal | ✅ item 265 — `pos_returns` doc + `sales_return` ledger @ original cost + cash→drawer / credit→debt + partially_refunded/refunded (SoT §15.1, 02 §2.9) |
| Simple stock receiving | ✅ item 268 — `goods_receipts` doc (GRV-Ymd-####, idempotent) → `purchase_received` ledger @ unit cost → weighted-avg recalc (SoT §6) |
| Opening stock | ✅ item 269 — `opening_stock_requests` (OSR-Ymd-####): staff submits → manager approves → `opening_balance` ledger @ unit cost + avg set (SoT §6) |
| Inventory adjustment (manager approval) | ✅ item 270 — `inventory_adjustments` (ADJ-Ymd-####): cashier submits signed +/− with reason → manager approves → `adjustment_in/out` @ avg cost, avg unchanged (SoT §6) |
| ~~Audit trail~~ | ✅ အကုန် မှတ်တမ်း — items 261–270 |
| **Daily closing** | ✅ item 266 — `daily_closings` per store+date: expected (shift drawer math + e-method sales + credit info) vs counted, diff, explanation, **manager approval**, offline gate (SoT §18, 02 §2.10) |
| Minimal reports | ✅ item 267 — `/pos/reports/{sales,cash,stock}` read-only: sales + method totals (cashier/date filters), cash drawer per-shift + aggregates, stock qty × avg cost value (ledger cache) |


**Exit criteria:** အထက်ပါ MVP items အကုန် online မှာ အလုပ်ဖြစ် · atomic posting · oversell 0 · daily closing reconcile ရတယ် · POS tests green

> ✅ **2026-08-11 — Phase 2 (Online POS MVP) အကုန်ပြီးပြီ (items 261–270).** နောက်တစ်ဆင့်: Phase 2.5 AlinnThit pilot.

> **မှတ်ချက်:** Full purchasing / purchase returns / supplier payables / advanced accounting တွေက Operations (Phase 4) — MVP မဟုတ်ဘူး။

---

## Phase 2.5 — AlinnThit Production Pilot

**ရည်ရွယ်ချက်:** ပြင်ပဖောက်သည်ကို မရောင်းခင် ကိုယ့်ဆိုင်မှာ စမ်းပြီး workflow မှန်ကြောင်း အတည်ပြု

| အလုပ် | အသေးစိတ် |
|---|---|
| Clean product/customer/supplier data | ✅ item 271 — `/admin/pilot-import` hub (Products/Customers/Suppliers tabs): CSV/XLSX upload → **dry-run preview** (validation + duplicate detection: SKU / phone / phone-then-name) → confirm → ImportHistory + error reports · `suppliers` master table · templates |
| Opening-stock reconciliation | Ledger vs လက်ရှိ |
| Debt opening balances | Receivables import |
| AppSheet/Google Sheets parallel validation | နှစ်စနစ် တွဲပြေး → ကိုက်ကြောင်း စစ် |
| Real cashier workflow | တကယ့် ဆိုင်မှာ သုံး |
| Returns/refunds + customer debt + daily closing | MVP features တွေ real usage |
| Backup & restore test | Versioned workflow စမ်း |
| Performance test + store-isolation test | Load + tenant leak မရှိကြောင်း |
| Several weeks of observed real usage | Stabilization period |
| Written recovery/cutover runbook | Rollback/failover လုပ်နည်း စာရွက် |

**Exit criteria:** Pilot workflow မတည်မငြိမ်ခင် **ပြင်ပဖောက်သည်ကို မရောင်းရ** · runbook အပြည့် · reconciliation diff = 0

---

## Phase 3 — Cloud PWA Offline Queue

**ရည်ရွယ်ချက်:** Internet မရှိတဲ့အခါလည်း cloud POS ဆက်အလုပ်လုပ်နိုင် (Model A အတွက်)

| အလုပ် | အသေးစိတ် |
|---|---|
| `/pos` PWA installable | သီးခြား service worker (`/pos/sw.js`) — storefront SW မထိ (SoT §4.3) |
| IndexedDB branch dataset | လိုတဲ့ဒေတာ သိမ်း (offline queue) |
| Offline transaction queue | Draft → ready → syncing → synced + error states (SoT §19.4) |
| Idempotent sync API | `client_transaction_id` unique — duplicate မဖြစ်ရ (SoT §19.2) |
| Device registration/revocation | Device handoff workflow (SoT §9.2) |
| Queue status & recovery | syncing/pending/failed/error — မမြင်ရအောင် မဝှက် + failed-queue recovery/export |
| Conflict strategy | Posted immutable → correction document (SoT §19.5) |

**Exit criteria:** Offline sale → reconnect → sync → balance မှန် · duplicate 0 · revoked device reject · Windows + Android field test pass

---

## Phase 4 — Operations Modules

**ရည်ရွယ်ချက်:** MVP အပြင် ကျန် module တွေ (SoT §13)

| Module | အချိန် |
|---|---|
| Full purchasing + purchase returns + supplier payables | Phase 4 |
| Stock transfers + stock counts | Phase 4 |
| Service jobs (ဖုန်းပြုပြင်ရေး) + service parts | Phase 4 |
| Expenses + finance ledger | Phase 4 |
| Advanced reports | Phase 4 |
| Finance/accounting period closing | Phase 4 |

---

## Phase 5 — Local LAN/SQLite Edition & Resale Readiness

**ရည်ရွယ်ချက်:** Offline-only ဖောက်သည်အတွက် Local install + ပြင်ပရောင်းချဖို့ ပြင်ဆင် — ကျယ်ရင် ၂ ပိုင်း ခွဲ:

### 5a. Local installation, backup, restore, update workflow
- SQLite single-tenant install (Model B — 02 §2.3)
- Browser devices → LAN/Wi-Fi
- **Versioned backup/restore** (02 §2.15): WAL checkpoint, snapshot, assets, manifest, checksums, integrity verify, restore dry-run, pre-restore backup, version compat validation
- Versioned update workflow (upgrade path)
- **ပထမ Local release တွင် central cloud sync မပါ**

### 5b. Provisioning, plans, licensing, support mode, monitoring, documentation
- **Offline license** — signed payload, public-key verify — **private signing key ကို install ထဲ မထည့်ရ**
- Tenant provisioning tooling (Cloud) + plan gating
- Store Support Mode (02 §2.13)
- Monitoring + error reporting + measurable upgrade triggers (02 §2.16)
- Resale documentation + training materials

**Exit criteria:** Local install ကို ဖောက်သည်အသစ်တစ်ယောက်ဆီ ပေးပြီး backup→restore→update အကုန် အလုပ်ဖြစ် · license verify/reject test pass

---

## Phase 6 — Customer-driven Industry Packs

**ရည်ရွယ်ချက်:** ဖောက်သည်အစစ် ပေါ်လာမှသာ ဆောက်မယ် — validated demand မရှိရင် မဆောက်

| Pack | ဘယ်အခါ |
|---|---|
| Pharmacy (expiry/batch) | ဆေးဆိုင် ဖောက်သည် ရလာရင် |
| Gold Shop (ကျပ်/ပဲ/ရွေး + daily rate + karat) | ရွှေဆိုင် ဖောက်သည် ရလာရင် |
| Grocery (scale + multi-unit) / Restaurant (KOT/tables) / Fuel / Fashion matrix | Demand ပေါ်မှ တစ်ခုချင်းစီ |

---

## Resale တိုးချဲ့မှု (ဘယ်အချိန် ဘာလုပ်မလဲ)

| ဘယ်အခါ | ဘာလုပ် |
|---|---|
| Phase 2.5 pilot မတည်မငြိမ်ခင် | **ပြင်ပဖောက်သည်ကို မရောင်းရ** |
| Pilot ပြီးမှ Cloud ဖောက်သည်သစ် | `store:create` + enabled_modules — Phase 1 မှာ ရှိပြီးသား |
| Offline-only ဖောက်သည်သစ် | Local LAN edition — Phase 5 ပြီးမှ |
| License စတင်ရောင်းချင်ရင် | Signed offline license (5b) — MVP မစမ်းရသေးခင် မလုပ် |
| Industry pack လိုလာရင် | Phase 6 — demand ရှိမှ |

---

## တည်ဆောက်စဉ် လိုက်နာရမယ့် အခြေခံစည်းမျဉ်း

1. **Store/tenant isolation** — store A ဒေတာ store B မှာ မပေါ်ရ (SoT §6)
2. **Server-side authorization** — static routes + middleware — UI hide တစ်ခုတည်း မလုံလောက်
3. **Inventory/finance ကို atomic + auditable** — partial မဖြစ်ရ (SoT §15.2, §19.3)
4. **Idempotency** — sync retry လုပ်ရင် duplicate မဖြစ်ရ (SoT §19.2)
5. **Money/quantity float မသုံး** — integer MMK + decimal precision (02 §2.6)
6. **Negative stock default block** (02 §2.8)
7. **SQLite + MySQL နှစ်မျိုးလုံး** — migration/test run ရမယ်
8. **Burmese/English label** — နှစ်ဘာသာ (lang ၃ ဖိုင်)
9. **Test ရေးရမယ်** — feature တိုင်းနဲ့
10. **SoT နဲ့ ဆန့်ကျင်ရင် ရပ်ပြီး မေး** — မခန့်မှန်းနဲ့ (02-target-design ရဲ့ SoT Conflicts ဇယား)

---

## ဆက်ဆွေးနွေးရန်

- ✅ SoT amendment ၅ ခု (02-target-design §SoT Conflicts) — **Approved 2026-08-10 + နှစ်ဖိုင်လုံးတွင် apply ပြီး**
- Phase 0 ရဲ့ ကျန် Owner decisions (negative stock override, hosting trigger thresholds) အတည်ပြုဖို့
- AlinnThit pilot ရဲ့ စတင်ရက် / data source
