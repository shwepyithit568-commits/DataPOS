# 🛠️ DataPOS — Project Operations & Commands Cheatsheet (လုပ်ငန်းသုံး Command များ လမ်းညွှန်)

DataPOS ပရောဂျက်ကို Local တွင် Run ရန်၊ Database ပြုပြင်ရန်၊ စမ်းသပ်ရန်နှင့် ပြဿနာဖြေရှင်းရန် လိုအပ်သော အရေးကြီး Command များ စုစည်းမှု ဖြစ်ပါသည်။

---

## 📌 ၁။ ပတ်ဝန်းကျင် သတ်မှတ်ခြင်း (Environment Setup)

ဆရာကြီး၏ Windows တွင် `php` command ကို မည်သည့်နေရာကမဆို တိုက်ရိုက် ရိုက်နိုင်ရန် PowerShell (Administrator) တွင် **တစ်ကြိမ်သာ** Run ရန် -

```powershell
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";D:\xmapp\php", "User")
```
*(Run ပြီးပါက VS Code / Terminal ကို ပိတ်ပြီး ပြန်ဖွင့်ပေးပါ)*

> 💡 **မှတ်ချက်**: အကယ်၍ `php` ဟု ရိုက်၍ မရသေးပါက အောက်ပါအတိုင်း **`D:\xmapp\php\php.exe`** ဟု အပြည့်အစုံ ရေးပေးနိုင်ပါသည်။

---

## 🚀 ၂။ နေ့စဉ် Server နှင့် Frontend Run ရန် (Daily Dev Run)

### Server စတင်ဖွင့်ရန် (Port: 8502)
```powershell
D:\xmapp\php\php.exe artisan serve --host=127.0.0.1 --port=8502
# သို့မဟုတ် PATH ထည့်ထားလျှင်:
php artisan serve --host=127.0.0.1 --port=8502
```

### CSS & JavaScript Build/Watcher Run ရန်
```powershell
npm run dev
```

### Production အတွက် CSS/JS Compile လုပ်ရန်
```powershell
npm run build
```

---

## 🗄️ ၃။ Database & User Accounts စီမံခန့်ခွဲမှု (Database & Seeding)

### (က) Login ဝင်မရသည့်အခါ User Accounts များ ပြန်လည်ထည့်သွင်းခြင်း
```powershell
D:\xmapp\php\php.exe artisan db:seed --class=UatSeeder
# သို့မဟုတ်:
php artisan db:seed --class=UatSeeder
```

### (ခ) Database တစ်ခုလုံးကို Table အသစ်ပြန်ဆောက်ပြီး Demo စာရင်းများ ထည့်ခြင်း (Fresh Reset)
```powershell
D:\xmapp\php\php.exe artisan migrate:fresh --seed --seeder=UatSeeder
# သို့မဟုတ်:
php artisan migrate:fresh --seed --seeder=UatSeeder
```

### (ဂ) Migration အသစ်များသာ Run ရန် (Data မပျက်စေဘဲ)
```powershell
D:\xmapp\php\php.exe artisan migrate
```

---

## 🧹 ၄။ Cache & System ရှင်းလင်းခြင်း (Cache Clearing)

စာမျက်နှာ၊ Route သို့မဟုတ် Setting အပြောင်းအလဲများ မပေါ်သည့်အခါ Run ရန် -

```powershell
# Cache အားလုံး (Config, Route, View, Events) တစ်ပြိုင်နက် ရှင်းခြင်း:
D:\xmapp\php\php.exe artisan optimize:clear
# သို့မဟုတ်:
php artisan optimize:clear
```

သီးခြား Cache များသာ ရှင်းလိုလျှင်:
```powershell
php artisan view:clear    # Blade Views cache ရှင်းရန်
php artisan route:clear   # Routes cache ရှင်းရန်
php artisan config:clear  # .env / Config cache ရှင်းရန်
```

---

## 🧪 ၅။ စနစ် စစ်ဆေးသည့် Automated Tests (Testing Commands)

### Feature Test အားလုံး စစ်ဆေးရန်
```powershell
D:\xmapp\php\php.exe artisan test
```

### သီးခြား Module Test များ စစ်ဆေးရန်
```powershell
# POS Reports စစ်ဆေးရန်:
D:\xmapp\php\php.exe artisan test --filter=PosReportsRevampTest

# Sales Analytics စစ်ဆေးရန်:
D:\xmapp\php\php.exe artisan test --filter=SalesAnalyticsTest

# Sidebar & Navigation UX စစ်ဆေးရန်:
D:\xmapp\php\php.exe artisan test --filter=AdminSidebarNavigationUXTest

# Profit & Loss စစ်ဆေးရန်:
D:\xmapp\php\php.exe artisan test --filter=ProfitLossTest

# E-load Topup & Shift စစ်ဆေးရန်:
D:\xmapp\php\php.exe artisan test --filter=EloadRegisterTest
```

---

## 🔑 ၆။ စနစ်၏ မူလ Login အကောင့်များ (Default Test Accounts)

စနစ်တွင် စမ်းသပ်နိုင်သော Default Password မှာ အားလုံးအတွက် **`password`** ဖြစ်ပါသည်:

| ရာထူး (Role) | အမည် (Name) | ဖုန်းနံပါတ် (Phone / Login ID) | စကားဝှက် | ဝင်ရောက်နိုင်မည့် နေရာ / URL |
| :--- | :--- | :--- | :--- | :--- |
| 👑 **Store Manager** | Mg Hla | **`09100000002`** | `password` | `/store/datapos-mobile/admin/dashboard` *(POS PIN: `1234`)* |
| 🧑‍💼 **Staff / Cashier** | Ko Kyaw | **`09100000003`** | `password` | `/store/datapos-mobile/pos` |
| 🌐 **Platform Owner** | Owner | **`09100000001`** | `password` | `/admin/dashboard` |
| 🛍️ **Wholesale Customer** | Daw Aye | **`09100000004`** | `password` | `/store/datapos-mobile/wholesale` |
| 👤 **Retail Customer** | Ma Su | **`09100000006`** | `password` | `/store/datapos-mobile` |

---

## ⚠️ ၇။ အဖြစ်များသော ပြဿနာများနှင့် ဖြေရှင်းနည်း (Troubleshooting)

| ပြဿနာ | ဖြစ်ရသည့် အကြောင်းရင်း | အမြန်ဖြေရှင်းနည်း |
| :--- | :--- | :--- |
| **`php : The term 'php' is not recognized`** | Windows PATH ထဲ PHP မရှိခြင်း | `D:\xmapp\php\php.exe artisan ...` ဟု အပြည့်အစုံ ရေးသုံးပါ |
| **Login ဝင်မရခြင်း / User မရှိခြင်း** | Database ထဲ User Accounts မရှိခြင်း | `D:\xmapp\php\php.exe artisan db:seed --class=UatSeeder` ကို Run ပါ |
| **စာမျက်နှာ UI မပြောင်းလဲခြင်း** | View / Route Cache ကျန်နေခြင်း | `D:\xmapp\php\php.exe artisan optimize:clear` ကို Run ပါ |
| **Port 8502 အသုံးပြုနေသည်ဟု ပြခြင်း** | Server မပိတ်ဘဲ နောက်ကွယ်တွင် ကျန်နေခြင်း | Terminal အဟောင်းကို ပိတ်ပါ သို့မဟုတ် Port ပြောင်း run ပါ (`--port=8503`) |
