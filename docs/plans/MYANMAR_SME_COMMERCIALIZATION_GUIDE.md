# DataPOS - Myanmar SME Commercialization Guide

**Document Version:** 2.0.0
**Last Updated:** 2026-08-27
**Target Market:** Myanmar micro, small, and medium businesses
**System Base:** Laravel 12.64.0, PHP 8.2, Blade, Alpine.js, Tailwind CSS 4, Vite, SQLite/MySQL-ready
**Current Scope:** Local/UAT commercialization preparation. Not yet a production resale release.

## Purpose

DataPOS ကို Myanmar SME ဆိုင်ရှင်တွေရှေ့မှာ ရောင်းချနိုင်တဲ့ product အဖြစ်ပြင်ဆင်ရန် ဒီ guide ကိုသုံးပါ။ Feature ထပ်တိုးတာထက် ပထမဦးစားပေးမှာ:

1. 5 မိနစ်အတွင်း နားလည်လွယ်တဲ့ live demo ပြနိုင်ခြင်း။
2. ဆိုင်တစ်ဆိုင်မှာ data မပျက်ဘဲ နေ့စဉ်သုံးနိုင်ခြင်း။
3. Internet မကောင်း/မီးပျက်/low-end PC အခြေအနေတွေမှာလည်း လုပ်ငန်းမရပ်ခြင်း။
4. Installation, backup, support ကို Boss တစ်ယောက်တည်း လိုက်လုပ်နိုင်လောက်အောင် ရိုးရှင်းခြင်း။

## Commercialization Principle

မြန်မာ SME ဆိုင်ရှင်အများစုက software feature list ထက် လက်တွေ့မြင်ရတဲ့ workflow ကိုပိုယုံကြည်တယ်။ အဲဒါကြောင့် DataPOS ကို အရင်ဆုံး “Mobile Shop Demo Pack” နဲ့ရောင်းပြသင့်သည်။

အရင်လုပ်ရန်:

- ဖုန်း/အပိုပစ္စည်းဆိုင် data preset
- POS sale demo
- Barcode/QR label demo
- Customer debt and collection demo
- Stock count / low stock demo
- Daily closing and profit report demo
- Local backup demo

နောက်မှလုပ်ရန်:

- Pharmacy, grocery, restaurant, hardware, agro, gold shop presets
- License activation
- Android APK
- Cloud sync/offline queue

## Release Phases

| Phase | Goal | Output | Priority |
|---|---|---|---|
| C1 | Demo-ready mobile shop package | Demo seeder, demo switcher, 5-minute script | Must do first |
| C2 | Pilot-shop safety | Backup/restore, import workflow, daily closing checklist | Must do before sales |
| C3 | Installer readiness | Start/stop scripts, desktop shortcuts, local setup notes | Do after pilot flow passes |
| C4 | Sales material | Burmese user guide, price package sheet, demo deck | Do before field sales |
| C5 | Anti-piracy | Offline licensing, feature flags, grace period | Do after first paying pilot |
| C6 | Mobile/tablet | PWA polish, Capacitor/TWA APK, Bluetooth printer experiments | Later |

## Phase C1 - Mobile Shop Demo Pack

This is the first practical commercial release target.

### Demo Data

Create one safe preset for `datapos-mobile`:

- Categories: Phones, Screen Protectors, Chargers, Cables, Earbuds, Powerbanks, Spare Parts, Repair Services
- Brands: Apple, Samsung, Xiaomi, Oppo, Vivo, Remax, Baseus, Anker
- Products: 30-50 realistic items with SKU/barcode, retail price, wholesale price, purchase cost, reorder level
- Customers: retail customer, wholesale customer, debt customer
- Suppliers: 2-3 realistic suppliers
- Opening stock: enough sample stock for POS and reports

### Demo Workflow

The demo must work without explaining database theory:

1. Scan/search product in POS.
2. Sell with cash/KPay.
3. Print or show receipt.
4. Show stock reduced automatically.
5. Add one debt sale or collect debt.
6. Show low-stock alert.
7. Show P&L/report page.
8. Run backup.

### Safe Implementation Rules

- Demo preset must be blocked outside `local`, `testing`, or explicitly approved UAT mode.
- Demo seed must target one store only and never wipe unrelated stores.
- Use transactions around destructive demo reset actions.
- Demo switcher UI must clearly say whether it will add data or replace demo data.
- Never put production/customer real data in demo seeders.

