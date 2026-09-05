# DataPOS Storefront UI/UX Standard Guide (v1.0)
**Production-Grade E-Commerce Storefront Polish, Offline-First Resilience & Low-End Device Optimization**

---

| Document Meta | Details |
| :--- | :--- |
| **Standard Version** | `v1.0.0-Production-Ready` |
| **Last Updated** | `2026-09-06` |
| **Applicable Modules** | Storefront Web (`resources/views/welcome.blade.php`, `products/*`, `cart/*`, `checkout/*`, `service-tracking.blade.php`, `layouts/storefront/*`) |
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
- [ ] **Sticky 3D Header:** Brand Logo, Real-time Search Input (`h-9`), Dark Mode Switcher, Cart Trigger (`sf-btn-3d`)။
- [ ] **Desktop Category Sidebar:**
  - Item Height အား ကျစ်လစ်သော **`40px`** (`py-1.5`) ထားရှိရမည်။
  - Subcategory Flyout Panel အကျယ်အား **`w-56 sm:w-64` (`256px`)** သို့ ချိန်ညှိရမည် (Banner မကွယ်စေရန်)။
  - ကွင်းစကွင်းပိတ်မပါသော **သဘာဝကျသည့် မြန်မာစကား သီးသန့်** (`$category->localized_name`) ပြသရမည်။
- [ ] **4-Item Value Trust Strip:** Banner အောက်တွင် `⚡ အမြန်ဆုံး ပို့ဆောင်မှု`၊ `🛡️ စစ်မှန်သော အာမခံ`၊ `🔧 ဆာဗစ်စစ်`၊ `💬 တိုက်ရိုက် အကူအညီ` ကတ် ၄ ခုအား 3D Tactile Push Cards များအဖြစ် တပ်ဆင်ရမည်။
- [ ] **Flash Sale & Promotional Product Grids:** စတော့အရေအတွက် နည်းနေပါက Low Stock Badge (`ကျန်ရှိအရေအတွက် နည်းပါး`) ကို ထင်ရှားသော အရောင်ဖြင့် ပြသရမည်။

### ၅.၂။ ကုန်ပစ္စည်းစာရင်း စာမျက်နှာ — Product Catalog (`products/index.blade.php`)
- [ ] **Sticky Filter Toolbar:** အမျိုးအစား Filter Pills၊ စျေးနှုန်း Range Slider၊ လက်ကျန်ရှိသော ပစ္စည်းများသာကြည့်ရန် Toggle။
- [ ] **Mobile Filter Drawer:** ဖုန်းမျက်နှာပြင်များတွင် အောက်ခြေမှ အိစက်စွာ တက်လာမည့် Slide-over Filter Sheet (1-Tap Clear All ပါဝင်ရမည်)။
- [ ] **Clean Product Cards:**
  - ပုံရိပ်ပေါ်တွင် တင်ထားသော 3D Wishlist Heart ခလုတ်။
  - ပစ္စည်းအမည် (`font-bold font-myanmar`)။
  - Dynamic Currency စျေးနှုန်း (`format_currency($p->price, $store)`)။
  - 1-Tap Quick Add to Cart 3D Push Button။

### ၅.၃။ ပစ္စည်းအသေးစိတ် စာမျက်နှာ — Product Details (`products/show.blade.php`)
- [ ] **Image Gallery with Pinch-to-Zoom:** လက်ချောင်းဖြင့် ဘယ်/ညာ ပွတ်ဆွဲ၍ ကြည့်ရှုနိုင်သော Image Carousel + Thumbnail Navigation။
- [ ] **Variant Matrix Picker:** အရောင် (Color)၊ သိုလှောင်မှု (Storage)၊ အမျိုးအစားခွဲ (Specs) များကို 3D Pills များဖြင့် ရွေးချယ်စေပြီး စျေးနှုန်းနှင့် စတော့ကို Realtime အလိုအလျောက် ပြောင်းလဲပြသခြင်း။
- [ ] **Direct Social Order Action Bar:**
  - အစိမ်းရောင် `Viber ဖြင့် ချက်ချင်းမှာယူရန်` ခလုတ်။
  - အပြာရောင် `Telegram ဖြင့် မေးမြန်းရန်` ခလုတ်။
  - အဓိက `ခြင်းတောင်းထဲသို့ ထည့်မည်` 3D ခလုတ်ကြီး။
