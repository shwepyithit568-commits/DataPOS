<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarcodeTemplate extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'type',
        'width_mm',
        'height_mm',
        'gap_x_mm',
        'gap_y_mm',
        'padding_top_mm',
        'padding_bottom_mm',
        'padding_left_mm',
        'padding_right_mm',
        'spacing_store_to_name_mm',
        'spacing_name_to_code_mm',
        'spacing_code_to_price_mm',
        'margin_top_mm',
        'margin_bottom_mm',
        'margin_left_mm',
        'margin_right_mm',
        'cols',
        'rows',
        'bar_height',
        'bar_width',
        'store_font',
        'name_font',
        'name_max_lines',
        'price_font',
        'code_type',
        'show_store_name',
        'show_product_name',
        'show_price',
        'show_code_text',
        'is_default',
    ];

    protected $casts = [
        'width_mm' => 'float',
        'height_mm' => 'float',
        'gap_x_mm' => 'float',
        'gap_y_mm' => 'float',
        'padding_top_mm' => 'float',
        'padding_bottom_mm' => 'float',
        'padding_left_mm' => 'float',
        'padding_right_mm' => 'float',
        'spacing_store_to_name_mm' => 'float',
        'spacing_name_to_code_mm' => 'float',
        'spacing_code_to_price_mm' => 'float',
        'margin_top_mm' => 'float',
        'margin_bottom_mm' => 'float',
        'margin_left_mm' => 'float',
        'margin_right_mm' => 'float',
        'cols' => 'integer',
        'rows' => 'integer',
        'bar_height' => 'integer',
        'bar_width' => 'float',
        'name_max_lines' => 'integer',
        'show_store_name' => 'boolean',
        'show_product_name' => 'boolean',
        'show_price' => 'boolean',
        'show_code_text' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
