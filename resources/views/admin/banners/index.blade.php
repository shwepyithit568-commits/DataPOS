@extends('layouts.admin.app')

@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Banners</h1>
            <p class="admin-page-sub">{{ $store->name }} · {{ number_format($banners->count()) }} {{ Str::plural('banner', $banners->count()) }}</p>
        </div>
    </div>

    {{-- Page Tabs --}}
    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl p-1 border border-slate-200 dark:border-slate-700 self-start">
        <a href="{{ url('/store/' . $store->slug . '/admin/banners?page=home') }}" 
            class="px-4 py-2 rounded-lg text-xs font-extrabold transition-all duration-200 {{ $currentPage === 'home' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-sm border border-slate-200 dark:border-slate-600' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            🏠 Home Page
        </a>
        <a href="{{ url('/store/' . $store->slug . '/admin/banners?page=glass_finder') }}" 
            class="px-4 py-2 rounded-lg text-xs font-extrabold transition-all duration-200 {{ $currentPage === 'glass_finder' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-sm border border-slate-200 dark:border-slate-600' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            🔍 Glass Finder
        </a>
    </div>

    {{-- Success Flash --}}
    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Error Flash --}}
    @if (isset($errors) && $errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1">
            <div class="flex items-center gap-2 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-6">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Reusable Toolbar (count display only; search/sort are UI affordances, no backend change) --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="Search banners..."
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => 'Newest',
            'oldest' => 'Oldest'
        ]"
        :showViewToggle="false"
        :showExportImport="false"
        :totalCount="$banners->count()"
    />

    {{-- Banners / Add New Banner (tabbed) --}}
    <div x-data="{ tab: 'list' }" class="admin-panel overflow-hidden transition-colors duration-200">
        {{-- Tab bar --}}
        <div class="flex border-b dark:border-slate-700 bg-gray-50/60 dark:bg-slate-900/40" role="tablist">
            <button type="button" role="tab" :aria-selected="tab === 'list'" @click="tab = 'list'"
                class="flex-1 sm:flex-none sm:px-6 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'list' ? 'border-purple-600 text-purple-700 dark:text-purple-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xs sm:text-sm shrink-0">🖼️</span>
                <span class="truncate">Banners</span>
                <span class="shrink-0 px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-xs font-bold text-gray-600 dark:text-slate-300">{{ number_format($banners->count()) }}</span>
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'add'" @click="tab = 'add'"
                class="flex-1 sm:flex-none sm:px-6 py-3 text-xs sm:text-sm sm:text-base font-semibold flex items-center justify-center gap-1.5 sm:gap-2 transition border-b-2 -mb-px min-w-0"
                :class="tab === 'add' ? 'border-purple-600 text-purple-700 dark:text-purple-300 bg-white dark:bg-slate-800' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800'">
                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">+</span>
                <span class="truncate">Add New Banner</span>
            </button>
        </div>

        {{-- Banners list tab panel --}}
        <div x-show="tab === 'list'" x-cloak x-transition>
            {{-- Banners Grid — Mobile: 1 col, Tablet: 2 cols, Desktop: 3 cols --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 p-4 sm:p-5">
                @forelse ($banners as $banner)
                    <div class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden flex flex-col transition-colors duration-200 hover:shadow-md">
                        {{-- Image preview with hover zoom (responsive fixed height — brand-page pattern) --}}
                        <div class="relative group bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 overflow-hidden">
                            <div class="h-36 sm:h-40 w-full overflow-hidden">
                                <img src="{{ asset('storage/' . $banner->image_path) }}" alt="{{ $banner->title }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                            </div>
                            {{-- Active / inactive status badge --}}
                            <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-bold backdrop-blur {{ $banner->is_active ? 'bg-green-600/85 text-white' : 'bg-gray-600/85 text-white' }}">
                                {{ $banner->is_active ? '● Active' : '○ Hidden' }}
                            </span>
                            {{-- Sort order badge --}}
                            <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-black/55 dark:bg-black/70 text-white text-xs font-bold backdrop-blur">
                                #{{ (int) $banner->sort_order }}
                            </span>
                        </div>

                        {{-- Body --}}
                        <div class="p-3 sm:p-4 flex-1 flex flex-col gap-1.5">
                            <div class="font-bold text-gray-900 dark:text-slate-100 text-sm truncate" title="{{ $banner->title }}">{{ $banner->title }}</div>
                            @if ($banner->description)
                                <p class="text-xs text-gray-500 dark:text-slate-400 line-clamp-2">{{ $banner->description }}</p>
                            @endif
                            @if ($banner->link_url)
                                <a href="{{ $banner->link_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-violet-600 dark:text-violet-400 hover:underline truncate" title="{{ $banner->link_url }}">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656M10.5 6H17a4 4 0 014 4v7a4 4 0 01-4 4H7a4 4 0 01-4-4v-3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l8-8m0 0h-5m5 0v5"/></svg>
                                    <span class="truncate">{{ $banner->link_url }}</span>
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656"/></svg>
                                    <span>No link</span>
                                </span>
                            @endif
                        </div>

                        {{-- Footer Actions --}}
                        <div class="px-3 sm:px-4 py-2.5 border-t dark:border-slate-700 flex items-center gap-2">
                            <a href="{{ url('/store/' . $store->slug . '/admin/banners/' . $banner->id . '/edit') }}" class="flex-1 text-center py-1.5 px-3 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/60 font-semibold text-xs border border-violet-200 dark:border-violet-800 whitespace-nowrap flex items-center justify-center gap-1">
                                ✏ Edit
                            </a>
                            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/banners/' . $banner->id) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" data-confirm="Delete this banner? The image will be removed." class="w-full py-1.5 px-3 rounded-lg bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/60 font-semibold text-xs border border-red-200 dark:border-red-800 whitespace-nowrap flex items-center justify-center gap-1">
                                    <span>🗑</span><span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-slate-800 p-8 rounded-xl text-center">
                        <div class="text-4xl mb-3 opacity-40">🖼️</div>
                        <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">No banners uploaded yet</div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">Add your first {{ $currentPage === 'glass_finder' ? 'Glass Finder' : '' }} banner using the form above.</div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Add New Banner tab panel --}}
        <div x-show="tab === 'add'" x-cloak x-transition>
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/banners') }}" enctype="multipart/form-data" class="p-4 sm:p-5 space-y-3.5">
                @csrf
                <input type="hidden" name="page" value="{{ $currentPage }}" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Summer Sale 2026" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Description (Optional)</label>
                        <textarea name="description" rows="2" maxlength="500" placeholder="Banner အောက်တွင် ပြမည့် အကျဉ်းချုပ်စာသား" class="w-full resize-y border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Link URL (Optional)</label>
                        <input type="text" name="link_url" value="{{ old('link_url') }}" placeholder="https://example.com သို့မဟုတ် /store/datapos-mobile/products" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Banner Image <span class="text-rose-500">*</span></label>
                        <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/webp" required class="block w-full text-sm text-gray-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 dark:file:bg-slate-700 file:text-purple-700 dark:file:text-purple-300 hover:file:bg-purple-100 cursor-pointer" />
                        <p class="mt-1 text-xs font-semibold text-purple-600 dark:text-purple-300">Recommended: 1920×900 px WebP/JPG · keep products and important visuals near the center.</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">PNG, JPG, or WebP · 10MB max · 16:9 recommended</p>
                    </div>
                </div>
                {{-- is_active is validated/stored by the controller (default true); offer a toggle on create --}}
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="hidden" name="is_active" value="0" />
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-purple-600 focus:ring-purple-500" />
                    <span class="text-xs font-semibold text-gray-700 dark:text-slate-300">Active (show on storefront)</span>
                </label>
                <div>
                    <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold text-sm shadow flex items-center justify-center gap-2 transition">
                        <span>⬆</span><span>Upload Banner</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
