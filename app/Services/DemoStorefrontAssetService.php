<?php

namespace App\Services;

use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DemoStorefrontAssetService
{
    private const PRODUCT_SIZE = 720;
    private const CATEGORY_SIZE = 640;
    private const BANNER_WIDTH = 1440;
    private const BANNER_HEIGHT = 560;

    private const PALETTES = [
        ['#0f766e', '#14b8a6', '#f59e0b'],
        ['#1d4ed8', '#38bdf8', '#f97316'],
        ['#7c2d12', '#ea580c', '#facc15'],
        ['#166534', '#4ade80', '#f59e0b'],
        ['#7e22ce', '#c084fc', '#22d3ee'],
        ['#9f1239', '#fb7185', '#fbbf24'],
    ];

    /**
     * Generate replaceable demo visuals after the database seed has committed.
     * Existing customer-uploaded images are never overwritten.
     *
     * @return array{products:int,categories:int,banners:int,skipped:int}
     */
    public function generate(Store $store, string $scenarioKey): array
    {
        $this->ensureSupported();
        $this->purge($store);

        $root = $this->root($store, $scenarioKey);
        $counts = ['products' => 0, 'categories' => 0, 'banners' => 0, 'skipped' => 0];

        $categories = Category::where('store_id', $store->id)->orderBy('id')->get();
        foreach ($categories as $index => $category) {
            $path = "{$root}/categories/{$category->id}.webp";
            if (! $this->mayReplace($category->image_path, $store)) {
                $counts['skipped']++;
                continue;
            }

            $this->renderCard($path, self::CATEGORY_SIZE, self::CATEGORY_SIZE, $category->name, 'SHOP CATEGORY', $index);
            $category->update(['image_path' => $path]);
            $counts['categories']++;
        }

        $products = Product::where('store_id', $store->id)->with('category')->orderBy('id')->get();
        foreach ($products as $index => $product) {
            $path = "{$root}/products/{$product->id}.webp";
            if (! $this->mayReplace($product->image_path, $store)) {
                $counts['skipped']++;
                continue;
            }

            $this->renderCard(
                $path,
                self::PRODUCT_SIZE,
                self::PRODUCT_SIZE,
                $product->name,
                $product->category?->name ?: 'DEMO PRODUCT',
                $index
            );
            $product->update(['image_path' => $path]);
            $counts['products']++;
        }

        foreach ($this->bannerDefinitions($store) as $index => $banner) {
            $path = "{$root}/banners/" . ($index + 1) . '.webp';
            $this->renderBanner($path, $store->name, $banner['eyebrow'], $index);
            HomeBanner::updateOrCreate(
                ['store_id' => $store->id, 'image_path' => $path],
                [
                    'page' => 'home',
                    'title' => $banner['title'],
                    'description' => $banner['description'],
                    'image_path' => $path,
                    'link_url' => $banner['link_url'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
            $counts['banners']++;
        }

        return $counts;
    }

    public function purge(Store $store): void
    {
        $prefix = $this->storePrefix($store);
        HomeBanner::where('store_id', $store->id)
            ->where('image_path', 'like', $prefix . '/%')
            ->delete();
        Storage::disk('public')->deleteDirectory($prefix);
    }

    private function mayReplace(?string $path, Store $store): bool
    {
        return empty($path) || Str::startsWith($path, $this->storePrefix($store) . '/');
    }

    private function ensureSupported(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp') || ! function_exists('imagettftext')) {
            throw new RuntimeException('GD with WebP and FreeType support is required to generate demo storefront assets.');
        }

        if (! is_file($this->latinFont()) || ! is_file($this->myanmarFont())) {
            throw new RuntimeException('Bundled storefront fonts are missing.');
        }
    }

    private function root(Store $store, string $scenarioKey): string
    {
        return $this->storePrefix($store) . '/' . Str::slug($scenarioKey);
    }

    private function storePrefix(Store $store): string
    {
        return 'demo-stores/' . $store->id;
    }

    private function renderCard(string $path, int $width, int $height, string $title, string $eyebrow, int $paletteIndex): void
    {
        $image = $this->canvas($width, $height, $paletteIndex);
        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocatealpha($image, 255, 255, 255, 28);

        imagefilledellipse($image, (int) ($width * .82), (int) ($height * .18), (int) ($width * .62), (int) ($height * .62), $muted);
        imagefilledrectangle($image, 52, $height - 180, $width - 52, $height - 56, imagecolorallocatealpha($image, 8, 15, 28, 45));

        $this->drawText($image, mb_strtoupper($eyebrow), 19, 62, 80, $white, $this->latinFont(), $width - 124, 1);
        $this->drawText($image, $title, 34, 78, $height - 142, $white, $this->fontFor($title), $width - 156, 3);
        $this->writeAtomic($image, $path);
    }

    private function renderBanner(string $path, string $storeName, string $eyebrow, int $paletteIndex): void
    {
        $image = $this->canvas(self::BANNER_WIDTH, self::BANNER_HEIGHT, $paletteIndex + 2);
        $white = imagecolorallocate($image, 255, 255, 255);
        $panel = imagecolorallocatealpha($image, 4, 10, 22, 48);
        imagefilledrectangle($image, 0, 0, 850, self::BANNER_HEIGHT, $panel);
        imagefilledellipse($image, 1220, 110, 620, 620, imagecolorallocatealpha($image, 255, 255, 255, 65));
        imagefilledellipse($image, 1110, 470, 360, 360, imagecolorallocatealpha($image, 255, 255, 255, 82));

        $this->drawText($image, mb_strtoupper($eyebrow), 22, 84, 104, $white, $this->latinFont(), 650, 1);
        $this->drawText($image, $storeName, 50, 84, 190, $white, $this->fontFor($storeName), 680, 2);
        $this->drawText($image, 'MYANMAR SME DEMO STOREFRONT', 20, 86, 385, $white, $this->latinFont(), 650, 1);
        $this->writeAtomic($image, $path);
    }

    private function canvas(int $width, int $height, int $paletteIndex): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        if (! $image) {
            throw new RuntimeException('Unable to allocate demo image canvas.');
        }

        [$from, $to] = self::PALETTES[$paletteIndex % count(self::PALETTES)];
        [$fr, $fg, $fb] = $this->rgb($from);
        [$tr, $tg, $tb] = $this->rgb($to);
        $bands = 80;
        for ($band = 0; $band < $bands; $band++) {
            $t = $band / ($bands - 1);
            $color = imagecolorallocate($image, (int) ($fr + ($tr - $fr) * $t), (int) ($fg + ($tg - $fg) * $t), (int) ($fb + ($tb - $fb) * $t));
            $y1 = (int) floor($height * $band / $bands);
            $y2 = (int) ceil($height * ($band + 1) / $bands);
            imagefilledrectangle($image, 0, $y1, $width, $y2, $color);
        }

        return $image;
    }

    private function writeAtomic(\GdImage $image, string $path): void
    {
        $disk = Storage::disk('public');
        $directory = dirname($path);
        $disk->makeDirectory($directory);
        $target = $disk->path($path);
        $temporary = $target . '.tmp-' . bin2hex(random_bytes(4));

        try {
            if (! imagewebp($image, $temporary, 82)) {
                throw new RuntimeException("Unable to write demo asset: {$path}");
            }
            if (! rename($temporary, $target)) {
                throw new RuntimeException("Unable to publish demo asset: {$path}");
            }
        } finally {
            imagedestroy($image);
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    private function drawText(\GdImage $image, string $text, int $size, int $x, int $baseline, int $color, string $font, int $maxWidth, int $maxLines): void
    {
        $lines = $this->wrap($text, $font, $size, $maxWidth, $maxLines);
        foreach ($lines as $index => $line) {
            imagettftext($image, $size, 0, $x, $baseline + ($index * (int) ($size * 1.35)), $color, $font, $line);
        }
    }

    /** @return array<int,string> */
    private function wrap(string $text, string $font, int $size, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = $box ? abs($box[2] - $box[0]) : 0;
            if ($line !== '' && $width > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return array_slice($lines, 0, $maxLines);
    }

    /** @return array{int,int,int} */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private function fontFor(string $text): string
    {
        return preg_match('/\p{Myanmar}/u', $text) ? $this->myanmarFont() : $this->latinFont();
    }

    private function latinFont(): string
    {
        return resource_path('assets/fonts/Outfit/Outfit-SemiBold.ttf');
    }

    private function myanmarFont(): string
    {
        return resource_path('assets/fonts/NotoSansMyanmar/NotoSansMyanmar-Bold.ttf');
    }

    /** @return array<int,array{title:string,description:string,link_url:string,eyebrow:string}> */
    private function bannerDefinitions(Store $store): array
    {
        $catalog = '/products?store_slug=' . rawurlencode($store->slug);

        return [
            ['title' => $store->name, 'description' => 'ရွေးချယ်စရာစုံလင်ပြီး စိတ်ချရသော ဝယ်ယူမှု', 'link_url' => $catalog, 'eyebrow' => 'WELCOME TO OUR STORE'],
            ['title' => 'အချိန်ကန့်သတ် အထူးလျှော့ဈေး', 'description' => 'Promotion ဝင်ပစ္စည်းများကို မကုန်မီ ရွေးချယ်ဝယ်ယူပါ', 'link_url' => $catalog . '&sort=sale', 'eyebrow' => 'LIMITED TIME OFFERS'],
            ['title' => 'လူကြိုက်များသော ပစ္စည်းများ', 'description' => 'ဖောက်သည်များအတွက် ရွေးချယ်ပေးထားသော Featured Products', 'link_url' => $catalog . '&featured=1', 'eyebrow' => 'FEATURED PRODUCTS'],
        ];
    }
}