- [ ] **Wholesale Customer Experience:** လက်ကားခွင့်ပြုချက်ရရှိထားသော Customer ဖြစ်ပါက လက်ကားစျေးနှုန်း Badge နှင့် အနည်းဆုံးမှာယူရမည့် အရေအတွက် (MOQ) ကို ရှင်းလင်းစွာ ဖော်ပြခြင်း။

### ၅.၄။ ခြင်းတောင်းနှင့် ငွေရှင်း စာမျက်နှာ — Cart & Checkout (`cart/*`, `checkout/*`)
- [ ] **Zero-Loss Cart Review:** ကုန်ပစ္စည်းတစ်ခုချင်းစီ၏ အရေအတွက် အတိုး/အလျှော့ ခလုတ်များ (`+` / `-`) အား Instant 3D Push ပြုလုပ်နိုင်ခြင်း။
- [ ] **Delivery & Logistics Selector:**
  - ရန်ကုန်/မန္တလေး အိမ်ရောက်ငွေချေ (Doorstep Delivery)။
  - နယ်ဝေး ကားဂိတ်တင်ပေးပို့မှု (Bus Gate Delivery) နှင့် ဂိတ်အမည်/မြို့နယ် ဖြည့်သွင်းသည့် ကွက်လပ်များ။
- [ ] **Payment & Receipt Slip Attachment:**
  - KPay / WavePay / CB Pay / AYA Pay QR Code ပြသမှု။
  - ငွေလွှဲပြေစာ Screenshot Upload လုပ်နိုင်သော Image Picker (Local Image Compression ပါဝင်ရမည်)။
  - COD (ပစ္စည်းရောက်မှ ငွေချေစနစ်) Toggle Option။

### ၅.၅။ ဆာဗစ်စစ် စစ်ဆေးခြင်း စာမျက်နှာ — Service Tracking (`service-tracking.blade.php`)
- [ ] **Instant Ticket Lookup:** ဘောက်ချာနံပါတ် သို့မဟုတ် ဖုန်းနံပါတ် ရိုက်ထည့်ရုံဖြင့် ချက်ချင်း ရှာဖွေနိုင်ခြင်း (Barcode Scanner ပါဝင်ရမည်)။
- [ ] **5-Stage Visual Stepper:**
  1. ပစ္စည်းလက်ခံရရှိ (`Received`)
  2. ချို့ယွင်းချက်စစ်ဆေးနေဆဲ (`Diagnosing`)
  3. ပြင်ဆင်နေဆဲ (`In Repair`)
  4. စမ်းသပ်စစ်ဆေးပြီးစီး (`Quality Testing`)
  5. ပစ္စည်းလာရောက်ထုတ်ယူနိုင်ပြီ (`Ready for Pickup`)
- [ ] **Direct Technician Assistance:** တာဝန်ခံပြင်ဆင်သူနှင့် ချက်ချင်း စကားပြောနိုင်သော Direct Chat ခလုတ်။

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
  - **လုပ်ဆောင်ချက်များ:** 3D Desktop Navigation, Compact Category Sidebar (`40px`), Slim Flyout Panel (`256px`), Value Trust Strip 3D Cards, Section Headers ရှိ "အားလုံးကြည့်မည် (View All)" Link များအား `sf-btn-3d active` Mini Push Buttons သို့ ပြောင်းလဲခြင်း၊ Glass Finder CTA အား `sf-btn-3d active` သို့ ပြင်ဆင်ခြင်း၊ Direct Support Modal ခလုတ်များ (Viber, Telegram, Phone) အား 3D Bevel Buttons သို့ ပြောင်းလဲခြင်း၊ Dynamic Theming Tokens အပြည့်အဝ ချိတ်ဆက်ပြီးစီး။
