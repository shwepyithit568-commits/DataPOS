@extends('layouts.admin.app')

@section('title', 'Edit User - ' . ($managedUser->name ?? 'DataPOS'))

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $currentRole = $managedUser->role === 'platform_owner' ? 'platform_owner' : ($membership?->role ?? 'retail_customer');
    $currentStaffRoleId = (string) old('staff_role_id', $membership?->staff_role_id ?? '');
@endphp

@section('content')
<div class="w-full max-w-4xl mx-auto space-y-5 sm:space-y-6 pb-12"
     x-data="{
        selectedRole: '{{ old('role', $currentRole) }}',
        isStaffRole(r) {
            return r === 'staff' || r === 'store_manager';
        }
     }">

    {{-- Top Header --}}
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ $returnTo ?? route('store.admin.users.index', $storeRouteParams) }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition shadow-sm">
                ←
            </a>
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.users.index', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        Users & Staff
                    </a>
                    <span>/</span>
                    <span class="text-violet-600 dark:text-violet-400">Edit User</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>{{ $managedUser->name }}</span>
                    @if ($managedUser->id === auth()->id())
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300">YOU</span>
                    @endif
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">📞 {{ $managedUser->phone }} · {{ $store->name }}</p>
            </div>
        </div>

        <a href="{{ $returnTo ?? route('store.admin.users.index', $storeRouteParams) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
            Back to list
        </a>
    </div>

    {{-- Validation Flash Errors --}}
    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-3xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>ဖြည့်သွင်းချက်များ မှားယွင်းနေပါသည်:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-5">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Edit Form Container --}}
    <form method="POST" action="{{ route('store.admin.users.update', array_merge($storeRouteParams, ['user' => $managedUser->id])) }}"
          class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
        @csrf
        @method('PUT')

        {{-- Section 1: Basic Profile Info --}}
        <div>
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                <span>👤</span>
                <span>အခြေခံ အချက်အလက်များ (Basic Profile)</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အမည် (Full Name) *</label>
                    <input type="text" name="name" value="{{ old('name', $managedUser->name) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ဖုန်းနံပါတ် (Phone) *</label>
                    <input type="text" name="phone" value="{{ old('phone', $managedUser->phone) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono font-semibold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">အီးမေးလ် (Email - Optional)</label>
                    <input type="email" name="email" value="{{ old('email', $managedUser->email) }}"
                           placeholder="optional@example.com"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-medium bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
            </div>
        </div>

        {{-- Section 2: Store Role & Permission Template --}}
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                <span>🛡️</span>
                <span>ရာထူးနှင့် အခွင့်အရေးများ (Role & Permissions)</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">စနစ်အဆင့် (System Role) *</label>
                    <select name="role" x-model="selectedRole" required
                            class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" {{ old('role', $currentRole) === $role ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $role)) }} {{ $role === 'platform_owner' ? '(Global Owner)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Granular Staff Role Template --}}
                <div x-show="isStaffRole(selectedRole)" x-transition>
                    <label class="block text-xs font-bold text-blue-700 dark:text-blue-300 mb-1">
                        <span>🛡️ ဝန်ထမ်း ရာထူးပုံစံ (Staff Role Template)</span>
                    </label>
                    <select name="staff_role_id"
                            class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold bg-blue-50/50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- No Specific Role (Default) --</option>
                        @foreach ($staffRoles as $sr)
                            <option value="{{ $sr->id }}" {{ $currentStaffRoleId === (string) $sr->id ? 'selected' : '' }}>
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
                            <option value="{{ $status }}" {{ old('status', $membership?->status ?? 'active') === $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div x-show="isStaffRole(selectedRole)" x-transition>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        POS Manager PIN <span class="text-slate-400 font-normal">(4-6 Digits, Optional)</span>
                    </label>
                    <input type="password" name="pos_pin" inputmode="numeric" maxlength="6" autocomplete="new-password"
                           placeholder="ထားခဲ့ပါက ယခင်အတိုင်း မပြောင်းလဲပါ"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                    <p class="text-[11px] text-slate-400 mt-1">ကောင်တာတွင် ဈေးလျှော့ခွင့် သတ်မှတ်ချက် ကျော်လွန်သည့်အခါ မန်နေဂျာအတည်ပြု PIN</p>
                </div>
            </div>
        </div>

        {{-- Section 3: Reset Password (Optional) --}}
        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-1 flex items-center gap-1.5">
                <span>🔑</span>
                <span>စကားဝှက် အသစ်လဲလှယ်ခြင်း (Reset Password - Optional)</span>
            </h3>
            <p class="text-[11px] text-slate-400 mb-3">စကားဝှက် အဟောင်းအတိုင်း ထားလိုပါက အလွတ်ထားခဲ့နိုင်ပါသည်</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">စကားဝှက် အသစ် (New Password)</label>
                    <input type="password" name="password" autocomplete="new-password"
                           placeholder="Leave blank to keep current"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">စကားဝှက် အတည်ပြုခြင်း (Confirm Password)</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password"
                           placeholder="Confirm new password"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500" />
                </div>
            </div>
        </div>

        @if ($managedUser->id === auth()->id())
            <div class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs font-bold text-amber-800 dark:text-amber-300 flex items-center gap-2">
                <span>⚠️</span>
                <span>မိမိကိုယ်ပိုင် Platform Owner အဆင့်အား ဤနေရာမှ ဖြုတ်ချခွင့် မရှိပါ။</span>
            </div>
        @endif

        {{-- Form Submit Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ $returnTo ?? route('store.admin.users.index', $storeRouteParams) }}"
               class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-black bg-violet-600 hover:bg-violet-500 text-white shadow-md shadow-violet-500/20 transition">
                Save Changes (သိမ်းဆည်းမည်)
            </button>
        </div>
    </form>

</div>
@endsection
