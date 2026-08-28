<?php

namespace App\Support;

class CurrencyFormatter
{
    public const DEFAULT_SETTINGS = [
        'currency_code'        => 'MMK',
        'currency_symbol'      => 'Ks',
        'symbol_position'      => 'after_space', // 'after_space' (100,000 Ks), 'after_tight' (100,000Ks), 'before_space' (Ks 100,000), 'before_tight' ($100,000)
        'decimal_places'       => 0,             // 0, 1, 2, 3, 4
        'decimal_separator'    => '.',           // '.', ','
        'thousand_separator'   => ',',           // ',', '.', 'space', 'none'
        'negative_format'      => 'minus',       // 'minus' (-100 Ks), 'parentheses' ((100 Ks)), 'dr_cr' (100 Ks DR)
        'show_symbol'          => true,
    ];

    /**
     * Format a numerical amount into accounting/currency string according to configuration.
     */
    public static function format(float|int|string|null $amount, array $customSettings = []): string
    {
        $settings = array_merge(self::DEFAULT_SETTINGS, array_filter($customSettings, fn ($v) => $v !== null && $v !== ''));
        $val = (float) ($amount ?? 0);
        $isNegative = $val < 0;
        $absVal = abs($val);

        $thousandSep = match ($settings['thousand_separator']) {
            'dot', '.' => '.',
            'space', ' ' => ' ',
            'none', '' => '',
            default => ',',
        };

        $decimalSep = $settings['decimal_separator'] === ',' ? ',' : '.';
        $decimals = max(0, (int) ($settings['decimal_places'] ?? 0));

        $formattedNumber = number_format($absVal, $decimals, $decimalSep, $thousandSep);

        $symbol = $settings['show_symbol'] ? trim((string) ($settings['currency_symbol'] ?? 'Ks')) : '';
        $withSymbol = match ($settings['symbol_position']) {
            'before_tight' => $symbol . $formattedNumber,
            'before_space' => $symbol !== '' ? $symbol . ' ' . $formattedNumber : $formattedNumber,
            'after_tight'  => $formattedNumber . $symbol,
            default        => $symbol !== '' ? $formattedNumber . ' ' . $symbol : $formattedNumber,
        };

        if (! $isNegative) {
            return $withSymbol;
        }

        return match ($settings['negative_format']) {
            'parentheses' => '(' . $withSymbol . ')',
            'dr_cr'       => $withSymbol . ' (DR)',
            default       => '-' . $withSymbol,
        };
    }
}
