<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreThemeRevision;
use App\Services\StoreContext;
use App\Services\ThemeDraftService;
use App\Themes\ThemeConfig;
use App\Themes\ThemeContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * AppearanceDraftController — JSON API for the Theme Draft system.
 *
 * All endpoints are scoped to the authenticated store manager's store.
 * Authorization is enforced by the EnsureStoreAccess:store_manager middleware
 * registered on each route in web.php.
 *
 * Response contracts:
 *   200 — success with JSON body
 *   409 — optimistic lock conflict or base-revision conflict (with `reason`)
 *   422 — missing draft (getOrCreate should have been called first)
 */
class AppearanceDraftController extends Controller
{
    public function __construct(
        private readonly ThemeDraftService $draftService,
        private readonly ThemeContext $themeContext,
    ) {}

    // -------------------------------------------------------------------------
    // GET /admin/appearance/preview
    // -------------------------------------------------------------------------

    /**
     * Render the production storefront home with the current DRAFT theme config,
     * WITHOUT publishing anything. Anonymous storefront requests are unaffected
     * because the override lives in a request-scoped ThemeContext.
     *
     * - Reuses the exact HomeController pipeline (real queries + view models),
     *   so the preview is the real storefront, not a mockup.
     * - Response is marked private/no-store and noindex so it is never cached
     *   by browsers, proxies, or search engines.
     */
    public function preview(Request $request, StoreContext $context): Response
    {
        $store = $context->getStore();
        $draft = $this->draftService->getOrCreate($store, $request->user());

        // Opt in to same-origin framing ONLY for this preview response so the
        // admin Appearance page can embed it in the preview iframe. SecurityHeaders
        // keeps frame-ancestors 'none' for every other page.
        $request->attributes->set('theme_preview_frame', true);

        $this->themeContext->setConfig(ThemeConfig::fromArray($draft->theme_config));

        $home = app(\App\Http\Controllers\Storefront\HomeController::class)->index($request, $context);

        if ($home instanceof \Illuminate\Http\RedirectResponse) {
            return $home;
        }

        return response($home instanceof View ? $home->render() : (string) $home)
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    // -------------------------------------------------------------------------
    // GET /admin/appearance/draft
    // -------------------------------------------------------------------------

    /**
     * Return the current draft state for the appearance editor to bootstrap from.
     *
     * Response:
     * {
     *   "draft": {
     *     "theme_config": {...9 fields...},
     *     "lock_version": 3,
     *     "base_revision_id": 12
     *   },
     *   "conflict": false,
     *   "latest_revision_id": 12
     * }
     */
    public function show(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();
        $draft = $this->draftService->getOrCreate($store, $request->user());

        $latestRevisionId = StoreThemeRevision::where('store_id', $store->id)
            ->where('action', '!=', 'baseline')
            ->latest('revision_number')
            ->value('id');

        return response()->json([
            'draft' => [
                'theme_config'     => $draft->theme_config,
                'lock_version'     => $draft->lock_version,
                'base_revision_id' => $draft->base_revision_id,
            ],
            'conflict'            => $draft->isConflicting($latestRevisionId),
            'latest_revision_id'  => $latestRevisionId,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /admin/appearance/draft
    // -------------------------------------------------------------------------

    /**
     * Save changes to the draft without touching the live storefront.
     *
     * Expected body:
     * {
     *   "theme_config": {...9 safe keys...},
     *   "lock_version": 3           ← client's known version
     * }
     *
     * Response 200: { "draft": { "lock_version": 4, ... } }
     * Response 409: { "error": "stale_lock", "message": "..." }
     */
    public function save(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        $input = $request->validate([
            'theme_config'  => ['required', 'array'],
            'lock_version'  => ['required', 'integer', 'min:1'],
        ]);

        // ThemeDraftService will normalize via ThemeConfig DTO internally
        $config = array_intersect_key(
            (array) $input['theme_config'],
            array_flip(ThemeConfig::SAFE_KEYS),
        );

        $draft = $this->draftService->save(
            $store,
            $config,
            (int) $input['lock_version'],
            $request->user(),
        );

        return response()->json([
            'draft' => [
                'theme_config'     => $draft->theme_config,
                'lock_version'     => $draft->lock_version,
                'base_revision_id' => $draft->base_revision_id,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /admin/appearance/publish
    // -------------------------------------------------------------------------

    /**
     * Publish the draft to the live storefront.
     * Runs a conflict check before publishing.
     *
     * Expected body:
     * { "lock_version": 4 }
     *
     * Response 200: { "revision_number": 5, "message": "..." }
     * Response 409: { "error": "conflict|stale_lock", "message": "..." }
     */
    public function publish(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        $input = $request->validate([
            'lock_version' => ['required', 'integer', 'min:1'],
        ]);

        $revision = $this->draftService->publish(
            $store,
            (int) $input['lock_version'],
            $request->user(),
            $request->ip(),
        );

        return response()->json([
            'revision_number' => $revision->revision_number,
            'message'         => "Storefront theme published. Revision #{$revision->revision_number} saved.",
        ]);
    }

    // -------------------------------------------------------------------------
    // DELETE /admin/appearance/draft
    // -------------------------------------------------------------------------

    /**
     * Discard the active draft.
     * The next GET /draft will re-seed from the published state.
     *
     * Response 200: { "message": "Draft discarded." }
     */
    public function discard(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();
        $this->draftService->discard($store, $request->user(), $request->ip());

        return response()->json(['message' => 'Draft discarded.']);
    }
}
