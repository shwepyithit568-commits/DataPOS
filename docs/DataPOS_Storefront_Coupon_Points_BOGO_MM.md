# DataPOS — Storefront Coupon, Points Redemption & BOGO (2026-09-19)

2026-09-19 ညပိုင်း pass — 2026-09-18 UAT တွင် "မလုပ်ရသေးသည်" ဟု စာရင်းတင်ထားခဲ့သော
အချက်များကို ဖြည့်ဆည်းခြင်း။

| အချက် | ယခင် | ယခု |
|---|---|---|
| အွန်လိုင်း (storefront) checkout တွင် coupon | ❌ (`orders` ဇယားတွင် column မရှိ) | ✅ |
| Points ကို ငွေလျှော့အဖြစ် သုံးခြင်း (POS) | ❌ (လူက manual ဖြတ်ရုံသာ) | ✅ |
| BOGO (buy one get one) ကောင်တာ | ❌ (ငြင်းပြခဲ့သည်) | ✅ (POS + storefront နှစ်ခုလုံး) |

---

## 1. အွန်လိုင်း အော်ဒါတွင် Coupon

**Migration:** `2026_09_19_000001_add_coupon_columns_to_orders.php`
- `orders.discount_amount` (decimal 12,2, default 0)၊ `orders.coupon_code` (varchar 40, nullable)၊
  `orders.promotion_id` (nullable)
- `promotion_usages.order_id` (nullable) — ယခင် `pos_sale_id` သာ ရှိသဖြင့် အွန်လိုင်း redemption
  သည် provenance မရှိခဲ့ပါ။
- ကော်လံအားလုံး additive + default ရှိသဖြင့် **ရှိပြီးသား အော်ဒါများ၏ total မပြောင်းလဲပါ**။

**စည်းကမ်း:** order builder တွင် coupon ကုဒ် ရိုက်ထည့်၍ "သုံးမည်" နှိပ်လျှင် server က
လက်ရှိ စာရင်းနှင့် ချိန်စစ်ပြီး (scope/BOGO/ကန့်သတ်ချက်အားလုံး) လျှော့ငွေကို ပြသည်။
အော်ဒါတင်ချိန်တွင် **ပြန်စစ်**ပြီး မရနိုင်ပါက အော်ဒါကို **မဖန်တီးဘဲ** အကြောင်းရင်း ပြသည်
(ဖောက်သည်က ရည်ရွယ်ချက်ရှိရှိ ရိုက်ထည့်ထားသဖြင့် တိတ်ဆိတ်စွာ လျစ်လျူရှုခြင်းထက် ရှင်းလင်းစွာ ပြောခြင်းက မှန်)။
Redemption ledger ကို အော်ဒါနှင့် တစ်ခါတည်း (transaction အတွင်း) ရေးသည်။

**Endpoint:** `POST /store/{store_slug}/orders/coupon` — `code` + `items_json` → `{valid, discount, message}`
(မည်သည့် အရာကိုမှ သိမ်း/မှတ်ခြင်း မပြု)။

**ပြသခြင်း:** order builder ၏ စုစုပေါင်းတွင် "Coupon (CODE): −X" လိုင်း၊ confirmation page နှင့်
admin order စာမျက်နှာတွင်လည်း ပြသည်။ `order_number` စာသားများတွင် total က လျှော့ပြီးသား
ပမာဏ ဖြစ်သည်။

## 2. Points ကို ငွေလျှော့အဖြစ် သုံးခြင်း (POS)

**ဆိုင်ဆက်တင် → POS:** `loyalty_point_value` = Points ၁ မှတ်၏ တန်ဖိုး (ကျပ်)၊ **0 = သုံး၍မရ**။
(ရရှိမှု နှုန်း `loyalty_amount_per_point` နှင့် **သီးခြား** — ဥပမာ ၁,၀၀၀ ကျပ်လျှင် ၁ မှတ်ရ၊
၁ မှတ် = ၁၀ ကျပ် ပြန်သုံး။)

**စည်းကမ်း (server က အမြဲ ထိန်း):**
- ရရှိနိုင်သည့် အများဆုံး = min(ဖောက်သည်လက်ကျန်, ဘောက်ချာတွင် ကျန်ရှိသည့်ငွေ ÷ တန်ဖိုး)
  → **ဆိုင်က ဖောက်သည်ကို ငွေပြန်အမ်းရမည့် အခြေအနေ ဘယ်တော့မှ မဖြစ်**။
- သုံးလိုက်သည့် Points များကို `loyalty_point_transactions` တွင် `redeemed` အမျိုးအစားနှင့်
  အော်ဒါ/ဘောက်ချာ reference နှင့်တကွ မှတ်သည်။
- **ငွေပြန်အမ်းလျှင် သုံးလိုက်သည့် Points ကို အချိုးကျ ပြန်ပေး**သည် (ရရှိသည့် Points ကို
  အချိုးကျ ပြန်ဖြတ်သလိုပင်) — မပြန်ပေးပါက ငွေပြန်အမ်းလိုက်တာနဲ့ Points ပျောက်သွားမည်။

