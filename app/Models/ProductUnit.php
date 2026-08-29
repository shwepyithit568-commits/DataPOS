<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'product_id',
        'unit_name',
        'conversion_factor',
        'retail_price',
        'wholesale_price',
        'barcode',
        'is_base_unit',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'retail_price'      => 'decimal:2',
        'wholesale_price'   => 'decimal:2',
        'is_base_unit'      => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
