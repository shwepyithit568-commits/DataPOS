{{--
    Horizontal chip-scroll row — shared by the POS module-links, category and
    brand rows. Single row of chips that scrolls sideways without a visible
    scrollbar.

    Props:
      @param string|null $label   Optional leading uppercase label (CATEGORIES / BRANDS).
      @param string      $variant 'chips' — always a single scrolling row with
                                  smooth scroll + chip snapping (categories/brands).
                                  'links' — wraps on desktop, becomes a single
                                  scrolling row on mobile only (module links).
      @param string      $class   Extra classes appended to the container.

    Slot: the chip buttons / links. Give each one `shrink-0` (and `snap-start`
    when using the 'chips' variant).
--}}
@props([
    'label' => null,
    'variant' => 'chips',
    'class' => '',
])

@php
    $variants = [
        'chips' => 'overflow-x-auto scroll-smooth overscroll-x-contain snap-x snap-proximity py-2.5',
        'links' => 'flex-wrap lg:flex-wrap max-lg:flex-nowrap max-lg:overflow-x-auto max-lg:whitespace-nowrap [&>*]:shrink-0 py-2.5',
    ];
    $base = 'flex items-center gap-2 px-4 border-t border-slate-100 dark:border-slate-800 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden';
@endphp

<div {{ $attributes->merge(['class' => trim("$base {$variants[$variant]} $class")]) }}>
    @if ($label)
        <span class="shrink-0 text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</span>
    @endif
    {{ $slot }}
</div>
