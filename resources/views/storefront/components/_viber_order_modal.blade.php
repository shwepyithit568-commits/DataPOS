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
            class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4"
            @click.self="$store.viberModal.close()"
        >
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" x-transition.opacity></div>
            <div class="relative w-full sm:max-w-md max-h-[90dvh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border-t sm:border border-slate-200 dark:border-slate-800" @click.stop>
                {{-- Mobile drag handle --}}
                <div class="sm:hidden flex justify-center pt-2.5 pb-1">
                    <div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                </div>

                {{-- Header --}}
                <div class="flex items-center justify-between px-4 sm:px-5 pt-3 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2 font-myanmar">
                        <x-brand-icon brand="viber" class="h-4 w-4 text-violet-600 dark:text-violet-400 fill-current"/>
                        {{ __('messages.viber_order_modal_title') }}
                    </h3>
                    <button type="button" data-viber-modal-close="true" @click="$store.viberModal.close()" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="{{ __('messages.close') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Variant validation warning --}}
                <template x-if="$store.viberModal.needsVariant">
                    <div class="mx-4 sm:mx-5 mt-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300 text-xs font-bold flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        {{ __('messages.select_variant_first') }}
                    </div>
                </template>

                {{-- Quantity + Price Stepper Box --}}
                <div class="px-4 sm:px-5 pt-3 pb-3 space-y-2.5">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 font-myanmar">{{ __('messages.quantity') }}</span>
                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="$store.viberModal.decQty()" :disabled="$store.viberModal.qty <= 1" class="sf-btn-3d w-8 h-8 !p-0 inline-flex items-center justify-center text-sm font-black disabled:opacity-40" aria-label="Decrease quantity">−</button>
                            <span class="w-9 text-center text-sm font-black text-slate-900 dark:text-white font-mono" x-text="$store.viberModal.qty"></span>
                            <button type="button" @click="$store.viberModal.incQty()" class="sf-btn-3d w-8 h-8 !p-0 inline-flex items-center justify-center text-sm font-black" aria-label="Increase quantity">+</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase block font-myanmar">{{ __('messages.unit_price') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white text-xs mt-0.5 block" x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency($store.viberModal.price || 0) : ($store.viberModal.price || 0)"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-violet-50/70 dark:bg-violet-950/40 border border-violet-200/80 dark:border-violet-800/60">
                            <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase block font-myanmar">{{ __('messages.total_price') }}</span>
                            <span class="font-black text-violet-700 dark:text-violet-300 text-xs mt-0.5 block" x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(($store.viberModal.price || 0) * ($store.viberModal.qty || 1)) : (($store.viberModal.price || 0) * ($store.viberModal.qty || 1))"></span>
                        </div>
                    </div>
                </div>

                {{-- Message preview --}}
                <div class="px-4 sm:px-5 pb-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1 font-myanmar">{{ __('messages.viber_order_preview') }}</p>
                    <pre class="text-[11px] leading-relaxed text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 p-3 whitespace-pre-wrap break-words font-myanmar max-h-36 overflow-y-auto" x-text="$store.viberModal.message"></pre>
                </div>

                {{-- Viber QR & Contact Fallback Collapsible --}}
                <div x-data="{ showQr: false }" class="px-4 sm:px-5 pb-2">
                    <button type="button" @click="showQr = !showQr" class="text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
                        <span>📱</span>
                        <span x-text="showQr ? '✕ {{ __('messages.close') }}' : 'ℹ️ {{ __('messages.viber_qr_backup') }}'"></span>
                    </button>
                    <div x-show="showQr" x-cloak x-transition class="mt-2 p-3 rounded-xl bg-violet-50/70 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800 text-center">
                        <p class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-2 font-myanmar">{{ __('messages.viber_qr_desc') }}</p>
                        <template x-if="$store.viberModal.phone">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white dark:bg-slate-900 border border-violet-200 dark:border-violet-700 font-mono font-black text-xs text-violet-700 dark:text-violet-300 shadow-2xs">
                                <span>📞</span>
                                <span x-text="$store.viberModal.phone"></span>
                            </div>
                        </template>
                    </div>
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
                <div class="px-4 sm:px-5 pb-4 space-y-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="$store.viberModal.copyMessage()" :disabled="$store.viberModal.copied === 'copying'" class="sf-btn-3d inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold disabled:opacity-60">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.00 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                            <span x-text="$store.viberModal.copied === 'copied' ? '{{ __('messages.message_copied') }}' : '{{ __('messages.copy_message') }}'"></span>
                        </button>
                        <a :href="$store.viberModal.phone ? ('tel:' + $store.viberModal.phone) : '#'" @click="$store.viberModal.phone && window.__viberModalState.phone && window.location.assign('tel:' + window.__viberModalState.phone)" class="sf-btn-3d-success inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold" x-show="$store.viberModal.phone">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                            <span>{{ __('messages.call_phone') }}</span>
                        </a>
                        <button type="button" @click="$store.viberModal.copyAndOpen(); window.__armViberFallback && window.__armViberFallback($store.viberModal.url, true)" :disabled="$store.viberModal.needsVariant || $store.viberModal.opening" class="col-span-2 w-full sf-btn-3d-viber inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-black shadow-md disabled:opacity-40 disabled:cursor-not-allowed">
                            <x-brand-icon brand="viber" class="h-4 w-4 shrink-0 fill-current"/>
                            <span>{{ __('messages.open_viber') }}</span>
                        </button>
                        <button type="button" @click="$store.viberModal.close()" class="col-span-2 w-full py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                            {{ __('messages.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>