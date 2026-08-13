# ၂။ လိုချင်တဲ့ ပုံစံ (Target Design)

> **ဒီဖိုင်မှာ:** POS စနစ်ကို ဘယ်လို ပုံစံနဲ့ တည်ဆောက်မလဲ — architecture, module ခွဲထားမှု, deployment model ၂ မျိုး, inventory ledger, money policy, sale lifecycle။
>
> **အခြေခံ:** `Source_of_Truth_MM.md` (SoT) — ဒါက ဒီဖိုင်ရဲ့ အနှစ်ချုပ် ပုံစံပါ။ **Revision 2 (2026-08-10)** — owner မှ အတည်ပြုထားသော architecture corrections များ ထည့်ပြီး။
>
> **သတိပြုရန်:** ဒီဖိုင်က SoT နဲ့ ဆန့်ကျင်နေသော အချက်များရှိပါက — SoT amendment ကို Owner ထံ တင်ပြပြီး အတည်ပြုမှ ရေးသားရမည် (SoT §33)။ လက်ရှိ ဆန့်ကျင်ချက်စာရင်း → ဒီဖိုင် အောက်ဆုံး "SoT Conflicts" အပိုင်း။

---

## ၂.၁ အဓိက ဆုံးဖြတ်ချက်: **တစ်ခုတည်းသော codebase**

Ecommerce ရော POS ရော **ဒီပရောဂျက်ထဲမှာပဲ** ဆောက်မယ် — ပရောဂျက်အသစ် သီးခြား မဆောက်ဘူး (SoT §4.1)။

**ဘာကြောင့်လဲ:**
- POS က catalog (products/SKU/prices) ကို ဒီထဲကပဲ ယူသုံးရမယ်
- Staff/auth/store isolation က ရှိပြီးသား
- Deploy pipeline တစ်ခုတည်း
- Cloud SaaS တွင် ဖောက်သည်တစ်ယောက် = `stores` table ထဲ tenant row တစ်ခု

---

## ၂.၂ Module ခွဲထားမှု (အရေးအကြီးဆုံး)

တစ်ခုတည်းထဲ ဆောက်ပေမဲ့ **POS ကို တင်းကျပ်တဲ့ module** အနေနဲ့ ခွဲထားရမယ်:

| အပိုင်း | စည်းမျဉ်း |
|---|---|
| Namespace | `App\POS\...` (Controller/Model/Service အကုန်) — ecommerce code နဲ့ မရောနှောရ |
| Tables | POS tables သီးခြား (`branches`, `warehouses`, `inventory_movements`, `sales`, ...) — ecommerce `orders` ကို ပြန်မသုံးရ (SoT §5) |
| Routes | `/pos` + `/pos/admin` — route group သီးခြား + middleware သီးခြား။ **Static registration** (၂.၄ ကြည့်ပါ) |
| Service Worker | `/pos/sw.js` (scope: `/pos/`) — storefront `/sw.js` နဲ့ မရောနှောရ (SoT §4.3) |
| CSS/JS | `pos.css` / `pos.js` — Vite entry သီးခြား |
| Tests | `tests/Feature/POS/...` — သီးခြား directory |
| Migration | POS migrations က add-only — ecommerce tables ကို မပြောင်း (ခြွင်းချက်: inventory adapter အတွက် approved migration) |
| Catalog share | POS က products ကို **read-only** share — inventory ကို shared ledger နဲ့ ထိန်း (၂.၅) |

---

## ၂.၃ Deployment Model ၂ မျိုး — **တစ်ခုနဲ့တစ်ခု မရောရ**

တစ်ခုတည်းသော codebase ကို model ၂ မျိုးနဲ့ run လုပ်လို့ရမယ်။ ဒီနှစ်မျိုးက **မတူညီတဲ့ system** ဖြစ်ပြီး "one deployment per cloud customer" နဲ့ "one multi-tenant SaaS application" ကို တစ်မျိုးတည်းလို မရေးရ။

### Model A — Cloud (Multi-tenant SaaS) — ပုံမှန်

