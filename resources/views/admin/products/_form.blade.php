{{-- Shared product form body. Requires Alpine state on create/edit wrappers. --}}
@php
    $input = 'mt-1 block w-full rounded-xl border border-gray-300 dark:border-slate-600 px-3.5 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 shadow-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30';
    $label = 'block text-sm font-semibold text-gray-800 dark:text-slate-200';
    $hint  = 'mt-1 text-xs leading-5 text-gray-500 dark:text-slate-400';
    $section = 'rounded-xl bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:p-5';
    $sectionTitle = 'text-base font-black text-gray-900 dark:text-slate-100';
    $sectionHint = 'mt-1 text-sm leading-6 text-gray-500 dark:text-slate-400';
    $fileInput = 'mt-1 block w-full text-sm text-gray-600 dark:text-slate-400 file:mr-4 file:rounded-xl file:border-0 file:bg-violet-50 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-slate-700 dark:file:text-violet-300';
@endphp

<div class="space-y-5">
    <section class="{{ $section }}">
        <div class="mb-4">
            <h2 class="{{ $sectionTitle }}">{{ __('messages.product_form_core_section') }}</h2>
            <p class="{{ $sectionHint }}">{{ __('messages.product_form_core_section_hint') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_name') }} <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="{{ $input }}" placeholder="{{ __('messages.product_form_name_placeholder') }}" />
                @error('name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_sku') }} <span class="text-rose-500">*</span></label>
                <input type="text" name="sku" x-model="productSku" value="{{ old('sku', $product->sku) }}" required class="{{ $input }}" placeholder="{{ __('messages.product_form_sku_placeholder') }}" />
                @error('sku')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="{{ $label }}">{{ __('messages.product_form_main_category') }}</label>
                    <button type="button" @click="categoryModalOpen = true" class="text-xs font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('messages.product_form_quick_category') }}</button>
                </div>
                <select x-model="selectedMainCategory" @change="onMainCategoryChange()" x-init="$nextTick(() => $el.value = selectedMainCategory)" class="{{ $input }} cursor-pointer">
                    <option value="">{{ __('messages.product_form_main_category_none') }}</option>
                    <template x-for="cat in mainCategories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_sub_category') }}</label>
                <select x-model="selectedSubCategory" :disabled="!selectedMainCategory" x-init="$nextTick(() => $el.value = selectedSubCategory)" class="{{ $input }} cursor-pointer disabled:cursor-not-allowed disabled:opacity-60">
                    <option value="" x-text="!selectedMainCategory ? '{{ __('messages.product_form_sub_category_choose_first') }}' : (subCategories.length ? '{{ __('messages.product_form_sub_category_none') }}' : '{{ __('messages.product_form_no_sub_categories') }}')"></option>
                    <template x-for="cat in subCategories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
                <input type="hidden" name="category_id" :value="selectedSubCategory || selectedMainCategory" />
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="{{ $label }}">{{ __('messages.product_form_brand') }}</label>
                    <button type="button" @click="brandModalOpen = true" class="text-xs font-bold text-violet-600 hover:underline dark:text-violet-400">{{ __('messages.product_form_quick_brand') }}</button>
                </div>
                {{-- x-init re-applies the persisted value after the x-for options
                     render — without it, x-model initializes the select before the
                     options exist, leaving the blank option selected and the
                     untouched form clearing brand_id on save. Same fix the main/
                     sub-category selects use. --}}
                <select name="brand_id" x-model="selectedBrand" x-init="$nextTick(() => $el.value = selectedBrand)" class="{{ $input }} cursor-pointer">
                    <option value="">{{ __('messages.product_form_none') }}</option>
                    <template x-for="b in brands" :key="b.id">
                        <option :value="b.id" x-text="b.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_stock_status') }}</label>
                <select name="stock_status" x-model="productStock" class="{{ $input }} cursor-pointer">
                    <option value="in_stock" {{ old('stock_status', $product->stock_status) === 'in_stock' ? 'selected' : '' }}>{{ __('messages.in_stock') }}</option>
                    <option value="out_of_stock" {{ old('stock_status', $product->stock_status) === 'out_of_stock' ? 'selected' : '' }}>{{ __('messages.out_of_stock') }}</option>
                </select>
            </div>
        </div>
    </section>

    <section class="{{ $section }}">
        <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <h2 class="{{ $sectionTitle }}">{{ __('messages.product_form_pricing_section') }}</h2>
                <p class="{{ $sectionHint }}">{{ __('messages.product_form_pricing_section_hint') }}</p>
            </div>
            <div class="rounded-xl bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                {{ __('messages.product_form_margin') }}:
                <span :class="marginPercent >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-400'" x-text="marginPercent + '%'">0%</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_retail_price') }} <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="retail_price" x-model="marginRetail" value="{{ old('retail_price', $product->retail_price) }}" required class="{{ $input }}" placeholder="1990000" />
                @error('retail_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_wholesale_price') }} <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="wholesale_price" x-model="marginWhole" value="{{ old('wholesale_price', $product->wholesale_price) }}" required class="{{ $input }}" placeholder="{{ __('messages.product_form_wholesale_placeholder') }}" />
                @error('wholesale_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_old_price') }}</label>
                <input type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $product->old_price) }}" class="{{ $input }}" placeholder="{{ __('messages.product_form_old_price_placeholder') }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_old_price_hint') }}</p>
                @error('old_price')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">{{ __('messages.product_form_sale_starts_at') }}</label>
                    <input type="datetime-local" name="sale_starts_at" value="{{ old('sale_starts_at', optional($product->sale_starts_at)->format('Y-m-d\TH:i')) }}" class="{{ $input }}" />
                    <p class="{{ $hint }}">{{ __('messages.product_form_sale_starts_hint') }}</p>
                    @error('sale_starts_at')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $label }}">{{ __('messages.product_form_sale_ends_at') }}</label>
                    <input type="datetime-local" name="sale_ends_at" value="{{ old('sale_ends_at', optional($product->sale_ends_at)->format('Y-m-d\TH:i')) }}" class="{{ $input }}" />
                    <p class="{{ $hint }}">{{ __('messages.product_form_sale_ends_hint') }}</p>
                    @error('sale_ends_at')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </section>

    <section class="{{ $section }}">
        <div class="mb-4">
            <h2 class="{{ $sectionTitle }}">{{ __('messages.product_form_media_section') }}</h2>
            <p class="{{ $sectionHint }}">{{ __('messages.product_form_media_section_hint') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_product_image') }}</label>
                <input type="file" name="image" accept="image/*" @change="previewMain($event)" class="{{ $fileInput }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_product_image_hint', ['size' => 10]) }}</p>
                @error('image')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                <div class="mt-3 flex flex-wrap gap-2">
                    <img x-show="mainPreview" :src="mainPreview" class="h-24 w-24 rounded-xl object-cover dark:border-slate-700" />
                    @if (!$isEdit && !empty($product->image_path))
                        <img src="{{ asset('storage/' . $product->image_path) }}" class="h-24 w-24 rounded-xl object-cover dark:border-slate-700" />
                    @endif
                    @if ($isEdit && !empty($product->image_path))
                        <div class="relative h-24 w-24 overflow-hidden rounded-xl">
                            <img src="{{ asset('storage/' . $product->image_path) }}" class="h-24 w-24 object-cover" />
                            <span class="absolute inset-x-0 bottom-0 bg-black/65 py-1 text-center text-xs font-bold text-white">{{ __('messages.product_form_current_image') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_gallery_images') }}</label>
                <input type="file" name="gallery_images[]" multiple accept="image/*" @change="previewGallery($event)" class="{{ $fileInput }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_gallery_hint', ['count' => 4, 'size' => 10]) }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <template x-for="(g, gi) in galleryPreviews" :key="gi">
                        <img :src="g" class="h-24 w-24 rounded-xl object-cover dark:border-slate-700" />
                    </template>
                </div>
            </div>
        </div>
    </section>

    <section class="{{ $section }}">
        <div class="mb-4">
            <h2 class="{{ $sectionTitle }}">{{ __('messages.product_form_policy_section') }}</h2>
            <p class="{{ $sectionHint }}">{{ __('messages.product_form_policy_section_hint') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_warranty') }}</label>
                <input type="text" name="warranty" x-model="productWarranty" value="{{ old('warranty', $product->warranty) }}" class="{{ $input }}" placeholder="{{ __('messages.product_form_warranty_placeholder') }}" />
            </div>

            <div>
                <label class="{{ $label }}">{{ __('messages.product_form_return_policy') }}</label>
                {{-- textarea (not a single-line input) so long policies stay readable;
                     the backend field name and request parameter are unchanged. --}}
                <textarea name="return_policy" rows="2" @input="refreshReturnPolicyPreview()" class="{{ $input }}" placeholder="{{ __('messages.product_form_return_policy_placeholder') }}">{{ old('return_policy', $product->return_policy) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="{{ $label }}">{{ __('messages.product_form_description') }}</label>
                <x-richtext-editor name="description" :value="old('description', $product->description)" :rows="160" placeholder="{{ __('messages.product_form_description_placeholder') }}" />
                <p class="{{ $hint }}">{{ __('messages.product_form_description_hint') }}</p>
                @error('description')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="{{ $label }}">{{ __('messages.product_form_meta_description') }}</label>
                <textarea name="meta_description" rows="2" maxlength="1000" @input="metaDescLen = $el.value.length" class="{{ $input }}" placeholder="{{ __('messages.product_form_meta_placeholder') }}">{{ old('meta_description', $product->meta_description) }}</textarea>
                <div class="mt-1 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                    <p class="{{ $hint }}">{{ __('messages.product_form_meta_helper') }}</p>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs font-semibold text-gray-400 dark:text-slate-500" :class="metaDescLen > 160 ? 'text-rose-500 dark:text-rose-400' : ''">
                            <span x-text="metaDescLen"></span>/160
                        </span>
                    </div>
                </div>
                <p class="{{ $hint }}">{{ __('messages.product_form_meta_empty_fallback') }}</p>
                @error('meta_description')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- Storefront previews: Description + auto-generated Specifications.
         Read-only — no new database fields. Mirrors app/Support/ProductSpecifications.php. --}}
    <section class="{{ $section }}">
        <div class="mb-4">
            <h2 class="{{ $sectionTitle }}">{{ __('messages.product_form_preview_section') }}</h2>
            <p class="{{ $sectionHint }}">{{ __('messages.product_form_specs_preview_hint') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="min-w-0">
                <h3 class="mb-2 text-xs font-black uppercase tracking-wide text-gray-500 dark:text-slate-400">{{ __('messages.product_form_description_preview') }}</h3>
                <div class="min-h-[8rem] rounded-xl border border-gray-200 bg-gray-50/80 p-4 text-sm leading-relaxed text-gray-800 prose prose-sm max-w-none dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100">
                    <template x-if="descriptionPreviewHtml">
                        <div x-html="descriptionPreviewHtml"></div>
                    </template>
                    <template x-if="!descriptionPreviewHtml">
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('messages.spec_description_empty') }}</p>
                    </template>
                </div>
            </div>

            <div class="min-w-0">
                <h3 class="mb-2 text-xs font-black uppercase tracking-wide text-gray-500 dark:text-slate-400">{{ __('messages.product_form_specs_preview') }}</h3>
                <div class="min-h-[8rem] rounded-xl border border-gray-200 bg-gray-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/60">
                    <template x-if="previewSpecs().length">
                        <dl class="divide-y divide-gray-100 dark:divide-slate-800">
                            <template x-for="(row, i) in previewSpecs()" :key="i">
                                <div class="grid grid-cols-1 gap-x-3 gap-y-0.5 py-2 sm:grid-cols-[minmax(0,9rem)_minmax(0,1fr)] sm:items-start">
                                    <dt class="break-words text-xs font-bold text-gray-500 dark:text-slate-400" x-text="row.label"></dt>
                                    <dd class="min-w-0 break-words text-xs font-medium text-gray-800 dark:text-slate-200" x-text="row.value"></dd>
                                </div>
                            </template>
                        </dl>
                    </template>
                    <template x-if="!previewSpecs().length">
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('messages.specs_empty') }}</p>
                    </template>
                </div>
            </div>

            {{-- Return Policy preview — mirrors the storefront's collapsible notice.
                 Server-rendered initial value; Alpine swaps in the live text on
                 input via refreshReturnPolicyPreview(). --}}
            <div class="min-w-0">
                <h3 class="mb-2 text-xs font-black uppercase tracking-wide text-gray-500 dark:text-slate-400">{{ __('messages.return_policy') }}</h3>
                <div class="min-h-[3.5rem] rounded-xl border border-gray-200 bg-gray-50/80 p-3 dark:border-slate-700 dark:bg-slate-900/60">
                    <p class="text-sm text-gray-800 dark:text-slate-100 whitespace-pre-line" x-show="returnPolicyPreview === null">{{ old('return_policy', $product->return_policy) ?: __('messages.specs_empty') }}</p>
                    <p class="text-sm text-gray-800 dark:text-slate-100 whitespace-pre-line" x-show="returnPolicyPreview !== null" x-text="returnPolicyPreview"></p>
                </div>
            </div>
        </div>
    </section>

    <section class="{{ $section }}">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
            <div>
                <h2 class="{{ $sectionTitle }}">{{ __('messages.product_form_variants_section') }} <span class="text-sm font-semibold text-gray-400">({{ __('messages.product_form_variants_optional') }})</span></h2>
                <p class="{{ $sectionHint }}">{{ __('messages.product_form_variants_hint') }}</p>
                @error('variants')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <button type="button" @click="addVariant()" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl border border-violet-300 bg-violet-50 px-4 py-2 text-xs font-black text-violet-700 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                {{ __('messages.product_form_add_variant') }}
            </button>
        </div>

        <div class="mt-4 rounded-xl border border-violet-100 bg-violet-50/70 p-3 dark:border-violet-900/60 dark:bg-violet-950/20 sm:p-4">
            <div class="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <h3 class="text-sm font-black text-violet-900 dark:text-violet-200">{{ __('messages.product_form_variant_preset_card') }}</h3>
                <a href="{{ route('store.admin.variant-presets.index', ['store_slug' => $store->slug]) }}" class="text-xs font-bold text-violet-700 hover:underline dark:text-violet-300">{{ __('messages.product_form_open_variant_settings') }}</a>
            </div>

            @if (($variantPresets ?? collect())->isNotEmpty())
                <div class="grid grid-cols-1 items-end gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto]">
                    <div>
                        <label class="text-xs font-bold uppercase text-violet-700 dark:text-violet-300">{{ __('messages.product_form_preset_1') }}</label>
                        <select x-model="selectedVariantPresetId" class="mt-1 w-full cursor-pointer rounded-xl border border-violet-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-violet-800 dark:bg-slate-900 dark:text-slate-100">
                            <option value="">{{ __('messages.product_form_choose_preset') }}</option>
                            <template x-for="preset in filteredVariantPresets" :key="preset.id">
                                <option :value="preset.id" x-text="preset.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase text-violet-700 dark:text-violet-300" data-test-label="Preset 2 (optional)">{{ __('messages.product_form_preset_2') }}</label>
                        <select x-model="selectedVariantPresetIdTwo" class="mt-1 w-full cursor-pointer rounded-xl border border-violet-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-violet-800 dark:bg-slate-900 dark:text-slate-100">
                            <option value="">{{ __('messages.product_form_combine_with') }}</option>
                            <template x-for="preset in filteredVariantPresets" :key="preset.id">
                                <option :value="preset.id" x-text="preset.name"></option>
                            </template>
                        </select>
                    </div>

                    <button type="button" @click="applyVariantPreset()" :disabled="!selectedVariantPresetId" data-test-label="Apply Preset" class="min-h-10 rounded-xl bg-violet-600 px-4 py-2 text-xs font-black text-white shadow hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ __('messages.product_form_apply_preset') }}
                    </button>

                    <button type="button" @click="applyVariantPresetCombination()" :disabled="!selectedVariantPresetId || !selectedVariantPresetIdTwo || selectedVariantPresetId === selectedVariantPresetIdTwo" data-test-label="Generate Combinations" class="min-h-10 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-black text-white shadow hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ __('messages.product_form_generate_combinations') }}
                    </button>
                </div>
                <p class="{{ $hint }}" x-show="selectedCategoryName">
                    {{ __('messages.product_form_category_preset_hint') }}
                    <span class="font-bold text-violet-700 dark:text-violet-300" x-text="filteredVariantPresets.length"></span>
                </p>
                <p class="{{ $hint }}">{{ __('messages.product_form_preset_hint') }}</p>
            @else
                <p class="text-xs font-semibold leading-5 text-gray-600 dark:text-slate-300">{{ __('messages.product_form_no_presets') }}</p>
            @endif
        </div>

        <div class="mt-4 space-y-3">
            <template x-for="(v, i) in variants" :key="i">
                <div class="rounded-xl bg-gray-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-violet-600 px-3 py-1 text-xs font-black text-white" x-text="'{{ __('messages.product_form_variant_label') }} ' + (i + 1)"></span>
                                <template x-for="(attr, ai) in (v.attributes || [])" :key="ai">
                                    <span class="max-w-full rounded-full border border-violet-300 bg-violet-50 px-2 py-0.5 text-xs font-bold text-violet-700 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300" x-text="attr.label + ': ' + attr.value"></span>
                                </template>
                            </div>
                        </div>
                        <button type="button" @click="removeVariant(i)" class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">{{ __('messages.product_form_remove_variant') }}</button>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 dark:text-slate-400">{{ __('messages.product_form_variant_name') }} *</label>
                            <input type="text" x-model="v.name" :name="'variants[' + i + '][name]'" required class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ __('messages.product_form_variant_name_placeholder') }}" />
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 dark:text-slate-400">{{ __('messages.product_form_sku') }}</label>
                            <input type="text" x-model="v.sku" :name="'variants[' + i + '][sku]'" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ __('messages.product_form_variant_sku_placeholder') }}" />
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 dark:text-slate-400">{{ __('messages.product_form_variant_stock') }}</label>
                            <select x-model="v.stock_status" :name="'variants[' + i + '][stock_status]'" class="mt-1 w-full cursor-pointer rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                                <option value="in_stock">{{ __('messages.in_stock') }}</option>
                                <option value="out_of_stock">{{ __('messages.out_of_stock') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 dark:text-slate-400">{{ __('messages.product_form_variant_retail_price') }} *</label>
                            <input type="number" step="0.01" min="0" x-model="v.retail_price" :name="'variants[' + i + '][retail_price]'" required class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 dark:text-slate-400">{{ __('messages.product_form_variant_wholesale_price') }}</label>
                            <input type="number" step="0.01" min="0" x-model="v.wholesale_price" :name="'variants[' + i + '][wholesale_price]'" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                        </div>

                        <div class="flex items-end pb-1">
                            <label class="inline-flex cursor-pointer select-none items-center gap-2 text-xs font-bold text-gray-700 dark:text-slate-300">
                                <input type="checkbox" x-model="v.is_default" :name="'variants[' + i + '][is_default]'" value="1" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                                {{ __('messages.product_form_default_variant') }}
                            </label>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 items-end gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 dark:text-slate-400">{{ __('messages.product_form_variant_image') }}</label>
                            <input type="file" accept="image/*" @change="previewVariantImage($event, i)" :name="'variants[' + i + '][image]'" class="mt-1 block w-full text-xs text-gray-600 dark:text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-slate-700 dark:file:text-violet-300" />
                            <p class="{{ $hint }}">{{ __('messages.product_form_variant_image_hint') }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <img x-show="v.image_preview" :src="v.image_preview" class="h-14 w-14 rounded-lg border border-gray-300 object-cover dark:border-slate-600" />
                            <template x-if="v.image_path && !v.image_preview">
                                <img :src="'/storage/' + v.image_path" class="h-14 w-14 rounded-lg border border-gray-300 object-cover dark:border-slate-600" />
                            </template>
                            <label x-show="v.image_path && !v.image_preview" class="inline-flex cursor-pointer select-none items-center gap-1 text-xs font-bold text-rose-600">
                                <input type="checkbox" x-model="v.remove_image" :name="'variants[' + i + '][remove_image]'" value="1" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500" />
                                {{ __('messages.product_form_remove_image') }}
                            </label>
                        </div>
                    </div>

                    <template x-for="(attr, ai) in (v.attributes || [])" :key="ai">
                        <span class="hidden">
                            <input type="hidden" :name="'variants[' + i + '][attributes][' + ai + '][label]'" :value="attr.label" />
                            <input type="hidden" :name="'variants[' + i + '][attributes][' + ai + '][value]'" :value="attr.value" />
                        </span>
                    </template>
                    <input type="hidden" x-model="v.id" :name="'variants[' + i + '][id]'" :value="v.id" />
                </div>
            </template>
        </div>
    </section>

    <div class="sticky bottom-0 z-10 -mx-4 border-t border-gray-200 bg-white/95 px-4 py-4 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur dark:border-slate-700 dark:bg-slate-900/95 sm:-mx-5 sm:px-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <label class="inline-flex cursor-pointer select-none items-center gap-2.5">
                <input type="checkbox" name="is_featured" value="1" id="is_featured" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                <span class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ __('messages.product_form_featured') }}</span>
            </label>

            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-violet-600 px-6 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-violet-700">
                {{ $isEdit ? __('messages.product_form_update_product') : __('messages.product_form_save_product') }}
            </button>
        </div>
    </div>
</div>
