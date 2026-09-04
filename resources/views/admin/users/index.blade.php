@extends('layouts.admin.app')

@section('title', __('messages.users_staff_title') . ' - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        createOpen: {{ $errors->any() ? 'true' : 'false' }},
        selectedRole: '{{ old('role', 'staff') }}',
        isStaffRole(r) {
            return r === 'staff' || r === 'store_manager' || r === 'store_owner';
        }
     }">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-sm sm:text-base font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                <span>👨‍💼 {{ __('messages.users_staff_title') }}</span>
            </h1>
            <span class="text-xs text-slate-400 hidden md:inline">· {{ __('messages.users_staff_subtitle') }}</span>
        </div>

        {{-- Top Right Action (Customer Directory, Role Templates, Create Staff) --}}
        <div class="flex items-center flex-wrap gap-2.5 self-start sm:self-auto">
            <a href="{{ route('store.admin.customers.index', $storeRouteParams) }}"
               class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 dark:hover:bg-emerald-900/60 transition flex items-center gap-2 border border-emerald-200/80 dark:border-emerald-800/80 shadow-sm"
               title="{{ __('messages.users_customer_directory_link') }}">
                <span>🛍️</span>
                <span>{{ __('messages.users_customer_directory_link') }} →</span>
            </a>
            <a href="{{ route('store.admin.roles.index', $storeRouteParams) }}"
               class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition flex items-center gap-2">
                <span>🛡️</span>
                <span>{{ __('messages.sidebar_roles') }}</span>
            </a>
            <button type="button" @click.stop="createOpen = !createOpen"
                    class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-500/20 transition flex items-center gap-2 active:scale-95">
                <span class="text-base leading-none" x-text="createOpen ? '✕' : '+'">+</span>
                <span>{{ __('messages.users_enroll_staff') }}</span>
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
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>အချက်အလက် ဖြည့်သွင်းမှု မှားယွင်းနေပါသည်:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-5">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 2. 4 Key Staff KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <a href="{{ route('store.admin.users.index', array_merge($storeRouteParams, ['tab' => 'all'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $currentTab === 'all' ? 'border-violet-500 ring-2 ring-violet-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.users_total_staff') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ $metrics['total_staff'] }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">All Store Employees</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                👨‍💼
            </span>
        </a>

        <a href="{{ route('store.admin.users.index', array_merge($storeRouteParams, ['tab' => 'active'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $currentTab === 'active' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.users_active_staff') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ $metrics['active_staff'] }}</h3>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">Active & Operating</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🟢
            </span>
        </a>

        <a href="{{ route('store.admin.users.index', array_merge($storeRouteParams, ['tab' => 'leadership'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $currentTab === 'leadership' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.users_leadership') }}</p>
                <h3 class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight">{{ $metrics['leadership_count'] }}</h3>
                <p class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold mt-0.5">Owners & Managers</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🛡️
            </span>
        </a>

        <a href="{{ route('store.admin.users.index', array_merge($storeRouteParams, ['tab' => 'suspended'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $currentTab === 'suspended' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 sm:p-5 shadow-sm flex items-center justify-between transition hover:shadow-md">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-1 truncate">{{ __('messages.users_suspended_staff') }}</p>
                <h3 class="text-xl sm:text-2xl font-black {{ $metrics['suspended_staff'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} font-mono tracking-tight">{{ $metrics['suspended_staff'] }}</h3>
                <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Access disabled</p>
            </div>
            <span class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 grid place-items-center text-xl font-bold shadow-inner flex-shrink-0">
                🚫
            </span>
        </a>
    </div>

    {{-- 3. Collapsible Create New User / Staff Form --}}
    <div x-show="createOpen" x-transition x-cloak
         class="rounded-3xl bg-white dark:bg-slate-900 border border-violet-200 dark:border-violet-900/50 p-5 sm:p-7 shadow-xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-lg">✨</span>
                <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">{{ __('messages.users_enroll_staff') }} (Enroll Store Staff)</h3>
                    <p class="text-xs text-slate-400">ဆိုင်ဝန်ထမ်း (Manager, Cashier, Service Technician, etc.) အကောင့်သစ် ဖွင့်လှစ်ပြီး ရာထူးနှင့် အခွင့်အရေးများ သတ်မှတ်ပါ</p>
                </div>
            </div>
            <button type="button" @click="createOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-2xl text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span>💡</span>
                <span>ဖောက်သည် (Customer) များ စာရင်းသွင်းရန်နှင့် စီမံခန့်ခွဲရန် ဖောက်သည်စာမျက်နှာကို အသုံးပြုပါ။</span>
            </div>
            <a href="{{ route('store.admin.customers.index', $storeRouteParams) }}" class="underline font-black text-emerald-700 dark:text-emerald-400 shrink-0">
                {{ __('messages.users_customer_directory_link') }} →
            </a>
        </div>

        <form method="POST" action="{{ route('store.admin.users.store', $storeRouteParams) }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အမည် (Full Name) *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="e.g. Ko Aung"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ဖုန်းနံပါတ် (Phone) *</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required
                           placeholder="09xxxxxxxxx"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အီးမေးလ် (Email - Optional)</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="optional@example.com"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အကောင့်အဆင့် (System Role) *</label>
                    <select name="role" x-model="selectedRole" required
                            class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" {{ old('role', 'staff') === $role ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $role)) }} {{ $role === 'platform_owner' ? '(Global Owner)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Granular Staff Role Template dropdown (Shown dynamically when role is staff or store_manager) --}}
                <div x-show="isStaffRole(selectedRole)" x-transition>
                    <label class="block text-xs font-bold text-blue-700 dark:text-blue-300 mb-1">
                        <span>🛡️ ဝန်ထမ်းရာထူးပုံစံ (Staff Role Template)</span>
                    </label>
                    <select name="staff_role_id"
                            class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold bg-blue-50/50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- No Specific Role (Default) --</option>
                        @foreach ($staffRoles as $sr)
                            <option value="{{ $sr->id }}" {{ (string) old('staff_role_id') === (string) $sr->id ? 'selected' : '' }}>
                                {{ $sr->name }} ({{ $sr->is_system ? 'System' : 'Custom' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အခြေအနေ (Store Status) *</label>
                    <select name="status"
                            class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ old('status', 'active') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">လျှို့ဝှက်နံပါတ် (Password) *</label>
                    <input type="password" name="password" required autocomplete="new-password"
                           placeholder="At least 6 characters"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အတည်ပြုနံပါတ် (Confirm Password) *</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                           placeholder="Re-enter password"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>

                <div x-show="isStaffRole(selectedRole)" x-transition>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        POS Manager PIN <span class="text-slate-400 font-normal">(4-6 Digits)</span>
                    </label>
                    <input type="password" name="pos_pin" inputmode="numeric" maxlength="6" autocomplete="new-password"
                           placeholder="e.g. 1234"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="createOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-md shadow-violet-500/20 transition">
                    အကောင့် အတည်ပြုဖွင့်လှစ်မည်
                </button>
            </div>
        </form>
    </div>

    {{-- 4. Unified Toolbar --}}
    @php
        $roleFilterOptions = [];
        foreach ($roles as $r) {
            $roleFilterOptions[$r] = ucwords(str_replace('_', ' ', $r));
        }

        $staffRoleFilterOptions = [];
        foreach ($staffRoles as $sr) {
            $staffRoleFilterOptions[$sr->id] = $sr->name;
        }

        $statusFilterOptions = [
            'active'    => 'Active',
            'pending'   => 'Pending',
            'suspended' => 'Suspended',
        ];
    @endphp

    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="အမည်၊ ဖုန်းနံပါတ်၊ အီးမေးလ်ဖြင့် ရှာဖွေပါ..."
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => 'Newest First',
            'oldest' => 'Oldest First',
        ]"
        :filters="[
            'role' => [
                'label' => 'System Role',
                'options' => $roleFilterOptions,
            ],
            'staff_role_id' => [
                'label' => 'Staff Role Template',
                'options' => $staffRoleFilterOptions,
            ],
            'status' => [
                'label' => 'Status',
                'options' => $statusFilterOptions,
            ],
        ]"
        :showExportImport="false"
        :totalCount="$users->total()"
        :perPage="$users->perPage()"
        :paginator="$users"
        :showPagination="true"
    />

    {{-- 5. Users List Table --}}
    <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">အမည်နှင့် အဆက်အသွယ် (Staff Info)</th>
                        <th class="py-3.5 px-4">စနစ်အဆင့် (System Role)</th>
                        <th class="py-3.5 px-4">ရာထူးအခွင့်အရေးပုံစံ (Assigned Role)</th>
                        <th class="py-3.5 px-4 text-center">အခြေအနေ (Status)</th>
                        <th class="py-3.5 px-4 text-right">လုပ်ဆောင်ချက် (Actions)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($users as $user)
                        @php
                            $membership = $user->stores->first()?->pivot;
                            $storeRole = $membership?->role ?? ($user->role === 'platform_owner' ? 'platform_owner' : 'staff');
                            $staffRoleId = $membership?->staff_role_id;
                            $assignedStaffRole = $staffRoleId ? ($allStaffRolesMap[$staffRoleId] ?? null) : null;
                            $status = $membership?->status ?? ($user->role === 'platform_owner' ? 'active' : 'active');
                            $initial = mb_substr($user->name ?: 'U', 0, 1);
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-500 text-white font-black text-xs grid place-items-center shadow-sm flex-shrink-0">
                                        {{ $initial }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 text-sm flex items-center gap-2 truncate">
                                            <span>{{ $user->name }}</span>
                                            @if ($user->id === auth()->id())
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300">YOU</span>
                                            @endif
                                        </div>
                                        <div class="font-mono text-[11px] text-slate-400 flex items-center gap-2">
                                            <span>📞 {{ $user->phone }}</span>
                                            @if ($user->email)
                                                <span>· ✉️ {{ $user->email }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold capitalize
                                    {{ $storeRole === 'platform_owner' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300' : '' }}
                                    {{ $storeRole === 'store_owner' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                                    {{ $storeRole === 'store_manager' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : '' }}
                                    {{ $storeRole === 'staff' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : '' }}">
                                    {{ str_replace('_', ' ', $storeRole) }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                @if (in_array($storeRole, ['store_owner', 'store_manager', 'staff'], true))
                                    @if ($assignedStaffRole)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border"
                                              style="background-color: {{ $assignedStaffRole->color }}15; color: {{ $assignedStaffRole->color }}; border-color: {{ $assignedStaffRole->color }}30;">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $assignedStaffRole->color }};"></span>
                                            <span>{{ $assignedStaffRole->name }}</span>
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                            Default Permissions
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase
                                    {{ $status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                    {{ $status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                                    {{ $status === 'suspended' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}">
                                    {{ $status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('store.admin.users.edit', array_merge($storeRouteParams, ['user' => $user->id])) }}"
                                       class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 transition">
                                        Edit
                                    </a>

                                    @if (! $user->isPlatformOwner() && auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('store.admin.users.suspend', array_merge($storeRouteParams, ['user' => $user->id])) }}"
                                              onsubmit="return confirm('{{ $status === 'suspended' ? 'အကောင့်အား ပြန်လည် အသုံးပြုခွင့် ပေးမှာ သေချာပါသလား?' : 'အသုံးပြုသူ အကောင့်အား ယာယီ ပိတ်ပင်မှာ သေချာပါသလား?' }}');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition
                                                    {{ $status === 'suspended' ? 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/50' : 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/50' }}">
                                                {{ $status === 'suspended' ? 'Activate' : 'Suspend' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('store.admin.users.destroy', array_merge($storeRouteParams, ['user' => $user->id])) }}"
                                              onsubmit="return confirm('အသုံးပြုသူ {{ $user->name }} ({{ $user->phone }}) အား ဤဆိုင်စာရင်းမှ ဖျက်/ဖယ်ရှားမှာ သေချာပါသလား?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                {{ __('messages.users_no_staff_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>

</div>
@endsection
