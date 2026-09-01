<div x-data="{
    open: false,
    title: '{{ __('messages.confirm_action') ?? 'အတည်ပြုပါ' }}',
    message: '{{ __('messages.confirm_delete') ?? 'ဤလုပ်ဆောင်ချက်ကို ဆက်လက်လုပ်ဆောင်မှာ သေချာပါသလား?' }}',
    confirmText: '{{ __('messages.confirm') ?? 'အတည်ပြုမည်' }}',
    cancelText: '{{ __('messages.cancel') ?? 'မလုပ်တော့ပါ' }}',
    icon: '🗑️',
    isDanger: true,
    submitting: false,
    callback: null,
    targetForm: null,

    show(options) {
        this.title = options.title || '{{ __('messages.confirm_action') ?? 'အတည်ပြုပါ' }}';
        this.message = options.message || '{{ __('messages.confirm_delete') ?? 'ဤလုပ်ဆောင်ချက်ကို ဆက်လက်လုပ်ဆောင်မှာ သေချာပါသလား?' }}';
        this.confirmText = options.confirmText || (options.isDanger !== false ? '{{ __('messages.delete') ?? 'ဖျက်မည်' }}' : '{{ __('messages.confirm') ?? 'အတည်ပြုမည်' }}');
        this.cancelText = options.cancelText || '{{ __('messages.cancel') ?? 'မလုပ်တော့ပါ' }}';
        this.icon = options.icon || (options.isDanger !== false ? '🗑️' : '⚠️');
        this.isDanger = options.isDanger !== false;
        this.callback = options.callback || null;
        this.targetForm = options.targetForm || null;
        this.submitting = false;
        this.open = true;
        this.$nextTick(() => this.$refs.cancelBtn?.focus());
    },
    close() {
        if (this.submitting) return;
        this.open = false;
        this.callback = null;
        this.targetForm = null;
    },
    confirm() {
        this.submitting = true;
        if (this.targetForm) {
            this.targetForm._confirmed = true;
            this.targetForm.submit();
        } else if (typeof this.callback === 'function') {
            this.callback();
            this.close();
        } else {
            this.close();
        }
    }
}"
@open-confirm.window="show($event.detail)"
@keydown.escape.window="if (open) close()"
x-cloak>
    <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs transition-opacity"
             @click="close()"></div>

        {{-- Modal Dialog --}}
        <div class="min-h-full flex items-center justify-center p-4 text-center">
            <div x-show="open"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative w-full max-w-sm sm:max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-5 sm:p-6 text-left overflow-hidden space-y-4"
                 @click.stop>
                 
                <div class="flex items-start gap-3.5">
                    <div class="w-11 h-11 rounded-xl shrink-0 flex items-center justify-center text-xl shadow-xs"
                         :class="isDanger ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-200 dark:border-rose-900/50' : 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-900/50'"
                         x-text="icon">
                    </div>
                    <div class="space-y-1 min-w-0 flex-1">
                        <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-tight" x-text="title"></h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed break-words" x-text="message"></p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" x-ref="cancelBtn" @click="close()" :disabled="submitting"
                            class="btn-3d px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 disabled:opacity-50">
                        <span x-text="cancelText"></span>
                    </button>
                    <button type="button" @click="confirm()" :disabled="submitting"
                            class="btn-3d px-5 py-2 rounded-xl text-xs font-black text-white shadow-md flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed"
                            :class="isDanger ? 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/20' : 'bg-violet-600 hover:bg-violet-500 shadow-violet-600/20'">
                        <svg x-show="submitting" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="confirmText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    window.confirmAction = function(options) {
        window.dispatchEvent(new CustomEvent('open-confirm', { detail: options }));
    };

    // Capture submit events on forms that specify confirmations
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || form._confirmed) return;

        var confirmMsg = form.getAttribute('data-confirm');
        var onsubmitAttr = form.getAttribute('onsubmit') || '';

        if (!confirmMsg && onsubmitAttr.includes('confirm(')) {
            var match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
            if (match && match[1]) {
                confirmMsg = match[1];
            }
        }

        if (confirmMsg) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var isDanger = !form.hasAttribute('data-confirm-safe');
            window.confirmAction({
                title: form.getAttribute('data-confirm-title') || '{{ __('messages.confirm_action') ?? 'အတည်ပြုပါ' }}',
                message: confirmMsg,
                confirmText: form.getAttribute('data-confirm-button') || (isDanger ? '{{ __('messages.delete') ?? 'ဖျက်မည်' }}' : '{{ __('messages.confirm') ?? 'အတည်ပြုမည်' }}'),
                isDanger: isDanger,
                targetForm: form
            });
            return false;
        }

        // Global Double Submit Prevention for standard forms
        if (!form.dataset.noDoubleSubmitProtection && form.method && form.method.toUpperCase() === 'POST') {
            var submitBtn = form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');
            if (submitBtn && !submitBtn.dataset.keepEnabled) {
                setTimeout(function() {
                    submitBtn.setAttribute('disabled', 'disabled');
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                }, 10);
            }
        }
    }, true);
})();
</script>
