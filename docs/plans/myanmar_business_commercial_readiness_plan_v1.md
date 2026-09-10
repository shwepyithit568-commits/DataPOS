# DataPOS — Myanmar Business Commercial Readiness Plan v1

## Document Status

- **Purpose:** DataPOS ကို Windows Offline Installer/EXE မထုတ်မီ မြန်မာနိုင်ငံရှိ retail၊ mobile/electronics၊ repair/service၊ wholesale နှင့် ecommerce လုပ်ငန်းများတွင် ရောင်းချအသုံးပြုနိုင်သောအဆင့်အထိ ပြင်ဆင်ရန် implementation plan
- **Status:** Approval Draft — v1
- **Rule:** ဤ plan ကို Store Owner/Project Owner က approval မပေးမီ production code၊ migrations၊ database records သို့မဟုတ် installer ကို မပြင်ရ။ Read-only audit၊ test baseline နှင့် documentation ပြုလုပ်ခြင်းသာ ခွင့်ပြုသည်။
- **Next document:** ဤ plan အောင်မြင်ပြီး UAT approval ရမှ active `windows_offline_installer_plan_v2.md` ကို current evidence နှင့်အညီ update လုပ်ရန် (`v1` သည် archive ထဲရှိ historical plan ဖြစ်သည်)

---

## 1. Primary Goal

DataPOS ကို programmer မဟုတ်သော ဆိုင်ရှင်နှင့်ဝန်ထမ်းများက—

- အင်တာနက်မရှိဘဲ Windows ကွန်ပျူတာပေါ်တွင် အသုံးပြုနိုင်ခြင်း
- ရောင်းအား၊ လက်ခံငွေ၊ ပြန်အမ်းငွေ၊ ကုန်ကျစရိတ်၊ အကြွေးနှင့် stock စာရင်းများ အပြန်အလှန်ကိုက်ညီခြင်း
- Receipt၊ Invoice၊ Voucher နှင့် Reports များကို မြန်မာစာမှန်ကန်စွာ Print/PDF ထုတ်နိုင်ခြင်း
- Excel/CSV ဖြင့် data import/export လုပ်ရာတွင် data မပျောက်၊ မပွား၊ မမှားခြင်း
- Backup ကို အလွယ်တကူသိမ်းပြီး ကွန်ပျူတာအသစ်တွင် Restore ပြန်လုပ်နိုင်ခြင်း
- Cashier၊ Manager၊ Accountant၊ Inventory Staff နှင့် Technician တို့အား သီးခြား permission ပေးနိုင်ခြင်း

တို့ကို ယုံကြည်စိတ်ချစွာ လုပ်နိုင်စေရန်ဖြစ်သည်။

## 2. Non-Goals

ဤ phase တွင်—

- Windows EXE/Installer မထုတ်သေးရ။
- မရှိသေးသော cloud synchronization architecture ကို ခန့်မှန်း၍ မတီထွင်ရ။
- Accounting/legal compliance အောင်မြင်သည်ဟု professional verification မရှိဘဲ မကြေညာရ။
- Scope နှင့်မသက်ဆိုင်သော storefront redesign သို့မဟုတ် business module အသစ် မတီထွင်ရ။
- Existing sales၊ stock၊ customer၊ finance နှင့် repair records မဖျက်ရ။

---

## 3. Mandatory Pre-Implementation Audit

### 3.1 Repository Baseline

1. Repository ရှိ `AGENTS.md` နှင့် project documentation အားလုံးကို အပြည့်အစုံဖတ်ပါ။
2. Local checkout၊ GitHub default branch နှင့် deployment commit မတူပါက source of truth commit တစ်ခုကို ရွေးချယ်ပြီး full SHA မှတ်တမ်းတင်ပါ။
3. `git status`၊ current branch၊ full commit SHA နှင့် remotes ကို report လုပ်ပါ။
4. Existing unrelated changes ကို မဖျက်၊ overwrite သို့မဟုတ် commit မလုပ်ရ။
5. Audit စတင်ချိန် `php artisan test` နှင့် `npm run build` ကို baseline အဖြစ် run ပြီး exact output သိမ်းပါ။

