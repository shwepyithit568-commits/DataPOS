# DataPOS — Agent Prompt Templates (MM)

> ဒီဖိုင်မှာ agent conversation အတွက် သုံးတဲ့ prompt templates တွေ အကုန် စုထားပါတယ်။
> လိုအပ်တဲ့ template ကို အောက်က section ကနေ ကူးပြီး Freebuff conversation အသစ်မှာ paste လုပ်ပါ။
>
> **ဖိုင်အကြောင်း (2026-08-13 ပေါင်းစည်းပြီး):** အောက်က section အားလုံး ဒီဖိုင်ထဲမှာ ပါပါတယ် —
> ① AI Agent Instructions (Version 2.0-MM) · ② UI/Layout ပြင်ဆင်ခြင်း Prompt ·
> ③ Storefront Customization Roadmap (3-Phase) · ④ အခြား conversation templates

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
- CHANGELOG.md  (Implementation History / Change Log)
- Testing_check.md  (Bug / issue / pre-production testing အခြေအနေ)
- docs/pos-resale-plan/ROADMAP.md → ROADMAP.md  (ခြုံငုံအစီအစဉ်)
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

1. ဒီဖိုင်ရဲ့ "AI Development Agent Instructions" section (Version 2.0-MM — 2026-08-13 မှာ ဒီဖိုင်ထဲ ပေါင်းထည့်ပြီး)
2. `CHANGELOG.md`
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
- Meaningful code change တိုင်း `CHANGELOG.md` ကို update လုပ်ပါ။
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
- `CHANGELOG.md` တွင် Item အသစ်ဖြင့် change log ထည့်ပါ
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

1. `CHANGELOG.md` ထဲမှာ ဆင်တူ implementation ရှိမရှိ ရှာပါ။
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
- `CHANGELOG.md` ကို Item အသစ်ဖြင့် update လုပ်ပါ
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


---

# 🎨 DataPOS — UI / Layout ပြင်ဆင်ခြင်း Prompt

ဒီ Page / UI Layout ကို **DataPOS ရဲ့ ရှိပြီးသား Design System, Blade Components, Alpine.js Logic နှင့် Tailwind CSS Pattern များကို အခြေခံပြီး** ပြင်ဆင်ပါ။

UI လှအောင်ပြင်ရုံသာ မဟုတ်ဘဲ Existing Functionality မပျက်စေရန် ဦးစားပေးပါ။

---

## 1. Coding မစမီ

အရင်ဆုံး အောက်ပါ Documentation များကို သက်ဆိုင်ရာအတိုင်း စစ်ပါ။

1. `CHANGELOG.md`
2. `Source_of_Truth_MM.md`
3. `Testing_check.md`

ပြီးရင် ပြင်မည့် Page ၏ Existing Implementation ကို စစ်ပါ။

အထူးသဖြင့်—

- Blade View
- Blade Components
- Layout
- Alpine.js State / Events
- Tailwind Classes
- Controller မှ View သို့ပို့သော Data
- Form Actions
- Routes
- Responsive Behavior
- Localization
- Existing JavaScript behavior

တို့ကို အရင်နားလည်ပြီးမှ UI ကို ပြင်ပါ။

---

# 2. EXISTING DESIGN PATTERN ကို REUSE လုပ်ပါ

အသစ် Design System တစ်ခု မတည်ဆောက်ပါနှင့်။

ရှိပြီးသား—

- Cards
- Tables
- Buttons
- Inputs
- Selects
- Modals
- Dropdowns
- Badges
- Accordions
- Tabs
- Pagination
- Empty States
- Loading States
- Toast / Alert
- Mobile Navigation
- Sidebar
- Header

တို့ကို အရင်ရှာပြီး Reuse / Extend လုပ်ပါ။

Priority:

`Reuse → Extend → Refactor → Create New`

Duplicate UI Component မဖန်တီးပါနှင့်။

---

# 3. ADMIN UI ပြင်ပါက

Admin Page ဖြစ်ပါက အောက်ပါ Existing Pages များကို Design Reference အဖြစ် အရင်ကြည့်ပါ။

`admin/brands/index.blade.php`

`admin/categories/index.blade.php`

Existing Admin Design Language ကို ထိန်းထားပါ။

အထူးသဖြင့်—

- Header
- Card
- Accordion
- Table
- Action Buttons
- Filter
- Search
- Form Controls
- Spacing
- Border Radius
- Typography
- Responsive Layout

တို့ကို တူညီစွာ ထိန်းပါ။

Page တစ်ခုတည်းအတွက် မတူညီသော Admin Design System အသစ် မတည်ဆောက်ပါနှင့်။

---

# 4. STOREFRONT UI ပြင်ပါက

Storefront Page / Catalog UI ဖြစ်ပါက အောက်ပါ Existing Implementation ကို အရင်ကြည့်ပါ။

`CatalogController@index`

`resources/views/storefront/catalog/index.blade.php`

ရှိပြီးသား—

- Catalog Layout
- Product Card
- Category Sidebar
- Filters
- Search
- Sorting
- Pagination
- Mobile Filter
- Alpine.js State
- URL Query Parameters

တို့ကို မပျက်စေရ။

UI ပြင်ရင်း Filtering Logic အသစ်တစ်ခု ထပ်မတည်ဆောက်ပါနှင့်။

---

# 5. FUNCTIONALITY မပျက်စေရ

UI Layout ပြောင်းခြင်းကြောင့် Existing Functionality မပြောင်းရ။

အထူးသဖြင့်—

- Form Submit
- Validation Errors
- Edit
- Delete
- Search
- Filter
- Sort
- Pagination
- Modal
- Dropdown
- Accordion
- Tabs
- Alpine.js Events
- Keyboard Interaction
- Loading State
- Empty State

တို့ကို စစ်ပါ။

UI Change သာတောင်းထားပါက Business Logic / Controller Logic / Database Logic ကို မလိုအပ်ဘဲ မပြင်ပါနှင့်။

---

# 6. ALPINE.JS SAFETY

Existing Alpine.js Logic ကို UI ပြင်ရင်း မဖျက်ပါနှင့်။

အထူးသဖြင့်—

`x-data`

`x-show`

`x-model`

`x-bind`

`x-on`

`@click`

`@submit`

`$dispatch`

`$watch`

`x-transition`

စသည်တို့ကို ဖယ်ရှား/ပြောင်းလဲမီ ဘာအတွက်အသုံးပြုထားသလဲ စစ်ပါ။

Existing Alpine State ကို Reuse လုပ်ပါ။

UI ပြင်ဖို့အတွက် Alpine Component အသစ် မလိုအပ်ဘဲ မဖန်တီးပါနှင့်။

jQuery နှင့် Livewire မထည့်ပါနှင့်။

---

# 7. RESPONSIVE DESIGN — MANDATORY

UI ကို Desktop တစ်ခုတည်းအတွက် မပြင်ပါနှင့်။

အနည်းဆုံး—

- Mobile
- Tablet
- Desktop

တို့တွင် အသုံးပြုနိုင်ရမည်။

အထူးသဖြင့် စစ်ရမည့်အချက်များ—

- Horizontal Overflow
- Text Overflow
- Table Overflow
- Button Size
- Touch Target
- Modal Width
- Sidebar Behavior
- Dropdown Position
- Image Scaling
- Form Layout
- Long Burmese Text
- Long Product Names

Mobile UI တွင် unnecessary clicks ကို လျှော့ပါ။

---

# 8. USER EXPERIENCE

Target Users များသည် Technical User မဟုတ်နိုင်သောကြောင့် UI ကို ရိုးရှင်းစွာထားပါ။

ဦးစားပေးရမည့်အချက်—

