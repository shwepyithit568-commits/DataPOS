# ၄။ တည်ဆောက်ရမယ့် အဆင့်ဆင့် (Implementation Phases)

> **ဒီဖိုင်မှာ:** POS စနစ်ကို ဘယ်ကစပြီး ဘယ်လို အဆင့်ဆင့် ဆောက်မလဲ — SoT §19 ကို လက်တွေ့ကျအောင် ခွဲထားတာ။
>
> **မူအရ:** အဆင့်တိုင်း ပြီးရင် စမ်းသပ်ပြီးမှ နောက်တစ်ဆင့်။ မလိုအပ်တဲ့အရာ ကြိုမဆောက်နဲ့။

---

## Phase 0 — Audit & Foundation (အခု စလို့ရပြီ)

**ရည်ရွယ်ချက်:** ဘာရှိပြီးသားလဲ၊ ဘာလိုလဲ သေချာသိ + အုတ်မြစ် ပြင်ဆင်

| အလုပ် | အသေးစိတ် | Status |
|---|---|---|
| Codebase audit | Laravel 12, models, routes, tests တွေ inventory | ✅ ဒီဖိုင်တွေထဲမှာ ရေးပြီးသား |
| Multi-store resolver ပြင် | Store ၂ ခုရှိရင် home မပျက်အောင် | ⏳ `docs/multi-store-ready-plan.md` ရှိပြီးသား |
| Data quality report | AppSheet/Google Sheets ဒေတာ စစ် | ⏳ စရန် |
| Gap analysis | SoT vs လက်ရှိ codebase — ကွာဟချက် | ⏳ စရန် |

**ထွက်ကုန်:** Phase 1 အတွက် task breakdown + acceptance tests

---

## Phase 1 — POS Foundation (အရင်ဆုံး ဆောက်ရမယ့် အုတ်မြစ်)

**ရည်ရွယ်ချက်:** POS ရဲ့ အခြေခံ data model + security

| အလုပ် | အသေးစိတ် |
|---|---|
| Branches + Warehouses | `branches`, `warehouses` tables + CRUD |
| Capabilities | `capabilities`, `branch_capabilities` — ဖွင့်ပိတ် ထိန်းချုပ်မှု |
| User branch roles + policies | `user_branch_roles` — Owner/Admin/Manager/Cashier/Read-only |
| Device registration/revocation | POS device တွေ မှတ်ပုံတင် |
| **enabled_modules (feature flags)** | Store အလိုက် POS/Ecommerce ဖွင့်ပိတ် — **resale အတွက် အရေးအကြီးဆုံး** |
| SKU/barcode normalization | Product မှာ UOM + barcode ထည့် |
| **Inventory movement ledger** | `inventory_movements` + `inventory_balances` — SoT §10 |
| Audit + approval foundation | `audit_logs`, `approvals` — SoT §15 |
| Backup/Restore commands | `php artisan backup` / `restore` |

**ဘာကြောင့် ဒီအစီအစဉ်လဲ:** ဒါတွေက နောက် module အကုန်ရဲ့ အောက်ခံ — ဒါမရှိရင် ဘာမှ မဆောက်နဲ့။

---

## Phase 2 — Online POS (ဦးစွာ online — offline မရောက်ခင်)

**ရည်ရွယ်ချက်:** Core POS ကို online မှာ အရင် အလုပ်ဖြစ်အောင် (complexity မြင့်တဲ့ offline ကို နောက်မှ)

| အလုပ် | အသေးစိတ် |
|---|---|
| POS cart + payment + receipt | `/pos` UI — barcode scan, qty, payment methods |
| Posted sale transaction | Sale header + lines + payments — atomic (SoT §11.2) |
| Stock integration | Sale → inventory movement − |
| Finance integration | Sale → finance ledger entry |
| Returns / Void / Reversal | မှားရင် ပြန်ပြင် (ရိုက်ဖျက်လို့ မရ — SoT §11.1) |
| Audit | အကုန် မှတ်တမ်း |

**ဘာကြောင့် online ဦးစား:** Offline sync က ရှုပ်ထွေးတယ် — online မှာ မှန်အောင် စမ်းပြီးမှ offline ထည့်မယ် (SoT §19 Phase 2 → 3)။

---

## Phase 3 — Offline PWA + Sync (offline-first)

**ရည်ရွယ်ချက်:** Internet မရှိတဲ့အခါလည်း ဆက်အလုပ်လုပ်နိုင်

