<?php

namespace App\Http\Controllers;

use App\Models\GlassFavorite;
use App\Models\GlassFinderItem;
use App\Services\GlassCodeNormalizer;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlassFinderController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        abort_unless($store, 404, 'Store not found.');

        $banners = $store->homeBanners()
            ->where('page', 'glass_finder')
            ->where('is_active', true)
            ->get();

        $brands = GlassFinderItem::where('store_id', $store->id)
            ->whereNotNull('brand')
            ->select('brand')
            ->distinct()
            ->pluck('brand');

        $models = GlassFinderItem::where('store_id', $store->id)
            ->whereNotNull('phone_model')
            ->select('phone_model')
            ->distinct()
            ->pluck('phone_model');

        $glassCodes = GlassFinderItem::where('store_id', $store->id)
            ->whereNotNull('glass_code')
            ->select('glass_code')
            ->distinct()
            ->pluck('glass_code');

        $query = GlassFinderItem::where('store_id', $store->id);

        $selectedItem = null;
        $compatibles = collect();

        // 1. Search by Phone Model
        if ($request->filled('phone_model')) {
            $matchesQuery = GlassFinderItem::where('store_id', $store->id)
                ->where('phone_model', 'like', '%' . $request->phone_model . '%');

            if ($request->filled('brand')) {
                $matchesQuery->where('brand', $request->brand);
            }

            $matches = $matchesQuery->get();
            $normalizedCodes = $matches->pluck('normalized_glass_code')->filter()->unique();

            if ($normalizedCodes->isNotEmpty()) {
                $compatiblesQuery = GlassFinderItem::where('store_id', $store->id)
                    ->whereIn('normalized_glass_code', $normalizedCodes);

                if ($request->filled('brand')) {
                    $compatiblesQuery->where('brand', $request->brand);
                }

                $compatibles = $compatiblesQuery
                    ->orderBy('normalized_glass_code')
                    ->orderBy('phone_model')
                    ->get();
            }

            $selectedItem = $matches->first();
        } 
        // 2. Search by Glass Code
        elseif ($request->filled('glass_code')) {
            $normalized = GlassCodeNormalizer::normalize($request->glass_code);
            $rawCode = trim((string) $request->glass_code);
            $compatiblesQuery = GlassFinderItem::where('store_id', $store->id)
                ->where(function ($query) use ($normalized, $rawCode) {
                    $query->where('normalized_glass_code', $normalized)
                        ->orWhere('normalized_glass_code', $rawCode)
                        ->orWhere('glass_code', $rawCode);
                });

            if ($request->filled('brand')) {
                $compatiblesQuery->where('brand', $request->brand);
            }

            $compatibles = $compatiblesQuery
                ->orderBy('phone_model')
                ->get();

            $selectedItem = $compatibles->first();
        } 
        // 3. Unified Smart Search (Keyword / Brand / Model / Code)
        elseif ($request->filled('search') || $request->filled('brand')) {
            $searchQuery = GlassFinderItem::where('store_id', $store->id);

            if ($request->filled('brand')) {
                $searchQuery->where('brand', $request->brand);
            }

            if ($request->filled('search')) {
                $term = $request->search;
                $normalizedTerm = GlassCodeNormalizer::normalize($term);
                $searchQuery->where(function ($q) use ($term, $normalizedTerm) {
                    $q->where('phone_model', 'like', '%' . $term . '%')
                      ->orWhere('glass_code', 'like', '%' . $term . '%')
                      ->orWhere('normalized_glass_code', 'like', '%' . $normalizedTerm . '%')
                      ->orWhere('brand', 'like', '%' . $term . '%');
                });
            }

            $matches = $searchQuery->get();

            // Find all compatible items belonging to normalized glass codes of the matches
            $normalizedCodes = $matches->pluck('normalized_glass_code')->filter()->unique();
            if ($normalizedCodes->isNotEmpty()) {
                $compatiblesQuery = GlassFinderItem::where('store_id', $store->id)
                    ->whereIn('normalized_glass_code', $normalizedCodes);

                if ($request->filled('brand')) {
                    $compatiblesQuery->where('brand', $request->brand);
                }

                $compatibles = $compatiblesQuery
                    ->orderBy('normalized_glass_code')
                    ->orderBy('phone_model')
                    ->get();
            } else {
                $compatibles = $matches;
            }

            if ($matches->isNotEmpty()) {
                $selectedItem = $matches->first();
            }
        }

        $groupedCompatibles = $compatibles->groupBy('normalized_glass_code');
        $items = $query->paginate(20);

        // Fetch favorite IDs if logged in
        $favoriteIds = auth()->check()
            ? GlassFavorite::where('user_id', auth()->id())->pluck('glass_finder_item_id')->toArray()
            : [];

        return view('storefront.glass_finder.index', compact(
            'store', 
            'banners',
            'items', 
            'selectedItem', 
            'compatibles', 
            'groupedCompatibles',
            'brands', 
            'models', 
            'glassCodes', 
            'favoriteIds'
        ));
    }

    public function toggleFavorite(Request $request): JsonResponse
    {
        $request->validate([
            'glass_finder_item_id' => ['required', 'exists:glass_finder_items,id'],
            'action' => ['sometimes', 'in:add,remove'],
        ]);

        if (!auth()->check()) {
            return response()->json(['message' => 'Guest favorite saved locally'], 200);
        }

        $favorite = GlassFavorite::where('user_id', auth()->id())
            ->where('glass_finder_item_id', $request->glass_finder_item_id)
            ->first();

        // Explicit intent: honor add/remove instead of blindly toggling.
        // A blind toggle would let a guest's "unfavorite" (no server row yet)
        // create a ghost favorite after the user logs in on the same browser.
        $action = $request->input('action');

        if ($action === 'add') {
            if ($favorite) {
                return response()->json(['status' => 'added', 'message' => 'Favorite already saved']);
            }

            GlassFavorite::create([
                'user_id' => auth()->id(),
                'glass_finder_item_id' => $request->glass_finder_item_id,
            ]);

            return response()->json(['status' => 'added', 'message' => 'Favorite added']);
        }

        if ($action === 'remove') {
            if ($favorite) {
                $favorite->delete();
                return response()->json(['status' => 'removed', 'message' => 'Favorite removed']);
            }

            return response()->json(['status' => 'removed', 'message' => 'Favorite not found']);
        }

        // Legacy toggle behavior (clients that don't send an explicit action).
        if ($favorite) {
            $favorite->delete();
            return response()->json(['status' => 'removed', 'message' => 'Favorite removed']);
        }

        GlassFavorite::create([
            'user_id' => auth()->id(),
            'glass_finder_item_id' => $request->glass_finder_item_id,
        ]);

        return response()->json(['status' => 'added', 'message' => 'Favorite added']);
    }
}
