{{-- Reusable inline Add Sub-Category form — rendered directly under a parent's
     header in BOTH table and card views (only the active view's template
     renders, so the x-ref/id stay unique). $parent must be provided. --}}
<form method="POST" action="{{ url('/store/' . $store->slug . '/admin/categories') }}" enctype="multipart/form-data"
    x-data="{ icon: '' }"
    @submit="if (savingSub) { $event.preventDefault(); } else { savingSub = true; }"
    class="space-y-3">
    @csrf
    <input type="hidden" name="parent_id" value="{{ $parent->id }}" />
    <p class="text-xs font-semibold text-violet-700 dark:text-violet-400">➕ {{ __('messages.category_add_sub_under', ['name' => $parent->name]) }}</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-start">
        <div>
            <label for="add-sub-name" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_sub_name') }} <span class="text-rose-500">*</span></label>
            <input id="add-sub-name" x-ref="addSubName" x-init="$el.focus()" type="text" name="name" required
                value="{{ old('parent_id') == $parent->id ? old('name') : '' }}"
                placeholder="e.g. TouchLCD"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ old('parent_id') == $parent->id && $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @if (old('parent_id') == $parent->id)
                @error('name')
                    <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            @endif
        </div>
        <div>
            <label for="add-sub-code" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.product_form_code') }}</label>
            <input id="add-sub-code" type="text" name="code"
                value="{{ old('parent_id') == $parent->id ? old('code') : '' }}"
                placeholder="{{ __('messages.product_form_code_placeholder') }}"
                class="w-full uppercase font-mono border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
            @if (old('parent_id') == $parent->id)
                @error('code')
                    <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            @endif
        </div>
        <div>
            <label for="add-sub-icon" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_icon_optional') }}</label>
            <input id="add-sub-icon" type="text" name="icon" x-model="icon" maxlength="8" placeholder="🗂️"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
        </div>
        <div class="lg:col-span-1">
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_image_optional') }}</label>
            <x-admin.logo-uploader :maxMb="$imageMaxMb" :input-name="'image'" :labels="$catUploaderLabels" />
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <button type="submit" :disabled="savingSub"
            class="inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed font-semibold text-sm shadow transition">
            <span x-show="!savingSub" class="inline-flex items-center gap-1.5"><span class="text-base leading-none">+</span><span>{{ __('messages.category_save_sub') }}</span></span>
            <span x-show="savingSub" class="inline-flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                {{ __('messages.category_saving_sub') }}
            </span>
        </button>
        <button type="button" @click="closeAddSub()" :disabled="savingSub"
            class="min-h-11 px-4 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
            {{ __('messages.category_add_sub_cancel') }}
        </button>
    </div>
</form>
