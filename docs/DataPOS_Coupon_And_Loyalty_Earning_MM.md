# DataPOS — Coupon Redemption & Loyalty Points Earning (2026-09-19)

လက်တွေ့ UAT (2026-09-18) တွင် တွေ့ခဲ့သော **feature gap ၂ ခု** ကို ဖြည့်ဆည်းသည့် မှတ်တမ်း —
Promotion/Coupon ကို ကောင်တာတွင် အသုံးပြုနိုင်ခြင်း နှင့် Loyalty Points ကို အရောင်းမှ
အလိုအလျောက် ရရှိခြင်း။ **Schema/migration အသစ် မလိုအပ်ပါ** — ဇယားကော်လံများ
(`pos_sales.promotion_id`/`coupon_code`, `promotion_usages`, `loyalty_point_transactions.pos_sale_id`,
`storefront_settings.pos_settings`) အားလုံး ဒီဇိုင်းထားပြီးသား ဖြစ်ပြီး **မချိတ်ဆက်ရသေး**ခဲ့ခြင်းသာ။

---

## 1. Coupon — ကောင်တာတွင် သုံးနိုင်ပြီ

**ဘယ်လိုလုပ်လဲ:** POS ၏ လျှော့စျေး modal တွင် Coupon ကုဒ် အကွက်။ ကုဒ်ကို server က
လက်ရှိ ဈေးခြင်းနှင့် ချိန်စစ်ပြီး **cart ပေါ်တွင် သိမ်းထား**သည် (manual လျှော့စျေးနှင့် တစ်နေရာတည်း
session ထဲ) — ထို့ကြောင့် ဈေးခြင်းကတ်၊ ငွေရှင်းမော်ဒယ်၊ ပြေစာ အားလုံးတွင် **လျှော့ပြီးသား
စုစုပေါင်း** ကို အလိုအလျောက် ပြသည်။ Redemption ကို အရောင်းစာရင်းသွင်းသည့် transaction ထဲတွင်
တစ်ခါတည်း ရေးသည် (usage ledger + `used_count`)။

| စည်းကမ်း | အပြုအမူ |
|---|---|
| ကုဒ် ရှာမတွေ့ / ပိတ်ထား / မစရသေး / သက်တမ်းကုန် | ငြင်း + အကြောင်းရင်းပြ (၃ ဘာသာ) |
| စုစုပေါင်း အသုံးအရေအတွက် ပြည့် | ငြင်း |
| ဖောက်သည်တစ်ဦး ကန့်သတ်ချက် ပြည့် | ငြင်း |
| အနည်းဆုံးဝယ်ရန် သတ်မှတ်ချက် မပြည့် | ငြင်း (လိုအပ်သည့်ပမာဏ ပြသ) |
| **ကုန်ပစ္စည်း/အမျိုးအစား ကန့်သတ် promo** | **သက်ဆိုင်သည့် ပစ္စည်းအပိုင်းကိုသာ** တွက် ("charger 10%" → ဈေးခြင်းတစ်ခုလုံး 10% မဖြစ်) |
| BOGO | ငြင်း (ကောင်တာတွင် line-item BOGO မရသေးသောကြောင့် — တိတ်ဆိတ်စွာ 0 လျှော့ခြင်းထက် ရှင်းလင်းစွာ ငြင်းခြင်းက ပိုမှန်) |
| Manual လျှော့စျေး + Coupon | ပေါင်းပြီး စုစုပေါင်းကို မကျော်စေရ (cap) |

