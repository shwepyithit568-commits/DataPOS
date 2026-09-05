<?php
$im = imagecreatefromwebp('public/storage/placeholders/datapos-software-ad-1.webp');
$rgb = imagecolorat($im, 400, 460);
$colors = imagecolorsforindex($im, $rgb);
echo "Pixel at 400,460: " . json_encode($colors) . PHP_EOL;

$rgb2 = imagecolorat($im, 400, 350);
$colors2 = imagecolorsforindex($im, $rgb2);
echo "Pixel at 400,350: " . json_encode($colors2) . PHP_EOL;

$rgb3 = imagecolorat($im, 100, 100);
$colors3 = imagecolorsforindex($im, $rgb3);
echo "Pixel at 100,100: " . json_encode($colors3) . PHP_EOL;
