# DataPOS — အွန်လိုင်းအော်ဒါ → ကောင်တာမှ ရောင်းချခြင်း (2026-09-19 ဒုတိယ pass)

ဤ pass တွင် **ဖောက်သည် အကောင့်ဖြင့် အွန်လိုင်းဝယ် → Admin/ဆိုင်ပိုင်ရှင်က အတည်ပြု →
ကောင်တာမှ ပစ္စည်းပေးအပ်** လမ်းကြောင်းတစ်ခုလုံးကို လက်တွေ့ (browser + MySQL) စမ်းသပ်ပြီး
တွေ့ရှိသည့် ချို့ယွင်းချက် ၃ ခုကို ပြင်ဆင်ခဲ့သည်။

## လမ်းကြောင်းတစ်ခုလုံး (မှတ်တမ်း)

| အဆင့် | လုပ်ဆောင်ချက် | ရလဒ် |
|---|---|---|
| ၁ | Storefront (`/store/uat-full-feature`) → **Register** ဖြင့် customer အသစ် | `users#16 Ma UAT Online` (09970000101) → **store_user.store_id = 3** ✓ |
| ၂ | Charger 25,000 + coupon `UAT10` (−2,500) → **အော်ဒါတင်မည်** | `ORD-6AAD91E30B08A` (#8)၊ user_id 16၊ total **22,500**၊ `promotion_usages.order_id = 8`၊ `used_count` 5 |
| ၃ | အရောင်းစာရင်း stock စစ် | အော်ဒါတင်ချိန်တွင် **မကျသေး** (pending) |
| ၄ | Admin: order 8 → **အတည်ပြုမည်** | `online_reserve −1` (movement 57)၊ on-hand 14 → **13** |
| ၅ | POS → **Web အော်ဒါများ** → "Cart ထဲသို့ ထည့်သွင်းမည်" | cart **25,000 → 22,500** (လျှော့ဈေး 2,500)၊ ဖောက်သည် Ma UAT Online အလိုအလျောက် ချိတ် |
| ၆ | **အရောင်းစာရင်းသွင်းမည်** (cash 22,500) | `RCP-20260919-0004`၊ total **22,500** / discount 2,500 / customer_id 16 |
| ၇ | စာရင်းစစ် | `orders#8 = delivered`၊ stock `online_cancel +1` → `pos_sale −1` = **၁ ခါသာ** (on-hand 13)၊ Points **+22** (22,500 ÷ 1,000)၊ `audit_logs.pos_web_order_fulfilled` |

## ချို့ယွင်းချက် ၁ — Storefront မှတ်ပုံတင်ခြင်း/ဝင်ခြင်း ဆိုင်လွဲသွားခြင်း

**တိုင်းတာချက်:** store #3 storefront မှ `?store_slug=uat-full-feature` ဖြင့် မှတ်ပုံတင်ခဲ့သော
ဖောက်သည်သည် **store #1** ထဲ ရောက်သွားသည် (`store_user.store_id = 1`)။

**ရင်းမြစ် (၃ ချက်):**
1. Storefront header ရှိ အကောင့်ဝင်ရန်/မှတ်ပုံတင်ရန် လင့်များသည် `store_slug` **လုံးဝမပါ** —
   `/login`၊ `/register` သို့ တိုက်ရိုက်သွားသည်။
2. `/login` နှင့် `/register` သည် store ကို လမ်းကြောင်း (path) မှာမပါသဖြင့်
   `ResolveStoreContext` သည် body/query မှ ရှာသည် — form တွင် hidden field မပါလျှင်
   **primary store** သို့ ကျသည်။
3. Register/Login ပြီးနောက် ပြန်လည်ညွှန်းသည့်လမ်းကြောင်း (`redirect('/')` နှင့်
   `getPrimaryStore()`) သည်လည်း primary store ကိုသာ သိသည်။

**ပြုပြင်ချက်:** layout တွင် `$authQuery = ['store_slug' => $activeStoreSlug]` ဆောက်၍
လင့် ၅ ခုလုံးတွင်ထည့်ခြင်း၊ form ၂ ခုစလုံးတွင် hidden `store_slug` ထည့်ခြင်း၊ login/register
အချင်းချင်း လင့်များတွင်ပါ သယ်ခြင်း၊ RegisterController က **မှတ်ပုံတင်သည့်ဆိုင်၏ storefront** သို့
ပြန်ညွှန်းခြင်း၊ LoginController က လက်ရှိ store context (ဖောက်သည်အဖြစ် တကယ်ပါဝင်သည့်ဆိုင်)
ကို ဦးစားပေးခြင်း။

**ပြန်လည်စစ်ဆေးချက်:** `Ma UAT Online` (users#16) → **store #3**၊ landing page
`/store/uat-full-feature` ✓။

## ချို့ယွင်းချက် ၂ — Web အော်ဒါကို cart ထဲထည့်ချိန် ကုဒ်/လျှော့ငွေ ပျောက်ခြင်း

**တိုင်းတာချက်:** ဖောက်သည်မြင်သည့် အော်ဒါ = **22,500** (coupon −2,500)၊ သို့သော် POS
cart ထဲသို့ ဆွဲထည့်လျှင် **25,000** ဖြစ်နေသည် — JS သည် item တစ်ခုချင်းကို `/cart` သို့
ပို့ပြီး (ယနေ့ စျေးနှုန်းဖြင့် တွက်) ကုဒ်/လျှော့ငွေ လုံးဝ မသယ်ခဲ့ပါ။

**ပြုပြင်ချက် — Server က တန်ဖိုးချိန်သည် (`PosSaleService::loadWebOrder()`)**
- အော်ဒါ၏ လိုင်းများကို cart ထဲ ဆောက်၊ ဖောက်သည်ကို ချိတ်၊ ပြီးလျှင် **`agreed_amount`
  (ရှိလျှင်) သို့မဟုတ် `total_amount`** နှင့် ကိုက်ညီစေရန် ကွာခြားချက်ကို **manual
  discount** အဖြစ် သတ်မှတ်သည်။
- **Coupon ကို နောက်တစ်ခါ redemption မလုပ်ပါ** — အော်ဒါကိုယ်တိုင် `promotion_usages`
  + `used_count` ကို စားပြီးဖြစ်သဖြင့် ထပ်လုပ်လျှင် အရေအတွက် မှားပြီး per-customer
  ကန့်သတ်ချက်ရှိလျှင် ငြင်းပင် ဖြစ်နိုင်သည်။
- အော်ဒါတန်ဖိုး > ယနေ့စျေး ဖြစ်နေလျှင် လျှော့ငွေ **မထည့်** — ဖောက်သည်ကို ပိုမတောင်းပဲ
  ယနေ့စျေးဖြင့်သာ ရောင်းသည်။
- Endpoint အသစ်: `POST /store/{slug}/pos/web-orders/{order}/import`
  (`store.permission:pos_sales.update`)။

**နောက်ထပ် ချို့ယွင်းချက်တစ်ခု (တူညီသောလမ်းကြောင်း):** အတည်ပြုပြီး အော်ဒါသည် stock ကို
**ကိုင်ထား**သည်။ ဆိုင်တွင် ကျန်သည့်အရေအတွက်က အော်ဒါကိုင်ထားသည့် အရေအတွက်သာဖြစ်နေလျှင်
(ဥပမာ ၁ လုံးသာ ကျန်) ကောင်တာမှ ရောင်းလို့ **မရ**ခဲ့ပါ ("Insufficient stock")။ ယခု
**reservation ကို အရောင်းမတင်မီ ပြန်လွှတ်**သည် (တစ် transaction တည်းအတွင်း)။
ထို့အပြင် ပို့ဆောင်ပြီး/ပယ်ဖျက်ပြီး အော်ဒါကို ထပ်ရောင်းလျှင် ယခင်က တိတ်ဆိတ်စွာ
အရောင်းတင်ပြီး အော်ဒါကို မထိခဲ့သည် — ယခု **ရှင်းလင်းစွာ ငြင်း**သည် (ဖောက်သည်ကို
နှစ်ခါမတောင်းစေရန်)။

## ချို့ယွင်းချက် ၃ — အကောင့်ဝင်ထားသူ၏ storefront page ကို browser cache လုပ်ခြင်း

**တိုင်းတာချက်:** `CachePublicPage` သည် storefront GET စာမျက်နှာများကို
`private, max-age=60` + ETag ဖြင့် cache လုပ်သည် — အကောင့်ဝင်ထားသူအတွက် ရေးဆွဲသည့်
စာမျက်နှာတွင်ပါ (ဖောက်သည်အမည်၊ Points၊ session နှင့်ချိတ်ထားသည့် CSRF token)။
session အသစ်ဖြစ်ပြီးနောက် cache ထဲက စာမျက်နှာကို သုံးလျှင် POST အားလုံး
**419 Page Expired** ဖြစ်သည် (တိုင်းတာ: ကောင်တာ logout နှိပ်လျှင် 419 → အထွက်မဖြစ်)။
`SecurityHeaders` ကိုယ်တိုင် "stale copy leaks an expired nonce/token" ဟု ရေးထားပြီးသား
ဖြစ်သည် — opt-in က အကောင့်ဝင်ထားသူကိုပါ ဖုံးနေခဲ့သည်။

**ပြုပြင်ချက်:** `CachePublicPage` သည် **ဧည့်သည် (anonymous)** အတွက်သာ cache လုပ်သည်;
အကောင့်ဝင်ထားသူ၏ စာမျက်နှာများသည် `no-store` ပြန်ရသည်။

**ပြန်လည်စစ်ဆေးချက်:** admin/order POS စာမျက်နှာများ `Cache-Control: no-store, private`၊
ETag မရှိ ✓ (test ၂ ခုဖြင့် သော့ခတ်ထား — ဧည့်သည် cache ရဆဲ၊ အကောင့်ဝင်ထားသူ မရ)။

## Test နှင့် Suite

| ဖိုင် | Test | အကြောင်း |
|---|---|---|
| `tests/Feature/StorefrontAuthStoreContextTest.php` | 6 | storefront မှတ်ပုံတင်ခြင်း → ထိုဆိုင်ထဲ၊ landing မှန်၊ hidden store_slug၊ လင့်များ၊ membership guard |
| `tests/Feature/POS/WebOrderImportTest.php` | 15 | import endpoint: အော်ဒါတန်ဖိုး ချိန်ခြင်း၊ agreed_amount၊ ယနေ့စျေးနှုန်း၊ နောက်ဆိုင်ငြင်းခြင်း၊ ပို့ပြီးအော်ဒါ ငြင်းခြင်း၊ **နောက်ဆုံး ၁ လုံးဖြင့် အရောင်းတင်နိုင်ခြင်း** |
| `tests/Feature/StorefrontCachePolicyTest.php` | 2 | ဧည့်သည် cache ရ၊ အကောင့်ဝင်ထားသူ မရ |
| **စုစုပေါင်း အသစ်/ပြင်ဆင်** | **23** | fail-before: auth ၄/၆ ကျခဲ့သည် (fix ဖြုတ်စမ်း၍ သက်သေပြ) |

**Suite:** SQLite နှင့် MySQL နှစ်ခုလုံး — အောက်တွင် run မှတ်တမ်း။ i18n parity **5945 × ၃ ဘာသာ**။

## မလုပ်ရသေးသည် (ရိုးသားစွာ)

- **အော်ဒါနှင့် ပြေစာ တိုက်ရိုက်ချိတ်ဆက်မှု မရှိ** — ပို့ဆောင်ချိန်တွင် `audit_logs` တွင်
  ပြေစာနံပါတ် ရေးထားသည်၊ သို့သော် `orders` တွင် `pos_sale_id` ကော်လံ မရှိသဖြင့်
  အော်ဒါစာမျက်နှာမှ ပြေစာကို တိုက်ရိုက် ဖွင့်၍မရပါ။ (အကြံပြု နောက်ဆက်တွဲ)
- ကောင်တာတွင် ပေးချေပြီးသော်လည်း `orders.payment_status` သည် `unpaid` အတိုင်း
  ကျန်သည် (staff ကိုယ်တိုင် `paid` သတ်မှတ်ရသည်)။
- အွန်လိုင်း checkout တွင် Points သုံးခြင်း မပါ၊ customer chip တွင် Points balance မပြ။
- BOGO တွင် အကောင်းဆုံးတွဲဖက်ရွေးချယ်မှု မပါ။
- ပရင်တာ/ESC-POS လက်တွေ့၊ eload API၊ SMS/Viber/Telegram အပို့၊ offline sync၊
  theme publish/rollback၊ content CRUD၊ backup restore — မစမ်းရသေးပါ။
