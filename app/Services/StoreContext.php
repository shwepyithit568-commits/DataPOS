<?php

namespace App\Services;

use App\Models\Store;

class StoreContext
{
    protected ?Store $activeStore = null;

    public function setStore(?Store $store): void
    {
        $this->activeStore = $store;
    }

    public function getStore(): ?Store
    {
        return $this->activeStore;
    }

    public function getStoreId(): ?int
    {
        return $this->activeStore?->id;
    }

    public function hasActiveStore(): bool
    {
        return $this->activeStore !== null && $this->activeStore->is_active;
    }

    /**
     * Build the route parameters array used by every store-scoped route.
     *
     * Prefers the current route's `store_slug` parameter so that controllers
     * don't need to duplicate the "slug" key across every view() call.
     * Falls back to the active store's slug when we're outside a request
     * (e.g. queued jobs, Tinker).
     *
     * @return array<string, string>
     */
    public function getRouteParams(): array
    {
        $request = request();
        $slug = $request->route('store_slug');

        if ($slug) {
            return ['store_slug' => (string) $slug];
        }

        if ($this->activeStore && $this->activeStore->slug) {
            return ['store_slug' => $this->activeStore->slug];
        }

        return [];
    }
}
