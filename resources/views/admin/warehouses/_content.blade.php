{{-- Warehouse Management Content --}}
@php
    $search = request('search', '');
    $sort = request('sort', 'name');
    $direction = request('direction', 'asc');
    $viewMode = request('view', 'table');
    $warehouses = $warehouses->sortBy($sort, SORT_NATURAL, $direction === 'desc');
    $total = $warehouses->count();
@endphp

<div x-data="warehousePage()" x-init="init()">
    <x-admin.toolbar
        :search="$search"
        :search-placeholder="__('messages.search_warehouse_placeholder')"
        :sort="$sort"
        :sort-options="[
            'name' => __('messages.sort_by_name'),
            'code' => __('messages.sort_by_code'),
        ]"
        :filters="[]"
        :total-count="$total"
        :show-view-toggle="true"
        :show-export-import="false"
        :view-mode="$viewMode"
        :show-pagination="false"
    />
</div>

<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-2 border-b border-neutral-200 dark:border-white/10">
        <a href="{{ route('store.admin.warehouses.index', [...$storeRouteParams]) }}" class="px-4 py-2.5 text-sm font-medium border-b-2 text-blue-600 border-blue-600 dark:text-emerald-400 dark:border-emerald-400">
            📦 {{ __('messages.warehouses_title') }} <span class="ml-1.5 inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-xs font-medium rounded-full bg-neutral-100 text-neutral-600 dark:bg-white/10 dark:text-neutral-300">{{ $total }}</span>
        </a>
    </div>
    <button @click="showCreate = true" type="button" class="inline-flex items-center gap-1.5 px-3 h-8 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-emerald-600 dark:hover:bg-emerald-700 rounded-lg transition-colors cursor-pointer">+ {{ __('messages.add_warehouse') }}</button>
</div>

@if($warehouses->isEmpty())
    <div class="text-center py-12 bg-white dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-white/10">
        <div class="text-4xl mb-3">📦</div>
        <p class="text-neutral-500 dark:text-neutral-400">{{ __('messages.no_warehouses_found') }}</p>
    </div>
@else
    <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-neutral-200 dark:border-white/10">
                <th class="text-left px-4 py-3 font-medium text-neutral-500">{{ __('messages.name') }}</th>
                <th class="text-left px-4 py-3 font-medium text-neutral-500">{{ __('messages.code') }}</th>
                <th class="text-left px-4 py-3 font-medium text-neutral-500">{{ __('messages.branch') }}</th>
                <th class="text-left px-4 py-3 font-medium text-neutral-500">{{ __('messages.status') }}</th>
                <th class="text-right px-4 py-3 font-medium text-neutral-500">{{ __('messages.actions') }}</th>
            </tr></thead>
            <tbody>
            @foreach($warehouses as $wh)
                <tr class="border-b border-neutral-100 dark:border-white/5 hover:bg-neutral-50 dark:hover:bg-white/[2%]">
                    <td class="px-4 py-3"><span class="font-medium text-neutral-900 dark:text-white">{{ $wh->name }}</span> @if($wh->is_default) <span class="text-xs text-emerald-600 dark:text-emerald-400">★ {{ __('messages.default') }}</span> @endif</td>
                    <td class="px-4 py-3 font-mono text-xs text-neutral-600">{{ $wh->code ?? '—' }}</td>
                    <td class="px-4 py-3 text-neutral-600">{{ $wh->branch->name ?? '—' }}</td>
                    <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 text-xs rounded-full {{ $wh->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-neutral-100 text-neutral-500' }}">{{ $wh->is_active ? __('messages.active') : __('messages.inactive') }}</span></td>
                    <td class="px-4 py-3 text-right">
                        <button @click="editWh = {{ json_encode(['id'=>$wh->id,'name'=>$wh->name,'code'=>$wh->code??'','branch_id'=>$wh->branch_id??'','is_active'=>$wh->is_active]) }}" class="inline-flex items-center justify-center w-8 h-8 text-neutral-500 hover:bg-neutral-100 rounded-lg cursor-pointer">✏️</button>
                        @unless($wh->is_default)<button @click="deleteWh = {{ json_encode(['id'=>$wh->id,'name'=>$wh->name]) }}" class="inline-flex items-center justify-center w-8 h-8 text-red-500 hover:bg-red-50 rounded-lg cursor-pointer">🗑️</button>@endunless
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Create --}}
<div x-show="showCreate" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showCreate=false" @keydown.escape.window="showCreate=false">
    <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-xl max-w-md w-full p-6" @click.stop>
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">{{ __('messages.add_warehouse') }}</h3>
        <form method="POST" action="{{ route('store.admin.warehouses.store', [...$storeRouteParams]) }}" class="space-y-4">@csrf
            <div><label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.name') }} *</label><input type="text" name="name" required maxlength="100" class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.code') }}</label><input type="text" name="code" maxlength="32" class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.branch') }}</label><select name="branch_id" class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm"><option value="">{{ __('messages.no_branch') }}</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="showCreate=false" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-neutral-100 rounded-lg hover:bg-neutral-200 cursor-pointer">{{ __('messages.cancel') }}</button><button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-emerald-600 rounded-lg cursor-pointer">{{ __('messages.create') }}</button></div>
        </form>
    </div>
