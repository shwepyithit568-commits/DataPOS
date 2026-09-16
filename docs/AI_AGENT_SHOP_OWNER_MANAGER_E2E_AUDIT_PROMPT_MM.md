# DataPOS — ဆိုင်ရှင်/မန်နေဂျာအတွက် End-to-End စစ်ဆေးမှု Prompt (မြန်မာ)

ဒီစာရွက်က **လုပ်ငန်းရှင် သို့မဟုတ် ဆိုင်မန်နေဂျာ** တစ်ဦးက AI agent (ZCode / Claude Code / Codex စသည်) ကို ခေါ်ပြီး
လက်ရှိ DataPOS စနစ်ကို **အစအဆုံး စစ်ဆေးခိုင်းနိုင်** အောင် ရေးထားတာပါ။ နည်းပညာမကျွမ်းကျင်လည်း သုံးနိုင်သည်။

> ဒီထဲက စစ်ဆေးနည်းတွေက ၂၀၂၆-၀၉-၁၇ ရက် production-အကြို audit မှာ **တကယ် run ပြီး ပြဿနာ ၈ ခု တွေ့ခဲ့**တဲ့
> နည်းလမ်းတွေပါ။ "နည်းလမ်း ၁၀ ချက်" လို ယေဘုယျ ဆောင်းပါးမဟုတ်ဘဲ၊ ဒီ project မှာ တကယ် လွဲနေတဲ့ နေရာတွေကို
> ရှာနိုင်တဲ့ စစ်ဆေးချက်တွေ ဖြစ်သည်။

---

## ၀။ ဘယ်အချိန် စစ်ရမလဲ

| အခါ | ဘယ်အပိုင်း စစ်ရမလဲ |
|---|---|
| **Hosting တင်မည့်အခါ** | အပိုင်း ၁–၅ အားလုံး (မဖြစ်မနေ) |
| Deploy တစ်ခါ လုပ်ပြီးတိုင်း | အပိုင်း ၁ (automated) + အပိုင်း ၂ (security) |
| လစဉ် | အပိုင်း ၁, ၃, ၆ |
| Cashier အသစ် သင်ပြီးတိုင်း | အပိုင်း ၇ (လက်တွေ့ လုပ်ငန်းစဉ်) |

**ဘယ်တော့မှ မလုပ်ရ:** Test suite အစိမ်းရောင် (green) ဖြစ်တာနဲ့ production တင်လို့ရပြီ လို့ မယူဆရ။
၂၀၂၆-၀၉-၁၇ ရက် audit မှာ **test ၁၉၀၀ အောင်နေရက်နဲ့** အရေးကြီးဆုံး ပြဿနာတွေ (မိမိ `.env` ဖိုင်
download ရနေတာ၊ offline API မှာ auth လုံးဝမရှိတာ၊ MySQL ပေါ်မှာ migrate လုံးဝကျမှာ) လွတ်နေခဲ့တယ်။

---

## ၁။ အလွယ်ဆုံး အဆင့် — Automated Gates

AI agent ကို ဒီစာသား ကူးထည့်ပြီး ခိုင်းပါ:

```text
DataPOS ကို production-အကြို စစ်ဆေးပါ။ အောက်ပါတာ ၃ ခုကို run ပြီး ရလဒ်အမှန် တင်ပြပါ။
ကျတဲ့ test ကို ဘာကြောင့်ကျလဲ ရှင်းပြပါ။ ဘာမှ မပြင်ပါနဲ့၊ တင်ပြရုံပါ။

1. php artisan test
2. php artisan test tests/Feature/MysqlMigrationSmokeTest.php
3. npm run build
```

**ဖတ်ရမည့်အချက် ၃ ချက်**

