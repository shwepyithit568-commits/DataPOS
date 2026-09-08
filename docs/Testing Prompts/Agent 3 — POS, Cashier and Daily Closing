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
