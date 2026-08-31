@extends('layouts.admin.app')

@section('title', __('messages.roles_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $rolesArray = ($roles instanceof \Illuminate\Pagination\LengthAwarePaginator ? $roles->items() : $roles->all());
    $staffArray = $staffMembers->all();

    $allPermGroupKeys = [];
    $allAvailableKeys = [];
    $allModuleActionMap = []; // [groupKey => [moduleKey => [view, edit, delete]]]

    foreach ($permissionGroups as $gKey => $gData) {
        $groupPerms = [];
        $allModuleActionMap[$gKey] = [];
        foreach ($gData['modules'] as $mKey => $mData) {
            $mPerms = $mData['permissions'];
            $allModuleActionMap[$gKey][$mKey] = $mPerms;
            foreach ($mPerms as $actKey => $pKey) {
                $groupPerms[] = $pKey;
                $allAvailableKeys[] = $pKey;
            }
        }
        $allPermGroupKeys[$gKey] = $groupPerms;
    }

    $exportUrl = route('store.admin.roles.export', array_merge($storeRouteParams, request()->only(['search', 'sort', 'status'])));
@endphp

@section('content')
<div class="w-full space-y-3 sm:space-y-4"
     x-data="{
        activeTab: 'roles',
        viewMode: localStorage.getItem('admin_roles_view_mode') || 'card',
        staffSearch: '',
        staffFilter: 'all',
        permSearch: '',
        createModalOpen: false,
        editModalOpen: false,
        assignModalOpen: false,
        allRolesList: {{ Illuminate\Support\Js::from($rolesArray) }},
        allStaffList: {{ Illuminate\Support\Js::from($staffArray) }},
        permissionGroups: {{ Illuminate\Support\Js::from($allPermGroupKeys) }},
        allAvailablePermissions: {{ Illuminate\Support\Js::from(array_unique($allAvailableKeys)) }},
        moduleActionMap: {{ Illuminate\Support\Js::from($allModuleActionMap) }},
        colorPresets: ['#0284c7', '#10b981', '#8b5cf6', '#f59e0b', '#e11d48', '#06b6d4', '#475569'],
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
            user_phone: '',
            staff_role_id: '',
            mode: 'select', // 'select' or 'create_custom'
            custom_role_name: '',
            custom_role_desc: '',
            custom_role_color: '#0284c7',
            custom_role_permissions: []
        },
        hasPerm(targetObj, permKey) {
            if (!targetObj || !Array.isArray(targetObj.permissions)) return false;
            return targetObj.permissions.includes(permKey);
        },
        togglePerm(targetObj, permKey) {
            if (!targetObj || !Array.isArray(targetObj.permissions)) return;
            if (this.hasPerm(targetObj, permKey)) {
                targetObj.permissions = targetObj.permissions.filter(p => p !== permKey);
            } else {
                targetObj.permissions.push(permKey);
            }
        },
        toggleModuleRow(targetObj, pView, pEdit, pDelete) {
            const rowKeys = [pView, pEdit, pDelete].filter(Boolean);
            const hasAll = rowKeys.every(k => this.hasPerm(targetObj, k));
            if (hasAll) {
                targetObj.permissions = targetObj.permissions.filter(p => !rowKeys.includes(p));
            } else {
                targetObj.permissions = [...new Set([...targetObj.permissions, ...rowKeys])];
            }
        },
        isModuleFull(targetObj, pView, pEdit, pDelete) {
            const rowKeys = [pView, pEdit, pDelete].filter(Boolean);
            return rowKeys.length > 0 && rowKeys.every(k => this.hasPerm(targetObj, k));
        },
        toggleGroupColumn(targetObj, groupKey, actionType) {
            const modules = this.moduleActionMap[groupKey] || {};
            const colKeys = [];
            for (const mKey in modules) {
                if (modules[mKey][actionType]) {
                    colKeys.push(modules[mKey][actionType]);
                }
            }
            const hasAll = colKeys.every(k => this.hasPerm(targetObj, k));
            if (hasAll) {
                targetObj.permissions = targetObj.permissions.filter(p => !colKeys.includes(p));
            } else {
                targetObj.permissions = [...new Set([...targetObj.permissions, ...colKeys])];
            }
        },
        toggleGroupAll(targetObj, groupKey) {
            const groupKeys = this.permissionGroups[groupKey] || [];
            const hasAll = groupKeys.every(k => this.hasPerm(targetObj, k));
            if (hasAll) {
                targetObj.permissions = targetObj.permissions.filter(p => !groupKeys.includes(p));
            } else {
                targetObj.permissions = [...new Set([...targetObj.permissions, ...groupKeys])];
            }
        },
        groupCount(targetObj, groupKey) {
            const groupKeys = this.permissionGroups[groupKey] || [];
            return groupKeys.filter(k => this.hasPerm(targetObj, k)).length;
        },
        selectAll(targetObj) {
            targetObj.permissions = [...this.allAvailablePermissions];
        },
        deselectAll(targetObj) {
            targetObj.permissions = [];
        },
        openCreateModal() {
            this.permSearch = '';
            this.newRole = {
                name: '',
                description: '',
                color: '#0284c7',
                permissions: []
            };
            this.createModalOpen = true;
        },
        openEditModalById(roleId) {
            this.permSearch = '';
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
        openAssignModalById(userId, initialMode = 'select') {
            this.permSearch = '';
            const staff = this.allStaffList.find(s => s.user_id === userId);
            if (staff) {
                this.assignData = {
                    user_id: staff.user_id,
                    user_name: staff.user_name,
                    user_phone: staff.user_phone || staff.user_email || '',
                    staff_role_id: staff.staff_role_id || '',
                    mode: initialMode,
                    custom_role_name: staff.user_name ? (staff.user_name + ' Role') : 'Custom Staff Role',
                    custom_role_desc: 'Custom assigned permissions for ' + (staff.user_name || 'staff member'),
                    custom_role_color: '#0284c7',
                    permissions: []
                };
                this.assignModalOpen = true;
            }
        },
        filteredStaff() {
            return this.allStaffList.filter(s => {
                const matchesSearch = !this.staffSearch || 
                    (s.user_name && s.user_name.toLowerCase().includes(this.staffSearch.toLowerCase())) ||
                    (s.user_phone && s.user_phone.toLowerCase().includes(this.staffSearch.toLowerCase())) ||
                    (s.user_email && s.user_email.toLowerCase().includes(this.staffSearch.toLowerCase())) ||
                    (s.role_name && s.role_name.toLowerCase().includes(this.staffSearch.toLowerCase()));
                
                if (!matchesSearch) return false;

                if (this.staffFilter === 'assigned') return !!s.staff_role_id;
                if (this.staffFilter === 'unassigned') return !s.staff_role_id;
                return true;
            });
        }
     }"
     @keydown.escape.window="createModalOpen = false; editModalOpen = false; assignModalOpen = false"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_roles_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-red-50 dark:bg-red-950/60 border border-red-200/60 dark:border-red-800/60 text-red-600 dark:text-red-400 grid place-items-center text-lg sm:text-xl font-bold shadow-sm shrink-0">
                🛡️
            </div>
            <div class="min-w-0">
                <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span class="truncate">{{ __('messages.roles_title') }}</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · {{ __('messages.roles_subtitle') }}</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
            <a href="{{ route('store.admin.audit-logs.index', $storeRouteParams) }}"
               class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition flex items-center gap-1.5 shadow-sm">
                <span>📋</span>
                <span>{{ __('messages.sidebar_audit_logs') }}</span>
            </a>

            <a href="{{ $exportUrl }}"
               class="px-3 py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-1.5 shadow-sm">
                <span>📊</span>
                <span>{{ __('messages.export_csv_button') }}</span>
            </a>

            <button type="button" @click.stop="openCreateModal()"
                    class="px-3.5 py-2 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-sm shadow-blue-500/20 transition flex items-center gap-1.5 active:scale-95">
                <span class="text-sm leading-none font-bold">+</span>
                <span>{{ __('messages.roles_new') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black text-xs shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-2xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            @foreach ($errors->all() as $error)
                <p>• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 2. 4 Key Roles KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 shadow-sm flex items-center justify-between transition hover:shadow">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-0.5 truncate">{{ __('messages.roles_total') }}</p>
                <h3 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ $metrics['total_roles'] }}</h3>
                <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5">{{ $metrics['active_roles'] }} {{ __('messages.roles_status_active') }}</p>
            </div>
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-lg sm:text-xl font-bold shadow-inner shrink-0">
                🎭
            </div>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 shadow-sm flex items-center justify-between transition hover:shadow">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-0.5 truncate">{{ __('messages.roles_active') }}</p>
                <h3 class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ $metrics['active_roles'] }}</h3>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium truncate mt-0.5">{{ __('messages.roles_active_desc') }}</p>
            </div>
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-lg sm:text-xl font-bold shadow-inner shrink-0">
                ✅
            </div>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 shadow-sm flex items-center justify-between transition hover:shadow">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-0.5 truncate">{{ __('messages.roles_total_staff') }}</p>
                <h3 class="text-lg sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono tracking-tight">{{ $metrics['total_staff'] }}</h3>
                <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5">Managers & Staff</p>
            </div>
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 grid place-items-center text-lg sm:text-xl font-bold shadow-inner shrink-0">
                👥
            </div>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 shadow-sm flex items-center justify-between transition hover:shadow">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-0.5 truncate">{{ __('messages.roles_unassigned_staff') }}</p>
                <h3 class="text-lg sm:text-2xl font-black {{ $metrics['unassigned_staff'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' }} font-mono tracking-tight">{{ $metrics['unassigned_staff'] }}</h3>
                <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5">{{ $metrics['unassigned_staff'] > 0 ? 'Need Role Assignment' : 'All Staff Assigned' }}</p>
            </div>
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-lg sm:text-xl font-bold shadow-inner shrink-0">
                ⚠️
            </div>
        </div>
    </div>

    {{-- Tab Switches --}}
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800">
        <button type="button" @click="activeTab = 'roles'"
                :class="activeTab === 'roles' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 font-semibold'"
                class="py-2.5 px-3.5 text-xs sm:text-sm border-b-2 transition flex items-center gap-2">
            <span>🎭</span>
            <span>{{ __('messages.roles_tab_roles') }}</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono font-bold">{{ $metrics['total_roles'] }}</span>
        </button>

        <button type="button" @click="activeTab = 'staff'"
                :class="activeTab === 'staff' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 font-semibold'"
                class="py-2.5 px-3.5 text-xs sm:text-sm border-b-2 transition flex items-center gap-2">
            <span>👥</span>
            <span>{{ __('messages.roles_tab_staff') }}</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono font-bold">{{ $metrics['total_staff'] }}</span>
        </button>
    </div>

    {{-- SECTION 1: ROLES & PERMISSIONS MATRIX TAB --}}
    <div x-show="activeTab === 'roles'" class="space-y-3">
        
        {{-- Unified Admin Toolbar --}}
        @php
            $statusFilterOptions = [
                'active'   => __('messages.roles_status_active'),
                'inactive' => __('messages.roles_status_inactive'),
            ];
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
        <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse ($roles as $role)
                @php
                    $perms = $role->permissions ?? [];
                    $isAll = in_array('*', $perms, true);
                    $permCountLabel = $isAll ? __('messages.roles_full_access') : count($perms) . ' ' . __('messages.roles_permissions_count');
                @endphp
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-3 group relative overflow-hidden">
                    {{-- Role top color indicator bar --}}
                    <div class="absolute top-0 left-0 right-0 h-1" style="background-color: {{ $role->color }};"></div>

                    <div class="space-y-2.5 pt-0.5">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-3.5 h-3.5 rounded-full shrink-0 ring-2 ring-white dark:ring-slate-900 shadow-sm" style="background-color: {{ $role->color }};"></span>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 group-hover:text-blue-600 transition truncate">
                                        {{ $role->name }}
                                    </h3>
                                    <span class="text-[10px] font-mono text-slate-400 block truncate">{{ $role->slug }}</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold shrink-0 {{ $role->is_system ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $role->is_system ? __('messages.roles_system_default') : __('messages.roles_custom_role') }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 min-h-[32px]">
                            {{ $role->description ?: '-' }}
                        </p>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                                <span class="text-slate-400 block text-[10px] font-bold uppercase">{{ __('messages.roles_assigned_staff') }}</span>
                                <span class="font-mono font-black text-slate-800 dark:text-slate-200 text-sm">
                                    {{ $role->staff_count }}
                                </span>
                            </div>
                            <div class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                                <span class="text-slate-400 block text-[10px] font-bold uppercase">{{ __('messages.roles_permissions_count') }}</span>
                                <span class="font-mono font-black text-blue-600 dark:text-blue-400 text-xs truncate block mt-0.5">
                                    {{ $permCountLabel }}
                                </span>
                            </div>
                        </div>

                        {{-- Permission summary preview --}}
                        <div class="flex items-center gap-1 flex-wrap pt-0.5">
                            @if ($isAll)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60">
                                    ⭐ {{ __('messages.roles_full_access') }}
                                </span>
                            @else
                                @foreach (array_slice($perms, 0, 3) as $p)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $p }}
                                    </span>
                                @endforeach
                                @if (count($perms) > 3)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-400 dark:bg-slate-800">
                                        +{{ count($perms) - 3 }} more
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        <button type="button" @click.stop="openEditModalById({{ (int) $role->id }})"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition flex items-center gap-1 shadow-sm">
                            <span>✏️</span>
                            <span>{{ __('messages.roles_edit_permissions') }}</span>
                        </button>

                        @if (! $role->is_system)
                            <form action="{{ route('store.admin.roles.destroy', array_merge($storeRouteParams, ['role' => $role->id])) }}"
                                  method="POST" onsubmit="return confirm('{{ __('messages.roles_delete_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition">
                                    {{ __('messages.roles_delete_role') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-xs sm:text-sm">
                    {{ __('messages.roles_no_records') }}
                </div>
            @endforelse
        </div>

        {{-- Role Table View --}}
        <div x-show="viewMode === 'table'" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Role Name & Slug</th>
                            <th class="py-3 px-4">{{ __('messages.roles_description') }}</th>
                            <th class="py-3 px-4 text-center">Type</th>
                            <th class="py-3 px-4 text-center">{{ __('messages.roles_assigned_staff') }}</th>
                            <th class="py-3 px-4 text-center">{{ __('messages.roles_permissions_count') }}</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($roles as $role)
                            @php
                                $perms = $role->permissions ?? [];
                                $isAll = in_array('*', $perms, true);
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $role->color }};"></span>
                                        <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm">{{ $role->name }}</span>
                                    </div>
                                    <span class="text-[10px] font-mono text-slate-400 ml-5 block">{{ $role->slug }}</span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                    {{ $role->description ?: '-' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $role->is_system ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $role->is_system ? __('messages.roles_system_default') : __('messages.roles_custom_role') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ $role->staff_count }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-blue-600 dark:text-blue-400">
                                    {{ $isAll ? __('messages.roles_full_access') : count($perms) }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" @click.stop="openEditModalById({{ (int) $role->id }})"
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 transition">
                                            Edit
                                        </button>
                                        @if (! $role->is_system)
                                            <form action="{{ route('store.admin.roles.destroy', array_merge($storeRouteParams, ['role' => $role->id])) }}"
                                                  method="POST" onsubmit="return confirm('{{ __('messages.roles_delete_confirm') }}');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                                    {{ __('messages.roles_no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SECTION 2: STAFF MEMBERS DIRECTORY TAB (Enhanced with Inline & Custom Role Assignment) --}}
    <div x-show="activeTab === 'staff'" class="space-y-3">
        {{-- Search and Filter Controls for Staff --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3 shadow-sm">
            <div class="flex items-center gap-2 flex-1 flex-wrap">
                <div class="relative flex-1 min-w-[200px] max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                    <input type="text" x-model="staffSearch"
                           placeholder="{{ __('messages.roles_search_staff') }}"
                           class="w-full pl-8 pr-3 py-1.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" @click="staffFilter = 'all'"
                            :class="staffFilter === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition">
                        All
                    </button>
                    <button type="button" @click="staffFilter = 'assigned'"
                            :class="staffFilter === 'assigned' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition">
                        Assigned
                    </button>
                    <button type="button" @click="staffFilter = 'unassigned'"
                            :class="staffFilter === 'unassigned' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition">
                        Unassigned
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                <button type="button" @click="openCreateModal()"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 text-slate-700 transition flex items-center gap-1.5 shadow-sm">
                    <span>+</span>
                    <span>{{ __('messages.roles_new') }}</span>
                </button>
                <div class="text-[11px] text-slate-400 font-semibold px-2">
                    <span x-text="filteredStaff().length"></span> / {{ $staffMembers->count() }} Staff
                </div>
            </div>
        </div>

        {{-- Staff Table with Direct Role Select & Custom Modal Access --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">{{ __('messages.roles_staff_directory_title') }}</h3>
                    <p class="text-[11px] text-slate-400">Assign Standard Roles (Manager, Cashier, Accountant) or Owner-defined Custom Permissions</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">{{ __('messages.roles_staff_member') }}</th>
                            <th class="py-3 px-4">{{ __('messages.roles_staff_contact') }}</th>
                            <th class="py-3 px-4">System Type</th>
                            <th class="py-3 px-4">Current Assigned Role</th>
                            <th class="py-3 px-4">Quick Role Assignment</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($staffMembers as $staff)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                {{-- Staff Name --}}
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-bold grid place-items-center text-xs shrink-0 border border-blue-200 dark:border-blue-800">
                                            {{ strtoupper(substr($staff->user_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm block">
                                                {{ $staff->user_name }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">ID: #{{ $staff->user_id }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Contact --}}
                                <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400 text-xs">
                                    {{ $staff->user_phone ?: ($staff->user_email ?: '-') }}
                                </td>

                                {{-- High Level Role --}}
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $staff->high_level_role === 'store_manager' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $staff->high_level_role === 'store_manager' ? 'Store Manager' : 'Staff Member' }}
                                    </span>
                                </td>

                                {{-- Current Role Badge --}}
                                <td class="py-3 px-4">
                                    @if ($staff->role_name)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                              style="background-color: {{ $staff->role_color ?: '#0284c7' }}15; color: {{ $staff->role_color ?: '#0284c7' }}; border-color: {{ $staff->role_color ?: '#0284c7' }}30;">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $staff->role_color ?: '#0284c7' }};"></span>
                                            <span>{{ $staff->role_name }}</span>
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300 border border-amber-200/50 dark:border-amber-800/50">
                                            {{ __('messages.roles_staff_unassigned') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Quick Inline Role Selector Form --}}
                                <td class="py-3 px-4">
                                    <form action="{{ route('store.admin.roles.assign_staff', $storeRouteParams) }}" method="POST" class="flex items-center gap-1.5">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $staff->user_id }}">
                                        <input type="hidden" name="action_mode" value="select">
                                        
                                        <select name="staff_role_id" onchange="this.form.submit()"
                                                class="px-2.5 py-1 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer min-w-[170px]">
                                            <option value="" {{ is_null($staff->staff_role_id) ? 'selected' : '' }}>
                                                -- {{ __('messages.roles_no_specific_role') }} --
                                            </option>
                                            
                                            <optgroup label="⭐ Standard System Roles (မူလစနစ် ရာထူးများ)">
                                                @foreach ($allRolesForSelect->where('is_system', true) as $r)
                                                    <option value="{{ $r->id }}" {{ $staff->staff_role_id == $r->id ? 'selected' : '' }}>
                                                        {{ $r->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>

                                            @if ($allRolesForSelect->where('is_system', false)->count() > 0)
                                                <optgroup label="🛠️ Owner Custom Roles (ပိုင်ရှင် သတ်မှတ် ရာထူးများ)">
                                                    @foreach ($allRolesForSelect->where('is_system', false) as $r)
                                                        <option value="{{ $r->id }}" {{ $staff->staff_role_id == $r->id ? 'selected' : '' }}>
                                                            {{ $r->name }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                    </form>
                                </td>

                                {{-- Actions --}}
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click.stop="openAssignModalById({{ (int) $staff->user_id }}, 'select')"
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300 transition inline-flex items-center gap-1 shadow-xs"
                                                title="Select Standard or Custom Role">
                                            <span>🎭</span>
                                            <span>{{ __('messages.roles_staff_change_role') }}</span>
                                        </button>

                                        <button type="button" @click.stop="openAssignModalById({{ (int) $staff->user_id }}, 'create_custom')"
                                                class="px-2 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 transition inline-flex items-center gap-1 shadow-xs"
                                                title="Create a tailor-made custom role for this staff member">
                                            <span>✨</span>
                                            <span>Custom Role</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400 dark:text-slate-500 text-xs">
                                    No staff members found in this store.
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
         class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-5xl bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800 my-4 max-h-[94vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-base font-bold">✨</span>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.roles_new') }}</h3>
                        <p class="text-[11px] text-slate-400">Define role details & configure sidebar module permissions (VIEW, EDIT, DELETE)</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold">✕</button>
            </div>

            <form action="{{ route('store.admin.roles.store', $storeRouteParams) }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Role Name *</label>
                        <input type="text" name="name" x-model="newRole.name" required
                               placeholder="e.g. Senior Cashier / Assistant Manager"
                               class="w-full px-3 py-2 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.roles_badge_color') }}</label>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <template x-for="c in colorPresets" :key="c">
                                    <button type="button" @click="newRole.color = c"
                                            class="w-6 h-6 rounded-full border-2 transition transform hover:scale-110 shadow-xs"
                                            :class="newRole.color === c ? 'border-slate-900 dark:border-white scale-110' : 'border-transparent'"
                                            :style="`background-color: ${c};`">
                                    </button>
                                </template>
                            </div>
                            <input type="color" name="color" x-model="newRole.color"
                                   class="h-7 w-8 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer ml-1">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.roles_description') }}</label>
                        <textarea name="description" x-model="newRole.description" rows="2"
                                  placeholder="Role duties, access boundary, and responsibilities summary..."
                                  class="w-full px-3 py-2 rounded-xl text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                {{-- Granular Permissions Matrix Section (Row-by-Row Sidebar Modules with VIEW, EDIT, DELETE) --}}
                <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                <span>🛡️</span>
                                <span>{{ __('messages.roles_permissions_matrix') }}</span>
                            </h4>
                            <p class="text-[11px] text-slate-400">Sidebar modules with separate VIEW, EDIT, DELETE permissions</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" @click="selectAll(newRole)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 hover:bg-blue-100 transition">{{ __('messages.roles_select_all') }}</button>
                            <button type="button" @click="deselectAll(newRole)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 transition">{{ __('messages.roles_clear_all') }}</button>
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-black bg-blue-600 text-white font-mono" x-text="`${newRole.permissions.length} / ${allAvailablePermissions.length} selected`"></span>
                        </div>
                    </div>

                    {{-- Search inside permissions --}}
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                        <input type="text" x-model="permSearch"
                               placeholder="{{ __('messages.roles_perm_search') }}"
                               class="w-full pl-8 pr-3 py-1.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Grouped Permissions Cards --}}
                    <div class="space-y-3.5">
                        @foreach ($permissionGroups as $groupKey => $group)
                            <div class="p-3 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-2.5 transition">
                                {{-- Group Header --}}
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ $group['icon'] ?? '📁' }}</span>
                                        <div>
                                            <span class="text-xs font-black text-slate-900 dark:text-slate-100 block">{{ $group['label'] }}</span>
                                            <span class="text-[10px] font-mono text-slate-400" x-text="`${groupCount(newRole, '{{ $groupKey }}')} of {{ count($group['modules']) * 3 }} actions selected`"></span>
                                        </div>
                                    </div>

                                    {{-- Group-level Quick Action Toggles --}}
                                    <div class="flex items-center gap-1.5 self-end sm:self-auto flex-wrap">
                                        <button type="button" @click="toggleGroupColumn(newRole, '{{ $groupKey }}', 'view')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800 hover:bg-sky-100 transition">
                                            👁️ All VIEW
                                        </button>
                                        <button type="button" @click="toggleGroupColumn(newRole, '{{ $groupKey }}', 'edit')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800 hover:bg-emerald-100 transition">
                                            ✏️ All EDIT
                                        </button>
                                        <button type="button" @click="toggleGroupColumn(newRole, '{{ $groupKey }}', 'delete')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800 hover:bg-rose-100 transition">
                                            🗑️ All DELETE
                                        </button>
                                        <button type="button" @click="toggleGroupAll(newRole, '{{ $groupKey }}')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 hover:bg-slate-300 transition">
                                            🔄 Group All
                                        </button>
                                    </div>
                                </div>

                                {{-- Module Rows with Individual VIEW, EDIT, DELETE Buttons on the Right --}}
                                <div class="space-y-1.5">
                                    @foreach ($group['modules'] as $moduleKey => $module)
                                        @php
                                            $pView = $module['permissions']['view'] ?? null;
                                            $pEdit = $module['permissions']['edit'] ?? null;
                                            $pDelete = $module['permissions']['delete'] ?? null;
                                            $searchString = strtolower($module['name'] . ' ' . $module['desc'] . ' ' . $pView . ' ' . $pEdit . ' ' . $pDelete);
                                        @endphp
                                        <div x-show="!permSearch || '{{ $searchString }}'.includes(permSearch.toLowerCase())"
                                             class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2 sm:p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-700 transition">
                                            
                                            {{-- Module Left Side: Row Checkbox + Module Name + Description --}}
                                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                <button type="button" @click="toggleModuleRow(newRole, '{{ $pView }}', '{{ $pEdit }}', '{{ $pDelete }}')"
                                                        title="Toggle all actions for this module"
                                                        class="w-5 h-5 rounded-md border flex items-center justify-center text-[10px] font-bold transition shrink-0"
                                                        :class="isModuleFull(newRole, '{{ $pView }}', '{{ $pEdit }}', '{{ $pDelete }}') 
                                                            ? 'bg-blue-600 border-blue-600 text-white' 
                                                            : (hasPerm(newRole, '{{ $pView }}') || hasPerm(newRole, '{{ $pEdit }}') || hasPerm(newRole, '{{ $pDelete }}') 
                                                                ? 'bg-blue-100 dark:bg-blue-950 border-blue-400 text-blue-700 dark:text-blue-300' 
                                                                : 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-transparent')">
                                                    ✓
                                                </button>
                                                <div class="min-w-0">
                                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200 block truncate">
                                                        {{ $module['name'] }}
                                                    </span>
                                                    <span class="text-[10px] text-slate-400 block truncate">
                                                        {{ $module['desc'] }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Module Right Side: VIEW, EDIT, DELETE Action Buttons --}}
                                            <div class="flex items-center gap-1.5 self-end sm:self-auto shrink-0">
                                                {{-- VIEW BUTTON --}}
                                                @if ($pView)
                                                    <button type="button" @click="togglePerm(newRole, '{{ $pView }}')"
                                                            :class="hasPerm(newRole, '{{ $pView }}') 
                                                                ? 'bg-sky-600 text-white border-sky-600 shadow-xs' 
                                                                : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-sky-600 dark:hover:text-sky-400'"
                                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black tracking-wider uppercase border transition flex items-center gap-1 active:scale-95">
                                                        <span :class="hasPerm(newRole, '{{ $pView }}') ? 'opacity-100' : 'opacity-40'">👁️</span>
                                                        <span>VIEW</span>
                                                    </button>
                                                @endif

                                                {{-- EDIT BUTTON --}}
                                                @if ($pEdit)
                                                    <button type="button" @click="togglePerm(newRole, '{{ $pEdit }}')"
                                                            :class="hasPerm(newRole, '{{ $pEdit }}') 
                                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' 
                                                                : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-emerald-600 dark:hover:text-emerald-400'"
                                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black tracking-wider uppercase border transition flex items-center gap-1 active:scale-95">
                                                        <span :class="hasPerm(newRole, '{{ $pEdit }}') ? 'opacity-100' : 'opacity-40'">✏️</span>
                                                        <span>EDIT</span>
                                                    </button>
                                                @endif

                                                {{-- DELETE BUTTON --}}
                                                @if ($pDelete)
                                                    <button type="button" @click="togglePerm(newRole, '{{ $pDelete }}')"
                                                            :class="hasPerm(newRole, '{{ $pDelete }}') 
                                                                ? 'bg-rose-600 text-white border-rose-600 shadow-xs' 
                                                                : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-rose-600 dark:hover:text-rose-400'"
                                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black tracking-wider uppercase border transition flex items-center gap-1 active:scale-95">
                                                        <span :class="hasPerm(newRole, '{{ $pDelete }}') ? 'opacity-100' : 'opacity-40'">🗑️</span>
                                                        <span>DELETE</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="createModalOpen = false"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-sm shadow-blue-500/20 transition">
                        {{ __('messages.roles_new') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: EDIT ROLE MODAL --}}
    <div x-show="editModalOpen" x-cloak
         @click.self="editModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-5xl bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800 my-4 max-h-[94vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-base font-bold">✏️</span>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.roles_edit') }}</h3>
                        <p class="text-[11px] text-slate-400">Modify role name, badge color, and sidebar module permissions (VIEW, EDIT, DELETE)</p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold">✕</button>
            </div>

            <form :action="'{{ url('/store/' . $store->slug . '/admin/security/roles') }}/' + editingRole.id"
                  method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Role Name *</label>
                        <input type="text" name="name" x-model="editingRole.name" required
                               placeholder="e.g. Senior Cashier / Assistant Manager"
                               class="w-full px-3 py-2 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.roles_badge_color') }}</label>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <template x-for="c in colorPresets" :key="c">
                                    <button type="button" @click="editingRole.color = c"
                                            class="w-6 h-6 rounded-full border-2 transition transform hover:scale-110 shadow-xs"
                                            :class="editingRole.color === c ? 'border-slate-900 dark:border-white scale-110' : 'border-transparent'"
                                            :style="`background-color: ${c};`">
                                    </button>
                                </template>
                            </div>
                            <input type="color" name="color" x-model="editingRole.color"
                                   class="h-7 w-8 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer ml-1">
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.roles_description') }}</label>
                        <textarea name="description" x-model="editingRole.description" rows="2"
                                  placeholder="Role duties, access boundary, and responsibilities summary..."
                                  class="w-full px-3 py-2 rounded-xl text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="editingRole.is_active"
                                   class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.roles_active_desc') }}</span>
                        </label>
                    </div>
                </div>

                {{-- Granular Permissions Matrix Section (Row-by-Row Sidebar Modules with VIEW, EDIT, DELETE) --}}
                <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                <span>🛡️</span>
                                <span>{{ __('messages.roles_permissions_matrix') }}</span>
                            </h4>
                            <p class="text-[11px] text-slate-400">Sidebar modules with separate VIEW, EDIT, DELETE permissions</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" @click="selectAll(editingRole)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 hover:bg-blue-100 transition">{{ __('messages.roles_select_all') }}</button>
                            <button type="button" @click="deselectAll(editingRole)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 transition">{{ __('messages.roles_clear_all') }}</button>
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-black bg-blue-600 text-white font-mono" x-text="`${editingRole.permissions.length} / ${allAvailablePermissions.length} selected`"></span>
                        </div>
                    </div>

                    {{-- Search inside permissions --}}
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                        <input type="text" x-model="permSearch"
                               placeholder="{{ __('messages.roles_perm_search') }}"
                               class="w-full pl-8 pr-3 py-1.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Grouped Permissions Cards --}}
                    <div class="space-y-3.5">
                        @foreach ($permissionGroups as $groupKey => $group)
                            <div class="p-3 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-2.5 transition">
                                {{-- Group Header --}}
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 dark:border-slate-700/60 pb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ $group['icon'] ?? '📁' }}</span>
                                        <div>
                                            <span class="text-xs font-black text-slate-900 dark:text-slate-100 block">{{ $group['label'] }}</span>
                                            <span class="text-[10px] font-mono text-slate-400" x-text="`${groupCount(editingRole, '{{ $groupKey }}')} of {{ count($group['modules']) * 3 }} actions selected`"></span>
                                        </div>
                                    </div>

                                    {{-- Group-level Quick Action Toggles --}}
                                    <div class="flex items-center gap-1.5 self-end sm:self-auto flex-wrap">
                                        <button type="button" @click="toggleGroupColumn(editingRole, '{{ $groupKey }}', 'view')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800 hover:bg-sky-100 transition">
                                            👁️ All VIEW
                                        </button>
                                        <button type="button" @click="toggleGroupColumn(editingRole, '{{ $groupKey }}', 'edit')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800 hover:bg-emerald-100 transition">
                                            ✏️ All EDIT
                                        </button>
                                        <button type="button" @click="toggleGroupColumn(editingRole, '{{ $groupKey }}', 'delete')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800 hover:bg-rose-100 transition">
                                            🗑️ All DELETE
                                        </button>
                                        <button type="button" @click="toggleGroupAll(editingRole, '{{ $groupKey }}')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 hover:bg-slate-300 transition">
                                            🔄 Group All
                                        </button>
                                    </div>
                                </div>

                                {{-- Module Rows with Individual VIEW, EDIT, DELETE Buttons on the Right --}}
                                <div class="space-y-1.5">
                                    @foreach ($group['modules'] as $moduleKey => $module)
                                        @php
                                            $pView = $module['permissions']['view'] ?? null;
                                            $pEdit = $module['permissions']['edit'] ?? null;
                                            $pDelete = $module['permissions']['delete'] ?? null;
                                            $searchString = strtolower($module['name'] . ' ' . $module['desc'] . ' ' . $pView . ' ' . $pEdit . ' ' . $pDelete);
                                        @endphp
                                        <div x-show="!permSearch || '{{ $searchString }}'.includes(permSearch.toLowerCase())"
                                             class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2 sm:p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 hover:border-blue-300 dark:hover:border-blue-700 transition">
                                            
                                            {{-- Module Left Side: Row Checkbox + Module Name + Description --}}
                                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                <button type="button" @click="toggleModuleRow(editingRole, '{{ $pView }}', '{{ $pEdit }}', '{{ $pDelete }}')"
                                                        title="Toggle all actions for this module"
                                                        class="w-5 h-5 rounded-md border flex items-center justify-center text-[10px] font-bold transition shrink-0"
                                                        :class="isModuleFull(editingRole, '{{ $pView }}', '{{ $pEdit }}', '{{ $pDelete }}') 
                                                            ? 'bg-blue-600 border-blue-600 text-white' 
                                                            : (hasPerm(editingRole, '{{ $pView }}') || hasPerm(editingRole, '{{ $pEdit }}') || hasPerm(editingRole, '{{ $pDelete }}') 
                                                                ? 'bg-blue-100 dark:bg-blue-950 border-blue-400 text-blue-700 dark:text-blue-300' 
                                                                : 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-transparent')">
                                                    ✓
                                                </button>
                                                <div class="min-w-0">
                                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200 block truncate">
                                                        {{ $module['name'] }}
                                                    </span>
                                                    <span class="text-[10px] text-slate-400 block truncate">
                                                        {{ $module['desc'] }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Module Right Side: VIEW, EDIT, DELETE Action Buttons --}}
                                            <div class="flex items-center gap-1.5 self-end sm:self-auto shrink-0">
                                                {{-- VIEW BUTTON --}}
                                                @if ($pView)
                                                    <button type="button" @click="togglePerm(editingRole, '{{ $pView }}')"
                                                            :class="hasPerm(editingRole, '{{ $pView }}') 
                                                                ? 'bg-sky-600 text-white border-sky-600 shadow-xs' 
                                                                : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-sky-600 dark:hover:text-sky-400'"
                                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black tracking-wider uppercase border transition flex items-center gap-1 active:scale-95">
                                                        <span :class="hasPerm(editingRole, '{{ $pView }}') ? 'opacity-100' : 'opacity-40'">👁️</span>
                                                        <span>VIEW</span>
                                                    </button>
                                                @endif

                                                {{-- EDIT BUTTON --}}
                                                @if ($pEdit)
                                                    <button type="button" @click="togglePerm(editingRole, '{{ $pEdit }}')"
                                                            :class="hasPerm(editingRole, '{{ $pEdit }}') 
                                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' 
                                                                : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-emerald-600 dark:hover:text-emerald-400'"
                                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black tracking-wider uppercase border transition flex items-center gap-1 active:scale-95">
                                                        <span :class="hasPerm(editingRole, '{{ $pEdit }}') ? 'opacity-100' : 'opacity-40'">✏️</span>
                                                        <span>EDIT</span>
                                                    </button>
                                                @endif

                                                {{-- DELETE BUTTON --}}
                                                @if ($pDelete)
                                                    <button type="button" @click="togglePerm(editingRole, '{{ $pDelete }}')"
                                                            :class="hasPerm(editingRole, '{{ $pDelete }}') 
                                                                ? 'bg-rose-600 text-white border-rose-600 shadow-xs' 
                                                                : 'bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700 hover:text-rose-600 dark:hover:text-rose-400'"
                                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black tracking-wider uppercase border transition flex items-center gap-1 active:scale-95">
                                                        <span :class="hasPerm(editingRole, '{{ $pDelete }}') ? 'opacity-100' : 'opacity-40'">🗑️</span>
                                                        <span>DELETE</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="editModalOpen = false"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-sm shadow-blue-500/20 transition">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: ASSIGN STAFF ROLE & CUSTOM PERMISSIONS MODAL (Dual Mode: Standard/Custom Role Picker OR Custom Direct Matrix) --}}
    <div x-show="assignModalOpen" x-cloak
         @click.self="assignModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="w-full max-w-4xl bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-2xl space-y-4 border border-slate-200 dark:border-slate-800 my-4 max-h-[94vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-base font-bold">🎭</span>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('messages.roles_assign_modal_title') }}</h3>
                        <p class="text-[11px] text-slate-400">Assign Standard System Role OR create a customized role tailored for this employee</p>
                    </div>
                </div>
                <button type="button" @click="assignModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
            </div>

            {{-- Staff Profile Summary Box --}}
            <div class="flex items-center justify-between gap-3 p-3 rounded-2xl bg-blue-50/60 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold grid place-items-center text-sm shadow-xs"
                         x-text="assignData.user_name ? assignData.user_name.charAt(0).toUpperCase() : 'U'">
                    </div>
                    <div>
                        <h4 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white" x-text="assignData.user_name"></h4>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono" x-text="assignData.user_phone || 'Staff Member'"></p>
                    </div>
                </div>

                {{-- Mode Switch Radio Tabs --}}
                <div class="flex items-center gap-1 p-1 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                    <button type="button" @click="assignData.mode = 'select'"
                            :class="assignData.mode === 'select' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                            class="px-2.5 py-1 rounded-lg text-xs transition flex items-center gap-1">
                        <span>⭐</span>
                        <span>Existing Roles</span>
                    </button>
                    <button type="button" @click="assignData.mode = 'create_custom'"
                            :class="assignData.mode === 'create_custom' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 font-semibold'"
                            class="px-2.5 py-1 rounded-lg text-xs transition flex items-center gap-1">
                        <span>✨</span>
                        <span>Tailor-made Role</span>
                    </button>
                </div>
            </div>

            <form action="{{ route('store.admin.roles.assign_staff', $storeRouteParams) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="user_id" :value="assignData.user_id">
                <input type="hidden" name="action_mode" :value="assignData.mode">

                {{-- MODE 1: SELECT EXISTING STANDARD OR CUSTOM ROLE --}}
                <div x-show="assignData.mode === 'select'" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            {{ __('messages.roles_select_role_template') }} *
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[360px] overflow-y-auto p-1">
                            {{-- Option: None --}}
                            <label class="p-3 rounded-xl border cursor-pointer transition flex items-center justify-between"
                                   :class="assignData.staff_role_id === '' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 ring-1 ring-blue-500' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                                <div class="flex items-center gap-2.5">
                                    <input type="radio" name="staff_role_id" value="" x-model="assignData.staff_role_id" class="text-blue-600">
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">-- {{ __('messages.roles_no_specific_role') }} --</span>
                                        <span class="text-[10px] text-slate-400">Default general staff privileges</span>
                                    </div>
                                </div>
                            </label>

                            @foreach ($allRolesForSelect as $r)
                                <label class="p-3 rounded-xl border cursor-pointer transition flex items-center justify-between group"
                                       :class="assignData.staff_role_id == {{ $r->id }} ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/40 ring-1 ring-blue-500' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <input type="radio" name="staff_role_id" value="{{ $r->id }}" x-model="assignData.staff_role_id" class="text-blue-600 shrink-0">
                                        <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $r->color }};"></span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate block">{{ $r->name }}</span>
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold {{ $r->is_system ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800' }}">
                                                    {{ $r->is_system ? 'Standard' : 'Custom' }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 line-clamp-1 block">{{ $r->description ?: ($r->is_system ? 'System default role template' : 'Owner custom role') }}</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- MODE 2: CREATE & ASSIGN CUSTOM ROLE ON THE FLY --}}
                <div x-show="assignData.mode === 'create_custom'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Custom Role Name *</label>
                            <input type="text" name="role_name" x-model="assignData.custom_role_name"
                                   placeholder="e.g. Senior Cashier & Stock Assistant"
                                   class="w-full px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Badge Color</label>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1">
                                    <template x-for="c in colorPresets" :key="c">
                                        <button type="button" @click="assignData.custom_role_color = c"
                                                class="w-6 h-6 rounded-full border-2 transition transform hover:scale-110 shadow-xs"
                                                :class="assignData.custom_role_color === c ? 'border-slate-900 dark:border-white scale-110' : 'border-transparent'"
                                                :style="`background-color: ${c};`">
                                        </button>
                                    </template>
                                </div>
                                <input type="color" name="role_color" x-model="assignData.custom_role_color"
                                       class="h-7 w-8 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer ml-1">
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Role Description</label>
                            <input type="text" name="role_description" x-model="assignData.custom_role_desc"
                                   placeholder="Duties, assigned counters, and access scope..."
                                   class="w-full px-3 py-2 rounded-xl text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    {{-- Dedicated VIEW, EDIT, DELETE matrix for this custom role --}}
                    <div class="space-y-3 pt-2">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🛡️</span>
                                    <span>Configure Individual Permissions (VIEW, EDIT, DELETE)</span>
                                </h4>
                                <p class="text-[11px] text-slate-400">Toggle permissions granted specifically to this staff member's role</p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="button" @click="selectAll(assignData)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 hover:bg-blue-100 transition">{{ __('messages.roles_select_all') }}</button>
                                <button type="button" @click="deselectAll(assignData)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 transition">{{ __('messages.roles_clear_all') }}</button>
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-black bg-blue-600 text-white font-mono" x-text="`${assignData.permissions.length} / ${allAvailablePermissions.length} selected`"></span>
                            </div>
                        </div>

                        {{-- Search inside permissions --}}
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                            <input type="text" x-model="permSearch"
                                   placeholder="{{ __('messages.roles_perm_search') }}"
                                   class="w-full pl-8 pr-3 py-1.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        {{-- Permissions Accordion Groups --}}
                        <div class="space-y-3 max-h-[380px] overflow-y-auto p-1">
                            @foreach ($permissionGroups as $groupKey => $group)
                                <div class="p-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-2">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200/60 dark:border-slate-700/60 pb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">{{ $group['icon'] ?? '📁' }}</span>
                                            <div>
                                                <span class="text-xs font-black text-slate-900 dark:text-slate-100 block">{{ $group['label'] }}</span>
                                                <span class="text-[10px] font-mono text-slate-400" x-text="`${groupCount(assignData, '{{ $groupKey }}')} of {{ count($group['modules']) * 3 }} selected`"></span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1 self-end sm:self-auto flex-wrap">
                                            <button type="button" @click="toggleGroupColumn(assignData, '{{ $groupKey }}', 'view')"
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border border-sky-200/60 hover:bg-sky-100 transition">
                                                👁️ VIEW
                                            </button>
                                            <button type="button" @click="toggleGroupColumn(assignData, '{{ $groupKey }}', 'edit')"
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200/60 hover:bg-emerald-100 transition">
                                                ✏️ EDIT
                                            </button>
                                            <button type="button" @click="toggleGroupColumn(assignData, '{{ $groupKey }}', 'delete')"
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200/60 hover:bg-rose-100 transition">
                                                🗑️ DELETE
                                            </button>
                                            <button type="button" @click="toggleGroupAll(assignData, '{{ $groupKey }}')"
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 hover:bg-slate-300 transition">
                                                🔄 ALL
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        @foreach ($group['modules'] as $moduleKey => $module)
                                            @php
                                                $pView = $module['permissions']['view'] ?? null;
                                                $pEdit = $module['permissions']['edit'] ?? null;
                                                $pDelete = $module['permissions']['delete'] ?? null;
                                                $searchString = strtolower($module['name'] . ' ' . $module['desc'] . ' ' . $pView . ' ' . $pEdit . ' ' . $pDelete);
                                            @endphp
                                            <div x-show="!permSearch || '{{ $searchString }}'.includes(permSearch.toLowerCase())"
                                                 class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 transition">
                                                
                                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                                    <button type="button" @click="toggleModuleRow(assignData, '{{ $pView }}', '{{ $pEdit }}', '{{ $pDelete }}')"
                                                            class="w-4 h-4 rounded border flex items-center justify-center text-[9px] font-bold transition shrink-0"
                                                            :class="isModuleFull(assignData, '{{ $pView }}', '{{ $pEdit }}', '{{ $pDelete }}') 
                                                                ? 'bg-blue-600 border-blue-600 text-white' 
                                                                : (hasPerm(assignData, '{{ $pView }}') || hasPerm(assignData, '{{ $pEdit }}') || hasPerm(assignData, '{{ $pDelete }}') 
                                                                    ? 'bg-blue-100 dark:bg-blue-950 border-blue-400 text-blue-700 dark:text-blue-300' 
                                                                    : 'bg-slate-100 dark:bg-slate-800 border-slate-300 text-transparent')">
                                                        ✓
                                                    </button>
                                                    <span class="font-bold text-xs text-slate-800 dark:text-slate-200 truncate">
                                                        {{ $module['name'] }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center gap-1 shrink-0 self-end sm:self-auto">
                                                    @if ($pView)
                                                        <label class="cursor-pointer">
                                                            <input type="checkbox" name="role_permissions[]" value="{{ $pView }}"
                                                                   x-model="assignData.permissions" class="sr-only">
                                                            <span :class="hasPerm(assignData, '{{ $pView }}') ? 'bg-sky-600 text-white border-sky-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'"
                                                                  class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase border transition inline-flex items-center gap-0.5">
                                                                <span>👁️</span><span>VIEW</span>
                                                            </span>
                                                        </label>
                                                    @endif

                                                    @if ($pEdit)
                                                        <label class="cursor-pointer">
                                                            <input type="checkbox" name="role_permissions[]" value="{{ $pEdit }}"
                                                                   x-model="assignData.permissions" class="sr-only">
                                                            <span :class="hasPerm(assignData, '{{ $pEdit }}') ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'"
                                                                  class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase border transition inline-flex items-center gap-0.5">
                                                                <span>✏️</span><span>EDIT</span>
                                                            </span>
                                                        </label>
                                                    @endif

                                                    @if ($pDelete)
                                                        <label class="cursor-pointer">
                                                            <input type="checkbox" name="role_permissions[]" value="{{ $pDelete }}"
                                                                   x-model="assignData.permissions" class="sr-only">
                                                            <span :class="hasPerm(assignData, '{{ $pDelete }}') ? 'bg-rose-600 text-white border-rose-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'"
                                                                  class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase border transition inline-flex items-center gap-0.5">
                                                                <span>🗑️</span><span>DELETE</span>
                                                            </span>
                                                        </label>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="assignModalOpen = false" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-black bg-blue-600 hover:bg-blue-500 text-white shadow-sm shadow-blue-500/20 transition">
                        <span x-text="assignData.mode === 'create_custom' ? 'Create & Assign Custom Role' : '{{ __('messages.roles_confirm_assignment') }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