- **test အရေအတွက်** — ပထမစစ်တုန်းနဲ့ ကွာရင် "ဘာကြောင့် ကွာသလဲ" လို့ မေးပါ။
- **"skipped"** — အစိမ်းရောင်ပြနေပေမယ့် skip ဖြစ်နေတဲ့ test ရှိသည်။ MySQL test က MySQL မဖွင့်ထားရင်
  skip လုပ်တယ် — ဆိုတော့ အဲဒီအချိန် MySQL ပေါ်မှာ ကျမယ့် bug တွေ အကုန် လွတ်နေမယ်။
  XAMPP MySQL ဖွင့်ပြီး ပြန် run ခိုင်းပါ။
- **`npm run build`** — ဒီ project က build လုပ်ပြီးသား ဖိုင် (`public/build`) ကို serve တာမို့ build မလုပ်ရင်
  CSS/JS အသစ်တွေ လုံးဝ မပေါ်ဘူး။

---

## ၂။ Security နှင့် Deploy အဆင့်

```text
DataPOS ရဲ့ security နဲ့ deploy အနေအထားကို စစ်ပါ။ ကုဒ်ဖတ်ရုံမဟုတ်ဘဲ တကယ် HTTP ခေါ်ပြီး သက်သေပြပါ။

က။ Web root ထိတွေ့မှု — XAMPP ရဲ့ DocumentRoot က D:/xmapp/htdocs ဖြစ်တယ်။ ဒါကြောင့်
   http://localhost/DataPOS/.env ကို ခေါ်ကြည့်ပါ။
   - 200 ပြန်ရင် = APP_KEY အပါအဝင် ဖိုင် တစ်ခုလုံး ပေါ်နေတာ (အတိအကျ ပြပါ)
   - 403/404 ဖြစ်ရမယ်
   ပြီးရင် .sql / .zip / root က .php ဖိုင် (scratch script တွေ) ရော ပေါ်နေလား စစ်ပါ။

ခ။ Route အားလုံး auth ကာကွယ်မှု ရှိမရှိ —
   php artisan route:list --json ကို ဖတ်ပြီး admin/ ၊ pos/ ၊ settings/ ပါတဲ့ route တွေထဲမှာ
   Authenticate middleware မပါတာ ရှိလား ရှာပါ။
   ရှိရင် အဲဒီ route ကို credential မပါဘဲ ခေါ်ကြည့်ပြီး ဘယ် status ပြန်လဲ ပြပါ —
   401/403 ဖြစ်ရမယ်။ 200 သို့မဟုတ် 422 ပြန်ရင် controller အထိ ရောက်နေတာ = လုံးဝ ဖွင့်ထားတာ။

ဂ။ .env အနေအထား — APP_ENV ၊ APP_DEBUG ၊ APP_KEY ၊ DB_CONNECTION ၊ QUEUE_CONNECTION စစ်ပါ။
   Production မှာ APP_DEBUG=true ဖြစ်နေရင် error page က secret ပြတယ်။
   .env.production ထဲမှာ APP_KEY အလွတ် သို့မဟုတ် change_me လို့ ကျန်နေတာ ရှိလား စစ်ပါ။

ဃ။ Queue worker — Production က QUEUE_CONNECTION=database ဆိုရင် worker (supervisor/systemd)
   run နေရမယ်။ မဟုတ်ရင် job တွေ တိတ်တဆိတ် လုံးဝ မလုပ်ဘူး။

င။ Backup နဲ့ DB tool — backup က database တစ်ခုလုံး (ဆိုင်အားလုံး) ကို dump လုပ်တယ်။
   ဆိုင်တစ်ဆိုင်ရဲ့ manager က တခြားဆိုင်တွေရဲ့ ဒေတာ download လုပ်လို့ရနေရင် ပြင်ရမယ်။
```

---

## ၃။ ပိုက်ဆံ တွက်ချက်မှု အဆင့် (အရေးကြီးဆုံး)

