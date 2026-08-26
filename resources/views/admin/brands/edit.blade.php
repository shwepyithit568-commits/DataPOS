@extends('layouts.admin.app')

@section('title', __('messages.brand_edit_title', ['name' => $brand->name]) . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2.5 sm:p-3.5')

@section('content')
<div class="w-full space-y-2.5 max-w-2xl">
    {{-- Header --}}
    <div class="p-2.5 sm:p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-between gap-3 transition">
        <div class="min-w-0">
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 truncate">
                <span>✏️ {{ __('messages.brand_edit_title', ['name' => $brand->name]) }}</span>
            </h1>
        </div>
        <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/products/master-data?tab=brands') }}" 
           class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition flex items-center gap-1">
            <span>&larr;</span> <span>{{ __('messages.brand_back') }}</span>
        </a>
    </div>

    {{-- Error Flash --}}
    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-xs text-red-700 dark:text-red-300 space-y-1">
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
        @submit="if (saving) { $event.preventDefault(); } else { saving = true; window.removeEventListener('beforeunload', warn); }"
        class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 space-y-4 shadow-2xs transition">
        @csrf
        @method('PUT')

        <div>
            <label for="edit-brand-name" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.brand_name') }} <span class="text-rose-500">*</span></label>
            <input id="edit-brand-name" x-ref="editName" type="text" name="name" value="{{ old('name', $brand->name) }}"
                @input="dirty = true"
                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('name')
                <p class="mt-1 text-[11px] font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="edit-brand-code" class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.product_form_code') }}</label>
            <input id="edit-brand-code" type="text" name="code" value="{{ old('code', $brand->code) }}"
                @input="dirty = true"
                class="w-full uppercase font-mono rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition" placeholder="{{ __('messages.product_form_code_placeholder') }}" />
            @error('code')
                <p class="mt-1 text-[11px] font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.brand_current_logo') }}</label>
            <x-admin.logo-uploader
                :maxMb="$imageMaxMb"
                :current-logo-url="$brand->logo_path ? asset('storage/' . $brand->logo_path) : null"
                :current-logo-alt="$brand->name"
                :allow-remove="true" />
            @error('logo')
                <p class="mt-1 text-[11px] font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="dirty = false; window.location.href = '{{ $returnTo ?? url('/store/' . $store->slug . '/admin/products/master-data?tab=brands') }}'"
                class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition">
                {{ __('messages.cancel') }}
            </button>
            <button type="submit" :disabled="saving"
                class="px-5 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-md shadow-violet-500/20 transition active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!saving" class="inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ __('messages.brand_update') }}
                </span>
                <span x-show="saving" class="inline-flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    {{ __('messages.brand_saving') }}
                </span>
            </button>
        </div>
    </form>
</div>
@endsection
