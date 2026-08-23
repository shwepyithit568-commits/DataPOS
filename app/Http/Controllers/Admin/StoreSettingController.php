<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreDeliveryMethod;
use App\Models\StorePaymentMethod;
use App\Models\StorefrontSetting;
use App\Support\ImageOptimizer;
use App\Services\StoreContext;
use App\Support\StorefrontAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreSettingController extends Controller
{
    /**
     * The settings area is split into separate pages (reachable from the admin
     * sidebar) instead of one tab-heavy page:
     *   general       — store name, tagline, language, logo, opening hours
     *   contact       — phone, viber, telegram, address, social links, chat button
     *   delivery      — delivery info, payment info, footer ad text
     *   how-to-order  — "How to Order" page content (intro, steps, videos)
     *   footer        — combined live preview of the storefront footer (read-only)
     *   pos           — POS behaviour (held-sale auto-expiry window)
     */
    private const SECTIONS = ['general', 'appearance', 'contact', 'delivery', 'how-to-order', 'footer', 'pos'];

    public function edit(Request $request, StoreContext $context): View
    {
        // Use route('section') explicitly — the method param would otherwise be
        // filled positionally with {store_slug} by Laravel's DI.
        $section = $request->route('section') ?? 'general';

        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $store = $context->getStore();
        $setting = $store->setting ?? new StorefrontSetting(['store_id' => $store->id, 'store_name' => $store->name]);

        return view('admin.settings.edit', compact('store', 'setting', 'section'));
    }

    public function update(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $section = $request->input('section', 'general');

        $validated = match ($section) {
            'appearance' => $request->validate([
                'theme_preset'        => ['nullable', 'string', Rule::in(array_keys(\App\Models\StorefrontSetting::THEME_PRESETS))],
                'theme_primary_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'theme_accent_color'  => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'theme_header_bg'     => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'theme_body_bg'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'theme_glow_style'    => ['nullable', 'string', Rule::in(['vivid', 'subtle', 'none'])],
                'theme_dark_mode'     => ['nullable', 'string', Rule::in(['auto', 'light', 'dark'])],
            ]),
            'contact' => $request->validate([
                'phone' => ['nullable', 'string', 'max:50'],
                'viber_number' => ['nullable', 'string', 'max:50'],
                'telegram_username' => ['nullable', 'string', 'max:100'],
                'address' => ['nullable', 'string'],
                'facebook_url' => ['nullable', 'string', 'max:255'],
                'youtube_url' => ['nullable', 'string', 'max:255'],
                'tiktok_url' => ['nullable', 'string', 'max:255'],
                'map_enabled' => ['sometimes', 'boolean'],
                'google_maps_url' => ['nullable', 'string', 'max:500'],
                'map_latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'map_longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'map_title' => ['nullable', 'string', 'max:120'],
                'map_embed_enabled' => ['sometimes', 'boolean'],
                'chat_button_label' => ['nullable', 'string', 'max:50'],
                'chat_button_icon' => ['nullable', 'string', 'max:10', Rule::in(['✈️', '💬', '📞', '📱'])],
                'chat_button_icon_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
                'chat_channels' => ['nullable', 'array', 'max:12'],
                'chat_channels.*.icon' => ['nullable', 'string', 'max:10'],
                'chat_channels.*.icon_path' => ['nullable', 'string', 'max:255'],
                'chat_channels.*.label' => ['nullable', 'string', 'max:50'],
                'chat_channels.*.href' => ['nullable', 'string', 'max:500'],
                'chat_channels.*.image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            ]),
            'delivery' => $request->validate([
                'delivery_info' => ['nullable', 'string'],
                'payment_info' => ['nullable', 'string'],
                'footer_ad_text' => ['nullable', 'string', 'max:255'],
            ]),
            'how-to-order' => $request->validate([
                'how_to_intro' => ['nullable', 'string'],
                'how_to_steps' => ['nullable', 'array', 'max:6'],
                'how_to_steps.*.icon' => ['nullable', 'string', 'max:10'],
                // nullable so blank repeater rows pass validation — they are
                // dropped by the filter below before saving.
                'how_to_steps.*.title' => ['nullable', 'string', 'max:255'],
                'how_to_steps.*.desc' => ['nullable', 'string'],
                'how_to_videos' => ['nullable', 'array', 'max:8'],
                'how_to_videos.*.title' => ['nullable', 'string', 'max:255'],
                'how_to_videos.*.url' => ['nullable', 'string', 'max:500'],
            ]),
            'pos' => $request->validate([
                // Hours before a held sale is auto-voided; blank = 24h default, 0 = disabled.
                'pos_hold_expiry_hours' => ['nullable', 'integer', 'min:0', 'max:720'],
                // Price-override discount % that needs a manager PIN; blank/0 = off.
                'pos_override_pin_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            ]),
            default => $request->validate([
                'store_name' => ['required', 'string', 'max:255'],
                'tagline' => ['nullable', 'string', 'max:160'],
                // Backward compatible legacy logo field (older clients).
                'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
                'storefront_logo' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:2048'],
                'admin_logo' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:2048'],
                'favicon' => ['nullable', 'image', 'mimes:png,webp', 'max:1024'],
                'opening_hours' => ['nullable', 'string', 'max:255'],
                'default_language' => ['required', Rule::in(array_keys(config('localization.supported', [])))],
            ]),
        };

        // Drop empty repeater rows (fully blank steps / videos) before saving.
        if ($section === 'how-to-order') {
            $validated['how_to_steps'] = collect($validated['how_to_steps'] ?? [])
                ->filter(fn ($step) => !empty($step['title']) || !empty($step['desc']))
                ->values()
                ->map(fn ($step) => [
                    'icon' => $step['icon'] ?? null,
                    'title' => $step['title'],
                    'desc' => $step['desc'],
                ])
                ->all();

            $validated['how_to_videos'] = collect($validated['how_to_videos'] ?? [])
                ->filter(fn ($video) => !empty($video['url']))
                ->values()
                ->map(fn ($video) => [
                    'title' => $video['title'] ?? null,
                    'url' => $video['url'],
                ])
                ->all();
        }

        $setting = $store->setting()->firstOrNew(['store_id' => $store->id], ['store_name' => $store->name]);

        // ---- Map / exact store location (contact section) ----
        // Only touch the map settings when the submitted form actually contains
        // map fields — the full contact form always does, but partial contact
        // saves (e.g. just phone/social URLs) must not wipe existing map data.
        $hasMapInput = $request->has('map_latitude') || $request->has('map_longitude')
            || $request->has('google_maps_url') || $request->has('map_enabled')
            || $request->has('map_embed_enabled') || $request->has('map_title');

        if ($section === 'contact' && $hasMapInput) {
            $googleMapsUrl = trim((string) ($validated['google_maps_url'] ?? ''));

            // Reject unsafe or non-map URLs; allow the shortened goo.gl links
            // the owner typically pastes from Google Maps "Share → Copy link".
            if ($googleMapsUrl !== '' && ! self::isAllowedUrlScheme($googleMapsUrl, ['https'])) {
                return back()->withErrors(['google_maps_url' => 'The Google Maps URL must be a valid https:// link.'])->withInput();
            }
            if ($googleMapsUrl !== '' && ! self::looksLikeGoogleMapsUrl($googleMapsUrl)) {
                return back()->withErrors(['google_maps_url' => 'The link does not look like a Google Maps link (maps.google.com, maps.app.goo.gl or google.com/maps).'])->withInput();
            }

            $validated['google_maps_url'] = $googleMapsUrl ?: null;
            $validated['map_enabled'] = (bool) ($validated['map_enabled'] ?? false);
            $validated['map_embed_enabled'] = (bool) ($validated['map_embed_enabled'] ?? false);
            $validated['map_latitude'] = ($validated['map_latitude'] ?? '') !== '' && ($validated['map_latitude'] ?? null) !== null
                ? (float) $validated['map_latitude']
                : null;
            $validated['map_longitude'] = ($validated['map_longitude'] ?? '') !== '' && ($validated['map_longitude'] ?? null) !== null
                ? (float) $validated['map_longitude']
                : null;
        }

        // Chat channels (floating chat popup): store per-row icon images, drop
        // blank rows, and delete icon files that are no longer referenced.
        if ($section === 'contact') {
            $validated['chat_channels'] = collect($validated['chat_channels'] ?? [])
                ->map(function ($channel, $index) use ($request) {
                    $file = $request->file("chat_channels.{$index}.image");
                    if ($file) {
                        if (StorefrontAsset::isManagedChatUpload($channel['icon_path'] ?? null)) {
                            Storage::disk('public')->delete($channel['icon_path']);
                        }
                        $channel['icon_path'] = ImageOptimizer::store($file, 'chat-icons', 512);
                    }

                    return [
                        'icon' => $channel['icon'] ?? null,
                        'icon_path' => StorefrontAsset::normalizeImagePath($channel['icon_path'] ?? null),
                        'label' => trim((string) ($channel['label'] ?? '')),
                        'href' => trim((string) ($channel['href'] ?? '')),
                    ];
                })
                ->filter(fn ($channel) => ! empty($channel['label']) || ! empty($channel['href']) || ! empty($channel['icon']) || ! empty($channel['icon_path']))
                ->values()
                ->all();

            $oldIconPaths = collect($setting->chat_channels ?? [])->pluck('icon_path')->filter();
            $newIconPaths = collect($validated['chat_channels'] ?? [])->pluck('icon_path')->filter();
            $oldIconPaths->diff($newIconPaths)
                ->filter(fn ($path) => StorefrontAsset::isManagedChatUpload($path))
                ->each(fn ($path) => Storage::disk('public')->delete($path));

            // Floating chat button custom icon image (upload / replace / remove).
            if ($request->hasFile('chat_button_icon_image')) {
                if (StorefrontAsset::isManagedChatUpload($setting->chat_button_icon_path)) {
                    Storage::disk('public')->delete($setting->chat_button_icon_path);
                }
                $validated['chat_button_icon_path'] = ImageOptimizer::store($request->file('chat_button_icon_image'), 'chat-icons', 512);
            } elseif ($request->input('chat_button_icon_remove') === '1' && StorefrontAsset::isManagedChatUpload($setting->chat_button_icon_path)) {
                Storage::disk('public')->delete($setting->chat_button_icon_path);
                $validated['chat_button_icon_path'] = null;
            }
            unset($validated['chat_button_icon_image']);
        }

        // ---- Brand assets (Storefront / Admin / Favicon) with safe sequencing ----
        // 1. Validate + store new files, 2. persist DB, 3. only then delete the
        // replaced files. If persistence fails, orphan files are removed and the
        // previous DB paths/files are preserved.
        $brandAssetPaths = [];
        $toDeleteAfterSave = [];

        $assetUploads = [
            'storefront_logo' => ['dir' => 'store-logos', 'max' => 1600],
            'admin_logo' => ['dir' => 'admin-logos', 'max' => 512],
            'favicon' => ['dir' => 'favicons', 'max' => 512],
        ];

        foreach ($assetUploads as $field => $cfg) {
            if ($request->hasFile($field)) {
                $newPath = ImageOptimizer::store($request->file($field), $cfg['dir'], $cfg['max']);
                $brandAssetPaths[$field] = $newPath;

                $oldPath = $setting->{$field . '_path'} ?? null;
                if ($oldPath) {
                    $toDeleteAfterSave[] = $oldPath;
                }
                $validated[$field . '_path'] = $newPath;
                unset($validated[$field]);
            } elseif ($request->input($field . '_remove') === '1') {
                $oldPath = $setting->{$field . '_path'} ?? null;
                if ($oldPath) {
                    $toDeleteAfterSave[] = $oldPath;
                }
                $validated[$field . '_path'] = null;
            }
        }

        // Legacy logo field — replaces `logo_path` itself.
        if ($request->hasFile('logo')) {
            $newPath = $request->file('logo')->store('store-logos', 'public');
            $brandAssetPaths['legacy'] = $newPath;

            if ($setting->logo_path) {
                $toDeleteAfterSave[] = $setting->logo_path;
            }
            $validated['logo_path'] = $newPath;
        }
        unset($validated['logo']);

        $setting->fill($validated);

        // After fill(), $setting holds the NEW column values — never delete a
        // replaced path that is still referenced by any of the four columns
        // (e.g. a legacy logo kept as a fallback after a specialized removal).
        $newlyReferenced = array_values(array_filter([
            $setting->storefront_logo_path,
            $setting->admin_logo_path,
            $setting->favicon_path,
            $setting->logo_path,
        ]));

        try {
            $setting->save();
        } catch (\Throwable $e) {
            // Remove orphan files created by this request, keep DB + old files.
            foreach ($brandAssetPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        foreach (array_unique($toDeleteAfterSave) as $path) {
            if (in_array($path, $newlyReferenced, true)) {
                continue;
            }
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Storefront settings updated successfully.');
    }

    // ---------------------------------------------------------------------
    // Structured Payment Methods CRUD (store-scoped)
    // ---------------------------------------------------------------------

    public function storePaymentMethod(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'type' => ['nullable', 'string', 'max:40'],
            'icon_type' => ['required', Rule::in(['builtin', 'custom', 'initials'])],
            'icon_value' => ['nullable', 'string', 'max:20'],
            'icon_path' => ['nullable', 'string', 'max:255'],
            'icon_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:120'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'show_account_details' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $iconPath = null;
        if ($request->hasFile('icon_image')) {
            $iconPath = ImageOptimizer::store($request->file('icon_image'), 'payment-icons', 512);
        } elseif (! empty($validated['icon_path']) && str_starts_with($validated['icon_path'], 'payment-icons/')) {
            $iconPath = $validated['icon_path'];
        }

        $store->paymentMethods()->create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'type' => $validated['type'] ?? 'custom',
            'icon_type' => $validated['icon_type'],
            'icon_value' => $validated['icon_value'] ?? null,
            'icon_path' => $iconPath,
            'account_name' => $validated['account_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'show_account_details' => (bool) ($validated['show_account_details'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? $store->paymentMethods()->max('sort_order') + 1),
        ]);

        return back()->with('success', 'Payment method added.');
    }

    public function updatePaymentMethod(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $paymentMethod = $store->paymentMethods()->findOrFail((int) $request->route('method'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'type' => ['nullable', 'string', 'max:40'],
            'icon_type' => ['required', Rule::in(['builtin', 'custom', 'initials'])],
            'icon_value' => ['nullable', 'string', 'max:20'],
            'icon_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_icon' => ['sometimes', 'boolean'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:120'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'show_account_details' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        // Icon lifecycle: replace → delete old after save; remove → delete now.
        $oldIconPath = $paymentMethod->icon_path;
        $newIconPath = null;
        if ($request->hasFile('icon_image')) {
            $newIconPath = ImageOptimizer::store($request->file('icon_image'), 'payment-icons', 512);
        } elseif (! empty($request->input('remove_icon'))) {
            $newIconPath = null;
        }

        $paymentMethod->fill([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'type' => $validated['type'] ?? 'custom',
            'icon_type' => $validated['icon_type'],
            'icon_value' => $validated['icon_value'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? $paymentMethod->is_active),
            'show_account_details' => (bool) ($validated['show_account_details'] ?? $paymentMethod->show_account_details),
            'sort_order' => (int) ($validated['sort_order'] ?? $paymentMethod->sort_order),
        ]);

        if ($newIconPath !== null || ! empty($request->input('remove_icon'))) {
            $paymentMethod->icon_path = $newIconPath;
        }

        try {
            $paymentMethod->save();
        } catch (\Throwable $e) {
            if ($newIconPath) {
                Storage::disk('public')->delete($newIconPath);
            }
            throw $e;
        }

        // Delete the replaced/removed icon only after the DB update succeeded.
        if (($newIconPath !== null || ! empty($request->input('remove_icon'))) && $oldIconPath && $oldIconPath !== $newIconPath) {
            Storage::disk('public')->delete($oldIconPath);
        }

        return back()->with('success', 'Payment method updated.');
    }

    public function destroyPaymentMethod(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $paymentMethod = $store->paymentMethods()->findOrFail((int) $request->route('method'));
        $iconPath = $paymentMethod->icon_path;
        $paymentMethod->delete();

        if ($iconPath && str_starts_with($iconPath, 'payment-icons/')) {
            Storage::disk('public')->delete($iconPath);
        }

        return back()->with('success', 'Payment method deleted.');
    }

    // ---------------------------------------------------------------------
    // Structured Delivery Methods CRUD (store-scoped)
    // ---------------------------------------------------------------------

    public function storeDeliveryMethod(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:40'],
            'icon' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string'],
            'service_area' => ['nullable', 'string', 'max:255'],
            'estimated_time' => ['nullable', 'string', 'max:120'],
            'fee_note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $store->deliveryMethods()->create([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? 'custom',
            'icon' => $validated['icon'] ?? null,
            'description' => $validated['description'] ?? null,
            'service_area' => $validated['service_area'] ?? null,
            'estimated_time' => $validated['estimated_time'] ?? null,
            'fee_note' => $validated['fee_note'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? $store->deliveryMethods()->max('sort_order') + 1),
        ]);

        return back()->with('success', 'Delivery method added.');
    }

    public function updateDeliveryMethod(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $deliveryMethod = $store->deliveryMethods()->findOrFail((int) $request->route('method'));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:40'],
            'icon' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string'],
            'service_area' => ['nullable', 'string', 'max:255'],
            'estimated_time' => ['nullable', 'string', 'max:120'],
            'fee_note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $deliveryMethod->fill([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? $deliveryMethod->type,
            'icon' => $validated['icon'] ?? $deliveryMethod->icon,
            'description' => $validated['description'] ?? null,
            'service_area' => $validated['service_area'] ?? null,
            'estimated_time' => $validated['estimated_time'] ?? null,
            'fee_note' => $validated['fee_note'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? $deliveryMethod->is_active),
            'sort_order' => (int) ($validated['sort_order'] ?? $deliveryMethod->sort_order),
        ])->save();

        return back()->with('success', 'Delivery method updated.');
    }

    public function destroyDeliveryMethod(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $deliveryMethod = $store->deliveryMethods()->findOrFail((int) $request->route('method'));
        $deliveryMethod->delete();

        return back()->with('success', 'Delivery method deleted.');
    }

    /**
     * Allowed URL schemes for user-supplied links. Rejects javascript:, data:,
     * vbscript: and any other scheme not explicitly allowed.
     */
    private static function isAllowedUrlScheme(string $url, array $allowed = ['https', 'http', 'tel', 'viber', 'tg', 'mailto']): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme !== '' && in_array($scheme, $allowed, true);
    }

    /**
     * Loose check that a link is Google-Maps-shaped. Accepts official domains
     * plus the shortened maps.app.goo.gl links Google Maps "Share" produces.
     */
    private static function looksLikeGoogleMapsUrl(string $url): bool
    {
        return preg_match('#^(https://)?(maps\.google\.|www\.google\.com/maps|maps\.app\.goo\.gl)#i', trim($url)) === 1;
    }
}
