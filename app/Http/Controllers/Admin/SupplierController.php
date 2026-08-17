<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Supplier master data — currently only the quick-add used by the product
 * form (full supplier management ships with the Purchasing module, Phase 4).
 */
class SupplierController extends Controller
{
    public function quickStore(Request $request, StoreContext $context): JsonResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        // Reuse an existing supplier with the same name (store-scoped) —
        // quick-add should never create duplicates from retries.
        $supplier = Supplier::where('store_id', $store->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($validated['name']))])
            ->first();

        if (! $supplier) {
            $supplier = Supplier::create([
                'store_id' => $store->id,
                'name' => trim($validated['name']),
                'phone' => $validated['phone'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'id' => $supplier->id,
            'name' => $supplier->name,
        ]);
    }
}
