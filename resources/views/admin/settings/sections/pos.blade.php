<div class="p-4 sm:p-6 space-y-6">
    <div class="admin-section-head">
        <div>
            <h2 class="admin-section-title">🛒 {{ __('messages.settings_pos') }}</h2>
            <p class="admin-section-sub">Cashier အတွေ့အကြုံဆိုင်ရာ POS settings — held sale တွေရဲ့ သက်တမ်းကုန်ချိန်။</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label for="pos_hold_expiry_hours" class="{{ $labelClass }}">Held Sale Expiry (hours)</label>
            <input id="pos_hold_expiry_hours" type="number" name="pos_hold_expiry_hours" min="0" max="720" step="1"
                   value="{{ old('pos_hold_expiry_hours', $setting->pos_hold_expiry_hours ?? 24) }}"
                   placeholder="24" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">
                Hold လုပ်ထားတဲ့ sale တစ်ခု ဘယ်နှစ်နာရီကြာရင် အလိုအလျောက် သက်တမ်းကုန် (auto-void) မလဲ။
                ဗလာထားရင် 24 နာရီ (default)၊ 0 ထားရင် auto-expiry ပိတ်ပါတယ်။ (1–720)
            </p>
            @error('pos_hold_expiry_hours')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="pos_override_pin_threshold" class="{{ $labelClass }}">Price Override Manager PIN Threshold (%)</label>
            <input id="pos_override_pin_threshold" type="number" name="pos_override_pin_threshold" min="0" max="100" step="1"
                   value="{{ old('pos_override_pin_threshold', $setting->pos_override_pin_threshold ?? '') }}"
                   placeholder="Disabled" class="{{ $inputClass }}" />
            <p class="{{ $helpClass }}">
                Cashier က လိုင်းဈေးကို tier ဈေးထက် ဒီ % ထက် ပိုလျှော့ရင် (discount) **Manager PIN** ထည့်ရပါမယ်။
                ဗလာ/0 ထားရင် PIN မလိုပါ။ Manager/owner တွေရဲ့ PIN ကို <b>Users → Edit</b> မှာ သတ်မှတ်ပေးပါ။
            </p>
            @error('pos_override_pin_threshold')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
