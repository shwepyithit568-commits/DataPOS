# DataPOS - Admin UI/UX Standard Guide

**Document Version:** 3.0.0
**Last Updated:** 2026-08-27
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
<div class="w-full space-y-2 sm:space-y-2.5">
    {{-- page content --}}
</div>
@endsection
```

Rules:

- Use `@section('main_padding', 'p-2')` for dense admin work screens.
- Keep page sections flat. Do not place cards inside cards.
- Use tables for scanning/comparing rows.
- Use cards only for repeated items, mobile-friendly summaries, modals, or genuinely framed tools.
- Make destructive actions visually separate from normal actions.

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
- Keep standard acronyms in English: `SKU`, `IMEI`, `SN`, `PIN`, `KPay`, `COD`.
- Do not rely on fallback text in production admin views.

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

## Visual Style Standard

Preferred base:

- surfaces: `bg-white`, `dark:bg-slate-900`,
- borders: `border-slate-200/80`, `dark:border-slate-800`,
- primary text: `text-slate-900`, `dark:text-slate-100`,
- secondary text: `text-slate-600`, `dark:text-slate-300`,
- muted text: `text-slate-500`, `dark:text-slate-400`.

Use color meaning consistently:

- success: green/emerald,
- warning: amber/yellow,
- danger: red/rose,
- info: blue/indigo,
- primary action: existing violet/indigo pattern unless the module already has a strong established color.

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
- [ ] Supports light/dark mode.
- [ ] Runs targeted tests.
- [ ] Runs `npm run build` if Blade/Tailwind classes or JS changed.

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