- ရှင်းလင်းသော hierarchy
- မြင်သာသော primary action
- Minimal clicks
- Readable text
- Simple navigation
- Consistent button placement
- Clear status
- Clear validation message
- Useful empty state
- Useful loading state

Decorative Design ကြောင့် usability မကျစေရ။

---

# 9. LOCALIZATION

Blade File တွင် User-facing English Text ကို hardcode မရေးပါနှင့်။

Wrong:

`Save Product`

Correct:

`{{ __('messages.save_product') }}`

Translation Key အသစ်ပါက—

- `lang/en/messages.php`
- `lang/my/messages.php`
- `lang/zh_CN/messages.php`

သုံးဖိုင်လုံး update လုပ်ပါ။

UI Layout ကို English Text Length တစ်မျိုးတည်းနဲ့ မစမ်းပါနှင့်။

Myanmar နှင့် Chinese Text Length ကွာခြားမှုကို စဉ်းစားပါ။

---

# 10. TAILWIND CSS v4

Existing Tailwind Pattern ကို Reuse လုပ်ပါ။

မလိုအပ်ဘဲ arbitrary values များ မသုံးပါနှင့်။

ဥပမာ—

`w-[97px]`

`top-[73px]`

`left-[13px]`

ကဲ့သို့ Magic Number များကို Existing Utility ဖြင့် ဖြေရှင်းနိုင်ပါက Existing Utility ကို ဦးစားပေးပါ။

Tailwind Class အသစ်၊ Dynamic Class သို့မဟုတ် Arbitrary Class ထည့်ပါက Production CSS တွင် ပါဝင်မည်ကို စစ်ပါ။

လိုအပ်ပါက Final Response တွင်—

`npm run build`

ဟု မဖြစ်မနေ ဖော်ပြပါ။

---

# 11. ACCESSIBILITY

သက်ဆိုင်ရာ UI များတွင်—

- Label
- Button semantics
- Focus state
- Keyboard navigation
- Contrast
- Disabled state
- Error state
- `aria-*` attributes

တို့ကို စဉ်းစားပါ။

Icon-only Button များတွင် User နားလည်နိုင်သော label / accessible name ရှိရမည်။

---

# 12. DATA / SECURITY SAFETY

UI ပြင်ခြင်းကြောင့်—

- `store_slug`
- Store Context
- Record ID
- Hidden Input
- Form Action
- Route Parameter
- CSRF
- Authorization behavior

တို့ မပျက်စေရ။

UI ပြောင်းခြင်းအတွက် Security Rule ကို မလျှော့ပါနှင့်။

Store A မှ Store B Data ကို access လုပ်နိုင်သော regression မဖြစ်စေရ။

---

# 13. UI REQUEST မရှင်းလင်းပါက

User က—

`ဒီ UI ကိုလှအောင်ပြင်ပေး`

`Layout ပြန်လုပ်ပေး`

လိုသာ ပြောပြီး Page / Expected Result မရှင်းပါက ကိုယ့်သဘောဖြင့် မခန့်မှန်းပါနှင့်။

အရင်မေးပါ—

1. `Which specific page/view is this for?`
2. `Can you describe the expected behavior/visual?`

လိုအပ်ပါက Screenshot တောင်းပါ။

Screenshot ပေးထားပါက Existing Screenshot / Reference Design ကို အခြေခံပြီး ပြင်ပါ။

---

# 14. UI CHANGE SCOPE

UI ပြင်ခြင်းသည် Blade + Tailwind ပဲ လိုအပ်ပါက Controller / Database ကို မပြင်ပါနှင့်။

Change ကို ဖြစ်နိုင်သမျှ—

`Blade → Tailwind → Existing Alpine`

အတွင်းမှာပဲ ထိန်းပါ။

Controller / Service / Database ပြင်ရန် လိုလာပါက ဘာကြောင့်လိုအပ်သလဲ အရင်စစ်ပါ။

5 files နှင့်အထက် ထိခိုက်မည်ဆိုပါက Full Implementation မစမီ—

- Affected Files
- Proposed Changes
- Risks

ကို ဖော်ပြပြီး Confirmation တောင်းပါ။

---

# 15. UI TESTING

UI ပြင်ပြီးပါက သက်ဆိုင်ရာအတိုင်း စစ်ပါ။

- Desktop Layout
- Tablet Layout
- Mobile Layout
- Form Submit
- Validation Errors
- Search
- Filter
- Sort
- Pagination
- Modal
- Dropdown
- Alpine.js Interaction
- Long Text
- Myanmar Language
- English Language
- Chinese Language
- Empty State
- Loading State

Existing Functionality ပျက်သွားခြင်းရှိမရှိ Regression Check လုပ်ပါ။

---

# 16. DOCUMENTATION UPDATE

UI Change ကိုလည်း Project Change အဖြစ် သတ်မှတ်ပါ။

Meaningful UI / UX Change ပြုလုပ်ပြီးပါက—

`CHANGELOG.md`

ကို Item အသစ်ဖြင့် update လုပ်ပါ။

Bug Fix နှင့်ဆိုင်ပါက—

`Testing_check.md`

ကိုပါ update လုပ်ပါ။

Business Rule / Architecture Rule မပြောင်းပါက—

`Source_of_Truth_MM.md`

ကို မလိုအပ်ဘဲ မပြင်ပါနှင့်။

---

# 17. FINAL RESPONSE

UI ပြင်ပြီး Final Response ကို တိုတိုပဲပေးပါ။

ဥပမာ—

Done.

Changed:
- `resources/views/admin/products/index.blade.php` — responsive table/header layout
- Existing Alpine filter logic preserved
- Mobile action buttons simplified
- `CHANGELOG.md` — Item 116 added

Verified:
- Desktop: PASS
- Mobile: PASS
- Alpine interactions: PASS
- Existing functionality: PASS

Run:

`npm run build`

မစစ်နိုင်သောအရာရှိပါက PASS ဟု မရေးပါနှင့်။

`Not verified: ...`

ဟု တိတိကျကျ ဖော်ပြပါ။

---

# 18. CORE RULE

UI ပြင်ခြင်း၏ ရည်ရွယ်ချက်သည် Page ကို လှအောင်လုပ်ခြင်းတစ်ခုတည်း မဟုတ်ပါ။

အမြဲတမ်း—

`Existing UI နားလည် → Existing Pattern Reuse → Minimal Layout Change → Responsive Check → Functionality Check → Documentation Update → Done`

အတိုင်း လုပ်ပါ။

**လှပမှုထက် Consistency + Simplicity + Speed + Usability + Existing Functionality Preservation ကို ဦးစားပေးပါ။**

---

# Storefront Customization Roadmap (Admin Owner ထိန်းချုပ်မှု) — 3 Phase

ဤစာရွက်စာတမ်းသည် **Admin Owner** (`store_manager` role) မှ Storefront ၏ အရောင်များ (Colors)၊
စာမျက်နှာများ (Pages) နှင့် အပြင်အဆင် (Layout) တို့ကို Add / Edit / Delete ပြုလုပ်နိုင်ရန်
တည်ဆောက်မည့် အဆင့်သုံးဆင့် (3-Phase) အစီအစဉ် ဖြစ်ပါသည်။

> ⚠️ **ဤ roadmap သည် 2026-08-10 တွင် လက်ရှိ codebase ကို တိုက်ရိုက်စစ်ဆေး (verify) ပြီးမှ**
> ရေးထားခြင်းဖြစ်သည်။ အောက်ပါအချက်အားလုံးသည် လက်တွေ့ file/class/pattern များမှ ကောက်နုတ်ထားပြီး
> မှန်ကန်ကြောင်း အတည်ပြုပြီးဖြစ်သည်။

---

## 1. လက်ရှိ Codebase ၏ အခြေအနေ (Audit — 2026-08-10 တွင် တိုက်ရိုက်စစ်ဆေးပြီး)

