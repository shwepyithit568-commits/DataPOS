<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Models\WholesaleApplication;
use App\Services\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WholesaleAdminController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $storeRouteParams = ['store_slug' => $store->slug];
        $tab = $request->query('tab', $request->query('status', 'all'));

        $query = $this->filteredQuery($request, $store);

        // Tab filter
        if (in_array($tab, ['pending', 'approved', 'rejected', 'suspended'], true)) {
            $query->where('status', $tab);
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'oldest'    => $query->oldest('created_at'),
            'business'  => $query->orderBy('business_name', 'asc'),
            default     => $query->latest('created_at'),
        };

        $perPage = request('per_page') === 'all' ? 1000 : (int) request('per_page', 25);
        $applications = $query->paginate($perPage)->withQueryString();
        $totalCount = $applications->total();

        // Summary KPI stats for the store (unfiltered)
        $baseQuery = WholesaleApplication::where('store_id', $store->id);
        $stats = [
            'total'     => (clone $baseQuery)->count(),
            'pending'   => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved'  => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected'  => (clone $baseQuery)->where('status', 'rejected')->count(),
            'suspended' => (clone $baseQuery)->where('status', 'suspended')->count(),
        ];

        $exportUrl = route('store.admin.wholesale.applications.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'tab', 'status'])));

        return view('admin.wholesale.index', compact(
            'store',
            'storeRouteParams',
            'applications',
            'totalCount',
            'stats',
            'tab',
            'sort',
            'exportUrl'
        ));
    }

    public function show(string $store_slug, WholesaleApplication $application, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store || $application->store_id !== $store->id) {
            abort(403, 'Unauthorized store wholesale application access.');
        }

        $storeRouteParams = ['store_slug' => $store->slug];
        $application->load('user');

        return view('admin.wholesale.show', compact('store', 'storeRouteParams', 'application'));
    }

    public function print(string $store_slug, WholesaleApplication $application, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store || $application->store_id !== $store->id) {
            abort(403, 'Unauthorized store wholesale application access.');
        }

        $storeRouteParams = ['store_slug' => $store->slug];
        $application->load('user');
        $setting = $store->setting;

        return view('admin.wholesale.print', compact('store', 'storeRouteParams', 'application', 'setting'));
    }

    public function updateStatus(Request $request, string $store_slug, WholesaleApplication $application, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store || $application->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store wholesale application.');
        }

        $validated = $request->validate([
            'status'     => ['required', 'in:pending,approved,rejected,suspended'],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $statusChanged = $application->status !== $validated['status'];

        $updateData = ['status' => $validated['status']];
        if ($request->has('admin_note')) {
            $note = trim((string) ($validated['admin_note'] ?? ''));
            $updateData['admin_note'] = $note === '' ? null : $note;
        }

        $application->update($updateData);

        // Sync store_user pivot table membership status
        if ($application->user_id) {
            $user = User::find($application->user_id);
            if ($user) {
                $user->stores()->syncWithoutDetaching([
                    $store->id => [
                        'role'   => 'wholesale_customer',
                        'status' => $validated['status'] === 'approved' ? 'active' : ($validated['status'] === 'pending' ? 'pending' : 'suspended'),
                    ],
                ]);
            }
        }

        $statusLabels = [
            'pending'   => 'Pending Review (စစ်ဆေးဆဲ)',
            'approved'  => 'Approved (ခွင့်ပြုပြီး)',
            'rejected'  => 'Rejected (ငြင်းပယ်ထားသည်)',
            'suspended' => 'Suspended (ဆိုင်းငံ့ထားသည်)',
        ];

        return back()->with('success', 'Wholesale application status updated to: ' . ($statusLabels[$validated['status']] ?? $validated['status']));
    }

    public function destroy(string $store_slug, WholesaleApplication $application, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store || $application->store_id !== $store->id) {
            abort(403, 'Unauthorized store wholesale application access.');
        }

        $user = request()->user();
        if (!$user->isPlatformOwner() && !$user->hasStoreRole($store->id, ['store_manager'])) {
            abort(403, 'Only the store owner/manager can delete wholesale applications.');
        }

        $businessName = $application->business_name;
        $application->delete();

        return redirect()
            ->route('store.admin.wholesale.applications.index', ['store_slug' => $store->slug])
            ->with('success', "Wholesale application for '{$businessName}' has been deleted.");
    }

    public function export(Request $request, StoreContext $context): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $tab = $request->query('tab', $request->query('status', 'all'));
        $query = $this->filteredQuery($request, $store);

        if (in_array($tab, ['pending', 'approved', 'rejected', 'suspended'], true)) {
            $query->where('status', $tab);
        }

        $applications = $query->latest('created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="wholesale-applications-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($applications, $store) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, ['Wholesale Applications Report', $store->name]);
            fputcsv($stream, ['Export Date', now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($stream, []);

            fputcsv($stream, [
                'ID', 'Business Name', 'Applicant Name', 'Phone', 'Address',
                'Applied Date', 'Status', 'Applicant Note', 'Admin Internal Note',
            ]);

            foreach ($applications as $app) {
                fputcsv($stream, [
                    $app->id,
                    $app->business_name,
                    $app->user?->name ?? 'Guest',
                    $app->phone,
                    $app->address ?? '',
                    $app->created_at->format('Y-m-d H:i'),
                    strtoupper($app->status),
                    $app->notes ?? '',
                    $app->admin_note ?? '',
                ]);
            }

            fclose($stream);
        }, 'wholesale-applications-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv', $headers);
    }

    private function filteredQuery(Request $request, Store $store): Builder
    {
        $query = WholesaleApplication::where('store_id', $store->id)->with('user');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected', 'suspended'], true)) {
            $query->where('status', $request->status);
        }

        return $query;
    }
}
