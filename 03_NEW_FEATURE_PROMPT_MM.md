ဒီ Feature အသစ်ကို **DataPOS ရဲ့ ရှိပြီးသား architecture နှင့် patterns ကို Reuse လုပ်ပြီး** Production-ready အဖြစ် တည်ဆောက်ပါ။

Coding မစမီ—

1. `2026-08-02_FIXES.md` ထဲမှာ ဆင်တူ implementation ရှိမရှိ ရှာပါ။
2. `Source_of_Truth.md` ထဲမှာ သက်ဆိုင်ရာ business / architecture rules ကို စစ်ပါ။
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
- Business/Architecture Rule ပြောင်းမှသာ `Source_of_Truth.md` ကို update လုပ်ပါ

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