```text
DataPOS မှာ ပိုက်ဆံတွက်တဲ့ နေရာအားလုံးကို စစ်ပါ။ Project ရဲ့ စံနှုန်းက bcmath (decimal) ဖြစ်ရမယ်၊
PHP float နဲ့ တွက်ရင် ငွေလွဲနိုင်တယ်။

က။ `(float)` ရှာပါ — ပိုက်ဆံနဲ့ ဆိုင်တဲ့ ကိန်းတွေမှာ float cast ရှိလား။
   ရှိရင် အဲဒီတန်ဖိုး DB ထဲ ပြန်ရေးသလား၊ ဖောက်သည်ကို ပြသသလား စစ်ပါ။

ခ။ `$x * $y` နဲ့ တွက်ပြီး bcadd/bcmul သုံးတဲ့ နေရာ ရောက်နေတာ ရှိလား —
   float ကနေ string ပြောင်းပြီး bcmath ထဲ ထည့်ရင် အဲဒီ float ရဲ့ rounding ပါလာတယ်။

ဂ။ Validation က `numeric` သုံးပြီး bcmath ခေါ်တဲ့နေရာ ရှိလား —
   `numeric` က "1e3" ကို လက်ခံတယ်၊ bcmath က လက်မခံဘဲ 500 error ပစ်တယ်။
   `decimal:0,2` ဖြစ်ရမယ်။

ဃ။ Transaction + lock — စာရင်း ၂ ကြောင်းအထက် ရေးတဲ့၊ ဒါမှမဟုတ် လက်ကျန်စစ်ပြီး ရေးတဲ့ နေရာတွေမှာ
   DB::transaction နဲ့ lockForUpdate ရှိလား။
   (ဥပမာ — အကြွေးကောက်ခံမှု: လက်ကျန်စစ်တာ နဲ့ ရေးတာ ခွဲထားရင် ပြိုင်တွက်ရင် အနုတ်ရောက်နိုင်တယ်။
    ငွေထုတ်တာမှာ လက်ကျန်စစ်တာ လုံးဝ မရှိရင် အကောင့် အနုတ်ထွက်နိုင်တယ်။)

င။ ဆိုင်ခွဲ ရောနှောမှု (cross-store) — ID တစ်ခုကို လက်ခံတဲ့နေရာတိုင်း `store_id` နဲ့ ကာထားလား။
   `exists:products,id` သက်သက်ဆိုရင် ဆိုင်တစ်ခုက တခြားဆိုင်ရဲ့ ပစ္စည်း ထည့်လို့ရတယ်။

တွေ့တာတိုင်းကို file:line နဲ့ ပြပါ။ ဘယ်ဟာ အရေးကြီးဆုံးလဲ အစဉ်လိုက်ပြပါ။ ဘာမှ မပြင်ပါနဲ့။
```

---

## ၄။ Frontend အဆင့်

```text
DataPOS ရဲ့ မျက်နှာပြင်ပိုင်း ကို စစ်ပါ။

က။ CSP ကြောင့် သေနေတဲ့ ခလုတ် — Project ရဲ့ CSP မှာ 'unsafe-inline' မရှိဘူး။ ဒါကြောင့်
   onclick= onchange= onsubmit= အားလုံး browser က ပိတ်ပစ်တယ် (ခလုတ်နှိပ်ရင် ဘာမှ မဖြစ်၊ error လည်း မပြ)။
   resources/views/ ထဲမှာ inline handler ရှာပြီး ဘယ်စာမျက်နှာ ဘယ်ခလုတ် သေနေလဲ စာရင်းပြပါ။

ခ။ စျေးနှုန်း ပြသမှု — "Ks" လို့ hardcode ရေးထားတာ ရှိလား။ /admin/settings/currency setting ကို
   လိုက်နာရမယ် (format_currency() / window.formatCurrency())။
   အရေအတွက်တွေမှာ "10.000" လို မပြရ — format_quantity() သုံးရမယ်။

ဂ။ ဘာသာစကား ၃ မျိုး — lang/my ၊ lang/en ၊ lang/zh_CN သုံးခုလုံးမှာ key တူညီရမယ်။
   မျက်နှာပြင်တွေမှာ သုံးထားတဲ့ key တွေ သုံးခုလုံးမှာ ရှိမရှိ စစ်ပါ — မရှိရင် screen ပေါ်မှာ
   "messages.xxx" လို အကြမ်း ပေါ်နေမယ်။

ဃ။ Alpine.js — Blade ရဲ့ x-data="..." attribute အတွင်းမှာ raw ကွက်ပါ " ပါရင် အဲဒီစာမျက်နှာရဲ့
   Alpine state တစ်ခုလုံး တိတ်တဆိတ် သေတယ် (dropdown အလွတ်၊ x-show panel တွေ ပွင့်နေမြဲ)။

င။ Build — Tailwind class အသစ် ထည့်ထားပြီး npm run build မလုပ်ထားရင် အဲဒီ class တွေ
   CSS ထဲ မပါဘူး (element က style မကျဘဲ ပေါ်လာမယ်)။
```

