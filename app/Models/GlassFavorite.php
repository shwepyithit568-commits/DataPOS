<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlassFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'glass_finder_item_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function glassItem(): BelongsTo
    {
        return $this->belongsTo(GlassFinderItem::class, 'glass_finder_item_id');
    }
}
