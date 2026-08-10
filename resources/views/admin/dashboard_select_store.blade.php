@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">Platform Owner Store Selector</h1>
    <p class="text-sm text-gray-600 dark:text-slate-400">Please select a store to view its specific admin control dashboard:</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        @foreach ($stores as $store)
            <a href="{{ url('/store/' . $store->slug . '/admin/dashboard') }}" class="group p-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-md hover:bg-violet-50/40 dark:hover:bg-slate-800 transition">
                <h3 class="font-bold text-lg text-violet-600 dark:text-violet-400 font-outfit">{{ $store->name }}</h3>
                <p class="text-xs text-gray-400 dark:text-slate-500 font-mono">Slug: {{ $store->slug }}</p>
            </a>
        @endforeach
    </div>
</div>
@endsection
