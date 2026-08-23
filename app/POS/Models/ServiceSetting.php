<?php

namespace App\POS\Models;

use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Service Setting / Master Data for Repair Center.
 *
 * Types:
 * - status: Repair workflow statuses (Pending, Diagnosing, In Repair, Ready, etc.)
 * - brand: Device Brands (Apple, Samsung, Xiaomi, Vivo, Oppo, Realme, etc.)
 * - category: Device Categories (Smartphone, Tablet, Laptop, Smartwatch, etc.)
 * - model: Device Models (iPhone 14, Galaxy S23, etc. Optional parent_id to Brand)
 * - color: Device Colors (Black, White, Space Gray, Gold, Sierra Blue, etc.)
 * - storage: Storage capacities (64GB, 128GB, 256GB, 512GB, 1TB, etc.)
 * - defect: Common defects / reported issues (Screen Broken, Battery Drain, No Power, etc.)
 * - accessory: Common accessories left with device (SIM Tray, Charger, Case, etc.)
 */
class ServiceSetting extends Model
{
    public const TYPES = [
        'status'    => 'Statuses',
        'brand'     => 'Brands',
        'category'  => 'Categories',
        'model'     => 'Models',
        'color'     => 'Colors',
        'storage'   => 'Storage',
        'defect'    => 'Issues / Defects',
        'accessory' => 'Accessories',
    ];

