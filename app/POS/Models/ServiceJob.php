<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Service job — a CUSTOMER-OWNED device being serviced (SoT §16).
 *
 * Covers Computer, CCTV, Network, Smartphone and other device types.
 * Status lifecycle:
 * received → diagnosing → awaiting_approval → awaiting_parts → in_repair →
 * ready → delivered, with explicit cancelled / unrepairable end states.
 *
 * Job number: SVC-YYYYMMDD-#### (auto). If a voucher_no is supplied on
 * intake, that acts as the human-readable reference; the SVC number is
 * still generated as the immutable system key.
 *
 * tracking_token: auto-generated on create. Allows customers to check status
 * at /store/{slug}/track/service/{token} without logging in.
 */
class ServiceJob extends Model
{
    public const STATUSES = [
        'received',
        'diagnosing',
        'awaiting_approval',
        'awaiting_parts',
        'in_repair',
        'ready',
        'delivered',
        'cancelled',
        'unrepairable',
    ];

    protected $fillable = [
        'store_id',
        'job_number',
        'voucher_no',
        'tracking_token',
        'customer_id',
        'contact_name',
        'contact_phone',
        'shipping_address',
        'device_type',
        'brand',
        'category',
        'model',
        'color',
        'storage',
        'imei_serial',
        'reported_problem',
        'intake_condition',
        'accessories',
        'pattern_lock',
        'device_password',
        'diagnosis',
        'technician_id',
        'status',
        'estimated_charge',
        'final_charge',
        'notes',
        'warranty_notes',
        'estimated_completion',
        'created_by',
    ];

    protected $casts = [
        'estimated_charge'     => 'decimal:2',
        'final_charge'         => 'decimal:2',
        'estimated_completion' => 'datetime',
    ];

    /**
     * Auto-generate a unique tracking_token on every new record.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $job): void {
            if (empty($job->tracking_token)) {
                do {
                    $token = Str::random(40);
                } while (static::where('tracking_token', $token)->exists());

                $job->tracking_token = $token;
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistory(): HasMany
    {
        // Order by id, not created_at — rows written in the same second would
        // otherwise tie and return in an unstable order.
        return $this->hasMany(ServiceJobStatus::class)->orderByDesc('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServiceJobPayment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceJobItem::class)->orderBy('id');
    }

    // ── Finance helpers ────────────────────────────────────────────────────

    /**
     * Sum of all line-item subtotals (parts + services) in MMK.
     */
    public function itemsTotal(): float
    {
        return (float) $this->items->sum('subtotal');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['delivered', 'cancelled', 'unrepairable'], true);
    }

    /**
     * Paid so far — sum of immutable payment rows. Uses the eager-loaded
     * relation when available so list views never trigger N+1 queries.
     */
    public function paidAmount(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    /**
     * Outstanding = final charge (or estimate while unpriced) − paid.
     */
    public function outstanding(): float
    {
        $charge = $this->final_charge !== null
            ? (float) $this->final_charge
            : (float) $this->estimated_charge;

        return max(0, $charge - $this->paidAmount());
    }

    // ── Public tracking URL ────────────────────────────────────────────────

    /**
     * Full public URL for the customer-facing status tracking page.
     * Login-free — guarded by tracking_token only.
     */
    public function trackingUrl(string $storeSlug): string
    {
        return url("/store/{$storeSlug}/track/service/{$this->tracking_token}");
    }

    // ── Number generation ──────────────────────────────────────────────────

    /**
     * Generate a unique SVC job number for the store (SVC-YYYYMMDD-####).
     * If the job carries a voucher_no, that acts as the human-readable
     * reference; the SVC number remains the immutable system key.
     */
    public static function generateNumber(int $storeId): string
    {
        $date   = now()->format('Ymd');
        $prefix = "SVC-{$date}-";

        $last = static::where('store_id', $storeId)
            ->where('job_number', 'like', "{$prefix}%")
            ->orderByDesc('job_number')
            ->value('job_number');

        $seq = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
