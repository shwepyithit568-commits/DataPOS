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
window.formatQuantity = function(val) {
    const cfg = window.__currencyConfig || {};
    const num = parseFloat(val) || 0;
    const qtyDecimals = cfg.qty_decimal_places !== undefined ? cfg.qty_decimal_places : 'auto';
    const trimZeros = cfg.qty_trim_zeros !== undefined ? Boolean(cfg.qty_trim_zeros) : true;
    const decSep = cfg.decimal_separator === ',' ? ',' : '.';
    let tSep = ',';
    if (cfg.thousand_separator === 'dot' || cfg.thousand_separator === '.') tSep = '.';
    else if (cfg.thousand_separator === 'space' || cfg.thousand_separator === ' ') tSep = ' ';
    else if (cfg.thousand_separator === 'none' || cfg.thousand_separator === '') tSep = '';

    const formatWithParts = (n, decs) => {
        const fixed = n.toFixed(decs);
        const parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, tSep);
        if (decs > 0 && trimZeros) {
            let decimalPart = parts[1].replace(/0+$/, '');
            return decimalPart.length > 0 ? parts[0] + decSep + decimalPart : parts[0];
        }
        return decs > 0 ? parts.join(decSep) : parts[0];
    };

    if (qtyDecimals === 'auto') {
        if (Number.isInteger(num)) {
            return formatWithParts(num, 0);
        }
        return formatWithParts(num, 3);
    }

    const d = Math.max(0, parseInt(qtyDecimals) || 0);
    return formatWithParts(num, d);
};
window.format_currency = window.formatCurrency;
window.format_quantity = window.formatQuantity;
</script>
