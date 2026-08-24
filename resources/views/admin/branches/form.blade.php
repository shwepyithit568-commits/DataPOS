@extends('layouts.admin.app')

@section('title', ($branch->exists ? __('messages.edit') : __('messages.branches_add_new')) . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="w-full max-w-3xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <a href="{{ route('store.admin.branches.index', ['store_slug' => $store->slug]) }}"
               class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1 mb-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Back to Branches</span>
            </a>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit">
                {{ $branch->exists ? 'Edit Branch: ' . $branch->name : __('messages.branches_add_new') }}
            </h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-sm text-rose-800 dark:text-rose-200">
            <div class="font-bold mb-1">Please fix the following errors:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $branch->exists ? route('store.admin.branches.update', ['store_slug' => $store->slug, 'branch' => $branch->id]) : route('store.admin.branches.store', ['store_slug' => $store->slug]) }}"
          class="space-y-6">
        @csrf
        @if($branch->exists)
            @method('PUT')
        @endif

        {{-- Form Fields Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-7 shadow-sm space-y-5">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Branch Name --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.branches_name') }} *
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $branch->name) }}"
                           required
                           placeholder="e.g. Mandalay 78th Branch"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Branch Code --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.branches_code') }}
                    </label>
                    <input type="text"
                           name="code"
                           value="{{ old('code', $branch->code) }}"
                           placeholder="e.g. MDY-01"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Manager Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.branches_manager') }}
                    </label>
                    <input type="text"
                           name="manager_name"
                           value="{{ old('manager_name', $branch->manager_name) }}"
                           placeholder="e.g. Ko Kyaw"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Phone / Viber --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.branches_phone') }}
                    </label>
                    <input type="text"
                           name="phone"
                           value="{{ old('phone', $branch->phone) }}"
                           placeholder="09-123456789 / 09-987654321"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Physical Address --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.branches_address') }}
                    </label>
                    <textarea name="address"
                              rows="2"
                              placeholder="e.g. 78th Street, Between 30th & 31st, Mandalay"
                              class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">{{ old('address', $branch->address) }}</textarea>
                </div>

                {{-- Description / Notes --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.branches_notes') }}
                    </label>
                    <textarea name="notes"
                              rows="2"
                              placeholder="Operational notes, business hours, etc."
                              class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">{{ old('notes', $branch->notes) }}</textarea>
                </div>

                {{-- Auto Create Warehouse Checkbox (Only on Create) --}}
                @if(!$branch->exists)
                    <div class="sm:col-span-2 p-3.5 bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/60 rounded-2xl">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox"
                                   name="create_warehouse"
                                   value="1"
                                   checked
                                   class="w-4 h-4 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block text-xs font-bold text-indigo-900 dark:text-indigo-200">{{ __('messages.branches_create_warehouse') }}</span>
                                <span class="block text-[11px] text-indigo-700/80 dark:text-indigo-300">Creates a dedicated stockpoint named "[Branch Name] Warehouse"</span>
                            </div>
                        </label>
                    </div>
                @endif

                {{-- Toggles: Default & Active --}}
                <div class="sm:col-span-2 flex items-center gap-6 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="is_default"
                               value="1"
                               {{ old('is_default', $branch->is_default) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.branches_is_default') }}</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.branches_is_active') }}</span>
                    </label>
                </div>

            </div>

        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('store.admin.branches.index', ['store_slug' => $store->slug]) }}"
               class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold shadow-md transition">
                {{ $branch->exists ? __('messages.save') : __('messages.branches_add_new') }}
            </button>
        </div>

    </form>
</div>
@endsection
