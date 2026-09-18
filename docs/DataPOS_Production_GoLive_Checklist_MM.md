# DataPOS — Production မတင်မီ စစ်ဆေးရန် စာရင်း (2026-09-19)

ဤစာရင်းတွင် **ဒေတာဘေ့စ်/ကုဒ် အပြင် လုပ်ရမည့်အရာများ** နှင့် **မစမ်းရသေးသည်များ** ကို
ရိုးသားစွာ ဖော်ပြထားသည်။ (ကုဒ်အပိုင်း စမ်းသပ်မှုရလဒ်များကို သက်ဆိုင်ရာ doc များတွင် ကြည့်ပါ။)

## ၁။ `.env` (deploy နေ့တွင် မဖြစ်မနေ)

| key | လိုအပ်သည့်တန်ဖိုး | အကြောင်းရင်း |
|---|---|---|
| `APP_ENV` | `production` | `staging`/`production` ဖြစ်မှသာ passwordless **quick-login** ပိတ်သည် |
| `APP_DEBUG` | `false` | error page တွင် ကုဒ်/လမ်းကြောင်း ပေါက်ကြားမှု ရပ်တန့်ရန် |
| `SHOW_QUICK_LOGIN` | `false` | quick-login သည် စကားဝှက် မလိုဘဲ ဝင်နိုင်သည် — local စမ်းသပ်ရန်သာ |
| `APP_URL` | ဆိုင်၏ HTTPS URL | ပြေစာ/QR လင့်များ၊ CSP၊ sitemap မှန်ရန် |
| `APP_KEY` | **မူရင်း** (ပြောင်းလျှင် ရှိပြီးသား encrypted data ပျက်) | — |
| `SESSION_SECURE_COOKIE` | `true` (HTTPS ဖြစ်လျှင်) | cookie ကို HTTPS ဖြင့်သာ ပို့ရန် |
| `DB_*` | production DB | စမ်းသပ် DB မဟုတ်ကြောင်း သေချာပါ |
| `QUEUE_CONNECTION` | `database` သို့မဟုတ် `sync` | Web Push notification (`OrderStatusNotification`) ပို့ရန် |
| `MAIL_*` | မထည့်လျှင် mail ပို့မည် မဟုတ်ပါ | အီးမေးလ် မသုံးလျှင် ထားခဲ့နိုင် |

**စစ်ဆေးပုံ (လက်တွေ့ run ပြီး ရလဒ်တင်ပြထားသည်):**
```bash
php artisan about            # environment, debug, cache driver
php artisan migrate:status   # အားလုံး Ran ဖြစ်ရမည်
```

## ၂။ Deploy လုပ်ငန်းစဉ်

```bash
php artisan down
git pull                     # main (သို့) သင့် release branch
composer install --no-dev --optimize-autoloader
php artisan migrate --force  # additive migration များ (အောက်တွင် ကြည့်)
npm ci && npm run build      # **မဖြစ်မနေ** — Tailwind/JS ကို built asset ဖြင့်သာ ကျွေးသည်
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

**မှတ်ချက်:** `public/build/` ကို git ignore လုပ်ထားသဖြင့် **server တွင် `npm run build`
မဖြစ်မနေ run ရမည်** (မဟုတ်ပါက JS/CSS အသစ် မပေါ်ပါ)။

## ၃။ ဤအတောအတွင်း ထည့်ခဲ့သော database migration များ (additive သာ)

| migration | အကျိုးသက်ရောက်မှု |
|---|---|
| `2026_09_19_000001_add_coupon_columns_to_orders` | `orders.discount_amount/coupon_code/promotion_id` + `promotion_usages.order_id` (FK) — ရှိပြီးသား အော်ဒါ total မပြောင်း |
| `2026_09_19_000002_add_pos_sale_link_to_orders` | `orders.pos_sale_id` (nullable FK → `pos_sales`) — ကောင်တာပြေစာ လင့် |

အားလုံး nullable/default ဖြစ်သဖြင့် **rollback မလိုဘဲ** တင်နိုင်သည်။

## ၄။ ပရင်တာ (printing) — production မတင်မီ သိထားရမည်

- လက်ရှိ printing သည် **browser `window.print()`** သာ — ESC/POS thermal command ပို့သည့်
  transport မရှိပါ။ ထို့ကြောင့် **58mm/80mm thermal printer** အတွက် browser မှ
  page size/margin ကို ကိုက်ညီအောင် သတ်မှတ်ရမည် (driver စမ်းသပ်မှု လိုသည်)။
- **ငွေအံဆွဲ (cash drawer) auto-open** မရှိသေးပါ (printer driver မှတဆင့်သာ ဖြစ်နိုင်)။
- Barcode/RFID scanner သည် **keyboard-wedge** (barcode ရိုက်ထည့်သလို) သာ —
  serial/HID driver တိုက်ရိုက် မပါ။

## ၅။ မစမ်းရသေးသည့် လုပ်ဆောင်ချက်များ (ရိုးသားစွာ)

ကုဒ်ရှိပြီး test ဖြင့် ဖုံးထားသော်လည်း **လက်တွေ့ device/ပြင်ပ service** မလိုဘဲ
မစမ်းနိုင်သည့်အရာများ:

- ပရင်တာ/ESC-POS၊ ငွေအံဆွဲ (အထက်တွင် ကြည့်)
- eload (ဖုန်းငွေဖြည့်) ပြင်ပ API — account/credential လိုသည်
- SMS/Viber/Telegram အပို့ — provider token လိုသည်
- Offline sync (SyncOutbox) — network ဖြတ်တောက်စမ်းသပ်မှု လိုသည်
- Theme publish/rollback — browser QA လုပ်ပြီး (memory တွင် မှတ်ထား)၊ သို့သော် production
  theme ကို ဆိုင်ပိုင်ရှင်ကိုယ်တိုင် publish လုပ်သည့်အခါ ထပ်စမ်းရန် လိုသည်
- Content CRUD (pages/banners) — admin UI ရှိသည်၊ အကြောင်းအရာ အမှန် ထည့်ရန် လိုသည်
- Backup restore — backup ဖိုင် ရှိပြီး (memory)၊ restore ကို staging တွင် စမ်းရန် လိုသည်

## ၆။ ဆိုင်ပိုင်ရှင် သိထားရမည့် စီးပွားရေး စည်းကမ်းများ

- **အော်ဒါ အတည်ပြုလျှင် stock ကိုင်ထားသည်** (ရောင်းလို့ရသည့်အရေအတွက် ချက်ချင်းလျော့)၊
  ကောင်တာမှ အရောင်းတင်ချိန်တွင် **၁ ခါသာ** ဖြတ်သည် (အသေးစိတ်:
  `docs/DataPOS_Online_Order_Counter_Fulfillment_MM.md`)။
- **Points ကို ကောင်တာတွင်သာ သုံးနိုင်သည်** (အွန်လိုင်း checkout တွင် မပါသေး)။
- Points ၁ မှတ်၏ တန်ဖိုး၊ ရရှိမှုနှုန်း၊ ကုဒ် (coupon) များကို **ဆိုင်ဆက်တင် → POS**
  တွင် သတ်မှတ်ရမည် (0 = ပိတ်)။
- Backdate/ပိတ်ထားသည့်ရက် (approved daily closing) တွင် အရောင်းတင်၍ မရပါ
  (`PeriodLockService`)။
