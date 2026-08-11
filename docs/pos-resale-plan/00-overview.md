# DataPOS POS + Resale Plan — ခြုံငုံဖတ်ရန်

> **ရည်ရွယ်ချက်:** ဒီဖိုဒါမှာ DataPOS ရဲ့ **POS စနစ်** ကို ဘယ်လို တည်ဆောက်မယ်၊ ကိုယ့်ဆိုင်မှာ ဘယ်လို သုံးမယ်၊ ပြီးတော့ **အခြားလုပ်ငန်းရှင်တွေကို ပြန်ရောင်းချ** မယ့် ပုံစံတွေကို ရှင်းပြထားတဲ့ စာရွက်စာတမ်းတွေ ဖြစ်ပါတယ်။
>
> **ဖတ်ရမယ့်သူ:** Project Owner (ဆရာကြီး) — နားလည်ပြီး ဆွေးနွေးဖို့အတွက်
>
> **ရက်စွဲ:** 2026-08-10 (Revision 2 — architecture corrections + MVP scope ပြင်ပြီး)

---

## ဖိုင်တွေရဲ့ အကြောင်းအရာ

| ဖိုင် | အကြောင်းအရာ | ဘာတွေသိရမလဲ |
|---|---|---|
| `01-current-state.md` | **လုပ်ပြီးသား အခြေအနေ** | လက်ရှိ ပရောဂျက်မှာ ဘာတွေ ရှိပြီးသားလဲ — Ecommerce, multi-store, PWA, web push, admin စနစ် (608 tests pass) |
| `02-target-design.md` | **လိုချင်တဲ့ ပုံစံ** | Architecture — တစ်ခုတည်းသော codebase, deployment model ၂ မျိုး (Cloud SaaS / Local install), shared inventory ledger, money/rounding policy, POS sale state machine, cashier shift |
| `03-sales-market-model.md` | **စျေးကွက်အလိုက် ရောင်းချ/ထိန်းချုပ်ပုံ** | ဖောက်သည်တွေကို ဘယ်လို ရောင်းမလဲ — SaaS tenant / Local install, module plan, license, Platform Owner support access |
| `04-implementation-phases.md` | **တည်ဆောက်ရမယ့် အဆင့်ဆင့်** | Phase 0 (Decisions) → Phase 1 (Foundation) → Phase 2 (Online POS MVP) → 2.5 (AlinnThit Pilot) → 3 (Cloud PWA offline) → 4 (Operations) → 5 (Local + Resale) → 6 (Industry packs) |

---

## အနှစ်ချုပ် — အဓိက ဆုံးဖြတ်ချက်တွေ (Revision 2)

1. **Codebase တစ်ခုတည်း** — Ecommerce ရော POS ရော ဒီပရောဂျက်ထဲမှာပဲ ဆောက်မယ် (SoT §4.1)။ Module isolation: `App\POS\...` + `/pos` routes + သီးခြား SW/CSS/JS/tests
2. **Deployment model ၂ မျိုး — တစ်ခုနဲ့တစ်ခု မရောရ:**
   - **Cloud ဖောက်သည် = Multi-tenant SaaS** — ဗဟို application တစ်ခုတည်း၊ store/tenant အများကြီး၊ တင်းကျပ်သော `store_id` isolation၊ store အလိုက် enabled modules၊ Platform Owner စီမံ၊ Store Owner က သူ့ဆိုင်ပဲ မြင်၊ custom domain နောက်မှ ထည့်နိုင်
   - **Local ဖောက်သည် = Single-tenant install** — ဖောက်သည်တစ်ယောက် installation တစ်ခု၊ Laravel + SQLite၊ ဆိုင်ထဲ PC/LAN၊ အမြဲ internet မလို၊ resale နောက်ပိုင်းမှ signed offline license၊ versioned backup/restore/update workflow
