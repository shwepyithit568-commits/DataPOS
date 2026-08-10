@extends('layouts.admin.app')

@section('content')
@php
    $presetIcons = ['📂', '📱', '🔧', '🔌', '🎧', '⚡', '📷', '🔋', '🗜️', '🧰', '💾', '🖥️', '📶', '🛡️', '🧲', '💡', '📦', '🗂️'];

    $catUploaderLabels = [
        'current' => __('messages.category_current_image'),
        'keep_current' => __('messages.category_image_keep_current'),
        'remove' => __('messages.category_remove_image'),
        'replace' => __('messages.category_image_replace'),
        'optional' => __('messages.category_image_optional'),
        'no_logo' => __('messages.category_no_image'),
        'invalid_type' => __('messages.category_image_invalid_type'),
        'too_large' => __('messages.category_image_too_large', ['mb' => $imageMaxMb]),
        'recommended' => __('messages.category_image_recommended'),
        'remove_selected' => __('messages.category_image_remove_selected'),
    ];
@endphp
<div class="w-full">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit truncate">{{ __('messages.category_edit_title', ['name' => $category->name]) }}</h1>
        <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/categories') }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline">&larr; {{ __('messages.category_back') }}</a>
    </div>

    {{-- Error Flash --}}
    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1 mb-5">
            <div class="flex items-center gap-2 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-6">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Form — sensible width on desktop, full-width on small screens --}}
    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/categories/' . $category->id) }}" enctype="multipart/form-data"
        x-data="{
            type: {{ $category->parent_id ? "'sub'" : "'parent'" }},
            selectedParent: @js(old('parent_id', $category->parent_id ?? '')),
            icon: @js(old('icon', $category->icon ?? '')),
            parentOptions: @js($parentOptions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()),
            saving: false,
            dirty: false,
            get selectedParentName() {
                return this.parentOptions.find((p) => String(p.id) === String(this.selectedParent))?.name || '';
            },
            get pathPreview() {
                return this.type === 'sub'
                    ? (this.selectedParent ? this.selectedParentName + ' ▸ ' + @js($category->name) : @js(__('messages.category_path_choose')))
                    : @js(__('messages.category_path_parent'));
            }
        }"
        x-init="
            const warn = (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } };
            window.addEventListener('beforeunload', warn);
        "
        @submit="if (saving) { $event.preventDefault(); } else { saving = true; }"
        class="w-full max-w-2xl bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-xl border border-gray-200/80 dark:border-slate-700 space-y-5 transition-colors duration-200">
        @csrf
        @method('PUT')

        <div>
            <label for="edit-category-name" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_name') }} <span class="text-rose-500">*</span></label>
            <input id="edit-category-name" type="text" name="name" value="{{ old('name', $category->name) }}" required
                @input="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('name')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Category Type: Parent vs Sub-category --}}
        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">{{ __('messages.category_type') }}</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label :class="type === 'parent' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'border-slate-200 dark:border-slate-600 hover:border-violet-300'"
                    class="flex items-start gap-2.5 rounded-lg border p-3 cursor-pointer transition">
                    <input type="radio" x-model="type" value="parent" class="mt-0.5 accent-violet-600" />
                    <span>
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-100">📂 {{ __('messages.category_type_parent') }}</span>
                        <span class="block text-xs text-gray-500 dark:text-slate-400">{{ __('messages.category_type_parent_hint') }}</span>
                    </span>
                </label>
                <label :class="type === 'sub' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'border-slate-200 dark:border-slate-600 hover:border-violet-300'"
                    class="flex items-start gap-2.5 rounded-lg border p-3 transition {{ $childrenCount > 0 ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }}">
                    <input type="radio" x-model="type" value="sub" class="mt-0.5 accent-violet-600" {{ $childrenCount > 0 ? 'disabled' : '' }} />
                    <span>
                        <span class="block text-sm font-bold text-gray-800 dark:text-slate-100">🗂️ {{ __('messages.category_type_sub') }}</span>
                        <span class="block text-xs text-gray-500 dark:text-slate-400">{{ __('messages.category_type_sub_hint') }}</span>
                    </span>
                </label>
            </div>

            @if ($childrenCount > 0)
                <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/></svg>
                    {{ __('messages.category_convert_blocked') }}
                </p>
            @endif

            <div x-show="type === 'sub'" x-cloak x-transition class="mt-2.5">
                <label for="edit-category-parent" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_belongs_under') }} <span class="text-rose-500">*</span></label>
                {{-- The select is ONLY required/enabled when type === 'sub'. A hidden
                     required select previously blocked Main Category edits silently. --}}
                <select id="edit-category-parent" x-model="selectedParent"
                    :required="type === 'sub'" :disabled="type !== 'sub'"
                    class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                    <option value="">{{ __('messages.category_choose_parent') }}</option>
                    @foreach ($parentOptions as $parentOption)
                        <option value="{{ $parentOption->id }}">{{ $parentOption->name }}</option>
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            {{-- Submits null when type === 'parent' (no conflicting parent_id). --}}
            <input type="hidden" name="parent_id" :value="type === 'sub' ? selectedParent : ''" />

            <p class="mt-2 text-xs font-semibold text-violet-600 dark:text-violet-400" x-text="pathPreview"></p>
        </div>

        {{-- Icon --}}
        <div>
            <label for="edit-category-icon" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_icon_optional') }}</label>
            <input id="edit-category-icon" type="text" name="icon" x-model="icon" maxlength="8" placeholder="📱"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
            <div class="mt-2 flex flex-wrap gap-1.5">
                @foreach ($presetIcons as $presetIcon)
                    <button type="button" @click="icon = @js($presetIcon)"
                        :aria-pressed="icon === @js($presetIcon)" :aria-label="@js($presetIcon)"
                        :class="icon === @js($presetIcon) ? 'ring-2 ring-violet-500 bg-violet-100 dark:bg-violet-900/40 border-violet-500' : 'border-slate-200 dark:border-slate-600 hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-slate-700'"
                        class="h-10 w-10 min-h-10 rounded-lg border text-base transition">
                        {{ $presetIcon }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Image --}}
        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_current_image') }}</label>
            <x-admin.logo-uploader
                :maxMb="$imageMaxMb"
                :current-logo-url="$category->image_path ? asset('storage/' . $category->image_path) : null"
                :current-logo-alt="$category->name"
                :allow-remove="true"
                :input-name="'image'"
                :remove-name="'remove_image'"
                :labels="$catUploaderLabels" />
            @error('image')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="edit-category-desc" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.category_description') }}</label>
            <textarea id="edit-category-desc" name="description" rows="3" @input="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">{{ old('description', $category->description) }}</textarea>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
            <button type="submit" :disabled="saving"
                class="inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed font-semibold text-sm shadow transition">
                <span x-show="!saving" class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('messages.category_update') }}
                </span>
                <span x-show="saving" class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    {{ __('messages.category_saving') }}
                </span>
            </button>
            <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/categories') }}"
                class="inline-flex items-center min-h-11 px-4 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
