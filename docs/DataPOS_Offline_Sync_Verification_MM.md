# Offline Sync — လက်တွေ့ စမ်းသပ်မှု အစီရင်ခံစာ (Verification)

> **ရက်စွဲ:** 2026-09-19 · **နည်းလမ်း:** instance နှစ်ခု (ကောင်တာ → ဗဟိုစက်) ကို
> သီးသန့် MySQL database နှစ်ခုပေါ်တွင် တကယ် run ပြီး တကယ့် HTTP ဖြင့် ပို့ခြင်း
> (`php artisan serve` port 8301) — mock မဟုတ်ပါ။
> `datapos_uat` ကို **မထိခိုက်ပါ** (လုံးဝ သီးသန့် DB များသာ)။

---

## ၁။ စမ်းသပ်မှု တည်ဆောက်ပုံ

| အပိုင်း | တန်ဖိုး |
|---|---|
| ကောင်တာ (Terminal) DB | `datapos_sync_terminal` (migrate အသစ်) |
| ဗဟိုစက် (Central) DB | `datapos_sync_central` (migrate အသစ်) |
| ဗဟိုစက် URL | `http://127.0.0.1:8301` |
| Store slug | `sync-demo` |
| ကောင်တာ ပစ္စည်း | 33W GaN Charger — စတော့ ၄၀ |
| ဗဟိုစက် ပစ္စည်း | အလားတူ (slug/SKU ကိုက်) — စတော့ ၄၀ |

## ၂။ ရလဒ်

### (က) အင်တာနက် မရှိချိန် အရောင်း မှတ်တမ်းတင်ခြင်း

ကောင်တာတွင် ၂ လုံး × ၅,၀၀၀ = ၁၀,၀၀၀ မှ **၁,၀၀၀ လျှော့ပေးပြီး ၉,၀၀၀** ကောက်ခဲ့သည်။

```
receipt               : RCP-20260919-0001
subtotal              : 10000.00
discount              :  1000.00
total                 :  9000.00
client_transaction_id : sale-1-01M2VDRTFZR8DJZT2NXMYSCZGM
outbox_status         : pending          ← အင်တာနက်မလိုဘဲ queue ထဲ ရောက်သည်
outbox_discount       : 1000.00          ← ကောက်ခဲ့သည့် discount ပါလာသည်
outbox_expected_total :  9000.00
on_hand_now           :  38.000
```

### (ခ) အင်တာနက် ပြန်ရချိန် ပို့ခြင်း (တကယ့် HTTP)

```
$ php artisan sync:push
Sync Demo Shop: pushed 1 — 1 synced, 0 failed.      ← ပို့အောင်
$ php artisan sync:push
Sync Demo Shop: pushed 0 — 0 synced, 0 failed.      ← queue ဗလာ (ထပ်မပို့)
```

ကောင်တာစက်ရှိ queue row: `status=synced`, `synced_at=2026-09-19 06:01:43`,
`retry_count=0`, `error=null`, `device=AlinThitMobile-KoKoLin`

### (ဂ) ဗဟိုစက်တွင် ပြန်လည် မှတ်တမ်းတင်ခြင်း — **ငွေ ကိုက်သည်**

```json
{
  "sales_count": 1,
  "receipt": "RCP-20260919-0001",
  "client_tx": "sale-1-01M2VDRTFZR8DJZT2NXMYSCZGM",
  "subtotal": "10000.00",
  "discount": "1000.00",
  "total": "9000.00",
  "shift": "Offline Sync Register",
  "shift_opened_at": "2026-09-19 06:01:33",
  "posted_at": "2026-09-19 06:01:33",
  "payments": ["cash:9000.00"],
  "on_hand": "38.000",
  "movements": ["adjustment_in:40.000", "pos_sale:-2.000"]
}
```

- စုစုပေါင်း ၉,၀၀၀ က **တစ်ကျပ်မှ မကွာ** — discount မပို့ခဲ့လျှင် ဤနေရာတွင် ၁၀,၀၀၀ ဖြစ်မည်
- စတော့ ၄၀ → ၃၈၊ ledger movement တစ်ခုတည်း (`pos_sale:-2.000`)
- register အမည် "Offline Sync Register"၊ `opened_at` သည် **အရောင်း၏ ရက်စွဲ** ဖြစ်သည်
  (ယနေ့ shift ထဲ ရောမလာပါ)

### (ဃ) Idempotency — ထပ်ပို့မိလျှင်

```
$ (queue row ကို pending ပြန်လုပ်ပြီး) php artisan sync:push
Sync Demo Shop: pushed 1 — 1 synced, 0 failed.
→ ဗဟိုစက်: sales_count 1, movements 2 ခု, on_hand 38  (မပြောင်း)
```

**အဖြေ ပျောက်ဆုံးသွားတဲ့အခါ နှစ်ခါ ပို့မိလျှင်လည်း အရောင်း နှစ်ခု မဖြစ်ပါ**၊
စတော့လည်း နှစ်ခါ မလျှော့ပါ။

### (င) လုံခြုံရေး

| စမ်းသပ်မှု | ရလဒ် |
|---|---|
| Sync key မှန် | `200` — health JSON ပြန်သည် |
| Sync key မှား | `401` (store slug ကို ခန့်မှန်းရ မလွယ်စေရန် တူညီသော အဖြေ) |
| `.env` မပြည့်စုံချိန် ပို့ခြင်း | ပို့မထွက်ပါ၊ `DATAPOS_SYNC_*` ဘယ်ဟာ လိုနေလဲ ဖော်ပြသည်၊ queue မထိပါ |
| အင်တာနက် ပြတ်ချိန် ပို့ခြင်း | `sync:push` exit code 0၊ queue အားလုံး `pending`၊ `retry_count` မတက် |

## ၃။ Automated tests

| Suite | ရလဒ် |
|---|---|
| SQLite (full) | **2212 tests / 10045 assertions — OK** |
| MySQL (full, disposable DB) | **2212 tests / 10045 assertions — OK** |
| `tests/Feature/OfflineSync/` | 25 tests / 94 assertions — OK (18 အသစ် + 7 ရှိပြီး) |
| `tests/Feature/POS/BuyBackModuleTest.php` | OK (CDN မရှိကြောင်း အာမခံသည့် test အသစ် အပါအဝင်) |

အသစ် စမ်းသပ်ထားသည့် အချက်များ: offline capture၊ transaction ပျက်လျှင် queue
row မကျန်ခြင်း၊ push အောင်မြင်ခြင်း၊ offline ဖြစ်နေစဉ် queue ထိန်းသိမ်းခြင်း၊
key ပယ်ခံရခြင်း၊ record တစ်ခုချင်း မအောင်မြင်ခြင်း၊ total mismatch သတိပေးချက်၊
discount တိကျမှု၊ ရက်စွဲအလိုက် offline register၊ pull-delta တွင် customer
(`retail_customer`/`wholesale_customer`) နှင့် စတော့/variant ပါလာခြင်း၊ command များ။

## ၄။ ဤစမ်းသပ်မှုတွင် တွေ့ရှိပြီး ပြင်ဆင်ခဲ့သည့် ချို့ယွင်းချက်များ

1. **`getPullDelta()` သည် customer စာရင်းကို အမြဲ အလွတ် ပြန်ခဲ့သည်** —
   pivot role `'customer'` ကို စစ်နေသည်၊ application က `retail_customer` /
   `wholesale_customer` ကိုသာ ရေးသည်။ ရှိပြီးသား test ကလည်း role မှားဖြင့်
   ရေးထားသဖြင့် မမိခဲ့ပါ။
2. **Pull delta တွင် စတော့ မပါခဲ့ပါ** — ဒါကြောင့် terminal တစ်ခုသည်
   လက်ကျန်မရှိသည့် ပစ္စည်းကို ရောင်းမိနိုင်သည်။ ယခု စတော့ + variant ပါလာသည်။
3. **ပို့လာသည့် အရောင်း၏ discount မပါခဲ့ပါ** — ဗဟိုစက် ပြန်တွက်သည့်အခါ
   လျှော့ပေးခဲ့သည့် ပမာဏ ပျောက်ပြီး စာရင်း မကိုက်သည်။ ယခု payload တွင် ပါသည်။
4. **Offline အရောင်းများ ယနေ့ shift ထဲ ရောက်ခဲ့သည်** — လွန်ခဲ့သည့် ရက်များ၏
   အရောင်းများ ယနေ့ ငွေရှင်းစာရင်းထဲ ရောနေမည်။ ယခု ရက်စွဲအလိုက်
   "Offline Sync Register" သုံးသည်။
5. **`SUM(quantity_on_hand)` က SQLite တွင် `6`၊ MySQL တွင် `6.000`** — sync
   payload သည် ဘယ် engine ပေါ်မှာ run သည်ဖြင့် ပုံစံ မပြောင်းရ။ ယခု အမြဲ ၃ ဆယ်စုနှံ။
6. **`OfflineSyncService` မှ ဗဟိုစက်ကို ခေါ်ခြင်း လုံးဝ မရှိခဲ့ပါ** — queue ထဲ
   ရေးသည့် code (`enqueue()`) ကို application တစ်နေရာမှ မခေါ်ခဲ့ပါ (test ထဲသာ)။
   ဆိုလိုသည်က "offline sync" သည် ဗဟိုစက်ဖက် သက်သက်သာ ရှိခဲ့သည်။
7. **Buy-Back ပြေစာ၏ PDF/JPG export သည် ပျက်နေခဲ့သည်** — html2pdf ကို
   cdnjs မှ ခေါ်ထားပြီး CSP (`script-src 'self' + nonce`) က ပိတ်ထားသည်။
   Google Fonts လည်း `font-src 'self'` ဖြင့် ပိတ်ခံနေခဲ့သည်။ ယခု နှစ်ခုလုံးကို
   local bundle ဖြင့် ပြောင်းထားသည် — အင်တာနက်မလိုဘဲ အလုပ်လုပ်သည်။
   **deploy လုပ်သည့်အခါ `npm run build` ကို မဖြစ်မနေ run ရမည်** —
   `public/build` ကို git တွင် မထည့်ထားပါ။

## ၅။ ဤစမ်းသပ်မှုဖြင့် မဖုံးလွှမ်းသေးသည့်အချက်

- ကောင်တာ နှစ်လုံး **တစ်ချိန်တည်း** offline ဖြစ်နေစဉ် တစ်ပစ္စည်းတည်းကို
  ရောင်းမိခြင်း — ဗဟိုစက်တွင် စတော့ မလုံလောက်လျှင် record က `failed` ဖြစ်ပြီး
  လူက စစ်ရမည် (အလိုအလျောက် ဖြေရှင်းခြင်း မရှိသေး)
- Refund / Buy-Back / Service Job / Shift closing များကို ပို့ခြင်း (အရောင်းနှင့်
  အကြွေးကောက်ခံမှုသာ ပို့သည်)
- Storefront order များကို ဆိုင်အတွင်း instance သို့ ဆွဲချခြင်း
- အင်တာနက် ရှိ/မရှိ ကို ဆိုင်တစ်ခုလုံးအတွက် အဆက်မပြတ် စောင့်ကြည့်ခြင်း
  (schedule + widget ဖြင့် လုပ်သည်၊ monitoring dashboard မရှိသေး)
