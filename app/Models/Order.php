<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_note',
        'admin_note',
        'contact_channel',
        'contact_identifier',
        'pricing_type',
        'total_amount',
        'agreed_amount',
        'payment_status',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'agreed_amount' => 'decimal:2',
    ];

    /**
     * Auto-generate a secure confirmation token on creation.
     *
     * The token is excluded from $fillable so it can never be set by
     * client input. It is generated server-side only.
     *
     * Collision handling: if the generated token already exists (extremely
     * unlikely with 40-char random), we retry up to 3 times, then fall
     * back to a hash-based token derived from id + time.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->confirmation_token)) {
                $order->confirmation_token = static::generateUniqueToken();
            }
        });
    }

    /**
     * Generate a cryptographically secure 40-character confirmation token.
     *
     * Retries if a collision somehow occurs (astronomically unlikely with
     * 40 random characters from a 64-character alphabet). If all retries
     * are exhausted, an exception is thrown — the database unique index
     * on confirmation_token remains the final integrity guarantee.
     *
     * Every returned token is exactly 40 characters. No fallback of a
     * different length is ever returned.
     */
    protected static function generateUniqueToken(): string
    {
        $maxAttempts = 3;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $token = Str::random(40);

            if (!static::where('confirmation_token', $token)->exists()) {
                return $token;
            }
        }

        throw new \RuntimeException(sprintf(
            'Failed to generate a unique confirmation_token after %d attempts. '
            . 'This should never happen. The database unique index will prevent collisions.',
            $maxAttempts
        ));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Final amount the order is worth: the admin-confirmed agreed_amount
     * when set, otherwise the original line-item total. Glass-finder orders
     * carry a Ks 0 total until the owner agrees a price over the phone.
     */
    public function effectiveAmount(): string
    {
        return $this->agreed_amount !== null
            ? number_format((float) $this->agreed_amount, 2, '.', '')
            : number_format((float) $this->total_amount, 2, '.', '');
    }
}
