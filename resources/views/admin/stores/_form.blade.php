@php
    $isEdit = isset($store) && $store->exists;
    $setting = $isEdit ? ($store->setting ?? $store->setting()->first()) : null;
    $action = $isEdit ? route('admin.stores.update', $store) : route('admin.stores.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $action }}" class="w-full max-w-3xl bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-xl border border-gray-200/80 dark:border-slate-700 space-y-5 transition-colors duration-200"
    x-data="{ saving: false, dirty: false, primary: {{ $isEdit ? ($store->is_primary ? 'true' : 'false') : 'false' }} }"
    x-init="
        const warn = (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } };
        window.addEventListener('beforeunload', warn);
        $nextTick(() => { const name = $refs.editName; if (name && (!document.activeElement || document.activeElement === document.body)) name.focus(); });
    "
    @submit="if (saving) { $event.preventDefault(); } else { saving = true; }">
    @csrf
    @method($method)

    {{-- Store identity --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
        <div>
            <label for="store-name" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_name') }} <span class="text-rose-500">*</span></label>
            <input id="store-name" x-ref="editName" type="text" name="name" value="{{ old('name', $isEdit ? $store->name : '') }}"
                @input="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('name')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="store-slug" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_slug') }} <span class="text-rose-500">*</span></label>
            <input id="store-slug" type="text" name="slug" value="{{ old('slug', $isEdit ? $store->slug : '') }}"
                @input="dirty = true"
                placeholder="e.g. my-shop"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('slug') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('slug')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-[11px] text-gray-400 dark:text-slate-500">{{ __('messages.store_slug_hint') }}</p>
        </div>
    </div>

    {{-- Status + language --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
        <div>
            <label for="store-default-language" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_default_language') }} <span class="text-rose-500">*</span></label>
            <select id="store-default-language" name="default_language" @change="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                @foreach (config('localization.supported', []) as $code => $locale)
                    <option value="{{ $code }}" {{ old('default_language', $isEdit ? ($setting?->default_language ?? 'my') : 'my') === $code ? 'selected' : '' }}>{{ $locale['label'] }}</option>
                @endforeach
            </select>
            @error('default_language')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_status') }}</label>
            <label class="inline-flex items-center gap-2 min-h-11 cursor-pointer select-none">
                <input type="checkbox" name="is_active" value="1" @change="dirty = true"
                    {{ old('is_active', $isEdit ? $store->is_active : true) ? 'checked' : '' }}
                    class="h-5 w-5 rounded border-gray-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500" />
                <span class="text-sm text-gray-700 dark:text-slate-300">{{ __('messages.store_active') }}</span>
            </label>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_primary') }}</label>
            <label class="inline-flex items-center gap-2 min-h-11 cursor-pointer select-none">
                <input type="checkbox" name="is_primary" value="1" x-model="primary" @change="dirty = true"
                    class="h-5 w-5 rounded border-gray-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500" />
                <span class="text-sm text-gray-700 dark:text-slate-300">{{ __('messages.store_primary') }}</span>
            </label>
            <p class="text-[11px] text-gray-400 dark:text-slate-500" x-show="primary" x-cloak>{{ __('messages.store_primary_hint') }}</p>
        </div>
    </div>

    {{-- Contact --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
        <div>
            <label for="store-phone" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_phone') }}</label>
            <input id="store-phone" type="text" name="phone" value="{{ old('phone', $isEdit ? ($setting?->phone ?? '') : '') }}"
                @input="dirty = true"
                placeholder="09xxxxxxxxx"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('phone') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('phone')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="store-viber" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_viber') }}</label>
            <input id="store-viber" type="text" name="viber_number" value="{{ old('viber_number', $isEdit ? ($store->viber_number ?? $setting?->viber_number ?? '') : '') }}"
                @input="dirty = true"
                placeholder="09xxxxxxxxx"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('viber_number') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('viber_number')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="store-telegram" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_telegram') }}</label>
            <input id="store-telegram" type="text" name="telegram_username" value="{{ old('telegram_username', $isEdit ? ($store->telegram_username ?? $setting?->telegram_username ?? '') : '') }}"
                @input="dirty = true"
                placeholder="username (no @)"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('telegram_username') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('telegram_username')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="store-opening-hours" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_opening_hours') }}</label>
            <input id="store-opening-hours" type="text" name="opening_hours" value="{{ old('opening_hours', $isEdit ? ($setting?->opening_hours ?? '') : '') }}"
                @input="dirty = true"
                placeholder="09:00 AM To 05:00 PM"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('opening_hours') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('opening_hours')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Address --}}
    <div>
        <label for="store-address" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_address') }}</label>
        <textarea id="store-address" name="address" rows="2" @input="dirty = true"
            class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">{{ old('address', $isEdit ? ($setting?->address ?? '') : '') }}</textarea>
        @error('address')
            <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Delivery / payment info --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
        <div>
            <label for="store-delivery-info" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_delivery_info') }}</label>
            <textarea id="store-delivery-info" name="delivery_info" rows="3" @input="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">{{ old('delivery_info', $isEdit ? ($setting?->delivery_info ?? '') : '') }}</textarea>
            @error('delivery_info')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="store-payment-info" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.store_payment_info') }}</label>
            <textarea id="store-payment-info" name="payment_info" rows="3" @input="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">{{ old('payment_info', $isEdit ? ($setting?->payment_info ?? '') : '') }}</textarea>
            @error('payment_info')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-2 pt-1">
        <button type="submit" :disabled="saving"
            class="inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed font-semibold text-sm shadow transition">
            <span x-show="!saving" class="inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ $isEdit ? __('messages.store_update') : __('messages.store_create_title') }}
            </span>
            <span x-show="saving" class="inline-flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                {{ __('messages.store_saving') }}
            </span>
        </button>
        <a href="{{ route('admin.stores.index') }}"
            class="inline-flex items-center min-h-11 px-4 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
            {{ __('messages.cancel') }}
        </a>
    </div>
</form>
