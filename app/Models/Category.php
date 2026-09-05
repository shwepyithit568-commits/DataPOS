<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'parent_id',
        'name',
        'code',
        'slug',
        'description',
        'image_path',
        'icon',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Category name as configured in Admin Master Data.
     */
    public function getLocalizedNameAttribute(): string
    {
        return (string) $this->name;
    }

    /**
     * Standard Myanmar translations for tech categories (clean, no redundant brackets).
     */
    public static function categoryDictionaryMy(): array
    {
        return [
            // Main Categories
            'Smartphones & Tablets'      => 'စမတ်ဖုန်းနှင့် တက်ဘလက်',
            'Cable & Charger'             => 'ကြိုးနှင့် အားသွင်းခေါင်း',
            'Audio & Sound'               => 'အသံနှင့် နားကြပ်',
            'Power & Storage'             => 'ပါဝါနှင့် မမိုရီ',
            'Screen & LCD'                => 'စခရင်နှင့် မှန်',
            'Phone Case & Cover'          => 'ဖုန်းကာဗာနှင့် အိတ်',
            'Battery (ဘတ်ထရီ)'           => 'ဘက်ထရီ',
            'Body & Back Cover'           => 'ဖုန်းဘောင်နှင့် နောက်ဖုံး',
            'Phone Spare Parts'           => 'ဖုန်းအပိုပစ္စည်းများ',
            'Phone Stand & Holder'        => 'ဖုန်းတင်စင်နှင့် တွဲဆက်',
            'CCTV & Security'             => 'လုံခြုံရေးကင်မရာနှင့် CCTV',
            'Network & Connectivity'      => 'ကွန်ရက်နှင့် အင်တာနက်',
            'Electronics & Gadgets'       => 'အီလက်ထရောနစ်ပစ္စည်းများ',
            'Service & Repair'            => 'ပြုပြင်ရေးဝန်ဆောင်မှု',

            // Cable & Charger
            'Charging Cable'              => 'အားသွင်းကြိုး',
            'Charger Adapter'             => 'အားသွင်းခေါင်း',
            'Charger Set'                 => 'ခေါင်း+ကြိုးတွဲလျက်',
            'Car Charger'                 => 'ကားအားသွင်း',
            'Wireless Charger'            => 'ကြိုးမဲ့အားသွင်း',
            'Power Adapter'               => 'ပါဝါခေါင်း',

            // Audio & Sound
            'Wired Earphone'              => 'ကြိုးနားကြပ်',
            'Bluetooth Earphone'          => 'ကြိုးမဲ့နားကြပ် (TWS)',
            'Bluetooth Speaker'           => 'ဘလူးတုသ် စပီကာ',
            'Microphone'                  => 'မိုက်ခရိုဖုန်း',
            'Wired Headset / Headphone'   => 'နားကြပ်ကြီး (Headphone)',

            // Power & Storage
            'Power Bank'                  => 'ပါဝါဘဏ်',
            'Memory Card'                 => 'မမိုရီကတ်',
            'USB Flash Drive'             => 'USB Flash Drive',
            'SSD / Hard Drive'            => 'SSD / Hard Drive',
            'USB Hub / Card Reader'       => 'USB Hub / ကတ်ဖတ်စက်',

            // Screen & LCD
            'Touch LCD'                   => 'Touch LCD',
            'Original Touch LCD'          => 'မူရင်း Touch LCD',
            'Touch Screen'                => 'Touch Screen',
            'Front Glass'                 => 'မှန်ချပ်',
            'OCA Glass'                   => 'OCA မှန်',
            'Screen Protector'            => 'မှန်ကပ်',
            'Privacy Filter'              => 'Privacy မှန်ကပ်',

            // Phone Case & Cover
            'Phone Cover / Case'          => 'ဖုန်းကာဗာ / အိတ်',
            'Silicone Case'               => 'ဆေးကာဗာ',
            'Clear Case'                  => 'ဖောင်းကြည်ကာဗာ',
            'Leather Case'                => 'သားရေကာဗာ',
            'Bumper / Shockproof Case'    => 'Shockproof ကာဗာ',
            'Card Holder Case'            => 'ကတ်ထည့်ကာဗာ',
            'Sticker'                     => 'စတစ်ကာ',
            'Water Bag'                   => 'ရေစိုခံအိတ်',

            // Battery
            'Phone Battery'               => 'မူရင်း/အပို ဘက်ထရီ',
            'High Capacity Battery'       => 'High Capacity ဘက်ထရီ',
            'Standard Replacement Battery'=> 'Standard ဘက်ထရီ',

            // Body & Back Cover
            'Back Glass'                  => 'နောက်ဖုံးမှန်',
            'Back Cover'                  => 'နောက်ဖုံး',
            'Body Frame'                  => 'ဖုန်းဘောင်',
            'Mid Frame'                   => 'အလယ်ဘောင်',
            'Housing Set'                 => 'ဟောင်စင် အစုံ',

            // Phone Spare Parts
            'Charging Port'               => 'အားသွင်းပေါက်',
            'Power Switch / Flex'         => 'ပါဝါခလုတ်ကြိုး',
            'Volume Button Flex'          => 'အသံခလုတ်ကြိုး',
            'Camera Module'               => 'ကင်မရာ မော်ဂျူး',
            'Ear Speaker'                 => 'နားစပီကာ',
            'Loud Speaker'                => 'အသံချဲ့စပီကာ',
            'Vibrator Motor'              => 'တုန်ခါမော်တာ',
            'Flex Ribbon Cable'           => 'ဖလက်ကြိုး',
            'IC Chip / Board'             => 'IC ချစ်ပ်ပြား',
            'Technician Tools & Supplies' => 'ပြင်ဆင်ရေးသုံး ပစ္စည်းများ',
            'Other Spare Parts'           => 'အခြား အပိုပစ္စည်းများ',

            // Stand & Holder
            'Phone Holder / Stand'        => 'ဖုန်းတင်စင်',
            'Car Mount Holder'            => 'ကားတွဲ ဖုန်းတင်စင်',
            'Desk Stand / Tripod'         => 'စားပွဲတင်စင် / Tripod',
            'Selfie Stick'                => 'ဆယ်လ်ဖီတုတ်',
            'Ring Stand'                  => 'လက်စွပ် ဖုန်းကွင်း',

            // CCTV & Security
            'CCTV Camera'                 => 'CCTV ကင်မရာ',
            'CCTV Accessories'            => 'CCTV ဆက်စပ်ပစ္စည်း',
            'CCTV IP Camera'              => 'CCTV IP ကင်မရာ (ပြင်ပ)',
            'CCTV Analog Camera'          => 'CCTV အင်နာလော့ ကင်မရာ',
            'DVR / NVR Recorder'          => 'DVR / NVR စက်',
            'Hard Disk (CCTV/NAS)'        => 'Hard Disk (CCTV/NAS)',
            'CCTV Power Supply'           => 'CCTV ပါဝါလိုင်း',

            // Network & Connectivity
            'WiFi Router'                 => 'ဝိုင်ဖိုင် ရူတာ',
            'Network Switch'              => 'နက်ဝပ် စွတ်ခ်ျ',
            'Network Cable (UTP)'         => 'နက်ဝပ်ကြိုး (UTP)',
            'SFP Module'                  => 'SFP မော်ဂျူး',
            'Access Point (AP)'           => 'အက်ဆက်ပွိုင့် (AP)',
            'Fiber Cable'                 => 'ဖိုင်ဘာကြိုး',

            // Electronics & Gadgets
            'Mini Fan'                    => 'ပန်ကာငယ်',
            'Mouse'                       => 'မောက်စ်',
            'LED Light'                   => 'မီးသီး / မီးလိုင်း',
            'Desktop PC / All-in-One'     => 'ကွန်ပျူတာ / Desktop PC',
            'Laptop / Notebook'           => 'လက်ပ်တော့ပ် / Laptop',
            'Monitor / Display'           => 'မော်နီတာ',
            'Keyboard'                    => 'ကီးဘုတ်',
            'Webcam'                      => 'ဝဘ်ကမ် ကင်မရာ',
            'USB Cable / Adapter'         => 'USB ကြိုး / အဒပ်တာ',
            'HDMI Display Cable'          => 'HDMI ကြိုး',

            // Service & Repair
            'Screen Repair / LCD ပြင်'    => 'စခရင် / LCD ပြင်ဆင်ခြင်း',
            'Battery Replacement'         => 'ဘက်ထရီ လဲလှယ်ခြင်း',
            'Charging Port Repair'        => 'အားသွင်းပေါက် ပြင်ဆင်ခြင်း',
            'Software / Flash / Update'   => 'ဆော့ဖ်ဝဲလ် / Flash / Update',
            'Water Damage Repair'         => 'ရေဝင် ချို့ယွင်းမှု ပြင်ဆင်ခြင်း',
            'Back Glass Replacement'      => 'နောက်ဖုံးမှန် လဲလှယ်ခြင်း',
            'CCTV Installation Service'   => 'CCTV တပ်ဆင်ခြင်း ဝန်ဆောင်မှု',
            'Network Setup Service'       => 'နက်ဝပ် ချိတ်ဆက်ခြင်း ဝန်ဆောင်မှု',
            'General Diagnostic'          => 'အထွေထွေ စစ်ဆေးခြင်း',
        ];
    }

    /**
     * Standard Chinese translations for tech categories.
     */
    public static function categoryDictionaryZh(): array
    {
        return [
            'Smartphones & Tablets'      => '智能手机与平板',
            'Cable & Charger'             => '线材与充电器',
            'Audio & Sound'               => '音频与耳机',
            'Power & Storage'             => '电源与存储',
            'Screen & LCD'                => '屏幕与显示屏',
            'Phone Case & Cover'          => '手机壳与保护套',
            'Battery (ဘတ်ထရီ)'           => '电池',
            'Body & Back Cover'           => '中框与后盖',
            'Phone Spare Parts'           => '手机维修配件',
            'Phone Stand & Holder'        => '支架与底座',
            'CCTV & Security'             => '监控与安防',
            'Network & Connectivity'      => '网络设备',
            'Electronics & Gadgets'       => '数码电子配件',
            'Service & Repair'            => '维修服务',
            'Charging Cable'              => '数据充电线',
            'Charger Adapter'             => '充电头',
            'Charger Set'                 => '充电套装',
            'Car Charger'                 => '车载充电器',
            'Wireless Charger'            => '无线充电器',
            'Power Adapter'               => '电源适配器',
        ];
    }
}
