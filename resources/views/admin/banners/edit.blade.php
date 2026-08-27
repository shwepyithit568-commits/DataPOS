@extends('layouts.admin.app')

@section('title', __('messages.banners_edit') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5 max-w-4xl mx-auto">
    {{-- Header --}}
    <header class="w-full flex items-center justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center text-sm sm:text-base font-black shrink-0">
                ✏️
            </div>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight truncate">
                    {{ __('messages.banners_edit') }}
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ $store->name }} · {{ $banner->title }}
                </p>
            </div>
        </div>
        <a href="{{ $returnTo ?? route('store.admin.banners.index', ['store_slug' => $store->slug, 'page' => $banner->page]) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1 active:scale-95">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            <span>{{ __('messages.back') }}</span>
        </a>
    </header>

    {{-- Validation Errors --}}
    @if(isset($errors) && $errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            @foreach($errors->all() as $e)
                <div class="flex items-center gap-1.5 font-bold"><span>⚠️</span><span>{{ $e }}</span></div>
            @endforeach
        </div>
    @endif

    {{-- Edit Form --}}
    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/banners/' . $banner->id) }}" enctype="multipart/form-data"
          class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-3 sm:p-4 space-y-3.5 shadow-2xs">
        @csrf
        @method('PUT')

        {{-- Current Image Preview --}}
        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Current Banner Image</label>
            @if ($banner->image_path)
                <div class="rounded-lg overflow-hidden border border-slate-200/80 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 aspect-[16/6] max-h-56">
                    <img src="{{ asset('storage/' . $banner->image_path) }}" alt="{{ $banner->title }}" class="h-full w-full object-cover" />
                </div>
            @else
                <p class="text-xs text-slate-400">No image uploaded.</p>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            {{-- Title --}}
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.banners_title_label') }} *</label>
                <input type="text" name="title" required value="{{ old('title', $banner->title) }}" placeholder="e.g. Summer Sale 2026"
                       class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
            </div>

            {{-- Description --}}
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.banners_desc_label') }}</label>
                <textarea name="description" rows="2" maxlength="500" placeholder="Banner အောက်တွင် ပြသမည့် အကျဉ်းချုပ်စာသား"
                          class="w-full resize-y border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">{{ old('description', $banner->description) }}</textarea>
                <p class="mt-1 text-[11px] text-slate-400">Maximum 500 characters.</p>
            </div>

            {{-- Link URL --}}
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.banners_link_label') }}</label>
                <input type="text" name="link_url" value="{{ old('link_url', $banner->link_url) }}" placeholder="https://example.com သို့မဟုတ် /store/datapos-mobile/products"
                       class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
            </div>

            {{-- Replace Image --}}
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Replace Image (Optional)</label>
                <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/webp"
                       class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 dark:file:bg-slate-800 file:text-violet-700 dark:file:text-violet-300 hover:file:bg-violet-100 cursor-pointer">
                <p class="mt-1 text-[11px] text-slate-400">{{ __('messages.banners_recommended_size') }} (Max: {{ $imageMaxMb }}MB)</p>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ $returnTo ?? route('store.admin.banners.index', ['store_slug' => $store->slug, 'page' => $banner->page]) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition active:scale-95">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit" class="px-4 py-1.5 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition active:scale-95">
                {{ __('messages.save') }}
            </button>
        </div>
    </form>
</div>
@endsection
