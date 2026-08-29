{{--
  Primary navigation — 'underline' variant (clean links, underline active).
  Same data contract as nav-pill (layout-provided variables in shared scope);
  only the visual treatment differs. Capability-aware links stay identical.
--}}
<nav aria-label="Storefront primary navigation" class="sf-primary-nav flex flex-wrap items-center justify-center gap-x-7 gap-y-2 text-sm font-extrabold">
    <a href="{{ $homeUrl }}" class="relative px-1 py-2 transition {{ $isHome ? 'text-sky-600 dark:text-sky-400' : 'text-slate-700 hover:text-sky-600 dark:text-slate-200 dark:hover:text-sky-400' }}">
        {{ __('messages.home') }}
        @if ($isHome)<span class="absolute inset-x-0 -bottom-0.5 h-0.5 rounded-full bg-sky-500"></span>@endif
    </a>
    <a href="{{ $productsUrl }}" class="relative px-1 py-2 transition {{ $isProducts ? 'text-sky-600 dark:text-sky-400' : 'text-slate-700 hover:text-sky-600 dark:text-slate-200 dark:hover:text-sky-400' }}">
        {{ __('messages.products') }}
        @if ($isProducts)<span class="absolute inset-x-0 -bottom-0.5 h-0.5 rounded-full bg-sky-500"></span>@endif
    </a>
    @if (store_can('storefront.glass_finder', $activeStoreContext))
        <a href="{{ $glassFinderUrl }}" class="relative inline-flex items-center gap-1 px-1 py-2 transition {{ $isGlassFinder ? 'text-sky-600 dark:text-sky-400' : 'text-slate-700 hover:text-sky-600 dark:text-slate-200 dark:hover:text-sky-400' }}">
            <span aria-hidden="true">📱</span>
            <span>{{ __('messages.glass_finder') }}</span>
            @if ($isGlassFinder)<span class="absolute inset-x-0 -bottom-0.5 h-0.5 rounded-full bg-sky-500"></span>@endif
        </a>
    @endif
    @if (store_can('service.repair_jobs', $activeStoreContext))
        <a href="{{ $serviceTrackingUrl }}" class="relative inline-flex items-center gap-1 px-1 py-2 transition {{ $isServiceTracking ? 'text-sky-600 dark:text-sky-400' : 'text-slate-700 hover:text-sky-600 dark:text-slate-200 dark:hover:text-sky-400' }}">
            <span aria-hidden="true">🔧</span>
            <span>{{ __('messages.nav_service_track') }}</span>
            @if ($isServiceTracking)<span class="absolute inset-x-0 -bottom-0.5 h-0.5 rounded-full bg-sky-500"></span>@endif
        </a>
    @endif
    @if (store_can('storefront.online_ordering', $activeStoreContext))
        <a href="{{ $howToOrderUrl }}" class="relative inline-flex items-center gap-1 px-1 py-2 transition {{ $isHowToOrder ? 'text-sky-600 dark:text-sky-400' : 'text-slate-700 hover:text-sky-600 dark:text-slate-200 dark:hover:text-sky-400' }}">
            <span aria-hidden="true">📖</span>
            <span>{{ __('messages.how_to_order') }}</span>
            @if ($isHowToOrder)<span class="absolute inset-x-0 -bottom-0.5 h-0.5 rounded-full bg-sky-500"></span>@endif
        </a>
    @endif
    @if (store_can('storefront.blog', $activeStoreContext))
        <a href="{{ $blogUrl }}" class="relative inline-flex items-center gap-1 px-1 py-2 transition {{ $isBlog ? 'text-sky-600 dark:text-sky-400' : 'text-slate-700 hover:text-sky-600 dark:text-slate-200 dark:hover:text-sky-400' }}">
            <span aria-hidden="true">📝</span>
            <span>{{ __('messages.blog') }}</span>
            @if ($isBlog)<span class="absolute inset-x-0 -bottom-0.5 h-0.5 rounded-full bg-sky-500"></span>@endif
        </a>
    @endif
</nav>
