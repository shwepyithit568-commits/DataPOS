# DataPOS

> **ဒီဖိုဒါက ဘာလဲ:** လက်ရှိ DataPOS Ecommerce (datapos.com — live) ရဲ့ codebase တစ်ခုလုံးကို ကူးယူထားတဲ့ **သီးခြား POS ပရောဂျက်** ဖြစ်ပါတယ်။
>
> **ရည်ရွယ်ချက်:** လက်ရှိ website ကို hosting ပေါ်မှာ ဆက်သုံးနေစဉ် — ဒီဖိုဒါထဲမှာ **Offline-first POS + resale စနစ်** ကို သီးခြား တည်ဆောက်ပါမယ်။
>
> **ကူးယူသည့်ရက်စွဲ:** 2026-08-10 · **အခြေခံ:** Laravel 12.64 (source: `data_ecommerce` main project)

---

## ⚠️ အရေးကြီး သတိပေးချက်

- **ဒီဖိုဒါက main project (လက်ရှိ live site) နဲ့ လုံးဝ သီးခြားပါ။** ဒီထဲက အပြောင်းအလဲတွေက datapos.com ကို မထိခိုက်ပါဘူး။
- `deploy-datapos.sh` က ကူးထည့်ပါတယ် — **ဒါပေမဲ့ ဒီဖိုဒါကနေ run လုပ်ရင် live site ပေါ် deploy ဖြစ်နိုင်လို့ မလုပ်ပါနဲ့!** DataPOS အတွက် deploy script အသစ် သီးခြား ရေးရမယ်။
- `.env` ကို **အသစ်** ဖန်တီးထားပါတယ် (SQLite, fresh APP_KEY) — production secrets မပါပါဘူး။

---

## Local မှာ run နည်း

```bash
cd DataPOS
D:/xmapp/php/php.exe artisan serve --port=8501
# → http://127.0.0.1:8501
```

ဒေတာဘေ့စ်: SQLite (`database/database.sqlite`) — migrations အားလုံး run ပြီးသား ✅

## ဖွဲ့စည်းပုံ မှတ်စု

- **လက်ရှိ ကုဒ်:** ဒီထဲမှာ ရှိသမျှက Ecommerce codebase ရဲ့ မိတ္တူပါ။ POS module (`App\POS\...`, `/pos` routes) ကို ဒီကနေ စတင် တည်ဆောက်ရမယ်။
- **ရည်ညွှန်း:** `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` (အခြေခံစည်းမျဉ်း) + `docs/pos-resale-plan/` (ဆောက်မယ့်ပုံစံ စာရွက်စာတမ်း)
- **ဘာတွေ မပါဘူး:** `.git`, `.env` (original), `.freebuff`, `node_modules`/`vendor` (ပါတယ် — 204MB copy), storage runtime files, test data

## Git မှတ်စု

DataPOS က သီးခြား git repo ဖြစ်နေပြီ — `main` branch, remote = `https://github.com/shwepyithit568-commits/DataPOS.git`။
Documentation အပြည့်အစုံ: `docs/README.md` (index) — changelog `2026-08-02_FIXES.md` · rules `Source_of_Truth_MM.md` + `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` · testing `Testing_check.md`။
