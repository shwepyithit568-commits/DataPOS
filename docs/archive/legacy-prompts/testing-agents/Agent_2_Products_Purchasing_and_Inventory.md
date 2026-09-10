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
