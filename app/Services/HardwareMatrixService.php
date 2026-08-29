<?php

namespace App\Services;

class HardwareMatrixService
{
    /**
     * Supported Hardware Profiles & Specifications.
     */
    public const SUPPORTED_HARDWARE = [
        'thermal_printers' => [
            '58mm_usb_pos'   => ['name' => '58mm USB POS Printer', 'width_chars' => 32, 'dpi' => 203, 'protocol' => 'ESC/POS', 'baud' => 9600],
            '80mm_lan_pos'   => ['name' => '80mm Network/LAN Thermal Printer', 'width_chars' => 48, 'dpi' => 203, 'protocol' => 'ESC/POS', 'port' => 9100],
            '58mm_bluetooth' => ['name' => '58mm Mobile Bluetooth Printer', 'width_chars' => 32, 'dpi' => 203, 'protocol' => 'ESC/POS', 'baud' => 9600],
            '80mm_bluetooth' => ['name' => '80mm Mobile Bluetooth Printer', 'width_chars' => 48, 'dpi' => 203, 'protocol' => 'ESC/POS', 'baud' => 115200],
        ],
        'barcode_scanners' => [
            '1d_usb_hid'      => ['name' => '1D USB Laser Scanner (HID Keyboard Emulation)', 'speed' => 'Instant', 'supported_symbologies' => ['EAN-13', 'Code 128', 'UPC-A']],
            '2d_wireless_hid' => ['name' => '2D QR & Barcode Wireless Scanner (Bluetooth / 2.4G)', 'speed' => 'Instant', 'supported_symbologies' => ['QR Code', 'DataMatrix', 'EAN-13', 'Code 128']],
            'camera_webcam'   => ['name' => 'Built-in Device Camera Scanner (HTML5 / PWA)', 'speed' => '100-300ms', 'supported_symbologies' => ['QR Code', 'EAN-13', 'Code 128']],
        ],
    ];

    /**
     * Generate raw ESC/POS test receipt bytes/commands for testing thermal printers.
     *
     * @param string $paperWidth '58mm' or '80mm'
     * @param string $storeName
     * @return string Raw ESC/POS bytes / hex representation
     */
    public static function generateEscPosTestReceipt(string $paperWidth = '80mm', string $storeName = 'DataPOS Demo Store'): string
    {
        $cols = $paperWidth === '58mm' ? 32 : 48;
        $esc = "\x1B";
        $gs  = "\x1D";

        $out = "";

        // 1. Initialize printer
        $out .= $esc . "@";

        // 2. Center Align & Double height header
        $out .= $esc . "a\x01"; // Center align
        $out .= $esc . "!\x30"; // Double width + double height
        $out .= $storeName . "\n";

        // 3. Normal font subtitle
        $out .= $esc . "!\x00"; // Normal font
        $out .= "Hardware Diagnostic Test Receipt\n";
        $out .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $out .= str_repeat('-', $cols) . "\n";

        // 4. Left Align Body
        $out .= $esc . "a\x00"; // Left align
        $out .= self::formatColumns("Printer Format:", strtoupper($paperWidth) . " ESC/POS", $cols) . "\n";
        $out .= self::formatColumns("Width:", $cols . " Characters/Line", $cols) . "\n";
        $out .= self::formatColumns("Hardware Status:", "READY [OK]", $cols) . "\n";
        $out .= str_repeat('-', $cols) . "\n";

        // 5. Test items
        $out .= self::formatColumns("1x Phone Screen Glass", "15,000 MMK", $cols) . "\n";
        $out .= self::formatColumns("1x 20W Fast Charger", "25,000 MMK", $cols) . "\n";
        $out .= str_repeat('-', $cols) . "\n";
        $out .= self::formatColumns("TOTAL AMOUNT:", "40,000 MMK", $cols) . "\n";
        $out .= str_repeat('=', $cols) . "\n";

        // 6. Center Barcode Test & QR Code
        $out .= $esc . "a\x01"; // Center
        $out .= "Scan Test Barcode:\n";
        $out .= $gs . "h\x50"; // Barcode height 80
        $out .= $gs . "w\x02"; // Barcode width 2
        $out .= $gs . "k\x04" . "DATAPOS2026\x00"; // Code39 / Code128 test
        $out .= "\nDATAPOS2026\n";

        $out .= "Thank You for Choosing DataPOS!\n";
        $out .= "\n\n\n";

        // 7. Paper cut
        $out .= $gs . "V\x41\x00"; // Full cut

        return $out;
    }

    /**
     * Format a two-column receipt line (e.g. Item Name on left, Price on right).
     */
    public static function formatColumns(string $left, string $right, int $totalWidth = 48): string
    {
        $spaces = $totalWidth - mb_strlen($left) - mb_strlen($right);
        if ($spaces < 1) {
            $spaces = 1;
        }

        return $left . str_repeat(' ', $spaces) . $right;
    }
}
