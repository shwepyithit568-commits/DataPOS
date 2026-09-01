# DataPOS - Admin UI/UX Standard Guide

**Document Version:** 4.0.0
**Last Updated:** 2026-09-01
**System Base:** Laravel 12.64.0, Blade, Alpine.js, Tailwind CSS 4, Vite
**Purpose:** Admin pages ကို Myanmar SME users အတွက် ဖတ်လွယ်၊ သုံးလွယ်၊ မြန်၊ consistent ဖြစ်အောင် ထိန်းသိမ်းရန်။

## Core Design Direction

DataPOS admin UI သည် marketing website မဟုတ်ပါ။ ဆိုင်ရှင်၊ cashier, warehouse staff, accountant တို့ နေ့စဉ်ပြန်ပြန်သုံးမည့် work tool ဖြစ်သည်။

Use:

- compact layout,
- clear tables,
- predictable filters,
- readable Burmese labels,
- visible totals,
- safe destructive actions,
- low-end device friendly spacing.

Avoid:

- oversized hero sections,
- decorative gradients everywhere,
- nested cards,
- hardcoded labels,
- tiny low-contrast text,
- inline event handlers that break CSP,
- UI changes that move important actions unexpectedly.

## Page Layout Standard

Admin child views should use the admin layout:

```blade
@extends('layouts.admin.app')

@section('title', __('messages.module_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-1">
    {{-- page content --}}
</div>
@endsection
```

Rules:

- Use `@section('main_padding', 'p-2')` for dense admin work screens.
- Default page-level sibling section gap should be approximately `4px` (`gap-1` / `space-y-1`).
- The `4px` rule applies to page-level section rhythm, not every internal control. Keep buttons, inputs, table rows, cards, and touch targets comfortably usable.
- Remove duplicate margins/padding that accidentally increase the intended section gap.
- Keep page sections flat. Do not place cards inside cards.
- Use tables for scanning/comparing rows.
- Use cards only for repeated items, mobile-friendly summaries, modals, or genuinely framed tools.
- Make destructive actions visually separate from normal actions.

## Compact Section Spacing Standard

Admin screens are operational work surfaces, so vertical rhythm should be intentionally compact.

Default page-level flow:

```text
Header
↓ ~4px
Banner / Summary
↓ ~4px
Search / Toolbar
↓ ~4px
Filters
↓ ~4px
Table / Grid / Main Content
↓ ~4px
Pagination / Footer Actions
```

Rules:

- Use approximately `4px` between direct page-level sibling sections by default.
- In Tailwind CSS, prefer shared `gap-1` / `space-y-1` patterns where structurally appropriate.
- Do not stack parent `gap`, child margins, and wrapper padding in ways that unintentionally create 12-24px gaps.
- A 4px section gap does **not** mean every internal spacing value must be 4px.
- Preserve readable line-height, usable form control padding, table-row height, and touch-friendly action targets.
- Larger spacing is allowed when it communicates a genuine semantic boundary, but it should be intentional and uncommon.
- On mobile, keep outer page padding compact while preventing content from touching the viewport edge.
- Prefer shared spacing utilities/components over one-off margin overrides.

## Header Standard

Each admin page should show:

- module group context,
- page title,
- short subtitle only when useful,
- primary action,
- optional secondary actions.

Keep header compact. Admin screens should show actual work content above the fold.

## Toolbar Standard

Use the existing component:

```blade
<x-admin.toolbar
    :search="request('search', '')"
    :searchPlaceholder="__('messages.search_placeholder')"
    :sort="request('sort', 'newest')"
    :sortOptions="[
        'newest' => __('messages.sort_newest'),
        'oldest' => __('messages.sort_oldest'),
    ]"
    :filters="$filters ?? []"
    :viewMode="request('view', 'table')"
    :showViewToggle="true"
    :showExportImport="true"
    :exportUrl="$exportUrl ?? null"
    :importUrl="$importUrl ?? null"
    :paginator="$items ?? null"
/>
```

Actual component props are defined in:

- [toolbar.blade.php](D:/xmapp/htdocs/DataPOS/resources/views/components/admin/toolbar.blade.php)

Do not invent unsupported props such as `showSearch` or `filterCount` unless the component is updated first.

## Table Standard

Tables should be optimized for real shop data:

- sticky header for long lists,
- horizontal scroll on small screens,
- `tabular-nums` / monospace style for money, quantity, date, reference numbers,
- clear row hover state,
- status badge with readable color,
- actions aligned consistently on the right,
- pagination visible at top or bottom when useful.

Required columns depend on module, but most operational tables should include:

- reference no,
- date/time,
- customer/supplier/product,
- amount/quantity/status,
- responsible user when relevant,
- actions.

## Card View Standard

Card view is secondary. It is useful for tablet/mobile or visually rich modules, but table view should remain available for dense business records.

Use:

```text
grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4
```

Each card should include:

- title/reference,
- key amount or quantity,
- status,
- date,
- primary action,
- at most 2-3 secondary details.

## Forms Standard

Forms should be practical for Myanmar shop operators:

- group fields by business meaning,
- use clear required/optional labels,
- validate server-side,
- preserve old input on validation error,
- show field-level error messages,
- use sensible defaults,
- avoid long one-column forms when desktop space is available,
- keep save/cancel actions sticky for long forms.

For money fields:

- use `min="0"` where negative values are invalid,
- use appropriate step such as `step="100"` for MMK where practical,
- format display with `number_format`,
- store decimal/money values consistently according to existing model conventions.

## Localization Standard

Admin views must use translation keys:

```blade
{{ __('messages.save') }}
{{ __('messages.cancel') }}
{{ __('messages.view_details') }}
```

When adding or changing admin UI text, update all three files:

- [lang/en/messages.php](D:/xmapp/htdocs/DataPOS/lang/en/messages.php)
- [lang/my/messages.php](D:/xmapp/htdocs/DataPOS/lang/my/messages.php)
- [lang/zh_CN/messages.php](D:/xmapp/htdocs/DataPOS/lang/zh_CN/messages.php)

Rules:

- Burmese must be natural retail language, not literal machine translation.
- Prefer short, direct, action-oriented Burmese suitable for compact admin UI.
- Preserve the source meaning and business intent while removing unnecessary formal/polite filler.
- Keep terminology consistent across navigation, buttons, forms, tables, settings, dialogs, validation messages, empty states, POS, inventory, reports, and footer.
- Shorten long UI labels when a clearer compact phrase exists; for example, use `ပစ္စည်းထည့်ရန်` instead of an unnecessarily long equivalent such as `ကုန်ပစ္စည်းအသစ်တစ်ခု ထည့်သွင်းရန်` when context already makes the meaning clear.
- Keep standard acronyms in English: `SKU`, `IMEI`, `SN`, `PIN`, `KPay`, `COD`.
- Preserve placeholders and interpolation tokens such as `{name}`, `{count}`, `%s`, and Blade/PHP variables.
- Audit rendered Myanmar UI for wrapping, clipping, button overflow, table-header width, modal width, navigation overflow, and mobile layout regressions.
- Do not rely on fallback text in production admin views.
- Do not leave hardcoded English/Burmese labels in admin views when a translation key should be used.

## Security and CSP Standard

Every admin page must follow these rules:

- Add `@csrf` to all POST/PUT/PATCH/DELETE forms.
- Avoid inline `onclick`, `onchange`, `onsubmit` handlers.
- Use Alpine.js bindings or shared JS helpers instead.
- Scope all data queries to current `StoreContext` / `store_id`.
- Protect manager-only actions with route middleware and controller checks.
- Validate route-model-bound records against the current store.
- Confirm destructive actions and explain impact in plain Burmese.

## Store Isolation Checklist

For every admin module:

- [ ] Route uses `EnsureStoreAccess` where store roles are required.
- [ ] Controller reads current store from `StoreContext`.
- [ ] Queries include `where('store_id', $store->id)` or equivalent relationship scope.
- [ ] `update`, `destroy`, `show`, export, print, and AJAX endpoints cannot access another store's records.
- [ ] Tests cover same-store allowed and cross-store blocked cases for sensitive modules.

## Responsive Standard

Support these practical environments:

- 1366x768 laptop,
- low-end POS monitor,
- tablet browser,
- phone browser for quick admin checks.

Rules:

- Important buttons must not overflow on small screens.
- Tables need horizontal scroll instead of broken columns.
- Filters should wrap cleanly.
- Text should not overlap icons or neighboring content.
- Keep tap targets usable on touch devices.

## Visual Style and Theme Standard

Admin UI must support both **High-Contrast Daylight Light Mode** and **True OLED Dark Mode** consistently across layouts, navigation, sidebar, cards, tables, forms, dropdowns, modals, tooltips, toasts, empty states, loading states, and overlays.

### Light Mode: High-Contrast Daylight Standard

Use these semantic targets:

- page/background surface: `#f4f6f8`,
- cards, tables, and primary panels: `#ffffff`,
- default border: `#cbd5e1`,
- stronger border when extra separation is required: `#94a3b8`,
- primary text/icons: `#0f172a`,
- secondary text/icons: `#1e293b`,
- muted/supporting text: `#334155`.

Rules:

- Main pages should feel crisp and readable in bright/daylight environments.
- Cards and table panels should remain clearly separated from the daylight page background.
- Borders on cards, tables, inputs, selects, and interactive panels must not become washed out or invisible.
- Avoid unnecessarily pale text for important business information.
- Money, totals, stock, status, and primary actions must remain immediately readable.

### Dark Mode: True OLED Dark Standard

Use these semantic targets:

- main background and major outer/sidebar surfaces: `#000000`,
- inner cards/panels: `#0a0f1d` or `#111827`,
- subtle/default dark border: `#1e293b`,
- stronger dark border: `#334155`,
- primary text/icons: `#f8fafc`,
- secondary text/icons: `#e2e8f0`,
- muted/supporting text: `#cbd5e1`.

Rules:

- The major application canvas and sidebar should use true OLED black where appropriate instead of a gray-looking page background.
- Inner surfaces should use deep slate to create subtle hierarchy without excessive elevation effects.
- Borders/dividers must remain visible but subdued.
- Do not place low-contrast dark gray text on black backgrounds.
- Inputs, selects, tables, dropdowns, modals, tooltips, dialogs, search UI, toasts, empty states, and loading states must all have complete dark-mode styling.
- Dark mode must not be implemented only at the page wrapper level; audit nested/shared components too.

### Theme Architecture

Prefer centralized semantic theme tokens / CSS variables or the project's shared Tailwind theme layer instead of scattering raw color values through individual Blade files.

Recommended semantic names:

```css
--bg-page
--bg-surface
--bg-elevated
--text-primary
--text-secondary
--text-muted
--border-default
--border-strong
```

If the project already has an equivalent token/theme architecture, extend it rather than creating a competing system.

Use color meaning consistently:

- success: green/emerald,
- warning: amber/yellow,
- danger: red/rose,
- info: blue/indigo,
- primary action: existing violet/indigo pattern unless the module already has a strong established color.

For both themes, verify normal, hover, focus, selected, disabled, error, warning, and success states. Maintain visible keyboard focus and sufficient text/control contrast. Do not rely on color alone to communicate critical state.

Do not overuse purple gradients. A consistent admin tool should feel calm and readable.

## Print and Hardware UI

For receipt, barcode, voucher, and printer screens:

- show paper size clearly: `58mm`, `80mm`, `A4`, etc.
- preview before print where possible,
- avoid hidden destructive printer settings,
- keep hardware notes practical and short,
- mark features as unverified until tested with real hardware.

## New Admin View Checklist

- [ ] Uses `layouts.admin.app`.
- [ ] Uses compact `main_padding`.
- [ ] Uses translated labels.
- [ ] Uses existing `<x-admin.toolbar>` props correctly.
- [ ] Has table view for dense records.
- [ ] Has responsive behavior for tablet/phone.
- [ ] Has clear empty state.
- [ ] Has server-side validation.
- [ ] Has authorization and store isolation.
- [ ] Avoids inline JS event handlers.
- [ ] Uses High-Contrast Daylight Light Mode tokens consistently.
- [ ] Uses True OLED Dark Mode tokens consistently, including nested/shared components.
- [ ] Uses approximately 4px page-level sibling section spacing unless a larger semantic separation is justified.
- [ ] Burmese labels are concise, natural, consistent, and checked in the rendered layout.
- [ ] Runs targeted tests.
- [ ] Runs `npm run build` if Blade/Tailwind classes or JS changed.

## Theme, Spacing, and Localization Audit Checklist

Before marking an admin page as polished:

### Light Mode

- [ ] Main page background is consistent with `#f4f6f8`.
- [ ] Primary cards/table panels use `#ffffff`.
- [ ] Borders are clearly visible using `#cbd5e1` / `#94a3b8` or equivalent semantic tokens.
- [ ] Important text/icons use high-contrast dark slate values.
- [ ] Inputs, tables, dropdowns, modals, and shared components follow the same theme.

### OLED Dark Mode

- [ ] Main canvas/sidebar uses true black `#000000` where appropriate.
- [ ] Inner cards/panels use `#0a0f1d` / `#111827` or equivalent tokens.
- [ ] Borders/dividers use subdued slate values.
- [ ] Primary/secondary/muted text remains clearly readable.
- [ ] No nested component falls back to an unintended light surface.
- [ ] Hover, focus, selected, disabled, validation, and status states remain clear.

### Spacing

- [ ] Direct page-level sections are approximately 4px apart by default.
- [ ] Duplicate margin/padding does not inflate section gaps.
- [ ] Internal controls remain readable and touch-friendly.
- [ ] Mobile layout keeps compact outer padding without body-level horizontal overflow.

### Myanmar Localization

- [ ] Burmese labels are concise and natural.
- [ ] Buttons use clear action-oriented wording.
- [ ] Terminology is consistent across modules.
- [ ] Standard acronyms remain unchanged where appropriate.
- [ ] Translation placeholders are preserved.
- [ ] No avoidable hardcoded UI labels remain.
- [ ] Rendered Burmese text does not clip, overlap, or break responsive layouts.

## Refactor Rule

When improving UI, do not redesign every admin page at once. Prioritize pages that appear in the sales demo and daily workflow:

1. POS sale.
2. Products and import.
3. Customer receivables.
4. Stock count and ledger.
5. Daily closing.
6. Profit and loss.
7. Settings, voucher, printer.

Large UI consistency work should be done module by module with screenshots and targeted tests.