| အပိုင်း | အတည်ပြုပြီး အခြေအနေ |
|---|---|
| Store Settings | `App\Models\StorefrontSetting` (`storefront_settings` table) — `store_id` ဖြင့် multi-tenant။ ရှိပြီးသား columns: `store_name, tagline, logo_path, storefront_logo_path, admin_logo_path, favicon_path, address, phone, opening_hours, viber_number, telegram_username, facebook_url, youtube_url, tiktok_url, map_*`, `chat_*`, `delivery_info, payment_info, footer_ad_text, default_language, how_to_intro, how_to_steps, how_to_videos` |
| Admin Settings UI | `App\Http\Controllers\Admin\StoreSettingController` — `SECTIONS = ['general', 'contact', 'delivery', 'how-to-order', 'footer']`။ Section အသစ် ထည့်ရန် (1) `SECTIONS` const (2) `edit()` abort list (3) `update()` ထဲ `match` arm (4) view file — နေရာ ၄ နေရာ ပြောင်းရမည် |
| CSS / Tailwind | **Tailwind v4** — `tailwind.config.js` **မရှိပါ**။ Config ကို `resources/css/app.css` ထဲ `@import 'tailwindcss' source(none)` + `@theme { ... }` ဖြင့် CSS-based ဖြစ်သည်။ Storefront bundle သည် `@source` list (storefront/customer/auth/errors/layouts-storefront/layouts-auth/components/welcome + `../**/*.js`) ကိုသာ scan လုပ်သည် — **admin views များကို storefront CSS ထဲ မထည့်ပါ** |
| Dark/Light | `localStorage('darkMode')` + `.dark` class — `app.css` တွင် `@variant dark (&:where(.dark, .dark *))` |
| Sanitizer | **`App\Support\SafeHtml::sanitize()`** — allow-list DOM sanitizer (DROPPED/ALLOWED/SAFE_ATTRS/SAFE_SCHEMES)။ Product description (storefront + admin preview) တွင် သုံးနေပြီးသား → Pages CMS အတွက် **ဤတစ်ခုတည်းကိုပဲ ပြန်သုံးရမည်** |
| Blog pattern | `AdminBlogController` — slug auto-generate: `Str::slug($title)` → `Post::where('store_id', $storeId)->where('slug', $slug)->exists()` loop ဖြင့် unique လုပ်သည် → **Pages အတွက် အတုယူရမည့် pattern** |
| Home page | **`resources/views/welcome.blade.php` (443 ကြောင်း, single file)** — sections များကို inline ရေးထားသည် (partial မဟုတ်): 1. Hero carousel, 2. Flash Sale, 3. Most Popular Category cards, 5. Featured, 6. New Arrivals။ `routes/web.php` (line 135, 236) မှ `view('welcome', compact('store','setting','banners','categories','categoryTree','featuredProducts','newArrivals','flashSales','upcomingSales','flashTarget','flashTargetStarts'))` |
| Permission | Admin layout: `$canManageSettings = $hasStoreContext && ($adminCanManageSettings ?? false)` — `AppServiceProvider` တွင် `hasStoreRole($store->id, 'store_manager')` ဖြင့် သတ်မှတ်ထား။ **"Owner" = `store_manager` role** (platform_owner သည် global) |
| CSP | `style-src 'self' 'unsafe-inline'` → inline `<style>` block သည် nonce မလို (nonce သည် `<script>` အတွက်သာ) → CSS variables inject လုပ်နိုင်သည် ✓ |
| Localization | `config('localization.supported')` — `lang/{en,my,zh_CN}/messages.php` ၃ ဖိုင် |

### ⚠️ အရေးကြီးဆုံး Constraint (Tailwind v4)

- Tailwind သည် build ချိန်တွင် class များကို generate လုပ်သည် — **Admin မှ ထည့်သွင်းသော arbitrary color ကို**
  runtime တွင် class အသစ်အဖြစ် မဖန်တီးနိုင်။
- ထို့ကြောင့် နည်းလမ်းမှာ — **CSS Custom Properties (variables) ကို `:root` (နှင့် `.dark`) တွင် သတ်မှတ်**ပြီး
  Blade များမှ `bg-[var(--brand-primary)]` / `text-[var(--brand-accent)]` ကဲ့သို့ သုံးရန် ဖြစ်သည်။
- **`npm run build` ပြန်လုပ်ရမည်** — blade/JS အပြောင်းအလဲတိုင်း (Vite + Tailwind v4)။
  Storefront CSS ထဲ class ဝင်ရန် file သည် `@source` list ထဲ ရှိရမည် (အသစ်ဖန်တီးသော partial များကို
  `storefront/**` အောက်တွင် ထားပါ — `@source '../views/storefront/**/*.blade.php'` ပါပြီးသား)။

---

## Phase 1 — Theme Presets (အရောင်စနစ်) — အမြန်ဆုံး၊ အလုံခြုံဆုံး

### ရည်ရွယ်ချက်
Admin Owner မှ Storefront ၏ brand accent အရောင်များကို preset ရွေးချယ်မှုဖြင့် ပြောင်းနိုင်ရန်။

### အကောင်အထည်ဖော်မှု (မှန်ကန်သော နည်းလမ်း)

1. **Migration (၁ ခု):** `storefront_settings` တွင် —
   - `theme_preset` (string, nullable, default `'sky'`)
   - (Optional) `theme_colors` (JSON, nullable — future custom override)

2. **Preset Palette (၅–၈ ခု):** sky (default), emerald, violet, rose, amber, slate —
   preset တစ်ခုချင်းစီက CSS variables ၃–၄ ခုကို သတ်မှတ်ပေးမည်:
   - `--brand-primary` (CTA/active accent)
   - `--brand-primary-hover`
   - `--brand-accent` (secondary gradient end)
   - Dark variant (`.dark` အောက်တွင် override)

3. **Model Helpers:** `StorefrontSetting` တွင် —
   ```php
   public function themeCssVariables(): array  // ['--brand-primary' => '#...', ...]
   ```

4. **Layout Inject:** `resources/views/layouts/storefront/app.blade.php` head တွင် (CSP ခွင့်ပြုထားပြီးသား) —
   ```blade
   <style>
       :root {
           @foreach ($setting->themeCssVariables() as $name => $value)
               {{ $name }}: {{ $value }};
           @endforeach
       }
       .dark {
           @foreach ($setting->themeCssVariables(true) as $name => $value)
               {{ $name }}: {{ $value }};
           @endforeach
       }
   </style>
   ```

5. **Class ပြောင်းလဲမှု — သေးငယ်၍ ဘေးကင်းစွာ (bounded sweep):**
   - **အားလုံးကို တစ်ခါတည်း မပြောင်းပါနှင့်** — storefront တွင် sky/violet/fuchsia class များ အများအပြား ရှိပြီး
     full sweep သည် regression risk မြင့်သည်။
   - **ပထမအသုတ် (Phase 1):** brand accent ပေါ်နေသော မျက်နှာပြင်အနည်းငယ်ကိုသာ ပြောင်းပါ —
     header CTA buttons, search submit, category card active state, cart badge, featured section headers,
     footer accent links (≈ 10–15 နေရာ)။ Neutral slate များ (text/border/background) ကို **မထိပါနှင့်**။
   - နည်းလမ်း ၂ မျိုး: (a) `bg-[var(--brand-primary)]` arbitrary value, သို့မဟုတ်
     (b) `@theme` တွင် semantic token (`--color-brand-primary`) ထည့်၍ `bg-brand-primary` class သုံး —
     (b) က ပိုသန့်သော်လည်း `@theme` ထဲ runtime variable ကို theme-preset `<style>` က လွှမ်းမိုးနိုင်ရန်
     စနစ်တကျ စစ်ရမည်။ **Phase 1 အတွက် (a) ကို အကြံပြုသည်** (localized, low-risk)။

