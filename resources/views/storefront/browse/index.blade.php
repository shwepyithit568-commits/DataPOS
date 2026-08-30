@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $listUrl = function (array $params = []) use ($storeSlug) {
        if ($storeSlug) {
            $params['store_slug'] = $storeSlug;
        }
        return url('/products?' . http_build_query($params));
    };
    $activeMainId = $browseRows->first()?->category->id ?? '';
@endphp

<div class="max-w-6xl mx-auto space-y-4 sm:space-y-6 pb-12">
    {{-- Page Header --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="space-y-1">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black text-white uppercase shadow-2xs border-0"
                 style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;">
                <span>🗂️</span>
                <span>Category Explorer</span>
            </div>
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white font-sans">
                {{ __('messages.browse_categories') }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                မိမိလိုချင်သော ပစ္စည်း အမျိုးအစားနှင့် Brand အလိုက် အလွယ်တကူ ရှာဖွေနိုင်ပါသည်
            </p>
        </div>

        <a href="{{ $listUrl() }}" class="self-start sm:self-auto inline-flex min-h-[38px] items-center gap-1.5 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 px-4 py-2 text-xs font-black text-slate-800 dark:text-slate-200 transition active:scale-95 shadow-2xs">
            <span>🛍️</span>
            <span>{{ __('messages.view_all_products') }}</span>
        </a>
    </div>

    {{-- Main Browse Container --}}
    <div class="rounded-3xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm"
         x-data="{ active: '{{ $activeMainId }}', ids: {{ Js::from($browseRows->pluck('category.id')->map(fn ($id) => (string) $id)->values()->all()) }}, select(i) { if (!this.ids[i]) return; this.active = this.ids[i]; this.$nextTick(() => { const btn = this.$refs.rail?.querySelector('[aria-pressed=&quot;true&quot;]'); if (btn) { btn.focus(); btn.scrollIntoView({ block: 'nearest' }); } }); }, move(dir) { if (this.ids.length < 2) return; const i = this.ids.indexOf(this.active); this.select((i + dir + this.ids.length) % this.ids.length); } }">

        {{-- Mobile / Tablet Category Selector (Horizontal Scroll) --}}
        <div class="lg:hidden border-b border-slate-200/80 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 p-2 sm:p-3">
            <div class="flex gap-2 overflow-x-auto scrollbar-thin snap-x snap-mandatory py-1">
                @forelse ($browseRows as $row)
                    @php $main = $row->category; @endphp
                    <button
                        type="button"
                        @click="active = '{{ $main->id }}'"
                        class="shrink-0 snap-start flex items-center gap-2 rounded-2xl px-3 py-2 transition select-none active:scale-95 border"
                        :class="active === '{{ $main->id }}' ? 'border-sky-500 bg-sky-50 dark:border-sky-400 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 shadow-xs' : 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                        :aria-pressed="active === '{{ $main->id }}'"
                    >
                        <span class="w-8 h-8 shrink-0 rounded-xl overflow-hidden bg-sky-100 dark:bg-slate-700 flex items-center justify-center text-base" aria-hidden="true">
                            @if ($main->image_path)
                                <img src="{{ asset('storage/' . $main->image_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                <span class="hidden w-full h-full items-center justify-center">{{ $main->icon ?: '📦' }}</span>
                            @else
                                {{ $main->icon ?: '📦' }}
                            @endif
                        </span>
                        <span class="min-w-0 font-black text-xs leading-tight truncate max-w-[9rem]">{{ $main->name }}</span>
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-black"
                              :class="active === '{{ $main->id }}' ? 'bg-sky-200/80 text-sky-800 dark:bg-sky-900 dark:text-sky-200' : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'">
                            {{ number_format($row->total) }}
                        </span>
                    </button>
                @empty
                    <div class="px-4 py-6 text-center text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.no_categories') }}</div>
                @endforelse
            </div>
        </div>

        <div class="lg:flex lg:h-[72vh] lg:min-h-[540px]">
            {{-- Desktop Left Rail (Vertical Scroll) --}}
            <nav class="hidden lg:block w-64 shrink-0 border-r border-slate-200/80 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 overflow-y-auto scrollbar-thin py-3 space-y-1 px-2"
                 x-ref="rail"
                 aria-label="{{ __('messages.browse_categories') }}"
                 @keydown.arrow-down.prevent="move(1)"
                 @keydown.arrow-up.prevent="move(-1)"
                 @keydown.home.prevent="select(0)"
                 @keydown.end.prevent="select(ids.length - 1)">
                @forelse ($browseRows as $row)
                    @php $main = $row->category; @endphp
                    <button
                        type="button"
                        @click="active = '{{ $main->id }}'"
                        @focus="active = '{{ $main->id }}'"
                        class="w-full flex items-center gap-3 px-3 py-3 rounded-2xl transition border text-left group"
                        :class="active === '{{ $main->id }}' ? 'bg-white dark:bg-slate-800 text-sky-700 dark:text-sky-300 border-sky-400 dark:border-sky-600 shadow-sm' : 'border-transparent text-slate-600 dark:text-slate-400 hover:bg-white/80 dark:hover:bg-slate-800/60'"
                        :aria-pressed="active === '{{ $main->id }}'"
                    >
                        <span class="w-10 h-10 shrink-0 rounded-xl overflow-hidden bg-sky-100 dark:bg-slate-700 flex items-center justify-center text-lg shadow-2xs group-hover:scale-105 transition" aria-hidden="true">
                            @if ($main->image_path)
                                <img src="{{ asset('storage/' . $main->image_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                <span class="hidden w-full h-full items-center justify-center">{{ $main->icon ?: '📦' }}</span>
                            @else
                                {{ $main->icon ?: '📦' }}
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <span class="block font-black text-xs leading-snug truncate">{{ $main->name }}</span>
                            <span class="block text-[11px] font-bold text-slate-600 dark:text-slate-500">{{ number_format($row->total) }} {{ __('messages.products') }}</span>
                        </div>
                        <span class="w-2 h-2 rounded-full transition" :class="active === '{{ $main->id }}' ? 'bg-sky-500' : 'bg-transparent'"></span>
                    </button>
                @empty
                    <div class="p-4 text-xs font-bold text-slate-500">{{ __('messages.no_categories') }}</div>
                @endforelse
            </nav>

            {{-- Right Panel --}}
            <div class="flex-1 min-w-0 bg-white dark:bg-slate-900 lg:overflow-y-auto scrollbar-thin">
                @forelse ($browseRows as $row)
                    @php $main = $row->category; @endphp
                    <div x-show="active === '{{ $main->id }}'" x-cloak class="p-4 sm:p-6 lg:p-8 space-y-6">
                        {{-- Panel Header --}}
                        <div class="flex items-center gap-3 lg:sticky lg:top-0 lg:z-10 lg:-mx-8 lg:px-8 lg:py-3 lg:bg-white/95 lg:backdrop-blur lg:border-b lg:border-slate-200/80 dark:lg:bg-slate-900/95 dark:lg:border-slate-800">
                            <span class="w-12 h-12 shrink-0 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 text-white flex items-center justify-center text-xl shadow-md shadow-sky-500/20" aria-hidden="true">{{ $main->icon ?: '📦' }}</span>
                            <div class="min-w-0">
                                <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white font-sans leading-tight truncate">{{ $main->name }}</h2>
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-500">{{ number_format($row->total) }} {{ __('messages.products') }}</p>
                            </div>
                            <a href="{{ $listUrl(['category_id' => $main->id]) }}"
                               style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; color: #ffffff !important;"
                               class="ml-auto shrink-0 inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-black text-white shadow-md shadow-sky-500/20 hover:brightness-110 active:scale-95 transition border-0">
                                <span>👀</span>
                                <span class="whitespace-nowrap">{{ __('messages.view_all_products') }}</span>
                            </a>
                        </div>

                        {{-- Brands Strip (Horizontal Scroll + Mouse Drag for All Devices) --}}
                        @if ($row->brands->isNotEmpty())
                            <div class="p-4 sm:p-5 rounded-3xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800 space-y-3"
                                 x-data="{
                                     canScrollLeft: false,
                                     canScrollRight: true,
                                     isDown: false,
                                     startX: 0,
                                     scrollLeftPos: 0,
                                     dragDistance: 0,
                                     checkScroll() {
                                         const el = this.$refs.brandTrack;
                                         if (!el) return;
                                         this.canScrollLeft = el.scrollLeft > 10;
                                         this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 10);
                                     },
                                     scrollBy(amount) {
                                         this.$refs.brandTrack?.scrollBy({ left: amount, behavior: 'smooth' });
                                     },
                                     startDrag(e) {
                                         this.isDown = true;
                                         this.dragDistance = 0;
                                         this.startX = e.pageX - this.$refs.brandTrack.offsetLeft;
                                         this.scrollLeftPos = this.$refs.brandTrack.scrollLeft;
                                     },
                                     stopDrag() {
                                         this.isDown = false;
                                     },
                                     doDrag(e) {
                                         if (!this.isDown) return;
                                         e.preventDefault();
                                         const x = e.pageX - this.$refs.brandTrack.offsetLeft;
                                         const walk = (x - this.startX) * 1.5;
                                         this.dragDistance = Math.abs(walk);
                                         this.$refs.brandTrack.scrollLeft = this.scrollLeftPos - walk;
                                         this.checkScroll();
                                     },
                                     preventClickIfDragged(e) {
                                         if (this.dragDistance > 6) {
                                             e.preventDefault();
                                             e.stopPropagation();
                                         }
                                     }
                                 }"
                                 x-init="$nextTick(() => checkScroll())">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5 font-sans">
                                        <span aria-hidden="true">🏷️</span>
                                        <span>{{ __('messages.brands') }} · အမှတ်တံဆိပ်များ</span>
                                        <span class="text-[11px] font-bold text-slate-500 font-mono">({{ $row->brands->count() }})</span>
                                        <span class="hidden sm:inline-block text-[10px] text-slate-400 font-normal ml-2">🖱️ (Mouse ဖြင့် ဖိဆွဲနိုင်ပါသည်)</span>
                                    </h3>
                                    <div class="flex items-center gap-1">
                                        <button type="button"
                                                @click="scrollBy(-240)"
                                                :disabled="!canScrollLeft"
                                                class="w-7 h-7 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs transition hover:border-sky-400 disabled:opacity-30 disabled:pointer-events-none shadow-2xs cursor-pointer"
                                                aria-label="Scroll left">
                                            ←
                                        </button>
                                        <button type="button"
                                                @click="scrollBy(240)"
                                                :disabled="!canScrollRight"
                                                class="w-7 h-7 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs transition hover:border-sky-400 disabled:opacity-30 disabled:pointer-events-none shadow-2xs cursor-pointer"
                                                aria-label="Scroll right">
                                            →
                                        </button>
                                    </div>
                                </div>
                                <div class="flex gap-2.5 overflow-x-auto scrollbar-thin pb-2 pt-0.5 -mx-1 px-1 cursor-grab active:cursor-grabbing select-none"
                                     x-ref="brandTrack"
                                     @mousedown="startDrag($event)"
                                     @mouseleave="stopDrag()"
                                     @mouseup="stopDrag()"
                                     @mousemove="doDrag($event)"
                                     @scroll.debounce.50ms="checkScroll()">
                                    @foreach ($row->brands as $brandRow)
                                        @php $brand = $brandRow['brand']; @endphp
                                        <a href="{{ $listUrl(['brand_id' => $brand->id]) }}"
                                           @click="preventClickIfDragged($event)"
                                           class="shrink-0 min-w-[140px] sm:min-w-[170px] flex items-center gap-2.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700 pl-2.5 pr-3.5 py-2.5 transition select-none active:scale-95 hover:border-sky-400 dark:hover:border-sky-500 hover:shadow-md group">
                                            <span class="w-10 h-10 shrink-0 rounded-xl overflow-hidden bg-sky-50 dark:bg-slate-700 flex items-center justify-center text-sm shadow-2xs group-hover:scale-105 transition pointer-events-none" aria-hidden="true">
                                                @if ($brand->logo_path)
                                                    <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="" class="w-full h-full object-cover pointer-events-none" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                                    <span class="hidden w-full h-full items-center justify-center text-xs font-black">{{ mb_substr($brand->name, 0, 1) }}</span>
                                                @else
                                                    <span class="text-xs font-black text-slate-700 dark:text-slate-300">{{ mb_substr($brand->name, 0, 1) }}</span>
                                                @endif
                                            </span>
                                            <span class="min-w-0 flex-1 pointer-events-none">
                                                <span class="block font-black text-xs leading-tight truncate text-slate-800 dark:text-slate-100 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">{{ $brand->name }}</span>
                                                <span class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-0.5">{{ number_format($brandRow['count']) }} {{ __('messages.products') }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Sub-categories Grid --}}
                        @if ($row->children->isNotEmpty())
                            <div class="space-y-3">
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                                    <span aria-hidden="true">📂</span>
                                    <span>{{ __('messages.sub_categories') }}</span>
                                </h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                                    @foreach ($row->children as $sub)
                                        <a href="{{ $listUrl(['category_id' => $sub->id]) }}"
                                           class="rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 p-3.5 flex items-center gap-3 transition hover:border-sky-400 hover:bg-white dark:hover:bg-slate-800 hover:shadow-sm group active:scale-95">
                                            <span class="w-9 h-9 shrink-0 rounded-xl bg-sky-100 dark:bg-slate-700 text-sky-700 dark:text-sky-300 flex items-center justify-center text-sm group-hover:scale-110 transition shadow-2xs" aria-hidden="true">📁</span>
                                            <div class="min-w-0 flex-1">
                                                <span class="block text-xs font-black text-slate-800 dark:text-slate-100 leading-tight truncate group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">{{ $sub->name }}</span>
                                                <span class="block text-[11px] font-bold text-slate-600 dark:text-slate-500">{{ number_format($sub->products_count) }} {{ __('messages.products') }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Fallback when a main category has no subs/brands --}}
                        @if ($row->children->isEmpty() && $row->brands->isEmpty())
                            <div class="p-8 rounded-3xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 text-center space-y-3">
                                <span class="text-3xl">📦</span>
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-400 font-myanmar">ဤ အမျိုးအစားအတွက် သီးသန့် Sub-category မရှိသေးပါ</p>
                                <a href="{{ $listUrl(['category_id' => $main->id]) }}"
                                   style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; color: #ffffff !important;"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black text-white shadow-md shadow-sky-500/20 hover:brightness-110 active:scale-95 transition border-0">
                                    👀 {{ __('messages.view_all_products') }}
                                </a>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center p-12 text-center space-y-2">
                        <span class="text-4xl" aria-hidden="true">🗂️</span>
                        <p class="text-sm font-black text-slate-700 dark:text-slate-300">{{ __('messages.no_categories') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

