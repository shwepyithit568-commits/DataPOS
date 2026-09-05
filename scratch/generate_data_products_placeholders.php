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

$configs = [
    1 => [
        'bg_top'     => [15, 23, 42],    // #0f172a Deep Slate
        'bg_mid'     => [2, 132, 199],   // #0284c7 Sky
        'bg_bot'     => [12, 74, 110],   // #0c4a6e Dark Sky
        'accent'     => [56, 189, 248],  // #38bdf8 Glow Cyan
        'pill_text'  => 'SMARTPHONES & DEVICES',
        'pill_bg'    => [14, 165, 233],
        'tagline'    => 'PREMIUM QUALITY TECH',
        'badge_icon' => 'DEVICE',
    ],
    2 => [
        'bg_top'     => [6, 78, 59],     // #064e3b Emerald 950
        'bg_mid'     => [16, 185, 129],  // #10b981 Mint
        'bg_bot'     => [4, 120, 87],    // #047857 Deep Green
        'accent'     => [52, 211, 153],  // #34d399 Light Mint
        'pill_text'  => 'CHARGER & ACCESSORIES',
        'pill_bg'    => [16, 185, 129],
        'tagline'    => 'GENUINE & FAST CHARGING',
        'badge_icon' => 'POWER',
    ],
    3 => [
        'bg_top'     => [30, 27, 75],    // #1e1b4b Indigo 950
        'bg_mid'     => [139, 92, 246],  // #8b5cf6 Violet
        'bg_bot'     => [88, 28, 135],   // #581c87 Deep Purple
        'accent'     => [192, 132, 252], // #c084fc Lavender Glow
        'pill_text'  => 'AUDIO & SMART GADGETS',
        'pill_bg'    => [139, 92, 246],
        'tagline'    => 'HI-FI SOUND & WIRELESS',
        'badge_icon' => 'AUDIO',
    ],
    4 => [
        'bg_top'     => [76, 5, 25],     // #4c0519 Rose 950
        'bg_mid'     => [225, 29, 72],   // #e11d48 Crimson Rose
        'bg_bot'     => [136, 19, 55],   // #881337 Deep Rose
        'accent'     => [251, 113, 133], // #fb7185 Rose Glow
        'pill_text'  => 'SERVICE & PROTECTION',
        'pill_bg'    => [244, 63, 94],
        'tagline'    => 'CERTIFIED ORIGINAL GEAR',
        'badge_icon' => 'SHIELD',
    ],
];

function drawCentered(GdImage $im, string $text, string $font, int $size, int $color, int $y): void {
    $bbox = imagettfbbox($size, 0, $font, $text);
    $width = abs($bbox[2] - $bbox[0]);
    $x = (int) ((800 - $width) / 2);
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

function drawSmoothRoundedBox(GdImage $im, int $x1, int $y1, int $x2, int $y2, int $radius, int $fillColor, ?int $borderColor = null): void {
    // Fill main center rectangle
    imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $fillColor);
    // Fill left and right middle rectangles
    imagefilledrectangle($im, $x1, $y1 + $radius, $x1 + $radius, $y2 - $radius, $fillColor);
    imagefilledrectangle($im, $x2 - $radius, $y1 + $radius, $x2, $y2 - $radius, $fillColor);
    // Fill 4 corner arcs cleanly (without overlapping rectangles)
    imagefilledarc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $fillColor, IMG_ARC_PIE);
    imagefilledarc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $fillColor, IMG_ARC_PIE);
    imagefilledarc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $fillColor, IMG_ARC_PIE);
    imagefilledarc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $fillColor, IMG_ARC_PIE);

    if ($borderColor !== null) {
        imagesetthickness($im, 2);
        // Top and bottom lines
        imageline($im, $x1 + $radius, $y1, $x2 - $radius, $y1, $borderColor);
        imageline($im, $x1 + $radius, $y2, $x2 - $radius, $y2, $borderColor);
        // Left and right lines
        imageline($im, $x1, $y1 + $radius, $x1, $y2 - $radius, $borderColor);
        imageline($im, $x2, $y1 + $radius, $x2, $y2 - $radius, $borderColor);
        // 4 corner arcs
        imagearc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $borderColor);
        imagearc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $borderColor);
        imagearc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $borderColor);
        imagearc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $borderColor);
    }
}

