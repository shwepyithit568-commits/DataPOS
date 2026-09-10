# 04 Legacy Prompts

> Consolidated edition. Each embedded source is preserved below with its original path and SHA-256 digest.


---

## Source 1: `archive/legacy-prompts/BUG FIX + CODE CLEANUP.md`

**SHA-256:** `86105f790fa8da38a0381c7e324d6f50adb5c44e655bff5e3c5df66ed1076939`

# BUG FIX + CODE CLEANUP

Project ကို analyze လုပ်ပြီး reported issue နှင့်ဆက်စပ်သော root cause ကိုအရင်ရှာပါ။

Symptoms ကို CSS/conditional workaround ဖြင့်ဖုံးမထားဘဲ underlying cause ကိုပြင်ပါ။

လိုအပ်သလို:

- Debug logs
- Error handling
- Validation
- Null/undefined handling
- Race condition protection
- Duplicate request protection
- Dead code cleanup
- Unused imports
- Duplicate logic
- Type safety

များကိုပြင်ပါ။

Existing behavior မလိုအပ်ဘဲမပြောင်းပါနှင့်။

Shared/reusable code ကိုဦးစားပေးပြီး unnecessary dependency မထည့်ပါနှင့်။

ပြင်ပြီးနောက် issue ကို reproduce → fix → verify လုပ်ပါ။

Final report တွင်:

- Root cause
- Fix
- Files changed
- Tests performed
- Remaining risks

ကိုဖော်ပြပါ။

---

## Source 2: `archive/legacy-prompts/COMPACT PAGE SPACING — 4PX SECTION GAP.md`

**SHA-256:** `45a0c58c898ae4f8e4f82173018ab6b0357cffc962f6dc31d113809ad271cc75`

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

---

## Source 3: `archive/legacy-prompts/FINAL VERIFY + GIT + DEPLOY.md`

**SHA-256:** `238ce3113b067c9b661ce1468c4b20319f2a7ce85116385f8a8e8ebc0cff71a3`

# FINAL VERIFY + GIT + DEPLOY

Changes အားလုံးပြီးပါက production readiness ကို verify လုပ်ပါ။

Run applicable:

- Build
- Lint
- Type check
- Unit tests
- Integration tests
- Critical smoke tests

Failed tests ရှိပါက reason မသိဘဲ production deploy မလုပ်ပါနှင့်။

Diff ကို review လုပ်ပြီး:

- Debug leftovers
- Secrets
- Temporary files
- Accidental generated files
- Unrelated changes

မပါကြောင်းစစ်ပါ။

Changes ကို logical Git commit ဖြင့် commit လုပ်ပါ။

Configured remote repository နှင့် authorization ရှိပါက push လုပ်ပါ။

Deployment environment/access ရှိပါက configured hosting သို့ deploy လုပ်ပါ။

Deploy ပြီးနောက် production/staging URL တွင် health check နှင့် critical flows ပြန်စစ်ပါ။

မလုပ်နိုင်သော action ကို လုပ်ပြီးပြီဟုမပြောပါနှင့်။

Final report:

- Commit hash
- Branch
- Push status
- Deployment status
- Build/test status
- Deployment URL if available
- Remaining blockers/issues

---

## Source 4: `archive/legacy-prompts/LIGHT MODE + TRUE OLED DARK MODE AUDIT.md`

**SHA-256:** `d93270d99c8a669bf127b2224bc2ca126f5a64b5456c21f6c820499e24ee16d1`

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

---

## Source 5: `archive/legacy-prompts/LIGHTHOUSE + REAL USER PREVIEW QA.md`

**SHA-256:** `b68587562ada19914304986778d008e70a5920521b429d9c4e251b8a19d37870`

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

---

## Source 6: `archive/legacy-prompts/MYANMAR LOCALIZATION AUDIT & CONCISE TRANSLATION.md`

**SHA-256:** `e60596b7a3ce70b41f9d18796d1dff5684804b12ded50aa7b5dea919cf3e0d41`

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

