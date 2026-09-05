@props([
    'method' => null,      // StorePaymentMethod model
    'iconType' => null,    // builtin | custom | initials (fallback)
    'iconValue' => null,   // builtin key or emoji
    'iconPath' => null,    // custom uploaded icon path
    'name' => '',
    'class' => 'h-7 w-7',
    'textClass' => 'text-[10px]',
])
@php
    $type = $iconType ?? $method?->icon_type ?? 'initials';
    $value = $iconValue ?? $method?->icon_value ?? '';
    $path = $iconPath ?? $method?->icon_path ?? null;
    $label = $name ?: ($method?->name ?? 'Pay');
    $key = strtolower(trim((string) $value));
@endphp
@if ($type === 'custom' && $path && \App\Support\StorefrontAsset::imageUrl($path))
    <span class="{{ $class }} inline-flex items-center justify-center shrink-0">
        <img src="{{ \App\Support\StorefrontAsset::imageUrl($path) }}" alt="{{ $label }}"
             class="h-full w-full object-contain" loading="lazy" decoding="async" />
    </span>
@elseif ($type === 'builtin' || !empty($key))
    @if (in_array($key, ['kpay', 'kbz', 'kpay-kbz', 'kbzpay']))
        {{-- KBZPay / KPay Official Brand Badge --}}
        <span class="{{ $class }} inline-flex items-center justify-center shrink-0 shadow-2xs rounded-lg overflow-hidden select-none" title="KBZPay / KPay" aria-label="KBZPay">
            <svg viewBox="0 0 48 48" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="10" fill="#0055B8"/>
                <path d="M13 11H20V21.5L28.5 11H36L25.5 24L37 37H29.5L20 25.5V37H13V11Z" fill="#FFFFFF"/>
            </svg>
        </span>
    @elseif (in_array($key, ['wavepay', 'wave', 'wavemoney']))
        {{-- WavePay Official Brand Badge --}}
        <span class="{{ $class }} inline-flex items-center justify-center shrink-0 shadow-2xs rounded-lg overflow-hidden select-none" title="WavePay" aria-label="WavePay">
            <svg viewBox="0 0 48 48" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="10" fill="#FFCC00"/>
                <path d="M10 16L15 32H19.5L23 21L25 28L28.5 16H33.5L28 32H23.5L20.5 22.5L17.5 32H13.5L8.5 16H10Z" fill="#0099DA"/>
                <path d="M26 16L30.5 29L34 16H39.5L33.5 32H29L24.5 19L26 16Z" fill="#0099DA"/>
            </svg>
        </span>
    @elseif (in_array($key, ['cbpay', 'cb', 'cbbank']))
        {{-- CB Pay Official Brand Badge --}}
        <span class="{{ $class }} inline-flex items-center justify-center shrink-0 shadow-2xs rounded-lg overflow-hidden select-none" title="CB Pay" aria-label="CB Pay">
            <svg viewBox="0 0 48 48" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="10" fill="#DF1E26"/>
                <text x="24" y="29" fill="#FFFFFF" font-family="Arial, sans-serif" font-weight="900" font-size="18" text-anchor="middle" letter-spacing="-0.5">CB</text>
                <rect x="10" y="34" width="28" height="3" rx="1.5" fill="#FED100"/>
            </svg>
        </span>
    @elseif (in_array($key, ['ayapay', 'aya', 'ayabank']))
        {{-- AYA Pay Official Brand Badge --}}
        <span class="{{ $class }} inline-flex items-center justify-center shrink-0 shadow-2xs rounded-lg overflow-hidden select-none" title="AYA Pay" aria-label="AYA Pay">
            <svg viewBox="0 0 48 48" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="10" fill="#E31B23"/>
                <text x="24" y="28" fill="#FFFFFF" font-family="Arial, sans-serif" font-weight="900" font-size="12" text-anchor="middle" letter-spacing="0.5">AYA</text>
                <circle cx="24" cy="35" r="2.5" fill="#FED100"/>
            </svg>
        </span>
    @elseif (in_array($key, ['mmqr', 'qr', 'promptpay']))
        {{-- MMQR (Myanmar National Standard) Official Brand Badge --}}
        <span class="{{ $class }} inline-flex items-center justify-center shrink-0 shadow-2xs rounded-lg overflow-hidden select-none" title="MMQR (National Standard QR)" aria-label="MMQR">
            <svg viewBox="0 0 48 48" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="10" fill="#D0021B"/>
                <text x="24" y="22" fill="#FED100" font-family="Arial, sans-serif" font-weight="900" font-size="9" text-anchor="middle" letter-spacing="0.5">MMQR</text>
                <rect x="14" y="25" width="20" height="14" rx="2" fill="#FFFFFF"/>
                <rect x="17" y="28" width="4" height="4" fill="#D0021B"/>
                <rect x="27" y="28" width="4" height="4" fill="#D0021B"/>
                <rect x="22" y="33" width="4" height="4" fill="#D0021B"/>
            </svg>
        </span>
    @elseif (in_array($key, ['cod', 'cash_on_delivery']))
        {{-- Cash on Delivery Brand Badge --}}
        <span class="{{ $class }} inline-flex items-center justify-center shrink-0 shadow-2xs rounded-lg overflow-hidden select-none" title="Cash on Delivery" aria-label="Cash on Delivery">
            <svg viewBox="0 0 48 48" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="10" fill="#059669"/>
                <text x="24" y="29" fill="#FFFFFF" font-family="Arial, sans-serif" font-weight="900" font-size="14" text-anchor="middle" letter-spacing="-0.5">COD</text>
                <circle cx="24" cy="36" r="2" fill="#34D399"/>
            </svg>
        </span>
    @elseif (in_array($key, ['bank', 'bank_transfer', 'mab', 'uab', 'yoma']))
        {{-- Bank Transfer Brand Badge --}}
        <span class="{{ $class }} inline-flex items-center justify-center shrink-0 shadow-2xs rounded-lg overflow-hidden select-none" title="Bank Transfer" aria-label="Bank Transfer">
            <svg viewBox="0 0 48 48" class="w-full h-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="10" fill="#0284C7"/>
                <path d="M24 11L11 18V21H37V18L24 11ZM14 23V32H17V23H14ZM21 23V32H24V23H21ZM28 23V32H31V23H28ZM10 34V37H38V34H10Z" fill="#FFFFFF"/>
            </svg>
        </span>
    @else
        <span class="{{ $class }} inline-flex items-center justify-center rounded-lg bg-slate-800 dark:bg-slate-700 text-white font-black {{ $textClass }} shrink-0 select-none" aria-label="{{ $label }}" title="{{ $label }}">
            {{ mb_strtoupper(mb_substr($label, 0, 2)) }}
        </span>
    @endif
@elseif ($value && mb_strlen(trim($value)) <= 4 && ! ctype_alnum(str_replace(['-', '_'], '', $value)))
    {{-- Emoji / short symbol icon --}}
    <span class="{{ $class }} inline-flex items-center justify-center rounded-lg bg-slate-200 dark:bg-slate-700 text-base shrink-0" aria-label="{{ $label }}" title="{{ $label }}">
        {{ $value }}
    </span>
@else
    {{-- Initials / text fallback --}}
    <span class="{{ $class }} inline-flex items-center justify-center rounded-lg bg-slate-800 dark:bg-slate-700 text-white font-black {{ $textClass }} shrink-0 select-none" aria-label="{{ $label }}" title="{{ $label }}">
        {{ mb_strtoupper(mb_substr($label, 0, 2)) }}
    </span>
@endif
