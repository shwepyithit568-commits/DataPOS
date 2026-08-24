# DataPOS — မြန်မာနိုင်ငံ SME ဈေးကွက် လက်တွေ့ဖြန့်ချိရောင်းချရေးနှင့် Demo စနစ် မဟာဗျူဟာ လမ်းညွှန်
**Document Version:** 1.1.0  
**Target Market:** Myanmar Micro, Small & Medium Enterprises (MSMEs / SMEs)  
**System Base:** DataPOS (Laravel 11 + Alpine.js + Tailwind CSS + Livewire + Offline-Ready Architecture)

---

## ၁။ ရည်ရွယ်ချက်နှင့် အခြေခံသဘောတရား (Executive Summary)

မြန်မာနိုင်ငံရှိ အသေးစား/အလတ်စား လုပ်ငန်းရှင် အများစုသည် **နည်းပညာပိုင်း အားနည်းခြင်း (Non-Technical)**၊ **စာရင်းဇယားကို စာအုပ်ဖြင့်သာ အချိန်ကြာမြင့်စွာ မှတ်သားခဲ့ခြင်း** နှင့် **အင်တာနက်/မီး အခက်အခဲများ** ရှိကြပါသည်။ 

ထို့ကြောင့် ဆော့ဝဲကို စကားလုံးများဖြင့်သာ ရှင်းပြရောင်းချခြင်းထက် **"၎င်းတို့၏ လုပ်ငန်းနှင့် တိုက်ရိုက်ကိုက်ညီသော နမူနာဒေတာများ (Live Industry Demo Data) ဖြင့် မျက်စိရှေ့တွင် ၅ မိနစ်အတွင်း လက်တွေ့ ထုတ်ပြ/ရောင်းပြခြင်း"** သည် အရောင်းပိတ်ရန် အထိရောက်ဆုံး နည်းဗျူဟာ ဖြစ်ပါသည်။

---

## ၂။ လုပ်ငန်းအလိုက် One-Click Demo Data စနစ် (Industry Demo Engine)

Admin Panel တွင်ဖြစ်စေ၊ CLI Command တွင်ဖြစ်စေ ခလုတ်တစ်ချက်နှိပ်ရုံဖြင့် သက်ဆိုင်ရာ လုပ်ငန်းအမျိုးအစားအလိုက် နမူနာပစ္စည်းများ၊ စျေးနှုန်းများ၊ အုပ်စုများကို အဆင်သင့်ထည့်သွင်းပေးနိုင်သည့် Seeder စနစ် တည်ဆောက်ထားရပါမည်။

```
┌─────────────────────────────────────────────────────────────┐
│             DataPOS Demo Switcher (Admin / CLI)             │
├─────────────────────────────────────────────────────────────┤
│  [📱 ဖုန်းဆိုင်]   [💊 ဆေးဆိုင်]   [🍽️ စားသောက်ဆိုင်]  [💍 ရွှေဆိုင်]   │
│  [🛒 ကုန်စုံဆိုင်] [🌱 စိုက်ပျိုးရေး] [🧱 အိမ်ဆောက်ပစ္စည်း]              │
└─────────────────────────────────────────────────────────────┘
```

### (က) ဖုန်းနှင့် အပိုပစ္စည်းဆိုင် (Mobile & Tech Accessories)
- **Sample Products**: iPhone 15 Pro Screen Protector, Remax 20000mAh Powerbank, Type-C Fast Cable, 20W Charger, Bluetooth Earbuds, Phone Back Glass.
- **Key Demo Highlights**: 
  - Glass Finder (ဖုန်းမော်ဒယ်အလိုက် ဖုန်းမှန် အလွယ်တကူ ရှာဖွေခြင်း)။
  - IMEI / Serial Number ထည့်သွင်းခြင်းနှင့် စစ်ဆေးခြင်း။
  - ဖုန်းပြင်ဝန်ဆောင်မှု (Repair Service Job Sheet & Customer Tracking)။

### (ခ) ဆေးနှင့် ဆေးပစ္စည်းဆိုင် (Pharmacy & Healthcare)
- **Sample Products**: Paracetamol 500mg, Amoxicillin 500mg, Decolgen, Biogesic, Royal-D, Digital Thermometer, Cotton & Gauze.
- **Key Demo Highlights**:
  - Expiry Date (သက်တမ်းကုန်ဆုံးရက်) သတိပေးချက် အရောင်များဖြင့် ပြသခြင်း။
  - ယူနစ်ခွဲရောင်းချမှု (၁ ဗူး / ၁ ကတ် / ၁ လုံး ဈေးနှုန်း အလိုအလျောက် တွက်ချက်ခြင်း)။
  - Generic Name (ဆေးဝါးဓာတ်အမည်) ဖြင့် ရှာဖွေနိုင်ခြင်း။

