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
        <h2 class="admin-section-title">📖 {{ __('messages.settings_hto_page_title') }}</h2>
        <p class="admin-section-sub">{{ __('messages.settings_how_to_order_sub') }}</p>
    </div>

    <div>
        <label class="{{ $labelClass }}">{{ __('messages.settings_hto_intro') }}</label>
        <textarea name="how_to_intro" rows="3" class="{{ $inputClass }}" placeholder="{{ __('messages.settings_how_to_order_intro_placeholder') }}">{{ old('how_to_intro', $setting->how_to_intro) }}</textarea>
        @error('how_to_intro')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="admin-section-title">🪜 {{ __('messages.settings_ordering_steps') }} <span x-text="steps.length"></span></h3>
                <p class="{{ $helpClass }}">{{ __('messages.settings_how_to_order_steps_help') }}</p>
            </div>
            <button type="button" @click="steps.push({ icon: '📱', title: '', desc: '' })"
                class="shrink-0 rounded-xl border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                + {{ __('messages.settings_hto_add_step') }}
            </button>
        </div>
    </div>

    <template x-for="(step, i) in steps" :key="i">
        <div class="rounded-xl bg-gray-50/70 p-4 space-y-3 dark:border-slate-700 dark:bg-slate-900/50">
            <div class="flex items-center justify-between">
                <span class="rounded-full bg-violet-600 px-3 py-1 text-xs font-black text-white" x-text="@js(__('messages.settings_hto_step')) + ' ' + (i + 1)"></span>
                <button type="button" @click="steps.splice(i, 1)" x-show="steps.length > 1"
                    class="rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                    ✕ {{ __('messages.settings_remove') }}
                </button>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-[7rem_1fr]">
                <div>
                    <label class="{{ $labelClass }}">{{ __('messages.settings_icon_emoji') }}</label>
                    <input type="text" x-model="step.icon" :name="'how_to_steps[' + i + '][icon]'" maxlength="10" placeholder="📱" class="{{ $inputClass }}" />
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('messages.settings_hto_step_title') }}</label>
                    <input type="text" x-model="step.title" :name="'how_to_steps[' + i + '][title]'" placeholder="{{ __('messages.settings_how_to_step_title_example') }}" class="{{ $inputClass }}" />
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}">{{ __('messages.settings_hto_step_desc') }}</label>
                <textarea x-model="step.desc" :name="'how_to_steps[' + i + '][desc]'" rows="2" class="{{ $inputClass }}" placeholder="{{ __('messages.settings_how_to_step_desc_placeholder') }}"></textarea>
            </div>
        </div>
    </template>

    <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="admin-section-title">{{ __('messages.settings_how_to_videos_title') }}</h3>
                <p class="{{ $helpClass }}">{{ __('messages.settings_how_to_videos_help') }}</p>
            </div>
            <button type="button" @click="videos.push({ title: '', url: '' })"
                class="shrink-0 rounded-xl border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                + {{ __('messages.settings_hto_add_video') }}
            </button>
        </div>
    </div>

    <template x-for="(video, i) in videos" :key="i">
        <div class="rounded-xl bg-gray-50/70 p-4 space-y-3 dark:border-slate-700 dark:bg-slate-900/50">
            <div class="flex items-center justify-between">
                <span class="rounded-full bg-rose-600 px-3 py-1 text-xs font-black text-white" x-text="@js(__('messages.settings_hto_video')) + ' ' + (i + 1)"></span>
                <button type="button" @click="videos.splice(i, 1)" x-show="videos.length > 1"
                    class="rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                    ✕ {{ __('messages.settings_remove') }}
                </button>
            </div>
            <div>
                <label class="{{ $labelClass }}">{{ __('messages.settings_hto_video_title') }}</label>
                <input type="text" x-model="video.title" :name="'how_to_videos[' + i + '][title]'" placeholder="{{ __('messages.settings_how_to_video_title_example') }}" class="{{ $inputClass }}" />
            </div>
            <div>
                <label class="{{ $labelClass }}">{{ __('messages.settings_hto_video_url') }}</label>
                <input type="url" x-model="video.url" :name="'how_to_videos[' + i + '][url]'" placeholder="{{ __('messages.settings_how_to_video_url_placeholder') }}" class="{{ $inputClass }}" />
            </div>
        </div>
    </template>
</div>
