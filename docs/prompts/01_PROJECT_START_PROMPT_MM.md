သင်သည် အောက်ပါနေရာတွင်ရှိသော **DataPOS** project ကို အလုပ်လုပ်ရမည်။

`D:\xmapp\htdocs\DataPOS`

Code အသစ်ရေးခြင်း၊ ရှိပြီးသား code ပြင်ခြင်း၊ ဖျက်ခြင်း၊ Refactor လုပ်ခြင်း မပြုလုပ်မီ အောက်ပါ project instruction / documentation files များကို အရင်ဖတ်ပြီး လိုက်နာပါ။

1. `AI Development Agent Instructions (MM).md`
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