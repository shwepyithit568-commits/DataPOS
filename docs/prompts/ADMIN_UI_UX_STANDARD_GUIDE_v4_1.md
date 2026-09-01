# DataPOS - Admin UI/UX Standard Guide

**Document Version:** 4.1.0  
**Last Updated:** 2026-09-01  
**System Base:** Laravel 12.64.0, Blade, Alpine.js, Tailwind CSS 4, Vite, PhpSpreadsheet  
**Purpose:** DataPOS Admin & POS Management စာမျက်နှာများကို Myanmar SME users များအတွက် ဖတ်လွယ်၊ သုံးလွယ်၊ အလွန်ကျစ်လစ်သွက်လက်ပြီး Consistent ဖြစ်သော စံသတ်မှတ်ချက်များအတိုင်း ထိန်းသိမ်းရန်။

---

## Core Design Direction

DataPOS admin UI သည် marketing website မဟုတ်ပါ။ ဆိုင်ရှင်၊ Cashier, Warehouse staff, Accountant တို့ နေ့စဉ် အချိန်ပြည့် ပြန်ပြန်သုံးမည့် မြန်ဆန်တိကျသော Operational Work Tool ဖြစ်သည်။

### မဖြစ်မနေ အသုံးပြုရမည့်အချက်များ (Use):
- **Ultra-dense compact layout:** ဒေါင်လိုက်ရော အလျားလိုက်ပါ 2px rhythm ဖြင့် မျက်နှာပြင်တစ်ခုတည်းတွင် အချက်အလက်များစွာ မြင်နိုင်စေခြင်း။
- **Row-based center-aligned stat cards:** အိုင်ကွန်နှင့် အချက်အလက်များကို မျဉ်းတစ်ပြေးတည်း center alignment ထားရှိခြင်း။
- **Interactive inline toolbars:** Search input (`h-7`), Filter pills, Excel export button နှင့် Table/Cards view switcher များ ချိတ်ဆက်ခြင်း။
- **Excel (.xlsx) & CSV exports:** စာရင်းဇယားများကို PhpSpreadsheet ဖြင့် အရောင်/စတိုင်လ်သပ်ရပ်စွာ ထုတ်ယူနိုင်ခြင်း။
- **Readable concise Burmese labels:** ကွင်းစကွင်းပိတ်အပိုများ မပါဘဲ တိုတိုရှင်းရှင်းနှင့် နားလည်လွယ်သော မြန်မာဝေါဟာရများ။
- **Clear tables & responsive card grids:** စာရင်းဇယားများကို စစ်ဆေးရလွယ်ကူပြီး မိုဘိုင်းတွင်လည်း ချောမွေ့စွာ ပြသနိုင်ခြင်း။

### ရှောင်ရှားရမည့်အချက်များ (Avoid):
- Oversized hero sections နှင့် နေရာလွတ် အကျယ်ကြီးဟနေခြင်းများ။
- အဓိပ္ပာယ်မရှိသော Decorative gradient များနှင့် Card အထပ်ထပ် nesting များ။
- Hardcoded labels နှင့် Low-contrast စာသားများ။
- Inline event handlers (CSP ချိုးဖောက်မှုများ)။
- အရေးကြီးသော ခလုတ်များကို မျက်နှာပြင်ပြင်ပသို့ ရောက်သွားစေခြင်း။

---

## Page Layout Standard (Ultra-Dense 2px Rhythm)

Admin child views အားလုံးသည် အောက်ပါ standard layout ဖြင့် တည်ဆောက်ရမည်:

```blade
@extends('layouts.admin.app')

@section('title', __('messages.module_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        viewMode: localStorage.getItem('module_view_mode') || 'table',
        setView(mode) {
            this.viewMode = mode;
            localStorage.setItem('module_view_mode', mode);
        }
     }">
    {{-- 1. Compact Page Header (34px - 38px) --}}
    {{-- 2. Summary Stat Cards (Row-based center alignment) --}}
    {{-- 3. Interactive Toolbar (Search, Filter Pills, Excel, Table/Cards) --}}
    {{-- 4. Main Table View & Responsive Card Grid --}}
    {{-- 5. Pagination / Footer Actions --}}
</div>
@endsection
```

### Layout Rules:
1. **Main Padding:** `@section('main_padding', 'p-0.5 sm:p-1')` သို့မဟုတ် CSS `.admin-dense` ကို အသုံးပြုပါ။
2. **Section Flow:** Direct sibling sections အားလုံးကြားတွင် တိကျသော **`2px` (`space-y-0.5`)** သာ ခြားစေရမည်။
3. **Grid Gaps:** Grids အားလုံး၏ row-gap နှင့် column-gap သည် **`2px` (`gap-0.5 sm:gap-1`)** ဖြစ်ရမည်။
4. **Flat Sections:** Card အထဲတွင် Card ထပ်ထည့်ခြင်း (Nested cards) လုံးဝ မပြုလုပ်ရ။

