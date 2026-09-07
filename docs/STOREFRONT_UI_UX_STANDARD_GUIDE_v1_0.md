# DataPOS Storefront UI/UX Standard Guide (v1.0)
**Production-Grade E-Commerce Storefront Polish, Offline-First Resilience & Low-End Device Optimization**

---

| Document Meta | Details |
| :--- | :--- |
| **Standard Version** | `v1.0.0-Production-Ready` |
| **Last Updated** | `2026-09-07` |
| **Applicable Modules** | Storefront Web (`resources/views/welcome.blade.php`, `storefront/browse/*`, `products/*`, `cart/*`, `checkout/*`, `service-tracking.blade.php`, `layouts/storefront/*`) |
| **Target Audience** | Software Architects, Full-Stack Engineers, Frontend Developers, QA Engineers, Shop Owners |
| **Reference Architecture** | Laravel 12.64.0, Tailwind CSS 4, Alpine.js, Pure CSS 3D Tactile Rhythm, Service Worker Offline Caching |
| **Target Environment** | Myanmar retail SME market, unstable 3G/4G cellular networks, low-to-mid range Android devices (Tecno, Infinix, Redmi, Realme, 2GB–4GB RAM) & Modern Desktop/iOS |

---

## ၁။ အခြေခံ သဘောတရားနှင့် တင်းကျပ်သော စည်းမျဉ်းများ (Core Philosophy & Red Lines)

Storefront (အွန်လိုင်း အရောင်းစာမျက်နှာ) သည် Customer များနှင့် ပထမဆုံး ထိတွေ့ဆက်ဆံရာ မျက်နှာစာဖြစ်သောကြောင့် လှပရုံသက်သက်သာမက **အလွန်ပေါ့ပါးခြင်း၊ အင်တာနက်လိုင်း မတည်ငြိမ်ချိန်တွင် အလုပ်လုပ်နိုင်ခြင်း၊ ဖုန်းအနိမ့်များတွင် Frame-drop လုံးဝမရှိခြင်း** တို့ မဖြစ်မနေ ပြည့်စုံရမည်။

```
+-----------------------------------------------------------------------------------+
|                    DATAPOS STOREFRONT CRAFTSMANSHIP PILLARS                       |
+-----------------------------------------------------------------------------------+
|  1. Zero-Lag Performance    : Pure CSS 3D Tactile Buttons, No GPU Blur Filters     |
|  2. Offline-First Resilience: LocalStorage Cart, Cached Catalog, Resilient Orders  |
|  3. Low-End Device Support  : 2GB RAM / Helio G35 Friendly, 60/120 FPS Target     |
|  4. Fully Responsive Layout : 320px Mobile to 4K Desktop Adaptive Breakpoints      |
|  5. Tri-lingual Invariance  : Clean Myanmar (No Brackets), English & Chinese       |
|  6. Dynamic Formatting      : Zero Hardcoded "Ks", Clean Integer Quantities        |
|  7. Admin-Driven Theming    : Zero Hardcoded Colors, CSS Token Architecture, 3D Push|
+-----------------------------------------------------------------------------------+
```

### တင်းကျပ်သော တားမြစ်ချက်များ (Strict Red Lines)
1. **No GPU Backdrop-Blur Overuse:** မိုဘိုင်းမျက်နှာပြင်များ၊ Header နှင့် ကတ်များတွင် `backdrop-filter: blur(...)` ကို အလွန်အကျွံ မသုံးရ။ (Mali/PowerVR GPU များတွင် 15–20 FPS အထိ ထိုးကျစေသည်)။
2. **No Hardcoded Currency ("Ks"):** မည်သည့် Blade ဖိုင်နှင့် JavaScript တွင်မှ `"Ks"` သို့မဟုတ် `"(Ks)"` ဟု Hardcoded မရေးရ။ အမြဲတမ်း `format_currency($amount, $store)` သို့မဟုတ် `window.formatCurrency(val)` ကိုသာ သုံးရမည်။
3. **No Trailing Decimals for Whole Quantities:** ကုန်ပစ္စည်း အရေအတွက်များတွင် `.000` မပါစေရ။ `format_quantity($qty, $store)` သို့မဟုတ် `$fmtQty` ဖြင့် `10` ဟုသာ သန့်ရှင်းစွာ ပြရမည်။
4. **No Mixed English/Myanmar Brackets in Microcopy:** `Charging Cable (အားသွင်းကြိုး)` ကဲ့သို့ အင်္ဂလိပ်နှင့် မြန်မာ ရောထွေးနေသော စာသားများ မပြရ။ မြန်မာမုဒ်တွင် `အားသွင်းကြိုး`၊ အင်္ဂလိပ်မုဒ်တွင် `Charging Cable` ဟု ဘာသာစကား သီးသန့် ခွဲထုတ်ပြသရမည်။
5. **No Data Loss on Connection Drop:** အင်တာနက်လိုင်း ပြတ်တောက်သွားခြင်း၊ စာမျက်နှာ Reload ဖြစ်သွားခြင်း သို့မဟုတ် Browser ပိတ်သွားခြင်းတို့ကြောင့် Customer ရွေးချယ်ထားသော ခြင်းတောင်း (Cart) ပျက်ပြယ်မသွားစေရ။
6. **No Hardcoded Design Colors:** မည်သည့်ခလုတ်၊ ကတ် သို့မဟုတ် အိုင်ကွန်တွင်မှ static hex code (ဥပမာ `#0ea5e9`, `#0284c7`) သို့မဟုတ် static Tailwind color classes (`bg-sky-500`) အသေမရေးရ။ Admin Dashboard ကနေ ကာလာနှင့် ဒီဇိုင်းပြောင်းလဲချိန်တွင် အလိုအလျောက် လိုက်လျောညီထွေ ပြောင်းလဲနိုင်စေရန် CSS Custom Properties / Design Tokens (`--sf-primary`, `--sf-primary-bevel`, `--sf-accent`) ဖြင့်သာ တည်ဆောက်ရမည်။

---

## ၂။ အင်တာနက်လိုင်းကျ/ပြတ်တောက်ချိန် အသုံးပြုနိုင်မှု စံနှုန်း (Offline & Network Resilience Standard)

မြန်မာနိုင်ငံ၏ လျှပ်စစ်မီးနှင့် တယ်လီကွန်း အခြေအနေအရ Customer များသည် စျေးဝယ်ယူနေစဉ် အင်တာနက်လိုင်း ခေတ္တပြတ်တောက်ခြင်း (Dropouts) နှင့် မကြာခဏ ကြုံတွေ့ရတတ်သည်။ စနစ်သည် အောက်ပါ Offline-Ready စံနှုန်းများကို ထည့်သွင်းထားရမည်-

```
+-----------------------------------------------------------------------------------+
|                        OFFLINE RESILIENCE ARCHITECTURE                            |
+-----------------------------------------------------------------------------------+
|  [User Browser]                                                                   |
|         │                                                                         |
|         ▼                                                                         |
|  [Service Worker / Cache Storage] ──► Cached Assets (CSS, JS, Fonts, SVG Icons)   |
|         │                                                                         |
|         ▼                                                                         |
|  [IndexedDB / LocalStorage]        ──► Cached Product Catalog & Active Cart        |
|         │                                                                         |
|         ├──► Online  : Normal Checkout ➔ Order API ➔ Realtime Invoice             |
|         │                                                                         |
|         └──► Offline : Fallback Order Queue ➔ Direct Viber/Telegram/Call Order    |
+-----------------------------------------------------------------------------------+
```

### ၂.၁။ LocalStorage Cart System (အော့ဖ်လိုင်း ခြင်းတောင်း ထိန်းသိမ်းမှု)
- Cart Data အား Server Session သက်သက်ပေါ်တွင် မမှီခိုဘဲ Client-side `localStorage.getItem('datapos_cart_' + storeSlug)` တွင် အမြဲ Synchronize ပြုလုပ်ထားရမည်။
- ဖုန်း restart ကျသွားခြင်း၊ browser app ပိတ်သွားခြင်း သို့မဟုတ် flight mode ဖြစ်သွားသည့်တိုင် Customer ရွေးချယ်ထားသော ပစ္စည်းများ မပျောက်ပျက်စေရ။

### ၂.၂။ Network Status Awareness & Visual Indicator (လိုင်းပြတ်တောက်မှု အသိပေးစနစ်)
- Window `online` နှင့် `offline` event listener များကို အသုံးပြု၍ အင်တာနက် ပြတ်တောက်သွားချိန်တွင် မျက်နှာပြင်ထိပ်၌ နှောင့်ယှက်မှုမရှိသော သတိပေးဘား (Toast Banner) ဖော်ပြရမည်-

```javascript
window.addEventListener('offline', () => {
    window.dispatchEvent(new CustomEvent('notify', {
        detail: {
            message: '⚡ အင်တာနက်လိုင်း ပြတ်တောက်နေပါသည် (Offline မုဒ်ဖြင့် ဆက်လက်ကြည့်ရှုနိုင်ပါသည်)',
            type: 'warning'
        }
    }));
});
```

### ၂.၃။ Resilient Multi-Channel Checkout Fallback (လိုင်းကျချိန် အော်ဒါမလွတ်စေသော စနစ်)
အင်တာနက်မရှိ၍ Checkout Form Submit မအောင်မြင်နိုင်သည့်အခါ စနစ်သည် Error တက်ပြီး ရပ်မသွားဘဲ အောက်ပါ Backup လမ်းကြောင်းများကို အလိုအလျောက် ဖွင့်ပေးရမည်-
1. **Direct Viber Order:** ခြင်းတောင်းထဲရှိ ပစ္စည်းအမည်၊ အရေအတွက်၊ စုစုပေါင်းတန်ဖိုးတို့အား Pre-formatted စာသားအဖြစ် ပြုစုပြီး ဆိုင်ရှင်၏ Viber Chat သို့ တိုက်ရိုက်ပို့ဆောင်ပေးခြင်း (`viber://chat?number=...`)။
2. **Direct Telegram Order:** Pre-formatted စာသားဖြင့် Telegram bot / username သို့ တိုက်ရိုက်ချိတ်ဆက်ပေးခြင်း (`https://t.me/...`)။
3. **Direct Phone Call:** ဆိုင်ဖုန်းနံပါတ်သို့ ချက်ချင်း ဖုန်းခေါ်ဆိုနိုင်သော ခလုတ် (`tel:...`)။

---

## ၃။ ဖုန်းအနိမ့်နှင့် စက်အညံ့များအတွက် အထူးစံနှုန်း (Low-End Device & Hardware Support)

မြန်မာနိုင်ငံတွင် အသုံးများသော $80–$150 တန် စမတ်ဖုန်းများ (Tecno Spark, Infinix Hot, Redmi A series, 2GB–3GB RAM, MediaTek Helio G series) တွင် ချောမွေ့စွာ အလုပ်လုပ်နိုင်ရန် အောက်ပါအတိုင်း တင်းကျပ်စွာ တည်ဆောက်ရမည်-

### ၃.၁။ Pure CSS 3D Tactile Push System (`sf-btn-3d`)
Button နှင့် Card တိုင်းတွင် လေးလံသော `box-shadow` အထပ်ထပ်နှင့် `backdrop-filter` များကို လုံးဝရှောင်ကြဉ်ပြီး **3px Bottom Solid Bevel** ဖြင့်သာ 3D အသွင် ဖန်တီးရမည်-

```css
/* resources/css/app.css — Zero-Lag Pure CSS 3D Button */
.sf-btn-3d {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
    transform: translateZ(0); /* Hardware GPU Acceleration */
    transition: transform 120ms ease, border-bottom-width 120ms ease, box-shadow 120ms ease;
    border-radius: 0.75rem;
    border: 1px solid rgba(203, 213, 225, 0.9);
    border-bottom: 3px solid #cbd5e1;
    background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
    color: #1e293b;
}

.sf-btn-3d:hover {
    transform: translateY(-1.5px);
    border-bottom: 4px solid #94a3b8;
}

.sf-btn-3d:active {
    transform: translateY(1.5px);
    border-bottom: 1.5px solid #cbd5e1;
}

.sf-btn-3d.active {
    background: linear-gradient(180deg, #0ea5e9 0%, #0284c7 100%);
    color: #ffffff !important;
    border-bottom: 3px solid #0369a1;
}
```

### ၃.၂။ Image Optimization & CLS (Layout Shift) ကာကွယ်ခြင်း
- ကုန်ပစ္စည်းဓာတ်ပုံတိုင်းတွင် `aspect-square` (သို့မဟုတ် `aspect-[4/3]`) container ထည့်သွင်းထားရမည် (ဓာတ်ပုံ load မပြီးမီ အောက်စာသားများ ခုန်တက်ခုန်ဆင်းဖြစ်သည့် CLS ကို တားဆီးရန်)။
- Banner ပထမပုံမှလွဲ၍ ကျန်ပုံများအားလုံးတွင် `loading="lazy"` နှင့် `decoding="async"` မဖြစ်မနေ ထည့်သွင်းရမည်။
- ဓာတ်ပုံပျက်နေပါက မازیမရွံ့ဖြစ်မနေစေရန် `data-img-fallback="hide-parent"` စနစ် တပ်ဆင်ထားရမည်။

### ၃.၃။ Local Font Assets (Web Font Loading Lag ကင်းစင်ခြင်း)
- Google CDN မှ Font ခေါ်ယူခြင်းကို လုံးဝရှောင်ကြဉ်ပြီး Local Assets (`public/build/assets/NotoSansMyanmar-*.ttf`) မှတစ်ဆင့်သာ ရယူရမည်။
- မြန်မာစာသားများ သေးငယ်ကျဉ်းမြောင်းမနေစေရန် အနိမ့်ဆုံး စာလုံးဆိုဒ်ကို `text-[13px]` ထားရှိပြီး၊ ခေါင်းစဉ်နှင့် ခလုတ်များတွင် `text-sm sm:text-base` နှင့် `font-black` (သို့မဟုတ် `font-bold`) ဖြင့် မျက်စိထဲ ထင်ရှားရှင်းလင်းစွာ မြင်သာစေရမည်။

---

## ၄။ Admin စီမံခန့်ခွဲမှုမှ ဒီဇိုင်းကာလာများ အလွယ်တကူ ပြောင်းလဲနိုင်မှုနှင့် Dynamic 3D ခလုတ် စံနှုန်း (Admin-Driven Dynamic Theming & Dynamic 3D Buttons)

ဆိုင်ရှင်များသည် ၎င်းတို့၏ စတိုးဆိုင် Brand Identity နှင့် Theme အရောင်များကို Admin Dashboard (`/admin/settings/theme`) မှ အချိန်မရွေး စိတ်ကြိုက် ပြောင်းလဲလိုကြသည်။ ထို့ကြောင့် Storefront ရှိ ခလုတ်များ၊ Header၊ Badges များနှင့် Cards များသည် Code ထဲတွင် Hex Code အသေ (Hardcoded) ရေးသားထားခြင်း လုံးဝမရှိစေဘဲ **Dynamic Design Tokens** ဖြင့် အပြည့်အဝ ချိတ်ဆက်ထားရမည်။

```
+-----------------------------------------------------------------------------------+
|                     ADMIN-DRIVEN DYNAMIC THEMING FLOW                             |
+-----------------------------------------------------------------------------------+
|  [Admin Theme Settings] (/admin/settings/theme)                                   |
|         │  Select: Tech Sky / Emerald / Violet / Amber / Custom Hex Color         |
|         ▼                                                                         |
|  [StorefrontSetting Model] (theme_primary_color, theme_accent_color, etc.)         |
|         │                                                                         |
|         ▼                                                                         |
|  [:root CSS Variables Injection] (resources/views/layouts/storefront/app.blade.php)|
|         ├──► --sf-primary        : #0ea5e9                                        |
|         ├──► --sf-primary-hover  : color-mix(in srgb, var(--sf-primary) 85%, #fff)|
|         ├──► --sf-primary-bevel  : color-mix(in srgb, var(--sf-primary) 70%, #000)|
|         └──► --sf-primary-active : color-mix(in srgb, var(--sf-primary) 85%, #000)|
|         │                                                                         |
|         ▼                                                                         |
|  [Storefront Components & 3D Buttons] (Zero Hardcoded Hex Codes)                  |
|         ├──► .sf-btn-3d.active   ──► Dynamic Brand Gradient & 3D Bevel Border     |
|         ├──► Add to Cart CTA     ──► Dynamic Primary Glow & Tactile Depression    |
|         └──► Category Badges     ──► Dynamic Ambient Tints & Contrast Text        |
+-----------------------------------------------------------------------------------+
```

### ၄.၁။ CSS Custom Properties (Design Tokens Architecture)
Root Container (`:root`) တွင် Admin မှ ရွေးချယ်ထားသော Primary/Accent အရောင်များကို အခြေခံ၍ Dynamic 3D Bevel Tokens များကို `color-mix()` ဖြင့် အလိုအလျောက် တွက်ချက်ထုတ်လုပ်ပေးရမည်-

```css
:root {
    /* ── Admin Driven Brand Tokens ── */
    --sf-primary:        {{ $sfColors['primary'] }}; /* Admin Theme Primary Color */
    --sf-accent:         {{ $sfColors['accent'] }};  /* Admin Accent Highlight Color */
    
    /* ── Dynamic Primary 3D Button Tokens ── */
    --sf-primary-hover:  color-mix(in srgb, var(--sf-primary) 85%, #ffffff);
    --sf-primary-bevel:  color-mix(in srgb, var(--sf-primary) 70%, #000000);
    --sf-primary-active: color-mix(in srgb, var(--sf-primary) 85%, #000000);

    /* ── Dynamic Accent 3D Button Tokens ── */
    --sf-accent-hover:   color-mix(in srgb, var(--sf-accent) 85%, #ffffff);
    --sf-accent-bevel:   color-mix(in srgb, var(--sf-accent) 70%, #000000);
    --sf-accent-active:  color-mix(in srgb, var(--sf-accent) 85%, #000000);
    
    /* ── Header & Body Theme Surfaces ── */
    --sf-header-bg:      {{ $sfColors['header_bg'] }};
    --sf-header-bg-dark: color-mix(in srgb, var(--sf-primary) 15%, #0f172a);
    --sf-body-bg:        {{ $sfColors['body_bg'] ?? '#f8fafc' }};
    --sf-body-bg-dark:   color-mix(in srgb, var(--sf-primary) 8%, #0b0f19);
}

.dark:root,
html.dark {
    --sf-primary-bevel:  color-mix(in srgb, var(--sf-primary) 50%, #000000);
    --sf-accent-bevel:   color-mix(in srgb, var(--sf-accent) 50%, #000000);
}
```

### ၄.၂။ Dynamic 3D Push Button Suite (ခလုတ်များ အားလုံး 3D ပုံစံဖြင့် စနစ်တကျ တည်ဆောက်ခြင်း)
Storefront စာမျက်နှာအားလုံးရှိ ခလုတ်များကို အောက်ပါ စံသတ်မှတ် 3D Classes ၅ မျိုးဖြင့်သာ ရေးသားရမည်ဖြစ်ပြီး မည်သည့် အရောင် Hex Code မှ အသေရေးသားခြင်း မပြုရပါ-

