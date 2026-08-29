@extends('layouts.admin.app')

@section('title', __('messages.theme_governance') . ' - DataPOS')
@section('main_padding', 'p-2 sm:p-3')

@php
    $totalCount = count($themes);
    $activeCount = count(array_filter($themes, fn($t) => $t->status === \App\Models\ThemeGovernance::STATUS_ACTIVE));
    $deprecatedCount = count(array_filter($themes, fn($t) => $t->status === \App\Models\ThemeGovernance::STATUS_DEPRECATED));
    $hiddenCount = count(array_filter($themes, fn($t) => $t->status === \App\Models\ThemeGovernance::STATUS_HIDDEN));
@endphp

@section('content')
<div class="w-full space-y-3 sm:space-y-4 pb-12"
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-400 grid place-items-center text-xl font-bold shadow-xs flex-shrink-0">
                🎨
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    <span>{{ __('messages.admin_panel') }}</span>
                    <span>/</span>
                    <span class="text-violet-600 dark:text-violet-400">Theme Governance</span>
                </div>
                <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>{{ __('messages.theme_governance') }}</span>
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 hidden sm:inline">(Theme Governance)</span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                    {{ __('messages.theme_governance_sub') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60 shadow-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                Platform Owner Only
            </span>
        </div>
    </div>

    {{-- 2. KPI / Metrics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        {{-- Total Curated Themes --}}
        <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-bold">
                <span>{{ __('messages.theme_total_count') }}</span>
                <span class="text-sm">🎨</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black text-slate-900 dark:text-white tabular-nums">
                {{ $totalCount }}
            </div>
            <div class="mt-0.5 text-[11px] text-slate-400">
                Core Design Themes
            </div>
        </div>

        {{-- Active Themes --}}
        <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-xl border border-emerald-200/70 dark:border-emerald-900/50 shadow-xs bg-emerald-50/20 dark:bg-emerald-950/10">
            <div class="flex items-center justify-between text-emerald-700 dark:text-emerald-400 text-xs font-bold">
                <span>{{ __('messages.theme_active_count') }}</span>
                <span class="text-sm">✨</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                {{ $activeCount }}
            </div>
            <div class="mt-0.5 text-[11px] text-emerald-600/80 dark:text-emerald-400/80">
                Selectable & Recommended
            </div>
        </div>

        {{-- Deprecated Themes --}}
        <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-xl border border-amber-200/70 dark:border-amber-900/50 shadow-xs bg-amber-50/20 dark:bg-amber-950/10">
            <div class="flex items-center justify-between text-amber-700 dark:text-amber-400 text-xs font-bold">
                <span>{{ __('messages.theme_deprecated_count') }}</span>
                <span class="text-sm">⚠️</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 tabular-nums">
                {{ $deprecatedCount }}
            </div>
            <div class="mt-0.5 text-[11px] text-amber-600/80 dark:text-amber-400/80">
                Flagged with replacement
            </div>
        </div>

        {{-- Hidden Themes --}}
        <div class="bg-white dark:bg-slate-900 p-3 sm:p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs bg-slate-50/50 dark:bg-slate-800/30">
            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400 text-xs font-bold">
                <span>{{ __('messages.theme_hidden_count') }}</span>
                <span class="text-sm">👁️‍🗨️</span>
            </div>
            <div class="mt-1 text-xl sm:text-2xl font-black text-slate-700 dark:text-slate-300 tabular-nums">
                {{ $hiddenCount }}
            </div>
            <div class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                Hidden from new pickers
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="p-3 sm:p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-xs">
            <span class="font-bold text-base">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 sm:p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-xl text-xs sm:text-sm text-rose-800 dark:text-rose-300 space-y-1 shadow-xs">
            <div class="flex items-center gap-1.5 font-bold">
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
    <div class="bg-white dark:bg-slate-900 p-2.5 sm:p-3 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-2.5">
        {{-- Left: Search input & Status filter pills --}}
        <div class="flex flex-wrap items-center gap-2 flex-1">
            {{-- Search Bar --}}
            <div class="relative min-w-[200px] sm:min-w-[260px] flex-1 max-w-sm">
                <input type="text"
                       x-model="search"
                       placeholder="{{ __('messages.search_placeholder') }} (ID, Name, Description)..."
                       class="w-full h-9 pl-9 pr-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:bg-white dark:focus:bg-slate-900 transition" />
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </div>

            {{-- Filter Pills --}}
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg border border-slate-200/60 dark:border-slate-700">
                <button type="button"
                        @click="statusFilter = 'all'"
                        class="px-2.5 py-1 rounded-md text-xs font-bold transition cursor-pointer"
                        :class="statusFilter === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                    {{ __('messages.all') ?? 'All' }} ({{ $totalCount }})
                </button>
                <button type="button"
                        @click="statusFilter = 'active'"
                        class="px-2.5 py-1 rounded-md text-xs font-bold transition cursor-pointer"
                        :class="statusFilter === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-emerald-600'">
                    {{ __('messages.theme_status_active') }} ({{ $activeCount }})
                </button>
                <button type="button"
                        @click="statusFilter = 'deprecated'"
                        class="px-2.5 py-1 rounded-md text-xs font-bold transition cursor-pointer"
                        :class="statusFilter === 'deprecated' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-amber-600'">
                    {{ __('messages.theme_status_deprecated') }} ({{ $deprecatedCount }})
                </button>
                <button type="button"
                        @click="statusFilter = 'hidden'"
                        class="px-2.5 py-1 rounded-md text-xs font-bold transition cursor-pointer"
                        :class="statusFilter === 'hidden' ? 'bg-slate-200 dark:bg-slate-600 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                    {{ __('messages.theme_status_hidden') }} ({{ $hiddenCount }})
                </button>
            </div>
        </div>

        {{-- Right: View mode switcher (Table vs Card view) --}}
        <div class="flex items-center gap-1 self-end sm:self-auto bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg border border-slate-200/60 dark:border-slate-700">
            <button type="button"
                    @click="viewMode = 'table'"
                    class="px-2.5 py-1 rounded-md text-xs font-bold flex items-center gap-1.5 transition cursor-pointer"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                <span>{{ __('messages.view_table') ?? 'Table' }}</span>
            </button>
            <button type="button"
                    @click="viewMode = 'card'"
                    class="px-2.5 py-1 rounded-md text-xs font-bold flex items-center gap-1.5 transition cursor-pointer"
                    :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-violet-300 shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>{{ __('messages.view_cards') ?? 'Cards' }}</span>
            </button>
        </div>
    </div>

    {{-- 4. Table View Standard --}}
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
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
    <div x-show="viewMode === 'card'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        @foreach ($themes as $theme)
            <div x-show="matches({
                    id: '{{ addslashes($theme->id) }}',
                    nameEn: '{{ addslashes($theme->nameEn) }}',
                    nameMm: '{{ addslashes($theme->nameMm) }}',
                    description: '{{ addslashes($theme->description) }}',
                    status: '{{ $theme->status }}'
                 })"
                 class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs hover:shadow-md transition overflow-hidden flex flex-col justify-between">
                
                {{-- Top Color Bar --}}
                <div class="h-2.5 w-full flex" style="background: linear-gradient(90deg, {{ $theme->primaryColor() }} 50%, {{ $theme->accentColor() }} 50%)"></div>

                <div class="p-3.5 space-y-3 flex-1 flex flex-col justify-between">
                    <div>
                        {{-- Header with Swatch & Status --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="flex -space-x-1 flex-shrink-0">
                                    <span class="h-5 w-5 rounded-full ring-2 ring-white dark:ring-slate-900 shadow-xs" style="background: {{ $theme->primaryColor() }}"></span>
                                    <span class="h-5 w-5 rounded-full ring-2 ring-white dark:ring-slate-900 shadow-xs" style="background: {{ $theme->accentColor() }}"></span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-900 dark:text-white leading-tight">
                                        {{ $theme->nameEn }}
                                    </h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                        {{ $theme->nameMm }}
                                    </p>
                                </div>
                            </div>

                            @if ($theme->status === \App\Models\ThemeGovernance::STATUS_ACTIVE)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    Active
                                </span>
                            @elseif ($theme->status === \App\Models\ThemeGovernance::STATUS_DEPRECATED)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300">
                                    Deprecated
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400">
                                    Hidden
                                </span>
                            @endif
                        </div>

                        {{-- Metadata ID & Description --}}
                        <div class="mt-2.5 flex items-center gap-2">
                            <span class="font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md border border-slate-200/60 dark:border-slate-700">
                                {{ $theme->id }}
                            </span>
                            <span class="text-[10px] text-slate-400">v{{ $theme->version }}</span>
                        </div>

                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-300 line-clamp-2 leading-relaxed">
                            {{ $theme->description }}
                        </p>

                        @if ($theme->status === \App\Models\ThemeGovernance::STATUS_DEPRECATED && $theme->replacementId)
                            <div class="mt-2 text-[11px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-1 rounded-lg border border-amber-200 dark:border-amber-800 flex items-center gap-1.5">
                                <span>↳</span>
                                <span>Replace: {{ $theme->replacementId }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Form Footer --}}
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                        <form method="POST" action="{{ route('admin.theme-governance.update') }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="theme_id" value="{{ $theme->id }}">

                            <div class="grid grid-cols-2 gap-1.5">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-0.5">{{ __('messages.theme_status') }}</label>
                                    <select name="status"
                                            class="w-full h-8 px-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200">
                                        @foreach (\App\Models\ThemeGovernance::STATUSES as $status)
                                            <option value="{{ $status }}" @selected($theme->status === $status)>
                                                {{ ucfirst($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-0.5">{{ __('messages.theme_replacement') }}</label>
                                    <select name="replacement_id"
                                            class="w-full h-8 px-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-200">
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
                                    class="w-full h-8 rounded-lg bg-violet-600 hover:bg-violet-500 text-white text-xs font-black shadow-xs transition flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
                                <span>{{ __('messages.save') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 6. Lifecycle Policy & Technical Explanation Card --}}
    <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs space-y-2.5">
        <div class="flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white">
            <span>🛡️</span>
            <span>{{ __('messages.theme_lifecycle_rules') }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            <div class="p-2.5 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-900/50 space-y-1">
                <div class="font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Active (အသုံးပြုနိုင်)</span>
                </div>
                <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                    {{ __('messages.theme_lifecycle_active_desc') }}
                </p>
            </div>

            <div class="p-2.5 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/50 space-y-1">
                <div class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Deprecated (ရပ်ဆိုင်းရန်လျာထား)</span>
                </div>
                <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                    {{ __('messages.theme_lifecycle_deprecated_desc') }}
                </p>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/60 space-y-1">
                <div class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span>Hidden (ဝှက်ထားသည်)</span>
                </div>
                <p class="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                    {{ __('messages.theme_lifecycle_hidden_desc') }}
                </p>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
            <span>📝</span>
            <span>{{ __('messages.theme_lifecycle_audit_note') }}</span>
        </div>
    </div>
</div>
@endsection