### (ဂ) စားသောက်ဆိုင်နှင့် ကော်ဖီဆိုင် (Restaurant, Cafe & F&B)
- **Sample Products**: မာလာရှမ်းကော၊ ကြက်ကြော်၊ အမဲသားကြော်၊ Espresso, Green Tea, Bubble Milk Tea, Ice Lemon Tea.
- **Key Demo Highlights**:
  - စားပွဲနံပါတ် စနစ် (Table 1, Table 2, VIP Room) နှင့် အော်ဒါပေါင်း/ခွဲခြင်း။
  - မီးဖိုချောင် အော်ဒါစလစ် (Kitchen Order Ticket - KOT) ထုတ်ပေးခြင်း။
  - Modifier စနစ် (အစပ်နည်း/များ၊ သကြား ၅၀%၊ ရေခဲမပါ)။

### (ဃ) ရွှေဆိုင်နှင့် ကျောက်မျက်ရတနာ (Gold & Jewelry)
- **Sample Products**: ၁၆ ပဲရည် ရွှေဆွဲကြိုး (၁ ကျပ်သား)၊ ရွှေလက်စွပ် (၂ ပဲ ၄ ရွေး)၊ ပလက်တီနမ် လက်ကောက်၊ စိန်နားကပ်။
- **Key Demo Highlights**:
  - မြန်မာ့ရွှေချိန်စနစ် (ကျပ်၊ ပဲ၊ ရွေး) အလိုအလျောက် တွက်ချက်မှု။
  - နေ့စဉ် ရွှေပေါက်ဈေး (Daily Gold Rate) အလိုက် ဈေးနှုန်း ချက်ချင်း Update ဖြစ်ခြင်း။
  - လက်ခနှင့် အလျော့တွက် (Wastage / Making Charges) စာရင်းခွဲထုတ်မှု။

### (င) ကုန်စုံဆိုင်နှင့် မီနီမတ် (Grocery & Minimart)
- **Sample Products**: မားမားခေါက်ဆွဲ (ဖာ/ထုပ်)၊ ကြက်ဥ (လုံး/ကတ်)၊ စားအုန်းဆီ (ပိဿာ/ဘူး)၊ ဆပ်ပြာမှုန့်၊ သွားတိုက်ဆေး။
- **Key Demo Highlights**:
  - Speed Barcode Scanning (ကောင်တာတွင် စက္ကန့်ပိုင်းအတွင်း လျင်မြန်စွာ ရောင်းချခြင်း)။
  - လက်လီဈေး / လက်ကားဈေး (Retail vs Wholesale Price Switching)။
  - စတော့လက်ကျန် နည်းပါးမှု သတိပေးချက် (Low Stock Alert)။

### (စ) စိုက်ပျိုးရေး မျိုးစေ့နှင့် ပိုးသတ်ဆေးဆိုင် (Agro-Chemical & Seeds)
- **Sample Products**: ယူရီးယား ဓာတ်မြေသြဇာ (၅၀ ကီလိုအိတ်)၊ ပေါင်းသတ်ဆေး (၁ လီတာ)၊ ပိုးသတ်ဆေး (၅၀၀ စီစီ)၊ စပါးမျိုးစေ့ (သင်းဝင်)။
- **Key Demo Highlights**:
  - တောင်သူ အကြွေးစာရင်း (Farmer Seasonal Credit Ledger & Loan tracking)။
  - ပစ္စည်းအရွယ်အစား Unit ခွဲရောင်းချခြင်း (ပုံးလိုက် / ပုလင်းလိုက်)။
  - သီးနှံရိတ်သိမ်းချိန်တွင် အကြွေးပြန်ဆပ်မှု သီးသန့် မှတ်တမ်းတင်ခြင်း။