---

## Header Standard (34px - 38px Compact Height)

Page Header သည် အမြင့် 34px မှ 38px ကြားသာ ရှိရမည်ဖြစ်ပြီး နေရာချွေတာရမည်:

```blade
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
    <div class="flex items-center gap-2.5 min-w-0">
        <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
            📦
        </span>
        <div class="min-w-0">
            <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                <span>{{ __('messages.module_title') }}</span>
                <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">({{ $store->name }})</span>
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                {{ __('messages.module_sub') }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
        <a href="{{ route('store.admin.module.create', $storeRouteParams) }}"
           class="h-7 px-3 rounded-md bg-sky-600 hover:bg-sky-500 text-white text-xs font-black shadow-2xs hover:shadow-sky-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path d="M12 4v16m8-8H4"/>
            </svg>
            <span>{{ __('messages.new_item') }}</span>
        </a>
    </div>
</div>
```

---

## Summary Stat Cards Standard (Row-Based Center Alignment)

KPI နှင့် Summary Stat Cards များသည် အပေါ်အောက် အထပ်ထပ် မဟုတ်ဘဲ ဘေးတိုက် **Row-based Center Alignment** ဖြင့် ပြသရမည်:

```blade
<div class="grid grid-cols-1 sm:grid-cols-3 gap-0.5 sm:gap-1" role="list">
    <div role="listitem" class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
        <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300 shadow-inner text-xs sm:text-sm font-bold">
            📦
        </div>
        <div class="min-w-0">
            <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                {{ number_format($summary['total']) }}
            </div>
            <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                {{ __('messages.total_items') }}
            </p>
        </div>
    </div>
</div>
```

---

## Interactive Inline Toolbar Standard

Toolbar အပိုင်းသည် Search input, Filter pills, Excel export button နှင့် View switcher များကို တစ်ဆက်တည်း ပေါင်းစပ်ထားရမည်:

```blade
<div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
    {{-- Left: Search Bar & Filter Pills --}}
    <div class="flex flex-wrap items-center gap-1.5 flex-1">
        <form method="GET" class="relative min-w-[180px] sm:min-w-[260px] flex-1 max-w-sm">
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="{{ __('messages.search_placeholder') }}..."
                   class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:bg-white dark:focus:bg-slate-900 transition" />
            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
        </form>

        <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
            <a href="{{ route('store.admin.module.index', $storeRouteParams) }}"
               class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer {{ empty($search) ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700' }}">
                {{ __('messages.all') }} ({{ $totalCount }})
            </a>
        </div>
    </div>

    {{-- Right: Excel Export & View Mode Switcher --}}
    <div class="flex items-center gap-1 self-end sm:self-auto">
        @if(!empty($exportUrl))
            <a href="{{ $exportUrl }}"
               title="Export Excel (.xlsx)"
               class="h-6 px-2 rounded text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition inline-flex items-center gap-1 cursor-pointer">
                <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Excel</span>
            </a>
        @endif

        <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
            <button type="button"
                    @click="setView('table')"
                    class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                <span>{{ __('messages.view_table') ?? 'Table' }}</span>
            </button>
            <button type="button"
                    @click="setView('card')"
                    class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                    :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>{{ __('messages.view_cards') ?? 'Cards' }}</span>
            </button>
        </div>
    </div>
</div>
```

---

## Excel & CSV Export Architecture Standard

စာရင်းဇယားများကို Export ပြုလုပ်ရာတွင် အောက်ပါ စံနှုန်းအတိုင်း ရေးဆွဲရမည်:

1. **Format Support:** `.xlsx` (Formatted Excel via PhpSpreadsheet) အား Default ထားရှိပြီး `?format=csv` (UTF-8 BOM Streamed Response) အား Fallback အဖြစ် ပံ့ပိုးပေးရမည်။
2. **Route Definition:** Export route ကို Wildcard Parameter (`{id}` သို့မဟုတ် `{return}`) အပေါ်တွင် ထားရှိရမည်:
   ```php
   Route::get('/returns', [ReturnsController::class, 'index'])->name('pos.returns.index');
   Route::get('/returns/export', [ReturnsController::class, 'export'])->name('pos.returns.export');
   Route::get('/returns/{return}', [ReturnsController::class, 'show'])->name('pos.returns.show');
   ```
3. **Controller Pattern:**
   - Title block (Store Name, Export Date, Total count, Total Amount).
   - Styled header row (Navy/Sky blue background, white bold text, row height 24).
   - Data rows with zebra striping (`#F8FAFC`), borders, and number formatting (`#,##0.00`).
   - Totals footer row with sum formulas or computed values.
   - Auto-sized column widths.