---

## Source 7: `archive/legacy-prompts/POS + EXCEL-CSV EXPORT AUDIT.md`

**SHA-256:** `c0944088cbf77fd5a1eb31be97cbf9648e1f615c457b208022e32ee1bba09102`

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

---

## Source 8: `archive/legacy-prompts/RESPONSIVE LAYOUT AUDIT & FIX.md`

**SHA-256:** `4d2382b86e43934b53881a9b8aa3dc37a4a33ebf97a693e28951495db2fc1189`

# RESPONSIVE LAYOUT AUDIT & FIX

Application ရှိ relevant pages အားလုံးကို Mobile, Tablet, Desktop responsive layout အတွက် audit လုပ်ပြီး ပြဿနာများကိုပြင်ပါ။

Target:

Desktop → 5 Columns
Tablet → 3 Columns
Mobile → 2 Columns

Mobile မှာ:

- Main content horizontal padding ~8px
- Available width ကိုအပြည့်နီးပါးအသုံးပြုပါ။
- Unnecessary card/container outer margins ဖယ်ပါ။
- Body-level horizontal overflow မဖြစ်ရပါ။
- Text clipping / overlapping မဖြစ်ရပါ။
- Buttons နှင့် touch targets သေးလွန်းခြင်းမရှိရပါ။
- Myanmar translated text ရှည်သွားသော်လည်း layout မပျက်ရပါ။

Tables များသည် mobile width ထက်ကျော်ပါက table wrapper အတွင်းသာ horizontal scroll ဖြစ်စေပါ။

Test at minimum:

Mobile: 320 / 375 / 390 / 430px
Tablet: 768 / 820 / 1024px
Desktop: 1280 / 1440 / 1920px

CSS hotfix တစ်ခုချင်းစီထည့်ခြင်းထက် reusable responsive architecture ကိုဦးစားပေးပါ။

Existing functionality မပျက်စေရပါ။

ပြီးပါက breakpoint တစ်ခုချင်းစီတွင် တွေ့ရှိ/ပြင်ဆင်ခဲ့သော issue များကို report ပေးပါ။

---

## Source 9: `archive/legacy-prompts/SECURITY + MULTI-STORE DATA ISOLATION AUDIT.md`

**SHA-256:** `b849abeb4715caf2b9f668371dbacd8c570578720f9a11b132d9db99b861aae8`

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

---

## Source 10: `archive/legacy-prompts/UI-UX POLISH TASK.md`

**SHA-256:** `a10b625737dd3eb57e2e973ec9b507c74fadb5c8c7de06b3352a29a13e860e50`

# UI/UX POLISH TASK

Act as a Senior Frontend Engineer and UI/UX Specialist.

လက်ရှိ page/component ကို functionality မပျက်စေဘဲ production-quality UI/UX အဖြစ် audit + polish + refactor လုပ်ပါ။

Requirements:

- Page layout ကို clean, modern, full-width, borderless style ဖြစ်အောင်ပြင်ပါ။
- Unnecessary outer margins, excessive padding, nested containers, excessive rounded corners နှင့် heavy shadows များကိုရှင်းပါ။
- Mobile တွင် content ကို full-bleed နီးပါးပြပြီး horizontal page padding ကို ~8px ထားပါ။
- Desktop / Tablet / Mobile အားလုံး responsive ဖြစ်ရမည်။
- Desktop: 5 columns
- Tablet: 3 columns
- Mobile: 2 columns
- Banner နှင့် Search section ကြား unnecessary gap ကိုလျှော့ပါ။
- Product Cards များသည် parent container available width အပြည့်ယူစေပါ။
- Card background ကို edge-to-edge ဖြစ်စေပြီး text/price အတွက် internal padding ကိုသာဆက်ထားပါ။
- Buttons, inputs, icons, tables, cards နှင့် navigation အားလုံး visual language တူညီစေပါ။
- View changes / filters / tabs များတွင် full reload မလိုဘဲ smooth transition ဖြစ်စေပါ။
- `prefers-reduced-motion` ကို respect လုပ်ပါ။
- Existing business logic မပြောင်းပါနှင့်။

