<?php

namespace App\Services;

use App\Models\Store;

class StorefrontNavigationRegistry
{
    /**
     * Central definition of all allowable system destination keys.
     */
    public const SYSTEM_DESTINATIONS = [
        'home' => [
            'key'                 => 'home',
            'label_key'           => 'messages.home',
            'default_icon'        => 'home',
            'required_capability' => null,
            'requires_auth'       => false,
            'guest_only'          => false,
            'default_desktop'     => true,
            'default_drawer'      => true,
            'default_bottom'      => true,
            'default_order'       => 10,
            'active_patterns'     => ['/', ''],
        ],
        'products' => [
            'key'                 => 'products',
            'label_key'           => 'messages.products',
            'default_icon'        => 'products',
            'required_capability' => 'storefront.ecommerce',
            'requires_auth'       => false,
            'guest_only'          => false,
            'default_desktop'     => true,
            'default_drawer'      => true,
            'default_bottom'      => true,
            'default_order'       => 20,
            'active_patterns'     => ['products*', 'store/*/product/*'],
        ],
        'categories' => [
            'key'                 => 'categories',
            'label_key'           => 'messages.categories',
            'default_icon'        => 'categories',
            'required_capability' => 'storefront.ecommerce',
            'requires_auth'       => false,
            'guest_only'          => false,
            'default_desktop'     => false,
            'default_drawer'      => true,
            'default_bottom'      => true,
            'default_order'       => 30,
            'active_patterns'     => ['browse*'],
        ],
        'glass_finder' => [
            'key'                 => 'glass_finder',
            'label_key'           => 'messages.glass_finder',
            'default_icon'        => 'glass',
            'required_capability' => 'storefront.glass_finder',
            'requires_auth'       => false,
            'guest_only'          => false,
            'default_desktop'     => true,
            'default_drawer'      => true,
            'default_bottom'      => false,
            'default_order'       => 40,
            'active_patterns'     => ['glass-finder*'],
        ],
        'service_tracking' => [
            'key'                 => 'service_tracking',
            'label_key'           => 'messages.nav_service_track',
            'default_icon'        => 'repair',
            'required_capability' => 'service.repair_jobs',
            'requires_auth'       => false,
            'guest_only'          => false,
            'default_desktop'     => true,
            'default_drawer'      => true,
            'default_bottom'      => true,
            'default_order'       => 50,
            'active_patterns'     => ['service-tracking*', 'store/*/track/service*'],
        ],
        'how_to_order' => [
            'key'                 => 'how_to_order',
            'label_key'           => 'messages.how_to_order',
            'default_icon'        => 'book',
            'required_capability' => 'storefront.online_ordering',
            'requires_auth'       => false,
            'guest_only'          => false,
            'default_desktop'     => true,
            'default_drawer'      => true,
            'default_bottom'      => false,
            'default_order'       => 60,
            'active_patterns'     => ['how-to-order*'],
        ],
        'blog' => [
            'key'                 => 'blog',
            'label_key'           => 'messages.blog',
            'default_icon'        => 'blog',
            'required_capability' => 'storefront.blog',
            'requires_auth'       => false,
            'guest_only'          => false,
            'default_desktop'     => true,
            'default_drawer'      => true,
            'default_bottom'      => false,
            'default_order'       => 70,
            'active_patterns'     => ['blog*'],
        ],
        'cart' => [
            'key'                 => 'cart',
            'label_key'           => 'messages.nav_cart',
            'default_icon'        => 'cart',
            'required_capability' => 'storefront.online_ordering',
            'requires_auth'       => false,
            'guest_only'          => false,
            'default_desktop'     => false,
            'default_drawer'      => false,
            'default_bottom'      => true,
            'default_order'       => 80,
            'active_patterns'     => ['order-builder*'],
        ],
        'account' => [
            'key'                 => 'account',
            'label_key'           => 'messages.nav_account',
            'default_icon'        => 'account',
            'required_capability' => 'storefront.customer_portal',
            'requires_auth'       => true,
            'guest_only'          => false,
            'default_desktop'     => false,
            'default_drawer'      => true,
            'default_bottom'      => true,
            'default_order'       => 90,
            'active_patterns'     => ['account*'],
        ],
        'login' => [
            'key'                 => 'login',
            'label_key'           => 'messages.login',
            'default_icon'        => 'login',
            'required_capability' => null,
            'requires_auth'       => false,
            'guest_only'          => true,
            'default_desktop'     => false,
            'default_drawer'      => true,
            'default_bottom'      => true,
            'default_order'       => 100,
            'active_patterns'     => ['login*'],
        ],
        'register' => [
            'key'                 => 'register',
            'label_key'           => 'messages.register',
            'default_icon'        => 'register',
            'required_capability' => null,
            'requires_auth'       => false,
            'guest_only'          => true,
            'default_desktop'     => false,
            'default_drawer'      => true,
            'default_bottom'      => false,
            'default_order'       => 110,
            'active_patterns'     => ['register*'],
        ],
    ];

    /**
     * Allowlisted icon keys.
     */
    public const ICON_KEYS = [
        'home',
        'products',
        'categories',
        'glass',
        'repair',
        'book',
        'blog',
        'cart',
        'account',
        'login',
        'register',
        'phone',
        'location',
        'gift',
        'info',
        'shield',
        'star',
        'sparkles',
        'chat',
        'heart',
        'document',
    ];

    public static function getSystemDestinations(): array
    {
        return self::SYSTEM_DESTINATIONS;
    }

    public static function getIconKeys(): array
    {
        return self::ICON_KEYS;
    }

    public static function isValidSystemDestination(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::SYSTEM_DESTINATIONS);
    }

    public static function isValidIconKey(?string $key): bool
    {
        return $key !== null && in_array($key, self::ICON_KEYS, true);
    }

    /**
     * Resolve the route URL for a system destination given the store.
     */
    public static function resolveSystemUrl(string $destinationKey, Store $store): string
    {
        $slug = $store->slug;

        return match ($destinationKey) {
            'home'             => $slug ? url('/?store_slug=' . $slug) : url('/'),
            'products'         => $slug ? url('/products?store_slug=' . $slug) : url('/products'),
            'categories'       => $slug ? url('/browse?store_slug=' . $slug) : url('/browse'),
            'glass_finder'     => $slug ? url('/glass-finder?store_slug=' . $slug) : url('/glass-finder'),
            'service_tracking' => $slug ? url('/service-tracking?store_slug=' . $slug) : url('/service-tracking'),
            'how_to_order'     => $slug ? url('/how-to-order?store_slug=' . $slug) : url('/how-to-order'),
            'blog'             => $slug ? url('/blog?store_slug=' . $slug) : url('/blog'),
            'cart'             => $slug ? url('/order-builder?store_slug=' . $slug) : url('/order-builder'),
            'account'          => $slug ? url('/account?store_slug=' . $slug) : url('/account'),
            'login'            => route('login'),
            'register'         => route('register'),
            default            => $slug ? url('/?store_slug=' . $slug) : url('/'),
        };
    }
}