| 3D Button Class | ရည်ရွယ်ချက်နှင့် အသုံးပြုရမည့် နေရာ | Dynamic Color Token အရင်းအမြစ် | 3D Bevel အနားသတ် |
| :--- | :--- | :--- | :--- |
| **`.sf-btn-3d`** | Neutral / Secondary (ဥပမာ- Categories, Nav, Qty `+`/`-`) | Slate Neutral Surface Gradient | `3px solid #cbd5e1` (Dark: `#020617`) |
| **`.sf-btn-3d-primary`**<br>*(or `.sf-btn-3d.active`)* | ပင်မလုပ်ဆောင်ချက် (Add to Cart, Buy Now, Active Tab) | Admin `--sf-primary` (Dynamic) | `3px solid var(--sf-primary-bevel)` |
| **`.sf-btn-3d-accent`** | အထူးသီးသန့် Highlight / Promo Badges / Compare | Admin `--sf-accent` (Dynamic) | `3px solid var(--sf-accent-bevel)` |
| **`.sf-btn-3d-success`** | အတည်ပြုခြင်းနှင့် Chat (Checkout, Viber/WhatsApp Order) | Emerald Green (`#10b981`) | `3px solid #047857` |
| **`.sf-btn-3d-danger`** | ဖျက်ပယ်ခြင်း (Remove Item, Clear Cart, Cancel) | Rose Red (`#f43f5e`) | `3px solid #be123c` |

```html
<!-- ၁။ Neutral 3D Button (Secondary Actions / Filters / Categories) -->
<button class="sf-btn-3d px-4 py-2 rounded-xl text-sm font-bold">
    {{ __('messages.categories') }}
</button>

<!-- ၂။ Dynamic Primary 3D Button (Add to Cart / Main CTA - Admin Color အတိုင်း အလိုအလျောက် ပြောင်းသည်) -->
<button class="sf-btn-3d-primary px-5 py-2.5 rounded-xl text-sm font-bold shadow-md">
    <x-storefront.navigation-icon name="cart" class="h-4 w-4 mr-1.5" />
    {{ __('messages.add_to_cart') }}
</button>

<!-- ၃။ Dynamic Accent 3D Button (Secondary CTA / Specials) -->
<button class="sf-btn-3d-accent px-4 py-2 rounded-xl text-sm font-bold">
    {{ __('messages.special_offers') }}
</button>

<!-- ၄။ Success 3D Button (Direct Viber Order / Confirm) -->
<a href="viber://chat?number=..." class="sf-btn-3d-success px-4 py-2 rounded-xl text-sm font-bold">
    💬 Viber ဖြင့်မှာယူမည်
</a>

<!-- ၅။ Danger 3D Button (Remove / Clear Cart) -->
<button class="sf-btn-3d-danger px-3 py-1.5 rounded-lg text-xs font-bold">
    ဖျက်မည်
</button>
```

### ၄.၃။ 3D Button Interaction Mechanics (စက်မလေးသော ရူပဗေဒ အပြုအမူ)
1. **Normal Rest State:**
   - အောက်ခြေတွင် `border-bottom: 3px solid [bevel-color]` ဖြင့် ကြွတက်နေသော 3D tactile အသွင်အပြင်ရှိသည်။
2. **Hover State (ကြွတက်ခြင်း):**
   - Mouse တင်လိုက်ချိန်တွင် `translateY(-1.5px)` ကြွတက်ပြီး အောက်ခြေ Bevel သည် `4px` သို့ နက်ရှိုင်းသွားမည်။
3. **Active Pressed State (အိဆင်းခြင်း):**
   - နှိပ်လိုက်ချိန်တွင် `translateY(1.5px)` အောက်သို့ နစ်ဆင်းသွားပြီး အောက်ခြေ Bevel သည် `1.5px` သို့ ကျုံ့သွားသဖြင့် တကယ့် physical keycap နှိပ်ရသလို ခံစားမှုပေးစွမ်းသည်။
4. **Zero GPU Filter Lag:**
   - ဤ 3D စနစ်တွင် `backdrop-filter: blur(...)` သို့မဟုတ် heavy multi-layered shadows များကို လုံးဝမသုံးဘဲ pure CSS borders နှင့် GPU `translateZ(0)` hardware acceleration ကိုသာ သုံးထားသဖြင့် low-end devices များတွင် 60/120 FPS အပြည့် ရရှိစေသည်။

### ၄.၄။ Preset Color Palettes & Custom Hex Picker
Admin Dashboard (`/admin/settings/theme`) တွင် အောက်ပါ Built-in Brand Presets များ ပါဝင်ရမည်ဖြစ်ပြီး Custom Color Picker ဖြင့်လည်း စိတ်ကြိုက် ရွေးချယ်နိုင်စေရမည်-
- **Modern Tech (Sky Blue):** `#0ea5e9` (ဖုန်းနှင့် နည်းပညာပစ္စည်းအရောင်းဆိုင်များအတွက်)
- **Emerald Merchant (Green):** `#10b981` (ယုံကြည်စိတ်ချရသော ကုန်စုံနှင့် စီးပွားရေးဆိုင်များအတွက်)
- **Royal Tech (Violet):** `#7c3aed` (Gaming၊ Computer နှင့် Premium Gadget ဆိုင်များအတွက်)
- **Cyber Amber (Gold/Orange):** `#f59e0b` (လျှပ်စစ်ပစ္စည်းနှင့် Accessories ဆိုင်များအတွက်)
- **Crimson Energy (Red):** `#ef4444` (Promotion နှင့် Super Deals အထူးပြုဆိုင်များအတွက်)

### ၄.၅။ Developer Coding Rules for Zero-Hardcoding
- **Rule 1 (No Static Hex in Blade/CSS):** Blade view များ သို့မဟုတ် CSS များတွင် `#0ea5e9` သို့မဟုတ် `#2563eb` ကဲ့သို့သော hex code များ အသေ (Hardcoded) မရေးရ။ အမြဲတမ်း `class="sf-btn-3d-primary"` သို့မဟုတ် `var(--sf-primary)` ကိုသာ သုံးရမည်။
- **Rule 2 (No Color-Specific Tailwind Utility for Brand Elements):** Primary Brand CTAs (Add to Cart, Checkout, Active Tab, Primary Filter Pill) များတွင် `bg-sky-500`, `bg-blue-600`, `text-sky-600` မသုံးရ။ Theme Token ချိတ်ဆက်ထားသော `sf-btn-3d-primary` သို့မဟုတ် `sf-btn-3d.active` ကိုသာ သုံးရမည်။
- **Rule 3 (Admin Theme Change Instant Reflect):** Admin Panel မှ အရောင်ပြောင်းလိုက်သည်နှင့် စတိုးဆိုင် Storefront စာမျက်နှာအားလုံး (`welcome.blade.php`, `products/*`, `cart/*`) ရှိ ခလုတ်များ၊ active nav၊ badges များ အားလုံးသည် Asset Recompile လုပ်စရာမလိုဘဲ ချက်ချင်း အရောင်လိုက်ပြောင်းသွားရမည်။

---

## ၅။ မျက်နှာပြင် အရွယ်အစားမျိုးစုံ စံသတ်မှတ်ချက် (Fully Responsive Breakpoints)

Storefront သည် မျက်နှာပြင် အကျဉ်းဆုံး 320px မှစတင်ကာ 4K Monitor အထိ ပြီးပြည့်စုံစွာ အချိုးကျ ပြသနိုင်ရမည်-

| Breakpoint | မျက်နှာပြင် အတိုင်းအတာ | ပစ်မှတ်ထားသော စက်ပစ္စည်းများ | Grid & Layout စံနှုန်း |
| :--- | :--- | :--- | :--- |
| **xs (Extra Small)** | `320px – 374px` | iPhone SE, Galaxy A01/A03 | Product 2-col (`gap-2`), Category Pills horizontal scroll |
| **sm (Small Mobile)**| `375px – 639px` | iPhone 13/14, Redmi, Vivo | Product 2-col (`gap-2.5 sm:gap-3`), Floating 3D Bottom Nav Bar |
| **md (Tablet / Fold)**| `640px – 1023px`| iPad, Galaxy Tab, Foldables | Product 3 to 4-col, Compact Banner Slider |
| **lg (Desktop Entry)**| `1024px – 1279px`| 13"–14" Laptop, Desktop HD | 25% Category Sidebar + 75% Hero Slider, Product 4-col |
| **xl (Wide Screen)** | `1280px+` | Full HD / 2K / 4K Monitors | Max Container `max-w-7xl`, Product 5-col Grid |

### Safe Area Ergonomics (မိုဘိုင်း လက်ချောင်း အထိန်းအမှတ်)
- iPhone Home Indicator Bar နှင့် Android Navigation Bar များကို မကွယ်စေရန် Storefront အောက်ခြေတွင် `pb-[calc(env(safe-area-inset-bottom,0px)+6rem)]` ထည့်သွင်းထားရမည်။
- အဓိက နှိပ်ရမည့် ခလုတ်တိုင်း (Add to Cart, Buy Now, Search, Menu) သည် Touch Target အနည်းဆုံး **`44px × 44px`** ရှိရမည်။

---

## ၆။ စာမျက်နှာအလိုက် လက်တွေ့ အဆင့်မြှင့်တင်ရမည့် Checklist (Page-by-Page Polish Checklist)

### ၅.၁။ ပင်မစာမျက်နှာ — Storefront Home (`welcome.blade.php`)
- [x] **Sticky 3D Header:** Brand Logo, Real-time Search Input (`h-9`), Dark Mode Switcher, Cart Trigger (`sf-btn-3d`)။
- [x] **Desktop Category Sidebar:**
  - Item Height အား ကျစ်လစ်သော **`40px`** (`py-1.5`) ထားရှိရမည်။
  - Subcategory Flyout Panel အကျယ်အား **`w-56 sm:w-64` (`256px`)** သို့ ချိန်ညှိရမည် (Banner မကွယ်စေရန်)။
  - ကွင်းစကွင်းပိတ်မပါသော **သဘာဝကျသည့် မြန်မာစကား သီးသန့်** (`$category->localized_name`) ပြသရမည်။
- [x] **4-Item Value Trust Strip:** Banner အောက်တွင် `⚡ အမြန်ဆုံး ပို့ဆောင်မှု`၊ `🛡️ စစ်မှန်သော အာမခံ`၊ `🔧 ဆာဗစ်စစ်`၊ `💬 တိုက်ရိုက် အကူအညီ` ကတ် ၄ ခုအား 3D Tactile Push Cards များအဖြစ် တပ်ဆင်ရမည်။
- [x] **Flash Sale & Promotional Product Grids:** စတော့အရေအတွက် နည်းနေပါက Low Stock Badge (`ကျန်ရှိအရေအတွက် နည်းပါး`) ကို ထင်ရှားသော အရောင်ဖြင့် ပြသရမည်။

### ၅.၂။ ကုန်ပစ္စည်းစာရင်း စာမျက်နှာ — Product Catalog (`products/index.blade.php`)
- [x] **Sticky Filter Toolbar:** အမျိုးအစား Filter Pills၊ စျေးနှုန်း Range Slider၊ လက်ကျန်ရှိသော ပစ္စည်းများသာကြည့်ရန် Toggle။
- [x] **Mobile Filter Drawer:** ဖုန်းမျက်နှာပြင်များတွင် အောက်ခြေမှ အိစက်စွာ တက်လာမည့် Slide-over Filter Sheet (1-Tap Clear All ပါဝင်ရမည်)။
- [x] **Clean Product Cards:**
  - ပုံရိပ်ပေါ်တွင် တင်ထားသော 3D Wishlist Heart ခလုတ်။
  - ပစ္စည်းအမည် (`font-bold font-myanmar`)။
  - Dynamic Currency စျေးနှုန်း (`format_currency($p->price, $store)`)။
  - 1-Tap Quick Add to Cart 3D Push Button။

### ၅.၃။ ပစ္စည်းအသေးစိတ် စာမျက်နှာ — Product Details (`products/show.blade.php`)
- [x] **Image Gallery with Pinch-to-Zoom:** လက်ချောင်းဖြင့် ဘယ်/ညာ ပွတ်ဆွဲ၍ ကြည့်ရှုနိုင်သော Image Carousel + Thumbnail Navigation။
- [x] **Variant Matrix Picker:** အရောင် (Color)၊ သိုလှောင်မှု (Storage)၊ အမျိုးအစားခွဲ (Specs) များကို 3D Pills များဖြင့် ရွေးချယ်စေပြီး စျေးနှုန်းနှင့် စတော့ကို Realtime အလိုအလျောက် ပြောင်းလဲပြသခြင်း။
- [x] **Direct Social Order Action Bar:**
  - အစိမ်းရောင် `Viber ဖြင့် ချက်ချင်းမှာယူရန်` ခလုတ်။
  - အပြာရောင် `Telegram ဖြင့် မေးမြန်းရန်` ခလုတ်။
  - အဓိက `ခြင်းတောင်းထဲသို့ ထည့်မည်` 3D ခလုတ်ကြီး။
- [x] **Wholesale Customer Experience:** လက်ကားခွင့်ပြုချက်ရရှိထားသော Customer ဖြစ်ပါက လက်ကားစျေးနှုန်း Badge နှင့် အနည်းဆုံးမှာယူရမည့် အရေအတွက် (MOQ) ကို ရှင်းလင်းစွာ ဖော်ပြခြင်း။

### ၅.၄။ ခြင်းတောင်းနှင့် ငွေရှင်း စာမျက်နှာ — Cart & Checkout (`cart/*`, `checkout/*`)
- [x] **Zero-Loss Cart Review:** ကုန်ပစ္စည်းတစ်ခုချင်းစီ၏ အရေအတွက် အတိုး/အလျှော့ ခလုတ်များ (`+` / `-`) အား Instant 3D Push ပြုလုပ်နိုင်ခြင်း။
- [x] **Delivery & Logistics Selector:**
  - ရန်ကုန်/မန္တလေး အိမ်ရောက်ငွေချေ (Doorstep Delivery)။
  - နယ်ဝေး ကားဂိတ်တင်ပေးပို့မှု (Bus Gate Delivery) နှင့် ဂိတ်အမည်/မြို့နယ် ဖြည့်သွင်းသည့် ကွက်လပ်များ။
- [x] **Payment & Receipt Slip Attachment:**
  - KPay / WavePay / CB Pay / AYA Pay QR Code ပြသမှု။
  - ငွေလွှဲပြေစာ Screenshot Upload လုပ်နိုင်သော Image Picker (Local Image Compression ပါဝင်ရမည်)။
  - COD (ပစ္စည်းရောက်မှ ငွေချေစနစ်) Toggle Option။

### ၅.၅။ ဆာဗစ်စစ် စစ်ဆေးခြင်း စာမျက်နှာ — Service Tracking (`service-tracking.blade.php`)
- [x] **Instant Ticket Lookup:** ဘောက်ချာနံပါတ် သို့မဟုတ် ဖုန်းနံပါတ် ရိုက်ထည့်ရုံဖြင့် ချက်ချင်း ရှာဖွေနိုင်ခြင်း (Barcode Scanner ပါဝင်ရမည်)။
- [x] **5-Stage Visual Stepper:**
  1. ပစ္စည်းလက်ခံရရှိ (`Received`)
  2. ချို့ယွင်းချက်စစ်ဆေးနေဆဲ (`Diagnosing`)
  3. ပြင်ဆင်နေဆဲ (`In Repair`)
  4. စမ်းသပ်စစ်ဆေးပြီးစီး (`Quality Testing`)
  5. ပစ္စည်းလာရောက်ထုတ်ယူနိုင်ပြီ (`Ready for Pickup`)
- [x] **Direct Technician Assistance:** တာဝန်ခံပြင်ဆင်သူနှင့် ချက်ချင်း စကားပြောနိုင်သော Direct Chat ခလုတ်။

---

## ၆။ ၃ ဘာသာ ပြိုင်တူ စံသတ်မှတ်ချက် (Tri-Lingual Localization Invariance)

`AGENTS.md` စည်းမျဉ်းအတိုင်း အင်္ဂလိပ်စာသားအလွတ်များ လုံးဝမကျန်စေဘဲ ဘာသာစကား ၃ မျိုးစလုံးတွင် တစ်ပြိုင်နက် ပြည့်စုံရမည်-

```
+-----------------------------------------------------------------------------------+
|                     TRI-LINGUAL LOCALIZATION INVARIANCE                           |
+-----------------------------------------------------------------------------------+
|  File: lang/my/messages.php   ──► သဘာဝကျသော မြန်မာစကား (ကွင်းစကွင်းပိတ်အပိုများ မပါရ) |
|  File: lang/en/messages.php   ──► Professional, concise English e-commerce terms  |
|  File: lang/zh_CN/messages.php──► Standard Simplified Chinese (全汉化，无遗漏)      |
+-----------------------------------------------------------------------------------+
```

### စံနမူနာ ဘာသာပြန် ဖွဲ့စည်းပုံ-

| Key | Myanmar (`lang/my`) | English (`lang/en`) | Chinese (`lang/zh_CN`) |
| :--- | :--- | :--- | :--- |
| `nav_home` | ပင်မ | Home | 首页 |
| `nav_products` | ပစ္စည်းများ | Products | 全部商品 |
| `categories` | ကဏ္ဍများ | Categories | 商品分类 |
| `view_all` | အားလုံးကြည့်ရန် | View All | 查看全部 |
| `fast_delivery` | အမြန်ဆုံး ပို့ဆောင်မှု | Fast Delivery | 快速配送 |
| `genuine_warranty` | စစ်မှန်သော အာမခံ | Genuine Warranty | 正品保障 |
| `direct_support` | တိုက်ရိုက် အကူအညီ | Direct Support | 客服咨询 |
| `add_to_cart` | ခြင်းတောင်းထဲထည့်မည် | Add to Cart | 加入购物车 |
| `buy_now` | ချက်ချင်းဝယ်မည် | Buy Now | 立即购买 |
| `checkout` | ငွေရှင်းမည် | Checkout | 去结算 |

---

## ၇။ Production မထုတ်မီ စစ်ဆေးရမည့် QA စစ်ဆေးမှုများ (Pre-Production QA Protocol)

Production သို့ Deploy မလုပ်မီ အောက်ပါ အဆင့် ၅ ဆင့်အတိုင်း မဖြစ်မနေ စစ်ဆေးအတည်ပြုရမည်-

```powershell
# ၁။ Asset Compilation & Zero-Error Check
npm run build

# ၂။ Route & Configuration Cache Verification
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ၃။ Database & Seeder Integrity Check
php artisan test --filter=StorefrontTest
```

### DevTools စစ်ဆေးမှု စံနှုန်းများ-
1. **Network Throttling Test:** Chrome DevTools တွင် `Slow 3G` ဖြင့် စမ်းသပ်ပါ (CSS/JS ပျက်မသွားဘဲ Skeleton Card များဖြင့် စနစ်တကျ ပြသနိုင်ရမည်)။
2. **Offline Simulation Test:** DevTools Network tab တွင် `Offline` ဖွင့်ထားချိန်၌ Cart နှင့် စာမျက်နှာများ ပျက်မသွားဘဲ Resilient Order ခလုတ်များ အလုပ်လုပ်ရမည်။
3. **CPU 4x Slowdown Test:** DevTools Performance tab တွင် `4x CPU Slowdown` ထားရှိပြီး Category Flyout Panel ဖွင့်/ပိတ်ခြင်းနှင့် Scroll ပြုလုပ်ခြင်းတို့တွင် Frame-rate သည် `60 FPS` အောက် မကျဆင်းရ။
4. **Mobile Responsive Inspection:** `320px` (iPhone SE) နှင့် `360px` (Galaxy A series) အကျဉ်းဆုံး မျက်နှာပြင်များတွင် စာသားများ ဘေးဘက်သို့ လျှံထွက်ခြင်း (Horizontal Overflow) လုံးဝ မရှိရ။

