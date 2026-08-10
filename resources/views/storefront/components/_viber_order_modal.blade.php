{{--
  Viber Order Modal — renders at body level via @include inside catalog page.
  Reads state from Alpine.store('viberModal') (defined in viber-order.js).
  Visibility driven by x-if on the store's reactive `open` flag.
--}}

<div
    x-data="{ localOpen: false }"
    x-effect="localOpen = !!$store.viberModal.open"
    @keydown.escape.window="localOpen && $store.viberModal.close()"
    role="dialog"
    aria-modal="true"
>
    <template x-if="localOpen">
        <div
            class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center"
            @click.self="$store.viberModal.close()"
        >
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" x-transition.opacity></div>
            <div class="relative w-full sm:max-w-md max-h-[90dvh] overflow-y-auto rounded-t-3xl sm:rounded-3xl bg-white dark:bg-slate-800 shadow-2xl border-t sm:border border-slate-200 dark:border-slate-700" @click.stop>
                {{-- Mobile drag handle --}}
                <div class="sm:hidden flex justify-center pt-2 pb-1">
                    <div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                </div>

                {{-- Header --}}
                <div class="flex items-center justify-between px-4 sm:px-5 pt-3 pb-2">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <x-brand-icon brand="viber" class="h-4 w-4 text-violet-600 dark:text-violet-400"/>
                        {{ __('messages.viber_order_modal_title') }}
                    </h3>
                    <button type="button" data-viber-modal-close="true" @click="$store.viberModal.close()" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition" aria-label="{{ __('messages.close') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Variant validation warning --}}
                <template x-if="$store.viberModal.needsVariant">
                    <div class="mx-4 sm:mx-5 mb-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300 text-xs font-bold flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        {{ __('messages.select_variant_first') }}
                    </div>
                </template>

                {{-- Quantity + Price --}}
                <div class="px-4 sm:px-5 pb-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('messages.quantity') }}</span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="$store.viberModal.decQty()" :disabled="$store.viberModal.qty <= 1" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-black flex items-center justify-center disabled:opacity-40 transition active:scale-90">−</button>
                            <span class="w-8 text-center text-sm font-black text-slate-900 dark:text-white" x-text="$store.viberModal.qty"></span>
                            <button type="button" @click="$store.viberModal.incQty()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-black flex items-center justify-center transition active:scale-90">+</button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-600 dark:text-slate-400">{{ __('messages.unit_price') }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">Ks <span x-text="$store.viberModal.fmt($store.viberModal.price || 0)"></span></span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-black text-slate-900 dark:text-white">{{ __('messages.total_price') }}</span>
                        <span class="font-black text-violet-600 dark:text-violet-400">Ks <span x-text="$store.viberModal.fmt(($store.viberModal.price || 0) * ($store.viberModal.qty || 1))"></span></span>
                    </div>
                </div>

                {{-- Message preview --}}
                <div class="px-4 sm:px-5 pb-3">
                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">{{ __('messages.viber_order_preview') }}</p>
                    <pre class="text-[11px] leading-relaxed text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-700 p-3 whitespace-pre-wrap break-words font-myanmar max-h-40 overflow-y-auto" x-text="$store.viberModal.message"></pre>
                </div>

                {{-- Copy feedback --}}
                <template x-if="$store.viberModal.copied === 'copied'">
                    <div class="mx-4 sm:mx-5 mb-2 p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 text-emerald-800 dark:text-emerald-300 text-[11px] font-bold text-center">
                        ✅ {{ __('messages.message_copied_hint') }}
                    </div>
                </template>
                <template x-if="$store.viberModal.copied === 'failed'">
                    <div class="mx-4 sm:mx-5 mb-2 p-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-800 dark:text-rose-300 text-[11px] font-bold text-center">
                        {{ __('messages.message_copy_failed') }}
                    </div>
                </template>

                {{-- Action buttons --}}
                <div class="px-4 sm:px-5 pb-4 space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="$store.viberModal.copyMessage()" :disabled="$store.viberModal.copied === 'copying'" class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-slate-100 dark:bg-slate-700 px-2 py-2.5 text-xs font-black text-slate-700 dark:text-slate-200 transition hover:bg-slate-200 dark:hover:bg-slate-600 active:scale-95 disabled:opacity-60">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.00 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                            <span x-text="$store.viberModal.copied === 'copied' ? '{{ __('messages.message_copied') }}' : '{{ __('messages.copy_message') }}'"></span>
                        </button>
                        <a :href="$store.viberModal.phone ? ('tel:' + $store.viberModal.phone) : '#'" @click="$store.viberModal.phone && window.__viberModalState.phone && window.location.assign('tel:' + window.__viberModalState.phone)" class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-2 py-2.5 text-xs font-black text-white transition hover:bg-emerald-500 active:scale-95" x-show="$store.viberModal.phone">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                            <span>{{ __('messages.call_phone') }}</span>
                        </a>
                        <button type="button" @click="$store.viberModal.copyAndOpen(); window.__armViberFallback && window.__armViberFallback($store.viberModal.url, true)" :disabled="$store.viberModal.needsVariant || $store.viberModal.opening" class="col-span-2 w-full inline-flex min-h-[48px] items-center justify-center gap-2 rounded-xl bg-purple-600 px-2 py-3 text-sm font-black text-white shadow-lg shadow-purple-500/25 transition hover:bg-purple-500 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed">
                            <x-brand-icon brand="viber" class="h-5 w-5 shrink-0"/>
                            <span>{{ __('messages.open_viber') }}</span>
                        </button>
                        <button type="button" @click="$store.viberModal.close()" class="col-span-2 w-full min-h-[44px] rounded-xl px-2 py-2 text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                            {{ __('messages.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>