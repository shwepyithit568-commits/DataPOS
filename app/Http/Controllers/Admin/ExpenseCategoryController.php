<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\POS\Models\ExpenseCategory;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $query = ExpenseCategory::where('store_id', $store->id);

        // Search by Name, Code, or Description
        $search = trim((string) $request->input('search', $request->input('q', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by Status (active / inactive)
        $status = $request->input('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Sorting
        $sort = $request->input('sort', 'order_asc');
        match ($sort) {
            'newest' => $query->latest('id'),
            'oldest' => $query->oldest('id'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc'),
        };

        // Summary metrics across all expense categories for this store
        $baseQuery = ExpenseCategory::where('store_id', $store->id);
        $metrics = [
            'total_count' => (clone $baseQuery)->count(),
            'active_count' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive_count' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $perPage = $request->input('per_page', 25);
        $perPageCount = ($perPage === 'all' || (int) $perPage > 1000) ? 10000 : (int) $perPage;

        $categories = $query->paginate($perPageCount)->withQueryString();

        return view('admin.expense_categories.index', compact(
            'store',
            'storeRouteParams',
            'categories',
            'metrics',
            'search',
            'status',
            'sort'
        ));
    }

    public function store(Request $request, StoreContext $context, string $store_slug): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expense_categories', 'name')->where('store_id', $store->id),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ExpenseCategory::create([
            'store_id' => $store->id,
            'name' => trim($validated['name']),
            'code' => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'color' => ! empty($validated['color']) ? trim($validated['color']) : '#6366f1',
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('store.admin.expense_categories.index', $storeRouteParams)
            ->with('success', __('messages.expense_category_created_success'));
    }

    public function update(Request $request, StoreContext $context, string $store_slug, int|string $category): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $categoryModel = ExpenseCategory::where('store_id', $store->id)->where('id', $category)->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expense_categories', 'name')
                    ->where('store_id', $store->id)
                    ->ignore($categoryModel->id),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $categoryModel->update([
            'name' => trim($validated['name']),
            'code' => ! empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'color' => ! empty($validated['color']) ? trim($validated['color']) : '#6366f1',
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('store.admin.expense_categories.index', $storeRouteParams)
            ->with('success', __('messages.expense_category_updated_success'));
    }

    public function toggle(StoreContext $context, string $store_slug, int|string $category): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $categoryModel = ExpenseCategory::where('store_id', $store->id)->where('id', $category)->firstOrFail();

        $categoryModel->update([
            'is_active' => ! $categoryModel->is_active,
        ]);

        $statusMsg = $categoryModel->is_active
            ? __('messages.expense_category_activated_success')
            : __('messages.expense_category_deactivated_success');

        return redirect()
            ->route('store.admin.expense_categories.index', $storeRouteParams)
            ->with('success', $statusMsg);
    }

    public function destroy(StoreContext $context, string $store_slug, int|string $category): RedirectResponse
    {
        $store = $context->getStore();
        $storeRouteParams = $context->getRouteParams();

        $categoryModel = ExpenseCategory::where('store_id', $store->id)->where('id', $category)->firstOrFail();
        $categoryModel->delete();

        return redirect()
            ->route('store.admin.expense_categories.index', $storeRouteParams)
            ->with('success', __('messages.expense_category_deleted_success'));
    }
}
