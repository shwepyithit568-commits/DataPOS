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
        $out .= self::generatePaperCutCommand(false);

        return $out;
    }

    /**
     * Generate standard ESC/POS Cash Drawer Kick pulse command.
     * Pulse: ESC p m t1 t2
     *
     * @param int $pin 0 = Pin 2 (standard drawer), 1 = Pin 5 (secondary drawer)
     * @param int $onTimePulse Units of 2ms (25 = 50ms pulse)
     * @param int $offTimePulse Units of 2ms (250 = 500ms pause)
     */
    public static function generateCashDrawerKickCommand(int $pin = 0, int $onTimePulse = 25, int $offTimePulse = 250): string
    {
        $m = ($pin === 1) ? "\x01" : "\x00";
        return "\x1B\x70" . $m . chr(min(255, max(1, $onTimePulse))) . chr(min(255, max(1, $offTimePulse)));
    }

    /**
     * Generate standard ESC/POS Paper Cut command.
     *
     * @param bool $partial True for partial cut, false for full cut
     */
    public static function generatePaperCutCommand(bool $partial = false): string
    {
        return $partial ? "\x1D\x56\x42\x00" : "\x1D\x56\x41\x00";
    }

    /**
     * Generate live ESC/POS receipt for a posted POS sale.
     */
    public static function generateEscPosSaleReceipt(\App\POS\Models\PosSale $sale, string $paperWidth = '80mm', bool $withDrawerKick = true): string
    {
        $cols = ($paperWidth === '58mm') ? 32 : 48;
        $esc = "\x1B";
        $gs  = "\x1D";

        $out = "";

        // Optional cash drawer kick
        if ($withDrawerKick) {
            $out .= self::generateCashDrawerKickCommand();
        }

        // Initialize printer
        $out .= $esc . "@";

        // Store header (Center align + bold)
        $storeName = $sale->store?->name ?? 'DataPOS Store';
        $out .= $esc . "a\x01"; // Center align
        $out .= $esc . "!\x30"; // Double width + height
        $out .= $storeName . "\n";

        $out .= $esc . "!\x00"; // Normal font
        if (! empty($sale->store?->setting?->address)) {
            $out .= $sale->store->setting->address . "\n";
        }
        if (! empty($sale->store?->setting?->phone)) {
            $out .= "Tel: " . $sale->store->setting->phone . "\n";
        }

        $out .= str_repeat('-', $cols) . "\n";

        // Invoice metadata (Left align)
        $out .= $esc . "a\x00";
        $out .= self::formatColumns("Invoice:", $sale->receipt_number, $cols) . "\n";
        $out .= self::formatColumns("Date:", $sale->posted_at?->format('Y-m-d H:i') ?? date('Y-m-d H:i'), $cols) . "\n";
        $out .= self::formatColumns("Cashier:", $sale->cashier?->name ?? 'Cashier', $cols) . "\n";
        if ($sale->customer) {
            $out .= self::formatColumns("Customer:", $sale->customer->name, $cols) . "\n";
        }
        $out .= str_repeat('-', $cols) . "\n";

        // Items
        foreach ($sale->items as $item) {
            $lineDesc = $item->quantity . "x " . $item->product_name;
            $lineTotal = number_format((float) $item->line_total) . " MMK";
            $out .= self::formatColumns($lineDesc, $lineTotal, $cols) . "\n";
        }

        $out .= str_repeat('-', $cols) . "\n";

        // Totals
        $out .= self::formatColumns("Subtotal:", number_format((float) $sale->subtotal) . " MMK", $cols) . "\n";
        if ((float) $sale->discount > 0) {
            $out .= self::formatColumns("Discount:", "−" . number_format((float) $sale->discount) . " MMK", $cols) . "\n";
        }
        $out .= str_repeat('=', $cols) . "\n";
        $out .= self::formatColumns("TOTAL:", number_format((float) $sale->total) . " MMK", $cols) . "\n";
        $out .= str_repeat('=', $cols) . "\n";

        // Payments
        foreach ($sale->payments as $p) {
            $methodName = strtoupper($p->method);
            $payInfo = number_format((float) $p->amount) . " MMK";
            if (! empty($p->reference)) {
                $payInfo .= " [Ref: {$p->reference}]";
            }
            $out .= self::formatColumns("Paid (" . $methodName . "):", $payInfo, $cols) . "\n";
            if ((float) $p->change_given > 0) {
                $out .= self::formatColumns("Change Given:", number_format((float) $p->change_given) . " MMK", $cols) . "\n";
            }
        }

        $out .= "\n";
        $out .= $esc . "a\x01"; // Center align
        $out .= "Thank You! Please Visit Again.\n";

        // Barcode
        $out .= $gs . "h\x40"; // Height 64
        $out .= $gs . "w\x02";
        $out .= $gs . "k\x04" . $sale->receipt_number . "\x00";
        $out .= "\n*" . $sale->receipt_number . "*\n";

        $out .= "\n\n\n";
        $out .= self::generatePaperCutCommand(false);

        return $out;
    }

    /**
     * Diagnostic profiles for self-service hardware verification.
     */
    public static function diagnoseHardwareProfiles(): array
    {
        return [
            'profiles' => self::SUPPORTED_HARDWARE,
            'esc_pos_commands' => [
                'initialize'     => '\x1B\x40 (ESC @)',
                'drawer_kick'    => '\x1B\x70\x00\x19\xFA (ESC p 0 25 250)',
                'full_cut'       => '\x1D\x56\x41\x00 (GS V 65 0)',
                'partial_cut'    => '\x1D\x56\x42\x00 (GS V 66 0)',
                'barcode_code39' => '\x1D\x6B\x04 (GS k 4)',
            ],
            'scanner_spec' => [
                'mode'           => 'HID Keyboard Wedge (Standard USB / 2.4G Wireless)',
                'suffix'         => 'CR (Enter) Carriage Return - Keycode 13',
                'inter_char_delay_ms' => 5,
                'min_scan_speed_chars_per_sec' => 50,
            ],
        ];
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
