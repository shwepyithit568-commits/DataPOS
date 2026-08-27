@extends('layouts.admin.app')

@section('title', 'Wholesale Applications - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_wholesale_view_mode') || 'table',
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_wholesale_view_mode', $event.detail)">

    {{-- ============================================================
         1. TOP PAGE HEADER — Eyebrow, Title, Context & Action Buttons
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-indigo-100 dark:border-indigo-900/60 mb-0.5">
                <span>💼</span>
                <span>{{ __('messages.sidebar_wholesale_applications') }}</span>
                <span class="text-slate-400 dark:text-slate-500">·</span>
                <span class="font-normal normal-case text-slate-500 dark:text-slate-400">B2B Merchant Portal</span>
            </div>
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>{{ __('messages.wholesale_admin_title') }}</span>
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $store->name }} · {{ __('messages.wholesale_admin_subtitle') }}
            </p>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ $exportUrl }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <span>📊</span>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('store.admin.orders.index', $storeRouteParams) }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <span>🛒</span>
                <span>အော်ဒါများ</span>
            </a>
        </div>
    </header>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            <div class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') }}:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-4">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         2. 4 KEY KPI CARDS — Compact Filterable Stat Cards
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2">

        {{-- Pending --}}
        <a href="{{ route('store.admin.wholesale.applications.index', array_merge($storeRouteParams, ['tab' => 'pending'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ $tab === 'pending' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 truncate">{{ __('messages.wholesale_status_pending') }}</span>
                <span class="text-xs">⏳</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono tracking-tight">{{ number_format($stats['pending']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">စစ်ဆေးရန် ကျန်ရှိ</div>
        </a>

        {{-- Approved --}}
        <a href="{{ route('store.admin.wholesale.applications.index', array_merge($storeRouteParams, ['tab' => 'approved'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ $tab === 'approved' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 truncate">{{ __('messages.wholesale_status_approved') }}</span>
                <span class="text-xs">✅</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono tracking-tight">{{ number_format($stats['approved']) }}</div>
            <div class="text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5">လက်ကားဝင်ခွင့်ပြုပြီး</div>
        </a>

        {{-- Rejected --}}
        <a href="{{ route('store.admin.wholesale.applications.index', array_merge($storeRouteParams, ['tab' => 'rejected'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ $tab === 'rejected' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 truncate">{{ __('messages.wholesale_status_rejected') }}</span>
                <span class="text-xs">❌</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-rose-600 dark:text-rose-400 mt-1 font-mono tracking-tight">{{ number_format($stats['rejected']) }}</div>
            <div class="text-[10px] text-rose-600/80 dark:text-rose-400/80 mt-0.5">ငြင်းပယ်ထားသည်</div>
        </a>

        {{-- Suspended --}}
        <a href="{{ route('store.admin.wholesale.applications.index', array_merge($storeRouteParams, ['tab' => 'suspended'])) }}"
           class="bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3 border {{ $tab === 'suspended' ? 'border-slate-500 ring-2 ring-slate-500/20' : 'border-slate-200/80 dark:border-slate-800' }} shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 truncate">{{ __('messages.wholesale_status_suspended') }}</span>
                <span class="text-xs">🚫</span>
            </div>
            <div class="text-lg sm:text-2xl font-black text-slate-700 dark:text-slate-300 mt-1 font-mono tracking-tight">{{ number_format($stats['suspended']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">ဆိုင်းငံ့ထားသည်</div>
        </a>

    </div>

    {{-- ============================================================
         3. UNIFIED ADMIN TOOLBAR
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="လုပ်ငန်းအမည်၊ ဖုန်းနံပါတ် သို့မဟုတ် လျှောက်ထားသူ ရှာဖွေပါ..."
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest'   => 'အသစ်ဆုံး (Newest First)',
            'oldest'   => 'အဟောင်းဆုံး (Oldest First)',
            'business' => 'လုပ်ငန်းအမည်: A → Z',
        ]"
        :filters="[
            'tab' => [
                'label'   => 'Status',
                'options' => [
                    'all'       => 'အားလုံး (All Applications)',
                    'pending'   => 'Pending (စစ်ဆေးဆဲ)',
                    'approved'  => 'Approved (ခွင့်ပြုပြီး)',
                    'rejected'  => 'Rejected (ငြင်းပယ်)',
                    'suspended' => 'Suspended (ဆိုင်းငံ့)',
                ],
            ],
        ]"
        :viewMode="'table'"
        :showViewToggle="true"
        :showExportImport="true"
        :exportUrl="$exportUrl"
        :totalCount="$applications->total()"
        :perPage="$applications->perPage()"
        :paginator="$applications"
        :showPagination="true"
    />

    {{-- ============================================================
         4. DUAL VIEWS: CARD GRID VIEW & SPREADSHEET TABLE VIEW
         ============================================================ --}}
    <div>
        {{-- Card View (viewMode === 'card') --}}
        <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-2.5">
            @forelse ($applications as $application)
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition flex flex-col justify-between space-y-2.5 group">
                    <div class="space-y-2">
                        {{-- Card Header --}}
                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <div class="min-w-0">
                                <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                   class="font-black text-xs sm:text-sm text-indigo-600 dark:text-indigo-400 group-hover:underline flex items-center gap-1.5 truncate">
                                    <span class="truncate">{{ $application->business_name }}</span>
                                    @if ($application->admin_note)
                                        <span title="Admin Note: {{ $application->admin_note }}">📝</span>
                                    @endif
                                </a>
                                <span class="text-[10px] text-slate-400 block font-mono">{{ $application->created_at->format('M d, Y h:i A') }}</span>
                            </div>
                            <span class="shrink-0 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase whitespace-nowrap
                                {{ $application->status === 'approved'  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                {{ $application->status === 'pending'   ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                                {{ $application->status === 'rejected'  ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}
                                {{ $application->status === 'suspended' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                                {{ $application->status }}
                            </span>
                        </div>

                        {{-- Applicant Info --}}
                        <div class="space-y-1 text-xs">
                            <div class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                <span>{{ $application->user?->name ?? 'Guest Applicant' }}</span>
                                <span class="text-[10px] font-mono text-slate-400">#{{ $application->id }}</span>
                            </div>
                            <div class="font-mono text-[11px] text-slate-600 dark:text-slate-300">📞 {{ $application->phone }}</div>
                            @if ($application->address)
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">📍 {{ $application->address }}</div>
                            @endif
                        </div>

                        {{-- Notes --}}
                        @if ($application->notes)
                            <div class="bg-blue-50 dark:bg-blue-950/40 rounded-lg p-2 text-[11px] text-blue-700 dark:text-blue-300 line-clamp-2 border border-blue-100 dark:border-blue-900/40">
                                💬 {{ $application->notes }}
                            </div>
                        @endif
                    </div>

                    {{-- Card Actions --}}
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1.5">
                        <form method="POST" action="{{ route('store.admin.wholesale.applications.update', array_merge($storeRouteParams, ['application' => $application->id])) }}" class="inline-flex items-center gap-1">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="text-[10px] font-bold border rounded-md px-1.5 py-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100">
                                <option value="pending"   {{ $application->status === 'pending'   ? 'selected' : '' }}>Pending</option>
                                <option value="approved"  {{ $application->status === 'approved'  ? 'selected' : '' }}>Approve</option>
                                <option value="rejected"  {{ $application->status === 'rejected'  ? 'selected' : '' }}>Reject</option>
                                <option value="suspended" {{ $application->status === 'suspended' ? 'selected' : '' }}>Suspend</option>
                            </select>
                            <button type="submit" class="px-2 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] shadow-2xs active:scale-95">
                                ✓
                            </button>
                        </form>

                        <div class="flex items-center gap-1">
                            <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                               class="px-2 py-1 rounded-md text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 transition">
                                {{ __('messages.customer_detail') }}
                            </a>
                            <a href="{{ route('store.admin.wholesale.applications.print', array_merge($storeRouteParams, ['application' => $application->id])) }}" target="_blank"
                               class="px-2 py-1 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 transition" title="Print Slip">
                                🧾
                            </a>
                            @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                                <form method="POST" action="{{ route('store.admin.wholesale.applications.destroy', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                      onsubmit="return confirm('{{ __('messages.wholesale_delete_confirm') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-1.5 py-1 rounded-md text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition cursor-pointer" title="Delete">
                                        🗑
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                    လျှောက်လွှာမှတ်တမ်း မရှိသေးပါ။ (No wholesale applications found.)
                </div>
            @endforelse
        </div>

        {{-- Spreadsheet Table View (viewMode === 'table') --}}
        <div x-show="viewMode === 'table'" class="rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden transition">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[750px]">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="p-2.5">{{ __('messages.wholesale_applicant') }}</th>
                            <th class="p-2.5">{{ __('messages.wholesale_business_name') }}</th>
                            <th class="p-2.5">{{ __('messages.wholesale_phone') }}</th>
                            <th class="p-2.5">{{ __('messages.wholesale_address') }}</th>
                            <th class="p-2.5">{{ __('messages.wholesale_applied_date') }}</th>
                            <th class="p-2.5 text-center">Status</th>
                            <th class="p-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($applications as $application)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                <td class="p-2.5">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ $application->user?->name ?? 'Guest Applicant' }}</div>
                                    <div class="font-mono text-[10px] text-slate-400">#{{ $application->id }} · {{ $application->user?->phone ?? '—' }}</div>
                                </td>

                                <td class="p-2.5">
                                    <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                       class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                                        {{ $application->business_name }}
                                        @if ($application->admin_note)
                                            <span title="{{ $application->admin_note }}">📝</span>
                                        @endif
                                    </a>
                                    @if ($application->admin_note)
                                        <span class="text-[10px] text-slate-400 truncate block max-w-[200px]">{{ Str::limit($application->admin_note, 30) }}</span>
                                    @endif
                                </td>

                                <td class="p-2.5 font-mono text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ $application->phone }}</td>

                                <td class="p-2.5 text-slate-500 dark:text-slate-400 max-w-[180px]">
                                    @if ($application->address)
                                        <span class="line-clamp-1 text-[11px]">{{ $application->address }}</span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>

                                <td class="p-2.5 text-slate-400 text-[11px] whitespace-nowrap font-mono">
                                    {{ $application->created_at->format('M d, Y h:i A') }}
                                </td>

                                <td class="p-2.5 text-center">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase whitespace-nowrap
                                        {{ $application->status === 'approved'  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                        {{ $application->status === 'pending'   ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                                        {{ $application->status === 'rejected'  ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}
                                        {{ $application->status === 'suspended' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                                        {{ $application->status }}
                                    </span>
                                </td>

                                <td class="p-2.5 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <form method="POST" action="{{ route('store.admin.wholesale.applications.update', array_merge($storeRouteParams, ['application' => $application->id])) }}" class="inline-flex items-center gap-1">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="text-[10px] font-bold border rounded-md px-1.5 py-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100">
                                                <option value="pending"   {{ $application->status === 'pending'   ? 'selected' : '' }}>Pending</option>
                                                <option value="approved"  {{ $application->status === 'approved'  ? 'selected' : '' }}>Approve</option>
                                                <option value="rejected"  {{ $application->status === 'rejected'  ? 'selected' : '' }}>Reject</option>
                                                <option value="suspended" {{ $application->status === 'suspended' ? 'selected' : '' }}>Suspend</option>
                                            </select>
                                            <button type="submit" class="px-2 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] shadow-2xs active:scale-95">
                                                Update
                                            </button>
                                        </form>

                                        <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                           class="px-2 py-1 rounded-md text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 transition">
                                            View
                                        </a>

                                        <a href="{{ route('store.admin.wholesale.applications.print', array_merge($storeRouteParams, ['application' => $application->id])) }}" target="_blank"
                                           class="px-2 py-1 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 transition" title="Print Slip">
                                            🧾
                                        </a>

                                        @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                                            <form method="POST" action="{{ route('store.admin.wholesale.applications.destroy', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                                  onsubmit="return confirm('{{ __('messages.wholesale_delete_confirm') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-1.5 py-1 rounded-md text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition cursor-pointer" title="Delete">
                                                    🗑
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400 dark:text-slate-500 text-xs font-bold">
                                    လျှောက်လွှာမှတ်တမ်း မရှိသေးပါ။ (No wholesale applications found.)
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($applications->hasPages())
        <div class="mt-2.5">
            {{ $applications->links() }}
        </div>
    @endif

</div>
@endsection
