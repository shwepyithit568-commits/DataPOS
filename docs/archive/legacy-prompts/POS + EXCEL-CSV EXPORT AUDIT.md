# POS + EXCEL/CSV EXPORT AUDIT

POS system နှင့် Excel/CSV exports ကို production-readiness အတွက် end-to-end audit လုပ်ပါ။

POS တွင်:

- Product selection
- Cart
- Quantity
- Discount
- Tax
- Subtotal
- Total
- Payment
- Change
- Stock deduction
- Receipt
- Refund
- Void/Cancel
- Duplicate transaction prevention
- Store isolation

တို့ကိုစစ်ပါ။

Currency/decimal calculations မှန်ကန်မှုကို verify လုပ်ပါ။

Excel/CSV တွင်:

- Headers
- Data mapping
- UTF-8 / Myanmar Unicode
- Currency
- Date/time
- Timezone
- Filters
- Store isolation
- Large datasets
- Empty datasets
- CSV escaping
- Quotes/newlines/commas
- Excel formula injection

တို့ကိုစစ်ပါ။

တွေ့ရှိသော bug များကို root cause အထိပြင်ပြီး automated tests ထည့်နိုင်သည့်နေရာတွင်ထည့်ပါ။

ပြင်သင့်တာ၊ တိုးသင့်တာ၊ ရှင်းသင့်တာများရှိပါက production-safe implementation ဖြစ်မှသာလုပ်ပါ။

ပြီးပါက POS နှင့် Export ကို သီးခြား test report ပြန်ပေးပါ။