**UI:** POS လျှော့စျေး modal တွင် "Loyalty Points" အကန့် — ရရှိနိုင်သည့် အများဆုံး ပြသ၊
ကိုယ်တိုင်ရိုက်ထည့်နိုင်သည်၊ "အများဆုံး သုံးမည်" ခလုတ်ရှိသည်။ ဈေးခြင်းတန်းများနှင့်
ငွေရှင်းမော်ဒယ်တွင် လျှော့ပြီးသား စုစုပေါင်း ပေါ်သည်။

## 3. BOGO — နှစ်လမ်းကြောင်းလုံးတွင်

Promotion စျေးတွက်ခြင်းကို **နေရာတစ်ခုတည်း** (`PromotionService::quoteLines()`) သို့ ရွှေ့ခဲ့သည် —
POS နှင့် storefront နှစ်ခုလုံး ထိုတစ်ခုတည်းသော စည်းကမ်းကို သုံးသည်။

- `percent_off` / `flat_off`: scope (ကုန်ပစ္စည်း/အမျိုးအစား) ကိုက်ညီသည့် **လိုင်းများပေါ်တွင်သာ** တွက်
- `bogo`: ကိုက်ညီသည့်လိုင်း၏ **နှစ်လုံးမြောက်တစ်လုံးစီ အခမဲ့** (`floor(qty ÷ 2)` × unit price)
- ဘယ်လိုင်းမှ မကိုက်ညီပါက `coupon_not_applicable` ဖြင့် ငြင်း
- **line မပါသော** (စုစုပေါင်းတစ်ခုတည်းသာ ရသည့်) validator path တွင် BOGO ကို
  `coupon_type_unsupported` ဖြင့် ငြင်းပြသည် — ခန့်မှန်း၍ ဂဏန်းမထုတ်ပါ။

## 4. လက်တွေ့ စမ်းသပ်တွင် တွေ့ရှိသည့် ချို့ယွင်းချက် — လျှော့စျေး နှစ်ခါ ဖြတ်ခြင်း

**အခြေအနေ:** ငွေရှင်းမော်ဒယ် (payment modal) က `discount` အကွက်တွင် **စုစုပေါင်း လျှော့ငွေ**
(manual + coupon + points) ကို ပြန်ပို့သည်။ Server က coupon နှင့် points ကို လိုင်းများမှ
**ပြန်တွက်**၍ တစ်ဖန်ထပ်ပေါင်းသဖြင့် နှစ်ခါ ဖြတ်ခဲ့သည်။

**တိုင်းတာချက် (စစ်မှန်သော ဖြစ်ရပ်):** ကောင်တာတွင် 25,000 ဘောက်ချာ၊ Points 50 (= 500 Ks) —
မျက်နှာပြင်တွင် စုစုပေါင်း **24,500** ပြသည်၊ သို့သော် သိမ်းလိုက်သည့် အရောင်းမှာ
**total 24,000 / discount 1,000** (RCP-20260919-0002) — ဖောက်သည်မြင်သည့်ငွေနှင့် စာရင်း မကိုက်။
Coupon တစ်ခုတည်း ရှိချိန်တွင်လည်း အလားတူ ဖြစ်နိုင်သည် (UI က စုစုပေါင်းကို ပြန်ပို့လျှင် ၂ ခါ)။

**ပြုပြင်ချက် — တစ်ခုတည်းသော အမှန်တရား (single source of truth):**
- `PosSaleService::cartTotals()` တွင် **`manual_discount`** ကို သီးခြား ထုတ်ပြသည်။
  `discount` က စုစုပေါင်း (manual + coupon + points) အတိုင်း ဆက်ရှိသည် — ပြသရန်။
- ငွေရှင်းမော်ဒယ်က **manual_discount** ကိုသာ ပြန်ပို့သည်; coupon/points ကို server က
  ပြန်တွက်၍ တစ်ခါသာ ပေါင်းသည်။
- လျှော့စျေးမော်ဒယ် ဖွင့်ချိန် prefill နှင့် "ဖျက်မည်" ခလုတ်လည်း manual discount ကိုသာ
  ကြည့်သည် (coupon/points ရှိရုံနှင့် ဖျက်လို့မရသည့် ခလုတ် မပေါ်တော့)။

**ပြန်လည်စစ်ဆေးချက် (RCP-20260919-0003):** ဘောက်ချာ 25,000၊ coupon UAT10 −2,500၊
Points 40 (= 400 Ks) → မျက်နှာပြင်တွင် 22,100၊ သိမ်းလျှင် **total 22,100 / discount 2,900 /
coupon UAT10**၊ cash 22,100 change 0၊ Points ledger `redeemed −40` (46 → 6) ပြီး
`bonus +22` (6 → 28; 22,100 ÷ 1,000) — မျက်နှာပြင်နှင့် စာရင်း **အပြည့်အဝ ကိုက်ညီ**။
(ယခင် RCP-20260919-0002 သည် UAT ဒေတာအဖြစ် ကျန်ရှိသည် — payment row က cash 24,500 /
change 500 ဖြစ်သဖြင့် လက်ကျန်ငွေနှင့် drawer ကိုယ်တိုင် ကိုက်ညီနေသည်၊ ပြန်မဖျက်ပါ။)