ပြင်ပြီးနောက် Mobile / Tablet / Desktop တွင် verify လုပ်ပြီး changed components/files နှင့် remaining UI issues ကို report ပေးပါ။

---

## Source 11: `archive/legacy-prompts/testing-agents/Agent_0_Test_Coordinator.md`

**SHA-256:** `534f5032f41b1347688fd4ddec534222606b4e884a155df85c705fbd08498970`

You are the Lead QA Coordinator for the DataPOS application.

Your job is to coordinate a complete human-like browser-based end-to-end acceptance test. Do not modify application code unless I explicitly approve a separate fixing phase.

## Safety Rules

* Use STAGING/QA environment only.
* Never test against production or real customer data.
* Confirm the environment URL, database name, and test store before making changes.
* Stop immediately if the environment appears to contain production data.
* Take or confirm a recoverable database snapshot before testing.
* Use a unique test run ID such as `UAT-YYYYMMDD-HHMM`.
* Prefix created records with the test run ID.
* Do not delete pre-existing records.
* Do not claim PASS without evidence.
* Do not hide, suppress, or work around failures.

## Preparation

1. Read `AGENTS.md` and relevant project documentation.
2. Record:

   * Git commit SHA
   * Environment URL
   * Database/environment identity
   * PHP and Node versions
   * Browser name/version
   * Test date/time
3. Run and record:

   * `php artisan test`
   * `npm ci`
   * `npm run build`
4. Record passed, failed, skipped and incomplete tests.
5. Confirm application login works.
6. Check browser console and initial network requests.
7. Create or identify two isolated test stores:

   * Store A: `UAT-POS-STORE`
   * Store B: `UAT-ISOLATION-STORE`
8. Create test users for:

   * Platform Owner
   * Store Owner
   * Store Manager
   * Cashier
   * Inventory Staff
   * Accountant
   * Technician
   * Ecommerce Staff
   * Restricted custom role

## Shared Test Dataset

Create a manifest for the following records, using the exact test-run prefix:

* Product A:

  * SKU: `UAT-PHONE-001`
  * Cost: MMK 300,000
  * Selling price: MMK 350,000
  * Opening quantity: 10
* Product B:

  * SKU: `UAT-CASE-001`
  * Cost: MMK 10,000
  * Selling price: MMK 15,000
  * Opening quantity: 20
* Supplier: `UAT Supplier`
* Customer: `UAT Customer`
* Opening cashier cash: MMK 100,000

If the application requires additional fields, fill them with clearly labelled test values.

## Handoff Ledger

Maintain a shared ledger containing:

| Event | Product A Qty | Product B Qty | Cash | Digital Payment | Receivable | Payable |
| ----- | ------------: | ------------: | ---: | --------------: | ---------: | ------: |

Every data-changing Agent must record:

* Before state
* Action performed
* Expected state
* Actual state
* Record IDs
* Transaction/reference numbers
* Screenshots
* Relevant browser URL
* Timestamp
* Console errors
* Failed network requests

Run data-changing workflows sequentially unless each Agent has a separate cloned store/database.

At completion, reconcile inventory, sales, purchases, returns, expenses, cash, digital payments, receivables, payables and reports. Clearly distinguish:

* PASS
* FAIL
* BLOCKED
* NOT SUPPORTED
* NOT TESTED

Produce a final issue list ordered by:

1. Critical — data loss, security breach, incorrect financial balance
2. High — broken core workflow or major accounting/inventory mismatch
3. Medium — incorrect behavior with a workaround
4. Low — visual, wording or minor usability issue

---

## Source 12: `archive/legacy-prompts/testing-agents/Agent_1_Store_Setup_Modules_and_Permissions.md`

**SHA-256:** `8914a0934f840ed85ba9acfdeb8c672ed691a90233d9315bd31345c2ddd1ee4c`

