@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="flex items-start sm:items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3 min-w-0">
            <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-tr from-violet-500 to-rose-500 text-white flex items-center justify-center text-xl shadow-lg shadow-purple-500/25">
                ✏️
            </div>
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit leading-tight truncate">
                    Edit Banner
                </h1>
                <p class="text-xs text-gray-600 dark:text-slate-400 truncate">
                    <span class="font-semibold text-gray-800 dark:text-slate-300">{{ $store->name }}</span>
                </p>
            </div>
        </div>
        <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/banners') }}" class="inline-flex items-center gap-1 text-xs text-purple-700 dark:text-purple-300 font-semibold hover:underline whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>Back to Banners</span>
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
            <div class="flex items-center gap-2 font-bold"><span>⚠️</span><span>Please fix the following:</span></div>
            <ul class="list-disc pl-8 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Edit Form --}}
    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/banners/' . $banner->id) }}" enctype="multipart/form-data" class="bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-xl space-y-4 transition-colors duration-200">
        @csrf
        @method('PUT')

        {{-- Current image preview --}}
        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1.5">Current Banner Image</label>
            @if ($banner->image_path)
                <div class="rounded-lg overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800">
                    <img src="{{ asset('storage/' . $banner->image_path) }}" alt="{{ $banner->title }}" class="h-36 sm:h-44 w-full object-cover" />
                </div>
            @else
                <p class="text-xs text-gray-400 dark:text-slate-500">No image uploaded.</p>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            {{-- Title --}}
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required value="{{ old('title', $banner->title) }}" placeholder="e.g. Summer Sale 2026" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" />
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Description (Optional)</label>
                <textarea name="description" rows="3" maxlength="500" placeholder="Banner အောက်တွင် ပြမည့် အကျဉ်းချုပ်စာသား" class="w-full resize-y border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition">{{ old('description', $banner->description) }}</textarea>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Maximum 500 characters. ဖုန်းတွင်ဖတ်ရလွယ်အောင် စာကြောင်း ၁–၂ ကြောင်းသာ အကြံပြုပါသည်။</p>
            </div>

            {{-- Link URL --}}
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Link URL (Optional)</label>
                <input type="text" name="link_url" value="{{ old('link_url', $banner->link_url) }}" placeholder="https://example.com သို့မဟုတ် /store/datapos-mobile/products" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition" />
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Full URL (https://...) သို့မဟုတ် စတိုးအတွင်း link (/products?... , /glass-finder?...) နှစ်မျိုးစလုံး လက်ခံပါတယ်။</p>
            </div>

            {{-- Image (optional replace) --}}
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Replace Image (Optional)</label>
                <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/webp" class="block w-full text-sm text-gray-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 dark:file:bg-slate-700 file:text-purple-700 dark:file:text-purple-300 hover:file:bg-purple-100 cursor-pointer" />
                <p class="mt-1 text-xs font-semibold text-purple-600 dark:text-purple-300">Recommended: 1920×900 px WebP/JPG · keep products and important visuals near the center.</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">PNG, JPG, or WebP · max {{ $imageMaxMb }}MB · leave empty to keep the current image</p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-1">
            <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/banners') }}" class="px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 font-semibold text-sm text-center transition">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold text-sm shadow flex items-center justify-center gap-2 transition">
                <span>💾</span><span>Update Banner</span>
            </button>
        </div>
    </form>
</div>
@endsection