### 3.2 Current-State Inventory

Module တစ်ခုစီကို အောက်ပါ status သုံးမျိုးဖြင့် ခွဲပါ—

- **Complete:** UI၊ backend၊ permission၊ validation၊ tests နှင့် offline behavior ပြည့်စုံ
- **Partial:** Feature ရှိသော်လည်း workflow/test/consistency မပြည့်စုံ
- **Missing:** အသုံးချနိုင်သော implementation မရှိ

အနည်းဆုံး အောက်ပါတို့ကို route → controller/service → model/database → UI → permission → automated test အထိ mapping ပြုလုပ်ပါ—

- POS/Sales/Returns/Refunds/Shift Closing
- Products/Categories/Brands/Variants/Barcode
- Inventory/Opening Stock/Ledger/Adjustment/Count/Reconciliation
- Customers/Receivables/Statements
- Suppliers/Purchasing/Payables/Returns
- Expenses/Cash/Bank/Profit & Loss
- Repair/Service/Warranty/IMEI/Spare Parts
- Ecommerce/Online Orders
- Receipt/Invoice/Voucher/Printing
- CSV/XLSX Import and Export
- PDF/Print
- Backup/Restore
- Audit Logs
- User Roles and Permissions

### 3.3 Known Existing Foundations to Preserve

Current repository audit တွင် အောက်ပါအခြေခံများ ရှိနေသည်ဟု တွေ့ရှိထားသဖြင့် duplicate implementation မတည်ဆောက်ဘဲ ပြန်သုံးပါ—

- Laravel-based POS application
- `Asia/Yangon` timezone default
- MMK currency configuration
- 58mm/80mm/A4/A5 printer and voucher structures
- POS receipt print/PDF behavior
- Ecommerce order invoice view
- PHPSpreadsheet-based XLSX reader/writer
- Products၊ Categories၊ Suppliers၊ Customers စသည့် import flows
- POS Sales/Cash/Stock/Service reports
- Profit & Loss၊ Inventory Valuation၊ Debt Aging reports
- Backup/Restore controllers and services
- Audit log foundation

ဤအချက်များကို code inspection နှင့် tests ဖြင့် ပြန်အတည်ပြုရမည်။

---

## 4. Commercial Readiness Priority

| Priority | Meaning | Release Rule |
| --- | --- | --- |
| P0 | Data loss၊ wrong money၊ wrong stock၊ unusable offline operation ဖြစ်စေနိုင် | အားလုံး PASS မဖြစ်မနေ |
| P1 | နေ့စဉ်လုပ်ငန်းနှင့် ဆိုင်ရှင်ဆုံးဖြတ်ချက်အတွက် အရေးကြီး | Installer မတိုင်မီ အဓိကအားဖြင့် PASS |
| P2 | Commercial support၊ convenience နှင့် scale | Pilot မတိုင်မီ သို့မဟုတ် documented limitation ဖြင့် defer |

---

## 5. P0 — Sales, Stock and Finance Integrity

### 5.1 Single Source of Truth

- Posted sale၊ return၊ refund၊ purchase receipt၊ purchase return၊ stock adjustment နှင့် stock transfer တိုင်းသည် inventory ledger နှင့် ချိတ်ဆက်ရမည်။
- Report တစ်ခုချင်းစီက duplicate calculation မလုပ်ဘဲ centralized reporting/accounting services ကို အသုံးပြုရမည်။
- Completed transaction ကို hard delete မလုပ်ရ။ Void၊ reversal သို့မဟုတ် correction entry ဖြင့်သာပြင်ရမည်။
- Money တွက်ချက်ရာတွင် float မသုံးဘဲ decimal/minor-unit policy ကို တစ်ပြေးညီ အသုံးပြုရမည်။
- Store၊ warehouse၊ channel နှင့် transaction source အားလုံးကို records/report filters တွင်ရှင်းလင်းစွာ သိရှိနိုင်ရမည်။

### 5.2 Mandatory Reconciliation Equations

```text
Opening Stock
+ Purchases Received
+ Sales Returns
+ Positive Adjustments
+ Transfers In
- POS/Offline Sales
- Online Fulfilled Sales
- Purchase Returns
- Negative Adjustments
- Transfers Out
= Closing Stock
```

