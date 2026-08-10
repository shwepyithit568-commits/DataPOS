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
}
