{{--
  Primary navigation — 'pill' variant (default for most themes).
  Uses $desktopNavItems resolved by StorefrontNavigationResolver.
--}}
<nav aria-label="Storefront primary navigation" class="sf-primary-nav flex flex-wrap items-center justify-center gap-1 rounded-2xl border border-slate-200/80 bg-white p-1 text-sm font-extrabold shadow-sm dark:border-slate-700 dark:bg-slate-800">
    @forelse ($desktopNavItems as $navItem)
        <a href="{{ $navItem->url }}"
           @if ($navItem->is_external) target="{{ $navItem->target }}" rel="{{ $navItem->rel }}" @endif
           class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 transition {{ $navItem->is_active ? 'sf-nav-active shadow-sm' : 'sf-nav-link' }}">
            @if ($navItem->icon_key && $navItem->icon_key !== 'home')
                <x-storefront.navigation-icon :name="$navItem->icon_key" class="h-4 w-4 shrink-0" />
            @endif
            <span class="whitespace-nowrap">{{ $navItem->label }}</span>
        </a>
    @empty
        <a href="{{ $homeUrl }}" class="rounded-xl px-3 py-2 transition {{ $isHome ? 'sf-nav-active shadow-sm' : 'sf-nav-link' }}">{{ __('messages.home') }}</a>
        <a href="{{ $productsUrl }}" class="rounded-xl px-3 py-2 transition {{ $isProducts ? 'sf-nav-active shadow-sm' : 'sf-nav-link' }}">{{ __('messages.products') }}</a>
        @if (store_can('storefront.glass_finder', $activeStoreContext))
            <a href="{{ $glassFinderUrl }}" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 transition {{ $isGlassFinder ? 'sf-nav-active shadow-sm' : 'sf-nav-link' }}">
                <span aria-hidden="true">📱</span>
                <span>{{ __('messages.glass_finder') }}</span>
            </a>
        @endif
        @if (store_can('service.repair_jobs', $activeStoreContext))
            <a href="{{ $serviceTrackingUrl }}" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 transition {{ $isServiceTracking ? 'sf-nav-active shadow-sm' : 'sf-nav-link' }}">
                <span aria-hidden="true">🔧</span>
                <span>{{ __('messages.nav_service_track') }}</span>
            </a>
        @endif
        @if (store_can('storefront.online_ordering', $activeStoreContext))
            <a href="{{ $howToOrderUrl }}" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 transition {{ $isHowToOrder ? 'sf-nav-active shadow-sm' : 'sf-nav-link' }}">
                <span aria-hidden="true">📖</span>
                <span>{{ __('messages.how_to_order') }}</span>
            </a>
        @endif
        @if (store_can('storefront.blog', $activeStoreContext))
            <a href="{{ $blogUrl }}" class="inline-flex items-center gap-1 rounded-xl px-3 py-2 transition {{ $isBlog ? 'sf-nav-active shadow-sm' : 'sf-nav-link' }}">
                <span aria-hidden="true">📝</span>
                <span>{{ __('messages.blog') }}</span>
            </a>
        @endif
    @endforelse
</nav>
