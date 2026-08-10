<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WholesaleApplication;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WholesaleAdminController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();

        $query = WholesaleApplication::where('store_id', $store->id)->with('user');

        // Search: business name, phone, or applicant name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Filter: status
        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected', 'suspended'])) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'oldest'    => $query->oldest(),
            'business'  => $query->orderBy('business_name', 'asc'),
            default     => $query->latest(),
        };

        // Summary stats for the current filtered set — computed BEFORE paginate to avoid clone issues
        $stats = [
            'total'     => $query->count(),
            'pending'   => (clone $query)->where('status', 'pending')->count(),
            'approved'  => (clone $query)->where('status', 'approved')->count(),
            'rejected'  => (clone $query)->where('status', 'rejected')->count(),
            'suspended' => (clone $query)->where('status', 'suspended')->count(),
        ];

        $perPage = request('per_page') === 'all' ? 100000 : (int) request('per_page', 25);
        $applications = $query->paginate($perPage)->withQueryString();
        $totalCount = $applications->total();

        return view('admin.wholesale.index', compact('store', 'applications', 'totalCount', 'stats'));
    }

    public function updateStatus(Request $request, string $store_slug, WholesaleApplication $application, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        // Security check: Store A admin cannot manage Store B applications
        if ($application->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store wholesale application.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected,suspended'],
            'notes' => ['nullable', 'string'],
        ]);

        $application->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $application->notes,
        ]);

        // Sync store_user pivot table membership status
        $user = User::findOrFail($application->user_id);
        $user->stores()->syncWithoutDetaching([
            $store->id => [
                'role' => 'wholesale_customer',
                'status' => $validated['status'] === 'approved' ? 'active' : $validated['status'],
            ]
        ]);

        return back()->with('success', 'Wholesale application status updated to ' . $validated['status']);
    }
}