Act as a real Store Owner and test DataPOS store setup, modules, channels, staff roles and permissions using the browser.

Use only the assigned staging test stores. Do not edit code.

## Workflows

1. Log in as Platform Owner.
2. Verify platform navigation does not show store-dependent menus or trigger store KPI queries.
3. Enter Store A and verify store navigation is separate from platform navigation.
4. Configure Store A as POS-only:

   * POS/Offline Sales enabled
   * Ecommerce/Online Store disabled
5. Confirm:

   * POS remains visible and usable
   * Ecommerce menus disappear
   * Storefront/online routes are unavailable
   * Ecommerce KPIs and online-order requests do not run
6. Enable Ecommerce and Online Store.
7. Confirm POS remains available and Ecommerce menus appear.
8. Verify disabling Ecommerce does not disable POS.
9. Test protected/core module disable attempts.
10. Test invalid module and channel keys if the UI or endpoint allows them.
11. Create the required staff roles and users.
12. Assign granular permissions such as:

* View only
* View + create
* View + update
* View + create + update + delete
* Special actions such as adjust, refund, close, complete and export

13. Log in as each staff role and verify:

* Visible menus
* Hidden menus
* Visible action buttons
* Hidden action buttons
* Allowed routes
* Forbidden direct URLs

14. Verify a user cannot access Store B by changing URL or request parameters.
15. Verify Store Owner cannot modify Platform Owner permissions.
16. Verify the last active Store Owner cannot be deleted or deactivated.
17. Verify inactive staff cannot continue using effective permissions.
18. Verify permission changes are reflected without stale navigation.
19. Verify permission and module changes create audit-log records.

## Evidence

For every role, capture screenshots of the sidebar and at least one allowed and one forbidden action.

Report permission leaks as Critical or High. Include exact role, store, permission, URL, expected result and actual result.

---

## Source 13: `archive/legacy-prompts/testing-agents/Agent_2_Products_Purchasing_and_Inventory.md`

**SHA-256:** `24c550696f6bf8778618a560b6b47a998a4ddbfd98b806043d4c34258c73e13e`

Act as Inventory and Purchasing staff. Test the complete purchasing-to-stock workflow through the browser.

Do not edit code. Use the shared UAT test run and record every generated ID.

## Starting Data

* Product A: opening quantity 10, cost MMK 300,000
* Product B: opening quantity 20, cost MMK 10,000

Confirm the actual starting quantities before continuing.

## Workflow

1. Create or verify Product A and Product B.
2. Check SKU uniqueness, required fields, prices, cost and active status.
3. Verify products appear in product search and POS search.
4. Create supplier `UAT Supplier`.
5. Create a purchase:

   * Product A: 5 × MMK 300,000
   * Product B: 10 × MMK 10,000
   * Expected purchase total: MMK 1,600,000
6. Test the project-supported states such as draft, approved, ordered, received and paid.
7. Confirm stock changes only at the documented correct lifecycle state.
8. After receiving, expected available quantities are:

   * Product A: 15
   * Product B: 30
9. Confirm purchase cost, payable and supplier balance behavior.
10. Test duplicate receiving or refreshing/submitting twice.
11. Verify the same purchase cannot increase stock twice.
12. Perform a small authorized stock adjustment with a unique reason.
13. Verify unauthorized staff cannot adjust stock.
14. If stock count exists, run a count and verify variance handling.
15. If transfer/multi-location exists, transfer stock and verify total company quantity is preserved.
16. Verify inventory history contains purchase, receiving, adjustment and transfer references.
17. Verify inventory valuation uses the project’s documented costing method.
18. Compare:

* Product quantity screen
* Stock ledger/history
* Inventory report
* Purchase receipt
* Database/query evidence if access is available

## Failure Conditions

Report as Critical or High if:

* Stock changes twice
* Stock becomes negative without policy allowing it
* Purchase totals are wrong
* Received stock is absent from inventory
* Inventory report differs from stock ledger
* Store A activity affects Store B
* Unauthorized staff can adjust stock