---

## ၈။ အနှစ်ချုပ် တာဝန်ခံချက် (Architectural Commitment)

ဤလမ်းညွှန်ပါ စံသတ်မှတ်ချက်များသည် အပေါ်ယံသဘောမျိုး ရေးသားထားခြင်း မဟုတ်ဘဲ **မြန်မာနိုင်ငံရှိ မိုဘိုင်းဖုန်း၊ ကွန်ပျူတာ၊ စီစီတီဗီနှင့် နည်းပညာဆိုင်ရာ SME လုပ်ငန်းများ** တွင် လက်တွေ့ကျကျ နေ့စဉ်သုံးစွဲနိုင်ရေးအတွက် ရည်ရွယ်ဖန်တီးထားခြင်း ဖြစ်ပါသည်။

စတိုးဆိုင်မျက်နှာစာ (Storefront) အား ထပ်မံ အဆင့်မြှင့်တင်ရာတွင် ဤ `v1.0` စံနှုန်းများကို တိကျစွာ လိုက်နာခြင်းဖြင့် ကမ္ဘာ့အဆင့်မီ ချောမောလှပပြီး၊ အင်တာနက်လိုင်း မည်မျှပင် ကျဆင်းနေစေကာမူ ရောင်းအားမကျဆင်းစေသော စစ်မှန်သော E-Commerce Platform အဖြစ် ရပ်တည်နိုင်မည် ဖြစ်ပါသည်။

---

## ၉။ Storefront တစ်ခုလုံးရှိ ပြင်ဆင်ရမည့် စာမျက်နှာများ စာရင်းနှင့် တိုးတက်မှု အခြေအနေ (Storefront Master Polish Checklist)

Storefront တစ်ခုလုံးအား အပေါ်ယံမဟုတ်ဘဲ စစ်မှန်သော Production စံချိန်မီဖြစ်စေရန် တစ်မျက်နှာပြီးတစ်မျက်နှာ အစီအစဉ်တကျ စစ်ဆေးပြင်ဆင်နိုင်ရန် အောက်ပါအတိုင်း စာရင်းပြုစုထားပါသည်-

### ၉.၁။ ပင်မ အရောင်းနှင့် စျေးဝယ်စာမျက်နှာများ (Core Commerce Pages)
- [x] **Storefront Home (ပင်မစာမျက်နှာ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/welcome.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/welcome.blade.php)
  - **လုပ်ဆောင်ချက်များ:** 3D Desktop Navigation, Compact Category Sidebar (`40px`), Slim Flyout Panel (`256px`), Value Trust Strip 3D Cards, Section Headers ရှိ "အားလုံးကြည့်မည် (View All)" Link များအား `sf-btn-3d active` Mini Push Buttons သို့ ပြောင်းလဲခြင်း၊ Glass Finder CTA အား `sf-btn-3d active` သို့ ပြင်ဆင်ခြင်း၊ Direct Support Modal ခလုတ်များ (Viber, Telegram, Phone) အား 3D Bevel Buttons သို့ ပြောင်းလဲခြင်း၊ Dynamic Theming Tokens အပြည့်အဝ ချိတ်ဆက်ခြင်း၊ **Browse Left-Rail Style Category Scroll Cards & Smooth Navigation:** "လူကြိုက်အများဆုံး အမျိုးအစားများ" Horizontal Scroll Cards များအား Browse Left-Rail ၏ `sf-btn-3d aspect-[3/4]` စံအတိုင်း Full-bleed edge-to-edge ဓာတ်ပုံ (~၈၀%)၊ ထိပ်ညာဘက် Semi-transparent Count Badge၊ ပါးလွှာကျစ်လျစ်သော အမည်ပြား၊ `/browse?category_id=...` သို့ Deep-link Auto-scroll Active စနစ်၊ Buttery-Smooth Inertial Drag (1:1 Physics with Click Protection) နှင့် Header ရှိ 3D Prev/Next Navigation Arrow Buttons (`❮` / `❯`) တို့ဖြင့် စုံလင်စွာ ပြင်ဆင်ပြီးစီး။ **Hero Section Subtle Corners Standard (`rounded-md sm:rounded-lg`):** Category Sidebar Background Card, Flyout Subcategory Panels, Hero Banner Photo Slider Container, Banner Navigation Controls, Fallback Hero Card နှင့် Trust Strip ကတ်များ/အိုင်ကွန်များအားလုံးတွင် ဖောင်းကြွနေသော `rounded-2xl` / `rounded-3xl` အဝိုက်ကြီးများ အားလုံးကို လျှော့ချပြီး ကျစ်လျစ်သပ်ရပ်သော `rounded-md sm:rounded-lg` (4px–8px) စံနှုန်းသို့ ပြင်ဆင်ပြီးစီး။ **Hero Section Anti-Drop Flexbox Architecture (Layout Stability):** CSS Grid Auto-placement ချို့ယွင်းချက်ကြောင့် အင်တာနက် အနည်းငယ်နှေးချိန် သို့မဟုတ် Rendering မပြီးပြတ်မီအချိန်များတွင် Hero Banner Slider သည် Row 2 သို့ ဆင်းကျသွားပြီး Sidebar ညာဘက်တွင် ကွက်လပ်ကြီးဖြစ်သွားသည့် ပြဿနာအား အပြီးအပိုင် ဖြေရှင်းနိုင်ရန် စိတ်ချရသော Flexbox Layout (`flex flex-col lg:flex-row items-stretch`) သို့ အဆင့်မြှင့်တင်ပြီး Category Sidebar (`lg:w-72 xl:w-80 shrink-0`) နှင့် Banner Slider Column (`w-full flex-1 min-w-0`) အမြဲတမ်း ဘေးချင်းယှဉ် တည်ငြိမ်စွာ ရပ်တည်နိုင်စေရန် ပြုပြင်ပြီးစီး။
- [x] **Product Catalog & Search (ကုန်ပစ္စည်းစာရင်းနှင့် ရှာဖွေမှု):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/catalog/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/catalog/index.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Hardcoded `Ks` နှင့် `(Ks)` များ အကုန်ဖယ်ရှားပြီး `format_currency()` သို့ ပြောင်းလဲခြင်း၊ Hardcoded ကာလာများ ဖယ်ရှားပြီး Dynamic 3D Push Button Suite (`sf-btn-3d`, `sf-btn-3d-primary`, `sf-btn-3d-danger`, `sf-btn-3d-success`, `sf-btn-3d-accent`) တပ်ဆင်ခြင်း၊ Grid/List view switcher 3D ပြောင်းခြင်း၊ Slim Category/Brand Hover Flyout (`256px` Zero GPU Lag) တပ်ဆင်ခြင်း၊ Desktop 2-Column Side-by-Side Grid Layout နှင့် Product Card Action Buttons များကို 3D ပြုလုပ်ပြီးစီး။
- [x] **Product Details & Variant Specs (ကုန်ပစ္စည်းအသေးစိတ်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/catalog/show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/catalog/show.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Hardcoded inline gradient styles အားလုံး ဖယ်ရှားပြီး 3D Tactile Suite ဖြင့် အစားထိုးခြင်း၊ "Buy Now" CTA အား `.sf-btn-3d-orange` ဖြင့်လည်းကောင်း၊ "Add to Order" CTA အား `.sf-btn-3d-primary` ဖြင့်လည်းကောင်း တပ်ဆင်ခြင်း၊ Direct Order (Viber & Telegram) အတွက် `.sf-btn-3d-viber` နှင့် `.sf-btn-3d-telegram` 3D Buttons အသစ်များ တည်ဆောက် တပ်ဆင်ခြင်း၊ 3D Variant Matrix Pills (Color/Storage/Specs) များတွင် Tactile push physics နှင့် active state သတ်မှတ်ခြင်း၊ 3D Wholesale Badge နှင့် Promo Pill များ တပ်ဆင်ခြင်း၊ 3D Metallic Gold / Rose Favorite Heart Button နှင့် 3D Share Button တပ်ဆင်ခြင်း၊ `lang/{en,my,zh_CN}/messages.php` သုံးဘာသာစလုံးတွင် `'buy_now'` key အပြည့်အစုံ ဖြည့်စွက်ပြီးစီး။
- [x] **Category Browser (ကုန်ပစ္စည်း ကဏ္ဍစုံ ရှာဖွေမှု):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/browse/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/browse/index.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Shopee/Lazada ပုံစံ 2-Pane Split-Screen Layout (ဘယ်ဘက် Vertical Categories Rail + ညာဘက် Category Details/Brands/Sub-categories Pane) ဖြင့် စနစ်ကျစွာ တည်ဆောက်ပြီးစီး။
    - **Admin Master Data Categories Synchronization:** `/admin/categories` တွင် ထည့်သွင်းထားသော Main Categories (၁၅) ခုလုံးအား Storefront တွင် အမည်၊ အရေအတွက်နှင့် အစဉ်လိုက် တိကျစွာ ပြသခြင်း။
    - **Sharp Rectangular 3D Card Architecture (`rounded-none`):** Category Cards၊ Brand Cards၊ Sub-category Pills နှင့် Product Grid Cards များအားလုံး၏ ထောင့် ၄ ဖက်လုံးမှ Rounded အဝိုက်များအားလုံးကို အပြီးအပိုင် ဖြုတ်ပယ်ပြီး လေးထောင့်စပ်စပ် 3D Tactile Push Tiles စတိုင်လ်သို့ ပြောင်းလဲခြင်း။
    - **Left Rail Edge-to-Edge Full-Bleed Proportions:** ဘယ်ဘက် Main Category ကတ်များ၏ အပေါ်ပိုင်း ၈၀% (`h-[77%] sm:h-[80%]`) အား အပြည့်ဓာတ်ပုံဧရိယာ ထားရှိပြီး၊ အောက်ဖက် ကဏ္ဍအမည်အကွက်အား ပါးလွှာကျစ်လျစ်သော အမြင့် (`px-0.5 py-0`) သို့ ပြင်ဆင်ခြင်း။
    - **Brands Card Horizontal Scroll Track:** Sub Category အောက်တည့်တည့်တွင် အမှတ်တံဆိပ် (Brands) ကတ်များအား အပေါ်ပိုင်း ၄ ပုံ ၃ ပုံ ဓာတ်ပုံအပြည့် (`h-[74%] sm:h-[76%]`) နှင့် အောက်ခြေ အမည်ပြား (`text-[8.5px] sm:text-[9.5px]`) ဖြင့် `.sf-brand-card` သီးသန့် တပ်ဆင်ခြင်း။ Brand Card နှိပ်လိုက်သည်နှင့် အောက်ဖက် Product Grid တွင် Realtime စစ်ထုတ်ပြသခြင်း။
    - **Sub-Category Buttons Ergonomics:** လက်ချောင်းဖြင့် နှိပ်ရလွယ်ကူစေရန် ခလုတ်အမြင့် `h-9 sm:h-10 px-3.5` တိုးမြှင့်ခြင်းနှင့် `rounded-none` သတ်မှတ်ခြင်း။
    - **Sticky Controls & Top Edge Flush Architecture:** ညာဘက် `<main>` container အား `pt-0` ထားရှိကာ Controls Toolbar (`sticky top-0 z-30`) အား ထိပ်စွန်းနှင့် ကွက်တိကပ်စေခြင်း။ Product Card များတွင် `relative isolate` ထည့်သွင်း၍ Scroll ဆွဲချိန်တွင် Card အတွင်းရှိ Wishlist Button များ Sticky Header ပေါ်သို့ ထိုးဖောက်မတက်စေရန် Stacking Context ကာကွယ်ထားခြင်း။
    - **Ultra-Dense 2px Rhythm:** Card တစ်ခုနှင့်တစ်ခု ကြားအကွာအဝေးကို `gap-0.5 sm:gap-1` ဖြင့် သိပ်သည်းကျစ်လျစ်စွာ ချိန်ညှိခြင်း။
    - **Single Search Bar Policy:** ရှုပ်ထွေးမှု ကင်းဝေးစေရန် browse container အပေါ်ရှိ ပိုနေသော ဒုတိယ Search Bar ကို ဖယ်ရှားပြီး Universal Header Search Bar နှင့် ဘယ်ဘက်ခြမ်း Realtime Filter စနစ်ဖြင့်သာ သန့်ရှင်းစွာ ထားရှိခြင်း။
    - **Localization & Formatting:** ကွင်းစကွင်းပိတ် အပိုများ မပါရှိသော သဘာဝကျသည့် မြန်မာစကား၊ အင်္ဂလိပ်နှင့် တရုတ် သုံးဘာသာစလုံး ပြည့်စုံစွာ ဖြည့်သွင်းထားပြီး Hardcoded "Ks" လုံးဝမပါရှိစေဘဲ Dynamic Currency စနစ်ဖြင့် တည်ဆောက်ပြီးစီး။
- [x] **Shopping Cart & Order Builder (ခြင်းတောင်းနှင့် အော်ဒါပြင်ဆင်မှု):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/orders/builder.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/orders/builder.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Production-ready အဆင့်မီ Fully Responsive Layout နှင့် UI/UX Polish ပြီးစီး။
    - **Mobile Full-Bleed 2px Padding:** မိုဘိုင်းဖုန်းများတွင် ဘေးဘက် space အလေအလွင့်မရှိစေရန် `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် 2px edge padding တပ်ဆင်ခြင်း။
    - **Desktop 12-Column Grid:** ဘယ်ဘက် ၇ ကော်လံတွင် ရွေးချယ်ထားသော ပစ္စည်းစာရင်း (Selected Products) နှင့် ညာဘက် ၅ ကော်လံတွင် Sticky Checkout Form သို့ ခွဲခြမ်းပြသခြင်း။
    - **Subtle Modern Rounded Corners Architecture (`rounded-lg sm:rounded-xl` & `rounded-md`):** မူလ `rounded-none` အစား မျက်စိပသာဒဖြစ်စေသော ခေတ်မီ နူးညံ့သပ်ရပ်သည့် Rounded Corners စံနှုန်း (`rounded-lg sm:rounded-xl` for cards, `rounded-md` for inputs/steppers) သို့ ပြောင်းလဲတပ်ဆင်ခြင်း။
    - **Official Admin Logo Synchronization:** Order Builder Header ဘားထိပ်ရှိ ဆိုင် Logo အား Storefront Logo အစား လုပ်ငန်း၏ တရားဝင် ပင်မ Admin Logo (`$storeSetting->adminLogo()`) သို့ ချိတ်ဆက်ပြသခြင်း။
    - **High-Visibility Golden Submit CTA (`sf-btn-3d-gold`):** "အော်ဒါပေးပို့မည် (Submit Order)" ခလုတ်အား အလွန်ထင်ရှားပြီး ဆွဲဆောင်မှုရှိသော ရွှေရောင် 3D Tactile Button (`sf-btn-3d-gold`) သို့ သီးသန့် အဆင့်မြှင့်တင်ခြင်း။
    - **Pure CSS 3D Tactile Push Steppers:** အရေအတွက် အတိုး/အလျှော့ (`+` / `-`) ခလုတ်များအား `.sf-btn-3d` tactile steppers များအဖြစ်လည်းကောင်း၊ Remove ခလုတ်အား `.sf-btn-3d-danger` အဖြစ်လည်းကောင်း ပြင်ဆင်ခြင်း။
    - **Dynamic Confirmation Channels with Official Brand Colors:** Viber, Telegram, Phone ချန်နယ်များအား မရွေးချယ်မီ (Default State) ကတည်းက ၎င်းတို့၏ တရားဝင် Brand Colors များဖြစ်သော `.sf-btn-3d-viber` (Viber Violet `#8b5cf6`), `.sf-btn-3d-telegram` (Telegram Sky Blue `#38bdf8`) နှင့် `.sf-btn-3d-success` (Phone Emerald) တို့ဖြင့် အမြဲတမ်း ပေါ်လွင်နေစေပြီး ရွေးချယ်ချိန်တွင် Active Ring Outline ဖြင့် ထင်ရှားစွာ Highlight ပြသခြင်း၊ သက်ဆိုင်ရာ dynamic identifier fields (Viber Phone, Telegram @username) နှင့် အပြည့်အဝ တွဲဖက်တပ်ဆင်ခြင်း။
    - **Zero Hardcoding Policy:** Static `#f85606` အရောင်များနှင့် "Ks" အသေရေးသားမှုများအားလုံး ဖယ်ရှားပြီး Dynamic Theme Tokens (`[color:var(--sf-primary)]`) နှင့် `window.formatCurrency()` သို့ ချိတ်ဆက်ခြင်း။
    - **Tri-Lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) သုံးဘာသာစလုံးအတွက် Translation Keys များ ပြည့်စုံစွာ ဖြည့်သွင်းပြီးစီး။
- [x] **Order Confirmation & Slip Attachment (အော်ဒါအတည်ပြုခြင်းနှင့် ပြေစာတင်ခြင်း):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/orders/confirmation.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/orders/confirmation.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Production-ready အဆင့်မီ Fully Responsive Layout နှင့် UI/UX Polish ပြီးစီး (Core Commerce Flow ၁၀၀% အပြည့်အဝ ပြီးမြောက်)။
    - **Success Hero Banner & 1-Tap Quick Copy:** Emerald Checkmark Hero Card နှင့် Order Number (#ORD-...) အား Clipboard သို့ 1-Tap Copy ချက်ချင်း ကူးယူနိုင်သော 3D Button တပ်ဆင်ခြင်း။
    - **4-Stage Visual Status Stepper:** အော်ဒါတင်ပြီး ➔ ဆိုင်မှ အတည်ပြုခြင်း ➔ ငွေပေးချေမှု/ထုပ်ပိုးခြင်း ➔ ပို့ဆောင်ခြင်း အဆင့် ၄ ဆင့်အား မိုဘိုင်းနှင့် ကွန်ပျူတာ အလိုက် သပ်ရပ်သော Visual Progress Stepper ဖြင့် ဖော်ပြခြင်း။
    - **Subtle Modern Rounded Corners Standard (`rounded-lg sm:rounded-xl` & `rounded-md`):** မူလ `rounded-3xl` ဖောင်းကြွကြီးများအားလုံး ဖြုတ်ပယ်ပြီး ကျစ်လျစ်သပ်ရပ်သော Subtle Rounded Corners သို့ ပြောင်းလဲခြင်း။
    - **Dynamic Currency & Strict Zero "Ks":** Hardcoded "Ks" အားလုံး ဖယ်ရှားပြီး `format_currency()` နှင့် `format_quantity()` သို့ ချိတ်ဆက်ခြင်း။
    - **Direct Shop Confirmation (Viber / Telegram / Hotline):** Brand colors အပြည့်ဖြင့် `.sf-btn-3d-viber`၊ `.sf-btn-3d-telegram`၊ `.sf-btn-3d-success` (ဖုန်းခေါ်ဆိုရန်) ခလုတ်များနှင့် အော်ဒါစာသားတစ်ခုလုံး 1-Tap ကူးယူနိုင်သော ခလုတ်များ စုံလင်စွာ တပ်ဆင်ခြင်း။
    - **Bank Transfer & QR Modal Integration:** စတိုးဆိုင်၏ ငွေလွှဲအကောင့်များနှင့် QR ကုဒ်များအား `<x-payment-qr-modal />` ဖွင့်၍ အသေးစိတ် ကြည့်ရှု/ကူးယူနိုင်သော စနစ် ချိတ်ဆက်ခြင်း။
    - **Print Slip & Back to Store Toolbar:** စာရွက်ဖြင့် ပြေစာထုတ်ယူရန် Print Media CSS အပြည့်ပါဝင်သော ပုံနှိပ်ခလုတ်နှင့် ဆက်လက်စျေးဝယ်ရန် `.sf-btn-3d-gold` ခလုတ်များ တပ်ဆင်ခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) သုံးဘာသာစလုံးအတွက် Translation Keys များ ပြည့်စုံစွာ ဖြည့်သွင်းပြီးစီး။

