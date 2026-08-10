{{-- Sticky section header for search-suggestion dropdown sections
     (Trending / Categories / Brands / Products). Replaces 8 duplicated
     class strings in the storefront layout. Overlay surface — backdrop-blur
     is allowed here per the Solid-Glass design rules. --}}
<div class="sticky top-0 z-10 border-b border-slate-100 bg-slate-50/95 px-3 py-1.5 text-[11px] font-black uppercase tracking-wide text-slate-500 backdrop-blur dark:border-slate-700/60 dark:bg-slate-800/95 dark:text-slate-400" aria-hidden="true">
    {{ $slot }}
</div>
