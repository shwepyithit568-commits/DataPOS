# DataPOS AI Agent QA & Safe-Fix Playbook

**Status:** Active consolidated prompt

**Merged from:** legacy bug-fix, responsive, spacing, theme, localization, POS/export, security, Lighthouse and final-verification prompts

**Scope:** Reusable instructions only; feature-specific approved plans remain authoritative.

## 1. Start safely

1. Read `AGENTS.md`, [`../README.md`](../README.md), the relevant approved architecture document and current tests.
2. Record branch, exact HEAD and `git status --short` before editing.
3. Preserve unrelated user changes. Do not deploy, push, delete data or rotate configuration unless explicitly authorized.
4. Reproduce the reported issue and identify the root cause before changing code.
5. Treat current routes, migrations, models and tests as evidence—not documentation claims alone.

## 2. Engineering rules

- Fix the underlying cause; do not hide symptoms with CSS or conditional workarounds.
- Prefer existing services, policies, middleware, components and design tokens.
- Add no unnecessary dependency or duplicate architecture.
- Validate nulls, types, nested payloads, allowlists, duplicate requests and concurrent writes.
- Protect financial and quantity calculations with the project decimal/bcmath convention; do not use PHP float for persisted financial truth.
- Do not change unrelated business behaviour merely to satisfy a test.
- Remove debug leftovers, unused imports and dead code only within the approved task scope.

## 3. Security and multi-store isolation

For every affected read/write route, verify authentication, active membership, role/permission, active StoreContext and target-resource ownership.

Test direct URLs, manipulated IDs and requests across Staff, Products, Inventory, Sales, POS, Customers, Reports and Settings. UI hiding is not authorization. Unauthorized and cross-store operations must fail at backend/query level. Classify findings as Critical, High, Medium or Low and provide reproduction evidence.

## 4. UI/UX and spacing

Follow [`ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md`](ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md) and [`../STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md`](../STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md).

- Use clean, full-width, restrained surfaces without unnecessary nested containers, margins, rounded corners or shadows.
- Page-level sibling sections should normally use the project’s compact approximately 4px rhythm; internal controls must retain readable spacing and usable touch targets.
- Reuse shared spacing/theme tokens instead of page-specific overrides.
- Respect `prefers-reduced-motion`, keyboard navigation and visible focus states.

Responsive verification minimum:

- Mobile: 320, 375, 390 and 430px
- Tablet: 768, 820 and 1024px
- Desktop: 1280, 1440 and 1920px

Tables wider than the viewport must scroll inside their wrapper without causing body-level horizontal overflow. Grid column counts are page-dependent; do not blindly force 5/3/2 columns where content or the canonical UI guide requires another layout.

## 5. Light and dark themes

Audit the complete rendered surface: page, sidebar, cards, tables, forms, dropdowns, modals, popovers, tooltips, empty/loading states and toasts.

- Light mode: high-contrast page/surface/text/border tokens suitable for daylight.
- Dark mode: true black outer surfaces where the current theme specifies OLED mode, with deep-slate inner surfaces and readable borders.
- Use centralized semantic tokens. Do not scatter arbitrary hardcoded colours.
- Verify default, hover, selected, disabled, error, warning and success states.
- Never rely on colour alone to communicate state.

## 6. Localization

Maintain key parity across `lang/my/messages.php`, `lang/en/messages.php` and `lang/zh_CN/messages.php`.

- Prefer concise, natural Burmese rather than literal word-for-word translation.
- Keep terminology consistent across navigation, buttons, forms, tables, settings, dialogs, validation, POS and reports.
- Preserve placeholders such as `:name`, `{count}` and `%s`.
- Find hardcoded user-facing strings, missing keys and accidental duplicates.
- Render-test Myanmar text for wrapping, truncation, table width, mobile navigation and modal/form alignment.

## 7. POS and Excel/CSV exports

For affected POS flows verify product selection, cart, quantity, discount, tax, totals, payment/change, stock posting, receipt, return/refund, void rules, idempotency and store isolation.

For `.xlsx`/CSV verify header/data mapping, Myanmar Unicode, configured currency, date/timezone, filters, empty/large datasets, quoting/newlines/commas and formula-injection protection. Provide separate POS and export results.

## 8. Browser and Lighthouse QA

Where the environment supports it, test the relevant public/admin journey in Mobile and Desktop Lighthouse and report Performance, Accessibility, Best Practices, SEO where applicable, LCP, CLS, blocking resources and violations.

Exercise the actual relevant user flow, not a screenshot alone. Capture viewport, role, theme, locale, console errors, failed network requests and reproducible defects. An untested item must be reported as Not Verified—not Passed.

## 9. Verification and Git handoff

Run the applicable build, lint/type checks, targeted tests, full regression suite and smoke tests. Do not deploy with unexplained failures.

Review the final diff for secrets, temporary/generated files, debug code and unrelated changes. Commit logically. Push or deploy only when explicitly authorized and available, then verify the target environment.

Final report must include:

- Starting/final branch and SHA
- Root cause and fix
- Complete changed-file list and reasons
- Exact commands and results
- Passed/failed/skipped counts
- Security/store-isolation evidence where relevant
- Browser/viewport/role/theme/locale evidence
- Console/network result
- Push/deployment status and URL when actually performed
- Remaining risks, limitations and items not verified
