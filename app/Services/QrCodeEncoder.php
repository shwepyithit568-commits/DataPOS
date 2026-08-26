<?php

namespace App\Services;

/**
 * Pure PHP Self-Contained QR Code (Model 2) Vector SVG Generator.
 * Zero external dependencies. 100% offline compatible.
 */
class QrCodeEncoder
{
    private const GF256_PRIMITIVE = 0x11d; // x^8 + x^4 + x^3 + x^2 + 1

    private static array $expTable = [];
    private static array $logTable = [];
    private static bool $tablesInitialized = false;

    // Capacity & Error Correction table for Medium (M) level (Versions 1 to 6)
    // [version, totalCodewords, dataCodewords, ecCodewords, ecBlocks]
    private const VERSION_SPECS = [
        1 => ['total' => 26,  'data' => 16,  'ec' => 10, 'blocks' => 1, 'align' => []],
        2 => ['total' => 44,  'data' => 28,  'ec' => 16, 'blocks' => 1, 'align' => [6, 18]],
        3 => ['total' => 70,  'data' => 44,  'ec' => 26, 'blocks' => 1, 'align' => [6, 22]],
        4 => ['total' => 100, 'data' => 64,  'ec' => 18, 'blocks' => 2, 'align' => [6, 26]],
        5 => ['total' => 134, 'data' => 86,  'ec' => 24, 'blocks' => 2, 'align' => [6, 30]],
        6 => ['total' => 172, 'data' => 108, 'ec' => 16, 'blocks' => 4, 'align' => [6, 34]],
    ];

    // Format info bits for Mask 0 through 7 (with Error Correction Level M, XORed with 0x5412)
    private const FORMAT_INFO_M = [
        0 => 0x5412, // 000
        1 => 0x5125, // 001
        2 => 0x5E7C, // 010
        3 => 0x5B4B, // 011
        4 => 0x45F9, // 100
        5 => 0x40CE, // 101
        6 => 0x4F97, // 110
        7 => 0x4AA0, // 111
    ];

