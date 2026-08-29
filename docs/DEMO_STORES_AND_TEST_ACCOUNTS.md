# DataPOS — Demo Stores & Test Accounts Guide 🚀

DataPOS စနစ်ကို Production မတင်မီ အပြည့်အဝ စမ်းသပ်ရန်နှင့် ဝယ်ယူလိုသော မြန်မာ SME လုပ်ငန်းရှင် (Clients) များအား လက်တွေ့ သရုပ်ပြ (Demo) ပြသရာတွင် အသုံးပြုနိုင်မည့် **နမူနာဆိုင်ခွဲ (၆) ဆိုင်၊ ဆိုင်အလိုက် သီးသန့် အကောင့်များ (Owner, Manager, Cashier, Technical, Wholesale, Retail) နှင့် စမ်းသပ်နည်း အဆင့်ဆင့် လမ်းညွှန်** ဖြစ်ပါသည်။

---

## ⚡ အမြန်စတင်ရန် (Quick Rebuild Command)

စမ်းသပ်ဒေတာများ ရှုပ်ထွေးသွားပါက သို့မဟုတ် မူလ သန့်ရှင်းသော Demo အခြေအနေသို့ အချိန်မရွေး ပြန်ထားလိုပါက Terminal တွင် အောက်ပါ Command ကို run ပါ:

```powershell
php artisan datapos:build-demo-stores
```

> 💡 **မှတ်ချက်:** အထက်ပါ Command သည် စမ်းသပ်ထားသော Transaction အဟောင်းများကို သန့်ရှင်းစေပြီး အောက်ပါ ဆိုင်ခွဲ (၆) ဆိုင်လုံးအတွက် Setting၊ ပစ္စည်းစာရင်း၊ စတော့၊ အော်ဒါ၊ POS အရောင်းစာရင်းများနှင့် ဆိုင်အလိုက် သီးသန့် အကောင့်များကို အလိုအလျောက် အသစ်ပြန်လည် ဖြည့်သွင်းပေးပါသည်။

---

## 👑 Platform Head (Super Admin)
- **အမည်:** ဦးအောင်မျိုး (DataPOS Platform Super Admin)
- **Login Phone:** `09777000111` | **Password:** `password` | **POS PIN:** `1234`
- **တာဝန်နှင့် လုပ်ပိုင်ခွင့်:** ဆိုင်ခွဲအားလုံး ထိန်းချုပ်ခွင့်၊ ဆိုင်အသစ်ဖွင့်ခြင်း/ဖျက်ခြင်း (`/admin/stores`)၊ Platform Admin Dashboard (`/admin/dashboard`)

---

## 🏬 ဆိုင်တစ်ခုချင်းစီအလိုက် သီးသန့်အကောင့်များ (Dedicated Store Accounts)
> 🔑 **စကားဝှက် (Password) အားလုံး:** `password` | **POS PIN:** `1234`  
> 🌐 **Login URL:** `http://127.0.0.1:8501/login`

### ၁။ Diamond Stone စိုက်ပျိုးရေးနှင့် မြေသြဇာ အရောင်းဆိုင် 🌾
| အခန်းကဏ္ဍ (Role) | အမည် | Login Phone | သွားရောက်မည့်နေရာ |
|---|---|---|---|
| 👑 **Store Owner (ဆိုင်ပိုင်ရှင်)** | ဦးမြင့်အောင် | `09130000001` | `/store/diamond-stone-agri/admin/dashboard` |
| 👔 **Store Manager (ဆိုင်မန်နေဂျာ)** | ကိုသက်နိုင် | `09130000002` | `/store/diamond-stone-agri/admin/dashboard` |
| 💵 **Cashier (အရောင်းစာရေး)** | မစန္ဒာ | `09130000003` | `/store/diamond-stone-agri/pos` |
| 🏬 **Wholesale (လက်ကားကုန်သည်)** | ဦးဖိုးထောင် | `09130000004` | `/store/diamond-stone-agri/wholesale` |
| 🛒 **Retail (လက်လီတောင်သူ)** | ကိုအောင်ဇော် | `09130000005` | `/store/diamond-stone-agri` |

---