| အချက် | အသေးစိတ် |
|---|---|
| Application | **ဗဟို application တစ်ခုတည်း** — cloud ပေါ်မှာ တစ်ခါတည်း run |
| Tenants | Store/tenant အများကြီး — တစ်ယောက်ချင်းစီအတွက် သီးခြား install/deploy **မလုပ်** |
| Isolation | တင်းကျပ်သော `store_id` scope — query/resource အဆင့်တိုင်း (SoT §6) |
| Modules | Store အလိုက် enabled modules (POS / Ecommerce / Both) |
| Admin | Platform Owner က tenant အကုန် စီမံ · Store Owner က သူ့ဆိုင်ပဲ |
| Domain | စတင်တွင် path/subdomain (`/store/{slug}`) — custom domain က နောက်ပိုင်း ထည့်နိုင် |
| Database | Cloud MySQL (central source of truth) |
| Offline | Cloud PWA offline queue (IndexedDB + idempotent sync) — Phase 3 |

### Model B — Local (Single-tenant installation) — offline ဖောက်သည်

| အချက် | အသေးစိတ် |
|---|---|
| Installation | ဖောက်သည်တစ်ယောက်အတွက် **dedicated install တစ်ခု** — ဆိုင်ထဲ PC ပေါ်မှာ |
| Database | **SQLite** — local |
| Network | Local PC / LAN (ဆိုင်ထဲ Wi-Fi) — browser devices က LAN ကတဆင့် ဝင်သုံး |
| Internet | **အမြဲ internet မလို** — cloud sync မလို |
| License | Signed offline license (public-key verify) — **resale နောက်ပိုင်း** (Phase 5)၊ MVP မစမ်းရသေးခင် မလုပ် |
| Backup | Versioned backup/restore/update workflow — live file copy မဟုတ်ဘူး (၂.၁၅) |

### Operational consequences (နှစ်မျိုးလုံး)

| လုပ်ငန်းဆောင်တာ | Cloud SaaS | Local install |
|---|---|---|
| Tenant အသစ် | `store:create` command → tenant row (deploy မလို) | Install package တစ်ခုလုံး ပေးရ |
| Update | တစ်ခါ deploy → အကုန် ရောက် | ဆိုင်တစ်ဆိုင်ချင်းစီ — versioned update workflow |
| Data center | ဗဟို MySQL တစ်ခု | SQLite — ဆိုင်တစ်ဆိုင်စီ သီးခြား |
| Scale | ဆိုင်ခွဲ/ဖောက်သည် များလာရင် infra ပြင်ရ (၂.၁၆) | Scale မလို — local ပဲ |
| Backup | Central daily + runbook | Versioned local backup (checksum + manifest) |
| Support | Platform Owner က remote (Support Mode — ၂.၁၃) | ဆိုင်မှာ လက်နဲ့ လုပ်ရ / support access ပိုခက် |

---

## ၂.၄ Module / Capability Enforcement — **Static Routes + Server-side Middleware**

**အရေးကြီးဆုံး correction:** tenant/feature flag ပေါ်မူတည်ပြီး route တွေကို conditionally register **မလုပ်ရ**။ Laravel route caching (`php artisan route:cache`) နဲ့ မကိုက်ညီလို့ပါ။

**နည်းလမ်း:**
1. POS routes အားလုံးကို **statically register** လုပ်မယ်
2. Route group ပေါ်မှာ **server-side module/capability middleware** တပ်မယ်:
   - Active store ကို resolve လုပ်
   - Module enabled လား စစ် (store-level)
   - Branch capability ရှိလား စစ် (branch-level)
   - Branch access + user role permission ရှိလား စစ် (SoT §7: `branch access AND branch capability AND role permission`)
   - မရရင် ရည်ရွယ်ချက်ရှိရှိ **403 သို့မဟုတ် 404** ပြန်
3. UI မှာ မရတဲ့ navigation items တွေကို **hide** — ဒါက authorization မဟုတ်ဘူး၊ UX သက်သက်ပဲ
4. Backend authorization က **authoritative** (UI hide တစ်ခုတည်း မလုံလောက် — SoT §7)

**Permission အဆင့် ၄ မျိုး — သီးခြား ခွဲထားရမယ်:**

