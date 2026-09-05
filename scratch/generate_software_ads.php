<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

$fontBold = resource_path('assets/fonts/Outfit/Outfit-SemiBold.ttf');
$fontRegular = resource_path('assets/fonts/Outfit/Outfit-Regular.ttf');
$fontMedium = resource_path('assets/fonts/Outfit/Outfit-Medium.ttf');

$disk = Storage::disk('public');
$disk->makeDirectory('placeholders');

$ads = [
    1 => [
        'bg_top'     => [15, 23, 42],    // Deep Slate
        'bg_mid'     => [3, 105, 161],   // Sky 700
        'bg_bot'     => [12, 74, 110],   // Dark Navy
        'accent'     => [56, 189, 248],  // Cyan Glow
        'pill_text'  => 'ALL-IN-ONE SMART POS',
        'title'      => 'DataPOS System',
        'tagline'    => 'Modern Retail & Mobile Sales',
        'bullets'    => [
            ['01', 'Mobile, Tablet & Desktop Support'],
            ['02', 'Instant Barcode Scanning & Fast Billing'],
            ['03', '58mm & 80mm ESC/POS Printing'],
            ['04', 'Integrated E-Commerce Storefront'],
        ],
        'footer'     => 'OFFICIAL DATAPOS SOFTWARE SOLUTION',
    ],
    2 => [
        'bg_top'     => [6, 78, 59],     // Emerald 950
        'bg_mid'     => [4, 120, 87],    // Emerald 700
        'bg_bot'     => [6, 78, 59],     // Dark Green
        'accent'     => [52, 211, 153],  // Mint Glow
        'pill_text'  => 'INVENTORY & WAREHOUSE',
        'title'      => 'DataPOS Cloud',
        'tagline'    => 'Real-time Stock Management',
        'bullets'    => [
            ['01', 'Real-time Multi-Warehouse Stock'],
            ['02', 'Cross-Store Stock Transfer & Ledger'],
            ['03', 'Low Stock & Reorder Auto Alerts'],
            ['04', 'Offline-First Resilient Architecture'],
        ],
        'footer'     => 'ZERO DATA LOSS • CLOUD & OFFLINE POS',
    ],
    3 => [
        'bg_top'     => [30, 27, 75],    // Indigo 950
        'bg_mid'     => [109, 40, 217],  // Violet 700
        'bg_bot'     => [76, 29, 149],   // Deep Purple
        'accent'     => [192, 132, 252], // Lavender Glow
        'pill_text'  => 'ACCOUNTING & PROFIT',
        'title'      => 'DataPOS Finance',
        'tagline'    => 'Business Intelligence & Ledger',
        'bullets'    => [
            ['01', 'Double-Entry Ledger & Cash Book'],
            ['02', 'Instant Daily Profit & Loss Reports'],
            ['03', 'KPay, Wave, Cash & Credit Multi-Pay'],
            ['04', 'Customer Tier & Wholesale Pricing'],
        ],
        'footer'     => 'TRANSPARENT FINANCIAL TRACKING',
    ],
    4 => [
        'bg_top'     => [76, 5, 25],     // Rose 950
        'bg_mid'     => [190, 18, 60],   // Rose 700
        'bg_bot'     => [136, 19, 55],   // Deep Rose
        'accent'     => [251, 113, 133], // Rose Glow
        'pill_text'  => 'SERVICE & WORKSHOP',
        'title'      => 'DataPOS Service',
        'tagline'    => 'Repair Jobs & Security Management',
        'bullets'    => [
            ['01', 'Device Repair Ticket & Live Tracking'],
            ['02', 'CCTV & Tech Serial Number Tracking'],
            ['03', 'Role-Based Staff Permission System'],
            ['04', 'Direct Viber & Telegram Notification'],
        ],
        'footer'     => 'DESIGNED FOR MYANMAR TECH SMES',
    ],
];

function drawCenteredText(GdImage $im, string $text, string $font, int $size, int $color, int $y): void {
    $bbox = imagettfbbox($size, 0, $font, $text);
    $width = abs($bbox[2] - $bbox[0]);
    $x = (int) ((800 - $width) / 2);
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

function drawSmoothBox(GdImage $im, int $x1, int $y1, int $x2, int $y2, int $radius, int $fillColor, ?int $borderColor = null): void {
    imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $fillColor);
    imagefilledrectangle($im, $x1, $y1 + $radius, $x1 + $radius, $y2 - $radius, $fillColor);
    imagefilledrectangle($im, $x2 - $radius, $y1 + $radius, $x2, $y2 - $radius, $fillColor);
    imagefilledarc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $fillColor, IMG_ARC_PIE);
    imagefilledarc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $fillColor, IMG_ARC_PIE);
    imagefilledarc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $fillColor, IMG_ARC_PIE);
    imagefilledarc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $fillColor, IMG_ARC_PIE);

    if ($borderColor !== null) {
        imagesetthickness($im, 2);
        imageline($im, $x1 + $radius, $y1, $x2 - $radius, $y1, $borderColor);
        imageline($im, $x1 + $radius, $y2, $x2 - $radius, $y2, $borderColor);
        imageline($im, $x1, $y1 + $radius, $x1, $y2 - $radius, $borderColor);
        imageline($im, $x2, $y1 + $radius, $x2, $y2 - $radius, $borderColor);
        imagearc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $borderColor);
        imagearc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $borderColor);
        imagearc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $borderColor);
        imagearc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $borderColor);
    }
}