---

## Localization & Myanmar Retail Language Standard

ဘာသာပြန်ခြင်း လုပ်ငန်းစဉ်သည် UI အသုံးပြုရ လွယ်ကူမှုအတွက် အဓိက အရေးပါသော အစိတ်အပိုင်းဖြစ်သည်။ ဘာသာစကား ဖိုင် ၃ ခုလုံးကို အမြဲတမ်း ပြိုင်တူ update ပြုလုပ်ရမည်:
- `lang/my/messages.php` (Burmese - Primary)
- `lang/en/messages.php` (English)
- `lang/zh_CN/messages.php` (Simplified Chinese)

### ၁။ ဘာသာပြန်ခြင်း အခြေခံမူ ၅ ချက် (5 Core Translation Rules)

1. **Concise & Action-Oriented (တိုတိုနှင့် လိုရင်းဖြစ်ရမည်):**
   - စကားလုံး အရှည်ကြီးများနှင့် တရားဝင်ရုံးသုံး အပိုစာသားများကို ရှောင်ရှားပါ။
   - ဥပမာ- `ကုန်ပစ္စည်းအသစ်တစ်ခု ထည့်သွင်းရန်` အစား **`ပစ္စည်းထည့်ရန်`**၊ `လက်ရှိစနစ်အတွင်း အသုံးပြုနိုင်သော Theme များ` အစား **`သုံးနိုင်သော Theme`** ဟု တိုတိုရှင်းရှင်း သုံးပါ။
2. **No Bracketed English Acronyms in UI (ကွင်းစကွင်းပိတ် အပိုများ လုံးဝမထည့်ရ):**
   - UI Badge၊ Pill၊ Label များတွင် `အသုံးပြုနိုင် (Active)` သို့မဟုတ် `ရပ်ဆိုင်း (Deprecated)` ကဲ့သို့ အင်္ဂလိပ်စာလုံး ကွင်းစကွင်းပိတ်များ မထည့်ရ။
   - မြန်မာလိုဆိုလျှင် **`သုံးနိုင်သည်`**၊ အင်္ဂလိပ်လိုဆိုလျှင် **`Active`** ဟု သီးသန့် ရှင်းလင်းစွာ ရေးရမည်။
3. **Retail-Native Terminology (လက်လီလက်ကား လုပ်ငန်းသုံး စကားလုံးများ):**
   - စက်ဘာသာပြန် (Machine Translation) ၏ တိုက်ရိုက်ပြန်ဆိုမှုများကို ရှောင်ရှားပြီး မြန်မာ အရောင်းဝန်ထမ်းနှင့် ဆိုင်ရှင်များ နေ့စဉ်သုံးသော စကားလုံးများကို သုံးစွဲပါ။ (ဥပမာ- `ပြန်အမ်းငွေ`, `ဘောက်ချာ`, `ပစ္စည်းပြန်သွင်းမှု`, `ကျသင့်ငွေ`)။
4. **Preserve English Technical Acronyms (နည်းပညာအတိုကောက်များ မူရင်းအတိုင်းထားခြင်း):**
   - `SKU`, `IMEI`, `SN`, `PIN`, `KPay`, `COD`, `POS`, `Excel`, `CSV` စသည့် စံအတိုကောက်များကို မြန်မာလို အတင်းအကျပ် မပြန်ဆိုဘဲ အင်္ဂလိပ်လို မူရင်းအတိုင်း ထားရမည်။
5. **No Broken Multi-line Layouts (စာကြောင်းမကျိုး၊ မျက်နှာပြင်မပြတ်စေရ):**
   - ဘာသာပြန်ပြီးနောက် Render ထွက်လာသော UI တွင် စာသားရှည်လွန်း၍ Button ပြားများ ကျိုးသွားခြင်း၊ Table Header များ ကွယ်သွားခြင်း မရှိစေရန် စစ်ဆေးရမည်။

---

### ၂။ UI အစိတ်အပိုင်းအလိုက် ဘာသာပြန် စံဇယား (Translation Reference Dictionary)

