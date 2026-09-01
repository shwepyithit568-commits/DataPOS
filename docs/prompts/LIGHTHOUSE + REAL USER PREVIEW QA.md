# LIGHTHOUSE + REAL USER PREVIEW QA

Application ကို production မတင်မီ QA audit လုပ်ပါ။

Lighthouse ကို Mobile + Desktop နှစ်မျိုးစလုံးတွင် relevant public/admin pages အတွက်စစ်ပါ။

Report:

- Performance
- Accessibility
- Best Practices
- SEO where relevant
- LCP
- CLS
- Blocking resources
- Image issues
- JavaScript issues
- Accessibility violations

ထို့နောက် live/dev preview ကို real shopper တစ်ယောက်ကဲ့သို့အသုံးပြုပြီး:

Homepage → Navigation → Search → Category → Product → Add to Cart → Cart → Checkout entry

flow ကိုစမ်းပါ။

ထို့အပြင်:

- Mobile
- Tablet
- Desktop
- Light Mode
- OLED Dark Mode
- Myanmar
- English

တို့ကိုစစ်ပါ။

Broken flow, rendering glitch, overflow, dead button, loading issue, translation issue, layout shift များကို severity + reproduction steps ဖြင့် report ပေးပါ။

ဖြစ်နိုင်သည့် issue ကို fix လုပ်ပြီးမှ re-test လုပ်ပါ။

မစစ်ရသေးသောအရာကို Passed ဟုမသတ်မှတ်ပါနှင့်။