| အဆင့် | ဘာကို ထိန်း | ဥပမာ |
|---|---|---|
| Store-level modules | ဘယ် module ဖွင့်လဲ | `enabled_modules`: pos / ecommerce / service / inventory / finance |
| Branch-level capabilities | ဘယ် branch မှာ ဘာလုပ်လို့ရလဲ | `branch_capabilities`: pos_sales, inventory, service, customer_debt, ... (SoT §7) |
| User permissions | ဘယ် user က ဘယ် branch မှာ ဘယ် action | `user_branch_roles` + policies (Owner/Admin/Manager/Cashier/Read-only) |
| Approval permissions | ဘယ် action က ဘယ်သူ့ approval လို | Manager approval: adjustment, void/reverse, discount override, handoff, backdated (SoT §8) |

---

## ၂.၅ Inventory Ledger — **POS ရော Ecommerce ရဲ့ တစ်ခုတည်းသော Source of Truth**

> **Correction:** Ledger ကို "POS-only stock system" အဖြစ် ဒီဇိုင်းမလုပ်ရ။ **POS နဲ့ Ecommerce နှစ်ခုလုံးရဲ့** authoritative inventory source ဖြစ်ရမယ်။

`products.quantity` / `products.stock_status` တစ်ခုတည်းကို inventory truth အဖြစ် မသုံးရ (SoT §14.1) — Revision 2 မှာ **stock_status က migration ကာလအတွင်း derived compatibility/cache field အဖြစ်သာ** ကျန်ရစ်မယ်။ Ledger ကနေ recalculate/rebuild လုပ်နိုင်ရမယ်။

### Ledger က ထောက်ပံ့ရမယ့် movement types (အနည်းဆုံး)

| Movement | Effect | မှတ်ချက် |
|---|---|---:|---|
| `opening_balance` | + | Migration batch |
| `purchase_received` | + | Goods receipt ဖြစ်မှ (PO တင်ရုံနဲ့ မတိုး) |
| `purchase_returned` | − | Supplier settlement ပါ update |
| `pos_sale` | − | Walk-in sale |
| `pos_return` | + | Refund/exchange return |
| `online_order_reservation` | − | Online order confirm ချိန်မှာ reserved |
| `online_order_confirmation` | − | Reserved → committed |
| `online_order_cancellation` | + | Cancel → availability ပြန် |
| `inventory_adjustment` | ± | Cashier က submit → manager approve မှ |
| `stock_count` | ± | Count difference |
| `transfer_out` | − | Dispatch |
| `transfer_in` | + | Receipt |
| `service_consumption` | − | Service parts (နောက်ပိုင်း) |
| `service_part_return` | + | Service parts (နောက်ပိုင်း) |
| `reversal` | ± | Correction — `reversal_of_id` နဲ့ link |

### Online order reservation policy (Ecommerce adapter)

- **Reserve လုပ်ချိန်:** Online order confirm လုပ်တဲ့အခါ (Viber/Telegram confirm → `online_order_reservation` movement)
- **Commit လုပ်ချိန်:** Order က fulfillment စတင် / payment ပြည့် / dispatch — `online_order_confirmation` (reserved → committed)
- **Reservation expire/release:** သတ်မှတ်ထားတဲ့ ကာလအတွင်း confirm/fulfill မဖြစ်ရင် auto-release (`online_order_cancellation`) — ကာလကို Owner သတ်မှတ် (Open Decision)
- **Cancel:** Cancel ဖြစ်ရင် reserved quantity ကို availability ပြန်ထည့်
- **Reconciliation:** `inventory_movements` ကနေ balance rebuild/verify — ledger နဲ့ balance ကွာရင် alert + review workflow

### Ecommerce integration (adapter/service)

- Existing `orders` + `order_items` ကို **ပြောင်းမလုပ်** — `InventoryAdapter` / service က order lifecycle events တွေကို ledger movements အဖြစ် ပြောင်းပေးမယ်
- POS sale ရော online order ရော တူညီတဲ့ stock pool ကနေ ယူလို့ **oversell မဖြစ်ရ**
- `sale_source` / order reference ကို movement `source_type`/`source_id` မှာ မှတ်

### Ledger table spec (အနည်းဆုံး fields)

