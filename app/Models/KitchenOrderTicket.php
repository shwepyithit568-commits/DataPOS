<?php

namespace App\Models;

use App\POS\Models\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenOrderTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'branch_id',
        'table_id',
        'server_user_id',
        'ticket_number',
        'order_type',
        'items',
        'status',
        'notes',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(User::class, 'server_user_id');
    }
}
