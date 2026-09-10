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
