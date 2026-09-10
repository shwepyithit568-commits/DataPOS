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
