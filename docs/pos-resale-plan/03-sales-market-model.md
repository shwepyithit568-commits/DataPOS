# ၃။ စျေးကွက်အလိုက် ရောင်းချ/ထိန်းချုပ်ပုံ (Sales & Market Model)

> **ဒီဖိုင်မှာ:** ဒီစနစ်ကို ဖောက်သည်တွေဆီ ဘယ်လို ရောင်းမလဲ — ဘယ်သူတွေ ဝယ်မလဲ၊ ဘယ်လို ထိန်းချုပ်မလဲ၊ စျေးနှုန်း ဘယ်လို သတ်မှတ်မလဲ။

---

## ၃.၁ ဖောက်သည်အမျိုးအစား ၃ မျိုး

| ဖောက်သည် | ဘာလိုလဲ | ဒီစနစ်နဲ့ ဘယ်လို ကိုက်လဲ |
|---|---|---|
| **POS ပဲလိုတဲ့သူ** | offline POS + inventory + debt + finance | `pos` module ဖွင့်, `ecommerce` ပိတ် — Local mode ဖြစ်နိုင်တယ် |
| **Ecommerce ပဲလိုတဲ့သူ** | online website + order | လက်ရှိ storefront အတိုင်း — POS မပါ |
| **နှစ်ခုလုံးလိုတဲ့သူ** | online + offline ပေါင်း | module အကုန်ဖွင့် — Cloud mode |

---

## ၃.၂ လုပ်ငန်းအမျိုးအစား (Industry) အလိုက် အလားအလာ

| လုပ်ငန်း | Core နဲ့ ကိုက်ညီမှု | ထပ်လိုတဲ့ pack |
|---|---|---|
| **ဖုန်းဆိုင် / အီလက်ထရွန်းနစ်** | ✅ အကောင်းဆုံး (ကိုယ့်ဆိုင်) | Service module (ပြုပြင်ရေး) |
| **ဆေးဆိုင်** | ✅ ကောင်း | Expiry + batch/lot tracking |
| **ရွှေဆိုင် / ဂျူးရတနာ** | ✅ ကောင်း | Weight pricing (ကျပ်/ပဲ/ရွေး) + daily rate + karat |
| **ကားဆိုင်ကယ်အပိုပစ္စည်း** | ✅ ကောင်း | Vehicle-model matching |
| **ကုန်စုံဆိုင်** | ✅ ကောင်း | Expiry + weight scale + multi-unit |
| **အိမ်သုံးလျှပ်စစ် / ဆိုလာ** | ✅ ကောင်း | Serial + warranty tracking |
| **အဝတ်အထည်** | ⚠️ အလယ်အလတ် | Size/color matrix + seasonal |
| **အိမ်ဆောက်ပစ္စည်း** | ⚠️ အလယ်အလတ် | UOM (အိတ်/တန်း) + project credit |
| **စားသောက်ဆိုင်** | ⚠️ Core ရတယ် | Table + KOT + combo module |
| **ဓာတ်ဆီဆိုင်** | ⚠️ Core ရတယ် | Liter qty + fuel grade + pump |
| **ပွဲရုံ / ခန်းမ** | ❌ အနည်းဆုံး | Booking/calendar module — သီးခြား လိုမယ် |

---

## ၃.၃ ရောင်းချနည်း (ဘယ်လို install လုပ်ပေးမလဲ)

### ဖောက်သည်တစ်ယောက်အတွက် install ပုံစံ

```
ဖောက်သည်အသစ် ရောက်လာရင်:
1. Domain/Subdomain တစ်ခု သတ်မှတ် (ဒါမှမဟုတ် local PC)
2. Codebase ကို deploy (deploy script — ဆိုဒ်တစ်ခုချင်းစီ စံသတ်မှတ်ထား)
3. php artisan store:create  (store name, slug, plan)
4. enabled_modules သတ်မှတ် (POS / Ecommerce / Both)
5. Admin account ဖန်တီး + license activate
6. ဆိုင်ဒေတာ ထည့်သွင်း (products import, branches, users)
```

