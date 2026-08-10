# ၂။ လိုချင်တဲ့ ပုံစံ (Target Design)

> **ဒီဖိုင်မှာ:** POS စနစ်ကို ဘယ်လို ပုံစံနဲ့ တည်ဆောက်မလဲ — architecture, module ခွဲထားမှု, deployment mode ၂ မျိုး။
>
> **အခြေခံ:** `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` (SoT) — ဒါက ဒီဖိုင်ရဲ့ အနှစ်ချုပ် ပုံစံပါ

---

## ၂.၁ အဓိက ဆုံးဖြတ်ချက်: **တစ်ခုတည်းသော codebase**

Ecommerce ရော POS ရော **ဒီပရောဂျက်ထဲမှာပဲ** ဆောက်မယ် — ပရောဂျက်အသစ် သီးခြား မဆောက်ဘူး (SoT §3.1)။

**ဘာကြောင့်လဲ:**
- POS က catalog (products/SKU/prices) ကို ဒီထဲကပဲ ယူသုံးရမယ်
- Staff/auth/store isolation က ရှိပြီးသား
- Deploy pipeline တစ်ခုတည်း
- ဖောက်သည်တစ်ယောက် = `stores` table ထဲ row တစ်ခု

---

## ၂.၂ Module ခွဲထားမှု (အရေးအကြီးဆုံး)

တစ်ခုတည်းထဲ ဆောက်ပေမဲ့ **POS ကို တင်းကျပ်တဲ့ module** အနေနဲ့ ခွဲထားရမယ် — "ဟိုဟာလို ဒီဟာလို" မဖြစ်အောင်:

| အပိုင်း | စည်းမျဉ်း |
|---|---|
| Namespace | `App\POS\...` (Controller/Model/Service အကုန်) — ecommerce code နဲ့ မရောနှောရ |
| Tables | POS tables သီးခြား (`branches`, `warehouses`, `inventory_movements`, `sales`, ...) — ecommerce `orders` ကို ပြန်မသုံးရ |
| Routes | `/pos` + `/pos/admin` — route group သီးခြား + middleware သီးခြား |
| Service Worker | `/pos/sw.js` (scope: `/pos/`) — storefront `/sw.js` နဲ့ မရောနှောရ |
| CSS/JS | `pos.css` / `pos.js` — Vite entry သီးခြား |
| Tests | `tests/Feature/POS/...` — သီးခြား directory |
| Migration | POS migrations က add-only — ecommerce tables ကို မပြောင်း |
| Catalog share | POS က products ကို **read-only** share — inventory ကို POS ledger နဲ့ ထိန်း |

---

## ၂.၃ Deployment Mode ၂ မျိုး (ဖောက်သည်အလိုက်)

တစ်ခုတည်းသော codebase ကို mode ၂ မျိုးနဲ့ install လုပ်လို့ရမယ်:

```
SAME codebase
 ├── Mode A: Cloud mode (ပုံမှန်)
 │     · MySQL (central) + multi-branch sync
 │     · ဆိုင်ခွဲအများကြီး၊ internet ရှိ/မရှိ ကြားထဲ sync
 │
 └── Mode B: Local mode (offline-only)
       · SQLite + LAN (ဆိုင်ထဲ Wi-Fi)
       · ဆိုင်တစ်ခုတည်း — internet လုံးဝမလို
       · Backup → USB file → Restore (php artisan command)
       · Offline license key (phone-home မလို)
```

**ဒီအတွက် ဆောက်ရမယ့် အခြေခံ:**
1. DB driver abstraction — Eloquent ကတဆင့်ပဲ (ရှိပြီးသား ✅)
2. `config('pos.mode')` = `cloud` | `local`
3. Local mode မှာ sync/queue စနစ်တွေ auto-skip
4. Backup/Restore artisan commands
5. Offline license activation

---

## ၂.၄ Feature Flags / Capabilities (ဖောက်သည်အလိုက် ဖွင့်ပိတ်)

ဖောက်သည်တစ်ယောက်ချင်းစီမှာ ဘယ် module ဖွင့်လဲ ထိန်းချုပ်နိုင်ရမယ် (SoT §5, §9):

