@extends('layouts.admin.app')

@section('content')
<div class="space-y-6 max-w-6xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">Pilot Demo Stores</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                Local/UAT testing အတွက် လုပ်ငန်းအမျိုးအစားအလိုက် demo store, users, warehouses, suppliers, products, opening stock data တည်ဆောက်ရန်။
            </p>
        </div>
        <a href="{{ route('store.admin.dashboard', ['store_slug' => $store->slug]) }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline whitespace-nowrap">&larr; Back to Dashboard</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-lg p-4 text-sm text-red-700 dark:text-red-300 space-y-1">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if (session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 text-sm font-semibold text-emerald-800 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100">Demo Business Builder</h2>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                    ခလုပ်တစ်ချက်နှိပ်ပြီး test ဆိုင်အသစ်တည်ဆောက်နိုင်ပါတယ်။ ထပ်နှိပ်မိရင် duplicate မပွားဘဲ ရှိပြီးသား data ကို update လုပ်ပါမယ်။
                </p>
            </div>
            <span class="shrink-0 inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold {{ $demoScenariosEnabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                {{ $demoScenariosEnabled ? 'Enabled for local testing' : 'Disabled outside local quick-login mode' }}
            </span>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($demoScenarios as $key => $scenario)
                <div class="rounded-lg border border-gray-200 dark:border-slate-700 p-4 space-y-3">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-slate-100">{{ $scenario['label'] }}</h3>
                        <p class="text-xs font-semibold text-violet-600 dark:text-violet-300 mt-0.5">{{ $scenario['subtitle'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-2">{{ $scenario['description'] }}</p>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-2">Store slug: {{ $scenario['store_slug'] }}</p>
                    </div>
                    <form method="POST" action="{{ route('store.admin.pilot-import.demo-scenarios.store', ['store_slug' => $store->slug, 'scenario' => $key]) }}">
                        @csrf
                        <button type="submit"
                            class="w-full px-3 py-2 rounded-md text-sm font-semibold text-white bg-violet-600 hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            @disabled(!$demoScenariosEnabled)
                        >
                            Create / Update {{ $scenario['label'] }} Demo
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