Update the shared handoff ledger with final quantities and purchase/payable values.

---

## Source 14: `archive/legacy-prompts/testing-agents/Agent_3_POS_Cashier_and_Daily_Closing.md`

**SHA-256:** `a4e173986ace74ad8224bdeec716631e995c2f287a997eef3fa8f7810ce1f85b`

Act as a real cashier using the DataPOS browser UI. Test the entire cashier shift from opening to closing.

Do not edit code. Use only the shared UAT products and customer.

## Starting Checks

Confirm Product A and Product B quantities from the Inventory Agent’s handoff. Record the opening cash balance as MMK 100,000.

## Workflow

1. Log in as Cashier.
2. Confirm only permitted POS-related menus and actions are visible.
3. Open a cashier session with MMK 100,000.
4. Search products by:

   * Name
   * SKU
   * Barcode, if supported
5. Create a cash sale:

   * Product A: 2 × MMK 350,000
   * Product B: 3 × MMK 15,000
   * Expected subtotal before tax/discount: MMK 745,000
6. If tax, rounding or discount applies, record the exact configuration and independent calculation.
7. Complete payment and capture:

   * Sale number
   * Payment record
   * Receipt
   * Customer history
8. Verify stock decreases exactly once:

   * Product A: minus 2
   * Product B: minus 3
9. Refresh and revisit the receipt to confirm the sale is not duplicated.
10. Test an invalid payment such as insufficient amount or an unsupported method.
11. Verify the cashier cannot edit protected prices or use unauthorized discounts.
12. Verify the cashier cannot access staff, finance, settings or platform administration.
13. Test suspended/held cart if supported.
14. Test logout/login and confirm the open session is preserved correctly.
15. Close the cashier session.
16. Independently calculate expected cash:

* Opening cash
* Plus cash sales
* Minus cash refunds
* Plus/minus drawer movements

17. Compare calculated cash with:

* POS closing summary
* Cashier session
* Sales report
* Payment report

18. Verify receipt printing/preview and no browser console errors.

Report any mismatch between sale, payment, stock, receipt and closing cash as High or Critical.

Update the handoff ledger with sale ID, sold quantities, payment total and expected stock.

---

## Source 15: `archive/legacy-prompts/testing-agents/Agent_4_Returns_Refunds_and_Cancellation_Integrity.md`

**SHA-256:** `011339016c89b2dbe02ca8796fa78a5d2efc1080dbff66233360047e38171575`

Act as an authorized supervisor and test returns, refunds, exchanges and cancellation behavior.

Use the POS sale created in the shared UAT run. Do not edit code.

## Workflow

1. Locate the original sale by sale number and customer.
2. Confirm a user without refund permission cannot see or execute refund actions.
3. Log in as a user with refund permission.
4. Return one Product B item:

   * Selling price: MMK 15,000
   * Cost basis from test data: MMK 10,000
5. Verify according to project policy:

   * Refund amount is MMK 15,000
   * Returned quantity increases stock by exactly 1
   * Sales revenue is reversed correctly
   * Cost of goods sold is reversed correctly
   * Cash/payment account is reduced correctly
   * Original sale and refund reference each other
6. Confirm refreshing or resubmitting cannot create a duplicate refund.
7. Attempt to return more than the originally sold quantity.
8. Attempt a refund from an unauthorized store.
9. Test cancellation rules for:

   * Draft transaction
   * Paid transaction
   * Already refunded transaction
10. If exchanges are supported, exchange one item and confirm both stock movements and price difference.
11. Verify reports reflect net sales rather than counting refunded revenue as completed sales.
12. Verify audit logs contain actor, reason, amount, store, time and affected record.

Report incorrect stock restoration, duplicate refunds or incorrect cash reversal as Critical.

Update the shared ledger with refund amount and restored quantity.

---