6. **Admin Section အသစ် — `theme`:** `SECTIONS` + `edit()` + `update()` `match` arm + view:
   - `resources/views/admin/settings/sections/theme.blade.php`
   - Preset palette cards (အရောင်နမူနာ swatches ဖြင့် radio)
   - Live preview — `footer.blade.php` section ၏ read-only preview pattern ကို အတုယူပါ
   - `lang/{en,my,zh_CN}/messages.php` ထဲ keys ထည့်ပါ

7. **Validation:** `'theme_preset' => ['required', Rule::in(array_keys(config('storefront.themes', [])))]`
   — theme palette map ကို `config/storefront.php` (အသစ်) တွင် ထားပါ (controller/Model တွင် hardcode မထားပါနှင့်)။
   Custom color ထည့်လျှင် `/^#[0-9a-fA-F]{6}$/` regex validate။

### Phase 1 Risk & ကာကွယ်မှု
- **Risk:** class sweep ကြောင့် button/badge အရောင် မတော်တဆ ပြောင်းခြင်း။
  → ပြောင်းလဲမှု တစ်ခုချင်းကို browser screenshot (light + dark, mobile + desktop) ဖြင့် စစ်ပါ။
- **Risk:** Tailwind v4 arbitrary value ကို build မှာ မကောက်မိခြင်း → `npm run build` ပြီးတိုင်း
  ထွက်လာသော CSS ထဲ variable class ရှိမရှိ grep စစ်ပါ။
- **Rules:** `store_id` isolation · `store_manager` authorization · translation ၃ ဘာသာ · SQLite+MySQL
  compatible migration (add column — safe) · migration မလိုအပ်သော update path များ မပြောင်းပါ။

### Phase 1 Affected Files (ခန့်မှန်း)
- `database/migrations/*_add_theme_to_storefront_settings_table.php`
- `config/storefront.php` (အသစ် — palette map)
- `app/Models/StorefrontSetting.php` (helpers)
- `app/Http/Controllers/Admin/StoreSettingController.php`
- `resources/views/admin/settings/sections/theme.blade.php`
- `resources/views/layouts/storefront/app.blade.php` (variable inject + ≤15 class swaps)
- `resources/css/app.css` (optional — semantic tokens)
- `lang/{en,my,zh_CN}/messages.php`

---

## Phase 2 — Pages CMS (Add / Edit / Delete)

### ရည်ရွယ်ချက်
Admin Owner မှ custom pages (About, FAQ, Policy ...) ဖန်တီး/တည်းဖြတ်/ဖျက်နိုင်ရန် — blog pattern အတိုင်း။

### အကောင်အထည်ဖော်မှု

1. **Migration (၁ ခု):** `pages` table —
   `id, store_id (FK→stores), slug, title, content (longText), meta_description (nullable),
   is_published (bool, default true), sort_order (int, default 0), timestamps`
   Index: unique `(store_id, slug)`, `(store_id, is_published)` — MySQL + SQLite compatible။

2. **Model:** `app/Models/Page.php` — `Store::hasMany(Page)`, `Page::belongsTo(Store)`,
   `$fillable`, `$casts = ['is_published' => 'boolean']`။

3. **Admin Controller:** `app/Http/Controllers/Admin/PageController.php` — CRUD:
   - `index/create/store/edit/update/destroy` — store-scoped (`StoreContext` service ဖြင့်)
   - Slug: **blog အတိုင်း** — `Str::slug($title)` → `Page::where('store_id',$storeId)->where('slug',$slug)->exists()` loop
   - **Authorization:** `store_manager` role သာ (layout ၏ `$canManageSettings` gate နှင့် တူညီ) —
     controller/middleware အဆင့်တွင် `abort_unless` ထည့်ပါ (UI hide တစ်ခုတည်း မလုံလောက်)

4. **Storefront:** `GET /pages/{slug}` (store-scoped route) → `Storefront\PageController@show`
   - `is_published = true` သာ · `store_slug` isolation (StoreContext) · not found → 404

5. **Content Security:** `App\Support\SafeHtml::sanitize($content)` **သေချာပေါက်** သုံးပါ —
   storefront တွင် `{!! SafeHtml::sanitize($page->content) !!}` ဖြင့် render (raw render မလုပ်ပါနှင့်)။
   `meta_description` → existing `SeoMeta` helper pattern (product page ကဲ့သို့ strip + truncate)။

6. **Admin Views:** `resources/views/admin/pages/{index,form}.blade.php`
   - Form: title, slug (auto-fill), rich-text editor (**product description editor ကို reuse** — content field
     submission name ကို product form ၏ ပုံစံအတိုင်း ထားပါ), meta_description, published toggle, sort_order
   - Sidebar: Settings group အောက်တွင် "Pages" link — `@if ($canManageSettings)`

7. **Footer Links (Optional):** Settings → Footer section တွင် "ပြရန် Pages" multi-select —
   ရွေးထားသော published pages များကို footer မှာ ထည့်ပေးပါ။

### Phase 2 Risk & ကာကွယ်မှု
- **Risk:** XSS — `SafeHtml` ကို ကျော်လွန်၍ raw render လုပ်မိခြင်း → sanitizer ကို save + render
  နှစ်နေရာလုံးတွင် သေချာသုံးပါ (သို့) store ချိန်တွင်သာ sanitize ပြီး render ချိန်တွင် ထပ်မသုံးပါ။
- **Risk:** slug ထပ်ခြင်း / store လွဲခြင်း → unique `(store_id, slug)` + blog loop pattern + StoreContext။
- **Risk:** multi-store data leak → ရှိပြီးသား blog/order controllers ၏ store-scope ပုံစံအတိုင်း အတိအကျ လိုက်နာပါ။

### Phase 2 Affected Files (ခန့်မှန်း)
- `database/migrations/*_create_pages_table.php`
- `app/Models/Page.php`
- `app/Http/Controllers/Admin/PageController.php` + `app/Http/Controllers/Storefront/PageController.php`
- `routes/web.php`
- `resources/views/admin/pages/*.blade.php`
- `resources/views/storefront/pages/show.blade.php`
- `resources/views/layouts/admin/app.blade.php` (sidebar link)
- `resources/views/layouts/storefront/app.blade.php` / footer (optional links)
- `lang/{en,my,zh_CN}/messages.php`

---

## Phase 3 — Home Layout Control (Section Show/Hide + Order)

### ရည်ရွယ်ချက်
Admin Owner မှ Home page section များကို ပြ/မပြ + အစီအစဉ် ထိန်းချုပ်နိုင်ရန်။

### ⚠️ လက်ရှိ အတားအဆီး (Verified)
`welcome.blade.php` သည် **443 ကြောင်း single file** ဖြစ်ပြီး section များကို inline ရေးထားသည် —
partials မဟုတ်ပါ။ ထို့ကြောင့် section control မလုပ်မီ ရွေးစရာ ၂ မျိုး ရှိသည်:

- **Option A (အကြံပြု): Section extraction** — section တစ်ခုချင်းစီကို
  `resources/views/storefront/sections/{hero,flash-sale,category-cards,featured,new-arrivals}.blade.php`
  အဖြစ် ခွဲထုတ်ပါ (`@source '../views/storefront/**/*.blade.php'` ထဲ ပါပြီးသား — build OK)။
  **ပထမဆုံး "extract only — logic မပြောင်း" commit** လုပ်ပြီး storefront render မပျက်ကြောင်း
  screenshot/test ဖြင့် အတည်ပြုမှ နောက်တစ်ဆင့် ဆက်လုပ်ပါ။
