@props([
    'methods' => collect(),
])

<div
    x-data="{
        open: false,
        activePay: null,
        copiedField: null,
        show(pay) {
            this.activePay = pay;
            this.open = true;
            this.copiedField = null;
        },
        close() {
            this.open = false;
            this.activePay = null;
        },
        copyText(text, field) {
            if (!text) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text);
            } else {
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            }
            this.copiedField = field;
            setTimeout(() => { this.copiedField = null; }, 2000);
        }
    }"
    @open-payment-modal.window="show($event.detail)"
    @keydown.escape.window="close()"
    x-cloak
>
    {{-- Modal Dialog --}}
    <div
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto bg-slate-950/70 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div
            @click.outside="close()"
            class="relative w-full max-w-sm sm:max-w-md rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xl overflow-hidden p-5 sm:p-6 space-y-4"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <template x-if="activePay?.icon_html">
                        <div class="w-8 h-8 shrink-0 flex items-center justify-center" x-html="activePay.icon_html"></div>
                    </template>
                    <div class="min-w-0">
                        <h4 class="text-base sm:text-lg font-black text-slate-900 dark:text-white truncate font-myanmar" x-text="activePay?.name || 'Payment Details'"></h4>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 font-myanmar">ငွေပေးချေမှု အချက်အလက်</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="close()"
                    class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 dark:hover:text-slate-200 transition cursor-pointer"
                    aria-label="Close"
                >
                    ✕
                </button>
            </div>

            {{-- QR Code Display --}}
            <template x-if="activePay?.qr_url">
                <div class="flex flex-col items-center justify-center p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 space-y-2">
                    <div class="relative w-48 h-48 sm:w-56 sm:h-56 rounded-xl overflow-hidden bg-white p-2 shadow-sm border border-slate-200 flex items-center justify-center">
                        <img :src="activePay.qr_url" :alt="activePay.name + ' QR Code'" class="w-full h-full object-contain select-none" />
                    </div>
                    <p class="text-[11px] font-bold text-slate-600 dark:text-slate-400 font-myanmar flex items-center gap-1">
                        <span>📱 Scan ဖတ်၍ တိုက်ရိုက် ငွေပေးချေနိုင်ပါသည်</span>
                    </p>
                </div>
            </template>

            {{-- Account Information with 1-Click Copy --}}
            <div class="space-y-2.5">
                <template x-if="activePay?.account_name">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 gap-2">
                        <div class="min-w-0">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Account Name</span>
                            <span class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white block truncate" x-text="activePay.account_name"></span>
                        </div>
                        <button
                            type="button"
                            @click="copyText(activePay.account_name, 'name')"
                            class="shrink-0 px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 active:scale-95 cursor-pointer"
                            :class="copiedField === 'name' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 hover:bg-slate-100'"
                        >
                            <span x-text="copiedField === 'name' ? '✓ ကူးပြီး' : '📋 Copy'"></span>
                        </button>
                    </div>
                </template>

                <template x-if="activePay?.account_number">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 gap-2">
                        <div class="min-w-0">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Account / Phone No.</span>
                            <span class="text-xs sm:text-sm font-mono font-black text-slate-900 dark:text-white block truncate" x-text="activePay.account_number"></span>
                        </div>
                        <button
                            type="button"
                            @click="copyText(activePay.account_number, 'number')"
                            class="shrink-0 px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 active:scale-95 cursor-pointer"
                            :class="copiedField === 'number' ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 hover:bg-slate-100'"
                        >
                            <span x-text="copiedField === 'number' ? '✓ ကူးပြီး' : '📋 Copy'"></span>
                        </button>
                    </div>
                </template>

                <template x-if="activePay?.instructions">
                    <div class="p-3 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-900/50 space-y-1">
                        <span class="text-[11px] font-extrabold text-amber-900 dark:text-amber-300 font-myanmar block">💡 လမ်းညွှန်ချက်:</span>
                        <p class="text-xs text-amber-800 dark:text-amber-200 font-myanmar leading-relaxed" x-text="activePay.instructions"></p>
                    </div>
                </template>
            </div>

            {{-- Close Button --}}
            <div class="pt-2">
                <button
                    type="button"
                    @click="close()"
                    class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-xs shadow-sm transition active:scale-95 cursor-pointer font-myanmar"
                >
                    ပိတ်မည်
                </button>
            </div>
        </div>
    </div>
</div>
