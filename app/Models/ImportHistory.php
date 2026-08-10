<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'type',
        'filename',
        'total_rows',
        'success_rows',
        'failed_rows',
        'error_file_path',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'success_rows' => 'integer',
        'failed_rows' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayType(): string
    {
        return match ($this->type) {
            'products' => 'Product',
            'glass_finder' => 'Glass Finder',
            default => str($this->type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function status(): string
    {
        if ($this->failed_rows > 0 && $this->success_rows > 0) {
            return 'Completed with errors';
        }

        if ($this->failed_rows > 0 && $this->success_rows === 0) {
            return 'Failed';
        }

        return 'Completed';
    }
}
