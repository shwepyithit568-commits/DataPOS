@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    <div class="flex items-start sm:items-center justify-between gap-3 flex-wrap">
        <div class="min-w-0">
            <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit leading-tight">
                Edit User
            </h1>
            <p class="text-xs text-gray-500 dark:text-slate-400">
                {{ $managedUser->name }} · {{ substr($managedUser->phone, 0, 4) }}****{{ substr($managedUser->phone, -3) }}
            </p>
        </div>
        <a href="{{ $returnTo ?? route('store.admin.users.index', ['store_slug' => $store->slug]) }}" class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline">
            Back to users
        </a>
    </div>

    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1">
            <div class="font-bold">Please check the form:</div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('store.admin.users.update', ['store_slug' => $store->slug, 'user' => $managedUser]) }}" class="bg-white dark:bg-slate-800 rounded-xl p-4 sm:p-5 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $managedUser->name) }}" required class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $managedUser->phone) }}" required class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Email optional</label>
                <input type="email" name="email" value="{{ old('email', $managedUser->email) }}" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Role</label>
                <select name="role" required class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none">
                    @foreach ($roles as $role)
                        @php
                            $currentRole = $managedUser->role === 'platform_owner' ? 'platform_owner' : ($membership?->role ?? 'retail_customer');
                        @endphp
                        <option value="{{ $role }}" {{ old('role', $currentRole) === $role ? 'selected' : '' }}>{{ str_replace('_', ' ', $role) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Store status</label>
                <select name="status" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none">
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ old('status', $membership?->status ?? 'active') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 dark:border-slate-700 space-y-3">
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-slate-100">Reset password optional</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400">Leave both fields blank to keep the current password.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">New password</label>
                    <input type="password" name="password" autocomplete="new-password" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Confirm new password</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
                </div>
            </div>
        </div>

        @if ($managedUser->id === auth()->id())
            <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300">
                You can update your profile details, but you cannot remove your own platform owner role.
            </div>
        @endif

        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" class="px-4 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 font-semibold text-sm shadow">
                Save Changes
            </button>
            <a href="{{ $returnTo ?? route('store.admin.users.index', ['store_slug' => $store->slug]) }}" class="px-4 py-2.5 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 font-semibold text-sm text-center">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