- [x] **Product Catalog & Search (ကုန်ပစ္စည်းစာရင်းနှင့် ရှာဖွေမှု):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/catalog/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/catalog/index.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Hardcoded `Ks` နှင့် `(Ks)` များ အကုန်ဖယ်ရှားပြီး `format_currency()` သို့ ပြောင်းလဲခြင်း၊ Hardcoded ကာလာများ ဖယ်ရှားပြီး Dynamic 3D Push Button Suite (`sf-btn-3d`, `sf-btn-3d-primary`, `sf-btn-3d-danger`, `sf-btn-3d-success`, `sf-btn-3d-accent`) တပ်ဆင်ခြင်း၊ Grid/List view switcher 3D ပြောင်းခြင်း၊ Slim Category/Brand Hover Flyout (`256px` Zero GPU Lag) တပ်ဆင်ခြင်း၊ Desktop 2-Column Side-by-Side Grid Layout နှင့် Product Card Action Buttons များကို 3D ပြုလုပ်ပြီးစီး။
- [x] **Product Details & Variant Specs (ကုန်ပစ္စည်းအသေးစိတ်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/catalog/show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/catalog/show.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Hardcoded inline gradient styles အားလုံး ဖယ်ရှားပြီး 3D Tactile Suite ဖြင့် အစားထိုးခြင်း၊ "Buy Now" CTA အား `.sf-btn-3d-orange` ဖြင့်လည်းကောင်း၊ "Add to Order" CTA အား `.sf-btn-3d-primary` ဖြင့်လည်းကောင်း တပ်ဆင်ခြင်း၊ Direct Order (Viber & Telegram) အတွက် `.sf-btn-3d-viber` နှင့် `.sf-btn-3d-telegram` 3D Buttons အသစ်များ တည်ဆောက် တပ်ဆင်ခြင်း၊ 3D Variant Matrix Pills (Color/Storage/Specs) များတွင် Tactile push physics နှင့် active state သတ်မှတ်ခြင်း၊ 3D Wholesale Badge နှင့် Promo Pill များ တပ်ဆင်ခြင်း၊ 3D Metallic Gold / Rose Favorite Heart Button နှင့် 3D Share Button တပ်ဆင်ခြင်း၊ `lang/{en,my,zh_CN}/messages.php` သုံးဘာသာစလုံးတွင် `'buy_now'` key အပြည့်အစုံ ဖြည့်စွက်ပြီးစီး။
- [ ] **Category Browser (ကုန်ပစ္စည်း ကဏ္ဍစုံ ရှာဖွေမှု):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/browse/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/browse/index.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Visual Category Grid Cards, Subcategory Accordions, Category Hero Banners, 3D Push Cards။
- [ ] **Shopping Cart & Order Builder (ခြင်းတောင်းနှင့် အော်ဒါပြင်ဆင်မှု):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/orders/builder.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/orders/builder.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** LocalStorage Resilient Cart, 3D Quantity Steppers (`+`/`-`), Dynamic Order Summary, KPay/WavePay Selector, Delivery Option Toggles, Instant Checkout 3D CTA။
- [ ] **Order Confirmation & Slip Attachment (အော်ဒါအတည်ပြုခြင်းနှင့် ပြေစာတင်ခြင်း):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/orders/confirmation.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/orders/confirmation.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Order Success Visual Banner, Order Code Copy 3D Button, Slip Upload Compression, Direct Contact Shop Button (`tel:` & `viber:`)။

### ၉.၂။ Customer ဝန်ဆောင်မှုနှင့် Self-Service စာမျက်နှာများ (Customer Service & Tools)
- [ ] **Service Tracking Search (ဆာဗစ်စစ် ရှာဖွေမှု ပင်မ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/service_tracking/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/service_tracking/index.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Phone/Ticket Search Input (`h-10`), Barcode Scanner Modal, Recent Search History Pills, Direct Support 3D Button။
- [ ] **Service Tracking Status Stepper (ပြင်ဆင်မှု အခြေအနေ အသေးစိတ်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/service_tracking/show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/service_tracking/show.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** 5-Stage Animated Progress Stepper, Device Photos/Parts List, Estimated Completion Time, Direct Technician Chat 3D CTA။
- [ ] **Glass / Screen Protector Finder (မှန်မကွဲ အလွယ်ရှာစနစ်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/glass_finder/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/glass_finder/index.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Brand/Model Cascading Dropdown, Real-time Compatible Models Matching, Quick Buy 3D Button။
- [ ] **How To Order Guide (စျေးဝယ်နည်း လမ်းညွှန်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/how_to_order/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/how_to_order/index.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Visual Infographic Steps, Payment Transfer Instructions, Bus Gate Delivery FAQ, 3D Accordion Expanders။
- [ ] **Wholesale Registration (လက်ကားဖောက်သည် လျှောက်ထားလွှာ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/wholesale/apply.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/wholesale/apply.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Clean Shop Verification Form, Business Card/License Upload, Submit 3D Button (`sf-btn-3d-primary`), Status Modal။
- [ ] **POS-Only Storefront Notice (စတိုးဆိုင်ပိတ် သို့မဟုတ် POS သီးသန့် အသိပေးချက်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/pos_only.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/pos_only.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Friendly Store Maintenance Illustration, Direct Store Contact Hotline, Store Working Hours, Map Direction Button။

### ၉.၃။ Customer အကောင့်နှင့် Portal စာမျက်နှာများ (Customer Account Portal)
- [ ] **Customer Profile Dashboard (အကောင့် ပင်မမျက်နှာပြင်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/customer/account/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/customer/account/index.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Centered Profile Stat Cards, Tier/Wholesale Status Badge, Quick Actions 3D Grid, Mobile Responsive Tab Switcher။
- [ ] **Customer Order History (မှာယူမှု မှတ်တမ်းများ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/customer/account/orders.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/customer/account/orders.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Filter by Status (Pending/Delivered/Cancelled), Clean Date/Amount Formatting, 1-Tap Reorder 3D Button။
- [ ] **Customer Order Detail & Invoice (အော်ဒါအသေးစိတ်နှင့် ပြေစာ):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/customer/account/order_show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/customer/account/order_show.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Invoice Printable View, Live Delivery Status, Payment Slip Preview, Download PDF/Image Button။
- [ ] **Customer Wishlist (နှစ်သက်သော ပစ္စည်းများ စာရင်း):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/customer/account/favorites.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/customer/account/favorites.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Compact Product Cards, Move to Cart 3D Button, Remove 3D Button (`sf-btn-3d-danger`), Stock Availability Badge။

### ၉.၄။ သတင်း၊ အကြောင်းအရာနှင့် CMS စာမျက်နှာများ (Content & CMS)
- [ ] **Tech Blog / News List (သတင်းဆောင်းပါးများ စာရင်း):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/blog/index.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/blog/index.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Modern Card Grid, Read Time Badge, Featured Post Hero Card, Category Filter Pills။
- [ ] **Blog Post Detail (ဆောင်းပါး အသေးစိတ်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/blog/show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/blog/show.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Typography Readability (`font-myanmar leading-relaxed`), Social Share Buttons, Related Products 3D Carousel။
- [ ] **Custom CMS Pages (Terms, Privacy, About Us):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/pages/show.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/pages/show.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Clean Markdown/HTML Rendering, Table of Contents Quick Nav, Mobile Ergonomics။

### ၉.၅။ အများသုံး Component များနှင့် Footer (Universal Components & Layouts)
- [x] **Storefront Base Layout (ပင်မ Layout နှင့် Header):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/layouts/storefront/app.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/layouts/storefront/app.blade.php)
  - **လုပ်ဆောင်ချက်များ:** Dynamic Theming Tokens, 3D Bottom Nav Bar, Search Bar Integration, Cart Count Badge, Colorful 3D Action Buttons (Favorites, Language, Cart, Dark Mode, Mobile Menu) ပြီးစီး။
- [ ] **Search Suggestions Dropdown (အမြန် ရှာဖွေမှု အကြံပြုချက်):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/components/search-suggestions-dropdown.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/components/search-suggestions-dropdown.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Highlighting Matched Keywords, Product Thumbnail, Dynamic Price, Arrow Keys Navigation Support။
- [ ] **Direct Viber Order Modal (Viber မှာယူမှု Dialog):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/storefront/components/_viber_order_modal.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/storefront/components/_viber_order_modal.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Pre-filled Customer Phone, Item List Preview, Send Order 3D Button (`sf-btn-3d-success`), QR Code Backup။
- [ ] **Storefront Universal Footer (စတိုးဆိုင် အောက်ခြေပိုင်း):**
  - **ဖိုင်လမ်းကြောင်း:** [`resources/views/components/storefront-footer.blade.php`](file:///d:/xmapp/htdocs/DataPOS/resources/views/components/storefront-footer.blade.php)
  - **ပြင်ဆင်ရန် အဓိကအချက်များ:** Shop Address, Operating Hours, Social Links (Facebook, Viber, Telegram), Payment Partners Badges, Safe Area Inset Padding။

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
| **Global Storefront CSS** | [`resources/css/app.css`](file:///d:/xmapp/htdocs/DataPOS/resources/css/app.css) | `.sf-btn-3d`, `.hero-cat-nav` နှင့် 3D Push Button Utility Classes များ တည်ဆောက်ခြင်း။ |

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