### Install mode ရွေးစရာ

| Mode | ဘယ်သူ့အတွက် | Server ဘယ်မှာ |
|---|---|---|
| **Cloud (shared hosting)** | internet ရှိတဲ့သူ, multi-branch | Hostinger လို hosting — ကိုယ့်အကောင့်နဲ့ သော်လည်းကောင်း |
| **Local (Windows PC)** | offline ပဲလိုတဲ့သူ | ဆိုင်ထဲက PC — SQLite + LAN |

---

## ၃.၄ License / ထိန်းချုပ်မှု ပုံစံ

| အပိုင်း | Cloud mode | Local mode |
|---|---|---|
| License check | Online activation (server ကို မေး) | **Offline license key** (file/code — phone-home မလို) |
| Update | Deploy script နဲ့ အဝေး | USB/လက်နဲ့ — ဆိုင်မှာ install |
| Backup | Hostinger daily + runbook အတိုင်း | `php artisan backup` → USB |
| Branch ပေါင်း | Auto sync | Manual (USB transfer) သို့မဟုတ် single-branch သာ |

### License plan အဆင့် (ဥပမာ — ဆရာကြီး ဆုံးဖြတ်ရမယ်)

| Plan | ပါဝင်မှု | သင့်တော်တဲ့သူ |
|---|---|---|
| POS Basic | POS + inventory + single branch | ဆိုင်ငယ် |
| POS Pro | POS + debt + finance + daily closing | ဆိုင်လတ် |
| Ecommerce | Online storefront + orders | Online ပဲလိုသူ |
| Complete | အကုန် — Cloud mode multi-branch | ဆိုင်ကြီး / franchise |

> **အရေးကြီး:** Plan တွေက codebase မပြောင်း — `enabled_modules` ပဲ ပြောင်းတယ်။ ဖောက်သည်က upgrade လိုချင်ရင် flag တစ်ခုဖွင့်ပေးရုံပဲ → ဒါက အကောင်းဆုံး selling point။

---

## ၃.၅ Platform Owner vs Store Owner

- **Platform Owner (ဆရာကြီး):** store အားလုံးကို ထိန်း — plan သတ်မှတ်, license ပေး, ပြဿနာ ဖြေရှင်း
- **Store Owner (ဖောက်သည်):** သူ့ဆိုင်တစ်ခုပဲ — staff, products, POS အကုန် ထိန်း

ဒီနှစ်ခုရဲ့ UI နဲ့ permission ကို သီးခြားခွဲထားရမယ် — platform owner က store တိုင်းရဲ့ အထဲထိ ဝင်လို့ရမယ်၊ store owner က သူ့ဆိုင်ပဲ မြင်ရမယ်။

---

## ၃.၆ မြန်မာနိုင်ငံအတွက် အရေးကြီး feature (ရောင်းရဖို့ မဖြစ်မနေ)

1. **ကြွေးစာရင်း (Credit/Debt)** — မြန်မာ့ဆိုင်တွေရဲ့ စာရင်းစာအုပ်ကို digitize — အရေးအကြီးဆုံး
2. **UOM (ကျပ်/ပဲ/ရွေး, kg, liter)** — ရွှေဆိုင်/ဈေးဆိုင်
3. **Multi-currency** — နယ်စပ်ကုန်သွယ် (Muse/Myawaddy)
4. **Warranty/Serial tracking** — ပြန်လဲ အများကြီးဖြစ်တဲ့အတွက်
5. **Burmese language** — UI + receipt နှစ်မျိုးလုံး

---

## ၃.၇ ဆက်ဖတ်ရန်

- `04-implementation-phases.md` — ဒါတွေကို ဘယ်အချိန် ဘယ်လို ဆောက်မလဲ
