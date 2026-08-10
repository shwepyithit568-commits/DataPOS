# DataPOS POS + Resale Plan — ခြုံငုံဖတ်ရန်

> **ရည်ရွယ်ချက်:** ဒီဖိုဒါမှာ DataPOS ရဲ့ **Offline POS စနစ်** ကို ဘယ်လို တည်ဆောက်မယ်၊ ကိုယ့်ဆိုင်မှာ ဘယ်လို သုံးမယ်၊ ပြီးတော့ **အခြားလုပ်ငန်းရှင်တွေကို ပြန်ရောင်းချ** မယ့် ပုံစံတွေကို ရှင်းပြထားတဲ့ စာရွက်စာတမ်းတွေ ဖြစ်ပါတယ်။
>
> **ဖတ်ရမယ့်သူ:** Project Owner (ဆရာကြီး) — နားလည်ပြီး ဆွေးနွေးဖို့အတွက်
>
> **ရက်စွဲ:** 2026-08-10

---

## ဖိုင်တွေရဲ့ အကြောင်းအရာ

| ဖိုင် | အကြောင်းအရာ | ဘာတွေသိရမလဲ |
|---|---|---|
| `01-current-state.md` | **လုပ်ပြီးသား အခြေအနေ** | လက်ရှိ ပရောဂျက်မှာ ဘာတွေ ရှိပြီးသားလဲ — Ecommerce, multi-store, PWA, web push, admin စနစ် |
| `02-target-design.md` | **လိုချင်တဲ့ ပုံစံ** | POS စနစ်ကို ဘယ်လို ပုံစံနဲ့ တည်ဆောက်မလဲ — တစ်ခုတည်းသော codebase, module ခွဲထားမှု, deployment mode ၂ မျိုး |
| `03-sales-market-model.md` | **စျေးကွက်အလိုက် ရောင်းချ/ထိန်းချုပ်ပုံ** | ဖောက်သည်တွေကို ဘယ်လို ရောင်းမလဲ — POS-only / Ecommerce-only / Both, လုပ်ငန်းအမျိုးမျိုး (ရွှေဆိုင်/စားသောက်ဆိုင်/...) |
| `04-implementation-phases.md` | **တည်ဆောက်ရမယ့် အဆင့်ဆင့်** | ဘယ်ကစပြီး ဘယ်လို အဆင့်ဆင့် ဆောက်မလဲ — Phase 0 (Audit) ကနေ Phase 5 (Cutover) အထိ |

---

## အနှစ်ချုပ် — အဓိက ဆုံးဖြတ်ချက်တွေ

1. **Codebase တစ်ခုတည်း** — Ecommerce ရော POS ရော ဒီပရောဂျက်ထဲမှာပဲ ဆောက်မယ် (ပရောဂျက်သစ် သီးခြား မဆောက်ဘူး) — SoT §3.1 နဲ့ ကိုက်ညီ
2. **Module တွေ သီးခြားခွဲ** — POS code ကို `App\POS\...` အောက်မှာ ခွဲထားပြီး storefront ကို မထိခိုက်အောင် လုပ်မယ်
3. **Feature flags / Capabilities** — ဖောက်သည်တစ်ယောက်ချင်းစီအတွက် ဘယ် module ဖွင့်မလဲ ဆိုတာ ထိန်းချုပ်နိုင်မယ်
4. **Deployment mode ၂ မျိုး** — Cloud mode (MySQL + multi-branch) / Local mode (SQLite + offline-only)
5. **လုပ်ငန်းစုံ ထောက်ပံ့နိုင်အောင်** — Core ကို industry-agnostic (UOM, fractional qty, custom fields) ဖြစ်အောင် ဆောက်ပြီး industry pack တွေကို ဖောက်သည်ပေါ်မှ ဆောက်မယ်

---

## ဖတ်ရန် အစီအစဉ်

1. `01-current-state.md` ကစဖတ်ပါ (ဘာရှိပြီးသားလဲ သိအောင်)
2. `02-target-design.md` ဖတ်ပါ (ဘယ်ကို ဦးတည်နေလဲ သိအောင်)
3. `03-sales-market-model.md` ဖတ်ပါ (ဘယ်လို ရောင်းမလဲ သိအောင်)
4. `04-implementation-phases.md` ဖတ်ပါ (ဘယ်ကစမလဲ သိအောင်)

ပြီးရင် ဆရာကြီးနဲ့ ထပ်ဆွေးနွေးလို့ ရပါတယ်။

---

## ဆက်စပ်ဖိုင်များ

- `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` — POS စနစ်ရဲ့ အခြေခံ စည်းမျဉ်း (အရေးအကြီးဆုံး)
- `docs/multi-store-ready-plan.md` — multi-store admin စနစ် တိုးတက်မှု အစီအစဉ်
- `docs/deployment-runbook.md` — deploy လုပ်နည်း မှတ်တမ်း