`inventory_movements`: `store_id`, `branch_id`, `warehouse_id`, `product_id`, `product_variant_id`, `movement_type`, `quantity_delta`, `unit_cost`, `source_type`, `source_id`, `client_transaction_id`, `occurred_at`, `posted_by`, `reversal_of_id`, `metadata` (JSON), timestamps

### Ledger rules (မဖြစ်မနေ)

1. **Posted movement ကို edit/delete မလုပ်ရ** — correction က reversal movement နဲ့သာ (SoT §15.1)
2. **Duplicate posting မဖြစ်ရ** — `(store_id, source_type, source_id)` သို့မဟုတ် `client_transaction_id` unique constraint (SoT §19.2)
3. **Offline retry idempotent** — same `client_transaction_id` → existing result ပြန် return
4. **Balance update က transactional** — `DB::transaction` (SoT §19.3)
5. **Concurrent sales မှားမဖြစ်ရ** — row lock / atomic update ဖြင့် race ကာကွယ်
6. **`inventory_balances` က derived performance cache** — direct write မလုပ်ရ
7. **Reconciliation command** — `php artisan inventory:reconcile` — movements ကနေ balances rebuild/verify + mismatch report

### Indexes (အနည်းဆုံး)

- Unique: `(store_id, source_type, source_id)` · `(store_id, client_transaction_id)`
- Index: `(warehouse_id, product_id, product_variant_id)` · `(occurred_at)` · `(reversal_of_id)` · `(movement_type)` — MySQL + SQLite compatible

---

## ၂.၆ Money, Quantity နဲ့ Rounding Policy

**Float ကို money/quantity အတွက် မသုံးရ။**