### (ဆ) အိမ်ဆောက်ပစ္စည်းနှင့် ဟာ့ဒ်ဝဲဆိုင် (Construction & Hardware)
- **Sample Products**: အယ်ဖာ ဘိလပ်မြေ (အိတ်)၊ သံချောင်း (၁၂ မီလီ)၊ PVC ရေပိုက် (၄ လက်မ)၊ ဆေးသုတ်ပုံး၊ သံမှို။
- **Key Demo Highlights**:
  - အတိုင်းအတာ ယူနစ်များ (ပေ၊ ချောင်း၊ အိတ်၊ တန်၊ ကျင်း)။
  - ကုန်ကားဖြင့် ပို့ဆောင်မှု မှတ်တမ်း (Delivery Waybill & Driver Dispatch)။
  - ကန်ထရိုက်တာ / ပန်းရံဆရာ အကြွေးစာရင်း စီမံခန့်ခွဲမှု။

---

## ၃။ မြန်မာစီးပွားရေးလုပ်ငန်းရှင်များထံ ကွင်းဆင်းရောင်းချရေး နည်းဗျူဟာ (Field Marketing Strategy)

### ၁။ "The 5-Minute Wow Demo" (၅ မိနစ်အတွင်း စိတ်ဝင်စားစေမည့် လက်တွေ့ပြသနည်း)
1. **မိနစ် ၁-၂ (အရောင်းမြန်ဆန်မှု ပြသခြင်း)**:
   - Barcode Scanner ဖြင့် ပစ္စည်း ၂/၃ ခု scan ဖတ်ပြခြင်း။
   - KPay / Cash ရွေးချယ်ပြီး `Enter` ခေါက်ကာ Bluetooth/USB Thermal Printer မှ ဘောက်ချာ ချက်ချင်းထွက်လာပုံကို လက်တွေ့ပြပါ။
2. **မိနစ် ၃-၄ (မိုဘိုင်းဖုန်းဖြင့် အွန်လိုင်းအော်ဒါနှင့် ဆိုင်ထိန်းချုပ်မှု ပြသခြင်း)**:
   - ဆိုင်ရှင်၏ ဖုန်းဖြင့် QR Code scan ဖတ်ခိုင်းပြီး လှပသော Storefront Web Page ကို ပြပါ။
   - ဖုန်းထဲမှ အော်ဒါတင်လိုက်သည်နှင့် POS ကောင်တာတွင် Notification တက်လာပုံကို ပြသပါ။ (ဤအချက်သည် ဆိုင်ရှင်များကို အထူးဆွဲဆောင်နိုင်ပါသည်)။
3. **မိနစ် ၅ (အရှုံး/အမြတ် ချက်ချင်းကြည့်ရှုနိုင်မှု ပြသခြင်း)**:
   - နေ့စဉ် အရောင်းဝင်ငွေ၊ အမြတ်ငွေ၊ ကုန်ကျစရိတ်နှင့် အကြွေးစာရင်း Dashboards များကို ရှင်းလင်းစွာ ပြသပါ။

---

## ၄။ ဈေးကွက် Package များနှင့် Pricing Strategies (ဈေးနှုန်းပုံစံများ)

| Package အမည် | ပါဝင်သော အရာများ | သင့်တော်သည့် လုပ်ငန်း | အကြံပြု ဈေးနှုန်းပုံစံ |
| :--- | :--- | :--- | :--- |
| **Starter (Offline POS)** | • POS အရောင်း + Barcode<br>• Inventory + Reports<br>• Local Backup စနစ် | ကုန်စုံဆိုင်ငယ်၊ အပိုပစ္စည်းဆိုင်၊ ဆေးဆိုင်ငယ် | တစ်ကြိမ်တည်း ဝယ်ယူခ (One-Time Setup Fee) |
| **Business (POS + Services)** | • Starter အားလုံးပါဝင်<br>• IMEI/Expiry/Service Tracker<br>• Multi-user Cashier Roles | ဖုန်းဆိုင်၊ ကွန်ပျူတာဆိုင်၊ ဆေးဆိုင်ကြီးများ | One-Time Fee + နှစ်စဉ် Support ကြေး |
| **Enterprise (Omnichannel)** | • POS + E-commerce Storefront<br>• Cloud Online Ordering<br>• Custom Domain + SSL + Hosting | နာမည်ကြီးဆိုင်များ၊ ဆိုင်ခွဲများ၊ Brand ဆိုင်များ | နှစ်စဉ်ကြေး (Annual Subscription Fee) |

### Hardware + Software Bundle ရောင်းချခြင်း (All-In-One Solution):
မြန်မာလုပ်ငန်းရှင်အများစုသည် Hardware သီးသန့်လိုက်ဝယ်ရသည်ကို စိတ်ရှုပ်တတ်ကြသည်။ ထို့ကြောင့် အောက်ပါအတွဲလိုက် ရောင်းချပါက ပိုမိုအောင်မြင်နိုင်ပါသည်:
- **Set A**: DataPOS Software + 58mm/80mm Thermal Printer + Barcode Scanner
- **Set B (Full Set)**: Touch Screen POS PC / Mini PC + Printer + Scanner + Cash Drawer + DataPOS အသင့်ထည့်သွင်းပြီး။

