<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StorefrontPage;
use App\Services\StoreContext;
use App\Services\StorefrontMarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display a custom storefront page.
     *
     * @param Request $request
     * @param string $store_slug
     * @param string $slug
     * @param StoreContext $context
     * @return View
     */
    public function show(Request $request, string $store_slug, string $slug, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $page = StorefrontPage::where('store_id', $store->id)
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->published()
            ->firstOrFail();

        $locale = app()->getLocale();
        $title = $page->localizedTitle($locale);
        $summary = $page->localizedSummary($locale);
        $rawMarkdown = $page->localizedContent($locale);
        $renderedContent = StorefrontMarkdownRenderer::render($rawMarkdown);

        $ogTitle = $page->localizedMetaTitle($locale) ?: $title . ' - ' . ($store->setting?->store_name ?? $store->name);
        $metaDescription = $page->localizedMetaDescription($locale) ?: $summary;
        $ogImage = $page->featured_image_path ? asset('storage/' . $page->featured_image_path) : null;
        $canonicalUrl = url('/store/' . $store->slug . '/page/' . $page->slug);

        return view('storefront.pages.show', compact(
            'store',
            'page',
            'title',
            'summary',
            'renderedContent',
            'ogTitle',
            'metaDescription',
            'ogImage',
            'canonicalUrl'
        ));
    }
}
