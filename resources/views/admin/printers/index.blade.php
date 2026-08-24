@extends('layouts.admin.app')

@section('title', __('messages.printers_title') . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<div class="w-full space-y-5 sm:space-y-6">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="admin-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400">
                {{ __('messages.sidebar_setup') ?? 'Hardware & System Setup' }}
            </p>
            <h1 class="admin-page-title mt-0.5">
                {{ __('messages.printers_title') }}
            </h1>
            <p class="admin-page-sub mt-1">
                {{ $store->name }} · {{ __('messages.printers_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Add New Printer Button --}}
            <a href="{{ route('store.admin.printers.create', ['store_slug' => $store->slug]) }}"
               class="admin-primary-btn bg-violet-600 hover:bg-violet-500">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('messages.printers_add_new') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-sm text-emerald-800 dark:text-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         SUMMARY STATS HAIRLINE GRID
         ============================================================ --}}
    <div class="admin-hairline-grid grid-cols-2 sm:grid-cols-4">
        {{-- 1. Total Configured --}}
        <div class="admin-hairline-cell bg-violet-50/30 dark:bg-violet-950/20">
            <div class="admin-stat-label text-violet-600 dark:text-violet-400">{{ __('messages.printers_total_configured') }}</div>
            <div class="admin-stat-value text-violet-700 dark:text-violet-300 font-mono">
                {{ $stats['total_printers'] }}
            </div>
            <div class="admin-stat-sub text-slate-500">{{ $stats['active_printers'] }} Active</div>
        </div>

        {{-- 2. Default Printer --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-emerald-600 dark:text-emerald-400">{{ __('messages.printers_default_printer') }}</div>
            <div class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100 truncate mt-0.5" title="{{ $stats['default_printer_name'] }}">
                {{ $stats['default_printer_name'] }}
            </div>
            <div class="admin-stat-sub text-slate-400">{{ $stats['default_printer_type'] }}</div>
        </div>

        {{-- 3. Network LAN Printers --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-blue-600 dark:text-blue-400">{{ __('messages.printers_network_lan') }}</div>
            <div class="admin-stat-value text-blue-600 dark:text-blue-400 font-mono">
                {{ $stats['network_printers'] }}
            </div>
            <div class="admin-stat-sub text-slate-400">Ethernet / Wi-Fi TCP/IP</div>
        </div>

        {{-- 4. Bluetooth / Mobile Printers --}}
        <div class="admin-hairline-cell">
            <div class="admin-stat-label text-indigo-600 dark:text-indigo-400">{{ __('messages.printers_bluetooth_mobile') }}</div>
            <div class="admin-stat-value text-indigo-600 dark:text-indigo-400 font-mono">
                {{ $stats['bluetooth_printers'] }}
            </div>
            <div class="admin-stat-sub text-slate-400">Portable Handheld Units</div>
        </div>
    </div>

    {{-- ============================================================
         CONFIGURED PRINTER CARDS
         ============================================================ --}}
    <div class="space-y-4">
        <h2 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono">
            Configured Receipt & Thermal Printers ({{ count($printers) }})
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($printers as $p)
                @php
                    $isNet = $p->isNetwork();
                    $isBt = $p->isBluetooth();
                    $isUsb = $p->isUsb();
                    $is80 = $p->is80mm();
                @endphp
                <div class="rounded-2xl sm:rounded-3xl border {{ $p->is_default ? 'border-violet-300 dark:border-violet-700/80 bg-violet-50/20 dark:bg-violet-950/20 shadow-md ring-1 ring-violet-400/30' : 'border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm' }} p-5 flex flex-col justify-between space-y-4 transition hover:shadow-lg">

                    <div>
                        {{-- Top Badges --}}
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                {{-- Paper width badge --}}
                                <span class="px-2 py-0.5 rounded-full text-xs font-black font-mono {{ $is80 ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-200' }}">
                                    {{ $p->paper_width }}
                                </span>

                                {{-- Connection badge --}}
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $isNet ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' : ($isBt ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300' : ($isUsb ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300')) }}">
                                    {{ strtoupper($p->connection_type) }}
                                </span>

                                {{-- Role badge --}}
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-300">
                                    {{ ucwords($p->printer_role) }}
                                </span>
                            </div>

                            @if($p->is_default)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 shadow-sm">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    {{ __('messages.printers_default_badge') }}
                                </span>
                            @endif
                        </div>

                        {{-- Printer Name --}}
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100 mt-2 font-outfit">
                            {{ $p->name }}
                        </h3>

                        {{-- Connection target detail --}}
                        <div class="mt-1 text-xs font-mono text-slate-500 dark:text-slate-400">
                            @if($isNet && $p->ip_address)
                                <span>🌐 IP: <strong class="text-slate-900 dark:text-slate-200">{{ $p->ip_address }}:{{ $p->port }}</strong></span>
                            @elseif($p->device_path)
                                <span>🔌 Path: <strong class="text-slate-900 dark:text-slate-200">{{ $p->device_path }}</strong></span>
                            @else
                                <span>🖥️ Web Browser Direct Print Dialog</span>
                            @endif
                        </div>

                        {{-- Capabilities Grid --}}
                        <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                            <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                                <span class="w-2 h-2 rounded-full {{ $p->auto_cut ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                <span>Auto Cutter: <strong>{{ $p->auto_cut ? 'Yes' : 'No' }}</strong></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                                <span class="w-2 h-2 rounded-full {{ $p->cash_drawer_kick ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                <span>Drawer Kick: <strong>{{ $p->cash_drawer_kick ? 'Yes' : 'No' }}</strong></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                                <span class="w-2 h-2 rounded-full {{ $p->print_copies > 1 ? 'bg-violet-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                <span>Copies: <strong>{{ $p->print_copies }}</strong></span>
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                                <span class="w-2 h-2 rounded-full {{ $p->feed_lines > 0 ? 'bg-blue-500' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                                <span>Feed: <strong>{{ $p->feed_lines }} lines</strong></span>
                            </div>
                        </div>

                        @if($p->header_text || $p->footer_text)
                            <div class="mt-2.5 p-2 bg-slate-50 dark:bg-slate-800/60 rounded-xl text-[11px] text-slate-500 dark:text-slate-400 space-y-0.5">
                                @if($p->header_text)<p class="truncate">Header: <em>"{{ $p->header_text }}"</em></p>@endif
                                @if($p->footer_text)<p class="truncate">Footer: <em>"{{ $p->footer_text }}"</em></p>@endif
                            </div>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        {{-- Left Actions: Test Print & Set Default --}}
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('store.admin.printers.test_print', ['store_slug' => $store->slug, 'printer' => $p->id]) }}"
                               target="_blank"
                               class="px-2.5 py-1.5 text-xs font-bold rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-600 dark:hover:text-white transition shadow-sm flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>{{ __('messages.printers_test_print') }}</span>
                            </a>

                            @if(!$p->is_default)
                                <form method="POST" action="{{ route('store.admin.printers.set_default', ['store_slug' => $store->slug, 'printer' => $p->id]) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-2.5 py-1.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                        {{ __('messages.printers_set_default') }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        {{-- Right Actions: Edit & Delete --}}
                        <div class="flex items-center gap-1">
                            <a href="{{ route('store.admin.printers.edit', ['store_slug' => $store->slug, 'printer' => $p->id]) }}"
                               class="p-1.5 text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                               title="{{ __('messages.edit') }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>

                            @if(!$p->is_default)
                                <form method="POST" action="{{ route('store.admin.printers.destroy', ['store_slug' => $store->slug, 'printer' => $p->id]) }}" onsubmit="return confirm('Remove this printer configuration?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                            title="{{ __('messages.delete') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center text-slate-400">
                    <p class="text-sm font-semibold">{{ __('messages.printers_no_printers') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ============================================================
         HARDWARE & ESC/POS SETUP GUIDE
         ============================================================ --}}
    <div class="rounded-2xl sm:rounded-3xl bg-slate-50 dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 space-y-3">
        <div class="flex items-center gap-2">
            <span class="text-base">💡</span>
            <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 font-outfit">
                Thermal Receipt Printer Tips (ESC/POS)
            </h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs text-slate-600 dark:text-slate-400">
            <div class="p-3 bg-white dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800">
                <p class="font-bold text-slate-900 dark:text-slate-200 mb-1">Standard 80mm POS</p>
                <p>Best for counter cashier stations. Supports auto paper cutting and 24V cash drawer kick-out pulses.</p>
            </div>
            <div class="p-3 bg-white dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800">
                <p class="font-bold text-slate-900 dark:text-slate-200 mb-1">Network LAN (IP 9100)</p>
                <p>Plug ethernet cable into router. Set static IP (e.g. 192.168.1.200) to allow multiple POS tablets/PCs to print simultaneously.</p>
            </div>
            <div class="p-3 bg-white dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800">
                <p class="font-bold text-slate-900 dark:text-slate-200 mb-1">Bluetooth 58mm Mini</p>
                <p>Ideal for mobile delivery, outdoor sales, and phone repair counters. Compact and battery powered.</p>
            </div>
        </div>
    </div>

</div>
@endsection
