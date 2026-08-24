<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VoucherTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'paper_size',
        'style_preset',
        'header_title',
        'header_subtitle',
        'show_logo',
        'logo_path',
        'address',
        'phone',
        'show_qr',
        'qr_type',
        'qr_image_path',
        'qr_label',
        'show_customer_info',
        'show_cashier_name',
        'show_tax_breakdown',
        'show_discount_line',
        'show_barcode',
        'footer_greeting',
        'footer_policy',
        'font_size',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'show_logo' => 'boolean',
        'show_qr' => 'boolean',
        'show_customer_info' => 'boolean',
        'show_cashier_name' => 'boolean',
        'show_tax_breakdown' => 'boolean',
        'show_discount_line' => 'boolean',
        'show_barcode' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function is80mm(): bool
    {
        return $this->paper_size === '80mm';
    }

    public function is58mm(): bool
    {
        return $this->paper_size === '58mm';
    }

    public function isA4(): bool
    {
        return $this->paper_size === 'a4';
    }

    public function isA5(): bool
    {
        return $this->paper_size === 'a5';
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_path) {
            return Storage::url($this->logo_path);
        }
        return null;
    }

    public function qrUrl(): ?string
    {
        if ($this->qr_image_path) {
            return Storage::url($this->qr_image_path);
        }
        return null;
    }
}