---

## ၅။ မြန်မာနိုင်ငံအတွက် မရှိမဖြစ် နည်းပညာ ကြံ့ခိုင်မှုများ (Technical Readiness)

1. **၁၀၀% Offline-First Capability**:
   - မီးပျက်၍ အင်တာနက်ပြတ်တောက်သွားသော်လည်း Local PC ပေါ်တွင် ချောမွေ့စွာ အရောင်းဖွင့်နိုင်ရမည်။
2. **One-Click Local Backup (USB/Flash Drive)**:
   - Windows Desktop ပေါ်တွင် `Backup_Today.bat` ကလစ်တစ်ချက်နှိပ်ရုံဖြင့် SQLite/MySQL database ကို Flash Drive ထဲ သို့မဟုတ် Drive D ထဲ အလိုအလျောက် သိမ်းဆည်းပေးသော စနစ်။
3. **Easy Local Installer**:
   - ဆိုင်ကွန်ပျူတာအသစ်တွင် ဆော့ဝဲထည့်သွင်းသည့်အခါ Setup Wizard သဖွယ် အလွယ်တကူ တင်နိုင်သော Auto-Installer Script များ ပြင်ဆင်ထားခြင်း။
4. **မြန်မာစာနှင့် ဖောင့်ပြဿနာ ကင်းစင်မှု**:
   - Unicode စနစ်အပြည့်အစုံဖြင့် Thermal Slip များပေါ်တွင် မြန်မာစာ စာလုံးမကျိုးဘဲ လှပသပ်ရပ်စွာ ထွက်ရှိနိုင်မှု (လက်ရှိ DataPOS တွင် အောင်မြင်စွာ တည်ဆောက်ထားပြီးဖြစ်သည်)။

---

## ၆။ Theme Import & Export JSON စနစ် ဗိသုကာ (Theme Architecture)

ဆော့ဝဲကို ဆိုင်ရှင်များ (သို့မဟုတ် Admin) က စိတ်ကြိုက် Theme Preset များ ဖလှယ်အသုံးပြုနိုင်ရန် `.datapos-theme.json` စနစ်ကို အောက်ပါအတိုင်း တည်ဆောက်ပါမည်:

```json
{
  "theme_version": "1.0",
  "theme_name": "Emerald Fresh (ဆေးဆိုင် & သဘာဝကုန်ပစ္စည်း)",
  "author": "Tech Buddy",
  "config": {
    "theme_primary_color": "#059669",
    "theme_accent_color": "#10b981",
    "theme_header_bg": "#064e3b",
    "theme_body_bg": "#f0fdf4",
    "theme_glow_style": "subtle",
    "theme_dark_mode": "auto",
    "theme_layout_preset": "emerald"
  }
}
```

### အလုပ်လုပ်ပုံအဆင့်ဆင့်:
1. **Export**: Admin Appearance စာမျက်နှာရှိ `Export Theme` ခလုတ်ကို နှိပ်ပါက လက်ရှိ Setting များကို JSON ဖိုင်အဖြစ် Browser မှ Download ချပေးခြင်း။
2. **Import**: အခြားဆိုင်ခွဲ သို့မဟုတ် ဆိုင်အသစ်တွင် `Import Theme JSON` ကို နှိပ်ပြီး ဖိုင်ရွေးတင်လိုက်သည်နှင့် Form Input များထဲသို့ အလိုအလျောက် ပြည့်သွားပြီး Live Preview တွင် ချက်ချင်းပြသပေးခြင်း။
3. **Validation**: JSON ဖိုင်တွင် HEX Color format (`#rrggbb`) နှင့် Glow Style enum (`vivid`, `subtle`, `none`) ကို Server-side validation ဖြင့် လုံခြုံစွာ စစ်ဆေးလက်ခံခြင်း။

---

## ၇။ Client များထံ ရောင်းချရန် Installer နှင့် Licensing စနစ် (Distribution & Anti-Piracy)

ဆော့ဝဲကို အခြားဆိုင်များသို့ ရောင်းချသည့်အခါ ကူးယူခိုးယူသုံးစွဲမှု မရှိစေရန်နှင့် Installation လွယ်ကူစေရန် အောက်ပါစနစ် (၂) ခုကို ပြင်ဆင်ထားရပါမည်:

### (က) One-Click Local Installer (ဖိုင်တစ်ချက်နှိပ် ဆော့ဝဲသွင်းစနစ်)
- **Windows Inno Setup / Batch Script**:
  - `DataPOS_Setup.exe` ကို Run လိုက်သည်နှင့် XAMPP သို့မဟုတ် Standalone Portable PHP + SQLite Engine ကို အလိုအလျောက် နေရာချပေးခြင်း။
  - Desktop ပေါ်တွင် **DataPOS Icon** (Shortcut) ထုတ်ပေးခြင်း။
  - Click နှိပ်လိုက်ပါက Browser တွင် `http://localhost:8502` ဖြင့် Full Screen POS ကောင်တာ ပွင့်လာစေခြင်း။

### (ခ) Hardware-Bound Offline Licensing System (စက်ကိရိယာ အခြေပြု လိုင်စင်စနစ်)
မြန်မာနိုင်ငံတွင် အင်တာနက်မရှိသော ဆိုင်များအတွက် **Offline Key Activation** ဖြင့် ကာကွယ်ရောင်းချရပါမည်:

```
[Client စက်တပ်ဆင်ခြင်း]
        │
        ▼
စက်၏ Hardware ID ထွက်လာခြင်း (Motherboard UUID + CPU ID)
  ဥပမာ: "DPOS-M49A-9821-BC77"
        │
        ▼
ဆိုင်ရှင်က Boss ထံ Viber / Phone ဖြင့် Hardware ID ပို့ခြင်း
        │
        ▼
Boss ၏ Keygen Generator မှ Activation Key ထုတ်ပေးခြင်း (RSA/AES Signature)
  ဥပမာ: "ACT-9912-FA83-2201-9874"
        │
        ▼
ဆော့ဝဲတွင် Key ထည့်လိုက်သည်နှင့် Full Version စတင်အသုံးပြုနိုင်ခြင်း
```

### လိုင်စင်စနစ်၏ အားသာချက်များ:
1. **စက်တစ်လုံးလျှင် လိုင်စင်တစ်ခု (Node-Locked)**: ဆော့ဝဲ Folder တစ်ခုလုံးကို အခြားစက်သို့ Copy ကူးသွားသော်လည်း အခြားစက်တွင် Hardware ID မတူသဖြင့် ပွင့်မည်မဟုတ်ပါ။
2. **Grace Period / Demo Mode**: ဆိုင်ရှင်များကို ၇ ရက် သို့မဟုတ် ၁၄ ရက် အခမဲ့ စမ်းသပ်သုံးစွဲခွင့် ပေးနိုင်ပြီး ရက်ပြည့်ပါက Key တောင်းဆိုသော စနစ်။
3. **Feature Unlock**: Starter Pack ဝယ်သူအတွက် Basic POS သာပွင့်ပြီး၊ Pro Pack ဝယ်သူအတွက် E-commerce & Repair Modules များပါ ပွင့်သွားသည့် Feature Flag Unlock စနစ်။

---

## ၈။ Tablet & Mobile Phone (Android APK) ဖြင့် အသုံးပြုမည့်သူများအတွက် နည်းဗျူဟာ (Mobile & Tablet Architecture)

ကွန်ပျူတာမဝယ်နိုင်သော သို့မဟုတ် ဆိုင်နေရာကျဉ်း၍ **Tablet / Android Phone** ဖြင့်သာ သုံးချင်သော စားသောက်ဆိုင်၊ ကော်ဖီဆိုင်၊ ဖုန်းအပိုပစ္စည်းဆိုင်များအတွက် DataPOS ကို APK အဖြစ် အောက်ပါနည်းလမ်း (၃) မျိုးဖြင့် ဖြန့်ချိနိုင်ပါသည်:

```
┌─────────────────────────────────────────────────────────────┐
│             DataPOS Android APK Architecture                │
├─────────────────────────────────────────────────────────────┤
│  [📱 Capacitor / TWA Native Shell]                          │
│     ├── Full-Screen Standalone APK (Browser Bar မပါ)        │
│     ├── Bluetooth Thermal Printer Direct Print (ESC/POS)    │
│     ├── Camera Barcode Scanner Support                      │
│     └── Local Wi-Fi / Cloud Server Auto-Connect             │
└─────────────────────────────────────────────────────────────┘
```