foreach ($configs as $idx => $c) {
    $im = imagecreatetruecolor(800, 800);
    imagesavealpha($im, true);
    
    // Smooth 3-stop vertical gradient
    for ($y = 0; $y < 800; $y++) {
        if ($y < 400) {
            $t = $y / 400.0;
            $r = (int) ($c['bg_top'][0] + ($c['bg_mid'][0] - $c['bg_top'][0]) * $t);
            $g = (int) ($c['bg_top'][1] + ($c['bg_mid'][1] - $c['bg_top'][1]) * $t);
            $b = (int) ($c['bg_top'][2] + ($c['bg_mid'][2] - $c['bg_top'][2]) * $t);
        } else {
            $t = ($y - 400) / 400.0;
            $r = (int) ($c['bg_mid'][0] + ($c['bg_bot'][0] - $c['bg_mid'][0]) * $t);
            $g = (int) ($c['bg_mid'][1] + ($c['bg_bot'][1] - $c['bg_mid'][1]) * $t);
            $b = (int) ($c['bg_mid'][2] + ($c['bg_bot'][2] - $c['bg_mid'][2]) * $t);
        }
        $lineColor = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, 800, $y, $lineColor);
    }
    
    // Soft ambient glow circles
    $accentAlpha = imagecolorallocatealpha($im, $c['accent'][0], $c['accent'][1], $c['accent'][2], 110);
    $whiteGlow = imagecolorallocatealpha($im, 255, 255, 255, 118);
    imagefilledellipse($im, 400, 360, 520, 520, $accentAlpha);
    imagefilledellipse($im, 400, 360, 420, 420, $whiteGlow);
    
    // Elegant thin concentric rings
    imagesetthickness($im, 1);
    $ringColor = imagecolorallocatealpha($im, 255, 255, 255, 100);
    imageellipse($im, 400, 360, 600, 600, $ringColor);
    imageellipse($im, 400, 360, 680, 680, $ringColor);
    
    // Center Floating Glass Tech Card
    $glassBg = imagecolorallocatealpha($im, 10, 18, 30, 40); // Clean dark translucent
    $glassBorder = imagecolorallocatealpha($im, 255, 255, 255, 55);
    drawSmoothRoundedBox($im, 110, 170, 690, 570, 36, $glassBg, $glassBorder);
    
    // Top Central Tech Icon Badge (Circular 3D Coin)
    $badgeCircleBg = imagecolorallocate($im, $c['accent'][0], $c['accent'][1], $c['accent'][2]);
    $badgeCircleBorder = imagecolorallocate($im, 255, 255, 255);
    $badgeShadow = imagecolorallocatealpha($im, 0, 0, 0, 75);
    
    imagefilledellipse($im, 400, 258, 106, 106, $badgeShadow);
    imagefilledellipse($im, 400, 255, 100, 100, $badgeCircleBg);
    imagesetthickness($im, 3);
    imageellipse($im, 400, 255, 100, 100, $badgeCircleBorder);
    
    // Draw stylish vector symbol inside badge
    $iconColor = imagecolorallocate($im, 255, 255, 255);
    if ($c['badge_icon'] === 'DEVICE') {
        // Phone icon
        imagesetthickness($im, 4);
        imagerectangle($im, 385, 230, 415, 280, $iconColor);
        imagefilledellipse($im, 400, 273, 5, 5, $iconColor);
    } elseif ($c['badge_icon'] === 'POWER') {
        // Lightning bolt
        imagesetthickness($im, 4);
        $points = [
            404, 230,
            391, 256,
            402, 256,
            396, 282,
            413, 250,
            402, 250
        ];
        imagefilledpolygon($im, $points, $iconColor);
    } elseif ($c['badge_icon'] === 'AUDIO') {
        // Headphones
        imagesetthickness($im, 4);
        imagearc($im, 400, 255, 50, 50, 180, 360, $iconColor);
        imagefilledrectangle($im, 375, 250, 384, 272, $iconColor);
        imagefilledrectangle($im, 416, 250, 425, 272, $iconColor);
    } else {
        // Shield
        imagesetthickness($im, 4);
        $shieldPoints = [
            400, 230,
            418, 240,
            418, 264,
            400, 282,
            382, 264,
            382, 240
        ];
        imagefilledpolygon($im, $shieldPoints, $iconColor);
    }
    
    // Category pill above title
    $pillBg = imagecolorallocatealpha($im, $c['pill_bg'][0], $c['pill_bg'][1], $c['pill_bg'][2], 20);
    $pillBorder = imagecolorallocatealpha($im, 255, 255, 255, 60);
    $pillBbox = imagettfbbox(14, 0, $fontBold, $c['pill_text']);
    $pW = abs($pillBbox[2] - $pillBbox[0]) + 40;
    drawSmoothRoundedBox($im, (int)((800 - $pW)/2), 335, (int)((800 + $pW)/2), 373, 19, $pillBg, $pillBorder);
    $white = imagecolorallocate($im, 255, 255, 255);
    drawCentered($im, $c['pill_text'], $fontBold, 14, $white, 360);
    
    // Main Title: "DATA PRODUCTS" (Bold, Crisp & Premium)
    $shadowColor = imagecolorallocatealpha($im, 0, 0, 0, 85);
    drawCentered($im, "DATA PRODUCTS", $fontBold, 44, $shadowColor, 437);
    drawCentered($im, "DATA PRODUCTS", $fontBold, 44, $white, 435);
    
    // Subtitle tagline
    $accentColor = imagecolorallocate($im, $c['accent'][0], $c['accent'][1], $c['accent'][2]);
    drawCentered($im, $c['tagline'], $fontMedium, 16, $accentColor, 485);
    
    // Watermark
    $watermark = imagecolorallocatealpha($im, 255, 255, 255, 90);
    drawCentered($im, "OFFICIAL DATA POS STOREFRONT", $fontRegular, 13, $watermark, 530);
    
    // Save as WebP
    $savePath = $disk->path("placeholders/data-product-{$idx}.webp");
    imagewebp($im, $savePath, 92);
    imagedestroy($im);
    
    echo "Generated: placeholders/data-product-{$idx}.webp\n";
}
