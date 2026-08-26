# DataPOS Admin UI/UX Standard Reference Guide 📋🎨

> **Document Version**: 2.0 (Production Standard)  
> **Target Audience**: Developers, Designers & System Architects  
> **Source Module Reference**: `Admin Product Management Module`, `Stock Adjustments`, `Stock Count`, `Warranty Tracker`, `Stock Ledger`  
> **Last Updated**: August 2026  

---

## Table of Contents
1. [Layout & Full-Bleed Structure](#1-layout--full-bleed-structure)
2. [Dark & Light Mode Design System](#2-dark--light-mode-design-system)
3. [Hero Header Standards](#3-hero-header-standards)
4. [Summary Stat Cards Architecture](#4-summary-stat-cards-architecture)
5. [Master Toolbar Component (`<x-admin.toolbar>`)](#5-master-toolbar-component-xadmin-toolbar)
6. [Spreadsheet Data Grid Table Architecture](#6-spreadsheet-data-grid-table-architecture)
7. [Modern Multi-Column Card Grid Architecture](#7-modern-multi-column-card-grid-architecture)
8. [Bulk Actions Architecture](#8-bulk-actions-architecture)
9. [Export & Import System Standards](#9-export--import-system-standards)
10. [Form & Create/Edit Pages Standard Architecture](#10-form--createedit-pages-standard-architecture)
11. [Localization Standards (EN / MY / ZH)](#11-localization-standards-en--my--zh)
12. [CSP & Security Standards](#12-csp--security-standards)
13. [Ready-to-Use Blade Boilerplate](#13-ready-to-use-blade-boilerplate)
14. [Checklist for Every New Admin View](#14-checklist-for-every-new-admin-view)

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
Place this at the top of your Blade view to get 8px edge-to-edge layout with synchronized view mode switching:
```blade
@extends('layouts.admin.app')

@section('title', __('messages.page_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{ viewMode: localStorage.getItem('admin_view_mode') || 'table' }"
     @view-changed.window="viewMode = $event.detail">
    {{-- Page Sections Here --}}
</div>
@endsection
```

---

## 2. Dark & Light Mode Design System

DataPOS supports a rich, high-contrast Dark and Light mode theme designed specifically for eye comfort during long working shifts in stores and warehouses.

### Standard Color Tokens

| UI Element | Light Mode | Dark Mode | Notes |
| :--- | :--- | :--- | :--- |
| **Page Background** | `bg-slate-50` | `dark:bg-slate-950` or `dark:bg-slate-900/60` | Clean, subtle contrast |
| **Card / Surface Background** | `bg-white` | `dark:bg-slate-900` | Pure card surface |
| **Secondary Sub-Box Surface** | `bg-slate-50/80` | `dark:bg-slate-800/50` | For inner hero boxes & metrics |
| **Primary Border** | `border-slate-200/80` | `dark:border-slate-800` | Subtle, clean demarcation |
| **Table Grid Divider** | `divide-slate-200/80` | `dark:divide-slate-800` | Google Sheets style borders |
| **Primary Text (Headings)** | `text-slate-900` | `dark:text-slate-100` | Maximum readability |
| **Secondary Text (Labels)** | `text-slate-700` | `dark:text-slate-300` | Clear labels & values |
| **Muted Text (Meta/Timestamp)** | `text-slate-400` / `500` | `dark:text-slate-400` / `500` | Subtle metadata |
| **Interactive Hover Border** | `hover:border-violet-300` | `dark:hover:border-violet-600/50` | Micro-interaction cue |

### Status & Accent Pill Badges (Light & Dark Pairs)

```blade
{{-- 1. Success / Active / Approved / Inflow --}}
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
    <span>✓</span> <span>{{ __('messages.status_approved') }}</span>
</span>

{{-- 2. Warning / In Progress / Expiring Soon / Variance --}}
<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
    <span>{{ __('messages.status_in_progress') }}</span>
</span>

{{-- 3. Danger / Expired / Cancelled / Rejected / Outflow --}}
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
    <span>✕</span> <span>{{ __('messages.status_cancelled') }}</span>
</span>

{{-- 4. Info / Special / Category / Repaired --}}
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
    <span>🛠️</span> <span>{{ __('messages.status_claimed') }}</span>
</span>
```

---

## 3. Hero Header Standards

The hero header provides context, store branding, subtitle, and primary actions:

```blade
<div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
    <div class="min-w-0">
        {{-- Eyebrow Pill --}}
        <div class="flex items-center gap-1.5 mb-0.5">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                <span>📦</span>
                <span>{{ __('messages.sidebar_inventory') }}</span>
            </span>
            <span class="text-slate-300 dark:text-slate-700">/</span>
            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
        </div>
        {{-- Title --}}
        <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
            <span>{{ __('messages.page_title') }}</span>
            <span class="text-xs font-mono font-bold text-slate-400">({{ number_format($items->total()) }})</span>
        </h1>
        {{-- Subtitle --}}
        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ __('messages.page_subtitle') }}</p>
    </div>

    {{-- Actions Row --}}
    <div class="flex flex-wrap items-center gap-1.5 shrink-0">
        {{-- Secondary Action --}}
        <a href="{{ route('store.admin.related.index', ['store_slug' => $store->slug]) }}"
           class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
            <span>📑</span>
            <span>{{ __('messages.secondary_action') }}</span>
        </a>

        {{-- Primary Action Button --}}
        <a href="{{ route('store.admin.module.create', ['store_slug' => $store->slug]) }}"
           class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>{{ __('messages.create_action') }}</span>
        </a>
    </div>
</div>
```

---

## 4. Summary Stat Cards Architecture

Click-to-filter 4-up metric cards that update the query string when clicked:

```blade
<div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5" role="list" aria-label="Status Metrics">
    {{-- Total Card --}}
    <a href="{{ route('store.admin.module.index', array_merge(['store_slug' => $store->slug], request()->except('status', 'page'))) }}"
       class="p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ empty($status) || $status === 'all' ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }}">
        <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner">
            <span class="text-base sm:text-lg">📦</span>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">
                {{ number_format($stats['total']) }}
            </p>
            <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                {{ __('messages.stat_total') }}
            </p>
        </div>
    </a>

    {{-- Active / In-Progress Card --}}
    <a href="{{ route('store.admin.module.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => 'active'])) }}"
       class="p-2.5 sm:p-3 rounded-lg border transition-all duration-200 shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ ($status ?? '') === 'active' ? 'border-emerald-600 bg-emerald-50/60 dark:border-emerald-500 dark:bg-emerald-950/40 ring-2 ring-emerald-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }}">
        <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner relative">
            <span class="text-base sm:text-lg">✅</span>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-mono">
                {{ number_format($stats['active']) }}
            </p>
            <p class="text-[10px] sm:text-[11px] text-emerald-700 dark:text-emerald-300/80 mt-1 truncate font-bold uppercase tracking-wider">
                {{ __('messages.stat_active') }}
            </p>
        </div>
    </a>
</div>
```

---

## 5. Master Toolbar Component (`<x-admin.toolbar>`)

The reusable Toolbar component provides standardized Search, Filters, Sort, View Modes, Import/Export, and Inline Mini-Pagination.

```blade
<x-admin.toolbar
    :showSearch="true"
    :searchPlaceholder="__('messages.search') . ' product, Ref No, customer...'"
    :searchValue="$filters['search'] ?? ''"
    :filterCount="$activeFiltersCount ?? 0"
    :showViewToggle="true"
    :activeView="'table'"
    :showSort="true"
    :sort="$filters['sort'] ?? 'newest'"
    :sortOptions="[
        'newest'     => __('messages.sort_newest'),
        'oldest'     => __('messages.sort_oldest'),
        'qty_desc'   => __('messages.sort_qty_high'),
        'qty_asc'    => __('messages.sort_qty_low'),
    ]"
    :showExportImport="true"
    :exportUrl="$exportUrl ?? null"
    :showPagination="true"
    :paginator="$items"
    :showPerPageSelector="true"
    :perPageOptions="[
        15    => '15',
        25    => '25',
        50    => '50',
        100   => '100',
        'all' => __('messages.all'),
    ]"
>
    {{-- Quick Status Filter Tabs --}}
    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs shrink-0 overflow-x-auto">
        @foreach([
            '' => __('messages.all'),
            'pending' => __('messages.status_pending'),
            'approved' => __('messages.status_approved'),
            'rejected' => __('messages.status_rejected'),
        ] as $stVal => $stLabel)
            <a href="{{ route('store.admin.module.index', array_merge(['store_slug' => $store->slug], request()->except('page'), ['status' => $stVal])) }}"
               class="px-2.5 py-1 rounded-md text-xs font-bold transition whitespace-nowrap {{ ($filters['status'] ?? '') === $stVal ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                {{ $stLabel }}
            </a>
        @endforeach
    </div>

    {{-- Filter Dropdown Slot --}}
    <x-slot:filterSlot>
        <div class="space-y-3 p-1 text-xs">
            {{-- Custom Filters Here --}}
            @if($activeFiltersCount > 0)
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <a href="{{ route('store.admin.module.index', ['store_slug' => $store->slug]) }}"
                       class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
                        {{ __('messages.reset') }}
                    </a>
                </div>
            @endif
        </div>
    </x-slot:filterSlot>
</x-admin.toolbar>
```

---

## 6. Spreadsheet Data Grid Table Architecture

Desktop-optimized, Google Sheets style with sticky headers and clean divide-x borders:

```blade
<div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
    <div class="overflow-x-auto max-h-[75vh] overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800">
        <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
            <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                    <th class="py-2.5 px-3 min-w-[150px]">{{ __('messages.reference_number') }}</th>
                    <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.product') }}</th>
                    <th class="py-2.5 px-3 text-right min-w-[110px]">{{ __('messages.quantity') }}</th>
                    <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.status') }}</th>
                    <th class="py-2.5 px-3 min-w-[130px]">{{ __('messages.date') }}</th>
                    <th class="py-2.5 px-3 text-center w-28">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                @forelse ($items as $item)
                    <tr class="divide-x divide-slate-200/80 dark:divide-slate-800 hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                        <td class="py-2 px-3 font-mono font-bold text-violet-600 dark:text-violet-400">
                            {{ $item->ref_number }}
                        </td>
                        <td class="py-2 px-3 font-semibold text-slate-900 dark:text-slate-100">
                            {{ $item->name }}
                        </td>
                        <td class="py-2 px-3 text-right font-mono font-black text-slate-900 dark:text-slate-100">
                            {{ number_format($item->quantity) }}
                        </td>
                        <td class="py-2 px-3 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                Approved
                            </span>
                        </td>
                        <td class="py-2 px-3 text-xs text-slate-500 font-mono">
                            {{ $item->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-2 px-3 text-center whitespace-nowrap">
                            {{-- Action buttons --}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">
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

## 7. Modern Multi-Column Card Grid Architecture

For all responsive card views across admin modules (Stock Adjustments, Stock Count, Warranty, Stock Ledger, Products, Purchases, etc.):

### Standard Grid Configuration
Use `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3` to provide fluid 1-column on mobile, 2-column on tablet, 3-column on laptop, and 4-column on desktop.

### Complete Card Template

```blade
<div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
    @forelse ($items as $item)
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:border-violet-300 dark:hover:border-violet-600/50 hover:shadow-sm transition flex flex-col justify-between group overflow-hidden">
            
            {{-- Top Card Content --}}
            <div class="p-3 sm:p-3.5 space-y-2.5">
                
                {{-- 1. Header: Ref / Title + Status Pill --}}
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                    <div>
                        <a href="{{ route('store.admin.module.show', ['store_slug' => $store->slug, 'id' => $item->id]) }}"
                           class="font-mono font-black text-xs sm:text-sm text-violet-600 dark:text-violet-400 hover:underline tracking-tight block">
                            {{ $item->ref_number }}
                        </a>
                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                            {{ $item->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>

                    {{-- Status Badge with Glowing Ping Dot if Pending/Active --}}
                    @if($item->isPending())
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                            <span>Pending</span>
                        </span>
                    @elseif($item->isApproved())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <span>✓</span>
                            <span>Approved</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                            <span>✕</span>
                            <span>Rejected</span>
                        </span>
                    @endif
                </div>

                {{-- 2. Hero Metric Box (Highlight Primary Numbers / Progress / Delta) --}}
                <div class="p-2.5 rounded-lg bg-slate-50/80 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            Metric Title:
                        </span>
                        <span class="font-black font-mono text-sm sm:text-base text-slate-900 dark:text-slate-100">
                            {{ number_format($item->main_value) }} Units
                        </span>
                    </div>
                    {{-- Secondary Sub-Metric --}}
                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/50 dark:border-slate-700/50 font-mono">
                        <span class="text-[10px] text-slate-400 font-sans">Sub-Detail</span>
                        <span class="font-bold text-slate-700 dark:text-slate-300 text-xs">Value</span>
                    </div>
                </div>

                {{-- 3. Metadata Rows (Creator, Branch, Warehouse, Notes) --}}
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between pt-0.5">
                    <span>ဆောင်ရွက်သူ: <strong class="text-slate-700 dark:text-slate-300">{{ $item->creator?->name ?? 'System' }}</strong></span>
                    @if($item->branch)
                        <span class="text-[10px] text-slate-400">({{ $item->branch->name }})</span>
                    @endif
                </div>

                @if($item->notes)
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/40 p-1.5 rounded border border-slate-200/60 dark:border-slate-800 line-clamp-2">
                        <strong>မှတ်ချက်:</strong> {{ $item->notes }}
                    </div>
                @endif
            </div>

            {{-- 4. Card Action Footer --}}
            <div class="p-2.5 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                {{-- Secondary Action (Print / Export / Sub-View) --}}
                <a href="{{ route('store.admin.module.print', ['store_slug' => $store->slug, 'id' => $item->id]) }}"
                   target="_blank"
                   title="Print"
                   class="p-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition text-xs font-bold flex items-center gap-1">
                    <span>🖨️</span>
                    <span>Print</span>
                </a>

                {{-- Primary Action Link --}}
                <a href="{{ route('store.admin.module.show', ['store_slug' => $store->slug, 'id' => $item->id]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs bg-violet-600 hover:bg-violet-700 text-white active:scale-95">
                    <span>{{ __('messages.view_details') }}</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    @empty
        {{-- Standard Empty State for Card Grid --}}
        <div class="col-span-full py-12 px-4 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 shadow-2xs">
            <span class="text-4xl mb-2 block">📋</span>
            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ __('messages.no_records_found') }}</p>
            <a href="{{ route('store.admin.module.create', ['store_slug' => $store->slug]) }}"
               class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-violet-600 text-white hover:bg-violet-700 shadow-md transition">
                <span>+</span>
                <span>{{ __('messages.create_new') }}</span>
            </a>
        </div>
    @endforelse
</div>
```

---

## 8. Bottom Pagination Container

Every index page must include standard pagination links wrapped inside a matching card container:

```blade
@if($items->hasPages())
    <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        {{ $items->links() }}
    </div>
@endif
```

---

## 9. Bulk Actions Architecture

The bulk action bar slides up whenever items are checked:

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

## 10. Form & Create/Edit Pages Standard Architecture

1. **Full-Bleed 8px Margin**: Use `@section('main_padding', 'p-2')`.
2. **Compact Section Spacing**: Outer wrapper uses `space-y-2 sm:space-y-2.5`.
3. **Streamlined Card Containers & Inputs**:
   ```blade
   $section = 'w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3';
   $input = 'w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 font-semibold focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500/40 outline-none transition';
   ```
4. **Sticky Bottom Action Bar**:
   ```blade
   <div class="sticky bottom-0 z-20 w-full border border-slate-200/90 bg-white/95 px-3 py-2.5 sm:px-4 backdrop-blur-md shadow-[0_-4px_16px_rgba(15,23,42,0.06)] dark:border-slate-800/90 dark:bg-slate-900/95 rounded-lg">
       <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
           <div class="flex items-center gap-2">
               <a href="{{ $returnTo }}" class="px-3.5 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold">{{ __('messages.cancel') }}</a>
               <button type="submit" class="px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white font-black text-xs shadow-md shadow-violet-500/20">💾 {{ __('messages.save') }}</button>
           </div>
       </div>
   </div>
   ```

---

## 11. Localization Standards & Translation Keys Catalog (EN / MY / ZH)

DataPOS supports 3 primary languages. **Hardcoded text strings in Blade views are strictly prohibited.** Every label, badge, button, table header, sort option, and message must use Laravel's `__('messages.key_name')` helper.

### The 3 Core Translation Files
Whenever adding a new admin view or upgrading an existing one, translation keys must be registered in all three files:
1. `lang/en/messages.php` — **English (Default Technical)**
2. `lang/my/messages.php` — **Burmese (မြန်မာဘာသာ - Primary Business Locale)**
3. `lang/zh_CN/messages.php` — **Simplified Chinese (简体中文)**

---

### Standard Key Naming Taxonomy (Prefix Conventions)

To keep translation files clean, organized, and avoid key collisions:

| Key Category | Naming Pattern | Example Key | English | Burmese (မြန်မာ) |
| :--- | :--- | :--- | :--- | :--- |
| **Sidebar Link** | `sidebar_{module}` | `sidebar_stock_ledger` | Stock Ledger | စတော့ လှုပ်ရှားမှု မှတ်တမ်း |
| **Page Title** | `{module}_title` | `stock_count_title` | Stock Count Sessions | စတော့ ရေတွက်စစ်ဆေးခြင်း |
| **Page Subtitle** | `{module}_sub` | `stock_count_sub` | Physical inventory audit | စတော့ လက်ကျန် အတည်ပြုချက်များ |
| **KPI Stat Total** | `{module}_stat_total` | `stock_count_stat_total` | Total Sessions | စုစုပေါင်း အကြိမ်ရေ |
| **KPI Stat Custom** | `{module}_stat_{name}` | `stock_ledger_stat_inflow` | Total Inbound | စုစုပေါင်း အဝင် |
| **Status Badge** | `status_{name}` / `{module}_status_{name}` | `status_active` | Active / Under Warranty | သက်တမ်းရှိ / အသုံးပြုဆဲ |
| **Sort Option** | `sort_{type}` / `{module}_sort_{type}` | `sort_newest` | Newest First | အသစ်ဆုံး အရင် |
| **Action Button** | `{action}_{module}` | `register_new_warranty` | Register Warranty | အာမခံ အသစ်မှတ်ပုံတင်ရန် |
| **Card Footer Action** | `{module}_view_details` | `view_details` | View Details | အသေးစိတ် ကြည့်ရန် |
| **Empty State Title** | `{module}_no_records` | `stock_ledger_no_movements` | No stock movements recorded | စတော့ လှုပ်ရှားမှု မှတ်တမ်း မရှိသေးပါ |
| **Empty State Button** | `{module}_create_first` | `stock_count_new_session` | New Count Session | ရေတွက်ခြင်း အသစ်စတင်ရန် |

---

### Common Admin UI/UX Translation Matrix (Cheat Sheet)

Use these standard terms across all modules to maintain terminology consistency:

| English (EN) | Burmese (MY - မြန်မာ) | Chinese (ZH - 简体中文) | Recommended Key |
| :--- | :--- | :--- | :--- |
| **Search placeholder** | ရှာဖွေရန်... | 搜索... | `messages.search` |
| **All** | အားလုံး | 全部 | `messages.all` |
| **Active** | သက်တမ်းရှိ / အသုံးပြုဆဲ | 活跃 / 生效中 | `messages.status_active` |
| **Pending** | စောင့်ဆိုင်းဆဲ | 待审核 / 待处理 | `messages.status_pending` |
| **Approved / Completed** | အတည်ပြုပြီး | 已批准 / 已完成 | `messages.status_approved` |
| **Rejected / Cancelled** | ပယ်ချပြီး / ပယ်ဖျက်ပြီး | 已拒绝 / 已取消 | `messages.status_rejected` |
| **In Progress** | ဆောင်ရွက်ဆဲ | 进行中 | `messages.status_in_progress` |
| **Expiring Soon** | သက်တမ်းကုန်ခါနီး | 即将到期 | `messages.status_expiring_soon` |
| **Expired** | သက်တမ်းကုန်ပြီး | 已过期 | `messages.status_expired` |
| **Claimed / Repaired** | ဝန်ဆောင်မှုရယူပြီး | 已理赔 / 已维修 | `messages.status_claimed` |
| **Product** | ကုန်ပစ္စည်း | 商品 / 产品 | `messages.product` |
| **Customer** | ဝယ်ယူသူ | 客户 | `messages.customer` |
| **Warehouse** | သိုလှောင်ရုံ | 仓库 | `messages.warehouse` |
| **Quantity** | အရေအတွက် | 数量 | `messages.quantity` |
| **Unit Cost** | ဝယ်ရင်းစျေး | 进价 / 单位成本 | `messages.unit_cost` |
| **Total Value** | စုစုပေါင်း တန်ဖိုး | 总价值 | `messages.total_value` |
| **Reference No.** | ကိုးကား နံပါတ် | 参考单号 | `messages.reference_number` |
| **Notes / Remarks** | မှတ်ချက် | 备注 | `messages.notes` |
| **Date & Time** | ရက်စွဲနှင့် အချိန် | 日期时间 | `messages.date` |
| **Actions** | ဆောင်ရွက်ချက်များ | 操作 | `messages.actions` |
| **Print / Certificate** | ပရင့်ထုတ်ရန် / လက်မှတ် | 打印 / 证书 | `messages.print` / `messages.print_certificate` |
| **View Details** | အသေးစိတ် ကြည့်ရန် | 查看详情 | `messages.view_details` |
| **Save / Submit** | သိမ်းဆည်းရန် | 保存 / 提交 | `messages.save` |
| **Cancel / Close** | ပယ်ဖျက်ရန် / ပိတ်ရန် | 取消 / 关闭 | `messages.cancel` |
| **Reset Filters** | Filter များ ပြန်ဖြုတ်ရန် | 重置筛选 | `messages.reset` |
| **Bin Card** | ပစ္စည်း ကတ် | 料卡 / 货位卡 | `messages.stock_ledger_bin_card` |
| **Stock Adjustments** | စတော့ အတိုး/အလျော့ ညှိခြင်း | 库存调整 | `messages.sidebar_stock_adjustments` |
| **Stock Count** | စတော့ ရေတွက်စစ်ဆေးခြင်း | 盘点管理 | `messages.sidebar_stock_count` |
| **Stock Ledger** | စတော့ လှုပ်ရှားမှု မှတ်တမ်း | 库存流水账 | `messages.sidebar_stock_ledger` |
| **Warranty Tracker** | အာမခံ မှတ်တမ်း | 保修跟踪 | `messages.sidebar_warranty` |

---

### Localization Coding Rules for Developers & Agents

1. **Never Hardcode Text**:
   ```blade
   {{-- ❌ INCORRECT (Hardcoded string) --}}
   <span>View Details</span>
   <p>No records found in database</p>

   {{-- ✅ CORRECT (Uses localization key with optional fallback) --}}
   <span>{{ __('messages.view_details') }}</span>
   <p>{{ __('messages.no_records_found') }}</p>
   ```

2. **Burmese Natural Language Rule**:
   - Use natural Myanmar retail terminology that cashiers and warehouse staff understand effortlessly.
   - Do NOT use robotic word-by-word machine translations.
   - Keep English technical acronyms in uppercase when standard (e.g. `SKU`, `IMEI`, `SN`, `Inv: #`, `Ks`, `Units`).

3. **Synchronized 3-File Updates**:
   - Before committing a new Blade view, search for every `__('messages.xxx')` call and verify that `xxx` is defined in:
     - `lang/en/messages.php`
     - `lang/my/messages.php`
     - `lang/zh_CN/messages.php`

---

## 12. CSP & Security Standards

1. **Never use inline `onclick` or `onchange="this.form.submit()"`**: Use `data-auto-submit` handled by `resources/js/csp-helpers.js`.
2. **Scope all actions to current store**: Always verify `$store->id` in queries and authorize requests with policies.
3. **Use CSRF tokens**: Always add `@csrf` inside forms.

---

## 13. Ready-to-Use Blade Boilerplate

When creating a new Admin Index view, copy and paste this standard skeleton:

```blade
@extends('layouts.admin.app')

@section('title', __('messages.module_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{ viewMode: localStorage.getItem('admin_view_mode') || 'table' }"
     @view-changed.window="viewMode = $event.detail">

    {{-- 1. Hero Header --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 transition">
        <div class="min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                    <span>📦</span>
                    <span>{{ __('messages.sidebar_module') }}</span>
                </span>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 truncate">{{ $store->name }}</span>
            </div>
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>{{ __('messages.module_title') }}</span>
                <span class="text-xs font-mono font-bold text-slate-400">({{ number_format($items->total()) }})</span>
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ __('messages.module_sub') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            <a href="{{ route('store.admin.module.create', ['store_slug' => $store->slug]) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-black bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-md shadow-violet-900/20 transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.create_action') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI Stat Cards (4-Up Click-to-Filter) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-2.5">
        {{-- Total Card --}}
        <a href="{{ route('store.admin.module.index', array_merge(['store_slug' => $store->slug], request()->except('status', 'page'))) }}"
           class="p-2.5 sm:p-3 rounded-lg border transition shadow-2xs flex items-center gap-2.5 sm:gap-3 {{ empty($status) || $status === 'all' ? 'border-violet-600 bg-violet-50/60 dark:border-violet-500 dark:bg-violet-950/40 ring-2 ring-violet-500/20' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900 hover:border-slate-300' }}">
            <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner">
                <span>📦</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-mono">{{ number_format($stats['total']) }}</p>
                <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">{{ __('messages.stat_total') }}</p>
            </div>
        </a>
    </div>

    {{-- 3. Standard Master Toolbar --}}
    <x-admin.toolbar
        :showSearch="true"
        :searchPlaceholder="__('messages.search') . '...'"
        :searchValue="$filters['search'] ?? ''"
        :filterCount="$activeFiltersCount ?? 0"
        :showViewToggle="true"
        :activeView="'table'"
        :showPagination="true"
        :paginator="$items"
        :showPerPageSelector="true"
    />

    {{-- 4. Spreadsheet Data Grid (Table View) --}}
    <div x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        {{-- Table Content --}}
    </div>

    {{-- 5. Responsive Card Grid (Cards View) --}}
    <div x-show="viewMode === 'card' || viewMode === 'cards'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
        {{-- Cards Content --}}
    </div>

    {{-- 6. Bottom Pagination --}}
    @if($items->hasPages())
        <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            {{ $items->links() }}
        </div>
    @endif

</div>
@endsection
```

---

## 14. Checklist for Every New Admin View

- [ ] **Full-Bleed Padding**: `@section('main_padding', 'p-2')` added.
- [ ] **View Mode State**: Outer container has `x-data="{ viewMode: localStorage.getItem('admin_view_mode') || 'table' }" @view-changed.window="viewMode = $event.detail"`.
- [ ] **Hero Header**: Compact hero header with eyebrow badge, total count, store context, and primary action button.
- [ ] **Summary Stat Cards**: 4-up click-to-filter KPI cards with active ring states and light/dark color tokens.
- [ ] **Standard `<x-admin.toolbar>`**: Wired with Search, Sort, View Toggle (`:showViewToggle="true"`), Pagination, and Per-Page options.
- [ ] **Spreadsheet Table View**: Sticky header, divide-x borders, monospace numbers, and status badges.
- [ ] **Responsive Card Grid View**: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`, Hero Metric Box, status badge with ping dot, and action footer.
- [ ] **Dark & Light Mode Support**: All text, surfaces, borders, and badges tested in both themes.
- [ ] **Bottom Pagination**: Included inside a matching surface card if `$items->hasPages()`.
- [ ] **Localization**: All text strings extracted to `__('messages.key')` and registered across `lang/en/messages.php`, `lang/my/messages.php`, and `lang/zh_CN/messages.php`.

