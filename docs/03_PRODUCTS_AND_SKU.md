# 03 Products And Sku

> Consolidated edition. Each embedded source is preserved below with its original path and SHA-256 digest.


---

## Source 1: `architecture/SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md`

**SHA-256:** `243d173d89ad65676aa4625353b5080dcb842ca2e9d85873be095931679b7afd`

# DataPOS — Smart Product & Store Architecture Specification
**Master Architecture & Implementation Blueprint for Multi-Vertical POS & Inventory Management**

---

> **Related Client Standard:** မိုဘိုင်းဖုန်းနှင့် ဆက်စပ်ပစ္စည်းများအတွက် အသေးစိတ် SKU Code ဖွဲ့စည်းမှုနှင့် Naming စံနှုန်းများကို `guides/ALINN_THIT_MOBILE_SKU_LOGIC_MM.md` (`../guides/ALINN_THIT_MOBILE_SKU_LOGIC_MM.md`; see consolidated index) တွင် သီးသန့် လေ့လာနိုင်သည်။

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

---

## Source 2: `guides/ALINN_THIT_MOBILE_SKU_LOGIC_MM.md`

**SHA-256:** `37d041d463f0bb22dbbc6e37e2c471ffb10b82abc8c9bc5d27f03a396de32f2a`

# AlinnThit Mobile — Product SKU Logic & Master AI Instruction

> **Document Type:** Client-Specific Operational Guide & SKU Normalization Standard
> **Status:** Canonical Client Guide (AlinnThit Mobile & Electronics Scope)
> **Generic Architecture Reference:** ../SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md (`../architecture/SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md`; see consolidated index)
> **Historical Archive:** ../archive/source-of-truth-history/ALINN_THIT_Ecommerce_Product_SKU_Logic_Original.md (`../archive/source-of-truth-history/ALINN_THIT_Ecommerce_Product_SKU_Logic_Original.md`; see consolidated index)
> **Notice:** ဤလမ်းညွှန်သည် AlinnThit Mobile Shop ၏ electronics/mobile accessories SKU decoding နှင့် naming စံနှုန်းများဖြစ်ပြီး DataPOS generic platform ၏ core schema နှင့်မရောထွေးဘဲ client store adapter သို့မဟုတ် catalog management တွင် အသုံးပြုရန်ဖြစ်သည်။

---

# ALINN THIT Ecommerce Product SKU Logic — Master Instruction for AI Agent

## Project Goal

Google Sheet ထဲက `Products` tab ကို cleanup / normalize လုပ်ရန်ဖြစ်သည်။

အဓိကအားဖြင့် SKU ကို **source of truth** အဖြစ် အသုံးပြုပြီး အောက်ပါ columns များကို မှန်ကန်စွာ စစ်ဆေး၊ ပြင်ဆင်၊ ဖြည့်စွက်ရန်ဖြစ်သည်။

- Name
- Category
- Parent Category
- Brand
- Variants
- Variant Name(s)
- Variant SKU(s)

**အရေးကြီးသည်:** SKU ကို ပြန်မရေးပါနှင့်။ ရှိပြီးသား SKU ကို decoding လုပ်၍ အခြား columns များကို ပြင်ရမည်။

---

# 1. SKU Structure

ALINN THIT SKU အများစု၏ အခြေခံ structure သည် —

`BRAND - MODEL - PRODUCT TYPE - EXTRA / CONNECTOR / QUALITY - COLOR`

သို့မဟုတ် product အမျိုးအစားအလိုက် —

`BRAND - MODEL - PRODUCT TYPE - VARIANT 1 - VARIANT 2`

ဖြစ်သည်။

SKU တိုင်းတွင် segments အရေအတွက် တိတိကျကျတူမနေပါ။ Product အလိုက် optional segments ရှိနိုင်သည်။

ဥပမာ:

`168-L009-CB-MC-BLK`

Meaning:

- `168` = Brand
- `L009` = Model No.
- `CB` = Cable
- `MC` = Micro USB
- `BLK` = Black

ဒီ product ၏ customer-facing Name ကို:

`L009 Cable`

ဟုထားရမည်။

`Micro USB` နှင့် `Black` ကို Name ထဲမထည့်ဘဲ **Variants** အဖြစ်သုံးရမည်။

---

# 2. Product Name Rule

## Name တွင် Brand မထည့်ပါနှင့်။

Name ကို တိုတို၊ သန့်ရှင်းပြီး ecommerce product card တွင် အဆင်ပြေစေရန်:

`Model + Product Type`

ပုံစံကို အဓိကအသုံးပြုပါ။

ဥပမာ:

`168-L009-CB-MC-BLK`
→ `L009 Cable`

`AB-A19-CHS-TC`
→ `A19 Charger Set`

`CAR-C305-USB-BLK`
→ `C305 Car Charger`