3. **Inventory ledger က POS ရော Ecommerce ရဲ့ တစ်ခုတည်းသော stock source of truth** — `inventory_movements` (immutable) + `inventory_balances` (derived cache)။ Ecommerce orders ကို adapter/service ကတဆင့် integrate — POS/Ecommerce နှစ်ခုလုံး တူညီတဲ့ stock ကို oversell မလုပ်နိုင်။ `products.stock_status` က migration ကာလအတွင်း derived compatibility/cache field အဖြစ်သာ ကျန်ရစ်မယ်
4. **Module/capability enforcement — static routes + server-side middleware** — tenant ပေါ်မူတည်ပြီး route တွေကို conditionally register **မလုပ်ရ** (route caching နဲ့ မကိုက်ညီလို့)။ Module enabled → branch capability → user permission → approval permission — အဆင့် ၄ မျိုး သီးခြား ခွဲထားပြီး server-side က authoritative (UI hide မလုံလောက်)
5. **Money/quantity — float မသုံးရ** — MMK ကို integer (ကျပ်) သို့မဟုတ် decimal ဖြင့် သိမ်း၊ discount/tax rounding order သတ်မှတ်၊ weighted-average costing (ကနဦး Mobile/Electronics MVP)၊ negative stock default ပိတ်
6. **MVP scope ပြင်ဆင်** — Customer Debt + Cashier Shift + Daily Closing တွေကို နောက်ကျ Operations phase ထဲ မထားတော့ဘူး — **Online POS MVP (Phase 2) ထဲ ထည့်မယ်** (မြန်မာ့ဈေးကွက်ရဲ့ မဖြစ်မနေ selling feature)
7. **Offline system ၂ မျိုး သီးခြားခွဲ** — (a) Cloud PWA offline queue (IndexedDB + sync API) နဲ့ (b) Local LAN/SQLite install — phase တစ်ခုထဲ မရော။ Order: Online Cloud POS → AlinnThit Pilot → Cloud PWA offline → Local LAN edition → cloud-to-local sync (demand ရှိမှ)
8. **AlinnThit production pilot က resale မလုပ်ခင် မဖြစ်မနေ** — real data, parallel validation, reconciliation, real cashier usage, backup/restore test — pilot မတည်မငြိမ်ခင် ပြင်ပဖောက်သည်ကို မရောင်းရ
9. **ပထမဆုံး product = Mobile/Electronics POS** — SKU/barcode, variants, serial/IMEI, warranty, retail/wholesale, customer debt, receiving, branch/warehouse inventory, returns/exchanges — ကျန် industry packs (ဆေး/ကုန်စုံ/ရွှေ/စားသောက်ဆိုင်/ဓာတ်ဆီ/အဝတ်အထည်) က ဖောက်သည်အစစ် ပေါ်လာမှ
10. **Platform Owner support access ကို explicit workflow နဲ့** — Store Support Mode: reason + start/end time + write အကုန် audit + active store ကို ရှင်းရှင်း ပြ + accidental cross-store write ကာကွယ် + finance/export တွင် ပိုတင်းကျပ်

---

## ဖတ်ရန် အစီအစဉ်

1. `01-current-state.md` ကစဖတ်ပါ (ဘာရှိပြီးသားလဲ သိအောင်)
2. `02-target-design.md` ဖတ်ပါ (ဘယ်ကို ဦးတည်နေလဲ သိအောင်)
3. `03-sales-market-model.md` ဖတ်ပါ (ဘယ်လို ရောင်းမလဲ သိအောင်)
4. `04-implementation-phases.md` ဖတ်ပါ (ဘယ်ကစမလဲ သိအောင်)

---

## ဆက်စပ်ဖိုင်များ

- `Source_of_Truth_MM.md` / `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` — POS စနစ်ရဲ့ အခြေခံ စည်းမျဉ်း (အရေးအကြီးဆုံး — ဒီပြင်ဆင်ချက်တွေနဲ့ ဆန့်ကျင်နေတဲ့ အခန်းတွေ ရှိနေလို့ amendment လိုအပ်မယ်)
- `docs/multi-store-ready-plan.md` — multi-store admin စနစ် တိုးတက်မှု အစီအစဉ်
- `docs/deployment-runbook.md` — deploy လုပ်နည်း မှတ်တမ်း
- `docs/backup-strategy.md` — backup/restore မှတ်တမ်း (Local edition အတွက် versioned workflow နဲ့ ချိတ်ဆက်)