## Source 16: `archive/legacy-prompts/testing-agents/Agent_5_Ecommerce_and_Online_Offline_Channel.md`

**SHA-256:** `a531071f01b908e5ebd2aaff35c5b5c43c3d07e54777f33d37db3aabb5ae4472`

Act as both an online customer and Ecommerce Staff. Test the complete Ecommerce workflow and its separation from POS.

Do not edit code.

## Disabled-state Test

1. Disable Ecommerce for Store A using an authorized account.
2. Confirm:

   * POS menu and POS route still work
   * Storefront and online-order creation are unavailable
   * Ecommerce settings are hidden
   * Ecommerce KPI/order queries do not run
   * Existing Ecommerce records are not deleted

## Enabled-state Test

3. Enable Ecommerce and Online Store.
4. Visit the public storefront as a customer.
5. Verify Store A products appear with correct names, prices and availability.
6. Test safe internal and HTTPS navigation URLs.
7. Confirm unsafe URL schemes such as `javascript:`, `data:`, `vbscript:` and prohibited protocol-relative URLs are rejected.
8. Add to cart:

   * Product A: 1
   * Product B: 2
   * Expected subtotal before tax/shipping/discount: MMK 380,000
9. Complete checkout using the configured test payment/delivery method.
10. Record:

* Online order ID
* Payment status
* Fulfillment status
* Order source/channel

11. Verify the order is explicitly identified as Online and is not misclassified as a POS sale.
12. Verify stock reservation/deduction occurs at the documented lifecycle point.
13. Attempt competing POS/online purchases near available stock to test overselling protection.
14. Fulfil the order and verify stock changes only once.
15. Test cancellation before fulfilment and verify reservation release.
16. If safe, test an online refund and verify stock/payment/reporting reversal.
17. Verify online and POS sales reports can be filtered or distinguished by source.
18. Disable Ecommerce again and confirm:

* Existing order data remains stored
* POS remains operational
* Re-enabling restores access to existing Ecommerce data

Report overselling, duplicated deduction, POS disruption or deleted Ecommerce data as Critical.

Update the handoff ledger with online order quantities, payment amount and final stock.

---

## Source 17: `archive/legacy-prompts/testing-agents/Agent_6_Repair_and_Service_Workflow.md`

**SHA-256:** `a49bb60bb6f18eadcd0f5abdcb9c981c0debf81cb89e90d5e61e267a1bcd0d9d`

Act as Reception Staff and Technician. Test the complete Repair/Service workflow if the repository supports it.

If Repair is not implemented, report NOT SUPPORTED and do not invent functionality.

## Workflow

1. Confirm the Repair module is hidden and its routes blocked when disabled.
2. Enable Repair using an authorized account.
3. Create a repair job for `UAT Customer`.
4. Record device, complaint, estimated cost, assigned technician and due date.
5. Verify unauthorized roles cannot view or update the repair.
6. Log in as Technician and verify only assigned/permitted repair data is visible.
7. Move the job through supported states such as:

   * Received
   * Diagnosing
   * Waiting for approval
   * In progress
   * Completed
   * Delivered
8. Attempt invalid state transitions.
9. Consume one Product B as a spare part if supported.
10. Verify inventory decreases by exactly 1 and references the repair job.
11. Add a test service charge of MMK 30,000 if supported.
12. Complete payment using the documented method.
13. Verify service revenue, spare-part cost, customer balance and payment reports.
14. Attempt to complete without required permission.
15. Test disabling Repair while an active job exists.
16. Verify active-workflow blocker or strong confirmation works.
17. Confirm disabling the module does not delete the repair job.
18. Re-enable and verify existing repair data returns.
19. Verify the audit trail records status, technician, parts, payment and actor.

Report lost repair records, incorrect parts stock or unauthorized completion as High or Critical.

Update the shared ledger with parts used, service revenue and payment information.

---

## Source 18: `archive/legacy-prompts/testing-agents/Agent_7_Finance_Accounting_and_Reconciliation.md`