| အလုပ် | အသေးစိတ် |
|---|---|
| `/pos` PWA installable | သီးခြား service worker (`/pos/sw.js`) |
| IndexedDB branch dataset | လိုတဲ့ဒေတာ သိမ်း (offline queue) |
| Idempotent sync API | `client_transaction_id` unique — duplicate မဖြစ်ရ (SoT §14) |
| Queue status UI | syncing / pending / failed / error — မမြင်ရအောင် မဝှက် |
| Device handoff | Windows ↔ Android ပြောင်းတဲ့အခါ စည်းမျဉ်း (SoT §7.2) |
| Local mode (SQLite) | Offline-only ဖောက်သည်အတွက် — backup/restore + offline license |

**ဘာကြောင့် နောက်မှ:** ဒါက အရှုပ်ထွေးဆုံး — core မှန်မှ ဒါ အလုပ်ဖြစ်မယ်။

---

## Phase 4 — Operations Modules (လုပ်ငန်းခွင်တွေ)

**ရည်ရွယ်ချက်:** POS အပြင် ကျန် module တွေ (SoT §9)

| Module | အချိန် |
|---|---|
| Purchases + Receiving + Purchase Returns | Phase 4 |
| Inventory Adjustments (Manager approval) | Phase 4 |
| Stock Counts + Transfers | Phase 4 |
| **Service Jobs** (ဖုန်းပြုပြင်ရေး) | Phase 4 |
| Customer Debt + Supplier Payables | Phase 4 |
| Expenses + Finance + **Daily Closing** | Phase 4 |
| Reports | Phase 4 |

---

## Phase 5 — Migration & Cutover (AppSheet → Laravel)

**ရည်ရွယ်ချက်:** အဟောင်း (AppSheet/Sheets) ဒေတာ ပြောင်းပြီး လက်ခံ

| အလုပ် | အသေးစိတ် |
|---|---|
| Clean + import | AppSheet/Sheets → Laravel (SoT §17) |
| Reconcile | Opening balances, inventory, debts, active jobs |
| Parallel validation | နှစ်စနစ် တွဲပြေး → ကိုက်ကြောင်း စစ် |
| Cutover | AppSheet → read-only → retire |

---

## Resale တိုးချဲ့မှု (ဖောက်သည်တွေ ပေါ်လာရင်)

| ဘယ်အခါ | ဘာလုပ် |
|---|---|
| ဖောက်သည်တွေ စဝင်လာတဲ့အခါ | `store:create` command + enabled_modules + license — ဒါတွေ Phase 1 မှာ ရှိပြီးသား |
| ပထမဆုံး ဖောက်သည်က ဆေးဆိုင် ဆိုရင် | Expiry date pack ဆောက် (core မှာ expiry ရှိပြီးသားဆိုရင် လွယ်) |
| ရွှေဆိုင် ဝယ်လာရင် | Weight pricing pack (UOM ရှိပြီးသား) |
| ပထမဆုံး offline-only ဖောက်သည် | Local mode (SQLite) — Phase 3 မှာ ပါပြီးသား |

---

## တည်ဆောက်စဉ် လိုက်နာရမယ့် အခြေခံစည်းမျဉ်း

1. **Store/tenant isolation** — store A ဒေတာ store B မှာ မပေါ်ရ
2. **Server-side authorization** — UI hide တစ်ခုတည်း မလုံလောက်
3. **Inventory/finance ကို atomic + auditable** — partial မဖြစ်ရ
4. **Idempotency** — sync retry လုပ်ရင် duplicate မဖြစ်ရ
5. **SQLite + MySQL နှစ်မျိုးလုံး** — migration/test run ရမယ်
6. **Burmese/English label** — နှစ်ဘာသာ
7. **Test ရေးရမယ်** — feature တိုင်းနဲ့
8. **SoT နဲ့ ဆန့်ကျင်ရင် ရပ်ပြီး မေး** — မခန့်မှန်းနဲ့

---

## ဆက်ဆွေးနွေးရန်

ဒီဖိုင်တွေ ဖတ်ပြီးရင် ဆရာကြီးနဲ့ ဒီအကြောင်းတွေ ဆွေးနွေးနိုင်ပါတယ်:
- Phase 0 ကစလား / Phase 1 ကစလား
- Plan/စျေးနှုန်း ပုံစံ
- ဘယ်လုပ်ငန်းကို ဦးစားပေးမလဲ
- Cloud mode ပဲလား Local mode ပါ လား