| အခန်းကဏ္ဍ (Category) | Key | ရှောင်ရှားရမည့်ပုံစံ (Avoid) | စံသတ်မှတ်ချက် (Use) | English |
|---|---|---|---|---|
| **Theme Status** | `theme_status_active` | အသုံးပြုနိုင် (Active) | **သုံးနိုင်သည်** | Active |
| **Theme Status** | `theme_status_deprecated` | ရပ်ဆိုင်းရန်လျာထား (Deprecated) | **ရပ်ဆိုင်းလျာထား** | Deprecated |
| **Theme Status** | `theme_status_hidden` | ဝှက်ထားသည် (Hidden) | **ဝှက်ထားသည်** | Hidden |
| **Theme Counts** | `theme_total_count` | စုစုပေါင်း Theme များ | **စုစုပေါင်း Theme** | Total Themes |
| **Theme Counts** | `theme_active_count` | လက်ရှိသုံးနိုင်သော Theme များ | **သုံးနိုင်သော Theme** | Active Themes |
| **Theme Counts** | `theme_deprecated_count` | ရပ်ဆိုင်းရန် လျာထားသော Theme များ | **ရပ်ဆိုင်းလျာထား** | Deprecated |
| **Theme Counts** | `theme_hidden_count` | ဝှက်ထားသော Theme များ | **ဝှက်ထားသော Theme** | Hidden Themes |
| **Theme Replacement** | `theme_replacement` | အစားထိုးရန် အကြံပြု Theme | **အစားထိုး Theme** | Replacement Theme |
| **Theme Replacement** | `theme_replacement_none` | အစားထိုး မရှိပါ | **မရှိပါ** | None |
| **POS Returns** | `returns_title` | ပစ္စည်း ပြန်သွင်း/ပြန်အမ်းမှုများ | **ပစ္စည်းပြန်အမ်းမှုများ** | Sales Returns |
| **POS Returns** | `returns_sub` | အရောင်းပြန်အမ်းငွေနှင့် ပစ္စည်းပြန်သွင်းမှု မှတ်တမ်းများ | **အရောင်းပြန်အမ်းငွေနှင့် ပစ္စည်းပြန်သွင်းမှုများ** | Sales returns & refunds ledger |
| **POS Returns** | `returns_today` | ယနေ့ ပြန်အမ်းမှုများ | **ယနေ့ ပြန်အမ်းမှု** | Today's Returns |
| **POS Returns** | `new_return` | ပြန်အမ်းမှုအသစ် ပြုလုပ်ရန် | **+ ပြန်အမ်းမှုအသစ်** | + New Return |
| **Actions** | `save` | သိမ်းဆည်းရန် | **သိမ်းမည်** | Save |
| **Actions** | `cancel` | မလုပ်တော့ပါ | **ပယ်ဖျက်** | Cancel |
| **Actions** | `view` / `view_details` | အသေးစိတ်ကြည့်ရှုရန် | **အသေးစိတ်** | Details |
| **Actions** | `refund` | ငွေပြန်အမ်းပေးရန် | **ငွေပြန်အမ်းမည်** | Process Refund |
| **View Modes** | `view_table` | ဇယားဖြင့်ကြည့်မည် | **Table** / **ဇယား** | Table |
| **View Modes** | `view_cards` | ကတ်ပြားဖြင့်ကြည့်မည် | **Cards** / **ကတ်ပြား** | Cards |
| **Exports** | `export_excel` | Excel ဖိုင် ထုတ်ယူရန် | **Excel** | Excel |

---

## Audit & Verification Checklist

- [ ] `@section('main_padding', 'p-0.5 sm:p-1')` သတ်မှတ်ထားခြင်း။
- [ ] Section တစ်ခုနှင့်တစ်ခုကြား 2px (`space-y-0.5`) သာ ခြားစေခြင်း။
- [ ] Header သည် 34px-38px အမြင့်ဖြင့် Compact ဖြစ်ခြင်း။
- [ ] Summary Stat Cards များသည် Row-based Center Alignment ဖြင့် အိုင်ကွန်နှင့် စာသားများ တစ်တန်းတည်းရှိခြင်း။
- [ ] Search Input အမြင့်သည် `h-7` ဖြစ်ပြီး Icon ပါဝင်ခြင်း။
- [ ] Excel Export ခလုတ် ပါဝင်ပြီး `.xlsx` နှင့် `.csv` နှစ်မျိုးစလုံး Download ပြုလုပ်နိုင်ခြင်း။
- [ ] Table/Cards View Switcher ချိတ်ဆက်ထားပြီး LocalStorage ဖြင့် ရွေးချယ်မှု မှတ်သားနိုင်ခြင်း။
- [ ] မြန်မာစာသားများသည် တိုတိုရှင်းရှင်းနှင့် သဘာဝကျသော ဝေါဟာရများ ဖြစ်ခြင်း (ကွင်းစကွင်းပိတ်အပိုများ မပါရှိခြင်း)။
- [ ] ဘာသာစကားဖိုင် ၃ ခု (`lang/my`, `lang/en`, `lang/zh_CN`) အပြည့်အစုံ update ပြုလုပ်ထားခြင်း။
- [ ] `php artisan test` အောင်မြင်ပြီး `npm run build` ပြုလုပ်ပြီးစီးခြင်း။