## Phase C2 - Pilot-Shop Safety

Before selling to a real shop, verify one complete business day:

| Workflow | Must Pass |
|---|---|
| Product import | CSV/XLSX preview, confirm, failure report |
| Opening stock | Stock balance and ledger agree |
| POS sale | Cash and KPay sale works |
| Return/refund | Stock and cash impact is correct |
| Customer debt | Debt creation and collection works |
| Purchase order | Receiving updates inventory and payable |
| Stock transfer | Ship and receive workflow works |
| Daily closing | Cash expected vs actual is clear |
| P&L | Sales, COGS, expenses, net profit are understandable |
| Backup/restore | Boss can restore from backup without data loss |

## Phase C3 - Local Installer Strategy

Keep the first installer simple. Avoid heavy packaging until pilot workflow is stable.

Recommended first version:

- `DataPOS_Start.bat` - starts Laravel server on `127.0.0.1:8501`
- `DataPOS_Backup_Today.bat` - copies SQLite DB and important storage files to a dated folder
- Desktop shortcut to open POS/admin in browser
- A simple README for the shop PC operator

Later installer:

- Inno Setup or portable package
- bundled PHP/runtime if XAMPP dependency becomes painful
- automatic `.env` creation
- setup wizard for store name, phone, logo, currency, printer size

Standard local URL:

```text
http://127.0.0.1:8501/store/datapos-mobile/pos
```

## Phase C4 - Sales Package

Use simple package names. Do not overpromise advanced cloud/offline sync until tested.

| Package | Good For | Include |
|---|---|---|
| Starter POS | small mobile accessory, grocery, pharmacy starter shop | POS, products, stock, receipt, daily closing, local backup |
| Business POS | mobile shop, repair shop, wholesale small business | Starter + debt, purchasing, warranty/IMEI, repair/service, reports |
| Business Online | shops that also want online catalog/order | Business + storefront, order handling, promotions, web push |

Hardware bundle options:

- Software + 80mm thermal printer + barcode scanner
- Mini PC or existing laptop setup + printer + scanner + cash drawer
- Optional local router for LAN access inside the shop

## Phase C5 - Licensing Strategy

Do not start licensing before the pilot shop is stable. Licensing adds support burden and can lock out honest customers if implemented poorly.

Recommended model:

- 14-day demo mode for evaluation
- offline activation key for one PC
- feature flags for Starter / Business / Business Online
- grace period for hardware replacement
- manual support override for trusted customers

Security direction:

- Use signed license payloads, not plain text flags.
- Do not store private signing keys in client code.
- Avoid relying only on motherboard UUID; some low-cost PCs expose unstable IDs.

## Phase C6 - Android / Tablet Direction

DataPOS already has web app assets such as `public/sw.js` and `public/manifest.webmanifest`, but APK release should be treated as a later phase.

Recommended path:

1. Make browser/PWA layout reliable on tablets.
2. Test local Wi-Fi access from Android devices to the shop PC.
3. Verify printing options: browser print, LAN printer, Bluetooth printer bridge.
4. Only then package with Capacitor or TWA.

Do not promise Bluetooth direct printing until it is tested with real Myanmar-market printers.

## Field Demo Script

Use this 5-minute flow:

1. Show POS and scan/search 2 products.
2. Complete one sale and show receipt.
3. Show stock balance changed.
4. Show one customer debt and collection.
5. Show today sales and P&L.
6. Show backup button/script.
7. Explain support package and hardware bundle.

Do not show too many admin pages. The goal is trust and clarity, not feature overload.

## Commercial Readiness Checklist

- [ ] Mobile shop demo preset exists.
- [ ] Demo preset is safe for local/UAT only.
- [ ] Demo reset cannot touch real production data.
- [ ] POS sale, return, stock count, debt, purchase, daily closing, P&L tested in one flow.
- [ ] Backup and restore documented and tested.
- [ ] Printer/scanner tested with available hardware.
- [ ] Burmese quick-start guide written for cashier and owner.
- [ ] Pricing/package sheet prepared.
- [ ] Known limits clearly listed before client demo.

## Current Recommended Next Step

Build `Commercialization Phase C1` first:

1. Mobile shop demo seeder.
2. Admin demo preset switcher.
3. 5-minute demo checklist.
4. Backup script and restore note.

After C1, run one pilot-day simulation before adding licensing or APK work.
