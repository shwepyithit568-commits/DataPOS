<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * Fills the "How to Order" page content (intro + steps) with friendly,
 * non-technical Myanmar defaults covering all business lines
 * (mobile, accessories, CCTV, computer, network, fashion) for every active store.
 *
 * NOTE: This seeder overwrites how_to_intro / how_to_steps each time it runs,
 * so admin edits to those fields are replaced. This was intentional so the
 * shop owner gets fresh default content before launch. Video links
 * (how_to_videos) are left untouched — the shop owner adds their own
 * YouTube/TikTok tutorial links from Admin → Store Settings.
 */
class HowToOrderContentSeeder extends Seeder
{
    public function run(): void
    {
        $defaultIntro = "ဖုန်း၊ ဖုန်းအပိုပစ္စည်း၊ CCTV၊ ကွန်ပြူတာ/လက်ပ်တော့၊ Network နဲ့ Fashion ပစ္စည်းတွေကို မှာယူရတာ အရမ်းလွယ်ပါတယ်။ အောက်က အဆင့် ၄ ခုကို လိုက်လုပ်ရုံပါပဲ — ဘာ software မှ မလိုပါဘူး။";

        $defaultSteps = [
            [
                'icon' => '📱',
                'title' => 'ပစ္စည်း ရွေးပါ',
                'desc' => '"ကုန်ပစ္စည်းများ" စာမျက်နှာကို ဖွင့်ပြီး Mobile၊ Accessories၊ CCTV၊ Computer/Laptop၊ Network (Router/WiFi) နဲ့ Fashion — ဘယ်ပစ္စည်းမဆို ရွေးလို့ရပါတယ်။ Category နဲ့ Filter တွေသုံးပြီး လွယ်လွယ်ရှာနိုင်ပါတယ်။',
            ],
            [
                'icon' => '🛒',
                'title' => 'အော်ဒါ ထည့်ပါ',
                'desc' => 'ပစ္စည်းပေါ်က "အော်ဒါစာရင်းသို့ ထည့်မည်" ခလုတ်ကို နှိပ်ပါ။ ပြီးရင် "ဈေးခြင်း" ထဲမှာ သင့်နာမည်၊ ဖုန်းနံပါတ် ရိုက်ထည့်ပြီး ပို့လိုက်ရုံပါပဲ။',
            ],
            [
                'icon' => '💬',
                'title' => 'အတည်ပြုချက် စောင့်ပါ',
                'desc' => 'ကျွန်တော်တို့ ဖုန်း ဒါမှမဟုတ် Viber / Telegram ကနေ ပစ္စည်းရှိမရှိ၊ ဈေးနဲ့ ပို့ဆောင်ခကို ပြန်ဆက်သွယ် အတည်ပြုပါမယ်။ CCTV နဲ့ Network ပစ္စည်းတွေဆို တပ်ဆင်အကြံပြုချက်ပါ ပေးပါတယ်။',
            ],
            [
                'icon' => '💰',
                'title' => 'ပေးချေပြီး လက်ခံပါ',
                'desc' => 'KBZ Pay / Wave Pay / Cash နဲ့ ပေးချေနိုင်ပါတယ်။ မြန်မာနိုင်ငံ အနှံ့ ပို့ဆောင်ပေးပြီး ပစ္စည်းကို အိမ်အရောက် လက်ခံရရှိပါမယ်။',
            ],
        ];

        foreach (Store::where('is_active', true)->get() as $store) {
            $setting = $store->setting()->firstOrCreate(
                ['store_id' => $store->id],
                ['store_name' => $store->name]
            );

            $setting->how_to_intro = $defaultIntro;
            $setting->how_to_steps = $defaultSteps;
            $setting->save();

            $this->command?->info("How to Order content refreshed for: {$store->name}");
        }
    }
}