### (က) Progressive Web App (PWA) → Capacitor / TWA APK Packaging (အကောင်းဆုံးနည်းလမ်း)
- **လုပ်ဆောင်ပုံ**:
  - DataPOS တွင် Service Worker (`sw.js`), Web App Manifest (`manifest.webmanifest`), Icons များ အပြည့်အစုံ ပါပြီးဖြစ်ပါသည်။
  - **Capacitor** သို့မဟုတ် Google ၏ **Bubblewrap CLI** ဖြင့် DataPOS ကို Android Studio တွင် Build လုပ်ကာ စစ်မှန်သော `.apk` ဖိုင်အဖြစ် ထုတ်ယူခြင်း။
- **အကျိုးကျေးဇူး**:
  - ဖုန်း/တက်ပလက်တွင် `DataPOS.apk` ကို install လုပ်လိုက်ပါက Browser ပုံစံမဟုတ်ဘဲ **Native App စစ်စစ်ကဲ့သို့ Full Screen** ဖြင့် အလုပ်လုပ်မည်။
  - App Icon လေးကို နှိပ်လိုက်သည်နှင့် ဆိုင်၏ POS ကောင်တာသို့ တိုက်ရိုက်ရောက်ရှိမည်။

### (ခ) ဆိုင်တွင်း Local Wi-Fi Hub စနစ် (Local Multi-Device Setup)
- ဆိုင်တွင် စျေးသက်သာသော **Mini PC / Android TV Box သို့မဟုတ် Laptop အဟောင်းတစ်လုံး** ကို ဆိုင်တွင်း Local Server အဖြစ် ထားရှိခြင်း။
- ဆိုင်ဝန်ထမ်းများသည် မိမိတို့၏ **Android Phone / Tablet များထဲမှ DataPOS APK ဖြင့် Wi-Fi ချိတ်ဆက်ကာ** အော်ဒါယူခြင်း၊ စတော့စစ်ခြင်း၊ အရောင်းဖွင့်ခြင်း ပြုလုပ်နိုင်ခြင်း (အင်တာနက်လိုင်း လုံးဝမလိုပါ)။

### (ဂ) Bluetooth Thermal Printer ချိတ်ဆက်မှု (Mobile Hardware Integration)
- Mobile APK ထဲမှနေ၍ စျေးသက်သာသော **Bluetooth 58mm / 80mm Mobile Thermal Printer** (ကျပ် ၅ သောင်း - ၁ သိန်းဝန်းကျင်) များနှင့် Bluetooth ဖြင့် တိုက်ရိုက်ချိတ်ကာ ခလုတ်တစ်ချက်နှိပ်ရုံဖြင့် စလစ်ဘောက်ချာ ချက်ချင်း ထုတ်ပေးနိုင်ခြင်း။

---

## ၉။ ရှေ့ဆက် အကောင်အထည်ဖော်ရမည့် အဆင့်များ (Next Steps Roadmap)

- [ ] **အဆင့် ၁**: Industry Demo Data Seeders များ စတင်ရေးဆွဲခြင်း (`MobileSeeder`, `PharmacySeeder`, `GrocerySeeder`, `RestaurantSeeder` စသည်)။
- [ ] **အဆင့် ၂**: Admin Settings ထဲတွင် ခလုတ်တစ်ချက်ဖြင့် Demo Data စမ်းသပ်ထည့်သွင်းနိုင်သည့် `Demo Preset Switcher` UI ထည့်သွင်းခြင်း။
- [ ] **အဆင့် ၃**: Admin Appearance တွင် `.json` ဖြင့် Theme Import / Export ပြုလုပ်နိုင်သည့် စနစ်ထည့်သွင်းခြင်း။
- [ ] **အဆင့် ၄**: One-Click Desktop Installer Script & Portable Standalone Runtime ပြင်ဆင်ခြင်း။
- [ ] **အဆင့် ၅**: Hardware-bound Licensing & Offline Activation Key Management System တည်ဆောက်ခြင်း။
- [ ] **အဆင့် ၆**: Capacitor / TWA Shell ဖြင့် Android Tablet / Phone အတွက် `DataPOS.apk` Build ထုတ်ပေးနိုင်ရန် ပြင်ဆင်ခြင်း။
- [ ] **အဆင့် ၇**: ကွင်းဆင်းအရောင်းအတွက် မြန်မာဘာသာ User Guide & Marketing Demo Deck ပြင်ဆင်ခြင်း။

---
*Created with Tech Buddy for DataPOS Commercialization & SME Expansion.*
