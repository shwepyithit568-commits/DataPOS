# DataPOS — Local Device Test Note

## Project Path

```bat
D:\xmapp\htdocs\data_ecommerce
```

---

## Tonight — Stop the Laravel Server

Laravel server running terminal တွင်:

```bat
Ctrl + C
```

ပြီးလျှင် Command Prompt window ကိုပိတ်ပြီး PC ကို shutdown လုပ်နိုင်သည်။

---

## Tomorrow Morning — Start Again

### 1. Open Command Prompt

### 2. Go to the project folder

```bat
cd /d D:\xmapp\htdocs\data_ecommerce
```

### 3. Check the current PC IPv4 address

```bat
ipconfig
```

Expected IPv4:

```text
192.168.10.161
```

### 4. Clear Laravel caches

```bat
D:\xmapp\php\php.exe artisan optimize:clear
```

### 5. Start the Laravel LAN server

```bat
D:\xmapp\php\php.exe artisan serve --host=0.0.0.0 --port=8500
```

Command Prompt window ကို မပိတ်ရပါ။ Window ပိတ်လျှင် Laravel server ရပ်သွားမည်။

---

## Phone URLs

### Storefront

```text
http://192.168.10.161:8500/store/datapos-mobile
```

### Admin

```text
http://192.168.10.161:8500/store/datapos-mobile/admin/
```

### Admin Login

- Local DB (SQLite) ထဲမှာ seeded/existing admin account ရှိပြီးသားဆိုရင် အဲဒါကို သုံးပါ။
- မရှိသေးဘူးဆိုရင် interactive prompt နဲ့ ဖန်တီးပါ (platform_owner role):

```bat
D:\xmapp\php\php.exe artisan production:create-admin
```

  (phone format: `09xxxxxxxxx`, password min 12 characters + uppercase + number + symbol)

---

## Phone Test Requirements

- Phone နှင့် PC ကို router တစ်လုံးတည်း၏ private network တွင်ချိတ်ထားပါ။
- Phone ကို Guest Wi-Fi မချိတ်ပါနှင့်။
- Mobile Data ကို ယာယီပိတ်ထားပါ။
- VPN / Proxy ကို ယာယီပိတ်ထားပါ။
- Router port forwarding မဖွင့်ပါနှင့်။

---

## If the PC IPv4 Address Changes

ဥပမာ IPv4 အသစ်က:

```text
192.168.10.165
```

`.env` ထဲက:

```env
APP_URL=http://192.168.10.161:8500
```

ကို:

```env
APP_URL=http://192.168.10.165:8500
```

အဖြစ်ပြောင်းပါ။

ပြီးလျှင်:

```bat
D:\xmapp\php\php.exe artisan optimize:clear
D:\xmapp\php\php.exe artisan serve --host=0.0.0.0 --port=8500
```

Phone URL ကိုလည်း IP အသစ်ဖြင့်ဖွင့်ပါ:

```text
http://192.168.10.165:8500/store/datapos-mobile
```

---

## Current Temporary LAN Configuration

```env
APP_URL=http://192.168.10.161:8500
FORCE_HTTPS=false
SESSION_SECURE_COOKIE=false
```

---

## Important Notes

- Current local database is SQLite, so XAMPP MySQL ကို start လုပ်ရန်မလိုပါ။
- Windows network profile ကို Private အဖြစ်သတ်မှတ်ထားသည်။
- Firewall rule name:

```text
ACDC Mobile LAN Test TCP 8500
```

- Firewall rule သည် Private profile, TCP port 8500, subnet `192.168.10.0/24` အတွက်သာဖြစ်သည်။
- Tailwind/Vite class အသစ်တွေ ထည့်ပြီးရင် CSS ပြောင်းလဲမှု ဖုန်းမှာ မမြင်ရရင်:

```bat
cd /d D:\xmapp\htdocs\data_ecommerce
npm run build
```

ပြီးမှ `optimize:clear` + server restart လုပ်ပါ။
