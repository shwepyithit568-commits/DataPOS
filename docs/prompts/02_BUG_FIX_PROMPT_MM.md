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