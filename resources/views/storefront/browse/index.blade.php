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
    {{-- ═══════════════════════════════════════════════
         Page Header — 3D Clean Header (No Hardcoded Gradients)
    ═══════════════════════════════════════════════ --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-6 border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="space-y-1">
            {{-- 3D Primary Badge — uses CSS token (Admin-driven, no hardcoded hex) --}}
            <div class="sf-btn-3d active inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase pointer-events-none">
                <span aria-hidden="true">🗂️</span>
                <span>{{ __('messages.browse_categories') }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white font-sans">
                {{ __('messages.browse_categories') }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                {{ __('messages.browse_categories_hint') }}
            </p>
        </div>

        {{-- View All Products — 3D push button --}}
        <a href="{{ $listUrl() }}"
           class="sf-btn-3d self-start sm:self-auto !inline-flex min-h-[38px] items-center gap-1.5 px-4 py-2 rounded-full text-xs font-black">
            <span aria-hidden="true">🛍️</span>
            <span>{{ __('messages.view_all_products') }}</span>
        </a>
    </div>

    {{-- ═══════════════════════════════════════════════
         Main Browse Container
    ═══════════════════════════════════════════════ --}}
    <div class="rounded-3xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm"
         x-data="{ active: '{{ $activeMainId }}', ids: {{ Js::from($browseRows->pluck('category.id')->map(fn ($id) => (string) $id)->values()->all()) }}, select(i) { if (!this.ids[i]) return; this.active = this.ids[i]; this.$nextTick(() => { const btn = this.$refs.rail?.querySelector('[aria-pressed=&quot;true&quot;]'); if (btn) { btn.focus(); btn.scrollIntoView({ block: 'nearest' }); } }); }, move(dir) { if (this.ids.length < 2) return; const i = this.ids.indexOf(this.active); this.select((i + dir + this.ids.length) % this.ids.length); } }">

        {{-- ══════════════════════════════════
             Mobile / Tablet Category Selector (Horizontal Scroll)
             — 3D Tactile Push Pills
        ══════════════════════════════════ --}}
        <div class="lg:hidden border-b border-slate-200/80 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50 p-2 sm:p-3">
            <div class="flex gap-2 overflow-x-auto scrollbar-thin snap-x snap-mandatory py-1">
                @forelse ($browseRows as $row)
                    @php $main = $row->category; @endphp
                    <button
                        type="button"
                        @click="active = '{{ $main->id }}'"
                        class="sf-btn-3d shrink-0 snap-start !flex-row items-center gap-2 px-3 py-2 rounded-2xl select-none text-xs font-black transition"
                        :class="active === '{{ $main->id }}' ? 'active' : ''"
                        :aria-pressed="active === '{{ $main->id }}'"
                    >
                        <span class="w-8 h-8 shrink-0 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-base" aria-hidden="true">
                            @if ($main->image_path)
                                <img src="{{ asset('storage/' . $main->image_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                <span class="hidden w-full h-full items-center justify-center">{{ $main->icon ?: '📦' }}</span>
                            @else
                                {{ $main->icon ?: '📦' }}
                            @endif
                        </span>
                        <span class="min-w-0 font-black text-xs leading-tight truncate max-w-[9rem]">{{ $main->name }}</span>
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-black bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400"
                              :class="active === '{{ $main->id }}' ? 'bg-white/30 !text-white' : ''">
                            {{ number_format($row->total) }}
                        </span>
                    </button>
                @empty
                    <div class="px-4 py-6 text-center text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('messages.no_categories') }}</div>
                @endforelse
            </div>
        </div>

        <div class="lg:flex lg:h-[72vh] lg:min-h-[540px]">
            {{-- ══════════════════════════════════
                 Desktop Left Rail (Vertical Scroll)
                 — 3D Tactile Push Buttons (sf-btn-3d with active state)
            ══════════════════════════════════ --}}
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
                        class="sf-btn-3d w-full !flex-row !justify-start items-center gap-3 px-3 py-3 rounded-2xl text-left group transition"
                        :class="active === '{{ $main->id }}' ? 'active' : ''"
                        :aria-pressed="active === '{{ $main->id }}'"
                    >
                        <span class="w-10 h-10 shrink-0 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-lg shadow-2xs group-hover:scale-105 transition" aria-hidden="true">
                            @if ($main->image_path)
                                <img src="{{ asset('storage/' . $main->image_path) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                <span class="hidden w-full h-full items-center justify-center">{{ $main->icon ?: '📦' }}</span>
                            @else
                                {{ $main->icon ?: '📦' }}
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <span class="block font-black text-xs leading-snug truncate">{{ $main->name }}</span>
                            <span class="block text-[11px] font-bold opacity-60">{{ number_format($row->total) }} {{ __('messages.products') }}</span>
                        </div>
                        <span class="w-2 h-2 rounded-full shrink-0 transition opacity-0 group-hover:opacity-60"
                              :class="active === '{{ $main->id }}' ? '!opacity-100 bg-white' : 'bg-slate-300 dark:bg-slate-600'"></span>
                    </button>
                @empty
                    <div class="p-4 text-xs font-bold text-slate-500">{{ __('messages.no_categories') }}</div>
                @endforelse
            </nav>

            {{-- ══════════════════════════════════
                 Right Panel — Category Content Area
            ══════════════════════════════════ --}}
            <div class="flex-1 min-w-0 bg-white dark:bg-slate-900 lg:overflow-y-auto scrollbar-thin">
                @forelse ($browseRows as $row)
                    @php $main = $row->category; @endphp
                    <div x-show="active === '{{ $main->id }}'" x-cloak class="p-4 sm:p-6 lg:p-8 space-y-6">

                        {{-- ══ Panel Header (Sticky on Desktop, Zero backdrop-blur on mobile) ══ --}}
                        <div class="flex items-center gap-3 lg:sticky lg:top-0 lg:z-10 lg:-mx-8 lg:px-8 lg:py-3 lg:bg-white/98 lg:border-b lg:border-slate-200/80 dark:lg:bg-slate-900/98 dark:lg:border-slate-800">
                            {{-- Category Icon — uses CSS token gradient via sf-btn-3d active ring --}}
                            <span class="sf-btn-3d active w-12 h-12 shrink-0 rounded-2xl text-white flex items-center justify-center text-xl pointer-events-none" aria-hidden="true">{{ $main->icon ?: '📦' }}</span>
                            <div class="min-w-0">
                                <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white font-sans leading-tight truncate">{{ $main->name }}</h2>
                                <p class="text-xs font-bold text-slate-500 dark:text-slate-500">{{ number_format($row->total) }} {{ __('messages.products') }}</p>
                            </div>
                            {{-- View All — 3D Primary Push Button --}}
                            <a href="{{ $listUrl(['category_id' => $main->id]) }}"
                               class="sf-btn-3d-primary ml-auto shrink-0 !inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-2 rounded-xl text-xs font-black">
                                <span aria-hidden="true">👀</span>
                                <span class="whitespace-nowrap">{{ __('messages.view_all_products') }}</span>
                            </a>
                        </div>

                        {{-- ══════════════════════════════════
                             Brands Strip (Horizontal Scroll + Mouse Drag)
                             — 3D Soft Bevel Push Cards (border-b-[3px])
                        ══════════════════════════════════ --}}
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
                                        <span>{{ __('messages.brands') }}</span>
                                        <span class="text-[11px] font-bold text-slate-500 font-mono">({{ $row->brands->count() }})</span>
                                        <span class="hidden sm:inline-block text-[10px] text-slate-400 font-normal ml-2">🖱️ (Mouse ဖြင့် ဖိဆွဲနိုင်ပါသည်)</span>
                                    </h3>
                                    <div class="flex items-center gap-1">
                                        {{-- Scroll Left Arrow --}}
                                        <button type="button"
                                                @click="scrollBy(-240)"
                                                :disabled="!canScrollLeft"
                                                class="sf-btn-3d w-7 h-7 !rounded-full text-xs disabled:opacity-30 disabled:pointer-events-none cursor-pointer"
                                                aria-label="Scroll left">
                                            ←
                                        </button>
                                        {{-- Scroll Right Arrow --}}
                                        <button type="button"
                                                @click="scrollBy(240)"
                                                :disabled="!canScrollRight"
                                                class="sf-btn-3d w-7 h-7 !rounded-full text-xs disabled:opacity-30 disabled:pointer-events-none cursor-pointer"
                                                aria-label="Scroll right">
                                            →
                                        </button>
                                    </div>
                                </div>

                                {{-- Brand Cards Track — 3D Soft Bevel Cards --}}
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
                                           class="shrink-0 min-w-[140px] sm:min-w-[170px] flex items-center gap-2.5
                                                  rounded-2xl bg-white dark:bg-slate-800
                                                  border border-slate-200/90 dark:border-slate-700
                                                  border-b-[3px] border-b-slate-300 dark:border-b-slate-600
                                                  pl-2.5 pr-3.5 py-2
                                                  transition-all duration-150 transform
                                                  hover:-translate-y-0.5 hover:border-b-[4px] hover:shadow-md
                                                  active:translate-y-0.5 active:border-b-[1.5px]
                                                  select-none group">
                                            <span class="w-10 h-10 shrink-0 rounded-xl overflow-hidden bg-slate-50 dark:bg-slate-700 flex items-center justify-center text-sm shadow-2xs group-hover:scale-105 transition pointer-events-none" aria-hidden="true">
                                                @if ($brand->logo_path)
                                                    <img src="{{ asset('storage/' . $brand->logo_path) }}" alt="" class="w-full h-full object-cover pointer-events-none" loading="lazy" decoding="async" data-img-fallback="hide-next">
                                                    <span class="hidden w-full h-full items-center justify-center text-xs font-black">{{ mb_substr($brand->name, 0, 1) }}</span>
                                                @else
                                                    <span class="text-xs font-black text-slate-700 dark:text-slate-300">{{ mb_substr($brand->name, 0, 1) }}</span>
                                                @endif
                                            </span>
                                            <span class="min-w-0 flex-1 pointer-events-none">
                                                <span class="block font-black text-xs leading-tight truncate text-slate-800 dark:text-slate-100 group-hover:text-[color:var(--sf-primary)] transition">{{ $brand->name }}</span>
                                                <span class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-0.5">{{ number_format($brandRow['count']) }} {{ __('messages.products') }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- ══════════════════════════════════
                             Sub-categories Grid
                             — 3D Tactile Push Cards (border-b-[3px] bevel)
                        ══════════════════════════════════ --}}
                        @if ($row->children->isNotEmpty())
                            <div class="space-y-3">
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                                    <span aria-hidden="true">📂</span>
                                    <span>{{ __('messages.sub_categories') }}</span>
                                </h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                                    @foreach ($row->children as $sub)
                                        <a href="{{ $listUrl(['category_id' => $sub->id]) }}"
                                           class="rounded-2xl
                                                  border border-slate-200/90 dark:border-slate-800
                                                  border-b-[3px] border-b-slate-300 dark:border-b-slate-700
                                                  bg-white dark:bg-slate-800/60
                                                  p-3.5 flex items-center gap-3
                                                  transition-all duration-150 transform
                                                  hover:-translate-y-0.5 hover:border-b-[4px] hover:shadow-md
                                                  hover:border-[color:var(--sf-primary)]/40
                                                  active:translate-y-0.5 active:border-b-[1.5px]
                                                  group">
                                            <span class="w-9 h-9 shrink-0 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-sm group-hover:scale-110 transition shadow-2xs" aria-hidden="true">📁</span>
                                            <div class="min-w-0 flex-1">
                                                <span class="block text-xs font-black text-slate-800 dark:text-slate-100 leading-tight truncate group-hover:text-[color:var(--sf-primary)] transition">{{ $sub->name }}</span>
                                                <span class="block text-[11px] font-bold text-slate-500 dark:text-slate-500">{{ number_format($sub->products_count) }} {{ __('messages.products') }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- ══ Fallback: No subs / No brands — 3D Primary CTA ══ --}}
                        @if ($row->children->isEmpty() && $row->brands->isEmpty())
                            <div class="p-8 rounded-3xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 text-center space-y-4">
                                <span class="text-3xl" aria-hidden="true">📦</span>
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-400 font-myanmar">{{ __('messages.no_sub_categories_hint') }}</p>
                                <a href="{{ $listUrl(['category_id' => $main->id]) }}"
                                   class="sf-btn-3d-primary inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black mx-auto">
                                    <span aria-hidden="true">👀</span>
                                    {{ __('messages.view_all_products') }}
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