**SHA-256:** `b24a9d035e63e92d0c3e3e99cfc3a856370673780d6f62a9510444dcfc95f202`

Act as an Accountant and independently reconcile the complete UAT test run.

Do not trust dashboard totals without recalculating them from source transactions. Do not edit code.

## Source Transactions

Collect actual IDs and values from:

* Opening inventory
* Purchase and receiving
* Supplier payable/payment
* POS sale
* POS refund
* Online order/payment
* Repair service/parts, if supported
* Expenses
* Cashier opening and closing

## Workflow

1. Verify the Accountant sees only permitted finance/reporting modules.
2. Create a test expense of MMK 20,000 using a clearly documented payment account.
3. Verify the expense changes the correct account and period.
4. Review supplier payable from the MMK 1,600,000 purchase.
5. Test partial/full supplier payment if supported.
6. Verify receivables for credit sales or unpaid online orders if present.
7. Reconcile payment methods separately:

   * Cash
   * Card/digital
   * Credit/receivable
   * Refunds
8. Recalculate independently:

   * Gross sales
   * Discounts
   * Taxes
   * Refunds
   * Net sales
   * Cost of goods sold
   * Gross profit
   * Service revenue
   * Expenses
   * Net profit
9. Verify stock valuation using the documented costing policy.
10. Compare independent totals with:

    * Dashboard
    * Sales report
    * Payment report
    * Inventory valuation
    * Expense report
    * Receivable report
    * Payable report
    * Profit and loss
    * Cashier closing
11. Verify POS and online transactions are separated by source without being omitted or double-counted.
12. Verify date, timezone, rounding, tax and currency behavior.
13. Test reconciliation period locking if supported.
14. Verify unauthorized users cannot approve, export or alter finance records.
15. Verify audit logs for expense, payment, refund and reconciliation changes.

For every mismatch, provide:

* Source records
* Independent formula
* Expected total
* Actual total
* Difference
* Suspected affected module
* Financial impact
* Severity

Any unexplained balance difference must prevent final acceptance.

---

## Source 19: `archive/legacy-prompts/testing-agents/Agent_8_Final_Security_Reports_and_Human_Experience_Audit.md`

**SHA-256:** `569324a50202fe3af68f8f341753734f80c895e27a262f605113b69e5f38c503`

Perform the final independent acceptance audit of DataPOS using the completed shared UAT dataset.

Do not edit code and do not silently correct test data.

## Security and Isolation

1. Test direct URLs for hidden modules.
2. Test Store A user access to Store B records.
3. Test request parameter and route slug tampering.
4. Verify 403/404 responses do not leak private information.
5. Test inactive user sessions and revoked permissions.
6. Verify Platform Owner, Store Owner and staff scopes remain separated.
7. Verify audit logs cover permissions, module/channel changes, stock, refunds and finance.

## Reporting Consistency

Cross-check the same transaction across:

* Product stock
* Inventory ledger
* Purchase records
* Sales records
* Online orders
* Repair records
* Payments
* Cash closing
* Receivables/payables
* Profit and loss
* Dashboard KPIs

No transaction should be missing, duplicated or attributed to the wrong store/channel.

## Browser and UX Coverage

Test:

* Desktop expanded: 1440×900
* Desktop collapsed: 1440×900
* Short laptop: 1366×600
* Tablet: 768×1024
* Mobile: 390×844
* Light mode
* Dark mode
* Myanmar
* English
* Simplified Chinese

Verify:

* Sidebar accordion
* Collapsed flyout
* Scrolling to the final menu item
* Mobile drawer
* Keyboard navigation
* Focus visibility
* Escape close
* Outside-click close
* Active menu state
* Empty groups hidden
* No placeholder `href="#"`
* No missing translations
* Forms retain valid data after validation errors
* Duplicate submission prevention
* Loading/error/empty states
* Browser console errors
* Failed network requests
* Receipt and report print layout

## Final Decision

Produce:

