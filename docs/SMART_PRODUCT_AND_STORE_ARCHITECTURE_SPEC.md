# DataPOS — Smart Product & Store Architecture Specification
**Master Architecture & Implementation Blueprint for Multi-Vertical POS & Inventory Management**

---

## ၁။ ရည်ရွယ်ချက် (Executive Summary & Goals)

DataPOS ကို မိုဘိုင်းဖုန်း၊ ကွန်ပျူတာ၊ CCTV၊ အပိုပစ္စည်း အရောင်းနှင့် ဝန်ဆောင်မှုဆိုင်များ (Alinn Thit ကဲ့သို့ Tech Business များ) အပြင် အထွေထွေကုန်စုံဆိုင်၊ အဝတ်အထည်ဆိုင်၊ ဝန်ဆောင်မှုလုပ်ငန်းစသည့် **လုပ်ငန်းအမျိုးအစားစုံ (Multi-Vertical Industry)** တွင် အမှားအယွင်းကင်းရှင်းစွာ အသုံးပြုနိုင်စေရန် အဆင့် ၂ ဆင့် စနစ် (2-Tier Architecture) ဖြင့် ဖွဲ့စည်းတည်ဆောက်မည် ဖြစ်သည်။

အဓိက ဦးတည်ချက်များမှာ-
1. **Zero Data-Entry Errors:** SKU နှင့် ကုန်ပစ္စည်းအမည် (Product Name) ရိုက်သွင်းရာတွင် ဝန်ထမ်းအချင်းချင်း စာလုံးပေါင်းမှားခြင်း၊ Format ကွဲလွဲခြင်း လုံးဝမရှိစေဘဲ အလိုအလျောက် သန့်ရှင်းစွာ ထွက်ပေါ်လာစေရန်။
2. **2-Tier Architecture (Store-Level Preset + Product-Level Switcher):** ဆိုင်အမျိုးအစားအလိုက် လိုအပ်သော Feature များကိုသာ အလိုအလျောက် ပွင့်စေပြီး မျက်စိရှင်းစေရန်။
3. **Hardware Barcode Scanner Ready:** ကုန်ပစ္စည်းဘူးပေါ်ရှိ မူရင်း Barcode များကို စက်ဖြင့် တိုက်ရိုက် Scan ဖတ်ပြီး ရောင်းချ/လက်ခံနိုင်ရန်။
4. **Warehouse & Shelf/Bin Location:** ပစ္စည်းငယ်များ (ဥပမာ - IC, Touch Screen, Back Cover) ကို ဆိုင်ထဲ/ဂိုဒေါင်ထဲ၌ စက္ကန့်ပိုင်းအတွင်း ရှာဖွေနိုင်ရန် စင်နံပါတ် (Shelf Location) စနစ် ချိတ်ဆက်ရန်။

---

## ၂။ Tier 1: Store Setup & Industry Presets (ဆိုင်အဆင့် သတ်မှတ်ချက်များ)

ဆိုင်အသစ်တစ်ခု ဖန်တီးသည့်အခါ သို့မဟုတ် Store Settings တွင် **Business Category (ဆိုင်အမျိုးအစား)** ကို ရွေးချယ်နိုင်မည် ဖြစ်သည်။

```
┌────────────────────────────────────────────────────────────────────────┐
│                        STORE BUSINESS PRESETS                          │
├─────────────────────────┬──────────────────────────────────────────────┤
│ 1. Mobile & Tech Shop   │ • Repair Center, Glass Finder, IMEI Tracking  │
│ (Alinn Thit Standard)   │ • Auto SKU: [BRAND]-[MODEL]-[TYPE]-[VAR]     │
│                         │ • Default Categories: Cable, Charger, Touch  │
├─────────────────────────┼──────────────────────────────────────────────┤
│ 2. General Retail       │ • Glass Finder & Repair များကို ဖျောက်ထားမည်    │
│    (ကုန်စုံ / Mart)      │ • Barcode & Expiry Date ကို ဦးစားပေးမည်     │
├─────────────────────────┼──────────────────────────────────────────────┤
│ 3. Fashion & Clothing   │ • Color & Size Matrix Variants ကို အဓိကထားမည်│
├─────────────────────────┼──────────────────────────────────────────────┤
│ 4. Service / Repair Lab │ • Job Ticketing, Labor Fee, Spare Part Use   │
└─────────────────────────┴──────────────────────────────────────────────┘
```

