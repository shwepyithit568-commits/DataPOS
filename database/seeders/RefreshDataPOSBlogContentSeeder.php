<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RefreshDataPOSBlogContentSeeder extends Seeder
{
    public function run(): void
    {
        // Safety: this seeder only refreshes content for the DataPOS store.
        // On a fresh production/test DB the store may not exist yet (the
        // production bootstrap creates it later) — skip instead of crashing.
        $store = Store::where('slug', 'datapos-mobile')->first();

        if (! $store) {
            $this->command?->warn('RefreshDataPOSBlogContentSeeder: store "datapos-mobile" not found — skipping.');

            return;
        }

        $posts = [
            7 => [
                'image_path' => 'blog/phone-buying-checklist.png',
                'title' => 'ဖုန်းဝယ်မယ်ဆို မဆုံးဖြတ်ခင် စစ်သင့်တဲ့ အချက် ၇ ချက်',
                'slug' => 'phone-buying-checklist',
                'published_at' => '2026-07-29 12:55:43',
                'category' => 'Mobile Guide',
                'excerpt' => 'ဖုန်းအသစ်ဝယ်တော့မယ်ဆို ဈေးတစ်ခုတည်းမကြည့်ပါနဲ့။ Performance, battery, camera, warranty နဲ့ resale value အထိ စစ်ပြီးမှရွေးပါ။',
                'tags' => 'mobile buying guide, smartphone, warranty, battery, camera',
                'meta_keywords' => 'ဖုန်းဝယ်နည်း, smartphone buying guide Myanmar, mobile shop, DataPOS',
                'meta_description' => 'ဖုန်းအသစ်ဝယ်မယ့်သူတွေအတွက် performance, battery, camera, warranty, storage နဲ့ budget စစ်ဆေးနည်းကို ဖတ်ရလွယ်အောင်ရှင်းပြထားသည်။',
                'content' => <<<'HTML'
<h2>ဖုန်းဝယ်တာက ဈေးပေါတာတစ်ခုတည်းနဲ့ မဆုံးဖြတ်သင့်ပါ</h2>
<p>ဖုန်းတစ်လုံးက နေ့တိုင်းသုံးရတဲ့ပစ္စည်းပါ။ ဈေးသက်သာလို့ဝယ်လိုက်ပေမယ့် battery မခံတာ၊ storage မလုံလောက်တာ၊ warranty မရှင်းတာတွေကြောင့် နောက်ပိုင်းပိုကုန်နိုင်ပါတယ်။ အောက်ကအချက်တွေကို စစ်ပြီးမှရွေးရင် ကိုယ့်ပိုက်ဆံနဲ့တန်တဲ့ဖုန်းကိုပိုမိုမှန်ကန်စွာရွေးနိုင်ပါတယ်။</p>
<h2>1. ကိုယ်သုံးမယ့်အလုပ်ကိုအရင်သတ်မှတ်ပါ</h2>
<p>Call, Facebook, Viber, TikTok လောက်ပဲသုံးမလား၊ game ဆော့မလား၊ camera ရိုက်မလား၊ business သုံးမလားဆိုတာအရင်ခွဲပါ။ Usage မတူရင် RAM, storage, chipset လိုအပ်ချက်လည်းမတူပါ။</p>
<h2>2. RAM နဲ့ Storage ကိုနည်းနည်းပိုယူပါ</h2>
<p>2026 အတွက် daily use ဆိုရင် RAM 6GB/8GB နဲ့ storage 128GB/256GB ကိုပိုအကြံပြုပါတယ်။ ဓာတ်ပုံ၊ video များသူဆို 256GB က ပိုစိတ်ချရပါတယ်။</p>
<h2>3. Battery နဲ့ charging ကိုမမေ့ပါနဲ့</h2>
<p>Battery 5000mAh ဝန်းကျင်နဲ့ fast charging support ပါရင် နေ့စဉ်သုံးရတာပိုအဆင်ပြေပါတယ်။ Charger original ပါ/မပါ၊ cable quality ကိုလည်းစစ်ပါ။</p>
<h2>4. Camera ကို megapixel တစ်ခုတည်းမကြည့်ပါနဲ့</h2>
<p>MP များတိုင်းပုံကောင်းတာမဟုတ်ပါ။ Low-light, stabilization, selfie, video quality တွေကို sample ရိုက်ကြည့်ပြီးမှဆုံးဖြတ်ပါ။</p>
<h2>5. Warranty နဲ့ after-sale service ကိုမေးပါ</h2>
<p>ဆိုင် warranty ဘယ်နှလ၊ official warranty ရှိ/မရှိ၊ software lock, region issue, replacement policy ရှိ/မရှိကိုဝယ်ခါနီးသေချာမေးပါ။</p>
<blockquote><p>ဖုန်းဝယ်တဲ့အခါ “အခုသုံးလို့ရမလား” ထက် “နောက် ၂ နှစ် သုံးလို့အဆင်ပြေမလား” ဆိုတာကိုပိုစဉ်းစားပါ။</p></blockquote>
<h2>DataPOS မှာ ဘာကူညီပေးနိုင်လဲ</h2>
<p>Mobile, accessories, screen protector, charger, cable, power bank တွေကို budget အလိုက်ရွေးပေးနိုင်ပါတယ်။ မသေချာရင် ကိုယ်သုံးမယ့်ပုံစံနဲ့ budget ကိုပြောပြီး Viber/Telegram ကနေမေးနိုင်ပါတယ်။</p>
HTML,
            ],
            8 => [
                'image_path' => 'blog/power-bank-and-cable-mistakes.png',
                'title' => 'Power Bank နဲ့ Charging Cable ဝယ်မယ်ဆို ဒီအမှား ၅ ခုကိုရှောင်ပါ',
                'slug' => 'power-bank-and-cable-mistakes',
                'published_at' => '2026-07-31 12:55:43',
                'category' => 'Accessories Guide',
                'excerpt' => 'Power bank နဲ့ cable က သေးသေးလေးလိုပေမယ့် quality မကောင်းရင် ဖုန်း battery ကိုထိခိုက်နိုင်ပါတယ်။ ဝယ်ခါနီးစစ်ရမယ့်အချက်တွေပါ။',
                'tags' => 'power bank, charging cable, accessories, fast charging, battery safety',
                'meta_keywords' => 'power bank ဝယ်နည်း, charging cable, ဖုန်းအားသွင်းကြိုး, accessories Myanmar',
                'meta_description' => 'Power bank နဲ့ charging cable ဝယ်တဲ့အခါ capacity, watt, cable type, safety protection နဲ့ warranty စစ်နည်းကိုရှင်းပြထားသည်။',
                'content' => <<<'HTML'
<h2>အားသွင်းပစ္စည်းတွေက ဖုန်းရဲ့အသက်ကိုထိန်းပေးတဲ့ပစ္စည်းပါ</h2>
<p>Power bank နဲ့ charging cable ကို “ပေါလို့ရရင်ပြီးရော” ဆိုပြီးရွေးတတ်ကြပါတယ်။ တကယ်တော့ quality မကောင်းတဲ့အားသွင်းပစ္စည်းတွေက battery health ကျစေခြင်း၊ အားသွင်းနှေးခြင်း၊ အပူတက်ခြင်းတွေဖြစ်စေနိုင်ပါတယ်။</p>
<h2>1. Capacity ကိုအမှန်တကယ်လိုအပ်သလောက်ရွေးပါ</h2>
<p>နေ့စဉ်အပြင်သွားသုံးဖို့ဆို 10,000mAh ကလုံလောက်နိုင်ပါတယ်။ ခရီးထွက်သူ၊ ဖုန်း/တက်ဘလက်နှစ်ခုသုံးသူဆို 20,000mAh ကိုစဉ်းစားပါ။ Capacity ကြီးလေ အလေးချိန်လည်းတိုးတာကိုမမေ့ပါနဲ့။</p>
<h2>2. Fast charging watt ကိုစစ်ပါ</h2>
<p>ဖုန်းက 33W support လုပ်ပေမယ့် power bank က 10W ပဲပေးနိုင်ရင် အားသွင်းနှေးနေပါလိမ့်မယ်။ USB-C PD, QC support ပါ/မပါကိုစစ်ပါ။</p>
<h2>3. Cable ကောင်းမှ fast charging အလုပ်လုပ်ပါတယ်</h2>
<p>ကြိုးမကောင်းရင် charger ကောင်းလည်းအပြည့်မရပါ။ Type-C to Type-C, Type-C to Lightning, USB-A to Type-C မျိုးကို ကိုယ့်ဖုန်း/charger နဲ့ကိုက်အောင်ရွေးပါ။</p>
<h2>4. Safety protection ပါတဲ့ brand ကိုရွေးပါ</h2>
<p>Over-charge, over-current, short-circuit protection ပါတဲ့ power bank ကိုရွေးတာပိုစိတ်ချရပါတယ်။ အပူလွန်တာ၊ body ဖောင်းတာရှိရင်ဆက်မသုံးပါနဲ့။</p>
<h2>5. Warranty နဲ့ original packaging ကိုစစ်ပါ</h2>
<p>Accessories တောင် warranty ရှိတဲ့ဆိုင်ကနေဝယ်တာပိုကောင်းပါတယ်။ အထူးသဖြင့် fast charger, cable, power bank တို့မှာ quality အာမခံကအရေးကြီးပါတယ်။</p>
<blockquote><p>ဖုန်းကိုနေ့တိုင်းအားသွင်းတာဖြစ်လို့ charging accessories ကိုလျှော့တွက်မထားပါနဲ့။</p></blockquote>
<p>DataPOS မှာ budget သက်သာတဲ့ accessories ကနေ fast charging support ပါတဲ့ quality items တွေအထိ ဖုန်းမော်ဒယ်နဲ့ကိုက်အောင်ရွေးပေးနိုင်ပါတယ်။</p>
HTML,
            ],
            9 => [
                'image_path' => 'blog/cctv-buying-guide.png',
                'title' => 'CCTV ဝယ်မယ်ဆို 2MP / 4MP / 8MP ဘယ်ဟာကိုရွေးရမလဲ',
                'slug' => 'cctv-buying-guide',
                'published_at' => '2026-08-01 12:55:43',
                'category' => 'CCTV Guide',
                'excerpt' => 'အိမ်၊ ဆိုင်၊ ဂိုဒေါင်၊ ရုံးခန်းအတွက် CCTV တပ်မယ်ဆို resolution တစ်ခုတည်းမဟုတ်ဘဲ storage, night vision, recorder နဲ့ internet ကြည့်နိုင်မှုပါစစ်ပါ။',
                'tags' => 'CCTV, security camera, 2MP, 4MP, 8MP, NVR, DVR',
                'meta_keywords' => 'CCTV ဝယ်နည်း, security camera Myanmar, 2MP 4MP 8MP CCTV, NVR DVR',
                'meta_description' => 'CCTV camera ဝယ်မယ့်သူများအတွက် 2MP, 4MP, 8MP ရွေးနည်း၊ storage, night vision, recorder နဲ့ installation အကြံပြုချက်များ။',
                'content' => <<<'HTML'
<h2>CCTV ရွေးတဲ့အခါ “ကင်မရာဘယ်နှလုံး” တစ်ခုတည်းမေးရုံနဲ့မလုံလောက်ပါ</h2>
<p>CCTV system က camera, recorder, hard disk, cable, power supply, internet viewing အားလုံးပေါင်းမှကောင်းကောင်းအလုပ်လုပ်ပါတယ်။ အရင်ဆုံးတပ်မယ့်နေရာနဲ့လိုချင်တဲ့အသေးစိတ်ကိုသတ်မှတ်ပါ။</p>
<h2>2MP, 4MP, 8MP ဘယ်လိုကွာလဲ</h2>
<p><strong>2MP</strong> က basic monitoring အတွက်လုံလောက်ပါတယ်။ ဆိုင်အတွင်းပိုင်း၊ အိမ်အဝင်ဝနီးနီးများအတွက်သင့်တော်ပါတယ်။ <strong>4MP</strong> က ပုံရိပ်ပိုရှင်းပြီး မျက်နှာ/နံပါတ်ပြားကိုပိုကောင်းကောင်းမြင်ချင်တဲ့နေရာတွေအတွက်သင့်တော်ပါတယ်။ <strong>8MP</strong> က wide area, warehouse, parking, high-detail monitoring အတွက်ပိုကောင်းပါတယ်။</p>
<h2>Night vision ကိုသေချာစစ်ပါ</h2>
<p>ညဘက်မှာပုံမရှင်းရင် CCTV ရဲ့အကျိုးကျေးဇူးတစ်ဝက်လျော့သွားပါတယ်။ Infrared range, color night vision, light condition တွေကိုတပ်မယ့်နေရာနဲ့ကိုက်အောင်ရွေးပါ။</p>
<h2>Storage ဘယ်လောက်လိုမလဲ</h2>
<p>Camera အရေအတွက်၊ resolution, recording mode, သိမ်းထားချင်တဲ့ရက်အရေအတွက်အပေါ်မူတည်ပါတယ်။ 4 cameras ကို ၇ ရက်ထက်ပိုသိမ်းချင်ရင် hard disk size ကိုကြိုတွက်ထားသင့်ပါတယ်။</p>
<h2>ဖုန်းနဲ့ကြည့်ချင်ရင် internet setup ပါလိုပါတယ်</h2>
<p>အပြင်ကနေဖုန်းနဲ့ live view ကြည့်ချင်ရင် router/internet connection, app setup, account security တွေပါမှန်ရပါမယ်။ Password ကို default မထားဖို့လည်းအရေးကြီးပါတယ်။</p>
<blockquote><p>CCTV က တပ်ပြီးရင်ပြီးတာမဟုတ်ပါ။ ကြည့်ရလွယ်၊ ပြန်ရှာရလွယ်၊ ပုံသိမ်းတာမှန်ဖို့လိုပါတယ်။</p></blockquote>
<p>DataPOS မှာ အိမ်၊ ဆိုင်၊ ဂိုဒေါင်၊ ရုံးခန်းအတွက် camera resolution, recorder, storage ကိုနေရာအလိုက်အကြံပြုပေးနိုင်ပါတယ်။</p>
HTML,
            ],
            10 => [
                'image_path' => 'blog/laptop-buying-guide.png',
                'title' => 'Laptop ဝယ်မယ်ဆို CPU, RAM, SSD ကို ဘယ်လိုရွေးမလဲ',
                'slug' => 'laptop-buying-guide',
                'published_at' => '2026-08-02 12:55:43',
                'category' => 'Computer Guide',
                'excerpt' => 'ကျောင်းသုံး၊ ရုံးသုံး၊ design, video editing, gaming အတွက် Laptop spec မတူပါ။ CPU, RAM, SSD, display နဲ့ warranty ကိုရှင်းရှင်းလင်းလင်းရွေးနည်းပါ။',
                'tags' => 'laptop buying guide, computer, RAM, SSD, CPU, student laptop, office laptop',
                'meta_keywords' => 'Laptop ဝယ်နည်း, computer shop Myanmar, RAM SSD CPU laptop guide',
                'meta_description' => 'Laptop ဝယ်မယ့်သူများအတွက် usage အလိုက် CPU, RAM, SSD, display, battery နဲ့ warranty ရွေးချယ်နည်းကိုဖော်ပြထားသည်။',
                'content' => <<<'HTML'
<h2>Laptop ဝယ်တာက ကိုယ့်အလုပ်ပေါ်မူတည်ပြီးရွေးရပါတယ်</h2>
<p>ကျောင်းစာရေးဖို့၊ office သုံးဖို့၊ Photoshop/Design လုပ်ဖို့၊ video editing လုပ်ဖို့၊ game ဆော့ဖို့လိုအပ်တဲ့ spec တွေမတူပါ။ ဈေးကြီးတိုင်းမလိုအပ်သလို ဈေးပေါတိုင်းလည်းမတန်နိုင်ပါ။</p>
<h2>Office / ကျောင်းသုံး</h2>
<p>Browser, Word, Excel, Zoom, YouTube လောက်ဆို Intel Core i3/i5 သို့မဟုတ် Ryzen 3/5, RAM 8GB, SSD 256GB/512GB ကအဆင်ပြေပါတယ်။ SSD ပါတာက HDD ထက်အများကြီးမြန်ပါတယ်။</p>
<h2>Design / Editing သုံး</h2>
<p>Photoshop, Illustrator, Canva-heavy work, video editing သုံးမယ်ဆို RAM 16GB, SSD 512GB, display color ကောင်းတာ၊ dedicated GPU လို/မလိုကိုစဉ်းစားပါ။</p>
<h2>Gaming သုံး</h2>
<p>Gaming laptop မှာ CPU ထက် GPU ကအရေးကြီးလာပါတယ်။ Cooling ကောင်းမကောင်း၊ adapter watt, display refresh rate, upgrade slot ရှိ/မရှိကိုပါစစ်ပါ။</p>
<h2>Battery နဲ့ weight</h2>
<p>နေ့တိုင်းသယ်ရမယ်ဆို 14-inch lightweight laptop ကပိုအဆင်ပြေပါတယ်။ အိမ်/ရုံးမှာထားသုံးမယ်ဆို 15.6-inch screen ကြီးတာကအလုပ်လုပ်ရတာပိုကောင်းနိုင်ပါတယ်။</p>
<h2>Warranty, keyboard, ports</h2>
<p>Keyboard အဆင်ပြေမပြေ၊ USB-C, HDMI, LAN, card reader လိုအပ်မလား၊ warranty ဘယ်လိုရမလဲကိုဝယ်ခါနီးစစ်ပါ။</p>
<blockquote><p>Laptop ကို spec စာရွက်နဲ့ပဲမဆုံးဖြတ်ပါနဲ့။ ကိုယ်သုံးမယ့် software နဲ့ကိုက်တာကအရေးကြီးဆုံးပါ။</p></blockquote>
<p>DataPOS မှာ student, office, design, gaming, business သုံး laptop/computer accessories တွေကို budget အလိုက်ရွေးပေးနိုင်ပါတယ်။</p>
HTML,
            ],
            11 => [
                'image_path' => 'blog/how-to-choose-a-wifi-router.png',
                'title' => 'WiFi နှေးနေလား? Router ရွေးတဲ့အခါစစ်ရမယ့် အချက်များ',
                'slug' => 'how-to-choose-a-wifi-router',
                'published_at' => '2026-08-03 12:55:43',
                'category' => 'Network Guide',
                'excerpt' => 'Internet ကြေးပေးထားပေမယ့် WiFi နှေးနေရင် router, coverage, band, placement, device limit တွေကြောင့်ဖြစ်နိုင်ပါတယ်။',
                'tags' => 'wifi router, network, dual band, mesh wifi, internet speed',
                'meta_keywords' => 'WiFi router ဝယ်နည်း, router Myanmar, dual band router, mesh wifi',
                'meta_description' => 'WiFi နှေးခြင်းကိုဖြေရှင်းရန် router ရွေးနည်း၊ dual-band, coverage, placement, mesh WiFi အကြံပြုချက်များ။',
                'content' => <<<'HTML'
<h2>Internet speed ကောင်းပေမယ့် WiFi နှေးတာ Router ကြောင့်ဖြစ်နိုင်ပါတယ်</h2>
<p>တစ်ခါတစ်လေ ISP speed မဟုတ်ဘဲ router မကိုက်တာ၊ တပ်ထားတဲ့နေရာမမှန်တာ၊ device များလွန်းတာကြောင့် WiFi နှေးတတ်ပါတယ်။ Router ဝယ်ခါနီး အောက်ကအချက်တွေကိုစစ်ပါ။</p>
<h2>Single band နဲ့ Dual band ကွာခြားချက်</h2>
<p>2.4GHz ကအကွာအဝေးပိုရောက်ပေမယ့် speed နည်းနိုင်ပါတယ်။ 5GHz က speed ပိုကောင်းပေမယ့် နံရံများရင်အကွာအဝေးလျော့နိုင်ပါတယ်။ ဖုန်း/လက်ပ်တော့များတဲ့အိမ်၊ ရုံးငယ်တွေမှာ dual-band router ကိုပိုအကြံပြုပါတယ်။</p>
<h2>အိမ်အကျယ်နဲ့နံရံအရေအတွက်ကိုတွက်ပါ</h2>
<p>တစ်ထပ်အိမ်ငယ်ဆို router တစ်လုံးလုံလောက်နိုင်ပေမယ့် နှစ်ထပ်အိမ်၊ အခန်းများ၊ concrete wall များရင် mesh WiFi သို့မဟုတ် access point ထပ်လိုနိုင်ပါတယ်။</p>
<h2>Device ဘယ်နှခုချိတ်မလဲ</h2>
<p>ဖုန်း ၅ လုံး၊ laptop ၂ လုံး၊ CCTV, smart TV, printer စသည်ဖြင့် device များရင် router performance ပိုလိုပါတယ်။ Cheap router တစ်လုံးတည်းနဲ့ device များလွန်းရင် lag ဖြစ်နိုင်ပါတယ်။</p>
<h2>Router placement</h2>
<p>Router ကို မြင့်တဲ့နေရာ၊ အလယ်ပိုင်း၊ အဖုံးမပိတ်တဲ့နေရာမှာထားပါ။ သံပုံး၊ TV နောက်၊ concrete corner, microwave အနီးတွေကိုရှောင်ပါ။</p>
<blockquote><p>WiFi ကောင်းချင်ရင် router model ရွေးတာနဲ့ placement နှစ်ခုလုံးမှန်ရပါမယ်။</p></blockquote>
<p>DataPOS မှာ home WiFi, office network, CCTV network setup အတွက် router/accessories ကိုလိုအပ်ချက်အလိုက်ရွေးပေးနိုင်ပါတယ်။</p>
HTML,
            ],
            12 => [
                'image_path' => 'blog/online-clothing-size-guide.png',
                'title' => 'Online Fashion ဝယ်တဲ့အခါ Size မမှားအောင်ရွေးနည်း',
                'slug' => 'online-clothing-size-guide',
                'published_at' => '2026-08-04 12:55:00',
                'category' => 'Fashion Guide',
                'excerpt' => 'Online မှာအဝတ်အစားဝယ်တဲ့အခါ S/M/L တစ်ခုတည်းမကြည့်ပါနဲ့။ ကိုယ်တိုင်းတာနည်း၊ fabric, fit type နဲ့ return policy ကိုစစ်ပါ။',
                'tags' => 'fashion, online shopping, size guide, clothing, accessories',
                'meta_keywords' => 'online fashion size guide, အဝတ်အစား size ရွေးနည်း, Myanmar fashion shop',
                'meta_description' => 'Online fashion ဝယ်တဲ့အခါ size မမှားစေရန် body measurement, fabric, fit type, size chart နဲ့ return policy စစ်နည်း။',
                'content' => <<<'HTML'
<h2>Online ကနေ Fashion ဝယ်ရင် Size chart ကိုသေချာဖတ်ပါ</h2>
<p>အဝတ်အစား size မတော်တာက online shopping မှာအများဆုံးကြုံရတဲ့ပြဿနာပါ။ Brand တစ်ခုနဲ့တစ်ခု S/M/L မတူနိုင်လို့ size label တစ်ခုတည်းနဲ့မဆုံးဖြတ်သင့်ပါ။</p>
<h2>ကိုယ်တိုင်းတာကိုအရင်ယူပါ</h2>
<p>Chest, waist, hip, shoulder, length တို့ကို measuring tape နဲ့တိုင်းထားပါ။ ကိုယ့်တိုင်းတာနဲ့ size chart ကိုနှိုင်းယှဉ်ပြီးမှရွေးပါ။ မသေချာရင် ပိုကြီးတဲ့ size ကိုစဉ်းစားတာပိုလုံခြုံပါတယ်။</p>
<h2>Fit type ကိုကြည့်ပါ</h2>
<p>Regular fit, slim fit, oversized fit ဆိုတာတစ်ခုနဲ့တစ်ခုဝတ်ရတဲ့ feeling မတူပါ။ ကိုယ်ကြိုက်တဲ့ style နဲ့ကိုက်မကိုက်ကို product description ထဲမှာစစ်ပါ။</p>
<h2>Fabric အမျိုးအစားက size feel ကိုပြောင်းစေပါတယ်</h2>
<p>Stretch ပါတဲ့အထည်က body နဲ့လိုက်လျောညီထွေဖြစ်နိုင်ပေမယ့် cotton/denim စတဲ့အထည်တွေက stretch နည်းနိုင်ပါတယ်။ Wash ပြီးနောက် shrink ဖြစ်နိုင်တဲ့အထည်မျိုးလည်းရှိပါတယ်။</p>
<h2>Review/real photo ရှိရင်ကြည့်ပါ</h2>
<p>Customer photo, height/weight reference, review comment တွေက size မှန်အောင်ရွေးရာမှာအထောက်အကူဖြစ်ပါတယ်။</p>
<h2>Return / exchange policy ကိုမေးပါ</h2>
<p>Size မတော်ရင်လဲလို့ရမလား၊ ဘယ်အခြေအနေမှာလက်ခံမလဲ၊ delivery fee ဘယ်သူတာဝန်ယူမလဲကိုဝယ်ခါနီးမေးထားပါ။</p>
<blockquote><p>Fashion item ဝယ်တဲ့အခါ “လှမလား” အပြင် “ကိုယ်နဲ့အံဝင်မလား” ဆိုတာကိုပါစစ်ပါ။</p></blockquote>
<p>DataPOS မှာ Fashion item တွေအတွက် size, color, style ကိုမေးမြန်းပြီးမှမှာယူနိုင်ပါတယ်။ မသေချာရင် Viber/Telegram ကနေတိုင်းတာကိုပို့ပြီးအကြံပြုချက်တောင်းနိုင်ပါတယ်။</p>
HTML,
            ],
        ];

        foreach ($posts as $id => $data) {
            $assetPath = database_path('seeders/assets/' . $data['image_path']);
            if (File::exists($assetPath)) {
                Storage::disk('public')->put($data['image_path'], File::get($assetPath));
            }

            Post::updateOrCreate(
                ['store_id' => $store->id, 'slug' => $data['slug']],
                [
                    ...$data,
                    'store_id' => $store->id,
                    'is_published' => true,
                ]
            );
        }
    }
}
