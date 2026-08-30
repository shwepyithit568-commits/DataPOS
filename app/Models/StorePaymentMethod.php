<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'type',
        'icon_type',          // builtin | custom | initials
        'icon_value',         // builtin key or emoji
        'icon_path',          // custom uploaded icon
        'qr_path',            // custom uploaded QR code image
        'account_name',
        'account_number',
        'instructions',
        'is_active',
        'show_account_details',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_account_details' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Masked account number for Admin summaries — digits are partially hidden
     * so the full value is never shown accidentally.
     */
    public function maskedAccountNumber(): ?string
    {
        if (! $this->account_number) {
            return null;
        }

        $visible = mb_substr((string) $this->account_number, -4);

        return '•••• ' . $visible;
    }

    public function maskedAccountName(): ?string
    {
        if (! $this->account_name) {
            return null;
        }

        // Keep the first letter + last 2 — e.g. "A*****it"
        $name = (string) $this->account_name;
        if (mb_strlen($name) <= 3) {
            return '•' . mb_substr($name, -1);
        }

        return mb_substr($name, 0, 1) . str_repeat('•', max(3, mb_strlen($name) - 3)) . mb_substr($name, -2);
    }

    /**
     * Publicly accessible URL for the payment QR code image.
     */
    public function qrUrl(): ?string
    {
        if (! $this->qr_path) {
            return null;
        }

        return \App\Support\StorefrontAsset::imageUrl($this->qr_path);
    }

    public function hasQr(): bool
    {
        return ! empty($this->qr_path);
    }
}