foreach ($ads as $idx => $ad) {
    $im = imagecreatetruecolor(800, 800);
    imagesavealpha($im, true);

    // 3-stop vertical gradient
    for ($y = 0; $y < 800; $y++) {
        if ($y < 400) {
            $t = $y / 400.0;
            $r = (int) ($ad['bg_top'][0] + ($ad['bg_mid'][0] - $ad['bg_top'][0]) * $t);
            $g = (int) ($ad['bg_top'][1] + ($ad['bg_mid'][1] - $ad['bg_top'][1]) * $t);
            $b = (int) ($ad['bg_top'][2] + ($ad['bg_mid'][2] - $ad['bg_top'][2]) * $t);
        } else {
            $t = ($y - 400) / 400.0;
            $r = (int) ($ad['bg_mid'][0] + ($ad['bg_bot'][0] - $ad['bg_mid'][0]) * $t);
            $g = (int) ($ad['bg_mid'][1] + ($ad['bg_bot'][1] - $ad['bg_mid'][1]) * $t);
            $b = (int) ($ad['bg_mid'][2] + ($ad['bg_bot'][2] - $ad['bg_mid'][2]) * $t);
        }
        $lineColor = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, 800, $y, $lineColor);
    }

    // Soft cyber glow rings
    $accentAlpha = imagecolorallocatealpha($im, $ad['accent'][0], $ad['accent'][1], $ad['accent'][2], 112);
    $whiteGlow = imagecolorallocatealpha($im, 255, 255, 255, 120);
    imagefilledellipse($im, 400, 400, 560, 560, $accentAlpha);
    imagefilledellipse($im, 400, 400, 420, 420, $whiteGlow);

    imagesetthickness($im, 1);
    $ringColor = imagecolorallocatealpha($im, 255, 255, 255, 105);
    imageellipse($im, 400, 400, 640, 640, $ringColor);
    imageellipse($im, 400, 400, 720, 720, $ringColor);

    // Center Frosted Glass Poster Card (Solid opaque to prevent background bleed-through)
    $cardBg = imagecolorallocate($im, 11, 20, 36);
    $cardBorder = imagecolorallocatealpha($im, 255, 255, 255, 65);
    drawSmoothBox($im, 70, 70, 730, 730, 36, $cardBg, $cardBorder);

    // Top Pill Tag
    $pillBg = imagecolorallocatealpha($im, $ad['accent'][0], $ad['accent'][1], $ad['accent'][2], 20);
    $pillBorder = imagecolorallocatealpha($im, 255, 255, 255, 60);
    $pillBbox = imagettfbbox(13, 0, $fontBold, $ad['pill_text']);
    $pW = abs($pillBbox[2] - $pillBbox[0]) + 36;
    drawSmoothBox($im, (int)((800 - $pW)/2), 110, (int)((800 + $pW)/2), 146, 18, $pillBg, $pillBorder);
    $white = imagecolorallocate($im, 255, 255, 255);
    drawCenteredText($im, $ad['pill_text'], $fontBold, 13, $white, 134);

    // Main Software Name: "DataPOS System"
    $shadowColor = imagecolorallocatealpha($im, 0, 0, 0, 85);
    drawCenteredText($im, $ad['title'], $fontBold, 46, $shadowColor, 210);
    drawCenteredText($im, $ad['title'], $fontBold, 46, $white, 208);

    // Tagline
    $accentColor = imagecolorallocate($im, $ad['accent'][0], $ad['accent'][1], $ad['accent'][2]);
    drawCenteredText($im, $ad['tagline'], $fontMedium, 17, $accentColor, 250);

    // Divider Line
    $divColor = imagecolorallocatealpha($im, 255, 255, 255, 75);
    imagesetthickness($im, 2);
    imageline($im, 150, 278, 650, 278, $divColor);

    // Feature Highlights (4 Bullets with circular index pills)
    $startY = 312;
    foreach ($ad['bullets'] as $bIdx => [$num, $text]) {
        $rowY = $startY + ($bIdx * 76);
        $boxBg = imagecolorallocate($im, 17, 29, 50);
        $boxBorder = imagecolorallocatealpha($im, 255, 255, 255, 80);
        drawSmoothBox($im, 110, $rowY, 690, $rowY + 60, 16, $boxBg, $boxBorder);

        // Circular number badge
        $numBg = imagecolorallocate($im, $ad['accent'][0], $ad['accent'][1], $ad['accent'][2]);
        $numText = imagecolorallocate($im, 15, 23, 42);
        imagefilledellipse($im, 145, $rowY + 30, 36, 36, $numBg);
        
        $nBbox = imagettfbbox(12, 0, $fontBold, $num);
        $nW = abs($nBbox[2] - $nBbox[0]);
        imagettftext($im, 12, 0, 145 - (int)($nW / 2), $rowY + 35, $numText, $fontBold, $num);

        // Feature Text
        $textColor = imagecolorallocate($im, 255, 255, 255);
        imagettftext($im, 15, 0, 180, $rowY + 37, $textColor, $fontMedium, $text);
    }

    // Bottom Watermark / CTA
    $watermark = imagecolorallocatealpha($im, 255, 255, 255, 80);
    drawCenteredText($im, $ad['footer'], $fontRegular, 12, $watermark, 680);

    // Save as WebP
    $savePath = $disk->path("placeholders/datapos-software-ad-{$idx}.webp");
    imagewebp($im, $savePath, 92);

    if ($idx === 1) {
        imagewebp($im, $disk->path("placeholders/datapos-software-ad.webp"), 92);
    }

    imagedestroy($im);
    echo "Generated: placeholders/datapos-software-ad-{$idx}.webp\n";
}
