<?php

namespace App\ViewModels\Storefront;

use Illuminate\Support\Collection;

class CategoryFilterViewModel
{
    /**
     * @param Collection<int, mixed> $categories
     * @param Collection<int, mixed> $brands
     * @param array<string, mixed> $activeFilters
     */
    public function __construct(
        public readonly Collection $categories,
        public readonly Collection $brands,
        public readonly array $activeFilters = [],
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null
    ) {}

    public function hasActiveFilters(): bool
    {
        return !empty(array_filter($this->activeFilters));
    }

    public function selectedCategoryId(): ?int
    {
        return isset($this->activeFilters['category_id']) ? (int) $this->activeFilters['category_id'] : null;
    }

    public function selectedBrandId(): ?int
    {
        return isset($this->activeFilters['brand_id']) ? (int) $this->activeFilters['brand_id'] : null;
    }

    public function selectedSort(): string
    {
        return (string) ($this->activeFilters['sort'] ?? 'latest');
    }
}