```text
Opening Cash
+ Cash Sales
+ Customer Debt Collections
+ Other Cash In
- Cash Refunds
- Expenses Paid
- Supplier Payments
- Other Cash Out
= Expected Closing Cash
```

Expected Closing Cash နှင့် Actual Closing Cash ကွာခြားချက်ကို variance အဖြစ် record လုပ်ပြီး reason နှင့် approver ပါရမည်။

### 5.3 Period and Backdate Control

- Daily cashier shift close
- Store daily close
- Month/accounting period lock
- Closed period ကို backdate create/update/delete မလုပ်နိုင်ခြင်း
- Reopen လုပ်ရာတွင် owner-level permission၊ reason နှင့် audit log
- Client/system clock ပြောင်း၍ locked period ကိုကျော်လွှားမရခြင်း

### 5.4 Approval Controls

အောက်ပါတို့ကို configurable threshold နှင့် permission ဖြင့်ကာကွယ်ပါ—

- Discount
- Price override
- Return/refund
- Sale void
- Stock adjustment
- Negative stock sale
- Expense approval
- Customer credit limit override
- Closed-period reopen

---

## 6. Invoice, Receipt and Business Document Architecture

### 6.1 Centralized Document Settings

Store Owner အတွက် `Business Setup > Documents & Printing` သို့မဟုတ် project convention နှင့်ကိုက်ညီသော centralized page ထားပါ။ အနည်းဆုံး—

- Store name in Myanmar and English
- Logo variants
- Address၊ phone၊ Viber၊ Telegram
- Business/Tax registration number
- Receipt/Invoice/Voucher prefix
- Store-scoped sequential numbering
- Financial-year reset rule or continuous numbering
- Paper size: 58mm၊ 80mm၊ A4၊ A5
- Default printer and copy count
- Header၊ subtitle၊ footer၊ terms and return policy
- Warranty policy
- Cashier/customer/payment detail visibility
- Discount/tax/payment breakdown visibility
- Barcode/QR/payment QR visibility
- Customer and cashier signature fields
- Original/Copy/Reprint/Void watermark
- Myanmar/English document language
- Live preview and test print

တို့ကို support လုပ်ပါ။

### 6.2 Required Business Documents

| Business Area | Required Documents |
| --- | --- |
| Sales | POS Receipt၊ Sales Invoice၊ Tax Invoice၊ Quotation၊ Delivery Note |
| Returns | Return Voucher၊ Refund Voucher၊ Exchange Voucher |
| Purchasing | Purchase Order၊ Goods Received Voucher၊ Purchase Return |
| Credit | Customer Statement၊ Supplier Statement၊ Payment Receipt |
| Cash | Cash In/Out Voucher၊ Shift Closing၊ Daily Closing |
| Service | Intake Ticket၊ Job Card၊ Estimate၊ Payment Receipt၊ Delivery/Warranty Certificate |
| Inventory | Stock Adjustment Voucher၊ Transfer Note၊ Stock Count Variance Report |

### 6.3 Numbering and Audit Rules

- Store တစ်ခုအတွင်း document number duplicate မဖြစ်ရ။
- Concurrent transactions ဖြစ်သော်လည်း sequence collision မဖြစ်ရ။
- Failed transaction တစ်ခုကြောင့် posted document နှစ်ခု နံပါတ်တူမဖြစ်ရ။
- Reprint count၊ actor၊ timestamp၊ reason ကို audit log မှတ်ပါ။
- Void document ကို number ပြန်သုံးခြင်း မရှိရ။
- Offline device မှထုတ်သော number နှင့် future sync conflicts ကို architecture အလိုက်ကာကွယ်ပါ။

---

## 7. PDF and Printing Standardization

### 7.1 Required Architecture

လက်ရှိ `html2pdf.js`၊ `window.print()` နှင့် HTML download behavior များကို inventory ပြီး centralized strategy သတ်မှတ်ပါ။