`BVT-X10-PB-(10000mAh)`
→ `X10 Power Bank`

`DENMEN-DR06-EP-WHT`
→ `DR06 Earphone`

Name ထဲတွင် ပုံမှန်အားဖြင့် အောက်ပါတို့ကို မထည့်ပါနှင့်။

- Brand
- Color
- Connector
- Quality
- Capacity
- Variant-specific attributes

၎င်းတို့ကို Variant data အဖြစ်ခွဲရန်ဖြစ်သည်။

သို့သော် model identity အတွက် အရေးကြီးသော `4G`, `5G`, `Pro`, `Plus`, `Lite`, `Max`, model year စသည်တို့ကို Name တွင်ထားနိုင်သည်။

ဥပမာ:

`Note 14 5G Screen Protector`

သည်မှန်သည်။

---

# 3. Product Type Codes

အောက်ပါ SKU codes များကို အခြေခံ product type အဖြစ်ယူပါ။

- `CB` = Cable
- `CH` = Charger
- `CHS` = Charger Set
- `CCH` = Car Charger
- `EP` = Earphone
- `BEP` = Bluetooth Earphone
- `SPK` = Bluetooth Speaker / Speaker
- `Mic` = Microphone
- `BT` = Battery
- `PB` = Power Bank
- `SG` = Screen Protector
- `TL` = Touch LCD
- `TS` = Touch Screen
- `GLS` = Glass / Front Glass / OCA Glass depending on following quality code
- `CV` = Phone Case
- `BC` = Mobile Phone Body/Back Cover depending on product context
- `BD` = Body Frame
- `HS` = Body Frame / Housing
- `USB FIX` = Charging Port
- `Power Swift` = Power Switch
- `SD` = Memory Card
- `Mouse` = Mouse
- `FAN` = Fan
- `ST` may represent holder/stick/accessory depending on product context
- CCTV-related codes should map to CCTV products according to Name/SKU context

Do not blindly assume one token if the surrounding SKU and Name clearly establish another product.

---

# 4. Special Confirmed SKU Meanings

These meanings have been explicitly confirmed by the store owner.

## `TS`

`TS` = **Touch Screen**

Meaning:

ဖုန်းမှန်ကွဲသွားသောအခါ လဲလှယ်အသုံးပြုသော အပေါ်မှန်သီးသန့် Touch Screen.

However, old inventory data may occasionally use TS inconsistently.

Therefore:

If SKU says `TS` but existing Name clearly says `Front Glass`, inspect nearby products/model patterns before changing blindly.

---

## `BC`

`BC` = Mobile Phone **Body Cover / Back Cover** context.

For mobile spare parts it usually maps to:

Category:
`Back Cover`

Parent:
`Body & Back Cover`

However, legacy SKU may contain exceptions.

Example:

`5a-A-004-BC-WHT`

Existing Product Name says Cable.

This is a legacy/anomalous SKU and must not automatically become Back Cover purely because of `BC`.

Use SKU + existing Name + surrounding product pattern together.

---

## `CAR...USB`

Example:

`CAR-C305-USB-BLK`

This is a charger plugged into a car cigarette-lighter socket and provides a USB charging port.

Therefore:

Name:
`C305 Car Charger`

Category:
`Car Charger`

Parent Category:
`Cable & Charger`

Do not classify this as USB Cable.

---

# 5. Connector Codes → Variants

Connector information must normally NOT be included in the main Name.

Use it as Variant data.

Typical mappings:

- `MC` = Micro USB
- `Micro` = Micro USB
- `TC` = Type-C
- `IP` = Lightning / iPhone connector
- `3.5MM` = 3.5mm
- `3IN1` = 3-in-1

Example:

`UW-L009-CB-TC-WHT`

Name:
`L009 Cable`

Variant:
- Connector = Type-C
- Color = White

Another SKU for same model:

`UW-L009-CB-IP-WHT`

Name:
`L009 Cable`

Variant:
- Connector = Lightning
- Color = White

These may eventually be merged into one product with multiple variants where appropriate.

---

# 6. Quality Codes → Variants / Category Logic

Quality codes may include:

- `ORG` = Original
- `AAA` = AAA quality
- `MA` = MA quality
- `OCA` = OCA
- other quality codes may exist

Quality information should generally be placed in Variant data, but it can also determine Category where taxonomy explicitly distinguishes Original vs Standard products.

---

# 7. Battery Category Logic

For battery SKU:

`...-BT-ORG`

Category:
`Original Battery`

Parent Category:
`Battery`

Battery without Original code:

`...-BT`
`...-BT-BLK`
`...-BT-WHT`

Category:
`Standard Battery`

Parent Category:
`Battery`

Example:

`HW-GR5(HB3864)-BT-ORG`

→ Category: `Original Battery`
→ Parent: `Battery`

Example:

