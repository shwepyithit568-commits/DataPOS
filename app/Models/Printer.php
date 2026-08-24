<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Printer extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'connection_type',
        'paper_width',
        'ip_address',
        'port',
        'device_path',
        'printer_role',
        'print_copies',
        'auto_cut',
        'cash_drawer_kick',
        'beep_on_print',
        'print_logo',
        'feed_lines',
        'header_text',
        'footer_text',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'port' => 'integer',
        'print_copies' => 'integer',
        'auto_cut' => 'boolean',
        'cash_drawer_kick' => 'boolean',
        'beep_on_print' => 'boolean',
        'print_logo' => 'boolean',
        'feed_lines' => 'integer',
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
        return $this->paper_width === '80mm';
    }

    public function is58mm(): bool
    {
        return $this->paper_width === '58mm';
    }

    public function isNetwork(): bool
    {
        return $this->connection_type === 'network';
    }

    public function isBluetooth(): bool
    {
        return $this->connection_type === 'bluetooth';
    }

    public function isUsb(): bool
    {
        return $this->connection_type === 'usb';
    }
}
