# DataPOS - Admin Modules Execution Roadmap

**Document Version:** 3.0.0
**Last Updated:** 2026-08-27
**System Base:** Laravel 12.64.0, PHP 8.2, Blade, Alpine.js, Tailwind CSS 4, SQLite/MySQL-ready
**Status Meaning:** “Implemented” means route/controller/view/test coverage exists. It does not automatically mean sale-ready, hardware-tested, or production-deployed.

## Current Summary

The previous roadmap described 22 high-priority admin modules. In the current codebase, these modules are no longer just sidebar placeholders. They have real route-backed implementation across admin, POS, finance, inventory, service, and storefront areas.

The remaining work is not “build all modules from zero.” The real remaining work is:

1. verify commercial demo flow end-to-end,
2. polish the most-used screens,
3. prepare safe demo data,
4. add backup/restore and installer workflow,
5. run pilot-shop testing before production deployment.

`store.admin.coming-soon` still exists for future roadmap modules, but it should not be treated as evidence that the 22 high-priority modules are missing.

## Module Readiness Matrix

| No | Module | Main Route | Current Status | Commercial Readiness Note |
|---:|---|---|---|---|
| 1 | Customer Receivables & Debt Ledger | `store.admin.receivables.index` | Implemented | Needs pilot debt workflow QA |
| 2 | Barcode & QR Label Printing | `store.admin.barcode.index` | Implemented | Needs real printer/sticker test |
| 3 | Profit & Loss Statement | `store.admin.profit_loss.index` | Implemented | Needs pilot data accuracy check |
| 4 | Warranty / Serial / IMEI Tracker | `store.admin.warranty.index` | Implemented | Needs mobile-shop demo data |
| 5 | Stock Ledger / Bin Cards | `store.admin.stock_ledger.index` | Implemented | Needs ledger reconciliation spot-check |
| 6 | Physical Stock Count | `store.admin.stock_count.index` | Implemented | Needs barcode scanner workflow test |
| 7 | Bulk Price Wizard | `store.admin.price_wizard.index` | Implemented | Needs permission and audit review |
| 8 | Cash & Bank Transactions | `store.admin.transactions.index` | Implemented | Needs cash closing reconciliation test |
| 9 | Thermal Receipt Printers | `store.admin.printers.index` | Implemented | Needs real 58mm/80mm hardware test |
| 10 | Voucher Designer | `store.admin.vouchers.index` | Implemented | Needs print layout QA |
| 11 | Branch Management | `store.admin.branches.index` | Implemented | Needs multi-branch pilot scenario |
| 12 | Currency Exchange Rates | `store.admin.exchange_rates.index` | Implemented | Needs pricing/accounting policy decision |
| 13 | Membership / Loyalty | `store.admin.membership.index` | Implemented | Optional for first pilot |
| 14 | Promotions / Coupons | `store.admin.promotions.index` | Implemented | Optional for first pilot |
| 15 | Web Catalog Visibility | `store.admin.web_products.index` | Implemented | Needed for online catalog demo |
| 16 | E-Load Register | `store.admin.eload.index` | Implemented | Useful for phone shops |
| 17 | Sales Analytics | `store.admin.sales_analytics.index` | Implemented | Needs realistic demo sales data |
| 18 | Inventory Valuation | `store.admin.inventory_valuation.index` | Implemented | Needs cost data correctness review |
| 19 | Debt Aging Report | `store.admin.debt_aging.index` | Implemented | Needs receivables sample data |
| 20 | Staff Roles | `store.admin.roles.index` | Implemented | Needs owner/staff permission audit |
| 21 | Audit Logs | `store.admin.audit-logs.index` | Implemented | Needs critical action coverage review |
| 22 | Database Tools / Alerts | `store.admin.database.index`, `store.admin.alerts.index` | Implemented | Needs safe-operation guard review |

## Sidebar Architecture

The current admin sidebar is organized into 11 practical business groups:

1. `POS & In-store Sales`
2. `Inventory & Products`
3. `Purchasing & Transfers`
4. `Ecommerce Storefront`
5. `Customers & CRM`
6. `Repairs & Service`
7. `Finance & Accounts`
8. `Reports & Analytics`
9. `Security & Access`
10. `System Maintenance`
11. `Business Setup`

This grouping is suitable for Myanmar SME users because daily cashier work, inventory work, purchasing work, finance work, and settings are separated clearly.

## Priority for Next Implementation Work

Do not start another large module batch before commercial readiness work. The codebase already has enough modules for a strong demo.

### Priority 1 - Sale Demo Path

Polish only the screens that appear in the first 5-minute sales demo:

- POS sale screen
- Products list/import
- Customer receivables
- Stock ledger/count
- Daily closing
- Profit and loss
- Settings / voucher / printer

### Priority 2 - Data Integrity Path

Verify data flow across:

- product opening stock to inventory ledger,
- sale to stock decrease and cash movement,
- return to stock restoration and refund,
- purchase receiving to stock increase and payable,
- debt sale to receivable,
- debt collection to receivable reduction and cash/bank transaction.

### Priority 3 - Permission and Store Isolation

For every module, confirm:

- route has `EnsureStoreAccess`,
- query is scoped to the current `StoreContext`,
- route-model-bound records cannot cross stores,
- manager-only actions are not available to ordinary staff,
- destructive actions have validation and clear confirmation.

### Priority 4 - UI Consistency

Use [ADMIN_UI_UX_STANDARD_GUIDE.md](D:/xmapp/htdocs/DataPOS/docs/ADMIN_UI_UX_STANDARD_GUIDE.md) as the standard for pages touched during new work. Avoid full-site redesign until pilot workflow is stable.

## Definition of Done for a Module

A module should be called “sale-ready” only when all items pass:

- route/controller/view exists,
- store scoping is enforced,
- authorization is tested,
- validation and error messages are practical,
- list/search/filter/pagination are usable,
- create/update/destructive paths are covered,
- Burmese labels are natural,
- light/dark mode is readable,
- low-end screen and tablet layout are usable,
- relevant export/print flows are tested if the module promises them,
- targeted feature tests pass.

## Commercial Readiness Gates

Before first paid pilot:

- [ ] Run full test suite or document exact failures.
- [ ] Run one mobile-shop demo from empty/fresh demo data.
- [ ] Verify receipt printing with real hardware or clearly mark printing as unverified.
- [ ] Verify backup and restore.
- [ ] Verify permissions for owner, manager, staff, customer.
- [ ] Verify no production secrets are committed.
- [ ] Verify `SHOW_QUICK_LOGIN=false` for any production/staging deployment.

Before public/resale release:

- [ ] Installer or setup script is repeatable.
- [ ] Support checklist exists.
- [ ] License/demo mode decision is implemented or explicitly deferred.
- [ ] Data migration/backup plan is documented.
- [ ] At least one real pilot shop has completed a full operating day.

## Recommended Next Step

Build and verify `Commercialization Phase C1`:

1. Mobile shop demo preset.
2. Admin demo preset switcher.
3. 5-minute demo script.
4. Backup/restore workflow.
5. Targeted tests for the above.

After that, use this roadmap as a QA checklist, not as a request to add more modules.