### ၂။ DataPOS မိုဘိုင်းဖုန်းနှင့် ဆက်စပ်ပစ္စည်း အရောင်းဆိုင် 📱
| အခန်းကဏ္ဍ (Role) | အမည် | Login Phone | သွားရောက်မည့်နေရာ |
|---|---|---|---|
| 👑 **Store Owner (ဆိုင်ပိုင်ရှင်)** | ဦးဝင်းဗိုလ် | `09150000001` | `/store/datapos-mobile/admin/dashboard` |
| 👔 **Store Manager (ဆိုင်မန်နေဂျာ)** | မအိအိဖြိုး | `09150000002` | `/store/datapos-mobile/admin/dashboard` |
| 💵 **Cashier (အရောင်းစာရေး)** | ကိုကျော်ဇင် | `09150000003` | `/store/datapos-mobile/pos` |
| 🏬 **Wholesale (လက်ကားဝယ်သူ)** | ဒေါ်တင်တင်ဝင်း | `09150000004` | `/store/datapos-mobile/wholesale` |
| 🛒 **Retail (လက်လီဝယ်သူ)** | မသီတာ | `09150000005` | `/store/datapos-mobile` |

---

### ၃။ ProTech CCTV၊ ကွန်ပျူတာနှင့် Network ဆိုင် 📹
| အခန်းကဏ္ဍ (Role) | အမည် | Login Phone | သွားရောက်မည့်နေရာ |
|---|---|---|---|
| 👑 **Store Owner (ဆိုင်ပိုင်ရှင်)** | ဦးဉာဏ်ထွန်း | `09170000001` | `/store/cctv-network-computer/admin/dashboard` |
| 👔 **Store Manager (ဆိုင်မန်နေဂျာ)** | ကိုစိုးမိုး | `09170000002` | `/store/cctv-network-computer/admin/dashboard` |
| 💵 **Cashier (အရောင်းစာရေး)** | ဒေါ်သဲနု | `09170000003` | `/store/cctv-network-computer/pos` |
| 🔧 **Technical (တပ်ဆင်ရေး/နည်းပညာရှင်)** | ကိုဇော်ကြီး | `09170000004` | `/store/cctv-network-computer/admin/service-jobs` |
| 🏬 **Wholesale (လက်ကားဝယ်သူ)** | ဦးမျိုးမင်း | `09170000005` | `/store/cctv-network-computer/wholesale` |
| 🛒 **Retail (လက်လီဝယ်သူ)** | ကိုထက်အောင် | `09170000006` | `/store/cctv-network-computer` |

---

### ၄။ ရွှေပြည်သစ် မိုဘိုင်းအရောင်းနှင့် စက်ပြင် ဝန်ဆောင်မှုဆိုင် 🔧
| အခန်းကဏ္ဍ (Role) | အမည် | Login Phone | သွားရောက်မည့်နေရာ |
|---|---|---|---|
| 👑 **Store Owner (ဆိုင်ပိုင်ရှင်)** | ဦးမိုးကျော် | `09160000001` | `/store/mobile-sale-service/admin/dashboard` |
| 👔 **Store Manager (ဆိုင်မန်နေဂျာ)** | ဒေါ်နွယ်နွယ် | `09160000002` | `/store/mobile-sale-service/admin/dashboard` |
| 💵 **Cashier (အရောင်းစာရေး)** | မမေသူ | `09160000003` | `/store/mobile-sale-service/pos` |
| 🔧 **Technical (စက်ပြင်ဆရာကြီး / Master)** | ကိုမင်းမင်း | `09160000004` | `/store/mobile-sale-service/admin/service-jobs` |
| 🏬 **Wholesale (လက်ကားဝယ်သူ)** | ဦးဘသိန်း | `09160000005` | `/store/mobile-sale-service/wholesale` |
| 🛒 **Retail (လက်လီဝယ်သူ)** | မခင်စိုး | `09160000006` | `/store/mobile-sale-service` |

---

### ၅။ ရွှေမင်္ဂလာ ဆေးဝါးနှင့် ကျန်းမာရေးပစ္စည်း အရောင်းဆိုင် 💊
| အခန်းကဏ္ဍ (Role) | အမည် | Login Phone | သွားရောက်မည့်နေရာ |
|---|---|---|---|
| 👑 **Store Owner (ဆိုင်ပိုင်ရှင်/ဆေးဝါးပညာရှင်)** | ဒေါက်တာကျော်မင်း | `09180000001` | `/store/pharmacy/admin/dashboard` |
| 👔 **Store Manager (ဆိုင်မန်နေဂျာ)** | ဒေါ်ရီရီမြင့် | `09180000002` | `/store/pharmacy/admin/dashboard` |
| 💵 **Cashier (ကောင်တာစာရေး)** | မနွယ်နီ | `09180000003` | `/store/pharmacy/pos` |
| 🏬 **Wholesale (ဆေးဆိုင်လက်ကား)** | ဦးသန်းထွန်း | `09180000004` | `/store/pharmacy/wholesale` |
| 🛒 **Retail (လက်လီဝယ်သူ)** | ဒေါ်ချိုချို | `09180000005` | `/store/pharmacy` |

---