</div>

{{-- Edit --}}
<div x-show="editWh" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="editWh=null" @keydown.escape.window="editWh=null">
    <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-xl max-w-md w-full p-6" @click.stop>
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">{{ __('messages.edit_warehouse') }}</h3>
        <form :action="'{{ route('store.admin.warehouses.update', [...$storeRouteParams, 'warehouse' => 0]) }}'.replace('/0','/'+editWh?.id)" method="POST" class="space-y-4">@csrf @method('PUT')
            <div><label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.name') }} *</label><input type="text" name="name" required maxlength="100" :value="editWh?.name" class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"></div>
            <div><label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.code') }}</label><input type="text" name="code" maxlength="32" :value="editWh?.code" class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">{{ __('messages.branch') }}</label><select name="branch_id" class="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-800 px-3 py-2 text-sm"><option value="">{{ __('messages.no_branch') }}</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" :checked="editWh?.is_active" class="rounded border-neutral-300 text-blue-600 dark:text-emerald-600"><label class="text-sm text-neutral-700 dark:text-neutral-300">{{ __('messages.active') }}</label></div>
            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="editWh=null" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-neutral-100 rounded-lg hover:bg-neutral-200 cursor-pointer">{{ __('messages.cancel') }}</button><button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-emerald-600 rounded-lg cursor-pointer">{{ __('messages.save_changes') }}</button></div>
        </form>
    </div>
</div>

{{-- Delete --}}
<div x-show="deleteWh" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="deleteWh=null" @keydown.escape.window="deleteWh=null">
    <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-xl max-w-md w-full p-6" @click.stop>
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">{{ __('messages.delete_warehouse') }}</h3>
        <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">{{ __('messages.warehouse_delete_confirm', ['name' => '']) }}<span x-text="deleteWh?.name"></span>"?</p>
        <form :action="'{{ route('store.admin.warehouses.destroy', [...$storeRouteParams, 'warehouse' => 0]) }}'.replace('/0','/'+deleteWh?.id)" method="POST">@csrf @method('DELETE')
            <div class="flex justify-end gap-2"><button type="button" @click="deleteWh=null" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-neutral-100 rounded-lg hover:bg-neutral-200 cursor-pointer">{{ __('messages.cancel') }}</button><button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg cursor-pointer">{{ __('messages.delete') }}</button></div>
        </form>
    </div>
</div>

<script>
function warehousePage() {
    return {
        showCreate: false, editWh: null, deleteWh: null,
        init() {}
    }
}
</script>