- Reliable server/local PDF generation ကို primary option အဖြစ်သုံးရန် feasibility စစ်ပါ။
- Browser print ကို optional fallback အဖြစ်ထားနိုင်သည်။
- PDF ဟုခေါ်သော download သည် `.html` content မဖြစ်ရ။
- PDF generation သည် internet၊ CDN သို့မဟုတ် system-installed programming tools မလိုရ။

### 7.2 Myanmar Font Requirements

- Unicode-compliant Myanmar font ကို application/installer assets ထဲ embed လုပ်ပါ။
- Font license ကို စစ်ပြီး redistribution ခွင့်ရှိကြောင်း report လုပ်ပါ။
- Myanmar text၊ English text၊ numbers နှင့် currency ကို PDF/print အားလုံးတွင်စစ်ပါ။
- Line wrapping၊ combining marks၊ table clipping နှင့် page break မပျက်ရ။

### 7.3 Print QA

- 58mm thermal
- 80mm thermal
- A4 portrait/landscape
- Long invoice across multiple pages
- Very long Myanmar product name
- 100+ line items
- Windows default printer missing/offline
- Reprint and duplicate-print prevention

တို့ကိုစမ်းသပ်ပါ။

---

## 8. Excel and CSV Import

### 8.1 Supported Import Domains

Existing implementation ကိုပြန်သုံးပြီး အနည်းဆုံး—

- Products and variants
- Categories and brands
- Opening stock
- Customers
- Customer opening debt
- Suppliers
- Supplier opening payable — domain support ရှိပါက
- Price lists
- Spare parts

တို့အတွက် import coverage ရှိ/မရှိ inventory ပြုလုပ်ပါ။

### 8.2 Standard Import Flow

1. Versioned XLSX template download
2. File upload
3. Sheet and column mapping
4. Dry-run validation
5. Preview valid/invalid/duplicate rows
6. Duplicate strategy: Skip/Update/Reject
7. Explicit confirmation
8. Database transaction import
9. Success/failure summary
10. Error workbook download
11. Import history and audit
12. Safe rollback where technically possible

### 8.3 Data Safety Rules

- `.xlsx` နှင့် UTF-8 CSV ကို support လုပ်ပါ။
- Phone numbers၊ SKU၊ barcode၊ IMEI တို့၏ leading zero မပျောက်ရ။
- Excel scientific notation ကြောင့် barcode/IMEI မပြောင်းရ။
- Excel dates ကို locale/timezone မှန်ကန်စွာဖတ်ရ။
- Unknown category၊ unit၊ supplier နှင့် invalid relation ကို reject သို့မဟုတ် explicit mapping လုပ်ရ။
- Duplicate SKU/barcode/IMEI/customer/supplier policy ရှင်းလင်းရ။
- Negative quantity၊ invalid cost/price နှင့် wholesale > retail rule များကို configurable validation ဖြင့်စစ်ရ။
- Formula injection (`=`, `+`, `-`, `@`) ကို CSV/XLSX export နှင့် import နှစ်ဖက်လုံးကာကွယ်ရ။
- Same file retry သည် duplicate records မဖန်တီးရ။
- 1,000၊ 10,000 နှင့် practical maximum rows performance စမ်းရ။

---

## 9. Excel and CSV Export

### 9.1 Standard Export Options

သက်ဆိုင်သည့် list/report တစ်ခုစီတွင်—

- Current filtered rows
- Selected rows
- All permitted rows
- CSV
- XLSX
- Print/PDF — သက်ဆိုင်ပါက

ကို တစ်ပြေးညီပြုလုပ်ပါ။

### 9.2 Export Metadata and Formatting

Exported workbook/report တွင်—

- Store name and branch
- Report title
- Date/time range
- Generated at in Asia/Yangon
- Generated by
- Applied filters
- Currency and number format
- Totals/subtotals
- Data snapshot timestamp

ပါရမည်။ Freeze header၊ auto filter၊ readable column width နှင့် Myanmar Unicode ကိုစစ်ပါ။

### 9.3 Security

- `*.view` နှင့် `*.export` permissions ကို သီးခြားစစ်ပါ။
- Staff သည် အခြား store data export မလုပ်နိုင်ရ။
- Cost၊ profit၊ customer balance စသည့် sensitive columns ကို permission အလိုက်ပဲထုတ်ပါ။
- Export action ကို audit log မှတ်ပါ။
- Large export များ memory exhaustion မဖြစ်စေရ။