    protected $fillable = [
        'store_id',
        'type',
        'name',
        'code',
        'description',
        'is_active',
        'sort_order',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get active options for a specific store and type, ordered by sort_order and name.
     */
    public static function optionsFor(int $storeId, string $type): Collection
    {
        self::ensureDefaults($storeId);

        return self::where('store_id', $storeId)
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all settings grouped by type for a store.
     */
    public static function allGroupedFor(int $storeId): array
    {
        self::ensureDefaults($storeId);

        $all = self::where('store_id', $storeId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $grouped = [];
        foreach (array_keys(self::TYPES) as $t) {
            $grouped[$t] = $all->where('type', $t)->values();
        }

        return $grouped;
    }

    /**
     * Auto-seed sensible defaults if the store has no service settings yet.
     */
    public static function ensureDefaults(int $storeId): void
    {
        $hasAny = self::where('store_id', $storeId)->exists();
        if ($hasAny) {
            return;
        }

        $defaults = [
            'brand' => [
                ['name' => 'Apple', 'sort_order' => 1],
                ['name' => 'Samsung', 'sort_order' => 2],
                ['name' => 'Xiaomi / Redmi', 'sort_order' => 3],
                ['name' => 'Vivo', 'sort_order' => 4],
                ['name' => 'Oppo', 'sort_order' => 5],
                ['name' => 'Realme', 'sort_order' => 6],
                ['name' => 'Huawei / Honor', 'sort_order' => 7],
                ['name' => 'Google Pixel', 'sort_order' => 8],
                ['name' => 'OnePlus', 'sort_order' => 9],
                ['name' => 'Sony', 'sort_order' => 10],
                ['name' => 'Infinix / Tecno', 'sort_order' => 11],
                ['name' => 'Other Brand', 'sort_order' => 99],
            ],
            'category' => [
                ['name' => 'Smartphone (စမတ်ဖုန်း)',           'sort_order' => 1],
                ['name' => 'Tablet / iPad (တက်ဘလက်)',           'sort_order' => 2],
                ['name' => 'Laptop / Notebook (လက်ပ်တော့)',      'sort_order' => 3],
                ['name' => 'Smartwatch / Band (စမတ်နာရီ)',       'sort_order' => 4],
                ['name' => 'Keypad Phone (ခလုတ်ဖုန်း)',          'sort_order' => 5],
                ['name' => 'Desktop / PC (ဒက်စတော့ပ်)',         'sort_order' => 6],
                ['name' => 'Printer / Scanner (ပရင်တာ/စကင်နာ)', 'sort_order' => 7],
                ['name' => 'CCTV Camera (ကင်မရာ)',               'sort_order' => 8],
                ['name' => 'NVR / DVR (မှတ်တမ်းစက်)',            'sort_order' => 9],
                ['name' => 'Router / Access Point (ရောက်တာ)',   'sort_order' => 10],
                ['name' => 'Network Switch (ကွန်ရက်ဆွစ်)',       'sort_order' => 11],
                ['name' => 'UPS / Power Supply (UPS/ပါဝါ)',      'sort_order' => 12],
                ['name' => 'Other Device (အခြားစက်ပစ္စည်း)',      'sort_order' => 99],
            ],
            'color' => [
                ['name' => 'Black (အနက်)', 'sort_order' => 1],
                ['name' => 'White (အဖြူ)', 'sort_order' => 2],
                ['name' => 'Silver (ငွေရောင်)', 'sort_order' => 3],
                ['name' => 'Space Gray', 'sort_order' => 4],
                ['name' => 'Gold (ရွှေရောင်)', 'sort_order' => 5],
                ['name' => 'Rose Gold', 'sort_order' => 6],
                ['name' => 'Blue (အပြာ)', 'sort_order' => 7],
                ['name' => 'Midnight Blue', 'sort_order' => 8],
                ['name' => 'Green (အစိမ်း)', 'sort_order' => 9],
                ['name' => 'Purple (ခရမ်း)', 'sort_order' => 10],
                ['name' => 'Red (အနီ)', 'sort_order' => 11],
            ],
            'storage' => [
                ['name' => '32 GB', 'sort_order' => 1],
                ['name' => '64 GB', 'sort_order' => 2],
                ['name' => '128 GB', 'sort_order' => 3],
                ['name' => '256 GB', 'sort_order' => 4],
                ['name' => '512 GB', 'sort_order' => 5],
                ['name' => '1 TB', 'sort_order' => 6],
            ],
            'defect' => [
                ['name' => 'Screen / Touch Broken (မှန်ကွဲ/Touch မရ)', 'sort_order' => 1],
                ['name' => 'Battery Drain / Swollen (ဘက်ထရီမခံ/ဖောင်း)', 'sort_order' => 2],
                ['name' => 'No Power / Dead (ပါဝါမနိုး/အဖွင့်မရ)', 'sort_order' => 3],
                ['name' => 'Water / Liquid Damaged (ရေဝင်/အရည်ဝင်)', 'sort_order' => 4],
                ['name' => 'Charging Port / No Charge (အားမဝင်/ကြိုးမမိ)', 'sort_order' => 5],
                ['name' => 'Speaker / Mic Problem (စပီကာ/မိုက်မကောင်း)', 'sort_order' => 6],
                ['name' => 'Camera Problem (ကင်မရာမရ/ဝါး)', 'sort_order' => 7],
                ['name' => 'Network / SIM / Wi-Fi Issue (လိုင်းမမိ/ဆင်းကဒ်မသိ)', 'sort_order' => 8],
                ['name' => 'Software / Bootloop (လိုဂိုလည်/ဆော့ဖ်ဝဲလ်ကျ)', 'sort_order' => 9],
                ['name' => 'Housing / Body Damaged (ကိုယ်ထည်/ကာဗာပျက်စီး)', 'sort_order' => 10],
            ],
            'accessory' => [
                ['name' => 'SIM Card (ဆင်းမ်ကတ်)', 'sort_order' => 1],
                ['name' => 'SIM Tray (ဆင်းမ်ခွက်)', 'sort_order' => 2],
                ['name' => 'Memory / SD Card (မန်မိုရီကတ်)', 'sort_order' => 3],
                ['name' => 'Phone Case / Cover (ဖုန်းကာဗာ)', 'sort_order' => 4],
                ['name' => 'Charger / Cable (ကြိုးနှင့်ခေါင်း)', 'sort_order' => 5],
                ['name' => 'Original Box (မူရင်းဖာဘူး)', 'sort_order' => 6],
                ['name' => 'Screen Guard / Glass (စခရင်မှန်ကပ်ပါ)', 'sort_order' => 7],
            ],
            'status' => [
                ['name' => 'Pending (စောင့်ဆိုင်းဆဲ)', 'code' => 'received', 'sort_order' => 1],
                ['name' => 'Diagnosing (စစ်ဆေးနေဆဲ)', 'code' => 'diagnosing', 'sort_order' => 2],
                ['name' => 'In Repair (ပြင်ဆင်နေဆဲ)', 'code' => 'in_repair', 'sort_order' => 3],
                ['name' => 'Awaiting Parts (ပစ္စည်းစောင့်)', 'code' => 'awaiting_parts', 'sort_order' => 4],
                ['name' => 'Ready for Pickup (ပြင်ဆင်ပြီး/လာယူနိုင်)', 'code' => 'ready', 'sort_order' => 5],
                ['name' => 'Delivered (အပ်နှံပြီး)', 'code' => 'delivered', 'sort_order' => 6],
                ['name' => 'Cancelled (ပယ်ဖျက်)', 'code' => 'cancelled', 'sort_order' => 7],
                ['name' => 'Unrepairable (ပြင်မရ)', 'code' => 'unrepairable', 'sort_order' => 8],
            ],
        ];

        $now = now();
        $records = [];
        foreach ($defaults as $type => $items) {
            foreach ($items as $item) {
                $records[] = [
                    'store_id'    => $storeId,
                    'type'        => $type,
                    'name'        => $item['name'],
                    'code'        => $item['code'] ?? null,
                    'description' => null,
                    'is_active'   => true,
                    'sort_order'  => $item['sort_order'] ?? 0,
                    'parent_id'   => null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        self::insert($records);
    }
}
