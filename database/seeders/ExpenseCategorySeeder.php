<?php

namespace Database\Seeders;

use App\Models\Store;
use App\POS\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Common expense categories for retail, phone sale & service shops in Myanmar.
     */
    public const DEFAULT_CATEGORIES = [
        [
            'name' => 'ဆိုင်ခန်းငှားခ (Shop Rent)',
            'code' => 'RENT',
            'description' => 'ဆိုင်ခန်းလခ၊ ဂိုဒေါင်နှင့် နေရာငှားရမ်းခများ',
            'color' => '#6366f1',
            'sort_order' => 1,
            'is_active' => true,
        ],
        [
            'name' => 'မီး/ရေ နှင့် မီတာခ (Electricity & Utilities)',
            'code' => 'UTIL',
            'description' => 'လျှပ်စစ်မီးခ၊ မီတာခ၊ ရေဖိုးနှင့် မီးစက်ဆီဖိုးများ',
            'color' => '#f59e0b',
            'sort_order' => 2,
            'is_active' => true,
        ],
        [
            'name' => 'ဝန်ထမ်း စားသောက်စရိတ် (Staff Meals & Tea)',
            'code' => 'MEALS',
            'description' => 'ဝန်ထမ်းနေ့လယ်စာ၊ ကော်ဖီ၊ လက်ဖက်ရည်နှင့် မုန့်ဖိုးများ',
            'color' => '#10b981',
            'sort_order' => 3,
            'is_active' => true,
        ],
        [
            'name' => 'သယ်ယူပို့ဆောင်ခ နှင့် ကားခ (Delivery & Transportation)',
            'code' => 'TRANS',
            'description' => 'ကုန်ပစ္စည်းပို့ဆောင်ခ၊ ကယ်ရီခ၊ ဂိတ်ချခ၊ ဆိုင်ကယ်/ကားဆီဖိုး',
            'color' => '#0ea5e9',
            'sort_order' => 4,
            'is_active' => true,
        ],
        [
            'name' => 'ဖုန်း နှင့် အင်တာနက်စရိတ် (Internet & Phone Bills)',
            'code' => 'COMM',
            'description' => 'Wi-Fi လိုင်းကြေး၊ ဆိုင်ဖုန်းဘေလ်၊ ဆက်သွယ်ရေးစရိတ်များ',
            'color' => '#3b82f6',
            'sort_order' => 5,
            'is_active' => true,
        ],
        [
            'name' => 'ရုံးသုံးနှင့် ဆိုင်သုံးပစ္စည်း (Stationery & Packaging)',
            'code' => 'OFFICE',
            'description' => 'ဘောက်ချာစာအုပ်၊ စက္ကူ၊ ဘောပင်၊ ထုပ်ပိုးပလတ်စတစ်အိတ်၊ တိပ်ခွေ',
            'color' => '#8b5cf6',
            'sort_order' => 6,
            'is_active' => true,
        ],
        [
            'name' => 'စက်ပြင်ကိရိယာနှင့် ဆိုင်ပြင်ဆင်စရိတ် (Tools & Maintenance)',
            'code' => 'MAINT',
            'description' => 'ဖုန်းပြင်ကိရိယာများ၊ ခဲကြိုး၊ ကော်၊ မီးသီးမီးချောင်း၊ ဆိုင်ပြင်ဆင်စရိတ်',
            'color' => '#ec4899',
            'sort_order' => 7,
            'is_active' => true,
        ],
        [
            'name' => 'ကြော်ငြာနှင့် မားကတ်တင်း (Advertising & Marketing)',
            'code' => 'MKTG',
            'description' => 'Facebook Boost ကြော်ငြာခ၊ ဗီနိုင်းဆိုင်းဘုတ်၊ ပရိုမိုးရှင်းစရိတ်',
            'color' => '#14b8a6',
            'sort_order' => 8,
            'is_active' => true,
        ],
        [
            'name' => 'အထွေထွေ အသုံးစရိတ် (General Miscellaneous)',
            'code' => 'MISC',
            'description' => 'အခြားသော မမျှော်မှန်းနိုင်သည့် အထွေထွေ ကုန်ကျစရိတ်များ',
            'color' => '#64748b',
            'sort_order' => 9,
            'is_active' => true,
        ],
    ];

    public function run(?int $storeId = null): void
    {
        $stores = $storeId ? Store::where('id', $storeId)->get() : Store::all();

        foreach ($stores as $store) {
            foreach (self::DEFAULT_CATEGORIES as $cat) {
                ExpenseCategory::firstOrCreate(
                    [
                        'store_id' => $store->id,
                        'name' => $cat['name'],
                    ],
                    [
                        'code' => $cat['code'],
                        'description' => $cat['description'],
                        'color' => $cat['color'],
                        'sort_order' => $cat['sort_order'],
                        'is_active' => $cat['is_active'],
                    ]
                );
            }
        }
    }
}
