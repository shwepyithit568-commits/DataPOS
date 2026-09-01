@extends('layouts.admin.app')

@section('title', __('messages.theme_governance') . ' - DataPOS')
@section('main_padding', 'p-0.5 sm:p-1')

@php
    $totalCount = count($themes);
    $activeCount = count(array_filter($themes, fn($t) => $t->status === \App\Models\ThemeGovernance::STATUS_ACTIVE));
    $deprecatedCount = count(array_filter($themes, fn($t) => $t->status === \App\Models\ThemeGovernance::STATUS_DEPRECATED));
    $hiddenCount = count(array_filter($themes, fn($t) => $t->status === \App\Models\ThemeGovernance::STATUS_HIDDEN));
@endphp

@section('content')
<div class="w-full space-y-0.5 pb-6"
     x-data="{
        search: '',
        statusFilter: 'all',
        viewMode: 'table',
        matches(theme) {
            const matchesSearch = !this.search || 
                theme.nameEn.toLowerCase().includes(this.search.toLowerCase()) ||
                theme.nameMm.toLowerCase().includes(this.search.toLowerCase()) ||
                theme.id.toLowerCase().includes(this.search.toLowerCase()) ||
                theme.description.toLowerCase().includes(this.search.toLowerCase());
            
            const matchesStatus = this.statusFilter === 'all' || theme.status === this.statusFilter;
            
            return matchesSearch && matchesStatus;
        }
     }">

    {{-- 1. Top Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-base font-bold shadow-xs flex-shrink-0">
                🎨
            </span>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                    <span>{{ __('messages.theme_governance') }}</span>
                    <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">(Theme Governance)</span>
                </h1>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.theme_governance_sub') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60 shadow-2xs">
                <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                Platform Owner Only
            </span>
        </div>
    </div>

    {{-- 2. KPI / Metrics Cards (Row-based centered alignment) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Total Curated Themes --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300 shadow-inner text-xs sm:text-sm font-bold">
                🎨
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ $totalCount }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.theme_total_count') }}
                </p>
            </div>
        </div>

        {{-- Active Themes --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-emerald-200/70 dark:border-emerald-900/50 shadow-2xs bg-emerald-50/20 dark:bg-emerald-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300 shadow-inner text-xs sm:text-sm font-bold">
                ✨
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    {{ $activeCount }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.theme_active_count') }}
                </p>
            </div>
        </div>

        {{-- Deprecated Themes --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-amber-200/70 dark:border-amber-900/50 shadow-2xs bg-amber-50/20 dark:bg-amber-950/10 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300 shadow-inner text-xs sm:text-sm font-bold">
                ⚠️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">
                    {{ $deprecatedCount }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-amber-600/80 dark:text-amber-400/80 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.theme_deprecated_count') }}
                </p>
            </div>
        </div>

        {{-- Hidden Themes --}}
        <div class="bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-center gap-2.5 sm:gap-3">
            <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-lg grid place-items-center bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 shadow-inner text-xs sm:text-sm font-bold">
                👁️‍🗨️
            </div>
            <div class="min-w-0">
                <div class="text-sm sm:text-base font-black text-slate-700 dark:text-slate-300 leading-none tabular-nums font-outfit">
                    {{ $hiddenCount }}
                </div>
                <p class="text-[9px] sm:text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.theme_hidden_count') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5 shadow-2xs">
            <span class="font-bold text-sm">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-0.5 shadow-2xs">
            <div class="flex items-center gap-1 font-bold">
                <span>⚠️</span>
                <span>{{ __('messages.validation_error') }}</span>
            </div>
            <ul class="list-disc list-inside text-xs pl-2 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 3. Interactive Search & Filter Toolbar --}}
    <div class="bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-1">
        {{-- Left: Search input & Status filter pills --}}
        <div class="flex flex-wrap items-center gap-1.5 flex-1">
            {{-- Search Bar --}}
            <div class="relative min-w-[180px] sm:min-w-[240px] flex-1 max-w-sm">
                <input type="text"
                       x-model="search"
                       placeholder="{{ __('messages.search_placeholder') }} (ID, Name, Description)..."
                       class="w-full h-7 pl-8 pr-2.5 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-violet-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </div>

            {{-- Filter Pills --}}
            <div class="flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                <button type="button"
                        @click="statusFilter = 'all'"
                        class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer"
                        :class="statusFilter === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                    {{ __('messages.all') ?? 'All' }} ({{ $totalCount }})
                </button>
                <button type="button"
                        @click="statusFilter = 'active'"
                        class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer"
                        :class="statusFilter === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-emerald-600'">
                    {{ __('messages.theme_status_active') }} ({{ $activeCount }})
                </button>
                <button type="button"
                        @click="statusFilter = 'deprecated'"
                        class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer"
                        :class="statusFilter === 'deprecated' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-amber-600'">
                    {{ __('messages.theme_status_deprecated') }} ({{ $deprecatedCount }})
                </button>
                <button type="button"
                        @click="statusFilter = 'hidden'"
                        class="px-2 py-0.5 rounded text-[11px] font-bold transition cursor-pointer"
                        :class="statusFilter === 'hidden' ? 'bg-slate-200 dark:bg-slate-600 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                    {{ __('messages.theme_status_hidden') }} ({{ $hiddenCount }})
                </button>
            </div>
        </div>

        {{-- Right: View mode switcher (Table vs Card view) --}}
        <div class="flex items-center gap-0.5 self-end sm:self-auto bg-slate-100 dark:bg-slate-800/80 p-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
            <button type="button"
                    @click="viewMode = 'table'"
                    class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                <span>{{ __('messages.view_table') ?? 'Table' }}</span>
            </button>
            <button type="button"
                    @click="viewMode = 'card'"
                    class="px-2 py-0.5 rounded text-[11px] font-bold flex items-center gap-1 transition cursor-pointer"
                    :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-2xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>{{ __('messages.view_cards') ?? 'Cards' }}</span>
            </button>
        </div>
    </div>

    {{-- 4. Table View Standard --}}
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 sticky top-0 z-10 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-3 min-w-[200px]">Theme</th>
                        <th class="px-4 py-3 min-w-[130px]">ID & Version</th>
                        <th class="px-4 py-3 min-w-[120px]">{{ __('messages.theme_status') }}</th>
                        <th class="px-4 py-3 min-w-[160px]">{{ __('messages.theme_replacement') }}</th>
                        <th class="px-4 py-3 min-w-[320px] text-right">Lifecycle Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($themes as $theme)
                        <tr x-show="matches({
                                id: '{{ addslashes($theme->id) }}',
                                nameEn: '{{ addslashes($theme->nameEn) }}',
                                nameMm: '{{ addslashes($theme->nameMm) }}',
                                description: '{{ addslashes($theme->description) }}',
                                status: '{{ $theme->status }}'
                            })"
                            class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                            
                            {{-- Theme Name & Palette --}}
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center gap-3">
                                    {{-- Color Swatches Ribbon --}}
                                    <div class="flex -space-x-1 flex-shrink-0">
                                        <span class="h-6 w-6 rounded-full ring-2 ring-white dark:ring-slate-900 shadow-xs"
                                              style="background: {{ $theme->primaryColor() }}"
                                              title="Primary: {{ $theme->primaryColor() }}"></span>
                                        <span class="h-6 w-6 rounded-full ring-2 ring-white dark:ring-slate-900 shadow-xs"
                                              style="background: {{ $theme->accentColor() }}"
                                              title="Accent: {{ $theme->accentColor() }}"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-black text-sm text-slate-800 dark:text-slate-100 flex items-center gap-1.5 truncate">
                                            <span>{{ $theme->nameEn }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium truncate">
                                            {{ $theme->nameMm }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- ID & Version --}}
                            <td class="px-4 py-3 align-middle">
                                <div class="flex flex-col gap-1">
                                    <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md w-fit border border-slate-200/60 dark:border-slate-700">
                                        {{ $theme->id }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        Release v{{ $theme->version }}
                                    </span>
                                </div>
                            </td>

                            {{-- Current Status Badge --}}
                            <td class="px-4 py-3 align-middle">
                                @if ($theme->status === \App\Models\ThemeGovernance::STATUS_ACTIVE)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 shadow-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ __('messages.theme_status_active') }}
                                    </span>
                                @elseif ($theme->status === \App\Models\ThemeGovernance::STATUS_DEPRECATED)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 shadow-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        {{ __('messages.theme_status_deprecated') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700 shadow-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        {{ __('messages.theme_status_hidden') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Replacement Link --}}
                            <td class="px-4 py-3 align-middle">
                                @if ($theme->status === \App\Models\ThemeGovernance::STATUS_DEPRECATED && $theme->replacementId)
                                    <span class="inline-flex items-center gap-1 font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800">
                                        <span>↳</span>
                                        <span>{{ $theme->replacementId }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Inline Management Form --}}
                            <td class="px-4 py-3 align-middle text-right">
                                <form method="POST" action="{{ route('admin.theme-governance.update') }}" class="inline-flex items-center justify-end gap-2 flex-wrap sm:flex-nowrap">
                                    @csrf
                                    <input type="hidden" name="theme_id" value="{{ $theme->id }}">

                                    {{-- Status Selector --}}
                                    <div class="relative">
                                        <select name="status"
                                                aria-label="{{ __('messages.theme_status') }}"
                                                class="h-8 pl-2.5 pr-7 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-violet-500 focus:outline-none shadow-xs">
                                            @foreach (\App\Models\ThemeGovernance::STATUSES as $status)
                                                <option value="{{ $status }}" @selected($theme->status === $status)>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Replacement Selector --}}
                                    <div class="relative">
                                        <select name="replacement_id"
                                                aria-label="{{ __('messages.theme_replacement') }}"
                                                class="h-8 pl-2.5 pr-7 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-violet-500 focus:outline-none shadow-xs">
                                            <option value="">— {{ __('messages.theme_replacement_none') }} —</option>
                                            @foreach ($allIds as $candidateId)
                                                @if ($candidateId !== $theme->id)
                                                    <option value="{{ $candidateId }}" @selected($theme->replacementId === $candidateId)>
                                                        {{ $candidateId }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Save Button --}}
                                    <button type="submit"
                                            class="h-8 px-3.5 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-xs hover:shadow-violet-500/20 transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                            <polyline points="7 3 7 8 15 8"></polyline>
                                        </svg>
                                        <span>{{ __('messages.save') }}</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 5. Card View Grid (Responsive) --}}
    <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0.5 sm:gap-1">
        @foreach ($themes as $theme)
            <div x-show="matches({
                    id: '{{ addslashes($theme->id) }}',
                    nameEn: '{{ addslashes($theme->nameEn) }}',
                    nameMm: '{{ addslashes($theme->nameMm) }}',
                    description: '{{ addslashes($theme->description) }}',
                    status: '{{ $theme->status }}'
                 })"
                 class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:shadow-xs transition overflow-hidden flex flex-col justify-between">
                
                {{-- Top Color Bar --}}
                <div class="h-2 w-full flex" style="background: linear-gradient(90deg, {{ $theme->primaryColor() }} 50%, {{ $theme->accentColor() }} 50%)"></div>

                <div class="p-2 space-y-1.5 flex-1 flex flex-col justify-between">
                    <div>
                        {{-- Header with Swatch & Status --}}
                        <div class="flex items-start justify-between gap-1.5">
                            <div class="flex items-center gap-1.5">
                                <div class="flex -space-x-1 flex-shrink-0">
                                    <span class="h-4 w-4 rounded-full ring-1 ring-white dark:ring-slate-900 shadow-xs" style="background: {{ $theme->primaryColor() }}"></span>
                                    <span class="h-4 w-4 rounded-full ring-1 ring-white dark:ring-slate-900 shadow-xs" style="background: {{ $theme->accentColor() }}"></span>
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-slate-900 dark:text-white leading-tight">
                                        {{ $theme->nameEn }}
                                    </h3>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                        {{ $theme->nameMm }}
                                    </p>
                                </div>
                            </div>

                            @if ($theme->status === \App\Models\ThemeGovernance::STATUS_ACTIVE)
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    Active
                                </span>
                            @elseif ($theme->status === \App\Models\ThemeGovernance::STATUS_DEPRECATED)
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300">
                                    Deprecated
                                </span>
                            @else
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400">
                                    Hidden
                                </span>
                            @endif
                        </div>

                        {{-- Metadata ID & Description --}}
                        <div class="mt-1 flex items-center gap-1.5">
                            <span class="font-mono text-[10px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200/60 dark:border-slate-700">
                                {{ $theme->id }}
                            </span>
                            <span class="text-[9px] text-slate-400">v{{ $theme->version }}</span>
                        </div>

                        <p class="mt-1 text-[11px] text-slate-600 dark:text-slate-300 line-clamp-2 leading-tight">
                            {{ $theme->description }}
                        </p>

                        @if ($theme->status === \App\Models\ThemeGovernance::STATUS_DEPRECATED && $theme->replacementId)
                            <div class="mt-1 text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-800 flex items-center gap-1">
                                <span>↳</span>
                                <span>Replace: {{ $theme->replacementId }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Form Footer --}}
                    <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800">
                        <form method="POST" action="{{ route('admin.theme-governance.update') }}" class="space-y-1">
                            @csrf
                            <input type="hidden" name="theme_id" value="{{ $theme->id }}">

                            <div class="grid grid-cols-2 gap-1">
                                <div>
                                    <label class="block text-[9px] font-bold uppercase text-slate-400 mb-0.5">{{ __('messages.theme_status') }}</label>
                                    <select name="status"
                                            class="w-full h-7 px-1.5 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200">
                                        @foreach (\App\Models\ThemeGovernance::STATUSES as $status)
                                            <option value="{{ $status }}" @selected($theme->status === $status)>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[9px] font-bold uppercase text-slate-400 mb-0.5">{{ __('messages.theme_replacement') }}</label>
                                    <select name="replacement_id"
                                            class="w-full h-7 px-1.5 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-200">
                                        <option value="">— None —</option>
                                        @foreach ($allIds as $candidateId)
                                            @if ($candidateId !== $theme->id)
                                                <option value="{{ $candidateId }}" @selected($theme->replacementId === $candidateId)>
                                                    {{ $candidateId }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button type="submit"
                                    class="w-full h-7 rounded bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-2xs transition flex items-center justify-center gap-1 active:scale-95 cursor-pointer">
                                <span>{{ __('messages.save') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 6. Lifecycle Policy & Technical Explanation Card --}}
    <div class="rounded-lg border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-2.5 shadow-2xs space-y-1.5">
        <div class="flex items-center gap-1.5 text-xs font-black text-slate-900 dark:text-white">
            <span>🛡️</span>
            <span>{{ __('messages.theme_lifecycle_rules') }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-1 text-xs">
            <div class="p-2 rounded-lg bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-900/50 space-y-0.5">
                <div class="font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Active (အသုံးပြုနိုင်)</span>
                </div>
                <p class="text-slate-600 dark:text-slate-300 text-[10.5px] leading-tight">
                    {{ __('messages.theme_lifecycle_active_desc') }}
                </p>
            </div>

            <div class="p-2 rounded-lg bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/50 space-y-0.5">
                <div class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Deprecated (ရပ်ဆိုင်းရန်လျာထား)</span>
                </div>
                <p class="text-slate-600 dark:text-slate-300 text-[10.5px] leading-tight">
                    {{ __('messages.theme_lifecycle_deprecated_desc') }}
                </p>
            </div>

            <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/60 space-y-0.5">
                <div class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    <span>Hidden (ဝှက်ထားသည်)</span>
                </div>
                <p class="text-slate-600 dark:text-slate-300 text-[10.5px] leading-tight">
                    {{ __('messages.theme_lifecycle_hidden_desc') }}
                </p>
            </div>
        </div>

        <div class="pt-1 border-t border-slate-100 dark:border-slate-800 text-[10.5px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
            <span>📝</span>
            <span>{{ __('messages.theme_lifecycle_audit_note') }}</span>
        </div>
    </div>
</div>
@endsection
