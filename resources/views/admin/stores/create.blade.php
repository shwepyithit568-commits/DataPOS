@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('messages.store_create_title') }}</h1>
            <p class="admin-page-sub">{{ __('messages.store_management_sub') }}</p>
        </div>
        <a href="{{ route('admin.stores.index') }}" class="shrink-0 text-xs text-violet-600 dark:text-violet-400 font-semibold hover:underline inline-flex items-center gap-1">
            &larr; {{ __('messages.back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="p-3.5 sm:p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300 space-y-1">
            <div class="flex items-center gap-2 font-bold"><span>⚠️</span><span>Errors:</span></div>
            @foreach ($errors->all() as $error)
                <div class="pl-6">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    @include('admin.stores._form')
</div>
@endsection
