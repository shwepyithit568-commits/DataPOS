Act as a real Store Owner and test DataPOS store setup, modules, channels, staff roles and permissions using the browser.

Use only the assigned staging test stores. Do not edit code.

## Workflows

1. Log in as Platform Owner.
2. Verify platform navigation does not show store-dependent menus or trigger store KPI queries.
3. Enter Store A and verify store navigation is separate from platform navigation.
4. Configure Store A as POS-only:

   * POS/Offline Sales enabled
   * Ecommerce/Online Store disabled
5. Confirm:

   * POS remains visible and usable
   * Ecommerce menus disappear
   * Storefront/online routes are unavailable
   * Ecommerce KPIs and online-order requests do not run
6. Enable Ecommerce and Online Store.
7. Confirm POS remains available and Ecommerce menus appear.
8. Verify disabling Ecommerce does not disable POS.
9. Test protected/core module disable attempts.
10. Test invalid module and channel keys if the UI or endpoint allows them.
11. Create the required staff roles and users.
12. Assign granular permissions such as:

* View only
* View + create
* View + update
* View + create + update + delete
* Special actions such as adjust, refund, close, complete and export

13. Log in as each staff role and verify:

* Visible menus
* Hidden menus
* Visible action buttons
* Hidden action buttons
* Allowed routes
* Forbidden direct URLs

14. Verify a user cannot access Store B by changing URL or request parameters.
15. Verify Store Owner cannot modify Platform Owner permissions.
16. Verify the last active Store Owner cannot be deleted or deactivated.
17. Verify inactive staff cannot continue using effective permissions.
18. Verify permission changes are reflected without stale navigation.
19. Verify permission and module changes create audit-log records.

## Evidence

For every role, capture screenshots of the sidebar and at least one allowed and one forbidden action.

Report permission leaks as Critical or High. Include exact role, store, permission, URL, expected result and actual result.
