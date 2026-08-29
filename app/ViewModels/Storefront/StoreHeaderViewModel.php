<?php

namespace App\ViewModels\Storefront;

use App\Models\Store;
use App\Models\StorefrontSetting;

class StoreHeaderViewModel
{
    public function __construct(
        public readonly ?Store $store,
        public readonly ?StorefrontSetting $setting
    ) {}

    public function storeName(): string
    {
        return $this->setting?->store_name ?: ($this->store?->name ?? config('app.name', 'DataPOS'));
    }

    public function tagline(): string
    {
        return $this->setting?->tagline ?: __('messages.default_tagline');
    }

    public function logoUrl(): ?string
    {
        $logo = $this->setting?->storefrontLogo();
        return $logo ? asset('storage/' . $logo) : null;
    }

    public function phone(): ?string
    {
        return $this->setting?->phone;
    }

    public function viberUrl(): ?string
    {
        return $this->setting?->viberUrl();
    }

    public function telegramUrl(): ?string
    {
        return $this->setting?->telegramUrl();
    }

    public function facebookUrl(): ?string
    {
        return $this->setting?->facebook_url;
    }

    public function tiktokUrl(): ?string
    {
        return $this->setting?->tiktok_url;
    }

    public function can(string $capability): bool
    {
        if (! $this->store) {
            return false;
        }

        return $this->store->hasCapability($capability);
    }

    public function isPosOnly(): bool
    {
        return $this->store?->isPosOnly() ?? false;
    }
}
