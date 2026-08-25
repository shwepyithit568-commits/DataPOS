@extends('layouts.admin.app')

@section('title', __('messages.roles_title') . ' - ' . ($store->name ?? 'DataPOS'))

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $rolesArray = ($roles instanceof \Illuminate\Pagination\LengthAwarePaginator ? $roles->items() : $roles->all());
    $staffArray = $staffMembers->all();

    $allPermGroupKeys = [];
    $allAvailableKeys = [];
    foreach ($permissionGroups as $gKey => $gData) {
        $keys = array_keys($gData['permissions']);
        $allPermGroupKeys[$gKey] = $keys;
        $allAvailableKeys = array_merge($allAvailableKeys, $keys);
    }
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        activeTab: 'roles',
        viewMode: localStorage.getItem('admin_view_mode') || 'card',
        createModalOpen: false,
        editModalOpen: false,
        assignModalOpen: false,
        allRolesList: {{ Illuminate\Support\Js::from($rolesArray) }},
        allStaffList: {{ Illuminate\Support\Js::from($staffArray) }},
        permissionGroups: {{ Illuminate\Support\Js::from($allPermGroupKeys) }},
        allAvailablePermissions: {{ Illuminate\Support\Js::from($allAvailableKeys) }},
        newRole: {
            name: '',
            description: '',
            color: '#0284c7',
            permissions: []
        },
        editingRole: {
            id: null,
            name: '',
            description: '',
            color: '#0284c7',
            is_active: true,
            permissions: []
        },
        assignData: {
            user_id: '',
            user_name: '',
            staff_role_id: ''
        },
        openCreateModal() {
            this.newRole = {
                name: '',
                description: '',
                color: '#0284c7',
                permissions: []
            };
            this.createModalOpen = true;
        },
        openEditModalById(roleId) {
            const role = this.allRolesList.find(r => r.id === roleId);
            if (role) {
                let perms = Array.isArray(role.permissions) ? [...role.permissions] : [];
                if (perms.includes('*')) {
                    perms = [...this.allAvailablePermissions];
                }
                this.editingRole = {
                    id: role.id,
                    name: role.name,
                    description: role.description || '',
                    color: role.color || '#0284c7',
                    is_active: !!role.is_active,
                    permissions: perms
                };
                this.editModalOpen = true;
            }
        },
        openAssignModalById(userId) {
            const staff = this.allStaffList.find(s => s.user_id === userId);
            if (staff) {
                this.assignData = {
                    user_id: staff.user_id,
                    user_name: staff.user_name,
                    staff_role_id: staff.staff_role_id || ''
                };
                this.assignModalOpen = true;
            }
        },
        toggleGroup(targetObj, groupKey) {
            const groupKeys = this.permissionGroups[groupKey] || [];
            const hasAll = groupKeys.every(k => targetObj.permissions.includes(k));
            if (hasAll) {
                targetObj.permissions = targetObj.permissions.filter(p => !groupKeys.includes(p));
            } else {
                const combined = [...targetObj.permissions, ...groupKeys];
                targetObj.permissions = [...new Set(combined)];
            }
        },
        selectAll(targetObj) {
            targetObj.permissions = [...this.allAvailablePermissions];
        },
        deselectAll(targetObj) {
            targetObj.permissions = [];
        }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                🛡️
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        {{ __('messages.admin_dashboard') }}
                    </a>
                    <span>/</span>
                    <span class="text-red-600 dark:text-red-400">{{ __('messages.sidebar_security') }}</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span class="truncate">{{ __('messages.roles_title') }}</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · {{ __('messages.roles_subtitle') }}</p>
            </div>
        </div>

        {{-- Top Right Action (Create Role Button) --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <button type="button" @click.stop="openCreateModal()"
                    class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-500/20 transition flex items-center gap-2 active:scale-95">
                <span class="text-base leading-none">+</span>
                <span>{{ __('messages.roles_new') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-3xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 2. 4 Key Roles KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.roles_total') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ $metrics['total_roles'] }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">{{ $metrics['active_roles'] }} Active Templates</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🎭
            </span>
        </div>

        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.roles_active') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ $metrics['active_roles'] }}</h3>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">Ready for assignment</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                ✅
            </span>
        </div>

        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.roles_total_staff') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">{{ $metrics['total_staff'] }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Managers & Staff</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                👥
            </span>
        </div>

        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.roles_unassigned_staff') }}</p>
                <h3 class="text-xl sm:text-2xl font-black {{ $metrics['unassigned_staff'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }} font-mono tracking-tight">{{ $metrics['unassigned_staff'] }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Need Role Assignment</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                ⚠️
            </span>
        </div>
    </div>

    {{-- Tab Switches --}}
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800">
        <button type="button" @click="activeTab = 'roles'"
                :class="activeTab === 'roles' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                class="py-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2">
            <span>🎭</span>
            <span>{{ __('messages.roles_tab_roles') }}</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">{{ $metrics['total_roles'] }}</span>
        </button>

        <button type="button" @click="activeTab = 'staff'"
                :class="activeTab === 'staff' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                class="py-3 px-4 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2">
            <span>👥</span>
            <span>{{ __('messages.roles_tab_staff') }}</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">{{ $metrics['total_staff'] }}</span>
        </button>
    </div>

    {{-- SECTION 1: ROLES & PERMISSIONS MATRIX TAB --}}
    <div x-show="activeTab === 'roles'" class="space-y-4">
        
        {{-- Unified Admin Toolbar --}}
        @php
            $statusFilterOptions = [
                'active'   => __('messages.roles_status_active'),
                'inactive' => __('messages.roles_status_inactive'),
            ];

            $exportUrl = route('store.admin.roles.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'status'])));
        @endphp

        <x-admin.toolbar
            :search="request('search', '')"
            :searchPlaceholder="__('messages.roles_filter_search')"
            :sort="request('sort', $sort)"
            :sortOptions="[
                'name_asc'   => __('messages.roles_sort_name_asc'),
                'name_desc'  => __('messages.roles_sort_name_desc'),
                'newest'     => __('messages.roles_sort_newest'),
                'oldest'     => __('messages.roles_sort_oldest'),
            ]"
            :filters="[
                'status' => [
                    'label' => __('messages.roles_filter_status'),
                    'options' => $statusFilterOptions,
                ],
            ]"
            :viewMode="'card'"
            :showViewToggle="true"
            :showExportImport="true"
            :exportUrl="$exportUrl"
            :totalCount="$roles instanceof \Illuminate\Pagination\LengthAwarePaginator ? $roles->total() : $roles->count()"
            :paginator="$roles instanceof \Illuminate\Pagination\LengthAwarePaginator ? $roles : null"
            :perPageOptions="[25 => '25', 50 => '50', 100 => '100', 'all' => 'All']"
        />

        {{-- Role Cards Grid View --}}
        <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($roles as $role)
                @php
                    $perms = $role->permissions ?? [];
                    $isAll = in_array('*', $perms, true);
                    $permCount = $isAll ? 'Full Access' : count($perms) . ' Permissions';
                @endphp
                <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: {{ $role->color }};"></span>
                                <div>
                                    <h3 class="font-black text-sm text-slate-900 dark:text-slate-100 group-hover:text-blue-600 transition">
                                        {{ $role->name }}
                                    </h3>
                                    <span class="text-[10px] font-mono text-slate-400">{{ $role->slug }}</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $role->is_system ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $role->is_system ? 'System Default' : 'Custom' }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                            {{ $role->description ?: 'No description provided.' }}
                        </p>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                <span class="text-slate-400 block text-[10px] font-bold uppercase">Staff Assigned</span>
                                <span class="font-mono font-black text-slate-800 dark:text-slate-200 text-sm">
                                    {{ $role->staff_count }} members
                                </span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                <span class="text-slate-400 block text-[10px] font-bold uppercase">Permissions</span>
                                <span class="font-mono font-black text-blue-600 dark:text-blue-400 text-xs truncate block">
                                    {{ $permCount }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        <button type="button" @click.stop="openEditModalById({{ (int) $role->id }})"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1">
                            <span>✏️</span>
                            <span>Edit Permissions</span>
                        </button>

                        @if (! $role->is_system)
                            <form action="{{ route('store.admin.roles.destroy', array_merge($storeRouteParams, ['role' => $role->id])) }}"
                                  method="POST" onsubmit="return confirm('Are you sure you want to delete this role?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                    {{ __('messages.roles_no_records') }}
                </div>
            @endforelse
        </div>

        {{-- Role Table View --}}
        <div x-show="viewMode === 'table'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4">Role Name</th>
                            <th class="py-3.5 px-4">Description</th>
                            <th class="py-3.5 px-4 text-center">Type</th>
                            <th class="py-3.5 px-4 text-center">Assigned Staff</th>
                            <th class="py-3.5 px-4 text-center">Permissions Count</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($roles as $role)
                            @php
                                $perms = $role->permissions ?? [];
                                $isAll = in_array('*', $perms, true);
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $role->color }};"></span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100 text-sm">{{ $role->name }}</span>
                                    </div>
                                    <span class="text-[11px] font-mono text-slate-400 ml-5">{{ $role->slug }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                    {{ $role->description ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $role->is_system ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $role->is_system ? 'System' : 'Custom' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $role->staff_count }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-blue-600 dark:text-blue-400">
                                    {{ $isAll ? 'Full Access' : count($perms) }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" @click.stop="openEditModalById({{ (int) $role->id }})"
                                                class="px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 transition">
                                            Edit
                                        </button>
                                        @if (! $role->is_system)
                                            <form action="{{ route('store.admin.roles.destroy', array_merge($storeRouteParams, ['role' => $role->id])) }}"
                                                  method="POST" onsubmit="return confirm('Are you sure you want to delete this role?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                    {{ __('messages.roles_no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SECTION 2: STAFF MEMBERS DIRECTORY TAB --}}
    <div x-show="activeTab === 'staff'" class="space-y-4">
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-900 dark:text-white">Store Staff & Assigned Permissions</h3>
                <span class="text-xs text-slate-400">{{ $staffMembers->count() }} Staff Members</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-4">Staff Member</th>
                            <th class="py-3.5 px-4">Contact</th>
                            <th class="py-3.5 px-4">System Role</th>
                            <th class="py-3.5 px-4">Assigned Role Template</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($staffMembers as $staff)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-slate-100 text-sm">
                                    {{ $staff->user_name }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-500 dark:text-slate-400">
                                    {{ $staff->user_phone ?: $staff->user_email }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-600 dark:text-slate-300 capitalize">
                                    {{ str_replace('_', ' ', $staff->high_level_role) }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($staff->role_name)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border"
                                              style="background-color: {{ $staff->role_color }}15; color: {{ $staff->role_color }}; border-color: {{ $staff->role_color }}30;">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $staff->role_color }};"></span>
                                            <span>{{ $staff->role_name }}</span>
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                            Unassigned
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 uppercase">
                                        {{ $staff->staff_status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button type="button" @click.stop="openAssignModalById({{ (int) $staff->user_id }})"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300 transition inline-flex items-center gap-1">
                                        <span>🎭</span>
                                        <span>Change Role</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                    No staff members found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL 1: CREATE NEW ROLE MODAL --}}
    <div x-show="createModalOpen" x-cloak
         @click.self="createModalOpen = false"
         @keydown.escape.window="createModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-3xl bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5 border border-slate-200 dark:border-slate-800 my-8 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>✨</span>
                    <span>Create New Staff Role</span>
                </h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
            </div>

            <form action="{{ route('store.admin.roles.store', $storeRouteParams) }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Role Name *</label>
                        <input type="text" name="name" x-model="newRole.name" required
                               placeholder="e.g. Senior Cashier"
                               class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Badge Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color" x-model="newRole.color"
                                   class="h-9 w-12 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer">
                            <input type="text" x-model="newRole.color" readonly
                                   class="w-full px-3.5 py-2 rounded-xl text-xs font-mono bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea name="description" x-model="newRole.description" rows="2"
                                  placeholder="Role duties and responsibilities summary..."
                                  class="w-full px-3.5 py-2 rounded-xl text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                {{-- Granular Permissions Matrix Section --}}
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Granular Permissions Matrix</h4>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="selectAll(newRole)" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">Select All</button>
                            <span class="text-slate-300 dark:text-slate-700">|</span>
                            <button type="button" @click="deselectAll(newRole)" class="text-[11px] font-bold text-slate-500 hover:underline">Clear All</button>
                            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-bold ml-1" x-text="`${newRole.permissions.length} selected`"></span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($permissionGroups as $groupKey => $group)
                            <div class="p-3.5 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-800 dark:text-slate-200">{{ $group['label'] }}</span>
                                    <button type="button" @click="toggleGroup(newRole, '{{ $groupKey }}')"
                                            class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                        Toggle All
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($group['permissions'] as $permKey => $permLabel)
                                        <label class="flex items-start gap-2 p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-blue-300 transition cursor-pointer text-xs">
                                            <input type="checkbox" name="permissions[]" value="{{ $permKey }}"
                                                   x-model="newRole.permissions"
                                                   class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $permKey }}</span>
                                                <span class="text-[11px] text-slate-400 block leading-tight">{{ $permLabel }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-500/20 transition">
                        Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: EDIT ROLE MODAL --}}
    <div x-show="editModalOpen" x-cloak
         @click.self="editModalOpen = false"
         @keydown.escape.window="editModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-3xl bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5 border border-slate-200 dark:border-slate-800 my-8 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>✏️</span>
                    <span>Edit Staff Role</span>
                </h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
            </div>

            <form :action="'{{ url('/store/' . $store->slug . '/admin/security/roles') }}/' + editingRole.id"
                  method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Role Name *</label>
                        <input type="text" name="name" x-model="editingRole.name" required
                               placeholder="e.g. Senior Cashier"
                               class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Badge Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color" x-model="editingRole.color"
                                   class="h-9 w-12 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer">
                            <input type="text" x-model="editingRole.color" readonly
                                   class="w-full px-3.5 py-2 rounded-xl text-xs font-mono bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                        <textarea name="description" x-model="editingRole.description" rows="2"
                                  placeholder="Role duties and responsibilities summary..."
                                  class="w-full px-3.5 py-2 rounded-xl text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="editingRole.is_active"
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Active (Available for staff assignment)</span>
                        </label>
                    </div>
                </div>

                {{-- Granular Permissions Matrix Section --}}
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Granular Permissions Matrix</h4>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="selectAll(editingRole)" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">Select All</button>
                            <span class="text-slate-300 dark:text-slate-700">|</span>
                            <button type="button" @click="deselectAll(editingRole)" class="text-[11px] font-bold text-slate-500 hover:underline">Clear All</button>
                            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-bold ml-1" x-text="`${editingRole.permissions.length} selected`"></span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($permissionGroups as $groupKey => $group)
                            <div class="p-3.5 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black text-slate-800 dark:text-slate-200">{{ $group['label'] }}</span>
                                    <button type="button" @click="toggleGroup(editingRole, '{{ $groupKey }}')"
                                            class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                        Toggle All
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach ($group['permissions'] as $permKey => $permLabel)
                                        <label class="flex items-start gap-2 p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-blue-300 transition cursor-pointer text-xs">
                                            <input type="checkbox" name="permissions[]" value="{{ $permKey }}"
                                                   x-model="editingRole.permissions"
                                                   class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $permKey }}</span>
                                                <span class="text-[11px] text-slate-400 block leading-tight">{{ $permLabel }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-500/20 transition">
                        Update Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: ASSIGN STAFF ROLE MODAL --}}
    <div x-show="assignModalOpen" x-cloak
         @click.self="assignModalOpen = false"
         @keydown.escape.window="assignModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🎭</span>
                    <span>Assign Staff Role</span>
                </h3>
                <button type="button" @click="assignModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
            </div>

            <form action="{{ route('store.admin.roles.assign_staff', $storeRouteParams) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="user_id" :value="assignData.user_id">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Staff Member</label>
                    <input type="text" :value="assignData.user_name" readonly
                           class="w-full px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Select Role Template *</label>
                    <select name="staff_role_id" x-model="assignData.staff_role_id"
                            class="w-full px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- No Specific Role (Default) --</option>
                        @foreach ($allRolesForSelect as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->is_system ? 'System' : 'Custom' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" @click="assignModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-500/20 transition">
                        Confirm Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
