# ၃။ စျေးကွက်အလိုက် ရောင်းချ/ထိန်းချုပ်ပုံ (Sales & Market Model)

> **ဒီဖိုင်မှာ:** ဒီစနစ်ကို ဖောက်သည်တွေဆီ ဘယ်လို ရောင်းမလဲ — ဘယ်သူတွေ ဝယ်မလဲ၊ ဘယ်လို ထိန်းချုပ်မလဲ၊ စျေးနှုန်း ဘယ်လို သတ်မှတ်မလဲ။
>
> **Revision 2 (2026-08-10):** Deployment model ၂ မျိုး (Cloud SaaS / Local install) ကို ရှင်းရှင်းခွဲပြီး — "cloud ဖောက်သည်တိုင်းအတွက် သီးခြား deploy" ဆိုတဲ့ အဟောင်းပုံစံကို ဖျက်လိုက်ပြီ။

---

## ၃.၁ ဖောက်သည်အမျိုးအစား — Deployment model နဲ့ module ပေါင်းစပ်

| ဖောက်သည် | Deployment model | Module ဖွင့်ထားမယ့်အရာ |
|---|---|---|
| **Online ပဲလိုတဲ့သူ** | Cloud SaaS (tenant row) | `ecommerce` — လက်ရှိ storefront အတိုင်း |
| **POS ပဲလိုတဲ့သူ (internet ရှိ)** | Cloud SaaS (tenant row) | `pos` + sub-modules |
| **နှစ်ခုလုံးလိုတဲ့သူ** | Cloud SaaS (tenant row) | `pos` + `ecommerce` + shared inventory |
| **Offline ပဲလိုတဲ့သူ (ဆိုင်ထဲ PC)** | **Local single-tenant install** | `pos` + sub-modules — SQLite, LAN |

> ဖောက်သည်တစ်ယောက် = Cloud မှာ tenant row တစ်ခု (သို့) Local install တစ်ခု။ "Cloud ဖောက်သည်တစ်ယောက်စီအတွက် သီးခြား server/deploy" မလုပ်ရ။

---

## ၃.၂ လုပ်ငန်းအမျိုးအစား (Industry) — Mobile/Electronics က ပထမဆုံး

| လုပ်ငန်း | ကိုက်ညီမှု | Pack / Extension |
|---|---|---|
| **ဖုန်းဆိုင် / အီလက်ထရွန်းနစ်** | ✅ **ပထမဆုံး product** (ကိုယ့်ဆိုင်) | Serial/IMEI + warranty + service jobs (နောက်ပိုင်း) |
| **ဆေးဆိုင်** | 🔜 နောက်ပိုင်း | Expiry + batch/lot — demand ပေါ်မှ |
| **ရွှေဆိုင် / ဂျူးရတနာ** | 🔜 နောက်ပိုင်း | Weight pricing (ကျပ်/ပဲ/ရွေး) + daily rate + karat |
| **ကုန်စုံဆိုင်** | 🔜 နောက်ပိုင်း | Expiry + weight scale + multi-unit |
| **စားသောက်ဆိုင်** | 🔜 နောက်ပိုင်း | Table + KOT + combo |
| **ဓာတ်ဆီဆိုင်** | 🔜 နောက်ပိုင်း | Liter qty + fuel grade + pump |
| **အဝတ်အထည်** | 🔜 နောက်ပိုင်း | Size/color matrix + seasonal |
| **ပွဲရုံ / ခန်းမ** | ❌ အနည်းဆုံး | Booking/calendar module — သီးခြား လိုမယ် |

> **မူ:** Architecture က forward-compatible (UOM, decimal qty, custom fields) — ဒါပေမဲ့ pack တွေက **ဖောက်သည်အစစ် ပေါ်လာမှသာ** ဆောက်မယ်။ Pharmacy/Grocery/Gold/Restaurant/Fuel/Fashion ကို first-release မှာ မထည့်ရ။

---

## ၃.၃ ရောင်းချ/Install ပုံစံ — Model အလိုက်

### Cloud SaaS ဖောက်သည်အသစ် (Model A)

```
ဖောက်သည်အသစ် ရောက်လာရင်:
1. ဗဟို SaaS app ထဲမှာ php artisan store:create  (name, slug, plan)
2. enabled_modules သတ်မှတ် (pos / ecommerce / both)
3. Store owner account ဖန်တီး
4. ဆိုင်ဒေတာ ထည့်သွင်း (products import, branches → default branch/warehouse auto)
5. Deploy မလို — tenant row တစ်ခုပဲ
```

### Local install ဖောက်သည် (Model B)

