# DataPOS — နောက် Conversation အသစ်အတွက် စတင်ရမည့် Message

> ဒီဖိုင်ကို ဖတ်ပြီး အောက်ဖော်ပြပါ **「 ပထမဆုံးပြောရမည့်စာသား 」** ကို ကူးယူပြီး Freebuff မှာ **Conversation အသစ်** ဖွင့်ကာ ပထမဆုံး message အဖြစ် paste လုပ်ပါ။
>
> ဒီဖိုင်ရဲ့ နောက်ဆုံးအခြေအနေနဲ့ ကိုက်ညီကြောင်း မသေချာရင် မပို့ခင် DataPOS ထဲက `git log --oneline -1` နဲ့ `git status --short` စစ်ပြီး အောက်ပါ commit hash / test အရေအတွက်တွေကို မွမ်းမံပါ။

---

## ပထမဆုံးပြောရမည့်စာသား (copy-paste ready)

```
ဒီ project က DataPOS ပါ — D:\xmapp\htdocs\DataPOS မှာ ရှိတဲ့ သီးခြား Laravel 12 project ဖြစ်ပြီး
လက်ရှိ alinnthit.com ecommerce (D:\xmapp\htdocs\data_ecommerce) နဲ့ လုံးဝ သီးခြားပါ။

အရင်ဆုံး ဒီဖိုင်တွေကို ဖတ်ပါ:
- DataPOS_Mobile_Offline_POS_Project_Source_of_Truth_MM.md  (POS စနစ်ရဲ့ စည်းမျဉ်းစာချုပ် — MUST READ)
- docs/pos-resale-plan/00-overview.md → 04-implementation-phases.md  (ခြုံငုံအစီအစဉ်)
- README.md  (run နည်း)

လက်ရှိအခြေအနေ:
- Git repo စတင်ပြီးပြီ — main branch, remote = https://github.com/shwepyithit568-commits/DataPOS.git
- Initial commit လုပ်ပြီး (commit 59976ee) push ပြီးပါပြီ
- Tests: 608 ခုလုံး pass (SQLite local)
- Local dev: DB_CONNECTION=sqlite, FORCE_HTTPS=false, port 8501 (php artisan serve)
- Brand: AlinnThit အားလုံး DataPOS အဖြစ် ပြောင်းပြီးပြီ (slug datapos-mobile)

ပထမဆုံး တာဝန်:
DataPOS_Mobile_Offline_POS_Project_Source_of_Truth_MM.md ထဲက Phase 0/1 foundation ကို စတင်ပါ —
Branch, Warehouse, Inventory ledger အခြေခံ models + migrations တည်ဆောက်ပါ။
SQLite နဲ့ MySQL နှစ်မျိုးလုံး compatible ဖြစ်အောင် ရေးပါ။
အရေးကြီး: ecommerce ရဲ့ orders/products ဇယားတွေကို ပြန်သုံး/ပြောင်း မလုပ်ပါနဲ့ — POS tables တွေက
သီးခြား add-only ဖြစ်ရမယ် (SoT §3, §4, §22 ကို လိုက်နာပါ)။
```

---

## နောက်ခံ အကျဉ်းချုပ် (ဆရာကြီးနဲ့ ဆွေးနွေးပြီးသား ဆုံးဖြတ်ချက်များ)

| အကြောင်း | ဆုံးဖြတ်ချက် |
|---|---|
| Codebase | **တစ်ခုတည်း** — DataPOS က main repo ကနေ သီးခြားကူးယူထားတဲ့ working copy၊ လွတ်လပ်စွာ ဆောက်မယ် |
| Module isolation | POS က `App\POS\...` namespace + `/pos` routes + သီးခြား SW/CSS/JS/tests — ecommerce code နဲ့ မရောနှောရ |
| Tables | POS tables သီးခြား add-only — `orders` table ကို POS sales အဖြစ် ပြန်မသုံးရ |
| Database | Cloud MySQL = central source of truth (Hostinger); offline devices = IndexedDB; local dev = SQLite |
| Hosting | Hostinger Unlimited shared hosting, MySQL ပါ (48-month) — Phase 4–5 ဆို VPS upgrade path |
| Resale | ဖောက်သည်တစ်ယောက် = store တစ်ခု; feature flags / capabilities နဲ့ POS / ecommerce module ရွေးဖွင့်နိုင် |
| Industry | Core က industry-agnostic (UOM, fractional qty, custom fields) — Gold Shop / Fuel / Restaurant packs က နောက်မှ |
| Offline | Periodic offline (SoT ဒီဇိုင်း) + Fully local mode (SQLite + LAN) နှစ်မျိုးလုံး |

## အရေးကြီး လမ်းညွှန်ချက်များ (နောက် conversation မှာ ထပ်ပြောစရာမလိုအောင်)

1. **DataPOS ထဲက `deploy-alinnthit.sh` ကို run မလုပ်ပါနဲ့** — ဒါက live site (alinnthit.com) ကို ညွှန်နေလို့ပါ။ DataPOS အတွက် deploy script အသစ် လိုအပ်ရင် `deploy-datapos.sh` လို သီးခြားရေးပါ။
2. `.env` မှာ `FORCE_HTTPS=false` — local HTTP dev အတွက်။ Production မှာတော့ `true` ပြန်ထားရမယ်။
3. Live site (data_ecommerce) ကို DataPOS အလုပ်တွေနဲ့ **မထိပါနဲ့** — သီးခြား repo နှစ်ခုပါ။
4. Migration ရေးတိုင်း SQLite + MySQL နှစ်မျိုးလုံး run ပြီး verify လုပ်ပါ (အခု suite က SQLite နဲ့ green ဖြစ်နေတယ်)။
