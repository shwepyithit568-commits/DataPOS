<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\POS\Models\Branch;
use App\POS\Services\BranchManagementService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchManagementController extends Controller
{
    public function __construct(
        protected BranchManagementService $branchService
    ) {
    }

    /**
     * Display the Multi-Branch Management Dashboard.
     */
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $branches = $this->branchService->getBranches($store);
        $stats = $this->branchService->getSummaryStats($store);

        return view('admin.branches.index', compact('store', 'branches', 'stats'));
    }

    /**
     * Show form to create a new branch.
     */
    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $branch = new Branch([
            'is_active' => true,
            'is_default' => false,
        ]);

        return view('admin.branches.form', compact('store', 'branch'));
    }

    /**
     * Store a new branch.
     */
    public function store(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('branches', 'name')->where('store_id', $store->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->where('store_id', $store->id),
            ],
            'phone' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'manager_name' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
            'create_warehouse' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $createWarehouse = !empty($validated['create_warehouse']);
        $this->branchService->saveBranch($store, $validated, null, $createWarehouse, $request->user());

        return redirect()->route('store.admin.branches.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.branches_created_success'));
    }

    /**
     * Display detailed branch profile.
     */
    public function show(StoreContext $context, string $store_slug, int|string $branch): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $branch = Branch::where('store_id', $store->id)->with('warehouses')->findOrFail($branch);

        return view('admin.branches.show', compact('store', 'branch'));
    }

    /**
     * Show form to edit a branch.
     */
    public function edit(StoreContext $context, string $store_slug, int|string $branch): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $branch = Branch::where('store_id', $store->id)->findOrFail($branch);

        return view('admin.branches.form', compact('store', 'branch'));
    }

    /**
     * Update an existing branch.
     */
    public function update(StoreContext $context, string $store_slug, int|string $branch, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $branch = Branch::where('store_id', $store->id)->findOrFail($branch);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('branches', 'name')->where('store_id', $store->id)->ignore($branch->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->where('store_id', $store->id)->ignore($branch->id),
            ],
            'phone' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'manager_name' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $this->branchService->saveBranch($store, $validated, $branch, false, $request->user());

        return redirect()->route('store.admin.branches.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.branches_updated_success'));
    }

    /**
     * Delete a branch.
     */
    public function destroy(StoreContext $context, string $store_slug, int|string $branch, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $branch = Branch::where('store_id', $store->id)->findOrFail($branch);

        if ($branch->is_default) {
            return back()->withErrors(['error' => __('messages.branches_cannot_delete_default')]);
        }

        $this->branchService->deleteBranch($store, $branch, $request->user());

        return redirect()->route('store.admin.branches.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.branches_deleted_success'));
    }

    /**
     * Set a branch as default.
     */
    public function setDefault(StoreContext $context, string $store_slug, int|string $branch, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $branch = Branch::where('store_id', $store->id)->findOrFail($branch);
        $this->branchService->setDefault($store, $branch, $request->user());

        return redirect()->route('store.admin.branches.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.branches_set_default_success'));
    }
}