---

## 10. Required Report Catalogue

### 10.1 Owner Dashboard

- Today/Yesterday/This month sales
- Gross sales၊ net sales၊ refunds၊ discounts၊ tax
- Gross profit and margin
- Cash/bank/mobile payment totals
- Receivables/payables
- Low/out/negative stock
- Pending repairs
- Pending online orders — ecommerce enabled ဖြစ်မှသာ
- Last backup and last closing status

### 10.2 Sales Reports

- Daily/weekly/monthly sales
- Product/category/brand
- Cashier/staff
- Store/branch/warehouse
- POS/online/manual source channel
- Payment method
- Discount/price override
- Return/refund/exchange
- Void/cancelled sale
- Tax summary
- Gross profit and margin

### 10.3 Inventory Reports

- Stock balance
- Stock ledger/bin card
- Stock valuation
- Opening/received/sold/returned/adjusted/closing movement
- Low/out/negative stock
- Fast/slow/dead stock
- Stock aging
- Physical count variance
- Damage/loss/expiry — supported domain ရှိပါက
- Transfer/warehouse/location
- Serial/IMEI and warranty status

### 10.4 Purchasing and Supplier Reports

- Purchase orders
- Goods received
- Purchase returns
- Purchase by supplier/product/category
- Supplier payable aging
- Supplier statement
- Supplier payment history
- Purchase cost movement

### 10.5 Customer and Finance Reports

- Customer receivable aging
- Customer statement
- Credit sales and collections
- Partial payment history
- Cash/bank book
- Income and expenses
- Expense category analysis
- Daily closing and cash variance
- Payment-method reconciliation
- Profit & Loss
- Channel profitability
- Tax summary

### 10.6 Repair/Service Reports

- Open/pending/completed jobs
- Technician workload
- Turnaround time
- Service income
- Parts usage and cost
- Repair profit
- Warranty returns
- Uncollected devices
- Outstanding repair payments

### 10.7 Report Consistency Requirements

- Report filters ကို date/time၊ store၊ branch၊ warehouse၊ user၊ channel၊ payment method၊ product နှင့် status အလိုက် support လုပ်ပါ။
- Summary total မှ detail records သို့ drill-down လုပ်နိုင်ရမည်။
- Sales report၊ cash report၊ stock report နှင့် P&L တို့၏ source transactions ကို trace လုပ်နိုင်ရမည်။
- Same filters ဖြင့် screen၊ XLSX၊ CSV နှင့် PDF totals တူရမည်။

---

## 11. Myanmar Payment Methods and Reconciliation

Default presets သို့မဟုတ် easy setup အဖြစ်—

- Cash
- KBZPay
- WavePay
- AYA Pay
- CB Pay
- MMQR
- Bank Transfer
- Card
- Cash on Delivery
- Customer Credit

ကို project conventions နှင့်အညီ ထည့်နိုင်ရမည်။ Provider branding/licensing ကို စစ်ပါ။

အောက်ပါ behavior များ လိုအပ်သည်—

- Split payment
- Transaction/reference number
- Optional payment proof
- Payment method အလိုက် shift closing
- Refund ကို original payment နှင့် trace လုပ်ခြင်း
- Payment edit/void approval
- Cash received/change amount
- Mobile payment settlement reconciliation

---

## 12. Myanmar Currency, Tax and Localization

### 12.1 Currency

- MMK default decimal places = 0 ဖြစ်နိုင်သော်လည်း database precision မဆုံးရှုံးရ။
- Quantity decimals ကို unit/product policy အလိုက်ထားပါ။
- `Ks` ကို views တွင် hardcode မထားဘဲ centralized formatter သုံးပါ။
- Receipt၊ invoice၊ reports၊ exports နှင့် dashboard တို့တွင် format တူရမည်။
- Large values၊ negative values နှင့် zero values ကိုစမ်းပါ။

### 12.2 Tax

