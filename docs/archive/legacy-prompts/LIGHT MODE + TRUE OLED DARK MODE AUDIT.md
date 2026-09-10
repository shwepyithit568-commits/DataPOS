# LIGHT MODE + TRUE OLED DARK MODE AUDIT

Act as a Senior UI Engineer and Design System Specialist.

Application ရှိ pages/components အားလုံးကို Light Mode နှင့် Dark Mode နှစ်မျိုးစလုံးတွင် audit လုပ်ပြီး အောက်ပါ Design Standard နှင့်မကိုက်ညီသောနေရာများကို ပြင်ပါ။

Do not only change the main page background. Audit all nested components, cards, tables, forms, dropdowns, modals, navigation, sidebar, footer, popovers, dialogs, tooltips, search boxes, empty states and loading states.

## ☀️ LIGHT MODE — High-Contrast Daylight Standard

Main page/background:
`#f4f6f8`

Primary cards / panels / tables:
`#ffffff`

Borders:
`#cbd5e1`
or stronger:
`#94a3b8`

Primary text:
`#0f172a`

Secondary text:
`#1e293b`

Muted/supporting text:
`#334155`

Light Mode တွင်:

- Background များ muddy gray မဖြစ်ရပါ။
- Cards နှင့် panels များ clean white ဖြစ်ရမည်။
- Borders များ washed-out / invisible မဖြစ်ရပါ။
- Text နှင့် icons များ daylight environment တွင်ဖတ်ရလွယ်ရမည်။
- Inputs, selects, tables, dropdowns နှင့် modals များတွင်လည်း contrast တူညီရမည်။

## 🌙 DARK MODE — True OLED Dark Standard

Main background:
`#000000`

Sidebar / major outer surfaces:
`#000000`

Inner cards / panels:
`#0a0f1d`
or
`#111827`

Borders / dividers:
`#1e293b`
or
`#334155`

Primary text:
`#f8fafc`

Secondary text:
`#e2e8f0`

Muted text:
`#cbd5e1`

Dark Mode တွင်:

- Main application surface ကို True OLED Black ဖြစ်စေပါ။
- Gray-looking main background မသုံးပါနှင့်။
- Cards / inner panels များကို main background မှ subtle separation ရှိအောင် Deep Slate သုံးပါ။
- Text readability နှင့် contrast မပျက်စေရပါ။
- Borders များ bright/harsh မဖြစ်စေဘဲ မြင်နိုင်ရမည်။
- Inputs, dropdowns, tables, modals, tooltips, dialogs, toast notifications နှင့် search UI များအားလုံး dark theme support ပြည့်စုံရမည်။

## IMPORTANT

Hard-coded colors များကို component တစ်ခုချင်းစီတွင် random ထည့်မည့်အစား centralized design tokens / CSS variables / theme configuration သုံးပါ။

ဥပမာ semantic tokens:

- `--bg-page`
- `--bg-surface`
- `--bg-elevated`
- `--text-primary`
- `--text-secondary`
- `--text-muted`
- `--border-default`
- `--border-strong`

ရှိပြီးသား design system/theme architecture ရှိပါက အသစ်တစ်ခုထပ်မဆောက်ဘဲ existing architecture ကိုအသုံးပြုပါ။

## ACCESSIBILITY

Light + Dark mode နှစ်မျိုးလုံးတွင်:

- Text contrast
- Button contrast
- Link visibility
- Focus indicators
- Disabled states
- Hover states
- Selected states
- Error / Warning / Success states

များကို accessibility အရစစ်ပါ။

Color တစ်ခုတည်းကို state indicator အဖြစ်မမှီခိုပါနှင့်။

## FINAL VERIFICATION

Page တစ်ခုချင်းစီကို Light Mode + OLED Dark Mode နှစ်မျိုးစလုံးတွင် visual audit လုပ်ပါ။

ပြီးပါက:

1. Theme inconsistencies found
2. Light Mode fixes
3. OLED Dark Mode fixes
4. Contrast/accessibility fixes
5. Components changed
6. Remaining issues

ကို report ပေးပါ။