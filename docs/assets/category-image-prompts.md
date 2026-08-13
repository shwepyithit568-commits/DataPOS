# Category Image Prompts — AI Image Generation

DataPOS ရဲ့ Category ဓာတ်ပုံတွေကို AI image generator (Midjourney / DALL·E / GPT-Image / Stable Diffusion / Flux) နဲ့ ထုတ်ဖို့ prompts တွေပါ။

**သုံးမယ့်နေရာ:** Category image တွေက storefront မှာ **သေးငယ်တဲ့ square icon (44px tile)** အနေနဲ့ ပြတယ် — ဒါကြောင့် **1:1 square + subject ဗဟိုမှာတစ်ခုတည်း + နောက်ခံ ရှင်းရှင်းလင်းလင်း** ဖြစ်အောင် prompt တွေ ရေးထားတယ်။

---

## 1. ပုံစံအခြေခံ (Style Base) — အားလုံးအတွက် တူညီတဲ့အပိုင်း

Prompts တိုင်းရဲ့ အစ/အဆုံးမှာ ပါတဲ့ shared style ပိုင်း — category အားလုံး တစ်ပုံစံတည်း (cohesive) ဖြစ်အောင် ထားတာ။

> **Style suffix (prompt အဆုံးမှာ ထည့်ရန်):**
> `professional e-commerce product photography, single subject centered, soft studio lighting, clean seamless light-gray background, high detail, sharp focus, commercial quality`

> **Negative prompt (Stable Diffusion / Flux သုံးရင်):**
> `text, watermark, logo, words, people, hands, clutter, dark background, low quality, blurry, distorted, extra objects`

---

## 2. Category Prompts (ကူးယူသုံးရန်)

### 📱 Mobile Phones
```
A premium modern smartphone floating at a slight 3/4 angle showing its screen with a colorful abstract wallpaper, professional e-commerce product photography, single subject centered, soft studio lighting, clean seamless light-gray background, high detail, sharp focus, commercial quality
```

### 🎧 Accessories
```
Wireless earbuds with their charging case and a neatly coiled USB-C charging cable arranged together, professional e-commerce product photography, centered composition, soft studio lighting, clean seamless light-gray background, high detail, sharp focus, commercial quality
```

### 📹 CCTV & Security
```
A modern white dome security camera mounted view, professional e-commerce product photography, single subject centered, soft studio lighting, clean seamless light-gray background, crisp detail, sharp focus, commercial quality
```

### 💻 Computer & Laptop
```
A sleek modern ultrabook laptop half open at a slight angle with a soft glowing screen, professional e-commerce product photography, single subject centered, minimal studio setup, soft lighting, clean seamless light-gray background, high detail, commercial quality
```

### 👕 Fashion
```
Neatly folded casual clothing stack with a small crossbody bag arranged as a tidy flat lay, professional e-commerce product photography, soft natural lighting, clean light background, centered composition, high detail, commercial quality
```

### 🌐 Network & WiFi
```
A modern white WiFi router with antennas standing upright, professional e-commerce product photography, single subject centered, soft studio lighting, clean seamless light-gray background, high detail, sharp focus, commercial quality
```

---

## 3. Tool အလိုက် သုံးနည်း

### Midjourney
```
/imagine <အပေါ်က prompt> --ar 1:1 --v 6 --no text, watermark, logo, people
```
(ပိုကောင်းချင်ရင် အဆုံးမှာ `--style raw` ထည့်နိုင်တယ် — ဓာတ်ပုံပိုဆန်တယ်)

### DALL·E / GPT-Image (ChatGPT)
```
Generate a square 1:1 image: <အပေါ်က prompt>. No text, no watermark, no logo.
```

### Stable Diffusion / Flux (AUTOMATIC1111 / ComfyUI)
- **Prompt:** `<အပေါ်က prompt>`
- **Negative prompt:** `text, watermark, logo, words, people, hands, clutter, dark background, low quality, blurry`
- **Resolution:** 1024×1024 (square)

---

## 4. Store Brand Accent (optional)

Store ရဲ့ အရောင် (violet / fuchsia) နဲ့ လိုက်ချင်ရင် prompt အဆုံးမှာ ဒါလေး ထည့်နိုင်တယ်:
```
with a subtle violet and fuchsia accent glow on the product edges
```
ဒါပေမယ့် thumbnail သေးသေးမှာ ပိုသန့်ဖို့ **မထည့်တာ ပိုကောင်းတယ်** — နောက်ခံ အရောင်သန့်သန့်နဲ့ ထားတာ category cards တွေ ပိုလှတယ်။

---

## 5. တင်နည်း (Upload)

1. ရလာတဲ့ image က square မဟုတ်ရင် **1:1 ဖြတ်ပြီးမှ** တင်ပါ (site က object-cover နဲ့ ပြတာမို့ မဖြတ်ရင်လည်း အလုပ်လုပ်တယ်)
2. **Admin → Categories → ပစ္စည်းအုပ်စု (Edit) → Image** မှာ တင်ပါ
3. PNG / JPG / JPEG / WebP (max 10MB) — site က WebP ပြောင်းပေးတယ်

**မှတ်ချက်:** TEST-Cat-Cable / TEST-Cat-Screen တို့က test data မို့ image မလိုဘူး — production မတင်ခင် ဖျက်ရမယ့်ဟာတွေပါ။
