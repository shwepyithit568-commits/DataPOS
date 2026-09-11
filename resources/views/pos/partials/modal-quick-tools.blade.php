{{-- ── Camera Barcode Scanner Modal (Mobile Camera + Photo Snapshot Fallback) ── --}}
<div x-show="barcodeScannerOpen" x-cloak class="fixed inset-0 z-[96] flex items-end sm:items-center justify-center p-0 sm:p-4"
     @keydown.escape.window="closeBarcodeScanner()">
    {{-- Translucent backdrop --}}
    <div class="absolute inset-0 bg-black/40 dark:bg-black/60 transition-opacity" @click="closeBarcodeScanner()"></div>

    <div class="relative w-full max-w-full sm:max-w-md rounded-t-3xl rounded-b-none sm:rounded-2xl bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 sm:border p-4 sm:p-5 shadow-2xl space-y-3.5 overflow-hidden max-h-[92dvh] overflow-y-auto pb-[calc(1.25rem+env(safe-area-inset-bottom,0px))]">
        {{-- Modal Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400 grid place-items-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/><path d="M7 8h10"/><path d="M7 16h10"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <span>{{ __('messages.pos_camera_scanner_title') }}</span>
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.pos_align_barcode_hint') }}</p>
                </div>
            </div>

            {{-- Camera Controls (Torch, Camera Switch, Close) --}}
            <div class="flex items-center gap-1.5">
                <button type="button" @click="toggleTorch()"
                        :class="barcodeTorchOn ? 'bg-amber-500 text-white shadow-amber-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                        class="w-8 h-8 rounded-lg grid place-items-center text-xs font-bold transition shadow-xs cursor-pointer"
                        title="{{ __('messages.pos_torch_toggle') }}">
                    💡
                </button>
                <button type="button" @click="toggleCameraFacing()"
                        class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-xs font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer"
                        title="{{ __('messages.pos_camera_switch') }}">
                    🔄
                </button>
                <button type="button" @click="closeBarcodeScanner()"
                        class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-black hover:bg-rose-100 dark:hover:bg-rose-900/60 transition cursor-pointer">✕</button>
            </div>
        </div>

        {{-- Camera Video Viewfinder Area --}}
        <div class="relative w-full aspect-4/3 sm:aspect-16/10 rounded-xl bg-black overflow-hidden border border-slate-700/50 shadow-inner flex items-center justify-center">
            {{-- Native video element — getUserMedia stream is bound here directly --}}
            <video id="pos-barcode-video"
                   autoplay playsinline muted
                   class="w-full h-full object-cover"
                   style="transform: scaleX(1);"
                   x-bind:style="barcodeFacingMode === 'user' ? 'transform:scaleX(-1);' : 'transform:scaleX(1);'">
            </video>

            {{-- Laser Scan Line Overlay Animation --}}
            <div class="pointer-events-none absolute inset-x-6 top-0 bottom-0 flex flex-col justify-center items-center">
                <div class="w-full h-40 sm:h-44 border-2 border-emerald-400/70 rounded-xl relative overflow-hidden shadow-[0_0_15px_rgba(52,211,153,0.3)]">
                    <div class="pos-scanner-laser"></div>
                    <div class="absolute top-1 left-2 text-[10px] font-mono font-black text-emerald-400 bg-black/60 px-1.5 py-0.5 rounded">SCANNER ACTIVE</div>
                </div>
            </div>

            {{-- Camera Loading / Initializing Indicator --}}
            <div x-show="barcodeLoading" x-cloak class="absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center gap-2 text-white">
                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <span class="text-xs font-semibold text-slate-300">ကင်မရာ စတင်ဖွင့်နေပါသည်...</span>
            </div>

            {{-- Permission / Error Notice if Camera is unavailable / blocked --}}
            <div x-show="barcodeCameraError" x-cloak class="absolute inset-0 bg-slate-900/95 p-3 sm:p-4 flex flex-col items-center justify-center text-center gap-2 text-white overflow-y-auto">
                <span class="text-2xl" x-text="barcodeCameraInsecure ? '🔒⚠️' : '📷⚠️'"></span>
                <h4 x-show="barcodeCameraInsecure" class="text-xs font-black text-amber-300">{{ __('messages.pos_camera_insecure_title') }}</h4>
                <p class="text-xs text-rose-300 font-semibold max-w-xs leading-relaxed" x-text="barcodeCameraError"></p>

                {{-- Actionable guidance when accessing over Insecure HTTP on phone --}}
                <div x-show="barcodeCameraInsecure" class="w-full max-w-xs bg-slate-800/90 border border-slate-700/80 rounded-xl p-2 text-left text-[11px] text-slate-300 space-y-1">
                    <div class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">Chrome Flag ဖွင့်နည်း (၁ မိနစ်)</div>
                    <ol class="list-decimal list-inside space-y-0.5 text-[10px] text-slate-300">
                        <li>Chrome address bar တွင် <code class="text-emerald-400 bg-slate-900 px-1 py-0.5 rounded select-all font-mono">chrome://flags</code> ဖွင့်ပါ</li>
                        <li><code class="text-emerald-400 bg-slate-900 px-1 py-0.5 rounded select-all font-mono">unsafely-treat-insecure-origin-as-secure</code> ဟု ရှာပါ</li>
                        <li><code class="text-emerald-400 bg-slate-900 px-1 py-0.5 rounded select-all font-mono" x-text="window.location.origin"></code> ထည့်ပြီး Enabled ပြုလုပ်ပါ</li>
                        <li>Relaunch နှိပ်ပြီး ချက်ချင်း တန်းသုံးနိုင်ပါပြီ</li>
                    </ol>
                </div>

                <button type="button" @click="startCameraScanner()"
                        class="sf-btn-3d-primary px-4 py-1.5 rounded-xl text-xs font-black cursor-pointer inline-flex items-center gap-1.5 mt-1">
                    <span>🔄 {{ __('messages.pos_camera_retry') }}</span>
                </button>
            </div>
        </div>

        {{-- Continuous Mode Toggle --}}
        <div class="flex items-center justify-between gap-2 pt-1 text-xs">
            <label class="flex items-center gap-2 cursor-pointer select-none font-bold text-slate-700 dark:text-slate-300">
                <input type="checkbox" x-model="barcodeContinuous" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                <span>{{ __('messages.pos_continuous_scan') }}</span>
            </label>
        </div>

        {{-- Recent Scanned Product Pill --}}
        <div x-show="barcodeLastScanned" x-cloak class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 truncate">
                <span class="text-emerald-600 dark:text-emerald-400 font-black">✓</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 truncate" x-text="barcodeLastScannedName || barcodeLastScanned"></span>
            </div>
            <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-emerald-600 text-white font-bold shrink-0" x-text="barcodeLastScanned"></span>
        </div>
    </div>
</div>

{{-- ── Quick-add customer modal — creates a store-scoped retail/wholesale customer ── --}}
<div x-show="quickAddOpen" x-cloak class="fixed inset-0 z-[95] flex items-end sm:items-center justify-center p-0 sm:p-4"
     @keydown.escape.window="quickAddOpen = false">
    <div class="absolute inset-0 bg-black/25 dark:bg-black/35 transition-opacity" @click="quickAddOpen = false"></div>
    <div class="relative w-full max-w-full sm:max-w-sm rounded-t-3xl rounded-b-none sm:rounded-2xl bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-600/80 sm:border p-5 shadow-2xl dark:shadow-black/80 space-y-4 max-h-[92dvh] overflow-y-auto pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-600/10 text-blue-600 dark:text-blue-400 grid place-items-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.pos_customer_quick_add_title') }}</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.pos_customer_recent') }}</p>
                </div>
            </div>
            <button type="button" @click="quickAddOpen = false"
                    class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.pos_customer_name') }} <span class="text-rose-500">*</span></label>
            <input type="text" name="quick_customer_name" x-model="qname" x-ref="quickName"
                   autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                   data-lpignore="true" data-1p-ignore="true" data-form-type="other"
                   @keydown.enter="$refs.quickPhone?.focus()"
                   class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-900 outline-none transition"
                   placeholder="{{ __('messages.pos_customer_name') }}">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.pos_customer_phone') }} <span class="text-rose-500">*</span></label>
            <input type="tel" name="quick_customer_phone" x-model="qphone" x-ref="quickPhone"
                   autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                   data-lpignore="true" data-1p-ignore="true" data-form-type="other"
                   @keydown.enter="quickAdd()"
                   class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm font-mono font-semibold focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-900 outline-none transition"
                   placeholder="09 123 456 789">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.pos_customer_type') }}</label>
            <div class="grid grid-cols-2 gap-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 p-1">
                <button type="button" @click="qtype = 'retail_customer'"
                        :class="qtype === 'retail_customer' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                        class="rounded-lg px-3 py-2 text-xs font-black transition flex items-center justify-center gap-1.5">
                    <span>🛒</span> {{ __('messages.pos_customer_retail') }}
                </button>
                <button type="button" @click="qtype = 'wholesale_customer'"
                        :class="qtype === 'wholesale_customer' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                        class="rounded-lg px-3 py-2 text-xs font-black transition flex items-center justify-center gap-1.5">
                    <span>🏬</span> {{ __('messages.pos_customer_wholesale') }}
                </button>
            </div>
            <p class="mt-1.5 text-[11px] text-slate-400 flex items-center gap-1" x-show="qtype === 'wholesale_customer'" x-cloak>
                <svg class="inline w-3.5 h-3.5 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/></svg>
                <span>{{ __('messages.pos_wholesale_type_hint') }}</span>
            </p>
        </div>
        <div class="flex gap-2 pt-1">
            <button type="button" @click="quickAddOpen = false"
                    class="sf-btn-3d flex-1 rounded-xl px-4 py-2.5 text-sm font-bold cursor-pointer">{{ __('messages.cancel') }}</button>
            <button type="button" @click="quickAdd()" :disabled="quickBusy || !qname.trim() || !qphone.trim()"
                    class="sf-btn-3d-primary flex-1 rounded-xl px-4 py-2.5 text-sm font-black disabled:opacity-50 transition flex items-center justify-center gap-2 cursor-pointer">
                <svg x-show="quickBusy" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <span x-text="quickBusy ? (labels.pos_customer_saving || 'Saving...') : '+ ' + '{{ __('messages.pos_customer_add') }}'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ── Shop Expense Modal (quick cash-out / daily store expense) ── --}}
<div x-show="expenseModalOpen" x-cloak class="fixed inset-0 z-[95] flex items-end sm:items-center justify-center p-0 sm:p-4"
     @keydown.escape.window="expenseModalOpen = false">
    <div class="absolute inset-0 bg-black/25 dark:bg-black/35 transition-opacity" @click="expenseModalOpen = false"></div>
    <div class="relative w-full max-w-full sm:max-w-lg rounded-t-3xl rounded-b-none sm:rounded-2xl bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-600/80 sm:border p-4 sm:p-5 shadow-2xl dark:shadow-black/80 space-y-3.5 max-h-[92dvh] overflow-y-auto pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 grid place-items-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.pos_expense_modal_title') }}</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.expenses_subtitle') }}</p>
                </div>
            </div>
            <button type="button" @click="expenseModalOpen = false"
                    class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
        </div>

        {{-- Quick Expense Title Chips --}}
        <div>
            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1.5">{{ __('messages.pos_expense_quick_title') }}</label>
            <div class="flex flex-wrap gap-1.5">
                @foreach (['သောက်ရေသန့်', 'Delivery / ပို့ခ', 'မုန့်ဖိုး / လက်ဖက်ရည်', 'ဆိုင်သုံးပစ္စည်း', 'ဖုန်းဘေလ်'] as $quickTitle)
                    <button type="button" @click="setQuickExpense('{{ $quickTitle }}')"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-amber-100 dark:hover:bg-amber-950/50 hover:text-amber-700 dark:hover:text-amber-300 text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700 transition">
                        {{ $quickTitle }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Title / Reason --}}
        <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.expense_title') }} <span class="text-rose-500">*</span></label>
            <input type="text" x-model="expenseTitle" x-ref="expenseTitleInput" @keydown.enter="$refs.expenseAmountInput?.focus()"
                   class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-amber-500 focus:bg-white dark:focus:bg-slate-900 outline-none transition"
                   placeholder="{{ __('messages.expense_title') }}">
        </div>

        {{-- Amount & Quick Amount Chips --}}
        <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.expense_amount') }} <span class="text-rose-500">*</span></label>
            <input type="number" step="any" min="0" x-model="expenseAmount" x-ref="expenseAmountInput" @keydown.enter="submitExpense()"
                   class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5 text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 focus:bg-white dark:focus:bg-slate-900 outline-none transition"
                   placeholder="0">
            <div class="flex flex-wrap gap-1 mt-2">
                @foreach ([1000, 2000, 3000, 5000, 10000, 20000, 50000] as $chipAmt)
                    <button type="button" @click="setQuickExpenseAmount({{ $chipAmt }})"
                            class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition">
                        {{ number_format($chipAmt) }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Category & Payment Method Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.expense_categories_title') }}</label>
                <select x-model="expenseCategoryId"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-amber-500 outline-none transition">
                    <option value="">-- {{ __('messages.expense_all_categories') }} --</option>
                    @if (isset($expenseCategories))
                        @foreach ($expenseCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.expense_payment_method') }}</label>
                <select x-model="expensePaymentMethod"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-amber-500 outline-none transition">
                    <option value="cash">{{ __('messages.cash') }}</option>
                    <option value="kpay">KBZPay</option>
                    <option value="wave">WavePay</option>
                    <option value="cbpay">CB Pay</option>
                    <option value="bank_transfer">{{ __('messages.bank_transfer') }}</option>
                    <option value="other">{{ __('messages.other') }}</option>
                </select>
            </div>
        </div>

        {{-- Cash Shift sync notice --}}
        <div x-show="expensePaymentMethod === 'cash' && shiftsEnabled" x-cloak
             class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 text-[11px] font-medium text-amber-800 dark:text-amber-300 flex items-start gap-2">
            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ __('messages.pos_expense_shift_cash_sync') }}</span>
        </div>

        {{-- Optional Paid To & Notes --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.expense_paid_to') }}</label>
                <input type="text" x-model="expensePaidTo"
                       class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3 py-2 text-sm font-medium focus:ring-2 focus:ring-amber-500 outline-none transition"
                       placeholder="{{ __('messages.expense_paid_to') }}">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.expense_notes') }}</label>
                <input type="text" x-model="expenseNotes"
                       class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3 py-2 text-sm font-medium focus:ring-2 focus:ring-amber-500 outline-none transition"
                       placeholder="{{ __('messages.expense_notes') }}">
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="expenseModalOpen = false"
                    class="sf-btn-3d flex-1 rounded-xl px-4 py-2.5 text-sm font-bold cursor-pointer">{{ __('messages.cancel') }}</button>
            <button type="button" @click="submitExpense()" :disabled="expenseBusy || !expenseTitle.trim() || !expenseAmount"
                    class="sf-btn-3d-primary flex-1 rounded-xl px-4 py-2.5 text-sm font-black disabled:opacity-50 transition flex items-center justify-center gap-2 cursor-pointer !bg-amber-600 hover:!bg-amber-500 !border-amber-700 text-white shadow-amber-900/30">
                <svg x-show="expenseBusy" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <span x-text="expenseBusy ? (labels.pos_expense_saving || '{{ __('messages.pos_expense_saving') }}') : '{{ __('messages.save') }}'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ── Discount Modal (order-level discount: amount or percentage) ── --}}
<div x-show="discountModalOpen" x-cloak class="fixed inset-0 z-[95] flex items-end sm:items-center justify-center p-0 sm:p-4"
     @keydown.escape.window="discountModalOpen = false">
    <div class="absolute inset-0 bg-black/25 dark:bg-black/35 transition-opacity" @click="discountModalOpen = false"></div>
    <div class="relative w-full max-w-full sm:max-w-md rounded-t-3xl rounded-b-none sm:rounded-2xl bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-600/80 sm:border p-4 sm:p-5 shadow-2xl dark:shadow-black/80 space-y-4 max-h-[92dvh] overflow-y-auto pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 grid place-items-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.order_discount') }}</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ __('messages.subtotal') }}: <span class="font-extrabold text-slate-700 dark:text-slate-200" x-text="formatCurrency(cart.totals.subtotal)"></span>
                    </p>
                </div>
            </div>
            <button type="button" @click="discountModalOpen = false"
                    class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer">✕</button>
        </div>

        {{-- Type Switcher: Fixed Amount vs Percentage --}}
        <div class="grid grid-cols-2 gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-800">
            <button type="button" @click="discountType = 'fixed'"
                    :class="discountType === 'fixed' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                    class="py-2 rounded-lg text-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                <span x-text="window.__currencyConfig?.currency_symbol || 'Ks'"></span>
                <span>{{ __('messages.amount') }}</span>
            </button>
            <button type="button" @click="discountType = 'percent'"
                    :class="discountType === 'percent' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm font-black' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                    class="py-2 rounded-lg text-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                <span>%</span>
                <span>{{ __('messages.percentage') }}</span>
            </button>
        </div>

        {{-- Quick percentage buttons --}}
        <div x-show="discountType === 'percent'" class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ __('messages.quick_discount') }}</label>
            <div class="grid grid-cols-4 gap-1.5">
                @foreach ([5, 10, 15, 20] as $pct)
                    <button type="button" @click="setQuickDiscountPercent({{ $pct }})"
                            class="sf-btn-3d py-2 rounded-xl text-xs font-black text-slate-700 dark:text-slate-200 transition cursor-pointer">
                        {{ $pct }}%
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Input field --}}
        <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1"
                   x-text="discountType === 'percent' ? '{{ __('messages.discount_rate') }} (%)' : '{{ __('messages.discount_amount') }}'"></label>
            <div class="relative">
                <input id="pos-discount-input"
                       type="number"
                       step="any"
                       min="0"
                       x-ref="discountInput"
                       x-model="discountValue"
                       @keydown.enter.prevent="applyDiscount()"
                       class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-3.5 py-2.5 text-base font-extrabold tabular-nums focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-900 outline-none transition"
                       :placeholder="discountType === 'percent' ? '0%' : '0'">
                <span class="absolute right-3.5 top-2.5 text-xs font-extrabold text-slate-400 pointer-events-none"
                      x-text="discountType === 'percent' ? '%' : (window.__currencyConfig?.currency_symbol || 'Ks')"></span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1" x-show="discountType === 'percent' && Number(discountValue) > 0 && Number(discountValue) <= 100">
                {{ __('messages.discount') }}: <span class="font-bold text-rose-600 dark:text-rose-400" x-text="formatCurrency(Math.round((Number(cart.totals.subtotal || 0) * (Number(discountValue) / 100)) * 100) / 100)"></span>
            </p>
            <p class="text-[11px] font-bold text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1" x-show="discountType === 'percent' && Number(discountValue) > 100" x-cloak>
                <span>⚠️</span>
                <span>{{ __('messages.pos_discount_pct_exceeded') }}</span>
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="clearDiscount()" x-show="Number(cart.totals.discount) > 0"
                    class="sf-btn-3d-danger px-3 py-2.5 rounded-xl text-xs font-bold transition cursor-pointer">
                {{ __('messages.clear_discount') }}
            </button>
            <button type="button" @click="discountModalOpen = false"
                    class="sf-btn-3d flex-1 rounded-xl px-4 py-2.5 text-xs font-bold cursor-pointer">
                {{ __('messages.cancel') }}
            </button>
            <button type="button" @click="applyDiscount()" :disabled="discountBusy || !discountValue || (discountType === 'percent' && Number(discountValue) > 100)"
                    class="sf-btn-3d-primary flex-1 rounded-xl px-4 py-2.5 text-xs font-black disabled:opacity-50 transition flex items-center justify-center gap-1.5 cursor-pointer text-white">
                <svg x-show="discountBusy" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <span>{{ __('messages.apply_discount') }}</span>
            </button>
        </div>
    </div>