- Tax enabled/disabled
- Inclusive/exclusive
- Line-level and document-level calculation
- Discount-before/after-tax business rule
- Rounding rule
- Tax-exempt product/customer — လိုအပ်ပါက
- Tax ID and tax invoice label
- Tax summary report

Tax rates သို့မဟုတ် statutory invoice requirements ကို hardcode မလုပ်ဘဲ configurable ထားပါ။ Release မတိုင်မီ Myanmar accountant/tax professional ဖြင့် current legal requirements ကို သီးခြားအတည်ပြုပါ။

### 12.3 Localization

- Myanmar၊ English နှင့် existing Simplified Chinese key parity
- User-facing hardcoded English/Burmese strings မကျန်ရ
- Myanmar Unicode search/sort/export/import/print အလုပ်လုပ်ရ
- Asia/Yangon date/time
- Configurable date format
- Error၊ validation၊ confirmation၊ empty states၊ print labels အားလုံး translation keys သုံးရ

---

## 13. Backup, Restore and Data Portability

### 13.1 Backup Requirements

- One-click manual backup
- Automatic daily backup
- Configurable backup location
- USB/external drive support
- Retention policy
- Backup success/failure notification
- Application/database version metadata
- Integrity checksum
- Optional encryption with documented recovery process
- Last successful backup display

### 13.2 Restore Requirements

- Restore preview and compatibility check
- Restore မတိုင်မီ automatic safety backup
- Wrong/corrupted/version-incompatible backup rejection
- Explicit confirmation
- Transactional/safe restore process
- Restore audit log
- Post-restore integrity verification
- Fresh Windows PC တွင် end-to-end restore test

### 13.3 Export Ownership

Store Owner သည် မိမိ store ၏ business data ကို documented archive format ဖြင့် export ထုတ်နိုင်ရမည်။ Archive တွင် schema/app version၊ store ID နှင့် export timestamp ပါရမည်။

---

## 14. Fully Offline Readiness

### 14.1 Zero-Internet Dependency

Internet cable/Wi-Fi ဖြုတ်ထားချိန်—

- Login
- POS sale
- Product/barcode search
- Stock deduction
- Receipt printing
- Return/refund
- Shift closing
- Reports
- PDF generation
- Excel import/export
- Backup/restore

တို့အလုပ်လုပ်ရမည်။

### 14.2 Local Assets

- JavaScript၊ CSS၊ icons၊ fonts နှင့် PDF dependencies အားလုံး local ဖြစ်ရမည်။
- CDN၊ Google Fonts၊ remote analytics နှင့် external API failure ကြောင့် core workflow မပိတ်ရ။
- Offline mode တွင် unnecessary background network retries မဖြစ်ရ။

### 14.3 Reliability

- Windows restart ပြီး application/database service auto-start
- Power loss/forced shutdown during sale test
- Database lock/corruption recovery guidance
- Disk-full handling
- Low-memory handling
- Printer disconnected/paper out behavior
- Clock rollback/forward detection for audit-sensitive transactions
- Offline license grace/activation design — commercialization phase တွင်လိုအပ်ပါက
- Update မတိုင်မီ backup နှင့် rollback

“POS-only/Offline-only sales channel” နှင့် “No-internet fully local operation” ကို UI/documentation တွင် မရောဘဲ သီးခြားသတ်မှတ်ပါ။

---

## 15. Hardware and Windows Compatibility

အနည်းဆုံး အောက်ပါ hardware ကို brand-independent interface ဖြင့်စမ်းပါ—

- 58mm thermal printer
- 80mm thermal printer
- USB barcode scanner
- Bluetooth barcode scanner — supported ဖြစ်ပါက
- A4 printer
- Cash drawer
- Label printer
- Windows 10 64-bit
- Windows 11 64-bit
- Low-spec PC target defined by project owner

Printer မတပ်ထားခြင်း၊ default printer မမှန်ခြင်း၊ paper width မမှန်ခြင်းတို့အတွက် programmer မလိုသော setup wizard နှင့် test print ပေးပါ။

---

## 16. Roles, Permissions and Audit

Granular permission architecture နှင့် ချိတ်ဆက်၍ အနည်းဆုံး—

