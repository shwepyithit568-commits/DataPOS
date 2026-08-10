@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 font-outfit">Edit Glass Item ({{ $store->name }})</h1>
        <a href="{{ $returnTo ?? url('/store/' . $store->slug . '/admin/glass-finder') }}" class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded-md hover:bg-gray-300 dark:hover:bg-slate-600 text-sm font-medium">Back to Glass Finder</a>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-md text-sm text-red-700 dark:text-red-300">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/glass-finder/' . $item->id) }}" class="bg-white dark:bg-slate-800 p-6 rounded-lg space-y-4 transition-colors duration-200">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Brand</label>
            <input type="text" name="brand" value="{{ old('brand', $item->brand) }}" required class="mt-1 block w-full border dark:border-slate-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Phone Model</label>
            <input type="text" name="phone_model" value="{{ old('phone_model', $item->phone_model) }}" required class="mt-1 block w-full border dark:border-slate-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Glass Code</label>
            <input type="text" name="glass_code" value="{{ old('glass_code', $item->glass_code) }}" required class="mt-1 block w-full border dark:border-slate-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Stock Status</label>
            <select name="stock_status" class="mt-1 block w-full border dark:border-slate-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100">
                <option value="in_stock" {{ old('stock_status', $item->stock_status) === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                <option value="out_of_stock" {{ old('stock_status', $item->stock_status) === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
        </div>

        <div>
            <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded-md hover:bg-violet-700 font-medium text-sm">Update Glass Item</button>
        </div>
    </form>
</div>
@endsection
