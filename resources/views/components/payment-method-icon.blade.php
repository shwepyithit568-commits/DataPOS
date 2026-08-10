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
    $builtins = [
        'kpay' => ['bg' => 'bg-violet-600', 'text' => 'K', 'label' => 'KPay'],
        'wavepay' => ['bg' => 'bg-cyan-500', 'text' => 'W', 'label' => 'WavePay'],
        'cbpay' => ['bg' => 'bg-emerald-500', 'text' => 'CB', 'label' => 'CB Pay'],
        'ayapay' => ['bg' => 'bg-amber-500', 'text' => 'A', 'label' => 'AYA Pay'],
        'mmqr' => ['bg' => 'bg-amber-600', 'text' => 'QR', 'label' => 'MMQR'],
        'bank' => ['bg' => 'bg-sky-600', 'text' => '🏦', 'label' => 'Bank Transfer'],
        'cod' => ['bg' => 'bg-rose-500', 'text' => '💵', 'label' => 'Cash on Delivery'],
        'cash' => ['bg' => 'bg-emerald-600', 'text' => '💵', 'label' => 'Cash'],
        'kpay-kbz' => ['bg' => 'bg-violet-600', 'text' => 'K', 'label' => 'KBZ Pay'],
        'kbz' => ['bg' => 'bg-violet-600', 'text' => 'K', 'label' => 'KBZ Pay'],
    ];
    $builtin = $builtins[strtolower((string) $value)] ?? null;
@endphp
@if ($type === 'custom' && $path && \App\Support\StorefrontAsset::imageUrl($path))
    <span class="{{ $class }} inline-flex items-center justify-center shrink-0">
        <img src="{{ \App\Support\StorefrontAsset::imageUrl($path) }}" alt="{{ $label }}"
             class="h-full w-full object-contain" loading="lazy" decoding="async" />
    </span>
@elseif ($type === 'builtin' && $builtin)
    <span class="{{ $class }} inline-flex items-center justify-center rounded-lg {{ $builtin['bg'] }} text-white font-black {{ $textClass }} shrink-0 select-none" aria-label="{{ $builtin['label'] }}" title="{{ $builtin['label'] }}">
        {{ $builtin['text'] }}
    </span>
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