1. Test environment and commit SHA
2. Roles and workflows tested
3. Inventory reconciliation
4. Cash/payment reconciliation
5. Receivable/payable reconciliation
6. Profit and loss reconciliation
7. Cross-store isolation result
8. Permission matrix result
9. Module/channel result
10. Responsive and localization result
11. Console/network result
12. All bugs with evidence
13. Untested or unsupported areas
14. Data cleanup/restore instructions
15. Final verdict:

    * READY
    * READY WITH KNOWN LOW-RISK ISSUES
    * NOT READY

Do not mark READY while any Critical or High issue, unexplained financial difference, stock mismatch, data-loss risk or cross-store security failure remains.

---

## Source 20: `archive/legacy-prompts/testing-agents/README.md`

**SHA-256:** `c41b52931080f5ac3d61f98319796e6245b9534a2a74b05227ed2d0b36580365`

# Multi-Agent UAT Testing Prompts (Archive)

ဤ directory ရှိ `Agent_0` မှ `Agent_8` စမ်းသပ်မှု Prompts များသည် ၂၀၂၆ စက်တင်ဘာ ၈ ရက်နေ့က DataPOS application အား end-to-end စစ်ဆေးခဲ့သော Multi-Agent UAT run တွင် အသုံးပြုခဲ့သော Prompts များ ဖြစ်ပါသည်။

အဆိုပါ စမ်းသပ်မှု၏ ရလဒ်များနှင့် UAT run manifest များကို [`01_HISTORICAL_AUDITS.md`](01_HISTORICAL_AUDITS.md) တွင် ထိန်းသိမ်းထားရှိပါသည်။

လက်ရှိ Active AI Agent QA & Fix Playbook အတွက် `../../../prompts/AI_AGENT_QA_PLAYBOOK.md` (`../../../prompts/AI_AGENT_QA_PLAYBOOK.md`; see consolidated index) ကိုသာ ကြည့်ရှုအသုံးပြုပါ။

## File Inventory

| File | Agent Role | Test Area |
|---|---|---|
| `Agent_0_Test_Coordinator.md` (`Agent_0_Test_Coordinator.md`; see consolidated index) | Lead QA Coordinator | UAT Coordination and multi-agent synthesis |
| `Agent_1_Store_Setup_Modules_and_Permissions.md` (`Agent_1_Store_Setup_Modules_and_Permissions.md`; see consolidated index) | Store Owner | Store setup, modules, channels, staff roles & permissions |
| `Agent_2_Products_Purchasing_and_Inventory.md` (`Agent_2_Products_Purchasing_and_Inventory.md`; see consolidated index) | Inventory Staff | Purchasing-to-stock workflow |
| `Agent_3_POS_Cashier_and_Daily_Closing.md` (`Agent_3_POS_Cashier_and_Daily_Closing.md`; see consolidated index) | Cashier | Cashier shifts, POS transactions & daily closing |
| `Agent_4_Returns_Refunds_and_Cancellation_Integrity.md` (`Agent_4_Returns_Refunds_and_Cancellation_Integrity.md`; see consolidated index) | Supervisor | Returns, refunds, exchanges & cancel integrity |
| `Agent_5_Ecommerce_and_Online_Offline_Channel.md` (`Agent_5_Ecommerce_and_Online_Offline_Channel.md`; see consolidated index) | Online Customer / Staff | Ecommerce ordering & channel separation |
| `Agent_6_Repair_and_Service_Workflow.md` (`Agent_6_Repair_and_Service_Workflow.md`; see consolidated index) | Technician / Reception | Repair jobs & service workflow |
| `Agent_7_Finance_Accounting_and_Reconciliation.md` (`Agent_7_Finance_Accounting_and_Reconciliation.md`; see consolidated index) | Accountant | Financial reconciliation & audit |
| `Agent_8_Final_Security_Reports_and_Human_Experience_Audit.md` (`Agent_8_Final_Security_Reports_and_Human_Experience_Audit.md`; see consolidated index) | Independent Auditor | Acceptance audit & security |