---

## ၅။ Database အဆင့်

```text
DataPOS ရဲ့ database အလုပ်တွေ စစ်ပါ။

က။ MySQL ပေါ်မှာ migrate ကျမကျ — XAMPP MySQL ဖွင့်ပြီး
   php artisan test tests/Feature/MysqlMigrationSmokeTest.php run ပါ။
   SQLite က "AFTER column" ကို ignore လုပ်တယ်၊ MySQL က လက်မခံဘူး။ ဒါကြောင့်
   SQLite မှာ အောင်နေတဲ့ migration က MySQL production မှာ ကျနိုင်တယ်။

ခ။ ရှိမရှိ မသေချာတဲ့ column ကို ခေါ်နေတာ ရှိလား — ဒါက အကွက်ဆိုးဆုံး bug ဖြစ်နိုင်တယ်:
   SQLite မှာ မသိသော "column_name" ကို string အနေနဲ့ ဖတ်လိုက်တာမို့ error မတက်ဘဲ
   0 row ပြန်တယ် (query အလုပ်လုပ်နေသလို ထင်ရတယ်)။ MySQL မှာတော့ တိုက်ရိုက် ကျမယ်။
   စစ်နည်း — ကုဒ်ထဲက where/sum/whereBetween/orderBy ခေါ်တဲ့ column နာမည်တွေကို
   database ရဲ့ တကယ့် column စာရင်းနဲ့ တိုက်စစ်ပါ။ မကိုက်တာ တွေ့ရင် report လုပ်ပါ။

ဂ။ Unique index လိုတဲ့နေရာ — စာရွက်နံပါတ် (invoice / receipt / return / PO / expense) တွေမှာ
   unique index မရှိရင် ပြိုင်တွက်ရင် နံပါတ်တူ ဖြစ်နိုင်တယ်။
   နံပါတ်ထုတ်တာလည်း row lock ပါတဲ့ sequence နဲ့ လုပ်ရမယ် (count()+1 မဖြစ်ရ)။
```

---

## ၆။ ဒီ project မှာ ကျော်လို့မရတဲ့ အချက် ၆ ချက်

| အချက် | ဘာကြောင့် လွယ်လွယ် လွတ်နိုင်လဲ |
|---|---|
| **၁။ Local က SQLite၊ Production က MySQL** | SQLite က error မပြတဲ့နေရာတွေမှာ MySQL က ပြတယ်။ MySQL ဖွင့်ထားမှ တွေ့မယ် |
| **၂။ Local က APP_DEBUG=true** | Production မှာ false ဖြစ်ရမယ်။ true ဖြစ်နေရင် ဖောက်သည်ကို secret ပေါ်တယ် |
| **၃။ `public/build` က gitignore** | Deploy တစ်ခါတိုင်း server ပေါ်မှာ `npm run build` လုပ်ရမယ် |
| **၄။ Local က Laravel serve၊ Production က Apache/LiteSpeed** | DocumentRoot က `public/` ဖြစ်ရမယ်။ မဟုတ်ရင် `.env` ပေါ်တယ် |
| **၅။ Test "skip" ဖြစ်နေတာ** | Skip ဖြစ်နေတဲ့ test က အစိမ်းရောင် ပြတယ်။ MySQL test လို တစ်ခုတည်း run ကြည့်ပါ |
| **၆။ float က ၁၀^၁၅ အထိ တိကျတယ် ဆိုတာ မှားတဲ့ ခန့်မှန်း** | float တစ်ခုတည်းအတွက်သာ တိကျတယ်။ **PHP နဲ့ ရာထောင်ချီ ပေါင်းရင်တော့ ၂၀၀,၀၀၀ row လောက်ကတည်းက လွဲပြီ** (တိုင်းတာချက်: ၁၀၀,၀၀၀ row = တိကျ၊ ၂၀၀,၀၀၀ = −၀.၀၁ ကျပ်၊ ၁,၀၀၀,၀၀၀ = −၀.၁၉ ကျပ်) |

