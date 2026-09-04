<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StorefrontNavigationItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class StorefrontNavigationResolver
{
    public function __construct(
        protected StorefrontNavigationDefaultsService $defaultsService
    ) {}

    /**
     * Resolve navigation items for a specific placement on the storefront.
     *
     * @param Store $store
     * @param string $placement 'desktop' | 'mobile_drawer' | 'mobile_bottom'
     * @param User|null $user
     * @return Collection
     */
    public function resolveForPlacement(Store $store, string $placement, ?User $user = null): Collection
    {
        $user = $user ?? Auth::user();

        // 1. Fetch store items with related page
        $items = StorefrontNavigationItem::where('store_id', $store->id)
            ->where('is_enabled', true)
            ->forPlacement($placement)
            ->with(['storefrontPage' => function ($q) {
                $q->select([
                    'id',
                    'store_id',
                    'slug',
                    'title_my',
                    'title_en',
                    'title_zh_cn',
                    'status',
                    'published_at',
                    'is_enabled',
                ]);
            }])
            ->ordered()
            ->get();

        // If no records in database, provide lazy auto-seeding or fallback collection
        if ($items->isEmpty() && !StorefrontNavigationItem::where('store_id', $store->id)->exists()) {
            $this->defaultsService->seedDefaultsForStore($store, false);

            $items = StorefrontNavigationItem::where('store_id', $store->id)
                ->where('is_enabled', true)
                ->forPlacement($placement)
                ->with('storefrontPage')
                ->ordered()
                ->get();
        }

        // 2. Filter items according to authentication and page publication status (Admin-controlled freedom)
        $resolved = collect();

        foreach ($items as $item) {
            // Check capability if specified on the item or system destination
            if (!empty($item->required_capability) && !store_can($item->required_capability, $store)) {
                continue;
            }

            // Check authentication requirement if explicitly required
            if ($item->requires_auth && !$user) {
                continue;
            }

            // System destination specific checks (e.g. login/register hidden when already authenticated)
            if ($item->destination_type === 'system') {
                $sysConfig = StorefrontNavigationRegistry::SYSTEM_DESTINATIONS[$item->destination_key] ?? null;
                if ($sysConfig) {
                    if (!empty($sysConfig['required_capability']) && !store_can($sysConfig['required_capability'], $store)) {
                        continue;
                    }
                    if (($sysConfig['guest_only'] ?? false) && $user) {
                        continue;
                    }
                }
            }

            // Custom Page destination check
            if ($item->destination_type === 'page') {
                $page = $item->storefrontPage;
                if (!$page || !$page->is_enabled || !$page->isPublished() || $page->store_id !== $store->id) {
                    continue;
                }
            }

            // Resolve URL & Active State
            $urlData = $this->resolveItemUrl($item, $store);
            $isActive = $this->determineIsActive($item, $urlData['url']);

            $resolved->push((object) [
                'id'                 => $item->id,
                'menu_key'           => $item->menu_key,
                'label'              => $item->localizedLabel(),
                'icon_key'           => $item->icon_key ?: 'home',
                'destination_type'   => $item->destination_type,
                'destination_key'    => $item->destination_key,
                'url'                => $urlData['url'],
                'is_external'        => $urlData['is_external'],
                'target'             => $urlData['target'],
                'rel'                => $urlData['rel'],
                'is_active'          => $isActive,
                'requires_auth'      => $item->requires_auth,
                'required_capability'=> $item->required_capability,
                'model'              => $item,
            ]);
        }

        return $resolved;
    }

    /**
     * Resolve the URL, target and rel for a navigation item.
     */
    protected function resolveItemUrl(StorefrontNavigationItem $item, Store $store): array
    {
        if ($item->destination_type === 'system' && !empty($item->destination_key)) {
            return [
                'url'         => StorefrontNavigationRegistry::resolveSystemUrl($item->destination_key, $store),
                'is_external' => false,
                'target'      => '_self',
                'rel'         => null,
            ];
        }

        if ($item->destination_type === 'page' && $item->storefrontPage) {
            $pageUrl = url('/store/' . $store->slug . '/page/' . $item->storefrontPage->slug);
            return [
                'url'         => $pageUrl,
                'is_external' => false,
                'target'      => '_self',
                'rel'         => null,
            ];
        }

        if ($item->destination_type === 'custom_url' && !empty($item->custom_url)) {
            $rawUrl = trim($item->custom_url);
            $isExternal = preg_match('#^(https?://)#i', $rawUrl) === 1;

            return [
                'url'         => $rawUrl,
                'is_external' => $isExternal,
                'target'      => $isExternal ? '_blank' : '_self',
                'rel'         => $isExternal ? 'noopener noreferrer' : null,
            ];
        }

        return [
            'url'         => url('/?store_slug=' . $store->slug),
            'is_external' => false,
            'target'      => '_self',
            'rel'         => null,
        ];
    }

    /**
     * Determine if an item is the currently active menu item.
     */
    protected function determineIsActive(StorefrontNavigationItem $item, string $resolvedUrl): bool
    {
        $currentUrl = Request::url();
        $currentFullUrl = Request::fullUrl();

        if ($item->destination_type === 'system') {
            $sys = StorefrontNavigationRegistry::SYSTEM_DESTINATIONS[$item->destination_key] ?? null;
            if ($sys) {
                if ($item->destination_key === 'home') {
                    return Request::is('/') || Request::is('store/*') && !Request::is('store/*/product/*') && !Request::is('store/*/track/*') && !Request::is('store/*/orders/*') && !Request::is('store/*/page/*');
                }

                foreach ($sys['active_patterns'] as $pattern) {
                    if (Request::is($pattern)) {
                        return true;
                    }
                }
            }
        }

        if ($item->destination_type === 'page' && $item->storefrontPage) {
            return Request::is('store/*/page/' . $item->storefrontPage->slug);
        }

        if ($item->destination_type === 'custom_url') {
            return $currentFullUrl === $resolvedUrl || $currentUrl === $resolvedUrl;
        }

        return false;
    }
}
