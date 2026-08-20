@extends('layouts.pos.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('messages.sidebar_payables') }}</p>
                <h1 class="text-xl font-black mt-0.5">{{ __('messages.payables_title') }}</h1>
            </div>
            <a href="{{ url('/store/' . $store->slug . '/pos/purchases') }}"
               class="rounded-xl px-4 py-2 text-sm font-bold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition">
                ← {{ __('messages.back_to_po_list') }}
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-4 py-3 text-sm font-semibold">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-rose-300 dark:border-rose-700 bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-300 px-4 py-3 text-sm font-semibold">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        {{-- Total outstanding summary --}}
        <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-5 shadow-sm">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">{{ __('messages.payables_total_outstanding') }}</p>
                    <p class="text-3xl font-black text-amber-800 dark:text-amber-300 mt-1">
                        Ks {{ number_format((float) $totalOutstanding) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/export?format=excel') }}"
                       class="rounded-xl px-4 py-2 text-sm font-bold bg-sky-600 hover:bg-sky-500 text-white transition">
                        📊 {{ __('messages.export_excel') }}
                    </a>
                    <a href="{{ url('/store/' . $store->slug . '/pos/purchases/export?format=pdf') }}"
                       class="rounded-xl px-4 py-2 text-sm font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">
                        📄 {{ __('messages.export_pdf') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Suppliers with balances --}}
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            @if ($suppliers->isEmpty())
                <p class="text-center text-sm text-slate-500 py-8">{{ __('messages.payables_none') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-3 py-2">{{ __('messages.payables_supplier') }}</th>
                                <th class="text-left px-3 py-2">{{ __('messages.payables_contact') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.payables_unpaid_pos') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.payables_outstanding') }}</th>
                                <th class="text-right px-3 py-2">{{ __('messages.payables_oldest') }}</th>
                                <th class="text-right px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($suppliers as $s)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="px-3 py-2.5">
                                        <span class="font-bold">{{ $s['supplier']->name }}</span>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if ($s['supplier']->contact_person)
                                            <div class="text-sm">{{ $s['supplier']->contact_person }}</div>
                                        @endif
                                        @if ($s['supplier']->phone)
                                            <div class="text-xs text-slate-400">{{ $s['supplier']->phone }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right">
                                        <span class="inline-block rounded-lg px-2 py-0.5 text-xs font-bold bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">
                                            {{ $s['unpaid_count'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                        Ks {{ number_format((float) $s['balance']) }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right text-xs text-slate-400">
                                        @if ($s['oldest_unpaid_date'])
                                            {{ $s['oldest_unpaid_date']->format('d M Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right">
                                        <a href="{{ url('/store/' . $store->slug . '/pos/purchases/payables/' . $s['supplier']->id) }}"
                                           class="text-xs font-bold text-sky-600 hover:underline">{{ __('messages.payables_view') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection