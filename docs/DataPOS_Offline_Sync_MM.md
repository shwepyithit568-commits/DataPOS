# DataPOS — Offline / LAN အသုံးပြုနည်းနှင့် အရောင်းပို့ခြင်း (Offline Sync)

> **အတိုချုပ် (TL;DR):** DataPOS သည် Laravel + MySQL server အက်ပ်ဖြစ်သည်။
> ဒါကြောင့် **"အင်တာနက်မရှိခြင်း" နှင့် "server မရှိခြင်း" က မတူပါ**။
> ဆိုင်အတွင်းမှာ server စက်တစ်လုံး (XAMPP) ထားပြီး ကောင်တာများက ဆိုင် WiFi/LAN
> ကနေ ချိတ်ဆိုက်ရင် — **အင်တာနက် လုံးဝမလိုဘဲ** Admin + POS အားလုံး အလုပ်လုပ်သည်။
> ဒါသည် မြန်မာဆိုင်များအတွက် မှန်ကန်သော နည်းလမ်းဖြစ်သည်။

---

## ၁။ ဘယ်လိုအခြေအနေများ ပံ့ပိုးထားသလဲ

### A. Standalone — ဆိုင်အတွင်းစက် (အကြံပြုသည်)

```
[ဆိုင်အတွင်းကွန်ပျူတာ]  ← server + MySQL + Admin/POS
        ↑  WiFi / LAN cable
[ကောင်တာ ၁] [ကောင်တာ ၂] [Tablet] [ဆိုင်ရှင်ဖုန်း]
```

- အရောင်း၊ စတော့၊ ပြေစာ၊ နေ့ချုပ် အားလုံး ဒီစက်ပေါ်မှာ လုပ်သည်
- **အင်တာနက် မလိုပါ** — ဆိုင် WiFi ရှိရုံနဲ့ လုံလောက်သည်
- အင်တာနက်လိုတာ: storefront ဖြင့် ဝယ်သူများ၊ E-Load၊ SMS/Viber၊ backup ကို cloud တင်ခြင်း
- ဒီအခြေအနေအတွက် code အသစ် မလိုပါ — deploy လုပ်တဲ့နည်း ကိစ္စသာ ဖြစ်သည်
- `.env`: `DATAPOS_SYNC_ENABLED=false` ထားပါ (default)

### B. Terminal — ကောင်တာက အရောင်းကို အပြင်ပို့ခြင်း

```
[ဆိုင်ကောင်တာ = Terminal]  ──အင်တာနက်ရချိန်──►  [ဗဟိုစက် = Central / storefront]
   အရောင်းကို ဒီမှာ မှတ်တမ်းတင်သည်                     ဗဟိုမှာ ပြန်လည် မှတ်တမ်းတင်သည်
```

- အင်တာနက် မရှိလည်း ရောင်းချနိုင်သည် — အရောင်းများ queue (`sync_outbox_records`) ထဲ စုပုံ
- အင်တာနက် ပြန်ရချိန် အလိုအလျောက် (schedule) သို့မဟုတ် လက်ဖြင့် (`sync:push`) ပို့သည်
- ပို့မှု မအောင်မြင်လျှင် queue ထဲ ရှိနေမည် — **ဘယ်တော့မှ ပျောက်မည် မဟုတ်**

### ပံ့ပိုးမထားသည့်အချက် — ရိုးသားစွာ

Cloud (Hostinger) ပေါ် host လုပ်ထားသော instance ကို **browser-only offline** ဖြစ်အောင်
လုပ်ခြင်းကို မထောက်ပံ့ပါ။ လိုချင်ရင် service worker + IndexedDB + local replica +
conflict resolution ဖြင့် POS UI ကို client-side အက်ပ်အဖြစ် ပြန်လည်ရေးရမည်။
ငွေနှင့် စတော့အတွက် အန္တရာယ်များသည် — ကောင်တာ နှစ်လုံး offline ဖြစ်နေစဉ် နောက်ဆုံး
ပစ္စည်းတစ်လုံးကို နှစ်ဖက်ရောင်းမိခြင်း၊ ပြေစာအမှတ် တိုက်မိခြင်း စသည်။
ဆိုင်အတွက် မှန်ကန်သော ဖြေရှင်းချက်မှာ **A (ဆိုင်အတွင်းစက်)** ဖြစ်သည်။

---

## ၂။ အင်တာနက် မရှိချိန် ဘာအလုပ်လုပ်သလဲ

| အလုပ်လုပ်သည် (server ဆိုင်အတွင်းရှိလျှင်) | အင်တာနက် လိုသည် |
|---|---|
| POS အရောင်း၊ ပြေစာထုတ်၊ barcode scan | Customer တွေ storefront ဖြင့် ဝယ်ခြင်း |
| စတော့/ledger၊ stock ledger စာမျက်နှာ | ဖုန်းဘေလ် / E-Load ဖြည့်ခြင်း |
| Adjustment၊ Buy-Back၊ Service Job | SMS / Viber အသိပေးချက် |
| နေ့ချုပ် (Closing)၊ X-Report၊ အစီရင်ခံစာများ | Backup ကို cloud (S3) တင်ခြင်း |
| ပရင်တာ print (58mm/80mm/A5/A4) | Terminal မှ ဗဟိုစက်သို့ အရောင်းပို့ခြင်း |
| Backup ကို ဆိုင်စက်ထဲ/USB ထဲ သိမ်းခြင်း | Web Push notification |

**မှတ်ချက်:** Font (Noto Sans Myanmar, Outfit, Roboto) နှင့် Buy-Back PDF ကိရိယာ
(html2pdf) တို့ကို **ဤစက်ပေါ်မှာသာ** bundle လုပ်ထားသည်။ မည်သည့် CDN (Google Fonts,
cdnjs) ကိုမှ ခေါ်မထားပါ — ဒါကြောင့် အင်တာနက်မရှိလည်း စာလုံးနှင့် PDF မှန်ကန်စွာ ထွက်သည်။

---

## ၃။ A. Standalone (ဆိုင်အတွင်းစက်) တည်ဆောက်နည်း

1. ဆိုင်အတွင်းကွန်ပျူတာတွင် XAMPP (Apache + MySQL + PHP) ထည့်ပြီး DataPOS ကို တင်ပါ
2. `D:\xmapp\apache\conf\extra\httpd-vhosts.conf` မှာ Apache ကို **LAN interface** ဖွင့်ပါ
   (default က port 80/listen အားလုံးဖြစ်လို့ များသောအားဖြင့် အလိုအလျောက် ရသည်)
3. Windows Firewall တွင် **inbound rule: TCP 80 (သို့ 8500)** ကို private network အတွက် ဖွင့်ပါ
4. ကောင်တာစက်များမှ `http://192.168.x.x/` (ဆိုင်စက်၏ IP) ဖြင့် ဝင်ပါ
5. ဆိုင်စက်၏ IP ကို fixed (static) ထားပါ — မဟုတ်လျှင် router က IP ပြောင်းနိုင်သည်
6. `.env` မှာ `APP_URL=http://192.168.x.x`, `DATAPOS_SYNC_ENABLED=false`

**စစ်ရန်:** ကောင်တာစက်မှ အင်တာနက် cable ဖြုတ်ပြီး POS အရောင်းတစ်ခု လုပ်ကြည့်ပါ —
ပြေစာ ထွက်ရမည်။ (ဤစစ်ဆေးမှုကို production မတင်မီ တစ်ခါ လုပ်ရန် လိုသည်။)

---

## ၄။ B. Terminal → Central အရောင်းပို့ခြင်း တည်ဆောက်နည်း

### ဗဟိုစက် (Cloud) တွင်

1. `/store/{slug}/admin/sync` ဖွင့် → **Sync API Key** → *Generate*
2. ပေါ်လာသော key ကို တစ်ခါသာ ပြသည် — ကူးယူပြီး ကောင်တာစက်ရဲ့ `.env` ထဲ ထည့်ပါ
3. (လိုအပ်လျှင်) *Revoke* ဖြင့် key ကို ပယ်ဖျက်နိုင်သည် — ကောင်တာများ ချက်ချင်း ရပ်မည်

### ကောင်တာစက် (Terminal) ၏ `.env` တွင်

```env
DATAPOS_SYNC_ENABLED=true
DATAPOS_SYNC_ROLE=terminal
DATAPOS_SYNC_CENTRAL_URL=https://yourdomain.com
DATAPOS_SYNC_STORE_SLUG=your-store-slug
DATAPOS_SYNC_API_KEY=<ဗဟိုစက်မှ ရသော key>
DATAPOS_SYNC_DEVICE_ID=ကောင်တာ-၁      # optional; default = စက်၏ hostname
```

### စစ်ဆေးခြင်း

```bash
php artisan sync:status          # configuration + queue + ဗဟိုစက် ချိတ်ဆက်မှု
php artisan sync:push            # လက်ဖြင့် တစ်ခါ ပို့ကြည့်ခြင်း
php artisan sync:pull            # ဗဟိုစက်၏ catalog delta ကို ကြည့်ခြင်း
```

### အလိုအလျောက် ပို့ခြင်း

Server ပေါ်တွင် cron တစ်ကြောင်း ရှိရုံနှင့် လုံလောက်သည် —

```
* * * * * php artisan schedule:run
```

`sync:push` ကို **၅ မိနစ်တစ်ခါ** အလိုအလျောက် run သည် (`routes/console.php`)။
အင်တာနက် မရှိချိန် run လျှင် exit code 0 ဖြင့် ရပ်ပြီး queue ကို မထိပါ —
cron error မဖြစ်ပါ။

ထို့အပြင် Admin မျက်နှာပြင်ရှိ **Sync widget** သည် browser က "online" ပြန်ဖြစ်သည့်အခါ
နှင့် pending အရောင်းများ ရှိနေသည့်အခါ အလိုအလျောက် ပို့ပေးသည်။

---

## ၅။ ငွေတိကျမှု အာမခံချက် (အရေးကြီးသည်)

အရောင်းတစ်ခုကို ဗဟိုစက်မှာ ပြန်လည်မှတ်တမ်းတင်သည့်အခါ —

1. **ကောင်တာက ကောက်ခဲ့သည့် ဈေးအတိအကျ** ကို line တစ်ခုချင်းစီအလိုက် ပို့သည်
   (`unit_price`) — ဗဟိုစက်က မိမိဈေးနှုန်းစာရင်းဖြင့် ပြန်မတွက်ပါ
2. **လျှော့ပေးခဲ့သော discount** (manual + coupon + point) ကို ပါ ပို့သည် —
   မပို့လျှင် discount ပါသော အရောင်းတိုင်း စာရင်းနှစ်ခု မကိုက်ပါ
3. ဗဟိုစက် **စုစုပေါင်းကို ပြန်စစ်သည်** — မကိုက်လျှင် `total mismatch: ...`
   ဟူသော သတိပေးချက် ပေါ်လာမည် (`⚠ Synced, needs a look`)၊ ဖုံးကွယ်မထားပါ
4. **Idempotency:** `client_transaction_id` ဖြင့် ပို့သည်။ အင်တာနက် ပြတ်သွားလို့
   အဖြေမရဘဲ ထပ်ပို့မိလျှင်လည်း အရောင်း **နှစ်ခါ မမှတ်တမ်းတင်ပါ** (idempotent ဖြစ်သည်)
5. Offline ဖြစ်နေစဉ် ရောင်းခဲ့သော အရောင်းများသည် ဗဟိုစက်တွင် **"Offline Sync Register"**
   အမည်ရှိ register တစ်ခုထဲ ရောက်သည် — ရောင်းခဲ့သည့် **ရက်စွဲအလိုက်** သီးခြားဖြစ်သည်။
   ဒါကြောင့် လွန်ခဲ့သည့် ၃ ရက်က အရောင်းသည် ယနေ့ shift စာရင်းထဲ ရောမလာပါ

---

## ၆။ ပြဿနာ ရှာဖွေခြင်း (Troubleshooting)

| လက္ခဏာ | အကြောင်းရင်း | လုပ်ရန် |
|---|---|---|
| Sync screen တွင် "Not ready to send" | `.env` key မပြည့်စုံ | ဖော်ပြထားသည့် `DATAPOS_SYNC_*` key များဖြည့်ပါ |
| "Central rejected the key" | key မှား / ဗဟိုစက်မှ revoke လုပ်ခဲ့ | ဗဟိုစက်မှ key အသစ်ထုတ်ပြီး `.env` ပြင်ပါ |
| "Central unreachable" | အင်တာနက် မရှိ / URL မှား / firewall | အင်တာနက်စစ်ပါ၊ URL စစ်ပါ။ အရောင်းများ လုံခြုံစွာ queue ထဲ ရှိသည် |
| Records များ `failed` ဖြစ်နေသည် | ဗဟိုစက်က ပစ္စည်းမတွေ့ / စတော့မလုံလောက် | `error_message` ကို ဖတ်ပါ၊ ဗဟိုစက်တွင် ပစ္စည်း/စတော့ စစ်ပါ၊ ပြင်ပြီး *Retry* |
| `⚠ Synced, needs a look` | စုစုပေါင်း မကိုက် (tax/ဈေးနှုန်း ကွာဟ) | `error_message` တွင် နှစ်ဖက်တန်ဖိုး ပြသည် — ဗဟိုစက်၏ tax setting စစ်ပါ |
| ငွေအံဆွဲ မပွင့် | drawer hardware မရှိသေး | ESC/POS kick command မှန်ကန်သည် (`1B 70 00 19 FA`) — drawer ဝယ်ပြီး ချိတ်လျှင် ရမည် |

---

## ၇။ စမ်းသပ်ပြီးသော အချက်များ (Verified)

- Automated tests: `tests/Feature/OfflineSync/OfflineSyncClientTest.php` (17 tests) +
  `OfflineSyncEngineTest.php` (7 tests) — capture, push, offline retry, key rejection,
  per-record failure, total mismatch warning, discount fidelity, dated offline register,
  pull-delta customers/stock/variants၊ command များ
- ပို့မှု HTTP layer ကို **instance နှစ်ခု** (ကောင်တာ → ဗဟိုစက်) ဖြင့် စစ်ဆေးထားသည်
  — `docs/DataPOS_Offline_Sync_Verification_MM.md` တွင် တိုင်းတာရရှိသည့် ကိန်းဂဏန်းများ ကြည့်ပါ

---

## ၈။ မလုပ်ရသေးသည့်အချက် (ကျန်ရှိသည်)

- **အွန်လိုင်း order ကို ဆိုင်စက်သို့ ဆွဲချခြင်း** — လောလောဆယ် `sync:pull` က
  product/category/customer ကို ဆွဲသည်။ Storefront order များကို ဆိုင်အတွင်း
  instance သို့ ရောက်စေခြင်း မရှိသေးပါ (အွန်လိုင်းရှိချိန် cloud admin မှ ကြည့်ရသည်)
- `sync:pull` သည် **ဖတ်ကြည့်ရန်သာ** ဖြစ်သည် — ဗဟိုစက်၏ ဈေးနှုန်း/စတော့/ဖောက်သည်
  အခြေအနေကို ပြသည်၊ ဆိုင်စက်ထဲ ပြန်မရေးပါ။ အကြောင်းမှာ ဗဟိုစက်၏ id များကို
  ဆိုင်စက် id များအဖြစ် တိုက်ရိုက်ရေးလျှင် တိုက်မိပြီး ဒေသတွင်း ဈေးနှုန်း/စတော့ မှတ်တမ်း
  ပျက်စီးနိုင်သည်။ "ဘယ်စက်က catalog ကို ပိုင်ဆိုင်သည်" ဆိုသည့် ဆုံးဖြတ်ချက် ရှိပြီးမှ
  ရေးခြင်းကို ထည့်သင့်သည်
- Shift ပိတ်ခြင်း/ငွေရှင်းခြင်းကို terminal မှ ဗဟိုသို့ ပို့ခြင်း (လက်ရှိတွင် အရောင်းသာ)
- Refund / Buy-Back / Service Job များကို ပို့ခြင်း (လက်ရှိတွင် POS အရောင်းနှင့်
  အကြွေးကောက်ခံမှုသာ)
- ကောင်တာ နှစ်လုံးက တစ်ပစ္စည်းတည်းကို တစ်ချိန်တည်း offline ရောင်းခြင်း — ရောင်းခွင့်
  ပြုထားသည်၊ ဗဟိုစက်တွင် စတော့ မလုံလောက်လျှင် `failed` ဖြစ်ပြီး လူက စစ်ရမည်
