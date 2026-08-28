@php
    $currencyStore = $store ?? app(\App\Services\StoreContext::class)->getStore();
    $currencySetting = $currencyStore?->setting ?? null;
    $currencyRawConfig = $currencySetting?->currency_settings ?? [];
    $currencyFinalConfig = array_merge(
        \App\Support\CurrencyFormatter::DEFAULT_SETTINGS,
        array_filter($currencyRawConfig, fn($v) => $v !== null && $v !== '')
    );
@endphp
<script nonce="{{ $cspNonce ?? '' }}">
window.__currencyConfig = @json($currencyFinalConfig);
window.formatCurrency = function(val) {
    const cfg = window.__currencyConfig || {};
    const num = parseFloat(val) || 0;
    const isNeg = num < 0;
    const abs = Math.abs(num);
    const decimals = typeof cfg.decimal_places === 'number' ? cfg.decimal_places : 0;
    const fixed = abs.toFixed(decimals);
    const parts = fixed.split('.');
    
    let tSep = ',';
    if (cfg.thousand_separator === 'dot' || cfg.thousand_separator === '.') tSep = '.';
    else if (cfg.thousand_separator === 'space' || cfg.thousand_separator === ' ') tSep = ' ';
    else if (cfg.thousand_separator === 'none' || cfg.thousand_separator === '') tSep = '';

    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, tSep);
    
    const decSep = cfg.decimal_separator === ',' ? ',' : '.';
    const formatted = parts.join(decSep);
    const symbol = cfg.show_symbol !== false ? (cfg.currency_symbol || 'Ks') : '';
    
    let withSym = formatted;
    if (cfg.symbol_position === 'after_space') withSym = symbol ? formatted + ' ' + symbol : formatted;
    else if (cfg.symbol_position === 'after_tight') withSym = formatted + symbol;
    else if (cfg.symbol_position === 'before_space') withSym = symbol ? symbol + ' ' + formatted : formatted;
    else if (cfg.symbol_position === 'before_tight') withSym = symbol + formatted;
    else withSym = symbol ? formatted + ' ' + symbol : formatted;

    if (!isNeg) return withSym;
    if (cfg.negative_format === 'parentheses') return '(' + withSym + ')';
    if (cfg.negative_format === 'dr_cr') return withSym + ' (DR)';
    return '-' + withSym;
};
</script>