---

## ၇။ လက်တွေ့ အသုံးပြုမှု စစ်ဆေးချက် (Cashier လမ်းကြောင်း)

နည်းပညာစစ်ဆေးမှု အားလုံး အောင်ပြီးလည်း **လူက တကယ်သုံးလို့ရရင်** ပြီးတာမို့ ဒါကို လိုက်လုပ်ကြည့်ပါ:

```text
Local server မှာ အောက်ပါ လုပ်ငန်းစဉ်ကို အစအဆုံး လုပ်ကြည့်ပြီး ဘယ်နေရာမှာ ရပ်သွားလဲ report လုပ်ပါ —

1. Cashier နဲ့ login → Register ဖွင့် (opening float ထည့်)
2. ပစ္စည်း ရှာပြီး လှည်း → Cash နဲ့ ရောင်း → Receipt print စမ်း
3. KPay / WavePay နဲ့ ရောင်း
4. Credit (အကြွေး) နဲ့ ရောင်း → ဖောက်သည်စာရင်း ဝင်မဝင် စစ်
5. ပစ္စည်း ပြန်အမ်း (Return) → ပိုက်ဆံနဲ့ စတော့ မှန်မှန် ပြန်လား
6. ဆိုင်ပိတ် (Daily Closing) → approve → Z-report print
7. Shift ပိတ် → ပြီးရင် အဲဒီ register ကို နောက်တစ်ခါ ထပ်ဖွင့်/ထပ်ပိတ် လုပ်ကြည့်
   (မှတ်ချက် — တစ်ခါက register တစ်ခုကို တစ်သက်လုံး တစ်ခါပဲ ပိတ်လို့ရတဲ့ bug ရှိခဲ့တယ်)

ကွာဟမှု တွေ့ရင် screenshot / အမှားစာသားနဲ့တကွ ပြပါ။ ဘာမှ မပြင်ပါနဲ့။
```

---

## ၈။ ရလဒ် တင်ပြပုံ (agent ကို ဒီပုံစံ ခိုင်းပါ)

```text
စစ်ဆေးမှု ရလဒ်ကို အောက်ပါပုံစံနဲ့ တင်ပြပါ —

## ရလဒ် အကျဉ်း
(တစ်ကြောင်း — ပြဿနာ ဘယ်နှစ်ခု တွေ့လဲ၊ ဘယ်ဟာ production တင်ခင် မဖြစ်မနေ ပြင်ရမလဲ)

## 🔴 မဖြစ်မနေ ပြင်ရမည်
| # | ပြဿနာ | ဖိုင်:လိုင်း | ဘယ်လို သက်သေပြထားလဲ |

## 🟠 ပြင်သင့်
## 🟢 ကောင်းပြီးသား (စစ်ပြီး အတည်ပြုထားသည်)

## လုပ်ခဲ့တဲ့ စစ်ဆေးမှု မှတ်တမ်း
(ဘယ် command run လဲ၊ ဘယ် URL ခေါ်လဲ — တစ်ခြားသူ ပြန်စစ်နိုင်အောင်)

## မစစ်နိုင်ခဲ့တာ / မသေချာတာ
(ရိုးသားစွာ ရေးပါ — "စမ်းမရဘူး" လို့ ရေးတာ မရှက်စရာဘူး)
```