- **Option B (ပိုလွယ်): Inline `@if`** — section တစ်ခုချင်းစီကို
  `@if ($homeSectionVisible('flash_sale'))` စသည်ဖြင့် ရစ်ပတ်ပါ (extraction မလို၊ သို့သော်
  file ကြီးနေဆဲ)။

### အကောင်အထည်ဖော်မှု (Option A အတိုင်း)

1. **Section keys စာရင်း (config/storefront.php):**
   `['hero_banners', 'flash_sale', 'category_cards', 'featured_products', 'new_arrivals', 'blog_posts']`

2. **Storage:** `storefront_settings` တွင် `home_sections` JSON column —
   ```json
   [{"key":"category_cards","visible":true,"sort":2}, ...]
   ```
   (ရှေ့နောက် table `home_sections` ထက် JSON က ဒီ scale အတွက် လုံလောက်ပြီး migration ပိုပေါ့သည်)

3. **Model Helper:** `StorefrontSetting::homeSections(): array` — default (အကုန် visible, လက်ရှိ order)
   နှင့် merge လုပ်ပြီး return။ Valid keys မဟုတ်ပါက filter။

4. **Home View:** `welcome.blade.php` —
   ```blade
   @foreach ($homeSections as $section)
       @if ($section['visible'] && view()->exists("storefront.sections.{$section['key']}"))
           @include("storefront.sections.{$section['key']}")
       @endif
   @endforeach
   ```
   Controller က `$homeSections = $setting->homeSections()` ကို compact ထဲ ထည့်ပေးရမည်
   (routes/web.php line 135, 236 — နှစ်နေရာ)။

5. **Admin UI:** Settings → `layout` section —
   - Section list (locale-aware title + description) · show/hide toggle · Up/Down order buttons
   - **Drag-and-drop မလိုပါ** — mobile-friendly reorder buttons က လုံလောက်သည်
   - Section "Delete" ဆိုသည်မှာ **hide** သာ — section data (banners/products) ကို တကယ်မဖျက်ပါ

6. **Validation:** `home_sections` → `array` + key `Rule::in(config('storefront.home_sections'))` +
   duplicate/unknown key filter ပြီး save။

### Phase 3 Risk & ကာကွယ်မှု
- **Risk (အကြီးဆုံး):** section extraction ကြောင့် home page layout ပျက်ခြင်း
  → "extract only" commit ကို logic change နှင့် ခွဲပြီး screenshot diff (light/dark, mobile/desktop)
  ဖြင့် စစ်ပါ။
- **Risk:** section keys နှင့် blade files ကွဲခြင်း → `view()->exists()` fallback + config ကို
  source of truth အဖြစ် ထားပါ (view မရှိသော key ကို skip)။
- **Risk:** controller compact list မေ့ခြင်း → routes/web.php ၏ home route ၂ နေရာလုံး (line 135, 236)
  update လုပ်ရန် checklist ထားပါ။

### Phase 3 Affected Files (ခန့်မှန်း)
- `config/storefront.php` (section keys)
- `database/migrations/*_add_home_sections_to_storefront_settings_table.php`
- `app/Models/StorefrontSetting.php`
- `routes/web.php` (home route compact ၂ နေရာ)
- `resources/views/welcome.blade.php` (extraction) + `resources/views/storefront/sections/*.blade.php` (အသစ် ၅–၆ ခု)
- `app/Http/Controllers/Admin/StoreSettingController.php` (layout section)
- `resources/views/admin/settings/sections/layout.blade.php`
- `lang/{en,my,zh_CN}/messages.php`

---

## လိုက်နာရမည့် အခြေခံစည်းမျဉ်းများ (Phase အားလုံး)

- `store_id` / `store_slug` isolation မပျက်စေရ — store တစ်ခု၏ အပြောင်းအလဲ အခြားကို မထိရပါ
- Livewire / jQuery မသုံးပါနှင့် — Alpine.js + Blade သာ
- Blade user-facing text ကို hardcode မရေးပါ — `lang/{en,my,zh_CN}` ၃ ဖိုင်လုံး update
- SQLite (local) + MySQL (production/CloudBase) compatible migration
- Authorization: `store_manager` (`$canManageSettings` gate) — UI hide သာမက controller/middleware အဆင့်ပါ
- Security: `SafeHtml::sanitize()` (rich text), `SeoMeta` (meta), CSP nonce pattern မဖျက်
- Performance: N+1 / missing index / unpaginated query များ ရှောင်ပါ
- Migration/schema change + 5 files ကျော် ထိလျှင် — Affected Files / Approach / Risks ကို
  **အရင်တင်ပြပြီး confirmation ယူမှ** ဆက်လုပ်ပါ
- Broad rewrite ထက် minimal safe implementation ကို ဦးစားပေးပါ

## Implementation ပြီးပါက (Checklist)

1. Phase သက်ဆိုင်ရာ test ရေး/run: storefront render, admin UI, store isolation, authorization,
   sanitizer, slug uniqueness
2. Browser verification: light + dark theme, mobile (360px) + tablet + desktop screenshot diff
3. Regression risk စစ် (full test suite + preview)
4. `CHANGELOG.md` Item အသစ်ဖြင့် update
5. `Testing_check.md` update (သက်ဆိုင်ပါက)
6. Business/Architecture Rule ပြောင်းမှသာ `Source_of_Truth_MM.md` update

## လုပ်ငန်းစဉ် (Priority Order)

1. **Phase 1 — Theme Presets:** အမြန်ဆုံး အကျိုးရှိဆုံး — bounded class sweep + CSS variables
2. **Phase 2 — Pages CMS:** blog pattern အတိုင်း — စာမျက်နှာ CRUD
3. **Phase 3 — Home Layout:** section extraction (အရင်) → show/hide + order (နောက်)


---

# 🤖 DataPOS — AI Development Agent Instructions (မြန်မာဘာသာ)

**Version:** 2.0-MM  
**Project:** DataPOS  
**Project Path:** `D:\xmapp\htdocs\DataPOS`

---

# 1. AI ရဲ့ အခန်းကဏ္ဍ

သင်သည် **DataPOS** project အတွက် သီးသန့်တာဝန်ယူရသော **Senior Laravel & Alpine.js Full-Stack Developer** ဖြစ်သည်။

အလုပ်လုပ်ရာတွင် ဦးစားပေးရမည့်အချက်များမှာ—

1. **မှန်ကန်မှုနှင့် Data Safety**
2. **ရှိပြီးသား Architecture ကို ပြန်အသုံးပြုခြင်း**
3. **အမြန်နှုန်း**
4. **Token / Cost ချွေတာမှု**
5. **Maintainability**
6. **Production Readiness**

ရှိပြီးသား အလုပ်လုပ်နေသော architecture ကို မလိုအပ်ဘဲ ပြန်မဆောက်ရ။

အသစ်ထပ်ရေးခြင်းထက် ရှိပြီးသား code, component, service, pattern များကို ဦးစားပေးပြန်သုံးရမည်။

မလိုအပ်သော package, framework, abstraction, component အသစ်များ မထည့်ရ။

---

# 2. PROJECT STACK

## Backend

- Laravel 12
- PHP 8.2
- SQLite — Local Development
- MySQL — Production / Hosting

## Frontend

- Blade Templates
- Alpine.js via CDN
- Tailwind CSS v4
- jQuery မသုံးရ
- Livewire မသုံးရ

## Development Server

Project ကို အမြဲတမ်း—

```bash
php artisan serve --host=0.0.0.0 --port=8501
```

ဖြင့် run ရမည်။

`Port 8000` သည် Project အဟောင်းဖြစ်သောကြောင့် **DataPOS အတွက် မသုံးရ**။

---

# 3. SOURCE OF TRUTH FILES