### ၆။ စည်တော်ကြီး အစားအသောက်နှင့် အဖျော်ယမကာဆိုင် 🍲
| အခန်းကဏ္ဍ (Role) | အမည် | Login Phone | သွားရောက်မည့်နေရာ |
|---|---|---|---|
| 👑 **Store Owner (ဆိုင်ပိုင်ရှင်)** | ဒေါ်နန်းခင်စိန် | `09140000001` | `/store/si-taw-gyi-food-bar/admin/dashboard` |
| 👔 **Store Manager (မန်နေဂျာ)** | ဦးအောင်ကို | `09140000002` | `/store/si-taw-gyi-food-bar/admin/dashboard` |
| 💵 **Cashier (ငွေကိုင်/အရောင်း)** | မခင်လေး | `09140000003` | `/store/si-taw-gyi-food-bar/pos` |
| 🛒 **Retail (စားသုံးသူ/ဧည့်သည်)** | ကိုမင်းသန့် | `09140000004` | `/store/si-taw-gyi-food-bar` |

---

## 🎯 Client Demo ပြသရန် စမ်းသပ်နည်း အဆင့်ဆင့် (Demo Workflows)

### Flow ၁: Storefront E-Commerce & Viber/Telegram Order တင်ခြင်း
1. လက်လီဝယ်သူဖုန်း (ဥပမာ `09130000005`) ဖြင့် Login ဝင်ပါ။
2. Storefront `http://127.0.0.1:8501/store/diamond-stone-agri` တွင် ပစ္စည်းများကို Cart ထဲထည့်ပါ။
3. Checkout တွင် ပို့ဆောင်မည့်လိပ်စာနှင့် ဆက်သွယ်ရန် Viber နံပါတ် ထည့်သွင်းပြီး အော်ဒါတင်ပါ။
4. ဆိုင်မန်နေဂျာအကောင့် `09130000002` ဖြင့် `/store/diamond-stone-agri/admin/orders` တွင် အော်ဒါအသစ်ရောက်ရှိလာပုံ၊ စျေးနှုန်းညှိနှိုင်းမှု (Agreed Amount) ပြင်ဆင်ပုံနှင့် အတည်ပြုပုံကို ပြသနိုင်ပါသည်။

---

### Flow ၂: POS Touch/Keyboard Fast Checkout & Receipt Printing
1. Cashier ဖုန်း `09150000003` (PIN: `1234`) ဖြင့် POS `http://127.0.0.1:8501/store/datapos-mobile/pos` သို့ ဝင်ပါ။
2. ပစ္စည်းကတ်များကို နှိပ်ပြီးဖြစ်စေ၊ Barcode ရိုက်ထည့်၍ဖြစ်စေ ခြင်းထဲထည့်ပါ။
3. KPay / Wave / ငွေသား ရွေးချယ်ပြီး **ငွေချေမည် (Checkout)** နှိပ်ပါ — Receipt ပြေစာ ထွက်လာပုံကို စမ်းသပ်ပါ။

---

### Flow ၃: ဖုန်း/ကွန်ပျူတာ စက်ပြင် Service Ticket လက်ခံခြင်း (Technician Workflow)
1. Technical ဖုန်း `09160000004` (Password: `password`) ဖြင့် Login ဝင်ပါ — Service Jobs စာမျက်နှာသို့ တိုက်ရိုက် ရောက်ရှိပါမည်။
2. Customer ထံမှ ပြုပြင်ရန် ပစ္စည်းအသစ် လက်ခံခြင်း (New Job Ticket: ဥပမာ iPhone 11 ဘက်ထရီလဲ) ဖန်တီးပါ။
3. အပိုပစ္စည်းထုတ်ယူခြင်း (Spare Part Usage) နှင့် ပြုပြင်ခ (Service Charge) ထည့်သွင်းပြီး Job ပြီးဆုံးကြောင်း အတည်ပြုပါ။

---

### Flow ၄: Platform Multi-Store Management (Boss Only)
1. Platform Owner `09777000111` ဖြင့် Login ဝင်ပါ။
2. Platform Dashboard (`/admin/dashboard`) နှင့် Store စီမံခန့်ခွဲမှု (`/admin/stores`) သို့ သွားရောက်ပါ။
3. ဆိုင်ခွဲအသစ် ဖွင့်လှစ်ခြင်း၊ ဆိုင်ခွဲများအား ဖွင့်/ပိတ် (Activate/Deactivate) ပြုလုပ်ခြင်းနှင့် မလိုလားသော ဆိုင်များကို အပြီးဖျက်ခြင်း (Delete) လုပ်ဆောင်ချက်များကို စမ်းသပ်နိုင်ပါသည်။