`HW-G730-BT`

→ Category: `Standard Battery`
→ Parent: `Battery`

---

# 8. Touch LCD Logic

## Original Touch LCD

If:

`TL + ORG`

Category:
`Original Touch LCD`

Parent:
`Screen & LCD`

Example:

`HW-NOVA2I-TL-ORG-WHT`

→ `Original Touch LCD`
→ `Screen & LCD`

## Non-original / other quality Touch LCD

Examples:

`TL`
`TL-MA`
`TL-AAA`

Category:
`Touch LCD`

Parent:
`Screen & LCD`

Quality itself should be stored as Variant where appropriate.

---

# 9. Touch Screen Logic

SKU contains:

`TS`

Normally:

Category:
`Touch Screen`

Parent:
`Screen & LCD`

Example:

`HW-GR5(17)-TS-GLD`

→ Touch Screen
→ Screen & LCD

But older SKUs can contain inconsistent naming. Do not automatically overwrite clearly established legacy Front Glass products without cross-checking.

---

# 10. Glass Logic

Glass products require looking at both `GLS` and quality token.

Typical logic:

`GLS-OCA`
→ `OCA Glass`

`GLS-AAA`
→ `Front Glass`

Parent:
`Screen & LCD`

Examples:

`RM-N5-GLS-OCA-BLK`
→ OCA Glass

`RM-N5-GLS-AAA-BLK`
→ Front Glass

If legacy product Name and SKU quality disagree, inspect similar SKUs for the same brand/model before modifying.

---

# 11. Screen Protector

SKU:

`SG`

Category:
`Screen Protector`

Screen Protector is NOT the same thing as:

- Touch Screen
- Touch LCD
- Front Glass
- OCA Glass

Do not mix these categories.

For the final taxonomy, every row should have an intentional Parent Category. If the current project requires no blank Parent Category values, use the agreed hierarchy rather than leaving Parent blank.

---

# 12. Phone Case

SKU:

`CV`

Category:
`Phone Case`

Do not confuse Phone Case with:

- Back Cover
- Body Frame
- Screen Protector

Variant tokens after `CV` can describe case type/style.

Examples may include:

- `SIL` = Silicone
- `CLR` = Clear
- `PLA` = Plastic
- `BTY`
- `CAT`
- `CAR`
- `VNO`

These are **case styles/variants**, not Category changes, unless explicitly verified otherwise.

---

# 13. Back Cover / Body Parts

Back Cover:

Category:
`Back Cover`

Parent:
`Body & Back Cover`

Body Frame / Housing:

Category:
`Body Frame`

Parent:
`Body & Back Cover`

Typical codes:

- `BC` = Back/Body Cover
- `BD` = Body Frame
- `HS` = Housing / Body Frame

Use product context for legacy exceptions.

---

# 14. Cable & Charger Taxonomy

Categories:

- Cable
- Charger
- Charger Set
- Car Charger

Parent Category:

`Cable & Charger`

Examples:

`CB`
→ Cable

`CH`
→ Charger

`CHS`
→ Charger Set

Car charging products
→ Car Charger

---

# 15. Audio Taxonomy

Parent:

`Audio`

Categories include:

- Earphone
- Bluetooth Earphone
- Bluetooth Speaker
- Microphone

Important:

`EP` = wired/normal Earphone

`BEP` = Bluetooth Earphone

Example:

`YK-D810-BEP-BLK`

must be:

Category:
`Bluetooth Earphone`

not normal Earphone.

---

# 16. Power & Storage

Parent:

`Power & Storage`

Categories:

- Power Bank
- Memory Card

`PB` = Power Bank

Capacity such as:

`10000mAh`
`20000mAh`

should be Variant/attribute, NOT part of canonical Name.

Example:

`BVT-X30-PB-(20000mAh)`

Name:
`X30 Power Bank`

Variant:
`Capacity = 20000mAh`

---

# 17. Phone Spare Parts

Parent:

`Phone Spare Parts`

Categories include:

- Charging Port
- Power Switch
- Other Spare Parts

Examples:

`USB FIX`
→ Charging Port

`Power Swift`
→ Power Switch

---

# 18. Electronic

Parent:

`Electronic`

Examples:

- LED Light
- Mouse
- Fan

---

# 19. CCTV

Parent:

`CCTV`

Categories can include:

- CCTV Camera
- CCTV Accessory

Examples:

camera unit
→ CCTV Camera

Power supply, Balun, CCTV supply cable
→ CCTV Accessory

---

# 20. Variant Rules

The following should normally become Variants rather than being embedded in Product Name:

- Connector
- Color
- Quality
- Capacity
- Size
- Case style
- other option-specific SKU tokens

Examples:

Color mappings:

