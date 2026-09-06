@props([
    'product',
    'store',
    'isWholesaleApproved' => false,
    'dense' => false,
    'rounded' => 'rounded-2xl',
])

{{--
  Product card — thin dispatcher into the theme's approved card variant
  (ThemePlan §6.2 component.product_card_variant). Callers keep using
  <x-product-card :product=... :store=...> — no per-theme query or data
  contract change; only the presentation partial differs.

  Variants live in components/product-card-variants/{variant}.blade.php.
  Unknown/missing variant falls back to the safe 'compact' partial.
--}}
@php
    $themePreset = $store?->setting?->theme_preset
        ?? $store?->theme_preset
        ?? \App\Themes\ThemeRegistry::getDefault()->id;
    $variant = \App\Themes\ThemeComponents::resolve($themePreset, 'product_card_variant');
    if (! view()->exists('components.product-card-variants.' . $variant)) {
        $variant = 'compact';
    }
@endphp

@include('components.product-card-variants.' . $variant, [
    'product'              => $product,
    'store'                => $store,
    'isWholesaleApproved'  => $isWholesaleApproved,
    'dense'                => $dense,
    'rounded'              => $rounded,
])