## 5. လက်တွေ့ (live) စမ်းသပ်ချက် — မျက်နှာပြင်နှင့် စာရင်း ကိုက်ညီမှု

`datapos_uat` (store #3 `uat-full-feature`) ပေါ်တွင် browser မှ အစအဆုံး စမ်းခဲ့သည်။

**အွန်လိုင်း coupon (order builder → confirmation):**
- ကုန်ပစ္စည်း 25,000 ၊ coupon `UAT10` (Charger အတွက် 10%) → မျက်နှာပြင် 22,500
- `orders`: total **22,500.00**, `discount_amount` **2,500.00**, `coupon_code` UAT10, `promotion_id` 1
- `promotion_usages`: `order_id = 6` (POS sale မဟုတ်)၊ `discount_applied` 2,500 — provenance မှန်
- `promotions.used_count` = 3 (POS ၂ + အွန်လိုင်း ၁ — ကန့်သတ်ချက် နှစ်လမ်းစလုံး ရေတွက်)

**POS Points သုံးခြင်း:** အထက်ပိုင်း အပိုင်း ၄ တွင် ဖော်ပြထားသည့် RCP-20260919-0003
(22,100 = 25,000 − 2,500 coupon − 400 points) — မျက်နှာပြင်၊ သိမ်းသည့်စာရင်း၊ ledger သုံးခု ကိုက်။

## 6. Test များ

| ဖိုင် | Test | အကြောင်း |
|---|---|---|
| `tests/Feature/POS/PosCouponAndLoyaltyTest.php` | 33 | coupon စည်းကမ်းအားလုံး၊ scope၊ BOGO (၃ မျိုး)၊ ရရှိမှု၊ tier၊ ငွေပြန်အမ်း၊ **Points သုံးခြင်း (၅ မျိုး)**၊ **လျှော့စျေး နှစ်ခါမဖြတ်စေရေး (၃ မျိုး)** |
| `tests/Feature/StorefrontOrderCouponTest.php` | 10 | အွန်လိုင်း coupon: လျှော့ငွေ၊ scope၊ expired၊ per-customer၊ BOGO၊ preview endpoint |
| **စုစုပေါင်း အသစ်** | **43** | fail-before သက်သေပြထား |

နှစ်ခါဖြတ်ခြင်း ပြုပြင်မှုအတွက် ကာကွယ်သည့် test ၃ ခု:
1. `test_the_posted_manual_discount_charges_the_coupon_and_the_points_exactly_once` —
   checkout ပို့သည့်အတိုင်း post လုပ်ပြီး **မျက်နှာပြင်တွင်ပြသည့် total = သိမ်းသည့် total** ကို စစ်သည်။
2. `test_the_cart_totals_separate_the_manual_discount_from_the_coupon_and_points` —
   `manual_discount` / `coupon_discount` / `points_value` သုံးခု သီးခြားရှိမှု (UI မှီခိုသည့် ကတိ)။
3. `test_the_checkout_ui_sends_the_manual_discount_back` — template/JS က manual_discount ကိုသာ
   ပြန်ပို့မှု (template သည် browser ထဲမှသာ အလုပ်လုပ်သဖြင့် static guard လိုသည်)။

**Suite:** SQLite **2132 tests / 9772 assertions — OK**၊ MySQL (datapos_points_test, MariaDB 10.4.32)
**2132 tests / 9772 assertions — OK** (fix အပြီး နှစ်ခုလုံး ပြန်လည် run) ။ i18n parity **5943 × ၃ ဘာသာ**။

## 7. မလုပ်ရသေးသည် (ရိုးသားစွာ)

- **POS မှာ ဖောက်သည်ကို Points လက်ကျန်ပြသခြင်း** (cart panel ရှိ customer chip) — လျှော့စျေး
  modal တွင် ရရှိနိုင်သည့် အများဆုံး ပြသနေပြီ၊ သို့သော် chip တွင် balance မပြသေး။
- **အွန်လိုင်း checkout တွင် Points သုံးခြင်း** (coupon သာ ရသည်)။
- BOGO ၏ "အခမဲ့ယူနစ်" သည် unit price အခြေခံဖြင့်သာ (တူညီသည့်လိုင်းအတွင်း အကောင်းဆုံး
  တွဲဖက်ရွေးချယ်မှု မပါ)။
- ပရင်တာ/ESC-POS လက်တွေ့၊ eload ပြင်ပ API၊ SMS/Viber/Telegram အပို့၊ offline sync —
  မစမ်းရသေးပါ။
