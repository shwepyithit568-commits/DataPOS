@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $listUrl = function (array $params = []) use ($storeSlug) {
        // No view override: the catalog page defaults to grid view.
        if ($storeSlug) {
            $params['store_slug'] = $storeSlug;
        }
        return url('/products?' . http_build_query($params));
    };
    $activeMainId = $browseRows->first()?->category->id ?? '';
@endphp

{{-- Page title --}}
<div class="mb-3 sm:mb-5 flex items-center gap-2.5">
    <span class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-br from-sky-500 to-violet-600 text-white flex items-center justify-center text-lg sm:text-xl shadow-md shadow-sky-500/25" aria-hidden="true">🗂️</span>
    <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white font-outfit leading-tight">{{ __('messages.browse_categories') }}</h1>
</div>

<div class="-mx-1 sm:mx-0 rounded-none overflow-hidden" x-data="{ active: '{{ $activeMainId }}', ids: {{ Js::from($browseRows->pluck('category.id')->map(fn ($id) => (string) $id)->values()->all()) }}, select(i) { if (!this.ids[i]) return; this.active = this.ids[i]; this.$nextTick(() => { const btn = this.$refs.rail?.querySelector('[aria-pressed=&quot;true&quot;]'); if (btn) { btn.focus(); btn.scrollIntoView({ block: 'nearest' }); } }); }, move(dir) { if (this.ids.length < 2) return; const i = this.ids.indexOf(this.active); this.select((i + dir + this.ids.length) % this.ids.length); } }">
    {{-- Mobile / tablet: category card selector (single horizontal scroll row) --}}
    <div class="lg:hidden border-b border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900">
        <div class="flex gap-2 overflow-x-auto scrollbar-thin snap-x snap-mandatory p-2">
            @forelse ($browseRows as $row)
                @php $main = $row->category; @endphp
                <button
                    type="button"
                    @click="active = '{{ $main->id }}'"
                    class="shrink-0 snap-start flex items-center gap-2 rounded-2xl pl-1.5 pr-3 py-1.5 transition select-none active:scale-95"
                    :class="active === '{{ $main->id }}' ? 'border-sky-500 bg-sky-50 dark:border-sky-400 dark:bg-slate-800' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800'"
                    :aria-pressed="active === '{{ $main->id }}'"
                >
                    <span class="w-10 h-10 shrink-0 rounded-lg overflow-hidden bg-sky-100 dark:bg-slate-700 flex items-center justify-center text-lg" aria-hidden="true">
                        @if ($main->image_path)
                            <img src="{{ asset('storage/' . $main->image_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                            <span class="hidden w-full h-full items-center justify-center">{{ $main->icon ?: '📦' }}</span>
                        @else
                            {{ $main->icon ?: '📦' }}
                        @endif
                    </span>
                    <span class="min-w-0 font-black text-[13px] leading-tight truncate max-w-[8rem] text-slate-800 dark:text-slate-100">{{ $main->name }}</span>
                </button>
            @empty
                <div class="px-4 py-6 text-center text-xs font-bold text-slate-600 dark:text-slate-500">{{ __('messages.no_categories') }}</div>
            @endforelse
        </div>
    </div>

    <div class="lg:flex lg:h-[72vh] lg:min-h-[500px]">
        {{-- Desktop rail: main categories (vertical scroll) --}}
        <nav class="hidden lg:block w-56 shrink-0 border-r border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-slate-900 overflow-y-auto scrollbar-thin py-2" x-ref="rail" aria-label="{{ __('messages.browse_categories') }}" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.home.prevent="select(0)" @keydown.end.prevent="select(ids.length - 1)">
            @forelse ($browseRows as $row)
                @php $main = $row->category; @endphp
                <button
                    type="button"
                    @click="active = '{{ $main->id }}'"
                    @focus="active = '{{ $main->id }}'"
                    class="w-full flex flex-col items-center gap-1 px-2 py-4 transition border-l-[3px]"
                    :class="active === '{{ $main->id }}' ? 'bg-slate-50 dark:bg-slate-800/60 text-sky-700 dark:text-sky-400 border-sky-600 shadow-sm' : 'border-transparent text-slate-500 dark:text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/60'"
                    :aria-pressed="active === '{{ $main->id }}'"
                >
                    <span class="w-11 h-11 rounded-xl overflow-hidden bg-sky-100 dark:bg-slate-700 flex items-center justify-center text-xl shadow-sm" aria-hidden="true">
                        @if ($main->image_path)
                            <img src="{{ asset('storage/' . $main->image_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                            <span class="hidden w-full h-full items-center justify-center">{{ $main->icon ?: '📦' }}</span>
                        @else
                            {{ $main->icon ?: '📦' }}
                        @endif
                    </span>
                    <span class="font-black text-[13px] leading-tight truncate w-full text-center">{{ $main->name }}</span>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-black leading-none" :class="active === '{{ $main->id }}' ? 'bg-sky-100 text-sky-700 dark:bg-slate-800 dark:text-sky-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'">{{ number_format($row->total) }}</span>
                </button>
            @empty
                <div class="p-4 text-xs font-bold text-slate-600 dark:text-slate-500">{{ __('messages.no_categories') }}</div>
            @endforelse
        </nav>

        {{-- Right panel: brands strip + sub-categories of the selected main --}}
        <div class="flex-1 min-w-0 bg-white dark:bg-slate-900 lg:overflow-y-auto scrollbar-thin">
            @forelse ($browseRows as $row)
                @php $main = $row->category; @endphp
                <div x-show="active === '{{ $main->id }}'" x-cloak class="p-2 sm:p-5 lg:p-6 space-y-3.5 sm:space-y-5">
                    {{-- Panel header (sticky on desktop so it stays visible while scrolling the panel) --}}
                    <div class="flex items-center gap-2.5 sm:gap-3 lg:sticky lg:top-0 lg:z-10 lg:-mx-6 lg:px-6 lg:py-3 lg:bg-white/95 lg:backdrop-blur lg:border-b lg:border-slate-200/70 dark:lg:bg-slate-900/95 dark:lg:border-slate-800/70">
                        <span class="w-10 h-10 sm:w-12 sm:h-12 shrink-0 rounded-xl sm:rounded-2xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white flex items-center justify-center text-lg sm:text-2xl shadow-md shadow-sky-500/20" aria-hidden="true">{{ $main->icon ?: '📦' }}</span>
                        <div class="min-w-0">
                            <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white font-outfit leading-tight truncate">{{ $main->name }}</h2>
                            <p class="text-xs sm:text-sm font-bold text-slate-600 dark:text-slate-500">{{ number_format($row->total) }} {{ __('messages.products') }}</p>
                        </div>
                        <a href="{{ $listUrl(['category_id' => $main->id]) }}" class="ml-auto shrink-0 inline-flex items-center gap-1 px-2.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white text-[11px] sm:text-sm font-extrabold shadow-md shadow-sky-500/20 hover:brightness-110 active:scale-95 transition">
                            👀 <span class="whitespace-nowrap">{{ __('messages.view_all_products') }}</span>
                        </a>
                    </div>

                    {{-- Brands strip --}}
                    @if ($row->brands->isNotEmpty())
                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800">
                            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-500 mb-2 flex items-center gap-1">
                                <span aria-hidden="true">🏷️</span> {{ __('messages.brands') }}
                            </h2>
                            <div class="flex gap-2 sm:gap-2.5 overflow-x-auto scrollbar-thin pb-1 -mx-1 px-1 snap-x snap-mandatory">
                                @foreach ($row->brands as $brandRow)
                                    @php $brand = $brandRow['brand']; @endphp
                                    <a href="{{ $listUrl(['brand_id' => $brand->id]) }}"
                                       class="shrink-0 snap-start flex items-center gap-2 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 pl-1.5 pr-3 py-1.5 transition select-none active:scale-95 hover:border-sky-400 dark:hover:border-sky-500/60 hover:shadow-sm">
                                        <span class="w-10 h-10 shrink-0 rounded-lg overflow-hidden bg-sky-100 dark:bg-slate-700 flex items-center justify-center text-lg" aria-hidden="true">
                                            @if ($brand->logo_path)
                                                <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                                <span class="hidden w-full h-full items-center justify-center text-xs font-black">{{ mb_substr($brand->name, 0, 1) }}</span>
                                            @else
                                                <span class="text-xs font-black">{{ mb_substr($brand->name, 0, 1) }}</span>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block font-black text-[13px] leading-tight truncate max-w-[7rem] text-slate-800 dark:text-slate-100">{{ $brand->name }}</span>
                                            <span class="block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ number_format($brandRow['count']) }} {{ __('messages.products') }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Sub categories — glued hairline grid, tap → 1-column product list --}}
                    @if ($row->children->isNotEmpty())
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-500 mb-2 flex items-center gap-1">
                                <span aria-hidden="true">📂</span> {{ __('messages.sub_categories') }}
                            </h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-px bg-slate-200 dark:bg-slate-800 rounded-none overflow-hidden">
                                @foreach ($row->children as $sub)
                                    <a href="{{ $listUrl(['category_id' => $sub->id]) }}"
                                       class="bg-white dark:bg-slate-900 p-3 md:p-4 flex items-center gap-2.5 min-h-[56px] hover:bg-sky-50 dark:hover:bg-slate-800 transition group active:bg-sky-50">
                                        <span class="w-8 h-8 md:w-9 md:h-9 shrink-0 rounded-lg bg-sky-100 dark:bg-slate-800 flex items-center justify-center text-base group-hover:scale-110 transition" aria-hidden="true">📁</span>
                                        <span class="min-w-0">
                                            <span class="block text-xs md:text-[13px] font-extrabold text-slate-800 dark:text-slate-100 leading-tight truncate">{{ $sub->name }}</span>
                                            <span class="block text-xs font-bold text-slate-600 dark:text-slate-500">{{ number_format($sub->products_count) }} {{ __('messages.products') }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Fallback when a main category has no subs/brands of its own --}}
                    @if ($row->children->isEmpty() && $row->brands->isEmpty())
                        <a href="{{ $listUrl(['category_id' => $main->id]) }}" class="block p-4 rounded-2xl bg-sky-50 dark:bg-slate-800 text-center text-xs font-extrabold text-sky-700 dark:text-sky-300 transition active:scale-[0.98]">
                            👀 {{ __('messages.view_all_products') }}
                        </a>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center justify-center p-10 text-center">
                    <span class="text-4xl mb-2" aria-hidden="true">🗂️</span>
                    <p class="text-sm font-extrabold text-slate-600 dark:text-slate-400">{{ __('messages.no_categories') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
