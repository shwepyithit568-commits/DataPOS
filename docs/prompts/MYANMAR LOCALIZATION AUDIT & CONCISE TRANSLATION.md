# MYANMAR LOCALIZATION AUDIT & CONCISE TRANSLATION

Application ရှိ Myanmar localization / translation strings အားလုံးကို audit လုပ်ပါ။

Goal:

မြန်မာဘာသာ UI စာသားများကို သဘာဝကျ၊ နားလည်လွယ်၊ တိုတောင်းပြီး UI တွင်ဖတ်ရှုရလွယ်သော Burmese wording ဖြစ်အောင်ပြင်ပါ။

Literal word-for-word translation မလုပ်ပါနှင့်။

English source ၏ meaning နှင့် action intent ကိုမပြောင်းဘဲ concise Burmese UI language အဖြစ်ပြန်ရေးပါ။

ဥပမာအားဖြင့် ရှည်လွန်းသော:

“ကုန်ပစ္စည်းအသစ်တစ်ခု ထည့်သွင်းရန်”

ကို context မှန်ပါက:

“ပစ္စည်းထည့်ရန်”

ကဲ့သို့တိုတောင်းစေနိုင်သည်။

အထူးသဖြင့်:

- Navigation labels
- Buttons
- Form labels
- Table headers
- Settings
- Tooltips
- Dialog titles
- Confirmation messages
- Validation messages
- Empty states
- Search placeholders
- POS actions
- Inventory actions
- Staff management
- Reports
- Footer

တို့ကိုစစ်ပါ။

Rules:

- Meaning မပျောက်စေရပါ။
- Technical/business terminology မမှားစေရပါ။
- Button labels များကို action-oriented ဖြစ်စေပါ။
- UI label တွင်မလိုအပ်သော polite/formal filler စကားလုံးများရှောင်ပါ။
- တူညီသော concept ကို application တစ်ခုလုံးတွင် translation တစ်မျိုးတည်းသုံးပါ။
- English စကားလုံးကို မြန်မာလိုပြောင်းခြင်းကြောင့် ပိုရှုပ်သွားပါက အသုံးများသော technical term ကို context အရဆက်ထားနိုင်သည်။
- Existing translation keys များကို မလိုအပ်ဘဲ rename မလုပ်ပါနှင့်။
- Hard-coded Burmese / English UI strings များရှိမရှိရှာပါ။
- Missing keys နှင့် duplicate translations များကိုရှင်းပါ။
- Variables/placeholders (`{name}`, `{count}`, `%s` etc.) မပျက်စေရပါ။
- Plural/count logic ရှိပါက behavior မပျက်စေရပါ။

Localization ပြောင်းပြီးနောက် Myanmar UI ကို actual rendered layout ဖြင့်စစ်ပါ။

အထူးသဖြင့်:

- Text overflow
- Button wrapping
- Truncation
- Table header width
- Mobile navigation
- Modal width
- Form alignment

များကိုစစ်ပြီး လိုအပ်သလို responsive UI ကိုပါပြင်ပါ။

ပြီးပါက:

1. Translation keys changed
2. Long translations shortened
3. Inconsistent terminology corrected
4. Hard-coded strings found/fixed
5. Missing translations fixed
6. Remaining localization issues

ကို report ပေးပါ။