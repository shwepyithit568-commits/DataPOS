<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Seeds the storefront blog with Myanmar articles covering each business line
 * (mobile, accessories, CCTV, computer, network, fashion).
 *
 * Destructive by design: deletes existing posts for the store first, then
 * recreates them — so the blog always reflects the current business lines.
 * Run with: php artisan db:seed --class=BlogSeeder
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();
        if (! $store) {
            return;
        }

        Post::where('store_id', $store->id)->delete();

        $posts = [
            [
                'title'        => 'ဖုန်းဝယ်တဲ့အခါ စစ်ဆေးသင့်တဲ့ အချက် ၇ ချက်',
                'slug'         => 'phone-buying-checklist',
                'excerpt'      => 'ဖုန်းအသစ် ဝယ်တော့မယ်ဆိုရင် ဈေးတင်မကြည့်ဘဲ ဒီအချက် ၇ ချက်ကို သေချာစစ်ဆေးပါ — နောက်နောင်မှာ ခေါင်းကိုက်စရာ ရှောင်နိုင်ပါတယ်။',
                'content'      => "ဖုန်းဝယ်တာက ကြီးမားတဲ့ ရင်းနှီးမြှုပ်နှံမှုတစ်ခုပါ။ ဈေးသက်သာရုံနဲ့ မရွေးဘဲ ဒီအချက် ၇ ချက်ကို စစ်ပြီးမှ ဝယ်ပါ။\n\n၁။ IMEI စစ်ပါ — ဖုန်းထဲမှာ *#06# နှိပ်ပြီး ပေါ်လာတဲ့ IMEI နံပါတ်နဲ့ သေတ္တာပေါ်က နံပါတ် တူညီမှုရှိမရှိ ကြည့်ပါ။\n\n၂။ Official warranty ရှိလား — ဆိုင်တင် အာမခံသာ မဟုတ်ဘဲ brand ရဲ့ တရားဝင် အာမခံပါတဲ့ ဖုန်းကို ရွေးပါ။\n\n၃။ Battery health — ဘက်ထရီ အသစ်လား၊ လဲထားလား မေးပါ။ ဖုန်းအသုံးပြုပြီး ဝယ်ရင် health % ကို သေချာကြည့်ပါ။\n\n၄။ Storage နဲ့ RAM — ကိုယ့်အသုံးပြုပုံနဲ့ ကိုက်တဲ့ 256GB / 12GB လို spec ကို ရွေးပါ။\n\n၅။ ပါဝင်ပစ္စည်းတွေ — charger၊ cable၊ box အကုန်ပါလား စစ်ပါ။\n\n၆။ Network support — Myanmar မှာ သုံးတဲ့ 4G/5G band တွေနဲ့ ကိုက်ညီလား မေးပါ။\n\n၇။ ၇ ရက် exchange — ဖွင့်ပြီး ပြဿနာရှိရင် ပြန်လဲလို့ရတဲ့ ဆိုင်ကို ရွေးပါ။\n\nကျွန်တော်တို့ DataPOS မှာ အထက်ပါ အချက်တွေ အားလုံးကို အာမခံပေးပါတယ် — Viber ကနေ စုံစမ်းနိုင်ပါတယ်။",
                'image_path'   => null,
                'published_at' => now()->subDays(6),
            ],
            [
                'title'        => 'Power Bank နဲ့ အားသွင်းကြိုး ဝယ်တဲ့အခါ မှားတတ်တဲ့ အမှား ၅ ခု',
                'slug'         => 'power-bank-and-cable-mistakes',
                'excerpt'      => 'ဈေးပေါတဲ့ power bank နဲ့ ကြိုးလေးတွေက ဖုန်းဘက်ထရီကို ဘယ်လို ပျက်စီးစေနိုင်လဲ — ရှောင်ရမယ့် အမှား ၅ ခုပါ။',
                'content'      => "Power bank နဲ့ အားသွင်းကြိုးက နေ့စဉ်သုံးပစ္စည်းမို့ ဈေးပေါတာပဲ ရွေးမိတတ်ပါတယ်။ ဒါပေမဲ့ ဒီအမှား ၅ ခုက သင့်ဖုန်းရဲ့ ဘက်ထရီကို တိတ်တဆိတ် ပျက်စီးစေနိုင်ပါတယ်။\n\n၁။ ဈေးအရမ်းပေါတဲ့ power bank — နာမည်မသိ brand ရဲ့ အလွန်ပေါတဲ့ဟာတွေက လိုအပ်တဲ့ output power မပေးနိုင်ဘဲ ဖုန်းကို ဖြည်းဖြည်း အားသွင်းရုံသာမက ပူလောင်မှုလည်း ဖြစ်စေနိုင်ပါတယ်။\n\n၂။ mAh ကြီးရုံနဲ့ ရွေးတာ — mAh က ဘက်ထရီပမာဏ၊ output watt (W) က အားသွင်းမြန်နှုန်း။ နှစ်ခုလုံး ကြည့်ပါ။\n\n၃။ Fast charging ကိုက်ညီမှု မစစ်တာ — သင့်ဖုန်းရဲ့ fast charging စနစ် (PD / QC) နဲ့ ကိုက်ညီတဲ့ဟာ ရွေးပါ။\n\n၄။ ကြိုးကို watt မကြည့်ဘဲ ဝယ်တာ — 100W ကြိုးနဲ့ 10W ကြိုး ဈေးကွာတာ အကြောင်းရှိပါတယ်။ မြန်မြန်အားသွင်းချင်ရင် watt မြင့်တာကို ရွေးပါ။\n\n၅။ အာမခံ မရှိတဲ့ဆိုင်ကနေ ဝယ်တာ — power bank လိုမျိုးဟာက အနည်းဆုံး ၆ လ အာမခံရှိတဲ့ဆိုင်ကနေ ဝယ်ပါ။\n\nDataPOS မှာ Anker၊ Baseus၊ UGREEN လို နာမည်ကြီး brand တွေကို အာမခံနဲ့ ရောင်းချပေးပါတယ်။",
                'image_path'   => null,
                'published_at' => now()->subDays(4),
            ],
            [
                'title'        => 'CCTV ဝယ်တဲ့အခါ 2MP / 4MP / 8MP ဘယ်ဟာ ရွေးရမလဲ',
                'slug'         => 'cctv-buying-guide',
                'excerpt'      => 'အိမ်၊ ဆိုင်၊ ဂိုဒေါင် ဘယ်နေရာမှာမဆို CCTV ဝယ်တော့မယ်ဆိုရင် resolution၊ recorder နဲ့ storage ကို ဘယ်လို ဆုံးဖြတ်ရမလဲ။',
                'content'      => "CCTV ဝယ်တာက ကင်မရာတစ်လုံးတည်း ဝယ်တာမဟုတ်ဘဲ စနစ်တစ်ခုလုံး ဝယ်တာပါ။ ဒါတွေကို ကြိုသိထားရင် အမှားနည်းပါတယ်။\n\n၁။ Resolution (MP) — 2MP က ဈေးသက်သာပြီး နေ့ခင်းဘက် ရှင်းတယ်။ 4MP က ညဘက်အရောင် (color night vision) နဲ့ အသေးစိတ်ကောင်းတယ်။ 8MP (4K) က ဈေးကြီးပြီး ကားနံပါတ်ပြား ဖတ်ချင်တာလို နေရာမျိုးအတွက်။\n\n၂။ Recorder — DVR က analog ကင်မရာနဲ့၊ NVR က IP/ကြိုးမဲ့ ကင်မရာနဲ့ တွဲသုံးတယ်။ ကင်မရာရွေးသလို recorder ကိုလည်း ကိုက်ညီအောင် ရွေးရပါတယ်။\n\n၃။ Storage — 4 ကင်မရာ 2MP ဆိုရင် 1TB က တစ်လလောက် မှတ်တမ်းထားနိုင်တယ်။ ကင်မရာများလေ၊ resolution မြင့်လေ storage ပိုလိုပါတယ်။\n\n၄။ Night vision — ညဘက် မှောင်တဲ့နေရာဆိုရင် color night vision ပါတဲ့ဟာ ရွေးပါ။\n\n၅။ ကြိုး ဒါမှမဟုတ် ကြိုးမဲ့ — ကြိုးမဲ့ (Wi-Fi) က တပ်ဆင်လွယ်ပေမယ့် WiFi ကောင်းဖို့လိုတယ်။ အရေးကြီးနေရာဆို ကြိုးပါတဲ့ စနစ် ပိုတည်ငြိမ်ပါတယ်။\n\nDataPOS မှာ Hikvision၊ Dahua၊ TP-Link Tapo kit တွေကို တပ်ဆင်လမ်းညွှန်နဲ့ ရောင်းချပေးပါတယ် — Viber ကနေ မေးပါ။",
                'image_path'   => null,
                'published_at' => now()->subDays(3),
            ],
            [
                'title'        => 'လက်ပ်တော့ ဝယ်တဲ့အခါ ဘယ် Spec တွေ ကြည့်ရမလဲ',
                'slug'         => 'laptop-buying-guide',
                'excerpt'      => 'ကျောင်း၊ ရုံး၊ design၊ gaming — ကိုယ့်အသုံးပြုပုံပေါ်မူတည်ပြီး လက်ပ်တော့ spec ရွေးနည်း အပြည့်အစုံ။',
                'content'      => "လက်ပ်တော့ ဈေးကွက်ကြီးတာနဲ့အမျှ ရွေးရတာ ရှုပ်တတ်ပါတယ်။ ကိုယ့်အလုပ်အမျိုးအစားကို အရင်သတ်မှတ်ပြီး အောက်က spec တွေနဲ့ ယှဉ်ကြည့်ပါ။\n\n၁။ Processor (CPU) — ရုံးသုံး/ကျောင်းသုံးဆို Core i5 (သို့) Ryzen 5 လုံလောက်ပါတယ်။ Video edit/design ဆိုရင် i7/Ryzen 7 အထက် ရွေးပါ။\n\n၂။ RAM — ယနေ့ခေတ်မှာ 8GB က အနည်းဆုံး၊ 16GB က အကြံပြုထားပါတယ်။ Browser tabs အများကြီး ဖွင့်တတ်ရင် 16GB ပိုအဆင်ပြေပါတယ်။\n\n၃။ Storage — SSD ဖြစ်ရပါမယ်။ 256GB က ရုံးသုံးအတွက် လုံလောက်ပြီး 512GB က ပိုစိတ်ချရပါတယ်။\n\n၄။ Screen — နေ့စဉ်သုံးရင် FHD (1920x1080) ဖြစ်ရပါမယ်။ Design ဆို OLED ဒါမှမဟုတ် ရောင်စုံတိကျတဲ့ panel ရွေးပါ။\n\n၅။ Battery — ကျောင်းသွားသယ်ရတဲ့သူဆို 8 နာရီအထက် ခံတဲ့ဟာ ရွေးပါ။\n\n၆။ Weight — ခရီးသွားလေ့ရှိရင် 1.5kg အောက် ပေါ့ပါးတဲ့ဟာ ရွေးပါ။\n\nအမှားများတဲ့အချက်က ဈေးကြီးတဲ့ spec ကို မလိုဘဲ ဝယ်မိတာပါ — ကိုယ့်အသုံးပြုပုံကို ပြောပြရင် DataPOS က လိုအပ်တဲ့ဟာကိုပဲ အကြံပြုပေးပါတယ်။",
                'image_path'   => null,
                'published_at' => now()->subDays(2),
            ],
            [
                'title'        => 'WiFi မြန်မြန်ရဖို့ Router ဘယ်လို ရွေးမလဲ',
                'slug'         => 'how-to-choose-a-wifi-router',
                'excerpt'      => 'Internet ကြေးဆောင်ထားပေမယ့် WiFi နှေးနေတာလား။ သင့်အိမ်/ရုံးနဲ့ ကိုက်တဲ့ router ရွေးနည်း အဆင့်ဆင့်။',
                'content'      => "Internet လိုင်းကောင်းပေမယ့် WiFi နှေးတယ်ဆိုရင် အပြစ်က router မှာ ဖြစ်တတ်ပါတယ်။ ဒီလိုရွေးကြည့်ပါ။\n\n၁။ AC လား AX လား — AX (WiFi 6) က အသစ်၊ မြန်ပြီး ပစ္စည်းအများကြီး တစ်ပြိုင်နက်ချိတ်ရင် ပိုတည်ငြိမ်တယ်။ အခုခေတ် ဖုန်းတွေက WiFi 6 ကို ထောက်ပံ့နေပြီမို့ AX ကို ရွေးတာ ရေရှည်စိတ်ချရတယ်။\n\n၂။ Speed နာမည် (AC1200, AX3000, AX5400...) — ဂဏန်းကြီးလေ မြန်လေ မဟုတ်ဘဲ ကိုယ့် internet လိုင်း speed နဲ့ ကိုက်ရင် ရပါတယ်။ ဥပမာ 50Mbps လိုင်းဆို AX1500 လောက်နဲ့ လုံလောက်ပါတယ်။\n\n၃။ Coverage — အိမ်ကြီး/ထပ်ပေါင်းအိမ်ဆိုရင် router တစ်လုံးတည်းနဲ့ မလုံလောက်တတ်ဘူး။ Mesh system (သို့) WiFi extender ထပ်ထည့်ပါ။\n\n၄။ Ports — desktop/ကြိုးသုံးချင်ရင် Gigabit port ပါတဲ့ဟာ ရွေးပါ။\n\n၅။ Band — dual-band (2.4GHz + 5GHz) ဖြစ်ရပါမယ်။ 2.4GHz က အကွာအဝေးဝေး၊ 5GHz က မြန်။\n\nဆိုင်မှာ တပ်ဆင်အသုံးပြုတာ ဒါမှမဟုတ် လူအများသုံးတယ်ဆိုရင် MikroTik လို business-grade ရွေးသင့်ပါတယ် — DataPOS မှာ TP-Link၊ Tenda၊ Mercusys၊ MikroTik အကုန်ရှိပါတယ်။",
                'image_path'   => null,
                'published_at' => now()->subDay(),
            ],
            [
                'title'        => 'အွန်လိုင်းကနေ အဝတ်အစား ဝယ်တဲ့အခါ Size ရွေးနည်း',
                'slug'         => 'online-clothing-size-guide',
                'excerpt'      => 'အွန်လိုင်းဝယ်ရင် size လွဲတတ်တဲ့ ပြဿနာ — ကိုယ့်အရွယ်အစား မှန်မှန်ရွေးနိုင်ဖို့ လွယ်ကူတဲ့ နည်းလမ်းတွေ။',
                'content'      => "အွန်လိုင်းကနေ အဝတ်အစား ဝယ်တုန်း size လွဲတာက အဖြစ်များဆုံး ပြဿနာပါ။ ဒီနည်းတွေသုံးရင် လွဲနိုင်ခြေ နည်းသွားပါလိမ့်မယ်။\n\n၁။ Measurement ယူပါ — ကိုယ့် chest (ရင်ဘတ်)၊ waist (ခါး)၊ hip (တင်ပါး)၊ shoulder (ပခုံး) ကို ကြိုးနဲ့တိုင်းပြီး မှတ်ထားပါ။\n\n၂။ Size chart ကြည့်ပါ — ဆိုင်တိုင်းမှာ size chart ရှိပါတယ်။ S/M/L နာမည်တွေက brand အလိုက် ကွာတတ်လို့ chart ကိုပဲ ယုံပါ။\n\n၃။ ချုပ်နည်းကြည့်ပါ — slim fit လား regular fit လား ဆိုတာ ဖတ်ပါ။ ကိုယ့်ခန္ဓာကိုယ်ပုံစံနဲ့ ကိုက်တာရွေးပါ။\n\n၄။ Fabric ကြည့်ပါ — 100% cotton လား၊ blend လား။ လျှော်ရင် ကျုံ့နိုင်လား ဆိုတာ ဖတ်ပါ။\n\n၅။ Exchange policy ရှိတဲ့ဆိုင် — size လွဲရင် ၃ ရက်အတွင်း လဲလို့ရတဲ့ဆိုင်ကနေ ဝယ်ပါ။\n\nDataPOS မှာ အဝတ်အစားတိုင်းမှာ size chart ပါပြီး မဖွင့်ရသေးဘဲ tag ပါရင် ၃ ရက်အတွင်း size လဲလှယ်ပေးပါတယ်။",
                'image_path'   => null,
                'published_at' => now(),
            ],
        ];

        foreach ($posts as $post) {
            Post::create(
                array_merge($post, ['store_id' => $store->id, 'is_published' => true])
            );
        }
    }
}