**သတိ:** agent တစ်ခုက "အားလုံး ကောင်းပါတယ်" လို့ ပြောရင် အဲဒါကို ပြန်စစ်ပါ —
*"မင်း ဘာကို ဘယ်လို စမ်းပြီး ကောင်းတယ်လို့ ပြောတာလဲ၊ command နဲ့ ပြပါ"* လို့ မေးပါ။
**သက်သေမပြဘဲ အာမခံချက် မယူရ။**

---

## ၉။ တစ်ခါတည်း ကူးထည့်လို့ရတဲ့ Prompt (အပြည့်အစုံ)

```text
DataPOS ကို production မတင်ခင် အစအဆုံး စစ်ဆေးပါ။ ကုဒ်ဖတ်ရုံမဟုတ်ဘဲ တကယ် run/ခေါ်ပြီး
သက်သေပြပါ။ ဘာမှ မပြင်ပါနဲ့ — တင်ပြရုံပါ။

[Automated]
- php artisan test
- php artisan test tests/Feature/MysqlMigrationSmokeTest.php   (skip ဖြစ်ရင် MySQL ဖွင့်ခိုင်းပါ)
- npm run build

[Security / Deploy]
- http://localhost/DataPOS/.env ခေါ်ကြည့် (200 ဖြစ်ရင် အတိအကျ ပြ)
- route:list ထဲက admin/pos route တွေ auth ရှိမရှိ + credential မပါဘဲ ခေါ်ကြည့်ပြီး status ပြ
- APP_ENV / APP_DEBUG / APP_KEY / QUEUE_CONNECTION စစ်

[Money]
- ပိုက်ဆံနဲ့ ဆိုင်တဲ့ float cast ၊ "$x * $y" တွက်ပြီး bcmath ထဲ ထည့်တဲ့နေရာ၊
  numeric validation + bcmath၊ transaction/lock မပါတဲ့ စာရင်းရေးမှု၊ cross-store ID
- တွေ့တာတိုင်း file:line

[Frontend]
- inline onclick/onchange/onsubmit (= CSP ကြောင့် သေနေတဲ့ ခလုတ်)
- hardcode "Ks" ၊ format_quantity မသုံးတဲ့ အရေအတွက်
- lang ၃ မျိုးလုံးမှာ မရှိတဲ့ key
- x-data attribute ထဲ raw " ပါလား

[Database]
- MySQL ပေါ် migrate ကျမကျ
- database မှာ မရှိတဲ့ column ကို ခေါ်နေတာ ရှိလား
  (SQLite က "မသော column" ကို string ဖတ်လိုက်တာမို့ 0 row ပြန်တယ် — error မပြ)
- စာရွက်နံပါတ် column တွေမှာ unique index ရှိမရှိ

ရလဒ်ကို — 🔴 မဖြစ်မနေ / 🟠 ပြင်သင့် / 🟢 ကောင်းပြီးသား / မစစ်နိုင်ခဲ့တာ — ဆိုပြီး ၄ ပိုင်း ခွဲပြပါ။
ပြဿနာတိုင်းမှာ ဘယ်လို သက်သေပြထားလဲ ရေးပါ။ သက်သေမရှိရင် "မသေချာ" လို့ ရေးပါ။
```

---

## ဆက်စပ် စာရွက်များ

- [Core Architecture](01_CORE_ARCHITECTURE.md) — စနစ် တည်ဆောက်ပုံ
- [QA and Release Checklists](06_QA_AND_RELEASE_CHECKLISTS.md) — release အခါ စစ်ရမည့် စာရင်း
- [Operations and Deployment](05_OPERATIONS_AND_DEPLOYMENT.md) — deploy လုပ်နည်း
- [Production Readiness Audit Report](PRODUCTION_READINESS_AUDIT_REPORT.md) — ၂၀၂၆-၀၉-၁၃ ရက် တင်ပြချက်
- [Project Commands Cheatsheet](PROJECT_COMMANDS_CHEATSHEET.md) — command အမြန်ကြည့်ရန်
