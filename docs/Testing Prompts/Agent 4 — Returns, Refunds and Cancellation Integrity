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
