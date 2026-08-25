@extends('layouts.admin.app')

@section('title', 'Wholesale Applications - ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12"
     x-data="{
        viewMode: localStorage.getItem('admin_wholesale_view_mode') || 'table',
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_wholesale_view_mode', $event.detail)">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 grid place-items-center text-xl sm:text-2xl font-bold shadow-sm flex-shrink-0">
                💼
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.dashboard', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        Dashboard
                    </a>
                    <span>/</span>
                    <span class="text-indigo-600 dark:text-indigo-400">Wholesale Applications</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>လက်ကားဝယ်ယူသူ လျှောက်လွှာများ (Wholesale Applications)</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $store->name }} · လက်ကားဖောက်သည်ဖြစ်ရန် လျှောက်ထားသော ဖောက်သည်များနှင့် လုပ်ငန်းများ</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ $exportUrl }}"
               class="px-3.5 py-2.5 rounded-2xl text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                <span>📊</span>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('store.admin.orders.index', $storeRouteParams) }}"
               class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-black bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/20 transition flex items-center gap-2 active:scale-95">
                <span>🛒</span>
                <span>အော်ဒါများ ကြည့်ရှုမည်</span>
            </a>
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
                <span>အမှားအယွင်း ရှိနေပါသည်:</span>
            </div>
            @foreach ($errors->all() as $error)
                <p class="ml-5">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 2. 4 Key KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-3.5">

        {{-- Pending --}}
        <a href="{{ route('store.admin.wholesale.applications.index', array_merge($storeRouteParams, ['tab' => 'pending'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'pending' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-amber-600 dark:text-amber-400 truncate">Pending</span>
                <span class="text-base">⏳</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">{{ number_format($stats['pending']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">စစ်ဆေးရန် ကျန်ရှိ</p>
        </a>

        {{-- Approved --}}
        <a href="{{ route('store.admin.wholesale.applications.index', array_merge($storeRouteParams, ['tab' => 'approved'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'approved' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 truncate">Approved</span>
                <span class="text-base">✅</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">{{ number_format($stats['approved']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">လက်ကားဝင်ခွင့်ပြုပြီး</p>
        </a>

        {{-- Rejected --}}
        <a href="{{ route('store.admin.wholesale.applications.index', array_merge($storeRouteParams, ['tab' => 'rejected'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'rejected' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-400 truncate">Rejected</span>
                <span class="text-base">❌</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">{{ number_format($stats['rejected']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ငြင်းပယ်ထားသည်</p>
        </a>

        {{-- Suspended --}}
        <a href="{{ route('store.admin.wholesale.applications.index', array_merge($storeRouteParams, ['tab' => 'suspended'])) }}"
           class="rounded-3xl bg-white dark:bg-slate-900 border {{ $tab === 'suspended' ? 'border-slate-500 ring-2 ring-slate-500/20' : 'border-slate-200/90 dark:border-slate-800' }} p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-400 truncate">Suspended</span>
                <span class="text-base">🚫</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-slate-600 dark:text-slate-400 font-mono tracking-tight">{{ number_format($stats['suspended']) }}</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-0.5">ဆိုင်းငံ့ထားသည်</p>
        </a>

    </div>

    {{-- 3. Unified Admin Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        searchPlaceholder="လုပ်ငန်းအမည်၊ ဖုန်းနံပါတ် သို့မဟုတ် လျှောက်ထားသူ ရှာဖွေပါ..."
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest'   => 'အသစ်ဆုံး (Newest First)',
            'oldest'   => 'အဟောင်းဆုံး (Oldest First)',
            'business' => 'လုပ်ငန်းအမည်: က မှ ဇ (A → Z)',
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

    {{-- 4. Card View (Alpine Toggle) --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($applications as $application)
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 group">
                <div class="space-y-3">
                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div class="min-w-0">
                            <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                               class="font-black text-sm text-indigo-600 dark:text-indigo-400 group-hover:underline flex items-center gap-1.5 truncate">
                                <span class="truncate">{{ $application->business_name }}</span>
                                @if ($application->admin_note)
                                    <span title="Admin Note: {{ $application->admin_note }}">📝</span>
                                @endif
                            </a>
                            <span class="text-[11px] text-slate-400 block">{{ $application->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <span class="flex-shrink-0 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap
                            {{ $application->status === 'approved'  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                            {{ $application->status === 'pending'   ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                            {{ $application->status === 'rejected'  ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}
                            {{ $application->status === 'suspended' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                            {{ $application->status }}
                        </span>
                    </div>

                    {{-- Applicant Info --}}
                    <div>
                        <div class="font-black text-sm text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span>{{ $application->user?->name ?? 'Guest Applicant' }}</span>
                            <span class="text-[10px] font-mono text-slate-400">#{{ $application->id }}</span>
                        </div>
                        <div class="font-mono text-xs text-slate-400">📞 {{ $application->phone }}</div>
                        @if ($application->address)
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">📍 {{ $application->address }}</div>
                        @endif
                    </div>

                    {{-- Notes --}}
                    @if ($application->notes)
                        <div class="bg-blue-50 dark:bg-blue-950/40 rounded-2xl px-3 py-2 text-[11px] text-blue-700 dark:text-blue-300 line-clamp-2">
                            💬 {{ $application->notes }}
                        </div>
                    @endif
                </div>

                {{-- Card Actions --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <form method="POST" action="{{ route('store.admin.wholesale.applications.update', array_merge($storeRouteParams, ['application' => $application->id])) }}" class="inline-flex items-center gap-1">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="text-[11px] font-bold border rounded-xl px-2 py-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100">
                            <option value="pending"   {{ $application->status === 'pending'   ? 'selected' : '' }}>Pending</option>
                            <option value="approved"  {{ $application->status === 'approved'  ? 'selected' : '' }}>Approve</option>
                            <option value="rejected"  {{ $application->status === 'rejected'  ? 'selected' : '' }}>Reject</option>
                            <option value="suspended" {{ $application->status === 'suspended' ? 'selected' : '' }}>Suspend</option>
                        </select>
                        <button type="submit" class="px-2 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow-sm">
                            ✓
                        </button>
                    </form>

                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                           class="px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 transition">
                            View
                        </a>
                        <a href="{{ route('store.admin.wholesale.applications.print', array_merge($storeRouteParams, ['application' => $application->id])) }}" target="_blank"
                           class="px-2 py-1 rounded-xl text-xs font-bold bg-sky-50 hover:bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 transition" title="Print Application Slip">
                            🧾
                        </a>
                        @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                            <form method="POST" action="{{ route('store.admin.wholesale.applications.destroy', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                  onsubmit="return confirm('\"{{ $application->business_name }}\" ၏ လျှောက်လွှာကို ဖျက်မည်မှာ သေချာပါသလား?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition" title="Delete">
                                    🗑
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                လျှောက်လွှာမှတ်တမ်း မရှိသေးပါ။ (No wholesale applications found.)
            </div>
        @endforelse
    </div>

    {{-- 5. Table View (Alpine Toggle) --}}
    <div x-show="viewMode === 'table'" class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">လျှောက်ထားသူ (Applicant)</th>
                        <th class="py-3.5 px-4">လုပ်ငန်းအမည် (Business)</th>
                        <th class="py-3.5 px-4">ဖုန်းနံပါတ် (Phone)</th>
                        <th class="py-3.5 px-4">လိပ်စာ (Address)</th>
                        <th class="py-3.5 px-4">ရက်စွဲ (Date)</th>
                        <th class="py-3.5 px-4 text-center">အခြေအနေ (Status)</th>
                        <th class="py-3.5 px-4 text-right">လုပ်ဆောင်ချက် (Actions)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($applications as $application)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ $application->user?->name ?? 'Guest Applicant' }}</div>
                                <div class="font-mono text-[11px] text-slate-400">#{{ $application->id }} · {{ $application->user?->phone ?? '—' }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                   class="font-black text-indigo-600 dark:text-indigo-400 text-xs hover:underline flex items-center gap-1">
                                    {{ $application->business_name }}
                                    @if ($application->admin_note)
                                        <span title="{{ $application->admin_note }}">📝</span>
                                    @endif
                                </a>
                                @if ($application->admin_note)
                                    <span class="text-[10px] text-slate-400 truncate block max-w-[200px]">{{ Str::limit($application->admin_note, 30) }}</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ $application->phone }}</td>

                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 max-w-[180px]">
                                @if ($application->address)
                                    <span class="line-clamp-2 text-[11px]">{{ $application->address }}</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-400 text-[11px] whitespace-nowrap">
                                {{ $application->created_at->format('M d, Y h:i A') }}
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap
                                    {{ $application->status === 'approved'  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                                    {{ $application->status === 'pending'   ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                                    {{ $application->status === 'rejected'  ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}
                                    {{ $application->status === 'suspended' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                                    {{ $application->status }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <form method="POST" action="{{ route('store.admin.wholesale.applications.update', array_merge($storeRouteParams, ['application' => $application->id])) }}" class="inline-flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="text-[10px] font-bold border rounded-xl px-2 py-1 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100">
                                            <option value="pending"   {{ $application->status === 'pending'   ? 'selected' : '' }}>Pending</option>
                                            <option value="approved"  {{ $application->status === 'approved'  ? 'selected' : '' }}>Approve</option>
                                            <option value="rejected"  {{ $application->status === 'rejected'  ? 'selected' : '' }}>Reject</option>
                                            <option value="suspended" {{ $application->status === 'suspended' ? 'selected' : '' }}>Suspend</option>
                                        </select>
                                        <button type="submit" class="px-2 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] shadow-sm">
                                            Update
                                        </button>
                                    </form>

                                    <a href="{{ route('store.admin.wholesale.applications.show', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                       class="px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 transition">
                                        View
                                    </a>

                                    <a href="{{ route('store.admin.wholesale.applications.print', array_merge($storeRouteParams, ['application' => $application->id])) }}" target="_blank"
                                       class="px-2 py-1 rounded-xl text-xs font-bold bg-sky-50 hover:bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 transition" title="Print Application Slip">
                                        🧾
                                    </a>

                                    @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                                        <form method="POST" action="{{ route('store.admin.wholesale.applications.destroy', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                                              onsubmit="return confirm('\"{{ $application->business_name }}\" ၏ လျှောက်လွှာကို ဖျက်မည်မှာ သေချာပါသလား?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition" title="Delete">
                                                🗑
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                                လျှောက်လွှာမှတ်တမ်း မရှိသေးပါ။ (No wholesale applications found.)
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6. Pagination --}}
    <div class="mt-4">
        {{ $applications->links() }}
    </div>

</div>
@endsection