</div>

{{-- ── No open shift: an "open register" modal greets the cashier on
       entry and reappears on demand (toolbar shift pill). Once the
       shift is opened the page is completely clean. ── --}}
@if ($store->hasCapability(\App\Capabilities\Capability::OPERATIONS_CASHIER_SHIFTS) && !$openShift)
    <div x-data="{ open: true }" @pos:open-register.window="open = true">
        <div x-show="open" x-cloak x-transition.opacity
             class="fixed inset-0 z-[95] flex items-end sm:items-center justify-center p-0 sm:p-4"
             role="dialog" aria-modal="true" aria-labelledby="pos-open-register-title"
             @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-black/25 dark:bg-black/35 transition-opacity" @click="open = false"></div>
            <div class="relative w-full max-w-full sm:max-w-sm rounded-t-3xl rounded-b-none sm:rounded-2xl bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-600/80 sm:border p-5 shadow-2xl dark:shadow-black/80 space-y-4 max-h-[92dvh] overflow-y-auto pb-[calc(1.5rem+env(safe-area-inset-bottom,0px))]">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="shrink-0 w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 grid place-items-center">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        </div>
                        <div class="min-w-0">
                            <h2 id="pos-open-register-title" class="text-sm font-black text-slate-900 dark:text-slate-100">{{ __('messages.pos_open_register') }}</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.pos_open_register_hint') }}</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false" aria-label="{{ __('messages.close') }}"
                            class="sf-btn-3d shrink-0 w-8 h-8 rounded-lg font-black cursor-pointer">✕</button>
                </div>

                <form method="POST" action="{{ url('/store/' . $store->slug . '/pos/shifts') }}" class="grid gap-3">
                    @csrf
                    @if ($errors->has('shift'))
                        <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-3 py-2.5 text-sm font-semibold">
                            {{ $errors->first('shift') }}
                        </div>
                    @endif
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.register_name') }}</label>
                        <input type="text" name="register_name" required maxlength="100" value="{{ old('register_name', auth()->user()?->name ?? '') }}"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm"
                               placeholder="{{ __('messages.register_name_placeholder') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.opening_cash') }}</label>
                        <input type="number" name="opening_cash" min="0" step="100" value="{{ old('opening_cash', 0) }}"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2.5 text-sm">
                    </div>
                    <button type="submit" class="sf-btn-3d-primary rounded-xl px-4 py-3 text-sm font-black cursor-pointer">{{ __('messages.open_shift') }}</button>
                </form>

                @if ($occupiedRegisters->isNotEmpty())
                    <div class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 px-3 py-2.5 text-xs">
                        <p class="font-bold mb-1.5 inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            {{ __('messages.registers_in_use') }}
                        </p>
                        <ul class="space-y-1">
                            @foreach ($occupiedRegisters as $busy)
                                <li>
                                    <span class="font-bold">{{ $busy->register_name }}</span> —
                                    {{ __('messages.register_occupied_by', ['cashier' => $busy->cashier?->name ?? '—', 'time' => $busy->opened_at?->format('H:i') ?? '—']) }}
                                    <span class="block opacity-80">
                                        {{ __('messages.register_drawer_state', ['opening' => format_currency((float) $busy->opening_cash, $store), 'sales' => format_currency((float) $busy->cash_sales, $store)]) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-1.5 opacity-80">{{ __('messages.pick_another_register') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

{{-- ── Mobile filter & more bottom sheet (More | Category | Brand tabs) ── --}}
<div class="lg:hidden" x-data="{ fsOpen: false, fsTab: 'category' }"
     @pos:open-filters.window="fsOpen = true; fsTab = 'category'"
     @pos:open-more.window="fsOpen = true; fsTab = 'more'">
    <div x-show="fsOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[85] bg-black/25 dark:bg-black/35" @click="fsOpen = false"></div>
    <div x-show="fsOpen" x-cloak x-transition
         class="fixed inset-x-0 bottom-0 z-[90] lg:hidden rounded-t-3xl bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-2xl max-h-[80vh] flex flex-col pb-[env(safe-area-inset-bottom)]">
        <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-black uppercase tracking-wide text-slate-700 dark:text-slate-200"
                x-text="fsTab === 'more' ? '{{ __('messages.pos_more') }}' : '{{ __('messages.pos_filters') }}'"></h3>
            <button type="button" @click="fsOpen = false" aria-label="{{ __('messages.close') }}"
                    class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
        </div>

        {{-- Tabs: More | Categories | Brands --}}
        <div class="grid grid-cols-3 gap-1 px-5 pt-3">
            <button type="button" @click="fsTab = 'more'"
                    class="min-h-11 rounded-xl px-2 py-2 text-xs font-black transition"
                    :class="fsTab === 'more' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'">
                {{ __('messages.pos_more') }}
            </button>
            <button type="button" @click="fsTab = 'category'"
                    class="min-h-11 rounded-xl px-2 py-2 text-xs font-black transition"
                    :class="fsTab === 'category' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'">
                {{ __('messages.categories') }}
                <span x-show="categoryId > 0" class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400 ml-1"></span>
            </button>
            <button type="button" @click="fsTab = 'brand'"
                    class="min-h-11 rounded-xl px-2 py-2 text-xs font-black transition"
                    :class="fsTab === 'brand' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'">
                {{ __('messages.brands') }}
                <span x-show="brandId > 0" class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400 ml-1"></span>
            </button>
        </div>

        <div class="px-5 py-3 overflow-y-auto">
            {{-- More / Module options --}}
            <div x-show="fsTab === 'more'" class="space-y-2">
                <button type="button" @click="fsOpen = false; document.getElementById('pos-held-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                        class="w-full text-left min-h-12 rounded-xl px-4 py-3 text-sm font-bold border transition flex items-center justify-between gap-3 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/></svg>
                        {{ __('messages.held_sales') }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-xs font-black" x-text="cart.held_count"></span>
                </button>
                <div class="grid grid-cols-2 gap-2 pt-1">
                    @foreach ($moduleLinks as $link)
                        @if (!empty($link['is_action']))
                            <button type="button" @click="fsOpen = false; openExpenseModal()"
                                    class="{{ $link['btn_3d'] }} min-h-12 rounded-xl px-3 py-2.5 text-xs font-bold text-left cursor-pointer flex items-center gap-2 text-white shadow-xs">
                                <svg class="w-4 h-4 shrink-0 text-white/95" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $link['icon'] !!}</svg>
                                <span class="truncate text-white">{{ __('messages.' . $link['label']) }}</span>
                            </button>
                        @else
                            <a href="{{ url('/store/' . $store->slug . '/' . $link['path']) }}"
                               @if (!empty($link['target_blank'])) target="_blank" rel="noopener noreferrer" @endif
                               class="{{ $link['btn_3d'] }} min-h-12 rounded-xl px-3 py-2.5 text-xs font-bold flex items-center gap-2 text-white shadow-xs">
                                <svg class="w-4 h-4 shrink-0 text-white/95" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $link['icon'] !!}</svg>
                                <span class="truncate text-white">{{ __('messages.' . $link['label']) }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Category options --}}
            <div x-show="fsTab === 'category'" class="space-y-1">
                <button type="button" @click="toggleCategory(0); fsOpen = false"
                        class="w-full text-left min-h-11 rounded-xl px-3 py-2.5 text-sm font-bold border transition flex items-center justify-between gap-2"
                        :class="categoryId === 0 ? 'border-blue-600 text-blue-600 bg-blue-600/5' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                    <span>{{ __('messages.pos_all') }}</span>
                    <span x-show="categoryId === 0" class="text-blue-600 font-black">✓</span>
                </button>
                <template x-for="c in categories" :key="'fs-cat-' + c.id">
                    <button type="button" @click="toggleCategory(c.id); fsOpen = false"
                            class="w-full text-left min-h-11 rounded-xl px-3 py-2.5 text-sm font-bold border transition flex items-center justify-between gap-2"
                            :class="categoryId === c.id ? 'border-blue-600 text-blue-600 bg-blue-600/5' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                        <span x-text="c.name"></span>
                        <span x-show="categoryId === c.id" class="text-blue-600 font-black">✓</span>
                    </button>
                </template>
            </div>

            {{-- Brand options --}}
            <div x-show="fsTab === 'brand'" class="space-y-1">
                <button type="button" @click="toggleBrand(0); fsOpen = false"
                        class="w-full text-left min-h-11 rounded-xl px-3 py-2.5 text-sm font-bold border transition flex items-center justify-between gap-2"
                        :class="brandId === 0 ? 'border-blue-600 text-blue-600 bg-blue-600/5' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                    <span>{{ __('messages.pos_all') }}</span>
                    <span x-show="brandId === 0" class="text-blue-600 font-black">✓</span>
                </button>
                <template x-for="b in brands" :key="'fs-brand-' + b.id">
                    <button type="button" @click="toggleBrand(b.id); fsOpen = false"
                            class="w-full text-left min-h-11 rounded-xl px-3 py-2.5 text-sm font-bold border transition flex items-center justify-between gap-2"
                            :class="brandId === b.id ? 'border-blue-600 text-blue-600 bg-blue-600/5' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200'">
                        <span x-text="b.name"></span>
                        <span x-show="brandId === b.id" class="text-blue-600 font-black">✓</span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- ── Web Orders modal (import an online order into the cart) ── --}}