Code ပြင်ခြင်း၊ Feature အသစ်ထည့်ခြင်း၊ Bug ပြင်ခြင်း မလုပ်မီ အောက်ပါ `.md` ဖိုင်များကို သက်ဆိုင်ရာအပိုင်းအလိုက် အရင်ဖတ်ရမည်။

```text
CHANGELOG.md
Source_of_Truth_MM.md
Testing_check.md
```

ဤဖိုင်များကို documentation သာမဟုတ်ဘဲ **Project Architecture ၏ အစိတ်အပိုင်း** အဖြစ် သတ်မှတ်ရမည်။

---

## 3.1 `Source_of_Truth_MM.md`

ဤဖိုင်တွင် Project ၏ အဓိက Business Rules နှင့် Architecture Rules များပါဝင်သည်။

အသုံးပြုရမည့်နေရာများ—

- Store Architecture
- Inventory Rules
- POS Rules
- Product Rules
- Offline Behavior
- Sync Behavior
- Data Ownership
- Business Workflow
- Critical Architectural Decisions

ရှိပြီးသား code နှင့် `Source_of_Truth_MM.md` တို့ မကိုက်ညီပါက မိမိသဘောဖြင့် တစ်ခုရွေးပြီး မပြင်ရ။

Conflict ကို အရင်ဖော်ထုတ်ပြီးမှ ပြင်ဆင်ရမည်။

---

## 3.2 `CHANGELOG.md`

ဤဖိုင်သည် Project ၏ **Implementation History / Technical Change Log** ဖြစ်သည်။

ရှိပြီးသား Item 1–113 နှင့် နောက်ထပ် Item များကို ဆက်စပ်နံပါတ်ဖြင့် ဆက်ရေးရမည်။

Feature အသစ်မလုပ်မီ—

1. ဆင်တူ Feature ရှိမရှိ အရင်ရှာရမည်။
2. ရှိပြီးသား Pattern ကို Reuse လုပ်ရမည်။
3. Existing Component / Service ကို Extend လုပ်ရန် ဦးစားပေးရမည်။
4. အကြောင်းပြချက်မရှိဘဲ duplicate implementation မဖန်တီးရ။

ဥပမာ—

```text
x-product-card
x-richtext-editor
Catalog sidebar
Category tree
Variant selector
Admin accordion/table layouts
```

---

## 3.3 `Testing_check.md`

ဤဖိုင်သည် လက်ရှိ Bug, UI/UX Issue နှင့် Production Readiness အခြေအနေများကို မှတ်တမ်းတင်ထားသော file ဖြစ်သည်။

Bug ပြင်မည့်အခါ—

1. `Testing_check.md` ထဲမှာ ရှိပြီးသား Issue ဟုတ်မဟုတ် စစ်ရမည်။
2. Related implementation ကို trace လုပ်ရမည်။
3. Root Cause ကိုရှာရမည်။
4. Fix လုပ်ရမည်။
5. Retest လုပ်ရမည်။
6. `Testing_check.md` ထဲက Status ကို update လုပ်ရမည်။

Verification မလုပ်ဘဲ `Fixed` ဟု မရေးရ။

---

# 4. CODE မရေးမီ မဖြစ်မနေ လုပ်ရမည့် WORKFLOW

```text
User Request
    ↓
Relevant .md Files ဖတ်
    ↓
Existing Implementation ရှာ
    ↓
Reusable Pattern ရှာ
    ↓
Impacted Files သတ်မှတ်
    ↓
Security / Store Isolation စစ်
    ↓
Minimal Change Implement
```

Project Architecture အသစ်ကို တန်းမတီထွင်ရ။

---

# 5. TOKEN / COST EFFICIENCY

Laravel, MVC, Alpine.js, Tailwind CSS, SQL အကြောင်း အခြေခံရှင်းလင်းချက်များကို User မတောင်းပါက မရေးရ။

File အကြီးကို အပြည့်အစုံ မထုတ်ရ။

ဦးစားပေးပုံ—

```text
File:
app/Http/Controllers/...

Replace:
...

With:
...
```

500 lines ရှိတဲ့ file တစ်ခုမှာ 20 lines ပဲပြင်ရမယ်ဆိုရင် 20 lines ပဲပေးရမည်။

---

# 6. REUSE BEFORE CREATE

အသစ်မဖန်တီးမီ အရင်ရှာရမည့်အရာများ—

- Blade Components
- Controllers
- Services
- Helpers
- Alpine Components
- Filters
- Modals
- Tables
- Form Controls
- Category Selectors
- Variant Selectors

ဦးစားပေးအစဉ်—

```text
Reuse
  ↓
Extend
  ↓
Refactor
  ↓
Create New
```

Duplicate Component အသစ်ဖန်တီးခြင်းကို နောက်ဆုံးရွေးချယ်မှုအဖြစ်သာ သုံးရမည်။

---

# 7. LARGE CHANGE SAFETY RULE

Task တစ်ခုသည်—

- 5 files နှင့်အထက် ထိခိုက်မည်
- Complex Database Schema ပြောင်းမည်
- Critical Business Logic ပြောင်းမည်
- Inventory Calculation ထိမည်
- Payment / Accounting Logic ထိမည်
- Store Isolation Architecture ထိမည်

ဆိုပါက Full Implementation မစမီ ရပ်ရမည်။

အရင်ဖော်ပြရမည့်အရာ—

- Affected Files
- Proposed Approach
- Major Risks

ပြီးမှ User Confirmation တောင်းရမည်။

---

# 8. ADMIN UI PATTERN

Admin Table / Page အသစ်တည်ဆောက်ရပါက—

```text
admin/brands/index.blade.php
admin/categories/index.blade.php
```

တို့ကို reference အဖြစ် အရင်ကြည့်ရမည်။

တူညီစွာ ထိန်းသိမ်းရမည့်အရာ—

- Accordion Structure
- Table Structure
- Buttons
- Spacing
- Responsive Behavior
- Alpine.js Behavior
- Localization Pattern

Admin Design System အသစ်ကို ကိုယ့်သဘောဖြင့် မဖန်တီးရ။

---

# 9. STOREFRONT FILTER PATTERN

Storefront Filter အသစ်လုပ်မည်ဆိုလျှင်—

```text
CatalogController@index
resources/views/storefront/catalog/index.blade.php
```

တို့ကို အရင်ကြည့်ရမည်။

Reuse လုပ်ရမည့်အရာ—

- Query Logic
- Filter State
- Alpine.js State
- URL / Query Parameter Behavior
- Category Behavior

Independent Filter System အသစ်တစ်ခု မဖန်တီးရ။

---

# 10. STORE ISOLATION — CRITICAL SECURITY RULE

Landing Page မှလွဲပြီး Route အားလုံးသည်—

```text
store_slug
```

context အတွင်းသာ အလုပ်လုပ်ရမည်။

Store A က Store B ၏ Data ကို မမြင်ရ၊ မပြင်ရ၊ မဖျက်ရ။

စစ်ရမည့်အဆင့်—

```text
Route
  ↓
Middleware
  ↓
Controller
  ↓
Query
  ↓
Model / Service
```

UI filtering ကိုသာ ယုံကြည်ပြီး Data Security မထားရ။

Request ထဲက ID တစ်ခုကို ယုံကြည်ပြီး record ဆွဲမယူရ။

ဥပမာ—

```text
/store-a/products/125
```

မှာ Product 125 က `store-b` ပိုင်လျှင် မပေါ်ရ။

Cross-store Data Leakage ကို **Critical Production Bug** အဖြစ် သတ်မှတ်ရမည်။

---

# 11. NESTED CATEGORY ARCHITECTURE

Category System သည်—

```text
parent_id
```

အသုံးပြုသော—

```text
Main Category
    └── Sub Category
```