---

## ၃။ Tier 2: Product Type Switcher (ပစ္စည်းအမျိုးအစား ခွဲခြားမှု)

Product Create & Edit စာမျက်နှာ ထိပ်ဆုံးတွင် ကုန်ပစ္စည်းအမျိုးအစား ၄ မျိုး ရွေးချယ်နိုင်သော **Option Switcher** ပါဝင်မည်-

1. 📦 **Standard Physical Product (ရိုးရိုးကုန်ပစ္စည်း):**
   * သာမန် Barcode၊ ဝယ်စျေး/ရောင်းစျေး၊ အရေအတွက် (Stock Quantity) သာ လိုအပ်သော ပစ္စည်းများ (ဥပမာ - ဖန်သားပြင်ကပ်မှန်၊ ကာဗာ)။
2. 📱 **Serialized / IMEI Product (နံပါတ်ပါ ပစ္စည်း):**
   * စမတ်ဖုန်း၊ တက်ဘလက်၊ Laptop၊ CCTV DVR ကဲ့သို့ IMEI/Serial Number သီးခြားမှတ်သားရမည့် ပစ္စည်းများ (Warranty & Serial Tracker အကွက်များ ပွင့်လာမည်)။
3. 🔀 **Variant / Matrix Product (အမျိုးအစားကွဲ ပစ္စည်း):**
   * အားသွင်းကြိုး (Micro / Type-C / Lightning)၊ Earphone (Black / White) သို့မဟုတ် Storage ကွဲပြားသော ပစ္စည်းများ (Live Variant Matrix ပွင့်လာမည်)။
4. 🛠️ **Service / Labor Item (ဝန်ဆောင်မှုနှင့် လက်ခ):**
   * ဆော့ဝဲလ်တင်ခ၊ မှန်လဲလက်ခ၊ လိုင်းဆွဲခ (Stock လက်ကျန် လျှော့စရာမလိုဘဲ ငွေလက်ခံနိုင်မည်)။

---

## ၄။ Smart Auto-Generator Engine (SKU & Name ထုတ်ပေးသည့် စနစ်)

### (က) Toggle Switch စနစ်
* **[Auto-Generate: ON]** ➔ Master Data ရှိ Code များဖြင့် SKU နှင့် Name ကို Real-time အလိုအလျောက် တည်ဆောက်ပေးသည်။
* **[Auto-Generate: OFF]** ➔ မိမိစိတ်ကြိုက် Product Name နှင့် SKU ကို Manual ရိုက်ထည့်နိုင်သည်။

### (ခ) Master Data Architecture (Codes Mapping)
Alinn Thit Mobile Shop SKU Master Logic အရ အောက်ပါ Master Tables များနှင့် ချိတ်ဆက်မည်-

* **Brand Code:** `168`, `BVT`, `AB`, `DENMEN`, `CAR`, `HOCO`, `REMAX`, etc.
* **Product Type Code:**
  * `CB` = Cable
  * `CH` = Charger
  * `CHS` = Charger Set
  * `CCH` = Car Charger
  * `EP` = Earphone
  * `BEP` = Bluetooth Earphone
  * `SPK` = Speaker
  * `PB` = Power Bank
  * `SG` = Screen Protector
  * `TL` = Touch LCD
  * `TS` = Touch Screen
  * `GLS` = Glass
  * `CV` = Phone Case
  * `BC` = Back Cover
* **Connector / Extra / Variant Code:**
  * `MC` = Micro USB
  * `TC` = Type-C
  * `IP` = Lightning (iPhone)
  * `OTG` = OTG Adapter
* **Color Code:**
  * `BLK` = Black, `WHT` = White, `BLU` = Blue, `RED` = Red, etc.

### (ဂ) Generation Logic & Formulas

```
1. SKU Formula:
   [BRAND_CODE] - [MODEL_CODE] - [TYPE_CODE] - [EXTRA/CONNECTOR] - [COLOR]
   ဥပမာ: 168-L009-CB-MC-BLK

2. Product Name Formula (သန့်ရှင်းသော Customer Name):
   [MODEL_CODE] + [PRODUCT_TYPE_NAME]
   ဥပမာ: L009 Cable (Color နှင့် Connector ကို Variant ထဲတွင်သာ သီးခြားခွဲထားမည်)
   
3. Model-specific Exceptions:
   Note 14 5G Screen Protector (Pro, 5G, Plus ပါက Name ထဲတွင် ထည့်သွင်းမည်)
```

