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
