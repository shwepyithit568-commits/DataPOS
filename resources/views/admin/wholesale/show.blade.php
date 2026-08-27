@extends('layouts.admin.app')

@section('title', $application->business_name . ' — Wholesale Application · ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5">

    {{-- ============================================================
         1. TOP PAGE HEADER — Eyebrow, Title, Status & Actions
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0 flex items-center gap-3">
            <a href="{{ route('store.admin.wholesale.applications.index', $storeRouteParams) }}"
               class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition shadow-2xs shrink-0"
               title="{{ __('messages.back') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="min-w-0">
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-indigo-100 dark:border-indigo-900/60 mb-0.5">
                    <span>💼</span>
                    <span>Wholesale Application</span>
                    <span class="text-slate-400 dark:text-slate-500">·</span>
                    <span class="font-normal normal-case text-slate-500 dark:text-slate-400">ID #{{ $application->id }}</span>
                </div>
                <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2 truncate">
                    <span class="truncate">{{ $application->business_name }}</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase whitespace-nowrap
                        {{ $application->status === 'approved'  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                        {{ $application->status === 'pending'   ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                        {{ $application->status === 'rejected'  ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}
                        {{ $application->status === 'suspended' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                        {{ $application->status }}
                    </span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                    {{ $application->created_at->format('M d, Y h:i A') }} · {{ $store->name }}
                </p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto">
            <a href="{{ route('store.admin.wholesale.applications.print', array_merge($storeRouteParams, ['application' => $application->id])) }}" target="_blank"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <span>🧾</span>
                <span>Print Slip</span>
            </a>

            @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                <form method="POST"
                      action="{{ route('store.admin.wholesale.applications.destroy', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                      onsubmit="return confirm('{{ __('messages.wholesale_delete_confirm') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 border border-rose-200 dark:border-rose-900 transition flex items-center gap-1.5 active:scale-95 shadow-2xs cursor-pointer">
                        <span>🗑</span>
                        <span>ဖျက်မည်</span>
                    </button>
                </form>
            @endif
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
         2. MAIN 2-COLUMN GRID — Details & Decision Form
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 sm:gap-2.5 items-start">

        {{-- Left 2 Columns: Applicant & Business Info --}}
        <div class="lg:col-span-2 space-y-2 sm:space-y-2.5">

            {{-- Applicant Info Card --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs">👤</span>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Applicant & Business Information</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('messages.wholesale_applicant') }}</div>
                        <div class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <span>{{ $application->user?->name ?? 'Guest Applicant' }}</span>
                            @if ($application->user_id)
                                <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $application->user_id])) }}"
                                   class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    (Profile →)
                                </a>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('messages.wholesale_phone') }}</div>
                        <div class="font-mono font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                            <span>📞 {{ $application->phone }}</span>
                            <a href="tel:{{ $application->phone }}" class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950 text-emerald-600 font-bold hover:bg-emerald-100">
                                Call
                            </a>
                        </div>
                    </div>

                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('messages.wholesale_business_name') }}</div>
                        <div class="font-black text-indigo-600 dark:text-indigo-400 text-sm">{{ $application->business_name }}</div>
                    </div>

                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('messages.wholesale_applied_date') }}</div>
                        <div class="font-semibold text-slate-800 dark:text-slate-200 font-mono">{{ $application->created_at->format('d M Y, h:i A') }}</div>
                        <div class="text-[10px] text-slate-400">{{ $application->created_at->diffForHumans() }}</div>
                    </div>
                </div>

                @if ($application->address)
                    <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('messages.wholesale_address') }}</div>
                        <div class="text-slate-700 dark:text-slate-300 flex items-start gap-1.5">
                            <span class="shrink-0">📍</span>
                            <span>{{ $application->address }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Applicant Note Card --}}
            @if ($application->notes)
                <div class="bg-blue-50 dark:bg-blue-950/40 rounded-lg border border-blue-200 dark:border-blue-900/60 p-3 sm:p-4 shadow-2xs space-y-1.5">
                    <h3 class="text-xs font-black uppercase tracking-wider text-blue-700 dark:text-blue-300 flex items-center gap-1.5">
                        <span>💬</span>
                        <span>လျှောက်ထားသူ၏ မှတ်ချက် (Applicant Note)</span>
                    </h3>
                    <p class="text-xs text-blue-900 dark:text-blue-100 leading-relaxed">{{ $application->notes }}</p>
                </div>
            @endif

        </div>

        {{-- Right 1 Column: Decision Form & Metadata --}}
        <div class="space-y-2 sm:space-y-2.5">

            {{-- Decision Form Card --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs">📋</span>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">{{ __('messages.wholesale_decision_title') }}</h2>
                </div>

                <form method="POST" action="{{ route('store.admin.wholesale.applications.update', array_merge($storeRouteParams, ['application' => $application->id])) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')

                    {{-- Status Choices --}}
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 block">Application Status</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            @foreach ([
                                'pending'   => ['⏳', 'Pending',   'amber'],
                                'approved'  => ['✅', 'Approve',   'emerald'],
                                'rejected'  => ['❌', 'Reject',    'rose'],
                                'suspended' => ['🚫', 'Suspend',   'slate'],
                            ] as $val => [$icon, $label, $color])
                                <label class="flex items-center gap-1.5 p-2 rounded-lg border cursor-pointer transition-all text-xs
                                    {{ $application->status === $val
                                        ? 'border-' . $color . '-500 bg-' . $color . '-50 dark:bg-' . $color . '-950/40 ring-1 ring-' . $color . '-500/20'
                                        : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600' }}">
                                    <input type="radio" name="status" value="{{ $val }}" class="sr-only"
                                        {{ $application->status === $val ? 'checked' : '' }}>
                                    <span class="text-xs">{{ $icon }}</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Admin Note --}}
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1 block">
                            {{ __('messages.wholesale_internal_note') }}
                        </label>
                        <textarea
                            name="admin_note"
                            rows="3"
                            placeholder="ဥပမာ- ဖုန်းဖြင့် ဆက်သွယ်ပြီး လုပ်ငန်းလိုင်စင် စစ်ဆေးအတည်ပြုပြီးပါပြီ..."
                            class="w-full text-xs border border-slate-200 dark:border-slate-700 rounded-lg p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none transition"
                        >{{ $application->admin_note }}</textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs shadow-2xs transition active:scale-95 cursor-pointer">
                        {{ __('messages.wholesale_save_decision') }}
                    </button>
                </form>
            </div>

            {{-- Application Metadata --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-2">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs">ℹ️</span>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Metadata</h2>
                </div>
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Application ID</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">#{{ $application->id }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">User ID</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">#{{ $application->user_id ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Applied At</span>
                        <span class="text-slate-700 dark:text-slate-300 font-mono text-[11px]">{{ $application->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Last Updated</span>
                        <span class="text-slate-700 dark:text-slate-300 font-mono text-[11px]">{{ $application->updated_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-1.5 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-semibold">Role Tier</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400 font-mono text-[11px]">wholesale_customer</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