```
Store settings ထဲ enabled_modules:
  ["pos", "ecommerce", "service", "inventory", "finance", ...]
```

| ဖောက်သည် | ဖွင့်ထားတဲ့ module | မြင်ရမယ့်အရာ |
|---|---|---|
| POS ပဲလိုတဲ့သူ | `pos` + sub-modules | `/pos` ပဲ — storefront မပေါ် |
| Ecommerce ပဲလိုတဲ့သူ | `ecommerce` | လက်ရှိအတိုင်း — POS မပေါ် |
| နှစ်ခုလုံးလိုတဲ့သူ | အကုန် | အကုန် |

**အကောင်အထည်ဖော်နည်း:**
- Route registration မှာ conditional (feature off → route 404)
- Sidebar/menu မှာ conditional
- **Server-side မှာ သေချာ enforce** — UI hide တစ်ခုတည်း မလုံလောက် (SoT §5)
- Branch level capabilities (ဆိုင်ခွဲတစ်ခုချင်းစီ) က Phase 1 မှာ

---

## ၂.၅ လုပ်ငန်းစုံ ထောက်ပံ့မှု (Industry-ready Core)

Core engine ကို **industry-agnostic** ဖြစ်အောင် ဆောက်ရမယ် — ဒါမှ ရွှေဆိုင်/စားသောက်ဆိုင်/ဓာတ်ဆီဆိုင်/ကုန်စုံဆိုင် အကုန် သုံးလို့ရမယ်:

### Core ထဲ ထည့်ရမယ့် (လုပ်ငန်းတိုင်း လို)
1. **UOM (Unit of Measure)** — `piece / kg / liter / ကျပ်-ပဲ-ရွေး` — product တိုင်းမှာ unit သတ်မှတ်နိုင်
2. **Fractional quantity** — 0.5kg, 2.5L — sale line မှာ decimal qty
3. **Custom fields** — လုပ်ငန်းအလိုက် extra data (karat, fuel grade, table no.) flexible ထည့်နိုင်
4. **Multi-currency** — Ks + USD/CNY/THB (နယ်စပ်ကုန်သွယ်မှု) — exchange rate tracking
5. **Serial number + Warranty tracking** — ပြန်လဲ/အာမခံ claims အတွက်
6. **Expiry date** — ဆေး/ကုန်စုံအတွက်
7. **Receipt layout config** — ဆိုင်အလိုက် receipt ပုံစံ

### Industry Pack (ဖောက်သည်ပေါ်မှ ဆောက်မယ်)
- Grocery Pack: expiry + weight scale + multi-unit
- Gold Shop Pack: weight pricing + daily rate + karat
- Fuel Station Pack: liter + fuel grade
- Restaurant Pack: tables + KOT + combos

> **အရေးကြီး:** Industry pack တွေကို အခုကတည်းက မဆောက်နဲ့ — over-engineering ဖြစ်မယ်။ Core ကို industry-agnostic ဖြစ်အောင်ပဲ ဆောက်ပြီး pack တွေက ဖောက်သည်အစစ် ပေါ်လာမှ ဆောက်ပါ။

---

## ၂.၆ POS ရဲ့ အဓိက Modules (SoT အတိုင်း)

1. POS Sales · 2. Purchases/Receiving · 3. Purchase Returns · 4. Sales Returns · 5. Exchanges
6. Inventory Adjustments · 7. Stock Transfers · 8. Stock Counts · 9. Service Jobs
10. Customer Receivables · 11. Supplier Payables · 12. Finance · 13. Daily Closing
14. Audit Logs · 15. Reports

Inventory ကို **ledger (immutable movements)** နဲ့ ထိန်းမယ် — `products.quantity` တစ်ခုတည်း မဟုတ်ဘူး (SoT §10)

---

## ၂.၇ ဆက်ဖတ်ရန်

- `03-sales-market-model.md` — ဖောက်သည်တွေကို ဘယ်လို ရောင်းမလဲ
- `04-implementation-phases.md` — ဘယ်ကစ ဆောက်မလဲ
