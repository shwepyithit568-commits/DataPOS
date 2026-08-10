@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Wholesale Applications</h1>
            <p class="admin-page-sub">{{ $store->name }} · {{ number_format($totalCount) }} applications</p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-amber-600 dark:text-amber-400">Pending</div>
            <div class="admin-stat-value">{{ number_format($stats['pending']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">Approved</div>
            <div class="admin-stat-value">{{ number_format($stats['approved']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-red-600 dark:text-red-400">Rejected</div>
            <div class="admin-stat-value">{{ number_format($stats['rejected']) }}</div>
        </div>
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-gray-600 dark:text-gray-400">Suspended</div>
            <div class="admin-stat-value">{{ number_format($stats['suspended']) }}</div>
        </div>
    </div>

    {{-- Success Flash --}}
    @if (session('success'))
        <div class="p-3.5 sm:p-4 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-start gap-2">
            <span class="text-base flex-shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Reusable Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="Search business name, phone or applicant..."
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => 'Newest',
            'oldest' => 'Oldest',
            'business' => 'Business: A to Z'
        ]"
        :filters="[
            'status' => [
                'label' => 'Status',
                'options' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended']
            ]
        ]"
        :showViewToggle="false"
        :showExportImport="false"
        :totalCount="$totalCount"
        :paginator="$applications"
    />

    {{-- ===== Card view (mobile only) ===== --}}
    <div class="sm:hidden space-y-3">
        @forelse ($applications as $application)
            <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 space-y-2.5 transition-colors duration-200">
                {{-- Top: business name + status --}}
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-sm text-gray-900 dark:text-slate-100 truncate">{{ $application->business_name }}</div>
                        <div class="text-xs text-gray-400 dark:text-slate-500">{{ $application->user?->name ?? 'Guest' }} · {{ $application->phone }}</div>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full uppercase whitespace-nowrap
                        {{ $application->status === 'approved' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : '' }}
                        {{ $application->status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' : '' }}
                        {{ $application->status === 'rejected' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : '' }}
                        {{ $application->status === 'suspended' ? 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300' : '' }}">
                        {{ $application->status }}
                    </span>
                </div>

                @if ($application->address)
                    <div class="text-xs text-gray-500 dark:text-slate-400 flex items-start gap-1">
                        <span class="flex-shrink-0">📍</span>
                        <span class="line-clamp-2">{{ $application->address }}</span>
                    </div>
                @endif

                @if ($application->notes)
                    <div class="text-xs text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-900/60 rounded-lg p-2">
                        <span class="font-semibold">Notes:</span> {{ $application->notes }}
                    </div>
                @endif

                {{-- Action --}}
                <div class="pt-2 border-t dark:border-slate-700">
                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/wholesale/applications/' . $application->id) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <label class="text-xs text-gray-500 dark:text-slate-400 font-semibold">Change:</label>
                        <select name="status" data-auto-submit class="flex-1 text-xs border rounded-lg px-2 py-1.5 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-slate-100 cursor-pointer">
                            <option value="pending" {{ $application->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ $application->status === 'approved' ? 'selected' : '' }}>Approve</option>
                            <option value="rejected" {{ $application->status === 'rejected' ? 'selected' : '' }}>Reject</option>
                            <option value="suspended" {{ $application->status === 'suspended' ? 'selected' : '' }}>Suspend</option>
                        </select>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 p-8 rounded-xl text-center">
                <div class="text-4xl mb-3 opacity-40">💼</div>
                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">No applications found</div>
                <div class="text-xs text-gray-500 dark:text-slate-400">Try adjusting your search or filters.</div>
            </div>
        @endforelse
    </div>

    {{-- ===== Table view (tablet + desktop) ===== --}}
    <div class="hidden sm:block bg-white dark:bg-slate-800 rounded-xl overflow-hidden transition-colors duration-200">
        <div class="admin-panel overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm text-gray-600 dark:text-slate-300">
                <thead class="bg-gray-50 dark:bg-slate-900/50 border-b dark:border-slate-700 font-semibold text-gray-700 dark:text-slate-200">
                    <tr>
                        <th class="p-3 whitespace-nowrap">Applicant</th>
                        <th class="p-3 whitespace-nowrap">Business</th>
                        <th class="p-3 whitespace-nowrap">Phone</th>
                        <th class="p-3 whitespace-nowrap">Address</th>
                        <th class="p-3 whitespace-nowrap">Date</th>
                        <th class="p-3 whitespace-nowrap">Status</th>
                        <th class="p-3 whitespace-nowrap">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-slate-700">
                    @forelse ($applications as $application)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-slate-700/50 transition">
                            <td class="p-3">
                                <div class="font-bold text-gray-900 dark:text-slate-100">{{ $application->user?->name ?? 'Guest' }}</div>
                                <div class="text-xs text-gray-400 dark:text-slate-500">{{ $application->user?->phone ?? '—' }}</div>
                            </td>
                            <td class="p-3 font-medium text-gray-900 dark:text-slate-100">{{ $application->business_name }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $application->phone }}</td>
                            <td class="p-3 text-xs text-gray-500 dark:text-slate-400 max-w-[200px]">
                                @if ($application->address)
                                    <span class="line-clamp-2">{{ $application->address }}</span>
                                @else
                                    <span class="text-gray-300 dark:text-slate-600">—</span>
                                @endif
                            </td>
                            <td class="p-3 text-xs text-gray-500 dark:text-slate-400 whitespace-nowrap">{{ $application->created_at->format('M d, Y') }}</td>
                            <td class="p-3">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase whitespace-nowrap
                                    {{ $application->status === 'approved' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : '' }}
                                    {{ $application->status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' : '' }}
                                    {{ $application->status === 'rejected' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300' : '' }}
                                    {{ $application->status === 'suspended' ? 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300' : '' }}">
                                    {{ $application->status }}
                                </span>
                            </td>
                            <td class="p-3">
                                <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/wholesale/applications/' . $application->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" data-auto-submit class="text-xs border rounded-lg px-2 py-1.5 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-slate-100 cursor-pointer">
                                        <option value="pending" {{ $application->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="approved" {{ $application->status === 'approved' ? 'selected' : '' }}>Approve</option>
                                        <option value="rejected" {{ $application->status === 'rejected' ? 'selected' : '' }}>Reject</option>
                                        <option value="suspended" {{ $application->status === 'suspended' ? 'selected' : '' }}>Suspend</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center">
                                <div class="text-4xl mb-3 opacity-40">💼</div>
                                <div class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-1">No applications found</div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">Try adjusting your search or filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if (method_exists($applications, 'links'))
        <div class="text-sm">{{ $applications->links() }}</div>
    @endif
</div>
@endsection