- `reports.view`
- `reports.export`
- `sales.refund`
- `sales.void`
- `sales.discount`
- `inventory.adjust`
- `inventory.export`
- `finance.view`
- `finance.export`
- `settings.documents.manage`
- `settings.printers.manage`
- `imports.manage`
- `backups.manage`
- `periods.close`
- `periods.reopen`

သို့မဟုတ် project existing naming convention နှင့်ကိုက်ညီသော equivalent များကို inventory/implement လုပ်ပါ။ Duplicate permission naming မဖန်တီးရ။

အောက်ပါတို့အားလုံး audit log မှတ်ရမည်—

- Import/export
- Print/reprint
- Refund/void/discount override
- Stock adjustment
- Period close/reopen
- Setting/tax/numbering changes
- Backup/restore
- Permission changes

---

## 17. Automated Test Requirements

### 17.1 Data Integrity

1. Sale posts correct stock and payment entries
2. Return/refund reverses correct quantities and money
3. Purchase receipt and return update ledger correctly
4. Adjustment and transfer preserve ledger equation
5. Cash closing equation and variance
6. P&L agrees with source transactions
7. Same report filters return same totals across UI/CSV/XLSX/PDF
8. Cross-store data isolation
9. Closed-period mutation rejected
10. Concurrent receipt/invoice numbering has no duplicates

### 17.2 Documents/PDF/Printing

11. 58mm receipt
12. 80mm receipt
13. A4 multi-page invoice
14. Myanmar Unicode renders correctly
15. Long item names do not clip
16. Reprint is audited
17. Void watermark appears
18. PDF generated without internet

### 17.3 Import/Export

19. Valid XLSX import
20. Valid UTF-8 CSV import
21. Invalid row rejected with useful error
22. Duplicate strategy behavior
23. Leading-zero phone/SKU/barcode preserved
24. Scientific-notation IMEI/barcode protected
25. Formula injection protected
26. Partial failure does not leave inconsistent data
27. Retry does not duplicate records
28. Export permission enforced
29. Cross-store export blocked
30. 10,000-row practical performance test

### 17.4 Backup/Offline

31. Manual backup and checksum
32. Automatic backup
33. Restore into fresh database/PC
34. Corrupted backup rejected
35. Version mismatch handled
36. Core workflow passes with network disabled
37. No external requests during offline UAT
38. Forced shutdown recovery
39. Disk-full/error messaging
40. Restart auto-start behavior

---

## 18. Browser and Manual UAT

### 18.1 Roles

- Store Owner
- Store Manager
- Cashier
- Inventory Staff
- Accountant
- Technician
- Custom StaffRole

### 18.2 Viewports

- 1440×900 desktop
- 1366×600 short laptop
- 768×1024 tablet
- 390×844 mobile where applicable
- Light and dark mode

### 18.3 Real Pilot Workflow

Demo data သာမက လက်တွေ့ဆိုင် workflow ဖြင့်—

1. Store first-time setup
2. Currency/document/printer setup
3. Products/customers/suppliers import
4. Opening stock and opening debt
5. Purchase receiving
6. Cash and credit POS sales
7. Split mobile payment
8. Return/refund/exchange
9. Stock count and adjustment
10. Customer debt collection
11. Expense and supplier payment
12. Repair intake/payment/completion
13. Daily closing
14. Owner reports and reconciliation
15. PDF/XLSX export
16. Backup
17. Restore on a clean Windows machine

တို့ကို programmer မဟုတ်သော staff ဖြင့်လုပ်ခိုင်းပြီး step တစ်ခုစီ PASS/FAIL evidence မှတ်ပါ။

---

## 19. Release Gates Before Windows Installer

အောက်ပါအားလုံး PASS မဖြစ်မနေ ဖြစ်ရမည်—