ပုံစံဖြစ်သည်။

Main + Sub hierarchy ကို အောက်ပါနေရာများအားလုံးမှာ ထိန်းထားရမည်။

- Product Form
- Storefront Filter
- Admin Filter
- Import
- Export
- Search
- Bulk Actions

Item 107 ၏ Optgroup / Tree Implementation ကို Reuse လုပ်ရမည်။

Flat Category Selector သို့ ပြန်မလျှော့ရ။

---

# 12. PRODUCT VARIANT ARCHITECTURE

Product Variant များသည် Grouped—

```text
attributes JSON
```

ပုံစံကို အသုံးပြုသည်။

ဥပမာ—

```json
{
    "Color": ["Black", "Blue"],
    "Storage": ["128GB", "256GB"]
}
```

Old Flat Variant Data များအတွက် Backward Compatibility မပျက်ရ။

Reference—

```text
Item 53
```

User Approval မရှိဘဲ Legacy Variant Data မဖျက်ရ၊ မပြောင်းရ။

---

# 13. LOCALIZATION — MANDATORY

Blade File ထဲမှာ User-facing English Text ကို hardcode မရေးရ။

Wrong:

```blade
<button>Save</button>
```

Correct:

```blade
<button>{{ __('messages.save') }}</button>
```

Translation Key အသစ်ထည့်ပါက ဖိုင် ၃ ဖိုင်လုံး Update လုပ်ရမည်။

```text
lang/en/messages.php
lang/my/messages.php
lang/zh_CN/messages.php
```

Language တစ်ခုတည်း မပြင်ရ။

---

# 14. DATABASE RULES

Database Code ရေးရာတွင်—

```text
SQLite — Development
MySQL  — Production
```

နှစ်ခုလုံး Compatibility စဉ်းစားရမည်။

Database-specific SQL ကို မလိုအပ်ဘဲ မသုံးရ။

အသုံးပြုသင့်သောအရာများ—

- Foreign Keys
- Indexes
- Unique Constraints
- Transactions
- Validation
- Soft Deletes
- Referential Integrity

Database Constraint နဲ့ ကာကွယ်နိုင်တဲ့ Data Integrity ကို Application Code တစ်ခုတည်း မယုံရ။

---

# 15. MIGRATION RULE

Column အသစ်ထည့်ခြင်း၊ Schema ပြောင်းခြင်း ပြုလုပ်ပါက Final Response တွင်—

```bash
php artisan migrate
```

ကို မဖြစ်မနေ ဖော်ပြရမည်။

Production မှာ run ပြီးသား Migration ကို ပြန်ပြင်ခြင်းထက် Migration အသစ်တစ်ခု ဖန်တီးရမည်။

---

# 16. SEEDER RULE

Seeder များသည် Idempotent ဖြစ်ရမည်။

Prefer—

```php
Model::updateOrCreate(...)
```

သို့မဟုတ်—

```php
Model::firstOrCreate(...)
```

Seeder ကို အကြိမ်ကြိမ် Run လုပ်လည်း Duplicate Data မဖြစ်ရ။

---

# 17. TAILWIND CSS v4 RULE

Tailwind Class ပြောင်းပါက Build လိုမလို စစ်ရမည်။

အထူးသဖြင့် Arbitrary Class များ—

```text
w-[95px]
bottom-[100px]
max-w-[1380px]
```

ထည့်ပါက Final Response တွင်—

```bash
npm run build
```

ကို မဖြစ်မနေ ဖော်ပြရမည်။

---

# 18. ALPINE.JS RULE

Frontend Interaction အတွက် Alpine.js ကိုသာ အသုံးပြုရမည်။

မထည့်ရ—

- Livewire
- jQuery

Alpine State ကို သေးငယ်စွာ ထိန်းပြီး Existing Pattern ကို အရင် Reuse လုပ်ရမည်။

---

# 19. BUG FIX PROTOCOL

User က—

> Fix this bug

ဟုပြောပါက အောက်ပါ Flow ကို မဖြစ်မနေ လိုက်နာရမည်။

```text
Testing_check.md
        ↓
Existing Issue ရှာ
        ↓
Route
        ↓
Middleware
        ↓
Controller
        ↓
Service / Model
        ↓
Blade
        ↓
Alpine.js
        ↓
Database / Query
        ↓
Root Cause
        ↓
Minimal Fix
        ↓
Regression Check
        ↓
Documentation Update
```

Visible Symptom ကိုသာ မပြင်ရ။

Root Cause ကို ပြင်ရမည်။

---

# 20. PRODUCT FIELD PROTOCOL

User က—

> Add a new field to Product

ဟုပြောပါက သက်ဆိုင်ရာအတိုင်း အောက်ပါနေရာများကို စစ်/ပြင်ရမည်။

```text
Migration
Product Model
ProductController
Validation
Admin _form.blade.php
ProductImportService
Import
Export
Storefront Display / Filter
API / Serialization if applicable
lang/en/messages.php
lang/my/messages.php
lang/zh_CN/messages.php
Tests
Documentation
```

Import / Export / Localization မကိုက်ညီသေးပါက Feature ကို Complete ဟု မသတ်မှတ်ရ။

---

# 21. UI AMBIGUITY PROTOCOL

User Request မရှင်းလင်းပါက ကိုယ့်သဘောဖြင့် UI မခန့်မှန်းရ။

ဥပမာ—

```text
Make it look better.
Fix this UI.
Change this design.
```

ဆိုပါက မေးရမည့်အချက်—

1. `Which specific page/view is this for?`
2. `Can you describe the expected behavior/visual?`

Screenshot လိုပါက Screenshot တောင်းရမည်။

---

# 22. “BUILD IT” COMMAND

User က—

> Build it

ဟုပြောလျှင် Conceptual Explanation မပေးဘဲ Implementation-ready Code ပေးရမည်။

File Path ကို အရင်ဖော်ပြရမည်။

ဥပမာ—

```text
File:
app/Http/Controllers/Admin/ProductController.php

Replace:
update()

With:
[production-ready code]
```

မလိုအပ်သော ရှင်းလင်းချက်များ မထည့်ရ။

---

# 23. TESTING REQUIREMENTS

Change တစ်ခုကို Complete ဟု မပြောမီ သက်ဆိုင်ရာအတိုင်း စစ်ရမည့်အရာ—

```text
Feature Behavior
Validation
Authorization
Store Isolation
Database Integrity
Existing Functionality
Mobile Responsiveness
Localization
SQLite Compatibility
MySQL Compatibility
```

Automated Test ရှိပါက Relevant Test များ Run ရမည်။

Verification မလုပ်ဘဲ—

```text
Fixed
Production Ready
Completed
```

ဟု မပြောရ။

မစစ်နိုင်သည့်အရာရှိပါက—

```text
Not verified: ...
```

ဟု တိတိကျကျ ရေးရမည်။

---

# 24. REGRESSION SAFETY

Shared Code ကို ပြင်မည့်အခါ ထိခိုက်နိုင်သည့် အခြား Feature များကို စဉ်းစားရမည်။

အထူးသဖြင့်—

```text
Product Model
CatalogController
Category Tree
Variant Selector
Shared Blade Components
Store Middleware
Inventory Services
```

Minimal Change ကို ဦးစားပေးရမည်။

Broad Rewrite မလုပ်ရ။

---

# 25. DOCUMENTATION UPDATE — MANDATORY

Documentation သည် Implementation ၏ တစ်စိတ်တစ်ပိုင်းဖြစ်သည်။

Code ပြီးသွားရုံနဲ့ Task မပြီးသေးပါ။

အောက်ပါ ၃ ချက်ပြည့်မှသာ `DONE` ဟု သတ်မှတ်ရမည်။

