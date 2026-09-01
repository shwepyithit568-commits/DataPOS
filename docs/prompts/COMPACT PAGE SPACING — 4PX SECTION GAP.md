# COMPACT PAGE SPACING — 4PX SECTION GAP

Application ရှိ pages အားလုံး၏ vertical spacing ကို audit လုပ်ပြီး compact, information-dense layout ဖြစ်အောင်ပြင်ပါ။

Primary requirement:

Page-level sibling sections တစ်ခုနှင့်တစ်ခုကြား default visual gap ကို approximately `4px` ဖြစ်အောင် normalize လုပ်ပါ။

ဥပမာ:

Banner
↓ 4px
Search
↓ 4px
Filters
↓ 4px
Content / Table / Grid
↓ 4px
Pagination / Footer actions

Unnecessary:

- `margin-top`
- `margin-bottom`
- large `gap`
- nested container padding
- duplicate spacing

များကြောင့် section တွေ အလွန်ဝေးနေခြင်းကိုဖယ်ရှားပါ။

IMPORTANT:

`4px` rule သည် page-level sections ကြား compact spacing အတွက်ဖြစ်သည်။

Text readability, form usability နှင့် touch accessibility လိုအပ်သော internal component spacing များကို အတင်း `4px` မလုပ်ပါနှင့်။

Buttons, inputs, cards, tables နှင့် touch controls အတွင်း padding များကို usable ဖြစ်အောင်ထိန်းထားပါ။

Spacing system ရှိပါက reusable spacing token သုံးပါ။

ဥပမာ:
`--space-section: 4px`

Page တစ်ခုချင်းစီတွင် random margin overrides မထည့်ပါနှင့်။

Mobile / Tablet / Desktop အားလုံးတွင် section rhythm တူညီမှုရှိကြောင်း verify လုပ်ပါ။

အထူးသဖြင့်:

- Header → Content
- Banner → Search
- Search → Filters
- Filters → Grid/Table
- Section heading → Section content
- Pagination → Content

တို့ကိုစစ်ပါ။

ပြီးပါက spacing inconsistencies နှင့် ပြင်ခဲ့သော shared styles/components များကို report ပေးပါ။