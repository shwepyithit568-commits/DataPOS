@extends('layouts.admin.app')

@section('title', $application->business_name . ' — Wholesale Application · ' . $store->name)

@php
    $storeRouteParams = ['store_slug' => $store->slug];
@endphp

@section('content')
<div class="w-full space-y-5 sm:space-y-6 pb-12">

    {{-- Top Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('store.admin.wholesale.applications.index', $storeRouteParams) }}"
               class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 grid place-items-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm">
                ←
            </a>
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
                    <a href="{{ route('store.admin.wholesale.applications.index', $storeRouteParams) }}" class="hover:text-slate-600 dark:hover:text-slate-300 transition">
                        Wholesale Applications
                    </a>
                    <span>/</span>
                    <span class="text-indigo-600 dark:text-indigo-400">Application Detail</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span class="truncate">{{ $application->business_name }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase whitespace-nowrap
                        {{ $application->status === 'approved'  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : '' }}
                        {{ $application->status === 'pending'   ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : '' }}
                        {{ $application->status === 'rejected'  ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' : '' }}
                        {{ $application->status === 'suspended' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : '' }}">
                        {{ $application->status }}
                    </span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $application->created_at->format('M d, Y h:i A') }} · {{ $store->name }}</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <a href="{{ route('store.admin.wholesale.applications.print', array_merge($storeRouteParams, ['application' => $application->id])) }}" target="_blank"
               class="px-4 py-2.5 rounded-2xl text-xs font-black bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                <span>🧾</span>
                <span>Print Application Slip</span>
            </a>

            @if (auth()->user()->isPlatformOwner() || auth()->user()->hasStoreRole($store->id, ['store_manager']))
                <form method="POST"
                      action="{{ route('store.admin.wholesale.applications.destroy', array_merge($storeRouteParams, ['application' => $application->id])) }}"
                      onsubmit="return confirm('\"{{ $application->business_name }}\" ၏ လျှောက်လွှာကို ဖျက်မည်မှာ သေချာပါသလား?\n\nဤလုပ်ဆောင်ချက်ကို ပြန်မလုပ်နိုင်ပါ။');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-3.5 py-2.5 rounded-2xl text-xs font-black bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-900 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition flex items-center gap-2 shadow-sm">
                        <span>🗑</span>
                        <span>ဖျက်မည်</span>
                    </button>
                </form>
            @endif
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

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Left 2 Columns: Applicant & Business Info --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Applicant Info Card --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>👤</span>
                    <span>လျှောက်ထားသူ အချက်အလက် (Applicant & Business Info)</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">လျှောက်ထားသူ (Applicant)</div>
                        <div class="font-black text-slate-900 dark:text-slate-100 text-sm flex items-center gap-1.5">
                            <span>{{ $application->user?->name ?? 'Guest Applicant' }}</span>
                            @if ($application->user_id)
                                <a href="{{ route('store.admin.customers.show', array_merge($storeRouteParams, ['customer' => $application->user_id])) }}"
                                   class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    (View Customer Profile →)
                                </a>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">ဖုန်းနံပါတ် (Phone)</div>
                        <div class="font-mono font-bold text-slate-900 dark:text-slate-100 text-sm flex items-center gap-2">
                            <span>📞 {{ $application->phone }}</span>
                            <a href="tel:{{ $application->phone }}" class="text-[11px] px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-600 font-bold hover:bg-emerald-100">
                                Call
                            </a>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">လုပ်ငန်းအမည် (Business / Shop Name)</div>
                        <div class="font-black text-indigo-600 dark:text-indigo-400 text-base">{{ $application->business_name }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">လျှောက်ထားသောရက် (Applied Date)</div>
                        <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $application->created_at->format('d M Y, h:i A') }}</div>
                        <div class="text-[11px] text-slate-400">{{ $application->created_at->diffForHumans() }}</div>
                    </div>
                </div>

                @if ($application->address)
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">လုပ်ငန်းလိပ်စာ (Business Address)</div>
                        <div class="text-sm text-slate-700 dark:text-slate-300 flex items-start gap-2">
                            <span class="flex-shrink-0">📍</span>
                            <span>{{ $application->address }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Applicant Note Card --}}
            @if ($application->notes)
                <div class="rounded-3xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 p-5 sm:p-6 shadow-sm space-y-2">
                    <h3 class="text-xs font-black uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-2">
                        <span>💬</span>
                        <span>လျှောက်ထားသူ၏ မှတ်ချက် (Applicant Note)</span>
                    </h3>
                    <p class="text-sm text-blue-900 dark:text-blue-100 leading-relaxed">{{ $application->notes }}</p>
                </div>
            @endif

        </div>

        {{-- Right 1 Column: Decision Form & Meta --}}
        <div class="space-y-4">

            {{-- Decision Form Card --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm space-y-4">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>📋</span>
                    <span>စိစစ်ဆုံးဖြတ်ချက် (Decision & Status)</span>
                </h3>

                <form method="POST" action="{{ route('store.admin.wholesale.applications.update', array_merge($storeRouteParams, ['application' => $application->id])) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    {{-- Status Radio Choices --}}
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2 block">Application Status</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ([
                                'pending'   => ['⏳', 'Pending',   'amber'],
                                'approved'  => ['✅', 'Approve',   'emerald'],
                                'rejected'  => ['❌', 'Reject',    'rose'],
                                'suspended' => ['🚫', 'Suspend',   'slate'],
                            ] as $val => [$icon, $label, $color])
                                <label class="flex items-center gap-2 p-2.5 rounded-2xl border-2 cursor-pointer transition-all
                                    {{ $application->status === $val
                                        ? 'border-' . $color . '-400 bg-' . $color . '-50 dark:bg-' . $color . '-950/30'
                                        : 'border-slate-200 dark:border-slate-700 hover:border-' . $color . '-300' }}">
                                    <input type="radio" name="status" value="{{ $val }}" class="sr-only"
                                        {{ $application->status === $val ? 'checked' : '' }}>
                                    <span class="text-sm">{{ $icon }}</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Admin Note --}}
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 block">
                            📝 ဆိုင်တွင်း သီးသန့်မှတ်စု (Internal Admin Note)
                            <span class="normal-case tracking-normal font-normal text-slate-400"> — ဖောက်သည်မမြင်ရ</span>
                        </label>
                        <textarea
                            name="admin_note"
                            rows="4"
                            placeholder="ဥပမာ- ဖုန်းဖြင့် ဆက်သွယ်ပြီး လုပ်ငန်းလိုင်စင် စစ်ဆေးအတည်ပြုပြီးပါပြီ..."
                            class="w-full text-xs border border-slate-200 dark:border-slate-700 rounded-2xl px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none transition"
                        >{{ $application->admin_note }}</textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm shadow-lg shadow-indigo-500/20 transition active:scale-95">
                        ✓ အတည်ပြုသိမ်းဆည်းမည် (Save Decision)
                    </button>
                </form>
            </div>

            {{-- Application Meta --}}
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 p-5 shadow-sm space-y-3">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>ℹ️</span>
                    <span>အချက်အလက် (Application Metadata)</span>
                </h3>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Application ID</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">#{{ $application->id }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">User Account ID</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">#{{ $application->user_id ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Applied At</span>
                        <span class="text-slate-700 dark:text-slate-300">{{ $application->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-semibold">Last Updated</span>
                        <span class="text-slate-700 dark:text-slate-300">{{ $application->updated_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-semibold">Wholesale Role</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400 font-mono">wholesale_customer</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
