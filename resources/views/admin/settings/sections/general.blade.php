<div class="p-4 sm:p-6 space-y-6">
    <div class="admin-section-head">
        <div>
            <h2 class="admin-section-title">🏪 {{ __('messages.settings_store_identity') }}</h2>
            <p class="admin-section-sub">အရှေ့ဘက် storefront header, title နှင့် default language အတွက်အချက်အလက်များ။</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="store_name" class="{{ $labelClass }}">Store Name <span class="text-rose-500">*</span></label>
            <input id="store_name" type="text" name="store_name" value="{{ old('store_name', $setting->store_name ?? $store->name) }}" required class="{{ $inputClass }}" />
            @error('store_name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="tagline" class="{{ $labelClass }}">Store Tagline</label>
            <input id="tagline" type="text" name="tagline" maxlength="160" value="{{ old('tagline', $setting->tagline) }}" placeholder="ဥပမာ — မိုဘိုင်းဖုန်းနှင့် အပိုပစ္စည်းများ" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">Storefront header တွင် ဆိုင်အမည်အောက်မှာ ပြမည့် စာသား (အများဆုံး 160 characters)။</p>
            @error('tagline')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="default_language" class="{{ $labelClass }}">Default Language <span class="text-rose-500">*</span></label>
            <select id="default_language" name="default_language" class="{{ $inputClass }}">
                @foreach (config('localization.supported', []) as $code => $locale)
                    <option value="{{ $code }}" {{ old('default_language', $setting->default_language ?? config('app.locale')) === $code ? 'selected' : '' }}>
                        {{ $locale['label'] }} ({{ $locale['native'] }})
                    </option>
                @endforeach
            </select>
            <p class="{{ $helpClass }}">New visitors will see this language first unless they choose another one.</p>
            @error('default_language')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="opening_hours" class="{{ $labelClass }}">Opening Hours</label>
            <input id="opening_hours" type="text" name="opening_hours" value="{{ old('opening_hours', $setting->opening_hours) }}" placeholder="09:00 AM to 05:00 PM" class="{{ $inputClass }}" />
            @error('opening_hours')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="pt-2">
        <h3 class="admin-section-title">🖼️ {{ __('messages.settings_brand_assets') }}</h3>
        <p class="admin-section-sub mt-0.5">Each surface can now use its own logo. Leaving a field empty keeps the documented fallback.</p>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        @php
            $storefrontEffective = $setting->storefrontLogo();
            $adminEffective = $setting->adminLogo();
            $faviconEffective = $setting->favicon();
        @endphp

        <x-admin.settings.sections.brand-asset
            field="storefront_logo"
            label="Storefront Logo"
            help="Horizontal PNG or WebP recommended. Ideal ratio: 3:1. Used in the Storefront header, login pages, invoices and social previews."
            :current-url="$storefrontEffective ? asset('storage/' . $storefrontEffective) : null"
            fallback-note="{{ $setting->storefront_logo_path ? '' : 'Using the legacy logo. Upload a horizontal logo for the Storefront header.' }}"
            max-mb="2"
            preview-wrap-class="flex h-24 w-full items-center justify-center"
            preview-img-class="max-h-20 max-w-full"
            input-id="brand-asset-storefront-logo"
        />

        <x-admin.settings.sections.brand-asset
            field="admin_logo"
            label="Admin Logo"
            help="Square PNG or WebP recommended. Ideal ratio: 1:1. Used in the Admin sidebar and compact Admin surfaces."
            :current-url="$adminEffective ? asset('storage/' . $adminEffective) : null"
            fallback-note="{{ $setting->admin_logo_path ? '' : ($setting->storefront_logo_path ? 'Using the Storefront logo. Upload a square icon for the Admin sidebar.' : 'Using the legacy logo. Upload a square icon for the Admin sidebar.') }}"
            max-mb="2"
            preview-wrap-class="flex h-24 w-24 items-center justify-center mx-auto"
            preview-img-class="max-h-24 max-w-24"
            :size-chips="[48, 32]"
            input-id="brand-asset-admin-logo"
        />

        <x-admin.settings.sections.brand-asset
            field="favicon"
            label="Favicon / App Icon"
            help="Square PNG or WebP. Recommended source size: 512×512px. Used in browser tabs, bookmarks and mobile shortcuts."
            :current-url="$faviconEffective ? asset('storage/' . $faviconEffective) : null"
            fallback-note="{{ $setting->favicon_path ? '' : 'Using a fallback image. For best results upload a 512×512 square PNG.' }}"
            accept="image/png,image/webp"
            max-mb="1"
            preview-wrap-class="flex h-16 w-16 items-center justify-center mx-auto"
            preview-img-class="max-h-14 max-w-14"
            :size-chips="[32, 16]"
            input-id="brand-asset-favicon"
        />
    </div>
</div>