- [ ] Source-of-truth Git commit fixed and clean
- [ ] Full automated test suite passes
- [ ] Production frontend build passes
- [ ] Sales/stock/cash/P&L reconciliation difference = 0 or documented valid rounding
- [ ] No duplicate document numbers
- [ ] Receipt/Invoice/PDF Myanmar text correct
- [ ] 58mm/80mm/A4 printing passes
- [ ] XLSX/CSV round-trip does not lose data
- [ ] Import errors are recoverable and understandable
- [ ] Backup restores successfully on a clean PC
- [ ] Seven-day no-internet pilot passes
- [ ] Power-loss/restart tests pass
- [ ] No required CDN/external network requests
- [ ] Role and export permissions pass
- [ ] Cross-store isolation passes
- [ ] Known limitations approved in writing
- [ ] Store Owner signs UAT acceptance

Gate တစ်ခု FAIL ဖြစ်ပါက active `windows_offline_installer_plan_v2.md` implementation မစရ။

---

## 20. Recommended Implementation Phases

### Phase A — Audit and Baseline

- Exact commit and environment inventory
- Current-state module/report/document/import/export matrix
- Baseline tests and build
- Gap and risk report

### Phase B — P0 Integrity and Controls

- Sales/stock/cash reconciliation
- Closing/locking/reversal/audit
- Document numbering
- Backup/restore correctness

### Phase C — Documents, PDF and Excel

- Centralized settings/templates
- Myanmar-safe PDF/printing
- Standard XLSX/CSV import/export

### Phase D — Reports and Myanmar Business UX

- Required report catalogue
- Payment-method reconciliation
- Localization and currency consistency
- Staff-friendly error/help states

### Phase E — Offline and Hardware UAT

- Network-disabled pilot
- Power-loss/restart tests
- Printer/scanner tests
- Fresh-PC restore

### Phase F — Approval for Installer Planning

- Completion report
- Known limitations
- UAT sign-off
- Then review/update `windows_offline_installer_plan_v2.md`

---

## 21. Scope Control

- Existing unrelated changes မဖျက်ရ။
- Phase တစ်ခုစီကို သီးခြား commit ပြုလုပ်ပါ။
- Database migration တိုင်း rollback/data-preservation strategy ပါရမည်။
- Existing POS/Ecommerce/Repair workflows မတော်တဆပိတ်မသွားရ။
- Feature မရှိပါက ရှိသည်ဟု မရေးရ။
- Test မ run နိုင်ပါက PASS ဟု မရေးရ။
- Browser/hardware QA မလုပ်နိုင်ပါက limitation အဖြစ်တိတိကျကျ report လုပ်ရ။
- Current Myanmar tax/legal compliance ကို professional verification မရှိဘဲ certified/compliant ဟု မကြေညာရ။

---

## 22. Required Completion Report

Implementation ပြီးသည့်အခါ အောက်ပါတို့ကို တစ်ပါတည်းပေးရမည်—

1. Source-of-truth branch and full Git commit SHA
2. Changed files list and reason per file
3. Database migrations and rollback/data-preservation summary
4. Before/after current-state capability matrix
5. Final report catalogue
6. Final document/template catalogue
7. Invoice/receipt numbering design
8. PDF/font/printing design
9. Import/export mapping and validation rules
10. Reconciliation results
11. Permission-to-route/action/export mapping
12. Audit log coverage
13. Backup and clean-PC restore evidence
14. Exact `php artisan test` output and counts
15. Exact `npm run build` output
16. Browser UAT checklist
17. Printer/scanner/hardware checklist
18. Seven-day offline pilot result
19. Network request audit
20. Power-loss/restart result
21. Screenshots/sample PDFs/sample XLSX files
22. Known limitations and deferred items
23. Unrelated files were not modified confirmation
24. Clean project ZIP or GitHub commit/branch link
25. Store Owner UAT approval status

---

## 23. Approval Checkpoint

ဤ `myanmar_business_commercial_readiness_plan_v1.md` ကို review ပြုလုပ်ပြီး Project Owner က approval ပေးပြီးမှ implementation စရမည်။

Approval မရမီ—

- Code မပြင်ရ
- Migration မဖန်တီးရ
- Production database မပြင်ရ
- Installer/EXE မထုတ်ရ
- Existing records မပြောင်းရ

Approval ရပြီး implementation၊ automated tests၊ real-store UAT နှင့် offline pilot အားလုံး Release Gates ဖြတ်ပြီးမှ `windows_offline_installer_plan_v2.md` ကို ဆက်ရေးရမည်။
