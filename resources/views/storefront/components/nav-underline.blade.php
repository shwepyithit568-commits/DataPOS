{{--
  Primary navigation — 'underline' variant.
  Uses $desktopNavItems resolved by StorefrontNavigationResolver.
--}}
<nav aria-label="Storefront primary navigation" class="sf-primary-nav flex flex-wrap items-center justify-center gap-x-7 gap-1.5 sm:gap-2 text-xs sm:text-sm font-black">
    @forelse ($desktopNavItems as $navItem)
        <a href="{{ $navItem->url }}"
           @if ($navItem->is_external) target="{{ $navItem->target }}" rel="{{ $navItem->rel }}" @endif
           class="sf-btn-3d !flex-row items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-xl transition {{ $navItem->is_active ? 'active' : '' }}">
            @if ($navItem->icon_key)
                <x-storefront.navigation-icon :name="$navItem->icon_key" class="h-4 w-4 shrink-0" />
            @endif
            <span class="whitespace-nowrap font-black">{{ $navItem->label }}</span>
        </a>
    @empty
        <a href="{{ $homeUrl }}" class="sf-btn-3d !flex-row items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-xl transition {{ $isHome ? 'active' : '' }}">
            <x-storefront.navigation-icon name="home" class="h-4 w-4 shrink-0" />
            <span class="whitespace-nowrap font-black">{{ __('messages.nav_home') }}</span>
        </a>
        <a href="{{ $productsUrl }}" class="sf-btn-3d !flex-row items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-xl transition {{ $isProducts ? 'active' : '' }}">
            <x-storefront.navigation-icon name="products" class="h-4 w-4 shrink-0" />
            <span class="whitespace-nowrap font-black">{{ __('messages.nav_products') }}</span>
        </a>
        @if (store_can('storefront.glass_finder', $activeStoreContext))
            <a href="{{ $glassFinderUrl }}" class="sf-btn-3d !flex-row items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-xl transition {{ $isGlassFinder ? 'active' : '' }}">
                <span aria-hidden="true">📱</span>
                <span class="whitespace-nowrap font-black">{{ __('messages.nav_glass_finder') }}</span>
            </a>
        @endif
        @if (store_can('service.repair_jobs', $activeStoreContext))
            <a href="{{ $serviceTrackingUrl }}" class="sf-btn-3d !flex-row items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-xl transition {{ $isServiceTracking ? 'active' : '' }}">
                <span aria-hidden="true">🔧</span>
                <span class="whitespace-nowrap font-black">{{ __('messages.nav_service_track') }}</span>
            </a>
        @endif
        @if (store_can('storefront.online_ordering', $activeStoreContext))
            <a href="{{ $howToOrderUrl }}" class="sf-btn-3d !flex-row items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-xl transition {{ $isHowToOrder ? 'active' : '' }}">
                <span aria-hidden="true">📖</span>
                <span class="whitespace-nowrap font-black">{{ __('messages.nav_how_to_order') }}</span>
            </a>
        @endif
        @if (store_can('storefront.blog', $activeStoreContext))
            <a href="{{ $blogUrl }}" class="sf-btn-3d !flex-row items-center gap-1.5 px-3 sm:px-3.5 py-1.5 rounded-xl transition {{ $isBlog ? 'active' : '' }}">
                <span aria-hidden="true">📝</span>
                <span class="whitespace-nowrap font-black">{{ __('messages.nav_blog') }}</span>
            </a>
        @endif
    @endforelse
</nav>