**လက်တွေ့ စမ်းသပ်မှု (MySQL, store #3):**
```
Coupon UAT 10% Charger Promo ဖြင့် −2,500 Ks လျှော့ပြီးပါပြီ
sale: subtotal 25,000 · discount 2,500 · total 22,500 · promotion_id 1 · coupon_code UAT10
promotion_usages: 1 row (sale 7, discount 2,500, customer 14) · promotions.used_count = 1
ပြေစာ: "လျှော့ဈေး − 2,500 Ks / Coupon ကုဒ်: UAT10 / စုစုပေါင်း 22,500 Ks"
```

## 2. Loyalty Points — အရောင်းမှ ရနိုင်ပြီ

**စည်းကမ်း (ဆိုင်ဆက်တင်တွင် သတ်မှတ်):** `pos_settings.loyalty_amount_per_point` = Points ၁ မှတ်ရရန်
မည်မျှဝယ်ရမည် (ဥပမာ 1000 = 1,000 ကျပ်လျှင် 1 မှတ်; **0 = ပိတ်**)။
`capability: commerce.loyalty_points` မရှိလျှင် လုံးဝ မရ။

- **ရမှတ်:** `floor(စုစုပေါင်း ÷ rate × tier multiplier)` — floor ကြောင့် မရသေးသည့်ငွေအတွက်
  Points အပိုမရ; bcmath ဖြင့်သာ တွက်။
- **အကြွေးဖြင့်ဝယ်လည်း ရသည်** (စုစုပေါင်းအပေါ် အခြေခံ)၊ သို့သော် **ငွေပြန်အမ်းလျှင် အချိုးကျ ပြန်ဖြတ်**သည်။
- **Tier multiplier:** ဖောက်သည်လက်ရှိ tier ၏ `point_multiplier` (Silver 1.2×, Gold 1.5×, Platinum 2×)။
- **Spending:** ဝယ်သည့်ငွေကို `store_user.total_spent` တွင် ပေါင်း (tier တွက်ရန် အခြေခံ)။
- **Tier အလိုအလျောက် တက်:** spending က တိုးလာလျှင် ကိုက်ညီသော tier သို့ အလိုအလျောက် တင်
  (**တက်ရုံသာ** — refund ကြောင့် အလိုအလျောက် ပြန်ချမည် မဟုတ်၊ လူက သတ်မှတ်ထားသည့် tier ကို မဖျက်ပါ)။
- **ငွေပြန်အမ်း:** အချိုးကျ ပြန်ဖြတ် (`floor(earned × refunded ÷ sale total)`) — တစ်စိတ်တစ်ပိုင်း
  ပြန်အမ်းလျှင် ထိုအပိုင်းအတွက် Points သာ ပြန်ဖြတ်; balance သည် ဘယ်တော့မှ အောက် မရောက်။
- **Idempotent:** အရောင်းတစ်ခုအတွက် တစ်ခါသာ ရေးသည် (retry လုပ်လျှင် ထပ်မရ)။

**လက်တွေ့ စမ်းသပ်မှု (MySQL, store #3):**
```
sale RCP-20260919-0001 — 22,500 Ks (coupon ပြီးနောက်)
loyalty_point_transactions: type=bonus · points=22 · balance_after=72 · pos_sale_id=7
                             note="ပြေစာ RCP-20260919-0001 မှ ရရှိသည့် Points"
store_user: loyalty_points 72 (manual +50 + 22) · total_spent 22,500 · tier → Standard (min 0)
ပြေစာ: "ရရှိသည့် Points — 22 · Points လက်ကျန်: 72"
```

## 3. ပြင်ဆင်ထားသော ဖိုင်များ

```
app/POS/Services/PromotionService.php         validateCouponDecimal(), redeem(), checkCoupon(), findByCode()
app/POS/Services/PosSaleService.php           session coupon (set/get/clear), previewCoupon(),
                                              eligibleSubtotalFor(), post() discount + promotion_id/coupon_code
app/POS/Services/PosReturnService.php         refund → loyalty reversal
app/POS/Services/MembershipLoyaltyService.php earningRate/isEarningEnabled/pointsForAmount,
                                              accrueForSale, reverseForReturn, syncTierForSpending
app/POS/Http/Controllers/PosSaleController.php setCoupon(), post() coupon_code
app/Http/Controllers/Admin/StoreSettingController.php  loyalty_amount_per_point
routes/web.php                                 POST /pos/cart/coupon
resources/js/app-admin.js                      applyCoupon()/clearCoupon() + state
resources/views/pos/partials/modal-quick-tools.blade.php  coupon field in the discount modal
resources/views/pos/receipt.blade.php          coupon code + points lines
resources/views/admin/settings/sections/pos.blade.php     loyalty rate field
lang/{my,en,zh_CN}/messages.php                key ၂၂ ခု (parity 5931 ×၃)
tests/Feature/POS/PosCouponAndLoyaltyTest.php  test ၂၃ ခု
tests/Feature/AdminSidebarNavigationUXTest.php aria-label assertions ကို အသစ်နှင့် ကိုက်အောင်
```

## 4. Test များ

| အချက် | ရလဒ် |
|---|---|
| `PosCouponAndLoyaltyTest` | **23/23 PASS** (74 assertions) |
| Fail-before | fix မတိုင်မီ **12 errors + 7 failures** (feature မရှိသေး) |
| POS test အုပ်စုလုံး | **589 PASS** |
| Suite အပြည့် | SQLite **2111+ PASS**၊ MySQL — အောက်တွင် |

> **မှတ်ချက် (တခြား agent ၏ commit):** `AdminSidebarNavigationUXTest` ရှိ test ၄ ခုသည်
> `9df4386` (Admin sidebar toggle ကို တစ်ခုတည်း ပေါင်းခြင်း) ကြောင့် **ကျွန်ုပ်၏ အလုပ်မတိုင်မီကတည်းက**
> ကျနေခဲ့သည် (clean HEAD တွင် run စမ်း၍ အတည်ပြု)။ aria-label များကို ဘာသာပြန် + Alpine binding
> ဖြစ်လာသဖြင့် assertion များကို **ရည်ရွယ်ချက် မပျောက်စေဘဲ** အသစ်နှင့် ကိုက်အောင် ပြင်ထားသည်။

## 5. မလုပ်ရသေးသည် (ရိုးသားစွာ)

- **အွန်လိုင်း (storefront) checkout တွင် coupon** — `orders` ဇယားတွင် coupon ကော်လံ မရှိသေးသဖြင့်
  migration လိုအပ်သည်။ ဤ pass သည် **ကောင်တာ (POS)** အတွက်သာ။
- **Points ဖြင့် ပြန်လဲခြင်း (redeem)** — admin မှ manual ဖြတ်နိုင်သည်; POS တွင် Points နှင့်
  ငွေလျှော့ခြင်း မရသေးပါ။
- **BOGO** ကို ကောင်တာတွင် ပံ့ပိုးမထားပါ (ငြင်းပြသည်)။
- ပရင်တာ/ESC-POS လက်တွေ့စမ်းသပ်ခြင်း (print preview သာ)။
