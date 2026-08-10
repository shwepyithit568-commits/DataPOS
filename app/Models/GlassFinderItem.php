<?php

namespace App\Models;

use App\Services\GlassCodeNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlassFinderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'brand',
        'phone_model',
        'glass_code',
        'normalized_glass_code',
        'stock_status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->normalized_glass_code = GlassCodeNormalizer::normalize($item->glass_code);
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isInStock(): bool
    {
        return $this->stock_status === 'in_stock';
    }
}
