@props([
    'code' => 'my',
    'size' => 'h-5 w-5',
])

@php
    // Unique clipPath id per render so multiple flags on one page never collide.
    $id = 'flag-' . $code . '-' . \Illuminate\Support\Str::random(6);
    $paths = [
        'my' => '<path fill="#fECB00" d="M2 2h20v6.67H2z"/><path fill="#34B233" d="M2 8.67h20v6.66H2z"/><path fill="#EA2839" d="M2 15.33h20V22H2z"/><path fill="#fff" d="m12 6.6 1.52 3.08 3.4.49-2.46 2.4.58 3.38L12 14.35l-3.04 1.6.58-3.38-2.46-2.4 3.4-.49L12 6.6z"/>',
        'en' => '<path fill="#012169" d="M2 2h20v20H2z"/><path stroke="#fff" stroke-width="5" d="m2 2 20 20M22 2 2 22"/><path stroke="#C8102E" stroke-width="3" d="m2 2 20 20M22 2 2 22"/><path fill="#fff" d="M10 2h4v20h-4zM2 10h20v4H2z"/><path fill="#C8102E" d="M10.8 2h2.4v20h-2.4zM2 10.8h20v2.4H2z"/>',
        'zh_CN' => '<path fill="#DE2910" d="M2 2h20v20H2z"/><path fill="#FFDE00" d="m7.2 5.4.9 2.75H11L8.65 9.86l.9 2.74-2.35-1.7-2.35 1.7.9-2.74L3.4 8.15h2.9l.9-2.75zM13.3 5.4l.32.83.88.05-.68.55.22.86-.74-.46-.74.46.22-.86-.68-.55.88-.05.32-.83zM16.1 8l.32.83.88.05-.68.55.22.86-.74-.46-.74.46.22-.86-.68-.55.88-.05.32-.83zM16 11.4l.32.83.88.05-.68.55.22.86-.74-.46-.74.46.22-.86-.68-.55.88-.05.32-.83zM13.2 14l.32.83.88.05-.68.55.22.86-.74-.46-.74.46.22-.86-.68-.55.88-.05.32-.83z"/>',
    ];
@endphp

<svg viewBox="0 0 24 24" class="{{ $size }} rounded-full shadow-sm ring-1 ring-slate-200 dark:ring-slate-700" aria-hidden="true">
    <defs>
        <clipPath id="{{ $id }}">
            <circle cx="12" cy="12" r="10" />
        </clipPath>
    </defs>
    <g clip-path="url(#{{ $id }})">
        @if (isset($paths[$code]))
            {!! $paths[$code] !!}
        @else
            <circle cx="12" cy="12" r="10" fill="#e2e8f0" />
        @endif
    </g>
</svg>
