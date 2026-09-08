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
