<div class="p-4 sm:p-6 space-y-6">
    <div class="admin-section-head">
        <div>
            <h2 class="admin-section-title">☎️ {{ __('messages.settings_contact_social') }}</h2>
            <p class="admin-section-sub">{{ __('messages.settings_contact_sub') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}">{{ __('messages.settings_main_phone') }}</label>
            <input type="text" name="phone" value="{{ old('phone', $setting->phone) }}" placeholder="09xxxxxxxxx" class="{{ $inputClass }}" />
            @error('phone')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">{{ __('messages.settings_viber_number') }}</label>
            <input type="text" name="viber_number" value="{{ old('viber_number', $setting->viber_number) }}" placeholder="09xxxxxxxxx" class="{{ $inputClass }}" />
            @error('viber_number')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $labelClass }}">{{ __('messages.settings_telegram_username') }}</label>
            <input type="text" name="telegram_username" value="{{ old('telegram_username', $setting->telegram_username) }}" placeholder="@username" class="{{ $inputClass }}" />
            @error('telegram_username')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2">
            <label class="{{ $labelClass }}">{{ __('messages.settings_shop_address') }}</label>
            <textarea name="address" rows="4" class="{{ $inputClass }}" placeholder="{{ __('messages.settings_shop_address_placeholder') }}">{{ old('address', $setting->address) }}</textarea>
            @error('address')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
                <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">{{ __('messages.settings_floating_chat_button') }}</h3>
                <p class="{{ $helpClass }}">{{ __('messages.settings_floating_chat_sub') }}</p>
            </div>
        </div>
        <div>
            <label class="{{ $labelClass }}">{{ __('messages.settings_floating_button_label') }}</label>
            <input type="text" name="chat_button_label" maxlength="50" value="{{ old('chat_button_label', $setting->chat_button_label) }}" placeholder="Chat with us" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">{{ __('messages.settings_floating_chat_label_help') }}</p>
            @error('chat_button_label')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        @php
            $floatIconPreview = \App\Support\StorefrontAsset::imageUrl($setting->chat_button_icon_path);
        @endphp
        <div x-data="{ iconPreview: {{ json_encode($floatIconPreview) }} }">
            <label class="{{ $labelClass }}">{{ __('messages.settings_floating_button_icon') }}</label>
            <select name="chat_button_icon" class="{{ $inputClass }}">
                <option value="">{{ __('messages.settings_icon_auto') }}</option>
                <option value="✈️" {{ old('chat_button_icon', $setting->chat_button_icon) === '✈️' ? 'selected' : '' }}>✈️ Telegram</option>
                <option value="💬" {{ old('chat_button_icon', $setting->chat_button_icon) === '💬' ? 'selected' : '' }}>💬 Chat</option>
                <option value="📞" {{ old('chat_button_icon', $setting->chat_button_icon) === '📞' ? 'selected' : '' }}>📞 Call</option>
                <option value="📱" {{ old('chat_button_icon', $setting->chat_button_icon) === '📱' ? 'selected' : '' }}>📱 Message</option>
            </select>
            <div class="mt-2 flex items-center gap-2">
                <img x-show="iconPreview" :src="iconPreview" alt=""
                     class="h-10 w-10 shrink-0 rounded-xl bg-white object-cover dark:border-slate-600">
                <input type="file" name="chat_button_icon_image" accept="image/png,image/jpeg,image/webp"
                       @change="iconPreview = URL.createObjectURL($event.target.files[0])"
                       class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:text-slate-400 dark:file:bg-violet-950/40 dark:file:text-violet-300" />
                <button type="button" @click="iconPreview = ''"
                    class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                    title="Remove custom icon image">
                    ✕
                </button>
                <input type="hidden" name="chat_button_icon_remove" :value="iconPreview ? '' : '1'" />
            </div>
            <p class="{{ $helpClass }}">{{ __('messages.settings_chat_channel_brand_icon_help') }}</p>
            @error('chat_button_icon')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            @error('chat_button_icon_image')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        @php
            $chatChannels = old('chat_channels', $setting->chat_channels ?? []);
        @endphp
        <div class="md:col-span-2">
            <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
                <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">📱 {{ __('messages.settings_channel') }}s (floating chat popup)</h3>
                <p class="{{ $helpClass }}">{{ __('messages.settings_chat_channels_popup_help') }}</p>
            </div>
        </div>
        <div class="md:col-span-2 space-y-3" x-data="{ channels: {{ json_encode($chatChannels, JSON_UNESCAPED_UNICODE) }} }">
            <button type="button" @click="channels.push({ icon: '', icon_path: '', label: '', href: '' })"
                class="shrink-0 rounded-xl border border-fuchsia-300 bg-fuchsia-50 px-3 py-2 text-xs font-bold text-fuchsia-700 hover:bg-fuchsia-100 dark:border-fuchsia-700 dark:bg-fuchsia-950/40 dark:text-fuchsia-300">
                {{ __('messages.settings_add_channel') }}
            </button>
            <template x-for="(ch, i) in channels" :key="i">
                <div class="rounded-xl bg-gray-50/70 p-4 space-y-3 dark:border-slate-700 dark:bg-slate-900/50">
                    <div class="flex items-center justify-between">
                        <span class="rounded-full bg-fuchsia-600 px-3 py-1 text-xs font-black text-white" x-text="@js(__('messages.settings_channel')) + ' ' + (i + 1)"></span>
                        <button type="button" @click="channels.splice(i, 1)"
                            class="rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                            ✕ {{ __('messages.settings_remove') }}
                        </button>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">{{ __('messages.settings_icon_emoji') }}</label>
                            <input type="text" x-model="ch.icon" :name="'chat_channels[' + i + '][icon]'" maxlength="10" placeholder="💬 / ✈️ / 📘 …" class="{{ $inputClass }}" />
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">{{ __('messages.settings_icon_image') }}</label>
                            <div class="flex items-center gap-2">
                                <img x-show="ch.icon_path || ch._preview"
                                     :src="ch._preview || (ch.icon_path ? (ch.icon_path.startsWith('assets/') ? '/' + ch.icon_path : '/storage/' + ch.icon_path) : '')"
                                     alt=""
                                     class="h-10 w-10 shrink-0 rounded-xl bg-white object-cover dark:border-slate-600">
                                <input type="file" :name="'chat_channels[' + i + '][image]'" accept="image/png,image/jpeg,image/webp"
                                       @change="ch._preview = URL.createObjectURL($event.target.files[0])"
                                       class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-fuchsia-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-fuchsia-700 hover:file:bg-fuchsia-100 dark:text-slate-400 dark:file:bg-fuchsia-950/40 dark:file:text-fuchsia-300" />
                                <input type="hidden" :name="'chat_channels[' + i + '][icon_path]'" :value="ch.icon_path" />
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">{{ __('messages.settings_label') }}</label>
                            <input type="text" x-model="ch.label" :name="'chat_channels[' + i + '][label]'" maxlength="50" placeholder="Viber / Telegram / Facebook …" class="{{ $inputClass }}" />
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">{{ __('messages.settings_link') }}</label>
                            <input type="text" x-model="ch.href" :name="'chat_channels[' + i + '][href]'" placeholder="https:// , viber:// , tel: , tg://" class="{{ $inputClass }}" />
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="md:col-span-2">
            <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
                <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">📲 Social Media (Facebook / YouTube / TikTok)</h3>
                <p class="{{ $helpClass }}">{{ __('messages.settings_social_media_help') }}</p>
            </div>
        </div>
        <div class="md:col-span-2">
            <label class="{{ $labelClass }}"><x-brand-icon brand="facebook" class="h-4 w-4 inline-block -mt-0.5 text-blue-600"/> Facebook Page URL</label>
            <input type="text" name="facebook_url" value="{{ old('facebook_url', $setting->facebook_url) }}" placeholder="https://facebook.com/yourpage" class="{{ $inputClass }}" />
            @error('facebook_url')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2">
            <label class="{{ $labelClass }}"><x-brand-icon brand="youtube" class="h-4 w-4 inline-block -mt-0.5 text-red-600"/> YouTube Channel URL</label>
            <input type="text" name="youtube_url" value="{{ old('youtube_url', $setting->youtube_url) }}" placeholder="https://youtube.com/@yourchannel" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">{{ __('messages.settings_youtube_channel_help') }}</p>
            @error('youtube_url')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2">
            <label class="{{ $labelClass }}"><x-brand-icon brand="tiktok" class="h-4 w-4 inline-block -mt-0.5"/> TikTok Profile URL</label>
            <input type="text" name="tiktok_url" value="{{ old('tiktok_url', $setting->tiktok_url) }}" placeholder="https://tiktok.com/@youraccount" class="{{ $inputClass }}" />
            @error('tiktok_url')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
                <h3 class="text-sm font-black text-gray-900 dark:text-slate-100">📍 Exact Store Location (Google Maps)</h3>
                <p class="{{ $helpClass }}">{{ __('messages.settings_google_maps_location_help') }}</p>
            </div>
        </div>

        <div class="md:col-span-2">
            <label for="map_enabled" class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="map_enabled" name="map_enabled" value="1" class="h-5 w-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500" {{ old('map_enabled', $setting->map_enabled) ? 'checked' : '' }} />
                <span class="{{ $labelClass }}">{{ __('messages.settings_show_store_location') }}</span>
            </label>
            <p class="{{ $helpClass }}">{{ __('messages.settings_map_enabled_help') }}</p>
            @error('map_enabled')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label for="google_maps_url" class="{{ $labelClass }}">{{ __('messages.settings_google_maps_url') }}</label>
            <input id="google_maps_url" type="text" name="google_maps_url" value="{{ old('google_maps_url', $setting->google_maps_url) }}" placeholder="https://maps.app.goo.gl/…" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">{{ __('messages.settings_google_maps_url_help') }}</p>
            @error('google_maps_url')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="map_latitude" class="{{ $labelClass }}">{{ __('messages.settings_map_latitude') }}</label>
            <input id="map_latitude" type="text" inputmode="decimal" name="map_latitude" value="{{ old('map_latitude', $setting->map_latitude) }}" placeholder="e.g. 17.3515" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">{{ __('messages.settings_map_coords_embed_help') }}</p>
            @error('map_latitude')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="map_longitude" class="{{ $labelClass }}">{{ __('messages.settings_map_longitude') }}</label>
            <input id="map_longitude" type="text" inputmode="decimal" name="map_longitude" value="{{ old('map_longitude', $setting->map_longitude) }}" placeholder="e.g. 95.4877" class="{{ $inputClass }}" />
            @error('map_longitude')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label for="map_title" class="{{ $labelClass }}">{{ __('messages.settings_map_title') }}</label>
            <input id="map_title" type="text" name="map_title" value="{{ old('map_title', $setting->map_title) }}" placeholder="DataPOS & CCTV" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">{{ __('messages.settings_map_title_help') }}</p>
            @error('map_title')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label for="map_embed_enabled" class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="map_embed_enabled" name="map_embed_enabled" value="1" class="h-5 w-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500" {{ old('map_embed_enabled', $setting->map_embed_enabled) ? 'checked' : '' }} />
                <span class="{{ $labelClass }}">{{ __('messages.settings_embed_map') }}</span>
            </label>
            <p class="{{ $helpClass }}">{{ __('messages.settings_map_embed_enabled_help') }}</p>
            @error('map_embed_enabled')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        @php
            $mapPreviewUrl = $setting->mapUrl();
            $mapEmbedSrc = $setting->mapEmbedSrc();
        @endphp
        <div class="md:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                <p class="text-xs font-black text-gray-800 dark:text-slate-200">{{ __('messages.settings_map_preview') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if ($mapPreviewUrl)
                        <a href="{{ $mapPreviewUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-lg bg-slate-800 px-3 py-2 text-xs font-black text-white hover:bg-slate-700">{{ __('messages.settings_open_in_google_maps') }}</a>
                    @else
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.settings_no_map_configured') }}</span>
                    @endif
                    @if ($mapEmbedSrc)
                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">{{ __('messages.settings_embed_ready') }}</span>
                    @else
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.settings_embed_needs_coords') }}</span>
                    @endif
                </div>
                @if ($mapEmbedSrc)
                    <iframe class="mt-3 h-40 w-full rounded-xl border-0" src="{{ $mapEmbedSrc }}" title="{{ $setting->map_title ?: 'Store map' }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                @endif
            </div>
        </div>
    </div>
</div>