### ၉.၂။ Customer ဝန်ဆောင်မှုနှင့် Self-Service စာမျက်နှာများ (Customer Service & Tools)
- [x] **Service Tracking Search (ဆာဗစ်စစ် ရှာဖွေမှု ပင်မ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/service_tracking/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/service_tracking/index.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Production-ready အဆင့်မီ Fully Responsive Layout နှင့် UI/UX Polish ပြီးစီး။
    - **Mobile Full-Bleed 2px Padding:** မိုဘိုင်းဖုန်းများတွင် ဘေးဘက် space အလေအလွင့်မရှိစေရန် `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် 2px edge padding တပ်ဆင်ခြင်း။
    - **Subtle Modern Rounded Corners Standard (`rounded-lg sm:rounded-xl` & `rounded-md`):** မူလ `rounded-2xl` အဝိုက်ကြီးများ ဖယ်ရှားပြီး ကျစ်လျစ်သပ်ရပ်သော Subtle Rounded Corners စံနှုန်းသို့ ပြောင်းလဲခြင်း။
    - **Interactive Search Helper Pills:** ဖောက်သည်များ ရှာဖွေရ လွယ်ကူစေရန် ဘောင်အောက်တွင် `ဘောက်ချာနံပါတ် (V-...)`၊ `ဂျော့နံပါတ် (SVC-...)`၊ `ဖုန်းနံပါတ်` စသည့် 1-Tap Quick Fill & Submit Pills များ တပ်ဆင်ခြင်း။
    - **Pure CSS 3D Tactile Search Button:** အစိမ်းရောင် `.sf-btn-3d-success` Tactile Button ဖြင့် ရှာဖွေမှုအား ထင်ရှားစွာ ခလုတ်နှိပ်ခံစားမှု ပေးစွမ်းခြင်း။
    - **3-Feature Trust Grid Cards:** Live Status Updates၊ Genuine Parts Assurance နှင့် Technician Support စသည့် အားသာချက်ကတ်များအား 3D Bevel Card စတိုင်လ်ဖြင့် ပြသခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) သုံးဘာသာစလုံးအတွက် Translation Keys များ ပြည့်စုံစွာ ဖြည့်သွင်းပြီးစီး။
- [x] **Service Tracking Status Stepper & A5 Slip Download (ပြင်ဆင်မှု အခြေအနေ အသေးစိတ်နှင့် ပြေစာ PDF ဖိုင်သိမ်းဆည်းမှု):**
  - **ဖိုင်လမ်းကြောင်းများ:**
    - [`resources/views/storefront/service_tracking/show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/service_tracking/show.blade.php)
    - [`resources/views/storefront/service_tracking/print.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/service_tracking/print.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Production-ready အဆင့်မီ 5-Stage Animated Progress Stepper၊ A5 Preview Modal နှင့် Computer/Phone ထဲသို့ တိုက်ရိုက် PDF ဖိုင်သိမ်းဆည်းနိုင်သော စနစ် ပြီးစီး။
    - **Top Actions Toolbar:** ရှာဖွေမှု စာမျက်နှာသို့ ပြန်သွားရန် ခလုတ်၊ စာရွက်ဖြင့် ပြေစာထုတ်ယူရန် Print Slip (`window.print`) ခလုတ်၊ ခြေရာခံ Tracking Link အား Clipboard သို့ 1-Tap ကူးယူနိုင်သော `.sf-btn-3d active` Copy Link Button နှင့် A5 Slip Preview Modal ဖွင့်ရန် `📄 ပြေစာ အစမ်းကြည့်/ထုတ်မည်` ခလုတ်များ တပ်ဆင်ခြင်း။
    - **A5 Sheet Proportional Service Slip Preview Modal:** ဖောက်သည်များ ပြေစာကို အွန်လိုင်းမှ ကြည့်ရှုစစ်ဆေးနိုင်ရန် Root `@stack('modals')` တွင် `z-[100]` ဖြင့် Sticky Header များ၏ အပေါ်၌ အစစ်အမှန် A5 စာရွက်အချိုးအစား (`max-w-[480px]`, `1:1.414` ratio) ဖြင့် ပေါ်ထွက်လာသော သပ်ရပ်ကြည်လင်သည့် Dialog Box တပ်ဆင်ခြင်း။
    - **Direct PDF File Download Button (`📄 PDF ဖိုင်သိမ်းမည်`):** မူလ ရိုးရိုးပုံနှိပ်ရန် (Print) ခလုတ်နေရာတွင် ကွန်ပြူတာ သို့မဟုတ် မိုဘိုင်းဖုန်းထဲသို့ PDF ပြေစာဖိုင်ကို ချက်ချင်း တိုက်ရိုက်ဒေါင်းလုဒ်ဆွဲ သိမ်းဆည်းနိုင်သော အစိမ်းရောင် 3D ခလုတ် (`.sf-btn-3d-success`) အဖြစ် အဆင့်မြှင့်တင်ခြင်း။
    - **Dedicated Printable & Downloadable Service Slip Route (`/track/service/{token}/print`):** Tailwind CSS v4 ၏ `oklch` color function parser limit များကြောင့် ပျက်စီးခြင်းမရှိစေဘဲ သီးသန့် Scoped CSS နှင့် `html2pdf.bundle.min.js` ပါဝင်သော Public Slip View Route အသစ် တည်ဆောက်ထားရှိခြင်း (58mm, 80mm, A5, A4 Paper Size Switcher, Instant PDF Generation, Direct Printer Hook, QR Code Scanner, Customer/Technician Signatures အပြည့်အစုံ ပါဝင်)။
    - **5-Stage Localized Visual Progress Stepper:** `လက်ခံရရှိ` (Received) ➔ `စစ်ဆေးဆဲ` (Diagnosing) ➔ `ပြင်ဆင်နေဆဲ` (In Repair - with Active Stage Pulse Animation) ➔ `ပြင်ဆင်ပြီးစီး` (Ready) ➔ `ပေးအပ်ပြီး` (Delivered) စသည့် စက်ပြင်လုပ်ငန်းစဉ် ၅ ဆင့်အား အဆင့်ဆင့် Visual Stepper ဖြင့် ဖော်ပြခြင်း။
    - **Device & Diagnosis Information Card:** ပစ္စည်းအမျိုးအစား (Brand/Model/Serial/IMEI)၊ ဖောက်သည်ပြဿနာနှင့် ကျွမ်းကျင်ပညာရှင်၏ စစ်ဆေးချက်မှတ်စု (Technician Diagnosis) တို့အား စနစ်တကျ ရှင်းလင်းစွာ ခွဲခြမ်းဖော်ပြခြင်း။
    - **Parts & Services Line Items Table:** လဲလှယ်သော ပစ္စည်းများနှင့် ဝန်ဆောင်မှုစရိတ်များအား သန့်ရှင်းသော အရေအတွက် (`format_quantity()`) နှင့် Dynamic Currency (`format_currency()`, Zero "Ks") ဖြင့် ဇယားဖွဲ့ပြသခြင်း။
    - **Timeline History with Badges:** ပြင်ဆင်မှု အဆင့်ဆင့် ပြောင်းလဲခဲ့သော ရက်စွဲ၊ အချိန်နှင့် တာဝန်ခံမှတ်စုများအား အချိန်နှင့်တပြေးညီ Timeline စနစ်ဖြင့် ဖော်ပြခြင်း။
    - **Financial Summary & Soft Highlight:** စုစုပေါင်းကုန်ကျစရိတ်၊ ကြိုတင်ပေးသွင်းငွေ (Deposit) နှင့် ကျန်ငွေ (Outstanding Balance) တို့အား ပေါ်လွင်သော Soft Highlight ဖြင့် ရှင်းလင်းစွာ တွက်ချက်ပြသခြင်း။
    - **Official Brand Direct Contact Channels:** ကျွမ်းကျင်ပညာရှင် သို့မဟုတ် ဆိုင်သို့ အလွယ်တကူ ဆက်သွယ်နိုင်ရန် တရားဝင် Brand Colors များဖြစ်သော `.sf-btn-3d-viber`၊ `.sf-btn-3d-telegram` နှင့် `.sf-btn-3d-success` (ဖုန်းခေါ်ဆိုရန်) ခလုတ်များ စုံလင်စွာ တပ်ဆင်ခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) သုံးဘာသာစလုံးအတွက် Translation Keys များ (`track_service_save_pdf`, `track_service_generating_pdf` စသည်) အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။
- [x] **Glass / Screen Protector Finder (မှန်မကွဲ အလွယ်ရှာစနစ်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/glass_finder/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/glass_finder/index.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Production-ready အဆင့်မီ Brand/Model Cascading Dropdown, Real-time Compatible Models Matrix နှင့် Quick Buy 3D Button တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **Brand/Model Cascading Dropdown Toolbar:** ဖုန်း Brand ရွေးချယ်လိုက်သည်နှင့် သက်ဆိုင်ရာ Model များ ချက်ချင်း အလိုအလျောက် ရွေးချယ်နိုင်သော Dynamic Cascading Dropdown နှင့် အလွယ်တကူ စာရိုက်ရှာဖွေနိုင်သည့် Smart Search Input တပ်ဆင်ခြင်း။
    - **Fast-Tap Horizontal Brand Chips:** လက်မတစ်ချက်နှိပ်ရုံဖြင့် Brand အလိုက် ကူးပြောင်းကြည့်ရှုနိုင်သော `.sf-btn-3d` Horizontal Touch Chips များ တပ်ဆင်ခြင်း။
    - **3-Step Visual Infographic Guide:** အဆင့် ၁ (Brand ရွေးပါ) ➔ အဆင့် ၂ (Model ရွေးပါ) ➔ အဆင့် ၃ (မှန်ကုဒ်တွေ့ရှိမည်) အဆင့် ၃ ဆင့် လမ်းညွှန်ချက်ကတ်များ ထည့်သွင်းခြင်း။
    - **Cross-Compatible Models Matrix:** Glass Code တစ်ခုချင်းစီအောက်တွင် တွဲဖက်အသုံးပြုနိုင်သည့် ဖုန်းမော်ဒယ်များအားಲ್ಲုံးကို Brand Tag နှင့် In-Stock Badge အပြည့်အစုံပါဝင်သော Chips များဖြင့် ပေါ်လွင်စွာ ခွဲခြမ်းဖော်ပြခြင်း။
    - **Quick Buy 3D CTA (`sf-btn-3d-gold`):** "🛒 ခြင်းတောင်းထဲထည့်မည်" ခလုတ်အား ရွှေရောင် 3D Button ဖြင့် တပ်ဆင်ထားပြီး နှိပ်လိုက်သည်နှင့် Cart Qty Badge အား အချိန်နှင့်တပြေးညီ Counter တိုးမြှင့်ဖော်ပြပေးခြင်း။
    - **Dual View Modes (List / Table Switcher):** မိုဘိုင်းနှင့် ကွန်ပျူတာ အသုံးပြုသူများ အကြိုက်တွေ့စေမည့် ကတ်စတိုင်လ် List View (`☰`) နှင့် အချက်အလက်များပြားစွာ ယှဉ်ကြည့်နိုင်သည့် Table View (`▦`) တပ်ဆင်ခြင်း။
    - **Direct Shop Inquiries:** Viber (`.sf-btn-3d-viber`) နှင့် Telegram (`.sf-btn-3d-telegram`) တို့ဖြင့် သက်ဆိုင်ရာ Glass Code ကို pre-filled စာသားဖြင့် တိုက်ရိုက်မေးမြန်းနိုင်စေခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) သုံးဘာသာစလုံးအတွက် Translation Keys များ ပြည့်စုံစွာ ဖြည့်သွင်းပြီးစီး။
- [x] **How To Order Guide (စျေးဝယ်နည်း လမ်းညွှန်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/how_to_order/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/how_to_order/index.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Visual Infographic Steps, Payment Transfer Instructions, Bus Gate Delivery FAQ, 3D Accordion Expanders တို့ဖြင့် ပြည့်စုံစွာ အဆင့်မြှင့်တင်ပြီးစီး။
    - **Visual Infographic Steps:** အဆင့် ၁ မှ ၅ အထိ လှပသော နံပါတ်စဉ် 3D Badge (`.sf-btn-3d active`) နှင့် အဆင့်လိုက် ညွှန်ပြချက်များဖြင့် ရှင်းလင်းစွာ ဖော်ပြခြင်း။
    - **Payment Transfer Instructions Box:** KPay, WavePay, Mobile Banking ငွေလွှဲနည်း၊ Screenshot ရိုက်သိမ်းနည်း၊ Viber သို့ ပို့ဆောင်အတည်ပြုနည်း အဆင့် (၃) ဆင့် လမ်းညွှန်ချက်။
    - **Bus Gate & Delivery Guide Box:** အောင်မင်္ဂလာ/ဒဂုံဧရာ/မန္တလေး အဝေးပြေး ကားဂိတ်ပို့ခ၊ တန်ဆာခ၊ ကားဂိတ်တင်ဖြတ်ပိုင်း (Voucher) Viber သို့ ပေးပို့မှု အချက်အလက်များ။
    - **3D Accordion FAQ Expanders:** Alpine.js ဖြင့် ချောမွေ့စွာ ဖွင့်/ပိတ်နိုင်သော အမေး/အဖြေ (၅) ခု ပါဝင်သည့် 3D Animated Accordion စနစ် တပ်ဆင်ခြင်း။
    - **Direct Shop Inquiries & Official Brand 3D Buttons:** Viber (`.sf-btn-3d-viber` ခရမ်းရောင်)၊ Telegram (`.sf-btn-3d-telegram` အပြာရောင်) နှင့် ဖုန်းခေါ်ဆိုရန် (`.sf-btn-3d-success` အစိမ်းရောင်) တရားဝင် Brand Colors များဖြင့် တပ်ဆင်ထားပြီး၊ ခလုတ်ပေါ်တွင် ဖုန်းနံပါတ်စာသား ရှည်လျားစွာ မပေါ်စေဘဲ ကလစ်နှိပ်မှ ဖုန်းခေါ် App ထဲသို့ နံပါတ် အလိုအလျောက် ရောက်ရှိစေမည့် စံနှုန်း တပ်ဆင်ခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။
- [x] **Wholesale Registration (လက်ကားဖောက်သည် လျှောက်ထားလွှာ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/wholesale/apply.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/wholesale/apply.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ Clean Shop Verification Form, Wholesale Benefits Highlights, Live Application Status Box, 3D Submit Button တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **Wholesale Partner Program Hero Header:** 3D Eyebrow Badge (`💼 Wholesale Partner Program`) နှင့် ခေါင်းစဉ်/စာသား ရှင်းလင်းချက်များ။
    - **Wholesale Benefits Highlights:** သီးသန့် လက်ကားစျေးနှုန်းများ၊ ဦးစားပေး ကားဂိတ်တင်ပို့မှု၊ သီးသန့် မန်နေဂျာနှင့် တိုက်ရိုက်ဆွေးနွေးမှု အကျိုးကျေးဇူး ၃ ရပ် ကတ်များ တပ်ဆင်ခြင်း။
    - **Live Application Status Indicator:** Pending (ပယင်းရောင် Pulse Badge)၊ Approved (စိမ်းရောင် VIP Badge)၊ Rejected (အနီရောင် Badge နှင့် Admin Note) စသည်ဖြင့် Live Status ဖော်ပြချက်။
    - **Clean Shop Verification Form & 3D Submit CTA:** ဆိုင်အမည်၊ ဖုန်းနံပါတ်၊ တည်နေရာလိပ်စာနှင့် ပစ္စည်းအမျိုးအစား မှတ်စု Form နှင့် `.sf-btn-3d-primary` 3D Submit Button။
    - **Guest Safety & Direct Partner Hotline:** အကောင့်မဝင်ရသေးသူများအတွက် Customer Login အသိပေးကတ်နှင့် Viber, Telegram, Direct Call တရားဝင် Brand Colors ခလုတ်များ တပ်ဆင်ခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။
- [x] **POS-Only Storefront Notice (စတိုးဆိုင်ပိတ် သို့မဟုတ် POS သီးသန့် အသိပေးချက်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/pos_only.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/pos_only.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ Friendly In-Store POS Notice Badge, Direct Store Contact Hotline (3D Viber, Telegram, Phone Buttons), Store Working Hours & Address Cards, Google Maps Directions Button, POS Sale Redirect Button (`.sf-btn-3d-primary`) တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **In-Store / POS Only Badge:** ခေတ်မီ 3D Alert Eyebrow Badge (`🏬 ဆိုင်တွင်သာ လူကိုယ်တိုင် လာရောက်ဝယ်ယူနိုင်ပါသည် (In-Store / POS Only)`) တပ်ဆင်ခြင်း။
    - **Store Working Hours & Physical Address Cards:** ဆိုင်ဖွင့်ချိန်၊ ပိတ်ရက်နှင့် တည်နေရာလိပ်စာတို့ကို ကတ်သန့်သန့်ဖြင့် ရှင်းလင်းစွာ ဖော်ပြခြင်း။
    - **3D Hotline Action Suite:** တရားဝင် Brand Colors များဖြင့် ဖုန်းခေါ်ဆိုရန် (`.sf-btn-3d-success`)၊ Viber (`.sf-btn-3d-viber`)၊ Telegram (`.sf-btn-3d-telegram`) နှင့် Google Maps လမ်းညွှန်ချက်ရယူရန် (`.sf-btn-3d`) ခလုတ်များ။
    - **Staff Direct POS Access:** ဆိုင်ဝန်ထမ်းများအတွက် POS Counter သို့ တိုက်ရိုက်ဝင်ရောက်နိုင်သော `.sf-btn-3d-primary` ခလုတ်။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။

- [x] **Customer Profile Dashboard (အကောင့် ပင်မမျက်နှာပြင်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/customer/account/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/customer/account/index.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ Retail Customer Wholesale CTA, Conditional Visibility, Live Status Indicator တို့ဖြင့် အဆင့်မြှင့်တင်ပြီးစီး။
    - **Wholesale Partner Invitation Banner:** လက်လီဖောက်သည် (Retail Customer) များအတွက် လက်ကားဝယ်ယူခွင့် အကျိုးကျေးဇူးဖော်ပြချက်နှင့် `.sf-btn-3d-primary` "လက်ကား လျှောက်ထားရန်" ခလုတ် တပ်ဆင်ခြင်း။
    - **Quick Actions Compact 3D Tactile Buttons (2-Columns on Mobile):** Mobile View တွင် အံဝင်ခွင်ကျဖြစ်စေသော ၂ ကော်လံ စနစ်ဖြင့် အော်ဒါမှတ်တမ်း (Orders)၊ သိမ်းဆည်းထားသည်များ (Favorites)၊ ရွှေဝါရောင် 3D Button (`.sf-btn-3d-gold`) ပါဝင်သည့် လက်ကားလျှောက်ထားရန် (Wholesale) နှင့် အနီရောင် 3D Button (`.sf-btn-3d-danger`) ပါဝင်သည့် အကောင့်မှထွက်ရန် (Log Out) ခလုတ်များကို tactile depth အပြည့်ဖြင့် တပ်ဆင်ခြင်း။
    - **Conditional Wholesale Visibility:** လက်ကားဖောက်သည် (Approved Wholesale Customer) များအတွက် အဆိုပါ လျှောက်ထားရန် ခလုတ်နှင့် Banner အား အလိုအလျောက် ဖုံးကွယ်ထားခြင်း။
    - **Web Push Preference & Floating Bell Sync:** Notification Toggle switch အား Event Listener ချိတ်ဆက်ပြီး၊ Toggle ပိတ်ထားချိန်တွင် Floating 🔔 Bell icon အား မျက်နှာပြင်မှ လုံးဝ ဖုံးကွယ်ထားစေခြင်း (`style="display: none;"` / `!hidden`)၊ Toggle အခြေအနေအား မြန်မာစာသား "အသိပေးချက်များ ပိတ်ထားပါသည်" ဖြင့် ချက်ချင်း ပြောင်းလဲဖော်ပြစေခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။
- [x] **Customer Order History (မှာယူမှု မှတ်တမ်းများ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/customer/account/orders.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/customer/account/orders.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ Status Filter Tabs, Search Bar, 1-Tap Reorder, Dynamic Currency, Card/Table Switcher တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **Ultra-Dense Mobile Layout:** `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် Mobile View တွင် Full-bleed အံဝင်ခွင်ကျ ပြသခြင်း။
    - **Live Status Filter Pills:** အားလုံး (All), ဆက်သွယ်ရန် စောင့်ဆိုင်းဆဲ (Pending Contact), အတည်ပြုပြီး (Confirmed), ပို့ဆောင်ပြီး (Delivered), ပယ်ဖျက်ပြီး (Cancelled) အဆင့်များအလိုက် Badge Count များဖြင့် စစ်ထုတ်နိုင်ခြင်း။
    - **Search Toolbar & View Switcher:** အော်ဒါအမှတ်၊ ဖုန်းနံပါတ် သို့မဟုတ် ပစ္စည်းအမည်ဖြင့် ရှာဖွေနိုင်ပြီး Cards View နှင့် Table View အကြား စိတ်ကြိုက် ပြောင်းလဲကြည့်ရှုနိုင်ခြင်း (localStorage မှတ်သားမှု ပါဝင်)။
    - **1-Tap Reorder (၁ ချက်နှိပ် ပြန်လည်မှာယူမည်):** ယခင်မှာယူခဲ့သည့် ပစ္စည်းများကို Client-side Alpine Store (`orderBuilder`) ထဲသို့ တိုက်ရိုက်ထည့်သွင်းပေးပြီး Order Builder သို့ ချက်ချင်း လမ်းညွှန်ပေးသည့် `.sf-btn-3d-primary` ခလုတ်။
    - **Dynamic Currency & Agreed Price Highlighting:** Hardcoded `Ks` လုံးဝမပါဝင်ဘဲ `format_currency()` Helper ဖြင့် စနစ်တကျ ပြသခြင်း၊ ဆိုင်မှ ညှိနှိုင်းလျှော့ပေါ့ပေးထားသော Agreed Price ရှိပါက မူလစျေးနှုန်းအား Strikethrough ဖြင့် နှိုင်းယှဉ်ပြသခြင်း။
    - **Empty State & Pagination:** အော်ဒါမှတ်တမ်း မရှိသေးချိန် သို့မဟုတ် စစ်ထုတ်မှု ရလဒ်မရှိချိန်တွင် ကုန်ပစ္စည်းများ ကြည့်ရှုမည့် 3D CTA နှင့် Laravel Query String-preserved Pagination စနစ်။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။
- [x] **Customer Order Detail & Invoice (အော်ဒါအသေးစိတ်နှင့် ပြေစာ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/customer/account/order_show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/customer/account/order_show.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ 4-Stage Live Delivery Status Tracking, Simulated 80mm/58mm Thermal Receipt Modal, Pure ESC/POS Print View, 1-Tap Reorder, Direct Store Hotline 3D Buttons တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **Ultra-Dense Layout & Header:** `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် Mobile View တွင် အံဝင်ခွင်ကျ ပြသခြင်း၊ အော်ဒါအမှတ် `#ORD-XXXX` အား ကလစ်တစ်ချက်ဖြင့် Copy ကူးယူနိုင်သော 3D Action ခလုတ် ပါဝင်ခြင်း။
    - **Live Order Delivery Stepper:** အော်ဒါတင်ပြီး (Order Placed) -> ဆိုင်မှ အတည်ပြုဆဲ (Under Review) -> ငွေပေးချေမှု/ထုပ်ပိုးခြင်း (Confirmed/Payment) -> ပို့ဆောင်ခြင်း (Delivery) အဆင့် (၄) ဆင့်အား Live Status အရောင်များနှင့် လှပစွာ ခြေရာခံပြသခြင်း (ပယ်ဖျက်ထားပါက အနီရောင် Notice ဖြင့် အသိပေးခြင်း)။
    - **Simulated Thermal Receipt Modal & Size Switcher:** 80mm နှင့် 58mm Thermal Receipt စက္ကူဆိုဒ်များ အလိုက် ကြည့်ရှုနိုင်သည့် Dialog Modal ပါဝင်ပြီး အမှန်တကယ် POS Printer မှ ထွက်လာသကဲ့သို့ Dashed Separators, Monospace Typography, Subtotal, Agreed Total, Payment Status, Footer Greeting တို့ဖြင့် ပြသပေးခြင်း။
    - **Direct ESC/POS `@media print` View:** Browser Print နှိပ်လိုက်ချိန်တွင် Navbar၊ Header၊ Footer များကို ဖုံးကွယ်၍ Thermal Printer အတိုင်း Monochrome သန့်ရှင်းစွာ ပရင့်ထုတ်ပေးနိုင်ခြင်း။
    - **1-Tap Reorder (၁ ချက်နှိပ် ပြန်လည်မှာယူမည်):** ယခုအော်ဒါထဲမှ ပစ္စည်းများအားလုံးကို `$store.orderBuilder` ထဲသို့ ချက်ချင်း ထည့်သွင်းပေးပြီး Order Builder သို့ ဦးတည်ပေးသည့် `.sf-btn-3d-gold` ခလုတ်။
    - **Direct Hotline Official Brand Buttons:** ဆိုင်သို့ တိုက်ရိုက်မေးမြန်းနိုင်သည့် Viber (`.sf-btn-3d-viber`)၊ Telegram (`.sf-btn-3d-telegram`) နှင့် ဖုန်းခေါ်ဆိုရန် (`.sf-btn-3d-success`) 3D ခလုတ်များ။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။
- [x] **Customer Wishlist (နှစ်သက်သော ပစ္စည်းများ စာရင်း):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/customer/account/favorites.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/customer/account/favorites.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ Compact Product Cards, Move to Cart 3D Button, Remove 3D Danger Button, Stock Availability Badges, Client/Cloud Dual-Sync တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **Ultra-Dense Mobile Layout:** `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် Mobile View တွင် Full-bleed အံဝင်ခွင်ကျ ပြသခြင်း။
    - **Compact Product Grid:** ဖုန်းမျက်နှာပြင်တွင် ၂ ကော်လံ (`grid-cols-2`)၊ Tablet တွင် ၃ ကော်လံ (`sm:grid-cols-3`) နှင့် Desktop တွင် ၄ ကော်လံ (`lg:grid-cols-4`) စနစ်ကျသော Aspect-square image container နှင့် Brand Letter Fallback Avatar ဖြင့် ပြသပေးခြင်း။
    - **Stock Availability Badges:** Server စတော့လက်ကျန် စစ်ဆေးမှုနှင့်အညီ စတော့ရှိပါက `● ပစ္စည်းရှိ (In Stock)` (Emerald Badge) နှင့် စတော့ကုန်သွားပါက `● ပစ္စည်းပြတ် (Out of Stock)` (Rose Badge) ဖြင့် ထင်ရှားစွာ ခွဲခြားဖော်ပြခြင်း။
    - **Remove 3D Button (`.sf-btn-3d-danger`):** ကတ်တစ်ခုချင်းစီ၏ ထိပ်ညာဘက်တွင် အနီရောင် 3D Tactile အမှိုက်ပုံး ခလုတ်ဖြင့် Server Favorite မှရော Local Storage Favorite မှပါ AJAX/DOM အလိုအလျောက် ပယ်ဖျက်နိုင်ခြင်း။
    - **Move to Cart 3D Button (`.sf-btn-3d-primary`):** ကတ်အောက်ခြေတွင် စျေးဝယ်ခြင်းတောင်းထဲသို့ ချက်ချင်းထည့်သွင်းနိုင်ပြီး Cart ထဲရှိ အရေအတွက် badge အား real-time live တိုးစေမည့် 3D ခလုတ် တပ်ဆင်ခြင်း။
    - **Dual Cloud & Local Storage Sync:** အကောင့်ဝင်ထားသူများအတွက် Cloud Database နှင့် Browser Local Storage နှစ်မျိုးလုံးတွင် သိမ်းဆည်းထားသော အကြိုက်ဆုံး ပစ္စည်းများကို အဆင်ပြေစွာ ပေါင်းစပ်ဖော်ပြပေးခြင်း။
    - **Empty State & Trust Badges:** အကြိုက်ဆုံးပစ္စည်း မရှိသေးချိန်တွင် ပစ္စည်းများ ရှာဖွေနိုင်သည့် 3D Browse ခလုတ်များနှင့် 100% Authentic, COD, Fast Delivery အစရှိသော စတိုးဆိုင် ယုံကြည်စိတ်ချရမှု အာမခံ Badge ၄ ခု တပ်ဆင်ခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။

### ၉.၄။ သတင်း၊ အကြောင်းအရာနှင့် CMS စာမျက်နှာများ (Content & CMS)
- [x] **Tech Blog / News List (သတင်းဆောင်းပါးများ စာရင်း):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/blog/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/blog/index.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ Modern Card Grid, Read Time Badge, Featured Post Hero Card, Category Filter Pills, Live Search Bar တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **Ultra-Dense Mobile Layout:** `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် မိုဘိုင်းမျက်နှာပြင်တွင် ဘေးဘောင်ကျဉ်းကျဉ်းဖြင့် နေရာလွတ်မဖြုန်းဘဲ အံဝင်ခွင်ကျ ပြသခြင်း။
    - **Top Header & Search Toolbar:** စတိုးဆိုင် Logo, ဆောင်းပါးအရေအတွက် 3D Badge, Keyword Search Input Form, Live Count ပါဝင်သည့် Cart 3D Button (`.sf-btn-3d-gold`) နှင့် Products Link တို့ဖြင့် စနစ်တကျ ဖွဲ့စည်းထားခြင်း။
    - **3D Category Filter Pills:** ကဏ္ဍအားလုံး (📚 All) အပြင် Mobile Guide, Accessories Guide, CCTV Guide, Computer Guide, Network Guide, Fashion Guide, Tips & Tricks စသည့် ကဏ္ဍခွဲများအလိုက် Emoji များဖြင့် တပ်ဆင်ထားပြီး ရွေးချယ်ထားသော ကဏ္ဍအား Primary 3D Highlight ဖြင့် ပြသခြင်း၊ စစ်ထုတ်မှုရှိပါက "✕ Clear All Filters" ခလုတ် ပေါ်ထွက်လာခြင်း။
    - **Featured Post Hero Card:** Page 1 တွင် အသစ်ဆုံး အထူးဆောင်းပါးအား အထူးပြုပြသသည့် ကြီးမားလှပသော Hero Card (⭐ Featured Story Badge, Category Badge, Published Date, Read Time Badge `⏱️ X min read`, Title, Excerpt နှင့် `.sf-btn-3d-primary` Read More CTA)။
    - **Modern Responsive Articles Card Grid:** 16:10 အချိုး အကြည်ဓာတ်ပုံ / fallback gradient container, Date badge, Category badge, Read time badge, Title (2 lines clamp), Excerpt (2 lines clamp) နှင့် 3D Read More Button တို့ဖြင့် Responsive ပြသခြင်း (Mobile တွင် 1 column, Tablet တွင် 2 columns, Desktop တွင် 3 columns)။
    - **Empty State & Trust Badges:** ဆောင်းပါးရှာမတွေ့ပါက သန့်ရှင်းသော မက်ဆေ့ခ်ျနှင့် Clear Filter / Browse Products ခလုတ်များ၊ အောက်ခြေတွင် Authentic, Expert Guidance, Fast Delivery, Tech Support စတိုးဆိုင် အာမခံ Badge ၄ ခု တပ်ဆင်ခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။
- [x] **Blog Post Detail (ဆောင်းပါး အသေးစိတ်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/blog/show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/blog/show.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ Typography Readability (`font-myanmar leading-relaxed`), Social Share 3D Toolbar, Shop Consultation Hotline Banner, Prev/Next 3D Navigation, Related Articles Grid တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **Ultra-Dense Mobile Layout:** `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် မိုဘိုင်းမျက်နှာပြင်တွင် ဘေးဘောင်ကျဉ်းကျဉ်းဖြင့် အံဝင်ခွင်ကျ ပြသခြင်း။
    - **Top Navigation Bar:** `← Back to blog` 3D ခလုတ်၊ Category Badge (`.sf-btn-3d active`)၊ Live Cart Count ပါဝင်သည့် Cart 3D Gold Button (`.sf-btn-3d-gold`) နှင့် Products Button များ တပ်ဆင်ခြင်း။
    - **Typography Readability & Excerpt Callout:** မြန်မာစာဖတ်ရှုရ လွယ်ကူစေသော `font-myanmar leading-relaxed sm:leading-loose` စာလုံးစတိုင်၊ ခေါင်းစဉ် (H1)၊ အကျဉ်းချုပ် Excerpt Callout Box (Sky border-l-4)၊ စာပိုဒ်ခွဲများ၊ Tags (#tag) စနစ်တကျ ပြသခြင်း။
    - **Social Share 3D Toolbar:** 1-Tap Copy Link 3D Button (နှိပ်လိုက်ပါက instant `✓ Copied!` တုံ့ပြန်မှု ပါဝင်)၊ Facebook (`.sf-btn-3d-facebook`)၊ Viber (`.sf-btn-3d-viber`)၊ Telegram (`.sf-btn-3d-telegram`) တရားဝင် Brand Colors များဖြင့် ဖွဲ့စည်းထားသော 3D Share ခလုတ်များ။
    - **Shop Consultation Hotline Banner:** ဆောင်းပါးဖတ်ရှုပြီးနောက် ဆိုင်နှင့် တိုက်ရိုက် ဆွေးနွေးမေးမြန်းနိုင်သော Viber (`.sf-btn-3d-viber`)၊ Telegram (`.sf-btn-3d-telegram`)၊ ဖုန်းခေါ်ဆိုရန် (`.sf-btn-3d-success`) နှင့် ကုန်ပစ္စည်းများ ကြည့်ရှုဝယ်ယူရန် (`.sf-btn-3d-primary`) 3D ခလုတ်များ။
    - **Prev / Next Article 3D Navigation Cards:** ယခင်ဆောင်းပါးနှင့် နောက်ဆောင်းပါးများသို့ အလွယ်တကူ ကူးပြောင်းဖတ်ရှုနိုင်သော 3D Navigation Card များ။
    - **Related Articles 3D Grid:** ကဏ္ဍတူ သို့မဟုတ် ဆက်စပ်ဆောင်းပါးများကို Responsive Card Grid စနစ်ဖြင့် ဖော်ပြခြင်း။
    - **Google Rich Results Schema.org:** Article structured data JSON-LD အပြည့်အစုံ ပါဝင်ခြင်း။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။
- [x] **Custom CMS Pages (Terms, Privacy, About Us):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/pages/show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/pages/show.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Storefront UI/UX Standard v1.0 နှင့်အညီ Clean Markdown/HTML Rendering, Table of Contents Quick Nav, Policy Switcher Pills, Social Share 3D Toolbar, Shop Hotline Banner တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
    - **Ultra-Dense Mobile Layout:** `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် မိုဘိုင်းမျက်နှာပြင်တွင် ဘေးဘောင်ကျဉ်းကျဉ်းဖြင့် နေရာလွတ်မဖြုန်းဘဲ အံဝင်ခွင်ကျ ပြသခြင်း။
    - **Top Header Bar:** Home ခလုတ်၊ လက်ရှိ စာမျက်နှာ အိုင်ကွန်နှင့် အမည် Badge (`.sf-btn-3d active`)၊ Live Cart Count ပါဝင်သည့် Cart 3D Gold Button (`.sf-btn-3d-gold`) နှင့် Products Button များ တပ်ဆင်ခြင်း။
    - **Store CMS Policy Switcher Pills:** ဆိုင်၏ မူဝါဒစာမျက်နှာများ (ℹ️ About Us, 📜 Terms & Conditions, 🔒 Privacy Policy) အကြား ကလစ်တစ်ချက်ဖြင့် ကူးပြောင်းနိုင်သော 3D Pills Bar ပါဝင်ပြီး လက်ရှိစာမျက်နှာအား Primary 3D Highlight ဖြင့် ပြသခြင်း။
    - **Dynamic Table of Contents (TOC):** Markdown အတွင်းရှိ H2/H3 ခေါင်းစဉ်များကို Alpine.js ဖြင့် အလိုအလျောက် ရေတွက်ဖော်ထုတ်ပေးပြီး ကလစ်နှိပ်ပါက သက်ဆိုင်ရာအခန်းဆီသို့ Smooth Scroll ဖြင့် အမြန်ရောက်ရှိစေသော Expandable TOC Box ပါဝင်ခြင်း။
    - **Clean Typography Rendering:** မြန်မာစာသားများအတွက် `font-myanmar leading-relaxed sm:leading-loose` စာလုံးစတိုင်၊ ခေါင်းစဉ်ကြီး/ငယ်များ၊ အကျဉ်းချုပ် Excerpt Box (Sky border-l-4)၊ စာရင်းများ (Lists)၊ ဇယားများ (Tables) နှင့် Blockquotes များ စနစ်တကျ ပြသခြင်း။
    - **Social Share 3D Toolbar:** 1-Tap Copy Link 3D Button (`✓ Copied!` feedback ပါဝင်)၊ Facebook (`.sf-btn-3d-facebook`)၊ Viber (`.sf-btn-3d-viber`)၊ Telegram (`.sf-btn-3d-telegram`) တရားဝင် Brand Colors များဖြင့် ဖွဲ့စည်းထားသော 3D Share ခလုတ်များ။
    - **Shop Consultation Hotline Banner:** ဆိုင်ဝန်ထမ်းများနှင့် တိုက်ရိုက်ဆွေးနွေး မေးမြန်းနိုင်သော Viber (`.sf-btn-3d-viber`)၊ Telegram (`.sf-btn-3d-telegram`)၊ ဖုန်းခေါ်ဆိုရန် (`.sf-btn-3d-success`) နှင့် ကုန်ပစ္စည်းများ ကြည့်ရှုဝယ်ယူရန် (`.sf-btn-3d-primary`) 3D ခလုတ်များ။
    - **Tri-lingual Localization:** မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် (zh_CN) ၃ ဘာသာစလုံးအတွက် Translation Keys များ အပြည့်အစုံ ဖြည့်သွင်းပြီးစီး။

### ၉.၅။ အများသုံး Component များနှင့် Footer (Universal Components & Layouts)
- [x] **Storefront Base Layout (ပင်မ Layout နှင့် Header):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/layouts/storefront/app.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/layouts/storefront/app.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Dynamic Theming Tokens, 3D Bottom Nav Bar, Search Bar Integration, Cart Count Badge, Colorful 3D Action Buttons (Favorites, Language, Cart, Dark Mode, Mobile Menu) ပြီးစီး။
- [x] **Search Suggestions Dropdown (အမြန် ရှာဖွေမှု အကြံပြုချက်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/components/search-suggestions-dropdown.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/components/search-suggestions-dropdown.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Matched Keywords Highlighting (`x-html="highlight(...)"` with XSS sanitation), Product Thumbnail, Dynamic Price & Strikethrough (`format_currency`), Live Stock Badges (`in_stock` / `out_of_stock`), Full Keyboard Arrow Navigation (`ArrowUp`, `ArrowDown`, `Enter`, `Escape`), Trending Searches Chips (`.sf-btn-3d`) တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
- [x] **Direct Viber Order Modal (Viber မှာယူမှု Dialog):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/components/_viber_order_modal.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/components/_viber_order_modal.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Pre-filled Customer Phone & Delivery Address, 3D Quantity Steppers (`+` / `-`), Dynamic Subtotal & Total calculation, Send Order 3D Button (`.sf-btn-3d-viber`), Direct Phone Call (`.sf-btn-3d-success`), Viber QR Code & Contact Fallback Expandable Accordion တို့ဖြင့် အပြည့်အဝ ပြီးစီး။
- [x] **Storefront Universal Footer (စတိုးဆိုင် အောက်ခြေပိုင်း):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/components/storefront-footer.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/storefront-footer.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Store Physical Address, Working Hours & Off Days, Official Social & Direct Chat 3D Buttons (Viber `.sf-btn-3d-viber`, Telegram `.sf-btn-3d-telegram`), Payment Partners Badges (KPay, WavePay, CBPay, AYAPay, COD), Mobile Safe Area Inset Padding (`pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))]`), 3D Back-to-Top Smooth Scroll Button (`.sf-btn-3d`) တို့ဖြင့် အပြည့်အဝ ပြီးစီး။

---

## ၁၀။ လက်တွေ့ ပြင်ဆင်ပြီးစီးခဲ့သော ဖိုင်လမ်းကြောင်းများနှင့် Admin Theme Controls ချိတ်ဆက်မှု မှတ်တမ်း (Production Change Log & Admin Theme Integration Architecture)

Admin Panel ရှိ **Theme Creation & Color Customization (အပြင်အဆင်နှင့် အရောင်အသွေး ထိန်းချုပ်မှု စာမျက်နှာ)** အား အဆင့်မြှင့်တင်ရာတွင် Storefront ဘက်ခြမ်း၌ လက်တွေ့တည်ဆောက်ထားသော CSS Tokens, Button Gradients, Component File Paths နှင့် Data Bindings များကို အောက်ပါအတိုင်း တိကျစွာ ကိုးကားအသုံးပြုနိုင်ပါသည်-

---

### ၁၀.၁။ ပြင်ဆင်ခဲ့သော ဖိုင်လမ်းကြောင်းများ စာရင်း (Modified File Paths Registry)

| Component / Feature | File Path | ပြင်ဆင်ချက် အကျဉ်းချုပ် |
| :--- | :--- | :--- |
| **Storefront Base Header** | [`resources/views/layouts/storefront/app.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/layouts/storefront/app.blade.php) | Header Action Buttons (Favorites, Language, Cart, Dark Mode, Mobile Menu) များအား သီးသန့် Color-coded 3D Tactile Buttons အဖြစ် အဆင့်မြှင့်တင်ခြင်း။ |
| **Language Switcher Component** | [`resources/views/components/language-switcher.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/language-switcher.blade.php) | Storefront Header နှင့် Admin/POS များ ခွဲခြားသတ်မှတ်နိုင်ရန် `$btnClass` (`btn-class` attribute) ထည့်သွင်းပေးခြင်း။ |
| **Product Card (Compact Variant)** | [`resources/views/components/product-card-variants/compact.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/product-card-variants/compact.blade.php) | Wishlist ခလုတ်အား မနှိပ်ခင် 3D Metallic Gold Button + White Heart၊ နှိပ်ပြီးပါက 3D Crimson Red Button + White Heart သို့ ပြောင်းလဲခြင်း။ |
| **Product Card (Showcase Variant)** | [`resources/views/components/product-card-variants/showcase.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/product-card-variants/showcase.blade.php) | Showcase Grid Card ပေါ်ရှိ Favorite ခလုတ်အား 3D Gold / Red Toggle အဖြစ် အဆင့်မြှင့်တင်ခြင်း။ |
| **Product Card (List Variant)** | [`resources/views/components/product-card-list.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/product-card-list.blade.php) | List View ရှိ Favorite ခလုတ်အား 3D Gold / Red Toggle အဖြစ် ပြင်ဆင်ခြင်း။ |
| **Storefront Home Page** | [`resources/views/welcome.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/welcome.blade.php) | Hero Category Sidebar Nav Height (`max-height: 384px`) နှင့် Banner + Trust Cards ညာဘက်ခြမ်း Pixel-perfect ညီညာအောင် ချိန်ညှိခြင်း။ |
| **Product Catalog & Filter Toolbar** | [`resources/views/storefront/catalog/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/catalog/index.blade.php) | Mobile Filter Toolbar Text ကို Row တစ်တန်းတည်း ညီညာစေခြင်း၊ Desktop Sidebar Categories & Brands ခလုတ်များ `w-full` အပြည့်ထားရှိခြင်း၊ Header Count Pill အပိုများ ရှင်းထုတ်ခြင်း။ |
| **Category Browser Split View** | [`resources/views/storefront/browse/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/browse/index.blade.php) | Sharp Rectangular 3D Cards (`rounded-none`)၊ Left Rail 80% Full-bleed Image၊ Brands Horizontal Scroll Track၊ Subcategory `h-9 sm:h-10`၊ Top-0 Flush Sticky Controls နှင့် Stacking Context Isolation (`relative isolate`) တပ်ဆင်ခြင်း။ |
| **Shopping Cart & Order Builder** | [`resources/views/storefront/orders/builder.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/orders/builder.blade.php) | Mobile 2px Full-Bleed Padding (`@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')`)၊ Sharp Rectangular 3D Tiles (`rounded-none`)၊ Pure CSS 3D Tactile Steppers (`+`/`-`)၊ Dynamic Theme Tokens၊ Dynamic Currency Formatter နှင့် Tri-Lingual Localization တပ်ဆင်ခြင်း။ |
| **Product Card Component Dispatcher** | [`resources/views/components/product-card.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/product-card.blade.php) | Callers များမှ ကတ်ထောင့်ဝိုက်ခြင်း သတ်မှတ်နိုင်ရန် `:rounded` prop ထည့်သွင်းခြင်း (ဥပမာ- `:rounded="'rounded-none'"` ဖြင့် Sharp 3D Tiles ဖွဲ့စည်းနိုင်ခြင်း)။ |
| **Product Card (Compact Variant)** | [`resources/views/components/product-card-variants/compact.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/product-card-variants/compact.blade.php) | Favorite Button အရွယ်အစားအား `w-7 h-7 sm:w-8 sm:h-8` (`icon: w-3.5 h-3.5 sm:w-4 sm:h-4`) သို့ ကျစ်လျစ်စွာ ပြင်ဆင်ခြင်း၊ `:rounded` prop dynamic binding နှင့် image ပေါ် blocking ဖြစ်မှု ကာကွယ်ခြင်း။ |
| **Global Storefront CSS** | [`resources/css/app.css`](file:///d:/xmapp/htdocs/DataPOS/resources/css/app.css) | `.sf-btn-3d`, `.hero-cat-nav` နှင့် 3D Push Button Utility Classes များ တည်ဆောက်ခြင်း။ |
| **POS-Only Storefront Notice** | [`resources/views/storefront/pos_only.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/pos_only.blade.php) | In-Store POS Notice Badge၊ Store Working Hours & Physical Address Cards၊ 3D Hotline Action Suite (`.sf-btn-3d-success`, `.sf-btn-3d-viber`, `.sf-btn-3d-telegram`)၊ Google Maps Direction ခလုတ်နှင့် Staff Direct POS Access CTA တပ်ဆင်ခြင်း။ |
| **Search Suggestions Dropdown** | [`resources/views/storefront/components/search-suggestions-dropdown.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/components/search-suggestions-dropdown.blade.php) | Matched Keyword Highlighting (XSS-safe)၊ Product Thumbnail၊ Dynamic Currency Price (`format_currency`)၊ Live Stock Badges (`in_stock`/`out_of_stock`)၊ Full Arrow Keyboard Nav နှင့် 3D Trending Chips တပ်ဆင်ခြင်း။ |
| **Direct Viber Order Modal** | [`resources/views/storefront/components/_viber_order_modal.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/components/_viber_order_modal.blade.php) | Pre-filled Customer Phone/Address၊ 3D Quantity Steppers (`+`/`-`)၊ Dynamic Pricing၊ Send Order 3D Button (`.sf-btn-3d-viber`)၊ Direct Call (`.sf-btn-3d-success`)၊ Viber QR Code & Backup Contact Accordion တပ်ဆင်ခြင်း။ |
| **Storefront Universal Footer** | [`resources/views/components/storefront-footer.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/storefront-footer.blade.php) | Physical Address & Hours Cards၊ Official Social 3D Buttons (Viber/Telegram)၊ Payment Partners Badges၊ Mobile Safe Area Inset Padding (`pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))]`)၊ 3D Back-to-Top Button တပ်ဆင်ခြင်း။ |
| **Catalog Controller Suggestions** | [`app/Http/Controllers/Storefront/CatalogController.php`](file:///d:/xmapp/htdocs/DataPOS/app/Http/Controllers/Storefront/CatalogController.php) | Live search suggestions API တွင် hardcoded `Ks` ဖယ်ရှား၍ `format_currency($product->retail_price, $store)` နှင့် `format_currency($product->old_price, $store)` အသုံးပြုခြင်း။ |
| **Storefront Client JS Engine** | [`resources/js/app.js`](file:///d:/xmapp/htdocs/DataPOS/resources/js/app.js) | Alpine `searchSuggestions` component တွင် XSS-safe `highlight(text)` နှင့် `escapeHtml(str)` utility methods များ ဖြည့်စွက်ပြီး Vite compilation ပြီးစီး။ |

---

### ၁၀.၂။ Header Action Buttons Color Coding Standard (ခေါင်းစဉ်ဘား ခလုတ်များ၏ အရောင်စံနှုန်း)

Admin Theme Editor တွင် Storefront Header Bar ကို ထိန်းချုပ်ရာ၌ ခလုတ်တစ်ခုချင်းစီ၏ အရောင်စံနှုန်းများကို အောက်ပါ Class ဖွဲ့စည်းပုံအတိုင်း ချိတ်ဆက်နိုင်ပါသည်-

```
+-------------------+---------------------------------------------+------------------------------------+
| Button Name       | Light Mode Design Token                     | Dark Mode Design Token             |
+-------------------+---------------------------------------------+------------------------------------+
| 1. Favorites      | from-rose-50 to-rose-100 (Bevel: rose-300)  | from-rose-950/60 to-rose-900/40    |
| 2. Language       | from-sky-50 to-blue-100 (Bevel: sky-300)    | from-sky-950/60 to-blue-900/40     |
| 3. Order Cart     | from-emerald-50 to-teal-100 (emerald-300)   | from-emerald-950/60 to-teal-900/40 |
| 4. Theme Toggle   | from-amber-50 to-amber-100 (amber-300)      | from-amber-950/60 to-amber-900/40  |
| 5. Mobile Menu    | from-indigo-50 to-violet-100 (indigo-300)   | from-indigo-950/60 to-violet-900/40|
+-------------------+---------------------------------------------+------------------------------------+
```

- **Tactile Push Physics:** ခလုတ်တိုင်းတွင် `transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none` နှင့် `border-b-[3px]` Solid Bevel အသုံးပြုထားသည်။
- **Badge Positioning:** Counter Badge တိုင်းတွင် `absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] sm:min-w-[20px] sm:h-[20px] ring-2 ring-white dark:ring-slate-900` ဖြင့် နေရာချထားသည်။

---

### ၁၀.၃။ Product Card 3D Gold Wishlist Button Standard (ရွှေရောင် 3D ခလုတ် စံနှုန်း)

Product Card တိုင်း၏ ညာဘက်အပေါ်ထောင့်တွင် ထည့်သွင်းထားသော Favorite Push Button ၏ CSS ဖွဲ့စည်းပုံ-

1. **Unfavorited State (မနှိပ်ခင် ရွှေရောင် 3D ခလုတ်စစ်စစ်):**
   - **Background Gradient:** `bg-gradient-to-b from-amber-300 via-amber-400 to-amber-600`
   - **Borders & 3D Bevel:** `border border-amber-200 border-b-[3px] border-b-amber-700`
   - **Shadows & Highlights:** `shadow-md shadow-amber-900/30 ring-1 ring-white/70`
   - **Heart Icon:** `text-white drop-shadow-sm` (အသဲကို ရွှေရောင်မလုပ်ဘဲ အဖြူစစ်စစ်ဖြင့် ပေါ်လွင်စေသည်)

2. **Favorited State (နှိပ်ပြီးချိန် အနီရောင် 3D ခလုတ်):**
   - **Background Gradient:** `bg-gradient-to-b from-rose-500 via-rose-600 to-rose-700`
   - **Borders & 3D Bevel:** `border border-rose-300 border-b-[3px] border-b-rose-900`
   - **Shadows & Highlights:** `shadow-md shadow-rose-950/40 ring-1 ring-white/70`
   - **Heart Icon:** `text-white drop-shadow-sm`

---

### ၁၀.၄။ Admin Theme Customizer နှင့် Database ချိတ်ဆက်မှု လမ်းညွှန် (Theme Config Architecture)

Admin ဖက်ခြမ်းတွင် ဆိုင်ရှင် သို့မဟုတ် Admin အနေဖြင့် Storefront Color များကို ဖန်တီးထိန်းချုပ်နိုင်မည့် Database Model နှင့် Controller ချိတ်ဆက်မှုများ-

1. **Database Table:** `storefront_settings`
   - `theme_preset` (string) — e.g. `'midnight'`, `'emerald_sleek'`, `'royal_violet'`, `'amber_gold'`
   - `theme_primary_color` (varchar 7) — e.g. `#0ea5e9`
   - `theme_accent_color` (varchar 7) — e.g. `#7c3aed`
   - `theme_header_bg` (varchar 7) — e.g. `#ffffff`
   - `theme_body_bg` (varchar 7) — e.g. `#f8fafc`
   - `theme_glow_style` (string) — e.g. `'subtle'`, `'vivid'`, `'none'`
   - `theme_dark_mode` (string) — `'auto'`, `'light'`, `'dark'`

2. **Draft Preview Pipeline (Theme Customizer Live Preview):**
   - File: `app/Themes/ThemeContext.php`
   - Class: `app/Themes/ThemeConfig.php`
   - Admin ဘက်ခြမ်းမှ Theme အသစ် စမ်းသပ်ချိန်တွင် `storefront_settings` ကို တိုက်ရိုက် overwrite မလုပ်ဘဲ Session-scoped `ThemeConfig` draft ကို inject ပြုလုပ်၍ `layouts/storefront/app.blade.php` သို့ CSS Variables (`var(--sf-primary)`, `var(--sf-accent)`) အဖြစ် Pass ပေးသည်။

3. **Admin Controller Reference:**
   - `app/Http/Controllers/Admin/StorefrontSettingsController.php`
   - Admin Theme Settings Form တွင် Primary Color ရွေးချယ်မှုနှင့်အတူ **3D Bevel Color** (`--sf-primary-bevel`) အား `color-mix(in srgb, var(--sf-primary) 70%, #000000)` သို့မဟုတ် PHP Bcmath / Hex Color Calculation ဖြင့် အလိုအလျောက် တွက်ချက်ဖန်တီးပေးရန် လိုအပ်ပါသည်။

---

### ၁၀.၅။ Product Details & Variant Specs 3D Button Suite Standard (ပစ္စည်းအသေးစိတ် 3D ခလုတ် စံနှုန်း)

Product Details Page (`resources/views/storefront/catalog/show.blade.php`) တွင် သုံးစွဲသူ၏ စိတ်ကျေနပ်မှုနှင့် ဝယ်ယူမှုနှုန်း (Conversion Rate) ကို အမြင့်ဆုံး ရရှိစေရန်အတွက် အောက်ပါ 3D Push Button Class များအား စနစ်တကျ တည်ဆောက် တပ်ဆင်ထားသည်-

1. **Dual Action CTA (အဓိက ဝယ်ယူမှု ခလုတ် ၂ ခု):**
   - **Buy Now CTA (`.sf-btn-3d-orange`):**
     - **Background Gradient:** `bg-gradient-to-b from-orange-500 via-orange-600 to-orange-700`
     - **3D Bevel:** `border-b-[3.5px] border-b-[#9a3412]` (Active တွင် `border-b-0` ဖြင့် `translate-y-0.5` နစ်ဝင်စေသည်)
     - **Localization:** `{{ __('messages.buy_now') }}` (EN: Buy Now, MY: ချက်ချင်းဝယ်မည်, ZH: 立即购买)
   - **Add to Order CTA (`.sf-btn-3d-primary`):**
     - **Background Gradient:** `from-sky-500 via-sky-600 to-sky-700` (သို့မဟုတ် Dynamic Primary Theme Gradient)
     - **3D Bevel:** `border-b-[3.5px] border-b-sky-800`
     - **Localization:** `{{ __('messages.add_to_order') }}`

2. **Direct Social Chat Buttons (တိုက်ရိုက် အော်ဒါတင် ခလုတ်များ):**
   - **Viber Chat Button (`.sf-btn-3d-viber`):**
     - **Background Gradient:** `from-violet-600 via-purple-700 to-indigo-800`
     - **3D Bevel:** `border-b-[3px] border-b-[#4c1d95]`
     - **Hover & Active:** `hover:from-violet-500 hover:to-indigo-700 active:border-b-0`
   - **Telegram Chat Button (`.sf-btn-3d-telegram`):**
     - **Background Gradient:** `from-sky-400 via-sky-500 to-cyan-600`
     - **3D Bevel:** `border-b-[3px] border-b-[#0369a1]`
     - **Hover & Active:** `hover:from-sky-300 hover:to-cyan-500 active:border-b-0`

3. **Variant Matrix Pills (အရောင်/အရွယ်အစား/Storage ခလုတ်များ):**
   - **Default/Unselected State:** `sf-btn-3d` (White/Dark Slate background, subtle 3D bevel `border-b-[2.5px] border-b-slate-300 dark:border-b-slate-700`)
   - **Selected State:** `.sf-btn-3d-orange ring-2 ring-orange-500/50 shadow-md` (ပေါ်လွင်သော မီးခိုးရောင်မစွန်းသော လိမ္မော်ရောင် tactile bevel)
   - **Out of Stock State:** `opacity-40 line-through cursor-not-allowed border-dashed`

4. **Action & Header Buttons:**
   - **Back Navigation Button:** `sf-btn-3d` mini push pill (`px-3 py-1.5`)
   - **Wishlist Heart Button:** `from-amber-300 via-amber-400 to-amber-600` (Gold 3D) မနှိပ်ရသေးချိန်၊ နှိပ်ပြီးချိန်တွင် Rose 3D
   - **Share Button:** Vibrant Sky 3D push button (`sf-btn-3d` variant with white icon)
   - **Wholesale Badge:** Emerald 3D Badge (`from-emerald-500 to-teal-600` with `border-b-2 border-b-emerald-800 shadow-xs`)

---

### ၁၀.၆။ Category Browser & Sharp 3D Card Architecture Standard (ကဏ္ဍစုံ ရှာဖွေမှုနှင့် လေးထောင့်စပ်စပ် 3D Card စံနှုန်း)

Storefront Category Browser (`resources/views/storefront/browse/index.blade.php`) သည် Customer များအတွက် မိုဘိုင်းဖုန်းနှင့် ကွန်ပျူတာတို့တွင် ကုန်ပစ္စည်းအမျိုးအစားစုံကို မျက်စိရှင်းရှင်းဖြင့် လျင်မြန်စွာ ရှာဖွေနိုင်စေသော Shopee/Lazada ပုံစံ 2-Pane Split-Screen Layout စနစ် ဖြစ်သည်။ အဆိုပါ စာမျက်နှာတွင် လက်တွေ့ကျင့်သုံးထားသော စံနှုန်းသတ်မှတ်ချက်များမှာ အောက်ပါအတိုင်း ဖြစ်သည်-

```
+------------------------------------------------------------------------------------------------+
|                        CATEGORY BROWSER 2-PANE SPLIT-SCREEN ARCHITECTURE                       |
+------------------------------------------------------------------------------------------------+
|  [Left Vertical Category Rail]          │  [Right Main Scrollable Content Area]                |
|  - Aspect 3/4 Tiles                     │  (main.flex-1.min-w-0.pt-0.pb-2)                     |
|  - rounded-none Sharp 3D                │  ┌────────────────────────────────────────────────┐  |
|  - Upper 80% Full-bleed Image           │  │ 1. STICKY TOP-0 CONTROLS (sticky.top-0.z-30)   │  |
|  - Lower Compact Label (px-0.5.py-0)    │  │    - Subcategory Strip (h-9 sm:h-10 px-3.5)    │  |
|  - Active: Dynamic Primary Gradient     │  │    - Brands Strip (.sf-brand-card rounded-none)│  |
|  - Rest: bg-white / dark:bg-slate-800   │  ├────────────────────────────────────────────────┤  |
|  - Pure CSS 3D Push Physics             │  │ 2. PRODUCTS GRID (gap-0.5 sm:gap-1.lg:gap-1.5) │  |
|                                         │  │    - Product Card (.relative.isolate)          │  |
|                                         │  │    - Compact Gold/Rose Heart (w-7 sm:w-8)      │  |
|                                         │  │    - :rounded="'rounded-none'"                 │  |
+-----------------------------------------┴──┴────────────────────────────────────────────────+
```

#### ၁။ Sharp Rectangular 3D Tiles (`rounded-none` လေးထောင့်စပ်စပ် ကတ်များ)
- ကုန်ပစ္စည်းအရေအတွက် များပြားသိပ်သည်းသော E-commerce/Browse စာမျက်နှာများတွင် ကြီးမားလွန်းသော ထောင့်ဝိုက်များ (`rounded-xl`, `rounded-2xl`) သည် နေရာလွတ် အလေအလွင့် ဖြစ်စေပြီး Visual Weight လေးလံစေသည်။
- ထို့ကြောင့် Category Cards, Brand Cards, Sub-category Pills နှင့် Products Grid Cards များအားလုံး၏ ထောင့် ၄ ဖက်လုံးရှိ အဝိုက်များကို လုံးဝဖယ်ရှားပြီး **`rounded-none` (0px)** ဖြင့်သာ တည်ဆောက်ရမည်။
- ပုံစံသည် ပြတ်သားသန့်ရှင်းသော လေးထောင့်စပ်စပ် 3D Tactile Push Tiles စတိုင်လ်အဖြစ် တညီတညွတ်တည်း ပေါ်လွင်စေသည်။

#### ၂။ Left Rail Category Cards Proportions (ဘယ်ဘက် ကဏ္ဍကတ် အချိုးအစားများ)
- **Aspect Ratio & Sizing:** `aspect-[3/4]` အချိုးဖြင့် ထောင်လိုက်ကတ် ပြုလုပ်ထားသည်။
- **Upper Edge-to-Edge Image (`h-[77%] sm:h-[80%]`):** အပေါ်ပိုင်း ၈၀% အား ကတ်၏ ဘေးဘောင်နှင့် ထိပ်စွန်းအထိ ကွက်တိကပ်သော Full-bleed ဓာတ်ပုံဧရိယာ ထားရှိပြီး၊ ဘေးဘောင် padding (`p-0`) မပါရှိစေရ။
- **Lower Compact Label (`px-0.5 py-0`):** အောက်ဖက် ၂၀% တွင်သာ ကဏ္ဍအမည်ကို `text-[8.5px] sm:text-[9.5px] font-black line-clamp-2` ဖြင့် နေရာယူမှု နည်းပါးစွာ ပြသသည်။
- **Readable Count Badge:** အပေါ်ညာဘက်ထောင့်တွင် `bg-black/60` (active ဖြစ်ပါက `bg-white/30`) backdrop blur badge ဖြင့် ကုန်ပစ္စည်း အရေအတွက်ကို ဖော်ပြထားသည်။
- **Admin Master Data Sync:** `/admin/categories` ရှိ Main Categories (၁၅) ခုလုံးနှင့် အမည်၊ အရေအတွက် အစဉ်လိုက် တိကျစွာ ချိတ်ဆက်ထားသည်။

#### ၃။ Brands Card Horizontal Scroll Track (အမှတ်တံဆိပ် အလျားလိုက် ကတ်များ)
- Sub-category Strip အောက်တည့်တည့်တွင် Category နှင့် ချိတ်ဆက်နေသော Brand များကို အလျားလိုက် ရွှေ့နိုင်သော ကတ်များအဖြစ် တပ်ဆင်ထားသည်။
- **Dedicated Card Dimensions (`.sf-brand-card`):** Mobile တွင် `76px × 96px`၊ Desktop တွင် `88px × 106px` အတိအကျ သတ်မှတ်ထားသည်။
- **Upper Image Proportions (`h-[74%] sm:h-[76%]`):** အပေါ်ပိုင်း ၄ ပုံ ၃ ပုံ နေရာတွင် Brand Logo သို့မဟုတ် သက်ဆိုင်ရာ ကုန်ပစ္စည်းဓာတ်ပုံကို ဘေးဘောင်မဲ့ Edge-to-edge ထားရှိသည်။
- **Slim Lower Brand Label (`px-0.5 py-0`):** အောက်ခြေတွင် `text-[8.5px] sm:text-[9.5px] font-black line-clamp-1` ဖြင့် အမှတ်တံဆိပ်အမည်ကို သပ်ရပ်စွာ ပြသသည်။
- **Client-Side Real-time Filtering:** Brand Card အား နှိပ်လိုက်သည်နှင့် ချက်ချင်း active ဖြစ်သွားပြီး အောက်ဖက် Product Grid တွင် သက်ဆိုင်ရာ Brand ပစ္စည်းများကိုသာ Instant စစ်ထုတ်ပြသသည်။

#### ၄။ Sub-Category Buttons Ergonomics (အမျိုးအစားခွဲ ခလုတ်များ)
- မိုဘိုင်းလက်ချောင်းဖြင့် နှိပ်ရ လွယ်ကူစေရန် အမြင့်အား **`h-9 sm:h-10 px-3.5`** သို့ တိုးမြှင့်ထားသည်။
- အဝိုက်မပါရှိသော **`rounded-none`** ဖြင့် Category Cards များနှင့် ညီညွတ်မှု ရှိစေသည်။
- ထိပ်ဆုံးတွင် ကဏ္ဍတစ်ခုလုံးရှိ စုစုပေါင်းအရေအတွက် ပြသသော `All (📂 အားလုံး)` ခလုတ် ပါဝင်ရမည်။

#### ၅။ Sticky Controls Toolbar & Stacking Context Isolation (ထိပ်ကပ် ဘားနှင့် အလွှာစနစ် ထိန်းချုပ်မှု)
- **Top Edge Flush (`pt-0`):** ညာဘက် `<main>` ဧရိယာအား `pt-0` ထားရှိကာ Toolbar အား `sticky top-0 z-30 bg-white dark:bg-slate-900` ဖြင့် Container အပေါ်ထိပ်စွန်းနှင့် ကွက်တိကပ်စေရမည် (Scroll မဆွဲခင် အပေါ်ဘက်တွင် အပေါက်ဟနေခြင်း မရှိစေရ)။
- **Stacking Context Isolation (`relative isolate`):** Product Card များ အောက်သို့ Scroll ဆွဲတင်ချိန်တွင် Card ပေါ်ရှိ 3D Wishlist Heart Button (သို့မဟုတ် action buttons) များသည် Sticky Toolbar ပေါ်သို့ ထိုးဖောက်တက်ရောက်ခြင်း မရှိစေရန် Product Card Wrapper အား `class="relative isolate"` မဖြစ်မနေ ထည့်သွင်းထားရမည်။

#### ၆။ Compact 3D Wishlist Button Standard (ကျစ်လျစ်သော အသဲနှလုံး 3D ခလုတ်)
- Product Card ပေါ်ရှိ Favorite Heart Button အား မူလအရွယ်အစား `w-10 h-10` မှ ကုန်ပစ္စည်းဓာတ်ပုံ မကွယ်စေရန် **`w-7 h-7 sm:w-8 sm:h-8`** သို့ လျှော့ချထားသည်။
- အတွင်းရှိ အသဲအိုင်ကွန်အား **`w-3.5 h-3.5 sm:w-4 sm:h-4`** ဖြင့် ထားရှိပြီး၊ ကတ်၏ အပေါ်ညာဘက်ထောင့် **`top-1.5 right-1.5 sm:top-2 sm:right-2`** တွင် သပ်ရပ်စွာ နေရာချထားသည်။
- Component Dispatcher (`<x-product-card :rounded="'rounded-none'">`) မှတစ်ဆင့် Outer Shell သို့ Rounded Prop တိုက်ရိုက် လက်ခံနိုင်အောင် တည်ဆောက်ထားသည်။

#### ၇။ Ultra-Dense 2px Rhythm & Single Search Bar Policy
- Card များ တစ်ခုနှင့်တစ်ခု အကြား နေရာလွတ် မကျယ်စေဘဲ သိပ်သည်းကျစ်လျစ်သော **`gap-0.5 sm:gap-1 lg:gap-1.5`** ဖြင့်သာ နေရာချထားရမည်။
- Browse Page ပေါ်တွင် Search Bar ၂ ခု ထပ်နေခြင်း မရှိစေရန် အပေါ်ဘက်ရှိ ဒုတိယ Search Bar အား ဖယ်ရှားပြီး Storefront Universal Header Search Bar နှင့် Left Rail Real-time Filter ဖြင့်သာ စနစ်တကျ ထားရှိသည်။

---

### ၁၀.၇။ Shopping Cart & Order Builder Architecture Standard (ခြင်းတောင်းနှင့် အော်ဒါပြင်ဆင်မှု စံနှုန်း)

Storefront Order Builder (`resources/views/storefront/orders/builder.blade.php`) သည် Customer များ ခြင်းတောင်းထဲရှိ ပစ္စည်းများကို စစ်ဆေး၍ အမည်၊ ဖုန်း၊ လိပ်စာ ဖြည့်သွင်းကာ အော်ဒါပေးပို့နိုင်သော စာမျက်နှာ ဖြစ်သည်။ ဤစာမျက်နှာအတွက် လက်တွေ့ကျင့်သုံးထားသော စံနှုန်းများမှာ အောက်ပါအတိုင်း ဖြစ်သည်-

```
+------------------------------------------------------------------------------------------------+
|                   SHOPPING CART & ORDER BUILDER 12-COLUMN RESPONSIVE LAYOUT                    |
+------------------------------------------------------------------------------------------------+
|  [Left Column: Selected Products (7 cols)]  │  [Right Column: Sticky Checkout Form (5 cols)]   |
|  - Full-Bleed Mobile 2px Padding            │  - Sticky Top-16 / Top-20 Docking                |
|  - Modern Container (rounded-lg sm:rounded- │  - Modern Inputs (rounded-md, border-slate-300)  |
|    xl)                                      │  - Contact Channels Grid (Viber/Telegram/Phone)  |
|  - Edge-to-Edge Product Rows                │  - High-Visibility Golden CTA (.sf-btn-3d-gold)  |
|  - Pure CSS 3D Steppers (.sf-btn-3d +/-)    │  - Realtime items_json Payload Binding           |
|  - Pure CSS 3D Danger Remove Button         │  - Tri-lingual Validation & Placeholders         |
|  - Dynamic Currency window.formatCurrency() │                                                  |
|  - Trust Badges (4x Modern 3D Tiles)        │                                                  |
+---------------------------------------------┴──────────────────────────────────────────────────+
|  [Bottom Section: Delivery & Payment Methods (2-Col Responsive Modern 3D Tiles)]               |
+------------------------------------------------------------------------------------------------+
```

#### ၁။ Mobile Full-Bleed 2px Edge-to-Edge Layout
- မိုဘိုင်းဖုန်းမျက်နှာပြင်များတွင် ကတ်ဘေးဘက် အလွတ်များ နေရာမယူစေရန် `@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')` ဖြင့် အစွန်း ၂ ဖက်တွင် 2px edge padding ဖြင့် content များကို screen ပြည့်ပြည့်ဝဝ (full-bleed) ဖော်ပြသည်။
- Container အတွင်းရှိ padding များကို `p-2 sm:p-3.5 lg:p-4` ဖြင့် အချိုးကျ ထားရှိသဖြင့် မျက်နှာပြင် ကျဉ်းမြောင်းသော 320px–375px ဖုန်းများတွင် အထူး သပ်ရပ်စေသည်။

#### ၂။ Subtle Modern Rounded Corners Standard (`rounded-lg sm:rounded-xl` & `rounded-md`)
- မူလ `rounded-none` (ထောင့်ချွန်စပ်စပ်) အစား မျက်စိပသာဒဖြစ်စေသော ခေတ်မီ နူးညံ့သပ်ရပ်သည့် Rounded Corners စံနှုန်းသို့ ပြောင်းလဲထားသည်။
- အဓိက Outer Container Cards များနှင့် Summary Cards များတွင် **`rounded-lg sm:rounded-xl`** (8px–12px) ကိုလည်းကောင်း၊ Input Boxes၊ Textarea၊ Quantity Steppers နှင့် Action Buttons များတွင် **`rounded-md`** (6px) ကိုလည်းကောင်း စနစ်တကျ အချိုးညီ အသုံးပြုရမည်။

#### ၃။ Pure CSS 3D Tactile Push Steppers & Danger Remove Button
- အရေအတွက် အတိုး/အလျှော့ ပြုလုပ်သည့် ခလုတ်များအား မူလ Flat ပုံစံမှ **`sf-btn-3d w-7 h-7 sm:w-8 sm:h-8 rounded-md`** tactile push physics သို့ အဆင့်မြှင့်တင်ထားသည်။
- ပစ္စည်းဖျက်ပယ်သည့် ခလုတ်အား **`.sf-btn-3d-danger w-7 h-7 sm:w-8 sm:h-8 rounded-md`** ဖြင့် ထင်ရှားသော 3D Red Push Button အဖြစ် ပြင်ဆင်ထားသည်။

#### ၄။ High-Visibility Golden Submit CTA (`sf-btn-3d-gold`)
- "အော်ဒါပေးပို့မည် (Submit Order)" ခလုတ်အား သာမန် primary color များထက် အဆပေါင်းများစွာ ထင်ရှားပေါ်လွင်ပြီး စျေးဝယ်သူ၏ မျက်စိကို တိုက်ရိုက်ဆွဲဆောင်နိုင်သော ရွှေရောင် 3D Tactile Button **`.sf-btn-3d-gold`** ဖြင့် သီးသန့် အဆင့်မြှင့်တင်ထားသည်။
- ဖိနှိပ်သည့်အခါ 3D Bevel ချိုင့်ဝင်သွားသည့် Tactile Feedback ပါဝင်ပြီး Loading ဖြစ်ချိန်တွင် အလိုအလျောက် Spinner ပြသကာ Double Submit မဖြစ်စေရန် ကာကွယ်ထားသည်။

#### ၅။ Official Admin Logo Synchronization (`$storeSetting->adminLogo()`)
- Order Builder စာမျက်နှာ ထိပ်စီး Header Bar ရှိ ဆိုင်တံဆိပ် (Logo) အား Generic Storefront Logo အစား လုပ်ငန်း၏ တရားဝင် ပင်မ Admin Logo (`$storeSetting->adminLogo()`) နှင့် တိုက်ရိုက် ချိတ်ဆက်ပြသထားသဖြင့် POS စနစ်နှင့် အွန်လိုင်းစတိုး၏ Brand Identity ကို တစ်သားတည်း တည်ဆောက်ပေးထားသည်။

#### ၆။ Dynamic Theming & Zero Hardcoding Policy
- စတိုးဆိုင် Theme အရောင်များနှင့် လိုက်လျောညီထွေဖြစ်စေရန် `#f85606` သို့မဟုတ် hardcoded hex colors များကို လုံးဝဖယ်ရှားပြီး **`[color:var(--sf-primary)]`** သို့ ပြောင်းလဲထားသည်။
- စျေးနှုန်းဖော်ပြမှုတွင် `"Ks"` အသေရေးသားထားခြင်း လုံးဝမပါရှိစေဘဲ Client-side `window.formatCurrency(val)` သို့မဟုတ် Server-side `format_currency()` ဖြင့် Dynamic Currency Setting အတိုင်း ပြသသည်။

#### ၇။ Tri-Lingual Localization Invariance
- `order_fast_checkout`, `order_confirm_channel_prompt`, `order_select_items_first`, `order_items_unit`, `order_no_image`, `order_payment_info_btn`, `order_payment_qr_btn`, `order_remove_item` နှင့် Input Placeholders များအားလုံးကို `lang/my/messages.php`၊ `lang/en/messages.php` နှင့် `lang/zh_CN/messages.php` သုံးဘာသာစလုံးတွင် တစ်ပြိုင်နက် ပြည့်စုံစွာ ထည့်သွင်းထားသည်။

---

### ၁၀.၈။ Hero Section Layout Stability & Zero-Shift Architecture (Flexbox Anti-Drop Standard)

Storefront ပင်မစာမျက်နှာ (`resources/views/welcome.blade.php`) ၏ Hero Section တွင် အင်တာနက် အနည်းငယ်နှေးခြင်း သို့မဟုတ် Browser Rendering မပြည့်စုံမီအချိန်များ၌ Category Sidebar ဘေးတွင် နေရာလွတ်ကြီးဖြစ်ပြီး Hero Banner Slider သည် အောက်သို့ လျှောကျ (drop down) သွားတတ်သည့် ပြဿနာအား အပြီးအပိုင် ဖြေရှင်းနိုင်ရန် ဤစံနှုန်းကို ချမှတ်ပြဌာန်းထားပါသည်-

```
+------------------------------------------------------------------------------------------------+
|                         HERO SECTION ZERO-SHIFT FLEXBOX ARCHITECTURE                           |
+------------------------------------------------------------------------------------------------+
|  [Desktop Flexbox Container: flex flex-col lg:flex-row gap-3 sm:gap-4 items-stretch]          |
|                                                                                                |
|  ┌───────────────────────────┐  ┌────────────────────────────────────────────────────────────┐  |
|  │ Category Sidebar          │  │ Wide Hero Banner Slider Column                             │  |
|  │ (hidden lg:flex)          │  │ (w-full flex-1 min-w-0 flex flex-col)                      │  |
|  │ - lg:w-72 xl:w-80         │  │ - 100% Flex-1 Fill Width Space                             │  |
|  │ - shrink-0 (Never shrinks)│  │ - Zero Row-Drop Guarantee (No wrapping)                    │  |
|  │ - 40px Compact Rows       │  │ - 4000ms Auto-Play with Pause on Hover                     │  |
|  │ - 256px Flyout Panels     │  │ - Value Trust Strip (3D Service/Delivery Badges)           │  |
|  └───────────────────────────┘  └────────────────────────────────────────────────────────────┘  |
+------------------------------------------------------------------------------------------------+
```

#### ၁။ ပြဿနာ၏ မူလဇာစ်မြစ် (Architectural Root Cause)
- ယခင်က Hero Section အား CSS Grid စနစ်ဖြစ်သော `grid grid-cols-1 lg:grid-cols-4 items-stretch` ဖြင့် တည်ဆောက်ထားခဲ့သည်။
- အင်တာနက် bandwidth ကျဆင်းနေချိန်၊ မိုဘိုင်း/ကွန်ပျူတာ hardware အားနည်းချိန် သို့မဟုတ် CSS/DOM hydration မပြီးပြတ်မီအချိန်များတွင် Browser ၏ **CSS Grid Auto-placement** Algorithm သည် Child 2 (Banner Column) ၏ Column Position ကို Row 1 တွင် မတွက်ချက်နိုင်ဘဲ Row 2 သို့ အလိုအလျောက် ဆင်းချလိုက်တတ်သည်။
- ၎င်းကြောင့် Category Sidebar ၏ ညာဘက်ခြမ်း ၇၅% ဧရိယာတွင် နေရာလွတ် အဖြူရောင်ကွက်လပ်ကြီး ဖြစ်သွားပြီး Banner သည် စာမျက်နှာ အောက်ဘက်သို့ ရောက်ရှိသွားကာ Reload ပြန်လုပ်မှသာ ပုံမှန် ပြန်ဖြစ်သွားခြင်း ဖြစ်သည်။

#### ၂။ Flexbox Anti-Drop Standard (စစ်မှန်သော ဖြေရှင်းမှု စံသတ်မှတ်ချက်)
- **Flexbox Row Transformation:** Container အား Grid စနစ်အစား မည်သည့်အခါမျှ အောက်သို့ ဆင်းမကျနိုင်သော Flexbox စနစ်ဖြစ်သည့် **`<div class="flex flex-col lg:flex-row gap-3 sm:gap-4 items-stretch">`** သို့ ပြောင်းလဲရမည်။
- **Fixed-Responsive Sidebar Width:** ဘယ်ဘက်ခြမ်း Category Sidebar အား **`hidden lg:flex lg:w-72 xl:w-80 shrink-0 min-w-0 flex-col`** ဖြင့် တိကျသော အကျယ်အဝန်း သတ်မှတ်ထားပြီး `shrink-0` ကြောင့် မည်သည့်အခါမျှ ကျုံ့ဝင်သွားခြင်း မရှိစေရ။
- **Fluid Banner Space Expansion:** ညာဘက်ခြမ်း Banner Column အား **`w-full flex-1 min-w-0 flex flex-col`** ဖြင့် သတ်မှတ်ထားသဖြင့် Sidebar ဘေးရှိ ကျန်ရှိသော နေရာလွတ်အားလုံးကို အပြည့်အဝ ရယူစေပြီး Desktop Screen အားလုံးတွင် ဘေးချင်းယှဉ် (Side-by-side) အဖြစ်သာ အမြဲတမ်း တည်ငြိမ်စွာ ဖော်ပြနိုင်မည် ဖြစ်သည်။
- **Mobile/Tablet Isolation:** 1024px အောက် (Mobile & Tablet) မျက်နှာပြင်များတွင် Sidebar သည် အလိုအလျောက် ကွယ်ပျောက်နေပြီး (`hidden lg:flex`) Banner Slider သည် Screen အပြည့် (`w-full`) ချောမွေ့စွာ ရပ်တည်မည် ဖြစ်သည်။

---

### ၁၀.၉။ Order Confirmation & Receipt Print Architecture Standard (အော်ဒါအတည်ပြုခြင်းနှင့် ပြေစာစံနှုန်း)

Storefront Order Confirmation (`resources/views/storefront/orders/confirmation.blade.php`) သည် စျေးဝယ်သူ အော်ဒါတင်သွင်းပြီးနောက် ရောက်ရှိလာသည့် အတည်ပြုချက် စာမျက်နှာ ဖြစ်သည်။ ဤစာမျက်နှာအတွက် ပြဌာန်းထားသော စံနှုန်းများမှာ အောက်ပါအတိုင်း ဖြစ်သည်-

```
+------------------------------------------------------------------------------------------------+
|                   ORDER CONFIRMATION & SLIP ARCHITECTURE (MAX-W-3XL RESPONSIVE)                |
+------------------------------------------------------------------------------------------------+
|  [1. Success Hero Banner]                                                                      |
|  - Emerald Badge (✓) + Ring Highlight + Dynamic Shop Name                                      |
|  - Order Number (#ORD-...) + 1-Tap Quick Copy 3D Button (.sf-btn-3d active)                    |
+------------------------------------------------------------------------------------------------+
|  [2. 4-Stage Visual Status Stepper]                                                            |
|  - Step 1: Placed (✓) | Step 2: Confirmation (⏱️) | Step 3: Packing | Step 4: Delivery (🚚)    |
+------------------------------------------------------------------------------------------------+
|  [3. Order Summary & Item Breakdown Card (rounded-lg sm:rounded-xl)]                           |
|  - Customer Name, Phone (with 1-Tap Direct Call), Channel Badge, Address, Note                 |
|  - Items Table with Thumbnail, Quantity Badge (xQty), Service/Digital Specs & Subtotals        |
|  - Grand Total Row (Dynamic format_currency, Strict Zero "Ks")                                 |
+------------------------------------------------------------------------------------------------+
|  [4. Direct Shop Confirmation Channels (No-Print)]                                             |
|  - Viber 3D Button (.sf-btn-3d-viber) | Telegram 3D Button (.sf-btn-3d-telegram)               |
|  - Store Hotline Call Button (.sf-btn-3d-success) | 1-Tap Copy Full Order Message              |
+------------------------------------------------------------------------------------------------+
|  [5. Store Payment Transfer Accounts & QR Modal (No-Print)]                                    |
|  - Active Bank/Mobile Pay Grid | <x-payment-qr-modal /> Integration                            |
+------------------------------------------------------------------------------------------------+
|  [6. Actions Toolbar: Print Slip (window.print) & Continue Shopping (.sf-btn-3d-gold)]         |
+------------------------------------------------------------------------------------------------+
```

#### ၁။ 1-Tap Quick Copy Code Standard
- Customer များသည် ဘဏ်ငွေလွှဲချိန်တွင် Remark/Note ထည့်ရန် သို့မဟုတ် ဆိုင်သို့ အော်ဒါနံပါတ် ပြောပြနိုင်ရန် `#ORD-...` နံပါတ်အား လက်ဖြင့် ဖိကူးစရာမလိုဘဲ ကလစ်တစ်ချက်ဖြင့် ချက်ချင်းကူးယူနိုင်သော **`sf-btn-3d active`** Mini Button ကို တပ်ဆင်ထားသည်။
- ကူးယူပြီးချိန်တွင် Clipboard API ဖြင့် အလုပ်လုပ်ကာ ခလုတ်စာသားသည် `✓ ကူးယူပြီးပါပြီ!` သို့ ချက်ချင်းပြောင်းလဲသွားမည် ဖြစ်သည်။

#### ၂။ 4-Stage Visual Status Stepper
- Customer စိတ်အေးချမ်းမှု (Buyer Confidence) ရရှိစေရန် အော်ဒါအခြေအနေကို အဆင့် ၄ ဆင့်ဖြင့် ထင်ရှားစွာ ပြသပေးထားသည်-
  1. `အော်ဒါတင်ပြီး` (Order Placed - အစိမ်းရောင် အမှတ်အသား)
  2. `ဆိုင်မှ အတည်ပြုဆဲ` (Shop Confirmation - အဝါရောင် အချိန်ပြ အိုင်ကွန်နှင့် အသက်ဝင်သော Pulse Animation)
  3. `ငွေပေးချေမှု/ထုပ်ပိုးခြင်း` (Payment & Packing)
  4. `ပို့ဆောင်ခြင်း` (Delivery)

#### ၃။ Print Slip Media Isolation
- စျေးဝယ်သူ သို့မဟုတ် ဆိုင်ဝန်ထမ်းမှ စာရွက်ဖြင့် ပြေစာထုတ်ယူလိုပါက `window.print()` ကို အသုံးပြုနိုင်ပြီး Print Media CSS (`@media print`) ဖြင့် Header၊ Footer၊ Viber/Telegram Buttons၊ QR Modal များနှင့် Navigation bar များကို အလိုအလျောက် ဖုံးကွယ်ပေးကာ သန့်ရှင်းသော ပြေစာစာရွက် သက်သက်သာ ထွက်ရှိလာစေသည်။

#### ၄။ Dynamic Formatting & Localization Invariance
- Currency တိုင်းတွင် Hardcoded "Ks" လုံးဝမပါဝင်ဘဲ `format_currency()` ဖြင့် စတိုးဆိုင် Setting အတိုင်း ပြသသည်။
- ကုန်ပစ္စည်း အရေအတွက်တွင် `.000` ကဲ့သို့ ဒသမအပိုများ မပါဝင်ဘဲ `format_quantity($qty, $store)` ဖြင့် သန့်ရှင်းစွာ ဖော်ပြသည်။
- မြန်မာ၊ အင်္ဂလိပ်နှင့် တရုတ် သုံးဘာသာစလုံးအတွက် Translation Keys များကို အပြည့်အစုံ ဖြည့်သွင်းထားသည်။

---

### ၁၀.၁၀။ Customer ဝန်ဆောင်မှုဆိုင်ရာ Service Tracking Search & 5-Stage Stepper Dashboard Architecture

ဖောက်သည်များသည် မိမိတို့ ပြင်ဆင်ရန် အပ်နှံထားသော မိုဘိုင်းဖုန်း၊ ကွန်ပျူတာ၊ စီစီတီဗီနှင့် အီလက်ထရောနစ် ပစ္စည်းများ၏ ပြင်ဆင်မှု အဆင့်ဆင့်ကို ဆိုင်သို့ ဖုန်းဆက်မေးစရာမလိုဘဲ အချိန်နှင့်တပြေးညီ အလွယ်တကူ ရှာဖွေစစ်ဆေးနိုင်ရန် တည်ဆောက်ထားသော စနစ် ဖြစ်သည်။

```
+------------------------------------------------------------------------------------------------+
|                    SERVICE TRACKING ARCHITECTURE (MAX-W-4XL RESPONSIVE)                        |
+------------------------------------------------------------------------------------------------+
|  [1. Actions Toolbar (No-Print)]                                                               |
|  - Back to Search Link | Print Slip Button (window.print) | 1-Tap Copy Tracking Link           |
+------------------------------------------------------------------------------------------------+
|  [2. Service Header & 1-Tap Job ID Copy]                                                       |
|  - Voucher No (V-...) / Job No (SVC-...) + Current Status Badge (with soft background)        |
+------------------------------------------------------------------------------------------------+
|  [3. 5-Stage Localized Visual Progress Stepper]                                                |
|  - 1. Received (✓) ➔ 2. Diagnosing (✓) ➔ 3. In Repair (⚡ Active Pulse) ➔ 4. Ready ➔ 5. Delivered |
+------------------------------------------------------------------------------------------------+
|  [4. Device Details & Diagnosis Card (rounded-lg sm:rounded-xl)]                               |
|  - Device: Brand, Model, Serial / IMEI Number                                                  |
|  - Customer Issue Description & Technician Technical Diagnosis Note                            |
+------------------------------------------------------------------------------------------------+
|  [5. Parts & Services Line Items Table]                                                        |
|  - Replaced Parts & Service Labor Fees (format_quantity, format_currency)                      |
+------------------------------------------------------------------------------------------------+
|  [6. Timeline History & Audit Trail]                                                           |
|  - Chronological Status Logs, Timestamp, Responsible Tech & Progression Notes                  |
+------------------------------------------------------------------------------------------------+
|  [7. Financial Summary & Balance Due]                                                          |
|  - Total Service Charges, Paid Deposit, and Outstanding Balance Due (with amber soft alert)   |
+------------------------------------------------------------------------------------------------+
|  [8. Direct Official Contact Channels (No-Print)]                                              |
|  - Viber 3D Button (.sf-btn-3d-viber) | Telegram 3D Button (.sf-btn-3d-telegram)               |
|  - Direct Phone Call Button (.sf-btn-3d-success)                                               |
+------------------------------------------------------------------------------------------------+
```

#### ၁။ Interactive Search Helper Pills (index.blade.php)
- Customer များသည် မိမိတို့ အပ်နှံထားသော လက်မှတ်မှ ဘောက်ချာနံပါတ်၊ ဆာဗစ်ဂျော့နံပါတ် သို့မဟုတ် ဖုန်းနံပါတ် စသည်ဖြင့် မည်သည့် အချက်အလက်ဖြင့်မဆို အလွယ်တကူ ရှာဖွေနိုင်ရန် Search Form အောက်တွင် 1-Tap Helper Pills များ စီမံပေးထားသည်။
- ကလစ်တစ်ချက် နှိပ်လိုက်သည်နှင့် Input ထဲသို့ စာသားအလိုအလျောက် ဖြည့်သွင်းကာ Form ကို တိုက်ရိုက် Submit ပြုလုပ်ပေးသည်။

#### ၂။ 5-Stage Localized Visual Progress Stepper (show.blade.php)
- Service Job တစ်ခုချင်းစီ၏ ပြင်ဆင်မှု အဆင့်ကို အဆင့် ၅ ဆင့်ဖြင့် စနစ်တကျ မီးမောင်းထိုးပြသထားသည်-
  1. `လက်ခံရရှိ` (Received - အောင်မြင်မှု အမှတ်အသား)
  2. `စစ်ဆေးဆဲ` (Diagnosing - အောင်မြင်မှု အမှတ်အသား)
  3. `ပြင်ဆင်နေဆဲ` (In Repair - တိုက်ရိုက်ပြင်ဆင်နေချိန်၌ အသက်ဝင်သော Pulse Ring Animation ဖြင့် ထင်ရှားစွာ ပြသ)
  4. `ပြင်ဆင်ပြီးစီး` (Ready for Pickup - ဆိုင်သို့ လာရောက်ယူဆောင်နိုင်သည့် အခြေအနေ)
  5. `ပေးအပ်ပြီး` (Delivered - ဖောက်သည်ထံ ပစ္စည်းပြန်လည် အပ်နှံပြီးစီးမှု)

#### ၃။ 1-Tap Tracking Link Copy & Clipboard Toast
- Customer များသည် မိမိတို့၏ Tracking Link အား အခြားမိသားစုဝင်များထံ မျှဝေရန် သို့မဟုတ် သိမ်းဆည်းထားရန် ခေါင်းစဉ်ထိပ်ရှိ `Copy Link` ခလုတ်ကို နှိပ်ရုံဖြင့် ချက်ချင်း ကူးယူနိုင်ပြီး `✓ ကူးယူပြီးပါပြီ!` ဟူသော UI Toast တုံ့ပြန်မှုကို ရရှိစေသည်။

#### ၄။ Service Job Slip Preview Modal & Print Isolation
- ဖောက်သည် သို့မဟုတ် ကောင်တာဝန်ထမ်းမှ `🖨️ ပြေစာ အစမ်းကြည့်/ထုတ်မည်` ခလုတ်ကို နှိပ်လိုက်သည့်အခါ Screen ပေါ်တွင် တရားဝင် **Service Job Slip Preview Modal (ပစ္စည်းလက်ခံပြုပြင်လွှာ / အပ်ပြေစာ)** ကတ်ပြား ချက်ချင်း ပွင့်လာမည် ဖြစ်သည်။
- ၎င်း Modal ထဲတွင် စတိုးဆိုင်အမည်၊ လိပ်စာ၊ ဖုန်းနံပါတ်၊ ဘောက်ချာနံပါတ်၊ ရက်စွဲ၊ ပိုင်ရှင်အချက်အလက်၊ စက်အမျိုးအစား (Brand/Model/Serial)၊ ပျက်စီးမှု၊ လဲလှယ်ပစ္စည်းများ၊ ကျသင့်ငွေနှင့် ပေးရန်ကျန်ငွေ ရှင်းတမ်းတို့ကို စနစ်တကျ အစမ်းကြည့်ရှုနိုင်ပြီး အောက်ခြေရှိ `🖨️ ပုံနှိပ်ရန်` ခလုတ်ဖြင့်ဖြစ်စေ၊ `🔗 လင့်ခ် ကူးယူမည်` ခလုတ်ဖြင့်ဖြစ်စေ စိတ်ကြိုက် အသုံးပြုနိုင်ပါသည်။
- `@media print` Isolation ကြောင့် စက္ကူ Print ထုတ်သည့်အခါတွင်လည်း မလိုအပ်သော Web Elements များကို ဖုံးကွယ်ပြီး သန့်ရှင်းသော ပစ္စည်းအပ်ပြေစာအဖြစ် ထွက်ရှိစေပါသည်။

#### ၅။ Direct Official Communication Channels
- ဖောက်သည်များအနေဖြင့် ပြင်ဆင်နေစဉ်အတွင်း ကျွမ်းကျင်ပညာရှင်ထံသို့ မေးမြန်းစုံစမ်းလိုပါက Brand Colors အပြည့်အစုံပါဝင်သော Pure CSS 3D Buttons များဖြစ်သည့် `.sf-btn-3d-viber`၊ `.sf-btn-3d-telegram` နှင့် `.sf-btn-3d-success` (ဖုန်းခေါ်ဆိုရန်) တို့ဖြင့် တိုက်ရိုက် ဆက်သွယ်စကားပြောဆိုနိုင်သည်။





