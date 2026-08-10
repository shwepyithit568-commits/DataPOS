<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use App\Support\AdminListReturn;
use App\Support\ImageOptimizer;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomeBannerController extends Controller
{
    private const IMAGE_MAX_KB = 10240;
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $currentPage = $request->query('page', 'home');
        $banners = $store->homeBanners()->where('page', $currentPage)->get();

        AdminListReturn::capture($request, 'admin_banners_return');

        return view('admin.banners.index', compact('store', 'banners', 'currentPage'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'page'       => ['required', 'string', 'in:home,glass_finder'],
            'image'      => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
            // Accept both full URLs (https://...) and in-store relative links (/products?...).
            'link_url'   => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $isValidUrl = filter_var($value, FILTER_VALIDATE_URL) !== false;
                $isValidRelative = is_string($value)
                    && str_starts_with($value, '/')
                    && filter_var('http://example.com' . $value, FILTER_VALIDATE_URL) !== false;
                if (! $isValidUrl && ! $isValidRelative) {
                    $fail('The link URL must be a valid full URL or a path starting with "/".');
                }
            }],
            'sort_order' => ['integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        $imagePath = ImageOptimizer::store($request->file('image'), 'banners', 1600);

        $store->homeBanners()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'page' => $validated['page'],
            'image_path' => $imagePath,
            'link_url' => $validated['link_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Banner created successfully.');
    }

    public function destroy(string $store_slug, HomeBanner $banner, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        // Ensure banner belongs to current store
        if ($banner->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store banner.');
        }

        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return back()->with('success', 'Banner deleted successfully.');
    }

    public function edit(string $store_slug, HomeBanner $banner, StoreContext $context): View
    {
        $store = $context->getStore();

        // Ensure banner belongs to current store
        if ($banner->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store banner.');
        }

        $imageMaxMb = self::IMAGE_MAX_KB / 1024;
        $returnTo = AdminListReturn::peek('admin_banners_return', '/store/' . $store->slug . '/admin/banners');

        return view('admin.banners.edit', compact('store', 'banner', 'imageMaxMb', 'returnTo'));
    }

    public function update(Request $request, string $store_slug, HomeBanner $banner, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        // Ensure banner belongs to current store
        if ($banner->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store banner.');
        }

        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            // Accept both full URLs (https://...) and in-store relative links (/products?...).
            'link_url' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $isValidUrl = filter_var($value, FILTER_VALIDATE_URL) !== false;
                $isValidRelative = is_string($value)
                    && str_starts_with($value, '/')
                    && filter_var('http://example.com' . $value, FILTER_VALIDATE_URL) !== false;
                if (! $isValidUrl && ! $isValidRelative) {
                    $fail('The link URL must be a valid full URL or a path starting with "/".');
                }
            }],
            'image'    => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
        ]);

        $data = [
            'title'    => $validated['title'],
            'description' => $validated['description'] ?? null,
            'link_url' => $validated['link_url'] ?? null,
        ];

        // Uploading a new image replaces the current one.
        if ($request->hasFile('image')) {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $data['image_path'] = ImageOptimizer::store($request->file('image'), 'banners', 1600);
        }

        $banner->update($data);

        return redirect(AdminListReturn::resolve('admin_banners_return', '/store/' . $store->slug . '/admin/banners'))
            ->with('success', 'Banner updated successfully.');
    }
}