```
ဖောက်သည်အသစ် ရောက်လာရင်:
1. Install package (Laravel + SQLite) ကို ဆိုင်ထဲ PC ပေါ် install
2. Setup wizard — store name, admin account, default branch/warehouse
3. License activation — resale နောက်ပိုင်းတွင် signed offline license
4. ဆိုင်ဒေတာ ထည့်သွင်း
5. Update/backup → versioned workflow (02-target-design §2.15)
```

### Install mode ရွေးစရာ

| Mode | ဘယ်သူ့အတွက် | Server ဘယ်မှာ | Internet |
|---|---|---|---|
| **Cloud (multi-tenant SaaS)** | internet ရှိတဲ့သူ, online လိုသူ | ဗဟို cloud app တစ်ခု (Hostinger...) | လို |
| **Local (Windows PC / LAN)** | offline ပဲလိုတဲ့သူ | ဆိုင်ထဲက PC — SQLite + LAN | မလို |

---

## ၃.၄ License / ထိန်းချုပ်မှု ပုံစံ

| အပိုင်း | Cloud mode | Local mode |
|---|---|---|
| License check | Online activation (server ကို မေး) | **Signed offline license** (public-key verify — private key ကို install ထဲ မထည့်ရ) — Resale Readiness (Phase 5) |
| Update | တစ်ခါ deploy → tenant အကုန် | Versioned update workflow — ဆိုင်တစ်ဆိုင်ချင်းစီ |
| Backup | Central daily + runbook | `php artisan backup` → versioned (snapshot + checksum + manifest) |
| Branch ပေါင်း | Multi-tenant app ထဲပဲ — branch capability ဖွင့် | Manual (versioned restore) သို့မဟုတ် single-branch သာ |

### License plan အဆင့် (ဥပမာ — ဆရာကြီး ဆုံးဖြတ်ရမယ်)

| Plan | ပါဝင်မှု | သင့်တော်တဲ့သူ |
|---|---|---|
| POS Basic | POS + inventory + single branch | ဆိုင်ငယ် |
| POS Pro | POS + debt + finance + daily closing | ဆိုင်လတ် |
| Ecommerce | Online storefront + orders | Online ပဲလိုသူ |
| Complete | အကုန် — multi-branch | ဆိုင်ကြီး / franchise |

> **အရေးကြီး:** Plan တွေက codebase မပြောင်း — `enabled_modules` ပဲ ပြောင်းတယ်။ Upgrade ဆိုရင် flag ဖွင့်ပေးရုံပဲ။

---

## ၃.၅ Platform Owner vs Store Owner — Support Access Workflow

- **Platform Owner (ဆရာကြီး):** SaaS app တစ်ခုလုံးကို စီမံ — plan, module flags, support
- **Store Owner (ဖောက်သည်):** သူ့ဆိုင်တစ်ခုပဲ — staff, products, POS

**Platform Owner က store ထဲ ဝင်ရမယ်ဆိုရင် — Store Support Mode ကိုသာ သုံးရမယ် (02-target-design §2.13):**

1. Enter Support Mode — **reason ရိုက်ရမယ်**
2. Start/end time record
3. **Write အကုန် audit** (actor, store, entity, before/after)
4. **Active store ကို ရှင်းရှင်း ပြ** (banner)
5. Accidental cross-store write ကာကွယ် (context lock)
6. Finance/export တွင် ပိုတင်းကျပ်
7. Store owner က သင့်တော်ရာ support activity ကို မြင်နိုင်

> Platform Owner ကို "store အကုန် invisible ဝင်လို့ရတယ်" ဆိုတဲ့ unrestricted access **မပေးရ** — SoT §6 store isolation ကို မချိုးရ။ Support session ကလွဲရင် ပုံမှန် query တွေက store-scoped ဖြစ်ရမယ်။

---

## ၃.၆ မြန်မာနိုင်ငံအတွက် အရေးကြီး feature (MVP ထဲ ပါ)

1. **ကြွေးစာရင်း (Credit/Debt)** — မြန်မာ့ဆိုင်တွေရဲ့ စာရင်းစာအုပ်ကို digitize — **MVP (Phase 2) ထဲ ပါ**
2. **Cashier shift + Daily closing** — expected vs actual cash — **MVP (Phase 2) ထဲ ပါ**
3. **Barcode/HID scanner + Split payments** (Cash/KPay/WavePay/CB Pay/MMQR) — MVP
4. **Warranty/Serial tracking** — ပြန်လဲ အများကြီးဖြစ်တဲ့အတွက် — Mobile/Electronics MVP
5. **Burmese language** — UI + receipt နှစ်မျိုးလုံး

---

## ၃.၇ ဆက်ဖတ်ရန်

- `04-implementation-phases.md` — ဒါတွေကို ဘယ်အချိန် ဘယ်လို ဆောက်မလဲ
