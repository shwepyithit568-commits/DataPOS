<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontNavigationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'menu_key',
        'label_my',
        'label_en',
        'label_zh_cn',
        'icon_key',
        'destination_type',
        'destination_key',
        'storefront_page_id',
        'custom_url',
        'show_desktop',
        'show_mobile_drawer',
        'show_mobile_bottom',
        'requires_auth',
        'required_capability',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'show_desktop'       => 'boolean',
        'show_mobile_drawer' => 'boolean',
        'show_mobile_bottom' => 'boolean',
        'requires_auth'      => 'boolean',
        'is_enabled'         => 'boolean',
        'sort_order'         => 'integer',
    ];

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                            */
    /* ------------------------------------------------------------------ */

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForPlacement(Builder $query, string $placement): Builder
    {
        return match ($placement) {
            'desktop'       => $query->where('show_desktop', true),
            'mobile_drawer' => $query->where('show_mobile_drawer', true),
            'mobile_bottom' => $query->where('show_mobile_bottom', true),
            default         => $query,
        };
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                     */
    /* ------------------------------------------------------------------ */

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function storefrontPage(): BelongsTo
    {
        return $this->belongsTo(StorefrontPage::class, 'storefront_page_id');
    }

    /* ------------------------------------------------------------------ */
    /*  Localization Helper                                               */
    /* ------------------------------------------------------------------ */

    public function localizedLabel(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return match ($locale) {
            'my'    => $this->label_my ?: ($this->label_en ?: ($this->label_zh_cn ?? '')),
            'zh_CN' => $this->label_zh_cn ?: ($this->label_en ?: ($this->label_my ?? '')),
            default => $this->label_en ?: ($this->label_my ?: ($this->label_zh_cn ?? '')),
        };
    }
}
