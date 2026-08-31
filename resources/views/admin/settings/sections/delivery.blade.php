<div class="p-4 sm:p-6 space-y-6"
    x-data="{
        showPayForm: false,
        showDelForm: false,
        payForm: { name: '', code: '', type: 'custom', icon_type: 'initials', icon_value: '', account_name: '', account_number: '', instructions: '', is_active: true, show_account_details: false, sort_order: 0 },
        delForm: { name: '', type: 'custom', icon: '🚚', description: '', service_area: '', estimated_time: '', fee_note: '', is_active: true, sort_order: 0 }
    }">
    <div class="admin-section-head">
        <div>
            <h2 class="admin-section-title">🚚 {{ __('messages.settings_delivery') }}</h2>
            <p class="admin-section-sub">Payment နှင့် delivery methods များကို structured cards အဖြစ် စီမံပါ — footer, \"မှာယူနည်း\" နှင့် Order Builder မှာ တူညီစွာ ပြမည်။</p>
        </div>
    </div>

    {{-- ============ PAYMENT METHODS ============ --}}
    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h3 class="admin-section-title">💳 {{ __('messages.settings_payment_methods', ['count' => $store->paymentMethods->count()]) }}</h3>
                <p class="{{ $helpClass }}">Active methods များက footer / \"မှာယူနည်း\" / Order Builder တွင် ပြမည်။ Account number ကို default အားဖြင့် မပြဘဲ \"Show account details\" ဖွင့်မှသာ ပြမည်။</p>
            </div>
            <button type="button" @click="showPayForm = !showPayForm; if (showPayForm) payForm = { name: '', code: '', type: 'custom', icon_type: 'initials', icon_value: '', account_name: '', account_number: '', instructions: '', is_active: true, show_account_details: false, sort_order: {{ $store->paymentMethods->max('sort_order') + 1 }} }"
                class="shrink-0 inline-flex min-h-11 items-center rounded-xl border border-violet-300 bg-violet-50 px-4 py-2 text-xs font-black text-violet-700 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                + {{ __('messages.settings_add_payment_method') }}
            </button>
        </div>

        {{-- Add form (inline card) --}}
        <div x-show="showPayForm" x-cloak x-transition class="rounded-2xl border border-violet-200 bg-violet-50/40 p-4 space-y-3 dark:border-violet-800/50 dark:bg-violet-950/20">
            <div class="flex items-center justify-between">
                <p class="text-xs font-black text-violet-700 dark:text-violet-300">New Payment Method</p>
                <button type="button" @click="showPayForm = false" class="rounded-lg px-2 py-1 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">✕ Close</button>
            </div>
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings/payment-methods') }}" enctype="multipart/form-data" class="space-y-3"
                @submit="submitting = true">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">Method Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="payForm.name" required maxlength="120" placeholder="e.g. KBZ Pay / WavePay / COD" class="{{ $inputClass }}" />
                        @error('name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Type</label>
                        <select name="type" x-model="payForm.type" class="{{ $inputClass }}">
                            @foreach (['kpay' => 'KBZPay/KPay', 'wavepay' => 'WavePay', 'cbpay' => 'CB Pay', 'ayapay' => 'AYA Pay', 'mmqr' => 'MMQR', 'bank' => 'Bank Transfer', 'cod' => 'Cash on Delivery', 'cash' => 'Cash at Store', 'custom' => 'Custom'] as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Icon Type</label>
                        <select name="icon_type" x-model="payForm.icon_type" class="{{ $inputClass }}">
                            <option value="builtin">Built-in brand icon</option>
                            <option value="custom">Custom uploaded icon</option>
                            <option value="initials">Initials / text</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Built-in Icon Key</label>
                        <select name="icon_value" x-model="payForm.icon_value" class="{{ $inputClass }}">
                            <option value="">— none —</option>
                            @foreach (['kpay' => 'KPay', 'wavepay' => 'WavePay', 'cbpay' => 'CB Pay', 'ayapay' => 'AYA Pay', 'mmqr' => 'MMQR', 'bank' => 'Bank Transfer', 'cod' => 'Cash on Delivery', 'cash' => 'Cash'] as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Custom Icon (PNG/JPG/WebP, ≤ 2MB)</label>
                        <input type="file" name="icon_image" accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:text-slate-400 dark:file:bg-violet-950/40 dark:file:text-violet-300" />
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">QR Code Image (PNG/JPG/WebP, ≤ 4MB)</label>
                        <input type="file" name="qr_image" accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-emerald-700 hover:file:bg-emerald-100 dark:text-slate-400 dark:file:bg-emerald-950/40 dark:file:text-emerald-300" />
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Account Name</label>
                        <input type="text" name="account_name" x-model="payForm.account_name" maxlength="120" placeholder="e.g. U Thit Sar" class="{{ $inputClass }}" />
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Account Number</label>
                        <input type="text" name="account_number" x-model="payForm.account_number" maxlength="120" placeholder="e.g. 09xxxxxxxxx / 123456789" class="{{ $inputClass }}" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">Instructions</label>
                        <textarea name="instructions" x-model="payForm.instructions" rows="2" class="{{ $inputClass }}" placeholder="ဥပမာ — လွှဲပြီးရင် payment screenshot ကို Viber မှာ ပို့ပါ"></textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="payForm.is_active" class="h-5 w-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500" checked />
                        <span class="text-xs font-bold text-gray-700 dark:text-slate-200">Active (show on storefront)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_account_details" value="1" x-model="payForm.show_account_details" class="h-5 w-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                        <span class="text-xs font-bold text-gray-700 dark:text-slate-200">Show account details publicly</span>
                    </label>
                </div>
                <button type="submit"
                    class="inline-flex min-h-12 items-center rounded-xl bg-violet-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 disabled:opacity-70">
                    <span>{{ __('messages.add_payment_method') }}</span>
                </button>
            </form>
        </div>

        {{-- Existing methods --}}
        @forelse ($store->paymentMethods as $pm)
            <div x-data="{ editing: false }" class="bg-white dark:bg-slate-800/60 rounded-xl px-3 py-2.5 sm:px-4 sm:py-3 transition-colors duration-200 border border-slate-200/80 dark:border-slate-700/60">
                {{-- Main Summary Row --}}
                <div class="flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <x-payment-method-icon :method="$pm" class="h-8 w-8 sm:h-9 sm:w-9 shrink-0" text-class="text-xs" />
                        <div class="min-w-0 flex flex-wrap items-center gap-2">
                            <h4 class="text-xs sm:text-sm font-black text-gray-900 dark:text-slate-100 truncate">{{ $pm->name }}</h4>
                            <span class="rounded-full px-2 py-0.5 text-[9px] font-black {{ $pm->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ $pm->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="text-[9px] font-bold text-slate-400">sort {{ $pm->sort_order }}</span>
                            @if ($pm->icon_path && \App\Support\StorefrontAsset::imageUrl($pm->icon_path))
                                <span class="inline-flex items-center gap-1 text-[9px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/40 px-1.5 py-0.5 rounded border border-violet-200 dark:border-violet-800">
                                    <span>📷 Custom Icon</span>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                        @if ($pm->hasQr())
                            <a href="{{ $pm->qrUrl() }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex min-h-8 items-center gap-1 rounded-lg border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700 shadow-2xs hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900/80 transition cursor-pointer"
                                title="View QR Code">
                                <span>📱 QR Code</span>
                            </a>
                        @endif
                        <button type="button" @click="editing = !editing"
                            class="inline-flex min-h-8 items-center gap-1 rounded-lg border border-sky-300 bg-sky-50 px-2.5 py-1 text-[11px] font-black text-sky-700 shadow-2xs hover:bg-sky-100 dark:border-sky-700 dark:bg-sky-950/60 dark:text-sky-300 dark:hover:bg-sky-900/80 cursor-pointer transition">
                            <span x-text="editing ? '✕ Cancel' : '✏️ Edit (ပြင်မည်)'"></span>
                        </button>
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings/payment-methods/' . $pm->id) }}" data-confirm="Delete {{ $pm->name }}?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex min-h-8 items-center gap-1 rounded-lg bg-rose-600 px-2.5 py-1 text-[11px] font-black text-white hover:bg-rose-700 active:bg-rose-800 cursor-pointer shadow-xs transition">
                                <span>🗑️ Delete</span>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Expandable Full Edit Form --}}
                <div x-show="editing" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="mt-3 pt-3 border-t border-slate-200/80 dark:border-slate-700/80">
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings/payment-methods/' . $pm->id) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $labelClass }}">Payment Method Name *</label>
                                <input type="text" name="name" value="{{ $pm->name }}" required maxlength="120" class="{{ $inputClass }}" />
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Icon Type</label>
                                <select name="icon_type" class="{{ $inputClass }}">
                                    <option value="builtin" {{ $pm->icon_type === 'builtin' ? 'selected' : '' }}>Built-in brand icon</option>
                                    <option value="custom" {{ $pm->icon_type === 'custom' ? 'selected' : '' }}>Custom uploaded icon</option>
                                    <option value="initials" {{ $pm->icon_type === 'initials' ? 'selected' : '' }}>Initials / text</option>
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Built-in Icon Key</label>
                                <select name="icon_value" class="{{ $inputClass }}">
                                    <option value="">— none —</option>
                                    @foreach (['kpay' => 'KBZPay (KPay)', 'wavepay' => 'WavePay', 'cbpay' => 'CB Pay', 'ayapay' => 'AYA Pay', 'mmqr' => 'MMQR (National Standard)', 'bank' => 'Bank Transfer', 'cod' => 'Cash on Delivery', 'cash' => 'Cash'] as $key => $label)
                                        <option value="{{ $key }}" {{ $pm->icon_value === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Sort Order</label>
                                <input type="number" name="sort_order" value="{{ $pm->sort_order }}" min="0" max="9999" class="{{ $inputClass }}" />
                            </div>

                            {{-- Custom Icon Upload & Preview --}}
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-900/40 p-3 space-y-2">
                                <label class="block text-xs font-black text-slate-800 dark:text-slate-200">📷 Custom Icon (PNG/JPG/WebP, ≤ 2MB)</label>
                                @if ($pm->icon_path && \App\Support\StorefrontAsset::imageUrl($pm->icon_path))
                                    <div class="flex items-center gap-3">
                                        <img src="{{ \App\Support\StorefrontAsset::imageUrl($pm->icon_path) }}" alt="Current Icon" class="h-10 w-10 object-contain rounded-lg border border-slate-300 bg-white p-1" />
                                        <label class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 cursor-pointer">
                                            <input type="checkbox" name="remove_icon" value="1" class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500" />
                                            <span>Remove Icon</span>
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="icon_image" accept="image/png,image/jpeg,image/webp"
                                    class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-violet-600 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-violet-700 cursor-pointer" />
                            </div>

                            {{-- QR Code Upload & Preview --}}
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-900/40 p-3 space-y-2">
                                <label class="block text-xs font-black text-slate-800 dark:text-slate-200">📱 QR Code Image (PNG/JPG/WebP, ≤ 4MB)</label>
                                @if ($pm->hasQr())
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $pm->qrUrl() }}" alt="Current QR" class="h-14 w-14 object-contain rounded-lg border border-slate-300 bg-white p-1 shadow-2xs" />
                                        <label class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 cursor-pointer">
                                            <input type="checkbox" name="remove_qr" value="1" class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500" />
                                            <span>Remove QR</span>
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="qr_image" accept="image/png,image/jpeg,image/webp"
                                    class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-emerald-700 cursor-pointer" />
                            </div>

                            <div>
                                <label class="{{ $labelClass }}">Account Name</label>
                                <input type="text" name="account_name" value="{{ $pm->account_name }}" maxlength="120" placeholder="e.g. U Thit Sar" class="{{ $inputClass }}" />
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Account Number</label>
                                <input type="text" name="account_number" value="{{ $pm->account_number }}" maxlength="120" placeholder="e.g. 09xxxxxxxxx / 123456789" class="{{ $inputClass }}" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="{{ $labelClass }}">Instructions</label>
                                <textarea name="instructions" rows="2" class="{{ $inputClass }}" placeholder="ဥပမာ — လွှဲပြီးရင် payment screenshot ကို Viber မှာ ပို့ပါ">{{ $pm->instructions }}</textarea>
                            </div>
                            <div class="sm:col-span-2 flex flex-wrap gap-4 pt-1">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" {{ $pm->is_active ? 'checked' : '' }} class="h-5 w-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                                    <span class="text-xs font-bold text-gray-700 dark:text-slate-200">Active (show on storefront)</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="show_account_details" value="1" {{ $pm->show_account_details ? 'checked' : '' }} class="h-5 w-5 rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                                    <span class="text-xs font-bold text-gray-700 dark:text-slate-200">Show account details publicly</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-violet-600 px-5 py-2.5 text-xs font-black text-white hover:bg-violet-700 shadow-sm transition">
                                💾 Save Changes (သိမ်းမည်)
                            </button>
                            <button type="button" @click="editing = false" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">No payment methods yet.</p>
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Add one above — until then the storefront falls back to the legacy payment note below.</p>
            </div>
        @endforelse

    </section>

    {{-- ============ DELIVERY METHODS ============ --}}
    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h3 class="admin-section-title">🚚 {{ __('messages.settings_delivery_methods', ['count' => $store->deliveryMethods->count()]) }}</h3>
                <p class="{{ $helpClass }}">Active methods များက footer (အတိုချုပ်) + \"မှာယူနည်း\" + Order Builder တွင် ပြမည်။</p>
            </div>
            <button type="button" @click="showDelForm = !showDelForm; if (showDelForm) delForm = { name: '', type: 'custom', icon: '🚚', description: '', service_area: '', estimated_time: '', fee_note: '', is_active: true, sort_order: {{ $store->deliveryMethods->max('sort_order') + 1 }} }"
                class="shrink-0 inline-flex min-h-11 items-center rounded-xl border border-sky-300 bg-sky-50 px-4 py-2 text-xs font-black text-sky-700 hover:bg-sky-100 dark:border-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                + {{ __('messages.settings_add_delivery_method') }}
            </button>
        </div>

        <div x-show="showDelForm" x-cloak x-transition class="rounded-2xl border border-sky-200 bg-sky-50/40 p-4 space-y-3 dark:border-sky-800/50 dark:bg-sky-950/20">
            <div class="flex items-center justify-between">
                <p class="text-xs font-black text-sky-700 dark:text-sky-300">New Delivery Method</p>
                <button type="button" @click="showDelForm = false" class="rounded-lg px-2 py-1 text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">✕ Close</button>
            </div>
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings/delivery-methods') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="delForm.name" required maxlength="120" placeholder="e.g. Store Pickup / Bus Gate / Express" class="{{ $inputClass }}" />
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Type</label>
                        <select name="type" x-model="delForm.type" class="{{ $inputClass }}">
                            @foreach (['pickup' => 'Store pickup', 'courier' => 'Express courier', 'bus' => 'Bus gate delivery', 'local' => 'Local delivery', 'nationwide' => 'Nationwide delivery', 'custom' => 'Custom'] as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Icon (emoji)</label>
                        <input type="text" name="icon" x-model="delForm.icon" maxlength="10" placeholder="🚚" class="{{ $inputClass }}" />
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Estimated Time</label>
                        <input type="text" name="estimated_time" x-model="delForm.estimated_time" maxlength="120" placeholder="e.g. 1–3 days" class="{{ $inputClass }}" />
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Service Area</label>
                        <input type="text" name="service_area" x-model="delForm.service_area" maxlength="255" placeholder="e.g. Myanmar nationwide" class="{{ $inputClass }}" />
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Fee Note</label>
                        <input type="text" name="fee_note" x-model="delForm.fee_note" maxlength="255" placeholder="e.g. Depending on location and item size" class="{{ $inputClass }}" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">Description / Instructions</label>
                        <textarea name="description" x-model="delForm.description" rows="2" class="{{ $inputClass }}"></textarea>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="delForm.is_active" class="h-5 w-5 rounded border-gray-300 text-sky-600 focus:ring-sky-500" checked />
                        <span class="text-xs font-bold text-gray-700 dark:text-slate-200">Active</span>
                    </label>
                </div>
                <button type="submit" class="inline-flex min-h-12 items-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-70">
                    <span>{{ __('messages.add_delivery_method') }}</span>
                </button>
            </form>
        </div>

        @forelse ($store->deliveryMethods as $dm)
            <div class="bg-white dark:bg-slate-800/60 rounded-xl p-4 transition-colors duration-200">
                <div class="flex flex-wrap items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-lg dark:bg-slate-800">{{ $dm->icon ?: '🚚' }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-black text-gray-900 dark:text-slate-100">{{ $dm->name }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $dm->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ $dm->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="text-[10px] font-bold text-slate-400">sort {{ $dm->sort_order }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ collect([$dm->service_area, $dm->estimated_time, $dm->fee_note])->filter()->implode(' · ') }}
                        </p>
                        @if ($dm->description)
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $dm->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5">
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings/delivery-methods/' . $dm->id) }}" class="flex flex-wrap items-center gap-2" data-confirm="Update this delivery method?">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $dm->name }}" required maxlength="120" class="w-40 rounded-lg border border-slate-300 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" />
                            <select name="type" class="rounded-lg border border-slate-300 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800">
                                @foreach (['pickup' => 'Store pickup', 'courier' => 'Express courier', 'bus' => 'Bus gate delivery', 'local' => 'Local delivery', 'nationwide' => 'Nationwide delivery', 'custom' => 'Custom'] as $type => $label)
                                    <option value="{{ $type }}" {{ $dm->type === $type ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="icon" value="{{ $dm->icon }}" maxlength="10" class="w-12 rounded-lg border border-slate-300 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" title="Icon (emoji)" />
                            <input type="text" name="estimated_time" value="{{ $dm->estimated_time }}" maxlength="120" class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" placeholder="Est. time" />
                            <input type="text" name="service_area" value="{{ $dm->service_area }}" maxlength="255" class="w-32 rounded-lg border border-slate-300 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" placeholder="Area" />
                            <input type="text" name="fee_note" value="{{ $dm->fee_note }}" maxlength="255" class="w-36 rounded-lg border border-slate-300 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" placeholder="Fee note" />
                            <textarea name="description" rows="1" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" placeholder="Description">{{ $dm->description }}</textarea>
                            <input type="number" name="sort_order" value="{{ $dm->sort_order }}" class="w-16 rounded-lg border border-slate-300 px-2 py-1.5 text-xs dark:border-slate-600 dark:bg-slate-800" title="Sort order" />
                            <label class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-sky-600" {{ $dm->is_active ? 'checked' : '' }} /> Active
                            </label>
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-slate-800 px-3 py-2 text-xs font-black text-white hover:bg-slate-700">Save</button>
                        </form>
                        <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings/delivery-methods/' . $dm->id) }}" data-confirm="Delete {{ $dm->name }}?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex min-h-11 items-center gap-1 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-black text-white hover:bg-rose-700 active:bg-rose-800 cursor-pointer shadow-xs transition">🗑️ Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">No delivery methods yet.</p>
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Add one above — until then the storefront falls back to the legacy delivery note below.</p>
            </div>
        @endforelse

    </section>

    {{-- ============ LEGACY NOTES + FOOTER PROMOTION (standalone form) ============ --}}
    {{-- These fields live in their own form — the delivery section must not be
         nested inside the main settings form because it contains method-CRUD forms. --}}
    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings') }}" enctype="multipart/form-data"
        class="bg-white dark:bg-slate-800/60 rounded-xl p-4 space-y-4 transition-colors duration-200"
        x-data="{ submitting: false, footerAd: {{ json_encode(old('footer_ad_text', $setting->footer_ad_text ?? '')) }} }"
        @submit="submitting = true">
        @csrf
        <input type="hidden" name="section" value="delivery">

        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h3 class="admin-section-title">📝 {{ __('messages.settings_legacy_notes') }}</h3>
                <p class="{{ $helpClass }}">Structured methods မရှိသေးစဉ် ဒီ notes တွေကို fallback အဖြစ် သုံးမည်။ Methods ရှိပြီးရင် ဒီ notes တွေကို footer မှာ မပြတော့ဘဲ \"မှာယူနည်း\" မှာပဲ ပြမည်။</p>
            </div>
            <button type="submit" :disabled="submitting"
                class="shrink-0 inline-flex min-h-11 items-center justify-center rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-black text-white shadow-sm hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 disabled:opacity-70">
                <svg x-show="submitting" x-cloak class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <span x-show="submitting" x-cloak>Saving…</span>
                <span x-show="!submitting">{{ __('messages.settings_save_notes') }}</span>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}">General Payment Note (legacy fallback + extra guidance)</label>
                <textarea name="payment_info" rows="3" class="{{ $inputClass }}" placeholder="ငွေပေးချေခြင်းဆိုင်ရာ အထွေထွေ မှတ်ချက်">{{ old('payment_info', $setting->payment_info) }}</textarea>
                <p class="{{ $helpClass }}">Structured methods မရှိသေးစဉ် fallback အဖြစ် ပြမည်။</p>
                @error('payment_info')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="{{ $labelClass }}">General Delivery Note (legacy fallback)</label>
                <textarea name="delivery_info" rows="3" class="{{ $inputClass }}" placeholder="ပို့ဆောင်မှုဆိုင်ရာ အထွေထွေ မှတ်ချက်">{{ old('delivery_info', $setting->delivery_info) }}</textarea>
                <p class="{{ $helpClass }}">Structured methods မရှိသေးစဉ် fallback အဖြစ် ပြမည်။</p>
                @error('delivery_info')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="md:col-span-2">
            <h4 class="text-xs font-black text-gray-900 dark:text-slate-100">📢 Footer Promotion (bottom bar)</h4>
            <p class="{{ $helpClass }}">Clean, borderless footer ရဲ့ အောက်ဆုံး bar မှာ store name နဲ့အတူ ပြမည့် အတိုချုပ် ကြော်ညာ စာသား (max 255)။</p>
            <div class="mt-2">
                <label class="{{ $labelClass }}">Footer Ad Text</label>
                <textarea name="footer_ad_text" rows="2" maxlength="255" x-model="footerAd" class="{{ $inputClass }}" placeholder="ဥပမာ — ဆော့ဖ်ဝဲ မှာယူလိုပါက 09xxxxxxxxx ကို ဆက်သွယ်ပါ"></textarea>
                <div class="mt-1 flex items-center justify-between">
                    <p class="{{ $helpClass }}">မထည့်ထားပါက \"© {{ date('Y') }} DataPOS\" ပြပါမည်။</p>
                    <span class="text-[10px] font-bold text-slate-400" x-text="footerAd.length + ' / 255'"></span>
                </div>
                @error('footer_ad_text')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                    <p class="border-b border-slate-200 bg-slate-50 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-500">Footer preview (light, hairline divider)</p>
                    <div class="bg-white px-4 py-4 sm:px-6 dark:bg-slate-900">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $setting->store_name ?? $store->name }}</span>
                            <p class="max-w-xl text-center text-sm text-slate-500 sm:text-right dark:text-slate-400" x-text="footerAd || '© ' + new Date().getFullYear() + ' DataPOS'"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