- `BLK` = Black
- `WHT` = White
- `BLU` = Blue
- `RED` = Red
- `GLD` = Gold
- `SLV` = Silver
- `GRAY` / `Gray` = Gray
- `PUR` = Purple

Additional codes should be interpreted carefully from existing products.

Do not guess unknown codes.

---

# 21. Variant Columns

The spreadsheet already contains:

- `Variants`
- `Variant Name(s)`
- `Variant SKU(s)`

Use these existing fields.

Do not create duplicate Variant columns unnecessarily.

Each variant must preserve the original SKU associated with that option.

If multiple rows represent exactly the same product model with only Connector / Color / Quality / Capacity differences, they can be treated as variants of the same product **only when confidently matched**.

Do not merge products merely because names are similar.

---

# 22. Brand Handling

Brand should stay in the dedicated `Brand` column.

**Do not put Brand in Product Name.**

Examples:

Brand:
`Huawei`

Name:
`Nova 2i Touch LCD`

NOT:

`Huawei Nova 2i Touch LCD`

Brand:
`Redmi`

Name:
`Note 10 5G Phone Case`

NOT:

`Redmi Note 10 5G Phone Case`

Exceptions may exist where the apparent brand word is actually part of the model identity. Inspect context before removing it.

---

# 23. Category Hierarchy

Use a consistent Parent → Category system.

Known parent groups include:

### Battery
- Original Battery
- Standard Battery

### Cable & Charger
- Cable
- Charger
- Charger Set
- Car Charger

### Audio
- Earphone
- Bluetooth Earphone
- Bluetooth Speaker
- Microphone

### Screen & LCD
- Original Touch LCD
- Touch LCD
- Touch Screen
- Front Glass
- OCA Glass

### Body & Back Cover
- Back Cover
- Body Frame

### Electronic
- LED Light
- Mouse
- Fan

### Power & Storage
- Power Bank
- Memory Card

### Phone Accessories
Typical accessory categories can include:
- Phone Holder
- Sticker
- Waterproof Pouch
- Phone Case / Screen Protector if this is the agreed final hierarchy

### Phone Spare Parts
- Charging Port
- Power Switch
- Other Spare Parts

### CCTV
- CCTV Camera
- CCTV Accessory

The objective is that Category and Parent Category always reflect the same hierarchy.

Do not leave accidental blanks.

---

# 24. Critical Safety Rule for Data Editing

Do NOT blindly rewrite all 1000+ rows from a single regex.

Process should be:

1. Read SKU.
2. Decode Brand.
3. Decode Model.
4. Identify Product Type.
5. Inspect Extra / Connector / Quality / Color.
6. Compare with existing Name.
7. Compare with existing Category.
8. Compare similar SKU patterns in the sheet.
9. Generate corrected Name.
10. Generate correct Category.
11. Generate correct Parent Category.
12. Generate Variants.
13. Preserve original SKU.
14. Flag unresolved anomalies instead of guessing.

---

# 25. Unknown / Ambiguous SKU Rule

If any SKU token cannot be confidently understood:

**DO NOT GUESS.**

Do not silently change the product.

Instead flag the entire row for review, preferably with red highlighting or a review note.

Provide the user with:

- Row number
- SKU
- Existing Name
- Existing Category
- Unknown token
- Your suspected meaning, if any

Then ask the store owner for clarification.

Once the owner explains the code, add that rule to the SKU logic and continue.

---

# 26. Legacy SKU Exception Rule

This inventory contains historical SKU formats.

Therefore SKU is the primary logic, but **SKU token alone is not always sufficient**.

Example:

`5a-A-004-BC-WHT`

Although `BC` normally means Body/Back Cover, this existing product is known as:

`5A A-004 Cable`

Therefore treat this as a legacy exception unless the owner explicitly requests SKU correction.

Likewise, products where `TS`, `GLS`, `BT`, etc. conflict with an otherwise strongly established Name/product family should be reviewed rather than automatically overwritten.

---

# 27. Final Verification

Before telling the user the cleanup is complete, verify:

- No accidental blank Category
- No accidental blank Parent Category where hierarchy requires one
- Category ↔ Parent Category mapping is consistent
- Brand is not duplicated in Name
- Product Name is concise
- Variant details are not unnecessarily duplicated in Name
- Connector is stored as Variant
- Color is stored as Variant
- Quality is stored as Variant
- Capacity is stored as Variant
- Original SKUs are preserved
- No unrelated columns were damaged
- Unknown SKU codes are flagged, not guessed

After editing, re-read the modified ranges from the live Google Sheet.

Do not say “completed” until the live values have been verified.

---

# 28. Core Principle

**SKU tells us what the product is.
Name tells the customer what the base product is.
Brand stays in Brand.
Category tells the product family.
Parent Category tells the higher-level family.
Connector / Quality / Color / Capacity belong in Variants.**

When there is uncertainty:

**Ask first. Do not invent data.**