<div x-show="webOrdersOpen" x-cloak x-transition.opacity class="fixed inset-0 z-[95] flex items-end sm:items-center justify-center p-0 sm:p-4"
     @keydown.escape.window="webOrdersOpen = false">
    <div class="absolute inset-0 bg-black/25 dark:bg-black/35 transition-opacity" @click="webOrdersOpen = false"></div>
    <div class="relative w-full max-w-full sm:max-w-lg rounded-t-3xl rounded-b-none sm:rounded-2xl bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-600/80 sm:border shadow-2xl dark:shadow-black/80 flex flex-col max-h-[90dvh] pb-[calc(1rem+env(safe-area-inset-bottom,0px))]">
        <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="min-w-0">
                <h3 class="text-sm font-black">{{ __('messages.pos_web_orders') }}</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ __('messages.pos_web_orders_hint') }}</p>
            </div>
            <button type="button" @click="webOrdersOpen = false" aria-label="{{ __('messages.close') }}"
                    class="shrink-0 w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">✕</button>
        </div>

        <div class="px-5 pt-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" x-model="webOrderQ" @input.debounce.300ms="loadWebOrders()"
                       :placeholder="'{{ __('messages.pos_search_placeholder') }}'"
                       class="w-full h-11 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 pl-9 pr-3 text-sm font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>

        <div class="px-5 py-3 overflow-y-auto space-y-2.5">
            <p x-show="webOrderLoading" x-cloak class="text-sm text-slate-500 dark:text-slate-400 text-center py-6">{{ __('messages.loading') }}</p>
            <template x-for="order in webOrderResults" :key="order.id">
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/30 p-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-mono text-xs font-black text-blue-600 dark:text-blue-400" x-text="order.order_number"></p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="order.status === 'confirmed' ? 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300'"
                              x-text="order.status_label"></span>
                    </div>
                    <p class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-200" x-text="order.customer_name"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="order.customer_phone || ''"></p>
                    <div class="mt-2 space-y-0.5">
                        <template x-for="(item, i) in order.items" :key="i">
                            <p class="text-xs text-slate-600 dark:text-slate-300 flex justify-between gap-2">
                                <span class="min-w-0 truncate" x-text="'×' + item.quantity + '  ' + item.name"></span>
                                <span class="shrink-0 font-mono tabular-nums" x-text="formatCurrency(item.unit_price)"></span>
                            </p>
                        </template>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between gap-2">
                        <p class="text-sm font-black text-slate-900 dark:text-slate-100 tabular-nums" x-text="formatCurrency(order.total)"></p>
                        <button type="button" @click="importWebOrder(order)"
                                class="sf-btn-3d-primary min-h-11 inline-flex items-center gap-1.5 px-3.5 rounded-xl text-xs font-black cursor-pointer">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                            {{ __('messages.web_order_load_cart') }}
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="!webOrderLoading && !webOrderResults.length" x-cloak
                 class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500 dark:text-slate-400">
                <svg class="inline w-4 h-4 -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/><path d="M14 3H9v6h5z"/></svg>
                {{ __('messages.no_web_orders') }}
            </div>
        </div>
    </div>
</div>