| အကြောင်း | ဆုံးဖြတ်ချက် |
|---|---|
| MMK storage | **Integer (ကျပ်)** — DB column: `BIGINT`/`INTEGER` (cents/ပြား မလို) — သို့မဟုတ် decimal(16,2) — နှစ်မျိုးလုံး float မဟုတ် |
| Quantity precision | `DECIMAL(12,3)` (MySQL) / `NUMERIC(12,3)` (SQLite) — fractional qty foundation (0.5kg, 2.5L) |
| Unit cost precision | `DECIMAL(14,4)` — weighted average အတွက် precision ပိုမြင့် |
| Discount/tax order | Line discount → line tax → line total → subtotal → order discount → tax → **grand total** (အစဉ်ကို Phase 0 မှာ acceptance test နဲ့ သေချာ သတ်မှတ် — Open Decision #14) |
| Receipt total rounding | Round ကို **final step တစ်ခါတည်းသာ** — intermediate rounding မလုပ် |
| Exchange rate (future) | `DECIMAL(14,6)` — နောက်ပိုင်း multi-currency အတွက် column ပဲ ထားမယ် — **full multi-currency feature ကို MVP မစောင့်ရ** |
| Posted sale totals | Posting ချိန်မှာ **immutable calculated totals** သိမ်း — နောက် price ပြောင်းရင် မပြောင်း |

---

## ၂.၇ Inventory Valuation — **Weighted-average Costing** (ကနဦး Mobile/Electronics MVP)

- **Receiving:** New stock ဝင်ရင် — `new_avg_cost = (existing_qty × existing_avg_cost + received_qty × received_unit_cost) / total_qty`
- **Returns (sales return):** Return က cost ကို ပြန်မတွက်ဘူး — original movement ရဲ့ unit cost ကို သုံး (sale line cost ပြန် restore)
- **Purchase return:** Supplier return က weighted average ကို ပြန်တွက် (returned qty ရဲ့ cost ကို နုတ်)
- **Adjustments:** Adjustment က လက်ရှိ avg cost ကို သုံး (valuation change မဟုတ်ဘူး) — special case ကို Phase 0 မှာ သတ်မှတ်
- **Serial/IMEI items:** Specific cost ထိန်းနိုင်ရမယ် (serialized item တိုင်းရဲ့ cost သီးခြား) — လိုအပ်ရင် movement `metadata` မှာ မှတ်
- **Negative inventory:** Default **ပိတ်ထား** (၂.၈) — negative ဖြစ်ချိန် cost calculation ကို မလုပ်ရအောင် block
- အားလုံး costing logic က `App\POS\Services\CostingService` ထဲ စုပြီး test ရေးရမယ်

---

## ၂.၈ Negative Stock Policy

- **Default:** Negative available stock ဖြစ်စေမည့် sale/adjustment ကို **block** (SoT §14.3)
- Exception (နောက်ပိုင်း ဒီဇိုင်း): Authorized manager override — ဒါပေမဲ့ override တိုင်း **audit + visibly reported** ဖြစ်ရမယ်
- Override design က Phase 0 မှာ သတ်မှတ်မယ် (Open Decision #16)

---

## ၂.၉ POS Sale State Machine

```
Draft → Held → Posted → Partially Refunded → Refunded
                     ↘ Reversed
Voided (posting မတိုင်ခင် — draft/held ကိုသာ)
```

| State | Rules |
|---|---|
| Draft / Held | Edit လို့ရ — hold/resume sale (MVP ပါ) — held sale က သိမ်းထားပြီး နောက်မှ resume |
| Posted | **Edit/delete မလုပ်ရ** — receipt number က **posting ချိန်မှာ** assign |
| Partially Refunded / Refunded | Refund document ဖြင့်သာ — original sale ကို မပြောင်း |
| Reversed | Posting အမှားကို reversal နဲ့ ပြင်ရမယ် (SoT §15.1) |
| Voided | Posting မတိုင်ခင် draft/held ကိုသာ void — posted ဖြစ်ပြီးသား sale ကို void မလုပ်ရ |

**Atomicity:** Posted sale တစ်ခုမှာ — sale header + lines + payments + inventory movements + finance entries + audit record အကုန် `DB::transaction` ထဲ (SoT §15.2, §19.3)

**Printing:** Print မအောင်လို့ posted sale ကို **ပြန်မဖျက်ရ** — sale က ပြီးပြီး၊ reprint ပဲ လုပ်ရမယ်။ Reprints တွေကို audit (reprint log) လုပ်ရမယ်

---

## ၂.၁၀ Cashier Shift နဲ့ Daily Closing — အဆင့် ၃ မျိုး

**အရေးကြီး:** ဒီသုံးမျိုးက မတူဘူး — ရောထွေးမရေးရ။

| အဆင့် | ဘာလဲ | ဘယ်အချိန် |
|---|---|---|
| **Cashier shift closing** | Cashier တစ်ယောက် + register/device တစ်ခု — shift စ/ဆုံး | နေ့စဉ် — shift တိုင်း |
| **Branch daily closing** | Branch တစ်ခုလုံးရဲ့ နေ့စဉ် summary — shifts အကုန် ပေါင်း | နေ့ကုန် |
| **Finance/accounting period closing** | စာရင်းကိုင်ကာလ (monthly...) — ledger ကို ပိတ် | ကာလအလိုက် (နောက်ပိုင်း) |

**Cashier shift fields (အနည်းဆုံး):** branch, device/register, cashier, opening_time, opening_cash, cash_sales, cash_refunds, cash_in/out, expected_closing_amount, actual_closing_amount, difference, notes, closed_by, manager_approval (required ဖြစ်ရင်)

**MVP (Phase 2):** Cashier shift closing + simple branch daily summary — finance period closing က Operations phase

**Daily closing rule (SoT §18):** Unresolved pending offline sale ရှိနေချိန် final closing ကို approve မလုပ်ရ (owner-approved exceptional procedure မရှိရင်)

---

## ၂.၁၁ Branches နဲ့ Warehouses — ဆက်နွယ်မှု

- **Branch** = business/sales location · **Warehouse** = inventory location
- Branch တစ်ခုမှာ warehouse တစ်ခု သို့မဟုတ် အများကြီး ရှိနိုင် (SoT §14.2)
- **Store တိုင်းကို default branch + default warehouse တစ်ခုစီ auto-create** လုပ်ပေးမယ်
- **Single-branch store** မှာ branch-switching UI ကို မပြရ — unnecessary complexity
- **Multi-branch UI** က branch > 1 ဖြစ်ပြီး multi-branch capability enabled မှသာ ပေါ်

---

## ၂.၁၂ Offline System ၂ မျိုး — သီးခြားခွဲ

### (a) Cloud PWA offline queue (Cloud SaaS အတွက် — Phase 3)
- Central MySQL + POS PWA + IndexedDB + offline transaction queue
- Internet ပြန်ရတဲ့အခါ sync · device registration/revocation (SoT §9) · conflict handling (SoT §19.5) · idempotency (SoT §19.2) · failed-queue recovery/export

### (b) Local LAN installation (Model B — Phase 5)
- Laravel က ဆိုင်ထဲ PC ပေါ်မှာ run · SQLite · browser devices တွေ LAN/Wi-Fi ကတဆင့် ဝင်
- **ပထမ Local release မှာ central cloud sync မပါ** — dedicated backup/restore/update workflow သာ
- Cloud-to-local sync က **proven customer demand ရှိမှသာ** (နောက်ပိုင်း)

**ဒီနှစ်ခုကို phase တစ်ခုထဲ မရော။** Recommended order → ROADMAP.md

---

## ၂.၁၃ Platform Owner Support Access — Explicit Workflow

> **Correction:** Platform Owner ကို "store အကုန် invisible ဝင်လို့ရတယ်" ဆိုတဲ့ လွတ်လပ်တဲ့ access **မပေးရ** — SoT §6 ရဲ့ store isolation ကို မချိုးရ။

**Store Support Mode workflow:**
1. Enter Store Support Mode — **reason မဖြစ်မနေ ရိုက်ရမယ်**
2. Start/end time ကို မှတ်တမ်းတင်မယ်
3. **Write တိုင်း audit** — actor, store, entity, before/after, reason (SoT §20)
4. **Active store ကို ရှင်းရှင်းလင်းလင်း ပြ** — banner/indicator ဖြင့်
5. **Accidental cross-store write ကို ကာကွယ်** — support session ထဲမှာ store context lock
6. **Finance / data-export actions တွင် ပိုတင်းကျပ်** — extra confirmation + approval
7. **Store Owner visibility** — သင့်တော်ရာ support activity ကို store owner က မြင်နိုင် (support log)

Platform Owner access ကို "unscoped tenant query ရေးခွင့်" အဖြစ် မသုံးရ — support session ကလွဲပြီး ပုံမှန် query တွေက store-scoped ဖြစ်ရမယ်

---

## ၂.၁၄ Industry Scope — **Mobile/Electronics POS က ပထမဆုံး**

> **Correction:** "Industry-agnostic core" ကို abstract လုပ်နေရုံနဲ့ မရ — **ပထမဆုံး စစ်မှန်တဲ့ product က Mobile/Electronics POS** ဖြစ်ရမယ်။

**Mobile/Electronics MVP core (Priority):**
- SKU · Barcode/HID scan · Product variants · Piece-based UOM · Decimal quantity foundation
- Serial/IMEI tracking · Warranty · Retail/Wholesale pricing · Customer debt
- Receiving · Branch/Warehouse inventory · Returns/Exchanges

**နောက်ပိုင်း Operations phase:** Mobile repair/service jobs · full purchasing · supplier payables

**Future extension points — ဖောက်သည်အစစ် မပေါ်မချင်း မဆောက်ရ:**
Pharmacy (expiry/batch) · Grocery/scale integration · Gold pricing (ကျပ်/ပဲ/ရွေး) · Restaurant KOT/tables · Fuel pumps · Advanced fashion matrix

Architecture က forward-compatible ဖြစ်ရမယ် (UOM, decimal qty, custom fields) — ဒါပေမဲ့ pack တွေက **customer demand ပေါ်မှသာ** (Phase 6)

---

## ၂.၁၅ Backup / Restore — Versioned Workflow (Local Edition)

> **Correction:** SQLite backup ကို "live file copy" အဖြစ် မရေးရ။

**Backup package (တစ်ခုချင်းစီမှာ):**
- Consistent DB snapshot (WAL checkpoint ပြီးမှ)
- Uploaded files/assets
- Manifest (created_at, app version, schema version, checksums)
- Integrity verification

**Restore workflow:**
- Restore dry-run/check · Automatic pre-restore backup · Version compatibility validation · Clear failure/recovery behavior

**Offline licensing:** Resale Readiness (Phase 5) မှာ — MVP မစမ်းရသေးခင် မလုပ်။ License = **signed payload** — public key နဲ့ verify။ **Private signing key ကို customer installation ထဲ ဘယ်တော့မှ မထည့်ရ**

---

## ၂.၁၆ Hosting / Operations — Upgrade Triggers

**AlinnThit pilot အတွက်:** Existing shared hosting (Hostinger) က လက်ခံနိုင် (SoT Hosting Decision)

**Resale SaaS အတွက်:** အောက်ပါတွေ လိုအပ်နိုင်တဲ့ infra — persistent queue workers, scheduler reliability, concurrent DB transactions, backups, monitoring, sync API traffic, large imports, error reporting

**Measurable upgrade triggers (ဒီထက်ကျော်ရင် VPS/infra ပြောင်းရမယ် — Owner နဲ့ ဆုံးဖြတ်):**
- Active tenants > N (ဥပမာ 25–50)
- Sync queue backlog / response time threshold ကျော်
- Peak concurrent POS/online requests ကျော်
- Import job runtime ကျော် / scheduler miss
- Storage/backup size ကျော်

(Exact thresholds က Phase 0 မှာ metric နဲ့တကွ သတ်မှတ်မယ်)

---

## ၂.၁၇ POS ရဲ့ အဓိက Modules (Revised)

1. POS Sales (Draft/Held/Posted/Return/Reversal) · 2. Cart + Barcode + Hold/Resume
3. Split Payments (Cash/KPay/WavePay/CB Pay/MMQR) · 4. Receipt + Reprint
5. Customer Credit/Debt · 6. Simple Receiving · 7. Opening Stock
8. Inventory Adjustments (manager approval) · 9. Cashier Shift + Daily Closing
10. Minimal Sales/Cash/Stock Reports · 11. Audit Trail
→ **Operations phase:** Full purchasing, purchase returns, supplier payables, stock transfers, stock counts, service jobs, expenses, finance ledger, advanced reports

Inventory ကို **shared ledger (immutable movements)** နဲ့ ထိန်းမယ် — `products.quantity` တစ်ခုတည်း မဟုတ်ဘူး (SoT §10)

---

## SoT Conflicts (✅ Approved 2026-08-10 + Applied — နှစ်ဖိုင်လုံးတွင် ပြင်ပြီး)

| # | SoT အခန်း | ဆန့်ကျင်ချက် | Applied amendment |
|---|---|---|---|
| 1 | SoT §5 (နှစ်ဖိုင်လုံး) — "Automatic Ecommerce Inventory Sync Out of Scope / Manual stock" | Ledger က POS+Ecommerce နှစ်ခုလုံးရဲ့ source of truth — adapter နဲ့ auto integrate | §5/§4 ပြင်ပြီး — manual maintain → ledger-derived · `online_reserve/confirm/cancel` movement types ထည့်ပြီး |
| 2 | SoT §28/§19 — Phase 2 Online POS တွင် debt/closing မပါ | Customer Debt + Cashier Shift + Daily Closing က Online POS MVP (Phase 2) ထဲ | Phase 2 scope ပြင်ပြီး — debt/closing ထည့်ပြီး၊ Phase 4 မှ ဖယ်ပြီး |
| 3 | SoT §28/§19 — AlinnThit pilot phase မရှိ၊ Local LAN edition phase မရှိ | Phase 2.5 Pilot + Phase 5 Local/Resale + Phase 6 packs ထည့်မယ် | Phase list ပြန်ရေးပြီး — Phase 0–6 အသစ် |
| 4 | SoT Open Decision #14/#15 (Price/Discount rules) | Money/rounding policy သတ်မှတ်ပြီး (၂.၆) | SoT မှာ Resolved အဖြစ် မှတ်ပြီး |
| 5 | SoT မှာ inventory valuation rule မရှိ | Weighted-average costing (၂.၇) | SoT §14.4 / §10.4 — Weighted-Average Costing rule ထည့်ပြီး |

---

## ၂.၁၈ ဆက်ဖတ်ရန်

- `ROADMAP.md` (Sales & Market Model section) — ဖောက်သည်တွေကို ဘယ်လို ရောင်းမလဲ
- `ROADMAP.md` — ဘယ်ကစ ဆောက်မလဲ
