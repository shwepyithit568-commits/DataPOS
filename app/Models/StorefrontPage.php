<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class StorefrontPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'title_my',
        'title_en',
        'title_zh_cn',
        'slug',
        'summary_my',
        'summary_en',
        'summary_zh_cn',
        'content_my',
        'content_en',
        'content_zh_cn',
        'featured_image_path',
        'meta_title_my',
        'meta_title_en',
        'meta_title_zh_cn',
        'meta_description_my',
        'meta_description_en',
        'meta_description_zh_cn',
        'status',
        'published_at',
        'is_enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled'   => 'boolean',
        'published_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                            */
    /* ------------------------------------------------------------------ */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', Carbon::now());
            });
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                     */
    /* ------------------------------------------------------------------ */

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function navigationItems(): HasMany
    {
        return $this->hasMany(StorefrontNavigationItem::class, 'storefront_page_id');
    }

    /* ------------------------------------------------------------------ */
    /*  Accessors / Localization Helpers                                  */
    /* ------------------------------------------------------------------ */

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function localizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return match ($locale) {
            'my'    => $this->title_my ?: ($this->title_en ?: ($this->title_zh_cn ?? '')),
            'zh_CN' => $this->title_zh_cn ?: ($this->title_en ?: ($this->title_my ?? '')),
            default => $this->title_en ?: ($this->title_my ?: ($this->title_zh_cn ?? '')),
        };
    }

    public function localizedSummary(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return match ($locale) {
            'my'    => $this->summary_my ?: ($this->summary_en ?: ($this->summary_zh_cn ?? '')),
            'zh_CN' => $this->summary_zh_cn ?: ($this->summary_en ?: ($this->summary_my ?? '')),
            default => $this->summary_en ?: ($this->summary_my ?: ($this->summary_zh_cn ?? '')),
        };
    }

    public function localizedContent(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return match ($locale) {
            'my'    => $this->content_my ?: ($this->content_en ?: ($this->content_zh_cn ?? '')),
            'zh_CN' => $this->content_zh_cn ?: ($this->content_en ?: ($this->content_my ?? '')),
            default => $this->content_en ?: ($this->content_my ?: ($this->content_zh_cn ?? '')),
        };
    }

    public function localizedMetaTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $meta = match ($locale) {
            'my'    => $this->meta_title_my ?: ($this->meta_title_en ?: ($this->meta_title_zh_cn ?? '')),
            'zh_CN' => $this->meta_title_zh_cn ?: ($this->meta_title_en ?: ($this->meta_title_my ?? '')),
            default => $this->meta_title_en ?: ($this->meta_title_my ?: ($this->meta_title_zh_cn ?? '')),
        };

        return $meta ?: $this->localizedTitle($locale);
    }

    public function localizedMetaDescription(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $meta = match ($locale) {
            'my'    => $this->meta_description_my ?: ($this->meta_description_en ?: ($this->meta_description_zh_cn ?? '')),
            'zh_CN' => $this->meta_description_zh_cn ?: ($this->meta_description_en ?: ($this->meta_description_my ?? '')),
            default => $this->meta_description_en ?: ($this->meta_description_my ?: ($this->meta_description_zh_cn ?? '')),
        };

        return $meta ?: $this->localizedSummary($locale);
    }
}
