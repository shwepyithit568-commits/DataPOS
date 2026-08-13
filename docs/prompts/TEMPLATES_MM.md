# DataPOS — Agent Prompt Templates (MM)

> ဒီဖိုင်မှာ agent conversation အတွက် သုံးတဲ့ prompt templates တွေ အကုန် စုထားပါတယ်။
> လိုအပ်တဲ့ template ကို အောက်က section ကနေ ကူးပြီး Freebuff conversation အသစ်မှာ paste လုပ်ပါ။
>
> ဆက်စပ်: `docs/prompts/AI Development Agent Instructions (MM).md` (အသေးစိတ် agent instructions) · `docs/prompts/04_UI_LAYOUT_PROMPT_MM.md` (UI/Layout) · `docs/prompts/05_STOREFRONT_CUSTOMIZATION_ROADMAP_MM.md` (Storefront customization roadmap)

---

## မာတိကာ (Contents)

1. [နောက် Conversation အသစ်အတွက် စတင်ရမည့် Message](#1-နောက်-conversation-အသစ်အတွက်-စတင်ရမည့်-message)
2. [Project Start Prompt](#2-project-start-prompt)
3. [Bug Fix Prompt](#3-bug-fix-prompt)
4. [New Feature Prompt](#4-new-feature-prompt)
5. [Category Image Prompts (AI image generation)](#5-category-image-prompts-ai-image-generation)

---

## 1. နောက် Conversation အသစ်အတွက် စတင်ရမည့် Message

> အောက်ဖော်ပြပါ **「 ပထမဆုံးပြောရမည့်စာသား 」** ကို ကူးယူပြီး Freebuff မှာ **Conversation အသစ်** ဖွင့်ကာ ပထမဆုံး message အဖြစ် paste လုပ်ပါ။
>
> ဒီဖိုင်ရဲ့ နောက်ဆုံးအခြေအနေနဲ့ ကိုက်ညီကြောင်း မသေချာရင် မပို့ခင် DataPOS ထဲက `git log --oneline -1` နဲ့ `git status --short` စစ်ပြီး အောက်ပါ commit hash / test အရေအတွက်တွေကို မွမ်းမံပါ။

### ပထမဆုံးပြောရမည့်စာသား (copy-paste ready)

```
ဒီ project က DataPOS ပါ — D:\xmapp\htdocs\DataPOS မှာ ရှိတဲ့ သီးခြား Laravel 12 project ဖြစ်ပြီး
လက်ရှိ alinnthit.com ecommerce (D:\xmapp\htdocs\data_ecommerce) နဲ့ လုံးဝ သီးခြားပါ။

အရင်ဆုံး ဒီဖိုင်တွေကို ဖတ်ပါ:
- DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md  (POS စနစ်ရဲ့ စည်းမျဉ်းစာချုပ် — MUST READ)
- Source_of_Truth_MM.md  (Business + Architecture Rules)
- 2026-08-02_FIXES.md  (Implementation History / Change Log)
- Testing_check.md  (Bug / issue / pre-production testing အခြေအနေ)
- docs/pos-resale-plan/00-overview.md → 04-implementation-phases.md  (ခြုံငုံအစီအစဉ်)
- README.md  (run နည်း)

လက်ရှိအခြေအနေ:
- Git repo: main branch — remote = https://github.com/shwepyithit568-commits/DataPOS.git
- Tests: အကုန် pass (SQLite local) — လက်ရှိ အရေအတွက် စစ်ရန်: php artisan test
- Local dev: DB_CONNECTION=sqlite, FORCE_HTTPS=false, port 8501 (php artisan serve)
- Brand: AlinnThit အားလုံး DataPOS အဖြစ် ပြောင်းပြီးပြီ (slug datapos-mobile)
```

### နောက်ခံ အကျဉ်းချုပ် (ဆရာကြီးနဲ့ ဆွေးနွေးပြီးသား ဆုံးဖြတ်ချက်များ)

| အကြောင်း | ဆုံးဖြတ်ချက် |
|---|---|
| Codebase | **တစ်ခုတည်း** — DataPOS က main repo ကနေ သီးခြားကူးယူထားတဲ့ working copy၊ လွတ်လပ်စွာ ဆောက်မယ် |
| Module isolation | POS က `App\POS\...` namespace + `/pos` routes + သီးခြား SW/CSS/JS/tests — ecommerce code နဲ့ မရောနှောရ |
| Tables | POS tables သီးခြား add-only — `orders` table ကို POS sales အဖြစ် ပြန်မသုံးရ |
| Database | Cloud MySQL = central source of truth (Hostinger); offline devices = IndexedDB; local dev = SQLite |
| Hosting | Hostinger Unlimited shared hosting, MySQL ပါ (48-month) — Phase 4–5 ဆို VPS upgrade path |
| Resale | ဖောက်သည်တစ်ယောက် = store တစ်ခု; feature flags / capabilities နဲ့ POS / ecommerce module ရွေးဖွင့်နိုင် |
| Industry | Core က industry-agnostic (UOM, fractional qty, custom fields) — Gold Shop / Fuel / Restaurant packs က နောက်မှ |
| Offline | Periodic offline (SoT ဒီဇိုင်း) + Fully local mode (SQLite + LAN) နှစ်မျိုးလုံး |

### အရေးကြီး လမ်းညွှန်ချက်များ (နောက် conversation မှာ ထပ်ပြောစရာမလိုအောင်)

1. **DataPOS ထဲက `deploy-alinnthit.sh` ကို run မလုပ်ပါနဲ့** — ဒါက live site (alinnthit.com) ကို ညွှန်နေလို့ပါ။ DataPOS အတွက် deploy script အသစ် လိုအပ်ရင် `deploy-datapos.sh` လို သီးခြားရေးပါ။
2. **`deploy-datapos.sh` ကို Hostinger deploy မလုပ်ရသေးပါ** — `ALLOW_HOSTINGER_DEPLOY=true` flag မပါရင် script က ABORT လုပ်ပါတယ် (project က hosting မှာ မတင်ရသေးလို့)။
3. `.env` မှာ `FORCE_HTTPS=false` — local HTTP dev အတွက်။ Production မှာတော့ `true` ပြန်ထားရမယ်။
4. Live site (data_ecommerce) ကို DataPOS အလုပ်တွေနဲ့ **မထိပါနဲ့** — သီးခြား repo နှစ်ခုပါ။
5. Migration ရေးတိုင်း SQLite + MySQL နှစ်မျိုးလုံး run ပြီး verify လုပ်ပါ (အခု suite က SQLite နဲ့ green ဖြစ်နေတယ်)။

---

## 2. Project Start Prompt

သင်သည် အောက်ပါနေရာတွင်ရှိသော **DataPOS** project ကို အလုပ်လုပ်ရမည်။

`D:\xmapp\htdocs\DataPOS`

Code အသစ်ရေးခြင်း၊ ရှိပြီးသား code ပြင်ခြင်း၊ ဖျက်ခြင်း၊ Refactor လုပ်ခြင်း မပြုလုပ်မီ အောက်ပါ project instruction / documentation files များကို အရင်ဖတ်ပြီး လိုက်နာပါ။

1. `docs/prompts/AI Development Agent Instructions (MM).md`
2. `2026-08-02_FIXES.md`
3. `Source_of_Truth_MM.md`
4. `Testing_check.md`

ဤဖိုင်များကို DataPOS project ၏ authoritative project context အဖြစ် သတ်မှတ်ပါ။

မဖြစ်မနေ လိုက်နာရမည့် စည်းကမ်းများ—

- သက်ဆိုင်ရာ rules နှင့် existing implementation history ကို မဖတ်ရသေးဘဲ coding မစပါနှင့်။
- ရှိပြီးသား components, services, controllers, Blade structures, Alpine.js logic နှင့် patterns များကို အရင် Reuse / Extend လုပ်ပါ။
- Livewire နှင့် jQuery မထည့်ပါနှင့်။
- `store_slug` isolation ကို မပျက်စေရ။ Store ownership ကို query / resource level အထိ verify လုပ်ပါ။
- Nested Category `parent_id` architecture ကို မပျက်စေရ။
- Product Variant ၏ grouped `attributes` JSON architecture နှင့် legacy backward compatibility ကို ထိန်းထားပါ။
- Blade file များတွင် user-facing English text ကို hardcode မရေးပါနှင့်။ Translation အသစ်ရှိပါက `en`, `my`, `zh_CN` language files သုံးခုလုံးကို တစ်ပြိုင်တည်း update လုပ်ပါ။
- Local SQLite နှင့် Production MySQL နှစ်ခုလုံးအတွက် compatibility ကို စဉ်းစားပါ။
- Broad rewrite ထက် minimal, safe, production-ready diff ကို ဦးစားပေးပါ။
- Change တစ်ခုက 5 files နှင့်အထက် ထိခိုက်မည်၊ complex schema change ဖြစ်မည်၊ inventory/payment/accounting/store isolation architecture ကို ထိမည်ဆိုပါက full implementation မစမီ affected files, proposed approach, risks ကို အရင်ပြပြီး confirmation တောင်းပါ။
- Code ပြင်ပြီးပါက relevant verification / testing လုပ်ပါ။
- Meaningful code change တိုင်း `2026-08-02_FIXES.md` ကို update လုပ်ပါ။
- Bug/Test status ပြောင်းပါက `Testing_check.md` ကို update လုပ်ပါ။
- Business Rule သို့မဟုတ် Architecture Rule အမှန်တကယ် ပြောင်းမှသာ `Source_of_Truth_MM.md` ကို update လုပ်ပါ။
- Code + Testing + Documentation ပြည့်စုံမှသာ `DONE` ဟု သတ်မှတ်ပါ။
- Tailwind class အသစ် သို့မဟုတ် arbitrary class ထည့်ပါက `npm run build` run ရမည်ဟု final response တွင် သတိပေးပါ။
- Migration အသစ်ပါက `php artisan migrate` run ရမည်ဟု final response တွင် သတိပေးပါ။

Development Server:

`php artisan serve --host=0.0.0.0 --port=8501`

`Port 8000` သည် project အဟောင်းဖြစ်သောကြောင့် မသုံးပါနှင့်။

အထက်ပါ files များကို ဖတ်ပြီးပါက အရှည်ကြီး summary မပေးပါနှင့်။

အောက်ပါစာတစ်ကြောင်းသာ ပြန်ပါ—

`Project context loaded. Ready for the task.`

---

## 3. Bug Fix Prompt

ဒီ Bug ကို ပြင်ပါ။

Coding မစမီ `Testing_check.md` ကို အရင်စစ်ပြီး ဒီ issue ရှိပြီးသားလား ကြည့်ပါ။

ပြီးရင် သက်ဆိုင်ရာ flow ကို အောက်ပါအစဉ်အတိုင်း trace လုပ်ပါ။

`Route → Middleware → Controller → Service/Model → Blade → Alpine.js → Database/Query`

Visible symptom ကိုပဲ မပြင်ပါနှင့်။ Root Cause ကိုရှာပြီး အနည်းဆုံးလိုအပ်သော safe fix ကိုသာ လုပ်ပါ။

မဖြစ်မနေ စစ်ရမည့်အချက်များ—

- `store_slug` isolation မပျက်စေရ
- Authorization / Validation မှန်ကန်စေရ
- Cross-store data leakage / IDOR မဖြစ်စေရ
- Existing working features မပျက်စေရ
- SQLite local နှင့် MySQL production compatibility ကို စဉ်းစားပါ
- Existing component / service / Alpine pattern ရှိပါက Reuse လုပ်ပါ
- Unrelated code ကို မပြင်ပါနှင့်

Bug ပြင်ပြီးပါက—

- Relevant testing / verification လုပ်ပါ
- Regression risk ကို စစ်ပါ
- `2026-08-02_FIXES.md` တွင် Item အသစ်ဖြင့် change log ထည့်ပါ
- `Testing_check.md` တွင် issue status ကို update လုပ်ပါ
- Business/Architecture Rule အမှန်တကယ် ပြောင်းမှသာ `Source_of_Truth_MM.md` ကို update လုပ်ပါ

Final response ကို တိုတိုပဲပေးပါ။

ဖော်ပြရမည့်အချက်များ—

- Root Cause
- Changed Files
- What was fixed
- Verified / Not Verified
- Run ရမည့် command ရှိပါက command

Tailwind class အသစ်ထည့်ခဲ့ပါက:

`npm run build`

Migration ပါခဲ့ပါက:

`php artisan migrate`

---

## 4. New Feature Prompt

ဒီ Feature အသစ်ကို **DataPOS ရဲ့ ရှိပြီးသား architecture နှင့် patterns ကို Reuse လုပ်ပြီး** Production-ready အဖြစ် တည်ဆောက်ပါ။

Coding မစမီ—

1. `2026-08-02_FIXES.md` ထဲမှာ ဆင်တူ implementation ရှိမရှိ ရှာပါ။
2. `Source_of_Truth_MM.md` ထဲမှာ သက်ဆိုင်ရာ business / architecture rules ကို စစ်ပါ။
3. `Testing_check.md` ထဲမှာ ဆက်စပ် known issue / limitation ရှိမရှိ စစ်ပါ။
4. ရှိပြီးသား Controller, Service, Blade Component, Alpine.js logic, Admin UI pattern, Storefront filter pattern များကို Reuse / Extend လုပ်နိုင်မလား စစ်ပါ။

မလိုအပ်ဘဲ architecture အသစ် မတည်ဆောက်ပါနှင့်။

မဖြစ်မနေ လိုက်နာရမည့်အချက်များ—

- `store_slug` isolation မပျက်စေရ
- Nested Category `parent_id` architecture ကို ထိန်းထားပါ
- Product Variant grouped `attributes` JSON နှင့် legacy backward compatibility ကို ထိန်းထားပါ
- Livewire / jQuery မသုံးပါနှင့်
- Blade user-facing text ကို hardcode မရေးပါနှင့်
- Translation အသစ်ပါက `lang/en/messages.php`, `lang/my/messages.php`, `lang/zh_CN/messages.php` သုံးဖိုင်လုံး update လုပ်ပါ
- Validation, Authorization, Data Integrity, Security ကို ထည့်စဉ်းစားပါ
- SQLite local နှင့် MySQL production compatibility ကို စစ်ပါ
- Database multi-write logic ရှိပါက Transaction လိုမလို စစ်ပါ
- N+1 query, missing index, large unpaginated query စသည့် performance risk များကို စစ်ပါ
- Broad rewrite ထက် minimal safe implementation ကို ဦးစားပေးပါ

Feature တစ်ခုက 5 files နှင့်အထက် ထိခိုက်မည်၊ complex schema change ဖြစ်မည်၊ inventory/payment/accounting/store isolation architecture ကို ထိမည်ဆိုပါက full code မရေးမီ—

- Affected Files
- Proposed Approach
- Risks

ကို အရင်ပြပြီး confirmation တောင်းပါ။

Implementation ပြီးပါက—

- Relevant testing / verification လုပ်ပါ
- Regression risk စစ်ပါ
- `2026-08-02_FIXES.md` ကို Item အသစ်ဖြင့် update လုပ်ပါ
- `Testing_check.md` ကို သက်ဆိုင်ပါက update လုပ်ပါ
- Business/Architecture Rule ပြောင်းမှသာ `Source_of_Truth_MM.md` ကို update လုပ်ပါ

Final response တွင်—

- Exact File Paths
- Precise Diff / Replace Block / Full Code as needed
- Verified / Not Verified
- Required Commands

ကိုသာ တိုတိုတိတိ ဖော်ပြပါ။

Tailwind class အသစ် သို့မဟုတ် arbitrary class ထည့်ပါက:

`npm run build`

Migration အသစ်ပါက:

`php artisan migrate`

---

## 5. Category Image Prompts (AI image generation)

DataPOS ရဲ့ Category ဓာတ်ပုံတွေကို AI image generator (Midjourney / DALL·E / GPT-Image / Stable Diffusion / Flux) နဲ့ ထုတ်ဖို့ prompts တွေပါ။

**သုံးမယ့်နေရာ:** Category image တွေက storefront မှာ **သေးငယ်တဲ့ square icon (44px tile)** အနေနဲ့ ပြတယ် — ဒါကြောင့် **1:1 square + subject ဗဟိုမှာတစ်ခုတည်း + နောက်ခံ ရှင်းရှင်းလင်းလင်း** ဖြစ်အောင် prompt တွေ ရေးထားတယ်။

### 5.1 ပုံစံအခြေခံ (Style Base) — အားလုံးအတွက် တူညီတဲ့အပိုင်း

Prompts တိုင်းရဲ့ အစ/အဆုံးမှာ ပါတဲ့ shared style ပိုင်း — category အားလုံး တစ်ပုံစံတည်း (cohesive) ဖြစ်အောင် ထားတာ။

> **Style suffix (prompt အဆုံးမှာ ထည့်ရန်):**
> `professional e-commerce product photography, single subject centered, soft studio lighting, clean seamless light-gray background, high detail, sharp focus, commercial quality`

> **Negative prompt (Stable Diffusion / Flux သုံးရင်):**
> `text, watermark, logo, words, people, hands, clutter, dark background, low quality, blurry, distorted, extra objects`

### 5.2 Category Prompts (ကူးယူသုံးရန်)

#### 📱 Mobile Phones
```
A premium modern smartphone floating at a slight 3/4 angle showing its screen with a colorful abstract wallpaper, professional e-commerce product photography, single subject centered, soft studio lighting, clean seamless light-gray background, high detail, sharp focus, commercial quality
```

#### 🎧 Accessories
```
Wireless earbuds with their charging case and a neatly coiled USB-C charging cable arranged together, professional e-commerce product photography, centered composition, soft studio lighting, clean seamless light-gray background, high detail, sharp focus, commercial quality
```

#### 📹 CCTV & Security
```
A modern white dome security camera mounted view, professional e-commerce product photography, single subject centered, soft studio lighting, clean seamless light-gray background, crisp detail, sharp focus, commercial quality
```

#### 💻 Computer & Laptop
```
A sleek modern ultrabook laptop half open at a slight angle with a soft glowing screen, professional e-commerce product photography, single subject centered, minimal studio setup, soft lighting, clean seamless light-gray background, high detail, commercial quality
```

#### 👕 Fashion
```
Neatly folded casual clothing stack with a small crossbody bag arranged as a tidy flat lay, professional e-commerce product photography, soft natural lighting, clean light background, centered composition, high detail, commercial quality
```

#### 🌐 Network & WiFi
```
A modern white WiFi router with antennas standing upright, professional e-commerce product photography, single subject centered, soft studio lighting, clean seamless light-gray background, high detail, sharp focus, commercial quality
```

### 5.3 Tool အလိုက် သုံးနည်း

#### Midjourney
```
/imagine <အပေါ်က prompt> --ar 1:1 --v 6 --no text, watermark, logo, people
```
(ပိုကောင်းချင်ရင် အဆုံးမှာ `--style raw` ထည့်နိုင်တယ် — ဓာတ်ပုံပိုဆန်တယ်)

#### DALL·E / GPT-Image (ChatGPT)
```
Generate a square 1:1 image: <အပေါ်က prompt>. No text, no watermark, no logo.
```

#### Stable Diffusion / Flux (AUTOMATIC1111 / ComfyUI)
- **Prompt:** `<အပေါ်က prompt>`
- **Negative prompt:** `text, watermark, logo, words, people, hands, clutter, dark background, low quality, blurry`
- **Resolution:** 1024×1024 (square)

### 5.4 Store Brand Accent (optional)

Store ရဲ့ အရောင် (violet / fuchsia) နဲ့ လိုက်ချင်ရင် prompt အဆုံးမှာ ဒါလေး ထည့်နိုင်တယ်:
```
with a subtle violet and fuchsia accent glow on the product edges
```
ဒါပေမယ့် thumbnail သေးသေးမှာ ပိုသန့်ဖို့ **မထည့်တာ ပိုကောင်းတယ်** — နောက်ခံ အရောင်သန့်သန့်နဲ့ ထားတာ category cards တွေ ပိုလှတယ်။

### 5.5 တင်နည်း (Upload)

1. ရလာတဲ့ image က square မဟုတ်ရင် **1:1 ဖြတ်ပြီးမှ** တင်ပါ (site က object-cover နဲ့ ပြတာမို့ မဖြတ်ရင်လည်း အလုပ်လုပ်တယ်)
2. **Admin → Categories → ပစ္စည်းအုပ်စု (Edit) → Image** မှာ တင်ပါ
3. PNG / JPG / JPEG / WebP (max 10MB) — site က WebP ပြောင်းပေးတယ်

**မှတ်ချက်:** TEST-Cat-Cable / TEST-Cat-Screen တို့က test data မို့ image မလိုဘူး — production မတင်ခင် ဖျက်ရမယ့်ဟာတွေပါ။
