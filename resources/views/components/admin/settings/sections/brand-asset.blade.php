@props([
    'field' => '',            // form field name, e.g. storefront_logo
    'label' => '',
    'help' => '',
    'currentUrl' => null,     // effective asset URL (dedicated or fallback) or null
    'fallbackNote' => '',     // e.g. "Falling back to the legacy logo."
    'accept' => 'image/png,image/webp,image/jpeg',
    'maxMb' => 2,             // client-side guidance (server validation is authoritative)
    'previewWrapClass' => '', // wide vs square preview container
    'previewImgClass' => '',  // sizing of the preview <img>
    'inputId' => '',
    'sizeChips' => [],        // realistic pixel-size previews, e.g. [48, 32]
])

@php
    $inputId = $inputId ?: ('brand-asset-' . $field);
@endphp

<div
    x-data="brandAssetUploader('{{ $field }}', {
        accept: '{{ $accept }}',
        maxBytes: {{ $maxMb * 1024 * 1024 }},
        currentUrl: {{ $currentUrl ? "'" . e($currentUrl, false) . "'" : 'null' }},
        fallbackNote: {{ $fallbackNote ? "'" . e($fallbackNote, false) . "'" : "''" }},
    })"
    @settings-submitting.window="beginSubmit()"
    class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 dark:border-slate-700 dark:bg-slate-900 transition-colors"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <label for="{{ $inputId }}" class="block text-sm font-bold text-gray-900 dark:text-slate-100">
                {{ $label }}
            </label>
            <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-slate-400">{{ $help }}</p>
        </div>
        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
            Max {{ $maxMb }} MB
        </span>
    </div>

    {{-- Preview: current/selected image on light + dark sample backgrounds --}}
    <div class="mt-4 grid grid-cols-2 gap-2">
        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700">
            <p class="mb-2 text-[10px] font-bold uppercase tracking-wide text-slate-500">Light</p>
            <div class="{{ $previewWrapClass }} rounded-lg bg-gradient-to-br from-slate-50 to-slate-200">
                <template x-if="previewSrc">
                    <img :src="previewSrc" alt="" class="{{ $previewImgClass }} object-contain" />
                </template>
                <template x-if="!previewSrc && showEmpty">
                    <span class="text-[11px] font-semibold text-slate-400">No image</span>
                </template>
                <template x-if="!previewSrc && showMarkedForRemoval">
                    <span class="text-[11px] font-semibold text-rose-500">Will be removed</span>
                </template>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-900 p-3 dark:border-slate-700">
            <p class="mb-2 text-[10px] font-bold uppercase tracking-wide text-slate-400">Dark</p>
            <div class="{{ $previewWrapClass }} rounded-lg bg-slate-950">
                <template x-if="previewSrc">
                    <img :src="previewSrc" alt="" class="{{ $previewImgClass }} object-contain" />
                </template>
                <template x-if="!previewSrc && showEmpty">
                    <span class="text-[11px] font-semibold text-slate-500">No image</span>
                </template>
                <template x-if="!previewSrc && showMarkedForRemoval">
                    <span class="text-[11px] font-semibold text-rose-400">Will be removed</span>
                </template>
            </div>
        </div>
    </div>

    {{-- Realistic size chips (e.g. 48×48 + 32×32 for the Admin icon) --}}
    @if (count($sizeChips) > 0)
        <div class="mt-3 flex flex-wrap items-end gap-3">
            @foreach ($sizeChips as $px)
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center rounded-md border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800" style="width: {{ $px }}px; height: {{ $px }}px;">
                        <template x-if="previewSrc">
                            <img :src="previewSrc" alt="" class="h-full w-full object-contain" />
                        </template>
                        <template x-if="!previewSrc && showEmpty">
                            <span class="text-[9px] font-semibold text-slate-400">—</span>
                        </template>
                    </div>
                    <p class="mt-1 text-[10px] font-bold text-slate-600 dark:text-slate-300">{{ $px }}×{{ $px }}</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Fallback notice (when a legacy/other asset is in use) --}}
    <template x-if="fallbackNote && showCurrent">
        <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
            <span class="font-black">Fallback in use:</span> <span x-text="fallbackNote"></span>
        </p>
    </template>

    {{-- Selected filename + size (aria-live) --}}
    <p aria-live="polite" class="mt-3 min-h-[1.25rem] text-xs font-semibold text-gray-700 dark:text-slate-300">
        <template x-if="selectedFile">
            <span><span x-text="fileName"></span> · <span x-text="fileSizeLabel"></span></span>
        </template>
        <template x-if="!selectedFile && showCurrent">
            <span>Current asset active</span>
        </template>
    </p>

    {{-- Validation / error (role=alert only for real errors) --}}
    <p role="alert" class="mt-1 text-xs font-bold text-rose-600 dark:text-rose-400" x-show="fileError" x-text="fileError" x-cloak></p>
    @error($field)<p role="alert" class="mt-1 text-xs font-bold text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror

    {{-- Controls --}}
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <label for="{{ $inputId }}"
            class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" /></svg>
            <template x-if="!selectedFile && showCurrent"><span>Replace</span></template>
            <template x-if="!selectedFile && !showCurrent"><span>Choose image</span></template>
            <template x-if="selectedFile"><span>Choose different</span></template>
            <input id="{{ $inputId }}" type="file" name="{{ $field }}" accept="{{ $accept }}" x-ref="fileInput"
                @change="handleFile($event)" class="sr-only" />
        </label>

        <button type="button" x-show="selectedFile" @click="clearSelection()"
            class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" x-cloak>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            Remove selected
        </button>

        <template x-if="showRemoveAction">
            <button type="button" @click="markForRemove()"
                class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border border-rose-200 px-3.5 py-2.5 text-sm font-bold text-rose-600 transition hover:bg-rose-50 dark:border-rose-800 dark:text-rose-400 dark:hover:bg-rose-950/40" x-cloak>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                Remove {{ $label }}
            </button>
        </template>

        <template x-if="showMarkedForRemoval">
            <button type="button" @click="cancelRemove()"
                class="inline-flex min-h-11 items-center gap-1.5 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" x-cloak>
                Cancel removal
            </button>
        </template>
    </div>

    {{-- Remove flag submitted only when marked --}}
    <input type="hidden" :name="field + '_remove'" :value="markRemove ? '1' : ''" />
</div>
