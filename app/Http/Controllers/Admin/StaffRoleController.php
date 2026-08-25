<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffRole;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffRoleController extends Controller
{
    /**
     * Display list of roles and staff assignment matrix.
     */
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        // Bootstrap default roles if none exist
        StaffRole::bootstrapDefaultRoles($store);

        $query = StaffRole::where('store_id', $store->id);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'active');
        }

        $sort = $request->query('sort', 'name_asc');
        $query = match ($sort) {
            'newest'    => $query->latest(),
            'oldest'    => $query->oldest(),
            'name_desc' => $query->orderByDesc('name'),
            default     => $query->orderBy('name'),
        };

        $perPage = $request->query('per_page', 25);
        if ($perPage === 'all') {
            $roles = $query->get();
        } else {
            $roles = $query->paginate((int) $perPage)->withQueryString();
        }

        // Fetch staff members in this store
        $staffMembers = DB::table('store_user')
            ->join('users', 'users.id', '=', 'store_user.user_id')
            ->leftJoin('staff_roles', 'staff_roles.id', '=', 'store_user.staff_role_id')
            ->where('store_user.store_id', $store->id)
            ->whereIn('store_user.role', ['store_manager', 'staff'])
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'users.phone as user_phone',
                'users.email as user_email',
                'store_user.role as high_level_role',
                'store_user.staff_role_id',
                'store_user.status as staff_status',
                'staff_roles.name as role_name',
                'staff_roles.color as role_color'
            )
            ->orderBy('users.name')
            ->get();

        $allRolesForSelect = StaffRole::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get();

        // Metrics
        $totalRoles = StaffRole::where('store_id', $store->id)->count();
        $activeRoles = StaffRole::where('store_id', $store->id)->where('is_active', true)->count();
        $totalStaff = $staffMembers->count();
        $unassignedStaff = $staffMembers->whereNull('staff_role_id')->count();

        $metrics = [
            'total_roles'      => $totalRoles,
            'active_roles'     => $activeRoles,
            'total_staff'      => $totalStaff,
            'unassigned_staff' => $unassignedStaff,
        ];

        $permissionGroups = StaffRole::PERMISSION_GROUPS;

        return view('admin.roles.index', compact(
            'store',
            'roles',
            'staffMembers',
            'allRolesForSelect',
            'metrics',
            'permissionGroups',
            'perPage',
            'sort'
        ));
    }

    /**
     * Store a new custom staff role.
     */
    public function store(Request $request, StoreContext $context, string $store_slug = ''): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:20',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $slug = Str::slug($validated['name']) . '-' . rand(100, 999);

        StaffRole::create([
            'store_id'    => $store->id,
            'name'        => $validated['name'],
            'slug'        => $slug,
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? '#0284c7',
            'permissions' => $validated['permissions'] ?? [],
            'is_system'   => false,
            'is_active'   => true,
        ]);

        return back()->with('success', __('messages.role_created_success'));
    }

    /**
     * Update an existing staff role.
     */
    public function update(Request $request, StoreContext $context, string $store_slug, int|string $role): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $staffRole = StaffRole::where('store_id', $store->id)->findOrFail((int) $role);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:20',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_active'   => 'nullable|boolean',
        ]);

        $staffRole->update([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'] ?? $staffRole->color,
            'permissions' => $validated['permissions'] ?? [],
            'is_active'   => $request->has('is_active') ? (bool) $request->input('is_active') : $staffRole->is_active,
        ]);

        return back()->with('success', __('messages.role_updated_success'));
    }

    /**
     * Delete a custom staff role.
     */
    public function destroy(Request $request, StoreContext $context, string $store_slug, int|string $role): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $staffRole = StaffRole::where('store_id', $store->id)->findOrFail((int) $role);

        if ($staffRole->is_system) {
            return back()->withErrors(['error' => __('messages.system_role_cannot_delete')]);
        }

        // Unlink any staff currently using this role
        DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('staff_role_id', $staffRole->id)
            ->update(['staff_role_id' => null]);

        $staffRole->delete();

        return back()->with('success', __('messages.role_deleted_success'));
    }

    /**
     * Assign a role to a staff member.
     */
    public function assignStaff(Request $request, StoreContext $context, string $store_slug = ''): RedirectResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $validated = $request->validate([
            'user_id'       => 'required|integer',
            'staff_role_id' => 'nullable|integer|exists:staff_roles,id',
        ]);

        DB::table('store_user')
            ->where('store_id', $store->id)
            ->where('user_id', $validated['user_id'])
            ->update(['staff_role_id' => $validated['staff_role_id']]);

        return back()->with('success', __('messages.staff_role_assigned_success'));
    }

    /**
     * Export Roles Matrix to CSV.
     */
    public function exportCsv(Request $request, StoreContext $context, string $store_slug = ''): StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $roles = StaffRole::where('store_id', $store->id)->orderBy('name')->get();
        $filename = 'staff-roles-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($store, $roles) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['Staff Roles & Permissions Directory', $store->name]);
            fputcsv($handle, ['Generated Date', now()->toFormattedDateString() . ' ' . now()->format('h:i A')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Role Name',
                'Role Slug',
                'Type',
                'Status',
                'Assigned Staff Count',
                'Total Permissions',
                'Permissions List',
                'Description',
            ]);

            foreach ($roles as $r) {
                $perms = $r->permissions ?? [];
                $permString = in_array('*', $perms, true) ? 'ALL (Full Admin)' : implode(', ', $perms);

                fputcsv($handle, [
                    $r->name,
                    $r->slug,
                    $r->is_system ? 'System Default' : 'Custom Store Role',
                    $r->is_active ? 'Active' : 'Inactive',
                    $r->staff_count,
                    in_array('*', $perms, true) ? 'ALL' : count($perms),
                    $permString,
                    $r->description ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
