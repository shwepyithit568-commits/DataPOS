<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'viber_number',
        'telegram_username',
        'is_active',
        'is_primary',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_primary' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function setting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StorefrontSetting::class);
    }

    public function homeBanners(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HomeBanner::class)->orderBy('sort_order', 'asc');
    }

    public function paymentMethods(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StorePaymentMethod::class);
    }

    public function deliveryMethods(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoreDeliveryMethod::class);
    }
}
