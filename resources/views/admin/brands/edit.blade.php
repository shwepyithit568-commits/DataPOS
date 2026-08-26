@extends('layouts.admin.app')

@section('content')
<div class="w-full">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit truncate">{{ __('messages.brand_edit_title', ['name' => $brand->name]) }}</h1>
        <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/brands') }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline inline-flex items-center gap-1">&larr; {{ __('messages.brand_back') }}</a>
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
    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/brands/' . $brand->id) }}" enctype="multipart/form-data"
        x-data="{ saving: false, dirty: false }"
        x-init="
            const warn = (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } };
            window.addEventListener('beforeunload', warn);
            $nextTick(() => { const name = $refs.editName; if (name && !document.activeElement || document.activeElement === document.body) name.focus(); });
        "
        @submit="if (saving) { $event.preventDefault(); } else { saving = true; }"
        class="w-full max-w-2xl bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-xl border border-gray-200/80 dark:border-slate-700 space-y-4 transition-colors duration-200">
        @csrf
        @method('PUT')

        <div>
            <label for="edit-brand-name" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.brand_name') }} <span class="text-rose-500">*</span></label>
            <input id="edit-brand-name" x-ref="editName" type="text" name="name" value="{{ old('name', $brand->name) }}"
                @input="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('name')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="edit-brand-code" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.product_form_code') }}</label>
            <input id="edit-brand-code" type="text" name="code" value="{{ old('code', $brand->code) }}"
                @input="dirty = true"
                class="w-full uppercase font-mono border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" placeholder="{{ __('messages.product_form_code_placeholder') }}" />
            @error('code')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.brand_current_logo') }}</label>
            <x-admin.logo-uploader
                :maxMb="$imageMaxMb"
                :current-logo-url="$brand->logo_path ? asset('storage/' . $brand->logo_path) : null"
                :current-logo-alt="$brand->name"
                :allow-remove="true" />
            @error('logo')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2 pt-1">
            <button type="submit" :disabled="saving"
                class="inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed font-semibold text-sm shadow transition">
                <span x-show="!saving" class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('messages.brand_update') }}
                </span>
                <span x-show="saving" class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    {{ __('messages.brand_saving') }}
                </span>
            </button>
            <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/brands') }}"
                class="inline-flex items-center min-h-11 px-4 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