    /**
     * Generate standalone scalable vector SVG markup for given text.
     */
    public static function generateSvg(string $text, int $size = 80, int $quietZone = 2): string
    {
        $matrix = self::encodeToMatrix($text);
        $moduleCount = count($matrix);
        $totalSize = $moduleCount + ($quietZone * 2);

        $pathD = '';
        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = $c + $quietZone;
                    $y = $r + $quietZone;
                    $pathD .= "M{$x},{$y}h1v1h-1z ";
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="100%%" height="100%%" preserveAspectRatio="xMidYMid meet" shape-rendering="crispEdges">
    <rect width="100%%" height="100%%" fill="#ffffff"/>
    <path d="%s" fill="#000000"/>
</svg>',
            $totalSize,
            $totalSize,
            trim($pathD)
        );
    }

    /**
     * Encode text into 2D binary matrix grid ([row][col] => 1 for dark, 0 for light).
     */
    public static function encodeToMatrix(string $text): array
    {
        self::initTables();

        $text = trim($text);
        if ($text === '') {
            $text = '000000';
        }

        $dataBytes = unpack('C*', $text);
        $charCount = count($dataBytes);

        // Find smallest fitting QR version (M level)
        $version = 1;
        foreach (self::VERSION_SPECS as $v => $spec) {
            // Byte mode overhead: 4 bits mode + 8 bits count = 1.5 bytes -> data capacity - 2
            if ($charCount <= ($spec['data'] - 2)) {
                $version = $v;
                break;
            }
            $version = $v;
        }

        $spec = self::VERSION_SPECS[$version];
        $matrixSize = 21 + (($version - 1) * 4);

        // 1. Bitstream assembly
        $bits = '';
        // Byte mode indicator: 0100
        $bits .= '0100';
        // Character count indicator (8 bits for v1-v6)
        $bits .= str_pad(decbin($charCount), 8, '0', STR_PAD_LEFT);
        // Character data bits
        foreach ($dataBytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }
        // Terminator (up to 4 zeroes)
        $totalDataBits = $spec['data'] * 8;
        $terminatorLen = min(4, $totalDataBits - strlen($bits));
        if ($terminatorLen > 0) {
            $bits .= str_repeat('0', $terminatorLen);
        }
        // Pad to byte boundary
        while (strlen($bits) % 8 !== 0) {
            $bits .= '0';
        }
        // Pad bytes (0xEC, 0x11 alternating)
        $padBytes = [0xEC, 0x11];
        $padIndex = 0;
        while (strlen($bits) < $totalDataBits) {
            $bits .= str_pad(decbin($padBytes[$padIndex % 2]), 8, '0', STR_PAD_LEFT);
            $padIndex++;
        }

        // Convert data bits to data codewords
        $dataCodewords = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $dataCodewords[] = bindec(substr($bits, $i, 8));
        }

        // 2. Error Correction Generation
        $numBlocks = $spec['blocks'];
        $ecPerBlock = $spec['ec'];
        $dataPerBlock = (int) ($spec['data'] / $numBlocks);

        $blockData = [];
        $blockEc = [];
        $offset = 0;
        for ($b = 0; $b < $numBlocks; $b++) {
            $slice = array_slice($dataCodewords, $offset, $dataPerBlock);
            $offset += $dataPerBlock;
            $blockData[] = $slice;
            $blockEc[] = self::calculateReedSolomon($slice, $ecPerBlock);
        }

        // Interleave codewords
        $finalCodewords = [];
        for ($i = 0; $i < $dataPerBlock; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $finalCodewords[] = $blockData[$b][$i];
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $finalCodewords[] = $blockEc[$b][$i];
            }
        }

        // Convert final codewords to bit array
        $finalBits = [];
        foreach ($finalCodewords as $cw) {
            for ($b = 7; $b >= 0; $b--) {
                $finalBits[] = ($cw >> $b) & 1;
            }
        }

        // 3. Matrix Construction
        $matrix = array_fill(0, $matrixSize, array_fill(0, $matrixSize, null));
        $isFunction = array_fill(0, $matrixSize, array_fill(0, $matrixSize, false));

        // Place Finder Patterns
        self::placeFinderPattern($matrix, $isFunction, 0, 0);
        self::placeFinderPattern($matrix, $isFunction, 0, $matrixSize - 7);
        self::placeFinderPattern($matrix, $isFunction, $matrixSize - 7, 0);

        // Place Separators
        self::placeSeparators($matrix, $isFunction, $matrixSize);

        // Place Alignment Patterns
        if (!empty($spec['align'])) {
            self::placeAlignmentPatterns($matrix, $isFunction, $spec['align']);
        }

        // Place Timing Patterns
        for ($i = 8; $i < $matrixSize - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            if ($matrix[6][$i] === null) {
                $matrix[6][$i] = $val;
                $isFunction[6][$i] = true;
            }
            if ($matrix[$i][6] === null) {
                $matrix[$i][6] = $val;
                $isFunction[$i][6] = true;
            }
        }

        // Dark module
        $matrix[$matrixSize - 8][8] = 1;
        $isFunction[$matrixSize - 8][8] = true;

        // Reserve Format Information Areas
        for ($i = 0; $i < 9; $i++) {
            $isFunction[8][$i] = true;
            $isFunction[$i][8] = true;
            $isFunction[8][$matrixSize - 1 - $i] = true;
            $isFunction[$matrixSize - 1 - $i][8] = true;
        }

        // 4. Place Data Bits (Zig-zag upwards & downwards)
        $bitIdx = 0;
        $numBits = count($finalBits);
        for ($right = $matrixSize - 1; $right > 0; $right -= 2) {
            if ($right === 6) {
                $right--; // Skip vertical timing pattern
            }
            $upward = (($right + 1) / 2) % 2 === 1;
            for ($vert = 0; $vert < $matrixSize; $vert++) {
                $r = $upward ? ($matrixSize - 1 - $vert) : $vert;
                for ($cOffset = 0; $cOffset < 2; $cOffset++) {
                    $c = $right - $cOffset;
                    if (! $isFunction[$r][$c]) {
                        $bitVal = $bitIdx < $numBits ? $finalBits[$bitIdx++] : 0;
                        $matrix[$r][$c] = $bitVal;
                    }
                }
            }
        }

        // 5. Apply Mask Pattern (Mask 0: (r + c) % 2 == 0)
        $maskId = 0;
        for ($r = 0; $r < $matrixSize; $r++) {
            for ($c = 0; $c < $matrixSize; $c++) {
                if (! $isFunction[$r][$c]) {
                    if (($r + $c) % 2 === 0) {
                        $matrix[$r][$c] ^= 1;
                    }
                }
            }
        }

        // 6. Write Format Info (Mask 0 + Error Correction M)
        $formatBits = self::FORMAT_INFO_M[$maskId];
        // Around top-left finder
        for ($i = 0; $i < 6; $i++) {
            $matrix[8][$i] = ($formatBits >> (14 - $i)) & 1;
        }
        $matrix[8][7] = ($formatBits >> 8) & 1;
        $matrix[8][8] = ($formatBits >> 7) & 1;
        $matrix[7][8] = ($formatBits >> 6) & 1;
        for ($i = 0; $i < 6; $i++) {
            $matrix[5 - $i][8] = ($formatBits >> (5 - $i)) & 1;
        }

        // Around bottom-left & top-right
        for ($i = 0; $i < 7; $i++) {
            $matrix[$matrixSize - 1 - $i][8] = ($formatBits >> $i) & 1;
        }
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$matrixSize - 8 + $i] = ($formatBits >> (7 + $i)) & 1;
        }

        return $matrix;
    }

    private static function placeFinderPattern(array &$matrix, array &$isFunction, int $startR, int $startC): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $isDark = ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4));
                $matrix[$startR + $r][$startC + $c] = $isDark ? 1 : 0;
                $isFunction[$startR + $r][$startC + $c] = true;
            }
        }
    }

    private static function placeSeparators(array &$matrix, array &$isFunction, int $size): void
    {
        // Top-left
        for ($i = 0; $i < 8; $i++) {
            if ($i < $size) {
                $matrix[7][$i] = 0;
                $isFunction[7][$i] = true;
                $matrix[$i][7] = 0;
                $isFunction[$i][7] = true;
            }
        }
        // Top-right
        for ($i = 0; $i < 8; $i++) {
            $c = $size - 8 + $i;
            if ($c >= 0 && $c < $size) {
                $matrix[7][$c] = 0;
                $isFunction[7][$c] = true;
                $matrix[$i][$size - 8] = 0;
                $isFunction[$i][$size - 8] = true;
            }
        }
        // Bottom-left
        for ($i = 0; $i < 8; $i++) {
            $r = $size - 8 + $i;
            if ($r >= 0 && $r < $size) {
                $matrix[$r][7] = 0;
                $isFunction[$r][7] = true;
                $matrix[$size - 8][$i] = 0;
                $isFunction[$size - 8][$i] = true;
            }
        }
    }

    private static function placeAlignmentPatterns(array &$matrix, array &$isFunction, array $coords): void
    {
        foreach ($coords as $r) {
            foreach ($coords as $c) {
                // Skip if overlapping with finder patterns
                if (($r <= 8 && $c <= 8) || ($r <= 8 && $c >= count($matrix) - 8) || ($r >= count($matrix) - 8 && $c <= 8)) {
                    continue;
                }
                for ($dr = -2; $dr <= 2; $dr++) {
                    for ($dc = -2; $dc <= 2; $dc++) {
                        $isDark = (abs($dr) === 2 || abs($dc) === 2 || ($dr === 0 && $dc === 0));
                        $matrix[$r + $dr][$c + $dc] = $isDark ? 1 : 0;
                        $isFunction[$r + $dr][$c + $dc] = true;
                    }
                }
            }
        }
    }

    private static function calculateReedSolomon(array $data, int $ecLength): array
    {
        // Build generator polynomial
        $generator = [1];
        for ($i = 0; $i < $ecLength; $i++) {
            $generator = self::polyMultiply($generator, [1, self::$expTable[$i]]);
        }

        $poly = array_pad($data, count($data) + $ecLength, 0);

        for ($i = 0; $i < count($data); $i++) {
            $coef = $poly[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($generator); $j++) {
                    $poly[$i + $j] ^= self::gfMultiply($generator[$j], $coef);
                }
            }
        }

        return array_slice($poly, count($data));
    }

    private static function initTables(): void
    {
        if (self::$tablesInitialized) {
            return;
        }

        self::$expTable = array_fill(0, 512, 0);
        self::$logTable = array_fill(0, 256, 0);

        $val = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$expTable[$i] = $val;
            self::$logTable[$val] = $i;
            $val <<= 1;
            if ($val & 0x100) {
                $val ^= self::GF256_PRIMITIVE;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$expTable[$i] = self::$expTable[$i - 255];
        }

        self::$tablesInitialized = true;
    }

    private static function gfMultiply(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }
        return self::$expTable[self::$logTable[$x] + self::$logTable[$y]];
    }

    private static function polyMultiply(array $p, array $q): array
    {
        $result = array_fill(0, count($p) + count($q) - 1, 0);
        for ($i = 0; $i < count($p); $i++) {
            for ($j = 0; $j < count($q); $j++) {
                $result[$i + $j] ^= self::gfMultiply($p[$i], $q[$j]);
            }
        }
        return $result;
    }
}
