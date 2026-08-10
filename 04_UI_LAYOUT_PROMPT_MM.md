# 🎨 DataPOS — UI / Layout ပြင်ဆင်ခြင်း Prompt

ဒီ Page / UI Layout ကို **DataPOS ရဲ့ ရှိပြီးသား Design System, Blade Components, Alpine.js Logic နှင့် Tailwind CSS Pattern များကို အခြေခံပြီး** ပြင်ဆင်ပါ။

UI လှအောင်ပြင်ရုံသာ မဟုတ်ဘဲ Existing Functionality မပျက်စေရန် ဦးစားပေးပါ။

---

## 1. Coding မစမီ

အရင်ဆုံး အောက်ပါ Documentation များကို သက်ဆိုင်ရာအတိုင်း စစ်ပါ။

1. `2026-08-02_FIXES.md`
2. `Source_of_Truth.md`
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

`2026-08-02_FIXES.md`

ကို Item အသစ်ဖြင့် update လုပ်ပါ။

Bug Fix နှင့်ဆိုင်ပါက—

`Testing_check.md`

ကိုပါ update လုပ်ပါ။

Business Rule / Architecture Rule မပြောင်းပါက—

`Source_of_Truth.md`

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
- `2026-08-02_FIXES.md` — Item 116 added

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