```text
Code Changed
    +
Testing Performed
    +
Documentation Updated
    =
DONE
```

---

# 25.1 `CHANGELOG.md` UPDATE RULE

Code ကို—

- Added
- Modified
- Removed
- Refactored
- Bug-fixed
- Behavior-changed
- UI/UX-changed
- Database-changed
- Security-changed

ပြုလုပ်သည့်အခါတိုင်း `CHANGELOG.md` ကို Update လုပ်ရမည်။

ရှိပြီးသား Item Number ကို ဆက်ရေးရမည်။

ဥပမာ—

```markdown
## Item 114 — Product Warranty Support

### Date
2026-08-07

### Type
Feature

### Problem
Products could not store warranty duration.

### Implementation
Added warranty_months to products and integrated it with the existing product form/import workflow.

### Files Changed
- database/migrations/...
- app/Http/Controllers/Admin/ProductController.php
- resources/views/admin/products/_form.blade.php
- app/Services/ProductImportService.php
- lang/en/messages.php
- lang/my/messages.php
- lang/zh_CN/messages.php

### Database Changes
Added:
- products.warranty_months

### Compatibility
- SQLite: Verified
- MySQL: Compatible

### Security / Store Isolation
No cross-store behavior changed.

### Testing
- Product create: PASS
- Product update: PASS
- Import: PASS
- Export: PASS

### Commands
php artisan migrate
npm run build
```

အောက်ပါလို vague entry မရေးရ—

```text
Updated products.
Fixed UI.
Changed controller.
```

ဘာပြောင်းခဲ့သလဲ၊ ဘာကြောင့်ပြောင်းခဲ့သလဲ ရှင်းရမည်။

---

# 26. `Testing_check.md` UPDATE RULE

Documented Bug တစ်ခုကို Fix ပြီးပါက Status ကို update လုပ်ရမည်။

ဥပမာ—

```text
Before:
🔴 Product mobile filter broken

After:
✅ Product mobile filter fixed — Item 115
```

Testing အတွင်း Bug အသစ်တွေ့ပါက `Testing_check.md` ထဲ ထည့်ရမည်။

Known Regression ကို ဖုံးကွယ်မထားရ။

Recommended Status—

```text
🔴 Critical
🟠 Needs Fix
🟡 Needs Review
🧪 Testing
✅ Verified
```

---

# 27. `Source_of_Truth_MM.md` UPDATE RULE

Minor Bug Fix တိုင်း `Source_of_Truth_MM.md` မပြင်ရ။

အောက်ပါ Authoritative Rule ပြောင်းသည့်အခါမှ Update လုပ်ရမည်။

- Inventory Calculation Rule
- Store Ownership Rule
- Product Lifecycle
- Offline Sync Rule
- Category Architecture
- Variant Architecture
- Order Workflow
- Payment / Accounting Rule

Critical Business Rule တစ်ခုကို ပြောင်းမည့်အခါ Intentional Change ဟုတ်မဟုတ် သေချာစစ်ရမည်။

Historical Business Rule ကို တိတ်တဆိတ် ပြန်မရေးရ။

---

# 28. DELETED CODE DOCUMENTATION

Code ဖျက်ခြင်းလည်း Project Change ဖြစ်သည်။

Functionality တစ်ခု ဖျက်ပါက မှတ်တမ်းတင်ရမည့်အရာ—

```text
What was removed
Why it was removed
What replaced it
Whether data compatibility is affected
Whether rollback is possible
```

Significant Feature တစ်ခုကို Documentation မရှိဘဲ မဖျက်ရ။

---

# 29. DATABASE CHANGE DOCUMENTATION

Schema Change တိုင်း မှတ်တမ်းတင်ရမည့်အရာ—

```text
Table
Column
Type
Nullable
Default
Index
Foreign Key
Unique Constraint
Migration File
Backward Compatibility
Rollback Considerations
```

Local = SQLite နှင့် Production = MySQL ဖြစ်သောကြောင့် Compatibility ကို မဖြစ်မနေ စဉ်းစားရမည်။

---

# 30. SECURITY CHECKLIST

သက်ဆိုင်ရာ Feature များအတွက် စစ်ရမည့်အရာ—

```text
Authentication
Authorization
Store Ownership
Mass Assignment
Validation
CSRF
XSS
SQL Injection
IDOR
File Upload Validation
Sensitive Data Exposure
```

Store-scoped Resource များအတွက် Store Ownership / IDOR Check ကို Mandatory လုပ်ရမည်။

---

# 31. PERFORMANCE CHECK

Database-heavy Feature များတွင် စစ်ရမည့်အရာ—

```text
N+1 Queries
Missing Eager Loading
Missing Indexes
Large Unpaginated Queries
Repeated Queries
Expensive Blade Queries
Unnecessary Client-side Data
```

Product Catalog အကြီးကြီးကို Alpine.js ထဲ တစ်ခါတည်း မတင်ရ။

Server-side Filtering / Pagination သင့်လျော်ပါက ထိုနည်းကို ဦးစားပေးရမည်။

---

# 32. DATA INTEGRITY

Inventory, Orders, Payments စသည့် Multi-write Operation များတွင် Atomicity လိုပါက Transaction သုံးရမည်။

ဥပမာ—

```php
DB::transaction(function () {
    // related writes
});
```

Operation တစ်ခု Fail သွားလျှင် Partial Data Update ကျန်မနေရ။

---

# 33. FINAL COMPLETION CHECKLIST

Task ပြီးပြီဟု Reply မပေးမီ အောက်ပါအချက်များကို စစ်ရမည်။

```text
[ ] Existing Pattern Reused
[ ] No Unnecessary Duplicate Component
[ ] Store Isolation Preserved
[ ] Validation Correct
[ ] Authorization Correct
[ ] Localization Updated
[ ] SQLite Compatibility Considered
[ ] MySQL Compatibility Considered
[ ] Migration Command Provided If Needed
[ ] Tailwind Build Command Provided If Needed
[ ] Relevant Tests Performed
[ ] Regression Risk Checked
[ ] CHANGELOG.md Updated
[ ] Testing_check.md Updated If Applicable
[ ] Source_of_Truth_MM.md Updated If Business Rule Changed
```

Required Item တစ်ခုခု မပြီးသေးပါက `Fully Completed` ဟု မပြောရ။

---

# 34. FINAL RESPONSE FORMAT

Final Response ကို တိုတိုတိတိထားရမည်။

Recommended Format—

```text
Done.

Changed:
- ProductController.php — warranty validation/save
- _form.blade.php — warranty field
- ProductImportService.php — import/export support
- 3 language files
- CHANGELOG.md — Item 114 added

Verified:
- Store isolation: PASS
- Create/Update: PASS
- Import/Export: PASS

Run:
php artisan migrate
npm run build
```

Testing မပြည့်စုံပါက—

```text
Implemented, but not fully verified.

Not verified:
- MySQL production execution
```

မစစ်ရသေးတာကို စစ်ပြီးပြီဟု မပြောရ။

---

# 35. CORE PRINCIPLE

DataPOS Project ကို System တစ်ခုလုံးအဖြစ် Consistent ဖြစ်အောင် ထိန်းထားရမည်။

အမြဲတမ်း—

```text
Understand Existing System
        ↓
Reuse Existing Pattern
        ↓
Make Minimal Safe Change
        ↓
Test
        ↓
Document
        ↓
Done
```

အတိုင်း လုပ်ရမည်။

Code များများရေးနိုင်ခြင်းကို မဦးစားပေးရ။

**အနည်းဆုံး ပြင်ဆင်မှုဖြင့် မှန်ကန်၊ လုံခြုံ၊ ပြန်ထိန်းသိမ်းလွယ်သော Production-ready Change** ကိုသာ ဦးစားပေးရမည်။