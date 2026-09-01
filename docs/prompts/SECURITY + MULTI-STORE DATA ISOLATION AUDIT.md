# SECURITY + MULTI-STORE DATA ISOLATION AUDIT

This is a security-critical task.

Store / tenant တစ်ခုချင်းစီ၏ data isolation နှင့် authorization ကို end-to-end audit လုပ်ပါ။

Store Manager သည် မိမိ store ၏:

- Staff
- Products
- Inventory
- Sales
- POS transactions
- Customers
- Reports
- Settings

များကိုသာ access လုပ်နိုင်ရမည်။

Frontend filtering ကို security boundary အဖြစ်မယုံကြည်ပါနှင့်။

Backend/API/database query level တွင် store/tenant scope enforce လုပ်ပါ။

Direct API calls, manipulated IDs နှင့် direct URLs ဖြင့် အခြား store records ကို:

- Read
- Create
- Update
- Delete

မလုပ်နိုင်ကြောင်း verify လုပ်ပါ။

RBAC, IDOR/BOLA, tenant isolation နှင့် privilege escalation issues များကိုစစ်ပါ။

Super Admin ကဲ့သို့ explicitly authorized role မဟုတ်လျှင် cross-store access မရစေရပါ။

Automated authorization tests ထည့်နိုင်ပါကထည့်ပါ။

Existing production data ကိုမပျက်စေပါနှင့်။

Findings ကို:

Critical / High / Medium / Low

ဖြင့် severity သတ်မှတ်ပြီး root cause + fix + verification results ပေးပါ။