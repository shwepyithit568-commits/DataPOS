# DataPOS Admin UI/UX Standard Reference Guide 📋🎨

> **Document Version**: 1.0 (Production Standard)  
> **Target Audience**: Developers, Designers & System Architects  
> **Source Module Reference**: `Admin Product Management Module` (`/store/{store}/admin/products`)  
> **Last Updated**: August 2026  

---

## Table of Contents
1. [Layout & Full-Bleed Structure](#1-layout--full-bleed-structure)
2. [Hero Header Standards](#2-hero-header-standards)
3. [Summary Stat Cards Architecture](#3-summary-stat-cards-architecture)
4. [Master Toolbar Component (`<x-admin.toolbar>`)](#4-master-toolbar-component-xadmin-toolbar)
5. [Spreadsheet Data Grid Table Architecture](#5-spreadsheet-data-grid-table-architecture)
6. [Responsive Card View Grid Architecture](#6-responsive-card-view-grid-architecture)
7. [Bulk Actions Architecture](#7-bulk-actions-architecture)
8. [Export & Import System Standards](#8-export--import-system-standards)
9. [Localization Standards (EN / MY / ZH)](#9-localization-standards-en--my--zh)
10. [CSP & Security Standards](#10-csp--security-standards)
11. [Ready-to-Use Blade Boilerplate](#11-ready-to-use-blade-boilerplate)

---

## 1. Layout & Full-Bleed Structure

All admin pages must be clean, spacious, borderless, and maximize screen real estate for desktop monitors, laptops, POS terminals, and tablets.

### Layout Container Configuration (`resources/views/layouts/admin/app.blade.php`)
The admin layout allows pages to define custom padding via `@section('main_padding')`:

```blade
<main class="flex-1 overflow-y-auto @yield('main_padding', 'p-2 sm:p-2.5 lg:p-3') bg-slate-50 dark:bg-slate-900/60 transition-colors duration-200">
    {{ $slot ?? '' }}
    @yield('content')
</main>
```

### Full-Bleed Setting for Child Pages
Place this at the top of your Blade view to get 8px edge-to-edge layout:
```blade
@extends('layouts.admin.app')

@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">
    {{-- Page Sections Here --}}
</div>
@endsection
```

---

## 2. Hero Header Standards

The hero header provides context, store branding, subtitle, and primary actions.

```blade
<header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
    <div class="min-w-0">
        {{-- Eyebrow Pill --}}
        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-violet-100 dark:border-violet-900/60 mb-0.5">
            <span>📦</span>
            <span>{{ __('messages.module_eyebrow') }}</span>
        </div>
        {{-- Title --}}
        <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            {{ __('messages.page_title') }}
        </h1>
        {{-- Subtitle with Store Name --}}
        <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            {{ $store->name }} · {{ __('messages.page_subtitle') }}
        </p>
    </div>
    
    {{-- Actions Row --}}
    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
        <a href="{{ route('store.admin.module.preset', $baseParams) }}"
           class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs active:scale-95">
            <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h7l2 2h9v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z"/></svg>
            <span>{{ __('messages.secondary_action') }}</span>
        </a>
        <a href="{{ route('store.admin.module.create', $baseParams) }}"
           class="px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>{{ __('messages.create_action') }}</span>
        </a>
    </div>
</header>
```

---

## 3. Summary Stat Cards Architecture

Responsive 4-column metric cards that act as active filter triggers on click.

```blade
@php
    $statAccents = [
        'primary'   => 'bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300',
        'success'   => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300',
        'danger'    => 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300',
        'warning'   => 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300',
    ];
@endphp

<div class="w-full grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5" role="list" aria-label="Summary Cards">
    {{-- Card Item --}}
    <a href="{{ $filterUrl }}" role="listitem"
       class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-3 transition-all duration-200 hover:shadow-sm active:scale-[.99]">
        <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center {{ $statAccents['primary'] }} shadow-inner">
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-base sm:text-xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                {{ number_format($count) }}
            </p>
            <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                {{ __('messages.card_label') }}
            </p>
        </div>
    </a>
</div>
```

---

## 4. Master Toolbar Component (`<x-admin.toolbar>`)

The reusable Toolbar component provides standardized Search, Filters, Sort, View Modes, Import/Export, and Inline Mini-Pagination.

### Usage in Blade View
```blade
<x-admin.toolbar
    :search="request('search', '')"
    :searchPlaceholder="__('messages.search_placeholder')"
    :sort="request('sort', 'newest')"
    :sortOptions="[
        'newest'     => __('messages.sort_newest'),
        'oldest'     => __('messages.sort_oldest'),
        'name_asc'   => __('messages.sort_name_asc'),
        'name_desc'  => __('messages.sort_name_desc'),
    ]"
    :showViewToggle="true"
    :activeView="$activeView ?? 'table'"
    :filterCount="$activeFiltersCount ?? 0"
    :showExportImport="true"
    :exportUrl="$exportUrl"
    :importUrl="$importUrl"
    :showPagination="true"
    :paginator="$items"
    :showPerPageSelector="true"
    :perPageOptions="[
        25     => '25',
        50     => '50',
        100    => '100',
        200    => '200',
        'all'  => __('messages.all'),
    ]"
>
    {{-- Filter Dropdown Slot --}}
    <x-slot:filterSlot>
        <div class="space-y-3 p-1">
            {{-- Filter Fields Here --}}
        </div>
    </x-slot:filterSlot>
</x-admin.toolbar>
```

### Key Toolbar Features Built-in:
1. **Interactive Mini-Paginator**: `◀ Prev` and `Next ▶` buttons with disabled states + Direct Jump Select `p / total_pages`.
2. **Items Per Page**: Seamless dropdown with `data-auto-submit` preserving all query parameters.
3. **Item Range Counter**: Monospace range badge (e.g. `1–50 / 180`).
4. **Unified Export Split Button**:
   - Left click: Direct `.xlsx` download.
   - Right arrow click: Opens modern **Floating Action Modal** with choices for **Microsoft Excel (.xlsx)** and **CSV Document (.csv)**.

---

## 5. Spreadsheet Data Grid Table Architecture

For desktop/laptop data entry and review, styled in Google Sheets style with sticky headers and zebra hover states.

```blade
<div id="data-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
    <div class="overflow-x-auto max-h-[75vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
        <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
            {{-- Sticky Header --}}
            <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                    <th class="py-2.5 px-2.5 w-12 text-center bg-slate-200/70 dark:bg-slate-800">
                        <input type="checkbox" x-model="selectAll" @change="toggleAll({{ json_encode($items->pluck('id')) }})" class="w-3.5 h-3.5 rounded border-slate-400 text-violet-600 cursor-pointer" />
                    </th>
                    <th class="py-2.5 px-3">{{ __('messages.name') }}</th>
                    <th class="py-2.5 px-3 text-right">{{ __('messages.amount') }}</th>
                    <th class="py-2.5 px-3 text-center">{{ __('messages.status') }}</th>
                    <th class="py-2.5 px-3 text-center w-32">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            {{-- Table Body --}}
            <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                @forelse ($items as $item)
                    <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                        <td class="py-2.5 px-2.5 text-center">
                            <input type="checkbox" :value="{{ $item->id }}" x-model="selectedIds" class="w-3.5 h-3.5 rounded border-slate-300 text-violet-600" />
                        </td>
                        <td class="py-2.5 px-3 font-semibold">{{ $item->name }}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold">{{ number_format($item->amount) }}</td>
                        <td class="py-2.5 px-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Active</span>
                        </td>
                        <td class="py-2.5 px-3 text-center">
                            {{-- Action buttons --}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-400">
                            {{ __('messages.no_records_found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
```

---

## 6. Responsive Card View Grid Architecture

For touchscreens, mobile devices, and visual card browsing:

```blade
<div x-show="viewMode === 'card'" class="w-full grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-2 sm:gap-2.5">
    @forelse ($items as $item)
        <div class="w-full bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg overflow-hidden transition shadow-2xs flex flex-col justify-between group hover:border-violet-300 dark:hover:border-violet-700/70 hover:shadow-sm">
            <div class="p-2 sm:p-3">
                {{-- Card Header & Badges --}}
                <div class="flex items-center justify-between mb-1.5">
                    <input type="checkbox" :value="{{ $item->id }}" x-model="selectedIds" class="w-4 h-4 rounded text-violet-600" />
                    <span class="text-[10px] font-black uppercase px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800">{{ $item->code }}</span>
                </div>
                {{-- Content --}}
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 line-clamp-2">{{ $item->name }}</h4>
                <div class="mt-2 text-sm font-black text-violet-600 font-mono">{{ number_format($item->amount) }} Ks</div>
            </div>
        </div>
    @empty
        {{-- Empty state --}}
    @endforelse
</div>
```

---

## 7. Bulk Actions Architecture

The bulk action bar slides up whenever `selectedIds.length > 0`:

```blade
<div id="bulk-actions-bar" x-show="selectedIds.length > 0" x-cloak 
     class="w-full bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 p-2.5 sm:p-3 rounded-lg shadow-lg text-sm border-2 border-violet-500/40 dark:border-violet-500/50">
    <div class="flex flex-wrap items-center justify-between gap-2.5">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-violet-600 animate-pulse"></span>
            <span class="font-black text-xs sm:text-sm">
                <span x-text="selectedIds.length" class="text-violet-600 font-bold"></span> {{ __('messages.items_selected') }}
            </span>
            <button type="button" @click="selectAll = false; selectedIds = []" class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded text-xs">
                {{ __('messages.cancel') }}
            </button>
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            {{-- Bulk Action Form --}}
            <form method="POST" action="{{ $bulkActionUrl }}" class="inline">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="ids[]" :value="id" />
                </template>
                <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-2xs">
                    {{ __('messages.bulk_action_button') }}
                </button>
            </form>
        </div>
    </div>
</div>
```

---

## 8. Export & Import System Standards

### Export Controller Pattern (`BinaryFileResponse`)
To prevent Chrome UUID download bugs and ensure proper file names:

```php
use Symfony\Component\HttpFoundation\BinaryFileResponse;

public function export(Request $request): BinaryFileResponse
{
    $format = $request->get('format', 'xlsx') === 'csv' ? 'csv' : 'xlsx';
    $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.' . $format;
    $filename = 'items-' . date('Y-m-d') . '.' . $format;

    // Generate file into $tempFile
    if ($format === 'xlsx') {
        $spreadsheet = new Spreadsheet();
        // Populate spreadsheet...
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    } else {
        $handle = fopen($tempFile, 'w');
        fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
        // Write CSV rows...
        fclose($handle);
        $mime = 'text/csv; charset=UTF-8';
    }

    return response()->download($tempFile, $filename, [
        'Content-Type'        => $mime,
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Content-Length'      => (string) filesize($tempFile),
    ])->deleteFileAfterSend(true);
}
```

### Import UI Standards
- Drag & Drop Dropzone for `.xlsx` / `.csv` files.
- Real-time client preview / Server validation preview with statistics cards (Total, New, Updates, Skipped, Errors).
- Download standard Excel template button (`product-import-template.xlsx`).

---

## 9. Localization Standards (EN / MY / ZH)

Always add translation keys across all 3 locale files in `lang/`:
1. `lang/en/messages.php` (English)
2. `lang/my/messages.php` (Burmese - မြန်မာ)
3. `lang/zh_CN/messages.php` (Simplified Chinese - 中文)

---

## 10. CSP & Security Standards

1. **Never use inline `onclick` or `onchange="this.form.submit()"`**: Use `data-auto-submit` handled by `resources/js/csp-helpers.js`.
2. **Scope all actions to the current store**: Always verify `$store->id` in queries and authorize requests with policies.
3. **Use CSRF tokens**: Always add `@csrf` inside forms.

---

## 11. Form & Create/Edit Pages Standard Architecture

For all admin create and edit forms:
1. **Full-Bleed 8px Margin**: Use `@section('main_padding', 'p-2')`.
2. **Compact Section Spacing**: Outer wrapper uses `space-y-2 sm:space-y-2.5`.
3. **Streamlined Card Containers**:
   ```blade
   $section = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3';
   $input = 'w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition';
   $fileInput = 'block w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-slate-800 dark:file:text-violet-300 rounded-lg border border-slate-200 dark:border-slate-700 p-1.5 bg-slate-50 dark:bg-slate-800/60';
   ```
4. **Sticky Bottom Action Bar**:
   ```blade
   <div class="sticky bottom-0 z-20 w-full border border-slate-200/90 bg-white/95 px-3 py-2.5 sm:px-4 backdrop-blur-md shadow-[0_-4px_16px_rgba(15,23,42,0.06)] dark:border-slate-800/90 dark:bg-slate-900/95 rounded-lg">
       <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
           {{-- Checkboxes / toggles --}}
           <div class="flex items-center gap-2">
               <a href="{{ $returnTo }}" class="px-3.5 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold">{{ __('messages.cancel') }}</a>
               <button type="submit" class="px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-black text-xs shadow-md shadow-violet-500/20">💾 {{ __('messages.save') }}</button>
           </div>
       </div>
   </div>
   ```

---

## 12. Summary Checklist for New Admin Pages

- [ ] `@section('main_padding', 'p-2')` added for full-bleed 8px padding.
- [ ] Compact Hero Header with eyebrow badge and store name context.
- [ ] Responsive 4-up Summary Stat cards with active filter clicks.
- [ ] Standard `<x-admin.toolbar>` with live search, filters, sort, view toggle, and pagination.
- [ ] Google Sheets style Data Grid table (`sticky` header, borderless rows).
- [ ] Card View grid (`grid-cols-2 md:grid-cols-3 xl:grid-cols-5`).
- [ ] Bulk actions bar with reactive Alpine state (`selectedIds`).
- [ ] Create / Edit Forms use `rounded-lg` inputs, `space-y-2.5` gaps, and sticky full-width bottom bar.
- [ ] Export controller using `BinaryFileResponse` with `deleteFileAfterSend(true)`.
- [ ] All keys translated in EN, MY, and ZH_CN.

