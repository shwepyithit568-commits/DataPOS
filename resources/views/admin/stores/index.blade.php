@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('messages.store_management') }}</h1>
            <p class="admin-page-sub">{{ __('messages.store_management_sub') }}</p>
        </div>
        <a href="{{ route('admin.stores.create') }}"
            class="shrink-0 inline-flex items-center gap-2 min-h-11 px-4 py-2.5 rounded-lg bg-violet-600 text-white hover:bg-violet-700 font-semibold text-sm shadow transition">
            <span class="text-base leading-none">+</span>
            <span>{{ __('messages.store_create_title') }}</span>
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
    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1">
            <div class="flex items-center gap-2 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-6">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div x-data="{
        confirmTarget: null,
        lastDeleteEl: null,
        deleting: false,
        openConfirm(el) {
            this.confirmTarget = { id: el.dataset.id, name: el.dataset.name };
            this.deleting = false;
            this.lastDeleteEl = el;
            this.$nextTick(() => this.$refs.confirmCancel?.focus());
        },
        closeConfirm() {
            this.confirmTarget = null;
            this.$nextTick(() => this.lastDeleteEl?.focus());
        },
        submitDelete() {
            if (this.deleting) return;
            this.deleting = true;
            this.$refs.deleteForm.submit();
        },
        trapFocus(e) {
            const panel = this.$refs.confirmPanel;
            if (!panel) return;
            const focusables = [...panel.querySelectorAll('button, a[href], input, select, textarea, [tabindex]')].filter(el => !el.disabled && el.offsetParent !== null && el.getAttribute('tabindex') !== '-1');
            if (focusables.length === 0) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    }" @keydown.escape.window="if (confirmTarget) closeConfirm()"
        class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">

        {{-- Table (horizontal scroll on narrow screens) --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm text-gray-600 dark:text-slate-300">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                    <tr>
                        <th class="p-3">{{ __('messages.store_name') }}</th>
                        <th class="p-3">{{ __('messages.store_slug') }}</th>
                        <th class="p-3 text-center">{{ __('messages.store_status') }}</th>
                        <th class="p-3 text-center">{{ __('messages.products') }}</th>
                        <th class="p-3 text-right">{{ __('messages.store_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse ($stores as $store)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-slate-700/40 transition">
                            <td class="p-3 max-w-[16rem]">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-bold text-gray-900 dark:text-slate-100 break-words">{{ $store->name }}</span>
                                    @if ($store->is_primary)
                                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[11px] font-bold">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.05 2.9a1 1 0 0 1 1.9 0l1.2 3.9 4.05.2a1 1 0 0 1 .58 1.79l-3.17 2.53 1.06 3.91a1 1 0 0 1-1.53 1.1L10 13.47l-3.14 1.86a1 1 0 0 1-1.53-1.1l1.06-3.9-3.17-2.54a1 1 0 0 1 .58-1.79l4.05-.2 1.2-3.9Z"/></svg>
                                            {{ __('messages.store_primary') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3 font-mono text-xs text-gray-400 dark:text-slate-400 break-all">{{ $store->slug }}</td>
                            <td class="p-3 text-center">
                                @if ($store->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-500/15 text-green-700 dark:text-green-300 text-[11px] font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        {{ __('messages.store_active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 text-[11px] font-bold">
                                        {{ __('messages.store_inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 text-xs font-semibold">{{ $store->products_count }}</span>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                    <a href="{{ url('/store/' . $store->slug) }}" target="_blank" rel="noopener"
                                        class="min-h-11 inline-flex items-center gap-1 px-2.5 rounded-lg text-xs font-semibold text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition"
                                        title="{{ __('messages.store_open_storefront') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5h6v6m0-6-8 8M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg>
                                        {{ __('messages.store_open_storefront') }}
                                    </a>
                                    @if ($store->is_active)
                                        <a href="{{ url('/store/' . $store->slug . '/admin/dashboard') }}"
                                            class="min-h-11 inline-flex items-center gap-1 px-2.5 rounded-lg text-xs font-semibold text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition"
                                            title="{{ __('messages.store_open_admin') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.3 4.5 3 9l7.3 4.5L17.6 9 10.3 4.5Zm0 6L3 15l7.3 4.5L17.6 15l-7.3-4.5Z"/></svg>
                                            {{ __('messages.store_open_admin') }}
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.stores.edit', $store) }}"
                                        class="min-h-11 inline-flex items-center gap-1 px-2.5 rounded-lg text-xs font-semibold text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-700 transition"
                                        title="{{ __('messages.edit') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11 14l-4 1 1-4 9.6-9.4Z"/></svg>
                                        {{ __('messages.edit') }}
                                    </a>
                                    @if ($store->is_active)
                                        <button type="button" data-id="{{ $store->id }}" data-name="{{ $store->name }}"
                                            @click="openConfirm($el)"
                                            class="min-h-11 inline-flex items-center gap-1 px-2.5 rounded-lg text-xs font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition"
                                            aria-label="{{ __('messages.store_deactivate') }}"
                                            title="{{ __('messages.store_deactivate') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.4 6.6A9 9 0 0 1 6.6 18.4M18.4 6.6A9 9 0 0 0 6.6 18.4M18.4 6.6l-11.8 11.8"/></svg>
                                            {{ __('messages.store_deactivate') }}
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('admin.stores.activate', $store) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="min-h-11 inline-flex items-center gap-1 px-2.5 rounded-lg text-xs font-semibold text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/40 transition"
                                                title="{{ __('messages.store_activate') }}">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                {{ __('messages.store_activate') }}
                                            </button>
                                        </form>
                                    @endif

                                    @if (auth()->user()?->isPlatformOwner())
                                        <form method="POST" action="{{ route('admin.stores.force-destroy', $store) }}" class="inline"
                                            onsubmit="return confirm('⚠️ သတိပေးချက်: Store \'{{ $store->name }}\' ({{ $store->slug }}) နှင့် သက်ဆိုင်သော ကုန်ပစ္စည်း၊ စတော့၊ အော်ဒါ၊ အရောင်းဒေတာအားလုံးကို အပြီးတိုင် ဖျက်ပစ်ပါမည်။ သေချာပါသလား?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="min-h-11 inline-flex items-center gap-1 px-2.5 rounded-lg text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                title="အပြီးတိုင် ဖျက်မည်">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                အပြီးတိုင်ဖျက်မည်
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center">
                                <div class="text-4xl mb-3 opacity-40">🏪</div>
                                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">{{ __('messages.store_empty_title') }}</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.store_empty_hint') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if (method_exists($stores, 'links'))
            <div class="p-4 border-t dark:border-slate-700 text-sm">{{ $stores->links() }}</div>
        @endif

        {{-- Deactivate confirmation modal (accessible: focus trap, Escape, backdrop, focus return) --}}
        <div x-show="confirmTarget" x-cloak x-transition.opacity.duration.150ms class="fixed inset-0 z-50" role="dialog" aria-modal="true"
            aria-labelledby="store-deactivate-title">
        <div class="fixed inset-0 bg-black/40" @click="closeConfirm()" aria-hidden="true"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div x-ref="confirmPanel" @keydown.tab.prevent="trapFocus($event)" @click.stop
                class="pointer-events-auto w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 p-5 shadow-xl border border-gray-200 dark:border-slate-700">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-600 dark:text-amber-300 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="store-deactivate-title" class="text-base font-bold text-gray-900 dark:text-slate-100">{{ __('messages.store_deactivate_modal_title') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300 break-words font-medium" x-text="confirmTarget ? confirmTarget.name : ''"></p>
                        <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">{{ __('messages.store_deactivate_modal_warning') }}</p>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" x-ref="confirmCancel" @click="closeConfirm()"
                        class="min-h-11 px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 transition">
                        {{ __('messages.cancel') }}
                    </button>
                    <form x-ref="deleteForm" method="POST"
                        :action="'{{ route('admin.stores.index') }}' + '/' + (confirmTarget ? confirmTarget.id : '')"
                        class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" @click="submitDelete()" :disabled="deleting"
                            class="min-h-11 inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed shadow transition">
                            <span x-show="!deleting" class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.4 6.6A9 9 0 0 1 6.6 18.4M18.4 6.6A9 9 0 0 0 6.6 18.4M18.4 6.6l-11.8 11.8"/></svg>
                                {{ __('messages.store_deactivate') }}
                            </span>
                            <span x-show="deleting" class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                {{ __('messages.store_deactivating') }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
