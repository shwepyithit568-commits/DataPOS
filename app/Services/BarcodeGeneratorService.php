<?php

namespace App\Services;

class BarcodeGeneratorService
{
    /**
     * Code 128 pattern table (patterns of 6 digits: 3 bars and 3 spaces width, sum = 11 modules).
     * Index 0 to 106.
     */
    private const CODE128_PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213', // 0-9
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132', // 10-19
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211', // 20-29
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313', // 30-39
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331', // 40-49
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111', // 50-59
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214', // 60-69
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111', // 70-79
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141', // 80-89
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141', // 90-99
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112' // 100-106 (106 is STOP pattern: 7 digits)
    ];

    /**
     * Generate Code 128 (Type B) Barcode as scalable vector SVG string.
     *
     * @param string $text The text/code to encode
     * @param int $height Height of bars in pixels (default 50)
     * @param float $barWidth Width of single module in pixels (default 2)
     * @param bool $showText Whether to display text beneath barcode
     * @return string SVG markup string
     */
    public function generateCode128Svg(string $text, int $height = 50, float $barWidth = 1.8, bool $showText = true): string
    {
        $text = trim($text);
        if ($text === '') {
            $text = '000000';
        }

        // Start Code B is index 104
        $startCode = 104;
        $values = [$startCode];
        $checksum = $startCode;

        // Encode characters in Code B (ASCII 32 to 126)
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];
            $ascii = ord($char);
            $val = $ascii >= 32 && $ascii <= 126 ? $ascii - 32 : 0;
            $values[] = $val;
            $checksum += $val * ($i + 1);
        }

        // Checksum modulo 103
        $checkVal = $checksum % 103;
        $values[] = $checkVal;

        // Stop Code is index 106
        $values[] = 106;

        // Build bars sequence
        $modules = '';
        foreach ($values as $val) {
            $pattern = self::CODE128_PATTERNS[$val] ?? self::CODE128_PATTERNS[0];
            $isBar = true;
            $patLen = strlen($pattern);
            for ($p = 0; $p < $patLen; $p++) {
                $count = (int) $pattern[$p];
                $modules .= str_repeat($isBar ? '1' : '0', $count);
                $isBar = ! $isBar;
            }
        }

        // Total width in modules
        $totalModules = strlen($modules);
        $quietZone = 10; // 10 modules on each side
        $svgWidth = ($totalModules + ($quietZone * 2)) * $barWidth;
        $svgHeight = $height + ($showText ? 16 : 0);

        // Generate SVG rects
        $x = $quietZone * $barWidth;
        $rects = [];
        for ($m = 0; $m < $totalModules; $m++) {
            if ($modules[$m] === '1') {
                // Find consecutive bars
                $run = 1;
                while ($m + 1 < $totalModules && $modules[$m + 1] === '1') {
                    $run++;
                    $m++;
                }
                $width = $run * $barWidth;
                $rects[] = sprintf('<rect x="%.2f" y="0" width="%.2f" height="%d" fill="#000000" />', $x, $width, $height);
                $x += $width;
            } else {
                $x += $barWidth;
            }
        }

        $rectsHtml = implode("\n    ", $rects);
        $textHtml = '';
        if ($showText) {
            $textHtml = sprintf(
                '<text x="%.2f" y="%d" text-anchor="middle" font-family="monospace" font-size="11" font-weight="bold" fill="#000000">%s</text>',
                $svgWidth / 2,
                $height + 12,
                htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %.2f %d" width="100%%" height="100%%" preserveAspectRatio="xMidYMid meet">
    <rect width="100%%" height="100%%" fill="#ffffff" />
    %s
    %s
</svg>',
            $svgWidth,
            $svgHeight,
            $rectsHtml,
            $textHtml
        );
    }

    /**
     * Generate SVG QR Code representation using clean native vector matrix generator.
     */
    public function generateQrCodeSvg(string $text, int $size = 80): string
    {
        return QrCodeEncoder::generateSvg($text, $size);
    }
}
