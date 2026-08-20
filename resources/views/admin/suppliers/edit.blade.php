@extends('layouts.admin.app')

@section('content')
<div class="w-full">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit truncate">{{ __('messages.supplier_edit_title', ['name' => $supplier->name]) }}</h1>
        <a href="{{ url('/store/' . $store->slug . '/admin/suppliers') }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline inline-flex items-center gap-1">&larr; {{ __('messages.supplier_back') }}</a>
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

    {{-- Form --}}
    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/suppliers/' . $supplier->id) }}"
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
            <label for="edit-supplier-name" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_name') }} <span class="text-rose-500">*</span></label>
            <input id="edit-supplier-name" x-ref="editName" type="text" name="name" value="{{ old('name', $supplier->name) }}"
                @input="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition {{ $errors->has('name') ? 'border-red-400 dark:border-red-500' : '' }}" />
            @error('name')
                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="edit-supplier-phone" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_phone') }}</label>
                <input id="edit-supplier-phone" type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" @input="dirty = true"
                    class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
            </div>
            <div>
                <label for="edit-supplier-email" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_email') }}</label>
                <input id="edit-supplier-email" type="email" name="email" value="{{ old('email', $supplier->email) }}" @input="dirty = true"
                    class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="edit-supplier-contact" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_contact_person') }}</label>
                <input id="edit-supplier-contact" type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" @input="dirty = true"
                    class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
            </div>
            <div>
                <label for="edit-supplier-address" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_address') }}</label>
                <input id="edit-supplier-address" type="text" name="address" value="{{ old('address', $supplier->address) }}" @input="dirty = true"
                    class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 min-h-11 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition" />
            </div>
        </div>

        <div>
            <label for="edit-supplier-notes" class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">{{ __('messages.supplier_notes') }}</label>
            <textarea id="edit-supplier-notes" name="notes" rows="2" @input="dirty = true"
                class="w-full border dark:border-slate-600 rounded-lg px-3 py-2.5 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition resize-none">{{ old('notes', $supplier->notes) }}</textarea>
        </div>

        {{-- Debt summary (read-only) --}}
        @if ($supplier->has_outstanding_balance)
            <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800">
                <div class="flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span class="font-semibold">{{ __('messages.supplier_outstanding_balance', ['amount' => number_format($supplier->remaining_balance, 2)]) }}</span>
                </div>
            </div>
        @endif

        <div class="flex items-center gap-2 pt-1">
            <button type="submit" :disabled="saving"
                class="inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-60 disabled:cursor-not-allowed font-semibold text-sm shadow transition">
                <span x-show="!saving" class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('messages.supplier_update') }}
                </span>
                <span x-show="saving" class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    {{ __('messages.supplier_saving') }}
                </span>
            </button>
            <a href="{{ url('/store/' . $store->slug . '/admin/suppliers') }}"
                class="inline-flex items-center min-h-11 px-4 py-2.5 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
                {{ __('messages.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