---

## ၅။ Barcode Scanner & POS Hardware Integration

* **Manufacturer Barcode (UPC/EAN):** ကုန်ပစ္စည်းဘူးပေါ်တွင် မူလပါရှိသော Barcode ကို Barcode Reader Gun ဖြင့် စကန်ဖတ်ရုံဖြင့် Form ထဲသို့ Auto ဖြည့်ပေးမည်။
* **Dual Identifiers:** ကုန်ပစ္စည်းတစ်ခုတွင် `SKU` (ဆိုင်တွင်း ကုဒ်) နှင့် `Barcode` (စက်ဖတ်ကုဒ်) နှစ်မျိုးစလုံး သီးခြားစီ တည်ရှိနိုင်မည်။
* **POS Quick Find:** POS ကောင်တာတွင် SKU ရိုက်ရှာသည်ဖြစ်စေ၊ Barcode Gun ဖြင့် Scan ဖတ်သည်ဖြစ်စေ စက္ကန့်ပိုင်းအတွင်း ချက်ချင်း Add to Cart ပြုလုပ်ပေးမည်။

---

## ၆။ Multi-Warehouse & Shelf / Bin Location (စင်နေရာ သတ်မှတ်ခြင်း)

ဖုန်းအပိုပစ္စည်းနှင့် လက်လီပစ္စည်းများ ရှာဖွေရ လွယ်ကူစေရန်-

1. **Warehouse Selector:**
   * ဆိုင်ရှေ့ အရောင်းကောင်တာ (Main Store Display)
   * ဆိုင်နောက် ဂိုဒေါင် (Backroom Storage)
   * ဒုတိယထပ် ဂိုဒေါင် (Upstairs Storage)
2. **Shelf / Bin Location (စင်/အကွက် နံပါတ်):**
   * ဥပမာ - `Rack-A1`, `Shelf-02`, `Box-B4`, `Bin-09`
3. **Operational Visibility:**
   * POS စာရင်းတွင် ပစ္စည်းအောက်၌ `[📍 Box-B4 / Shelf-02]` ဟု ဖော်ပြပေးထားသဖြင့် အရောင်းဝန်ထမ်းအသစ်များ အလွယ်တကူ သွားယူနိုင်မည်။
   * Stock Adjustment နှင့် Daily Check စစ်ဆေးသည့်အခါ စင်အလိုက် (By Shelf) စာရင်းစစ်နိုင်မည်။

---

## ၇။ အကောင်အထည်ဖော်မည့် အဆင့်ဆင့် အစီအစဉ် (Implementation Phases)

### Phase 1: Database & Model Preparation
* `products` table တွင် `product_type`, `shelf_location`, `barcode`, `specs` (JSON), `compatible_models` fields များ စစ်ဆေး/ချိတ်ဆက်ခြင်း။
* Master Data ဇယားများ (`brands`, `categories`, `variant_presets`) တွင် `code` prefix စနစ်များ ပိုမိုခိုင်မာအောင် ဖြည့်ဆည်းခြင်း။

### Phase 2: Product Create/Edit Form Engine (UI/UX)
* `_form.blade.php` တွင် **Product Type Switcher (၄ မျိုး)** ထည့်သွင်းခြင်း။
* **Smart Auto-Generate SKU & Name Engine** (Alpine.js Realtime Preview) တည်ဆောက်ခြင်း။
* **Warehouse & Shelf Location selector** အကွက် ထည့်သွင်းခြင်း။

### Phase 3: Store Setup & Preset Automation
* Store Settings တွင် Business Industry Preset ရွေးချယ်နိုင်သော စနစ် ချိတ်ဆက်ခြင်း။

### Phase 4: Verification & Automated Tests
* Full Feature Test Suite စစ်ဆေးခြင်း (Auto SKU validation, Type Switcher assertions, Store isolation checks)။

---

**Document Version:** 1.0.0  
**Status:** Approved Architectural Blueprint  
**Last Updated:** 2026-08-26  
