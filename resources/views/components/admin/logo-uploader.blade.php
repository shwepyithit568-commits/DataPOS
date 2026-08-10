@props([
    'inputName' => 'logo',
    'maxMb' => 10,
    'currentLogoUrl' => null,
    'currentLogoAlt' => 'Logo',
    'allowRemove' => false,
    'accept' => 'image/png,image/jpeg,image/webp',
    'removeName' => 'remove_logo',
    'labels' => null,
])

@php
    // Brand defaults — categories pass their own translation set via $labels.
    $labels = $labels ?? [
        'current' => __('messages.brand_current_logo'),
        'keep_current' => __('messages.brand_logo_keep_current'),
        'remove' => __('messages.brand_logo_remove'),
        'replace' => __('messages.brand_logo_replace'),
        'optional' => __('messages.brand_logo_optional'),
        'no_logo' => __('messages.brand_no_logo'),
        'invalid_type' => __('messages.brand_logo_invalid_type'),
        'too_large' => __('messages.brand_logo_too_large', ['mb' => $maxMb]),
        'recommended' => __('messages.brand_logo_recommended'),
        'remove_selected' => __('messages.brand_logo_remove_selected'),
    ];
@endphp

<div x-data="{
    previewUrl: null,
    fileName: '',
    fileSize: '',
    typeError: '',
    sizeError: '',
    selected: false,
    removeChecked: false,
    maxBytes: {{ (int) ($maxMb * 1024 * 1024) }},
    onFileChange(event) {
        const file = event.target.files[0];
        this.clearObjectUrl();
        if (!file) {
            this.selected = false;
            this.fileName = '';
            this.fileSize = '';
            this.typeError = '';
            this.sizeError = '';
            return;
        }
        this.fileName = file.name;
        this.fileSize = file.size > 1048576
            ? (file.size / 1048576).toFixed(1) + ' MB'
            : Math.max(1, Math.round(file.size / 1024)) + ' KB';
        this.typeError = '';
        this.sizeError = '';
        const okTypes = ['image/png', 'image/jpeg', 'image/webp'];
        const okExt = /\.(png|jpe?g|webp)$/i.test(file.name);
        if (!okTypes.includes(file.type) || !okExt) {
            this.typeError = @js($labels['invalid_type']);
        }
        if (file.size > this.maxBytes) {
            this.sizeError = @js($labels['too_large']);
        }
        this.previewUrl = file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
        this.selected = true;
        // Selecting a new file cancels the “remove current” choice.
        this.removeChecked = false;
    },
    clearSelection() {
        this.clearObjectUrl();
        this.selected = false;
        this.fileName = '';
        this.fileSize = '';
        this.typeError = '';
        this.sizeError = '';
        this.removeChecked = false;
        this.$refs.fileInput.value = '';
    },
    clearObjectUrl() {
        if (this.previewUrl) {
            URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
        }
    },
    onRemoveToggle() {
        // Choosing “remove current” clears any pending new file so the two
        // states can never be submitted together.
        if (this.removeChecked && this.selected) {
            this.clearSelection();
            this.removeChecked = true;
        }
    }
}" class="space-y-2.5">
    @if ($currentLogoUrl)
        {{-- Current image state --}}
        <div>
            <p class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ $labels['current'] }}</p>
            <div class="flex items-center gap-3">
                <div class="h-16 w-16 shrink-0 rounded-lg border dark:border-slate-600 bg-white dark:bg-slate-900 p-1 flex items-center justify-center overflow-hidden">
                    <img src="{{ $currentLogoUrl }}" alt="{{ $currentLogoAlt }}" class="max-h-full max-w-full object-contain" />
                </div>
                <div class="text-xs text-gray-500 dark:text-slate-400">
                    <span x-show="!removeChecked && !selected" class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-semibold">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ $labels['keep_current'] }}
                    </span>
                    <span x-show="removeChecked" class="text-red-600 dark:text-red-400 font-semibold">{{ $labels['remove'] }}</span>
                    <span x-show="selected" class="text-violet-600 dark:text-violet-400 font-semibold">{{ $labels['replace'] }}</span>
                </div>
            </div>
            @if ($allowRemove)
                <label class="mt-2 inline-flex items-center gap-2 cursor-pointer select-none text-xs font-semibold text-red-600 dark:text-red-400">
                    <input type="checkbox" name="{{ $removeName }}" value="1" x-model="removeChecked" @change="onRemoveToggle()"
                        class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-red-500 focus:ring-red-500" />
                    {{ $labels['remove'] }}
                </label>
            @endif
        </div>
    @else
        <p class="text-xs text-gray-400 dark:text-slate-500">{{ $labels['no_logo'] }}</p>
    @endif

    {{-- New file selection --}}
    <div class="rounded-lg border border-dashed dark:border-slate-600 border-gray-300 p-3 bg-gray-50/50 dark:bg-slate-900/40">
        <label class="cursor-pointer flex items-center gap-2 text-xs font-semibold text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 10l5-5 5 5M12 5v12"/></svg>
            <span>{{ $currentLogoUrl ? $labels['replace'] : $labels['optional'] }}</span>
            <input type="file" x-ref="fileInput" name="{{ $inputName }}" accept="{{ $accept }}" @change="onFileChange($event)"
                class="sr-only" />
        </label>
        <p class="mt-1.5 text-[11px] sm:text-xs text-gray-400 dark:text-slate-500">
            PNG · JPG · WebP — {{ $labels['too_large'] }}
            <span class="block">{{ $labels['recommended'] }}</span>
        </p>
    </div>

    {{-- Selected file preview + meta --}}
    <div x-show="selected" x-cloak x-transition.opacity class="flex items-start gap-3 rounded-lg border dark:border-slate-700 border-gray-200 p-2.5">
        <div class="h-14 w-14 shrink-0 rounded-md border dark:border-slate-600 bg-white dark:bg-slate-900 p-0.5 flex items-center justify-center overflow-hidden">
            <img x-show="previewUrl" :src="previewUrl" alt="Preview" class="max-h-full max-w-full object-contain" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-xs font-semibold text-gray-800 dark:text-slate-200 truncate" x-text="fileName"></div>
            <div class="text-[11px] text-gray-400 dark:text-slate-500" x-text="fileSize"></div>
            <p x-show="typeError" x-cloak class="text-[11px] sm:text-xs text-red-600 dark:text-red-400 font-medium" x-text="typeError"></p>
            <p x-show="sizeError" x-cloak class="text-[11px] sm:text-xs text-red-600 dark:text-red-400 font-medium" x-text="sizeError"></p>
        </div>
        <button type="button" @click="clearSelection()"
            class="shrink-0 min-h-11 px-2.5 inline-flex items-center gap-1 text-xs font-semibold text-gray-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition"
            :aria-label="@js($labels['remove_selected'])">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ $labels['remove_selected'] }}
        </button>
    </div>
</div>
