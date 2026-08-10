@php
    // Repeater data — 4 empty step rows by default so the form shows the
    // structure; the controller drops fully-blank rows on save.
    $howToSteps = old('how_to_steps', $setting->how_to_steps ?? []);
    if (empty($howToSteps)) {
        $howToSteps = [
            ['icon' => '📱', 'title' => '', 'desc' => ''],
            ['icon' => '🛒', 'title' => '', 'desc' => ''],
            ['icon' => '💬', 'title' => '', 'desc' => ''],
            ['icon' => '💰', 'title' => '', 'desc' => ''],
        ];
    }
    $howToVideos = old('how_to_videos', $setting->how_to_videos ?? []);
@endphp

<div class="p-4 sm:p-6 space-y-5"
    x-data="{
        steps: {{ json_encode($howToSteps, JSON_UNESCAPED_UNICODE) }},
        videos: {{ json_encode($howToVideos, JSON_UNESCAPED_UNICODE) }}
    }">
    <div>
        <h2 class="admin-section-title">📖 How to Order Page Content</h2>
        <p class="admin-section-sub">Storefront ရဲ့ "မှာယူနည်း" စာမျက်နှာမှာ ပြမည့် မိတ်ဆက်စာသား၊ အဆင့်တွေနဲ့ အသုံးပြုနည်း ဗီဒီယိုလင့်များ — ဖောက်သည်တွေ လွယ်လွယ်နဲ့ ဝယ်တတ်အောင် ရိုးရိုးရှင်းရှင်း ရေးပေးပါ။</p>
    </div>

    <div>
        <label class="{{ $labelClass }}">Intro Text (ခေါင်းစဉ်အောက်က မိတ်ဆက်စာကြောင်း)</label>
        <textarea name="how_to_intro" rows="3" class="{{ $inputClass }}" placeholder="ဥပမာ — ဖုန်း၊ ဖုန်းမှန်နဲ့ အပိုပစ္စည်းတွေ မှာယူရတာ အရမ်းလွယ်ပါတယ်။ အောက်က အဆင့်တွေကို လိုက်လုပ်ရုံပါပဲ…">{{ old('how_to_intro', $setting->how_to_intro) }}</textarea>
        @error('how_to_intro')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="admin-section-title">🪜 {{ __('messages.settings_ordering_steps') }} <span x-text="steps.length"></span></h3>
                <p class="{{ $helpClass }}">စာမျက်နှာပေါ်မှာ အဆင့်တစ်ခုချင်းစီအတွက် icon (emoji) + ခေါင်းစဉ် + ရှင်းလင်းချက်။ လုံးဝအလွတ်ဖြစ်နေတဲ့ အဆင့်တွေကို save လုပ်တဲ့အခါ ဖျက်ပစ်ပါမယ်။</p>
            </div>
            <button type="button" @click="steps.push({ icon: '📱', title: '', desc: '' })"
                class="shrink-0 rounded-xl border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                + Add Step
            </button>
        </div>
    </div>

    <template x-for="(step, i) in steps" :key="i">
        <div class="rounded-xl bg-gray-50/70 p-4 space-y-3 dark:border-slate-700 dark:bg-slate-900/50">
            <div class="flex items-center justify-between">
                <span class="rounded-full bg-violet-600 px-3 py-1 text-xs font-black text-white" x-text="'Step ' + (i + 1)"></span>
                <button type="button" @click="steps.splice(i, 1)" x-show="steps.length > 1"
                    class="rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                    ✕ Remove
                </button>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-[7rem_1fr]">
                <div>
                    <label class="{{ $labelClass }}">Icon (emoji)</label>
                    <input type="text" x-model="step.icon" :name="'how_to_steps[' + i + '][icon]'" maxlength="10" placeholder="📱" class="{{ $inputClass }}" />
                </div>
                <div>
                    <label class="{{ $labelClass }}">Title</label>
                    <input type="text" x-model="step.title" :name="'how_to_steps[' + i + '][title]'" placeholder="ဥပမာ — ပစ္စည်းရွေးပါ" class="{{ $inputClass }}" />
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Description</label>
                <textarea x-model="step.desc" :name="'how_to_steps[' + i + '][desc]'" rows="2" class="{{ $inputClass }}" placeholder="ဒီအဆင့်မှာ ဖောက်သည်က ဘာလုပ်ရမလဲ ရိုးရိုးရှင်းရှင်း ရေးပါ"></textarea>
            </div>
        </div>
    </template>

    <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="admin-section-title">🎬 အသုံးပြုနည်း ဗီဒီယိုလင့်များ (YouTube / TikTok)</h3>
                <p class="{{ $helpClass }}">YouTube link ထည့်ရင် စာမျက်နှာပေါ်မှာ video အကြည့်ရပါမယ်။ TikTok link ဆိုရင် "Watch on TikTok" ခလုတ်အဖြစ် ပြပါမယ်။ YouTube channel / TikTok profile link က ☎️ Contact section ရဲ့ Social Media ထဲမှာ ထည့်ပါ။</p>
            </div>
            <button type="button" @click="videos.push({ title: '', url: '' })"
                class="shrink-0 rounded-xl border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                + Add Video
            </button>
        </div>
    </div>

    <template x-for="(video, i) in videos" :key="i">
        <div class="rounded-xl bg-gray-50/70 p-4 space-y-3 dark:border-slate-700 dark:bg-slate-900/50">
            <div class="flex items-center justify-between">
                <span class="rounded-full bg-rose-600 px-3 py-1 text-xs font-black text-white" x-text="'Video ' + (i + 1)"></span>
                <button type="button" @click="videos.splice(i, 1)" x-show="videos.length > 1"
                    class="rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                    ✕ Remove
                </button>
            </div>
            <div>
                <label class="{{ $labelClass }}">Video Title (ထည့်မထားရင်လည်းရ)</label>
                <input type="text" x-model="video.title" :name="'how_to_videos[' + i + '][title]'" placeholder="ဥပမာ — ဖုန်းမှန် ဘယ်လိုမှာယူမလဲ" class="{{ $inputClass }}" />
            </div>
            <div>
                <label class="{{ $labelClass }}">Video URL (YouTube / TikTok link)</label>
                <input type="url" x-model="video.url" :name="'how_to_videos[' + i + '][url]'" placeholder="https://youtube.com/watch?v=… သို့ https://tiktok.com/@…/video/…" class="{{ $inputClass }}" />
            </div>
        </div>
    </template>
</div>
