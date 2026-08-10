@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Users & Customers</h1>
            <p class="admin-page-sub">{{ $store->name }} · Platform owner only</p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1">
            <div class="font-bold">Please check the form:</div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div
        x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }"
        class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden"
    >
        <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between gap-2 p-4 sm:p-5 text-left hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
            <span class="text-sm sm:text-base font-semibold text-gray-800 dark:text-slate-100">Create User Account</span>
            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <div x-show="open" x-transition>
            <form method="POST" action="{{ route('store.admin.users.store', ['store_slug' => $store->slug]) }}" class="p-4 sm:p-5 pt-0 space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="09..." class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Email optional</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Role</label>
                        <select name="role" required class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none">
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" {{ old('role', 'retail_customer') === $role ? 'selected' : '' }}>{{ str_replace('_', ' ', $role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Store status</label>
                        <select name="status" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ old('status', 'active') === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Password</label>
                        <input type="password" name="password" required autocomplete="new-password" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Confirm password</label>
                        <input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full border dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-slate-400">Passwords must have at least 12 characters with uppercase, lowercase, number, and symbol.</p>

                <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-violet-600 text-white rounded-lg hover:bg-violet-700 font-semibold text-sm shadow">
                    Create Account
                </button>
            </form>
        </div>
    </div>

    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="Search name, phone, or email"
        :sort="request('sort', 'newest')"
        :sortOptions="[]"
        :filters="[
            'role' => [
                'label' => 'Role',
                'options' => array_combine($roles, array_map(fn ($r) => str_replace('_', ' ', $r), $roles)) ?: [],
            ],
        ]"
        :showExportImport="false"
        :totalCount="$users->total()"
        :perPage="$users->perPage()"
        :paginator="$users"
        :showPagination="false"
    />

    <div class="bg-white dark:bg-slate-800 rounded-xl overflow-hidden">
        <div class="admin-panel overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-gray-50 dark:bg-slate-900/60 text-gray-600 dark:text-slate-300 border-b dark:border-slate-700">
                    <tr>
                        <th class="p-3">User</th>
                        <th class="p-3">Global role</th>
                        <th class="p-3">Store role</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse ($users as $user)
                        @php $membership = $user->stores->first()?->pivot; @endphp
                        <tr>
                            <td class="p-3">
                                <div class="font-semibold text-gray-900 dark:text-slate-100">{{ $user->name }}</div>
                                <div class="font-mono text-xs text-gray-500 dark:text-slate-400">{{ substr($user->phone, 0, 4) }}****{{ substr($user->phone, -3) }}</div>
                            </td>
                            <td class="p-3">{{ $user->role }}</td>
                            <td class="p-3">{{ $membership?->role ?? '-' }}</td>
                            <td class="p-3">{{ $membership?->status ?? ($user->role === 'platform_owner' ? 'global' : '-') }}</td>
                            <td class="p-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('store.admin.users.edit', ['store_slug' => $store->slug, 'user' => $user]) }}" class="px-3 py-1.5 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-700 dark:text-violet-300 font-semibold border border-violet-200 dark:border-violet-800">
                                        Edit
                                    </a>
                                    @if (! $user->isPlatformOwner() && auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('store.admin.users.suspend', ['store_slug' => $store->slug, 'user' => $user]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" data-confirm="Suspend this user store access?" class="px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 font-semibold border border-amber-200 dark:border-amber-800">
                                                Suspend
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500 dark:text-slate-400">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $users->links() }}
</div>
@endsection
